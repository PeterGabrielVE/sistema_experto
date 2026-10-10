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

## Desarrollo guiado por specs

Cada historia de usuario tiene su spec en [specs/](specs/) (qué, criterios de aceptación, plan y
tareas) y cada criterio, un test que lo declara. El CI verifica que estén alineados. Proceso,
convenciones y comandos en [specs/README.md](specs/README.md); principios en
[specs/constitution.md](specs/constitution.md).

## CI/CD

GitHub Actions: tests, lint y build en cada PR; publicación de imágenes en GHCR y despliegue
opcional al hacer merge a `main`. Detalles y configuración en [docs/ci-cd.md](docs/ci-cd.md).

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
| `GET /rules` | Catálogo de reglas (id, criterio, fuente), incluidas las de macronutrientes (`macro_rules`) |
| `POST /meal-plan` | Menú del día por programación lineal entera (metas, catálogo de alimentos y semilla) |
| `GET /health` | Estado y versión de las reglas (sin token) |

- **Índices:** IMC, cintura/talla, cintura/cadera, HOMA-IR, índice TyG, TG/HDL, colesterol no HDL,
  LDL de Friedewald (si el laboratorio no informó LDL), categoría de presión arterial y:
  - **Perfil aterogénico:** Castelli I (CT/HDL), Castelli II (LDL/HDL, con LDL informado o de Friedewald)
    y el índice aterogénico del plasma, AIP = log10(TG/HDL en mmol/L).
  - **FLI** (Fatty Liver Index, Bedogni 2006): triglicéridos, IMC, cintura y GGT. < 30 descarta y ≥ 60
    sugiere esteatosis hepática.
  - **FINDRISC** (riesgo de diabetes tipo 2 a 10 años, 0 a 26 puntos): edad, IMC y cintura de la consulta
    más el cuestionario de la ficha clínica (actividad física, verduras o frutas, antihipertensivos,
    glucosa alta alguna vez y familiares con diabetes). Prediabetes registrada cuenta como glucosa alta.
    Solo se calcula en adultos sin diabetes registrada y con todas las respuestas; si falta alguna, el
    hallazgo `DAT-01` indica cuáles.

  El redondeo es "half up", igual que `round()` de PHP, así que los valores coinciden con los de la app.
- **Evaluaciones:** estado glicémico (ADA), resistencia a la insulina (probable con 2 o más de
  HOMA-IR, TyG y TG/HDL alterados; posible con 1), síndrome metabólico (criterios armonizados 2009,
  cintura ≥ 90/80 cm) y perfil aterogénico (alterado con Castelli I o II, AIP o colesterol no HDL sobre
  el corte; limítrofe con AIP intermedio). Con datos incompletos el resultado es `indeterminado`, nunca `ausente` por omisión.
- **Hallazgos:** cada regla (`GLU-01`, `RI-01`, `SM-01`…) entrega severidad, evidencia y acción
  sugerida, incluidos los exámenes que faltan para completar la evaluación.
- **Distribución de macronutrientes** (`macronutrients`, solo adultos con edad, peso y talla;
  [expert/app/nutrition.py](expert/app/nutrition.py)): energía por Mifflin-St Jeor × factor de actividad
  (`physical_activity` 0 a 4), con déficit de 500/750 kcal en sobrepeso/obesidad o superávit de 400 kcal
  en bajo peso y mínimo de 1500/1200 kcal (hombres/mujeres). Parte de 50 % carbohidratos, 20 % proteínas
  y 30 % grasas y aplica las reglas `MAC-01` a `MAC-09`: carbohidratos 40 % con diabetes y 45 % con
  prediabetes, resistencia a la insulina, síndrome metabólico, triglicéridos altos o hígado graso; grasa
  saturada < 7 % con LDL alto o perfil aterogénico; sodio < 1500 mg con hipertensión; proteína mínima de
  0,8 g/kg, 1,0 desde los 65 años y 1,2 con cambio de peso (sobre el peso a IMC 25 si es mayor). Gana el
  porcentaje de carbohidratos más bajo; si la proteína no alcanza el mínimo, se toma de los carbohidratos.
  Entrega gramos por macronutriente, límites de grasa saturada, azúcares añadidos, fibra, sodio y carga
  glucémica (máximo 120 al día; 100 con alteración glicémica), y las
  reglas aplicadas con su evidencia. Cortes en `nutrition` de `shared/clinical_thresholds.json`.
  El formulario de consulta la pide al abrir el modal (`POST /diagnosis/{patient}/macros`, con el
  peso, talla, edad y actividad del formulario más la ficha y los últimos exámenes) y rellena gramos
  por día, gramos por comida (un tercio), requerimiento en kcal/día y kcal/kg; los campos quedan
  editables y, si el servicio no responde, se ingresan a mano.
- **Reglas configurables** (menú *Reglas de macronutrientes*, solo Doctor Jefe): "si variable
  operador valor, entonces acciones", por ejemplo *HOMA-IR > 2,5 → carga glucémica máxima 80 y
  carbohidratos máximo 45 %*. Las variables (índices calculados, exámenes, presión, cintura, peso, edad),
  los operadores y el rango permitido de cada acción están en `configurable_macro_rules` de
  `shared/clinical_thresholds.json`; Laravel y el servicio validan con esos valores. Las reglas activas
  (tabla `macro_rules`) se envían en `macro_rules` con cada `POST /evaluate` y se aplican después de las
  `MAC-xx` con la misma combinación (gana el valor más restrictivo; el ajuste de energía se suma). Si
  falta el dato, la regla no se aplica. En la evaluación aparecen como `CFG-<id>` con `configured: true`.
- **Plan alimentario por programación lineal** ([expert/app/meal_plan.py](expert/app/meal_plan.py),
  PuLP con el solver HiGHS): elige medias porciones de intercambio del catálogo `foods` para
  desayuno, colación, almuerzo, once y cena, de modo que el día cumpla el requerimiento y los gramos de
  la consulta (±3 % energía, ±5 % macronutrientes, ±10 % la energía de cada comida; fuera de esa banda el
  desvío se minimiza). Restricciones (en `meal_plan` de `shared/clinical_thresholds.json`): alimentos
  permitidos por comida, grupos obligatorios (verduras y proteína en almuerzo y cena, pan o cereal al
  desayuno y once), porciones diarias por grupo según las guías (≥ 3 verduras, 2-4 frutas, 2-3 lácteos,
  legumbres hasta 1), un alimento por grupo y comida (dos verduras) y porciones máximas por alimento.
  Como los alimentos de un grupo de intercambio son equivalentes, el modelo decide por grupo y perfil
  nutricional y después asigna alimentos concretos con una semilla, sin repetirlos en el día; resuelve
  en menos de un segundo. La carga glucémica del día (índice glicémico × carbohidratos / 100) y la grasa
  saturada tienen un máximo: el del plan de macronutrientes del paciente (por ejemplo, carga glucémica
  80 si una regla configurada lo fija) o el general (120 y 10 % de la energía). Para eso `foods` tiene
  `glycemic_index` y `saturated_fat` (g por porción), con valores de referencia aproximados (índice
  glicémico de las tablas internacionales, Atkinson et al. 2021) en el catálogo de alimentos.
  Para que los menús sean naturales hay topes por grupo y comida (una fruta, un aceite, dos lácteos),
  porciones mínimas (carnes, verduras y cereales desde 1 porción) y topes por alimento (huevo: 2).
  Con `days` (hasta 7) genera un menú por día; cada día resuelve su propio modelo y encarece lo que ya
  usaron los días anteriores, así la semana varía en estructura y no solo en nombres.
  **Alergias** ([expert/app/allergies.py](expert/app/allergies.py)): `allergies` recibe etiquetas
  (`gluten`) o el texto libre de la ficha clínica ("celíaca, alergia al maní"); reconoce los alérgenos
  del vocabulario `meal_plan.allergens` (gluten, lactosa, leche, huevo, soya, maní, frutos secos,
  pescado, mariscos) y los alimentos nombrados (kiwi), y deja fuera esos alimentos antes de optimizar:
  es una restricción dura. Lo que no reconoce se informa para revisarlo a mano. **Presupuesto**:
  `budget` (CLP por día) limita el costo del día con el `price` por porción del catálogo; superarlo pesa
  más que alejarse de las metas, y si las pautas no caben en él el plan lo dice. Cada alimento y día
  trae su `cost`. Laravel envía las alergias de la ficha y el presupuesto del editor (`?presupuesto=`).
- **Catálogo de composición nutricional** ([shared/food_catalog.csv](shared/food_catalog.csv)): una fila
  por porción de intercambio con `id`, nombre, grupo, gramos, energía, proteínas, grasas, grasa saturada,
  carbohidratos, índice glicémico, alérgenos (`allergens`, etiquetas separadas por `;`), precio
  referencial en CLP por porción (`price`, a actualizar) y sodio, potasio, fósforo y calcio (mg); celda vacía = dato desconocido,
  se acepta coma decimal. Es la fuente única: `php artisan foods:import` (también `FoodsSeeder`) lo carga en
  la tabla `foods` por `id` y conserva los alimentos que ya no están en el archivo, porque los menús
  guardados los referencian; si una fila está mal no escribe nada y lista cada error con su línea. El
  servicio `expert` lo lee con [expert/app/catalog.py](expert/app/catalog.py) (se recarga si el archivo
  cambia): `GET /foods` lo devuelve con los datos a revisar (nombres repetidos, energía que no calza con
  4/4/9 kcal/g, carbohidratos sin índice glicémico, grupos que el generador no usa) y `POST /meal-plan`
  sin `foods` genera con él. Laravel sigue enviando `foods` desde la tabla, así el editor y el generador
  usan los mismos alimentos.
- **Propuesta de menú de la consulta** ([app/Services/MealPlanService.php](app/Services/MealPlanService.php)):
  la página de resultado muestra la propuesta guardada o, si no hay, una generada (*Otra variante*,
  1/3/7 días). *Revisar y guardar* abre el editor (`/result/{id}/menu`): por día y comida se cambia el
  alimento (cualquiera del catálogo), las porciones (de a media) o se agregan y quitan alimentos, se copian
  o quitan días, viendo en vivo los totales contra las metas y los topes. Al guardar, el servidor recalcula
  todo desde el catálogo y lo guarda en `meal_plans` (una por consulta, marcada si se ajustó a mano). La
  página y el PDF muestran siempre lo guardado, aunque cambien el catálogo o las reglas; *Generar nueva
  propuesta* parte de cero y *Descartar* vuelve a la automática. Solo médicos editan.
  Requiere que la consulta tenga requerimiento energético y gramos de carbohidratos, proteínas y lípidos.
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
- **Estructura:** `main.py` crea la app; los endpoints están en `routers/` (`health.py`, `diagnosis.py`);
  `config.py` lee las variables de entorno (`EXPERT_TOKEN`, `CLINICAL_THRESHOLDS_PATH`, `FOOD_CATALOG_PATH`); `security.py` valida
  el token; `schemas.py` y `responses.py` definen los cuerpos de entrada y de respuesta. La documentación
  OpenAPI queda en `/docs` y `/openapi.json` dentro de la red de Docker.
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
  `anthropometry.weight_kg` y `anthropometry.height_cm`. Para FLI se envía `labs.ggt` (U/L) y para
  FINDRISC el grupo `risk_factors`: `daily_physical_activity`, `daily_fruit_vegetables`,
  `antihypertensive_medication`, `high_glucose_history` (booleanos) y `family_history_diabetes`
  (`none`, `second_degree` o `first_degree`).
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
