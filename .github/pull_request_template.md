<!--
Título: Conventional Commits.
  feat(US-3.1): ...   feat(TK-3.1.2): ...   (requiere una spec aprobada en specs/)
  fix: ...  docs(US-4.1): spec ...  test: ...  refactor: ...  chore: ...  ci: ...  deps: ...
-->

## Spec

<!-- Enlace a specs/EPIC-xx-.../US-x.y-.../spec.md y la tarea (TK-x.y.n). "No aplica" para fix/chore/ci. -->

## Qué cambia

<!-- Resumen para el revisor: comportamiento visible, no la lista de archivos. -->

## Criterios de aceptación cubiertos

<!-- AC que este PR implementa o modifica y el test que lo verifica. -->

- [ ] AC-n — `tests/...::test_...`

## Checklist

- [ ] La spec está en `approved` (o se aprueba en este PR) y refleja lo implementado.
- [ ] Tareas marcadas `[x]` en `tasks.md`; estado de la spec actualizado (`in-progress` / `implemented`).
- [ ] Cada AC nuevo o modificado tiene un test etiquetado (`#[Group('US-x.y/AC-n')]` / `@pytest.mark.spec`).
- [ ] `python scripts/spec_check.py` sin errores.
- [ ] Revisé la constitución (`specs/constitution.md`); excepciones justificadas en `plan.md`.
- [ ] Valores clínicos nuevos en `shared/clinical_thresholds.json`, con su fuente.
