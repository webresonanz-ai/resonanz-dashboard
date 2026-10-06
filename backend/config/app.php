<?php

declare(strict_types=1);

return [
    // ─── JWT ───────────────────────────────────────────────────────
    'jwt_secret'         => $_ENV['JWT_SECRET'] ?? 'change-this-to-a-long-random-secret-in-production',
    'jwt_expiry_minutes' => (int) ($_ENV['JWT_EXPIRY_MINUTES'] ?? 60),

    // ─── CORS ──────────────────────────────────────────────────────
    'allowed_origins' => explode(',', $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost:5173'),

    // ─── Rate limiting ─────────────────────────────────────────────
    // Max attempts per window per IP on auth endpoints
    'rate_limit_attempts' => (int) ($_ENV['RATE_LIMIT_ATTEMPTS'] ?? 10),
    'rate_limit_window'   => (int) ($_ENV['RATE_LIMIT_WINDOW']   ?? 60),   // seconds

    // ─── Environment ───────────────────────────────────────────────
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
];
