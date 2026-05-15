<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$car_id = (int)($_GET['id'] ?? 0);
if (!$car_id) {
    http_response_code(302);
    header('Location: /cars.php');
    exit;
}

$db   = get_db();
$stmt = $db->prepare('
    SELECT c.*, cat.name AS category_name, cat.slug AS category_slug
    FROM cars c
    JOIN car_categories cat ON c.category_id = cat.id
    WHERE c.id = ? LIMIT 1
');
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car || $car['status'] === 'inactive') {
    http_response_code(302);
    header('Location: /cars.php');
    exit;
}

$images_stmt = $db->prepare('SELECT file_path, alt_text, is_primary FROM car_images WHERE car_id = ? ORDER BY sort_order, id');
$images_stmt->execute([$car_id]);
$car_images = $images_stmt->fetchAll();

$feats_stmt = $db->prepare('SELECT feature, value FROM car_features WHERE car_id = ?');
$feats_stmt->execute([$car_id]);
$car_features = $feats_stmt->fetchAll();

$services = $db->query('SELECT id, slug, name FROM services WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

// Build gallery URLs
$gallery = [];
foreach ($car_images as $img) {
    $gallery[] = UPLOAD_URL . ltrim($img['file_path'], '/');
}
$primary_url = $car['thumbnail_path']
    ? UPLOAD_URL . ltrim($car['thumbnail_path'], '/')
    : '/assets/images/cars/1.png';
if (empty($gallery)) {
    $gallery[] = $primary_url;
}

$page_title       = h($car['make'] . ' ' . $car['model']) . ' — Car Hire Kenya';
$page_description = 'Hire a ' . $car['make'] . ' ' . $car['model'] . ' in Kenya from ' . format_kes($car['price_per_day']) . ' per day.';

$deposit_min = get_setting('security_deposit_min', '15000');
$deposit_max = get_setting('security_deposit_max', '50000');

$gallery_json = json_encode(array_values($gallery));
$active_json  = json_encode($gallery[0] ?? '');

$nav_white = true;
require __DIR__ . '/includes/header.php';
?>

<script>
  var _carGallery = <?= $gallery_json ?>;
  var _carActive  = <?= $active_json ?>;
</script>
<main class="flex-grow pt-20" x-data="Object.assign(carSinglePage(), { gallery: _carGallery, activeImage: _carActive })">

  <!-- Breadcrumb -->
  <div class="bg-light-2">
    <section class="border-border flex items-center border-t py-5">
      <div class="container">
        <div class="flex flex-wrap items-center justify-between gap-y-2.5">
          <div class="text-light-1 flex flex-wrap items-center gap-x-2.5 gap-y-1.5 text-sm">
            <a href="/index.php" class="hover:text-blue-1">Home</a>
            <span>&gt;</span>
            <a href="/cars.php" class="hover:text-blue-1">Car Hire</a>
            <span>&gt;</span>
            <span class="text-dark-1"><?= h($car['make'] . ' ' . $car['model']) ?></span>
          </div>
          <a href="/cars.php?category=<?= h($car['category_slug']) ?>" class="text-light-1 text-sm hover:text-blue-1">
            Browse <?= h($car['category_name']) ?> Cars
          </a>
        </div>
      </div>
    </section>
  </div>

  <!-- Main Content -->
  <section class="pt-10">
    <div class="container">
      <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">

        <!-- ===== Left Column ===== -->
        <div class="lg:col-span-8">

          <!-- Title & Actions -->
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
              <h1 class="text-26 text-dark-1 font-semibold">
                <?= h($car['make'] . ' ' . $car['model']) ?>
              </h1>
              <div class="flex items-center gap-x-1.5 pt-1.5">
                <i class="icon-location-2 text-light-1 text-base"></i>
                <div class="text-15 text-light-1">Nairobi, Kenya</div>
                <span class="bg-light-1 mx-1 h-1 w-1 rounded-full"></span>
                <span class="text-15 text-light-1 uppercase"><?= h($car['category_name']) ?></span>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <?php if ($car['status'] === 'available'): ?>
              <span class="bg-green-100 text-green-700 rounded-full px-3 py-1 text-sm font-medium">Available</span>
              <?php elseif ($car['status'] === 'hired'): ?>
              <span class="bg-yellow-100 text-yellow-700 rounded-full px-3 py-1 text-sm font-medium">Currently Hired</span>
              <?php else: ?>
              <span class="bg-red-100 text-red-700 rounded-full px-3 py-1 text-sm font-medium">Not Available</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Gallery -->
          <div class="mt-10 gap-3 space-y-5 sm:flex sm:flex-row-reverse">
            <!-- Main Image -->
            <div class="border-border flex-1 overflow-hidden rounded border">
              <img :src="activeImage" alt="<?= h($car['make'] . ' ' . $car['model']) ?>"
                class="h-auto w-full object-cover object-center" />
            </div>
            <!-- Thumbnails -->
            <div class="gap-3 max-sm:grid max-sm:grid-cols-4 sm:max-w-30">
              <template x-for="(image, index) in gallery" :key="index">
                <button @click="activeImage = image"
                  :class="activeImage === image ? 'border-dark-1' : ''"
                  class="border-border aspect-12/10 cursor-pointer overflow-hidden rounded border duration-300">
                  <img :src="image" alt="Car thumbnail" class="h-auto w-full object-cover object-center" />
                </button>
              </template>
            </div>
          </div>

          <!-- Highlights -->
          <div class="mt-10">
            <h3 class="text-2xl font-semibold">Car Highlights</h3>
            <div class="mt-6 grid grid-cols-2 gap-6 sm:grid-cols-4">
              <div class="flex items-start gap-3">
                <i class="icon icon-user-2 mt-1 text-2xl text-gray-700"></i>
                <div>
                  <div class="text-sm text-gray-500">Passengers</div>
                  <div class="font-medium"><?= (int)$car['passenger_capacity'] ?></div>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <i class="icon icon-luggage mt-1 text-2xl text-gray-700"></i>
                <div>
                  <div class="text-sm text-gray-500">Luggage</div>
                  <div class="font-medium"><?= (int)$car['luggage_capacity'] ?></div>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <i class="icon icon-transmission mt-1 text-2xl text-gray-700"></i>
                <div>
                  <div class="text-sm text-gray-500">Transmission</div>
                  <div class="font-medium"><?= ucfirst(h($car['transmission'])) ?></div>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <i class="icon icon-speedometer mt-1 text-2xl text-gray-700"></i>
                <div>
                  <div class="text-sm text-gray-500">Fuel</div>
                  <div class="font-medium"><?= ucfirst(h($car['fuel_type'])) ?></div>
                </div>
              </div>
            </div>
          </div>

          <div class="my-10 border-t border-gray-200"></div>

          <!-- Description -->
          <?php if ($car['description']): ?>
          <div>
            <h3 class="text-2xl font-semibold">Overview</h3>
            <p class="mt-4 leading-relaxed text-gray-700"><?= nl2br(h($car['description'])) ?></p>
          </div>
          <div class="my-10 border-t border-gray-200"></div>
          <?php endif; ?>

          <!-- Specifications -->
          <div>
            <h3 class="text-2xl font-semibold">Specifications</h3>
            <div class="mt-4 grid grid-cols-1 gap-6 text-sm sm:grid-cols-3">
              <div><div class="text-gray-500">Make</div><div class="font-medium"><?= h($car['make']) ?></div></div>
              <div><div class="text-gray-500">Model</div><div class="font-medium"><?= h($car['model']) ?></div></div>
              <div><div class="text-gray-500">Year</div><div class="font-medium"><?= (int)$car['year'] ?></div></div>
              <div><div class="text-gray-500">Color</div><div class="font-medium"><?= h($car['color']) ?></div></div>
              <div><div class="text-gray-500">Transmission</div><div class="font-medium"><?= ucfirst(h($car['transmission'])) ?></div></div>
              <div><div class="text-gray-500">Fuel Type</div><div class="font-medium"><?= ucfirst(h($car['fuel_type'])) ?></div></div>
              <?php if ($car['engine_cc']): ?>
              <div><div class="text-gray-500">Engine</div><div class="font-medium"><?= (int)$car['engine_cc'] ?> cc</div></div>
              <?php endif; ?>
              <div><div class="text-gray-500">Passengers</div><div class="font-medium"><?= (int)$car['passenger_capacity'] ?></div></div>
              <div><div class="text-gray-500">Luggage</div><div class="font-medium"><?= (int)$car['luggage_capacity'] ?> bags</div></div>
            </div>
          </div>

          <div class="my-10 border-t border-gray-200"></div>

          <!-- Amenities -->
          <div>
            <h3 class="text-2xl font-semibold">Amenities</h3>
            <div class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
              <?php if ($car['has_ac']): ?>
              <div class="flex items-center gap-3">
                <i class="icon icon-check text-green-500"></i><div>Air Conditioning</div>
              </div>
              <?php endif; ?>
              <?php if ($car['has_wifi']): ?>
              <div class="flex items-center gap-3">
                <i class="icon icon-check text-green-500"></i><div>Wi-Fi</div>
              </div>
              <?php endif; ?>
              <?php if ($car['has_gps']): ?>
              <div class="flex items-center gap-3">
                <i class="icon icon-check text-green-500"></i><div>GPS Navigation</div>
              </div>
              <?php endif; ?>
              <?php if ($car['has_child_seat']): ?>
              <div class="flex items-center gap-3">
                <i class="icon icon-check text-green-500"></i><div>Child Seat</div>
              </div>
              <?php endif; ?>
              <?php foreach ($car_features as $feat): ?>
              <div class="flex items-center gap-3">
                <i class="icon icon-check text-green-500"></i>
                <div><?= h($feat['feature']) ?><?= $feat['value'] ? ': ' . h($feat['value']) : '' ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="my-10 border-t border-gray-200"></div>

          <!-- Requirements -->
          <div>
            <h3 class="text-2xl font-semibold">Rental Requirements</h3>
            <ul class="mt-4 space-y-3 text-sm text-gray-700">
              <li class="flex items-start gap-2"><i class="icon-check text-green-500 mt-0.5"></i> Original National ID or Valid Passport</li>
              <li class="flex items-start gap-2"><i class="icon-check text-green-500 mt-0.5"></i> Valid Driving License (min. 2 years experience)</li>
              <li class="flex items-start gap-2"><i class="icon-check text-green-500 mt-0.5"></i> Entry Visa — for foreign nationals</li>
              <li class="flex items-start gap-2"><i class="icon-check text-green-500 mt-0.5"></i>
                Security Deposit: KES <?= number_format((float)$deposit_min) ?> – KES <?= number_format((float)$deposit_max) ?>
              </li>
            </ul>
          </div>

        </div><!-- /.lg:col-span-8 -->

        <!-- ===== Right Column – Booking Sidebar ===== -->
        <div class="lg:col-span-4">
          <div class="flex justify-end">
            <div class="border-border sticky top-4 w-full rounded border bg-white p-7.5 lg:w-[360px]">

              <!-- Price -->
              <div class="mb-5 flex items-center justify-between">
                <div class="text-light-1 text-sm">
                  From <span class="text-dark-1 ml-1 text-xl font-medium"><?= format_kes($car['price_per_day']) ?></span> / day
                </div>
                <div class="bg-yellow-1 flex h-10 w-10 items-center justify-center rounded">
                  <div class="text-dark-1 text-sm font-semibold">4.9</div>
                </div>
              </div>

              <?php if ($car['status'] === 'available'): ?>
              <!-- Booking Form -->
              <form action="/booking.php" method="GET" class="space-y-5 pt-5">
                <input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">

                <!-- Service type -->
                <div class="border-border w-full rounded border px-5 py-2.5">
                  <p class="text-15 text-dark-1 mb-1 block font-medium">Service</p>
                  <select name="service" class="outline-none text-light-1 w-full bg-transparent">
                    <?php foreach ($services as $svc): ?>
                    <option value="<?= h($svc['slug']) ?>"><?= h($svc['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- Pickup location -->
                <div class="border-border w-full rounded border px-5 py-2.5">
                  <p class="text-15 text-dark-1 mb-1 block font-medium">Pick up location</p>
                  <input type="text" name="pickup_location" placeholder="City or Airport"
                    class="outline-none text-light-1 w-full" required />
                </div>

                <!-- Drop off location -->
                <div class="border-border w-full rounded border px-5 py-2.5">
                  <p class="text-15 text-dark-1 mb-1 block font-medium">Drop off location</p>
                  <input type="text" name="dropoff_location" placeholder="City or Airport (optional)"
                    class="outline-none text-light-1 w-full" />
                </div>

                <!-- Pickup date -->
                <div class="border-border w-full rounded border px-5 py-2.5">
                  <p class="text-15 text-dark-1 mb-1 block font-medium">Pick up date</p>
                  <input type="date" name="pickup_date" min="<?= date('Y-m-d') ?>"
                    class="outline-none text-light-1 w-full" required />
                </div>

                <!-- Return date -->
                <div class="border-border w-full rounded border px-5 py-2.5">
                  <p class="text-15 text-dark-1 mb-1 block font-medium">Return date</p>
                  <input type="date" name="return_date"
                    class="outline-none text-light-1 w-full" />
                </div>

                <!-- Submit -->
                <button type="submit"
                  class="bg-yellow-1 text-15 hover:bg-blue-1 text-dark-1 flex h-15 w-full cursor-pointer items-center justify-center gap-2.5 rounded px-9 duration-300 hover:text-white lg:inline-flex">
                  Book Now
                </button>
              </form>

              <!-- Airport Transfer pricing -->
              <?php if ($car['price_airport']): ?>
              <div class="mt-5 rounded bg-blue-1/5 p-4 text-sm">
                <div class="text-dark-1 font-medium">Airport Transfer</div>
                <div class="text-light-1 mt-1">From <?= format_kes($car['price_airport']) ?></div>
              </div>
              <?php endif; ?>
              <?php if ($car['price_wedding']): ?>
              <div class="mt-3 rounded bg-yellow-1/10 p-4 text-sm">
                <div class="text-dark-1 font-medium">Wedding Package</div>
                <div class="text-light-1 mt-1">From <?= format_kes($car['price_wedding']) ?></div>
              </div>
              <?php endif; ?>

              <?php else: ?>
              <div class="rounded bg-red-50 p-5 text-center text-sm text-red-700">
                This vehicle is currently not available for booking.
                <a href="/cars.php" class="mt-2 block font-medium text-blue-1 hover:underline">Browse other cars</a>
              </div>
              <?php endif; ?>

            </div>
          </div>
        </div><!-- /.lg:col-span-4 -->

      </div><!-- /.grid -->
    </div><!-- /.container -->
  </section>

  <!-- Similar Cars -->
  <section class="pb-20 pt-10 md:pb-30 md:pt-20">
    <div class="container">
      <div class="mb-10">
        <h3 class="text-dark-1 text-2xl font-semibold">Similar Cars</h3>
      </div>
      <?php
      $similar = $db->prepare('
          SELECT c.id, c.make, c.model, c.year, c.transmission, c.passenger_capacity,
                 c.luggage_capacity, c.price_per_day, c.thumbnail_path,
                 cat.name AS category_name
          FROM cars c
          JOIN car_categories cat ON c.category_id = cat.id
          WHERE c.id != ? AND c.category_id = ? AND c.status = "available"
          ORDER BY c.sort_order, c.id DESC LIMIT 4
      ');
      $similar->execute([$car_id, $car['category_id']]);
      $similar_cars = $similar->fetchAll();
      ?>
      <div class="grid grid-cols-1 gap-7.5 md:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($similar_cars as $sc):
          $sc_thumb = $sc['thumbnail_path']
            ? UPLOAD_URL . ltrim($sc['thumbnail_path'], '/')
            : '/assets/images/cars/1.png';
        ?>
        <div class="group">
          <a href="/car.php?id=<?= (int)$sc['id'] ?>" class="block">
            <div class="relative mb-4 aspect-30/25 overflow-hidden rounded border border-gray-200">
              <img src="<?= h($sc_thumb) ?>" alt="<?= h($sc['make'] . ' ' . $sc['model']) ?>"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                onerror="this.src='/assets/images/cars/1.png'">
            </div>
            <div>
              <div class="text-light-1 mb-2 flex items-center text-sm">
                <span>Nairobi, Kenya</span>
                <span class="bg-light-1 mx-2 h-1 w-1 rounded-full"></span>
                <span class="uppercase"><?= h($sc['category_name']) ?></span>
              </div>
              <h4 class="text-dark-1 mb-2 text-lg leading-tight font-medium">
                <?= h($sc['make'] . ' ' . $sc['model']) ?>
                <span class="text-light-1 text-sm font-normal"><?= (int)$sc['year'] ?></span>
              </h4>
              <div class="flex flex-wrap items-center gap-x-5 gap-y-2 pt-1">
                <div class="text-dark-1 flex items-center text-sm">
                  <span class="icon-user-2 mr-2"></span><span><?= (int)$sc['passenger_capacity'] ?></span>
                </div>
                <div class="text-dark-1 flex items-center text-sm">
                  <span class="icon-transmission mr-2"></span><span><?= ucfirst(h($sc['transmission'])) ?></span>
                </div>
              </div>
              <div class="text-light-1 mt-4">
                From <span class="text-dark-1 font-medium"><?= format_kes($sc['price_per_day']) ?></span> / day
              </div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
        <?php if (empty($similar_cars)): ?>
        <div class="col-span-4 text-center py-10 text-light-1">
          <a href="/cars.php?category=<?= h($car['category_slug']) ?>" class="text-blue-1 hover:underline">
            Browse all <?= h($car['category_name']) ?> cars &rarr;
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
