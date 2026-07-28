<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\Service;

interface ServiceRepository
{
    public function findById(int $id): ?Service;

    public function exists(int $id): bool;

    public function save(Service $service): void;

    public function delete(int $id): void;

    public function findAll(): array;

    public function findByCategory(string $category): array;

    public function findActive(): array;
}
