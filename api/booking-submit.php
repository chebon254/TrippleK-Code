<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/booking_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    json_response(['success' => false, 'error' => 'Invalid JSON body'], 400);
}

// Verify CSRF
if (!verify_csrf($input['csrf_token'] ?? '')) {
    json_response(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}

// Validate required fields
$required = [
    'car_id'          => 'Car ID',
    'service_slug'    => 'Service',
    'pickup_date'     => 'Pickup date',
    'pickup_time'     => 'Pickup time',
    'pickup_location' => 'Pickup location',
    'full_name'       => 'Full name',
    'email'           => 'Email',
    'phone'           => 'Phone number',
];
foreach ($required as $field => $label) {
    if (empty(trim((string)($input[$field] ?? '')))) {
        json_response(['success' => false, 'error' => "{$label} is required"], 422);
    }
}

// Validate email
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'error' => 'Invalid email address'], 422);
}

// Parse input
$car_id          = (int)$input['car_id'];
$service_slug    = trim($input['service_slug']);
$pickup_date     = trim($input['pickup_date']);
$pickup_time     = trim($input['pickup_time']);
$return_date     = trim($input['return_date'] ?? '') ?: null;
$return_time     = trim($input['return_time'] ?? '') ?: null;
$pickup_location = trim($input['pickup_location']);
$dropoff_location= trim($input['dropoff_location'] ?? '');
$num_passengers  = max(1, (int)($input['num_passengers'] ?? 1));
$flight_number   = trim($input['flight_number'] ?? '');
$special_req     = trim($input['special_requests'] ?? '');
$full_name       = trim($input['full_name']);
$email           = strtolower(trim($input['email']));
$phone           = trim($input['phone']);
$id_number       = trim($input['id_number'] ?? '');

// Validate date
if ($pickup_date < date('Y-m-d')) {
    json_response(['success' => false, 'error' => 'Pickup date cannot be in the past'], 422);
}
if ($return_date && $return_date < $pickup_date) {
    json_response(['success' => false, 'error' => 'Return date must be on or after pickup date'], 422);
}

$db = get_db();

// Get service ID
$svc_stmt = $db->prepare('SELECT id FROM services WHERE slug = ? AND is_active = 1');
$svc_stmt->execute([$service_slug]);
$service_id = $svc_stmt->fetchColumn();
if (!$service_id) {
    json_response(['success' => false, 'error' => 'Invalid service selected'], 422);
}

// Calculate days and price
$num_days      = $return_date ? max(1, (int)((strtotime($return_date) - strtotime($pickup_date)) / 86400) + 1) : 1;
$car_stmt      = $db->prepare('SELECT price_per_day, price_airport, price_wedding, make, model FROM cars WHERE id = ?');
$car_stmt->execute([$car_id]);
$car           = $car_stmt->fetch();
if (!$car) {
    json_response(['success' => false, 'error' => 'Car not found'], 404);
}

$price_per_day = match($service_slug) {
    'airport-transfer' => (float)($car['price_airport'] ?: $car['price_per_day']),
    'wedding'          => (float)($car['price_wedding']  ?: $car['price_per_day']),
    default            => (float)$car['price_per_day'],
};

$base_amount  = $price_per_day * $num_days;
$total_amount = $base_amount;

// Transaction-safe booking creation
$db->beginTransaction();
try {
    // Lock car and recheck availability
    $lock = $db->prepare('SELECT id, status FROM cars WHERE id = ? FOR UPDATE');
    $lock->execute([$car_id]);
    $locked_car = $lock->fetch();

    if (!$locked_car || in_array($locked_car['status'], ['inactive', 'maintenance'])) {
        $db->rollBack();
        json_response(['success' => false, 'error' => 'This car is not available'], 409);
    }

    // Check date overlap
    if ($return_date) {
        $overlap = $db->prepare('
            SELECT COUNT(*) FROM bookings
            WHERE car_id = ?
              AND status IN ("confirmed","active")
              AND pickup_date <= ?
              AND (return_date IS NULL OR return_date >= ?)
        ');
        $overlap->execute([$car_id, $return_date, $pickup_date]);
    } else {
        $overlap = $db->prepare('
            SELECT COUNT(*) FROM bookings
            WHERE car_id = ?
              AND status IN ("confirmed","active")
              AND pickup_date = ?
        ');
        $overlap->execute([$car_id, $pickup_date]);
    }

    if ((int)$overlap->fetchColumn() > 0) {
        $db->rollBack();
        json_response(['success' => false, 'error' => 'Sorry, this car is no longer available for your selected dates. Please choose different dates.'], 409);
    }

    // Upsert customer (find existing by email+phone or create new)
    $cust_stmt = $db->prepare('SELECT id FROM customers WHERE email = ? AND phone = ? LIMIT 1');
    $cust_stmt->execute([$email, $phone]);
    $customer_id = $cust_stmt->fetchColumn();

    if (!$customer_id) {
        $ins_cust = $db->prepare('
            INSERT INTO customers (full_name, email, phone, id_number)
            VALUES (?, ?, ?, ?)
        ');
        $ins_cust->execute([$full_name, $email, $phone, $id_number]);
        $customer_id = (int)$db->lastInsertId();
    }

    // Generate booking reference
    $booking_ref = generate_booking_ref();

    // Insert booking
    $ins_book = $db->prepare('
        INSERT INTO bookings
          (booking_ref, car_id, customer_id, service_id,
           pickup_location, dropoff_location, pickup_date, pickup_time,
           return_date, return_time, num_days, num_passengers,
           flight_number, special_requests,
           base_amount, extras_amount, discount_amount, total_amount, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,0,?,?)
    ');
    $ins_book->execute([
        $booking_ref, $car_id, $customer_id, $service_id,
        $pickup_location, $dropoff_location, $pickup_date, $pickup_time,
        $return_date, $return_time, $num_days, $num_passengers,
        $flight_number, $special_req,
        $base_amount, $total_amount, 'pending',
    ]);
    $booking_id = (int)$db->lastInsertId();

    $db->commit();

    json_response([
        'success'     => true,
        'booking_ref' => $booking_ref,
        'booking_id'  => $booking_id,
        'total'       => $total_amount,
        'total_fmt'   => format_kes($total_amount),
        'num_days'    => $num_days,
        'car_name'    => $car['make'] . ' ' . $car['model'],
        'redirect'    => '/booking.php?step=payment&ref=' . urlencode($booking_ref),
    ]);

} catch (Throwable $e) {
    $db->rollBack();
    json_response(['success' => false, 'error' => 'Booking failed. Please try again.'], 500);
}
