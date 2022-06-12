<?php

namespace SteppingHat\BackblazeB2\Handler;

use SteppingHat\BackblazeB2\Exception\B2Exception;
use SteppingHat\BackblazeB2\Exception\BadAuthTokenException;
use SteppingHat\BackblazeB2\Exception\BadJsonException;
use SteppingHat\BackblazeB2\Exception\BadRequestException;
use SteppingHat\BackblazeB2\Exception\BadValueException;
use SteppingHat\BackblazeB2\Exception\BucketAlreadyExistsException;
use SteppingHat\BackblazeB2\Exception\BucketNotEmptyException;
use SteppingHat\BackblazeB2\Exception\FileNotPresentException;
use SteppingHat\BackblazeB2\Exception\NotFoundException;
use SteppingHat\BackblazeB2\Exception\UnauthorizedAccessException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ErrorHandler {

    protected static array $classMappings = [
        'bad_auth_token'                 => BadAuthTokenException::class,
        'bad_json'                       => BadJsonException::class,
        'bad_request'                    => BadRequestException::class,
        'bad_value'                      => BadValueException::class,
        'duplicate_bucket_name'          => BucketAlreadyExistsException::class,
        'not_found'                      => NotFoundException::class,
        'file_not_present'               => FileNotPresentException::class,
        'cannot_delete_non_empty_bucket' => BucketNotEmptyException::class,
        'unauthorized'                   => UnauthorizedAccessException::class,
    ];

    /**
     * Try and see if we can get a more specific exception
     * @param ResponseInterface $response
     * @throws B2Exception
     */
    public static function handleErrorResponse(ResponseInterface $response) {
        try {
            $responseData = $response->toArray(false);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            throw new TransportException($e);
        }

        if(array_key_exists($responseData['code'], self::$classMappings)) {
            $exceptionClass = self::$classMappings[$responseData['code']];
        } else {
            // We don't have an exception mapped to this response error, throw generic exception
            $exceptionClass = B2Exception::class;
        }

        throw new $exceptionClass($responseData['code'].': '.$responseData['message']);
    }

}