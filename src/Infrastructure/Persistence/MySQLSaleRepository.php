<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\SaleRepository;
use App\Infrastructure\Database\Connection;
use PDO;

class MySQLSaleRepository implements SaleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM sales WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByCustomerId(int $customerId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM sales
            WHERE customer_id = ? AND deleted_at IS NULL
            ORDER BY created_at DESC
        ');
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function findByDateRange(string $startDate, string $endDate): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM sales
            WHERE DATE(created_at) BETWEEN ? AND ?
            AND deleted_at IS NULL
            ORDER BY created_at DESC
        ');
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM sales
            WHERE status = ? AND deleted_at IS NULL
            ORDER BY created_at DESC
        ');
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function save(array $sale): int
    {
        if (isset($sale['id'])) {
            return $this->update($sale['id'], $sale);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO sales (
                customer_id, staff_id, appointment_id, subtotal, discount_amount,
                tax_amount, total, discount_type, discount_value, status, notes,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');

        $stmt->execute([
            $sale['customer_id'] ?? null,
            $sale['staff_id'],
            $sale['appointment_id'] ?? null,
            $sale['subtotal'],
            $sale['discount_amount'] ?? 0,
            $sale['tax_amount'] ?? 0,
            $sale['total'],
            $sale['discount_type'] ?? null,
            $sale['discount_value'] ?? null,
            $sale['status'] ?? 'open',
            $sale['notes'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): int
    {
        $updates = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'created_at') {
                $updates[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($updates)) {
            return $id;
        }

        $values[] = $id;
        $sql = 'UPDATE sales SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $id;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE sales SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }
}
