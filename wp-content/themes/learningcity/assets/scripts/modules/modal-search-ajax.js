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

  function setMeta(text) {
    if (meta) meta.textContent = text;
  }

  async function requestSearch(query) {
    if (activeController) activeController.abort();
    activeController = new AbortController();

    const fd = new FormData();
    fd.append('action', 'lc_modal_search');
    fd.append('nonce', cfg.nonce);
    fd.append('q', query || '');

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

    return json.data;
  }

  async function runSearch(query) {
    const q = String(query || '').trim();
    setMeta(q ? `ผลลัพธ์สำหรับ “${q}”` : 'พิมพ์เพื่อค้นหาแบบเรียลไทม์');

    try {
      const data = await requestSearch(q);

      if (q.length >= 2 && window.LCAnalytics && typeof window.LCAnalytics.trackSearch === 'function') {
        window.LCAnalytics.trackSearch(q);
      }

      renderListItems(listNextlearn, data.nextlearn || []);
      renderListItems(listLocations, data.locations || []);
    } catch (error) {
      if (error && error.name === 'AbortError') return;
      renderListItems(listNextlearn, []);
      renderListItems(listLocations, []);
      setMeta('เกิดข้อผิดพลาดในการค้นหา');
    }
  }

  function scheduleSearch() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => runSearch(input.value), 220);
  }

  input.addEventListener('input', scheduleSearch);

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-modal-id="modal-search"]');
    if (!trigger) return;

    setTimeout(() => {
      input.focus();
      runSearch(input.value);
    }, 40);
  });

  if (window.location.hash === '#modal-search') {
    setTimeout(() => {
      input.focus();
      runSearch(input.value);
    }, 220);
  }
})();
