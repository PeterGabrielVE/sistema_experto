#!/usr/bin/env python3
"""Spec-driven development checks (specs/README.md).

    python scripts/spec_check.py                  validate specs/ and the test tags
    python scripts/spec_check.py list             spec index with status and coverage
    python scripts/spec_check.py tests US-3.1     tests that verify a spec, and how to run them
    python scripts/spec_check.py pr "<title>"     a feat PR must name an approved spec

Standard library only (runs on Python 3.9+, locally and in CI).
"""

from __future__ import annotations

import re
import sys
from dataclasses import dataclass, field
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SPECS = ROOT / "specs"
TEST_DIRS = [ROOT / "tests", ROOT / "expert" / "tests", ROOT / "inference" / "tests"]

STATUSES = ["draft", "approved", "in-progress", "implemented", "deprecated"]
# From "approved" on, the spec needs a plan and tasks that cover every criterion.
PLANNED = {"approved", "in-progress", "implemented"}
REQUIRED_FIELDS = ["id", "title", "epic", "status"]
REQUIRED_SECTIONS = [
    "Contexto",
    "Historia de usuario",
    "Criterios de aceptación",
    "Fuera de alcance",
    "Preguntas abiertas",
]

SPEC_ID = re.compile(r"^US-\d+\.\d+$")
CRITERION = re.compile(r"^\s*[-*]\s+\*\*(AC-\d+)\*\*(.*)$")
TASK = re.compile(r"^\s*[-*]\s+\[([ xX])\]\s+\*\*(TK-[\d.]+)\*\*(.*)$")
# PHPUnit: #[Group('US-3.1/AC-2')]   pytest: @pytest.mark.spec("US-3.1/AC-2", ...) or pytestmark = ...
PHP_TAG = re.compile(r"#\[Group\(\s*'(US-[\d.]+(?:/AC-\d+)?)'\s*\)\]")
PY_TAG = re.compile(r"mark\.spec\(([^)]*)\)")
PY_ARG = re.compile(r"[\"'](US-[\d.]+(?:/AC-\d+)?)[\"']")
TEST_SYMBOL = re.compile(r"^\s*(?:public\s+function|def|async\s+def|class)\s+(\w+)")

# Conventional Commit types that do not change behaviour and need no spec.
FREE_TYPES = {"fix", "chore", "docs", "test", "refactor", "ci", "build", "perf", "style", "revert", "deps"}


@dataclass
class Spec:
    path: Path
    meta: dict
    criteria: dict  # AC-n -> text
    tasks: dict = field(default_factory=dict)  # TK-x -> (done, text)
    has_plan: bool = False

    @property
    def id(self) -> str:
        return self.meta.get("id", "")

    @property
    def status(self) -> str:
        return self.meta.get("status", "")


@dataclass
class Tag:
    ref: str  # US-3.1 or US-3.1/AC-2
    file: Path
    symbol: str  # test name, class name or "" for the whole module


def rel(path: Path) -> str:
    return path.relative_to(ROOT).as_posix()


def front_matter(text: str) -> dict:
    match = re.match(r"^---\n(.*?)\n---\n", text.replace("\r\n", "\n"), re.S)
    if not match:
        return {}
    meta = {}
    for line in match.group(1).splitlines():
        key, sep, value = line.partition(":")
        if sep:
            meta[key.strip()] = value.split("#", 1)[0].strip().strip("\"'")
    return meta


def sections(text: str) -> set:
    return {m.group(1).strip() for m in re.finditer(r"^##\s+(.+)$", text, re.M)}


def load_specs(errors: list) -> list:
    specs = []
    for spec_file in sorted(SPECS.glob("EPIC-*/US-*/spec.md")):
        text = spec_file.read_text(encoding="utf-8")
        meta = front_matter(text)
        criteria = {}
        ac = None
        for line in text.splitlines():
            match = CRITERION.match(line)
            if match:
                ac = match.group(1)
                if ac in criteria:
                    errors.append(f"{rel(spec_file)}: {ac} está repetido")
                criteria[ac] = match.group(2).strip(" —-")
            elif ac and line.startswith((" ", "\t")) and line.strip():
                criteria[ac] += " " + line.strip()  # indented continuation
            else:
                ac = None
        spec = Spec(spec_file, meta, criteria, has_plan=(spec_file.parent / "plan.md").exists())

        tasks_file = spec_file.parent / "tasks.md"
        if tasks_file.exists():
            task = None
            for number, line in enumerate(tasks_file.read_text(encoding="utf-8").splitlines(), 1):
                match = TASK.match(line)
                if match:
                    task = match.group(2)
                    if task in spec.tasks:
                        errors.append(f"{rel(tasks_file)}:{number}: {task} está repetida")
                    spec.tasks[task] = (match.group(1) != " ", match.group(3))
                elif task and line.startswith((" ", "\t")) and line.strip():
                    # Indented continuation of the previous task.
                    done, text = spec.tasks[task]
                    spec.tasks[task] = (done, f"{text} {line.strip()}")
                else:
                    task = None
        specs.append(spec)
    return specs


def load_tags() -> list:
    tags = []
    for directory in TEST_DIRS:
        for test_file in sorted(list(directory.rglob("*Test.php")) + list(directory.rglob("test_*.py"))):
            pending = []
            for line in test_file.read_text(encoding="utf-8").splitlines():
                found = PHP_TAG.findall(line)
                for call in PY_TAG.findall(line):
                    found += PY_ARG.findall(call)
                if found and line.lstrip().startswith("pytestmark"):
                    tags += [Tag(ref, test_file, "") for ref in found]
                    continue
                pending += found
                symbol = TEST_SYMBOL.match(line)
                if symbol and pending:
                    tags += [Tag(ref, test_file, symbol.group(1)) for ref in pending]
                    pending = []
    return tags


def validate(specs: list, tags: list) -> tuple:
    errors, warnings = [], []
    by_id = {}
    for spec in specs:
        where = rel(spec.path)
        for key in REQUIRED_FIELDS:
            if not spec.meta.get(key):
                errors.append(f"{where}: falta '{key}' en el encabezado")
        if spec.id and not SPEC_ID.match(spec.id):
            errors.append(f"{where}: id '{spec.id}' no tiene la forma US-<épica>.<n>")
        if spec.id and not spec.path.parent.name.startswith(spec.id + "-"):
            errors.append(f"{where}: la carpeta debe empezar por '{spec.id}-'")
        epic = spec.meta.get("epic", "")
        if epic and not spec.path.parent.parent.name.startswith(epic + "-"):
            errors.append(f"{where}: la épica '{epic}' no coincide con la carpeta {spec.path.parent.parent.name}")
        if spec.status and spec.status not in STATUSES:
            errors.append(f"{where}: estado '{spec.status}' inválido ({', '.join(STATUSES)})")
        if spec.id in by_id:
            errors.append(f"{where}: el id {spec.id} ya existe en {rel(by_id[spec.id].path)}")
        by_id[spec.id] = spec

        missing = [s for s in REQUIRED_SECTIONS if s not in sections(spec.path.read_text(encoding="utf-8"))]
        if missing:
            errors.append(f"{where}: faltan secciones: {', '.join(missing)}")
        if not spec.criteria:
            errors.append(f"{where}: no tiene criterios de aceptación (- **AC-1** ...)")

        number = spec.id[3:]
        for task in spec.tasks:
            if not task.startswith(f"TK-{number}."):
                errors.append(f"{where}: la tarea {task} no pertenece a {spec.id} (TK-{number}.n)")

        if spec.status in PLANNED:
            if not spec.has_plan:
                errors.append(f"{where}: con estado '{spec.status}' se requiere plan.md")
            if not spec.tasks:
                errors.append(f"{where}: con estado '{spec.status}' se requiere tasks.md con tareas")
            planned = " ".join(text for _, text in spec.tasks.values())
            for ac in spec.criteria:
                if not re.search(rf"\b{ac}\b", planned):
                    errors.append(f"{where}: ninguna tarea cubre {spec.id}/{ac}")

        covered = {t.ref for t in tags}
        for ac, text in spec.criteria.items():
            manual = "[manual]" in text
            tested = f"{spec.id}/{ac}" in covered
            if spec.status == "implemented" and not tested and not manual:
                errors.append(f"{where}: {spec.id}/{ac} está implementado pero ningún test lo verifica")
            elif spec.status == "in-progress" and not tested and not manual:
                warnings.append(f"{where}: {spec.id}/{ac} aún sin test")
        if spec.status == "implemented":
            pending = [task for task, (done, _) in spec.tasks.items() if not done]
            if pending:
                errors.append(f"{where}: implementada con tareas pendientes: {', '.join(pending)}")

    for tag in tags:
        spec_id, _, ac = tag.ref.partition("/")
        spec = by_id.get(spec_id)
        where = f"{rel(tag.file)} ({tag.symbol or 'módulo'})"
        if spec is None:
            errors.append(f"{where}: {tag.ref} no corresponde a ninguna spec")
        elif ac and ac not in spec.criteria:
            errors.append(f"{where}: {tag.ref} no existe en {rel(spec.path)}")
        elif spec.status == "deprecated":
            warnings.append(f"{where}: {tag.ref} pertenece a una spec obsoleta")
    return errors, warnings


def coverage(spec: Spec, tags: list) -> str:
    covered = {t.ref.partition("/")[2] for t in tags if t.ref.startswith(spec.id + "/")}
    return f"{len(covered & set(spec.criteria))}/{len(spec.criteria)}"


def cmd_check(specs, tags, load_errors: list) -> int:
    errors, warnings = validate(specs, tags)
    errors = load_errors + errors
    for message in warnings:
        print(f"aviso: {message}")
    for message in errors:
        print(f"error: {message}")
    print(f"{len(specs)} specs, {len(tags)} referencias en tests: {len(errors)} errores, {len(warnings)} avisos")
    return 1 if errors else 0


def cmd_list(specs, tags) -> int:
    print(f"{'ID':<8} {'Estado':<12} {'AC con test':<12} {'Tareas':<8} Título")
    for spec in specs:
        done = sum(1 for d, _ in spec.tasks.values() if d)
        print(f"{spec.id:<8} {spec.status:<12} {coverage(spec, tags):<12} {done}/{len(spec.tasks):<6} {spec.meta.get('title', '')}")
    return 0


def cmd_tests(specs, tags, spec_id: str) -> int:
    spec = next((s for s in specs if s.id == spec_id), None)
    if spec is None:
        print(f"No existe la spec {spec_id}")
        return 1
    print(f"{spec.id} — {spec.meta.get('title', '')} ({spec.status})\n")
    for ac, text in spec.criteria.items():
        print(f"{ac}: {text}")
        found = [t for t in tags if t.ref in (f"{spec.id}/{ac}", spec.id)]
        for tag in found:
            print(f"    {rel(tag.file)}::{tag.symbol or '*'}")
        if not found:
            print("    (sin tests)" + (" — verificación manual" if "[manual]" in text else ""))
    groups = sorted({t.ref for t in tags if t.ref.startswith(spec.id) and t.file.suffix == ".php"})
    if groups:
        # PHPUnit 12 takes one group per --group option.
        print()
        print("PHP:    php artisan test " + " ".join(f"--group={g}" for g in groups))
    for service in ("expert", "inference"):
        if any(t.ref.startswith(spec.id) and t.file.suffix == ".py" and service in t.file.parts for t in tags):
            print(f"Python: cd {service} && python -m pytest tests --spec {spec.id}")
    return 0


def cmd_pr(specs, title: str) -> int:
    match = re.match(r"^(\w+)(?:\(([^)]*)\))?!?:\s*\S", title)
    if not match:
        print(f"El título debe seguir Conventional Commits: 'feat(US-3.1): ...' o 'fix: ...'. Recibido: {title!r}")
        return 1
    kind = match.group(1).lower()
    if kind in FREE_TYPES:
        print(f"'{kind}' no requiere spec.")
        return 0
    if kind != "feat":
        print(f"Tipo '{kind}' desconocido. Use feat o uno de: {', '.join(sorted(FREE_TYPES))}")
        return 1
    ids = re.findall(r"\b(US-\d+\.\d+|TK-\d+\.\d+\.\d+)\b", title)
    if not ids:
        print("Un PR 'feat' debe nombrar su historia o tarea: 'feat(US-3.1): ...' o 'feat(TK-3.1.2): ...'")
        return 1
    by_id = {s.id: s for s in specs}
    for ref in ids:
        spec_id = ref if ref.startswith("US-") else "US-" + ".".join(ref[3:].split(".")[:2])
        spec = by_id.get(spec_id)
        if spec is None:
            print(f"{ref}: no existe specs/EPIC-*/{spec_id}-*/spec.md. Escriba la spec primero (specs/README.md).")
            return 1
        if spec.status == "draft":
            print(f"{ref}: la spec {spec_id} está en borrador; debe aprobarse antes de implementar.")
            return 1
        if ref.startswith("TK-") and ref not in spec.tasks:
            print(f"{ref}: no está en {rel(spec.path.parent / 'tasks.md')}")
            return 1
    print(f"OK: {', '.join(ids)}")
    return 0


def main(argv: list) -> int:
    # Specs contain characters such as ≥ that the Windows console code page cannot print.
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    errors: list = []
    specs = load_specs(errors)
    tags = load_tags()
    command = argv[1] if len(argv) > 1 else "check"
    if command == "check":
        return cmd_check(specs, tags, errors)
    if command == "list":
        return cmd_list(specs, tags)
    if command == "tests" and len(argv) == 3:
        return cmd_tests(specs, tags, argv[2])
    if command == "pr" and len(argv) == 3:
        return cmd_pr(specs, argv[2])
    print(__doc__)
    return 2


if __name__ == "__main__":
    sys.exit(main(sys.argv))
