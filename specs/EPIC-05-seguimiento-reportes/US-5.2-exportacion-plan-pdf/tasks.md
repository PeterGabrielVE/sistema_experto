# Tareas · US-5.2

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-5.2.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.
Conservan los ids del backlog: TK-5.2.1 es la vista imprimible y TK-5.2.2 el generador PDF. Orden por
dependencia: el PDF convierte la vista.

- [x] **TK-5.2.1** — Crear `config/clinic.php`, `PatientPlanDocument` y la vista `meal_plans/pdf.blade.php`
  (logo o nombre, paciente, fecha, profesional, menú por día y comida con energía y macros del día,
  recomendaciones, indicaciones generales compartidas con el informe y alergias; un día por página), con
  los tests de contenido sobre el HTML. Cubre AC-1, AC-2, AC-4, AC-5, AC-8, AC-9.
- [x] **TK-5.2.2** — Crear la ruta `meal-plan.pdf` con DomPDF (solo Doctor y Doctor Jefe, `404` sin plan
  guardado, sin llamar a los servicios Python) y los botones *Informe (PDF)* y *Plan para el paciente (PDF)*
  en la página de resultado, con sus tests. Cubre AC-1, AC-3, AC-6, AC-7.
