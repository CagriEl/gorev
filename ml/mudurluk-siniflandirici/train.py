"""
BERTurk modelini Gorev şikayet metinleriyle ince ayar yapar.

Kullanım:
  python data_loader.py
  python train.py
  python train.py --data data/processed/train.csv
"""

from __future__ import annotations

import argparse
import json
import os
from pathlib import Path

# Mac MPS + küçük veri setlerinde kararlılık için CPU tercih edilir
os.environ.setdefault("PYTORCH_ENABLE_MPS_FALLBACK", "1")
os.environ.setdefault("TOKENIZERS_PARALLELISM", "false")

import numpy as np
import pandas as pd
import torch
from sklearn.metrics import accuracy_score, classification_report
from sklearn.preprocessing import LabelEncoder
from transformers import (
    AutoModelForSequenceClassification,
    AutoTokenizer,
    Trainer,
    TrainingArguments,
)

from config import BASE_DIR, get_settings

PROCESSED_DIR = BASE_DIR / "data" / "processed"


class TextDataset(torch.utils.data.Dataset):
    def __init__(self, texts: list[str], labels: list[int], tokenizer, max_length: int):
        self.texts = texts
        self.labels = labels
        self.tokenizer = tokenizer
        self.max_length = max_length

    def __len__(self) -> int:
        return len(self.texts)

    def __getitem__(self, idx: int) -> dict:
        encoded = self.tokenizer(
            self.texts[idx],
            truncation=True,
            padding="max_length",
            max_length=self.max_length,
            return_tensors="pt",
        )
        item = {key: val.squeeze(0) for key, val in encoded.items()}
        item["labels"] = torch.tensor(self.labels[idx], dtype=torch.long)
        return item


def load_training_frames(train_path: Path, test_path: Path | None) -> tuple[pd.DataFrame, pd.DataFrame]:
    train_df = pd.read_csv(train_path)
    if test_path and test_path.exists():
        test_df = pd.read_csv(test_path)
    else:
        test_df = train_df.sample(min(max(2, len(train_df) // 5), len(train_df)), random_state=42)
    return train_df, test_df


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--data",
        type=Path,
        default=PROCESSED_DIR / "train.csv",
        help="Eğitim CSV yolu",
    )
    parser.add_argument("--test-data", type=Path, default=PROCESSED_DIR / "test.csv")
    args = parser.parse_args()

    settings = get_settings()
    train_df, test_df = load_training_frames(args.data, args.test_data)

    encoder = LabelEncoder()
    encoder.fit(train_df["mudurluk_slug"].astype(str))
    y_train = encoder.transform(train_df["mudurluk_slug"].astype(str))
    y_test = encoder.transform(test_df["mudurluk_slug"].astype(str))

    print(f"Sınıf sayısı: {len(encoder.classes_)} -> {list(encoder.classes_)}")

    tokenizer = AutoTokenizer.from_pretrained(settings.model_name)
    model = AutoModelForSequenceClassification.from_pretrained(
        settings.model_name,
        num_labels=len(encoder.classes_),
        id2label={i: str(c) for i, c in enumerate(encoder.classes_)},
        label2id={str(c): i for i, c in enumerate(encoder.classes_)},
    )

    train_dataset = TextDataset(
        train_df["sikayet_metni"].astype(str).tolist(),
        y_train.tolist(),
        tokenizer,
        settings.max_length,
    )
    eval_dataset = TextDataset(
        test_df["sikayet_metni"].astype(str).tolist(),
        y_test.tolist(),
        tokenizer,
        settings.max_length,
    )

    output_dir = settings.model_dir
    output_dir.mkdir(parents=True, exist_ok=True)

    use_cpu = not torch.cuda.is_available()
    training_args = TrainingArguments(
        output_dir=str(output_dir / "checkpoints"),
        num_train_epochs=settings.train_epochs,
        per_device_train_batch_size=settings.train_batch_size,
        per_device_eval_batch_size=settings.train_batch_size,
        learning_rate=2e-5,
        weight_decay=0.01,
        eval_strategy="epoch",
        save_strategy="epoch",
        load_best_model_at_end=True,
        logging_steps=10,
        report_to=[],
        use_cpu=use_cpu,
        use_mps_device=False,
    )

    def compute_metrics(eval_pred):
        logits, labels = eval_pred
        preds = np.argmax(logits, axis=-1)
        return {"accuracy": accuracy_score(labels, preds)}

    trainer = Trainer(
        model=model,
        args=training_args,
        train_dataset=train_dataset,
        eval_dataset=eval_dataset,
        compute_metrics=compute_metrics,
    )

    print("Eğitim başlıyor (CPU/GPU otomatik seçilir)...")
    trainer.train()

    # Değerlendirme raporu
    preds = trainer.predict(eval_dataset)
    y_pred = np.argmax(preds.predictions, axis=-1)
    print(classification_report(y_test, y_pred, target_names=encoder.classes_))

    # Kalıcı kayıt
    trainer.save_model(str(output_dir))
    tokenizer.save_pretrained(str(output_dir))

    slug_to_name: dict[str, str] = {}
    labels_file = BASE_DIR / "labels.json"
    if labels_file.exists():
        payload = json.loads(labels_file.read_text(encoding="utf-8"))
        slug_to_name = {x["slug"]: x["name"] for x in payload.get("labels", [])}

    meta = {
        "model_name": settings.model_name,
        "classes": encoder.classes_.tolist(),
        "id2label": {str(i): str(c) for i, c in enumerate(encoder.classes_)},
        "label2id": {str(c): int(i) for i, c in enumerate(encoder.classes_)},
        "slug_to_name": slug_to_name,
        "confidence_threshold": settings.confidence_threshold,
    }
    (output_dir / "label_map.json").write_text(
        json.dumps(meta, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    print(f"Model kaydedildi: {output_dir}")


if __name__ == "__main__":
    main()
