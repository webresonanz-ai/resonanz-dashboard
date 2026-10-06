<?php

declare(strict_types=1);

// ─── Load .env ────────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (!isset($_ENV[$key])) { $_ENV[$key] = $value; putenv("{$key}={$value}"); }
    }
}

// ─── CORS — must run before everything ───────────────────────
(function (): void {
    $allowed = array_map('trim', explode(',', $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost:5173'));
    $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
})();

// ─── Error handling ───────────────────────────────────────────
$debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
error_reporting($debug ? E_ALL : 0);
ini_set('display_errors', $debug ? '1' : '0');

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

// ─── PSR-4 autoloader ─────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $map = [
        'Core\\'        => 'core/',
        'Controllers\\' => 'controllers/',
        'Middleware\\'  => 'middleware/',
        'Services\\'    => 'services/',
    ];
    foreach ($map as $prefix => $dir) {
        if (!str_starts_with($class, $prefix)) continue;
        $file = __DIR__ . '/../' . $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});

// ─── Bootstrap ────────────────────────────────────────────────
use Core\Request;
use Core\Response;
use Core\Router;
use Middleware\CorsMiddleware;
use Middleware\RateLimitMiddleware;
use Middleware\AuthMiddleware;
use Middleware\AdminMiddleware;
use Middleware\ValidationMiddleware;
use Controllers\AuthController;
use Controllers\ScheduleController;
use Controllers\ConcertController;
use Controllers\NewsController;
use Controllers\CourseController;
use Controllers\FacilityController;
use Controllers\TeacherController;
use Controllers\ContactController;

$req = new Request();
$res = new Response();
$r   = new Router($req, $res);

$r->use(new CorsMiddleware());

// Reusable middleware instances
$authMw  = new AuthMiddleware();
$adminMw = new AdminMiddleware();
$rl      = new RateLimitMiddleware();

// Controllers
$auth     = new AuthController();
$schedule = new ScheduleController();
$concert  = new ConcertController();
$news     = new NewsController();
$course   = new CourseController();
$facility = new FacilityController();
$teacher  = new TeacherController();
$contact  = new ContactController();

// ═══════════════════════════════════════════════════════════════
//  AUTH
// ═══════════════════════════════════════════════════════════════
$r->post('/api/auth/register', [$auth, 'register'], [
    $rl,
    ValidationMiddleware::make([
        'name'                  => 'required|min:2|max:100',
        'email'                 => 'required|email|max:255',
        'password'              => 'required|min:8|max:128',
        'password_confirmation' => 'required',
    ]),
    function (Request $req, Response $res, callable $next): void {
        if ($req->input('password') !== $req->input('password_confirmation')) {
            $res->error('Validation failed.', 422, ['password' => ['The password confirmation does not match.']]);
            return;
        }
        $next();
    },
]);
$r->post('/api/auth/login',  [$auth, 'login'],  [$rl, ValidationMiddleware::make(['email' => 'required|email', 'password' => 'required'])]);
$r->get('/api/auth/me',      [$auth, 'me'],      [$authMw]);
$r->post('/api/auth/logout', [$auth, 'logout'],  [$authMw]);

// ═══════════════════════════════════════════════════════════════
//  PUBLIC DATA  (read-only, no auth)
// ═══════════════════════════════════════════════════════════════
$r->get('/api/schedule',   [$schedule, 'publicIndex']);
$r->get('/api/concerts',   [$concert,  'publicIndex']);
$r->get('/api/news',       [$news,     'publicIndex']);
$r->get('/api/courses',    [$course,   'publicIndex']);
$r->get('/api/facilities', [$facility, 'publicIndex']);
$r->get('/api/teachers',   [$teacher,  'publicIndex']);

$r->post('/api/contact', [$contact, 'submit'], [
    ValidationMiddleware::make([
        'name'    => 'required|min:2|max:100',
        'email'   => 'required|email|max:255',
        'subject' => 'required|min:2|max:255',
        'message' => 'required|min:5',
    ]),
]);

// ═══════════════════════════════════════════════════════════════
//  ADMIN — all routes require AdminMiddleware
// ═══════════════════════════════════════════════════════════════

// ── Schedule ────────────────────────────────────────────────
$r->get('/api/admin/schedule',     [$schedule, 'index'],   [$adminMw]);
$r->post('/api/admin/schedule',    [$schedule, 'store'],   [$adminMw]);
$r->put('/api/admin/schedule/:id', [$schedule, 'update'],  [$adminMw]);
$r->delete('/api/admin/schedule/:id', [$schedule, 'destroy'], [$adminMw]);

// ── Concerts ────────────────────────────────────────────────
$r->get('/api/admin/concerts',     [$concert, 'index'],   [$adminMw]);
$r->post('/api/admin/concerts',    [$concert, 'store'],   [$adminMw]);
$r->put('/api/admin/concerts/:id', [$concert, 'update'],  [$adminMw]);
$r->delete('/api/admin/concerts/:id', [$concert, 'destroy'], [$adminMw]);

// ── News ────────────────────────────────────────────────────
$r->get('/api/admin/news',     [$news, 'index'],   [$adminMw]);
$r->post('/api/admin/news',    [$news, 'store'],   [$adminMw]);
$r->put('/api/admin/news/:id', [$news, 'update'],  [$adminMw]);
$r->delete('/api/admin/news/:id', [$news, 'destroy'], [$adminMw]);

// ── Courses ─────────────────────────────────────────────────
$r->get('/api/admin/courses',     [$course, 'index'],   [$adminMw]);
$r->post('/api/admin/courses',    [$course, 'store'],   [$adminMw]);
$r->put('/api/admin/courses/:id', [$course, 'update'],  [$adminMw]);
$r->delete('/api/admin/courses/:id', [$course, 'destroy'], [$adminMw]);

// ── Facilities ──────────────────────────────────────────────
$r->get('/api/admin/facilities',     [$facility, 'index'],   [$adminMw]);
$r->post('/api/admin/facilities',    [$facility, 'store'],   [$adminMw]);
$r->put('/api/admin/facilities/:id', [$facility, 'update'],  [$adminMw]);
$r->delete('/api/admin/facilities/:id', [$facility, 'destroy'], [$adminMw]);

// ── Teachers ────────────────────────────────────────────────
$r->get('/api/admin/teachers',     [$teacher, 'index'],   [$adminMw]);
$r->post('/api/admin/teachers',    [$teacher, 'store'],   [$adminMw]);
$r->put('/api/admin/teachers/:id', [$teacher, 'update'],  [$adminMw]);
$r->delete('/api/admin/teachers/:id', [$teacher, 'destroy'], [$adminMw]);

// ── Contact messages ────────────────────────────────────────
$r->get('/api/admin/contact/stats', [$contact, 'stats'],   [$adminMw]);
$r->get('/api/admin/contact',       [$contact, 'index'],   [$adminMw]);
$r->get('/api/admin/contact/:id',   [$contact, 'show'],    [$adminMw]);
$r->delete('/api/admin/contact/:id', [$contact, 'destroy'], [$adminMw]);

// ── Health ──────────────────────────────────────────────────
$r->get('/api/health', fn($q,$s) => $s->success(['status' => 'ok', 'timestamp' => date('c')], 'API is running.'));

$r->dispatch();
