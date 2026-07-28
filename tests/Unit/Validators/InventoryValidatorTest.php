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
            'adjustmentType' => 'purchase',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateSuccessfulAdjustment(): void
    {
        $data = [
            'quantity' => 10,
            'adjustmentType' => 'adjustment',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateSuccessfulReturn(): void
    {
        $data = [
            'quantity' => 5,
            'adjustmentType' => 'return',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateSuccessfulDamage(): void
    {
        $data = [
            'quantity' => 3,
            'adjustmentType' => 'damage',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateMissingQuantity(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'adjustmentType' => 'purchase',
        ];

        $this->validator->validate($data);
    }

    public function testValidateInvalidQuantityType(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'quantity' => 'fifty',
            'adjustmentType' => 'purchase',
        ];

        $this->validator->validate($data);
    }

    public function testValidateNegativeQuantity(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'quantity' => -10,
            'adjustmentType' => 'purchase',
        ];

        $this->validator->validate($data);
    }

    public function testValidateZeroQuantity(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'quantity' => 0,
            'adjustmentType' => 'purchase',
        ];

        $this->validator->validate($data);
    }

    public function testValidateMissingAdjustmentType(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'quantity' => 50,
        ];

        $this->validator->validate($data);
    }

    public function testValidateInvalidAdjustmentType(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'quantity' => 50,
            'adjustmentType' => 'theft',
        ];

        $this->validator->validate($data);
    }

    public function testValidateLargeQuantity(): void
    {
        $data = [
            'quantity' => 10000,
            'adjustmentType' => 'purchase',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }
}
