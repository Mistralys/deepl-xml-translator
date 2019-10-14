<?php
/**
 * File containing the {@link Translator} class.
 *
 * @package DeeplXML
 * @see Translator
 */

declare(strict_types=1);

namespace DeeplXML;

/**
 * Container for a single string to translate.
 *
 * @package DeeplXML
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class Translator_String
{
   /**
    * @var Translator
    */
    protected $translator;
    
   /**
    * @var string
    */
    protected $id;
    
   /**
    * @var string
    */
    protected $original;
   
   /**
    * @var string
    */
    protected $translated = '';
    
    protected $ignore = array();
    
    public function __construct(Translator $translator, string $id, string $original)
    {
        $this->translator = $translator;
        $this->id = $id;
        $this->original = $original;
    }
    
   /**
    * Retrieves the string ID, as it was specified when it was added. 
    * @return string
    */
    public function getID() : string
    {
        return $this->id;
    }
    
   /**
    * Retrieves the original, untranslated text.
    * @return string
    */
    public function getOriginalText() : string
    {
        return $this->original;
    }
    
   /**
    * Retrieves the text to translate with any adjustments
    * required to send it to DeepL, like ignored strings.
    *  
    * @return string
    */
    public function getPreparedText() : string
    {
        $text = $this->getOriginalText();
        
        foreach($this->ignore as $string) 
        {
            $text = str_replace(
                $string, 
                sprintf(
                    '<%1$s>%2$s</%1$s>',
                    Translator::IGNORE_TAG,
                    $string
                ),
                $text
            );
        }
        
        return $text;
    }
    
   /**
    * Adds a string to ignore during the translation: any
    * occurrences of the string in the text to translate 
    * will be ignored by DeepL.
    * 
    * @param string $string
    */
    public function addIgnoreString($string) : Translator_String
    {
        if(!in_array($string, $this->ignore)) {
            $this->ignore[] = $string;
        }
        
        return $this;
    }
    
   /**
    * Sets the translated text - this is done automatically
    * by the translator itself once the translation results
    * have been received from DeepL.
    * 
    * @param string $text
    */
    public function setTranslatedText(string $text) : void
    {
        $tagName = Translator::IGNORE_TAG;
        
        // remove the ignore tags, if any.
        if(strstr($text, $tagName)) 
        {
            $text = str_replace(
                array('<'.$tagName.'>', '</'.$tagName.'>'), 
                '',
                $text
            );
        }
        
        $text = html_entity_decode($text);
        
        $this->translated = $text;
    }
    
   /**
    * Retrieves the translated text. Runs the translation
    * automatically if the text has not been translated yet.
    * 
    * @return string
    * @throws Translator_Exception 
    */
    public function getTranslatedText() : string
    {
        $this->translate();
        
        return $this->translated;
    }
    
   /**
    * Whether the string has been translated.
    * @return bool
    */    
    public function isTranslated() : bool
    {
        return $this->translator->isTranslated();
    }
    
   /**
    * Alias for calling {@link Translator::translate()}.
    * 
    * @see Translator::translate()
    */
    public function translate() : void
    {
        $this->translator->translate();
    }
}