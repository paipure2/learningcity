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

  function openCourseModal() {
    const modal = getModalEl();
    if (!modal) return;

    modal.classList.add('modal-active');
    document.body.setAttribute('data-scroll', 'hidden');

    const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = `${scrollbarWidth}px`;
  }

  function openFirstAccordionInModal(modal) {
    if (!modal) return;
    const firstItem = modal.querySelector(".accordion-item");
    if (!firstItem) return;

    const firstPanel = firstItem.querySelector(".accordion-panel");
    if (!firstPanel) return;

    modal.querySelectorAll(".accordion-item").forEach((it) => {
      it.classList.remove("is-active");
      const p = it.querySelector(".accordion-panel");
      if (p) p.style.maxHeight = "";
    });

    firstItem.classList.add("is-active");
    firstPanel.style.maxHeight = `${firstPanel.scrollHeight}px`;
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
      // Wait until content is visible before calculating panel height.
      requestAnimationFrame(() => openFirstAccordionInModal(modal));
      document.dispatchEvent(new CustomEvent('courseModal:loaded', { detail: { courseId } }));
    }, SKELETON_DELAY_MS);
  }

  // แชร์แบบเปิด modal (ยังใช้กับ URL bar ได้) แต่ "ไม่" ใช้กับปุ่ม copy/open
  function buildModalUrl(courseId) {
    // Use current page URL as base (archive/tax/search/etc.) so close can return correctly.
    const u = new URL(window.location.href);
    u.searchParams.set('course_modal', String(courseId));
    u.hash = 'modal-course';
    return u.toString();
  }

  function getCourseIdFromUrl() {
    const u = new URL(window.location.href);
    return u.searchParams.get('course_modal');
  }

  function cleanupCourseModalUrl() {
    try {
      const u = new URL(window.location.href);
      let changed = false;
      if (u.searchParams.has('course_modal')) {
        u.searchParams.delete('course_modal');
        changed = true;
      }
      if (u.hash === '#modal-course') {
        u.hash = '';
        changed = true;
      }
      if (!changed) return;
      const nextUrl = `${u.pathname}${u.search ? `?${u.searchParams.toString()}` : ''}${u.hash || ''}`;
      history.replaceState(null, '', nextUrl);
    } catch (_) {}
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

  async function onCourseCardClick(a, e) {
    if (!a) return;
    e.preventDefault();
    e.stopPropagation();

    const courseId = a.getAttribute('data-course-id');
    const fallbackUrl = a.getAttribute('data-course-url') || a.getAttribute('href') || '';
    if (!courseId) return;

    openCourseModal();

    const shareUrl = buildModalUrl(courseId);
    try {
      history.pushState({ courseId }, '', shareUrl);
    } catch (_) {}

    setTimeout(() => {
      try {
        history.replaceState({ courseId }, '', shareUrl);
      } catch (_) {}
    }, 0);

    await loadIntoModal(courseId, fallbackUrl);
  }

  function bindCards() {
    if (document.__lcCourseModalDelegatedBound) return;
    document.__lcCourseModalDelegatedBound = true;

    document.addEventListener('click', (e) => {
      const a = e.target.closest('.card-course[data-modal-id="modal-course"][data-course-id]');
      if (!a) return;
      onCourseCardClick(a, e);
    }, { passive: false });
  }

  function bindModalCloseUrlCleanup() {
    const modal = getModalEl();
    if (!modal || modal.__urlCleanupBound) return;
    modal.__urlCleanupBound = true;

    const maybeCleanup = () => {
      if (!modal.classList.contains('modal-active')) {
        cleanupCourseModalUrl();
      }
    };

    // Observe class changes from modal.js open/close flow.
    const observer = new MutationObserver(maybeCleanup);
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });

    // Extra safety for Escape/overlay close timing.
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      setTimeout(maybeCleanup, 0);
    });
  }

  // init
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      bindCards();
      bindModalCloseUrlCleanup();
    });
  } else {
    bindCards();
    bindModalCloseUrlCleanup();
  }

  window.CourseModalAjax = {
    rebind: bindCards,
    openFromCard: (cardEl, event) => {
      if (!cardEl) return;
      const e = event || {
        preventDefault() {},
        stopPropagation() {},
      };
      onCourseCardClick(cardEl, e);
    },
  };

  // deep link load
  const deepId = getCourseIdFromUrl();
  if (deepId) {
    openCourseModal();
    loadIntoModal(deepId, ''); // single permalink จะมาจาก AJAX เอง
  }

  // back/forward
  window.addEventListener('popstate', () => {
    const id = getCourseIdFromUrl();
    if (id) {
      openCourseModal();
      loadIntoModal(id, '');
    }
  });
})();

// ===== Distance sort for course sessions (single + modal) =====
(() => {
  const LOCATION_CACHE_KEY = "lc_user_location_v1";
  const LOCATION_CACHE_TTL_MS = 1000 * 60 * 60 * 24 * 30;

  function toNum(v) {
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
  }

  function haversineKm(lat1, lng1, lat2, lng2) {
    const toRad = (d) => d * Math.PI / 180;
    const R = 6371;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
      Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
  }

  function formatKm(km) {
    if (!Number.isFinite(km)) return "";
    if (km < 1) return `${Math.round(km * 1000)} ม.`;
    return `${km.toFixed(1)} กม.`;
  }

  function loadCachedLocation() {
    try {
      const raw = localStorage.getItem(LOCATION_CACHE_KEY);
      if (!raw) return null;
      const data = JSON.parse(raw);
      if (!data) return null;
      const lat = toNum(data.lat);
      const lng = toNum(data.lng);
      if (lat === null || lng === null) return null;
      const ts = toNum(data.ts);
      if (ts && Date.now() - ts > LOCATION_CACHE_TTL_MS) {
        localStorage.removeItem(LOCATION_CACHE_KEY);
        return null;
      }
      return { lat, lng, ts: toNum(data.ts) || Date.now() };
    } catch (_) {
      return null;
    }
  }

  function saveCachedLocation(lat, lng) {
    try {
      const prev = loadCachedLocation() || {};
      localStorage.setItem(LOCATION_CACHE_KEY, JSON.stringify({
        lat,
        lng,
        ts: Date.now(),
        near: typeof prev.near === "boolean" ? prev.near : false,
        radius: Number.isFinite(prev.radius) ? prev.radius : 5,
      }));
    } catch (_) {}
  }

  function setStatus(container, text) {
    const el = container.querySelector("[data-course-distance-status]");
    if (el) el.textContent = text || "";
  }

  function sortAndRenderByLocation(container, userLoc) {
    const list = container.querySelector("[data-course-location-list]");
    if (!list) return;
    const items = Array.from(list.querySelectorAll(".accordion-item[data-location-id]"));
    if (!items.length) return;

    items.forEach((item, idx) => {
      if (!item.dataset.initialIndex) item.dataset.initialIndex = String(idx);
      const lat = toNum(item.getAttribute("data-lat"));
      const lng = toNum(item.getAttribute("data-lng"));
      const district = (item.getAttribute("data-district-label") || "").trim();
      const badge = item.querySelector("[data-distance-badge]");

      if (!userLoc || lat === null || lng === null) {
        item.dataset.distanceKm = "";
        if (badge) badge.textContent = "";
        return;
      }

      const km = haversineKm(userLoc.lat, userLoc.lng, lat, lng);
      item.dataset.distanceKm = String(km);
      if (badge) {
        const distanceText = `ห่างจากคุณ ${formatKm(km)}`;
        badge.textContent = district ? `${district} | ${distanceText}` : distanceText;
      }
    });

    const sorted = items.slice().sort((a, b) => {
      if (!userLoc) {
        return Number(a.dataset.initialIndex || 0) - Number(b.dataset.initialIndex || 0);
      }
      const da = toNum(a.dataset.distanceKm);
      const db = toNum(b.dataset.distanceKm);
      if (da === null && db === null) return Number(a.dataset.initialIndex || 0) - Number(b.dataset.initialIndex || 0);
      if (da === null) return 1;
      if (db === null) return -1;
      return da - db;
    });

    sorted.forEach((item) => list.appendChild(item));
  }

  function bindDistanceContainer(container) {
    if (!container || container.dataset.distanceBound === "1") return;
    container.dataset.distanceBound = "1";

    const btnCurrent = container.querySelector("[data-course-distance-use-current]");
    const btnManual = container.querySelector("[data-course-distance-set-manual]");
    const btnClear = container.querySelector("[data-course-distance-clear]");

    function setCurrentButtonState(mode) {
      if (!btnCurrent) return;
      const isActive = mode === "active";
      const isLoading = mode === "loading";
      btnCurrent.disabled = isActive || isLoading;
      btnCurrent.textContent = isLoading
        ? "กำลังระบุตำแหน่ง..."
        : (isActive ? "ใช้ตำแหน่งปัจจุบันอยู่" : "ใช้ตำแหน่งปัจจุบัน");

      if (isActive || isLoading) {
        btnCurrent.classList.add("opacity-60", "cursor-not-allowed");
      } else {
        btnCurrent.classList.remove("opacity-60", "cursor-not-allowed");
      }
    }

    const applyCached = () => {
      const cached = loadCachedLocation();
      if (cached) {
        sortAndRenderByLocation(container, cached);
        setStatus(container, "");
        setCurrentButtonState("active");
      } else {
        sortAndRenderByLocation(container, null);
        setCurrentButtonState("idle");
      }
    };

    if (btnCurrent) {
      btnCurrent.addEventListener("click", () => {
        if (!navigator.geolocation) {
          setStatus(container, "อุปกรณ์ไม่รองรับตำแหน่งปัจจุบัน");
          setCurrentButtonState("idle");
          return;
        }
        setCurrentButtonState("loading");
        setStatus(container, "กำลังขอตำแหน่งปัจจุบัน...");
        navigator.geolocation.getCurrentPosition((pos) => {
          const lat = toNum(pos.coords.latitude);
          const lng = toNum(pos.coords.longitude);
          if (lat === null || lng === null) {
            setStatus(container, "อ่านตำแหน่งไม่สำเร็จ");
            setCurrentButtonState("idle");
            return;
          }
          saveCachedLocation(lat, lng);
          sortAndRenderByLocation(container, { lat, lng });
          setStatus(container, "เรียงลำดับใกล้ -> ไกล จากตำแหน่งปัจจุบัน");
          setCurrentButtonState("active");
        }, () => {
          setStatus(container, "ไม่ได้รับสิทธิ์ตำแหน่งปัจจุบัน");
          setCurrentButtonState("idle");
        }, {
          enableHighAccuracy: true,
          timeout: 12000,
          maximumAge: 120000,
        });
      });
    }

    if (btnManual) {
      btnManual.addEventListener("click", () => {
        const raw = window.prompt("กรอกพิกัดของคุณ: lat,lng", "");
        if (!raw) return;
        const parts = raw.split(",");
        if (parts.length !== 2) {
          setStatus(container, "รูปแบบไม่ถูกต้อง ตัวอย่าง 13.7563,100.5018");
          return;
        }
        const lat = toNum(parts[0].trim());
        const lng = toNum(parts[1].trim());
        if (lat === null || lng === null || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
          setStatus(container, "พิกัดไม่ถูกต้อง");
          return;
        }
        saveCachedLocation(lat, lng);
        sortAndRenderByLocation(container, { lat, lng });
        setStatus(container, "เรียงลำดับใกล้ -> ไกล จาก location ที่เลือก");
        setCurrentButtonState("active");
      });
    }

    if (btnClear) {
      btnClear.addEventListener("click", () => {
        try {
          localStorage.removeItem(LOCATION_CACHE_KEY);
        } catch (_) {}
        sortAndRenderByLocation(container, null);
        setStatus(container, "ล้างตำแหน่งแล้ว");
        setCurrentButtonState("idle");
      });
    }

    applyCached();
  }

  function initCourseSessionDistance(root = document) {
    root.querySelectorAll('[data-course-session-distance="1"]').forEach(bindDistanceContainer);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => initCourseSessionDistance(document));
  } else {
    initCourseSessionDistance(document);
  }

  document.addEventListener("courseModal:loaded", () => {
    const modal = document.querySelector('[data-modal-content="modal-course"]');
    if (modal) initCourseSessionDistance(modal);
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

  const wasActive = item.classList.contains("is-active");
  const allItems = inCourseModal.querySelectorAll(".accordion-item");
  allItems.forEach((it) => {
    it.classList.remove("is-active");
    const p = it.querySelector(".accordion-panel");
    if (p) p.style.maxHeight = "";
  });

  if (!wasActive) {
    item.classList.add("is-active");
    panel.style.maxHeight = panel.scrollHeight + "px";
  }
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
