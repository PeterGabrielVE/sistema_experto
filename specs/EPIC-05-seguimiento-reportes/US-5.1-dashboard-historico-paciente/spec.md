---
id: US-5.1
title: Ver la evolución del peso y HOMA-IR del paciente
epic: EPIC-05
status: draft
owner: Frontend Dev · Laravel Dev
---

# US-5.1 · Ver la evolución del peso y HOMA-IR del paciente

## Contexto

Parte de esto ya existe: el *Registro de mediciones* grafica peso, IMC, cintura, presión y glicemia
capilar, y *Exámenes de laboratorio* grafica glicemia, insulina, HbA1c, HOMA-IR, TyG y lípidos
(`resources/js/evolution-chart.js`, Chart.js). Pero el peso y el HOMA-IR están en páginas distintas.
Para evaluar si el plan funciona en un paciente con resistencia a la insulina, el médico necesita ver
**juntos**, en la ficha del paciente, cómo cambian el peso y la resistencia a la insulina.

## Historia de usuario

**Como** médico tratante,
**quiero** ver en la ficha del paciente una gráfica con la evolución del peso y del HOMA-IR,
**para** evaluar de un vistazo si el tratamiento está funcionando y ajustar el plan.

## Criterios de aceptación

- **AC-1** — Dado un paciente con mediciones y exámenes en distintas fechas, cuando el médico abre su
  ficha, entonces ve una gráfica en el tiempo con el peso (de las mediciones) y el HOMA-IR (de los
  exámenes), cada uno con su propio eje y unidad.
- **AC-2** — Dado un examen sin glicemia o sin insulina en ayunas, cuando se arma la gráfica, entonces
  ese examen no aporta un punto de HOMA-IR (no se grafica cero), y se indica cuántos exámenes no lo
  permiten calcular.
- **AC-3** — Dado el corte de HOMA-IR de `shared/clinical_thresholds.json`
  (`insulin_resistance.homa_ir`), cuando se muestra la gráfica, entonces el corte aparece como línea de
  referencia y los puntos sobre él se distinguen.
- **AC-4** — Dado una serie con menos de dos registros válidos en el período seleccionado, cuando se
  muestra la gráfica, entonces en vez de esa serie aparece un mensaje que explica que faltan datos y cómo
  registrarlos, y la otra serie se sigue mostrando (D-3).
- **AC-5** — Dado un usuario sin acceso a la ficha clínica del paciente, cuando abre la ficha o pide los
  datos de la gráfica, entonces recibe un error 403.
- **AC-6** — Dado un paciente, cuando se piden sus datos históricos en JSON (TK-5.1.2), entonces la
  respuesta trae las mismas series, fechas, valores y unidades que la gráfica, con el corte de HOMA-IR,
  y la gráfica se construye con esa respuesta (D-1).
- **AC-7** — Dado la gráfica, cuando el médico elige *Últimos 3 meses*, *6 meses*, *12 meses* o *Todo el
  historial*, entonces ambas series muestran solo los registros de ese período, en orden cronológico y con
  sus fechas originales; al abrir la ficha se muestra todo el historial (D-3).

## Fuera de alcance

- Comparar pacientes entre sí o ver estadísticas de la población (eso está en *Estadísticas*).
- Exportar la gráfica a PDF (puede ser parte de US-5.2).

## Decisiones funcionales

### D-1. Fuente de datos e histórico clínico

Se mantendrá el endpoint JSON definido en TK-5.1.2 para consultar la evolución histórica del paciente. Este endpoint será la fuente de datos de la gráfica y podrá ser reutilizado por otros módulos autorizados del sistema experto, como informes clínicos y análisis de tendencias.

- El backend será responsable de calcular el HOMA-IR y aplicar los umbrales clínicos configurados.
- La respuesta incluirá las series, fechas, valores, unidades y metadatos necesarios para construir la gráfica.
- El acceso estará protegido por los permisos de la ficha clínica del paciente.
- Los datos presentados en la gráfica y los devueltos por el endpoint deberán ser consistentes.

### D-2. Series clínicas incluidas

La gráfica de US-5.1 incluirá exclusivamente:

- **Peso:** obtenido del registro de mediciones del paciente, expresado en kilogramos.
- **HOMA-IR:** obtenido de los exámenes de laboratorio con los datos necesarios para calcularlo.

Ambas series compartirán el eje temporal y utilizarán ejes verticales independientes.

El IMC, la circunferencia de cintura, TyG, HbA1c y otros indicadores quedan fuera del alcance de esta historia y podrán incorporarse mediante historias de usuario posteriores.

### D-3. Rango temporal

Por defecto, la gráfica mostrará todo el historial disponible del paciente.

Se permitirá filtrar la visualización por los siguientes períodos:

- Últimos 3 meses.
- Últimos 6 meses.
- Últimos 12 meses.
- Todo el historial.

El filtro temporal se aplicará de manera consistente a ambas series. Los registros se mostrarán en orden cronológico y conservarán sus fechas originales.

Si alguna serie tiene menos de dos registros válidos en el período seleccionado, se mostrará un mensaje que explique la falta de datos y cómo registrarlos. La disponibilidad de una serie no impedirá visualizar la otra.

## Preguntas abiertas

- [x] ¿Quién usa el endpoint JSON de TK-5.1.2? Respondida en D-1: se mantiene y es la fuente de la gráfica.
- [x] ¿Qué series se incluyen? Respondida en D-2: solo peso y HOMA-IR.
- [x] ¿Qué rango de fechas se muestra por defecto? Respondida en D-3: todo el historial, con filtros de 3, 6 y 12 meses.

## Referencias

- Backlog (Notion): US-5.1, TK-5.1.1 (*gráficos con Chart.js*), TK-5.1.2 (*endpoint de histórico clínico*).
- Corte de HOMA-IR: `insulin_resistance.homa_ir` en [shared/clinical_thresholds.json](../../../shared/clinical_thresholds.json).
