---
description: Escribir plan.md y tasks.md de una spec y dejarla lista para aprobar
argument-hint: <US-x.y>
---

Planifica la historia $ARGUMENTS.

1. Lee su `spec.md`, `specs/constitution.md` y las plantillas `specs/_templates/plan.md` y `tasks.md`.
   Si la spec tiene preguntas abiertas sin responder, detente y enuméralas: no se planifica sobre
   supuestos.
2. Estudia el código afectado (Laravel en `app/`, servicios `expert/` e `inference/`, `shared/`,
   migraciones y vistas) y los tests existentes de esa zona.
3. Escribe `plan.md`: enfoque y alternativa descartada, componentes, contratos con ejemplo JSON,
   modelo de datos con migración reversible, **verificación de la constitución principio por
   principio** (justifica cualquier ⚠️), y la tabla *criterio → test* con la capa de cada uno.
4. Escribe `tasks.md`: tareas `TK-x.y.n` pequeñas, cada una mergeable sola y con
   "Cubre AC-…"; entre todas cubren todos los AC. Ordénalas por dependencia.
5. Cambia `status` a `approved` solo si el usuario lo confirma; si no, déjalo en `draft` y dilo.
6. Ejecuta `python scripts/spec_check.py` y corrige los errores. No escribas código de producción.
