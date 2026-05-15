<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function is_car_available(int $car_id, string $date_from, string $date_to): bool {
    $db   = get_db();
    $stmt = $db->prepare('
        SELECT COUNT(*) FROM bookings
        WHERE car_id = ?
          AND status IN ("confirmed", "active")
          AND pickup_date <= ?
          AND (return_date IS NULL OR return_date >= ?)
    ');
    $stmt->execute([$car_id, $date_to, $date_from]);
    return (int)$stmt->fetchColumn() === 0;
}

function calculate_booking_total(float $price_per_day, int $num_days, array $extras = []): array {
    $base    = $price_per_day * max(1, $num_days);
    $extras_total = array_sum(array_column($extras, 'amount'));
    return [
        'base_amount'   => $base,
        'extras_amount' => $extras_total,
        'total_amount'  => $base + $extras_total,
    ];
}

function get_booking_by_ref(string $ref): ?array {
    $db   = get_db();
    $stmt = $db->prepare('
        SELECT b.*,
               cu.full_name, cu.email, cu.phone, cu.id_number, cu.address,
               c.make, c.model, c.year, c.registration_number, c.transmission,
               c.passenger_capacity, c.thumbnail_path,
               cat.name AS category_name,
               s.name AS service_name, s.slug AS service_slug,
               p.amount AS paid_amount, p.payment_method, p.gateway_ref, p.paid_at,
               p.status AS payment_status
        FROM bookings b
        JOIN customers cu  ON b.customer_id = cu.id
        JOIN cars c        ON b.car_id = c.id
        JOIN car_categories cat ON c.category_id = cat.id
        JOIN services s    ON b.service_id = s.id
        LEFT JOIN payments p ON p.booking_id = b.id AND p.status = "completed"
        WHERE b.booking_ref = ?
        LIMIT 1
    ');
    $stmt->execute([$ref]);
    return $stmt->fetch() ?: null;
}

function get_booking_by_id(int $id): ?array {
    $db   = get_db();
    $stmt = $db->prepare('
        SELECT b.*,
               cu.full_name, cu.email, cu.phone, cu.id_number, cu.address,
               c.make, c.model, c.year, c.registration_number, c.transmission,
               c.passenger_capacity, c.thumbnail_path,
               cat.name AS category_name,
               s.name AS service_name,
               p.amount AS paid_amount, p.payment_method, p.gateway_ref, p.paid_at,
               p.status AS payment_status
        FROM bookings b
        JOIN customers cu  ON b.customer_id = cu.id
        JOIN cars c        ON b.car_id = c.id
        JOIN car_categories cat ON c.category_id = cat.id
        JOIN services s    ON b.service_id = s.id
        LEFT JOIN payments p ON p.booking_id = b.id AND p.status = "completed"
        WHERE b.id = ?
        LIMIT 1
    ');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function update_booking_status(int $booking_id, string $status, string $admin_notes = ''): bool {
    $allowed = ['pending', 'confirmed', 'active', 'completed', 'cancelled'];
    if (!in_array($status, $allowed, true)) return false;

    $db   = get_db();
    $stmt = $db->prepare('
        UPDATE bookings SET status = ?, admin_notes = ? WHERE id = ?
    ');
    return $stmt->execute([$status, $admin_notes, $booking_id]);
}
