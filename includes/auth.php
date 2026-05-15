<?php
require_once __DIR__ . '/db.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function require_admin_auth(): void {
    start_session();
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_login(string $email, string $password): bool {
    $db   = get_db();
    $stmt = $db->prepare('SELECT id, password_hash FROM admin WHERE email = ? LIMIT 1');
    $stmt->execute([trim($email)]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        start_session();
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_email'] = $email;
        return true;
    }
    return false;
}

function admin_logout(): void {
    start_session();
    $_SESSION = [];
    session_destroy();
}

function is_logged_in(): bool {
    start_session();
    return !empty($_SESSION['admin_id']);
}
