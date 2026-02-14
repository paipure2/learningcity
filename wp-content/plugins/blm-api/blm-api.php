<?php
/**
 * Plugin Name: BLM API
 * Description: REST endpoints for Bangkok Learning Map (locations-light + filters + location full) with scheduled caching + auto rebuild on updates.
 * Version: 0.8.0
 */

if (!defined('ABSPATH')) exit;

/** ---------- helpers ---------- */

function blm_float_or_null($v) {
  return is_numeric($v) ? floatval($v) : null;
}

function blm_location_full_cache_key($location_id) {
  return 'blm_location_full_v3_' . intval($location_id);
}

function blm_get_location_description_from_content($post_id) {
  $post = get_post($post_id);
  if (!$post || empty($post->post_content)) return '';
  $content = apply_filters('the_content', $post->post_content);
  $content = wp_strip_all_tags($content, true);
  $content = preg_replace('/\s+/u', ' ', (string) $content);
  return trim((string) $content);
}

// Replace local home_url origin with the current request origin (useful for dev on mobile)

function blm_id_from_acf_post_object($value) {
  if (is_object($value) && isset($value->ID)) return (int) $value->ID;
  if (is_numeric($value)) return (int) $value;
  if (is_array($value) && !empty($value[0])) {
    $first = $value[0];
    if (is_object($first) && isset($first->ID)) return (int) $first->ID;
    if (is_numeric($first)) return (int) $first;
  }
  return 0;
}

function blm_course_id_from_session($session_id) {
  if (!function_exists('get_field')) return 0;
  $course = get_field('course', $session_id, false);
  return blm_id_from_acf_post_object($course);
}

function blm_location_id_from_session($session_id) {
  if (!function_exists('get_field')) return 0;
  $location = get_field('location', $session_id, false);
  return blm_id_from_acf_post_object($location);
}

function blm_format_course_duration_text($minutes) {
  $minutes = (int) $minutes;
  if ($minutes <= 0) return 'ตามรอบเรียน';
  $hours = floor($minutes / 60);
  $mins  = $minutes % 60;
  $out = '';
  if ($hours > 0) $out .= $hours . ' ชม. ';
  if ($mins > 0)  $out .= $mins . ' นาที';
  return trim($out);
}

function blm_format_course_price_text($price) {
  if ($price === null || $price === '') return 'ดูรอบเรียน';
  if (is_numeric($price) && (float)$price == 0) return 'ฟรี';
  return number_format((float)$price) . ' บาท';
}

function blm_date_to_ts($v) {
  if ($v === null) return 0;
  $v = trim((string) $v);
  if ($v === '') return 0;

  // ACF Date Picker raw often: Ymd เช่น 20260105
  if (preg_match('/^\d{8}$/', $v)) {
    $y = substr($v, 0, 4);
    $m = substr($v, 4, 2);
    $d = substr($v, 6, 2);
    $ts = strtotime("$y-$m-$d");
    return $ts ?: 0;
  }

  $ts = strtotime($v);
  return $ts ?: 0;
}

function blm_is_session_open_for_reg($sid, $today_ts) {
  if (!function_exists('get_field')) return false;

  $reg_start = get_field('reg_start', $sid, false);
  $reg_end   = get_field('reg_end',   $sid, false);

  $start_ts = blm_date_to_ts($reg_start);
  $end_ts   = blm_date_to_ts($reg_end);

  // ถ้าไม่กำหนดวันรับสมัครเลย ให้ถือว่าเปิดรับสมัคร
  if ($start_ts === 0 && $end_ts === 0) return true;
  if ($start_ts > 0 && $end_ts === 0) return $today_ts >= $start_ts;
  if ($start_ts === 0 && $end_ts > 0) return $today_ts <= $end_ts;

  return ($today_ts >= $start_ts && $today_ts <= $end_ts);
}

function blm_get_courses_for_location($location_id) {
  $location_id = (int) $location_id;
  if (!$location_id) return [];

  $session_ids = get_posts([
    'post_type'      => 'session',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => [
      'relation' => 'OR',
      [
        'key'     => 'location',
        'value'   => (string) $location_id,
        'compare' => '=',
      ],
      [
        'key'     => 'location',
        'value'   => '"' . (string) $location_id . '"',
        'compare' => 'LIKE',
      ],
    ],
  ]);

  if (empty($session_ids)) return [];

  $course_ids = [];
  $today_ts = strtotime(current_time('Y-m-d'));
  foreach ($session_ids as $sid) {
    if (!blm_is_session_open_for_reg($sid, $today_ts)) continue;
    $cid = blm_course_id_from_session($sid);
    if ($cid) $course_ids[$cid] = true;
  }

  if (empty($course_ids)) return [];

  $courses = [];
  foreach (array_keys($course_ids) as $cid) {
    if (get_post_type($cid) !== 'course' || get_post_status($cid) !== 'publish') continue;

    $price = function_exists('get_field') ? get_field('price', $cid) : null;
    $minutes = function_exists('get_field') ? get_field('total_minutes', $cid) : null;

    $courses[] = [
      'id' => (int) $cid,
      'title' => get_the_title($cid),
      'price' => is_numeric($price) ? (float)$price : null,
      'price_text' => blm_format_course_price_text($price),
      'total_minutes' => is_numeric($minutes) ? (int)$minutes : null,
      'duration_text' => blm_format_course_duration_text($minutes),
      'url' => get_permalink($cid),
    ];
  }

  usort($courses, function ($a, $b) {
    return strcasecmp((string)$a['title'], (string)$b['title']);
  });

  return $courses;
}

function blm_get_top_term_slug($term, $tax) {
  if (!$term || is_wp_error($term)) return '';
  static $cache = [];
  $key = $tax . ':' . (int)$term->term_id;
  if (isset($cache[$key])) return $cache[$key];

  $current = $term;
  while ($current && !is_wp_error($current) && !empty($current->parent)) {
    $current = get_term((int)$current->parent, $tax);
  }

  $slug = $current && !is_wp_error($current) ? blm_slug_decode($current->slug) : '';
  $cache[$key] = $slug;
  return $slug;
}

function blm_meta_value_to_ids($raw) {
  if (is_array($raw)) {
    $out = [];
    foreach ($raw as $v) {
      if (is_numeric($v)) $out[] = (int) $v;
    }
    return array_values(array_unique(array_filter($out)));
  }

  if (!is_string($raw) && !is_numeric($raw)) return [];

  $value = maybe_unserialize($raw);
  if (is_array($value)) return blm_meta_value_to_ids($value);
  if (is_numeric($value)) return [intval($value)];

  return [];
}

function blm_build_location_course_categories_map() {
  global $wpdb;

  $session_ids = get_posts([
    'post_type'      => 'session',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
  ]);

  if (empty($session_ids)) return [];

  $session_to_locations = [];
  $session_to_courses = [];

  foreach (array_chunk(array_map('intval', $session_ids), 800) as $chunk) {
    if (empty($chunk)) continue;
    $ph = implode(',', array_fill(0, count($chunk), '%d'));
    $sql = $wpdb->prepare(
      "SELECT post_id, meta_key, meta_value
       FROM {$wpdb->postmeta}
       WHERE post_id IN ($ph) AND meta_key IN ('location','course')",
      $chunk
    );
    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (empty($rows)) continue;

    foreach ($rows as $row) {
      $sid = (int)($row['post_id'] ?? 0);
      if ($sid <= 0) continue;
      $ids = blm_meta_value_to_ids($row['meta_value'] ?? null);
      if (empty($ids)) continue;

      if (($row['meta_key'] ?? '') === 'location') {
        if (!isset($session_to_locations[$sid])) $session_to_locations[$sid] = [];
        foreach ($ids as $id) $session_to_locations[$sid][$id] = true;
      } elseif (($row['meta_key'] ?? '') === 'course') {
        if (!isset($session_to_courses[$sid])) $session_to_courses[$sid] = [];
        foreach ($ids as $id) $session_to_courses[$sid][$id] = true;
      }
    }
  }

  $location_has_course = [];
  foreach ($session_ids as $sid) {
    $locs = array_keys($session_to_locations[(int)$sid] ?? []);
    $courses = array_keys($session_to_courses[(int)$sid] ?? []);
    if (empty($locs) || empty($courses)) continue;

    foreach ($locs as $loc_id) {
      $loc_id = (int) $loc_id;
      if ($loc_id <= 0) continue;
      $location_has_course[$loc_id] = true;
    }
  }

  $map = [];
  foreach ($location_has_course as $loc_id => $has_course) {
    $map[$loc_id] = ['has_course' => (bool)$has_course];
  }

  return $map;
}

/**
 * Returns [lat,lng] sanitized.
 * - casts to float
 * - auto-swap if lat looks like 100.xx and lng looks like 13.xx
 * - validates range
 */
function blm_sanitize_lat_lng($lat, $lng) {
  $lat = blm_float_or_null($lat);
  $lng = blm_float_or_null($lng);

  if ($lat === null || $lng === null) return [null, null];

  // auto-swap if mistakenly swapped
  if (abs($lat) > 90 && abs($lng) <= 90) {
    $tmp = $lat; $lat = $lng; $lng = $tmp;
  }

  // validate range
  if (abs($lat) > 90 || abs($lng) > 180) return [null, null];

  return [$lat, $lng];
}

/**
 * WP term slugs can be percent-encoded if non-latin.
 * We want human-readable keys in JSON, so decode them.
 */
function blm_slug_decode($slug) {
  return rawurldecode((string)$slug);
}

/** first term slug (for single-select taxonomies) */
function blm_first_term_slug($post_id, $tax) {
  $terms = get_the_terms($post_id, $tax);
  if (is_wp_error($terms) || empty($terms)) return '';
  return blm_slug_decode($terms[0]->slug ?? '');
}

/** all term slugs (for multi-select taxonomies) */
function blm_all_term_slugs($post_id, $tax) {
  $terms = get_the_terms($post_id, $tax);
  if (is_wp_error($terms) || empty($terms)) return [];
  return array_values(array_map(fn($t) => blm_slug_decode($t->slug), $terms));
}

function blm_get_terms_simple($tax, $args = []) {
  $terms = get_terms(array_merge([
    'taxonomy'   => $tax,
    'hide_empty' => false,
  ], is_array($args) ? $args : []));
  if (is_wp_error($terms) || empty($terms)) return [];
  return array_map(fn($t) => [
    'slug'  => blm_slug_decode($t->slug),
    'name'  => (string)$t->name,
    'count' => intval($t->count),
  ], $terms);
}

/**
 * Guard REST callbacks so PHP notices/warnings do not leak as HTML into JSON responses.
 */
function blm_rest_guard(callable $fn) {
  ob_start();
  try {
    $result = $fn();
    $noise = trim((string) ob_get_clean());
    if ($noise !== '') {
      error_log('[BLM API] Unexpected output in REST callback: ' . wp_strip_all_tags($noise));
    }
    return $result;
  } catch (Throwable $e) {
    ob_end_clean();
    error_log('[BLM API] REST callback failed: ' . $e->getMessage());
    return new WP_REST_Response([
      'error' => 'server_error',
      'message' => 'BLM API error',
    ], 500);
  }
}

/** ---------- cache builders ---------- */

function blm_build_locations_light_payload() {
  $location_course_categories = blm_build_location_course_categories_map();
  $q = new WP_Query([
    'post_type'              => 'location',
    'post_status'            => 'publish',
    'posts_per_page'         => -1,
    'fields'                 => 'ids',
    'no_found_rows'          => true,
    'update_post_term_cache' => false,
    'update_post_meta_cache' => false,
    'orderby'                => 'date',
    'order'                  => 'DESC',
  ]);

  $places = [];

  foreach ($q->posts as $post_id) {
    $meta = get_post_meta($post_id);

    $raw_lat = $meta['latitude'][0] ?? null;
    $raw_lng = $meta['longitude'][0] ?? null;
    [$lat, $lng] = blm_sanitize_lat_lng($raw_lat, $raw_lng);

    // skip invalid coords (prevents MapLibre crash)
    if ($lat === null || $lng === null) continue;

    $district  = blm_first_term_slug($post_id, 'district');
    $category  = blm_first_term_slug($post_id, 'location-type');
    $categories = blm_all_term_slugs($post_id, 'location-type');
    $tags      = blm_all_term_slugs($post_id, 'age_range');
    $amenities = blm_all_term_slugs($post_id, 'facility');
    $admission = blm_all_term_slugs($post_id, 'admission_policy');
    $cc_meta = $location_course_categories[$post_id] ?? null;
    $has_courses = !empty($cc_meta['has_course']);

    $address = $meta['address'][0] ?? '';

    $places[] = [
      'id'        => intval($post_id),
      'name'      => get_the_title($post_id),
      'district'  => (string)$district,
      // Keep single category for backward compatibility, but include all categories
      'category'  => (string)($category ?: ($categories[0] ?? '')),
      'categories' => $categories,
      'tags'      => $tags,
      'amenities' => $amenities,
      'admission_policies' => $admission,
      'has_courses' => $has_courses,
      'lat'       => $lat,
      'lng'       => $lng,
      'address'   => is_string($address) ? trim($address) : (string)$address,
      'list_image' => blm_build_location_first_image($post_id),
    ];
  }

  return [
    'places' => $places,
    'meta' => [
      'count' => count($places),
      'schema_version' => 9,
      'generated_at' => time(),
    ],
  ];
}

function blm_get_map_page_url() {
  $pages = get_posts([
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-blm.php',
    'fields'         => 'ids',
  ]);
  if (!empty($pages)) return get_permalink($pages[0]);
  return home_url('/');
}

function blm_build_filters_payload() {
  return [
    'districts'  => blm_get_terms_simple('district'),
    'categories' => blm_get_terms_simple('location-type'),
    'age_ranges' => blm_get_terms_simple('age_range'),
    'facilities' => blm_get_terms_simple('facility'),
    'admission_policies' => blm_get_terms_simple('admission_policy'),
    'meta' => [
      'generated_at' => time(),
    ],
  ];
}

function blm_build_location_images($post_id) {
  $images = [];
  $gallery = function_exists('get_field') ? get_field('images', $post_id) : null;

  // Fallback: read raw postmeta for gallery field
  if (!is_array($gallery) || empty($gallery)) {
    $raw = get_post_meta($post_id, 'images', true);
    if (is_string($raw)) $raw = maybe_unserialize($raw);
    if (is_array($raw)) $gallery = $raw;
    elseif (!empty($raw)) $gallery = [$raw];
  }

  if (!is_array($gallery) || empty($gallery)) return $images;

  foreach ($gallery as $img) {
    // ACF image array
    if (is_array($img) && !empty($img['url'])) {
      $images[] = [
        'url' => $img['url'],
        'medium' => $img['sizes']['medium'] ?? $img['url'],
        'large' => $img['sizes']['large'] ?? $img['url'],
        'caption' => $img['caption'] ?? '',
      ];
      continue;
    }

    // Attachment ID
    if (is_numeric($img)) {
      $id = intval($img);
      $full = wp_get_attachment_url($id);
      if (!$full) continue;
      $med = wp_get_attachment_image_src($id, 'medium');
      $lg = wp_get_attachment_image_src($id, 'large');
      $caption = wp_get_attachment_caption($id);
      $images[] = [
        'url' => $full,
        'medium' => is_array($med) ? $med[0] : $full,
        'large' => is_array($lg) ? $lg[0] : $full,
        'caption' => $caption ?: '',
      ];
    }
  }

  return $images;
}

function blm_normalize_image_for_list($value) {
  if (!$value) return null;

  // ACF image array
  if (is_array($value) && !empty($value['url'])) {
    $url = $value['url'];
    return [
      'thumb' => $value['sizes']['thumbnail'] ?? $url,
      'medium' => $value['sizes']['medium'] ?? $url,
      'large' => $value['sizes']['large'] ?? $url,
      'caption' => (string) ($value['caption'] ?? ''),
    ];
  }

  // Attachment ID
  if (is_numeric($value)) {
    $id = (int) $value;
    $full = wp_get_attachment_url($id);
    if (!$full) return null;
    $thumb = wp_get_attachment_image_src($id, 'thumbnail');
    $med = wp_get_attachment_image_src($id, 'medium');
    $lg = wp_get_attachment_image_src($id, 'large');
    $caption = wp_get_attachment_caption($id);
    return [
      'thumb' => is_array($thumb) ? $thumb[0] : $full,
      'medium' => is_array($med) ? $med[0] : $full,
      'large' => is_array($lg) ? $lg[0] : $full,
      'caption' => $caption ?: '',
    ];
  }

  // Raw URL
  if (is_string($value) && $value !== '') {
    return [
      'thumb' => $value,
      'medium' => $value,
      'large' => $value,
      'caption' => '',
    ];
  }

  return null;
}

function blm_build_location_first_image($post_id) {
  // Lightweight mode for locations-light: read only raw postmeta to avoid memory spikes.
  $raw = get_post_meta($post_id, 'images', true);
  if (is_string($raw)) $raw = maybe_unserialize($raw);

  if (is_array($raw) && !empty($raw)) {
    $img = blm_normalize_image_for_list(reset($raw));
    if ($img) return $img;
  } else {
    $img = blm_normalize_image_for_list($raw);
    if ($img) return $img;
  }

  // Fallbacks if images gallery empty
  foreach (['image', 'thumbnail', 'cover', 'featured_image'] as $field_name) {
    $v = get_post_meta($post_id, $field_name, true);
    if (is_string($v)) $v = maybe_unserialize($v);
    $img = blm_normalize_image_for_list($v);
    if ($img) return $img;
  }

  // Final fallback: WP featured image
  $thumb_id = get_post_thumbnail_id($post_id);
  if ($thumb_id) {
    $img = blm_normalize_image_for_list((int) $thumb_id);
    if ($img) return $img;
  }

  return null;
}

/** ---------- cache invalidation ---------- */

function blm_clear_light_cache() {
  delete_transient('blm_locations_light_v16');
  delete_transient('blm_locations_light_v15');
  delete_transient('blm_locations_light_v14');
  delete_transient('blm_locations_light_v13');
  delete_transient('blm_locations_light_v12');
  delete_transient('blm_locations_light_v11');
  delete_transient('blm_locations_light_v10');
  delete_transient('blm_locations_light_v9');
  delete_transient('blm_filters_v6');
}

/** ล้าง full cache ของ location รายตัว */
function blm_clear_full_cache_for_post($post_id) {
  delete_transient(blm_location_full_cache_key($post_id));
}

function blm_clear_full_cache_for_location_id($location_id) {
  $location_id = (int) $location_id;
  if ($location_id) delete_transient(blm_location_full_cache_key($location_id));
}

/** ---------- auto rebuild caches on content update (NEW) ---------- */

/**
 * กัน rebuild ถี่เกินไปด้วย lock + schedule single event
 * - post_id ใช้เพื่อ prewarm full cache ของรายการที่เพิ่งแก้
 */
function blm_schedule_rebuild($post_id = 0) {
  $post_id = intval($post_id);

  // กัน schedule ซ้ำถี่ ๆ (10 วินาที)
  if (get_transient('blm_rebuild_lock')) return;

  set_transient('blm_rebuild_lock', 1, 10);

  // หน่วง 5 วินาที กัน save รัว ๆ
  wp_schedule_single_event(time() + 5, 'blm_rebuild_caches_event', [$post_id]);
}

/** งาน rebuild + prewarm full */
add_action('blm_rebuild_caches_event', function ($post_id = 0) {

  // rebuild light
  $light = blm_build_locations_light_payload();
  set_transient('blm_locations_light_v16', $light, 6 * HOUR_IN_SECONDS);

  // rebuild filters
  $filters = blm_build_filters_payload();
  set_transient('blm_filters_v6', $filters, 6 * HOUR_IN_SECONDS);

  // prewarm full cache for updated post (ถ้าเป็น location ที่ publish)
  $post_id = intval($post_id);
  if ($post_id && get_post_type($post_id) === 'location' && get_post_status($post_id) === 'publish') {
    $meta = get_post_meta($post_id);

    // ---- ปรับคีย์ให้ตรง ACF ของคุณ ----
    $phone = $meta['phone'][0] ?? '';
    $hours = $meta['opening_hours'][0] ?? ($meta['hours'][0] ?? '');
    $description = blm_get_location_description_from_content($post_id);
    if ($description === '') $description = $meta['description'][0] ?? '';

    // links
    $googleMaps = $meta['google_maps'][0] ?? '';
    $facebook   = $meta['facebook'][0] ?? '';

    // images (ACF gallery)
    $images = blm_build_location_images($post_id);

    // courses from session -> course (unique)
    $courses = blm_get_courses_for_location($post_id);

    $map_url = add_query_arg(['place' => $post_id], blm_get_map_page_url());
    $resp = [
      'id' => $post_id,
      'name' => get_the_title($post_id),
      'permalink' => get_permalink($post_id),
      'map_url' => $map_url,
      'admission_policies' => blm_all_term_slugs($post_id, 'admission_policy'),
      'phone' => is_string($phone) ? trim($phone) : (string)$phone,
      'hours' => is_string($hours) ? trim($hours) : (string)$hours,
      'description' => is_string($description) ? trim($description) : (string)$description,
      'links' => [
        'googleMaps' => is_string($googleMaps) ? trim($googleMaps) : '',
        'facebook'   => is_string($facebook) ? trim($facebook) : '',
      ],
      'images' => $images,
      'courses' => $courses,
      'meta' => [
        'generated_at' => time(),
      ],
    ];

    set_transient(blm_location_full_cache_key($post_id), $resp, 15 * MINUTE_IN_SECONDS);
  }
}, 10, 1);

/** ---------- hooks: post changes ---------- */

add_action('save_post_location', function ($post_id) {
  // กัน autosave / revision
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (wp_is_post_revision($post_id)) return;

  blm_clear_light_cache();
  blm_clear_full_cache_for_post($post_id);

  // NEW: rebuild caches อัตโนมัติหลังอัปเดต
  blm_schedule_rebuild($post_id);
}, 10, 1);

// session changes should refresh related location full cache (courses tab depends on sessions)
add_action('save_post_session', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (wp_is_post_revision($post_id)) return;

  $location_id = blm_location_id_from_session($post_id);
  blm_clear_full_cache_for_location_id($location_id);
}, 10, 1);

add_action('trashed_post', function ($post_id) {
  if (get_post_type($post_id) !== 'session') return;
  $location_id = blm_location_id_from_session($post_id);
  blm_clear_full_cache_for_location_id($location_id);
}, 10, 1);

add_action('before_delete_post', function ($post_id) {
  if (get_post_type($post_id) !== 'session') return;
  $location_id = blm_location_id_from_session($post_id);
  blm_clear_full_cache_for_location_id($location_id);
}, 10, 1);

// course changes should refresh locations that reference its sessions
add_action('save_post_course', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (wp_is_post_revision($post_id)) return;

  $session_ids = get_posts([
    'post_type'      => 'session',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => [
      'relation' => 'OR',
      [
        'key'     => 'course',
        'value'   => (string) $post_id,
        'compare' => '=',
      ],
      [
        'key'     => 'course',
        'value'   => '"' . (string) $post_id . '"',
        'compare' => 'LIKE',
      ],
    ],
  ]);

  if (empty($session_ids)) return;

  $location_ids = [];
  foreach ($session_ids as $sid) {
    $loc_id = blm_location_id_from_session($sid);
    if ($loc_id) $location_ids[$loc_id] = true;
  }

  foreach (array_keys($location_ids) as $loc_id) {
    blm_clear_full_cache_for_location_id($loc_id);
  }
}, 10, 1);

add_action('deleted_post', function ($post_id) {
  if (get_post_type($post_id) === 'location') {
    blm_clear_light_cache();
    blm_clear_full_cache_for_post($post_id);

    // NEW: rebuild (ไม่ส่ง id เพราะโดนลบแล้ว)
    blm_schedule_rebuild(0);
  }
});

/** term changes also affect filters */
add_action('created_term', function ($term_id, $tt_id, $taxonomy) {
  if (in_array($taxonomy, ['district','location-type','facility','age_range'], true)) {
    blm_clear_light_cache();
    blm_schedule_rebuild(0); // NEW
  }
}, 10, 3);

add_action('edited_term', function ($term_id, $tt_id, $taxonomy) {
  if (in_array($taxonomy, ['district','location-type','facility','age_range'], true)) {
    blm_clear_light_cache();
    blm_schedule_rebuild(0); // NEW
  }
}, 10, 3);

add_action('delete_term', function ($term_id, $tt_id, $taxonomy) {
  if (in_array($taxonomy, ['district','location-type','facility','age_range'], true)) {
    blm_clear_light_cache();
    blm_schedule_rebuild(0); // NEW
  }
}, 10, 3);

/** ---------- scheduled refresh (no server config needed) ---------- */

/** เพิ่ม schedule ทุก 15 นาที */
add_filter('cron_schedules', function ($schedules) {
  if (!isset($schedules['blm_15min'])) {
    $schedules['blm_15min'] = [
      'interval' => 15 * 60,
      'display'  => 'Every 15 minutes (BLM)'
    ];
  }
  return $schedules;
});

/** ตั้ง cron ตอนเปิดปลั๊กอิน */
register_activation_hook(__FILE__, function () {
  if (!wp_next_scheduled('blm_refresh_caches')) {
    wp_schedule_event(time() + 60, 'blm_15min', 'blm_refresh_caches');
  }

  // อุ่น cache ทันทีหลังเปิด (กัน request แรกช้า)
  $light = blm_build_locations_light_payload();
  set_transient('blm_locations_light_v16', $light, 6 * HOUR_IN_SECONDS);

  $filters = blm_build_filters_payload();
  set_transient('blm_filters_v6', $filters, 6 * HOUR_IN_SECONDS);
});

/** ถอด cron ตอนปิดปลั๊กอิน */
register_deactivation_hook(__FILE__, function () {
  $t = wp_next_scheduled('blm_refresh_caches');
  if ($t) wp_unschedule_event($t, 'blm_refresh_caches');
});

/** ให้ cron มาสร้าง cache ใหม่เป็นช่วง ๆ */
add_action('blm_refresh_caches', function () {
  $light = blm_build_locations_light_payload();
  set_transient('blm_locations_light_v16', $light, 6 * HOUR_IN_SECONDS);

  $filters = blm_build_filters_payload();
  set_transient('blm_filters_v6', $filters, 6 * HOUR_IN_SECONDS);
});

/** ---------- REST ---------- */

add_action('rest_api_init', function () {

  /**
   * GET /wp-json/blm/v1/locations-light
   */
  register_rest_route('blm/v1', '/locations-light', [
    'methods'  => 'GET',
    'permission_callback' => '__return_true',
    'callback' => function (WP_REST_Request $req) {
      return blm_rest_guard(function () {
        // อ่านจาก cache เป็นหลัก
        $resp = get_transient('blm_locations_light_v16');
        if (!$resp) $resp = get_transient('blm_locations_light_v15');
        if (!$resp) $resp = get_transient('blm_locations_light_v14');
        if (!$resp) $resp = get_transient('blm_locations_light_v13');
        if (!$resp) $resp = get_transient('blm_locations_light_v12');
        if (!$resp) $resp = get_transient('blm_locations_light_v11');
        if (!$resp) $resp = get_transient('blm_locations_light_v10');
        if (!$resp) $resp = get_transient('blm_locations_light_v9');
        // backward fallback (old key)
        if (!$resp) $resp = get_transient('blm_locations_light_v7');
        if (is_array($resp) && intval($resp['meta']['schema_version'] ?? 0) < 9) {
          $resp = null;
        }

        // fallback: ถ้ายังไม่เคยมี cache (เช่นเพิ่งย้าย server/ลบ transient)
        if (!$resp) {
          try {
            $resp = blm_build_locations_light_payload();
            set_transient('blm_locations_light_v16', $resp, 6 * HOUR_IN_SECONDS);
          } catch (Throwable $e) {
            error_log('[BLM API] locations-light rebuild failed: ' . $e->getMessage());
            $resp = [
              'places' => [],
              'meta' => [
                'count' => 0,
                'schema_version' => 9,
                'generated_at' => time(),
              ],
            ];
          }
        }

        // ---- Browser cache 15 นาที (ไม่ต้องแก้ server) ----
        $generated_at = intval($resp['meta']['generated_at'] ?? time());
        $etag = '"' . md5((string)$generated_at) . '"';

        $if_none_match = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($if_none_match === $etag) {
          // client มีของล่าสุดแล้ว
          $r304 = new WP_REST_Response(null, 304);
          $r304->header('Cache-Control', 'public, max-age=900');
          $r304->header('ETag', $etag);
          return $r304;
        }

        $response = new WP_REST_Response($resp, 200);
        $response->header('Cache-Control', 'public, max-age=900');
        $response->header('ETag', $etag);
        return $response;
      });
    },
  ]);

  /**
   * GET /wp-json/blm/v1/filters
   */
  register_rest_route('blm/v1', '/filters', [
    'methods'  => 'GET',
    'permission_callback' => '__return_true',
    'callback' => function () {
      return blm_rest_guard(function () {
        $resp = get_transient('blm_filters_v6');
        // backward fallback (old key)
        if (!$resp) $resp = get_transient('blm_filters_v4');
        if (!$resp) {
          try {
            $resp = blm_build_filters_payload();
            set_transient('blm_filters_v6', $resp, 6 * HOUR_IN_SECONDS);
          } catch (Throwable $e) {
            error_log('[BLM API] filters rebuild failed: ' . $e->getMessage());
            $resp = [
              'districts' => [],
              'categories' => [],
              'age_ranges' => [],
              'facilities' => [],
              'admission_policies' => [],
              'meta' => ['generated_at' => time()],
            ];
          }
        }

        // Browser cache 15 นาทีเช่นกัน
        $generated_at = intval($resp['meta']['generated_at'] ?? time());
        $etag = '"' . md5((string)$generated_at) . '"';

        $if_none_match = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($if_none_match === $etag) {
          $r304 = new WP_REST_Response(null, 304);
          $r304->header('Cache-Control', 'public, max-age=900');
          $r304->header('ETag', $etag);
          return $r304;
        }

        $response = new WP_REST_Response($resp, 200);
        $response->header('Cache-Control', 'public, max-age=900');
        $response->header('ETag', $etag);
        return $response;
      });
    },
  ]);

  /**
   * GET /wp-json/blm/v1/location/<id>
   * Full details for one location (lazy-load)
   */
  register_rest_route('blm/v1', '/location/(?P<id>\d+)', [
    'methods'  => 'GET',
    'permission_callback' => '__return_true',
    'callback' => function (WP_REST_Request $req) {
      return blm_rest_guard(function () use ($req) {

        $id = intval($req['id']);
        if (!$id || get_post_type($id) !== 'location' || get_post_status($id) !== 'publish') {
          return new WP_REST_Response(['error' => 'not_found'], 404);
        }

        $cache_key = blm_location_full_cache_key($id);
        $cached = get_transient($cache_key);
        if ($cached) return new WP_REST_Response($cached, 200);

        $meta = get_post_meta($id);

      // ---- ปรับคีย์ให้ตรง ACF ของคุณ ----
      $phone = $meta['phone'][0] ?? '';
      $hours = $meta['opening_hours'][0] ?? ($meta['hours'][0] ?? '');
      $description = blm_get_location_description_from_content($id);
      if ($description === '') $description = $meta['description'][0] ?? '';

      // links
      $acfMapUrl  = $meta['map_url'][0] ?? '';
      $googleMaps = $meta['google_maps'][0] ?? '';
      $facebook   = $meta['facebook'][0] ?? '';

      // images (ACF gallery)
      $images = blm_build_location_images($id);

      // courses from session -> course (unique)
      $courses = blm_get_courses_for_location($id);

      $map_url = add_query_arg(['place' => $id], blm_get_map_page_url());
      $resp = [
        'id' => $id,
        'name' => get_the_title($id),
        'permalink' => get_permalink($id),
        'map_url' => $map_url,
        'admission_policies' => blm_all_term_slugs($id, 'admission_policy'),
        'phone' => is_string($phone) ? trim($phone) : (string)$phone,
      'hours' => is_string($hours) ? trim($hours) : (string)$hours,
      'description' => is_string($description) ? trim($description) : (string)$description,
        'links' => [
          'googleMaps' => (is_string($acfMapUrl) && trim($acfMapUrl) !== '')
            ? trim($acfMapUrl)
            : (is_string($googleMaps) ? trim($googleMaps) : ''),
          'facebook'   => is_string($facebook) ? trim($facebook) : '',
        ],
      'images' => $images,
      'courses' => $courses,
      'meta' => [
        'generated_at' => time(),
        ],
      ];

        set_transient($cache_key, $resp, 15 * MINUTE_IN_SECONDS);
        return new WP_REST_Response($resp, 200);
      });
    },
  ]);

});
