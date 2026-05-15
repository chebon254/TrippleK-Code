<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payment_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$booking_ref = trim($input['booking_ref'] ?? '');
$phone       = trim($input['phone']       ?? '');

if (!$booking_ref || !$phone) {
    json_response(['success' => false, 'error' => 'Booking reference and phone number required'], 400);
}

$db   = get_db();
$stmt = $db->prepare('SELECT id, total_amount, status FROM bookings WHERE booking_ref = ? LIMIT 1');
$stmt->execute([$booking_ref]);
$booking = $stmt->fetch();

if (!$booking) {
    json_response(['success' => false, 'error' => 'Booking not found'], 404);
}
if ($booking['status'] === 'confirmed') {
    json_response(['success' => false, 'error' => 'This booking is already paid'], 409);
}
if ($booking['status'] === 'cancelled') {
    json_response(['success' => false, 'error' => 'This booking has been cancelled'], 409);
}

try {
    $result = mpesa_stk_push($phone, (float)$booking['total_amount'], $booking_ref);

    if (isset($result['CheckoutRequestID'])) {
        // Record the pending payment
        $ins = $db->prepare('
            INSERT INTO payments (booking_id, payment_method, transaction_ref, phone_number, amount, status)
            VALUES (?, "mpesa", ?, ?, ?, "processing")
        ');
        $ins->execute([
            $booking['id'],
            $result['CheckoutRequestID'],
            $phone,
            $booking['total_amount'],
        ]);

        json_response([
            'success'             => true,
            'checkout_request_id' => $result['CheckoutRequestID'],
            'message'             => 'STK Push sent to ' . $phone . '. Please enter your M-Pesa PIN.',
        ]);
    } else {
        json_response([
            'success' => false,
            'error'   => $result['errorMessage'] ?? 'M-Pesa request failed. Please try again.',
        ], 502);
    }
} catch (Throwable $e) {
    json_response(['success' => false, 'error' => 'Payment initiation failed: ' . $e->getMessage()], 500);
}
