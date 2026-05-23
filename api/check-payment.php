<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$booking_ref = trim($_GET['booking_ref'] ?? '');

if (!$booking_ref) {
    json_response(['paid' => false, 'error' => 'Missing booking_ref parameter'], 400);
}

$db = get_db();

$stmt = $db->prepare('
    SELECT p.status, b.status AS booking_status, b.booking_ref
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    WHERE b.booking_ref = ?
    ORDER BY p.id DESC
    LIMIT 1
');
$stmt->execute([$booking_ref]);
$row = $stmt->fetch();

if (!$row) {
    json_response(['paid' => false, 'status' => 'pending']);
}

$paid   = $row['status'] === 'completed' || $row['booking_status'] === 'confirmed';
$failed = $row['status'] === 'failed';

json_response([
    'paid'          => $paid,
    'failed'        => $failed,
    'status'        => $row['status'],
    'booking_status'=> $row['booking_status'],
    'booking_ref'   => $row['booking_ref'],
    'redirect'      => $paid ? '/invoice.php?ref=' . urlencode($row['booking_ref']) : null,
]);
