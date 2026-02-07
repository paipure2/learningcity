<?php
if (!defined('ABSPATH')) exit;
?>
<div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/40 z-40 lg:hidden"></div>

<aside id="sidebar"
  class="fixed inset-y-0 left-0 z-50 w-[88%] max-w-[380px] bg-white border-r overflow-auto
         -translate-x-full transition-transform duration-200
         lg:static lg:translate-x-0 lg:z-auto lg:w-auto lg:max-w-none lg:col-span-3 lg:block">

  <div class="p-4 space-y-4">

    <div class="lg:hidden flex items-center justify-between">
      <div class="font-bold text-slate-800">ตัวกรอง</div>
      <button id="btnCloseFiltersMobile"
        class="h-10 w-10 rounded-full bg-black text-white flex items-center justify-center">✕</button>
    </div>

    <div>
      <h1 class="text-xl font-bold">แผนที่แหล่งเรียนรู้ กทม.</h1>
      <p class="text-sm text-slate-500">MapLibre + JSON • สำรวจตามพื้นที่แผนที่</p>
    </div>

    <div class="rounded-xl border p-3 bg-white space-y-2">
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
        <div class="text-[11px] text-slate-500 mt-1">*กรองจากตำแหน่งของคุณ</div>
      </div>

      <div id="locStatus" class="text-xs text-slate-500">ยังไม่ได้รับตำแหน่ง</div>
    </div>


    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="text-sm font-semibold">ประเภทสถานที่</label>
        <button id="btnAllCats" class="text-xs text-emerald-700 underline">ดูทั้งหมด</button>
      </div>

      <div id="catGrid" class="grid grid-cols-2 gap-2"></div>

      <div class="flex items-center justify-between text-xs text-slate-500">
        <span>เลือกได้หลายประเภท</span>
        <button id="btnClearCats" class="underline">ล้างประเภท</button>
      </div>
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
      <div class="text-xs text-slate-500">ไม่เลือก = ทุกช่วงวัย</div>
    </div>

    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="text-sm font-semibold">สิ่งอำนวยความสะดวก</label>
        <button id="btnAllFacilities" class="text-xs text-emerald-700 underline">ดูทั้งหมด</button>
      </div>

      <!-- TOP 10 pills -->
      <div id="facilityWrap" class="flex flex-wrap gap-2"></div>

      <div class="flex items-center justify-between text-xs text-slate-500">
        <span>เลือกหลายอัน = ต้องมีครบทุกอัน</span>
        <button id="btnClearFacilities" class="underline">ล้างสิ่งอำนวยฯ</button>
      </div>
    </div>

    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="text-sm font-semibold">ราคา</label>
        <button id="btnClearAdmission" class="text-xs text-emerald-700 underline">ล้างราคา</button>
      </div>
      <div id="admissionWrap" class="flex flex-wrap gap-2"></div>
      <div class="text-xs text-slate-500">เลือกได้หลายแบบ</div>
    </div>

    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="text-sm font-semibold">หมวดคอร์ส</label>
        <button id="btnAllCourseCats" class="text-xs text-emerald-700 underline">ดูทั้งหมด</button>
      </div>
      <div id="courseCatWrap" class="flex flex-wrap gap-2"></div>
      <div class="flex items-center justify-between text-xs text-slate-500">
        <span>เลือกได้หลายแบบ</span>
        <button id="btnClearCourseCat" class="underline">ล้างหมวด</button>
      </div>
    </div>


    <div class="rounded-xl border p-3 bg-white">
      <div class="flex items-center justify-between">
        <div class="text-xs text-slate-500">สถานที่ในกรอบแผนที่</div>
        <div class="text-lg font-bold"><span id="count">0</span> แห่ง</div>
      </div>
      <button id="reset"
              class="mt-2 w-full px-3 py-2 rounded-lg border hover:bg-slate-50">
        ล้างตัวกรองทั้งหมด
      </button>
    </div>

    <div class="text-xs text-slate-500">
      เลื่อน/ซูมแผนที่ → รายการ + จำนวน จะอัปเดตอัตโนมัติ
    </div>
  </div>
</aside>
