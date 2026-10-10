---
description: Implementar una tarea TK de una spec aprobada, con tests etiquetados
argument-hint: <TK-x.y.n>
---

Implementa la tarea $ARGUMENTS.

1. Ubica la spec con `python scripts/spec_check.py list` y lee `spec.md`, `plan.md` y `tasks.md`.
   Si la spec está en `draft`, detente: hay que aprobarla primero.
2. Implementa solo lo que la tarea y sus AC piden (constitución IX). Si descubres que la spec está
   incompleta o equivocada, propone el cambio a la spec y espera confirmación antes de desviarte.
3. Escribe primero los tests de los AC que cubre la tarea, etiquetados:
   `#[Group('US-x.y/AC-n')]` en PHPUnit o `@pytest.mark.spec("US-x.y/AC-n")` en pytest. Deben fallar
   antes de implementar y pasar después. Sin ids fijos ni dependencia del orden de claves JSON
   (la suite corre en SQLite y MySQL).
4. Valores clínicos nuevos solo en `shared/clinical_thresholds.json`, con su fuente.
5. Marca la tarea `[x]` en `tasks.md`; pasa la spec a `in-progress` o, si era la última tarea y cada
   AC tiene test, a `implemented`.
6. Verifica y reporta el resultado real de cada uno:
   - `python scripts/spec_check.py`
   - `php artisan test --group=US-x.y/AC-n` y la suite completa `php artisan test`
   - `vendor/bin/pint --test --dirty`
   - Python, si aplica: `docker compose exec expert python -m pytest tests --spec US-x.y`
7. No hagas commit ni push: el usuario los hace. Sugiere el título del PR: `feat(TK-x.y.n): ...`.
