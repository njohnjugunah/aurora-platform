<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Application\Exceptions\ValidationException;

class LoginValidator
{
    public function validate(array $data): void
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'][] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            $errors['password'][] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters';
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }
    }
}
