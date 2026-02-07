/**
 * course-modal-ajax.js
 * - AJAX load course modal content
 * - URL share: ?course_modal=ID#modal-course
 * - Works with existing modal.js (which sets #modal-course)
 * - Accordion in modal
 * - Re-init icons after AJAX
 * - Copy link button (COPY/OPEN = single permalink)
 */

(() => {
  const cache = new Map();
  let controller = null;

  // ✅ หน่วง skeleton หลังข้อมูลมาแล้ว
  const SKELETON_DELAY_MS = 200;
  let skeletonTimer = null;

  const CFG = window.COURSE_MODAL || {};
  if (!CFG.ajax_url || !CFG.nonce) {
    console.warn('[course-modal-ajax] COURSE_MODAL missing:', CFG);
  }

  function qs(sel) { return document.querySelector(sel); }

  function getModalEl() {
    return qs('[data-modal-content="modal-course"]');
  }

  // ✅ ใช้ class is-loading ให้เข้ากับ CSS:
  // .skeleton { display:none }
  // .skeleton.is-loading { display:block }
  function setSkeleton(modal, isLoading) {
    const sk = modal.querySelector('[data-course-skeleton]');
    const body = modal.querySelector('[data-course-modal-body]');

    if (sk) sk.classList.toggle('is-loading', !!isLoading);
    if (body) body.style.display = isLoading ? 'none' : '';
  }

  function clearSkeletonTimer() {
    if (skeletonTimer) {
      clearTimeout(skeletonTimer);
      skeletonTimer = null;
    }
  }

  function hideSkeletonAfterDelay(modal, courseId) {
    clearSkeletonTimer();
    skeletonTimer = setTimeout(() => {
      setSkeleton(modal, false);
      document.dispatchEvent(new CustomEvent('courseModal:loaded', { detail: { courseId } }));
    }, SKELETON_DELAY_MS);
  }

  // แชร์แบบเปิด modal (ยังใช้กับ URL bar ได้) แต่ "ไม่" ใช้กับปุ่ม copy/open
  function buildModalUrl(courseId) {
    const base = CFG.archive_url || window.location.pathname;
    const u = new URL(base, window.location.origin);
    u.searchParams.set('course_modal', String(courseId));
    u.hash = 'modal-course';
    return u.toString();
  }

  function getCourseIdFromUrl() {
    const u = new URL(window.location.href);
    return u.searchParams.get('course_modal');
  }

  async function fetchCourse(courseId) {
    const fd = new FormData();
    fd.append('action', 'load_course_modal');
    fd.append('nonce', CFG.nonce || '');
    fd.append('course_id', String(courseId || ''));

    controller = new AbortController();

    const res = await fetch(CFG.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: fd,
      signal: controller.signal,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    // อ่านเป็น text ก่อนเพื่อกันกรณี PHP ส่ง warning/HTML กลับมา
    const text = await res.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch (e) {
      console.error('[course-modal-ajax] Non-JSON response:', text);
      throw new Error('AJAX response is not JSON (check PHP error / admin-ajax output)');
    }

    if (!res.ok) {
      console.error('[course-modal-ajax] HTTP error:', res.status, json);
      throw new Error(json?.data?.message || `HTTP ${res.status}`);
    }

    if (!json.success) {
      console.error('[course-modal-ajax] success:false', json);
      throw new Error(json?.data?.message || 'Load failed');
    }

    return json.data; // { html, permalink, title, ... }
  }

  // ✅ ปุ่ม copy/open ต้องเป็น "single permalink"
  function updateTopLinks(modal, singleUrl) {
    const copyBtn = modal.querySelector('[data-course-copy-link]');
    const openA = modal.querySelector('[data-course-open-link]');

    if (copyBtn) copyBtn.setAttribute('data-copy-url', singleUrl || '');
    if (openA) openA.setAttribute('href', singleUrl || '');
  }

  async function loadIntoModal(courseId, fallbackUrl) {
    const modal = getModalEl();
    if (!modal) return;

    // ✅ ถ้ามีการโหลดรอบก่อนค้างอยู่ ให้เคลียร์ timeout กันปิดผิดจังหวะ
    clearSkeletonTimer();

    // ✅ แสดง skeleton ทันที
    setSkeleton(modal, true);

    // ✅ ยกเลิก request เก่า (ถ้ามี)
    if (controller) controller.abort();

    try {
      let data;
      if (cache.has(courseId)) {
        data = cache.get(courseId);
      } else {
        data = await fetchCourse(courseId);
        cache.set(courseId, data);
      }

      const body = modal.querySelector('[data-course-modal-body]');
      if (body) body.innerHTML = data.html;

      // ✅ ใช้ single permalink ให้ 2 ปุ่ม
      const singleUrl = data.permalink || fallbackUrl || '';
      updateTopLinks(modal, singleUrl);

      // ✅ หน่วงให้ skeleton อยู่ต่ออีก 2 วิ (ไม่รอรูป/วิดีโอ)
      hideSkeletonAfterDelay(modal, courseId);

    } catch (err) {
      if (err.name === 'AbortError') return;

      const body = modal.querySelector('[data-course-modal-body]');
      if (body) body.innerHTML = `<div class="py-6 text-center opacity-70">โหลดข้อมูลไม่สำเร็จ</div>`;

      // error ก็ยังให้ 2 ปุ่มไป single ถ้าเรามี fallbackUrl
      updateTopLinks(modal, fallbackUrl || '');

      // ✅ จะให้ error ก็หน่วง 2 วิเหมือนกัน
      hideSkeletonAfterDelay(modal, courseId);
    }
  }

  function bindCards() {
    document.querySelectorAll('.card-course[data-modal-id="modal-course"][data-course-id]').forEach((a) => {
      if (a.__bound) return;
      a.__bound = true;

      a.addEventListener('click', async (e) => {
        e.preventDefault();

        const courseId = a.getAttribute('data-course-id');
        const fallbackUrl = a.getAttribute('data-course-url') || a.getAttribute('href') || '';
        if (!courseId) return;

        // ✅ URL bar เป็นลิงก์แชร์แบบเปิด modal
        const shareUrl = buildModalUrl(courseId);
        try {
          history.pushState({ courseId }, '', shareUrl);
        } catch (_) {}

        // ✅ modal.js จะ replaceState เป็น pathname#modal-course
        // เลย replace กลับให้เป็น shareUrl หลังมันทำงาน
        setTimeout(() => {
          try {
            history.replaceState({ courseId }, '', shareUrl);
          } catch (_) {}
        }, 0);

        await loadIntoModal(courseId, fallbackUrl);
      }, { passive: false });
    });
  }

  // init
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindCards);
  } else {
    bindCards();
  }

  window.CourseModalAjax = { rebind: bindCards };

  // deep link load
  const deepId = getCourseIdFromUrl();
  if (deepId) {
    loadIntoModal(deepId, ''); // single permalink จะมาจาก AJAX เอง
  }

  // back/forward
  window.addEventListener('popstate', () => {
    const id = getCourseIdFromUrl();
    if (id) loadIntoModal(id, '');
  });
})();


// ===== Accordion (เฉพาะใน modal-course) =====
document.addEventListener("click", (e) => {
  const header = e.target.closest(".accordion-header");
  if (!header) return;

  const inCourseModal = header.closest('[data-modal-content="modal-course"]');
  if (!inCourseModal) return;

  const item = header.closest(".accordion-item");
  const panel = item?.querySelector(".accordion-panel");
  if (!item || !panel) return;

  item.classList.toggle("is-active");
  panel.style.maxHeight = item.classList.contains("is-active")
    ? panel.scrollHeight + "px"
    : "";
});

// icon init
document.addEventListener('courseModal:loaded', () => {
  if (window.initSvgInjections) window.initSvgInjections();
});

// copy link (คัดลอก single permalink ที่ถูก set ไว้)
document.addEventListener("click", async (e) => {
  const btn = e.target.closest("[data-course-copy-link]");
  if (!btn) return;

  e.preventDefault();

  let url = btn.getAttribute("data-copy-url");

  if (!url) {
    const modal = btn.closest('[data-modal-content="modal-course"]');
    const openA = modal?.querySelector("[data-course-open-link]");
    url = openA?.getAttribute("href");
  }

  if (!url) return;

  try {
    await navigator.clipboard.writeText(url);

    const textEl = btn.querySelector(".btn-text");
    const old = textEl?.textContent;

    if (textEl) textEl.textContent = "คัดลอกแล้ว";
    setTimeout(() => {
      if (textEl) textEl.textContent = old || "คัดลอกลิงก์";
    }, 1000);
  } catch (err) {
    const ta = document.createElement("textarea");
    ta.value = url;
    ta.style.position = "fixed";
    ta.style.left = "-9999px";
    document.body.appendChild(ta);
    ta.select();
    document.execCommand("copy");
    document.body.removeChild(ta);
  }
});
