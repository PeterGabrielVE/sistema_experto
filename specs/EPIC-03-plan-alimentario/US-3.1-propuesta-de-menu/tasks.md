# Tareas · US-3.1

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-3.1.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.

- [x] **TK-3.1.1** — Cargar el catálogo de composición nutricional de alimentos (`shared/food_catalog.csv`,
  `foods:import`, `GET /foods`). Cubre AC-8. Commit `f6b2d49`.
- [x] **TK-3.1.2** — Implementar el solver lineal con restricciones de pautas, topes, alergias y
  presupuesto (`POST /meal-plan`). Cubre AC-2, AC-3, AC-4, AC-5, AC-6, AC-7. Commit `308cc4d`.
- [x] **TK-3.1.3** — Mostrar la propuesta en la página de resultado y el PDF, con variantes, días y
  degradación sin servicio o sin metas. Cubre AC-1, AC-5, AC-10, AC-11. Commits `54713b6` (generador
  base) y `94b1a3a`.
- [x] **TK-3.1.4** — Editor de la propuesta: cambiar alimentos y porciones, recalcular en el servidor,
  guardar y descartar, solo médicos. Cubre AC-9, AC-10. Commit `94b1a3a`.
