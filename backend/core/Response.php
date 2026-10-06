<?php

declare(strict_types=1);

namespace Core;

/**
 * Response — fluent JSON response builder.
 */
class Response
{
    private int   $statusCode = 200;
    private array $headers    = ['Content-Type' => 'application/json; charset=utf-8'];
    private mixed $data       = null;

    public function status(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function json(mixed $data): void
    {
        $this->data = $data;
        $this->send();
    }

    public function success(mixed $data = null, string $message = 'Success', int $code = 200): void
    {
        $this->status($code)->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    public function error(string $message, int $code = 400, mixed $errors = null): void
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        $this->status($code)->json($payload);
    }

    private function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
