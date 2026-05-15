<?php
// ─── Database ────────────────────────────────────────────────────────────────
define('DB_HOST',    '127.0.0.1');
define('DB_PORT',    '10009');
define('DB_NAME',    'tripplek');
define('DB_USER',    'root');
define('DB_PASS',    'root');
define('DB_SOCKET',  '/home/kibe/.config/Local/run/8CM_ebu2L/mysql/mysqld.sock');
define('DB_CHARSET', 'utf8mb4');

// ─── Application ─────────────────────────────────────────────────────────────
define('APP_URL',  'http://tripplek.local');   // → https://tripplek.co.ke on deploy
define('APP_ENV',  'development');              // → 'production' on deploy
define('APP_NAME', 'Tripple K');

// ─── Paths ───────────────────────────────────────────────────────────────────
define('BASE_PATH',   dirname(__DIR__));
define('UPLOAD_DIR',  BASE_PATH . '/uploads/');
define('UPLOAD_URL',  APP_URL . '/uploads/');

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
