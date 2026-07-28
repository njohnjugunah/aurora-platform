<?php

namespace App\Application\Exceptions;

use Exception;

class AppointmentConflictException extends Exception
{
    public function __construct(string $message = '', ?Exception $previous = null)
    {
        parent::__construct($message, 409, $previous);
    }
}
