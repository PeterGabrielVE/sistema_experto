"""Request bodies. Bounds mirror the Laravel validation (ClinicalMeasurementRequest, LabResultRequest)."""

from __future__ import annotations

from typing import Literal

from pydantic import BaseModel, Field, model_validator


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
