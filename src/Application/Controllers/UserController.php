<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Repositories\UserRepository;
use Psr\Log\LoggerInterface;

class UserController
{
    public function __construct(
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {}

    public function list(array $query): array
    {
        try {
            $page = (int) ($query['page'] ?? 1);
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $filters = [
                'role' => $query['role'] ?? null,
                'status' => $query['status'] ?? 'active',
            ];

            $sort = $query['sort'] ?? 'id';
            $order = strtolower($query['order'] ?? 'asc');
            $order = in_array($order, ['asc', 'desc']) ? $order : 'asc';

            $result = $this->userRepository->findFiltered(
                $filters,
                $sort,
                $order,
                $page,
                $limit
            );

            $this->logger->info('Users listed', [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total']
            ]);

            return [
                'success' => true,
                'data' => $result['users'],
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
            $this->logger->error('Failed to list users', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve users'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function get(int $id): array
    {
        try {
            $user = $this->userRepository->findById($id);

            if (!$user) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'User not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('User retrieved', ['id' => $id]);

            return [
                'success' => true,
                'data' => $user,
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to retrieve user', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve user'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function create(array $request): array
    {
        try {
            $errors = [];

            if (empty($request['email']) || !filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['field' => 'email', 'message' => 'Valid email is required'];
            }

            if (empty($request['password']) || strlen($request['password']) < 8) {
                $errors[] = ['field' => 'password', 'message' => 'Password must be at least 8 characters'];
            }

            if (empty($request['name']) || strlen($request['name']) < 2) {
                $errors[] = ['field' => 'name', 'message' => 'Name is required and must be at least 2 characters'];
            }

            if (empty($request['role']) || !in_array($request['role'], ['admin', 'manager', 'receptionist', 'staff'])) {
                $errors[] = ['field' => 'role', 'message' => 'Valid role is required'];
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

            $existingUser = $this->userRepository->findByEmail($request['email']);
            if ($existingUser) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'DUPLICATE_RESOURCE',
                        'message' => 'Email already in use'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $user = [
                'email' => $request['email'],
                'password' => password_hash($request['password'], PASSWORD_BCRYPT),
                'name' => $request['name'],
                'role' => $request['role'],
                'status' => 'active',
                'phone' => $request['phone'] ?? null,
            ];

            $result = $this->userRepository->create($user);

            unset($result['password']);

            $this->logger->info('User created', [
                'user_id' => $result['id'],
                'email' => $request['email'],
                'role' => $request['role']
            ]);

            return [
                'success' => true,
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to create user', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to create user'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function update(int $id, array $request): array
    {
        try {
            $user = $this->userRepository->findById($id);

            if (!$user) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'User not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $errors = [];

            if (isset($request['email'])) {
                if (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = ['field' => 'email', 'message' => 'Valid email is required'];
                } else {
                    $existingUser = $this->userRepository->findByEmail($request['email']);
                    if ($existingUser && $existingUser['id'] !== $id) {
                        $errors[] = ['field' => 'email', 'message' => 'Email already in use'];
                    }
                }
            }

            if (isset($request['password']) && strlen($request['password']) < 8) {
                $errors[] = ['field' => 'password', 'message' => 'Password must be at least 8 characters'];
            }

            if (isset($request['role']) && !in_array($request['role'], ['admin', 'manager', 'receptionist', 'staff'])) {
                $errors[] = ['field' => 'role', 'message' => 'Valid role is required'];
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
                'email' => $request['email'] ?? $user['email'],
                'name' => $request['name'] ?? $user['name'],
                'role' => $request['role'] ?? $user['role'],
                'status' => $request['status'] ?? $user['status'],
                'phone' => $request['phone'] ?? $user['phone'],
            ];

            if (isset($request['password'])) {
                $updated['password'] = password_hash($request['password'], PASSWORD_BCRYPT);
            }

            $result = $this->userRepository->update($updated);

            unset($result['password']);

            $this->logger->info('User updated', ['id' => $id]);

            return [
                'success' => true,
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to update user', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to update user'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function delete(int $id): array
    {
        try {
            $user = $this->userRepository->findById($id);

            if (!$user) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'User not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->userRepository->delete($id);

            $this->logger->info('User deleted', ['id' => $id]);

            return [
                'success' => true,
                'data' => ['id' => $id],
                'meta' => ['timestamp' => date('c')]
            ];

} catch (\Exception $e) {
            $this->logger->error('Failed to delete user', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to delete user'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }
}
