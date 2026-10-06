<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;
use Services\AuthService;
use RuntimeException;

/**
 * AuthController — handles /api/auth/* endpoints.
 */
class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * POST /api/auth/register
     *
     * Body: { name, email, password, password_confirmation }
     */
    public function register(Request $request, Response $response): void
    {
        try {
            $result = $this->authService->register([
                'name'     => $request->input('name'),
                'email'    => $request->input('email'),
                'password' => $request->input('password'),
            ]);

            $response->success(
                $result,
                'Registration successful. Welcome to Resonanz!',
                201
            );
        } catch (RuntimeException $e) {
            $response->error($e->getMessage(), 409);
        }
    }

    /**
     * POST /api/auth/login
     *
     * Body: { email, password }
     */
    public function login(Request $request, Response $response): void
    {
        try {
            $result = $this->authService->login([
                'email'    => $request->input('email'),
                'password' => $request->input('password'),
            ]);

            $response->success($result, 'Login successful.');
        } catch (RuntimeException $e) {
            $response->error($e->getMessage(), 401);
        }
    }

    /**
     * GET /api/auth/me
     *
     * Requires: AuthMiddleware
     * Returns the currently authenticated user from the JWT payload.
     */
    public function me(Request $request, Response $response): void
    {
        $authUser = $_REQUEST['auth_user'] ?? null;

        if (!$authUser) {
            $response->error('Not authenticated.', 401);
            return;
        }

        $response->success([
            'id'    => $authUser['sub'],
            'email' => $authUser['email'],
            'name'  => $authUser['name'],
            'role'  => $authUser['role'],
        ], 'Authenticated.');
    }

    /**
     * POST /api/auth/logout
     *
     * Client-side JWT logout — the token is short-lived; for full invalidation,
     * a token blocklist would be needed. Here we simply acknowledge the request.
     */
    public function logout(Request $request, Response $response): void
    {
        $response->success(null, 'Logged out successfully.');
    }
}
