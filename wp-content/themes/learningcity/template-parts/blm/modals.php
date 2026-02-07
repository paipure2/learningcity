<?php
if (!defined('ABSPATH')) exit;
?>
<div id="catModal" class="hidden fixed inset-0 z-999999">
  <div class="absolute inset-0 bg-black/40"></div>

  <div class="absolute inset-x-4 top-16 bottom-16 bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
    <div class="p-4 border-b flex items-center justify-between">
      <div class="font-bold">เลือกประเภทสถานที่</div>
      <button id="closeCatModal" class="text-lg">✕</button>
    </div>

    <div class="p-4 overflow-auto">
      <input id="catSearch"
             class="w-full rounded-xl border px-3 py-2 mb-3"
             placeholder="ค้นหาประเภท... (เช่น ห้องสมุด, ศิลปะ)" />
      <div class="text-xs text-slate-500 mb-3">*เลือกได้หลายประเภท</div>
      <div id="catModalGrid" class="grid grid-cols-2 gap-3"></div>
    </div>

    <div class="p-4 border-t bg-slate-50 flex items-center justify-between">
      <button id="btnClearCats2" class="text-sm underline text-slate-600">ล้างประเภท</button>
      <button id="btnApplyCats" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
        ใช้ตัวกรอง
      </button>
    </div>
  </div>
</div>


<div id="facilityModal" class="hidden fixed inset-0 z-999999">
  <div class="absolute inset-0 bg-black/40"></div>

  <div class="absolute inset-x-4 top-16 bottom-16 bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
    <div class="p-4 border-b flex items-center justify-between">
      <div class="font-bold">เลือกสิ่งอำนวยความสะดวก</div>
      <button id="closeFacilityModal" class="text-lg">✕</button>
    </div>

    <div class="p-4 overflow-auto">
      <input id="facilitySearch"
             class="w-full rounded-xl border px-3 py-2 mb-3"
             placeholder="ค้นหาสิ่งอำนวยฯ..." />
      <div class="text-xs text-slate-500 mb-3">*เลือกได้หลายอัน (ต้องมีครบทุกอัน)</div>
      <div id="facilityModalGrid" class="flex flex-wrap gap-2"></div>
    </div>

    <div class="p-4 border-t bg-slate-50 flex items-center justify-between">
      <button id="btnClearFacilities2" class="text-sm underline text-slate-600">ล้างสิ่งอำนวยฯ</button>
      <button id="btnApplyFacilities" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
        ใช้ตัวกรอง
      </button>
    </div>
  </div>
</div>


<div id="courseCatModal" class="hidden fixed inset-0 z-999999">
  <div class="absolute inset-0 bg-black/40"></div>

  <div class="absolute inset-x-4 top-16 bottom-16 bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
    <div class="p-4 border-b flex items-center justify-between">
      <div class="font-bold">เลือกหมวดคอร์ส</div>
      <button id="closeCourseCatModal" class="text-lg">✕</button>
    </div>

    <div class="p-4 overflow-auto">
      <input id="courseCatSearch"
             class="w-full rounded-xl border px-3 py-2 mb-3"
             placeholder="ค้นหาหมวดคอร์ส..." />
      <div class="text-xs text-slate-500 mb-3">*เลือกได้หลายแบบ</div>
      <div id="courseCatModalGrid" class="flex flex-wrap gap-2"></div>
    </div>

    <div class="p-4 border-t bg-slate-50 flex items-center justify-between">
      <button id="btnClearCourseCats2" class="text-sm underline text-slate-600">ล้างหมวด</button>
      <button id="btnApplyCourseCats" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
        ใช้ตัวกรอง
      </button>
    </div>
  </div>
</div>


<div id="reportModal" class="hidden fixed inset-0 z-999999">
  <div class="absolute inset-0 bg-black/40"></div>

  <div class="absolute inset-x-4 top-16 bottom-16 bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
    <div class="p-4 border-b flex items-center justify-between">
      <div class="font-bold">แจ้งแก้ไขข้อมูลสถานที่</div>
      <button id="closeReportModal" class="text-lg">✕</button>
    </div>

    <form id="reportForm" class="p-4 overflow-auto space-y-4">
      <div class="text-sm text-slate-600">โปรดระบุข้อมูลที่ไม่ถูกต้องเพื่อให้ทีมงานแก้ไขได้เร็วขึ้น</div>

      <div>
        <div class="text-sm font-semibold mb-2">เรื่องที่ต้องการแก้ไข</div>
        <div class="grid grid-cols-2 gap-2 text-sm">
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="report_topics[]" value="address" class="rounded">
            ที่อยู่
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="report_topics[]" value="phone" class="rounded">
            เบอร์โทร
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="report_topics[]" value="hours" class="rounded">
            เวลาทำการ
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="report_topics[]" value="images" class="rounded">
            รูปภาพ
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="report_topics[]" value="links" class="rounded">
            ลิงก์
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="report_topics[]" value="other" class="rounded">
            อื่น ๆ
          </label>
        </div>
      </div>

      <div>
        <label class="text-sm font-semibold">รายละเอียดเพิ่มเติม</label>
        <textarea id="reportDetails" name="report_details" rows="4"
                  class="mt-2 w-full rounded-xl border px-3 py-2 text-sm"
                  placeholder="เช่น เบอร์โทรควรเป็น 02-xxx-xxxx หรือเวลาทำการเป็น 09:00-17:00"></textarea>
      </div>

      <div class="hidden" aria-hidden="true">
        <label>Leave this empty</label>
        <input type="text" id="reportWebsite" name="report_website" tabindex="-1" autocomplete="off">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-semibold">ชื่อผู้แจ้ง (ไม่บังคับ)</label>
          <input id="reportName" name="report_name"
                 class="mt-2 w-full rounded-xl border px-3 py-2 text-sm" placeholder="ชื่อ">
        </div>
        <div>
          <label class="text-sm font-semibold">อีเมล/เบอร์ (ไม่บังคับ)</label>
          <input id="reportContact" name="report_contact"
                 class="mt-2 w-full rounded-xl border px-3 py-2 text-sm" placeholder="อีเมลหรือเบอร์โทร">
        </div>
      </div>

      <div id="reportError" class="hidden text-sm text-rose-600"></div>
      <div id="reportSuccess" class="hidden text-sm text-emerald-700"></div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" id="cancelReport"
                class="px-4 py-2 rounded-xl border text-sm font-semibold text-slate-600 hover:bg-slate-50">
          ยกเลิก
        </button>
        <button type="submit" id="submitReport"
                class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
          ส่งรายงาน
        </button>
      </div>
    </form>
  </div>
</div>
