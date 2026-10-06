<?php

declare(strict_types=1);

namespace Middleware;

use Core\Request;
use Core\Response;

/**
 * CorsMiddleware — ensures CORS headers are present on all non-OPTIONS
 * responses that pass through the router middleware stack.
 *
 * The actual preflight (OPTIONS) is handled at the top of index.php
 * before the router runs, so it always gets headers regardless of
 * whether a matching route exists.
 */
class CorsMiddleware
{
    private array $allowedOrigins;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/app.php';
        $this->allowedOrigins = $config['allowed_origins'];
    }

    public function __invoke(Request $request, Response $response, callable $next): void
    {
        // Headers were already sent for this request origin by the bootstrap
        // block in index.php. Only re-emit if they haven't been sent yet
        // (e.g. when running under a different SAPI or during unit tests).
        if (!headers_sent()) {
            $origin = $request->header('origin', '');

            if (in_array($origin, $this->allowedOrigins, true)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Vary: Origin');
            }

            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Allow-Credentials: true');
        }

        $next();
    }
}
