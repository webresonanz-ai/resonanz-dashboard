<?php

declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use PDO;

/**
 * HomeController — guest Home page content configuration.
 *
 * The `home_settings` table is a key → {value_en, value_id} store.
 * Missing/empty values mean "use the built-in frontend default",
 * so a fresh install (or after reset) renders exactly like before.
 *
 *   GET    /api/home          public snapshot  { key: {en, id} }
 *   GET    /api/admin/home    same snapshot (admin)
 *   PUT    /api/admin/home    bulk upsert      { key: {en,id} | "plain" }
 *   DELETE /api/admin/home    clear all (frontend falls back to defaults)
 */
class HomeController
{
    /** Bilingual text keys: {en, id} */
    public const TEXT_KEYS = [
        'hero_badge',
        'hero_title_a',
        'hero_title_highlight',
        'hero_title_b',
        'hero_subtitle',
        'hero_primary_label',
        'hero_secondary_label',
        'hero_award_label',
        'hero_students_label',
        'stat_students_label',
        'stat_events_label',
        'stat_awards_label',
        'stat_faculty_label',
        'why_eyebrow',
        'why_title_a',
        'why_subtitle',
        'cta_title_a',
        'cta_title_highlight',
        'cta_subtitle',
        'cta_button_label',
    ];

    /** Language-neutral keys: single value (stored in value_en) */
    public const NEUTRAL_KEYS = [
        'hero_award_value',
        'stat_students_value',
        'stat_awards_value',
        'hero_background_image',
        'hero_side_image',
    ];

    /** JSON-encoded keys (stored in value_en) */
    public const JSON_KEYS = [
        // [{title_en,title_id,desc_en,desc_id} x3]
        'features',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** @return string[] */
    public static function allowedKeys(): array
    {
        return array_merge(self::TEXT_KEYS, self::NEUTRAL_KEYS, self::JSON_KEYS);
    }

    // ─── PUBLIC SNAPSHOT ─────────────────────────────────────────
    public function publicIndex(Request $req, Response $res): void
    {
        $res->success($this->fetchAll(), 'OK');
    }

    // ─── ADMIN SNAPSHOT ──────────────────────────────────────────
    public function show(Request $req, Response $res): void
    {
        $res->success($this->fetchAll(), 'OK');
    }

    /** @return array<string, array{en:string,id:string}> */
    private function fetchAll(): array
    {
        try {
            $stmt = $this->db->query('SELECT `key`, `value_en`, `value_id` FROM `home_settings`');
            $rows = $stmt->fetchAll();
        } catch (\Throwable) {
            // Table not migrated yet → empty snapshot = frontend defaults
            return [];
        }

        $allowed = self::allowedKeys();
        $out = [];
        foreach ($rows as $r) {
            if (!is_array($r)) continue;
            $k = $r['key'] ?? null;
            if (!is_string($k) || !in_array($k, $allowed, true)) continue;
            $out[$k] = [
                'en' => (string) ($r['value_en'] ?? ''),
                'id' => (string) ($r['value_id'] ?? ''),
            ];
        }
        return $out;
    }

    // ─── BULK UPSERT ─────────────────────────────────────────────
    public function update(Request $req, Response $res): void
    {
        $data = $req->all();
        if (!is_array($data)) {
            $res->error('Invalid payload.', 422);
            return;
        }

        $allowed = self::allowedKeys();
        $sql = 'INSERT INTO `home_settings` (`key`, `value_en`, `value_id`)'
            . ' VALUES (:k, :en, :id)'
            . ' ON DUPLICATE KEY UPDATE `value_en` = VALUES(`value_en`), `value_id` = VALUES(`value_id`)';

        try {
            $stmt = $this->db->prepare($sql);
        } catch (\Throwable) {
            $res->error('Home settings table is not available. Please run the database migration.', 500);
            return;
        }

        $saved = 0;
        foreach ($data as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;

            [$en, $id, $ok] = $this->normalize($k, $v);
            if (!$ok) {
                $res->error("Invalid value for '{$k}'.", 422);
                return;
            }

            if (strlen($en) > 60000 || strlen((string) $id) > 60000) {
                $res->error("Value for '{$k}' is too long.", 422);
                return;
            }

            try {
                $stmt->execute(['k' => $k, 'en' => $en, 'id' => $id]);
                $saved++;
            } catch (\Throwable $e) {
                $res->error('Could not save home settings.', 500);
                return;
            }
        }

        $res->success($this->fetchAll(), "Saved {$saved} setting(s).");
    }

    /**
     * Normalize one incoming value to [value_en, value_id, ok].
     *
     * @return array{0:string,1:?string,2:bool}
     */
    private function normalize(string $key, mixed $v): array
    {
        // Language-neutral keys: single string (or {en} object)
        if (in_array($key, self::NEUTRAL_KEYS, true)) {
            if (is_array($v)) $v = $v['en'] ?? '';
            return [is_scalar($v) ? (string) $v : '', null, true];
        }

        // JSON keys: string or array (encoded); empty clears the override
        if (in_array($key, self::JSON_KEYS, true)) {
            $json = is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            if (!is_string($json)) return ['', null, false];
            if (trim($json) === '') return ['', null, true];
            return is_array(json_decode($json, true)) ? [$json, null, true] : ['', null, false];
        }

        // Bilingual text keys: {en, id} object or plain string (applies to EN)
        if (is_array($v)) {
            $en = isset($v['en']) && is_scalar($v['en']) ? (string) $v['en'] : '';
            $id = isset($v['id']) && is_scalar($v['id']) ? (string) $v['id'] : '';
            return [$en, $id, true];
        }
        return [is_scalar($v) ? (string) $v : '', '', true];
    }

    // ─── RESET (clear overrides → frontend defaults) ─────────────
    public function reset(Request $req, Response $res): void
    {
        try {
            $this->db->exec('DELETE FROM `home_settings`');
        } catch (\Throwable) {
            $res->error('Home settings table is not available. Please run the database migration.', 500);
            return;
        }
        $res->success([], 'Home page reset to defaults.');
    }
}
