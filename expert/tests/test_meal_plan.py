import json
from pathlib import Path

import pytest

from app import meal_plan, thresholds as t

# The exchange list of database/seeders/FoodsSeeder.php.
FOODS = json.loads((Path(__file__).parent / "fixtures" / "foods.json").read_text(encoding="utf-8"))
TARGETS = {"energy": 1750, "carbohydrates": 197, "proteins": 88, "fats": 68}


def plain(text):
    return meal_plan._plain(text)


@pytest.fixture(scope="module")
def plan():
    return meal_plan.generate(TARGETS, FOODS, seed=1)


def test_meets_the_targets_within_the_tolerance(plan):
    assert plan["status"] == "optimo"
    for key, tolerance in t.MEAL_PLAN["tolerance_percent"].items():
        if key != "meal_energy":
            assert abs(plan["deviation_percent"][key]) <= tolerance, key
    assert [m["key"] for m in plan["meals"]] == [m["key"] for m in t.MEAL_PLAN["meals"]]


def test_respects_the_guideline_constraints(plan):
    portions = {}
    for meal, cfg in zip(plan["meals"], t.MEAL_PLAN["meals"]):
        groups = [plain(i["group"]) for i in meal["items"]]
        for required in cfg.get("required", []):
            assert {plain(g) for g in required} & set(groups), (meal["key"], required)
        for group in set(groups):
            limit = 2 if group == "verduras" else 1
            assert groups.count(group) <= limit, (meal["key"], group)
        assert all(meal_plan.fits(next(f for f in FOODS if f["id"] == i["food_id"]), cfg) for i in meal["items"])
        for item in meal["items"]:
            portions[plain(item["group"])] = portions.get(plain(item["group"]), 0) + item["portions"]
            assert item["portions"] * 2 == int(item["portions"] * 2)  # half portions

    for group, (low, high) in t.MEAL_PLAN["daily_portions"].items():
        assert low <= portions.get(plain(group), 0) <= high, group


def test_totals_add_up(plan):
    for key in ("energy", "carbohydrates", "proteins", "fats"):
        assert plan["totals"][key] == pytest.approx(sum(m["totals"][key] for m in plan["meals"]), abs=0.2)


def test_grams_come_from_the_exchange_portion(plan):
    items = [i for m in plan["meals"] for i in m["items"]]
    for item in items:
        food = next(f for f in FOODS if f["id"] == item["food_id"])
        assert item["grams"] == (round(item["portions"] * food["grams"]) if food["grams"] else None)


def test_skips_foods_without_nutrients(plan):
    used = {i["food_id"] for m in plan["meals"] for i in m["items"]}
    assert not used & {f["id"] for f in FOODS if f["kcal"] == 0}
    assert "Mote Crudo" in plan["notes"][0]


def test_another_seed_gives_another_menu_with_the_same_quality():
    one, other = meal_plan.generate(TARGETS, FOODS, seed=1), meal_plan.generate(TARGETS, FOODS, seed=7)
    foods = lambda p: [i["name"] for m in p["meals"] for i in m["items"]]  # noqa: E731

    assert foods(one) != foods(other)
    assert meal_plan.generate(TARGETS, FOODS, seed=1) == one  # reproducible


def test_flags_targets_out_of_reach():
    # 1200 kcal: the guideline minimums of fruit, vegetables and dairy leave no room.
    plan = meal_plan.generate({"energy": 1200, "carbohydrates": 120, "proteins": 75, "fats": 47}, FOODS)

    assert plan["status"] == "optimo"
    assert any("Fuera de la tolerancia" in n for n in plan["notes"])


def test_without_matching_foods():
    plan = meal_plan.generate(TARGETS, [{"id": 1, "name": "Agua", "item": "Bebidas", "grams": 200, "kcal": 1, "protein": 0, "fat": 0, "cho": 0}])

    assert plan["status"] == "sin_alimentos"


def test_respects_the_glycemic_load_and_saturated_fat_ceilings():
    default = meal_plan.generate(TARGETS, FOODS, seed=1)
    strict = meal_plan.generate(TARGETS, FOODS, seed=1, limits={"glycemic_load": 80, "saturated_fat": 14})

    assert default["limits"] == {"glycemic_load": t.GLYCEMIC_LOAD["base"], "saturated_fat": round(1750 * 0.10 / 9)}
    assert strict["limits"] == {"glycemic_load": 80, "saturated_fat": 14}
    assert strict["totals"]["glycemic_load"] <= 80 * 1.02
    assert strict["totals"]["saturated_fat"] <= 14 * 1.02
    for key in ("energy", "carbohydrates", "proteins", "fats"):
        assert abs(strict["deviation_percent"][key]) <= t.MEAL_PLAN["tolerance_percent"][key]


def test_glycemic_load_of_a_portion():
    bread = next(f for f in FOODS if f["name"] == "Pan Marraqueta")  # GI 75, 30 g of carbohydrates
    plan = meal_plan.generate(TARGETS, FOODS, seed=1)
    items = [i for m in plan["meals"] for i in m["items"]]

    assert meal_plan._with_quality(bread)["gl"] == pytest.approx(22.5)
    assert all(i["glycemic_load"] == 0 for i in items if i["group"] in ("Carnes", "Aceites"))


def test_foods_without_glycemic_index_are_reported():
    foods = [{**f, "glycemic_index": None} if f["item"] == "Pan" else f for f in FOODS]
    plan = meal_plan.generate(TARGETS, foods, seed=1)
    used_bread = any(i["group"] == "Pan" for m in plan["meals"] for i in m["items"])

    assert any("Sin índice glicémico" in n for n in plan["notes"]) == used_bread
