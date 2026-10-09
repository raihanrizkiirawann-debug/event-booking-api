<?php

namespace App\Services;

use CodeIgniter\Cache\CacheInterface;

class LoginRateLimiter
{
    private CacheInterface $cache;

    private int $maxAttempts = 5;

    private int $decaySeconds = 900; // 15 menit

    public function __construct()
    {
        $this->cache = service('cache');
    }

    public function isBlocked(string $key): bool
    {
        $attempts = $this->cache->get($key);

        return $attempts !== null && (int) $attempts >= $this->maxAttempts;
    }

    public function hit(string $key): int
    {
        $attempts = $this->cache->get($key);

        if ($attempts === null) {
            $attempts = 0;
        }

        $attempts++;

        $this->cache->save(
            $key,
            $attempts,
            $this->decaySeconds
        );

        return $attempts;
    }

    public function clear(string $key): void
    {
        $this->cache->delete($key);
    }

    public function remainingAttempts(string $key): int
    {
        $attempts = $this->cache->get($key);

        if ($attempts === null) {
            return $this->maxAttempts;
        }

        return max(
            0,
            $this->maxAttempts - (int) $attempts
        );
    }
}