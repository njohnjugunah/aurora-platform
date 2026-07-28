<?php

declare(strict_types=1);

namespace App\Domain\Models;

use DateTime;

class Sale
{
    private ?int $id = null;
    private ?int $appointmentId = null;
    private int $customerId;
    private ?int $staffId = null;
    private DateTime $saleDate;
    private ?DateTime $saleTime = null;
    private float $subtotal = 0;
    private float $discountAmount = 0;
    private float $discountPercentage = 0;
    private float $taxAmount = 0;
    private float $totalAmount = 0;
    private string $status = 'Draft';
    private ?string $notes = null;
    private array $lineItems = [];
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private ?int $createdBy = null;
    private ?int $updatedBy = null;
    private ?DateTime $deletedAt = null;

    public function __construct(
        int $customerId,
        ?int $appointmentId = null,
        ?int $staffId = null
    ) {
        $this->customerId = $customerId;
        $this->appointmentId = $appointmentId;
        $this->staffId = $staffId;
        $this->saleDate = new DateTime();
        $this->saleTime = new DateTime();
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function markAsCompleted(): void
    {
        $this->status = 'Completed';
        $this->updatedAt = new DateTime();
    }

    public function markAsPaid(): void
    {
        $this->status = 'Completed';
        $this->updatedAt = new DateTime();
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function calculateTotal(): void
    {
        $subtotal = 0;
        foreach ($this->lineItems as $item) {
            $subtotal += $item['line_total'] ?? ($item['quantity'] * $item['unit_price']);
        }

        $this->subtotal = $subtotal;
        $discounted = $subtotal - $this->discountAmount;
        $this->taxAmount = $discounted * 0.16;
        $this->totalAmount = $discounted + $this->taxAmount;
    }

    public function addLineItem(
        int $productId,
        int $quantity,
        float $unitPrice,
        float $discountAmount = 0,
        float $taxAmount = 0
    ): void {
        $this->lineItems[] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'line_total' => ($quantity * $unitPrice) - $discountAmount + $taxAmount,
        ];

        $this->calculateTotal();
    }

    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointmentId,
            'customer_id' => $this->customerId,
            'staff_id' => $this->staffId,
            'sale_date' => $this->saleDate->format('Y-m-d'),
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discountAmount,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
            'line_items' => $this->lineItems,
            'created_at' => $this->createdAt->format('c'),
        ];
    }
}
