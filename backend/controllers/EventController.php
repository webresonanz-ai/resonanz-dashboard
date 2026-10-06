<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

class EventController extends ResourceController
{
    protected string $table    = 'events';
    protected array  $fillable = ['title','event_date','event_time','venue','type','use_registration_url','registration_url','tag','is_active'];

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

        // URL only applies to Concert + checkbox on; otherwise clear it
        $isConcert = ($data['type'] ?? 'Concert') === 'Concert';
        $useUrl = !empty($data['use_registration_url']);
        if (!$isConcert || !$useUrl) {
            if (array_key_exists('use_registration_url', $data) && !$isConcert) {
                $data['use_registration_url'] = 0;
            }
            if (array_key_exists('registration_url', $data)) {
                $data['registration_url'] = null;
            }
        } else {
            if (isset($data['registration_url'])) {
                $data['registration_url'] = trim((string) $data['registration_url']) ?: null;
            }
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
        $stmt = $this->db->query(
            'SELECT * FROM `events` WHERE is_active = 1 ORDER BY event_date ASC'
        );
        $res->success($stmt->fetchAll(), 'OK');
    }
}
