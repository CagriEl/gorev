"""
Gorev veritabanından veya örnek CSV'den eğitim verisi hazırlar.

Kullanım:
  python data_loader.py              # Önce DB, yoksa örnek veri
  python data_loader.py --db-only    # Sadece veritabanı
  python data_loader.py --sample-only
"""

from __future__ import annotations

import argparse
import json
import re
import unicodedata
from pathlib import Path

import pandas as pd
from sklearn.model_selection import train_test_split
from sqlalchemy import create_engine, text

from config import BASE_DIR, get_settings

PROCESSED_DIR = BASE_DIR / "data" / "processed"
SAMPLE_CSV = BASE_DIR / "data" / "sample_train.csv"
BELEDIYE_CSV = BASE_DIR / "data" / "belediye_sikayetleri.csv"
PANEL_EXPORT_CSV = BASE_DIR / "data" / "panel_export" / "panel_samples.csv"
LABELS_FILE = BASE_DIR / "labels.json"

# Kapatılmış / güvenilir görev durumları (Gorev TaskStatus)
TRUSTED_STATUSES = (
    "kapatildi",
    "tamamlandi",
    "cozuldu",
    "onay_bekliyor",
)


def slugify_department(name: str) -> str:
    """Müdürlük adından sabit slug üretir (ör. Fen İşleri → FEN_ISLERI)."""
    name = name.strip().upper()
    # Türkçe karakterleri ASCII'ye çevir
    normalized = unicodedata.normalize("NFKD", name)
    ascii_name = "".join(c for c in normalized if not unicodedata.combining(c))
    ascii_name = ascii_name.replace("İ", "I").replace("ı", "i")
    slug = re.sub(r"[^A-Z0-9]+", "_", ascii_name.upper())
    slug = re.sub(r"_+", "_", slug).strip("_")
    # "MUDURLUGU" / "MUDURLUK" sonekini kısalt (isteğe bağlı tutarlılık)
    for suffix in ("_MUDURLUGU", "_MUDURLUK"):
        if slug.endswith(suffix):
            slug = slug[: -len(suffix)]
    return slug or "BILINMEYEN"


def load_label_name_map() -> dict[str, str]:
    """slug -> tam müdürlük adı."""
    if not LABELS_FILE.exists():
        return {}
    payload = json.loads(LABELS_FILE.read_text(encoding="utf-8"))
    return {item["slug"]: item["name"] for item in payload.get("labels", [])}


def build_slug_from_db_name(name: str, known: dict[str, str]) -> str:
    slug = slugify_department(name)
    if slug in known:
        return slug
    # Tam ad eşleşmesi
    for s, full in known.items():
        if full.lower() == name.strip().lower():
            return s
    return slug


def fetch_from_database() -> pd.DataFrame:
    """Gorev tasks + departments tablolarından metin ve müdürlük çeker."""
    settings = get_settings()
    engine = create_engine(settings.database_url)

    placeholders = ", ".join(f":s{i}" for i in range(len(TRUSTED_STATUSES)))
    params = {f"s{i}": s for i, s in enumerate(TRUSTED_STATUSES)}

    dialect = engine.dialect.name
    min_len = "LENGTH" if dialect == "sqlite" else "CHAR_LENGTH"
    concat = (
        "TRIM(COALESCE(t.title, '') || ' ' || COALESCE(t.description, '') || ' ' || COALESCE(t.location, ''))"
        if dialect == "sqlite"
        else "TRIM(CONCAT_WS(' ', NULLIF(t.title, ''), NULLIF(t.description, ''), NULLIF(t.location, '')))"
    )

    soft_delete = ""
    try:
        with engine.connect() as conn:
            conn.execute(text("SELECT deleted_at FROM tasks LIMIT 1"))
        soft_delete = "AND t.deleted_at IS NULL"
    except Exception:
        pass

    sql = text(
        f"""
        SELECT
            {concat} AS sikayet_metni,
            d.name AS mudurluk_adi
        FROM tasks t
        INNER JOIN departments d ON d.id = t.department_id
        WHERE t.status IN ({placeholders})
          {soft_delete}
          AND {min_len}(TRIM(t.title)) >= 5
        """
    )

    with engine.connect() as conn:
        rows = conn.execute(sql, params).mappings().all()

    if not rows:
        return pd.DataFrame(columns=["sikayet_metni", "mudurluk_slug"])

    known = load_label_name_map()
    records = []
    for row in rows:
        text_value = (row["sikayet_metni"] or "").strip()
        dept_name = (row["mudurluk_adi"] or "").strip()
        if len(text_value) < 8 or not dept_name:
            continue
        records.append(
            {
                "sikayet_metni": text_value,
                "mudurluk_slug": build_slug_from_db_name(dept_name, known),
                "mudurluk_adi": dept_name,
            }
        )

    return pd.DataFrame(records)


def load_sample_csv() -> pd.DataFrame:
    """Yerleşik örnek veri (DB boşken eğitim/demo için)."""
    if not SAMPLE_CSV.exists():
        raise FileNotFoundError(f"Örnek veri bulunamadı: {SAMPLE_CSV}")
    df = pd.read_csv(SAMPLE_CSV)
    return df[["sikayet_metni", "mudurluk_slug"]].copy()


def load_belediye_csv() -> pd.DataFrame:
    """Genel belediye şikâyet örnekleri (geniş başlangıç seti)."""
    if not BELEDIYE_CSV.exists():
        return pd.DataFrame(columns=["sikayet_metni", "mudurluk_slug"])
    df = pd.read_csv(BELEDIYE_CSV)
    return df[["sikayet_metni", "mudurluk_slug"]].copy()


def split_and_save(df: pd.DataFrame) -> tuple[Path, Path]:
    """Train/test CSV kaydeder; sınıf başına en az 2 örnek gerekir."""
    if df.empty:
        raise ValueError("Eğitim verisi boş.")

    counts = df["mudurluk_slug"].value_counts()
    valid_slugs = counts[counts >= 2].index.tolist()
    if len(valid_slugs) < 2:
        # Çok az veri: tümünü eğitime al, test = eğitimin küçük kopyası
        train_df = df
        test_df = df.sample(min(5, len(df)), random_state=get_settings().random_seed)
    else:
        df = df[df["mudurluk_slug"].isin(valid_slugs)]
        stratify = df["mudurluk_slug"] if counts.min() >= 2 else None
        train_df, test_df = train_test_split(
            df,
            test_size=get_settings().test_size,
            random_state=get_settings().random_seed,
            stratify=stratify,
        )

    PROCESSED_DIR.mkdir(parents=True, exist_ok=True)
    train_path = PROCESSED_DIR / "train.csv"
    test_path = PROCESSED_DIR / "test.csv"
    train_df.to_csv(train_path, index=False)
    test_df.to_csv(test_path, index=False)

    # Eğitimde kullanılan slug listesi
    label_map = {
        "slug_to_name": load_label_name_map(),
        "classes": sorted(train_df["mudurluk_slug"].unique().tolist()),
    }
    (PROCESSED_DIR / "label_map.json").write_text(
        json.dumps(label_map, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    print(f"Eğitim: {len(train_df)} kayıt -> {train_path}")
    print(f"Test: {len(test_df)} kayıt -> {test_path}")
    print(f"Sınıflar: {label_map['classes']}")

    return train_path, test_path


def load_panel_export() -> pd.DataFrame:
    """Filament panelinden dışa aktarılan örnekler."""
    if not PANEL_EXPORT_CSV.exists():
        return pd.DataFrame(columns=["sikayet_metni", "mudurluk_slug"])
    return pd.read_csv(PANEL_EXPORT_CSV)[["sikayet_metni", "mudurluk_slug"]].copy()


def main() -> None:
    parser = argparse.ArgumentParser(description="Gorev verisinden eğitim seti üret")
    parser.add_argument("--db-only", action="store_true")
    parser.add_argument("--sample-only", action="store_true")
    parser.add_argument(
        "--merge-panel",
        action="store_true",
        help="Panelden girilen eğitim örneklerini DB ve örnek veriyle birleştir",
    )
    args = parser.parse_args()

    frames: list[pd.DataFrame] = []

    if not args.sample_only:
        try:
            db_df = fetch_from_database()
            print(f"Veritabanından {len(db_df)} kayıt okundu.")
            if not db_df.empty:
                frames.append(db_df)
        except Exception as exc:
            print(f"Veritabanı okunamadı ({exc}).")

    if args.merge_panel or PANEL_EXPORT_CSV.exists():
        panel_df = load_panel_export()
        if not panel_df.empty:
            frames.append(panel_df)
            print(f"Panel örnekleri: {len(panel_df)} kayıt.")

    belediye_df = load_belediye_csv()
    if not belediye_df.empty:
        frames.append(belediye_df)
        print(f"Belediye şikâyet seti: {len(belediye_df)} kayıt.")

    sample_df = load_sample_csv()
    if args.sample_only or not frames:
        frames.append(sample_df)
        print(f"Örnek CSV eklendi: {len(sample_df)} kayıt.")
    elif sum(len(f) for f in frames) < 30:
        frames.append(sample_df)
        print("Az veri: örnek CSV de eklendi.")

    df = pd.concat(frames, ignore_index=True) if frames else sample_df
    df = df.drop_duplicates(subset=["sikayet_metni"], keep="first")

    if df["mudurluk_slug"].nunique() < 2:
        df = pd.concat([df, sample_df], ignore_index=True).drop_duplicates(
            subset=["sikayet_metni"],
            keep="first",
        )
        print("En az iki sınıf için örnek CSV tamamlandı.")

    print(f"Toplam: {len(df)} kayıt, sınıflar: {sorted(df['mudurluk_slug'].unique().tolist())}")
    split_and_save(df)


if __name__ == "__main__":
    main()
