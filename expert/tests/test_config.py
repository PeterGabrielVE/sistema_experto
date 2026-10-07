from pathlib import Path

from app.config import get_settings


def test_defaults(monkeypatch):
    monkeypatch.delenv("EXPERT_TOKEN", raising=False)
    monkeypatch.delenv("CLINICAL_THRESHOLDS_PATH", raising=False)
    monkeypatch.delenv("FOOD_CATALOG_PATH", raising=False)

    settings = get_settings()

    assert settings.token == ""
    assert settings.thresholds_path.name == "clinical_thresholds.json"
    assert settings.thresholds_path.parent.name == "shared"
    assert settings.food_catalog_path == settings.thresholds_path.parent / "food_catalog.csv"


def test_from_environment(monkeypatch):
    monkeypatch.setenv("EXPERT_TOKEN", "secret")
    monkeypatch.setenv("CLINICAL_THRESHOLDS_PATH", "/tmp/cut-offs.json")
    monkeypatch.setenv("FOOD_CATALOG_PATH", "/tmp/foods.csv")

    settings = get_settings()

    assert settings.token == "secret"
    assert settings.thresholds_path == Path("/tmp/cut-offs.json")
    assert settings.food_catalog_path == Path("/tmp/foods.csv")
