"""Clinical indices computed from anthropometry, vital signs and lab results.

Every function returns None when a needed value is missing. Rounding is half up,
like PHP's round(), so values match the ones the Laravel app shows.
"""

from __future__ import annotations

import math
from decimal import ROUND_HALF_UP, Decimal

from . import thresholds as t

BMI_CATEGORIES = {1: "Bajo peso", 2: "Normal", 3: "Sobrepeso", 4: "Obesidad"}

# mg/dL per mmol/L, for the atherogenic index of plasma.
MG_PER_MMOL_TRIGLYCERIDES = 88.57
MG_PER_MMOL_CHOLESTEROL = 38.67

# FINDRISC questions, in the order of the questionnaire.
FINDRISC_ITEMS = {
    "age": "Edad",
    "bmi": "IMC",
    "waist": "Cintura",
    "physical_activity": "Actividad física diaria ≥ 30 min",
    "fruit_vegetables": "Verduras o frutas a diario",
    "antihypertensive_medication": "Fármacos antihipertensivos",
    "high_glucose_history": "Glicemia alta alguna vez",
    "family_history_diabetes": "Familiares con diabetes",
}


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


def castelli_i(total_cholesterol: float | None, hdl: float | None) -> float | None:
    """Total cholesterol / HDL."""
    return _round(total_cholesterol / hdl, 2) if _present(total_cholesterol, hdl) else None


def castelli_ii(ldl: float | None, hdl: float | None) -> float | None:
    """LDL / HDL."""
    return _round(ldl / hdl, 2) if _present(ldl, hdl) else None


def aip(triglycerides: float | None, hdl: float | None) -> float | None:
    """Atherogenic index of plasma: log10(triglycerides / HDL), both in mmol/L."""
    if not _present(triglycerides, hdl):
        return None
    return _round(math.log10((triglycerides / MG_PER_MMOL_TRIGLYCERIDES) / (hdl / MG_PER_MMOL_CHOLESTEROL)), 2)


def aip_risk(value: float) -> str:
    if value < t.AIP_INTERMEDIATE:
        return "Riesgo bajo"
    if value <= t.AIP_HIGH:
        return "Riesgo intermedio"
    return "Riesgo alto"


def fli(triglycerides: float | None, weight_kg: float | None, height_cm: float | None,
        ggt: float | None, waist_cm: float | None) -> float | None:
    """Fatty liver index (Bedogni 2006), 0 to 100. Triglycerides mg/dL, GGT U/L, waist cm; BMI unrounded."""
    if not _present(triglycerides, weight_kg, height_cm, ggt, waist_cm):
        return None
    y = (0.953 * math.log(triglycerides) + 0.139 * weight_kg / (height_cm / 100) ** 2
         + 0.718 * math.log(ggt) + 0.053 * waist_cm - 15.745)
    return _round(100 / (1 + math.exp(-y)), 1)


def fli_category(value: float) -> str:
    if value < t.FLI_RULE_OUT:
        return "Esteatosis descartada"
    if value < t.FLI_RULE_IN:
        return "Zona indeterminada"
    return "Esteatosis probable"


def findrisc_points(facts: dict) -> dict[str, int | None]:
    """Points of each FINDRISC question (keys of FINDRISC_ITEMS); None when the answer is missing.

    Prediabetes in the clinical record answers "high glucose ever found" with yes.
    """
    a, r, sex = facts["anthropometry"], facts["risk_factors"], facts["sex"]
    age, value, waist = facts["age"], bmi(a["weight_kg"], a["height_cm"]), a["waist_cm"]
    waist_low, waist_high = t.FINDRISC_WAIST[sex]
    high_glucose = True if facts["conditions"]["prediabetes"] else r["high_glucose_history"]

    def yes_no(answer: bool | None, yes: int, no: int) -> int | None:
        return None if answer is None else (yes if answer else no)

    return {
        "age": None if age is None else (0, 2, 3, 4)[sum(age >= limit for limit in t.FINDRISC_AGE)],
        "bmi": None if value is None else (0 if value < t.FINDRISC_BMI["overweight"] else 1 if value <= t.FINDRISC_BMI["obesity"] else 3),
        "waist": None if waist is None else (0 if waist < waist_low else 3 if waist <= waist_high else 4),
        "physical_activity": yes_no(r["daily_physical_activity"], 0, 2),
        "fruit_vegetables": yes_no(r["daily_fruit_vegetables"], 0, 1),
        "antihypertensive_medication": yes_no(r["antihypertensive_medication"], 2, 0),
        "high_glucose_history": yes_no(high_glucose, 5, 0),
        "family_history_diabetes": None if r["family_history_diabetes"] is None
        else {"none": 0, "second_degree": 3, "first_degree": 5}[r["family_history_diabetes"]],
    }


def findrisc_applies(facts: dict) -> bool:
    """Screening of undiagnosed type 2 diabetes in adults."""
    return not facts["conditions"]["diabetes"] and (facts["age"] is None or facts["age"] >= t.ADULT_AGE)


def findrisc(facts: dict) -> int | None:
    """FINDRISC score (Lindström 2003), 0 to 26; None when it does not apply or an answer is missing."""
    points = findrisc_points(facts)
    if not findrisc_applies(facts) or None in points.values():
        return None
    return sum(points.values())


def findrisc_risk(score: int) -> dict:
    """Risk band of the score: label and ten-year risk of type 2 diabetes."""
    return [band for band in t.FINDRISC_RISK if score >= band["from"]][-1]


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

    Each entry: label, value, unit, reference (text), high (bool or None when there is no cut-off);
    bmi, aip, blood_pressure, fli and findrisc also have a category.
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

    sex_limit = t.CASTELLI_I[sex]
    value = castelli_i(lab["total_cholesterol"], lab["hdl"])
    add("castelli_i", "Índice de Castelli I (CT/HDL)", value, "", f"≤ {fmt(sex_limit)}", value is not None and value > sex_limit)

    # Reported LDL, otherwise Friedewald.
    ldl = lab["ldl"] if lab["ldl"] is not None else out.get("ldl_friedewald", {}).get("value")
    sex_limit = t.CASTELLI_II[sex]
    value = castelli_ii(ldl, lab["hdl"])
    add("castelli_ii", "Índice de Castelli II (LDL/HDL)", value, "", f"≤ {fmt(sex_limit)}", value is not None and value > sex_limit)

    value = aip(lab["triglycerides"], lab["hdl"])
    if value is not None:
        add("aip", "Índice aterogénico del plasma (AIP)", value, "", f"< {fmt(t.AIP_INTERMEDIATE)} bajo; > {fmt(t.AIP_HIGH)} alto", value > t.AIP_HIGH)
        out["aip"]["category"] = aip_risk(value)

    category = blood_pressure_category(v["systolic_bp"], v["diastolic_bp"])
    if category is not None:
        add("blood_pressure", "Presión arterial", f"{v['systolic_bp']}/{v['diastolic_bp']}", "mmHg", f"< {t.BP_ELEVATED_SYSTOLIC}/{t.BP_STAGE1['diastolic']}", category != "Normal")
        out["blood_pressure"]["category"] = category

    value = fli(lab["triglycerides"], a["weight_kg"], a["height_cm"], lab["ggt"], a["waist_cm"])
    if value is not None:
        add("fli", "Fatty Liver Index (FLI)", value, "", f"< {t.FLI_RULE_OUT} descarta; ≥ {t.FLI_RULE_IN} sugiere esteatosis", value >= t.FLI_RULE_IN)
        out["fli"]["category"] = fli_category(value)

    value = findrisc(facts)
    if value is not None:
        band = findrisc_risk(value)
        add("findrisc", "FINDRISC (riesgo de diabetes tipo 2)", value, "pts", f"< {t.FINDRISC_SCREENING_FROM}", value >= t.FINDRISC_SCREENING_FROM)
        out["findrisc"]["category"] = f"Riesgo {band['label'].lower()}, {band['ten_year_risk']} a 10 años"

    return out
