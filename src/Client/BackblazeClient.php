<?php

namespace SteppingHat\BackblazeB2\Client;

use DateTime;
use SteppingHat\BackblazeB2\Exception\ValidationException;
use SteppingHat\BackblazeB2\Exception\B2Exception;
use SteppingHat\BackblazeB2\Model\Bucket;
use SteppingHat\BackblazeB2\Model\File;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class BackblazeClient extends AbstractClient {

    /**
     * Create a bucket
     * Token must have the "writeBuckets" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_create_bucket.html
     *
     * @param string $name                  The name of the bucket
     * @param string $type                  The type of the bucket
     * @return Bucket
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function createBucket(string $name, string $type): Bucket {
        $this->validateCapability('writeBuckets');

        if(!in_array($type, [Bucket::TYPE_PUBLIC, Bucket::TYPE_PRIVATE])) {
            throw new ValidationException(sprintf('The bucket type must be either "%s" or "%s". Got "%s" instead.', Bucket::TYPE_PRIVATE, Bucket::TYPE_PUBLIC, $type));
        }

        $response = $this->sendAuthenticatedRequest('POST', 'b2_create_bucket', [
            'accountId'  => $this->accountId,
            'bucketName' => $name,
            'bucketType' => $type
        ]);

        return Bucket::constructFromResponse($response);
    }

    /**
     * Lists all buckets on the account
     * Token must have the "listBuckets" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_list_buckets.html
     *
     * @return Bucket[]
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function listBuckets(): array {
        $this->validateCapability('listBuckets');

        $response = $this->sendAuthenticatedRequest('POST', 'b2_list_buckets', [
            'accountId' => $this->accountId
        ]);

        /** @var Bucket[] $buckets */
        $buckets = [];
        foreach($response['buckets'] as $bucket) {
            $buckets[] = Bucket::constructFromResponse($bucket);
        }

        return $buckets;
    }

    /**
     * Update the bucket privacy type
     * Token must have the "writeBuckets" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_update_bucket.html
     *
     * @param Bucket $bucket
     * @param string $type
     * @return Bucket
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function updateBucket(Bucket $bucket, string $type): Bucket {
        return $this->updateBucketById($bucket->getId(), $type);
    }

    /**
     * Update the bucket privacy type
     * Token must have the "writeBuckets" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_update_bucket.html
     *
     * @param string $bucketId
     * @param string $type
     * @return Bucket
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function updateBucketById(string $bucketId, string $type): Bucket {
        $this->validateCapability('writeBuckets');

        if(!in_array($type, [Bucket::TYPE_PUBLIC, Bucket::TYPE_PRIVATE])) {
            throw new ValidationException(sprintf('The bucket type must be either "%s" or "%s". Got "%s" instead.', Bucket::TYPE_PRIVATE, Bucket::TYPE_PUBLIC, $type));
        }

        $response = $this->sendAuthenticatedRequest('POST', 'b2_update_bucket', [
            'accountId' => $this->accountId,
            'bucketId' => $bucketId,
            'bucketType' => $type
        ]);

        return Bucket::constructFromResponse($response);
    }

    /**
     * Deletes a bucket
     * The token must have the "deleteBuckets" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_delete_bucket.html
     *
     * @param Bucket $bucket
     * @return bool
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function deleteBucket(Bucket $bucket) {
        return $this->deleteBucketById($bucket->getId());
    }

    /**
     * Deletes a bucket
     * The token must have the "deleteBuckets" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_delete_bucket.html
     *
     * @param string $bucketId
     * @return bool
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function deleteBucketById(string $bucketId) {
        $this->validateCapability('deleteBuckets');

        $this->sendAuthenticatedRequest('POST', 'b2_delete_bucket', [
            'accountId' => $this->accountId,
            'bucketId' => $bucketId
        ]);

        return true;
    }

    /**
     * Lists the files in a bucket
     * Token must have the "listFiles" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_list_file_names.html
     *
     * @param Bucket|null $bucket           The bucket containing the files
     * @param string|null $filename         Optional filename to only return a single result if found
     * @param string|null $prefix           Optional search prefix (see docs)
     * @param string|null $delimiter        Optional search delimiter (see docs)
     * @return File[]
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function listFiles(?Bucket $bucket, ?string $filename = null, ?string $prefix = '', ?string $delimiter = null): array {
        $this->validateCapability('listFiles');

        $limit = 1000;
        $next = null;
        if($filename !== null) {
            $limit = 1;
            $next = $filename;
        }

        /** @var File[] $files */
        $files = [];

        $params = [
            'maxFileCount' => $limit
        ];
        if($bucket !== null) $params['bucketId'] = $bucket->getId();
        if($next !== null) $params['startFileName'] = $next;
        if($prefix !== null) $params['prefix'] = $prefix;
        if($delimiter !== null) $params['delimiter'] = $delimiter;

        do {
            $anotherRequest = false;

            $response = $this->sendAuthenticatedRequest('POST', 'b2_list_file_names', $params);

            foreach($response['files'] as $file) {
                if($filename === null || ($filename === $file['fileName'])) {
                    $files[] = File::constructFromResponse($file);
                }
            }

            if(!($filename || $response['nextFileName'] === null)) {
                $params['startFileName'] = $response['nextFileName'];
                $anotherRequest = true;
            }

        } while($anotherRequest == true);

        return $files;
    }

    /**
     * Query to see if a file exists given the criteria (same call as BackblazeClient#listFiles)
     *
     * @param Bucket|null $bucket           The bucket containing the files
     * @param string|null $filename         Optionally provide a filename to only return a single result if found
     * @param string|null $prefix
     * @param string|null $delimiter
     * @return bool
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function fileExists(?Bucket $bucket, ?string $filename = null, ?string $prefix = '', ?string $delimiter = null): bool {
        return !empty($this->listFiles($bucket, $filename, $prefix, $delimiter));
    }

    /**
     * Return information about a single file
     * Token must have the "readFiles" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_get_file_info.html
     *
     * @param File $file
     * @return File
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function getFileInfo(File $file): File {
        return $this->getFileInfoById($file->getId());
    }

    /**
     * Return information about a single file
     * Token must have the "readFiles" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_get_file_info.html
     *
     * @param string $fileId
     * @return File
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function getFileInfoById(string $fileId): File {
        $this->validateCapability('readFiles');

        $response = $this->sendAuthenticatedRequest('POST', 'b2_get_file_info', [
            'fileId' => $fileId
        ]);

        return File::constructFromResponse($response);
    }

    /**
     * Delete a file
     * Token must have the "deleteFiles" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_delete_file_version.html
     *
     * @param File $file
     * @param bool|null                     Must be specified and set to true if deleting a file version protected by File Lock governance mode retention settings. (Token requires the "bypassGovernance" capability)
     * @return array{fileId: string, fileName: string}
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function deleteFile(File $file, ?bool $bypassGovernance = null): array {
        $this->validateCapability('deleteFiles');

        $params = [];
        if($bypassGovernance !== null) {
            $this->validateCapability('bypassGovernance');
            $params['bypassGovernance'] = $bypassGovernance;
        }

        return $this->sendAuthenticatedRequest('POST', 'b2_delete_file_version', [
            ...$params,
            'fileName' => $file->getName(),
            'fileId' => $file->getId()
        ]);
    }

    /**
     * Upload a file to a bucket
     * Token must have the "writeFiles" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_upload_file.html
     *
     * @param Bucket $bucket                The bucket to upload into
     * @param string|resource $data         The file data, either as a resource or as a string
     * @param string $filename              The file name/path
     * @param DateTime|null $lastModified   Optionally specify when the file was last modified
     * @param string|null $contentType      Optionally override the file type instead of letting Backblaze B2 guess it
     * @param array $customHeaders          Optionally specify custom headers to be paired with the request (which override any generated headers if they already exist)
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function upload(Bucket $bucket, $data, string $filename, ?DateTime $lastModified = null, ?string $contentType = null, array $customHeaders = []) {
        $this->validateCapability('writeFiles');

        // Clean the filename
        if(substr($filename, 0, 1) === '/') {
            $filename = ltrim($filename, '/');
        }

        // Get the upload URL
        $response = $this->sendAuthenticatedRequest('POST', 'b2_get_upload_url', [
            'bucketId' => $bucket->getId()
        ]);

        $endpoint = $response['uploadUrl'];
        $token = $response['authorizationToken'];

        // Get the hash and size
        if(is_resource($data)) {
            $context = hash_init('sha1');
            hash_update_stream($context, $data);
            $hash = hash_final($context);

            $size = fstat($data)['size'];

            rewind($data);

            // TODO: Make HttpClient still send the Content-Length header first when streaming resources into the body
            $data = stream_get_contents($data);
        } else {
            $hash = sha1($data);
            $size = strlen($data);
        }

        if($lastModified !== null) {
            $lastModified = round((int)$lastModified->format('Uu') / 1000);
        } else {
            $lastModified = round(microtime(true) * 1000);
        }

        $contentType = $contentType ?? 'b2/x-auto';

        $response = $this->sendRawRequest($endpoint, 'POST', [
            'headers' => [
                'Authorization'                         => $token,
                'Content-Type'                          => $contentType,
                'Content-Length'                        => $size,
                'X-Bz-File-Name'                        => $filename,
                'X-Bz-Content-Sha1'                     => $hash,
                'X-Bz-Info-src_last_modified_millis'    => $lastModified,
                ...$customHeaders
            ],
            'body' => $data,
            'timeout' => -1
        ]);

        try {
            $response = $response->toArray(true);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            throw new TransportException($e);
        }

        return File::constructFromResponse($response);
    }

    /**
     * Downloads one file from B2
     * Token must have the "readFiles" capability
     *
     * @link https://www.backblaze.com/b2/docs/b2_download_file_by_id.html
     *
     * @param File $file
     * @param resource $stream              An optional stream to write the contents directly into
     * @return string|null                  The raw file contents (or null if writing into a stream)
     * @throws B2Exception                  If the B2 server replied with an error
     * @throws TransportExceptionInterface  If there was an issue with the request
     */
    public function download(File $file, &$stream = null): ?string {
        $this->validateCapability('readFiles');

        $authToken = $this->getAuthenticationToken();

        $url = $authToken->getDownloadUrl().self::B2_API_V1.'/b2_download_file_by_id';

        $response = $this->sendRawRequest($url, 'GET', [
            'headers' => [
                'Authorization' => $authToken->getToken()
            ],
            'query' => ['fileId' => $file->getId()]
        ]);

        try {
            if(is_resource($stream)) {
                foreach($this->httpClient->stream($response) as $chunk) {
                    fwrite($stream, $chunk->getContent());
                }
                rewind($stream);
                return null;
            } else {
                return $response->getContent(true);
            }
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            throw new TransportException($e);
        }
    }

}