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
          class="flex items-center gap-2 text-[#25D366] hover:opacity-80 transition-opacity">
          <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="16" cy="16" r="16" fill="#25D366"/>
            <path fill="#fff" d="M23.5 8.5A10.42 10.42 0 0 0 16 5.5a10.5 10.5 0 0 0-9.1 15.74L5.5 26.5l5.41-1.42A10.5 10.5 0 0 0 16 26.5a10.5 10.5 0 0 0 7.5-17.91Zm-7.5 16.15a8.7 8.7 0 0 1-4.44-1.22l-.32-.19-3.21.84.86-3.13-.21-.33a8.72 8.72 0 1 1 7.32 3.99Zm4.78-6.53c-.26-.13-1.54-.76-1.78-.85s-.41-.13-.58.13-.67.85-.82 1.02-.3.2-.57.07a7.24 7.24 0 0 1-2.09-1.29 7.84 7.84 0 0 1-1.45-1.8c-.15-.26 0-.4.11-.53s.26-.3.39-.46a1.8 1.8 0 0 0 .26-.43.48.48 0 0 0-.02-.46c-.07-.13-.58-1.41-.8-1.93s-.42-.44-.58-.45h-.5a.95.95 0 0 0-.69.32 2.91 2.91 0 0 0-.91 2.17 5.05 5.05 0 0 0 1.06 2.68 11.57 11.57 0 0 0 4.44 3.93 14.5 14.5 0 0 0 1.48.55 3.56 3.56 0 0 0 1.64.1 2.67 2.67 0 0 0 1.75-1.24 2.16 2.16 0 0 0 .15-1.24c-.06-.11-.24-.17-.5-.3Z"/>
          </svg>
          WhatsApp Customer
        </a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
