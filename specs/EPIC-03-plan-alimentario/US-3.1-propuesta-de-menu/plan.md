# Plan técnico · US-3.1

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

El menú se genera en el servicio `expert` como un problema de **programación lineal entera**
(PuLP + HiGHS): variables en medias porciones de intercambio por grupo y comida, restricciones
duras para las pautas, alergias y topes, y una función objetivo que penaliza el desvío de las metas
fuera de la tolerancia y el exceso de presupuesto. Como los alimentos de un grupo de intercambio son
equivalentes, el modelo decide por grupo y perfil nutricional y después asigna alimentos concretos
con una semilla, lo que hace el resultado reproducible (AC-5) y rápido (NFR-1).

Alternativa descartada: heurística greedy en PHP. No garantiza las tolerancias de AC-2 ni las
restricciones combinadas (alergias + presupuesto + topes), y duplicaría lógica clínica fuera del
servicio experto.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Servicio `expert` | `expert/app/meal_plan.py`, `allergies.py`, `catalog.py`, `routers/` | Modelo, alergias, catálogo, `POST /meal-plan`, `GET /foods` |
| Laravel | `app/Services/MealPlanService.php`, `app/Http/Controllers/MealPlanController.php` | Genera, recalcula y guarda la propuesta |
| Laravel | `app/Services/FoodCatalogImporter.php`, comando `foods:import` | Importa el catálogo a `foods` |
| Datos compartidos | `shared/food_catalog.csv`, `meal_plan` en `shared/clinical_thresholds.json` | Catálogo y pautas |
| Base de datos | `foods` (`glycemic_index`, `saturated_fat`, `allergens`, `price`), `meal_plans` | Columnas y tabla nuevas |
| Vistas | `resources/views/diagnoses/` (resultado, editor, PDF) | Propuesta, editor y PDF |

## Contratos

`POST /meal-plan` (servicio `expert`, con token):

```json
{
  "targets": {"energy": 1750, "carbohydrates": 197, "proteins": 88, "fats": 68},
  "limits": {"glycemic_load": 120, "saturated_fat": 19},
  "foods": [{"id": 1, "name": "Pollo", "item": "Carnes", "grams": 50, "kcal": 65, "...": "..."}],
  "allergies": ["celíaca, alergia al maní"],
  "budget": 6000,
  "seed": 42000,
  "days": 3
}
```

Respuesta: `status` (`optimo` | `factible` | ...), `days[].meals[].items[]` con porciones, gramos y
costo, totales por día y `restrictions` (alérgenos reconocidos, alimentos excluidos, lo no reconocido,
presupuesto). Sin `foods`, el servicio usa su copia del catálogo compartido.

Semilla: `diagnosis.id * 1000 + variante`, para que página y PDF generen el mismo menú (AC-5).

## Modelo de datos

- `foods`: se agregan `glycemic_index`, `saturated_fat`, `allergens` y `price`; el import actualiza por
  `id` y no borra alimentos (los menús guardados los referencian).
- `meal_plans`: una fila por consulta con el plan en JSON y la marca `edited`.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ⚠️ | Retro-spec: la historia se implementó antes de adoptar SDD |
| II. Fuente única de verdad clínica | ✅ | Pautas en `meal_plan`, catálogo en `shared/food_catalog.csv` |
| III. Reglas clínicas trazables | ✅ | Los topes vienen del plan de macronutrientes (`MAC-xx`, `CFG-<id>`) |
| IV. Datos incompletos no son negativos | ✅ | Alergias no reconocidas y alimentos sin datos se informan |
| V. Degradación segura | ✅ | AC-11: sin servicio la página se muestra; el médico edita la propuesta (AC-9) |
| VI. Los tests son la evidencia | ✅ | Todos los AC con tests etiquetados |
| VII. Privacidad y control de acceso | ✅ | Solo médicos editan (`test_only_doctors_edit_proposals`) |
| VIII. Idioma | ✅ | |
| IX. Simplicidad | ✅ | Dependencias nuevas: `pulp` y `highspy` (solver), justificadas en *Enfoque* |

## Estrategia de pruebas

`python scripts/spec_check.py tests US-3.1` lista el detalle actualizado. Resumen:

| Criterio | Capa | Tests |
| --- | --- | --- |
| AC-1 | Laravel + API | `MealPlanTest::test_result_page_shows_the_generated_proposal`, `test_api.py::test_meal_plan` |
| AC-2 | Python | `test_meal_plan.py::test_meets_the_targets_within_the_tolerance`, `test_flags_targets_out_of_reach` |
| AC-3 | Python | `test_respects_the_guideline_constraints`, `test_realistic_meals` |
| AC-4 | Python + Laravel | `test_respects_the_glycemic_load_and_saturated_fat_ceilings`, `MealPlanTest::test_ceilings_come_from_the_macronutrient_plan` |
| AC-5 | Python + Laravel | `test_week_has_different_days_all_within_the_targets`, `MealPlanTest::test_pdf_generates_the_same_variant` |
| AC-6 | Python + Laravel | `test_allergies.py`, `MealPlanTest::test_sends_the_allergies_of_the_clinical_record_and_the_budget` |
| AC-7 | Python + Laravel | `test_a_budget_*`, `MealPlanTest::test_a_budget_out_of_range_is_ignored` |
| AC-8 | Laravel + Python | `FoodCatalogImportTest`, `test_catalog.py` |
| AC-9 | Laravel | `MealPlanEditorTest` |
| AC-10 | Laravel | `MealPlanTest::test_saved_proposal_is_shown_instead_of_generating_one` |
| AC-11 | Laravel | `MealPlanTest::test_without_targets_nothing_is_requested`, `test_service_down` |

## Riesgos y decisiones

- **Infactibilidad:** con alergias y presupuesto juntos el modelo puede no tener solución exacta. Se
  resuelve con penalizaciones (las metas y el presupuesto son blandos) y nunca con las alergias (NFR-2).
- **Tiempo de resolución:** límite por día en `time_limit_seconds`; varios días se resuelven en
  secuencia, encareciendo lo ya usado para variar.
- **Catálogo referenciado:** el import nunca borra alimentos para no romper menús guardados (AC-8, AC-10).
