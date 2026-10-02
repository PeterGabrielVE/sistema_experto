# sistema_experto
KATRINA PARA EMITIR RECOMENDACIONES DIETÉTICAS Y EJERCICIOS A PACIENTES CON RESISTENCIA A LA INSULINA 

## Requisitos

- Laravel 13 · PHP 8.3+ · MySQL 8
- Docker y Docker Compose (recomendado)

## Levantar con Docker

```bash
cp .env.example .env
docker compose run --rm --no-deps app php artisan key:generate --show
# copiar la clave generada en APP_KEY dentro de .env

docker compose up -d --build
```

La aplicación queda en http://localhost:8000 (puerto configurable con `APP_PORT`).
Las migraciones se ejecutan automáticamente al iniciar el contenedor (`RUN_MIGRATIONS`).

Cargar datos iniciales (usuario `admin@email.com` / `secret` y catálogos):

```bash
docker compose exec app php artisan db:seed --force
for s in GenderSeeder GroupFoodSeeder PhysicalActivitySeeder TimeOfDaySeeder \
         TypeOfPreparationSeeder UnitOfMeasurementSeeder FoodsSeeder RulesSeeder; do
  docker compose exec app php artisan db:seed --force --class=$s
done
```

Volúmenes persistentes: `db-data` (MySQL), `storage` (logs, sesiones, caché) y
`patient-images` (fotos de pacientes en `public/patient/images`).

Con `APP_ENV=production` el contenedor ejecuta `php artisan optimize` al arrancar.

## Motor de inferencia (Python + ML)

El servicio `inference` (FastAPI + scikit-learn, carpeta [inference/](inference/)) clasifica
al paciente en una categoría (`rules.id`: 1 Bajo peso, 2 Normal, 3 Sobrepeso, 4 Obesidad)
a partir de peso, talla, edad, sexo y actividad física. La categoría determina las
recomendaciones del PDF.

- Al crear un diagnóstico, Laravel ([app/Services/InferenceEngine.php](app/Services/InferenceEngine.php))
  llama a `POST /predict` y guarda `id_rule`, `inference_source`, `inference_confidence` y `model_version`.
- Si el servicio no responde (o `INFERENCE_URL` está vacío), se usan las reglas de IMC en PHP
  (`inference_source = rules`).
- Los doctores (rol 2 y 3) pueden corregir la categoría en la pantalla de resultado
  (`inference_source = manual`). Esas etiquetas son las que aportan información nueva al modelo.
- El modelo base se entrena con datos sintéticos generados desde las reglas de IMC al construir
  la imagen. Para reentrenar incluyendo los diagnósticos confirmados:

```bash
docker compose exec app php artisan inference:train
```

El modelo se guarda en el volumen `models`. `INFERENCE_TOKEN` (en `.env`) protege la API;
el servicio solo es accesible dentro de la red de Docker. Tests: `docker compose exec inference python -m pytest tests`.

## Servicio experto de diagnóstico (Python)

El servicio `expert` (FastAPI, carpeta [expert/](expert/)) calcula índices clínicos y aplica reglas
clínicas versionadas. No tiene estado ni acceso a la base de datos: Laravel le envía los datos y
el servicio responde.

| Endpoint | Uso |
|---|---|
| `POST /evaluate` | Índices, evaluaciones y hallazgos de la consulta |
| `POST /indices` | Solo los índices |
| `GET /rules` | Catálogo de reglas (id, criterio, fuente) |
| `GET /health` | Estado y versión de las reglas (sin token) |

- **Índices:** IMC, cintura/talla, cintura/cadera, HOMA-IR, índice TyG, TG/HDL, colesterol no HDL,
  LDL de Friedewald (si el laboratorio no informó LDL) y categoría de presión arterial. El redondeo
  es "half up", igual que `round()` de PHP, así que los valores coinciden con los de la app.
- **Evaluaciones:** estado glicémico (ADA), resistencia a la insulina (probable con 2 o más de
  HOMA-IR, TyG y TG/HDL alterados; posible con 1) y síndrome metabólico (criterios armonizados 2009,
  cintura ≥ 90/80 cm). Con datos incompletos el resultado es `indeterminado`, nunca `ausente` por omisión.
- **Hallazgos:** cada regla (`GLU-01`, `RI-01`, `SM-01`…) entrega severidad, evidencia y acción
  sugerida, incluidos los exámenes que faltan para completar la evaluación.
- La página de resultado de la consulta muestra la evaluación al equipo médico. Usa las mediciones y
  exámenes asociados a la consulta o, si no hay, los últimos del paciente hasta la fecha de la consulta,
  además de la ficha clínica ([app/Services/ExpertDiagnosisService.php](app/Services/ExpertDiagnosisService.php)).
  Si el servicio no responde (o `EXPERT_URL` está vacío) la página se muestra sin la evaluación.
- **Puntos de corte:** están en un solo archivo, [shared/clinical_thresholds.json](shared/clinical_thresholds.json),
  junto con la fuente de cada uno. Lo leen Laravel (`config('clinical.…')`), el servicio `expert` y el
  servicio `inference` (cortes de IMC). Docker lo copia a las imágenes Python con `additional_contexts`,
  así que después de cambiar un valor hay que reconstruirlas (`docker compose up -d --build`) y,
  en producción, regenerar la caché de configuración de Laravel.
- La meta de LDL depende del riesgo: ≥ 160 mg/dL en general y ≥ 100 mg/dL con diabetes registrada (ADA).
- `EXPERT_TOKEN` (en `.env`) protege la API. Tests: `docker compose exec expert python -m pytest tests`.

## API REST de diagnóstico

La aplicación expone una API JSON en `/api/v1` ([routes/api.php](routes/api.php)) que procesa los
parámetros de un paciente y retorna el diagnóstico: categoría nutricional (servicio `inference` o
reglas de IMC), sus recomendaciones y la evaluación del servicio `expert`.

| Método y ruta | Uso | Acceso |
|---|---|---|
| `POST /api/v1/tokens` | Emite un token con `email` y `password` (5 intentos por minuto) | Usuario con rol |
| `DELETE /api/v1/tokens` | Revoca el token actual | Autenticado |
| `POST /api/v1/diagnoses/evaluate` | Procesa parámetros y retorna el diagnóstico (no guarda nada) | Doctor / Doctor Jefe |
| `GET /api/v1/diagnoses/{id}` | Diagnóstico guardado; `evaluation` solo para el equipo médico | Usuario con rol |

- Autenticación: `Authorization: Bearer <token>`. Cada usuario tiene un token; emitir uno nuevo
  revoca el anterior. Solo se guarda su hash SHA-256 (`users.api_token`).
- Los parámetros siguen el esquema del servicio experto ([expert/app/schemas.py](expert/app/schemas.py))
  más `physical_activity` (0 a 4). Obligatorios: `sex` (`H`/`M`), `age`, `physical_activity`,
  `anthropometry.weight_kg` y `anthropometry.height_cm`.
- `evaluation` es `null` si el servicio experto no está configurado o no responde; la categoría
  se entrega igual.

```bash
TOKEN=$(curl -s -X POST localhost:8000/api/v1/tokens -H 'Accept: application/json' \
  -d email=doctor@correo.cl -d password=… | jq -r .token)   # cuenta con rol Doctor

curl -s -X POST localhost:8000/api/v1/diagnoses/evaluate \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d '{
    "sex": "H", "age": 40, "physical_activity": 2,
    "anthropometry": {"weight_kg": 80, "height_cm": 165, "waist_cm": 98},
    "vitals": {"systolic_bp": 128, "diastolic_bp": 82},
    "labs": {"fasting_glucose": 105, "fasting_insulin": 18.2, "triglycerides": 180, "hdl": 38},
    "conditions": {"hypertension": true}
  }'
```

```json
{
  "data": {
    "bmi": 29.38,
    "category": {"id": 3, "label": "Sobrepeso", "source": "ml", "confidence": 0.91, "model_version": "…"},
    "recommendations": ["…"],
    "evaluation": {"indices": {…}, "assessments": {…}, "findings": [{"rule_id": "RI-01", …}], "ruleset_version": "2026.10.1"}
  }
}
```

## Colas, eventos y auditoría

Docker levanta un worker (`queue`) y el scheduler (`scheduler`) con la cola `database`.

| Evento | Listeners |
|---|---|
| `PatientRegistered`, `PatientDeleted`, `DiagnosisCreated`, `UserRoleChanged`, `ClinicalRecordSaved`, `ClinicalMeasurementRecorded`, `LabResultRecorded` | `RecordAuditTrail` |
| `DiagnosisCategoryConfirmed` | `RecordAuditTrail`, `ScheduleModelRetraining` (encola `RetrainInferenceModel` con 10 min de espera) |
| `UserAccountCreated` | `RecordAuditTrail`, `SendAccountCreatedNotification` (en cola) |

- `RetrainInferenceModel` es único: varias correcciones seguidas generan un solo reentrenamiento.
  También corre cada noche a las 03:00. Manual: `php artisan inference:train [--queue]`.
- La bitácora clínica queda en `storage/logs/audit-YYYY-MM-DD.log` (365 días, `AUDIT_LOG_DAYS`).
- Los permisos están en `app/Policies` (una policy por modelo).

## Registro de mediciones clínicas

`/patient/{id}/measurements` guarda el historial de controles del paciente (varios por paciente,
a diferencia de la ficha clínica que es única): peso, talla, cintura, cadera, % de grasa,
presión arterial, frecuencia cardíaca y glicemia capilar.

- Calcula IMC (mismas categorías que `rules.id`), cintura/talla (> 0,5 = riesgo cardiometabólico),
  cintura/cadera y la categoría de presión arterial (ACC/AHA 2017). Valores referenciales.
- Muestra la última medición con su variación respecto a la anterior y un gráfico de evolución.
- Solo el equipo médico (roles 2 y 3) ve y registra mediciones; eliminar queda restringido a
  quien la registró o a un Doctor Jefe. Cada cambio se audita (solo nombres de campos, sin valores).

## Exámenes de laboratorio

`/patient/{id}/lab-results` guarda el historial de exámenes (tabla `lab_results`, un registro por
fecha de toma de muestra): glicemia en ayunas, insulina basal, HbA1c, colesterol total, HDL, LDL y
triglicéridos.

- Calcula HOMA-IR (> 2,5), índice TyG = ln(TG × glicemia / 2) (> 8,5) y TG/HDL (> 3) como
  indicadores de resistencia a la insulina, y marca los valores fuera del rango de referencia.
- Mediciones y exámenes pueden asociarse a una consulta (`diagnosis_id`, opcional y del mismo
  paciente). La pantalla de resultado de la consulta los muestra y permite registrarlos desde ahí.
  Si se elimina la consulta, los registros se conservan sin vínculo.
- Mismos permisos y auditoría que el registro de mediciones.

## Front-end

Los estilos del dashboard (Now UI) se sirven desde `public/assets`. Vite compila
`resources/js/app.js` y `resources/sass/app.scss` (`npm install && npm run build`);
la imagen Docker lo hace automáticamente.
