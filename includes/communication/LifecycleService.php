<?php

namespace GlamByMariga\Communication;

use PDO;
use Exception;

/**
 * Lifecycle Service
 * Customer lifecycle stage management and recommendations
 */
class LifecycleService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Initialize lifecycle stage definitions
     */
    public function initializeStageDefinitions()
    {
        try {
            $stages = [
                [
                    'stage_name' => 'prospect',
                    'description' => 'Not yet a customer',
                    'criteria' => json_encode(['no_orders' => true]),
                    'min_days_in_stage' => 0,
                    'max_days_in_stage' => 30,
                    'recommended_campaign_type' => 'welcome',
                    'engagement_goal' => 'First purchase',
                    'success_metric' => 'completed_purchase'
                ],
                [
                    'stage_name' => 'new_customer',
                    'description' => 'Just made first purchase',
                    'criteria' => json_encode(['order_count' => 1, 'days_since_signup' => 90]),
                    'min_days_in_stage' => 7,
                    'max_days_in_stage' => 90,
                    'recommended_campaign_type' => 'onboarding',
                    'engagement_goal' => 'Second purchase',
                    'success_metric' => 'second_purchase'
                ],
                [
                    'stage_name' => 'active',
                    'description' => 'Regular customer making purchases',
                    'criteria' => json_encode(['min_orders' => 2, 'recency_days' => 60]),
                    'min_days_in_stage' => 30,
                    'max_days_in_stage' => 365,
                    'recommended_campaign_type' => 'engagement',
                    'engagement_goal' => 'Increase frequency',
                    'success_metric' => 'increased_frequency'
                ],
                [
                    'stage_name' => 'at_risk',
                    'description' => 'Showing signs of disengagement',
                    'criteria' => json_encode(['recency_days' => 90, 'engagement_low' => true]),
                    'min_days_in_stage' => 1,
                    'max_days_in_stage' => 30,
                    'recommended_campaign_type' => 'win_back',
                    'engagement_goal' => 'Re-engagement',
                    'success_metric' => 'purchase_or_engagement'
                ],
                [
                    'stage_name' => 'inactive',
                    'description' => 'No activity for extended period',
                    'criteria' => json_encode(['recency_days' => 180]),
                    'min_days_in_stage' => 30,
                    'max_days_in_stage' => 999,
                    'recommended_campaign_type' => 'reactivation',
                    'engagement_goal' => 'Return to active',
                    'success_metric' => 'return_purchase'
                ],
                [
                    'stage_name' => 'loyal',
                    'description' => 'High-value repeat customer',
                    'criteria' => json_encode(['min_orders' => 5, 'min_ltv' => 25000]),
                    'min_days_in_stage' => 90,
                    'max_days_in_stage' => 999,
                    'recommended_campaign_type' => 'vip',
                    'engagement_goal' => 'Retention & advocacy',
                    'success_metric' => 'lifetime_value_growth'
                ],
                [
                    'stage_name' => 'vip',
                    'description' => 'Most valuable customers',
                    'criteria' => json_encode(['min_ltv' => 50000, 'min_orders' => 10]),
                    'min_days_in_stage' => 180,
                    'max_days_in_stage' => 999,
                    'recommended_campaign_type' => 'premium_vip',
                    'engagement_goal' => 'Exclusive experiences',
                    'success_metric' => 'advocacy_referrals'
                ]
            ];

            foreach ($stages as $stage) {
                $stmt = $this->db->prepare(
                    "INSERT IGNORE INTO lifecycle_stage_definitions
                     (stage_name, description, criteria, min_days_in_stage, max_days_in_stage,
                      recommended_campaign_type, engagement_goal, success_metric)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $stage['stage_name'],
                    $stage['description'],
                    $stage['criteria'],
                    $stage['min_days_in_stage'],
                    $stage['max_days_in_stage'],
                    $stage['recommended_campaign_type'],
                    $stage['engagement_goal'],
                    $stage['success_metric']
                ]);
            }

            return ['success' => true, 'message' => 'Stage definitions initialized'];

        } catch (Exception $e) {
            error_log('Initialize stages error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate customer lifecycle stage
     */
    public function calculateLifecycleStage($customerId)
    {
        try {
            // Get customer metrics
            $stmt = $this->db->prepare(
                "SELECT
                    DATEDIFF(NOW(), c.created_at) as days_since_signup,
                    COUNT(DISTINCT o.id) as order_count,
                    COALESCE(SUM(o.total), 0) as lifetime_value,
                    COALESCE(DATEDIFF(NOW(), MAX(o.created_at)), 999) as days_since_last_order,
                    COALESCE(ces.engagement_score, 50) as engagement_score
                 FROM customers c
                 LEFT JOIN orders o ON c.id = o.customer_id
                 LEFT JOIN customer_engagement_scores ces ON c.id = ces.customer_id
                 WHERE c.id = ?
                 GROUP BY c.id"
            );
            $stmt->execute([$customerId]);
            $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$metrics) {
                return ['success' => false, 'error' => 'Customer not found'];
            }

            // Determine stage
            $stage = $this->determineStage($metrics);

            // Get stage definition
            $stageStmt = $this->db->prepare(
                "SELECT * FROM lifecycle_stage_definitions WHERE stage_name = ?"
            );
            $stageStmt->execute([$stage]);
            $stageDef = $stageStmt->fetch(PDO::FETCH_ASSOC);

            // Get current stage info
            $currentStageStmt = $this->db->prepare(
                "SELECT current_stage, stage_start_date FROM customer_lifecycle_stages
                 WHERE customer_id = ?"
            );
            $currentStageStmt->execute([$customerId]);
            $currentStage = $currentStageStmt->fetch(PDO::FETCH_ASSOC);

            $stageChanged = !$currentStage || $currentStage['current_stage'] !== $stage;
            $daysInStage = $currentStage
                ? intval((time() - strtotime($currentStage['stage_start_date'])) / 86400)
                : 0;

            // Get recommended actions
            $actions = $this->getRecommendedActions($stage, $metrics);

            // Store/update lifecycle stage
            $upsertStmt = $this->db->prepare(
                "INSERT INTO customer_lifecycle_stages
                 (customer_id, current_stage, stage_start_date, days_in_stage, previous_stage,
                  stage_progression, recommended_actions)
                 VALUES (?, ?, NOW(), ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 current_stage = VALUES(current_stage),
                 days_in_stage = VALUES(days_in_stage),
                 stage_start_date = IF(current_stage != VALUES(current_stage), NOW(), stage_start_date),
                 previous_stage = current_stage,
                 recommended_actions = VALUES(recommended_actions),
                 updated_at = NOW()"
            );
            $upsertStmt->execute([
                $customerId,
                $stage,
                $daysInStage,
                $currentStage['current_stage'] ?? 'prospect',
                json_encode([$currentStage['current_stage'] => date('Y-m-d H:i:s')]),
                json_encode($actions)
            ]);

            return [
                'success' => true,
                'stage' => $stage,
                'stage_definition' => $stageDef,
                'days_in_stage' => $daysInStage,
                'stage_changed' => $stageChanged,
                'recommended_actions' => $actions,
                'metrics' => $metrics
            ];

        } catch (Exception $e) {
            error_log('Calculate lifecycle stage error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Determine stage based on metrics
     */
    private function determineStage($metrics)
    {
        $orders = $metrics['order_count'];
        $ltv = $metrics['lifetime_value'];
        $recency = $metrics['days_since_last_order'];
        $engagement = $metrics['engagement_score'];

        // VIP: High LTV and many orders
        if ($ltv >= 50000 && $orders >= 10) {
            return 'vip';
        }

        // Loyal: Multiple orders and reasonable LTV
        if ($orders >= 5 && $ltv >= 25000) {
            return 'loyal';
        }

        // Inactive: No recent activity
        if ($recency >= 180) {
            return 'inactive';
        }

        // At-Risk: Recent inactivity or low engagement
        if ($recency >= 90 || $engagement < 30) {
            return 'at_risk';
        }

        // Active: Regular purchases
        if ($orders >= 2 && $recency < 60) {
            return 'active';
        }

        // New Customer: First purchase within 90 days
        if ($orders === 1 && $metrics['days_since_signup'] <= 90) {
            return 'new_customer';
        }

        // Prospect: No orders yet
        return 'prospect';
    }

    /**
     * Get recommended actions for lifecycle stage
     */
    private function getRecommendedActions($stage, $metrics)
    {
        $actions = [
            'prospect' => [
                'Send welcome series',
                'Offer first-purchase discount',
                'Educational content',
                'Show social proof'
            ],
            'new_customer' => [
                'Send thank you email',
                'Onboarding sequence',
                'Product education',
                'Encourage second purchase',
                'Request feedback'
            ],
            'active' => [
                'Regular engagement campaigns',
                'Product recommendations',
                'Loyalty rewards',
                'Exclusive member offers',
                'Ask for referrals'
            ],
            'at_risk' => [
                'Send re-engagement campaign',
                'Offer special incentive',
                'Ask for feedback',
                'Win-back series',
                'Special offer limited time'
            ],
            'inactive' => [
                'Reactivation campaign',
                'Strong incentive offer',
                'Remind of previous products',
                'Share what\'s new',
                'Last chance offer'
            ],
            'loyal' => [
                'VIP recognition',
                'Exclusive early access',
                'Premium customer service',
                'Loyalty tier rewards',
                'Ask for testimonials'
            ],
            'vip' => [
                'Concierge service',
                'Exclusive events/previews',
                'Personal shopping assistance',
                'Custom offerings',
                'Ambassador program'
            ]
        ];

        return $actions[$stage] ?? $actions['prospect'];
    }

    /**
     * Get lifecycle stage distribution
     */
    public function getStageDistribution()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT current_stage, COUNT(*) as count
                 FROM customer_lifecycle_stages
                 GROUP BY current_stage"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get stage distribution error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customers by stage
     */
    public function getCustomersByStage($stage, $limit = 100)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.*, cls.current_stage, cls.days_in_stage, ces.engagement_score
                 FROM customers c
                 JOIN customer_lifecycle_stages cls ON c.id = cls.customer_id
                 LEFT JOIN customer_engagement_scores ces ON c.id = ces.customer_id
                 WHERE cls.current_stage = ?
                 ORDER BY cls.updated_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$stage, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get customers by stage error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Track stage progression
     */
    public function getStageProgression($customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT stage_progression, stage_start_date, previous_stage
                 FROM customer_lifecycle_stages
                 WHERE customer_id = ?"
            );
            $stmt->execute([$customerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    'success' => true,
                    'progression' => json_decode($result['stage_progression'], true),
                    'current_stage_since' => $result['stage_start_date'],
                    'previous_stage' => $result['previous_stage']
                ];
            }

            return ['success' => false, 'error' => 'No progression data'];

        } catch (Exception $e) {
            error_log('Get stage progression error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get stage health metrics
     */
    public function getStageHealthMetrics()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    cls.current_stage,
                    COUNT(cls.customer_id) as stage_size,
                    AVG(ces.engagement_score) as avg_engagement,
                    AVG(cp.churn_risk_score) as avg_churn_risk,
                    COUNT(CASE WHEN cp.churn_risk_score >= 70 THEN 1 END) as at_risk_count
                 FROM customer_lifecycle_stages cls
                 LEFT JOIN customer_engagement_scores ces ON cls.customer_id = ces.customer_id
                 LEFT JOIN churn_predictions cp ON cls.customer_id = cp.customer_id
                 GROUP BY cls.current_stage"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get stage health metrics error: ' . $e->getMessage());
            return [];
        }
    }
}
