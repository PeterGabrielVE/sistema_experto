---
description: Redactar la spec (draft) de una historia de usuario siguiendo specs/README.md
argument-hint: <US-x.y> <descripción de la necesidad>
---

Redacta la spec de esta historia: $ARGUMENTS

1. Lee `specs/README.md`, `specs/constitution.md`, `specs/_templates/spec.md` y una spec existente
   (`specs/EPIC-03-plan-alimentario/US-3.1-propuesta-de-menu/spec.md`) como referencia de formato.
2. Si no se indicó el id `US-x.y` o la épica, pregúntalos: vienen del backlog del equipo, no se inventan.
   Revisa con `python scripts/spec_check.py list` que el id no exista.
3. Investiga el código y el README solo para entender el contexto actual (qué existe, qué datos hay,
   qué roles participan). La spec describe **qué** y **por qué**, nunca clases, tablas ni endpoints.
4. Crea `specs/EPIC-xx-<slug>/US-x.y-<slug>/spec.md` desde la plantilla con `status: draft`:
   - Criterios `AC-n` en *dado / cuando / entonces*, cada uno observable y verificable por un test.
     Incluye los casos de datos incompletos y de servicio caído (constitución IV y V) y quién puede
     hacer qué (VII).
   - Todo lo que no sepas con certeza (cortes clínicos, roles, reglas de negocio) va a
     **Preguntas abiertas**, no a una suposición dentro de un criterio.
   - Lo que la historia no hace, a **Fuera de alcance**.
5. Ejecuta `python scripts/spec_check.py` y corrige los errores.
6. No escribas `plan.md`, `tasks.md` ni código. Termina con un resumen de los criterios y la lista
   de preguntas abiertas que el equipo médico debe responder antes de aprobarla. Sugiere el título del
   PR: `docs(US-x.y): spec <título>`.
