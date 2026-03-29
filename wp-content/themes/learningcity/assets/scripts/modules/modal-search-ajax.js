(function () {
  const cfg = window.LC_MODAL_SEARCH || {};
  const input = document.getElementById('lc-modal-search-input');
  const meta = document.getElementById('lc-modal-search-meta');
  const modal = document.querySelector('[data-modal-content="modal-search"]');

  if (!input || !modal || !cfg.ajax_url || !cfg.nonce) return;

  const listNextlearn = modal.querySelector('[data-list="nextlearn"]');
  const listLocations = modal.querySelector('[data-list="locations"]');

  let activeController = null;
  let debounceTimer = null;
  let lastCompletedQuery = null;
  let lastRenderedQuery = null;
  const responseCache = new Map();
  let loadingCount = 0;

  function esc(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function renderListItems(container, items) {
    if (!container) return;

    if (!Array.isArray(items) || !items.length) {
      container.innerHTML = '<div class="lc-search-empty">ไม่พบข้อมูล</div>';
      return;
    }

    container.innerHTML = items
      .map((item) => {
        const badge = item.badge ? `<span class="lc-search-item__badge">${esc(item.badge)}</span>` : '';
        return `<a class="lc-search-item" href="${esc(item.url)}"><span class="lc-search-item__head"><span class="lc-search-item__title">${esc(item.title)}</span>${badge}</span></a>`;
      })
      .join('');
  }

  function setMeta(text, isLoading = false) {
    if (!meta) return;

    if (isLoading) {
      meta.innerHTML = `
        <span style="display:inline-flex;align-items:center;gap:8px;">
          <span style="display:inline-flex;width:14px;height:14px;animation:lc-modal-search-spin 0.8s linear infinite;">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.2" stroke-width="3"></circle>
              <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
            </svg>
          </span>
          <span>${esc(text)}</span>
        </span>
      `;
      return;
    }

    meta.textContent = text;
  }

  function ensureSpinnerStyle() {
    if (document.getElementById('lc-modal-search-spinner-style')) return;

    const style = document.createElement('style');
    style.id = 'lc-modal-search-spinner-style';
    style.textContent = '@keyframes lc-modal-search-spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
    document.head.appendChild(style);
  }

  function setLoadingState(isLoading, query) {
    if (isLoading) {
      loadingCount += 1;
      ensureSpinnerStyle();
      setMeta(query ? `กำลังค้นหา “${query}”` : 'กำลังเตรียมผลลัพธ์', true);
      return;
    }

    loadingCount = Math.max(0, loadingCount - 1);
  }

  async function requestSearch(query) {
    const normalizedQuery = String(query || '').trim();
    if (responseCache.has(normalizedQuery)) {
      return responseCache.get(normalizedQuery);
    }

    if (activeController) activeController.abort();
    activeController = new AbortController();

    const fd = new FormData();
    fd.append('action', 'lc_modal_search');
    fd.append('nonce', cfg.nonce);
    fd.append('q', normalizedQuery);

    const response = await fetch(cfg.ajax_url, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      signal: activeController.signal,
    });

    const json = await response.json();
    if (!json || !json.success) {
      throw new Error((json && json.data && json.data.message) || 'Search failed');
    }

    responseCache.set(normalizedQuery, json.data);
    return json.data;
  }

  async function runSearch(query) {
    const q = String(query || '').trim();
    if (q === lastCompletedQuery && q === lastRenderedQuery && responseCache.has(q)) {
      return;
    }

    setLoadingState(true, q);

    try {
      const data = await requestSearch(q);
      lastCompletedQuery = q;

      if (q.length >= 2 && window.LCAnalytics && typeof window.LCAnalytics.trackSearch === 'function') {
        window.LCAnalytics.trackSearch(q);
      }

      renderListItems(listNextlearn, data.nextlearn || []);
      renderListItems(listLocations, data.locations || []);
      lastRenderedQuery = q;
      setLoadingState(false, q);
      setMeta(q ? `ผลลัพธ์สำหรับ “${q}”` : 'พิมพ์เพื่อค้นหาแบบเรียลไทม์');
    } catch (error) {
      if (error && error.name === 'AbortError') {
        setLoadingState(false, q);
        return;
      }
      renderListItems(listNextlearn, []);
      renderListItems(listLocations, []);
      setLoadingState(false, q);
      setMeta('เกิดข้อผิดพลาดในการค้นหา');
    }
  }

  function scheduleSearch() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      const q = String(input.value || '').trim();

      if (q.length === 1) {
        setMeta('พิมพ์อย่างน้อย 2 ตัวอักษรเพื่อค้นหา');
        listNextlearn.innerHTML = '';
        listLocations.innerHTML = '';
        lastRenderedQuery = null;
        return;
      }

      runSearch(q);
    }, 320);
  }

  input.addEventListener('input', scheduleSearch);

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-modal-id="modal-search"]');
    if (!trigger) return;

    setTimeout(() => {
      input.focus();
      const q = String(input.value || '').trim();
      if (q !== lastRenderedQuery) {
        runSearch(q);
      }
    }, 40);
  });

  if (window.location.hash === '#modal-search') {
    setTimeout(() => {
      input.focus();
      const q = String(input.value || '').trim();
      if (q !== lastRenderedQuery) {
        runSearch(q);
      }
    }, 220);
  }
})();
