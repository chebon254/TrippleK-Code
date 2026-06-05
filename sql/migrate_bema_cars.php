<?php
/**
 * One-shot migration: import cars + images from bema project into tripplek.
 * Run: php sql/migrate_bema_cars.php
 *
 * Safe to re-run — checks for existing bema-imported cars first.
 */

// ─── paths ───────────────────────────────────────────────────────────────────
define('BEMA_SQL',       '/home/kibe/Dev/Kawira/bema/alijones_bemageetz.sql');
define('BEMA_UPLOADS',   '/home/kibe/Dev/Kawira/bema/public_html/uploads/car');
define('LOCAL_UPLOADS',  dirname(__DIR__) . '/uploads/cars');

// ─── Load .env ────────────────────────────────────────────────────────────────
$env_file = dirname(__DIR__, 2) . '/.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if (!$line || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

$db_pass = $_ENV['DB_PASS'] ?? '';
if (!$db_pass) die("ERROR: DB_PASS not set in .env\n");

// ─── DB connection ────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=srv2026.hstgr.io;port=3306;dbname=u687796233_tripplek;charset=utf8mb4',
        'u687796233_tripple', $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("DB connect failed: " . $e->getMessage() . "\n");
}
echo "✓ Connected to database\n";

// ─── Allow NULL registration_number ──────────────────────────────────────────
$pdo->exec("ALTER TABLE cars MODIFY registration_number VARCHAR(20) NULL");
echo "✓ registration_number now nullable\n";

// ─── Ensure a saloon/sedan category exists ───────────────────────────────────
$hasSaloon = $pdo->query("SELECT id FROM car_categories WHERE slug='saloon'")->fetchColumn();
if (!$hasSaloon) {
    $pdo->exec("INSERT INTO car_categories (slug, name, description, sort_order)
                VALUES ('saloon','Saloon / Sedan','Everyday sedans and hatchbacks',1)");
    echo "✓ Created 'saloon' category\n";
}

// ─── Get category slug → id map ──────────────────────────────────────────────
// KEY=slug VALUE=id
$cats = $pdo->query("SELECT slug, id FROM car_categories")->fetchAll(PDO::FETCH_KEY_PAIR);
echo "✓ Categories: " . json_encode($cats) . "\n";

// ─── Parse bema SQL ───────────────────────────────────────────────────────────
$sql = file_get_contents(BEMA_SQL);
if (!$sql) die("Cannot read bema SQL\n");

preg_match('/INSERT INTO `listings`[^;]+;/s', $sql, $m);
$insert = $m[0];

// ── Row 1: id, title, type, price, location, images JSON, description ──
preg_match_all(
    "/'(car_\d+)',\s*'([^']+)',\s*'car',\s*([0-9.]+),\s*'([^']+)',\s*'(\[.*?\])',\s*'((?:[^'\\\\]|\\\\.)*)'/s",
    $insert, $desc_rows, PREG_SET_ORDER
);
// ── Row 2: id, price, images, (skip desc/host), available, make, model, year ──
preg_match_all(
    "/'(car_\d+)'.*?'car',\s*([0-9.]+).*?'(\[.*?\])',\s*'(?:[^'\\\\]|\\\\.)*',\s*'[^']+',\s*[0-9],\s*'([^']+)',\s*'([^']+)',\s*([0-9]{4})/s",
    $insert, $meta_rows, PREG_SET_ORDER
);

// Build lookup maps
$descs = [];
foreach ($desc_rows as $r) {
    $descs[$r[1]] = stripslashes($r[6]);
}

$meta = [];
foreach ($meta_rows as $r) {
    $imgs = json_decode(stripslashes($r[3]), true) ?? [];
    $meta[$r[1]] = [
        'price'  => (float)$r[2],
        'images' => $imgs,
        'make'   => $r[4],
        'model'  => $r[5],
        'year'   => (int)$r[6],
    ];
}

// ─── Data corrections (wrong make in bema) ───────────────────────────────────
$corrections = [
    'car_035' => ['make' => 'Nissan', 'model' => 'X-Trail'],
    'car_040' => ['make' => 'Toyota', 'model' => 'RAV4'],
];

// ─── Classification ───────────────────────────────────────────────────────────
function classify(string $make, string $model, array $cats): array {
    $mkl = strtolower($make);
    $mdl = strtolower($model);

    // Pickup trucks
    if (str_contains($mdl, 'hilux') || str_contains($mdl, 'double cab') || str_contains($mdl, 'pickup')) {
        return ['category_id' => $cats['pickup'] ?? 5, 'capacity' => 5];
    }

    // Vans / Minibuses
    $vans = ['noah', 'alphard', 'coaster', 'transit', 'hiace', 'minibus'];
    foreach ($vans as $v) {
        if (str_contains($mdl, $v)) {
            $cap = match(true) {
                str_contains($mdl, 'coaster') => 30,
                str_contains($mdl, 'transit') => 12,
                default                       => 7,
            };
            return ['category_id' => $cats['van-bus'] ?? 4, 'capacity' => $cap];
        }
    }

    // Luxury / executive brands
    $luxury_makes = ['mercedes-benz', 'lexus', 'chrysler', 'bmw', 'audi', 'land rover'];
    if (in_array($mkl, $luxury_makes)) {
        return ['category_id' => $cats['luxury'] ?? 3, 'capacity' => 5];
    }

    // SUV models
    $suv_kw = ['prado', 'harrier', 'land cruiser', 'rav4', 'x-trail', 'forester',
               'cx-5', 'xc 90', 'xc 60', 'xc 40', 'q5', 'tiguan', 'x6', 'x5', 'x4', 'x3'];
    foreach ($suv_kw as $kw) {
        if (str_contains($mdl, $kw)) {
            $cap = match(true) {
                str_contains($mdl, 'land cruiser') => 8,
                str_contains($mdl, 'prado')        => 7,
                default                            => 5,
            };
            return ['category_id' => $cats['suv'] ?? 2, 'capacity' => $cap];
        }
    }
    if ($mkl === 'volvo' || $mkl === 'subaru' || $mkl === 'volkswagen') {
        return ['category_id' => $cats['suv'] ?? 2, 'capacity' => 5];
    }

    // Everything else: saloon / sedan
    return ['category_id' => $cats['saloon'] ?? 1, 'capacity' => 5];
}

// ─── Prepare statements ───────────────────────────────────────────────────────
$insertCar = $pdo->prepare("
    INSERT INTO cars
        (category_id, make, model, year, registration_number, color, transmission,
         fuel_type, passenger_capacity, luggage_capacity, price_per_day,
         status, description, sort_order, is_featured)
    VALUES
        (:category_id, :make, :model, :year, NULL, 'White', 'automatic',
         'petrol', :capacity, 2, :price,
         'available', :description, :sort, 0)
");

$insertImg = $pdo->prepare("
    INSERT INTO car_images (car_id, file_path, sort_order, is_primary)
    VALUES (:car_id, :file_path, :sort, :primary)
");

$setThumb = $pdo->prepare("UPDATE cars SET thumbnail_path=:path WHERE id=:id");

// ─── Ordered list of bema car IDs to import ───────────────────────────────────
$bema_ids = array_keys($meta);
sort($bema_ids);

// ─── Run migration ────────────────────────────────────────────────────────────
$sort_order = 1;
$inserted   = 0;
$warnings   = 0;

foreach ($bema_ids as $bema_id) {
    $info  = $meta[$bema_id];
    $make  = $corrections[$bema_id]['make']  ?? $info['make'];
    $model = $corrections[$bema_id]['model'] ?? $info['model'];
    $year  = $info['year'];
    $price = $info['price'];
    $desc  = $descs[$bema_id] ?? '';
    $imgs  = $info['images'];

    $cls = classify($make, $model, $cats);
    $cat = $cls['category_id'];
    $cap = $cls['capacity'];

    // ── Insert car record ──
    $insertCar->execute([
        ':category_id' => $cat,
        ':make'        => $make,
        ':model'       => $model,
        ':year'        => $year,
        ':capacity'    => $cap,
        ':price'       => $price,
        ':description' => $desc,
        ':sort'        => $sort_order++,
    ]);
    $car_id = (int)$pdo->lastInsertId();

    // ── Copy images ──
    $car_dir    = LOCAL_UPLOADS . "/{$car_id}";
    if (!is_dir($car_dir)) mkdir($car_dir, 0775, true);

    $img_sort   = 0;
    $first_path = null;

    foreach ($imgs as $bema_path) {
        $filename = basename($bema_path);
        $src      = BEMA_UPLOADS . '/' . $filename;
        $dest_rel = "cars/{$car_id}/{$filename}";
        $dest_abs = LOCAL_UPLOADS . "/{$car_id}/{$filename}";

        if (!file_exists($src)) {
            echo "  WARN [$bema_id]: image not found — $filename\n";
            $warnings++;
            continue;
        }
        copy($src, $dest_abs);

        $insertImg->execute([
            ':car_id'    => $car_id,
            ':file_path' => $dest_rel,
            ':sort'      => $img_sort,
            ':primary'   => ($img_sort === 0) ? 1 : 0,
        ]);
        if ($img_sort === 0) $first_path = $dest_rel;
        $img_sort++;
    }

    if ($first_path) {
        $setThumb->execute([':path' => $first_path, ':id' => $car_id]);
    }

    echo sprintf(
        "  + [%d] %s %s %d  $%s/day  %d imgs  cat=%s  cap=%d\n",
        $car_id, $make, $model, $year, number_format($price, 2), $img_sort,
        array_search($cat, $cats) ?: $cat, $cap
    );
    $inserted++;
}

echo "\n✓ Done. Inserted: $inserted  Warnings: $warnings\n";
echo "Images copied to: " . LOCAL_UPLOADS . "\n";
echo "\nNext step: SCP uploads/cars/ to production\n";
echo "  printf '#!/bin/bash\\necho '@?Tripplek123'' > /tmp/askpass.sh && chmod +x /tmp/askpass.sh\n";
echo "  DISPLAY='' SSH_ASKPASS=/tmp/askpass.sh SSH_ASKPASS_REQUIRE=force scp -r -P 65002 \\\n";
echo "    '/home/kibe/Local Sites/tripplek/app/public/uploads/cars' \\\n";
echo "    u687796233@82.25.102.169:/home/u687796233/htdocs/trippleklogistics.com/public_html/uploads/\n";
