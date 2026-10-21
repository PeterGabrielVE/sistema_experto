---
id: US-6.3
title: Dar acceso a los pacientes a un portal propio
epic: EPIC-06
status: draft
owner: Laravel Dev
---

# US-6.3 · Dar acceso a los pacientes a un portal propio

## Contexto

Hoy solo el equipo (Administrador, Doctor y Doctor Jefe) entra al sistema. Un paciente es un registro
que el médico mantiene, con un correo de contacto opcional, pero sin cuenta. Por eso no puede
registrar nada por su cuenta.

US-6.2 (registro diario del cumplimiento del plan) necesita que el paciente entre al sistema y vea solo
lo suyo: esta historia es su requisito previo (US-6.2, D-1). También recoge el correo de bienvenida al
paciente, que US-6.1 sacó de su alcance (US-6.1, D-4).

El portal expone datos de salud fuera de la consulta. Cada criterio de acceso de esta historia aplica
el principio VII de la [constitución](../../constitution.md): un paciente ve solo sus datos y nunca las
pantallas del equipo.

## Historia de usuario

**Como** paciente,
**quiero** entrar a un portal con mi propia cuenta, invitado por mi médico,
**para** ver mi próxima consulta y, con US-6.2, registrar cómo cumplo mi plan.

## Criterios de aceptación

- **AC-1** — Dado un paciente con correo y sin cuenta, cuando un profesional autorizado (pregunta 1) lo
  invita desde la ficha, entonces el paciente recibe un correo de bienvenida con un enlace personal para
  crear su contraseña, y la ficha muestra que fue invitado y cuándo. El correo no incluye datos clínicos.
- **AC-2** — Dado un paciente sin correo, o con un correo que ya usa otra cuenta del sistema, cuando se
  intenta invitarlo, entonces no se envía nada y la ficha indica por qué (pregunta 4).
- **AC-3** — Dado un enlace de invitación vigente (pregunta 2), cuando el paciente crea su contraseña,
  entonces su cuenta queda activa y entra al portal. Si el enlace está vencido, ya se usó o fue alterado,
  no se crea nada y se le indica que pida una nueva invitación a su médico.
- **AC-4** — Dado un paciente con cuenta activa, cuando inicia sesión con su correo y contraseña,
  entonces entra a la página de inicio del portal, que muestra su nombre y su próxima consulta, o que no
  tiene una programada (pregunta 3). Con datos incorrectos ve el mismo mensaje que el equipo, sin
  revelar si el correo existe, y tras intentos repetidos se bloquea temporalmente.
- **AC-5** — Dado un paciente que olvidó su contraseña, cuando la pide desde la pantalla de acceso,
  entonces recibe un enlace para crear una nueva, igual que el equipo; la respuesta no revela si el
  correo tiene cuenta.
- **AC-6** — Dado un paciente con sesión iniciada, cuando intenta abrir cualquier página o dato del equipo
  (listas de pacientes, fichas, consultas, configuración) o un dato de otro paciente, entonces recibe un
  error 403 y no ve nada de ese contenido.
- **AC-7** — Dado un paciente con cuenta, cuando un profesional autorizado le quita el acceso desde la
  ficha, entonces su sesión abierta se cierra y no puede volver a entrar hasta una nueva invitación; si
  se elimina el paciente, se elimina también su cuenta.
- **AC-8** — Dado un servidor de correo caído, cuando se envía una invitación, entonces la ficha indica que
  no se pudo enviar y permite reintentarla; registrar o editar al paciente sigue funcionando.
- **AC-9** — Dado cualquier invitación, activación, inicio de sesión de un paciente o retiro de acceso,
  cuando ocurre, entonces queda registrado quién, cuándo y sobre qué paciente, sin datos clínicos.

## Requisitos no funcionales

- **NFR-1** — Las páginas del portal se pueden usar en un teléfono (360 px de ancho). `[manual]`:
  se recorren invitación, creación de contraseña, inicio de sesión e inicio del portal en el modo
  responsive del navegador a 360 px, sin desplazamiento horizontal.
- **NFR-2** — Las contraseñas de los pacientes cumplen las mismas reglas que las del equipo y la sesión
  vence por inactividad como la del equipo.

## Fuera de alcance

- Registro del cumplimiento del plan: es US-6.2.
- Que el paciente cree su cuenta sin invitación o edite sus datos personales desde el portal.
- Mensajería entre paciente y médico, aplicación móvil nativa y doble factor de autenticación.
- Cuentas para familiares o cuidadores (ver pregunta 4).

## Preguntas abiertas

- [ ] **1. Roles** (Doctor Jefe): ¿quién invita a un paciente y le quita el acceso: Doctor y Doctor Jefe, o
  también el Administrador, que hoy crea las cuentas del equipo?
- [ ] **2.** ¿Cuánto dura vigente el enlace de invitación (por ejemplo, 72 horas o 7 días)?
- [ ] **3.** ¿Qué ve el paciente en la página de inicio además de su próxima consulta? ¿Su plan
  alimentario guardado (solo lectura) o el PDF del resultado? Todo lo que se muestre es dato de salud
  fuera de la consulta.
- [ ] **4. Correos compartidos** (Doctor Jefe): ¿qué se hace cuando un correo pertenece a varios
  pacientes (un padre o una madre con sus hijos) o a un usuario del equipo? ¿Hay pacientes menores de
  edad que necesiten un cuidador?
- [ ] **5. Términos y consentimiento** (Doctor Jefe + asesoría legal): ¿el paciente debe aceptar términos
  de uso y de tratamiento de datos personales (Ley 19.628) al crear su cuenta? ¿Quién redacta el texto?

## Referencias

- US-6.1, D-4: [correo de bienvenida](../US-6.1-recordatorios-de-cita/spec.md).
- US-6.2: [registro del cumplimiento del plan](../US-6.2-check-in-adherencia/spec.md).
- Ley 19.628 sobre protección de la vida privada (Chile).
