<?php
/**
 * One-time DB migration: M-Pesa / Flutterwave → Paystack
 * DELETE THIS FILE after running it once.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();   // must be logged into admin

$db  = get_db();
$log = [];

try {
    // 1. Widen payment_method ENUM
    $db->exec("ALTER TABLE payments MODIFY COLUMN payment_method
        ENUM('card','mobile_money','bank_transfer','bank','cash') NOT NULL");
    $log[] = '✅  payments.payment_method ENUM updated.';
} catch (Throwable $e) {
    $log[] = '⚠️  ENUM alter: ' . $e->getMessage();
}

try {
    // 2. Remove old gateway settings
    $n = $db->exec("DELETE FROM settings WHERE setting_key IN (
        'mpesa_env','mpesa_shortcode','mpesa_consumer_key','mpesa_consumer_secret','mpesa_passkey',
        'flutterwave_env','flutterwave_public_key','flutterwave_secret_key','flutterwave_hash'
    )");
    $log[] = "✅  Removed {$n} old M-Pesa/Flutterwave settings rows.";
} catch (Throwable $e) {
    $log[] = '⚠️  Delete old settings: ' . $e->getMessage();
}

try {
    // 3. Seed Paystack settings
    $db->exec("INSERT IGNORE INTO settings (setting_key, setting_val, label, group_name, sort_order) VALUES
        ('paystack_env',        'test',                                             'Paystack Environment', 'payments', 1),
        ('paystack_public_key', 'pk_test_bb3228024e0509f44f75cf3355f69428966e645d', 'Paystack Public Key',  'payments', 2),
        ('paystack_secret_key', 'sk_test_08c50754a3e0326c362237dc1c39f68fb3803a7a', 'Paystack Secret Key',  'payments', 3)
    ");
    // Also UPDATE in case rows already existed with empty values
    $db->exec("UPDATE settings SET setting_val = 'pk_test_bb3228024e0509f44f75cf3355f69428966e645d' WHERE setting_key = 'paystack_public_key'");
    $db->exec("UPDATE settings SET setting_val = 'sk_test_08c50754a3e0326c362237dc1c39f68fb3803a7a' WHERE setting_key = 'paystack_secret_key'");
    $db->exec("UPDATE settings SET setting_val = 'test' WHERE setting_key = 'paystack_env'");
    $log[] = '✅  Paystack test keys saved to database.';
} catch (Throwable $e) {
    $log[] = '⚠️  Seed Paystack settings: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head><title>DB Migration</title>
<style>body{font-family:monospace;max-width:700px;margin:60px auto;padding:0 20px;}
pre{background:#1a1a1a;color:#c8942a;padding:20px;border-radius:6px;line-height:1.8;}
.done{background:#14532d;color:#86efac;padding:16px 20px;border-radius:6px;margin-top:16px;}
</style></head>
<body>
<h2>Paystack DB Migration</h2>
<pre><?= implode("\n", $log) ?></pre>
<div class="done">
  ✅ Done. <strong>Delete <code>/admin/run-migration</code> now.</strong><br>
  Then go to <a href="/admin/settings">Admin → Settings</a> to enter your Paystack keys.
</div>
</body>
</html>
