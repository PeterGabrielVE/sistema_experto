"""Text normalization shared by the catalog, the meal plan and the allergies."""

from __future__ import annotations

import unicodedata


def plain(text: str) -> str:
    """Lowercase without accents, for matching names and groups."""
    return "".join(c for c in unicodedata.normalize("NFD", text) if unicodedata.category(c) != "Mn").lower()
