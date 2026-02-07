<?php
if (!defined('ABSPATH')) exit;
?>
<section id="listSectionDesktop"
  class="hidden lg:block lg:col-span-4 border-r bg-slate-50 overflow-auto">
  <div class="sticky top-0 bg-slate-50/90 backdrop-blur border-b z-10">
    <div class="p-3">
      <div class="text-sm text-slate-700 font-medium">
        แสดงแหล่งเรียนรู้ <span id="listCount" class="font-bold text-emerald-700">0</span> สถานที่
      </div>
      <div id="activeFilters" class="mt-2 flex flex-wrap gap-2"></div>
    </div>
  </div>

  <div class="p-3 space-y-3">
    <div id="list" class="space-y-3"></div>

    <button id="btnLoadMoreDesktop"
      class="hidden w-full px-4 py-3 rounded-xl border bg-white hover:bg-slate-50 font-semibold text-sm">
      โหลดเพิ่มอีก 10 รายการ
    </button>

    <div id="loadMoreHintDesktop" class="hidden text-xs text-slate-500 text-center"></div>
  </div>
</section>

<section id="listSectionMobile"
  class="lg:hidden hidden bg-slate-50 overflow-auto h-full">
  <div class="sticky top-0 bg-slate-50/90 backdrop-blur border-b z-10">
    <div class="p-3">
      <div class="text-sm text-slate-700 font-medium">
        แสดงแหล่งเรียนรู้ <span id="listCountMobile" class="font-bold text-emerald-700">0</span> สถานที่
      </div>
      <div id="activeFiltersMobile" class="mt-2 flex flex-wrap gap-2"></div>
    </div>
  </div>

  <div class="p-3 space-y-3">
    <div id="listMobile" class="space-y-3"></div>

    <button id="btnLoadMoreMobile"
      class="hidden w-full px-4 py-3 rounded-xl border bg-white hover:bg-slate-50 font-semibold text-sm">
      โหลดเพิ่มอีก 10 รายการ
    </button>

    <div id="loadMoreHintMobile" class="hidden text-xs text-slate-500 text-center"></div>
  </div>
</section>
