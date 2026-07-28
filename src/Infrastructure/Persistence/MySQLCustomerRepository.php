<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\CustomerRepository;
use App\Infrastructure\Database\Connection;
use PDO;

class MySQLCustomerRepository implements CustomerRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM customers WHERE phone = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$phone]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM customers WHERE email = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM customers WHERE deleted_at IS NULL ORDER BY created_at DESC
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM customers WHERE status = ? AND deleted_at IS NULL ORDER BY created_at DESC
        ');
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function save(array $customer): int
    {
        if (isset($customer['id'])) {
            return $this->update($customer['id'], $customer);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO customers (
                first_name, last_name, phone, email, preferred_staff_id,
                communication_preference, total_visits, total_spent, avg_transaction_value,
                last_visit_at, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');

        $stmt->execute([
            $customer['first_name'],
            $customer['last_name'],
            $customer['phone'],
            $customer['email'] ?? null,
            $customer['preferred_staff_id'] ?? null,
            $customer['communication_preference'] ?? 'sms',
            $customer['total_visits'] ?? 0,
            $customer['total_spent'] ?? 0,
            $customer['avg_transaction_value'] ?? 0,
            $customer['last_visit_at'] ?? null,
            $customer['status'] ?? 'active',
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
        $sql = 'UPDATE customers SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $id;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE customers SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }
}
