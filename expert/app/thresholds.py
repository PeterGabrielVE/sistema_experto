"""Clinical cut-off points used by the indices and rules (adults, reference only).

Loaded from shared/clinical_thresholds.json, the single source also read by Laravel
(config/clinical.php) and the inference service.
"""

from __future__ import annotations

import json
import os
from pathlib import Path


def _path() -> Path:
    if os.environ.get("CLINICAL_THRESHOLDS_PATH"):
        return Path(os.environ["CLINICAL_THRESHOLDS_PATH"])
    here = Path(__file__).resolve()
    # Docker image: /service/shared; repository checkout: <repo>/shared.
    candidates = [here.parents[1] / "shared", here.parents[2] / "shared"]
    return next((c for c in candidates if c.is_dir()), candidates[-1]) / "clinical_thresholds.json"


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
