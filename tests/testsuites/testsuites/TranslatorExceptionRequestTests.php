<?php

declare(strict_types=1);

namespace testsuites;

use DeeplXML\Translator;
use DeeplXML\Translator_Exception_Request;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Translator_Exception_Request: getDetails() fallback,
 * renderAnalysis() output modes, and Guzzle exception chain accessors.
 *
 * @package DeeplXML
 * @subpackage Tests
 */
final class TranslatorExceptionRequestTests extends TestCase
{
    // region: getDetails()

    /**
     * Before setLanguages() / setXML() are called, getDetails() must
     * fall back to the constructor-provided details string without
     * recursion or fatal error.
     *
     * @see Translator_Exception_Request::getDetails()
     */
    public function test_getDetails_beforeSetup_fallsBackToConstructorDetails() : void
    {
        $ex = new Translator_Exception_Request(
            'Test message',
            'Fallback details',
            Translator::ERROR_TRANSLATION_REQUEST_FAILED
        );

        $this->assertSame('Fallback details', $ex->getDetails());
    }

    /**
     * After setLanguages() and setXML() are called, getDetails() must
     * return the renderAnalysis() output, which is non-empty and contains
     * the source language string.
     *
     * @see Translator_Exception_Request::getDetails()
     */
    public function test_getDetails_afterSetup_returnsAnalysis() : void
    {
        $ex = new Translator_Exception_Request(
            'Test message',
            'Details',
            Translator::ERROR_TRANSLATION_REQUEST_FAILED
        );

        $translator = new Translator('dummy', 'EN', 'DE');
        $ex->setTranslator($translator);
        $ex->setLanguages('EN', 'DE');
        $ex->setXML('<document><deeplstring id="id1">Hello</deeplstring></document>');

        $details = $ex->getDetails();

        $this->assertNotEmpty($details);
        $this->assertStringContainsString('EN', $details);
    }

    // endregion

    // region: renderAnalysis()

    /**
     * renderAnalysis(false) (plain text) must not contain raw <br> tags;
     * renderAnalysis(true) (HTML) must contain <br> tags. The two outputs
     * must differ.
     *
     * @see Translator_Exception_Request::renderAnalysis()
     */
    public function test_renderAnalysis_htmlAndPlainTextDiffer() : void
    {
        $ex = new Translator_Exception_Request(
            'Test',
            '',
            Translator::ERROR_TRANSLATION_REQUEST_FAILED
        );

        $translator = new Translator('dummy', 'EN', 'DE');
        $ex->setTranslator($translator);
        $ex->setLanguages('EN', 'DE');
        $ex->setXML('<document><deeplstring id="id1">Hello</deeplstring></document>');

        $plain = $ex->renderAnalysis(false);
        $html  = $ex->renderAnalysis(true);

        $this->assertNotEquals($plain, $html);
        $this->assertStringContainsString('<br>', $html);
        $this->assertStringNotContainsString('<br>', $plain);
    }

    // endregion

    // region: Guzzle exception accessors

    /**
     * hasGuzzleException() must return false and getGuzzleException() must
     * return null when the exception was not caused by a Guzzle request.
     *
     * @see Translator_Exception_Request::hasGuzzleException()
     * @see Translator_Exception_Request::getGuzzleException()
     */
    public function test_hasGuzzleException_withoutGuzzle() : void
    {
        $ex = new Translator_Exception_Request(
            'Test',
            '',
            Translator::ERROR_TRANSLATION_REQUEST_FAILED
        );

        $this->assertFalse($ex->hasGuzzleException());
        $this->assertNull($ex->getGuzzleException());
    }

    // endregion
}
