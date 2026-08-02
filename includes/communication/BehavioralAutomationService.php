<?php

namespace GlamByMariga\Communication;

use PDO;
use Exception;

/**
 * Behavioral Automation Service
 * Handles automated triggers based on customer behavior
 */
class BehavioralAutomationService
{
    private $db;
    private $emailService;
    private $pushService;
    private $smsService;

    public function __construct(PDO $db, EmailService $emailService = null,
                               PushNotificationService $pushService = null,
                               SMSService $smsService = null)
    {
        $this->db = $db;
        $this->emailService = $emailService;
        $this->pushService = $pushService;
        $this->smsService = $smsService;
    }

    /**
     * Create automation rule
     */
    public function createRule($ruleData)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO automation_rules (name, description, trigger_type, trigger_condition,
                 action_type, action_config, target_segment, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)"
            );
            $stmt->execute([
                $ruleData['name'],
                $ruleData['description'] ?? null,
                $ruleData['trigger_type'],
                json_encode($ruleData['trigger_condition'] ?? []),
                $ruleData['action_type'],
                json_encode($ruleData['action_config'] ?? []),
                $ruleData['target_segment'] ?? 'all'
            ]);

            return [
                'success' => true,
                'rule_id' => $this->db->lastInsertId(),
                'message' => 'Rule created'
            ];

        } catch (Exception $e) {
            error_log('Create rule error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Track abandoned cart
     */
    public function trackAbandonedCart($customerId, $cartData, $cartValue)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO abandoned_carts (customer_id, cart_data, cart_value, abandoned_at)
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE cart_data = ?, cart_value = ?, abandoned_at = NOW()"
            );
            $cartJson = json_encode($cartData);
            $stmt->execute([$customerId, $cartJson, $cartValue, $cartJson, $cartValue]);

            return [
                'success' => true,
                'message' => 'Abandoned cart tracked'
            ];

        } catch (Exception $e) {
            error_log('Track abandoned cart error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process abandoned cart reminder
     */
    public function sendAbandonedCartReminder($customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM abandoned_carts WHERE customer_id = ? ORDER BY abandoned_at DESC LIMIT 1"
            );
            $stmt->execute([$customerId]);
            $cart = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cart || $cart['recovered_at']) {
                return ['success' => false, 'error' => 'No abandoned cart found'];
            }

            // Check if already reminded recently
            if ($cart['last_reminder_at'] && strtotime($cart['last_reminder_at']) > strtotime('-24 hours')) {
                return ['success' => false, 'error' => 'Already reminded recently'];
            }

            $customerStmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
            $customerStmt->execute([$customerId]);
            $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

            $cartData = json_decode($cart['cart_data'], true);
            $itemCount = count($cartData ?? []);
            $cartValue = $cart['cart_value'];

            // Send email
            if ($this->emailService) {
                $this->emailService->sendTemplate(
                    $customer['email'],
                    'abandoned_cart',
                    [
                        'customer_name' => $customer['name'],
                        'item_count' => $itemCount,
                        'cart_value' => number_format($cartValue, 2),
                        'recovery_url' => '/checkout?cart=' . base64_encode(json_encode($cartData))
                    ]
                );
            }

            // Send SMS if enabled
            if ($this->smsService && isset($customer['phone'])) {
                $this->smsService->send(
                    $customer['phone'],
                    "Hi {$customer['name']}, complete your purchase! You have {$itemCount} items in your cart. " .
                    "Click here to checkout: [RECOVERY_URL] - GlamByMariga"
                );
            }

            // Update reminder count
            $updateStmt = $this->db->prepare(
                "UPDATE abandoned_carts SET reminder_count = reminder_count + 1, last_reminder_at = NOW()
                 WHERE id = ?"
            );
            $updateStmt->execute([$cart['id']]);

            return [
                'success' => true,
                'message' => 'Abandoned cart reminder sent'
            ];

        } catch (Exception $e) {
            error_log('Send cart reminder error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate customer engagement score
     */
    public function calculateEngagementScore($customerId)
    {
        try {
            // Get email engagement metrics
            $emailStmt = $this->db->prepare(
                "SELECT
                    COUNT(*) as total_emails,
                    SUM(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) as opened_count,
                    SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked_count
                 FROM email_logs WHERE to_address = (SELECT email FROM customers WHERE id = ?)"
            );
            $emailStmt->execute([$customerId]);
            $emailMetrics = $emailStmt->fetch(PDO::FETCH_ASSOC);

            // Get order metrics
            $orderStmt = $this->db->prepare(
                "SELECT
                    COUNT(*) as order_count,
                    MAX(created_at) as last_order_date,
                    AVG(total) as avg_order_value,
                    SUM(total) as lifetime_value
                 FROM orders WHERE customer_id = ?"
            );
            $orderStmt->execute([$customerId]);
            $orderMetrics = $orderStmt->fetch(PDO::FETCH_ASSOC);

            // Calculate rates
            $emailOpenRate = $emailMetrics['total_emails'] > 0
                ? ($emailMetrics['opened_count'] / $emailMetrics['total_emails']) * 100
                : 0;
            $emailClickRate = $emailMetrics['total_emails'] > 0
                ? ($emailMetrics['clicked_count'] / $emailMetrics['total_emails']) * 100
                : 0;

            // Calculate days since last order
            $lastOrderDays = $orderMetrics['last_order_date']
                ? floor((time() - strtotime($orderMetrics['last_order_date'])) / 86400)
                : 999;

            // Calculate engagement score (0-100)
            $engagementScore = 0;
            $engagementScore += min($emailOpenRate * 0.3, 30);  // 30% from email opens
            $engagementScore += min($emailClickRate * 0.2, 20); // 20% from email clicks
            $engagementScore += min(($orderMetrics['order_count'] / 10) * 25, 25); // 25% from order frequency
            $engagementScore += min(($orderMetrics['lifetime_value'] / 100000) * 25, 25); // 25% from lifetime value

            // Detect at-risk customers
            $isAtRisk = $orderMetrics['order_count'] > 0 && $lastOrderDays > 90 && $engagementScore < 40;
            $riskScore = $isAtRisk ? 100 - $engagementScore : 0;

            // Update or insert engagement score
            $stmt = $this->db->prepare(
                "INSERT INTO customer_engagement_scores
                 (customer_id, engagement_score, email_open_rate, email_click_rate,
                  order_frequency, average_order_value, last_order_days, is_at_risk, risk_score)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 engagement_score = VALUES(engagement_score),
                 email_open_rate = VALUES(email_open_rate),
                 email_click_rate = VALUES(email_click_rate),
                 order_frequency = VALUES(order_frequency),
                 average_order_value = VALUES(average_order_value),
                 last_order_days = VALUES(last_order_days),
                 is_at_risk = VALUES(is_at_risk),
                 risk_score = VALUES(risk_score),
                 updated_at = NOW()"
            );
            $stmt->execute([
                $customerId,
                round($engagementScore, 2),
                round($emailOpenRate, 2),
                round($emailClickRate, 2),
                $orderMetrics['order_count'] ?? 0,
                $orderMetrics['avg_order_value'] ?? 0,
                $lastOrderDays,
                $isAtRisk ? 1 : 0,
                round($riskScore, 2)
            ]);

            return [
                'success' => true,
                'engagement_score' => round($engagementScore, 2),
                'is_at_risk' => $isAtRisk,
                'risk_score' => round($riskScore, 2)
            ];

        } catch (Exception $e) {
            error_log('Calculate engagement error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Trigger win-back campaign for at-risk customers
     */
    public function sendWinBackCampaign($customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.*, s.engagement_score, s.is_at_risk FROM customers c
                 LEFT JOIN customer_engagement_scores s ON c.id = s.customer_id
                 WHERE c.id = ?"
            );
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer || !$customer['is_at_risk']) {
                return ['success' => false, 'error' => 'Customer not at risk'];
            }

            // Create win-back offer (e.g., 20% discount)
            $couponCode = 'WINBACK' . strtoupper(substr(uniqid(), -5));

            if ($this->emailService) {
                $this->emailService->sendTemplate(
                    $customer['email'],
                    'win_back',
                    [
                        'customer_name' => $customer['name'],
                        'coupon_code' => $couponCode,
                        'discount_amount' => '20%',
                        'shop_url' => '/shop?coupon=' . $couponCode
                    ]
                );
            }

            // Log automation execution
            $logStmt = $this->db->prepare(
                "INSERT INTO automation_executions (rule_id, customer_id, action_taken, status, created_at)
                 VALUES (?, ?, ?, 'executed', NOW())"
            );
            $logStmt->execute([0, $customerId, 'win_back_email']);

            return [
                'success' => true,
                'coupon_code' => $couponCode,
                'message' => 'Win-back campaign sent'
            ];

        } catch (Exception $e) {
            error_log('Win-back campaign error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process all active automation rules
     */
    public function processAutomationRules()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM automation_rules WHERE is_active = TRUE
                 AND (max_executions = -1 OR execution_count < max_executions)"
            );
            $stmt->execute();
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results = [];

            foreach ($rules as $rule) {
                $result = $this->executeRule($rule);
                $results[] = [
                    'rule_id' => $rule['id'],
                    'rule_name' => $rule['name'],
                    'success' => $result['success'],
                    'executed' => $result['executed'] ?? 0
                ];
            }

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (Exception $e) {
            error_log('Process rules error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Execute individual automation rule
     */
    private function executeRule($rule)
    {
        try {
            $triggered = 0;
            $condition = json_decode($rule['trigger_condition'], true);
            $config = json_decode($rule['action_config'], true);

            switch ($rule['trigger_type']) {
                case 'abandoned_cart':
                    // Find carts abandoned for X hours
                    $hours = $condition['hours_ago'] ?? 1;
                    $stmt = $this->db->prepare(
                        "SELECT DISTINCT customer_id FROM abandoned_carts
                         WHERE abandoned_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
                         AND recovered_at IS NULL AND reminder_count < 3"
                    );
                    $stmt->execute([$hours]);
                    $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($customers) {
                        $this->sendAbandonedCartReminder($customers);
                        $triggered++;
                    }
                    break;

                case 'low_engagement':
                    // Find low engagement customers
                    $threshold = $condition['engagement_threshold'] ?? 30;
                    $stmt = $this->db->prepare(
                        "SELECT customer_id FROM customer_engagement_scores
                         WHERE engagement_score < ? AND is_at_risk = TRUE"
                    );
                    $stmt->execute([$threshold]);
                    $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($customers) {
                        $this->sendWinBackCampaign($customers);
                        $triggered++;
                    }
                    break;

                case 'birthday':
                    // Send birthday offers
                    $stmt = $this->db->prepare(
                        "SELECT id FROM customers
                         WHERE DATE_FORMAT(birth_date, '%m-%d') = DATE_FORMAT(NOW(), '%m-%d')"
                    );
                    $stmt->execute();
                    $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($customers as $customerId) {
                        if ($this->emailService) {
                            $customerStmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
                            $customerStmt->execute([$customerId]);
                            $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

                            $this->emailService->sendTemplate(
                                $customer['email'],
                                'birthday_offer',
                                [
                                    'customer_name' => $customer['name'],
                                    'discount_code' => $config['discount_code'] ?? 'BIRTHDAY20'
                                ]
                            );
                        }
                        $triggered++;
                    }
                    break;

                case 'reorder_due':
                    // Remind customers for repeat purchases
                    $days = $condition['days_since_order'] ?? 30;
                    $stmt = $this->db->prepare(
                        "SELECT DISTINCT customer_id FROM orders
                         WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                         GROUP BY customer_id"
                    );
                    $stmt->execute([$days]);
                    $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($customers as $customerId) {
                        if ($this->emailService) {
                            $customerStmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
                            $customerStmt->execute([$customerId]);
                            $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

                            $this->emailService->sendTemplate(
                                $customer['email'],
                                'reorder_reminder',
                                ['customer_name' => $customer['name']]
                            );
                        }
                        $triggered++;
                    }
                    break;
            }

            // Update execution count
            $updateStmt = $this->db->prepare(
                "UPDATE automation_rules SET execution_count = execution_count + 1 WHERE id = ?"
            );
            $updateStmt->execute([$rule['id']]);

            return ['success' => true, 'executed' => $triggered];

        } catch (Exception $e) {
            error_log('Execute rule error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get at-risk customers
     */
    public function getAtRiskCustomers($limit = 50)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.*, s.engagement_score, s.risk_score, s.last_order_days
                 FROM customers c
                 JOIN customer_engagement_scores s ON c.id = s.customer_id
                 WHERE s.is_at_risk = TRUE
                 ORDER BY s.risk_score DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get at-risk customers error: ' . $e->getMessage());
            return [];
        }
    }
}
