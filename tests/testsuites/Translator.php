<?php

use PHPUnit\Framework\TestCase;

use DeeplXML\Translator;

final class TranslatorTest extends TestCase
{
    /**
     * @see Translator::addString()
     */
    public function test_addString()
    {
        $translator = new Translator('dummy', 'EN', 'DE');
        
        $translator->addString('id1', 'Translate me');
        $translator->addString('id2', 'Translate me too');
        
        $this->assertEquals(true, $translator->stringIDExists('id1'));
        $this->assertEquals(2, count($translator->getStrings()));
    }
  
    public function test_addString_getOriginal()
    {
        $translator = new Translator('dummy', 'EN', 'DE');
        
        $translator->addString('id1', 'Translate me');
        
        $this->assertEquals('Translate me', $translator->getStringByID('id1')->getOriginalText());
    }

    public function test_addString_getID()
    {
        $translator = new Translator('dummy', 'EN', 'DE');
        
        $translator->addString('id1', 'Translate me');
        
        $this->assertEquals('id1', $translator->getStringByID('id1')->getID());
    }
    
    public function test_getLocales()
    {
        $translator = new Translator('dummy', 'EN', 'DE');
        
        $this->assertEquals('EN', $translator->getSourceLanguage());
        $this->assertEquals('DE', $translator->getTargetLanguage());
    }

    public function test_getLocales_caseSensitive()
    {
        $translator = new Translator('dummy', 'en', 'de');
        
        $this->assertEquals('EN', $translator->getSourceLanguage());
        $this->assertEquals('DE', $translator->getTargetLanguage());
    }
    
    public function test_getConnector()
    {
        $translator = new Translator('dummy', 'en', 'de');
        
        $connector = $translator->getConnector();
        
        $this->assertInstanceOf('\Scn\DeeplApiConnector\DeeplClient', $connector);
    }
    
    public function test_translate()
    {
        if(empty(TESTS_DEEPL_APIKEY)) {
            $this->markTestSkipped('No DeepL API key defined.');
            return;
        }
        
        $translator = new Translator(TESTS_DEEPL_APIKEY, 'EN', 'DE');
        
        $translator->addString('id1', 'Hello');

        $translator->translate();
        
        $this->assertTrue($translator->isTranslated());
        
        $string = $translator->getStringByID('id1');
        
        $this->assertEquals('Hallo', $string->getTranslatedText());
        
        $this->assertTrue($string->isTranslated());
    }
    
    public function test_ignoreString()
    {
        if(empty(TESTS_DEEPL_APIKEY)) {
            $this->markTestSkipped('No DeepL API key defined.');
            return;
        }
        
        $translator = new Translator(TESTS_DEEPL_APIKEY, 'EN', 'DE');
        
        $translator->addString('id1', '<p>Hello <b>world</b></p>')
        ->addIgnoreString('world');
        
        $translator->translate();
        
        $string = $translator->getStringByID('id1');
        
        $this->assertEquals('<p>Hallo <b>world</b></p>', $string->getTranslatedText());
    }
}
    