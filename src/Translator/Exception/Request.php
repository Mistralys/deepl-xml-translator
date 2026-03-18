<?php

declare(strict_types=1);

namespace DeeplXML;

use AppUtils\ConvertHelper;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Scn\DeeplApiConnector\Exception\RequestException;
use Scn\DeeplApiConnector\Model\TranslationConfig;
use function AppUtils\parseThrowable;
use function AppUtils\parseVariable;

class Translator_Exception_Request extends Translator_Exception
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var TranslationConfig
     */
    private $config;

    /**
     * @var string
     */
    private $xml;

    /**
     * Guard flag to prevent infinite recursion when getDetails()
     * calls renderAnalysis(), which internally calls getDetails()
     * via parseThrowable().
     *
     * @var bool
     */
    private bool $computingDetails = false;

    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }

    public function setConfig(TranslationConfig $config): void
    {
        $this->config = $config;
    }

    public function setXML(string $xml): void
    {
        $this->xml = $xml;
    }

    /**
     * Overrides the default details getter to return the full
     * diagnostic analysis when config and XML are available.
     * Falls back to the constructor-provided details string
     * while the exception is still being constructed (i.e. before
     * setConfig() / setXML() have been called) or during the
     * internal renderAnalysis() call to avoid recursion.
     *
     * @return string
     */
    public function getDetails() : string
    {
        if($this->computingDetails || $this->config === null || $this->xml === null)
        {
            return parent::getDetails();
        }

        $this->computingDetails = true;
        $result = $this->renderAnalysis();
        $this->computingDetails = false;

        return $result;
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Retrieves the `deepl-api-connector` configuration used
     * for the DeepL request.
     *
     * @return TranslationConfig
     */
    public function getConfig(): TranslationConfig
    {
        return $this->config;
    }

    /**
     * Retrieves the XML that was submitted to DeepL for translation.
     *
     * @return string
     */
    public function getXML(): string
    {
        return $this->xml;
    }

    /**
     * Whether the exception that was encountered is a Guzzle
     * HTTP request exception.
     *
     * @return bool
     */
    public function hasGuzzleException(): bool
    {
        return $this->getGuzzleException() !== null;
    }

    /**
     * Retrieves the Guzzle HTTP request exception that was
     * originally thrown, if any.
     *
     * @return ClientException|null
     */
    public function getGuzzleException() : ?ClientException
    {
        $previous = $this->getPrevious();

        if(!$previous instanceof RequestException)
        {
            return null;
        }

        $previous = $previous->getPrevious();

        if($previous instanceof ClientException)
        {
            return $previous;
        }

        return null;
    }

    /**
     * Retrieves the original Guzzle request instance, if any.
     * The Guzzle exception must be present for this to work.
     *
     * @return RequestInterface
     */
    public function getGuzzleRequest() : ?RequestInterface
    {
        $ex = $this->getGuzzleException();

        if($ex) {
            return $ex->getRequest();
        }

        return null;
    }

    /**
     * Retrieves the original Guzzle response instance, if any.
     * The Guzzle exception must be present for this to work.
     *
     * @return ResponseInterface|null
     */
    public function getGuzzleResponse() : ?ResponseInterface
    {
        $ex = $this->getGuzzleException();

        if($ex) {
            return $ex->getResponse();
        }

        return null;
    }

    public function renderAnalysis(bool $html=false) : string
    {
        $ex = $this->getGuzzleException();
        $previous = $this->getPrevious();
        $message = parseThrowable($this)->renderErrorMessage(true);

        if(!$ex)
        {
            $previousInfo = $previous !== null
                ? sprintf('An exception of type [%s] occurred.', parseVariable($previous)->enableType()->toString())
                : 'No HTTP request exception was captured. The API may have returned an empty or malformed response (e.g. 403 Forbidden due to an invalid API key).';

            return $this->renderText(sprintf(
                '%1$s<br>'.
                'Exception info:<br>'.
                '%5$s<br>'.
                'Source language: %2$s<br>'.
                'Target language: %3$s<br>'.
                'Submitted XML:<pre>%4$s</pre>',
                $previousInfo,
                $this->config->getSourceLang(),
                $this->config->getTargetLang(),
                $this->filterHTML($this->getXML(), $html),
                $message
            ), $html);
        }

        $request = $ex->getRequest();
        $response = $ex->getResponse();
        $body = $response->getBody();

        return $this->renderText(sprintf(
            'An error occurred while transmitting the translation request to DeepL.<br>'.
            'Response code: %4$s<br>'.
            'URI: %1$s<br>'.
            'Source language: %8$s<br>'.
            'Target language: %9$s<br>'.
            'Response body (%2$s bytes):<pre>%3$s</pre>'.
            'Request body (%5$s bytes):<pre>%6$s</pre>'.
            'Submitted XML:<pre>%7$s</pre>',
            $request->getUri(),
            $body->getSize(),
            $body->getContents(),
            $response->getStatusCode().' '.$response->getReasonPhrase(),
            $request->getBody()->getSize(),
            $this->filterHTML($request->getBody()->getContents(), $html),
            $this->filterHTML($this->getXML(), $html),
            $this->config->getSourceLang(),
            $this->config->getTargetLang()
        ), $html);
    }

    private function filterHTML(string $html, bool $htmlAllowed) : string
    {
        if(!$htmlAllowed)
        {
            return $html;
        }

        return htmlspecialchars($html);
    }

    private function renderText(string $text, bool $html) : string
    {
        if($html)
        {
            return $text;
        }

        return str_replace(
            array('<br>', '<pre>', '</pre>'),
            PHP_EOL,
            $text
        );
    }
}