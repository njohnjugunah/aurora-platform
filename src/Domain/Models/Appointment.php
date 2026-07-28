<?php

declare(strict_types=1);

namespace App\Domain\Models;

use DateTime;

class Appointment
{
    private ?int $id = null;
    private int $customerId;
    private int $serviceId;
    private int $staffId;
    private DateTime $scheduledDate;
    private DateTime $scheduledTime;
    private int $durationMinutes;
    private string $status;
    private ?string $customerNotes = null;
    private ?string $staffNotes = null;
    private ?string $cancellationReason = null;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private ?int $createdBy = null;
    private ?int $updatedBy = null;
    private ?DateTime $deletedAt = null;

    public function __construct(
        int $customerId,
        int $serviceId,
        int $staffId,
        DateTime $scheduledDate,
        DateTime $scheduledTime,
        int $durationMinutes,
        string $status = 'Pending'
    ) {
        $this->customerId = $customerId;
        $this->serviceId = $serviceId;
        $this->staffId = $staffId;
        $this->scheduledDate = $scheduledDate;
        $this->scheduledTime = $scheduledTime;
        $this->durationMinutes = $durationMinutes;
        $this->status = $status;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getStaffId(): int
    {
        return $this->staffId;
    }

    public function getScheduledDate(): DateTime
    {
        return $this->scheduledDate;
    }

    public function getScheduledTime(): DateTime
    {
        return $this->scheduledTime;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function getEndTime(): DateTime
    {
        $endTime = clone $this->scheduledDate;
        $endTime->setTime(
            $this->scheduledTime->format('H'),
            $this->scheduledTime->format('i')
        );
        $endTime->modify("+{$this->durationMinutes} minutes");
        return $endTime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new DateTime();
    }

    public function confirm(): void
    {
        $this->status = 'Confirmed';
        $this->updatedAt = new DateTime();
    }

    public function cancel(string $reason = ''): void
    {
        $this->status = 'Cancelled';
        $this->cancellationReason = $reason;
        $this->updatedAt = new DateTime();
    }

    public function markAsCompleted(): void
    {
        $this->status = 'Completed';
        $this->updatedAt = new DateTime();
    }

    public function getCustomerNotes(): ?string
    {
        return $this->customerNotes;
    }

    public function setCustomerNotes(?string $notes): void
    {
        $this->customerNotes = $notes;
    }

    public function getStaffNotes(): ?string
    {
        return $this->staffNotes;
    }

    public function setStaffNotes(?string $notes): void
    {
        $this->staffNotes = $notes;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customerId,
            'service_id' => $this->serviceId,
            'staff_id' => $this->staffId,
            'scheduled_date' => $this->scheduledDate->format('Y-m-d'),
            'scheduled_time' => $this->scheduledTime->format('H:i:s'),
            'duration_minutes' => $this->durationMinutes,
            'status' => $this->status,
            'customer_notes' => $this->customerNotes,
            'staff_notes' => $this->staffNotes,
            'cancellation_reason' => $this->cancellationReason,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
        ];
    }
}
