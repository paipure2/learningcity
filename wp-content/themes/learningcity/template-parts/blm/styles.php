<?php
if (!defined('ABSPATH')) exit;
?>
<style>
  :root { --lc-header-h: 0px; }
  .blm-viewport {
    height: calc(100vh - var(--lc-header-h));
    height: calc(100svh - var(--lc-header-h));
  }
  #blmApp { height: 100%; }

  #map { width: 100%; height: 100%; }

  /* user location marker */
  .me-dot{
    width:16px;height:16px;
    border-radius:9999px;
    background:#2563eb;
    border:2px solid #fff;
    box-shadow:0 2px 10px rgba(37,99,235,.35);
  }

  /* active filter chips under list header */
  .filter-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 10px;
    border-radius:9999px;
    background:#fff;
    border:1px solid rgb(226 232 240);
    font-size:12px;
    cursor:pointer;
    user-select:none;
  }
  .filter-chip:hover{ background: rgb(248 250 252); }
  .filter-chip__x{ font-weight:800; opacity:.6; }

  /* soften borders in BLM UI */
  #blmApp .border,
  #blmApp .border-t,
  #blmApp .border-b,
  #blmApp .border-l,
  #blmApp .border-r{
    border-color: rgb(226 232 240) !important;
  }

  /* line-clamp fallback */
  .line-clamp-2{
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
  }

  #listSectionMobile {
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
  }

  /* svg icon sizing */
  .icon-16 svg{ width:16px;height:16px; display:block; }
  .icon-18 svg{ width:18px;height:18px; display:block; }
  .icon-20 svg{ width:20px;height:20px; display:block; }
  .icon-24 svg{ width:24px;height:24px; display:block; }

  /* search results panel: show above input on mobile */
  @media (max-width: 1023px) {
    #searchPanel {
      top: auto !important;
      bottom: calc(100% + 8px);
      margin-top: 0 !important;
    }
  }

</style>
