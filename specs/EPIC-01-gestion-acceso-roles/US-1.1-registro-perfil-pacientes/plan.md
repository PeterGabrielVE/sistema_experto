# Plan técnico · US-1.1

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

Es una spec retroactiva: el comportamiento ya existe y tiene tests. El plan vincula cada criterio con los
tests que lo prueban y la tarea los etiqueta con `#[Group('US-1.1/AC-n')]`. No cambia código de producción.

Piezas existentes: `PatientController` y `PatientRequest` (con la regla `Rut`), el formulario Vue
`PatientForm.vue`, `PatientResource`, `PatientService` (alta, edición, eliminación con eventos),
`ClinicalRecordController` y `ClinicalRecordRequest`, `PatientPolicy` (permisos del equipo) y
`RecordAuditTrail` (auditoría).

**Alternativa descartada:** escribir tests nuevos por criterio. Los que existen ya cubren cada uno, y
duplicarlos no agrega evidencia.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Tests Laravel | Los de la tabla de abajo | Solo atributos `#[Group(...)]` |
| Código de producción | — | Sin cambios |

## Contratos

Sin cambios: rutas `patient.*` y `patient.clinical-record.*` del grupo `can:viewAny,Patient`.

## Modelo de datos

Sin cambios.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | Spec retroactiva: documenta lo vigente; un cambio futuro parte de esta spec |
| II. Fuente única de verdad clínica | ✅ | No hay valores clínicos en esta historia |
| III. Reglas clínicas trazables | ✅ | No aplica |
| IV. Datos incompletos no son negativos | ✅ | Las respuestas FINDRISC no contestadas quedan "sin información" (AC-5) |
| V. Degradación segura | ✅ | Registrar pacientes y fichas no depende de los servicios Python |
| VI. Los tests son la evidencia | ✅ | Cada AC queda con tests etiquetados |
| VII. Privacidad y control de acceso | ✅ | `PatientPolicy`: el Administrador no ve la ficha; eliminar, solo el autor o el Doctor Jefe; auditoría sin valores |
| VIII. Idioma | ✅ | Interfaz en español; RUT en formato chileno |
| IX. Simplicidad | ✅ | Sin cambios de código |

## Estrategia de pruebas

| Criterio | Capa | Tests existentes que se etiquetan |
| --- | --- | --- |
| AC-1 | Feature | `Access\PatientManagementTest::test_doctor_registers_patient`, `PatientFormApiTest::test_store_returns_the_resource_and_next_step`, `PatientModelTest::test_age_is_derived_from_birthdate` |
| AC-2 | Unit / Feature | `RutTest` (3 tests), `PatientManagementTest::test_rut_must_be_unique_in_any_format`, `test_doctor_updates_patient_keeping_rut`, `PatientFormApiTest::test_update_via_json_keeps_the_rut` |
| AC-3 | Feature | `PatientManagementTest::test_patient_validation_rules`, `PatientFormApiTest::test_validation_errors_are_returned_as_json_per_field`, `PatientModelTest::test_patient_email_is_optional_validated_and_normalized` |
| AC-4 | Feature | `PatientFormApiTest::test_list_paginates_and_searches_on_the_server` (nombre, RUT con y sin formato, correo) |
| AC-5 | Feature | `ClinicalRecordTest::test_doctor_registers_the_clinical_record`, `test_doctor_edits_the_clinical_record`, `test_findrisc_answers`, `test_validation` |
| AC-6 | Feature | `ClinicalRecordTest::test_show_without_record_goes_to_the_form` |
| AC-7 | Feature | `ClinicalRecordTest::test_administrator_cannot_read_or_write_clinical_data`, `Access\AuthorizationTest::test_admin_cannot_modify_patients`, `test_permission_matrix` |
| AC-8 | Feature | `Access\PolicyTest::test_only_author_or_chief_doctor_can_delete_a_patient`, `test_other_doctor_gets_403_with_reason`, `ClinicalRecordTest::test_record_is_deleted_with_the_patient` |
| AC-9 | Feature | `ClinicalRecordTest::test_changes_are_audited_without_values`, `EventsTest::test_patient_lifecycle_events`, `ListenersAndJobsTest::test_audit_trail_records_actor_and_record` |

## Riesgos y decisiones

- **Edición de datos personales sin auditoría:** AC-9 lo deja dicho. Auditarla sería un criterio nuevo; por ahora
  no lo pide nadie y los datos personales no son datos clínicos.
- **Tareas del backlog:** se usan los ids TK-1.1.1 (*Migración y Modelo de Pacientes*) y TK-1.1.2 (*Formulario
  UI de Pacientes*) siguiendo la numeración del resto del backlog; si en Notion son otros, se corrigen aquí.
