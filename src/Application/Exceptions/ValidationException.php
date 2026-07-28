<?php

namespace App\Application\Exceptions;

use Exception;

class ValidationException extends Exception
{
    public function __construct(
        string $message,
        public array $errors = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, 422, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
