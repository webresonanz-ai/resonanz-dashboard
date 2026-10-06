<?php

declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════
 *  Resonanz Music Foundation — API Entry Point
 *
 *  All HTTP requests are routed here via the .htaccess rewrite.
 *
 *  Namespace autoloading is done via a simple PSR-4-style loader.
 *  The structure mirrors:
 *
 *    backend/
 *    ├── config/
 *    ├── controllers/  → namespace Controllers\
 *    ├── core/         → namespace Core\
 *    ├── middleware/   → namespace Middleware\
 *    ├── services/     → namespace Services\
 *    └── public/       ← you are here
 * ═══════════════════════════════════════════════════════════════
 */

// ─── Load .env ────────────────────────────────────────────────
// __DIR__ is backend/public — one level up reaches backend/
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        // Don't overwrite variables already set in the real environment
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// ─── CORS — must run before EVERYTHING, including error handlers ──────────
// Preflight OPTIONS requests never reach the router; they must get CORS
// headers here at the entry-point level, otherwise the browser blocks them.
(function (): void {
    $allowedOrigins = explode(',', $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost:5173');
    $origin         = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');

    // Respond immediately to preflight — no further processing needed
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
})();

// ─── Error handling ───────────────────────────────────────────
$debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

set_exception_handler(function (Throwable $e) use ($debug): void {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $debug ? $e->getMessage() : 'An unexpected error occurred.',
        'trace'   => $debug ? $e->getTraceAsString() : null,
    ]);
    exit;
});

// ─── PSR-4 style autoloader ───────────────────────────────────
spl_autoload_register(function (string $class): void {
    $baseDir   = __DIR__ . '/../';
    $namespaces = [
        'Core\\'        => 'core/',
        'Controllers\\' => 'controllers/',
        'Middleware\\'  => 'middleware/',
        'Services\\'    => 'services/',
    ];

    foreach ($namespaces as $prefix => $relDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file     = $baseDir . $relDir . $relative . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ─── Bootstrap ────────────────────────────────────────────────
use Core\Request;
use Core\Response;
use Core\Router;
use Middleware\CorsMiddleware;
use Middleware\RateLimitMiddleware;
use Middleware\AuthMiddleware;
use Middleware\ValidationMiddleware;
use Controllers\AuthController;

$request  = new Request();
$response = new Response();
$router   = new Router($request, $response);

// ─── Global middleware ────────────────────────────────────────
$router->use(new CorsMiddleware());

// ─── Auth controller instance ─────────────────────────────────
$auth = new AuthController();

// ─── Routes ───────────────────────────────────────────────────

/**
 * POST /api/auth/register
 * Rate-limited + validated
 */
$router->post('/api/auth/register', [$auth, 'register'], [
    new RateLimitMiddleware(),
    ValidationMiddleware::make([
        'name'                  => 'required|min:2|max:100',
        'email'                 => 'required|email|max:255',
        'password'              => 'required|min:8|max:128',
        'password_confirmation' => 'required',
        // "confirmed" rule checks password === password_confirmation
    ]),
    // Re-run just the confirmed rule via a light inline middleware
    function (Request $req, Response $res, callable $next): void {
        $password = $req->input('password');
        $confirm  = $req->input('password_confirmation');
        if ($password !== $confirm) {
            $res->error('Validation failed.', 422, [
                'password' => ['The password confirmation does not match.'],
            ]);
            return;
        }
        $next();
    },
]);

/**
 * POST /api/auth/login
 * Rate-limited + validated
 */
$router->post('/api/auth/login', [$auth, 'login'], [
    new RateLimitMiddleware(),
    ValidationMiddleware::make([
        'email'    => 'required|email|max:255',
        'password' => 'required|min:1|max:128',
    ]),
]);

/**
 * GET /api/auth/me   — protected
 */
$router->get('/api/auth/me', [$auth, 'me'], [
    new AuthMiddleware(),
]);

/**
 * POST /api/auth/logout  — protected
 */
$router->post('/api/auth/logout', [$auth, 'logout'], [
    new AuthMiddleware(),
]);

/**
 * GET /api/health  — public ping endpoint
 */
$router->get('/api/health', function (Request $req, Response $res): void {
    $res->success(['status' => 'ok', 'timestamp' => date('c')], 'API is running.');
});

// ─── Dispatch ─────────────────────────────────────────────────
$router->dispatch();
