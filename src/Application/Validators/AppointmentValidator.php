<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Application\Exceptions\ValidationException;

class AppointmentValidator
{
    public function validate(array $data): void
    {
        $errors = [];

        if (empty($data['customerId'])) {
            $errors[] = ['field' => 'customerId', 'message' => 'Customer ID is required'];
        } elseif (!is_int($data['customerId']) || $data['customerId'] <= 0) {
            $errors[] = ['field' => 'customerId', 'message' => 'Customer ID must be a positive integer'];
        }

        if (empty($data['serviceId'])) {
            $errors[] = ['field' => 'serviceId', 'message' => 'Service ID is required'];
        } elseif (!is_int($data['serviceId']) || $data['serviceId'] <= 0) {
            $errors[] = ['field' => 'serviceId', 'message' => 'Service ID must be a positive integer'];
        }

        if (empty($data['staffId'])) {
            $errors[] = ['field' => 'staffId', 'message' => 'Staff ID is required'];
        } elseif (!is_int($data['staffId']) || $data['staffId'] <= 0) {
            $errors[] = ['field' => 'staffId', 'message' => 'Staff ID must be a positive integer'];
        }

        if (empty($data['startTime'])) {
            $errors[] = ['field' => 'startTime', 'message' => 'Start time is required'];
        } elseif (!$this->isValidDateTime($data['startTime'])) {
            $errors[] = ['field' => 'startTime', 'message' => 'Start time must be a valid ISO 8601 datetime'];
        } else {
            $startTime = new \DateTime($data['startTime']);
            $now = new \DateTime();

            if ($startTime <= $now->modify('+1 hour')) {
                $errors[] = ['field' => 'startTime', 'message' => 'Start time must be at least 1 hour in the future'];
            }
        }

        if (isset($data['notes']) && !is_string($data['notes'])) {
            $errors[] = ['field' => 'notes', 'message' => 'Notes must be a string'];
        }

        if (!empty($errors)) {
            throw new ValidationException('Appointment validation failed', $errors);
        }
    }

    public function validateUpdate(array $data): void
    {
        $errors = [];

        if (isset($data['service_id']) && (!is_int($data['service_id']) || $data['service_id'] <= 0)) {
            $errors[] = ['field' => 'serviceId', 'message' => 'Service ID must be a positive integer'];
        }

        if (isset($data['staff_id']) && (!is_int($data['staff_id']) || $data['staff_id'] <= 0)) {
            $errors[] = ['field' => 'staffId', 'message' => 'Staff ID must be a positive integer'];
        }

        if (isset($data['start_time'])) {
            if (!$this->isValidDateTime($data['start_time'])) {
                $errors[] = ['field' => 'startTime', 'message' => 'Start time must be a valid ISO 8601 datetime'];
            } else {
                $startTime = new \DateTime($data['start_time']);
                $now = new \DateTime();

                if ($startTime <= $now->modify('+1 hour')) {
                    $errors[] = ['field' => 'startTime', 'message' => 'Start time must be at least 1 hour in the future'];
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Appointment update validation failed', $errors);
        }
    }

    private function isValidDateTime(string $dateTime): bool
    {
        try {
            new \DateTime($dateTime);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
