# Plan técnico · US-6.2

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

Todo ocurre en Laravel, sobre el portal de US-6.3. Un `AdherenceService` resuelve dos cosas: el **menú
del día** de una fecha, y el **resumen semanal** que ve el médico.

El menú del día es el día del último plan guardado que toca por rotación (D-4). El día 1 es la fecha en
que se guardó el plan (`meal_plans.updated_at`, en `clinic_timezone`). Para otra fecha se usa
`((fecha − fecha de guardado) mód nº de días) + 1`.

El inicio del portal muestra el menú de **hoy** y el de **ayer** (D-3). Cada comida tiene tres opciones
(*cumplí*, *en parte*, *no cumplí*) y una cuarta, *sin marcar*. Cada comida marcada es una fila en la
tabla nueva `adherence_checkins`, única por paciente, fecha y comida: corregir la reemplaza y desmarcar
la borra. Así, una comida sin marcar no existe en la tabla y nunca se confunde con *no cumplí*
(constitución IV). La ruta de guardado recibe solo la fecha. El paciente siempre es el de la sesión
(AC-3), y una fecha que no sea hoy o ayer responde 422 (AC-2).

El médico ve en la ficha una tarjeta *Cumplimiento del plan* con las últimas 8 semanas (lunes a domingo).
Cada semana muestra el porcentaje con los puntajes de D-5, que viven en
`shared/clinical_thresholds.json`, las comidas marcadas y los días sin registro. Una semana sin
comidas marcadas dice "sin dato", no 0 % (AC-4).

**Alternativas descartadas:**

- *Una fila por día con un JSON de comidas.* Cada comida tiene su propio estado y puede corregirse sola.
  Además, el índice único `(paciente, fecha, comida)` impide duplicados en SQLite y MySQL sin leer y
  reescribir el JSON entero.
- *Guardar "sin dato" como un estado.* Obligaría a crear filas para todo lo que el paciente no marcó,
  y una fila de más podría contarse por error. La ausencia de la fila ya es "sin dato".
- *Calcular el menú del día desde el lunes o el día de la semana.* D-4 lo descartó: la rotación parte
  del día en que se guardó el plan.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Datos compartidos | `shared/clinical_thresholds.json` | `adherence.scores` (`done` 1, `partial` 0,5, `missed` 0) con su fuente (D-5) |
| Base de datos | `…_create_adherence_checkins_table.php` (nueva) | Registros por comida |
| Laravel | `app/Models/AdherenceCheckin.php` (nuevo) | Estados, `score()` |
| Laravel | `app/Models/Patient.php` | `adherenceCheckins()`, `firstMealPlanDate()` |
| Laravel | `app/Services/AdherenceService.php` (nuevo) | `menuFor()`, `registrableDates()`, `record()`, `weeklySummary()` |
| Laravel | `app/Http/Controllers/PortalController.php` | Menú de hoy y de ayer, con lo ya marcado |
| Laravel | `app/Http/Controllers/PortalAdherenceController.php` (nuevo), `app/Http/Requests/AdherenceRequest.php` (nuevo) | Guardar las marcas de un día |
| Laravel | `app/Http/Controllers/ClinicalRecordController.php` | Resumen semanal para la ficha |
| Laravel | `routes/web.php` | `PUT /portal/cumplimiento/{date}` en el grupo `auth` + `patient` |
| Vistas | `resources/views/portal/home.blade.php`, `portal/_day.blade.php` (nueva) | Menú de hoy y de ayer con el formulario por comida |
| Vistas | `resources/views/clinical_records/_adherence.blade.php` (nueva), `show.blade.php` | Tarjeta semanal |
| Servicios Python | — | Sin cambios |

## Contratos

**Portal** (`auth` + `patient`; siempre el paciente de la sesión):

`PUT /portal/cumplimiento/{date}` → `portal.adherence.update`, con `date` en formato `AAAA-MM-DD`:

```json
{ "meals": { "desayuno": "done", "colacion_matutina": "", "almuerzo": "partial", "cena": "missed" } }
```

- `date` debe ser hoy o ayer en `clinic_timezone`; si no, `422` con "Solo puede registrar hoy o ayer."
- Las claves de `meals` deben ser comidas del menú de ese día que tengan alimentos; si no, `422`.
- Valor `done`, `partial` o `missed`: crea o reemplaza la fila. Valor vacío: borra la fila (*sin marcar*).
- Sin plan guardado: `422` (AC-5). La página ya no muestra el formulario.
- Redirige al inicio del portal con "Registro guardado."

**Ficha clínica** (sesión del equipo, `viewClinicalRecord`): la tarjeta lee
`AdherenceService::weeklySummary($patient, 8)`:

```json
[
  {"from": "2026-10-19", "to": "2026-10-25", "percent": 75.0, "marked": 8, "days_without_record": 2},
  {"from": "2026-10-12", "to": "2026-10-18", "percent": null, "marked": 0, "days_without_record": 7}
]
```

- `percent` = promedio de los puntajes de las comidas marcadas × 100, con un decimal. Es `null`
  ("sin dato") si no hay comidas marcadas.
- `days_without_record` cuenta los días de la semana sin ninguna comida marcada, entre la fecha del
  primer plan guardado del paciente y ayer. Hoy no cuenta porque el día no terminó, y antes del primer
  plan no había nada que cumplir.
- Solo se listan las semanas desde la del primer plan guardado. Sin planes, la tarjeta dice "Sin plan
  alimentario guardado".

**`shared/clinical_thresholds.json`** (bloque nuevo):

```json
"adherence": {
  "_about": "Puntaje de cada comida marcada por el paciente en el portal (US-6.2): el porcentaje semanal es su promedio sobre las comidas marcadas; las no marcadas son sin dato.",
  "scores": {"done": 1, "partial": 0.5, "missed": 0},
  "source": "Decisión del Doctor Jefe (US-6.2, D-5)."
}
```

## Modelo de datos

**`adherence_checkins`** (nueva):

| Columna | Tipo | Nota |
| --- | --- | --- |
| `id` | bigint PK | |
| `patient_id` | FK `patients`, `cascadeOnDelete` | |
| `meal_plan_id` | FK `meal_plans`, `nullOnDelete`, nullable | Plan del que salió el menú |
| `date` | date | Día del menú, en `clinic_timezone` |
| `plan_day` | unsignedTinyInteger | Día del plan que tocaba (D-4) |
| `meal_key` | string(32) | `meal_plan.meals[].key` |
| `meal_label` | string(64) | Copia del nombre de la comida, para mostrarla aunque el plan cambie |
| `status` | string(8) | `done` \| `partial` \| `missed` |
| `recorded_by` | FK `users`, `nullOnDelete`, nullable | Cuenta del paciente que marcó |
| timestamps | | |

Índice único `(patient_id, date, meal_key)`. La migración es reversible (`down` borra la tabla) y no
mueve datos.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | La spec se aprueba antes de TK-6.2.1 |
| II. Fuente única de verdad clínica | ✅ | Los puntajes de D-5 van en `shared/clinical_thresholds.json` (`adherence.scores`) con su fuente. Las 8 semanas de la tarjeta son una decisión de presentación: van en la vista |
| III. Reglas clínicas trazables | ✅ | No hay reglas nuevas. La tarjeta muestra, junto a cada porcentaje, cuántas comidas lo forman |
| IV. Datos incompletos no son negativos | ✅ | Es el centro de AC-4: lo no marcado no existe en la tabla y no entra en el promedio; una semana sin marcas es "sin dato", nunca 0 %. Los días sin registro se informan aparte |
| V. Degradación segura | ✅ | No usa los servicios Python. Un día sin plan o sin comidas no rompe el portal: lo dice |
| VI. Los tests son la evidencia | ✅ | Tests PHPUnit por AC (tabla de abajo). Las fechas se fijan con `Carbon::setTestNow()` |
| VII. Privacidad y control de acceso | ✅ | La ruta del portal está en el grupo `auth` + `patient`, recibe solo una fecha y usa el paciente de la sesión. La tarjeta de la ficha está bajo `viewClinicalRecord`. El test de aislamiento de US-6.3 cubre la ruta nueva |
| VIII. Idioma | ✅ | Interfaz en español; fechas `dd/mm` y porcentajes con coma decimal |
| IX. Simplicidad | ✅ | Sin dependencias ni servicios nuevos. Una tabla nueva, justificada arriba frente a un JSON por día |

## Estrategia de pruebas

Archivos nuevos: `tests/Feature/Portal/PortalAdherenceTest.php` (portal) y
`tests/Feature/AdherenceSummaryTest.php` (ficha y cálculo).

| Criterio | Capa | Test |
| --- | --- | --- |
| AC-1 | Feature | `PortalAdherenceTest::test_portal_shows_todays_menu_with_a_choice_per_meal` |
| AC-1 | Feature | `PortalAdherenceTest::test_menu_rotates_from_the_day_the_plan_was_saved` (plan de 3 días: guardado el día 1, 4 y 7 vuelven al día 1; un cambio del plan reinicia la rotación) |
| AC-1 | Feature | `PortalAdherenceTest::test_patient_marks_each_meal` |
| AC-2 | Feature | `PortalAdherenceTest::test_patient_corrects_or_unmarks_today_and_yesterday` |
| AC-2 | Feature | `PortalAdherenceTest::test_older_or_future_days_cannot_be_recorded` (antes de ayer, mañana, fecha inválida) |
| AC-2 | Feature | `PortalAdherenceTest::test_today_is_the_clinic_day` (23:30 en Santiago, que en UTC ya es el día siguiente) |
| AC-2 | Feature | `PortalAdherenceTest::test_meals_not_in_the_days_menu_are_rejected` |
| AC-3 | Feature | `PortalAdherenceTest::test_a_patient_records_and_sees_only_their_own_meals` (dos pacientes con cuenta) |
| AC-3 | Feature | `PortalIsolationTest` (existente: la ruta nueva declara `patient` y el equipo recibe 403) |
| AC-4 | Feature | `AdherenceSummaryTest::test_weekly_percent_counts_partial_as_half_over_marked_meals` |
| AC-4 | Feature | `AdherenceSummaryTest::test_a_week_without_marks_is_no_data_not_zero` |
| AC-4 | Feature | `AdherenceSummaryTest::test_days_without_record_are_counted_from_the_first_plan_to_yesterday` |
| AC-4 | Feature | `AdherenceSummaryTest::test_clinical_record_shows_the_weekly_summary` |
| AC-4 | Feature | `AdherenceSummaryTest::test_scores_come_from_the_shared_file` |
| AC-5 | Feature | `PortalAdherenceTest::test_without_a_saved_plan_there_is_nothing_to_record` |

## Riesgos y decisiones

- **El plan se vuelve a guardar:** la rotación parte de nuevo desde ese día (D-4: "desde la fecha en que
  se guardó"). Los registros anteriores conservan su `meal_plan_id` y `meal_label`, así que el
  resumen semanal no cambia.
- **Hoy en Chile:** "hoy" y "ayer" se calculan en `clinic_timezone`, no en UTC. Entre las 21:00 y la
  medianoche de Chile, UTC ya está en el día siguiente.
- **Comidas sin alimentos:** si una comida del menú no tiene alimentos (por ejemplo, una colación vacía),
  no se ofrece para marcar ni se acepta.
- **Rango de la tarjeta:** 8 semanas alcanzan para el intervalo habitual entre consultas. Ver más atrás
  sería un criterio nuevo.
- **Registros del paciente sin cuenta:** si se le quita el acceso (US-6.3/AC-7), sus registros quedan
  en la ficha (`recorded_by` pasa a `null`). Siguen siendo datos del paciente.
