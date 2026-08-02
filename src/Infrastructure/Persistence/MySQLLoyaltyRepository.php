<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\LoyaltyRepository;
use App\Infrastructure\Database\Connection;
use PDO;

class MySQLLoyaltyRepository implements LoyaltyRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM loyalty_points WHERE customer_id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByCustomerId(int $customerId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM loyalty_points WHERE customer_id = ?
        ');
        $stmt->execute([$customerId]);
        return $stmt->fetch() ?: null;
    }

    public function getLeaderboard(int $limit = 50, string $period = 'all_time'): array
    {
        $orderBy = match($period) {
            'month' => 'points_earned_this_month',
            'year' => 'points_earned_this_year',
            default => 'total_points'
        };

        $stmt = $this->pdo->prepare("
            SELECT customer_id, total_points, tier, last_earned_at
            FROM loyalty_points
            ORDER BY $orderBy DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll() ?: [];
    }

    public function getTransactionHistory(int $customerId, int $page = 1, int $limit = 50): ?array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare('
            SELECT * FROM loyalty_points
            WHERE customer_id = ?
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$customerId, $limit, $offset]);
        return $stmt->fetchAll() ?: null;
    }

    public function update(int $customerId, array $data): int
    {
        $updates = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== 'customer_id') {
                $updates[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($updates)) {
            return $customerId;
        }

        $values[] = $customerId;
        $sql = 'UPDATE loyalty_points SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE customer_id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $customerId;
    }

    public function incrementPoints(int $customerId, int $points): void
    {
        $this->ensureExists($customerId);

        $stmt = $this->pdo->prepare('
            UPDATE loyalty_points
            SET total_points = total_points + ?,
                points_earned = points_earned + ?,
                last_earned_at = NOW(),
                updated_at = NOW()
            WHERE customer_id = ?
        ');
        $stmt->execute([$points, $points, $customerId]);
    }

    public function decrementPoints(int $customerId, int $points): void
    {
        $this->ensureExists($customerId);

        $stmt = $this->pdo->prepare('
            UPDATE loyalty_points
            SET total_points = GREATEST(0, total_points - ?),
                points_redeemed = points_redeemed + ?,
                last_redeemed_at = NOW(),
                updated_at = NOW()
            WHERE customer_id = ?
        ');
        $stmt->execute([$points, $points, $customerId]);
    }

    private function ensureExists(int $customerId): void
    {
        $existing = $this->findByCustomerId($customerId);
        if (!$existing) {
            $stmt = $this->pdo->prepare('
                INSERT INTO loyalty_points (customer_id, total_points, tier, created_at, updated_at)
                VALUES (?, 0, ?, NOW(), NOW())
            ');
            $stmt->execute([$customerId, 'bronze']);
        }
    }
}
