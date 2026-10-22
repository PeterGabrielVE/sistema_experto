# Plan técnico · US-1.2

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

AC-1 a AC-8 ya existen y tienen tests: se etiquetan, como en las otras specs retroactivas. AC-9 es trabajo
nuevo: los rangos de referencia (`max`/`min`) de `LabResult::ANALYTES` pasan a un bloque `lab_reference` de
`shared/clinical_thresholds.json`, con su fuente (D-1), y `LabResult::isOutOfRange()` los lee de
`config('clinical.lab_reference')`. La GGT tiene un rango por sexo (D-2): el método toma el sexo del paciente del
examen. En `ANALYTES` quedan las etiquetas, las unidades y los límites posibles del formulario (`limits`), que
validan datos imposibles y no son valores clínicos de referencia.

**Alternativa descartada:** dejar los rangos en el código con un comentario de la fuente. La constitución II pide
que vivan en el archivo compartido; además, el Doctor Jefe podrá ajustarlos sin cambiar código.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Datos compartidos | `shared/clinical_thresholds.json` | Bloque `lab_reference` con los rangos y sus fuentes |
| Laravel | `app/Models/LabResult.php` | Sin `max`/`min` en `ANALYTES`; `referenceRange($analito, $sexo)` y `analytes($sexo)` leen la configuración; `isOutOfRange()` usa el sexo del paciente |
| Vistas | `resources/views/lab_results/form.blade.php`, `index.blade.php` | El formulario Vue y la columna de referencia reciben los rangos del sexo del paciente |
| Front-end | `resources/js/utils/lab.js` | Sin rango de referencia, el valor no se juzga (antes habría dicho "normal") |
| Tests Laravel | Los de la tabla de abajo | Etiquetas `#[Group(...)]`; tests nuevos de AC-9 |
| Servicios Python | — | Sin cambios: no usan estos rangos |

## Contratos

`shared/clinical_thresholds.json` (bloque nuevo):

```json
"lab_reference": {
  "_about": "Rangos de referencia con que el historial de exámenes marca un valor fuera de rango (US-1.2). max: alto por encima; min: bajo por debajo.",
  "fasting_glucose": {"max": 99}, "hba1c": {"max": 5.6},
  "total_cholesterol": {"max": 199}, "ldl": {"max": 129}, "triglycerides": {"max": 149}, "hdl": {"min": 40},
  "fasting_insulin": {"max": 25},
  "ggt": {"max": {"M": 40, "H": 60}},
  "source": "ADA Standards of Care (glicemia en ayunas < 100 mg/dL y HbA1c < 5,7 %); NCEP ATP III (colesterol total < 200, LDL < 130, triglicéridos < 150 y HDL ≥ 40 mg/dL); insulina basal y GGT: rango habitual de laboratorio, que varía según el laboratorio (GGT por sexo, US-1.2 D-2)."
}
```

Sexo según `patients.gender`: `M` mujer, `H` hombre. Sin sexo registrado, la GGT no se marca (no se supone uno).

## Modelo de datos

Sin cambios.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | La parte retroactiva documenta lo vigente; AC-9 se implementa con la spec aprobada |
| II. Fuente única de verdad clínica | ✅ | Es el objetivo de AC-9: los rangos salen del código y van al archivo compartido con su fuente |
| III. Reglas clínicas trazables | ✅ | No aplica |
| IV. Datos incompletos no son negativos | ✅ | Un analito sin valor no se marca; sin sexo, la GGT no se marca |
| V. Degradación segura | ✅ | No depende de los servicios Python |
| VI. Los tests son la evidencia | ✅ | Tests etiquetados por AC y tests nuevos de AC-9 |
| VII. Privacidad y control de acceso | ✅ | Políticas existentes; auditoría sin valores |
| VIII. Idioma | ✅ | Coma decimal en formularios e historial |
| IX. Simplicidad | ✅ | Sin dependencias ni tablas nuevas |

## Estrategia de pruebas

| Criterio | Capa | Tests |
| --- | --- | --- |
| AC-1 | Feature | `ClinicalMeasurementTest::test_doctor_registers_a_measurement`, `test_form_suggests_the_last_height` |
| AC-2 | Feature | `LabResultTest::test_doctor_registers_a_lab_result`, `test_vue_form_saves_with_json` |
| AC-3 | Feature | `ClinicalMeasurementTest::test_validation`, `LabResultTest::test_validation` |
| AC-4 | Feature | `ClinicalMeasurementTest::test_measurement_links_to_a_consultation_of_the_same_patient`, `LabResultTest::test_links_to_a_consultation_of_the_same_patient_only` |
| AC-5 | Feature | `ClinicalMeasurementTest::test_history_shows_indicators_and_variation`, `test_empty_history`, `test_indicators`; `LabResultTest::test_history_shows_indicators_and_out_of_range_values`, `test_empty_history`, `test_indicators` |
| AC-6 | Feature | `ClinicalMeasurementTest::test_new_consultation_is_prefilled_with_last_weight_and_height`, `test_new_consultation_without_measurements` |
| AC-7 | Feature | `ClinicalMeasurementTest` y `LabResultTest`: `test_doctor_edits_*`, `test_only_author_or_chief_doctor_can_delete`, `test_administrator_cannot_read_or_write_*`, `test_*_of_another_patient_is_not_found` |
| AC-8 | Feature | `ClinicalMeasurementTest::test_changes_are_audited_without_values`, `LabResultTest::test_changes_are_audited_without_values` |
| AC-9 | Feature | `LabResultTest::test_reference_ranges_come_from_the_shared_file` (nuevo: cambiar un rango en la configuración cambia la marca), `test_ggt_range_depends_on_sex` (nuevo: 50 U/L es alto en una mujer y normal en un hombre; sin sexo, sin marca) |

## Riesgos y decisiones

- **GGT entre 40 y 60 U/L:** antes se marcaba sobre 50 para todos; ahora una mujer con 45 U/L aparece alta y un
  hombre con 55 U/L, normal. Es lo que pide D-2.
- **Tareas del backlog:** TK-1.2.1 (*Migración de Consultas y Laboratorio*) y TK-1.2.2 (*Formulario de Captura de
  Consulta*) siguen la numeración del resto del backlog; TK-1.2.3 es nueva (AC-9).
