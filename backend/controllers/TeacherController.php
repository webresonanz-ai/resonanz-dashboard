<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

class TeacherController extends ResourceController
{
    protected string $table    = 'teachers';
    protected array  $fillable = ['name','role','bio','initials','email','is_active','sort_order'];

    protected function orderClause(): string
    {
        return 'sort_order ASC, id ASC';
    }

    public function publicIndex(Request $req, Response $res): void
    {
        $stmt = $this->db->query(
            "SELECT id,name,role,bio,initials,sort_order FROM `teachers` WHERE is_active = 1 ORDER BY sort_order ASC"
        );
        $res->success($stmt->fetchAll(), 'OK');
    }
}
