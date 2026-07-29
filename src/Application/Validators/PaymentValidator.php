<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Application\Exceptions\ValidationException;

class PaymentValidator
{
    public function validate(array $data): void
    {
        $errors = [];

        if (empty($data['method']) || !is_string($data['method'])) {
            $errors[] = ['field' => 'method', 'message' => 'Payment method is required'];
        } elseif (!in_array($data['method'], ['cash', 'mpesa', 'card', 'bank_transfer'])) {
            $msg = 'Invalid payment method. Allowed: cash, mpesa, card, bank_transfer';
            $errors[] = ['field' => 'method', 'message' => $msg];
        }

        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors[] = ['field' => 'amount', 'message' => 'Amount must be a positive number'];
        }

        if ($data['method'] === 'mpesa') {
            if (empty($data['phone']) || !is_string($data['phone'])) {
                $errors[] = ['field' => 'phone', 'message' => 'Phone is required for M-Pesa payments'];
            } elseif (!$this->isValidPhoneNumber($data['phone'])) {
                $errors[] = ['field' => 'phone', 'message' => 'Invalid phone number format'];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Payment validation failed', $errors);
        }
    }

    private function isValidPhoneNumber(string $phone): bool
    {
        return (bool) preg_match('/^\+?[1-9]\d{1,14}$/', str_replace([' ', '-', '(', ')'], '', $phone));
    }
}
