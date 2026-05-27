<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/customer_functions.php';

$db     = get_db();
$errors = [];
$success = false;

// ─── POST handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Honeypot spam check
    if (!empty($_POST['website'])) {
        // Silently succeed (bot)
        $success = true;
    } else {

        $type = $_POST['customer_type'] ?? 'individual';

        $first_name = trim($_POST['first_name']  ?? '');
        $last_name  = trim($_POST['last_name']   ?? '');
        $email      = trim($_POST['email']       ?? '');
        $phone      = trim($_POST['phone']       ?? '');
        $dob        = trim($_POST['date_of_birth'] ?? '');
        $id_type    = trim($_POST['id_type']     ?? '');
        $id_number  = trim($_POST['id_number']   ?? '');
        $dl_number  = trim($_POST['driver_license_number'] ?? '');
        $dl_expiry  = trim($_POST['driver_license_expiry'] ?? '');
        $kra_pin    = trim($_POST['kra_pin']     ?? '');
        $res_addr   = trim($_POST['residential_address'] ?? '');
        $work_addr  = trim($_POST['work_address'] ?? '');

        $corp_name  = trim($_POST['corporate_name']    ?? '');
        $cont_first = trim($_POST['contact_first_name'] ?? '');
        $cont_last  = trim($_POST['contact_last_name']  ?? '');
        $org_email  = trim($_POST['org_email']   ?? '');
        $org_phone  = trim($_POST['org_phone']   ?? '');
        $org_kra    = trim($_POST['org_kra_pin'] ?? '');
        $org_addr   = trim($_POST['org_address'] ?? '');

        // Validation
        if ($type === 'individual') {
            if (!$first_name) $errors[] = 'First name is required.';
            if (!$email && !$phone) $errors[] = 'Please provide an email address or phone number.';
        } else {
            if (!$corp_name) $errors[] = 'Organisation name is required.';
            if (!$org_email && !$org_phone) $errors[] = 'Please provide an email address or phone number.';
        }

        if (empty($errors)) {
            $full_name   = $type === 'individual' ? trim("{$first_name} {$last_name}") : $corp_name;
            $root_email  = $type === 'individual' ? $email : $org_email;
            $root_phone  = $type === 'individual' ? $phone : $org_phone;

            // Duplicate check (same email+phone)
            if ($root_email) {
                $dup = $db->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
                $dup->execute([$root_email]);
                if ($dup->fetchColumn()) {
                    $errors[] = 'An account with this email already exists. Please contact us if you need help.';
                }
            }
        }

        if (empty($errors)) {
            // Insert base row
            $db->prepare("
                INSERT INTO customers (customer_type, full_name, email, phone, status, created_at)
                VALUES (?, ?, ?, ?, 'active', NOW())
            ")->execute([$type, $full_name, $root_email, $root_phone]);
            $customer_id = (int)$db->lastInsertId();

            // File uploads
            $upload_map = [
                'photo'                        => ['photos',    'photo'],
                'driver_license_front'         => ['documents', 'driver_license_front'],
                'id_front'                     => ['documents', 'id_front'],
                'certificate_of_incorporation' => ['documents', 'certificate_of_incorporation'],
                'org_logo'                     => ['photos',    'org_logo'],
            ];

            $file_paths = [];
            foreach ($upload_map as $field => [$subdir, $col]) {
                if (!empty($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    try {
                        $file_paths[$col] = save_customer_file($_FILES[$field], $subdir, $customer_id);
                    } catch (RuntimeException $e) {
                        // Non-fatal — log and continue
                        error_log("Customer file upload error ({$field}): " . $e->getMessage());
                    }
                }
            }

            // Update with all fields
            $set = [
                'first_name'          => $type === 'individual' ? $first_name : null,
                'last_name'           => $type === 'individual' ? $last_name  : null,
                'date_of_birth'       => $dob ?: null,
                'id_type'             => $type === 'individual' ? $id_type : null,
                'id_number'           => $type === 'individual' ? $id_number : null,
                'driver_license_number' => $dl_number ?: null,
                'driver_license_expiry' => $dl_expiry ?: null,
                'kra_pin'             => $type === 'individual' ? ($kra_pin ?: null) : null,
                'residential_address' => $res_addr ?: null,
                'work_address'        => $work_addr ?: null,
                'corporate_name'      => $type === 'organization' ? $corp_name  : null,
                'contact_first_name'  => $type === 'organization' ? $cont_first : null,
                'contact_last_name'   => $type === 'organization' ? $cont_last  : null,
                'org_email'           => $type === 'organization' ? $org_email  : null,
                'org_phone'           => $type === 'organization' ? $org_phone  : null,
                'org_kra_pin'         => $type === 'organization' ? ($org_kra ?: null) : null,
                'org_address'         => $type === 'organization' ? ($org_addr ?: null) : null,
            ];
            foreach ($file_paths as $col => $path) {
                $set[$col] = $path;
            }
            $cols   = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($set)));
            $values = array_values($set);
            $values[] = $customer_id;
            $db->prepare("UPDATE customers SET {$cols} WHERE id = ?")->execute($values);

            $success = true;
        }
    }
}

$page_title       = 'Register';
$page_description = 'Register as a customer of Tripple K Car Hire & Transfers.';
$current_page     = 'register';
$nav_white        = true;
require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow bg-dark-1">

  <!-- Hero banner -->
  <section class="relative flex items-center justify-center bg-cover bg-center py-16 lg:py-20"
    style="background-image:url(/assets/images/cars/hero-bg.jpg)">
    <div class="absolute inset-0 bg-dark-1/60"></div>
    <div class="container relative text-center">
      <h1 class="text-3xl font-semibold text-white md:text-5xl">Customer Registration</h1>
      <p class="mt-3 text-light-1">Complete the form below to register with Tripple K Car Hire.</p>
    </div>
  </section>

  <section class="py-12 lg:py-16">
    <div class="container max-w-3xl">

      <?php if ($success): ?>
      <!-- ── Success state ── -->
      <div class="rounded bg-dark-3 border border-green-500/30 p-10 text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-500/10 border border-green-500/30">
          <i class="icon-check text-green-400 text-3xl"></i>
        </div>
        <h2 class="text-white text-2xl font-semibold mb-3">Registration Submitted!</h2>
        <p class="text-light-1 mb-6">
          Thank you for registering with Tripple K. Our team will review your details and get in touch with you shortly.
        </p>
        <a href="/" class="inline-block bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-8 py-3 font-semibold transition-colors">
          Back to Homepage
        </a>
      </div>

      <?php else: ?>
      <!-- ── Form ── -->

      <?php if ($errors): ?>
      <div class="mb-6 rounded border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-400">
        <strong>Please fix the following:</strong>
        <ul class="mt-1 list-disc list-inside space-y-0.5">
          <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <form method="POST" action="/register" enctype="multipart/form-data"
        x-data="{
          type: '<?= h($_POST['customer_type'] ?? 'individual') ?>',
          step: 1
        }">

        <!-- Honeypot -->
        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

        <!-- Type selector -->
        <div class="mb-8 rounded bg-dark-3 border border-border p-1 flex">
          <button type="button" @click="type='individual'; step=1"
            :class="type==='individual'
              ? 'bg-blue-1 text-dark-1'
              : 'text-light-1 hover:text-white'"
            class="flex-1 rounded py-3 text-sm font-semibold transition-colors">
            <i class="icon-user mr-2"></i>Individual
          </button>
          <button type="button" @click="type='organization'; step=1"
            :class="type==='organization'
              ? 'bg-blue-1 text-dark-1'
              : 'text-light-1 hover:text-white'"
            class="flex-1 rounded py-3 text-sm font-semibold transition-colors">
            <i class="icon-building mr-2"></i>Organisation
          </button>
        </div>
        <input type="hidden" name="customer_type" :value="type">

        <!-- ══ INDIVIDUAL FORM ══ -->
        <div x-show="type==='individual'" x-cloak class="space-y-6">

          <!-- Step 1: Personal info -->
          <div x-show="step===1" x-cloak>
            <div class="mb-4 flex items-center gap-3">
              <div class="h-8 w-8 rounded-full bg-blue-1 text-dark-1 flex items-center justify-center text-sm font-bold">1</div>
              <h2 class="text-white font-semibold text-lg">Personal Information</h2>
            </div>

            <div class="rounded bg-dark-3 border border-border p-6 space-y-4">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="pub-label">First Name *</label>
                  <input type="text" name="first_name" value="<?= h($_POST['first_name'] ?? '') ?>"
                    class="pub-input" required>
                </div>
                <div>
                  <label class="pub-label">Last Name</label>
                  <input type="text" name="last_name" value="<?= h($_POST['last_name'] ?? '') ?>"
                    class="pub-input">
                </div>
                <div>
                  <label class="pub-label">Email Address</label>
                  <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>"
                    class="pub-input">
                </div>
                <div>
                  <label class="pub-label">Phone Number</label>
                  <input type="tel" name="phone" value="<?= h($_POST['phone'] ?? '') ?>"
                    class="pub-input" placeholder="+254 …">
                </div>
                <div>
                  <label class="pub-label">Date of Birth</label>
                  <input type="date" name="date_of_birth"
                    value="<?= h($_POST['date_of_birth'] ?? '') ?>"
                    class="pub-input">
                </div>
                <div>
                  <label class="pub-label">KRA PIN</label>
                  <input type="text" name="kra_pin" value="<?= h($_POST['kra_pin'] ?? '') ?>"
                    class="pub-input" placeholder="A123456789B">
                </div>
              </div>
              <div>
                <label class="pub-label">Residential Address</label>
                <input type="text" name="residential_address"
                  value="<?= h($_POST['residential_address'] ?? '') ?>"
                  class="pub-input" placeholder="Street, City, County">
              </div>
              <div>
                <label class="pub-label">Work Address <span class="text-light-1 font-normal">(optional)</span></label>
                <input type="text" name="work_address"
                  value="<?= h($_POST['work_address'] ?? '') ?>"
                  class="pub-input" placeholder="Street, City, County">
              </div>
            </div>

            <div class="mt-4 flex justify-end">
              <button type="button" @click="step=2"
                class="bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-8 py-3 font-semibold transition-colors">
                Continue <i class="icon-arrow-right ml-1"></i>
              </button>
            </div>
          </div>

          <!-- Step 2: ID document -->
          <div x-show="step===2" x-cloak>
            <div class="mb-4 flex items-center gap-3">
              <div class="h-8 w-8 rounded-full bg-blue-1 text-dark-1 flex items-center justify-center text-sm font-bold">2</div>
              <h2 class="text-white font-semibold text-lg">ID Document</h2>
            </div>

            <div class="rounded bg-dark-3 border border-border p-6 space-y-4">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="pub-label">ID Type</label>
                  <select name="id_type" class="pub-input">
                    <option value="">— Select —</option>
                    <?php foreach (['National ID', 'Passport', 'Military ID', 'Alien ID'] as $t): ?>
                    <option value="<?= h($t) ?>" <?= ($_POST['id_type'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="pub-label">ID Number</label>
                  <input type="text" name="id_number" value="<?= h($_POST['id_number'] ?? '') ?>"
                    class="pub-input">
                </div>
              </div>
              <div>
                <label class="pub-label">ID Front Photo <span class="text-light-1 font-normal">(JPG, PNG or PDF · max 10MB)</span></label>
                <input type="file" name="id_front" accept="image/*,application/pdf" class="pub-file">
              </div>
            </div>

            <div class="mt-4 flex gap-3 justify-between">
              <button type="button" @click="step=1"
                class="rounded border border-border px-6 py-3 text-sm text-light-1 hover:text-white transition-colors">
                <i class="icon-arrow-left mr-1"></i> Back
              </button>
              <button type="button" @click="step=3"
                class="bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-8 py-3 font-semibold transition-colors">
                Continue <i class="icon-arrow-right ml-1"></i>
              </button>
            </div>
          </div>

          <!-- Step 3: Driver's licence + photo -->
          <div x-show="step===3" x-cloak>
            <div class="mb-4 flex items-center gap-3">
              <div class="h-8 w-8 rounded-full bg-blue-1 text-dark-1 flex items-center justify-center text-sm font-bold">3</div>
              <h2 class="text-white font-semibold text-lg">Driver's Licence & Photo</h2>
            </div>

            <div class="rounded bg-dark-3 border border-border p-6 space-y-4">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="pub-label">Licence Number</label>
                  <input type="text" name="driver_license_number"
                    value="<?= h($_POST['driver_license_number'] ?? '') ?>"
                    class="pub-input">
                </div>
                <div>
                  <label class="pub-label">Expiry Date</label>
                  <input type="date" name="driver_license_expiry"
                    value="<?= h($_POST['driver_license_expiry'] ?? '') ?>"
                    class="pub-input">
                </div>
              </div>
              <div>
                <label class="pub-label">Licence Front Photo <span class="text-light-1 font-normal">(JPG, PNG or PDF · max 10MB)</span></label>
                <input type="file" name="driver_license_front" accept="image/*,application/pdf" class="pub-file">
              </div>

              <hr class="border-border">

              <div>
                <label class="pub-label">Profile Photo <span class="text-light-1 font-normal">(optional · JPG/PNG/WebP · max 10MB)</span></label>
                <input type="file" name="photo" accept="image/*" class="pub-file">
              </div>
            </div>

            <div class="mt-4 flex gap-3 justify-between">
              <button type="button" @click="step=2"
                class="rounded border border-border px-6 py-3 text-sm text-light-1 hover:text-white transition-colors">
                <i class="icon-arrow-left mr-1"></i> Back
              </button>
              <button type="submit"
                class="bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-8 py-3 font-semibold transition-colors">
                Submit Registration
              </button>
            </div>
          </div>

          <!-- Step dots (individual) -->
          <div class="flex items-center justify-center gap-2 mt-2">
            <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="h-2 rounded-full transition-all"
              :class="step === <?= $i ?> ? 'w-6 bg-blue-1' : 'w-2 bg-dark-4'"></div>
            <?php endfor; ?>
          </div>

        </div><!-- /individual -->


        <!-- ══ ORGANISATION FORM ══ -->
        <div x-show="type==='organization'" x-cloak class="space-y-6">

          <!-- Step 1: Org details -->
          <div x-show="step===1" x-cloak>
            <div class="mb-4 flex items-center gap-3">
              <div class="h-8 w-8 rounded-full bg-blue-1 text-dark-1 flex items-center justify-center text-sm font-bold">1</div>
              <h2 class="text-white font-semibold text-lg">Organisation Details</h2>
            </div>

            <div class="rounded bg-dark-3 border border-border p-6 space-y-4">
              <div>
                <label class="pub-label">Organisation Name *</label>
                <input type="text" name="corporate_name" value="<?= h($_POST['corporate_name'] ?? '') ?>"
                  class="pub-input" required>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="pub-label">KRA PIN</label>
                  <input type="text" name="org_kra_pin" value="<?= h($_POST['org_kra_pin'] ?? '') ?>"
                    class="pub-input" placeholder="P051234567A">
                </div>
                <div>
                  <label class="pub-label">Organisation Email</label>
                  <input type="email" name="org_email" value="<?= h($_POST['org_email'] ?? '') ?>"
                    class="pub-input">
                </div>
                <div>
                  <label class="pub-label">Organisation Phone</label>
                  <input type="tel" name="org_phone" value="<?= h($_POST['org_phone'] ?? '') ?>"
                    class="pub-input" placeholder="+254 …">
                </div>
              </div>
              <div>
                <label class="pub-label">Organisation Address</label>
                <input type="text" name="org_address" value="<?= h($_POST['org_address'] ?? '') ?>"
                  class="pub-input" placeholder="Street, City, County">
              </div>
            </div>

            <div class="mt-4 flex justify-end">
              <button type="button" @click="step=2"
                class="bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-8 py-3 font-semibold transition-colors">
                Continue <i class="icon-arrow-right ml-1"></i>
              </button>
            </div>
          </div>

          <!-- Step 2: Contact person + documents -->
          <div x-show="step===2" x-cloak>
            <div class="mb-4 flex items-center gap-3">
              <div class="h-8 w-8 rounded-full bg-blue-1 text-dark-1 flex items-center justify-center text-sm font-bold">2</div>
              <h2 class="text-white font-semibold text-lg">Contact Person & Documents</h2>
            </div>

            <div class="rounded bg-dark-3 border border-border p-6 space-y-4">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="pub-label">Contact First Name</label>
                  <input type="text" name="contact_first_name"
                    value="<?= h($_POST['contact_first_name'] ?? '') ?>" class="pub-input">
                </div>
                <div>
                  <label class="pub-label">Contact Last Name</label>
                  <input type="text" name="contact_last_name"
                    value="<?= h($_POST['contact_last_name'] ?? '') ?>" class="pub-input">
                </div>
              </div>

              <hr class="border-border">

              <div>
                <label class="pub-label">
                  Certificate of Incorporation <span class="text-light-1 font-normal">(JPG, PNG or PDF · max 10MB)</span>
                </label>
                <input type="file" name="certificate_of_incorporation" accept="image/*,application/pdf"
                  class="pub-file">
              </div>
              <div>
                <label class="pub-label">
                  Organisation Logo <span class="text-light-1 font-normal">(optional · JPG/PNG/WebP · max 10MB)</span>
                </label>
                <input type="file" name="org_logo" accept="image/*" class="pub-file">
              </div>
            </div>

            <div class="mt-4 flex gap-3 justify-between">
              <button type="button" @click="step=1"
                class="rounded border border-border px-6 py-3 text-sm text-light-1 hover:text-white transition-colors">
                <i class="icon-arrow-left mr-1"></i> Back
              </button>
              <button type="submit"
                class="bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-8 py-3 font-semibold transition-colors">
                Submit Registration
              </button>
            </div>
          </div>

          <!-- Step dots (org) -->
          <div class="flex items-center justify-center gap-2 mt-2">
            <?php for ($i = 1; $i <= 2; $i++): ?>
            <div class="h-2 rounded-full transition-all"
              :class="step === <?= $i ?> ? 'w-6 bg-blue-1' : 'w-2 bg-dark-4'"></div>
            <?php endfor; ?>
          </div>

        </div><!-- /organisation -->

      </form>
      <?php endif; ?>

    </div><!-- /container -->
  </section>

</main>

<style>
.pub-label{display:block;margin-bottom:.375rem;font-size:.875rem;font-weight:500;color:#fff}
.pub-input{width:100%;border-radius:.375rem;border:1px solid #1a1a1a;background:#222;color:#fff;padding:.75rem 1rem;font-size:.875rem;outline:none;transition:border-color .2s}
.pub-input::placeholder{color:#a89878}
.pub-input:focus{border-color:#c8942a}
.pub-file{display:block;width:100%;font-size:.875rem;color:#a89878;cursor:pointer}
.pub-file::file-selector-button{margin-right:1rem;border-radius:.375rem;border:0;background:#222;padding:.625rem 1rem;font-size:.875rem;font-weight:500;color:#fff;cursor:pointer;transition:background .2s}
.pub-file::file-selector-button:hover{background:#1a1a1a}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
