"""Food allergies and intolerances: the foods a menu must not include.

The clinical record keeps them as free text ("alergia al maní, intolerancia a la lactosa"), and
the request may also send allergen tags (gluten). Each piece of text is matched against the
allergen vocabulary of the guidelines (shared/clinical_thresholds.json, meal_plan.allergens:
tag -> label and the words that name it), and the foods carrying that tag in the catalog
(food_catalog.csv, allergens column) are left out. A piece that names no allergen is looked up
in the food names (kiwi -> Kiwi). A piece that matches nothing is reported for the doctor: the
menu cannot guarantee it. Exclusion is a hard constraint, never traded for the targets.
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field

from . import thresholds as t
from .text import plain

SEPARATORS = re.compile(r"[,;/\n.()]+|\s+(?:y|e|o)\s+")
# Words around the allergen that name nothing to exclude.
FILLER = {
    "alergia", "alergias", "alergico", "alergica", "intolerancia", "intolerancias", "intolerante",
    "sensibilidad", "a", "al", "la", "las", "el", "los", "lo", "de", "del", "con", "sin", "no", "tolera",
    "come", "leve", "severa", "severo", "grave", "posible", "sospecha", "ninguna", "ninguno", "niega",
}


@dataclass
class Restrictions:
    allergens: list[str] = field(default_factory=list)  # tags, in vocabulary order
    words: list[str] = field(default_factory=list)  # matched against food names
    unrecognized: list[str] = field(default_factory=list)

    def excludes(self, food: dict) -> bool:
        if set(food.get("allergens") or []) & set(self.allergens):
            return True
        name = plain(food["name"])
        return any(re.search(rf"\b{re.escape(word)}", name) for word in self.words)

    def labels(self) -> list[str]:
        vocabulary = t.MEAL_PLAN["allergens"]
        return [vocabulary[tag]["label"] for tag in self.allergens] + self.words


def _names(tag: str) -> list[str]:
    return [tag.replace("_", " "), *(plain(n) for n in t.MEAL_PLAN["allergens"][tag]["names"])]


def _stem(word: str) -> str:
    """Singular enough to find kiwi from kiwis and frutilla from frutillas."""
    for ending in ("es", "s"):
        if word.endswith(ending) and len(word) - len(ending) >= 3:
            return word[: -len(ending)]
    return word


def parse(entries: list[str], foods: list[dict]) -> Restrictions:
    """Allergen tags and food names in the entries; what names neither, unrecognized."""
    vocabulary = t.MEAL_PLAN["allergens"]
    tags, words, unrecognized = set(), [], []
    names = [plain(f["name"]) for f in foods]
    for entry in entries:
        for piece in SEPARATORS.split(plain(entry)):
            piece = " ".join(piece.replace("_", " ").split())
            meaningful = [w for w in piece.split() if w not in FILLER]
            if not meaningful:
                continue
            found = {tag for tag in vocabulary if any(re.search(rf"\b{re.escape(n)}\b", piece) for n in _names(tag))}
            if found:
                tags |= found
                continue
            stems = [_stem(w) for w in meaningful if len(w) >= 3]
            matched = [s for s in stems if any(re.search(rf"\b{re.escape(s)}", n) for n in names)]
            if matched:
                words += [s for s in matched if s not in words]
            else:
                unrecognized.append(piece)
    return Restrictions([tag for tag in vocabulary if tag in tags], words, unrecognized)


def exclude(foods: list[dict], entries: list[str]) -> tuple[list[dict], list[dict], Restrictions]:
    """The foods allowed and the foods left out by the allergies."""
    restrictions = parse(entries, foods)
    kept, excluded = [], []
    for food in foods:
        (excluded if restrictions.excludes(food) else kept).append(food)
    return kept, excluded, restrictions
