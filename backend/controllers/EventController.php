<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

class EventController extends ResourceController
{
    protected string $table    = 'events';
    protected array  $fillable = ['title','event_date','event_time','venue','type','event_code','max_capacity','use_registration_url','registration_url','cover_image','tag','is_active'];

    private ?array $columnsCache = null;

    protected function orderClause(): string
    {
        return 'event_date ASC';
    }

    protected function beforeSave(array $data, bool $isCreate): array
    {
        // Accept legacy aliases from older frontend builds
        if (!array_key_exists('use_registration_url', $data) && array_key_exists('use_external_url', $data)) {
            $data['use_registration_url'] = $data['use_external_url'];
        }
        if (!array_key_exists('registration_url', $data) && array_key_exists('external_url', $data)) {
            $data['registration_url'] = $data['external_url'];
        }

        $allowedTypes = ['Concert', 'Workshop', 'Masterclass'];
        if (isset($data['type']) && !in_array($data['type'], $allowedTypes, true)) {
            $data['type'] = 'Concert';
        }

        // Normalize checkbox value to 0/1
        if (array_key_exists('use_registration_url', $data)) {
            $data['use_registration_url'] = $data['use_registration_url'] ? 1 : 0;
        }

        // Internal registration fields: only for Concert without external URL
        $isConcert = ($data['type'] ?? 'Concert') === 'Concert';
        $useUrl = !empty($data['use_registration_url']);
        if (!$isConcert || !$useUrl) {
            if (array_key_exists('use_registration_url', $data) && !$isConcert) {
                $data['use_registration_url'] = 0;
            }
            if (array_key_exists('registration_url', $data) && (!$isConcert || !$useUrl)) {
                // keep the key so filter passes, but null it when not external
                if (!$isConcert || !$useUrl) {
                    $data['registration_url'] = null;
                }
            }
        } else {
            if (isset($data['registration_url'])) {
                $data['registration_url'] = trim((string) $data['registration_url']) ?: null;
            }
        }

        // Normalize event_code: uppercase alphanumeric, max 20 chars.
        // Only meaningful for internal concert registration — clear otherwise.
        $isInternal = $isConcert && !$useUrl;
        if (array_key_exists('event_code', $data)) {
            $code = strtoupper(trim((string) $data['event_code']));
            $code = preg_replace('/[^A-Z0-9]/', '', $code) ?? '';
            $code = substr($code, 0, 20);
            $data['event_code'] = ($isInternal && $code !== '') ? $code : null;
        }

        // Normalize max_capacity: positive int or null. Only for internal concerts.
        if (array_key_exists('max_capacity', $data)) {
            $raw = $data['max_capacity'];
            if ($raw === '' || $raw === null) {
                $data['max_capacity'] = null;
            } else {
                $cap = (int) $raw;
                $data['max_capacity'] = ($isInternal && $cap > 0) ? $cap : null;
            }
        }

        // Normalize optional cover image (upload path or external URL)
        if (array_key_exists('cover_image', $data)) {
            $cover = trim((string) $data['cover_image']);
            $data['cover_image'] = $cover !== '' ? $cover : null;
        }

        // Drop legacy / alias fields if client still sends them
        unset($data['price'], $data['use_external_url'], $data['external_url']);

        // Drop fields that don't exist in DB yet (migration not run) to avoid 500
        return $this->filterToExistingColumns($data);
    }

    /**
     * Intersect $data with actual table columns so deploys without
     * migrate_events_type_url.sql don't blow up with "Unknown column".
     */
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
        $res->success($this->allWithCounts('e.is_active = 1'), 'OK');
    }

    public function publicShow(Request $req, Response $res, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $row = $this->oneWithCounts($id, true);
        if (!$row) {
            $res->error('Event not found.', 404);
            return;
        }
        $res->success($row, 'OK');
    }

    public function index(Request $req, Response $res): void
    {
        $res->success($this->allWithCounts(''), 'OK');
    }

    /**
     * Events + registration counts. Falls back to plain SELECT when the
     * event_registrations table / new columns don't exist yet.
     */
    private function allWithCounts(string $where): array
    {
        try {
            $sql = 'SELECT e.*, COUNT(r.id) AS registered_count FROM `events` e ' .
                   'LEFT JOIN `event_registrations` r ON r.event_id = e.id ' .
                   ($where !== '' ? "WHERE {$where} " : '') .
                   'GROUP BY e.id ORDER BY e.event_date ASC';
            $rows = $this->db->query($sql)->fetchAll();
            return array_map([$this, 'castCounts'], $rows);
        } catch (\Throwable) {
            try {
                $sql = 'SELECT * FROM `events` e ' .
                       ($where !== '' ? "WHERE {$where} " : '') .
                       'ORDER BY e.event_date ASC';
                return $this->db->query($sql)->fetchAll();
            } catch (\Throwable) {
                return [];
            }
        }
    }

    private function oneWithCounts(int $id, bool $onlyActive): array|false
    {
        try {
            $sql = 'SELECT e.*, COUNT(r.id) AS registered_count FROM `events` e ' .
                   'LEFT JOIN `event_registrations` r ON r.event_id = e.id ' .
                   'WHERE e.id = :id' . ($onlyActive ? ' AND e.is_active = 1' : '') .
                   ' GROUP BY e.id LIMIT 1';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            return $row ? $this->castCounts($row) : false;
        } catch (\Throwable) {
            $stmt = $this->db->prepare(
                'SELECT * FROM `events` WHERE id = :id' . ($onlyActive ? ' AND is_active = 1' : '') . ' LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            return $row ?: false;
        }
    }

    private function castCounts(array $row): array
    {
        if (isset($row['registered_count'])) {
            $row['registered_count'] = (int) $row['registered_count'];
        }
        if (isset($row['max_capacity']) && $row['max_capacity'] !== null) {
            $row['max_capacity'] = (int) $row['max_capacity'];
        }
        return $row;
    }
}
