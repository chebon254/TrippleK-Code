<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Read raw POST body from Safaricom
$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

// Log for debugging (remove in production)
if (APP_ENV === 'development') {
    error_log('M-Pesa Callback: ' . $raw);
}

$body = $payload['Body']['stkCallback'] ?? null;
if (!$body) {
    http_response_code(200);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'OK']);
    exit;
}

$checkout_id = $body['CheckoutRequestID'] ?? '';
$result_code = (int)($body['ResultCode'] ?? -1);

$db = get_db();

// Find the pending payment
$pay_stmt = $db->prepare('
    SELECT p.id, p.booking_id, b.booking_ref
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    WHERE p.transaction_ref = ? AND p.status = "processing"
    LIMIT 1
');
$pay_stmt->execute([$checkout_id]);
$payment = $pay_stmt->fetch();

if (!$payment) {
    http_response_code(200);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'OK']);
    exit;
}

if ($result_code === 0) {
    // Successful payment — extract metadata
    $metadata = [];
    foreach ($body['CallbackMetadata']['Item'] ?? [] as $item) {
        $metadata[$item['Name']] = $item['Value'] ?? null;
    }

    $receipt_no = $metadata['MpesaReceiptNumber'] ?? null;
    $amount     = (float)($metadata['Amount'] ?? 0);
    $paid_at    = date('Y-m-d H:i:s');

    $db->beginTransaction();
    try {
        // Update payment record
        $db->prepare('
            UPDATE payments SET
              status = "completed", gateway_ref = ?, amount = ?,
              gateway_response = ?, paid_at = ?
            WHERE id = ?
        ')->execute([
            $receipt_no,
            $amount,
            json_encode($body),
            $paid_at,
            $payment['id'],
        ]);

        // Confirm booking
        $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")
           ->execute([$payment['booking_id']]);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('M-Pesa callback DB error: ' . $e->getMessage());
    }
} else {
    // Failed payment
    $db->prepare('
        UPDATE payments SET status = "failed", gateway_response = ? WHERE id = ?
    ')->execute([json_encode($body), $payment['id']]);
}

// Always respond 200 to Safaricom
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
