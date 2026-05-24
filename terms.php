<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$company_name  = get_setting('company_name', 'Tripple K Car Hire');
$company_email = get_setting('company_email', '');

$page_title   = 'Terms & Conditions';
$current_page = 'terms';
require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow">

  <section class="py-20 md:py-30" x-data="{ activeTab: 'terms' }">
    <div class="container">
      <div class="grid gap-8 lg:grid-cols-12">

        <!-- Sidebar -->
        <div class="lg:col-span-3">
          <div class="border-border rounded border bg-dark-3 p-8">
            <nav class="space-y-3" role="tablist">
              <button @click="activeTab = 'terms'"
                :class="activeTab === 'terms' ? 'text-blue-1 font-medium' : 'text-white hover:text-blue-1'"
                class="text-15 w-full cursor-pointer rounded px-3 py-2 text-left transition">
                Terms &amp; Conditions
              </button>
              <button @click="activeTab = 'privacy'"
                :class="activeTab === 'privacy' ? 'text-blue-1 font-medium' : 'text-white hover:text-blue-1'"
                class="text-15 w-full cursor-pointer rounded px-3 py-2 text-left transition">
                Privacy Policy
              </button>
            </nav>
          </div>
        </div>

        <!-- Content -->
        <div class="lg:col-span-9">

          <!-- Terms Panel -->
          <div x-show="activeTab === 'terms'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100">
            <h1 class="mb-4 text-3xl font-semibold">Terms &amp; Conditions</h1>
            <p class="text-light-1 text-15 mb-8">Last updated: <?= date('F Y') ?></p>

            <?php
            $terms_sections = [
              ['1. Rental Agreement', "By completing a booking with {$company_name}, you agree to be bound by these terms and conditions. A binding contract is formed when you receive a booking confirmation from us."],
              ['2. Driver Requirements', "All drivers must be at least 23 years of age and hold a valid driving licence. Foreign nationals must hold an International Driving Permit in addition to their national licence. A minimum of 2 years driving experience is required."],
              ['3. Security Deposit', "A refundable security deposit of KES 15,000 to KES 50,000 (depending on vehicle category) is required at the time of vehicle collection. The deposit is refunded within 7 business days of vehicle return, subject to no damage or outstanding charges."],
              ['4. Payment Terms', "Full payment is required before vehicle collection unless otherwise agreed in writing. We accept M-Pesa (Lipa Na M-Pesa), Airtel Money, Visa/Mastercard via Flutterwave, and bank transfers. All prices are in Kenya Shillings (KES) and are inclusive of VAT where applicable."],
              ['5. Vehicle Use', "Vehicles must not be driven outside Kenya without prior written consent. The vehicle must not be used to carry more passengers than its rated capacity, for racing or off-road activities, or for any illegal purpose. Smoking is strictly prohibited in all vehicles."],
              ['6. Fuel Policy', "Vehicles are provided with a full tank of fuel and must be returned with a full tank. If returned with less fuel, the cost of refuelling plus a KES 500 service charge will be deducted from the security deposit."],
              ['7. Damage &amp; Insurance', "The renter is liable for any damage to the vehicle during the rental period. Our vehicles carry comprehensive insurance, but the renter remains responsible for the excess/deductible amount. Any damage must be reported to us immediately."],
              ['8. Late Returns', "Late returns of more than 2 hours will be charged at the daily rate prorated per hour. Continued use without authorisation will be treated as vehicle theft and reported to the police."],
              ['9. Governing Law', "These terms are governed by the laws of Kenya. Any disputes shall be subject to the exclusive jurisdiction of the courts of Kenya."],
            ];
            foreach ($terms_sections as $i => [$heading, $body]):
            ?>
            <div class="<?= $i > 0 ? 'mt-9' : '' ?>">
              <h2 class="text-base font-medium"><?= $heading ?></h2>
              <div class="text-white text-15 mt-2 space-y-4 leading-relaxed">
                <p><?= $body ?></p>
              </div>
            </div>
            <?php endforeach; ?>

            <!-- Cancellations as a list -->
            <div class="mt-9">
              <h2 class="text-base font-medium">5. Cancellations &amp; Refunds</h2>
              <ul class="text-white text-15 mt-2 space-y-2 leading-relaxed list-disc pl-5">
                <li>More than 48 hours before pickup: full refund minus a KES 500 processing fee.</li>
                <li>24–48 hours before pickup: 50% refund.</li>
                <li>Less than 24 hours before pickup: no refund.</li>
                <li>No-shows: no refund.</li>
              </ul>
            </div>
          </div>

          <!-- Privacy Panel -->
          <div x-show="activeTab === 'privacy'" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100">
            <h1 class="mb-4 text-3xl font-semibold">Privacy Policy</h1>
            <p class="text-light-1 text-15 mb-8">Last updated: <?= date('F Y') ?></p>

            <?php
            $privacy_sections = [
              ['1. Information We Collect', "When you make a booking, we collect your full name, email address, phone number, ID/passport number, and payment information. We also collect usage data such as IP addresses and browser information for security and analytics purposes."],
              ['2. How We Use Your Information', null],
              ['3. Payment Data', "Payment processing is handled by Safaricom (M-Pesa Daraja API) and Flutterwave. We do not store your card details. M-Pesa transactions are subject to Safaricom's privacy policy."],
              ['4. Data Sharing', "We do not sell, rent, or trade your personal data to third parties. We may share your information with payment processors, insurance providers, and law enforcement when required by law."],
              ['5. Data Retention', "We retain booking and customer records for a minimum of 7 years as required by Kenyan tax and business regulations (Income Tax Act, VAT Act). You may request deletion of your personal data subject to these legal retention requirements."],
              ['6. Your Rights', "Under Kenya's Data Protection Act 2019, you have the right to access, correct, or request deletion of your personal data." . ($company_email ? " Contact us at {$company_email} to exercise these rights." : '')],
              ['7. Cookies', "Our website uses session cookies for essential functionality (keeping you logged in, CSRF protection). We do not use tracking or advertising cookies."],
            ];
            foreach ($privacy_sections as $i => [$heading, $body]):
            ?>
            <div class="<?= $i > 0 ? 'mt-9' : '' ?>">
              <h2 class="text-base font-medium"><?= $heading ?></h2>
              <?php if ($i === 1): ?>
              <ul class="text-white text-15 mt-2 space-y-2 leading-relaxed list-disc pl-5">
                <li>To process and confirm your booking</li>
                <li>To communicate with you about your reservation</li>
                <li>To process payments securely</li>
                <li>To comply with legal and regulatory requirements in Kenya</li>
                <li>To improve our services and website</li>
              </ul>
              <?php else: ?>
              <div class="text-white text-15 mt-2 leading-relaxed"><p><?= $body ?></p></div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <div class="mt-9">
              <h2 class="text-base font-medium">8. Contact</h2>
              <div class="text-white text-15 mt-2 leading-relaxed">
                <p>For any privacy-related enquiries, please contact us via the details on our <a href="/contact" class="text-blue-1 hover:underline">Contact page</a>.</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
