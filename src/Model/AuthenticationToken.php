<?php

namespace SteppingHat\BackblazeB2\Model;

use DateTime;
use JsonSerializable;

class AuthenticationToken implements JsonSerializable {

    /** @var string */
    protected string $token;

    /** @var string */
    protected string $apiUrl;

    /** @var string */
    protected string $downloadUrl;

    /** @var string */
    protected string $s3ApiUrl;

    /** @var int */
    protected int $recommendedPartSize;

    /** @var int */
    protected int $absoluteMinimumPartSize;

    /** @var array */
    protected array $capabilities;

    /** @var string|null */
    protected ?string $bucketId;

    /** @var DateTime */
    protected $expiry;

    public function __construct(string $token, string $apiUrl, string $downloadUrl, string $s3ApiUrl, int $recommendedPartSize, int $absoluteMinimumPartSize, array $capabilities, ?string $bucketId, ?DateTime $expiry = null) {
        $this->token = $token;
        $this->apiUrl = $apiUrl;
        $this->downloadUrl = $downloadUrl;
        $this->s3ApiUrl = $s3ApiUrl;
        $this->recommendedPartSize = $recommendedPartSize;
        $this->absoluteMinimumPartSize = $absoluteMinimumPartSize;
        $this->capabilities = $capabilities;
        $this->bucketId = $bucketId;
        if($this->expiry === null) {
            $this->expiry = new DateTime('+ 23 hours'); // Set a 23 hour expiry by default
        }
    }

    public function jsonSerialize(): array {
        return [
            'token' => $this->token,
            'apiUrl' => $this->apiUrl,
            'downloadUrl' => $this->downloadUrl,
            's3ApiUrl' => $this->s3ApiUrl,
            'recommendedPartSize' => $this->recommendedPartSize,
            'absoluteMinimumPartSize' => $this->absoluteMinimumPartSize,
            'capabilities' => $this->capabilities,
            'bucketId' => $this->bucketId
        ];
    }

    /**
     * @return string
     */
    public function getToken(): string {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string {
        return $this->apiUrl;
    }

    /**
     * @return string
     */
    public function getDownloadUrl(): string {
        return $this->downloadUrl;
    }

    /**
     * @return string
     */
    public function getS3ApiUrl(): string {
        return $this->s3ApiUrl;
    }

    /**
     * @return int
     */
    public function getRecommendedPartSize(): int {
        return $this->recommendedPartSize;
    }

    /**
     * @return int
     */
    public function getAbsoluteMinimumPartSize(): int {
        return $this->absoluteMinimumPartSize;
    }

    /**
     * @return array
     */
    public function getCapabilities(): array {
        return $this->capabilities;
    }

    /**
     * @param $capability
     * @return bool
     */
    public function hasCapability($capability): bool {
        return in_array($capability, $this->capabilities);
    }

    /**
     * @return string|null
     */
    public function getBucketId(): ?string {
        return $this->bucketId;
    }

    /**
     * @return DateTime
     */
    public function getExpiry(): DateTime {
        return $this->expiry;
    }

    /**
     * @return bool
     */
    public function hasExpired(): bool {
        return $this->expiry < new DateTime();
    }

}