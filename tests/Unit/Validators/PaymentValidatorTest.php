<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use App\Application\Validators\PaymentValidator;
use App\Application\Exceptions\ValidationException;

class PaymentValidatorTest extends TestCase
{
    private PaymentValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PaymentValidator();
    }

    public function testValidateSuccessfulCashPayment(): void
    {
        $data = [
            'method' => 'cash',
            'amount' => 1000.00,
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateSuccessfulMpesaPayment(): void
    {
        $data = [
            'method' => 'mpesa',
            'amount' => 1000.00,
            'phone' => '+254712345678',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateMissingPaymentMethod(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'amount' => 1000.00,
        ];

        $this->validator->validate($data);
    }

    public function testValidateInvalidPaymentMethod(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'method' => 'bitcoin',
            'amount' => 1000.00,
        ];

        $this->validator->validate($data);
    }

    public function testValidateMissingAmount(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'method' => 'cash',
        ];

        $this->validator->validate($data);
    }

    public function testValidateNegativeAmount(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'method' => 'cash',
            'amount' => -100.00,
        ];

        $this->validator->validate($data);
    }

    public function testValidateZeroAmount(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'method' => 'cash',
            'amount' => 0,
        ];

        $this->validator->validate($data);
    }

    public function testValidateMpesaWithoutPhone(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'method' => 'mpesa',
            'amount' => 1000.00,
        ];

        $this->validator->validate($data);
    }

    public function testValidateMpesaWithInvalidPhone(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'method' => 'mpesa',
            'amount' => 1000.00,
            'phone' => 'invalid-phone',
        ];

        $this->validator->validate($data);
    }

    public function testValidateCardPayment(): void
    {
        $data = [
            'method' => 'card',
            'amount' => 1000.00,
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateBankTransferPayment(): void
    {
        $data = [
            'method' => 'bank_transfer',
            'amount' => 5000.00,
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }
}
