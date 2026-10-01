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
