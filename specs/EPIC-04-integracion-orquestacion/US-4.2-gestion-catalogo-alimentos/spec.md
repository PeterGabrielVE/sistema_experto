---
id: US-4.2
title: Gestionar el catálogo de alimentos desde la aplicación
epic: EPIC-04
status: draft
owner: Laravel Dev · Python Dev
---

# US-4.2 · Gestionar el catálogo de alimentos desde la aplicación

## Contexto

La **importación** del catálogo ya existe y está especificada en US-3.1 (AC-8, tarea TK-3.1.1):
[shared/food_catalog.csv](../../../shared/food_catalog.csv) es la fuente única, `php artisan foods:import`
la carga en la tabla `foods` por `id`, y el servicio `expert` lee el mismo archivo (`GET /foods`,
`POST /meal-plan` sin `foods`). Por eso la tarea del backlog TK-4.2.1 (*script ETL con Pandas*) ya
está resuelta de otra forma: el importador valida y lista cada error por línea sin necesidad de Pandas.

Lo que falta es TK-4.2.2: hoy, para agregar o corregir un alimento hay que editar el CSV y ejecutar el
comando, lo que depende de un desarrollador. El equipo clínico quiere hacerlo desde la aplicación.

Esto choca con el principio II de la [constitución](../../constitution.md) (fuente única de verdad):
si la aplicación edita la tabla `foods`, el CSV y el servicio `expert` quedan desactualizados. La
pregunta abierta 1 debe resolverse antes de aprobar.

## Historia de usuario

**Como** médico a cargo de los planes alimentarios,
**quiero** agregar, corregir y retirar alimentos del catálogo desde la aplicación,
**para** mantener el catálogo al día sin depender de un desarrollador.

## Criterios de aceptación

- **AC-1** — Dado el catálogo, cuando el médico abre *Alimentos*, entonces ve los alimentos con su
  grupo de intercambio, porción en gramos, energía, macronutrientes, índice glicémico, alérgenos y
  precio, puede buscarlos por nombre y filtrarlos por grupo, y ve marcados los datos a revisar
  (energía que no calza con 4/4/9 kcal/g, carbohidratos sin índice glicémico).
- **AC-2** — Dado el formulario de alimento, cuando el médico lo guarda, entonces se valida que el grupo
  sea uno de los que usa el generador, la porción y la energía sean mayores que cero, los nutrientes no
  sean negativos y los alérgenos pertenezcan al vocabulario de `meal_plan.allergens`; con un error, no
  se guarda nada y se indica el campo.
- **AC-3** — Dado un alimento editado, cuando se genera una propuesta de menú nueva, entonces usa los
  valores nuevos; las propuestas ya guardadas no cambian (US-3.1/AC-10).
- **AC-4** — Dado un alimento que aparece en menús guardados, cuando el médico lo retira, entonces deja de
  usarse en propuestas nuevas pero no se borra, y los menús guardados se siguen mostrando completos.
- **AC-5** — Dado un cambio hecho en la aplicación, cuando se vuelve a importar el catálogo o se
  reinicia el servicio `expert`, entonces el cambio no se pierde y Laravel y el servicio usan los mismos
  alimentos (según lo que resuelva la pregunta abierta 1).
- **AC-6** — Dado un usuario sin el rol autorizado (pregunta abierta 2), cuando intenta ver o modificar el
  catálogo, entonces recibe un error 403 y no se modifica nada.
- **AC-7** — Dado cualquier alta, edición o retiro, cuando se guarda, entonces queda registrado quién lo
  hizo, cuándo y qué valores cambiaron.

## Requisitos no funcionales

- **NFR-1** — El listado responde en menos de 1 s con el catálogo completo (cientos de alimentos).

## Fuera de alcance

- Importar bases externas (USDA u otras) o hacer edición masiva desde la aplicación.
- Recetas o alimentos compuestos.
- Precios en línea: el `price` sigue siendo referencial.

## Preguntas abiertas

- [ ] **1. Fuente de verdad del catálogo** (Doctor Jefe + desarrolladores). Opciones:
  a) la base de datos pasa a ser la fuente y el CSV se **exporta** desde ella (el servicio `expert`
  recibe siempre `foods` desde Laravel); b) el CSV sigue siendo la fuente y la aplicación genera un
  cambio al CSV que se revisa en un PR; c) se mantiene la importación manual y esta historia se descarta.
  Afecta a la constitución II y a US-3.1/AC-8.
- [ ] **2. Roles:** ¿quién edita el catálogo, Doctor y Doctor Jefe o solo Doctor Jefe? ¿El Administrador
  puede verlo? (El backlog dice "nutricionista", rol que no existe en el sistema.)
- [ ] **3.** ¿Qué tolerancia se acepta entre la energía declarada y la calculada por 4/4/9 kcal/g antes de
  impedir guardar (o solo advertir)?
- [ ] **4.** ¿Se descarta TK-4.2.1 (ETL con Pandas) por estar cubierta por TK-3.1.1?

## Referencias

- US-3.1, AC-8: [importación del catálogo](../../EPIC-03-plan-alimentario/US-3.1-propuesta-de-menu/spec.md).
- Backlog (Notion): US-4.2 *Importación de Catálogo de Alimentos*, TK-4.2.1, TK-4.2.2.
