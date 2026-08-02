<?php

namespace GlamByMariga\Communication;

use PDO;
use Exception;

/**
 * Churn Prediction Service
 * Predicts customer churn risk and triggers interventions
 */
class ChurnPredictionService
{
    private $db;
    private $automationService;

    public function __construct(PDO $db, BehavioralAutomationService $automationService = null)
    {
        $this->db = $db;
        $this->automationService = $automationService;
    }

    /**
     * Calculate churn probability for customer
     */
    public function calculateChurnProbability($customerId)
    {
        try {
            // Gather feature data
            $features = $this->gatherCustomerFeatures($customerId);

            if (!$features['success']) {
                return ['success' => false, 'error' => 'Insufficient customer data'];
            }

            $data = $features['data'];

            // Calculate risk factors
            $riskScore = $this->calculateRiskScore($data);
            $churnProb30 = $this->predictChurnProbability($data, 30);
            $churnProb60 = $this->predictChurnProbability($data, 60);
            $churnProb90 = $this->predictChurnProbability($data, 90);

            // Identify primary risk factor
            $riskFactors = $this->identifyRiskFactors($data);

            // Store prediction
            $stmt = $this->db->prepare(
                "INSERT INTO churn_predictions
                 (customer_id, churn_risk_score, churn_probability_30_days,
                  churn_probability_60_days, churn_probability_90_days,
                  primary_risk_factor, secondary_risk_factors, predicted_churn_date, confidence_score)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 churn_risk_score = VALUES(churn_risk_score),
                 churn_probability_30_days = VALUES(churn_probability_30_days),
                 churn_probability_60_days = VALUES(churn_probability_60_days),
                 churn_probability_90_days = VALUES(churn_probability_90_days),
                 primary_risk_factor = VALUES(primary_risk_factor),
                 secondary_risk_factors = VALUES(secondary_risk_factors),
                 predicted_churn_date = VALUES(predicted_churn_date),
                 confidence_score = VALUES(confidence_score),
                 calculated_at = NOW()"
            );

            $predictedChurnDate = date('Y-m-d', strtotime('+' . round($churnProb90 * 90 / 100) . ' days'));

            $stmt->execute([
                $customerId,
                $riskScore,
                $churnProb30,
                $churnProb60,
                $churnProb90,
                $riskFactors['primary'],
                json_encode($riskFactors['secondary']),
                $predictedChurnDate,
                $this->calculateConfidenceScore($data)
            ]);

            return [
                'success' => true,
                'churn_risk_score' => $riskScore,
                'churn_probability_30' => $churnProb30,
                'churn_probability_60' => $churnProb60,
                'churn_probability_90' => $churnProb90,
                'primary_risk_factor' => $riskFactors['primary'],
                'predicted_churn_date' => $predictedChurnDate,
                'confidence_score' => $this->calculateConfidenceScore($data)
            ];

        } catch (Exception $e) {
            error_log('Calculate churn probability error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Gather customer features for prediction
     */
    private function gatherCustomerFeatures($customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    DATEDIFF(NOW(), c.created_at) as days_since_signup,
                    COUNT(DISTINCT o.id) as total_orders,
                    COALESCE(SUM(o.total), 0) as total_spent,
                    COALESCE(AVG(o.total), 0) as avg_order_value,
                    COALESCE(DATEDIFF(NOW(), MAX(o.created_at)), NULL) as days_since_last_order,
                    COALESCE(el.open_count, 0) as email_opens,
                    COALESCE(el.total_emails, 0) as total_emails,
                    COALESCE(el.click_count, 0) as email_clicks
                 FROM customers c
                 LEFT JOIN orders o ON c.id = o.customer_id
                 LEFT JOIN (
                    SELECT to_address,
                           SUM(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) as open_count,
                           SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as click_count,
                           COUNT(*) as total_emails
                    FROM email_logs
                    WHERE opened_at > DATE_SUB(NOW(), INTERVAL 90 DAY)
                    GROUP BY to_address
                 ) el ON c.email = el.to_address
                 WHERE c.id = ?
                 GROUP BY c.id"
            );
            $stmt->execute([$customerId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return ['success' => false, 'error' => 'Customer not found'];
            }

            // Get engagement score
            $engagementStmt = $this->db->prepare(
                "SELECT engagement_score FROM customer_engagement_scores WHERE customer_id = ?"
            );
            $engagementStmt->execute([$customerId]);
            $engagement = $engagementStmt->fetch(PDO::FETCH_ASSOC);
            $data['engagement_score'] = $engagement['engagement_score'] ?? 50;

            // Calculate derived metrics
            $data['email_open_rate'] = $data['total_emails'] > 0
                ? ($data['email_opens'] / $data['total_emails']) * 100
                : 0;
            $data['email_click_rate'] = $data['total_emails'] > 0
                ? ($data['email_clicks'] / $data['total_emails']) * 100
                : 0;
            $data['recency'] = $data['days_since_last_order'] ?? 999;
            $data['frequency'] = $data['total_orders'];
            $data['monetary'] = $data['total_spent'];

            return ['success' => true, 'data' => $data];

        } catch (Exception $e) {
            error_log('Gather features error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate churn risk score (0-100)
     */
    private function calculateRiskScore($data)
    {
        $riskScore = 0;

        // Recency (days since last order) - most important factor
        if ($data['recency'] > 90) {
            $riskScore += 40;
        } elseif ($data['recency'] > 60) {
            $riskScore += 25;
        } elseif ($data['recency'] > 30) {
            $riskScore += 10;
        }

        // Frequency (number of purchases)
        if ($data['frequency'] === 1) {
            $riskScore += 25; // One-time buyers at higher risk
        } elseif ($data['frequency'] < 3) {
            $riskScore += 15;
        }

        // Email engagement
        if ($data['email_open_rate'] < 20) {
            $riskScore += 15;
        } elseif ($data['email_open_rate'] < 40) {
            $riskScore += 8;
        }

        // Overall engagement score
        if ($data['engagement_score'] < 30) {
            $riskScore += 20;
        } elseif ($data['engagement_score'] < 50) {
            $riskScore += 10;
        }

        // Account age (newer customers more risky)
        if ($data['days_since_signup'] < 90) {
            $riskScore += 10;
        }

        return min(100, $riskScore);
    }

    /**
     * Predict churn probability for specific timeframe
     */
    private function predictChurnProbability($data, $days)
    {
        $baselineProbability = 0.15; // 15% baseline churn

        // Adjust based on recency
        if ($data['recency'] > $days) {
            return 95; // Very likely to have churned
        }

        // RFM-based probability
        $recencyScore = min(1, $data['recency'] / $days);
        $frequencyScore = max(0, 1 - ($data['frequency'] / 10));
        $monetaryScore = max(0, 1 - ($data['monetary'] / 50000));

        $riskMultiplier = ($recencyScore * 0.5) + ($frequencyScore * 0.3) + ($monetaryScore * 0.2);

        $probability = $baselineProbability + ($riskMultiplier * 0.85);

        return min(99, max(1, round($probability * 100, 2)));
    }

    /**
     * Identify risk factors
     */
    private function identifyRiskFactors($data)
    {
        $factors = [];

        if ($data['recency'] > 90) {
            $factors[] = 'No recent purchases (>90 days)';
        }
        if ($data['frequency'] === 1) {
            $factors[] = 'First-time customer';
        }
        if ($data['email_open_rate'] < 20) {
            $factors[] = 'Low email engagement';
        }
        if ($data['engagement_score'] < 30) {
            $factors[] = 'Overall low engagement';
        }
        if ($data['total_spent'] < 5000) {
            $factors[] = 'Low lifetime value';
        }

        return [
            'primary' => array_shift($factors) ?? 'No activity',
            'secondary' => $factors
        ];
    }

    /**
     * Calculate confidence score based on data availability
     */
    private function calculateConfidenceScore($data)
    {
        $confidence = 50; // Base confidence

        if ($data['total_orders'] > 5) {
            $confidence += 20;
        } elseif ($data['total_orders'] > 2) {
            $confidence += 10;
        }

        if ($data['days_since_signup'] > 180) {
            $confidence += 15;
        }

        if ($data['total_emails'] > 10) {
            $confidence += 15;
        }

        return min(95, $confidence);
    }

    /**
     * Get high-risk customers for intervention
     */
    public function getHighRiskCustomers($riskThreshold = 70, $limit = 50)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT cp.*, c.email, c.name, c.phone,
                        ces.engagement_score
                 FROM churn_predictions cp
                 JOIN customers c ON cp.customer_id = c.id
                 LEFT JOIN customer_engagement_scores ces ON cp.customer_id = ces.customer_id
                 WHERE cp.churn_risk_score >= ?
                 AND cp.intervention_sent = FALSE
                 ORDER BY cp.churn_risk_score DESC
                 LIMIT ?"
            );
            $stmt->execute([$riskThreshold, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get high risk customers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Send intervention campaign for at-risk customer
     */
    public function sendInterventionCampaign($customerId, $campaignId)
    {
        try {
            // Mark intervention as sent
            $stmt = $this->db->prepare(
                "UPDATE churn_predictions
                 SET intervention_sent = TRUE,
                     intervention_campaign_id = ?,
                     intervention_sent_at = NOW()
                 WHERE customer_id = ?"
            );
            $stmt->execute([$campaignId, $customerId]);

            return ['success' => true, 'message' => 'Intervention campaign sent'];

        } catch (Exception $e) {
            error_log('Send intervention error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Mark customer as retained
     */
    public function markAsRetained($customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE churn_predictions
                 SET customer_retained = TRUE,
                     retained_at = NOW()
                 WHERE customer_id = ?"
            );
            $stmt->execute([$customerId]);

            return ['success' => true];

        } catch (Exception $e) {
            error_log('Mark retained error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get churn prediction analytics
     */
    public function getAnalytics()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    COUNT(*) as total_predictions,
                    SUM(CASE WHEN churn_risk_score >= 70 THEN 1 ELSE 0 END) as high_risk_count,
                    AVG(churn_risk_score) as avg_risk_score,
                    SUM(CASE WHEN intervention_sent = TRUE THEN 1 ELSE 0 END) as interventions_sent,
                    SUM(CASE WHEN customer_retained = TRUE THEN 1 ELSE 0 END) as customers_retained
                 FROM churn_predictions"
            );
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get analytics error: ' . $e->getMessage());
            return null;
        }
    }
}
