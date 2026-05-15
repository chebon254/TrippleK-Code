<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_auth();

$page_title = 'Settings';
$db         = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request.');
        redirect('/admin/settings.php');
    }

    // Handle admin password change separately
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            flash('error', 'Passwords do not match.');
            redirect('/admin/settings.php');
        }
        if (strlen($_POST['new_password']) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            redirect('/admin/settings.php');
        }
        $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare('UPDATE admin SET password_hash = ? WHERE id = ?')
           ->execute([$hash, $_SESSION['admin_id']]);
        flash('success', 'Password changed successfully.');
        redirect('/admin/settings.php');
    }

    // Save settings
    $allowed_keys = [
        'company_name','company_tagline','company_phone','company_whatsapp',
        'company_email','company_address','company_hours','company_website','maps_embed_url',
        'kra_pin','tra_license','security_deposit_min','security_deposit_max',
        'mpesa_env','mpesa_shortcode','mpesa_consumer_key','mpesa_consumer_secret','mpesa_passkey',
        'flutterwave_env','flutterwave_public_key','flutterwave_secret_key','flutterwave_hash',
        'invoice_prefix','invoice_footer','invoice_bank_name','invoice_bank_account','invoice_bank_branch',
    ];

    $stmt = $db->prepare('UPDATE settings SET setting_val = ? WHERE setting_key = ?');
    foreach ($allowed_keys as $key) {
        if (isset($_POST[$key])) {
            $stmt->execute([trim($_POST[$key]), $key]);
        }
    }

    flash('success', 'Settings saved successfully.');
    redirect('/admin/settings.php');
}

// Load all settings
$rows = $db->query('SELECT setting_key, setting_val FROM settings')->fetchAll();
$s    = array_column($rows, 'setting_val', 'setting_key');

require __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-8">
  <h1 class="text-dark-1 mb-2 text-3xl font-semibold">Settings</h1>
  <p class="text-light-1">Configure your site, payments, and business details</p>
</div>

<form method="POST" action="/admin/settings.php">
  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

  <div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">

      <!-- General -->
      <div class="rounded bg-white p-6 shadow-[0_10px_30px_0_#05103608]">
        <h2 class="mb-4 border-b border-gray-100 pb-3 text-base font-medium text-dark-1">Company Information</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <?php
          $general_fields = [
            ['company_name',    'Company Name',    'text',  'Tripple K Car Hire & Transfers'],
            ['company_tagline', 'Tagline',         'text',  'Your road, our wheels'],
            ['company_phone',   'Primary Phone',   'text',  '+254 700 000 000'],
            ['company_whatsapp','WhatsApp Number', 'text',  '+254700000000'],
            ['company_email',   'Email Address',   'email', 'info@tripplek.co.ke'],
            ['company_address', 'Physical Address','text',  'Nairobi, Kenya'],
          ];
          foreach ($general_fields as [$key, $label, $type, $placeholder]):
          ?>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1"><?= $label ?></label>
            <input type="<?= $type ?>" name="<?= $key ?>" value="<?= h($s[$key] ?? '') ?>"
              placeholder="<?= h($placeholder) ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
          <?php endforeach; ?>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Website URL</label>
            <input type="text" name="company_website" value="<?= h($s['company_website'] ?? '') ?>"
              placeholder="https://tripplek.co.ke"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Business Hours</label>
            <input type="text" name="company_hours" value="<?= h($s['company_hours'] ?? '') ?>"
              placeholder="Mon–Sat 7am–8pm, Sun 8am–6pm"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Google Maps Embed URL</label>
            <input type="text" name="maps_embed_url" value="<?= h($s['maps_embed_url'] ?? '') ?>"
              placeholder="https://www.google.com/maps/embed?pb=..."
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
            <p class="mt-1 text-xs text-gray-400">Paste the embed src URL from Google Maps → Share → Embed a map.</p>
          </div>
        </div>
      </div>

      <!-- Legal -->
      <div class="rounded bg-white p-6 shadow-[0_10px_30px_0_#05103608]">
        <h2 class="mb-4 border-b border-gray-100 pb-3 text-base font-medium text-dark-1">Legal & Compliance</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">KRA PIN</label>
            <input type="text" name="kra_pin" value="<?= h($s['kra_pin'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">TRA License Number</label>
            <input type="text" name="tra_license" value="<?= h($s['tra_license'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Min Security Deposit (KES)</label>
            <input type="number" name="security_deposit_min" value="<?= h($s['security_deposit_min'] ?? '15000') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Max Security Deposit (KES)</label>
            <input type="number" name="security_deposit_max" value="<?= h($s['security_deposit_max'] ?? '50000') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
        </div>
      </div>

      <!-- M-Pesa -->
      <div class="rounded bg-white p-6 shadow-[0_10px_30px_0_#05103608]">
        <h2 class="mb-1 border-b border-gray-100 pb-3 text-base font-medium text-dark-1">M-Pesa (Daraja API)</h2>
        <p class="mb-4 text-xs text-gray-400">Get credentials from <strong>developer.safaricom.co.ke</strong></p>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Environment</label>
            <select name="mpesa_env" class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
              <option value="sandbox" <?= ($s['mpesa_env'] ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Testing)</option>
              <option value="live"    <?= ($s['mpesa_env'] ?? '') === 'live'    ? 'selected' : '' ?>>Live (Production)</option>
            </select>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Paybill / Till Number</label>
            <input type="text" name="mpesa_shortcode" value="<?= h($s['mpesa_shortcode'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Consumer Key</label>
            <input type="text" name="mpesa_consumer_key" value="<?= h($s['mpesa_consumer_key'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none font-mono text-xs">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Consumer Secret</label>
            <input type="password" name="mpesa_consumer_secret" value="<?= h($s['mpesa_consumer_secret'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none font-mono text-xs">
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Passkey (Lipa Na MPesa)</label>
            <input type="password" name="mpesa_passkey" value="<?= h($s['mpesa_passkey'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none font-mono text-xs">
          </div>
        </div>
        <p class="mt-3 text-xs text-gray-400">
          Callback URL (register this with Safaricom):<br>
          <code class="bg-gray-50 px-2 py-1 rounded text-xs"><?= APP_URL ?>/api/mpesa-callback.php</code>
        </p>
      </div>

      <!-- Flutterwave -->
      <div class="rounded bg-white p-6 shadow-[0_10px_30px_0_#05103608]">
        <h2 class="mb-1 border-b border-gray-100 pb-3 text-base font-medium text-dark-1">Flutterwave (Cards & Airtel Money)</h2>
        <p class="mb-4 text-xs text-gray-400">Get credentials from <strong>dashboard.flutterwave.com</strong></p>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Environment</label>
            <select name="flutterwave_env" class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
              <option value="test" <?= ($s['flutterwave_env'] ?? '') === 'test' ? 'selected' : '' ?>>Test</option>
              <option value="live" <?= ($s['flutterwave_env'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option>
            </select>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Public Key</label>
            <input type="text" name="flutterwave_public_key" value="<?= h($s['flutterwave_public_key'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none font-mono text-xs">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Secret Key</label>
            <input type="password" name="flutterwave_secret_key" value="<?= h($s['flutterwave_secret_key'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none font-mono text-xs">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Webhook Hash</label>
            <input type="password" name="flutterwave_hash" value="<?= h($s['flutterwave_hash'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none font-mono text-xs">
          </div>
        </div>
        <p class="mt-3 text-xs text-gray-400">
          Webhook URL (register in Flutterwave dashboard):<br>
          <code class="bg-gray-50 px-2 py-1 rounded text-xs"><?= APP_URL ?>/api/flutterwave-callback.php</code>
        </p>
      </div>

      <!-- Invoice / Bank -->
      <div class="rounded bg-white p-6 shadow-[0_10px_30px_0_#05103608]">
        <h2 class="mb-4 border-b border-gray-100 pb-3 text-base font-medium text-dark-1">Invoice & Banking</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Invoice Prefix</label>
            <input type="text" name="invoice_prefix" value="<?= h($s['invoice_prefix'] ?? 'TK') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Bank Name</label>
            <input type="text" name="invoice_bank_name" value="<?= h($s['invoice_bank_name'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none" placeholder="e.g. Equity Bank">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Bank Account Number</label>
            <input type="text" name="invoice_bank_account" value="<?= h($s['invoice_bank_account'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Bank Branch</label>
            <input type="text" name="invoice_bank_branch" value="<?= h($s['invoice_bank_branch'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none" placeholder="e.g. Nairobi CBD">
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Invoice Footer Text</label>
            <textarea name="invoice_footer" rows="2"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none resize-none"><?= h($s['invoice_footer'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

      <div class="rounded bg-white p-6 shadow-[0_10px_30px_0_#05103608]">
        <button type="submit"
          class="bg-blue-1 hover:bg-dark-1 w-full rounded px-6 py-3 text-sm font-medium text-white transition-colors">
          Save Settings
        </button>
      </div>

      <!-- Change Password -->
      <div class="rounded bg-white p-6 shadow-[0_10px_30px_0_#05103608]">
        <h2 class="mb-4 text-base font-medium text-dark-1">Change Password</h2>
        <form method="POST" action="/admin/settings.php">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <div class="mb-3">
            <label class="mb-1.5 block text-sm font-medium text-dark-1">New Password</label>
            <input type="password" name="new_password" minlength="8"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none"
              placeholder="Min. 8 characters">
          </div>
          <div class="mb-4">
            <label class="mb-1.5 block text-sm font-medium text-dark-1">Confirm Password</label>
            <input type="password" name="confirm_password"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none">
          </div>
          <button type="submit"
            class="w-full rounded border border-gray-200 px-6 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
            Update Password
          </button>
        </form>
      </div>

    </div>
  </div>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
