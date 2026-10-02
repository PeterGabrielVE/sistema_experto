"""Expert diagnosis service: builds the FastAPI app. Endpoints live in app/routers."""

from __future__ import annotations

from fastapi import FastAPI

from . import config, rules
from .routers import diagnosis, health


def create_app() -> FastAPI:
    app = FastAPI(title=config.TITLE, version=rules.RULESET_VERSION)
    app.include_router(health.router)
    app.include_router(diagnosis.router)
    return app


app = create_app()
