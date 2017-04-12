<?php

namespace PCextreme\RgwAdminClient\Authentication;

use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;

class SignatureV2 implements Authentication
{
    /**
     * @var array
     */
    private $signableHeaders = array('Content-MD5', 'Content-Type');

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @param string $apiKey
     * @param string $secretKey
     */
    public function __construct($apiKey, $secretKey)
    {
        $this->apiKey = $apiKey;

        $this->secretKey = $secretKey;
    }

    /**
     * Authenticates a request.
     *
     * @param RequestInterface $request
     *
     * @return RequestInterface
     */
    public function authenticate(RequestInterface $request)
    {
        $request = $request->withHeader('Date', gmdate(\DateTime::RFC2822));

        $signed = $this->signString($this->createCanonicalizedString($request), $this->secretKey);

        $request = $request->withHeader('Authorization', 'AWS ' . $this->apiKey . ':' . $signed);

        return $request;
    }

    /**
     * Sign the provided string with the secret key of the user.
     *
     * @param string $string
     * @param string $secretKey
     *
     * @return string
     */
    public function signString($string, $secretKey)
    {
        return base64_encode(
            hash_hmac('sha1', $string, $secretKey, true)
        );
    }

    /**
     * @param RequestInterface $request
     * @param null $expires
     *
     * @return string
     */
    public function createCanonicalizedString(RequestInterface $request, $expires = null)
    {
        $buffer = $request->getMethod() . "\n";

        // Add the interesting headers
        foreach ($this->signableHeaders as $header) {
            $buffer .= (string) $request->getHeaderLine($header) . "\n";
        }

        // Choose dates from left to right based on what's set
        $date = $expires ?: (string) $request->getHeaderLine('date');

        $buffer .= "{$date}\n"
            . $this->createCanonicalizedAmzHeaders($request)
            . $this->createCanonicalizedResource($request);

        return $buffer;
    }

    /**
     * Create a canonicalized AmzHeaders string for a signature.
     *
     * @param RequestInterface $request Request from which to gather headers
     *
     * @return string Returns canonicalized AMZ headers.
     */
    private function createCanonicalizedAmzHeaders(RequestInterface $request)
    {
        $headers = array();
        foreach ($request->getHeaders() as $name => $header) {
            $name = strtolower($name);
            if (strpos($name, 'x-amz-') === 0) {
                $value = trim((string) $header);
                if ($value || $value === '0') {
                    $headers[$name] = $name . ':' . $value;
                }
            }
        }

        if (!$headers) {
            return '';
        }

        ksort($headers);

        return implode("\n", $headers) . "\n";
    }

    /**
     * Create a canonicalized resource for a request.
     *
     * @param RequestInterface $request Request for the resource
     *
     * @return string
     */
    private function createCanonicalizedResource(RequestInterface $request)
    {
        return $request->getUri()->getPath();
    }
}
