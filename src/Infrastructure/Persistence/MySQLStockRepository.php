<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\StockRepository;
use App\Infrastructure\Database\Connection;
use PDO;

class MySQLStockRepository implements StockRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findByProductId(int $productId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stock WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $productId, array $data): void
    {
        $updates = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== 'product_id') {
                $updates[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($updates)) {
            return;
        }

        $values[] = $productId;
        $values[] = date('Y-m-d H:i:s');
        $sql = 'UPDATE stock SET ' . implode(', ', $updates) . ', updated_at = ? WHERE product_id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
    }

    public function addStock(int $productId, int $quantity): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE stock SET quantity_on_hand = quantity_on_hand + ?, updated_at = NOW()
            WHERE product_id = ?
        ');
        $stmt->execute([$quantity, $productId]);
    }

    public function deductStock(int $productId, int $quantity): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE stock SET quantity_on_hand = quantity_on_hand - ?, updated_at = NOW()
            WHERE product_id = ? AND quantity_on_hand >= ?
        ');
        $stmt->execute([$quantity, $productId, $quantity]);
    }

    public function recordMovement(int $productId, string $type, int $quantity): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO stock_movements (product_id, type, quantity, created_at)
            VALUES (?, ?, ?, NOW())
        ');
        $stmt->execute([$productId, $type, $quantity]);
    }
}
