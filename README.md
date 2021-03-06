[![Build Status](https://travis-ci.com/Mistralys/deepl-xml-translator.svg?branch=master)](https://travis-ci.com/Mistralys/deepl-xml-translator)

# DeepL XML Translator

This is a drop-in PHP helper class that can translate a bundle of strings,
which may contain markup. It uses [DeepL](https://www.deepl.com)'s 
[XML translation feature](https://www.deepl.com/docs-api.html?part=xml), 
and is designed to be very easy to use.

## Installation

Simply require the package in your project via Composer:

```json
"require": {
    "mistralys/deepl-xml-translator" : "dev-master"
}
```

See the packagist page: https://packagist.org/packages/mistralys/deepl-xml-translator

## Usage

### Create a new instance of the helper

```php
$translator = new \DeeplXML\Translator(
    'YourAPIKey', // the DeepL API key to use to connect
    'EN', // source language
    'DE' // target language
); 
```

### Add strings to translate

```php
$translator->addString('string1', 'Please translate me');
$translator->addString('string2', 'Please also translate me');
```

### Fetch the translation

```php
try
{
    $translator->translate();
}
catch(\DeeplXML\Translator_Exception $e)
{
    // handle errors
}    
```

### Access the translated strings

Retrieving all strings at once:

```php
$strings = $translator->getStrings();

 foreach($strings as $string)
 {
     $text = $string->getTranslatedText();
 }
```

Retrieving specific strings by ID:

```php
$string = $translator->getStringByID('string2');
$text = $string->getTranslatedText();
```

### Ignoring parts of strings

To avoid parts of strings being translated, the characters to ignore can be
specified as needed. They are replaced by placeholders by a simple search & replace
before sending the translation to DeepL, and restored again afterwards. 

```php
$string = $translator->addString('string1', 'Please ignore me');

// the text "ignore" will not be translated in the string
$string->addIgnoreString('ignore');

```

## DeepL API connection

The translator class uses the [SC-Networks/deepl-api-connector](https://github.com/SC-Networks/deepl-api-connector) DeepL API package to connect to the service in the background.

If you need more advanced features, like translating files and the like, you may use the fully configured connector instance directly:

```php
$api = $translator->getConnector();

// do something with it
```

## Running the tests

By default, the unit tests will only test the offline API of the Translator itself. To enable live testing with the DeepL API, rename the file `tests/apikey.dist.php` to `tests/apikey.php` and edit it to insert your API key. The additional tests will be enabled automatically.
