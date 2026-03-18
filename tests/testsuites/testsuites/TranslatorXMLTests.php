<?php

declare(strict_types=1);

namespace testsuites;

use DeeplXML\Translator;
use DeeplXML\Translator_Exception;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests for renderXML() (via getXML()) and all offline-testable branches
 * of parseXMLResult() (exposed through TestableTranslator).
 *
 * @package DeeplXML
 * @subpackage Tests
 */
final class TranslatorXMLTests extends TestCase
{
    // region: renderXML() / getXML()

    /**
     * getXML() must produce a document with one <deeplstring> element per
     * added string, each carrying the correct id attribute.
     *
     * @see Translator::getXML()
     */
    public function test_getXML_containsCorrectElements() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');
        $translator->addString('id1', 'Hello world');
        $translator->addString('id2', '<b>HTML content</b>');

        $xml = $translator->getXML();

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $nodes = $dom->getElementsByTagName(Translator::SPLITTING_TAG);

        $this->assertCount(2, $nodes);
        $this->assertSame('id1', $nodes->item(0)->getAttribute('id'));
        $this->assertSame('id2', $nodes->item(1)->getAttribute('id'));
    }

    // endregion

    // region: parseXMLResult() — error branches

    /**
     * When the response XML is missing one of the originally submitted IDs,
     * parseXMLResult() must throw ERROR_STRING_NOT_FOUND_IN_RESULT.
     *
     * @see Translator::ERROR_STRING_NOT_FOUND_IN_RESULT
     */
    public function test_parseXMLResult_missingStringInResult() : void
    {
        $translator = new TestableTranslator('dummy', 'EN', 'DE');
        $translator->addString('id1', 'Hello');
        $translator->addString('id2', 'World');

        // Response only contains id1 — id2 is absent.
        $responseXml = '<?xml version="1.0"?>'
            . '<document>'
            . '<deeplstring id="id1">Hallo</deeplstring>'
            . '</document>';

        $this->expectException(Translator_Exception::class);
        $this->expectExceptionCode(Translator::ERROR_STRING_NOT_FOUND_IN_RESULT);

        $translator->callParseXMLResult($responseXml);
    }

    /**
     * When a response element is missing its id attribute,
     * parseXMLResult() must throw ERROR_MISSING_ID_ATTRIBUTE_IN_RESPONSE.
     *
     * @see Translator::ERROR_MISSING_ID_ATTRIBUTE_IN_RESPONSE
     */
    public function test_parseXMLResult_elementMissingIdAttribute() : void
    {
        $translator = new TestableTranslator('dummy', 'EN', 'DE');
        $translator->addString('id1', 'Hello');

        // The deeplstring element has no id attribute.
        $responseXml = '<?xml version="1.0"?>'
            . '<document>'
            . '<deeplstring>Hallo</deeplstring>'
            . '</document>';

        $this->expectException(Translator_Exception::class);
        $this->expectExceptionCode(Translator::ERROR_MISSING_ID_ATTRIBUTE_IN_RESPONSE);

        $translator->callParseXMLResult($responseXml);
    }

    /**
     * When the response XML contains an ID that was never submitted,
     * parseXMLResult() must throw ERROR_RESPONSE_STRING_DOES_NOT_EXIST.
     *
     * @see Translator::ERROR_RESPONSE_STRING_DOES_NOT_EXIST
     */
    public function test_parseXMLResult_unknownIdInResponse() : void
    {
        $translator = new TestableTranslator('dummy', 'EN', 'DE');
        $translator->addString('id1', 'Hello');

        // Response contains a rogue ID that was never submitted.
        $responseXml = '<?xml version="1.0"?>'
            . '<document>'
            . '<deeplstring id="id999">Hallo</deeplstring>'
            . '</document>';

        $this->expectException(Translator_Exception::class);
        $this->expectExceptionCode(Translator::ERROR_RESPONSE_STRING_DOES_NOT_EXIST);

        $translator->callParseXMLResult($responseXml);
    }

    // endregion
}
