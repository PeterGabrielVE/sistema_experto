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


class Index(BaseModel):
    label: str
    value: float | str = Field(description="Number, or 'systolic/diastolic' for blood_pressure")
    unit: str
    reference: str | None = Field(description="Reference range as text")
    high: bool | None = Field(description="Outside the reference range; null when there is no cut-off")
    category: str | None = Field(default=None, description="Only bmi and blood_pressure")


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


class Assessments(BaseModel):
    glycemic_status: GlycemicStatus
    insulin_resistance: InsulinResistance
    metabolic_syndrome: MetabolicSyndrome


class Finding(BaseModel):
    rule_id: str = Field(examples=["RI-01"])
    severity: Severity
    title: str
    evidence: list[str]
    recommendation: str | None


class Evaluation(BaseModel):
    indices: dict[str, Index]
    assessments: Assessments
    findings: list[Finding] = Field(description="Sorted by severity: alert, warning, info")
    ruleset_version: str
