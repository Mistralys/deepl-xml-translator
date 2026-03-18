# Constraints & Conventions

---

## Language & Typing

- All source files use `declare(strict_types=1)`.
- PHP minimum version is **8.4**. Type declarations must be compatible.
- PHPStan is run at **level 6**. All source code in `src/` must pass at that level.
- `tests/` and `vendor/` are explicitly excluded from PHPStan analysis (`phpstan.neon`).

---

## Namespace & Autoloading

- All library classes belong to the `DeeplXML` namespace.
- Autoloading uses Composer's `classmap` strategy targeting `src/`. There is no PSR-4 mapping. File names must match class names exactly (PHP classmap convention: `Translator_String` lives in `src/Translator/String.php`).

---

## Error Handling

- All exceptions thrown by the library extend `Translator_Exception`, which itself extends `\AppUtils\BaseException`.
- `Translator_Exception` is used for logic/validation errors (duplicate ID, unknown ID, empty string list, malformed XML response).
- `Translator_Exception_Request` is used exclusively for errors arising from the HTTP call to DeepL.
- Every exception must be thrown with a numeric error code. The error code constants are defined on `Translator` (prefixed `ERROR_`).
- When catching and re-throwing, the original exception is passed as the `$previous` argument to preserve the chain.

---

## XML Protocol

- Strings are batched into a single XML document with a `<document>` root element.
- Each string is wrapped in a `<deeplstring id="...">` element (constant `Translator::SPLITTING_TAG`).
- Sub-strings to ignore are wrapped in `<deeplignore>` elements (constant `Translator::IGNORE_TAG`) before sending, and stripped out of the response after.
- DeepL tag handling is always configured as `xml`; `split_sentences` is always `nonewlines`; `preserve_formatting` is always `on`.
- HTML detection: `AppUtils\ConvertHelper::isStringHTML()` determines whether a string's content must be inserted as an XML fragment (HTML) or a plain text node.

---

## DeepL Client Lifecycle

- `DeepL\Translator` instances are cached statically in `Translator::$deepl` keyed by API key, so multiple `Translator` objects with the same API key share one client.
- Calling `setProxy()` resets (nullifies) the cached client for that API key, forcing re-initialisation on the next request. This is intentional — proxy config must be set before the first `translate()` call, or `setProxy()` must be called again.
- Changing `setTimeOut()` after the client has been initialised does **not** reset the client; set the timeout before the first call to `translate()` or `getConnector()`.

---

## Simulation Mode

- When simulation mode is active (`setSimulation(true)`), translated results are stored in and retrieved from `$_SESSION`, keyed by `'deepl-' . md5($sourceXml)`.
- This requires an active PHP session (`session_start()` must have been called by the host application).
- Simulation mode is intended for development only, to reduce API calls.

---

## Response Validation

- After parsing the DeepL response, the library verifies that **every** string originally sent is present in the response. A missing string causes a `Translator_Exception` with code `ERROR_STRING_NOT_FOUND_IN_RESULT`.
- DeepL may silently omit untranslatable or empty strings; this library treats omissions as errors.

---

## Testing

- Tests are located under `tests/testsuites/testsuites/` — note the double nesting.
- Tests that require a live DeepL API key are skipped automatically when `tests/apikey.php` is absent or `TESTS_DEEPL_APIKEY` is empty.
- Tests that require a proxy are skipped when `TESTS_PROXY_SERVER` is empty.
- `tests/apikey.php` must not be committed. `tests/apikey.dist.php` is the safe template.
- The test bootstrap (`tests/bootstrap.php`) defines the `TESTS_ROOT` constant and conditionally loads `apikey.php`.

---

## Dependencies

- The DeepL HTTP client is `deeplcom/deepl-php` (the official DeepL library), sourced from Packagist. It replaced the former `scn/deepl-api-connector` fork (removed in v2.1.0) because the upstream SCN package was unmaintained and its authentication layer had changed.
- `minimum-stability` is `dev` with `prefer-stable: true` to accommodate the `dev-latest` constraint on `roave/security-advisories`.
