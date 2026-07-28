<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\Appointment;
use App\Domain\Repositories\AppointmentRepository;
use App\Domain\Repositories\CustomerRepository;
use App\Domain\Repositories\ServiceRepository;
use App\Domain\Repositories\StaffRepository;
use App\Application\Exceptions\InvalidBookingException;
use App\Application\Exceptions\AppointmentConflictException;
use DateTime;
use Psr\Log\LoggerInterface;

class BookingService
{
    public function __construct(
        private AppointmentRepository $appointmentRepo,
        private CustomerRepository $customerRepo,
        private ServiceRepository $serviceRepo,
        private StaffRepository $staffRepo,
        private AvailabilityService $availabilityService,
        private NotificationService $notificationService,
        private LoggerInterface $logger
    ) {}

    public function bookAppointment(
        int $customerId,
        int $serviceId,
        int $staffId,
        DateTime $scheduledDate,
        DateTime $scheduledTime,
        string $customerNotes = ''
    ): Appointment {
        $this->validateBookingRequest(
            $customerId,
            $serviceId,
            $staffId,
            $scheduledDate,
            $scheduledTime
        );

        $service = $this->serviceRepo->findById($serviceId);
        $durationMinutes = $service->getDurationMinutes();

        if (!$this->availabilityService->isAvailable(
            $staffId,
            $scheduledDate,
            $scheduledTime,
            $durationMinutes
        )) {
            throw new AppointmentConflictException(
                'Staff member not available at requested time'
            );
        }

        $appointment = new Appointment(
            customerId: $customerId,
            serviceId: $serviceId,
            staffId: $staffId,
            scheduledDate: $scheduledDate,
            scheduledTime: $scheduledTime,
            durationMinutes: $durationMinutes,
            status: 'Pending'
        );

        $this->appointmentRepo->save($appointment);

        try {
            $this->notificationService->sendBookingConfirmation($appointment);
            $this->notificationService->scheduleReminder($appointment);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send booking notification', [
                'appointment_id' => $appointment->getId(),
                'error' => $e->getMessage()
            ]);
        }

        $this->logger->info('Appointment booked', [
            'appointment_id' => $appointment->getId(),
            'customer_id' => $customerId,
            'staff_id' => $staffId
        ]);

        return $appointment;
    }

    private function validateBookingRequest(
        int $customerId,
        int $serviceId,
        int $staffId,
        DateTime $scheduledDate,
        DateTime $scheduledTime
    ): void {
        if (!$this->customerRepo->exists($customerId)) {
            throw new InvalidBookingException('Customer not found');
        }

        if (!$this->serviceRepo->exists($serviceId)) {
            throw new InvalidBookingException('Service not found');
        }

        $staff = $this->staffRepo->findById($staffId);
        if (!$staff || $staff->getStatus() !== 'Active') {
            throw new InvalidBookingException('Staff member not available');
        }

        if ($scheduledDate->format('Y-m-d') < date('Y-m-d')) {
            throw new InvalidBookingException(
                'Cannot book appointments in the past'
            );
        }

        $maxDate = new DateTime();
        $maxDate->modify('+30 days');
        if ($scheduledDate > $maxDate) {
            throw new InvalidBookingException(
                'Booking window is 30 days in advance'
            );
        }

        $appointmentStart = new DateTime(
            $scheduledDate->format('Y-m-d') . ' ' .
            $scheduledTime->format('H:i:s')
        );
        $now = new DateTime();
        $leadTime = $appointmentStart->diff($now);

        if ($leadTime->invert === 0 || $leadTime->h < 1) {
            throw new InvalidBookingException(
                'Appointments must be booked at least 1 hour in advance'
            );
        }
    }
}
