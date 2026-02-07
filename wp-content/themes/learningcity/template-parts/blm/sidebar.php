<?php
if (!defined('ABSPATH')) exit;
?>
<div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/40 z-40 lg:hidden"></div>

<aside id="sidebar"
  class="fixed inset-y-0 left-0 z-50 w-[88%] max-w-[380px] border-r overflow-auto
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
      <div class="flex items-center justify-between gap-2">
        <button id="btnLocate"
          class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
          ใช้ตำแหน่งปัจจุบัน
        </button>

        <div id="nearMeWrap" class="hidden">
          <button id="chipNearMe"
            class="px-3 py-2 rounded-lg border text-sm font-semibold hover:bg-slate-50">
            ใกล้ฉัน: ปิด
          </button>
        </div>
      </div>

      <div id="nearMeRadiusWrap" class="hidden">
        <div class="flex items-center justify-between gap-2 mt-2">
          <label class="text-xs text-slate-600">รัศมี (กม.)</label>
          <input id="radiusKm" type="number" min="0.1" step="0.1" value="5"
                 class="w-24 rounded-lg border px-2 py-1 text-sm text-right"/>
        </div>
      </div>

      <div id="locStatus" class="text-xs text-slate-500">ใช้ตำแหน่งปัจจุบัน</div>
    </div>


    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="text-sm font-semibold">ประเภทสถานที่</label>
        <button id="btnAllCats" class="blm-view-all">ดูทั้งหมด +</button>
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
        <label class="text-sm font-semibold">หมวดคอร์ส</label>
        <button id="btnAllCourseCats" class="blm-view-all">ดูทั้งหมด +</button>
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
