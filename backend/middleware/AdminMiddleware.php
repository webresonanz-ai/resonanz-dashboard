<?php

declare(strict_types=1);

namespace Middleware;

use Core\Request;
use Core\Response;
use Services\JwtService;
use RuntimeException;

/**
 * AdminMiddleware — extends JWT auth with an admin-role check.
 *
 * Must be placed AFTER AuthMiddleware in the route stack, or can replace
 * it (it performs both JWT verification and role check internally).
 *
 * Usage: new AdminMiddleware()  — on any route restricted to admins.
 */
class AdminMiddleware
{
    private JwtService $jwt;

    public function __construct()
    {
        $this->jwt = new JwtService();
    }

    public function __invoke(Request $request, Response $response, callable $next): void
    {
        $token = $request->bearerToken();

        if ($token === null) {
            $response->error('Authentication required.', 401);
            return;
        }

        try {
            $payload = $this->jwt->verify($token);
        } catch (RuntimeException $e) {
            $response->error($e->getMessage(), 401);
            return;
        }

        if (($payload['role'] ?? '') !== 'admin') {
            $response->error('Forbidden. Admin access required.', 403);
            return;
        }

        $_REQUEST['auth_user'] = $payload;

        $next();
    }
}
