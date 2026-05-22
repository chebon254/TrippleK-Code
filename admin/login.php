<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Already logged in → redirect to dashboard
if (is_logged_in()) {
    redirect('/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter your email and password.';
        } elseif (admin_login($email, $password)) {
            redirect('/admin/index.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | <?= h(get_setting('company_name', APP_NAME)) ?></title>
  <link rel="icon" href="/assets/images/logo/favicon.jpeg">
  <link href="/assets/bundle.css" rel="stylesheet">
</head>
<body class="bg-light-2 flex min-h-screen items-center justify-center px-4">

<div class="w-full max-w-md">

  <!-- Logo -->
  <div class="mb-8 text-center">
    <img src="/assets/images/logo/tripplek-logo.jpeg" alt="Logo" width="220" height="56" class="mx-auto mb-3 w-[220px] h-[56px] object-contain">
    <p class="text-sm text-gray-500">Administration Panel</p>
  </div>

  <!-- Card -->
  <div class="rounded-xl bg-white p-8 shadow-lg">
    <h1 class="mb-6 text-xl font-semibold text-dark-1">Sign In</h1>

    <?php if ($error): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <?= h($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="/admin/login.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

      <div class="mb-4">
        <label for="email" class="mb-1.5 block text-sm font-medium text-dark-1">Email Address</label>
        <input type="email" id="email" name="email" required autocomplete="email"
          value="<?= h($_POST['email'] ?? '') ?>"
          class="border-border focus:border-blue-1 w-full rounded border px-4 py-3 text-sm outline-none focus:border-2"
          placeholder="admin@tripplek.co.ke">
      </div>

      <div class="mb-6" x-data="{ show: false }">
        <label for="password" class="mb-1.5 block text-sm font-medium text-dark-1">Password</label>
        <div class="relative">
          <input :type="show ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
            class="border-border focus:border-blue-1 w-full rounded border px-4 py-3 pr-12 text-sm outline-none focus:border-2"
            placeholder="Enter your password">
          <button type="button" @click="show = !show"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <i :class="show ? 'icon-eye-off' : 'icon-eye'" class="text-base"></i>
          </button>
        </div>
      </div>

      <button type="submit"
        class="bg-blue-1 hover:bg-blue-2 w-full rounded px-6 py-3 text-sm font-medium text-white transition-colors">
        Sign In
      </button>
    </form>

    <div class="mt-6 border-t border-gray-100 pt-4 text-center text-xs text-gray-400">
      <a href="/index.php" class="hover:text-blue-1 hover:underline">&larr; Back to website</a>
    </div>
  </div>

</div>

<script defer src="/assets/bundle.js"></script>
</body>
</html>
