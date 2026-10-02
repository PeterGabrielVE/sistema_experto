"""HTTP API of the expert diagnosis service. Stateless: Laravel sends the facts, the service answers."""

from __future__ import annotations

import os
import secrets

from fastapi import Depends, FastAPI, Header, HTTPException

from . import indices, rules
from .schemas import Facts

app = FastAPI(title="Sistema experto - servicio de diagnóstico", version=rules.RULESET_VERSION)


def require_token(authorization: str | None = Header(default=None)) -> None:
    expected = os.environ.get("EXPERT_TOKEN", "")
    if not expected:
        return
    if not authorization or not secrets.compare_digest(authorization, f"Bearer {expected}"):
        raise HTTPException(status_code=401, detail="Invalid token")


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "ruleset_version": rules.RULESET_VERSION, "rules": len(rules.RULES)}


@app.get("/rules", dependencies=[Depends(require_token)])
def rule_catalog() -> dict:
    return {"ruleset_version": rules.RULESET_VERSION, "rules": [r.catalog() for r in rules.RULES]}


@app.post("/indices", dependencies=[Depends(require_token)])
def compute_indices(facts: Facts) -> dict:
    return {"indices": indices.compute(facts.model_dump()), "ruleset_version": rules.RULESET_VERSION}


@app.post("/evaluate", dependencies=[Depends(require_token)])
def evaluate(facts: Facts) -> dict:
    return rules.evaluate(facts.model_dump())
