<?php
if (!defined('ABSPATH')) exit;
?>
<script>
  // ================= CONFIG =================
  const MAPTILER_KEY = "A9j0Af0Z3BiCSKcrWllM";
  const MAPTILER_STYLE = `https://api.maptiler.com/maps/streets-v2/style.json?key=${MAPTILER_KEY}`;
  const SITE_PATH = "<?php echo esc_js((string) wp_parse_url(home_url(), PHP_URL_PATH)); ?>";
  const SITE_BASE = window.location.origin + (SITE_PATH || "");
  const LOCAL_GLYPHS_BASE = `${SITE_BASE}/wp-content/themes/learningcity/assets/fonts/map-font`;
  // Must match the folder name exactly (see MapLibre Font Maker output)
  const LOCAL_GLYPH_FONT = "Anuphan-SemiBold";

  const LIST_PAGE_SIZE = 10;

  const CLUSTER_MAX_ZOOM = 14;
  const CLUSTER_RADIUS = 55;

  const SHOW_LABEL_ZOOM = 14;

  const appRoot = document.getElementById("blmApp");
  const appMode = appRoot?.dataset?.mode || "map";
  const singlePlaceId = Number(appRoot?.dataset?.placeId || 0);
  const isSingleMode = appMode === "single" && Number.isFinite(singlePlaceId) && singlePlaceId > 0;
  const REPORT_AJAX_URL = "<?php echo esc_js(admin_url('admin-ajax.php')); ?>";
  const REPORT_NONCE = "<?php echo esc_js(wp_create_nonce('lc_report_location')); ?>";
  const LOCATION_CACHE_KEY = "lc_user_location_v1";
  const LOCATION_CACHE_TTL_MS = 1000 * 60 * 60 * 24 * 30; // 30 days

  let cachedNear = null;
  let cachedRadius = null;

  // ================= SVG ICONS =================
  const ICON_SVGS = {
    default: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 21s7-4.5 7-11a7 7 0 0 0-14 0c0 6.5 7 11 7 11z"/>
        <circle cx="12" cy="10" r="2"/>
      </svg>
    `,
    library: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 19a2 2 0 0 0 2 2h12"/>
        <path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2z"/>
        <path d="M8 7h6"/>
        <path d="M8 11h6"/>
      </svg>
    `,
    museum: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 10l9-6 9 6"/>
        <path d="M5 10v10"/>
        <path d="M9 10v10"/>
        <path d="M15 10v10"/>
        <path d="M19 10v10"/>
        <path d="M3 20h18"/>
      </svg>
    `,
    park: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 2l-3 7h6l-3-7z"/>
        <path d="M12 9l-4 6h8l-4-6z"/>
        <path d="M12 15v7"/>
        <path d="M9 22h6"/>
      </svg>
    `,
    learning_center: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M22 10L12 4 2 10l10 6 10-6z"/>
        <path d="M6 12v5c0 1 3 3 6 3s6-2 6-3v-5"/>
      </svg>
    `,
    science: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10 2v6l-4 8a4 4 0 0 0 4 6h4a4 4 0 0 0 4-6l-4-8V2"/>
        <path d="M8 14h8"/>
      </svg>
    `,
    art: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 22c4.4 0 8-3.6 8-8 0-6-8-12-8-12S4 8 4 14c0 4.4 3.6 8 8 8z"/>
        <path d="M8.5 14.5h.01"/>
        <path d="M12 11h.01"/>
        <path d="M15.5 14.5h.01"/>
      </svg>
    `,
    history: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M6 3h12v18H6z"/>
        <path d="M8 7h8"/>
        <path d="M8 11h8"/>
        <path d="M8 15h6"/>
      </svg>
    `,
    kids: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="7" r="3"/>
        <path d="M5 21a7 7 0 0 1 14 0"/>
      </svg>
    `,
    community: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 21V10l9-6 9 6v11"/>
        <path d="M9 21v-6h6v6"/>
      </svg>
    `,
    coworking: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3" y="4" width="18" height="12" rx="2"/>
        <path d="M8 20h8"/>
        <path d="M12 16v4"/>
      </svg>
    `,
    sport: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M13 5a2 2 0 1 0-2 2"/>
        <path d="M7 22l2-6 3-3 4 2 2 7"/>
        <path d="M10 13l-2-3 3-3 4 2"/>
      </svg>
    `,
    book_house: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 11l9-7 9 7"/>
        <path d="M5 11v9h14v-9"/>
        <path d="M8 13h5"/>
        <path d="M8 17h5"/>
      </svg>
    `,
    museum_kids: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 10l9-6 9 6"/>
        <path d="M5 10v10"/>
        <path d="M9 10v10"/>
        <path d="M15 10v10"/>
        <path d="M19 10v10"/>
        <path d="M3 20h18"/>
        <circle cx="18.5" cy="5.5" r="2"/>
        <path d="M18.5 7.5v2"/>
      </svg>
    `,
    museum_local: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 10l9-6 9 6"/>
        <path d="M5 10v10"/>
        <path d="M9 10v10"/>
        <path d="M15 10v10"/>
        <path d="M19 10v10"/>
        <path d="M3 20h18"/>
        <path d="M12 4v6"/>
        <path d="M12 4h4l-1 2 1 2h-4"/>
      </svg>
    `,
    indie_bookstore: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 7h6a2 2 0 0 1 2 2v10a2 2 0 0 0-2-2H4z"/>
        <path d="M20 7h-6a2 2 0 0 0-2 2v10a2 2 0 0 1 2-2h6z"/>
        <path d="M16.5 11.5s1.5-1.5 2.5 0c1 1.5-2.5 3.5-2.5 3.5s-3.5-2-2.5-3.5c1-1.5 2.5 0 2.5 0z"/>
      </svg>
    `,
    vocational_school: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 7h8l4 4v8H4z"/>
        <path d="M12 7v4h4"/>
        <path d="M7 15l4 4"/>
        <path d="M13 13l-2 2"/>
      </svg>
    `,
    bma_school: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 10l9-6 9 6"/>
        <path d="M5 10v9h14v-9"/>
        <path d="M10 19v-5h4v5"/>
        <path d="M12 4v6"/>
        <path d="M12 4h4l-1 2 1 2h-4"/>
      </svg>
    `,
    sports_field: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3" y="5" width="18" height="14" rx="2"/>
        <path d="M12 5v14"/>
        <circle cx="12" cy="12" r="2"/>
      </svg>
    `,
    sports_center: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 10v4"/>
        <path d="M7 9v6"/>
        <path d="M10 12h4"/>
        <path d="M14 12h4"/>
        <path d="M17 9v6"/>
        <path d="M20 10v4"/>
      </svg>
    `,
    recreation_center: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="5" r="2"/>
        <path d="M4 11l4-3"/>
        <path d="M20 11l-4-3"/>
        <path d="M8 8l4 3 4-3"/>
        <path d="M12 11v8"/>
        <path d="M8 19h8"/>
      </svg>
    `,
    senior_center: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="9" cy="6" r="2"/>
        <path d="M9 8v6"/>
        <path d="M9 14l-3 6"/>
        <path d="M9 14l3 4"/>
        <path d="M15 11v8"/>
        <path d="M15 19h2"/>
      </svg>
    `,
    child_dev_center: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="8" r="4"/>
        <path d="M10 14h4"/>
        <path d="M12 14v2"/>
        <path d="M10 16h4"/>
      </svg>
    `,
    public_park: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 3l-3 6h6l-3-6z"/>
        <path d="M7 11h10"/>
        <path d="M6 11v7"/>
        <path d="M18 11v7"/>
        <path d="M9 18h6"/>
      </svg>
    `,
    art_gallery: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="4" y="5" width="16" height="14" rx="2"/>
        <path d="M8 9h8"/>
        <path d="M8 13h5"/>
      </svg>
    `,
    online: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3" y="5" width="18" height="12" rx="2"/>
        <path d="M8 21h8"/>
        <path d="M12 17v4"/>
        <path d="M8 9c1.5-1.5 4.5-1.5 6 0"/>
        <path d="M6.5 7.5c2.5-2.5 8.5-2.5 11 0"/>
      </svg>
    `
  };

  // ================= CATEGORY META =================
  const DEFAULT_CATEGORY_COLOR = "#10b981";
  const CATEGORY_COLOR_PALETTE = [
    "#2563eb", "#7c3aed", "#16a34a", "#f59e0b", "#06b6d4", "#ec4899",
    "#64748b", "#f97316", "#0ea5e9", "#111827", "#ef4444", "#14b8a6"
  ];

  const CATEGORY_META = {
    library: { label: "ห้องสมุด", iconKey: "library", color: "#2563eb" },
    museum: { label: "พิพิธภัณฑ์", iconKey: "museum", color: "#7c3aed" },
    park: { label: "สวน/ธรรมชาติ", iconKey: "park", color: "#16a34a" },
    learning_center: { label: "ศูนย์เรียนรู้", iconKey: "learning_center", color: "#f59e0b" },
    science: { label: "วิทยาศาสตร์", iconKey: "science", color: "#06b6d4" },
    art: { label: "ศิลปะ", iconKey: "art", color: "#ec4899" },
    history: { label: "ประวัติศาสตร์", iconKey: "history", color: "#64748b" },
    kids: { label: "เด็ก/ครอบครัว", iconKey: "kids", color: "#f97316" },
    community: { label: "ชุมชน", iconKey: "community", color: "#0ea5e9" },
    coworking: { label: "Co-working", iconKey: "coworking", color: "#111827" },
    sport: { label: "กีฬา", iconKey: "sport", color: "#ef4444" }
  };

  const CATEGORY_LABEL_META = {
    "บ้านหนังสือ": { iconKey: "book_house", color: "#1d4ed8" },
    "พิพิธภัณฑ์เด็ก": { iconKey: "museum_kids", color: "#f97316" },
    "พิพิธภัณฑ์ท้องถิ่น": { iconKey: "museum_local", color: "#7c3aed" },
    "ร้านหนังสืออิสระ": { iconKey: "indie_bookstore", color: "#0ea5e9" },
    "โรงเรียนฝึกอาชีพ": { iconKey: "vocational_school", color: "#f59e0b" },
    "โรงเรียนสังกัดกทม.": { iconKey: "bma_school", color: "#14b8a6" },
    "ลานกีฬา": { iconKey: "sports_field", color: "#ef4444" },
    "ศูนย์กีฬา": { iconKey: "sports_center", color: "#dc2626" },
    "ศูนย์นันทนาการ": { iconKey: "recreation_center", color: "#a855f7" },
    "ศูนย์บริการผู้สูงอายุ": { iconKey: "senior_center", color: "#64748b" },
    "ศูนย์พัฒนาเด็กเล็ก": { iconKey: "child_dev_center", color: "#22c55e" },
    "สวนสาธารณะ": { iconKey: "public_park", color: "#16a34a" },
    "ห้องสมุด": { iconKey: "library", color: "#2563eb" },
    "หอศิลป์": { iconKey: "art_gallery", color: "#ec4899" },
    "ออนไลน์": { iconKey: "online", color: "#0f172a" }
  };

  const CATEGORY_ICON_RULES = [
    { test: /(บ้านหนังสือ)/, iconKey: "book_house" },
    { test: /(พิพิธภัณฑ์เด็ก)/, iconKey: "museum_kids" },
    { test: /(พิพิธภัณฑ์ท้องถิ่น)/, iconKey: "museum_local" },
    { test: /(ร้านหนังสืออิสระ)/, iconKey: "indie_bookstore" },
    { test: /(โรงเรียนฝึกอาชีพ)/, iconKey: "vocational_school" },
    { test: /(โรงเรียนสังกัดกทม\.?)/, iconKey: "bma_school" },
    { test: /(ลานกีฬา)/, iconKey: "sports_field" },
    { test: /(ศูนย์กีฬา)/, iconKey: "sports_center" },
    { test: /(ศูนย์นันทนาการ)/, iconKey: "recreation_center" },
    { test: /(ศูนย์บริการผู้สูงอายุ)/, iconKey: "senior_center" },
    { test: /(ศูนย์พัฒนาเด็กเล็ก)/, iconKey: "child_dev_center" },
    { test: /(สวนสาธารณะ)/, iconKey: "public_park" },
    { test: /(ห้องสมุด|library|lib)/, iconKey: "library" },
    { test: /(หอศิลป์|gallery|art)/, iconKey: "art_gallery" },
    { test: /(ออนไลน์|online)/, iconKey: "online" },
    { test: /(museum|พิพิธภัณฑ์)/, iconKey: "museum" },
    { test: /(park|สวน|ธรรมชาติ|ป่า|สนาม)/, iconKey: "park" },
    { test: /(learning|ศูนย์เรียนรู้|center|centre)/, iconKey: "learning_center" },
    { test: /(science|วิทยาศาสตร์|stem|lab)/, iconKey: "science" },
    { test: /(history|ประวัติศาสตร์|heritage)/, iconKey: "history" },
    { test: /(kid|เด็ก|family|ครอบครัว)/, iconKey: "kids" },
    { test: /(community|ชุมชน|ประชาคม)/, iconKey: "community" },
    { test: /(cowork|co-working|workspace|office|ออฟฟิศ|ทำงานร่วม)/, iconKey: "coworking" },
    { test: /(sport|กีฬา|สนามกีฬา|ฟิตเนส|gym)/, iconKey: "sport" }
  ];

  const ICON_POOL = [
    "book_house","museum_kids","museum_local","indie_bookstore","vocational_school","bma_school",
    "sports_field","sports_center","recreation_center","senior_center","child_dev_center","public_park",
    "library","art_gallery","online","museum","park","learning_center","science","art","history","kids",
    "community","coworking","sport"
  ];

  function hashString(s) {
    let h = 0;
    for (let i = 0; i < s.length; i++) h = ((h << 5) - h) + s.charCodeAt(i);
    return Math.abs(h);
  }

  function pickIconKeyForCategory(slug, label) {
    const text = `${slug || ""} ${label || ""}`.toLowerCase();
    for (const r of CATEGORY_ICON_RULES) if (r.test.test(text)) return r.iconKey;
    return ICON_POOL[hashString(text) % ICON_POOL.length] || "default";
  }

  function pickColorForCategory(slug, label) {
    const text = `${slug || ""} ${label || ""}`;
    return CATEGORY_COLOR_PALETTE[hashString(text) % CATEGORY_COLOR_PALETTE.length] || DEFAULT_CATEGORY_COLOR;
  }

  let categoryMetaIndex = null;

  function rebuildCategoryMetaIndex() {
    const next = {};
    const apiCats = (filtersData?.categories || []).filter(x => x?.slug);
    apiCats.forEach(({ slug, name }) => {
      const base = CATEGORY_META[slug] || {};
      const label = name || base.label || slug || "อื่นๆ";
      const labelMeta = CATEGORY_LABEL_META[label] || {};
      const iconKey = labelMeta.iconKey || base.iconKey || pickIconKeyForCategory(slug, label);
      const color = labelMeta.color || base.color || pickColorForCategory(slug, label);
      next[slug] = { label, iconKey, color };
    });

    Object.keys(CATEGORY_META).forEach(slug => {
      if (next[slug]) return;
      const base = CATEGORY_META[slug];
      next[slug] = {
        label: base.label || slug,
        iconKey: base.iconKey || pickIconKeyForCategory(slug, base.label),
        color: base.color || pickColorForCategory(slug, base.label)
      };
    });

    if (!next.default) {
      next.default = { label: "อื่นๆ", iconKey: "default", color: DEFAULT_CATEGORY_COLOR };
    }

    categoryMetaIndex = next;
  }

  // ================= STATE =================
  let allPlaces = [];
  let filtersData = null; // NEW: from /wp-json/blm/v1/filters
  let map;

  let selectedId = null;
  let copyToastTimer = null;

  let hoverAnimRaf = null;
  let hoverAnimT0 = 0;
  let activeAnimRaf = null;
  let activeAnimT0 = 0;

  let userLocation = null; // {lat, lng}
  let meMarker = null;

  // ================= FULL DATA =================
  const fullCache = new Map();
  async function loadFullForId(id) {
    if (fullCache.has(id)) return fullCache.get(id);
    try {
      setApiLoading(true, "กำลังโหลดรายละเอียด...", "drawer");
      const res = await fetch(`/learningcity/wp-json/blm/v1/location/${id}`);
      if (!res.ok) return null;
      const full = await res.json();
      fullCache.set(id, full);
      return full;
    } finally {
      setApiLoading(false, "", "drawer");
    }
  }

  const state = {
    district: "",
    categories: new Set(),
    tags: new Set(),       // age_range slugs
    amenities: new Set(),  // facility slugs
    admissionPolicies: new Set(), // admission_policy slugs
    courseCategories: new Set(), // course_category slugs
    nearMeEnabled: false,
    radiusKm: 5
  };

  let searchQuery = "";
  let mobileView = "map";
  let isSearching = false;
  const DRAWER_ANIM_MS = 280;
  let drawerHideTimer = null;

  let listLimit = LIST_PAGE_SIZE;
  let lastVisible = [];

  const PLACES_SOURCE_ID = "places-src";
  const LAYER_CLUSTER_CIRCLE = "places-cluster-circle";
  const LAYER_CLUSTER_COUNT  = "places-cluster-count";
  const LAYER_UNCLUSTERED    = "places-unclustered";
  const LAYER_UNCLUSTERED_ACTIVE_RING = "places-unclustered-active-ring";
  const LAYER_UNCLUSTERED_ACTIVE = "places-unclustered-active";
  const LAYER_UNCLUSTERED_LABEL = "places-unclustered-label";

  // ================= HELPERS =================
  function setApiLoading(on, text = "กำลังโหลดข้อมูล...", scope = "global") {
    const box = scope === "drawer" ? el("drawerLoading") : el("apiLoading");
    if (!box) return;
    const label = box.querySelector("div.text-sm");
    if (label && on && text) label.textContent = text;
    box.classList.toggle("hidden", !on);
  }

  const el = (id) => document.getElementById(id);
  const norm = (s) => (s || "").toString().trim().toLowerCase();
  const decodeHtmlEntities = (raw) => {
    const src = (raw || "").toString();
    if (!src) return "";
    const t = document.createElement("textarea");
    let out = src;
    // Decode twice to handle values like &amp;#8211;
    for (let i = 0; i < 2; i += 1) {
      t.innerHTML = out;
      const next = t.value;
      if (next === out) break;
      out = next;
    }
    return out;
  };

  function isMobile() {
    return window.matchMedia("(max-width: 1023px)").matches;
  }

  function debounce(fn, wait = 250) {
    let t = null;
    return (...args) => {
      if (t) clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  // iOS sometimes drops click on overlayed UI; bind a safe tap handler
  function bindTap(node, handler) {
    if (!node || !handler) return;
    let lastTouch = 0;

    node.addEventListener("touchend", (e) => {
      lastTouch = Date.now();
      handler(e);
    }, { passive: true });

    node.addEventListener("pointerup", (e) => {
      if (e.pointerType === "touch") return;
      handler(e);
    });

    node.addEventListener("click", (e) => {
      if (Date.now() - lastTouch < 600) return;
      handler(e);
    });
  }

  // ================= URL SYNC =================
  const urlState = { initialMap: null, isApplying: false };

  function _parseList(v) {
    if (!v) return [];
    return String(v).split(",").map(s => s.trim()).filter(Boolean);
  }
  function _toCsv(setOrArr) {
    if (!setOrArr) return "";
    const arr = Array.isArray(setOrArr) ? setOrArr : Array.from(setOrArr);
    return arr.filter(Boolean).join(",");
  }
  function _setOrDelete(sp, key, value) {
    const empty = value === null || value === undefined || value === "" ||
      (Array.isArray(value) && value.length === 0);
    if (empty) sp.delete(key);
    else sp.set(key, String(value));
  }
  function _setCsvOrDelete(sp, key, setOrArr) {
    const csv = _toCsv(setOrArr);
    if (!csv) sp.delete(key);
    else sp.set(key, csv);
  }

  function getCurrentUrlState() {
    return {
      q: (searchQuery || "").trim(),
      district: state.district || "",
      categories: Array.from(state.categories || []),
      tags: Array.from(state.tags || []),
      amenities: Array.from(state.amenities || []),
      admission: Array.from(state.admissionPolicies || []),
      course_categories: Array.from(state.courseCategories || []),
      near: state.nearMeEnabled ? 1 : 0,
      radius: Number.isFinite(state.radiusKm) ? state.radiusKm : 5,
      place: selectedId ? String(selectedId) : "",
      lat: map ? Number(map.getCenter().lat.toFixed(6)) : (urlState.initialMap?.lat ?? ""),
      lng: map ? Number(map.getCenter().lng.toFixed(6)) : (urlState.initialMap?.lng ?? ""),
      zoom: map ? Number(map.getZoom().toFixed(2)) : (urlState.initialMap?.zoom ?? ""),
    };
  }

  function writeUrlFromState(mode = "replace") {
    if (urlState.isApplying) return;

    const s = getCurrentUrlState();
    const sp = new URLSearchParams(window.location.search);

    _setOrDelete(sp, "q", s.q);
    _setOrDelete(sp, "district", s.district);
    _setCsvOrDelete(sp, "categories", s.categories);
    _setCsvOrDelete(sp, "tags", s.tags);
    _setCsvOrDelete(sp, "amenities", s.amenities);
    _setCsvOrDelete(sp, "admission", s.admission);
    _setCsvOrDelete(sp, "course_categories", s.course_categories);

    _setOrDelete(sp, "near", s.near ? "1" : "");
    _setOrDelete(sp, "radius", s.near ? String(s.radius) : "");

    _setOrDelete(sp, "place", s.place);

    _setOrDelete(sp, "lat", s.lat !== "" ? String(s.lat) : "");
    _setOrDelete(sp, "lng", s.lng !== "" ? String(s.lng) : "");
    _setOrDelete(sp, "zoom", s.zoom !== "" ? String(s.zoom) : "");

    const newUrl = window.location.pathname + (sp.toString() ? `?${sp.toString()}` : "");
    const payload = s;

    if (mode === "push") window.history.pushState(payload, "", newUrl);
    else window.history.replaceState(payload, "", newUrl);
  }

  function loadCachedLocation() {
    try {
      const raw = localStorage.getItem(LOCATION_CACHE_KEY);
      if (!raw) return;
      const data = JSON.parse(raw);
      if (!data || !Number.isFinite(data.lat) || !Number.isFinite(data.lng)) return;
      if (data.ts && Date.now() - data.ts > LOCATION_CACHE_TTL_MS) {
        localStorage.removeItem(LOCATION_CACHE_KEY);
        return;
      }
      userLocation = { lat: data.lat, lng: data.lng };
      cachedNear = typeof data.near === "boolean" ? data.near : null;
      cachedRadius = Number.isFinite(data.radius) ? data.radius : null;
    } catch (e) {
      // ignore cache errors
    }
  }

  function saveLocationCache() {
    if (!userLocation) return;
    try {
      const payload = {
        lat: userLocation.lat,
        lng: userLocation.lng,
        ts: Date.now(),
        near: !!state.nearMeEnabled,
        radius: Number.isFinite(state.radiusKm) ? state.radiusKm : 5,
      };
      localStorage.setItem(LOCATION_CACHE_KEY, JSON.stringify(payload));
    } catch (e) {
      // ignore cache errors
    }
  }

  function readUrlToState() {
    const sp = new URLSearchParams(window.location.search);
    const hasNear = sp.has("near");
    const hasRadius = sp.has("radius");
    return {
      q: sp.get("q") || "",
      district: sp.get("district") || "",
      categories: _parseList(sp.get("categories")),
      tags: _parseList(sp.get("tags")),
      amenities: _parseList(sp.get("amenities")),
      admission: _parseList(sp.get("admission")),
      course_categories: _parseList(sp.get("course_categories")),
      near: hasNear ? sp.get("near") === "1" : (cachedNear ?? false),
      radius: hasRadius ? Number(sp.get("radius")) : (Number.isFinite(cachedRadius) ? cachedRadius : 5),
      place: sp.get("place") || "",
      map: {
        lat: sp.get("lat") ? Number(sp.get("lat")) : null,
        lng: sp.get("lng") ? Number(sp.get("lng")) : null,
        zoom: sp.get("zoom") ? Number(sp.get("zoom")) : null,
      }
    };
  }

  function applyStateFromUrl() {
    const u = readUrlToState();
    urlState.isApplying = true;

    searchQuery = u.q;
    el("q").value = u.q;

    state.district = u.district;
    el("district").value = u.district;

    state.categories.clear();
    u.categories.forEach(c => state.categories.add(c));

    state.tags.clear();
    u.tags.forEach(t => state.tags.add(t));

    state.amenities.clear();
    u.amenities.forEach(a => state.amenities.add(a));
    state.admissionPolicies.clear();
    u.admission.forEach(a => state.admissionPolicies.add(a));
    state.courseCategories.clear();
    u.course_categories.forEach(a => state.courseCategories.add(a));

    // sync dynamic UIs
    syncTagButtonsUI();
    syncFacilityUI();
    syncAdmissionUI();
    syncCourseCategoryUI();

    state.nearMeEnabled = !!u.near;
    state.radiusKm = Number.isFinite(u.radius) ? u.radius : 5;
    el("radiusKm").value = state.radiusKm;

    el("nearMeRadiusWrap").classList.toggle("hidden", !state.nearMeEnabled);
    el("chipNearMe").textContent = state.nearMeEnabled ? "ใกล้ฉัน: เปิด" : "ใกล้ฉัน: ปิด";
    el("chipNearMe").className = state.nearMeEnabled
      ? "px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700"
      : "px-3 py-2 rounded-lg border text-sm font-semibold hover:bg-slate-50";

    if (u.map.lat != null && u.map.lng != null && u.map.zoom != null) {
      urlState.initialMap = { lat: u.map.lat, lng: u.map.lng, zoom: u.map.zoom };
    } else {
      urlState.initialMap = null;
      if (u.place) {
        const id = Number(u.place);
        const p = allPlaces.find(x => x.id === id);
        if (p && typeof p.lat === "number" && typeof p.lng === "number") {
          urlState.initialMap = { lat: p.lat, lng: p.lng, zoom: 16 };
        }
      }
    }

    renderCategoryUIs();

    resetListLimit();
    refresh();

    if (u.place) {
      const id = Number(u.place);
      const p = allPlaces.find(x => x.id === id);
      if (p) openDrawer(p, { forceMapOnMobile: true });
    } else {
      closeDrawer();
    }

    urlState.isApplying = false;
    writeUrlFromState("replace");
  }

  window.addEventListener("popstate", () => {
    applyStateFromUrl();
  });

  const writeUrlDebounced = debounce(() => writeUrlFromState("replace"), 350);
  // ================= END URL SYNC =================

  function haversineKm(lat1, lon1, lat2, lon2) {
    const toRad = (d) => d * Math.PI / 180;
    const R = 6371;
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    const a =
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
      Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  }

  function formatKm(km) {
    if (km == null || !Number.isFinite(km)) return "-";
    if (km < 1) return `${Math.round(km * 1000)} ม.`;
    return `${km.toFixed(km < 10 ? 1 : 0)} กม.`;
  }

  // ✅ category label from taxonomy
  function taxonomyCategoryName(slug) {
    const it = (filtersData?.categories || []).find(x => x.slug === slug);
    return it?.name || "";
  }

  function catMeta(key) {
    const taxName = taxonomyCategoryName(key);
    const meta = (categoryMetaIndex && categoryMetaIndex[key]) || CATEGORY_META[key] || {};
    return {
      label: taxName || meta.label || key || "อื่นๆ",
      iconKey: meta.iconKey || "default",
      color: meta.color || DEFAULT_CATEGORY_COLOR
    };
  }

  function getPlaceCategories(place) {
    if (Array.isArray(place.categories) && place.categories.length) return place.categories;
    if (place.category) return [place.category];
    return [];
  }

  function getPrimaryCategory(place) {
    return place.category || getPlaceCategories(place)[0] || "";
  }

  function getIconKeyFromCategory(category) {
    const meta = catMeta(category);
    return meta.iconKey || "default";
  }

  function getSvgByKey(iconKey) {
    return ICON_SVGS[iconKey] || ICON_SVGS.default;
  }

  function svgForDom(iconKey, sizeClass = "icon-18") {
    return `<span class="${sizeClass}">${getSvgByKey(iconKey)}</span>`;
  }

  const DRAWER_META_ICONS = {
    address: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 0 0-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2"/></svg>`,
    gmaps: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4"/><path d="M16 6V4"/><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M8 14h8"/></svg>`,
    phone: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.7.7 2.5a2 2 0 0 1-.4 2.1L8.1 9.6a16 16 0 0 0 6.3 6.3l1.3-1.3a2 2 0 0 1 2.1-.4c.8.3 1.6.6 2.5.7a2 2 0 0 1 1.7 2z"/></svg>`,
    hours: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>`,
    admission: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V9z"/><path d="M12 7v12"/></svg>`,
    tags: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 19a6 6 0 0 1 12 0"/><circle cx="17" cy="9" r="2.5"/><path d="M14 19a4.5 4.5 0 0 1 7 0"/></svg>`,
    facebook: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 8.5 3.5L22 13"/><path d="M14 11a5 5 0 0 0-8.5-3.5L2 11"/><path d="M8 16l8-8"/></svg>`
  };

  function renderDrawerMetaIcons() {
    const map = [
      ["iconAddress", "address"],
      ["iconGmaps", "gmaps"],
      ["iconPhone", "phone"],
      ["iconHours", "hours"],
      ["iconAdmission", "admission"],
      ["iconTags", "tags"],
      ["iconFacebook", "facebook"]
    ];
    map.forEach(([id, key]) => {
      const holder = el(id);
      if (holder) holder.innerHTML = `<span class="icon-24">${DRAWER_META_ICONS[key]}</span>`;
    });
  }

  function computeDistances() {
    if (!userLocation) {
      allPlaces.forEach(p => { delete p._distanceKm; });
      return;
    }
    for (const p of allPlaces) {
      p._distanceKm = (typeof p.lat === "number" && typeof p.lng === "number")
        ? haversineKm(userLocation.lat, userLocation.lng, p.lat, p.lng)
        : null;
    }
  }

  function hasAllAmenities(place, selected) {
    if (selected.size === 0) return true;
    const a = new Set(place.amenities || []);
    for (const x of selected) if (!a.has(x)) return false;
    return true;
  }

  function matchesFilters(place) {
    if (state.district && place.district !== state.district) return false;
    if (state.categories.size > 0) {
      const cats = new Set(getPlaceCategories(place));
      let ok = false;
      for (const c of state.categories) {
        if (cats.has(c)) { ok = true; break; }
      }
      if (!ok) return false;
    }

    // tags: OR logic (เลือกหลายอัน = ผ่านถ้ามีอย่างน้อย 1)
    if (state.tags.size > 0) {
      const t = new Set(place.tags || []);
      let ok = false;
      for (const x of state.tags) if (t.has(x)) { ok = true; break; }
      if (!ok) return false;
    }

    // amenities: AND logic (เลือกหลายอัน = ต้องมีครบ)
    if (!hasAllAmenities(place, state.amenities)) return false;

    // admission policy: OR logic (เลือกหลายอัน = ผ่านถ้ามีอย่างน้อย 1)
    if (state.admissionPolicies.size > 0) {
      const a = new Set(place.admission_policies || []);
      let ok = false;
      for (const x of state.admissionPolicies) if (a.has(x)) { ok = true; break; }
      if (!ok) return false;
    }

    if (state.nearMeEnabled) {
      if (!userLocation || !Number.isFinite(place._distanceKm)) return false;
      if (Number.isFinite(state.radiusKm) && place._distanceKm > state.radiusKm) return false;
    }

    // course categories: OR logic (เลือกหลายอัน = ผ่านถ้ามีอย่างน้อย 1)
    if (state.courseCategories.size > 0) {
      const c = new Set(place.course_categories || []);
      const p = new Set(place.course_category_parents || []);
      const parentSet = new Set((filtersData?.course_categories || []).filter(x => !x.parent).map(x => x.slug));
      let ok = false;
      for (const x of state.courseCategories) {
        if (parentSet.has(x)) {
          if (p.has(x)) { ok = true; break; }
        } else if (c.has(x)) { ok = true; break; }
      }
      if (!ok) return false;
    }
    return true;
  }

  function isInBounds(place) {
    return map.getBounds().contains([place.lng, place.lat]);
  }

  function resetListLimit() {
    listLimit = LIST_PAGE_SIZE;
  }

  // ================= MOBILE VIEW =================
  function setMobileView(view) {
    mobileView = view;

    const mapTab = el("tabMap");
    const listTab = el("tabList");
    const listMobile = el("listSectionMobile");
    const mapSection = el("mapSection");

    if (view === "map") {
      mapTab.className = "px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 text-white";
      listTab.className = "px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-white";

      listMobile.classList.add("hidden");
      mapSection.classList.remove("hidden");

      setTimeout(() => map && map.resize(), 60);
    } else {
      mapTab.className = "px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-white";
      listTab.className = "px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 text-white";

      listMobile.classList.remove("hidden");
      mapSection.classList.remove("hidden");
    }
  }


  // ================= SIDEBAR =================
  function openSidebarMobile() {
    el("sidebarOverlay").classList.remove("hidden");
    el("sidebar").classList.remove("-translate-x-full");
  }
  function closeSidebarMobile() {
    el("sidebarOverlay").classList.add("hidden");
    el("sidebar").classList.add("-translate-x-full");
  }
  function closeSidebarIfMobile() {
    if (isMobile()) closeSidebarMobile();
  }

  // ================= ACTIVE FILTER CHIPS =================
  function renderActiveFilters(targetId) {
    const wrap = document.getElementById(targetId);
    if (!wrap) return;
    wrap.innerHTML = "";
    const chips = [];

    const labelForAge = (slug) => {
      const it = (filtersData?.age_ranges || []).find(x => x.slug === slug);
      return it?.name || slug;
    };
    const labelForFacility = (slug) => {
      const it = (filtersData?.facilities || []).find(x => x.slug === slug);
      return it?.name || slug;
    };

    if (state.district) {
      chips.push({
        label: `เขต${state.district}`,
        clear: () => { state.district = ""; el("district").value = ""; }
      });
    }

    for (const cat of state.categories) {
      const meta = catMeta(cat);
      chips.push({
        label: `${meta.label}`,
        clear: () => { state.categories.delete(cat); renderCategoryUIs(); }
      });
    }

    for (const tag of state.tags) {
      chips.push({
        label: `ช่วงวัย: ${labelForAge(tag)}`,
        clear: () => { state.tags.delete(tag); syncTagButtonsUI(); }
      });
    }

    for (const am of state.amenities) {
      chips.push({
        label: `มี ${labelForFacility(am)}`,
        clear: () => { state.amenities.delete(am); syncFacilityUI(); }
      });
    }

    const labelForAdmission = (slug) => {
      const it = (filtersData?.admission_policies || []).find(x => x.slug === slug);
      const name = it?.name || slug;
      return name === "ไม่มีค่าบริการ" ? "ฟรี" : name;
    };

    for (const ap of state.admissionPolicies) {
      chips.push({
        label: `ราคา: ${labelForAdmission(ap)}`,
        clear: () => { state.admissionPolicies.delete(ap); syncAdmissionUI(); }
      });
    }

    const labelForCourseCat = (slug) => {
      const it = (filtersData?.course_categories || []).find(x => x.slug === slug);
      return it?.name || slug;
    };

    for (const cc of state.courseCategories) {
      chips.push({
        label: `คอร์ส: ${labelForCourseCat(cc)}`,
        clear: () => { state.courseCategories.delete(cc); syncCourseCategoryUI(); }
      });
    }

    if (state.nearMeEnabled) {
      chips.push({
        label: `ใกล้ฉัน ≤ ${state.radiusKm} กม.`,
        clear: () => {
          state.nearMeEnabled = false;
          el("nearMeRadiusWrap").classList.add("hidden");
          el("chipNearMe").textContent = "ใกล้ฉัน: ปิด";
          el("chipNearMe").className = "px-3 py-2 rounded-lg border text-sm font-semibold";
        }
      });
    }

    if (chips.length === 0) {
      wrap.innerHTML = "";
      return;
    }

    chips.forEach(c => {
      const chip = document.createElement("button");
      chip.className = "filter-chip";
      chip.innerHTML = `<span>${c.label}</span><span class="filter-chip__x">✕</span>`;
      chip.onclick = () => {
        c.clear();
        resetListLimit();
        refresh();
        writeUrlFromState("push");
      };
      wrap.appendChild(chip);
    });
  }

  function updateFilterCount() {
    const elCount = el("filtersCount");
    if (!elCount) return;
    let count = 0;
    if (state.district) count += 1;
    count += state.categories.size;
    count += state.tags.size;
    count += state.amenities.size;
    if (state.nearMeEnabled) count += 1;
    count += state.admissionPolicies.size;
    count += state.courseCategories.size;
    elCount.textContent = String(count);
  }

  // ================= SEARCH UX =================
  function updateSearchStatusUI() {
    const hasText = searchQuery.trim().length > 0;
    if (hasText) {
      el("searchStatus").classList.remove("hidden");
      el("searchText").textContent = searchQuery.trim();
    } else {
      el("searchStatus").classList.add("hidden");
    }
  }

  function getGlobalSearchResults() {
    const q = searchQuery.trim();
    if (!q) return [];
    const res = allPlaces
      .filter(p => typeof p.lat === "number" && typeof p.lng === "number")
      .filter(p => {
        const hay = norm((p.name||"") + " " + (p.address||"") + " " + (p.district||""));
        return hay.includes(norm(q));
      });
    if (userLocation) res.sort((a,b) => (a._distanceKm ?? 1e9) - (b._distanceKm ?? 1e9));
    else res.sort((a,b) => (a.name||"").localeCompare(b.name||"", "th"));
    return res.slice(0, 8);
  }

  function renderSearchPanel() {
    const panel = el("searchPanel");
    const box = el("searchResults");

    if (!isSearching || !searchQuery.trim()) {
      panel.classList.add("hidden");
      return;
    }

    const items = getGlobalSearchResults();
    panel.classList.remove("hidden");
    box.innerHTML = "";

    if (items.length === 0) {
      box.innerHTML = `<div class="p-3 text-sm text-slate-500">ไม่พบผลลัพธ์</div>`;
      return;
    }

    items.forEach(p => {
      const dist = userLocation ? formatKm(p._distanceKm) : "";
      const primaryCat = getPrimaryCategory(p);
      const meta = catMeta(primaryCat);
      const iconKey = getIconKeyFromCategory(primaryCat);
      const districtText = (p.district || "").trim();
      const subText = districtText ? `${meta.label} : ${districtText}` : meta.label;

      const row = document.createElement("button");
      row.className = "w-full text-left px-3 py-3 hover:bg-slate-50 border-b last:border-b-0";
      row.innerHTML = `
        <div class="search-result-row">
          <span class="result-icon" style="color: ${meta.color}">${svgForDom(iconKey, "icon-18")}</span>
          <div class="result-main">
            <div class="result-title">${p.name}</div>
            <div class="result-meta-row">
              <div class="result-sub truncate">${subText}</div>
              <div class="result-distance">${dist}</div>
            </div>
          </div>
        </div>
      `;

      row.onclick = () => {
        isSearching = false;
        closeSearchPanel();

        if (map && typeof p.lng === "number" && typeof p.lat === "number") {
          map.flyTo({
            center: [p.lng, p.lat],
            zoom: Math.max(map.getZoom(), 20),
            speed: 1.2,
            curve: 1.4,
            essential: true
          });
        }

        openDrawer(p, { forceMapOnMobile: true });
        closeSidebarIfMobile();
      };

      box.appendChild(row);
    });
  }

  function closeSearchPanel() {
    el("searchPanel").classList.add("hidden");
  }

  function skeletonPills(count = 6) {
    return Array.from({ length: count }, (_, i) => {
      const widths = [84, 98, 74, 112, 88, 126];
      return `<span class="blm-skeleton blm-skeleton-pill" style="width:${widths[i % widths.length]}px"></span>`;
    }).join("");
  }

  function renderFilterSkeletons() {
    const district = el("district");
    if (district) {
      district.disabled = true;
      district.innerHTML = `<option value="">กำลังโหลด...</option>`;
    }

    const wraps = [
      ["ageRangeWrap", 5],
      ["facilityWrap", 8],
      ["admissionWrap", 3],
      ["courseCatWrap", 5]
    ];
    wraps.forEach(([id, count]) => {
      const wrap = el(id);
      if (!wrap) return;
      wrap.innerHTML = skeletonPills(count);
    });
  }

  function clearFilterSkeletons() {
    const district = el("district");
    if (district) district.disabled = false;
  }

  // ================= DYNAMIC FILTER UIs (NEW) =================
  function renderAgeRangeButtons() {
    const wrap = el("ageRangeWrap");
    if (!wrap) return;

    wrap.innerHTML = "";
    const items = (filtersData?.age_ranges || [])
      .map(x => ({ value: x.slug, label: x.name }))
      .filter(x => x.value);

    if (!items.length) {
      wrap.innerHTML = `<div class="text-xs text-slate-500">ไม่มีข้อมูลช่วงวัย</div>`;
      return;
    }

    items.forEach(({ value, label }) => {
      const btn = document.createElement("button");
      btn.className = "tagBtn px-3 py-1 rounded-full border text-sm";
      btn.dataset.tag = value;
      btn.textContent = label;

      if (state.tags.has(value)) {
        btn.classList.add("bg-emerald-600", "text-white", "border-emerald-600");
      }
      wrap.appendChild(btn);
    });
  }

  const FACILITY_TOP_N = 10;

  function makePill({ value, label, active }) {
    const btn = document.createElement("button");
    btn.className =
      "amPill px-3 py-1 rounded-full border text-sm " +
      (active ? "bg-emerald-600 text-white border-emerald-600" : "bg-white hover:bg-slate-50");
    btn.dataset.am = value;
    btn.textContent = label;
    return btn;
  }

  function getFacilitiesSorted() {
    const items = (filtersData?.facilities || [])
      .map(x => ({
        value: x.slug,
        label: x.name,
        count: Number(x.count ?? 0)
      }))
      .filter(x => x.value);

    items.sort((a,b) => (b.count - a.count) || a.label.localeCompare(b.label, "th"));
    return items;
  }

  function renderFacilityPillsTop() {
    const wrap = el("facilityWrap");
    if (!wrap) return;

    wrap.innerHTML = "";
    const items = getFacilitiesSorted();

    if (!items.length) {
      wrap.innerHTML = `<div class="text-xs text-slate-500">ไม่มีข้อมูลสิ่งอำนวยความสะดวก</div>`;
      return;
    }

    items.slice(0, FACILITY_TOP_N).forEach(it => {
      wrap.appendChild(makePill({
        value: it.value,
        label: it.label,
        active: state.amenities.has(it.value)
      }));
    });
  }

  function renderFacilityModalGrid(query = "") {
    const grid = el("facilityModalGrid");
    if (!grid) return;

    const q = norm(query);
    const items = getFacilitiesSorted().filter(it =>
      !q || norm(it.label).includes(q) || norm(it.value).includes(q)
    );

    grid.innerHTML = "";
    if (!items.length) {
      grid.innerHTML = `<div class="text-sm text-slate-500">ไม่พบสิ่งอำนวยฯ</div>`;
      return;
    }

    items.forEach(it => {
      grid.appendChild(makePill({
        value: it.value,
        label: `${it.label}${it.count ? ` (${it.count})` : ""}`,
        active: state.amenities.has(it.value)
      }));
    });
  }

  function syncTagButtonsUI() {
    const wrap = el("ageRangeWrap");
    if (!wrap) return;
    wrap.querySelectorAll(".tagBtn").forEach(btn => {
      const tag = btn.dataset.tag;
      const on = state.tags.has(tag);
      btn.classList.toggle("bg-emerald-600", on);
      btn.classList.toggle("text-white", on);
      btn.classList.toggle("border-emerald-600", on);
    });
  }

  function syncFacilityUI() {
    el("facilityWrap")?.querySelectorAll(".amPill").forEach(btn => {
      const v = btn.dataset.am;
      const on = state.amenities.has(v);
      btn.classList.toggle("bg-emerald-600", on);
      btn.classList.toggle("text-white", on);
      btn.classList.toggle("border-emerald-600", on);
      btn.classList.toggle("bg-white", !on);
      btn.classList.toggle("hover:bg-slate-50", !on);
    });

    el("facilityModalGrid")?.querySelectorAll(".amPill").forEach(btn => {
      const v = btn.dataset.am;
      const on = state.amenities.has(v);
      btn.classList.toggle("bg-emerald-600", on);
      btn.classList.toggle("text-white", on);
      btn.classList.toggle("border-emerald-600", on);
      btn.classList.toggle("bg-white", !on);
      btn.classList.toggle("hover:bg-slate-50", !on);
    });
  }

  function getAdmissionItems() {
    const fromFilters = (filtersData?.admission_policies || [])
      .map(x => ({
        value: x.slug,
        label: x.name,
        count: Number(x.count ?? 0)
      }))
      .filter(x => x.value);

    if (fromFilters.length) {
      fromFilters.sort((a,b) => (b.count - a.count) || a.label.localeCompare(b.label, "th"));
      return fromFilters;
    }

    const counts = new Map();
    for (const p of allPlaces) {
      (p.admission_policies || []).forEach(slug => {
        if (!slug) return;
        counts.set(slug, (counts.get(slug) || 0) + 1);
      });
    }
    const items = [...counts.entries()].map(([value, count]) => ({
      value,
      label: value,
      count
    }));
    items.sort((a,b) => (b.count - a.count) || a.label.localeCompare(b.label, "th"));
    return items;
  }

  function admissionLabel(label) {
    return label === "ไม่มีค่าบริการ" ? "ฟรี" : label;
  }

  function renderAdmissionPills() {
    const wrap = el("admissionWrap");
    if (!wrap) return;
    wrap.innerHTML = "";

    const items = getAdmissionItems();
    if (!items.length) {
      wrap.innerHTML = `<div class="text-xs text-slate-500">ไม่มีข้อมูลราคา</div>`;
      return;
    }

    items.forEach(it => {
      const btn = document.createElement("button");
      const active = state.admissionPolicies.has(it.value);
      btn.className =
        "adPill px-3 py-1 rounded-full border text-sm " +
        (active ? "bg-emerald-600 text-white border-emerald-600" : "bg-white hover:bg-slate-50");
      btn.dataset.admission = it.value;
      btn.textContent = admissionLabel(it.label);
      wrap.appendChild(btn);
    });
  }

  function syncAdmissionUI() {
    el("admissionWrap")?.querySelectorAll(".adPill").forEach(btn => {
      const v = btn.dataset.admission;
      const on = state.admissionPolicies.has(v);
      btn.classList.toggle("bg-emerald-600", on);
      btn.classList.toggle("text-white", on);
      btn.classList.toggle("border-emerald-600", on);
      btn.classList.toggle("bg-white", !on);
      btn.classList.toggle("hover:bg-slate-50", !on);
    });
  }

  function getCourseCategoryItems() {
    const terms = (filtersData?.course_categories || []);
    const labelMap = new Map(terms.map(x => [x.slug, x.name]));
    const parentMap = new Map(terms.map(x => [x.slug, x.parent || ""]));
    const parentSet = new Set(terms.filter(x => !x.parent).map(x => x.slug));

    const counts = new Map();
    for (const p of allPlaces) {
      (p.course_category_parents || []).forEach(slug => {
        if (!slug) return;
        counts.set(slug, (counts.get(slug) || 0) + 1);
      });
      (p.course_categories || []).forEach(slug => {
        if (!slug) return;
        counts.set(slug, (counts.get(slug) || 0) + 1);
      });
    }

    const items = terms.map(t => ({
      value: t.slug,
      label: labelMap.get(t.slug) || t.slug,
      parent: parentMap.get(t.slug) || "",
      count: counts.get(t.slug) || 0
    }));

    const filtered = items.filter(x => x.count > 0);
    filtered.sort((a,b) => (b.count - a.count) || a.label.localeCompare(b.label, "th"));
    return filtered;
  }

  const COURSE_CAT_TOP_N = 10;

  function makeCourseCatPill({ value, label, active }) {
    const btn = document.createElement("button");
    btn.className =
      "ccPill px-3 py-1 rounded-full border text-sm " +
      (active ? "bg-emerald-600 text-white border-emerald-600" : "bg-white hover:bg-slate-50");
    btn.dataset.courseCat = value;
    btn.textContent = label;
    return btn;
  }

  function renderCourseCategoryPillsTop() {
    const wrap = el("courseCatWrap");
    if (!wrap) return;
    wrap.innerHTML = "";

    const items = getCourseCategoryItems();
    if (!items.length) {
      wrap.innerHTML = `<div class="text-xs text-slate-500">ไม่มีข้อมูลหมวดคอร์ส</div>`;
      return;
    }

    items.slice(0, COURSE_CAT_TOP_N).forEach(it => {
      wrap.appendChild(makeCourseCatPill({
        value: it.value,
        label: it.label,
        active: state.courseCategories.has(it.value)
      }));
    });
  }

  function renderCourseCategoryModalGrid(query = "") {
    const grid = el("courseCatModalGrid");
    if (!grid) return;

    const q = norm(query);
    const items = getCourseCategoryItems().filter(it =>
      !q || norm(it.label).includes(q) || norm(it.value).includes(q)
    );

    grid.innerHTML = "";
    if (!items.length) {
      grid.innerHTML = `<div class="text-sm text-slate-500">ไม่พบหมวดคอร์ส</div>`;
      return;
    }

    items.forEach(it => {
      grid.appendChild(makeCourseCatPill({
        value: it.value,
        label: `${it.label}${it.count ? ` (${it.count})` : ""}`,
        active: state.courseCategories.has(it.value)
      }));
    });
  }

  function syncCourseCategoryUI() {
    el("courseCatWrap")?.querySelectorAll(".ccPill").forEach(btn => {
      const v = btn.dataset.courseCat;
      const on = state.courseCategories.has(v);
      btn.classList.toggle("bg-emerald-600", on);
      btn.classList.toggle("text-white", on);
      btn.classList.toggle("border-emerald-600", on);
      btn.classList.toggle("bg-white", !on);
      btn.classList.toggle("hover:bg-slate-50", !on);
    });

    el("courseCatModalGrid")?.querySelectorAll(".ccPill").forEach(btn => {
      const v = btn.dataset.courseCat;
      const on = state.courseCategories.has(v);
      btn.classList.toggle("bg-emerald-600", on);
      btn.classList.toggle("text-white", on);
      btn.classList.toggle("border-emerald-600", on);
      btn.classList.toggle("bg-white", !on);
      btn.classList.toggle("hover:bg-slate-50", !on);
    });
  }

  // ================= CATEGORY UI (FROM TAXONOMY) =================
  function getAllCategoriesFromData() {
    const tax = (filtersData?.categories || []).map(x => x.slug).filter(Boolean);
    if (tax.length) return tax;

    const keys = new Set();
    allPlaces.forEach(p => getPlaceCategories(p).forEach(c => keys.add(c)));
    Object.keys(CATEGORY_META).forEach(k => keys.add(k));
    return [...keys];
  }

  function getTopCategoriesFromData(limit = 6) {
    const cats = (filtersData?.categories || [])
      .map(x => ({ slug: x.slug, count: Number(x.count ?? 0) }))
      .filter(x => x.slug);

    if (cats.length) {
      cats.sort((a,b) => (b.count - a.count) || catMeta(a.slug).label.localeCompare(catMeta(b.slug).label, "th"));
      return cats.slice(0, limit).map(x => x.slug);
    }

    const counts = new Map();
    for (const p of allPlaces) {
      const catsArr = getPlaceCategories(p);
      if (!catsArr.length) continue;
      catsArr.forEach(c => counts.set(c, (counts.get(c) || 0) + 1));
    }
    return [...counts.entries()]
      .sort((a,b) => b[1] - a[1])
      .slice(0, limit)
      .map(([k]) => k);
  }

  function makeCatButton(key) {
    const meta = catMeta(key);
    const iconKey = getIconKeyFromCategory(key);
    const btn = document.createElement("button");
    const active = state.categories.has(key);
    btn.className = "flex items-center gap-2 rounded-xl border px-3 py-2 text-sm " +
      (active ? "bg-emerald-600 text-white border-emerald-600" : "bg-white hover:bg-slate-50");
    btn.innerHTML = `
      <span style="color: ${active ? "#ffffff" : meta.color}">${svgForDom(iconKey, "icon-18")}</span>
      <span class="truncate">${meta.label}</span>
    `;
    btn.onclick = () => {
      if (state.categories.has(key)) state.categories.delete(key);
      else state.categories.add(key);
      renderCategoryUIs();
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    };
    return btn;
  }

  function renderCategoryUIs() {
    const top = getTopCategoriesFromData(10);
    const all = getAllCategoriesFromData()
      .sort((a,b) => catMeta(a).label.localeCompare(catMeta(b).label, "th"));
    const grid = el("catGrid");
    grid.innerHTML = "";
    top.forEach(k => grid.appendChild(makeCatButton(k)));
    renderCategoryModalGrid(all, el("catSearch")?.value || "");
  }

  function renderCategoryModalGrid(allKeys, query) {
    const q = norm(query);
    const modalGrid = el("catModalGrid");
    modalGrid.innerHTML = "";
    const keys = allKeys.filter(k => norm(catMeta(k).label).includes(q) || norm(k).includes(q));
    if (!keys.length) {
      modalGrid.innerHTML = `<div class="col-span-2 text-sm text-slate-500">ไม่พบประเภท</div>`;
      return;
    }
    keys.forEach(k => modalGrid.appendChild(makeCatButton(k)));
  }

  function clearCategories() {
    state.categories.clear();
    renderCategoryUIs();
    resetListLimit();
    refresh();
    writeUrlFromState("push");
  }

  // ================= DRAWER =================
  function renderDistanceBadge(distanceKm) {
    const distanceEl = el("dDistance");
    if (!distanceEl) return;
    if (userLocation && Number.isFinite(distanceKm)) {
      distanceEl.innerHTML = `<span class="lbl">ห่างจากคุณ</span><span class="val">${formatKm(distanceKm)}</span>`;
      return;
    }
    distanceEl.innerHTML = `<span class="lbl">ห่างจากคุณ</span><span class="val">-</span>`;
  }

  function setDrawerTabsVisibility(hasCourses) {
    const tabsWrap = document.querySelector("#drawer .blm-drawer-tabs");
    const tabBtnCourses = el("tabBtnCourses");
    if (tabBtnCourses) tabBtnCourses.classList.toggle("hidden", !hasCourses);
    if (tabsWrap) {
      tabsWrap.classList.toggle("hidden", !hasCourses);
      tabsWrap.style.display = hasCourses ? "flex" : "none";
    }
  }

  function updateDescReadMoreVisibility() {
    const desc = el("dDesc");
    const btn = el("dDescMore");
    if (!desc || !btn) return;

    const text = (desc.textContent || "").trim();
    if (!text) {
      btn.classList.add("hidden");
      return;
    }

    const wasExpanded = desc.classList.contains("is-expanded");
    if (wasExpanded) desc.classList.remove("is-expanded");

    requestAnimationFrame(() => {
      const over3Lines = desc.scrollHeight > (desc.clientHeight + 1);
      btn.classList.toggle("hidden", !over3Lines);
      if (!over3Lines) {
        btn.textContent = "อ่านทั้งหมด ▼";
        desc.classList.remove("is-expanded");
      } else if (wasExpanded) {
        desc.classList.add("is-expanded");
      }
    });
  }

  function setActiveTab(tab) {
    document.querySelectorAll(".tabPanel").forEach(p => p.classList.add("hidden"));
    el(`tab-${tab}`).classList.remove("hidden");
    document.querySelectorAll(".tabBtn").forEach(btn => {
      const isActive = btn.dataset.tab === tab;
      btn.classList.toggle("is-active", isActive);
    });
  }

  function updateDetailsRowsVisibility() {
    const details = el("tab-details");
    if (!details) return;

    const hasMeaningfulText = (raw) => {
      const v = (raw || "").replace(/\s+/g, " ").trim().toLowerCase();
      if (!v) return false;
      const emptyTokens = new Set(["-", "—", "–", "n/a", "na", "null", "undefined", "ไม่ระบุ", "ไม่มีข้อมูล"]);
      return !emptyTokens.has(v);
    };

    const hasValidExternalUrl = (raw) => {
      const href = (raw || "").trim();
      if (!href || href === "#") return false;
      return /^https?:\/\//i.test(href);
    };

    const hasValidPhone = (raw) => {
      const v = (raw || "").trim();
      if (!hasMeaningfulText(v)) return false;
      const digits = v.replace(/\D/g, "");
      return digits.length >= 6;
    };

    const hasValidHours = (raw) => hasMeaningfulText(raw);
    const hasValidAdmission = (raw) => hasMeaningfulText(raw);

    const checks = [
      ["rowAddress", () => hasMeaningfulText(el("dAddress")?.textContent || "")],
      ["rowGmaps", () => {
        const href = (el("dGmaps")?.getAttribute("href") || "").trim();
        return !!href && href !== "#";
      }],
      ["rowPhone", () => hasValidPhone(el("dPhone")?.textContent || "")],
      ["rowHours", () => hasValidHours(el("dHours")?.textContent || "")],
      ["rowAdmission", () => hasValidAdmission(el("dAdmission")?.textContent || "")],
      ["rowTags", () => (el("dTags")?.children.length || 0) > 0],
      ["rowAmenities", () => (el("dAmenities")?.children.length || 0) > 0],
      ["rowFacebook", () => hasValidExternalUrl(el("dFacebook")?.getAttribute("href") || "") && !el("dFacebook")?.classList.contains("hidden")],
    ];

    checks.forEach(([rowId, hasData]) => {
      const row = el(rowId);
      if (!row) return;
      const visible = !!hasData();
      row.classList.toggle("hidden", !visible);
      row.style.display = visible ? "" : "none";
    });

    const visibleRows = checks
      .map(([rowId]) => el(rowId))
      .filter((row) => row && !row.classList.contains("hidden")).length;

    const detailsBtn = el("tabBtnDetails");
    if (detailsBtn) detailsBtn.classList.toggle("hidden", visibleRows === 0);
    details.classList.toggle("hidden", visibleRows === 0);

    if (visibleRows === 0) {
      const coursesBtn = el("tabBtnCourses");
      if (coursesBtn && !coursesBtn.classList.contains("hidden")) {
        setActiveTab("courses");
      }
    }
  }

  function openDrawer(place, options = {}) {
    const { forceMapOnMobile = false } = options;

    const drawer = el("drawer");
    if (!drawer) return;

    if (drawerHideTimer) {
      clearTimeout(drawerHideTimer);
      drawerHideTimer = null;
    }

    selectedId = place.id;
    syncActiveMarkerState();
    writeUrlFromState("push");

    drawer.classList.remove("hidden");
    requestAnimationFrame(() => {
      drawer.classList.add("is-open");
    });

    if (isMobile() && forceMapOnMobile) setMobileView("map");

    const primaryCat = getPrimaryCategory(place);
    const meta = catMeta(primaryCat);
    const iconKey = getIconKeyFromCategory(primaryCat);

    const setRowVisible = (id, visible) => {
      const row = el(id);
      if (!row) return;
      row.classList.toggle("hidden", !visible);
      row.style.display = visible ? "" : "none";
    };

    el("dTitle").textContent = place.name || "";
    el("dDistrict").textContent = place.district ? `เขต${place.district}` : "";
    el("dCategory").textContent = meta.label;
    const dIconEl = el("dIcon");
    dIconEl.innerHTML = `<span style="color: #ffffff">${svgForDom(iconKey, "icon-20")}</span>`;
    dIconEl.style.background = meta.color || DEFAULT_CATEGORY_COLOR;
    renderDistanceBadge(place._distanceKm);
    el("dAddress").textContent = place.address || "";
    setRowVisible("rowAddress", !!place.address);

    // default: hide optional rows until data is loaded
    setRowVisible("rowReportIssue", true);
    setRowVisible("rowPhone", false);
    setRowVisible("rowHours", false);
    setRowVisible("rowAdmission", false);
    setRowVisible("rowTags", false);
    setRowVisible("rowDesc", false);
    setRowVisible("rowFacebook", false);
    setRowVisible("rowAmenities", false);
    setRowVisible("rowImages", false);
    setRowVisible("rowGmaps", false);

    el("dPhone").textContent = "";
    el("dHours").textContent = "";
    el("dAdmission").textContent = "";
    el("dDesc").textContent = "";
    el("dDesc").classList.remove("is-expanded");
    el("dDescMore")?.classList.add("hidden");
    if (el("dDescMore")) el("dDescMore").textContent = "อ่านทั้งหมด ▼";

    const fallbackGmaps = (typeof place.lat === "number" && typeof place.lng === "number")
      ? `https://maps.google.com/?q=${place.lat},${place.lng}`
      : "";
    if (fallbackGmaps) {
      el("dGmaps").href = fallbackGmaps;
      setRowVisible("rowGmaps", true);
    } else {
      el("dGmaps").href = "#";
      setRowVisible("rowGmaps", false);
    }
    const fbEl = el("dFacebook");
    fbEl.classList.add("hidden");
    fbEl.href = "#";
    // removed permalink link

    const labelForAge = (slug) => {
      const it = (filtersData?.age_ranges || []).find(x => x.slug === slug);
      return it?.name || slug;
    };
    const labelForFacility = (slug) => {
      const it = (filtersData?.facilities || []).find(x => x.slug === slug);
      return it?.name || slug;
    };

    const tagsWrap = el("dTags");
    tagsWrap.innerHTML = "";
    (place.tags || []).forEach(t => {
      const chip = document.createElement("span");
      chip.className = "text-xs px-3 py-1 rounded-full border bg-white";
      chip.textContent = labelForAge(t);
      tagsWrap.appendChild(chip);
    });
    setRowVisible("rowTags", (place.tags || []).length > 0);

    const amWrap = el("dAmenities");
    amWrap.innerHTML = "";
    (place.amenities || []).forEach(a => {
      const chip = document.createElement("span");
      chip.className = "text-xs px-3 py-1 rounded-full border bg-emerald-50 text-emerald-800 border-emerald-200";
      chip.textContent = labelForFacility(a);
      amWrap.appendChild(chip);
    });
    setRowVisible("rowAmenities", (place.amenities || []).length > 0);

    const grid = el("imgGrid");
    grid.innerHTML = "";
    setRowVisible("rowImages", false);

    const coursesWrap = el("dCourses");
    coursesWrap.innerHTML = `<div class="text-sm text-slate-400">กำลังโหลดคอร์ส...</div>`;
    const coursesCountEl = el("coursesCount");
    if (coursesCountEl) coursesCountEl.textContent = "0";
    setDrawerTabsVisibility(false);
    const coursesSearchWrap = el("coursesSearchWrap");
    const coursesSearch = el("coursesSearch");
    if (coursesSearch) coursesSearch.value = "";
    if (coursesSearchWrap) coursesSearchWrap.classList.add("hidden");

    setActiveTab("details");
    updateDetailsRowsVisibility();
    if (isMobile() && forceMapOnMobile) setMobileView("map");

    loadFullForId(place.id).then((full) => {
      if (selectedId !== place.id) return;
      if (!full) {
        setRowVisible("rowAddress", false);
        setRowVisible("rowPhone", false);
        setRowVisible("rowHours", false);
        setRowVisible("rowAdmission", false);
        setRowVisible("rowTags", false);
        setRowVisible("rowDesc", false);
        setRowVisible("rowFacebook", false);
        setRowVisible("rowAmenities", false);
        setRowVisible("rowImages", false);
        setRowVisible("rowGmaps", true);
        coursesWrap.innerHTML = `<div class="text-sm text-slate-400">ยังไม่มีคอร์ส</div>`;
        if (coursesCountEl) coursesCountEl.textContent = "0";
        setDrawerTabsVisibility(false);
        updateDetailsRowsVisibility();
        return;
      }

      const addressText = full.address || place.address || "";
      el("dAddress").textContent = addressText;
      setRowVisible("rowAddress", !!addressText);

      el("dPhone").textContent = full.phone || "";
      setRowVisible("rowPhone", !!full.phone);

      el("dHours").textContent = full.hours || "";
      setRowVisible("rowHours", !!full.hours);
      {
        const aps = Array.isArray(full.admission_policies) ? full.admission_policies : [];
        const labels = aps.map(s => s === "ไม่มีค่าบริการ" ? "ฟรี" : s).filter(Boolean);
        el("dAdmission").textContent = labels.length ? labels.join(", ") : "";
        setRowVisible("rowAdmission", labels.length > 0);
      }
      const cleanDesc = decodeHtmlEntities(full.description || "");
      el("dDesc").textContent = cleanDesc;
      el("dDesc").classList.remove("is-expanded");
      if (el("dDescMore")) el("dDescMore").textContent = "อ่านทั้งหมด ▼";
      updateDescReadMoreVisibility();
      setRowVisible("rowDesc", !!cleanDesc.trim());

      const gmapsLink = (full.links?.googleMaps || "").trim();
      const finalGmapsLink = gmapsLink || fallbackGmaps;
      if (finalGmapsLink) {
        el("dGmaps").href = finalGmapsLink;
        setRowVisible("rowGmaps", true);
      } else {
        el("dGmaps").href = "#";
        setRowVisible("rowGmaps", false);
      }
      if (full.links?.facebook) {
        fbEl.href = full.links.facebook;
        fbEl.classList.remove("hidden");
        setRowVisible("rowFacebook", true);
      } else {
        fbEl.classList.add("hidden");
        setRowVisible("rowFacebook", false);
      }
      updateDetailsRowsVisibility();

      const normalizeImage = (img) => {
        if (!img) return null;
        if (typeof img === "string") {
          return { url: img, medium: img, large: img, caption: "" };
        }
        const url = img.url || img.large || img.medium;
        if (!url) return null;
        return {
          url,
          medium: img.medium || url,
          large: img.large || url,
          caption: img.caption || ""
        };
      };

      const imgs = Array.isArray(full.images) ? full.images.map(normalizeImage).filter(Boolean) : [];
      if (!imgs.length) {
        grid.innerHTML = "";
        setRowVisible("rowImages", false);
      } else {
        setRowVisible("rowImages", true);
        grid.innerHTML = "";
        const group = `place-${place.id}`;
        imgs.forEach((im, idx) => {
          const a = document.createElement("a");
          a.href = im.large || im.url;
          a.setAttribute("data-fancybox", group);
          if (im.caption) {
            a.setAttribute("data-caption", im.caption);
            a.setAttribute("data-fancybox-caption", im.caption);
          }

          if (idx < 3) {
            const img = document.createElement("img");
            img.src = im.medium || im.url;
            img.alt = im.caption || "place image";
            img.className = "h-32 w-full object-cover rounded-2xl border";
            a.appendChild(img);
          } else {
            a.className = "hidden";
          }
          grid.appendChild(a);
        });

        // Ensure Fancybox binds to newly injected gallery
        if (window.Fancybox) {
          window.Fancybox.bind(`[data-fancybox="${group}"]`, {
            caption: (fancybox, slide) => {
              const cap = slide?.triggerEl?.dataset?.caption || slide?.triggerEl?.dataset?.fancyboxCaption;
              return cap || "";
            },
            Thumbs: false,
            Toolbar: { display: { left: [], middle: [], right: ["close"] } },
          });
        }
      }

      coursesWrap.innerHTML = "";
      const courses = Array.isArray(full.courses) ? full.courses : [];
      if (coursesCountEl) coursesCountEl.textContent = String(courses.length);
      if (!courses.length) {
        coursesWrap.innerHTML = `<div class="text-sm text-slate-400">ยังไม่มีคอร์ส</div>`;
        setDrawerTabsVisibility(false);
        setActiveTab("details");
      } else {
        setDrawerTabsVisibility(true);
        const renderCourses = (list) => {
          coursesWrap.innerHTML = "";
          if (!list.length) {
            coursesWrap.innerHTML = `<div class="text-sm text-slate-400">ไม่พบคอร์สที่ค้นหา</div>`;
            return;
          }
          list.forEach(c => {
            const a = document.createElement("a");
            a.href = c.url || "#";
            a.target = "_blank";
            a.rel = "noreferrer";
            a.className = "block p-3 rounded-xl border bg-white hover:bg-slate-50";
            a.innerHTML = `
              <div class="font-semibold text-sm">${c.title || "-"}</div>
              <div class="text-xs text-slate-600 mt-1">
                ราคา: ${c.price_text || "-"} • ชั่วโมงเรียน: ${c.duration_text || "-"}
              </div>
              <div class="text-xs text-emerald-700 underline mt-1">เปิดหน้าคอร์ส ↗</div>
            `;
            coursesWrap.appendChild(a);
          });
        };

        renderCourses(courses);

        if (courses.length > 10 && coursesSearchWrap && coursesSearch) {
          coursesSearchWrap.classList.remove("hidden");
          coursesSearch.oninput = () => {
            const q = coursesSearch.value.trim().toLowerCase();
            if (!q) return renderCourses(courses);
            const filtered = courses.filter(c => (c.title || "").toLowerCase().includes(q));
            renderCourses(filtered);
          };
        } else if (coursesSearchWrap) {
          coursesSearchWrap.classList.add("hidden");
        }
      }
    });
  }

  function closeDrawer() {
    const drawer = el("drawer");
    if (!drawer) return;

    selectedId = null;
    syncActiveMarkerState();
    drawer.classList.remove("is-open");
    if (drawerHideTimer) clearTimeout(drawerHideTimer);
    drawerHideTimer = setTimeout(() => {
      drawer.classList.add("hidden");
      drawerHideTimer = null;
    }, DRAWER_ANIM_MS);
    writeUrlFromState("replace");
  }

  function buildPlaceShareUrl(place) {
    const url = new URL(window.location.href);
    url.search = "";
    url.searchParams.set("place", String(place.id));
    if (typeof place.lat === "number" && typeof place.lng === "number") {
      url.searchParams.set("lat", String(Number(place.lat).toFixed(6)));
      url.searchParams.set("lng", String(Number(place.lng).toFixed(6)));
      url.searchParams.set("zoom", "16");
    }
    return url.toString();
  }

  async function copyCurrentPlaceLink() {
    if (!selectedId) return;
    const place = allPlaces.find(x => String(x.id) === String(selectedId));
    if (!place) return;

    const btn = el("btnSharePlace");
    const url = buildPlaceShareUrl(place);

    try {
      await navigator.clipboard.writeText(url);
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

    if (btn) {
      const oldTitle = btn.getAttribute("title") || "คัดลอกลิงก์";
      const toast = el("copyLinkToast");
      btn.setAttribute("title", "คัดลอกแล้ว");
      btn.classList.add("is-copied");
      if (copyToastTimer) clearTimeout(copyToastTimer);
      toast?.classList.add("is-show");
      setTimeout(() => {
        btn.setAttribute("title", oldTitle);
        btn.classList.remove("is-copied");
      }, 950);
      copyToastTimer = setTimeout(() => {
        toast?.classList.remove("is-show");
      }, 1200);
    }
  }

  // ================= REPORT MODAL =================
  function openReportModal() {
    const modal = el("reportModal");
    if (!modal) return;
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";

    const title = el("dTitle")?.textContent || "";
    const header = modal.querySelector(".font-bold");
    if (header) header.textContent = title ? `แจ้งแก้ไขข้อมูล: ${title}` : "แจ้งแก้ไขข้อมูลสถานที่";

    const err = el("reportError");
    const ok = el("reportSuccess");
    err?.classList.add("hidden");
    ok?.classList.add("hidden");
  }

  function closeReportModal() {
    const modal = el("reportModal");
    if (!modal) return;
    modal.classList.add("hidden");
    document.body.style.overflow = "";
    const form = el("reportForm");
    if (form) form.reset();
  }

  function setReportMessage(type, msg) {
    const err = el("reportError");
    const ok = el("reportSuccess");
    if (err) err.classList.add("hidden");
    if (ok) ok.classList.add("hidden");
    if (type === "error" && err) {
      err.textContent = msg;
      err.classList.remove("hidden");
    }
    if (type === "success" && ok) {
      ok.textContent = msg;
      ok.classList.remove("hidden");
    }
  }

  (function bindReportModal() {
    const modal = el("reportModal");
    const openBtn = el("btnReportIssue");
    const closeBtn = el("closeReportModal");
    const cancelBtn = el("cancelReport");
    const form = el("reportForm");
    const submitBtn = el("submitReport");
    if (!form) return;

    openBtn?.addEventListener("click", () => {
      if (!selectedId) return;
      openReportModal();
    });
    closeBtn?.addEventListener("click", closeReportModal);
    cancelBtn?.addEventListener("click", closeReportModal);
    modal?.addEventListener("click", (e) => {
      const target = e.target;
      if (!(target instanceof Element)) return;
      if (target.dataset.modalBackdrop === "1") closeReportModal();
    });

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!selectedId) {
        setReportMessage("error", "ไม่พบรหัสสถานที่ กรุณาลองใหม่อีกครั้ง");
        return;
      }

      const topics = Array.from(document.querySelectorAll('input[name="report_topics[]"]:checked'))
        .map((el) => el.value);
      const details = (el("reportDetails")?.value || "").trim();
      const name = (el("reportName")?.value || "").trim();
      const contact = (el("reportContact")?.value || "").trim();

      if (!topics.length && details.length < 3) {
        setReportMessage("error", "กรุณาเลือกหัวข้อ หรือพิมพ์รายละเอียดเพิ่มเติม");
        return;
      }

      submitBtn?.setAttribute("disabled", "disabled");
      submitBtn?.classList.add("opacity-60", "cursor-not-allowed");
      setReportMessage("success", "กำลังส่งรายงาน...");

      const fd = new FormData();
      fd.append("action", "lc_report_location");
      fd.append("nonce", REPORT_NONCE);
      fd.append("location_id", String(selectedId));
      topics.forEach(t => fd.append("topics[]", t));
      fd.append("details", details);
      fd.append("name", name);
      fd.append("contact", contact);
      const hp = el("reportWebsite")?.value || "";
      fd.append("website", hp);

      try {
        const res = await fetch(REPORT_AJAX_URL, { method: "POST", body: fd });
        const json = await res.json();
        if (!json || !json.success) {
          throw new Error(json?.data?.message || "ส่งรายงานไม่สำเร็จ");
        }
        setReportMessage("success", "ขอบคุณครับ ทีมงานได้รับรายงานแล้ว");
        setTimeout(() => closeReportModal(), 1200);
      } catch (err) {
        setReportMessage("error", err.message || "ส่งรายงานไม่สำเร็จ");
      } finally {
        submitBtn?.removeAttribute("disabled");
        submitBtn?.classList.remove("opacity-60", "cursor-not-allowed");
      }
    });
  })();

  // ================= MAP ICONS =================
  async function addSvgImagesToMap() {
    const cats = categoryMetaIndex ? Object.keys(categoryMetaIndex) : Object.keys(CATEGORY_META);
    const keys = new Set(cats.length ? cats : ["default"]);
    keys.add("default");

    const tasks = [...keys].map((catKey) => {
      return new Promise((resolve) => {
        const meta = catMeta(catKey);
        const innerPath = getSvgByKey(meta.iconKey)
          .replace(/<svg[^>]*>/, "")
          .replace("</svg>", "");

        const fillColor = meta.color || DEFAULT_CATEGORY_COLOR;

        const svgString = `
          <svg width="64" height="64" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
            <rect x="4" y="4" width="56" height="56" rx="14" fill="${fillColor}" />
            <g transform="translate(14, 14) scale(1.5)" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              ${innerPath}
            </g>
          </svg>
        `.trim();

        const img = new Image();
        const blob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);

        img.onload = () => {
          try {
            const id = `blm-${catKey}`;
            if (map.hasImage(id)) map.removeImage(id);
            map.addImage(id, img, { pixelRatio: 2 });
          } catch (e) { console.error("Map image error:", e); }
          URL.revokeObjectURL(url);
          resolve();
        };
        img.onerror = () => { URL.revokeObjectURL(url); resolve(); };
        img.src = url;
      });
    });

    return Promise.all(tasks);
  }

  // ================= MAP: CLUSTER SOURCE + LAYERS =================
  function toGeoJSON(places) {
    return {
      type: "FeatureCollection",
      features: places
        .filter(p => typeof p.lat === "number" && typeof p.lng === "number")
        .map(p => {
          const primaryCat = getPrimaryCategory(p);
          const categoryKey = primaryCat || "default";
          const iconKey = `blm-${categoryKey}`;
          return {
            type: "Feature",
            id: p.id,
            geometry: { type: "Point", coordinates: [p.lng, p.lat] },
            properties: { id: p.id, category: categoryKey, district: p.district || "", name: p.name || "", iconKey }
          };
        })
    };
  }

  function ensurePlacesLayers() {
    if (map.getSource(PLACES_SOURCE_ID)) return;

    map.addSource(PLACES_SOURCE_ID, {
      type: "geojson",
      data: toGeoJSON([]),
      cluster: true,
      clusterMaxZoom: CLUSTER_MAX_ZOOM,
      clusterRadius: CLUSTER_RADIUS
    });

    map.addLayer({
      id: LAYER_CLUSTER_CIRCLE,
      type: "circle",
      source: PLACES_SOURCE_ID,
      filter: ["has", "point_count"],
      paint: {
        "circle-color": "#00744B",
        "circle-radius": ["step", ["get", "point_count"], 18, 100, 22, 300, 26],
        "circle-opacity": 1,
        "circle-stroke-width": 2,
        "circle-stroke-color": "#ffffff"
      }
    });

    map.addLayer({
      id: LAYER_CLUSTER_COUNT,
      type: "symbol",
      source: PLACES_SOURCE_ID,
      filter: ["has", "point_count"],
      layout: {
        "text-field": "{point_count_abbreviated}",
        "text-font": ["Anuphan-SemiBold", "Anuphan SemiBold"],
        "text-size": 14
      },
      paint: { "text-color": "#ffffff" }
    });

    map.addLayer({
      id: LAYER_UNCLUSTERED,
      type: "symbol",
      source: PLACES_SOURCE_ID,
      filter: ["!", ["has", "point_count"]],
      layout: {
        "icon-image": ["get", "iconKey"],
        "icon-size": 1.2,
        "icon-allow-overlap": true,
        "icon-ignore-placement": true,
        "icon-anchor": "center"
      }
    });

    map.addLayer({
      id: LAYER_UNCLUSTERED_ACTIVE_RING,
      type: "circle",
      source: PLACES_SOURCE_ID,
      filter: ["all", ["!", ["has", "point_count"]], ["==", ["to-string", ["get", "id"]], ""]],
      paint: {
        "circle-radius": 20,
        "circle-color": "#00da8d",
        "circle-opacity": 0,
        "circle-stroke-width": 3,
        "circle-stroke-color": "#00da8d",
        "circle-stroke-opacity": 0.85
      }
    });

    map.addLayer({
      id: LAYER_UNCLUSTERED_ACTIVE,
      type: "symbol",
      source: PLACES_SOURCE_ID,
      filter: ["all", ["!", ["has", "point_count"]], ["==", ["to-string", ["get", "id"]], ""]],
      layout: {
        "icon-image": ["get", "iconKey"],
        "icon-size": 1.55,
        "icon-allow-overlap": true,
        "icon-ignore-placement": true,
        "icon-anchor": "center"
      }
    });

    map.addLayer({
      id: "places-unclustered-hover",
      type: "symbol",
      source: PLACES_SOURCE_ID,
      filter: ["all", ["!", ["has", "point_count"]], ["==", ["to-string", ["get", "id"]], ""]],
      layout: {
        "icon-image": ["get", "iconKey"],
        "icon-size": 1.6,
        "icon-allow-overlap": true,
        "icon-ignore-placement": true,
        "icon-anchor": "center"
      }
    });

    map.addLayer({
      id: LAYER_UNCLUSTERED_LABEL,
      type: "symbol",
      source: PLACES_SOURCE_ID,
      filter: ["!", ["has", "point_count"]],
      minzoom: SHOW_LABEL_ZOOM,
      layout: {
        "text-field": ["get", "name"],
        "text-font": ["Anuphan-SemiBold"],
        "text-size": 14,
        "text-anchor": "left",
        "text-offset": [1.8, 0],
        "text-max-width": 10,
        "text-line-height": 1.1,
        "text-justify": "left"
      },
      paint: {
        "text-color": "#0f172a",
        "text-halo-color": "#ffffff",
        "text-halo-width": 2
      }
    });

    map.on("click", LAYER_CLUSTER_CIRCLE, (e) => {
      const features = map.queryRenderedFeatures(e.point, { layers: [LAYER_CLUSTER_CIRCLE] });
      const clusterId = features[0].properties.cluster_id;
      map.getSource(PLACES_SOURCE_ID).getClusterExpansionZoom(clusterId, (err, zoom) => {
        if (err) return;
        map.easeTo({ center: features[0].geometry.coordinates, zoom });
      });
    });

    const openPlaceFromPoint = (e, layers) => {
      const f = map.queryRenderedFeatures(e.point, { layers })[0];
      if (!f) return;
      const fid = String(f.properties?.id ?? "");
      const place = allPlaces.find(p => String(p.id) === fid);
      if (place) openDrawer(place, { forceMapOnMobile: true });
    };

    map.on("click", LAYER_UNCLUSTERED, (e) => openPlaceFromPoint(e, [LAYER_UNCLUSTERED]));
    map.on("click", LAYER_UNCLUSTERED_ACTIVE, (e) => openPlaceFromPoint(e, [LAYER_UNCLUSTERED_ACTIVE]));
    map.on("click", LAYER_UNCLUSTERED_LABEL, (e) => openPlaceFromPoint(e, [LAYER_UNCLUSTERED_LABEL]));

    const setCursor = (c) => map.getCanvas().style.cursor = c;
    map.on("mouseenter", LAYER_CLUSTER_CIRCLE, () => setCursor("pointer"));
    map.on("mouseleave", LAYER_CLUSTER_CIRCLE, () => setCursor(""));
    map.on("mouseenter", LAYER_UNCLUSTERED, () => setCursor("pointer"));
    map.on("mouseleave", LAYER_UNCLUSTERED, () => setCursor(""));
    map.on("mouseenter", LAYER_UNCLUSTERED_ACTIVE, () => setCursor("pointer"));
    map.on("mouseleave", LAYER_UNCLUSTERED_ACTIVE, () => setCursor(""));
    map.on("mouseenter", LAYER_UNCLUSTERED_LABEL, () => setCursor("pointer"));
    map.on("mouseleave", LAYER_UNCLUSTERED_LABEL, () => setCursor(""));
    syncActiveMarkerState();
  }

  function syncActiveMarkerState() {
    if (!map) return;
    const activeId = selectedId == null ? "" : String(selectedId);
    const activeFilter = ["all", ["!", ["has", "point_count"]], ["==", ["to-string", ["get", "id"]], activeId]];

    if (map.getLayer(LAYER_UNCLUSTERED_ACTIVE_RING)) {
      map.setFilter(LAYER_UNCLUSTERED_ACTIVE_RING, activeFilter);
    }
    if (map.getLayer(LAYER_UNCLUSTERED_ACTIVE)) {
      map.setFilter(LAYER_UNCLUSTERED_ACTIVE, activeFilter);
    }

    if (activeId) startActivePulse();
    else stopActivePulse();
  }

  function syncPlacesSource(placesToShow) {
    const src = map.getSource(PLACES_SOURCE_ID);
    if (src) src.setData(toGeoJSON(placesToShow));
  }

  // ================= LIST RENDER =================
  function buildListCard(place) {
    const primaryCat = getPrimaryCategory(place);
    const meta = catMeta(primaryCat);
    const iconKey = getIconKeyFromCategory(primaryCat);
    const distText = userLocation ? `ห่างจากคุณ ${formatKm(place._distanceKm)}` : "";
    const card = document.createElement("button");
    card.className = "w-full text-left p-4 rounded-xl bg-white border hover:shadow-sm transition";
    card.innerHTML = `
      <div class="flex items-start gap-3">
        <div class="shrink-0 mt-0.5 w-[35px] h-[35px] rounded-lg text-white flex items-center justify-center" style="background:${meta.color}">${svgForDom(iconKey, "icon-18")}</div>
        <div class="min-w-0 flex-1">
          <div class="font-semibold leading-snug text-[16px]">
            <span>${place.name}</span>
          </div>
          <div class="text-[14px] text-slate-700">
            <span>${meta.label}${place.district ? (" : เขต" + place.district) : ""}</span>
          </div>
          <div class="mt-2 flex flex-wrap gap-1">
            ${(place.tags || []).slice(0,4).map(t => `<span class="text-[11px] px-2 py-[1px] rounded-full border bg-white">${t}</span>`).join("")}
          </div>
          <div class="text-[14px] font-semibold text-emerald-700 mt-2">${distText}</div>
        </div>
      </div>
    `;

    card.addEventListener("mouseenter", () => {
      if (!map) return;
      map.setFilter("places-unclustered-hover", ["==", ["to-string", ["get", "id"]], String(place.id)]);
      startHoverShake();
    });

    card.addEventListener("mouseleave", () => {
      if (!map) return;
      map.setFilter("places-unclustered-hover", ["==", ["to-string", ["get", "id"]], ""]);
      stopHoverShake();
    });

    card.onclick = () => {
      if (map && typeof place.lng === "number" && typeof place.lat === "number") {
        map.flyTo({ center: [place.lng, place.lat], zoom: 16 });
      }
      openDrawer(place);
      closeSidebarIfMobile();
    };
    return card;
  }

  function renderList(places) {
    const list = el("list");
    const listMobile = el("listMobile");
    if (list) list.innerHTML = "";
    if (listMobile) listMobile.innerHTML = "";

    if (places.length === 0) {
      const empty = document.createElement("div");
      empty.className = "p-4 rounded-xl bg-white border text-slate-600";
      empty.textContent = "ไม่พบสถานที่ในกรอบแผนที่/เงื่อนไขนี้";
      if (list) list.appendChild(empty.cloneNode(true));
      if (listMobile) listMobile.appendChild(empty);
      return;
    }

    for (const p of places) {
      if (list) list.appendChild(buildListCard(p));
      if (listMobile) listMobile.appendChild(buildListCard(p));
    }
  }

  function renderLoadMoreUI(total, shown) {
    const hasMore = total > shown;
    [el("btnLoadMoreDesktop"), el("btnLoadMoreMobile")].forEach(b => b?.classList.toggle("hidden", !hasMore));
    [el("loadMoreHintDesktop"), el("loadMoreHintMobile")].forEach(h => {
      if (h) {
        h.classList.toggle("hidden", !hasMore);
        h.textContent = hasMore ? `แสดง ${shown} จาก ${total} รายการ` : "";
      }
    });
  }

  function applyLoadMore() {
    listLimit += LIST_PAGE_SIZE;
    const slice = lastVisible.slice(0, listLimit);
    renderList(slice);
    renderLoadMoreUI(lastVisible.length, slice.length);
  }

  // ================= REFRESH =================
  function refresh() {
    if (!map) return;
    computeDistances();

    const visible = allPlaces
      .filter(p => typeof p.lat === "number" && typeof p.lng === "number")
      .filter(p => matchesFilters(p) && isInBounds(p));

    if (userLocation) {
      visible.sort((a,b) => (a._distanceKm ?? 1e9) - (b._distanceKm ?? 1e9));
    }

    lastVisible = visible;

    el("count").textContent = String(visible.length);
    [el("listCount"), el("listCountMobile")].forEach(c => { if (c) c.textContent = String(visible.length); });

    const slice = visible.slice(0, listLimit);
    renderList(slice);

    updateSearchStatusUI();
    renderSearchPanel();

    renderActiveFilters("activeFilters");
    renderActiveFilters("activeFiltersMobile");
    updateFilterCount();

    renderLoadMoreUI(visible.length, slice.length);

    syncPlacesSource(visible);

    if (selectedId) {
      const p = allPlaces.find(x => x.id === selectedId);
      if (p) renderDistanceBadge(p._distanceKm);
    }
  }

  // ================= LOCATION =================
  function requestLocation() {
    if (!window.isSecureContext && location.hostname !== "localhost") {
      const msg = "iOS ต้องใช้ HTTPS เพื่อขอตำแหน่งปัจจุบัน";
      const status = el("locStatus");
      if (status) status.textContent = msg;
      alert(msg);
      return;
    }
    if (!navigator.geolocation) { el("locStatus").textContent = "อุปกรณ์ไม่รองรับการใช้ตำแหน่งปัจจุบัน"; return; }
    el("locStatus").textContent = "กำลังใช้ตำแหน่งปัจจุบัน...";
    navigator.geolocation.getCurrentPosition((pos) => {
      userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      el("locStatus").textContent = "ใช้ตำแหน่งปัจจุบันอยู่";
      el("nearMeWrap").classList.remove("hidden");
      if (!meMarker) {
        meMarker = new maplibregl.Marker({ element: Object.assign(document.createElement("div"), {className: "me-dot"}), anchor: "center" })
          .setLngLat([userLocation.lng, userLocation.lat])
          .addTo(map);
      } else {
        meMarker.setLngLat([userLocation.lng, userLocation.lat]);
      }
      computeDistances();
      resetListLimit();
      refresh();
      map.flyTo({ center: [userLocation.lng, userLocation.lat], zoom: Math.max(map.getZoom(), 13) });
      saveLocationCache();
    }, () => {
      el("locStatus").textContent = "ไม่สามารถใช้ตำแหน่งปัจจุบันได้";
    }, { enableHighAccuracy: true });
  }

  function toggleNearMe() {
    state.nearMeEnabled = !state.nearMeEnabled;
    el("nearMeRadiusWrap").classList.toggle("hidden", !state.nearMeEnabled);
    el("chipNearMe").textContent = state.nearMeEnabled ? "ใกล้ฉัน: เปิด" : "ใกล้ฉัน: ปิด";
    el("chipNearMe").className = state.nearMeEnabled
      ? "px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700"
      : "px-3 py-2 rounded-lg border text-sm font-semibold hover:bg-slate-50";
    resetListLimit();
    refresh();
    writeUrlFromState("push");
    saveLocationCache();
  }

  // ================= LOAD DATA =================
  async function loadPlaces() {
    try {
      setApiLoading(true, "กำลังโหลดสถานที่...");
      const res = await fetch(`/learningcity/wp-json/blm/v1/locations-light?per_page=10000`);
      const json = await res.json();
      allPlaces = json.places || [];
    } catch (e) {
      console.error(e);
      alert("โหลดข้อมูลไม่สำเร็จ");
    } finally {
      setApiLoading(false);
    }
  }

  async function loadFilters() {
    try {
      const res = await fetch(`/learningcity/wp-json/blm/v1/filters`);
      if (!res.ok) return;
      filtersData = await res.json();
    } catch (e) {
      console.warn("loadFilters failed", e);
    }
  }

  function fillDistrictOptionsFromFilters() {
    const sel = el("district");
    if (!sel) return;
    sel.innerHTML = `<option value="">ทุกเขต</option>`;

    const districts =
      (filtersData?.districts || [])
        .map(x => x.slug)
        .filter(Boolean)
        .sort((a,b) => a.localeCompare(b, "th"));

    const list = districts.length
      ? districts
      : [...new Set(allPlaces.map(p => p.district).filter(Boolean))]
          .sort((a,b) => a.localeCompare(b, "th"));

    for (const d of list) {
      const opt = document.createElement("option");
      opt.value = d;
      opt.textContent = d;
      sel.appendChild(opt);
    }
  }

  // ================= UI BINDINGS =================
  function bindUI() {
    renderDrawerMetaIcons();
    const bindBackdropClose = (modalId, onClose) => {
      const modal = el(modalId);
      if (!modal || typeof onClose !== "function") return;
      modal.addEventListener("click", (e) => {
        const target = e.target;
        if (!(target instanceof Element)) return;
        if (target.dataset.modalBackdrop === "1") onClose();
      });
    };

    el("tabMap")?.addEventListener("click", () => setMobileView("map"));
    el("tabList")?.addEventListener("click", () => setMobileView("list"));

    el("btnOpenFiltersMobile")?.addEventListener("click", openSidebarMobile);
    el("btnCloseFiltersMobile")?.addEventListener("click", closeSidebarMobile);
    el("sidebarOverlay")?.addEventListener("click", closeSidebarMobile);

    bindTap(el("btnLocate"), requestLocation);
    bindTap(el("btnLocateMobile"), requestLocation);

    el("chipNearMe")?.addEventListener("click", () => {
      if (!userLocation) requestLocation();
      else toggleNearMe();
    });

    el("radiusKm")?.addEventListener("input", () => {
      state.radiusKm = Number(el("radiusKm").value || 5);
      if (state.nearMeEnabled) refresh();
      writeUrlFromState("replace");
      saveLocationCache();
    });

    el("btnLoadMoreDesktop")?.addEventListener("click", applyLoadMore);
    el("btnLoadMoreMobile")?.addEventListener("click", applyLoadMore);

    // ===== SEARCH =====
    el("q")?.addEventListener("focus", () => {
      isSearching = true;
      renderSearchPanel();
    });

    el("q")?.addEventListener("input", (e) => {
      searchQuery = e.target.value;
      isSearching = true;
      updateSearchStatusUI();
      renderSearchPanel();
      writeUrlDebounced();
    });

    el("btnClearSearch")?.addEventListener("click", () => {
      searchQuery = "";
      isSearching = false;
      el("q").value = "";
      closeSearchPanel();
      updateSearchStatusUI();
      writeUrlFromState("replace");
    });

    el("dDescMore")?.addEventListener("click", () => {
      const desc = el("dDesc");
      const btn = el("dDescMore");
      if (!desc || !btn) return;
      const expanded = desc.classList.toggle("is-expanded");
      btn.textContent = expanded ? "ย่อ ▲" : "อ่านทั้งหมด ▼";
    });

    document.addEventListener("click", (e) => {
      const box = el("searchBox");
      if (box && !box.contains(e.target)) {
        isSearching = false;
        closeSearchPanel();
      }
    });

    // ===== FILTERS =====
    el("district")?.addEventListener("change", (e) => {
      state.district = e.target.value;
      resetListLimit();
      refresh();
      closeSidebarIfMobile();
      writeUrlFromState("push");
    });

    // ✅ dynamic: ageRange buttons (event delegation)
    el("ageRangeWrap")?.addEventListener("click", (e) => {
      const btn = e.target.closest?.(".tagBtn");
      if (!btn) return;
      const tag = btn.dataset.tag;
      if (!tag) return;

      if (state.tags.has(tag)) state.tags.delete(tag);
      else state.tags.add(tag);

      syncTagButtonsUI();
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    });

    // ✅ dynamic: facilities pills + modal
    function toggleFacility(value) {
      if (!value) return;
      if (state.amenities.has(value)) state.amenities.delete(value);
      else state.amenities.add(value);
      syncFacilityUI();
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    }

    el("facilityWrap")?.addEventListener("click", (e) => {
      const btn = e.target.closest?.(".amPill");
      if (!btn) return;
      toggleFacility(btn.dataset.am);
    });

    el("btnAllFacilities")?.addEventListener("click", () => {
      el("facilityModal").classList.remove("hidden");
      renderFacilityModalGrid(el("facilitySearch")?.value || "");
    });

    el("closeFacilityModal")?.addEventListener("click", () => {
      el("facilityModal").classList.add("hidden");
    });
    bindBackdropClose("facilityModal", () => el("facilityModal")?.classList.add("hidden"));

    el("btnApplyFacilities")?.addEventListener("click", () => {
      el("facilityModal").classList.add("hidden");
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    });

    el("facilityModalGrid")?.addEventListener("click", (e) => {
      const btn = e.target.closest?.(".amPill");
      if (!btn) return;
      toggleFacility(btn.dataset.am);
    });

    el("facilitySearch")?.addEventListener("input", () => {
      renderFacilityModalGrid(el("facilitySearch").value || "");
      syncFacilityUI();
    });

    el("admissionWrap")?.addEventListener("click", (e) => {
      const btn = e.target.closest?.(".adPill");
      if (!btn) return;
      const value = btn.dataset.admission;
      if (!value) return;
      if (state.admissionPolicies.has(value)) state.admissionPolicies.delete(value);
      else state.admissionPolicies.add(value);
      syncAdmissionUI();
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    });

    function clearAdmission() {
      state.admissionPolicies.clear();
      syncAdmissionUI();
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    }

    el("btnClearAdmission")?.addEventListener("click", clearAdmission);

    el("courseCatWrap")?.addEventListener("click", (e) => {
      const btn = e.target.closest?.(".ccPill");
      if (!btn) return;
      const value = btn.dataset.courseCat;
      if (!value) return;
      if (state.courseCategories.has(value)) state.courseCategories.delete(value);
      else state.courseCategories.add(value);
      syncCourseCategoryUI();
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    });

    el("courseCatModalGrid")?.addEventListener("click", (e) => {
      const btn = e.target.closest?.(".ccPill");
      if (!btn) return;
      const value = btn.dataset.courseCat;
      if (!value) return;
      if (state.courseCategories.has(value)) state.courseCategories.delete(value);
      else state.courseCategories.add(value);
      syncCourseCategoryUI();
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    });

    function clearCourseCategories() {
      state.courseCategories.clear();
      syncCourseCategoryUI();
      renderCourseCategoryPillsTop();
      renderCourseCategoryModalGrid(el("courseCatSearch")?.value || "");
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    }

    el("btnClearCourseCat")?.addEventListener("click", clearCourseCategories);
    el("btnClearCourseCats2")?.addEventListener("click", clearCourseCategories);

    el("btnAllCourseCats")?.addEventListener("click", () => el("courseCatModal").classList.remove("hidden"));
    el("closeCourseCatModal")?.addEventListener("click", () => el("courseCatModal").classList.add("hidden"));
    bindBackdropClose("courseCatModal", () => el("courseCatModal")?.classList.add("hidden"));
    el("btnApplyCourseCats")?.addEventListener("click", () => {
      el("courseCatModal").classList.add("hidden");
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    });

    el("courseCatSearch")?.addEventListener("input", () => {
      renderCourseCategoryModalGrid(el("courseCatSearch").value || "");
      syncCourseCategoryUI();
    });

    function clearFacilities() {
      state.amenities.clear();
      syncFacilityUI();
      renderFacilityPillsTop();
      renderFacilityModalGrid(el("facilitySearch")?.value || "");
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    }

    el("btnClearFacilities")?.addEventListener("click", clearFacilities);
    el("btnClearFacilities2")?.addEventListener("click", clearFacilities);

    el("reset")?.addEventListener("click", () => {
      state.district = "";
      state.categories.clear();
      state.tags.clear();
      state.amenities.clear();
      state.admissionPolicies.clear();
      state.courseCategories.clear();
      state.nearMeEnabled = false;

      searchQuery = "";
      isSearching = false;

      el("q").value = "";
      el("district").value = "";
      closeSearchPanel();

      syncTagButtonsUI();
      syncFacilityUI();
      syncAdmissionUI();
      syncCourseCategoryUI();

      el("nearMeRadiusWrap").classList.add("hidden");
      el("chipNearMe").textContent = "ใกล้ฉัน: ปิด";
      el("chipNearMe").className =
        "px-3 py-2 rounded-lg border text-sm font-semibold hover:bg-slate-50";

    renderCategoryUIs();
    renderCourseCategoryPillsTop();
    renderCourseCategoryModalGrid(el("courseCatSearch")?.value || "");
    resetListLimit();
    refresh();
    closeSidebarIfMobile();
    writeUrlFromState("replace");
    });

    el("drawerClose")?.addEventListener("click", closeDrawer);
    el("btnSharePlace")?.addEventListener("click", copyCurrentPlaceLink);

    document.querySelectorAll(".tabBtn").forEach((btn) =>
      btn.addEventListener("click", () => setActiveTab(btn.dataset.tab))
    );

    el("btnAllCats")?.addEventListener("click", () => el("catModal").classList.remove("hidden"));
    el("closeCatModal")?.addEventListener("click", () => el("catModal").classList.add("hidden"));
    bindBackdropClose("catModal", () => el("catModal")?.classList.add("hidden"));
    el("btnApplyCats")?.addEventListener("click", () => {
      el("catModal").classList.add("hidden");
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    });

    el("btnClearCats")?.addEventListener("click", clearCategories);
    el("btnClearCats2")?.addEventListener("click", clearCategories);

    el("catSearch")?.addEventListener("input", () =>
      renderCategoryModalGrid(
        getAllCategoriesFromData().sort((a, b) =>
          catMeta(a).label.localeCompare(catMeta(b).label, "th")
        ),
        el("catSearch").value || ""
      )
    );

    window.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        isSearching = false;
        closeSearchPanel();
        closeDrawer();
        el("catModal").classList.add("hidden");
        closeSidebarMobile();
      }
    });

    const onResize = () => {
      if (isMobile()) setMobileView(mobileView || "map");
      else {
        el("listSectionMobile").classList.add("hidden");
        closeSidebarMobile();
        setTimeout(() => map && map.resize(), 80);
      }
    };
    window.addEventListener("resize", onResize);
    onResize();

  }

  function syncHeaderHeight() {
    const header = document.querySelector("header");
    const h = header ? header.offsetHeight : 0;
    document.documentElement.style.setProperty("--lc-header-h", `${h}px`);
  }

  // ================= INIT MAP =================
  async function initMap() {
    const initialCenter = urlState.initialMap
      ? [urlState.initialMap.lng, urlState.initialMap.lat]
      : [100.5018, 13.7563];

    const initialZoom = urlState.initialMap ? urlState.initialMap.zoom : 12;

    map = new maplibregl.Map({
      container: "map",
      style: MAPTILER_STYLE,
      center: initialCenter,
      zoom: initialZoom,
      transformRequest: (url, resourceType) => {
        if (resourceType === "Glyphs" || url.endsWith(".pbf")) {
          const match = /\/fonts\/([^/]+)\/(\d+-\d+)\.pbf/.exec(url);
          if (match) {
            const fontstack = decodeURIComponent(match[1]);
            if (fontstack === LOCAL_GLYPH_FONT) {
              return { url: `${LOCAL_GLYPHS_BASE}/${LOCAL_GLYPH_FONT}/${match[2]}.pbf` };
            }
          }
        }
        return { url };
      }
    });

    map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), "bottom-left");

    const update = debounce(() => {
      resetListLimit();
      refresh();
      writeUrlFromState("replace");
    }, 250);

    map.on("load", async () => {
      await addSvgImagesToMap();
      ensurePlacesLayers();
      if (userLocation) {
        el("locStatus").textContent = "ใช้ตำแหน่งปัจจุบันอยู่";
        el("nearMeWrap").classList.remove("hidden");
        if (!meMarker) {
          meMarker = new maplibregl.Marker({ element: Object.assign(document.createElement("div"), {className: "me-dot"}), anchor: "center" })
            .setLngLat([userLocation.lng, userLocation.lat])
            .addTo(map);
        } else {
          meMarker.setLngLat([userLocation.lng, userLocation.lat]);
        }
      }
      refresh();
      writeUrlFromState("replace");
    });

    map.on("moveend", update);
    map.on("zoomend", update);
  }

  // ================= HOVER ANIM =================
  function startHoverShake() {
    if (!map) return;
    if (hoverAnimRaf) cancelAnimationFrame(hoverAnimRaf);
    hoverAnimT0 = performance.now();

    const tick = (t) => {
      if (!map.getLayer("places-unclustered-hover")) { hoverAnimRaf = null; return; }

      const dt = (t - hoverAnimT0) / 1000;
      const wobble = Math.sin(dt * 18) * 6;
      const pulse  = 1.6 + Math.sin(dt * 12) * 0.12;

      map.setLayoutProperty("places-unclustered-hover", "icon-rotate", wobble);
      map.setLayoutProperty("places-unclustered-hover", "icon-size", pulse);

      hoverAnimRaf = requestAnimationFrame(tick);
    };

    hoverAnimRaf = requestAnimationFrame(tick);
  }

  function stopHoverShake() {
    if (hoverAnimRaf) cancelAnimationFrame(hoverAnimRaf);
    hoverAnimRaf = null;

    if (!map || !map.getLayer("places-unclustered-hover")) return;
    map.setLayoutProperty("places-unclustered-hover", "icon-rotate", 0);
    map.setLayoutProperty("places-unclustered-hover", "icon-size", 1.6);
  }

  function startActivePulse() {
    if (!map) return;
    if (activeAnimRaf) cancelAnimationFrame(activeAnimRaf);
    activeAnimT0 = performance.now();

    const tick = (t) => {
      if (!map.getLayer(LAYER_UNCLUSTERED_ACTIVE) || !map.getLayer(LAYER_UNCLUSTERED_ACTIVE_RING)) {
        activeAnimRaf = null;
        return;
      }
      if (selectedId == null) {
        stopActivePulse();
        return;
      }

      const dt = (t - activeAnimT0) / 1000;
      const rippleDuration = 1.15;
      const phase = (dt % rippleDuration) / rippleDuration;
      const ringRadius = 20 + (phase * 13);
      const ringStrokeOpacity = 0.9 * (1 - phase);

      map.setPaintProperty(LAYER_UNCLUSTERED_ACTIVE_RING, "circle-radius", ringRadius);
      map.setPaintProperty(LAYER_UNCLUSTERED_ACTIVE_RING, "circle-stroke-opacity", ringStrokeOpacity);

      activeAnimRaf = requestAnimationFrame(tick);
    };

    activeAnimRaf = requestAnimationFrame(tick);
  }

  function stopActivePulse() {
    if (activeAnimRaf) cancelAnimationFrame(activeAnimRaf);
    activeAnimRaf = null;
    if (!map) return;
    if (map.getLayer(LAYER_UNCLUSTERED_ACTIVE)) {
      map.setLayoutProperty(LAYER_UNCLUSTERED_ACTIVE, "icon-size", 1.55);
    }
    if (map.getLayer(LAYER_UNCLUSTERED_ACTIVE_RING)) {
      map.setPaintProperty(LAYER_UNCLUSTERED_ACTIVE_RING, "circle-radius", 20);
      map.setPaintProperty(LAYER_UNCLUSTERED_ACTIVE_RING, "circle-stroke-opacity", 0.85);
    }
  }

  // ================= BOOT =================
  async function boot() {
    syncHeaderHeight();
    window.addEventListener("resize", syncHeaderHeight);

    renderFilterSkeletons();
    loadCachedLocation();
    await Promise.all([loadPlaces(), loadFilters()]);
    rebuildCategoryMetaIndex();
    fillDistrictOptionsFromFilters();

    let singlePlace = null;
    if (isSingleMode) {
      singlePlace = allPlaces.find(x => x.id === singlePlaceId) || null;
      if (singlePlace && typeof singlePlace.lat === "number" && typeof singlePlace.lng === "number") {
        urlState.initialMap = { lat: singlePlace.lat, lng: singlePlace.lng, zoom: 16 };
      }
    }

    // สร้าง UI จาก filters
    renderAgeRangeButtons();
    renderFacilityPillsTop();
    renderAdmissionPills();
    renderCourseCategoryPillsTop();
    clearFilterSkeletons();
    renderCourseCategoryModalGrid("");
    renderFacilityModalGrid("");

    bindUI();
    renderCategoryUIs();

    // apply URL หลังจาก UI ถูกสร้างแล้ว
    applyStateFromUrl();
    syncFacilityUI();
    renderFacilityPillsTop(); // ให้ top10 สะท้อน state

    await initMap();

    if (isSingleMode && singlePlace) {
      openDrawer(singlePlace, { forceMapOnMobile: true });
    }

    // sync UI ให้ตรง state หลัง apply URL
    syncTagButtonsUI();
    syncFacilityUI();
    syncAdmissionUI();
    syncCourseCategoryUI();
  }

  boot();
</script>
