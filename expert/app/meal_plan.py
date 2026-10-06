"""Meal plans by mixed-integer linear programming (scipy.optimize.milp, HiGHS).

Decides how many half exchange portions go in each meal so each day meets the energy and
macronutrient targets of the consultation. Hard constraints come from the dietary guidelines
(shared/clinical_thresholds.json, meal_plan): which foods fit each meal, the groups each meal
requires, daily portions per food group, portions of a group in one meal (one fruit, one oil…),
the smallest sensible portion of a food (no 13 g of meat), one food of each group per meal (two
vegetables) and portions per food. The glycemic load (glycemic index x carbohydrates / 100) and
the saturated fat of the day have a ceiling, from the macronutrient plan of the patient or the
general one. The targets are soft: deviations within the tolerance are free and beyond it the
objective minimizes the relative excess, for the day and for each meal's share of energy, so
there is always a plan.

Exchange lists give the same nutrients to every food of a group, so the model works on classes
(meal, group, nutrient profile) instead of single foods: the optimum is the same and the solver
does not get lost among equivalent foods. Concrete foods are then assigned to each class with
a seeded shuffle that avoids repeating foods during the day and across days; another seed,
another menu. A plan of several days solves one model per day, each one penalizing the
classes the previous days already used, so the days differ in structure and not only in names.
"""

from __future__ import annotations

import random
import unicodedata
from collections import Counter, defaultdict

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


def _by_group(setting: dict, item: str):
    """Value of a per-group setting ({"default": …, "Frutas": …}) for a plain group name."""
    return next((v for k, v in setting.items() if _plain(k) == item), setting["default"])


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


def food_cap(food: dict) -> int:
    """Half portions of a food in one meal: the general maximum or a per-food one (eggs: 2)."""
    caps = t.MEAL_PLAN["max_portions_per_food"]
    name = _plain(food["name"])
    named = [v for k, v in caps.get("by_name", {}).items() if _plain(k) == name]
    return HALVES * (named[0] if named else caps["meal"])


def _split(halves: int, parts: int, minimum: int) -> list[int]:
    """Half portions among up to `parts` foods, each at least `minimum`: 6 in 2 -> [3, 3]."""
    parts = max(1, min(parts, halves // max(minimum, 1)))
    return [halves // parts + (1 if k < halves % parts else 0) for k in range(parts)]


def generate(targets: dict, foods: list[dict], seed: int = 0, limits: dict | None = None, days: int = 1) -> dict:
    """targets: energy (kcal), carbohydrates, proteins, fats (g). foods: id, name, item, grams
    (per portion, None when measured otherwise), kcal, protein, fat, cho, saturated_fat (per portion)
    and glycemic_index. limits: glycemic_load and saturated_fat (g) of the day; missing ones,
    the general ceilings. days: menus to generate, different from each other."""
    cfg = t.MEAL_PLAN
    notes = []
    limits = {**default_limits(targets), **{k: v for k, v in (limits or {}).items() if v is not None}}

    usable = [_with_quality(f) for f in foods if f["kcal"] > 0]
    missing = sorted({f["name"] for f in foods if f["kcal"] <= 0} - {f["name"] for f in usable})
    if missing:
        notes.append(f"Sin valores nutricionales, no se usan: {', '.join(missing)}.")

    # Classes: (meal, group, nutrient profile) -> foods.
    members: dict[tuple, list[dict]] = defaultdict(list)
    for m, meal in enumerate(cfg["meals"]):
        for food in usable:
            if fits(food, meal):
                members[(m, _plain(food["item"]), _profile(food))].append(food)
    if not members:
        return {"status": "sin_alimentos", "days": [], "notes": notes + ["Ningún alimento del catálogo calza con las comidas."]}
    for item, (lo, _) in cfg["daily_portions"].items():
        if lo > 0 and not any(ci == _plain(item) for _, ci, _ in members):
            notes.append(f"El catálogo no tiene alimentos de {item}.")

    rng = random.Random(seed)
    class_uses: Counter = Counter()  # (group, profile) -> days that used it
    food_days: Counter = Counter()  # food name -> days that used it
    out_days, statuses = [], []
    for day in range(1, days + 1):
        result = _solve(targets, limits, members, class_uses, rng)
        if result is None:
            notes.append(f"Día {day}: el optimizador no encontró un plan.")
            continue
        halves, status = result
        statuses.append(status)
        for i, cls in enumerate(members):
            if halves[i]:
                class_uses[(cls[1], cls[2])] += 1
        out_days.append(_day(day, targets, limits, members, halves, status, rng, food_days, usable))

    if not out_days:
        return {"status": "sin_solucion", "days": [], "notes": notes}
    return {
        "status": "optimo" if all(s == "optimo" for s in statuses) else "factible",
        "seed": seed,
        "targets": targets,
        "limits": limits,
        "days": out_days,
        "notes": notes,
    }


def _solve(targets, limits, members, class_uses, rng) -> tuple[np.ndarray, str] | None:
    """Half portions per class for one day; classes used on previous days cost more."""
    cfg = t.MEAL_PLAN
    meals, w = cfg["meals"], cfg["weights"]
    classes = list(members)

    # Variables: x (half portions) and y (class used) per class, then deviation over/under
    # for each nutrient and for each meal's energy, then the excess over each ceiling.
    n_cls, n_dev = len(classes), 2 * len(NUTRIENTS) + 2 * len(meals) + len(QUALITY)
    n = 2 * n_cls + n_dev
    X, Y, DEV, MEAL_DEV = 0, n_cls, 2 * n_cls, 2 * n_cls + 2 * len(NUTRIENTS)
    EXCESS = MEAL_DEV + 2 * len(meals)

    meal_kcal = [targets["energy"] * meal["energy_share"] / 100 for meal in meals]
    c = np.zeros(n)
    # Ties between profiles of a group, and variety between days (per use of the class).
    c[Y:Y + n_cls] = [w["variety"] * rng.random() + w["variety_days"] * class_uses[(item, p)] for _, item, p in classes]
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

    per_day = HALVES * cfg["max_portions_per_food"]["day"]
    # A class can hold as many foods as the group allows in a meal, each up to its cap
    # (by name: the catalog may repeat a food in two rows).
    x_max = []
    for cls in classes:
        caps = sorted({f["name"]: food_cap(f) for f in members[cls]}.values(), reverse=True)
        x_max.append(sum(caps[:_by_group(cfg["foods_per_meal"], cls[1])]))
    for i, (_, item, _) in enumerate(classes):
        minimum = int(HALVES * _by_group(cfg["min_portions"], item))
        add({X + i: 1, Y + i: -x_max[i]}, -np.inf, 0)  # portions only in a used class
        add({X + i: 1, Y + i: -minimum}, 0, np.inf)  # a used class has at least its minimum

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
        for item in {classes[i][1] for i in in_meal}:
            same = [i for i in in_meal if classes[i][1] == item]
            # One food of each group per meal (vegetables: two): profiles of a group compete.
            add({Y + i: 1 for i in same}, 0, _by_group(cfg["foods_per_meal"], item))
            # Portions of a group in one meal: one fruit, one oil…
            add({X + i: 1 for i in same}, 0, HALVES * _by_group(cfg["group_portions_per_meal"], item))

    # Portions per food and day, over all the foods that share a profile.
    for item, profile in {(item, p) for _, item, p in classes}:
        same = [i for i, (_, ci, cp) in enumerate(classes) if (ci, cp) == (item, profile)]
        n_foods = len({f["name"] for i in same for f in members[classes[i]]})
        add({X + i: 1 for i in same}, 0, per_day * n_foods)

    for item, (lo, hi) in cfg["daily_portions"].items():
        same = [X + i for i, (_, ci, _) in enumerate(classes) if ci == _plain(item)]
        if same:
            add({i: 1 for i in same}, HALVES * lo, HALVES * hi)

    integrality = np.r_[np.ones(2 * n_cls), np.zeros(n_dev)]
    bounds = Bounds(np.zeros(n), np.r_[x_max, np.ones(n_cls), np.full(n_dev, np.inf)])
    result = milp(c, constraints=LinearConstraint(np.array(rows), lower, upper), integrality=integrality,
                  bounds=bounds, options={"time_limit": cfg["time_limit_seconds"], "mip_rel_gap": cfg["mip_rel_gap"]})
    if result.x is None:
        return None
    return np.rint(result.x[X:X + n_cls]).astype(int), "optimo" if result.status == 0 else "factible"


def _day(day, targets, limits, members, halves, status, rng, food_days, usable) -> dict:
    cfg = t.MEAL_PLAN
    meals, notes = _assign(list(members), members, halves, rng, food_days, targets)
    if status != "optimo":
        notes.append("Se alcanzó el tiempo límite: el plan es factible pero puede no ser el óptimo.")

    totals = {key: _round(sum(meal["totals"][key] for meal in meals), 1) for key in REPORTED}
    plan_foods = {i["food_id"] for meal in meals for i in meal["items"]}
    no_gi = sorted({f["name"] for f in usable if f["id"] in plan_foods and f.get("glycemic_index") is None and f["cho"] >= 5})
    if no_gi:
        notes.append(f"Sin índice glicémico, no suman a la carga glucémica: {', '.join(no_gi)}.")
    over = {"glycemic_load": "la carga glucémica", "saturated_fat": "la grasa saturada"}
    exceeded = [over[key] for key in QUALITY if totals[key] > limits[key] * 1.02]  # 2 %: rounding
    if exceeded:
        notes.append(f"Supera el máximo de {', '.join(exceeded)}: bajarlo alejaría el plan de las metas de energía y macronutrientes.")
    labels = {"energy": "la energía", "carbohydrates": "los carbohidratos", "proteins": "las proteínas", "fats": "las grasas"}
    # 0,3 points of slack: the totals add rounded portions, the model works with exact ones.
    off = [labels[key] for key in NUTRIENTS if abs(totals[key] - targets[key]) > targets[key] * (cfg["tolerance_percent"][key] + 0.3) / 100]
    if off:
        notes.append(f"Fuera de la tolerancia en {', '.join(off)}: los límites de porciones por grupo no permiten acercarse más; ajustar a mano.")

    return {
        "day": day,
        "meals": meals,
        "totals": totals,
        "deviation_percent": {key: _round((totals[key] - targets[key]) / targets[key] * 100, 1) if targets[key] else 0.0 for key in NUTRIENTS},
        "notes": notes,
    }


def _assign(classes, members, halves, rng, food_days, targets) -> tuple[list[dict], list[str]]:
    """Concrete foods for the chosen classes: shuffled by the seed, preferring foods not used
    today, then foods used on fewer previous days, within each food's cap per meal."""
    cfg = t.MEAL_PLAN
    used: Counter = Counter()  # food name -> half portions today
    per_day = HALVES * cfg["max_portions_per_food"]["day"]
    out, notes = [], []
    for m, meal in enumerate(cfg["meals"]):
        items = []
        for i, (cm, item, _) in enumerate(classes):
            if cm != m or halves[i] == 0:
                continue
            # The catalog may repeat a name (two rows for the same food): once per meal.
            in_meal = {chosen["name"] for chosen in items}
            candidates = [f for f in members[classes[i]] if f["name"] not in in_meal] or list(members[classes[i]])
            rng.shuffle(candidates)
            candidates = list({f["name"]: f for f in candidates}.values())  # one row per name
            candidates.sort(key=lambda f: (used[f["name"]] > 0, food_days[f["name"]], used[f["name"]]))
            minimum = int(HALVES * _by_group(cfg["min_portions"], item))
            remaining, chosen = int(halves[i]), []
            for part in _split(remaining, min(_by_group(cfg["foods_per_meal"], item), len(candidates)), minimum):
                pool = [f for f in candidates if f not in chosen and food_cap(f) >= part and used[f["name"]] + part <= per_day]
                food = pool[0] if pool else max((f for f in candidates if f not in chosen), key=food_cap, default=candidates[0])
                chosen.append(food)
                used[food["name"]] += part
                if not pool:
                    notes.append(f"{food['name']}: {used[food['name']] / HALVES:g} porciones en el día, más de las sugeridas; "
                                 "el catálogo tiene pocas alternativas equivalentes.")
                items.append(_item(food, part / HALVES))
        out.append({
            "key": meal["key"],
            "label": meal["label"],
            "energy_target": int(_round(targets["energy"] * meal["energy_share"] / 100, 0)),
            "items": items,
            "totals": {key: _round(sum(i[key] for i in items), 1) for key in REPORTED},
        })
    for name in used:
        food_days[name] += 1
    return out, notes


def _item(food: dict, portions: float) -> dict:
    return {
        "food_id": food["id"],
        "name": food["name"],
        "group": food["item"],
        "portions": portions,
        "grams": int(_round(portions * food["grams"], 0)) if food.get("grams") else None,
        **{key: _round(portions * food[field], 1) for key, field in REPORTED.items()},
    }
