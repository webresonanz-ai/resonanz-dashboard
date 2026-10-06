<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

class ConcertController extends ResourceController
{
    protected string $table    = 'concerts';
    protected array  $fillable = ['title','event_date','event_time','venue','price','tag','is_active'];

    protected function orderClause(): string
    {
        return 'event_date ASC';
    }

    public function publicIndex(Request $req, Response $res): void
    {
        $stmt = $this->db->query(
            "SELECT * FROM `concerts` WHERE is_active = 1 ORDER BY event_date ASC"
        );
        $res->success($stmt->fetchAll(), 'OK');
    }
}
