<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Repositories\StaffRepository;
use Psr\Log\LoggerInterface;

class StaffController
{
    public function __construct(
        private StaffRepository $staffRepository,
        private LoggerInterface $logger
    ) {}

    public function list(array $query): array
    {
        try {
            $page = (int) ($query['page'] ?? 1);
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $filters = [
                'status' => $query['status'] ?? 'active',
                'specialization' => $query['specialization'] ?? null,
            ];

            $sort = $query['sort'] ?? 'id';
            $order = strtolower($query['order'] ?? 'asc');
            $order = in_array($order, ['asc', 'desc']) ? $order : 'asc';

            $result = $this->staffRepository->findFiltered(
                $filters,
                $sort,
                $order,
                $page,
                $limit
            );

            $this->logger->info('Staff listed', [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total']
            ]);

            return [
                'success' => true,
                'data' => $result['staff'],
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
            $this->logger->error('Failed to list staff', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve staff'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function get(int $id): array
    {
        try {
            $staff = $this->staffRepository->findById($id);

            if (!$staff) {
                $this->logger->warning('Staff not found', ['id' => $id]);

                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Staff member not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('Staff retrieved', ['id' => $id]);

            return [
                'success' => true,
                'data' => $staff,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve staff', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve staff'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function performance(int $id, array $query): array
    {
        try {
            $staff = $this->staffRepository->findById($id);

            if (!$staff) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Staff member not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $startDate = $query['start_date'] ?? date('Y-m-01');
            $endDate = $query['end_date'] ?? date('Y-m-d');

            $performance = $this->staffRepository->getPerformanceMetrics($id, $startDate, $endDate);

            $this->logger->info('Staff performance retrieved', ['id' => $id]);

            return [
                'success' => true,
                'data' => [
                    'staffId' => $id,
                    'name' => $staff['name'],
                    'period' => ['startDate' => $startDate, 'endDate' => $endDate],
                    'metrics' => $performance
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve staff performance', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve staff performance'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function commission(int $id, array $query): array
    {
        try {
            $staff = $this->staffRepository->findById($id);

            if (!$staff) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Staff member not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $startDate = $query['start_date'] ?? date('Y-m-01');
            $endDate = $query['end_date'] ?? date('Y-m-d');

            $commission = $this->staffRepository->calculateCommission($id, $startDate, $endDate);

            $this->logger->info('Staff commission calculated', ['id' => $id]);

            return [
                'success' => true,
                'data' => [
                    'staffId' => $id,
                    'name' => $staff['name'],
                    'period' => ['startDate' => $startDate, 'endDate' => $endDate],
                    'commission' => $commission
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to calculate staff commission', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to calculate commission'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }
}
