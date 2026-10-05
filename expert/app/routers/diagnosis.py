"""Diagnosis endpoints. Stateless: Laravel sends the facts, the service answers."""

from __future__ import annotations

from fastapi import APIRouter, Depends

from .. import indices, nutrition, rules
from ..responses import Evaluation, Indices, RuleCatalog
from ..schemas import Facts
from ..security import require_token

router = APIRouter(tags=["diagnosis"], dependencies=[Depends(require_token)])


@router.get("/rules", response_model=RuleCatalog)
def rule_catalog() -> dict:
    """Rules in evaluation order, with their criterion and source; macro_rules shape the macronutrient distribution."""
    return {
        "ruleset_version": rules.RULESET_VERSION,
        "rules": [r.catalog() for r in rules.RULES],
        "macro_rules": [r.catalog() for r in nutrition.MACRO_RULES],
    }


@router.post("/indices", response_model=Indices, response_model_exclude_unset=True)
def compute_indices(facts: Facts) -> dict:
    """Clinical indices only (no assessments or findings)."""
    return {"indices": indices.compute(facts.model_dump()), "ruleset_version": rules.RULESET_VERSION}


@router.post("/evaluate", response_model=Evaluation, response_model_exclude_unset=True)
def evaluate(facts: Facts) -> dict:
    """Indices, assessments, findings and the suggested macronutrient distribution."""
    return rules.evaluate(facts.model_dump())
