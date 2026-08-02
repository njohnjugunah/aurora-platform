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

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stock WHERE product_id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByProductId(int $productId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stock WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    public function findFiltered(array $filters, int $page = 1, int $limit = 50): array
    {
        $where = [];
        $params = [];

        if (isset($filters['product_id'])) {
            $where[] = 'product_id = ?';
            $params[] = $filters['product_id'];
        }

        if (isset($filters['low_stock'])) {
            $where[] = 'quantity_on_hand <= reorder_point';
        }

        $offset = ($page - 1) * $limit;
        $params[] = $limit;
        $params[] = $offset;

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->pdo->prepare("
            SELECT * FROM stock
            $whereClause
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function findProducts(array $filters, string $sort, string $order, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $orderClause = "ORDER BY $sort $order";

        $stmt = $this->pdo->prepare("
            SELECT * FROM stock
            $orderClause
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll() ?: [];
    }

    public function getStockLevel(int $productId): ?int
    {
        $stmt = $this->pdo->prepare('
            SELECT quantity_on_hand FROM stock WHERE product_id = ?
        ');
        $stmt->execute([$productId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['quantity_on_hand'] : null;
    }

    public function getMovements(int $productId, array $filters, int $page, int $limit): ?array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare('
            SELECT * FROM stock_movements
            WHERE product_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$productId, $limit, $offset]);
        return $stmt->fetchAll() ?: null;
    }

    public function getLowStockItems(int $limit = 50): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM stock
            WHERE quantity_on_hand <= reorder_point
            ORDER BY quantity_on_hand ASC
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll() ?: null;
    }

    public function update(int $productId, array $data): int
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
            return $productId;
        }

        $values[] = $productId;
        $sql = 'UPDATE stock SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE product_id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $productId;
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
