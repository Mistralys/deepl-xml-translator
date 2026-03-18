<?php

declare(strict_types=1);

namespace testsuites;

use DeeplXML\Translator;
use DeeplXML\Translator_Exception;
use PHPUnit\Framework\TestCase;

/**
 * Offline (no API key required) tests for Translator guard conditions
 * and configuration methods.
 *
 * @package DeeplXML
 * @subpackage Tests
 */
final class TranslatorOfflineTests extends TestCase
{
    // region: addString() guards

    /**
     * Adding a second string with the same ID must throw with
     * ERROR_STRING_ID_ALREADY_EXISTS.
     *
     * @see Translator::addString()
     * @see Translator::ERROR_STRING_ID_ALREADY_EXISTS
     */
    public function test_addString_duplicateIDThrows() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');
        $translator->addString('id1', 'Hello');

        $this->expectException(Translator_Exception::class);
        $this->expectExceptionCode(Translator::ERROR_STRING_ID_ALREADY_EXISTS);

        $translator->addString('id1', 'World');
    }

    // endregion

    // region: translate() guards

    /**
     * Calling translate() with no strings added must throw with
     * ERROR_NO_STRINGS_TO_TRANSLATE.
     *
     * @see Translator::translate()
     * @see Translator::ERROR_NO_STRINGS_TO_TRANSLATE
     */
    public function test_translate_noStringsThrows() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');

        $this->expectException(Translator_Exception::class);
        $this->expectExceptionCode(Translator::ERROR_NO_STRINGS_TO_TRANSLATE);

        $translator->translate();
    }

    /**
     * Calling translate() a second time must be a no-op: the $translated
     * flag short-circuits before any API call is attempted.
     *
     * @see Translator::translate()
     */
    public function test_translate_idempotent() : void
    {
        $translator = new TestableTranslator('dummy', 'EN', 'DE');
        $translator->addString('id1', 'Hello');
        $translator->markTranslated();

        // Must not throw even though 'dummy' is not a real API key.
        $translator->translate();

        $this->assertTrue($translator->isTranslated());
    }

    // endregion

    // region: getStringByID() guard

    /**
     * Requesting a string by an ID that was never added must throw with
     * ERROR_CANNOT_GET_UNKNOWN_STRING.
     *
     * @see Translator::getStringByID()
     * @see Translator::ERROR_CANNOT_GET_UNKNOWN_STRING
     */
    public function test_getStringByID_unknownThrows() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');

        $this->expectException(Translator_Exception::class);
        $this->expectExceptionCode(Translator::ERROR_CANNOT_GET_UNKNOWN_STRING);

        $translator->getStringByID('does-not-exist');
    }

    // endregion

    // region: Timeout configuration

    /**
     * setTimeOut() must persist and be returned by getTimeOut().
     *
     * @see Translator::setTimeOut()
     * @see Translator::getTimeOut()
     */
    public function test_setTimeout_persists() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');
        $translator->setTimeOut(30.0);

        $this->assertSame(30.0, $translator->getTimeOut());
    }

    // endregion

    // region: Collection state

    /**
     * stringIDExists() must return false for an ID that was never added.
     *
     * @see Translator::stringIDExists()
     */
    public function test_stringIDExists_returnsFalseForUnknown() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');

        $this->assertFalse($translator->stringIDExists('never-added'));
    }

    /**
     * getStrings() must return an empty array before any strings are added.
     *
     * @see Translator::getStrings()
     */
    public function test_getStrings_emptyInitially() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');

        $this->assertSame([], $translator->getStrings());
    }

    /**
     * isTranslated() must return false before translate() has been called.
     *
     * @see Translator::isTranslated()
     */
    public function test_isTranslated_falseInitially() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');

        $this->assertFalse($translator->isTranslated());
    }

    // endregion
}
