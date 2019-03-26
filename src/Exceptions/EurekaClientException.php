<?php

namespace Eureka\Exceptions;

use Exception;
use Throwable;

class EurekaClientException extends Exception
{
    public function __construct($message = "", $code = -1, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}