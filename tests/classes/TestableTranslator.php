<?php

declare(strict_types=1);

namespace testsuites;

use DeeplXML\Translator;

/**
 * Exposes protected internals of Translator for offline testing.
 *
 * @package DeeplXML
 * @subpackage Tests
 */
class TestableTranslator extends Translator
{
    /**
     * Marks the translator as already translated so that subsequent
     * calls to translate() return immediately without hitting the API.
     */
    public function markTranslated() : void
    {
        $this->translated = true;
    }

    /**
     * Calls the protected parseXMLResult() method with a fixture XML
     * string, allowing offline tests of all response-parsing branches.
     */
    public function callParseXMLResult(string $xml) : void
    {
        $this->parseXMLResult($xml);
    }
}
