<?php

declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use PDO;

/**
 * ResourceController — abstract CRUD base.
 *
 * Concrete subclasses must define:
 *   - $table        : database table name
 *   - $fillable     : columns allowed on create/update
 *   - $rules        : validation rules (same format as ValidationMiddleware)
 *   - orderClause() : ORDER BY clause string
 */
abstract class ResourceController
{
    protected PDO    $db;
    protected string $table;
    protected array  $fillable = [];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── LIST ──────────────────────────────────────────────────────────────
    public function index(Request $req, Response $res): void
    {
        $stmt = $this->db->query(
            "SELECT * FROM `{$this->table}` ORDER BY " . $this->orderClause()
        );
        $res->success($stmt->fetchAll(), 'OK');
    }

    // ─── SHOW ──────────────────────────────────────────────────────────────
    public function show(Request $req, Response $res, array $params): void
    {
        $row = $this->findOrFail((int) $params['id'], $res);
        if ($row) {
            $res->success($this->decode($row), 'OK');
        }
    }

    // ─── CREATE ────────────────────────────────────────────────────────────
    public function store(Request $req, Response $res): void
    {
        $data = $this->pick($req->all());
        $data = $this->beforeSave($data, true);

        [$cols, $placeholders, $bindings] = $this->buildInsert($data);
        $this->db->prepare(
            "INSERT INTO `{$this->table}` ({$cols}) VALUES ({$placeholders})"
        )->execute($bindings);

        $id  = (int) $this->db->lastInsertId();
        $row = $this->findOrFail($id, $res);
        if ($row) {
            $res->success($this->decode($row), 'Created successfully.', 201);
        }
    }

    // ─── UPDATE ────────────────────────────────────────────────────────────
    public function update(Request $req, Response $res, array $params): void
    {
        $id = (int) $params['id'];
        if (!$this->findOrFail($id, $res)) {
            return;
        }

        $data = $this->pick($req->all());
        $data = $this->beforeSave($data, false);

        if (empty($data)) {
            $res->error('No valid fields provided for update.', 422);
            return;
        }

        [$setClauses, $bindings] = $this->buildUpdate($data);
        $bindings['id'] = $id;
        $this->db->prepare(
            "UPDATE `{$this->table}` SET {$setClauses} WHERE id = :id"
        )->execute($bindings);

        $res->success($this->decode($this->findOrFail($id, $res)), 'Updated successfully.');
    }

    // ─── DELETE ────────────────────────────────────────────────────────────
    public function destroy(Request $req, Response $res, array $params): void
    {
        $id = (int) $params['id'];
        if (!$this->findOrFail($id, $res)) {
            return;
        }

        $this->db->prepare("DELETE FROM `{$this->table}` WHERE id = :id")->execute(['id' => $id]);
        $res->success(null, 'Deleted successfully.');
    }

    // ─── HELPERS ───────────────────────────────────────────────────────────

    /** Find a row by ID; emit 404 and return false if not found. */
    protected function findOrFail(int $id, Response $res): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            $res->error('Record not found.', 404);
            return false;
        }
        return $row;
    }

    /** Keep only $fillable keys from the input array. */
    protected function pick(array $input): array
    {
        return array_intersect_key($input, array_flip($this->fillable));
    }

    /** Build INSERT column/placeholder/binding lists. */
    protected function buildInsert(array $data): array
    {
        $cols         = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $placeholders = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
        return [$cols, $placeholders, $data];
    }

    /** Build SET clause and bindings for UPDATE. */
    protected function buildUpdate(array $data): array
    {
        $sets     = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
        return [$sets, $data];
    }

    /** Hook — subclasses can mutate $data before insert/update (e.g. JSON encode). */
    protected function beforeSave(array $data, bool $isCreate): array
    {
        return $data;
    }

    /** Hook — subclasses can decode JSON columns after fetch. */
    protected function decode(array $row): array
    {
        return $row;
    }

    /** Subclasses define ORDER BY clause. */
    protected function orderClause(): string
    {
        return 'id ASC';
    }
}
