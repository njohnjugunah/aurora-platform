<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Application\Exceptions\ValidationException;

class CustomerValidator
{
    public function validate(array $data): void
    {
        $errors = [];

        if (empty($data['name']) || !is_string($data['name'])) {
            $errors[] = ['field' => 'name', 'message' => 'Name is required and must be a string'];
        } elseif (strlen($data['name']) < 2 || strlen($data['name']) > 100) {
            $errors[] = ['field' => 'name', 'message' => 'Name must be between 2 and 100 characters'];
        }

        if (empty($data['phone']) || !is_string($data['phone'])) {
            $errors[] = ['field' => 'phone', 'message' => 'Phone is required and must be a string'];
        } elseif (!$this->isValidPhoneNumber($data['phone'])) {
            $errors[] = ['field' => 'phone', 'message' => 'Phone must be a valid phone number'];
        }

        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['field' => 'email', 'message' => 'Email must be a valid email address'];
            }
        }

        if (isset($data['dateOfBirth']) && !empty($data['dateOfBirth'])) {
            if (!$this->isValidDate($data['dateOfBirth'])) {
                $errors[] = ['field' => 'dateOfBirth', 'message' => 'Date of birth must be a valid date (YYYY-MM-DD)'];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Customer validation failed', $errors);
        }
    }

    public function validateUpdate(array $data): void
    {
        $errors = [];

        if (isset($data['name'])) {
            if (!is_string($data['name']) || strlen($data['name']) < 2 || strlen($data['name']) > 100) {
                $errors[] = ['field' => 'name', 'message' => 'Name must be between 2 and 100 characters'];
            }
        }

        if (isset($data['phone'])) {
            if (!is_string($data['phone']) || !$this->isValidPhoneNumber($data['phone'])) {
                $errors[] = ['field' => 'phone', 'message' => 'Phone must be a valid phone number'];
            }
        }

        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['field' => 'email', 'message' => 'Email must be a valid email address'];
            }
        }

        if (isset($data['dateOfBirth']) && !empty($data['dateOfBirth'])) {
            if (!$this->isValidDate($data['dateOfBirth'])) {
                $errors[] = ['field' => 'dateOfBirth', 'message' => 'Date of birth must be a valid date (YYYY-MM-DD)'];
            }
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], ['active', 'inactive'])) {
                $errors[] = ['field' => 'status', 'message' => 'Status must be either active or inactive'];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Customer update validation failed', $errors);
        }
    }

    private function isValidPhoneNumber(string $phone): bool
    {
        return (bool) preg_match('/^\+?[1-9]\d{1,14}$/', str_replace([' ', '-', '(', ')'], '', $phone));
    }

    private function isValidDate(string $date): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) !== false;
    }
}
