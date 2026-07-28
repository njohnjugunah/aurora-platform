<?php

declare(strict_types=1);

namespace App\Domain\Models;

use DateTime;

class Service
{
    private ?int $id = null;
    private string $name;
    private string $category;
    private ?string $description = null;
    private float $basePrice;
    private int $durationMinutes;
    private string $status = 'Active';
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private ?DateTime $deletedAt = null;

    public function __construct(
        string $name,
        string $category,
        float $basePrice,
        int $durationMinutes
    ) {
        $this->name = $name;
        $this->category = $category;
        $this->basePrice = $basePrice;
        $this->durationMinutes = $durationMinutes;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'base_price' => $this->basePrice,
            'duration_minutes' => $this->durationMinutes,
            'status' => $this->status,
            'created_at' => $this->createdAt->format('c'),
        ];
    }
}
