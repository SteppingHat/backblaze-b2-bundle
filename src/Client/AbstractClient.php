<?php

namespace SteppingHat\BackblazeB2\Client;

use Psr\Cache\InvalidArgumentException;
use SteppingHat\BackblazeB2\Exception\B2Exception;
use SteppingHat\BackblazeB2\Exception\MissingCapabilityException;
use SteppingHat\BackblazeB2\Handler\ErrorHandler;
use SteppingHat\BackblazeB2\Model\AuthenticationToken;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractClient {

    const B2_API_BASE_URL = 'https://api.backblazeb2.com';
    const B2_API_V1 = '/b2api/v2';

    protected string $accountId;
    private string $applicationId;
    private string $applicationKey;

    protected ?FilesystemAdapter $cache;

    protected HttpClientInterface $httpClient;

    private ?AuthenticationToken $authToken;

    public function __construct(HttpClientInterface $httpClient, string $accountId, string $applicationId, string $applicationKey, ?string $tokenCacheDirectory = null) {
        $this->httpClient = $httpClient;

        $this->accountId = $accountId;
        $this->applicationId = $applicationId;
        $this->applicationKey = $applicationKey;

        $this->cache = $tokenCacheDirectory ? new FilesystemAdapter('BackblazeB2', 0, $tokenCacheDirectory) : null;

        $this->authToken = null;
    }
    /**
     * Handle the request
     *
     * @param string $method
     * @param string $endpoint
     * @param array|null $data
     * @param array|null $headers
     * @return array
     * @throws B2Exception
     * @throws TransportExceptionInterface
     */
    protected function sendAuthenticatedRequest(string $method, string $endpoint, ?array $data = null, ?array $headers = null): array {
        $auth = $this->getAuthenticationToken();

        $defaultHeaders = [
            'Authorization' => $auth->getToken()
        ];

        $options = [];

        if($data !== null) {
            if($method === 'GET') {
                $options['query'] = $data;
            } elseif($method === 'POST') {
                $options['body'] = json_encode($data);
            }
        }

        $options['headers'] = $headers !== null ? [$headers, ...$defaultHeaders] : $defaultHeaders;

        return $this->sendRequest($auth->getApiUrl(), $method, $endpoint, $options);
    }

    /**
     * Get an authentication token either from a local cache or request a new one if non-existent or expired
     * @return AuthenticationToken
     * @throws B2Exception
     * @throws TransportExceptionInterface
     */
    protected function getAuthenticationToken(): AuthenticationToken {
        if($this->authToken !== null && !$this->authToken->hasExpired()) {
            return $this->authToken;
        }

        if($this->cache !== null) {
            try {
                $token = $this->cache->get('authenticationToken', function (ItemInterface $item) {
                    $token = $this->fetchAuthenticationToken();
                    $item->expiresAt($token->getExpiry());
                    return $token;
                });

                if($token->hasExpired()) {
                    // Expire the cache, and take the loss this time around. Next request will refill our cache.
                    $this->cache->deleteItem('authenticationToken');
                } else {
                    $this->authToken = $token;
                    return $this->authToken;
                }
            } catch (InvalidArgumentException $e) {
                dump($e); die();
                // Do nothing, try fetch using barbaric methods
            }
        }

        $this->authToken = $this->fetchAuthenticationToken();

        return $this->authToken;
    }

    /**
     * @throws B2Exception
     * @throws TransportExceptionInterface
     */
    private function fetchAuthenticationToken(): AuthenticationToken {
        $response = $this->sendRequest(self::B2_API_BASE_URL, 'GET', 'b2_authorize_account', [
            'auth_basic' => implode(':', [$this->applicationId, $this->applicationKey]),
        ]);

        return new AuthenticationToken(
            $response['authorizationToken'],
            $response['apiUrl'],
            $response['downloadUrl'],
            $response['s3ApiUrl'],
            $response['recommendedPartSize'],
            $response['absoluteMinimumPartSize'],
            $response['allowed']['capabilities'],
            $response['allowed']['bucketId']
        );
    }

    /**
     * @param string $capability
     * @throws B2Exception
     * @throws TransportExceptionInterface
     */
    protected function validateCapability(string $capability) {
        if(!$this->getAuthenticationToken()->hasCapability($capability)) {
            throw new MissingCapabilityException($capability);
        }
    }

    /**
     * @param string $baseUrl
     * @param string $method
     * @param string $endpoint
     * @param array|null $options
     * @param bool $asArray
     * @return array|string
     * @throws B2Exception
     * @throws TransportExceptionInterface
     */
    protected function sendRequest(string $baseUrl, string $method, string $endpoint, ?array $options = null, bool $asArray = true) {
        $response = $this->httpClient->request($method, $baseUrl.self::B2_API_V1.'/'.$endpoint, $options);

        if($response->getStatusCode() !== 200) {
            ErrorHandler::handleErrorResponse($response);
        }

        try {
            return $asArray ? $response->toArray(true) : $response->getContent(true);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            throw new TransportException($e);
        }
    }

    /**
     * @param string $url
     * @param string $method
     * @param array|null $options
     * @return ResponseInterface
     * @throws B2Exception
     * @throws TransportExceptionInterface
     */
    protected function sendRawRequest(string $url, string $method, ?array $options = null): ResponseInterface {
        $response = $this->httpClient->request($method, $url, $options);

        if($response->getStatusCode() !== 200) {
            ErrorHandler::handleErrorResponse($response);
        }

        return $response;
    }

}