<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Services\PaymentService;
use App\Application\Services\InventoryService;
use App\Application\Services\LoyaltyService;
use App\Application\Validators\PaymentValidator;
use App\Application\Exceptions\ValidationException;
use App\Domain\Repositories\SaleRepository;
use App\Domain\Repositories\PaymentRepository;
use Psr\Log\LoggerInterface;

class SaleController
{
    public function __construct(
        private SaleRepository $saleRepository,
        private PaymentRepository $paymentRepository,
        private PaymentService $paymentService,
        private InventoryService $inventoryService,
        private LoyaltyService $loyaltyService,
        private PaymentValidator $paymentValidator,
        private LoggerInterface $logger
    ) {}

    public function list(array $query): array
    {
        try {
            $page = (int) ($query['page'] ?? 1);
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $filters = [
                'date' => $query['date'] ?? null,
                'staff_id' => $query['staff_id'] ?? null,
                'status' => $query['status'] ?? null,
                'min_amount' => $query['min_amount'] ?? null,
                'max_amount' => $query['max_amount'] ?? null,
            ];

            $sort = $query['sort'] ?? 'id';
            $order = strtolower($query['order'] ?? 'desc');
            $order = in_array($order, ['asc', 'desc']) ? $order : 'desc';

            $result = $this->saleRepository->findFiltered(
                $filters,
                $sort,
                $order,
                $page,
                $limit
            );

            $this->logger->info('Sales listed', [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total']
            ]);

            return [
                'success' => true,
                'data' => $result['sales'],
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
            $this->logger->error('Failed to list sales', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve sales'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function get(int $id): array
    {
        try {
            $sale = $this->saleRepository->findById($id);

            if (!$sale) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Sale not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('Sale retrieved', ['id' => $id]);

            return [
                'success' => true,
                'data' => $sale,
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to retrieve sale', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve sale'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function create(array $request): array
    {
        try {
            $errors = [];

            if (!isset($request['items']) || !is_array($request['items']) || empty($request['items'])) {
                $errors[] = ['field' => 'items', 'message' => 'At least one item is required'];
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

            $sale = [
                'customer_id' => $request['customerId'] ?? null,
                'staff_id' => $request['staffId'] ?? null,
                'items' => $request['items'],
                'discount_type' => $request['discountType'] ?? 'percentage',
                'discount_value' => $request['discountValue'] ?? 0,
                'notes' => $request['notes'] ?? null,
                'status' => 'open',
            ];

            $result = $this->saleRepository->create($sale);

            $this->logger->info('Sale created', [
                'sale_id' => $result['id'],
                'customer_id' => $request['customerId'] ?? 'walk-in'
            ]);

            return [
                'success' => true,
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to create sale', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to create sale'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function payment(int $id, array $request): array
    {
        try {
            $sale = $this->saleRepository->findById($id);

            if (!$sale) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Sale not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            if ($sale['status'] === 'paid') {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'BUSINESS_RULE_VIOLATION',
                        'message' => 'Sale is already paid'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->paymentValidator->validate($request);

            $paymentMethod = $request['method'];
            $amount = $request['amount'] ?? $sale['total'];

            if ($amount > $sale['total']) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'BUSINESS_RULE_VIOLATION',
                        'message' => 'Payment amount exceeds sale total'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $payment = $this->paymentService->processPayment(
                sale_id: $id,
                method: $paymentMethod,
                amount: $amount,
                reference_data: $request
            );

            $this->saleRepository->updateStatus($id, 'paid');

            if ($sale['customer_id']) {
                $this->loyaltyService->awardPoints(
                    customer_id: $sale['customer_id'],
                    amount: $amount,
                    reference: "SALE-{$id}"
                );
            }

            $this->logger->info('Payment processed', [
                'sale_id' => $id,
                'method' => $paymentMethod,
                'amount' => $amount
            ]);

            return [
                'success' => true,
                'data' => [
                    'paymentId' => $payment['id'],
                    'saleId' => $id,
                    'method' => $paymentMethod,
                    'amount' => $amount,
                    'status' => $payment['status'],
                    'reference' => $payment['reference'] ?? null
                ],
                'meta' => ['timestamp' => date('c')]
            ];

} catch (ValidationException $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                    'details' => $e->getErrors()
                ],
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to process payment', [
                'sale_id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to process payment'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function refund(int $id, array $request): array
    {
        try {
            $sale = $this->saleRepository->findById($id);

            if (!$sale) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Sale not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            if ($sale['status'] !== 'paid') {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'BUSINESS_RULE_VIOLATION',
                        'message' => 'Only paid sales can be refunded'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $reason = $request['reason'] ?? 'No reason provided';

            $refund = $this->paymentService->refund(
                sale_id: $id,
                reason: $reason
            );

            $this->saleRepository->updateStatus($id, 'refunded');

            if ($sale['customer_id']) {
                $this->loyaltyService->reversePoints(
                    customer_id: $sale['customer_id'],
                    reference: "SALE-{$id}"
                );
            }

            $this->logger->info('Sale refunded', [
                'sale_id' => $id,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'data' => [
                    'refundId' => $refund['id'],
                    'saleId' => $id,
                    'amount' => $sale['total'],
                    'status' => 'processed',
                    'processedAt' => date('c')
                ],
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to refund sale', [
                'sale_id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to refund sale'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }
}
