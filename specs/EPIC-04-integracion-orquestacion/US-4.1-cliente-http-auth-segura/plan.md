# Plan técnico · US-4.1

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

La mayor parte de la historia ya está construida. Laravel llama a los servicios con `Http::baseUrl()
->withToken()->timeout()` ([ExpertDiagnosisService.php](../../../app/Services/ExpertDiagnosisService.php),
[InferenceEngine.php](../../../app/Services/InferenceEngine.php)). Los dos servicios comparan el token Bearer
con `secrets.compare_digest` y dejan abierta solo la ruta `/health`
([expert/app/security.py](../../../expert/app/security.py), [inference/app/main.py](../../../inference/app/main.py)).
Si un servicio falla, Laravel usa la alternativa. El plan etiqueta los tests que ya prueban eso y cierra los
dos huecos de la spec:

1. **AC-3, rechazo por defecto (D-2).** Hoy, si el token está vacío, `require_token` deja pasar todo.
   Pasará a responder `503` con "Credencial del servicio no configurada", salvo que el servicio declare un
   ambiente local con `APP_ENV=local`. Elegí `503` y no `401` porque el problema es de configuración y no
   del cliente, y en Laravel cae en la misma degradación de AC-4. `/health` sigue abierto e informa si la
   credencial está configurada, para que quien opera el despliegue vea el error sin mirar los logs.
2. **AC-5, logs sin valores clínicos.** El `detail` de un error 422 de FastAPI trae `input` (el valor
   enviado) y `ctx`. Laravel lo guardará reducido a `loc` y `type`, es decir, qué campo falló y por qué
   regla, sin el valor.

`APP_ENV` es la variable que ya usa Laravel. `inference` la recibe con el `.env`. A `expert` hay que
pasársela en `compose.yaml`, por defecto `production`, para que olvidarla deje el servicio cerrado y no
abierto.

**Alternativas descartadas:**

- *Que el servicio no arranque sin credencial.* Su healthcheck fallaría y Docker lo reiniciaría en un
  bucle. Laravel ya trata un servicio que no responde, pero el error quedaría escondido entre reinicios.
  Con un `503` explícito y `/health` informando, el problema se ve.
- *JWT con expiración y TLS interno.* Las decisiones D-1 y D-3 los descartaron.
- *No registrar el `detail` en absoluto.* Saber qué campo rechazó el servicio sirve para depurar
  diferencias de esquema entre Laravel y Python. Basta con quitar los valores.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Servicio `expert` | `expert/app/config.py`, `expert/app/security.py`, `expert/app/routers/health.py` | `Settings.environment` (`APP_ENV`); `require_token` responde 503 sin token fuera de local; `/health` con `auth_configured` |
| Servicio `expert` | `expert/tests/conftest.py`, `expert/tests/test_api.py` | Los tests corren con `APP_ENV=local`; tests de AC-2 y AC-3 |
| Servicio `inference` | `inference/app/main.py`, `inference/tests/test_api.py` (y su `conftest`) | La misma regla y los mismos tests |
| Infraestructura | `compose.yaml` | `expert`: `APP_ENV: ${APP_ENV:-production}` |
| Laravel | `app/Services/ExpertDiagnosisService.php` | `errors` del log reducidos a `[{loc, type}]` |
| Laravel | `tests/Feature/ExpertDiagnosisTest.php`, `InferenceEngineTest.php` | Etiquetas de AC-1, AC-4 y AC-6 en tests existentes; tests nuevos de 401/503 y del log sin valores |
| Documentación | `README.md` | Configuración de los tokens y de `APP_ENV` en los servicios |

## Contratos

**Servicios Python** (`expert` e `inference`), en cada ruta salvo `/health`:

| Token configurado | `APP_ENV` | Solicitud | Respuesta |
| --- | --- | --- | --- |
| Sí | cualquiera | Sin `Authorization` o con un token incorrecto | `401` `{"detail": "Invalid token"}` |
| Sí | cualquiera | `Authorization: Bearer <token>` | Procesa la solicitud |
| No | distinto de `local` (o sin definir) | Cualquiera | `503` `{"detail": "Credencial del servicio no configurada"}` |
| No | `local` | Cualquiera | Procesa la solicitud (desarrollo) |

`GET /health` (abierto) agrega:

```json
{"status": "ok", "auth_configured": false}
```

**Log de Laravel ante un 422** (antes y después):

```json
// antes
{"diagnosis": 12, "status": 422, "errors": [{"type": "less_than_equal", "loc": ["body", "labs", "fasting_glucose"], "msg": "Input should be less than or equal to 1000", "input": 2000, "ctx": {"le": 1000}}]}
// después
{"diagnosis": 12, "status": 422, "errors": [{"loc": "body.labs.fasting_glucose", "type": "less_than_equal"}]}
```

## Modelo de datos

Sin cambios.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | La spec se aprueba antes de TK-4.1.1. Como es en parte retroactiva, los tests existentes se etiquetan con sus AC |
| II. Fuente única de verdad clínica | ✅ | Sin valores clínicos nuevos |
| III. Reglas clínicas trazables | ✅ | No aplica |
| IV. Datos incompletos no son negativos | ✅ | No aplica: no cambia cómo se evalúan los datos |
| V. Degradación segura | ✅ | El `503` de un servicio sin credencial cae en la misma alternativa que un servicio caído (AC-4); hay test |
| VI. Los tests son la evidencia | ✅ | PHPUnit y pytest por AC. AC-7 es `[manual]`, con su procedimiento en la spec |
| VII. Privacidad y control de acceso | ✅ | Es el objetivo de la historia: rechazo por defecto sin credencial y logs sin valores clínicos |
| VIII. Idioma | ✅ | Mensajes nuevos en español; código en inglés |
| IX. Simplicidad | ✅ | Sin dependencias ni tablas nuevas. Una variable de entorno (`APP_ENV`) que el proyecto ya usa |

## Estrategia de pruebas

| Criterio | Capa | Test |
| --- | --- | --- |
| AC-1 | Laravel | `ExpertDiagnosisTest::test_result_page_shows_the_expert_evaluation` (existente: `Authorization: Bearer t0k`), se etiqueta |
| AC-1 | Laravel | `InferenceEngineTest::test_uses_ml_service_prediction` (existente: `Bearer t0k`), se etiqueta |
| AC-2 | Python (`expert`) | `test_api.py::test_requires_token_when_configured` (existente), se etiqueta |
| AC-2 | Python (`inference`) | `test_api.py::test_requires_token_when_configured` (existente), se etiqueta |
| AC-3 | Python (`expert`) | `test_api.py::test_without_token_rejects_unless_local` |
| AC-3 | Python (`inference`) | `test_api.py::test_without_token_rejects_unless_local` |
| AC-3 | Python (`expert`) | `test_api.py::test_health_says_whether_auth_is_configured` |
| AC-4 | Laravel | `ExpertDiagnosisTest::test_page_works_when_the_service_is_down_or_invalid` (existente), se etiqueta |
| AC-4 | Laravel | `InferenceEngineTest::test_falls_back_to_rules_when_service_is_down` y `test_falls_back_to_rules_on_invalid_response` (existentes), se etiquetan |
| AC-4 | Laravel | `ExpertDiagnosisTest::test_page_works_when_the_token_is_rejected_or_missing` (401 y 503) |
| AC-4 | Laravel | `InferenceEngineTest::test_falls_back_to_rules_when_the_token_is_rejected_or_missing` |
| AC-5 | Laravel | `ExpertDiagnosisTest::test_invalid_data_is_logged_without_clinical_values` (`Log::spy`: el registro tiene `loc` y `type`, y no los valores enviados) |
| AC-6 | Laravel | `ExpertDiagnosisTest::test_not_called_when_not_configured` e `InferenceEngineTest::test_uses_rules_when_service_not_configured` (existentes), se etiquetan |
| AC-7 | Manual | Procedimiento de la spec, en el PR de TK-4.1.2 |

El tiempo de espera de AC-1 no se puede observar con `Http::fake`. Queda cubierto por la configuración
(`services.*.timeout`) y por el test de AC-4, que simula el servicio sin respuesta (`ConnectionException`).

## Riesgos y decisiones

- **Despliegues sin `APP_ENV` en `expert`:** con el valor por defecto `production`, un despliegue sin
  `EXPERT_TOKEN` empieza a responder `503`. Así lo quiere D-2, pero cambia el comportamiento: antes de
  desplegar hay que confirmar que `EXPERT_TOKEN` e `INFERENCE_TOKEN` estén definidos en producción. Va
  en la descripción del PR de TK-4.1.2.
- **Tests de Python:** hoy corren sin token y esperan `200`. El `conftest` fija `APP_ENV=local` para
  todos, y los tests de AC-3 lo quitan explícitamente.
- **`.env.example` ya trae tokens de ejemplo (`change-me`).** En local siguen funcionando, aunque no
  se declare el ambiente.
