<?php

declare(strict_types=1);

namespace App\Domain\Models;

use DateTime;

class User
{
    private ?int $id = null;
    private string $email;
    private string $passwordHash;
    private string $firstName;
    private string $lastName;
    private string $status;
    private ?DateTime $lastLoginAt = null;
    private ?string $lastLoginIp = null;
    private int $loginAttempts = 0;
    private ?DateTime $lockedUntil = null;
    private ?DateTime $passwordChangedAt = null;
    private bool $mfaEnabled = false;
    private ?string $mfaSecret = null;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private ?DateTime $deletedAt = null;

    public function __construct(
        string $email,
        string $passwordHash,
        string $firstName,
        string $lastName,
        string $status = 'Active'
    ) {
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $hash): void
    {
        $this->passwordHash = $hash;
        $this->passwordChangedAt = new DateTime();
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    public function recordLoginAttempt(string $ipAddress): void
    {
        $this->loginAttempts++;
        $this->lastLoginIp = $ipAddress;

        if ($this->loginAttempts >= 5) {
            $this->lockedUntil = (new DateTime())->modify('+1 hour');
        }
    }

    public function recordSuccessfulLogin(string $ipAddress): void
    {
        $this->loginAttempts = 0;
        $this->lastLoginAt = new DateTime();
        $this->lastLoginIp = $ipAddress;
        $this->lockedUntil = null;
    }

    public function isLocked(): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > new DateTime();
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'last_login_at' => $this->lastLoginAt?->format('c'),
            'last_login_ip' => $this->lastLoginIp,
            'mfa_enabled' => $this->mfaEnabled,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
        ];
    }
}
