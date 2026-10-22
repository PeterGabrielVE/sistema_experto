---
id: US-1.2
title: Registrar las mediciones y los exámenes de laboratorio de cada consulta
epic: EPIC-01
status: implemented
owner: Laravel Dev · Frontend Dev
---

# US-1.2 · Registrar las mediciones y los exámenes de laboratorio de cada consulta

> Spec escrita en gran parte después de la implementación (retro-spec), a partir del código y los tests.
> Agrega un criterio que el código todavía no cumple (AC-9).

## Contexto

El seguimiento de la resistencia a la insulina se apoya en mediciones repetidas (peso, talla, cintura,
cadera, grasa corporal, presión arterial, frecuencia cardíaca, glicemia capilar) y en exámenes de laboratorio
(glicemia e insulina en ayunas, HbA1c, perfil lipídico y GGT). Cada registro puede quedar asociado a una
consulta, y el historial muestra la evolución. El sistema experto (US-2.1, US-2.2) usa el registro más
reciente hasta la fecha de la consulta.

Al revisar el código para esta spec apareció un hueco: los rangos de referencia con que el historial marca
un examen como fuera de rango (por ejemplo, glicemia en ayunas hasta 99 mg/dL o HDL desde 40 mg/dL) están
escritos en el código, y no en `shared/clinical_thresholds.json` con su fuente, como exige la constitución II.

## Historia de usuario

**Como** médico,
**quiero** registrar las mediciones y los exámenes del paciente en cada consulta y ver su historial,
**para** evaluar su evolución y que el sistema experto use los datos vigentes.

## Criterios de aceptación

- **AC-1** — Dado un paciente, cuando un médico registra una medición, entonces se guarda con su fecha y los
  valores que haya (todos opcionales), se acepta la coma decimal, y el formulario sugiere la última talla
  registrada.
- **AC-2** — Dado un paciente, cuando un médico registra un examen de laboratorio, entonces se guarda con su
  fecha y los analitos que haya (todos opcionales), con la coma decimal.
- **AC-3** — Dado valores fuera de los límites posibles (por ejemplo, peso fuera de 2 a 400 kg, presión
  diastólica mayor que la sistólica o un analito fuera de su rango posible), cuando se guarda, entonces no se
  registra nada y cada campo indica su error.
- **AC-4** — Dado una medición o un examen, cuando se asocia a una consulta, entonces solo puede ser una
  consulta del mismo paciente.
- **AC-5** — Dado el historial de mediciones, cuando un médico lo abre, entonces ve cada registro con sus
  indicadores (IMC, cintura/talla, cintura/cadera) y la variación respecto del anterior; en el de exámenes, los
  indicadores de resistencia a la insulina (HOMA-IR, TyG, TG/HDL) y los valores fuera de rango marcados. Un
  dato faltante no se muestra como cero (constitución IV).
- **AC-6** — Dado una consulta nueva, cuando el médico la abre, entonces el formulario trae el último peso y la
  última talla registrados.
- **AC-7** — Dado un registro, cuando un médico intenta editarlo o eliminarlo, entonces cualquier médico puede
  editarlo y solo quien lo registró o un Doctor Jefe puede eliminarlo; un Administrador no puede ver ni modificar mediciones ni exámenes (403),
  y un registro de otro paciente no se encuentra (404).
- **AC-8** — Dado cualquier alta, edición o eliminación, cuando se guarda, entonces queda registrado quién, cuándo
  y qué campos cambiaron, sin los valores (son datos de salud).
- **AC-9** — Dado el historial de exámenes, cuando marca un valor fuera de rango, entonces usa los rangos de
  referencia de `shared/clinical_thresholds.json`, con su fuente bibliográfica (D-1), y la GGT según el sexo del
  paciente (D-2).

## Fuera de alcance

- La gráfica de evolución del peso y del HOMA-IR: es US-5.1.
- Cargar exámenes desde un archivo del laboratorio.

## Decisiones funcionales

### D-1. Rangos de referencia del laboratorio

Se mantienen los valores que el sistema ya usa, con su fuente en `shared/clinical_thresholds.json`:
glicemia en ayunas ≤ 99 mg/dL y HbA1c ≤ 5,6 % (ADA Standards of Care); colesterol total ≤ 199, LDL ≤ 129 y
triglicéridos ≤ 149 mg/dL, y HDL ≥ 40 mg/dL (NCEP ATP III); insulina basal ≤ 25 µU/mL (rango habitual de
laboratorio, que varía según el laboratorio).

### D-2. GGT por sexo

La GGT se marca alta sobre 40 U/L en mujeres y sobre 60 U/L en hombres (rango habitual de laboratorio).

## Preguntas abiertas

- [x] **1. Rangos de referencia del laboratorio.** Respondida en D-1 y D-2.

## Referencias

- Backlog (Notion): US-1.2 *Registro de Mediciones Clínicas* (*Migración de Consultas y Laboratorio*,
  *Formulario de Captura de Consulta*).
- Constitución II (fuente única de verdad clínica) y IV (datos incompletos).
