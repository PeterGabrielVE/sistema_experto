import pytest


def pytest_addoption(parser):
    parser.addoption("--spec", action="append", default=[], help="Only tests of a spec or criterion: --spec US-4.1 or --spec US-4.1/AC-3")


def pytest_configure(config):
    config.addinivalue_line("markers", 'spec(*criteria): acceptance criteria verified, e.g. "US-4.1/AC-3" (specs/README.md)')


def pytest_collection_modifyitems(config, items):
    wanted = config.getoption("--spec")
    if not wanted:
        return

    def selected(item):
        refs = [ref for mark in item.iter_markers("spec") for ref in mark.args]
        return any(ref == w or ref.startswith(w + "/") for ref in refs for w in wanted)

    deselected = [item for item in items if not selected(item)]
    config.hook.pytest_deselected(items=deselected)
    items[:] = [item for item in items if selected(item)]


@pytest.fixture(autouse=True)
def local_without_token(monkeypatch):
    """Development without a credential (US-4.1, D-2); the tests of AC-2 and AC-3 change it."""
    monkeypatch.delenv("INFERENCE_TOKEN", raising=False)
    monkeypatch.setenv("APP_ENV", "local")
