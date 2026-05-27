<?php
// ─── Load .env (one level above web root — never web-accessible) ──────────────
(function () {
    $env_file = dirname(dirname(__DIR__)) . '/.env';  // app/.env
    if (!file_exists($env_file)) return;
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $val;
            putenv("{$key}={$val}");
        }
    }
})();

// ─── Database ────────────────────────────────────────────────────────────────
define('DB_HOST',    'srv2026.hstgr.io');
define('DB_PORT',    '3306');
define('DB_NAME',    'u687796233_tripplek');
define('DB_USER',    'u687796233_tripple');
define('DB_PASS',    $_ENV['DB_PASS'] ?? '');          // set in .env
define('DB_SOCKET',  '');                               // empty = use host+port (required on shared hosting)
define('DB_CHARSET', 'utf8mb4');

// ─── Application ─────────────────────────────────────────────────────────────
define('APP_URL',  'https://saddlebrown-coyote-537196.hostingersite.com');  // → https://tripplek.co.ke on final deploy
define('APP_ENV',  'production');
define('APP_NAME', 'Tripple K');

// ─── Paths ───────────────────────────────────────────────────────────────────
define('BASE_PATH',   dirname(__DIR__));
define('UPLOAD_DIR',  BASE_PATH . '/uploads/');
// Root-relative so uploads work on any host/port (local dev + production)
define('UPLOAD_URL',  '/uploads/');

// ─── Session ─────────────────────────────────────────────────────────────────
define('SESSION_NAME', 'TRIPPLEK_ADMIN');

// ─── Currency ────────────────────────────────────────────────────────────────
define('CURRENCY', 'KES');

// ─── PHP settings ────────────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');
