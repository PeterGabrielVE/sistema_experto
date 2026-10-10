# Constitución del proyecto

Principios que toda spec, plan e implementación debe cumplir. Cada `plan.md` los revisa uno a
uno en su sección *Verificación de la constitución*; si un plan necesita romper alguno, lo
justifica ahí y la excepción se aprueba en el PR. Cambiar este archivo requiere un PR propio
(`docs: constitución ...`) aprobado por el equipo.

## I. Spec antes que código

Ningún cambio de comportamiento se implementa sin una spec en estado `approved` o posterior.
La spec dice **qué** y **por qué**; el plan, **cómo**. Si durante la implementación cambia lo que
se quiere, se actualiza primero la spec. Los PR `feat` nombran su historia o tarea
(`feat(US-3.1): ...`, `feat(TK-3.1.2): ...`) y el CI lo verifica.

## II. Fuente única de verdad clínica

Los puntos de corte, rangos y vocabularios clínicos viven en
[shared/clinical_thresholds.json](../shared/clinical_thresholds.json), con la fuente bibliográfica
de cada valor; el catálogo de alimentos, en [shared/food_catalog.csv](../shared/food_catalog.csv).
Laravel y los servicios Python los leen de ahí. Ningún valor clínico se escribe en el código.

## III. Reglas clínicas trazables

Cada regla tiene un identificador estable (`GLU-01`, `RI-01`, `MAC-03`, `CFG-<id>`) y entrega su
evidencia: los datos de entrada y el corte aplicado. Una regla no se renumera ni se reutiliza su id;
si deja de aplicarse, se elimina y su id queda retirado.

## IV. Datos incompletos no son datos negativos

Si falta un dato, el resultado es `indeterminado` y se informa qué falta; nunca `ausente` por
omisión. Lo que el sistema no reconoce (por ejemplo, una alergia en texto libre) se informa para
revisarlo a mano, no se ignora.

## V. Degradación segura

Laravel sigue atendiendo si los servicios Python no responden: la clasificación usa las reglas de
IMC y la página de resultado se muestra sin la evaluación experta. Un servicio caído nunca
impide registrar una consulta. El sistema sugiere y el profesional decide: toda propuesta
automática es editable por el médico.

## VI. Los tests son la evidencia

Cada criterio de aceptación tiene al menos un test automatizado que lo declara
(`#[Group('US-3.1/AC-2')]` o `@pytest.mark.spec("US-3.1/AC-2")`); los que no se pueden
automatizar se marcan `[manual]` en la spec con el procedimiento de verificación. La suite pasa en
SQLite y en MySQL 8.4 (producción): sin ids fijos, sin depender del orden de claves JSON ni de
datos aleatorios que puedan colisionar.

## VII. Privacidad y control de acceso

Los datos de pacientes son datos de salud. Toda ruta nueva declara qué roles la usan y lo
verifica una policy o un middleware con su test. Los tests y fixtures usan datos ficticios; los
logs no guardan datos clínicos identificables. Las APIs entre servicios se protegen con token.

## VIII. Idioma

Interfaz, specs y documentación en español de Chile; código, identificadores y comentarios en
inglés. Los números se muestran con coma decimal (`2,5`).

## IX. Simplicidad

Se implementa lo que pide la spec y nada más (lo demás va a *Fuera de alcance*). Una dependencia
nueva, un servicio nuevo o una tabla nueva se justifican en el plan con la alternativa descartada.
