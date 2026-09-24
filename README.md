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

## Front-end

Los estilos del dashboard (Now UI) se sirven desde `public/assets`. Vite compila
`resources/js/app.js` y `resources/sass/app.scss` (`npm install && npm run build`);
la imagen Docker lo hace automáticamente.
