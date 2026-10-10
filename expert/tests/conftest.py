import pytest

from app.schemas import Facts


def pytest_addoption(parser):
    parser.addoption("--spec", action="append", default=[], help="Only tests of a spec or criterion: --spec US-3.1 or --spec US-3.1/AC-2")


def pytest_configure(config):
    config.addinivalue_line("markers", 'spec(*criteria): acceptance criteria verified, e.g. "US-3.1/AC-2" (specs/README.md)')


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


@pytest.fixture
def facts():
    """Facts as the API passes them to the engine; nested groups as keyword arguments."""

    def build(sex="H", age=40, **groups):
        return Facts(sex=sex, age=age, **groups).model_dump()

    return build
