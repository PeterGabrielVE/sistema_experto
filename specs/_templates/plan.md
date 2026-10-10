# Plan técnico · US-0.0

Spec: [spec.md](spec.md). Este documento dice **cómo**; si contradice la spec, manda la spec.

## Enfoque

Resumen de la solución en uno o dos párrafos y por qué se eligió frente a la alternativa principal.

## Componentes afectados

| Capa | Archivos | Cambio |
| --- | --- | --- |
| Laravel | `app/...` | |
| Servicio `expert` | `expert/app/...` | |
| Servicio `inference` | `inference/app/...` | |
| Datos compartidos | `shared/...` | |
| Base de datos | `database/migrations/...` | |
| Vistas / front-end | `resources/...` | |

## Contratos

Endpoints, payloads, esquemas o eventos nuevos o modificados (con ejemplo JSON).

## Modelo de datos

Tablas y columnas nuevas, índices y migración de datos existentes (y su reversa).

## Verificación de la constitución

| Principio | Cumple | Nota |
| --- | --- | --- |
| I. Spec antes que código | ✅ | |
| II. Fuente única de verdad clínica | ✅ | Valores nuevos en `shared/clinical_thresholds.json` |
| III. Reglas clínicas trazables | ✅ | |
| IV. Datos incompletos no son negativos | ✅ | |
| V. Degradación segura | ✅ | Qué pasa si el servicio no responde |
| VI. Los tests son la evidencia | ✅ | |
| VII. Privacidad y control de acceso | ✅ | Roles y policy |
| VIII. Idioma | ✅ | |
| IX. Simplicidad | ✅ | Dependencias nuevas: ninguna |

## Estrategia de pruebas

Qué test verifica cada criterio y en qué capa (unitario Python, feature Laravel, contrato).

| Criterio | Test |
| --- | --- |
| AC-1 | `tests/Feature/...::test_...` |

## Riesgos y decisiones

- Riesgo, impacto y mitigación. Decisiones que valga la pena recordar (ADR breve).
