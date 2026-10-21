---
id: US-4.2
title: Gestionar el catálogo de alimentos desde la aplicación
epic: EPIC-04
status: approved
owner: Laravel Dev · Python Dev
---

# US-4.2 · Gestionar el catálogo de alimentos desde la aplicación

## Contexto

La **importación** del catálogo ya existe y está especificada en US-3.1 (AC-8, tarea TK-3.1.1):
[shared/food_catalog.csv](../../../shared/food_catalog.csv) es la fuente única, `php artisan foods:import`
la carga en la tabla `foods` por `id`, y el servicio `expert` lee el mismo archivo (`GET /foods`,
`POST /meal-plan` sin `foods`). Por eso la tarea del backlog TK-4.2.1 (*script ETL con Pandas*) ya
está resuelta de otra forma: el importador valida y lista cada error por línea sin necesidad de Pandas
(D-4).

Lo que falta es TK-4.2.2: hoy, para agregar o corregir un alimento hay que editar el CSV y ejecutar el
comando, lo que depende de un desarrollador. El equipo clínico quiere hacerlo desde la aplicación.

Esto choca con el principio II de la [constitución](../../constitution.md) (fuente única de verdad):
si la aplicación edita la tabla `foods`, el CSV queda desactualizado. Se resuelve en D-1: la base de
datos pasa a ser la fuente y la constitución se modifica en un PR propio.

## Historia de usuario

**Como** Doctor Jefe a cargo de los planes alimentarios,
**quiero** agregar, corregir y retirar alimentos del catálogo desde la aplicación,
**para** mantener el catálogo al día sin depender de un desarrollador.

## Criterios de aceptación

- **AC-1** — Dado el catálogo, cuando un médico abre *Alimentos*, entonces ve los alimentos activos con su
  grupo de intercambio, porción en gramos, energía, macronutrientes, índice glicémico, alérgenos y
  precio, puede buscarlos por nombre y filtrarlos por grupo, y ve marcados los datos a revisar
  (energía que difiere de la calculada por 4/4/9 kcal/g más que la tolerancia de D-3, carbohidratos
  sin índice glicémico).
- **AC-2** — Dado el formulario de alimento, cuando el Doctor Jefe lo guarda, entonces se valida que el
  grupo sea uno de los que usa el generador, la porción y la energía sean mayores que cero, los
  nutrientes no sean negativos y los alérgenos pertenezcan al vocabulario de `meal_plan.allergens`; con
  un error, no se guarda nada y se indica el campo. Si la energía queda fuera de la tolerancia de D-3,
  se guarda igual, con una advertencia, y el alimento queda marcado como dato a revisar.
- **AC-3** — Dado un alimento editado, cuando se genera una propuesta de menú nueva, entonces usa los
  valores nuevos; las propuestas ya guardadas no cambian (US-3.1/AC-10).
- **AC-4** — Dado un alimento que aparece en menús guardados, cuando el Doctor Jefe lo retira, entonces deja
  de usarse en propuestas nuevas y en el editor, pero no se borra, y los menús guardados se siguen
  mostrando completos.
- **AC-5** — Dado un cambio hecho en la aplicación, cuando se vuelve a importar el catálogo o se
  reinicia el servicio `expert`, entonces el cambio no se pierde y Laravel y el servicio usan los mismos
  alimentos; el Doctor Jefe puede descargar el catálogo como CSV con el formato de la importación (D-1).
- **AC-6** — Dado un Doctor, cuando intenta crear, editar o retirar un alimento, entonces recibe un error
  403; un Administrador recibe 403 también al intentar verlo; en ambos casos no se modifica nada (D-2).
- **AC-7** — Dado cualquier alta, edición o retiro, cuando se guarda, entonces queda registrado quién lo
  hizo, cuándo y qué valores cambiaron.

## Requisitos no funcionales

- **NFR-1** — El listado responde en menos de 1 s con el catálogo completo (cientos de alimentos).

## Fuera de alcance

- Importar bases externas (USDA u otras) o hacer edición masiva desde la aplicación.
- Recetas o alimentos compuestos.
- Precios en línea: el `price` sigue siendo referencial.
- Reactivar un alimento retirado desde la aplicación.

## Decisiones funcionales

### D-1. Fuente de verdad del catálogo

La tabla `foods` pasa a ser la fuente de verdad. Laravel envía siempre los alimentos al servicio
`expert` al generar un menú (ya lo hace). `shared/food_catalog.csv` queda como **carga inicial** de un
ambiente nuevo: `foods:import` solo agrega los alimentos que no están en la base y nunca sobrescribe los
existentes. El catálogo se puede **exportar** desde la aplicación con el mismo formato. Esto modifica el
principio II de la constitución, en un PR propio (`docs: constitución ...`), y US-3.1/AC-8.

### D-2. Roles

Solo el **Doctor Jefe** crea, edita y retira alimentos, igual que el resto de la base de conocimiento
(reglas, recomendaciones, horarios). Los Doctores ven el catálogo. El Administrador no tiene acceso.

### D-3. Energía que no calza con los macronutrientes

Si la energía declarada difiere en más de **15 %** de la calculada con 4/4/9 kcal/g, se advierte y el
alimento queda marcado como dato a revisar, pero se guarda: la fibra, los polialcoholes o el alcohol
explican diferencias reales. El 15 % va en `shared/clinical_thresholds.json`, con su fuente, y lo usan
Laravel y el servicio `expert`.

### D-4. TK-4.2.1 (ETL con Pandas)

Se descarta: está cubierta por TK-3.1.1, el importador que valida y reporta los errores por línea.

## Preguntas abiertas

- [x] **1. Fuente de verdad del catálogo.** Respondida en D-1: la base de datos, con el CSV como carga
  inicial y exportación.
- [x] **2. Roles.** Respondida en D-2: solo el Doctor Jefe edita; los Doctores ven; el Administrador no
  tiene acceso.
- [x] **3.** ¿Qué tolerancia se acepta entre la energía declarada y la calculada? Respondida en D-3: 15 %,
  solo advertencia.
- [x] **4.** ¿Se descarta TK-4.2.1? Respondida en D-4: sí.

## Referencias

- US-3.1, AC-8: [importación del catálogo](../../EPIC-03-plan-alimentario/US-3.1-propuesta-de-menu/spec.md).
- Backlog (Notion): US-4.2 *Importación de Catálogo de Alimentos*, TK-4.2.1, TK-4.2.2.
- Factores de Atwater generales: FAO, *Food energy – methods of analysis and conversion factors*,
  Food and Nutrition Paper 77, 2003.
