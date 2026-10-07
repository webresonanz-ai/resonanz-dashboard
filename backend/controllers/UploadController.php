<?php

declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;

/**
 * UploadController — handles admin image uploads.
 *
 * POST /api/admin/uploads  (multipart/form-data, field: "image", optional field: "folder" = events|home|facilities)
 * Returns: { url: "/uploads/events/xxx.jpg" }
 */
class UploadController
{
    private const MAX_BYTES = 5 * 1024 * 1024; // 5 MB
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const ALLOWED_FOLDERS = ['events' => 'event_', 'home' => 'home_', 'facilities' => 'facility_'];

    public function store(Request $req, Response $res): void
    {
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

        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            $res->error('Image too large. Max 5 MB.', 422);
            return;
        }

        $origName = (string) ($file['name'] ?? 'upload');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
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

        // Target subfolder (events = default)
        $folder = strtolower(trim((string) ($req->input('folder') ?? 'events')));
        if (!array_key_exists($folder, self::ALLOWED_FOLDERS)) {
            $res->error('Invalid folder. Allowed: events, home, facilities.', 422);
            return;
        }
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
