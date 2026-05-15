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

// Payment step (after booking created)
$step = $_GET['step'] ?? '';
if ($step === 'payment' && $booking_ref) {
    $booking = get_booking_by_ref($booking_ref);
    if (!$booking) {
        flash('error', 'Booking not found.');
        redirect('/cars.php');
    }
    $page_title = 'Complete Payment';
    require __DIR__ . '/includes/header.php';
    ?>
    <main class="flex-grow">
      <section class="py-16">
        <div class="container">
          <div class="mx-auto max-w-2xl">

            <h1 class="text-dark-1 mb-2 text-3xl font-semibold">Complete Your Payment</h1>
            <p class="text-light-1 text-15 mb-8">Booking Reference: <strong class="font-mono text-dark-1"><?= h($booking['booking_ref']) ?></strong></p>

            <!-- Booking Summary -->
            <div class="border-border mb-8 rounded border bg-white p-6">
              <h2 class="text-dark-1 mb-4 text-base font-semibold">Booking Summary</h2>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-light-1">Vehicle</span><span class="font-medium text-dark-1"><?= h($booking['make'] . ' ' . $booking['model']) ?></span></div>
                <div class="flex justify-between"><span class="text-light-1">Service</span><span class="text-dark-1"><?= h($booking['service_name']) ?></span></div>
                <div class="flex justify-between"><span class="text-light-1">Pickup</span><span class="text-dark-1"><?= h(display_date($booking['pickup_date'])) ?> at <?= h(substr($booking['pickup_time'],0,5)) ?></span></div>
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
            <div x-data="paymentForm('<?= h($booking['booking_ref']) ?>', <?= (float)$booking['total_amount'] ?>)">

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
        bookingRef,
        totalAmount,
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

$services      = $db->query('SELECT id, slug, name FROM services WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
$service_slug  = $_GET['service']           ?? 'car-hire';
$date_from     = $_GET['date_from']         ?? '';
$date_to       = $_GET['date_to']           ?? '';
$pickup_location = $_GET['pickup_location'] ?? '';

$page_title = 'Book ' . $car['make'] . ' ' . $car['model'];
$thumb = $car['thumbnail_path'] ? UPLOAD_URL . $car['thumbnail_path'] : APP_URL . '/assets/images/cars/1.png';

require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow">

  <!-- Page Banner -->
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

  <section class="py-16">
    <div class="container">
      <div class="mx-auto max-w-4xl"
        x-data="bookingForm()"
        x-init="init()">

        <!-- Progress Steps -->
        <div class="mb-10 flex items-center gap-4">
          <?php foreach (['Your Details', 'Review', 'Payment'] as $i => $label): ?>
          <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold"
              :class="step >= <?= $i ?> ? 'bg-blue-1 text-white' : 'bg-light-2 text-light-1'">
              <?= $i + 1 ?>
            </div>
            <span class="hidden text-sm font-medium sm:block"
              :class="step === <?= $i ?> ? 'text-dark-1' : 'text-light-1'"><?= $label ?></span>
          </div>
          <?php if ($i < 2): ?>
          <div class="h-px flex-1 border-border border-t"></div>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">

          <!-- Form -->
          <div class="lg:col-span-2">

            <!-- Step 0: Your Details -->
            <div x-show="step === 0" class="border-border rounded border bg-white p-6">
              <h2 class="text-dark-1 mb-5 text-base font-semibold">Your Details</h2>

              <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">Full Name *</label>
                  <input type="text" x-model="form.full_name" placeholder="John Kamau"
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none">
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">Email *</label>
                  <input type="email" x-model="form.email" placeholder="john@email.com"
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none">
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">Phone Number *</label>
                  <input type="tel" x-model="form.phone" placeholder="+254 7XX XXX XXX"
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none">
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">ID / Passport</label>
                  <input type="text" x-model="form.id_number" placeholder="National ID or Passport"
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none">
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">Pickup Date *</label>
                  <input type="date" x-model="form.pickup_date"
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none">
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">Return Date</label>
                  <input type="date" x-model="form.return_date"
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none">
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">Pickup Location *</label>
                  <input type="text" x-model="form.pickup_location" placeholder="e.g. JKIA, Nairobi CBD"
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none">
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">No. of Passengers</label>
                  <select x-model="form.num_passengers"
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none">
                    <?php for ($i = 1; $i <= $car['passenger_capacity']; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> passenger<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-dark-1">Service</label>
                  <select x-model="form.service_slug"
                    class="border-border focus:border-dark-1 text-15 h-12 w-full rounded border px-4 outline-none">
                    <?php foreach ($services as $svc): ?>
                    <option value="<?= h($svc['slug']) ?>"><?= h($svc['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="mt-4">
                <label class="mb-1.5 block text-sm font-medium text-dark-1">Special Requests</label>
                <textarea x-model="form.special_requests" rows="3"
                  placeholder="Baby seat, extra driver, airport greeting sign..."
                  class="border-border focus:border-dark-1 text-15 w-full resize-none rounded border px-4 py-3 outline-none"></textarea>
              </div>

              <div x-show="formError" class="mt-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600" x-text="formError"></div>

              <div class="mt-6 flex justify-end">
                <button @click="nextStep()"
                  class="bg-blue-1 hover:bg-dark-1 text-15 h-12 rounded px-8 font-medium text-white transition">
                  Review Booking &rarr;
                </button>
              </div>
            </div>

            <!-- Step 1: Review -->
            <div x-show="step === 1" class="border-border rounded border bg-white p-6">
              <h2 class="text-dark-1 mb-5 text-base font-semibold">Review Your Booking</h2>
              <div class="space-y-3">
                <div class="flex justify-between text-sm"><span class="text-light-1">Name</span><span class="font-medium text-dark-1" x-text="form.full_name"></span></div>
                <div class="flex justify-between text-sm"><span class="text-light-1">Email</span><span class="text-dark-1" x-text="form.email"></span></div>
                <div class="flex justify-between text-sm"><span class="text-light-1">Phone</span><span class="text-dark-1" x-text="form.phone"></span></div>
                <div class="flex justify-between text-sm"><span class="text-light-1">Vehicle</span><span class="font-medium text-dark-1"><?= h($car['make'] . ' ' . $car['model']) ?></span></div>
                <div class="flex justify-between text-sm"><span class="text-light-1">Service</span><span class="text-dark-1" x-text="form.service_slug"></span></div>
                <div class="flex justify-between text-sm"><span class="text-light-1">Pickup Date</span><span class="text-dark-1" x-text="form.pickup_date"></span></div>
                <div class="flex justify-between text-sm" x-show="form.return_date"><span class="text-light-1">Return Date</span><span class="text-dark-1" x-text="form.return_date"></span></div>
                <div class="flex justify-between text-sm"><span class="text-light-1">Pickup Location</span><span class="text-dark-1" x-text="form.pickup_location"></span></div>
              </div>
              <div class="border-border mt-6 flex items-center justify-between border-t pt-4">
                <button @click="step = 0" class="text-15 text-light-1 hover:text-dark-1">&larr; Edit Details</button>
                <button @click="submitBooking()"
                  class="bg-blue-1 hover:bg-dark-1 text-15 h-12 rounded px-8 font-medium text-white transition"
                  :disabled="submitting">
                  <span x-show="!submitting">Confirm &amp; Continue to Payment</span>
                  <span x-show="submitting">Creating booking...</span>
                </button>
              </div>
              <div x-show="submitError" class="mt-3 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600" x-text="submitError"></div>
            </div>

          </div>

          <!-- Booking Summary Sidebar -->
          <div>
            <div class="border-border rounded border bg-white p-5 lg:sticky lg:top-28">
              <img src="<?= h($thumb) ?>" alt="<?= h($car['make'] . ' ' . $car['model']) ?>"
                class="mb-4 aspect-30/25 w-full rounded object-cover" onerror="this.src='/assets/images/cars/1.png'">
              <h3 class="font-semibold text-dark-1"><?= h($car['make'] . ' ' . $car['model']) ?></h3>
              <div class="text-15 text-light-1 mt-1"><?= h($car['category_name']) ?> · <?= $car['year'] ?></div>
              <div class="border-border mt-4 space-y-2 border-t pt-4">
                <div class="text-15 flex items-center gap-2 text-dark-1">
                  <i class="icon-transmission text-blue-1"></i> <?= ucfirst($car['transmission']) ?>
                </div>
                <div class="text-15 flex items-center gap-2 text-dark-1">
                  <i class="icon-user-2 text-blue-1"></i> <?= $car['passenger_capacity'] ?> passengers
                </div>
                <?php if ($car['has_ac']): ?>
                <div class="text-15 flex items-center gap-2 text-dark-1">
                  <i class="icon-wind text-blue-1"></i> Air Conditioning
                </div>
                <?php endif; ?>
              </div>
              <div class="border-border mt-4 border-t pt-4">
                <div class="flex justify-between text-sm font-medium text-dark-1">
                  <span>Price / day</span>
                  <span class="text-blue-1"><?= format_kes($car['price_per_day']) ?></span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>
</main>

<script>
function bookingForm() {
  return {
    step: 0,
    form: {
      car_id:           <?= $car_id ?>,
      service_slug:     '<?= h($service_slug) ?>',
      pickup_date:      '<?= h($date_from) ?>',
      pickup_time:      '08:00',
      return_date:      '<?= h($date_to) ?>',
      return_time:      '08:00',
      pickup_location:  '<?= h($pickup_location) ?>',
      dropoff_location: '',
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

    init() {},

    nextStep() {
      this.formError = '';
      if (!this.form.full_name.trim())        { this.formError = 'Full name is required'; return; }
      if (!this.form.email.trim())             { this.formError = 'Email is required'; return; }
      if (!this.form.phone.trim())             { this.formError = 'Phone number is required'; return; }
      if (!this.form.pickup_location.trim())  { this.formError = 'Pickup location is required'; return; }
      if (!this.form.pickup_date)              { this.formError = 'Pickup date is required'; return; }
      this.step = 1;
    },

    async submitBooking() {
      this.submitting  = true;
      this.submitError = '';
      try {
        const payload = { ...this.form, csrf_token: '<?= h(csrf_token()) ?>' };
        const res = await fetch('/api/booking-submit.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
          window.location.href = data.redirect;
        } else {
          this.submitError = data.error || 'Booking failed. Please try again.';
        }
      } catch(e) {
        this.submitError = 'Network error. Please try again.';
      } finally {
        this.submitting = false;
      }
    },
  };
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
