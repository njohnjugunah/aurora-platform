<?php

namespace GlamByMariga\Analytics;

use PDO;
use Exception;

/**
 * Attribution Service
 * Multi-touch attribution modeling and channel analysis
 */
class AttributionService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Track customer touchpoint
     */
    public function trackTouchpoint($customerId, $touchpointType, $channel, $campaignId = null, $engagementValue = 0)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO customer_touchpoints
                 (customer_id, touchpoint_type, channel, campaign_id, engagement_value, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$customerId, $touchpointType, $channel, $campaignId, $engagementValue]);

            return ['success' => true];

        } catch (Exception $e) {
            error_log('Track touchpoint error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate attribution for order
     */
    public function calculateOrderAttribution($orderId, $modelTypes = ['first_touch', 'last_touch', 'linear', 'time_decay'])
    {
        try {
            // Get order and customer info
            $orderStmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            // Get customer touchpoints leading to this order
            $touchpointStmt = $this->db->prepare(
                "SELECT * FROM customer_touchpoints
                 WHERE customer_id = ? AND created_at < ?
                 ORDER BY created_at ASC"
            );
            $touchpointStmt->execute([$order['customer_id'], $order['created_at']]);
            $touchpoints = $touchpointStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($touchpoints)) {
                // Direct traffic
                $touchpoints = [[
                    'channel' => 'direct',
                    'campaign_id' => null,
                    'created_at' => $order['created_at']
                ]];
            }

            // Calculate attribution for each model
            foreach ($modelTypes as $model) {
                $attribution = $this->applyAttributionModel($model, $touchpoints, $order['total']);
                $this->storeAttribution($orderId, $order['customer_id'], $model, $attribution, $touchpoints);
            }

            return ['success' => true, 'message' => 'Attribution calculated'];

        } catch (Exception $e) {
            error_log('Calculate attribution error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Apply attribution model
     */
    private function applyAttributionModel($model, $touchpoints, $orderValue)
    {
        $attribution = [];
        $touchpointCount = count($touchpoints);

        switch ($model) {
            case 'first_touch':
                $attribution[$touchpoints[0]['channel']] = $orderValue;
                break;

            case 'last_touch':
                $attribution[$touchpoints[$touchpointCount - 1]['channel']] = $orderValue;
                break;

            case 'linear':
                $valuePerTouch = $orderValue / $touchpointCount;
                foreach ($touchpoints as $tp) {
                    $channel = $tp['channel'] ?? 'direct';
                    $attribution[$channel] = ($attribution[$channel] ?? 0) + $valuePerTouch;
                }
                break;

            case 'time_decay':
                // Exponential weighting (earlier touches worth less)
                $weights = [];
                $totalWeight = 0;

                for ($i = 0; $i < $touchpointCount; $i++) {
                    $weight = pow(2, $i); // Exponential decay
                    $weights[$i] = $weight;
                    $totalWeight += $weight;
                }

                foreach ($touchpoints as $i => $tp) {
                    $channel = $tp['channel'] ?? 'direct';
                    $value = ($weights[$i] / $totalWeight) * $orderValue;
                    $attribution[$channel] = ($attribution[$channel] ?? 0) + $value;
                }
                break;

            case 'position_based':
                // 40% first, 40% last, 20% middle
                $attribution[$touchpoints[0]['channel']] = $orderValue * 0.4;
                $attribution[$touchpoints[$touchpointCount - 1]['channel']] =
                    ($attribution[$touchpoints[$touchpointCount - 1]['channel']] ?? 0) + ($orderValue * 0.4);

                if ($touchpointCount > 2) {
                    $middleValue = $orderValue * 0.2 / ($touchpointCount - 2);
                    for ($i = 1; $i < $touchpointCount - 1; $i++) {
                        $channel = $touchpoints[$i]['channel'] ?? 'direct';
                        $attribution[$channel] = ($attribution[$channel] ?? 0) + $middleValue;
                    }
                }
                break;

            case 'data_driven':
                // Simplified data-driven: weight by engagement value
                $totalEngagement = 0;
                foreach ($touchpoints as $tp) {
                    $totalEngagement += ($tp['engagement_value'] ?? 1);
                }

                foreach ($touchpoints as $tp) {
                    $channel = $tp['channel'] ?? 'direct';
                    $weight = ($tp['engagement_value'] ?? 1) / $totalEngagement;
                    $attribution[$channel] = ($attribution[$channel] ?? 0) + ($weight * $orderValue);
                }
                break;
        }

        return $attribution;
    }

    /**
     * Store attribution results
     */
    private function storeAttribution($orderId, $customerId, $modelType, $attribution, $touchpoints)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO attribution_models
                 (order_id, customer_id, model_type, conversion_value, touch_points, channel_attribution)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 channel_attribution = VALUES(channel_attribution)"
            );
            $stmt->execute([
                $orderId,
                $customerId,
                $modelType,
                $this->getOrderTotal($orderId),
                json_encode($touchpoints),
                json_encode($attribution)
            ]);

        } catch (Exception $e) {
            error_log('Store attribution error: ' . $e->getMessage());
        }
    }

    /**
     * Get order total
     */
    private function getOrderTotal($orderId)
    {
        $stmt = $this->db->prepare("SELECT total FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Get channel attribution performance
     */
    public function getChannelPerformance($modelType = 'linear')
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    channel,
                    COUNT(DISTINCT customer_id) as conversions,
                    SUM(CAST(channel_attribution ->> ? AS DECIMAL(10,2))) as attributed_revenue,
                    AVG(CAST(channel_attribution ->> ? AS DECIMAL(10,2))) as avg_attribution,
                    COUNT(*) as touchpoint_count
                 FROM attribution_models
                 LEFT JOIN customer_touchpoints ON attribution_models.customer_id = customer_touchpoints.customer_id
                 WHERE model_type = ?
                 GROUP BY channel
                 ORDER BY attributed_revenue DESC"
            );
            $stmt->execute([$modelType, $modelType, $modelType]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get channel performance error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get campaign attribution
     */
    public function getCampaignAttribution($campaignId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    am.model_type,
                    COUNT(DISTINCT am.customer_id) as conversions,
                    SUM(am.conversion_value) as total_revenue,
                    AVG(am.conversion_value) as avg_order_value
                 FROM attribution_models am
                 JOIN customer_touchpoints ct ON am.customer_id = ct.customer_id
                 WHERE ct.campaign_id = ?
                 GROUP BY am.model_type"
            );
            $stmt->execute([$campaignId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get campaign attribution error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Compare attribution models
     */
    public function compareAttributionModels()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    model_type,
                    COUNT(*) as order_count,
                    SUM(conversion_value) as total_attributed_revenue,
                    AVG(conversion_value) as avg_attributed_value
                 FROM attribution_models
                 GROUP BY model_type
                 ORDER BY total_attributed_revenue DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Compare models error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get top attribution channels
     */
    public function getTopChannels($limit = 10)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    channel,
                    COUNT(DISTINCT customer_id) as unique_customers,
                    COUNT(*) as total_touchpoints,
                    SUM(engagement_value) as total_engagement
                 FROM customer_touchpoints
                 WHERE channel IS NOT NULL
                 GROUP BY channel
                 ORDER BY total_engagement DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get top channels error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate ROI by channel
     */
    public function calculateChannelROI($startDate, $endDate)
    {
        try {
            $channels = ['email', 'social', 'organic', 'paid', 'direct', 'referral'];
            $roi = [];

            foreach ($channels as $channel) {
                $stmt = $this->db->prepare(
                    "SELECT
                        COUNT(DISTINCT customer_id) as conversions,
                        SUM(conversion_value) as revenue
                     FROM attribution_models am
                     JOIN customer_touchpoints ct ON am.customer_id = ct.customer_id
                     WHERE ct.channel = ?
                     AND am.created_at BETWEEN ? AND ?"
                );
                $stmt->execute([$channel, $startDate, $endDate]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                // Estimated cost (simplified)
                $estimatedCost = $result['conversions'] * 50; // $50 per conversion

                $roi[$channel] = [
                    'conversions' => $result['conversions'],
                    'revenue' => $result['revenue'],
                    'estimated_cost' => $estimatedCost,
                    'roi_percentage' => $estimatedCost > 0
                        ? round((($result['revenue'] - $estimatedCost) / $estimatedCost) * 100, 2)
                        : 0
                ];
            }

            return [
                'success' => true,
                'roi_by_channel' => $roi
            ];

        } catch (Exception $e) {
            error_log('Calculate ROI error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
