---
id: US-2.2
title: Evaluar al paciente con reglas clínicas y sugerir la distribución de macronutrientes
epic: EPIC-02
status: approved
owner: Python Dev · Laravel Dev
---

# US-2.2 · Evaluar al paciente con reglas clínicas y sugerir la distribución de macronutrientes

> Spec escrita después de la implementación (retro-spec), a partir del código y los tests del servicio
> experto y de Laravel.

## Contexto

Con los índices de US-2.1, el sistema experto aplica reglas clínicas con fuente bibliográfica. Las reglas
resumen el estado del paciente en cuatro evaluaciones: estado glicémico, resistencia a la insulina, síndrome
metabólico y perfil aterogénico. Con eso producen hallazgos (por ejemplo, *Valores en rango de prediabetes*)
y sugieren la energía y la distribución de macronutrientes. Por ejemplo, si el HOMA-IR, el TyG o el TG/HDL
están alterados, se baja la carga glucémica.

Cada regla tiene un identificador estable y entrega su evidencia: los datos y el corte aplicados (constitución
III). El Doctor Jefe puede agregar reglas propias de macronutrientes, que se aplican sobre las del sistema.

## Historia de usuario

**Como** médico,
**quiero** que el sistema evalúe al paciente con reglas clínicas trazables y me sugiera la energía y la
distribución de macronutrientes,
**para** decidir el plan con criterios explícitos que puedo revisar y corregir.

## Criterios de aceptación

- **AC-1** — Dado una consulta, cuando se evalúa, entonces se informan el estado glicémico, la resistencia a la
  insulina (probable con dos o más indicadores alterados, posible con uno), el síndrome metabólico (tres o más
  criterios armonizados) y el perfil aterogénico, cada uno con su evidencia.
- **AC-2** — Dado las reglas clínicas, cuando se evalúa, entonces cada hallazgo indica su regla (GLU, RI, SM, ANT,
  LIP, PA, HEP, FIN o DAT), su severidad, los datos y el corte aplicados y su fuente; los identificadores no se
  repiten.
- **AC-3** — Dado un criterio sin datos, cuando se evalúa el síndrome metabólico o la resistencia a la insulina,
  entonces el resultado es "indeterminado" si los criterios faltantes podrían cambiarlo, y "ausente" solo cuando
  ya no pueden alcanzar el umbral (constitución IV); la evaluación sugiere los datos que faltan.
- **AC-4** — Dado una enfermedad registrada en la ficha (diabetes, hipertensión, dislipidemia), cuando se evalúa,
  entonces cuenta como criterio tratado, y una alerta de rango de diabetes solo aparece sin diabetes registrada.
- **AC-5** — Dado un paciente menor de edad, cuando se evalúa, entonces se advierte que los cortes son de adultos
  y no se sugiere distribución de macronutrientes.
- **AC-6** — Dado los datos antropométricos y la actividad física, cuando se pide la distribución, entonces se
  calcula la energía (gasto basal de Mifflin-St Jeor y peso de referencia con IMC 25 como tope) y se aplican las
  reglas MAC-01 a MAC-09 sobre la distribución base; si varias fijan el mismo límite, gana la más restrictiva.
- **AC-7** — Dado el formulario de consulta, cuando el médico ingresa peso, talla, edad y actividad, entonces recibe
  la distribución sugerida con los campos completados, que puede editar; sin servicio experto, el formulario se
  llena a mano (constitución V).
- **AC-8** — Dado las reglas de macronutrientes del Doctor Jefe, cuando están activas, entonces se aplican sobre las
  del sistema con su código (CFG); solo el Doctor Jefe las crea, edita o desactiva, y se validan contra el catálogo
  de variables y rangos compartido.
- **AC-9** — Dado la página de resultado, cuando la abre un Administrador, entonces no ve la evaluación clínica;
  los médicos sí.

## Fuera de alcance

- El cálculo de los índices: es US-2.1.
- La propuesta de menú a partir de la distribución: es US-3.1.

## Preguntas abiertas

Ninguna: la spec describe el comportamiento vigente.

## Referencias

- Backlog (Notion): US-2.2 *Motor de Reglas Nutricionales* (*Árbol de Decisión Nutricional*).
- Fuentes de cada regla: catálogo de reglas del servicio experto (`GET /rules`) y
  [shared/clinical_thresholds.json](../../../shared/clinical_thresholds.json): ADA Standards of Care, Alberti et
  al. 2009, NCEP ATP III, ACC/AHA 2017, EASL-EASD-EASO 2016, AHA/ACC/TOS 2013, PROT-AGE 2013, entre otras.
