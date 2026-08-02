<?php

namespace GlamByMariga\Admin;

use PDO;
use Exception;

/**
 * Admin Customer Service
 * Manages customer data and analytics
 */
class AdminCustomerService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all customers with pagination
     */
    public function getAllCustomers($limit = 20, $offset = 0, $filters = [])
    {
        try {
            $query = "SELECT c.*,
                            COUNT(DISTINCT o.id) as total_orders,
                            COALESCE(SUM(o.total_amount), 0) as lifetime_value,
                            MAX(o.created_at) as last_order_date
                     FROM customers c
                     LEFT JOIN orders o ON c.id = o.customer_id
                     WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $query .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }

            if (!empty($filters['order_count'])) {
                if ($filters['order_count'] === 'repeat') {
                    $query .= " HAVING COUNT(DISTINCT o.id) > 1";
                } elseif ($filters['order_count'] === 'single') {
                    $query .= " HAVING COUNT(DISTINCT o.id) = 1";
                } elseif ($filters['order_count'] === 'inactive') {
                    $query .= " HAVING COUNT(DISTINCT o.id) = 0";
                }
            }

            $query .= " GROUP BY c.id ORDER BY lifetime_value DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get customers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customer count
     */
    public function getCustomerCount($filters = [])
    {
        try {
            $query = "SELECT COUNT(*) as total FROM customers WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;

        } catch (Exception $e) {
            error_log('Get customer count error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get customer details
     */
    public function getCustomerDetails($customerId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                return [
                    'success' => false,
                    'error' => 'Customer not found'
                ];
            }

            // Get order history
            $stmt = $this->db->prepare(
                "SELECT id, created_at, status, total_amount FROM orders
                 WHERE customer_id = ? ORDER BY created_at DESC"
            );
            $stmt->execute([$customerId]);
            $customer['orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get analytics
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as total_orders,
                        SUM(total_amount) as lifetime_value,
                        AVG(total_amount) as avg_order_value,
                        MAX(created_at) as last_order_date
                 FROM orders WHERE customer_id = ?"
            );
            $stmt->execute([$customerId]);
            $analytics = $stmt->fetch(PDO::FETCH_ASSOC);
            $customer['analytics'] = $analytics;

            return [
                'success' => true,
                'customer' => $customer
            ];

        } catch (Exception $e) {
            error_log('Get customer details error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get top customers by spending
     */
    public function getTopCustomers($limit = 10)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.*,
                        COUNT(DISTINCT o.id) as total_orders,
                        SUM(o.total_amount) as lifetime_value,
                        AVG(o.total_amount) as avg_order_value
                 FROM customers c
                 LEFT JOIN orders o ON c.id = o.customer_id
                 GROUP BY c.id
                 ORDER BY lifetime_value DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get top customers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customer segments
     */
    public function getCustomerSegments()
    {
        try {
            $segments = [];

            // Total customers
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM customers");
            $stmt->execute();
            $segments['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Repeat customers
            $stmt = $this->db->prepare(
                "SELECT COUNT(DISTINCT customer_id) as count FROM orders
                 GROUP BY customer_id HAVING COUNT(*) > 1"
            );
            $stmt->execute();
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM (
                    SELECT COUNT(DISTINCT customer_id) as cnt FROM orders
                    GROUP BY customer_id HAVING COUNT(*) > 1
                ) as repeat"
            );
            $stmt->execute();
            $segments['repeat_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // One-time customers
            $segments['one_time_customers'] = $segments['total_customers'] - $segments['repeat_customers'];

            // High value (top 20% spenders)
            $stmt = $this->db->prepare(
                "SELECT COUNT(DISTINCT c.id) as count FROM customers c
                 LEFT JOIN orders o ON c.id = o.customer_id
                 GROUP BY c.id
                 HAVING SUM(o.total_amount) > (
                     SELECT SUM(total_amount)/COUNT(DISTINCT customer_id) * 1.5
                     FROM orders
                 )"
            );
            $stmt->execute();
            $segments['high_value_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // Inactive (no orders)
            $stmt = $this->db->prepare(
                "SELECT COUNT(c.id) as count FROM customers c
                 WHERE c.id NOT IN (SELECT DISTINCT customer_id FROM orders)"
            );
            $stmt->execute();
            $segments['inactive_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            return [
                'success' => true,
                'segments' => $segments
            ];

        } catch (Exception $e) {
            error_log('Get segments error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customer cohort analysis
     */
    public function getCohortAnalysis($months = 6)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT DATE_TRUNC(DATE_SUB(NOW(), INTERVAL ? MONTH)) as cohort_month,
                        COUNT(DISTINCT customer_id) as customers,
                        SUM(total_amount) as revenue,
                        AVG(total_amount) as avg_order_value
                 FROM orders
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                 GROUP BY MONTH(created_at), YEAR(created_at)
                 ORDER BY created_at ASC"
            );
            $stmt->execute([$months, $months]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Cohort analysis error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Send marketing message to customer
     */
    public function sendMarketingMessage($customerId, $subject, $message)
    {
        try {
            // Store message in database
            $stmt = $this->db->prepare(
                "INSERT INTO customer_communications
                (customer_id, subject, message, type, status, created_at)
                VALUES (?, ?, ?, 'marketing', 'pending', NOW())"
            );

            // Check if table exists first, create if not
            try {
                $stmt->execute([$customerId, $subject, $message]);
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'customer_communications') !== false) {
                    // Table doesn't exist, create it
                    $this->db->exec(
                        "CREATE TABLE IF NOT EXISTS customer_communications (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            customer_id INT NOT NULL,
                            subject VARCHAR(255),
                            message TEXT,
                            type VARCHAR(50),
                            status VARCHAR(50),
                            created_at DATETIME,
                            sent_at DATETIME,
                            FOREIGN KEY (customer_id) REFERENCES customers(id)
                        )"
                    );
                    $stmt->execute([$customerId, $subject, $message]);
                } else {
                    throw $e;
                }
            }

            return [
                'success' => true,
                'message' => 'Message queued for sending'
            ];

        } catch (Exception $e) {
            error_log('Send message error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customer retention rate
     */
    public function getRetentionRate()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    (SELECT COUNT(DISTINCT customer_id) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)) as current_month,
                    (SELECT COUNT(DISTINCT customer_id) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)) as previous_month
                "
            );
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            $retention = $data['previous_month'] > 0
                ? round(($data['current_month'] / $data['previous_month']) * 100, 2)
                : 0;

            return [
                'success' => true,
                'retention_rate' => $retention,
                'current_month_customers' => $data['current_month'],
                'previous_month_customers' => $data['previous_month']
            ];

        } catch (Exception $e) {
            error_log('Retention rate error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
