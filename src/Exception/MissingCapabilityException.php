<?php

namespace SteppingHat\BackblazeB2\Exception;

use Throwable;

class MissingCapabilityException extends \RuntimeException {

    public function __construct($capability = "", $code = 0, Throwable $previous = null) {
        $message = sprintf('The token is missing the "%s" capability', $capability);
        parent::__construct($message, $code, $previous);
    }

}