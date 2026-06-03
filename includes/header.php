<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$site_name    = get_setting('company_name', APP_NAME);
$whatsapp     = get_setting('company_whatsapp', '');

// ── SEO helpers ──────────────────────────────────────────────────────────────
$_req_path      = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$_canonical     = $page_canonical ?? (rtrim(APP_URL, '/') . $_req_path);
$_seo_desc      = $page_description ?? 'Affordable car hire, airport transfers, chauffeur and wedding packages in Nairobi, Kenya.';
$_seo_image     = $page_og_image ?? (APP_URL . '/assets/images/logo/tripplek-logo.jpeg');
$_raw_title     = $page_title ?? $site_name;

// LocalBusiness JSON-LD (appears on every public page)
$_ld = [
    '@context'    => 'https://schema.org',
    '@type'       => ['LocalBusiness', 'AutoRental'],
    'name'        => $site_name,
    'url'         => APP_URL,
    'logo'        => APP_URL . '/assets/images/logo/tripplek-logo.jpeg',
    'image'       => APP_URL . '/assets/images/logo/tripplek-logo.jpeg',
    'description' => 'Trusted car hire, airport transfers, chauffeur and wedding packages in Nairobi, Kenya.',
    'telephone'   => get_setting('company_phone', ''),
    'email'       => get_setting('company_email', ''),
    'address'     => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => get_setting('company_address', 'Nairobi, Kenya'),
        'addressLocality' => 'Nairobi',
        'addressCountry'  => 'KE',
    ],
    'geo'         => ['@type' => 'GeoCoordinates', 'latitude' => '-1.2921', 'longitude' => '36.8219'],
    'areaServed'  => ['@type' => 'Country', 'name' => 'Kenya'],
    'priceRange'  => '$$',
    'openingHours'=> 'Mo-Su 07:00-20:00',
    'sameAs'      => [get_setting('company_website', APP_URL)],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title><?= h($_raw_title) ?> | <?= h($site_name) ?></title>
  <meta name="description" content="<?= h($_seo_desc) ?>">
  <link rel="canonical" href="<?= h($_canonical) ?>">
  <!-- Open Graph (WhatsApp / Facebook / LinkedIn) -->
  <meta property="og:type"        content="website">
  <meta property="og:site_name"   content="<?= h($site_name) ?>">
  <meta property="og:title"       content="<?= h($_raw_title) ?>">
  <meta property="og:description" content="<?= h($_seo_desc) ?>">
  <meta property="og:url"         content="<?= h($_canonical) ?>">
  <meta property="og:image"       content="<?= h($_seo_image) ?>">
  <meta property="og:locale"      content="en_KE">
  <!-- Twitter / X Card -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= h($_raw_title) ?>">
  <meta name="twitter:description" content="<?= h($_seo_desc) ?>">
  <meta name="twitter:image"       content="<?= h($_seo_image) ?>">
  <!-- Structured Data -->
  <script type="application/ld+json"><?= json_encode($_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
  <?php if (!empty($page_schema)): ?>
  <script type="application/ld+json"><?= $page_schema ?></script>
  <?php endif; ?>
  <link rel="icon" href="/assets/images/logo/favicon.jpeg">
  <link href="/assets/bundle.css" rel="stylesheet">
  <style>
    [x-cloak]{display:none!important}
    /* Dark-theme native form controls */
    select,input[type="date"],input[type="time"]{color-scheme:dark}
    select option{background:#111;color:#fff}
  </style>
  <script>
    function dropdown(){return{open:false,toggle(){this.open=!this.open},close(){this.open=false}}}
    function megaMenu(){return{open:false,activeTab:'hotel',toggle(){this.open=!this.open},close(){this.open=false}}}
    function navbar(){return{mobileOpen:false,searchOpen:false,isSticky:false,forceSticky:false,currentPage:'',handleScroll(){this.isSticky=this.forceSticky||window.scrollY>50},init(){this.forceSticky=this.$el.dataset.forceWhite==='1';this.handleScroll();this._sh=()=>this.handleScroll();window.addEventListener('scroll',this._sh,{passive:true});this.currentPage=window.location.pathname.split('/').pop().replace('.php','')},destroy(){if(this._sh)window.removeEventListener('scroll',this._sh)}}}
    function carSinglePage(){return{gallery:[],activeImage:''}}
    function accordion(){return{activeIndex:null,toggle(i){this.activeIndex=this.activeIndex===i?null:i}}}
  </script>
</head>
<body x-data="{ loaded: true, mobileOpen: false, searchOpen: false }">

<!-- Preloader -->
<noscript><style>#gt-preloader{display:none!important}</style></noscript>
<div id="gt-preloader"
  class="js-preloader fixed inset-0 z-5000 flex flex-col items-center justify-center bg-dark-1 transition-opacity duration-500"
  role="status" aria-live="polite" aria-label="Loading"
  x-show="loaded"
  x-init="window.addEventListener('DOMContentLoaded', () => { setTimeout(() => loaded = false, 400) })">
  <div class="animate-gt-wrap relative flex h-[72px] w-[72px] items-center justify-center overflow-hidden rounded-[30px] bg-dark-3 shadow-[0_2px_24px_rgba(200,148,42,0.2)]">
    <div class="animate-gt-icon absolute">
      <svg class="h-[37px] w-[38px] text-yellow-3" viewBox="0 0 38 37" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g clip-path="url(#pclip)">
          <path d="M32.9675 13.9422C32.9675 6.25436 26.7129 0 19.0251 0C11.3372 0 5.08289 6.25436 5.08289 13.9422C5.08289 17.1322 7.32025 21.6568 11.7327 27.3906C13.0538 29.1071 14.3656 30.6662 15.4621 31.9166V35.8212C15.4621 36.4279 15.9539 36.92 16.561 36.92H21.4895C22.0965 36.92 22.5883 36.4279 22.5883 35.8212V31.9166C23.6849 30.6662 24.9966 29.1071 26.3177 27.3906C30.7302 21.6568 32.9675 17.1322 32.9675 13.9422Z" fill="currentColor"/>
        </g>
        <defs><clipPath id="pclip"><rect width="36.92" height="36.92" fill="white" transform="translate(0.54)"/></clipPath></defs>
      </svg>
    </div>
  </div>
  <div class="mt-4 text-2xl font-semibold text-white"><?= h($site_name) ?></div>
</div>
<style>
  .js-preloader.-is-hidden{opacity:0;pointer-events:none}
  @keyframes gtWrap{0%,100%{transform:scale(1)}50%{transform:scale(1.2)}}
  @keyframes gtIcon{0%{transform:translateY(150%) scale(1)}50%{transform:translateY(0%) scale(1.2) rotate(20deg)}100%{transform:translateY(-150%) rotate(-20deg)}}
  .animate-gt-wrap{animation:gtWrap 1.8s ease infinite}
  .animate-gt-icon{animation:gtIcon 1.8s ease infinite}
</style>

<div class="flex min-h-screen flex-col justify-between overflow-hidden">

<!-- Header -->
<header x-data="navbar()" data-force-white="<?= !empty($nav_white) ? '1' : '0' ?>"
  :class="isSticky ? 'bg-dark-2 fixed border-border' : 'border-transparent bg-transparent absolute'"
  class="inset-x-0 top-0 z-50 border-b transition-all duration-300">
  <div class="mx-auto max-w-[1500px] px-8 sm:px-5">
    <div class="flex h-20 items-center justify-between">

      <!-- Logo + Desktop Nav -->
      <div class="flex items-center">
        <a href="/" class="mr-12 min-w-[140px]">
          <img src="/assets/images/logo/tripplek-logo.jpeg" alt="<?= h($site_name) ?>" width="200" height="50" class="w-[200px] h-[50px] object-contain">
        </a>
        <nav class="hidden xl:flex">
          <ul class="text-white flex items-center gap-6">
            <li>
              <a href="/"
                class="py-8 <?= $current_page === 'index' ? 'text-blue-1' : 'hover:text-blue-1' ?>">
                Home
              </a>
            </li>
            <li>
              <a href="/cars"
                class="py-8 <?= $current_page === 'cars' ? 'text-blue-1' : 'hover:text-blue-1' ?>">
                Car Hire
              </a>
            </li>
            <li x-data="dropdown()" @mouseenter="open=true" @mouseleave="open=false" class="relative">
              <button class="hover:text-blue-1 flex items-center py-8">
                <span class="mr-2">Services</span>
                <span :class="open?'rotate-180':''"><i class="icon-chevron-sm-down text-[8px]"></i></span>
              </button>
              <div x-cloak x-show="open" x-transition class="absolute top-full left-0 mt-0 w-52 rounded bg-dark-3 p-2.5 text-sm shadow-xl border border-border">
                <a href="/#airport-transfer" class="block rounded px-5 py-2 text-white/80 hover:text-blue-1 hover:bg-blue-1/10">Airport Transfer</a>
                <a href="/#chauffeur" class="block rounded px-5 py-2 text-white/80 hover:text-blue-1 hover:bg-blue-1/10">Chauffeur Service</a>
                <a href="/#wedding" class="block rounded px-5 py-2 text-white/80 hover:text-blue-1 hover:bg-blue-1/10">Wedding Packages</a>
              </div>
            </li>
            <li>
              <a href="/about"
                class="py-8 <?= $current_page === 'about' ? 'text-blue-1' : 'hover:text-blue-1' ?>">
                About
              </a>
            </li>
            <li>
              <a href="/contact"
                class="py-8 <?= $current_page === 'contact' ? 'text-blue-1' : 'hover:text-blue-1' ?>">
                Contact
              </a>
            </li>
            <li>
              <a href="/register"
                class="py-8 <?= $current_page === 'register' ? 'text-blue-1' : 'hover:text-blue-1' ?>">
                New Customer
              </a>
            </li>
          </ul>
        </nav>
      </div>

      <!-- Right: CTA + Mobile Toggle -->
      <div class="flex items-center gap-3">
        <a href="/register"
          class="hidden xl:inline-flex items-center h-12 rounded border border-blue-1 text-blue-1 px-6 text-sm font-medium whitespace-nowrap transition-colors hover:bg-blue-1 hover:text-dark-1 <?= $current_page === 'register' ? 'bg-blue-1 text-dark-1' : '' ?>">
          New Customer
        </a>
        <a href="/cars"
          class="hidden xl:inline-flex bg-blue-1 text-white items-center h-12 rounded px-8 text-sm font-medium whitespace-nowrap transition-colors hover:bg-yellow-3 hover:text-dark-1">
          Book Now
        </a>
        <!-- Mobile icons -->
        <div class="flex items-center space-x-4 xl:hidden">
          <button @click="mobileOpen = !mobileOpen" class="text-white hover:text-blue-1">
            <i class="icon-menu text-xl"></i>
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- Mobile Drawer -->
  <div x-cloak x-show="mobileOpen" class="fixed inset-0 z-50">
    <div @click="mobileOpen=false" x-show="mobileOpen"
      class="absolute inset-0 bg-black/50" style="display:none"></div>
    <nav x-show="mobileOpen"
      x-transition:enter="transition ease-out duration-300"
      x-transition:enter-start="-translate-x-full"
      x-transition:enter-end="translate-x-0"
      class="absolute inset-y-0 left-0 w-72 bg-dark-2 p-6 shadow-xl overflow-y-auto border-r border-border"
      style="display:none">
      <div class="mb-8 flex items-center justify-between">
        <img src="/assets/images/logo/tripplek-logo.jpeg" alt="<?= h($site_name) ?>" width="180" height="44" class="w-[180px] h-[44px] object-contain">
        <button @click="mobileOpen=false"><i class="icon-close text-xl"></i></button>
      </div>
      <ul class="space-y-2 text-white">
        <li><a href="/" class="block rounded px-4 py-3 font-medium hover:bg-blue-1/5 hover:text-blue-1 <?= $current_page==='index'?'text-blue-1 bg-blue-1/5':'' ?>">Home</a></li>
        <li><a href="/cars" class="block rounded px-4 py-3 font-medium hover:bg-blue-1/5 hover:text-blue-1 <?= $current_page==='cars'?'text-blue-1 bg-blue-1/5':'' ?>">Car Hire</a></li>
        <li><a href="/#airport-transfer" class="block rounded px-4 py-3 font-medium hover:bg-blue-1/5 hover:text-blue-1">Airport Transfer</a></li>
        <li><a href="/#chauffeur" class="block rounded px-4 py-3 font-medium hover:bg-blue-1/5 hover:text-blue-1">Chauffeur Service</a></li>
        <li><a href="/#wedding" class="block rounded px-4 py-3 font-medium hover:bg-blue-1/5 hover:text-blue-1">Wedding Packages</a></li>
        <li><a href="/about" class="block rounded px-4 py-3 font-medium hover:bg-blue-1/5 hover:text-blue-1 <?= $current_page==='about'?'text-blue-1 bg-blue-1/5':'' ?>">About Us</a></li>
        <li><a href="/contact" class="block rounded px-4 py-3 font-medium hover:bg-blue-1/5 hover:text-blue-1 <?= $current_page==='contact'?'text-blue-1 bg-blue-1/5':'' ?>">Contact</a></li>
        <li><a href="/register" class="block rounded px-4 py-3 font-medium hover:bg-blue-1/5 hover:text-blue-1 <?= $current_page==='register'?'text-blue-1 bg-blue-1/5':'' ?>">New Customer</a></li>
      </ul>
      <div class="mt-6 space-y-3">
        <a href="/cars" class="bg-blue-1 text-white block text-center rounded px-4 py-3 font-medium hover:bg-yellow-3 hover:text-dark-1 transition-colors">Book a Car</a>
        <a href="/register" class="border border-blue-1 text-blue-1 block text-center rounded px-4 py-3 font-medium hover:bg-blue-1 hover:text-dark-1 transition-colors">New Customer</a>
      </div>
    </nav>
  </div>
</header>
