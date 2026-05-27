<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_auth();

$page_title = 'Dashboard';
$db         = get_db();

$total_bookings = (int)$db->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$pending        = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$confirmed      = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$total_cars     = (int)$db->query("SELECT COUNT(*) FROM cars WHERE status != 'inactive'")->fetchColumn();

$this_month = $db->query("
    SELECT COALESCE(SUM(p.amount), 0)
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    WHERE p.status = 'completed'
      AND MONTH(p.paid_at) = MONTH(NOW())
      AND YEAR(p.paid_at) = YEAR(NOW())
")->fetchColumn();

$total_revenue = $db->query("
    SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'
")->fetchColumn();

$recent = $db->query("
    SELECT b.id, b.booking_ref, b.status, b.total_amount, b.pickup_date, b.created_at,
           cu.full_name, cu.phone,
           c.make, c.model
    FROM bookings b
    JOIN customers cu ON b.customer_id = cu.id
    JOIN cars c ON b.car_id = c.id
    ORDER BY b.created_at DESC
    LIMIT 8
")->fetchAll();

require __DIR__ . '/../includes/admin_header.php';
?>

<!-- Page Header -->
<div class="mb-8">
  <h1 class="text-white mb-2 text-3xl font-semibold">Dashboard</h1>
  <p class="text-light-1">Welcome back — here's your business overview.</p>
</div>

<!-- Stats Cards -->
<div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">

  <a href="/admin/bookings" class="rounded bg-dark-3 border border-border p-6 transition hover:shadow-lg">
    <div class="flex items-center justify-between">
      <div>
        <p class="mb-2 font-medium text-light-1">Total Bookings</p>
        <h3 class="text-white mb-1 text-3xl font-semibold"><?= number_format($total_bookings) ?></h3>
        <p class="text-light-1 text-sm">All time</p>
      </div>
      <div class="flex h-15 w-15 items-center justify-center">
        <img src="/assets/images/dashboard/icons/3.svg" alt="Bookings">
      </div>
    </div>
  </a>

  <a href="/admin/bookings?status=pending" class="rounded bg-dark-3 border border-border p-6 transition hover:shadow-lg">
    <div class="flex items-center justify-between">
      <div>
        <p class="mb-2 font-medium text-light-1">Pending</p>
        <h3 class="text-white mb-1 text-3xl font-semibold"><?= number_format($pending) ?></h3>
        <p class="text-light-1 text-sm">Awaiting confirmation</p>
      </div>
      <div class="flex h-15 w-15 items-center justify-center">
        <img src="/assets/images/dashboard/icons/1.svg" alt="Pending">
      </div>
    </div>
  </a>

  <a href="/admin/cars" class="rounded bg-dark-3 border border-border p-6 transition hover:shadow-lg">
    <div class="flex items-center justify-between">
      <div>
        <p class="mb-2 font-medium text-light-1">Active Fleet</p>
        <h3 class="text-white mb-1 text-3xl font-semibold"><?= number_format($total_cars) ?></h3>
        <p class="text-light-1 text-sm">Vehicles available</p>
      </div>
      <div class="flex h-15 w-15 items-center justify-center">
        <img src="/assets/images/dashboard/icons/4.svg" alt="Fleet">
      </div>
    </div>
  </a>

  <a href="/admin/payments" class="rounded bg-dark-3 border border-border p-6 transition hover:shadow-lg">
    <div class="flex items-center justify-between">
      <div>
        <p class="mb-2 font-medium text-light-1">Monthly Revenue</p>
        <h3 class="text-white mb-1 text-2xl font-semibold"><?= format_kes((float)$this_month) ?></h3>
        <p class="text-light-1 text-sm">This month</p>
      </div>
      <div class="flex h-15 w-15 items-center justify-center">
        <img src="/assets/images/dashboard/icons/2.svg" alt="Revenue">
      </div>
    </div>
  </a>

</div>

<!-- Recent Bookings + Revenue Summary -->
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

  <!-- Recent Bookings Table -->
  <div class="rounded bg-dark-3 border border-border p-6 lg:col-span-2">
    <div class="mb-6 flex items-center justify-between">
      <h2 class="text-white text-xl font-semibold">Recent Bookings</h2>
      <a href="/admin/bookings" class="text-blue-1 text-sm font-medium hover:text-blue-700">View All</a>
    </div>

    <div class="overflow-hidden">
      <table class="w-full">
        <thead class="bg-dark-4">
          <tr>
            <th class="text-white px-4 py-3 text-left text-sm font-semibold">Ref</th>
            <th class="text-white px-4 py-3 text-left text-sm font-semibold">Customer</th>
            <th class="text-white px-4 py-3 text-left text-sm font-semibold hidden xl:table-cell">Vehicle</th>
            <th class="text-white px-4 py-3 text-left text-sm font-semibold">Status</th>
            <th class="text-white px-4 py-3 text-right text-sm font-semibold">Amount</th>
          </tr>
        </thead>
        <tbody class="divide-border divide-y divide-dashed">
          <?php if (empty($recent)): ?>
          <tr><td colspan="5" class="px-4 py-10 text-center text-light-1">No bookings yet.</td></tr>
          <?php else: ?>
          <?php foreach ($recent as $b): ?>
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
            <td class="px-4 py-4 text-sm text-light-1 hidden xl:table-cell whitespace-nowrap">
              <?= h($b['make'] . ' ' . $b['model']) ?>
            </td>
            <td class="px-4 py-4">
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= booking_status_class($b['status']) ?>">
                <?= ucfirst($b['status']) ?>
              </span>
            </td>
            <td class="px-4 py-4 text-right text-sm font-medium text-white whitespace-nowrap">
              <?= format_kes($b['total_amount']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Right Sidebar -->
  <div class="space-y-6">

    <!-- Revenue Summary -->
    <div class="rounded bg-dark-3 border border-border p-6">
      <h2 class="text-white mb-5 text-xl font-semibold">Revenue</h2>
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <span class="text-light-1 text-sm">This Month</span>
          <span class="text-white font-semibold"><?= format_kes((float)$this_month) ?></span>
        </div>
        <div class="border-border border-t pt-4 flex items-center justify-between">
          <span class="text-light-1 text-sm">All Time</span>
          <span class="text-white font-semibold"><?= format_kes((float)$total_revenue) ?></span>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="rounded bg-dark-3 border border-border p-6">
      <h2 class="text-white mb-5 text-xl font-semibold">Quick Actions</h2>
      <div class="space-y-2">
        <a href="/admin/add-car"
          class="text-white hover:bg-dark-4 flex items-center gap-3 rounded px-3 py-2.5 text-sm font-medium transition-colors hover:text-blue-1">
          <i class="icon-plus text-base text-light-1"></i> Add New Car
        </a>
        <a href="/admin/bookings?status=pending"
          class="text-white hover:bg-dark-4 flex items-center gap-3 rounded px-3 py-2.5 text-sm font-medium transition-colors hover:text-blue-1">
          <i class="icon-clock text-base text-light-1"></i> Pending Bookings
          <?php if ($pending > 0): ?>
          <span class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-semibold text-white"><?= $pending ?></span>
          <?php endif; ?>
        </a>
        <a href="/admin/payments"
          class="text-white hover:bg-dark-4 flex items-center gap-3 rounded px-3 py-2.5 text-sm font-medium transition-colors hover:text-blue-1">
          <i class="icon-price-label text-base text-light-1"></i> View Payments
        </a>
        <a href="/admin/settings"
          class="text-white hover:bg-dark-4 flex items-center gap-3 rounded px-3 py-2.5 text-sm font-medium transition-colors hover:text-blue-1">
          <i class="icon-shield text-base text-light-1"></i> Settings
        </a>
      </div>
    </div>

  </div>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
