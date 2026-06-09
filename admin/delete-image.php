<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/image_functions.php';

require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    json_response(['error' => 'Invalid CSRF token'], 403);
}

$img_id = (int)($_POST['img_id'] ?? 0);
$car_id = (int)($_POST['car_id'] ?? 0);

if (!$img_id || !$car_id) {
    json_response(['error' => 'Missing parameters'], 400);
}

$db   = get_db();
$stmt = $db->prepare('SELECT file_path FROM car_images WHERE id = ? AND car_id = ?');
$stmt->execute([$img_id, $car_id]);
$img  = $stmt->fetch();

if (!$img) {
    json_response(['error' => 'Image not found'], 404);
}

delete_single_image($img['file_path']);
$db->prepare('DELETE FROM car_images WHERE id = ?')->execute([$img_id]);

// Keep thumbnail pointing at first remaining image
$first = $db->prepare('SELECT file_path FROM car_images WHERE car_id = ? ORDER BY sort_order LIMIT 1');
$first->execute([$car_id]);
$thumb = $first->fetchColumn();
$db->prepare('UPDATE cars SET thumbnail_path = ? WHERE id = ?')->execute([$thumb ?: null, $car_id]);

json_response(['success' => true]);
