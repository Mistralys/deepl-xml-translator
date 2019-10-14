# DeepL XML Translator

This is a drop-in helper class that can translate a bundle of strings,
which may contain markup. It uses DeepL's XML translation feature, and
is designed to be very easy to use.

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

```php
$strings = $translator->getStrings();

 foreach($strings as $string)
 {
     $text = $string->getTranslatedText();
 }
```
