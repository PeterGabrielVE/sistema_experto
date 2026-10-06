"""Request bodies. Bounds mirror the Laravel validation (ClinicalMeasurementRequest, LabResultRequest)."""

from __future__ import annotations

from typing import Literal

from pydantic import BaseModel, Field, model_validator

from . import thresholds as t


class Anthropometry(BaseModel):
    weight_kg: float | None = Field(default=None, ge=2, le=400)
    height_cm: float | None = Field(default=None, ge=40, le=250)
    waist_cm: float | None = Field(default=None, ge=30, le=250)
    hip_cm: float | None = Field(default=None, ge=40, le=250)


class Vitals(BaseModel):
    systolic_bp: int | None = Field(default=None, ge=60, le=260)
    diastolic_bp: int | None = Field(default=None, ge=30, le=160)

    @model_validator(mode="after")
    def both_or_none(self) -> Vitals:
        if (self.systolic_bp is None) != (self.diastolic_bp is None):
            raise ValueError("systolic_bp and diastolic_bp go together")
        if self.systolic_bp is not None and self.diastolic_bp >= self.systolic_bp:
            raise ValueError("diastolic_bp must be lower than systolic_bp")
        return self


class Labs(BaseModel):
    fasting_glucose: float | None = Field(default=None, ge=20, le=600, description="mg/dL")
    fasting_insulin: float | None = Field(default=None, ge=0.5, le=300, description="µU/mL")
    hba1c: float | None = Field(default=None, ge=3, le=20, description="%")
    total_cholesterol: float | None = Field(default=None, ge=50, le=600, description="mg/dL")
    hdl: float | None = Field(default=None, ge=5, le=200, description="mg/dL")
    ldl: float | None = Field(default=None, ge=10, le=500, description="mg/dL")
    triglycerides: float | None = Field(default=None, ge=20, le=5000, description="mg/dL")
    ggt: float | None = Field(default=None, ge=1, le=3000, description="U/L, gamma-glutamil transferasa")


class Conditions(BaseModel):
    """Diagnoses marked in the clinical record (ficha clínica)."""

    diabetes: bool = False
    prediabetes: bool = False
    hypertension: bool = False
    dyslipidemia: bool = False
    pcos: bool = False


class RiskFactors(BaseModel):
    """FINDRISC questionnaire answers (ficha clínica). None: not asked."""

    daily_physical_activity: bool | None = Field(default=None, description="At least 30 minutes every day, at work or leisure")
    daily_fruit_vegetables: bool | None = Field(default=None, description="Eats vegetables or fruit every day")
    antihypertensive_medication: bool | None = None
    high_glucose_history: bool | None = Field(default=None, description="High glucose ever found (check-up, illness, pregnancy)")
    family_history_diabetes: Literal["none", "second_degree", "first_degree"] | None = Field(
        default=None, description="second_degree: grandparents, aunts/uncles, cousins; first_degree: parents, siblings, children"
    )


class Facts(BaseModel):
    sex: Literal["H", "M"]
    age: int | None = Field(default=None, ge=0, le=130)
    physical_activity: int | None = Field(
        default=None, ge=0, le=4, description="0 muy ligera, 1 ligera, 2 moderada, 3 activa, 4 muy activa; for the energy target"
    )
    anthropometry: Anthropometry = Anthropometry()
    vitals: Vitals = Vitals()
    labs: Labs = Labs()
    conditions: Conditions = Conditions()
    risk_factors: RiskFactors = RiskFactors()


def _action(name: str):
    low, high = t.CONFIGURABLE["actions"][name]["range"]
    return Field(default=None, ge=low, le=high, description=t.CONFIGURABLE["actions"][name]["label"])


class MacroActions(BaseModel):
    """Same meaning as the adjustments of the built-in MAC rules (nutrition.py); ranges in shared/clinical_thresholds.json."""

    carbohydrates: int | None = _action("carbohydrates")
    fats: int | None = _action("fats")
    protein_g_per_kg: float | None = _action("protein_g_per_kg")
    energy: int | None = _action("energy")
    glycemic_load: int | None = _action("glycemic_load")
    saturated_fat_pct: int | None = _action("saturated_fat_pct")
    added_sugar_pct: int | None = _action("added_sugar_pct")
    sodium_mg: int | None = _action("sodium_mg")

    @model_validator(mode="after")
    def at_least_one(self) -> MacroActions:
        if all(v is None for v in self.model_dump().values()):
            raise ValueError("a rule needs at least one action")
        return self


Variable = Literal[tuple(t.CONFIGURABLE["variables"])]
Operator = Literal[tuple(t.CONFIGURABLE["operators"])]


class ConfiguredMacroRule(BaseModel):
    """A macronutrient rule configured in the app (Laravel macro_rules): if variable operator value, apply the actions."""

    id: str = Field(max_length=20, examples=["CFG-1"])
    title: str = Field(max_length=120)
    advice: str | None = Field(default=None, max_length=500)
    variable: Variable
    operator: Operator
    value: float
    actions: MacroActions


class EvaluationRequest(Facts):
    """Body of /evaluate: the facts plus the macronutrient rules configured in the app."""

    macro_rules: list[ConfiguredMacroRule] = Field(default=[], max_length=100)


class MealPlanTargets(BaseModel):
    """Daily targets of the consultation (the doctor's values in the form)."""

    energy: float = Field(ge=800, le=6000, description="kcal/day")
    carbohydrates: float = Field(ge=0, le=1000, description="g/day")
    proteins: float = Field(ge=0, le=400, description="g/day")
    fats: float = Field(ge=0, le=400, description="g/day")


class Food(BaseModel):
    """An exchange portion of the food catalog (Laravel foods table)."""

    id: int
    name: str = Field(max_length=120)
    item: str = Field(max_length=60, description="Food group as in foods.item: Pan, Cereales, Carnes, Verduras…")
    grams: float | None = Field(default=None, ge=0, le=1000, description="Grams per portion; null or 0 when measured otherwise")
    kcal: float = Field(ge=0, le=2000)
    protein: float = Field(ge=0, le=200)
    fat: float = Field(ge=0, le=200)
    saturated_fat: float | None = Field(default=None, ge=0, le=200, description="g per portion")
    cho: float = Field(ge=0, le=500)
    glycemic_index: float | None = Field(default=None, ge=0, le=150, description="Glucose = 100; null without carbohydrates to speak of")


class MealPlanLimits(BaseModel):
    """Daily ceilings, usually the limits of the patient's macronutrient plan; missing: the general ones."""

    glycemic_load: float | None = Field(default=None, ge=20, le=300)
    saturated_fat: float | None = Field(default=None, ge=5, le=200, description="g/day")


class MealPlanRequest(BaseModel):
    targets: MealPlanTargets
    foods: list[Food] = Field(min_length=1, max_length=500)
    limits: MealPlanLimits = MealPlanLimits()
    seed: int = Field(default=0, ge=0, le=1_000_000, description="Another seed, another menu with the same targets")
