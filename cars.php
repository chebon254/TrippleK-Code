<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title       = 'Browse Cars';
$page_description = 'Filter and browse our full fleet of hire cars in Kenya — sedans, SUVs, executive and more.';

$db = get_db();

// Filters from URL
$f_category    = trim($_GET['category']    ?? '');
$f_transmission= trim($_GET['transmission']?? '');
$f_min_price   = (float)($_GET['min_price']  ?? 0);
$f_max_price   = (float)($_GET['max_price']  ?? 0);
$f_passengers  = (int)($_GET['passengers']   ?? 0);
$f_has_ac      = !empty($_GET['has_ac'])   ? 1 : null;
$f_transmission= in_array($f_transmission, ['automatic','manual']) ? $f_transmission : '';
$f_date_from   = trim($_GET['date_from'] ?? '');
$f_date_to     = trim($_GET['date_to']   ?? '');
$f_sort        = in_array($_GET['sort'] ?? '', ['price_asc','price_desc','name_asc']) ? $_GET['sort'] : 'price_asc';
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 12;

$categories  = $db->query('SELECT slug, name FROM car_categories ORDER BY sort_order')->fetchAll();
$max_price_db = (int)ceil((float)$db->query('SELECT COALESCE(MAX(price_per_day),50000) FROM cars')->fetchColumn() / 1000) * 1000;

// Build query
$where  = ['c.status = ?'];
$params = ['available'];

if ($f_category) {
    $where[]  = 'cat.slug = ?';
    $params[] = $f_category;
}
if ($f_transmission) {
    $where[]  = 'c.transmission = ?';
    $params[] = $f_transmission;
}
if ($f_min_price > 0) {
    $where[]  = 'c.price_per_day >= ?';
    $params[] = $f_min_price;
}
if ($f_max_price > 0) {
    $where[]  = 'c.price_per_day <= ?';
    $params[] = $f_max_price;
}
if ($f_passengers > 0) {
    $where[]  = 'c.passenger_capacity >= ?';
    $params[] = $f_passengers;
}
if ($f_has_ac) {
    $where[] = 'c.has_ac = 1';
}
if ($f_date_from && $f_date_to) {
    $where[]  = 'c.id NOT IN (SELECT car_id FROM bookings WHERE status IN (\'confirmed\',\'active\') AND pickup_date <= ? AND (return_date IS NULL OR return_date >= ?))';
    $params[] = $f_date_to;
    $params[] = $f_date_from;
}

$sort_map = ['price_asc'=>'c.price_per_day ASC','price_desc'=>'c.price_per_day DESC','name_asc'=>'c.make ASC, c.model ASC'];
$order_by = $sort_map[$f_sort];

$base_sql = "FROM cars c JOIN car_categories cat ON c.category_id = cat.id WHERE " . implode(' AND ', $where);

$total_cars = (int)$db->prepare("SELECT COUNT(*) $base_sql")->execute($params) ? $db->prepare("SELECT COUNT(*) $base_sql")->execute($params) : 0;
$count_stmt = $db->prepare("SELECT COUNT(*) $base_sql");
$count_stmt->execute($params);
$total_cars  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_cars / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$cars_stmt = $db->prepare("SELECT c.id, c.make, c.model, c.year, c.transmission, c.fuel_type,
       c.passenger_capacity, c.luggage_capacity, c.price_per_day, c.has_ac,
       c.thumbnail_path, cat.name AS category_name, cat.slug AS category_slug
$base_sql ORDER BY $order_by LIMIT $per_page OFFSET $offset");
$cars_stmt->execute($params);
$cars = $cars_stmt->fetchAll();

// Build page URL helper
function page_url(array $extra = []): string {
    $params = array_merge($_GET, $extra);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null && $v !== '0');
    return '/cars.php?' . http_build_query($params);
}

require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow">

  <!-- Breadcrumb / page header -->
  <section class="pt-20 md:pt-25">
    <div class="bg-dark-1 py-10">
      <div class="container">
        <div class="row y-gap-5">
          <div>
            <h1 class="text-30 fw-600 text-white">Car Hire in Kenya</h1>
            <div class="text-white/70 mt-2">
              <a href="/index.php" class="hover:text-white">Home</a>
              <span class="mx-2">/</span>
              <span>Car Hire</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Listing + Sidebar -->
  <section class="py-12">
    <div class="container">
      <div class="flex flex-col gap-8 lg:flex-row">

        <!-- ===== Sidebar ===== -->
        <aside class="lg:w-[300px]">
          <form method="GET" action="/cars.php">
            <div class="divide-border space-y-7.5 divide-y *:not-last-of-type:pb-7.5">

              <!-- Search bar -->
              <div class="border-border relative z-20 mb-7.5 w-full rounded border p-5">
                <h3 class="text-dark-1 mb-3 text-lg font-medium">Search</h3>
                <div class="flex w-full flex-col items-center gap-5">
                  <div class="grid w-full gap-5">

                    <!-- Pickup date -->
                    <div class="border-border relative w-full rounded border px-5 py-2.5 text-left">
                      <p class="text-15 text-dark-1 mb-1 block font-medium">Pick up date</p>
                      <input type="date" name="date_from" value="<?= h($f_date_from) ?>"
                        min="<?= date('Y-m-d') ?>"
                        class="outline-none text-light-1 w-full" />
                    </div>

                    <!-- Return date -->
                    <div class="border-border relative w-full rounded border px-5 py-2.5 text-left">
                      <p class="text-15 text-dark-1 mb-1 block font-medium">Return date</p>
                      <input type="date" name="date_to" value="<?= h($f_date_to) ?>"
                        min="<?= h($f_date_from ?: date('Y-m-d')) ?>"
                        class="outline-none text-light-1 w-full" />
                    </div>

                    <!-- Passengers -->
                    <div class="border-border relative w-full rounded border px-5 py-2.5 text-left">
                      <p class="text-15 text-dark-1 mb-1 block font-medium">Passengers</p>
                      <select name="passengers" class="outline-none text-light-1 w-full bg-transparent">
                        <option value="">Any</option>
                        <?php foreach ([1,2,3,4,5,6,7,8] as $p): ?>
                        <option value="<?= $p ?>" <?= $f_passengers == $p ? 'selected' : '' ?>><?= $p ?>+</option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="w-full">
                    <button type="submit"
                      class="bg-yellow-1 text-15 hover:bg-dark-1 text-dark-1 flex h-15 w-full cursor-pointer items-center justify-center gap-2.5 rounded px-9 duration-300 hover:text-white lg:inline-flex">
                      <div class="icon-search text-xl"></div>
                      Search
                    </button>
                  </div>
                </div>
              </div>

              <!-- Car Category -->
              <div>
                <h3 class="mb-3 text-lg font-medium">Car Category</h3>
                <div class="text-dark-1 space-y-3 text-sm">
                  <label class="flex items-center justify-between cursor-pointer">
                    <div class="flex items-center">
                      <input type="radio" name="category" value=""
                        class="h-4 w-4 text-blue-600"
                        <?= $f_category === '' ? 'checked' : '' ?>>
                      <span class="ml-3">All Categories</span>
                    </div>
                  </label>
                  <?php foreach ($categories as $cat): ?>
                  <label class="flex items-center justify-between cursor-pointer">
                    <div class="flex items-center">
                      <input type="radio" name="category" value="<?= h($cat['slug']) ?>"
                        class="h-4 w-4 text-blue-600"
                        <?= $f_category === $cat['slug'] ? 'checked' : '' ?>>
                      <span class="ml-3"><?= h($cat['name']) ?></span>
                    </div>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Transmission -->
              <div>
                <h3 class="mb-3 text-lg font-medium">Transmission</h3>
                <div class="text-dark-1 space-y-3 text-sm">
                  <label class="flex items-center cursor-pointer">
                    <input type="radio" name="transmission" value=""
                      class="h-4 w-4 text-blue-600"
                      <?= $f_transmission === '' ? 'checked' : '' ?>>
                    <span class="ml-3">Any</span>
                  </label>
                  <label class="flex items-center cursor-pointer">
                    <input type="radio" name="transmission" value="automatic"
                      class="h-4 w-4 text-blue-600"
                      <?= $f_transmission === 'automatic' ? 'checked' : '' ?>>
                    <span class="ml-3">Automatic</span>
                  </label>
                  <label class="flex items-center cursor-pointer">
                    <input type="radio" name="transmission" value="manual"
                      class="h-4 w-4 text-blue-600"
                      <?= $f_transmission === 'manual' ? 'checked' : '' ?>>
                    <span class="ml-3">Manual</span>
                  </label>
                </div>
              </div>

              <!-- Price Range -->
              <div class="pb-6"
                x-data="rangeSlider({ min: 0, max: <?= $max_price_db ?>, startLower: <?= (int)($f_min_price ?: 0) ?>, startUpper: <?= (int)($f_max_price ?: $max_price_db) ?>, step: 500 })">
                <h5 class="text-dark-1 mb-3 text-lg font-medium">Price / Day (KES)</h5>
                <div class="text-dark-1 mb-5 flex justify-between text-base">
                  <span>KES <span x-text="lower.toLocaleString()"></span></span>
                  <span>KES <span x-text="upper.toLocaleString()"></span></span>
                </div>
                <div x-ref="slider" class="price-slider px-1"></div>
                <input type="hidden" name="min_price" x-bind:value="lower">
                <input type="hidden" name="max_price" x-bind:value="upper">
              </div>

              <!-- Specifications -->
              <div>
                <h3 class="mb-3 text-lg font-medium">Specifications</h3>
                <div class="text-dark-1 space-y-3 text-sm">
                  <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="has_ac" value="1"
                      class="h-4 w-4 text-blue-600"
                      <?= $f_has_ac ? 'checked' : '' ?>>
                    <span class="ml-3">Air Conditioning</span>
                  </label>
                </div>
              </div>

            </div>
          </form>
        </aside>

        <!-- ===== Listings ===== -->
        <div class="flex-1">

          <!-- Results Header -->
          <div class="border-border mb-7.5 flex flex-wrap items-center justify-between gap-4 border-b pb-7.5">
            <div class="text-light-1 text-15">
              <span class="text-dark-1 text-lg font-medium"><?= number_format($total_cars) ?></span>
              car<?= $total_cars !== 1 ? 's' : '' ?> found
              <?php if ($f_category): ?>
              <span class="ml-1">in <strong><?= h(array_column($categories, 'name', 'slug')[$f_category] ?? $f_category) ?></strong></span>
              <?php endif; ?>
            </div>
            <div class="flex items-center gap-3">
              <a href="<?= h(page_url(['sort'=>'price_asc'])) ?>"
                class="text-15 <?= $f_sort === 'price_asc' ? 'bg-blue-1 text-white' : 'bg-blue-1/5 text-blue-1' ?> flex h-10 cursor-pointer items-center gap-2 rounded-full px-5 font-medium duration-300 hover:bg-blue-1 hover:text-white">
                Price ↑
              </a>
              <a href="<?= h(page_url(['sort'=>'price_desc'])) ?>"
                class="text-15 <?= $f_sort === 'price_desc' ? 'bg-blue-1 text-white' : 'bg-blue-1/5 text-blue-1' ?> flex h-10 cursor-pointer items-center gap-2 rounded-full px-5 font-medium duration-300 hover:bg-blue-1 hover:text-white">
                Price ↓
              </a>
            </div>
          </div>

          <?php if (empty($cars)): ?>
          <div class="py-20 text-center">
            <div class="text-light-1 text-lg">No cars match your criteria.</div>
            <a href="/cars.php" class="text-blue-1 mt-4 inline-block hover:underline">Clear all filters</a>
          </div>

          <?php else: ?>

          <!-- Car Grid -->
          <div class="grid grid-cols-1 gap-7.5 md:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($cars as $car):
              $thumb = $car['thumbnail_path']
                ? UPLOAD_URL . ltrim($car['thumbnail_path'], '/')
                : '/assets/src/images/cars/1.png';
            ?>
            <div class="group">
              <a href="/car.php?id=<?= (int)$car['id'] ?>" class="block">
                <div class="relative mb-4 aspect-30/25 overflow-hidden rounded border border-gray-200">
                  <img src="<?= h($thumb) ?>" alt="<?= h($car['make'] . ' ' . $car['model']) ?>"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    onerror="this.src='/assets/src/images/cars/1.png'">
                </div>
                <div>
                  <div class="text-light-1 mb-2 flex items-center text-sm">
                    <span>Nairobi, Kenya</span>
                    <span class="bg-light-1 mx-2 h-1 w-1 rounded-full"></span>
                    <span class="uppercase"><?= h($car['category_name']) ?></span>
                  </div>
                  <h4 class="text-dark-1 mb-2 text-lg leading-tight font-medium">
                    <?= h($car['make'] . ' ' . $car['model']) ?>
                    <span class="text-light-1 text-sm font-normal"><?= (int)$car['year'] ?></span>
                  </h4>
                  <div class="flex flex-wrap items-center gap-x-5 gap-y-2 pt-1">
                    <div class="text-dark-1 flex items-center text-sm">
                      <span class="icon-user-2 mr-2"></span><span><?= (int)$car['passenger_capacity'] ?></span>
                    </div>
                    <div class="text-dark-1 flex items-center text-sm">
                      <span class="icon-luggage mr-2"></span><span><?= (int)$car['luggage_capacity'] ?></span>
                    </div>
                    <div class="text-dark-1 flex items-center text-sm">
                      <span class="icon-transmission mr-2"></span>
                      <span><?= ucfirst(h($car['transmission'])) ?></span>
                    </div>
                    <?php if ($car['has_ac']): ?>
                    <div class="text-dark-1 flex items-center text-sm">
                      <span class="icon-ac mr-2"></span><span>A/C</span>
                    </div>
                    <?php endif; ?>
                  </div>
                  <div class="text-light-1 mt-4">
                    From <span class="text-dark-1 font-medium"><?= format_kes($car['price_per_day']) ?></span> / day
                  </div>
                </div>
              </a>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
          <div class="border-border mt-10 flex justify-center gap-2 border-t pt-10">
            <?php if ($page > 1): ?>
            <a href="<?= h(page_url(['page' => $page - 1])) ?>"
              class="border-border flex h-10 w-10 items-center justify-center rounded border hover:border-blue-1 hover:text-blue-1">
              <i class="icon-chevron-left text-xs"></i>
            </a>
            <?php endif; ?>

            <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
            <a href="<?= h(page_url(['page' => $p])) ?>"
              class="flex h-10 w-10 items-center justify-center rounded text-sm font-medium
                <?= $p === $page ? 'bg-blue-1 text-white' : 'border border-border hover:border-blue-1 hover:text-blue-1' ?>">
              <?= $p ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <a href="<?= h(page_url(['page' => $page + 1])) ?>"
              class="border-border flex h-10 w-10 items-center justify-center rounded border hover:border-blue-1 hover:text-blue-1">
              <i class="icon-chevron-right text-xs"></i>
            </a>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php endif; ?>
        </div><!-- /.flex-1 listings -->

      </div><!-- /.flex flex-col lg:flex-row -->
    </div><!-- /.container -->
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
