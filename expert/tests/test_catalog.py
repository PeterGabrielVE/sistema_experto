import os

import pytest

from app import catalog, meal_plan

HEADER = "id,name,group,group_id,portion,grams,kcal,protein,fat,saturated_fat,cho,glycemic_index,sodium_mg,potassium_mg,phosphorus_mg,calcium_mg\n"


def rows(*lines):
    return HEADER + "\n".join(lines) + "\n"


@pytest.fixture(scope="module")
def shared():
    return catalog.load()


def test_loads_the_shared_catalog(shared):
    ids = [f["id"] for f in shared.foods]
    assert len(ids) == len(set(ids)) > 60
    assert set(shared.groups()) == {"Carnes", "Pan", "Cereales", "Verduras", "Frutas", "Legumbres", "Lácteos", "Aceites"}
    marraqueta = next(f for f in shared.foods if f["name"] == "Pan Marraqueta")
    assert marraqueta | {"id": 12, "item": "Pan", "grams": 50, "kcal": 140, "cho": 30, "glycemic_index": 75} == marraqueta
    arvejas = next(f for f in shared.foods if f["name"] == "Arvejas Cocidas")
    assert arvejas["sodium_mg"] == 7.5
    # Unknown minerals and grams are null, not 0.
    assert next(f for f in shared.foods if f["name"] == "Pollo")["sodium_mg"] is None
    assert next(f for f in shared.foods if f["name"] == "Aceites")["grams"] is None


def test_the_shared_catalog_has_no_duplicates_or_empty_foods(shared):
    assert not [w for w in shared.warnings if "repetido" in w or "Sin energía" in w]
    assert all(f["kcal"] > 0 for f in shared.foods)


def test_reports_doubtful_data(shared):
    energy = next(w for w in shared.warnings if w.startswith("Energía"))
    assert "Queso Mantecoso o Chanco (80 declaradas, 24 calculadas)" in energy


def test_meal_plan_from_the_shared_catalog(shared):
    plan = meal_plan.generate({"energy": 1750, "carbohydrates": 197, "proteins": 88, "fats": 68}, shared.foods, seed=1)
    assert plan["status"] == "optimo"
    ids = {f["id"] for f in shared.foods}
    assert {i["food_id"] for meal in plan["days"][0]["meals"] for i in meal["items"]} <= ids


def test_parse_accepts_decimal_comma_quotes_and_blank_cells():
    foods = catalog.parse(rows('7,"Atún, en agua",Carnes,4,1,60,65,11,2,"0,3",1,,,,,'))
    assert foods == [{
        "id": 7, "name": "Atún, en agua", "item": "Carnes", "grams": 60.0, "kcal": 65.0, "protein": 11.0, "fat": 2.0,
        "saturated_fat": 0.3, "cho": 1.0, "glycemic_index": None, "group_id": 4, "portion": "1",
        "sodium_mg": None, "potassium_mg": None, "phosphorus_mg": None, "calcium_mg": None,
    }]


def test_parse_lists_every_bad_row():
    with pytest.raises(catalog.CatalogError) as error:
        catalog.parse(rows(
            "1,Pollo,Carnes,4,1,50,sesenta,11,2,0.5,1,,,,,",
            "2,Pavo,Carnes,4,1,50,65,11,2,3,1,,,,,",  # more saturated fat than fat
            "1,Cerdo,Carnes,4,1,50,65,11,2,0.7,1,,,,,",
            "3,Huevo,Carnes,4,1,50,65,11,2,0.7,1,,,,,,extra",
        ))
    errors = error.value.errors
    assert len(errors) == 3
    assert errors[0].startswith("Línea 2 (Pollo): kcal:")
    assert errors[1].startswith("Línea 3 (Pavo): ") and "saturated_fat cannot exceed fat" in errors[1]
    assert errors[2] == "Línea 5: más celdas que columnas."


def test_parse_reports_repeated_ids():
    with pytest.raises(catalog.CatalogError, match="id 1 repetido"):
        catalog.parse(rows("1,Pollo,Carnes,4,1,50,65,11,2,0.5,1,,,,,", "1,Cerdo,Carnes,4,1,50,65,11,2,0.7,1,,,,,"))


def test_parse_checks_the_columns():
    with pytest.raises(catalog.CatalogError) as error:
        catalog.parse("id,name,group,group_id,kcal,protein,fat,glicemic_index\n1,Pollo,Carnes,4,65,11,2,\n")
    assert error.value.errors == ["Columna desconocida: glicemic_index.", "Falta la columna cho."]


def test_check_warns_about_duplicates_empty_foods_and_unknown_groups():
    foods = catalog.parse(rows(
        "1,Zanahoria,Verduras,2,1,50,60,4,0,0,14,39,,,,",
        "2,zanahoria,Verduras,2,1,50,60,4,0,0,14,39,,,,",
        "3,Papas Cocidas,Cereales,3,1,150,0,0,0,0,0,,,,,",
        "4,Galletón,Snacks,9,1,30,140,3,1,0.5,30,,,,,",
    ))
    warnings = catalog.check(foods)
    assert "Zanahoria (Verduras) está repetido: ids 1, 2." in warnings
    assert "Sin energía, el generador no los usa: Papas Cocidas." in warnings
    assert "Sin índice glicémico, no suman a la carga glucémica: Galletón." in warnings
    assert "El grupo Snacks no está en las pautas del generador (meal_plan): sus alimentos no se usan." in warnings
    assert "No hay alimentos de Frutas, grupo diario de las pautas." in warnings


def test_load_reads_again_when_the_file_changes(tmp_path):
    path = tmp_path / "foods.csv"
    path.write_text("﻿" + rows("1,Pollo,Carnes,4,1,50,65,11,2,0.5,1,,,,,"), encoding="utf-8")
    assert [f["name"] for f in catalog.load(path).foods] == ["Pollo"]
    assert catalog.load(path) is catalog.load(path)

    path.write_text(rows("1,Pavo,Carnes,4,1,50,65,11,2,0.5,1,,,,,"), encoding="utf-8")
    stat = path.stat()
    os.utime(path, ns=(stat.st_atime_ns, stat.st_mtime_ns + 1_000_000))
    assert [f["name"] for f in catalog.load(path).foods] == ["Pavo"]


def test_load_without_file(tmp_path):
    with pytest.raises(catalog.CatalogError, match="No se encontró"):
        catalog.load(tmp_path / "missing.csv")
