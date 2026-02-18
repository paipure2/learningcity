<?php
if (!defined('ABSPATH')) exit;

$lc_global_edit_nonce = wp_create_nonce('lc_location_edit_access');
$lc_global_edit_ajax_url = admin_url('admin-ajax.php');
$lc_global_has_session = !empty($_COOKIE['lc_loc_edit_token']);
?>
<div id="lcGlobalLoginModal" class="hidden fixed inset-0" style="z-index:2147483647;">
    <div class="absolute inset-0 bg-[#0b1726]/55 backdrop-blur-[2px]" data-global-login-backdrop="1"></div>
    <div class="absolute inset-x-4 top-16 md:top-24 bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-w-[560px] mx-auto border border-[#d8e2ec]">
        <div class="px-5 py-4 border-b border-[#e8eef5] flex items-center justify-between bg-[#f8fbff]">
            <div>
                <div class="text-[11px] font-semibold tracking-[0.08em] text-[#00744b] uppercase">สิทธิ์ผู้แก้ไข</div>
                <div class="font-bold text-[20px] leading-tight text-[#132239]">เข้าสู่ระบบแก้ไขข้อมูลสถานที่</div>
            </div>
            <button id="lcGlobalLoginClose" class="text-xl text-slate-500 hover:text-slate-700" type="button" aria-label="ปิด">✕</button>
        </div>
        <div class="p-5 space-y-4">
            <div class="text-sm text-slate-600">กรอกอีเมลที่ได้รับสิทธิ์ ระบบจะส่ง OTP ไปทางอีเมลเพื่อเข้าสู่หน้าส่งคำขอแก้ไขข้อมูล</div>
            <div>
                <label class="text-sm font-semibold text-[#1f2f46]">อีเมล</label>
                <input id="lcGlobalLoginEmail" type="email" class="mt-2 w-full rounded-xl border border-[#cfd9e5] px-3 py-2.5 text-sm focus:border-[#00744b] focus:ring-0" placeholder="กรอกอีเมลของคุณ">
            </div>
            <div id="lcGlobalLoginOtpWrap" class="hidden">
                <label class="text-sm font-semibold text-[#1f2f46]">OTP</label>
                <input id="lcGlobalLoginOtp" inputmode="numeric" maxlength="6" class="mt-2 w-full rounded-xl border border-[#cfd9e5] px-3 py-2.5 text-sm tracking-[0.15em] focus:border-[#00744b] focus:ring-0" placeholder="กรอกรหัส 6 หลัก">
            </div>
            <div id="lcGlobalLoginError" class="hidden text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2"></div>
            <div id="lcGlobalLoginSuccess" class="hidden text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2"></div>
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" id="lcGlobalLoginCancel" class="px-4 py-2 rounded-xl border border-[#d3dbe7] text-sm font-semibold text-slate-600 hover:bg-slate-50">ยกเลิก</button>
                <button type="button" id="lcGlobalLoginRequestOtp" class="px-4 py-2 rounded-xl bg-[#00744b] text-white text-sm font-semibold hover:bg-[#006642]">ส่ง OTP</button>
                <button type="button" id="lcGlobalLoginVerifyOtp" class="hidden px-4 py-2 rounded-xl bg-[#00744b] text-white text-sm font-semibold hover:bg-[#006642]">ยืนยัน OTP</button>
            </div>
        </div>
    </div>
</div>
<button
  id="lcStatusFabBtn"
  type="button"
  class="hidden fixed right-4 bottom-4 rounded-full bg-[#00744b] text-white px-4 py-3 text-sm font-semibold shadow-[0_10px_28px_rgba(0,116,75,0.35)] hover:bg-[#006642]"
  style="z-index:2147483646;"
>
  Editor Panel
</button>

<div id="lcStatusModal" class="hidden fixed inset-0" style="z-index:2147483647;">
  <div class="absolute inset-0 bg-[#0b1726]/55 backdrop-blur-[2px]" data-status-backdrop="1"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col w-full max-w-[760px] border border-[#d8e2ec]" style="height:min(760px, calc(100vh - 32px));">
    <div class="px-5 py-4 border-b border-[#e8eef5] flex items-center justify-between bg-[#f8fbff]">
      <div>
        <div class="font-bold text-[20px] leading-tight text-[#132239]">Editor Panel</div>
        <div class="text-xs text-slate-500 mt-1" id="lcStatusRequesterEmail">ติดตามคำขอที่ส่งไปล่าสุด</div>
      </div>
      <div class="flex items-center gap-2">
        <button id="lcStatusLogoutBtn" type="button" class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 text-sm font-semibold hover:bg-rose-50">ออกจากระบบ</button>
        <button id="lcStatusModalClose" class="text-xl text-slate-500 hover:text-slate-700" type="button" aria-label="ปิด">✕</button>
      </div>
    </div>
    <div id="lcStatusFiltersWrap">
      <div class="px-4 py-3 border-b border-[#e8eef5] flex flex-col md:flex-row md:items-center gap-3 md:gap-4">
        <div class="flex-1 min-w-0">
          <div class="text-xs font-semibold tracking-[0.05em] text-slate-500 uppercase mb-2">สถานะ</div>
          <div class="flex gap-2 overflow-x-auto pb-1" id="lcStatusTabs">
            <button type="button" class="whitespace-nowrap px-3 py-1.5 rounded-full border text-sm font-semibold bg-[#00744b] text-white border-[#00744b]" data-status-tab="all">ทั้งหมด (0)</button>
            <button type="button" class="whitespace-nowrap px-3 py-1.5 rounded-full border text-sm font-semibold text-slate-700 border-slate-300" data-status-tab="pending">รอตรวจสอบ (0)</button>
            <button type="button" class="whitespace-nowrap px-3 py-1.5 rounded-full border text-sm font-semibold text-slate-700 border-slate-300" data-status-tab="approved">อนุมัติ (0)</button>
            <button type="button" class="whitespace-nowrap px-3 py-1.5 rounded-full border text-sm font-semibold text-slate-700 border-slate-300" data-status-tab="rejected">ไม่อนุมัติ (0)</button>
          </div>
        </div>
        <div class="w-full md:w-[240px] md:flex-none">
          <div class="text-xs font-semibold tracking-[0.05em] text-slate-500 uppercase mb-2">ประเภท</div>
          <select id="lcStatusTypeSelect" class="w-full rounded-xl border border-[#cfd9e5] px-3 py-2.5 text-sm font-semibold text-slate-700 focus:border-[#00744b] focus:ring-0">
            <option value="all">ทุกประเภท (0)</option>
            <option value="location">สถานที่ (0)</option>
            <option value="course">คอร์ส (0)</option>
          </select>
        </div>
      </div>
    </div>
    <div class="p-4 overflow-auto flex-1 min-h-0" id="lcStatusBody" style="max-height:100%;">
      <div class="text-sm text-slate-500">กำลังโหลด...</div>
    </div>
    <div class="hidden p-4 overflow-auto flex-1 min-h-0" id="lcStatusDetailBody" style="max-height:100%;"></div>
  </div>
  </div>
</div>
<style>
  #lcCourseEditModal .lc-inline-tab{height:34px;padding:0 12px;border-radius:999px;border:1px solid #cfd9e5;background:#fff;color:#334155;font-weight:700;font-size:14px;cursor:pointer}
  #lcCourseEditModal .lc-inline-tab.is-active{background:#00744b;color:#fff;border-color:#00744b}
  #lcCourseEditModal .lc-course-panel{display:none}
  #lcCourseEditModal .lc-course-panel.is-active{display:block}
  #lcCourseEditModal .lc-inline-note{min-height:22px;font-size:13px}
  #lcCourseEditModal .lc-inline-note.ok{color:#166534}
  #lcCourseEditModal .lc-inline-note.err{color:#b91c1c}
  #lcCourseEditModal .lc-inline-btn{height:38px;padding:0 14px;border-radius:12px;border:1px solid #cfd9e5;background:#fff;color:#0f172a;font-weight:700;font-size:14px;cursor:pointer}
  #lcCourseEditModal .lc-inline-btn.primary{border-color:#00744b;background:#00744b;color:#fff}
  #lcCourseEditModal .lc-inline-success-screen{display:none;flex:1;align-items:center;justify-content:center;padding:24px}
  #lcCourseEditModal .lc-inline-success-box{text-align:center;max-width:540px}
  #lcCourseEditModal .lc-inline-success-title{font-size:30px;font-weight:800;color:#0f172a;line-height:1.2}
  #lcCourseEditModal .lc-inline-success-text{margin-top:10px;font-size:18px;color:#475569;line-height:1.5}
  #lcCourseEditModal .lc-inline-success-actions{margin-top:18px;display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
  #lcCourseEditModal .lc-course-card.is-success .lc-course-head,#lcCourseEditModal .lc-course-card.is-success .lc-course-body,#lcCourseEditModal .lc-course-card.is-success .lc-course-footer{display:none}
  #lcCourseEditModal .lc-course-card.is-success .lc-inline-success-screen{display:flex}
  #lcCourseEditModal .lc-inline-processing{position:absolute;inset:0;background:rgba(255,255,255,.82);display:none;align-items:center;justify-content:center;z-index:40}
  #lcCourseEditModal .lc-course-card.is-processing .lc-inline-processing{display:flex}
  #lcCourseEditModal .lc-inline-processing-box{display:grid;justify-items:center;gap:8px;padding:14px 16px;border:1px solid #dbe4ee;border-radius:12px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.12)}
  #lcCourseEditModal .lc-inline-processing-spinner{width:28px;height:28px;border:3px solid #dbe4ee;border-top-color:#00744b;border-radius:50%;animation:lcInlineSpin .85s linear infinite}
  #lcCourseEditModal .lc-inline-processing-text{font-size:14px;font-weight:700;color:#0f172a}
  @keyframes lcInlineSpin{to{transform:rotate(360deg)}}
  #lcCourseEditModal .lc-inline-img-item{position:relative;border:1px solid #dbe4ee;border-radius:10px;background:#fff;padding:5px}
  #lcCourseEditModal .lc-inline-img-item img{width:100%;height:76px;object-fit:cover;border-radius:6px;display:block}
  #lcCourseEditModal .lc-inline-img-rm{position:absolute;right:8px;top:8px;background:#fff;border:1px solid #fca5a5;color:#dc2626;border-radius:999px;font-size:11px;font-weight:700;padding:2px 8px;cursor:pointer}
  #lcCourseEditModal .lc-inline-img-item.is-remove{border-color:#dc2626;background:#fff1f2}
  #lcCourseEditModal .lc-inline-dropzone{border:1px dashed #9fb2c8;border-radius:12px;padding:12px;background:#f8fbff;text-align:center;cursor:pointer}
  #lcCourseEditModal .lc-inline-dropzone.is-drag{border-color:#00744b;background:#ecfdf5}
  #lcCourseEditModal .lc-inline-dropzone-title{font-size:14px;font-weight:800;color:#0f172a}
  #lcCourseEditModal .lc-inline-dropzone-sub{font-size:12px;color:#64748b;margin-top:4px}
  #lcCourseEditModal .lc-inline-files-meta{font-size:12px;color:#334155;margin-top:8px;word-break:break-word}
  #lcCourseEditModal .lc-inline-new-files{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:8px;margin-top:8px}
  #lcCourseEditModal .lc-inline-new-file{border:1px solid #dbe4ee;border-radius:10px;background:#fff;padding:6px;display:grid;gap:6px}
  #lcCourseEditModal .lc-inline-new-file-thumb{width:100%;height:86px;border-radius:7px;object-fit:cover;background:#f1f5f9}
  #lcCourseEditModal .lc-inline-new-file-meta{font-size:12px;color:#475569;line-height:1.35}
  #lcCourseEditModal .lc-inline-new-file-name{font-weight:700;color:#0f172a;word-break:break-word}
  #lcCourseEditModal .lc-inline-new-file-row{display:flex;align-items:center;justify-content:space-between;gap:6px}
  #lcCourseEditModal .lc-inline-new-file-remove{height:24px;padding:0 8px;border-radius:999px;border:1px solid #dc2626;background:#fff;color:#dc2626;font-size:11px;font-weight:700;cursor:pointer}
  #lcCourseEditModal .lc-inline-session-item{border:1px solid #dbe4ee;border-radius:10px;background:#fff;padding:0}
  #lcCourseEditModal .lc-inline-session-head{padding:10px 12px;cursor:pointer;font-weight:700;color:#0f172a;list-style:none}
  #lcCourseEditModal .lc-inline-session-head::-webkit-details-marker{display:none}
  #lcCourseEditModal .lc-inline-session-head-row{display:flex;align-items:center;justify-content:space-between;gap:8px}
  #lcCourseEditModal .lc-inline-session-name{font-weight:800}
  #lcCourseEditModal .lc-inline-session-id{font-size:90%;font-weight:600;color:#64748b}
  #lcCourseEditModal .lc-inline-session-body{padding:0 10px 10px}
  #lcCourseEditModal .lc-inline-session-remove{height:30px;padding:0 10px;border-radius:999px;border:1px solid #dc2626;background:#fff;color:#dc2626;font-size:12px;font-weight:700;cursor:pointer}
  #lcCourseEditModal .lc-inline-session-remove.toggle.is-active{background:#fee2e2}
  #lcCourseEditModal .lc-inline-session-item.is-remove{border-color:#dc2626;background:#fff1f2}
  #lcCourseEditModal .lc-inline-session-item.is-remove .lc-inline-session-body{display:none}
</style>
<div id="lcCourseEditModal" class="hidden fixed inset-0" style="z-index:2147483647;">
  <div class="absolute inset-0 bg-[#0b1726]/55 backdrop-blur-[2px]" data-course-edit-backdrop="1"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="lc-course-card relative bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col w-full max-w-[760px] border border-[#d8e2ec]" style="height:min(760px, calc(100vh - 32px));">
      <div class="lc-course-head px-5 py-4 border-b border-[#e8eef5] bg-[#f8fbff]">
        <div class="flex items-center justify-between gap-3">
          <div>
            <div class="font-bold text-[20px] leading-tight text-[#132239]">แจ้งแก้ไขข้อมูลคอร์ส</div>
            <div class="text-xs text-slate-500 mt-1" id="lcCourseEditModalSub">กำลังโหลดข้อมูลคอร์ส...</div>
          </div>
          <button id="lcCourseEditClose" class="text-xl text-slate-500 hover:text-slate-700" type="button" aria-label="ปิด">✕</button>
        </div>
        <div class="mt-3 flex flex-wrap gap-2" id="lcCourseEditTabs">
          <button type="button" class="lc-inline-tab is-active" data-course-tab="course">ข้อมูลคอร์ส</button>
          <button type="button" class="lc-inline-tab" data-course-tab="images">รูปภาพคอร์ส</button>
          <button type="button" class="lc-inline-tab" data-course-tab="sessions">ข้อมูล Session</button>
        </div>
      </div>
      <div class="lc-course-body p-4 overflow-auto flex-1 min-h-0">
        <section class="lc-course-panel is-active space-y-4" data-course-panel="course">
          <div>
            <label class="text-sm font-semibold text-[#1f2f46]">ชื่อคอร์ส</label>
            <input id="lcCourseEditTitle" type="text" class="mt-2 w-full rounded-xl border border-[#cfd9e5] px-3 py-2.5 text-sm focus:border-[#00744b] focus:ring-0" />
          </div>
          <div>
            <label class="text-sm font-semibold text-[#1f2f46]">คำอธิบายคอร์ส</label>
            <textarea id="lcCourseEditDescription" rows="5" class="mt-2 w-full rounded-xl border border-[#cfd9e5] px-3 py-2.5 text-sm focus:border-[#00744b] focus:ring-0"></textarea>
          </div>
          <div>
            <label class="text-sm font-semibold text-[#1f2f46]">ลิงก์สำหรับเข้าไปเรียน</label>
            <input id="lcCourseEditLearningLink" type="url" class="mt-2 w-full rounded-xl border border-[#cfd9e5] px-3 py-2.5 text-sm focus:border-[#00744b] focus:ring-0" />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-semibold text-[#1f2f46]">จำนวนนาทีที่เรียน</label>
              <input id="lcCourseEditTotalMinutes" type="number" min="0" step="1" class="mt-2 w-full rounded-xl border border-[#cfd9e5] px-3 py-2.5 text-sm focus:border-[#00744b] focus:ring-0" />
            </div>
            <div>
              <label class="text-sm font-semibold text-[#1f2f46]">ราคา</label>
              <input id="lcCourseEditPrice" type="number" min="0" step="0.01" class="mt-2 w-full rounded-xl border border-[#cfd9e5] px-3 py-2.5 text-sm focus:border-[#00744b] focus:ring-0" />
            </div>
          </div>
          <div>
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-[#1f2f46]">
              <input id="lcCourseEditHasCertificate" type="checkbox" class="rounded border-[#cfd9e5] text-[#00744b] focus:ring-[#00744b]">
              มีใบรับรองไหม
            </label>
          </div>
          <div>
            <label class="text-sm font-semibold text-[#1f2f46]">หมายเหตุเพิ่มเติมถึงแอดมิน</label>
            <textarea id="lcCourseEditNote" rows="4" class="mt-2 w-full rounded-xl border border-[#cfd9e5] px-3 py-2.5 text-sm focus:border-[#00744b] focus:ring-0" placeholder="ระบุรายละเอียดที่ต้องการแก้ไขเพิ่มเติม"></textarea>
          </div>
        </section>
        <section class="lc-course-panel space-y-4" data-course-panel="images">
          <div>
            <div class="text-sm font-semibold text-[#1f2f46]">รูปปัจจุบัน</div>
            <div id="lcCourseEditExistingImages" class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2"></div>
          </div>
          <div>
            <div class="text-sm font-semibold text-[#1f2f46]">เพิ่มรูปใหม่</div>
            <div id="lcCourseEditDropzone" class="lc-inline-dropzone mt-2" tabindex="0" role="button" aria-label="ลากและวางรูปภาพ หรือคลิกเพื่อเลือกไฟล์">
              <div class="lc-inline-dropzone-title">ลากและวางรูปที่นี่ หรือคลิกเพื่อเลือกไฟล์</div>
              <div class="lc-inline-dropzone-sub">รองรับ JPG / PNG / WebP</div>
              <div id="lcCourseEditNewImagesMeta" class="lc-inline-files-meta">ยังไม่ได้เลือกรูป</div>
            </div>
            <div id="lcCourseEditNewImagesPreview" class="lc-inline-new-files"></div>
            <input id="lcCourseEditNewImages" type="file" accept="image/*" multiple style="display:none;">
          </div>
        </section>
        <section class="lc-course-panel space-y-4" data-course-panel="sessions">
          <div class="flex items-center justify-between gap-2">
            <input id="lcCourseSessionSearch" type="text" class="w-full rounded-xl border border-[#cfd9e5] px-3 py-2.5 text-sm focus:border-[#00744b] focus:ring-0" placeholder="ค้นหา Session ด้วย keyword (ชื่อ/รายละเอียด)">
            <button type="button" id="lcCourseAddSessionToggle" class="lc-inline-btn whitespace-nowrap">เพิ่ม Session</button>
          </div>
          <div id="lcCourseAddSessionPanel" class="hidden border border-[#cfe0ff] border-dashed rounded-xl p-4 bg-[#f8fbff] space-y-3">
            <div class="text-sm font-semibold text-[#475569]">เพิ่ม Session ใหม่โดยเลือก Location</div>
            <div>
              <label class="text-xs font-semibold text-slate-600">เลือก Location</label>
              <select id="lcCourseAddSessionLocation" class="mt-1 w-full rounded-lg border border-[#cfd9e5] px-3 py-2 text-sm focus:border-[#00744b] focus:ring-0">
                <option value="">เลือก Location</option>
              </select>
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-600">ช่วงเวลาเรียน</label>
              <textarea id="lcCourseAddSessionTimePeriod" rows="2" class="mt-1 w-full rounded-lg border border-[#cfd9e5] px-3 py-2 text-sm focus:border-[#00744b] focus:ring-0"></textarea>
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-600">รายละเอียด Session</label>
              <textarea id="lcCourseAddSessionDetails" rows="3" class="mt-1 w-full rounded-lg border border-[#cfd9e5] px-3 py-2 text-sm focus:border-[#00744b] focus:ring-0"></textarea>
            </div>
            <div class="flex justify-end">
              <button type="button" id="lcCourseAddSessionSubmit" class="lc-inline-btn primary">เพิ่ม Session</button>
            </div>
          </div>
          <div id="lcCourseEditSessionList" class="mt-2 space-y-3"></div>
        </section>
      </div>
      <div class="lc-course-footer px-5 py-4 border-t border-[#e8eef5] bg-white">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <label class="inline-flex items-start gap-2 text-sm text-slate-700">
            <input id="lcCourseEditConfirmCheck" type="checkbox" class="mt-1 rounded border-[#cfd9e5] text-[#00744b] focus:ring-[#00744b]">
            <span>ฉันตรวจสอบข้อมูลแล้ว และยืนยันการส่งคำขอแก้ไข</span>
          </label>
          <div class="flex items-center gap-2">
            <button type="button" id="lcCourseEditCancel" class="lc-inline-btn">ยกเลิก</button>
            <button type="button" id="lcCourseEditSubmit" class="lc-inline-btn primary">ส่งคำขอแก้ไข</button>
          </div>
        </div>
        <div id="lcCourseEditError" class="lc-inline-note mt-2"></div>
      </div>
      <div class="lc-inline-success-screen" id="lcCourseEditSuccessScreen">
        <div class="lc-inline-success-box">
          <div class="lc-inline-success-title">ได้รับคำขอเรียบร้อยแล้ว</div>
          <div class="lc-inline-success-text">กรุณารอ 3-5 วันสำหรับการรีวิว</div>
          <div class="lc-inline-success-actions">
            <button type="button" class="lc-inline-btn" id="lcCourseEditSuccessClose">ปิดหน้าต่างนี้</button>
            <button type="button" class="lc-inline-btn primary" id="lcCourseEditSuccessViewStatus">ดูรายการแจ้งแก้ไขทั้งหมด</button>
          </div>
        </div>
      </div>
      <div class="lc-inline-processing" id="lcCourseEditProcessingLayer" aria-live="polite" aria-busy="true">
        <div class="lc-inline-processing-box">
          <div class="lc-inline-processing-spinner"></div>
          <div class="lc-inline-processing-text">กำลังส่งคำขอแก้ไข...</div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  const el = (id) => document.getElementById(id);
  const modal = el("lcGlobalLoginModal");
  if (!modal) return;
  const triggers = Array.from(document.querySelectorAll('[data-lc-auth-trigger="1"]'));
  const closeBtn = el("lcGlobalLoginClose");
  const cancelBtn = el("lcGlobalLoginCancel");
  const reqBtn = el("lcGlobalLoginRequestOtp");
  const verifyBtn = el("lcGlobalLoginVerifyOtp");
  const emailEl = el("lcGlobalLoginEmail");
  const otpEl = el("lcGlobalLoginOtp");
  const otpWrap = el("lcGlobalLoginOtpWrap");
  const errEl = el("lcGlobalLoginError");
  const okEl = el("lcGlobalLoginSuccess");
  const statusFabBtn = el("lcStatusFabBtn");
  const statusModal = el("lcStatusModal");
  const statusCloseBtn = el("lcStatusModalClose");
  const statusLogoutBtn = el("lcStatusLogoutBtn");
  const statusRequesterEmailEl = el("lcStatusRequesterEmail");
  const statusFiltersWrap = el("lcStatusFiltersWrap");
  const statusTabs = el("lcStatusTabs");
  const statusTypeSelectEl = el("lcStatusTypeSelect");
  const statusBody = el("lcStatusBody");
  const statusDetailBody = el("lcStatusDetailBody");
  const courseEditModal = el("lcCourseEditModal");
  const courseEditCloseBtn = el("lcCourseEditClose");
  const courseEditCancelBtn = el("lcCourseEditCancel");
  const courseEditSubmitBtn = el("lcCourseEditSubmit");
  const courseEditTabsEl = el("lcCourseEditTabs");
  const courseEditConfirmCheckEl = el("lcCourseEditConfirmCheck");
  const courseEditTitleEl = el("lcCourseEditTitle");
  const courseEditDescriptionEl = el("lcCourseEditDescription");
  const courseEditLearningLinkEl = el("lcCourseEditLearningLink");
  const courseEditTotalMinutesEl = el("lcCourseEditTotalMinutes");
  const courseEditPriceEl = el("lcCourseEditPrice");
  const courseEditHasCertificateEl = el("lcCourseEditHasCertificate");
  const courseEditExistingImagesEl = el("lcCourseEditExistingImages");
  const courseEditDropzoneEl = el("lcCourseEditDropzone");
  const courseEditNewImagesEl = el("lcCourseEditNewImages");
  const courseEditNewImagesMetaEl = el("lcCourseEditNewImagesMeta");
  const courseEditNewImagesPreviewEl = el("lcCourseEditNewImagesPreview");
  const courseEditNoteEl = el("lcCourseEditNote");
  const courseAddSessionToggleEl = el("lcCourseAddSessionToggle");
  const courseAddSessionPanelEl = el("lcCourseAddSessionPanel");
  const courseAddSessionLocationEl = el("lcCourseAddSessionLocation");
  const courseAddSessionTimePeriodEl = el("lcCourseAddSessionTimePeriod");
  const courseAddSessionDetailsEl = el("lcCourseAddSessionDetails");
  const courseAddSessionSubmitEl = el("lcCourseAddSessionSubmit");
  const courseSessionSearchEl = el("lcCourseSessionSearch");
  const courseEditSessionListEl = el("lcCourseEditSessionList");
  const courseEditErrorEl = el("lcCourseEditError");
  const courseEditSuccessScreenEl = el("lcCourseEditSuccessScreen");
  const courseEditSuccessCloseBtn = el("lcCourseEditSuccessClose");
  const courseEditSuccessViewStatusBtn = el("lcCourseEditSuccessViewStatus");
  const courseEditModalSubEl = el("lcCourseEditModalSub");
  const ajaxUrl = <?php echo wp_json_encode($lc_global_edit_ajax_url); ?>;
  const nonce = <?php echo wp_json_encode($lc_global_edit_nonce); ?>;
  let hasSession = <?php echo $lc_global_has_session ? 'true' : 'false'; ?>;
  let activeLocationId = 0;
  let statusFilter = "all";
  let statusTypeFilter = "all";
  let statusRows = [];
  let statusCounts = { all: 0, pending: 0, approved: 0, rejected: 0 };
  let statusTypeCounts = { all: 0, location: 0, course: 0 };
  let requesterEmail = "";
  let activeDetailRequestId = 0;
  let pendingCourseTrigger = null;
  let courseEditContext = null;
  let courseNewSessionSeq = 0;
  const statusNewRequestIds = new Set();

  [modal, statusModal, statusFabBtn, courseEditModal].forEach((node) => {
    if (!node || !(node instanceof HTMLElement)) return;
    if (node.parentElement !== document.body) {
      document.body.appendChild(node);
    }
  });

  const setMsg = (type, msg) => {
    if (errEl) errEl.classList.add("hidden");
    if (okEl) okEl.classList.add("hidden");
    if (!msg) return;
    if (type === "error" && errEl) {
      errEl.textContent = msg;
      errEl.classList.remove("hidden");
    }
    if (type === "success" && okEl) {
      okEl.textContent = msg;
      okEl.classList.remove("hidden");
    }
  };

  const decodeHtmlEntities = (value) => {
    const raw = String(value ?? "");
    if (!raw || raw.indexOf("&") === -1) return raw;
    const textarea = document.createElement("textarea");
    textarea.innerHTML = raw;
    return textarea.value || raw;
  };
  const normalizeLineBreaks = (value) => String(value ?? "").replace(/\r\n?/g, "\n");

  const setCourseEditMsg = (type, msg) => {
    if (!courseEditErrorEl) return;
    courseEditErrorEl.className = "lc-inline-note mt-2";
    courseEditErrorEl.textContent = "";
    if (!msg) return;
    courseEditErrorEl.textContent = msg;
    courseEditErrorEl.classList.add(type === "error" ? "err" : "ok");
  };

  const setCourseEditTab = (tab) => {
    const currentTab = String(tab || "course");
    document.querySelectorAll("#lcCourseEditModal [data-course-tab]").forEach((btn) => {
      const isActive = String(btn.getAttribute("data-course-tab") || "") === currentTab;
      btn.classList.toggle("is-active", isActive);
    });
    document.querySelectorAll("#lcCourseEditModal [data-course-panel]").forEach((panel) => {
      panel.classList.toggle("is-active", String(panel.getAttribute("data-course-panel") || "") === currentTab);
    });
  };

  const showCourseSubmitSuccessInModal = () => {
    const card = courseEditModal?.querySelector(".lc-course-card");
    if (!(card instanceof HTMLElement)) return;
    card.classList.add("is-success");
  };

  const hideCourseSubmitSuccessInModal = () => {
    const card = courseEditModal?.querySelector(".lc-course-card");
    if (!(card instanceof HTMLElement)) return;
    card.classList.remove("is-success");
  };

  const showCourseInlineProcessing = () => {
    const card = courseEditModal?.querySelector(".lc-course-card");
    if (!(card instanceof HTMLElement)) return;
    card.classList.add("is-processing");
  };

  const hideCourseInlineProcessing = () => {
    const card = courseEditModal?.querySelector(".lc-course-card");
    if (!(card instanceof HTMLElement)) return;
    card.classList.remove("is-processing");
  };

  const openModal = (locationId) => {
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
    activeLocationId = Number(locationId || 0);
    if (otpWrap) otpWrap.classList.add("hidden");
    reqBtn?.classList.remove("hidden");
    verifyBtn?.classList.add("hidden");
    setMsg("", "");
  };

  const closeModal = () => {
    modal.classList.add("hidden");
    document.body.style.overflow = "";
    activeLocationId = 0;
    if (!hasSession) {
      pendingCourseTrigger = null;
    }
    if (emailEl) emailEl.value = "";
    if (otpEl) otpEl.value = "";
    setMsg("", "");
  };

  const api = {
    open(opts) {
      const locationId = Number(opts?.locationId || 0);
      if (hasSession) return;
      openModal(locationId);
    },
    close() {
      closeModal();
    },
    isLoggedIn() {
      return !!hasSession;
    },
    async logout() {
      try {
        const fd = new FormData();
        fd.append("action", "lc_logout_location_edit_session");
        fd.append("nonce", String(nonce || ""));
        const res = await fetch(String(ajaxUrl || ""), { method: "POST", body: fd, credentials: "same-origin" });
        const json = await res.json();
        hasSession = !!json?.success ? false : hasSession;
      } catch (err) {
        // keep previous state when request fails
      } finally {
        syncTriggerLabels();
        syncStatusFab();
        closeStatusModal();
        closeCourseEditModal();
      }
    },
  };
  window.lcLocationEditAuth = api;
  window.lcOpenLocationEditAccessModal = function (locationId) {
    api.open({ locationId: Number(locationId || 0) });
  };

  const syncTriggerLabels = () => {
    triggers.forEach((btn) => {
      if (!(btn instanceof HTMLElement)) return;
      const mode = String(btn.dataset.lcAuthMode || "");
      if (mode !== "toggle") return;
      btn.textContent = hasSession ? "ออกจากระบบ" : "เข้าสู่ระบบผู้แก้ไข";
    });
  };
  const syncStatusFab = () => {
    if (!statusFabBtn) return;
    statusFabBtn.classList.toggle("hidden", !hasSession);
  };
  syncTriggerLabels();
  syncStatusFab();

  const setStatusTabUI = () => {
    if (!statusTabs) return;
    const labels = {
      all: `ทั้งหมด (${Number(statusCounts.all || 0)})`,
      pending: `รอตรวจสอบ (${Number(statusCounts.pending || 0)})`,
      approved: `อนุมัติ (${Number(statusCounts.approved || 0)})`,
      rejected: `ไม่อนุมัติ (${Number(statusCounts.rejected || 0)})`,
    };
    statusTabs.querySelectorAll("[data-status-tab]").forEach((btn) => {
      if (!(btn instanceof HTMLButtonElement)) return;
      const key = String(btn.dataset.statusTab || "all");
      const active = key === statusFilter;
      btn.textContent = labels[key] || btn.textContent;
      btn.classList.toggle("bg-[#00744b]", active);
      btn.classList.toggle("text-white", active);
      btn.classList.toggle("border-[#00744b]", active);
      btn.classList.toggle("text-slate-700", !active);
      btn.classList.toggle("border-slate-300", !active);
    });
  };
  const setStatusTypeTabUI = () => {
    if (!(statusTypeSelectEl instanceof HTMLSelectElement)) return;
    const scopedRows = statusRows.filter((row) => {
      return statusFilter === "all" || String(row?.status || "") === statusFilter;
    });
    const scopedCounts = { all: scopedRows.length, location: 0, course: 0 };
    scopedRows.forEach((row) => {
      const key = String(row?.target_type || "location");
      if (key === "location" || key === "course") scopedCounts[key] += 1;
    });
    const labels = {
      all: `ทุกประเภท (${Number(scopedCounts.all || 0)})`,
      location: `สถานที่ (${Number(scopedCounts.location || 0)})`,
      course: `คอร์ส (${Number(scopedCounts.course || 0)})`,
    };
    Array.from(statusTypeSelectEl.options).forEach((opt) => {
      const key = String(opt.value || "all");
      if (labels[key]) opt.text = labels[key];
    });
    statusTypeSelectEl.value = statusTypeFilter;
  };

  const renderStatusRows = () => {
    if (!statusBody) return;
    if (statusFiltersWrap) statusFiltersWrap.classList.remove("hidden");
    if (statusTabs) statusTabs.classList.remove("hidden");
    if (statusBody) statusBody.classList.remove("hidden");
    if (statusDetailBody) {
      statusDetailBody.classList.add("hidden");
      statusDetailBody.innerHTML = "";
    }
    activeDetailRequestId = 0;
    const rows = statusRows.filter((row) => {
      const statusPass = statusFilter === "all" || String(row?.status || "") === statusFilter;
      const typePass = statusTypeFilter === "all" || String(row?.target_type || "location") === statusTypeFilter;
      return statusPass && typePass;
    });
    if (!rows.length) {
      statusBody.innerHTML = `<div class="text-sm text-slate-500">ไม่พบรายการในแท็บนี้</div>`;
      return;
    }
    const esc = (v) => String(v || "").replace(/[&<>"']/g, (m) => ({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[m] || m));
    statusBody.innerHTML = rows.map((row) => {
      const rid = Number(row?.request_id || 0);
      const locationTitle = esc(decodeHtmlEntities(row?.location_title || "-"));
      const typeLabel = esc(row?.target_type_label || (String(row?.target_type || "location") === "course" ? "คอร์ส" : "สถานที่"));
      const status = String(row?.status || "pending");
      const statusLabel = esc(row?.status_label || "");
      const submitted = esc(row?.submitted_at || "-");
      const isNew = statusNewRequestIds.has(rid);
      const statusBadgeClass = status === "approved"
        ? "background:#ecfdf3;color:#166534;"
        : (status === "rejected" ? "background:#fef2f2;color:#991b1b;" : "background:#fff7ed;color:#9a3412;");
      return `
        <button type="button" data-open-request="${rid}" style="width:100%;text-align:left;border:1px solid #e2e8f0;border-radius:12px;padding:12px;background:#fff;margin-bottom:10px;cursor:pointer;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
            <div>
              <div style="font-weight:800;color:#0f172a;">${locationTitle}</div>
              <div style="font-size:12px;color:#64748b;margin-top:2px;">${typeLabel} · คำขอ #${rid} · ส่งเมื่อ ${submitted}</div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
              ${isNew ? '<span style="display:inline-flex;align-items:center;height:24px;padding:0 9px;border-radius:999px;font-size:11px;font-weight:800;background:#dcfce7;color:#166534;border:1px solid #86efac;">NEW</span>' : ''}
              <span style="display:inline-flex;align-items:center;height:24px;padding:0 10px;border-radius:999px;font-size:12px;font-weight:700;${statusBadgeClass}">${statusLabel}</span>
            </div>
          </div>
          <div style="margin-top:8px;font-size:13px;color:#0f766e;font-weight:700;">ดูรายละเอียดการแก้ไข →</div>
        </button>
      `;
    }).join("");
  };

  const renderImageGrid = (images) => {
    const esc = (v) => String(v || "").replace(/[&<>"']/g, (m) => ({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[m] || m));
    const rows = Array.isArray(images) ? images : [];
    if (!rows.length) return `<div style="color:#64748b;">-</div>`;
    return `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(84px,1fr));gap:8px;">${rows.map((img) => {
      const src = esc(img?.thumb || img?.medium || img?.url || "");
      return `<div style="border:1px solid #dbe4ee;border-radius:8px;padding:4px;background:#fff;"><img src="${src}" alt="" style="width:100%;height:74px;object-fit:cover;border-radius:6px;display:block;"></div>`;
    }).join("")}</div>`;
  };

  const renderStatusDetail = (row) => {
    if (!statusDetailBody) return;
    const esc = (v) => String(v || "").replace(/[&<>"']/g, (m) => ({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[m] || m));
    const rid = Number(row?.request_id || 0);
    const locationTitle = esc(decodeHtmlEntities(row?.location_title || "-"));
    const status = String(row?.status || "pending");
    const statusLabel = esc(row?.status_label || "");
    const submitted = esc(row?.submitted_at || "-");
    const rejected = esc(row?.reject_reason || "");
    const details = Array.isArray(row?.change_details) ? row.change_details : [];
    const statusBadgeClass = status === "approved"
      ? "background:#ecfdf3;color:#166534;"
      : (status === "rejected" ? "background:#fef2f2;color:#991b1b;" : "background:#fff7ed;color:#9a3412;");
    const detailRows = details.length ? details.map((item) => {
      const type = String(item?.type || "text");
      const label = esc(item?.label || "ข้อมูล");
      if (type === "images") {
        const action = String(item?.action || "");
        const imgs = Array.isArray(item?.images) ? item.images : [];
        const actionLabel = action === "remove" ? "รายการรูปที่ขอลบ" : (action === "add" ? "รายการรูปที่ขอเพิ่ม" : "รายการรูปที่แก้ไข");
        return `
          <div style="border:1px solid #e2e8f0;border-radius:10px;padding:10px;background:#fff;">
            <div style="font-weight:700;color:#0f172a;margin-bottom:6px;">${label}</div>
            <div style="font-size:12px;color:#64748b;margin-bottom:8px;">${esc(actionLabel)}</div>
            ${renderImageGrid(imgs)}
          </div>
        `;
      }
      const beforeHtml = `<div style="white-space:pre-wrap;">${esc(item?.old || "-")}</div>`;
      const afterHtml = `<div style="white-space:pre-wrap;">${esc(item?.new || "-")}</div>`;
      return `
        <div style="border:1px solid #e2e8f0;border-radius:10px;padding:10px;background:#fff;">
          <div style="font-weight:700;color:#0f172a;margin-bottom:8px;">${label}</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <div style="border:1px solid #dbe3ee;border-radius:8px;padding:8px;background:#f8fafc;">
              <div style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:4px;">ก่อนแก้</div>
              ${beforeHtml}
            </div>
            <div style="border:1px solid #dbe3ee;border-radius:8px;padding:8px;background:#f0fdf4;">
              <div style="font-size:12px;font-weight:700;color:#065f46;margin-bottom:4px;">หลังแก้</div>
              ${afterHtml}
            </div>
          </div>
        </div>
      `;
    }).join("") : `<div style="color:#64748b;">ไม่พบรายละเอียด</div>`;

    statusDetailBody.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
        <button type="button" id="lcStatusBackBtn" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50">← ย้อนกลับ</button>
        <span style="display:inline-flex;align-items:center;height:26px;padding:0 10px;border-radius:999px;font-size:12px;font-weight:700;${statusBadgeClass}">${statusLabel}</span>
      </div>
      <div style="margin-bottom:10px;">
        <div style="font-weight:800;color:#0f172a;">${locationTitle}</div>
        <div style="font-size:12px;color:#64748b;margin-top:2px;">คำขอ #${rid} · ส่งเมื่อ ${submitted}</div>
      </div>
      ${status === "rejected" && rejected ? `<div style="margin-bottom:10px;padding:8px 10px;border-radius:8px;background:#fff1f2;border:1px solid #fecdd3;font-size:13px;color:#9f1239;"><strong>เหตุผลที่ไม่อนุมัติ:</strong> ${rejected}</div>` : ""}
      <div style="display:grid;gap:8px;">${detailRows}</div>
    `;
    const backBtn = el("lcStatusBackBtn");
    backBtn?.addEventListener("click", () => renderStatusRows());
  };

  const openStatusDetail = async (requestId) => {
    if (!statusBody || !statusDetailBody || !statusTabs) return;
    activeDetailRequestId = Number(requestId || 0);
    if (!activeDetailRequestId) return;
    statusNewRequestIds.delete(activeDetailRequestId);
    if (statusFiltersWrap) statusFiltersWrap.classList.add("hidden");
    statusTabs.classList.add("hidden");
    statusBody.classList.add("hidden");
    statusDetailBody.classList.remove("hidden");
    statusDetailBody.innerHTML = `<div class="text-sm text-slate-500">กำลังโหลดรายละเอียด...</div>`;
    try {
      const fd = new FormData();
      fd.append("action", "lc_fetch_location_edit_status_detail");
      fd.append("nonce", String(nonce || ""));
      fd.append("request_id", String(activeDetailRequestId));
      const res = await fetch(String(ajaxUrl || ""), { method: "POST", body: fd, credentials: "same-origin" });
      const json = await res.json();
      if (!json?.success) throw new Error(json?.data?.message || "โหลดรายละเอียดไม่สำเร็จ");
      renderStatusDetail(json.data || {});
    } catch (err) {
      statusDetailBody.innerHTML = `<div class="text-sm text-rose-700">โหลดรายละเอียดไม่สำเร็จ</div><div style="margin-top:8px;"><button type="button" id="lcStatusBackBtn" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50">← ย้อนกลับ</button></div>`;
      const backBtn = el("lcStatusBackBtn");
      backBtn?.addEventListener("click", () => renderStatusRows());
    }
  };

  const fetchStatusFeed = async () => {
    if (!statusBody) return;
    statusBody.innerHTML = `<div class="text-sm text-slate-500">กำลังโหลด...</div>`;
    try {
      const fd = new FormData();
      fd.append("action", "lc_fetch_location_edit_status_feed");
      fd.append("nonce", String(nonce || ""));
      const res = await fetch(String(ajaxUrl || ""), { method: "POST", body: fd, credentials: "same-origin" });
      const json = await res.json();
      if (!json?.success) throw new Error(json?.data?.message || "โหลดสถานะไม่สำเร็จ");
      statusRows = Array.isArray(json?.data?.rows) ? json.data.rows : [];
      statusCounts = (json?.data?.counts && typeof json.data.counts === "object") ? json.data.counts : { all: 0, pending: 0, approved: 0, rejected: 0 };
      statusTypeCounts = (json?.data?.type_counts && typeof json.data.type_counts === "object") ? json.data.type_counts : { all: 0, location: 0, course: 0 };
      requesterEmail = String(json?.data?.requester_email || "").trim();
      if (statusRequesterEmailEl) {
        statusRequesterEmailEl.textContent = requesterEmail ? `อีเมลผู้แก้ไข: ${requesterEmail}` : "ติดตามคำขอที่ส่งไปล่าสุด";
      }
      setStatusTabUI();
      setStatusTypeTabUI();
      renderStatusRows();
    } catch (err) {
      statusBody.innerHTML = `<div class="text-sm text-rose-700">โหลดสถานะไม่สำเร็จ</div>`;
    }
  };

  const openStatusModal = async () => {
    if (!statusModal) return;
    statusModal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
    await fetchStatusFeed();
  };
  const closeStatusModal = () => {
    if (!statusModal) return;
    statusModal.classList.add("hidden");
    document.body.style.overflow = "";
  };
  const closeCourseEditModal = () => {
    if (!courseEditModal) return;
    hideCourseSubmitSuccessInModal();
    hideCourseInlineProcessing();
    courseEditModal.classList.add("hidden");
    document.body.style.overflow = "";
    courseEditContext = null;
    courseNewSessionSeq = 0;
    setCourseEditTab("course");
    if (courseEditConfirmCheckEl) courseEditConfirmCheckEl.checked = false;
    if (courseEditSessionListEl) courseEditSessionListEl.innerHTML = "";
    if (courseSessionSearchEl instanceof HTMLInputElement) courseSessionSearchEl.value = "";
    if (courseAddSessionLocationEl instanceof HTMLSelectElement) {
      courseAddSessionLocationEl.innerHTML = '<option value="">เลือก Location</option>';
    }
    if (courseAddSessionTimePeriodEl instanceof HTMLTextAreaElement) courseAddSessionTimePeriodEl.value = "";
    if (courseAddSessionDetailsEl instanceof HTMLTextAreaElement) courseAddSessionDetailsEl.value = "";
    if (courseAddSessionPanelEl) courseAddSessionPanelEl.classList.add("hidden");
    if (courseAddSessionToggleEl) courseAddSessionToggleEl.textContent = "เพิ่ม Session";
    if (courseEditExistingImagesEl) courseEditExistingImagesEl.innerHTML = "";
    if (courseEditNewImagesEl) {
      courseEditNewImagesEl.value = "";
      courseEditNewImagesEl._lcPendingFiles = [];
      if (typeof courseEditNewImagesEl._lcRenderMeta === "function") {
        courseEditNewImagesEl._lcRenderMeta();
      }
    }
    if (courseEditNewImagesMetaEl) courseEditNewImagesMetaEl.textContent = "ยังไม่ได้เลือกรูป";
    if (courseEditNoteEl) courseEditNoteEl.value = "";
    setCourseEditMsg("", "");
  };
  const updateCourseNewImagesMeta = () => {
    if (!(courseEditNewImagesEl instanceof HTMLInputElement) || !courseEditNewImagesMetaEl || !courseEditNewImagesPreviewEl) return;
    let previewUrls = Array.isArray(courseEditNewImagesEl._lcPreviewUrls) ? courseEditNewImagesEl._lcPreviewUrls : [];
    previewUrls.forEach((url) => { try { URL.revokeObjectURL(url); } catch (err) {} });
    previewUrls = [];
    const files = Array.isArray(courseEditNewImagesEl._lcPendingFiles) ? courseEditNewImagesEl._lcPendingFiles : (courseEditNewImagesEl.files ? Array.from(courseEditNewImagesEl.files) : []);
    const totalBytes = files.reduce((sum, file) => sum + Number(file?.size || 0), 0);
    if (!files.length) {
      courseEditNewImagesMetaEl.textContent = "ยังไม่ได้เลือกรูป";
      courseEditNewImagesPreviewEl.innerHTML = "";
      courseEditNewImagesEl._lcPreviewUrls = previewUrls;
      return;
    }
    const mb = (totalBytes / (1024 * 1024)).toFixed(2);
    courseEditNewImagesMetaEl.textContent = `เลือกแล้ว ${files.length} รูป (${mb} MB)`;
    courseEditNewImagesPreviewEl.innerHTML = files.map((file, index) => {
      const name = String(file?.name || `image-${index + 1}`);
      const safeName = name.replace(/[&<>"']/g, (m) => ({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[m] || m));
      const sizeMb = ((Number(file?.size || 0)) / (1024 * 1024)).toFixed(2);
      const isImage = /^image\//i.test(String(file?.type || ""));
      let thumb = "";
      if (isImage) {
        const url = URL.createObjectURL(file);
        previewUrls.push(url);
        thumb = `<img class="lc-inline-new-file-thumb" src="${url}" alt="">`;
      } else {
        thumb = `<div class="lc-inline-new-file-thumb" style="display:flex;align-items:center;justify-content:center;">FILE</div>`;
      }
      return `
        <div class="lc-inline-new-file">
          ${thumb}
          <div class="lc-inline-new-file-row">
            <div class="lc-inline-new-file-meta">
              <div class="lc-inline-new-file-name">${safeName}</div>
              <div>${sizeMb} MB</div>
            </div>
            <button type="button" class="lc-inline-new-file-remove" data-course-remove-new-image="${index}">ลบ</button>
          </div>
        </div>
      `;
    }).join("");
    courseEditNewImagesEl._lcPreviewUrls = previewUrls;
  };
  const renderCourseImageRows = (images) => {
    if (!courseEditExistingImagesEl) return;
    const rows = Array.isArray(images) ? images : [];
    if (!rows.length) {
      courseEditExistingImagesEl.innerHTML = '<div class="text-sm text-slate-500">ไม่มีรูปภาพคอร์ส</div>';
      return;
    }
    const esc = (v) => String(v || "").replace(/[&<>"']/g, (m) => ({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[m] || m));
    courseEditExistingImagesEl.innerHTML = rows.map((img) => {
      const id = Number(img?.id || 0);
      const src = esc(img?.thumb || img?.medium || img?.url || "");
      return `
        <div class="lc-inline-img-item" data-image-id="${id}">
          <img src="${src}" alt="">
          <button type="button" class="lc-inline-img-rm" data-course-remove-image="${id}">ลบ</button>
        </div>
      `;
    }).join("");
  };
  const renderCourseAddLocationOptions = (locations) => {
    if (!(courseAddSessionLocationEl instanceof HTMLSelectElement)) return;
    const rows = Array.isArray(locations) ? locations : [];
    const esc = (v) => String(v || "").replace(/[&<>"']/g, (m) => ({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[m] || m));
    if (!rows.length) {
      courseAddSessionLocationEl.innerHTML = '<option value="">ไม่พบ Location ที่รองรับคอร์สนี้</option>';
      return;
    }
    const options = rows.map((row) => {
      const id = Number(row?.id || 0);
      const title = esc(decodeHtmlEntities(row?.title || ""));
      if (!id || !title) return "";
      return `<option value="${id}">${title} (#${id})</option>`;
    }).filter(Boolean).join("");
    courseAddSessionLocationEl.innerHTML = `<option value="">เลือก Location</option>${options}`;
  };
  const getCourseSessionRows = () => Array.from(courseEditSessionListEl?.querySelectorAll(".lc-inline-session-item[data-course-session-row]") || []);
  const ensureCourseSessionSearchEmptyEl = () => {
    if (!courseEditSessionListEl) return null;
    let node = courseEditSessionListEl.querySelector("[data-course-session-search-empty='1']");
    if (node) return node;
    node = document.createElement("div");
    node.className = "text-sm text-slate-500 hidden";
    node.setAttribute("data-course-session-search-empty", "1");
    node.textContent = "ไม่พบ Session ที่ตรงกับคำค้นหา";
    courseEditSessionListEl.appendChild(node);
    return node;
  };
  const refreshCourseSessionSearchIndex = () => {
    const rows = getCourseSessionRows();
    rows.forEach((row) => {
      const title = String(row.querySelector(".lc-inline-session-name")?.textContent || "");
      const timePeriod = String(row.querySelector("[data-session-field='time_period']")?.value || "");
      const details = String(row.querySelector("[data-session-field='session_details']")?.value || "");
      row.setAttribute("data-course-session-search", normalizeLineBreaks(`${title}\n${timePeriod}\n${details}`).toLowerCase());
    });
  };
  const applyCourseSessionSearch = () => {
    const rows = getCourseSessionRows();
    const keyword = normalizeLineBreaks(String(courseSessionSearchEl?.value || "")).toLowerCase().trim();
    let visibleCount = 0;
    rows.forEach((row) => {
      const hay = String(row.getAttribute("data-course-session-search") || "").toLowerCase();
      const show = !keyword || hay.includes(keyword);
      row.classList.toggle("hidden", !show);
      if (show) visibleCount += 1;
    });
    const emptyEl = ensureCourseSessionSearchEmptyEl();
    if (emptyEl) {
      emptyEl.classList.toggle("hidden", !(keyword && rows.length > 0 && visibleCount === 0));
    }
  };
  const buildCourseSessionRowHtml = (row) => {
    const esc = (v) => String(v || "").replace(/[&<>"']/g, (m) => ({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[m] || m));
    const sid = Number(row?.id || 0);
    const isNew = !!row?.is_new;
    const keyId = isNew ? Number(row?.new_id || 0) : sid;
    const locationId = Number(row?.location_id || 0);
    const locationTitle = esc(decodeHtmlEntities(row?.location_title || "Session"));
    const timePeriod = esc(row?.time_period || "");
    const details = esc(row?.session_details || "");
    const badge = isNew ? '<span class="lc-inline-session-id"> (ใหม่)</span>' : `<span class="lc-inline-session-id"> (#${sid})</span>`;
    const removeText = isNew ? "ลบ Session ใหม่" : "ลบ Session นี้";
    return `
      <details class="lc-inline-session-item" data-course-session-row="${keyId}" data-course-session-new="${isNew ? "1" : "0"}" data-course-location-id="${locationId}">
        <summary class="lc-inline-session-head">
          <div class="lc-inline-session-head-row">
            <span class="lc-inline-session-name">${locationTitle}${badge}</span>
            <button type="button" class="lc-inline-session-remove toggle" data-course-session-delete="1">${removeText}</button>
          </div>
        </summary>
        <div class="lc-inline-session-body">
          <label class="text-xs font-semibold text-slate-600">ช่วงเวลาเรียน</label>
          <textarea data-session-field="time_period" rows="2" class="mt-1 w-full rounded-lg border border-[#cfd9e5] px-3 py-2 text-sm focus:border-[#00744b] focus:ring-0">${timePeriod}</textarea>
          <label class="text-xs font-semibold text-slate-600 mt-2 block">รายละเอียด Session</label>
          <textarea data-session-field="session_details" rows="3" class="mt-1 w-full rounded-lg border border-[#cfd9e5] px-3 py-2 text-sm focus:border-[#00744b] focus:ring-0">${details}</textarea>
        </div>
      </details>
    `;
  };
  const renderCourseSessionRows = (sessions) => {
    if (!courseEditSessionListEl) return;
    const rows = Array.isArray(sessions) ? sessions : [];
    if (!rows.length) {
      courseEditSessionListEl.innerHTML = '<div class="text-sm text-slate-500">ไม่พบ Session ของคอร์สนี้</div>';
      return;
    }
    courseEditSessionListEl.innerHTML = rows.map((row) => buildCourseSessionRowHtml(row)).join("");
    refreshCourseSessionSearchIndex();
    applyCourseSessionSearch();
  };
  const openCourseEditModal = async (courseId, fallbackTitle = "") => {
    if (!courseEditModal) return;
    const cid = Number(courseId || 0);
    if (!cid) return;
    hideCourseSubmitSuccessInModal();
    hideCourseInlineProcessing();
    setCourseEditTab("course");
    courseNewSessionSeq = 0;
    if (courseEditConfirmCheckEl) courseEditConfirmCheckEl.checked = false;
    if (courseAddSessionPanelEl) courseAddSessionPanelEl.classList.add("hidden");
    if (courseAddSessionToggleEl) courseAddSessionToggleEl.textContent = "เพิ่ม Session";
    courseEditModal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
    if (courseEditModalSubEl) courseEditModalSubEl.textContent = "กำลังโหลดข้อมูลคอร์ส...";
    setCourseEditMsg("", "");
    if (courseEditSessionListEl) courseEditSessionListEl.innerHTML = '<div class="text-sm text-slate-500">กำลังโหลด Session...</div>';
    try {
      const fd = new FormData();
      fd.append("action", "lc_get_course_edit_context");
      fd.append("nonce", String(nonce || ""));
      fd.append("course_id", String(cid));
      const contextRes = await fetch(String(ajaxUrl || ""), { method: "POST", body: fd, credentials: "same-origin" });
      const contextJson = await contextRes.json();
      if (!contextJson?.success || !contextJson?.data) {
        throw new Error(contextJson?.data?.message || "โหลดข้อมูลคอร์สไม่สำเร็จ");
      }
      courseEditContext = contextJson.data;
      if (courseEditTitleEl) courseEditTitleEl.value = decodeHtmlEntities(String(contextJson.data?.course?.title || fallbackTitle || ""));
      if (courseEditDescriptionEl) courseEditDescriptionEl.value = String(contextJson.data?.course?.course_description || "");
      if (courseEditLearningLinkEl) courseEditLearningLinkEl.value = String(contextJson.data?.course?.learning_link || "");
      if (courseEditTotalMinutesEl) courseEditTotalMinutesEl.value = String(contextJson.data?.course?.total_minutes || "");
      if (courseEditPriceEl) courseEditPriceEl.value = String(contextJson.data?.course?.price || "");
      if (courseEditHasCertificateEl) {
        const raw = String(contextJson.data?.course?.has_certificate ?? "").toLowerCase();
        courseEditHasCertificateEl.checked = raw === "1" || raw === "true" || raw === "yes";
      }
      if (courseEditNewImagesEl) {
        courseEditNewImagesEl.value = "";
        courseEditNewImagesEl._lcPendingFiles = [];
      }
      updateCourseNewImagesMeta();
      renderCourseImageRows(contextJson.data?.course?.images || []);
      renderCourseAddLocationOptions(contextJson.data?.available_locations || []);
      if (courseEditModalSubEl) courseEditModalSubEl.textContent = `คอร์ส #${cid}`;
      renderCourseSessionRows(contextJson.data?.sessions || []);
    } catch (err) {
      if (courseEditModalSubEl) courseEditModalSubEl.textContent = "โหลดข้อมูลคอร์สไม่สำเร็จ";
      if (courseEditSessionListEl) courseEditSessionListEl.innerHTML = '<div class="text-sm text-rose-700">ไม่สามารถโหลด Session ได้</div>';
      setCourseEditMsg("error", String(err?.message || "โหลดข้อมูลคอร์สไม่สำเร็จ"));
    }
  };
  window.lcLocationEditStatus = {
    open: openStatusModal,
    close: closeStatusModal,
    markNewRequest(requestId) {
      const rid = Number(requestId || 0);
      if (rid > 0) statusNewRequestIds.add(rid);
    },
    async openWithNew(requestId) {
      const rid = Number(requestId || 0);
      if (rid > 0) statusNewRequestIds.add(rid);
      statusFilter = "all";
      statusTypeFilter = "all";
      setStatusTabUI();
      setStatusTypeTabUI();
      await openStatusModal();
    },
  };

  const refreshLoginState = async () => {
    try {
      const fd = new FormData();
      fd.append("action", "lc_fetch_location_edit_dashboard");
      fd.append("nonce", String(nonce || ""));
      const res = await fetch(String(ajaxUrl || ""), { method: "POST", body: fd, credentials: "same-origin" });
      const json = await res.json();
      hasSession = !!json?.success;
    } catch (err) {
      hasSession = false;
    } finally {
      syncTriggerLabels();
      syncStatusFab();
    }
  };
  refreshLoginState();

  triggers.forEach((btn) => {
    btn.addEventListener("click", async () => {
      const mode = String(btn.dataset.lcAuthMode || "");
      if (mode === "toggle" && hasSession) {
        await api.logout();
        return;
      }
      api.open({ locationId: 0 });
    });
  });
  closeBtn?.addEventListener("click", closeModal);
  cancelBtn?.addEventListener("click", closeModal);
  modal?.addEventListener("click", (e) => {
    const target = e.target;
    if (target instanceof Element && target.dataset.globalLoginBackdrop === "1") {
      closeModal();
    }
  });
  statusFabBtn?.addEventListener("click", openStatusModal);
  statusCloseBtn?.addEventListener("click", closeStatusModal);
  statusLogoutBtn?.addEventListener("click", async () => {
    await api.logout();
  });
  statusModal?.addEventListener("click", (e) => {
    const target = e.target;
    if (target instanceof Element && target.dataset.statusBackdrop === "1") {
      closeStatusModal();
    }
  });
  statusTabs?.addEventListener("click", (e) => {
    const btn = e.target instanceof Element ? e.target.closest("[data-status-tab]") : null;
    if (!btn) return;
    statusFilter = String(btn.getAttribute("data-status-tab") || "all");
    statusTypeFilter = "all";
    setStatusTabUI();
    setStatusTypeTabUI();
    renderStatusRows();
  });
  statusTypeSelectEl?.addEventListener("change", () => {
    statusTypeFilter = String(statusTypeSelectEl.value || "all");
    setStatusTabUI();
    setStatusTypeTabUI();
    renderStatusRows();
  });
  statusBody?.addEventListener("click", (e) => {
    const btn = e.target instanceof Element ? e.target.closest("[data-open-request]") : null;
    if (!btn) return;
    const rid = Number(btn.getAttribute("data-open-request") || 0);
    if (!rid) return;
    openStatusDetail(rid);
  });
  document.addEventListener("lc:location-edit-submitted", async (event) => {
    const rid = Number(event?.detail?.requestId || 0);
    if (rid > 0) statusNewRequestIds.add(rid);
    statusFilter = "all";
    statusTypeFilter = "all";
    setStatusTabUI();
    setStatusTypeTabUI();
    await openStatusModal();
  });

  document.addEventListener("click", async (event) => {
    const target = event.target instanceof Element ? event.target.closest('[data-lc-course-edit-trigger="1"]') : null;
    if (!target) return;
    event.preventDefault();
    const courseId = Number(target.getAttribute("data-course-id") || 0);
    const courseTitle = String(target.getAttribute("data-course-title") || "").trim();
    if (!courseId) return;
    if (hasSession) {
      await openCourseEditModal(courseId, courseTitle);
      return;
    }
    pendingCourseTrigger = { courseId, courseTitle };
    api.open({ locationId: 0 });
  });
  courseEditCloseBtn?.addEventListener("click", closeCourseEditModal);
  courseEditCancelBtn?.addEventListener("click", closeCourseEditModal);
  courseEditSuccessCloseBtn?.addEventListener("click", closeCourseEditModal);
  courseEditSuccessViewStatusBtn?.addEventListener("click", async () => {
    closeCourseEditModal();
    statusFilter = "all";
    statusTypeFilter = "course";
    setStatusTabUI();
    setStatusTypeTabUI();
    await openStatusModal();
  });
  courseEditModal?.addEventListener("click", (e) => {
    const target = e.target;
    if (target instanceof Element && target.dataset.courseEditBackdrop === "1") {
      closeCourseEditModal();
    }
  });
  courseEditTabsEl?.addEventListener("click", (e) => {
    const btn = e.target instanceof Element ? e.target.closest("[data-course-tab]") : null;
    if (!btn) return;
    setCourseEditTab(String(btn.getAttribute("data-course-tab") || "course"));
  });
  courseEditDropzoneEl?.addEventListener("click", (e) => {
    e.preventDefault();
    courseEditNewImagesEl?.click();
  });
  courseEditDropzoneEl?.addEventListener("dragover", (e) => {
    e.preventDefault();
    courseEditDropzoneEl.classList.add("is-drag");
  });
  courseEditDropzoneEl?.addEventListener("dragleave", () => {
    courseEditDropzoneEl.classList.remove("is-drag");
  });
  courseEditDropzoneEl?.addEventListener("drop", (e) => {
    e.preventDefault();
    courseEditDropzoneEl.classList.remove("is-drag");
    if (!(courseEditNewImagesEl instanceof HTMLInputElement)) return;
    const incoming = Array.from(e.dataTransfer?.files || []);
    const current = Array.isArray(courseEditNewImagesEl._lcPendingFiles) ? courseEditNewImagesEl._lcPendingFiles : [];
    courseEditNewImagesEl._lcPendingFiles = [...current, ...incoming];
    updateCourseNewImagesMeta();
  });
  courseEditNewImagesEl?.addEventListener("change", () => {
    if (!(courseEditNewImagesEl instanceof HTMLInputElement)) return;
    const selected = courseEditNewImagesEl.files ? Array.from(courseEditNewImagesEl.files) : [];
    courseEditNewImagesEl._lcPendingFiles = selected;
    updateCourseNewImagesMeta();
  });
  if (courseEditNewImagesEl instanceof HTMLInputElement) {
    courseEditNewImagesEl._lcPendingFiles = [];
    courseEditNewImagesEl._lcRenderMeta = updateCourseNewImagesMeta;
  }
  courseEditNewImagesPreviewEl?.addEventListener("click", (e) => {
    const btn = e.target instanceof Element ? e.target.closest("[data-course-remove-new-image]") : null;
    if (!btn || !(courseEditNewImagesEl instanceof HTMLInputElement)) return;
    e.preventDefault();
    const idx = Number(btn.getAttribute("data-course-remove-new-image") || -1);
    const files = Array.isArray(courseEditNewImagesEl._lcPendingFiles) ? [...courseEditNewImagesEl._lcPendingFiles] : [];
    if (idx < 0 || idx >= files.length) return;
    files.splice(idx, 1);
    courseEditNewImagesEl._lcPendingFiles = files;
    updateCourseNewImagesMeta();
  });
  courseEditExistingImagesEl?.addEventListener("click", (e) => {
    const btn = e.target instanceof Element ? e.target.closest("[data-course-remove-image]") : null;
    if (!btn) return;
    e.preventDefault();
    const row = btn.closest(".lc-inline-img-item");
    if (!row) return;
    row.classList.toggle("is-remove");
  });
  courseEditSessionListEl?.addEventListener("click", (e) => {
    const btn = e.target instanceof Element ? e.target.closest("[data-course-session-delete='1']") : null;
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const row = btn.closest(".lc-inline-session-item");
    if (!row) return;
    if (String(row.getAttribute("data-course-session-new") || "0") === "1") {
      row.remove();
      refreshCourseSessionSearchIndex();
      applyCourseSessionSearch();
      return;
    }
    const willDelete = !row.classList.contains("is-remove");
    row.classList.toggle("is-remove", willDelete);
    btn.classList.toggle("is-active", willDelete);
    btn.textContent = willDelete ? "ยกเลิกลบ Session" : "ลบ Session นี้";
    refreshCourseSessionSearchIndex();
    applyCourseSessionSearch();
  });
  courseEditSessionListEl?.addEventListener("input", (e) => {
    const target = e.target;
    if (!(target instanceof HTMLTextAreaElement)) return;
    if (!target.matches("[data-session-field='time_period'], [data-session-field='session_details']")) return;
    refreshCourseSessionSearchIndex();
    applyCourseSessionSearch();
  });
  courseSessionSearchEl?.addEventListener("input", () => {
    refreshCourseSessionSearchIndex();
    applyCourseSessionSearch();
  });
  courseAddSessionToggleEl?.addEventListener("click", () => {
    if (!courseAddSessionPanelEl) return;
    const willOpen = courseAddSessionPanelEl.classList.contains("hidden");
    courseAddSessionPanelEl.classList.toggle("hidden", !willOpen);
    if (courseAddSessionToggleEl) {
      courseAddSessionToggleEl.textContent = willOpen ? "ปิดเพิ่ม Session" : "เพิ่ม Session";
    }
  });
  courseAddSessionSubmitEl?.addEventListener("click", () => {
    if (!courseEditSessionListEl || !(courseAddSessionLocationEl instanceof HTMLSelectElement)) return;
    const locationId = Number(courseAddSessionLocationEl.value || 0);
    const locationTitle = String(courseAddSessionLocationEl.selectedOptions?.[0]?.textContent || "").trim();
    const timePeriod = normalizeLineBreaks(String(courseAddSessionTimePeriodEl?.value || ""));
    const sessionDetails = normalizeLineBreaks(String(courseAddSessionDetailsEl?.value || ""));
    if (!locationId || !locationTitle) {
      setCourseEditMsg("error", "กรุณาเลือก Location ก่อนเพิ่ม Session");
      return;
    }
    const rowHtml = buildCourseSessionRowHtml({
      is_new: true,
      new_id: ++courseNewSessionSeq,
      location_id: locationId,
      location_title: locationTitle.replace(/\s*\(#\d+\)\s*$/, ""),
      time_period: timePeriod,
      session_details: sessionDetails,
    });
    const empty = courseEditSessionListEl.querySelector(".text-sm.text-slate-500:not([data-course-session-search-empty='1'])");
    if (empty) empty.remove();
    courseEditSessionListEl.insertAdjacentHTML("afterbegin", rowHtml);
    if (courseAddSessionTimePeriodEl instanceof HTMLTextAreaElement) courseAddSessionTimePeriodEl.value = "";
    if (courseAddSessionDetailsEl instanceof HTMLTextAreaElement) courseAddSessionDetailsEl.value = "";
    courseAddSessionLocationEl.value = "";
    refreshCourseSessionSearchIndex();
    applyCourseSessionSearch();
    setCourseEditMsg("", "");
  });
  courseEditSubmitBtn?.addEventListener("click", async () => {
    if (!courseEditContext || !hasSession) {
      setCourseEditMsg("error", "กรุณาเข้าสู่ระบบผู้แก้ไขก่อนส่งคำขอ");
      return;
    }
    const courseId = Number(courseEditContext?.course_id || 0);
    if (!courseId) {
      setCourseEditMsg("error", "ไม่พบข้อมูลคอร์ส");
      return;
    }
    const title = String(courseEditTitleEl?.value || "").trim();
    const courseDescription = normalizeLineBreaks(String(courseEditDescriptionEl?.value || ""));
    const learningLink = String(courseEditLearningLinkEl?.value || "").trim();
    const totalMinutes = String(courseEditTotalMinutesEl?.value || "").trim();
    const price = String(courseEditPriceEl?.value || "").trim();
    const hasCertificate = !!courseEditHasCertificateEl?.checked;
    const newImages = Array.isArray(courseEditNewImagesEl?._lcPendingFiles) ? courseEditNewImagesEl._lcPendingFiles : Array.from(courseEditNewImagesEl?.files || []);
    const requestNote = normalizeLineBreaks(String(courseEditNoteEl?.value || ""));
    const sessionRows = Array.from(courseEditSessionListEl?.querySelectorAll(".lc-inline-session-item[data-course-session-row]") || []);
    const removedImageIds = Array.from(courseEditExistingImagesEl?.querySelectorAll(".lc-inline-img-item.is-remove") || [])
      .map((node) => Number(node.getAttribute("data-image-id") || 0))
      .filter((id) => Number.isFinite(id) && id > 0);
    const deleteSessionIds = sessionRows
      .filter((row) => String(row.getAttribute("data-course-session-new") || "0") !== "1" && row.classList.contains("is-remove"))
      .map((row) => Number(row.getAttribute("data-course-session-row") || 0))
      .filter((sid) => Number.isFinite(sid) && sid > 0);
    const sessions = sessionRows
      .filter((row) => String(row.getAttribute("data-course-session-new") || "0") !== "1" && !row.classList.contains("is-remove"))
      .map((row) => {
        const sid = Number(row.getAttribute("data-course-session-row") || 0);
        const timePeriod = normalizeLineBreaks(String(row.querySelector("[data-session-field='time_period']")?.value || ""));
        const sessionDetails = normalizeLineBreaks(String(row.querySelector("[data-session-field='session_details']")?.value || ""));
        return { id: sid, time_period: timePeriod, session_details: sessionDetails };
      })
      .filter((row) => Number(row.id) > 0);
    const newSessions = sessionRows
      .filter((row) => String(row.getAttribute("data-course-session-new") || "0") === "1" && !row.classList.contains("is-remove"))
      .map((row) => {
        const locationId = Number(row.getAttribute("data-course-location-id") || 0);
        const timePeriod = normalizeLineBreaks(String(row.querySelector("[data-session-field='time_period']")?.value || ""));
        const sessionDetails = normalizeLineBreaks(String(row.querySelector("[data-session-field='session_details']")?.value || ""));
        return { location_id: locationId, time_period: timePeriod, session_details: sessionDetails };
      })
      .filter((row) => Number(row.location_id) > 0);
    const normalize = (v) => String(v || "").trim();
    const normalizeNumber = (v) => {
      const raw = String(v ?? "").trim();
      if (raw === "") return "";
      const n = Number(raw);
      return Number.isFinite(n) ? String(n) : raw;
    };
    const normalizeBool = (v) => {
      const raw = String(v ?? "").trim().toLowerCase();
      return raw === "1" || raw === "true" || raw === "yes" ? "1" : "0";
    };
    const beforeCourse = courseEditContext?.course || {};
    const beforeSessionsMap = new Map((Array.isArray(courseEditContext?.sessions) ? courseEditContext.sessions : []).map((s) => [Number(s?.id || 0), s]));
    const courseChanged =
      normalize(title) !== normalize(beforeCourse?.title) ||
      normalize(courseDescription) !== normalize(beforeCourse?.course_description) ||
      normalize(learningLink) !== normalize(beforeCourse?.learning_link) ||
      normalizeNumber(totalMinutes) !== normalizeNumber(beforeCourse?.total_minutes) ||
      normalizeNumber(price) !== normalizeNumber(beforeCourse?.price) ||
      normalizeBool(hasCertificate ? "1" : "0") !== normalizeBool(beforeCourse?.has_certificate);
    const changedSessions = sessions.filter((row) => {
      const before = beforeSessionsMap.get(Number(row.id || 0));
      if (!before) return false;
      const changed = (
        normalize(row.time_period) !== normalize(before?.time_period) ||
        normalize(row.session_details) !== normalize(before?.session_details)
      );
      return changed;
    });
    const changed = courseChanged || changedSessions.length > 0 || deleteSessionIds.length > 0 || newSessions.length > 0 || removedImageIds.length > 0 || newImages.length > 0 || normalize(requestNote) !== "";

    if (!changed) {
      setCourseEditMsg("error", "ข้อความใหม่เหมือนข้อมูลเดิม");
      return;
    }
    if (!courseEditConfirmCheckEl?.checked) {
      setCourseEditMsg("error", "กรุณาติ๊กยืนยันว่าตรวจสอบข้อมูลแล้วก่อนส่ง");
      return;
    }

    courseEditSubmitBtn.setAttribute("disabled", "disabled");
    showCourseInlineProcessing();
    setCourseEditMsg("success", "กำลังส่งคำขอแก้ไขคอร์ส...");
    try {
      const fd = new FormData();
      fd.append("action", "lc_submit_course_edit_request");
      fd.append("nonce", String(nonce || ""));
      fd.append("course_id", String(courseId));
      fd.append("title", title);
      fd.append("course_description", courseDescription);
      fd.append("learning_link", learningLink);
      fd.append("total_minutes", totalMinutes);
      fd.append("price", price);
      fd.append("has_certificate", hasCertificate ? "1" : "0");
      fd.append("request_note", requestNote);
      fd.append("sessions", JSON.stringify(changedSessions));
      fd.append("delete_session_ids", JSON.stringify(deleteSessionIds));
      fd.append("new_sessions", JSON.stringify(newSessions));
      fd.append("remove_image_ids", JSON.stringify(removedImageIds));
      newImages.forEach((file) => {
        fd.append("new_images[]", file, file.name || "course-image.jpg");
      });
      const res = await fetch(String(ajaxUrl || ""), { method: "POST", body: fd, credentials: "same-origin" });
      const json = await res.json();
      if (!json?.success) throw new Error(json?.data?.message || "ส่งคำขอแก้ไขคอร์สไม่สำเร็จ");
      const rid = Number(json?.data?.request_id || 0);
      if (rid > 0) statusNewRequestIds.add(rid);
      hideCourseInlineProcessing();
      showCourseSubmitSuccessInModal();
      setCourseEditMsg("", "");
    } catch (err) {
      hideCourseInlineProcessing();
      setCourseEditMsg("error", String(err?.message || "ส่งคำขอแก้ไขคอร์สไม่สำเร็จ"));
    } finally {
      courseEditSubmitBtn.removeAttribute("disabled");
    }
  });

  reqBtn?.addEventListener("click", async () => {
    const email = String(emailEl?.value || "").trim();
    if (!email) {
      setMsg("error", "กรุณากรอกอีเมล");
      return;
    }
    reqBtn.setAttribute("disabled", "disabled");
    setMsg("success", "กำลังส่ง OTP...");
    try {
      const fd = new FormData();
      fd.append("action", "lc_request_location_edit_otp");
      fd.append("nonce", String(nonce || ""));
      fd.append("email", email);
      fd.append("location_id", String(activeLocationId || 0));
      const res = await fetch(String(ajaxUrl || ""), { method: "POST", body: fd });
      const json = await res.json();
      if (!json?.success) throw new Error(json?.data?.message || "ส่ง OTP ไม่สำเร็จ");
      setMsg("success", "ส่ง OTP แล้ว กรุณาตรวจสอบอีเมล");
      otpWrap?.classList.remove("hidden");
      verifyBtn?.classList.remove("hidden");
      reqBtn.classList.add("hidden");
    } catch (err) {
      setMsg("error", String(err?.message || "ส่ง OTP ไม่สำเร็จ"));
    } finally {
      reqBtn.removeAttribute("disabled");
    }
  });

  verifyBtn?.addEventListener("click", async () => {
    const email = String(emailEl?.value || "").trim();
    const otp = String(otpEl?.value || "").trim();
    if (!email || !otp) {
      setMsg("error", "กรุณากรอกอีเมลและ OTP");
      return;
    }
    verifyBtn.setAttribute("disabled", "disabled");
    setMsg("success", "กำลังตรวจสอบ OTP...");
    try {
      const fd = new FormData();
      fd.append("action", "lc_verify_location_edit_otp");
      fd.append("nonce", String(nonce || ""));
      fd.append("email", email);
      fd.append("otp", otp);
      fd.append("location_id", String(activeLocationId || 0));
      const res = await fetch(String(ajaxUrl || ""), { method: "POST", body: fd });
      const json = await res.json();
      if (!json?.success) throw new Error(json?.data?.message || "OTP ไม่ถูกต้อง");
      hasSession = true;
      closeModal();
      syncTriggerLabels();
      syncStatusFab();
      setMsg("success", "");
      if (pendingCourseTrigger && Number(pendingCourseTrigger.courseId || 0) > 0) {
        const { courseId, courseTitle } = pendingCourseTrigger;
        pendingCourseTrigger = null;
        await openCourseEditModal(Number(courseId || 0), String(courseTitle || ""));
      }
    } catch (err) {
      setMsg("error", String(err?.message || "OTP ไม่ถูกต้อง"));
    } finally {
      verifyBtn.removeAttribute("disabled");
    }
  });
})();
</script>
