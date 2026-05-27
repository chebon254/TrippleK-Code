<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer_functions.php';

require_admin_auth();

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    flash('error', 'Invalid customer.');
    redirect('/admin/customers');
}

$customer = $db->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
$customer->execute([$id]);
$c = $customer->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    flash('error', 'Customer not found.');
    redirect('/admin/customers');
}

// Bookings for this customer
try {
    $bookings = $db->prepare("
        SELECT b.*, ca.make, ca.model, s.name AS service_name
        FROM bookings b
        LEFT JOIN cars ca ON ca.id = b.car_id
        LEFT JOIN services s ON s.id = b.service_id
        WHERE b.customer_id = ?
        ORDER BY b.created_at DESC
        LIMIT 20
    ");
    $bookings->execute([$id]);
    $brows = $bookings->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable) {
    $brows = [];
}

$name = customer_display_name($c);
$page_title = 'Customer: ' . $name;

require __DIR__ . '/../includes/admin_header.php';
?>

<!-- Breadcrumb -->
<div class="mb-6 flex items-center gap-2 text-sm text-light-1">
  <a href="/admin/customers" class="hover:text-white transition-colors">Customers</a>
  <span>/</span>
  <span class="text-white"><?= h($name) ?></span>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

  <!-- ── Left column: identity card ── -->
  <div class="space-y-6">

    <!-- Profile card -->
    <div class="rounded bg-dark-3 border border-border p-6 text-center">
      <?php $photo_url = $c['photo'] ? customer_file_url($c['photo']) : ''; ?>
      <?php if ($photo_url): ?>
      <img src="<?= h($photo_url) ?>" alt=""
        class="mx-auto mb-4 h-24 w-24 rounded-full object-cover border-2 border-blue-1/40">
      <?php else: ?>
      <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-blue-1/20 border-2 border-blue-1/30 text-3xl font-bold text-blue-1">
        <?= h(strtoupper(substr($name, 0, 2))) ?>
      </div>
      <?php endif; ?>

      <h2 class="text-white text-xl font-semibold"><?= h($name) ?></h2>
      <div class="mt-2 flex items-center justify-center gap-2">
        <?= customer_type_badge($c['customer_type']) ?>
        <?= customer_status_badge($c['status'] ?? 'active') ?>
      </div>
      <p class="text-light-1 mt-3 text-sm"><?= h($c['email'] ?? '') ?></p>
      <p class="text-light-1 text-sm"><?= h($c['phone'] ?? '') ?></p>
      <p class="text-light-1 mt-1 text-xs">Customer since <?= h(display_date($c['created_at'] ?? '')) ?></p>

      <div class="mt-4 flex gap-3 justify-center">
        <a href="/admin/add-customer?edit=<?= $id ?>"
          class="rounded border border-border px-4 py-2 text-sm text-light-1 hover:text-white hover:bg-dark-4 transition-colors">
          Edit
        </a>
        <form method="POST" action="/admin/customers" class="inline"
          onsubmit="return confirm('Delete this customer and all their uploaded files permanently?')">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $id ?>">
          <button type="submit"
            class="rounded border border-red-500/30 bg-red-500/10 px-4 py-2 text-sm text-red-400 hover:bg-red-500/20 transition-colors">
            Delete
          </button>
        </form>
      </div>
    </div>

    <!-- Documents card -->
    <?php
    $docs = [];
    if ($c['customer_type'] === 'individual') {
        if ($c['driver_license_front']) $docs['Driver\'s Licence'] = $c['driver_license_front'];
        if ($c['id_front'])            $docs['ID Document']       = $c['id_front'];
    } else {
        if ($c['certificate_of_incorporation']) $docs['Certificate of Incorporation'] = $c['certificate_of_incorporation'];
        if ($c['org_logo'])                     $docs['Organisation Logo']            = $c['org_logo'];
    }
    ?>
    <?php if ($docs): ?>
    <div class="rounded bg-dark-3 border border-border p-5">
      <h3 class="text-white mb-4 font-semibold">Documents</h3>
      <div class="space-y-3">
        <?php foreach ($docs as $label => $rel_path):
          $url = customer_file_url($rel_path);
          $ext = strtolower(pathinfo($rel_path, PATHINFO_EXTENSION));
          $is_pdf = $ext === 'pdf';
        ?>
        <div>
          <p class="text-light-1 mb-1 text-xs"><?= h($label) ?></p>
          <?php if ($is_pdf): ?>
          <a href="<?= h($url) ?>" target="_blank"
            class="inline-flex items-center gap-2 rounded border border-border px-3 py-2 text-xs text-blue-1 hover:bg-dark-4 transition-colors">
            <i class="icon-file"></i> View PDF
          </a>
          <?php else: ?>
          <a href="<?= h($url) ?>" target="_blank">
            <img src="<?= h($url) ?>" alt="<?= h($label) ?>"
              class="h-24 w-full rounded object-cover border border-border hover:opacity-80 transition-opacity">
          </a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- ── Right column: details + bookings ── -->
  <div class="lg:col-span-2 space-y-6">

    <!-- KYC Details -->
    <div class="rounded bg-dark-3 border border-border p-6">
      <h3 class="text-white mb-5 font-semibold border-b border-border pb-3">
        <?= $c['customer_type'] === 'organization' ? 'Organisation Details' : 'Personal Details' ?>
      </h3>

      <?php if ($c['customer_type'] === 'individual'): ?>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <?php
        $fields = [
          'First Name'          => $c['first_name'],
          'Last Name'           => $c['last_name'],
          'Email'               => $c['email'],
          'Phone'               => $c['phone'],
          'Date of Birth'       => $c['date_of_birth'] ? display_date($c['date_of_birth']) : null,
          'KRA PIN'             => $c['kra_pin'],
          'ID Type'             => $c['id_type'],
          'ID Number'           => $c['id_number'],
          'Driver Licence No.'  => $c['driver_license_number'],
          'Licence Expiry'      => $c['driver_license_expiry'] ? display_date($c['driver_license_expiry']) : null,
        ];
        foreach ($fields as $label => $value): if (!$value) continue; ?>
        <div>
          <p class="text-light-1 text-xs mb-0.5"><?= h($label) ?></p>
          <p class="text-white text-sm"><?= h($value) ?></p>
        </div>
        <?php endforeach; ?>

        <?php if ($c['residential_address']): ?>
        <div class="sm:col-span-2">
          <p class="text-light-1 text-xs mb-0.5">Residential Address</p>
          <p class="text-white text-sm"><?= h($c['residential_address']) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($c['work_address']): ?>
        <div class="sm:col-span-2">
          <p class="text-light-1 text-xs mb-0.5">Work Address</p>
          <p class="text-white text-sm"><?= h($c['work_address']) ?></p>
        </div>
        <?php endif; ?>
      </div>

      <?php else: /* organization */ ?>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <?php
        $fields = [
          'Organisation Name'   => $c['corporate_name'],
          'KRA PIN'             => $c['org_kra_pin'],
          'Contact First Name'  => $c['contact_first_name'],
          'Contact Last Name'   => $c['contact_last_name'],
          'Contact Email'       => $c['org_email'] ?? $c['email'],
          'Contact Phone'       => $c['org_phone'] ?? $c['phone'],
        ];
        foreach ($fields as $label => $value): if (!$value) continue; ?>
        <div>
          <p class="text-light-1 text-xs mb-0.5"><?= h($label) ?></p>
          <p class="text-white text-sm"><?= h($value) ?></p>
        </div>
        <?php endforeach; ?>

        <?php if ($c['org_address']): ?>
        <div class="sm:col-span-2">
          <p class="text-light-1 text-xs mb-0.5">Organisation Address</p>
          <p class="text-white text-sm"><?= h($c['org_address']) ?></p>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Bookings -->
    <div class="rounded bg-dark-3 border border-border">
      <div class="border-b border-border px-6 py-4 flex items-center justify-between">
        <h3 class="text-white font-semibold">Booking History</h3>
        <span class="text-light-1 text-sm"><?= count($brows) ?> booking<?= count($brows) != 1 ? 's' : '' ?></span>
      </div>

      <?php if (empty($brows)): ?>
      <div class="px-6 py-10 text-center text-light-1 text-sm">No bookings yet.</div>
      <?php else: ?>
      <div class="divide-y divide-border">
        <?php foreach ($brows as $b): ?>
        <div class="flex items-center justify-between px-6 py-4">
          <div>
            <a href="/admin/booking-view?ref=<?= h($b['booking_ref']) ?>"
              class="text-white text-sm font-medium hover:text-blue-1 transition-colors">
              <?= h($b['booking_ref']) ?>
            </a>
            <div class="text-light-1 text-xs mt-0.5">
              <?= h($b['make'] . ' ' . $b['model']) ?> · <?= h($b['pickup_date'] ?? '') ?>
            </div>
          </div>
          <div class="text-right">
            <div class="text-white text-sm font-medium">KES <?= number_format((float)($b['total_amount'] ?? 0)) ?></div>
            <div class="mt-0.5">
              <?php
              $sc = booking_status_class($b['status'] ?? 'pending');
              ?>
              <span class="inline-block rounded px-2 py-0.5 text-xs font-medium <?= $sc ?>">
                <?= ucfirst(h($b['status'] ?? 'pending')) ?>
              </span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
