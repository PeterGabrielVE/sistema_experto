# Tareas · US-4.2

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-4.2.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.
TK-4.2.1 (ETL con Pandas) se descartó (D-4) y su id queda retirado. TK-4.2.2 conserva el id del backlog
(gestión desde la aplicación). **Antes de TK-4.2.6** se mergea el PR que cambia el principio II de la
constitución (plan, *Verificación de la constitución*).

- [x] **TK-4.2.3** — Agregar `meal_plan.catalog_review` a `shared/clinical_thresholds.json` (tolerancia
  de 15 % y mínimo de 5 g de carbohidratos, con la fuente); hacer que `expert/app/catalog.py` los use en
  lugar de `ATWATER_TOLERANCE`, y crear `Food::reviewFlags()` en Laravel con los mismos valores, con
  `FoodReviewFlagsTest` y el test de pytest. Cubre AC-1.
- [x] **TK-4.2.2** — Crear la sección *Alimentos* (lista con búsqueda por nombre, filtro por grupo y
  marcas a revisar), la entrada del menú y `FoodPolicy` (médicos ven; Administrador 403), con los tests de
  lista, permisos y NFR-1. Cubre AC-1, AC-6.
- [x] **TK-4.2.4** — Crear el alta y la edición para el Doctor Jefe (`FoodRequest`, `FoodCatalogService`,
  advertencia de energía) y el evento `FoodCatalogChanged` auditado con los valores anteriores y nuevos,
  con los tests de validación, de propuestas nuevas con los valores editados y de auditoría.
  Cubre AC-2, AC-3, AC-6, AC-7.
- [x] **TK-4.2.5** — Agregar `retired_at` a `foods`, la acción *Retirar* (Doctor Jefe, auditada) y
  excluir los retirados de `generate()` y `editorCatalog()` sin afectar a `rebuild()` ni a los menús
  guardados. Cubre AC-4, AC-6, AC-7.
- [ ] **TK-4.2.6** — Hacer que `foods:import` solo agregue ids nuevos, crear `foods:export` y el botón
  *Descargar CSV*, y actualizar US-3.1/AC-8 y sus tests, una vez mergeado el cambio a la constitución.
  Cubre AC-5.
