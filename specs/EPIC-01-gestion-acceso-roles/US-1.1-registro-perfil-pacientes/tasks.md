# Tareas · US-1.1

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-1.1.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.
Las dos tareas del backlog ya están implementadas: se cierran etiquetando sus tests (plan, *Estrategia de
pruebas*), y pueden ir en un mismo PR.

- [x] **TK-1.1.1** — Migración y modelo de pacientes y ficha clínica (implementado): etiquetar los tests de
  modelo, RUT, validación, ficha, permisos y auditoría. Cubre AC-1, AC-2, AC-3, AC-5, AC-7, AC-8, AC-9.
- [x] **TK-1.1.2** — Formulario UI de pacientes (implementado): etiquetar los tests del formulario Vue, la lista
  con búsqueda y la ficha sin registrar. Cubre AC-1, AC-3, AC-4, AC-6.
