<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\AuthController;
use App\Application\Services\AuthenticationService;
use App\Application\Services\JWTService;
use App\Application\Validators\LoginValidator;
use App\Domain\Repositories\UserRepository;
use Psr\Log\LoggerInterface;

class AuthControllerTest extends TestCase
{
    private AuthController $controller;
    private $authService;
    private $jwtService;
    private $loginValidator;
    private $userRepository;
    private $logger;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthenticationService::class);
        $this->jwtService = $this->createMock(JWTService::class);
        $this->loginValidator = $this->createMock(LoginValidator::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new AuthController(
            $this->authService,
            $this->jwtService,
            $this->loginValidator,
            $this->userRepository,
            $this->logger
        );
    }

    public function testLoginSuccess(): void
    {
        $request = ['email' => 'user@example.com', 'password' => 'password123'];

        $this->loginValidator->method('validate')->willReturn(true);
        $this->authService->method('login')->willReturn(['user_id' => 1, 'email' => 'user@example.com']);
        $this->jwtService->method('generateToken')->willReturn('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...');

        $result = $this->controller->login($request);

        $this->assertEquals('success', $result['status']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $request = ['email' => 'user@example.com', 'password' => 'wrongpassword'];

        $this->loginValidator->method('validate')->willReturn(true);
        $this->authService->method('login')->willReturn(null);

        $result = $this->controller->login($request);

        $this->assertEquals('error', $result['status']);
    }

    public function testLoginValidationError(): void
    {
        $request = ['email' => 'invalid', 'password' => 'pass'];

        $this->loginValidator->method('validate')->will($this->throwException(new \Exception('Validation failed')));

        $result = $this->controller->login($request);

        $this->assertEquals('error', $result['status']);
    }

    public function testLogout(): void
    {
        $result = $this->controller->logout(['token' => 'some_token']);

        $this->assertEquals('success', $result['status']);
    }

    public function testRefreshToken(): void
    {
        $request = ['refresh_token' => 'old_refresh_token'];

        $this->jwtService->method('refreshToken')->willReturn('new_access_token');

        $result = $this->controller->refresh($request);

        $this->assertEquals('success', $result['status']);
    }

    public function testRefreshTokenInvalid(): void
    {
        $request = ['refresh_token' => 'invalid_token'];

        $this->jwtService->method('refreshToken')->willReturn(null);

        $result = $this->controller->refresh($request);

        $this->assertEquals('error', $result['status']);
    }
}
