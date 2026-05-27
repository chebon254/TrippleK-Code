<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_auth();

$db = get_db();

$search      = trim($_GET['search']    ?? '');
$status_f    = trim($_GET['status']    ?? '');
$date_from   = trim($_GET['date_from'] ?? '');
$date_to     = trim($_GET['date_to']   ?? '');
$per_page    = 15;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($current_page - 1) * $per_page;

$allowed_statuses = ['pending', 'confirmed', 'active', 'completed', 'cancelled'];

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(b.booking_ref LIKE ? OR cu.full_name LIKE ? OR cu.phone LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($status_f && in_array($status_f, $allowed_statuses, true)) {
    $where[]  = 'b.status = ?';
    $params[] = $status_f;
}
if ($date_from) {
    $where[]  = 'b.pickup_date >= ?';
    $params[] = $date_from;
}
if ($date_to) {
    $where[]  = 'b.pickup_date <= ?';
    $params[] = $date_to;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count_stmt = $db->prepare("
    SELECT COUNT(*) FROM bookings b
    JOIN customers cu ON b.customer_id = cu.id
    {$where_sql}
");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pagination = paginate($total, $per_page, $current_page,
    '?page=%d&search=' . urlencode($search) .
    '&status=' . urlencode($status_f) .
    '&date_from=' . urlencode($date_from) .
    '&date_to=' . urlencode($date_to));

$stmt = $db->prepare("
    SELECT b.id, b.booking_ref, b.status, b.pickup_date, b.return_date,
           b.num_days, b.total_amount, b.created_at,
           cu.full_name, cu.phone,
           c.make, c.model,
           s.name AS service_name
    FROM bookings b
    JOIN customers cu ON b.customer_id = cu.id
    JOIN cars c       ON b.car_id = c.id
    JOIN services s   ON b.service_id = s.id
    {$where_sql}
    ORDER BY b.created_at DESC
    LIMIT {$per_page} OFFSET {$offset}
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$stats_raw = $db->query("SELECT status, COUNT(*) as cnt FROM bookings GROUP BY status")->fetchAll();
$stats = [];
foreach ($stats_raw as $row) {
    $stats[$row['status']] = $row['cnt'];
}

$page_title = 'Bookings';
require __DIR__ . '/../includes/admin_header.php';
?>

<!-- Page Header -->
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="text-white mb-2 text-3xl font-semibold">Bookings</h1>
    <p class="text-light-1"><?= number_format($total) ?> booking<?= $total !== 1 ? 's' : '' ?> found</p>
  </div>
</div>

<!-- Status Filter Pills -->
<div class="mb-6 flex flex-wrap gap-2">
  <?php
  $base_url = 'bookings.php?search=' . urlencode($search) . '&date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to);
  ?>
  <a href="/admin/bookings"
    class="rounded-full px-4 py-1.5 text-sm font-medium transition
      <?= !$status_f ? 'bg-dark-1 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
    All <span class="ml-1 opacity-70"><?= array_sum($stats) ?></span>
  </a>
  <?php foreach (['pending'=>'Pending','confirmed'=>'Confirmed','active'=>'Active','completed'=>'Completed','cancelled'=>'Cancelled'] as $s => $l): ?>
  <a href="<?= $base_url ?>&status=<?= $s ?>"
    class="rounded-full px-4 py-1.5 text-sm font-medium transition
      <?= $status_f === $s ? booking_status_class($s) : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
    <?= $l ?> <span class="ml-1 opacity-70"><?= $stats[$s] ?? 0 ?></span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Search + Date Filter -->
<form method="get" class="mb-6 rounded bg-dark-3 border border-border p-4">
  <?php if ($status_f): ?><input type="hidden" name="status" value="<?= h($status_f) ?>"><?php endif; ?>
  <div class="flex flex-wrap gap-3">
    <div class="relative flex-1 min-w-[200px]">
      <input type="text" name="search" value="<?= h($search) ?>"
        placeholder="Search ref, name, phone..."
        class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 py-2.5 pr-4 pl-10 text-sm outline-none">
      <i class="icon-search text-light-1 absolute top-1/2 left-3 -translate-y-1/2 text-base"></i>
    </div>
    <input type="date" name="date_from" value="<?= h($date_from) ?>"
      class="border-border focus:border-blue-1 rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
    <input type="date" name="date_to" value="<?= h($date_to) ?>"
      class="border-border focus:border-blue-1 rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
    <button type="submit"
      class="bg-blue-1 hover:bg-dark-1 rounded px-5 py-2.5 text-sm font-medium text-white transition">
      Search
    </button>
    <?php if ($search || $date_from || $date_to): ?>
    <a href="/admin/bookings<?= $status_f ? '?status=' . $status_f : '' ?>"
      class="border-border rounded border px-4 py-2.5 text-sm text-light-1 transition hover:bg-dark-4">
      Clear
    </a>
    <?php endif; ?>
  </div>
</form>

<!-- Bookings Table -->
<div class="rounded bg-dark-3 border border-border p-6">
  <div class="overflow-x-auto">
    <table class="w-full border-collapse">
      <thead class="bg-dark-4">
        <tr>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Ref</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Customer</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap hidden md:table-cell">Vehicle</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap hidden lg:table-cell">Service</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Pickup</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap hidden md:table-cell">Days</th>
          <th class="text-white px-4 py-3 text-right text-sm font-semibold whitespace-nowrap">Amount</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Status</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Action</th>
        </tr>
      </thead>
      <tbody class="divide-border divide-y divide-dashed">
        <?php if (!$bookings): ?>
        <tr>
          <td colspan="9" class="px-4 py-12 text-center text-light-1">No bookings found.</td>
        </tr>
        <?php else: ?>
        <?php foreach ($bookings as $b): ?>
        <tr class="transition hover:bg-dark-4">
          <td class="px-4 py-4 whitespace-nowrap">
            <a href="/admin/booking-view?id=<?= $b['id'] ?>"
              class="text-blue-1 font-mono text-sm font-medium hover:underline">
              <?= h($b['booking_ref']) ?>
            </a>
          </td>
          <td class="px-4 py-4">
            <div class="text-white text-sm font-medium"><?= h($b['full_name']) ?></div>
            <div class="text-light-1 text-xs"><?= h($b['phone']) ?></div>
          </td>
          <td class="px-4 py-4 text-sm text-light-1 hidden md:table-cell whitespace-nowrap">
            <?= h($b['make'] . ' ' . $b['model']) ?>
          </td>
          <td class="px-4 py-4 text-sm text-gray-500 hidden lg:table-cell whitespace-nowrap">
            <?= h($b['service_name']) ?>
          </td>
          <td class="px-4 py-4 text-sm whitespace-nowrap">
            <?= display_date($b['pickup_date']) ?>
          </td>
          <td class="px-4 py-4 text-sm text-light-1 hidden md:table-cell">
            <?= (int)$b['num_days'] ?>
          </td>
          <td class="px-4 py-4 text-right text-sm font-semibold text-dark-1 whitespace-nowrap">
            <?= format_kes($b['total_amount']) ?>
          </td>
          <td class="px-4 py-4 whitespace-nowrap">
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= booking_status_class($b['status']) ?>">
              <?= ucfirst($b['status']) ?>
            </span>
          </td>
          <td class="px-4 py-4 whitespace-nowrap">
            <div class="flex items-center gap-2">
              <a href="/admin/booking-view?id=<?= $b['id'] ?>"
                class="bg-light-2 hover:bg-blue-1 group flex h-9 w-9 items-center justify-center rounded transition"
                title="View">
                <i class="icon-eye text-light-1 text-base group-hover:text-white"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pagination['total_pages'] > 1): ?>
  <div class="border-border mt-8 border-t pt-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <?php if ($pagination['has_prev']): ?>
      <a href="<?= $pagination['prev_url'] ?>"
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

      <?php if ($pagination['has_next']): ?>
      <a href="<?= $pagination['next_url'] ?>"
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
