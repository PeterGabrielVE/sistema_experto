"""Meal plans by mixed-integer linear programming (PuLP with the HiGHS solver).

Decides how many half exchange portions go in each meal so each day meets the energy and
macronutrient targets of the consultation. Hard constraints come from the dietary guidelines
(shared/clinical_thresholds.json, meal_plan): which foods fit each meal, the groups each meal
requires, daily portions per food group, portions of a group in one meal (one fruit, one oil…),
the smallest sensible portion of a food (no 13 g of meat), one food of each group per meal (two
vegetables) and portions per food. Foods the patient is allergic or intolerant to are left out
before the model is built (allergies.py). The glycemic load (glycemic index x carbohydrates /
100), the saturated fat and, when given, the cost of the day have a ceiling. The targets are
soft: deviations within the tolerance are free and beyond it the objective minimizes the
relative excess, for the day and for each meal's share of energy, so there is always a plan.
Ceilings are soft too, but going over the budget costs more than missing a target.

Exchange lists give the same nutrients to every food of a group, so the model works on classes
(meal, group, nutrient profile and, with a budget, price) instead of single foods: the optimum is
the same and the solver does not get lost among equivalent foods. Concrete foods are then
assigned to each class with a seeded shuffle that avoids repeating foods during the day and
across days; another seed, another menu. A plan of several days solves one model per day, each
one penalizing the classes the previous days already used, so the days differ in structure and
not only in names.
"""

from __future__ import annotations

import random
from collections import Counter, defaultdict

import pulp

from . import allergies as allergy
from . import thresholds as t
from .indices import _round
from .text import plain as _plain

NUTRIENTS = {"energy": "kcal", "carbohydrates": "cho", "proteins": "protein", "fats": "fat"}
# Ceilings, computed per portion in _with_quality().
QUALITY = {"glycemic_load": "gl", "saturated_fat": "sat"}
REPORTED = {**NUTRIENTS, **QUALITY}
PRICE = len(REPORTED)  # position of the price in a priced profile
HALVES = 2  # decision variables count half portions


def _by_group(setting: dict, item: str):
    """Value of a per-group setting ({"default": …, "Frutas": …}) for a plain group name."""
    return next((v for k, v in setting.items() if _plain(k) == item), setting["default"])


def fits(food: dict, meal: dict) -> bool:
    name, item = _plain(food["name"]), _plain(food["item"])
    if any(_plain(n) in name for n in meal.get("exclude_names", [])):
        return False
    return item in {_plain(i) for i in meal["items"]} or any(_plain(n) in name for n in meal.get("include_names", []))


def _profile(food: dict, priced: bool) -> tuple:
    """Nutrients per portion and, under a budget, the price: foods alike for the model."""
    return tuple(food[field] for field in REPORTED.values()) + ((food["price"],) if priced else ())


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


def _clp(amount: float) -> str:
    return "$" + f"{amount:,.0f}".replace(",", ".")


def generate(targets: dict, foods: list[dict], seed: int = 0, limits: dict | None = None, days: int = 1,
             allergies: list[str] | None = None, budget: float | None = None) -> dict:
    """targets: energy (kcal), carbohydrates, proteins, fats (g). foods: id, name, item, grams
    (per portion, None when measured otherwise), kcal, protein, fat, cho, saturated_fat (per portion),
    glycemic_index, allergens (tags) and price (CLP per portion). limits: glycemic_load and
    saturated_fat (g) of the day; missing ones, the general ceilings. days: menus to generate,
    different from each other. allergies: tags or free text of the clinical record; those foods
    are never used. budget: CLP per day; foods without price are not used then."""
    cfg = t.MEAL_PLAN
    notes = []
    limits = {**default_limits(targets), **{k: v for k, v in (limits or {}).items() if v is not None}}

    usable = [_with_quality(f) for f in foods if f["kcal"] > 0]
    missing = sorted({f["name"] for f in foods if f["kcal"] <= 0} - {f["name"] for f in usable})
    if missing:
        notes.append(f"Sin valores nutricionales, no se usan: {', '.join(missing)}.")

    usable, excluded, restrictions = allergy.exclude(usable, allergies or [])
    if excluded:
        notes.append(f"Excluidos por alergias o intolerancias ({', '.join(restrictions.labels())}): "
                     f"{', '.join(sorted({f['name'] for f in excluded}))}.")
    if restrictions.unrecognized:
        notes.append(f"No se reconoce como alérgeno ni alimento del catálogo: {', '.join(restrictions.unrecognized)}; "
                     "revisar el menú a mano.")

    if budget is not None:
        unpriced = sorted({f["name"] for f in usable if f.get("price") is None})
        usable = [f for f in usable if f.get("price") is not None]
        if unpriced:
            notes.append(f"Sin precio, no se usan con presupuesto: {', '.join(unpriced)}.")

    restricted = {
        "allergens": restrictions.allergens,
        "foods": restrictions.words,
        "excluded_foods": sorted({f["name"] for f in excluded}),
        "unrecognized": restrictions.unrecognized,
        "budget": budget,
    }

    # Classes: (meal, group, profile) -> foods.
    members: dict[tuple, list[dict]] = defaultdict(list)
    for m, meal in enumerate(cfg["meals"]):
        for food in usable:
            if fits(food, meal):
                members[(m, _plain(food["item"]), _profile(food, budget is not None))].append(food)
    if not members:
        return {"status": "sin_alimentos", "days": [], "restrictions": restricted,
                "notes": notes + ["Ningún alimento del catálogo calza con las comidas."]}
    for item, (lo, _) in cfg["daily_portions"].items():
        if lo > 0 and not any(ci == _plain(item) for _, ci, _ in members):
            notes.append(f"El catálogo no tiene alimentos de {item}.")
    # Allergies (or a short catalog) may leave a meal without any food of a required group.
    for m, meal in enumerate(cfg["meals"]):
        present = {ci for cm, ci, _ in members if cm == m}
        for groups in meal.get("required", []):
            if not present & {_plain(g) for g in groups}:
                notes.append(f"{meal['label']}: no hay alimentos permitidos de {' o '.join(groups)}; se arma sin ellos.")

    rng = random.Random(seed)
    class_uses: Counter = Counter()  # (group, profile) -> days that used it
    food_days: Counter = Counter()  # food name -> days that used it
    out_days, statuses = [], []
    for day in range(1, days + 1):
        result = _solve(targets, limits, members, class_uses, rng, budget)
        if result is None:
            notes.append(f"Día {day}: el optimizador no encontró un plan.")
            continue
        halves, status = result
        statuses.append(status)
        for i, cls in enumerate(members):
            if halves[i]:
                class_uses[(cls[1], cls[2])] += 1
        out_days.append(_day(day, targets, limits, budget, members, halves, status, rng, food_days, usable))

    if not out_days:
        return {"status": "sin_solucion", "days": [], "restrictions": restricted, "notes": notes}
    return {
        "status": "optimo" if all(s == "optimo" for s in statuses) else "factible",
        "seed": seed,
        "targets": targets,
        "limits": limits,
        "restrictions": restricted,
        "days": out_days,
        "notes": notes,
    }


def _solve(targets, limits, members, class_uses, rng, budget=None) -> tuple[list[int], str] | None:
    """Half portions per class for one day; classes used on previous days cost more."""
    cfg = t.MEAL_PLAN
    meals, w, tolerance = cfg["meals"], cfg["weights"], cfg["tolerance_percent"]
    classes = list(members)
    every = range(len(classes))
    meal_kcal = [targets["energy"] * meal["energy_share"] / 100 for meal in meals]
    model = pulp.LpProblem("menu", pulp.LpMinimize)
    objective = []

    # A class can hold as many foods as the group allows in a meal, each up to its cap
    # (by name: the catalog may repeat a food in two rows).
    x_max = []
    for cls in classes:
        caps = sorted({f["name"]: food_cap(f) for f in members[cls]}.values(), reverse=True)
        x_max.append(sum(caps[:_by_group(cfg["foods_per_meal"], cls[1])]))
    # x: half portions of the class; y: the class is used.
    x = [model.add_variable(f"x_{i}", 0, x_max[i], cat=pulp.LpInteger) for i in every]
    y = [model.add_variable(f"y_{i}", cat=pulp.LpBinary) for i in every]

    def amount(k: int, which=every):
        """Day total of the k-th profile field (kcal, cho…) over the given classes."""
        return pulp.lpSum(classes[i][2][k] / HALVES * x[i] for i in which)

    def soft_band(name: str, expr, target: float, percent: float, weight: float):
        """|expr - target| <= percent % of target; beyond it, over + under at weight / target."""
        over, under = model.add_variable(f"{name}_over", 0), model.add_variable(f"{name}_under", 0)
        band = target * percent / 100
        model.addConstraint(expr - over + under >= target - band, f"{name}_lo")
        model.addConstraint(expr - over + under <= target + band, f"{name}_hi")
        objective.append(weight / max(target, 1) * (over + under))

    def soft_ceiling(name: str, expr, limit: float, weight: float):
        """expr <= limit; the excess costs weight / limit."""
        excess = model.add_variable(f"{name}_excess", 0)
        model.addConstraint(expr - excess <= limit, name)
        objective.append(weight / max(limit, 1) * excess)

    # Ties between profiles of a group, and variety between days (per use of the class).
    objective.append(pulp.lpSum(
        (w["variety"] * rng.random() + w["variety_days"] * class_uses[(item, profile)]) * y[i]
        for i, (_, item, profile) in enumerate(classes)
    ))

    per_day = HALVES * cfg["max_portions_per_food"]["day"]
    for i, (_, item, _) in enumerate(classes):
        model.addConstraint(x[i] <= x_max[i] * y[i], f"used_{i}")  # portions only in a used class
        model.addConstraint(x[i] >= int(HALVES * _by_group(cfg["min_portions"], item)) * y[i], f"minimum_{i}")

    # Daily targets.
    for k, key in enumerate(NUTRIENTS):
        soft_band(key, amount(k), targets[key], tolerance[key], w[key])

    # Ceilings: glycemic load, saturated fat and the budget.
    for q, key in enumerate(QUALITY):
        soft_ceiling(key, amount(len(NUTRIENTS) + q), limits[key], w[key])
    if budget is not None:
        soft_ceiling("budget", amount(PRICE), budget, w["budget"])

    for m in range(len(meals)):
        in_meal = [i for i, (cm, _, _) in enumerate(classes) if cm == m]
        if not in_meal:
            continue
        soft_band(f"meal_{m}", amount(0, in_meal), meal_kcal[m], tolerance["meal_energy"], w["meal_energy"])
        model.addConstraint(pulp.lpSum(y[i] for i in in_meal) >= 1, f"meal_{m}_not_empty")
        # Required groups: at least half a portion of any of them, when there is any.
        for r, groups in enumerate(meals[m].get("required", [])):
            plain = {_plain(g) for g in groups}
            required = [x[i] for i in in_meal if classes[i][1] in plain]
            if required:
                model.addConstraint(pulp.lpSum(required) >= 1, f"meal_{m}_required_{r}")
        for g, item in enumerate(sorted({classes[i][1] for i in in_meal})):
            same = [i for i in in_meal if classes[i][1] == item]
            # One food of each group per meal (vegetables: two): profiles of a group compete.
            model.addConstraint(pulp.lpSum(y[i] for i in same) <= _by_group(cfg["foods_per_meal"], item), f"meal_{m}_foods_{g}")
            # Portions of a group in one meal: one fruit, one oil…
            model.addConstraint(pulp.lpSum(x[i] for i in same) <= HALVES * _by_group(cfg["group_portions_per_meal"], item),
                                f"meal_{m}_portions_{g}")

    # Portions per food and day, over all the foods that share a profile.
    for p, (item, profile) in enumerate(sorted({(item, profile) for _, item, profile in classes}, key=repr)):
        same = [i for i, (_, ci, cp) in enumerate(classes) if (ci, cp) == (item, profile)]
        n_foods = len({f["name"] for i in same for f in members[classes[i]]})
        model.addConstraint(pulp.lpSum(x[i] for i in same) <= per_day * n_foods, f"profile_{p}")

    for g, (item, (lo, hi)) in enumerate(cfg["daily_portions"].items()):
        same = [x[i] for i, (_, ci, _) in enumerate(classes) if ci == _plain(item)]
        if same:
            model.addConstraint(pulp.lpSum(same) >= HALVES * lo, f"daily_{g}_lo")
            model.addConstraint(pulp.lpSum(same) <= HALVES * hi, f"daily_{g}_hi")

    model.setObjective(pulp.lpSum(objective))
    model.solve(pulp.HiGHS(msg=False, timeLimit=cfg["time_limit_seconds"], gapRel=cfg["mip_rel_gap"]))
    if model.sol_status not in (pulp.LpSolutionOptimal, pulp.LpSolutionIntegerFeasible):
        return None
    status = "optimo" if model.sol_status == pulp.LpSolutionOptimal else "factible"
    return [int(round(v.value() or 0)) for v in x], status


def _day(day, targets, limits, budget, members, halves, status, rng, food_days, usable) -> dict:
    cfg = t.MEAL_PLAN
    meals, notes = _assign(list(members), members, halves, rng, food_days, targets)
    if status != "optimo":
        notes.append("Se alcanzó el tiempo límite: el plan es factible pero puede no ser el óptimo.")

    totals = {key: _round(sum(meal["totals"][key] for meal in meals), 1) for key in REPORTED}
    items = [i for meal in meals for i in meal["items"]]
    cost = None if any(i["cost"] is None for i in items) else _round(sum(i["cost"] for i in items), 0)
    plan_foods = {i["food_id"] for i in items}
    no_gi = sorted({f["name"] for f in usable if f["id"] in plan_foods and f.get("glycemic_index") is None and f["cho"] >= 5})
    if no_gi:
        notes.append(f"Sin índice glicémico, no suman a la carga glucémica: {', '.join(no_gi)}.")
    over = {"glycemic_load": "la carga glucémica", "saturated_fat": "la grasa saturada"}
    exceeded = [over[key] for key in QUALITY if totals[key] > limits[key] * 1.02]  # 2 %: rounding
    if exceeded:
        notes.append(f"Supera el máximo de {', '.join(exceeded)}: bajarlo alejaría el plan de las metas de energía y macronutrientes.")
    if budget is not None and cost is not None and cost > budget * 1.01:
        notes.append(f"Cuesta {_clp(cost)}, sobre el presupuesto de {_clp(budget)}: las pautas y las metas no caben en él.")
    labels = {"energy": "la energía", "carbohydrates": "los carbohidratos", "proteins": "las proteínas", "fats": "las grasas"}
    # 0,3 points of slack: the totals add rounded portions, the model works with exact ones.
    off = [labels[key] for key in NUTRIENTS if abs(totals[key] - targets[key]) > targets[key] * (cfg["tolerance_percent"][key] + 0.3) / 100]
    if off:
        notes.append(f"Fuera de la tolerancia en {', '.join(off)}: los límites de porciones por grupo no permiten acercarse más; ajustar a mano.")

    return {
        "day": day,
        "meals": meals,
        "totals": totals,
        "cost": cost,
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
    price = food.get("price")
    return {
        "food_id": food["id"],
        "name": food["name"],
        "group": food["item"],
        "portions": portions,
        "grams": int(_round(portions * food["grams"], 0)) if food.get("grams") else None,
        **{key: _round(portions * food[field], 1) for key, field in REPORTED.items()},
        "cost": _round(portions * price, 0) if price is not None else None,
    }
