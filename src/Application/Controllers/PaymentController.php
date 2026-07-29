<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Services\PaymentService;
use App\Application\Validators\PaymentValidator;
use App\Application\Exceptions\ValidationException;
use App\Domain\Repositories\PaymentRepository;
use Psr\Log\LoggerInterface;

class PaymentController
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private PaymentService $paymentService,
        private PaymentValidator $validator,
        private LoggerInterface $logger
    ) {}

    public function list(array $query): array
    {
        try {
            $page = (int) ($query['page'] ?? 1);
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $filters = [
                'method' => $query['method'] ?? null,
                'status' => $query['status'] ?? null,
                'date' => $query['date'] ?? null,
            ];

            $sort = $query['sort'] ?? 'id';
            $order = strtolower($query['order'] ?? 'desc');
            $order = in_array($order, ['asc', 'desc']) ? $order : 'desc';

            $result = $this->paymentRepository->findFiltered(
                $filters,
                $sort,
                $order,
                $page,
                $limit
            );

            $this->logger->info('Payments listed', [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total']
            ]);

            return [
                'success' => true,
                'data' => $result['payments'],
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
            $this->logger->error('Failed to list payments', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve payments'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function get(int $id): array
    {
        try {
            $payment = $this->paymentRepository->findById($id);

            if (!$payment) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Payment not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('Payment retrieved', ['id' => $id]);

            return [
                'success' => true,
                'data' => $payment,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve payment', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve payment'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function verify(int $id, array $request): array
    {
        try {
            $payment = $this->paymentRepository->findById($id);

            if (!$payment) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Payment not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            if ($payment['status'] === 'verified') {
                return [
                    'success' => true,
                    'data' => $payment,
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $verificationCode = $request['verificationCode'] ?? null;

            $result = $this->paymentService->verifyPayment($id, $verificationCode);

            $this->logger->info('Payment verified', ['id' => $id]);

            return [
                'success' => true,
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to verify payment', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to verify payment'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function refund(int $id, array $request): array
    {
        try {
            $payment = $this->paymentRepository->findById($id);

            if (!$payment) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Payment not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            if ($payment['status'] !== 'verified') {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'BUSINESS_RULE_VIOLATION',
                        'message' => 'Only verified payments can be refunded'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $reason = $request['reason'] ?? 'No reason provided';

            $result = $this->paymentService->refund(
                payment_id: $id,
                reason: $reason
            );

            $this->logger->info('Payment refunded', ['id' => $id]);

            return [
                'success' => true,
                'data' => [
                    'paymentId' => $id,
                    'refundId' => $result['refund_id'],
                    'status' => 'refunded',
                    'processedAt' => date('c')
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to refund payment', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to refund payment'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }
}
