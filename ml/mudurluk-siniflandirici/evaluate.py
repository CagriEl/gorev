"""Kayıtlı modeli test CSV üzerinde değerlendirir."""

from __future__ import annotations

import json
from pathlib import Path

import numpy as np
import pandas as pd
import torch
from sklearn.metrics import classification_report, confusion_matrix
from transformers import AutoModelForSequenceClassification, AutoTokenizer

from config import BASE_DIR, get_settings

PROCESSED_DIR = BASE_DIR / "data" / "processed"


def main() -> None:
    settings = get_settings()
    test_path = PROCESSED_DIR / "test.csv"
    if not test_path.exists():
        raise SystemExit("Önce: python data_loader.py && python train.py")

    df = pd.read_csv(test_path)
    meta = json.loads((settings.model_dir / "label_map.json").read_text(encoding="utf-8"))
    classes = meta["classes"]

    tokenizer = AutoTokenizer.from_pretrained(settings.model_dir)
    model = AutoModelForSequenceClassification.from_pretrained(settings.model_dir)
    model.eval()

    preds = []
    confidences = []
    for text in df["sikayet_metni"].astype(str):
        inputs = tokenizer(
            text,
            return_tensors="pt",
            truncation=True,
            max_length=settings.max_length,
        )
        with torch.no_grad():
            logits = model(**inputs).logits
            probs = torch.softmax(logits, dim=-1).numpy()[0]
        idx = int(np.argmax(probs))
        preds.append(classes[idx])
        confidences.append(float(probs[idx]))

    print(classification_report(df["mudurluk_slug"], preds))
    print("Karışıklık matrisi:")
    print(confusion_matrix(df["mudurluk_slug"], preds, labels=classes))
    print(f"Ortalama güven: {np.mean(confidences):.2f}")


if __name__ == "__main__":
    main()
