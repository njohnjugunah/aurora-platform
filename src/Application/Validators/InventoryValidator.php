<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Application\Exceptions\ValidationException;

class InventoryValidator
{
    public function validate(array $data): void
    {
        $errors = [];

        if (empty($data['name']) || !is_string($data['name'])) {
            $errors[] = ['field' => 'name', 'message' => 'Product name is required'];
        }

        if (empty($data['sku']) || !is_string($data['sku'])) {
            $errors[] = ['field' => 'sku', 'message' => 'SKU is required'];
        }

        if (!isset($data['quantity']) || !is_int($data['quantity']) || $data['quantity'] < 0) {
            $errors[] = ['field' => 'quantity', 'message' => 'Quantity must be a non-negative integer'];
        }

        if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            $errors[] = ['field' => 'price', 'message' => 'Price must be a non-negative number'];
        }

        if (!empty($errors)) {
            throw new ValidationException('Inventory validation failed', $errors);
        }
    }

    public function validateAdjustment(array $data): void
    {
        $errors = [];

        if (empty($data['quantity']) || !is_int($data['quantity'])) {
            $errors[] = ['field' => 'quantity', 'message' => 'Quantity must be an integer'];
        }

        if (empty($data['type']) || !in_array($data['type'], ['purchase', 'adjustment', 'return', 'damage'])) {
            $errors[] = ['field' => 'type', 'message' => 'Valid adjustment type is required'];
        }

        if (!empty($errors)) {
            throw new ValidationException('Stock adjustment validation failed', $errors);
        }
    }
}
