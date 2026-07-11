"""Anahtar kelime ile müdürlük tahmini (BERT belirsizken)."""

from __future__ import annotations

import re
import unicodedata

KEYWORD_RULES: dict[str, list[str]] = {
    "TEMIZLIK_ISLERI": [
        "çöp",
        "çöpler",
        "konteyner",
        "temizlik",
        "süpür",
        "atık",
        "pislik",
        "koku",
        "çöp kutusu",
        "çöp kamyonu",
        "kirli",
        "dolmuş",
        "taşıyor",
        "moloz",
    ],
    "FEN_ISLERI": [
        "kaldırım",
        "çukur",
        "yol",
        "asfalt",
        "sokak lambası",
        "aydınlatma",
        "kanalizasyon",
        "bordür",
        "boru patla",
        "trafik levhası",
    ],
    "PARK_BAHCE": [
        "park",
        "bahçe",
        "ağaç",
        "budama",
        "çimen",
        "yeşil alan",
        "salıncak",
        "oyun alanı",
    ],
    "VETERINER": [
        "köpek",
        "kedi",
        "hayvan",
        "yaralı",
        "sokak hayvan",
        "kuş",
    ],
}


def _normalize(text: str) -> str:
    text = text.strip().lower()
    text = unicodedata.normalize("NFKC", text)
    return re.sub(r"\s+", " ", text)


def classify_by_keywords(text: str) -> tuple[str, float, int] | None:
    """(slug, confidence, score) veya None."""
    normalized = _normalize(text)
    if not normalized:
        return None

    best_slug = None
    best_score = 0

    for slug, keywords in KEYWORD_RULES.items():
        score = sum(1 for kw in keywords if kw in normalized)
        if score > best_score:
            best_score = score
            best_slug = slug

    if best_slug is None or best_score < 1:
        return None

    confidence = min(0.95, 0.78 + best_score * 0.06)
    return best_slug, confidence, best_score


def should_use_keyword_override(confidence: float, threshold: float, top_predictions: list) -> bool:
    if confidence < threshold:
        return True
    if len(top_predictions) >= 2:
        diff = abs(float(top_predictions[0]["confidence"]) - float(top_predictions[1]["confidence"]))
        if diff < 0.12:
            return True
    return False
