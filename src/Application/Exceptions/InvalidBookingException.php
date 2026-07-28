<?php

namespace App\Application\Exceptions;

use Exception;

class InvalidBookingException extends Exception
{
    public function __construct(string $message = '', ?Exception $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
}
