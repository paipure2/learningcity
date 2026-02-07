<?php
if (!defined('ABSPATH')) exit;
?>
<div id="apiLoading" class="hidden fixed inset-0 z-[999] bg-black/30">
  <div class="absolute inset-0 flex items-center justify-center">
    <div class="rounded-2xl bg-white px-5 py-4 shadow-xl border flex items-center gap-3">
      <div class="h-5 w-5 rounded-full border-2 border-slate-300 border-t-emerald-600 animate-spin"></div>
      <div class="text-sm font-semibold text-slate-700">กำลังโหลดข้อมูล...</div>
    </div>
  </div>
</div>
