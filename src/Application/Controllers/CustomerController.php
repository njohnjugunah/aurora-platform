<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Validators\CustomerValidator;
use App\Application\Exceptions\ValidationException;
use App\Domain\Repositories\CustomerRepository;
use Psr\Log\LoggerInterface;

class CustomerController
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private CustomerValidator $validator,
        private LoggerInterface $logger
    ) {}

    public function list(array $query): array
    {
        try {
            $page = (int) ($query['page'] ?? 1);
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $filters = [
                'loyalty_tier' => $query['loyalty_tier'] ?? null,
                'min_visits' => $query['min_visits'] ?? null,
                'status' => $query['status'] ?? 'active',
            ];

            $sort = $query['sort'] ?? 'id';
            $order = strtolower($query['order'] ?? 'asc');
            $order = in_array($order, ['asc', 'desc']) ? $order : 'asc';

            $result = $this->customerRepository->findFiltered(
                $filters,
                $sort,
                $order,
                $page,
                $limit
            );

            $this->logger->info('Customers listed', [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total']
            ]);

            return [
                'success' => true,
                'data' => $result['customers'],
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
            $this->logger->error('Failed to list customers', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve customers'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function get(int $id): array
    {
        try {
            $customer = $this->customerRepository->findById($id);

            if (!$customer) {
                $this->logger->warning('Customer not found', ['id' => $id]);

                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Customer not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('Customer retrieved', ['id' => $id]);

            return [
                'success' => true,
                'data' => $customer,
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to retrieve customer', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve customer'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function create(array $request): array
    {
        try {
            $this->validator->validate($request);

            $customer = [
                'name' => $request['name'],
                'phone' => $request['phone'],
                'email' => $request['email'] ?? null,
                'date_of_birth' => $request['dateOfBirth'] ?? null,
                'status' => 'active',
            ];

            $result = $this->customerRepository->create($customer);

            $this->logger->info('Customer created', [
                'customer_id' => $result['id'],
                'phone' => $request['phone']
            ]);

            return [
                'success' => true,
                'data' => $result,
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
            $this->logger->error('Failed to create customer', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to create customer'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function update(int $id, array $request): array
    {
        try {
            $customer = $this->customerRepository->findById($id);

            if (!$customer) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Customer not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->validator->validateUpdate($request);

            $updated = [
                'id' => $id,
                'name' => $request['name'] ?? $customer['name'],
                'phone' => $request['phone'] ?? $customer['phone'],
                'email' => $request['email'] ?? $customer['email'],
                'date_of_birth' => $request['dateOfBirth'] ?? $customer['dateOfBirth'],
                'status' => $request['status'] ?? $customer['status'],
            ];

            $result = $this->customerRepository->update($updated);

            $this->logger->info('Customer updated', ['id' => $id]);

            return [
                'success' => true,
                'data' => $result,
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
            $this->logger->error('Failed to update customer', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to update customer'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function delete(int $id): array
    {
        try {
            $customer = $this->customerRepository->findById($id);

            if (!$customer) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Customer not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->customerRepository->delete($id);

            $this->logger->info('Customer deleted', ['id' => $id]);

            return [
                'success' => true,
                'data' => ['id' => $id],
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to delete customer', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to delete customer'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }
}
