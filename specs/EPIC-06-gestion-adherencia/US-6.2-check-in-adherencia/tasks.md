# Tareas · US-6.2

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-6.2.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.
Orden por dependencia: el registro necesita el menú del día, y el resumen de la ficha necesita los registros.

- [x] **TK-6.2.1** — Crear `AdherenceService::menuFor()` (rotación desde la fecha de guardado, en
  `clinic_timezone`) y mostrar en el inicio del portal el menú de hoy y el de ayer, solo de lectura; sin plan
  guardado, el aviso. Cubre AC-1, AC-5.
- [x] **TK-6.2.2** — Crear `adherence_checkins` y `adherence.scores` en `clinical_thresholds.json`; el
  formulario por comida (*cumplí*, *en parte*, *no cumplí*, *sin marcar*) para hoy y ayer y la ruta
  `PUT /portal/cumplimiento/{date}`, que rechaza otros días y comidas ajenas al menú, con
  `PortalAdherenceTest`. Cubre AC-1, AC-2, AC-3, AC-5.
- [ ] **TK-6.2.3** — Crear `AdherenceService::weeklySummary()` y la tarjeta *Cumplimiento del plan* en la
  ficha (8 semanas: porcentaje o "sin dato", comidas marcadas y días sin registro), con
  `AdherenceSummaryTest`. Cubre AC-4.
