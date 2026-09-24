FROM python:3.13-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    MODEL_PATH=/models/model.joblib

WORKDIR /service

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY app ./app
COPY tests ./tests

# Bake a baseline model (synthetic data) so the service works on first start.
# The /models volume is seeded from this directory the first time it is mounted.
RUN python -m app.train

RUN useradd --system --uid 1000 app && chown -R app /models
USER app

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
