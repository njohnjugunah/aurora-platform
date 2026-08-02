<?php

namespace GlamByMariga\Communication;

use PDO;
use Exception;

/**
 * Predictive Service
 * Send-time optimization and predictive analytics
 */
class PredictiveService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Calculate optimal send time for customer
     */
    public function getOptimalSendTime($customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM customer_open_patterns WHERE customer_id = ?"
            );
            $stmt->execute([$customerId]);
            $pattern = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pattern) {
                return [
                    'success' => true,
                    'optimal_hour' => $pattern['optimal_send_hour'],
                    'optimal_day' => $pattern['optimal_send_day'],
                    'timezone' => $pattern['timezone'],
                    'open_rate_by_hour' => json_decode($pattern['open_rate_by_hour'], true)
                ];
            }

            return ['success' => false, 'error' => 'No pattern data available'];

        } catch (Exception $e) {
            error_log('Get optimal send time error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate customer open patterns from email logs
     */
    public function calculateCustomerOpenPatterns($customerId)
    {
        try {
            // Get customer timezone
            $customerStmt = $this->db->prepare("SELECT timezone FROM customers WHERE id = ?");
            $customerStmt->execute([$customerId]);
            $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
            $timezone = $customer['timezone'] ?? 'UTC';

            // Get email open data
            $stmt = $this->db->prepare(
                "SELECT opened_at FROM email_logs
                 WHERE to_address = (SELECT email FROM customers WHERE id = ?)
                 AND opened_at IS NOT NULL
                 ORDER BY opened_at DESC
                 LIMIT 100"
            );
            $stmt->execute([$customerId]);
            $opens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($opens)) {
                return ['success' => false, 'error' => 'Insufficient data'];
            }

            // Analyze patterns
            $dayPreferences = [];
            $hourPreferences = [];
            $opensByHour = array_fill(0, 24, 0);

            foreach ($opens as $open) {
                $date = new \DateTime($open['opened_at']);
                $hour = (int)$date->format('H');
                $day = $date->format('l');

                $dayPreferences[$day] = ($dayPreferences[$day] ?? 0) + 1;
                $opensByHour[$hour]++;
            }

            // Find optimal hour
            arsort($dayPreferences);
            $optimalDay = key($dayPreferences);
            $optimalHour = array_search(max($opensByHour), $opensByHour);

            // Calculate hour percentages
            $totalOpens = count($opens);
            $openRateByHour = [];
            for ($h = 0; $h < 24; $h++) {
                $openRateByHour[$h] = round(($opensByHour[$h] / $totalOpens) * 100, 2);
            }

            // Update or insert pattern
            $updateStmt = $this->db->prepare(
                "INSERT INTO customer_open_patterns
                 (customer_id, day_of_week_preferences, hour_preferences, timezone,
                  optimal_send_hour, optimal_send_day, open_rate_by_hour)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 day_of_week_preferences = VALUES(day_of_week_preferences),
                 hour_preferences = VALUES(hour_preferences),
                 optimal_send_hour = VALUES(optimal_send_hour),
                 optimal_send_day = VALUES(optimal_send_day),
                 open_rate_by_hour = VALUES(open_rate_by_hour),
                 last_calculated_at = NOW()"
            );
            $updateStmt->execute([
                $customerId,
                json_encode($dayPreferences),
                json_encode($hourPreferences),
                $timezone,
                $optimalHour,
                $optimalDay,
                json_encode($openRateByHour)
            ]);

            return [
                'success' => true,
                'optimal_hour' => $optimalHour,
                'optimal_day' => $optimalDay,
                'open_rate_by_hour' => $openRateByHour
            ];

        } catch (Exception $e) {
            error_log('Calculate patterns error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get segment-level optimal send time
     */
    public function getSegmentOptimalSendTime($segment)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM segment_send_times
                 WHERE segment = ? AND day_of_week = DAYNAME(NOW())"
            );
            $stmt->execute([$segment]);
            $sendTime = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($sendTime) {
                return [
                    'success' => true,
                    'optimal_hour' => $sendTime['optimal_hour'],
                    'avg_open_rate' => $sendTime['avg_open_rate'],
                    'avg_click_rate' => $sendTime['avg_click_rate']
                ];
            }

            return ['success' => false, 'error' => 'No segment data'];

        } catch (Exception $e) {
            error_log('Get segment send time error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate segment send times
     */
    public function calculateSegmentSendTimes($segment)
    {
        try {
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            foreach ($days as $day) {
                // Get email performance for this segment on this day
                $stmt = $this->db->prepare(
                    "SELECT AVG(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) as open_rate,
                            AVG(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as click_rate,
                            HOUR(sent_at) as send_hour,
                            COUNT(*) as count
                     FROM email_logs el
                     JOIN campaign_recipients cr ON el.campaign_id = cr.campaign_id
                     JOIN customers c ON cr.customer_id = c.id
                     WHERE DAYNAME(el.sent_at) = ?
                     AND (cr.segment = ? OR ? = 'all')
                     GROUP BY send_hour
                     ORDER BY open_rate DESC
                     LIMIT 1"
                );
                $stmt->execute([$day, $segment, $segment]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    $insertStmt = $this->db->prepare(
                        "INSERT INTO segment_send_times
                         (segment, day_of_week, optimal_hour, avg_open_rate, avg_click_rate, sample_size)
                         VALUES (?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                         optimal_hour = VALUES(optimal_hour),
                         avg_open_rate = VALUES(avg_open_rate),
                         avg_click_rate = VALUES(avg_click_rate),
                         sample_size = VALUES(sample_size),
                         last_updated = NOW()"
                    );
                    $insertStmt->execute([
                        $segment,
                        $day,
                        $result['send_hour'],
                        $result['open_rate'] * 100,
                        $result['click_rate'] * 100,
                        $result['count']
                    ]);
                }
            }

            return ['success' => true, 'message' => 'Segment send times calculated'];

        } catch (Exception $e) {
            error_log('Calculate segment send times error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Predict campaign performance
     */
    public function predictCampaignPerformance($campaignId)
    {
        try {
            // Get campaign and recipient data
            $stmt = $this->db->prepare(
                "SELECT c.*, COUNT(cr.id) as recipient_count
                 FROM marketing_campaigns c
                 LEFT JOIN campaign_recipients cr ON c.id = cr.campaign_id
                 WHERE c.id = ?
                 GROUP BY c.id"
            );
            $stmt->execute([$campaignId]);
            $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }

            // Get historical performance for similar campaigns
            $historyStmt = $this->db->prepare(
                "SELECT AVG(opened_count / recipients_count) as avg_open_rate,
                        AVG(clicked_count / recipients_count) as avg_click_rate,
                        COUNT(*) as similar_campaigns
                 FROM marketing_campaigns
                 WHERE target_segment = ? AND status = 'sent'
                 AND created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)"
            );
            $historyStmt->execute([$campaign['target_segment']]);
            $history = $historyStmt->fetch(PDO::FETCH_ASSOC);

            // Calculate predictions
            $baselineOpenRate = $history['avg_open_rate'] ?? 0.35;
            $baselineClickRate = $history['avg_click_rate'] ?? 0.12;
            $baselineConversionRate = 0.03; // Typical e-commerce conversion

            // Apply confidence based on historical data
            $confidence = min(95, 50 + ($history['similar_campaigns'] ?? 0) * 5);

            $predictedOpens = $campaign['recipient_count'] * $baselineOpenRate;
            $predictedClicks = $predictedOpens * $baselineClickRate;
            $predictedConversions = $predictedClicks * $baselineConversionRate;
            $predictedRevenue = $predictedConversions * 2500; // Avg order value

            // Store prediction
            $insertStmt = $this->db->prepare(
                "INSERT INTO predictive_campaign_performance
                 (campaign_id, predicted_open_rate, predicted_click_rate, predicted_conversion_rate,
                  predicted_revenue, confidence_interval)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $insertStmt->execute([
                $campaignId,
                $baselineOpenRate * 100,
                $baselineClickRate * 100,
                $baselineConversionRate * 100,
                $predictedRevenue,
                $confidence
            ]);

            return [
                'success' => true,
                'predicted_open_rate' => round($baselineOpenRate * 100, 2),
                'predicted_click_rate' => round($baselineClickRate * 100, 2),
                'predicted_conversion_rate' => round($baselineConversionRate * 100, 2),
                'predicted_revenue' => round($predictedRevenue, 2),
                'confidence' => $confidence,
                'based_on_campaigns' => $history['similar_campaigns'] ?? 0
            ];

        } catch (Exception $e) {
            error_log('Predict campaign error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get predictive analytics dashboard data
     */
    public function getDashboardMetrics()
    {
        try {
            // Get average prediction accuracy
            $accuracyStmt = $this->db->prepare(
                "SELECT AVG(prediction_accuracy) as avg_accuracy,
                        COUNT(*) as predictions_made
                 FROM predictive_campaign_performance
                 WHERE actual_open_rate IS NOT NULL"
            );
            $accuracyStmt->execute();
            $accuracy = $accuracyStmt->fetch(PDO::FETCH_ASSOC);

            // Get top performing subject lines
            $subjectStmt = $this->db->prepare(
                "SELECT subject_line, open_rate, click_rate, sent_count
                 FROM subject_line_performance
                 WHERE sent_count > 10
                 ORDER BY open_rate DESC
                 LIMIT 5"
            );
            $subjectStmt->execute();
            $topSubjects = $subjectStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get lifecycle distribution
            $lifecycleStmt = $this->db->prepare(
                "SELECT current_stage, COUNT(*) as count
                 FROM customer_lifecycle_stages
                 GROUP BY current_stage"
            );
            $lifecycleStmt->execute();
            $lifecycle = $lifecycleStmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'prediction_accuracy' => round($accuracy['avg_accuracy'] ?? 0, 2),
                'predictions_made' => $accuracy['predictions_made'] ?? 0,
                'top_subject_lines' => $topSubjects,
                'lifecycle_distribution' => $lifecycle
            ];

        } catch (Exception $e) {
            error_log('Get dashboard metrics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
