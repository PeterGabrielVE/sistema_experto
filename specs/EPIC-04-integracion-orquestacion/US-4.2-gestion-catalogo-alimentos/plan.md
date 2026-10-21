# Plan técnico · US-4.2

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

La tabla `foods` pasa a ser la fuente de verdad (D-1). Una sección nueva *Alimentos* en Laravel lista,
busca y filtra el catálogo para los médicos, y permite al Doctor Jefe crear, editar y retirar alimentos
(D-2). Los cambios llegan solos al generador, porque `MealPlanService::generate()` ya envía `foods` desde
la base en cada `POST /meal-plan` (AC-3, AC-5). Retirar marca `retired_at`, sin borrar: los alimentos
retirados no van al generador ni al editor, y los menús guardados no se ven afectados porque guardan
el plan completo en `meal_plans.plan` (AC-4).

Las marcas de *dato a revisar* (energía fuera de la tolerancia y carbohidratos sin índice glicémico) las
calcula `Food::reviewFlags()` en PHP, con umbrales que se leen de `shared/clinical_thresholds.json`
(`meal_plan.catalog_review`). `expert/app/catalog.py` usa esos mismos valores, en lugar de la tolerancia
de 20 % que tiene escrita en el código. Así, la lista funciona aunque el servicio Python no responda
(constitución V), y los dos lados aplican el mismo criterio (II).

`foods:import` pasa a ser solo carga inicial: agrega los ids que faltan y no toca los existentes, así que
volver a importar no pisa lo editado en la aplicación (AC-5). Un comando `foods:export` y un botón
*Descargar CSV* generan el archivo con el formato de la importación. Cada alta, edición y retiro dispara
el evento `FoodCatalogChanged`, que `RecordAuditTrail` escribe en el log `audit` con los valores
anteriores y nuevos (AC-7).

**Alternativas descartadas:**

- *El CSV sigue siendo la fuente y la aplicación propone un PR* (opción b de la pregunta 1). Exige
  integrarse con GitHub y un desarrollador sigue revisando cada cambio, justo lo que la historia quiere
  evitar. La descartó D-1.
- *Que Laravel le pida a `expert` las marcas de revisión (`GET /foods`).* El servicio lee el CSV, no la
  base, así que marcaría datos viejos, y la lista dependería de que el servicio esté arriba.
- *Borrado lógico con `SoftDeletes`.* Oculta el alimento de todas las consultas, también de
  `MealPlanService::rebuild()`, que necesita los alimentos de un menú guardado al reabrirlo en el editor.
  Con `retired_at` y un scope explícito queda claro dónde se excluye.
- *Tabla de historial `food_changes`.* El proyecto ya audita en el canal de log `audit`
  (`RecordAuditTrail`), y AC-7 pide un registro, no una pantalla de historial (constitución IX).

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Datos compartidos | `shared/clinical_thresholds.json` | `meal_plan.catalog_review`: `energy_tolerance_percent` (15), `glycemic_index_min_cho` (5) y la fuente |
| Servicio `expert` | `expert/app/catalog.py` | `check()` lee los dos umbrales de `thresholds.MEAL_PLAN["catalog_review"]` (se borra `ATWATER_TOLERANCE = 0.20`) |
| Base de datos | `database/migrations/…_add_retired_at_to_foods_table.php` (nueva) | `retired_at` nullable |
| Laravel | `app/Models/Food.php` | Casts, `scopeActive()`, `reviewFlags()`, `groups()` (grupos del generador), `perPortionAtwaterKcal()` |
| Laravel | `app/Policies/FoodPolicy.php` (nueva) | `viewAny`: médicos; `create`, `update`, `retire`: Doctor Jefe |
| Laravel | `app/Http/Controllers/FoodController.php` (nuevo) | `index` (búsqueda y filtro), `create`, `store`, `edit`, `update`, `retire`, `export` |
| Laravel | `app/Http/Requests/FoodRequest.php` (nuevo) | Validación de AC-2 |
| Laravel | `app/Services/FoodCatalogService.php` (nuevo) | `save()` y `retire()` con evento y cambios; `export()` a CSV |
| Laravel | `app/Events/FoodCatalogChanged.php` (nuevo), `app/Listeners/RecordAuditTrail.php` | `food.created`, `food.updated`, `food.retired` con `{campo: [antes, después]}` |
| Laravel | `app/Services/MealPlanService.php` | `generate()` y `editorCatalog()` usan solo `Food::active()`; `rebuild()` sigue con todos |
| Laravel | `app/Services/FoodCatalogImporter.php`, `routes/console.php` | Importación solo de ids nuevos; comando `foods:export` |
| Laravel | `routes/web.php` | `Route::resource('foods', …)->except(['show', 'destroy'])`, `PATCH foods/{food}/retire`, `GET foods/export` |
| Vistas | `resources/views/foods/index.blade.php`, `form.blade.php` (nuevas), `layouts/navbars/sidebar.blade.php` | Lista con búsqueda, filtro y marcas; formulario; entrada *Alimentos* en el menú |
| Specs | `specs/constitution.md` (PR propio), `specs/EPIC-03-…/US-3.1-…/spec.md` | Principio II y US-3.1/AC-8 según D-1 |

## Contratos

**Web** (sesión; roles según D-2):

| Método y ruta | Nombre | Quién | Respuesta |
| --- | --- | --- | --- |
| `GET /foods?q=pan&grupo=Cereales` | `foods.index` | Doctor, Doctor Jefe | Lista de alimentos activos, ordenada por grupo y nombre |
| `GET /foods/create`, `POST /foods` | `foods.create`, `foods.store` | Doctor Jefe | Formulario; redirige a la lista con un mensaje y, si corresponde, la advertencia de energía |
| `GET /foods/{food}/edit`, `PUT /foods/{food}` | `foods.edit`, `foods.update` | Doctor Jefe | Igual que el alta |
| `PATCH /foods/{food}/retire` | `foods.retire` | Doctor Jefe | Redirige a la lista; `422` si ya estaba retirado |
| `GET /foods/export` | `foods.export` | Doctor Jefe | `text/csv`, `food_catalog-AAAA-MM-DD.csv` |

**Campos del formulario** (`FoodRequest`; los nombres son los de la columna del CSV):

| Campo | Regla |
| --- | --- |
| `name` | `required`, `string`, `max:100` |
| `group` | `required`, uno de `Food::groups()` (claves de `meal_plan.daily_portions` y `items` de `meal_plan.meals`) |
| `portion` | `nullable`, `string`, `max:50` (texto: "1 taza") |
| `grams`, `kcal` | `required`, `numeric`, `gt:0` |
| `protein`, `fat`, `cho` | `required`, `numeric`, `min:0` |
| `saturated_fat` | `nullable`, `numeric`, `min:0`, `lte:fat` |
| `glycemic_index` | `nullable`, `integer`, `between:0,150` |
| `allergens` | `array`; cada uno `in:` claves de `meal_plan.allergens` |
| `price` | `nullable`, `numeric`, `min:0` (CLP por porción) |

Se acepta la coma decimal (`7,5`). `id_group` no se pide: se toma de los alimentos existentes del mismo
grupo (columna heredada que el generador no usa).

**`shared/clinical_thresholds.json`** (nuevo bloque dentro de `meal_plan`):

```json
"catalog_review": {
  "_about": "Datos del catálogo a revisar (no impiden guardar ni usar el alimento).",
  "energy_tolerance_percent": 15,
  "glycemic_index_min_cho": 5,
  "source": "Factores de Atwater generales 4/4/9 kcal/g: FAO, Food energy – methods of analysis and conversion factors, Food and Nutrition Paper 77, 2003. Tolerancia de 15 % y mínimo de 5 g de carbohidratos: decisión del Doctor Jefe (US-4.2, D-3), por la fibra, los polialcoholes y el alcohol."
}
```

**Auditoría** (canal `audit`, una línea por cambio; los alimentos no son datos de pacientes, así que
se registran los valores):

```json
{"action": "food.updated", "user_id": 3, "food_id": 12, "changes": {"kcal": ["140", "150"], "allergens": ["gluten", "gluten;soya"]}}
```

**Comandos:** `php artisan foods:import` agrega solo los ids que no están en la base e informa cuántos ya
existían y se conservan. `php artisan foods:export [--path=]` escribe los alimentos activos con las
columnas del CSV, por defecto en la salida estándar.

## Modelo de datos

`foods`: columna nueva `retired_at` `dateTime` `nullable`. La migración es reversible (`down` borra la
columna) y no mueve datos. Las columnas heredadas (`kcal`, `protein`, `lipid`, `cho`… son `string`) no
cambian: el servicio guarda números con punto decimal, igual que el importador, y `Food` ya los convierte
a número al enviarlos.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | La spec se aprueba antes de TK-4.2.3; US-3.1/AC-8 se modifica en la tarea que cambia el importador |
| II. Fuente única de verdad clínica | ⚠️ | El catálogo deja de vivir en `shared/food_catalog.csv` y pasa a la tabla `foods` (D-1). Sigue habiendo **una** fuente: Laravel la envía a `expert` en cada solicitud y el CSV se genera desde ella. Requiere el PR propio que cambia el principio II (texto propuesto abajo), que debe mergearse antes de TK-4.2.6. Los umbrales nuevos van en `shared/clinical_thresholds.json`, con su fuente |
| III. Reglas clínicas trazables | ✅ | No hay reglas nuevas. Las marcas de revisión dicen el valor declarado, el calculado y la tolerancia |
| IV. Datos incompletos no son negativos | ✅ | Un índice glicémico vacío es "sin dato" (marca a revisar), nunca 0. Un precio vacío no es gratis: el generador ya no usa ese alimento cuando hay presupuesto |
| V. Degradación segura | ✅ | La lista y el formulario no dependen de los servicios Python. Todo cambio de un alimento es editable por el Doctor Jefe |
| VI. Los tests son la evidencia | ✅ | PHPUnit y pytest por AC (tabla de abajo). NFR-1 con un test de 500 alimentos |
| VII. Privacidad y control de acceso | ✅ | `FoodPolicy` en cada ruta, con tests 403 para Doctor (escritura) y Administrador (todo). No hay datos de pacientes |
| VIII. Idioma | ✅ | Interfaz en español; números con coma decimal en la lista y aceptados con coma en el formulario |
| IX. Simplicidad | ✅ | Sin dependencias, servicios ni tablas nuevas: una columna (`retired_at`) y el log de auditoría existente |

**Cambio propuesto al principio II** (PR propio `docs: constitución, catálogo de alimentos en la base de datos`):

> Los puntos de corte, rangos y vocabularios clínicos viven en `shared/clinical_thresholds.json`, con la
> fuente bibliográfica de cada valor. El catálogo de alimentos vive en la tabla `foods`, que el Doctor Jefe
> mantiene desde la aplicación; Laravel lo envía a los servicios Python en cada solicitud, y
> `shared/food_catalog.csv` es solo la carga inicial de un ambiente nuevo y el formato de exportación.
> Ningún valor clínico se escribe en el código.

## Estrategia de pruebas

| Criterio | Capa | Test |
| --- | --- | --- |
| AC-1 | Laravel (feature) | `FoodCatalogTest::test_doctor_lists_searches_and_filters_active_foods` |
| AC-1 | Laravel (feature) | `FoodCatalogTest::test_list_marks_energy_mismatch_and_missing_glycemic_index` |
| AC-1 | Laravel (unit sobre el modelo) | `FoodReviewFlagsTest::test_review_flags_use_the_shared_thresholds` |
| AC-1 | Python | `test_catalog.py::test_check_uses_the_shared_review_thresholds` |
| AC-2 | Laravel (feature) | `FoodCatalogTest::test_chief_doctor_creates_and_edits_a_food` |
| AC-2 | Laravel (feature) | `FoodCatalogTest::test_validation_rejects_bad_values_without_saving` (data provider: grupo, porción, energía, nutriente negativo, alérgeno desconocido, grasa saturada > grasa) |
| AC-2 | Laravel (feature) | `FoodCatalogTest::test_energy_out_of_tolerance_is_saved_with_a_warning` |
| AC-3 | Laravel (feature) | `FoodCatalogTest::test_new_proposals_use_the_edited_values` (`Http::fake` del servicio `expert`: el `foods` enviado trae los valores nuevos) |
| AC-3 | Laravel (feature) | `FoodCatalogTest::test_saved_proposals_do_not_change` |
| AC-4 | Laravel (feature) | `FoodCatalogTest::test_retired_food_is_not_sent_to_the_generator_nor_the_editor` |
| AC-4 | Laravel (feature) | `FoodCatalogTest::test_saved_menu_with_a_retired_food_is_shown_complete` |
| AC-5 | Laravel (feature) | `FoodCatalogImportTest::test_importing_again_keeps_the_foods_edited_in_the_app` |
| AC-5 | Laravel (feature) | `FoodCatalogTest::test_chief_doctor_exports_the_catalog_in_the_import_format` (el CSV exportado se vuelve a importar sin errores en una base vacía) |
| AC-6 | Laravel (feature) | `FoodCatalogTest::test_doctor_can_view_but_not_change_the_catalog` |
| AC-6 | Laravel (feature) | `FoodCatalogTest::test_administrator_cannot_see_the_catalog` |
| AC-7 | Laravel (feature) | `FoodCatalogTest::test_changes_are_audited_with_old_and_new_values` (`Log::shouldReceive` en el canal `audit`) |
| NFR-1 | Laravel (feature) | `FoodCatalogTest::test_lists_five_hundred_foods_in_under_a_second` |

Los tests usan alimentos creados en el propio test, con ids tomados de los modelos, para que la suite
pase en SQLite y en MySQL. El test de pytest corre con
`docker compose exec expert python -m pytest tests --spec US-4.2`.

Al cambiar el importador, `FoodCatalogImportTest::test_updates_by_id_and_keeps_foods_that_are_not_in_the_file`
(US-3.1/AC-8) se reemplaza por `test_importing_again_keeps_the_foods_edited_in_the_app`, junto con el
texto de US-3.1/AC-8.

## Riesgos y decisiones

- **El servicio `expert` sigue leyendo el CSV** en `GET /foods` y en `POST /meal-plan` sin `foods`.
  Laravel no usa ninguno de los dos, pero si alguien los llama directo verá la carga inicial. Se deja
  documentado en `catalog.py` y en el README del servicio; quitarlos rompería sus tests y no lo pide
  la spec.
- **Columnas heredadas en `string`:** la búsqueda y el orden son por `name` e `item`, no por números.
  Si en el futuro se ordena por energía, habrá que migrar las columnas a `decimal`.
- **Tolerancia del servicio de 20 % a 15 %:** `GET /foods` empezará a advertir alimentos que hoy no
  advierte. Es lo que pide D-3, y el test de pytest fija el nuevo valor.
- **Grupos del formulario:** solo los que conoce el generador (`meal_plan`). Un grupo nuevo requiere
  primero cambiar las pautas en `clinical_thresholds.json`, con su fuente.
- **Alimento retirado en un menú guardado:** el menú lo muestra igual (está en `meal_plans.plan`). Si
  el médico abre ese menú en el editor, el alimento aparece en la comida pero no en el buscador para
  agregarlo de nuevo.
