<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\Sale;

interface SaleRepository
{
    public function findById(int $id): ?Sale;

    public function findByCustomerId(int $customerId): array;

    public function save(Sale $sale): void;

    public function delete(int $id): void;

    public function findPaginated(int $page = 1, int $perPage = 20): array;
}
