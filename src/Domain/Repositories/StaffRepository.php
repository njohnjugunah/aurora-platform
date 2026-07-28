<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\Staff;

interface StaffRepository
{
    public function findById(int $id): ?Staff;

    public function findByUserId(int $userId): ?Staff;

    public function exists(int $id): bool;

    public function save(Staff $staff): void;

    public function delete(int $id): void;

    public function findAll(): array;

    public function findActive(): array;

    public function findByRole(string $role): array;
}
