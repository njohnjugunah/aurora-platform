<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use App\Application\Validators\CustomerValidator;
use App\Application\Exceptions\ValidationException;

class CustomerValidatorTest extends TestCase
{
    private CustomerValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CustomerValidator();
    }

    public function testValidateSuccessfulCustomer(): void
    {
        $data = [
            'name' => 'John Doe',
            'phone' => '+254712345678',
            'email' => 'john@example.com',
            'dateOfBirth' => '1990-01-15',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateMissingName(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'phone' => '+254712345678',
            'email' => 'john@example.com',
        ];

        $this->validator->validate($data);
    }

    public function testValidateNameTooShort(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'J',
            'phone' => '+254712345678',
            'email' => 'john@example.com',
        ];

        $this->validator->validate($data);
    }

    public function testValidateNameTooLong(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => str_repeat('a', 101),
            'phone' => '+254712345678',
            'email' => 'john@example.com',
        ];

        $this->validator->validate($data);
    }

    public function testValidateMissingPhone(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->validator->validate($data);
    }

    public function testValidateInvalidPhoneFormat(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'John Doe',
            'phone' => '1234567890', // Not E.164 format
            'email' => 'john@example.com',
        ];

        $this->validator->validate($data);
    }

    public function testValidateMissingEmail(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'John Doe',
            'phone' => '+254712345678',
        ];

        $this->validator->validate($data);
    }

    public function testValidateInvalidEmailFormat(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'John Doe',
            'phone' => '+254712345678',
            'email' => 'invalid-email',
        ];

        $this->validator->validate($data);
    }

    public function testValidateInvalidDateOfBirth(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'John Doe',
            'phone' => '+254712345678',
            'email' => 'john@example.com',
            'dateOfBirth' => 'invalid-date',
        ];

        $this->validator->validate($data);
    }

    public function testValidateUpdatePartialData(): void
    {
        $data = [
            'name' => 'Jane Doe',
        ];

        $this->validator->validateUpdate($data);
        $this->assertTrue(true);
    }
}
