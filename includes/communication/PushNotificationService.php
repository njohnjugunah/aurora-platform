<?php

namespace GlamByMariga\Communication;

use PDO;
use Exception;

/**
 * Push Notification Service
 * Handles web push and in-app notifications
 */
class PushNotificationService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Send web push notification
     */
    public function sendWebPush($customerId, $title, $body, $icon = null, $actionUrl = null)
    {
        try {
            // Get customer's push subscriptions
            $stmt = $this->db->prepare(
                "SELECT * FROM push_subscriptions WHERE customer_id = ? AND is_active = TRUE"
            );
            $stmt->execute([$customerId]);
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subscriptions)) {
                return ['success' => false, 'error' => 'No active push subscriptions'];
            }

            $results = ['sent' => 0, 'failed' => 0];

            foreach ($subscriptions as $subscription) {
                $payload = [
                    'title' => $title,
                    'body' => $body,
                    'icon' => $icon ?: '/images/logo-192x192.png',
                    'badge' => '/images/badge-72x72.png',
                    'tag' => 'notification-' . time(),
                    'requireInteraction' => false
                ];

                if ($actionUrl) {
                    $payload['data'] = ['url' => $actionUrl];
                }

                // Send via Web Push Protocol
                $sent = $this->sendPushViaProtocol($subscription, $payload);
                if ($sent) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                }
            }

            // Log notification
            $this->logPushNotification($customerId, $title, $body, $results['sent'] > 0);

            return [
                'success' => $results['sent'] > 0,
                'sent' => $results['sent'],
                'failed' => $results['failed']
            ];

        } catch (Exception $e) {
            error_log('Web push error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send in-app notification
     */
    public function sendInAppNotification($customerId, $title, $message, $type = 'info', $actionUrl = null)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO in_app_notifications (customer_id, title, message, type, action_url, is_read, created_at)
                 VALUES (?, ?, ?, ?, ?, FALSE, NOW())"
            );
            $stmt->execute([$customerId, $title, $message, $type, $actionUrl]);

            // Also send web push if customer has subscriptions
            if ($type !== 'info' || getenv('PUSH_ALL_NOTIFICATIONS')) {
                $this->sendWebPush($customerId, $title, $message, null, $actionUrl);
            }

            return [
                'success' => true,
                'notification_id' => $this->db->lastInsertId()
            ];

        } catch (Exception $e) {
            error_log('In-app notification error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send notification via Web Push Protocol
     */
    private function sendPushViaProtocol($subscription, $payload)
    {
        try {
            $endpoint = $subscription['endpoint'];
            $publicKey = $subscription['public_key'];
            $authToken = $subscription['auth_token'];

            // For production, implement full Web Push Protocol with encryption
            // For now, simplified version without encryption
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'TTL: 24'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 10
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 201 || $httpCode === 200;

        } catch (Exception $e) {
            error_log('Send push protocol error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log push notification
     */
    private function logPushNotification($customerId, $title, $body, $success)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO push_notification_logs (customer_id, title, body, status, created_at)
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$customerId, $title, $body, $success ? 'sent' : 'failed']);

        } catch (Exception $e) {
            error_log('Log push error: ' . $e->getMessage());
        }
    }

    /**
     * Get in-app notifications for customer
     */
    public function getInAppNotifications($customerId, $limit = 20)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM in_app_notifications
                 WHERE customer_id = ?
                 ORDER BY created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$customerId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get notifications error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE in_app_notifications
                 SET is_read = TRUE, read_at = NOW()
                 WHERE id = ? AND customer_id = ?"
            );
            $stmt->execute([$notificationId, $customerId]);

            return ['success' => true];

        } catch (Exception $e) {
            error_log('Mark read error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Register push subscription
     */
    public function registerSubscription($customerId, $subscription)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO push_subscriptions (customer_id, endpoint, public_key, auth_token, is_active, created_at)
                 VALUES (?, ?, ?, ?, TRUE, NOW())
                 ON DUPLICATE KEY UPDATE is_active = TRUE, updated_at = NOW()"
            );
            $stmt->execute([
                $customerId,
                $subscription['endpoint'],
                $subscription['keys']['p256dh'] ?? null,
                $subscription['keys']['auth'] ?? null
            ]);

            return ['success' => true, 'message' => 'Subscription registered'];

        } catch (Exception $e) {
            error_log('Register subscription error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send order notification
     */
    public function notifyOrderStatus($orderId, $status)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT o.*, c.name FROM orders o
                 JOIN customers c ON o.customer_id = c.id
                 WHERE o.id = ?"
            );
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            $titles = [
                'confirmed' => 'Order Confirmed!',
                'processing' => 'Order Processing',
                'shipped' => 'Your Order is on the way!',
                'delivered' => 'Order Delivered',
                'cancelled' => 'Order Cancelled'
            ];

            $bodies = [
                'confirmed' => 'Your order #' . $order['id'] . ' has been confirmed.',
                'processing' => 'We\'re preparing your order #' . $order['id'] . ' for shipment.',
                'shipped' => 'Order #' . $order['id'] . ' has been shipped with tracking.',
                'delivered' => 'Your order #' . $order['id'] . ' has been delivered!',
                'cancelled' => 'Your order #' . $order['id'] . ' has been cancelled.'
            ];

            return $this->sendInAppNotification(
                $order['customer_id'],
                $titles[$status] ?? 'Order Update',
                $bodies[$status] ?? 'Your order status has been updated',
                'order',
                '/orders?id=' . $orderId
            );

        } catch (Exception $e) {
            error_log('Order notification error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send promotional notification
     */
    public function sendPromotion($customerId, $title, $message, $imageUrl = null, $promoCode = null)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO in_app_notifications (customer_id, title, message, type, action_url, created_at)
                 VALUES (?, ?, ?, 'promotion', ?, NOW())"
            );
            $actionUrl = $promoCode ? '/shop?code=' . urlencode($promoCode) : '/shop';
            $stmt->execute([$customerId, $title, $message, $actionUrl]);

            return $this->sendWebPush($customerId, $title, $message, $imageUrl, $actionUrl);

        } catch (Exception $e) {
            error_log('Promotion notification error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
