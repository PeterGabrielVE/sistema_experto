"""Service settings, all from environment variables.

Read on each call (cheap) so a changed variable applies without restarting the process,
and tests can set it with monkeypatch.
"""

from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path

TITLE = "Sistema experto - servicio de diagnóstico"


def _default_thresholds_path() -> Path:
    here = Path(__file__).resolve()
    # Docker image: /service/shared; repository checkout: <repo>/shared.
    candidates = [here.parents[1] / "shared", here.parents[2] / "shared"]
    return next((c for c in candidates if c.is_dir()), candidates[-1]) / "clinical_thresholds.json"


@dataclass(frozen=True)
class Settings:
    # Bearer token required by every endpoint but /health. Empty: no authentication.
    token: str
    # Clinical cut-offs shared with Laravel and the inference service.
    thresholds_path: Path


def get_settings() -> Settings:
    path = os.environ.get("CLINICAL_THRESHOLDS_PATH")
    return Settings(
        token=os.environ.get("EXPERT_TOKEN", ""),
        thresholds_path=Path(path) if path else _default_thresholds_path(),
    )
