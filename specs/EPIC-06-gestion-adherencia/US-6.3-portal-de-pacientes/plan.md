# Plan técnico · US-6.3

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

Los pacientes con acceso al portal son **usuarios** de la tabla `users`, con un rol nuevo
`Role::Patient` (`rol_id = 4`) y una columna `patient_id` que los une a su registro de `patients`. Así
reutilizan lo que ya existe para el equipo: la pantalla de acceso, el bloqueo tras intentos repetidos
(`ThrottlesLogins`), la recuperación de contraseña (broker `users`), las reglas de contraseña y la
duración de la sesión (AC-4, AC-5, NFR-2). Como `users.email` es único, "un correo, una cuenta" (D-4) lo
garantiza la base de datos.

El riesgo de este enfoque es que un paciente pase por las comprobaciones que hoy aceptan "cualquier
usuario con rol". Por eso el aislamiento (AC-6) va primero y en dos capas:

1. Un middleware `staff` en **todas** las rutas del equipo (el grupo `auth` de `routes/web.php`, `/home`
   y la API) que responde 403 a un paciente. Un middleware `patient` protege las rutas `/portal`.
2. Las policies que hoy aceptan `role() !== null` (`PatientPolicy::viewAny`, `DiagnosisPolicy::viewAny`)
   pasan a `isStaff()`, y `POST /api/v1/tokens` no entrega tokens a pacientes. Si una ruta nueva olvida
   el middleware, la policy sigue negando.

La **invitación** es un registro propio (`patient_invitations`) con un token aleatorio guardado como
hash, que vence a los 7 días (D-2). La cuenta se crea recién cuando el paciente acepta: si el enlace no
sirve, no se crea nada (AC-3). El correo de bienvenida lo envía un job con reintentos (`SendPortalInvitation`),
igual que los recordatorios de US-6.1, y la ficha muestra el estado del envío, con la opción de reintentar
(AC-1, AC-8). Los **términos** (D-5) son un archivo Markdown fuera del código; su versión es un hash del
contenido. Sin ese archivo no se puede invitar (AC-10).

**Quitar el acceso** borra la cuenta del paciente (AC-7). En la petición siguiente, el guard de sesión
no encuentra al usuario y lo trata como invitado, así que la sesión abierta se cierra sin depender del
driver de sesiones (hoy `file`). La clave foránea `users.patient_id` con `cascadeOnDelete` borra la
cuenta junto con el paciente.

**Alternativas descartadas:**

- *Guard y tabla separados para pacientes (`patient_accounts`).* Aísla por construcción, pero duplica el
  inicio de sesión, la recuperación de contraseña, el bloqueo por intentos y las reglas de contraseña,
  que la spec pide iguales a las del equipo. Además, la pantalla de acceso tendría que probar dos
  proveedores, y no se podría garantizar con un índice único que un correo tenga una sola cuenta entre
  equipo y pacientes (D-4).
- *Crear la cuenta al invitar y usar el broker de contraseñas como invitación.* El broker comparte la
  tabla de tokens con la recuperación de contraseña (un token pisa al otro) y vence en 60 minutos. Además,
  una cuenta creada al invitar podría activarse con *¿Olvidaste tu contraseña?* sin aceptar los términos.
- *Enviar la invitación dentro de la misma petición.* Con el servidor de correo caído, la ficha esperaría
  hasta el timeout del SMTP. El job deja la petición rápida y concentra los reintentos y el registro del
  fallo en un solo lugar, como en US-6.1.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Base de datos | `…_add_patient_id_to_users_table.php` (TK-6.3.1) y `…_add_terms_to_users_table.php` (TK-6.3.3), nuevas | `users.patient_id` (FK única, `cascadeOnDelete`); `terms_version`, `terms_accepted_at` |
| Base de datos | `…_create_patient_invitations_table.php` (nueva) | Invitaciones y estado del envío |
| Laravel | `app/Enums/Role.php` | `Patient = 4` ("Paciente"); `staffOptions()` para el formulario de usuarios |
| Laravel | `app/Models/User.php` | `patient()`, `isPatient()`, `isStaff()` |
| Laravel | `app/Models/Patient.php` | `account()`, `invitations()`, `latestInvitation()`, `latestMealPlan()` |
| Laravel | `app/Models/PatientInvitation.php` (nuevo) | Estados, `isUsable()`, búsqueda por hash del token |
| Laravel | `app/Http/Middleware/EnsureStaff.php`, `EnsurePatient.php` (nuevos), `bootstrap/app.php` | Alias `staff` y `patient` |
| Laravel | `app/Policies/PatientPolicy.php`, `DiagnosisPolicy.php`, `UserPolicy.php` | `viewAny`: `isStaff()`; `invitePortal`, `revokePortal`: médicos; el Administrador no edita ni borra cuentas de pacientes, y la lista de usuarios solo muestra al equipo |
| Laravel | `app/Http/Controllers/Api/TokenController.php` | Sin token para pacientes (misma respuesta que credenciales malas) |
| Laravel | `app/Http/Controllers/Auth/LoginController.php`, `ResetPasswordController.php`, `HomeController.php` | Destino según el rol: `/portal` para pacientes, `/home` para el equipo |
| Laravel | `app/Support/PortalTerms.php` (nuevo), `config/portal.php` (nuevo) | Ruta del archivo de términos, versión (hash) y HTML |
| Laravel | `app/Services/PortalAccessService.php` (nuevo) | `invite()`, `accept()`, `revoke()` |
| Laravel | `app/Jobs/SendPortalInvitation.php`, `app/Mail/PortalInvitationMail.php` (nuevos) | Envío con reintentos; `failed()` marca la invitación |
| Laravel | `app/Http/Controllers/PortalInvitationController.php`, `PortalAccessController.php`, `PortalController.php` (nuevos) | Aceptar la invitación (invitado), invitar o quitar el acceso (ficha) e inicio del portal |
| Laravel | `app/Events/PatientPortalAccessChanged.php` (nuevo), `app/Listeners/RecordAuditTrail.php` | `portal.invited`, `portal.activated`, `portal.revoked`; `portal.login` desde `Illuminate\Auth\Events\Login` |
| Laravel | `routes/web.php`, `routes/api.php` | Middleware `staff` en las rutas del equipo; grupo `/portal` |
| Vistas | `resources/views/clinical_records/_portal.blade.php` (nueva), `show.blade.php` | Tarjeta *Portal del paciente*: estado, invitar, reintentar, quitar acceso |
| Vistas | `resources/views/portal/*.blade.php`, `resources/views/mail/portal-invitation.blade.php` (nuevas) | Aceptar invitación, inicio del portal y correo de bienvenida |
| Configuración | `.env.example` | `PATIENT_TERMS_PATH` |
| Servicios Python, `shared/` | — | Sin cambios |

## Contratos

**Ficha clínica** (sesión del equipo; `PatientPolicy::invitePortal` y `revokePortal`: Doctor y Doctor Jefe):

| Método y ruta | Nombre | Efecto |
| --- | --- | --- |
| `POST /patient/{patient}/portal/invitation` | `patient.portal.invite` | Crea una invitación (invalida la anterior) y encola el correo. Redirige a la ficha con el estado o con el motivo por el que no se invitó (AC-2, AC-10) |
| `DELETE /patient/{patient}/portal` | `patient.portal.revoke` | Borra la cuenta e invalida las invitaciones pendientes; `422` si no tiene cuenta ni invitación |

Para reintentar un envío fallido se vuelve a invitar (AC-8). La ficha muestra el estado de la última
invitación: *enviándose*, *enviada el …*, *no se pudo enviar*, *aceptada el …* o *vencida*.

**Invitación** (invitado; `throttle:10,1`):

- `GET /portal/invitacion/{token}` → `portal.invitation`: el formulario de contraseña (dos veces) y los
  términos (HTML del archivo) con la casilla *Acepto*. Si el token no sirve (no existe, vencido, usado o
  invalidado), muestra la página "Pida una nueva invitación a su médico", sin datos del paciente.
- `POST` a la misma ruta: `password` (`confirmed`, `Password::min(8)->letters()->numbers()`, igual que
  `PasswordRequest`), `terms` (`accepted`) y `terms_version`. Si la versión de los términos cambió desde
  que se abrió la página, se vuelve a mostrar el formulario. En una transacción: crea el usuario
  (`name` = nombre completo del paciente, `email` = correo de la invitación, `rol_id = 4`, `patient_id`,
  `terms_version`, `terms_accepted_at`), marca la invitación `accepted` e inicia la sesión. Redirige a
  `/portal`.

**Portal** (`auth` + `patient`): `GET /portal` → `portal.home`. Siempre usa el paciente de la sesión
(`auth()->user()->patient`): las rutas del portal no reciben ids, así que no se puede pedir el dato de
otro paciente.

**Acceso:** el login es el mismo. `LoginController::redirectTo()` envía a `/portal` o a `/home` según el
rol, y `ResetPasswordController` hace lo mismo. `/home` redirige a un paciente a `/portal`. Toda otra
ruta del equipo responde `403` a un paciente.

**Correo de bienvenida** (sin datos clínicos, AC-1):

```
Asunto: Su acceso al portal de pacientes

Hola Ana,
su profesional la invitó al portal de pacientes, donde podrá ver su próxima consulta y su plan
alimentario.
[Crear mi contraseña]  (enlace válido hasta el 28/10/2026)
Si no esperaba este correo, puede ignorarlo.
```

**Términos** (`config/portal.php`):

```php
'terms_path' => env('PATIENT_TERMS_PATH', storage_path('app/legal/terminos-portal-pacientes.md')),
```

`PortalTerms::version()` = `sha256:` y los primeros 12 caracteres del hash del contenido. Cualquier
cambio al texto es una versión nueva. Si el archivo no existe o está vacío, `PortalTerms::available()`
es `false` y no se puede invitar (AC-10).

**Auditoría** (canal `audit`, solo ids):

```json
{"action": "portal.activated", "actor_id": 58, "actor_role": "Patient", "patient_id": 12, "terms_version": "sha256:9f2c41d07ab3"}
```

## Modelo de datos

**`users`** (columnas nuevas):

| Columna | Tipo | Nota |
| --- | --- | --- |
| `patient_id` | FK `patients`, `nullable`, `unique`, `cascadeOnDelete` | Solo en cuentas de pacientes |
| `terms_version` | string(32), nullable | Versión aceptada (AC-3) |
| `terms_accepted_at` | datetime, nullable | |

**`patient_invitations`** (nueva):

| Columna | Tipo | Nota |
| --- | --- | --- |
| `id` | bigint PK | |
| `patient_id` | FK `patients`, `cascadeOnDelete` | |
| `invited_by` | FK `users`, `nullOnDelete`, nullable | |
| `email` | string | Correo al que se envió |
| `token_hash` | char(64), `unique` | SHA-256 del token; el token solo viaja en el correo |
| `expires_at` | datetime | Creación + 7 días (D-2) |
| `status` | string(16) | `queued` \| `sent` \| `failed` \| `accepted` \| `revoked` |
| `sent_at`, `failed_at`, `accepted_at` | datetime, nullable | |
| timestamps | | |

Índice `(patient_id, created_at)` para la última invitación. Ambas migraciones son reversibles: `down`
borra la tabla y las columnas, y antes borra las cuentas con `rol_id = 4`, que sin `patient_id` quedarían
huérfanas.

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | La spec se aprueba antes de TK-6.3.1 |
| II. Fuente única de verdad clínica | ✅ | No hay valores clínicos. La vigencia de 7 días es una regla de acceso: va en `config/portal.php` |
| III. Reglas clínicas trazables | ✅ | No aplica |
| IV. Datos incompletos no son negativos | ✅ | Sin cita o sin plan guardado, el portal lo dice; no muestra vacíos como si fueran datos |
| V. Degradación segura | ✅ | El portal no usa los servicios Python. Un servidor de correo caído no impide registrar ni editar al paciente: la invitación queda *no enviada* y se reintenta (AC-8) |
| VI. Los tests son la evidencia | ✅ | Tests PHPUnit por AC (tabla de abajo). NFR-1 se verifica a mano, como dice la spec |
| VII. Privacidad y control de acceso | ✅ | Es el centro de la historia: middleware `staff`/`patient` en cada grupo de rutas, policies con `isStaff()`, API sin tokens para pacientes, portal sin ids en las rutas, token de invitación guardado como hash, correo y páginas públicas sin datos clínicos, auditoría solo con ids. Un test recorre **todas** las rutas GET del equipo con un paciente y espera 403 |
| VIII. Idioma | ✅ | Portal, correo y mensajes en español de Chile; fechas `dd/mm/aaaa` en `clinic_timezone` |
| IX. Simplicidad | ✅ | Sin dependencias ni servicios nuevos (Markdown con `Str::markdown`, ya incluido). Una tabla nueva (`patient_invitations`), justificada arriba frente al broker de contraseñas |

## Estrategia de pruebas

Todo en Laravel (PHPUnit, `RefreshDatabase`). Archivos nuevos: `tests/Feature/Portal/PortalIsolationTest.php`,
`PortalInvitationTest.php`, `PortalLoginTest.php`, `PortalHomeTest.php`, `PortalRevokeTest.php` y
`PortalAuditTest.php`.
`UserFactory` gana el estado `patient(Patient $patient)`.

| Criterio | Capa | Test |
| --- | --- | --- |
| AC-1 | Feature | `PortalInvitationTest::test_doctor_invites_and_the_patient_gets_a_welcome_mail_without_clinical_data` |
| AC-1 | Feature | `PortalInvitationTest::test_clinical_record_shows_the_invitation_state` |
| AC-1 | Feature | `PortalInvitationTest::test_administrator_cannot_invite` |
| AC-2 | Feature | `PortalInvitationTest::test_patient_without_email_or_with_an_email_in_use_is_not_invited` (data provider: sin correo, correo de un médico, correo de otra cuenta de paciente) |
| AC-3 | Feature | `PortalInvitationTest::test_patient_creates_the_password_accepts_the_terms_and_enters` |
| AC-3 | Feature | `PortalInvitationTest::test_without_accepting_the_terms_no_account_is_created` |
| AC-3 | Feature | `PortalInvitationTest::test_expired_used_revoked_or_tampered_link_creates_nothing` (data provider) |
| AC-4 | Feature | `PortalHomeTest::test_portal_shows_name_next_appointment_and_saved_meal_plan` |
| AC-4 | Feature | `PortalHomeTest::test_portal_without_appointment_or_meal_plan_says_so` |
| AC-4 | Feature | `PortalHomeTest::test_portal_shows_no_clinical_data` (consulta, ficha y exámenes cargados: no aparecen IMC, categoría, motivo de consulta ni valores de laboratorio) |
| AC-4 | Feature | `PortalLoginTest::test_patient_logs_in_to_the_portal_and_staff_to_home` |
| AC-4 | Feature | `PortalLoginTest::test_wrong_credentials_give_the_same_message_and_are_throttled` |
| AC-5 | Feature | `PortalLoginTest::test_patient_resets_the_password_like_the_staff` |
| AC-5 | Feature | `PortalLoginTest::test_reset_request_does_not_reveal_whether_the_email_has_an_account` |
| AC-6 | Feature | `PortalIsolationTest::test_patient_gets_403_on_every_staff_route` (recorre `Route::getRoutes()`: toda ruta GET sin parámetros del grupo del equipo, más las rutas con el id del propio paciente y de otro) |
| AC-6 | Feature | `PortalIsolationTest::test_patient_cannot_get_an_api_token` |
| AC-6 | Feature | `PortalIsolationTest::test_staff_cannot_open_the_portal` |
| AC-6 | Feature | `PortalIsolationTest::test_patient_sees_only_their_own_data` (dos pacientes con cuenta) |
| AC-6 | Feature | `PortalIsolationTest::test_user_form_does_not_offer_the_patient_role` |
| AC-7 | Feature | `PortalRevokeTest::test_revoking_ends_the_session_and_blocks_login_until_a_new_invitation` |
| AC-7 | Feature | `PortalRevokeTest::test_deleting_the_patient_deletes_the_account` |
| AC-8 | Feature | `PortalInvitationTest::test_mail_failure_marks_the_invitation_and_inviting_again_retries` |
| AC-8 | Feature | `PortalInvitationTest::test_patient_can_be_edited_while_the_mail_server_is_down` |
| AC-9 | Feature | `PortalAuditTest::test_invite_activate_login_and_revoke_are_audited_without_clinical_data` |
| AC-10 | Feature | `PortalInvitationTest::test_cannot_invite_without_the_terms_text` |
| NFR-2 | Feature | `PortalInvitationTest::test_password_rules_match_the_staff` |

Los tests de términos escriben un archivo temporal y lo apuntan con `config(['portal.terms_path' => …])`.
Los de vencimiento usan `Carbon::setTestNow()`. Los ids se toman de los modelos creados, para que la
suite pase en SQLite y en MySQL.

**Verificación manual (NFR-1), en el PR de TK-6.3.4:** recorrer la invitación, la creación de la
contraseña, el inicio de sesión y el inicio del portal en el modo responsive del navegador a 360 px, sin
desplazamiento horizontal.

## Riesgos y decisiones

- **Rutas nuevas del equipo sin `staff`:** el test que recorre todas las rutas falla si alguien agrega
  una ruta fuera del grupo. Además, las policies niegan a los pacientes.
- **El correo del paciente cambia después de activar la cuenta:** la cuenta conserva el correo con que
  se activó, que es el del inicio de sesión. Cambiarlo en la ficha no cambia la cuenta. La tarjeta del
  portal muestra el correo de la cuenta cuando difiere, y el médico puede quitar el acceso e invitar de
  nuevo. Sincronizarlos queda fuera de esta historia.
- **Términos actualizados después de la activación:** la cuenta guarda la versión aceptada. Pedir que se
  acepte una versión nueva al iniciar sesión no está en la spec; si la clínica lo necesita, es un
  criterio nuevo.
- **El texto de los términos lo entrega la clínica:** hasta entonces no se puede invitar (AC-10), y el
  sistema lo dice en la ficha. El archivo no va en el repositorio. Su ruta se configura por ambiente.
- **Sesiones en archivo:** quitar el acceso borra la cuenta, así que la sesión abierta deja de valer
  en la petición siguiente sin tener que borrar sesiones. El token *recordarme* también se pierde con
  la cuenta.
- **Token de invitación:** se genera con `Str::random(64)` y solo se guarda su SHA-256. Una invitación
  nueva marca las anteriores como `revoked`, así que nunca hay dos enlaces vigentes para un paciente.
