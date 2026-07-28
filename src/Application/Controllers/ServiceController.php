<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Repositories\ServiceRepository;
use Psr\Log\LoggerInterface;

class ServiceController
{
    public function __construct(
        private ServiceRepository $serviceRepository,
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
                'category' => $query['category'] ?? null,
            ];

            $sort = $query['sort'] ?? 'id';
            $order = strtolower($query['order'] ?? 'asc');
            $order = in_array($order, ['asc', 'desc']) ? $order : 'asc';

            $result = $this->serviceRepository->findFiltered(
                $filters,
                $sort,
                $order,
                $page,
                $limit
            );

            $this->logger->info('Services listed', [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total']
            ]);

            return [
                'success' => true,
                'data' => $result['services'],
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
            $this->logger->error('Failed to list services', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve services'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function get(int $id): array
    {
        try {
            $service = $this->serviceRepository->findById($id);

            if (!$service) {
                $this->logger->warning('Service not found', ['id' => $id]);

                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Service not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('Service retrieved', ['id' => $id]);

            return [
                'success' => true,
                'data' => $service,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve service', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve service'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function create(array $request): array
    {
        try {
            $errors = [];

            if (empty($request['name']) || !is_string($request['name'])) {
                $errors[] = ['field' => 'name', 'message' => 'Name is required and must be a string'];
            }

            if (empty($request['price']) || !is_numeric($request['price']) || $request['price'] <= 0) {
                $errors[] = ['field' => 'price', 'message' => 'Price must be a positive number'];
            }

            if (empty($request['duration']) || !is_int($request['duration']) || $request['duration'] <= 0) {
                $errors[] = ['field' => 'duration', 'message' => 'Duration must be a positive integer (in minutes)'];
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

            $service = [
                'name' => $request['name'],
                'description' => $request['description'] ?? null,
                'price' => $request['price'],
                'duration' => $request['duration'],
                'category' => $request['category'] ?? null,
                'status' => 'active',
            ];

            $result = $this->serviceRepository->create($service);

            $this->logger->info('Service created', [
                'service_id' => $result['id'],
                'name' => $request['name']
            ]);

            return [
                'success' => true,
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to create service', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to create service'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function update(int $id, array $request): array
    {
        try {
            $service = $this->serviceRepository->findById($id);

            if (!$service) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Service not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $errors = [];

            if (isset($request['price']) && (!is_numeric($request['price']) || $request['price'] <= 0)) {
                $errors[] = ['field' => 'price', 'message' => 'Price must be a positive number'];
            }

            if (isset($request['duration']) && (!is_int($request['duration']) || $request['duration'] <= 0)) {
                $errors[] = ['field' => 'duration', 'message' => 'Duration must be a positive integer'];
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

            $updated = [
                'id' => $id,
                'name' => $request['name'] ?? $service['name'],
                'description' => $request['description'] ?? $service['description'],
                'price' => $request['price'] ?? $service['price'],
                'duration' => $request['duration'] ?? $service['duration'],
                'category' => $request['category'] ?? $service['category'],
                'status' => $request['status'] ?? $service['status'],
            ];

            $result = $this->serviceRepository->update($updated);

            $this->logger->info('Service updated', ['id' => $id]);

            return [
                'success' => true,
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to update service', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to update service'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function delete(int $id): array
    {
        try {
            $service = $this->serviceRepository->findById($id);

            if (!$service) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Service not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->serviceRepository->delete($id);

            $this->logger->info('Service deleted', ['id' => $id]);

            return [
                'success' => true,
                'data' => ['id' => $id],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete service', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to delete service'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }
}
