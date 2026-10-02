"""Clinical indices computed from anthropometry, vital signs and lab results.

Every function returns None when a needed value is missing. Rounding is half up,
like PHP's round(), so values match the ones the Laravel app shows.
"""

from __future__ import annotations

import math
from decimal import ROUND_HALF_UP, Decimal

from . import thresholds as t

BMI_CATEGORIES = {1: "Bajo peso", 2: "Normal", 3: "Sobrepeso", 4: "Obesidad"}


def fmt(value: float) -> str:
    """Spanish decimal comma, no trailing zeros: 5.60 -> '5,6'."""
    return f"{value:.2f}".rstrip("0").rstrip(".").replace(".", ",")


def _round(value: float, digits: int) -> float:
    return float(Decimal(repr(value)).quantize(Decimal(1).scaleb(-digits), rounding=ROUND_HALF_UP))


def _present(*values: float | None) -> bool:
    return all(v is not None and v > 0 for v in values)


def bmi(weight_kg: float | None, height_cm: float | None) -> float | None:
    if not _present(weight_kg, height_cm):
        return None
    return _round(weight_kg / (height_cm / 100) ** 2, 1)


def bmi_category(value: float | None) -> int | None:
    """Same thresholds as InferenceEngine::ruleForImc() (rules.id)."""
    if value is None:
        return None
    if value < t.BMI_NORMAL_FROM:
        return 1
    if value < t.BMI_OVERWEIGHT_FROM:
        return 2
    if value < t.BMI_OBESITY_FROM:
        return 3
    return 4


def waist_to_height(waist_cm: float | None, height_cm: float | None) -> float | None:
    return _round(waist_cm / height_cm, 2) if _present(waist_cm, height_cm) else None


def waist_to_hip(waist_cm: float | None, hip_cm: float | None) -> float | None:
    return _round(waist_cm / hip_cm, 2) if _present(waist_cm, hip_cm) else None


def homa_ir(glucose: float | None, insulin: float | None) -> float | None:
    """Fasting glucose (mg/dL) × fasting insulin (µU/mL) / 405."""
    return _round(glucose * insulin / 405, 2) if _present(glucose, insulin) else None


def tyg(triglycerides: float | None, glucose: float | None) -> float | None:
    """ln(triglycerides × fasting glucose / 2), both in mg/dL."""
    return _round(math.log(triglycerides * glucose / 2), 2) if _present(triglycerides, glucose) else None


def tg_hdl(triglycerides: float | None, hdl: float | None) -> float | None:
    return _round(triglycerides / hdl, 2) if _present(triglycerides, hdl) else None


def non_hdl(total_cholesterol: float | None, hdl: float | None) -> float | None:
    return _round(total_cholesterol - hdl, 0) if _present(total_cholesterol, hdl) else None


def ldl_friedewald(total_cholesterol: float | None, hdl: float | None, triglycerides: float | None) -> float | None:
    """total − HDL − TG/5; not valid with triglycerides ≥ 400 mg/dL."""
    if not _present(total_cholesterol, hdl, triglycerides) or triglycerides >= t.FRIEDEWALD_MAX_TRIGLYCERIDES:
        return None
    value = _round(total_cholesterol - hdl - triglycerides / 5, 0)
    return value if value > 0 else None


def blood_pressure_category(systolic: int | None, diastolic: int | None) -> str | None:
    """ACC/AHA 2017, same as ClinicalMeasurement::bloodPressureCategory()."""
    if not _present(systolic, diastolic):
        return None
    if systolic >= t.BP_STAGE2["systolic"] or diastolic >= t.BP_STAGE2["diastolic"]:
        return "Hipertensión etapa 2"
    if systolic >= t.BP_STAGE1["systolic"] or diastolic >= t.BP_STAGE1["diastolic"]:
        return "Hipertensión etapa 1"
    if systolic >= t.BP_ELEVATED_SYSTOLIC:
        return "Elevada"
    return "Normal"


def compute(facts: dict) -> dict[str, dict]:
    """All the indices that can be computed from the facts, keyed by code.

    Each entry: label, value, unit, reference (text), high (bool or None when there is no cut-off).
    """
    a, v, lab = facts["anthropometry"], facts["vitals"], facts["labs"]
    sex = facts["sex"]
    out: dict[str, dict] = {}

    def add(key, label, value, unit="", reference=None, high=None):
        if value is not None:
            out[key] = {"label": label, "value": value, "unit": unit, "reference": reference, "high": high}

    value = bmi(a["weight_kg"], a["height_cm"])
    if value is not None:
        add("bmi", "IMC", value, "kg/m²", f"{fmt(t.BMI_NORMAL_FROM)} a < {fmt(t.BMI_OVERWEIGHT_FROM)}", bmi_category(value) != 2)
        out["bmi"]["category"] = BMI_CATEGORIES[bmi_category(value)]

    value = waist_to_height(a["waist_cm"], a["height_cm"])
    add("waist_to_height", "Cintura/talla", value, "", f"≤ {fmt(t.WAIST_TO_HEIGHT)}", value is not None and value > t.WAIST_TO_HEIGHT)

    value = waist_to_hip(a["waist_cm"], a["hip_cm"])
    limit = t.WAIST_TO_HIP[sex]
    add("waist_to_hip", "Cintura/cadera", value, "", f"≤ {fmt(limit)}", value is not None and value > limit)

    value = homa_ir(lab["fasting_glucose"], lab["fasting_insulin"])
    add("homa_ir", "HOMA-IR", value, "", f"≤ {fmt(t.HOMA_IR)}", value is not None and value > t.HOMA_IR)

    value = tyg(lab["triglycerides"], lab["fasting_glucose"])
    add("tyg", "Índice TyG", value, "", f"≤ {fmt(t.TYG)}", value is not None and value > t.TYG)

    value = tg_hdl(lab["triglycerides"], lab["hdl"])
    add("tg_hdl", "TG/HDL", value, "", f"≤ {fmt(t.TG_HDL)}", value is not None and value > t.TG_HDL)

    value = non_hdl(lab["total_cholesterol"], lab["hdl"])
    add("non_hdl", "Colesterol no HDL", value, "mg/dL", f"< {t.NON_HDL_HIGH}", value is not None and value >= t.NON_HDL_HIGH)

    # Only when the lab did not report LDL.
    if lab["ldl"] is None:
        value = ldl_friedewald(lab["total_cholesterol"], lab["hdl"], lab["triglycerides"])
        add("ldl_friedewald", "LDL calculado (Friedewald)", value, "mg/dL", f"< {t.LDL_HIGH}", value is not None and value >= t.LDL_HIGH)

    category = blood_pressure_category(v["systolic_bp"], v["diastolic_bp"])
    if category is not None:
        add("blood_pressure", "Presión arterial", f"{v['systolic_bp']}/{v['diastolic_bp']}", "mmHg", f"< {t.BP_ELEVATED_SYSTOLIC}/{t.BP_STAGE1['diastolic']}", category != "Normal")
        out["blood_pressure"]["category"] = category

    return out
