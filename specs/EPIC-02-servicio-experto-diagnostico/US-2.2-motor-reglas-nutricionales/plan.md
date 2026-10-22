# Plan técnico · US-2.2

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

Spec retroactiva. Las evaluaciones y reglas viven en `expert/app/rules.py` (GLU, RI, SM, ANT, LIP, PA, HEP,
FIN, DAT), la energía y los macronutrientes en `expert/app/nutrition.py` (MAC-01 a MAC-09 y las reglas
configuradas CFG). En Laravel: `MacroRuleController` y `MacroRulePolicy` (solo el Doctor Jefe),
`DiagnosisController::macros` (el formulario de consulta pide la distribución) y la página de resultado. La
tarea etiqueta los tests existentes. Sin cambios de código.

**Alternativa descartada:** tests nuevos por criterio; los existentes ya los cubren.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Tests Python (`expert`) | `test_rules.py`, `test_nutrition.py`, `test_api.py` | Marcas `spec` |
| Tests Laravel | `MacroRuleTest.php`, `MacroPlanTest.php`, `ExpertDiagnosisTest.php`, `Api/DiagnosisApiTest.php` | Atributos `Group` |
| Código de producción | — | Sin cambios |

## Contratos

Sin cambios: `POST /evaluate`, `POST /macro-plan`, `GET /rules` (servicio `expert`) y las rutas
`macro-rules.*` y `diagnosis.macros` de Laravel.

## Modelo de datos

Sin cambios.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | Spec retroactiva |
| II. Fuente única de verdad clínica | ✅ | Cortes y porcentajes en `shared/clinical_thresholds.json`; variables y rangos de las reglas configurables también |
| III. Reglas clínicas trazables | ✅ | Es el centro de AC-2: id estable, evidencia y fuente por hallazgo |
| IV. Datos incompletos no son negativos | ✅ | "Indeterminado" mientras los criterios faltantes puedan cambiar el resultado (AC-3) |
| V. Degradación segura | ✅ | Sin servicio, el formulario se llena a mano y la página se muestra sin la evaluación; el médico decide (AC-7) |
| VI. Los tests son la evidencia | ✅ | Cada AC queda con tests etiquetados |
| VII. Privacidad y control de acceso | ✅ | Solo los médicos ven la evaluación (AC-9); solo el Doctor Jefe configura reglas (AC-8) |
| VIII. Idioma | ✅ | Hallazgos en español |
| IX. Simplicidad | ✅ | Sin cambios de código |

## Estrategia de pruebas

| Criterio | Capa | Tests existentes que se etiquetan |
| --- | --- | --- |
| AC-1 | Python / Laravel | `test_rules.py::test_patient_with_insulin_resistance_and_metabolic_syndrome`, `test_one_altered_indicator_is_possible_insulin_resistance`, `test_atherogenic_profile`, `test_metabolic_syndrome_uses_sex_specific_cut_offs`; `ExpertDiagnosisTest::test_result_page_shows_the_expert_evaluation` |
| AC-2 | Python | `test_rules.py::test_rule_ids_are_unique`, `test_healthy_patient_has_no_findings`, `test_ldl_goal_is_stricter_with_diabetes`, `test_fatty_liver_finding`, `test_findrisc_finding`; `test_api.py::test_rule_catalog` |
| AC-3 | Python | `test_rules.py::test_metabolic_syndrome_is_indeterminate_with_missing_criteria`, `test_metabolic_syndrome_is_absent_when_missing_criteria_cannot_reach_three`, `test_suggests_the_missing_data`, `test_missing_findrisc_answers_only_when_it_applies` |
| AC-4 | Python | `test_rules.py::test_known_conditions_count_as_treated_criteria`, `test_diabetes_range_alert_only_without_known_diabetes` |
| AC-5 | Python | `test_rules.py::test_warns_that_cut_offs_are_for_adults`; `test_nutrition.py::test_no_plan_for_minors_or_without_anthropometry` |
| AC-6 | Python | `test_nutrition.py::test_bmr_mifflin_st_jeor`, `test_reference_weight_caps_at_bmi_25`, `test_healthy_adult_gets_the_base_distribution`, `test_metabolic_patient_lowers_carbohydrates_and_saturated_fat`, `test_diabetes_takes_the_lowest_carbohydrate_share`, `test_protein_is_raised_to_the_minimum_g_per_kg`, `test_underweight_gets_a_surplus`, `test_missing_activity_assumes_very_light`, `test_the_most_restrictive_value_wins` |
| AC-7 | Laravel | `MacroPlanTest::test_returns_the_plan_and_the_form_fields`, `test_without_a_plan_there_are_no_fields`, `test_service_not_configured_or_down`, `test_consultation_form_asks_the_expert_service`, `test_validation_and_authorization`; `ExpertDiagnosisTest::test_result_page_shows_the_macronutrient_distribution` |
| AC-8 | Laravel / Python | `MacroRuleTest` (5 tests); `test_nutrition.py::test_configured_rule_adjusts_the_glycemic_load`, `test_configured_rule_does_not_apply_below_the_value_or_without_data`, `test_configured_lab_variable_and_energy`; `test_api.py::test_evaluate_with_configured_macro_rules`, `test_rejects_invalid_macro_rules` |
| AC-9 | Laravel | `ExpertDiagnosisTest::test_administrator_does_not_get_the_clinical_evaluation`; `Api\DiagnosisApiTest::test_administrator_gets_the_diagnosis_without_the_clinical_evaluation` |

## Riesgos y decisiones

- **Tarea del backlog:** TK-2.2.1 (*Árbol de Decisión Nutricional*) sigue la numeración del resto del backlog.
