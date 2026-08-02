<?php

namespace GlamByMariga\Communication;

use PDO;
use Exception;

/**
 * SMS Service
 * Handles SMS sending via Africastalking or Twilio
 */
class SMSService
{
    private $db;
    private $provider;
    private $apiKey;
    private $apiSecret;
    private $senderID;

    public function __construct(PDO $db = null)
    {
        $this->db = $db;
        $this->provider = getenv('SMS_PROVIDER') ?: 'africastalking'; // africastalking or twilio
        $this->apiKey = getenv('SMS_API_KEY');
        $this->apiSecret = getenv('SMS_API_SECRET');
        $this->senderID = getenv('SMS_SENDER_ID') ?: 'GlamByMariga';
    }

    /**
     * Send SMS message
     */
    public function send($phoneNumber, $message)
    {
        try {
            // Normalize phone number (remove non-digits, add country code if needed)
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

            $result = null;

            if ($this->provider === 'africastalking') {
                $result = $this->sendViaAfricasTalking($phoneNumber, $message);
            } elseif ($this->provider === 'twilio') {
                $result = $this->sendViaTwilio($phoneNumber, $message);
            } else {
                return [
                    'success' => false,
                    'error' => 'Unknown SMS provider: ' . $this->provider
                ];
            }

            // Log SMS send
            if ($this->db) {
                $this->logSMS($phoneNumber, $message, $result);
            }

            return $result;

        } catch (Exception $e) {
            error_log('SMS send error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS via Africastalking
     */
    private function sendViaAfricasTalking($phoneNumber, $message)
    {
        try {
            $url = 'https://api.sandbox.africastalking.com/version1/messaging';

            $data = [
                'username' => 'sandbox',
                'message' => $message,
                'recipients' => [$phoneNumber]
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                    'apiKey: ' . $this->apiKey
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_TIMEOUT => 10
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                return [
                    'success' => true,
                    'provider_id' => $result['SMSMessageData']['Messages'][0]['MessageId'] ?? null,
                    'message' => 'SMS sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to send SMS: HTTP ' . $httpCode
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS via Twilio
     */
    private function sendViaTwilio($phoneNumber, $message)
    {
        try {
            $accountSid = getenv('TWILIO_ACCOUNT_SID');
            $authToken = getenv('TWILIO_AUTH_TOKEN');
            $fromNumber = getenv('TWILIO_PHONE_NUMBER');

            $url = "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json";

            $data = [
                'From' => $fromNumber,
                'To' => $phoneNumber,
                'Body' => $message
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => "$accountSid:$authToken",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_TIMEOUT => 10
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 201) {
                $result = json_decode($response, true);
                return [
                    'success' => true,
                    'provider_id' => $result['sid'] ?? null,
                    'message' => 'SMS sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to send SMS: HTTP ' . $httpCode
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Normalize phone number
     */
    private function normalizePhoneNumber($phoneNumber)
    {
        // Remove spaces, dashes, parentheses
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // Add country code if missing (Kenya: +254)
        if (!preg_match('/^\+/', $phoneNumber)) {
            if (preg_match('/^07/', $phoneNumber)) {
                $phoneNumber = '+254' . substr($phoneNumber, 1);
            } elseif (preg_match('/^7/', $phoneNumber)) {
                $phoneNumber = '+254' . $phoneNumber;
            }
        }

        return $phoneNumber;
    }

    /**
     * Log SMS message
     */
    private function logSMS($phoneNumber, $message, $result)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO sms_messages (phone_number, message, status, provider, provider_id, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $phoneNumber,
                $message,
                $result['success'] ? 'sent' : 'failed',
                $this->provider,
                $result['provider_id'] ?? null
            ]);

        } catch (Exception $e) {
            error_log('Log SMS error: ' . $e->getMessage());
        }
    }

    /**
     * Send appointment reminder SMS
     */
    public function sendAppointmentReminder($bookingId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT b.*, c.phone, s.name as service_name
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
            $message = "Hi {$booking['name']}, reminder: Your {$booking['service_name']} appointment is tomorrow at {$appointmentTime}. See you then! - GlamByMariga";

            return $this->send($booking['phone'], $message);

        } catch (Exception $e) {
            error_log('SMS reminder error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send order status SMS
     */
    public function sendOrderStatus($orderId, $status)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT o.*, c.phone, c.name FROM orders o
                 JOIN customers c ON o.customer_id = c.id
                 WHERE o.id = ?"
            );
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            $messages = [
                'shipped' => "Your order #{$order['id']} has shipped! Tracking: {$order['tracking_number']} - GlamByMariga",
                'delivered' => "Your order #{$order['id']} has been delivered! Thank you for shopping with us. - GlamByMariga",
                'cancelled' => "Your order #{$order['id']} has been cancelled. Contact us for details. - GlamByMariga"
            ];

            $message = $messages[$status] ?? "Order #{$order['id']} status: {$status}";

            return $this->send($order['phone'], $message);

        } catch (Exception $e) {
            error_log('Order SMS error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
