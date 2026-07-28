<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\StockRepository;
use Psr\Log\LoggerInterface;

class InventoryService
{
    public function __construct(
        private StockRepository $stockRepo,
        private LoggerInterface $logger
    ) {}

    public function deductStock(int $productId, int $quantity): void
    {
        $stock = $this->stockRepo->findByProductId($productId);

        if ($stock['quantity_available'] < $quantity) {
            throw new \Exception('Insufficient stock');
        }

        $newQuantity = $stock['quantity_on_hand'] - $quantity;

        $this->stockRepo->update($productId, [
            'quantity_on_hand' => $newQuantity
        ]);

        $this->logger->info('Stock deducted', [
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
    }

    public function recordPurchase(int $productId, int $quantity): void
    {
        $this->deductStock($productId, $quantity);

        $this->logger->info('Purchase recorded', [
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
    }

    public function recordRefund(int $productId, int $quantity): void
    {
        $this->stockRepo->addStock($productId, $quantity);

        $this->logger->info('Refund recorded', [
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
    }
}
