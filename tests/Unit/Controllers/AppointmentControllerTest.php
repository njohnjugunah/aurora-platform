<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\AppointmentController;
use App\Application\Services\BookingService;
use App\Application\Services\AvailabilityService;
use App\Application\Validators\AppointmentValidator;
use App\Application\Exceptions\ValidationException;
use App\Application\Exceptions\InvalidBookingException;
use App\Domain\Repositories\AppointmentRepository;
use Psr\Log\LoggerInterface;

class AppointmentControllerTest extends TestCase
{
    private AppointmentController $controller;
    private $appointmentRepository;
    private $bookingService;
    private $validator;
    private $logger;

    protected function setUp(): void
    {
        $this->appointmentRepository = $this->createMock(AppointmentRepository::class);
        $this->bookingService = $this->createMock(BookingService::class);
        $this->validator = $this->createMock(AppointmentValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new AppointmentController(
            $this->appointmentRepository,
            $this->bookingService,
            $this->validator,
            $this->logger
        );
    }

    public function testListAppointments(): void
    {
        $query = [
            'page' => 1,
            'limit' => 50,
            'date' => '2026-08-15',
            'staff_id' => 1,
        ];

        $mockAppointments = [
            ['id' => 1, 'customer_id' => 1, 'staff_id' => 1],
            ['id' => 2, 'customer_id' => 2, 'staff_id' => 1],
        ];

        $this->appointmentRepository->method('findPaginated')->willReturn([
            'appointments' => $mockAppointments,
            'total' => 2,
        ]);

        $result = $this->controller->list($query);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
    }

    public function testGetAppointment(): void
    {
        $appointmentId = 1;
        $mockAppointment = [
            'id' => 1,
            'customer_id' => 1,
            'staff_id' => 1,
            'start_time' => '2026-08-15 10:00:00',
        ];

        $this->appointmentRepository->method('findById')->willReturn($mockAppointment);

        $result = $this->controller->get($appointmentId);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
        $this->assertEquals($mockAppointment, $result['data']);
    }

    public function testGetAppointmentNotFound(): void
    {
        $appointmentId = 999;

        $this->appointmentRepository->method('findById')->willReturn(null);

        $result = $this->controller->get($appointmentId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('NOT_FOUND', $result['error']['code']);
    }

    public function testCreateAppointmentSuccess(): void
    {
        $request = [
            'customerId' => 1,
            'serviceId' => 2,
            'staffId' => 3,
            'startTime' => '2026-08-15 10:00:00',
        ];

        $this->validator->method('validate'); // Returns void, no return value
        $this->bookingService->method('bookAppointment')->willReturn(42);
        $this->appointmentRepository->method('findById')->willReturn([
            'id' => 42,
            'customer_id' => 1,
            'service_id' => 2,
            'staff_id' => 3,
        ]);

        $result = $this->controller->create($request);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']);
    }

    public function testCreateAppointmentValidationError(): void
    {
        $request = [
            'customerId' => null,
            'serviceId' => 2,
            'staffId' => 3,
            'startTime' => '2026-08-15 10:00:00',
        ];

        $this->validator->method('validate')
            ->will($this->throwException(new ValidationException('Validation failed', ['customerId' => ['is required']])));

        $result = $this->controller->create($request);

        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['success']);
        $this->assertEquals('VALIDATION_ERROR', $result['error']['code']);
    }

    public function testUpdateAppointment(): void
    {
        $appointmentId = 1;
        $request = [
            'staffId' => 4,
            'notes' => 'Updated notes',
        ];

        $existingAppointment = [
            'id' => 1,
            'customer_id' => 1,
            'service_id' => 2,
            'staff_id' => 3,
        ];

        $this->appointmentRepository->method('findById')->willReturn($existingAppointment);
        $this->validator->method('validateUpdate'); // Returns void
        $this->appointmentRepository->method('update')->willReturn(1);

        $result = $this->controller->update($appointmentId, $request);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testUpdateAppointmentNotFound(): void
    {
        $appointmentId = 999;
        $request = ['staffId' => 4];

        $this->appointmentRepository->method('findById')->willReturn(null);

        $result = $this->controller->update($appointmentId, $request);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('NOT_FOUND', $result['error']['code']);
    }

    public function testCancelAppointment(): void
    {
        $appointmentId = 1;
        $request = ['reason' => 'Customer request'];

        $existingAppointment = [
            'id' => 1,
            'status' => 'Confirmed',
        ];

        $this->appointmentRepository->method('findById')->willReturn($existingAppointment);
        $this->appointmentRepository->method('update')->willReturn(1);

        $result = $this->controller->cancel($appointmentId, $request);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['success']);
    }

    public function testCancelAppointmentAlreadyCancelled(): void
    {
        $appointmentId = 1;
        $request = ['reason' => 'Customer request'];

        $existingAppointment = [
            'id' => 1,
            'status' => 'Cancelled',
        ];

        $this->appointmentRepository->method('findById')->willReturn($existingAppointment);

        $result = $this->controller->cancel($appointmentId, $request);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('BUSINESS_RULE_VIOLATION', $result['error']['code']);
    }

    public function testListWithPaginationLimit(): void
    {
        $query = [
            'page' => 1,
            'limit' => 500, // More than max of 100
        ];

        $this->appointmentRepository->method('findFiltered')->with(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            1,
            100 // Should be capped at 100
        )->willReturn(['data' => [], 'total' => 0]);

        $result = $this->controller->list($query);

        $this->assertEquals('success', $result['status']);
    }
}
