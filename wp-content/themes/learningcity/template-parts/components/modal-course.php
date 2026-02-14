<div data-modal-content="modal-course" class="modal modal-course p-0!">
  <div class="overlay-modal"></div>

  <div class="card-modal max-w-full">
    <div class="absolute top-4 right-4 z-20">
      <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
        <div class="icon-close"></div>
      </button>
    </div>

    <div class="modal-content relative z-10 overflow-y-auto! group h-full bg-[#fcfcfc]">
        <div class="modal-course-gradient absolute top-0 left-0 w-full sm:h-96 h-[640px]"
        style="background: linear-gradient(0deg, #fcfcfc 10%, #00744b33 45%);">
        </div>

      <div class="py-3 px-4 relative">

        <!-- Top actions -->
        <div class="flex items-center justify-start gap-2">
          <button class="btn-link-v1" data-course-copy-link>
            <i class="icon-copy-link"></i>
            <span class="btn-text">คัดลอกลิงก์</span>
          </button>

          <a class="btn-link-v1" data-course-open-link target="_blank">
            <i class="icon-open-link"></i>
            เปิดในหน้าใหม่
          </a>
        </div>

        <!-- Skeleton (โชว์ตอนกำลังโหลด) -->
        <div class="skeleton relative w-full z-10" data-course-skeleton>
          <div class="md:px-12 sm:px-6 px-0 mt-6 max-sm:mt-12">
            <div class="flex sm:items-end sm:flex-row flex-col-reverse items-center max-sm:text-center gap-4">
              <div class="flex-1 max-sm:mt-2 w-full">
                <div class="h-4 sm:w-1/2 w-full mb-2" data-skeleton></div>
                <div class="h-12 sm:w-4/5 w-full" data-skeleton></div>
                <div class="flex items-center justify-start gap-2 mt-2 max-sm:w-full mx-auto">
                  <div class="h-4 w-4" data-skeleton></div>
                  <div class="h-4 sm:w-1/2 w-full" data-skeleton></div>
                </div>
              </div>
              <div class="w-32 max-sm:w-36 aspect-square" data-skeleton></div>
            </div>

            <div class="flex sm:flex-row flex-col items-center overflow-hidden my-8 w-full" data-skeleton>
              <div class="py-5 flex items-center justify-evenly flex-1 w-full">
                <?php for ($i=0; $i<4; $i++): ?>
                  <div class="text-center flex-1">
                    <div class="h-5 w-5 mt-1 mx-auto" data-skeleton></div>
                    <div class="h-3 w-1/2 mt-1 mx-auto" data-skeleton></div>
                    <div class="h-4 w-1/2 mt-1 mx-auto" data-skeleton></div>
                  </div>
                <?php endfor; ?>
              </div>
            </div>

            <div class="pt-1">
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-1/2 h-4 mt-3" data-skeleton></div>
            </div>

            <div class="my-10">
              <div class="w-1/3 h-8" data-skeleton></div>
              <div class="w-full h-20 mt-2" data-skeleton></div>
              <div class="w-full h-20 mt-2" data-skeleton></div>
              <div class="w-full h-20 mt-2" data-skeleton></div>
            </div>

            <div class="my-10">
              <div class="w-1/3 h-8" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
            </div>

            <div class="my-10">
              <div class="w-1/3 h-8" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
            </div>
          </div>
        </div>

        <!-- AJAX จะยัด HTML เข้ามาตรงนี้ -->
        <div class="content-inner is-loaded md:px-12 sm:px-6 px-0 mt-6 transition-all max-sm:mt-12"
             data-course-modal-body
             style="display:none;">
        </div>

      </div>
    </div>
  </div>
</div>

<div data-modal-content="modal-course-report" class="modal modal-course-report p-4!">
  <div class="overlay-course-report"></div>
  <div class="card-modal max-w-[640px] max-h-[90vh] overflow-auto">
    <div class="absolute top-4 right-4 z-20">
      <button class="close-course-report-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
        <div class="icon-close"></div>
      </button>
    </div>

    <div class="modal-content relative z-10 bg-white p-6 sm:p-8">
      <h3 class="text-fs24 font-bold">แจ้งแก้ไขข้อมูลคอร์ส</h3>
      <p class="text-fs14 opacity-70 mt-1">โปรดระบุข้อมูลที่ไม่ถูกต้องเพื่อให้ทีมงานตรวจสอบได้เร็วขึ้น</p>

      <form id="courseReportForm" class="mt-5 space-y-4">
        <input type="hidden" id="courseReportCourseId" name="course_id" value="">

        <div>
          <div class="text-fs16 font-semibold mb-2">เรื่องที่ต้องการแก้ไข</div>
          <div class="grid sm:grid-cols-2 grid-cols-1 gap-2 text-fs14">
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="report_topics[]" value="address">สถานที่</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="report_topics[]" value="phone">เบอร์ติดต่อ</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="report_topics[]" value="hours">วันเวลาเรียน</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="report_topics[]" value="images">รูปภาพ</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="report_topics[]" value="links">ลิงก์ลงทะเบียน</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="report_topics[]" value="other">อื่น ๆ</label>
          </div>
        </div>

        <div>
          <label for="courseReportDetails" class="text-fs16 font-semibold">รายละเอียดเพิ่มเติม</label>
          <textarea id="courseReportDetails" name="report_details" rows="4" class="mt-2 w-full rounded-xl border px-3 py-2 text-fs14" placeholder="ระบุข้อมูลที่ควรแก้ไข"></textarea>
        </div>

        <div class="hidden" aria-hidden="true">
          <label for="courseReportWebsite">Leave this empty</label>
          <input type="text" id="courseReportWebsite" name="report_website" tabindex="-1" autocomplete="off">
        </div>

        <div class="grid sm:grid-cols-2 grid-cols-1 gap-3">
          <div>
            <label for="courseReportName" class="text-fs16 font-semibold">ชื่อผู้แจ้ง (ไม่บังคับ)</label>
            <input id="courseReportName" name="report_name" class="mt-2 w-full rounded-xl border px-3 py-2 text-fs14">
          </div>
          <div>
            <label for="courseReportContact" class="text-fs16 font-semibold">อีเมล/เบอร์ (ไม่บังคับ)</label>
            <input id="courseReportContact" name="report_contact" class="mt-2 w-full rounded-xl border px-3 py-2 text-fs14">
          </div>
        </div>

        <div id="courseReportError" class="hidden text-fs14 text-rose-600"></div>
        <div id="courseReportSuccess" class="hidden text-fs14 text-emerald-700"></div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" class="btn-link-v1 close-course-report-modal">ยกเลิก</button>
          <button type="submit" id="courseReportSubmit" class="btn-link-v2 text-fs16! px-5! py-2!">ส่งรายงาน</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .modal.modal-course-report {
    align-items: center;
    justify-content: center;
  }

  .modal.modal-course-report .overlay-course-report {
    background: rgba(0, 0, 0, 0.7);
    z-index: 40;
    transition: 0.25s ease;
    opacity: 0;
    visibility: hidden;
    pointer-events: all;
    cursor: pointer;
    position: fixed;
    inset: 0;
  }

  .modal.modal-course-report .card-modal {
    z-index: 50;
    width: min(640px, calc(100% - 2rem));
    max-height: min(90vh, 820px);
    border-radius: 20px;
    opacity: 0;
    transform: translateY(18px) scale(0.97);
    transition: transform 0.24s ease, opacity 0.24s ease;
  }

  .modal.modal-course-report.modal-active .overlay-course-report {
    opacity: 1;
    visibility: visible;
  }

  .modal.modal-course-report.modal-active .card-modal {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  </style>
