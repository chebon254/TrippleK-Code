<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$company_name     = get_setting('company_name',    'Tripple K Car Hire');
$company_phone    = get_setting('company_phone',   '');
$company_email    = get_setting('company_email',   '');
$company_address  = get_setting('company_address', '');
$company_whatsapp = get_setting('company_whatsapp', '');
$maps_embed_url   = get_setting('maps_embed_url') ?: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63820.78675869162!2d36.798130034814456!3d-1.2950571305451974!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f1172d84d49a7%3A0xf7cf0254b297924c!2sNairobi!5e0!3m2!1sen!2ske!4v1778769785399!5m2!1sen!2ske';

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        if ($company_email) {
            $to      = $company_email;
            $subj    = '[Tripple K] ' . ($subject ?: 'Website Enquiry') . ' from ' . $name;
            $body    = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";
            $headers = "From: noreply@trippleklogistics.com\r\nReply-To: {$email}";
            mail($to, $subj, $body, $headers);
        }
        $success = true;
    }
}

$page_title  = 'Contact Us';
$current_page = 'contact';
$nav_white   = true;
require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow">

  <!-- Map Container with Overlay Form -->
  <section class="relative pb-40">

    <!-- Map -->
    <?php if ($maps_embed_url): ?>
    <div class="relative h-[700px] w-full">
      <iframe class="absolute top-0 left-0 h-full w-full"
        src="<?= h($maps_embed_url) ?>"
        allowfullscreen="" loading="lazy"></iframe>
    </div>
    <?php else: ?>
    <div class="relative h-[400px] w-full bg-light-2 flex items-center justify-center">
      <div class="text-center">
        <i class="icon-map text-white text-5xl mb-3"></i>
        <p class="text-15 text-light-1">Map not configured — add it in Admin → Settings.</p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Floating Contact Form -->
    <div class="pointer-events-none absolute inset-0">
      <div class="pointer-events-none container mx-auto flex h-full items-end justify-end px-6 sm:px-4">
        <div class="pointer-events-auto w-full max-w-[520px]">

          <?php if ($success): ?>
          <div class="shadow-2 space-y-5 rounded bg-dark-3 p-5 sm:p-10 text-center border border-border">
            <i class="icon-check text-blue-1 text-5xl"></i>
            <h3 class="text-white text-2xl font-semibold">Message Sent!</h3>
            <p class="text-15 text-light-1">Thank you for reaching out. We'll get back to you shortly.</p>
            <a href="/contact" class="text-15 text-blue-1 hover:underline">Send another message</a>
          </div>

          <?php else: ?>
          <form method="post" class="shadow-2 space-y-5 rounded bg-dark-3 p-5 sm:p-10 border border-border">
            <h2 class="text-white text-2xl font-semibold">Send a message</h2>

            <?php if ($error): ?>
            <div class="rounded border border-red-2/30 bg-red-1/10 px-4 py-3 text-sm text-red-2"><?= h($error) ?></div>
            <?php endif; ?>

            <!-- Name -->
            <div class="relative">
              <input type="text" name="name" value="<?= h($_POST['name'] ?? '') ?>"
                placeholder=" " aria-required="true"
                class="peer border-border focus:border-blue-1 text-15 h-17.5 w-full rounded border px-4 placeholder-transparent outline-none focus:border-2">
              <label class="text-light-1 peer-focus:text-blue-1 peer-not-placeholder-shown:text-white pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-base transition-all peer-not-placeholder-shown:top-4 peer-not-placeholder-shown:text-xs peer-focus:top-4 peer-focus:text-xs">
                Full Name *
              </label>
            </div>

            <!-- Email -->
            <div class="relative">
              <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>"
                placeholder=" " aria-required="true"
                class="peer border-border focus:border-blue-1 text-15 h-17.5 w-full rounded border px-4 placeholder-transparent outline-none focus:border-2">
              <label class="text-light-1 peer-focus:text-blue-1 peer-not-placeholder-shown:text-white pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-base transition-all peer-not-placeholder-shown:top-4 peer-not-placeholder-shown:text-xs peer-focus:top-4 peer-focus:text-xs">
                Email *
              </label>
            </div>

            <!-- Subject -->
            <div class="relative">
              <input type="text" name="subject" value="<?= h($_POST['subject'] ?? '') ?>"
                placeholder=" "
                class="peer border-border focus:border-blue-1 text-15 h-17.5 w-full rounded border px-4 placeholder-transparent outline-none focus:border-2">
              <label class="text-light-1 peer-focus:text-blue-1 peer-not-placeholder-shown:text-white pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-base transition-all peer-not-placeholder-shown:top-4 peer-not-placeholder-shown:text-xs peer-focus:top-4 peer-focus:text-xs">
                Subject
              </label>
            </div>

            <!-- Message -->
            <div class="relative">
              <textarea name="message" placeholder=" " aria-required="true" rows="5"
                class="peer border-border focus:border-blue-1 text-15 w-full rounded border px-4 py-6 placeholder-transparent outline-none focus:border-2"><?= h($_POST['message'] ?? '') ?></textarea>
              <label class="text-light-1 peer-focus:text-blue-1 peer-not-placeholder-shown:text-white pointer-events-none absolute top-6 left-4 text-base transition-all peer-not-placeholder-shown:top-2 peer-not-placeholder-shown:text-xs peer-focus:top-2 peer-focus:text-xs">
                Message *
              </label>
            </div>

            <div>
              <button type="submit"
                class="bg-blue-1 hover:bg-dark-1 text-15 relative flex h-12.5 cursor-pointer items-center justify-center gap-3 rounded px-6 font-medium text-white transition">
                <span>Send a Message</span>
                <i class="icon-arrow-top-right text-xs"></i>
              </button>
            </div>
          </form>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </section>

  <!-- Contact Info -->
  <section class="bg-dark-2 py-20 md:py-30">
    <div class="container">
      <div class="mb-5">
        <h2 class="text-white text-3xl font-semibold sm:text-2xl">Contact Us</h2>
      </div>

      <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-12 xl:gap-20">

        <?php if ($company_address): ?>
        <div>
          <div class="text-light-1 mb-3 text-sm">Address</div>
          <div class="text-white text-lg font-medium"><?= nl2br(h($company_address)) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($company_phone): ?>
        <div>
          <div class="text-light-1 mb-3 text-sm">Toll Free Customer Care</div>
          <a href="tel:<?= h(preg_replace('/\s+/', '', $company_phone)) ?>"
            class="text-white hover:text-blue-1 text-lg font-medium transition">
            <?= h($company_phone) ?>
          </a>
        </div>
        <?php endif; ?>

        <?php if ($company_email): ?>
        <div>
          <div class="text-light-1 mb-3 text-sm">Need live support?</div>
          <a href="mailto:<?= h($company_email) ?>"
            class="text-white hover:text-blue-1 text-lg font-medium transition">
            <?= h($company_email) ?>
          </a>
        </div>
        <?php endif; ?>

        <?php if ($company_whatsapp): ?>
        <div>
          <div class="text-light-1 mb-3 text-sm">Chat with us</div>
          <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $company_whatsapp)) ?>?text=Hello%2C+I+have+an+enquiry"
            target="_blank"
            class="text-white hover:text-blue-1 text-lg font-medium transition">
            WhatsApp Us
          </a>
          <p class="text-light-1 text-15 mt-1">Mon–Sat, 7am–9pm</p>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
