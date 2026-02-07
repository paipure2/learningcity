<?php wp_footer(); ?>

<style>
  /* Optional: map to your site's CSS variables if they exist */
  :root {
    --modal-bg: var(--background, #ffffff);
    --modal-fg: var(--foreground, #111827);
    --modal-muted: var(--muted-foreground, #6b7280);
    --modal-border: var(--border, #e5e7eb);
    --modal-primary: var(--primary, #111827);
    --modal-primary-fg: var(--primary-foreground, #ffffff);
    --modal-surface: var(--card, #ffffff);
  }
</style>

<div id="welcomeModal" class="fixed inset-0 z-99999999 hidden" role="dialog" aria-modal="true">
  <!-- Backdrop -->
  <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]"></div>

  <!-- Panel -->
  <div class="relative flex min-h-full items-center justify-center p-4">
    <div
      class="w-full max-w-2xl rounded-2xl shadow-2xl"
      style="background: var(--modal-surface); color: var(--modal-fg);"
    >
      <div class="flex items-start justify-between gap-4 px-6 py-4 border-b" style="border-color: var(--modal-border);">
        <div class="min-w-0">
          <h2 class="text-lg font-semibold tracking-tight" id="welcome-title">
            เว็บไซต์นี้อยู่ในช่วงทดสอบระบบ
          </h2>
      
        </div>

        <button
          type="button"
          id="welcomeCloseBtn"
          class="inline-flex h-10 w-10 items-center justify-center rounded-full hover:bg-black/5 focus:outline-none focus:ring-2 focus:ring-black/20"
          aria-label="ปิดหน้าต่าง"
        >
          <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 6l12 12M18 6L6 18" />
          </svg>
        </button>
      </div>

      <div class="px-6 py-5 text-sm leading-relaxed">
        <p>
          เว็บไซต์นี้อยู่ในช่วงทดสอบระบบ ทีมงานกทม. ในหน่วยงานต่างๆ ได้แก่
        </p>

        <ul class="mt-3 list-disc space-y-1 pl-6">
          <li>ศูนย์นันทนาการ</li>
          <li>ศูนย์บริการผู้สูงอายุ</li>
          <li>ศูนย์ฝึกอาชีพกทม.(สังกัดสำนักงานเขต)</li>
          <li>โรงเรียนฝึกอาชีพ</li>
        </ul>

        <p class="mt-4">
          กรุณาตรวจสอบข้อมูลความถูกต้องของเนื้อหา และหากพบว่าข้อมูลส่วนไดผิด
          กรุณาคลิกที่ปุ่ม <span class="font-semibold">แจ้งแก้ไขข้อมูล</span>
          บริเวณด้านล่างขวาของเว็บไซต์
        </p>

      </div>

      <div class="flex flex-col-reverse gap-2 px-6 py-4 border-t sm:flex-row sm:justify-end" style="border-color: var(--modal-border);">
        <button
          type="button"
          id="welcomeOkBtn"
          class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold"
          style="background: var(--modal-primary); color: var(--modal-primary-fg);"
        >
          รับทราบ
        </button>

        <button
          type="button"
          id="welcomeHideBtn"
          class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold border hover:bg-black/5"
          style="border-color: var(--modal-border);"
          title="ซ่อนและไม่แสดงอีกในเบราว์เซอร์นี้"
        >
          ไม่ต้องแสดงอีก
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById("welcomeModal");
    const closeBtn = document.getElementById("welcomeCloseBtn");
    const okBtn = document.getElementById("welcomeOkBtn");
    const hideBtn = document.getElementById("welcomeHideBtn");
    const STORAGE_KEY = "welcome_modal_dismissed_v1";

    function openModal() {
      modal.classList.remove("hidden");
      document.body.style.overflow = "hidden";
    }
    function closeModal() {
      modal.classList.add("hidden");
      document.body.style.overflow = "";
    }

    if (!localStorage.getItem(STORAGE_KEY)) {
      window.addEventListener("load", () => setTimeout(openModal, 120));
    }

    closeBtn.addEventListener("click", closeModal);
    okBtn.addEventListener("click", closeModal);
    hideBtn.addEventListener("click", () => {
      localStorage.setItem(STORAGE_KEY, "1");
      closeModal();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !modal.classList.contains("hidden")) closeModal();
    });

    // clicking backdrop
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });
  })();
</script>


<!-- Floating "แจ้งแก้ไข" Button + Popup (Tailwind) -->
<div class="fixed bottom-5 right-5 z-999999">
  <!-- Floating button -->
  <button
    id="reportFab"
    type="button"
    class="group inline-flex items-center gap-2 rounded-full bg-gray-900 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-black focus:outline-none focus:ring-2 focus:ring-black/30"
    aria-haspopup="dialog"
    aria-controls="reportModal"
  >
    <!-- pencil icon -->
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M12 20h9" />
      <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5z" />
    </svg>
    แจ้งแก้ไข
  </button>
</div>

<!-- Modal -->
<div
  id="reportModal"
  class="fixed inset-0 z-99999999 hidden"
  role="dialog"
  aria-modal="true"
  aria-labelledby="reportTitle"
>
  <!-- backdrop -->
  <div id="reportBackdrop" class="absolute inset-0 bg-black/50 backdrop-blur-[2px]"></div>

  <!-- panel -->
  <div class="relative flex min-h-full items-end justify-center p-4 sm:items-center">
    <div
      class="w-full max-w-xl rounded-2xl bg-white shadow-2xl"
    >
      <!-- header -->
      <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4">
        <div class="min-w-0">
          <h2 id="reportTitle" class="text-base font-semibold text-gray-900">
            แจ้งแก้ไขข้อมูล
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            กรุณากรอกข้อมูลเพื่อแจ้งแก้ไข/แจ้งปัญหา
          </p>
        </div>

        <button
          id="reportClose"
          type="button"
          class="inline-flex h-10 w-10 items-center justify-center rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-black/20"
          aria-label="ปิดหน้าต่าง"
        >
          <svg viewBox="0 0 24 24" class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 6l12 12M18 6L6 18" />
          </svg>
        </button>
      </div>

      <!-- body -->
      <div class="max-h-[70vh] overflow-y-auto px-5 py-4">
        <!-- ✅ Paste Ninja Forms embed here -->
          <div class="w-full">
                <iframe
                    src="https://docs.google.com/forms/d/e/1FAIpQLSdrRMvJhwNZt0fZW1ZM6LAiDbq6YWARBK7NHAb4fob-Z9QmKQ/viewform?embedded=true"
                    class="h-[70vh] w-full rounded-xl border border-gray-200"
                    frameborder="0"
                    marginheight="0"
                    marginwidth="0"
                >
                    กำลังโหลดแบบฟอร์ม…
                </iframe>

        </div>
      </div>

      <!-- footer -->
      <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-5 py-4">
        <button
          type="button"
          id="reportCancel"
          class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-black/20"
        >
          ปิด
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const fab = document.getElementById("reportFab");
    const modal = document.getElementById("reportModal");
    const backdrop = document.getElementById("reportBackdrop");
    const closeBtn = document.getElementById("reportClose");
    const cancelBtn = document.getElementById("reportCancel");

    // Focus management (basic)
    let lastActiveEl = null;

    function openModal() {
      lastActiveEl = document.activeElement;
      modal.classList.remove("hidden");
      document.body.style.overflow = "hidden";
      // focus close for accessibility
      setTimeout(() => closeBtn.focus(), 0);
    }

    function closeModal() {
      modal.classList.add("hidden");
      document.body.style.overflow = "";
      if (lastActiveEl && typeof lastActiveEl.focus === "function") lastActiveEl.focus();
    }

    fab.addEventListener("click", openModal);
    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);

    // click backdrop to close
    backdrop.addEventListener("click", closeModal);

    // ESC to close
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !modal.classList.contains("hidden")) {
        closeModal();
      }
    });
  })();
</script>


</body>



</html>