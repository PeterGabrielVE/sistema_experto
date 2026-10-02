"""Bearer token shared with Laravel (EXPERT_TOKEN)."""

from __future__ import annotations

import secrets

from fastapi import Depends, Header, HTTPException

from .config import Settings, get_settings


def require_token(
    authorization: str | None = Header(default=None),
    settings: Settings = Depends(get_settings),
) -> None:
    if not settings.token:
        return
    if not authorization or not secrets.compare_digest(authorization, f"Bearer {settings.token}"):
        raise HTTPException(status_code=401, detail="Invalid token")
