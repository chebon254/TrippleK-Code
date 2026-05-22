<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$company_name     = get_setting('company_name',    'Tripple K Car Hire');
$company_phone    = get_setting('company_phone',   '');
$company_whatsapp = get_setting('company_whatsapp', '');

$db = get_db();
$fleet_count   = (int)$db->query("SELECT COUNT(*) FROM cars WHERE status != 'inactive'")->fetchColumn();
$booking_count = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed','active','completed')")->fetchColumn();

$page_title   = 'About Us';
$current_page = 'about';
require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow">

  <!-- Hero -->
  <section class="relative z-10 flex items-center justify-center bg-cover bg-center bg-no-repeat py-20 lg:py-25"
    style="background-image: url(/assets/images/cars/hero-bg.jpg)">
    <div class="bg-dark-1/50 absolute inset-0 -z-10"></div>
    <div class="container">
      <div class="text-center">
        <h1 class="md:text-40 text-3xl font-semibold text-white"><?= h($company_name) ?></h1>
        <p class="mt-4 text-white">Your trusted car hire partner in Kenya</p>
      </div>
    </div>
  </section>

  <!-- Why Choose Us -->
  <section class="pt-20 md:pt-30">
    <div class="container">
      <div class="mb-10 text-center">
        <h2 class="text-white mb-3 text-3xl font-semibold">Why Choose Us</h2>
        <p class="text-light-1 text-base">We go the extra mile so you don't have to worry</p>
      </div>
      <div class="-mx-4 flex flex-wrap justify-center gap-y-10 md:justify-between">
        <div class="w-full px-4 sm:w-1/2 md:w-1/3 xl:w-1/4" data-aos="fade">
          <div class="mx-auto flex max-w-[300px] flex-col items-center text-center">
            <img src="/assets/src/images/featureIcons/1/1.svg" alt="Best Price Guarantee" class="size-17.5">
            <div class="mt-8">
              <h4 class="text-white text-lg font-medium">Best Price Guarantee</h4>
              <p class="text-15 text-light-1 mt-2">Competitive daily and weekly rates with no hidden charges.</p>
            </div>
          </div>
        </div>
        <div class="w-full px-4 sm:w-1/2 md:w-1/3 xl:w-1/4" data-aos="fade" data-aos-delay="100">
          <div class="mx-auto flex max-w-[300px] flex-col items-center text-center">
            <img src="/assets/src/images/featureIcons/1/2.svg" alt="Easy & Quick Booking" class="size-17.5">
            <div class="mt-8">
              <h4 class="text-white text-lg font-medium">Easy &amp; Quick Booking</h4>
              <p class="text-15 text-light-1 mt-2">Book online in minutes and get instant confirmation.</p>
            </div>
          </div>
        </div>
        <div class="w-full px-4 sm:w-1/2 md:w-1/3 xl:w-1/4" data-aos="fade" data-aos-delay="200">
          <div class="mx-auto flex max-w-[300px] flex-col items-center text-center">
            <img src="/assets/src/images/featureIcons/1/3.svg" alt="Customer Care 24/7" class="size-17.5">
            <div class="mt-8">
              <h4 class="text-white text-lg font-medium">Customer Care 24/7</h4>
              <p class="text-15 text-light-1 mt-2">We're available around the clock for bookings and roadside support.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Who We Are -->
  <section class="pt-20 md:pt-30">
    <div class="container">
      <div class="grid gap-7.5 lg:grid-cols-2">
        <div class="w-full self-center lg:max-w-[520px]">
          <h2 class="text-white text-3xl font-semibold">About <?= h($company_name) ?></h2>
          <p class="text-light-1 mt-2 text-base">Premier car hire and transport in Kenya</p>
          <p class="text-white text-15 mt-15">
            <?= h($company_name) ?> is a trusted car hire and transport service company based in Kenya.
            We provide reliable, comfortable, and affordable vehicle rental solutions for individuals,
            corporates, and special occasions across the country.
            <br><br>
            Whether you need a daily car hire, airport transfer, executive chauffeur, or a vehicle for
            your wedding day, we have the right car and the professional service to match.
          </p>
          <div class="mt-8 flex flex-wrap gap-4">
            <a href="/cars.php"
              class="bg-blue-1 hover:bg-dark-1 text-15 inline-flex h-15 items-center gap-2 rounded px-6 font-medium text-white transition">
              Browse Our Fleet
            </a>
            <?php if ($company_whatsapp): ?>
            <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $company_whatsapp)) ?>?text=Hello%2C+I%27d+like+to+learn+more"
              target="_blank"
              class="border-border hover:border-blue-1 text-15 inline-flex h-15 items-center gap-2 rounded border px-6 font-medium text-white transition">
              WhatsApp Us
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div>
          <div class="grid grid-cols-2 gap-4">
            <div class="rounded border border-border bg-blue-1/10 p-6 text-center hover:border-blue-1">
              <i class="icon-car text-blue-1 text-4xl"></i>
              <div class="mt-3 font-semibold text-white">Car Hire</div>
              <p class="text-15 text-light-1 mt-1">Daily &amp; weekly rentals</p>
            </div>
            <div class="rounded border border-border bg-dark-3 p-6 text-center hover:border-blue-1">
              <i class="icon-plane text-blue-1 text-4xl"></i>
              <div class="mt-3 font-semibold text-white">Airport Transfer</div>
              <p class="text-15 text-light-1 mt-1">On-time, every time</p>
            </div>
            <div class="rounded border border-border bg-dark-3 p-6 text-center hover:border-blue-1">
              <i class="icon-user text-white text-4xl"></i>
              <div class="mt-3 font-semibold text-white">Chauffeur</div>
              <p class="text-15 text-light-1 mt-1">Professional drivers</p>
            </div>
            <div class="rounded border border-border bg-blue-1/10 p-6 text-center hover:border-blue-1">
              <i class="icon-heart text-blue-1 text-4xl"></i>
              <div class="mt-3 font-semibold text-white">Wedding</div>
              <p class="text-15 text-light-1 mt-1">Elegant &amp; memorable</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats -->
  <section class="pt-15">
    <div class="container">
      <div class="border-border grid grid-cols-1 gap-8 border-b pb-10 text-center sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <div class="text-white text-3xl font-semibold sm:text-40"><?= $fleet_count ?>+</div>
          <div class="text-light-1 mt-2 text-sm">Vehicles in Fleet</div>
        </div>
        <div>
          <div class="text-white text-3xl font-semibold sm:text-40"><?= $booking_count ?>+</div>
          <div class="text-light-1 mt-2 text-sm">Happy Customers</div>
        </div>
        <div>
          <div class="text-white text-3xl font-semibold sm:text-40">4</div>
          <div class="text-light-1 mt-2 text-sm">Services Offered</div>
        </div>
        <div>
          <div class="text-white text-3xl font-semibold sm:text-40">24/7</div>
          <div class="text-light-1 mt-2 text-sm">Customer Support</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Requirements + CTA -->
  <section class="py-20 md:py-30">
    <div class="container">
      <div class="grid gap-7.5 lg:grid-cols-2 lg:items-start">
        <div>
          <h2 class="text-white text-3xl font-semibold">Rental Requirements</h2>
          <p class="text-light-1 text-15 mt-2">To hire a vehicle from us, you'll need the following:</p>
          <ul class="mt-6 space-y-3">
            <?php
            $requirements = [
              'Valid National ID or Passport',
              "Valid driver's licence (for self-drive hires)",
              'Security deposit: KES 15,000 – 50,000 depending on vehicle',
              'Minimum age: 23 years',
              'At least 2 years of driving experience',
            ];
            foreach ($requirements as $req):
            ?>
            <li class="flex items-start gap-3">
              <i class="icon-check text-blue-1 mt-0.5 shrink-0"></i>
              <span class="text-white text-15"><?= h($req) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="rounded bg-blue-1 p-8 text-white">
          <h3 class="text-2xl font-semibold">Ready to Book?</h3>
          <p class="text-15 mt-2 text-white/80">Browse our fleet and book your car online in minutes. Instant confirmation on most vehicles.</p>
          <div class="mt-6 space-y-3">
            <a href="/cars.php"
              class="block w-full rounded bg-dark-1 py-3 text-center text-sm font-semibold text-blue-1 hover:bg-dark-3 border border-blue-1/30 transition">
              Browse Available Cars
            </a>
            <?php if ($company_phone): ?>
            <a href="tel:<?= h(preg_replace('/\s+/', '', $company_phone)) ?>"
              class="block w-full rounded border border-white/40 py-3 text-center text-sm font-medium text-white hover:bg-white/10 transition">
              Call <?= h($company_phone) ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
