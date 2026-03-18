# DeepL XML Translator Changelog

## v2.0.3 - Improved exception diagnostics
- Exception: `getDetails()` now returns the full diagnostic analysis, visible in any standard exception display.
- Exception: Fixed the no-Guzzle-exception branch to show a specific, actionable message instead of a generic one.
- Composer: Updated minimum versions for `mistralys/application-utils` and `guzzlehttp/guzzle`.

### v2.0.2 - Bugfix release
- Fixed a PHP error via a temporary fork of the `scn/deepl-api-connector` package.
- Added error checks when Deepl returns an empty result.

### v2.0.1 - Minor feature release 

- Added `setTimeOut()` and `getTimeOut()`.
- Added `enableRequestDebug()` to debug connection issues.
- Now using Guzzle constants for options.
- Bumped Guzzle version to fix a vulnerability.

### v2.0.0 - Dependency update release, PHP 7.4

- New PHP version requirement: v7.4+
- Upgraded to `scn/deepl-api-connector` v3.x branch.
- Switched to the new `guzzlehttp/guzzle` package.
- Fixed missing response when creating a translation exception.

### v1.0.5 - Maintenance release

- Modified `scn/deepl-api-connector` dependency to use the v2.1.2 release, which contains the needed patches that we used dev-master for before.

### v1.0.4 - Maintenance release

- Cosmetic change only: Fixed the `composer.json` description text of the package.

### v1.0.3 - Code quality release

- Better information in the exception thrown when the DeepL request fails.
- Specialized exception `Translator_Exception_Request` for request failures.
- Code quality review thanks to PHPStan.

### v1.0.2 - Dependency update release

- Tied to `mistralys/application-utils` v1.3.0.
- Removed a deprecated method call.

### v1.0.1 - Added proxy support

- Added the `setProxy()` method to connect via a proxy.

### v1.0.0 - Initial release
