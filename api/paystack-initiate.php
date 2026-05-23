<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payment_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input       = json_decode(file_get_contents('php://input'), true);
$booking_ref = trim($input['booking_ref'] ?? '');

if (!$booking_ref) {
    json_response(['success' => false, 'error' => 'Booking reference required'], 400);
}

if (!paystack_secret_key()) {
    json_response(['success' => false, 'error' => 'Payment gateway not configured. Please contact us.'], 503);
}

$db = get_db();

$stmt = $db->prepare('
    SELECT b.id, b.booking_ref, b.total_amount, b.status, b.customer_id,
           cu.full_name, cu.email, cu.phone
    FROM bookings b
    JOIN customers cu ON b.customer_id = cu.id
    WHERE b.booking_ref = ?
    LIMIT 1
');
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
    $result = paystack_initialize_transaction(
        ['booking_ref' => $booking['booking_ref'], 'total_amount' => (float)$booking['total_amount']],
        ['full_name'   => $booking['full_name'],   'email'        => $booking['email'],  'phone' => $booking['phone']]
    );

    if (!empty($result['status']) && !empty($result['data']['authorization_url'])) {
        json_response([
            'success'           => true,
            'authorization_url' => $result['data']['authorization_url'],
            'reference'         => $result['data']['reference'],
        ]);
    } else {
        $msg = $result['message'] ?? 'Could not reach payment gateway. Please try again.';
        json_response(['success' => false, 'error' => $msg], 502);
    }
} catch (Throwable $e) {
    error_log('Paystack initiate error: ' . $e->getMessage());
    json_response(['success' => false, 'error' => 'Payment initiation failed. Please try again.'], 500);
}
