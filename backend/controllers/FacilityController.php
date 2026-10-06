<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

class FacilityController extends ResourceController
{
    protected string $table    = 'facilities';
    protected array  $fillable = ['name','capacity','description','icon','is_active','sort_order'];

    protected function orderClause(): string
    {
        return 'sort_order ASC, id ASC';
    }

    public function publicIndex(Request $req, Response $res): void
    {
        $stmt = $this->db->query(
            "SELECT * FROM `facilities` WHERE is_active = 1 ORDER BY sort_order ASC"
        );
        $res->success($stmt->fetchAll(), 'OK');
    }
}
