<?php

declare(strict_types=1);

namespace App\Domain\Models;

use DateTime;

class Customer
{
    private ?int $id = null;
    private string $email;
    private string $phone;
    private string $firstName;
    private string $lastName;
    private ?DateTime $dateOfBirth = null;
    private ?string $gender = null;
    private ?string $address = null;
    private ?string $city = null;
    private ?string $postalCode = null;
    private ?string $country = null;
    private string $preferredContactMethod = 'SMS';
    private bool $communicationConsent = false;
    private string $loyaltyTier = 'Bronze';
    private float $totalLifetimeValue = 0;
    private string $status = 'Active';
    private ?DateTime $lastVisitDate = null;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private ?DateTime $deletedAt = null;

    public function __construct(
        string $email,
        string $phone,
        string $firstName,
        string $lastName
    ) {
        $this->email = $email;
        $this->phone = $phone;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getLoyaltyTier(): string
    {
        return $this->loyaltyTier;
    }

    public function updateLoyaltyTier(int $points): void
    {
        if ($points >= 25000) {
            $this->loyaltyTier = 'Platinum';
        } elseif ($points >= 10000) {
            $this->loyaltyTier = 'Gold';
        } elseif ($points >= 5000) {
            $this->loyaltyTier = 'Silver';
        } else {
            $this->loyaltyTier = 'Bronze';
        }
    }

    public function recordVisit(): void
    {
        $this->lastVisitDate = new DateTime();
    }

    public function getTotalLifetimeValue(): float
    {
        return $this->totalLifetimeValue;
    }

    public function addPurchase(float $amount): void
    {
        $this->totalLifetimeValue += $amount;
        $this->updatedAt = new DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'loyalty_tier' => $this->loyaltyTier,
            'total_lifetime_value' => $this->totalLifetimeValue,
            'status' => $this->status,
            'last_visit_date' => $this->lastVisitDate?->format('Y-m-d'),
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
        ];
    }
}
