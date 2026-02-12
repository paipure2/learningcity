<?php
if (!defined('ABSPATH')) exit;
?>
<div id="mobileTopbar" class="lg:hidden sticky top-0 z-40 bg-white border-b">
  <div class="px-3 py-2 flex items-center gap-2">
    <button id="btnOpenFiltersMobile"
      class="px-3 py-2 rounded-xl border bg-white text-sm font-semibold hover:bg-slate-50">
      ตัวกรอง <span id="filtersCount" class="text-emerald-700">0</span>
    </button>

    <div id="mobileTabsWrap" class="flex-1 flex items-center justify-center">
      <div class="inline-flex rounded-2xl border bg-slate-50 p-1">
        <button id="tabMap"
          class="px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 text-white">
          แผนที่
        </button>
        <button id="tabList"
          class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-white">
          รายการ
        </button>
      </div>
    </div>

    <button id="btnLocateMobile"
      class="px-3 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
      ตำแหน่ง
    </button>
  </div>
</div>
