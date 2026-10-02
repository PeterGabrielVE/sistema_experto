from app import rules


def rule_ids(result):
    return [f["rule_id"] for f in result["findings"]]


def test_patient_with_insulin_resistance_and_metabolic_syndrome(facts):
    result = rules.evaluate(facts(
        anthropometry={"weight_kg": 80, "height_cm": 165, "waist_cm": 98},
        vitals={"systolic_bp": 128, "diastolic_bp": 82},
        labs={
            "fasting_glucose": 105, "fasting_insulin": 18.2, "hba1c": 5.9,
            "total_cholesterol": 210, "hdl": 38, "ldl": 135, "triglycerides": 180,
        },
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
            "total_cholesterol": 170, "hdl": 62, "ldl": 95, "triglycerides": 80,
        },
    ))

    assert result["findings"] == []
    assert result["assessments"]["glycemic_status"]["status"] == "normal"
    assert result["assessments"]["insulin_resistance"]["status"] == "no_sugerida"
    assert result["assessments"]["metabolic_syndrome"]["status"] == "ausente"


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
