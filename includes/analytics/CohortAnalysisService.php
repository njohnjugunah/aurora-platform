<?php

namespace GlamByMariga\Analytics;

use PDO;
use Exception;

/**
 * Cohort Analysis Service
 * Customer cohort tracking and retention analysis
 */
class CohortAnalysisService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a customer cohort
     */
    public function createCohort($cohortName, $cohortType, $startDate, $endDate, $definition = [])
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO customer_cohorts
                 (cohort_name, cohort_type, cohort_start_date, cohort_end_date, cohort_definition)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $cohortName,
                $cohortType,
                $startDate,
                $endDate,
                json_encode($definition)
            ]);

            $cohortId = $this->db->lastInsertId();

            // Populate cohort members
            $this->populateCohortMembers($cohortId, $cohortType, $startDate, $endDate, $definition);

            return [
                'success' => true,
                'cohort_id' => $cohortId,
                'message' => 'Cohort created successfully'
            ];

        } catch (Exception $e) {
            error_log('Create cohort error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Populate cohort members based on criteria
     */
    private function populateCohortMembers($cohortId, $cohortType, $startDate, $endDate, $definition)
    {
        try {
            $query = "SELECT DISTINCT c.id FROM customers c ";
            $params = [];

            switch ($cohortType) {
                case 'signup_month':
                    $query .= "WHERE DATE_FORMAT(c.created_at, '%Y-%m') BETWEEN ? AND ?";
                    $params = [substr($startDate, 0, 7), substr($endDate, 0, 7)];
                    break;

                case 'first_purchase_month':
                    $query .= "JOIN orders o ON c.id = o.customer_id
                              WHERE o.created_at = (SELECT MIN(created_at) FROM orders WHERE customer_id = c.id)
                              AND DATE_FORMAT(o.created_at, '%Y-%m') BETWEEN ? AND ?";
                    $params = [substr($startDate, 0, 7), substr($endDate, 0, 7)];
                    break;

                case 'behavior':
                    // Custom behavior-based cohort
                    if (isset($definition['min_orders'])) {
                        $query .= "WHERE (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) >= ?";
                        $params[] = $definition['min_orders'];
                    }
                    break;

                case 'custom':
                    // Use custom SQL from definition
                    if (isset($definition['sql'])) {
                        $query = $definition['sql'];
                    }
                    break;
            }

            $stmt = $this->db->prepare($query);
            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Insert cohort members
            $insertStmt = $this->db->prepare(
                "INSERT INTO cohort_members (cohort_id, customer_id, joined_cohort_date)
                 VALUES (?, ?, NOW())"
            );

            foreach ($customers as $customerId) {
                $insertStmt->execute([$cohortId, $customerId]);
            }

            // Update cohort metadata
            $this->updateCohortMetadata($cohortId, count($customers));

        } catch (Exception $e) {
            error_log('Populate cohort members error: ' . $e->getMessage());
        }
    }

    /**
     * Update cohort metadata
     */
    private function updateCohortMetadata($cohortId, $customerCount)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    COUNT(DISTINCT cm.customer_id) as member_count,
                    COALESCE(SUM(o.total), 0) as total_revenue,
                    COALESCE(AVG(o.total), 0) as avg_customer_value,
                    COUNT(DISTINCT o.id) as total_orders
                 FROM cohort_members cm
                 LEFT JOIN orders o ON cm.customer_id = o.customer_id
                 WHERE cm.cohort_id = ?"
            );
            $stmt->execute([$cohortId]);
            $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calculate retention rates
            $retention1m = $this->calculateRetention($cohortId, 1);
            $retention3m = $this->calculateRetention($cohortId, 3);
            $retention6m = $this->calculateRetention($cohortId, 6);
            $retention12m = $this->calculateRetention($cohortId, 12);

            // Calculate average order value and frequency
            $avgOrderValue = $metrics['member_count'] > 0 ? $metrics['total_revenue'] / max(1, $metrics['member_count']) : 0;
            $avgFrequency = $metrics['member_count'] > 0 ? $metrics['total_orders'] / $metrics['member_count'] : 0;

            // Calculate churn rate
            $churnRate = 100 - $retention12m;

            // Calculate health score
            $healthScore = ($retention12m * 0.5) + ($avgFrequency * 10) + (min($avgOrderValue / 1000, 1) * 40);

            $updateStmt = $this->db->prepare(
                "UPDATE customer_cohorts
                 SET customer_count = ?,
                     total_revenue = ?,
                     avg_customer_value = ?,
                     retention_rate_1m = ?,
                     retention_rate_3m = ?,
                     retention_rate_6m = ?,
                     retention_rate_12m = ?,
                     avg_order_frequency = ?,
                     avg_order_value = ?,
                     churn_rate = ?,
                     health_score = ?,
                     last_analyzed = NOW()
                 WHERE id = ?"
            );
            $updateStmt->execute([
                $metrics['member_count'],
                $metrics['total_revenue'],
                $avgOrderValue,
                $retention1m,
                $retention3m,
                $retention6m,
                $retention12m,
                $avgFrequency,
                $avgOrderValue,
                $churnRate,
                $healthScore,
                $cohortId
            ]);

        } catch (Exception $e) {
            error_log('Update cohort metadata error: ' . $e->getMessage());
        }
    }

    /**
     * Calculate retention rate for cohort
     */
    private function calculateRetention($cohortId, $months)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    COUNT(DISTINCT cm.customer_id) as total_members,
                    SUM(CASE WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                            THEN 1 ELSE 0 END) as active_members
                 FROM cohort_members cm
                 LEFT JOIN orders o ON cm.customer_id = o.customer_id
                 WHERE cm.cohort_id = ?"
            );
            $stmt->execute([$months, $cohortId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total_members'] === 0) {
                return 0;
            }

            return round(($result['active_members'] / $result['total_members']) * 100, 2);

        } catch (Exception $e) {
            error_log('Calculate retention error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get cohort retention matrix
     */
    public function getCohortRetentionMatrix($cohortId)
    {
        try {
            // Create month columns dynamically
            $monthData = [];

            for ($month = 0; $month <= 12; $month++) {
                $stmt = $this->db->prepare(
                    "SELECT COUNT(DISTINCT cm.customer_id) as user_count,
                            COALESCE(SUM(o.total), 0) as revenue
                     FROM cohort_members cm
                     LEFT JOIN orders o ON cm.customer_id = o.customer_id
                     AND o.created_at >= DATE_ADD(cm.joined_cohort_date, INTERVAL ? MONTH)
                     AND o.created_at < DATE_ADD(cm.joined_cohort_date, INTERVAL ? MONTH)
                     WHERE cm.cohort_id = ?"
                );
                $stmt->execute([$month, $month + 1, $cohortId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                $monthData['month_' . $month . '_users'] = $result['user_count'];
                $monthData['month_' . $month . '_revenue'] = $result['revenue'];
            }

            return [
                'success' => true,
                'cohort_id' => $cohortId,
                'retention_matrix' => $monthData
            ];

        } catch (Exception $e) {
            error_log('Get retention matrix error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Compare cohorts
     */
    public function compareCohorts($cohortIds)
    {
        try {
            $placeholders = implode(',', array_fill(0, count($cohortIds), '?'));

            $stmt = $this->db->prepare(
                "SELECT
                    id, cohort_name, customer_count, avg_customer_value,
                    retention_rate_1m, retention_rate_3m, retention_rate_6m, retention_rate_12m,
                    avg_order_frequency, churn_rate, health_score
                 FROM customer_cohorts
                 WHERE id IN ($placeholders)
                 ORDER BY avg_customer_value DESC"
            );
            $stmt->execute($cohortIds);
            $cohorts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'cohorts' => $cohorts
            ];

        } catch (Exception $e) {
            error_log('Compare cohorts error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get cohort trends
     */
    public function getCohortTrends($limit = 10)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    cohort_name, customer_count, avg_customer_value,
                    retention_rate_12m, health_score, cohort_start_date
                 FROM customer_cohorts
                 WHERE is_active = TRUE
                 ORDER BY cohort_start_date DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            $cohorts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'cohorts' => $cohorts
            ];

        } catch (Exception $e) {
            error_log('Get trends error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get best performing cohorts
     */
    public function getBestPerformingCohorts($metric = 'health_score', $limit = 10)
    {
        try {
            $orderColumn = match($metric) {
                'retention' => 'retention_rate_12m',
                'value' => 'avg_customer_value',
                'churn' => 'churn_rate',
                default => 'health_score'
            };

            $orderDirection = $metric === 'churn' ? 'ASC' : 'DESC';

            $stmt = $this->db->prepare(
                "SELECT *
                 FROM customer_cohorts
                 WHERE is_active = TRUE
                 ORDER BY $orderColumn $orderDirection
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get best cohorts error: ' . $e->getMessage());
            return [];
        }
    }
}
