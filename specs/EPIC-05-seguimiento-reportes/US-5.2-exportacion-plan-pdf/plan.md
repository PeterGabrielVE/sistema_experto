# Plan técnico · US-5.2

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

Un PDF nuevo, `Plan para el paciente`, se genera con DomPDF (ya instalado: `barryvdh/laravel-dompdf`, que
usa el informe actual) desde una vista Blade propia y simple. Usa tablas y CSS en línea, porque DomPDF no
soporta flexbox. El informe actual (`download/{diagnosis}`, `result-pdf.blade.php`) no cambia (D-1).

Los datos del documento los arma un servicio, `PatientPlanDocument`, a partir del **plan guardado**
(`meal_plans.plan`), así que es exactamente lo que el médico revisó (D-3) y no cambia aunque cambien el
catálogo o las reglas (AC-5). Tampoco llama a los servicios Python (AC-6). Para cada día lleva:

- el menú: comidas, alimentos, porciones y gramos;
- los totales de energía y macronutrientes del día, que ya trae el plan guardado.

Además lleva:

- las recomendaciones de la categoría (`Recommendation` del `id_rule` de la consulta), sin nombrarla;
- las indicaciones generales, que hoy están escritas en `result-pdf.blade.php` y pasan a una vista parcial
  compartida por los dos PDF, para que haya un solo texto;
- las alergias e intolerancias consideradas, que son el mismo texto de la ficha que se le envía al
  generador (`MealPlanService::allergies()`). El plan guardado no conserva las `restrictions` del
  generador: `rebuild()` no las copia.

La identidad de la clínica (D-5) va en `config/clinic.php`, con variables de entorno: nombre, dirección,
teléfono, correo y ruta del logo. El logo se incrusta como `data:` URI, así DomPDF no necesita acceso a
archivos ni a la red. Si falta, se pone el nombre en su lugar (AC-8), y en el pie solo van los datos que
estén configurados (AC-9).

Lo descargan solo el Doctor y el Doctor Jefe (`viewClinicalRecord` del paciente, D-4). Sin plan guardado,
la página de resultado no ofrece el botón y la ruta responde `404` (AC-3).

**Alternativas descartadas:**

- *Reutilizar `result-pdf.blade.php` con un modo "paciente".* Ese archivo mezcla datos del informe
  (RUT, dirección, categoría) con estilos antiguos, y D-1 pide que el informe no cambie. Una vista propia
  es más corta y su test puede comprobar qué datos **no** aparecen.
- *Snappy (wkhtmltopdf).* Sería un binario más en la imagen Docker. DomPDF ya está y basta para tablas y un
  logo (constitución IX).
- *Generar la propuesta si no hay plan guardado.* D-3 lo descartó.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Laravel | `config/clinic.php` (nuevo), `.env.example` | `CLINIC_NAME`, `CLINIC_ADDRESS`, `CLINIC_PHONE`, `CLINIC_EMAIL`, `CLINIC_LOGO_PATH` (por defecto `public/assets/img/sauce-logo.png`) |
| Laravel | `app/Services/PatientPlanDocument.php` (nuevo) | `data(Diagnosis)`: plan guardado, recomendaciones, alergias, clínica, logo en base64 |
| Laravel | `app/Http/Controllers/MealPlanController.php` | `pdf()`: autoriza, 404 sin plan, `Pdf::loadView()->download()` |
| Laravel | `routes/web.php` | `GET result/{diagnosis}/plan.pdf` → `meal-plan.pdf`, en el grupo de `Diagnosis` |
| Vistas | `resources/views/meal_plans/pdf.blade.php` (nueva) | El documento |
| Vistas | `resources/views/pdf/_general-advice.blade.php` (nueva), `result-pdf.blade.php` | Indicaciones generales compartidas (el informe muestra el mismo texto que hoy) |
| Vistas | `resources/views/diagnoses/result.blade.php` | Botones *Informe (PDF)* y *Plan para el paciente (PDF)*, o el aviso de guardar primero |
| Servicios Python, `shared/` | — | Sin cambios |

## Contratos

`GET /result/{diagnosis}/plan.pdf` → `meal-plan.pdf` (sesión del equipo; `viewClinicalRecord` del paciente):

| Caso | Respuesta |
| --- | --- |
| Doctor o Doctor Jefe, con plan guardado | `200` `application/pdf`, `attachment; filename="plan-alimentario-AAAA-MM-DD.pdf"` (fecha de la consulta; sin el nombre del paciente en el archivo) |
| Sin plan guardado | `404` "La consulta no tiene un plan alimentario guardado." |
| Administrador | `403` |

`PatientPlanDocument::data()` (lo que recibe la vista):

```json
{
  "clinic": {"name": "El Sauce", "address": "Av. Siempre Viva 123", "phone": null, "email": "contacto@elsauce.cl", "logo": "data:image/png;base64,..."},
  "patient_name": "Ana Rojas",
  "date": "21/10/2026",
  "professional": "Dra. Carolina Muñoz",
  "days": [{"day": 1, "totals": {"energy": 1792.4, "carbohydrates": 197, "proteins": 88, "fats": 68},
            "meals": [{"label": "Desayuno", "items": [{"name": "Avena", "portions": 1.5, "grams": 75}]}]}],
  "recommendations": ["Prefiera cereales integrales."],
  "allergies": "Alergia al maní"
}
```

`days[].totals` incluye solo energía y macronutrientes: la carga glucémica y la grasa saturada del plan
guardado se dejan fuera (D-2).

## Modelo de datos

Sin cambios.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | La spec se aprueba antes de TK-5.2.1 |
| II. Fuente única de verdad clínica | ✅ | Sin valores clínicos nuevos. Los totales salen del plan guardado y las recomendaciones de la base de conocimiento |
| III. Reglas clínicas trazables | ✅ | No aplica |
| IV. Datos incompletos no son negativos | ✅ | Sin alergias registradas, el PDF dice "Sin alergias ni intolerancias registradas", no "ninguna". Sin recomendaciones, se omite la sección. Sin logo o sin contacto, se omiten esos datos (AC-8, AC-9) |
| V. Degradación segura | ✅ | No depende de los servicios Python (AC-6). Solo el plan revisado por el médico (D-3) |
| VI. Los tests son la evidencia | ✅ | PHPUnit por AC (tabla de abajo) |
| VII. Privacidad y control de acceso | ✅ | `viewClinicalRecord` (Doctor y Doctor Jefe) con test 403. El documento no lleva RUT, dirección, IMC, categoría ni datos de la ficha, salvo las alergias (AC-2), con un test que lo comprueba. El nombre del archivo no lleva el nombre del paciente |
| VIII. Idioma | ✅ | Español de Chile, coma decimal, fecha `dd/mm/aaaa` |
| IX. Simplicidad | ✅ | Sin dependencias nuevas (DomPDF ya está). Sin tablas nuevas |

## Estrategia de pruebas

Archivo nuevo: `tests/Feature/PatientPlanPdfTest.php`. El contenido se prueba sobre el HTML de la vista
(`view('meal_plans.pdf', $data)->render()`), que es lo que DomPDF convierte. La ruta se prueba por su
respuesta (tipo, nombre de archivo, códigos).

| Criterio | Capa | Test |
| --- | --- | --- |
| AC-1 | Feature | `test_document_has_the_clinic_patient_professional_and_menu` |
| AC-1 | Feature | `test_doctor_downloads_the_plan_as_a_pdf_file` (`application/pdf`, nombre del archivo) |
| AC-1 | Feature | `test_result_page_offers_the_report_and_the_patient_plan` |
| AC-2 | Feature | `test_document_has_daily_energy_recommendations_advice_and_allergies` |
| AC-2 | Feature | `test_document_has_no_rut_address_bmi_category_nor_clinical_record` |
| AC-3 | Feature | `test_without_a_saved_plan_there_is_no_patient_pdf` (página sin el botón y con el aviso; ruta `404`) |
| AC-4 | Feature | `test_each_day_starts_on_a_new_page` (`page-break-before` desde el día 2) |
| AC-5 | Feature | `test_document_shows_the_plan_as_saved` (alimento retirado y renombrado después de guardar) |
| AC-6 | Feature | `test_pdf_does_not_call_the_python_services` (`Http::fake` que falla; `Http::assertNothingSent`) |
| AC-7 | Feature | `test_administrator_gets_403` |
| AC-8 | Feature | `test_without_logo_the_clinic_name_is_shown` |
| AC-9 | Feature | `test_footer_shows_only_the_configured_contact_data` |

Además, `MealPlanTest::test_pdf_view_lists_the_days` (existente) sigue cubriendo el informe, que no cambia.

## Riesgos y decisiones

- **Planes guardados antes de US-5.2:** todos traen `days[].totals`, porque `rebuild()` siempre los calculó.
  Si un día no los trajera, la línea de energía se omite en vez de mostrar 0.
- **Alergias:** el texto se toma de la ficha **al descargar**, no al guardar el plan. Si la ficha cambió
  después, el PDF muestra el texto actual. Guardar la copia con el plan sería un cambio a US-3.1; se
  acepta para esta historia porque el texto es una advertencia de seguridad y debe ser el vigente.
- **Tamaño del logo:** `sauce-logo.png` se incrusta en cada PDF. Si es grande, se reduce la imagen una
  vez en `public/assets/img`; no hace falta una dependencia para redimensionar.
- **Datos de la clínica sin configurar:** solo aparece el nombre, que por defecto es `El Sauce`. Hay que
  completar las variables en el `.env` del servidor (va en la descripción del PR de TK-5.2.2).
