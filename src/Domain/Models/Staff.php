<?php

declare(strict_types=1);

namespace App\Domain\Models;

use DateTime;

class Staff
{
    private ?int $id = null;
    private int $userId;
    private string $firstName;
    private string $lastName;
    private string $role;
    private string $experienceLevel;
    private string $status;
    private string $phone;
    private string $email;
    private float $salaryBase = 0;
    private float $commissionPercentage = 0;
    private ?string $bankAccount = null;
    private DateTime $startedDate;
    private ?DateTime $terminatedDate = null;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private ?DateTime $deletedAt = null;

    public function __construct(
        int $userId,
        string $firstName,
        string $lastName,
        string $role,
        string $experienceLevel = 'Junior'
    ) {
        $this->userId = $userId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->role = $role;
        $this->experienceLevel = $experienceLevel;
        $this->status = 'Active';
        $this->startedDate = new DateTime();
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

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        if ($status === 'Terminated') {
            $this->terminatedDate = new DateTime();
        }
    }

    public function getCommissionPercentage(): float
    {
        return $this->commissionPercentage;
    }

    public function setCommissionPercentage(float $percentage): void
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Commission must be between 0 and 100');
        }
        $this->commissionPercentage = $percentage;
    }

    public function calculateCommission(float $revenue): float
    {
        return ($revenue * $this->commissionPercentage) / 100;
    }

    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'role' => $this->role,
            'experience_level' => $this->experienceLevel,
            'status' => $this->status,
            'phone' => $this->phone ?? '',
            'email' => $this->email ?? '',
            'commission_percentage' => $this->commissionPercentage,
            'created_at' => $this->createdAt->format('c'),
        ];
    }
}
