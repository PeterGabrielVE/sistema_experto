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

**Dependencia:** los pacientes **no tienen acceso al sistema**. Los roles existentes son
Administrador, Doctor y Doctor Jefe; un paciente es un registro, no un usuario. El acceso de los
pacientes (cuenta, inicio de sesión y acceso solo a sus propios datos) se especifica en
[US-6.3](../US-6.3-portal-de-pacientes/spec.md), que debe estar aprobada antes que esta (D-1).

## Historia de usuario

**Como** paciente,
**quiero** entrar al portal y marcar cada día si cumplí mi plan,
**para** que mi médico vea mi adherencia y ajustemos el plan juntos.

## Criterios de aceptación

- **AC-1** — Dado un paciente con cuenta y un plan alimentario guardado, cuando entra al portal, entonces
  ve su menú del día y puede marcar, por comida, si lo cumplió, lo cumplió en parte o no lo cumplió (D-2).
  Si el plan tiene varios días, el menú del día rota en orden desde la fecha en que se guardó el plan
  (D-4).
- **AC-2** — Dado el portal, cuando el paciente entra, entonces puede registrar o corregir el cumplimiento
  de hoy y de ayer; los días anteriores ya no se pueden registrar ni modificar, y si quedaron sin
  registro se muestran como "sin dato" (D-3).
- **AC-3** — Dado un paciente, cuando entra al portal, entonces solo ve sus propios datos; cualquier
  intento de ver los de otro paciente responde 403.
- **AC-4** — Dado los registros de adherencia, cuando el médico abre la ficha del paciente, entonces ve
  el porcentaje de cumplimiento por semana y los días sin registro, que se muestran como "sin dato" y
  nunca como incumplimiento (constitución IV). El porcentaje cuenta *cumplí* como 1, *cumplí en parte*
  como 0,5 y *no cumplí* como 0, sobre las comidas marcadas; las comidas sin marcar no cuentan (D-5). Una
  semana sin comidas marcadas muestra "sin dato", no 0 %.
- **AC-5** — Dado un paciente sin plan alimentario guardado, cuando entra al portal, entonces ve que aún
  no tiene plan y no puede registrar cumplimiento.

## Fuera de alcance

- Registro de alimentos consumidos con cantidades (diario de comidas).
- Aplicación móvil nativa.
- Mensajería entre paciente y médico.
- La cuenta del paciente y su acceso al portal: es US-6.3.

## Decisiones funcionales

### D-1. Portal de pacientes

El acceso de los pacientes se especifica en una historia propia y previa, US-6.3 (rol Paciente,
invitación por correo desde la ficha, inicio de sesión, recuperación de contraseña y acceso solo a
sus datos). Esta historia no se planifica hasta que US-6.3 esté aprobada.

### D-2. Registro por comida

El paciente no conoce sus gramos de macronutrientes, pero sí su menú. Por eso marca cada comida del
menú del día como *cumplí*, *cumplí en parte* o *no cumplí*.

### D-3. Días que se pueden registrar

El día de hoy y el de ayer, para poder registrar lo que se olvidó la noche anterior. Después, el día
queda "sin dato".

### D-4. Menú del día en un plan de varios días

Los menús rotan en orden: el día en que se guardó el plan es el día 1, el siguiente el día 2, y al
terminar vuelve a empezar. El paciente no elige.

### D-5. Porcentaje de cumplimiento

*Cumplí* vale 1, *cumplí en parte* 0,5 y *no cumplí* 0. El porcentaje de la semana es el promedio sobre
las comidas marcadas; las comidas sin marcar son "sin dato" y no entran en el cálculo.

## Preguntas abiertas

- [x] **1. Portal de pacientes.** Respondida en D-1: historia previa US-6.3.
- [x] **2.** ¿El cumplimiento se registra por comida o como un único "sí / en parte / no" por día?
  Respondida en D-2: por comida.
- [x] **3.** ¿Cuántos días hacia atrás puede registrar el paciente? Respondida en D-3: hoy y ayer.
- [x] **4.** ¿Cómo se calcula el porcentaje semanal? Respondida en D-5: *en parte* cuenta 0,5 y las
  comidas sin marcar no cuentan.
- [x] **5.** ¿Qué menú ve el paciente si el plan tiene varios días? Respondida en D-4: rotan en orden
  desde la fecha en que se guardó el plan.

## Referencias

- Backlog (Notion): US-6.2 *Check-in diario de adherencia*.
- [US-6.3](../US-6.3-portal-de-pacientes/spec.md): portal de pacientes.
