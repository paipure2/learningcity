(function () {
  const cfg = window.LC_MODAL_SEARCH || {};
  const input = document.getElementById('lc-modal-search-input');
  const meta = document.getElementById('lc-modal-search-meta');
  const modal = document.querySelector('[data-modal-content="modal-search"]');

  if (!input || !modal || !cfg.ajax_url || !cfg.nonce) return;

  const quickTitle = modal.querySelector('#lc-search-quick-title');
  const listQuick = modal.querySelector('[data-list="quick"]');
  const listNextlearn = modal.querySelector('[data-list="nextlearn"]');
  const listLocations = modal.querySelector('[data-list="locations"]');

  const HISTORY_KEY = 'lc_modal_search_history_v1';
  const HISTORY_MAX = 8;

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

  function readHistory() {
    try {
      const raw = localStorage.getItem(HISTORY_KEY);
      const arr = raw ? JSON.parse(raw) : [];
      if (!Array.isArray(arr)) return [];
      return arr.filter((v) => typeof v === 'string' && v.trim() !== '').slice(0, HISTORY_MAX);
    } catch (e) {
      return [];
    }
  }

  function writeHistory(history) {
    try {
      localStorage.setItem(HISTORY_KEY, JSON.stringify(history.slice(0, HISTORY_MAX)));
    } catch (e) {
      // ignore storage errors
    }
  }

  function pushHistory(query) {
    const q = String(query || '').trim();
    if (q.length < 2) return;

    const current = readHistory().filter((v) => v.toLowerCase() !== q.toLowerCase());
    current.unshift(q);
    writeHistory(current);
  }

  function renderQuickChips(chips, emptyText) {
    if (!listQuick) return;

    if (!Array.isArray(chips) || !chips.length) {
      listQuick.innerHTML = `<div class="lc-search-empty">${esc(emptyText || 'ยังไม่มีข้อมูล')}</div>`;
      return;
    }

    listQuick.innerHTML = `<div class="lc-search-chip-wrap">${chips
      .map((label) => `<button type="button" class="lc-search-chip" data-chip-query="${esc(label)}">${esc(label)}</button>`)
      .join('')}</div>`;
  }

  function renderListItems(container, items) {
    if (!container) return;

    if (!Array.isArray(items) || !items.length) {
      container.innerHTML = '<div class="lc-search-empty">ไม่พบข้อมูล</div>';
      return;
    }

    container.innerHTML = items
      .map((item) => {
        const subtitle = item.subtitle ? `<div class="lc-search-item__sub">${esc(item.subtitle)}</div>` : '';
        return `<a class="lc-search-item" href="${esc(item.url)}"><span class="lc-search-item__title">${esc(
          item.title
        )}</span>${subtitle}</a>`;
      })
      .join('');
  }

  function setMeta(text) {
    if (meta) meta.textContent = text;
  }

  function renderQuickSection(popularKeywords) {
    const history = readHistory();

    if (history.length > 0) {
      if (quickTitle) quickTitle.textContent = 'ประวัติการค้นหา';
      renderQuickChips(history, 'ยังไม่มีประวัติการค้นหา');
      return;
    }

    if (quickTitle) quickTitle.textContent = 'คำค้นหาบ่อย';
    renderQuickChips(popularKeywords || [], 'ยังไม่มีคำค้นหาบ่อย');
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

      if (q.length >= 2 && ((data.nextlearn || []).length > 0 || (data.locations || []).length > 0)) {
        pushHistory(q);
      }

      renderQuickSection(data.popular_keywords || []);
      renderListItems(listNextlearn, data.nextlearn || []);
      renderListItems(listLocations, data.locations || []);
    } catch (error) {
      if (error && error.name === 'AbortError') return;
      renderQuickSection([]);
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

  modal.addEventListener('click', (event) => {
    const chip = event.target.closest('[data-chip-query]');
    if (!chip) return;

    const q = chip.getAttribute('data-chip-query') || '';
    input.value = q;
    input.focus();
    runSearch(q);
  });

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
