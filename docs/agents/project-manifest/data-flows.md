# Key Data Flows

---

## 1. Standard Translation Flow

The primary use case: add one or more strings, call `translate()`, read results.

```
Caller
  │
  ├─ new Translator($apiKey, $sourceLang, $targetLang)
  │
  ├─ $translator->addString($id, $text)
  │     └─ new Translator_String($translator, $id, $text)
  │        stored in Translator::$strings[$id]
  │
  ├─ (optional) $string->addIgnoreString($substring)
  │     └─ stored in Translator_String::$ignore[]
  │
  └─ $translator->translate()
        │
        ├─ Translator::initDeepL()
        │     └─ new DeepL\Translator($apiKey, ['http_client' => new GuzzleHttp\Client($options)])
        │        result cached in static Translator::$deepl[$apiKey]
        │
        ├─ Translator::renderXML()
        │     ├─ foreach $strings: Translator_String::getPreparedText()
        │     │     └─ wraps $ignore[] entries in <deeplignore> tags
        │     ├─ detects HTML vs plain text via AppUtils\ConvertHelper::isStringHTML()
        │     ├─ builds <document><deeplstring id="...">...</deeplstring>...</document>
        │     └─ returns XML string
        │
        ├─ $options = [tag_handling=xml, preserve_formatting=true,
        │              split_sentences=nonewlines, ignore_tags=[deeplignore]]
        │
        ├─ (if simulation mode) check $_SESSION[md5($xml)] for cached result
        │
        ├─ DeepL\Translator::translateText($xml, $sourceLang, $targetLang, $options)  <── HTTP POST to DeepL API
        │     returns DeepL\TextResult; $result->text contains translated XML
        │
        ├─ (if simulation mode) store result in $_SESSION[md5($xml)]
        │
        ├─ Translator::parseXMLResult($responseXml)
        │     ├─ DOMDocument::loadXML($responseXml)
        │     ├─ foreach <deeplstring> nodes: extract id attribute and child node text
        │     ├─ Translator_String::setTranslatedText($text)
        │     │     ├─ strips <deeplignore> wrapper tags
        │     │     └─ html_entity_decode()
        │     └─ validates all sent strings are present in the response
        │
        └─ Translator::$translated = true
```

After `translate()` completes, the caller reads results:

```
$string = $translator->getStringByID($id)
$text   = $string->getTranslatedText()    // returns Translator_String::$translated
```

Or iterates all strings:

```
foreach ($translator->getStrings() as $string) {
    $text = $string->getTranslatedText();
}
```

---

## 2. Auto-translate via String Instance

A string instance can trigger translation lazily:

```
$string->getTranslatedText()
  └─ calls $string->translate()
       └─ delegates to $this->translator->translate()
            └─ (same flow as section 1 above)
```

---

## 3. Error Flow — Failed HTTP Request

When `DeepL\Translator::translateText()` throws any `Exception`:

```
DeepL\Translator::translateText()
  └─ throws Exception (e.g. DeepL\DeepLException or a subclass such as
                        DeepL\AuthorizationException, DeepL\ConnectionException,
                        the latter optionally wrapping GuzzleHttp\Exception\ClientException)
       │
       └─ caught in Translator::translate()
            └─ new Translator_Exception_Request(...)
                 ├─ setTranslator($this)
                 ├─ setXML($sourceXml)
                 ├─ setLanguages($sourceLang, $targetLang)
                 └─ thrown to caller

Caller catches Translator_Exception_Request:
  ├─ $ex->renderAnalysis()           — full diagnostic report (plain text or HTML)
  ├─ $ex->getGuzzleException()       — underlying Guzzle ClientException, if any
  ├─ $ex->getGuzzleResponse()        — HTTP response, if any
  └─ $ex->getDetails()               — same as renderAnalysis() output (auto-computed)
```

---

## 4. Proxy Flow

When a proxy is configured before translation:

```
$translator->setProxy($proxyURI)
  ├─ stores $this->proxy
  └─ resets cached DeeplClient for this API key (Translator::reset())

$translator->translate()
  └─ Translator::initDeepL()
       └─ new GuzzleHttp\Client([
              RequestOptions::PROXY           => $proxyURI,
              RequestOptions::CONNECT_TIMEOUT => $timeOut,
              RequestOptions::DEBUG           => $requestDebugging,
          ])
          (all subsequent DeepL requests route through the proxy)
```

---

## 5. Advanced: Direct Connector Access

For operations not supported by this library (e.g., glossary management, usage queries):

```
$api = $translator->getConnector()   // returns DeepL\Translator
// caller uses $api directly per the deeplcom/deepl-php documentation
```
