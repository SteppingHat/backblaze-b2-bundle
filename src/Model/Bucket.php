<?php

namespace SteppingHat\BackblazeB2\Model;

use JsonSerializable;

class Bucket implements JsonSerializable {

    const TYPE_PRIVATE = 'allPrivate';
    const TYPE_PUBLIC = 'allPublic';

    /** @var string  */
    protected string $id;

    /** @var string  */
    protected string $name;

    /** @var string  */
    protected string $type;

    public function __construct($id, $name, $type) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
    }

    public static function constructFromResponse(array $response): Bucket {
        return new Bucket($response['bucketId'], $response['bucketName'], $response['bucketType']);
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type
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
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }
}
