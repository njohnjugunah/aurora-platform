<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Services\AuthenticationService;
use App\Application\Validators\LoginValidator;
use App\Application\Exceptions\ValidationException;
use Psr\Log\LoggerInterface;

class AuthController
{
    public function __construct(
        private AuthenticationService $authService,
        private LoginValidator $validator,
        private LoggerInterface $logger
    ) {}

    public function login(array $request): array
    {
        try {
            $this->validator->validate($request);

            $result = $this->authService->authenticate(
                $request['email'],
                $request['password']
            );

            $this->logger->info('User login successful', [
                'user_id' => $result['user']['id'],
                'email' => $request['email']
            ]);

            return [
                'success' => true,
                'status' => 'success',
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (ValidationException $e) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                    'details' => $e->getErrors()
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Login failed', [
                'email' => $request['email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Invalid email or password'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    public function logout(array $request): array
    {
        $this->logger->info('User logout');

        return [
            'success' => true,
            'status' => 'success',
            'meta' => ['timestamp' => date('c')]
        ];
    }

    public function refresh(array $request): array
    {
        try {
            $token = $request['Authorization'] ?? null;
            if (!$token) {
                throw new \Exception('No token provided');
            }

            $token = str_replace('Bearer ', '', $token);
            $result = $this->authService->refreshToken($token);

            return [
                'success' => true,
                'status' => 'success',
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Token refresh failed'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }
}
