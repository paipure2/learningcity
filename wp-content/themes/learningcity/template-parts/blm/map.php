<?php
if (!defined('ABSPATH')) exit;
?>
<main id="mapSection" class="lg:col-span-5 relative h-full">
  <div id="map"></div>

  <!-- SEARCH OVERLAY (NEW) -->
  <div id="searchOverlay"
    class="absolute z-40 left-3 right-3 bottom-3
          lg:left-auto lg:right-4 lg:bottom-auto lg:top-4 lg:w-[360px]">

    <div id="searchBox" class="bg-white/95 backdrop-blur rounded-2xl border shadow-sm p-3">
      <div class="space-y-2 relative">
        <label class="text-sm font-semibold">ค้นหา</label>
        <input id="q"
              class="w-full rounded-lg border px-3 py-2"
              placeholder="พิมพ์ชื่อ/ที่อยู่/เขต..."
              autocomplete="off"/>

        <div id="searchStatus"
            class="hidden rounded-xl border bg-emerald-50 p-3 text-xs text-emerald-900">
          กำลังค้นหา: <span id="searchText" class="font-semibold"></span>
          <button id="btnClearSearch" class="ml-2 underline font-semibold">ล้างค้นหา</button>
        </div>

        <div id="searchPanel"
            class="hidden absolute z-50 left-0 right-0 mt-2 bg-white border rounded-xl shadow-lg overflow-hidden">
          <div class="px-3 py-2 text-xs text-slate-500 border-b">
            ผลการค้นหา (คลิกเพื่อไปยังสถานที่)
          </div>
          <div id="searchResults" class="max-h-72 overflow-auto"></div>
          <div class="px-3 py-2 text-xs text-slate-500 border-t bg-slate-50">
            แสดงสูงสุด 8 รายการ • กด ESC เพื่อปิด
          </div>
        </div>
      </div>
    </div>
  </div>


  <aside id="drawer" class="hidden fixed top-0 h-full bg-white z-[70] overflow-hidden
       w-full sm:w-[560px] right-0
       lg:left-[25vw] lg:w-[33.3333vw] lg:right-auto">
    <button id="drawerClose"
      class="absolute top-4 right-4 h-10 w-10 rounded-full bg-black text-white flex items-center justify-center">
      ✕
    </button>

    <div class="h-full flex flex-col">
      <div class="px-6 pt-8 pb-4 border-b">
        <h2 id="dTitle" class="text-2xl font-extrabold leading-tight"></h2>

        <div class="mt-3 flex items-center gap-3 text-sm text-slate-600">
          <div class="inline-flex items-center gap-2">
            <span id="dIcon"
              class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 icon-24">
            </span>
            <div>
              <div id="dDistrict" class="text-slate-700 font-medium"></div>
              <div id="dCategory" class="text-slate-500 text-xs"></div>
            </div>
          </div>

          <div class="ml-auto text-emerald-700 font-semibold" id="dDistance">-</div>
        </div>
      </div>

      <div class="flex-1 overflow-auto">
        <div id="rowImages" class="px-6 pt-5">
          <div id="imgGrid" class="grid grid-cols-3 gap-2"></div>
        </div>

        <div class="px-6 pt-5">
          <div class="flex bg-slate-100 rounded-full p-1">
            <button id="tabBtnDetails" class="tabBtn flex-1 py-2 rounded-full text-sm font-semibold" data-tab="details">รายละเอียด</button>
            <button id="tabBtnCourses" class="tabBtn flex-1 py-2 rounded-full text-sm font-semibold" data-tab="courses">คอร์สเรียน (<span id="coursesCount">0</span>)</button>
          </div>
        </div>

        <div class="px-6 py-5">
          <div id="tab-details" class="tabPanel space-y-4">
            <div id="rowAddress" class="flex gap-3">
              <div class="mt-1 text-emerald-700">📍</div>
              <div class="flex-1">
                <div id="dAddress" class="text-sm text-slate-800"></div>
              </div>
            </div>
            <div id="rowGmaps" class="flex gap-3">
              <div class="mt-1 text-emerald-700">🗺️</div>
              <div class="flex-1">
                <a id="dGmaps" class="text-sm text-emerald-700 underline" target="_blank" rel="noreferrer">เปิด Google Maps ↗</a>
              </div>
            </div>

            <div id="rowPhone" class="flex gap-3">
              <div class="mt-1 text-emerald-700">📞</div>
              <div class="flex-1">
                <div id="dPhone" class="text-sm text-slate-800"></div>
              </div>
            </div>

            <div id="rowHours" class="flex gap-3">
              <div class="mt-1 text-emerald-700">🕒</div>
              <div class="flex-1">
                <div id="dHours" class="text-sm text-slate-800 whitespace-pre-line"></div>
              </div>
            </div>

            <div id="rowAdmission" class="flex gap-3">
              <div class="mt-1 text-emerald-700">💰</div>
              <div class="flex-1">
                <div id="dAdmission" class="text-sm text-slate-800"></div>
              </div>
            </div>

            <div id="rowTags" class="flex gap-3">
              <div class="mt-1 text-emerald-700">🏷️</div>
              <div class="flex-1">
                <div id="dTags" class="flex flex-wrap gap-2"></div>
              </div>
            </div>

            <div id="rowDesc" class="flex gap-3">
              <div class="mt-1 text-emerald-700">ℹ️</div>
              <div class="flex-1">
                <div id="dDesc" class="text-sm text-slate-600"></div>
              </div>
            </div>

            <div id="rowFacebook" class="flex gap-3">
              <div class="mt-1 text-emerald-700">🔗</div>
              <div class="flex-1">
                <a id="dFacebook" class="text-sm text-emerald-700 underline" target="_blank" rel="noreferrer">Facebook ↗</a>
              </div>
            </div>

            <div id="rowAmenities" class="flex gap-3">
              <div class="mt-1 text-emerald-700">✨</div>
              <div class="flex-1">
                <div class="text-sm text-slate-600">สิ่งอำนวยความสะดวก</div>
                <div id="dAmenities" class="flex flex-wrap gap-2 mt-2"></div>
              </div>
            </div>

            <div class="pt-2">
              <button id="btnReportIssue"
                      class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-xl border bg-white hover:bg-slate-50">
                แจ้งแก้ไขข้อมูล
              </button>
              <div class="text-xs text-slate-500 mt-1">พบข้อมูลไม่ถูกต้อง? ช่วยเราปรับให้ถูกต้องได้เลย</div>
            </div>
          </div>

          <div id="tab-courses" class="tabPanel hidden space-y-3">
            <div class="text-sm text-slate-600">คอร์สเรียน</div>
            <div id="coursesSearchWrap" class="hidden">
              <input id="coursesSearch"
                     class="w-full rounded-xl border px-3 py-2 text-sm"
                     placeholder="ค้นหาชื่อคอร์ส..." />
            </div>
              <div id="dCourses" class="space-y-2"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </aside>
</main>
