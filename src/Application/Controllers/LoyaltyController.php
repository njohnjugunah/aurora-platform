<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Services\LoyaltyService;
use App\Domain\Repositories\LoyaltyRepository;
use Psr\Log\LoggerInterface;

class LoyaltyController
{
    public function __construct(
        private LoyaltyRepository $loyaltyRepository,
        private LoyaltyService $loyaltyService,
        private LoggerInterface $logger
    ) {}

    public function getCustomerPoints(int $customerId): array
    {
        try {
            $points = $this->loyaltyRepository->findByCustomerId($customerId);

            if (!$points) {
                $this->logger->warning('Customer loyalty record not found', ['customer_id' => $customerId]);

                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Loyalty record not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $tier = $this->loyaltyService->calculateTier($points['points']);

            $this->logger->info('Customer loyalty retrieved', ['customer_id' => $customerId]);

            return [
                'success' => true,
                'data' => [
                    'customerId' => $customerId,
                    'points' => $points['points'],
                    'tier' => $tier['tier'],
                    'discount' => $tier['discount'],
                    'nextTier' => $tier['next_tier'],
                    'pointsToNextTier' => $tier['points_to_next']
                ],
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to retrieve loyalty points', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve loyalty points'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function getLeaderboard(array $query): array
    {
        try {
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $period = $query['period'] ?? 'all_time';

            $result = $this->loyaltyRepository->getLeaderboard($limit, $period);

            $this->logger->info('Loyalty leaderboard retrieved', [
                'limit' => $limit,
                'period' => $period,
                'count' => count($result)
            ]);

            return [
                'success' => true,
                'data' => $result,
                'pagination' => ['limit' => $limit, 'total' => count($result)],
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to retrieve leaderboard', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve leaderboard'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function getTransactions(int $customerId, array $query): array
    {
        try {
            $page = (int) ($query['page'] ?? 1);
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $result = $this->loyaltyRepository->getTransactionHistory(
                $customerId,
                $page,
                $limit
            );

            if ($result === null) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Customer not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('Loyalty transactions retrieved', [
                'customer_id' => $customerId,
                'count' => count($result['transactions'])
            ]);

            return [
                'success' => true,
                'data' => $result['transactions'],
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
            $this->logger->error('Failed to retrieve loyalty transactions', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve loyalty transactions'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function redeemPoints(int $customerId, array $request): array
    {
        try {
            $errors = [];

            if (empty($request['points']) || !is_int($request['points']) || $request['points'] <= 0) {
                $errors[] = ['field' => 'points', 'message' => 'Points must be a positive integer'];
            }

            if (empty($request['reason']) || !is_string($request['reason'])) {
                $errors[] = ['field' => 'reason', 'message' => 'Reason is required'];
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

            $result = $this->loyaltyService->redeemPoints(
                customer_id: $customerId,
                points: $request['points'],
                reason: $request['reason']
            );

            $this->logger->info('Loyalty points redeemed', [
                'customer_id' => $customerId,
                'points' => $request['points']
            ]);

            return [
                'success' => true,
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to redeem points', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to redeem points'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function getTierBenefits(string $tier): array
    {
        try {
            $tiers = [
                'silver' => [
                    'name' => 'Silver',
                    'minPoints' => 0,
                    'maxPoints' => 4999,
                    'discount' => 0.05,
                    'benefits' => ['5% discount', 'Birthday bonus points']
                ],
                'gold' => [
                    'name' => 'Gold',
                    'minPoints' => 5000,
                    'maxPoints' => 14999,
                    'discount' => 0.10,
                    'benefits' => ['10% discount', '1.5x points earning', 'Priority booking']
                ],
                'platinum' => [
                    'name' => 'Platinum',
                    'minPoints' => 15000,
                    'maxPoints' => 999999,
                    'discount' => 0.15,
                    'benefits' => ['15% discount', '2x points earning', 'VIP support', 'Free services']
                ]
            ];

            if (!isset($tiers[$tier])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Tier not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('Tier benefits retrieved', ['tier' => $tier]);

            return [
                'success' => true,
                'data' => $tiers[$tier],
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to retrieve tier benefits', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve tier benefits'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }
}
