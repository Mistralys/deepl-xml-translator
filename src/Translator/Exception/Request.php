<?php

declare(strict_types=1);

namespace DeeplXML;

use AppUtils\ConvertHelper;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Scn\DeeplApiConnector\Exception\RequestException;
use Scn\DeeplApiConnector\Model\TranslationConfig;
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

        if(!$ex)
        {
            return $this->renderText(sprintf(
                'An exception of type [%1$s] occurred.<br>'.
                'Source language: %2$s<br>'.
                'Target language: %3$s<br>'.
                'Submitted XML:<pre>%4$s</pre>',
                parseVariable($this->getPrevious())->enableType()->toString(),
                $this->config->getSourceLang(),
                $this->config->getTargetLang(),
                $this->filterHTML($this->getXML(), $html)
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