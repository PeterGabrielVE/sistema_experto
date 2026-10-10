---
id: US-6.2
title: Registrar el cumplimiento diario del plan por el paciente
epic: EPIC-06
status: draft
owner: Frontend Dev
---

# US-6.2 · Registrar el cumplimiento diario del plan por el paciente

## Contexto

El médico no sabe cuánto cumple el paciente su plan entre consultas, y sin ese dato no puede
distinguir un plan que no funciona de uno que no se sigue.

**Dependencia bloqueante:** los pacientes **no tienen acceso al sistema**. Los roles existentes son
Administrador, Doctor y Doctor Jefe (`app/Enums/Role.php`); un paciente es un registro (`patients`), no
un usuario. Esta historia requiere un portal de pacientes (cuenta, inicio de sesión y acceso solo a sus
propios datos), que hoy no está en el backlog. Ver pregunta abierta 1.

## Historia de usuario

**Como** paciente,
**quiero** entrar al portal y marcar cada día si cumplí mi plan,
**para** que mi médico vea mi adherencia y ajustemos el plan juntos.

## Criterios de aceptación

- **AC-1** — Dado un paciente con cuenta y un plan alimentario guardado, cuando entra al portal, entonces
  ve su menú del día y puede marcar, por comida, si lo cumplió, lo cumplió en parte o no lo cumplió.
- **AC-2** — Dado un día ya registrado, cuando el paciente vuelve a entrar ese mismo día, entonces puede
  corregir su registro; los días anteriores ya no se modifican.
- **AC-3** — Dado un paciente, cuando entra al portal, entonces solo ve sus propios datos; cualquier
  intento de ver los de otro paciente responde 403.
- **AC-4** — Dado los registros de adherencia, cuando el médico abre la ficha del paciente, entonces ve
  el porcentaje de cumplimiento por semana y los días sin registro, que se muestran como "sin dato" y
  nunca como incumplimiento (constitución IV).
- **AC-5** — Dado un paciente sin plan alimentario guardado, cuando entra al portal, entonces ve que aún
  no tiene plan y no puede registrar cumplimiento.

## Fuera de alcance

- Registro de alimentos consumidos con cantidades (diario de comidas).
- Aplicación móvil nativa.
- Mensajería entre paciente y médico.

## Preguntas abiertas

- [ ] **1. Portal de pacientes** (bloqueante): ¿se crea una épica o historia previa para que los pacientes
  tengan cuenta (rol Paciente, invitación por correo, inicio de sesión, recuperación de contraseña)?
  Sin ella esta historia no se puede aprobar.
- [ ] **2.** ¿El cumplimiento se registra por comida o como un único "sí / en parte / no" por día? El
  backlog dice "si cumplí mis macros", pero el paciente no conoce sus gramos: ¿se pregunta por el menú?
- [ ] **3.** ¿Cuántos días hacia atrás puede registrar el paciente si olvidó hacerlo?

## Referencias

- Backlog (Notion): US-6.2 *Check-in diario de adherencia*.
