<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use App\Application\Validators\LoginValidator;
use App\Application\Exceptions\ValidationException;

class LoginValidatorTest extends TestCase
{
    private LoginValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LoginValidator();
    }

    public function testValidateSuccessfulLogin(): void
    {
        $data = [
            'email' => 'user@example.com',
            'password' => 'password123',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateMissingEmail(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'password' => 'password123',
        ];

        $this->validator->validate($data);
    }

    public function testValidateEmptyEmail(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'email' => '',
            'password' => 'password123',
        ];

        $this->validator->validate($data);
    }

    public function testValidateInvalidEmailFormat(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $this->validator->validate($data);
    }

    public function testValidateMissingPassword(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'email' => 'user@example.com',
        ];

        $this->validator->validate($data);
    }

    public function testValidateEmptyPassword(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'email' => 'user@example.com',
            'password' => '',
        ];

        $this->validator->validate($data);
    }

    public function testValidatePasswordTooShort(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'email' => 'user@example.com',
            'password' => 'pass12',
        ];

        $this->validator->validate($data);
    }

    public function testValidatePasswordMinimumLength(): void
    {
        $data = [
            'email' => 'user@example.com',
            'password' => 'password',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateLongPassword(): void
    {
        $data = [
            'email' => 'user@example.com',
            'password' => str_repeat('a', 128),
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateEmailWithSpecialCharacters(): void
    {
        $data = [
            'email' => 'user+tag@example.co.uk',
            'password' => 'password123',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidatePasswordWithSpecialCharacters(): void
    {
        $data = [
            'email' => 'user@example.com',
            'password' => 'P@ssw0rd!#$%',
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }
}
