import pytest
from fastapi.testclient import TestClient

from app.main import app

PAYLOAD = {
    "sex": "H",
    "age": 40,
    "anthropometry": {"weight_kg": 80, "height_cm": 165, "waist_cm": 98},
    "labs": {"fasting_glucose": 105, "fasting_insulin": 18.2, "triglycerides": 180, "hdl": 38},
    "conditions": {"hypertension": True},
}


@pytest.fixture
def client(monkeypatch):
    monkeypatch.delenv("EXPERT_TOKEN", raising=False)
    return TestClient(app)


def test_evaluate(client):
    response = client.post("/evaluate", json=PAYLOAD)

    assert response.status_code == 200
    body = response.json()
    assert body["indices"]["tyg"]["value"] == 9.15
    assert body["assessments"]["metabolic_syndrome"]["status"] == "presente"
    assert body["findings"][0]["severity"] == "alert"
    assert body["macronutrients"]["status"] == "calculado"
    assert body["macronutrients"]["macros"]["carbohydrates"]["percent"] <= 45
    assert body["ruleset_version"]


def test_indices_only(client):
    body = client.post("/indices", json=PAYLOAD).json()

    assert body["indices"]["homa_ir"]["value"] == 4.72
    assert "findings" not in body


def test_rule_catalog(client):
    body = client.get("/rules").json()

    assert {"id": "SM-01", "category": "Síndrome metabólico"}.items() <= body["rules"][4].items()
    assert all(r["source"] for r in body["rules"])
    assert [r["id"] for r in body["macro_rules"]][:3] == ["MAC-01", "MAC-02", "MAC-03"]
    assert {r["category"] for r in body["macro_rules"]} == {"Nutrición"}


def test_macro_plan_without_data_leaves_optional_keys_out(client):
    body = client.post("/evaluate", json={"sex": "M"}).json()

    assert body["macronutrients"] == {"status": "indeterminado", "reason": body["macronutrients"]["reason"], "rules": [], "notes": []}


@pytest.mark.parametrize(
    "payload",
    [
        {"sex": "X"},
        {"sex": "H", "labs": {"fasting_glucose": 2000}},
        {"sex": "H", "vitals": {"systolic_bp": 120}},
        {"sex": "H", "vitals": {"systolic_bp": 80, "diastolic_bp": 90}},
    ],
)
def test_rejects_invalid_facts(client, payload):
    assert client.post("/evaluate", json=payload).status_code == 422


def test_requires_token_when_configured(client, monkeypatch):
    monkeypatch.setenv("EXPERT_TOKEN", "secret")

    assert client.post("/evaluate", json=PAYLOAD).status_code == 401
    assert client.post("/evaluate", json=PAYLOAD, headers={"Authorization": "Bearer secret"}).status_code == 200
    assert client.get("/health").status_code == 200  # open for the Docker healthcheck


def test_optional_index_keys_stay_out(client):
    body = client.post("/evaluate", json=PAYLOAD).json()

    assert body["indices"]["bmi"]["category"] == "Sobrepeso"
    assert "category" not in body["indices"]["homa_ir"]


def test_health(client):
    body = client.get("/health").json()

    assert body["status"] == "ok"
    assert body["rules"] == len(client.get("/rules").json()["rules"])


def test_openapi_documents_the_responses(client):
    paths = client.get("/openapi.json").json()["paths"]

    def schema(path, method):
        return paths[path][method]["responses"]["200"]["content"]["application/json"]["schema"]["$ref"]

    assert schema("/evaluate", "post").endswith("/Evaluation")
    assert schema("/indices", "post").endswith("/Indices")
    assert schema("/rules", "get").endswith("/RuleCatalog")
    assert schema("/health", "get").endswith("/Health")


def test_evaluate_with_configured_macro_rules(client):
    rule = {"id": "CFG-1", "title": "HOMA-IR alto", "variable": "homa_ir", "operator": ">", "value": 2.5, "actions": {"glycemic_load": 80}}
    body = client.post("/evaluate", json={**PAYLOAD, "macro_rules": [rule]}).json()

    assert body["macronutrients"]["rules"][-1]["rule_id"] == "CFG-1"
    assert body["macronutrients"]["limits"]["glycemic_load"]["amount"] == 80


@pytest.mark.parametrize(
    "rule",
    [
        {"variable": "shoe_size", "operator": ">", "value": 40, "actions": {"glycemic_load": 80}},
        {"variable": "homa_ir", "operator": "!=", "value": 2.5, "actions": {"glycemic_load": 80}},
        {"variable": "homa_ir", "operator": ">", "value": 2.5, "actions": {"glycemic_load": 10}},
        {"variable": "homa_ir", "operator": ">", "value": 2.5, "actions": {}},
    ],
)
def test_rejects_invalid_macro_rules(client, rule):
    payload = {**PAYLOAD, "macro_rules": [{"id": "CFG-1", "title": "Regla", **rule}]}
    assert client.post("/evaluate", json=payload).status_code == 422
