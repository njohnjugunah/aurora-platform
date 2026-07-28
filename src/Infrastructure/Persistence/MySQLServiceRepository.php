<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\ServiceRepository;
use App\Infrastructure\Database\Connection;
use PDO;

class MySQLServiceRepository implements ServiceRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM services WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM services WHERE deleted_at IS NULL ORDER BY name ASC
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByCategory(string $category): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM services WHERE category = ? AND deleted_at IS NULL ORDER BY name ASC
        ');
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM services WHERE status = ? AND deleted_at IS NULL ORDER BY name ASC
        ');
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function save(array $service): int
    {
        if (isset($service['id'])) {
            return $this->update($service['id'], $service);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO services (
                name, description, category, price, duration_minutes, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');

        $stmt->execute([
            $service['name'],
            $service['description'] ?? null,
            $service['category'] ?? null,
            $service['price'],
            $service['duration_minutes'],
            $service['status'] ?? 'active',
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
        $sql = 'UPDATE services SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $id;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE services SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }
}
