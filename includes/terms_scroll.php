<!-- ── Terms & Conditions (scroll-to-accept) ── -->
<div class="mt-6 mb-4">
  <div class="mb-2 flex items-center justify-between">
    <p class="text-sm font-semibold text-white">Terms &amp; Conditions <span class="text-red-400">*</span></p>
    <span x-show="!termsRead" class="text-xs text-amber-400 flex items-center gap-1">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
      Scroll to read &amp; accept
    </span>
    <span x-show="termsRead" x-cloak class="text-xs text-green-400 flex items-center gap-1">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
      Terms accepted
    </span>
  </div>

  <div class="relative rounded border border-border bg-dark-2 overflow-hidden">
    <div class="h-56 overflow-y-auto p-5 text-sm leading-relaxed text-white/75 space-y-5"
      style="scrollbar-width:thin;scrollbar-color:#c8942a #1a1a1a"
      @scroll="if($event.target.scrollTop+$event.target.clientHeight>=$event.target.scrollHeight-10) termsRead=true">

      <div>
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-white">Authorised Use &amp; Driving</p>
        <ul class="space-y-1.5 list-disc list-inside">
          <li>Only the driver(s) named in this agreement are authorized to operate the vehicle.</li>
          <li>All drivers must be at least 24 years of age and hold a valid driving licence with a minimum of three (3) years' driving experience.</li>
          <li>The vehicle shall not be driven under the influence of alcohol, drugs, or any substance that may impair the driver's ability to operate the vehicle safely.</li>
          <li>The vehicle shall not be used for racing, speed testing, towing, overloading, commercial haulage, or any unlawful purpose.</li>
          <li>The hirer shall not transport fare-paying passengers, illegal goods, or prohibited substances.</li>
          <li>The vehicle shall not be taken outside the Republic of Kenya without prior written consent from the Company.</li>
        </ul>
      </div>

      <div>
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-white">Financial Liabilities</p>
        <ul class="space-y-1.5 list-disc list-inside">
          <li>The hirer shall be solely responsible for all traffic fines, parking penalties, toll charges, and any other violations incurred during the hire period.</li>
          <li>An administrative penalty of <strong class="text-white">KES 5,000</strong> per offence shall apply for any unpaid fines requiring the Company's intervention.</li>
          <li>The cost of panel repainting shall be <strong class="text-white">KES 11,000</strong> per affected panel.</li>
          <li>Any tampering, disconnection, or interference with the vehicle's speedometer or mileage recording device shall attract charges equivalent to the applicable daily hire rate, in addition to the full cost of repair or replacement.</li>
          <li>Any mileage exceeding the agreed limit shall attract an additional charge equivalent to 100% of the total booking value, unless otherwise agreed in writing.</li>
          <li>In the event of cancellation, the hirer shall forfeit the full booking amount together with an additional charge equivalent to three (3) days' hire. No refunds shall be issued once the vehicle has been handed over to the hirer.</li>
          <li>In the event of delayed payment or refusal to pay, the Company reserves the right to repossess the vehicle and pursue all lawful means of recovery, including legal action.</li>
        </ul>
      </div>

      <div>
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-white">Accidents &amp; Damage Reporting</p>
        <ul class="space-y-1.5 list-disc list-inside">
          <li>Any accident, theft, loss, or damage must be reported to the Company and the nearest police station within <strong class="text-white">24 hours</strong> of occurrence.</li>
          <li>The hirer shall provide a Police Abstract and written incident report within <strong class="text-white">48 hours</strong>.</li>
          <li>In the event of damage, the hirer shall be liable up to <strong class="text-white">KES 300,000</strong> for saloon/small vehicles and <strong class="text-white">KES 500,000</strong> for premium, luxury, SUVs, and large vehicles.</li>
          <li>The hirer shall be responsible for all costs relating to towing, recovery, repairs, replacement parts, and related expenses.</li>
          <li>Where the Company's insurance is limited to third-party risks, the hirer shall be liable for uninsured losses, damages from riots or civil disturbances, and total loss of the vehicle.</li>
        </ul>
      </div>

      <div>
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-white">Vehicle Care &amp; Fuel</p>
        <ul class="space-y-1.5 list-disc list-inside">
          <li>The vehicle shall be returned in the same condition and with the same fuel level as when issued to the hirer.</li>
          <li>During the hire period, the hirer shall monitor engine oil, brake fluid, coolant levels, and tyre pressure.</li>
          <li>The hirer shall take all reasonable precautions to secure the vehicle whenever it is unattended.</li>
          <li>The hirer shall bear the cost of tyre damage, punctures, lost tools, missing accessories, and any damage resulting from negligence or misuse.</li>
          <li>No modifications, alterations, or installations may be made to the vehicle without the Company's prior written approval.</li>
          <li>Vehicle returns shall only be accepted between <strong class="text-white">8:00 a.m. and 6:00 p.m.</strong>, Monday to Sunday, unless otherwise agreed in writing.</li>
        </ul>
      </div>

      <div class="border-t border-border pt-4">
        <p class="text-xs text-white/50 leading-relaxed italic">
          I confirm that I am the sole authorized driver of the vehicle, unless additional authorized drivers have been specified in this agreement. I acknowledge that I have read, understood, and agreed to all the terms and conditions contained herein, and I accept full responsibility for the vehicle throughout the entire hire period until it is returned to the Company in accordance with this agreement.
        </p>
      </div>

    </div>
    <!-- Fade gradient to indicate more content below -->
    <div x-show="!termsRead"
      class="pointer-events-none absolute bottom-0 left-0 right-0 h-10 bg-gradient-to-t from-dark-2 to-transparent"></div>
  </div>
</div>
