<?php

namespace GlamByMariga\Communication;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Email Service
 * Handles all email sending operations
 */
class EmailService
{
    private $mailer;
    private $fromEmail;
    private $fromName;
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db;
        $this->setupMailer();
    }

    /**
     * Setup PHPMailer configuration
     */
    private function setupMailer()
    {
        $this->mailer = new PHPMailer(true);

        // Get email config from environment or config
        $this->fromEmail = getenv('MAIL_FROM_EMAIL') ?: 'noreply@glambymariga.com';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'GlamByMariga';

        try {
            // For development, use SMTP or log to file
            if (getenv('MAIL_DRIVER') === 'smtp') {
                $this->mailer->isSMTP();
                $this->mailer->Host = getenv('MAIL_HOST') ?: 'smtp.mailtrap.io';
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = getenv('MAIL_USERNAME');
                $this->mailer->Password = getenv('MAIL_PASSWORD');
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_TLS;
                $this->mailer->Port = getenv('MAIL_PORT') ?: 587;
            } else {
                // Use PHP mail() function as fallback
                $this->mailer->isMail();
            }

            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';

        } catch (MailException $e) {
            error_log('Mailer setup error: ' . $e->getMessage());
        }
    }

    /**
     * Send email
     */
    public function send($to, $subject, $html, $plainText = null, $attachments = [])
    {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->clearAttachments();

            // Add recipient
            if (is_array($to)) {
                foreach ($to as $email) {
                    $this->mailer->addAddress($email);
                }
            } else {
                $this->mailer->addAddress($to);
            }

            // Set subject and body
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $html;
            $this->mailer->AltBody = $plainText ?: strip_tags($html);

            // Add attachments
            foreach ($attachments as $attachment) {
                $this->mailer->addAttachment($attachment['path'], $attachment['name'] ?? '');
            }

            // Send email
            $result = $this->mailer->send();

            // Log email send
            if ($this->db) {
                $this->logEmail($to, $subject, 'sent', $html);
            }

            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];

        } catch (MailException $e) {
            error_log('Email send error: ' . $e->getMessage());

            if ($this->db) {
                $this->logEmail($to, $subject, 'failed', $html, $e->getMessage());
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send templated email
     */
    public function sendTemplate($to, $templateName, $variables = [], $attachments = [])
    {
        try {
            // Get template
            $template = $this->getTemplate($templateName);
            if (!$template) {
                return [
                    'success' => false,
                    'error' => 'Template not found: ' . $templateName
                ];
            }

            // Replace variables in subject and body
            $subject = $this->replaceVariables($template['subject'], $variables);
            $html = $this->replaceVariables($template['body'], $variables);

            return $this->send($to, $subject, $html, null, $attachments);

        } catch (Exception $e) {
            error_log('Send template error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send bulk emails
     */
    public function sendBulk($recipients, $subject, $html, $delay = 0)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'total' => count($recipients)
        ];

        foreach ($recipients as $recipient) {
            $result = $this->send($recipient, $subject, $html);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            // Add delay between sends to avoid rate limiting
            if ($delay > 0) {
                usleep($delay * 1000);
            }
        }

        return $results;
    }

    /**
     * Get email template
     */
    private function getTemplate($templateName)
    {
        if (!$this->db) {
            return $this->getBuiltInTemplate($templateName);
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT subject, body FROM email_templates WHERE name = ? AND is_active = TRUE"
            );
            $stmt->execute([$templateName]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get template error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get built-in email templates
     */
    private function getBuiltInTemplate($templateName)
    {
        $templates = [
            'order_confirmation' => [
                'subject' => 'Order Confirmation - #{{ORDER_ID}}',
                'body' => $this->getOrderConfirmationTemplate()
            ],
            'order_shipped' => [
                'subject' => 'Your Order Has Shipped - #{{ORDER_ID}}',
                'body' => $this->getOrderShippedTemplate()
            ],
            'order_delivered' => [
                'subject' => 'Your Order Has Been Delivered - #{{ORDER_ID}}',
                'body' => $this->getOrderDeliveredTemplate()
            ],
            'appointment_confirmed' => [
                'subject' => 'Appointment Confirmation - {{SERVICE_NAME}}',
                'body' => $this->getAppointmentConfirmedTemplate()
            ],
            'appointment_reminder' => [
                'subject' => 'Reminder: Your Appointment Tomorrow',
                'body' => $this->getAppointmentReminderTemplate()
            ],
            'welcome_email' => [
                'subject' => 'Welcome to GlamByMariga!',
                'body' => $this->getWelcomeTemplate()
            ],
            'reset_password' => [
                'subject' => 'Reset Your Password',
                'body' => $this->getResetPasswordTemplate()
            ],
            'review_request' => [
                'subject' => 'How was your experience with {{PRODUCT_NAME}}?',
                'body' => $this->getReviewRequestTemplate()
            ]
        ];

        return $templates[$templateName] ?? null;
    }

    /**
     * Replace variables in text
     */
    private function replaceVariables($text, $variables)
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . strtoupper($key) . '}}', $value, $text);
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }

    /**
     * Log email send
     */
    private function logEmail($to, $subject, $status, $body, $error = null)
    {
        try {
            $toArray = is_array($to) ? implode(',', $to) : $to;
            $stmt = $this->db->prepare(
                "INSERT INTO email_logs (to_address, subject, body, status, error, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$toArray, $subject, substr($body, 0, 1000), $status, $error]);

        } catch (Exception $e) {
            error_log('Log email error: ' . $e->getMessage());
        }
    }

    // Email Template Builders

    private function getOrderConfirmationTemplate()
    {
        return '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="margin: 0;">Order Confirmed!</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">Thank you for your purchase</p>
    </div>
    <div style="background: white; padding: 30px; border-radius: 0 0 12px 12px;">
        <p>Hi {{CUSTOMER_NAME}},</p>
        <p>Your order #{{ORDER_ID}} has been confirmed and is being prepared for shipment.</p>
        <div style="background: #faf8f5; padding: 20px; border-left: 4px solid #C9A961; margin: 20px 0;">
            <p><strong>Order Summary:</strong></p>
            <p>Order ID: {{ORDER_ID}}</p>
            <p>Total Amount: KES {{ORDER_TOTAL}}</p>
            <p>Order Date: {{ORDER_DATE}}</p>
        </div>
        <p>You will receive a tracking number as soon as your order ships.</p>
        <p><strong>Questions?</strong> Reply to this email or contact us at support@glambymariga.com</p>
        <p>Best regards,<br>GlamByMariga Team</p>
    </div>
</div>
        ';
    }

    private function getOrderShippedTemplate()
    {
        return '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="margin: 0;">Your Order is on the Way!</h1>
    </div>
    <div style="background: white; padding: 30px; border-radius: 0 0 12px 12px;">
        <p>Hi {{CUSTOMER_NAME}},</p>
        <p>Great news! Order #{{ORDER_ID}} has been shipped.</p>
        <div style="background: #faf8f5; padding: 20px; border-left: 4px solid #C9A961; margin: 20px 0;">
            <p><strong>Tracking Information:</strong></p>
            <p>Tracking Number: {{TRACKING_NUMBER}}</p>
            <p>Expected Delivery: {{DELIVERY_DATE}}</p>
            <p>Carrier: {{CARRIER}}</p>
        </div>
        <p>Track your package <a href="{{TRACKING_URL}}" style="color: #B76E79; text-decoration: none;">here</a>.</p>
        <p>Best regards,<br>GlamByMariga Team</p>
    </div>
</div>
        ';
    }

    private function getOrderDeliveredTemplate()
    {
        return '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="margin: 0;">Delivery Confirmed!</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">Your order has been delivered</p>
    </div>
    <div style="background: white; padding: 30px; border-radius: 0 0 12px 12px;">
        <p>Hi {{CUSTOMER_NAME}},</p>
        <p>Your order #{{ORDER_ID}} has been delivered!</p>
        <p>We hope you love your purchase. We\'d love to hear your feedback!</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{REVIEW_URL}}" style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; display: inline-block;">Leave a Review</a>
        </div>
        <p>Thank you for shopping with us!</p>
        <p>Best regards,<br>GlamByMariga Team</p>
    </div>
</div>
        ';
    }

    private function getAppointmentConfirmedTemplate()
    {
        return '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="margin: 0;">Appointment Confirmed!</h1>
    </div>
    <div style="background: white; padding: 30px; border-radius: 0 0 12px 12px;">
        <p>Hi {{CUSTOMER_NAME}},</p>
        <p>Your appointment has been confirmed!</p>
        <div style="background: #faf8f5; padding: 20px; border-left: 4px solid #C9A961; margin: 20px 0;">
            <p><strong>Appointment Details:</strong></p>
            <p>Service: {{SERVICE_NAME}}</p>
            <p>Date: {{APPOINTMENT_DATE}}</p>
            <p>Time: {{APPOINTMENT_TIME}}</p>
            <p>Duration: {{DURATION}}</p>
            <p>Price: KES {{PRICE}}</p>
        </div>
        <p>See you soon!</p>
        <p>Best regards,<br>GlamByMariga Team</p>
    </div>
</div>
        ';
    }

    private function getAppointmentReminderTemplate()
    {
        return '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="margin: 0;">Appointment Reminder</h1>
    </div>
    <div style="background: white; padding: 30px; border-radius: 0 0 12px 12px;">
        <p>Hi {{CUSTOMER_NAME}},</p>
        <p>Just a friendly reminder about your appointment tomorrow!</p>
        <div style="background: #faf8f5; padding: 20px; border-left: 4px solid #C9A961; margin: 20px 0;">
            <p><strong>Service:</strong> {{SERVICE_NAME}}</p>
            <p><strong>Time:</strong> {{APPOINTMENT_TIME}}</p>
            <p><strong>Location:</strong> {{LOCATION}}</p>
        </div>
        <p>If you need to reschedule, please let us know as soon as possible.</p>
        <p>Best regards,<br>GlamByMariga Team</p>
    </div>
</div>
        ';
    }

    private function getWelcomeTemplate()
    {
        return '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="margin: 0;">Welcome to GlamByMariga!</h1>
    </div>
    <div style="background: white; padding: 30px; border-radius: 0 0 12px 12px;">
        <p>Hi {{CUSTOMER_NAME}},</p>
        <p>Welcome to GlamByMariga! We\'re thrilled to have you join our beauty community.</p>
        <p>Explore our services:</p>
        <ul>
            <li>Premium Beauty Services</li>
            <li>High-Quality Products</li>
            <li>Expert Consultations</li>
            <li>Exclusive Member Offers</li>
        </ul>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{SHOP_URL}}" style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; display: inline-block;">Start Shopping</a>
        </div>
        <p>Best regards,<br>GlamByMariga Team</p>
    </div>
</div>
        ';
    }

    private function getResetPasswordTemplate()
    {
        return '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="margin: 0;">Reset Your Password</h1>
    </div>
    <div style="background: white; padding: 30px; border-radius: 0 0 12px 12px;">
        <p>Hi {{CUSTOMER_NAME}},</p>
        <p>We received a request to reset your password.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{RESET_URL}}" style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; display: inline-block;">Reset Password</a>
        </div>
        <p>This link expires in 24 hours.</p>
        <p>If you didn\'t request this, please ignore this email.</p>
        <p>Best regards,<br>GlamByMariga Team</p>
    </div>
</div>
        ';
    }

    private function getReviewRequestTemplate()
    {
        return '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="margin: 0;">We\'d Love Your Feedback!</h1>
    </div>
    <div style="background: white; padding: 30px; border-radius: 0 0 12px 12px;">
        <p>Hi {{CUSTOMER_NAME}},</p>
        <p>Thank you for purchasing {{PRODUCT_NAME}}! We\'d love to hear what you think.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{REVIEW_URL}}" style="background: linear-gradient(135deg, #B76E79, #C9A961); color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; display: inline-block;">Leave a Review</a>
        </div>
        <p>Your feedback helps us improve!</p>
        <p>Best regards,<br>GlamByMariga Team</p>
    </div>
</div>
        ';
    }
}
