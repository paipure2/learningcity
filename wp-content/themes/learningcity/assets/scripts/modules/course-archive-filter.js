const STORAGE_KEY = "lc_course_open_only";

function asInt(value, fallback = 1) {
  const n = parseInt(String(value ?? ""), 10);
  return Number.isNaN(n) ? fallback : n;
}

function getPageFromUrl(rawUrl) {
  try {
    const url = new URL(rawUrl, window.location.origin);

    const paged = asInt(url.searchParams.get("paged"), 0);
    if (paged > 0) return paged;

    const page = asInt(url.searchParams.get("page"), 0);
    if (page > 0) return page;

    const m = url.pathname.match(/\/page\/(\d+)\/?$/);
    if (m && m[1]) return asInt(m[1], 1);
  } catch (e) {
    return 1;
  }

  return 1;
}

function getStoredOpenOnly(defaultValue = true) {
  const raw = window.localStorage.getItem(STORAGE_KEY);
  if (raw === null) return defaultValue;
  return raw !== "0";
}

function setStoredOpenOnly(enabled) {
  window.localStorage.setItem(STORAGE_KEY, enabled ? "1" : "0");
}

function escapeHtml(str) {
  return String(str ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function buildUrlFromState({ selectors, openOnly, page }) {
  const url = new URL(window.location.href);

  ["course_category", "course_provider", "audience"].forEach((key) => {
    const value = selectors[key] || "";
    if (value) url.searchParams.set(key, value);
    else url.searchParams.delete(key);
  });

  if (openOnly) url.searchParams.delete("open_only");
  else url.searchParams.set("open_only", "0");

  if (selectors.q) url.searchParams.set("q", selectors.q);
  else url.searchParams.delete("q");

  if (page > 1) url.searchParams.set("paged", String(page));
  else url.searchParams.delete("paged");

  return `${url.pathname}?${url.searchParams.toString()}`.replace(/\?$/, "");
}

function initCourseArchiveFilter() {
  const root = document.getElementById("lc-archive-filters");
  const results = document.getElementById("lc-course-results");
  if (!root || !results || !window.LC_COURSE_FILTER) return;
  if (root.dataset.lcFilterBound === "1") return;
  root.dataset.lcFilterBound = "1";

  const ajaxUrl = window.LC_COURSE_FILTER.ajax_url;
  const nonce = window.LC_COURSE_FILTER.nonce;

  const contextTaxonomy = root.dataset.contextTaxonomy || "";
  const contextTerm = root.dataset.contextTerm || "";
  const openDefault = root.dataset.openDefault !== "0";

  const toggle = document.getElementById("lc-open-only-toggle");
  const openOnlyText = document.getElementById("lc-open-only-text");
  const countNode = document.querySelector("#lc-course-count strong");
  const loadingNode = document.getElementById("lc-filter-loading");
  const resetButton = document.getElementById("lc-filter-reset");
  const keywordInput = document.getElementById("lc-filter-keyword");
  const panelToggle = document.getElementById("lc-filter-panel-toggle");
  const mobileMq = window.matchMedia("(max-width: 768px)");

  const selectors = {
    course_category: root.querySelector('select[data-taxonomy="course_category"]'),
    course_provider: root.querySelector('select[data-taxonomy="course_provider"]'),
    audience: root.querySelector('select[data-taxonomy="audience"]'),
  };

  let isBusy = false;
  let typingTimer = null;
  let controller = null;
  let requestSeq = 0;

  function scrollToResultsTop() {
    const top = results.getBoundingClientRect().top + window.scrollY - 20;
    window.scrollTo({ top: Math.max(0, top), behavior: "smooth" });
  }

  function readFilterState() {
    return {
      page: 1,
      openOnly: !!toggle?.checked,
      course_category: selectors.course_category?.value || "",
      course_provider: selectors.course_provider?.value || "",
      audience: selectors.audience?.value || "",
      q: keywordInput?.value?.trim() || "",
    };
  }

  function setLoading(loading) {
    isBusy = loading;
    results.classList.toggle("is-loading", loading);
    if (loadingNode) loadingNode.hidden = !loading;
    if (toggle) toggle.disabled = loading;
    if (resetButton) resetButton.disabled = loading;

    Object.values(selectors).forEach((el) => {
      if (el) el.disabled = loading;
    });
  }

  function setPanelOpen(open) {
    if (!panelToggle) return;
    root.classList.toggle("is-mobile-open", !!open);
    panelToggle.setAttribute("aria-expanded", open ? "true" : "false");
  }

  function setOpenOnlyState(isOpenOnly) {
    if (!toggle) return;
    toggle.checked = !!isOpenOnly;
    if (openOnlyText) {
      openOnlyText.textContent = toggle.checked ? "รับสมัครอยู่" : "ทั้งหมด";
    }
  }

  function updateCount(value) {
    if (!countNode) return;
    countNode.textContent = new Intl.NumberFormat("th-TH").format(asInt(value, 0));
  }

  function applyOptions(optionsData, selectedState) {
    [ "course_category", "course_provider", "audience" ].forEach((taxonomy) => {
      const select = selectors[taxonomy];
      if (!select) return;

      const placeholder = select.dataset.placeholder || "ทั้งหมด";
      const options = Array.isArray(optionsData?.[taxonomy]) ? optionsData[taxonomy] : [];
      const wanted = selectedState?.[taxonomy] || "";

      const hasWanted = wanted && options.some((item) => item?.slug === wanted);
      let html = `<option value="">ทั้งหมด</option>`;
      options.forEach((item) => {
        if (!item?.slug) return;
        html += `<option value="${escapeHtml(item.slug)}">${escapeHtml(item.name || item.slug)}</option>`;
      });

      if (wanted && !hasWanted) {
        html += `<option value="${escapeHtml(wanted)}">${escapeHtml(wanted)}</option>`;
      }

      select.innerHTML = html;
      select.value = wanted || "";
      select.setAttribute("aria-label", placeholder);
    });
  }

  async function fetchCourses(page = 1, shouldPushState = true, scrollAfterLoad = false) {
    const state = readFilterState();
    state.page = page;
    const currentSeq = ++requestSeq;

    setStoredOpenOnly(state.openOnly);
    setLoading(true);
    if (controller) controller.abort();
    controller = new AbortController();

    const body = new URLSearchParams();
    body.set("action", "lc_filter_courses");
    body.set("nonce", nonce);
    body.set("page", String(state.page));
    body.set("open_only", state.openOnly ? "1" : "0");
    body.set("context_taxonomy", contextTaxonomy);
    body.set("context_term", contextTerm);
    body.set("course_category", state.course_category);
    body.set("course_provider", state.course_provider);
    body.set("audience", state.audience);
    body.set("q", state.q);

    try {
      const response = await fetch(ajaxUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        credentials: "same-origin",
        body: body.toString(),
        signal: controller.signal,
      });

      const json = await response.json();
      if (!response.ok || !json?.success) {
        throw new Error(json?.data?.message || "request_failed");
      }
      if (currentSeq !== requestSeq) return;

      results.innerHTML = json.data.html || "";
      updateCount(json.data.found_posts || 0);
      applyOptions(json.data.options || {}, state);
      if (window.initSvgInjections) {
        window.initSvgInjections();
      }
      if (window.initSwipers) {
        requestAnimationFrame(() => window.initSwipers());
      }
      if (window.CourseModalAjax?.rebind) {
        window.CourseModalAjax.rebind();
      }

      if (shouldPushState) {
        const nextUrl = buildUrlFromState({
          selectors: {
            course_category: state.course_category,
            course_provider: state.course_provider,
            audience: state.audience,
            q: state.q,
          },
          openOnly: state.openOnly,
          page: state.page,
        });
        window.history.replaceState({}, "", nextUrl);
      }

      if (scrollAfterLoad) {
        requestAnimationFrame(scrollToResultsTop);
      }
    } catch (error) {
      if (error?.name === "AbortError") return;
      console.error("[lc-course-filter]", error);
    } finally {
      if (currentSeq !== requestSeq) return;
      setLoading(false);
    }
  }

  const storedOpenOnly = getStoredOpenOnly(openDefault);
  setOpenOnlyState(storedOpenOnly);

  const initialOpenFromUrl = new URL(window.location.href).searchParams.get("open_only");
  if (initialOpenFromUrl === "0" && toggle) {
    setOpenOnlyState(false);
    setStoredOpenOnly(false);
  } else if (initialOpenFromUrl === "1" && toggle) {
    setOpenOnlyState(true);
    setStoredOpenOnly(true);
  }

  if (toggle) {
    toggle.addEventListener("change", () => {
      setOpenOnlyState(toggle.checked);
      fetchCourses(1, true);
    });
  }

  Object.values(selectors).forEach((el) => {
    if (!el) return;
    el.addEventListener("change", () => {
      fetchCourses(1, true);
    });
  });

  if (resetButton) {
    resetButton.addEventListener("click", () => {
      Object.values(selectors).forEach((el) => {
        if (el) el.value = "";
      });
      if (keywordInput) keywordInput.value = "";
      fetchCourses(1, true);
    });
  }

  if (keywordInput) {
    keywordInput.addEventListener("input", () => {
      if (typingTimer) clearTimeout(typingTimer);
      typingTimer = setTimeout(() => fetchCourses(1, true), 300);
    });
  }

  if (panelToggle) {
    panelToggle.addEventListener("click", () => {
      setPanelOpen(!root.classList.contains("is-mobile-open"));
    });
  }

  results.addEventListener("click", (event) => {
    const link = event.target.closest("a.page-numbers");
    if (!link || link.classList.contains("current") || link.classList.contains("dots")) return;

    event.preventDefault();
    const page = getPageFromUrl(link.getAttribute("href") || "");
    fetchCourses(page, true, true);
  });

  const hasPresetFilters = Object.values(selectors).some((el) => !!(el && el.value))
    || !!(keywordInput && keywordInput.value.trim());
  if (mobileMq.matches) {
    root.classList.add("is-mobile-collapsible");
    setPanelOpen(hasPresetFilters);
  } else {
    root.classList.remove("is-mobile-collapsible");
    setPanelOpen(true);
  }

  const onMediaChange = (event) => {
    if (event.matches) {
      root.classList.add("is-mobile-collapsible");
      setPanelOpen(false);
    } else {
      root.classList.remove("is-mobile-collapsible");
      setPanelOpen(true);
    }
  };

  if (typeof mobileMq.addEventListener === "function") {
    mobileMq.addEventListener("change", onMediaChange);
  } else if (typeof mobileMq.addListener === "function") {
    mobileMq.addListener(onMediaChange);
  }

  const currentOpenOnly = toggle ? !!toggle.checked : storedOpenOnly;
  if (currentOpenOnly !== openDefault || hasPresetFilters) {
    fetchCourses(1, false);
  }
}

async function navigateCourseAsideAjax(url, pushState = true) {
  const currentMain = document.getElementById("lc-course-main-content");
  if (!currentMain) {
    window.location.href = url;
    return;
  }

  try {
    currentMain.classList.add("is-loading");

    const response = await fetch(url, {
      credentials: "same-origin",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, "text/html");
    const nextMain = doc.getElementById("lc-course-main-content");
    if (!nextMain) {
      window.location.href = url;
      return;
    }

    currentMain.innerHTML = nextMain.innerHTML;
    document.title = doc.title || document.title;
    if (pushState) {
      window.history.pushState({ lcCourseAjax: true }, "", url);
    }

    if (window.initSvgInjections) window.initSvgInjections();
    if (window.initSwipers) requestAnimationFrame(() => window.initSwipers());
    if (window.CourseModalAjax?.rebind) window.CourseModalAjax.rebind();
    if (typeof window.initCourseArchiveFilter === "function") {
      window.initCourseArchiveFilter();
    } else {
      initCourseArchiveFilter();
    }

    const top = currentMain.getBoundingClientRect().top + window.scrollY - 20;
    window.scrollTo({ top: Math.max(0, top), behavior: "smooth" });
  } catch (error) {
    console.error("[lc-aside-ajax]", error);
    window.location.href = url;
  } finally {
    const main = document.getElementById("lc-course-main-content");
    if (main) main.classList.remove("is-loading");
  }
}

function initCourseAsideAjaxNav() {
  if (window.__LC_ASIDE_AJAX_BOUND) return;
  window.__LC_ASIDE_AJAX_BOUND = true;

  const closeModalCategoryIfOpen = () => {
    const modal = document.querySelector('[data-modal-content="modal-category"]');
    if (!modal) return;
    modal.classList.remove("modal-active");

    const hasActiveModal = !!document.querySelector(".modal.modal-active");
    if (!hasActiveModal) {
      document.body.removeAttribute("data-scroll");
      document.body.style.overflow = "";
      document.body.style.paddingRight = "";
    }
  };

  document.addEventListener("click", (event) => {
    const link = event.target.closest(
      ".aside a[href], .lc-subcat-scroll a[href], [data-modal-content='modal-category'] a[href]"
    );
    if (!link) return;
    if (link.hasAttribute("target") || link.hasAttribute("download")) return;
    if (link.closest("[data-modal-id]")) return;

    let url;
    try {
      url = new URL(link.href, window.location.origin);
    } catch (_) {
      return;
    }
    if (url.origin !== window.location.origin) return;

    // intercept only course archive/tax links from sidebar
    const path = url.pathname.toLowerCase();
    const isCoursePath =
      path === "/course" ||
      path === "/course/" ||
      path.includes("/course/") ||
      path.includes("/course_category/") ||
      path.includes("/course_provider/") ||
      path.includes("/audience/");
    if (!isCoursePath) return;

    event.preventDefault();
    if (link.closest("[data-modal-content='modal-category']")) {
      closeModalCategoryIfOpen();
    }
    navigateCourseAsideAjax(url.toString(), true);
  });

  window.addEventListener("popstate", () => {
    const current = window.location.href;
    navigateCourseAsideAjax(current, false);
  });
}

window.initCourseArchiveFilter = initCourseArchiveFilter;

document.addEventListener("DOMContentLoaded", () => {
  initCourseArchiveFilter();
  initCourseAsideAjaxNav();
});
