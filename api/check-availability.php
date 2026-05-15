<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/booking_functions.php';

$car_id    = (int)($_GET['car_id']    ?? 0);
$date_from = trim($_GET['date_from']  ?? '');
$date_to   = trim($_GET['date_to']    ?? '');

if (!$car_id || !$date_from || !$date_to) {
    json_response(['available' => false, 'error' => 'Missing parameters'], 400);
}

if ($date_from > $date_to) {
    json_response(['available' => false, 'error' => 'Return date must be after pickup date'], 422);
}

if ($date_from < date('Y-m-d')) {
    json_response(['available' => false, 'error' => 'Pickup date cannot be in the past'], 422);
}

// Check car exists and is not permanently unavailable
$db   = get_db();
$stmt = $db->prepare("SELECT status FROM cars WHERE id = ?");
$stmt->execute([$car_id]);
$car  = $stmt->fetch();

if (!$car) {
    json_response(['available' => false, 'error' => 'Car not found'], 404);
}

if (in_array($car['status'], ['inactive', 'maintenance'], true)) {
    json_response(['available' => false, 'error' => 'This vehicle is not available for hire'], 200);
}

$available = is_car_available($car_id, $date_from, $date_to);

// Calculate num_days and cost preview
$days  = max(1, (int)((strtotime($date_to) - strtotime($date_from)) / 86400) + 1);
$price = 0;
if ($available) {
    $price_stmt = $db->prepare('SELECT price_per_day FROM cars WHERE id = ?');
    $price_stmt->execute([$car_id]);
    $price_per_day = (float)$price_stmt->fetchColumn();
    $price = $price_per_day * $days;
}

json_response([
    'available'       => $available,
    'num_days'        => $days,
    'estimated_total' => $price,
    'formatted_total' => $available ? format_kes($price) : null,
    'message'         => $available
        ? "Available for {$days} day" . ($days > 1 ? 's' : '')
        : 'Sorry, this vehicle is already booked for your selected dates.',
]);
