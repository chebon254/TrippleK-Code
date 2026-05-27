<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer_functions.php';

require_admin_auth();

$db = get_db();

// Edit mode?
$edit_id = (int)($_GET['edit'] ?? 0);
$editing = $edit_id > 0;
$c       = null;

if ($editing) {
    $s = $db->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
    $s->execute([$edit_id]);
    $c = $s->fetch(PDO::FETCH_ASSOC);
    if (!$c) {
        flash('error', 'Customer not found.');
        redirect('/admin/customers');
    }
}

$page_title = $editing ? 'Edit Customer' : 'Add Customer';
$errors     = [];

// ─── POST handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request.');
        redirect('/admin/customers');
    }

    $type = $_POST['customer_type'] ?? 'individual';

    // ── Individual fields ──
    $first_name  = trim($_POST['first_name']  ?? '');
    $last_name   = trim($_POST['last_name']   ?? '');
    $email       = trim($_POST['email']       ?? '');
    $phone       = trim($_POST['phone']       ?? '');
    $dob         = trim($_POST['date_of_birth'] ?? '');
    $id_type     = trim($_POST['id_type']     ?? '');
    $id_number   = trim($_POST['id_number']   ?? '');
    $dl_number   = trim($_POST['driver_license_number'] ?? '');
    $dl_expiry   = trim($_POST['driver_license_expiry'] ?? '');
    $kra_pin     = trim($_POST['kra_pin']     ?? '');
    $res_addr    = trim($_POST['residential_address'] ?? '');
    $work_addr   = trim($_POST['work_address'] ?? '');

    // ── Organisation fields ──
    $corp_name   = trim($_POST['corporate_name']    ?? '');
    $cont_first  = trim($_POST['contact_first_name'] ?? '');
    $cont_last   = trim($_POST['contact_last_name']  ?? '');
    $org_email   = trim($_POST['org_email']   ?? '');
    $org_phone   = trim($_POST['org_phone']   ?? '');
    $org_kra     = trim($_POST['org_kra_pin'] ?? '');
    $org_addr    = trim($_POST['org_address'] ?? '');

    $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive']) ? $_POST['status'] : 'active';

    // Validation
    if ($type === 'individual') {
        if (!$first_name) $errors[] = 'First name is required.';
        if (!$email && !$phone) $errors[] = 'Email or phone is required.';
    } else {
        if (!$corp_name) $errors[] = 'Organisation name is required.';
        if (!$org_email && !$org_phone) $errors[] = 'Email or phone is required.';
    }

    // Build full_name for backwards compat
    $full_name = $type === 'individual'
        ? trim("{$first_name} {$last_name}")
        : $corp_name;

    // Use correct email/phone for root columns
    $root_email = $type === 'individual' ? $email : $org_email;
    $root_phone = $type === 'individual' ? $phone : $org_phone;

    if (empty($errors)) {

        if ($editing) {
            $customer_id = $edit_id;
        } else {
            // Insert minimal row first to get the ID (needed for file paths)
            $db->prepare("
                INSERT INTO customers (customer_type, full_name, email, phone, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ")->execute([$type, $full_name, $root_email, $root_phone, $status]);
            $customer_id = (int)$db->lastInsertId();
        }

        // ── File uploads ──
        $uploads = [
            'photo'                      => ['photos',    'photo'],
            'driver_license_front'       => ['documents', 'driver_license_front'],
            'id_front'                   => ['documents', 'id_front'],
            'certificate_of_incorporation' => ['documents', 'certificate_of_incorporation'],
            'org_logo'                   => ['photos',    'org_logo'],
        ];

        $file_paths = [];
        foreach ($uploads as $field => [$subdir, $col]) {
            if (!empty($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                try {
                    $file_paths[$col] = save_customer_file($_FILES[$field], $subdir, $customer_id);
                } catch (RuntimeException $e) {
                    $errors[] = "Upload error ({$field}): " . $e->getMessage();
                }
            }
        }

        if (empty($errors)) {
            // Build update SET
            $set = [
                'customer_type'      => $type,
                'full_name'          => $full_name,
                'first_name'         => $type === 'individual' ? $first_name : null,
                'last_name'          => $type === 'individual' ? $last_name  : null,
                'email'              => $root_email,
                'phone'              => $root_phone,
                'date_of_birth'      => $dob ?: null,
                'id_type'            => $type === 'individual' ? $id_type : null,
                'id_number'          => $type === 'individual' ? $id_number : null,
                'driver_license_number' => $dl_number ?: null,
                'driver_license_expiry' => $dl_expiry ?: null,
                'kra_pin'            => $type === 'individual' ? ($kra_pin ?: null) : null,
                'residential_address'=> $res_addr ?: null,
                'work_address'       => $work_addr ?: null,
                'corporate_name'     => $type === 'organization' ? $corp_name : null,
                'contact_first_name' => $type === 'organization' ? $cont_first : null,
                'contact_last_name'  => $type === 'organization' ? $cont_last  : null,
                'org_email'          => $type === 'organization' ? $org_email  : null,
                'org_phone'          => $type === 'organization' ? $org_phone  : null,
                'org_kra_pin'        => $type === 'organization' ? ($org_kra ?: null) : null,
                'org_address'        => $type === 'organization' ? ($org_addr ?: null) : null,
                'status'             => $status,
                'updated_at'         => date('Y-m-d H:i:s'),
            ];

            // Merge uploaded file paths
            foreach ($file_paths as $col => $path) {
                $set[$col] = $path;
            }

            $cols   = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($set)));
            $values = array_values($set);
            $values[] = $customer_id;

            $db->prepare("UPDATE customers SET {$cols} WHERE id = ?")->execute($values);

            flash('success', $editing ? 'Customer updated.' : 'Customer added successfully.');
            redirect('/admin/customer-view?id=' . $customer_id);
        } else {
            // If we created the row but hit a file error, clean up
            if (!$editing) {
                $db->prepare('DELETE FROM customers WHERE id = ?')->execute([$customer_id]);
                delete_customer_files($customer_id);
            }
        }
    }
}

$v = fn($key, $default = '') => h($c[$key] ?? ($_POST[$key] ?? $default));

require __DIR__ . '/../includes/admin_header.php';
?>

<!-- Breadcrumb -->
<div class="mb-6 flex items-center gap-2 text-sm text-light-1">
  <a href="/admin/customers" class="hover:text-white transition-colors">Customers</a>
  <span>/</span>
  <span class="text-white"><?= $editing ? 'Edit Customer' : 'Add Customer' ?></span>
</div>

<!-- Errors -->
<?php if ($errors): ?>
<div class="mb-6 rounded border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-400">
  <strong>Please fix the following:</strong>
  <ul class="mt-1 list-disc list-inside space-y-0.5">
    <?php foreach ($errors as $e): ?>
    <li><?= h($e) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="POST" action="/admin/add-customer<?= $editing ? '?edit=' . $edit_id : '' ?>"
  enctype="multipart/form-data"
  x-data="{
    type: '<?= h($c['customer_type'] ?? ($_POST['customer_type'] ?? 'individual')) ?>'
  }">

  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

  <!-- Type tabs -->
  <div class="mb-6 flex gap-3">
    <button type="button" @click="type='individual'"
      :class="type==='individual'
        ? 'bg-blue-1 text-dark-1 border-blue-1'
        : 'bg-dark-3 text-light-1 border-border hover:bg-dark-4'"
      class="rounded border px-6 py-2.5 text-sm font-semibold transition-colors">
      Individual
    </button>
    <button type="button" @click="type='organization'"
      :class="type==='organization'
        ? 'bg-blue-1 text-dark-1 border-blue-1'
        : 'bg-dark-3 text-light-1 border-border hover:bg-dark-4'"
      class="rounded border px-6 py-2.5 text-sm font-semibold transition-colors">
      Organisation
    </button>
    <input type="hidden" name="customer_type" :value="type">
  </div>

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    <!-- ── Main form ── -->
    <div class="lg:col-span-2 space-y-6">

      <!-- Individual section -->
      <div x-show="type==='individual'" x-cloak>
        <div class="rounded bg-dark-3 border border-border p-6 space-y-5">
          <h2 class="text-white font-semibold border-b border-border pb-3">Personal Information</h2>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">First Name *</label>
              <input type="text" name="first_name" value="<?= $v('first_name') ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">Last Name</label>
              <input type="text" name="last_name" value="<?= $v('last_name') ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">Email</label>
              <input type="email" name="email" value="<?= $v('email') ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">Phone</label>
              <input type="tel" name="phone" value="<?= $v('phone') ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">Date of Birth</label>
              <input type="date" name="date_of_birth"
                value="<?= h($c['date_of_birth'] ?? ($_POST['date_of_birth'] ?? '')) ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">KRA PIN</label>
              <input type="text" name="kra_pin" value="<?= $v('kra_pin') ?>"
                class="input-field w-full">
            </div>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Residential Address</label>
            <input type="text" name="residential_address" value="<?= $v('residential_address') ?>"
              class="input-field w-full">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Work Address</label>
            <input type="text" name="work_address" value="<?= $v('work_address') ?>"
              class="input-field w-full">
          </div>
        </div>

        <!-- ID Document -->
        <div class="rounded bg-dark-3 border border-border p-6 space-y-4 mt-6">
          <h2 class="text-white font-semibold border-b border-border pb-3">ID Document</h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">ID Type</label>
              <select name="id_type" class="input-field w-full">
                <option value="">— Select —</option>
                <?php foreach (['National ID', 'Passport', 'Military ID', 'Alien ID'] as $t): ?>
                <option value="<?= h($t) ?>" <?= ($c['id_type'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">ID Number</label>
              <input type="text" name="id_number" value="<?= $v('id_number') ?>"
                class="input-field w-full">
            </div>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">
              ID Front Photo <span class="text-light-1 font-normal">(JPG/PNG/PDF, max 10MB)</span>
            </label>
            <?php if (!empty($c['id_front'])): ?>
            <div class="mb-2">
              <?php if (strtolower(pathinfo($c['id_front'], PATHINFO_EXTENSION)) === 'pdf'): ?>
              <a href="<?= h(customer_file_url($c['id_front'])) ?>" target="_blank"
                class="text-blue-1 text-xs hover:underline">View current PDF</a>
              <?php else: ?>
              <img src="<?= h(customer_file_url($c['id_front'])) ?>" alt=""
                class="h-20 rounded object-cover border border-border">
              <p class="text-xs text-light-1 mt-1">Upload a new file to replace.</p>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            <input type="file" name="id_front" accept="image/*,application/pdf"
              class="file-input w-full">
          </div>
        </div>

        <!-- Driver's Licence -->
        <div class="rounded bg-dark-3 border border-border p-6 space-y-4 mt-6">
          <h2 class="text-white font-semibold border-b border-border pb-3">Driver's Licence</h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">Licence Number</label>
              <input type="text" name="driver_license_number"
                value="<?= $v('driver_license_number') ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">Expiry Date</label>
              <input type="date" name="driver_license_expiry"
                value="<?= h($c['driver_license_expiry'] ?? ($_POST['driver_license_expiry'] ?? '')) ?>"
                class="input-field w-full">
            </div>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">
              Licence Front Photo <span class="text-light-1 font-normal">(JPG/PNG/PDF, max 10MB)</span>
            </label>
            <?php if (!empty($c['driver_license_front'])): ?>
            <div class="mb-2">
              <?php if (strtolower(pathinfo($c['driver_license_front'], PATHINFO_EXTENSION)) === 'pdf'): ?>
              <a href="<?= h(customer_file_url($c['driver_license_front'])) ?>" target="_blank"
                class="text-blue-1 text-xs hover:underline">View current PDF</a>
              <?php else: ?>
              <img src="<?= h(customer_file_url($c['driver_license_front'])) ?>" alt=""
                class="h-20 rounded object-cover border border-border">
              <p class="text-xs text-light-1 mt-1">Upload a new file to replace.</p>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            <input type="file" name="driver_license_front" accept="image/*,application/pdf"
              class="file-input w-full">
          </div>
        </div>
      </div>

      <!-- Organisation section -->
      <div x-show="type==='organization'" x-cloak>
        <div class="rounded bg-dark-3 border border-border p-6 space-y-5">
          <h2 class="text-white font-semibold border-b border-border pb-3">Organisation Details</h2>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <label class="mb-1.5 block text-sm font-medium text-white">Organisation Name *</label>
              <input type="text" name="corporate_name" value="<?= $v('corporate_name') ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">Contact First Name</label>
              <input type="text" name="contact_first_name" value="<?= $v('contact_first_name') ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">Contact Last Name</label>
              <input type="text" name="contact_last_name" value="<?= $v('contact_last_name') ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">Organisation Email</label>
              <input type="email" name="org_email" value="<?= $v('org_email') ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">Organisation Phone</label>
              <input type="tel" name="org_phone" value="<?= $v('org_phone') ?>"
                class="input-field w-full">
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-white">KRA PIN</label>
              <input type="text" name="org_kra_pin" value="<?= $v('org_kra_pin') ?>"
                class="input-field w-full">
            </div>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Organisation Address</label>
            <input type="text" name="org_address" value="<?= $v('org_address') ?>"
              class="input-field w-full">
          </div>
        </div>

        <!-- Org Documents -->
        <div class="rounded bg-dark-3 border border-border p-6 space-y-4 mt-6">
          <h2 class="text-white font-semibold border-b border-border pb-3">Documents</h2>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">
              Certificate of Incorporation <span class="text-light-1 font-normal">(JPG/PNG/PDF, max 10MB)</span>
            </label>
            <?php if (!empty($c['certificate_of_incorporation'])): ?>
            <div class="mb-2">
              <a href="<?= h(customer_file_url($c['certificate_of_incorporation'])) ?>" target="_blank"
                class="text-blue-1 text-xs hover:underline">View current file</a>
              <p class="text-xs text-light-1 mt-1">Upload a new file to replace.</p>
            </div>
            <?php endif; ?>
            <input type="file" name="certificate_of_incorporation" accept="image/*,application/pdf"
              class="file-input w-full">
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">
              Organisation Logo <span class="text-light-1 font-normal">(JPG/PNG/WebP, max 10MB)</span>
            </label>
            <?php if (!empty($c['org_logo'])): ?>
            <div class="mb-2">
              <img src="<?= h(customer_file_url($c['org_logo'])) ?>" alt=""
                class="h-20 rounded object-cover border border-border">
              <p class="text-xs text-light-1 mt-1">Upload a new file to replace.</p>
            </div>
            <?php endif; ?>
            <input type="file" name="org_logo" accept="image/*"
              class="file-input w-full">
          </div>
        </div>
      </div>

    </div>

    <!-- ── Sidebar: photo + status ── -->
    <div class="space-y-6">

      <!-- Photo -->
      <div class="rounded bg-dark-3 border border-border p-5">
        <h3 class="text-white mb-4 font-semibold border-b border-border pb-3">
          Profile Photo
        </h3>
        <?php if (!empty($c['photo'])): ?>
        <div class="mb-3">
          <img src="<?= h(customer_file_url($c['photo'])) ?>" alt=""
            class="h-28 w-28 rounded-full object-cover border border-border mx-auto">
          <p class="text-xs text-light-1 mt-2 text-center">Upload a new photo to replace.</p>
        </div>
        <?php endif; ?>
        <input type="file" name="photo" accept="image/*" class="file-input w-full">
        <p class="text-xs text-light-1 mt-2">JPG/PNG/WebP, max 10MB.</p>
      </div>

      <!-- Status -->
      <div class="rounded bg-dark-3 border border-border p-5">
        <h3 class="text-white mb-4 font-semibold border-b border-border pb-3">Status</h3>
        <select name="status" class="input-field w-full">
          <?php foreach (['active', 'inactive'] as $s): ?>
          <option value="<?= $s ?>"
            <?= ($c['status'] ?? 'active') === $s ? 'selected' : '' ?>>
            <?= ucfirst($s) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Submit -->
      <div class="flex flex-col gap-3">
        <button type="submit"
          class="bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-6 py-3 text-sm font-semibold transition-colors w-full">
          <?= $editing ? 'Save Changes' : 'Add Customer' ?>
        </button>
        <a href="/admin/customers"
          class="rounded border border-border px-6 py-3 text-sm text-light-1 hover:text-white text-center transition-colors">
          Cancel
        </a>
      </div>
    </div>

  </div><!-- /grid -->
</form>

<style>
.input-field{width:100%;border-radius:.375rem;border:1px solid #1a1a1a;background:#222;color:#fff;padding:.625rem .75rem;font-size:.875rem;outline:none;transition:border-color .2s}
.input-field:focus{border-color:#c8942a}
.input-field::placeholder{color:#a89878}
.file-input{display:block;width:100%;font-size:.875rem;color:#a89878;cursor:pointer}
.file-input::file-selector-button{margin-right:1rem;border-radius:.375rem;border:0;background:#222;padding:.5rem 1rem;font-size:.875rem;font-weight:500;color:#fff;cursor:pointer;transition:background .2s}
.file-input::file-selector-button:hover{background:#1a1a1a}
</style>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
