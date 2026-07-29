<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use App\Application\Validators\InventoryValidator;
use App\Application\Exceptions\ValidationException;

class InventoryValidatorTest extends TestCase
{
    private InventoryValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new InventoryValidator();
    }

    public function testValidateSuccessfulPurchase(): void
    {
        $data = [
            'quantity' => 50,
            'type' => 'purchase',
        ];

        $this->validator->validateAdjustment($data);
        $this->assertTrue(true);
    }

    public function testValidateSuccessfulAdjustment(): void
    {
        $data = [
            'quantity' => 10,
            'type' => 'adjustment',
        ];

        $this->validator->validateAdjustment($data);
        $this->assertTrue(true);
    }

    public function testValidateSuccessfulReturn(): void
    {
        $data = [
            'quantity' => 5,
            'type' => 'return',
        ];

        $this->validator->validateAdjustment($data);
        $this->assertTrue(true);
    }

    public function testValidateSuccessfulDamage(): void
    {
        $data = [
            'quantity' => 3,
            'type' => 'damage',
        ];

        $this->validator->validateAdjustment($data);
        $this->assertTrue(true);
    }

    public function testValidateMissingQuantity(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'type' => 'purchase',
        ];

        $this->validator->validateAdjustment($data);
    }

    public function testValidateInvalidQuantityType(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'quantity' => 'fifty',
            'type' => 'purchase',
        ];

        $this->validator->validateAdjustment($data);
    }

    public function testValidateNegativeQuantity(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'quantity' => -10,
            'type' => 'purchase',
        ];

        $this->validator->validateAdjustment($data);
    }

    public function testValidateZeroQuantity(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'quantity' => 0,
            'type' => 'purchase',
        ];

        $this->validator->validateAdjustment($data);
    }

    public function testValidateMissingAdjustmentType(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'quantity' => 50,
        ];

        $this->validator->validateAdjustment($data);
    }

    public function testValidateInvalidAdjustmentType(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'quantity' => 50,
            'type' => 'theft',
        ];

        $this->validator->validateAdjustment($data);
    }

    public function testValidateLargeQuantity(): void
    {
        $data = [
            'quantity' => 10000,
            'type' => 'purchase',
        ];

        $this->validator->validateAdjustment($data);
        $this->assertTrue(true);
    }
}
