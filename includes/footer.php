<?php
$company_name   = get_setting('company_name', APP_NAME);
$company_phone  = get_setting('company_phone', '');
$company_email  = get_setting('company_email', '');
$company_wa     = get_setting('company_whatsapp', '');
$kra_pin        = get_setting('kra_pin', '');
$tra_license    = get_setting('tra_license', '');
$year           = date('Y');
?>

        <!-- Footer -->
        <footer class="bg-dark-1 text-white">
          <div class="mx-auto max-w-7xl px-4 py-16">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-4">

              <div>
                <img src="/assets/images/logo/tripplek-logo.jpeg" alt="<?= h($company_name) ?>" width="200" height="48" class="mb-6 w-[200px] h-[48px] object-contain">
                <?php if ($company_phone): ?>
                <div class="mt-4">
                  <div class="text-sm text-white/70">Call Us</div>
                  <a href="tel:<?= h(preg_replace('/\s/', '', $company_phone)) ?>"
                    class="mt-1 block text-lg font-medium hover:text-yellow-3"><?= h($company_phone) ?></a>
                </div>
                <?php endif; ?>
                <?php if ($company_email): ?>
                <div class="mt-4">
                  <div class="text-sm text-white/70">Email Us</div>
                  <a href="mailto:<?= h($company_email) ?>"
                    class="mt-1 block font-medium hover:text-yellow-3"><?= h($company_email) ?></a>
                </div>
                <?php endif; ?>
                <?php if ($kra_pin): ?>
                <div class="mt-4 text-xs text-white/50">KRA PIN: <?= h($kra_pin) ?></div>
                <?php endif; ?>
                <?php if ($tra_license): ?>
                <div class="text-xs text-white/50">TRA License: <?= h($tra_license) ?></div>
                <?php endif; ?>
              </div>

              <div>
                <h5 class="mb-6 text-base font-medium">Services</h5>
                <ul class="space-y-2 text-white/90">
                  <li><a href="/cars.php" class="hover:underline">Car Hire</a></li>
                  <li><a href="/index.php#airport-transfer" class="hover:underline">Airport Transfer</a></li>
                  <li><a href="/index.php#chauffeur" class="hover:underline">Chauffeur Service</a></li>
                  <li><a href="/index.php#wedding" class="hover:underline">Wedding Packages</a></li>
                </ul>
              </div>

              <div>
                <h5 class="mb-6 text-base font-medium">Company</h5>
                <ul class="space-y-2 text-white/90">
                  <li><a href="/about.php" class="hover:underline">About Us</a></li>
                  <li><a href="/contact.php" class="hover:underline">Contact</a></li>
                  <li><a href="/terms.php" class="hover:underline">Terms & Conditions</a></li>
                </ul>
              </div>

              <div>
                <h5 class="mb-6 text-base font-medium">Quick Book</h5>
                <p class="mb-4 text-sm text-white/70">Ready to hit the road? Browse our fleet and book online.</p>
                <a href="/cars.php"
                  class="inline-block bg-blue-1 text-white rounded px-6 py-3 text-sm font-medium hover:bg-yellow-3 hover:text-dark-1 transition-colors">
                  Browse Cars
                </a>
                <?php if ($company_wa): ?>
                <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $company_wa)) ?>?text=Hello+<?= urlencode($company_name) ?>%2C+I%27d+like+to+enquire+about+a+booking."
                  target="_blank" rel="noopener"
                  class="mt-3 flex items-center gap-2 text-sm text-white/80 hover:text-white">
                  <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.556 4.116 1.526 5.845L.057 23.428c-.073.244.159.468.4.395l5.697-1.454A11.946 11.946 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.815 9.815 0 0 1-5.027-1.381l-.361-.215-3.724.95.985-3.635-.234-.373A9.818 9.818 0 1 1 12 21.818z"/></svg>
                  Chat on WhatsApp
                </a>
                <?php endif; ?>
              </div>

            </div>

            <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-white/15 pt-5 md:flex-row">
              <div class="text-sm text-white/60">
                &copy; <?= $year ?> <?= h($company_name) ?>. All rights reserved.
              </div>
              <div class="flex items-center gap-6 text-sm text-white/60">
                <a href="/terms.php" class="hover:text-white hover:underline">Terms</a>
                <a href="/contact.php" class="hover:text-white hover:underline">Contact</a>
              </div>
            </div>
          </div>
        </footer>

      </div><!-- /.flex.min-h-screen (Page Wrapper) -->


<?php if ($company_wa): ?>
<!-- WhatsApp Float Button -->
<a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $company_wa)) ?>?text=Hello+<?= urlencode($company_name) ?>%2C+I%27d+like+to+enquire+about+a+booking."
  target="_blank" rel="noopener" aria-label="Chat on WhatsApp"
  class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-green-500 text-white shadow-lg hover:bg-green-600 transition-colors">
  <svg class="h-7 w-7 fill-current" viewBox="0 0 24 24">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.556 4.116 1.526 5.845L.057 23.428c-.073.244.159.468.4.395l5.697-1.454A11.946 11.946 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.815 9.815 0 0 1-5.027-1.381l-.361-.215-3.724.95.985-3.635-.234-.373A9.818 9.818 0 1 1 12 21.818z"/>
  </svg>
</a>
<?php endif; ?>

<script defer src="/assets/bundle.js"></script>
</body>
</html>
