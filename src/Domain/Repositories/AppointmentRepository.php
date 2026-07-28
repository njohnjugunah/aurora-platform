<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\Appointment;
use DateTime;

interface AppointmentRepository
{
    public function findById(int $id): ?Appointment;

    public function findByCustomerId(int $customerId): array;

    public function findByStaffAndDate(int $staffId, DateTime $date): array;

    public function findConflicting(
        int $staffId,
        DateTime $scheduledDate,
        DateTime $scheduledTime,
        int $durationMinutes
    ): array;

    public function save(Appointment $appointment): void;

    public function delete(int $id): void;

    public function findPaginated(
        int $page = 1,
        int $perPage = 20,
        array $filters = []
    ): array;
}
