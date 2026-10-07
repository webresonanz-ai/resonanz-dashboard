<?php

declare(strict_types=1);

namespace Services;

/**
 * MailService — minimal SMTP client for sending HTML mail via Gmail.
 *
 * Auth uses the Google App Password from the environment:
 *   GOOGLE_APP_EMAIL     full Gmail address (sender + SMTP username)
 *   GOOGLE_APP_PASSWORD  16-char app password (spaces/quotes tolerated)
 *
 * No external dependencies: raw socket + STARTTLS (requires openssl).
 */
class MailService
{
    private string $host = 'smtp.gmail.com';
    private int $port = 587;
    private int $timeout = 20;
    private string $lastError = '';

    public function lastError(): string
    {
        return $this->lastError;
    }

    public static function isConfigured(): bool
    {
        return self::username() !== '' && self::password() !== '';
    }

    public static function username(): string
    {
        return trim((string) ($_ENV['GOOGLE_APP_EMAIL'] ?? getenv('GOOGLE_APP_EMAIL') ?: ''));
    }

    public static function password(): string
    {
        $raw = (string) ($_ENV['GOOGLE_APP_PASSWORD'] ?? getenv('GOOGLE_APP_PASSWORD') ?: '');
        $raw = trim($raw);
        // Strip surrounding quotes (the .env loader keeps them) and inner spaces
        if (strlen($raw) >= 2) {
            $first = $raw[0];
            $last = $raw[strlen($raw) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $raw = substr($raw, 1, -1);
            }
        }
        return str_replace(' ', '', $raw);
    }

    /**
     * Build and send the event ticket email.
     *
     * @param array{to:string,name:string} $guest
     * @param array{title:string,event_date:string,event_time:string,venue:string,event_code:string} $event
     */
    public function sendTicket(array $guest, array $event, string $registrationCode): bool
    {
        $to = trim($guest['to'] ?? '');
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Invalid recipient email.';
            return false;
        }

        $name = trim($guest['name'] ?? 'Guest');
        $name = $name !== '' ? $name : 'Guest';
        $title = (string) ($event['title'] ?? 'Event');
        $subject = "Your ticket — {$title}";

        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data='
            . rawurlencode($registrationCode);

        $e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $html = <<<HTML
        <div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;color:#2b1a1a;">
          <div style="background:#3d0a0a;color:#f5c86e;padding:24px 28px;border-radius:12px 12px 0 0;">
            <p style="margin:0;font-size:12px;letter-spacing:3px;">RESONANZ MUSIC FOUNDATION</p>
            <h1 style="margin:8px 0 0;font-size:22px;">{$e($title)}</h1>
          </div>
          <div style="border:1px solid #e5d5b0;border-top:none;padding:24px 28px;border-radius:0 0 12px 12px;">
            <p style="margin:0 0 4px;">Hello <strong>{$e($name)}</strong>,</p>
            <p style="margin:0 0 16px;">Your registration is confirmed. Please show this QR code at the entrance.</p>
            <table style="font-size:14px;margin-bottom:16px;">
              <tr><td style="color:#888;padding-right:12px;">Date</td><td><strong>{$e((string)($event['event_date'] ?? ''))} {$e(substr((string)($event['event_time'] ?? ''), 0, 5))}</strong></td></tr>
              <tr><td style="color:#888;padding-right:12px;">Venue</td><td><strong>{$e((string)($event['venue'] ?? ''))}</strong></td></tr>
              <tr><td style="color:#888;padding-right:12px;">Guest</td><td><strong>{$e($name)}</strong></td></tr>
            </table>
            <div style="text-align:center;background:#faf6ec;border:1px dashed #c9a94e;border-radius:10px;padding:20px;">
              <img src="{$qrUrl}" alt="Ticket QR code" width="220" height="220" style="display:block;margin:0 auto;" />
            </div>
            <p style="font-size:12px;color:#999;margin:16px 0 0;">This ticket is linked to your email and valid for one entry.</p>
          </div>
        </div>
        HTML;

        $text = "Hello {$name},\n\nYour registration for \"{$title}\" is confirmed.\n"
            . "Date: " . ($event['event_date'] ?? '') . ' ' . substr((string)($event['event_time'] ?? ''), 0, 5) . "\n"
            . "Venue: " . ($event['venue'] ?? '') . "\n"
            . "Ticket code: {$registrationCode}\n\n"
            . "Please show this code (or its QR code) at the entrance.\n";

        return $this->send($to, $subject, $html, $text);
    }

    // ─── SMTP plumbing ──────────────────────────────────────────

    public function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        $user = self::username();
        $pass = self::password();
        if ($user === '' || $pass === '') {
            $this->lastError = 'Email is not configured (GOOGLE_APP_EMAIL / GOOGLE_APP_PASSWORD).';
            return false;
        }

        $sock = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}", $errno, $errstr, $this->timeout
        );
        if (!$sock) {
            $this->lastError = "SMTP connect failed: {$errstr} ({$errno}).";
            return false;
        }
        stream_set_timeout($sock, $this->timeout);

        try {
            $this->expect($sock, 220);
            $this->cmd($sock, "EHLO resonanz.local", 250);
            $this->cmd($sock, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('TLS negotiation failed.');
            }
            $this->cmd($sock, "EHLO resonanz.local", 250);
            $this->cmd($sock, 'AUTH LOGIN', 334);
            $this->cmd($sock, base64_encode($user), 334);
            $this->cmd($sock, base64_encode($pass), 235);

            $from = $user;
            $this->cmd($sock, "MAIL FROM:<{$from}>", 250);
            $this->cmd($sock, "RCPT TO:<{$to}>", [250, 251]);
            $this->cmd($sock, 'DATA', 354);

            $boundary = 'rz_' . bin2hex(random_bytes(12));
            $headers = [
                "From: Resonanz Music Foundation <{$from}>",
                "To: <{$to}>",
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            ];
            $body = "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($text !== '' ? $text : strip_tags($html)))
                . "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($html))
                . "--{$boundary}--\r\n";

            fwrite($sock, implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n");
            $this->expect($sock, 250);
            try { $this->cmd($sock, 'QUIT', 221); } catch (\Throwable) { /* ignore */ }
            fclose($sock);
            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            try { fwrite($sock, "QUIT\r\n"); } catch (\Throwable) { /* ignore */ }
            fclose($sock);
            return false;
        }
    }

    private function readLine(mixed $sock): string
    {
        $data = '';
        while (($line = fgets($sock, 515)) !== false) {
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        if ($data === '') {
            throw new \RuntimeException('SMTP read timeout.');
        }
        return $data;
    }

    /** @param int|int[] $want */
    private function expect(mixed $sock, int|array $want): string
    {
        $line = $this->readLine($sock);
        $code = (int) substr($line, 0, 3);
        $want = (array) $want;
        if (!in_array($code, $want, true)) {
            throw new \RuntimeException("SMTP error: {$line}");
        }
        return $line;
    }

    /** @param int|int[] $want */
    private function cmd(mixed $sock, string $command, int|array $want): string
    {
        fwrite($sock, $command . "\r\n");
        return $this->expect($sock, $want);
    }

    private function encodeHeader(string $subject): string
    {
        if (preg_match('/^[\x20-\x7E]*$/', $subject)) {
            return $subject;
        }
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }
}
