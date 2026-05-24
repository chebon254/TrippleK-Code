<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/image_functions.php';

require_admin_auth();

$page_title = 'Fleet Management';
$db         = get_db();

$filter_cat    = $_GET['category'] ?? '';
$filter_status = $_GET['status']   ?? '';
$search        = trim($_GET['q']   ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 15;
$offset        = ($page - 1) * $per_page;

$where  = ['1=1'];
$params = [];
if ($filter_cat) {
    $where[]  = 'cat.slug = ?';
    $params[] = $filter_cat;
}
if ($filter_status) {
    $where[]  = 'c.status = ?';
    $params[] = $filter_status;
}
if ($search) {
    $where[]  = '(c.make LIKE ? OR c.model LIKE ? OR c.registration_number LIKE ?)';
    $term     = "%{$search}%";
    $params   = array_merge($params, [$term, $term, $term]);
}
$where_sql = implode(' AND ', $where);

$count_stmt = $db->prepare("SELECT COUNT(*) FROM cars c JOIN car_categories cat ON c.category_id=cat.id WHERE $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT c.id, c.make, c.model, c.year, c.registration_number, c.status,
           c.price_per_day, c.transmission, c.passenger_capacity, c.is_featured,
           c.thumbnail_path, cat.name AS category_name
    FROM cars c
    JOIN car_categories cat ON c.category_id = cat.id
    WHERE $where_sql
    ORDER BY c.sort_order ASC, c.id DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$cars = $stmt->fetchAll();

$categories = $db->query('SELECT slug, name FROM car_categories ORDER BY sort_order')->fetchAll();
$pager      = paginate($total, $per_page, $page,
    '/admin/cars.php?page=%d' .
    ($filter_cat    ? "&category=$filter_cat"             : '') .
    ($filter_status ? "&status=$filter_status"            : '') .
    ($search        ? "&q=" . urlencode($search)          : ''));

require __DIR__ . '/../includes/admin_header.php';
?>

<!-- Page Header -->
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="text-white mb-2 text-3xl font-semibold">Fleet Management</h1>
    <p class="text-light-1"><?= $total ?> vehicle<?= $total !== 1 ? 's' : '' ?> in fleet</p>
  </div>
  <a href="/admin/add-car.php"
    class="bg-blue-1 hover:bg-dark-1 inline-flex items-center gap-2 rounded px-5 py-2.5 text-sm font-medium text-white transition">
    <i class="icon-plus text-base"></i> Add Car
  </a>
</div>

<!-- Filters -->
<form method="GET" class="mb-6 rounded bg-dark-3 border border-border p-4">
  <div class="flex flex-wrap gap-3">
    <div class="relative flex-1 min-w-[200px]">
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search make, model, reg..."
        class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 py-2.5 pr-4 pl-10 text-sm outline-none">
      <i class="icon-search text-light-1 absolute top-1/2 left-3 -translate-y-1/2 text-base"></i>
    </div>
    <select name="category" class="border-border focus:border-blue-1 rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
      <option value="<?= h($cat['slug']) ?>" <?= $filter_cat === $cat['slug'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="border-border focus:border-blue-1 rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
      <option value="">All Statuses</option>
      <?php foreach (['available'=>'Available','hired'=>'Hired','maintenance'=>'Maintenance','inactive'=>'Inactive'] as $v=>$l): ?>
      <option value="<?= $v ?>" <?= $filter_status === $v ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="bg-blue-1 hover:bg-dark-1 rounded px-5 py-2.5 text-sm font-medium text-white transition">Filter</button>
    <?php if ($search || $filter_cat || $filter_status): ?>
    <a href="/admin/cars.php" class="border-border rounded border px-4 py-2.5 text-sm text-light-1 transition hover:bg-dark-4">Clear</a>
    <?php endif; ?>
  </div>
</form>

<!-- Cars Table -->
<div class="rounded bg-dark-3 border border-border p-6">
  <div class="overflow-x-auto">
    <table class="w-full border-collapse">
      <thead class="bg-dark-4">
        <tr>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Vehicle</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap hidden md:table-cell">Category</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap hidden lg:table-cell">Reg. No.</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap hidden md:table-cell">Price/Day</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Status</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Action</th>
        </tr>
      </thead>
      <tbody class="divide-border divide-y divide-dashed">
        <?php if (empty($cars)): ?>
        <tr><td colspan="6" class="px-4 py-12 text-center text-light-1">No vehicles found.</td></tr>
        <?php else: ?>
        <?php foreach ($cars as $car): ?>
        <tr class="transition hover:bg-dark-4">
          <td class="px-4 py-4">
            <div class="flex items-center gap-3">
              <?php if ($car['thumbnail_path']): ?>
              <img src="<?= h(UPLOAD_URL . $car['thumbnail_path']) ?>" alt=""
                class="h-12 w-16 flex-shrink-0 rounded object-cover">
              <?php else: ?>
              <div class="h-12 w-16 flex-shrink-0 rounded bg-gray-100 flex items-center justify-center">
                <i class="icon-car text-gray-300 text-xl"></i>
              </div>
              <?php endif; ?>
              <div>
                <div class="text-white text-sm font-medium"><?= h($car['make'] . ' ' . $car['model']) ?></div>
                <div class="text-light-1 text-xs"><?= h($car['year']) ?> · <?= ucfirst($car['transmission']) ?> · <?= $car['passenger_capacity'] ?> seats</div>
              </div>
            </div>
          </td>
          <td class="px-4 py-4 text-sm text-light-1 hidden md:table-cell whitespace-nowrap">
            <?= h($car['category_name']) ?>
          </td>
          <td class="px-4 py-4 font-mono text-xs text-gray-600 hidden lg:table-cell whitespace-nowrap">
            <?= h($car['registration_number']) ?>
          </td>
          <td class="px-4 py-4 text-sm font-semibold text-dark-1 hidden md:table-cell whitespace-nowrap">
            <?= format_kes($car['price_per_day']) ?>
          </td>
          <td class="px-4 py-4 whitespace-nowrap">
            <?php
            $sc = match($car['status']) {
              'available'   => 'bg-green-50 text-green-600',
              'hired'       => 'bg-blue-1/5 text-blue-1',
              'maintenance' => 'bg-yellow-4 text-yellow-3',
              default       => 'bg-gray-100 text-gray-600',
            };
            ?>
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $sc ?>">
              <?= ucfirst($car['status']) ?>
            </span>
          </td>
          <td class="px-4 py-4 whitespace-nowrap">
            <div class="flex items-center gap-2.5">
              <a href="/car.php?id=<?= $car['id'] ?>" target="_blank"
                class="bg-light-2 hover:bg-blue-1 group flex h-9 w-9 items-center justify-center rounded transition"
                title="View public page">
                <i class="icon-arrow-top-right text-light-1 text-base group-hover:text-white"></i>
              </a>
              <a href="/admin/edit-car.php?id=<?= $car['id'] ?>"
                class="bg-light-2 hover:bg-blue-1 group flex h-9 w-9 items-center justify-center rounded transition"
                title="Edit">
                <i class="icon-edit text-light-1 text-base group-hover:text-white"></i>
              </a>
              <form method="POST" action="/admin/delete-car.php" class="inline"
                onsubmit="return confirm('Delete <?= h(addslashes($car['make'] . ' ' . $car['model'])) ?>? This cannot be undone.')">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                <button type="submit"
                  class="bg-light-2 group flex h-9 w-9 items-center justify-center rounded transition hover:bg-red-600"
                  title="Delete">
                  <i class="icon-trash-2 text-light-1 text-base group-hover:text-white"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pager['total_pages'] > 1): ?>
  <div class="border-border mt-8 border-t pt-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <?php if ($pager['has_prev']): ?>
      <a href="<?= $pager['prev_url'] ?>"
        class="text-light-1 hover:bg-blue-1 border-border hover:border-blue-1 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border bg-dark-3 text-xs font-medium duration-300 hover:text-white">
        <i class="icon-chevron-left"></i>
      </a>
      <?php else: ?>
      <span class="text-light-1 border-border flex h-10 w-10 items-center justify-center rounded-full border bg-dark-3 text-xs opacity-40">
        <i class="icon-chevron-left"></i>
      </span>
      <?php endif; ?>

      <p class="text-light-1 text-sm">
        <?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?> of <?= number_format($total) ?>
      </p>

      <?php if ($pager['has_next']): ?>
      <a href="<?= $pager['next_url'] ?>"
        class="text-light-1 hover:bg-blue-1 border-border hover:border-blue-1 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border bg-dark-3 text-xs font-medium duration-300 hover:text-white">
        <i class="icon-chevron-right"></i>
      </a>
      <?php else: ?>
      <span class="text-light-1 border-border flex h-10 w-10 items-center justify-center rounded-full border bg-dark-3 text-xs opacity-40">
        <i class="icon-chevron-right"></i>
      </span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
