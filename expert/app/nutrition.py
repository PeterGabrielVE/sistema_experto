"""Suggested macronutrient distribution: energy target and share of carbohydrates, proteins and fats.

Starts from the base distribution (AMDR) and applies the medical rules (MAC-xx) on top of the
facts, indices and assessments of rules.py: the lowest carbohydrate share and the highest fat
share win, protein takes the rest and is raised when it falls short of the minimum g/kg.
A starting point for the dietitian, not a prescription.
"""

from __future__ import annotations

import math
from dataclasses import dataclass
from typing import Callable

from .indices import _round, fmt
from . import thresholds as t

CATEGORY = "Nutrición"

ACTIVITY_LEVELS = ["Muy ligera", "Ligera", "Moderada", "Activa", "Muy activa"]

# label, kcal per gram
MACROS = {"carbohydrates": ("Carbohidratos", 4), "proteins": ("Proteínas", 4), "fats": ("Grasas", 9)}


@dataclass(frozen=True)
class MacroRule:
    """evaluate returns None when the rule does not apply, otherwise an adjustment:
    evidence and advice, plus any of carbohydrates (max %), fats (%), energy (kcal),
    protein_g_per_kg (minimum), saturated_fat_pct, added_sugar_pct, sodium_mg (maximums)."""

    id: str
    title: str
    description: str
    source: str
    evaluate: Callable[[dict], dict | None]

    def catalog(self) -> dict:
        return {"id": self.id, "category": CATEGORY, "title": self.title, "description": self.description, "source": self.source}


def _excess_weight(ctx):
    bmi = ctx["indices"].get("bmi")
    if bmi is None or bmi["category"] not in ("Sobrepeso", "Obesidad"):
        return None
    deficit = t.ENERGY["obesity_deficit" if bmi["category"] == "Obesidad" else "overweight_deficit"]
    return {
        "evidence": [f"IMC {fmt(bmi['value'])} kg/m² ({bmi['category'].lower()})"],
        "energy": -deficit,
        "protein_g_per_kg": t.PROTEIN_G_PER_KG["weight_change"],
        "advice": f"Déficit de {deficit} kcal/día; meta inicial de 5 a 10 % del peso en 6 meses, con proteína suficiente para preservar masa magra.",
    }


def _underweight(ctx):
    bmi = ctx["indices"].get("bmi")
    if bmi is None or bmi["category"] != "Bajo peso":
        return None
    surplus = t.ENERGY["underweight_surplus"]
    return {
        "evidence": [f"IMC {fmt(bmi['value'])} kg/m²"],
        "energy": surplus,
        "protein_g_per_kg": t.PROTEIN_G_PER_KG["weight_change"],
        "advice": f"Superávit de {surplus} kcal/día con alimentos de alta densidad nutricional; descartar causas orgánicas del bajo peso.",
    }


def _diabetes(ctx):
    g = ctx["assessments"]["glycemic_status"]
    if g["status"] not in ("diabetes_conocida", "rango_diabetes"):
        return None
    evidence = ["Diabetes registrada en la ficha"] if g["status"] == "diabetes_conocida" else g["evidence"]
    return {
        "evidence": evidence,
        **t.MACROS_DIABETES,
        "added_sugar_pct": t.ADDED_SUGAR_PCT["metabolic"],
        "advice": "Carbohidratos de bajo índice glicémico y ricos en fibra, repartidos en el día y constantes entre comidas; con insulina, conteo de carbohidratos.",
    }


def _glycemic(ctx):
    a = ctx["assessments"]
    if a["glycemic_status"]["status"] in ("diabetes_conocida", "rango_diabetes"):
        return None  # MAC-03
    evidence = []
    if a["glycemic_status"]["status"] == "prediabetes" or ctx["facts"]["conditions"]["prediabetes"]:
        evidence.append("Prediabetes")
    if a["insulin_resistance"]["status"] == "probable":
        evidence.append("Resistencia a la insulina probable")
    if a["metabolic_syndrome"]["status"] == "presente":
        evidence.append(f"Síndrome metabólico ({a['metabolic_syndrome']['met']} de 5 criterios)")
    if not evidence:
        return None
    return {
        "evidence": evidence,
        **t.MACROS_GLYCEMIC,
        "added_sugar_pct": t.ADDED_SUGAR_PCT["metabolic"],
        "advice": "Reducir carbohidratos refinados y bebidas azucaradas; preferir granos integrales, legumbres y verduras.",
    }


def _triglycerides(ctx):
    tg = ctx["facts"]["labs"]["triglycerides"]
    if tg is None or tg < t.MS_TRIGLYCERIDES:
        return None
    return {
        "evidence": [f"Triglicéridos {fmt(tg)} mg/dL (≥ {t.MS_TRIGLYCERIDES})"],
        **t.MACROS_TRIGLYCERIDES,
        "added_sugar_pct": t.ADDED_SUGAR_PCT["metabolic"],
        "advice": "Evitar alcohol, azúcares y harinas refinadas; preferir grasas insaturadas y pescado graso (omega 3).",
    }


def _ldl_atherogenic(ctx):
    lab, computed = ctx["facts"]["labs"], ctx["indices"]
    ldl = lab["ldl"] if lab["ldl"] is not None else computed.get("ldl_friedewald", {}).get("value")
    limit = t.LDL_HIGH_DIABETES if ctx["facts"]["conditions"]["diabetes"] else t.LDL_HIGH
    evidence = []
    if ldl is not None and ldl >= limit:
        evidence.append(f"LDL {fmt(ldl)} mg/dL (≥ {limit})")
    if computed.get("non_hdl", {}).get("high"):
        evidence.append(f"Colesterol no HDL {fmt(computed['non_hdl']['value'])} mg/dL")
    if ctx["assessments"]["atherogenic_profile"]["status"] == "alterado":
        evidence.append("Perfil aterogénico alterado")
    if ctx["facts"]["conditions"]["dyslipidemia"]:
        evidence.append("Dislipidemia registrada en la ficha")
    if not evidence:
        return None
    return {
        "evidence": evidence,
        "saturated_fat_pct": t.SATURATED_FAT_PCT["lipids"],
        "advice": "Reemplazar grasas saturadas por mono y poliinsaturadas (aceite de oliva, palta, frutos secos), eliminar grasas trans y aportar fibra soluble (avena, legumbres).",
    }


def _fatty_liver(ctx):
    fli = ctx["indices"].get("fli")
    if fli is None or not fli["high"]:
        return None
    return {
        "evidence": [f"FLI {fmt(fli['value'])}"],
        **t.MACROS_FATTY_LIVER,
        "added_sugar_pct": t.ADDED_SUGAR_PCT["metabolic"],
        "advice": "Patrón mediterráneo sin fructosa añadida ni alcohol; con exceso de peso, bajar de 7 a 10 % del peso.",
    }


def _hypertension(ctx):
    bp = ctx["indices"].get("blood_pressure")
    evidence = []
    if ctx["facts"]["conditions"]["hypertension"]:
        evidence.append("Hipertensión registrada en la ficha")
    if bp is not None and bp["category"].startswith("Hipertensión"):
        evidence.append(f"Presión arterial {bp['value']} mmHg")
    if not evidence:
        return None
    return {
        "evidence": evidence,
        "sodium_mg": t.SODIUM_MG["hypertension"],
        "advice": "Patrón DASH: frutas, verduras, legumbres y lácteos descremados; evitar ultraprocesados y sal agregada.",
    }


def _older_adult(ctx):
    age = ctx["facts"]["age"]
    if age < t.OLDER_AGE:
        return None
    return {
        "evidence": [f"Edad {age} años"],
        "protein_g_per_kg": t.PROTEIN_G_PER_KG["older"],
        "advice": "Repartir la proteína en las comidas principales (25 a 30 g en cada una) para prevenir sarcopenia.",
    }


MACRO_RULES: list[MacroRule] = [
    MacroRule("MAC-01", "Exceso de peso", f"IMC ≥ {fmt(t.BMI_OVERWEIGHT_FROM)}: déficit de {t.ENERGY['overweight_deficit']} kcal (sobrepeso) o {t.ENERGY['obesity_deficit']} kcal (obesidad) y proteína ≥ {fmt(t.PROTEIN_G_PER_KG['weight_change'])} g/kg de peso de referencia.", "AHA/ACC/TOS 2013", _excess_weight),
    MacroRule("MAC-02", "Bajo peso", f"IMC < {fmt(t.BMI_NORMAL_FROM)}: superávit de {t.ENERGY['underweight_surplus']} kcal y proteína ≥ {fmt(t.PROTEIN_G_PER_KG['weight_change'])} g/kg.", "OMS", _underweight),
    MacroRule("MAC-03", "Diabetes", f"Diabetes registrada o en rango: carbohidratos {t.MACROS_DIABETES['carbohydrates']} %, grasas {t.MACROS_DIABETES['fats']} %, azúcares añadidos < {t.ADDED_SUGAR_PCT['metabolic']} %.", "ADA Standards of Care; consenso ADA 2019 de terapia nutricional", _diabetes),
    MacroRule("MAC-04", "Alteración glicémica sin diabetes", f"Prediabetes, resistencia a la insulina probable o síndrome metabólico: carbohidratos {t.MACROS_GLYCEMIC['carbohydrates']} %, grasas {t.MACROS_GLYCEMIC['fats']} %.", "ADA Standards of Care", _glycemic),
    MacroRule("MAC-05", "Hipertrigliceridemia", f"Triglicéridos ≥ {t.MS_TRIGLYCERIDES} mg/dL: carbohidratos {t.MACROS_TRIGLYCERIDES['carbohydrates']} %, grasas {t.MACROS_TRIGLYCERIDES['fats']} %, azúcares añadidos < {t.ADDED_SUGAR_PCT['metabolic']} %.", "AHA Scientific Statement 2011 (Miller et al.)", _triglycerides),
    MacroRule("MAC-06", "LDL alto o perfil aterogénico", f"LDL sobre la meta, colesterol no HDL alto, perfil aterogénico alterado o dislipidemia registrada: grasa saturada < {t.SATURATED_FAT_PCT['lipids']} %.", "NCEP ATP III (TLC)", _ldl_atherogenic),
    MacroRule("MAC-07", "Hígado graso", f"FLI ≥ {t.FLI_RULE_IN}: carbohidratos {t.MACROS_FATTY_LIVER['carbohydrates']} %, sin fructosa añadida ni alcohol.", "EASL-EASD-EASO 2016", _fatty_liver),
    MacroRule("MAC-08", "Hipertensión", f"Hipertensión registrada o presión ≥ {t.BP_STAGE1['systolic']}/{t.BP_STAGE1['diastolic']} mmHg: sodio < {t.SODIUM_MG['hypertension']} mg/día.", "AHA; dieta DASH", _hypertension),
    MacroRule("MAC-09", "Adulto mayor", f"{t.OLDER_AGE} años o más: proteína ≥ {fmt(t.PROTEIN_G_PER_KG['older'])} g/kg.", "PROT-AGE 2013", _older_adult),
]


def bmr(sex: str, age: int, weight_kg: float, height_cm: float) -> float:
    """Basal metabolic rate, Mifflin-St Jeor (kcal/day)."""
    return 10 * weight_kg + 6.25 * height_cm - 5 * age + (5 if sex == "H" else -161)


def reference_weight(weight_kg: float, height_cm: float) -> float:
    """Actual weight, or the weight at the top of the normal BMI with excess weight: protein is
    dosed on lean mass, not on fat."""
    return _round(min(weight_kg, t.BMI_OVERWEIGHT_FROM * (height_cm / 100) ** 2), 1)


def distribute(adjustments: list[dict], target_kcal: int, ref_weight: float) -> tuple[dict[str, int], float, list[str]]:
    """Percent of each macronutrient; the protein minimum, in g/kg; notes."""
    bounds, notes = t.MACRO_BOUNDS, []
    carbohydrates = min([t.MACROS_BASE["carbohydrates"], *(a["carbohydrates"] for a in adjustments if "carbohydrates" in a)])
    fats = max([t.MACROS_BASE["fats"], *(a["fats"] for a in adjustments if "fats" in a)])
    shares = {"carbohydrates": carbohydrates, "proteins": 100 - carbohydrates - fats, "fats": fats}

    min_g_per_kg = max([t.PROTEIN_G_PER_KG["adult"], *(a["protein_g_per_kg"] for a in adjustments if "protein_g_per_kg" in a)])
    needed = min(math.ceil(min_g_per_kg * ref_weight * 4 / target_kcal * 100), bounds["proteins"][1])
    for donor in ("carbohydrates", "fats"):  # protein first takes from carbohydrates
        moved = max(0, min(needed - shares["proteins"], shares[donor] - bounds[donor][0]))
        shares[donor] -= moved
        shares["proteins"] += moved
    if shares["proteins"] * target_kcal / 400 < min_g_per_kg * ref_weight:
        notes.append(f"No se alcanza {fmt(min_g_per_kg)} g/kg de proteína dentro de los rangos de distribución: revisar el aporte energético.")

    return shares, min_g_per_kg, notes


def macro_plan(ctx: dict) -> dict:
    facts = ctx["facts"]
    sex, age, a = facts["sex"], facts["age"], facts["anthropometry"]
    weight, height = a["weight_kg"], a["height_cm"]

    if age is None or weight is None or height is None:
        return {"status": "indeterminado", "reason": "Faltan edad, peso o talla para calcular el requerimiento energético.", "rules": [], "notes": []}
    if age < t.ADULT_AGE:
        return {"status": "no_aplica", "reason": "Menor de 18 años: usar requerimientos y curvas pediátricas.", "rules": [], "notes": []}

    notes = []
    level = facts.get("physical_activity")
    if level is None:
        level = 0
        notes.append("Sin nivel de actividad física: se asume muy ligera.")

    applied = [(rule, adj) for rule in MACRO_RULES if (adj := rule.evaluate(ctx)) is not None]
    adjustments = [adj for _, adj in applied]

    basal = bmr(sex, age, weight, height)
    maintenance = basal * t.ACTIVITY_FACTORS[level]
    adjustment = sum(adj.get("energy", 0) for adj in adjustments)
    target = int(_round((maintenance + adjustment) / 10, 0)) * 10  # to the nearest 10 kcal
    floor = t.ENERGY["min_kcal"][sex]
    if target < floor:
        target = floor
        notes.append(f"Se aplica el mínimo de {floor} kcal/día; bajo ese aporte, solo con supervisión.")

    ref_weight = reference_weight(weight, height)
    shares, min_g_per_kg, distribution_notes = distribute(adjustments, target, ref_weight)
    notes += distribution_notes

    macros = {}
    for key, (label, kcal_per_g) in MACROS.items():
        kcal = target * shares[key] / 100
        macros[key] = {"label": label, "percent": shares[key], "grams": int(_round(kcal / kcal_per_g, 0)), "kcal": int(_round(kcal, 0))}
    macros["proteins"]["g_per_kg"] = _round(macros["proteins"]["grams"] / ref_weight, 2)
    if shares["proteins"] > t.MACROS_BASE["proteins"]:
        notes.append("Con enfermedad renal crónica ajustar la proteína: la función renal no está en los datos evaluados.")

    saturated = min([t.SATURATED_FAT_PCT["base"], *(adj["saturated_fat_pct"] for adj in adjustments if "saturated_fat_pct" in adj)])
    sugar = min([t.ADDED_SUGAR_PCT["base"], *(adj["added_sugar_pct"] for adj in adjustments if "added_sugar_pct" in adj)])
    sodium = min([t.SODIUM_MG["base"], *(adj["sodium_mg"] for adj in adjustments if "sodium_mg" in adj)])

    return {
        "status": "calculado",
        "energy": {
            "bmr": int(_round(basal, 0)),
            "activity_level": ACTIVITY_LEVELS[level],
            "activity_factor": t.ACTIVITY_FACTORS[level],
            "maintenance": int(_round(maintenance, 0)),
            "adjustment": adjustment,
            "target": target,
        },
        "reference_weight_kg": ref_weight,
        "protein_min_g_per_kg": min_g_per_kg,
        "macros": macros,
        "limits": {
            "saturated_fat": {"label": "Grasas saturadas", "comparator": "max", "amount": int(_round(target * saturated / 900, 0)), "unit": "g", "percent": saturated},
            "added_sugar": {"label": "Azúcares añadidos", "comparator": "max", "amount": int(_round(target * sugar / 400, 0)), "unit": "g", "percent": sugar},
            "fiber": {"label": "Fibra", "comparator": "min", "amount": int(_round(target * t.FIBER_G_PER_1000_KCAL / 1000, 0)), "unit": "g"},
            "sodium": {"label": "Sodio", "comparator": "max", "amount": sodium, "unit": "mg"},
        },
        "rules": [{"rule_id": rule.id, "title": rule.title, "evidence": adj["evidence"], "advice": adj["advice"]} for rule, adj in applied],
        "notes": notes,
    }
