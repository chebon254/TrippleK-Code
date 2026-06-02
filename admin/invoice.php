<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/booking_functions.php';

require_admin_auth();

// Accept ?id=X or ?ref=TK-...
$id  = (int)($_GET['id']  ?? 0);
$ref = trim($_GET['ref']  ?? '');

if ($id) {
    $booking = get_booking_by_id($id);
} elseif ($ref) {
    $booking = get_booking_by_ref($ref);
} else {
    flash('error', 'No booking specified.');
    redirect('/admin/bookings');
}

if (!$booking) {
    flash('error', 'Booking not found.');
    redirect('/admin/bookings');
}

$company_name    = get_setting('company_name',    'Tripple K Car Hire');
$company_address = get_setting('company_address', '');
$company_phone   = get_setting('company_phone',   '');
$company_email   = get_setting('company_email',   '');
$company_website = get_setting('company_website', '');
$kra_pin         = get_setting('kra_pin',         '');
$tra_license     = get_setting('tra_license',     '');

$admin_page = 'bookings';
$page_title = 'Invoice ' . $booking['booking_ref'];
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-6 flex items-center justify-between print:hidden">
  <a href="/admin/booking-view?id=<?= $booking['id'] ?>" class="text-sm text-blue-1 hover:underline">&larr; Back to Booking</a>
  <div class="flex gap-3">
    <a href="<?= APP_URL ?>/invoice?ref=<?= urlencode($booking['booking_ref']) ?>" target="_blank"
      class="inline-flex h-9 items-center gap-1.5 rounded border border-gray-200 px-4 text-sm text-light-1 hover:bg-dark-4 transition">
      Public Link
    </a>
    <button onclick="window.print()"
      class="bg-blue-1 hover:bg-dark-1 inline-flex h-9 items-center gap-2 rounded px-5 text-sm font-medium text-white transition">
      <i class="icon-download text-sm"></i> Print
    </button>
  </div>
</div>

<!-- Invoice Card -->
<div class="overflow-hidden rounded bg-dark-3 border border-border" id="invoice-card">
  <div class="px-8 py-10 md:px-12">

    <!-- Header -->
    <div class="flex flex-wrap items-start justify-between gap-6">
      <div>
        <div class="text-2xl font-bold text-dark-1"><?= h($company_name) ?></div>
        <?php if ($company_address): ?><div class="mt-1 text-sm text-gray-500"><?= nl2br(h($company_address)) ?></div><?php endif; ?>
        <?php if ($company_phone): ?><div class="mt-1 text-sm text-gray-500">Tel: <?= h($company_phone) ?></div><?php endif; ?>
        <?php if ($company_email): ?><div class="mt-0.5 text-sm text-gray-500"><?= h($company_email) ?></div><?php endif; ?>
        <?php if ($kra_pin): ?><div class="mt-1 text-xs text-gray-400">KRA PIN: <?= h($kra_pin) ?></div><?php endif; ?>
        <?php if ($tra_license): ?><div class="text-xs text-gray-400">TRA License: <?= h($tra_license) ?></div><?php endif; ?>
      </div>
      <div class="text-right">
        <div class="text-sm font-medium text-gray-400 uppercase tracking-wide">Invoice</div>
        <div class="mt-1 text-2xl font-bold text-dark-1"><?= h($booking['booking_ref']) ?></div>
        <div class="mt-2 text-sm text-gray-500">
          Date: <?= display_date(date('Y-m-d', strtotime($booking['created_at'] ?? 'now'))) ?>
        </div>
        <div class="mt-1">
          <span class="inline-block rounded px-2.5 py-0.5 text-xs font-semibold <?= booking_status_class($booking['status']) ?>">
            <?= ucfirst($booking['status']) ?>
          </span>
        </div>
      </div>
    </div>

    <div class="my-8 border-t border-border"></div>

    <!-- Parties -->
    <div class="grid gap-8 sm:grid-cols-2">
      <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">From</div>
        <div class="font-semibold text-dark-1"><?= h($company_name) ?></div>
        <?php if ($company_address): ?><div class="mt-1 text-sm text-gray-500"><?= nl2br(h($company_address)) ?></div><?php endif; ?>
      </div>
      <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Bill To</div>
        <div class="font-semibold text-dark-1"><?= h($booking['full_name']) ?></div>
        <div class="mt-1 text-sm text-gray-500"><?= h($booking['phone']) ?></div>
        <div class="text-sm text-gray-500"><?= h($booking['email']) ?></div>
        <?php if ($booking['id_number']): ?>
        <div class="mt-1 text-xs text-gray-400">ID/Passport: <?= h($booking['id_number']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="my-8 border-t border-border"></div>

    <!-- Booking Details -->
    <div class="mb-8">
      <div class="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Booking Details</div>
      <div class="grid gap-3 text-sm sm:grid-cols-2 md:grid-cols-3">
        <div><div class="text-xs text-gray-400">Vehicle</div><div class="font-medium text-dark-1"><?= h($booking['make'] . ' ' . $booking['model'] . ' ' . $booking['year']) ?></div></div>
        <div><div class="text-xs text-gray-400">Service</div><div class="font-medium"><?= h($booking['service_name']) ?></div></div>
        <div>
          <div class="text-xs text-gray-400">Pickup</div>
          <div class="font-medium"><?= display_date($booking['pickup_date']) ?>
          <?php if ($booking['pickup_time']): ?> at <?= h(substr($booking['pickup_time'],0,5)) ?><?php endif; ?></div>
        </div>
        <?php if ($booking['return_date']): ?>
        <div><div class="text-xs text-gray-400">Return</div><div><?= display_date($booking['return_date']) ?></div></div>
        <div><div class="text-xs text-gray-400">Duration</div><div><?= (int)$booking['num_days'] ?> day<?= $booking['num_days'] > 1 ? 's' : '' ?></div></div>
        <?php endif; ?>
        <?php if ($booking['pickup_location']): ?>
        <div><div class="text-xs text-gray-400">Pickup Location</div><div><?= h($booking['pickup_location']) ?></div></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Items Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full border-collapse text-left text-sm">
        <thead>
          <tr class="bg-blue-1/5 text-blue-1">
            <th class="px-4 py-3 font-semibold">Description</th>
            <th class="px-4 py-3 font-semibold">Days</th>
            <th class="px-4 py-3 font-semibold text-right">Rate / Day</th>
            <th class="px-4 py-3 font-semibold text-right">Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-t border-border">
            <td class="px-4 py-3">
              <?= h($booking['service_name']) ?> — <?= h($booking['make'] . ' ' . $booking['model']) ?>
              <?php if ($booking['registration_number']): ?>
              <span class="ml-1 text-xs text-gray-400">(<?= h($booking['registration_number']) ?>)</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3"><?= (int)$booking['num_days'] ?></td>
            <td class="px-4 py-3 text-right">
              $ <?= number_format($booking['num_days'] > 0 ? $booking['total_amount'] / $booking['num_days'] : $booking['total_amount'], 2) ?>
            </td>
            <td class="px-4 py-3 text-right"><?= format_kes($booking['total_amount']) ?></td>
          </tr>
          <tr class="border-t border-border bg-dark-4">
            <td class="px-4 py-4 font-semibold text-dark-1" colspan="3">Total Amount</td>
            <td class="px-4 py-4 font-bold text-dark-1 text-right text-base"><?= format_kes($booking['total_amount']) ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Payment Status -->
    <?php if ($booking['payment_status'] === 'completed'): ?>
    <div class="mt-8 rounded-lg bg-green-50 px-5 py-4 text-sm">
      <div class="mb-1 font-semibold text-green-700">Payment Received</div>
      <div class="grid gap-2 text-green-700 sm:grid-cols-2">
        <div><span class="text-green-500">Method:</span> <?= h(ucwords(str_replace('_', ' ', $booking['payment_method'] ?? ''))) ?></div>
        <?php if ($booking['gateway_ref']): ?>
        <div><span class="text-green-500">Receipt:</span> <span class="font-mono"><?= h($booking['gateway_ref']) ?></span></div>
        <?php endif; ?>
        <?php if ($booking['paid_at']): ?>
        <div><span class="text-green-500">Paid On:</span> <?= display_datetime($booking['paid_at']) ?></div>
        <?php endif; ?>
        <div><span class="text-green-500">Amount:</span> <strong><?= format_kes($booking['paid_amount'] ?? $booking['total_amount']) ?></strong></div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Footer -->
  <div class="border-t border-border px-8 py-6 text-center text-sm text-gray-400">
    <div class="flex flex-wrap justify-center gap-x-8 gap-y-1">
      <?php if ($company_website): ?><span><?= h($company_website) ?></span><?php endif; ?>
      <?php if ($company_email): ?><span><?= h($company_email) ?></span><?php endif; ?>
      <?php if ($company_phone): ?><span><?= h($company_phone) ?></span><?php endif; ?>
    </div>
    <div class="mt-2 text-xs">Thank you for choosing <?= h($company_name) ?>.</div>
  </div>
</div>

<style>
@media print {
  .print\:hidden { display: none !important; }
  body { background: white; }
  #invoice-card { box-shadow: none; border: 1px solid #e5e7eb; }
  aside, header, .sidebar-overlay { display: none !important; }
  main { margin: 0 !important; padding: 0 !important; }
}
</style>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
