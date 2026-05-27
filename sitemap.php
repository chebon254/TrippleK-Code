<?php
/**
 * Dynamic XML Sitemap
 * Covers all public pages + individual car pages.
 * Admin routes excluded.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Use APP_URL for sitemap (always absolute)
$base = rtrim(APP_URL, '/');
$today = date('Y-m-d');

// Fetch all available cars
$db = get_db();
try {
    $cars = $db->query("SELECT id, updated_at FROM cars WHERE status = 'available' ORDER BY id")
               ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable) {
    $cars = [];
}

// Fetch all car categories
try {
    $categories = $db->query("SELECT slug FROM car_categories ORDER BY sort_order, id")
                     ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable) {
    $categories = [];
}

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');  // don't index the sitemap page itself
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

  <!-- ── Static pages ── -->
  <url>
    <loc><?= $base ?>/</loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
    <lastmod><?= $today ?></lastmod>
  </url>

  <url>
    <loc><?= $base ?>/cars</loc>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
    <lastmod><?= $today ?></lastmod>
  </url>

  <url>
    <loc><?= $base ?>/about</loc>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
    <lastmod><?= $today ?></lastmod>
  </url>

  <url>
    <loc><?= $base ?>/contact</loc>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
    <lastmod><?= $today ?></lastmod>
  </url>

  <url>
    <loc><?= $base ?>/terms</loc>
    <changefreq>yearly</changefreq>
    <priority>0.3</priority>
    <lastmod><?= $today ?></lastmod>
  </url>

  <!-- ── Category filter pages ── -->
<?php foreach ($categories as $cat): ?>
  <url>
    <loc><?= $base ?>/cars?category=<?= urlencode($cat['slug']) ?></loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
    <lastmod><?= $today ?></lastmod>
  </url>
<?php endforeach; ?>

  <!-- ── Individual car pages ── -->
<?php foreach ($cars as $car):
    $lastmod = !empty($car['updated_at']) ? date('Y-m-d', strtotime($car['updated_at'])) : $today;
?>
  <url>
    <loc><?= $base ?>/car?id=<?= (int)$car['id'] ?></loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
    <lastmod><?= $lastmod ?></lastmod>
  </url>
<?php endforeach; ?>

</urlset>
