import json
from pathlib import Path

import pytest

from app import meal_plan, thresholds as t

# The exchange list of database/seeders/FoodsSeeder.php, with glycemic index and saturated fat.
FOODS = json.loads((Path(__file__).parent / "fixtures" / "foods.json").read_text(encoding="utf-8"))
TARGETS = {"energy": 1750, "carbohydrates": 197, "proteins": 88, "fats": 68}
CFG = t.MEAL_PLAN


def plain(text):
    return meal_plan._plain(text)


def food(food_id):
    return next(f for f in FOODS if f["id"] == food_id)


@pytest.fixture(scope="module")
def plan():
    return meal_plan.generate(TARGETS, FOODS, seed=1)


@pytest.fixture(scope="module")
def week():
    return meal_plan.generate(TARGETS, FOODS, seed=1, days=7)


@pytest.mark.spec("US-3.1/AC-2")
def test_meets_the_targets_within_the_tolerance(plan):
    day = plan["days"][0]
    assert plan["status"] == "optimo"
    for key, tolerance in CFG["tolerance_percent"].items():
        if key != "meal_energy":
            assert abs(day["deviation_percent"][key]) <= tolerance + 0.3, key
    assert [m["key"] for m in day["meals"]] == [m["key"] for m in CFG["meals"]]


@pytest.mark.spec("US-3.1/AC-3")
def test_respects_the_guideline_constraints(week):
    for day in week["days"]:
        portions = {}
        for meal, cfg in zip(day["meals"], CFG["meals"]):
            groups = [plain(i["group"]) for i in meal["items"]]
            for required in cfg.get("required", []):
                assert {plain(g) for g in required} & set(groups), (day["day"], meal["key"], required)
            for group in set(groups):
                assert groups.count(group) <= meal_plan._by_group(CFG["foods_per_meal"], group), (meal["key"], group)
                in_group = sum(i["portions"] for i in meal["items"] if plain(i["group"]) == group)
                assert in_group <= meal_plan._by_group(CFG["group_portions_per_meal"], group), (day["day"], meal["key"], group)
            for item in meal["items"]:
                assert meal_plan.fits(food(item["food_id"]), cfg)
                assert item["portions"] * 2 == int(item["portions"] * 2)  # half portions
                assert item["portions"] >= meal_plan._by_group(CFG["min_portions"], plain(item["group"])), item
                portions[plain(item["group"])] = portions.get(plain(item["group"]), 0) + item["portions"]

        for group, (low, high) in CFG["daily_portions"].items():
            assert low <= portions.get(plain(group), 0) <= high, (day["day"], group)


@pytest.mark.spec("US-3.1/AC-3")
def test_realistic_meals(week):
    items = [(meal["key"], i) for day in week["days"] for meal in day["meals"] for i in meal["items"]]
    # One fruit per meal at most, two eggs at most, no crumbs of meat.
    assert all(i["portions"] <= 1 for _, i in items if i["group"] == "Frutas")
    assert all(i["portions"] <= 2 for _, i in items if i["name"] in ("Huevo", "Clara de Huevo"))
    assert all(i["portions"] >= 1 for _, i in items if i["group"] in ("Carnes", "Verduras"))


@pytest.mark.spec("US-3.1/AC-2")
def test_totals_add_up(plan):
    day = plan["days"][0]
    for key in ("energy", "carbohydrates", "proteins", "fats", "glycemic_load", "saturated_fat"):
        assert day["totals"][key] == pytest.approx(sum(m["totals"][key] for m in day["meals"]), abs=0.2)


@pytest.mark.spec("US-3.1/AC-1")
def test_grams_come_from_the_exchange_portion(plan):
    for item in [i for m in plan["days"][0]["meals"] for i in m["items"]]:
        source = food(item["food_id"])
        assert item["grams"] == (int(item["portions"] * source["grams"] + 0.5) if source["grams"] else None)  # half up


@pytest.mark.spec("US-3.1/AC-8")
def test_skips_foods_without_nutrients(plan):
    used = {i["food_id"] for m in plan["days"][0]["meals"] for i in m["items"]}
    assert not used & {f["id"] for f in FOODS if f["kcal"] == 0}
    assert "Mote Crudo" in plan["notes"][0]


@pytest.mark.spec("US-3.1/AC-5")
def test_another_seed_gives_another_menu_with_the_same_quality():
    one, other = meal_plan.generate(TARGETS, FOODS, seed=1), meal_plan.generate(TARGETS, FOODS, seed=7)
    foods = lambda p: [i["name"] for m in p["days"][0]["meals"] for i in m["items"]]  # noqa: E731

    assert foods(one) != foods(other)
    assert meal_plan.generate(TARGETS, FOODS, seed=1) == one  # reproducible


@pytest.mark.spec("US-3.1/AC-5")
def test_week_has_different_days_all_within_the_targets(week):
    assert [d["day"] for d in week["days"]] == list(range(1, 8))
    menus = [frozenset((m["key"], i["name"]) for m in d["meals"] for i in m["items"]) for d in week["days"]]
    assert len(set(menus)) == 7
    # The main dish of lunch changes along the week.
    lunches = [next((i["name"] for i in d["meals"][2]["items"] if i["group"] in ("Carnes", "Legumbres")), None) for d in week["days"]]
    assert len(set(lunches)) >= 4
    for day in week["days"]:
        assert abs(day["deviation_percent"]["energy"]) <= CFG["tolerance_percent"]["energy"] + 0.3


@pytest.mark.spec("US-3.1/AC-5")
def test_first_day_of_a_week_is_the_single_day_plan(plan, week):
    assert week["days"][0]["deviation_percent"] == plan["days"][0]["deviation_percent"]


@pytest.mark.spec("US-3.1/AC-2")
def test_flags_targets_out_of_reach():
    # 1200 kcal: the guideline minimums of fruit, vegetables and dairy leave no room.
    plan = meal_plan.generate({"energy": 1200, "carbohydrates": 120, "proteins": 75, "fats": 47}, FOODS)

    assert any("Fuera de la tolerancia" in n for n in plan["days"][0]["notes"])


@pytest.mark.spec("US-3.1/AC-2")
def test_without_matching_foods():
    plan = meal_plan.generate(TARGETS, [{"id": 1, "name": "Agua", "item": "Bebidas", "grams": 200, "kcal": 1, "protein": 0, "fat": 0, "cho": 0}])

    assert plan["status"] == "sin_alimentos"
    assert plan["days"] == []


@pytest.mark.spec("US-3.1/AC-4")
def test_respects_the_glycemic_load_and_saturated_fat_ceilings():
    default = meal_plan.generate(TARGETS, FOODS, seed=1)
    strict = meal_plan.generate(TARGETS, FOODS, seed=1, limits={"glycemic_load": 80, "saturated_fat": 14})
    day = strict["days"][0]

    assert default["limits"] == {"glycemic_load": t.GLYCEMIC_LOAD["base"], "saturated_fat": round(1750 * 0.10 / 9)}
    assert strict["limits"] == {"glycemic_load": 80, "saturated_fat": 14}
    assert day["totals"]["glycemic_load"] <= 80 * 1.02
    assert day["totals"]["saturated_fat"] <= 14 * 1.02


@pytest.mark.spec("US-3.1/AC-4")
def test_glycemic_load_of_a_portion(plan):
    bread = next(f for f in FOODS if f["name"] == "Pan Marraqueta")  # GI 75, 30 g of carbohydrates
    items = [i for m in plan["days"][0]["meals"] for i in m["items"]]

    assert meal_plan._with_quality(bread)["gl"] == pytest.approx(22.5)
    assert all(i["glycemic_load"] == 0 for i in items if i["group"] in ("Carnes", "Aceites"))


@pytest.mark.spec("US-3.1/AC-4")
def test_foods_without_glycemic_index_are_reported():
    foods = [{**f, "glycemic_index": None} if f["item"] == "Pan" else f for f in FOODS]
    plan = meal_plan.generate(TARGETS, foods, seed=1)
    day = plan["days"][0]
    used_bread = any(i["group"] == "Pan" for m in day["meals"] for i in m["items"])

    assert any("Sin índice glicémico" in n for n in day["notes"]) == used_bread


@pytest.fixture(scope="module")
def priced():
    from app import catalog

    return catalog.load().foods


@pytest.mark.spec("US-3.1/AC-7")
def test_reports_the_cost_of_each_food_and_day(priced):
    day = meal_plan.generate(TARGETS, priced, seed=1)["days"][0]
    items = [i for m in day["meals"] for i in m["items"]]
    prices = {f["id"]: f["price"] for f in priced}

    assert all(i["cost"] == round(i["portions"] * prices[i["food_id"]]) for i in items)
    assert day["cost"] == pytest.approx(sum(i["cost"] for i in items), abs=1)


@pytest.mark.spec("US-3.1/AC-7")
def test_without_prices_there_is_no_cost(plan):
    assert plan["days"][0]["cost"] is None
    assert plan["restrictions"]["budget"] is None


@pytest.mark.spec("US-3.1/AC-7")
def test_a_budget_lowers_the_cost_and_keeps_the_targets(priced):
    free = meal_plan.generate(TARGETS, priced, seed=1, days=3)
    budget = 5000
    tight = meal_plan.generate(TARGETS, priced, seed=1, days=3, budget=budget)

    assert tight["restrictions"]["budget"] == budget
    assert max(d["cost"] for d in free["days"]) > budget  # the budget binds
    for day in tight["days"]:
        assert day["cost"] <= budget * 1.01, day["day"]
        assert abs(day["deviation_percent"]["energy"]) <= CFG["tolerance_percent"]["energy"] + 0.3
        assert not any("presupuesto" in n for n in day["notes"])


@pytest.mark.spec("US-3.1/AC-7")
def test_a_budget_too_low_is_exceeded_as_little_as_possible(priced):
    plan = meal_plan.generate(TARGETS, priced, seed=1, budget=1000)
    day = plan["days"][0]

    assert day["cost"] > 1000
    assert any(n.startswith("Cuesta $") and "sobre el presupuesto de $1.000" in n for n in day["notes"])


@pytest.mark.spec("US-3.1/AC-7")
def test_with_a_budget_foods_without_price_are_left_out(priced):
    foods = [{**f, "price": None} if f["name"] == "Pollo" else f for f in priced]
    plan = meal_plan.generate(TARGETS, foods, seed=1, days=3, budget=8000)

    assert "Pollo" not in {i["name"] for d in plan["days"] for m in d["meals"] for i in m["items"]}
    assert any(n == "Sin precio, no se usan con presupuesto: Pollo." for n in plan["notes"])


@pytest.mark.spec("US-3.1/AC-6", "US-3.1/AC-7")
def test_allergies_and_budget_together(priced):
    plan = meal_plan.generate(TARGETS, priced, seed=1, days=2, budget=5500, allergies=["intolerancia a la lactosa"])

    for day in plan["days"]:
        assert day["cost"] <= 5500 * 1.01
        assert not [i for m in day["meals"] for i in m["items"] if "lactosa" in next(f for f in priced if f["id"] == i["food_id"])["allergens"]]
