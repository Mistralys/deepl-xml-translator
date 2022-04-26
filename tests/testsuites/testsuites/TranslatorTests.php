<?php

declare(strict_types=1);

namespace testsuites;

use DeeplXML\Translator_Exception_Request;
use PHPUnit\Framework\TestCase;
use DeeplXML\Translator;
use const TESTS_DEEPL_APIKEY;
use const TESTS_PROXY_SERVER;

final class TranslatorTests extends TestCase
{
    // region: _Tests

    /**
     * @see Translator::addString()
     */
    public function test_addString() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');

        $translator->addString('id1', 'Translate me');
        $translator->addString('id2', 'Translate me too');

        $this->assertEquals(true, $translator->stringIDExists('id1'));
        $this->assertCount(2, $translator->getStrings());
    }

    public function test_addString_getOriginal() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');

        $translator->addString('id1', 'Translate me');

        $this->assertEquals('Translate me', $translator->getStringByID('id1')->getOriginalText());
    }

    public function test_addString_getID() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');

        $translator->addString('id1', 'Translate me');

        $this->assertEquals('id1', $translator->getStringByID('id1')->getID());
    }

    public function test_getLocales() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');

        $this->assertEquals('EN', $translator->getSourceLanguage());
        $this->assertEquals('DE', $translator->getTargetLanguage());
    }

    public function test_getLocales_caseSensitive() : void
    {
        $translator = new Translator('dummy', 'en', 'de');

        $this->assertEquals('EN', $translator->getSourceLanguage());
        $this->assertEquals('DE', $translator->getTargetLanguage());
    }

    public function test_getConnector() : void
    {
        $translator = new Translator('dummy', 'en', 'de');

        $translator->getConnector();

        $this->addToAssertionCount(1);
    }

    public function test_translate() : void
    {
        $this->skipIfNoAPIKey();

        $translator = new Translator(TESTS_DEEPL_APIKEY, 'EN', 'DE');

        $translator->addString('id1', 'Hello');

        $this->runTranslation($translator);

        $this->assertTrue($translator->isTranslated());

        $string = $translator->getStringByID('id1');

        $this->assertEquals('Hallo', $string->getTranslatedText());

        $this->assertTrue($string->isTranslated());
    }

    public function test_ignoreString() : void
    {
        $this->skipIfNoAPIKey();

        $translator = new Translator(TESTS_DEEPL_APIKEY, 'EN', 'DE');

        $translator->addString('id1', '<p>Hello <b>world</b></p>')
            ->addIgnoreString('world');

        $this->runTranslation($translator);

        $string = $translator->getStringByID('id1');

        $this->assertEquals('<p>Hallo <b>world</b></p>', $string->getTranslatedText());
    }

    public function test_connectWithProxy() : void
    {
        $this->skipIfNoProxy();
        $this->skipIfNoAPIKey();

        $translator = new Translator(TESTS_DEEPL_APIKEY, 'EN', 'DE');
        $translator->setProxy(TESTS_PROXY_SERVER);

        $translator->addString('id1', 'Hello');

        $this->runTranslation($translator);

        $this->assertTrue($translator->isTranslated());
    }

    // endregion

    // region: Support methods

    private function runTranslation(Translator $translator) : void
    {
        try
        {
            $translator->translate();
        }
        catch (Translator_Exception_Request $ex)
        {
            $this->fail(
                'An exception occurred during the request to DeepL. Details:' . PHP_EOL .
                $ex->renderAnalysis()
            );
        }
    }

    /**
     * @return void
     */
    protected function skipIfNoAPIKey() : void
    {
        if (empty(TESTS_DEEPL_APIKEY))
        {
            $this->markTestSkipped('No DeepL API key defined.');
        }
    }

    /**
     * @return void
     */
    protected function skipIfNoProxy() : void
    {
        if (empty(TESTS_PROXY_SERVER))
        {
            $this->markTestSkipped('No proxy server specified.');
        }
    }

    // endregion
}
