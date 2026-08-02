<?php

namespace GlamByMariga\Communication;

use PDO;
use Exception;

/**
 * Notification Service
 * Orchestrates different notification types (email, SMS, in-app)
 */
class NotificationService
{
    private $db;
    private $emailService;

    public function __construct(PDO $db, EmailService $emailService = null)
    {
        $this->db = $db;
        $this->emailService = $emailService ?: new EmailService($db);
    }

    /**
     * Send order confirmation notification
     */
    public function notifyOrderConfirmation($orderId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT o.*, c.name, c.email, c.phone FROM orders o
                 JOIN customers c ON o.customer_id = c.id
                 WHERE o.id = ?"
            );
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            $variables = [
                'customer_name' => $order['name'],
                'order_id' => $order['id'],
                'order_total' => number_format($order['total_amount'], 2),
                'order_date' => date('M d, Y', strtotime($order['created_at']))
            ];

            // Send email
            $emailResult = $this->emailService->sendTemplate(
                $order['email'],
                'order_confirmation',
                $variables
            );

            // Log notification
            $this->logNotification($order['customer_id'], 'order_confirmation', $orderId, $emailResult['success']);

            return $emailResult;

        } catch (Exception $e) {
            error_log('Order notification error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send order shipped notification
     */
    public function notifyOrderShipped($orderId, $trackingNumber = null)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT o.*, c.name, c.email FROM orders o
                 JOIN customers c ON o.customer_id = c.id
                 WHERE o.id = ?"
            );
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            $variables = [
                'customer_name' => $order['name'],
                'order_id' => $order['id'],
                'tracking_number' => $trackingNumber ?: $order['tracking_number'] ?: 'TBD',
                'delivery_date' => date('M d, Y', strtotime($order['delivery_date'])),
                'carrier' => $order['shipping_method'] ?: 'Standard',
                'tracking_url' => 'https://glambymariga.com/track?order=' . $orderId
            ];

            $emailResult = $this->emailService->sendTemplate(
                $order['email'],
                'order_shipped',
                $variables
            );

            $this->logNotification($order['customer_id'], 'order_shipped', $orderId, $emailResult['success']);

            return $emailResult;

        } catch (Exception $e) {
            error_log('Shipped notification error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send order delivered notification
     */
    public function notifyOrderDelivered($orderId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT o.*, c.name, c.email FROM orders o
                 JOIN customers c ON o.customer_id = c.id
                 WHERE o.id = ?"
            );
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            $variables = [
                'customer_name' => $order['name'],
                'order_id' => $order['id'],
                'review_url' => 'https://glambymariga.com/review?order=' . $orderId
            ];

            $emailResult = $this->emailService->sendTemplate(
                $order['email'],
                'order_delivered',
                $variables
            );

            $this->logNotification($order['customer_id'], 'order_delivered', $orderId, $emailResult['success']);

            return $emailResult;

        } catch (Exception $e) {
            error_log('Delivered notification error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send appointment confirmation
     */
    public function notifyAppointmentConfirmed($bookingId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT b.*, c.name, c.email, s.name as service_name, s.price
                 FROM bookings b
                 JOIN customers c ON b.customer_id = c.id
                 JOIN services s ON b.service_id = s.id
                 WHERE b.id = ?"
            );
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                return ['success' => false, 'error' => 'Booking not found'];
            }

            $appointmentTime = date('H:i', strtotime($booking['start_time']));
            $appointmentDate = date('M d, Y', strtotime($booking['booking_date']));
            $duration = ($booking['end_time'] && $booking['start_time'])
                ? round((strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 60) . ' min'
                : '1 hour';

            $variables = [
                'customer_name' => $booking['name'],
                'service_name' => $booking['service_name'],
                'appointment_date' => $appointmentDate,
                'appointment_time' => $appointmentTime,
                'duration' => $duration,
                'price' => number_format($booking['price'], 2)
            ];

            $emailResult = $this->emailService->sendTemplate(
                $booking['email'],
                'appointment_confirmed',
                $variables
            );

            $this->logNotification($booking['customer_id'], 'appointment_confirmed', $bookingId, $emailResult['success']);

            return $emailResult;

        } catch (Exception $e) {
            error_log('Appointment notification error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send appointment reminder
     */
    public function notifyAppointmentReminder($bookingId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT b.*, c.name, c.email, s.name as service_name
                 FROM bookings b
                 JOIN customers c ON b.customer_id = c.id
                 JOIN services s ON b.service_id = s.id
                 WHERE b.id = ?"
            );
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                return ['success' => false, 'error' => 'Booking not found'];
            }

            $appointmentTime = date('H:i', strtotime($booking['start_time']));

            $variables = [
                'customer_name' => $booking['name'],
                'service_name' => $booking['service_name'],
                'appointment_time' => $appointmentTime,
                'location' => 'GlamByMariga Salon'
            ];

            $emailResult = $this->emailService->sendTemplate(
                $booking['email'],
                'appointment_reminder',
                $variables
            );

            $this->logNotification($booking['customer_id'], 'appointment_reminder', $bookingId, $emailResult['success']);

            return $emailResult;

        } catch (Exception $e) {
            error_log('Reminder notification error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send welcome email to new customer
     */
    public function sendWelcomeEmail($customerId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                return ['success' => false, 'error' => 'Customer not found'];
            }

            $variables = [
                'customer_name' => $customer['name'],
                'shop_url' => 'https://glambymariga.com/shop'
            ];

            $emailResult = $this->emailService->sendTemplate(
                $customer['email'],
                'welcome_email',
                $variables
            );

            $this->logNotification($customerId, 'welcome_email', null, $emailResult['success']);

            return $emailResult;

        } catch (Exception $e) {
            error_log('Welcome email error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send review request email
     */
    public function sendReviewRequest($customerId, $productId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer || !$product) {
                return ['success' => false, 'error' => 'Customer or product not found'];
            }

            $variables = [
                'customer_name' => $customer['name'],
                'product_name' => $product['name'],
                'review_url' => 'https://glambymariga.com/review?product=' . $productId
            ];

            $emailResult = $this->emailService->sendTemplate(
                $customer['email'],
                'review_request',
                $variables
            );

            $this->logNotification($customerId, 'review_request', $productId, $emailResult['success']);

            return $emailResult;

        } catch (Exception $e) {
            error_log('Review request error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log notification
     */
    private function logNotification($customerId, $type, $referenceId, $success)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO notifications (customer_id, type, reference_id, status, created_at)
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$customerId, $type, $referenceId, $success ? 'sent' : 'failed']);

        } catch (Exception $e) {
            error_log('Log notification error: ' . $e->getMessage());
        }
    }

    /**
     * Get customer notification preferences
     */
    public function getPreferences($customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM notification_preferences WHERE customer_id = ?"
            );
            $stmt->execute([$customerId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get preferences error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences($customerId, $preferences)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO notification_preferences (customer_id, email_orders, email_appointments,
                 email_promotions, email_reviews, sms_alerts, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                 email_orders = ?, email_appointments = ?, email_promotions = ?,
                 email_reviews = ?, sms_alerts = ?"
            );

            $values = [
                $customerId,
                $preferences['email_orders'] ?? 1,
                $preferences['email_appointments'] ?? 1,
                $preferences['email_promotions'] ?? 1,
                $preferences['email_reviews'] ?? 1,
                $preferences['sms_alerts'] ?? 0,
                $preferences['email_orders'] ?? 1,
                $preferences['email_appointments'] ?? 1,
                $preferences['email_promotions'] ?? 1,
                $preferences['email_reviews'] ?? 1,
                $preferences['sms_alerts'] ?? 0
            ];

            $stmt->execute($values);

            return ['success' => true, 'message' => 'Preferences updated'];

        } catch (Exception $e) {
            error_log('Update preferences error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
