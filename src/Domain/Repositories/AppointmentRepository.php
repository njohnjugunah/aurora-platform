<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\Appointment;
use DateTime;

interface AppointmentRepository
{
    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByCustomerId(int $customerId): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByStaffAndDate(int $staffId, DateTime $date): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findConflicting(
        int $staffId,
        DateTime $scheduledDate,
        DateTime $scheduledTime,
        int $durationMinutes
    ): array;

    public function save(Appointment $appointment): void;

    public function delete(int $id): void;

    /**
     * @return array<string, mixed>
     */
    public function findPaginated(
        int $page = 1,
        int $perPage = 20,
        array $filters = []
    ): array;

    /**
     * @param array<string, mixed> $data
     * @return mixed
     */
    public function update(array $data): mixed;

    /**
     * @return array<string, mixed>|null
     */
    public function updateStatus(int $id, string $status, string $reason = ''): ?array;
}
