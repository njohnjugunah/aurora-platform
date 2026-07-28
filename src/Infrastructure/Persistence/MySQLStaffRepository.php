<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\StaffRepository;
use App\Infrastructure\Database\Connection;
use PDO;

class MySQLStaffRepository implements StaffRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM staff_members WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM staff_members WHERE user_id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM staff_members WHERE deleted_at IS NULL ORDER BY first_name ASC
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM staff_members WHERE status = ? AND deleted_at IS NULL ORDER BY first_name ASC
        ');
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function save(array $staff): int
    {
        if (isset($staff['id'])) {
            return $this->update($staff['id'], $staff);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO staff_members (
                user_id, first_name, last_name, phone, position, hire_date,
                status, commission_rate, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');

        $stmt->execute([
            $staff['user_id'],
            $staff['first_name'],
            $staff['last_name'],
            $staff['phone'],
            $staff['position'],
            $staff['hire_date'],
            $staff['status'] ?? 'active',
            $staff['commission_rate'] ?? 0,
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
        $sql = 'UPDATE staff_members SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $id;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE staff_members SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }
}
