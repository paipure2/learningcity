<?php
if (!defined('ABSPATH')) exit;
?>
<div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/40 z-[2147483643] lg:hidden"></div>

<aside id="sidebar"
  class="fixed inset-y-0 left-0 z-[2147483644] w-[88%] max-w-[380px] border-r overflow-auto
         -translate-x-full transition-transform duration-200
         lg:static lg:translate-x-0 lg:z-auto lg:w-auto lg:max-w-none lg:block">

  <div class="p-4 space-y-4 blm-sidebar-inner">

    <div class="lg:hidden flex items-center justify-between">
      <div class="font-bold text-slate-800">ตัวกรอง</div>
      <button id="btnCloseFiltersMobile"
        class="h-10 w-10 rounded-full bg-black text-white flex items-center justify-center">✕</button>
    </div>

    <div class="blm-heading">
      <h1 class="text-xl font-bold">
        <span class="blm-heading-black">ค้นหา</span><span class="blm-heading-green">แหล่งเรียนรู้</span><br>
        <span class="blm-heading-green">ใกล้บ้านคุณ</span>
      </h1>
    </div>

    <div class="rounded-xl border p-3 bg-white space-y-2 blm-location-box">
      <div class="blm-near-actions">
        <button id="btnLocate"
          class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 inline-flex items-center gap-2">
          <span aria-hidden="true" class="icon-20">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
              <path d="M536.5-503.5Q560-527 560-560t-23.5-56.5Q513-640 480-640t-56.5 23.5Q400-593 400-560t23.5 56.5Q447-480 480-480t56.5-23.5ZM480-186q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 106Q319-217 239.5-334.5T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 100-79.5 217.5T480-80Zm0-480Z"/>
            </svg>
          </span>
          ใช้ตำแหน่งปัจจุบัน
        </button>

        <div id="nearMeWrap" class="hidden">
          <label for="nearMeSwitch" class="blm-near-switch-wrap">
            <span id="nearMeSwitchLabel" class="blm-near-switch-label">ค้นหาใกล้ฉัน</span>
            <input id="nearMeSwitch" type="checkbox" class="sr-only">
            <span class="blm-ios-switch" aria-hidden="true"></span>
          </label>
        </div>
      </div>

      <div id="nearMeRadiusWrap" class="hidden">
        <div class="flex items-center justify-between gap-2 mt-2">
          <label class="text-xs text-slate-600">รัศมี (กม.)</label>
          <span id="radiusKmValue" class="text-sm font-semibold text-slate-800">5 กม.</span>
        </div>
        <input id="radiusKm" type="range" min="1" max="20" step="1" value="5"
               class="w-full mt-2"/>
      </div>

      <div id="locStatus" class="text-xs text-slate-500">ใช้ตำแหน่งปัจจุบัน</div>
    </div>


    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="text-sm font-semibold">ประเภทสถานที่</label>
      </div>

      <div id="catGrid" class="grid grid-cols-2 gap-2"></div>
    </div>

    <div class="space-y-2">
      <label class="text-sm font-semibold">เขต</label>
      <select id="district" class="w-full rounded-lg border px-3 py-2">
        <option value="">ทุกเขต</option>
      </select>
    </div>

    <div class="space-y-2">
      <label class="text-sm font-semibold">ช่วงวัย</label>
      <div id="ageRangeWrap" class="flex flex-wrap gap-2"></div>
    </div>

    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="text-sm font-semibold">สิ่งอำนวยความสะดวก</label>
        <button id="btnAllFacilities" class="blm-view-all">ดูทั้งหมด +</button>
      </div>

      <!-- TOP 10 pills -->
      <div id="facilityWrap" class="flex flex-wrap gap-2"></div>

    </div>

    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="text-sm font-semibold">ราคา</label>
      </div>
      <div id="admissionWrap" class="flex flex-wrap gap-2"></div>
    </div>

    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="text-sm font-semibold">สถานะคอร์ส</label>
      </div>
      <div id="courseCatWrap" class="flex flex-wrap gap-2"></div>
    </div>


    <div>
      <span id="count" class="hidden">0</span>
      <button id="reset" class="mt-2">
        ล้างตัวกรองทั้งหมด
      </button>
    </div>
  </div>
</aside>
