<?php

namespace SteppingHat\BackblazeB2\Exception;

use Throwable;

class B2Exception extends \Exception {

    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        $message = sprintf('Backblaze B2 responded with an error: "%s"', $message);
        parent::__construct($message, $code, $previous);
    }

}
