<?php namespace PCextreme\RgwAdminClient;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Http\Message\MessageFactory;
use Http\Message\UriFactory;
use PCextreme\RgwAdminClient\Authentication\SignatureV2;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client implements ClientInterface
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var PluginClient
     */
    private $pluginClient;

    /**
     * @var UriFactory
     */
    private $uriFactory;

    /**
     * Create rgw admin client instance.
     *
     * @param array $options
     * @param array $collaborators
     *
     * @throws \InvalidArgumentException when the required options are not set.
     * @throws NotFoundException when there is no valid MessageFactory or HttpClient found.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->assertRequiredOptions($options);

        $this->setOptions($options);

        $this->setCollaborators($collaborators);
    }

    /**
     * Set options.
     *
     * @param array $options
     *
     * @return void
     */
    protected function setOptions(array $options)
    {
        $possible = $this->getRequiredOptions();

        $configured = array_intersect_key($options, array_flip($possible));

        foreach ($configured as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Set collaborators.
     *
     * @param array $collaborators
     *
     * @return void
     *
     * @throws NotFoundException when there is no MessageFactory, HttpClient or UriFactory found.
     */
    protected function setCollaborators(array $collaborators)
    {
        if (empty($collaborators['messageFactory'])) {
            $collaborators['messageFactory'] = MessageFactoryDiscovery::find();
        }

        $this->setMessageFactory($collaborators['messageFactory']);

        if (empty($collaborators['httpClient'])) {
            $collaborators['httpClient'] = HttpClientDiscovery::find();
        }

        $this->setHttpClient($collaborators['httpClient']);

        if (empty($collaborators['uriFactory'])) {
            $collaborators['uriFactory'] = UriFactoryDiscovery::find();
        }

        $this->setUriFactory($collaborators['uriFactory']);
    }

    /**
     * Set the UriFactory instance.
     *
     * @param UriFactory $uriFactory
     *
     * @return void
     */
    public function setUriFactory(UriFactory $uriFactory)
    {
        $this->uriFactory = $uriFactory;
    }

    /**
     * Returns the current UriFactory instance.
     *
     * @return UriFactory
     */
    public function getUriFactory()
    {
        return $this->uriFactory;
    }

    /**
     * Set the MessageFactory instance.
     *
     * @param MessageFactory $messageFactory
     *
     * @return void
     */
    public function setMessageFactory(MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    /**
     * Returns the current MessageFactory instance.
     *
     * @return MessageFactory
     */
    public function getMessageFactory()
    {
        return $this->messageFactory;
    }

    /**
     * Set the HttpClient instance.
     *
     * @param HttpClient $httpClient
     *
     * @return void
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Returns the current HttpClient instance.
     *
     * @return PluginClient
     *
     * @throws \RuntimeException
     */
    public function getHttpClient()
    {
        if ($this->pluginClient !== null) {
            return $this->pluginClient;
        }

        $plugins = [
            new ErrorPlugin(),
            new AuthenticationPlugin(
                new SignatureV2($this->apiKey, $this->secretKey)
            ),
        ];

        $this->pluginClient = new PluginClient($this->httpClient, $plugins);

        return $this->pluginClient;
    }

    /**
     * Return all the required options.
     *
     * @return array
     */
    protected function getRequiredOptions()
    {
        return ['apiUrl', 'apiKey', 'secretKey'];
    }

    /**
     * Verifies that all required options have been provided.
     *
     * @param array $options
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function assertRequiredOptions(array $options)
    {
        $missing = array_diff_key(array_flip($this->getRequiredOptions()), $options);

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Required option(s) not defined: ' . implode(',', array_keys($missing))
            );
        }
    }

    /**
     * Create uri object instance.
     *
     * @param string $command
     * @param array $options
     *
     * @return \Psr\Http\Message\UriInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function buildUri($command, array $options)
    {
        $baseUrl = $this->apiUrl . '/' . $command;

        $options['format'] = 'json';

        $baseUrl .= '?' . http_build_query($options);

        return $this->getUriFactory()->createUri($baseUrl);
    }

    /**
     * Create request object instance.
     *
     * @param string $command
     * @param string $method
     * @param array $options
     *
     * @return RequestInterface
     *
     * @throws \InvalidArgumentException
     */
    public function createRequest($command, $method, array $options = [])
    {
        $uri = $this->buildUri($command, $options);

        return $this->getMessageFactory()->createRequest($method, $uri);
    }

    /**
     * Send the HTTP request and return the parsed response.
     *
     * @param RequestInterface $request
     *
     * @return mixed
     *
     * @throws \Http\Client\Exception
     * @throws \Exception
     * @throws \RuntimeException
     */
    public function sendRequest(RequestInterface $request)
    {
        $response = $this->getHttpClient()->sendRequest($request);

        return $this->parseResponse($response);
    }

    /**
     * Validate and parse response.
     *
     * @param ResponseInterface $response
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function parseResponse(ResponseInterface $response)
    {
        $readable = $response->getBody()->isReadable();

        if ($readable === false) {
            throw new \Exception('Unable to parse response.');
        }

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Create request and parse response.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return RequestInterface
     *
     * @throws \Exception
     * @throws \Http\Client\Exception
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __call($method, $arguments)
    {
        $method = strtolower($method);

        if ($method !== 'delete' && $method !== 'get' &&
            $method !== 'post'   && $method !== 'put'
        ) {
            throw new \InvalidArgumentException('Unsupported HTTP method specified.');
        }

        if (empty($arguments[0])) {
            throw new \InvalidArgumentException('No resource specified.');
        }

        list ($resource, $parameters) = $arguments;

        return $this->sendRequest(
            $this->createRequest($resource, $method, $parameters)
        );
    }
}
