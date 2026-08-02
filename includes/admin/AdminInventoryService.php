<?php

namespace GlamByMariga\Admin;

use PDO;
use Exception;

/**
 * Admin Inventory Service
 * Manages inventory and stock control
 */
class AdminInventoryService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get inventory status
     */
    public function getInventoryStatus()
    {
        try {
            // Total stock value
            $stmt = $this->db->prepare(
                "SELECT SUM(pv.stock * p.price) as total_value,
                        SUM(pv.stock) as total_units,
                        COUNT(DISTINCT p.id) as total_products,
                        COUNT(DISTINCT pv.id) as total_variants
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id"
            );
            $stmt->execute();
            $status = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'status' => $status
            ];

        } catch (Exception $e) {
            error_log('Inventory status error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems($threshold = 10, $limit = 50, $offset = 0)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT pv.*, p.name as product_name, p.sku, p.price,
                        (pv.stock * p.price) as value
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id
                 WHERE pv.stock <= ?
                 ORDER BY pv.stock ASC
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([$threshold, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Low stock error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get low stock count
     */
    public function getLowStockCount($threshold = 10)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM product_variants WHERE stock <= ?"
            );
            $stmt->execute([$threshold]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        } catch (Exception $e) {
            error_log('Low stock count error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems($limit = 50, $offset = 0)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT pv.*, p.name as product_name, p.sku, p.price
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id
                 WHERE pv.stock = 0
                 ORDER BY pv.updated_at DESC
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Out of stock error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get out of stock count
     */
    public function getOutOfStockCount()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM product_variants WHERE stock = 0"
            );
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        } catch (Exception $e) {
            error_log('Out of stock count error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get stock history for variant
     */
    public function getStockHistory($variantId, $limit = 50, $offset = 0)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT sh.*, u.name as changed_by
                 FROM stock_history sh
                 LEFT JOIN users u ON sh.created_by = u.id
                 WHERE sh.variant_id = ?
                 ORDER BY sh.created_at DESC
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([$variantId, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Stock history error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Adjust stock manually
     */
    public function adjustStock($variantId, $quantity, $reason, $notes = null)
    {
        try {
            $this->db->beginTransaction();

            // Get current stock
            $stmt = $this->db->prepare(
                "SELECT stock, product_id FROM product_variants WHERE id = ?"
            );
            $stmt->execute([$variantId]);
            $variant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$variant) {
                return [
                    'success' => false,
                    'error' => 'Variant not found'
                ];
            }

            $oldStock = $variant['stock'];
            $newStock = $oldStock + $quantity;

            if ($newStock < 0) {
                return [
                    'success' => false,
                    'error' => 'Insufficient stock for this adjustment'
                ];
            }

            // Update stock
            $stmt = $this->db->prepare(
                "UPDATE product_variants SET stock = ? WHERE id = ?"
            );
            $stmt->execute([$newStock, $variantId]);

            // Log change
            $stmt = $this->db->prepare(
                "INSERT INTO stock_history
                (product_id, variant_id, old_stock, new_stock, change_amount, reason)
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $variant['product_id'],
                $variantId,
                $oldStock,
                $newStock,
                $quantity,
                $reason
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'message' => 'Stock adjusted successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Adjust stock error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get inventory valuation by category
     */
    public function getInventoryByCategory()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.id, c.name,
                        COUNT(DISTINCT p.id) as product_count,
                        SUM(pv.stock) as total_units,
                        SUM(pv.stock * p.price) as total_value
                 FROM categories c
                 LEFT JOIN products p ON c.id = p.category_id
                 LEFT JOIN product_variants pv ON p.id = pv.product_id
                 GROUP BY c.id
                 ORDER BY total_value DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Category inventory error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get slow moving items
     */
    public function getSlowMovingItems($days = 90, $limit = 20)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.*, pv.*,
                        COUNT(oi.id) as sales_count,
                        DATEDIFF(NOW(), MAX(oi.created_at)) as days_since_sale,
                        (pv.stock * p.price) as inventory_value
                 FROM products p
                 JOIN product_variants pv ON p.id = pv.product_id
                 LEFT JOIN order_items oi ON pv.id = oi.variant_id
                 GROUP BY pv.id
                 HAVING (MAX(oi.created_at) < DATE_SUB(NOW(), INTERVAL ? DAY) OR oi.id IS NULL)
                 AND pv.stock > 0
                 ORDER BY inventory_value DESC
                 LIMIT ?"
            );
            $stmt->execute([$days, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Slow moving items error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get fast moving items
     */
    public function getFastMovingItems($days = 30, $limit = 20)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.*, pv.*,
                        COUNT(oi.id) as sales_count,
                        SUM(oi.quantity) as units_sold,
                        SUM(oi.subtotal) as revenue
                 FROM products p
                 JOIN product_variants pv ON p.id = pv.product_id
                 LEFT JOIN order_items oi ON pv.id = oi.variant_id
                 WHERE oi.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 GROUP BY pv.id
                 ORDER BY units_sold DESC
                 LIMIT ?"
            );
            $stmt->execute([$days, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Fast moving items error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get inventory turnover rate
     */
    public function getInventoryTurnoverRate($days = 365)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    SUM(oi.quantity) as units_sold,
                    (SELECT SUM(stock) FROM product_variants) as current_stock,
                    ROUND(SUM(oi.quantity) / ((SELECT SUM(stock) FROM product_variants) + 0.1), 2) as turnover_rate
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            $stmt->execute([$days]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Turnover rate error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get reorder suggestions
     */
    public function getReorderSuggestions($threshold = 5)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT pv.*, p.name, p.sku,
                        (SELECT AVG(quantity) FROM order_items WHERE variant_id = pv.id) as avg_monthly_sales,
                        (pv.stock / ((SELECT AVG(quantity) FROM order_items WHERE variant_id = pv.id) + 0.1)) as months_of_stock
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id
                 WHERE pv.stock <= ? * ((SELECT AVG(quantity) FROM order_items WHERE variant_id = pv.id) + 0.1)
                 ORDER BY months_of_stock ASC"
            );
            $stmt->execute([$threshold]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Reorder suggestions error: ' . $e->getMessage());
            return [];
        }
    }
}
