---
id: US-4.1
title: Enviar los datos clínicos a los servicios Python de forma segura
epic: EPIC-04
status: draft
owner: Laravel Dev · Python Dev
---

# US-4.1 · Enviar los datos clínicos a los servicios Python de forma segura

> Spec escrita en gran parte después de la implementación (retro-spec), a partir del código, el README y
> los tests. Agrega dos criterios que el código todavía no cumple (AC-3 y AC-5).

## Contexto

Laravel delega en dos servicios Python: el sistema experto (índices, reglas clínicas, distribución de
macronutrientes y menú) y el motor de inferencia (clasificación por IMC con aprendizaje automático).
Cada consulta les envía datos de salud del paciente: antropometría, exámenes, antecedentes y alergias.

Hoy ya existe:
- Laravel llama a cada servicio con una credencial compartida y un tiempo de espera, ambos configurables.
- Los servicios rechazan una credencial incorrecta.
- Si un servicio no responde, la consulta sigue funcionando (constitución V).

Al revisar el código para esta spec aparecieron dos huecos:

1. **Sin credencial configurada, los servicios aceptan cualquier petición.** Basta con que falte una
   variable de entorno en un despliegue para que el servicio quede abierto a cualquiera que lo alcance.
2. **Un rechazo por datos inválidos queda en el log con los valores enviados.** El detalle de error del
   servicio incluye los datos de entrada, y el registro incluye la consulta. Esto contradice la
   constitución VII: los logs no guardan datos clínicos identificables.

En el backlog hay dos filas con este id: *Cliente HTTP Laravel ↔ Python* (Integración & Reportería) y
*Cliente HTTP y Auth Segura API* (Integración & Orquestación), con las tareas TK-4.1.1 y TK-4.1.2.
Describen lo mismo y se tratan como una sola historia.

## Historia de usuario

**Como** sistema que atiende pacientes,
**quiero** enviar los datos clínicos a los servicios Python solo por un canal autenticado y sin dejarlos
en los registros,
**para** que nadie más que Laravel pueda consultar o alterar las evaluaciones, y la información de salud no
se filtre.

## Criterios de aceptación

- **AC-1** — Dado una consulta, cuando Laravel pide la evaluación, el menú o la clasificación a un servicio
  Python, entonces la solicitud lleva la credencial de ese servicio y espera a lo sumo el tiempo
  configurado para él.
- **AC-2** — Dado un servicio Python con credencial configurada, cuando recibe una solicitud de datos sin
  credencial o con una incorrecta, entonces la rechaza como no autorizada y no procesa los datos; solo la
  verificación de salud (sin datos) responde sin credencial.
- **AC-3** — Dado un servicio Python desplegado sin credencial configurada, cuando recibe una solicitud de
  datos, entonces la rechaza e informa que falta configurar la credencial, en vez de atenderla sin
  autenticación (pregunta 2).
- **AC-4** — Dado un servicio Python caído, lento, con una respuesta inválida o que rechaza la credencial,
  cuando Laravel lo llama, entonces la consulta se guarda igual: la clasificación usa las reglas de IMC, la
  página de resultado se muestra sin la evaluación experta y el menú indica que no se pudo generar
  (constitución V).
- **AC-5** — Dado un error al llamar a un servicio Python, cuando Laravel lo registra en el log, entonces el
  registro dice qué servicio falló, el código de respuesta y qué campos fueron rechazados, pero no incluye
  ningún valor clínico enviado.
- **AC-6** — Dado que la dirección de un servicio no está configurada, cuando se atiende una consulta,
  entonces Laravel no intenta llamarlo y usa la alternativa de AC-4.
- **AC-7** `[manual]` — Dado el despliegue de producción, cuando se intenta llegar a los servicios Python
  desde fuera del servidor, entonces no responden: solo Laravel los alcanza por la red interna.
  Procedimiento: con el despliegue de producción levantado, intentar desde otro equipo una solicitud al
  puerto de cada servicio y comprobar que no hay respuesta; luego comprobar desde el contenedor de
  Laravel que la verificación de salud sí responde.

## Fuera de alcance

- La API REST pública de Laravel (`/api/v1`) y sus tokens de usuario: ya tienen su propio control de
  acceso (US-6.3 bloqueó a los pacientes).
- Rotar las credenciales automáticamente o guardarlas en un gestor de secretos.
- Cifrar los datos clínicos en reposo.

## Preguntas abiertas

- [ ] **1. Tipo de credencial** (desarrolladores): ¿basta con un secreto compartido por servicio, como hoy
  (una "API key" enviada como token Bearer), o se requiere JWT con expiración? El backlog (TK-4.1.2) admite
  ambos. El secreto compartido es más simple; el JWT limita el daño si una credencial se filtra, pero
  agrega firma, expiración y reloj sincronizado.
- [ ] **2. Desarrollo local** (desarrolladores): con AC-3, ¿un servicio sin credencial debe rechazar las
  solicitudes también en desarrollo local, o se permite trabajar sin credencial solo cuando el ambiente
  se declara explícitamente como local?
- [ ] **3. Cifrado entre servicios** (desarrolladores e infraestructura): ¿basta con la red interna de
  Docker entre Laravel y los servicios, o se exige TLS también dentro del servidor?

## Referencias

- Backlog (Notion): US-4.1 *Cliente HTTP y Auth Segura API* (y *Cliente HTTP Laravel ↔ Python*), TK-4.1.1
  *Implementar Laravel HTTP Client*, TK-4.1.2 *Autenticación Server-to-Server*.
- Constitución: V (degradación segura) y VII (privacidad y control de acceso).
