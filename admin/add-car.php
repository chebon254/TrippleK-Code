<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/image_functions.php';

require_admin_auth();

$page_title = 'Add New Car';
$db         = get_db();
$errors     = [];

// Load categories and services for dropdowns
$categories = $db->query('SELECT id, name FROM car_categories ORDER BY sort_order')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        // Validate required fields
        $fields = [
            'category_id'         => 'Category',
            'make'                => 'Make',
            'model'               => 'Model',
            'year'                => 'Year',
            'registration_number' => 'Registration Number',
            'price_per_day'       => 'Price Per Day',
        ];
        foreach ($fields as $field => $label) {
            if (empty(trim($_POST[$field] ?? ''))) {
                $errors[] = "{$label} is required.";
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $db->prepare('
                    INSERT INTO cars
                      (category_id, make, model, year, registration_number, color,
                       transmission, fuel_type, passenger_capacity, luggage_capacity,
                       engine_cc, has_ac, has_wifi, has_gps, has_child_seat,
                       price_per_day, price_airport, price_wedding,
                       status, description, sort_order, is_featured)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                ');
                $stmt->execute([
                    (int)$_POST['category_id'],
                    trim($_POST['make']),
                    trim($_POST['model']),
                    (int)$_POST['year'],
                    strtoupper(trim($_POST['registration_number'])),
                    trim($_POST['color'] ?? 'White'),
                    $_POST['transmission'] ?? 'automatic',
                    $_POST['fuel_type']    ?? 'petrol',
                    (int)($_POST['passenger_capacity'] ?? 5),
                    (int)($_POST['luggage_capacity']   ?? 2),
                    !empty($_POST['engine_cc']) ? (int)$_POST['engine_cc'] : null,
                    isset($_POST['has_ac'])         ? 1 : 0,
                    isset($_POST['has_wifi'])        ? 1 : 0,
                    isset($_POST['has_gps'])         ? 1 : 0,
                    isset($_POST['has_child_seat'])   ? 1 : 0,
                    (float)$_POST['price_per_day'],
                    !empty($_POST['price_airport']) ? (float)$_POST['price_airport'] : null,
                    !empty($_POST['price_wedding'])  ? (float)$_POST['price_wedding']  : null,
                    $_POST['status'] ?? 'available',
                    trim($_POST['description'] ?? ''),
                    (int)($_POST['sort_order'] ?? 0),
                    isset($_POST['is_featured']) ? 1 : 0,
                ]);
                $car_id = (int)$db->lastInsertId();

                // Handle image uploads
                if (!empty($_FILES['car_images']['name'][0])) {
                    $is_primary = true;
                    $sort       = 0;
                    foreach ($_FILES['car_images']['name'] as $i => $name) {
                        if ($_FILES['car_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                        $file = [
                            'name'     => $_FILES['car_images']['name'][$i],
                            'tmp_name' => $_FILES['car_images']['tmp_name'][$i],
                            'size'     => $_FILES['car_images']['size'][$i],
                            'error'    => $_FILES['car_images']['error'][$i],
                        ];
                        try {
                            $paths = upload_car_image($car_id, $file);
                            $imgStmt = $db->prepare('
                                INSERT INTO car_images (car_id, file_path, sort_order, is_primary)
                                VALUES (?, ?, ?, ?)
                            ');
                            $imgStmt->execute([$car_id, $paths['path'], $sort++, $is_primary ? 1 : 0]);
                            if ($is_primary) {
                                $db->prepare('UPDATE cars SET thumbnail_path = ? WHERE id = ?')
                                   ->execute([$paths['path'], $car_id]);
                                $is_primary = false;
                            }
                        } catch (RuntimeException $e) {
                            $errors[] = "Image upload error: " . $e->getMessage();
                        }
                    }
                }

                // Save features
                if (!empty($_POST['features'])) {
                    $featStmt = $db->prepare('INSERT INTO car_features (car_id, feature, value) VALUES (?,?,?)');
                    foreach ($_POST['features'] as $feat) {
                        $feat = trim($feat);
                        if ($feat !== '') $featStmt->execute([$car_id, $feat, '']);
                    }
                }

                flash('success', "Car {$_POST['make']} {$_POST['model']} added successfully.");
                redirect('/admin/cars.php');

            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors[] = 'Registration number already exists.';
                } else {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

require __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-white mb-2 text-3xl font-semibold">Add New Car</h1>
    <p class="text-light-1">Add a vehicle to the Tripple K fleet</p>
  </div>
  <a href="/admin/cars.php" class="rounded border border-gray-200 px-4 py-2 text-sm text-light-1 hover:bg-dark-4">&larr; Back to Fleet</a>
</div>

<?php if ($errors): ?>
<div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
  <ul class="list-inside list-disc text-sm text-red-700 space-y-1">
    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="POST" action="/admin/add-car.php" enctype="multipart/form-data" x-data="{ features: [''] }">
  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

  <div class="grid gap-6 lg:grid-cols-3">

    <!-- Main Form -->
    <div class="lg:col-span-2 space-y-6">

      <!-- Basic Info -->
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Vehicle Information</h2>
        <div class="grid gap-4 sm:grid-cols-2">

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Category *</label>
            <select name="category_id" required
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
              <option value="">Select category...</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                <?= h($cat['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Status</label>
            <select name="status" class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
              <?php foreach (['available'=>'Available','hired'=>'Hired','maintenance'=>'Maintenance','inactive'=>'Inactive'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($_POST['status'] ?? 'available') === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Make *</label>
            <input type="text" name="make" required placeholder="e.g. Toyota"
              value="<?= h($_POST['make'] ?? '') ?>"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Model *</label>
            <input type="text" name="model" required placeholder="e.g. Land Cruiser V8"
              value="<?= h($_POST['model'] ?? '') ?>"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Year *</label>
            <input type="number" name="year" required min="2000" max="<?= date('Y') + 1 ?>"
              value="<?= h($_POST['year'] ?? date('Y')) ?>"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Registration Number *</label>
            <input type="text" name="registration_number" required placeholder="e.g. KDA 123X"
              value="<?= h($_POST['registration_number'] ?? '') ?>"
              class="border-border focus:border-dark-1 w-full rounded border px-3 py-2.5 text-sm outline-none uppercase"
              style="text-transform:uppercase">
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Color</label>
            <input type="text" name="color" placeholder="e.g. Pearl White"
              value="<?= h($_POST['color'] ?? 'White') ?>"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Engine CC</label>
            <input type="number" name="engine_cc" placeholder="e.g. 2500"
              value="<?= h($_POST['engine_cc'] ?? '') ?>"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Transmission</label>
            <select name="transmission" class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
              <option value="automatic" <?= ($_POST['transmission'] ?? 'automatic') === 'automatic' ? 'selected' : '' ?>>Automatic</option>
              <option value="manual"    <?= ($_POST['transmission'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Fuel Type</label>
            <select name="fuel_type" class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
              <?php foreach (['petrol'=>'Petrol','diesel'=>'Diesel','hybrid'=>'Hybrid','electric'=>'Electric'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($_POST['fuel_type'] ?? 'petrol') === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Passenger Capacity</label>
            <input type="number" name="passenger_capacity" min="1" max="50"
              value="<?= h($_POST['passenger_capacity'] ?? '5') ?>"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Luggage / Bags</label>
            <input type="number" name="luggage_capacity" min="0" max="20"
              value="<?= h($_POST['luggage_capacity'] ?? '2') ?>"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
          </div>

        </div>

        <!-- Description -->
        <div class="mt-4">
          <label class="mb-1.5 block text-sm font-medium text-white">Description</label>
          <textarea name="description" rows="4" placeholder="Describe the vehicle, ideal use cases, any special notes..."
            class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none resize-none"><?= h($_POST['description'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- Amenities -->
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Amenities</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <?php
          $amenities = [
            'has_ac'        => 'Air Conditioning',
            'has_wifi'      => 'Wi-Fi / Hotspot',
            'has_gps'       => 'GPS Navigation',
            'has_child_seat'=> 'Child Seat',
          ];
          foreach ($amenities as $name => $label):
          ?>
          <label class="flex cursor-pointer items-center gap-2 rounded border border-border px-3 py-2.5 hover:bg-dark-4">
            <input type="checkbox" name="<?= $name ?>" <?= isset($_POST[$name]) ? 'checked' : ($name === 'has_ac' ? 'checked' : '') ?>>
            <span class="text-sm"><?= $label ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Extra Features -->
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Additional Features</h2>
        <p class="mb-3 text-xs text-gray-400">Add custom feature tags (e.g. "Sunroof", "Leather Seats")</p>
        <template x-for="(feat, i) in features" :key="i">
          <div class="mb-2 flex items-center gap-2">
            <input type="text" :name="`features[]`" x-model="features[i]"
              placeholder="e.g. Sunroof"
              class="border-border focus:border-dark-1 flex-1 rounded border px-3 py-2 text-sm outline-none">
            <button type="button" @click="features.splice(i,1)" x-show="features.length > 1"
              class="text-red-400 hover:text-red-600 px-2">
              <i class="icon-close text-base"></i>
            </button>
          </div>
        </template>
        <button type="button" @click="features.push('')"
          class="mt-1 text-sm text-blue-1 hover:underline">+ Add Feature</button>
      </div>

      <!-- Images -->
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Photos</h2>
        <p class="mb-3 text-xs text-gray-400">Upload up to 10 images. First image becomes the thumbnail. JPG/PNG/WebP, max 5MB each.</p>
        <input type="file" name="car_images[]" multiple accept="image/jpeg,image/png,image/webp"
          class="block w-full text-sm text-gray-500 file:mr-4 file:rounded file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-1 hover:file:bg-blue-100">
      </div>

    </div>

    <!-- Sidebar: Pricing + Options -->
    <div class="space-y-6">

      <!-- Pricing -->
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Pricing (KES)</h2>
        <div class="space-y-4">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Price Per Day *</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">KES</span>
              <input type="number" name="price_per_day" required min="0" step="100"
                value="<?= h($_POST['price_per_day'] ?? '') ?>"
                class="border-border focus:border-dark-1 w-full rounded border py-2.5 pr-3 pl-12 text-sm outline-none">
            </div>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Airport Transfer Rate</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">KES</span>
              <input type="number" name="price_airport" min="0" step="100"
                value="<?= h($_POST['price_airport'] ?? '') ?>"
                class="border-border focus:border-dark-1 w-full rounded border py-2.5 pr-3 pl-12 text-sm outline-none"
                placeholder="Optional">
            </div>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Wedding Package Rate</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">KES</span>
              <input type="number" name="price_wedding" min="0" step="100"
                value="<?= h($_POST['price_wedding'] ?? '') ?>"
                class="border-border focus:border-dark-1 w-full rounded border py-2.5 pr-3 pl-12 text-sm outline-none"
                placeholder="Optional">
            </div>
          </div>
        </div>
      </div>

      <!-- Options -->
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Display Options</h2>
        <label class="flex cursor-pointer items-center gap-3">
          <input type="checkbox" name="is_featured" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
          <div>
            <div class="text-sm font-medium">Feature on Homepage</div>
            <div class="text-xs text-gray-400">Show in homepage carousel</div>
          </div>
        </label>
        <div class="mt-4">
          <label class="mb-1.5 block text-sm font-medium text-white">Sort Order</label>
          <input type="number" name="sort_order" min="0"
            value="<?= h($_POST['sort_order'] ?? '0') ?>"
            class="border-border focus:border-dark-1 w-full rounded border px-3 py-2 text-sm outline-none">
          <p class="mt-1 text-xs text-gray-400">Lower = shows first</p>
        </div>
      </div>

      <!-- Submit -->
      <div class="rounded bg-dark-3 border border-border p-6">
        <button type="submit"
          class="bg-blue-1 hover:bg-dark-1 w-full rounded px-6 py-3 text-sm font-medium text-white transition-colors">
          Add Car to Fleet
        </button>
        <a href="/admin/cars.php"
          class="mt-3 block w-full rounded border border-gray-200 px-6 py-3 text-center text-sm text-light-1 hover:bg-dark-4">
          Cancel
        </a>
      </div>

    </div>
  </div>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
