<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\Appointment;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function sendBookingConfirmation(Appointment $appointment): void
    {
        try {
            $this->logger->info('Sending booking confirmation', [
                'appointment_id' => $appointment->getId()
            ]);

            // TODO: Implement actual SMS/Email sending
            // - Send SMS to customer
            // - Send email to staff
            // - Record in notifications log
        } catch (\Exception $e) {
            $this->logger->error('Failed to send confirmation', [
                'appointment_id' => $appointment->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function scheduleReminder(Appointment $appointment): void
    {
        try {
            $this->logger->info('Scheduling appointment reminder', [
                'appointment_id' => $appointment->getId()
            ]);

            // TODO: Implement reminder scheduling
            // - Schedule reminder 24 hours before
            // - Use job queue system
        } catch (\Exception $e) {
            $this->logger->error('Failed to schedule reminder', [
                'appointment_id' => $appointment->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function sendPaymentConfirmation(int $paymentId, string $receiptNumber): void
    {
        try {
            $this->logger->info('Sending payment confirmation', [
                'payment_id' => $paymentId,
                'receipt_number' => $receiptNumber
            ]);

            // TODO: Send SMS/Email receipt
        } catch (\Exception $e) {
            $this->logger->error('Failed to send payment confirmation', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
