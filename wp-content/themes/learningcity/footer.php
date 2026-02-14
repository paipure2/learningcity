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
          กรุณาตรวจสอบข้อมูลความถูกต้องของเนื้อหา
          และหากพบว่าข้อมูลส่วนใดผิดพลาดสามารถแจ้งทีมงานได้ตามช่องทางที่กำหนด
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



</body>



</html>
