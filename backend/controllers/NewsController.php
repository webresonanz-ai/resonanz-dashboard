<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

class NewsController extends ResourceController
{
    protected string $table    = 'news';
    protected array  $fillable = ['title','category','excerpt','content','is_published','published_at'];

    protected function orderClause(): string
    {
        return 'published_at DESC';
    }

    public function publicIndex(Request $req, Response $res): void
    {
        $stmt = $this->db->query(
            "SELECT * FROM `news` WHERE is_published = 1 ORDER BY published_at DESC"
        );
        $res->success($stmt->fetchAll(), 'OK');
    }
}
