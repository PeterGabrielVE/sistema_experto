# Tareas · US-6.3

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-6.3.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.
Orden por dependencia: el aislamiento va primero, para que ninguna cuenta de paciente exista antes de
que el equipo esté protegido.

- [ ] **TK-6.3.1** — Agregar `Role::Patient`, `users.patient_id`, `User::isPatient()`/`isStaff()`, los
  middleware `staff` (todas las rutas del equipo, `/home` y la API) y `patient`; cambiar a `isStaff()` las
  policies que aceptan cualquier rol, negar tokens de la API a pacientes y sacar el rol Paciente del
  formulario de usuarios, con `PortalIsolationTest`. Todavía no se puede invitar a nadie. Cubre AC-6.
- [ ] **TK-6.3.2** — Crear `patient_invitations`, `PortalTerms` y `config/portal.php`; la invitación desde
  la ficha (Doctor y Doctor Jefe), con las comprobaciones de correo, cuenta existente y términos, y el job
  `SendPortalInvitation` con el correo de bienvenida y sus reintentos; la tarjeta *Portal del paciente* con
  el estado del envío; auditar `portal.invited`. Cubre AC-1, AC-2, AC-8, AC-9, AC-10.
- [ ] **TK-6.3.3** — Aceptar la invitación (crear la contraseña y aceptar los términos, con su versión),
  crear la cuenta e iniciar sesión; enviar a cada rol a su inicio después del login y de la recuperación
  de contraseña; auditar `portal.activated` y `portal.login`, con `PortalLoginTest` y los tests de
  aceptación. Cubre AC-3, AC-4, AC-5, AC-9.
- [ ] **TK-6.3.4** — Crear el inicio del portal con el nombre, la próxima consulta y el último plan
  guardado (solo lectura, sin datos clínicos), con `PortalHomeTest` y la verificación manual de NFR-1
  en el PR. Cubre AC-4, AC-6.
- [ ] **TK-6.3.5** — Quitar el acceso desde la ficha (borrar la cuenta e invalidar las invitaciones) y
  borrar la cuenta junto con el paciente; auditar `portal.revoked`, con `PortalRevokeTest` y
  `PortalAuditTest`. Cubre AC-7, AC-9.
