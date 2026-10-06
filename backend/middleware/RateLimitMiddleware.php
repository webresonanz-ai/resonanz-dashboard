<?php

declare(strict_types=1);

namespace Middleware;

use Core\Request;
use Core\Response;

/**
 * RateLimitMiddleware — file-based sliding-window rate limiter.
 *
 * Counts requests per IP within a configured time window.
 * Falls back gracefully if the storage directory is not writable.
 *
 * Stored as JSON files in /tmp/resonanz_ratelimit/<hash>.json
 * Each file contains: { "hits": [timestamp, ...] }
 */
class RateLimitMiddleware
{
    private int    $maxAttempts;
    private int    $windowSeconds;
    private string $storageDir;

    public function __construct()
    {
        $config              = require __DIR__ . '/../config/app.php';
        $this->maxAttempts   = $config['rate_limit_attempts'];
        $this->windowSeconds = $config['rate_limit_window'];
        $this->storageDir    = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resonanz_ratelimit';

        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0700, true);
        }
    }

    public function __invoke(Request $request, Response $response, callable $next): void
    {
        $ip  = $request->ip();
        $key = hash('sha256', $ip . '_' . $request->getPath());
        $file = $this->storageDir . DIRECTORY_SEPARATOR . $key . '.json';

        $now  = time();
        $data = $this->readFile($file);

        // Remove hits outside the current window
        $data['hits'] = array_values(array_filter(
            $data['hits'],
            fn(int $ts) => ($now - $ts) < $this->windowSeconds
        ));

        if (count($data['hits']) >= $this->maxAttempts) {
            $oldest  = min($data['hits']);
            $retryIn = $this->windowSeconds - ($now - $oldest);

            header('Retry-After: ' . max(0, $retryIn));
            $response->error(
                'Too many requests. Please try again in ' . max(0, $retryIn) . ' seconds.',
                429
            );
            return;
        }

        $data['hits'][] = $now;
        $this->writeFile($file, $data);

        // Expose rate-limit headers
        $remaining = $this->maxAttempts - count($data['hits']);
        header('X-RateLimit-Limit: '     . $this->maxAttempts);
        header('X-RateLimit-Remaining: ' . max(0, $remaining));
        header('X-RateLimit-Reset: '     . ($now + $this->windowSeconds));

        $next();
    }

    private function readFile(string $file): array
    {
        if (!file_exists($file)) {
            return ['hits' => []];
        }
        $content = @file_get_contents($file);
        if ($content === false) {
            return ['hits' => []];
        }
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : ['hits' => []];
    }

    private function writeFile(string $file, array $data): void
    {
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }
}
