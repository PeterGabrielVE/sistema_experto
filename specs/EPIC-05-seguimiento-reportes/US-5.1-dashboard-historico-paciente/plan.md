# Plan técnico · US-5.1

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

Un endpoint JSON de Laravel, `GET /patient/{patient}/evolution?period=…`, arma las dos series
(peso y HOMA-IR), el corte de HOMA-IR y los indicadores de cada punto y serie (D-1). Toda la lógica
clínica y de filtrado vive en un servicio PHP probado con PHPUnit. La ficha clínica incluye una tarjeta
nueva con un canvas que un módulo JS **delgado** llena consultando ese endpoint: solo dibuja lo que el
servidor ya calculó, y vuelve a consultar al cambiar el período (D-3).

El HOMA-IR sale de `LabResult::homaIr()`, el mismo método que usan la página de exámenes y la
evaluación, así que el valor es igual en todo el sistema (AC-6). El corte se lee de
`config('clinical.insulin_resistance.homa_ir')`, que viene de `shared/clinical_thresholds.json`.

**Alternativas descartadas:**

- *Unir en Blade las series que ya arman `ClinicalMeasurementService::series()` y
  `LabResultService::series()`.* Contradice D-1, que pide el endpoint como fuente de la gráfica. Además,
  esas series usan fechas `d/m/Y` como categorías y un máximo de 60 registros, y aquí hacen falta fechas
  reales en un eje de tiempo compartido y todo el historial.
- *Eje de tiempo con `chartjs-adapter-date-fns` y línea de corte con `chartjs-plugin-annotation`.* Son dos
  dependencias nuevas para algo que Chart.js resuelve sin ellas: eje `linear` con milisegundos y
  etiquetas formateadas en `es-CL`, y el corte como un dataset de dos puntos con línea discontinua
  (constitución IX).

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Laravel | `app/Services/PatientEvolutionService.php` (nuevo) | Series, filtro por período, corte y conteos |
| Laravel | `app/Http/Controllers/PatientEvolutionController.php` (nuevo, invocable) | Autoriza con `viewClinicalRecord`, valida `period` y responde JSON |
| Laravel | `routes/web.php` | `patient/{patient}/evolution` → `patient.evolution`, dentro del grupo `can:viewAny,Patient` |
| Vistas | `resources/views/clinical_records/show.blade.php` | Tarjeta *Evolución* con canvas, botones de período y mensajes por serie |
| Front-end | `resources/js/patient-evolution-chart.js` (nuevo), `vite.config.js` | Gráfica de dos ejes que consume el endpoint |
| Servicios Python, `shared/`, base de datos | — | Sin cambios |

## Contratos

`GET /patient/{patient}/evolution?period=6m` (sesión web; `period` ∈ `all` (por defecto), `3m`, `6m`, `12m`):

```json
{
  "period": {"key": "6m", "from": "2026-04-10", "to": "2026-10-10"},
  "series": {
    "weight": {
      "label": "Peso",
      "unit": "kg",
      "points": [
        {"date": "2026-05-02", "value": 84.5},
        {"date": "2026-08-20", "value": 81.0}
      ],
      "enough_data": true
    },
    "homa_ir": {
      "label": "HOMA-IR",
      "unit": "",
      "threshold": 2.5,
      "points": [
        {"date": "2026-05-02", "value": 4.72, "above_threshold": true},
        {"date": "2026-09-15", "value": 2.31, "above_threshold": false}
      ],
      "excluded": 1,
      "enough_data": true
    }
  }
}
```

- `points`: solo registros válidos del período (peso informado; HOMA-IR calculable), en orden
  cronológico (fecha y luego `id`) con la fecha original en ISO 8601 (AC-7).
- `excluded`: exámenes del período sin glicemia o sin insulina en ayunas (AC-2).
- `enough_data`: `false` con menos de dos puntos; cada serie se evalúa por separado (AC-4).
- `above_threshold`: `value > threshold`, igual que la regla del servicio experto ("alterado si es
  mayor al valor") (AC-3).
- Períodos: `from` = hoy − *n* meses, ambos extremos incluidos, con la zona horaria de la aplicación.
- Errores: `403` sin `viewClinicalRecord` (AC-5); `422` si `period` es otro valor.

## Modelo de datos

Sin cambios: se leen `clinical_measurements` (`measured_at`, `weight_kg`) y `lab_results`
(`taken_at`, `fasting_glucose`, `fasting_insulin`). Ambas tablas ya tienen índice `(patient_id, fecha)`.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | La spec se aprueba antes de TK-5.1.2 |
| II. Fuente única de verdad clínica | ✅ | Corte desde `shared/clinical_thresholds.json` vía `config('clinical…')`; HOMA-IR desde `LabResult::homaIr()` |
| III. Reglas clínicas trazables | ✅ | La respuesta entrega el corte aplicado junto a cada punto |
| IV. Datos incompletos no son negativos | ✅ | Los exámenes sin datos no se grafican como cero: se excluyen y se cuentan (AC-2) |
| V. Degradación segura | ✅ | No depende de los servicios Python; si falla el endpoint, el resto de la ficha se muestra y la tarjeta dice que no se pudo cargar |
| VI. Los tests son la evidencia | ✅ | Lógica en el servidor con tests PHPUnit; lo visual se revisa con la lista manual de abajo |
| VII. Privacidad y control de acceso | ✅ | `viewClinicalRecord` (solo médicos) en la ficha y en el endpoint, con test 403 |
| VIII. Idioma | ✅ | Etiquetas en español; fechas `dd-mm-aaaa` y coma decimal en la gráfica |
| IX. Simplicidad | ✅ | Sin dependencias, tablas ni servicios nuevos |

## Estrategia de pruebas

Los tests nuevos van en `tests/Feature/PatientEvolutionTest.php`, salvo donde se indica otro archivo.

| Criterio | Capa | Test |
| --- | --- | --- |
| AC-1 | Laravel (vista) | `ClinicalRecordTest::test_show_page_includes_the_evolution_chart` |
| AC-1, AC-6 | Laravel (API) | `test_returns_weight_and_homa_ir_series_in_chronological_order` |
| AC-2 | Laravel (API) | `test_exams_without_glucose_or_insulin_are_excluded_and_counted` |
| AC-3 | Laravel (API) | `test_includes_the_homa_ir_cut_off_and_flags_points_above_it` |
| AC-4 | Laravel (API) | `test_each_series_reports_whether_it_has_enough_data` |
| AC-4, AC-7 | Laravel (vista) | `ClinicalRecordTest::test_show_page_includes_the_evolution_chart` (botones y mensajes) |
| AC-5 | Laravel (API) | `test_administrator_gets_403` |
| AC-5 | Laravel (vista) | `ClinicalRecordTest::test_administrator_cannot_read_or_write_clinical_data` (existente, se etiqueta) |
| AC-6 | Laravel (API) | `test_homa_ir_values_match_the_lab_results_page` |
| AC-7 | Laravel (API) | `test_period_filter_applies_to_both_series`, `test_rejects_an_unknown_period` |

Los tests de período fijan la fecha con `Carbon::setTestNow()`. Los ids se toman de los modelos
creados, no se escriben fijos, para que la suite pase en SQLite y MySQL.

**Verificación manual en el PR de TK-5.1.1** (el proyecto no tiene tests de JS, y agregar Vitest solo
para esto contradiría IX):

1. Paciente con mediciones y exámenes en fechas distintas: dos ejes (kg a la izquierda, HOMA-IR a la
   derecha) sobre un solo eje de tiempo.
2. Línea discontinua del corte 2,5 y puntos de HOMA-IR sobre el corte en otro color.
3. Paciente con un solo examen: mensaje en la serie HOMA-IR, con enlace para registrar un examen, y el
   peso se sigue mostrando.
4. Botones 3, 6 y 12 meses y *Todo*: ambas series cambian; al recargar la página vuelve a *Todo*.
5. Con el endpoint caído (por ejemplo, devolviendo 500): la ficha se muestra y la tarjeta indica el error.

## Riesgos y decisiones

- **Paciente sin ficha clínica:** `ClinicalRecordController::show` redirige al formulario si no hay ficha,
  así que la gráfica solo se ve en pacientes que tienen una. Se acepta: la spec ubica la gráfica en la
  ficha. Si el equipo quiere verla sin ficha, es un cambio de spec.
- **Historiales largos:** sin el tope de 60 registros de las gráficas actuales. Con el volumen de una
  consulta (decenas de registros por paciente) la respuesta es pequeña; si creciera, se agrega un tope
  en la spec como NFR.
- **Zona horaria:** `config/app.php` usa `UTC`. Entre las 21:00 y la medianoche de Chile, "hoy" ya es el
  día siguiente en UTC, y el límite del período se corre un día. Para un filtro de meses no afecta la
  lectura clínica; no se cambia aquí la zona de toda la aplicación, porque afectaría a otras fechas. Si se
  quiere exactitud, el servicio calcula "hoy" con `America/Santiago`.
- **Fechas:** se envían en ISO y se formatean en el navegador; el eje se construye con milisegundos
  desde las fechas ISO al mediodía, para que la zona horaria del navegador no corra un día.
