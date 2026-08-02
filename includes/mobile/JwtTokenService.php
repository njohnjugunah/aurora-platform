<?php

namespace GlamByMariga\Mobile;

use PDO;
use Exception;

/**
 * JWT Token Service
 * Handles JWT token generation, validation, and refresh
 */
class JwtTokenService
{
    private $db;
    private $algorithm;
    private $accessTokenExpiry;
    private $refreshTokenExpiry;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->algorithm = getenv('JWT_ALGORITHM') ?: 'HS256';
        $this->accessTokenExpiry = 15 * 60; // 15 minutes
        $this->refreshTokenExpiry = 30 * 24 * 60 * 60; // 30 days
    }

    /**
     * Generate access and refresh tokens
     */
    public function generateTokens(int $customerId, string $deviceId, string $userAgent = '', string $ipAddress = ''): array
    {
        $now = time();
        $accessExpiresAt = $now + $this->accessTokenExpiry;
        $refreshExpiresAt = $now + $this->refreshTokenExpiry;

        // Generate tokens
        $accessToken = $this->generateToken([
            'type' => 'access',
            'customer_id' => $customerId,
            'device_id' => $deviceId,
            'iat' => $now,
            'exp' => $accessExpiresAt,
        ]);

        $refreshToken = $this->generateToken([
            'type' => 'refresh',
            'customer_id' => $customerId,
            'device_id' => $deviceId,
            'iat' => $now,
            'exp' => $refreshExpiresAt,
        ]);

        // Store in database
        $this->storeTokens($customerId, $deviceId, $accessToken, $refreshToken, $accessExpiresAt, $refreshExpiresAt, $ipAddress, $userAgent);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_expires_in' => $this->accessTokenExpiry,
            'refresh_expires_in' => $this->refreshTokenExpiry,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Validate access token
     */
    public function validateToken(string $token): array
    {
        try {
            $payload = $this->decodeToken($token);

            // Check if token is stored and not revoked
            if (!$this->isTokenValid($payload['access_token'] ?? $token)) {
                return ['valid' => false, 'error' => 'Token has been revoked'];
            }

            // Check expiration
            if ($payload['exp'] < time()) {
                return ['valid' => false, 'error' => 'Token has expired'];
            }

            // Check token type
            if ($payload['type'] !== 'access') {
                return ['valid' => false, 'error' => 'Invalid token type'];
            }

            return [
                'valid' => true,
                'customer_id' => $payload['customer_id'],
                'device_id' => $payload['device_id'],
                'payload' => $payload,
            ];

        } catch (Exception $e) {
            error_log('Token validation error: ' . $e->getMessage());
            return ['valid' => false, 'error' => 'Invalid token'];
        }
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(string $refreshToken, string $deviceId): array
    {
        try {
            $payload = $this->decodeToken($refreshToken);

            // Validate refresh token
            if ($payload['type'] !== 'refresh') {
                throw new Exception('Invalid token type');
            }

            if ($payload['exp'] < time()) {
                throw new Exception('Refresh token has expired');
            }

            // Verify device ID matches
            if ($payload['device_id'] !== $deviceId) {
                throw new Exception('Device ID mismatch');
            }

            $customerId = $payload['customer_id'];

            // Generate new access token
            $now = time();
            $accessExpiresAt = $now + $this->accessTokenExpiry;

            $accessToken = $this->generateToken([
                'type' => 'access',
                'customer_id' => $customerId,
                'device_id' => $deviceId,
                'iat' => $now,
                'exp' => $accessExpiresAt,
            ]);

            // Update token in database
            $stmt = $this->db->prepare(
                "UPDATE api_tokens
                 SET access_token = ?, access_expires_at = ?
                 WHERE customer_id = ? AND device_id = ? AND refresh_token = ? AND is_revoked = FALSE"
            );
            $stmt->execute([$accessToken, date('Y-m-d H:i:s', $accessExpiresAt), $customerId, $deviceId, $refreshToken]);

            return [
                'success' => true,
                'access_token' => $accessToken,
                'access_expires_in' => $this->accessTokenExpiry,
                'token_type' => 'Bearer',
            ];

        } catch (Exception $e) {
            error_log('Token refresh error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Token refresh failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Revoke token
     */
    public function revokeToken(string $token, int $customerId): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE api_tokens
                 SET is_revoked = TRUE, revoked_at = NOW()
                 WHERE (access_token = ? OR refresh_token = ?) AND customer_id = ?"
            );
            $stmt->execute([$token, $token, $customerId]);

            return true;

        } catch (Exception $e) {
            error_log('Token revocation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke all tokens for device
     */
    public function revokeDeviceTokens(int $customerId, string $deviceId): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE api_tokens
                 SET is_revoked = TRUE, revoked_at = NOW()
                 WHERE customer_id = ? AND device_id = ?"
            );
            $stmt->execute([$customerId, $deviceId]);

            return true;

        } catch (Exception $e) {
            error_log('Device token revocation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke all customer tokens
     */
    public function revokeAllTokens(int $customerId): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE api_tokens
                 SET is_revoked = TRUE, revoked_at = NOW()
                 WHERE customer_id = ?"
            );
            $stmt->execute([$customerId]);

            return true;

        } catch (Exception $e) {
            error_log('All tokens revocation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate JWT token
     */
    private function generateToken(array $payload): string
    {
        // Header
        $header = json_encode(['alg' => $this->algorithm, 'typ' => 'JWT']);
        $headerEncoded = $this->base64UrlEncode($header);

        // Payload
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        // Signature
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $this->getSecretKey(), true)
        );

        return "$headerEncoded.$payloadEncoded.$signature";
    }

    /**
     * Decode JWT token
     */
    private function decodeToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Verify signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $this->getSecretKey(), true);
        $signatureExpected = $this->base64UrlEncode($signature);

        if (!hash_equals($signatureExpected, $signatureEncoded)) {
            throw new Exception('Invalid signature');
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        if (!$payload) {
            throw new Exception('Invalid payload');
        }

        return $payload;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Get secret key
     */
    private function getSecretKey(): string
    {
        return getenv('JWT_SECRET') ?: 'dev-secret-key-do-not-use-in-production';
    }

    /**
     * Store tokens in database
     */
    private function storeTokens(int $customerId, string $deviceId, string $accessToken, string $refreshToken, int $accessExpiresAt, int $refreshExpiresAt, string $ipAddress, string $userAgent): void
    {
        try {
            // Revoke existing tokens for this device
            $this->revokeDeviceTokens($customerId, $deviceId);

            // Store new tokens
            $stmt = $this->db->prepare(
                "INSERT INTO api_tokens
                 (customer_id, device_id, access_token, refresh_token, access_expires_at, refresh_expires_at, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $customerId,
                $deviceId,
                $accessToken,
                $refreshToken,
                date('Y-m-d H:i:s', $accessExpiresAt),
                date('Y-m-d H:i:s', $refreshExpiresAt),
                $ipAddress,
                $userAgent,
            ]);

        } catch (Exception $e) {
            error_log('Token storage error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if token is valid (not revoked)
     */
    private function isTokenValid(string $token): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM api_tokens
                 WHERE (access_token = ? OR refresh_token = ?) AND is_revoked = FALSE
                 LIMIT 1"
            );
            $stmt->execute([$token, $token]);

            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log('Token validity check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get active devices for customer
     */
    public function getActiveDevices(int $customerId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT dt.id, dt.device_id, dt.device_name, dt.os_type, dt.os_version,
                        dt.app_version, dt.last_used_at, dt.created_at,
                        CASE WHEN at.is_revoked = FALSE THEN 'active' ELSE 'inactive' END as status
                 FROM device_tokens dt
                 LEFT JOIN api_tokens at ON dt.customer_id = at.customer_id AND dt.device_id = at.device_id
                 WHERE dt.customer_id = ? AND dt.is_active = TRUE
                 ORDER BY dt.last_used_at DESC"
            );
            $stmt->execute([$customerId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get active devices error: ' . $e->getMessage());
            return [];
        }
    }
}
