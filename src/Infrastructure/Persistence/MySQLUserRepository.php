<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\UserRepository;
use App\Infrastructure\Database\Connection;
use PDO;

class MySQLUserRepository implements UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM users WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM users WHERE email = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM users WHERE status = ? AND deleted_at IS NULL ORDER BY created_at DESC
        ');
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function save(array $user): int
    {
        if (isset($user['id'])) {
            return $this->update($user['id'], $user);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO users (
                email, password_hash, name, phone, status, role_id,
                last_login_at, failed_login_count, locked_until,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');

        $stmt->execute([
            $user['email'],
            $user['password_hash'],
            $user['name'],
            $user['phone'] ?? null,
            $user['status'] ?? 'active',
            $user['role_id'],
            $user['last_login_at'] ?? null,
            $user['failed_login_count'] ?? 0,
            $user['locked_until'] ?? null,
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
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $id;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }
}
