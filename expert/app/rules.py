"""Expert rules: from facts and indices to explained clinical findings.

Four assessments summarize the patient (glycemic status, insulin resistance,
metabolic syndrome, atherogenic profile); the rules turn facts, indices and assessments into findings
with evidence and a suggested action, and nutrition.py into a suggested macronutrient
distribution. Both support, never replace, the professional's judgement.
"""

from __future__ import annotations

from dataclasses import dataclass
from typing import Callable, Literal

from . import indices, nutrition
from .indices import fmt
from . import thresholds as t

RULESET_VERSION = "2026.10.5.1"

Severity = Literal["info", "warning", "alert"]
SEVERITY_ORDER = {"alert": 0, "warning": 1, "info": 2}


# --------------------------------------------------------------------------- assessments


def glycemic_status(facts: dict) -> dict:
    """ADA criteria on fasting glucose and HbA1c (capillary glucose is not fasting: not used)."""
    lab, known = facts["labs"], facts["conditions"]
    glucose, hba1c = lab["fasting_glucose"], lab["hba1c"]
    evidence = []
    if glucose is not None:
        evidence.append(f"Glicemia en ayunas {fmt(glucose)} mg/dL")
    if hba1c is not None:
        evidence.append(f"HbA1c {fmt(hba1c)} %")

    if known["diabetes"]:
        status = "diabetes_conocida"
    elif (glucose or 0) >= t.FASTING_GLUCOSE_DIABETES or (hba1c or 0) >= t.HBA1C_DIABETES:
        status = "rango_diabetes"
    elif (glucose or 0) >= t.FASTING_GLUCOSE_PREDIABETES or (hba1c or 0) >= t.HBA1C_PREDIABETES:
        status = "prediabetes"
    elif evidence:
        status = "normal"
    else:
        status = "indeterminado"

    return {"status": status, "evidence": evidence}


def insulin_resistance(computed: dict) -> dict:
    """HOMA-IR, TyG and TG/HDL: probable with two or more above the cut-off, possible with one."""
    cutoffs = {"homa_ir": t.HOMA_IR, "tyg": t.TYG, "tg_hdl": t.TG_HDL}
    available = {k: computed[k] for k in cutoffs if k in computed}
    positive = [k for k, index in available.items() if index["high"]]

    if not available:
        status = "indeterminado"
    elif len(positive) >= 2:
        status = "probable"
    elif positive:
        status = "posible"
    else:
        status = "no_sugerida"

    return {
        "status": status,
        "positive": len(positive),
        "evaluated": len(available),
        "evidence": [
            f"{i['label']} {fmt(i['value'])} ({'sobre' if i['high'] else 'bajo'} el umbral {fmt(cutoffs[k])})"
            for k, i in available.items()
        ],
    }


def metabolic_syndrome(facts: dict) -> dict:
    """Harmonized criteria (2009): three or more of five. Known conditions count as treated criteria."""
    a, v, lab, known, sex = facts["anthropometry"], facts["vitals"], facts["labs"], facts["conditions"], facts["sex"]

    def criterion(name, met, evidence):
        return {"name": name, "met": met, "evidence": evidence}

    criteria = []

    waist = a["waist_cm"]
    criteria.append(criterion(
        f"Cintura ≥ {t.MS_WAIST[sex]} cm",
        None if waist is None else waist >= t.MS_WAIST[sex],
        None if waist is None else f"{fmt(waist)} cm",
    ))

    tg = lab["triglycerides"]
    criteria.append(criterion(
        f"Triglicéridos ≥ {t.MS_TRIGLYCERIDES} mg/dL",
        None if tg is None else tg >= t.MS_TRIGLYCERIDES,
        None if tg is None else f"{fmt(tg)} mg/dL",
    ))

    hdl = lab["hdl"]
    criteria.append(criterion(
        f"HDL < {t.MS_HDL[sex]} mg/dL",
        None if hdl is None else hdl < t.MS_HDL[sex],
        None if hdl is None else f"{fmt(hdl)} mg/dL",
    ))

    sbp, dbp = v["systolic_bp"], v["diastolic_bp"]
    bp_met = None if sbp is None or dbp is None else (sbp >= t.MS_SYSTOLIC or dbp >= t.MS_DIASTOLIC)
    bp_evidence = None if bp_met is None else f"{sbp}/{dbp} mmHg"
    if known["hypertension"] and not bp_met:
        bp_met, bp_evidence = True, "Hipertensión registrada en la ficha"
    criteria.append(criterion(f"Presión ≥ {t.MS_SYSTOLIC}/{t.MS_DIASTOLIC} mmHg", bp_met, bp_evidence))

    glucose = lab["fasting_glucose"]
    glucose_met = None if glucose is None else glucose >= t.MS_FASTING_GLUCOSE
    glucose_evidence = None if glucose is None else f"{fmt(glucose)} mg/dL"
    if known["diabetes"] and not glucose_met:
        glucose_met, glucose_evidence = True, "Diabetes registrada en la ficha"
    criteria.append(criterion(f"Glicemia en ayunas ≥ {t.MS_FASTING_GLUCOSE} mg/dL", glucose_met, glucose_evidence))

    met = sum(1 for c in criteria if c["met"])
    unknown = sum(1 for c in criteria if c["met"] is None)

    if met >= t.MS_MIN_CRITERIA:
        status = "presente"
    elif met + unknown < t.MS_MIN_CRITERIA:
        status = "ausente"
    else:
        status = "indeterminado"

    return {"status": status, "met": met, "unknown": unknown, "criteria": criteria}


def atherogenic_profile(computed: dict) -> dict:
    """Castelli I and II, AIP and non-HDL cholesterol: altered with any above the cut-off,
    borderline with only the AIP in the intermediate band."""
    available = {k: computed[k] for k in ("castelli_i", "castelli_ii", "aip", "non_hdl") if k in computed}
    positive = [k for k, index in available.items() if index["high"]]

    if not available:
        status = "indeterminado"
    elif positive:
        status = "alterado"
    elif "aip" in available and available["aip"]["value"] >= t.AIP_INTERMEDIATE:
        status = "limitrofe"
    else:
        status = "normal"

    return {
        "status": status,
        "positive": len(positive),
        "evaluated": len(available),
        "evidence": [f"{i['label']} {fmt(i['value'])}{' ' + i['unit'] if i['unit'] else ''} (referencia {i['reference']})" for i in available.values()],
    }


# --------------------------------------------------------------------------- rules


@dataclass(frozen=True)
class Rule:
    id: str
    category: str
    title: str
    description: str
    source: str
    evaluate: Callable[[dict], list[dict]]

    def catalog(self) -> dict:
        return {k: getattr(self, k) for k in ("id", "category", "title", "description", "source")}


def finding(rule_id: str, severity: Severity, title: str, evidence: list[str], recommendation: str | None = None) -> dict:
    return {"rule_id": rule_id, "severity": severity, "title": title, "evidence": evidence, "recommendation": recommendation}


def _glu_diabetes_range(ctx):
    g = ctx["assessments"]["glycemic_status"]
    if g["status"] != "rango_diabetes":
        return []
    return [finding("GLU-01", "alert", "Valores en rango de diabetes sin diagnóstico registrado", g["evidence"],
                    "Confirmar con una segunda medición (glicemia en ayunas o HbA1c) antes de diagnosticar.")]


def _glu_prediabetes(ctx):
    g = ctx["assessments"]["glycemic_status"]
    if g["status"] != "prediabetes":
        return []
    return [finding("GLU-02", "warning", "Valores en rango de prediabetes", g["evidence"],
                    "Indicar cambios de estilo de vida y controlar glicemia y HbA1c periódicamente.")]


def _glu_control(ctx):
    hba1c = ctx["facts"]["labs"]["hba1c"]
    if not ctx["facts"]["conditions"]["diabetes"] or hba1c is None or hba1c < t.HBA1C_TARGET:
        return []
    return [finding("GLU-03", "warning", "Diabetes con HbA1c sobre la meta habitual", [f"HbA1c {fmt(hba1c)} % (meta general < {fmt(t.HBA1C_TARGET)} %)"],
                    "Revisar el plan de tratamiento; la meta puede individualizarse según edad, comorbilidades y riesgo de hipoglicemia.")]


def _insulin_resistance(ctx):
    ir = ctx["assessments"]["insulin_resistance"]
    if ir["status"] == "probable":
        return [finding("RI-01", "warning", "Resistencia a la insulina probable", ir["evidence"],
                        "Priorizar plan alimentario de bajo índice glicémico y actividad física regular.")]
    if ir["status"] == "posible":
        return [finding("RI-01", "info", "Posible resistencia a la insulina (un indicador alterado)", ir["evidence"],
                        "Completar los exámenes faltantes para confirmar.")]
    return []


def _metabolic_syndrome(ctx):
    ms = ctx["assessments"]["metabolic_syndrome"]
    if ms["status"] != "presente":
        return []
    evidence = [f"{c['name']}: {c['evidence']}" for c in ms["criteria"] if c["met"]]
    return [finding("SM-01", "alert", f"Síndrome metabólico ({ms['met']} de 5 criterios)", evidence,
                    "Abordar todos los factores de riesgo cardiometabólico en el plan de tratamiento.")]


def _bmi(ctx):
    index = ctx["indices"].get("bmi")
    if index is None or index["category"] == "Normal":
        return []
    severity = "info" if index["category"] == "Sobrepeso" else "warning"
    return [finding("ANT-01", severity, index["category"], [f"IMC {fmt(index['value'])} kg/m²"])]


def _waist_to_height(ctx):
    index = ctx["indices"].get("waist_to_height")
    if index is None or not index["high"]:
        return []
    return [finding("ANT-02", "warning", "Riesgo cardiometabólico por adiposidad abdominal",
                    [f"Cintura/talla {fmt(index['value'])} (> {fmt(t.WAIST_TO_HEIGHT)})"],
                    "La reducción del perímetro de cintura es un objetivo del tratamiento.")]


def _atherogenic_dyslipidemia(ctx):
    lab, sex = ctx["facts"]["labs"], ctx["facts"]["sex"]
    tg, hdl = lab["triglycerides"], lab["hdl"]
    if tg is None or hdl is None or tg < t.MS_TRIGLYCERIDES or hdl >= t.MS_HDL[sex]:
        return []
    return [finding("LIP-01", "warning", "Dislipidemia aterogénica (TG altos y HDL bajo)",
                    [f"Triglicéridos {fmt(tg)} mg/dL", f"HDL {fmt(hdl)} mg/dL"],
                    "Patrón típico de resistencia a la insulina; reducir azúcares simples y alcohol.")]


def _ldl(ctx):
    ldl = ctx["facts"]["labs"]["ldl"]
    label = "LDL"
    if ldl is None and "ldl_friedewald" in ctx["indices"]:
        ldl, label = ctx["indices"]["ldl_friedewald"]["value"], "LDL calculado (Friedewald)"
    # Stricter goal with diabetes: higher cardiovascular risk.
    diabetes = ctx["facts"]["conditions"]["diabetes"]
    limit = t.LDL_HIGH_DIABETES if diabetes else t.LDL_HIGH
    if ldl is None or ldl < limit:
        return []
    if diabetes:
        return [finding("LIP-02", "warning", "LDL sobre la meta para diabetes", [f"{label} {fmt(ldl)} mg/dL (meta < {limit})"],
                        "Evaluar tratamiento hipolipemiante según el riesgo cardiovascular.")]
    return [finding("LIP-02", "warning", "Colesterol LDL alto", [f"{label} {fmt(ldl)} mg/dL (≥ {limit})"],
                    "Evaluar riesgo cardiovascular global.")]


def _atherogenic_profile(ctx):
    ap = ctx["assessments"]["atherogenic_profile"]
    if ap["status"] == "alterado":
        return [finding("LIP-03", "warning", f"Perfil aterogénico alterado ({ap['positive']} de {ap['evaluated']} índices)", ap["evidence"],
                        "Evaluar riesgo cardiovascular global; reducir grasas saturadas y trans, azúcares simples y alcohol.")]
    if ap["status"] == "limitrofe":
        return [finding("LIP-03", "info", "Índice aterogénico del plasma en riesgo intermedio", ap["evidence"],
                        "Controlar el perfil lipídico y reforzar alimentación y actividad física.")]
    return []


def _fatty_liver(ctx):
    index = ctx["indices"].get("fli")
    if index is None or index["value"] < t.FLI_RULE_OUT:
        return []
    evidence = [f"FLI {fmt(index['value'])} ({index['category'].lower()})"]
    if index["high"]:
        return [finding("HEP-01", "warning", "Esteatosis hepática probable (FLI)", evidence,
                        "Considerar ecografía abdominal y perfil hepático; una baja de peso de 7 a 10 % mejora la esteatosis.")]
    return [finding("HEP-01", "info", "FLI en zona indeterminada", evidence,
                    "No permite descartar esteatosis; considerar ecografía si hay otros factores de riesgo.")]


def _findrisc(ctx):
    index = ctx["indices"].get("findrisc")
    if index is None or index["value"] < t.FINDRISC_RISK[1]["from"]:
        return []
    points = indices.findrisc_points(ctx["facts"])
    evidence = [f"{label}: {points[k]} pts" for k, label in indices.FINDRISC_ITEMS.items() if points[k]]
    title = f"FINDRISC {index['value']} pts: {index['category'].lower()}"
    if index["high"]:
        return [finding("FIN-01", "warning", title, evidence,
                        "Tamizaje con glicemia en ayunas o HbA1c (o PTGO) e intervención intensiva en estilo de vida.")]
    return [finding("FIN-01", "info", title, evidence, "Reforzar hábitos saludables y repetir el cuestionario en los controles.")]


def _blood_pressure(ctx):
    index = ctx["indices"].get("blood_pressure")
    if index is None or not index["category"].startswith("Hipertensión"):
        return []
    known = ctx["facts"]["conditions"]["hypertension"]
    title = f"{index['category']}{' (hipertensión registrada)' if known else ''}"
    severity = "alert" if index["category"].endswith("2") else "warning"
    action = "Revisar el control de la presión arterial." if known else "Confirmar con mediciones repetidas antes de diagnosticar."
    return [finding("PA-01", severity, title, [f"Presión arterial {index['value']} mmHg"], action)]


MISSING_DATA = [
    # (needed by, fields, suggestion)
    ("HOMA-IR", ("labs", "fasting_insulin"), "Solicitar insulina basal para calcular HOMA-IR."),
    ("HOMA-IR, índice TyG y glicemia", ("labs", "fasting_glucose"), "Solicitar glicemia en ayunas."),
    ("índice TyG, TG/HDL y síndrome metabólico", ("labs", "triglycerides"), "Solicitar perfil lipídico (triglicéridos)."),
    ("TG/HDL y síndrome metabólico", ("labs", "hdl"), "Solicitar perfil lipídico (colesterol HDL)."),
    ("confirmar el estado glicémico", ("labs", "hba1c"), "Solicitar HbA1c."),
    ("síndrome metabólico y cintura/talla", ("anthropometry", "waist_cm"), "Medir el perímetro de cintura."),
    ("síndrome metabólico", ("vitals", "systolic_bp"), "Registrar la presión arterial."),
    ("FLI (hígado graso)", ("labs", "ggt"), "Solicitar GGT para calcular el FLI."),
]


def _missing_data(ctx):
    missing = [(needed, suggestion) for needed, (group, field), suggestion in MISSING_DATA if ctx["facts"][group][field] is None]
    if indices.findrisc_applies(ctx["facts"]):
        points = indices.findrisc_points(ctx["facts"])
        # Age, BMI and waist come from the consultation and measurements, not from the questionnaire.
        unanswered = [label.lower() for k, label in indices.FINDRISC_ITEMS.items() if points[k] is None and k not in ("age", "bmi", "waist")]
        if unanswered:
            missing.append((f"FINDRISC ({', '.join(unanswered)})", "Completar el cuestionario FINDRISC en la ficha clínica."))
    if not missing:
        return []
    return [finding("DAT-01", "info", "Datos faltantes para completar la evaluación",
                    [f"Falta dato para {needed}" for needed, _ in missing],
                    " ".join(suggestion for _, suggestion in missing))]


def _not_adult(ctx):
    age = ctx["facts"]["age"]
    if age is None or age >= t.ADULT_AGE:
        return []
    return [finding("DAT-02", "warning", "Paciente menor de 18 años", [f"Edad {age} años"],
                    "Los puntos de corte son de adultos: interpretar con referencias pediátricas.")]


RULES: list[Rule] = [
    Rule("GLU-01", "Glicemia", "Rango de diabetes", f"Glicemia en ayunas ≥ {t.FASTING_GLUCOSE_DIABETES} mg/dL o HbA1c ≥ {fmt(t.HBA1C_DIABETES)} % sin diabetes registrada.", "ADA Standards of Care", _glu_diabetes_range),
    Rule("GLU-02", "Glicemia", "Prediabetes", f"Glicemia en ayunas {t.FASTING_GLUCOSE_PREDIABETES}-{t.FASTING_GLUCOSE_DIABETES - 1} mg/dL o HbA1c {fmt(t.HBA1C_PREDIABETES)}-{fmt(t.HBA1C_DIABETES - 0.1)} %.", "ADA Standards of Care", _glu_prediabetes),
    Rule("GLU-03", "Glicemia", "Control glicémico", f"Diabetes registrada con HbA1c ≥ {fmt(t.HBA1C_TARGET)} %.", "ADA Standards of Care", _glu_control),
    Rule("RI-01", "Resistencia a la insulina", "Indicadores de resistencia a la insulina", f"HOMA-IR > {fmt(t.HOMA_IR)}, TyG > {fmt(t.TYG)}, TG/HDL > {fmt(t.TG_HDL)}: probable con dos o más, posible con uno.", "Matthews 1985; Simental-Mendía 2008; McLaughlin 2003", _insulin_resistance),
    Rule("SM-01", "Síndrome metabólico", "Síndrome metabólico", "Tres o más criterios: cintura, triglicéridos, HDL, presión arterial, glicemia en ayunas.", "Criterios armonizados, Alberti et al. 2009", _metabolic_syndrome),
    Rule("ANT-01", "Antropometría", "Estado nutricional por IMC", f"IMC fuera del rango normal ({fmt(t.BMI_NORMAL_FROM)} a < {fmt(t.BMI_OVERWEIGHT_FROM)}).", "OMS", _bmi),
    Rule("ANT-02", "Antropometría", "Cintura/talla", f"Cintura/talla > {fmt(t.WAIST_TO_HEIGHT)}.", "Ashwell 2012", _waist_to_height),
    Rule("LIP-01", "Lípidos", "Dislipidemia aterogénica", "Triglicéridos altos con HDL bajo.", "NCEP ATP III", _atherogenic_dyslipidemia),
    Rule("LIP-02", "Lípidos", "LDL alto", f"LDL (informado o Friedewald) ≥ {t.LDL_HIGH} mg/dL; ≥ {t.LDL_HIGH_DIABETES} mg/dL con diabetes registrada.", "NCEP ATP III; ADA Standards of Care", _ldl),
    Rule("PA-01", "Presión arterial", "Hipertensión", f"Presión ≥ {t.BP_STAGE1['systolic']}/{t.BP_STAGE1['diastolic']} mmHg.", "ACC/AHA 2017", _blood_pressure),
    Rule("LIP-03", "Lípidos", "Perfil aterogénico", f"Castelli I > {fmt(t.CASTELLI_I['H'])}/{fmt(t.CASTELLI_I['M'])}, Castelli II > {fmt(t.CASTELLI_II['H'])}/{fmt(t.CASTELLI_II['M'])} (hombres/mujeres), AIP > {fmt(t.AIP_HIGH)} o colesterol no HDL ≥ {t.NON_HDL_HIGH} mg/dL; limítrofe con AIP {fmt(t.AIP_INTERMEDIATE)} a {fmt(t.AIP_HIGH)}.", "Millán et al. 2009; Dobiásová 2001; NCEP ATP III", _atherogenic_profile),
    Rule("HEP-01", "Hígado", "Hígado graso (FLI)", f"Fatty Liver Index ≥ {t.FLI_RULE_IN} sugiere esteatosis; {t.FLI_RULE_OUT} a {t.FLI_RULE_IN - 1} es zona indeterminada.", "Bedogni et al. 2006", _fatty_liver),
    Rule("FIN-01", "Glicemia", "Riesgo de diabetes tipo 2 (FINDRISC)", f"FINDRISC ≥ {t.FINDRISC_SCREENING_FROM} pts: tamizaje; {t.FINDRISC_RISK[1]['from']} a {t.FINDRISC_SCREENING_FROM - 1} pts: riesgo levemente elevado. Solo adultos sin diabetes registrada.", "Lindström y Tuomilehto 2003", _findrisc),
    Rule("DAT-01", "Datos", "Datos faltantes", "Exámenes o mediciones que faltan para completar los índices y criterios.", "Sistema experto", _missing_data),
    Rule("DAT-02", "Datos", "Menor de edad", "Los puntos de corte usados son de adultos.", "Sistema experto", _not_adult),
]


def evaluate(facts: dict) -> dict:
    computed = indices.compute(facts)
    ctx = {
        "facts": facts,
        "indices": computed,
        "assessments": {
            "glycemic_status": glycemic_status(facts),
            "insulin_resistance": insulin_resistance(computed),
            "metabolic_syndrome": metabolic_syndrome(facts),
            "atherogenic_profile": atherogenic_profile(computed),
        },
    }
    findings = [f for rule in RULES for f in rule.evaluate(ctx)]
    findings.sort(key=lambda f: SEVERITY_ORDER[f["severity"]])  # stable: rule order within a severity

    return {
        "indices": computed,
        "assessments": ctx["assessments"],
        "findings": findings,
        "macronutrients": nutrition.macro_plan(ctx),
        "ruleset_version": RULESET_VERSION,
    }
