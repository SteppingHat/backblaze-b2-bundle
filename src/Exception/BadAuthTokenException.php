<?php

namespace SteppingHat\BackblazeB2\Exception;

use Throwable;

class BadAuthTokenException extends B2Exception {

    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        $message = !empty($message) ? $message : "Bad auth token";
        parent::__construct($message, $code, $previous);
    }

}
