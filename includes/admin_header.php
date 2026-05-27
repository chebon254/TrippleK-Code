<?php
$site_name  = get_setting('company_name', APP_NAME);
$admin_page = basename($_SERVER['PHP_SELF'], '.php');
$flash      = get_flash();

// Fetch admin info
start_session();
$_admin_id    = $_SESSION['admin_id'] ?? 0;
$_admin_email = $_SESSION['admin_email'] ?? '';
$_admin_name  = 'Admin';
if ($_admin_id) {
    try {
        $s = get_db()->prepare('SELECT name FROM admin WHERE id = ? LIMIT 1');
        $s->execute([$_admin_id]);
        $_admin_name = $s->fetchColumn() ?: 'Admin';
    } catch (Throwable) {}
}

// Pending bookings count for notifications
$_pending_count = 0;
try {
    $_pending_count = (int)get_db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
} catch (Throwable) {}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0" />
  <title><?= h($page_title ?? 'Admin') ?> | <?= h($site_name) ?> Admin</title>
  <link rel="icon" href="/assets/images/logo/favicon.jpeg">
  <link href="/assets/bundle.css" rel="stylesheet">
  <link href="/assets/admin.css" rel="stylesheet">
  <style>
    [x-cloak]{display:none!important}
    @keyframes gtWrap{0%,100%{transform:scale(1)}50%{transform:scale(1.2)}}
    @keyframes gtIcon{0%{transform:translateY(150%) scale(1)}50%{transform:translateY(0%) scale(1.2) rotate(20deg)}100%{transform:translateY(-150%) rotate(-20deg)}}
    .animate-gt-wrap{animation:gtWrap 1.8s ease infinite}
    .animate-gt-icon{animation:gtIcon 1.8s ease infinite}

    .admin-sidebar{transform:translateX(-100%);transition:transform .3s ease}
    .admin-sidebar.is-open{transform:translateX(0)}
    @media(min-width:1024px){
      .admin-sidebar{transform:translateX(0)}
      .admin-sidebar.is-collapsed{transform:translateX(-100%)}
    }
    .admin-main{transition:padding-left .3s ease}
    @media(min-width:1024px){
      .admin-main{padding-left:18rem}
      .admin-main.is-collapsed{padding-left:0}
    }
  </style>
  <script>
    function dashboardPage(){
      return {
        // Desktop: sidebar open by default; Mobile: closed by default
        sidebarOpen: window.innerWidth >= 1024,
        init(){}
      }
    }
    function dropdown(){return{open:false,toggle(){this.open=!this.open},close(){this.open=false}}}
  </script>
</head>
<body class="bg-dark-1 text-white" x-data="dashboardPage()" x-init="init()">

<!-- ===== Admin Layout ===== -->

  <!-- ===== Header ===== -->
  <header class="fixed top-0 z-40 w-full bg-dark-2 border-b border-border"
    x-data="{ notificationsOpen: false, profileOpen: false }">
    <div class="px-6 py-4 sm:px-8">
      <div class="flex items-center justify-between">

        <!-- Left: Logo + Toggle + Search -->
        <div class="flex items-center gap-6">
          <a href="/admin/index.php" class="flex min-w-[140px] items-center">
            <img src="/assets/images/logo/tripplek-logo.jpeg" alt="<?= h($site_name) ?>" width="200" height="48" class="w-[200px] h-[48px] object-contain">
          </a>

          <!-- Sidebar toggle -->
          <button @click="sidebarOpen = !sidebarOpen"
            class="rounded-lg p-2 text-white transition-colors hover:bg-dark-3"
            aria-label="Toggle sidebar">
            <i class="icon-menu-2 text-xl"></i>
          </button>

          <!-- Search -->
          <div class="relative hidden md:block">
            <form method="get" action="/admin/bookings.php">
              <input type="search" name="search" placeholder="Search bookings..."
                class="border-border focus:border-blue-1 w-64 rounded border bg-dark-3 text-white placeholder-light-1 py-2.5 pr-4 pl-12 outline-none focus:border-2">
              <i class="icon-search text-light-1 absolute top-1/2 left-4 -translate-y-1/2 text-lg"></i>
            </form>
          </div>
        </div>

        <!-- Right: Notifications + View Site + Profile -->
        <div class="flex items-center gap-4">

          <!-- Notifications -->
          <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
              class="relative hidden h-12 w-12 items-center justify-center rounded-full bg-dark-3 border border-border transition-colors hover:bg-dark-4 lg:flex">
              <i class="icon-notification text-blue-1 text-xl"></i>
              <?php if ($_pending_count > 0): ?>
              <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500"></span>
              <?php endif; ?>
            </button>

            <div x-show="open" x-cloak @click.away="open = false"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100"
              class="absolute right-0 z-50 mt-2 w-80 rounded bg-dark-3 border border-border shadow-2xl">
              <div class="border-b border-border p-4">
                <h3 class="text-white font-semibold">Notifications</h3>
              </div>
              <div class="max-h-72 overflow-y-auto">
                <?php if ($_pending_count > 0): ?>
                <a href="/admin/bookings.php?status=pending"
                  class="flex cursor-pointer items-start gap-3 border-b border-border p-4 hover:bg-dark-4">
                  <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-1/20">
                    <i class="icon-clock text-blue-1"></i>
                  </div>
                  <div class="flex-1">
                    <p class="text-white text-sm font-medium">Pending Bookings</p>
                    <p class="text-light-1 mt-1 text-xs"><?= $_pending_count ?> booking<?= $_pending_count !== 1 ? 's' : '' ?> awaiting confirmation</p>
                  </div>
                </a>
                <?php else: ?>
                <div class="p-6 text-center text-sm text-light-1">No new notifications</div>
                <?php endif; ?>
              </div>
              <div class="border-t border-border p-3">
                <a href="/admin/bookings.php?status=pending"
                  class="text-blue-1 block w-full text-center text-sm font-medium hover:text-yellow-3">
                  View All Bookings
                </a>
              </div>
            </div>
          </div>

          <!-- View Site -->
          <a href="/index.php" target="_blank"
            class="hidden h-12 w-12 items-center justify-center rounded-full bg-dark-3 border border-border transition-colors hover:bg-dark-4 lg:flex"
            title="View Website">
            <i class="icon-arrow-top-right text-blue-1 text-xl"></i>
          </a>

          <!-- Profile Dropdown -->
          <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
              class="flex items-center gap-3 transition-opacity hover:opacity-80">
              <div class="flex h-12 w-12 items-center justify-center rounded-full bg-dark-3 border-2 border-blue-1/40 text-sm font-bold text-blue-1">
                <?= strtoupper(substr($_admin_name, 0, 2)) ?>
              </div>
              <div class="hidden text-left lg:block">
                <div class="text-white text-sm font-semibold"><?= h($_admin_name) ?></div>
                <div class="text-light-1 text-xs"><?= h($_admin_email) ?></div>
              </div>
              <i class="icon-chevron-sm-down text-light-1 hidden lg:block"></i>
            </button>

            <div x-show="open" x-cloak @click.away="open = false"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100"
              class="absolute right-0 z-50 mt-2 w-56 rounded bg-dark-3 border border-border shadow-2xl">
              <div class="border-b border-border p-4">
                <div class="text-white text-sm font-semibold"><?= h($_admin_name) ?></div>
                <div class="text-light-1 text-xs"><?= h($_admin_email) ?></div>
              </div>
              <div class="py-2">
                <a href="/admin/settings.php"
                  class="flex items-center gap-3 px-4 py-2 text-sm text-white hover:bg-dark-4">
                  <i class="icon-shield text-light-1"></i><span>Settings</span>
                </a>
                <a href="/admin/bookings.php"
                  class="flex items-center gap-3 px-4 py-2 text-sm text-white hover:bg-dark-4">
                  <i class="icon-calendar text-light-1"></i><span>Bookings</span>
                </a>
                <a href="/admin/cars.php"
                  class="flex items-center gap-3 px-4 py-2 text-sm text-white hover:bg-dark-4">
                  <i class="icon-car text-light-1"></i><span>Fleet</span>
                </a>
              </div>
              <div class="border-t border-border py-2">
                <a href="/admin/logout.php"
                  class="flex items-center gap-3 px-4 py-2 text-sm text-red-400 hover:bg-dark-4">
                  <i class="icon-arrow-right"></i><span>Logout</span>
                </a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </header>

<!-- ===== Flex wrapper ===== -->
<div class="flex pt-20">

  <!-- ===== Sidebar ===== -->
  <aside class="admin-sidebar fixed inset-y-0 left-0 z-30 mt-20 w-64 bg-dark-2 border-r border-border"
    :class="sidebarOpen ? 'is-open is-collapsed' : ''">
    <div class="h-full overflow-y-auto pt-6 pb-6">
      <nav class="space-y-1 px-4">

        <?php
        $nav_items = [
          ['href'=>'/admin/index.php',      'page'=>'index',      'icon'=>'/assets/images/dashboard/sidebar/compass.svg',  'label'=>'Dashboard'],
          ['href'=>'/admin/bookings.php',   'page'=>'bookings',   'icon'=>'/assets/images/dashboard/sidebar/booking.svg',  'label'=>'Bookings',
           'badge' => $_pending_count ?: 0],
          ['href'=>'/admin/cars.php',       'page'=>'cars',       'icon'=>'/assets/images/dashboard/sidebar/taxi.svg',     'label'=>'Fleet / Cars'],
          ['href'=>'/admin/categories.php', 'page'=>'categories', 'icon'=>'/assets/images/dashboard/sidebar/map.svg',      'label'=>'Categories'],
          ['href'=>'/admin/payments.php',   'page'=>'payments',   'icon'=>'/assets/images/dashboard/sidebar/bookmark.svg', 'label'=>'Payments'],
          ['href'=>'/admin/settings.php',   'page'=>'settings',   'icon'=>'/assets/images/dashboard/sidebar/gear.svg',     'label'=>'Settings'],
        ];
        foreach ($nav_items as $item):
          $active = ($admin_page === $item['page']);
        ?>
        <a href="<?= $item['href'] ?>"
          class="flex items-center gap-4 rounded px-4 py-3 font-medium transition-colors
            <?= $active ? 'bg-blue-1/10 text-blue-1 border border-blue-1/20' : 'text-white hover:bg-dark-3' ?>">
          <img src="<?= $item['icon'] ?>" alt="<?= $item['label'] ?>" class="h-5 w-5 opacity-80">
          <span><?= $item['label'] ?></span>
          <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
          <span class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-semibold text-white">
            <?= $item['badge'] ?>
          </span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>

        <div class="mt-4 border-t border-border pt-4">
          <a href="/index.php" target="_blank"
            class="flex items-center gap-4 rounded px-4 py-3 font-medium text-white transition-colors hover:bg-dark-3">
            <img src="/assets/images/dashboard/sidebar/map.svg" alt="View Site" class="h-5 w-5 opacity-80">
            <span>View Website</span>
          </a>
          <a href="/admin/logout.php"
            class="flex items-center gap-4 rounded px-4 py-3 font-medium text-red-400 transition-colors hover:bg-dark-3">
            <img src="/assets/images/dashboard/sidebar/log-out.svg" alt="Logout" class="h-5 w-5 opacity-80">
            <span>Logout</span>
          </a>
        </div>

      </nav>
    </div>
  </aside>

  <!-- Sidebar overlay (mobile) -->
  <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
    class="fixed inset-0 z-20 bg-black/50 lg:hidden"></div>

  <!-- ===== Main Content ===== -->
  <main class="bg-dark-1 flex min-h-screen flex-1 flex-col admin-main"
    :class="sidebarOpen ? 'is-collapsed' : ''">
    <div class="p-6 lg:p-8">

      <!-- Flash Message -->
      <?php if ($flash): ?>
      <div class="mb-6 flex items-center gap-3 rounded px-4 py-3 text-sm
        <?= $flash['type'] === 'success'
          ? 'bg-green-500/10 text-green-400 border border-green-500/30'
          : 'bg-red-500/10 text-red-400 border border-red-500/30' ?>">
        <i class="<?= $flash['type'] === 'success' ? 'icon-check' : 'icon-x' ?> text-base"></i>
        <?= h($flash['message']) ?>
      </div>
      <?php endif; ?>
