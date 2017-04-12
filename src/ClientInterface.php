<?php namespace PCextreme\RgwAdminClient;

use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Message\UriFactory;
use Psr\Http\Message\RequestInterface;

interface ClientInterface
{
    /**
     * Set the UriFactory instance.
     *
     * @param UriFactory $uriFactory
     *
     * @return void
     */
    public function setUriFactory(UriFactory $uriFactory);

    /**
     * Returns the current UriFactory instance.
     *
     * @return UriFactory
     */
    public function getUriFactory();

    /**
     * Set the MessageFactory instance.
     *
     * @param MessageFactory $messageFactory
     *
     * @return void
     */
    public function setMessageFactory(MessageFactory $messageFactory);

    /**
     * Returns the current MessageFactory instance.
     *
     * @return MessageFactory
     */
    public function getMessageFactory();

    /**
     * Set the HttpClient instance.
     *
     * @param HttpClient $httpClient
     *
     * @return void
     */
    public function setHttpClient(HttpClient $httpClient);

    /**
     * Returns the current HttpClient instance.
     *
     * @return PluginClient
     *
     * @throws \RuntimeException
     */
    public function getHttpClient();

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
    public function createRequest($command, $method, array $options = []);

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
    public function sendRequest(RequestInterface $request);
}
