<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

/**
 * UploadController — handles admin image & font uploads.
 *
 * POST /api/admin/uploads  (multipart/form-data)
 *   Images: field "image", optional "folder" = events|home|facilities|teachers
 *           → { url: "/uploads/events/xxx.jpg" }
 *   Fonts:  field "font", "folder" = fonts  (.ttf/.otf/.woff/.woff2, max 10 MB)
 *           → { url: "/uploads/fonts/font_xxx.ttf" }
 */
class UploadController
{
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;  // 5 MB
    private const MAX_FONT_BYTES  = 10 * 1024 * 1024; // 10 MB
    private const ALLOWED_IMG_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const ALLOWED_FONT_EXT = ['ttf', 'otf', 'woff', 'woff2'];
    private const ALLOWED_FOLDERS = ['events' => 'event_', 'home' => 'home_', 'facilities' => 'facility_', 'teachers' => 'teacher_', 'fonts' => 'font_'];

    /** Magic-byte signatures per font extension */
    private const FONT_MAGIC = [
        // TTF is usually 00 01 00 00; accept Mac 'true'/'typ1' and CFF 'OTTO' too
        'ttf'   => ["\x00\x01\x00\x00", 'true', 'typ1', 'OTTO'],
        // OTF is usually 'OTTO', but TrueType-flavoured .otf files exist
        'otf'   => ['OTTO', "\x00\x01\x00\x00", 'true', 'typ1'],
        'woff'  => ['wOFF'],
        'woff2' => ['wOF2'],
    ];

    public function store(Request $req, Response $res): void
    {
        // Target subfolder first (fonts = font files, anything else = images)
        $folder = strtolower(trim((string) ($req->input('folder') ?? 'events')));
        if (!array_key_exists($folder, self::ALLOWED_FOLDERS)) {
            $res->error('Invalid folder. Allowed: events, home, facilities, teachers, fonts.', 422);
            return;
        }

        if ($folder === 'fonts') {
            $this->storeFont($req, $res);
            return;
        }

        $file = $req->file('image');

        if (!$file) {
            $res->error('No image file provided. Send multipart/form-data with field "image".', 422);
            return;
        }

        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $res->error($this->uploadErrorMessage($err), 422);
            return;
        }

        if (($file['size'] ?? 0) > self::MAX_IMAGE_BYTES) {
            $res->error('Image too large. Max 5 MB.', 422);
            return;
        }

        $origName = (string) ($file['name'] ?? 'upload');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_IMG_EXT, true)) {
            $res->error('Invalid image type. Allowed: jpg, jpeg, png, webp, gif.', 422);
            return;
        }

        // Verify it is actually an image
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $res->error('Invalid upload.', 422);
            return;
        }
        $info = @getimagesize($tmp);
        if ($info === false) {
            $res->error('Uploaded file is not a valid image.', 422);
            return;
        }

        // Target subfolder was validated at the top of store()
        $prefix = self::ALLOWED_FOLDERS[$folder];

        $dir = __DIR__ . '/../public/uploads/' . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            $res->error('Could not create upload directory.', 500);
            return;
        }

        try {
            $name = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        } catch (\Throwable) {
            $name = $prefix . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        }

        if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
            $res->error('Could not save uploaded image.', 500);
            return;
        }

        $res->success(['url' => '/uploads/' . $folder . '/' . $name], 'Uploaded successfully.', 201);
    }

    /**
     * Font upload branch: field "font", folder "fonts".
     * Accepts .ttf/.otf/.woff/.woff2 (max 10 MB), verified by magic bytes.
     */
    private function storeFont(Request $req, Response $res): void
    {
        $file = $req->file('font');

        if (!$file) {
            $res->error('No font file provided. Send multipart/form-data with field "font" and folder "fonts".', 422);
            return;
        }

        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $res->error($this->uploadErrorMessage($err), 422);
            return;
        }

        if (($file['size'] ?? 0) > self::MAX_FONT_BYTES) {
            $res->error('Font too large. Max 10 MB.', 422);
            return;
        }

        $origName = (string) ($file['name'] ?? 'font');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_FONT_EXT, true)) {
            $res->error('Invalid font type. Allowed: ttf, otf, woff, woff2.', 422);
            return;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $res->error('Invalid upload.', 422);
            return;
        }

        // Verify magic bytes match the claimed font format
        $head = @file_get_contents($tmp, false, null, 0, 4);
        $valid = false;
        foreach (self::FONT_MAGIC[$ext] ?? [] as $sig) {
            if ($head !== false && substr($head, 0, strlen($sig)) === $sig) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            $res->error('Uploaded file is not a valid font file.', 422);
            return;
        }

        $dir = __DIR__ . '/../public/uploads/fonts';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            $res->error('Could not create upload directory.', 500);
            return;
        }

        try {
            $name = 'font_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        } catch (\Throwable) {
            $name = 'font_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        }

        if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
            $res->error('Could not save uploaded font.', 500);
            return;
        }

        $res->success(['url' => '/uploads/fonts/' . $name], 'Font uploaded successfully.', 201);
    }

    /**
     * Public font streaming: GET /api/fonts/:name
     *
     * Why this exists: font files under /uploads/fonts/ are static files.
     * Depending on the server (php -S without router script, Apache, nginx)
     * they may be served WITHOUT CORS headers, so browsers silently refuse
     * to load them via @font-face on the guest site (origin :5173 → :8000).
     * This route always passes through index.php + CorsMiddleware, so the
     * font is guaranteed to carry Access-Control-Allow-Origin.
     */
    public function showFont(Request $req, Response $res, array $params = []): void
    {
        $name = (string) ($params['name'] ?? '');
        // Only serve files this app generated: font_<stamp>_<rand>.<ext>
        if (!preg_match('/\Afont_[A-Za-z0-9_\-]+\.(ttf|otf|woff|woff2)\z/', $name)) {
            $res->error('Font not found.', 404);
            return;
        }

        $dir = __DIR__ . '/../public/uploads/fonts';
        $real = realpath($dir . '/' . $name);
        $base = realpath($dir);
        if ($real === false || $base === false || !str_starts_with($real, $base) || !is_file($real)) {
            $res->error('Font not found.', 404);
            return;
        }

        $mime = [
            'ttf' => 'font/ttf', 'otf' => 'font/otf',
            'woff' => 'font/woff', 'woff2' => 'font/woff2',
        ][strtolower(pathinfo($real, PATHINFO_EXTENSION))] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($real));
        header('Cache-Control: public, max-age=86400');
        header('Cross-Origin-Resource-Policy: cross-origin');
        readfile($real);
        exit;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'Image exceeds server limit (upload_max_filesize=' . ini_get('upload_max_filesize') . '). Please use a smaller image or raise the PHP limit.',
            UPLOAD_ERR_FORM_SIZE => 'Image exceeds the form size limit. Please use a smaller image.',
            UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No image file provided. Send multipart/form-data with field "image".',
            UPLOAD_ERR_NO_TMP_DIR => 'Server misconfigured: missing temp directory.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by a server extension.',
            default => 'Upload failed (code ' . $code . ').',
        };
    }
}
