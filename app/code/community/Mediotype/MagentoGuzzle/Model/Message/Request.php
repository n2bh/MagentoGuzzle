<?php
/**
 * HTTP request class to send requests
 */
class Mediotype_MagentoGuzzle_Model_Message_Request extends Mediotype_MagentoGuzzle_Model_Message_AbstractMessage implements Mediotype_MagentoGuzzle_Model_Message_RequestInterface
{
    use Mediotype_MagentoGuzzle_Trait_Event_HasEmitterTrait;

    /** @var Mediotype_MagentoGuzzle_Model_Url HTTP Url */
    private $url;

    /** @var string HTTP method */
    private $method;

    /** @var Mediotype_MagentoGuzzle_Model_Collection Transfer options */
    private $transferOptions;

    /**
     * @param string           $method  HTTP method
     * @param string|Mediotype_MagentoGuzzle_Model_Url       $url     HTTP URL to connect to. The URI scheme,
     *   host header, and URI are parsed from the full URL. If query string
     *   parameters are present they will be parsed as well.
     * @param array|Mediotype_MagentoGuzzle_Model_Collection $headers HTTP headers
     * @param mixed            $body    Body to send with the request
     * @param array            $options Array of options to use with the request
     *     - emitter: Event emitter to use with the request
     */
    public function __construct(
        $method,
        $url,
        $headers = array(),
        $body = null,
        array $options = array()
    ) {
        $this->setUrl($url);
        $this->method = strtoupper($method);
        $this->handleOptions($options);
        $this->transferOptions = new Mediotype_MagentoGuzzle_Model_Collection($options);
        $this->addPrepareEvent();

        if ($body !== null) {
            $this->setBody($body);
        }

        if ($headers) {
            foreach ($headers as $key => $value) {
                $this->setHeader($key, $value);
            }
        }
    }

    public function __clone()
    {
        if ($this->emitter) {
            $this->emitter = clone $this->emitter;
        }
        $this->transferOptions = clone $this->transferOptions;
        $this->url = clone $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url instanceof Mediotype_MagentoGuzzle_Model_Url ? $url : Mediotype_MagentoGuzzle_Model_Url::fromString($url);
        $this->updateHostHeaderFromUrl();

        return $this;
    }

    public function getUrl()
    {
        return (string) $this->url;
    }

    public function setQuery($query)
    {
        $this->url->setQuery($query);

        return $this;
    }

    public function getQuery()
    {
        return $this->url->getQuery();
    }

    public function setMethod($method)
    {
        $this->method = strtoupper($method);

        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getScheme()
    {
        return $this->url->getScheme();
    }

    public function setScheme($scheme)
    {
        $this->url->setScheme($scheme);

        return $this;
    }

    public function getPort()
    {
        return $this->url->getPort();
    }

    public function setPort($port)
    {
        $this->url->setPort($port);
        $this->updateHostHeaderFromUrl();

        return $this;
    }

    public function getHost()
    {
        return $this->url->getHost();
    }

    public function setHost($host)
    {
        $this->url->setHost($host);
        $this->updateHostHeaderFromUrl();

        return $this;
    }

    public function getPath()
    {
        return '/' . ltrim($this->url->getPath(), '/');
    }

    public function setPath($path)
    {
        $this->url->setPath($path);

        return $this;
    }

    public function getResource()
    {
        $resource = $this->getPath();
        if ($query = (string) $this->url->getQuery()) {
            $resource .= '?' . $query;
        }

        return $resource;
    }

    public function getConfig()
    {
        return $this->transferOptions;
    }

    protected function handleOptions(array &$options)
    {
        parent::handleOptions($options);
        // Use a custom emitter if one is specified, and remove it from
        // options that are exposed through getConfig()
        if (isset($options['emitter'])) {
            $this->emitter = $options['emitter'];
            unset($options['emitter']);
        }
    }

    /**
     * Adds a subscriber that ensures a request's body is prepared before
     * sending.
     */
    private function addPrepareEvent()
    {
        static $subscriber;
        if (!$subscriber) {
            $subscriber = new Mediotype_MagentoGuzzle_Model_Subscriber_Prepare();
        }

        $this->getEmitter()->attach($subscriber);
    }

    private function updateHostHeaderFromUrl()
    {
        $port = $this->url->getPort();
        $scheme = $this->url->getScheme();
        if ($host = $this->url->getHost()) {
            if (($port == 80 && $scheme == 'http') ||
                ($port == 443 && $scheme == 'https')
            ) {
                $this->setHeader('Host', $host);
            } else {
                $this->setHeader('Host', "{$host}:{$port}");
            }
        }
    }
}
