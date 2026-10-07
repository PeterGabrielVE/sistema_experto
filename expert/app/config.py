"""Service settings, all from environment variables.

Read on each call (cheap) so a changed variable applies without restarting the process,
and tests can set it with monkeypatch.
"""

from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path

TITLE = "Sistema experto - servicio de diagnóstico"


def _shared(name: str) -> Path:
    here = Path(__file__).resolve()
    # Docker image: /service/shared; repository checkout: <repo>/shared.
    candidates = [here.parents[1] / "shared", here.parents[2] / "shared"]
    return next((c for c in candidates if c.is_dir()), candidates[-1]) / name


@dataclass(frozen=True)
class Settings:
    # Bearer token required by every endpoint but /health. Empty: no authentication.
    token: str
    # Clinical cut-offs shared with Laravel and the inference service.
    thresholds_path: Path
    # Food composition catalog (exchange portions), also imported by Laravel into foods.
    food_catalog_path: Path


def get_settings() -> Settings:
    thresholds = os.environ.get("CLINICAL_THRESHOLDS_PATH")
    catalog = os.environ.get("FOOD_CATALOG_PATH")
    return Settings(
        token=os.environ.get("EXPERT_TOKEN", ""),
        thresholds_path=Path(thresholds) if thresholds else _shared("clinical_thresholds.json"),
        food_catalog_path=Path(catalog) if catalog else _shared("food_catalog.csv"),
    )
