<?php

namespace GlamByMariga\Analytics;

use PDO;
use Exception;

/**
 * Journey Service
 * Customer journey mapping and stage analysis
 */
class JourneyService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Map customer journey
     */
    public function mapCustomerJourney($customerId)
    {
        try {
            // Define journey stages
            $stages = [
                'awareness' => 'First website visit or ad impression',
                'consideration' => 'Email opened or content viewed',
                'decision' => 'First purchase',
                'retention' => 'Repeat purchase',
                'advocacy' => 'Referral or review'
            ];

            // Get first touchpoint (awareness)
            $awarenessStmt = $this->db->prepare(
                "SELECT MIN(created_at) as date FROM customer_touchpoints WHERE customer_id = ?"
            );
            $awarenessStmt->execute([$customerId]);
            $awareness = $awarenessStmt->fetch(PDO::FETCH_ASSOC);

            // Get first email open (consideration)
            $considerationStmt = $this->db->prepare(
                "SELECT MIN(opened_at) as date FROM email_logs WHERE to_address =
                    (SELECT email FROM customers WHERE id = ?) AND opened_at IS NOT NULL"
            );
            $considerationStmt->execute([$customerId]);
            $consideration = $considerationStmt->fetch(PDO::FETCH_ASSOC);

            // Get first purchase (decision)
            $decisionStmt = $this->db->prepare(
                "SELECT MIN(created_at) as date FROM orders WHERE customer_id = ?"
            );
            $decisionStmt->execute([$customerId]);
            $decision = $decisionStmt->fetch(PDO::FETCH_ASSOC);

            // Get second purchase or retention indicator (retention)
            $retentionStmt = $this->db->prepare(
                "SELECT MIN(created_at) as date FROM orders WHERE customer_id = ? AND created_at >
                    (SELECT MIN(created_at) FROM orders WHERE customer_id = ?)"
            );
            $retentionStmt->execute([$customerId, $customerId]);
            $retention = $retentionStmt->fetch(PDO::FETCH_ASSOC);

            // Determine current stage
            $currentStage = $this->determineCurrentStage($customerId);

            // Calculate journey metrics
            $journeyPath = $this->buildJourneyPath($awareness, $consideration, $decision, $retention);
            $stageDurations = $this->calculateStageDurations($awareness, $consideration, $decision, $retention);

            // Store journey mapping
            $stmt = $this->db->prepare(
                "INSERT INTO customer_journey_stages
                 (customer_id, awareness_stage_date, consideration_stage_date, decision_stage_date,
                  retention_stage_date, current_stage, avg_stage_duration, total_journey_duration,
                  conversion_path_length, conversion_path, is_converged)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 current_stage = VALUES(current_stage),
                 conversion_path = VALUES(conversion_path),
                 is_converged = VALUES(is_converged)"
            );

            $totalDuration = $this->calculateTotalDuration($awareness, $retention ?? $decision);
            $avgDuration = array_sum(array_values($stageDurations)) / max(1, count($stageDurations));

            $stmt->execute([
                $customerId,
                $awareness['date'] ?? null,
                $consideration['date'] ?? null,
                $decision['date'] ?? null,
                $retention['date'] ?? null,
                $currentStage,
                round($avgDuration, 2),
                $totalDuration,
                count(array_filter($journeyPath)),
                json_encode($journeyPath),
                $currentStage === 'retention' || $currentStage === 'advocacy' ? 1 : 0
            ]);

            return [
                'success' => true,
                'customer_id' => $customerId,
                'current_stage' => $currentStage,
                'journey_path' => $journeyPath,
                'stage_durations' => $stageDurations,
                'total_duration' => $totalDuration
            ];

        } catch (Exception $e) {
            error_log('Map journey error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Determine current stage
     */
    private function determineCurrentStage($customerId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                MIN(created_at) as first_touchpoint,
                (SELECT MIN(opened_at) FROM email_logs WHERE to_address =
                    (SELECT email FROM customers WHERE id = ?) AND opened_at IS NOT NULL) as first_email_open,
                (SELECT MIN(created_at) FROM orders WHERE customer_id = ?) as first_purchase,
                (SELECT COUNT(*) FROM orders WHERE customer_id = ?) as order_count
             FROM customer_touchpoints WHERE customer_id = ?"
        );
        $stmt->execute([$customerId, $customerId, $customerId, $customerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result['first_purchase']) {
            if ($result['first_email_open']) {
                return 'consideration';
            } elseif ($result['first_touchpoint']) {
                return 'awareness';
            }
            return 'awareness';
        }

        if ($result['order_count'] > 1) {
            return 'retention';
        }

        return 'decision';
    }

    /**
     * Build journey path
     */
    private function buildJourneyPath($awareness, $consideration, $decision, $retention)
    {
        $path = [
            'awareness' => $awareness['date'] ?? null,
            'consideration' => $consideration['date'] ?? null,
            'decision' => $decision['date'] ?? null,
            'retention' => $retention['date'] ?? null,
            'advocacy' => null // Would be set by referrals
        ];

        return $path;
    }

    /**
     * Calculate stage durations (in days)
     */
    private function calculateStageDurations($awareness, $consideration, $decision, $retention)
    {
        $durations = [];

        if ($awareness['date'] && $consideration['date']) {
            $durations['awareness_to_consideration'] =
                round((strtotime($consideration['date']) - strtotime($awareness['date'])) / 86400, 1);
        }

        if ($consideration['date'] && $decision['date']) {
            $durations['consideration_to_decision'] =
                round((strtotime($decision['date']) - strtotime($consideration['date'])) / 86400, 1);
        }

        if ($decision['date'] && $retention['date']) {
            $durations['decision_to_retention'] =
                round((strtotime($retention['date']) - strtotime($decision['date'])) / 86400, 1);
        }

        return $durations;
    }

    /**
     * Calculate total journey duration
     */
    private function calculateTotalDuration($start, $end)
    {
        if (!$start['date'] || !$end['date']) {
            return 0;
        }

        return round((strtotime($end['date']) - strtotime($start['date'])) / 86400, 1);
    }

    /**
     * Get journey stage conversions
     */
    public function getStageConversions()
    {
        try {
            $transitions = [
                ['from' => 'awareness', 'to' => 'consideration'],
                ['from' => 'consideration', 'to' => 'decision'],
                ['from' => 'decision', 'to' => 'retention'],
                ['from' => 'retention', 'to' => 'advocacy']
            ];

            $results = [];

            foreach ($transitions as $transition) {
                $stmt = $this->db->prepare(
                    "SELECT
                        COUNT(*) as total_customers,
                        SUM(CASE WHEN ? IS NOT NULL THEN 1 ELSE 0 END) as converted
                     FROM customer_journey_stages
                     WHERE ? IS NOT NULL"
                );
                $stmt->execute([
                    $transition['to'] . '_stage_date',
                    $transition['from'] . '_stage_date'
                ]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);

                $conversionRate = $data['total_customers'] > 0
                    ? round(($data['converted'] / $data['total_customers']) * 100, 2)
                    : 0;

                $results[] = [
                    'from_stage' => $transition['from'],
                    'to_stage' => $transition['to'],
                    'total_customers' => $data['total_customers'],
                    'converted' => $data['converted'],
                    'conversion_rate' => $conversionRate
                ];
            }

            return [
                'success' => true,
                'stage_conversions' => $results
            ];

        } catch (Exception $e) {
            error_log('Get stage conversions error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get customers by journey stage
     */
    public function getCustomersByStage($stage, $limit = 100)
    {
        try {
            $stageColumn = $stage . '_stage_date';

            $stmt = $this->db->prepare(
                "SELECT c.*, cjs.current_stage, cjs.total_journey_duration
                 FROM customers c
                 JOIN customer_journey_stages cjs ON c.id = cjs.customer_id
                 WHERE cjs.current_stage = ?
                 ORDER BY c.created_at DESC
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
     * Get average journey duration
     */
    public function getAverageJourneyMetrics()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    AVG(DATEDIFF(decision_stage_date, awareness_stage_date)) as avg_to_first_purchase,
                    AVG(DATEDIFF(retention_stage_date, decision_stage_date)) as avg_to_repeat,
                    AVG(total_journey_duration) as avg_total_duration,
                    SUM(CASE WHEN is_converged = TRUE THEN 1 ELSE 0 END) as converted_customers,
                    COUNT(*) as total_customers
                 FROM customer_journey_stages"
            );
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'avg_to_first_purchase_days' => round($result['avg_to_first_purchase'] ?? 0, 1),
                'avg_to_repeat_purchase_days' => round($result['avg_to_repeat'] ?? 0, 1),
                'avg_total_journey_days' => round($result['avg_total_duration'] ?? 0, 1),
                'conversion_rate' => round(($result['converted_customers'] / max(1, $result['total_customers'])) * 100, 2)
            ];

        } catch (Exception $e) {
            error_log('Get metrics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get most common journey paths
     */
    public function getMostCommonPaths($limit = 10)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    conversion_path,
                    COUNT(*) as path_count,
                    AVG(total_journey_duration) as avg_duration
                 FROM customer_journey_stages
                 WHERE is_converged = TRUE
                 GROUP BY conversion_path
                 ORDER BY path_count DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get paths error: ' . $e->getMessage());
            return [];
        }
    }
}
