<?php
if (!defined('ABSPATH')) exit;
?>
<script>
  // ================= CONFIG =================
  const MAPTILER_KEY = "A9j0Af0Z3BiCSKcrWllM";
  const MAPTILER_STYLE = `https://api.maptiler.com/maps/streets-v2/style.json?key=${MAPTILER_KEY}`;
  const SITE_PATH = "<?php echo esc_js((string) wp_parse_url(home_url(), PHP_URL_PATH)); ?>";
  const SITE_BASE = window.location.origin + (SITE_PATH || "");
  const SITE_ROOT = SITE_BASE.replace(/\/+$/, "");
  const BLM_API_BASE = `${SITE_ROOT}/wp-json/blm/v1`;
  const BLM_LIST_PLACEHOLDER = `${SITE_ROOT}/wp-content/themes/learningcity/assets/images/placeholder-gray.png`;
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
  const PLACES_CACHE_KEY = "lc_blm_places_cache_v3";
  const LOCATION_CACHE_TTL_MS = 1000 * 60 * 60 * 24 * 30; // 30 days
  const PLACES_CACHE_TTL_MS = 1000 * 60 * 30; // 30 minutes
  const WELCOME_SEEN_KEY = "lc_blm_welcome_seen_v1";
  const API_CACHE_BUST = String(Date.now());

  let cachedNear = null;
  let cachedRadius = null;

  // ================= SVG ICONS =================
  const GMSVG = (path, viewBox = "0 -960 960 960") =>
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${viewBox}" fill="currentColor" aria-hidden="true"><path d="${path}"/></svg>`;

  const ICON_SVGS = {
    default: GMSVG("M480-80q-61 0-116-23.5T267-169q-42-42-65.5-97T178-382q0-67 25.5-127.5T273-615q44-44 103-68.5T504-708q67 0 127.5 25.5T735-613q44 44 68.5 103T829-382q0 61-23.5 116T740-169q-42 42-97 65.5T527-80h-47Zm23-323q33 0 56.5-23.5T583-483q0-33-23.5-56.5T503-563q-33 0-56.5 23.5T423-483q0 33 23.5 56.5T503-403Z"),
    library: GMSVG("M480-60q-72-68-165-104t-195-36v-440q101 0 194 36.5T480-498q73-69 166-105.5T840-640v440q-103 0-195.5 36T480-60Zm0-104q63-47 134-75t146-37v-276q-73 13-143.5 52.5T480-394q-66-66-136.5-105.5T200-552v276q75 9 146 37t134 75ZM367-647q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47Zm169.5-56.5Q560-727 560-760t-23.5-56.5Q513-840 480-840t-56.5 23.5Q400-793 400-760t23.5 56.5Q447-680 480-680t56.5-23.5ZM480-760Zm0 366Z"),
    museum: GMSVG("M120-120v-80h720v80H120Zm80-120v-280h-80v-80l360-240 360 240v80h-80v280h80v80H120v-80h80Zm80 0h120v-280H280v280Zm200 0h120v-280H480v280Z"),
    park: GMSVG("M440-120v-240H300l180-260-90-140h170v-120h80v120h170l-90 140 180 260H520v240h-80Z"),
    learning_center: GMSVG("M480-140 240-280v-240l-120-70 360-210 360 210v240L480-140Zm0-92 280-160v-252L480-804 200-644v252l280 160Zm0-228Z"),
    science: GMSVG("M280-80q-33 0-56.5-23.5T200-160q0-14 4.5-27t13.5-24l202-279v-230h-80v-80h280v80h-80v230l202 279q9 11 13.5 24t4.5 27q0 33-23.5 56.5T680-80H280Zm54-80h292L500-336 374-160Z"),
    art: GMSVG("M480-80q-83 0-156-31.5t-127-86Q143-252 111.5-325T80-480q0-83 31.5-156t86-127Q252-817 325-848.5T480-880q83 0 156 31.5t127 86Q817-708 848.5-635T880-480q0 74-35.5 126T756-272H620q-20 0-35 14.5T570-222q0 10 5 22t10 20q6 8 11 18.5t5 23.5q0 26-18.5 42T540-80h-60Zm-160-400q17 0 28.5-11.5T360-520q0-17-11.5-28.5T320-560q-17 0-28.5 11.5T280-520q0 17 11.5 28.5T320-480Zm160-80q17 0 28.5-11.5T520-600q0-17-11.5-28.5T480-640q-17 0-28.5 11.5T440-600q0 17 11.5 28.5T480-560Zm160 80q17 0 28.5-11.5T680-520q0-17-11.5-28.5T640-560q-17 0-28.5 11.5T600-520q0 17 11.5 28.5T640-480Zm80 160q17 0 28.5-11.5T760-360q0-17-11.5-28.5T720-400q-17 0-28.5 11.5T680-360q0 17 11.5 28.5T720-320Z"),
    history: GMSVG("M480-200q-117 0-198.5-81.5T200-480q0-117 81.5-198.5T480-760q68 0 128 31.5T710-642v-118h80v280H510v-80h167q-26-57-79.5-88.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280q58 0 107-31t73-82h84q-28 86-99.5 139.5T480-200Zm40-140-200-120v-160h80v114l160 96-40 70Z"),
    kids: GMSVG("M400-560q-50 0-85-35t-35-85q0-50 35-85t85-35q50 0 85 35t35 85q0 50-35 85t-85 35Zm-240 400v-112q0-34 17.5-62.5T224-378q62-31 117-46.5T400-440q59 0 114.5 15.5T624-378q30 15 47 43.5t17 62.5v112H160Z"),
    community: GMSVG("M40-160v-112q0-34 17-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q30 15 47 43.5t17 62.5v112H40Zm720 0v-112q0-34-17-62.5T696-378q-20-10-41-18t-43-15q49 34 78.5 83T720-240v80h40Zm0-360q-50 0-85-35t-35-85q0-50 35-85t85-35q50 0 85 35t35 85q0 50-35 85t-85 35Zm-400 0q-50 0-85-35t-35-85q0-50 35-85t85-35q50 0 85 35t35 85q0 50-35 85t-85 35Z"),
    coworking: GMSVG("M160-120v-480q0-33 23.5-56.5T240-680h160v-80q0-33 23.5-56.5T480-840h160q33 0 56.5 23.5T720-760v80h160q33 0 56.5 23.5T960-600v480H160Zm320-560h160v-80H480v80ZM240-200h640v-400H240v400Z"),
    sport: GMSVG("M120-120v-200h80v-200q0-33 23.5-56.5T280-600h160v-80H320v-80h320v80H520v80h160q33 0 56.5 23.5T760-520v200h80v200h-80v-120H200v120h-80Z"),
    book_house: GMSVG("M120-120v-440l360-280 360 280v440H560v-240H400v240H120Z"),
    museum_kids: GMSVG("M120-120v-80h720v80H120Zm80-120v-280h-80v-80l360-240 360 240v80h-80v280h80v80H120v-80h80Zm280-80q33 0 56.5-23.5T560-400q0-33-23.5-56.5T480-480q-33 0-56.5 23.5T400-400q0 33 23.5 56.5T480-320Z"),
    museum_local: GMSVG("M80-80v-80h80v-360H80v-80l400-280 400 280v80h-80v360h80v80H80Zm160-80h480-480Zm80-80h80v-160l80 120 80-120v160h80v-280h-80l-80 120-80-120h-80v280Zm400 80v-454L480-782 240-614v454h480Z"),
    indie_bookstore: GMSVG("M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560v640H240q-17 0-28.5 11.5T200-160h560v80H200Zm40-160h440v-480H240v480Zm0-480v480-480Z"),
    vocational_school: GMSVG("M160-120q-33 0-56.5-23.5T80-200v-440q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v440q0 33-23.5 56.5T800-120H160Zm240-600h160v-80H400v80Zm400 360H600v80H360v-80H160v160h640v-160Zm-360 0h80v-80h-80v80Zm-280-80h200v-80h240v80h200v-200H160v200Zm320 40Z"),
    skill_center: GMSVG("m352-522 86-87-56-57-44 44-56-56 43-44-45-45-87 87 159 158Zm328 329 87-87-45-45-44 43-56-56 43-44-57-56-86 86 158 159Zm24-567 57 57-57-57ZM290-120H120v-170l175-175L80-680l200-200 216 216 151-152q12-12 27-18t31-6q16 0 31 6t27 18l53 54q12 12 18 27t6 31q0 16-6 30.5T816-647L665-495l215 215L680-80 465-295 290-120Zm-90-80h56l392-391-57-57-391 392v56Zm420-419-29-29 57 57-28-28Z"),
    bma_school: GMSVG("M480-120 200-272v-240L40-600l440-240 440 240v320h-80v-276l-80 44v240L480-120Zm0-332 274-148-274-148-274 148 274 148Zm0 241 200-108v-151L480-360 280-470v151l200 108Zm0-241Zm0 90Zm0 0Z"),
    sports_field: GMSVG("M80-105.5q0-9.5 8-17.5l151-140 21-157 103-214q15-32 47.5-42t64.5 7l135 70 182-5-9-34q-20-4-38-17.5T708-695q-14-20-27-43t-26-54q-14-33 2.5-66t51.5-42l65-17q35-9 65 10.5t35 55.5q5 32 5.5 59.5T877-739q-4 32-13 53t-24 34l44 162-58 15-21-77-187 29q-12 2-24 0t-23-7l-42-20-99 136 244 293q8 9 6 18.5T672-87q-6 6-15.5 7.5T638-86L362-314l-37 83q-5 11-12.5 20T295-196L118-84q-10 6-19 4t-14-9q-5-7-5-16.5Zm496.5-751Q600-833 600-800t-23.5 56.5Q553-720 520-720t-56.5-23.5Q440-767 440-800t23.5-56.5Q487-880 520-880t56.5 23.5ZM799-696q8-2 11.5-9.5T818-736q4-22 3.5-47.5T816-843q-2-9-9.5-14t-16.5-3l-65 18q-9 2-13.5 10.5T711-815q14 33 27 56t29 40q13 14 18.5 18.5T799-696Z"),
    sports_center: GMSVG("m414-168 12-56q3-13 12.5-21.5T462-256l124-10q13-2 24 5t16 19l16 38q39-23 70-55.5t52-72.5l-12-6q-11-8-16-19.5t-2-24.5l28-122q3-12 12.5-20t21.5-10q-5-25-12.5-48.5T764-628q-9 5-19.5 4.5T726-630l-106-64q-11-7-16-19t-2-25l8-34q-31-14-63.5-21t-66.5-7q-14 0-29 1.5t-29 4.5l30 68q5 12 2.5 25T442-680l-94 82q-10 9-23.5 10t-24.5-6l-92-56q-23 38-35.5 81.5T160-480q0 16 4 52l88-8q14-2 25.5 4.5T294-412l48 114q5 12 2.5 25T332-252l-38 32q27 20 57.5 33t62.5 19Zm72-172q-13 2-24-5t-16-19l-54-124q-5-12-1.5-25t13.5-21l102-86q9-9 22-10t24 6l112 66q11 7 17 19t3 25l-32 130q-3 13-12 21.5T618-352l-132 12Zm-6 260q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"),
    recreation_center: GMSVG("M127-167q-47-47-47-113t47-113q47-47 113-47 23 0 42.5 5.5T320-418v-342l480-80v480q0 66-47 113t-113 47q-66 0-113-47t-47-113q0-66 47-113t113-47q23 0 42.5 5.5T720-498v-165l-320 63v320q0 66-47 113t-113 47q-66 0-113-47Z"),
    senior_center: GMSVG("m320-40-64-48 104-139v-213q0-31 5-67.5t15-67.5l-60 33v142h-80v-188l176-100q25-14 43.5-21.5T494-717q25 0 45.5 21.5T587-628q32 54 58 81t56 41q11-8 19-11t19-3q25 0 43 18t18 42v420h-40v-420q0-8-6-14t-14-6q-8 0-14 6t-6 14v50h-40v-19q-54-23-84-51.5T543-557q-11 28-17.5 68.5T521-412l79 112v260h-80v-200l-71-102-9 142L320-40Zm220-700q-33 0-56.5-23.5T460-820q0-33 23.5-56.5T540-900q33 0 56.5 23.5T620-820q0 33-23.5 56.5T540-740Z"),
    child_dev_center: GMSVG("M371.5-273Q323-306 300-360h360q-23 54-71.5 87T480-240q-60 0-108.5-33Zm-27-161.5Q330-449 330-470t14.5-35.5Q359-520 380-520t35.5 14.5Q430-491 430-470t-14.5 35.5Q401-420 380-420t-35.5-14.5Zm200 0Q530-449 530-470t14.5-35.5Q559-520 580-520t35.5 14.5Q630-491 630-470t-14.5 35.5Q601-420 580-420t-35.5-14.5ZM480-80q-75 0-140.5-28.5t-114-77q-48.5-48.5-77-114T120-440q0-32 5-62t16-59l80 14q-11 25-16 51.5t-5 55.5q0 117 81.5 198.5T480-160q117 0 198.5-81.5T760-440q0-16-2-31.5t-5-30.5q-81-9-150-48T485-651l70-41q32 37 72.5 63t88.5 39q-25-39-61.5-68.5T573-704l84-50q83 47 133 129.5T840-440q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-80ZM200-615l413-155q-32-26-70-39.5T463-823q-95 0-169.5 57.5T200-615Zm-64 110q-7-20-11.5-41t-4.5-43q0-91 51-163t129-112q-2-4-2.5-7.5t-.5-8.5q0-17 11.5-28.5T337-920q14 0 24 8t14 20q22-5 43.5-8t44.5-3q67 0 127.5 26T697-802l122-46 28 75-711 268Zm271-188Z"),
    public_park: GMSVG("M200-80v-80h240v-160h-80q-83 0-141.5-58.5T160-520q0-60 33-110.5t89-73.5q9-75 65.5-125.5T480-880q76 0 132.5 50.5T678-704q56 23 89 73.5T800-520q0 83-58.5 141.5T600-320h-80v160h240v80H200Zm160-320h240q50 0 85-35t35-85q0-36-20.5-66T646-630l-42-18-6-46q-6-45-39.5-75.5T480-800q-45 0-78.5 30.5T362-694l-6 46-42 18q-33 14-53.5 44T240-520q0 50 35 85t85 35Zm120-200Z"),
    art_gallery: GMSVG("M120-120v-720h720v720H120Zm80-80h560v-560H200v560Zm80-80h400L640-440 520-300l-80-100-160 200Z"),
    online: GMSVG("M160-120v-80h280v-80H120v-560h720v560H520v80h280v80H160Zm40-240h560v-320H200v320Z")
  };

  // ================= CATEGORY META =================
  const DEFAULT_CATEGORY_COLOR = "#205a41";
  const CATEGORY_COLOR_PALETTE = [
    "#205a41", "#67a33b", "#00c08b", "#ffb449", "#f8df52", "#0071ce", "#ec3faa"
  ];

  const CATEGORY_META = {
    library: { label: "ห้องสมุด", iconKey: "library", color: "#0071ce" },
    museum: { label: "พิพิธภัณฑ์", iconKey: "museum", color: "#ec3faa" },
    park: { label: "สวน/ธรรมชาติ", iconKey: "park", color: "#67a33b" },
    learning_center: { label: "ศูนย์เรียนรู้", iconKey: "learning_center", color: "#00c08b" },
    science: { label: "วิทยาศาสตร์", iconKey: "science", color: "#0071ce" },
    art: { label: "ศิลปะ", iconKey: "art", color: "#ec3faa" },
    history: { label: "ประวัติศาสตร์", iconKey: "history", color: "#205a41" },
    kids: { label: "เด็ก/ครอบครัว", iconKey: "kids", color: "#ffb449" },
    community: { label: "ชุมชน", iconKey: "community", color: "#00c08b" },
    coworking: { label: "Co-working", iconKey: "coworking", color: "#205a41" },
    sport: { label: "กีฬา", iconKey: "sport", color: "#ffb449" }
  };

  const CATEGORY_LABEL_META = {
    "บ้านหนังสือ": { iconKey: "book_house", color: "#0071ce" },
    "พิพิธภัณฑ์เด็ก": { iconKey: "museum_kids", color: "#ffb449" },
    "พิพิธภัณฑ์ท้องถิ่น": { iconKey: "museum_local", color: "#ec3faa" },
    "ร้านหนังสืออิสระ": { iconKey: "indie_bookstore", color: "#0071ce" },
    "โรงเรียนฝึกอาชีพ": { iconKey: "vocational_school", color: "#ffb449" },
    "ศูนย์ฝึกอาชีพ": { iconKey: "skill_center", color: "#ffb449" },
    "โรงเรียนสังกัดกทม.": { iconKey: "bma_school", color: "#00c08b" },
    "ลานกีฬา": { iconKey: "sports_field", color: "#ffb449" },
    "ศูนย์กีฬา": { iconKey: "sports_center", color: "#ec3faa" },
    "ศูนย์นันทนาการ": { iconKey: "recreation_center", color: "#ec3faa" },
    "ศูนย์บริการผู้สูงอายุ": { iconKey: "senior_center", color: "#205a41" },
    "ศูนย์พัฒนาเด็กเล็ก": { iconKey: "child_dev_center", color: "#67a33b" },
    "สวนสาธารณะ": { iconKey: "public_park", color: "#67a33b" },
    "ห้องสมุด": { iconKey: "library", color: "#0071ce" },
    "หอศิลป์": { iconKey: "art_gallery", color: "#ec3faa" },
    "ออนไลน์": { iconKey: "online", color: "#205a41" }
  };

  const CATEGORY_ICON_RULES = [
    { test: /(บ้านหนังสือ)/, iconKey: "book_house" },
    { test: /(พิพิธภัณฑ์เด็ก)/, iconKey: "museum_kids" },
    { test: /(พิพิธภัณฑ์ท้องถิ่น)/, iconKey: "museum_local" },
    { test: /(ร้านหนังสืออิสระ)/, iconKey: "indie_bookstore" },
    { test: /(โรงเรียนฝึกอาชีพ)/, iconKey: "vocational_school" },
    { test: /(ศูนย์ฝึกอาชีพ)/, iconKey: "skill_center" },
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
    "book_house","museum_kids","museum_local","indie_bookstore","vocational_school","skill_center","bma_school",
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

  function colorToRgba(color, alpha = 0.12) {
    const c = String(color || "").trim();
    if (!c) return `rgba(15, 23, 42, ${alpha})`;

    const hex = c.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
    if (hex) {
      let h = hex[1];
      if (h.length === 3) h = h.split("").map((x) => x + x).join("");
      const r = parseInt(h.slice(0, 2), 16);
      const g = parseInt(h.slice(2, 4), 16);
      const b = parseInt(h.slice(4, 6), 16);
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    const rgb = c.match(/^rgba?\(([^)]+)\)$/i);
    if (rgb) {
      const parts = rgb[1].split(",").map((x) => Number(x.trim()));
      const [r, g, b] = parts;
      if (Number.isFinite(r) && Number.isFinite(g) && Number.isFinite(b)) {
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
      }
    }

    return c;
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
  async function loadFullForId(id, options = {}) {
    const silent = !!options.silent;
    if (fullCache.has(id)) return fullCache.get(id);
    try {
      if (!silent) setApiLoading(true, "กำลังโหลดรายละเอียด...", "drawer");
      const res = await fetch(withNoCache(`${BLM_API_BASE}/location/${id}`), {
        cache: "no-store",
        headers: { Accept: "application/json" }
      });
      if (!res.ok) return null;
      const full = await res.json();
      fullCache.set(id, full);
      return full;
    } finally {
      if (!silent) setApiLoading(false, "", "drawer");
    }
  }

  const state = {
    district: "",
    categories: new Set(),
    tags: new Set(),       // age_range slugs
    amenities: new Set(),  // facility slugs
    admissionPolicies: new Set(), // admission_policy slugs
    courseCategories: new Set(), // course_category slugs
    courseMode: "", // "", "has_course", "no_course"
    courseLocationIds: new Set(), // location IDs from course context
    nearMeEnabled: false,
    radiusKm: 5
  };

  let searchQuery = "";
  let mobileView = "map";
  let mobileSheetExpanded = false;
  let isSearching = false;
  let suppressMapClicksUntil = 0;
  let suppressSheetHandleClickUntil = 0;
  let suppressSheetCardClickUntil = 0;
  const DRAWER_ANIM_MS = 280;
  let drawerHideTimer = null;

  let listLimit = LIST_PAGE_SIZE;
  let lastVisible = [];
  let isLoadingMore = false;
  const INFINITE_LOAD_OFFSET_PX = 220;
  const NEAR_RADIUS_MIN_KM = 1;
  const NEAR_RADIUS_MAX_KM = 20;
  const NEAR_RADIUS_VIEW_PADDING = 1.45; // zoom out slightly so the full radius ring is visible
  let infiniteDesktopObserver = null;
  let infiniteMobileObserver = null;

  const PLACES_SOURCE_ID = "places-src";
  const LAYER_CLUSTER_CIRCLE = "places-cluster-circle";
  const LAYER_CLUSTER_COUNT  = "places-cluster-count";
  const LAYER_UNCLUSTERED    = "places-unclustered";
  const LAYER_UNCLUSTERED_ACTIVE_RING = "places-unclustered-active-ring";
  const LAYER_UNCLUSTERED_ACTIVE = "places-unclustered-active";
  const LAYER_UNCLUSTERED_LABEL = "places-unclustered-label";
  const PAIR_ICON_REGISTRY = new Map();
  const NEAR_RADIUS_SOURCE_ID = "near-radius-src";
  const LAYER_NEAR_RADIUS_FILL = "near-radius-fill";
  const LAYER_NEAR_RADIUS_LINE = "near-radius-line";
  // Thailand extent guard (prevent zoom/pan too far out)
  const TH_BOUNDS = [[97.2, 5.2], [105.9, 21.0]];
  const TH_MIN_ZOOM = 5.1;

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

  function escHtml(v) {
    return String(v == null ? "" : v)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
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
      course_mode: state.courseMode || "",
      course_locs: Array.from(state.courseLocationIds || []),
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
    _setOrDelete(sp, "course_mode", s.course_mode);
    _setCsvOrDelete(sp, "course_locs", s.course_locs);

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

  function loadPlacesCache() {
    try {
      const raw = localStorage.getItem(PLACES_CACHE_KEY);
      if (!raw) return null;
      const data = JSON.parse(raw);
      if (!data || !Array.isArray(data.places)) return null;
      if (!data.ts || (Date.now() - data.ts > PLACES_CACHE_TTL_MS)) return null;
      return data.places;
    } catch (e) {
      return null;
    }
  }

  function savePlacesCache(places) {
    try {
      localStorage.setItem(PLACES_CACHE_KEY, JSON.stringify({
        ts: Date.now(),
        places: Array.isArray(places) ? places : []
      }));
    } catch (e) {
      // ignore storage errors
    }
  }

  function withNoCache(url) {
    const sep = String(url).includes("?") ? "&" : "?";
    return `${url}${sep}_=${API_CACHE_BUST}`;
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
      course_mode: sp.get("course_mode") || "",
      course_locs: _parseList(sp.get("course_locs")),
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
    state.courseMode = u.course_mode || "";
    if (!state.courseMode) {
      if (state.courseCategories.has("has_course")) state.courseMode = "has_course";
      else if (state.courseCategories.has("no_course")) state.courseMode = "no_course";
    }
    state.courseCategories.clear();
    state.courseLocationIds.clear();
    u.course_locs.forEach(id => {
      const n = Number(id);
      if (Number.isFinite(n) && n > 0) state.courseLocationIds.add(String(Math.trunc(n)));
    });

    // sync dynamic UIs
    syncTagButtonsUI();
    syncFacilityUI();
    syncAdmissionUI();
    syncCourseCategoryUI();

    state.nearMeEnabled = !!u.near;
    state.radiusKm = normalizeRadiusKm(u.radius);
    el("radiusKm").value = state.radiusKm;
    updateRadiusLabel();

    el("nearMeRadiusWrap").classList.toggle("hidden", !state.nearMeEnabled);
    setNearMeChipUI();

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

  function calcZoomForRadiusKm(radiusKm) {
    if (!map || !userLocation) return null;
    const r = Math.max(NEAR_RADIUS_MIN_KM, Number(radiusKm) || 5);
    const latRad = (userLocation.lat * Math.PI) / 180;
    const cosLat = Math.max(0.2, Math.cos(latRad));
    const minPx = Math.max(280, Math.min(window.innerWidth, window.innerHeight));
    const targetRadiusPx = minPx * 0.32;
    const meters = r * NEAR_RADIUS_VIEW_PADDING * 1000;
    const zoom = Math.log2((156543.03392 * cosLat * targetRadiusPx) / meters);
    return Math.max(9, Math.min(17.5, zoom));
  }

  function syncMapZoomToRadius(radiusKm, opts = {}) {
    if (!map || !userLocation || !state.nearMeEnabled) return;
    const targetZoom = calcZoomForRadiusKm(radiusKm);
    if (!Number.isFinite(targetZoom)) return;
    const currentZoom = map.getZoom();
    if (Math.abs(currentZoom - targetZoom) < 0.08) return;

    const duration = Number.isFinite(opts.duration) ? opts.duration : 180;
    map.stop?.();
    map.easeTo({
      center: [userLocation.lng, userLocation.lat],
      zoom: targetZoom,
      duration,
      essential: true
    });
  }

  function nearRadiusEmptyGeojson() {
    return { type: "FeatureCollection", features: [] };
  }

  function makeRadiusCircleGeojson(lat, lng, radiusKm, steps = 96) {
    const earthKm = 6371;
    const latRad = (lat * Math.PI) / 180;
    const angDist = radiusKm / earthKm;
    const coords = [];

    for (let i = 0; i <= steps; i += 1) {
      const bearing = (i / steps) * (2 * Math.PI);
      const lat2 = Math.asin(
        Math.sin(latRad) * Math.cos(angDist) +
        Math.cos(latRad) * Math.sin(angDist) * Math.cos(bearing)
      );
      const lng2 = (lng * Math.PI) / 180 + Math.atan2(
        Math.sin(bearing) * Math.sin(angDist) * Math.cos(latRad),
        Math.cos(angDist) - Math.sin(latRad) * Math.sin(lat2)
      );
      coords.push([(lng2 * 180) / Math.PI, (lat2 * 180) / Math.PI]);
    }

    return {
      type: "FeatureCollection",
      features: [{
        type: "Feature",
        geometry: { type: "Polygon", coordinates: [coords] },
        properties: {}
      }]
    };
  }

  function ensureNearRadiusLayers() {
    if (!map) return;
    if (!map.getSource(NEAR_RADIUS_SOURCE_ID)) {
      map.addSource(NEAR_RADIUS_SOURCE_ID, {
        type: "geojson",
        data: nearRadiusEmptyGeojson()
      });
    }

    if (!map.getLayer(LAYER_NEAR_RADIUS_FILL)) {
      map.addLayer({
        id: LAYER_NEAR_RADIUS_FILL,
        type: "fill",
        source: NEAR_RADIUS_SOURCE_ID,
        paint: {
          "fill-color": "#00744b",
          "fill-opacity": 0.05
        }
      }, LAYER_UNCLUSTERED);
    }

    if (!map.getLayer(LAYER_NEAR_RADIUS_LINE)) {
      map.addLayer({
        id: LAYER_NEAR_RADIUS_LINE,
        type: "line",
        source: NEAR_RADIUS_SOURCE_ID,
        paint: {
          "line-color": "#00744b",
          "line-width": 1.5,
          "line-opacity": 0.55
        }
      }, LAYER_UNCLUSTERED);
    }
  }

  function syncNearRadiusOverlay() {
    if (!map || !map.getSource(NEAR_RADIUS_SOURCE_ID)) return;
    const src = map.getSource(NEAR_RADIUS_SOURCE_ID);
    const enabled = !!state.nearMeEnabled && !!userLocation && Number.isFinite(state.radiusKm);

    if (!enabled) {
      src.setData(nearRadiusEmptyGeojson());
      return;
    }

    src.setData(makeRadiusCircleGeojson(userLocation.lat, userLocation.lng, state.radiusKm));
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

  function getIconCategoriesForPlace(place, limit = 2) {
    const out = [];
    const seen = new Set();
    for (const c of getPlaceCategories(place)) {
      const key = String(c || "").trim();
      if (!key || seen.has(key)) continue;
      seen.add(key);
      out.push(key);
      if (out.length >= limit) break;
    }
    if (!out.length) out.push(getPrimaryCategory(place) || "default");
    return out;
  }

  function pairSignatureFromCategories(catA, catB) {
    return `${String(catA || "").trim()}||${String(catB || "").trim()}`;
  }

  function ensurePairIconId(catA, catB) {
    const sig = pairSignatureFromCategories(catA, catB);
    if (!PAIR_ICON_REGISTRY.has(sig)) {
      PAIR_ICON_REGISTRY.set(sig, `pair-${hashString(sig)}`);
    }
    return PAIR_ICON_REGISTRY.get(sig);
  }

  function getMapIconIdForPlace(place) {
    const cats = getIconCategoriesForPlace(place, 2);
    if (cats.length >= 2) {
      const pairId = ensurePairIconId(cats[0], cats[1]);
      return `blm-${pairId}`;
    }
    return `blm-${(cats[0] || "default")}`;
  }

  function buildSplitBadgeHtml(catA, catB, options = {}) {
    const mA = catMeta(catA);
    const mB = catMeta(catB);
    const iconA = getIconKeyFromCategory(catA);
    const iconB = getIconKeyFromCategory(catB);
    const w = Number(options.width || 46);
    const h = Number(options.height || 30);
    const r = Number(options.radius || 8);
    const iconSizeClass = options.iconSizeClass || "icon-16";

    return `
      <span style="display:inline-flex;overflow:hidden;border-radius:${r}px;width:${w}px;height:${h}px;box-shadow:inset 0 0 0 1px rgba(15,23,42,.08)">
        <span style="display:inline-flex;align-items:center;justify-content:center;width:50%;height:100%;background:${mA.color || DEFAULT_CATEGORY_COLOR};color:#fff">${svgForDom(iconA, iconSizeClass)}</span>
        <span style="display:inline-flex;align-items:center;justify-content:center;width:50%;height:100%;background:${mB.color || DEFAULT_CATEGORY_COLOR};color:#fff">${svgForDom(iconB, iconSizeClass)}</span>
      </span>
    `.trim();
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
    gmaps: `<svg viewBox="0 -960 960 960" aria-hidden="true"><path fill="currentColor" d="m600-120-240-84-186 72q-20 8-37-4.5T120-170v-560q0-13 7.5-23t20.5-15l212-72 240 84 186-72q20-8 37 4.5t17 33.5v560q0 13-7.5 23T812-192l-212 72Zm-40-98v-468l-160-56v468l160 56Zm80 0 120-40v-474l-120 46v468Zm-440-10 120-46v-468l-120 40v474Zm440-458v468-468Zm-320-56v468-468Z"/></svg>`,
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

  function placeHasCourses(place) {
    if (typeof place?.has_courses === "boolean") return place.has_courses;
    if (Array.isArray(place?.course_categories) && place.course_categories.length > 0) return true;
    if (Array.isArray(place?.course_category_parents) && place.course_category_parents.length > 0) return true;
    return false;
  }

  function matchesFilters(place, options = {}) {
    const ignoreNear = !!options.ignoreNear;
    if (state.courseLocationIds.size > 0) {
      const pid = String(place.id);
      if (!state.courseLocationIds.has(pid)) return false;
    }

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

    if (!ignoreNear && state.nearMeEnabled) {
      if (!userLocation || !Number.isFinite(place._distanceKm)) return false;
      if (Number.isFinite(state.radiusKm) && place._distanceKm > state.radiusKm) return false;
    }

    if (state.courseMode === "has_course" && !placeHasCourses(place)) return false;
    if (state.courseMode === "no_course" && placeHasCourses(place)) return false;

    // backward compatibility for old shared URLs
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

  function updateRadiusLabel() {
    const v = Number.isFinite(state.radiusKm) ? state.radiusKm : 5;
    const out = `${Math.round(v)} กม.`;
    const elLabel = el("radiusKmValue");
    if (elLabel) elLabel.textContent = out;
  }

  function normalizeRadiusKm(v) {
    const n = Number(v);
    if (!Number.isFinite(n)) return 5;
    return Math.min(NEAR_RADIUS_MAX_KM, Math.max(NEAR_RADIUS_MIN_KM, Math.round(n)));
  }

  function setNearMeChipUI() {
    const sw = el("nearMeSwitch");
    if (sw) sw.checked = !!state.nearMeEnabled;
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

    if (!listMobile || !mapSection) return;

    if (isMobile()) {
      mapSection.classList.remove("hidden");
      listMobile.classList.remove("hidden");
      listMobile.classList.toggle("is-expanded", view === "list");
      listMobile.classList.toggle("is-collapsed", view !== "list");
      mobileSheetExpanded = view === "list";

      if (mapTab && listTab) {
        if (view === "list") {
          mapTab.className = "px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-white";
          listTab.className = "px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 text-white";
        } else {
          mapTab.className = "px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 text-white";
          listTab.className = "px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-white";
        }
      }
      setTimeout(() => map && map.resize(), 60);
      return;
    }

    if (view === "map") {
      if (mapTab && listTab) {
        mapTab.className = "px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 text-white";
        listTab.className = "px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-white";
      }

      listMobile.classList.add("hidden");
      mapSection.classList.remove("hidden");

      setTimeout(() => map && map.resize(), 60);
    } else {
      if (mapTab && listTab) {
        mapTab.className = "px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-white";
        listTab.className = "px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 text-white";
      }

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

    if (state.courseMode === "has_course" || state.courseMode === "no_course") {
      chips.push({
        label: state.courseMode === "has_course" ? "มีคอร์ส" : "ไม่มีคอร์ส",
        clear: () => { state.courseMode = ""; syncCourseCategoryUI(); }
      });
    }

    if (state.nearMeEnabled) {
      chips.push({
        label: `ใกล้ฉัน ≤ ${state.radiusKm} กม.`,
        clear: () => {
          state.nearMeEnabled = false;
          el("nearMeRadiusWrap").classList.add("hidden");
          setNearMeChipUI();
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
    if (state.courseMode) count += 1;
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
    let hasCourseCount = 0;
    let noCourseCount = 0;
    for (const p of allPlaces) {
      if (placeHasCourses(p)) hasCourseCount += 1;
      else noCourseCount += 1;
    }

    return [
      { value: "has_course", label: "มีคอร์ส", count: hasCourseCount },
      { value: "no_course", label: "ไม่มีคอร์ส", count: noCourseCount },
    ].filter((x) => x.count > 0);
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
      wrap.innerHTML = `<div class="text-xs text-slate-500">ไม่มีข้อมูลสถานะคอร์ส</div>`;
      return;
    }

    items.slice(0, COURSE_CAT_TOP_N).forEach(it => {
      const showCount = it.value !== "no_course";
      wrap.appendChild(makeCourseCatPill({
        value: it.value,
        label: `${it.label}${showCount && it.count ? ` (${it.count})` : ""}`,
        active: state.courseMode === it.value
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
      grid.innerHTML = `<div class="text-sm text-slate-500">ไม่พบสถานะคอร์ส</div>`;
      return;
    }

    items.forEach(it => {
      const showCount = it.value !== "no_course";
      grid.appendChild(makeCourseCatPill({
        value: it.value,
        label: `${it.label}${showCount && it.count ? ` (${it.count})` : ""}`,
        active: state.courseMode === it.value
      }));
    });
  }

  function syncCourseCategoryUI() {
    el("courseCatWrap")?.querySelectorAll(".ccPill").forEach(btn => {
      const v = btn.dataset.courseCat;
      const on = state.courseMode === v;
      btn.classList.toggle("bg-emerald-600", on);
      btn.classList.toggle("text-white", on);
      btn.classList.toggle("border-emerald-600", on);
      btn.classList.toggle("bg-white", !on);
      btn.classList.toggle("hover:bg-slate-50", !on);
    });

    el("courseCatModalGrid")?.querySelectorAll(".ccPill").forEach(btn => {
      const v = btn.dataset.courseCat;
      const on = state.courseMode === v;
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
    const baseColor = meta.color || "#00744b";
    btn.className = "flex items-center gap-2 rounded-xl px-3 py-2 text-sm transition-colors" + (active ? " is-active-cat" : "");
    btn.style.setProperty("--cat-color", baseColor);
    btn.style.setProperty("--cat-bg", active ? baseColor : colorToRgba(baseColor, 0.12));
    btn.style.setProperty("--cat-text", active ? "#ffffff" : "#0f172a");
    btn.innerHTML = `
      <span style="color: ${active ? "#ffffff" : (meta.color || "#00744b")}">${svgForDom(iconKey, "icon-18")}</span>
      <span class="truncate">${meta.label}</span>
    `;
    btn.type = "button";
    btn.onclick = () => toggleCategory(key);
    return btn;
  }

  function toggleCategory(key) {
    if (state.categories.has(key)) state.categories.delete(key);
    else state.categories.add(key);
    renderCategoryUIs();
    resetListLimit();
    refresh();
    writeUrlFromState("push");
  }

  function renderMobileQuickCategoryPills() {
    const wrap = el("mobileQuickCats");
    if (!wrap) return;
    wrap.innerHTML = "";

    const all = getAllCategoriesFromData()
      .sort((a,b) => catMeta(a).label.localeCompare(catMeta(b).label, "th"));
    if (!all.length) return;

    all.forEach((key) => {
      const meta = catMeta(key);
      const iconKey = getIconKeyFromCategory(key);
      const btn = document.createElement("button");
      const active = state.categories.has(key);
      btn.type = "button";
      btn.className = "mobile-quick-cat" + (active ? " is-active" : "");
      btn.innerHTML = `
        <span class="icon-slot">${svgForDom(iconKey, "icon-18")}</span>
        <span class="txt">${meta.label}</span>
      `;
      btn.style.setProperty("--pill-cat-color", meta.color || "#00744b");
      btn.setAttribute("aria-pressed", active ? "true" : "false");
      btn.onclick = () => toggleCategory(key);
      wrap.appendChild(btn);
    });
  }

  function renderCategoryUIs() {
    const all = getAllCategoriesFromData()
      .sort((a,b) => catMeta(a).label.localeCompare(catMeta(b).label, "th"));
    const grid = el("catGrid");
    grid.innerHTML = "";
    all.forEach(k => grid.appendChild(makeCatButton(k)));
    renderMobileQuickCategoryPills();
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
    const { forceMapOnMobile = false, expandOnOpen = false } = options;

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
    drawer.classList.remove("is-expanded");
    if (isMobile() && expandOnOpen) drawer.classList.add("is-expanded");
    requestAnimationFrame(() => {
      drawer.classList.add("is-open");
    });

    if (isMobile()) {
      el("listSectionMobile")?.classList.add("is-hidden");
    }

    if (isMobile() && forceMapOnMobile) setMobileView("map");

    const primaryCat = getPrimaryCategory(place);
    const meta = catMeta(primaryCat);
    const iconKey = getIconKeyFromCategory(primaryCat);
    const iconCats = getIconCategoriesForPlace(place, 2);
    const drawerCatLabels = iconCats.map((c) => catMeta(c).label).filter(Boolean);
    const drawerCategoryText = drawerCatLabels.length >= 2
      ? `${drawerCatLabels[0]} / ${drawerCatLabels[1]}`
      : (drawerCatLabels[0] || meta.label);

    const setRowVisible = (id, visible) => {
      const row = el(id);
      if (!row) return;
      row.classList.toggle("hidden", !visible);
      row.style.display = visible ? "" : "none";
    };

    el("dTitle").textContent = place.name || "";
    el("dDistrict").textContent = place.district ? `เขต${place.district}` : "";
    el("dCategory").textContent = drawerCategoryText;
    const dIconEl = el("dIcon");
    if (iconCats.length >= 2) {
      dIconEl.innerHTML = buildSplitBadgeHtml(iconCats[0], iconCats[1], { width: 46, height: 30, radius: 8, iconSizeClass: "icon-16" });
      dIconEl.style.background = "transparent";
      dIconEl.style.width = "46px";
      dIconEl.style.height = "30px";
      dIconEl.style.borderRadius = "8px";
    } else {
      dIconEl.innerHTML = `<span style="color: #ffffff">${svgForDom(iconKey, "icon-20")}</span>`;
      dIconEl.style.background = meta.color || DEFAULT_CATEGORY_COLOR;
      dIconEl.style.width = "35px";
      dIconEl.style.height = "35px";
      dIconEl.style.borderRadius = "8px";
    }
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

    const looksLikeHttpUrl = (v) => /^https?:\/\//i.test((v || "").trim());
    const fromAcfMapUrl = (place.map_url || "").trim();
    const fallbackFromLatLng = (typeof place.lat === "number" && typeof place.lng === "number")
      ? `https://maps.google.com/?q=${place.lat},${place.lng}`
      : "";
    const initialGmapsLink = looksLikeHttpUrl(fromAcfMapUrl) ? fromAcfMapUrl : fallbackFromLatLng;
    if (initialGmapsLink) {
      el("dGmaps").href = initialGmapsLink;
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
      const finalGmapsLink = looksLikeHttpUrl(gmapsLink) ? gmapsLink : fallbackFromLatLng;
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

  function closeDrawer(options = {}) {
    const { suppressMapClick = false } = options;
    const drawer = el("drawer");
    if (!drawer) return;

    selectedId = null;
    syncActiveMarkerState();
    drawer.classList.remove("is-expanded");
    drawer.classList.remove("is-open");
    if (drawerHideTimer) clearTimeout(drawerHideTimer);
    drawerHideTimer = setTimeout(() => {
      drawer.classList.add("hidden");
      drawerHideTimer = null;
    }, DRAWER_ANIM_MS);
    if (suppressMapClick) {
      suppressMapClicksUntil = Date.now() + 600;
    }
    if (isMobile()) {
      const listMobile = el("listSectionMobile");
      if (listMobile) listMobile.classList.remove("is-hidden");
      mobileSheetExpanded = false;
      setMobileView("map"); // map mode on mobile == list in peek state
    }
    writeUrlFromState("replace");
  }

  function bindDrawerDrag() {
    const drawer = el("drawer");
    const panel = el("drawerPanel");
    const grabber = el("drawerGrabber");
    const grabberWrap = el("drawerGrabberWrap");
    const hero = el("drawerHero");
    if (!drawer || !panel || !grabber) return;

    let dragging = false;
    let startY = 0;
    let baseTranslate = 0;
    let currentTranslate = 0;
    let startTs = 0;
    let wasExpanded = false;
    let activePointerId = null;
    const peekOffsetPx = () => window.innerHeight * 0.6; // 40svh visible
    const canStartFromTarget = (target) => {
      if (!(target instanceof Element)) return true;
      return !target.closest("button, a, input, textarea, select, label, [role='button'], .tabBtn");
    };

    const startDrag = (clientY) => {
      if (!isMobile() || !drawer.classList.contains("is-open")) return;
      dragging = true;
      startY = clientY;
      startTs = Date.now();
      wasExpanded = drawer.classList.contains("is-expanded");
      baseTranslate = wasExpanded ? 0 : peekOffsetPx();
      currentTranslate = baseTranslate;
      panel.style.transition = "none";
    };

    const moveDrag = (clientY) => {
      if (!dragging) return;
      const raw = clientY - startY;
      const minY = 0;
      const maxY = peekOffsetPx();
      currentTranslate = Math.min(maxY, Math.max(minY, baseTranslate + raw));
      panel.style.transform = `translateY(${currentTranslate}px)`;
    };

    const endDrag = () => {
      if (!dragging) return;
      dragging = false;
      const elapsed = Math.max(1, Date.now() - startTs);
      const velocity = (currentTranslate - baseTranslate) / elapsed;

      panel.style.transition = "";
      panel.style.transform = "";

      const peek = peekOffsetPx();
      const shouldExpand = currentTranslate < (peek * 0.45) || velocity < -0.18;
      const shouldCollapse = currentTranslate > (peek * 0.55) || velocity > 0.08;

      if (shouldExpand) {
        drawer.classList.add("is-expanded");
      } else if (shouldCollapse) {
        drawer.classList.remove("is-expanded");
      } else {
        // snap back to state before drag when movement is small
        drawer.classList.toggle("is-expanded", wasExpanded);
      }
    };

    const bindStartZone = (zone) => {
      if (!zone) return;
      zone.addEventListener("touchstart", (e) => {
        if (!canStartFromTarget(e.target)) return;
        const t = e.touches?.[0];
        if (!t) return;
        startDrag(t.clientY);
      }, { passive: false });
      zone.addEventListener("touchend", endDrag);
      zone.addEventListener("touchcancel", endDrag);

      zone.addEventListener("pointerdown", (e) => {
        if (!canStartFromTarget(e.target)) return;
        if (e.pointerType !== "touch" && e.pointerType !== "pen") return;
        activePointerId = e.pointerId;
        if (zone.setPointerCapture) {
          try { zone.setPointerCapture(e.pointerId); } catch (err) {}
        }
        startDrag(e.clientY);
      });
      zone.addEventListener("pointerup", endDrag);
      zone.addEventListener("pointercancel", endDrag);
    };
    [grabber, grabberWrap, hero].forEach(bindStartZone);

    window.addEventListener("touchmove", (e) => {
      if (!dragging) return;
      const t = e.touches?.[0];
      if (!t) return;
      if (e.cancelable) e.preventDefault();
      moveDrag(t.clientY);
    }, { passive: false });
    window.addEventListener("pointermove", (e) => {
      if (!dragging) return;
      if (activePointerId != null && e.pointerId !== activePointerId) return;
      moveDrag(e.clientY);
    });
    window.addEventListener("mouseup", endDrag);
    window.addEventListener("touchend", endDrag);
    window.addEventListener("touchcancel", endDrag);
    window.addEventListener("pointerup", () => { activePointerId = null; });
    window.addEventListener("pointercancel", () => { activePointerId = null; });
  }

  function bindListSheetDrag() {
    const sheet = el("listSectionMobile");
    const handle = el("btnSheetToggle");
    if (!sheet || !handle) return;

    let dragging = false;
    let gestureMode = ""; // "", "drag", "scroll"
    let startY = 0;
    let startX = 0;
    let baseTranslate = 0;
    let currentTranslate = 0;
    let startTs = 0;
    let dragMoved = false;
    let activePointerId = null;
    let dragInputType = ""; // "touch" | "pen" | ""
    let wasExpanded = false;
    let startScrollEl = null;
    let gesturePrimed = false;
    const DRAG_START_THRESHOLD_PX = 8;

    const collapsedOffsetPx = () => window.innerHeight * 0.68; // 32svh visible
    const expandedOffsetPx = () => {
      const root = getComputedStyle(document.documentElement);
      const headerVar = parseFloat(root.getPropertyValue("--lc-header-h")) || 0;
      const headerEl = document.querySelector("header");
      const headerPx = headerEl ? headerEl.offsetHeight : headerVar;
      return Math.max(0, Math.round(headerPx));
    };

    const findScrollableParent = (target) => {
      let node = target instanceof Element ? target : null;
      while (node && node !== sheet) {
        if (node instanceof HTMLElement) {
          const style = getComputedStyle(node);
          const overflowY = style.overflowY;
          const canScroll = (overflowY === "auto" || overflowY === "scroll") && node.scrollHeight > node.clientHeight;
          if (canScroll) return node;
        }
        node = node.parentElement;
      }
      if (sheet.scrollHeight > sheet.clientHeight) return sheet;
      return null;
    };

    const startDrag = (clientY, inputType = "") => {
      if (!isMobile()) return;
      if (sheet.classList.contains("is-hidden")) return;
      dragging = true;
      dragInputType = inputType || "";
      startY = clientY;
      startTs = Date.now();
      dragMoved = false;
      wasExpanded = sheet.classList.contains("is-expanded");
      baseTranslate = wasExpanded ? expandedOffsetPx() : collapsedOffsetPx();
      currentTranslate = baseTranslate;
      sheet.style.transition = "none";
    };

    const moveDrag = (clientY) => {
      if (!dragging) return;
      const raw = clientY - startY;
      const minY = expandedOffsetPx();
      const maxY = collapsedOffsetPx();
      currentTranslate = Math.min(maxY, Math.max(minY, baseTranslate + raw));
      if (Math.abs(currentTranslate - baseTranslate) > 2) dragMoved = true;
      sheet.style.transform = `translateY(${currentTranslate}px)`;
    };

    const resetGesture = () => {
      dragging = false;
      gestureMode = "";
      dragInputType = "";
      startScrollEl = null;
      gesturePrimed = false;
    };

    const endDrag = () => {
      if (!dragging) {
        resetGesture();
        return;
      }
      dragging = false;
      const elapsed = Math.max(1, Date.now() - startTs);
      const velocity = (currentTranslate - baseTranslate) / elapsed;

      sheet.style.transition = "";
      sheet.style.transform = "";

      const minY = expandedOffsetPx();
      const maxY = collapsedOffsetPx();
      const mid = (minY + maxY) / 2;
      const shouldExpand = currentTranslate < mid || velocity < -0.18;
      const shouldCollapse = currentTranslate >= mid || velocity > 0.08;
      const nextExpanded = shouldExpand ? true : (shouldCollapse ? false : wasExpanded);

      mobileSheetExpanded = nextExpanded;
      sheet.classList.toggle("is-expanded", nextExpanded);
      sheet.classList.toggle("is-collapsed", !nextExpanded);
      if (dragMoved && dragInputType === "touch") {
        suppressSheetHandleClickUntil = Date.now() + 350;
        suppressSheetCardClickUntil = Date.now() + 400;
      }
      setTimeout(() => map && map.resize(), 80);
      resetGesture();
    };

    const beginGesture = (clientX, clientY, target) => {
      if (!isMobile() || sheet.classList.contains("is-hidden")) {
        gesturePrimed = false;
        return;
      }
      if (target instanceof Element && target.closest("input, textarea, select, [contenteditable='true']")) {
        gesturePrimed = false;
        return;
      }
      startX = clientX;
      startY = clientY;
      gestureMode = "";
      dragMoved = false;
      startScrollEl = findScrollableParent(target);
      gesturePrimed = true;
    };

    const resolveGestureMode = (clientX, clientY) => {
      if (!gesturePrimed) return "";
      if (gestureMode) return gestureMode;
      const dx = clientX - startX;
      const dy = clientY - startY;
      if (Math.abs(dx) < DRAG_START_THRESHOLD_PX && Math.abs(dy) < DRAG_START_THRESHOLD_PX) return "";
      if (Math.abs(dy) <= Math.abs(dx)) {
        gestureMode = "scroll";
        return gestureMode;
      }
      const isExpandedNow = sheet.classList.contains("is-expanded");
      const draggingDown = dy > 0;
      const atTop = !startScrollEl || startScrollEl.scrollTop <= 0;
      // In peek state, any vertical gesture should control the sheet.
      // In expanded state, only drag-down at top should control the sheet (otherwise keep list scrolling).
      const shouldDrag = !isExpandedNow ? true : (draggingDown && atTop);
      gestureMode = shouldDrag ? "drag" : "scroll";
      if (gestureMode === "drag") startDrag(startY, "touch");
      return gestureMode;
    };

    sheet.addEventListener("touchstart", (e) => {
      const t = e.touches?.[0];
      if (!t) return;
      beginGesture(t.clientX, t.clientY, e.target);
    }, { passive: true });
    sheet.addEventListener("touchend", endDrag);
    sheet.addEventListener("touchcancel", endDrag);

    sheet.addEventListener("pointerdown", (e) => {
      if (e.pointerType === "mouse") return;
      activePointerId = e.pointerId;
      if (sheet.setPointerCapture) {
        try { sheet.setPointerCapture(e.pointerId); } catch (err) {}
      }
      beginGesture(e.clientX, e.clientY, e.target);
    });
    sheet.addEventListener("pointerup", endDrag);
    sheet.addEventListener("pointercancel", endDrag);

    window.addEventListener("touchmove", (e) => {
      const t = e.touches?.[0];
      if (!t) return;
      if (gesturePrimed && !gestureMode) {
        const dy = t.clientY - startY;
        const atTop = !startScrollEl || startScrollEl.scrollTop <= 0;
        if (dy > 0 && atTop && e.cancelable) e.preventDefault();
      }
      const mode = resolveGestureMode(t.clientX, t.clientY);
      if (mode !== "drag") return;
      if (e.cancelable) e.preventDefault();
      moveDrag(t.clientY);
    }, { passive: false });
    window.addEventListener("pointermove", (e) => {
      if (activePointerId != null && e.pointerId !== activePointerId) return;
      const mode = resolveGestureMode(e.clientX, e.clientY);
      if (mode !== "drag") return;
      moveDrag(e.clientY);
    });
    window.addEventListener("mouseup", endDrag);
    window.addEventListener("touchend", endDrag);
    window.addEventListener("touchcancel", endDrag);
    window.addEventListener("pointerup", () => { activePointerId = null; });
    window.addEventListener("pointercancel", () => { activePointerId = null; });

    sheet.addEventListener("click", (e) => {
      const isTouchClick = !!(e.sourceCapabilities && e.sourceCapabilities.firesTouchEvents);
      if (isTouchClick && Date.now() < suppressSheetCardClickUntil) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);
  }

  function shouldShowWelcomeModal() {
    if (isSingleMode) return false;
    try {
      return localStorage.getItem(WELCOME_SEEN_KEY) !== "1";
    } catch (e) {
      return true;
    }
  }

  function openWelcomeModal() {
    const modal = el("welcomeModal");
    if (!modal) return;
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
  }

  function closeWelcomeModal(markSeen = true) {
    const modal = el("welcomeModal");
    if (!modal) return;
    if (markSeen) {
      try { localStorage.setItem(WELCOME_SEEN_KEY, "1"); } catch (e) {}
    }
    modal.classList.add("hidden");
    document.body.style.overflow = "";
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

    // Prepare pair icon ids from current places (first 2 categories per place)
    for (const p of allPlaces) {
      const pair = getIconCategoriesForPlace(p, 2);
      if (pair.length >= 2) ensurePairIconId(pair[0], pair[1]);
    }

    const tasks = [...keys].map((catKey) => {
      return new Promise((resolve) => {
        const meta = catMeta(catKey);
        const iconSvg = getSvgByKey(meta.iconKey)
          .replace(/fill="currentColor"/g, 'fill="#ffffff"')
          .replace(/<svg\b([^>]*)>/i, '<svg$1 x="14" y="14" width="36" height="36" preserveAspectRatio="xMidYMid meet">');

        const fillColor = meta.color || DEFAULT_CATEGORY_COLOR;

        const svgString = `
          <svg width="64" height="64" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
            <rect x="4" y="4" width="56" height="56" rx="14" fill="${fillColor}" />
            ${iconSvg}
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

    // Pair markers (horizontal split)
    for (const [sig, pairId] of PAIR_ICON_REGISTRY.entries()) {
      tasks.push(new Promise((resolve) => {
        const [catA, catB] = sig.split("||");
        const mA = catMeta(catA);
        const mB = catMeta(catB);
        const iconA = getSvgByKey(getIconKeyFromCategory(catA))
          .replace(/fill="currentColor"/g, 'fill="#ffffff"')
          .replace(/<svg\b([^>]*)>/i, '<svg$1 x="18" y="24" width="22" height="22" preserveAspectRatio="xMidYMid meet">');
        const iconB = getSvgByKey(getIconKeyFromCategory(catB))
          .replace(/fill="currentColor"/g, 'fill="#ffffff"')
          .replace(/<svg\b([^>]*)>/i, '<svg$1 x="60" y="24" width="22" height="22" preserveAspectRatio="xMidYMid meet">');

        const svgString = `
          <svg width="100" height="72" viewBox="0 0 100 72" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <clipPath id="cp-${pairId}">
                <rect x="4" y="8" width="92" height="56" rx="18" />
              </clipPath>
            </defs>
            <g clip-path="url(#cp-${pairId})">
              <rect x="4" y="8" width="92" height="56" fill="${mA.color || DEFAULT_CATEGORY_COLOR}" />
              <rect x="50" y="8" width="46" height="56" fill="${mB.color || DEFAULT_CATEGORY_COLOR}" />
            </g>
            <rect x="4.5" y="8.5" width="91" height="55" rx="17.5" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="1" />
            <line x1="50" y1="12" x2="50" y2="60" stroke="#ffffff" stroke-opacity="0.45" stroke-width="1" />
            ${iconA}
            ${iconB}
          </svg>
        `.trim();

        const img = new Image();
        const blob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        img.onload = () => {
          try {
            const id = `blm-${pairId}`;
            if (map.hasImage(id)) map.removeImage(id);
            map.addImage(id, img, { pixelRatio: 2 });
          } catch (e) { console.error("Map pair image error:", e); }
          URL.revokeObjectURL(url);
          resolve();
        };
        img.onerror = () => { URL.revokeObjectURL(url); resolve(); };
        img.src = url;
      }));
    }

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
          const iconKey = getMapIconIdForPlace(p);
          const isPair = String(iconKey).startsWith("blm-pair-");
          return {
            type: "Feature",
            id: p.id,
            geometry: { type: "Point", coordinates: [p.lng, p.lat] },
            properties: { id: p.id, category: categoryKey, district: p.district || "", name: p.name || "", iconKey, isPair }
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
        "icon-size": 1.38,
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
        "icon-size": 1.75,
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
        "icon-size": 1.8,
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
        "text-offset": [
          "case",
          ["boolean", ["get", "isPair"], false],
          ["literal", [2.55, 0]],
          ["literal", [1.8, 0]]
        ],
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
      if (Date.now() < suppressMapClicksUntil) return;
      const features = map.queryRenderedFeatures(e.point, { layers: [LAYER_CLUSTER_CIRCLE] });
      const clusterId = features[0].properties.cluster_id;
      map.getSource(PLACES_SOURCE_ID).getClusterExpansionZoom(clusterId, (err, zoom) => {
        if (err) return;
        map.easeTo({ center: features[0].geometry.coordinates, zoom });
      });
    });

    const openPlaceFromPoint = (e, layers) => {
      if (Date.now() < suppressMapClicksUntil) return;
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
    const iconCats = getIconCategoriesForPlace(place, 2);
    const catLabels = iconCats.map((c) => catMeta(c).label).filter(Boolean);
    const categoryLabel = catLabels.length >= 2 ? `${catLabels[0]} / ${catLabels[1]}` : (catLabels[0] || meta.label);
    const distText = userLocation ? `ห่างจากคุณ ${formatKm(place._distanceKm)}` : "";
    const listImage = place?.list_image || null;
    const listImageSrc = listImage?.thumb || listImage?.medium || listImage?.large || BLM_LIST_PLACEHOLDER;
    const isPlaceholderImage = listImageSrc === BLM_LIST_PLACEHOLDER;
    const imageBgColor = isPlaceholderImage ? "#f1f1f1" : "#e2e8f0";
    const nameText = escHtml(place?.name || "");
    const districtText = escHtml(`${categoryLabel}${place.district ? (" : เขต" + place.district) : ""}`);
    const tagsHtml = (place.tags || [])
      .slice(0, 4)
      .map((t) => `<span class="text-[11px] px-2 py-[1px] rounded-full border bg-white">${escHtml(t)}</span>`)
      .join("");
    const distanceText = escHtml(distText);
    const imageAlt = nameText || "สถานที่";

    const badgeHtml = iconCats.length >= 2
      ? buildSplitBadgeHtml(iconCats[0], iconCats[1], { width: 44, height: 30, radius: 8, iconSizeClass: "icon-16" })
      : `<span class="inline-flex items-center justify-center rounded-lg text-white icon-18" style="width:35px;height:35px;background:${meta.color || DEFAULT_CATEGORY_COLOR}">${svgForDom(iconKey, "icon-18")}</span>`;

    const card = document.createElement("button");
    card.type = "button";
    card.className = "w-full text-left p-4 rounded-xl bg-white border hover:shadow-sm transition";
    card.innerHTML = `
      <div class="flex items-start gap-3">
        <div class="shrink-0 mt-0.5">${badgeHtml}</div>
        <div class="min-w-0 flex-1">
          <div class="font-semibold leading-snug text-[16px]">
            <span>${nameText}</span>
          </div>
          <div class="text-[14px] text-slate-700">
            <span>${districtText}</span>
          </div>
          <div class="mt-2 flex flex-wrap gap-1">
            ${tagsHtml}
          </div>
          <div class="text-[14px] font-semibold text-emerald-700 mt-2">${distanceText}</div>
        </div>
        <div class="shrink-0 rounded-lg overflow-hidden" style="width:80px;height:80px;border:none;background:${imageBgColor};">
          <img
            data-role="list-thumb"
            src="${escHtml(listImageSrc)}"
            alt="${imageAlt}"
            class="w-full h-full object-cover"
            style="width:80px;height:80px;border:none;"
            loading="lazy"
            decoding="async"
            fetchpriority="low"
            width="80"
            height="80"
          >
        </div>
      </div>
    `;

    // Fallback lazy hydrate: when card enters viewport, pull first image from /location/:id
    const imgEl = card.querySelector('img[data-role="list-thumb"]');
    const hasApiThumb = !!(listImage && (listImage.thumb || listImage.medium || listImage.large));
    if (imgEl && !hasApiThumb) {
      const hydrateImage = async () => {
        if (imgEl.dataset.hydrated === "1") return;
        imgEl.dataset.hydrated = "1";
        try {
          const full = await loadFullForId(place.id, { silent: true });
          const first = Array.isArray(full?.images) && full.images.length ? full.images[0] : null;
          const src = first?.medium || first?.url || first?.large || "";
          if (src) imgEl.src = src;
        } catch (_) {
          // keep placeholder on failure
        }
      };

      if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver((entries, ob) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            ob.unobserve(entry.target);
            hydrateImage();
          });
        }, { rootMargin: "160px 0px" });
        observer.observe(card);
      } else {
        hydrateImage();
      }
    }

    card.addEventListener("mouseenter", () => {
      if (!map) return;
      if (map.getLayer("places-unclustered-hover")) {
        map.setFilter("places-unclustered-hover", ["==", ["to-string", ["get", "id"]], String(place.id)]);
      }
      startHoverShake();
    });

    card.addEventListener("mouseleave", () => {
      if (!map) return;
      if (map.getLayer("places-unclustered-hover")) {
        map.setFilter("places-unclustered-hover", ["==", ["to-string", ["get", "id"]], ""]);
      }
      stopHoverShake();
    });

    card.onclick = () => {
      if (map && typeof place.lng === "number" && typeof place.lat === "number") {
        map.flyTo({ center: [place.lng, place.lat], zoom: 16 });
      }
      openDrawer(place, { expandOnOpen: false });
      closeSidebarIfMobile();
    };
    return card;
  }

  function renderList(places, options = {}) {
    const append = !!options.append;
    const list = el("list");
    const listMobile = el("listMobile");
    if (!append) {
      if (list) list.innerHTML = "";
      if (listMobile) listMobile.innerHTML = "";
    }

    if (!append && places.length === 0) {
      const empty = document.createElement("div");
      empty.className = "py-8 text-center text-slate-600 flex flex-col items-center justify-center gap-2";
      empty.innerHTML = `
        <span class="icon-24 text-slate-500" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
            <path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h240l80 80h320q33 0 56.5 23.5T880-640H447l-80-80H160v480l96-320h684L837-217q-8 26-29.5 41.5T760-160H160Zm84-80h516l72-240H316l-72 240Zm0 0 72-240-72 240Zm-84-400v-80 80Z"/>
          </svg>
        </span>
        <span>ไม่พบสถานที่ในกรอบแผนที่/ตัวกรองนี้</span>
      `.trim();
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
    [el("btnLoadMoreDesktop"), el("btnLoadMoreMobile")].forEach((b) => {
      if (!b) return;
      // Infinite load mode: keep buttons hidden (fallback click handler still attached)
      b.classList.add("hidden");
      b.disabled = isLoadingMore;
      b.classList.toggle("opacity-60", isLoadingMore);
      b.classList.toggle("cursor-not-allowed", isLoadingMore);
      b.textContent = isLoadingMore ? "กำลังโหลดเพิ่ม..." : "โหลดเพิ่ม";
    });
    [el("loadMoreHintDesktop"), el("loadMoreHintMobile")].forEach(h => {
      if (h) {
        h.classList.toggle("hidden", !hasMore && !isLoadingMore);
        h.textContent = isLoadingMore
          ? "กำลังโหลดเพิ่ม..."
          : (hasMore ? `แสดง ${shown} จาก ${total} รายการ • เลื่อนลงเพื่อโหลดเพิ่ม` : "");
      }
    });
  }

  async function applyLoadMore() {
    if (isLoadingMore) return;
    const prevLimit = Math.min(listLimit, lastVisible.length);
    if (prevLimit >= lastVisible.length) return;

    isLoadingMore = true;
    renderLoadMoreUI(lastVisible.length, prevLimit);

    await new Promise((resolve) => setTimeout(resolve, 120));

    listLimit = Math.min(listLimit + LIST_PAGE_SIZE, lastVisible.length);
    const appendSlice = lastVisible.slice(prevLimit, listLimit);
    renderList(appendSlice, { append: true });

    isLoadingMore = false;
    renderLoadMoreUI(lastVisible.length, listLimit);
  }

  function setupInfiniteLoadObservers() {
    infiniteDesktopObserver?.disconnect?.();
    infiniteMobileObserver?.disconnect?.();

    const desktopRoot = el("listSectionDesktop");
    const desktopTarget = el("infiniteSentinelDesktop");
    if (desktopRoot && desktopTarget && "IntersectionObserver" in window) {
      infiniteDesktopObserver = new IntersectionObserver((entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          if (!isElementActuallyVisible(desktopRoot)) continue;
          applyLoadMore();
        }
      }, { root: desktopRoot, rootMargin: `0px 0px ${INFINITE_LOAD_OFFSET_PX}px 0px`, threshold: 0.01 });
      infiniteDesktopObserver.observe(desktopTarget);
    }

    const mobileRoot = el("listSectionMobile");
    const mobileTarget = el("infiniteSentinelMobile");
    if (mobileRoot && mobileTarget && "IntersectionObserver" in window) {
      infiniteMobileObserver = new IntersectionObserver((entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          if (!isElementActuallyVisible(mobileRoot)) continue;
          applyLoadMore();
        }
      }, { root: mobileRoot, rootMargin: `0px 0px ${INFINITE_LOAD_OFFSET_PX}px 0px`, threshold: 0.01 });
      infiniteMobileObserver.observe(mobileTarget);
    }
  }

  function getActiveListContainer() {
    if (isMobile()) return el("listSectionMobile");
    return el("listSectionDesktop");
  }

  function isElementActuallyVisible(node) {
    if (!node) return false;
    const style = window.getComputedStyle(node);
    return style.display !== "none" && style.visibility !== "hidden";
  }

  function maybeTriggerInfiniteLoad(container = null) {
    if (isLoadingMore) return;
    if (listLimit >= lastVisible.length) return;
    const root = container || getActiveListContainer();
    if (!root || !isElementActuallyVisible(root)) return;
    const canScrollInRoot = root.scrollHeight > (root.clientHeight + 4);
    if (!canScrollInRoot) return;
    if (root.scrollTop <= 0) return;
    const remain = root.scrollHeight - (root.scrollTop + root.clientHeight);
    if (remain <= INFINITE_LOAD_OFFSET_PX) applyLoadMore();
  }

  // ================= REFRESH =================
  function refresh() {
    if (!map) return;
    isLoadingMore = false;
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
    syncNearRadiusOverlay();

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

  // ================= LOAD DATA =================
  async function loadPlaces() {
    try {
      setApiLoading(true, "กำลังโหลดสถานที่...");
      const res = await fetch(withNoCache(`${BLM_API_BASE}/locations-light`), {
        cache: "no-store",
        headers: { Accept: "application/json" }
      });
      if (!res.ok) {
        throw new Error(`locations-light failed (${res.status})`);
      }
      const raw = await res.text();
      let json = null;
      try {
        json = JSON.parse(raw);
      } catch (err) {
        const preview = raw.slice(0, 180).replace(/\s+/g, " ");
        throw new Error(`locations-light returned non-JSON: ${preview}`);
      }
      if (!Array.isArray(json.places)) {
        throw new Error("locations-light JSON missing places[]");
      }
      allPlaces = json.places;
      savePlacesCache(allPlaces);
    } catch (e) {
      console.error(e);
      const cachedPlaces = loadPlacesCache();
      if (Array.isArray(cachedPlaces) && cachedPlaces.length) {
        allPlaces = cachedPlaces;
        console.warn("Using cached places due to API failure");
      } else {
        allPlaces = [];
        alert("โหลดข้อมูลไม่สำเร็จ (API ส่งข้อมูลไม่ถูกต้อง)");
      }
    } finally {
      setApiLoading(false);
    }
  }

  async function loadFilters() {
    try {
      const res = await fetch(withNoCache(`${BLM_API_BASE}/filters`), {
        cache: "no-store",
        headers: { Accept: "application/json" }
      });
      if (!res.ok) return;
      const raw = await res.text();
      try {
        filtersData = JSON.parse(raw);
      } catch (err) {
        console.warn("loadFilters got non-JSON response", raw.slice(0, 180));
      }
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
    bindDrawerDrag();
    bindListSheetDrag();
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
    updateRadiusLabel();
    setNearMeChipUI();

    el("nearMeSwitch")?.addEventListener("change", (e) => {
      if (!userLocation) {
        requestLocation();
        e.target.checked = false;
        return;
      }
      state.nearMeEnabled = !!e.target.checked;
      el("nearMeRadiusWrap").classList.toggle("hidden", !state.nearMeEnabled);
      setNearMeChipUI();
      resetListLimit();
      refresh();
      syncMapZoomToRadius(state.radiusKm, { duration: 220 });
      writeUrlFromState("push");
      saveLocationCache();
    });

    el("radiusKm")?.addEventListener("input", () => {
      state.radiusKm = normalizeRadiusKm(el("radiusKm").value || 5);
      el("radiusKm").value = state.radiusKm;
      updateRadiusLabel();
      if (state.nearMeEnabled) refresh();
      syncMapZoomToRadius(state.radiusKm, { duration: 120 });
      writeUrlFromState("replace");
      saveLocationCache();
    });

    el("btnLoadMoreDesktop")?.addEventListener("click", applyLoadMore);
    el("btnLoadMoreMobile")?.addEventListener("click", applyLoadMore);
    setupInfiniteLoadObservers();
    el("listSectionDesktop")?.addEventListener("scroll", () => maybeTriggerInfiniteLoad(el("listSectionDesktop")), { passive: true });
    el("listSectionMobile")?.addEventListener("scroll", () => maybeTriggerInfiniteLoad(el("listSectionMobile")), { passive: true });
    el("btnSheetToggle")?.addEventListener("click", () => {
      if (Date.now() < suppressSheetHandleClickUntil) return;
      mobileSheetExpanded = !mobileSheetExpanded;
      setMobileView(mobileSheetExpanded ? "list" : "map");
    });

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
      state.courseMode = state.courseMode === value ? "" : value;
      state.courseCategories.clear();
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
      state.courseMode = state.courseMode === value ? "" : value;
      state.courseCategories.clear();
      syncCourseCategoryUI();
      resetListLimit();
      refresh();
      writeUrlFromState("push");
    });

    function clearCourseCategories() {
      state.courseCategories.clear();
      state.courseMode = "";
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
      state.courseMode = "";
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
      setNearMeChipUI();

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
    el("btnWelcomeStart")?.addEventListener("click", () => closeWelcomeModal(true));
    el("closeWelcomeModal")?.addEventListener("click", () => closeWelcomeModal(true));
    bindBackdropClose("welcomeModal", () => closeWelcomeModal(true));

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
        closeWelcomeModal(true);
        closeSidebarMobile();
      }
    });

    const onResize = () => {
      if (isMobile()) setMobileView(mobileSheetExpanded ? "list" : (mobileView || "map"));
      else {
        el("listSectionMobile").classList.add("hidden");
        el("listSectionMobile").classList.remove("is-expanded", "is-collapsed", "is-hidden");
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
      minZoom: TH_MIN_ZOOM,
      maxBounds: TH_BOUNDS,
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
      ensureNearRadiusLayers();
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

    if (shouldShowWelcomeModal()) openWelcomeModal();

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
