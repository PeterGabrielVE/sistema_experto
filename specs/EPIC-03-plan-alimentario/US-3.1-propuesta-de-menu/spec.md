---
id: US-3.1
title: Generar propuesta de menú ajustada a calorías y macros
epic: EPIC-03
status: implemented
owner: Equipo médico (Doctor Jefe)
---

# US-3.1 · Generar propuesta de menú ajustada a calorías y macros

> Spec escrita después de la implementación (retro-spec) a partir del código, el README y los
> tests existentes. Sirve como referencia del formato para las historias nuevas.

## Contexto

Después de la consulta, el médico tiene el requerimiento energético del paciente y los gramos de
carbohidratos, proteínas y lípidos por día (plan de macronutrientes del motor experto). Armar a mano un
menú que cumpla esas metas con porciones de intercambio toma tiempo y es propenso a errores, sobre
todo cuando hay que respetar topes de carga glucémica, alergias y un presupuesto. El paciente con
resistencia a la insulina necesita un menú concreto, no solo cifras.

## Historia de usuario

**Como** médico (Doctor o Doctor Jefe),
**quiero** que el sistema proponga un menú de uno o varios días que cumpla las calorías y los
macronutrientes de la consulta,
**para** entregar al paciente un plan alimentario concreto, seguro y revisado por mí en minutos.

## Criterios de aceptación

- **AC-1** — Dado una consulta con requerimiento energético y gramos de carbohidratos, proteínas y
  lípidos, cuando el médico abre la página de resultado, entonces ve una propuesta con desayuno,
  colación, almuerzo, once y cena, con alimentos, gramos y porciones de intercambio.
- **AC-2** — Dado las metas del día, cuando se genera el menú, entonces la energía queda dentro de
  ±3 %, cada macronutriente dentro de ±5 % y cada comida dentro de ±10 % de su energía; si las metas
  no se pueden alcanzar con el catálogo, el desvío se minimiza y la propuesta lo informa.
- **AC-3** — Dado las pautas de `meal_plan` en `shared/clinical_thresholds.json`, cuando se genera
  el menú, entonces cumple los grupos obligatorios por comida, las porciones diarias por grupo
  (≥ 3 verduras, 2-4 frutas, 2-3 lácteos, legumbres hasta 1), los topes por grupo y alimento, y no
  repite un alimento en el día.
- **AC-4** — Dado un plan de macronutrientes con topes de carga glucémica o grasa saturada, cuando
  se genera el menú, entonces no los supera; sin plan, usa los topes generales (carga glucémica 120,
  grasa saturada 10 % de la energía). Los alimentos con carbohidratos sin índice glicémico se informan.
- **AC-5** — Dado la propuesta, cuando el médico pide 3 o 7 días u *Otra variante*, entonces obtiene
  días distintos entre sí con la misma calidad respecto de las metas; la misma consulta y variante
  producen siempre el mismo menú (la página y el PDF coinciden).
- **AC-6** — Dado alergias o intolerancias en la ficha clínica (etiquetas o texto libre, por ejemplo
  "celíaca, alergia al maní"), cuando se genera el menú, entonces ningún alimento con ese alérgeno ni
  nombrado aparece en él; lo que no se reconoce se informa para revisarlo a mano.
- **AC-7** — Dado un presupuesto diario en CLP (entre 500 y 1.000.000), cuando se genera el menú,
  entonces el costo del día no lo supera si las pautas caben en él; si no caben, lo excede lo menos
  posible y lo informa. Cada alimento y día muestra su costo. Un presupuesto fuera de rango se ignora.
- **AC-8** — Dado el catálogo `shared/food_catalog.csv`, cuando se importa (`php artisan foods:import`),
  entonces la tabla `foods` se actualiza por `id`, se conservan los alimentos que ya no están en el
  archivo y un archivo con errores no escribe nada y lista cada error con su línea. Los alimentos sin
  valores nutricionales no se usan y se informan.
- **AC-9** — Dado una propuesta, cuando el médico la revisa en el editor y la guarda, entonces el
  servidor recalcula todas las cantidades desde el catálogo, la guarda en `meal_plans` (una por
  consulta, marcada si se ajustó a mano) y *Descartar* vuelve a la propuesta automática. Solo los
  médicos pueden editar.
- **AC-10** — Dado una propuesta guardada, cuando se abre la página de resultado o el PDF, entonces
  se muestra lo guardado aunque después cambien el catálogo o las reglas.
- **AC-11** — Dado una consulta sin metas o el servicio experto sin responder, cuando se abre la página
  de resultado, entonces la página se muestra igual, sin propuesta y con el motivo; sin metas no se
  llama al servicio ni se ofrece el editor.

## Requisitos no funcionales

- **NFR-1** — Cada día del menú se resuelve en menos de un segundo en el caso típico; el solver tiene
  un límite de `meal_plan.time_limit_seconds` (5 s) por día.
- **NFR-2** — Las restricciones de alergias son duras: se aplican antes de optimizar, nunca como
  penalización.

## Fuera de alcance

- Recetas, preparación o lista de compras.
- Menús de más de 7 días o planificación por semanas con rotación fija.
- Precios en línea de supermercados (el `price` del catálogo es referencial y se actualiza a mano).
- Que el paciente edite su propio menú.

## Preguntas abiertas

- [ ] ¿El editor de la propuesta (AC-9) es parte de US-3.1 o una historia propia (US-3.2)? Si se separa,
  AC-9 y AC-10 pasan a la nueva spec y aquí quedan como referencia.

## Referencias

- Guías alimentarias y porciones de intercambio: `meal_plan` en
  [shared/clinical_thresholds.json](../../../shared/clinical_thresholds.json).
- Índice glicémico: Atkinson F. S. et al., *International tables of glycemic index and glycemic load
  values 2021*, Am J Clin Nutr.
- Commits: `94b1a3a` (US-3.1), `f6b2d49` (TK-3.1.1), `308cc4d` (TK-3.1.2).
