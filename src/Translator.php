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
 * DeepL translation helper, to easily translate strings
 * using the DeepL API. Uses the <code>scn/deepl-api-connector</code>
 * package as backend to handle the communication with the
 * server, and wraps a self explaining inteface over it.
 * 
 * @package DeeplXML
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class Translator
{
    const ERROR_STRING_ID_ALREADY_EXISTS = 37601;
    
    const ERROR_NO_STRINGS_TO_TRANSLATE = 37602;
    
    const ERROR_FAILED_CONVERTING_TEXT = 37603;
    
    const ERROR_MISSING_ID_ATTRIBUTE_IN_RESPONSE = 37604;
    
    const ERROR_RESPONSE_STRING_DOES_NOT_EXIST = 37605;
    
    const ERROR_STRING_NOT_FOUND_IN_RESULT = 37506;
    
    const ERROR_CANNOT_GET_UNKNOWN_STRING = 37507;
    
   /**
    * The name of the XML tag to use to store individual texts in
    * @var string
    */
    const SPLITTING_TAG = 'deeplstring';
   
   /**
    * The name of the XML tag used to tell DeepL to ignore contents.
    * @var string
    */
    const IGNORE_TAG = 'deeplignore'; 
    
    /**
     * @var \Scn\DeeplApiConnector\DeeplClient[]
     * @see Translator::initDeepL()
     */
    protected static $deepl = array();
    
   /**
    * @var Translator_String[]
    */
    protected $strings = array();
    
   /**
    * @var string
    */
    protected $sourceLang;
    
   /**
    * @var string
    */
    protected $targetLang;
    
   /**
    * @var bool
    */
    protected $translated = false;
    
   /**
    * @var string
    */
    protected $apiKey;
    
   /**
    * @var bool
    */
    protected $simulate = false;
    
   /**
    * @var string
    */
    protected $proxy;
    
   /**
    * Create a new translation helper for the specified languages.
    * 
    * @param string $apiKey The API key to use to connect to the DeepL API
    * @param string $sourceLang The language of the source strings, e.g. "EN", "DE"
    * @param string $targetLang The language to translate strings to, e.g. "EN", "DE"
    */
    public function __construct(string $apiKey, string $sourceLang, string $targetLang)
    { 
        $this->apiKey = $apiKey;
        $this->sourceLang = strtoupper($sourceLang);
        $this->targetLang = strtoupper($targetLang);
    }
    
    public function getSourceLanguage() : string
    {
        return $this->sourceLang;
    }
    
    public function getTargetLanguage() : string
    {
        return $this->targetLang;
    }
    
   /**
    * Sets whether to enable the simulation mode. In this 
    * mode, unique sets of strings to translate are cached,
    * so that only a single request is sent to DeepL per set.
    * 
    * This is useful for debugging, to avoid sending lots of
    * requests to the DeepL API.
    * 
    * @param bool $enabled
    * @return Translator
    */
    public function setSimulation(bool $enabled=true) : Translator
    {
        $this->simulate = $enabled;
        return $this;
    }
    
   /**
    * Sets a proxy to use to connect to the DeepL API endpoint.
    * All requests will be done through the proxy.
    * 
    * @param string $proxyURI The URI, in the form: tcp://username:password@1.2.3.4:10
    * @return Translator
    */
    public function setProxy(string $proxyURI) : Translator
    {
        $this->proxy = $proxyURI;
        
        $this->reset();
        
        return $this;
    }
    
   /**
    * Initializes the DeepL connection - this is only done
    * once, and shared between all translator instances, per
    * API key to allow different API keys.
    * 
    * @throws Translator_Exception
    */
    protected function initDeepL()
    {
        if(isset(self::$deepl[$this->apiKey])) {
            return;
        }
         
        self::$deepl[$this->apiKey] = new \Scn\DeeplApiConnector\DeeplClient(
            new \GuzzleHttp\Client($this->compileRequestOptions()),
            new \Scn\DeeplApiConnector\Handler\DeeplRequestFactory($this->apiKey)
        );
    }
    
   /** 
    * Resets the deepl instance if it has already been created.
    */
    protected function reset()
    {
        if(isset(self::$deepl[$this->apiKey])) {
            self::$deepl[$this->apiKey] = null;
        }
    }
    
   /**
    * Compiles the request options to the expected Guzzle
    * request format.
    * 
    * @return array
    */
    protected function compileRequestOptions() : array
    {
        $options = array();
        
        if(isset($this->proxy)) 
        {
            $options['proxy'] = $this->proxy;
        }
        
        return $options;
    }
    
   /**
    * Retrieves the DeepL API connection, to be able to run more
    * advanced translation tasks.
    * 
    * @return \Scn\DeeplApiConnector\DeeplClient
    */
    public function getConnector() : \Scn\DeeplApiConnector\DeeplClient
    {
        $this->initDeepL();
        
        return self::$deepl[$this->apiKey];
    }

   /**
    * Adds a string to translate, and returns the string instance.
    * This will allow retrieving the translated text once the
    * translation has been fetched.
    * 
    * @param string $id
    * @param string $text
    * @throws Translator_Exception
    * @return Translator_String
    */
    public function addString(string $id, string $text) : Translator_String
    {
        if(isset($this->strings[$id])) {
            throw new Translator_Exception(
                'A string already exists with the same ID.',
                sprintf(
                    'Tried adding the string [%s].',
                    $id
                ),
                self::ERROR_STRING_ID_ALREADY_EXISTS
            );
        }
        
        $string = new Translator_String($this, $id, $text);
        
        $this->strings[$id] = $string;
        
        return $string;
    }
    
   /**
    * Retrieves the XML that the translator sends to DeepL
    * to be translated.
    * 
    * @return string
    * @see Translator::renderXML()
    */
    public function getXML() : string
    {
        return $this->renderXML();
    }
    
   /**
    * Starts the translation process. Afterwards, the translated strings
    * can be accessed via the string instances themselves.
    * 
    * @throws Translator_Exception
    */
    public function translate() : void
    {
        if($this->translated) {
            return;
        }
        
        if(empty($this->strings)) {
            throw new Translator_Exception(
                'No strings to translate',
                'Cannot run a translation without strings to translate',
                self::ERROR_NO_STRINGS_TO_TRANSLATE
            );
        }
        
        $this->initDeepL();
        
        $sourceXML = $this->renderXML();
        
        $config = new \Scn\DeeplApiConnector\Model\TranslationConfig(
            $sourceXML,
            $this->targetLang,
            $this->sourceLang
        );
        
        $config->setTagHandling(array('xml'));
        $config->setPreserveFormatting(\Scn\DeeplApiConnector\Enum\TextHandlingEnum::PRESERVEFORMATTING_ON);
        $config->setSourceLang($this->sourceLang);
        $config->setTargetLang($this->targetLang);
        $config->setSplitSentences(\Scn\DeeplApiConnector\Enum\TextHandlingEnum::SPLITSENTENCES_NONEWLINES);
        $config->setIgnoreTags(array(self::IGNORE_TAG));

        $cacheID = null;
        $xml = null;
        
        if($this->simulate)
        {
            $cacheID = 'deepl-'.md5($sourceXML);
        
            if(isset($_SESSION[$cacheID])) {
                $xml = $_SESSION[$cacheID];
            }
        }
        
        if($xml === null)
        {
            /* @var $translation \Scn\DeeplApiConnector\Model\Translation */
            
            $translation = self::$deepl[$this->apiKey]->getTranslation($config);
            
            $xml = $translation->getText();
            
            if($cacheID !== null) {
                $_SESSION[$cacheID] = $xml;
            }
        }
        
        $this->parseXMLResult($xml);

        $this->translated = true;
    }
    
   /**
    * Whether the strings have been translated.
    * @return bool
    */
    public function isTranslated() : bool
    {
        return $this->translated;
    }
    
   /**
    * Retrieves all strings that were added to translate.
    * @return array
    */
    public function getStrings()
    {
        return array_values($this->strings);
    }
   
   /**
    * Checks whether a string with the specified ID has been added.
    * @param string $id
    * @return bool
    */
    public function stringIDExists(string $id) : bool
    {
        return isset($this->strings[$id]);
    }
    
   /**
    * Retrieves a string instance that was added previously by its ID.
    * 
    * @param string $id
    * @throws Translator_Exception
    * @return Translator_String
    */
    public function getStringByID(string $id) : Translator_String
    {
        if(isset($this->strings[$id])) {
            return $this->strings[$id];
        }
        
        throw new Translator_Exception(
            'Cannot get unknown string',
            sprintf(
                'No string with the ID [%s] has been added.',
                $id
            ),
            self::ERROR_CANNOT_GET_UNKNOWN_STRING
        );
    }
    
   /**
    * Renders the XML document containing the strings to 
    * translate, which is sent to DeepL. 
    * 
    * @return string
    */
    protected function renderXML() : string
    {
        $xml = \AppUtils\XMLHelper::create();
        $root = $xml->createRoot('document');
        
        foreach($this->strings as $string) 
        {
            try
            {
                $text = $string->getPreparedText();
                
                if(\AppUtils\ConvertHelper::isStringHTML($text)) 
                {
                    $fragment = \AppUtils\XMLHelper::string2xml($text);
                    $tag = $xml->addFragmentTag($root, self::SPLITTING_TAG, $fragment);
                }
                else
                {
                    $tag = $xml->addTextTag($root, self::SPLITTING_TAG, $text);
                }
            }
            catch(\Exception $e) 
            {
                throw new Translator_Exception(
                    'Failed to convert text snippet',
                    sprintf(
                        'Adding a text snippet to XML failed: [%s].',
                        htmlspecialchars($text)
                    ),
                    self::ERROR_FAILED_CONVERTING_TEXT,
                    $e
                );
            }
            
            $xml->addAttribute($tag, 'id', $string->getID());
        }
        
        $markup = $xml->saveXML();
        
        return $markup;
    }
    
   /**
    * Parses the returned XML from DeepL to update the strings.
    * 
    * NOTE: DeepL does not always return all strings that were
    * given. If a string cannot be translated or is empty for 
    * example, it will not be present in the result XML. This is
    * why we only update the strings that in the result.
    * 
    * @param string $xml
    * @throws Translator_Exception
    */
    protected function parseXMLResult(string $xml) : void
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);
        
        $nodes = $dom->getElementsByTagName(self::SPLITTING_TAG);
        
        $results = array();
        
        foreach($nodes as $node) 
        {
            $stringID = $node->getAttribute('id');
            
            $results[] = $stringID;
            
            if(empty($stringID)) 
            {
                throw new Translator_Exception(
                    'ID attribute value missing in DeepL response XML',
                    sprintf(
                        'A translation element does not have the expected ID attribute. Received XML: %s We originally sent this XML: %s',
                        $this->prettifyXML($xml),
                        $this->prettifyXML($this->getXML())
                    ),
                    self::ERROR_MISSING_ID_ATTRIBUTE_IN_RESPONSE
                );
            }
            
            if(!isset($this->strings[$stringID])) 
            {
                throw new Translator_Exception(
                    'Returned string does not exist',
                    sprintf(
                        'The string [%s] was present in the translated XML that does not exist. Received XML: %s We originally sent this XML: %s',
                        $stringID,
                        $this->prettifyXML($xml),
                        $this->prettifyXML($this->getXML())
                    ),
                    self::ERROR_RESPONSE_STRING_DOES_NOT_EXIST
                );
            }

            $text = '';
            foreach($node->childNodes as $child) {
                $text .= $dom->saveXML($child);
            }
            
            $this->strings[$stringID]->setTranslatedText($text);
        }
        
        // make sure that all strings were present
        foreach($this->strings as $string)
        {
            $stringID = $string->getID();
            
            if(!in_array($stringID, $results)) {
                throw new Translator_Exception(
                    'String not found in result',
                    sprintf(
                        'The string [%s] could not be found in the result XML. Received this XML: %s We originally sent this XML: %s',
                        $stringID,
                        $this->prettifyXML($xml),
                        $this->prettifyXML($this->getXML())
                    ),
                    self::ERROR_STRING_NOT_FOUND_IN_RESULT
                );
            }
        }
    }
    
    protected function prettifyXML(string $xml)
    {
        return \AppUtils\Highlighter::xml($xml, true); 
    }
}
