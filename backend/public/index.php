<?php

declare(strict_types=1);

// ─── getallheaders() polyfill (Nginx/LiteSpeed + PHP-FPM) ────────────
// getallheaders() only exists under Apache. On Hostinger (LiteSpeed) it is
// undefined, which fatals EVERY request in Core\Request. Build it from $_SERVER.
if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = str_replace('_', '-', strtolower(substr($name, 5)));
                $headers[$key] = $value;
            } elseif ($name === 'CONTENT_TYPE') {
                $headers['content-type'] = $value;
            } elseif ($name === 'CONTENT_LENGTH') {
                $headers['content-length'] = $value;
            }
        }
        return $headers;
    }
}

// ─── Load .env ────────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        // Strip surrounding quotes: DB_PASS="Resonanzwebsite28#" must yield
        // Resonanzwebsite28# — otherwise the quotes become part of the
        // password and the DB connection fails.
        if (strlen($value) >= 2) {
            $first = $value[0];
            if (($first === '"' || $first === "'") && $value[-1] === $first) {
                $value = substr($value, 1, -1);
            }
        }
        if (!isset($_ENV[$key])) { $_ENV[$key] = $value; putenv("{$key}={$value}"); }
    }
}

// ─── CORS — must run before everything ───────────────────────
(function (): void {
            $allowed = array_map('trim', explode(',', $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost:5173,http://localhost:5174,https://admin.resonanz.id,https://trms.resonanz.id'));
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
    // Always log server-side (storage/logs is blocked from web access).
    // With APP_DEBUG=false the client only sees a generic message, so this
    // log is the only way to diagnose production 500s via File Manager.
    try {
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $line = sprintf(
            "[%s] %s %s :: %s: %s in %s:%d\n%s\n\n",
            date('c'),
            $_SERVER['REQUEST_METHOD'] ?? '?',
            strtok($_SERVER['REQUEST_URI'] ?? '/', '?'),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        @file_put_contents($logDir . '/app.log', $line, FILE_APPEND | LOCK_EX);
    } catch (Throwable) {
        // Logging must never break the error response itself
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $debug ? $e->getMessage() : 'An unexpected error occurred.',
        'trace'   => $debug ? $e->getTraceAsString() : null,
    ]);
    exit;
});

// ─── Serve uploaded files directly (php built-in server router mode) ──
(function (): void {
    $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    if (is_string($uri) && str_starts_with($uri, '/uploads/')) {
        $file = __DIR__ . $uri;
        $real = realpath($file);
        $base = realpath(__DIR__ . '/uploads');
        if ($real !== false && $base !== false && str_starts_with($real, $base) && is_file($real)) {
            // Fonts are loaded cross-origin (<link>/@font-face from :5173 → :8000),
            // so they MUST carry CORS headers. The bootstrap CORS block above
            // already set them when Origin is allowed, but re-emit here to be
            // safe (e.g. direct <link> loads, cached preflights).
    $allowed = array_map('trim', explode(',', $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost:5173,http://localhost:5174,https://admin.resonanz.id,https://trms.resonanz.id'));
            $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($origin, $allowed, true)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Vary: Origin');
            } else {
                // Public font/image files need no credentials → wildcard is safe
                header('Access-Control-Allow-Origin: *');
            }
            header('Cross-Origin-Resource-Policy: cross-origin');
            // Correct MIME per format (browsers reject wrong font MIME in some modes)
            $fontMime = [
                'ttf' => 'font/ttf', 'otf' => 'font/otf',
                'woff' => 'font/woff', 'woff2' => 'font/woff2',
            ];
            $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
            $mime = $fontMime[$ext] ?? (mime_content_type($real) ?: 'application/octet-stream');
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($real));
            header('Cache-Control: public, max-age=86400');
            readfile($real);
            exit;
        }
    }
})();

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
use Controllers\EventController;
use Controllers\EventRegistrationController;
use Controllers\NewsController;
use Controllers\CourseController;
use Controllers\FacilityController;
use Controllers\TeacherController;
use Controllers\ContactController;
use Controllers\HomeController;
use Controllers\UploadController;

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
$event    = new EventController();
$eventReg = new EventRegistrationController();
$news     = new NewsController();
$course   = new CourseController();
$facility = new FacilityController();
$teacher  = new TeacherController();
$contact  = new ContactController();
$home     = new HomeController();
$upload   = new UploadController();

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
$r->get('/api/events',    [$event,    'publicIndex']);
$r->get('/api/events/:id', [$event,   'publicShow']);
$r->post('/api/events/:id/register', [$eventReg, 'register'], [
    $rl,
    ValidationMiddleware::make([
        'name'  => 'required|min:2|max:100',
        'email' => 'required|email|max:255',
        'phone' => 'required|min:5|max:30',
    ]),
]);
$r->get('/api/events/:id/availability', [$eventReg, 'availability']);
$r->get('/api/news',       [$news,     'publicIndex']);
$r->get('/api/courses',    [$course,   'publicIndex']);
$r->get('/api/facilities', [$facility, 'publicIndex']);
$r->get('/api/teachers',   [$teacher,  'publicIndex']);
$r->get('/api/home',       [$home,     'publicIndex']);
// Public font files with guaranteed CORS headers (see UploadController::showFont).
// Use /api/fonts/<file> instead of /uploads/fonts/<file> for @font-face.
$r->get('/api/fonts/:name', [$upload, 'showFont']);

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

// ── Events ────────────────────────────────────────────────
$r->get('/api/admin/events',     [$event, 'index'],   [$adminMw]);
$r->post('/api/admin/events',    [$event, 'store'],   [$adminMw]);
$r->put('/api/admin/events/:id', [$event, 'update'],  [$adminMw]);
$r->delete('/api/admin/events/:id', [$event, 'destroy'], [$adminMw]);
$r->get('/api/admin/events/:id/registrations', [$eventReg, 'adminIndex'], [$adminMw]);
$r->delete('/api/admin/registrations/:id', [$eventReg, 'adminDestroy'], [$adminMw]);
$r->post('/api/admin/registrations/:id/send-ticket', [$eventReg, 'adminSendTicket'], [$adminMw, $rl]);

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

// ── Home page config ────────────────────────────────────────
$r->get('/api/admin/home',    [$home, 'show'],   [$adminMw]);
$r->put('/api/admin/home',    [$home, 'update'], [$adminMw]);
$r->delete('/api/admin/home', [$home, 'reset'],  [$adminMw]);

// ── Contact messages ────────────────────────────────────────
$r->get('/api/admin/contact/stats', [$contact, 'stats'],   [$adminMw]);
$r->get('/api/admin/contact',       [$contact, 'index'],   [$adminMw]);
$r->get('/api/admin/contact/:id',   [$contact, 'show'],    [$adminMw]);
$r->delete('/api/admin/contact/:id', [$contact, 'destroy'], [$adminMw]);

// ── Uploads (images) ──────────────────────────────────────────
$r->post('/api/admin/uploads', [$upload, 'store'], [$adminMw]);

// ── Health ──────────────────────────────────────────────────
$r->get('/api/health', fn($q,$s) => $s->success(['status' => 'ok', 'timestamp' => date('c')], 'API is running.'));

$r->dispatch();
