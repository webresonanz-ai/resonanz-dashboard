<?php

declare(strict_types=1);

namespace Middleware;

use Core\Request;
use Core\Response;
use Services\JwtService;
use RuntimeException;

/**
 * AuthMiddleware — validates the Bearer JWT on protected routes.
 *
 * On success, the decoded payload is stored in $_REQUEST['auth_user']
 * so controllers can read the authenticated user's data.
 */
class AuthMiddleware
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
            $response->error('Authentication required. No token provided.', 401);
            return;
        }

        try {
            $payload = $this->jwt->verify($token);
        } catch (RuntimeException $e) {
            $response->error($e->getMessage(), 401);
            return;
        }

        // Store the decoded payload for downstream handlers/controllers
        $_REQUEST['auth_user'] = $payload;

        $next();
    }
}
