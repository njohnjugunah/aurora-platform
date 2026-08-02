<?php

namespace GlamByMariga\Communication;

use PDO;
use Exception;

/**
 * Campaign Service
 * Advanced campaign scheduling, A/B testing, and lifecycle management
 */
class CampaignService
{
    private $db;
    private $emailService;

    public function __construct(PDO $db, EmailService $emailService = null)
    {
        $this->db = $db;
        $this->emailService = $emailService;
    }

    /**
     * Create marketing campaign
     */
    public function createCampaign($data)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO marketing_campaigns (name, description, template_id, target_segment, status, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['template_id'] ?? null,
                $data['target_segment'] ?? 'all',
                'draft'
            ]);

            return [
                'success' => true,
                'campaign_id' => $this->db->lastInsertId(),
                'message' => 'Campaign created'
            ];

        } catch (Exception $e) {
            error_log('Create campaign error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Schedule campaign with recurrence support
     */
    public function scheduleCampaign($campaignId, $scheduleData)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO campaign_schedules (campaign_id, schedule_type, send_at, recurrence_pattern,
                 next_send_at, timezone, optimal_send_time, optimal_send_hour, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)"
            );

            $nextSend = $scheduleData['send_at'] ?? date('Y-m-d H:i:s', strtotime('+1 hour'));
            $timezone = $scheduleData['timezone'] ?? 'UTC';

            $stmt->execute([
                $campaignId,
                $scheduleData['schedule_type'] ?? 'scheduled',
                $scheduleData['send_at'] ?? null,
                $scheduleData['recurrence_pattern'] ?? null,
                $nextSend,
                $timezone,
                $scheduleData['optimal_send_time'] ?? false,
                $scheduleData['optimal_send_hour'] ?? null
            ]);

            return [
                'success' => true,
                'schedule_id' => $this->db->lastInsertId(),
                'message' => 'Campaign scheduled'
            ];

        } catch (Exception $e) {
            error_log('Schedule campaign error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get target segment recipients
     */
    public function getSegmentRecipients($segment)
    {
        try {
            $query = "SELECT c.id, c.email, c.name FROM customers c";

            switch ($segment) {
                case 'repeat':
                    $query .= " WHERE (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) > 1";
                    break;
                case 'high_value':
                    $query .= " WHERE (SELECT SUM(total) FROM orders WHERE customer_id = c.id) > 50000";
                    break;
                case 'inactive':
                    $query .= " WHERE (SELECT MAX(created_at) FROM orders WHERE customer_id = c.id) < DATE_SUB(NOW(), INTERVAL 90 DAY)";
                    break;
                case 'new':
                    $query .= " WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                default:
                    // 'all' or other segments
                    break;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get segment error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create A/B test for campaign
     */
    public function createABTest($campaignId, $testData)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO campaign_ab_tests (campaign_id, name, variant_a_template_id, variant_b_template_id,
                 variant_a_subject, variant_b_subject, split_percentage, metric, test_duration_days, started_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $campaignId,
                $testData['name'],
                $testData['variant_a_template_id'],
                $testData['variant_b_template_id'],
                $testData['variant_a_subject'] ?? null,
                $testData['variant_b_subject'] ?? null,
                $testData['split_percentage'] ?? 50,
                $testData['metric'] ?? 'open_rate',
                $testData['test_duration_days'] ?? 3
            ]);

            return [
                'success' => true,
                'test_id' => $this->db->lastInsertId(),
                'message' => 'A/B test created'
            ];

        } catch (Exception $e) {
            error_log('Create A/B test error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Assign recipients to A/B test variants
     */
    public function assignABTestRecipients($testId, $recipients)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO campaign_ab_test_recipients (test_id, campaign_recipient_id, variant, created_at)
                 VALUES (?, ?, ?, NOW())"
            );

            $variantA = floor(count($recipients) * 0.5);
            $count = 0;

            foreach ($recipients as $recipient) {
                $variant = $count < $variantA ? 'A' : 'B';
                $stmt->execute([$testId, $recipient['id'], $variant]);
                $count++;
            }

            return [
                'success' => true,
                'assigned_count' => count($recipients),
                'message' => 'Recipients assigned to variants'
            ];

        } catch (Exception $e) {
            error_log('Assign A/B test error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send campaign to recipients
     */
    public function sendCampaign($campaignId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.*, s.id as template_id FROM marketing_campaigns c
                 LEFT JOIN email_templates s ON c.template_id = s.id
                 WHERE c.id = ?"
            );
            $stmt->execute([$campaignId]);
            $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }

            // Get recipients for segment
            $recipients = $this->getSegmentRecipients($campaign['target_segment']);

            if (empty($recipients)) {
                return ['success' => false, 'error' => 'No recipients for this segment'];
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    // Create campaign_recipient entry
                    $recipientStmt = $this->db->prepare(
                        "INSERT INTO campaign_recipients (campaign_id, customer_id, status, sent_at)
                         VALUES (?, ?, 'pending', NOW())"
                    );
                    $recipientStmt->execute([$campaignId, $recipient['id']]);
                    $campaignRecipientId = $this->db->lastInsertId();

                    // Send email if email service available
                    if ($this->emailService && $campaign['template_id']) {
                        $result = $this->emailService->sendTemplate(
                            $recipient['email'],
                            'marketing_' . $campaign['template_id'],
                            [
                                'customer_name' => $recipient['name'],
                                'campaign_id' => $campaignId
                            ]
                        );

                        if ($result['success']) {
                            $sentCount++;
                            // Update recipient status
                            $updateStmt = $this->db->prepare(
                                "UPDATE campaign_recipients SET status = 'sent', sent_at = NOW() WHERE id = ?"
                            );
                            $updateStmt->execute([$campaignRecipientId]);
                        } else {
                            $failedCount++;
                        }
                    }

                } catch (Exception $e) {
                    $failedCount++;
                    error_log('Send to recipient error: ' . $e->getMessage());
                }
            }

            // Update campaign status
            $updateCampaignStmt = $this->db->prepare(
                "UPDATE marketing_campaigns SET status = 'sent', sent_at = NOW(),
                 recipients_count = ? WHERE id = ?"
            );
            $updateCampaignStmt->execute([$sentCount, $campaignId]);

            return [
                'success' => $sentCount > 0,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'message' => "Campaign sent to $sentCount recipients"
            ];

        } catch (Exception $e) {
            error_log('Send campaign error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get campaign analytics
     */
    public function getCampaignAnalytics($campaignId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                    SUM(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) as opened_count,
                    SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked_count,
                    SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced_count,
                    ROUND(100.0 * SUM(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) /
                        NULLIF(SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END), 0), 2) as open_rate,
                    ROUND(100.0 * SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) /
                        NULLIF(SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END), 0), 2) as click_rate
                 FROM campaign_recipients
                 WHERE campaign_id = ?"
            );
            $stmt->execute([$campaignId]);
            $analytics = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'analytics' => $analytics ?? [
                    'sent_count' => 0,
                    'opened_count' => 0,
                    'clicked_count' => 0,
                    'bounced_count' => 0,
                    'open_rate' => 0,
                    'click_rate' => 0
                ]
            ];

        } catch (Exception $e) {
            error_log('Get analytics error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Determine A/B test winner
     */
    public function determineABTestWinner($testId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    cr.variant,
                    SUM(CASE WHEN cr.status = 'opened' THEN 1 ELSE 0 END) as opened_count,
                    SUM(CASE WHEN cr.status = 'clicked' THEN 1 ELSE 0 END) as clicked_count,
                    SUM(CASE WHEN cr.status = 'sent' THEN 1 ELSE 0 END) as sent_count
                 FROM campaign_ab_test_recipients catr
                 JOIN campaign_recipients cr ON catr.campaign_recipient_id = cr.id
                 WHERE catr.test_id = ?
                 GROUP BY cr.variant"
            );
            $stmt->execute([$testId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) < 2) {
                return ['success' => false, 'error' => 'Insufficient test data'];
            }

            // Get test config
            $testStmt = $this->db->prepare("SELECT metric FROM campaign_ab_tests WHERE id = ?");
            $testStmt->execute([$testId]);
            $test = $testStmt->fetch(PDO::FETCH_ASSOC);

            $winner = null;
            $maxRate = 0;

            foreach ($results as $result) {
                if ($result['sent_count'] > 0) {
                    if ($test['metric'] === 'open_rate') {
                        $rate = ($result['opened_count'] / $result['sent_count']) * 100;
                    } elseif ($test['metric'] === 'click_rate') {
                        $rate = ($result['clicked_count'] / $result['sent_count']) * 100;
                    } else {
                        $rate = ($result['opened_count'] / $result['sent_count']) * 100;
                    }

                    if ($rate > $maxRate) {
                        $maxRate = $rate;
                        $winner = $result['variant'];
                    }
                }
            }

            // Update test with winner
            if ($winner) {
                $updateStmt = $this->db->prepare(
                    "UPDATE campaign_ab_tests SET winner = ?, ended_at = NOW() WHERE id = ?"
                );
                $updateStmt->execute([$winner, $testId]);
            }

            return [
                'success' => true,
                'winner' => $winner,
                'results' => $results
            ];

        } catch (Exception $e) {
            error_log('Determine winner error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get campaign list with pagination
     */
    public function getCampaigns($page = 1, $limit = 20)
    {
        try {
            $offset = ($page - 1) * $limit;

            $stmt = $this->db->prepare(
                "SELECT mc.*, et.name as template_name,
                    COALESCE(mc.recipients_count, 0) as recipients_count,
                    COALESCE(mc.opened_count, 0) as opened_count,
                    COALESCE(mc.clicked_count, 0) as clicked_count
                 FROM marketing_campaigns mc
                 LEFT JOIN email_templates et ON mc.template_id = et.id
                 ORDER BY mc.created_at DESC
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([$limit, $offset]);
            $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM marketing_campaigns");
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            return [
                'success' => true,
                'campaigns' => $campaigns,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ];

        } catch (Exception $e) {
            error_log('Get campaigns error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
