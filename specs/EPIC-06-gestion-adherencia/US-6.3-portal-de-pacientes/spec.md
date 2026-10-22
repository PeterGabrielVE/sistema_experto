---
id: US-6.3
title: Dar acceso a los pacientes a un portal propio
epic: EPIC-06
status: in-progress
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
**para** ver mi próxima consulta y mi plan alimentario y, con US-6.2, registrar cómo lo cumplo.

## Criterios de aceptación

- **AC-1** — Dado un paciente con correo y sin cuenta, cuando un Doctor o un Doctor Jefe lo invita desde
  la ficha (D-1), entonces el paciente recibe un correo de bienvenida con un enlace personal para crear su
  contraseña, vigente por 7 días (D-2), y la ficha muestra que fue invitado y cuándo. El correo no incluye
  datos clínicos. Un Administrador que intenta invitar recibe un error 403.
- **AC-2** — Dado un paciente sin correo, o con un correo que ya usa otra cuenta del sistema, cuando se
  intenta invitarlo, entonces no se envía nada y la ficha indica por qué: un correo tiene a lo sumo una
  cuenta (D-4).
- **AC-3** — Dado un enlace de invitación vigente, cuando el paciente crea su contraseña y acepta los
  términos de uso y de tratamiento de datos (D-5), entonces su cuenta queda activa, queda registrada la
  versión de los términos que aceptó y cuándo, y entra al portal. Sin aceptar los términos no se activa
  la cuenta. Si el enlace está vencido (más de 7 días), ya se usó o fue alterado, no se crea nada y se le
  indica que pida una nueva invitación a su médico.
- **AC-4** — Dado un paciente con cuenta activa, cuando inicia sesión con su correo y contraseña,
  entonces entra a la página de inicio del portal, que muestra su nombre, su próxima consulta (o que no
  tiene una programada) y su plan alimentario guardado más reciente, solo de lectura (D-3); si no tiene
  un plan guardado, lo indica. No muestra diagnósticos, exámenes ni otros datos de la ficha. Con datos
  incorrectos ve el mismo mensaje que el equipo, sin revelar si el correo existe, y tras intentos
  repetidos se bloquea temporalmente.
- **AC-5** — Dado un paciente que olvidó su contraseña, cuando la pide desde la pantalla de acceso,
  entonces recibe un enlace para crear una nueva, igual que el equipo; la respuesta no revela si el
  correo tiene cuenta.
- **AC-6** — Dado un paciente con sesión iniciada, cuando intenta abrir cualquier página o dato del equipo
  (listas de pacientes, fichas, consultas, configuración) o un dato de otro paciente, entonces recibe un
  error 403 y no ve nada de ese contenido.
- **AC-7** — Dado un paciente con cuenta, cuando un Doctor o un Doctor Jefe le quita el acceso desde la
  ficha, entonces su sesión abierta se cierra y no puede volver a entrar hasta una nueva invitación; si
  se elimina el paciente, se elimina también su cuenta.
- **AC-8** — Dado un servidor de correo caído, cuando se envía una invitación, entonces la ficha indica que
  no se pudo enviar y permite reintentarla; registrar o editar al paciente sigue funcionando.
- **AC-9** — Dado cualquier invitación, activación (con la versión de los términos aceptada), inicio de
  sesión de un paciente o retiro de acceso, cuando ocurre, entonces queda registrado quién, cuándo y sobre
  qué paciente, sin datos clínicos.
- **AC-10** — Dado que la clínica todavía no entregó el texto de los términos, cuando un médico intenta
  invitar a un paciente, entonces no se envía nada y se indica que falta el texto de los términos (D-5).

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
- Cuentas para familiares o cuidadores, y pacientes menores de edad con un cuidador (D-4).
- El PDF del resultado de la consulta en el portal (D-3).
- Redactar el texto de los términos: lo entrega la clínica (D-5).

## Decisiones funcionales

### D-1. Roles

Invitan a un paciente al portal y le quitan el acceso el **Doctor y el Doctor Jefe**, que lo atienden y
ya editan sus datos. El Administrador sigue gestionando solo las cuentas del equipo.

### D-2. Vigencia de la invitación

El enlace de invitación vale **7 días**, para el paciente que no revisa su correo a diario. Si vence, el
médico lo vuelve a invitar.

### D-3. Qué ve el paciente

Su nombre, su próxima consulta y su **plan alimentario guardado más reciente, solo de lectura**: es lo
que US-6.2 necesita y no incluye diagnósticos ni exámenes. El PDF del resultado no se muestra, porque
incluye el IMC, la categoría y otros datos clínicos.

### D-4. Un correo, una cuenta

Un correo tiene a lo sumo una cuenta en el sistema, del equipo o de un paciente. Las cuentas de
cuidadores y los pacientes menores de edad quedan fuera de esta historia.

### D-5. Términos y tratamiento de datos

Al crear su contraseña, el paciente acepta los términos de uso y de tratamiento de sus datos personales
(Ley 19.628), y se registra qué versión aceptó y cuándo. El texto lo redacta la clínica con asesoría legal
y se mantiene fuera del código; sin ese texto no se puede invitar a pacientes.

## Preguntas abiertas

- [x] **1. Roles.** Respondida en D-1: Doctor y Doctor Jefe.
- [x] **2.** ¿Cuánto dura vigente el enlace de invitación? Respondida en D-2: 7 días.
- [x] **3.** ¿Qué ve el paciente en la página de inicio? Respondida en D-3: su próxima consulta y su plan
  guardado, solo de lectura; sin el PDF del resultado.
- [x] **4. Correos compartidos.** Respondida en D-4: un correo, una cuenta; cuidadores y menores, fuera
  de alcance.
- [x] **5. Términos y consentimiento.** Respondida en D-5: se aceptan al crear la contraseña, con la
  versión registrada; el texto lo entrega la clínica.

## Referencias

- US-6.1, D-4: [correo de bienvenida](../US-6.1-recordatorios-de-cita/spec.md).
- US-6.2: [registro del cumplimiento del plan](../US-6.2-check-in-adherencia/spec.md).
- Ley 19.628 sobre protección de la vida privada (Chile).
