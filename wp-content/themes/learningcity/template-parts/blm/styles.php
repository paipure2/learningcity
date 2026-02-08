<?php
if (!defined('ABSPATH')) exit;
?>
<style>
  :root { --lc-header-h: 0px; }
  .blm-viewport {
    height: calc(100vh - var(--lc-header-h));
    height: calc(100svh - var(--lc-header-h));
  }
  #blmApp {
    height: 100%;
    font-family: var(--font-anuphan), system-ui, sans-serif;
    --blm-primary: #00744b;
    --blm-bg-soft: #ffffff;
    --blm-border: #dfdfdf;
    --blm-card: #f6f6f6;
    --blm-col-filter: clamp(320px, 27vw, 390px);
    --blm-col-list: clamp(340px, 28.75vw, 414px);
  }

  .blm-shell { height: 100%; }

  @media (min-width: 1024px) {
    .blm-shell {
      display: grid;
      grid-template-columns: var(--blm-col-filter) var(--blm-col-list) minmax(0, 1fr);
      height: 100%;
    }
    #sidebar { width: auto; max-width: none; }
    #listSectionDesktop { width: auto; }
  }

  #map { width: 100%; height: 100%; }

  body.page-template-page-blm header .header {
    height: 75px !important;
    min-height: 75px !important;
    padding-top: 10px !important;
    padding-bottom: 10px !important;
  }
  body.page-template-page-blm header .logo-site svg {
    width: 120px !important;
  }
  body.page-template-page-blm header .box-right > div:first-child {
    width: 112px !important;
  }
  body.page-template-page-blm header .box-right img {
    width: 100% !important;
    height: auto !important;
  }

  .me-dot{
    width:16px;height:16px;
    border-radius:9999px;
    background:#2563eb;
    border:2px solid #fff;
    box-shadow:0 2px 10px rgba(37,99,235,.35);
  }

  #sidebar {
    background: var(--blm-bg-soft) !important;
    border-right: 1px solid var(--blm-border) !important;
  }
  .blm-sidebar-inner {
    padding: 22px 19px 24px;
    gap: 28px;
    background: var(--blm-bg-soft) !important;
  }
  .blm-heading h1 {
    font-family: var(--font-bkk), var(--font-anuphan), sans-serif;
    color: #000;
    font-size: 28px;
    line-height: 1.4;
    letter-spacing: 0;
  }
  .blm-heading .blm-heading-black { color: #000000; }
  .blm-heading .blm-heading-green { color: var(--blm-primary); }
  .blm-location-box {
    border-radius: 13px !important;
    box-shadow: 0 4px 28px rgba(0, 0, 0, 0.12);
    border: 0 !important;
    background: #fff !important;
  }
  #btnLocate {
    border-radius: 999px !important;
    border: 0 !important;
    background: linear-gradient(180deg, #1893ff 12%, #0045ad 100%) !important;
    font-size: 14px;
    font-weight: 600;
    min-height: 42px;
  }
  #chipNearMe {
    border-radius: 999px !important;
    min-height: 42px;
    border-color: #c8c8c8 !important;
    background: #fff !important;
    font-size: 14px;
    font-weight: 600;
  }
  #sidebar .space-y-2 > .flex > label,
  #sidebar .space-y-2 > label {
    font-size: 16px !important;
    font-weight: 600 !important;
  }
  .blm-view-all {
    font-size: 12px;
    font-weight: 600;
    color: var(--blm-primary);
    text-decoration: none !important;
    line-height: 1;
  }
  .blm-view-all:hover {
    text-decoration: none !important;
    opacity: 0.9;
  }
  #sidebar select,
  #sidebar input[type="number"] {
    border-radius: 8px !important;
    border-color: #111 !important;
  }
  #catGrid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 7px 9px;
  }
  #catGrid > button {
    border-radius: 12px !important;
    min-height: 42px;
    border: 1px solid #e5e5e5 !important;
    background: #fff !important;
    justify-content: flex-start;
    padding: 10px 8px !important;
    font-size: 14px;
  }
  #catGrid > button.bg-emerald-600 {
    background: var(--blm-primary) !important;
    border-color: var(--blm-primary) !important;
    color: #fff !important;
  }
  #ageRangeWrap .tagBtn,
  #facilityWrap .amPill,
  #admissionWrap .adPill,
  #courseCatWrap .ccPill,
  #facilityModalGrid .amPill,
  #courseCatModalGrid .ccPill {
    border-radius: 999px !important;
    min-height: 32px;
    padding: 6px 10px !important;
    border: 1px solid #e5e5e5 !important;
    background: #fff !important;
    font-size: 13px;
    line-height: 1.2;
    font-weight: 400;
  }
  #ageRangeWrap,
  #facilityWrap,
  #admissionWrap,
  #courseCatWrap,
  #facilityModalGrid,
  #courseCatModalGrid {
    gap: 6px !important;
  }
  #ageRangeWrap .bg-emerald-600,
  #facilityWrap .bg-emerald-600,
  #admissionWrap .bg-emerald-600,
  #courseCatWrap .bg-emerald-600 {
    background: var(--blm-primary) !important;
    border-color: var(--blm-primary) !important;
    color: #fff !important;
  }
  #reset {
    width: auto !important;
    border: 0 !important;
    padding: 0 !important;
    color: #ce0000;
    background: transparent !important;
    text-decoration: underline;
    font-size: 14px;
    font-weight: 400;
  }

  #listSectionDesktop {
    background: linear-gradient(180deg, #d1f9eb 0%, #e1ffd8 51%) !important;
    border-right: 1px solid var(--blm-border) !important;
  }
  #listSectionDesktop .sticky {
    background: rgba(209, 249, 235, 0.85) !important;
    border-bottom-color: rgba(0, 0, 0, 0.08) !important;
    backdrop-filter: blur(2px);
  }
  #activeFilters:empty,
  #activeFiltersMobile:empty {
    display: none !important;
    margin-top: 0 !important;
  }

  .filter-chip{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:5px 11px;
    border-radius:9999px;
    background:#fff;
    border:0;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
    font-size:12px;
    cursor:pointer;
    user-select:none;
  }
  .filter-chip:hover{ background: #fff; }
  .filter-chip__x{ font-weight:800; opacity:.6; }

  #list > button,
  #listMobile > button {
    border: 0 !important;
    border-radius: 10px !important;
    background: #ffffff !important;
    box-shadow: 0 7px 20px rgba(0, 0, 0, 0.06);
    padding: 14px 13px !important;
  }
  #list > button:hover,
  #listMobile > button:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.11);
  }
  #list .text-emerald-700,
  #listMobile .text-emerald-700 { color: var(--blm-primary) !important; }

  .line-clamp-2{
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
  }

  #mapSection {
    background: #eae1da;
  }

  #drawer {
    top: var(--lc-header-h) !important;
    height: calc(100svh - var(--lc-header-h)) !important;
    background: transparent !important;
    overflow: hidden;
    pointer-events: none;
  }
  #drawerPanel {
    height: 100%;
    font-family: var(--font-anuphan), system-ui, sans-serif;
    touch-action: pan-y;
    transform: translateX(100%);
    transition: transform 0.28s cubic-bezier(0.2, 0.9, 0.3, 1);
    will-change: transform;
  }
  #drawer .overflow-auto {
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
  }
  #drawer.is-open {
    pointer-events: auto;
  }
  #drawer.is-open #drawerPanel {
    transform: translateX(0);
  }
  .blm-drawer-title {
    font-family: var(--font-bkk), var(--font-anuphan), sans-serif;
    letter-spacing: 0;
    line-height: 1.4;
  }
  #dDistance.blm-distance-box {
    width: 78px;
    min-height: 47px;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 0 17.3px rgba(0, 0, 0, 0.1);
    padding: 5px 8px 4px;
    text-align: right;
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: center;
    gap: 1px;
  }
  #dDistance .lbl {
    color: #000;
    font-size: 12px;
    line-height: 1.1;
    font-weight: 600;
  }
  #dDistance .val {
    color: #00744b;
    font-size: 18px;
    line-height: 1.1;
    font-weight: 700;
  }
  .blm-desc-text {
    font-size: 15px;
    line-height: 1.5;
    color: #000;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .blm-desc-text.is-expanded {
    display: block;
  }
  #rowDesc {
    display: flex;
    flex-direction: column;
    gap: 9px;
  }
  .blm-readmore {
    margin-top: 0;
    color: #00744b;
    font-size: 12px;
    line-height: 1.5;
    font-weight: 600;
    width: fit-content;
  }
  .blm-drawer-tabs {
    height: 46px;
    width: 100%;
    border-radius: 999px;
    padding: 5px;
    background: #f2f2f2;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
    display: flex;
    gap: 0;
  }
  .blm-drawer-tabs .tabBtn {
    height: 36px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.73);
    white-space: nowrap;
    transition: all 0.2s ease;
  }
  #tabBtnDetails.tabBtn {
    flex: 0 0 46%;
  }
  #tabBtnCourses.tabBtn {
    flex: 1;
  }
  .blm-drawer-tabs .tabBtn.is-active {
    background: linear-gradient(180deg, #00da8d 0%, #00744b 100%);
    color: #fff;
  }
  .blm-meta-row {
    display: flex;
    gap: 12px;
    align-items: center;
  }
  .blm-meta-icon {
    width: 24px;
    min-width: 24px;
    text-align: center;
    line-height: 1;
    margin-top: 0;
    color: #1f1f1f;
  }
  .blm-meta-icon-svg > span {
    display: inline-flex;
    width: 24px;
    height: 24px;
    align-items: center;
    justify-content: center;
  }
  #dGmaps.blm-gmaps-btn {
    display: inline-flex;
    align-items: center;
    border: 1px solid #000;
    border-radius: 999px;
    min-height: 33px;
    padding: 8px 13px;
    font-size: 14px;
    color: #000;
    line-height: 1;
  }
  #dTags > span {
    background: #fff !important;
    border: 1px solid #d4d4d4 !important;
    border-radius: 999px !important;
    min-height: 27px;
    padding: 8px 12px !important;
    font-size: 14px !important;
    line-height: 1 !important;
    color: #000 !important;
    display: inline-flex;
    align-items: center;
  }
  #dAmenities > span {
    background: #d1f9eb !important;
    border: 0 !important;
    border-radius: 999px !important;
    min-height: 27px;
    padding: 8px 12px !important;
    font-size: 14px !important;
    line-height: 1 !important;
    color: #000 !important;
    display: inline-flex;
    align-items: center;
  }
  #imgGrid a img {
    height: 116px !important;
    border-radius: 12px !important;
    border: 0 !important;
  }

  @media (min-width: 1024px) {
    #drawer {
      left: var(--blm-col-filter) !important;
      right: auto !important;
      width: var(--blm-col-list) !important;
    }
  }

  #searchOverlay { pointer-events: none; }
  #searchOverlay > * { pointer-events: auto; }
  #searchBox {
    border-radius: 8px !important;
    border-color: rgba(0, 0, 0, 0.08) !important;
    background: transparent !important;
    box-shadow: 0 1px 16px rgba(0, 0, 0, 0.1) !important;
  }
  #searchBox label {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
  }
  #q {
    height: 42px;
    border-radius: 8px !important;
    border: 0 !important;
    background-color: #ffffff !important;
    font-size: 16px;
    font-weight: 600;
    color: #000000;
    padding-left: 14px !important;
    padding-right: 36px !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23111111' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
  }
  #searchBox:has(#searchStatus:not(.hidden)) #q {
    border-radius: 8px 8px 0 0 !important;
  }
  #searchStatus {
    background: rgba(0, 0, 0, 0.28) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    color: #ffffff !important;
    border: 0 !important;
    border-radius: 0 0 8px 8px !important;
    margin-top: 0 !important;
    min-height: 35px;
    padding: 8px 12px !important;
    font-size: 14px !important;
  }
  #searchStatus:not(.hidden) {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  #searchStatus #btnClearSearch { color: #ffffff; }
  #searchPanel {
    margin-top: 4px !important;
    border-radius: 8px !important;
    border-color: rgba(0, 0, 0, 0.08) !important;
    box-shadow: 0 1px 16px rgba(0, 0, 0, 0.1) !important;
    background: #ffffff !important;
  }
  #searchPanel .search-panel-head,
  #searchPanel .search-panel-foot {
    font-size: 12px !important;
    color: rgba(0, 0, 0, 0.4) !important;
    background: #ffffff;
  }
  #searchResults > button {
    padding-top: 9px !important;
    padding-bottom: 9px !important;
    border-bottom-color: rgba(0, 0, 0, 0.12) !important;
  }
  #searchResults .search-result-row {
    display: grid;
    grid-template-columns: 18px minmax(0, 1fr);
    column-gap: 8px;
    align-items: start;
  }
  #searchResults .result-icon {
    display: inline-flex;
    width: 18px;
    height: 18px;
    align-items: center;
    justify-content: center;
    margin-top: 0;
  }
  #searchResults .result-main {
    min-width: 0;
  }
  #searchResults .result-title {
    font-size: 14px;
    font-weight: 600;
    color: #000000;
    line-height: 1.25;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    white-space: normal;
  }
  #searchResults .result-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  #searchResults .result-sub {
    font-size: 12px;
    color: #000000;
    line-height: 1.2;
    margin-top: 3px;
  }
  #searchResults .result-distance {
    font-size: 12px;
    font-weight: 700;
    color: #00744b;
    white-space: nowrap;
    margin-top: 3px;
  }

  #listSectionMobile {
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
  }

  #blmApp .border,
  #blmApp .border-t,
  #blmApp .border-b,
  #blmApp .border-l,
  #blmApp .border-r{
    border-color: var(--blm-border) !important;
  }

  .icon-16 svg{ width:16px;height:16px; display:block; }
  .icon-18 svg{ width:18px;height:18px; display:block; }
  .icon-20 svg{ width:20px;height:20px; display:block; }
  .icon-24 svg{ width:24px;height:24px; display:block; }

  @media (max-width: 1023px) {
    .blm-heading h1 {
      font-size: 28px;
      line-height: 1.4;
    }
    #drawer {
      top: 0 !important;
      height: 100vh !important;
      height: 100svh !important;
      width: 100% !important;
      left: 0 !important;
      right: 0 !important;
    }
    #drawerPanel {
      background: #fff;
    }
    #drawer .overflow-auto {
      padding: 17px 23px 24px !important;
    }
    #sidebar { background: #fff !important; }
    #searchPanel {
      top: auto !important;
      bottom: calc(100% + 8px);
      margin-top: 0 !important;
    }
  }
</style>
