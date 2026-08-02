<?php

namespace GlamByMariga\Mobile;

use Exception;

/**
 * Mobile API Middleware
 * Handles authentication, rate limiting, and request/response formatting
 */
class MobileApiMiddleware
{
    private $tokenService;
    private $customerId;
    private $deviceId;

    public function __construct(JwtTokenService $tokenService = null)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Initialize mobile API request
     */
    public static function init(): void
    {
        // Set JSON headers
        header('Content-Type: application/json; charset=UTF-8');
        header('X-API-Version: 1.0');

        // CORS headers (if needed)
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Device-ID, X-Request-ID');

        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }

    /**
     * Authenticate mobile API request
     */
    public function authenticate(): bool
    {
        // Get authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            $this->sendError('Missing or invalid authorization header', 401);
            return false;
        }

        $token = $matches[1];

        // Validate token
        if (!$this->tokenService) {
            $this->sendError('Token service not initialized', 500);
            return false;
        }

        $validation = $this->tokenService->validateToken($token);

        if (!$validation['valid']) {
            $this->sendError($validation['error'], 401);
            return false;
        }

        $this->customerId = $validation['customer_id'];
        $this->deviceId = $validation['device_id'];

        return true;
    }

    /**
     * Get authenticated customer ID
     */
    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    /**
     * Get authenticated device ID
     */
    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    /**
     * Send success response
     */
    public static function success(array $data = [], string $message = 'Success', int $code = 200): void
    {
        self::sendJson([
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ], $code);
    }

    /**
     * Send paginated response
     */
    public static function paginated(array $items, int $page, int $perPage, int $total, string $message = 'Success'): void
    {
        $pages = ceil($total / $perPage);

        self::sendJson([
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $pages,
                'has_more' => $page < $pages,
            ],
            'timestamp' => date('c'),
        ], 200);
    }

    /**
     * Send error response
     */
    public static function sendError(string $message, int $code = 400, array $errors = []): void
    {
        self::sendJson([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c'),
        ], $code);
    }

    /**
     * Send validation error
     */
    public static function validationError(array $errors): void
    {
        self::sendJson([
            'success' => false,
            'code' => 422,
            'message' => 'Validation failed',
            'errors' => $errors,
            'timestamp' => date('c'),
        ], 422);
    }

    /**
     * Send JSON response
     */
    private static function sendJson(array $data, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Get request data (handles JSON and form)
     */
    public static function getRequestData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?? [];
        } else {
            $data = $_POST;
        }

        return $data;
    }

    /**
     * Get query parameters
     */
    public static function getQuery(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get request header
     */
    public static function getHeader(string $key, $default = null)
    {
        $httpKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$httpKey] ?? $default;
    }

    /**
     * Log API access
     */
    public static function logAccess(\PDO $db, int $customerId, string $deviceId, string $endpoint, string $method, int $statusCode, int $responseTime = 0, int $responseSize = 0): void
    {
        try {
            $stmt = $db->prepare(
                "INSERT INTO api_access_logs
                 (customer_id, device_id, endpoint, method, status_code, response_time_ms, response_size, user_agent, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $customerId,
                $deviceId,
                $endpoint,
                $method,
                $statusCode,
                $responseTime,
                $responseSize,
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'],
            ]);

        } catch (Exception $e) {
            error_log('API access logging error: ' . $e->getMessage());
        }
    }

    /**
     * Check rate limit
     */
    public static function checkRateLimit(\PDO $db, int $customerId, int $limit = 100, int $window = 60): bool
    {
        try {
            $stmt = $db->prepare(
                "SELECT COUNT(*) as count FROM api_access_logs
                 WHERE customer_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
            );
            $stmt->execute([$customerId, $window]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result['count'] < $limit;

        } catch (Exception $e) {
            error_log('Rate limit check error: ' . $e->getMessage());
            return true; // Allow on error
        }
    }

    /**
     * Validate request
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $ruleError = self::validateRule($field, $value, $rule);
                if ($ruleError) {
                    $errors[$field][] = $ruleError;
                }
            }
        }

        return $errors;
    }

    /**
     * Validate single rule
     */
    private static function validateRule(string $field, $value, string $rule): ?string
    {
        if (strpos($rule, ':') !== false) {
            list($ruleName, $ruleParam) = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $ruleParam = null;
        }

        switch ($ruleName) {
            case 'required':
                if (empty($value)) {
                    return "$field is required";
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "$field must be a valid email";
                }
                break;

            case 'phone':
                if ($value && !preg_match('/^(\+254|254|0)[17]\d{8}$/', preg_replace('/[^0-9+]/', '', $value))) {
                    return "$field must be a valid phone number";
                }
                break;

            case 'min':
                if ($value && strlen($value) < (int)$ruleParam) {
                    return "$field must be at least $ruleParam characters";
                }
                break;

            case 'max':
                if ($value && strlen($value) > (int)$ruleParam) {
                    return "$field must be at most $ruleParam characters";
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    return "$field must be numeric";
                }
                break;

            case 'integer':
                if ($value && !is_int($value) && !ctype_digit((string)$value)) {
                    return "$field must be an integer";
                }
                break;

            case 'in':
                $allowed = explode(',', $ruleParam);
                if ($value && !in_array($value, $allowed)) {
                    return "$field must be one of: " . implode(', ', $allowed);
                }
                break;

            case 'date':
                if ($value && !strtotime($value)) {
                    return "$field must be a valid date";
                }
                break;
        }

        return null;
    }
}
