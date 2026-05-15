<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payment_functions.php';

// Verify Flutterwave webhook signature
$secret_hash = get_setting('flutterwave_hash', '');
$signature   = $_SERVER['HTTP_VERIF_HASH'] ?? '';

if ($secret_hash && $signature !== $secret_hash) {
    http_response_code(401);
    exit('Unauthorized');
}

$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (APP_ENV === 'development') {
    error_log('Flutterwave Webhook: ' . $raw);
}

$event = $payload['event'] ?? '';
if ($event !== 'charge.completed') {
    http_response_code(200);
    echo 'OK';
    exit;
}

$data        = $payload['data'] ?? [];
$tx_ref      = $data['tx_ref'] ?? '';    // our booking_ref
$tx_id       = $data['id']     ?? '';
$tx_status   = $data['status'] ?? '';
$tx_amount   = (float)($data['amount'] ?? 0);
$tx_currency = $data['currency'] ?? 'KES';

if (!$tx_ref || $tx_status !== 'successful') {
    http_response_code(200);
    echo 'OK';
    exit;
}

// Verify transaction with Flutterwave API
$verified = flutterwave_verify_transaction((string)$tx_id);
if (($verified['data']['status'] ?? '') !== 'successful') {
    http_response_code(200);
    echo 'OK';
    exit;
}

$db = get_db();

// Find booking
$bk_stmt = $db->prepare('SELECT id, total_amount, status FROM bookings WHERE booking_ref = ? LIMIT 1');
$bk_stmt->execute([$tx_ref]);
$booking = $bk_stmt->fetch();

if (!$booking || $booking['status'] === 'confirmed') {
    http_response_code(200);
    echo 'OK';
    exit;
}

// Determine payment method from Flutterwave response
$payment_type = match(strtolower($data['payment_type'] ?? '')) {
    'mobilemoneyrwanda','mobilemoney' => 'airtel_money',
    default                            => 'card',
};

$db->beginTransaction();
try {
    // Insert payment record
    $db->prepare('
        INSERT INTO payments
          (booking_id, payment_method, transaction_ref, gateway_ref, amount, currency, status, gateway_response, paid_at)
        VALUES (?, ?, ?, ?, ?, ?, "completed", ?, NOW())
    ')->execute([
        $booking['id'],
        $payment_type,
        $tx_ref,
        (string)$tx_id,
        $tx_amount,
        $tx_currency,
        json_encode($data),
    ]);

    // Confirm booking
    $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$booking['id']]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('Flutterwave callback DB error: ' . $e->getMessage());
}

http_response_code(200);
echo 'OK';
