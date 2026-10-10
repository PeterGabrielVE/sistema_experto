# Spec-driven development

En este proyecto **la spec es la fuente de verdad del comportamiento**. Antes de escribir código
se acuerda qué debe hacer el sistema y cómo se va a verificar; el código y los tests se escriben
contra esos criterios, y el CI comprueba que spec, tareas y tests sigan alineados.

Las reglas de fondo están en la [constitución](constitution.md).

## Estructura

```
specs/
├── constitution.md                    principios no negociables
├── _templates/                        spec.md, plan.md, tasks.md para copiar
└── EPIC-03-plan-alimentario/
    └── US-3.1-propuesta-de-menu/
        ├── spec.md                    qué y por qué: historia y criterios de aceptación (AC)
        ├── plan.md                    cómo: diseño, contratos, datos, pruebas, constitución
        └── tasks.md                   tareas TK-3.1.n, cada una un PR
```

Los ids siguen el backlog: épica `EPIC-03`, historia `US-3.1` (3 = épica), tarea `TK-3.1.2`,
criterio `US-3.1/AC-4`. Los ids **no se renumeran ni se reutilizan**: un criterio que deja de
aplicar se borra y su número queda retirado.

## Ciclo de una historia

```
draft ──► approved ──► in-progress ──► implemented ──► (deprecated)
 spec      + plan        PRs feat         todas las
           + tasks       por tarea        tareas [x]
```

1. **Especificar** (`draft`). Copiar `_templates/spec.md` a `specs/EPIC-xx-.../US-x.y-slug/spec.md`.
   Escribir el contexto, la historia y criterios *dado / cuando / entonces* verificables, sin
   decidir la implementación. PR: `docs(US-x.y): spec ...`.
2. **Planificar y aprobar** (`approved`). Agregar `plan.md` (cómo, contratos, verificación de la
   constitución, qué test prueba cada AC) y `tasks.md` (tareas que entre todas cubren cada AC).
   Sin preguntas abiertas pendientes. Aprueba el **Doctor Jefe** (criterios clínicos) y un
   desarrollador (plan). Puede ir en el mismo PR que la spec.
3. **Implementar** (`in-progress`). Un PR por tarea: `feat(TK-x.y.n): ...`. El PR implementa,
   etiqueta los tests con los AC que cubre y marca la tarea `[x]`. Si al implementar cambia lo que
   se quiere, **primero se cambia la spec** en el mismo PR y se explica en la descripción.
4. **Cerrar** (`implemented`). Con todas las tareas `[x]` y cada AC verificado por un test
   (o marcado `[manual]` con su procedimiento), el último PR pasa la spec a `implemented`.
5. **Retirar** (`deprecated`). Si el comportamiento se elimina o lo reemplaza otra historia, se
   indica cuál en la spec. Los tests que la referencian se borran o se reetiquetan.

Los cambios de comportamiento de una historia ya `implemented` se hacen igual: se edita la spec
(criterio nuevo o modificado), se vuelve a `in-progress` si hay tareas nuevas y se implementa.
Los `fix` que restauran lo que la spec ya dice no requieren cambiarla.

## Trazabilidad: tests ↔ criterios

Cada test declara qué criterio verifica.

```php
use PHPUnit\Framework\Attributes\Group;

#[Group('US-3.1/AC-7')]
public function test_a_budget_out_of_range_is_ignored(): void
```

```python
@pytest.mark.spec("US-3.1/AC-6", "US-3.1/AC-7")
def test_allergies_and_budget_together(priced): ...

pytestmark = pytest.mark.spec("US-3.1/AC-6")  # todo el módulo
```

En PHP el atributo también puede ir sobre la clase (todos sus tests). Un test puede cubrir
varios criterios y un criterio puede tener varios tests.

## Comandos

```bash
python scripts/spec_check.py                 # valida specs, tareas y etiquetas (lo mismo que el CI)
python scripts/spec_check.py list            # índice: estado, AC con test, tareas hechas
python scripts/spec_check.py tests US-3.1    # tests de cada AC y cómo ejecutarlos
python scripts/spec_check.py pr "feat(US-3.1): ..."   # valida un título de PR

php artisan test --group=US-3.1/AC-7         # tests PHP de un criterio
cd expert && python -m pytest tests --spec US-3.1   # tests Python de una spec (o --spec US-3.1/AC-6)
```

## Qué verifica el CI

| Check | Regla |
| --- | --- |
| **Specs · Trazabilidad** (`ci.yml`) | Encabezado (`id`, `title`, `epic`, `status`) y secciones obligatorias; ids y carpetas coherentes; AC y tareas sin repetir; desde `approved` existen `plan.md` y `tasks.md` y cada AC está cubierto por alguna tarea; en `implemented`, todas las tareas `[x]` y cada AC con test (o `[manual]`); ningún test referencia un criterio o spec inexistente. |
| **PR · Título y spec** (`pr.yml`) | Título en Conventional Commits. Un `feat` debe nombrar una `US-x.y` o `TK-x.y.n` que exista y cuya spec no esté en `draft` (la tarea debe estar en `tasks.md`). `fix`, `docs`, `test`, `refactor`, `chore`, `ci`, `build`, `perf`, `style`, `revert` y `deps` no requieren spec. |

En `in-progress`, un AC sin test es un aviso; en `implemented`, un error.

## Con Claude Code

Los comandos de [.claude/commands/](../.claude/commands/) siguen este proceso:

- `/spec <descripción>`: redacta la spec en `draft`, con preguntas abiertas en vez de suposiciones.
- `/plan US-x.y`: escribe `plan.md` y `tasks.md` para una spec, con la verificación de la constitución.
- `/implement TK-x.y.n`: implementa una tarea con sus tests etiquetados y valida con `spec_check`.

## Specs existentes

La historia US-3.1 se especificó después de implementarse (retro-spec) y sirve como referencia del
formato. Las historias anteriores (EPIC-01 usuarios, el motor experto) se especifican cuando
se modifiquen: no hace falta escribir specs de lo que no se va a tocar.
