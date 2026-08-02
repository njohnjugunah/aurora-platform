<?php

namespace GlamByMariga\Admin;

use PDO;
use Exception;

/**
 * Admin Order Service
 * Handles order management and fulfillment
 */
class AdminOrderService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all orders with filters
     */
    public function getAllOrders($limit = 20, $offset = 0, $filters = [])
    {
        try {
            $query = "SELECT o.*, c.name as customer_name, c.email, c.phone,
                            COUNT(DISTINCT oi.id) as item_count
                     FROM orders o
                     LEFT JOIN customers c ON o.customer_id = c.id
                     LEFT JOIN order_items oi ON o.id = oi.order_id
                     WHERE 1=1";
            $params = [];

            if (!empty($filters['status'])) {
                $query .= " AND o.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['payment_status'])) {
                $query .= " AND o.payment_status = ?";
                $params[] = $filters['payment_status'];
            }

            if (!empty($filters['date_from'])) {
                $query .= " AND DATE(o.created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $query .= " AND DATE(o.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $query .= " AND (o.id LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }

            $query .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get orders error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get order count
     */
    public function getOrderCount($filters = [])
    {
        try {
            $query = "SELECT COUNT(DISTINCT o.id) as total FROM orders o
                     LEFT JOIN customers c ON o.customer_id = c.id
                     WHERE 1=1";
            $params = [];

            if (!empty($filters['status'])) {
                $query .= " AND o.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['payment_status'])) {
                $query .= " AND o.payment_status = ?";
                $params[] = $filters['payment_status'];
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;

        } catch (Exception $e) {
            error_log('Get order count error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get order details
     */
    public function getOrderDetails($orderId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT o.*, c.name as customer_name, c.email, c.phone
                 FROM orders o
                 LEFT JOIN customers c ON o.customer_id = c.id
                 WHERE o.id = ?"
            );
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return [
                    'success' => false,
                    'error' => 'Order not found'
                ];
            }

            // Get order items
            $stmt = $this->db->prepare(
                "SELECT oi.*, p.name as product_name, p.sku
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?"
            );
            $stmt->execute([$orderId]);
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'order' => $order
            ];

        } catch (Exception $e) {
            error_log('Get order details error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $newStatus, $notes = null)
    {
        try {
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

            if (!in_array($newStatus, $validStatuses)) {
                return [
                    'success' => false,
                    'error' => 'Invalid status'
                ];
            }

            $currentNotes = $this->getOrderNotes($orderId);
            $updatedNotes = $currentNotes ? $currentNotes . "\n[" . date('Y-m-d H:i:s') . "] " . $notes : $notes;

            $stmt = $this->db->prepare(
                "UPDATE orders SET status = ?, notes = ? WHERE id = ?"
            );
            $stmt->execute([$newStatus, $updatedNotes, $orderId]);

            return [
                'success' => true,
                'message' => 'Order status updated'
            ];

        } catch (Exception $e) {
            error_log('Update status error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Assign tracking number
     */
    public function assignTrackingNumber($orderId, $trackingNumber)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE orders SET tracking_number = ? WHERE id = ?"
            );
            $stmt->execute([$trackingNumber, $orderId]);

            return [
                'success' => true,
                'message' => 'Tracking number assigned'
            ];

        } catch (Exception $e) {
            error_log('Assign tracking error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process refund
     */
    public function processRefund($orderId, $refundAmount = null, $reason = null)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return [
                    'success' => false,
                    'error' => 'Order not found'
                ];
            }

            $refundAmount = $refundAmount ?? $order['total_amount'];

            $notes = "Refund: KES {$refundAmount}";
            if ($reason) {
                $notes .= " - Reason: {$reason}";
            }

            $stmt = $this->db->prepare(
                "UPDATE orders SET status = 'refunded', payment_status = 'refunded' WHERE id = ?"
            );
            $stmt->execute([$orderId]);

            // Restore stock
            $stmt = $this->db->prepare(
                "SELECT product_id, variant_id, quantity FROM order_items WHERE order_id = ?"
            );
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                if ($item['variant_id']) {
                    $stmt = $this->db->prepare(
                        "UPDATE product_variants SET stock = stock + ? WHERE id = ?"
                    );
                    $stmt->execute([$item['quantity'], $item['variant_id']]);
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'refund_amount' => $refundAmount,
                'message' => 'Refund processed successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Process refund error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT status, COUNT(*) as count FROM orders GROUP BY status"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get by status error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders($limit = 10)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT o.*, c.name as customer_name
                 FROM orders o
                 LEFT JOIN customers c ON o.customer_id = c.id
                 ORDER BY o.created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get recent orders error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get order notes
     */
    private function getOrderNotes($orderId)
    {
        try {
            $stmt = $this->db->prepare("SELECT notes FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['notes'] ?? null;

        } catch (Exception $e) {
            return null;
        }
    }
}
