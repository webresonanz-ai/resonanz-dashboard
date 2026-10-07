<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

class FacilityController extends ResourceController
{
    protected string $table    = 'facilities';
    protected array  $fillable = ['name','capacity','description','icon','image','is_active','sort_order'];

    private ?array $columnsCache = null;

    protected function orderClause(): string
    {
        return 'sort_order ASC, id ASC';
    }

    protected function beforeSave(array $data, bool $isCreate): array
    {
        // Normalize optional image (upload path or external URL)
        if (array_key_exists('image', $data)) {
            $image = trim((string) $data['image']);
            $data['image'] = $image !== '' ? $image : null;
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
        $stmt = $this->db->query(
            "SELECT * FROM `facilities` WHERE is_active = 1 ORDER BY sort_order ASC"
        );
        $res->success($stmt->fetchAll(), 'OK');
    }
}
