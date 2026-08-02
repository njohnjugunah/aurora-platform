<?php

namespace GlamByMariga\Shop;

use PDO;
use Exception;

/**
 * Order Manager
 * Handles order processing, fulfillment, and invoice generation
 */
class OrderManager
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
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

            $stmt = $this->db->prepare(
                "UPDATE orders SET status = ?, notes = ? WHERE id = ?"
            );
            $stmt->execute([$newStatus, $notes, $orderId]);

            // If shipped, update delivery date
            if ($newStatus === 'shipped') {
                $stmt = $this->db->prepare(
                    "UPDATE orders SET delivery_date = DATE_ADD(NOW(), INTERVAL 3 DAY) WHERE id = ?"
                );
                $stmt->execute([$orderId]);
            }

            return [
                'success' => true,
                'message' => 'Order status updated'
            ];

        } catch (Exception $e) {
            error_log('Update order status error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Assign tracking number
     */
    public function assignTrackingNumber($orderId, $trackingNumber, $shippingMethod = null)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE orders SET tracking_number = ?, shipping_method = ? WHERE id = ?"
            );
            $stmt->execute([$trackingNumber, $shippingMethod, $orderId]);

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
    public function processRefund($orderId, $refundAmount = null)
    {
        try {
            $this->db->beginTransaction();

            // Get order
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

            // Update order status
            $stmt = $this->db->prepare(
                "UPDATE orders SET status = 'refunded', notes = CONCAT(notes, '\n', ?) WHERE id = ?"
            );
            $stmt->execute(["Refund processed: KES {$refundAmount}", $orderId]);

            // Get order items for stock restoration
            $stmt = $this->db->prepare(
                "SELECT product_id, variant_id, quantity FROM order_items WHERE order_id = ?"
            );
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Restore stock
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
     * Generate invoice
     */
    public function generateInvoice($orderId)
    {
        try {
            // Get order details
            $stmt = $this->db->prepare(
                "SELECT o.*, c.name, c.email, c.phone
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
                "SELECT oi.*, p.name, p.sku, pv.size, pv.color
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                 WHERE oi.order_id = ?"
            );
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'invoice' => [
                    'order' => $order,
                    'items' => $items,
                    'generated_at' => date('Y-m-d H:i:s')
                ]
            ];

        } catch (Exception $e) {
            error_log('Generate invoice error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get sales report
     */
    public function getSalesReport($startDate, $endDate)
    {
        try {
            // Total sales
            $stmt = $this->db->prepare(
                "SELECT
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value,
                    SUM(discount_amount) as total_discounts
                 FROM orders
                 WHERE status IN ('processing', 'shipped', 'delivered')
                 AND created_at BETWEEN ? AND ?"
            );
            $stmt->execute([$startDate, $endDate]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            // Top products
            $stmt = $this->db->prepare(
                "SELECT
                    p.id, p.name, p.image_url,
                    SUM(oi.quantity) as units_sold,
                    SUM(oi.subtotal) as revenue
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 JOIN orders o ON oi.order_id = o.id
                 WHERE o.status IN ('processing', 'shipped', 'delivered')
                 AND o.created_at BETWEEN ? AND ?
                 GROUP BY p.id
                 ORDER BY revenue DESC
                 LIMIT 10"
            );
            $stmt->execute([$startDate, $endDate]);
            $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Daily revenue
            $stmt = $this->db->prepare(
                "SELECT
                    DATE(created_at) as date,
                    COUNT(*) as orders,
                    SUM(total_amount) as revenue
                 FROM orders
                 WHERE status IN ('processing', 'shipped', 'delivered')
                 AND created_at BETWEEN ? AND ?
                 GROUP BY DATE(created_at)
                 ORDER BY date DESC"
            );
            $stmt->execute([$startDate, $endDate]);
            $dailyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'report' => [
                    'summary' => $summary,
                    'top_products' => $topProducts,
                    'daily_revenue' => $dailyRevenue,
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]
            ];

        } catch (Exception $e) {
            error_log('Generate report error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get order analytics
     */
    public function getOrderAnalytics()
    {
        try {
            // Status breakdown
            $stmt = $this->db->prepare(
                "SELECT status, COUNT(*) as count FROM orders GROUP BY status"
            );
            $stmt->execute();
            $statusBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Recent orders
            $stmt = $this->db->prepare(
                "SELECT id, created_at, status, total_amount, customer_id
                 FROM orders
                 ORDER BY created_at DESC
                 LIMIT 20"
            );
            $stmt->execute();
            $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Revenue stats
            $stmt = $this->db->prepare(
                "SELECT
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value,
                    COUNT(*) as total_orders
                 FROM orders
                 WHERE status IN ('processing', 'shipped', 'delivered')"
            );
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'analytics' => [
                    'status_breakdown' => $statusBreakdown,
                    'recent_orders' => $recentOrders,
                    'stats' => $stats
                ]
            ];

        } catch (Exception $e) {
            error_log('Get analytics error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
