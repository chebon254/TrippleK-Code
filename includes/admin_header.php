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
  <link rel="icon" href="/assets/favicon.png">
  <link href="/assets/bundle.css" rel="stylesheet">
  <style>
    [x-cloak]{display:none!important}
    @keyframes gtWrap{0%,100%{transform:scale(1)}50%{transform:scale(1.2)}}
    @keyframes gtIcon{0%{transform:translateY(150%) scale(1)}50%{transform:translateY(0%) scale(1.2) rotate(20deg)}100%{transform:translateY(-150%) rotate(-20deg)}}
    .animate-gt-wrap{animation:gtWrap 1.8s ease infinite}
    .animate-gt-icon{animation:gtIcon 1.8s ease infinite}
    .js-preloader.-is-hidden{opacity:0;pointer-events:none}

    /* ── Admin sidebar ──
       Mobile:  hidden by default, .is-open shows it
       Desktop: visible by default, .is-collapsed hides it          */
    .admin-sidebar{transform:translateX(-100%);transition:transform .3s ease}
    .admin-sidebar.is-open{transform:translateX(0)}
    @media(min-width:1024px){
      .admin-sidebar{transform:translateX(0)}
      .admin-sidebar.is-collapsed{transform:translateX(-100%)}
    }

    /* ── Admin main ──
       Desktop: left-padded by sidebar width (18rem = 256px + gutter)
       Collapses to 0 when .is-collapsed is added                   */
    .admin-main{transition:padding-left .3s ease}
    @media(min-width:1024px){
      .admin-main{padding-left:18rem}
      .admin-main.is-collapsed{padding-left:0}
    }
  </style>
  <script>
    function dashboardPage(){
      return {
        loaded: true,
        sidebarOpen: false,
        init(){ setTimeout(()=>{ this.loaded = false }, 400) }
      }
    }
    function dropdown(){return{open:false,toggle(){this.open=!this.open},close(){this.open=false}}}
  </script>
</head>
<body x-data="dashboardPage()" x-init="init()">

<!-- Preloader -->
<noscript><style>#gt-preloader{display:none!important}</style></noscript>
<div id="gt-preloader"
  class="js-preloader fixed inset-0 z-5000 flex flex-col items-center justify-center bg-white transition-opacity duration-500"
  role="status" x-show="loaded"
  x-init="window.addEventListener('DOMContentLoaded', () => { setTimeout(() => loaded = false, 400) })">
  <div class="animate-gt-wrap relative flex h-[72px] w-[72px] items-center justify-center overflow-hidden rounded-[30px] bg-white shadow-[0_2px_24px_rgba(0,0,0,0.08)]">
    <div class="animate-gt-icon absolute">
      <svg class="h-[37px] w-[38px] text-blue-600" viewBox="0 0 38 37" fill="none">
        <path d="M32.9675 13.9422C32.9675 6.25436 26.7129 0 19.0251 0C11.3372 0 5.08289 6.25436 5.08289 13.9422C5.08289 17.1322 7.32025 21.6568 11.7327 27.3906C13.0538 29.1071 14.3656 30.6662 15.4621 31.9166V35.8212C15.4621 36.4279 15.9539 36.92 16.561 36.92H21.4895C22.0965 36.92 22.5883 36.4279 22.5883 35.8212V31.9166C23.6849 30.6662 24.9966 29.1071 26.3177 27.3906C30.7302 21.6568 32.9675 17.1322 32.9675 13.9422Z" fill="currentColor"/>
      </svg>
    </div>
  </div>
  <div class="mt-4 text-xl font-semibold text-slate-800"><?= h($site_name) ?></div>
</div>

<!-- ===== Admin Layout ===== -->

  <!-- ===== Header ===== -->
  <header class="fixed top-0 z-40 w-full bg-white shadow-sm"
    x-data="{ searchOpen: false, notificationsOpen: false, profileOpen: false }">
    <div class="px-6 py-4 sm:px-8">
      <div class="flex items-center justify-between">

        <!-- Left: Logo + Toggles + Search -->
        <div class="flex items-center gap-6">
          <a href="/admin/index.php" class="flex min-w-[140px] items-center">
            <img src="/assets/images/general/logo-dark.svg" alt="<?= h($site_name) ?>" class="w-[140px]">
          </a>

          <!-- Mobile toggle -->
          <button @click="sidebarOpen = !sidebarOpen"
            class="rounded-lg p-2 transition-colors hover:bg-gray-100 lg:hidden">
            <i class="icon-menu-2 text-xl"></i>
          </button>

          <!-- Desktop toggle -->
          <button @click="sidebarOpen = !sidebarOpen"
            class="hidden rounded-lg p-2 transition-colors hover:bg-gray-100 lg:block">
            <i class="icon-menu-2 text-xl"></i>
          </button>

          <!-- Search -->
          <div class="relative hidden md:block">
            <form method="get" action="/admin/bookings.php">
              <input type="search" name="search" placeholder="Search bookings..."
                class="border-border focus:border-dark-1 w-64 rounded border py-2.5 pr-4 pl-12 outline-none focus:border-2">
              <i class="icon-search text-light-1 absolute top-1/2 left-4 -translate-y-1/2 text-lg"></i>
            </form>
          </div>
        </div>

        <!-- Right: Notifications + Messages + Profile -->
        <div class="flex items-center gap-4">

          <!-- Notifications -->
          <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
              class="relative hidden h-12 w-12 items-center justify-center rounded-full bg-blue-50 transition-colors hover:bg-blue-100 lg:flex">
              <i class="icon-notification text-blue-1 text-xl"></i>
              <?php if ($_pending_count > 0): ?>
              <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500"></span>
              <?php endif; ?>
            </button>

            <!-- Notifications Dropdown -->
            <div x-show="open" @click.away="open = false"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100"
              class="absolute right-0 z-50 mt-2 w-80 rounded bg-white shadow-2xl"
              style="display:none">
              <div class="border-b border-gray-100 p-4">
                <h3 class="text-dark-1 font-semibold">Notifications</h3>
              </div>
              <div class="max-h-72 overflow-y-auto">
                <?php if ($_pending_count > 0): ?>
                <a href="/admin/bookings.php?status=pending"
                  class="hover:bg-light-2 flex cursor-pointer items-start gap-3 border-b border-gray-50 p-4">
                  <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100">
                    <i class="icon-clock text-yellow-600"></i>
                  </div>
                  <div class="flex-1">
                    <p class="text-dark-1 text-sm font-medium">Pending Bookings</p>
                    <p class="text-light-1 mt-1 text-xs"><?= $_pending_count ?> booking<?= $_pending_count !== 1 ? 's' : '' ?> awaiting confirmation</p>
                  </div>
                </a>
                <?php else: ?>
                <div class="p-6 text-center text-sm text-gray-400">No new notifications</div>
                <?php endif; ?>
              </div>
              <div class="border-t border-gray-100 p-3">
                <a href="/admin/bookings.php?status=pending"
                  class="text-blue-1 block w-full text-center text-sm font-medium hover:text-blue-700">
                  View All Bookings
                </a>
              </div>
            </div>
          </div>

          <!-- Messages / View Site -->
          <a href="/index.php" target="_blank"
            class="hidden h-12 w-12 items-center justify-center rounded-full bg-blue-50 transition-colors hover:bg-blue-100 lg:flex"
            title="View Website">
            <i class="icon-arrow-top-right text-blue-1 text-xl"></i>
          </a>

          <!-- Profile Dropdown -->
          <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
              class="flex items-center gap-3 transition-opacity hover:opacity-80">
              <img src="/assets/images/avatars/3.png" alt="Admin"
                class="h-12 w-12 rounded-full border-2 border-blue-100 object-cover">
              <div class="hidden text-left lg:block">
                <div class="text-dark-1 text-sm font-semibold"><?= h($_admin_name) ?></div>
                <div class="text-light-1 text-xs"><?= h($_admin_email) ?></div>
              </div>
              <i class="icon-chevron-sm-down text-light-1 hidden lg:block"></i>
            </button>

            <!-- Profile Dropdown Menu -->
            <div x-show="open" @click.away="open = false"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100"
              class="absolute right-0 z-50 mt-2 w-56 rounded bg-white shadow-2xl"
              style="display:none">
              <div class="border-b border-gray-100 p-4">
                <div class="text-dark-1 text-sm font-semibold"><?= h($_admin_name) ?></div>
                <div class="text-light-1 text-xs"><?= h($_admin_email) ?></div>
              </div>
              <div class="py-2">
                <a href="/admin/settings.php"
                  class="text-dark-1 hover:bg-light-2 flex items-center gap-3 px-4 py-2 text-sm">
                  <i class="icon-shield text-light-1"></i>
                  <span>Settings</span>
                </a>
                <a href="/admin/bookings.php"
                  class="text-dark-1 hover:bg-light-2 flex items-center gap-3 px-4 py-2 text-sm">
                  <i class="icon-calendar text-light-1"></i>
                  <span>Bookings</span>
                </a>
                <a href="/admin/cars.php"
                  class="text-dark-1 hover:bg-light-2 flex items-center gap-3 px-4 py-2 text-sm">
                  <i class="icon-car text-light-1"></i>
                  <span>Fleet</span>
                </a>
              </div>
              <div class="border-t border-gray-100 py-2">
                <a href="/admin/logout.php"
                  class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                  <i class="icon-arrow-right"></i>
                  <span>Logout</span>
                </a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </header>

<!-- ===== Flex wrapper: sidebar + main ===== -->
<div class="flex pt-20">

  <!-- ===== Sidebar ===== -->
  <aside class="admin-sidebar fixed inset-y-0 left-0 z-30 mt-20 w-64 bg-white shadow-lg"
    :class="sidebarOpen ? 'is-open is-collapsed' : ''">
    <div class="h-full overflow-y-auto pt-6 pb-6">
      <nav class="space-y-2 px-4">

        <?php
        $nav_items = [
          ['href'=>'/admin/index.php',   'page'=>'index',    'icon'=>'/assets/images/dashboard/sidebar/compass.svg',  'label'=>'Dashboard'],
          ['href'=>'/admin/bookings.php','page'=>'bookings', 'icon'=>'/assets/images/dashboard/sidebar/booking.svg',  'label'=>'Bookings',
           'badge' => $_pending_count ?: 0],
          ['href'=>'/admin/cars.php',    'page'=>'cars',     'icon'=>'/assets/images/dashboard/sidebar/taxi.svg',     'label'=>'Fleet / Cars'],
          ['href'=>'/admin/payments.php','page'=>'payments', 'icon'=>'/assets/images/dashboard/sidebar/bookmark.svg', 'label'=>'Payments'],
          ['href'=>'/admin/settings.php','page'=>'settings', 'icon'=>'/assets/images/dashboard/sidebar/gear.svg',     'label'=>'Settings'],
        ];
        foreach ($nav_items as $item):
          $active = ($admin_page === $item['page']);
        ?>
        <a href="<?= $item['href'] ?>"
          class="flex items-center gap-4 rounded px-4 py-3 font-medium transition-colors
            <?= $active ? 'text-blue-1 bg-blue-50' : 'text-dark-1 hover:bg-light-2' ?>">
          <img src="<?= $item['icon'] ?>" alt="<?= $item['label'] ?>" class="h-5 w-5">
          <span><?= $item['label'] ?></span>
          <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
          <span class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-semibold text-white">
            <?= $item['badge'] ?>
          </span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>

        <div class="mt-4 border-t border-gray-100 pt-4">
          <a href="/index.php" target="_blank"
            class="flex items-center gap-4 rounded px-4 py-3 font-medium text-dark-1 transition-colors hover:bg-light-2">
            <img src="/assets/images/dashboard/sidebar/map.svg" alt="View Site" class="h-5 w-5">
            <span>View Website</span>
          </a>
          <a href="/admin/logout.php"
            class="flex items-center gap-4 rounded px-4 py-3 font-medium transition-colors hover:bg-red-50"
            style="color: #ef4444">
            <img src="/assets/images/dashboard/sidebar/log-out.svg" alt="Logout" class="h-5 w-5">
            <span>Logout</span>
          </a>
        </div>

      </nav>
    </div>
  </aside>

  <!-- Sidebar overlay (mobile) -->
  <div x-show="sidebarOpen" @click="sidebarOpen = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-20 bg-black/50 lg:hidden" style="display:none"></div>

  <!-- ===== Main Content ===== -->
  <main class="bg-light-2 flex min-h-screen flex-1 flex-col admin-main"
    :class="sidebarOpen ? 'is-collapsed' : ''">
    <div class="p-6 lg:p-8">

      <!-- Flash Message -->
      <?php if ($flash): ?>
      <div class="mb-6 flex items-center gap-3 rounded px-4 py-3 text-sm
        <?= $flash['type'] === 'success'
          ? 'bg-green-50 text-green-700 border border-green-200'
          : 'bg-red-50 text-red-600 border border-red-200' ?>">
        <i class="<?= $flash['type'] === 'success' ? 'icon-check' : 'icon-x' ?> text-base"></i>
        <?= h($flash['message']) ?>
      </div>
      <?php endif; ?>
