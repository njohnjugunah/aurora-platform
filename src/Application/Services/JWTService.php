<?php

declare(strict_types=1);

namespace App\Application\Services;

class JWTService
{
    private string $secret;
    private string $algorithm;
    private int $expiration;

    public function __construct()
    {
        $this->secret = getenv('JWT_SECRET') ?: 'default-secret-change-in-production';
        $this->algorithm = getenv('JWT_ALGORITHM') ?: 'HS256';
        $this->expiration = (int)getenv('JWT_EXPIRATION') ?: 3600;
    }

    public function generateToken(array $payload): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expiration;

        $header = base64_encode(json_encode(['alg' => $this->algorithm, 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            "$header.$payload",
            $this->secret,
            true
        );
        $signature = base64_encode($signature);

        return "$header.$payload.$signature";
    }

    public function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $expectedSignature = base64_encode(
            hash_hmac('sha256', "$header.$payload", $this->secret, true)
        );

        if ($signature !== $expectedSignature) {
            return null;
        }

        $decoded = json_decode(base64_decode($payload), true);

        if (!$decoded || ($decoded['exp'] ?? 0) < time()) {
            return null;
        }

        return $decoded;
    }
}
