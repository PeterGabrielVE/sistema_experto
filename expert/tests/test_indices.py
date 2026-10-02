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
