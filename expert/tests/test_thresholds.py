import importlib
import json

from app import indices, thresholds


def test_loaded_from_the_shared_file():
    shared = json.loads(thresholds._path().read_text(encoding="utf-8"))

    assert thresholds._path().parent.name == "shared"
    assert thresholds.HOMA_IR == shared["insulin_resistance"]["homa_ir"]
    assert thresholds.MS_WAIST == shared["metabolic_syndrome"]["waist"]


def test_a_changed_cut_off_changes_the_result(tmp_path, monkeypatch):
    data = json.loads(thresholds._path().read_text(encoding="utf-8"))
    data["insulin_resistance"]["homa_ir"] = 5.0
    custom = tmp_path / "clinical_thresholds.json"
    custom.write_text(json.dumps(data), encoding="utf-8")

    monkeypatch.setenv("CLINICAL_THRESHOLDS_PATH", str(custom))
    try:
        importlib.reload(thresholds)
        assert thresholds.HOMA_IR == 5.0
    finally:
        monkeypatch.delenv("CLINICAL_THRESHOLDS_PATH")
        importlib.reload(thresholds)
    assert indices.t.HOMA_IR == 2.5
