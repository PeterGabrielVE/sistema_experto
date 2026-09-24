import pytest
from fastapi.testclient import TestClient

from app import main, model


@pytest.fixture(scope="module")
def client():
    main._model = model.train()
    return TestClient(main.app)


@pytest.mark.parametrize(
    ("weight", "size", "expected"),
    [(45, 175, 1), (65, 170, 2), (80, 170, 3), (110, 165, 4)],
)
def test_predicts_expected_category(client, weight, size, expected):
    response = client.post(
        "/predict",
        json={"weight": weight, "size": size, "age": 40, "gender": "H", "physical_activity": 2},
    )
    assert response.status_code == 200
    body = response.json()
    assert body["rule_id"] == expected
    assert 0 < body["confidence"] <= 1


def test_rejects_invalid_input(client):
    response = client.post(
        "/predict",
        json={"weight": -1, "size": 170, "age": 40, "gender": "X", "physical_activity": 9},
    )
    assert response.status_code == 422


def test_requires_token_when_configured(client, monkeypatch):
    monkeypatch.setenv("INFERENCE_TOKEN", "secret")
    payload = {"weight": 70, "size": 170, "age": 40, "gender": "M", "physical_activity": 1}
    assert client.post("/predict", json=payload).status_code == 401
    ok = client.post("/predict", json=payload, headers={"Authorization": "Bearer secret"})
    assert ok.status_code == 200


def test_manual_labels_are_learned():
    # A doctor consistently overrides borderline "Normal" patients as "Sobrepeso".
    manual = [
        {"weight": 72, "size": 170, "age": 50, "gender": "H", "physical_activity": 0, "rule_id": 3}
    ] * 400
    trained = model.train(manual)
    assert trained.samples_manual == 400
    assert trained.predict(manual[0])["rule_id"] == 3
