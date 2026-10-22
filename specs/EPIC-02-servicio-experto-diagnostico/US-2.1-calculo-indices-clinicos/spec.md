---
id: US-2.1
title: Calcular los índices clínicos del paciente
epic: EPIC-02
status: draft
owner: Python Dev
---

# US-2.1 · Calcular los índices clínicos del paciente

> Spec escrita después de la implementación (retro-spec), a partir del código y los tests del servicio
> experto.

## Contexto

La resistencia a la insulina y sus riesgos asociados se estiman con índices que combinan antropometría,
presión arterial y exámenes: HOMA-IR, TyG, TG/HDL, el perfil aterogénico, el índice de hígado graso (FLI) y
el FINDRISC. Calcularlos a mano es lento y propenso a errores. El servicio experto los calcula a partir de
los datos de la consulta, y Laravel los muestra en la página de resultado y en la API.

Los puntos de corte y sus fuentes viven en `shared/clinical_thresholds.json`, que también lee Laravel
(constitución II).

## Historia de usuario

**Como** médico,
**quiero** que el sistema calcule los índices clínicos del paciente con los datos de la consulta,
**para** evaluar la resistencia a la insulina y sus riesgos sin hacer los cálculos a mano.

## Criterios de aceptación

- **AC-1** — Dado una consulta con los datos necesarios, cuando se evalúa, entonces se calculan el IMC y su
  categoría, cintura/talla, cintura/cadera, HOMA-IR, TyG, TG/HDL, colesterol no HDL, LDL de Friedewald,
  Castelli I y II, AIP y su riesgo, FLI y su categoría, y el puntaje FINDRISC con su banda de riesgo.
- **AC-2** — Dado un índice, cuando falta alguno de los datos que necesita, entonces no se calcula ni se
  informa como cero, y la evaluación indica qué dato falta (constitución IV).
- **AC-3** — Dado los mismos datos, cuando el servicio y Laravel calculan un índice (por ejemplo, el HOMA-IR del
  historial de exámenes), entonces dan el mismo valor, con el mismo redondeo.
- **AC-4** — Dado triglicéridos de 400 mg/dL o más, cuando se evalúa, entonces no se calcula el LDL de
  Friedewald; si el laboratorio informó el LDL, se usa ese y no el calculado.
- **AC-5** — Dado los puntos de corte que dependen del sexo (por ejemplo, Castelli o la cintura del FINDRISC),
  cuando se evalúa, entonces se usa el corte del sexo del paciente.
- **AC-6** — Dado el FINDRISC, cuando falta alguna respuesta, el paciente tiene diabetes registrada o es menor de
  edad, entonces no se calcula el puntaje; la prediabetes registrada responde la pregunta de glucosa alta.
- **AC-7** — Dado un punto de corte, cuando se cambia en `shared/clinical_thresholds.json`, entonces el servicio y
  Laravel usan el valor nuevo sin cambiar el código.
- **AC-8** — Dado datos imposibles (por ejemplo, una glicemia fuera de rango o una presión diastólica sin la
  sistólica), cuando se piden los índices, entonces el servicio rechaza la solicitud e indica el campo.

## Fuera de alcance

- Las reglas que convierten los índices en hallazgos y la distribución de macronutrientes: son US-2.2.
- El canal seguro entre Laravel y el servicio: es US-4.1.

## Preguntas abiertas

Ninguna: la spec describe el comportamiento vigente.

## Referencias

- Backlog (Notion): EPIC-02 *Servicio Experto de Diagnóstico*, US-2.1 *API de Cálculo de Índices Clínicos*
  (*Arquitectura FastAPI/Flask*, *Implementación de Fórmulas Clínicas*).
- Fuentes de cada corte: `source` de cada bloque de [shared/clinical_thresholds.json](../../../shared/clinical_thresholds.json)
  (Matthews 1985, Simental-Mendía 2008, McLaughlin 2003, Bedogni 2006, Lindström y Tuomilehto 2003, entre otras).
