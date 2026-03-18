<?php

declare(strict_types=1);

namespace testsuites;

use DeeplXML\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Translator_String: ignore-string handling,
 * translated-text storage, and delegation to the parent Translator.
 *
 * @package DeeplXML
 * @subpackage Tests
 */
final class TranslatorStringTests extends TestCase
{
    // region: getPreparedText() — ignore strings

    /**
     * An ignore string must be wrapped in <deeplignore> tags in the
     * prepared text.
     *
     * @see \DeeplXML\Translator_String::getPreparedText()
     * @see Translator::IGNORE_TAG
     */
    public function test_getPreparedText_wrapsIgnoreString() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');
        $string = $translator->addString('id1', 'Hello world');
        $string->addIgnoreString('world');

        $this->assertStringContainsString(
            '<deeplignore>world</deeplignore>',
            $string->getPreparedText()
        );
    }

    /**
     * Adding the same ignore string twice must not cause double-wrapping:
     * the deduplication guard must keep only one entry.
     *
     * @see \DeeplXML\Translator_String::addIgnoreString()
     */
    public function test_getPreparedText_deduplicatesIgnoreString() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');
        $string = $translator->addString('id1', 'Hello world');
        $string->addIgnoreString('world');
        $string->addIgnoreString('world'); // duplicate — must be ignored

        $prepared = $string->getPreparedText();

        // Must be wrapped exactly once; no nested <deeplignore> tags.
        $this->assertStringNotContainsString('<deeplignore><deeplignore>', $prepared);
        $this->assertSame(1, substr_count($prepared, '<deeplignore>world</deeplignore>'));
    }

    // endregion

    // region: setTranslatedText()

    /**
     * setTranslatedText() must strip <deeplignore> open and close tags
     * from the stored translated value.
     *
     * @see \DeeplXML\Translator_String::setTranslatedText()
     */
    public function test_setTranslatedText_stripsIgnoreTags() : void
    {
        $translator = new TestableTranslator('dummy', 'EN', 'DE');
        $string = $translator->addString('id1', 'Hello world');
        $translator->markTranslated();

        $string->setTranslatedText('Hallo <deeplignore>world</deeplignore>');

        $this->assertSame('Hallo world', $string->getTranslatedText());
    }

    /**
     * setTranslatedText() must apply html_entity_decode() so that HTML
     * entities in the DeepL response are stored as literal characters.
     *
     * @see \DeeplXML\Translator_String::setTranslatedText()
     */
    public function test_setTranslatedText_decodesHTMLEntities() : void
    {
        $translator = new TestableTranslator('dummy', 'EN', 'DE');
        $string = $translator->addString('id1', 'Hello & world');
        $translator->markTranslated();

        $string->setTranslatedText('Hallo &amp; Welt');

        $this->assertSame('Hallo & Welt', $string->getTranslatedText());
    }

    // endregion

    // region: isTranslated() delegation

    /**
     * Translator_String::isTranslated() must delegate to the parent
     * Translator and return false before any translation has run.
     *
     * @see \DeeplXML\Translator_String::isTranslated()
     */
    public function test_isTranslated_delegatesToTranslator() : void
    {
        $translator = new Translator('dummy', 'EN', 'DE');
        $string = $translator->addString('id1', 'Hello');

        $this->assertFalse($string->isTranslated());
        $this->assertSame($translator->isTranslated(), $string->isTranslated());
    }

    // endregion
}
