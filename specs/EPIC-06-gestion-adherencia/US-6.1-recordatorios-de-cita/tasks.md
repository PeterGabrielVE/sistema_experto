# Tareas · US-6.1

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-6.1.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.
TK-6.1.1 y TK-6.1.2 conservan los ids del backlog (correo y tarea programada). Orden por dependencia:
el correo necesita la cita y el consentimiento, y la tarea programada necesita el correo. Por eso van
primero TK-6.1.3 y TK-6.1.4.

- [x] **TK-6.1.3** — Crear la tabla `appointments`, el modelo `Appointment` y `AppointmentService`
  (`schedule`, `cancel`); agregar *Próxima consulta* e *Indicaciones* al formulario de la consulta y la
  tarjeta *Próxima consulta* en la ficha clínica, con las rutas para cambiarla y cancelarla (solo médicos),
  con `AppointmentTest`. Todavía no se envía ningún correo. Cubre AC-1, AC-7.
- [x] **TK-6.1.4** — Agregar el consentimiento (`appointment_reminders`, `reminders_unsubscribed_at`) con
  la casilla en `PatientForm.vue`, y la baja por enlace firmado (`GET` confirma, `POST` da de baja) con sus
  vistas públicas sin datos del paciente; la ficha muestra la baja y su fecha, con
  `ReminderUnsubscribeTest` y el test de reactivación en `PatientFormApiTest`. Cubre AC-4, AC-8.
- [x] **TK-6.1.1** — Crear el Mailable `AppointmentReminderMail` (Markdown, con el enlace de baja) y el job
  `SendAppointmentReminder` (reintentos con espera, `failed()` marca `failed`, vuelve a validar la cita
  antes de enviar), con los tests de contenido, reintentos e idempotencia del job en
  `AppointmentReminderTest`. Cubre AC-2, AC-3, AC-5, AC-6.
- [x] **TK-6.1.2** — Crear el comando `appointments:send-reminders` (citas de mañana en `clinic_timezone`,
  motivos de omisión, reclamo atómico, un job por cita) y programarlo a las 09:00 America/Santiago;
  mostrar en la tarjeta de la ficha el estado del recordatorio y su motivo; tests del comando y
  verificación manual del plan en el PR. Cubre AC-2, AC-3, AC-4, AC-5.
