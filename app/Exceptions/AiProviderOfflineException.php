<?php

namespace App\Exceptions;

use Exception;

class AiProviderOfflineException extends Exception
{
    public function __construct($message = "AI Provider Offline or Unreachable", $code = 503, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
