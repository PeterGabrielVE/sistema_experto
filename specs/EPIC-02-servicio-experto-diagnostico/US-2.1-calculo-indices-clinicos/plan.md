# Plan técnico · US-2.1

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

Spec retroactiva: el cálculo vive en `expert/app/indices.py` y lo expone `POST /evaluate` (y `indices` en la
respuesta). Los cortes salen de `shared/clinical_thresholds.json` (`expert/app/thresholds.py` en Python,
`config/clinical.php` en Laravel). Laravel calcula también el HOMA-IR, TyG y TG/HDL del historial de exámenes
(`LabResult`). La tarea etiqueta los tests existentes con `@pytest.mark.spec("US-2.1/AC-n")` y
`#[Group('US-2.1/AC-n')]`. Sin cambios de código.

**Alternativa descartada:** tests nuevos por criterio; los existentes ya los cubren.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Tests Python (`expert`) | `test_indices.py`, `test_api.py`, `test_thresholds.py`, `test_rules.py` | Marcas `spec` |
| Tests Laravel | `ClinicalThresholdsTest.php`, `PatientEvolutionTest.php` | Atributos `Group` |
| Código de producción | — | Sin cambios |

## Contratos

Sin cambios: `POST /evaluate` (servicio `expert`), con token según US-4.1.

## Modelo de datos

Sin cambios.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | Spec retroactiva |
| II. Fuente única de verdad clínica | ✅ | Todos los cortes en `shared/clinical_thresholds.json`, con fuente (AC-7) |
| III. Reglas clínicas trazables | ✅ | Los índices entregan su valor y el corte aplicado |
| IV. Datos incompletos no son negativos | ✅ | Sin dato no hay índice, nunca cero (AC-2) |
| V. Degradación segura | ✅ | Sin el servicio, Laravel clasifica por IMC (US-4.1/AC-4) |
| VI. Los tests son la evidencia | ✅ | Cada AC queda con tests etiquetados |
| VII. Privacidad y control de acceso | ✅ | Canal y token según US-4.1 |
| VIII. Idioma | ✅ | Etiquetas en español; código en inglés |
| IX. Simplicidad | ✅ | Sin cambios de código |

## Estrategia de pruebas

| Criterio | Capa | Tests existentes que se etiquetan |
| --- | --- | --- |
| AC-1 | Python | `test_indices.py::test_compute_reports_only_available_indices`, `test_bmi_categories_match_the_rules_table`, `test_blood_pressure_categories`, `test_atherogenic_indices`, `test_aip_risk`, `test_fatty_liver_index`, `test_fli_categories`, `test_findrisc`, `test_findrisc_risk_bands`; `test_api.py::test_evaluate`, `test_indices_only` |
| AC-2 | Python | `test_indices.py::test_missing_values_give_no_index`, `test_compute_reports_only_available_indices`; `test_rules.py::test_suggests_the_missing_data` |
| AC-3 | Python / Laravel | `test_indices.py::test_same_values_as_the_laravel_app`, `test_rounds_half_up_like_php`; `PatientEvolutionTest::test_homa_ir_values_match_the_lab_results_page` |
| AC-4 | Python | `test_indices.py::test_friedewald_only_below_400_mg_dl_of_triglycerides`, `test_calculated_ldl_only_when_the_lab_did_not_report_it`, `test_castelli_ii_uses_friedewald_when_the_lab_did_not_report_ldl` |
| AC-5 | Python | `test_indices.py::test_castelli_cut_offs_depend_on_sex`, `test_findrisc_waist_points` |
| AC-6 | Python | `test_indices.py::test_findrisc_needs_every_answer_and_does_not_apply_to_diabetes_or_minors`, `test_prediabetes_answers_the_high_glucose_question`, `test_findrisc_age_points`, `test_findrisc_bmi_points` |
| AC-7 | Python / Laravel | `test_thresholds.py::test_loaded_from_the_shared_file`, `test_a_changed_cut_off_changes_the_result`; `ClinicalThresholdsTest::test_config_is_the_shared_file`, `test_models_use_the_configured_cut_offs` |
| AC-8 | Python | `test_api.py::test_rejects_invalid_facts` |

Se corren con `docker compose exec expert python -m pytest tests --spec US-2.1` y
`php artisan test --group=US-2.1/AC-n`.

## Riesgos y decisiones

- **Tareas del backlog:** TK-2.1.1 (*Arquitectura FastAPI/Flask*) y TK-2.1.2 (*Implementación de Fórmulas
  Clínicas*) siguen la numeración del resto del backlog.
