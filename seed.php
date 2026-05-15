<?php
/**
 * One-time seed script — run once then DELETE this file.
 * Access: http://tripplek.local:10008/seed.php
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$db = get_db();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$log = [];

// ── 1. Fix admin password ──────────────────────────────────────────────────
$hash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $db->prepare("INSERT INTO admin (name, email, password_hash) VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), name = VALUES(name)");
$stmt->execute(['Admin', 'admin@tripplek.co.ke', $hash]);
$log[] = '✓ Admin password set → Admin@1234';

// ── 2. Services ────────────────────────────────────────────────────────────
$services = [
    ['car-hire',        'Car Hire',         'Daily and weekly self-drive car rentals across Kenya.',           1],
    ['airport-transfer','Airport Transfer',  'Reliable pickup and drop-off at JKIA, Wilson, and other airports.',2],
    ['chauffeur',       'Chauffeur Service', 'Professional driver-guided transport for business and leisure.',  3],
    ['wedding',         'Wedding Packages',  'Elegant vehicles for your special day — decorated on request.',  4],
];
$svc_stmt = $db->prepare("INSERT IGNORE INTO services (slug, name, description, sort_order, is_active) VALUES (?,?,?,?,1)");
foreach ($services as $s) {
    $svc_stmt->execute($s);
}
$log[] = '✓ Services seeded (' . count($services) . ')';

// ── 3. Car categories ──────────────────────────────────────────────────────
$cats = [
    ['saloon',    'Saloon / Sedan',  'Comfortable sedans ideal for city and inter-county travel.'],
    ['suv',       'SUV / 4x4',       'Spacious 4x4s perfect for safaris and rough terrain.'],
    ['executive', 'Executive',       'Premium vehicles for business travel and VIP clients.'],
    ['van',       'Van / Minibus',   'High-capacity vans for groups, tours, and airport transfers.'],
];
$cat_stmt = $db->prepare("INSERT IGNORE INTO car_categories (slug, name, description) VALUES (?,?,?)");
foreach ($cats as $c) {
    $cat_stmt->execute($c);
}
$log[] = '✓ Car categories seeded (' . count($cats) . ')';

// fetch category IDs
$cat_ids = $db->query("SELECT slug, id FROM car_categories")->fetchAll(PDO::FETCH_KEY_PAIR);

// ── 4. Dummy Cars ──────────────────────────────────────────────────────────
$cars = [
    // Saloon
    [
        'category' => 'saloon', 'make' => 'Toyota', 'model' => 'Axio',
        'year' => 2021, 'registration_number' => 'KDA 123A',
        'color' => 'Silver', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
        'passenger_capacity' => 4, 'luggage_capacity' => 2,
        'price_per_day' => 3500,
        'has_ac' => 1, 'is_featured' => 1,
        'description' => 'Well-maintained Toyota Axio, ideal for business and city travel. Fuel-efficient and comfortable.',
    ],
    [
        'category' => 'saloon', 'make' => 'Toyota', 'model' => 'Premio',
        'year' => 2020, 'registration_number' => 'KDB 456B',
        'color' => 'White', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
        'passenger_capacity' => 4, 'luggage_capacity' => 2,
        'price_per_day' => 4000,
        'has_ac' => 1, 'is_featured' => 1,
        'description' => 'Spacious and reliable Toyota Premio. Perfect for long-distance drives and corporate use.',
    ],
    [
        'category' => 'saloon', 'make' => 'Nissan', 'model' => 'Tiida',
        'year' => 2019, 'registration_number' => 'KDC 789C',
        'color' => 'Blue', 'transmission' => 'manual', 'fuel_type' => 'petrol',
        'passenger_capacity' => 4, 'luggage_capacity' => 2,
        'price_per_day' => 3000,
        'has_ac' => 1, 'is_featured' => 0,
        'description' => 'Affordable Nissan Tiida — great fuel economy, easy to drive in city traffic.',
    ],
    // SUV
    [
        'category' => 'suv', 'make' => 'Toyota', 'model' => 'Land Cruiser Prado',
        'year' => 2022, 'registration_number' => 'KDD 321D',
        'color' => 'Black', 'transmission' => 'automatic', 'fuel_type' => 'diesel',
        'passenger_capacity' => 7, 'luggage_capacity' => 4,
        'price_per_day' => 12000,
        'has_ac' => 1, 'is_featured' => 1,
        'description' => 'Premium Land Cruiser Prado — the ultimate safari and off-road vehicle. 4WD, leather seats, sun roof.',
    ],
    [
        'category' => 'suv', 'make' => 'Toyota', 'model' => 'RAV4',
        'year' => 2021, 'registration_number' => 'KDE 654E',
        'color' => 'White', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
        'passenger_capacity' => 5, 'luggage_capacity' => 3,
        'price_per_day' => 7500,
        'has_ac' => 1, 'is_featured' => 1,
        'description' => 'Versatile Toyota RAV4 — perfect for both city drives and weekend getaways.',
    ],
    [
        'category' => 'suv', 'make' => 'Mitsubishi', 'model' => 'Pajero',
        'year' => 2020, 'registration_number' => 'KDF 987F',
        'color' => 'Grey', 'transmission' => 'automatic', 'fuel_type' => 'diesel',
        'passenger_capacity' => 7, 'luggage_capacity' => 4,
        'price_per_day' => 9000,
        'has_ac' => 1, 'is_featured' => 0,
        'description' => 'Powerful Mitsubishi Pajero with full 4WD. Handles Kenya\'s toughest roads with ease.',
    ],
    // Executive
    [
        'category' => 'executive', 'make' => 'Mercedes-Benz', 'model' => 'E-Class',
        'year' => 2022, 'registration_number' => 'KDG 111G',
        'color' => 'Black', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
        'passenger_capacity' => 4, 'luggage_capacity' => 2,
        'price_per_day' => 18000,
        'has_ac' => 1, 'is_featured' => 1,
        'description' => 'Luxurious Mercedes-Benz E-Class — the perfect vehicle for corporate executives and VIP guests.',
    ],
    [
        'category' => 'executive', 'make' => 'BMW', 'model' => '5 Series',
        'year' => 2021, 'registration_number' => 'KDH 222H',
        'color' => 'White', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
        'passenger_capacity' => 4, 'luggage_capacity' => 2,
        'price_per_day' => 16000,
        'has_ac' => 1, 'is_featured' => 0,
        'description' => 'Elegant BMW 5 Series with premium interior. Ideal for business meetings and special occasions.',
    ],
    // Van
    [
        'category' => 'van', 'make' => 'Toyota', 'model' => 'Hiace',
        'year' => 2021, 'registration_number' => 'KDI 333I',
        'color' => 'White', 'transmission' => 'manual', 'fuel_type' => 'diesel',
        'passenger_capacity' => 14, 'luggage_capacity' => 6,
        'price_per_day' => 8000,
        'has_ac' => 1, 'is_featured' => 1,
        'description' => 'Spacious Toyota Hiace — perfect for group airport transfers, tours, and corporate shuttles.',
    ],
    [
        'category' => 'van', 'make' => 'Toyota', 'model' => 'Noah',
        'year' => 2020, 'registration_number' => 'KDJ 444J',
        'color' => 'Silver', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
        'passenger_capacity' => 8, 'luggage_capacity' => 3,
        'price_per_day' => 6000,
        'has_ac' => 1, 'is_featured' => 0,
        'description' => 'Comfortable Toyota Noah minivan — ideal for family trips and small group transfers.',
    ],
];

$car_stmt = $db->prepare("
    INSERT IGNORE INTO cars
        (category_id, make, model, year, registration_number, color,
         transmission, fuel_type, passenger_capacity, luggage_capacity,
         price_per_day, has_ac, is_featured, status, description)
    VALUES
        (:category_id, :make, :model, :year, :registration_number, :color,
         :transmission, :fuel_type, :passenger_capacity, :luggage_capacity,
         :price_per_day, :has_ac, :is_featured, 'available', :description)
");

$inserted = 0;
foreach ($cars as $car) {
    $car['category_id'] = $cat_ids[$car['category']] ?? 1;
    unset($car['category']);
    $car_stmt->execute($car);
    $inserted++;
}
$log[] = "✓ Cars seeded ({$inserted})";

// ── 5. Settings defaults ───────────────────────────────────────────────────
$defaults = [
    ['company_name',    'Tripple K Car Hire'],
    ['company_phone',   '+254 700 000 000'],
    ['company_email',   'info@tripplek.co.ke'],
    ['company_whatsapp','+254700000000'],
    ['company_address', 'Nairobi, Kenya'],
    ['company_website', 'https://tripplek.co.ke'],
    ['maps_embed_url',  'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63820.78675869162!2d36.798130034814456!3d-1.2950571305451974!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f1172d84d49a7%3A0xf7cf0254b297924c!2sNairobi!5e0!3m2!1sen!2ske!4v1778769785399!5m2!1sen!2ske'],
];
$set_stmt = $db->prepare("INSERT IGNORE INTO settings (setting_key, setting_val) VALUES (?,?)");
foreach ($defaults as $s) {
    $set_stmt->execute($s);
}
$log[] = '✓ Settings defaults seeded';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Seed Complete</title>
  <style>
    body{font-family:system-ui,sans-serif;max-width:540px;margin:60px auto;padding:0 20px;background:#f9fafb}
    h1{color:#1e3a5f;font-size:1.5rem}
    ul{list-style:none;padding:0}
    li{padding:8px 12px;margin:6px 0;background:#fff;border-left:4px solid #22c55e;border-radius:4px;font-size:.95rem}
    .actions{margin-top:24px;display:flex;gap:12px;flex-wrap:wrap}
    a{display:inline-block;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:500;font-size:.9rem}
    .btn-primary{background:#3b5bdb;color:#fff}
    .btn-secondary{background:#fff;color:#1e3a5f;border:1px solid #ddd}
    .warn{margin-top:20px;padding:12px;background:#fef9c3;border:1px solid #fde047;border-radius:6px;font-size:.85rem;color:#713f12}
  </style>
</head>
<body>
  <h1>Seed Complete</h1>
  <ul>
    <?php foreach ($log as $line): ?>
    <li><?= htmlspecialchars($line) ?></li>
    <?php endforeach; ?>
  </ul>
  <div class="warn">⚠ Delete <strong>seed.php</strong> after logging in — it resets the admin password on every run.</div>
  <div class="actions">
    <a href="/admin/login.php" class="btn-primary">Go to Admin Login</a>
    <a href="/index.php" class="btn-secondary">View Website</a>
  </div>
</body>
</html>
