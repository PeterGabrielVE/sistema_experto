# Tareas · US-5.1

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-5.1.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.
Orden por dependencia: la gráfica consume el endpoint, así que TK-5.1.2 va primero.

- [x] **TK-5.1.2** — Crear el endpoint `GET /patient/{patient}/evolution` y `PatientEvolutionService`
  (series de peso y HOMA-IR, corte, `above_threshold`, `excluded`, `enough_data`, filtro `period`),
  protegido con `viewClinicalRecord`, con `PatientEvolutionTest`. Sin cambios visibles en la interfaz.
  Cubre AC-2, AC-3, AC-4, AC-5, AC-6, AC-7.
- [ ] **TK-5.1.1** — Mostrar en la ficha clínica la tarjeta *Evolución* con la gráfica de dos ejes de
  `patient-evolution-chart.js` (Chart.js, sin dependencias nuevas), la línea del corte, los botones de
  período y los mensajes por serie; test de la vista y verificación manual del plan en el PR.
  Cubre AC-1, AC-3, AC-4, AC-5, AC-7.
