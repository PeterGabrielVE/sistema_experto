# CI/CD

Dos workflows de GitHub Actions en [.github/workflows/](../.github/workflows/).

## CI ([ci.yml](../.github/workflows/ci.yml))

Corre en cada pull request y en cada push a `develop`. Solo se ejecutan los jobs cuyos
archivos cambiaron (`shared/` y los workflows activan todo lo que depende de ellos).

| Job | Qué verifica |
| --- | --- |
| PHP · Pint | Estilo (preset Laravel) **solo en los archivos que toca el PR**; los errores aparecen inline en el diff. |
| PHP · Tests | `php artisan test` contra SQLite y contra MySQL 8.4 (el motor de producción). |
| PHP · Dependency audit | `composer audit` sobre `composer.lock`. |
| Front-end | `npm audit` (high+), `npm run build` con Vite. |
| Python · expert / inference | Ruff (errores de sintaxis y pyflakes), pytest y `pip-audit` (informativo, ver abajo). |
| Docker | Construye las 3 imágenes, levanta el stack con `docker compose` y comprueba `/up`, `/` y los `/health` de los servicios Python. |
| Specs · Trazabilidad | `scripts/spec_check.py`: specs, tareas y tests etiquetados alineados ([specs/README.md](../specs/README.md)). |
| **CI passed** | Gate único: falla si algún job falló o se canceló. |

Pint se aplica de forma incremental porque el código existente es anterior a la regla:
quien modifica un archivo lo deja formateado (`vendor/bin/pint <archivo>`). Para formatear todo
de una vez: `vendor/bin/pint` en un PR dedicado.

`pip-audit` no bloquea todavía: `fastapi==0.118.*` fija `starlette 0.48`, que tiene advisories
conocidos (además de `pytest 8.4`). Al actualizar FastAPI, quitar `continue-on-error` del paso.

Aparte, [pr.yml](../.github/workflows/pr.yml) valida el título de cada PR (Conventional Commits; un
`feat` debe nombrar una historia o tarea con spec aprobada). Corre también al editar el título.

## CD ([cd.yml](../.github/workflows/cd.yml))

Corre en cada push a `main`, en tags `vX.Y.Z` y manualmente.

1. **CI completo** (el mismo workflow, sin filtro de rutas). Si falla, no se publica nada.
2. **Publish**: construye y sube a GHCR, con SBOM y attestation de procedencia:
   - `ghcr.io/<owner>/sistema-experto`
   - `ghcr.io/<owner>/sistema-experto-inference`
   - `ghcr.io/<owner>/sistema-experto-expert`

   Tags: `sha-<commit>` siempre, `latest` en `main`, `X.Y.Z` y `X.Y` en tags semver.
3. **Deploy** (opcional, desactivado hasta configurarlo): por SSH copia `compose.yaml` y
   [compose.prod.yaml](../compose.prod.yaml) al servidor, descarga las imágenes `sha-<commit>`
   y reinicia con `docker compose up --wait`. Las migraciones corren al arrancar (`RUN_MIGRATIONS`).

### Activar el despliegue

En el servidor: Docker + Compose ≥ 2.24 y el archivo `.env` de producción en `DEPLOY_PATH`
(`APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, credenciales de BD y tokens). El `.env`
nunca pasa por CI.

En GitHub → Settings → Environments → crear `production` (recomendado: *Required reviewers*
y *Deployment branches: main y tags*) con:

| Tipo | Nombre | Valor |
| --- | --- | --- |
| Variable | `DEPLOY_HOST` | Host o IP del servidor. Definirla activa el job. |
| Variable | `DEPLOY_USER` | Usuario SSH con acceso a Docker. |
| Variable | `DEPLOY_PATH` | Opcional, por defecto `/opt/sistema_experto`. |
| Variable | `APP_URL` | Opcional: URL pública para el health check posterior. |
| Secret | `DEPLOY_SSH_KEY` | Clave privada SSH (solo para este despliegue). |
| Secret | `DEPLOY_KNOWN_HOSTS` | Salida de `ssh-keyscan <host>`. |

`DEPLOY_HOST` debe definirse como variable **del repositorio** (no solo del environment), porque
la condición del job se evalúa antes de entrar al environment.

Rollback: volver a ejecutar el workflow sobre el commit anterior, o en el servidor
`IMAGE_OWNER=<owner> IMAGE_TAG=sha-<commit> docker compose -f compose.yaml -f compose.prod.yaml up -d --no-build`.

## Protección de ramas (recomendado)

En `main` y `develop`: exigir PR, exigir el check **CI passed** y que la rama esté al día.
Dependabot ([dependabot.yml](../.github/dependabot.yml)) abre PRs semanales contra `develop`
para Composer, npm, pip, Docker y las propias Actions.

## Ejecutar lo mismo en local

```bash
php artisan test
vendor/bin/pint --test --diff=develop
npm ci && npm run build
docker compose exec expert python -m pytest tests
docker compose exec inference python -m pytest tests
```
