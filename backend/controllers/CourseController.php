<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

class CourseController extends ResourceController
{
    protected string $table    = 'courses';
    protected array  $fillable = ['name','level','price','period','duration','class_size','features','is_featured','is_active','sort_order'];

    protected function orderClause(): string
    {
        return 'sort_order ASC, id ASC';
    }

    protected function beforeSave(array $data, bool $isCreate): array
    {
        // Encode features array as JSON string for storage
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }
        return $data;
    }

    protected function decode(array $row): array
    {
        if (isset($row['features']) && is_string($row['features'])) {
            $row['features'] = json_decode($row['features'], true) ?? [];
        }
        return $row;
    }

    public function publicIndex(Request $req, Response $res): void
    {
        $stmt = $this->db->query(
            "SELECT * FROM `courses` WHERE is_active = 1 ORDER BY sort_order ASC"
        );
        $rows = array_map([$this, 'decode'], $stmt->fetchAll());
        $res->success($rows, 'OK');
    }
}
