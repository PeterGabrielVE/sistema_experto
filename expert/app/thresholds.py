"""Clinical cut-off points used by the indices and rules (adults, reference only).

Loaded from shared/clinical_thresholds.json, the single source also read by Laravel
(config/clinical.php) and the inference service.
"""

from __future__ import annotations

import json
from pathlib import Path

from .config import get_settings


def _path() -> Path:
    return get_settings().thresholds_path


_t = json.loads(_path().read_text(encoding="utf-8"))

# Insulin resistance.
HOMA_IR = _t["insulin_resistance"]["homa_ir"]
TYG = _t["insulin_resistance"]["tyg"]
TG_HDL = _t["insulin_resistance"]["tg_hdl"]

# Anthropometry.
BMI_NORMAL_FROM = _t["bmi"]["normal_from"]
BMI_OVERWEIGHT_FROM = _t["bmi"]["overweight_from"]
BMI_OBESITY_FROM = _t["bmi"]["obesity_from"]
WAIST_TO_HEIGHT = _t["anthropometry"]["waist_to_height"]
WAIST_TO_HIP = _t["anthropometry"]["waist_to_hip"]

# Blood pressure.
BP_ELEVATED_SYSTOLIC = _t["blood_pressure"]["elevated_systolic"]
BP_STAGE1 = _t["blood_pressure"]["stage1"]
BP_STAGE2 = _t["blood_pressure"]["stage2"]

# Glycemia.
FASTING_GLUCOSE_PREDIABETES = _t["glycemia"]["fasting_glucose_prediabetes"]
FASTING_GLUCOSE_DIABETES = _t["glycemia"]["fasting_glucose_diabetes"]
HBA1C_PREDIABETES = _t["glycemia"]["hba1c_prediabetes"]
HBA1C_DIABETES = _t["glycemia"]["hba1c_diabetes"]
HBA1C_TARGET = _t["glycemia"]["hba1c_target"]

# Metabolic syndrome.
MS_WAIST = _t["metabolic_syndrome"]["waist"]
MS_TRIGLYCERIDES = _t["metabolic_syndrome"]["triglycerides"]
MS_HDL = _t["metabolic_syndrome"]["hdl"]
MS_SYSTOLIC = _t["metabolic_syndrome"]["systolic"]
MS_DIASTOLIC = _t["metabolic_syndrome"]["diastolic"]
MS_FASTING_GLUCOSE = _t["metabolic_syndrome"]["fasting_glucose"]
MS_MIN_CRITERIA = _t["metabolic_syndrome"]["min_criteria"]

# Lipids.
LDL_HIGH = _t["lipids"]["ldl_high"]
LDL_HIGH_DIABETES = _t["lipids"]["ldl_high_diabetes"]
NON_HDL_HIGH = _t["lipids"]["non_hdl_high"]
FRIEDEWALD_MAX_TRIGLYCERIDES = _t["lipids"]["friedewald_max_triglycerides"]

ADULT_AGE = _t["adult_age"]

# Atherogenic profile.
CASTELLI_I = _t["atherogenic"]["castelli_i"]
CASTELLI_II = _t["atherogenic"]["castelli_ii"]
AIP_INTERMEDIATE = _t["atherogenic"]["aip_intermediate"]
AIP_HIGH = _t["atherogenic"]["aip_high"]

# Fatty liver.
FLI_RULE_OUT = _t["fatty_liver"]["fli_rule_out"]
FLI_RULE_IN = _t["fatty_liver"]["fli_rule_in"]

# Type 2 diabetes risk.
FINDRISC_AGE = _t["findrisc"]["age"]
FINDRISC_BMI = _t["findrisc"]["bmi"]
FINDRISC_WAIST = _t["findrisc"]["waist"]
FINDRISC_RISK = _t["findrisc"]["risk"]
FINDRISC_SCREENING_FROM = _t["findrisc"]["screening_from"]

# Macronutrient distribution.
_n = _t["nutrition"]
ACTIVITY_FACTORS = _n["activity_factors"]
ENERGY = _n["energy"]
MACROS_BASE = _n["base"]
MACROS_GLYCEMIC = _n["glycemic"]
MACROS_DIABETES = _n["diabetes"]
MACROS_TRIGLYCERIDES = _n["triglycerides"]
MACROS_FATTY_LIVER = _n["fatty_liver"]
MACRO_BOUNDS = _n["bounds"]
PROTEIN_G_PER_KG = _n["protein_g_per_kg"]
OLDER_AGE = _n["older_age"]
SATURATED_FAT_PCT = _n["saturated_fat_pct"]
ADDED_SUGAR_PCT = _n["added_sugar_pct"]
FIBER_G_PER_1000_KCAL = _n["fiber_g_per_1000_kcal"]
SODIUM_MG = _n["sodium_mg"]
GLYCEMIC_LOAD = _n["glycemic_load"]

# Macronutrient rules configured in the app: variables, operators and allowed range of each action.
CONFIGURABLE = _t["configurable_macro_rules"]

# Meal plan by linear programming.
MEAL_PLAN = _t["meal_plan"]
