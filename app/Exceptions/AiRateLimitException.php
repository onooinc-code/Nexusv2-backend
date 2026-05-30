<?php

namespace App\Exceptions;

use Exception;

class AiRateLimitException extends Exception
{
    public function __construct($message = "AI Provider Rate Limit Exceeded", $code = 429, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
