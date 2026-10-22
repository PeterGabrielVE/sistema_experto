---
id: US-5.2
title: Descargar el plan alimentario en PDF para entregarlo al paciente
epic: EPIC-05
status: approved
owner: Laravel Dev · Frontend Dev
---

# US-5.2 · Descargar el plan alimentario en PDF para entregarlo al paciente

## Contexto

El médico entrega el plan alimentario al paciente al final de la consulta. Hoy la página de resultado
tiene un botón *Descargar* que genera un PDF del **resultado completo** de la consulta. Ese documento:

- es un informe clínico más que un plan para el paciente: incluye el RUT y la dirección del paciente, la
  categoría de IMC, las recomendaciones de esa categoría, un texto general sobre actividad física y el
  menú con energía, macronutrientes y carga glucémica por día;
- no tiene el logo ni la identidad de la clínica;
- si la consulta no tiene un plan guardado, genera uno nuevo al momento, que el médico no revisó;
- lo puede descargar cualquier miembro del equipo, también el Administrador.

El backlog pide un documento **limpio y amigable, con el logo de la clínica** (TK-5.2.1), generado a partir
de una vista imprimible (TK-5.2.2). Esta historia también absorbe la fila repetida del backlog
*Vista de Resultados y Generación PDF* (US-4.2 en *Integración & Reportería*), que pedía "descarga de
informe para el paciente".

El plan guardado ya es lo que el médico revisó y aceptó, y no cambia si después cambian el catálogo o
las reglas (US-3.1/AC-10, US-4.2/AC-4).

## Historia de usuario

**Como** médico,
**quiero** descargar en PDF el plan alimentario de la consulta, claro y con el logo de la clínica,
**para** entregárselo al paciente impreso o por correo.

## Criterios de aceptación

- **AC-1** — Dado una consulta con plan alimentario guardado, cuando el médico pide el *Plan para el
  paciente* en PDF, entonces recibe un documento aparte del informe de resultado, que no cambia (D-1). El
  documento tiene el logo y el nombre de la clínica, el nombre del paciente, la fecha de la consulta, el
  profesional, y el menú de cada día por comida, con cada alimento, sus porciones y sus gramos, en español
  de Chile y con coma decimal.
- **AC-2** — Dado el PDF del plan, cuando se genera, entonces además del menú incluye (D-2):
  - la energía y los macronutrientes de cada día;
  - las recomendaciones de la categoría del paciente, sin nombrar la categoría;
  - las indicaciones generales;
  - las alergias e intolerancias que el menú dejó fuera.

  Nunca incluye el RUT, la dirección, el IMC, la categoría, la carga glucémica ni otros datos de la ficha
  clínica (motivo de consulta, antecedentes, exámenes).
- **AC-3** — Dado una consulta sin plan guardado, cuando el médico abre su resultado, entonces no se ofrece
  el *Plan para el paciente*, y se indica que primero hay que revisar y guardar la propuesta (D-3); pedir el
  PDF directamente responde que no hay plan guardado.
- **AC-4** — Dado un plan de varios días, cuando se descarga, entonces el PDF muestra todos los días en
  orden y cada día empieza en una página nueva.
- **AC-5** — Dado un plan guardado con un alimento que después se retiró del catálogo, o con reglas que
  cambiaron, cuando se descarga, entonces el PDF muestra el plan tal como se guardó.
- **AC-6** — Dado el servicio experto caído, cuando se descarga el PDF de un plan guardado, entonces se
  genera igual: no depende de los servicios Python (constitución V).
- **AC-7** — Dado un Administrador, cuando intenta descargar el PDF del plan, entonces recibe un error 403;
  solo lo descargan el Doctor y el Doctor Jefe (D-4).
- **AC-8** — Dado que el logo de la clínica no está disponible, cuando se genera el PDF, entonces se genera
  igual, con el nombre de la clínica en su lugar.
- **AC-9** — Dado los datos de contacto de la clínica (dirección, teléfono y correo), cuando se genera el PDF,
  entonces el pie muestra los que estén configurados en el servidor y omite los que falten (D-5).

## Fuera de alcance

- Enviar el PDF por correo desde el sistema.
- Editar el plan desde el PDF o personalizar el documento por paciente.
- Traducciones a otros idiomas.
- Descargar el PDF desde el portal de pacientes (D-6).

## Decisiones funcionales

### D-1. Documento aparte

El *Plan para el paciente* es un PDF nuevo, aparte del informe de resultado actual, que sigue igual para el
profesional. La página de resultado ofrece los dos.

### D-2. Contenido

Además del menú (alimentos, porciones y gramos): la energía y los macronutrientes de cada día, las
recomendaciones de la categoría (sin nombrarla), las indicaciones generales y las alergias e intolerancias
consideradas. Ni el IMC ni la categoría ni la carga glucémica.

### D-3. Solo el plan guardado

Se entrega lo que el médico revisó: sin plan guardado no hay PDF del plan (constitución V: el sistema sugiere
y el profesional decide).

### D-4. Roles

Doctor y Doctor Jefe, igual que la ficha clínica. El Administrador no.

### D-5. Identidad de la clínica

El logo "El Sauce" de la pantalla de acceso. El nombre, la dirección, el teléfono y el correo de la clínica
se configuran en el servidor, no en el código, y van en el pie; los que falten no se muestran.

### D-6. Portal

Por ahora el paciente no descarga el PDF desde el portal: lo entrega el médico. El portal ya muestra el plan,
y este PDF agrega energía y macronutrientes, que el portal no muestra (US-6.3, D-3).

## Preguntas abiertas

- [x] **1. Relación con el PDF actual.** Respondida en D-1: documento aparte.
- [x] **2. Contenido.** Respondida en D-2.
- [x] **3. Sin plan guardado.** Respondida en D-3: solo el plan guardado.
- [x] **4. Roles.** Respondida en D-4: Doctor y Doctor Jefe.
- [x] **5. Identidad de la clínica.** Respondida en D-5: logo "El Sauce" y datos de contacto configurables.
- [x] **6. Portal.** Respondida en D-6: no por ahora.

## Referencias

- Backlog (Notion): US-5.2 *Exportación de Plan Nutricional*, TK-5.2.1 *Vista Blade de la Dieta (Printable)*,
  TK-5.2.2 *Generador PDF (DomPDF / Snappy)*; fila repetida US-4.2 *Vista de Resultados y Generación PDF*.
- [US-3.1](../../EPIC-03-plan-alimentario/US-3.1-propuesta-de-menu/spec.md): propuesta y plan guardado.
- [US-6.3](../../EPIC-06-gestion-adherencia/US-6.3-portal-de-pacientes/spec.md): portal de pacientes.
