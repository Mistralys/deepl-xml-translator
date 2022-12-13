<?php
/**
 * File containing the {@link Translator} class.
 * 
 * @package DeeplXML
 * @see Translator
 */

declare(strict_types=1);

namespace DeeplXML;

use AppUtils\ConvertHelper;
use AppUtils\Highlighter;
use AppUtils\XMLHelper;
use DOMDocument;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Scn\DeeplApiConnector\DeeplClient;
use Scn\DeeplApiConnector\DeeplClientFactory;
use Scn\DeeplApiConnector\Enum\TextHandlingEnum;
use Scn\DeeplApiConnector\Exception\RequestException;
use Scn\DeeplApiConnector\Model\ResponseModelInterface;
use Scn\DeeplApiConnector\Model\Translation;
use Scn\DeeplApiConnector\Model\TranslationConfig;
use function AppUtils\parseVariable;

/**
 * "DeepL" translation helper, to easily translate strings
 * using the DeepL API. Uses the <code>scn/deepl-api-connector</code>
 * package as backend to handle the communication with the
 * server, and wraps a self explaining interface over it.
 * 
 * @package DeeplXML
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class Translator
{
    public const ERROR_STRING_ID_ALREADY_EXISTS = 37601;
    public const ERROR_NO_STRINGS_TO_TRANSLATE = 37602;
    public const ERROR_FAILED_CONVERTING_TEXT = 37603;
    public const ERROR_MISSING_ID_ATTRIBUTE_IN_RESPONSE = 37604;
    public const ERROR_RESPONSE_STRING_DOES_NOT_EXIST = 37605;
    public const ERROR_STRING_NOT_FOUND_IN_RESULT = 37506;
    public const ERROR_CANNOT_GET_UNKNOWN_STRING = 37507;
    public const ERROR_TRANSLATION_REQUEST_FAILED = 37508;
    public const ERROR_UNSUPPORTED_TRANSLATION_RESULT = 37509;
    public const ERROR_EMPTY_XML_DOCUMENT = 37510;
    public const ERROR_TRANSLATION_RESULT_EMPTY = 37511;

   /**
    * The name of the XML tag to use to store individual texts in
    * @var string
    */
    public const SPLITTING_TAG = 'deeplstring';
   
   /**
    * The name of the XML tag used to tell DeepL to ignore contents.
    * @var string
    */
    public const IGNORE_TAG = 'deeplignore'; 
    
    /**
     * @var array<string,DeeplClient|null>
     * @see Translator::initDeepL()
     */
    protected static array $deepl = array();
    
   /**
    * @var Translator_String[]
    */
    protected array $strings = array();
    
    protected string $sourceLang;
    protected string $targetLang;
    protected bool $translated = false;
    protected string $apiKey;
    protected bool $simulate = false;
    protected string $proxy = '';
    private float $timeOut = 8.0;
    private bool $requestDebugging = false;

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

    public function setTimeOut(float $timeOut) : self
    {
        $this->timeOut = $timeOut;
        return $this;
    }

    public function getTimeOut() : float
    {
        return $this->timeOut;
    }

    public function enableRequestDebug(bool $enable) : self
    {
        $this->requestDebugging = $enable;
        return $this;
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
    */
    private function initDeepL() : void
    {
        if(isset(self::$deepl[$this->apiKey])) {
            return;
        }

        self::$deepl[$this->apiKey] = DeeplClientFactory::create(
            $this->apiKey,
            new Client($this->compileRequestOptions())
        );
    }
    
   /** 
    * Resets the deepl instance if it has already been created.
    */
    private function reset() : void
    {
        if(isset(self::$deepl[$this->apiKey])) {
            self::$deepl[$this->apiKey] = null;
        }
    }
    
   /**
    * Compiles the request options to the expected Guzzle
    * request format.
    * 
    * @return array<string,mixed>
    */
    private function compileRequestOptions() : array
    {
        $options = array();
        
        if(isset($this->proxy)) 
        {
            $options[RequestOptions::PROXY] = $this->proxy;
        }

        $options[RequestOptions::CONNECT_TIMEOUT] = $this->timeOut;
        $options[RequestOptions::DEBUG] = $this->requestDebugging;
        
        return $options;
    }
    
   /**
    * Retrieves the DeepL API connection, to be able to run more
    * advanced translation tasks.
    * 
    * @return DeeplClient
    */
    public function getConnector() : DeeplClient
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
     * @throws Translator_Exception
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
    * @throws Translator_Exception_Request
    *
    * @see Translator::ERROR_NO_STRINGS_TO_TRANSLATE
    * @see Translator::ERROR_TRANSLATION_REQUEST_FAILED
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

        $config = new TranslationConfig(
            $sourceXML,
            $this->targetLang,
            $this->sourceLang
        );
        
        $config->setTagHandling(array('xml'));
        $config->setPreserveFormatting(TextHandlingEnum::PRESERVEFORMATTING_ON);
        $config->setSourceLang($this->sourceLang);
        $config->setTargetLang($this->targetLang);
        $config->setSplitSentences(TextHandlingEnum::SPLITSENTENCES_NONEWLINES);
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
            try
            {
                $translation = $this->getTranslation($config);

                if($translation instanceof Translation)
                {
                    $xml = $translation->getText();
                }
                else
                {
                    throw new Translator_Exception(
                        'Unsupported translation result',
                        sprintf(
                            'The class instance of type [%s] is unhandled.',
                            parseVariable($translation)->enableType()->toString()
                        ),
                        self::ERROR_UNSUPPORTED_TRANSLATION_RESULT
                    );
                }
            }
            catch(Exception $e)
            {
                $ex = new Translator_Exception_Request(
                    'The translation request failed',
                    '',
                    self::ERROR_TRANSLATION_REQUEST_FAILED,
                    $e
                );

                $ex->setTranslator($this);
                $ex->setXML($sourceXML);
                $ex->setConfig($config);

                throw $ex;
            }

            if($cacheID !== null) {
                $_SESSION[$cacheID] = $xml;
            }
        }

        if(empty($xml))
        {
            $ex = new Translator_Exception_Request(
                'No translation result XML returned.',
                'The result XML returned by the service was empty. Typically, this happens if the request to the DeepL service returned a 403: Forbidden, which points to an API key issue.',
                self::ERROR_TRANSLATION_RESULT_EMPTY
            );

            $ex->setTranslator($this);
            $ex->setXML($sourceXML);
            $ex->setConfig($config);

            throw $ex;
        }

        $this->parseXMLResult($xml);

        $this->translated = true;
    }

    /**
     * @throws RequestException
     */
    private function getTranslation(TranslationConfig $config) : ResponseModelInterface
    {
        return $this->getConnector()->getTranslation($config);
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
    * @return Translator_String[]
    */
    public function getStrings() : array
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
     * @throws Translator_Exception
     */
    private function renderXML() : string
    {
        $xml = XMLHelper::create();
        $root = $xml->createRoot('document');
        $text = '';
        
        foreach($this->strings as $string) 
        {
            try
            {
                $text = $string->getPreparedText();
                
                if(ConvertHelper::isStringHTML($text))
                {
                    $fragment = XMLHelper::string2xml($text);
                    $tag = $xml->addFragmentTag($root, self::SPLITTING_TAG, $fragment);
                }
                else
                {
                    $tag = $xml->addTextTag($root, self::SPLITTING_TAG, $text);
                }
            }
            catch(Exception $e)
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
        
        return $xml->saveXML();
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
    private function parseXMLResult(string $xml) : void
    {
        $xml = trim($xml);

        if(empty($xml)) {
            throw new Translator_Exception(
                'Empty XML translation document',
                'The specified XML code is an empty string.',
                self::ERROR_EMPTY_XML_DOCUMENT
            );
        }

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);
        
        $nodes = $dom->getElementsByTagName(self::SPLITTING_TAG);
        
        $results = array();
        
        foreach($nodes as $node) 
        {
            $stringID = (string)$node->getAttribute('id');
            
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
            
            if(!in_array($stringID, $results, true)) {
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
    
    private function prettifyXML(string $xml) : string
    {
        return Highlighter::xml($xml, true);
    }
}
