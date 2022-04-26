## v2.0.0 - Dependency update release

- New PHP version requirement: v7.4+
- Upgraded to `scn/deepl-api-connector` v3.x branch.
- Switched to the new `guzzlehttp/guzzle` package.
- Fixed missing response when creating a translation exception.

## v1.0.5 - Maintenance release

- Modified `scn/deepl-api-connector` dependency to use the v2.1.2 release, which contains the needed patches that we used dev-master for before.

## v1.0.4 - Maintenance release

- Cosmetic change only: Fixed the `composer.json` description text of the package.

## v1.0.3 - Code quality release

- Better information in the exception thrown when the DeepL request fails.
- Specialized exception `Translator_Exception_Request` for request failures.
- Code quality review thanks to PHPStan.

## v1.0.2 - Dependency update release

- Tied to `mistralys/application-utils` v1.3.0.
- Removed a deprecated method call.

## v1.0.1 - Added proxy support

- Added the `setProxy()` method to connect via a proxy.

## v1.0.0 - Initial release