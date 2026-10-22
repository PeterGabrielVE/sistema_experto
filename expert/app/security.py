"""Bearer token shared with Laravel (EXPERT_TOKEN). Without one the service stays closed,
unless it is declared local (US-4.1, D-2): a deployment that forgot it is not left open."""

from __future__ import annotations

import secrets

from fastapi import Depends, Header, HTTPException

from .config import Settings, get_settings


def require_token(
    authorization: str | None = Header(default=None),
    settings: Settings = Depends(get_settings),
) -> None:
    if not settings.token:
        if settings.environment == "local":
            return
        raise HTTPException(status_code=503, detail="Credencial del servicio no configurada")
    if not authorization or not secrets.compare_digest(authorization, f"Bearer {settings.token}"):
        raise HTTPException(status_code=401, detail="Invalid token")
