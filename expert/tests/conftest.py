import pytest

from app.schemas import Facts


@pytest.fixture
def facts():
    """Facts as the API passes them to the engine; nested groups as keyword arguments."""

    def build(sex="H", age=40, **groups):
        return Facts(sex=sex, age=age, **groups).model_dump()

    return build
