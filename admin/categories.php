<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/image_functions.php';

require_admin_auth();

$page_title = 'Categories';
$db         = get_db();
$errors     = [];

// ─── POST handlers ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request.');
        redirect('/admin/categories');
    }

    $action = $_POST['action'] ?? '';

    // Add new category
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);

        if (!$name) {
            flash('error', 'Category name is required.');
            redirect('/admin/categories');
        }
        if (!$slug) $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));

        // Save image from Cropper.js base64
        $image_path = null;
        if (!empty($_POST['cropped_image'])) {
            try {
                $image_path = save_base64_image($_POST['cropped_image'], 'categories', 451, 375);
            } catch (RuntimeException $e) {
                flash('error', 'Image error: ' . $e->getMessage());
                redirect('/admin/categories');
            }
        }

        try {
            $db->prepare('INSERT INTO car_categories (slug, name, description, sort_order, image_path) VALUES (?,?,?,?,?)')
               ->execute([$slug, $name, $desc, $sort, $image_path]);
            flash('success', "Category \"{$name}\" added.");
        } catch (Throwable $e) {
            flash('error', 'Failed to add category: ' . $e->getMessage());
        }
        redirect('/admin/categories');
    }

    // Update category
    if ($action === 'update') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);

        if (!$id || !$name) {
            flash('error', 'Missing required fields.');
            redirect('/admin/categories');
        }

        // New image?
        $new_image = null;
        if (!empty($_POST['cropped_image'])) {
            try {
                $new_image = save_base64_image($_POST['cropped_image'], 'categories', 451, 375);
                // Delete old image if any
                $old = $db->prepare('SELECT image_path FROM car_categories WHERE id=?');
                $old->execute([$id]);
                $old_path = $old->fetchColumn();
                if ($old_path) {
                    $full = UPLOAD_DIR . $old_path;
                    if (is_file($full)) unlink($full);
                }
            } catch (RuntimeException $e) {
                flash('error', 'Image error: ' . $e->getMessage());
                redirect('/admin/categories');
            }
        }

        try {
            if ($new_image) {
                $db->prepare('UPDATE car_categories SET slug=?, name=?, description=?, sort_order=?, image_path=? WHERE id=?')
                   ->execute([$slug, $name, $desc, $sort, $new_image, $id]);
            } else {
                $db->prepare('UPDATE car_categories SET slug=?, name=?, description=?, sort_order=? WHERE id=?')
                   ->execute([$slug, $name, $desc, $sort, $id]);
            }
            flash('success', 'Category updated.');
        } catch (Throwable $e) {
            flash('error', 'Failed to update: ' . $e->getMessage());
        }
        redirect('/admin/categories');
    }

    // Delete category
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Remove image file
            $row = $db->prepare('SELECT image_path FROM car_categories WHERE id=?');
            $row->execute([$id]);
            $img = $row->fetchColumn();
            if ($img && is_file(UPLOAD_DIR . $img)) unlink(UPLOAD_DIR . $img);

            $db->prepare('DELETE FROM car_categories WHERE id=?')->execute([$id]);
            flash('success', 'Category deleted.');
        }
        redirect('/admin/categories');
    }
}

// ─── Load all categories ──────────────────────────────────────────────────────
try {
    $cats = $db->query("
        SELECT cc.*, COUNT(c.id) AS car_count
        FROM car_categories cc
        LEFT JOIN cars c ON c.category_id = cc.id AND c.status != 'inactive'
        GROUP BY cc.id
        ORDER BY cc.sort_order, cc.id
    ")->fetchAll();
} catch (Throwable) {
    $cats = $db->query('SELECT *, 0 AS car_count FROM car_categories ORDER BY sort_order, id')->fetchAll();
}

require __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-8 flex items-center justify-between">
  <div>
    <h1 class="text-white mb-1 text-3xl font-semibold">Categories</h1>
    <p class="text-light-1">Manage car categories shown on the homepage.</p>
  </div>
  <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
    class="bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-5 py-2.5 text-sm font-semibold transition-colors">
    + Add Category
  </button>
</div>

<!-- Categories table -->
<div class="rounded bg-dark-3 border border-border overflow-hidden">
  <table class="w-full">
    <thead class="bg-dark-4">
      <tr>
        <th class="text-white px-4 py-3 text-left text-sm font-semibold">Image</th>
        <th class="text-white px-4 py-3 text-left text-sm font-semibold">Name / Slug</th>
        <th class="text-white px-4 py-3 text-left text-sm font-semibold hidden sm:table-cell">Cars</th>
        <th class="text-white px-4 py-3 text-left text-sm font-semibold hidden lg:table-cell">Sort</th>
        <th class="text-white px-4 py-3 text-right text-sm font-semibold">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-border">
      <?php if (empty($cats)): ?>
      <tr><td colspan="5" class="px-4 py-10 text-center text-light-1">No categories yet.</td></tr>
      <?php else: ?>
      <?php foreach ($cats as $cat): ?>
      <tr class="hover:bg-dark-4 transition-colors">
        <td class="px-4 py-3">
          <?php if (!empty($cat['image_path'])): ?>
          <img src="<?= h(UPLOAD_URL . $cat['image_path']) ?>" alt=""
            class="h-12 w-20 rounded object-cover border border-border">
          <?php else: ?>
          <div class="h-12 w-20 rounded bg-dark-4 border border-border flex items-center justify-center">
            <i class="icon-car text-light-1 text-xs"></i>
          </div>
          <?php endif; ?>
        </td>
        <td class="px-4 py-3">
          <div class="text-white text-sm font-medium"><?= h($cat['name']) ?></div>
          <div class="text-light-1 text-xs font-mono">/<?= h($cat['slug']) ?></div>
        </td>
        <td class="px-4 py-3 text-light-1 text-sm hidden sm:table-cell"><?= (int)$cat['car_count'] ?></td>
        <td class="px-4 py-3 text-light-1 text-sm hidden lg:table-cell"><?= (int)$cat['sort_order'] ?></td>
        <td class="px-4 py-3 text-right">
          <button onclick="openEdit(<?= h(json_encode($cat)) ?>)"
            class="text-blue-1 hover:text-yellow-3 text-sm font-medium mr-3 transition-colors">Edit</button>
          <form method="POST" action="/admin/categories" class="inline"
            onsubmit="return confirm('Delete this category?')">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
            <button type="submit" class="text-red-400 hover:text-red-300 text-sm font-medium transition-colors">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- ===== Add Modal ===== -->
<div id="modal-add" class="hidden fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4 overflow-y-auto">
  <div class="w-full max-w-lg rounded bg-dark-2 border border-border shadow-2xl mt-10 mb-10">
    <div class="flex items-center justify-between px-6 py-4 border-b border-border">
      <h2 class="text-white font-semibold">Add Category</h2>
      <button onclick="document.getElementById('modal-add').classList.add('hidden')"
        class="text-light-1 hover:text-white"><i class="icon-close text-lg"></i></button>
    </div>
    <form method="POST" action="/admin/categories" x-data="categoryImageCropper()">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="add">
      <div class="p-6 space-y-4">

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Name *</label>
            <input type="text" name="name" required
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Slug</label>
            <input type="text" name="slug" placeholder="auto-generated"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white placeholder-light-1 px-3 py-2.5 text-sm outline-none font-mono">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 sm:col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-white">Description</label>
            <input type="text" name="description"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Sort Order</label>
            <input type="number" name="sort_order" value="0" min="0"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
          </div>
        </div>

        <!-- Category Image Cropper (451×375) -->
        <?php include __DIR__ . '/../includes/category_image_cropper.php'; ?>

      </div>
      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-border">
        <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')"
          class="rounded border border-border px-5 py-2 text-sm text-light-1 hover:text-white transition-colors">Cancel</button>
        <button type="submit"
          class="bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-6 py-2 text-sm font-semibold transition-colors">
          Add Category
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ===== Edit Modal ===== -->
<div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4 overflow-y-auto">
  <div class="w-full max-w-lg rounded bg-dark-2 border border-border shadow-2xl mt-10 mb-10">
    <div class="flex items-center justify-between px-6 py-4 border-b border-border">
      <h2 class="text-white font-semibold">Edit Category</h2>
      <button onclick="document.getElementById('modal-edit').classList.add('hidden')"
        class="text-light-1 hover:text-white"><i class="icon-close text-lg"></i></button>
    </div>
    <form method="POST" action="/admin/categories" x-data="categoryImageCropper()" id="edit-cat-form">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-cat-id">
      <div class="p-6 space-y-4">

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Name *</label>
            <input type="text" name="name" id="edit-cat-name" required
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Slug</label>
            <input type="text" name="slug" id="edit-cat-slug"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none font-mono">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 sm:col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-white">Description</label>
            <input type="text" name="description" id="edit-cat-desc"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-white">Sort Order</label>
            <input type="number" name="sort_order" id="edit-cat-sort" value="0" min="0"
              class="border-border focus:border-blue-1 w-full rounded border bg-dark-4 text-white px-3 py-2.5 text-sm outline-none">
          </div>
        </div>

        <!-- Current image preview -->
        <div id="edit-current-img" class="hidden">
          <label class="mb-1.5 block text-sm font-medium text-white">Current Image</label>
          <img id="edit-current-img-el" src="" alt="" class="h-20 rounded object-cover border border-border">
          <p class="text-xs text-light-1 mt-1">Select a new crop below to replace it.</p>
        </div>

        <!-- Category Image Cropper (451×375) -->
        <?php include __DIR__ . '/../includes/category_image_cropper.php'; ?>

      </div>
      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-border">
        <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')"
          class="rounded border border-border px-5 py-2 text-sm text-light-1 hover:text-white transition-colors">Cancel</button>
        <button type="submit"
          class="bg-blue-1 hover:bg-yellow-3 text-dark-1 rounded px-6 py-2 text-sm font-semibold transition-colors">
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(cat) {
  document.getElementById('edit-cat-id').value   = cat.id;
  document.getElementById('edit-cat-name').value = cat.name;
  document.getElementById('edit-cat-slug').value = cat.slug;
  document.getElementById('edit-cat-desc').value = cat.description || '';
  document.getElementById('edit-cat-sort').value = cat.sort_order || 0;
  const imgWrap = document.getElementById('edit-current-img');
  const imgEl   = document.getElementById('edit-current-img-el');
  if (cat.image_path) {
    imgEl.src = '<?= rtrim(UPLOAD_URL, '/') ?>/' + cat.image_path;
    imgWrap.classList.remove('hidden');
  } else {
    imgWrap.classList.add('hidden');
  }
  document.getElementById('modal-edit').classList.remove('hidden');
}
</script>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
