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

        try
        {
            $translator->translate();
        }
        catch(Exception $e)
        {
            $this->fail('Exception: '.\AppUtils\ConvertHelper::throwable2info($e)->toString());
            return;
        }
        
        $this->assertTrue($translator->isTranslated());
        
        $string = $translator->getStringByID('id1');
        
        $this->assertEquals('Hallo', $string->getTranslatedText());
    }
}
    