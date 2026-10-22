---
id: US-1.1
title: Registrar y editar los pacientes y su ficha clínica
epic: EPIC-01
status: approved
owner: Laravel Dev · Frontend Dev
---

# US-1.1 · Registrar y editar los pacientes y su ficha clínica

> Spec escrita después de la implementación (retro-spec), a partir del código y los tests. Documenta
> también los permisos del equipo sobre los pacientes, porque la épica *Gestión de Acceso y Roles*
> (EPIC-01) no tiene una historia propia. Los permisos de los pacientes sobre el portal están en US-6.3.

## Contexto

El médico atiende pacientes con resistencia a la insulina y necesita sus datos personales y una ficha
clínica: motivo de consulta, antecedentes, fármacos, alergias, hábitos y el cuestionario FINDRISC. Esa
información alimenta la evaluación del sistema experto, el plan alimentario y el seguimiento.

El equipo tiene tres roles: **Administrador** (gestiona las cuentas del equipo), **Doctor** y **Doctor
Jefe** (atienden a los pacientes; el Doctor Jefe además mantiene la base de conocimiento). La ficha clínica
es un dato de salud: solo la ven los médicos (constitución VII).

## Historia de usuario

**Como** médico,
**quiero** registrar y editar a mis pacientes y su ficha clínica,
**para** tener sus datos al día en cada consulta y que el sistema los use en la evaluación y el plan.

## Criterios de aceptación

- **AC-1** — Dado el formulario de paciente, cuando un médico lo guarda, entonces se registra con nombre,
  apellido, RUT, fecha de nacimiento, sexo, dirección, correo (opcional) y comentario, y la edad se calcula
  desde la fecha de nacimiento.
- **AC-2** — Dado un RUT, cuando se registra un paciente, entonces se valida su dígito verificador, se guarda
  en un único formato y no puede repetirse aunque se escriba con o sin puntos y guion; al editar al paciente,
  el RUT no cambia.
- **AC-3** — Dado un formulario con datos inválidos (nombre corto, fecha de nacimiento futura, correo mal
  escrito, sexo desconocido), cuando se guarda, entonces no se registra nada y cada campo indica su error.
- **AC-4** — Dado la lista de pacientes, cuando un usuario del equipo la abre, entonces se pagina y se puede
  buscar por nombre, RUT (con o sin puntos y guion) o correo en el servidor.
- **AC-5** — Dado un paciente, cuando un médico registra o edita su ficha clínica, entonces queda una sola ficha
  por paciente con motivo de consulta, antecedentes mórbidos, otros antecedentes, antecedentes familiares,
  fármacos, alergias e intolerancias alimentarias, tabaco, alcohol, horas de sueño, agua, las preguntas del
  FINDRISC y el antecedente familiar de diabetes; una respuesta no contestada queda como "sin información",
  nunca como "no" (constitución IV).
- **AC-6** — Dado un paciente sin ficha clínica, cuando un médico abre su ficha, entonces se le lleva al
  formulario para completarla.
- **AC-7** — Dado un Administrador, cuando abre los pacientes, entonces ve la lista y sus datos personales,
  pero no puede crear, editar ni eliminar pacientes, ni ver o modificar la ficha clínica (403).
- **AC-8** — Dado un paciente, cuando un médico intenta eliminarlo, entonces solo puede hacerlo quien lo
  registró o un Doctor Jefe; a otro médico se le indica por qué no. Eliminar al paciente elimina también su
  ficha, sus consultas, mediciones y exámenes.
- **AC-9** — Dado el registro o la eliminación de un paciente, o el alta o la edición de su ficha, cuando se
  guarda, entonces queda registrado quién lo hizo y cuándo; para la ficha, además, qué campos cambiaron, sin
  los valores clínicos. (La edición de los datos personales del paciente no se audita hoy.)

## Fuera de alcance

- Las mediciones y los exámenes de laboratorio: son US-1.2.
- El acceso de los pacientes al sistema: es US-6.3.
- Crear cuentas del equipo: lo hace el Administrador en *Usuarios*, sin registro público.

## Preguntas abiertas

Ninguna: la spec describe el comportamiento vigente.

## Referencias

- Backlog (Notion): EPIC-01 *Gestión de Acceso y Roles*, US-1.1 *Registro y Perfil de Pacientes*
  (*Migración y Modelo de Pacientes*, *Formulario UI de Pacientes*).
- FINDRISC: Lindström y Tuomilehto 2003.
