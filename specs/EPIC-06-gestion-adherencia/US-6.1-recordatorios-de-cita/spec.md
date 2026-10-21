---
id: US-6.1
title: Enviar recordatorios automáticos de la próxima consulta
epic: EPIC-06
status: in-progress
owner: Laravel Dev
---

# US-6.1 · Enviar recordatorios automáticos de la próxima consulta

## Contexto

Las inasistencias interrumpen el seguimiento, que es clave en resistencia a la insulina (los cambios se
evalúan con exámenes cada pocos meses). Hoy el sistema **no registra citas**: no hay fecha de próxima
consulta en ninguna tabla. Los pacientes tienen correo de contacto (`patients.email`, opcional) pero no
cuenta de usuario. Ya existe la infraestructura de colas, scheduler (`schedule:work`) y notificaciones
(`AccountCreatedNotification`).

Por eso esta historia necesita primero registrar la próxima consulta (AC-1, AC-7), aunque el backlog solo
nombra el correo (TK-6.1.1) y la tarea programada (TK-6.1.2).

## Historia de usuario

**Como** paciente,
**quiero** recibir un correo el día antes de mi próxima consulta,
**para** no olvidarla y mantener mi seguimiento.

## Criterios de aceptación

- **AC-1** — Dado una consulta, cuando el médico la guarda, entonces puede registrar la fecha y hora de
  la próxima consulta del paciente y sus indicaciones de preparación, y la ficha del paciente la muestra
  con el profesional a cargo (D-1).
- **AC-2** — Dado un paciente con correo, que aceptó recibir recordatorios (D-3), y una próxima consulta
  para mañana, cuando corre la tarea diaria (D-2), entonces recibe un correo con la fecha, la hora, el
  nombre del profesional, las indicaciones de preparación (por ejemplo, ayuno para exámenes, si
  corresponde) y un enlace para dejar de recibir recordatorios.
- **AC-3** — Dado que la tarea diaria corre más de una vez el mismo día o se reintenta, cuando revisa las
  citas, entonces cada cita genera a lo sumo un recordatorio.
- **AC-4** — Dado un paciente sin correo, que no aceptó recibir recordatorios o que se dio de baja, o una
  cita cancelada, cuando corre la tarea, entonces no se le envía nada; en la ficha se ve que el
  recordatorio no se envió y por qué.
- **AC-5** — Dado un fallo del servidor de correo, cuando se envía el recordatorio, entonces se reintenta
  y, si sigue fallando, queda registrado como fallido sin detener los demás envíos.
- **AC-6** — Dado el contenido del correo, cuando se envía, entonces no incluye diagnósticos, exámenes ni
  otros datos clínicos (constitución VII).
- **AC-7** — Dado un paciente con una próxima consulta programada, cuando el médico la cambia o la
  cancela desde la ficha, entonces el recordatorio se envía para la nueva fecha o, si se canceló, no se
  envía; no se puede programar una consulta en una fecha pasada (D-1).
- **AC-8** — Dado el enlace del correo, cuando el paciente lo abre y confirma, entonces deja de recibir
  recordatorios sin iniciar sesión, y la ficha muestra que se dio de baja y cuándo; el enlace no muestra
  datos del paciente y no sirve si se altera (D-3).

## Fuera de alcance

- Agenda completa (disponibilidad de horas, reserva por el paciente, reprogramación en línea, estados
  *confirmada* o *asistió*).
- Recordatorios por SMS o WhatsApp.
- Correo de bienvenida al paciente: va en una historia propia (D-4).

## Decisiones funcionales

### D-1. Citas

Una tabla de citas simple: paciente, fecha y hora, profesional, indicaciones de preparación y estado
*programada* o *cancelada*. Cada paciente tiene a lo sumo una próxima consulta programada; registrar
otra la reemplaza. No hay agenda ni disponibilidad de horas.

### D-2. Horario de envío

La tarea corre una vez al día a las **09:00 hora de Chile (America/Santiago)** y envía los recordatorios
de las citas del día siguiente en esa zona horaria. La aplicación sigue guardando las fechas en UTC.

### D-3. Consentimiento y baja

El médico marca en los datos del paciente si acepta recibir recordatorios por correo; la casilla está
**desactivada por defecto**. Cada recordatorio trae un enlace firmado para darse de baja sin iniciar
sesión. Si el paciente se da de baja, el médico puede volver a activarlo solo a pedido del paciente.

### D-4. Correo de bienvenida

El correo de bienvenida que el backlog nombra en TK-6.1.1 se saca de esta historia: no es un
recordatorio de cita y encaja con el portal de pacientes (pregunta 1 de US-6.2).

## Preguntas abiertas

- [x] **1.** ¿Basta con una "próxima consulta" por paciente o se necesita una agenda de citas con
  estados? Respondida en D-1: tabla de citas simple con estado programada o cancelada.
- [x] **2.** ¿A qué hora se envía el recordatorio y en qué zona horaria? Respondida en D-2: 09:00
  America/Santiago, para las citas del día siguiente.
- [x] **3.** ¿Cómo se pide el consentimiento y cómo se da de baja el paciente? Respondida en D-3: casilla
  en los datos del paciente (desactivada por defecto) y enlace firmado de baja en cada correo.
- [x] **4.** El correo de bienvenida de TK-6.1.1, ¿va en esta historia o en una propia? Respondida en D-4:
  en una historia propia.

## Referencias

- Backlog (Notion): US-6.1, TK-6.1.1 (*Laravel Mailables*), TK-6.1.2 (*tarea cron*).
