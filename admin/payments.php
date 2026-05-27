<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_auth();

$db = get_db();

$method_f    = trim($_GET['method'] ?? '');
$status_f    = trim($_GET['status'] ?? '');
$search      = trim($_GET['search'] ?? '');
$per_page    = 20;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($current_page - 1) * $per_page;

$allowed_methods  = ['card', 'mobile_money', 'bank_transfer', 'bank', 'cash'];
$allowed_statuses = ['processing', 'completed', 'failed', 'refunded', 'pending'];

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(b.booking_ref LIKE ? OR cu.full_name LIKE ? OR p.gateway_ref LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($method_f && in_array($method_f, $allowed_methods, true)) {
    $where[]  = 'p.payment_method = ?';
    $params[] = $method_f;
}
if ($status_f && in_array($status_f, $allowed_statuses, true)) {
    $where[]  = 'p.status = ?';
    $params[] = $status_f;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count_stmt = $db->prepare("
    SELECT COUNT(*) FROM payments p
    JOIN bookings b   ON p.booking_id = b.id
    JOIN customers cu ON b.customer_id = cu.id
    {$where_sql}
");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pagination = paginate($total, $per_page, $current_page,
    '?page=%d&method=' . urlencode($method_f) .
    '&status=' . urlencode($status_f) .
    '&search=' . urlencode($search));

$stmt = $db->prepare("
    SELECT p.id, p.payment_method, p.transaction_ref, p.gateway_ref,
           p.amount, p.currency, p.status, p.paid_at, p.created_at,
           b.booking_ref, b.id AS booking_id,
           cu.full_name, cu.phone
    FROM payments p
    JOIN bookings b   ON p.booking_id = b.id
    JOIN customers cu ON b.customer_id = cu.id
    {$where_sql}
    ORDER BY p.id DESC
    LIMIT {$per_page} OFFSET {$offset}
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

$totals = $db->query("
    SELECT COUNT(*) as cnt, COALESCE(SUM(amount),0) as total
    FROM payments WHERE status = 'completed'
")->fetch();

$page_title = 'Payments';
require __DIR__ . '/../includes/admin_header.php';
?>

<!-- Page Header -->
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="text-white mb-2 text-3xl font-semibold">Payments</h1>
    <p class="text-light-1"><?= number_format($total) ?> transaction<?= $total !== 1 ? 's' : '' ?></p>
  </div>
  <div class="rounded bg-dark-3 px-6 py-4 shadow-[0_10px_30px_0_#05103608] text-right">
    <div class="text-light-1 text-xs mb-1">Total Collected</div>
    <div class="text-dark-1 text-xl font-bold"><?= format_kes($totals['total']) ?></div>
    <div class="text-light-1 text-xs mt-1"><?= number_format($totals['cnt']) ?> successful payments</div>
  </div>
</div>

<!-- Filters -->
<form method="get" class="mb-6 rounded bg-dark-3 border border-border p-4">
  <div class="flex flex-wrap gap-3">
    <div class="relative flex-1 min-w-[200px]">
      <input type="text" name="search" value="<?= h($search) ?>"
        placeholder="Booking ref, customer, receipt..."
        class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 py-2.5 pr-4 pl-12 text-sm outline-none">
      <i class="icon-search text-light-1 absolute top-1/2 left-4 -translate-y-1/2 text-base"></i>
    </div>
    <select name="method" class="border-border focus:border-blue-1 rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
      <option value="">All Methods</option>
      <option value="card"          <?= $method_f === 'card'          ? 'selected' : '' ?>>Card (Visa/Mastercard)</option>
      <option value="mobile_money"  <?= $method_f === 'mobile_money'  ? 'selected' : '' ?>>Mobile Money (M-Pesa)</option>
      <option value="bank_transfer" <?= $method_f === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
      <option value="bank"          <?= $method_f === 'bank'          ? 'selected' : '' ?>>Bank</option>
      <option value="cash"          <?= $method_f === 'cash'          ? 'selected' : '' ?>>Cash</option>
    </select>
    <select name="status" class="border-border focus:border-blue-1 rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
      <option value="">All Statuses</option>
      <option value="processing" <?= $status_f === 'processing' ? 'selected' : '' ?>>Processing</option>
      <option value="completed"  <?= $status_f === 'completed'  ? 'selected' : '' ?>>Completed</option>
      <option value="failed"     <?= $status_f === 'failed'     ? 'selected' : '' ?>>Failed</option>
      <option value="refunded"   <?= $status_f === 'refunded'   ? 'selected' : '' ?>>Refunded</option>
      <option value="pending"    <?= $status_f === 'pending'    ? 'selected' : '' ?>>Pending</option>
    </select>
    <button type="submit"
      class="bg-blue-1 hover:bg-dark-1 rounded px-5 py-2.5 text-sm font-medium text-white transition">
      Filter
    </button>
    <?php if ($search || $method_f || $status_f): ?>
    <a href="/admin/payments" class="border-border rounded border px-4 py-2.5 text-sm text-light-1 transition hover:bg-dark-4">
      Clear
    </a>
    <?php endif; ?>
  </div>
</form>

<!-- Payments Table -->
<div class="rounded bg-dark-3 border border-border p-6">
  <div class="overflow-x-auto">
    <table class="w-full border-collapse">
      <thead class="bg-dark-4">
        <tr>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Booking</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Customer</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap hidden md:table-cell">Method</th>
          <th class="text-white px-4 py-3 text-right text-sm font-semibold whitespace-nowrap">Amount</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap hidden lg:table-cell">Receipt / Ref</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Status</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap hidden md:table-cell">Date</th>
          <th class="text-white px-4 py-3 text-left text-sm font-semibold whitespace-nowrap">Action</th>
        </tr>
      </thead>
      <tbody class="divide-border divide-y divide-dashed">
        <?php if (!$payments): ?>
        <tr>
          <td colspan="8" class="px-4 py-12 text-center text-light-1">No payment records found.</td>
        </tr>
        <?php else: ?>
        <?php foreach ($payments as $p): ?>
        <tr class="transition hover:bg-dark-4">
          <td class="px-4 py-4 whitespace-nowrap">
            <a href="/admin/booking-view?id=<?= (int)$p['booking_id'] ?>"
              class="text-blue-1 font-mono text-sm font-medium hover:underline">
              <?= h($p['booking_ref']) ?>
            </a>
          </td>
          <td class="px-4 py-4">
            <div class="text-white text-sm font-medium"><?= h($p['full_name']) ?></div>
            <div class="text-light-1 text-xs"><?= h($p['phone']) ?></div>
          </td>
          <td class="px-4 py-4 text-sm font-medium text-dark-1 hidden md:table-cell whitespace-nowrap">
            <?php
            echo match($p['payment_method']) {
                'card'          => '💳 Card',
                'mobile_money'  => '📱 Mobile Money',
                'bank_transfer' => '🏦 Bank Transfer',
                'bank'          => '🏦 Bank',
                'cash'          => '💵 Cash',
                default         => ucwords(str_replace('_', ' ', $p['payment_method'])),
            };
            ?>
          </td>
          <td class="px-4 py-4 text-right text-sm font-semibold text-dark-1 whitespace-nowrap">
            <?= format_kes($p['amount']) ?>
          </td>
          <td class="px-4 py-4 hidden lg:table-cell whitespace-nowrap">
            <?php if ($p['gateway_ref']): ?>
            <span class="font-mono text-xs text-gray-600"><?= h($p['gateway_ref']) ?></span>
            <?php elseif ($p['transaction_ref']): ?>
            <span class="font-mono text-xs text-gray-400"><?= h(substr($p['transaction_ref'], 0, 20)) ?>…</span>
            <?php else: ?>
            <span class="text-gray-300">—</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-4 whitespace-nowrap">
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= payment_status_class($p['status']) ?>">
              <?= ucfirst($p['status']) ?>
            </span>
          </td>
          <td class="px-4 py-4 text-xs text-light-1 hidden md:table-cell whitespace-nowrap">
            <?= $p['paid_at'] ? display_datetime($p['paid_at']) : display_datetime($p['created_at']) ?>
          </td>
          <td class="px-4 py-4 whitespace-nowrap">
            <a href="/admin/booking-view?id=<?= (int)$p['booking_id'] ?>"
              class="bg-light-2 hover:bg-blue-1 group flex h-9 w-9 items-center justify-center rounded transition"
              title="View booking">
              <i class="icon-eye text-light-1 text-base group-hover:text-white"></i>
            </a>
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
        class="text-light-1 hover:bg-blue-1 border-border hover:border-blue-1 flex h-10 w-10 items-center justify-center rounded-full border bg-dark-3 text-xs duration-300 hover:text-white">
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
        class="text-light-1 hover:bg-blue-1 border-border hover:border-blue-1 flex h-10 w-10 items-center justify-center rounded-full border bg-dark-3 text-xs duration-300 hover:text-white">
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
