<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/image_functions.php';

require_admin_auth();

$db     = get_db();
$car_id = (int)($_GET['id'] ?? 0);

if (!$car_id) {
    flash('error', 'Invalid car ID.');
    redirect('/admin/cars');
}

$stmt = $db->prepare('SELECT * FROM cars WHERE id = ?');
$stmt->execute([$car_id]);
$car = $stmt->fetch();
if (!$car) {
    flash('error', 'Car not found.');
    redirect('/admin/cars');
}

// Load images and features
$images   = $db->prepare('SELECT * FROM car_images WHERE car_id = ? ORDER BY sort_order')->execute([$car_id]) ? [] : [];
$img_stmt = $db->prepare('SELECT * FROM car_images WHERE car_id = ? ORDER BY sort_order');
$img_stmt->execute([$car_id]);
$images = $img_stmt->fetchAll();

$feat_stmt = $db->prepare('SELECT feature FROM car_features WHERE car_id = ?');
$feat_stmt->execute([$car_id]);
$features = array_column($feat_stmt->fetchAll(), 'feature');
if (empty($features)) $features = [''];

$categories = $db->query('SELECT id, name FROM car_categories ORDER BY sort_order')->fetchAll();
$page_title  = 'Edit Car';
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        // Update car record
        $fields = ['category_id' => 'Category', 'make' => 'Make',
                   'year' => 'Year', 'price_per_day' => 'Price Per Day'];
        foreach ($fields as $field => $label) {
            if (empty(trim($_POST[$field] ?? ''))) $errors[] = "{$label} is required.";
        }

        if (empty($errors)) {
            try {
                $upd = $db->prepare('
                    UPDATE cars SET
                      category_id=?, make=?, model=?, year=?, registration_number=?, color=?,
                      transmission=?, fuel_type=?, passenger_capacity=?, luggage_capacity=?,
                      engine_cc=?, has_ac=?, has_wifi=?, has_gps=?, has_child_seat=?,
                      price_per_day=?, price_airport=?, price_wedding=?,
                      status=?, description=?, sort_order=?, is_featured=?
                    WHERE id=?
                ');
                $upd->execute([
                    (int)$_POST['category_id'],
                    trim($_POST['make']),
                    !empty(trim($_POST['model'] ?? '')) ? trim($_POST['model']) : null,
                    (int)$_POST['year'],
                    !empty(trim($_POST['registration_number'] ?? '')) ? strtoupper(trim($_POST['registration_number'])) : null,
                    trim($_POST['color'] ?? 'White'),
                    $_POST['transmission'] ?? 'automatic',
                    $_POST['fuel_type'] ?? 'petrol',
                    (int)($_POST['passenger_capacity'] ?? 5),
                    (int)($_POST['luggage_capacity'] ?? 2),
                    !empty($_POST['engine_cc']) ? (int)$_POST['engine_cc'] : null,
                    isset($_POST['has_ac']) ? 1 : 0,
                    isset($_POST['has_wifi']) ? 1 : 0,
                    isset($_POST['has_gps']) ? 1 : 0,
                    isset($_POST['has_child_seat']) ? 1 : 0,
                    (float)$_POST['price_per_day'],
                    !empty($_POST['price_airport']) ? (float)$_POST['price_airport'] : null,
                    !empty($_POST['price_wedding']) ? (float)$_POST['price_wedding'] : null,
                    $_POST['status'] ?? 'available',
                    trim($_POST['description'] ?? ''),
                    (int)($_POST['sort_order'] ?? 0),
                    isset($_POST['is_featured']) ? 1 : 0,
                    $car_id,
                ]);

                $imgStmt = $db->prepare('INSERT INTO car_images (car_id, file_path, sort_order, is_primary) VALUES (?,?,?,?)');
                $sort    = count($images);

                // Hero carousel image (cropped 1500×650) — becomes primary
                if (!empty($_POST['cropped_hero'])) {
                    try {
                        $path = save_base64_image($_POST['cropped_hero'], "cars/{$car_id}", 1500, 650);
                        $imgStmt->execute([$car_id, $path, 0, 1]);
                        $db->prepare('UPDATE car_images SET is_primary=0 WHERE car_id=? AND file_path!=?')->execute([$car_id, $path]);
                    } catch (RuntimeException $e) {
                        $errors[] = 'Carousel image error: ' . $e->getMessage();
                    }
                }

                // Gallery images (any dimensions)
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    foreach ($_FILES['gallery_images']['name'] as $i => $name) {
                        if ($_FILES['gallery_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                        $file = [
                            'name'     => $_FILES['gallery_images']['name'][$i],
                            'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                            'size'     => $_FILES['gallery_images']['size'][$i],
                            'error'    => $_FILES['gallery_images']['error'][$i],
                        ];
                        try {
                            $paths = upload_car_image($car_id, $file);
                            $imgStmt->execute([$car_id, $paths['path'], $sort++, 0]);
                        } catch (RuntimeException $e) {
                            $errors[] = 'Image error: ' . $e->getMessage();
                        }
                    }
                }

                // Delete selected images
                if (!empty($_POST['delete_images'])) {
                    foreach ($_POST['delete_images'] as $img_id) {
                        $del_stmt = $db->prepare('SELECT file_path FROM car_images WHERE id=? AND car_id=?');
                        $del_stmt->execute([(int)$img_id, $car_id]);
                        $img = $del_stmt->fetch();
                        if ($img) {
                            delete_single_image($img['file_path']);
                            $db->prepare('DELETE FROM car_images WHERE id=?')->execute([(int)$img_id]);
                        }
                    }
                }

                // Update thumbnail to first remaining image
                $first_img = $db->prepare('SELECT file_path FROM car_images WHERE car_id=? ORDER BY sort_order LIMIT 1');
                $first_img->execute([$car_id]);
                $thumb = $first_img->fetchColumn();
                $db->prepare('UPDATE cars SET thumbnail_path=? WHERE id=?')->execute([$thumb ?: null, $car_id]);

                // Update features
                $db->prepare('DELETE FROM car_features WHERE car_id=?')->execute([$car_id]);
                if (!empty($_POST['features'])) {
                    $feat_ins = $db->prepare('INSERT INTO car_features (car_id, feature) VALUES (?,?)');
                    foreach ($_POST['features'] as $feat) {
                        $feat = trim($feat);
                        if ($feat !== '') $feat_ins->execute([$car_id, $feat]);
                    }
                }

                flash('success', 'Car updated successfully.');
                redirect('/admin/cars');

            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors[] = 'Registration number already exists for another car.';
                } else {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
    // Reload for re-render
    $car = array_merge($car, $_POST);
    $features = !empty($_POST['features']) ? $_POST['features'] : [''];
}

require __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-white mb-2 text-3xl font-semibold">Edit Car</h1>
    <p class="text-light-1"><?= h($car['make'] . ' ' . $car['model'] . ' — ' . $car['registration_number']) ?></p>
  </div>
  <a href="/admin/cars" class="rounded border border-gray-200 px-4 py-2 text-sm text-light-1 hover:bg-dark-4">&larr; Back</a>
</div>

<?php if ($errors): ?>
<div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
  <ul class="list-inside list-disc text-sm text-red-700 space-y-1">
    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="POST" action="/admin/edit-car?id=<?= $car_id ?>" enctype="multipart/form-data"
  x-data="{ features: <?= json_encode(array_values($features)) ?> }">
  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

  <div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">

      <!-- Vehicle Info (same fields as add-car) -->
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Vehicle Information</h2>
        <div class="grid gap-4 sm:grid-cols-2">

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Category *</label>
            <select name="category_id" required class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $car['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Status</label>
            <select name="status" class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
              <?php foreach (['available'=>'Available','hired'=>'Hired','maintenance'=>'Maintenance','inactive'=>'Inactive'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $car['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php
          $text_fields = [
            ['make','Make *','text','e.g. Toyota'],
            ['model','Model','text','e.g. Land Cruiser V8'],
            ['year','Year *','number',''],
            ['registration_number','Registration Number','text','e.g. KDA 123X'],
            ['color','Color','text','e.g. Pearl White'],
            ['engine_cc','Engine CC','number','e.g. 2500'],
            ['passenger_capacity','Passenger Capacity','number',''],
            ['luggage_capacity','Luggage / Bags','number',''],
          ];
          foreach ($text_fields as [$name, $label, $type, $placeholder]):
          ?>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white"><?= $label ?></label>
            <input type="<?= $type ?>" name="<?= $name ?>"
              value="<?= h((string)($car[$name] ?? '')) ?>"
              placeholder="<?= h($placeholder) ?>"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none"
              <?= str_contains($label, '*') ? 'required' : '' ?>>
          </div>
          <?php endforeach; ?>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Transmission</label>
            <select name="transmission" class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
              <option value="automatic" <?= $car['transmission']==='automatic'?'selected':'' ?>>Automatic</option>
              <option value="manual"    <?= $car['transmission']==='manual'?'selected':'' ?>>Manual</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Fuel Type</label>
            <select name="fuel_type" class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none">
              <?php foreach (['petrol'=>'Petrol','diesel'=>'Diesel','hybrid'=>'Hybrid','electric'=>'Electric'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $car['fuel_type']===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>

        </div>

        <div class="mt-4">
          <label class="mb-1.5 block text-sm font-medium text-white">Description</label>
          <textarea name="description" rows="4" class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none resize-none"><?= h($car['description'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- Amenities -->
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Amenities</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <?php foreach (['has_ac'=>'AC','has_wifi'=>'Wi-Fi','has_gps'=>'GPS','has_child_seat'=>'Child Seat'] as $n=>$l): ?>
          <label class="flex cursor-pointer items-center gap-2 rounded border border-border px-3 py-2.5 hover:bg-dark-4">
            <input type="checkbox" name="<?= $n ?>" <?= $car[$n] ? 'checked' : '' ?>>
            <span class="text-sm"><?= $l ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Current Images -->
      <?php if (!empty($images)): ?>
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Current Photos</h2>
        <p class="mb-3 text-xs text-gray-400">Check images to delete them.</p>
        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
          <?php foreach ($images as $img): ?>
          <div class="relative rounded overflow-hidden">
            <img src="<?= h(UPLOAD_URL . $img['file_path']) ?>" alt="" class="h-24 w-full object-cover">
            <label class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 hover:opacity-100 transition-opacity cursor-pointer">
              <input type="checkbox" name="delete_images[]" value="<?= $img['id'] ?>" class="h-4 w-4">
              <span class="ml-1 text-xs text-white">Delete</span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Add More Photos -->
      <script>window._carFeatured = <?= $car['is_featured'] ? 'true' : 'false' ?>;</script>
      <?php include __DIR__ . '/../includes/car_image_uploader.php'; ?>

      <!-- Features -->
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Additional Features</h2>
        <template x-for="(feat, i) in features" :key="i">
          <div class="mb-2 flex items-center gap-2">
            <input type="text" name="features[]" x-model="features[i]" placeholder="e.g. Sunroof"
              class="border-border focus:border-dark-1 flex-1 rounded border px-3 py-2 text-sm outline-none">
            <button type="button" @click="features.splice(i,1)" x-show="features.length > 1"
              class="text-red-400 hover:text-red-600 px-2"><i class="icon-close text-base"></i></button>
          </div>
        </template>
        <button type="button" @click="features.push('')" class="mt-1 text-sm text-blue-1 hover:underline">+ Add Feature</button>
      </div>

    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Pricing (USD)</h2>
        <div class="space-y-4">
          <?php foreach (['price_per_day'=>'Price Per Day *','price_airport'=>'Airport Transfer','price_wedding'=>'Wedding Package'] as $n=>$l): ?>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white"><?= $l ?></label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">USD</span>
              <input type="number" name="<?= $n ?>" min="0" step="1"
                value="<?= h((string)($car[$n] ?? '')) ?>"
                class="border-border focus:border-dark-1 w-full rounded border py-2.5 pr-3 pl-12 text-sm outline-none"
                <?= str_contains($l,'*')?'required':'' ?>>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="rounded bg-dark-3 border border-border p-6">
        <h2 class="mb-4 text-base font-medium text-white">Options</h2>
        <label class="flex cursor-pointer items-center gap-3 mb-4">
          <input type="checkbox" name="is_featured" <?= $car['is_featured'] ? 'checked' : '' ?>
            @change="$dispatch('featured-toggle', { checked: $event.target.checked })">
          <div>
            <div class="text-sm font-medium">Feature on Homepage</div>
            <div class="text-xs text-gray-400">Show in homepage carousel</div>
          </div>
        </label>
        <label class="mb-1.5 block text-sm font-medium text-white">Sort Order</label>
        <input type="number" name="sort_order" min="0" value="<?= h((string)$car['sort_order']) ?>"
          class="border-border focus:border-dark-1 w-full rounded border px-3 py-2 text-sm outline-none">
      </div>

      <div class="rounded bg-dark-3 border border-border p-6 space-y-3">
        <button type="submit"
          class="bg-blue-1 hover:bg-dark-1 w-full rounded px-6 py-3 text-sm font-medium text-white transition-colors">
          Save Changes
        </button>
        <a href="/admin/cars"
          class="block w-full rounded border border-gray-200 px-6 py-3 text-center text-sm text-light-1 hover:bg-dark-4">
          Cancel
        </a>
      </div>
    </div>

  </div>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
