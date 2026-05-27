<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer_functions.php';

require_admin_auth();

$page_title = 'Customers';
$db         = get_db();

// ─── POST: delete ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request.');
        redirect('/admin/customers');
    }

    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Remove all uploaded files
            delete_customer_files($id);
            $db->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
            flash('success', 'Customer deleted.');
        }
        redirect('/admin/customers');
    }

    if (($_POST['action'] ?? '') === 'toggle_status') {
        $id  = (int)($_POST['id'] ?? 0);
        $new = ($_POST['status'] ?? '') === 'active' ? 'inactive' : 'active';
        if ($id) {
            $db->prepare('UPDATE customers SET status = ? WHERE id = ?')->execute([$new, $id]);
            flash('success', 'Status updated.');
        }
        redirect('/admin/customers');
    }
}

// ─── Filters ─────────────────────────────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$type_f   = trim($_GET['type']   ?? '');
$status_f = trim($_GET['status'] ?? '');
$per_page = 20;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

$where  = [];
$params = [];

if ($search) {
    $like     = '%' . $search . '%';
    $where[]  = '(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR corporate_name LIKE ?)';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($type_f && in_array($type_f, ['individual', 'organization'], true)) {
    $where[]  = 'customer_type = ?';
    $params[] = $type_f;
}
if ($status_f && in_array($status_f, ['active', 'inactive'], true)) {
    $where[]  = 'status = ?';
    $params[] = $status_f;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$count_stmt = $db->prepare("SELECT COUNT(*) FROM customers {$where_sql}");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$total_pages = (int)ceil($total / $per_page);

// Rows
$rows_stmt = $db->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) AS booking_count
    FROM customers c
    {$where_sql}
    ORDER BY c.created_at DESC
    LIMIT {$per_page} OFFSET {$offset}
");
$rows_stmt->execute($params);
$customers = $rows_stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Summary counts ───────────────────────────────────────────────────────────
try {
    $summary = $db->query("
        SELECT
          COUNT(*) AS total,
          SUM(customer_type = 'individual')   AS individuals,
          SUM(customer_type = 'organization') AS organisations,
          SUM(status = 'active')              AS active
        FROM customers
    ")->fetch(PDO::FETCH_ASSOC);
} catch (Throwable) {
    $summary = ['total' => 0, 'individuals' => 0, 'organisations' => 0, 'active' => 0];
}

require __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="text-white mb-1 text-3xl font-semibold">Customers</h1>
    <p class="text-light-1">All registered customers and their KYC details.</p>
  </div>
  <a href="/admin/add-customer"
    class="bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-5 py-2.5 text-sm font-semibold transition-colors">
    + Add Customer
  </a>
</div>

<!-- Summary cards -->
<div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
  <?php
  $cards = [
    ['label'=>'Total', 'value'=>$summary['total'], 'icon'=>'icon-users', 'color'=>'text-blue-1'],
    ['label'=>'Individuals', 'value'=>$summary['individuals'], 'icon'=>'icon-user', 'color'=>'text-yellow-3'],
    ['label'=>'Organisations', 'value'=>$summary['organisations'], 'icon'=>'icon-building', 'color'=>'text-purple-400'],
    ['label'=>'Active', 'value'=>$summary['active'], 'icon'=>'icon-check', 'color'=>'text-green-400'],
  ];
  foreach ($cards as $c):
  ?>
  <div class="rounded bg-dark-3 border border-border p-4">
    <div class="flex items-center justify-between">
      <span class="text-light-1 text-sm"><?= $c['label'] ?></span>
      <i class="<?= $c['icon'] ?> <?= $c['color'] ?>"></i>
    </div>
    <div class="text-white mt-2 text-2xl font-bold"><?= number_format((int)$c['value']) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="mb-6 flex flex-wrap items-center gap-3">

  <!-- Search -->
  <div class="relative flex-1 min-w-48">
    <form method="GET" action="/admin/customers">
      <?php if ($type_f):   ?><input type="hidden" name="type"   value="<?= h($type_f) ?>"><?php endif; ?>
      <?php if ($status_f): ?><input type="hidden" name="status" value="<?= h($status_f) ?>"><?php endif; ?>
      <input type="search" name="search" value="<?= h($search) ?>" placeholder="Search name, email, phone…"
        class="border-border focus:border-blue-1 w-full rounded border bg-dark-3 text-white placeholder-light-1 py-2.5 pl-12 pr-4 outline-none">
      <i class="icon-search text-light-1 absolute top-1/2 left-4 -translate-y-1/2 text-lg"></i>
    </form>
  </div>

  <!-- Type filter -->
  <?php
  $types = ['' => 'All Types', 'individual' => 'Individual', 'organization' => 'Organisation'];
  foreach ($types as $val => $label):
    $active = $type_f === $val;
    $base_q = http_build_query(array_filter(['search' => $search, 'status' => $status_f, 'type' => $val]));
  ?>
  <a href="/admin/customers?<?= $base_q ?>"
    class="rounded border px-4 py-2 text-sm font-medium transition-colors
      <?= $active ? 'bg-blue-1/20 text-blue-1 border-blue-1/30' : 'bg-dark-3 text-light-1 border-border hover:bg-dark-4' ?>">
    <?= $label ?>
  </a>
  <?php endforeach; ?>

  <!-- Status filter -->
  <?php
  $statuses = ['' => 'All Status', 'active' => 'Active', 'inactive' => 'Inactive'];
  foreach ($statuses as $val => $label):
    $active = $status_f === $val;
    $base_q = http_build_query(array_filter(['search' => $search, 'type' => $type_f, 'status' => $val]));
  ?>
  <a href="/admin/customers?<?= $base_q ?>"
    class="rounded border px-4 py-2 text-sm font-medium transition-colors
      <?= $active ? 'bg-blue-1/20 text-blue-1 border-blue-1/30' : 'bg-dark-3 text-light-1 border-border hover:bg-dark-4' ?>">
    <?= $label ?>
  </a>
  <?php endforeach; ?>

</div>

<!-- Table -->
<div class="rounded bg-dark-3 border border-border overflow-x-auto">
  <table class="w-full min-w-[640px]">
    <thead class="bg-dark-4">
      <tr>
        <th class="text-white px-4 py-3 text-left text-sm font-semibold">Customer</th>
        <th class="text-white px-4 py-3 text-left text-sm font-semibold hidden sm:table-cell">Type</th>
        <th class="text-white px-4 py-3 text-left text-sm font-semibold hidden md:table-cell">Contact</th>
        <th class="text-white px-4 py-3 text-left text-sm font-semibold hidden lg:table-cell">Bookings</th>
        <th class="text-white px-4 py-3 text-left text-sm font-semibold hidden lg:table-cell">Joined</th>
        <th class="text-white px-4 py-3 text-left text-sm font-semibold">Status</th>
        <th class="text-white px-4 py-3 text-right text-sm font-semibold">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-border">
      <?php if (empty($customers)): ?>
      <tr>
        <td colspan="7" class="px-4 py-12 text-center text-light-1">
          <?= $search || $type_f || $status_f ? 'No customers match your filters.' : 'No customers yet.' ?>
        </td>
      </tr>
      <?php else: ?>
      <?php foreach ($customers as $c):
        $name = customer_display_name($c);
        $initials = strtoupper(substr($name, 0, 2));
        $photo_url = $c['photo'] ? customer_file_url($c['photo']) : '';
      ?>
      <tr class="hover:bg-dark-4 transition-colors">
        <!-- Name + photo -->
        <td class="px-4 py-3">
          <div class="flex items-center gap-3">
            <?php if ($photo_url): ?>
            <img src="<?= h($photo_url) ?>" alt=""
              class="h-9 w-9 rounded-full object-cover border border-border flex-shrink-0">
            <?php else: ?>
            <div class="h-9 w-9 rounded-full bg-blue-1/20 border border-blue-1/30 flex items-center justify-center flex-shrink-0 text-xs font-bold text-blue-1">
              <?= h($initials) ?>
            </div>
            <?php endif; ?>
            <div>
              <a href="/admin/customer-view?id=<?= (int)$c['id'] ?>"
                class="text-white text-sm font-medium hover:text-blue-1 transition-colors">
                <?= h($name) ?>
              </a>
              <?php if ($c['customer_type'] === 'organization' && !empty($c['contact_first_name'])): ?>
              <div class="text-light-1 text-xs">
                Contact: <?= h($c['contact_first_name'] . ' ' . $c['contact_last_name']) ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </td>

        <!-- Type -->
        <td class="px-4 py-3 hidden sm:table-cell">
          <?= customer_type_badge($c['customer_type']) ?>
        </td>

        <!-- Contact -->
        <td class="px-4 py-3 hidden md:table-cell">
          <div class="text-white text-sm"><?= h($c['email'] ?? '') ?></div>
          <div class="text-light-1 text-xs"><?= h($c['phone'] ?? '') ?></div>
        </td>

        <!-- Bookings -->
        <td class="px-4 py-3 text-light-1 text-sm hidden lg:table-cell">
          <?php if ($c['booking_count'] > 0): ?>
          <a href="/admin/bookings?search=<?= urlencode($c['email'] ?? '') ?>"
            class="text-blue-1 hover:text-yellow-3 transition-colors">
            <?= (int)$c['booking_count'] ?> booking<?= $c['booking_count'] != 1 ? 's' : '' ?>
          </a>
          <?php else: ?>
          <span>0 bookings</span>
          <?php endif; ?>
        </td>

        <!-- Joined -->
        <td class="px-4 py-3 text-light-1 text-sm hidden lg:table-cell">
          <?= h(display_date($c['created_at'] ?? '')) ?>
        </td>

        <!-- Status -->
        <td class="px-4 py-3">
          <?= customer_status_badge($c['status'] ?? 'active') ?>
        </td>

        <!-- Actions -->
        <td class="px-4 py-3 text-right">
          <a href="/admin/customer-view?id=<?= (int)$c['id'] ?>"
            class="text-blue-1 hover:text-yellow-3 text-sm font-medium mr-3 transition-colors">View</a>

          <!-- Toggle status -->
          <form method="POST" action="/admin/customers" class="inline">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <input type="hidden" name="status" value="<?= h($c['status'] ?? 'active') ?>">
            <button type="submit" class="text-light-1 hover:text-white text-sm mr-3 transition-colors">
              <?= ($c['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate' ?>
            </button>
          </form>

          <!-- Delete -->
          <form method="POST" action="/admin/customers" class="inline"
            onsubmit="return confirm('Delete this customer and all their files?')">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="text-red-400 hover:text-red-300 text-sm transition-colors">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="mt-6 flex items-center justify-between text-sm text-light-1">
  <span>Showing <?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?> of <?= number_format($total) ?></span>
  <div class="flex gap-2">
    <?php
    $q_base = http_build_query(array_filter(['search' => $search, 'type' => $type_f, 'status' => $status_f]));
    for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++):
      $active = $p === $page;
    ?>
    <a href="/admin/customers?<?= $q_base ?>&page=<?= $p ?>"
      class="flex h-8 w-8 items-center justify-center rounded border transition-colors
        <?= $active ? 'bg-blue-1 text-dark-1 border-blue-1' : 'bg-dark-3 text-light-1 border-border hover:bg-dark-4' ?>">
      <?= $p ?>
    </a>
    <?php endfor; ?>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
