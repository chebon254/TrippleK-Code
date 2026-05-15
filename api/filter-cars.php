<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db     = get_db();
$where  = ['c.status = "available"'];
$params = [];

// Category filter
if (!empty($_GET['category'])) {
    $where[]  = 'cat.slug = ?';
    $params[] = $_GET['category'];
}

// Transmission
$allowed_trans = ['automatic', 'manual'];
if (!empty($_GET['transmission']) && in_array($_GET['transmission'], $allowed_trans, true)) {
    $where[]  = 'c.transmission = ?';
    $params[] = $_GET['transmission'];
}

// Fuel type
$allowed_fuel = ['petrol', 'diesel', 'hybrid', 'electric'];
if (!empty($_GET['fuel_type']) && in_array($_GET['fuel_type'], $allowed_fuel, true)) {
    $where[]  = 'c.fuel_type = ?';
    $params[] = $_GET['fuel_type'];
}

// Price range
if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $where[]  = 'c.price_per_day >= ?';
    $params[] = (float)$_GET['min_price'];
}
if (isset($_GET['max_price']) && is_numeric($_GET['max_price']) && (float)$_GET['max_price'] > 0) {
    $where[]  = 'c.price_per_day <= ?';
    $params[] = (float)$_GET['max_price'];
}

// Minimum passengers
if (!empty($_GET['passengers']) && is_numeric($_GET['passengers'])) {
    $where[]  = 'c.passenger_capacity >= ?';
    $params[] = (int)$_GET['passengers'];
}

// Amenities
if (!empty($_GET['has_ac']))         { $where[] = 'c.has_ac = 1'; }
if (!empty($_GET['has_wifi']))       { $where[] = 'c.has_wifi = 1'; }
if (!empty($_GET['has_gps']))        { $where[] = 'c.has_gps = 1'; }
if (!empty($_GET['has_child_seat'])) { $where[] = 'c.has_child_seat = 1'; }

// Availability check: exclude cars with overlapping confirmed/active bookings
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to']   ?? '';
if ($date_from && $date_to && $date_from <= $date_to) {
    $where[] = 'c.id NOT IN (
        SELECT DISTINCT b.car_id FROM bookings b
        WHERE b.status IN ("confirmed","active")
          AND b.pickup_date <= ?
          AND (b.return_date IS NULL OR b.return_date >= ?)
    )';
    $params[] = $date_to;
    $params[] = $date_from;
}

// Service type filter (affects which price to show)
$service = $_GET['service'] ?? '';

$where_sql = implode(' AND ', $where);

// Count
$count_stmt = $db->prepare("SELECT COUNT(*) FROM cars c JOIN car_categories cat ON c.category_id=cat.id WHERE $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

// Pagination
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(24, max(6, (int)($_GET['per_page'] ?? 9)));
$offset  = ($page - 1) * $perPage;

// Fetch
$stmt = $db->prepare("
    SELECT c.id, c.make, c.model, c.year, c.transmission, c.fuel_type,
           c.passenger_capacity, c.luggage_capacity, c.price_per_day,
           c.price_airport, c.price_wedding,
           c.has_ac, c.has_wifi, c.has_gps, c.has_child_seat,
           c.thumbnail_path, c.is_featured, c.description,
           cat.name AS category_name, cat.slug AS category_slug
    FROM cars c
    JOIN car_categories cat ON c.category_id = cat.id
    WHERE $where_sql
    ORDER BY c.is_featured DESC, c.sort_order ASC, c.id DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$cars = $stmt->fetchAll();

// Normalise for JSON output
foreach ($cars as &$car) {
    $car['thumbnail_url']    = $car['thumbnail_path']
        ? UPLOAD_URL . $car['thumbnail_path']
        : APP_URL . '/assets/images/cars/1.png';
    $car['price_formatted']  = format_kes((float)$car['price_per_day']);
    $car['url']              = APP_URL . '/car.php?id=' . $car['id'];
    // Decide which price to highlight based on service
    $car['display_price']    = match($service) {
        'airport-transfer' => $car['price_airport'] ? format_kes((float)$car['price_airport']) : $car['price_formatted'],
        'wedding'          => $car['price_wedding']  ? format_kes((float)$car['price_wedding'])  : $car['price_formatted'],
        default            => $car['price_formatted'],
    };
    $car['display_price_label'] = match($service) {
        'airport-transfer' => 'per trip',
        'wedding'          => 'per package',
        default            => '/ day',
    };
    unset($car['thumbnail_path']); // don't expose server paths
}
unset($car);

json_response([
    'success'     => true,
    'cars'        => $cars,
    'total'       => $total,
    'page'        => $page,
    'per_page'    => $perPage,
    'total_pages' => (int)ceil($total / $perPage),
]);
