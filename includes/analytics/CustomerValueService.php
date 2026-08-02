<?php

namespace GlamByMariga\Analytics;

use PDO;
use Exception;

/**
 * Customer Value Service
 * LTV prediction and customer segmentation
 */
class CustomerValueService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Predict customer lifetime value
     */
    public function predictCustomerLTV($customerId)
    {
        try {
            // Gather customer metrics
            $stmt = $this->db->prepare(
                "SELECT
                    c.id,
                    COUNT(DISTINCT o.id) as order_count,
                    COALESCE(SUM(o.total), 0) as total_spent,
                    COALESCE(AVG(o.total), 0) as avg_order_value,
                    COALESCE(DATEDIFF(NOW(), MAX(o.created_at)), 999) as days_since_last_order,
                    DATEDIFF(NOW(), c.created_at) as customer_age_days,
                    COALESCE(ces.engagement_score, 50) as engagement_score,
                    COALESCE(cp.churn_probability_90_days, 0) as churn_probability
                 FROM customers c
                 LEFT JOIN orders o ON c.id = o.customer_id
                 LEFT JOIN customer_engagement_scores ces ON c.id = ces.customer_id
                 LEFT JOIN churn_predictions cp ON c.id = cp.customer_id
                 WHERE c.id = ?
                 GROUP BY c.id"
            );
            $stmt->execute([$customerId]);
            $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$metrics) {
                return ['success' => false, 'error' => 'Customer not found'];
            }

            // Calculate current LTV
            $currentLTV = $metrics['total_spent'];

            // Calculate base LTV components
            $avgOrderValue = $metrics['avg_order_value'] ?? 0;
            $purchaseFrequency = $metrics['order_count'] > 0
                ? $metrics['order_count'] / max(1, $metrics['customer_age_days'] / 365)
                : 0;
            $customerLifespan = 5; // Assume 5-year relationship

            // Base LTV calculation
            $baseLTV = $avgOrderValue * $purchaseFrequency * $customerLifespan;

            // Adjust for engagement and churn risk
            $engagementMultiplier = 0.5 + ($metrics['engagement_score'] / 100) * 1.5;
            $churnMultiplier = 1 - ($metrics['churn_probability'] / 100);

            // Calculate predictions for different timeframes
            $ltv3Months = $this->calculateLTV($metrics, 3, $engagementMultiplier, $churnMultiplier);
            $ltv6Months = $this->calculateLTV($metrics, 6, $engagementMultiplier, $churnMultiplier);
            $ltv12Months = $this->calculateLTV($metrics, 12, $engagementMultiplier, $churnMultiplier);
            $ltv24Months = $this->calculateLTV($metrics, 24, $engagementMultiplier, $churnMultiplier);

            // Determine value segment and tier
            $tier = $this->determineValueTier($ltv12Months);
            $segment = $this->getValueSegment($metrics);
            $growthPotential = $this->calculateGrowthPotential($metrics, $baseLTV);
            $confidence = $this->calculateConfidence($metrics);

            // Store prediction
            $upsertStmt = $this->db->prepare(
                "INSERT INTO customer_ltv_predictions
                 (customer_id, predicted_ltv_3_months, predicted_ltv_6_months, predicted_ltv_12_months,
                  predicted_ltv_24_months, current_ltv, ltv_growth_potential, ltv_confidence_score,
                  value_segment, segment_tier, is_high_value)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 predicted_ltv_3_months = VALUES(predicted_ltv_3_months),
                 predicted_ltv_6_months = VALUES(predicted_ltv_6_months),
                 predicted_ltv_12_months = VALUES(predicted_ltv_12_months),
                 predicted_ltv_24_months = VALUES(predicted_ltv_24_months),
                 ltv_growth_potential = VALUES(ltv_growth_potential),
                 segment_tier = VALUES(segment_tier),
                 is_high_value = VALUES(is_high_value),
                 updated_at = NOW()"
            );
            $upsertStmt->execute([
                $customerId,
                round($ltv3Months, 2),
                round($ltv6Months, 2),
                round($ltv12Months, 2),
                round($ltv24Months, 2),
                round($currentLTV, 2),
                round($growthPotential, 2),
                $confidence,
                $segment,
                $tier,
                $tier === 'vip' || $tier === 'very_high' ? 1 : 0
            ]);

            return [
                'success' => true,
                'customer_id' => $customerId,
                'current_ltv' => round($currentLTV, 2),
                'predicted_ltv_3m' => round($ltv3Months, 2),
                'predicted_ltv_6m' => round($ltv6Months, 2),
                'predicted_ltv_12m' => round($ltv12Months, 2),
                'predicted_ltv_24m' => round($ltv24Months, 2),
                'segment_tier' => $tier,
                'value_segment' => $segment,
                'growth_potential' => round($growthPotential, 2),
                'confidence' => $confidence
            ];

        } catch (Exception $e) {
            error_log('Predict LTV error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate LTV for specific timeframe
     */
    private function calculateLTV($metrics, $months, $engagementMultiplier, $churnMultiplier)
    {
        $avgOrderValue = $metrics['avg_order_value'] ?? 100;
        $purchaseFrequency = max(0, $metrics['order_count'] / max(1, $metrics['customer_age_days'] / 30));

        // Predict orders in timeframe
        $predictedOrders = max(0, ($purchaseFrequency * $months) - 1);

        // Adjust for engagement and churn
        $adjustedOrders = $predictedOrders * $engagementMultiplier * $churnMultiplier;

        return $avgOrderValue * $adjustedOrders;
    }

    /**
     * Determine value tier based on LTV
     */
    private function determineValueTier($ltv12m)
    {
        if ($ltv12m >= 50000) {
            return 'vip';
        } elseif ($ltv12m >= 25000) {
            return 'very_high';
        } elseif ($ltv12m >= 10000) {
            return 'high';
        } elseif ($ltv12m >= 5000) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Get value segment
     */
    private function getValueSegment($metrics)
    {
        $segments = [];

        if ($metrics['order_count'] > 5) {
            $segments[] = 'high_frequency';
        }
        if ($metrics['total_spent'] > 50000) {
            $segments[] = 'high_spender';
        }
        if ($metrics['engagement_score'] > 70) {
            $segments[] = 'highly_engaged';
        }
        if ($metrics['days_since_last_order'] < 30) {
            $segments[] = 'recent_purchaser';
        }

        return implode(',', $segments) ?: 'standard';
    }

    /**
     * Calculate growth potential
     */
    private function calculateGrowthPotential($metrics, $baseLTV)
    {
        $currentLTV = $metrics['total_spent'];
        $potential = $baseLTV - $currentLTV;

        return max(0, $potential);
    }

    /**
     * Calculate prediction confidence
     */
    private function calculateConfidence($metrics)
    {
        $confidence = 50;

        if ($metrics['order_count'] > 10) {
            $confidence += 25;
        } elseif ($metrics['order_count'] > 5) {
            $confidence += 15;
        } elseif ($metrics['order_count'] > 2) {
            $confidence += 10;
        }

        if ($metrics['customer_age_days'] > 365) {
            $confidence += 20;
        }

        return min(95, $confidence);
    }

    /**
     * Get high-value customer opportunities
     */
    public function getHighValueOpportunities($limit = 50)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.*, clp.predicted_ltv_12_months, clp.ltv_growth_potential,
                        clp.segment_tier, clp.value_segment
                 FROM customers c
                 JOIN customer_ltv_predictions clp ON c.id = clp.customer_id
                 WHERE clp.ltv_growth_potential > 5000
                 ORDER BY clp.ltv_growth_potential DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get opportunities error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get segment performance
     */
    public function getSegmentPerformance()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    segment_tier,
                    COUNT(*) as customer_count,
                    SUM(predicted_ltv_12_months) as total_ltv,
                    AVG(predicted_ltv_12_months) as avg_ltv,
                    AVG(ltv_growth_potential) as avg_growth_potential,
                    SUM(is_high_value) as high_value_count
                 FROM customer_ltv_predictions
                 GROUP BY segment_tier
                 ORDER BY avg_ltv DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get segment performance error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Analyze LTV components
     */
    public function analyzeLTVComponents($customerId)
    {
        try {
            // Calculate base LTV
            $stmt = $this->db->prepare(
                "SELECT SUM(total) as base_ltv FROM orders WHERE customer_id = ?"
            );
            $stmt->execute([$customerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $baseLTV = $result['base_ltv'] ?? 0;

            // Calculate upsell potential
            $avgOrderValue = $this->getAverageOrderValue($customerId);
            $upsellPotential = $avgOrderValue * 0.2; // 20% of AOV

            // Calculate cross-sell potential
            $crosssellPotential = $avgOrderValue * 0.15; // 15% of AOV

            // Calculate retention value
            $retentionValue = $baseLTV * 0.3; // 30% of base LTV

            // Calculate referral value
            $referralValue = $avgOrderValue * 2; // 2 referred customers worth

            // Calculate advocacy value
            $advocacyValue = $baseLTV * 0.1; // 10% of base LTV

            $totalPotential = $baseLTV + $upsellPotential + $crosssellPotential
                            + $retentionValue + $referralValue + $advocacyValue;

            $expansion = $totalPotential - $baseLTV;

            // Get churn-adjusted LTV
            $churnStmt = $this->db->prepare(
                "SELECT churn_risk_score FROM churn_predictions WHERE customer_id = ?"
            );
            $churnStmt->execute([$customerId]);
            $churn = $churnStmt->fetch(PDO::FETCH_ASSOC);
            $churnAdjusted = $baseLTV * (1 - ($churn['churn_risk_score'] ?? 0) / 100);

            // Store components
            $insertStmt = $this->db->prepare(
                "INSERT INTO ltv_components
                 (customer_id, base_ltv, upsell_potential, cross_sell_potential, retention_value,
                  referral_value, brand_advocacy_value, total_potential_ltv, expansion_opportunity,
                  churn_risk_adjusted_ltv)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 base_ltv = VALUES(base_ltv),
                 upsell_potential = VALUES(upsell_potential),
                 cross_sell_potential = VALUES(cross_sell_potential),
                 retention_value = VALUES(retention_value),
                 total_potential_ltv = VALUES(total_potential_ltv),
                 expansion_opportunity = VALUES(expansion_opportunity),
                 churn_risk_adjusted_ltv = VALUES(churn_risk_adjusted_ltv)"
            );
            $insertStmt->execute([
                $customerId,
                round($baseLTV, 2),
                round($upsellPotential, 2),
                round($crosssellPotential, 2),
                round($retentionValue, 2),
                round($referralValue, 2),
                round($advocacyValue, 2),
                round($totalPotential, 2),
                round($expansion, 2),
                round($churnAdjusted, 2)
            ]);

            return [
                'success' => true,
                'base_ltv' => round($baseLTV, 2),
                'upsell_potential' => round($upsellPotential, 2),
                'cross_sell_potential' => round($crosssellPotential, 2),
                'retention_value' => round($retentionValue, 2),
                'referral_value' => round($referralValue, 2),
                'advocacy_value' => round($advocacyValue, 2),
                'total_potential' => round($totalPotential, 2),
                'expansion_opportunity' => round($expansion, 2),
                'churn_adjusted' => round($churnAdjusted, 2)
            ];

        } catch (Exception $e) {
            error_log('Analyze LTV components error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get average order value
     */
    private function getAverageOrderValue($customerId)
    {
        $stmt = $this->db->prepare(
            "SELECT AVG(total) as avg_value FROM orders WHERE customer_id = ?"
        );
        $stmt->execute([$customerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['avg_value'] ?? 0;
    }

    /**
     * Get value distribution dashboard data
     */
    public function getValueDistribution()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    segment_tier,
                    COUNT(*) as count,
                    SUM(predicted_ltv_12_months) as total_value,
                    AVG(predicted_ltv_12_months) as avg_value
                 FROM customer_ltv_predictions
                 GROUP BY segment_tier
                 ORDER BY avg_value DESC"
            );
            $stmt->execute();
            $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate percentages
            $totalCustomers = array_sum(array_column($distribution, 'count'));
            $totalValue = array_sum(array_column($distribution, 'total_value'));

            foreach ($distribution as &$tier) {
                $tier['percentage_of_customers'] = round(($tier['count'] / $totalCustomers) * 100, 2);
                $tier['percentage_of_value'] = round(($tier['total_value'] / $totalValue) * 100, 2);
            }

            return [
                'success' => true,
                'distribution' => $distribution,
                'total_customers' => $totalCustomers,
                'total_value' => round($totalValue, 2)
            ];

        } catch (Exception $e) {
            error_log('Get value distribution error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
