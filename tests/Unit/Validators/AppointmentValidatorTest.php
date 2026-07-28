<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use App\Application\Validators\AppointmentValidator;
use App\Application\Exceptions\ValidationException;
use DateTime;

class AppointmentValidatorTest extends TestCase
{
    private AppointmentValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AppointmentValidator();
    }

    public function testValidateSuccessfulAppointment(): void
    {
        $data = [
            'customerId' => 1,
            'serviceId' => 2,
            'staffId' => 3,
            'startTime' => (new DateTime('+2 hours'))->format('Y-m-d H:i:s'),
        ];

        $this->validator->validate($data);
        $this->assertTrue(true); // If no exception, validation passed
    }

    public function testValidateMissingCustomerId(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'serviceId' => 2,
            'staffId' => 3,
            'startTime' => (new DateTime('+2 hours'))->format('Y-m-d H:i:s'),
        ];

        $this->validator->validate($data);
    }

    public function testValidateMissingServiceId(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'customerId' => 1,
            'staffId' => 3,
            'startTime' => (new DateTime('+2 hours'))->format('Y-m-d H:i:s'),
        ];

        $this->validator->validate($data);
    }

    public function testValidateMissingStaffId(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'customerId' => 1,
            'serviceId' => 2,
            'startTime' => (new DateTime('+2 hours'))->format('Y-m-d H:i:s'),
        ];

        $this->validator->validate($data);
    }

    public function testValidateMissingStartTime(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'customerId' => 1,
            'serviceId' => 2,
            'staffId' => 3,
        ];

        $this->validator->validate($data);
    }

    public function testValidateInvalidStartTimeInPast(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'customerId' => 1,
            'serviceId' => 2,
            'staffId' => 3,
            'startTime' => (new DateTime('-1 hour'))->format('Y-m-d H:i:s'),
        ];

        $this->validator->validate($data);
    }

    public function testValidateInvalidStartTimeNotEnoughLeadTime(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'customerId' => 1,
            'serviceId' => 2,
            'staffId' => 3,
            'startTime' => (new DateTime('+30 minutes'))->format('Y-m-d H:i:s'),
        ];

        $this->validator->validate($data);
    }

    public function testValidateInvalidCustomerId(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'customerId' => 'invalid',
            'serviceId' => 2,
            'staffId' => 3,
            'startTime' => (new DateTime('+2 hours'))->format('Y-m-d H:i:s'),
        ];

        $this->validator->validate($data);
    }

    public function testValidateUpdateWithPartialData(): void
    {
        $data = [
            'staffId' => 5,
        ];

        $this->validator->validateUpdate($data);
        $this->assertTrue(true); // Partial updates allowed
    }

    public function testValidateUpdateWithInvalidData(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'startTime' => (new DateTime('-1 hour'))->format('Y-m-d H:i:s'),
        ];

        $this->validator->validateUpdate($data);
    }
}
