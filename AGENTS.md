# AGENTS.md — DeepL XML Translator

> Authoritative operating guide for AI agents working in this codebase.
> Read this file completely before touching any source file.

---

## 1. Project Manifest — Start Here!

**Manifest location:** `docs/agents/project-manifest/`

This is the canonical source of truth for the project. Consult it before reading any implementation code.

| Document | Description |
|---|---|
| [`README.md`](docs/agents/project-manifest/README.md) | Project overview, package identity, and manifest index. |
| [`tech-stack.md`](docs/agents/project-manifest/tech-stack.md) | Runtime, language, frameworks, libraries, architectural patterns, and Composer scripts. |
| [`file-tree.md`](docs/agents/project-manifest/file-tree.md) | Annotated directory structure of the entire project. |
| [`api-surface.md`](docs/agents/project-manifest/api-surface.md) | Every public constructor, constant, property, and method signature for all classes. |
| [`data-flows.md`](docs/agents/project-manifest/data-flows.md) | Key interaction paths — from caller through `Translator` to the DeepL HTTP API and back. |
| [`constraints.md`](docs/agents/project-manifest/constraints.md) | Established rules, naming conventions, error-handling protocol, and non-obvious gotchas. |

### Quick Start Workflow

Execute these steps in order before writing, editing, or reviewing any code:

1. Read `README.md` — understand what the library does and its package identity.
2. Read `tech-stack.md` — internalise the language version, architectural patterns, and available Composer scripts.
3. Read `file-tree.md` — form a complete mental map of the directory layout.
4. Read `constraints.md` — absorb all rules and gotchas before forming any plan.
5. Read `api-surface.md` — reference method signatures and constants as needed.
6. Read `data-flows.md` — trace the execution path relevant to your task.
7. Only then open source files under `src/` or `tests/`.

---

## 2. Manifest Maintenance Rules

When you make a code change, you must update every manifest document listed in the corresponding row.

| Change Made | Documents to Update |
|---|---|
| New class added to `src/` | `api-surface.md`, `file-tree.md` |
| Class renamed or moved | `api-surface.md`, `file-tree.md` |
| Public method or constant added / removed / renamed | `api-surface.md` |
| New error code constant added to `Translator` | `api-surface.md`, `constraints.md` |
| New or removed Composer dependency | `tech-stack.md` |
| Composer script added, removed, or changed | `tech-stack.md` |
| Directory or file restructured | `file-tree.md` |
| New exception type introduced | `api-surface.md`, `constraints.md`, `data-flows.md` |
| Translation flow or XML protocol changed | `data-flows.md`, `constraints.md` |
| Simulation mode behaviour changed | `constraints.md`, `data-flows.md` |
| PHP minimum version changed | `tech-stack.md`, `constraints.md` |
| PHPStan level or config changed | `tech-stack.md`, `constraints.md` |
| Test structure or bootstrap changed | `file-tree.md`, `constraints.md` |
| New architectural pattern introduced | `tech-stack.md` |

---

## 3. Efficiency Rules — Search Smart

Unnecessary filesystem scans waste tokens and slow down every task. Apply this lookup order strictly:

- **Finding a file's location?** Check `file-tree.md` FIRST.
- **Looking up a method signature, constant, or constructor?** Check `api-surface.md` FIRST.
- **Understanding a dependency, pattern, or build script?** Check `tech-stack.md` FIRST.
- **Tracing how data moves through the system?** Check `data-flows.md` FIRST.
- **Unsure whether something is allowed?** Check `constraints.md` FIRST.
- **Only after the manifest cannot answer the question** open a source file under `src/` or `tests/`.

---

## 4. Failure Protocol & Decision Matrix

| Scenario | Action | Priority |
|---|---|---|
| Requirement is ambiguous | Use the most restrictive interpretation; flag ambiguity in your response. | MUST |
| Manifest contradicts source code | Trust the manifest; flag the source code as the defect. Do not silently accept the code as correct. | MUST |
| Manifest document is missing or incomplete | Flag the gap explicitly; do not invent facts to fill it. | MUST |
| Code path has no test coverage | Proceed with caution; add a recommendation to write a test. | SHOULD |
| New exception needed | Extend `Translator_Exception`; assign a numeric `ERROR_` constant on `Translator`; update `api-surface.md` and `constraints.md`. | MUST |
| `setProxy()` called after `translate()` | This is valid — it resets the cached `DeeplClient`. Document the call order in any code you write. | MUST |
| `setTimeOut()` called after first `translate()` or `getConnector()` | The timeout will NOT apply to the already-initialised client. Warn the caller. | MUST |
| Simulation mode used | Verify `session_start()` has been called by the host application; simulation mode depends on `$_SESSION`. | MUST |
| Adding a dependency | Check that it does not conflict with `deeplcom/deepl-php` or `guzzlehttp/guzzle`. | MUST |
| Test needs a live API key | Use `tests/apikey.dist.php` as a template; never commit `tests/apikey.php`. | MUST |
| Running static analysis | Use `composer analyze`; code in `src/` must pass PHPStan level 6. | MUST |

---

## 5. Project Stats

| Property | Value |
|---|---|
| Language | PHP 8.4+ (`declare(strict_types=1)` everywhere) |
| Package | `mistralys/deepl-xml-translator` |
| Architecture | Single-namespace library (`DeeplXML`); facade over `deeplcom/deepl-php` |
| Package manager | Composer (classmap autoloading from `src/`) |
| Test framework | PHPUnit 12 — suite name `DeeplXML Translator Tests` |
| Static analysis | PHPStan 2.1+, level 6, `src/` only |
| Build / task scripts | `composer analyze`, `analyze-save`, `analyze-clear`, `test`, `test-file`, `test-suite`, `test-filter`, `test-group` |
| Key external API | DeepL API (XML translation mode, single batched `<document>` per request) |
| Licence | GPL-3.0-or-later |

---

## 6. Composer Scripts

All scripts are invoked as `composer <script-name>` from the project root.

### Static Analysis

| Command | Description |
|---|---|
| `composer analyze` | Run PHPStan against `src/` using `phpstan.neon` (memory limit 900 MB). Must pass at level 6 before any PR. |
| `composer analyze-save` | Same analysis, but redirect the full output to `phpstan-result.txt`. Never fails the shell (exit 0 always), so it is safe to use in non-blocking pipelines. |
| `composer analyze-clear` | Clear the PHPStan result cache. Run this when switching branches or after large refactors to avoid stale cache hits. |

### Tests

| Command | When to use |
|---|---|
| `composer test` | Run the entire test suite with default output. Use this for a quick full-suite pass. |
| `composer test-file -- <path>` | Run a single test file without progress output. Pass the file path after `--`. |
| `composer test-suite -- <name>` | Run one named test suite (defined in `phpunit.xml`). The only suite in this project is `DeeplXML Translator Tests`. |
| `composer test-filter -- <pattern>` | Run only tests whose names match `<pattern>` (substring or regex). Useful for targeting a single test method. |
| `composer test-group -- <group>` | Run only tests tagged with a specific `@group` annotation. |

**Examples**

```bash
# Full suite
composer test

# Single file
composer test-file -- tests/Translator/TranslatorTest.php

# Named suite
composer test-suite -- "DeeplXML Translator Tests"

# Single test method
composer test-filter -- testTranslateSimpleString

# Group
composer test-group -- deepl-api
```
