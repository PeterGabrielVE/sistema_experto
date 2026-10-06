"""Diagnosis endpoints. Stateless: Laravel sends the facts, the service answers."""

from __future__ import annotations

from fastapi import APIRouter, Depends

from .. import indices, meal_plan, nutrition, rules
from ..responses import Evaluation, Indices, MealPlan, RuleCatalog
from ..schemas import EvaluationRequest, Facts, MealPlanRequest
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
def evaluate(facts: EvaluationRequest) -> dict:
    """Indices, assessments, findings and the suggested macronutrient distribution (with the macro_rules configured in the app)."""
    return rules.evaluate(facts.model_dump())


@router.post("/meal-plan", response_model=MealPlan, response_model_exclude_unset=True)
def generate_meal_plan(request: MealPlanRequest) -> dict:
    """Daily menu by integer linear programming: exchange portions of the given foods per meal that
    best meet the energy and macronutrient targets within the dietary guideline constraints and the
    glycemic load and saturated fat ceilings."""
    return meal_plan.generate(
        request.targets.model_dump(), [f.model_dump() for f in request.foods], request.seed, request.limits.model_dump()
    )
