import pytest

from app import nutrition, rules


def plan(facts, **kw):
    return rules.evaluate(facts(**kw))["macronutrients"]


def rule_ids(result):
    return [r["rule_id"] for r in result["rules"]]


def test_bmr_mifflin_st_jeor():
    assert nutrition.bmr("H", 40, 80, 165) == pytest.approx(1636.25)
    assert nutrition.bmr("M", 40, 80, 165) == pytest.approx(1470.25)


def test_reference_weight_caps_at_bmi_25():
    assert nutrition.reference_weight(60, 170) == 60
    assert nutrition.reference_weight(110, 170) == 72.2


def test_healthy_adult_gets_the_base_distribution(facts):
    result = plan(facts, sex="M", age=30, physical_activity=2, anthropometry={"weight_kg": 58, "height_cm": 162})

    assert result["status"] == "calculado"
    assert result["energy"] == {
        "bmr": 1282, "activity_level": "Moderada", "activity_factor": 1.55,
        "maintenance": 1986, "adjustment": 0, "target": 1990,
    }
    assert {k: m["percent"] for k, m in result["macros"].items()} == {"carbohydrates": 50, "proteins": 20, "fats": 30}
    assert result["macros"]["carbohydrates"]["grams"] == 249
    assert result["limits"]["saturated_fat"]["percent"] == 10
    assert result["limits"]["sodium"]["amount"] == 2000
    assert result["rules"] == [] and result["notes"] == []


def test_metabolic_patient_lowers_carbohydrates_and_saturated_fat(facts):
    result = plan(
        facts, physical_activity=1,
        anthropometry={"weight_kg": 80, "height_cm": 165, "waist_cm": 98},
        vitals={"systolic_bp": 128, "diastolic_bp": 82},
        labs={"fasting_glucose": 105, "fasting_insulin": 18.2, "hba1c": 5.9, "total_cholesterol": 210,
              "hdl": 38, "ldl": 135, "triglycerides": 180, "ggt": 45},
    )

    assert rule_ids(result) == ["MAC-01", "MAC-04", "MAC-05", "MAC-06", "MAC-07", "MAC-08"]
    assert (result["energy"]["adjustment"], result["energy"]["target"]) == (-500, 1750)
    assert {k: m["percent"] for k, m in result["macros"].items()} == {"carbohydrates": 45, "proteins": 20, "fats": 35}
    assert result["limits"]["saturated_fat"]["percent"] == 7
    assert result["limits"]["added_sugar"]["percent"] == 5
    assert result["limits"]["sodium"]["amount"] == 1500
    assert result["macros"]["proteins"]["g_per_kg"] >= result["protein_min_g_per_kg"] == 1.2


def test_diabetes_takes_the_lowest_carbohydrate_share(facts):
    result = plan(facts, sex="M", age=70, physical_activity=0,
                  anthropometry={"weight_kg": 95, "height_cm": 155}, conditions={"diabetes": True})

    assert {"MAC-01", "MAC-03", "MAC-09"} <= set(rule_ids(result))
    assert {k: m["percent"] for k, m in result["macros"].items()} == {"carbohydrates": 40, "proteins": 25, "fats": 35}
    # 1689 - 750 kcal is below the minimum for women.
    assert result["energy"]["target"] == 1200
    assert any("mínimo de 1200" in n for n in result["notes"])
    assert any("renal" in n for n in result["notes"])


def test_protein_is_raised_to_the_minimum_g_per_kg(facts):
    # Obesity: 1,2 g/kg × 81 kg of reference weight needs 22 % of 1810 kcal; it comes from carbohydrates.
    result = plan(facts, age=40, physical_activity=0, anthropometry={"weight_kg": 120, "height_cm": 180})

    assert result["energy"]["target"] == 1810
    assert result["reference_weight_kg"] == 81
    assert {k: m["percent"] for k, m in result["macros"].items()} == {"carbohydrates": 48, "proteins": 22, "fats": 30}
    assert result["macros"]["proteins"]["g_per_kg"] >= 1.2


def test_underweight_gets_a_surplus(facts):
    result = plan(facts, sex="M", age=25, physical_activity=1, anthropometry={"weight_kg": 45, "height_cm": 165})

    assert rule_ids(result) == ["MAC-02"]
    assert result["energy"]["adjustment"] == 400


def test_missing_activity_assumes_very_light(facts):
    result = plan(facts, anthropometry={"weight_kg": 70, "height_cm": 175})

    assert result["energy"]["activity_factor"] == 1.2
    assert any("actividad física" in n for n in result["notes"])


def test_no_plan_for_minors_or_without_anthropometry(facts):
    minor = plan(facts, age=15, anthropometry={"weight_kg": 50, "height_cm": 160})
    assert (minor["status"], "energy" in minor) == ("no_aplica", False)

    assert plan(facts, anthropometry={"weight_kg": 70})["status"] == "indeterminado"


HOMA_RULE = {
    "id": "CFG-1", "title": "Resistencia a la insulina por HOMA-IR", "advice": "Preferir alimentos de bajo índice glicémico.",
    "variable": "homa_ir", "operator": ">", "value": 2.5, "actions": {"glycemic_load": 80, "carbohydrates": 42},
}


def evaluate_with(facts, macro_rules, **kw):
    return rules.evaluate({**facts(**kw), "macro_rules": macro_rules})["macronutrients"]


def test_configured_rule_adjusts_the_glycemic_load(facts):
    result = evaluate_with(facts, [HOMA_RULE], physical_activity=2,
                           anthropometry={"weight_kg": 70, "height_cm": 175},
                           labs={"fasting_glucose": 100, "fasting_insulin": 15})  # HOMA-IR 3,7

    applied = result["rules"][-1]
    assert applied == {
        "rule_id": "CFG-1", "title": "Resistencia a la insulina por HOMA-IR", "configured": True,
        "evidence": ["HOMA-IR 3,7 (> 2,5)"],
        "advice": "Preferir alimentos de bajo índice glicémico. (Carbohidratos máximo 42 %, carga glucémica máxima 80 por día)",
    }
    assert result["limits"]["glycemic_load"]["amount"] == 80
    assert result["macros"]["carbohydrates"]["percent"] == 42
    assert all(not r["configured"] for r in result["rules"][:-1])


def test_configured_rule_does_not_apply_below_the_value_or_without_data(facts):
    below = evaluate_with(facts, [HOMA_RULE], anthropometry={"weight_kg": 70, "height_cm": 175},
                          labs={"fasting_glucose": 85, "fasting_insulin": 6})  # HOMA-IR 1,26
    missing = evaluate_with(facts, [HOMA_RULE], anthropometry={"weight_kg": 70, "height_cm": 175})

    for result in (below, missing):
        assert "CFG-1" not in rule_ids(result)
        assert result["limits"]["glycemic_load"]["amount"] == 120


def test_the_most_restrictive_value_wins(facts):
    # Diabetes (MAC-03) lowers the glycemic load to 100 and carbohydrates to 40; the rule cannot raise them.
    lenient = {**HOMA_RULE, "id": "CFG-2", "variable": "age", "operator": ">=", "value": 18,
               "actions": {"glycemic_load": 140, "carbohydrates": 60, "sodium_mg": 1800}}
    result = evaluate_with(facts, [lenient], anthropometry={"weight_kg": 70, "height_cm": 175}, conditions={"diabetes": True})

    assert result["limits"]["glycemic_load"]["amount"] == 100
    assert result["macros"]["carbohydrates"]["percent"] == 40
    assert result["limits"]["sodium"]["amount"] == 1800
    assert result["rules"][-1]["evidence"] == ["Edad 40 años (≥ 18)"]


def test_configured_lab_variable_and_energy(facts):
    rule = {"id": "CFG-3", "title": "TG muy altos", "variable": "triglycerides", "operator": ">=", "value": 500,
            "actions": {"energy": -200, "fats": 25}}
    result = evaluate_with(facts, [rule], physical_activity=2,
                           anthropometry={"weight_kg": 70, "height_cm": 175}, labs={"triglycerides": 600})

    assert result["energy"]["adjustment"] == -200
    assert result["rules"][-1]["advice"] == "Grasas 25 %, ajuste de energía -200 kcal/día"
