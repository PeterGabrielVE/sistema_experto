"""HTTP API for the inference engine. Laravel calls /predict and /train."""

from __future__ import annotations

import os
import secrets
import threading
from typing import Literal

from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

from . import data, model

app = FastAPI(title="Sistema experto - motor de inferencia")

_lock = threading.Lock()
_model = model.load()


def require_token(authorization: str | None = Header(default=None)) -> None:
    expected = os.environ.get("INFERENCE_TOKEN", "")
    if not expected:
        return
    if not authorization or not secrets.compare_digest(authorization, f"Bearer {expected}"):
        raise HTTPException(status_code=401, detail="Invalid token")


class Patient(BaseModel):
    weight: float = Field(gt=0, description="kg")
    size: float = Field(gt=0, description="cm")
    age: int = Field(ge=0, le=130)
    gender: Literal["H", "M"]
    physical_activity: int = Field(ge=0, le=4)


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "model": _model.metadata() if _model else None}


@app.post("/predict", dependencies=[Depends(require_token)])
def predict(patient: Patient) -> dict:
    if _model is None:
        raise HTTPException(status_code=503, detail="Model not trained")
    return _model.predict(patient.model_dump())


@app.post("/train", dependencies=[Depends(require_token)])
def train() -> dict:
    global _model
    try:
        manual = data.manual_diagnoses()
    except Exception as exc:  # database unreachable: keep serving the current model
        raise HTTPException(status_code=502, detail=f"Could not read diagnoses: {exc}") from exc

    trained = model.train(manual)
    with _lock:
        model.save(trained)
        _model = trained
    return trained.metadata()
