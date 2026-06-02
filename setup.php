<?php
/**
 * One-time setup script — run via browser then DELETE this file.
 * Visit: http://tripplek.local/setup.php
 */

// Prevent running on production
if (isset($_SERVER['HTTP_HOST']) && !in_array($_SERVER['HTTP_HOST'], ['tripplek.local', 'localhost', '127.0.0.1'])) {
    http_response_code(403);
    die('Setup not allowed in production.');
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$errors   = [];
$executed = 0;

try {
    $db  = get_db();
    $sql = file_get_contents(__DIR__ . '/sql/tripplek.sql');

    // Strip single-line comments, then split on semicolons
    $sql = preg_replace('/--[^\n]*/', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try {
            $db->exec($stmt);
            $executed++;
        } catch (PDOException $e) {
            $errors[] = htmlspecialchars($e->getMessage()) . '<br><small>' . htmlspecialchars(substr($stmt, 0, 120)) . '...</small>';
        }
    }
} catch (PDOException $e) {
    $errors[] = 'DB connection failed: ' . htmlspecialchars($e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tripple K — Setup</title>
  <style>
    body { font-family: sans-serif; max-width: 700px; margin: 60px auto; padding: 0 20px; }
    h1   { color: #1a1a2e; }
    .ok  { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 16px; border-radius: 6px; margin-bottom: 16px; }
    .err { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 8px; font-size: 13px; }
    pre  { background: #f4f4f4; padding: 10px; border-radius: 4px; font-size: 12px; }
    a    { color: #007bff; }
  </style>
</head>
<body>
<h1>Tripple K — Database Setup</h1>

<?php if (empty($errors)): ?>
<div class="ok">
  <strong>Success!</strong> Executed <?= $executed ?> SQL statements.<br>
  Database tables and seed data are ready.<br><br>
  <strong>Default admin credentials:</strong><br>
  Email: <code>admin@trippleklogistics.com</code><br>
  Password: <code>Admin@1234</code><br><br>
  <a href="/admin/login.php">Go to Admin Login &rarr;</a><br>
  <a href="/index.php">Go to Homepage &rarr;</a>
</div>
<p><strong>Important:</strong> Delete or rename <code>setup.php</code> after setup is complete.</p>
<?php else: ?>
<div class="ok">Executed <?= $executed ?> statements. Some errors occurred (listed below — duplicates and "already exists" warnings are normal on re-run):</div>
<?php foreach ($errors as $err): ?>
<div class="err"><?= $err ?></div>
<?php endforeach; ?>
<p><a href="/admin/login.php">Try Admin Login &rarr;</a></p>
<?php endif; ?>

<pre>
DB_HOST: <?= DB_HOST ?>
DB_PORT: <?= DB_PORT ?>
DB_NAME: <?= DB_NAME ?>
PHP: <?= phpversion() ?>
</pre>
</body>
</html>
