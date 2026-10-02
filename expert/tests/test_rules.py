from app import rules

# Healthy answers: 0 points.
FINDRISC_ANSWERS = {
    "daily_physical_activity": True,
    "daily_fruit_vegetables": True,
    "antihypertensive_medication": False,
    "high_glucose_history": False,
    "family_history_diabetes": "none",
}


def rule_ids(result):
    return [f["rule_id"] for f in result["findings"]]


def finding_of(result, rule_id):
    return next((f for f in result["findings"] if f["rule_id"] == rule_id), None)


def test_patient_with_insulin_resistance_and_metabolic_syndrome(facts):
    result = rules.evaluate(facts(
        anthropometry={"weight_kg": 80, "height_cm": 165, "waist_cm": 98},
        vitals={"systolic_bp": 128, "diastolic_bp": 82},
        labs={
            "fasting_glucose": 105, "fasting_insulin": 18.2, "hba1c": 5.9,
            "total_cholesterol": 210, "hdl": 38, "ldl": 135, "triglycerides": 180, "ggt": 45,
        },
        risk_factors=FINDRISC_ANSWERS,
    ))

    a = result["assessments"]
    assert a["glycemic_status"]["status"] == "prediabetes"
    assert (a["insulin_resistance"]["status"], a["insulin_resistance"]["positive"]) == ("probable", 3)
    assert a["insulin_resistance"]["evidence"][0] == "HOMA-IR 4,72 (sobre el umbral 2,5)"
    # Waist, TG, HDL and glucose; 128/82 is below 130/85.
    assert (a["metabolic_syndrome"]["status"], a["metabolic_syndrome"]["met"]) == ("presente", 4)

    ids = rule_ids(result)
    assert ids[0] == "SM-01"  # alerts first
    assert {"GLU-02", "RI-01", "ANT-01", "ANT-02", "LIP-01", "PA-01"} <= set(ids)
    assert "DAT-01" not in ids  # nothing missing


def test_healthy_patient_has_no_findings(facts):
    result = rules.evaluate(facts(
        sex="M",
        anthropometry={"weight_kg": 58, "height_cm": 162, "waist_cm": 72},
        vitals={"systolic_bp": 112, "diastolic_bp": 72},
        labs={
            "fasting_glucose": 85, "fasting_insulin": 6, "hba1c": 5.2,
            "total_cholesterol": 170, "hdl": 62, "ldl": 95, "triglycerides": 80, "ggt": 18,
        },
        risk_factors=FINDRISC_ANSWERS,
    ))

    assert result["findings"] == []
    assert result["assessments"]["glycemic_status"]["status"] == "normal"
    assert result["assessments"]["insulin_resistance"]["status"] == "no_sugerida"
    assert result["assessments"]["metabolic_syndrome"]["status"] == "ausente"
    assert result["assessments"]["atherogenic_profile"]["status"] == "normal"
    assert result["indices"]["findrisc"]["value"] == 0


def test_metabolic_syndrome_is_indeterminate_with_missing_criteria(facts):
    ms = rules.evaluate(facts(labs={"triglycerides": 200, "hdl": 35}))["assessments"]["metabolic_syndrome"]
    assert (ms["status"], ms["met"], ms["unknown"]) == ("indeterminado", 2, 3)


def test_metabolic_syndrome_is_absent_when_missing_criteria_cannot_reach_three(facts):
    ms = rules.evaluate(facts(labs={"triglycerides": 90, "hdl": 60, "fasting_glucose": 88}))["assessments"]["metabolic_syndrome"]
    assert (ms["status"], ms["met"], ms["unknown"]) == ("ausente", 0, 2)


def test_metabolic_syndrome_uses_sex_specific_cut_offs(facts):
    data = {"anthropometry": {"waist_cm": 85}, "labs": {"hdl": 45, "triglycerides": 160}}
    assert rules.evaluate(facts(sex="M", **data))["assessments"]["metabolic_syndrome"]["status"] == "presente"
    assert rules.evaluate(facts(sex="H", **data))["assessments"]["metabolic_syndrome"]["met"] == 1


def test_known_conditions_count_as_treated_criteria(facts):
    result = rules.evaluate(facts(
        anthropometry={"waist_cm": 95},
        vitals={"systolic_bp": 118, "diastolic_bp": 76},
        conditions={"hypertension": True, "diabetes": True},
    ))

    ms = result["assessments"]["metabolic_syndrome"]
    assert ms["status"] == "presente"
    assert "Hipertensión registrada en la ficha" in [c["evidence"] for c in ms["criteria"]]
    assert result["assessments"]["glycemic_status"]["status"] == "diabetes_conocida"


def test_diabetes_range_alert_only_without_known_diabetes(facts):
    assert "GLU-01" in rule_ids(rules.evaluate(facts(labs={"hba1c": 6.8})))

    known = rules.evaluate(facts(labs={"hba1c": 7.4}, conditions={"diabetes": True}))
    assert "GLU-01" not in rule_ids(known)
    assert "GLU-03" in rule_ids(known)


def test_one_altered_indicator_is_possible_insulin_resistance(facts):
    result = rules.evaluate(facts(labs={"fasting_glucose": 90, "fasting_insulin": 14}))  # HOMA-IR 3,11

    assert result["assessments"]["insulin_resistance"]["status"] == "posible"
    assert next(f for f in result["findings"] if f["rule_id"] == "RI-01")["severity"] == "info"


def test_suggests_the_missing_data(facts):
    result = rules.evaluate(facts(labs={"fasting_glucose": 95}))
    missing = next(f for f in result["findings"] if f["rule_id"] == "DAT-01")

    assert "Solicitar insulina basal para calcular HOMA-IR." in missing["recommendation"]
    assert "Medir el perímetro de cintura." in missing["recommendation"]
    assert "Solicitar glicemia en ayunas." not in missing["recommendation"]
    assert result["assessments"]["insulin_resistance"]["status"] == "indeterminado"


def test_warns_that_cut_offs_are_for_adults(facts):
    assert "DAT-02" in rule_ids(rules.evaluate(facts(age=15)))
    assert "DAT-02" not in rule_ids(rules.evaluate(facts(age=None)))


def test_rule_ids_are_unique():
    ids = [r.id for r in rules.RULES]
    assert len(ids) == len(set(ids))


def test_ldl_goal_is_stricter_with_diabetes(facts):
    labs = {"ldl": 120}
    assert "LIP-02" not in rule_ids(rules.evaluate(facts(labs=labs)))

    finding = next(f for f in rules.evaluate(facts(labs=labs, conditions={"diabetes": True}))["findings"] if f["rule_id"] == "LIP-02")
    assert finding["title"] == "LDL sobre la meta para diabetes"
    assert finding["evidence"] == ["LDL 120 mg/dL (meta < 100)"]


def test_atherogenic_profile(facts):
    altered = rules.evaluate(facts(labs={"total_cholesterol": 230, "hdl": 38, "triglycerides": 200}))
    ap = altered["assessments"]["atherogenic_profile"]
    assert (ap["status"], ap["positive"], ap["evaluated"]) == ("alterado", 4, 4)
    assert "Colesterol no HDL 192 mg/dL (referencia < 160)" in ap["evidence"]
    assert finding_of(altered, "LIP-03")["severity"] == "warning"

    # AIP 0.2 (intermediate); Castelli I 3.33, Castelli II 1.6 and non-HDL 105 within range.
    borderline = rules.evaluate(facts(sex="M", labs={"total_cholesterol": 150, "hdl": 45, "triglycerides": 165}))
    assert borderline["assessments"]["atherogenic_profile"]["status"] == "limitrofe"
    assert finding_of(borderline, "LIP-03")["severity"] == "info"

    assert rules.evaluate(facts())["assessments"]["atherogenic_profile"]["status"] == "indeterminado"


def test_fatty_liver_finding(facts):
    def fli_finding(ggt):
        result = rules.evaluate(facts(
            anthropometry={"weight_kg": 90, "height_cm": 170, "waist_cm": 104},
            labs={"triglycerides": 200, "ggt": ggt},
        ))
        return finding_of(result, "HEP-01")

    assert fli_finding(60) == {
        "rule_id": "HEP-01", "severity": "warning", "title": "Esteatosis hepática probable (FLI)",
        "evidence": ["FLI 88,9 (esteatosis probable)"],
        "recommendation": "Considerar ecografía abdominal y perfil hepático; una baja de peso de 7 a 10 % mejora la esteatosis.",
    }
    assert fli_finding(3)["severity"] == "info"  # FLI 48.4: indeterminate
    assert fli_finding(1) is None                # FLI 29.8: rules out


def test_findrisc_finding(facts):
    high = rules.evaluate(facts(
        age=50, anthropometry={"weight_kg": 90, "height_cm": 170, "waist_cm": 104},
        risk_factors={**FINDRISC_ANSWERS, "daily_physical_activity": False, "family_history_diabetes": "first_degree"},
    ))
    finding = finding_of(high, "FIN-01")
    assert (finding["severity"], finding["title"]) == ("warning", "FINDRISC 16 pts: riesgo alto, 33 % a 10 años")
    assert finding["evidence"] == [
        "Edad: 2 pts", "IMC: 3 pts", "Cintura: 4 pts", "Actividad física diaria ≥ 30 min: 2 pts", "Familiares con diabetes: 5 pts",
    ]

    # 2 (age) + 0 (BMI 24.2) + 3 (waist) + 2 (physical activity) = 7.
    slight = rules.evaluate(facts(
        age=50, anthropometry={"weight_kg": 70, "height_cm": 170, "waist_cm": 95},
        risk_factors={**FINDRISC_ANSWERS, "daily_physical_activity": False},
    ))
    assert finding_of(slight, "FIN-01")["severity"] == "info"

    low = rules.evaluate(facts(age=30, anthropometry={"weight_kg": 60, "height_cm": 170, "waist_cm": 80}, risk_factors=FINDRISC_ANSWERS))
    assert "FIN-01" not in rule_ids(low)


def test_missing_findrisc_answers_only_when_it_applies(facts):
    def missing(**groups):
        dat = finding_of(rules.evaluate(facts(age=50, **groups)), "DAT-01")
        return [e for e in dat["evidence"] if "FINDRISC" in e] if dat else []

    assert missing(risk_factors={"daily_physical_activity": True}) == [
        "Falta dato para FINDRISC (verduras o frutas a diario, fármacos antihipertensivos, glicemia alta alguna vez, familiares con diabetes)"
    ]
    # Prediabetes answers the glucose question.
    assert "glicemia alta" not in missing(conditions={"prediabetes": True})[0]
    assert missing(conditions={"diabetes": True}) == []
    assert missing(risk_factors=FINDRISC_ANSWERS) == []
