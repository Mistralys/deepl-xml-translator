# Public API Surface

All classes are in the `DeeplXML` namespace.

---

## `Translator`

**File:** `src/Translator.php`
**Description:** Main entry point. Accepts a batch of strings, sends them to DeepL as a single XML document, and distributes the translated results back to the individual `Translator_String` instances.

### Constants

```php
public const ERROR_STRING_ID_ALREADY_EXISTS         = 37601;
public const ERROR_NO_STRINGS_TO_TRANSLATE          = 37602;
public const ERROR_FAILED_CONVERTING_TEXT           = 37603;
public const ERROR_MISSING_ID_ATTRIBUTE_IN_RESPONSE = 37604;
public const ERROR_RESPONSE_STRING_DOES_NOT_EXIST   = 37605;
public const ERROR_STRING_NOT_FOUND_IN_RESULT       = 37506;
public const ERROR_CANNOT_GET_UNKNOWN_STRING        = 37507;
public const ERROR_TRANSLATION_REQUEST_FAILED       = 37508;
public const ERROR_UNSUPPORTED_TRANSLATION_RESULT   = 37509;
public const ERROR_EMPTY_XML_DOCUMENT               = 37510;
public const ERROR_TRANSLATION_RESULT_EMPTY         = 37511;
public const ERROR_DEPRECATED_TARGET_LANGUAGE       = 37512;  // thrown when $targetLang is EN or PT

public const SPLITTING_TAG = 'deeplstring';  // XML tag wrapping each string sent to DeepL
public const IGNORE_TAG    = 'deeplignore';  // XML tag instructing DeepL to skip contents

// Maps deprecated target language codes to their accepted regional replacements.
// @see Translator::ERROR_DEPRECATED_TARGET_LANGUAGE
public const DEPRECATED_TARGET_LANGUAGES = [
    'EN' => ['EN-GB', 'EN-US'],
    'PT' => ['PT-BR', 'PT-PT'],
];
```

### Constructor

```php
public function __construct(string $apiKey, string $sourceLang, string $targetLang)
```

`$sourceLang` and `$targetLang` are normalised to uppercase (e.g. `'en'` becomes `'EN'`).

**Throws `Translator_Exception` (`ERROR_DEPRECATED_TARGET_LANGUAGE`)** if `$targetLang` is a deprecated
code. DeepL rejects `EN` and `PT` as target languages; use a regional variant instead:
- `EN` → `EN-GB` or `EN-US`
- `PT` → `PT-BR` or `PT-PT`

Both codes are valid as *source* languages.

### Public Methods

```php
// Configuration
public function setTimeOut(float $timeOut) : self
public function getTimeOut() : float
public function enableRequestDebug(bool $enable) : self
public function setSimulation(bool $enabled = true) : Translator
public function setProxy(string $proxyURI) : Translator   // URI: tcp://user:pass@1.2.3.4:10

// Language
public function getSourceLanguage() : string
public function getTargetLanguage() : string

// String management
public function addString(string $id, string $text) : Translator_String   // throws Translator_Exception
public function stringIDExists(string $id) : bool
public function getStringByID(string $id) : Translator_String              // throws Translator_Exception
public function getStrings() : Translator_String[]

// Translation
public function translate() : void            // throws Translator_Exception, Translator_Exception_Request
public function isTranslated() : bool

// Diagnostics / advanced
public function getXML() : string             // returns the XML that would be / was sent to DeepL
public function getConnector() : \DeepL\Translator
```

---

## `Translator_String`

**File:** `src/Translator/String.php`
**Description:** Represents a single string in the translation batch. Holds the original text, any ignore-strings, and the translated result once available.

### Constructor

```php
public function __construct(Translator $translator, string $id, string $original)
```

Instantiated exclusively by `Translator::addString()` — callers do not construct these directly.

### Public Methods

```php
public function getID() : string
public function getOriginalText() : string
public function getPreparedText() : string          // original with ignore-strings wrapped in <deeplignore> tags

public function addIgnoreString(string $string) : Translator_String  // fluent; deduplicates entries

public function setTranslatedText(string $text) : void  // called by Translator after parsing response
public function getTranslatedText() : string            // auto-triggers translate() if not yet done; throws Translator_Exception, Translator_Exception_Request

public function isTranslated() : bool                  // delegates to parent Translator::isTranslated()
public function translate() : void                     // delegates to parent Translator::translate(); throws Translator_Exception, Translator_Exception_Request
```

---

## `Translator_Exception`

**File:** `src/Translator/Exception.php`
**Description:** Base exception for all library errors. Extends `\AppUtils\BaseException`, which provides structured `$message`, `$details`, and `$code` fields.

```php
class Translator_Exception extends \AppUtils\BaseException {}
```

No additional public members beyond those inherited from `\AppUtils\BaseException`.

---

## `Translator_Exception_Request`

**File:** `src/Translator/Exception/Request.php`
**Description:** Specialised exception thrown when the HTTP request to DeepL fails or returns an unexpected result. Extends `Translator_Exception`. Carries diagnostic context: the `Translator` instance, the request languages, and the submitted XML.

### Public Methods

```php
public function setTranslator(Translator $translator) : void
public function setLanguages(string $sourceLang, string $targetLang) : void
public function setXML(string $xml) : void

public function getTranslator() : Translator
public function getSourceLanguage() : string
public function getTargetLanguage() : string
public function getXML() : string

// Overrides BaseException::getDetails() to return full renderAnalysis() output when context is available
public function getDetails() : string

public function hasGuzzleException() : bool
public function getGuzzleException() : ?\GuzzleHttp\Exception\ClientException
public function getGuzzleRequest() : ?\Psr\Http\Message\RequestInterface
public function getGuzzleResponse() : ?\Psr\Http\Message\ResponseInterface

// Returns a human-readable diagnostic string (plain text by default, HTML if $html=true)
public function renderAnalysis(bool $html = false) : string
```
