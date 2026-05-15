<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

http_response_code(404);

$page_title   = 'Page Not Found';
$current_page = '404';
require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow">
  <section class="py-20 md:py-30">
    <div class="container">
      <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2 xl:gap-40">

        <!-- Left: Big 404 -->
        <div class="order-2 lg:order-1 text-center" data-aos="fade-right">
          <div class="text-[80px] leading-none font-bold tracking-tight text-dark-1/10 sm:text-[120px] select-none">
            40<span class="text-blue-1">4</span>
          </div>
        </div>

        <!-- Right: Content -->
        <div class="order-1 max-w-[470px] lg:order-2" data-aos="fade-left">
          <h2 class="text-dark-1 mt-6 text-3xl leading-tight font-semibold">
            Oops! It looks like you're lost.
          </h2>
          <p class="text-15 text-light-1 mt-4">
            The page you're looking for isn't available. Try searching again or use the links below.
          </p>
          <div class="mt-10 flex flex-wrap gap-4">
            <a href="/index.php"
              class="bg-blue-1 hover:bg-dark-1 inline-flex h-15 items-center justify-center gap-2 rounded px-8 font-medium text-white transition">
              <i class="icon-home text-sm"></i> Back to Home
            </a>
            <a href="/cars.php"
              class="border-border hover:border-dark-1 text-15 inline-flex h-15 items-center gap-2 rounded border px-8 font-medium text-dark-1 transition">
              Browse Cars
            </a>
          </div>
          <div class="mt-10">
            <p class="text-15 text-light-1 mb-3">Looking for something specific?</p>
            <div class="flex flex-wrap gap-2 text-sm">
              <a href="/cars.php" class="rounded border border-border px-4 py-2 text-dark-1 hover:border-blue-1 hover:text-blue-1 transition">Car Hire</a>
              <a href="/cars.php?service=airport-transfer" class="rounded border border-border px-4 py-2 text-dark-1 hover:border-blue-1 hover:text-blue-1 transition">Airport Transfer</a>
              <a href="/cars.php?service=chauffeur" class="rounded border border-border px-4 py-2 text-dark-1 hover:border-blue-1 hover:text-blue-1 transition">Chauffeur</a>
              <a href="/contact.php" class="rounded border border-border px-4 py-2 text-dark-1 hover:border-blue-1 hover:text-blue-1 transition">Contact Us</a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
