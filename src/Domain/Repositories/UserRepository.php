<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\User;

interface UserRepository
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function exists(int $id): bool;

    public function save(User $user): void;

    public function delete(int $id): void;

    public function findAll(): array;

    public function findByRole(string $role): array;
}
