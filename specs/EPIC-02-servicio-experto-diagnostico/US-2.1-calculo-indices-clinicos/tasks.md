# Tareas · US-2.1

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-2.1.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.
Las dos tareas del backlog ya están implementadas: se cierran etiquetando sus tests, y pueden ir en un mismo PR.

- [ ] **TK-2.1.1** — Arquitectura del servicio y API (implementado): etiquetar los tests de la API, de los
  cortes compartidos y del rechazo de datos imposibles. Cubre AC-1, AC-7, AC-8.
- [ ] **TK-2.1.2** — Fórmulas clínicas (implementado): etiquetar los tests de cada índice, de los datos
  faltantes, del redondeo igual a Laravel, de Friedewald, de los cortes por sexo y del FINDRISC. Cubre AC-1,
  AC-2, AC-3, AC-4, AC-5, AC-6.
