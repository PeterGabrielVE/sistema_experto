"""Daily meal plan by mixed-integer linear programming (scipy.optimize.milp, HiGHS).

Decides how many half exchange portions go in each meal so the day meets the energy and
macronutrient targets of the consultation. Hard constraints come from the dietary guidelines
(shared/clinical_thresholds.json, meal_plan): which foods fit each meal, the groups each meal
requires, daily portions per food group, one food of each group per meal (two vegetables) and
portions per food. The glycemic load (glycemic index x carbohydrates / 100) and the saturated fat
of the day have a ceiling, from the macronutrient plan of the patient or the general one. The
targets are soft: deviations within the tolerance are free and beyond it the objective
minimizes the relative excess, for the day and for each meal's share of energy, so there is
always a plan.

Exchange lists give the same nutrients to every food of a group, so the model works on classes
(meal, group, nutrient profile) instead of single foods: the optimum is the same and the solver
does not get lost among equivalent foods. Concrete foods are then assigned to each class with
a seeded shuffle that avoids repeating foods during the day; another seed, another menu.
"""

from __future__ import annotations

import random
import unicodedata
from collections import defaultdict

import numpy as np
from scipy.optimize import Bounds, LinearConstraint, milp

from .indices import _round
from . import thresholds as t

NUTRIENTS = {"energy": "kcal", "carbohydrates": "cho", "proteins": "protein", "fats": "fat"}
# Ceilings, computed per portion in _with_quality().
QUALITY = {"glycemic_load": "gl", "saturated_fat": "sat"}
REPORTED = {**NUTRIENTS, **QUALITY}
HALVES = 2  # decision variables count half portions


def _plain(text: str) -> str:
    """Lowercase without accents, for matching names and groups."""
    return "".join(c for c in unicodedata.normalize("NFD", text) if unicodedata.category(c) != "Mn").lower()


def fits(food: dict, meal: dict) -> bool:
    name, item = _plain(food["name"]), _plain(food["item"])
    if any(_plain(n) in name for n in meal.get("exclude_names", [])):
        return False
    return item in {_plain(i) for i in meal["items"]} or any(_plain(n) in name for n in meal.get("include_names", []))


def _profile(food: dict) -> tuple:
    return tuple(food[field] for field in REPORTED.values())


def _with_quality(food: dict) -> dict:
    """Glycemic load and saturated fat of a portion; unknown counts as 0."""
    gi = food.get("glycemic_index")
    return {**food, "gl": gi * food["cho"] / 100 if gi is not None else 0.0, "sat": food.get("saturated_fat") or 0.0}


def default_limits(targets: dict) -> dict:
    """The general ceilings of the macronutrient plan (nutrition.py) when the patient's are not given."""
    return {
        "glycemic_load": t.GLYCEMIC_LOAD["base"],
        "saturated_fat": _round(targets["energy"] * t.SATURATED_FAT_PCT["base"] / 900, 0),
    }


def _foods_per_meal(item: str) -> int:
    limits = t.MEAL_PLAN["foods_per_meal"]
    return next((v for k, v in limits.items() if _plain(k) == item), limits["default"])


def _split(halves: int, parts: int) -> list[int]:
    """Half portions among up to `parts` foods, at least one portion each: 6 in 2 -> [3, 3]."""
    parts = max(1, min(parts, halves // HALVES))
    return [halves // parts + (1 if k < halves % parts else 0) for k in range(parts)]


def generate(targets: dict, foods: list[dict], seed: int = 0, limits: dict | None = None) -> dict:
    """targets: energy (kcal), carbohydrates, proteins, fats (g). foods: id, name, item, grams
    (per portion, None when measured otherwise), kcal, protein, fat, cho, saturated_fat (per portion)
    and glycemic_index. limits: glycemic_load and saturated_fat (g) of the day; missing ones,
    the general ceilings."""
    cfg = t.MEAL_PLAN
    meals, notes = cfg["meals"], []
    limits = {**default_limits(targets), **{k: v for k, v in (limits or {}).items() if v is not None}}

    usable = [_with_quality(f) for f in foods if f["kcal"] > 0]
    missing = sorted({f["name"] for f in foods if f["kcal"] <= 0} - {f["name"] for f in usable})
    if missing:
        notes.append(f"Sin valores nutricionales, no se usan: {', '.join(missing)}.")

    # Classes: (meal, group, nutrient profile) -> foods.
    members: dict[tuple, list[dict]] = defaultdict(list)
    for m, meal in enumerate(meals):
        for food in usable:
            if fits(food, meal):
                members[(m, _plain(food["item"]), _profile(food))].append(food)
    classes = list(members)
    if not classes:
        return {"status": "sin_alimentos", "meals": [], "notes": notes + ["Ningún alimento del catálogo calza con las comidas."]}

    # Variables: x (half portions) and y (class used) per class, then deviation over/under
    # for each nutrient and for each meal's energy, then the excess over each ceiling.
    n_cls, n_dev = len(classes), 2 * len(NUTRIENTS) + 2 * len(meals) + len(QUALITY)
    n = 2 * n_cls + n_dev
    X, Y, DEV, MEAL_DEV = 0, n_cls, 2 * n_cls, 2 * n_cls + 2 * len(NUTRIENTS)
    EXCESS = MEAL_DEV + 2 * len(meals)

    w = cfg["weights"]
    rng = random.Random(seed)
    meal_kcal = [targets["energy"] * meal["energy_share"] / 100 for meal in meals]
    c = np.zeros(n)
    c[X:X + n_cls] = [w["variety"] * rng.random() for _ in classes]  # ties between profiles of a group
    for k, key in enumerate(NUTRIENTS):
        c[DEV + 2 * k:DEV + 2 * k + 2] = w[key] / max(targets[key], 1)
    for m in range(len(meals)):
        c[MEAL_DEV + 2 * m:MEAL_DEV + 2 * m + 2] = w["meal_energy"] / max(meal_kcal[m], 1)
    for q, key in enumerate(QUALITY):
        c[EXCESS + q] = w[key] / max(limits[key], 1)

    rows, lower, upper = [], [], []

    def add(coefs: dict[int, float], lo: float, hi: float):
        row = np.zeros(n)
        for i, v in coefs.items():
            row[i] += v
        rows.append(row)
        lower.append(lo)
        upper.append(hi)

    per_meal = HALVES * cfg["max_portions_per_food"]["meal"]
    per_day = HALVES * cfg["max_portions_per_food"]["day"]
    # A class can hold as many foods as the group allows in a meal.
    # (by name: the catalog may repeat a food in two rows).
    x_max = [per_meal * min(_foods_per_meal(item), len({f["name"] for f in members[(m, item, p)]})) for m, item, p in classes]
    for i in range(n_cls):
        add({X + i: 1, Y + i: -x_max[i]}, -np.inf, 0)  # portions only in a used class
        add({X + i: 1, Y + i: -1}, 0, np.inf)  # a used class has at least half a portion

    # Daily targets: |sum - over + under - target| <= tolerance.
    tolerance = cfg["tolerance_percent"]
    for k, key in enumerate(NUTRIENTS):
        coefs = {X + i: profile[k] / HALVES for i, (_, _, profile) in enumerate(classes)}
        coefs[DEV + 2 * k], coefs[DEV + 2 * k + 1] = -1, 1
        band = targets[key] * tolerance[key] / 100
        add(coefs, targets[key] - band, targets[key] + band)

    # Ceilings: sum - excess <= limit.
    for q, key in enumerate(QUALITY):
        k = len(NUTRIENTS) + q
        coefs = {X + i: profile[k] / HALVES for i, (_, _, profile) in enumerate(classes)}
        coefs[EXCESS + q] = -1
        add(coefs, -np.inf, limits[key])

    for m in range(len(meals)):
        in_meal = [i for i, (cm, _, _) in enumerate(classes) if cm == m]
        if not in_meal:
            continue
        coefs = {X + i: classes[i][2][0] / HALVES for i in in_meal}
        coefs[MEAL_DEV + 2 * m], coefs[MEAL_DEV + 2 * m + 1] = -1, 1
        band = meal_kcal[m] * tolerance["meal_energy"] / 100
        add(coefs, meal_kcal[m] - band, meal_kcal[m] + band)
        add({Y + i: 1 for i in in_meal}, 1, np.inf)  # never an empty meal
        # Required groups: at least half a portion of any of them.
        for groups in meals[m].get("required", []):
            plain = {_plain(g) for g in groups}
            add({X + i: 1 for i in in_meal if classes[i][1] in plain}, 1, np.inf)
        # One food of each group per meal (vegetables: two): profiles of a group compete.
        for item in {classes[i][1] for i in in_meal}:
            add({Y + i: 1 for i in in_meal if classes[i][1] == item}, 0, _foods_per_meal(item))

    # Portions per food and day, over all the foods that share a profile.
    for item, profile in {(item, p) for _, item, p in classes}:
        same = [i for i, (_, ci, cp) in enumerate(classes) if (ci, cp) == (item, profile)]
        n_foods = len({f["name"] for i in same for f in members[classes[i]]})
        add({X + i: 1 for i in same}, 0, per_day * n_foods)

    for item, (lo, hi) in cfg["daily_portions"].items():
        same = [X + i for i, (_, ci, _) in enumerate(classes) if ci == _plain(item)]
        if same:
            add({i: 1 for i in same}, HALVES * lo, HALVES * hi)
        elif lo > 0:
            notes.append(f"El catálogo no tiene alimentos de {item}.")

    integrality = np.r_[np.ones(2 * n_cls), np.zeros(n_dev)]
    bounds = Bounds(np.zeros(n), np.r_[x_max, np.ones(n_cls), np.full(n_dev, np.inf)])
    result = milp(c, constraints=LinearConstraint(np.array(rows), lower, upper), integrality=integrality,
                  bounds=bounds, options={"time_limit": cfg["time_limit_seconds"], "mip_rel_gap": cfg["mip_rel_gap"]})

    if result.x is None:
        return {"status": "sin_solucion", "meals": [], "notes": notes + [f"El optimizador no encontró un plan: {result.message}"]}
    if result.status != 0:
        notes.append("Se alcanzó el tiempo límite: el plan es factible pero puede no ser el óptimo.")

    out_meals, used = _assign(classes, members, np.rint(result.x[X:X + n_cls]).astype(int), meals, meal_kcal, rng)
    if any(halves > per_day for halves in used.values()):
        notes.append("Algún alimento supera las porciones diarias sugeridas: el catálogo tiene pocas alternativas.")

    totals = {key: _round(sum(meal["totals"][key] for meal in out_meals), 1) for key in REPORTED}
    plan_foods = {i["food_id"] for meal in out_meals for i in meal["items"]}
    no_gi = sorted({f["name"] for f in usable if f["id"] in plan_foods and f.get("glycemic_index") is None and f["cho"] >= 5})
    if no_gi:
        notes.append(f"Sin índice glicémico, no suman a la carga glucémica: {', '.join(no_gi)}.")
    over = {"glycemic_load": "la carga glucémica", "saturated_fat": "la grasa saturada"}
    exceeded = [over[key] for key in QUALITY if totals[key] > limits[key] * 1.02]  # 2 %: rounding
    if exceeded:
        notes.append(f"Supera el máximo de {', '.join(exceeded)}: bajarlo alejaría el plan de las metas de energía y macronutrientes.")
    labels = {"energy": "la energía", "carbohydrates": "los carbohidratos", "proteins": "las proteínas", "fats": "las grasas"}
    off = [labels[key] for key in NUTRIENTS if abs(totals[key] - targets[key]) > targets[key] * cfg["tolerance_percent"][key] / 100 + 1e-6]
    if off:
        notes.append(f"Fuera de la tolerancia en {', '.join(off)}: los límites de porciones por grupo no permiten acercarse más; ajustar a mano.")
    return {
        "status": "optimo" if result.status == 0 else "factible",
        "seed": seed,
        "targets": targets,
        "limits": limits,
        "totals": totals,
        "deviation_percent": {key: _round((totals[key] - targets[key]) / targets[key] * 100, 1) if targets[key] else 0.0 for key in NUTRIENTS},
        "meals": out_meals,
        "notes": notes,
    }


def _assign(classes, members, halves, meals, meal_kcal, rng) -> tuple[list[dict], dict]:
    """Concrete foods for the chosen classes: shuffled by the seed, foods not yet used today first."""
    used: dict[str, int] = defaultdict(int)  # food name -> half portions in the day
    per_day = HALVES * t.MEAL_PLAN["max_portions_per_food"]["day"]
    out = []
    for m, meal in enumerate(meals):
        items = []
        for i, (cm, item, _) in enumerate(classes):
            if cm != m or halves[i] == 0:
                continue
            # The catalog may repeat a name (two rows for the same food): once per meal.
            in_meal = {chosen["name"] for chosen in items}
            candidates = [f for f in members[classes[i]] if f["name"] not in in_meal] or list(members[classes[i]])
            rng.shuffle(candidates)
            candidates.sort(key=lambda f: (used[f["name"]] > 0, used[f["name"]]))
            names = list(dict.fromkeys(f["name"] for f in candidates))
            for part, name in zip(_split(int(halves[i]), min(_foods_per_meal(item), len(names))), names):
                food = next(f for f in candidates if f["name"] == name)
                if used[food["name"]] + part > per_day:
                    food = min(candidates, key=lambda f: used[f["name"]])
                used[food["name"]] += part
                items.append(_item(food, part / HALVES))
        out.append({
            "key": meal["key"],
            "label": meal["label"],
            "energy_target": int(_round(meal_kcal[m], 0)),
            "items": items,
            "totals": {key: _round(sum(i[key] for i in items), 1) for key in REPORTED},
        })
    return out, used


def _item(food: dict, portions: float) -> dict:
    return {
        "food_id": food["id"],
        "name": food["name"],
        "group": food["item"],
        "portions": portions,
        "grams": int(_round(portions * food["grams"], 0)) if food.get("grams") else None,
        **{key: _round(portions * food[field], 1) for key, field in REPORTED.items()},
    }
