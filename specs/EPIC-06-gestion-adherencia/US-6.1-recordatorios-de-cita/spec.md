---
id: US-6.1
title: Enviar recordatorios automáticos de la próxima consulta
epic: EPIC-06
status: draft
owner: Laravel Dev
---

# US-6.1 · Enviar recordatorios automáticos de la próxima consulta

## Contexto

Las inasistencias interrumpen el seguimiento, que es clave en resistencia a la insulina (los cambios se
evalúan con exámenes cada pocos meses). Hoy el sistema **no registra citas**: no hay fecha de próxima
consulta en ninguna tabla. Los pacientes tienen correo de contacto (`patients.email`, opcional) pero no
cuenta de usuario. Ya existe la infraestructura de colas, scheduler (`schedule:work`) y notificaciones
(`AccountCreatedNotification`).

Por eso esta historia necesita primero registrar la próxima consulta (AC-1), aunque el backlog solo
nombra el correo (TK-6.1.1) y la tarea programada (TK-6.1.2).

## Historia de usuario

**Como** paciente,
**quiero** recibir un correo el día antes de mi próxima consulta,
**para** no olvidarla y mantener mi seguimiento.

## Criterios de aceptación

- **AC-1** — Dado una consulta, cuando el médico la guarda, entonces puede registrar la fecha y hora de
  la próxima consulta del paciente, y la ficha del paciente la muestra.
- **AC-2** — Dado un paciente con correo y una próxima consulta para mañana, cuando corre la tarea diaria,
  entonces recibe un correo con la fecha, hora, el nombre del profesional y las indicaciones de
  preparación (por ejemplo, ayuno para exámenes, si corresponde).
- **AC-3** — Dado que la tarea diaria corre más de una vez el mismo día o se reintenta, cuando revisa las
  citas, entonces cada cita genera a lo sumo un recordatorio.
- **AC-4** — Dado un paciente sin correo, con la cita cancelada o que pidió no recibir recordatorios,
  cuando corre la tarea, entonces no se le envía nada; en la ficha se ve que el recordatorio no se envió
  y por qué.
- **AC-5** — Dado un fallo del servidor de correo, cuando se envía el recordatorio, entonces se reintenta
  y, si sigue fallando, queda registrado como fallido sin detener los demás envíos.
- **AC-6** — Dado el contenido del correo, cuando se envía, entonces no incluye diagnósticos, exámenes ni
  otros datos clínicos (constitución VII).

## Fuera de alcance

- Agenda completa (disponibilidad de horas, reserva por el paciente, reprogramación en línea).
- Recordatorios por SMS o WhatsApp.
- Correo de bienvenida al paciente (el backlog lo nombra en TK-6.1.1; ver pregunta 4).

## Preguntas abiertas

- [ ] **1.** ¿Basta con una "próxima consulta" por paciente o se necesita una agenda de citas con
  estados (programada, confirmada, cancelada, asistió)?
- [ ] **2.** ¿A qué hora se envía el recordatorio y en qué zona horaria (America/Santiago)?
- [ ] **3.** ¿Cómo se pide el consentimiento para recibir correos y cómo se da de baja el paciente?
- [ ] **4.** El correo de bienvenida de TK-6.1.1, ¿va en esta historia o en una propia? (No es un
  recordatorio de cita.)

## Referencias

- Backlog (Notion): US-6.1, TK-6.1.1 (*Laravel Mailables*), TK-6.1.2 (*tarea cron*).
