<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/booking_functions.php';

$db = get_db();

// Handle payment complete redirect from Flutterwave
$payment_status = $_GET['payment'] ?? '';
$booking_ref    = trim($_GET['ref'] ?? '');

if ($payment_status === 'complete' && $booking_ref) {
    $booking = get_booking_by_ref($booking_ref);
    if ($booking && $booking['status'] === 'confirmed') {
        redirect('/invoice.php?ref=' . urlencode($booking_ref));
    }
}

// ─── Payment Step (after booking is created) ──────────────────────────────────
$step = $_GET['step'] ?? '';
if ($step === 'payment' && $booking_ref) {
    $booking = get_booking_by_ref($booking_ref);
    if (!$booking) {
        flash('error', 'Booking not found.');
        redirect('/cars.php');
    }
    $page_title = 'Complete Payment';
    $nav_white  = true;
    require __DIR__ . '/includes/header.php';
    ?>
    <main class="flex-grow pt-20">
      <section class="py-16">
        <div class="container">
          <div class="mx-auto max-w-2xl">

            <h1 class="text-dark-1 mb-2 text-3xl font-semibold">Complete Your Payment</h1>
            <p class="text-light-1 text-15 mb-8">
              Booking Reference: <strong class="font-mono text-dark-1"><?= h($booking['booking_ref']) ?></strong>
            </p>

            <!-- Booking Summary -->
            <div class="border-border mb-8 rounded border bg-white p-6">
              <h2 class="text-dark-1 mb-4 text-base font-semibold">Booking Summary</h2>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-light-1">Vehicle</span><span class="font-medium text-dark-1"><?= h($booking['make'] . ' ' . $booking['model']) ?></span></div>
                <div class="flex justify-between"><span class="text-light-1">Service</span><span class="text-dark-1"><?= h($booking['service_name']) ?></span></div>
                <div class="flex justify-between"><span class="text-light-1">Pickup</span><span class="text-dark-1"><?= h(display_date($booking['pickup_date'])) ?> at <?= h(substr($booking['pickup_time'] ?? '08:00', 0, 5)) ?></span></div>
                <?php if ($booking['return_date']): ?>
                <div class="flex justify-between"><span class="text-light-1">Return</span><span class="text-dark-1"><?= h(display_date($booking['return_date'])) ?></span></div>
                <?php endif; ?>
                <div class="flex justify-between"><span class="text-light-1">Duration</span><span class="text-dark-1"><?= $booking['num_days'] ?> day<?= $booking['num_days'] > 1 ? 's' : '' ?></span></div>
                <div class="border-border mt-3 flex justify-between border-t pt-3 font-bold text-dark-1">
                  <span>Total Amount</span>
                  <span><?= format_kes($booking['total_amount']) ?></span>
                </div>
              </div>
            </div>

            <!-- Payment Options -->
            <div x-data="paymentForm(<?= json_encode($booking['booking_ref']) ?>, <?= (float)$booking['total_amount'] ?>)">

              <h2 class="text-dark-1 mb-4 text-base font-semibold">Select Payment Method</h2>

              <!-- M-Pesa -->
              <div class="border-border mb-4 rounded border bg-white p-5">
                <div class="mb-4 flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded bg-blue-1/5">
                    <span class="text-sm font-bold text-blue-1">M</span>
                  </div>
                  <div>
                    <div class="font-semibold text-dark-1">M-Pesa (Lipa Na MPesa)</div>
                    <div class="text-15 text-light-1">Safaricom STK Push — instant payment</div>
                  </div>
                </div>
                <div x-show="!mpesa.sent">
                  <div class="mb-3">
                    <label class="mb-1.5 block text-sm font-medium text-dark-1">M-Pesa Phone Number</label>
                    <input type="tel" x-model="mpesa.phone" placeholder="e.g. 0712345678"
                      class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none">
                  </div>
                  <button @click="sendMpesa()"
                    class="bg-blue-1 hover:bg-dark-1 text-15 w-full rounded px-4 py-3 font-medium text-white transition"
                    :disabled="mpesa.loading">
                    <span x-show="!mpesa.loading">Pay <?= format_kes($booking['total_amount']) ?> via M-Pesa</span>
                    <span x-show="mpesa.loading">Sending STK Push...</span>
                  </button>
                </div>
                <div x-show="mpesa.sent" class="text-center">
                  <div class="text-15 text-dark-1 mb-3">STK Push sent to <strong x-text="mpesa.phone"></strong>. Enter your PIN on your phone.</div>
                  <div class="animate-spin mx-auto mb-3 h-6 w-6 rounded-full border-2 border-blue-1 border-t-transparent"></div>
                  <div class="text-15 text-light-1">Waiting for payment confirmation...</div>
                  <div x-show="mpesa.error" class="mt-2 text-sm text-red-600" x-text="mpesa.error"></div>
                  <button @click="mpesa.sent=false; mpesa.error=''" class="mt-3 text-15 text-blue-1 hover:underline">Try again</button>
                </div>
              </div>

              <!-- Card / Airtel via Flutterwave -->
              <div class="border-border mb-4 rounded border bg-white p-5">
                <div class="mb-4 flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded bg-light-2">
                    <span class="text-sm font-bold text-dark-1">F</span>
                  </div>
                  <div>
                    <div class="font-semibold text-dark-1">Card / Airtel Money</div>
                    <div class="text-15 text-light-1">Visa, Mastercard, Airtel Money via Flutterwave</div>
                  </div>
                </div>
                <button @click="payFlutterwave()"
                  class="bg-blue-1 hover:bg-dark-1 text-15 w-full rounded px-4 py-3 font-medium text-white transition"
                  :disabled="flw.loading">
                  <span x-show="!flw.loading">Pay via Card / Airtel Money</span>
                  <span x-show="flw.loading">Redirecting to payment...</span>
                </button>
              </div>

              <!-- Bank Transfer -->
              <div class="border-border mb-4 rounded border bg-white p-5">
                <div class="mb-4 flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded bg-light-2">
                    <i class="icon-building text-dark-1"></i>
                  </div>
                  <div>
                    <div class="font-semibold text-dark-1">Bank Transfer</div>
                    <div class="text-15 text-light-1">Booking confirmed after payment proof received</div>
                  </div>
                </div>
                <div x-show="!bank.shown">
                  <button @click="bank.shown=true"
                    class="border-border w-full rounded border px-4 py-3 text-sm text-dark-1 hover:bg-light-2 transition">
                    View Bank Details
                  </button>
                </div>
                <div x-show="bank.shown" class="mt-3 rounded bg-light-2 px-4 py-3 text-sm">
                  <?php
                  $bank_name    = get_setting('invoice_bank_name', '');
                  $bank_account = get_setting('invoice_bank_account', '');
                  $bank_branch  = get_setting('invoice_bank_branch', '');
                  ?>
                  <?php if ($bank_name): ?>
                  <div class="mb-1"><span class="text-light-1">Bank:</span> <strong class="text-dark-1"><?= h($bank_name) ?></strong></div>
                  <?php endif; ?>
                  <?php if ($bank_account): ?>
                  <div class="mb-1"><span class="text-light-1">Account:</span> <strong class="font-mono text-dark-1"><?= h($bank_account) ?></strong></div>
                  <?php endif; ?>
                  <?php if ($bank_branch): ?>
                  <div class="mb-1"><span class="text-light-1">Branch:</span> <span class="text-dark-1"><?= h($bank_branch) ?></span></div>
                  <?php endif; ?>
                  <div><span class="text-light-1">Reference:</span> <strong class="font-mono text-dark-1"><?= h($booking['booking_ref']) ?></strong></div>
                  <p class="text-15 text-light-1 mt-3">After transfer, WhatsApp us your receipt to confirm your booking.</p>
                  <?php $wa = get_setting('company_whatsapp',''); if ($wa): ?>
                  <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $wa)) ?>?text=Hello%2C+I%27ve+completed+bank+transfer+for+booking+<?= urlencode($booking['booking_ref']) ?>"
                    target="_blank"
                    class="mt-3 flex items-center gap-2 text-sm font-medium text-dark-1 hover:text-blue-1 transition">
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                    Send Receipt on WhatsApp
                  </a>
                  <?php endif; ?>
                </div>
              </div>

              <div x-show="error" class="mt-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600" x-text="error"></div>

            </div>

            <div class="mt-6 text-center">
              <a href="/car.php?id=<?= h($booking['car_id'] ?? '') ?>" class="text-15 text-light-1 hover:text-blue-1">&larr; Back to car</a>
            </div>
          </div>
        </div>
      </section>
    </main>

    <script>
    function paymentForm(bookingRef, totalAmount) {
      return {
        bookingRef, totalAmount,
        mpesa: { phone: '', loading: false, sent: false, error: '', pollTimer: null },
        flw:   { loading: false },
        bank:  { shown: false },
        error: '',

        async sendMpesa() {
          if (!this.mpesa.phone) { this.error = 'Please enter your M-Pesa phone number'; return; }
          this.mpesa.loading = true;
          this.error = '';
          try {
            const res = await fetch('/api/mpesa-initiate.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ booking_ref: this.bookingRef, phone: this.mpesa.phone }),
            });
            const data = await res.json();
            if (data.success) {
              this.mpesa.sent = true;
              this.pollPayment(data.checkout_request_id);
            } else {
              this.error = data.error || 'M-Pesa request failed.';
            }
          } catch(e) {
            this.error = 'Network error. Please try again.';
          } finally {
            this.mpesa.loading = false;
          }
        },

        pollPayment(checkoutId) {
          this.mpesa.pollTimer = setInterval(async () => {
            const res = await fetch(`/api/check-payment.php?checkout_request_id=${checkoutId}&booking_ref=${this.bookingRef}`);
            const data = await res.json();
            if (data.paid && data.redirect) {
              clearInterval(this.mpesa.pollTimer);
              window.location.href = data.redirect;
            } else if (data.failed) {
              clearInterval(this.mpesa.pollTimer);
              this.mpesa.error = 'Payment failed. Please try again.';
            }
          }, 3000);
          setTimeout(() => {
            clearInterval(this.mpesa.pollTimer);
            if (this.mpesa.sent) this.mpesa.error = 'Payment timed out. If you paid, please contact us.';
          }, 180000);
        },

        async payFlutterwave() {
          this.flw.loading = true;
          this.error = '';
          try {
            const res = await fetch('/api/flutterwave-initiate.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ booking_ref: this.bookingRef }),
            });
            const data = await res.json();
            if (data.link) {
              window.location.href = data.link;
            } else {
              this.error = data.error || 'Could not initiate card payment.';
              this.flw.loading = false;
            }
          } catch(e) {
            this.error = 'Network error. Please try again.';
            this.flw.loading = false;
          }
        },
      };
    }
    </script>

    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ─── Booking Form ─────────────────────────────────────────────────────────────

$car_id = (int)($_GET['car_id'] ?? 0);
if (!$car_id) {
    redirect('/cars.php');
}

$stmt = $db->prepare('
    SELECT c.*, cat.name AS category_name FROM cars c
    JOIN car_categories cat ON c.category_id = cat.id
    WHERE c.id = ? AND c.status = "available"
');
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car) {
    flash('error', 'Car not available for booking.');
    redirect('/cars.php');
}

$services         = $db->query('SELECT id, slug, name FROM services WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
$service_slug     = $_GET['service']           ?? ($services[0]['slug'] ?? 'car-hire');
$pickup_date_url  = $_GET['pickup_date']       ?? '';
$return_date_url  = $_GET['return_date']       ?? '';
$pickup_location  = $_GET['pickup_location']   ?? '';
$dropoff_location = $_GET['dropoff_location']  ?? '';

// Calculate days / price preview
$days = 1;
if ($pickup_date_url && $return_date_url) {
    try {
        $d1   = new DateTime($pickup_date_url);
        $d2   = new DateTime($return_date_url);
        $diff = $d1->diff($d2)->days;
        $days = max(1, (int)$diff);
    } catch (Exception) {}
}
$total_preview = $car['price_per_day'] * $days;

// Serialize data for JS — zero PHP interpolation inside JS strings
$booking_js_data = json_encode([
    'car_id'          => $car_id,
    'service_slug'    => $service_slug,
    'pickup_date'     => $pickup_date_url,
    'return_date'     => $return_date_url,
    'pickup_location' => $pickup_location,
    'dropoff_location'=> $dropoff_location,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$csrf_js = json_encode(csrf_token());

$thumb      = $car['thumbnail_path'] ? UPLOAD_URL . $car['thumbnail_path'] : '/assets/images/cars/1.png';
$page_title = 'Book ' . $car['make'] . ' ' . $car['model'];
$nav_white  = true;
require __DIR__ . '/includes/header.php';
?>

<script>
(function () {
  var d    = <?= $booking_js_data ?>;
  var csrf = <?= $csrf_js ?>;

  window.bookingForm = function () {
    return {
      step: 1,
      form: {
        car_id:           d.car_id,
        service_slug:     d.service_slug,
        pickup_date:      d.pickup_date,
        pickup_time:      '08:00',
        return_date:      d.return_date,
        return_time:      '08:00',
        pickup_location:  d.pickup_location,
        dropoff_location: d.dropoff_location,
        full_name:        '',
        email:            '',
        phone:            '',
        id_number:        '',
        num_passengers:   1,
        flight_number:    '',
        special_requests: '',
      },
      formError:   '',
      submitError: '',
      submitting:  false,

      nextStep() {
        this.formError = '';
        if (!this.form.full_name.trim())      { this.formError = 'Full name is required.'; return; }
        if (!this.form.email.trim())           { this.formError = 'Email address is required.'; return; }
        if (!this.form.phone.trim())           { this.formError = 'Phone number is required.'; return; }
        if (!this.form.pickup_location.trim()){ this.formError = 'Pickup location is required.'; return; }
        if (!this.form.pickup_date)            { this.formError = 'Pickup date is required.'; return; }
        this.step = 2;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      },

      async submitBooking() {
        this.submitting  = true;
        this.submitError = '';
        try {
          const payload = { ...this.form, csrf_token: csrf };
          const res = await fetch('/api/booking-submit.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
          });
          const data = await res.json();
          if (data.success) {
            window.location.href = data.redirect;
          } else {
            this.submitError = data.error || 'Booking failed. Please try again.';
          }
        } catch (e) {
          this.submitError = 'Network error. Please try again.';
        } finally {
          this.submitting = false;
        }
      },
    };
  };
})();
</script>

<main class="flex-grow pt-20">

  <!-- Dark Banner -->
  <section class="bg-dark-1 py-10">
    <div class="container">
      <div class="flex flex-wrap items-center justify-between gap-y-2">
        <h1 class="text-2xl font-semibold text-white">Book Your Car</h1>
        <div class="text-light-1 flex items-center gap-2 text-sm">
          <a href="/index.php" class="hover:text-white">Home</a>
          <span>/</span>
          <a href="/cars.php" class="hover:text-white">Cars</a>
          <span>/</span>
          <span class="text-white"><?= h($car['make'] . ' ' . $car['model']) ?></span>
        </div>
      </div>
    </div>
  </section>

  <!-- Stepper + Form -->
  <section class="py-14 md:py-20">
    <div class="container">
      <div x-data="bookingForm()">

        <!-- ── Progress Stepper ────────────────────────────────────── -->
        <div class="mb-12 flex items-center md:mb-16">

          <!-- Step 1 -->
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full transition"
              :class="step >= 1 ? 'bg-blue-1' : 'bg-blue-1/5'">
              <span x-show="step > 1">
                <i class="icon-check text-base text-white"></i>
              </span>
              <span x-show="step <= 1"
                class="font-medium"
                :class="step >= 1 ? 'text-white' : 'text-blue-1'">1</span>
            </div>
            <span class="hidden text-base font-medium sm:inline md:text-lg"
              :class="step === 1 ? 'text-dark-1' : 'text-light-1'">Your Details</span>
          </div>

          <div class="bg-border mx-4 hidden h-px flex-1 md:block"></div>

          <!-- Step 2 -->
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full font-medium transition"
              :class="step >= 2 ? 'bg-blue-1' : 'bg-blue-1/5'">
              <span x-show="step > 2">
                <i class="icon-check text-base text-white"></i>
              </span>
              <span x-show="step <= 2"
                :class="step >= 2 ? 'text-white' : 'text-blue-1'">2</span>
            </div>
            <span class="hidden text-base font-medium sm:inline md:text-lg"
              :class="step === 2 ? 'text-dark-1' : 'text-light-1'">Review</span>
          </div>

          <div class="bg-border mx-4 hidden h-px flex-1 md:block"></div>

          <!-- Step 3 (payment — next page) -->
          <div class="flex items-center gap-3">
            <div class="bg-blue-1/5 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full font-medium">
              <span class="text-blue-1">3</span>
            </div>
            <span class="text-light-1 hidden text-base font-medium sm:inline md:text-lg">Payment</span>
          </div>
        </div>

        <!-- ── Main Grid ───────────────────────────────────────────── -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-12">

          <!-- LEFT: Form Steps -->
          <div class="lg:col-span-7">

            <!-- ── Step 1: Your Details ── -->
            <div x-show="step === 1"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100">

              <h2 class="text-dark-1 mb-2 text-xl font-semibold md:text-2xl">Let us know who you are</h2>
              <p class="text-light-1 text-15 mb-8">Fill in your details to complete the booking.</p>

              <div class="space-y-5">

                <!-- Full name -->
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">Full Name <span class="text-red-500">*</span></label>
                  <input type="text" x-model="form.full_name"
                    placeholder="John Kamau"
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none focus:border-2">
                </div>

                <!-- Email + Phone -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-sm font-medium text-dark-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" x-model="form.email"
                      placeholder="john@email.com"
                      class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none focus:border-2">
                  </div>
                  <div>
                    <label class="mb-1.5 block text-sm font-medium text-dark-1">Phone Number <span class="text-red-500">*</span></label>
                    <input type="tel" x-model="form.phone"
                      placeholder="+254 7XX XXX XXX"
                      class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none focus:border-2">
                  </div>
                </div>

                <!-- ID / Passport -->
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">ID / Passport Number</label>
                  <input type="text" x-model="form.id_number"
                    placeholder="National ID or Passport No."
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none focus:border-2">
                </div>

                <!-- Pickup / Dropoff -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-sm font-medium text-dark-1">Pickup Location <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.pickup_location"
                      placeholder="e.g. JKIA, Nairobi CBD"
                      class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none focus:border-2">
                  </div>
                  <div>
                    <label class="mb-1.5 block text-sm font-medium text-dark-1">Drop-off Location</label>
                    <input type="text" x-model="form.dropoff_location"
                      placeholder="Same as pickup or different"
                      class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none focus:border-2">
                  </div>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-sm font-medium text-dark-1">Pickup Date <span class="text-red-500">*</span></label>
                    <input type="date" x-model="form.pickup_date"
                      min="<?= date('Y-m-d') ?>"
                      class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none focus:border-2">
                  </div>
                  <div>
                    <label class="mb-1.5 block text-sm font-medium text-dark-1">Return Date</label>
                    <input type="date" x-model="form.return_date"
                      class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none focus:border-2">
                  </div>
                </div>

                <!-- Service + Passengers -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-sm font-medium text-dark-1">Service</label>
                    <select x-model="form.service_slug"
                      class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none focus:border-2">
                      <?php foreach ($services as $svc): ?>
                      <option value="<?= h($svc['slug']) ?>"><?= h($svc['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div>
                    <label class="mb-1.5 block text-sm font-medium text-dark-1">Passengers</label>
                    <select x-model="form.num_passengers"
                      class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none focus:border-2">
                      <?php for ($i = 1; $i <= $car['passenger_capacity']; $i++): ?>
                      <option value="<?= $i ?>"><?= $i ?> passenger<?= $i > 1 ? 's' : '' ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>
                </div>

                <!-- Special Requests -->
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">Special Requests</label>
                  <textarea x-model="form.special_requests" rows="3"
                    placeholder="Baby seat, extra driver, airport greeting sign..."
                    class="border-border focus:border-dark-1 text-15 w-full resize-none rounded border px-4 py-3 outline-none focus:border-2"></textarea>
                </div>

                <!-- Error -->
                <div x-show="formError"
                  class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600"
                  x-text="formError"></div>

                <!-- CTA -->
                <div class="flex justify-end pt-2">
                  <button @click="nextStep()"
                    class="bg-blue-1 hover:bg-dark-1 text-15 inline-flex h-12 cursor-pointer items-center gap-3 rounded px-8 font-medium text-white transition">
                    Review Booking
                    <i class="icon-arrow-right text-sm"></i>
                  </button>
                </div>

              </div>
            </div>

            <!-- ── Step 2: Review ── -->
            <div x-show="step === 2"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100">

              <h2 class="text-dark-1 mb-2 text-xl font-semibold md:text-2xl">Review Your Booking</h2>
              <p class="text-light-1 text-15 mb-8">Please confirm all details before proceeding to payment.</p>

              <!-- Details summary -->
              <div class="border-border mb-6 rounded border bg-white">
                <h3 class="text-dark-1 border-border border-b px-6 py-4 text-sm font-semibold uppercase tracking-wide">Your Information</h3>
                <div class="divide-border divide-y px-6">
                  <div class="flex items-center justify-between py-4">
                    <span class="text-light-1 text-sm">Full Name</span>
                    <span class="text-dark-1 font-medium" x-text="form.full_name"></span>
                  </div>
                  <div class="flex items-center justify-between py-4">
                    <span class="text-light-1 text-sm">Email</span>
                    <span class="text-dark-1 text-sm" x-text="form.email"></span>
                  </div>
                  <div class="flex items-center justify-between py-4">
                    <span class="text-light-1 text-sm">Phone</span>
                    <span class="text-dark-1 text-sm" x-text="form.phone"></span>
                  </div>
                  <div class="flex items-center justify-between py-4" x-show="form.id_number">
                    <span class="text-light-1 text-sm">ID / Passport</span>
                    <span class="text-dark-1 text-sm" x-text="form.id_number"></span>
                  </div>
                </div>
              </div>

              <!-- Trip details summary -->
              <div class="border-border mb-6 rounded border bg-white">
                <h3 class="text-dark-1 border-border border-b px-6 py-4 text-sm font-semibold uppercase tracking-wide">Trip Details</h3>
                <div class="divide-border divide-y px-6">
                  <div class="flex items-center justify-between py-4">
                    <span class="text-light-1 text-sm">Vehicle</span>
                    <span class="text-dark-1 font-medium"><?= h($car['make'] . ' ' . $car['model']) ?></span>
                  </div>
                  <div class="flex items-center justify-between py-4">
                    <span class="text-light-1 text-sm">Service</span>
                    <span class="text-dark-1 text-sm" x-text="form.service_slug"></span>
                  </div>
                  <div class="flex items-center justify-between py-4">
                    <span class="text-light-1 text-sm">Pickup Location</span>
                    <span class="text-dark-1 text-sm" x-text="form.pickup_location"></span>
                  </div>
                  <div class="flex items-center justify-between py-4" x-show="form.dropoff_location">
                    <span class="text-light-1 text-sm">Drop-off Location</span>
                    <span class="text-dark-1 text-sm" x-text="form.dropoff_location"></span>
                  </div>
                  <div class="flex items-center justify-between py-4">
                    <span class="text-light-1 text-sm">Pickup Date</span>
                    <span class="text-dark-1 text-sm" x-text="form.pickup_date"></span>
                  </div>
                  <div class="flex items-center justify-between py-4" x-show="form.return_date">
                    <span class="text-light-1 text-sm">Return Date</span>
                    <span class="text-dark-1 text-sm" x-text="form.return_date"></span>
                  </div>
                  <div class="flex items-center justify-between py-4">
                    <span class="text-light-1 text-sm">Passengers</span>
                    <span class="text-dark-1 text-sm" x-text="form.num_passengers + ' passenger(s)'"></span>
                  </div>
                  <div class="flex items-center justify-between py-4" x-show="form.special_requests">
                    <span class="text-light-1 text-sm">Special Requests</span>
                    <span class="text-dark-1 text-sm max-w-xs text-right" x-text="form.special_requests"></span>
                  </div>
                </div>
              </div>

              <!-- Error -->
              <div x-show="submitError"
                class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600"
                x-text="submitError"></div>

              <!-- Actions -->
              <div class="flex flex-wrap items-center justify-between gap-4">
                <button @click="step = 1; formError = ''"
                  class="border-border text-light-1 hover:text-dark-1 inline-flex items-center gap-2 rounded border px-5 py-3 text-sm transition hover:bg-gray-50">
                  <i class="icon-chevron-left text-xs"></i>
                  Edit Details
                </button>
                <button @click="submitBooking()"
                  class="bg-blue-1 hover:bg-dark-1 text-15 inline-flex h-12 cursor-pointer items-center gap-3 rounded px-8 font-medium text-white transition"
                  :disabled="submitting">
                  <span x-show="!submitting">Confirm &amp; Pay</span>
                  <span x-show="submitting">Creating booking...</span>
                  <i class="icon-arrow-right text-sm" x-show="!submitting"></i>
                </button>
              </div>

            </div>

          </div><!-- /left col -->

          <!-- RIGHT: Car Summary Card -->
          <div class="lg:col-span-5">
            <div class="border-border space-y-0 rounded border bg-white lg:sticky lg:top-28">

              <!-- Car image + name -->
              <div class="p-6 md:p-8">
                <h3 class="text-dark-1 mb-5 text-lg font-medium md:text-xl">Your booking details</h3>

                <div class="mb-5 flex gap-4">
                  <img src="<?= h($thumb) ?>" alt="<?= h($car['make'] . ' ' . $car['model']) ?>"
                    class="h-24 w-24 flex-shrink-0 rounded object-cover md:h-28 md:w-28"
                    onerror="this.src='/assets/images/cars/1.png'">
                  <div class="flex-1 min-w-0">
                    <h4 class="text-dark-1 mb-1 text-sm font-semibold leading-tight md:text-base">
                      <?= h($car['make'] . ' ' . $car['model']) ?>
                    </h4>
                    <p class="text-light-1 text-xs md:text-sm">
                      <?= h($car['category_name']) ?> &middot; <?= $car['year'] ?>
                    </p>
                    <div class="mt-3 space-y-1">
                      <div class="text-dark-1 flex items-center gap-2 text-xs">
                        <i class="icon-transmission text-blue-1 text-sm"></i>
                        <?= ucfirst(h($car['transmission'])) ?>
                      </div>
                      <div class="text-dark-1 flex items-center gap-2 text-xs">
                        <i class="icon-user-2 text-blue-1 text-sm"></i>
                        <?= $car['passenger_capacity'] ?> passengers max
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-border h-px"></div>

              <!-- Dates -->
              <div class="p-6 md:p-8">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <div class="text-light-1 text-xs mb-1">Pick-up</div>
                    <div class="text-dark-1 text-sm font-medium">
                      <?= $pickup_date_url ? h(display_date($pickup_date_url)) : '—' ?>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-light-1 text-xs mb-1">Return</div>
                    <div class="text-dark-1 text-sm font-medium">
                      <?= $return_date_url ? h(display_date($return_date_url)) : '—' ?>
                    </div>
                  </div>
                </div>
                <div class="text-light-1 mt-3 text-xs">
                  Duration: <strong class="text-dark-1"><?= $days ?> day<?= $days !== 1 ? 's' : '' ?></strong>
                </div>
              </div>

              <div class="bg-border h-px"></div>

              <!-- Price breakdown -->
              <div class="p-6 md:p-8">
                <div class="space-y-2.5 text-sm">
                  <div class="flex justify-between">
                    <span class="text-light-1"><?= format_kes($car['price_per_day']) ?> &times; <?= $days ?> day<?= $days !== 1 ? 's' : '' ?></span>
                    <span class="text-dark-1"><?= format_kes($total_preview) ?></span>
                  </div>
                  <?php if ($car['price_airport'] && $service_slug === 'airport-transfer'): ?>
                  <div class="flex justify-between">
                    <span class="text-light-1">Airport Transfer Fee</span>
                    <span class="text-dark-1"><?= format_kes($car['price_airport']) ?></span>
                  </div>
                  <?php endif; ?>
                  <div class="border-border mt-3 flex justify-between border-t pt-3 font-semibold">
                    <span class="text-dark-1">Estimated Total</span>
                    <span class="text-blue-1 text-base"><?= format_kes($total_preview) ?></span>
                  </div>
                  <p class="text-light-1 text-xs">Final amount confirmed on review.</p>
                </div>
              </div>

              <div class="bg-border h-px"></div>

              <!-- Requirements note -->
              <div class="p-6 md:p-8">
                <p class="text-light-1 text-xs leading-relaxed">
                  <strong class="text-dark-1">Required:</strong> Valid ID / Passport + Driving License.
                  Security deposit <?= format_kes((float)get_setting('security_deposit_min','15000')) ?>–<?= format_kes((float)get_setting('security_deposit_max','50000')) ?> payable at pickup.
                </p>
              </div>

            </div>
          </div><!-- /right col -->

        </div><!-- /grid -->

      </div><!-- /x-data -->
    </div><!-- /container -->
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
