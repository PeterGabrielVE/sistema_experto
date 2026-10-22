# Tareas · US-1.2

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-1.2.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.
TK-1.2.1 y TK-1.2.2 son las tareas del backlog, ya implementadas: se cierran etiquetando sus tests. TK-1.2.3 es
nueva y es la única con cambios de código.

- [ ] **TK-1.2.1** — Migración de consultas y laboratorio (implementado): etiquetar los tests de registro,
  validación, vínculo con la consulta, historial, permisos y auditoría. Cubre AC-1, AC-2, AC-3, AC-4, AC-5, AC-7,
  AC-8.
- [ ] **TK-1.2.2** — Formulario de captura de consulta (implementado): etiquetar los tests del formulario con el
  último peso y talla. Cubre AC-6.
- [x] **TK-1.2.3** — Mover los rangos de referencia del laboratorio a `shared/clinical_thresholds.json`
  (`lab_reference`, con sus fuentes) y marcar la GGT según el sexo, con sus tests. Cubre AC-9.
