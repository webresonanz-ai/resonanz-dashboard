<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;
use Core\Database;

class ContactController
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** POST /api/contact  — public submission */
    public function submit(Request $req, Response $res): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contact_messages (name, email, subject, message)
             VALUES (:name, :email, :subject, :message)'
        );
        $stmt->execute([
            'name'    => $req->input('name'),
            'email'   => $req->input('email'),
            'subject' => $req->input('subject'),
            'message' => $req->input('message'),
        ]);
        $res->success(null, 'Your message has been sent. We will be in touch shortly.', 201);
    }

    /** GET /api/admin/contact  — admin: list all messages */
    public function index(Request $req, Response $res): void
    {
        $stmt = $this->db->query('SELECT * FROM contact_messages ORDER BY created_at DESC');
        $res->success($stmt->fetchAll(), 'OK');
    }

    /** GET /api/admin/contact/:id  — admin: get single message */
    public function show(Request $req, Response $res, array $params): void
    {
        $stmt = $this->db->prepare('SELECT * FROM contact_messages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $params['id']]);
        $row = $stmt->fetch();
        if (!$row) { $res->error('Message not found.', 404); return; }

        // Auto-mark as read when viewed
        if (!$row['is_read']) {
            $this->db->prepare('UPDATE contact_messages SET is_read=1, read_at=NOW() WHERE id=:id')
                     ->execute(['id' => $row['id']]);
            $row['is_read'] = 1;
        }
        $res->success($row, 'OK');
    }

    /** DELETE /api/admin/contact/:id */
    public function destroy(Request $req, Response $res, array $params): void
    {
        $this->db->prepare('DELETE FROM contact_messages WHERE id = :id')
                 ->execute(['id' => (int) $params['id']]);
        $res->success(null, 'Message deleted.');
    }

    /** GET /api/admin/contact/stats  — unread count */
    public function stats(Request $req, Response $res): void
    {
        $total  = $this->db->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
        $unread = $this->db->query('SELECT COUNT(*) FROM contact_messages WHERE is_read=0')->fetchColumn();
        $res->success(['total' => (int)$total, 'unread' => (int)$unread], 'OK');
    }
}
