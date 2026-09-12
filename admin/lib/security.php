<?php
declare(strict_types=1);

const CMS_LOGIN_MAX_ATTEMPTS = 8;
const CMS_LOGIN_WINDOW = 900;

function cms_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    return (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function cms_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => cms_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('BLCMSSESS');
    session_start();

    $now = time();
    if (isset($_SESSION['last_seen']) && $now - (int) $_SESSION['last_seen'] > 28800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_seen'] = $now;
}

function cms_security_headers(bool $admin = false): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: ' . ($admin ? 'DENY' : 'SAMEORIGIN'));
    header_remove('X-Powered-By');
    if ($admin) {
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header(
            "Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
            . "style-src 'self' 'unsafe-inline'; script-src 'self'; "
            . "form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'"
        );
    }
}

function cms_client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return $ip !== '' ? substr($ip, 0, 45) : 'unknown';
}

function ensure_login_attempts_table(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    db()->exec(
        'CREATE TABLE IF NOT EXISTS login_attempts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            username VARCHAR(191) NOT NULL DEFAULT "",
            attempted_at INT UNSIGNED NOT NULL,
            INDEX idx_ip_time (ip, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $done = true;
}

function login_lockout_seconds(string $ip): int
{
    try {
        ensure_login_attempts_table();
        $cutoff = time() - CMS_LOGIN_WINDOW;
        $stmt = db()->prepare(
            'SELECT COUNT(*) AS failures, COALESCE(MAX(attempted_at), 0) AS latest
             FROM login_attempts WHERE ip = ? AND attempted_at > ?'
        );
        $stmt->execute([$ip, $cutoff]);
        $row = $stmt->fetch();
        if (!$row || (int) $row['failures'] < CMS_LOGIN_MAX_ATTEMPTS) {
            return 0;
        }
        $remaining = ((int) $row['latest'] + CMS_LOGIN_WINDOW) - time();
        return $remaining > 0 ? $remaining : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

function record_failed_login(string $ip, string $username): void
{
    try {
        ensure_login_attempts_table();
        $stmt = db()->prepare('INSERT INTO login_attempts (ip, username, attempted_at) VALUES (?, ?, ?)');
        $stmt->execute([$ip, substr($username, 0, 191), time()]);
        db()->prepare('DELETE FROM login_attempts WHERE attempted_at < ?')->execute([time() - 86400]);
    } catch (Throwable $e) {
    }
}

function clear_login_attempts(string $ip): void
{
    try {
        ensure_login_attempts_table();
        db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
    } catch (Throwable $e) {
    }
}

function uses_default_password(int $userId): bool
{
    try {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? password_verify('admin123', $row['password_hash']) : false;
    } catch (Throwable $e) {
        return false;
    }
}

function describe_lockout(int $seconds): string
{
    $minutes = (int) ceil($seconds / 60);
    return $minutes <= 1 ? 'about a minute' : $minutes . ' minutes';
}
