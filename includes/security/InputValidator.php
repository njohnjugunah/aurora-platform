<?php

namespace GlamByMariga\Security;

class InputValidator
{
    /**
     * Validate email
     */
    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number (Kenya format)
     */
    public static function phone(string $phone): bool
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return preg_match('/^(\+254|254|0)[17]\d{8}$/', $phone) === 1;
    }

    /**
     * Validate URL
     */
    public static function url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate integer
     */
    public static function integer($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): bool
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return false;
        }

        $int = (int)$value;
        return $int >= $min && $int <= $max;
    }

    /**
     * Validate float
     */
    public static function float($value, float $min = PHP_FLOAT_MIN, float $max = PHP_FLOAT_MAX): bool
    {
        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            return false;
        }

        $float = (float)$value;
        return $float >= $min && $float <= $max;
    }

    /**
     * Validate string length
     */
    public static function string(string $value, int $minLen = 1, int $maxLen = 255): bool
    {
        $len = strlen($value);
        return $len >= $minLen && $len <= $maxLen;
    }

    /**
     * Validate date format
     */
    public static function date(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate JSON
     */
    public static function json(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Sanitize HTML (remove tags)
     */
    public static function sanitizeHtml(string $html): string
    {
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize for database (additional layer, use prepared statements!)
     */
    public static function sanitizeDb(string $value): string
    {
        return addslashes($value);
    }

    /**
     * Validate array keys exist
     */
    public static function requiredKeys(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!isset($data[$key]) || (is_string($data[$key]) && trim($data[$key]) === '')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sanitize array recursively
     */
    public static function sanitizeArray(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $key = is_string($key) ? htmlspecialchars($key, ENT_QUOTES, 'UTF-8') : $key;

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Extract safe fields from data
     */
    public static function extractFields(array $data, array $allowedFields): array
    {
        $result = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $result[$field] = $data[$field];
            }
        }

        return $result;
    }
}
