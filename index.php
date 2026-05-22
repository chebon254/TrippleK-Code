<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title       = get_setting('company_name', APP_NAME);
$page_description = get_setting('company_tagline', 'Affordable car hire, airport transfers, chauffeur and wedding packages in Kenya.');

$db = get_db();

$featured_cars = $db->query("
    SELECT c.id, c.make, c.model, c.year, c.transmission, c.passenger_capacity,
           c.luggage_capacity, c.price_per_day, c.thumbnail_path,
           cat.name AS category_name, cat.slug AS category_slug
    FROM cars c
    JOIN car_categories cat ON c.category_id = cat.id
    WHERE c.is_featured = 1 AND c.status = 'available'
    ORDER BY c.sort_order ASC, c.id DESC
    LIMIT 8
")->fetchAll();

if (empty($featured_cars)) {
    $featured_cars = $db->query("
        SELECT c.id, c.make, c.model, c.year, c.transmission, c.passenger_capacity,
               c.luggage_capacity, c.price_per_day, c.thumbnail_path,
               cat.name AS category_name, cat.slug AS category_slug
        FROM cars c
        JOIN car_categories cat ON c.category_id = cat.id
        WHERE c.status = 'available'
        ORDER BY c.id DESC LIMIT 8
    ")->fetchAll();
}

$categories = $db->query('SELECT slug, name FROM car_categories ORDER BY sort_order')->fetchAll();
$company_wa = get_setting('company_whatsapp', '');

require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow">

  <!-- ===== Hero ===== -->
  <section class="relative z-20 pt-35 md:pt-50">
    <div class="absolute top-0 left-0 -z-10 h-full w-full bg-gradient-to-b from-dark-2 to-dark-1"></div>

    <div class="container mx-auto h-full px-4">
      <div class="mb-6 flex flex-col items-center justify-center text-center" data-aos="fade-up" data-aos-delay="0">
        <h1 class="md:text-60 text-white text-3xl leading-tight font-semibold sm:text-4xl">
          <?= h(get_setting('company_tagline', 'Affordable Car Hire in Kenya')) ?>
        </h1>
        <p class="text-white mt-3">
          Self-drive, airport transfers, chauffeur &amp; wedding packages across Kenya.
        </p>
      </div>

      <!-- Search form -->
      <div class="relative z-20 mx-auto w-full max-w-[900px] rounded bg-dark-3 shadow-1 border border-border lg:p-5"
        x-data="{
          location: '',
          dateFrom: '',
          dateTo: '',
          passengers: 1,
          doSearch() {
            let p = {};
            if (this.location) p.location = this.location;
            if (this.dateFrom) p.date_from = this.dateFrom;
            if (this.dateTo)   p.date_to   = this.dateTo;
            if (this.passengers > 1) p.passengers = this.passengers;
            window.location.href = '/cars.php?' + new URLSearchParams(p).toString();
          }
        }">
        <div class="flex w-full items-center max-lg:flex-col lg:gap-7.5" data-aos="fade-up" data-aos-delay="0">
          <div class="grid w-full lg:grid-cols-3 lg:gap-7.5">

            <!-- Location -->
            <div class="relative max-lg:p-5 lg:pl-6.5">
              <span class="text-15 text-white block font-medium">Pickup Location</span>
              <input type="text" placeholder="City or Airport" x-model="location"
                class="outline-none w-full text-light-1 placeholder-light-1" />
            </div>

            <!-- Dates -->
            <div class="relative border-[#2a2a2a] max-lg:border-y max-lg:p-5 lg:border-x lg:px-7.5">
              <span class="text-15 text-white block font-medium">Pickup Date</span>
              <input type="date" x-model="dateFrom" :min="new Date().toISOString().slice(0,10)"
                class="outline-none text-light-1 w-full" />
            </div>

            <!-- Return date -->
            <div class="relative max-lg:p-5">
              <span class="text-15 text-white block font-medium">Return Date</span>
              <input type="date" x-model="dateTo" :min="dateFrom || new Date().toISOString().slice(0,10)"
                class="outline-none text-light-1 w-full" />
            </div>
          </div>

          <!-- Search button -->
          <div class="max-lg:w-full max-lg:px-5 max-lg:pb-5">
            <button type="button" @click="doSearch()"
              class="bg-blue-1 hover:bg-yellow-3 text-white flex h-12 items-center gap-2 rounded px-8 text-sm font-medium whitespace-nowrap transition-colors lg:h-14 lg:text-base">
              <i class="icon-search text-xs"></i>
              Search
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Hero Image Slider -->
    <div x-data="hero8()" x-init="init()" class="relative z-10 -mt-12.5 px-6">
      <div class="absolute bottom-0 left-1/2 -z-10 h-1/2 w-dvw -translate-x-1/2 bg-dark-1"></div>
      <div class="swiper relative mx-auto max-w-[1500px]" x-ref="masthead" data-aos="fade-up" data-aos-delay="100">
        <div class="swiper-wrapper">
          <div class="swiper-slide overflow-hidden rounded-2xl">
            <div class="relative aspect-150/65 w-full overflow-hidden rounded-2xl bg-cover bg-center bg-no-repeat max-md:py-40"
              style="background-image: url(/assets/images/masthead/8/1.png)"></div>
          </div>
          <div class="swiper-slide overflow-hidden rounded-2xl">
            <div class="relative aspect-150/65 w-full overflow-hidden rounded-2xl bg-cover bg-center bg-no-repeat max-md:py-40"
              style="background-image: url(/assets/images/masthead/8/1.png)"></div>
          </div>
          <div class="swiper-slide overflow-hidden rounded-2xl">
            <div class="relative aspect-150/65 w-full overflow-hidden rounded-2xl bg-cover bg-center bg-no-repeat max-md:py-40"
              style="background-image: url(/assets/images/masthead/8/1.png)"></div>
          </div>
        </div>
        <div>
          <button class="slider-prev absolute top-1/2 left-3 z-20 flex size-12.5 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-blue-1 font-bold text-blue-1 duration-300 hover:bg-blue-1 hover:text-white">
            <span class="icon-arrow-left text-sm"></span>
          </button>
          <button class="slider-next absolute top-1/2 right-3 z-20 flex size-12.5 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-blue-1 font-bold text-blue-1 duration-300 hover:bg-blue-1 hover:text-white">
            <span class="icon-arrow-right text-sm"></span>
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== Why Choose Us ===== -->
  <section class="pt-20 md:pt-30">
    <div class="container">
      <div class="mb-10 text-center">
        <h2 class="text-white mb-3 text-3xl font-semibold">Why Choose Us</h2>
        <p class="text-light-1 text-base">Trusted by hundreds of customers across Kenya</p>
      </div>
      <div class="-mx-4 flex flex-wrap justify-center gap-y-10 md:justify-between">
        <div class="w-full px-4 sm:w-1/2 md:w-1/3 xl:w-1/4" data-aos="fade">
          <div class="mx-auto flex max-w-[300px] flex-col items-center text-center">
            <div class="flex items-center justify-center">
              <img src="/assets/src/images/featureIcons/1/1.svg" alt="Best Price Guarantee" class="size-17.5">
            </div>
            <div class="mt-8">
              <h4 class="text-white text-lg font-medium">Best Price Guarantee</h4>
              <p class="text-15 text-light-1 mt-2">Transparent pricing with no hidden charges — what you see is what you pay.</p>
            </div>
          </div>
        </div>
        <div class="w-full px-4 sm:w-1/2 md:w-1/3 xl:w-1/4" data-aos="fade" data-aos-delay="100">
          <div class="mx-auto flex max-w-[300px] flex-col items-center text-center">
            <div class="flex items-center justify-center">
              <img src="/assets/src/images/featureIcons/1/2.svg" alt="Easy Booking" class="size-17.5">
            </div>
            <div class="mt-8">
              <h4 class="text-white text-lg font-medium">Easy &amp; Quick Booking</h4>
              <p class="text-15 text-light-1 mt-2">Book online in minutes via M-Pesa, card or bank transfer. Instant confirmation.</p>
            </div>
          </div>
        </div>
        <div class="w-full px-4 sm:w-1/2 md:w-1/3 xl:w-1/4" data-aos="fade" data-aos-delay="200">
          <div class="mx-auto flex max-w-[300px] flex-col items-center text-center">
            <div class="flex items-center justify-center">
              <img src="/assets/src/images/featureIcons/1/3.svg" alt="24/7 Support" class="size-17.5">
            </div>
            <div class="mt-8">
              <h4 class="text-white text-lg font-medium">Customer Care 24/7</h4>
              <p class="text-15 text-light-1 mt-2">Our team is always reachable via WhatsApp or phone for any help on the road.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== Popular Car Hire ===== -->
  <section class="pt-20 md:pt-30">
    <div class="container">
      <div class="mb-10 text-center">
        <h2 class="text-white text-3xl font-semibold">Popular Car Hire</h2>
        <p class="text-light-1 mt-2">Top picks from our Kenya fleet</p>
      </div>

      <?php if (!empty($featured_cars)): ?>
      <div class="relative" x-data="carsCarousel8()" data-aos="fade-left">
        <div class="swiper" x-ref="wrap">
          <div class="swiper-wrapper">
            <?php foreach ($featured_cars as $car):
              $thumb = $car['thumbnail_path']
                ? UPLOAD_URL . ltrim($car['thumbnail_path'], '/')
                : '/assets/src/images/cars/1.png';
            ?>
            <div class="swiper-slide transition-all duration-300">
              <a href="/car.php?id=<?= (int)$car['id'] ?>" class="group block">
                <div class="relative mb-4 aspect-30/25 overflow-hidden rounded border border-[#2a2a2a]">
                  <img src="<?= h($thumb) ?>" alt="<?= h($car['make'] . ' ' . $car['model']) ?>"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    onerror="this.src='/assets/src/images/cars/1.png'">
                </div>
                <div>
                  <div class="text-light-1 mb-2 flex items-center text-sm">
                    <span>Nairobi, Kenya</span>
                    <span class="bg-light-1 mx-2 h-1 w-1 rounded-full"></span>
                    <span class="uppercase"><?= h($car['category_name']) ?></span>
                  </div>
                  <h4 class="text-white mb-2 text-lg leading-tight font-medium">
                    <?= h($car['make'] . ' ' . $car['model']) ?>
                    <span class="text-light-1 text-sm font-normal"><?= (int)$car['year'] ?></span>
                  </h4>
                  <div class="flex flex-wrap items-center gap-x-5 gap-y-2 pt-1">
                    <div class="text-white flex items-center text-sm">
                      <span class="icon-user-2 mr-2"></span><span><?= (int)$car['passenger_capacity'] ?></span>
                    </div>
                    <div class="text-white flex items-center text-sm">
                      <span class="icon-luggage mr-2"></span><span><?= (int)$car['luggage_capacity'] ?></span>
                    </div>
                    <div class="text-white flex items-center text-sm">
                      <span class="icon-transmission mr-2"></span><span><?= ucfirst(h($car['transmission'])) ?></span>
                    </div>
                  </div>
                  <div class="text-light-1 mt-4">
                    From <span class="text-white font-medium"><?= format_kes($car['price_per_day']) ?></span> / day
                  </div>
                </div>
              </a>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <button x-ref="prev"
            class="slider-prev absolute top-1/3 -left-5 z-20 flex size-12.5 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-border text-white duration-300 hover:bg-blue-1 hover:border-blue-1 shadow">
            <span class="icon-chevron-left text-sm"></span>
          </button>
          <button x-ref="next"
            class="slider-next absolute top-1/3 -right-5 z-20 flex size-12.5 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-border text-white duration-300 hover:bg-blue-1 hover:border-blue-1 shadow">
            <span class="icon-chevron-right text-sm"></span>
          </button>
        </div>
      </div>
      <?php else: ?>
      <div class="py-12 text-center">
        <p class="text-light-1">No cars available yet. Check back soon or <a href="/contact.php" class="text-blue-1 underline">contact us</a>.</p>
      </div>
      <?php endif; ?>

      <div class="mt-10 text-center">
        <a href="/cars.php"
          class="text-blue-1 border-blue-1 hover:bg-blue-1 hover:text-white inline-flex h-12 items-center rounded border px-8 text-sm font-medium transition-colors">
          See All Cars
        </a>
      </div>
    </div>
  </section>

  <!-- ===== Services ===== -->
  <section id="services" class="pt-20 md:pt-30">
    <div class="container">
      <div class="mb-10 text-center">
        <h2 class="text-white text-3xl font-semibold">Our Services</h2>
        <p class="text-light-1 mt-2">Everything you need on the road in Kenya</p>
      </div>
      <div class="xs:grid-cols-2 grid gap-7.5 md:grid-cols-4">
        <?php
        $svcs = [
          ['icon'=>'icon-car',      'id'=>'car-hire',         'title'=>'Car Hire',          'link'=>'/cars.php',
           'desc'=>'Self-drive saloons, SUVs and executive cars by the day. Flexible pickup across Nairobi.'],
          ['icon'=>'icon-airplane', 'id'=>'airport-transfer', 'title'=>'Airport Transfer',  'link'=>'/cars.php?service=airport-transfer',
           'desc'=>'Reliable pickup & drop-off at JKIA and Wilson Airport. Meet-and-greet available.'],
          ['icon'=>'icon-shield',   'id'=>'chauffeur',         'title'=>'Chauffeur Service', 'link'=>'/cars.php?service=chauffeur',
           'desc'=>'Professional drivers for corporate travel, VIP events and full-day engagements.'],
          ['icon'=>'icon-heart',    'id'=>'wedding',           'title'=>'Wedding Packages',  'link'=>'/cars.php?service=wedding',
           'desc'=>'Make your big day unforgettable with our elegant executive fleet.'],
        ];
        foreach ($svcs as $i => $svc):
        ?>
        <a href="<?= $svc['link'] ?>" id="<?= $svc['id'] ?>"
          class="rounded border border-border bg-dark-3 p-6 text-center transition-all hover:border-blue-1 hover:shadow-xl"
          data-aos="fade" data-aos-delay="<?= $i * 100 ?>">
          <div class="bg-blue-1/10 text-blue-1 mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full">
            <i class="<?= $svc['icon'] ?> text-2xl"></i>
          </div>
          <h3 class="text-white mb-2 text-base font-medium"><?= $svc['title'] ?></h3>
          <p class="text-15 text-light-1 mt-2"><?= $svc['desc'] ?></p>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===== Browse by Category ===== -->
  <?php if (!empty($categories)): ?>
  <section class="pt-20 md:pt-30">
    <div class="container">
      <div class="mb-10 text-center">
        <h2 class="text-white text-3xl font-semibold">Browse by Category</h2>
      </div>
      <div class="xs:grid-cols-2 grid gap-7.5 md:grid-cols-4">
        <?php foreach ($categories as $i => $cat): ?>
        <a href="/cars.php?category=<?= h($cat['slug']) ?>"
          class="group relative overflow-hidden rounded-xl"
          data-aos="fade" data-aos-delay="<?= $i * 100 ?>">
          <div class="relative aspect-30/25 overflow-hidden rounded-xl bg-dark-1">
            <img src="/assets/src/images/cars/1.png" alt="<?= h($cat['name']) ?>"
              class="h-full w-full object-cover opacity-50 transition-transform duration-300 group-hover:scale-105">
          </div>
          <div class="absolute inset-0 flex flex-col items-center justify-center text-white">
            <h4 class="text-lg font-semibold"><?= h($cat['name']) ?></h4>
            <div class="mt-1 text-sm text-white/80">Explore fleet &rarr;</div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ===== Booking Requirements ===== -->
  <section class="pt-20 md:pt-30">
    <div class="container">
      <div class="row y-gap-40 items-center xl:gap-20">
        <div class="col-xl-5" data-aos="fade-right">
          <h2 class="text-white mb-6 text-3xl font-semibold">Booking Requirements</h2>
          <ul class="space-y-4">
            <?php
            $reqs = [
              'Original National ID or Valid Passport',
              'Valid Driving License (min. 2 years experience)',
              'Entry Visa — for foreign nationals',
              'Security Deposit: KES ' . number_format((float)get_setting('security_deposit_min','15000'))
                . ' – ' . number_format((float)get_setting('security_deposit_max','50000')),
            ];
            foreach ($reqs as $r):
            ?>
            <li class="flex items-start gap-3">
              <div class="bg-blue-1 mt-1 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full">
                <i class="icon-check text-white text-xs"></i>
              </div>
              <span class="text-white"><?= h($r) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <div class="mt-8 flex flex-wrap gap-4">
            <a href="/cars.php"
              class="bg-blue-1 hover:bg-yellow-3 rounded px-6 py-3 text-sm font-medium text-white transition-colors">
              Book a Car
            </a>
            <?php if ($company_wa): ?>
            <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $company_wa)) ?>?text=Hello%2C+I%27d+like+to+enquire+about+a+booking."
              target="_blank" rel="noopener"
              class="flex items-center gap-2 rounded border border-green-500 px-6 py-3 text-sm font-medium text-green-400 hover:bg-green-500/10 transition-colors">
              <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.556 4.116 1.526 5.845L.057 23.428c-.073.244.159.468.4.395l5.697-1.454A11.946 11.946 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.815 9.815 0 0 1-5.027-1.381l-.361-.215-3.724.95.985-3.635-.234-.373A9.818 9.818 0 1 1 12 21.818z"/></svg>
              Chat on WhatsApp
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-xl-6 col-offset-xl-1">
          <div class="relative" data-aos="fade-left">
            <img src="/assets/src/images/masthead/8/1.png" alt="Car rental Kenya"
              class="rounded-2xl shadow-xl w-full object-cover"
              onerror="this.style.display='none'">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== CTA Banner ===== -->
  <section class="pt-20 md:pt-30 pb-20 md:pb-30">
    <div class="container">
      <div class="bg-blue-1 relative overflow-hidden rounded-2xl p-12 text-center text-white">
        <h2 class="mb-4 text-3xl font-semibold">Ready to Hit the Road?</h2>
        <p class="mb-8 text-lg text-white/80">Browse our full fleet and book online in minutes.</p>
        <a href="/cars.php"
          class="inline-block rounded bg-dark-1 px-8 py-3.5 text-sm font-semibold text-white hover:bg-dark-3 transition-colors border border-white/20">
          Browse All Cars
        </a>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
