"""
FastAPI — şikayet metninden müdürlük tahmini.

Başlatma:
  uvicorn main:app --host 127.0.0.1 --port 8100

veya:
  python main.py
"""

from __future__ import annotations

import json
from contextlib import asynccontextmanager
from functools import lru_cache
from typing import Any

import torch
from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field
from transformers import AutoModelForSequenceClassification, AutoTokenizer

from config import get_settings
from keyword_rules import classify_by_keywords, should_use_keyword_override

# Global model durumu
_state: dict[str, Any] = {}


class PredictRequest(BaseModel):
    text: str = Field(..., min_length=3, max_length=2000, description="Şikayet / talep metni")


class PredictResponse(BaseModel):
    department_slug: str
    department_name: str | None
    confidence: float
    needs_review: bool
    top_predictions: list[dict[str, float | str]]
    classification_method: str = "ml"


def _verify_api_key(x_api_key: str | None = Header(default=None)) -> None:
    settings = get_settings()
    if settings.api_key and x_api_key != settings.api_key:
        raise HTTPException(status_code=401, detail="Geçersiz API anahtarı")


def _load_model() -> None:
    settings = get_settings()
    model_dir = settings.model_dir
    meta_path = model_dir / "label_map.json"
    if not meta_path.exists():
        raise FileNotFoundError(
            f"Model bulunamadı: {model_dir}. Önce: python data_loader.py && python train.py"
        )

    meta = json.loads(meta_path.read_text(encoding="utf-8"))
    _state["meta"] = meta
    _state["classes"] = meta["classes"]
    _state["id2label"] = {int(k): v for k, v in meta.get("id2label", {}).items()} or {
        i: c for i, c in enumerate(meta["classes"])
    }
    _state["threshold"] = float(meta.get("confidence_threshold", settings.confidence_threshold))

    slug_to_name = meta.get("slug_to_name") or {}
    if not slug_to_name:
        labels_file = model_dir.parent / "labels.json"
        if labels_file.exists():
            payload = json.loads(labels_file.read_text(encoding="utf-8"))
            slug_to_name = {x["slug"]: x["name"] for x in payload.get("labels", [])}
    _state["slug_to_name"] = slug_to_name

    _state["tokenizer"] = AutoTokenizer.from_pretrained(model_dir)
    _state["model"] = AutoModelForSequenceClassification.from_pretrained(model_dir)
    _state["model"].eval()


@asynccontextmanager
async def lifespan(app: FastAPI):
    try:
        _load_model()
        print(f"Model yüklendi: {get_settings().model_dir}")
    except FileNotFoundError as exc:
        print(f"UYARI: {exc}")
    yield
    _state.clear()


app = FastAPI(
    title="Bel-Sistem Müdürlük Sınıflandırıcı",
    description="BERTurk ile şikayet metni → müdürlük slug",
    version="1.0.0",
    lifespan=lifespan,
)


@app.get("/health")
def health() -> dict:
    ready = "model" in _state
    return {"status": "ok" if ready else "model_not_loaded", "ready": ready}


@app.post("/predict", response_model=PredictResponse, dependencies=[Depends(_verify_api_key)])
def predict(body: PredictRequest) -> PredictResponse:
    if "model" not in _state:
        raise HTTPException(
            status_code=503,
            detail="Model yüklenmedi. data_loader.py ve train.py çalıştırın.",
        )

    tokenizer = _state["tokenizer"]
    model = _state["model"]
    classes: list[str] = _state["classes"]
    slug_to_name: dict[str, str] = _state["slug_to_name"]
    threshold: float = _state["threshold"]

    inputs = tokenizer(
        body.text.strip(),
        return_tensors="pt",
        truncation=True,
        max_length=get_settings().max_length,
    )
    with torch.no_grad():
        logits = model(**inputs).logits
        probs = torch.softmax(logits, dim=-1).numpy()[0]

    top_idx = int(probs.argmax())
    confidence = float(probs[top_idx])
    slug = classes[top_idx]

    top_predictions = [
        {
            "department_slug": classes[i],
            "confidence": float(probs[i]),
        }
        for i in probs.argsort()[::-1][:3]
    ]

    needs_review = confidence < threshold
    method = "ml"

    if should_use_keyword_override(confidence, threshold, top_predictions):
        keyword_hit = classify_by_keywords(body.text.strip())
        if keyword_hit is not None:
            kw_slug, kw_conf, _kw_score = keyword_hit
            if kw_slug in classes:
                slug = kw_slug
                confidence = kw_conf
                needs_review = confidence < threshold
                method = "keyword"

    return PredictResponse(
        department_slug=slug,
        department_name=slug_to_name.get(slug),
        confidence=round(confidence, 4),
        needs_review=needs_review,
        top_predictions=top_predictions,
        classification_method=method,
    )


if __name__ == "__main__":
    import uvicorn

    s = get_settings()
    uvicorn.run("main:app", host=s.api_host, port=s.api_port, reload=False)
