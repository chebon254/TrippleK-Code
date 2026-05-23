<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payment_functions.php';

// Always respond 200 immediately so Paystack doesn't retry unnecessarily
http_response_code(200);

$raw       = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if (APP_ENV === 'development') {
    error_log('Paystack Webhook received: ' . $raw);
}

// Validate HMAC SHA512 signature
if (!$signature || !paystack_verify_webhook_signature($raw, $signature)) {
    error_log('Paystack Webhook: invalid signature');
    exit('OK');
}

$payload = json_decode($raw, true);
$event   = $payload['event'] ?? '';

// Only process successful charges
if ($event !== 'charge.success') {
    exit('OK');
}

$data      = $payload['data'] ?? [];
$reference = $data['reference'] ?? '';
$status    = $data['status']    ?? '';

if (!$reference || $status !== 'success') {
    exit('OK');
}

// Double-verify with Paystack API (never trust webhook data alone)
try {
    $verified = paystack_verify_transaction($reference);
} catch (Throwable $e) {
    error_log('Paystack webhook verify error: ' . $e->getMessage());
    exit('OK');
}

$tx_data   = $verified['data'] ?? [];
$tx_status = $tx_data['status'] ?? '';

if ($tx_status !== 'success') {
    exit('OK');
}

// The reference is our booking_ref
$booking_ref = $reference;

$db = get_db();

$bk_stmt = $db->prepare('SELECT id, total_amount, status FROM bookings WHERE booking_ref = ? LIMIT 1');
$bk_stmt->execute([$booking_ref]);
$booking = $bk_stmt->fetch();

if (!$booking) {
    error_log('Paystack webhook: booking not found for ref ' . $booking_ref);
    exit('OK');
}

// Idempotency — skip if already confirmed
if ($booking['status'] === 'confirmed') {
    exit('OK');
}

// Verify amount matches (amount in kobo)
$expected_kobo = (int) round((float)$booking['total_amount'] * 100);
$paid_kobo     = (int)($tx_data['amount'] ?? 0);

if ($paid_kobo < $expected_kobo) {
    error_log("Paystack webhook: amount mismatch for {$booking_ref}. Expected {$expected_kobo}, got {$paid_kobo}");
    exit('OK');
}

try {
    paystack_confirm_booking($db, (int)$booking['id'], $reference, $tx_data);
} catch (Throwable $e) {
    error_log('Paystack webhook DB error: ' . $e->getMessage());
}

exit('OK');
