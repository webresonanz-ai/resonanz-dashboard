<?php

declare(strict_types=1);

namespace Core;

/**
 * Router — lightweight regex-based router supporting middleware stacks.
 *
 * Routes are matched in the order they are registered.
 * Middleware is an ordered list of callables that run before the final handler.
 *
 * Each route callback signature:
 *   function(Request $req, Response $res, array $params): void
 *
 * Each middleware signature:
 *   function(Request $req, Response $res, callable $next): void
 */
class Router
{
    private array $routes     = [];
    private array $middleware = [];  // Global middleware
    private Request  $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request  = $request;
        $this->response = $response;
    }

    /** Register global middleware (runs on every route). */
    public function use(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, callable $handler, array $middleware): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middleware');
    }

    /** Convert a route path like /users/:id into a named regex. */
    private function toRegex(string $path): string
    {
        $pattern = preg_replace('/\//', '\\/', $path);
        $pattern = preg_replace('/:([a-zA-Z0-9_]+)/', '(?P<$1>[^\/]+)', $pattern);
        return '/^' . $pattern . '$/';
    }

    /** Dispatch the current request through matching route + middleware. */
    public function dispatch(): void
    {
        $method = $this->request->getMethod();
        $path   = $this->request->getPath();

        // Handle OPTIONS preflight (CORS middleware will set the headers)
        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $regex = $this->toRegex($route['path']);
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }

            // Extract named captures as route params
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            // Build full middleware stack: global + route-specific
            $stack = array_merge($this->middleware, $route['middleware']);

            $this->runStack(
                $stack,
                $route['handler'],
                $params
            );
            return;
        }

        $this->response->error('Endpoint not found.', 404);
    }

    /**
     * Run the middleware stack then the final handler.
     * Each middleware receives ($req, $res, $next); calling $next() advances the chain.
     */
    private function runStack(array $stack, callable $handler, array $params): void
    {
        $req = $this->request;
        $res = $this->response;

        $runner = function () use (&$runner, &$stack, $handler, $req, $res, $params): void {
            if (empty($stack)) {
                $handler($req, $res, $params);
                return;
            }
            $mw = array_shift($stack);
            $mw($req, $res, $runner);
        };

        $runner();
    }
}
