"""Liveness, open (no token) for the Docker healthcheck."""

from __future__ import annotations

from fastapi import APIRouter

from .. import rules
from ..responses import Health

router = APIRouter(tags=["health"])


@router.get("/health", response_model=Health)
def health() -> dict:
    return {"status": "ok", "ruleset_version": rules.RULESET_VERSION, "rules": len(rules.RULES)}
