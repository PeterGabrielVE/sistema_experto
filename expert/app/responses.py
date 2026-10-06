"""Response bodies. They document the API (OpenAPI) and check what the engine returns.

The engine (indices.py, rules.py) builds plain dicts; the routes declare these models
as response_model with response_model_exclude_unset, so optional keys the engine
leaves out (Index.category) stay out of the JSON.
"""

from __future__ import annotations

from typing import Literal

from pydantic import BaseModel, Field

from .rules import Severity


class Health(BaseModel):
    status: Literal["ok"]
    ruleset_version: str
    rules: int = Field(description="Number of rules loaded")


class RuleInfo(BaseModel):
    id: str = Field(examples=["RI-01"])
    category: str
    title: str
    description: str = Field(description="Criterion, with the cut-offs in use")
    source: str = Field(description="Clinical guideline or paper")


class RuleCatalog(BaseModel):
    ruleset_version: str
    rules: list[RuleInfo]
    macro_rules: list[RuleInfo] = Field(description="Rules of the macronutrient distribution (category Nutrición)")


class Index(BaseModel):
    label: str
    value: int | float | str = Field(description="Number, or 'systolic/diastolic' for blood_pressure")
    unit: str
    reference: str | None = Field(description="Reference range as text")
    high: bool | None = Field(description="Outside the reference range; null when there is no cut-off")
    category: str | None = Field(default=None, description="Only bmi, aip, blood_pressure, fli and findrisc")


class Indices(BaseModel):
    indices: dict[str, Index] = Field(description="Only the indices the facts allow; keyed by code (bmi, homa_ir, tyg…)")
    ruleset_version: str


class GlycemicStatus(BaseModel):
    status: Literal["diabetes_conocida", "rango_diabetes", "prediabetes", "normal", "indeterminado"]
    evidence: list[str]


class InsulinResistance(BaseModel):
    status: Literal["probable", "posible", "no_sugerida", "indeterminado"]
    positive: int = Field(description="Indices above the cut-off")
    evaluated: int = Field(description="Indices that could be computed (of HOMA-IR, TyG, TG/HDL)")
    evidence: list[str]


class Criterion(BaseModel):
    name: str
    met: bool | None = Field(description="null: missing data")
    evidence: str | None


class MetabolicSyndrome(BaseModel):
    status: Literal["presente", "ausente", "indeterminado"]
    met: int
    unknown: int
    criteria: list[Criterion]


class AtherogenicProfile(BaseModel):
    status: Literal["alterado", "limitrofe", "normal", "indeterminado"]
    positive: int = Field(description="Indices above the cut-off")
    evaluated: int = Field(description="Indices that could be computed (of Castelli I and II, AIP, non-HDL)")
    evidence: list[str]


class Assessments(BaseModel):
    glycemic_status: GlycemicStatus
    insulin_resistance: InsulinResistance
    metabolic_syndrome: MetabolicSyndrome
    atherogenic_profile: AtherogenicProfile


class Finding(BaseModel):
    rule_id: str = Field(examples=["RI-01"])
    severity: Severity
    title: str
    evidence: list[str]
    recommendation: str | None


class Energy(BaseModel):
    bmr: int = Field(description="Basal metabolic rate, Mifflin-St Jeor (kcal/day)")
    activity_level: str
    activity_factor: float
    maintenance: int = Field(description="kcal/day")
    adjustment: int = Field(description="Deficit (negative) or surplus, kcal/day")
    target: int = Field(description="kcal/day, rounded to 10")


class Macro(BaseModel):
    label: str
    percent: int = Field(description="Of the energy target")
    grams: int
    kcal: int
    g_per_kg: float | None = Field(default=None, description="Proteins only, per kg of reference weight")


class Macros(BaseModel):
    carbohydrates: Macro
    proteins: Macro
    fats: Macro


class NutrientLimit(BaseModel):
    label: str
    comparator: Literal["max", "min"]
    amount: int
    unit: Literal["g", "mg", ""]
    percent: int | None = Field(default=None, description="Of the energy target; saturated fat and added sugar")


class MacroRuleApplied(BaseModel):
    rule_id: str = Field(examples=["MAC-03"])
    title: str
    evidence: list[str]
    advice: str
    configured: bool = Field(description="Configured in the app (macro_rules of the request), not built in")


class MacroPlan(BaseModel):
    status: Literal["calculado", "no_aplica", "indeterminado"]
    reason: str | None = Field(default=None, description="Why there is no plan (no_aplica, indeterminado)")
    energy: Energy | None = None
    reference_weight_kg: float | None = Field(default=None, description="Actual weight, or weight at BMI 25 with excess weight")
    protein_min_g_per_kg: float | None = None
    macros: Macros | None = None
    limits: dict[str, NutrientLimit] | None = Field(default=None, description="saturated_fat, added_sugar, fiber, sodium, glycemic_load")
    rules: list[MacroRuleApplied] = Field(description="Rules that shaped the distribution: built-in MAC rules in catalog order, then the configured ones")
    notes: list[str]


class Evaluation(BaseModel):
    indices: dict[str, Index]
    assessments: Assessments
    findings: list[Finding] = Field(description="Sorted by severity: alert, warning, info")
    macronutrients: MacroPlan = Field(description="Suggested energy target and macronutrient distribution (adults)")
    ruleset_version: str


class Nutrients(BaseModel):
    energy: float = Field(description="kcal")
    carbohydrates: float = Field(description="g")
    proteins: float = Field(description="g")
    fats: float = Field(description="g")


class Quality(BaseModel):
    glycemic_load: float
    saturated_fat: float = Field(description="g")


class NutrientsAndQuality(Nutrients, Quality):
    pass


class MealItem(NutrientsAndQuality):
    food_id: int
    name: str
    group: str
    portions: float = Field(description="Exchange portions, in halves")
    grams: int | None = Field(description="null when the food has no grams per portion (oils: spoons)")


class Meal(BaseModel):
    key: str
    label: str
    energy_target: int = Field(description="kcal of the meal's share")
    items: list[MealItem]
    totals: NutrientsAndQuality


class MealDay(BaseModel):
    day: int
    meals: list[Meal]
    totals: NutrientsAndQuality
    deviation_percent: Nutrients = Field(description="(total - target) / target × 100")
    notes: list[str]


class MealPlan(BaseModel):
    status: Literal["optimo", "factible", "sin_solucion", "sin_alimentos"]
    seed: int | None = None
    targets: Nutrients | None = None
    limits: Quality | None = Field(default=None, description="Daily ceilings applied")
    days: list[MealDay] = Field(description="One menu per day; the same targets every day")
    notes: list[str] = Field(description="About the whole plan; each day has its own")
