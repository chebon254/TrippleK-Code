<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$company_name  = get_setting('company_name', 'Tripple K Car Hire');
$company_email = get_setting('company_email', '');
$company_phone = get_setting('company_phone', '');

$page_title       = 'Terms & Conditions | ' . $company_name;
$page_description = 'Car hire rental terms and conditions for ' . $company_name . ' — authorised use, financial liabilities, damage reporting, vehicle care, and privacy policy.';
$current_page     = 'terms';
require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow">

  <!-- Banner -->
  <section class="relative flex items-center justify-center bg-cover bg-center bg-no-repeat pt-32 pb-14"
    style="background-image:url(/assets/images/backgrounds/adolfo-felix-Yi9-QIObQ1o-unsplash.jpg)">
    <div class="absolute inset-0 bg-dark-1/70"></div>
    <div class="relative container text-center">
      <h1 class="text-3xl font-semibold text-white md:text-5xl">Terms &amp; Conditions</h1>
      <p class="mt-3 text-light-1">Please read these carefully before making a booking.</p>
    </div>
  </section>

  <section class="py-16 md:py-24" x-data="{ activeTab: 'terms' }">
    <div class="container">
      <div class="grid gap-8 lg:grid-cols-12">

        <!-- Sidebar nav -->
        <div class="lg:col-span-3">
          <div class="sticky top-28 rounded border border-border bg-dark-3 p-6">
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-light-1">Contents</p>
            <nav class="space-y-1" role="tablist">
              <?php
              $tabs = [
                ['terms',   'Terms & Conditions'],
                ['privacy', 'Privacy Policy'],
              ];
              foreach ($tabs as [$key, $label]):
              ?>
              <button @click="activeTab = '<?= $key ?>'"
                :class="activeTab === '<?= $key ?>'
                  ? 'bg-blue-1/10 text-blue-1 border border-blue-1/20'
                  : 'text-white hover:bg-dark-4'"
                class="w-full rounded px-4 py-2.5 text-left text-sm font-medium transition-colors">
                <?= $label ?>
              </button>
              <?php endforeach; ?>
            </nav>

            <?php if ($company_phone || $company_email): ?>
            <div class="mt-6 border-t border-border pt-5 text-xs text-light-1 space-y-2">
              <p class="font-semibold text-white text-xs uppercase tracking-widest mb-3">Questions?</p>
              <?php if ($company_phone): ?>
              <a href="tel:<?= h(preg_replace('/\s/', '', $company_phone)) ?>"
                class="flex items-center gap-2 hover:text-white transition-colors">
                <i class="icon-phone text-blue-1"></i><?= h($company_phone) ?>
              </a>
              <?php endif; ?>
              <?php if ($company_email): ?>
              <a href="mailto:<?= h($company_email) ?>"
                class="flex items-center gap-2 hover:text-white transition-colors break-all">
                <i class="icon-mail text-blue-1"></i><?= h($company_email) ?>
              </a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Content -->
        <div class="lg:col-span-9">

          <!-- ── Terms & Conditions panel ── -->
          <div x-show="activeTab === 'terms'"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0">

            <div class="mb-8">
              <h2 class="text-2xl font-semibold text-white">Terms &amp; Conditions</h2>
              <p class="mt-1 text-sm text-light-1">Last updated: <?= date('F Y') ?></p>
            </div>

            <?php
            $sections = [
              [
                'title' => 'Authorised Use &amp; Driving',
                'items' => [
                  'Only the driver(s) named in this agreement are authorized to operate the vehicle.',
                  'All drivers must be at least 24 years of age and must hold a valid driving license with a minimum of three (3) years\' driving experience.',
                  'The vehicle shall not be driven under the influence of alcohol, drugs, or any substance that may impair the driver\'s ability to operate the vehicle safely.',
                  'The vehicle shall not be used for racing, speed testing, towing, overloading, commercial haulage, or any unlawful purpose.',
                  'The hirer shall not transport fare-paying passengers, illegal goods, or prohibited substances.',
                  'The vehicle shall not be taken outside the Republic of Kenya without prior written consent from the Company.',
                ],
              ],
              [
                'title' => 'Financial Liabilities',
                'items' => [
                  'The hirer shall be solely responsible for the payment of all traffic fines, parking penalties, toll charges, and any other violations incurred during the hire period.',
                  'An administrative penalty of <strong class="text-white">KES 5,000</strong> per offence shall apply for any unpaid fines requiring the Company\'s intervention.',
                  'The cost of panel repainting shall be <strong class="text-white">KES 11,000</strong> per affected panel.',
                  'Any tampering, disconnection, or interference with the vehicle\'s speedometer or mileage recording device shall attract charges equivalent to the applicable daily hire rate, in addition to the full cost of repair or replacement.',
                  'Any mileage exceeding the agreed limit shall attract an additional charge equivalent to 100% of the total booking value, unless otherwise agreed in writing.',
                  'In the event of cancellation, the hirer shall forfeit the full booking amount together with an additional charge equivalent to three (3) days\' hire. No refunds shall be issued once the vehicle has been handed over to the hirer.',
                  'The hirer shall settle all amounts due within the agreed payment period. In the event of delayed payment or refusal to pay, the Company reserves the right to pursue all lawful means available to recover outstanding amounts, including repossession of the vehicle and legal action.',
                ],
              ],
              [
                'title' => 'Accidents &amp; Damage Reporting',
                'items' => [
                  'Any accident, theft, loss, or damage involving the vehicle must be reported to both the Company and the nearest police station within <strong class="text-white">24 hours</strong> of occurrence.',
                  'The hirer shall provide the Company with a Police Abstract and a written incident report within <strong class="text-white">48 hours</strong>.',
                  'In the event of damage to the vehicle, the hirer shall be liable up to <strong class="text-white">KES 300,000</strong> for saloon cars and other small vehicles; and <strong class="text-white">KES 500,000</strong> for premium, luxury, SUVs, and other large vehicles.',
                  'The hirer shall be responsible for all costs relating to towing, recovery, repairs, replacement parts, and any related expenses arising from the incident.',
                  'Where the Company\'s insurance cover is limited to third-party risks, the hirer shall be liable for any uninsured losses, damages arising from riots or civil disturbances, and total loss of the vehicle.',
                ],
              ],
              [
                'title' => 'Vehicle Care &amp; Fuel',
                'items' => [
                  'The vehicle shall be returned in the same condition and with the same fuel level as when it was issued to the hirer.',
                  'During the hire period, the hirer shall ensure that the vehicle is properly maintained, including monitoring engine oil, brake fluid, coolant levels, and tyre pressure.',
                  'The hirer shall take all reasonable precautions to secure the vehicle whenever it is unattended.',
                  'The hirer shall bear the cost of tyre damage, punctures, lost tools, missing accessories, and any damage resulting from negligence or misuse.',
                  'No modifications, alterations, or installations may be made to the vehicle without the Company\'s prior written approval.',
                  'Vehicle returns shall only be accepted between <strong class="text-white">8:00 a.m. and 6:00 p.m.</strong>, Monday to Sunday, unless otherwise agreed in writing.',
                ],
              ],
            ];
            foreach ($sections as $idx => $section):
            ?>
            <div class="<?= $idx > 0 ? 'mt-10' : '' ?> rounded border border-border bg-dark-3 p-6">
              <h3 class="mb-4 text-base font-semibold text-white"><?= $section['title'] ?></h3>
              <ul class="space-y-3">
                <?php foreach ($section['items'] as $item): ?>
                <li class="flex gap-3 text-sm text-white/75 leading-relaxed">
                  <span class="mt-0.5 flex-shrink-0 text-blue-1">&#9656;</span>
                  <span><?= $item ?></span>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
            <?php endforeach; ?>

            <!-- Signature acknowledgement -->
            <div class="mt-8 rounded border border-yellow-3/30 bg-yellow-3/5 p-6">
              <p class="text-sm text-white/70 leading-relaxed italic">
                <strong class="text-white not-italic">By confirming a booking</strong>, the hirer confirms that they are the sole authorized driver of the vehicle (unless additional authorized drivers have been specified), acknowledges that they have read, understood, and agreed to all the terms and conditions contained herein, and accepts full responsibility for the vehicle throughout the entire hire period until it is returned to the Company.
              </p>
            </div>

          </div><!-- /terms panel -->

          <!-- ── Privacy Policy panel ── -->
          <div x-show="activeTab === 'privacy'" x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0">

            <div class="mb-8">
              <h2 class="text-2xl font-semibold text-white">Privacy Policy</h2>
              <p class="mt-1 text-sm text-light-1">Last updated: <?= date('F Y') ?></p>
            </div>

            <?php
            $privacy = [
              ['Information We Collect', 'When you make a booking or register, we collect your full name, email address, phone number, date of birth, ID/passport number, driver\'s licence details, and any documents you upload. We also collect usage data such as IP addresses for security purposes.'],
              ['How We Use Your Information', null],
              ['Payment Data', 'Payment processing is handled by Paystack and M-Pesa (Safaricom Daraja API). We do not store your card details. M-Pesa transactions are subject to Safaricom\'s privacy policy.'],
              ['Data Sharing', 'We do not sell, rent, or trade your personal data to third parties. We may share your information with payment processors, insurance providers, and law enforcement when required by law.'],
              ['Data Retention', 'We retain booking and customer records for a minimum of 7 years as required by Kenyan tax and business regulations (Income Tax Act, VAT Act). You may request deletion of your personal data subject to these legal retention requirements.'],
              ['Your Rights', 'Under Kenya\'s Data Protection Act 2019, you have the right to access, correct, or request deletion of your personal data.' . ($company_email ? ' Contact us at <a href="mailto:' . h($company_email) . '" class="text-blue-1 hover:underline">' . h($company_email) . '</a> to exercise these rights.' : '')],
              ['Cookies', 'Our website uses session cookies for essential functionality (keeping you logged in, CSRF protection). We do not use tracking or advertising cookies.'],
            ];
            foreach ($privacy as $idx => [$heading, $body]):
            ?>
            <div class="<?= $idx > 0 ? 'mt-8' : '' ?> rounded border border-border bg-dark-3 p-6">
              <h3 class="mb-3 text-base font-semibold text-white"><?= $idx + 1 ?>. <?= $heading ?></h3>
              <?php if ($idx === 1): ?>
              <ul class="space-y-2">
                <?php foreach (['To process and confirm your booking', 'To communicate with you about your reservation', 'To process payments securely', 'To comply with legal and regulatory requirements in Kenya', 'To improve our services and website'] as $point): ?>
                <li class="flex gap-3 text-sm text-white/75 leading-relaxed">
                  <span class="mt-0.5 flex-shrink-0 text-blue-1">&#9656;</span>
                  <span><?= $point ?></span>
                </li>
                <?php endforeach; ?>
              </ul>
              <?php else: ?>
              <p class="text-sm text-white/75 leading-relaxed"><?= $body ?></p>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <div class="mt-8 rounded border border-border bg-dark-3 p-6">
              <h3 class="mb-3 text-base font-semibold text-white">8. Contact</h3>
              <p class="text-sm text-white/75 leading-relaxed">
                For any privacy-related enquiries, please contact us via the details on our
                <a href="/contact" class="text-blue-1 hover:underline">Contact page</a>.
              </p>
            </div>

          </div><!-- /privacy panel -->

        </div><!-- /content col -->
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
