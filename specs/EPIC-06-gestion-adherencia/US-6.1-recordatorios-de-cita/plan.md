# Plan técnico · US-6.1

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

Una tabla nueva `appointments` guarda la próxima consulta (D-1) y, en la misma fila, el estado de su
recordatorio. `AppointmentService` registra, cambia y cancela la cita: lo usan el formulario de la
consulta (campos opcionales *Próxima consulta* e *Indicaciones*) y una tarjeta nueva en la ficha clínica.
Cambiar la fecha deja el recordatorio pendiente otra vez (AC-7).

Un comando `appointments:send-reminders`, programado a las 09:00 America/Santiago (D-2), busca las citas
del día siguiente en esa zona horaria. A las que no corresponde enviar les deja el motivo (AC-4). Las demás
las **reclama** con un `UPDATE … WHERE reminder_status IS NULL` y encola un job `SendAppointmentReminder`
por cita. Ese `UPDATE` condicional es atómico en SQLite y MySQL, así que dos ejecuciones del comando no
pueden reclamar la misma cita (AC-3). El job envía el Mailable `AppointmentReminderMail` (TK-6.1.1 del
backlog), se reintenta con espera y en `failed()` marca la cita como fallida. Como cada cita va en su propio
job, un fallo no detiene los demás envíos (AC-5).

El consentimiento son dos columnas en `patients`: la casilla `appointment_reminders`, desactivada por
defecto, y `reminders_unsubscribed_at`. La casilla se agrega al formulario Vue del paciente (D-3). El enlace
de baja es una URL firmada sin sesión: `GET` muestra una confirmación y `POST` da de baja, para que los
filtros de correo que abren enlaces no den de baja al paciente por error (AC-8).

**Alternativas descartadas:**

- *Columna `next_appointment_at` en `patients`.* No tiene estado *cancelada* ni dónde guardar el resultado
  del recordatorio de cada cita, que piden AC-3 y AC-4 (y D-1 la descarta).
- *Tabla aparte `appointment_reminders` con un registro por envío.* Hay a lo sumo un recordatorio por
  cita y fecha, así que tres columnas en `appointments` bastan (constitución IX). Si se agregan más avisos
  (por ejemplo, una semana antes), ese sería el momento de separarla.
- *`Notification` encolada en vez de job + Mailable.* Para marcar la cita como enviada habría que escuchar
  `NotificationSent`. El job deja en un solo lugar el envío, los reintentos y el resultado. Además, el
  backlog pide un Mailable.
- *Reutilizar `Schedule`/`schedules`.* Esa tabla guarda los horarios de comida de la base de conocimiento
  (desayuno, almuerzo, cena) y no tiene relación con las citas.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Base de datos | `database/migrations/…_create_appointments_table.php` (nueva) | Tabla `appointments` |
| Base de datos | `database/migrations/…_add_appointment_reminders_to_patients_table.php` (nueva) | Consentimiento y baja |
| Laravel | `app/Models/Appointment.php` (nuevo), `database/factories/AppointmentFactory.php` (nueva) | Modelo, estados, `scopeScheduled`, `reminderLabel()` |
| Laravel | `app/Models/Patient.php` | `appointments()`, `nextAppointment()`, casts y `fillable` del consentimiento, `canReceiveReminders()` |
| Laravel | `app/Services/AppointmentService.php` (nuevo) | `schedule()`, `cancel()`, `dueForReminder()`, `claim()`, `skip()` |
| Laravel | `app/Http/Controllers/AppointmentController.php` (nuevo) | `update` (programar o cambiar) y `destroy` (cancelar) desde la ficha |
| Laravel | `app/Http/Requests/AppointmentRequest.php` (nuevo), `StoreDiagnosisRequest.php` | Fecha futura e indicaciones (máx. 500) |
| Laravel | `app/Services/DiagnosisService.php`, `DiagnosisController.php` | Al guardar la consulta, programa la cita si viene `next_appointment_at` |
| Laravel | `app/Http/Requests/PatientRequest.php`, `app/Http/Resources/PatientResource.php`, `app/Services/PatientService.php` | Casilla de consentimiento; al reactivarla se borra `reminders_unsubscribed_at` |
| Laravel | `app/Http/Controllers/ReminderUnsubscribeController.php` (nuevo) | `show` y `store` con middleware `signed` y `throttle` |
| Laravel | `app/Mail/AppointmentReminderMail.php` (nuevo), `app/Jobs/SendAppointmentReminder.php` (nuevo) | Correo y envío con reintentos |
| Laravel | `routes/console.php` | Comando `appointments:send-reminders` y su programación |
| Laravel | `routes/web.php` | Rutas de la cita (grupo `can:viewAny,Patient`) y de baja (fuera de `auth`) |
| Laravel | `config/app.php`, `.env.example` | `clinic_timezone` = `env('CLINIC_TIMEZONE', 'America/Santiago')` |
| Vistas | `resources/views/diagnoses/create.blade.php` | Campos *Próxima consulta* (`datetime-local`) e *Indicaciones* |
| Vistas | `resources/views/clinical_records/_appointment.blade.php` (nueva), `show.blade.php` | Tarjeta *Próxima consulta*: fecha, profesional, indicaciones, estado del recordatorio, cambiar y cancelar |
| Vistas | `resources/views/mail/appointment-reminder.blade.php` (nueva) | Correo Markdown |
| Vistas | `resources/views/reminders/unsubscribe.blade.php`, `unsubscribed.blade.php` (nuevas) | Confirmación pública, sin datos del paciente |
| Front-end | `resources/js/components/patients/PatientForm.vue` | Casilla *Acepta recordatorios de cita por correo* y aviso si se dio de baja |
| Servicios Python, `shared/` | — | Sin cambios |

## Contratos

**Formulario de la consulta** (`POST /diagnosis`, campos nuevos y opcionales):

| Campo | Regla |
| --- | --- |
| `next_appointment_at` | `nullable`, `date_format:Y-m-d\TH:i`, posterior a ahora; se interpreta en `clinic_timezone` |
| `next_appointment_notes` | `nullable`, `string`, `max:500`; solo se acepta junto con la fecha |

**Ficha clínica** (sesión web; solo médicos, `PatientPolicy::update`):

- `PUT /patient/{patient}/appointment` → `patient.appointment.update`, con `scheduled_at` (mismo formato
  y reglas) y `notes`. Programa la cita o cambia la programada. Redirige a la ficha con un mensaje.
- `DELETE /patient/{patient}/appointment` → `patient.appointment.destroy`. Cancela la cita programada:
  `422` si no hay ninguna y `403` sin permiso.

**Baja de recordatorios** (pública, URL firmada sin expiración):

- `GET /recordatorios/baja/{patient}?signature=…` → `reminders.unsubscribe`. Muestra "¿Desea dejar de
  recibir recordatorios de consulta?" con un botón, sin nombre ni datos del paciente.
- `POST` a la misma URL firmada: `appointment_reminders = false` y `reminders_unsubscribed_at = now()`.
  Responde con la página *Listo* y es idempotente.
- Firma inválida o alterada: `403`. Límite de `throttle:10,1` por IP.

**Comando:** `php artisan appointments:send-reminders [--date=AAAA-MM-DD]`. Sin `--date`, usa "mañana" en
`clinic_timezone`. Imprime un resumen por estado y no imprime datos del paciente:

```
Recordatorios para el 22-10-2026: 5 encolados, 2 omitidos (1 sin correo, 1 sin consentimiento), 1 cancelada.
```

**Programación** (`routes/console.php`):

```php
Schedule::command('appointments:send-reminders')
    ->dailyAt('09:00')->timezone(config('app.clinic_timezone'))->withoutOverlapping();
```

**Correo** (asunto y contenido; no menciona la especialidad ni datos clínicos, AC-6):

```
Asunto: Recordatorio: su consulta es mañana, jueves 22 de octubre, a las 10:30

Hola Ana,
le recordamos su próxima consulta:
  Fecha: jueves 22 de octubre de 2026
  Hora: 10:30
  Profesional: Dra. Carolina Muñoz
  Indicaciones: Venir en ayuno de 8 horas para el examen de glicemia.

Si no desea recibir estos recordatorios: [Dejar de recibir recordatorios]
```

El correo solo usa estos campos: `patients.first_name`, `appointments.scheduled_at` (en
`clinic_timezone`), `users.name` del profesional, `appointments.notes` y el enlace firmado. Las
indicaciones las escribe el médico como texto para el paciente; el formulario avisa que se envían por
correo.

## Modelo de datos

**`appointments`** (nueva):

| Columna | Tipo | Nota |
| --- | --- | --- |
| `id` | bigint PK | |
| `patient_id` | FK `patients`, `cascadeOnDelete` | |
| `user_id` | FK `users`, `nullOnDelete`, nullable | Profesional a cargo: quien la programa |
| `diagnosis_id` | FK `diagnoses`, `nullOnDelete`, nullable | Consulta donde se programó (si fue desde ahí) |
| `scheduled_at` | datetime | En UTC |
| `notes` | string(500), nullable | Indicaciones de preparación |
| `status` | string(16) | `scheduled` \| `cancelled` |
| `cancelled_at` | datetime, nullable | |
| `reminder_status` | string(16), nullable | `null` pendiente \| `queued` \| `sent` \| `skipped` \| `failed` |
| `reminder_reason` | string(32), nullable | `no_email` \| `no_consent` \| `unsubscribed` \| `cancelled` \| `mail_error` \| `queue_error` (no se pudo encolar el job) |
| `reminder_at` | datetime, nullable | Cuándo cambió `reminder_status` por última vez |
| timestamps | | |

Índices: `(status, scheduled_at)` para el comando y `(patient_id, status)` para la ficha. La regla de
"a lo sumo una programada por paciente" (D-1) la aplica `AppointmentService::schedule()`: si existe una
programada futura la actualiza y si no, crea una. No se usa un índice único parcial porque MySQL no lo
soporta.

**`patients`** (columnas nuevas): `appointment_reminders` boolean `default false` y
`reminders_unsubscribed_at` datetime nullable. Los pacientes existentes quedan sin consentimiento, como
pide D-3.

Ambas migraciones son reversibles (`down` borra la tabla y las columnas) y no migran datos, porque hoy no
hay citas registradas.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | Ninguna tarea empieza antes de que la spec esté `approved` |
| II. Fuente única de verdad clínica | ✅ | No hay valores clínicos nuevos. La zona horaria y el horario no son clínicos: van en `config/app.php` y `routes/console.php` |
| III. Reglas clínicas trazables | ✅ | No aplica: no hay reglas clínicas nuevas |
| IV. Datos incompletos no son negativos | ✅ | Un paciente sin correo o sin consentimiento no se trata como "enviado": queda `skipped` con su motivo y la ficha lo muestra (AC-4) |
| V. Degradación segura | ✅ | La cita es opcional en la consulta. Si el correo o la cola fallan, guardar la consulta no falla. Un correo caído solo deja la cita `failed` (AC-5) |
| VI. Los tests son la evidencia | ✅ | Cada AC tiene tests PHPUnit (tabla de abajo); la fecha se fija con `Carbon::setTestNow()` |
| VII. Privacidad y control de acceso | ✅ | Programar, cambiar y cancelar: médicos (`PatientPolicy::update`), con test 403 para Administrador. La tarjeta solo se ve en la ficha clínica (`viewClinicalRecord`). La ruta de baja es pública a propósito: la protege la firma (`signed`), no muestra datos del paciente, tiene `throttle` y un test de firma alterada. El correo no lleva datos clínicos (AC-6). Logs y salida del comando solo con ids de cita |
| VIII. Idioma | ✅ | Correo, ficha y páginas de baja en español de Chile; fechas `dd-mm-aaaa` y hora 24 h en `clinic_timezone` |
| IX. Simplicidad | ✅ | Dependencias nuevas: ninguna. Tabla nueva `appointments`, justificada arriba frente a la columna en `patients` y a la tabla de envíos |

## Estrategia de pruebas

Todo en Laravel (PHPUnit, `RefreshDatabase`). Archivos nuevos: `tests/Feature/AppointmentTest.php`,
`tests/Feature/AppointmentReminderTest.php` (correo y job), `tests/Feature/SendAppointmentRemindersCommandTest.php`
(comando) y `tests/Feature/ReminderUnsubscribeTest.php`.

| Criterio | Capa | Test |
| --- | --- | --- |
| AC-1 | Feature | `AppointmentTest::test_saving_a_consultation_schedules_the_next_appointment` |
| AC-1 | Feature | `AppointmentTest::test_consultation_without_next_appointment_creates_none` |
| AC-1 | Feature (vista) | `AppointmentTest::test_clinical_record_shows_the_next_appointment_and_professional` |
| AC-2 | Feature | `SendAppointmentRemindersCommandTest::test_queues_a_reminder_for_tomorrows_appointments_only` |
| AC-2 | Feature | `SendAppointmentRemindersCommandTest::test_tomorrow_is_computed_in_the_clinic_timezone` (cita a las 23:30 de Santiago, que en UTC ya es el día siguiente) |
| AC-2 | Feature | `AppointmentReminderTest::test_reminder_mail_has_date_time_professional_notes_and_unsubscribe_link` |
| AC-2 | Feature | `SendAppointmentRemindersCommandTest::test_command_is_scheduled_daily_at_nine_in_santiago` |
| AC-3 | Feature | `SendAppointmentRemindersCommandTest::test_running_the_command_twice_queues_one_reminder` |
| AC-3 | Feature | `AppointmentReminderTest::test_a_retried_job_does_not_send_twice` |
| AC-4 | Feature | `SendAppointmentRemindersCommandTest::test_skips_patients_without_email_consent_or_unsubscribed_and_cancelled_appointments` |
| AC-4 | Feature | `AppointmentReminderTest::test_job_skips_when_the_appointment_or_consent_changed_after_queueing` |
| AC-4 | Feature (vista) | `SendAppointmentRemindersCommandTest::test_clinical_record_shows_why_a_reminder_was_not_sent` |
| AC-5 | Feature | `AppointmentReminderTest::test_job_retries_with_backoff` (`$tries`, `backoff()`) |
| AC-5 | Feature | `AppointmentReminderTest::test_a_failed_job_marks_the_reminder_as_failed` |
| AC-5 | Feature | `SendAppointmentRemindersCommandTest::test_one_failure_does_not_stop_the_other_reminders` |
| AC-6 | Feature | `AppointmentReminderTest::test_reminder_mail_has_no_clinical_data` (paciente con consulta, ficha y exámenes: el HTML y el texto no contienen IMC, peso, categoría, glicemia, HOMA-IR ni motivo de consulta) |
| AC-7 | Feature | `AppointmentTest::test_doctor_reschedules_from_the_clinical_record_and_reminder_resets` |
| AC-7 | Feature | `AppointmentTest::test_doctor_cancels_the_next_appointment` |
| AC-7 | Feature | `AppointmentTest::test_rejects_an_appointment_in_the_past` |
| AC-7 | Feature | `AppointmentTest::test_scheduling_again_replaces_the_scheduled_one` |
| AC-7 | Feature | `AppointmentTest::test_administrator_cannot_schedule_or_cancel` (403) |
| AC-8 | Feature | `ReminderUnsubscribeTest::test_signed_link_shows_confirmation_without_patient_data` |
| AC-8 | Feature | `ReminderUnsubscribeTest::test_confirming_unsubscribes_and_clinical_record_shows_it` |
| AC-8 | Feature | `ReminderUnsubscribeTest::test_tampered_or_unsigned_link_is_rejected` |
| AC-8 | Feature | `PatientFormApiTest::test_doctor_can_reenable_reminders_and_clears_unsubscribe_date` |

Los tests del comando usan `Queue::fake()`, y los del job `Mail::fake()` o un mailer que lanza una
excepción. Los ids se toman de los modelos creados y la fecha se fija con `Carbon::setTestNow()`, para que
la suite pase en SQLite y en MySQL 8.4.

**Verificación manual en el PR de TK-6.1.2** (SMTP de pruebas, por ejemplo Mailpit, y `queue:work`):

1. Programar una cita para mañana a un paciente con consentimiento y correr el comando: el correo llega
   con fecha y hora de Chile, el enlace de baja funciona y la ficha muestra *Enviado*.
2. Detener el servidor SMTP y repetir con otra cita: tras los reintentos, la ficha muestra *Falló el envío*.

## Riesgos y decisiones

- **Hora de verano en Chile:** `scheduled_at` se guarda en UTC y se convierte con `clinic_timezone` al
  mostrarla y al calcular "mañana". `dailyAt('09:00')->timezone(...)` sigue el cambio de hora. Un test
  cubre una cita a las 23:30 de Santiago.
- **Doble ejecución del scheduler** (por ejemplo, dos contenedores `scheduler`): la reclama el `UPDATE`
  condicional, no la caché, así que AC-3 se cumple aunque falle `withoutOverlapping()`.
- **Reintento después de enviar:** si el correo sale pero falla el `UPDATE` a `sent`, un reintento podría
  enviarlo de nuevo. El job vuelve a leer la cita y solo envía si sigue en `queued`. Queda una ventana
  mínima (el proceso muere entre `send` y `update`), que se acepta.
- **Cambios entre la cola y el envío:** si la cita se cancela, cambia de fecha o el paciente se da de baja
  después de encolar, el job lo detecta al releer y marca `skipped` con el motivo.
- **Paciente sin ficha clínica:** la tarjeta vive en la ficha, que exige tener una (igual que en US-5.1).
  La cita se puede programar desde la consulta aunque no haya ficha, y los recordatorios se envían igual.
- **Indicaciones con datos clínicos:** son texto libre y el médico podría escribir algo clínico. El
  formulario lo advierte ("se envían por correo al paciente; no incluya diagnósticos"). AC-6 se verifica
  sobre los datos que el sistema agrega al correo, no sobre lo que escribe el médico.
- **Enlace de baja sin expiración:** un correo antiguo debe seguir sirviendo para darse de baja. La firma
  depende de `APP_KEY`; si se rota la clave, los enlaces antiguos dejan de servir y el paciente puede
  pedir la baja al médico.
