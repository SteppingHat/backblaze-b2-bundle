<?php

namespace SteppingHat\BackblazeB2\Tests\Client;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SteppingHat\BackblazeB2\Client\AbstractClient;
use SteppingHat\BackblazeB2\Client\BackblazeClient;
use SteppingHat\BackblazeB2\Model\AuthenticationToken;
use SteppingHat\BackblazeB2\Model\Bucket;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BackblazeClientTest extends TestCase {

    /** @var BackblazeClient */
    protected $client;

    /** @var MockObject|HttpClientInterface */
    protected $httpClient;

    protected $responseDir;

    protected function setUp(): void {
        $this->responseDir = __DIR__ . '/../Resources/response_data';

        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->client = new BackblazeClient($this->httpClient, '010203040506', 'qwertyuiop', 'asdfghjkl');
    }

    public function testGetAuthentication() {
        $authResponse = $this->createMock(ResponseInterface::class);
        $authResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $authResponse->expects($this->once())
            ->method('toArray')
            ->willReturn($this->getResponseData('b2_authorize_account-success.json'));

        $this->httpClient->method('request')
            ->with('GET', AbstractClient::B2_API_BASE_URL.AbstractClient::B2_API_V1.'/b2_authorize_account')
            ->willReturn($authResponse);

        $reflection = new \ReflectionClass(AbstractClient::class);
        $method = $reflection->getMethod('getAuthenticationToken');
        $method->setAccessible(true);
        $property = $reflection->getProperty('authToken');
        $property->setAccessible(true);

        $method->invoke($this->client);
        $authToken = $property->getValue($this->client);

        $this->assertInstanceOf(AuthenticationToken::class, $authToken);
    }

//    public function testCreateBucket() {
//        $response = $this->createMock(ResponseInterface::class);
//        $response->expects($this->once())
//            ->method('getStatusCode')
//            ->willReturn(200);
//        $response->expects($this->once())
//            ->method('toArray')
//            ->willReturn($this->getResponseData('b2_create_bucket-success.json'));
//
//
//        $this->httpClient->method('request')
//            ->willReturn($response);
//
//        $bucket = $this->client->createBucket('any-name-you-pick', Bucket::TYPE_PRIVATE);
//
//        $this->assertInstanceOf(Bucket::class, $bucket);
//        $this->assertEquals('any-name-you-pick', $bucket->getName());
//    }

    /**
     * Finds and JSON decodes test response data
     * @param string $name
     * @return array
     */
    private function getResponseData(string $name): array {
        $fileName = $this->responseDir.'/'.$name;
        if(!file_exists($fileName)) {
            throw new \RuntimeException("Response data not found");
        }

        return json_decode(file_get_contents($fileName), true);
    }

}