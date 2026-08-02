<?php

namespace GlamByMariga\Admin;

use PDO;
use Exception;

/**
 * Admin Analytics Service
 * Provides business analytics and reporting
 */
class AdminAnalyticsService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get dashboard metrics
     */
    public function getDashboardMetrics()
    {
        try {
            // Total revenue (all time)
            $stmt = $this->db->prepare(
                "SELECT COALESCE(SUM(total_amount), 0) as total_revenue,
                        COUNT(*) as total_orders
                 FROM orders
                 WHERE status IN ('processing', 'shipped', 'delivered')"
            );
            $stmt->execute();
            $revenue = $stmt->fetch(PDO::FETCH_ASSOC);

            // Today's revenue
            $stmt = $this->db->prepare(
                "SELECT COALESCE(SUM(total_amount), 0) as today_revenue,
                        COUNT(*) as today_orders
                 FROM orders
                 WHERE DATE(created_at) = CURDATE()
                 AND status IN ('processing', 'shipped', 'delivered')"
            );
            $stmt->execute();
            $today = $stmt->fetch(PDO::FETCH_ASSOC);

            // Total products
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM products");
            $stmt->execute();
            $products = $stmt->fetch(PDO::FETCH_ASSOC);

            // Total customers
            $stmt = $this->db->prepare(
                "SELECT COUNT(DISTINCT customer_id) as total FROM orders"
            );
            $stmt->execute();
            $customers = $stmt->fetch(PDO::FETCH_ASSOC);

            // Pending orders
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'"
            );
            $stmt->execute();
            $pending = $stmt->fetch(PDO::FETCH_ASSOC);

            // Low stock items
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as total FROM product_variants WHERE stock < 10"
            );
            $stmt->execute();
            $lowStock = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'metrics' => [
                    'total_revenue' => $revenue['total_revenue'] ?? 0,
                    'total_orders' => $revenue['total_orders'] ?? 0,
                    'today_revenue' => $today['today_revenue'] ?? 0,
                    'today_orders' => $today['today_orders'] ?? 0,
                    'total_products' => $products['total'] ?? 0,
                    'total_customers' => $customers['total'] ?? 0,
                    'pending_orders' => $pending['total'] ?? 0,
                    'low_stock_items' => $lowStock['total'] ?? 0
                ]
            ];

        } catch (Exception $e) {
            error_log('Dashboard metrics error: ' . $e->getMessage());
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
            // Daily sales
            $stmt = $this->db->prepare(
                "SELECT DATE(created_at) as date,
                        COUNT(*) as orders,
                        SUM(total_amount) as revenue
                 FROM orders
                 WHERE status IN ('processing', 'shipped', 'delivered')
                 AND created_at BETWEEN ? AND ?
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC"
            );
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Summary
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as total_orders,
                        SUM(total_amount) as total_revenue,
                        AVG(total_amount) as avg_order_value,
                        SUM(discount_amount) as total_discounts,
                        SUM(shipping_cost) as total_shipping
                 FROM orders
                 WHERE status IN ('processing', 'shipped', 'delivered')
                 AND created_at BETWEEN ? AND ?"
            );
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'report' => [
                    'summary' => $summary,
                    'daily_sales' => $dailySales,
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]
            ];

        } catch (Exception $e) {
            error_log('Sales report error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get top products
     */
    public function getTopProducts($days = 30, $limit = 10)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.*,
                        SUM(oi.quantity) as units_sold,
                        SUM(oi.subtotal) as revenue,
                        COUNT(DISTINCT o.id) as order_count
                 FROM products p
                 LEFT JOIN order_items oi ON p.id = oi.product_id
                 LEFT JOIN orders o ON oi.order_id = o.id
                 WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 AND o.status IN ('processing', 'shipped', 'delivered')
                 GROUP BY p.id
                 ORDER BY revenue DESC
                 LIMIT ?"
            );
            $stmt->execute([$days, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Top products error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get top customers
     */
    public function getTopCustomers($limit = 10)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.*,
                        COUNT(DISTINCT o.id) as order_count,
                        SUM(o.total_amount) as total_spent
                 FROM customers c
                 LEFT JOIN orders o ON c.id = o.customer_id
                 GROUP BY c.id
                 ORDER BY total_spent DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Top customers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get payment methods report
     */
    public function getPaymentMethodsReport($startDate, $endDate)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT payment_method,
                        COUNT(*) as total_transactions,
                        SUM(total_amount) as total_amount,
                        AVG(total_amount) as avg_amount
                 FROM orders
                 WHERE status IN ('processing', 'shipped', 'delivered')
                 AND created_at BETWEEN ? AND ?
                 GROUP BY payment_method
                 ORDER BY total_amount DESC"
            );
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Payment methods error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get order status distribution
     */
    public function getOrderStatusDistribution()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT status, COUNT(*) as count FROM orders GROUP BY status"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Status distribution error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics()
    {
        try {
            // New customers this month
            $stmt = $this->db->prepare(
                "SELECT COUNT(DISTINCT customer_id) as new_customers
                 FROM orders
                 WHERE YEAR(created_at) = YEAR(NOW())
                 AND MONTH(created_at) = MONTH(NOW())"
            );
            $stmt->execute();
            $newCustomers = $stmt->fetch(PDO::FETCH_ASSOC);

            // Average order value
            $stmt = $this->db->prepare(
                "SELECT AVG(total_amount) as avg_order_value FROM orders
                 WHERE status IN ('processing', 'shipped', 'delivered')"
            );
            $stmt->execute();
            $avgOrder = $stmt->fetch(PDO::FETCH_ASSOC);

            // Repeat customers
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as repeat_customers
                 FROM (
                     SELECT customer_id, COUNT(*) as order_count
                     FROM orders
                     GROUP BY customer_id
                     HAVING order_count > 1
                 ) as repeat"
            );
            $stmt->execute();
            $repeat = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'analytics' => [
                    'new_customers_this_month' => $newCustomers['new_customers'] ?? 0,
                    'avg_order_value' => $avgOrder['avg_order_value'] ?? 0,
                    'repeat_customers' => $repeat['repeat_customers'] ?? 0
                ]
            ];

        } catch (Exception $e) {
            error_log('Customer analytics error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get inventory report
     */
    public function getInventoryReport()
    {
        try {
            // Total stock value
            $stmt = $this->db->prepare(
                "SELECT SUM(pv.stock * p.price) as total_value,
                        SUM(pv.stock) as total_units
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id"
            );
            $stmt->execute();
            $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

            // Low stock count
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as low_stock_count FROM product_variants WHERE stock < 10"
            );
            $stmt->execute();
            $lowStock = $stmt->fetch(PDO::FETCH_ASSOC);

            // Out of stock count
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as out_of_stock_count FROM product_variants WHERE stock = 0"
            );
            $stmt->execute();
            $outOfStock = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'report' => [
                    'total_value' => $inventory['total_value'] ?? 0,
                    'total_units' => $inventory['total_units'] ?? 0,
                    'low_stock_count' => $lowStock['low_stock_count'] ?? 0,
                    'out_of_stock_count' => $outOfStock['out_of_stock_count'] ?? 0
                ]
            ];

        } catch (Exception $e) {
            error_log('Inventory report error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get coupon effectiveness
     */
    public function getCouponEffectiveness()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT code,
                        discount_type,
                        discount_value,
                        current_uses as times_used,
                        SUM(o.discount_amount) as discount_amount_applied,
                        COUNT(DISTINCT o.id) as orders_with_coupon
                 FROM coupons c
                 LEFT JOIN orders o ON c.code = o.coupon_code
                 GROUP BY c.id
                 ORDER BY current_uses DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Coupon effectiveness error: ' . $e->getMessage());
            return [];
        }
    }
}
