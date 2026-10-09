<?php

declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Services\MailService;
use PDO;

/**
 * EventRegistrationController — internal concert registration.
 *
 * Public : POST /api/events/:id/register  (name, email, phone)
 *          GET  /api/events/:id/availability (count / remaining)
 * Admin  : GET  /api/admin/events/:id/registrations
 *          DELETE /api/admin/registrations/:id
 *
 * Registration code format: EventCode_RegistrationID_Timestamp_Random4
 *   e.g. RSVNCFEST_42_1728292800_A3K9
 */
class EventRegistrationController
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── PUBLIC: register for an event ─────────────────────────────
    public function register(Request $req, Response $res, array $params): void
    {
        $eventId = (int) ($params['id'] ?? 0);
        if ($eventId <= 0) {
            $res->error('Invalid event.', 422);
            return;
        }

        $event = $this->findEvent($eventId, true);
        if (!$event) {
            $res->error('Event not found.', 404);
            return;
        }

        // Only Concert events with internal registration (no external URL)
        if (($event['type'] ?? '') !== 'Concert') {
            $res->error('Online registration is only available for Concert events.', 422);
            return;
        }
        // Past events are no longer registrable (public list already hides them).
        $eventDate = substr(trim((string) ($event['event_date'] ?? '')), 0, 10);
        if ($eventDate !== '' && $eventDate < date('Y-m-d')) {
            $res->error('This event has already ended.', 422);
            return;
        }
        if (!empty($event['use_registration_url']) && !empty($event['registration_url'])) {
            $res->error('This event uses an external registration link.', 422);
            return;
        }

        $name  = trim((string) $req->input('name', ''));
        $email = trim((string) $req->input('email', ''));
        $phone = trim((string) $req->input('phone', ''));

        $errors = [];
        if ($name === '' || mb_strlen($name) < 2) {
            $errors['name'][] = 'The name field must be at least 2 characters.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'][] = 'The name field must not exceed 100 characters.';
        }
        if ($email === '') {
            $errors['email'][] = 'The email field is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'The email field must be a valid email address.';
        } elseif (mb_strlen($email) > 255) {
            $errors['email'][] = 'The email field must not exceed 255 characters.';
        }
        if ($phone === '') {
            $errors['phone'][] = 'The phone field is required.';
        } elseif (mb_strlen($phone) < 5 || mb_strlen($phone) > 30) {
            $errors['phone'][] = 'The phone field must be between 5 and 30 characters.';
        }
        if ($errors !== []) {
            $res->error('Validation failed.', 422, $errors);
            return;
        }

        try {
            // Capacity check
            $count = $this->countRegistrations($eventId);
            $capacity = isset($event['max_capacity']) && $event['max_capacity'] !== null
                ? (int) $event['max_capacity'] : null;
            if ($capacity !== null && $capacity > 0 && $count >= $capacity) {
                $res->error('Registration is full. Maximum capacity reached.', 409);
                return;
            }

            // Prevent duplicate email per event
            $dup = $this->db->prepare(
                'SELECT id FROM `event_registrations` WHERE event_id = :eid AND email = :email LIMIT 1'
            );
            $dup->execute(['eid' => $eventId, 'email' => $email]);
            if ($dup->fetch()) {
                $res->error('This email is already registered for the event.', 409);
                return;
            }

            // Insert first to obtain the registration ID
            $ins = $this->db->prepare(
                'INSERT INTO `event_registrations` (event_id, `name`, email, phone) ' .
                'VALUES (:eid, :name, :email, :phone)'
            );
            $ins->execute(['eid' => $eventId, 'name' => $name, 'email' => $email, 'phone' => $phone]);
            $regId = (int) $this->db->lastInsertId();

            // Build code: EventCode_RegistrationID_Timestamp_Random4
            $eventCode = strtoupper(trim((string) ($event['event_code'] ?? '')));
            $eventCode = preg_replace('/[^A-Z0-9]/', '', $eventCode) ?? '';
            if ($eventCode === '') {
                $eventCode = 'EVT' . $eventId;
            }
            $timestamp = time();
            $regCode = "{$eventCode}_{$regId}_{$timestamp}_{$this->randomSuffix(4)}";
            // Retry on (extremely unlikely) code collision
            for ($attempt = 0; $attempt < 5; $attempt++) {
                try {
                    $chk = $this->db->prepare(
                        'SELECT id FROM `event_registrations` WHERE registration_code = :code LIMIT 1'
                    );
                    $chk->execute(['code' => $regCode]);
                    if (!$chk->fetch()) {
                        break;
                    }
                    $regCode = "{$eventCode}_{$regId}_{$timestamp}_{$this->randomSuffix(4)}";
                } catch (\Throwable) {
                    break;
                }
            }

            $upd = $this->db->prepare(
                'UPDATE `event_registrations` SET registration_code = :code WHERE id = :id'
            );
            $upd->execute(['code' => $regCode, 'id' => $regId]);

            $row = $this->findRegistration($regId);
            $remaining = ($capacity !== null && $capacity > 0) ? max(0, $capacity - ($count + 1)) : null;

            $res->success([
                'registration' => $row,
                'event' => [
                    'id' => (int) $event['id'],
                    'title' => $event['title'],
                    'event_code' => $eventCode,
                ],
                'registered_count' => $count + 1,
                'remaining' => $remaining,
            ], 'Registration successful.', 201);
        } catch (\Throwable $e) {
            $res->error('Could not complete registration. Please try again.', 500);
        }
    }

    // ─── PUBLIC: availability ──────────────────────────────────────
    public function availability(Request $req, Response $res, array $params): void
    {
        $eventId = (int) ($params['id'] ?? 0);
        $event = $this->findEvent($eventId, true);
        if (!$event) {
            $res->error('Event not found.', 404);
            return;
        }
        try {
            $count = $this->countRegistrations($eventId);
        } catch (\Throwable) {
            $count = 0;
        }
        $capacity = isset($event['max_capacity']) && $event['max_capacity'] !== null
            ? (int) $event['max_capacity'] : null;
        $res->success([
            'event_id' => $eventId,
            'registered_count' => $count,
            'max_capacity' => $capacity,
            'remaining' => ($capacity !== null && $capacity > 0) ? max(0, $capacity - $count) : null,
            'is_full' => ($capacity !== null && $capacity > 0) ? ($count >= $capacity) : false,
        ], 'OK');
    }

    // ─── ADMIN: list registrations for an event ────────────────────
    public function adminIndex(Request $req, Response $res, array $params): void
    {
        $eventId = (int) ($params['id'] ?? 0);
        $event = $this->findEvent($eventId, false);
        if (!$event) {
            $res->error('Event not found.', 404);
            return;
        }
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM `event_registrations` WHERE event_id = :eid ORDER BY id ASC'
            );
            $stmt->execute(['eid' => $eventId]);
            $rows = $stmt->fetchAll();
        } catch (\Throwable) {
            $rows = [];
        }
        $res->success(['event' => $event, 'registrations' => $rows], 'OK');
    }

    // ─── ADMIN: delete a registration ──────────────────────────────
    public function adminDestroy(Request $req, Response $res, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $row = $this->findRegistration($id);
        if (!$row) {
            $res->error('Registration not found.', 404);
            return;
        }
        $this->db->prepare('DELETE FROM `event_registrations` WHERE id = :id')->execute(['id' => $id]);
        $res->success(null, 'Registration deleted.');
    }

    // ─── ADMIN: email the ticket (QR code) to a registered guest ───
    public function adminSendTicket(Request $req, Response $res, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $reg = $this->findRegistration($id);
        if (!$reg) {
            $res->error('Registration not found.', 404);
            return;
        }
        if (empty($reg['registration_code'])) {
            $res->error('This registration has no ticket code yet.', 422);
            return;
        }
        $event = $this->findEvent((int) $reg['event_id'], false);
        if (!$event) {
            $res->error('Event not found.', 404);
            return;
        }
        if (!MailService::isConfigured()) {
            $res->error('Email is not configured (GOOGLE_APP_EMAIL / GOOGLE_APP_PASSWORD).', 500);
            return;
        }

        $mailer = new MailService();
        $ok = $mailer->sendTicket(
            ['to' => (string) $reg['email'], 'name' => (string) $reg['name']],
            [
                'title' => (string) ($event['title'] ?? 'Event'),
                'event_date' => (string) ($event['event_date'] ?? ''),
                'event_time' => (string) ($event['event_time'] ?? ''),
                'venue' => (string) ($event['venue'] ?? ''),
                'event_code' => (string) ($event['event_code'] ?? ''),
            ],
            (string) $reg['registration_code']
        );

        if (!$ok) {
            $res->error('Could not send ticket email: ' . $mailer->lastError(), 500);
            return;
        }
        $res->success(['sent_to' => $reg['email']], 'Ticket sent to ' . $reg['email'] . '.');
    }

    // ─── HELPERS ───────────────────────────────────────────────────
    private function findEvent(int $id, bool $onlyActive): array|false
    {
        $sql = 'SELECT * FROM `events` WHERE id = :id' . ($onlyActive ? ' AND is_active = 1' : '') . ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: false;
    }

    private function findRegistration(int $id): array|false
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM `event_registrations` WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch() ?: false;
        } catch (\Throwable) {
            return false;
        }
    }

    private function countRegistrations(int $eventId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS c FROM `event_registrations` WHERE event_id = :eid');
        $stmt->execute(['eid' => $eventId]);
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0);
    }

    /** Random uppercase alphanumeric suffix, e.g. "A3K9". */
    private function randomSuffix(int $length = 4): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}
