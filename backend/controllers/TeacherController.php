<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

class TeacherController extends ResourceController
{
    protected string $table    = 'teachers';
    protected array  $fillable = ['name','role','bio','initials','email','photo','is_active','sort_order'];

    private ?array $columnsCache = null;

    protected function orderClause(): string
    {
        return 'sort_order ASC, id ASC';
    }

    protected function beforeSave(array $data, bool $isCreate): array
    {
        // Normalize optional photo (upload path or external URL)
        if (array_key_exists('photo', $data)) {
            $photo = trim((string) $data['photo']);
            $data['photo'] = $photo !== '' ? $photo : null;
        }

        // Drop fields that don't exist in DB yet (migration not run) to avoid 500
        return $this->filterToExistingColumns($data);
    }

    private function filterToExistingColumns(array $data): array
    {
        $cols = $this->tableColumns();
        if ($cols === []) {
            return $data;
        }
        return array_intersect_key($data, array_flip($cols));
    }

    private function tableColumns(): array
    {
        if ($this->columnsCache !== null) {
            return $this->columnsCache;
        }
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$this->table}`");
            $rows = $stmt->fetchAll();
            $cols = [];
            foreach ($rows as $r) {
                $cols[] = is_array($r) ? ($r['Field'] ?? array_values($r)[0] ?? null) : null;
            }
            $this->columnsCache = array_values(array_filter($cols));
        } catch (\Throwable) {
            $this->columnsCache = [];
        }
        return $this->columnsCache;
    }

    public function publicIndex(Request $req, Response $res): void
    {
        try {
            $stmt = $this->db->query(
                "SELECT id,name,role,bio,initials,email,photo,sort_order FROM `teachers` WHERE is_active = 1 ORDER BY sort_order ASC"
            );
            $res->success($stmt->fetchAll(), 'OK');
        } catch (\Throwable) {
            // Fallback for DBs without the `photo`/`email` columns yet
            $stmt = $this->db->query(
                "SELECT id,name,role,bio,initials,sort_order FROM `teachers` WHERE is_active = 1 ORDER BY sort_order ASC"
            );
            $res->success($stmt->fetchAll(), 'OK');
        }
    }
}
