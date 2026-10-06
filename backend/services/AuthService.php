<?php

declare(strict_types=1);

namespace Services;

use Core\Database;
use PDO;
use RuntimeException;

/**
 * AuthService — handles user registration and login business logic.
 *
 * All password hashing uses PHP's built-in password_hash() with bcrypt.
 * Sensitive user fields (password) are never returned to callers.
 */
class AuthService
{
    private PDO        $db;
    private JwtService $jwt;

    public function __construct()
    {
        $this->db  = Database::getInstance()->getConnection();
        $this->jwt = new JwtService();
    }

    // ─── Register ─────────────────────────────────────────────────────────

    /**
     * Register a new user.
     *
     * @param  array{ name: string, email: string, password: string } $data
     * @return array{ user: array, token: string }
     * @throws RuntimeException  if the email is already taken.
     */
    public function register(array $data): array
    {
        if ($this->findByEmail($data['email'])) {
            throw new RuntimeException('An account with this email already exists.');
        }

        $hashed = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, role, created_at)
             VALUES (:name, :email, :password, :role, NOW())'
        );

        $stmt->execute([
            'name'     => $data['name'],
            'email'    => strtolower($data['email']),
            'password' => $hashed,
            'role'     => 'user',
        ]);

        $userId = (int) $this->db->lastInsertId();

        $user = $this->findById($userId);

        return [
            'user'  => $this->sanitize($user),
            'token' => $this->jwt->issue($user),
        ];
    }

    // ─── Login ────────────────────────────────────────────────────────────

    /**
     * Authenticate a user with email + password.
     *
     * Uses a constant-time comparison to avoid timing attacks.
     *
     * @param  array{ email: string, password: string } $data
     * @return array{ user: array, token: string }
     * @throws RuntimeException  on invalid credentials.
     */
    public function login(array $data): array
    {
        $user = $this->findByEmail($data['email']);

        // Always call password_verify even if user not found (timing attack mitigation)
        $hash          = $user['password'] ?? '$2y$12$invalidhashtopreventtimingattack';
        $passwordValid = password_verify($data['password'], $hash);

        if (!$user || !$passwordValid) {
            throw new RuntimeException('Invalid email or password.');
        }

        // Rehash if needed (e.g. cost factor was updated)
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $this->updatePassword($user['id'], $data['password']);
        }

        return [
            'user'  => $this->sanitize($user),
            'token' => $this->jwt->issue($user),
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower($email)]);
        return $stmt->fetch();
    }

    private function findById(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new RuntimeException('User not found.');
        }
        return $user;
    }

    private function updatePassword(int $id, string $plaintext): void
    {
        $hashed = password_hash($plaintext, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $this->db->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute(['password' => $hashed, 'id' => $id]);
    }

    /** Strip sensitive fields before sending user data to the client. */
    private function sanitize(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
