<?php
if (!defined('ABSPATH')) exit;
?>
<div id="mobileTopbar" class="lg:hidden sticky top-0 z-40 bg-white border-b">
  <div class="px-3 py-2 flex items-center gap-2">
    <button id="btnOpenFiltersMobile"
      type="button"
      class="px-3 py-2 rounded-xl border bg-white text-sm font-semibold hover:bg-slate-50">
      ตัวกรอง <span id="filtersCount" class="text-emerald-700">0</span>
    </button>

    <div id="mobileQuickCatsWrap" class="min-w-0 flex-1">
      <div id="mobileQuickCats" aria-label="หมวดหมู่ด่วน"></div>
    </div>

    <div id="mobileTabsWrap" class="flex-1 flex items-center justify-center">
      <div class="inline-flex rounded-2xl border bg-slate-50 p-1">
        <button id="tabMap"
          type="button"
          class="px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 text-white">
          แผนที่
        </button>
        <button id="tabList"
          type="button"
          class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-white">
          รายการ
        </button>
      </div>
    </div>
  </div>
</div>

<button id="btnLocateMobile"
  type="button"
  class="lg:hidden blm-mobile-locate-fab"
  aria-label="ระบุตำแหน่ง">
  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M12 21s7-4.5 7-11a7 7 0 0 0-14 0c0 6.5 7 11 7 11z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="12" cy="10" r="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
</button>
