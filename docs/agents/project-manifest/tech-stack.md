# Tech Stack & Patterns

## Runtime & Language

- **Language:** PHP
- **Minimum version:** PHP 8.4 (as of `composer.json` `"php": "^8.4"`)
- **Strict types:** `declare(strict_types=1)` is used in all source files.

## Package Manager

- **Composer** (autoloading via `classmap` pointing at `src/`)
- `composer.phar` is committed to the repository for self-contained usage.

## Production Dependencies

| Package | Version constraint | Purpose |
|---|---|---|
| `mistralys/application-utils` | `>=1.3.0` | Utilities: `ConvertHelper`, `Highlighter`, `XMLHelper`, `BaseException`, `parseVariable()`, `parseThrowable()`. |
| `scn/deepl-api-connector` | `dev-master` (via VCS fork at `github.com/Mistralys/deepl-api-connector.git`) | Low-level DeepL HTTP client (`DeeplClient`, `DeeplClientFactory`, `TranslationConfig`, `Translation`, enums). |
| `guzzlehttp/guzzle` | `^7.4.2` | HTTP client used internally by `scn/deepl-api-connector`; also configured directly for timeout, proxy, and debug options. |
| `ext-dom` | `*` | DOM extension used to parse DeepL's XML response. |
| `ext-openssl` | `*` | TLS support for HTTPS calls to the DeepL API. |

## Development Dependencies

| Package | Version constraint | Purpose |
|---|---|---|
| `phpunit/phpunit` | `^12.0` | Unit and integration test runner. |
| `phpstan/phpstan` | `>=2.1` | Static analysis, level 6. |
| `phpstan/phpstan-phpunit` | `^2.0` | PHPStan extension for PHPUnit stubs. |
| `roave/security-advisories` | `dev-latest` | Blocks installation of packages with known security advisories. |

## Architectural Patterns

- **Single-namespace library:** All classes live in the `DeeplXML` namespace.
- **Facade over third-party client:** `Translator` acts as a high-level facade over `scn/deepl-api-connector`, hiding its configuration complexity.
- **XML envelope pattern:** Multiple strings are batched into a single XML document (`<document>` root, `<deeplstring id="...">` children) and sent as one request to DeepL's XML translation mode, preserving HTML markup within each string.
- **Ignore-tag injection:** Sub-strings that must not be translated are wrapped in `<deeplignore>` tags before the request and stripped back out after.
- **Shared static connector pool:** `DeeplClient` instances are cached in a `static` array keyed by API key (`Translator::$deepl`), so multiple `Translator` instances sharing the same key reuse one HTTP client.
- **Simulation / session cache:** An optional simulation mode caches translated results in `$_SESSION` keyed by `md5` of the source XML, to avoid redundant API calls during development.

## Build & Analysis Scripts (Composer)

| Script | Command |
|---|---|
| `analyze` | PHPStan analysis at level 6 with 900 MB memory limit. |
| `analyze-save` | Same, redirected to `phpstan-result.txt`. |
| `analyze-clear` | Clear PHPStan result cache. |
| `test` | Run PHPUnit. |
| `test-file` | PHPUnit without progress output. |
| `test-suite` | PHPUnit filtered to a named test suite. |
| `test-filter` | PHPUnit filtered to a named test method. |
| `test-group` | PHPUnit filtered to a named group. |
