<?php
if (!defined('ABSPATH')) exit;
?>
<main id="mapSection" class="relative h-full">
  <div id="map"></div>

  <!-- SEARCH OVERLAY (NEW) -->
  <div id="searchOverlay"
    class="absolute z-40 left-3 right-3 bottom-3
          lg:left-auto lg:right-4 lg:bottom-auto lg:top-[18px] lg:w-[297px]">

    <div id="searchBox" class="bg-white/95 backdrop-blur rounded-[8px] border shadow-sm p-0">
      <div class="relative">
        <label class="text-sm font-semibold">ค้นหา</label>
        <input id="q"
              class="w-full rounded-lg border px-3 py-2"
              placeholder="พิมพ์ชื่อ/ที่อยู่/เขต..."
              autocomplete="off"/>

        <div id="searchStatus"
            class="hidden rounded-[8px] border p-2 text-xs">
          กำลังค้นหา: <span id="searchText" class="font-semibold"></span>
          <button id="btnClearSearch" class="ml-2 underline font-semibold">ล้างค้นหา</button>
        </div>

        <div id="searchPanel"
            class="hidden absolute z-50 left-0 right-0 mt-2 bg-white border rounded-xl shadow-lg overflow-hidden">
          <div class="search-panel-head px-3 py-2 text-xs text-slate-500 border-b">
            ผลการค้นหา
          </div>
          <div id="searchResults" class="max-h-[252px] overflow-auto"></div>
          <div class="search-panel-foot px-3 py-2 text-xs text-slate-500 border-t bg-slate-50">
            แสดงสูงสุด 8 รายการ
          </div>
        </div>
      </div>
    </div>
  </div>


  <aside id="drawer" class="hidden fixed top-0 h-full z-[70] overflow-hidden
       w-full sm:w-[560px] right-0
       lg:left-[25vw] lg:w-[33.3333vw] lg:right-auto">
    <div id="drawerPanel" class="relative h-full bg-[#f2f2f2]">
      <div id="drawerGrabberWrap" class="blm-drawer-grabber-wrap">
        <button id="drawerGrabber" class="blm-drawer-grabber" aria-label="ลากเพื่อย่อหรือปิดรายละเอียด"></button>
      </div>

      <div id="drawerLoading" class="hidden absolute inset-0 z-20 bg-white/65 backdrop-blur-[1px]">
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="rounded-2xl bg-white px-5 py-4 shadow-xl border flex items-center gap-3">
            <div class="h-5 w-5 rounded-full border-2 border-slate-300 border-t-emerald-600 animate-spin"></div>
            <div class="text-sm font-semibold text-slate-700">กำลังโหลดรายละเอียด...</div>
          </div>
        </div>
      </div>

      <div class="h-full flex flex-col">
        <div id="drawerHero" class="relative bg-[linear-gradient(180deg,#D1F9EB_0%,#E1FFD8_91.42%)]">
          <div class="relative z-10 flex flex-col gap-[11px] px-[24px] pt-[15px] pb-[15px]">
            <div class="flex items-center justify-between gap-2">
              <button id="drawerClose"
                class="inline-flex h-[30px] w-fit items-center gap-px rounded-full bg-white px-[9px] shadow-[0_2px_4px_rgba(0,0,0,0.25)]">
                <span class="text-[18px] leading-none">←</span>
                <span class="text-[14px] font-semibold">ย้อนกลับ</span>
              </button>
              <div class="flex items-center gap-2">
                <span id="copyLinkToast" class="blm-copy-toast" aria-live="polite">Copy link แล้ว</span>
                <button id="btnSharePlace" type="button"
                  class="blm-share-btn inline-flex h-[30px] w-[30px] items-center justify-center rounded-full bg-white shadow-[0_2px_4px_rgba(0,0,0,0.25)]"
                  aria-label="คัดลอกลิงก์สถานที่นี้"
                  title="คัดลอกลิงก์">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-[16px] w-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10 13a5 5 0 0 0 7.54.54l2-2a5 5 0 0 0-7.07-7.07l-1.12 1.12"></path>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-2 2a5 5 0 0 0 7.07 7.07l1.12-1.12"></path>
                  </svg>
                </button>
              </div>
            </div>

            <div class="flex flex-col gap-[10px]">
              <h2 id="dTitle" class="blm-drawer-title text-[24px] leading-[1.4] text-black"></h2>
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-[10px]">
                  <span id="dIcon"
                    class="inline-flex h-[35px] w-[35px] items-center justify-center rounded-[8px] text-white icon-20">
                  </span>
                  <div>
                    <div id="dCategory" class="text-[14px] font-normal leading-[1.2] text-black"></div>
                    <div id="dDistrict" class="text-[14px] font-normal leading-[1.2] text-black"></div>
                  </div>
                </div>
                <div id="dDistance" class="blm-distance-box">
                  <span class="lbl">ห่างจากคุณ</span>
                  <span class="val">-</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div id="drawerBody" class="flex-1 overflow-auto bg-white px-[23px] py-[17px]">
          <div class="flex flex-col gap-[18px]">
            <div id="rowImages">
              <div id="imgGrid" class="grid grid-cols-3 gap-[7px]"></div>
            </div>

            <div id="rowDesc">
              <div id="dDesc" class="blm-desc-text"></div>
              <button id="dDescMore" type="button" class="blm-readmore">อ่านทั้งหมด ▼</button>
            </div>

            <div class="blm-drawer-tabs">
              <button id="tabBtnDetails" class="tabBtn is-active" data-tab="details">รายละเอียด</button>
              <button id="tabBtnCourses" class="tabBtn" data-tab="courses">คอร์สเรียน (<span id="coursesCount">0</span>)</button>
            </div>

            <div id="tab-details" class="tabPanel space-y-4">
              <div id="rowAddress" class="blm-meta-row">
                <div class="blm-meta-icon blm-meta-icon-svg" aria-hidden="true"><span id="iconAddress"></span></div>
                <div class="flex-1">
                  <div id="dAddress" class="text-[14px] leading-[1.45] text-black"></div>
                </div>
              </div>

              <div id="rowGmaps" class="blm-meta-row">
                <div class="blm-meta-icon blm-meta-icon-svg" aria-hidden="true"><span id="iconGmaps"></span></div>
                <div class="flex-1">
                  <a id="dGmaps" class="blm-gmaps-btn" target="_blank" rel="noreferrer">เปิด Google Maps ↗</a>
                </div>
              </div>

              <div id="rowPhone" class="blm-meta-row">
                <div class="blm-meta-icon blm-meta-icon-svg" aria-hidden="true"><span id="iconPhone"></span></div>
                <div class="flex-1">
                  <div id="dPhone" class="text-[14px] text-black"></div>
                </div>
              </div>

              <div id="rowHours" class="blm-meta-row">
                <div class="blm-meta-icon blm-meta-icon-svg" aria-hidden="true"><span id="iconHours"></span></div>
                <div class="flex-1">
                  <div id="dHours" class="text-[14px] whitespace-pre-line text-black"></div>
                </div>
              </div>

              <div id="rowAdmission" class="blm-meta-row">
                <div class="blm-meta-icon blm-meta-icon-svg" aria-hidden="true"><span id="iconAdmission"></span></div>
                <div class="flex-1">
                  <div id="dAdmission" class="text-[14px] text-black"></div>
                </div>
              </div>

              <div id="rowTags" class="blm-meta-row">
                <div class="blm-meta-icon blm-meta-icon-svg" aria-hidden="true"><span id="iconTags"></span></div>
                <div class="flex-1">
                  <div id="dTags" class="flex flex-wrap gap-2"></div>
                </div>
              </div>

              <div id="rowAmenities" class="rounded-[12px] border border-black/10 bg-transparent p-4">
                <div class="text-[14px] font-semibold text-black/90">สิ่งอำนวยความสะดวก</div>
                <div id="dAmenities" class="mt-2 flex flex-wrap gap-2"></div>
              </div>

              <div id="rowFacebook" class="blm-meta-row">
                <div class="blm-meta-icon blm-meta-icon-svg" aria-hidden="true"><span id="iconFacebook"></span></div>
                <div class="flex-1">
                  <a id="dFacebook" class="text-[14px] text-[#00744b] underline" target="_blank" rel="noreferrer">Facebook ↗</a>
                </div>
              </div>

              <div id="rowReportIssue" class="hidden pt-1">
                <button id="btnReportIssue"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-500 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">
                  แจ้งแก้ไขข้อมูล
                </button>
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
