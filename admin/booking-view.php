<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/booking_functions.php';

require_admin_auth();

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    flash('error', 'Invalid booking ID.');
    redirect('/admin/bookings');
}

$booking = get_booking_by_id($id);

if (!$booking) {
    flash('error', 'Booking not found.');
    redirect('/admin/bookings');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid CSRF token.');
        redirect('/admin/booking-view?id=' . $id);
    }
    $new_status  = trim($_POST['status']      ?? '');
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    if (update_booking_status($id, $new_status, $admin_notes)) {
        flash('success', 'Booking status updated to ' . ucfirst($new_status) . '.');
    } else {
        flash('error', 'Invalid status value.');
    }
    redirect('/admin/booking-view?id=' . $id);
}

// Fetch all payment records for this booking
$pay_stmt = $db->prepare('SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC');
$pay_stmt->execute([$id]);
$payments = $pay_stmt->fetchAll();

$admin_page = 'bookings';
$page_title = 'Booking ' . $booking['booking_ref'];
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
  <div>
    <a href="/admin/bookings" class="text-blue-1 mb-2 inline-flex items-center gap-1 text-sm hover:underline">
      <i class="icon-chevron-left text-xs"></i> All Bookings
    </a>
    <h1 class="text-white text-3xl font-semibold"><?= h($booking['booking_ref']) ?></h1>
  </div>
  <div class="flex gap-3">
    <a href="/admin/invoice?id=<?= $id ?>"
      class="border-border inline-flex h-10 items-center gap-2 rounded border px-4 text-sm text-light-1 transition hover:bg-dark-4">
      <i class="icon-download text-sm"></i> Invoice
    </a>
    <a href="<?= APP_URL ?>/invoice?ref=<?= urlencode($booking['booking_ref']) ?>" target="_blank"
      class="border-border inline-flex h-10 items-center gap-2 rounded border px-4 text-sm text-light-1 transition hover:bg-dark-4">
      <i class="icon-arrow-top-right text-sm"></i> Public Invoice
    </a>
  </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">

  <!-- Left: Booking Details -->
  <div class="lg:col-span-2 space-y-6">

    <!-- Customer -->
    <div class="rounded bg-dark-3 border border-border p-6">
      <h2 class="mb-4 text-sm font-semibold text-white">Customer Information</h2>
      <div class="grid gap-3 text-sm sm:grid-cols-2">
        <div><div class="text-xs text-light-1">Full Name</div><div class="font-medium text-white"><?= h($booking['full_name']) ?></div></div>
        <div><div class="text-xs text-light-1">Phone</div><div class="font-medium text-white"><?= h($booking['phone']) ?></div></div>
        <div><div class="text-xs text-light-1">Email</div><div class="font-medium text-white"><?= h($booking['email']) ?></div></div>
        <?php if ($booking['id_number']): ?>
        <div><div class="text-xs text-light-1">ID/Passport</div><div class="font-medium text-white"><?= h($booking['id_number']) ?></div></div>
        <?php endif; ?>
        <?php if ($booking['address']): ?>
        <div class="sm:col-span-2"><div class="text-xs text-light-1">Address</div><div class="text-white"><?= h($booking['address']) ?></div></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Booking -->
    <div class="rounded bg-dark-3 border border-border p-6">
      <h2 class="mb-4 text-sm font-semibold text-white">Booking Details</h2>
      <div class="grid gap-3 text-sm sm:grid-cols-2">
        <div>
          <div class="text-xs text-light-1">Vehicle</div>
          <div class="font-medium text-white"><?= h($booking['make'] . ' ' . $booking['model'] . ' (' . $booking['year'] . ')') ?></div>
          <?php if ($booking['registration_number']): ?><div class="text-xs text-light-1 font-mono"><?= h($booking['registration_number']) ?></div><?php endif; ?>
        </div>
        <div><div class="text-xs text-light-1">Category</div><div class="text-white"><?= h($booking['category_name']) ?></div></div>
        <div><div class="text-xs text-light-1">Service</div><div class="text-white"><?= h($booking['service_name']) ?></div></div>
        <div>
          <div class="text-xs text-light-1">Pickup Date &amp; Time</div>
          <div class="text-white"><?= display_date($booking['pickup_date']) ?>
          <?php if ($booking['pickup_time']): ?> at <?= h(substr($booking['pickup_time'],0,5)) ?><?php endif; ?>
          </div>
        </div>
        <?php if ($booking['return_date']): ?>
        <div><div class="text-xs text-light-1">Return Date</div><div class="text-white"><?= display_date($booking['return_date']) ?></div></div>
        <?php endif; ?>
        <div><div class="text-xs text-light-1">Duration</div><div class="text-white"><?= (int)$booking['num_days'] ?> day<?= $booking['num_days'] > 1 ? 's' : '' ?></div></div>
        <?php if ($booking['pickup_location']): ?>
        <div><div class="text-xs text-light-1">Pickup Location</div><div class="text-white"><?= h($booking['pickup_location']) ?></div></div>
        <?php endif; ?>
        <?php if ($booking['dropoff_location']): ?>
        <div><div class="text-xs text-light-1">Drop-off Location</div><div class="text-white"><?= h($booking['dropoff_location']) ?></div></div>
        <?php endif; ?>
        <?php if ($booking['num_passengers']): ?>
        <div><div class="text-xs text-light-1">Passengers</div><div class="text-white"><?= (int)$booking['num_passengers'] ?></div></div>
        <?php endif; ?>
        <?php if ($booking['flight_number']): ?>
        <div><div class="text-xs text-light-1">Flight Number</div><div class="text-white"><?= h($booking['flight_number']) ?></div></div>
        <?php endif; ?>
        <div><div class="text-xs text-light-1">Total Amount</div><div class="font-bold text-white text-base"><?= format_kes($booking['total_amount']) ?></div></div>
        <div><div class="text-xs text-light-1">Created</div><div class="text-white"><?= display_datetime($booking['created_at']) ?></div></div>
      </div>
      <?php if ($booking['special_requests']): ?>
      <div class="mt-4 rounded-lg bg-dark-4 px-4 py-3 text-sm">
        <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-light-1">Special Requests</div>
        <div class="text-white"><?= nl2br(h($booking['special_requests'])) ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Payments -->
    <div class="rounded bg-dark-3 border border-border p-6">
      <h2 class="mb-4 text-sm font-semibold text-white">Payment History</h2>
      <?php if (!$payments): ?>
      <p class="text-sm text-light-1">No payment records yet.</p>
      <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left text-xs uppercase tracking-wide text-light-1 border-b border-border">
              <th class="pb-2 pr-4">Method</th>
              <th class="pb-2 pr-4">Amount</th>
              <th class="pb-2 pr-4">Gateway Ref</th>
              <th class="pb-2 pr-4">Status</th>
              <th class="pb-2">Date</th>
            </tr>
          </thead>
          <tbody class="divide-border divide-y divide-dashed">
            <?php foreach ($payments as $pay): ?>
            <tr>
              <td class="py-2.5 pr-4 font-medium text-white"><?= h(ucwords(str_replace('_', ' ', $pay['payment_method']))) ?></td>
              <td class="py-2.5 pr-4 text-white"><?= format_kes($pay['amount']) ?></td>
              <td class="py-2.5 pr-4 font-mono text-xs text-light-1"><?= h($pay['gateway_ref'] ?? '—') ?></td>
              <td class="py-2.5 pr-4">
                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold <?= payment_status_class($pay['status']) ?>">
                  <?= ucfirst($pay['status']) ?>
                </span>
              </td>
              <td class="py-2.5 text-light-1"><?= $pay['paid_at'] ? display_datetime($pay['paid_at']) : display_datetime($pay['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Right: Status Control -->
  <div class="space-y-6">

    <!-- Current Status -->
    <div class="rounded bg-dark-3 border border-border p-6">
      <h2 class="mb-3 text-sm font-semibold text-white">Booking Status</h2>
      <span class="inline-block rounded-full px-3 py-1 text-sm font-semibold <?= booking_status_class($booking['status']) ?>">
        <?= ucfirst($booking['status']) ?>
      </span>
    </div>

    <!-- Update Status Form -->
    <div class="rounded bg-dark-3 border border-border p-6">
      <h2 class="mb-4 text-sm font-semibold text-white">Update Status</h2>
      <form method="post">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div class="mb-3">
          <label class="mb-1 block text-xs font-medium text-light-1">New Status</label>
          <select name="status" class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
            <?php foreach (['pending','confirmed','active','completed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $booking['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-xs font-medium text-light-1">Admin Notes</label>
          <textarea name="admin_notes" rows="4" placeholder="Internal notes..."
            class="border-border focus:border-blue-1 w-full resize-none rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2 text-sm outline-none"><?= h($booking['admin_notes'] ?? '') ?></textarea>
        </div>
        <button type="submit"
          class="bg-blue-1 hover:bg-dark-1 w-full rounded px-4 py-2.5 text-sm font-medium text-white transition">
          Update Booking
        </button>
      </form>
    </div>

    <!-- Quick Links -->
    <div class="rounded bg-dark-3 border border-border p-6 text-sm">
      <h2 class="mb-3 text-sm font-semibold text-white">Quick Links</h2>
      <div class="space-y-2">
        <a href="<?= APP_URL ?>/invoice?ref=<?= urlencode($booking['booking_ref']) ?>" target="_blank"
          class="flex items-center gap-2 text-blue-1 hover:underline">
          <i class="icon-download text-sm"></i> View Public Invoice
        </a>
        <a href="<?= APP_URL ?>/car?id=<?= (int)$booking['car_id'] ?>" target="_blank"
          class="flex items-center gap-2 text-blue-1 hover:underline">
          <i class="icon-car text-sm"></i> View Car
        </a>
        <?php
        $wa = get_setting('company_whatsapp', '');
        if ($wa && $booking['phone']):
            $wa_clean = preg_replace('/[^0-9]/', '', $booking['phone']);
        ?>
        <a href="https://wa.me/<?= h($wa_clean) ?>?text=Hello+<?= urlencode($booking['full_name']) ?>%2C+regarding+your+booking+<?= urlencode($booking['booking_ref']) ?>"
          target="_blank"
          class="flex items-center gap-2 text-green-600 hover:underline">
          <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967c-.273-.099-.471-.148-.67.15c-.197.297-.767.966-.94 1.164c-.173.199-.347.223-.644.075c-.297-.15-1.255-.463-2.39-1.475c-.883-.788-1.48-1.761-1.653-2.059c-.173-.297-.018-.458.13-.606c.134-.133.298-.347.446-.52c.149-.174.198-.298.298-.497c.099-.198.05-.371-.025-.52c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.5-.669-.51c-.173-.008-.371-.01-.57-.01c-.198 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.096 3.2 5.077 4.487c.709.306 1.262.489 1.694.625c.712.227 1.36.195 1.871.118c.571-.085 1.758-.719 2.006-1.413c.248-.694.248-1.289.173-1.413c-.074-.124-.272-.198-.57-.347z"/></svg>
          WhatsApp Customer
        </a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
