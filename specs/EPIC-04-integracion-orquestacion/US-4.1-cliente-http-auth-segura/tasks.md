# Tareas · US-4.1

Spec: [spec.md](spec.md) · Plan: [plan.md](plan.md)

Una tarea es un cambio que se puede revisar y mergear solo (un PR `feat(TK-4.1.n): ...`).
Cada tarea nombra los criterios que cubre; entre todas cubren todos los AC de la spec.
Conservan los ids del backlog: TK-4.1.1 es el cliente de Laravel y TK-4.1.2 la autenticación de los
servicios. Son independientes y pueden ir en cualquier orden.

- [x] **TK-4.1.1** — Etiquetar los tests existentes del cliente HTTP de Laravel (credencial, degradación,
  servicio no configurado); agregar los tests de degradación ante `401` y `503`, y registrar los errores
  `422` del servicio `expert` solo con el campo y el tipo de error, sin valores clínicos. Cubre AC-1, AC-4,
  AC-5, AC-6.
- [ ] **TK-4.1.2** — Hacer que `expert` e `inference` respondan `503` sin token configurado salvo con
  `APP_ENV=local`, que `/health` informe `auth_configured`, pasar `APP_ENV` a `expert` en `compose.yaml` y
  documentarlo; etiquetar los tests de token existentes y agregar los de AC-3, con la verificación manual de
  AC-7 en el PR. Cubre AC-2, AC-3, AC-7.
