<?php

namespace GlamByMariga\Security;

class CsrfToken
{
    private const TOKEN_NAME = '_token';
    private const TOKEN_LENGTH = 32;

    /**
     * Generate CSRF token
     */
    public static function generate(): string
    {
        if (isset($_SESSION[self::TOKEN_NAME]) && self::isValid($_SESSION[self::TOKEN_NAME])) {
            return $_SESSION[self::TOKEN_NAME];
        }

        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::TOKEN_NAME] = $token;

        return $token;
    }

    /**
     * Get current token or generate new one
     */
    public static function get(): string
    {
        return $_SESSION[self::TOKEN_NAME] ?? self::generate();
    }

    /**
     * Verify CSRF token from request
     */
    public static function verify($token = null): bool
    {
        if ($token === null) {
            $token = $_POST[self::TOKEN_NAME]
                ?? $_GET[self::TOKEN_NAME]
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? null;
        }

        if (!$token || !isset($_SESSION[self::TOKEN_NAME])) {
            return false;
        }

        // Use timing-safe comparison
        return hash_equals($_SESSION[self::TOKEN_NAME], $token);
    }

    /**
     * Validate token structure (before comparison)
     */
    private static function isValid(string $token): bool
    {
        return strlen($token) === self::TOKEN_LENGTH * 2 && ctype_xdigit($token);
    }

    /**
     * Clear token (logout)
     */
    public static function clear(): void
    {
        unset($_SESSION[self::TOKEN_NAME]);
    }

    /**
     * Inject token into HTML forms
     */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . htmlspecialchars(self::get(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get token name
     */
    public static function name(): string
    {
        return self::TOKEN_NAME;
    }
}
