<!-- ── Terms & Conditions (scroll-to-accept) ── -->
<div class="mt-6 mb-4">

  <!-- Header row -->
  <div class="mb-2 flex items-center justify-between">
    <p class="text-sm font-semibold text-white">Terms &amp; Conditions <span class="text-red-400">*</span></p>
    <span x-show="!termsRead"
      style="display:inline-block;font-size:.7rem;color:#f59e0b;white-space:nowrap">
      &#9660; Scroll to read &amp; accept
    </span>
    <span x-show="termsRead" x-cloak
      style="display:inline-block;font-size:.7rem;color:#4ade80;white-space:nowrap">
      &#10003; Terms accepted
    </span>
  </div>

  <!-- Scrollable box -->
  <div style="position:relative;border:1px solid #2a2a2a;border-radius:.5rem;overflow:hidden">
    <div
      style="height:14rem;overflow-y:auto;background:#111;padding:1.25rem 1.25rem 1.25rem 1.25rem;font-size:.8125rem;line-height:1.65;color:rgba(255,255,255,.72);scrollbar-width:thin;scrollbar-color:#c8942a #1a1a1a"
      @scroll="if($event.target.scrollTop+$event.target.clientHeight>=$event.target.scrollHeight-12) termsRead=true">

      <!-- Section 1 -->
      <p style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#fff;margin-bottom:.6rem">Authorised Use &amp; Driving</p>
      <ul style="margin:0 0 1.25rem 0;padding-left:1.25rem;list-style:disc">
        <li style="margin-bottom:.45rem">Only the driver(s) named in this agreement are authorized to operate the vehicle.</li>
        <li style="margin-bottom:.45rem">All drivers must be at least 24 years of age and hold a valid driving licence with a minimum of three (3) years&#39; driving experience.</li>
        <li style="margin-bottom:.45rem">The vehicle shall not be driven under the influence of alcohol, drugs, or any substance that may impair the driver&#39;s ability to operate the vehicle safely.</li>
        <li style="margin-bottom:.45rem">The vehicle shall not be used for racing, speed testing, towing, overloading, commercial haulage, or any unlawful purpose.</li>
        <li style="margin-bottom:.45rem">The hirer shall not transport fare-paying passengers, illegal goods, or prohibited substances.</li>
        <li style="margin-bottom:.45rem">The vehicle shall not be taken outside the Republic of Kenya without prior written consent from the Company.</li>
      </ul>

      <!-- Section 2 -->
      <p style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#fff;margin-bottom:.6rem">Financial Liabilities</p>
      <ul style="margin:0 0 1.25rem 0;padding-left:1.25rem;list-style:disc">
        <li style="margin-bottom:.45rem">The hirer shall be solely responsible for all traffic fines, parking penalties, toll charges, and any other violations incurred during the hire period.</li>
        <li style="margin-bottom:.45rem">An administrative penalty of <strong style="color:#fff">KES 5,000</strong> per offence shall apply for any unpaid fines requiring the Company&#39;s intervention.</li>
        <li style="margin-bottom:.45rem">The cost of panel repainting shall be <strong style="color:#fff">KES 11,000</strong> per affected panel.</li>
        <li style="margin-bottom:.45rem">Any tampering with the vehicle&#39;s speedometer or mileage recording device shall attract charges equivalent to the daily hire rate plus full repair or replacement cost.</li>
        <li style="margin-bottom:.45rem">Any mileage exceeding the agreed limit shall attract an additional charge equivalent to 100% of the total booking value, unless otherwise agreed in writing.</li>
        <li style="margin-bottom:.45rem">In the event of cancellation, the hirer shall forfeit the full booking amount together with an additional charge equivalent to three (3) days&#39; hire. No refunds shall be issued once the vehicle has been handed over to the hirer.</li>
        <li style="margin-bottom:.45rem">In the event of delayed payment or refusal to pay, the Company reserves the right to repossess the vehicle and pursue all lawful means of recovery, including legal action.</li>
      </ul>

      <!-- Section 3 -->
      <p style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#fff;margin-bottom:.6rem">Accidents &amp; Damage Reporting</p>
      <ul style="margin:0 0 1.25rem 0;padding-left:1.25rem;list-style:disc">
        <li style="margin-bottom:.45rem">Any accident, theft, loss, or damage must be reported to the Company and the nearest police station within <strong style="color:#fff">24 hours</strong> of occurrence.</li>
        <li style="margin-bottom:.45rem">The hirer shall provide a Police Abstract and written incident report within <strong style="color:#fff">48 hours</strong>.</li>
        <li style="margin-bottom:.45rem">The hirer shall be liable up to <strong style="color:#fff">KES 300,000</strong> for saloon/small vehicles and <strong style="color:#fff">KES 500,000</strong> for premium, luxury, SUVs, and large vehicles.</li>
        <li style="margin-bottom:.45rem">The hirer shall be responsible for all costs relating to towing, recovery, repairs, replacement parts, and related expenses arising from any incident.</li>
        <li style="margin-bottom:.45rem">Where the Company&#39;s insurance is limited to third-party risks, the hirer shall be liable for all uninsured losses, damages from riots or civil disturbances, and total loss of the vehicle.</li>
      </ul>

      <!-- Section 4 -->
      <p style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#fff;margin-bottom:.6rem">Vehicle Care &amp; Fuel</p>
      <ul style="margin:0 0 1.25rem 0;padding-left:1.25rem;list-style:disc">
        <li style="margin-bottom:.45rem">The vehicle shall be returned in the same condition and with the same fuel level as when issued to the hirer.</li>
        <li style="margin-bottom:.45rem">During the hire period, the hirer shall monitor engine oil, brake fluid, coolant levels, and tyre pressure.</li>
        <li style="margin-bottom:.45rem">The hirer shall take all reasonable precautions to secure the vehicle whenever it is unattended.</li>
        <li style="margin-bottom:.45rem">The hirer shall bear the cost of tyre damage, punctures, lost tools, missing accessories, and any damage resulting from negligence or misuse.</li>
        <li style="margin-bottom:.45rem">No modifications, alterations, or installations may be made to the vehicle without the Company&#39;s prior written approval.</li>
        <li style="margin-bottom:.45rem">Vehicle returns shall only be accepted between <strong style="color:#fff">8:00 a.m. and 6:00 p.m.</strong>, Monday to Sunday, unless otherwise agreed in writing.</li>
      </ul>

      <!-- Signature paragraph -->
      <p style="border-top:1px solid #2a2a2a;padding-top:.9rem;font-size:.75rem;color:rgba(255,255,255,.45);font-style:italic;line-height:1.7">
        I confirm that I am the sole authorized driver of the vehicle, unless additional authorized drivers have been specified in this agreement. I acknowledge that I have read, understood, and agreed to all the terms and conditions contained herein, and I accept full responsibility for the vehicle throughout the entire hire period until it is returned to the Company in accordance with this agreement.
      </p>

    </div>

    <!-- Bottom fade -->
    <div x-show="!termsRead"
      style="position:absolute;bottom:0;left:0;right:0;height:2.5rem;background:linear-gradient(to top,#111,transparent);pointer-events:none"></div>
  </div>

</div>
