<?php

namespace SteppingHat\BackblazeB2\Model;

use JsonSerializable;

class File implements JsonSerializable {

    /** @var string */
    protected string $id;

    /** @var string  */
    protected string $name;

    /** @var string|null  */
    protected ?string $hash;

    /** @var int|null  */
    protected ?int $size;

    /** @var string|null  */
    protected ?string $type;

    /** @var array|null  */
    protected ?array $info;

    /** @var string|null  */
    protected ?string $bucketId;

    /** @var string|null  */
    protected ?string $action;

    /** @var int|null  */
    protected ?int $uploadTimestamp;

    public function __construct(string $id, string $name, ?string $hash = null, ?int $size = null, ?string $type = null, ?array $info = null, ?string $bucketId = null, ?string $action = null, ?int $uploadTimestamp = null) {
        $this->id = $id;
        $this->name = $name;
        $this->hash = $hash;
        $this->size = $size;
        $this->type = $type;
        $this->info = $info;
        $this->bucketId = $bucketId;
        $this->action = $action;
        $this->uploadTimestamp = $uploadTimestamp;
    }

    /**
     * @param array $response
     * @return File
     */
    public static function constructFromResponse(array $response): File {
        return new File(
            $response['fileId'],
            $response['fileName'],
            $response['contentSha1'],
            $response['contentLength'],
            $response['contentType'],
            $response['fileInfo'],
            $response['bucketId'],
            $response['action'],
            $response['uploadTimestamp']
        );
    }

    public function jsonSerialize() {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'hash' => $this->getHash(),
            'size' => $this->getSize(),
            'type' => $this->getType(),
            'info' => $this->getInfo(),
            'bucketId' => $this->getBucketId(),
            'action' => $this->getAction(),
            'uploadTimestamp' => $this->getUploadTimestamp(),
        ];
    }

    /**
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getHash(): ?string {
        return $this->hash;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int {
        return $this->size;
    }

    /**
     * @return string|null
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return array|null
     */
    public function getInfo(): ?array {
        return $this->info;
    }

    /**
     * @return string|null
     */
    public function getBucketId(): ?string {
        return $this->bucketId;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string {
        return $this->action;
    }

    /**
     * @return int|null
     */
    public function getUploadTimestamp(): ?int {
        return $this->uploadTimestamp;
    }

}