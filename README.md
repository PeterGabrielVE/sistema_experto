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

## Front-end

Los estilos del dashboard (Now UI) se sirven desde `public/assets`. Vite compila
`resources/js/app.js` y `resources/sass/app.scss` (`npm install && npm run build`);
la imagen Docker lo hace automáticamente.
