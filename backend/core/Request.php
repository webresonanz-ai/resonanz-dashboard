<?php

declare(strict_types=1);

namespace Core;

/**
 * Request — thin wrapper around the current HTTP request.
 */
class Request
{
    private array $body   = [];
    private array $query  = [];
    private array $headers = [];
    private array $files   = [];

    public function __construct()
    {
        // Parse JSON body
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->body = $decoded;
            }
        }

        // Support multipart/form-data (file uploads + regular fields)
        if (!empty($_POST)) {
            $this->body = array_merge($this->body, $_POST);
        }
        $this->files = $_FILES ?? [];

        $this->query = $_GET ?? [];

        // Normalise header names to lowercase with hyphens
        foreach (getallheaders() as $name => $value) {
            $this->headers[strtolower($name)] = $value;
        }
    }

    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function getPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return strtok($uri, '?');
    }

    /** Return a body field, optionally trimmed. */
    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization', '');
        if (is_string($auth) && str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    public function ip(): string
    {
        // Respect common proxy headers
        foreach (['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return explode(',', $_SERVER[$key])[0];
            }
        }
        return '0.0.0.0';
    }

    public function all(): array
    {
        return $this->body;
    }

    /** Return a single uploaded file entry (or null). */
    public function file(string $key): ?array
    {
        $f = $this->files[$key] ?? null;
        return is_array($f) ? $f : null;
    }

    /** Return all uploaded files. */
    public function files(): array
    {
        return $this->files;
    }
}
