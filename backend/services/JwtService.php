<?php

declare(strict_types=1);

namespace Services;

use RuntimeException;

/**
 * JwtService — manual HS256 JWT implementation (no external dependencies).
 *
 * Produces compact JWTs: Base64Url(header).Base64Url(payload).Base64Url(signature)
 *
 * The signature is HMAC-SHA256 over the first two segments, keyed with $secret.
 */
class JwtService
{
    private string $secret;
    private int    $expiryMinutes;

    public function __construct()
    {
        $config              = require __DIR__ . '/../config/app.php';
        $this->secret        = $config['jwt_secret'];
        $this->expiryMinutes = $config['jwt_expiry_minutes'];
    }

    /**
     * Issue a signed JWT for the given user payload.
     *
     * @param  array<string, mixed> $user  Must contain at least 'id' and 'email'.
     */
    public function issue(array $user): string
    {
        $now = time();

        $header = $this->b64Encode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $payload = $this->b64Encode(json_encode([
            'iss'   => 'resonanz-api',
            'iat'   => $now,
            'exp'   => $now + ($this->expiryMinutes * 60),
            'sub'   => (string) $user['id'],
            'email' => $user['email'],
            'name'  => $user['name'] ?? '',
            'role'  => $user['role'] ?? 'user',
        ]));

        $signature = $this->b64Encode($this->sign($header . '.' . $payload));

        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * Verify and decode a JWT string.
     *
     * @throws RuntimeException on any verification failure.
     * @return array<string, mixed> The decoded payload.
     */
    public function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token format.');
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expectedSig = $this->b64Encode($this->sign($header . '.' . $payload));
        if (!hash_equals($expectedSig, $signature)) {
            throw new RuntimeException('Token signature is invalid.');
        }

        // Decode payload
        $decoded = json_decode($this->b64Decode($payload), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Token payload could not be decoded.');
        }

        // Check expiry
        if (!isset($decoded['exp']) || time() > $decoded['exp']) {
            throw new RuntimeException('Token has expired.');
        }

        // Check issuer
        if (($decoded['iss'] ?? '') !== 'resonanz-api') {
            throw new RuntimeException('Token issuer is invalid.');
        }

        return $decoded;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret, true);
    }

    private function b64Encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64Decode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
