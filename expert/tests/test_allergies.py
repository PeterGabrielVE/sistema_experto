import pytest

from app import allergies, catalog, meal_plan

TARGETS = {"energy": 1750, "carbohydrates": 197, "proteins": 88, "fats": 68}


@pytest.fixture(scope="module")
def foods():
    return catalog.load().foods


def used(plan):
    return [i for day in plan["days"] for meal in day["meals"] for i in meal["items"]]


@pytest.mark.parametrize(
    "text, tags",
    [
        (["gluten"], ["gluten"]),
        (["frutos_secos"], ["frutos_secos"]),
        (["Alergia al maní"], ["mani"]),
        (["Intolerancia a la lactosa y alergia a los mariscos"], ["lactosa", "mariscos"]),
        (["Celíaca; APLV"], ["gluten", "leche"]),
        (["nueces, almendras / huevo"], ["huevo", "frutos_secos"]),
        (["ALERGIA A PESCADOS O MARISCOS."], ["pescado", "mariscos"]),
    ],
)
def test_recognizes_allergens_in_free_text(foods, text, tags):
    restrictions = allergies.parse(text, foods)

    assert restrictions.allergens == tags
    assert restrictions.unrecognized == []


def test_food_names_and_unknown_allergies(foods):
    restrictions = allergies.parse(["Alergia a los kiwis", "polen", "no", ""], foods)

    assert restrictions.allergens == []
    assert restrictions.words == ["kiwi"]
    assert restrictions.unrecognized == ["polen"]


def test_excludes_tagged_foods_and_named_foods(foods):
    kept, excluded, _ = allergies.exclude(foods, ["gluten", "kiwi"])
    names = {f["name"] for f in excluded}

    assert {"Pan Marraqueta", "Pan Molde", "Avena Cruda", "Galletas Integrales", "Carne Vegetal", "Kiwi"} <= names
    assert not {"Quinoa Cruda", "Papas Cocidas", "Pollo"} & names
    assert len(kept) + len(excluded) == len(foods)


def test_milk_allergy_keeps_soy_milk(foods):
    _, excluded, _ = allergies.exclude(foods, ["alergia a la leche"])
    names = {f["name"] for f in excluded}

    assert "Yogurt Natural o Diet" in names
    assert "Leche de Soya" not in names


@pytest.mark.parametrize("entries", [["gluten"], ["intolerancia a la lactosa", "alergia al huevo"], ["frutos secos", "maní", "pescado", "mariscos", "soya"]])
def test_menus_never_use_excluded_foods(foods, entries):
    plan = meal_plan.generate(TARGETS, foods, seed=1, days=3, allergies=entries)
    _, excluded, _ = allergies.exclude(foods, entries)

    assert plan["status"] in ("optimo", "factible")
    assert not {i["food_id"] for i in used(plan)} & {f["id"] for f in excluded}
    assert plan["restrictions"]["excluded_foods"] == sorted({f["name"] for f in excluded})
    assert any(n.startswith("Excluidos por alergias") for n in plan["notes"])


def test_gluten_free_menu_still_has_starches(foods):
    plan = meal_plan.generate(TARGETS, foods, seed=1, allergies=["celiaquía"])
    day = plan["days"][0]

    assert abs(day["deviation_percent"]["energy"]) <= 3.3
    assert any(i["group"] in ("Cereales", "Legumbres") for m in day["meals"] for i in m["items"])
    # Breakfast asks for bread or cereals, and every one of them has gluten.
    assert "Desayuno: no hay alimentos permitidos de Pan o Cereales; se arma sin ellos." in plan["notes"]


def test_reports_allergies_it_cannot_guarantee(foods):
    plan = meal_plan.generate(TARGETS, foods, seed=1, allergies=["polen"])

    assert plan["restrictions"]["unrecognized"] == ["polen"]
    assert any("No se reconoce" in n and "polen" in n for n in plan["notes"])


def test_without_any_allowed_food(foods):
    everything = sorted({f["name"] for f in foods})
    plan = meal_plan.generate(TARGETS, foods, allergies=everything)

    assert plan["status"] == "sin_alimentos"
    assert plan["days"] == []
