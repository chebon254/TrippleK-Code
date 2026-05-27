<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/image_functions.php';

require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/cars');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    flash('error', 'Invalid request.');
    redirect('/admin/cars');
}

$car_id = (int)($_POST['car_id'] ?? 0);
if (!$car_id) {
    flash('error', 'Invalid car ID.');
    redirect('/admin/cars');
}

$db   = get_db();
$stmt = $db->prepare('SELECT id, make, model FROM cars WHERE id = ?');
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car) {
    flash('error', 'Car not found.');
    redirect('/admin/cars');
}

// Check for existing bookings
$bookings = $db->prepare("SELECT COUNT(*) FROM bookings WHERE car_id = ? AND status IN ('pending','confirmed','active')");
$bookings->execute([$car_id]);
if ((int)$bookings->fetchColumn() > 0) {
    flash('error', "Cannot delete {$car['make']} {$car['model']} — it has active bookings.");
    redirect('/admin/cars');
}

// Delete images from disk
delete_car_images($car_id);

// Delete from database (cascade handles car_images and car_features)
$db->prepare('DELETE FROM cars WHERE id = ?')->execute([$car_id]);

flash('success', "{$car['make']} {$car['model']} deleted successfully.");
redirect('/admin/cars');
