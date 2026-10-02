import pytest

from app import indices


def test_same_values_as_the_laravel_app():
    # tests/Feature/LabResultTest.php: 105 mg/dL, 18,2 µU/mL, TG 180, HDL 38; 80 kg and 165 cm.
    assert indices.homa_ir(105, 18.2) == 4.72
    assert indices.tyg(180, 105) == 9.15
    assert indices.tg_hdl(180, 38) == 4.74
    assert indices.bmi(80, 165) == 29.4


def test_rounds_half_up_like_php():
    # 0.125 is exact in binary: Python's round() would give 0.12.
    assert indices._round(0.125, 2) == 0.13
    assert indices.waist_to_height(85, 170) == 0.5


@pytest.mark.parametrize(("bmi", "category"), [(18.4, 1), (18.5, 2), (24.9, 2), (25, 3), (29.9, 3), (30, 4)])
def test_bmi_categories_match_the_rules_table(bmi, category):
    assert indices.bmi_category(bmi) == category


@pytest.mark.parametrize(
    ("systolic", "diastolic", "category"),
    [
        (115, 75, "Normal"),
        (125, 75, "Elevada"),
        (132, 78, "Hipertensión etapa 1"),
        (118, 82, "Hipertensión etapa 1"),
        (145, 85, "Hipertensión etapa 2"),
    ],
)
def test_blood_pressure_categories(systolic, diastolic, category):
    assert indices.blood_pressure_category(systolic, diastolic) == category


def test_friedewald_only_below_400_mg_dl_of_triglycerides():
    assert indices.ldl_friedewald(210, 38, 180) == 136
    assert indices.ldl_friedewald(210, 38, 400) is None


def test_missing_values_give_no_index():
    assert indices.homa_ir(105, None) is None
    assert indices.tyg(None, 105) is None
    assert indices.blood_pressure_category(None, 80) is None


def test_compute_reports_only_available_indices(facts):
    result = indices.compute(facts(
        labs={"fasting_glucose": 105, "fasting_insulin": 18.2},
        anthropometry={"weight_kg": 80, "height_cm": 165},
    ))

    assert set(result) == {"bmi", "homa_ir"}
    assert result["homa_ir"] == {"label": "HOMA-IR", "value": 4.72, "unit": "", "reference": "≤ 2,5", "high": True}
    assert result["bmi"]["category"] == "Sobrepeso"


def test_calculated_ldl_only_when_the_lab_did_not_report_it(facts):
    profile = {"total_cholesterol": 210, "hdl": 38, "triglycerides": 180}
    assert indices.compute(facts(labs=profile))["ldl_friedewald"]["value"] == 136
    assert "ldl_friedewald" not in indices.compute(facts(labs={**profile, "ldl": 135}))


def test_atherogenic_indices():
    assert indices.castelli_i(230, 38) == 6.05
    assert indices.castelli_ii(152, 38) == 4.0
    # log10((200 / 88.57) / (38 / 38.67)) = 0.361
    assert indices.aip(200, 38) == 0.36
    assert indices.aip(80, 62) == -0.25


@pytest.mark.parametrize(
    ("value", "risk"),
    [(0.1, "Riesgo bajo"), (0.11, "Riesgo intermedio"), (0.21, "Riesgo intermedio"), (0.22, "Riesgo alto")],
)
def test_aip_risk(value, risk):
    assert indices.aip_risk(value) == risk


def test_castelli_ii_uses_friedewald_when_the_lab_did_not_report_ldl(facts):
    result = indices.compute(facts(labs={"total_cholesterol": 230, "hdl": 38, "triglycerides": 200}))

    assert result["ldl_friedewald"]["value"] == 152
    assert result["castelli_ii"]["value"] == 4.0
    assert result["castelli_i"]["high"] is True
    assert result["aip"]["category"] == "Riesgo alto"


def test_castelli_cut_offs_depend_on_sex(facts):
    labs = {"total_cholesterol": 190, "hdl": 40}  # 4.75
    assert indices.compute(facts(sex="H", labs=labs))["castelli_i"]["high"] is False
    assert indices.compute(facts(sex="M", labs=labs))["castelli_i"]["high"] is True


def test_fatty_liver_index():
    # y = 0.953 ln 200 + 0.139 × 31.14 + 0.718 ln 60 + 0.053 × 104 − 15.745 = 2.08
    assert indices.fli(200, 90, 170, 60, 104) == 88.9
    assert indices.fli(80, 58, 162, 18, 72) < 30
    assert indices.fli(200, 90, 170, None, 104) is None


@pytest.mark.parametrize(
    ("value", "category"),
    [(29.9, "Esteatosis descartada"), (30, "Zona indeterminada"), (59.9, "Zona indeterminada"), (60, "Esteatosis probable")],
)
def test_fli_categories(value, category):
    assert indices.fli_category(value) == category


ANSWERS = {
    "daily_physical_activity": False,
    "daily_fruit_vegetables": True,
    "antihypertensive_medication": True,
    "high_glucose_history": False,
    "family_history_diabetes": "first_degree",
}


def test_findrisc(facts):
    patient = facts(age=50, anthropometry={"weight_kg": 90, "height_cm": 170, "waist_cm": 104}, risk_factors=ANSWERS)

    assert indices.findrisc_points(patient) == {
        "age": 2, "bmi": 3, "waist": 4, "physical_activity": 2, "fruit_vegetables": 0,
        "antihypertensive_medication": 2, "high_glucose_history": 0, "family_history_diabetes": 5,
    }
    assert indices.findrisc(patient) == 18
    result = indices.compute(patient)["findrisc"]
    assert (result["value"], result["unit"], result["high"]) == (18, "pts", True)
    assert result["category"] == "Riesgo alto, 33 % a 10 años"


@pytest.mark.parametrize(("age", "points"), [(44, 0), (45, 2), (54, 2), (55, 3), (64, 3), (65, 4)])
def test_findrisc_age_points(facts, age, points):
    assert indices.findrisc_points(facts(age=age))["age"] == points


# 170 cm: BMI 24.9, 25.0, 30.0 and 30.1.
@pytest.mark.parametrize(("weight", "points"), [(72, 0), (72.3, 1), (86.7, 1), (87, 3)])
def test_findrisc_bmi_points(facts, weight, points):
    assert indices.findrisc_points(facts(anthropometry={"weight_kg": weight, "height_cm": 170}))["bmi"] == points


@pytest.mark.parametrize(
    ("sex", "waist", "points"),
    [("H", 93, 0), ("H", 94, 3), ("H", 102, 3), ("H", 103, 4), ("M", 79, 0), ("M", 80, 3), ("M", 88, 3), ("M", 89, 4)],
)
def test_findrisc_waist_points(facts, sex, waist, points):
    assert indices.findrisc_points(facts(sex=sex, anthropometry={"waist_cm": waist}))["waist"] == points


@pytest.mark.parametrize(
    ("score", "label"),
    [(0, "Bajo"), (6, "Bajo"), (7, "Levemente elevado"), (11, "Levemente elevado"), (12, "Moderado"),
     (15, "Alto"), (20, "Alto"), (21, "Muy alto"), (26, "Muy alto")],
)
def test_findrisc_risk_bands(score, label):
    assert indices.findrisc_risk(score)["label"] == label


def test_prediabetes_answers_the_high_glucose_question(facts):
    patient = facts(conditions={"prediabetes": True}, risk_factors={**ANSWERS, "high_glucose_history": None})
    assert indices.findrisc_points(patient)["high_glucose_history"] == 5


def test_findrisc_needs_every_answer_and_does_not_apply_to_diabetes_or_minors(facts):
    complete = {"anthropometry": {"weight_kg": 90, "height_cm": 170, "waist_cm": 104}, "risk_factors": ANSWERS}

    assert indices.findrisc(facts(age=50, **complete)) == 18
    assert indices.findrisc(facts(age=50, **{**complete, "risk_factors": {**ANSWERS, "daily_fruit_vegetables": None}})) is None
    assert indices.findrisc(facts(age=None, **complete)) is None
    assert indices.findrisc(facts(age=50, conditions={"diabetes": True}, **complete)) is None
    assert indices.findrisc(facts(age=16, **complete)) is None
