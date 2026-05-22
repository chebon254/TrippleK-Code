<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/booking_functions.php';

$booking_ref = trim($_GET['ref'] ?? '');

if (!$booking_ref) {
    flash('error', 'No booking reference provided.');
    redirect('/cars.php');
}

$booking = get_booking_by_ref($booking_ref);

if (!$booking) {
    flash('error', 'Booking not found.');
    redirect('/cars.php');
}

// Settings
$company_name    = get_setting('company_name',    'Tripple K Car Hire');
$company_address = get_setting('company_address', '');
$company_phone   = get_setting('company_phone',   '');
$company_email   = get_setting('company_email',   '');
$company_website = get_setting('company_website', '');
$kra_pin         = get_setting('kra_pin',         '');
$tra_license     = get_setting('tra_license',     '');

$page_title = 'Invoice ' . $booking['booking_ref'];
require __DIR__ . '/includes/header.php';
?>
<main class="flex-grow">
  <div class="container py-8">

    <!-- Actions (hidden on print) -->
    <div class="mb-6 flex items-center justify-between print:hidden">
      <a href="/cars.php" class="text-sm text-blue-1 hover:underline">&larr; Browse More Cars</a>
      <button onclick="window.print()"
        class="bg-blue-1 hover:bg-yellow-3 inline-flex h-11 items-center gap-2 rounded px-6 text-sm font-medium text-white transition shadow-sm">
        Print Invoice <i class="icon-download ml-1 text-base"></i>
      </button>
    </div>

    <!-- Status Banner -->
    <?php if ($booking['status'] === 'confirmed' || $booking['status'] === 'active'): ?>
    <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 px-5 py-4 text-sm text-green-700 print:hidden">
      <svg class="h-5 w-5 flex-shrink-0 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
      <span>Your booking is <strong>confirmed</strong>. Please present this invoice when collecting your vehicle.</span>
    </div>
    <?php elseif ($booking['status'] === 'pending'): ?>
    <div class="mb-6 flex items-center gap-3 rounded-lg bg-yellow-100 px-5 py-4 text-sm text-yellow-600 print:hidden">
      <svg class="h-5 w-5 flex-shrink-0 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
      <span>Payment pending. <a href="/booking.php?step=payment&ref=<?= urlencode($booking['booking_ref']) ?>" class="font-medium underline">Complete payment</a> to confirm your booking.</span>
    </div>
    <?php endif; ?>

    <!-- Invoice Card -->
    <div class="overflow-hidden rounded-xl bg-dark-3 shadow-sm border border-border" id="invoice-card">
      <div class="px-8 py-10 md:px-12">

        <!-- Header: Logo + Invoice Number -->
        <div class="flex flex-wrap items-start justify-between gap-6">
          <div>
            <div class="text-2xl font-bold text-white"><?= h($company_name) ?></div>
            <?php if ($company_address): ?>
            <div class="mt-1 text-sm text-white/50"><?= nl2br(h($company_address)) ?></div>
            <?php endif; ?>
            <?php if ($company_phone): ?>
            <div class="mt-1 text-sm text-white/50">Tel: <?= h($company_phone) ?></div>
            <?php endif; ?>
            <?php if ($company_email): ?>
            <div class="mt-0.5 text-sm text-white/50"><?= h($company_email) ?></div>
            <?php endif; ?>
            <?php if ($kra_pin): ?>
            <div class="mt-1 text-xs text-white/40">KRA PIN: <?= h($kra_pin) ?></div>
            <?php endif; ?>
            <?php if ($tra_license): ?>
            <div class="text-xs text-white/40">TRA License: <?= h($tra_license) ?></div>
            <?php endif; ?>
          </div>
          <div class="text-right">
            <div class="text-sm font-medium text-white/40 uppercase tracking-wide">Invoice</div>
            <div class="mt-1 text-2xl font-bold text-white"><?= h($booking['booking_ref']) ?></div>
            <div class="mt-2 text-sm text-white/50">
              Date: <?= display_date(date('Y-m-d', strtotime($booking['created_at'] ?? 'now'))) ?>
            </div>
            <div class="mt-1">
              <span class="inline-block rounded px-2.5 py-0.5 text-xs font-semibold <?= booking_status_class($booking['status']) ?>">
                <?= ucfirst($booking['status']) ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Divider -->
        <div class="my-8 border-t border-gray-100"></div>

        <!-- Parties: Supplier + Customer -->
        <div class="grid gap-8 sm:grid-cols-2">
          <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-white/40">From</div>
            <div class="font-semibold text-white"><?= h($company_name) ?></div>
            <?php if ($company_address): ?>
            <div class="mt-1 text-sm text-white/50"><?= nl2br(h($company_address)) ?></div>
            <?php endif; ?>
          </div>
          <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-white/40">Bill To</div>
            <div class="font-semibold text-white"><?= h($booking['full_name']) ?></div>
            <div class="mt-1 text-sm text-white/50"><?= h($booking['phone']) ?></div>
            <div class="text-sm text-white/50"><?= h($booking['email']) ?></div>
            <?php if ($booking['id_number']): ?>
            <div class="mt-1 text-xs text-white/40">ID/Passport: <?= h($booking['id_number']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Divider -->
        <div class="my-8 border-t border-gray-100"></div>

        <!-- Booking Details -->
        <div class="mb-8">
          <div class="mb-4 text-xs font-semibold uppercase tracking-wide text-white/40">Booking Details</div>
          <div class="grid gap-3 text-sm sm:grid-cols-2 md:grid-cols-3">
            <div>
              <div class="text-xs text-white/40">Vehicle</div>
              <div class="font-medium text-white"><?= h($booking['make'] . ' ' . $booking['model'] . ' ' . $booking['year']) ?></div>
            </div>
            <div>
              <div class="text-xs text-white/40">Category</div>
              <div class="font-medium text-white"><?= h($booking['category_name']) ?></div>
            </div>
            <div>
              <div class="text-xs text-white/40">Service</div>
              <div class="font-medium text-white"><?= h($booking['service_name']) ?></div>
            </div>
            <div>
              <div class="text-xs text-white/40">Pickup Date &amp; Time</div>
              <div class="font-medium text-white">
                <?= display_date($booking['pickup_date']) ?>
                <?php if ($booking['pickup_time']): ?> at <?= h(substr($booking['pickup_time'], 0, 5)) ?><?php endif; ?>
              </div>
            </div>
            <?php if ($booking['return_date']): ?>
            <div>
              <div class="text-xs text-white/40">Return Date</div>
              <div class="font-medium text-white"><?= display_date($booking['return_date']) ?></div>
            </div>
            <div>
              <div class="text-xs text-white/40">Duration</div>
              <div class="font-medium text-white"><?= $booking['num_days'] ?> day<?= $booking['num_days'] > 1 ? 's' : '' ?></div>
            </div>
            <?php endif; ?>
            <?php if ($booking['pickup_location']): ?>
            <div>
              <div class="text-xs text-white/40">Pickup Location</div>
              <div class="font-medium text-white"><?= h($booking['pickup_location']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($booking['dropoff_location']): ?>
            <div>
              <div class="text-xs text-white/40">Drop-off Location</div>
              <div class="font-medium text-white"><?= h($booking['dropoff_location']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($booking['num_passengers']): ?>
            <div>
              <div class="text-xs text-white/40">Passengers</div>
              <div class="font-medium text-white"><?= (int)$booking['num_passengers'] ?></div>
            </div>
            <?php endif; ?>
            <?php if ($booking['flight_number']): ?>
            <div>
              <div class="text-xs text-white/40">Flight Number</div>
              <div class="font-medium text-white"><?= h($booking['flight_number']) ?></div>
            </div>
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
              <tr class="border-t border-gray-100">
                <td class="px-4 py-3">
                  <?= h($booking['service_name']) ?> — <?= h($booking['make'] . ' ' . $booking['model']) ?>
                  <?php if ($booking['registration_number']): ?>
                  <span class="ml-1 text-xs text-white/40">(<?= h($booking['registration_number']) ?>)</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3"><?= (int)$booking['num_days'] ?></td>
                <td class="px-4 py-3 text-right">
                  <?php
                  $rate = $booking['num_days'] > 0
                    ? number_format($booking['total_amount'] / $booking['num_days'], 2)
                    : number_format($booking['total_amount'], 2);
                  ?>
                  KES <?= $rate ?>
                </td>
                <td class="px-4 py-3 text-right"><?= format_kes($booking['total_amount']) ?></td>
              </tr>
              <tr class="border-t border-[#2a2a2a] bg-dark-4">
                <td class="px-4 py-4 font-semibold text-white" colspan="3">Total Amount</td>
                <td class="px-4 py-4 font-bold text-white text-right text-base"><?= format_kes($booking['total_amount']) ?></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Payment Info -->
        <?php if ($booking['payment_status'] === 'completed'): ?>
        <div class="mt-8 rounded-lg bg-green-50 px-5 py-4 text-sm">
          <div class="mb-1 font-semibold text-green-700">Payment Received</div>
          <div class="grid gap-2 text-white sm:grid-cols-2">
            <div><span class="text-light-1">Method:</span> <?= h(ucwords(str_replace('_', ' ', $booking['payment_method'] ?? ''))) ?></div>
            <?php if ($booking['gateway_ref']): ?>
            <div><span class="text-light-1">Receipt:</span> <span class="font-mono"><?= h($booking['gateway_ref']) ?></span></div>
            <?php endif; ?>
            <?php if ($booking['paid_at']): ?>
            <div><span class="text-light-1">Paid On:</span> <?= display_datetime($booking['paid_at']) ?></div>
            <?php endif; ?>
            <div><span class="text-light-1">Amount:</span> <strong><?= format_kes($booking['paid_amount'] ?? $booking['total_amount']) ?></strong></div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if ($booking['special_requests']): ?>
        <div class="mt-6 rounded-lg bg-dark-4 px-5 py-4 text-sm">
          <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-white/40">Special Requests</div>
          <div class="text-white/60"><?= nl2br(h($booking['special_requests'])) ?></div>
        </div>
        <?php endif; ?>

      </div>

      <!-- Footer -->
      <div class="border-t border-gray-100 px-8 py-6 text-center text-sm text-white/40">
        <div class="flex flex-wrap justify-center gap-x-8 gap-y-1">
          <?php if ($company_website): ?><a href="<?= h($company_website) ?>" class="hover:text-blue-1 transition"><?= h($company_website) ?></a><?php endif; ?>
          <?php if ($company_email): ?><a href="mailto:<?= h($company_email) ?>" class="hover:text-blue-1 transition"><?= h($company_email) ?></a><?php endif; ?>
          <?php if ($company_phone): ?><a href="tel:<?= h(preg_replace('/\s+/', '', $company_phone)) ?>" class="hover:text-blue-1 transition"><?= h($company_phone) ?></a><?php endif; ?>
        </div>
        <div class="mt-2 text-xs">Thank you for choosing <?= h($company_name) ?>. We look forward to serving you.</div>
      </div>
    </div>

  </div>
</main>

<style>
@media print {
  .print\:hidden { display: none !important; }
  body { background: white; }
  #invoice-card { box-shadow: none; }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
