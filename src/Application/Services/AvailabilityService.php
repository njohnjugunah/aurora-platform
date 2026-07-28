<?php

declare(strict_types=1);

namespace App\Application\Services;

use DateTime;
use App\Domain\Repositories\AppointmentRepository;

class AvailabilityService
{
    public function __construct(
        private AppointmentRepository $appointmentRepo
    ) {}

    public function isAvailable(
        int $staffId,
        DateTime $date,
        DateTime $time,
        int $durationMinutes
    ): bool {
        $conflicts = $this->appointmentRepo->findConflicting(
            $staffId,
            $date,
            $time,
            $durationMinutes
        );

        return count($conflicts) === 0;
    }

    public function getAvailableSlots(
        int $staffId,
        DateTime $date,
        int $serviceMinutes
    ): array {
        $slots = [];
        $businessStart = new DateTime($date->format('Y-m-d 09:00:00'));
        $businessEnd = new DateTime($date->format('Y-m-d 17:00:00'));

        $currentTime = clone $businessStart;

        while ($currentTime < $businessEnd) {
            $available = $this->isAvailable(
                $staffId,
                $date,
                $currentTime,
                $serviceMinutes
            );

            $slots[] = [
                'time' => $currentTime->format('H:i:s'),
                'available' => $available
            ];

            $currentTime->modify('+15 minutes');
        }

        return $slots;
    }
}
