<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\Customer;

interface CustomerRepository
{
    public function findById(int $id): ?Customer;

    public function findByEmail(string $email): ?Customer;

    public function findByPhone(string $phone): ?Customer;

    public function exists(int $id): bool;

    public function save(Customer $customer): void;

    public function delete(int $id): void;

    public function findAll(int $page = 1, int $perPage = 20): array;

    public function findByLoyaltyTier(string $tier): array;
}
