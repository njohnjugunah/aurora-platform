<?php

namespace GlamByMariga\Security;

class RateLimiter
{
    private $cacheDir;
    private $attempts = 5;
    private $window = 60; // seconds

    public function __construct(string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/aurora_ratelimit';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        $this->attempts = (int)(getenv('RATE_LIMIT_ATTEMPTS') ?: 5);
        $this->window = (int)(getenv('RATE_LIMIT_WINDOW') ?: 60);
    }

    /**
     * Check if request should be allowed
     */
    public function allow(string $identifier, int $attempts = null, int $window = null): bool
    {
        $attempts = $attempts ?? $this->attempts;
        $window = $window ?? $this->window;

        $file = $this->getCacheFile($identifier);
        $data = $this->readCache($file);

        $now = time();
        $windowStart = $now - $window;

        // Remove old attempts outside the window
        $data = array_filter($data, fn($timestamp) => $timestamp > $windowStart);

        // Check if limit exceeded
        if (count($data) >= $attempts) {
            return false;
        }

        // Record new attempt
        $data[] = $now;
        $this->writeCache($file, $data);

        return true;
    }

    /**
     * Get remaining attempts
     */
    public function remaining(string $identifier, int $attempts = null, int $window = null): int
    {
        $attempts = $attempts ?? $this->attempts;
        $window = $window ?? $this->window;

        $file = $this->getCacheFile($identifier);
        $data = $this->readCache($file);

        $now = time();
        $windowStart = $now - $window;

        $data = array_filter($data, fn($timestamp) => $timestamp > $windowStart);

        return max(0, $attempts - count($data));
    }

    /**
     * Reset limit for identifier
     */
    public function reset(string $identifier): void
    {
        $file = $this->getCacheFile($identifier);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Get cache file path
     */
    private function getCacheFile(string $identifier): string
    {
        // Sanitize identifier
        $hash = hash('sha256', $identifier);
        return $this->cacheDir . '/' . substr($hash, 0, 2) . '/' . $hash . '.json';
    }

    /**
     * Read cache file
     */
    private function readCache(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    /**
     * Write cache file
     */
    private function writeCache(string $file, array $data): void
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Cleanup old cache files
     */
    public function cleanup(): void
    {
        $cutoff = time() - (24 * 60 * 60); // 24 hours

        $files = glob($this->cacheDir . '/*/*.json');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}
