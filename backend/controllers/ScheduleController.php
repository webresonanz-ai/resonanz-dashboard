<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

class ScheduleController extends ResourceController
{
    protected string $table    = 'schedule';
    protected array  $fillable = ['day','time_start','time_end','course','room','teacher','sort_order'];

    protected function orderClause(): string
    {
        return 'sort_order ASC, FIELD(day,"Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday")';
    }

    /** Public list (no auth needed) — called from the main frontend. */
    public function publicIndex(Request $req, Response $res): void
    {
        $this->index($req, $res);
    }
}
