<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Services\InventoryService;
use App\Domain\Repositories\StockRepository;
use Psr\Log\LoggerInterface;

class InventoryController
{
    public function __construct(
        private StockRepository $stockRepository,
        private InventoryService $inventoryService,
        private LoggerInterface $logger
    ) {}

    public function listProducts(array $query): array
    {
        try {
            $page = (int) ($query['page'] ?? 1);
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $filters = [
                'category' => $query['category'] ?? null,
                'status' => $query['status'] ?? 'active',
                'in_stock' => $query['in_stock'] ?? null,
            ];

            $sort = $query['sort'] ?? 'id';
            $order = strtolower($query['order'] ?? 'asc');
            $order = in_array($order, ['asc', 'desc']) ? $order : 'asc';

            $result = $this->stockRepository->findProducts(
                $filters,
                $sort,
                $order,
                $page,
                $limit
            );

            $this->logger->info('Products listed', [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total']
            ]);

            return [
                'success' => true,
                'status' => 'success',
                'data' => $result['products'],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $result['total'],
                    'pages' => ceil($result['total'] / $limit),
                    'hasMore' => ($page * $limit) < $result['total']
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to list products', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve products'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function getStock(int $productId): array
    {
        try {
            $stock = $this->stockRepository->getStockLevel($productId);

            if (!$stock) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Product not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('Stock retrieved', ['product_id' => $productId]);

            return [
                'success' => true,
                'status' => 'success',
                'data' => $stock,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve stock', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve stock'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function getMovements(int $productId, array $query): array
    {
        try {
            $page = (int) ($query['page'] ?? 1);
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $filters = [
                'type' => $query['type'] ?? null,
                'start_date' => $query['start_date'] ?? null,
                'end_date' => $query['end_date'] ?? null,
            ];

            $result = $this->stockRepository->getMovements(
                $productId,
                $filters,
                $page,
                $limit
            );

            if ($result === null) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Product not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('Stock movements retrieved', [
                'product_id' => $productId,
                'count' => count($result['movements'])
            ]);

            return [
                'success' => true,
                'status' => 'success',
                'data' => $result['movements'],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $result['total'],
                    'pages' => ceil($result['total'] / $limit),
                    'hasMore' => ($page * $limit) < $result['total']
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve movements', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve stock movements'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function adjustStock(int $productId, array $request): array
    {
        try {
            $errors = [];

            if (empty($request['quantity']) || !is_int($request['quantity'])) {
                $errors[] = ['field' => 'quantity', 'message' => 'Quantity must be an integer'];
            }

            $types = ['purchase', 'adjustment', 'return', 'damage'];
            if (empty($request['type']) || !in_array($request['type'], $types)) {
                $errors[] = ['field' => 'type', 'message' => 'Valid movement type is required'];
            }

            if (!empty($errors)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Validation failed',
                        'details' => $errors
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $result = $this->inventoryService->adjustStock(
                product_id: $productId,
                quantity: $request['quantity'],
                type: $request['type'],
                reason: $request['reason'] ?? null,
                reference: $request['reference'] ?? null
            );

            $this->logger->info('Stock adjusted', [
                'product_id' => $productId,
                'quantity' => $request['quantity'],
                'type' => $request['type']
            ]);

            return [
                'success' => true,
                'status' => 'success',
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to adjust stock', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to adjust stock'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function getLowStock(array $query): array
    {
        try {
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $result = $this->stockRepository->getLowStockItems($limit);

            $this->logger->info('Low stock items retrieved', ['count' => count($result)]);

            return [
                'success' => true,
                'status' => 'success',
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve low stock items', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve low stock items'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }
}
