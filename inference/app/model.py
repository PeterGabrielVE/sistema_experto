"""Nutritional-category classifier used by the Laravel app.

Categories match the ids created by RulesSeeder:
    1 = Bajo peso, 2 = Normal, 3 = Sobrepeso, 4 = Obesidad
"""

from __future__ import annotations

import os
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path

import joblib
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import FunctionTransformer

CATEGORIES = {1: "Bajo peso", 2: "Normal", 3: "Sobrepeso", 4: "Obesidad"}

# Feature order: weight (kg), size (cm), age (years), gender (1 = H, 0 = M), physical_activity (0-4)
FEATURES = ["weight", "size", "age", "gender", "physical_activity"]

MODEL_PATH = Path(os.environ.get("MODEL_PATH", "/models/model.joblib"))

# Doctor-confirmed labels carry more information than synthetic ones.
MANUAL_SAMPLE_WEIGHT = 5.0


def rule_for_imc(imc: float) -> int:
    """Same thresholds as the original PHP expert rules."""
    if imc < 18.5:
        return 1
    if imc < 25:
        return 2
    if imc < 30:
        return 3
    return 4


def _add_imc(x: np.ndarray) -> np.ndarray:
    size_m = x[:, 1] / 100.0
    imc = x[:, 0] / np.square(size_m)
    return np.column_stack([x, imc])


def encode(sample: dict) -> list[float]:
    gender = sample["gender"]
    if isinstance(gender, str):
        gender = 1.0 if gender.upper() == "H" else 0.0
    return [
        float(sample["weight"]),
        float(sample["size"]),
        float(sample["age"]),
        float(gender),
        float(sample["physical_activity"]),
    ]


def synthetic_dataset(n: int = 20000, seed: int = 42) -> tuple[np.ndarray, np.ndarray]:
    """Patients sampled over realistic ranges, labelled by the expert rules."""
    rng = np.random.default_rng(seed)
    gender = rng.integers(0, 2, n)
    size = np.where(gender == 1, rng.normal(172, 8, n), rng.normal(160, 7, n)).clip(135, 210)
    imc = rng.uniform(14, 45, n)
    weight = imc * np.square(size / 100.0)
    age = rng.integers(18, 90, n)
    activity = rng.integers(0, 5, n)

    x = np.column_stack([weight, size, age, gender, activity]).astype(float)
    y = np.array([rule_for_imc(v) for v in imc])
    return x, y


def build_pipeline() -> Pipeline:
    return Pipeline(
        [
            ("imc", FunctionTransformer(_add_imc)),
            ("clf", RandomForestClassifier(n_estimators=200, min_samples_leaf=2, random_state=42, n_jobs=-1)),
        ]
    )


@dataclass
class TrainedModel:
    pipeline: Pipeline
    version: str
    trained_at: str
    samples_synthetic: int
    samples_manual: int
    accuracy: float

    def predict(self, sample: dict) -> dict:
        x = np.array([encode(sample)])
        proba = self.pipeline.predict_proba(x)[0]
        classes = [int(c) for c in self.pipeline.classes_]
        best = int(np.argmax(proba))
        return {
            "rule_id": classes[best],
            "category": CATEGORIES[classes[best]],
            "confidence": round(float(proba[best]), 4),
            "probabilities": {CATEGORIES[c]: round(float(p), 4) for c, p in zip(classes, proba)},
            "model_version": self.version,
        }

    def metadata(self) -> dict:
        return {
            "version": self.version,
            "trained_at": self.trained_at,
            "samples_synthetic": self.samples_synthetic,
            "samples_manual": self.samples_manual,
            "accuracy": self.accuracy,
        }


def train(manual: list[dict] | None = None) -> TrainedModel:
    """Train on synthetic data plus doctor-confirmed diagnoses (dicts with FEATURES + rule_id)."""
    manual = manual or []
    x_syn, y_syn = synthetic_dataset()
    weights = np.ones(len(y_syn))

    if manual:
        x_man = np.array([encode(m) for m in manual])
        y_man = np.array([int(m["rule_id"]) for m in manual])
        x = np.vstack([x_syn, x_man])
        y = np.concatenate([y_syn, y_man])
        weights = np.concatenate([weights, np.full(len(y_man), MANUAL_SAMPLE_WEIGHT)])
    else:
        x, y = x_syn, y_syn

    x_train, x_test, y_train, y_test, w_train, _ = train_test_split(
        x, y, weights, test_size=0.2, random_state=42, stratify=y
    )
    pipeline = build_pipeline()
    pipeline.fit(x_train, y_train, clf__sample_weight=w_train)
    accuracy = float(accuracy_score(y_test, pipeline.predict(x_test)))

    # Refit on everything once evaluated.
    pipeline.fit(x, y, clf__sample_weight=weights)

    now = datetime.now(timezone.utc)
    return TrainedModel(
        pipeline=pipeline,
        version=now.strftime("%Y%m%d%H%M%S"),
        trained_at=now.isoformat(),
        samples_synthetic=len(y_syn),
        samples_manual=len(manual),
        accuracy=round(accuracy, 4),
    )


def save(model: TrainedModel, path: Path = MODEL_PATH) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    tmp = path.with_suffix(".tmp")
    joblib.dump(model, tmp)
    tmp.replace(path)


def load(path: Path = MODEL_PATH) -> TrainedModel | None:
    if not path.exists():
        return None
    return joblib.load(path)
