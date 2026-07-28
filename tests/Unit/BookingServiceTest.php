<?php

namespace Tests\Unit\Application\Services;

use PHPUnit\Framework\TestCase;
use App\Application\Services\BookingService;
use App\Domain\Repositories\AppointmentRepository;
use App\Domain\Repositories\CustomerRepository;
use App\Application\Exceptions\InvalidBookingException;
use Psr\Log\NullLogger;

class BookingServiceTest extends TestCase
{
    private BookingService $bookingService;
    private $appointmentRepo;
    private $customerRepo;

    protected function setUp(): void
    {
        $this->appointmentRepo = $this->createMock(AppointmentRepository::class);
        $this->customerRepo = $this->createMock(CustomerRepository::class);

        $this->bookingService = new BookingService(
            $this->appointmentRepo,
            $this->customerRepo,
            $this->createMock(ServiceRepository::class),
            $this->createMock(StaffRepository::class),
            $this->createMock(AvailabilityService::class),
            $this->createMock(NotificationService::class),
            new NullLogger()
        );
    }

    public function testBookAppointmentValidation(): void
    {
        $this->customerRepo->method('exists')->willReturn(false);

        $this->expectException(InvalidBookingException::class);

        $this->bookingService->bookAppointment(
            99999,
            1,
            1,
            new DateTime('2026-08-15'),
            new DateTime('2000-01-01 10:00:00')
        );
    }

    public function testBookAppointmentInPast(): void
    {
        $this->expectException(InvalidBookingException::class);
        $this->expectExceptionMessage('Cannot book appointments in the past');

        $pastDate = new DateTime('2026-01-01');
        $this->bookingService->bookAppointment(
            10,
            1,
            1,
            $pastDate,
            new DateTime('2000-01-01 10:00:00')
        );
    }
}
