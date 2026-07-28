<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\UserRepository;
use App\Application\Exceptions\InvalidBookingException;

class AuthenticationService
{
    public function __construct(
        private UserRepository $userRepo,
        private JWTService $jwtService
    ) {}

    public function authenticate(string $email, string $password): array
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user || !$user->verifyPassword($password)) {
            throw new InvalidBookingException('Invalid credentials');
        }

        if ($user->isLocked()) {
            throw new InvalidBookingException('Account is locked. Try again later.');
        }

        $token = $this->jwtService->generateToken([
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => ['user']
        ]);

        $user->recordSuccessfulLogin($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $this->userRepo->save($user);

        return [
            'token' => $token,
            'user' => $user->toArray(),
            'expires_in' => 3600
        ];
    }

    public function refreshToken(string $token): array
    {
        $payload = $this->jwtService->verifyToken($token);

        if (!$payload) {
            throw new \Exception('Invalid token');
        }

        $newToken = $this->jwtService->generateToken($payload);

        return [
            'token' => $newToken,
            'expires_in' => 3600
        ];
    }

    public function validateToken(string $token): ?array
    {
        return $this->jwtService->verifyToken($token);
    }
}
