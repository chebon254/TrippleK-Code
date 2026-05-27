<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

admin_logout();
header('Location: /admin/login');
exit;
