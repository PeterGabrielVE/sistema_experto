FROM python:3.13-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /service

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY app ./app
# Shared clinical cut-offs (compose: additional_contexts.shared = ./shared).
COPY --from=shared clinical_thresholds.json ./shared/
COPY tests ./tests

RUN useradd --system --uid 1000 app
USER app

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
