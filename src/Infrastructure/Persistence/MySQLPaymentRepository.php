<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\PaymentRepository;
use App\Infrastructure\Database\Connection;
use PDO;

class MySQLPaymentRepository implements PaymentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySaleId(int $saleId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM payments WHERE sale_id = ? ORDER BY created_at DESC
        ');
        $stmt->execute([$saleId]);
        return $stmt->fetchAll();
    }

    public function findByCustomerId(int $customerId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM payments WHERE customer_id = ? ORDER BY created_at DESC
        ');
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function save(array $payment): int
    {
        if (isset($payment['id'])) {
            return $this->update($payment['id'], $payment);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO payments (
                sale_id, customer_id, method, amount, reference, status,
                mpesa_checkout_request_id, mpesa_merchant_request_id,
                mpesa_result_code, mpesa_result_desc, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');

        $stmt->execute([
            $payment['sale_id'],
            $payment['customer_id'] ?? null,
            $payment['method'],
            $payment['amount'],
            $payment['reference'] ?? null,
            $payment['status'] ?? 'pending',
            $payment['mpesa_checkout_request_id'] ?? null,
            $payment['mpesa_merchant_request_id'] ?? null,
            $payment['mpesa_result_code'] ?? null,
            $payment['mpesa_result_desc'] ?? null,
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
        $sql = 'UPDATE payments SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $id;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM payments WHERE id = ?');
        $stmt->execute([$id]);
    }
}
