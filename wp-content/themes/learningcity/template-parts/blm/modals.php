<?php
if (!defined('ABSPATH')) exit;
$lc_photo_upload_config = apply_filters('lc_public_photo_upload_config', [
  'enabled' => false,
  'max_files' => 6,
  'max_file_size_mb' => 8,
]);
?>
<div id="catModal" class="hidden fixed inset-0 z-999999">
  <div class="absolute inset-0 bg-black/40" data-modal-backdrop="1"></div>

  <div class="blm-modal-panel absolute inset-x-4 top-16 bottom-16 bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
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

<div id="welcomeModal" class="hidden fixed inset-0 z-999999">
  <div class="absolute inset-0 bg-black/45" data-modal-backdrop="1"></div>

  <div class="blm-modal-panel blm-welcome-panel absolute inset-x-4 bg-white rounded-2xl shadow-xl overflow-hidden">
    <div class="p-5 sm:p-6">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="text-xl font-bold text-slate-900">ยินดีต้อนรับสู่แผนที่แหล่งเรียนรู้</h3>
          <p class="mt-2 text-sm text-slate-600">หน้านี้ช่วยให้คุณค้นหาสถานที่เรียนรู้ใกล้บ้าน และดูรายละเอียดได้ในที่เดียว</p>
        </div>
        <button id="closeWelcomeModal" class="text-lg text-slate-500 hover:text-slate-700" aria-label="ปิด">✕</button>
      </div>

      <div class="mt-4 grid gap-2 text-sm text-slate-700">
        <div>1. ใช้ตัวกรองเพื่อเลือกประเภทสถานที่ เขต ช่วงวัย และสิ่งอำนวยความสะดวก</div>
        <div>2. คลิกหมุดหรือชื่อสถานที่บนแผนที่เพื่อเปิดรายละเอียด</div>
        <div>3. คัดลอกลิงก์สถานที่เพื่อแชร์ให้ผู้อื่นได้ทันที</div>
      </div>

      <div class="mt-6 flex justify-end">
        <button id="btnWelcomeStart"
                class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
          เริ่มใช้งาน
        </button>
      </div>
    </div>
  </div>
</div>


<div id="facilityModal" class="hidden fixed inset-0 z-999999">
  <div class="absolute inset-0 bg-black/40" data-modal-backdrop="1"></div>

  <div class="blm-modal-panel absolute inset-x-4 top-16 bottom-16 bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
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
  <div class="absolute inset-0 bg-black/40" data-modal-backdrop="1"></div>

  <div class="blm-modal-panel absolute inset-x-4 top-16 bottom-16 bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
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

<div id="photoUploadModal" class="hidden fixed inset-0 z-999999">
  <div class="absolute inset-0 bg-black/40" data-modal-backdrop="1"></div>

  <div class="blm-modal-panel absolute inset-x-4 top-16 bottom-16 bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
    <div class="p-4 border-b flex items-center justify-between">
      <div class="font-bold">อัปโหลดรูปสถานที่</div>
      <button id="closePhotoUploadModal" class="text-lg">✕</button>
    </div>

    <form id="photoUploadForm" class="p-4 overflow-auto space-y-4" enctype="multipart/form-data">
      <div class="text-sm text-slate-600">รูปที่ส่งเข้ามาจะอยู่ในสถานะรอตรวจสอบก่อนแสดงผลบนเว็บไซต์</div>

      <div>
        <label class="text-sm font-semibold">สถานที่</label>
        <input id="photoUploadPlaceName" type="text" class="mt-2 w-full rounded-xl border px-3 py-2 text-sm bg-slate-100" readonly>
      </div>

      <div>
        <label class="text-sm font-semibold">ชื่อผู้ส่ง *</label>
        <input id="photoUploaderName" name="uploader_name"
               class="mt-2 w-full rounded-xl border px-3 py-2 text-sm"
               maxlength="120" required placeholder="กรอกชื่อ">
      </div>

      <div>
        <label class="text-sm font-semibold">อีเมลผู้ส่ง *</label>
        <input id="photoUploaderEmail" name="uploader_email" type="email"
               class="mt-2 w-full rounded-xl border px-3 py-2 text-sm"
               maxlength="190" required placeholder="name@example.com">
      </div>

      <div>
        <label class="text-sm font-semibold">อัปโหลดรูป *</label>
        <input id="photoUploadFiles" name="place_images[]" type="file"
               accept="image/jpeg,image/png,image/webp"
               class="mt-2 w-full rounded-xl border px-3 py-2 text-sm"
               multiple required>
        <div id="photoUploadHelp" class="mt-2 text-xs text-slate-500"></div>
      </div>

      <div class="hidden" aria-hidden="true">
        <label>Leave this empty</label>
        <input type="text" id="photoUploadWebsite" name="photo_upload_website" tabindex="-1" autocomplete="off">
      </div>

      <div id="photoUploadError" class="hidden text-sm text-rose-600"></div>
      <div id="photoUploadSuccess" class="hidden text-sm text-emerald-700"></div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" id="cancelPhotoUpload"
                class="px-4 py-2 rounded-xl border text-sm font-semibold text-slate-600 hover:bg-slate-50">
          ยกเลิก
        </button>
        <button type="submit" id="submitPhotoUpload"
                class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
          ส่งรูป
        </button>
      </div>
    </form>
  </div>
</div>
