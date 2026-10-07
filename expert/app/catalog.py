"""Food composition catalog: the exchange portions of shared/food_catalog.csv.

The same file is imported by Laravel into the foods table (php artisan foods:import), so the
menus generated with the service's own catalog and the doctor's editor use the same foods and
ids. Loaded on demand and kept until the file changes. A malformed row stops the load
(CatalogError, with every row at fault); valid but doubtful data (repeated names, energy that does
not match the macronutrients…) goes to the warnings, for the nutritionist to review.
"""

from __future__ import annotations

import csv
import io
from collections import Counter, defaultdict
from dataclasses import dataclass
from pathlib import Path

from pydantic import ValidationError

from . import thresholds as t
from .config import get_settings
from .meal_plan import _plain
from .schemas import CatalogFood

# CSV column -> CatalogFood field; the rest are named alike.
RENAMED = {"group": "item"}
REQUIRED = ["id", "name", "group", "group_id", "kcal", "protein", "fat", "cho"]
TEXT = {"name", "item", "portion"}
# kcal per gram of carbohydrates, proteins and fats; a portion further than this from its own
# declared energy is reported.
ATWATER = {"cho": 4, "protein": 4, "fat": 9}
ATWATER_TOLERANCE = 0.20


class CatalogError(ValueError):
    def __init__(self, errors: list[str]):
        super().__init__("; ".join(errors))
        self.errors = errors


@dataclass(frozen=True)
class Catalog:
    foods: list[dict]
    warnings: list[str]

    def groups(self) -> dict[str, int]:
        return dict(Counter(f["item"] for f in self.foods))


def parse(text: str) -> list[dict]:
    """Rows of the catalog as CatalogFood dicts. Empty cells are unknown values (null) and a
    decimal comma is accepted (7,5). Raises CatalogError listing every bad row."""
    reader = csv.DictReader(io.StringIO(text))
    header = [h.strip() for h in reader.fieldnames or []]
    fields = set(CatalogFood.model_fields)
    errors = [f"Columna desconocida: {h}." for h in header if RENAMED.get(h, h) not in fields]
    errors += [f"Falta la columna {c}." for c in REQUIRED if c not in header]
    if errors:
        raise CatalogError(errors)

    foods, ids = [], {}
    for line, row in enumerate(reader, start=2):
        values = {}
        if None in row:
            errors.append(f"Línea {line}: más celdas que columnas.")
            continue
        for column, value in row.items():
            value = (value or "").strip()
            if not value:  # unknown value
                continue
            field = RENAMED.get(column.strip(), column.strip())
            values[field] = value if field in TEXT else value.replace(",", ".")
        if not values:
            continue  # blank line
        try:
            food = CatalogFood.model_validate(values).model_dump()
        except ValidationError as e:
            name = values.get("name", "?")
            errors += [f"Línea {line} ({name}): {'.'.join(map(str, err['loc'])) or 'fila'}: {err['msg']}." for err in e.errors()]
            continue
        if food["id"] in ids:
            errors.append(f"Línea {line} ({food['name']}): id {food['id']} repetido (línea {ids[food['id']]}).")
            continue
        ids[food["id"]] = line
        foods.append(food)

    if errors:
        raise CatalogError(errors)
    if not foods:
        raise CatalogError(["El catálogo no tiene alimentos."])
    return foods


def check(foods: list[dict]) -> list[str]:
    """Valid but doubtful data, for the nutritionist. The generator still uses these foods
    (except those without energy)."""
    warnings = []

    by_name = defaultdict(list)
    for f in foods:
        by_name[(_plain(f["item"]), _plain(f["name"]))].append(f)
    for same in by_name.values():
        if len(same) > 1:
            warnings.append(f"{same[0]['name']} ({same[0]['item']}) está repetido: ids {', '.join(str(f['id']) for f in same)}.")

    empty = [f["name"] for f in foods if f["kcal"] <= 0]
    if empty:
        warnings.append(f"Sin energía, el generador no los usa: {', '.join(empty)}.")

    off = []
    for f in foods:
        computed = sum(f[k] * kcal for k, kcal in ATWATER.items())
        if f["kcal"] > 0 and abs(computed - f["kcal"]) > f["kcal"] * ATWATER_TOLERANCE:
            off.append(f"{f['name']} ({f['kcal']:g} declaradas, {computed:g} calculadas)")
    if off:
        warnings.append(f"Energía que no calza con los macronutrientes (4/4/9 kcal/g, ±{ATWATER_TOLERANCE:.0%}): {'; '.join(off)}.")

    no_gi = [f["name"] for f in foods if f["glycemic_index"] is None and f["cho"] >= 5]
    if no_gi:
        warnings.append(f"Sin índice glicémico, no suman a la carga glucémica: {', '.join(no_gi)}.")

    cfg = t.MEAL_PLAN
    known = {_plain(g) for g in cfg["daily_portions"]} | {_plain(i) for meal in cfg["meals"] for i in meal["items"]}
    groups = {_plain(f["item"]): f["item"] for f in foods}
    for plain, group in groups.items():
        if plain not in known:
            warnings.append(f"El grupo {group} no está en las pautas del generador (meal_plan): sus alimentos no se usan.")
    for group, (lo, _) in cfg["daily_portions"].items():
        if lo > 0 and _plain(group) not in groups:
            warnings.append(f"No hay alimentos de {group}, grupo diario de las pautas.")
    return warnings


_cache: dict[Path, tuple[int, Catalog]] = {}


def load(path: Path | None = None) -> Catalog:
    """The catalog of FOOD_CATALOG_PATH (default shared/food_catalog.csv), read again when the
    file changes. Raises CatalogError when it is missing or malformed."""
    path = path or get_settings().food_catalog_path
    try:
        mtime = path.stat().st_mtime_ns
    except OSError:
        raise CatalogError([f"No se encontró el catálogo de alimentos ({path})."]) from None
    cached = _cache.get(path)
    if cached and cached[0] == mtime:
        return cached[1]
    # utf-8-sig: spreadsheets save the CSV with a BOM.
    foods = parse(path.read_text(encoding="utf-8-sig"))
    catalog = Catalog(foods, check(foods))
    _cache[path] = (mtime, catalog)
    return catalog
