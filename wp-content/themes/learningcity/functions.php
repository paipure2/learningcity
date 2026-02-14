<?php

require_once 'inc/constants.php';

/**
 * =========================================================
 * NOTE: โครงสร้างไฟล์นี้ (Ctrl+F หาได้ง่าย)
 * - [FRONT] enqueue libraries + Vite assets
 * - [FRONT] Course modal AJAX
 * - [HELPER] nav_active + helper functions
 * - [COURSE FILTER] Course open registration flags + query filter
 * - [FRONT] Hour Chart data (transient)
 * - [ADMIN SESSION] แสดงข้อมูล Course ใต้ ACF field (AJAX)
 * - [ADMIN SESSION] ตั้งชื่อ session จาก course - location
 * - [ADMIN SESSION] ซ่อน Title/Slug session + ปิด session single
 * - [ADMIN SESSION] Filter Location (เร็ว + cache)  ***ปรับให้เร็วสำหรับ 3-4k sessions***
 * - [ADMIN SESSION] Preview/View -> Course permalink (เร็ว)
 * - [ADMIN] แสดง "อัปเดตล่าสุดโดย + วันเวลา" (course/location/session)
 * =========================================================
 */


/* =========================================================
 * [FRONT] Enqueue library styles and scripts properly
 * ========================================================= */
function blc_enqueue_libraries() {

  // ===== Swiper =====
  wp_enqueue_style(
    'swiper-css',
    'https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css',
    array(),
    '12.0.3'
  );

  wp_enqueue_script(
    'swiper-bundle',
    'https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js',
    array(),
    '12.0.3',
    true
  );

  // ===== Fancybox =====
  wp_enqueue_style(
    'fancybox-css',
    'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.css',
    array(),
    '5.0'
  );

  wp_enqueue_script(
    'fancybox-js',
    'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.umd.js',
    array(),
    '5.0',
    true
  );

  // ===== Theme CSS =====
  wp_enqueue_style(
    'theme-css',
    get_stylesheet_uri(),
    array(),
    filemtime(get_stylesheet_directory() . '/style.css')
  );
}
add_action('wp_enqueue_scripts', 'blc_enqueue_libraries');


/* =========================================================
 * [FRONT] Vite assets enqueue
 * ========================================================= */
if (defined('VITE_THEME_MANIFEST_PATH') && file_exists(VITE_THEME_MANIFEST_PATH)) {
    $scriptHandles = [];

    add_action('wp_enqueue_scripts', function () use (&$scriptHandles) {
        $manifest = json_decode(file_get_contents(VITE_THEME_MANIFEST_PATH), true);
        $themeVersion = wp_get_theme()->get('Version');

        if (is_array($manifest)) {
            foreach ($manifest as $key => $value) {
                // Skip admin-scripts.js - it should only be loaded in admin via admin_enqueue_scripts
                if (strpos($key, 'admin-scripts.js') !== false) {
                    continue;
                }

                if (empty($value['file'])) continue;

                $file = $value['file'];
                $ext = pathinfo($file, PATHINFO_EXTENSION);

                if ($ext === 'css') {
                    wp_enqueue_style($key, VITE_THEME_ASSETS_DIR . '/' . $file, [], $themeVersion);
                } elseif ($ext === 'js') {
                    $handle = str_replace(['/', '.', ' '], '-', $key);
                    wp_enqueue_script($handle, VITE_THEME_ASSETS_DIR . '/' . $file, [], $themeVersion, true);
                    $scriptHandles[] = $handle;
                }
            }
        }
    });

    // Add type="module" to all enqueued scripts from Vite
    add_filter('script_loader_tag', function ($tag, $handle) use (&$scriptHandles) {
        if (in_array($handle, $scriptHandles, true)) {
            return str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }, 10, 2);

} else {
    require_once 'inc/vite.php';
}

require_once 'inc/init/setup-wordpress.php';

// Admin includes
require_once 'inc/admin/admin-style.php';
require_once 'inc/admin/admin-class.php';
require_once 'inc/admin/admin-scripts.php';
require_once 'inc/admin/hide-menu-admin.php';
require_once 'inc/admin/button-edit.php';
require_once 'inc/admin/disabled-post.php';
require_once 'inc/admin/acf-tabs-name.php';
require_once 'inc/admin/acf-preview-styles.php';
require_once 'inc/admin/acf-flexible-keyboard.php';

/* =========================================================
 * [FRONT] AJAX Modal Course
 * - ลดซ้ำ: ใช้ block เดียว (มีทั้ง enqueue + config)
 * ========================================================= */
add_action('wp_enqueue_scripts', function () {

  wp_enqueue_script(
    'course-modal-ajax',
    get_template_directory_uri() . '/assets/scripts/modules/course-modal-ajax.js',
    [], // ถ้าใช้ jQuery ให้เปลี่ยนเป็น ['jquery']
    null,
    true
  );

  // ทำให้ config เป็น global ใช้ได้ทุกไฟล์
  $data = [
    'ajax_url'    => admin_url('admin-ajax.php'),
    'nonce'       => wp_create_nonce('course_modal_nonce'),
    'report_nonce'=> wp_create_nonce('lc_report_course'),
    'archive_url' => get_post_type_archive_link('course'),
    'current_course_id' => is_singular('course') ? (int) get_queried_object_id() : 0,
  ];

  wp_add_inline_script(
    'course-modal-ajax',
    'window.COURSE_MODAL = ' . wp_json_encode($data) . ';',
    'before'
  );
});

if (!function_exists('load_course_modal')) {
  add_action('wp_ajax_load_course_modal', 'load_course_modal');
  add_action('wp_ajax_nopriv_load_course_modal', 'load_course_modal');

  function load_course_modal() {
    check_ajax_referer('course_modal_nonce', 'nonce');

    $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
    if (!$course_id) {
      wp_send_json_error(['message' => 'missing course_id'], 400);
    }

    $course_post = get_post($course_id);
    if (!$course_post || $course_post->post_status !== 'publish') {
      wp_send_json_error(['message' => 'course not found'], 404);
    }

    if ($course_post->post_type !== 'course') {
      wp_send_json_error(['message' => 'invalid post type'], 400);
    }

    $tpl = locate_template('template-parts/components/modal-course-ajax.php');
    if (!$tpl) {
      wp_send_json_error(['message' => 'template not found'], 500);
    }

    ob_start();
    global $post;
    $post = $course_post;
    setup_postdata($post);
    include $tpl;
    wp_reset_postdata();
    $html = ob_get_clean();

    wp_send_json_success([
      'html'      => $html,
      'permalink' => get_permalink($course_id),
      'title'     => get_the_title($course_id),
    ]);
  }
}

// Enqueue BLM assets only on BLM page template
add_action('wp_enqueue_scripts', function () {
  if (!is_page_template('page-blm.php')) return;

  wp_enqueue_style(
    'maplibre-gl',
    'https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css',
    [],
    '4.7.1'
  );
  wp_enqueue_script(
    'maplibre-gl',
    'https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js',
    [],
    '4.7.1',
    true
  );
}, 20);


/* =========================================================
 * [LOCATION REPORTS] Report incorrect info (front + admin)
 * ========================================================= */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) return;

  acf_add_local_field_group([
    'key' => 'group_lc_location_reports',
    'title' => 'Location Reports',
    'fields' => [
      [
        'key' => 'field_lc_reports',
        'label' => 'Reports',
        'name' => 'reports',
        'type' => 'repeater',
        'layout' => 'table',
        'button_label' => 'Add Report',
        'sub_fields' => [
          [
            'key' => 'field_lc_reported_at',
            'label' => 'วันที่แจ้ง',
            'name' => 'reported_at',
            'type' => 'date_time_picker',
            'display_format' => 'Y-m-d H:i',
            'return_format' => 'Y-m-d H:i',
            'wrapper' => ['width' => 30],
          ],
          [
            'key' => 'field_lc_report_status',
            'label' => 'สถานะ',
            'name' => 'status',
            'type' => 'select',
            'choices' => [
              'pending' => 'ยังไม่แก้ไข',
              'reviewing' => 'กำลังตรวจสอบ',
              'resolved' => 'แก้ไขแล้ว',
            ],
            'default_value' => 'pending',
            'wrapper' => ['width' => 20],
          ],
          [
            'key' => 'field_lc_report_details_group',
            'label' => 'รายละเอียด',
            'name' => 'report_details_group',
            'type' => 'group',
            'acfe_group_modal' => 1,
            'acfe_group_modal_close' => 1,
            'acfe_group_modal_button' => 'ดูรายละเอียด',
            'acfe_group_modal_size' => 'large',
            'sub_fields' => [
              [
                'key' => 'field_lc_report_topics',
                'label' => 'Topics',
                'name' => 'report_topics',
                'type' => 'checkbox',
                'choices' => [
                  'address' => 'ที่อยู่',
                  'phone' => 'เบอร์โทร',
                  'hours' => 'เวลาทำการ',
                  'images' => 'รูปภาพ',
                  'links' => 'ลิงก์',
                  'other' => 'อื่น ๆ',
                ],
              ],
              [
                'key' => 'field_lc_report_locations',
                'label' => 'Related Locations',
                'name' => 'report_locations',
                'type' => 'textarea',
                'rows' => 3,
                'instructions' => 'สถานที่ที่ผู้แจ้งเลือกจากหน้าคอร์ส',
              ],
              [
                'key' => 'field_lc_report_details',
                'label' => 'Details',
                'name' => 'report_details',
                'type' => 'textarea',
              ],
              [
                'key' => 'field_lc_reporter_name',
                'label' => 'Reporter Name',
                'name' => 'reporter_name',
                'type' => 'text',
              ],
              [
                'key' => 'field_lc_reporter_contact',
                'label' => 'Reporter Contact',
                'name' => 'reporter_contact',
                'type' => 'text',
              ],
              [
                'key' => 'field_lc_report_admin_note',
                'label' => 'Admin Note',
                'name' => 'admin_note',
                'type' => 'textarea',
              ],
            ],
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'location',
        ],
      ],
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'course',
        ],
      ],
    ],
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
  ]);
});

function lc_report_allowed_topics() {
  return ['address', 'phone', 'hours', 'images', 'links', 'other'];
}

function lc_recalc_report_counts($post_id) {
  $post_type = get_post_type($post_id);
  if (!in_array($post_type, ['location', 'course'], true)) return;
  if (!function_exists('get_field')) return;

  $rows = get_field('field_lc_reports', $post_id);
  $total = is_array($rows) ? count($rows) : 0;
  $open = 0;
  $pending = 0;
  $reviewing = 0;
  $resolved = 0;
  $last_at = 0;
  if (is_array($rows)) {
    foreach ($rows as $r) {
      $status = is_array($r) ? ($r['status'] ?? 'pending') : 'pending';
      if ($status === 'pending') $pending++;
      if ($status === 'reviewing') $reviewing++;
      if ($status === 'resolved') $resolved++;
      if ($status !== 'resolved') $open++;
      $reported_at = is_array($r) ? ($r['reported_at'] ?? '') : '';
      if ($reported_at) {
        $ts = strtotime($reported_at);
        if ($ts && $ts > $last_at) $last_at = $ts;
      }
    }
  }
  update_post_meta($post_id, '_lc_report_total_count', $total);
  update_post_meta($post_id, '_lc_report_open_count', $open);
  update_post_meta($post_id, '_lc_report_pending_count', $pending);
  update_post_meta($post_id, '_lc_report_reviewing_count', $reviewing);
  update_post_meta($post_id, '_lc_report_resolved_count', $resolved);
  update_post_meta($post_id, '_lc_report_last_at', $last_at);
}

add_action('acf/save_post', function ($post_id) {
  $post_type = get_post_type($post_id);
  if (!in_array($post_type, ['location', 'course'], true)) return;
  lc_recalc_report_counts($post_id);
}, 20);

add_action('wp_ajax_lc_report_location', 'lc_report_location');
add_action('wp_ajax_nopriv_lc_report_location', 'lc_report_location');
add_action('wp_ajax_lc_report_course', 'lc_report_course');
add_action('wp_ajax_nopriv_lc_report_course', 'lc_report_course');

function lc_report_location() {
  check_ajax_referer('lc_report_location', 'nonce');

  $post_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
  if (!$post_id || get_post_type($post_id) !== 'location' || get_post_status($post_id) !== 'publish') {
    wp_send_json_error(['message' => 'invalid location'], 400);
  }

  // Honeypot (bots often fill hidden fields)
  $website = isset($_POST['website']) ? trim((string) $_POST['website']) : '';
  if ($website !== '') {
    wp_send_json_success(['message' => 'ok']);
  }

  // Rate limit by IP (1 request per 60 seconds)
  $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
  $rate_key = 'lc_report_rl_' . md5($ip);
  if (get_transient($rate_key)) {
    wp_send_json_error(['message' => 'rate_limited'], 429);
  }
  set_transient($rate_key, 1, 60);

  $topics = isset($_POST['topics']) ? (array) $_POST['topics'] : [];
  $topics = array_values(array_intersect($topics, lc_report_allowed_topics()));
  $details = isset($_POST['details']) ? sanitize_textarea_field($_POST['details']) : '';
  $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
  $contact = isset($_POST['contact']) ? sanitize_text_field($_POST['contact']) : '';

  if (empty($topics) && mb_strlen($details) < 3) {
    wp_send_json_error(['message' => 'missing details'], 400);
  }

  if (!function_exists('add_row')) {
    wp_send_json_error(['message' => 'acf not available'], 500);
  }

  $row = [
    'reported_at' => current_time('Y-m-d H:i'),
    'status' => 'pending',
    'report_details_group' => [
      'report_topics' => $topics,
      'report_details' => $details,
      'reporter_name' => $name,
      'reporter_contact' => $contact,
      'admin_note' => '',
    ],
  ];

  $added = add_row('field_lc_reports', $row, $post_id);
  if (!$added) {
    wp_send_json_error(['message' => 'failed to save'], 500);
  }

  lc_recalc_report_counts($post_id);

  wp_send_json_success(['message' => 'ok']);
}

function lc_report_course() {
  check_ajax_referer('lc_report_course', 'nonce');

  $post_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
  if (!$post_id || get_post_type($post_id) !== 'course' || get_post_status($post_id) !== 'publish') {
    wp_send_json_error(['message' => 'invalid course'], 400);
  }

  $website = isset($_POST['website']) ? trim((string) $_POST['website']) : '';
  if ($website !== '') {
    wp_send_json_success(['message' => 'ok']);
  }

  $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
  $rate_key = 'lc_report_course_rl_' . md5($ip);
  if (get_transient($rate_key)) {
    wp_send_json_error(['message' => 'rate_limited'], 429);
  }
  set_transient($rate_key, 1, 60);

  $topics = isset($_POST['topics']) ? (array) $_POST['topics'] : [];
  $topics = array_values(array_intersect($topics, lc_report_allowed_topics()));
  $location_ids = isset($_POST['location_ids']) ? (array) $_POST['location_ids'] : [];
  $location_ids = array_values(array_unique(array_filter(array_map('intval', $location_ids))));
  $details = isset($_POST['details']) ? sanitize_textarea_field($_POST['details']) : '';
  $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
  $contact = isset($_POST['contact']) ? sanitize_text_field($_POST['contact']) : '';

  $allowed_location_ids = lc_get_course_location_ids_for_report($post_id);
  if (!empty($allowed_location_ids) && !empty($location_ids)) {
    $location_ids = array_values(array_intersect($location_ids, $allowed_location_ids));
  } else {
    $location_ids = [];
  }

  $location_titles = [];
  foreach ($location_ids as $lid) {
    $title = get_the_title($lid);
    if ($title) $location_titles[] = $title;
  }

  if (empty($topics) && mb_strlen($details) < 3) {
    wp_send_json_error(['message' => 'missing details'], 400);
  }

  if (!function_exists('add_row')) {
    wp_send_json_error(['message' => 'acf not available'], 500);
  }

  $row = [
    'reported_at' => current_time('Y-m-d H:i'),
    'status' => 'pending',
    'report_details_group' => [
      'report_topics' => $topics,
      'report_locations' => !empty($location_titles) ? implode("\n", $location_titles) : '',
      'report_details' => $details,
      'reporter_name' => $name,
      'reporter_contact' => $contact,
      'admin_note' => '',
    ],
  ];

  $added = add_row('field_lc_reports', $row, $post_id);
  if (!$added) {
    wp_send_json_error(['message' => 'failed to save'], 500);
  }

  lc_recalc_report_counts($post_id);
  wp_send_json_success(['message' => 'ok']);
}

function lc_get_course_location_ids_for_report($course_id) {
  $course_id = (int) $course_id;
  if ($course_id <= 0) return [];

  $session_ids = get_posts([
    'post_type' => 'session',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
      [
        'key' => 'course',
        'value' => $course_id,
        'compare' => '=',
      ],
    ],
  ]);

  if (empty($session_ids)) return [];

  $ids = [];
  foreach ($session_ids as $sid) {
    $location = function_exists('get_field') ? get_field('location', $sid) : get_post_meta($sid, 'location', true);
    $location_id = 0;
    if (is_object($location) && isset($location->ID)) {
      $location_id = (int) $location->ID;
    } elseif (is_numeric($location)) {
      $location_id = (int) $location;
    }

    if ($location_id > 0 && get_post_type($location_id) === 'location') {
      $ids[] = $location_id;
    }
  }

  return array_values(array_unique($ids));
}



/* =========================================================
 * [ADMIN] Location Reports page
 * ========================================================= */
add_action('admin_menu', function () {
  $counts = lc_reports_tab_counts();
  $pending = intval($counts['pending'] ?? 0);
  $badge = $pending > 0
    ? ' <span class="update-plugins count-' . $pending . '"><span class="update-count">' . $pending . '</span></span>'
    : '';

  add_submenu_page(
    'edit.php?post_type=location',
    'Location Reports',
    'แจ้งแก้ไข' . $badge,
    'edit_posts',
    'lc-location-reports',
    'lc_render_location_reports_page'
  );
}, 10);

add_action('admin_menu', function () {
  global $submenu;
  $parent = 'edit.php?post_type=location';
  if (empty($submenu[$parent]) || !is_array($submenu[$parent])) return;

  $items = $submenu[$parent];
  $target = null;
  foreach ($items as $i => $item) {
    if (!empty($item[2]) && $item[2] === 'lc-location-reports') {
      $target = $item;
      unset($items[$i]);
      break;
    }
  }
  if (!$target) return;

  $new = [];
  $inserted = false;
  foreach ($items as $item) {
    $new[] = $item;
    if (!empty($item[2]) && $item[2] === 'edit.php?post_type=location') {
      $new[] = $target;
      $inserted = true;
    }
  }
  if (!$inserted) $new[] = $target;

  $submenu[$parent] = $new;
}, 999);

function lc_reports_tab_counts() {
  $counts = [
    'pending' => 0,
    'reviewing' => 0,
    'resolved' => 0,
    'all' => 0,
  ];

  $q_all = new WP_Query([
    'post_type' => 'location',
    'post_status' => 'publish',
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => false,
    'meta_query' => [
      [
        'key' => '_lc_report_total_count',
        'value' => 0,
        'compare' => '>',
        'type' => 'NUMERIC',
      ],
    ],
  ]);
  $counts['all'] = (int) $q_all->found_posts;

  $q_pending = new WP_Query([
    'post_type' => 'location',
    'post_status' => 'publish',
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => false,
    'meta_query' => [
      [
        'key' => '_lc_report_pending_count',
        'value' => 0,
        'compare' => '>',
        'type' => 'NUMERIC',
      ],
    ],
  ]);
  $counts['pending'] = (int) $q_pending->found_posts;

  $q_review = new WP_Query([
    'post_type' => 'location',
    'post_status' => 'publish',
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => false,
    'meta_query' => [
      [
        'key' => '_lc_report_reviewing_count',
        'value' => 0,
        'compare' => '>',
        'type' => 'NUMERIC',
      ],
    ],
  ]);
  $counts['reviewing'] = (int) $q_review->found_posts;

  $q_resolved = new WP_Query([
    'post_type' => 'location',
    'post_status' => 'publish',
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => false,
    'meta_query' => [
      [
        'key' => '_lc_report_total_count',
        'value' => 0,
        'compare' => '>',
        'type' => 'NUMERIC',
      ],
      [
        'key' => '_lc_report_open_count',
        'value' => 0,
        'compare' => '=',
        'type' => 'NUMERIC',
      ],
    ],
  ]);
  $counts['resolved'] = (int) $q_resolved->found_posts;

  return $counts;
}

function lc_render_location_reports_page() {
  if (!current_user_can('edit_posts')) return;

  $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';
  if (!in_array($tab, ['pending', 'reviewing', 'resolved', 'all'], true)) $tab = 'pending';

  $counts = lc_reports_tab_counts();

  $meta_query = [];
  if ($tab === 'pending') {
    $meta_query[] = [
      'key' => '_lc_report_pending_count',
      'value' => 0,
      'compare' => '>',
      'type' => 'NUMERIC',
    ];
  } elseif ($tab === 'reviewing') {
    $meta_query[] = [
      'key' => '_lc_report_reviewing_count',
      'value' => 0,
      'compare' => '>',
      'type' => 'NUMERIC',
    ];
  } elseif ($tab === 'resolved') {
    $meta_query[] = [
      'key' => '_lc_report_total_count',
      'value' => 0,
      'compare' => '>',
      'type' => 'NUMERIC',
    ];
    $meta_query[] = [
      'key' => '_lc_report_open_count',
      'value' => 0,
      'compare' => '=',
      'type' => 'NUMERIC',
    ];
  } else {
    $meta_query[] = [
      'key' => '_lc_report_total_count',
      'value' => 0,
      'compare' => '>',
      'type' => 'NUMERIC',
    ];
  }

  $q = new WP_Query([
    'post_type' => 'location',
    'post_status' => 'publish',
    'posts_per_page' => 50,
    'orderby' => 'meta_value_num',
    'meta_key' => '_lc_report_last_at',
    'order' => 'DESC',
    'meta_query' => $meta_query,
  ]);

  echo '<div class="wrap">';
  echo '<h1>แจ้งแก้ไข</h1>';

  $base_url = admin_url('edit.php?post_type=location&page=lc-location-reports');
  echo '<h2 class="nav-tab-wrapper">';
  $tabs = [
    'pending' => 'ยังไม่แก้ไข',
    'reviewing' => 'กำลังตรวจสอบ',
    'resolved' => 'แก้ไขแล้ว',
    'all' => 'ทั้งหมด',
  ];
  foreach ($tabs as $key => $label) {
    $url = esc_url(add_query_arg('tab', $key, $base_url));
    $class = $tab === $key ? 'nav-tab nav-tab-active' : 'nav-tab';
    $count = intval($counts[$key] ?? 0);
    echo '<a class="' . esc_attr($class) . '" href="' . $url . '">' . esc_html($label) . ' <span class="count">(' . $count . ')</span></a>';
  }
  echo '</h2>';

  echo '<table class="wp-list-table widefat fixed striped">';
  echo '<thead><tr>';
  echo '<th>ชื่อสถานที่</th>';
  echo '<th style="width:140px">จำนวนรายงานค้าง</th>';
  echo '<th style="width:180px">วันที่รายงานล่าสุด</th>';
  echo '</tr></thead>';
  echo '<tbody>';

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      $post_id = get_the_ID();
      $open = (int) get_post_meta($post_id, '_lc_report_open_count', true);
      $last_at = (int) get_post_meta($post_id, '_lc_report_last_at', true);
      $last_text = $last_at ? date_i18n('Y-m-d H:i', $last_at) : '-';
      $edit_link = get_edit_post_link($post_id);

      echo '<tr>';
      echo '<td><a href="' . esc_url($edit_link) . '"><strong>' . esc_html(get_the_title()) . '</strong></a></td>';
      echo '<td>' . esc_html($open) . '</td>';
      echo '<td>' . esc_html($last_text) . '</td>';
      echo '</tr>';
    }
    wp_reset_postdata();
  } else {
    echo '<tr><td colspan="3">ไม่มีรายการ</td></tr>';
  }

  echo '</tbody></table>';
  echo '</div>';
}

/* =========================================================
 * [ADMIN] Course Reports page
 * ========================================================= */
add_action('admin_menu', function () {
  $counts = lc_course_reports_tab_counts();
  $pending = intval($counts['pending'] ?? 0);
  $badge = $pending > 0
    ? ' <span class="update-plugins count-' . $pending . '"><span class="update-count">' . $pending . '</span></span>'
    : '';

  add_submenu_page(
    'edit.php?post_type=course',
    'Course Reports',
    'แจ้งแก้ไข' . $badge,
    'edit_posts',
    'lc-course-reports',
    'lc_render_course_reports_page'
  );
}, 10);

add_action('admin_menu', function () {
  global $submenu;
  $parent = 'edit.php?post_type=course';
  if (empty($submenu[$parent]) || !is_array($submenu[$parent])) return;

  $items = $submenu[$parent];
  $target = null;
  foreach ($items as $i => $item) {
    if (!empty($item[2]) && $item[2] === 'lc-course-reports') {
      $target = $item;
      unset($items[$i]);
      break;
    }
  }
  if (!$target) return;

  $new = [];
  $inserted = false;
  foreach ($items as $item) {
    $new[] = $item;
    if (!empty($item[2]) && $item[2] === 'edit.php?post_type=course') {
      $new[] = $target;
      $inserted = true;
    }
  }
  if (!$inserted) $new[] = $target;

  $submenu[$parent] = $new;
}, 999);

function lc_course_reports_tab_counts() {
  $counts = [
    'pending' => 0,
    'reviewing' => 0,
    'resolved' => 0,
    'all' => 0,
  ];

  $q_all = new WP_Query([
    'post_type' => 'course',
    'post_status' => 'publish',
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => false,
    'meta_query' => [
      [
        'key' => '_lc_report_total_count',
        'value' => 0,
        'compare' => '>',
        'type' => 'NUMERIC',
      ],
    ],
  ]);
  $counts['all'] = (int) $q_all->found_posts;

  $q_pending = new WP_Query([
    'post_type' => 'course',
    'post_status' => 'publish',
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => false,
    'meta_query' => [
      [
        'key' => '_lc_report_pending_count',
        'value' => 0,
        'compare' => '>',
        'type' => 'NUMERIC',
      ],
    ],
  ]);
  $counts['pending'] = (int) $q_pending->found_posts;

  $q_review = new WP_Query([
    'post_type' => 'course',
    'post_status' => 'publish',
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => false,
    'meta_query' => [
      [
        'key' => '_lc_report_reviewing_count',
        'value' => 0,
        'compare' => '>',
        'type' => 'NUMERIC',
      ],
    ],
  ]);
  $counts['reviewing'] = (int) $q_review->found_posts;

  $q_resolved = new WP_Query([
    'post_type' => 'course',
    'post_status' => 'publish',
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => false,
    'meta_query' => [
      [
        'key' => '_lc_report_total_count',
        'value' => 0,
        'compare' => '>',
        'type' => 'NUMERIC',
      ],
      [
        'key' => '_lc_report_open_count',
        'value' => 0,
        'compare' => '=',
        'type' => 'NUMERIC',
      ],
    ],
  ]);
  $counts['resolved'] = (int) $q_resolved->found_posts;

  return $counts;
}

function lc_render_course_reports_page() {
  if (!current_user_can('edit_posts')) return;

  $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';
  if (!in_array($tab, ['pending', 'reviewing', 'resolved', 'all'], true)) $tab = 'pending';

  $counts = lc_course_reports_tab_counts();

  $meta_query = [];
  if ($tab === 'pending') {
    $meta_query[] = [
      'key' => '_lc_report_pending_count',
      'value' => 0,
      'compare' => '>',
      'type' => 'NUMERIC',
    ];
  } elseif ($tab === 'reviewing') {
    $meta_query[] = [
      'key' => '_lc_report_reviewing_count',
      'value' => 0,
      'compare' => '>',
      'type' => 'NUMERIC',
    ];
  } elseif ($tab === 'resolved') {
    $meta_query[] = [
      'key' => '_lc_report_total_count',
      'value' => 0,
      'compare' => '>',
      'type' => 'NUMERIC',
    ];
    $meta_query[] = [
      'key' => '_lc_report_open_count',
      'value' => 0,
      'compare' => '=',
      'type' => 'NUMERIC',
    ];
  } else {
    $meta_query[] = [
      'key' => '_lc_report_total_count',
      'value' => 0,
      'compare' => '>',
      'type' => 'NUMERIC',
    ];
  }

  $q = new WP_Query([
    'post_type' => 'course',
    'post_status' => 'publish',
    'posts_per_page' => 50,
    'orderby' => 'meta_value_num',
    'meta_key' => '_lc_report_last_at',
    'order' => 'DESC',
    'meta_query' => $meta_query,
  ]);

  echo '<div class="wrap">';
  echo '<h1>แจ้งแก้ไข</h1>';

  $base_url = admin_url('edit.php?post_type=course&page=lc-course-reports');
  echo '<h2 class="nav-tab-wrapper">';
  $tabs = [
    'pending' => 'ยังไม่แก้ไข',
    'reviewing' => 'กำลังตรวจสอบ',
    'resolved' => 'แก้ไขแล้ว',
    'all' => 'ทั้งหมด',
  ];
  foreach ($tabs as $key => $label) {
    $url = esc_url(add_query_arg('tab', $key, $base_url));
    $class = $tab === $key ? 'nav-tab nav-tab-active' : 'nav-tab';
    $count = intval($counts[$key] ?? 0);
    echo '<a class="' . esc_attr($class) . '" href="' . $url . '">' . esc_html($label) . ' <span class="count">(' . $count . ')</span></a>';
  }
  echo '</h2>';

  echo '<table class="wp-list-table widefat fixed striped">';
  echo '<thead><tr>';
  echo '<th>ชื่อคอร์ส</th>';
  echo '<th style="width:140px">จำนวนรายงานค้าง</th>';
  echo '<th style="width:180px">วันที่รายงานล่าสุด</th>';
  echo '</tr></thead>';
  echo '<tbody>';

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      $post_id = get_the_ID();
      $open = (int) get_post_meta($post_id, '_lc_report_open_count', true);
      $last_at = (int) get_post_meta($post_id, '_lc_report_last_at', true);
      $last_text = $last_at ? date_i18n('Y-m-d H:i', $last_at) : '-';
      $edit_link = get_edit_post_link($post_id);

      echo '<tr>';
      echo '<td><a href="' . esc_url($edit_link) . '"><strong>' . esc_html(get_the_title()) . '</strong></a></td>';
      echo '<td>' . esc_html($open) . '</td>';
      echo '<td>' . esc_html($last_text) . '</td>';
      echo '</tr>';
    }
    wp_reset_postdata();
  } else {
    echo '<tr><td colspan="3">ไม่มีรายการ</td></tr>';
  }

  echo '</tbody></table>';
  echo '</div>';
}


/* =========================================================
 * [HELPER] check page url for header active menu
 * ========================================================= */
function nav_active($args = []) {

  // หน้าแรก
  if (!empty($args['home']) && is_front_page()) {
    return 'active';
  }

  // ✅ เฉพาะเมนู Next Learn เท่านั้น ถึงจะเช็ค nextlearn
  if (
    !empty($args['nextlearn']) &&
    (
      is_page('nextlearn') ||
      (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'nextlearn') !== false)
    )
  ) {
    return 'active';
  }

  // post type archive
  if (!empty($args['post_type']) && is_post_type_archive($args['post_type'])) {
    return 'active';
  }

  // single ของ post type
  if (!empty($args['post_type']) && is_singular($args['post_type'])) {
    return 'active';
  }

  // taxonomy ที่ผูกกับ post type นั้นจริง ๆ
  if (!empty($args['post_type']) && is_tax()) {
    $queried = get_queried_object();

    if ($queried && !is_wp_error($queried) && !empty($queried->taxonomy)) {
      $tax = get_taxonomy($queried->taxonomy);

      if ($tax && in_array($args['post_type'], (array) $tax->object_type, true)) {
        return 'active';
      }
    }
  }

  // page slug
  if (!empty($args['page']) && is_page($args['page'])) {
    return 'active';
  }

  // page template
  if (!empty($args['page_template']) && is_page_template($args['page_template'])) {
    return 'active';
  }

  return '';
}



/* =========================================================
 * [HELPER] Helper functions for Learning City Theme
 * ========================================================= */

// แปลง Hex เป็น RGBA
function hex_to_rgba($hex, $opacity = 1) {
    $hex = str_replace('#', '', (string)$hex);
    if (strlen($hex) === 3) {
        $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "rgba({$r}, {$g}, {$b}, {$opacity})";
}

// ดึงค่า ACF จาก Term (ถ้าไม่มีให้ไล่เช็ก Parent)
function get_term_acf_inherit($term, $field_name) {
    if (!$term) return null;
    $current = $term;
    while ($current && !is_wp_error($current)) {
        $value = get_field($field_name, $current);
        if (!empty($value)) return $value;
        if (empty($current->parent)) break;
        $current = get_term($current->parent, $current->taxonomy);
    }
    return null;
}

// include helper
require_once get_template_directory() . '/inc/course-helpers.php';

// สีของ Category
if (!function_exists('cc_lighten_hex')) {
  function cc_lighten_hex($hex, $percent = 0.85) {
    $hex = ltrim((string)$hex, '#');

    if (strlen($hex) === 3) {
      $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }

    if (strlen($hex) !== 6) return '#D6EBE0';

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = (int) round($r + (255 - $r) * $percent);
    $g = (int) round($g + (255 - $g) * $percent);
    $b = (int) round($b + (255 - $b) * $percent);

    return sprintf('#%02X%02X%02X', $r, $g, $b);
  }
}

// date to พศ.
function lc_thai_short_date($date_str) {
  if (empty($date_str)) return '';

  $ts = strtotime($date_str);
  if (!$ts) return $date_str;

  $months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];

  $d = (int) date('j', $ts);
  $m = (int) date('n', $ts);
  $y = (int) date('Y', $ts);

  $by2 = (int) substr((string)($y + 543), -2);

  return $d . ' ' . $months[$m] . ' ' . $by2;
}


/* =========================================================
 * [COURSE FILTER] Course Open Registration Filter (FAST)
 * ========================================================= */

function lc_date_to_ts($v) {
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

// get course_id from session ACF field "course"
function lc_get_course_id_from_session($session_id) {
  $course = get_field('course', $session_id, false);

  if (is_object($course) && isset($course->ID)) return (int) $course->ID;
  if (is_numeric($course)) return (int) $course;

  if (is_array($course) && !empty($course[0])) {
    $first = $course[0];
    if (is_object($first) && isset($first->ID)) return (int) $first->ID;
    if (is_numeric($first)) return (int) $first;
  }

  return 0;
}

function lc_is_session_open_for_reg($sid, $today_ts) {
  $reg_start = get_field('reg_start', $sid, false);
  $reg_end   = get_field('reg_end',   $sid, false);

  $start_ts = lc_date_to_ts($reg_start);
  $end_ts   = lc_date_to_ts($reg_end);

  if ($start_ts === 0 && $end_ts === 0) return true;
  if ($start_ts > 0 && $end_ts === 0) return $today_ts >= $start_ts;
  if ($start_ts === 0 && $end_ts > 0) return $today_ts <= $end_ts;

  return ($today_ts >= $start_ts && $today_ts <= $end_ts);
}

/**
 * Runtime guard for course cards on archive/tax pages.
 * Show course only when:
 * - at least one session is currently open for registration, OR
 * - at least one session has no reg_start/reg_end (treat as always open).
 */
function lc_course_should_show_in_archive($course_id) {
  $course_id = (int) $course_id;
  if (!$course_id) return false;

  static $cache = [];
  if (array_key_exists($course_id, $cache)) return $cache[$course_id];

  $session_ids = get_posts([
    'post_type'      => 'session',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => [
      'relation' => 'OR',
      [
        'key'     => 'course',
        'value'   => (string) $course_id,
        'compare' => '=',
      ],
      [
        'key'     => 'course',
        'value'   => '"' . (string) $course_id . '"',
        'compare' => 'LIKE',
      ],
    ],
  ]);

  if (empty($session_ids)) {
    // Support online courses that do not have sessions yet.
    $learning_link = trim((string) get_field('learning_link', $course_id));
    $is_online_without_sessions = ($learning_link !== '');
    $cache[$course_id] = $is_online_without_sessions;
    return $is_online_without_sessions;
  }

  $today_ts = strtotime(current_time('Y-m-d'));
  foreach ($session_ids as $sid) {
    $reg_start = trim((string) get_field('reg_start', $sid, false));
    $reg_end   = trim((string) get_field('reg_end',   $sid, false));

    if ($reg_start === '' && $reg_end === '') {
      $cache[$course_id] = true;
      return true;
    }

    if (lc_is_session_open_for_reg($sid, $today_ts)) {
      $cache[$course_id] = true;
      return true;
    }
  }

  $cache[$course_id] = false;
  return false;
}

function lc_recalc_course_flags($course_id) {
  $course_id = (int) $course_id;
  if (!$course_id) return;

  $session_ids = get_posts([
    'post_type'      => 'session',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => [
      'relation' => 'OR',
      [
        'key'     => 'course',
        'value'   => (string) $course_id,
        'compare' => '=',
      ],
      [
        'key'     => 'course',
        'value'   => '"' . (string) $course_id . '"',
        'compare' => 'LIKE',
      ],
    ],
  ]);

  $has_session = !empty($session_ids);
  update_post_meta($course_id, '_lc_has_session', $has_session ? 1 : 0);

  $open_reg = 0;
  $has_missing_reg_info = 0;
  if ($has_session) {
    $today_ts = strtotime(current_time('Y-m-d'));
    foreach ($session_ids as $sid) {
      $reg_start_raw = get_field('reg_start', $sid, false);
      $reg_end_raw   = get_field('reg_end',   $sid, false);
      $reg_start_txt = trim((string) $reg_start_raw);
      $reg_end_txt   = trim((string) $reg_end_raw);

      // If registration fields are not provided, treat as always open.
      if ($reg_start_txt === '' && $reg_end_txt === '') {
        $has_missing_reg_info = 1;
      }

      if (lc_is_session_open_for_reg($sid, $today_ts)) {
        $open_reg = 1;
        break;
      }
    }
  }
  update_post_meta($course_id, '_lc_open_reg', $open_reg);
  update_post_meta($course_id, '_lc_reg_missing', $has_missing_reg_info);
}

// save session -> recalc its course
add_action('save_post_session', function ($post_id, $post, $update) {
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

  if (get_post_status($post_id) !== 'publish') {
    $course_id = lc_get_course_id_from_session($post_id);
    if ($course_id) lc_recalc_course_flags($course_id);
    return;
  }

  $course_id = lc_get_course_id_from_session($post_id);
  if ($course_id) lc_recalc_course_flags($course_id);
}, 20, 3);

// trash/delete session -> recalc its course
function lc_recalc_from_session($session_id) {
  $course_id = lc_get_course_id_from_session($session_id);
  if ($course_id) lc_recalc_course_flags($course_id);
}
add_action('trashed_post', function ($post_id) {
  if (get_post_type($post_id) === 'session') lc_recalc_from_session($post_id);
}, 20);
add_action('before_delete_post', function ($post_id) {
  if (get_post_type($post_id) === 'session') lc_recalc_from_session($post_id);
}, 20);

// Daily cron refresh
add_action('init', function () {
  if (!wp_next_scheduled('lc_refresh_course_open_reg_daily')) {
    wp_schedule_event(time() + 60, 'daily', 'lc_refresh_course_open_reg_daily');
  }
});
add_action('lc_refresh_course_open_reg_daily', function () {
  global $wpdb;

  $course_ids = $wpdb->get_col("
    SELECT DISTINCT pm.meta_value
    FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
    WHERE pm.meta_key = 'course'
      AND p.post_type = 'session'
      AND p.post_status = 'publish'
      AND pm.meta_value REGEXP '^[0-9]+$'
  ");

  if (empty($course_ids)) return;

  foreach ($course_ids as $cid) {
    if (is_numeric($cid)) lc_recalc_course_flags((int) $cid);
  }
});

// Default filter on archives
add_action('pre_get_posts', function ($q) {
  if (is_admin() || !$q->is_main_query()) return;
  if (!empty($_GET['show_all'])) return;

  $is_course_context = (
    $q->is_post_type_archive('course') ||
    $q->is_tax('course_category') ||
    $q->is_tax('course_provider') ||
    $q->is_tax('skill-level') ||
    $q->is_tax('audience') ||
    $q->is_tag()
  );

  if (!$is_course_context) return;

  $q->set('post_type', 'course');

  $existing_mq = $q->get('meta_query');
  if (!is_array($existing_mq)) $existing_mq = [];

  // Show courses that are:
  // - open for registration, or
  // - sessions missing reg fields, or
  // - online courses with learning_link (no session needed).
  $open_filter = [
    'relation' => 'OR',
    [
      'key'     => '_lc_open_reg',
      'value'   => 1,
      'compare' => '=',
      'type'    => 'NUMERIC',
    ],
    [
      'key'     => '_lc_reg_missing',
      'value'   => 1,
      'compare' => '=',
      'type'    => 'NUMERIC',
    ],
    [
      'key'     => 'learning_link',
      'value'   => '',
      'compare' => '!=',
    ],
  ];

  if (!empty($existing_mq)) {
    $q->set('meta_query', [
      'relation' => 'AND',
      $existing_mq,
      $open_filter,
    ]);
  } else {
    $q->set('meta_query', $open_filter);
  }
}, 20);

add_action('init', function () {
  register_taxonomy_for_object_type('post_tag', 'course');
});

add_action('template_redirect', function () {
  if (!is_singular('location')) return;

  $pages = get_posts([
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-blm.php',
    'fields'         => 'ids',
  ]);

  $map_url = !empty($pages) ? get_permalink($pages[0]) : home_url('/');
  $target = add_query_arg(['place' => get_the_ID()], $map_url);

  wp_safe_redirect($target, 302);
  exit;
});


/* =========================================================
 * [FRONT] Hour Chart data (transient)
 * ========================================================= */
add_action('wp_enqueue_scripts', function () {

  wp_enqueue_script(
    'theme-app',
    get_template_directory_uri() . '/assets/scripts/scripts.js',
    [],
    null,
    true
  );

  $wanted = ['job', 'language', 'digital'];

  $cache_key = 'blc_course_hours_by_theme_v1';
  $data = get_transient($cache_key);

  if ($data === false) {
    $totals = array_fill_keys($wanted, 0);

    $ids = get_posts([
      'post_type'      => 'course',
      'post_status'    => 'publish',
      'posts_per_page' => -1,
      'fields'         => 'ids',
      'no_found_rows'  => true,
    ]);

    foreach ($ids as $id) {
      $hours = get_post_meta($id, 'total_attendance_hours', true);
      $hours = is_numeric($hours) ? (float)$hours : 0;
      if ($hours <= 0) continue;

      $slugs = wp_get_post_terms($id, 'key-theme', ['fields' => 'slugs']);
      if (is_wp_error($slugs) || empty($slugs)) continue;

      foreach ($wanted as $slug) {
        if (in_array($slug, $slugs, true)) {
          $totals[$slug] += $hours;
        }
      }
    }

    $job = (int)round($totals['job']);
    $language = (int)round($totals['language']);
    $digital = (int)round($totals['digital']);
    $total = $job + $language + $digital;

    $percent = [
      'job'      => $total ? round(($job / $total) * 100, 2) : 0,
      'language' => $total ? round(($language / $total) * 100, 2) : 0,
      'digital'  => $total ? round(($digital / $total) * 100, 2) : 0,
    ];

    $data = [
      'job'      => $job,
      'language' => $language,
      'digital'  => $digital,
      'total'    => $total,
      'target'   => 1000000,
      'percent'  => $percent,
    ];

    set_transient($cache_key, $data, 10 * MINUTE_IN_SECONDS);
  }

  wp_add_inline_script(
    'theme-app',
    'window.__BLC__ = ' . wp_json_encode(['chart' => $data]) . ';',
    'before'
  );
});


/* =========================================================
 * [ADMIN SESSION] แสดงข้อมูลคอร์สใต้ ACF field (AJAX)
 * - ใช้เฉพาะหน้า add/edit session
 * - ต้องมีไฟล์: /admin-config/session-course-provider.js
 * ========================================================= */
add_action('admin_enqueue_scripts', function ($hook) {

    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'session') return;

    $src = get_stylesheet_directory_uri() . '/admin-config/session-course-provider.js';

    wp_enqueue_script(
        'session-course-provider',
        $src,
        ['acf-input'],
        '2.0',
        true
    );

    wp_localize_script('session-course-provider', 'SCP', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('scp_nonce'),
    ]);
});

add_action('wp_ajax_scp_get_course_provider', function () {

    check_ajax_referer('scp_nonce', 'nonce');

    $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
    $no_data   = 'ไม่มีข้อมูล';

    $na = function ($v) use ($no_data) {
        if ($v === 0 || $v === '0') return '0';
        return (isset($v) && $v !== '' && $v !== null) ? $v : $no_data;
    };

    if (!$course_id) {
        wp_send_json_success([
            'html' => '<ul style="margin:0;padding-left:18px; list-style:disc;">
                <li>หน่วยงาน: '.$no_data.'</li>
                <li>รายละเอียด: '.$no_data.'</li>
                <li>ชั่วโมงเรียน: '.$no_data.'</li>
                <li>ราคา: '.$no_data.'</li>
                <li>ใบรับรอง: '.$no_data.'</li>
                <li>เหมาะสำหรับ: '.$no_data.'</li>
            </ul>'
        ]);
    }

    // หน่วยงาน (taxonomy)
    $terms = get_the_terms($course_id, 'course_provider');
    $provider = (!is_wp_error($terms) && !empty($terms))
        ? implode(', ', wp_list_pluck($terms, 'name'))
        : $no_data;

    // 1.1) เหมาะสำหรับ (taxonomy: audience)
    $aud_terms = get_the_terms($course_id, 'audience');
    $audience = (!is_wp_error($aud_terms) && !empty($aud_terms))
      ? implode(', ', wp_list_pluck($aud_terms, 'name'))
      : $no_data;


    // รายละเอียด
    $desc = $na(get_field('course_description', $course_id));
    $desc_safe = ($desc === $no_data) ? $desc : nl2br(esc_html($desc));

    // ชั่วโมงเรียน (นาที -> ชั่วโมง)
    $minutes = get_field('total_minutes', $course_id);

    if ($minutes === '' || $minutes === null) {
        // ไม่กรอกอะไรเลย
        $hours_text = $no_data;

    } elseif ((int)$minutes === 0) {
        // กรอก 0
        $hours_text = 'ตามรอบเรียน';

    } else {
        // มีนาที → แปลงเป็นชั่วโมง
        $hours = ((int)$minutes) / 60;
        $hours_text = rtrim(
            rtrim(number_format($hours, 2, '.', ''), '0'),
            '.'
        ) . ' ชั่วโมง';
    }



    // ราคา
    $price = get_field('price', $course_id);
    $price_text = ($price === '' || $price === null) ? $no_data : number_format((float)$price) . ' บาท';

    // ใบรับรอง
    $has_cert = get_field('has_certificate', $course_id);
    $cert_text = ($has_cert === '' || $has_cert === null) ? $no_data : ($has_cert ? 'มี' : 'ไม่มี');

    // ปุ่มไปหน้า course
    $course_url = get_permalink($course_id);
    $btn = $course_url
        ? '<div style="margin-top:8px;">
             <a class="button button-secondary" target="_blank" href="'.esc_url($course_url).'">อ่านรายละเอียดคอร์ส</a>
           </div>'
        : '';

    $html = '
    <ul style="margin:0;padding-left:18px; list-style:disc;">
        <li><strong>หน่วยงาน:</strong> '.esc_html($provider).'</li>
        <li><strong>รายละเอียด:</strong> '.$desc_safe.'</li>
        <li><strong>ชั่วโมงเรียน:</strong> '.esc_html($hours_text).'</li>
        <li><strong>ราคา:</strong> '.esc_html($price_text).'</li>
        <li><strong>ใบรับรอง:</strong> '.esc_html($cert_text).'</li>
        <li><strong>เหมาะสำหรับ:</strong> '.esc_html($audience).'</li>
    </ul>'.$btn;

    wp_send_json_success(['html' => $html]);
});

/* =========================================================
 * [FRONT] Nearby courses for NextLearn (by cached user location)
 * ========================================================= */
if (!function_exists('lc_parse_location_id_from_session')) {
    function lc_parse_location_id_from_session($session_id) {
        $location = function_exists('get_field') ? get_field('location', $session_id, false) : get_post_meta($session_id, 'location', true);
        if (is_object($location) && isset($location->ID)) return (int) $location->ID;
        if (is_numeric($location)) return (int) $location;
        if (is_array($location) && !empty($location[0])) {
            $first = $location[0];
            if (is_object($first) && isset($first->ID)) return (int) $first->ID;
            if (is_numeric($first)) return (int) $first;
        }
        return 0;
    }
}

if (!function_exists('lc_haversine_km')) {
    function lc_haversine_km($lat1, $lon1, $lat2, $lon2) {
        $to_rad = M_PI / 180;
        $d_lat = ($lat2 - $lat1) * $to_rad;
        $d_lon = ($lon2 - $lon1) * $to_rad;
        $a = sin($d_lat / 2) * sin($d_lat / 2)
            + cos($lat1 * $to_rad) * cos($lat2 * $to_rad)
            * sin($d_lon / 2) * sin($d_lon / 2);
        return 6371 * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}

add_action('wp_ajax_lc_get_nearby_courses', 'lc_get_nearby_courses_ajax');
add_action('wp_ajax_nopriv_lc_get_nearby_courses', 'lc_get_nearby_courses_ajax');
if (!function_exists('lc_get_nearby_courses_data')) {
    function lc_get_nearby_courses_data($lat, $lng, $limit = 6) {
        $lat = (float) $lat;
        $lng = (float) $lng;
        $limit = (int) $limit;
        if ($limit <= 0) $limit = 6;
        if ($limit > 12) $limit = 12;

        if (!is_numeric($lat) || !is_numeric($lng) || abs($lat) > 90 || abs($lng) > 180) {
            return new WP_Error('invalid_coords', 'invalid_coords', ['status' => 400]);
        }

        // Cache nearby result by rounded user coordinate to avoid heavy recalculation.
        $cache_key = 'lc_nearby_' . md5(sprintf('%.3f|%.3f|%d', $lat, $lng, $limit));
        $cached = get_transient($cache_key);
        if (is_array($cached) && isset($cached['courses'])) {
            return $cached;
        }

        $session_ids = get_posts([
            'post_type'      => 'session',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);
        if (empty($session_ids)) {
            return ['courses' => []];
        }

        $location_coord_cache = [];
        $best_by_course = [];
        $course_allowed_cache = [];

        $extract_related_id = static function ($raw) {
            if (is_object($raw) && isset($raw->ID)) return (int) $raw->ID;
            if (is_numeric($raw)) return (int) $raw;
            if (is_array($raw) && !empty($raw[0])) {
                $first = $raw[0];
                if (is_object($first) && isset($first->ID)) return (int) $first->ID;
                if (is_numeric($first)) return (int) $first;
            }
            return 0;
        };

        foreach ($session_ids as $sid) {
            // Read raw meta directly for speed (avoid ACF get_field in large loops).
            $course_raw = get_post_meta($sid, 'course', true);
            $course_id = $extract_related_id($course_raw);
            if ($course_id <= 0) continue;

            if (!array_key_exists($course_id, $course_allowed_cache)) {
                $is_course = (get_post_type($course_id) === 'course');
                $is_publish = (get_post_status($course_id) === 'publish');
                // Lightweight visibility guard: use precomputed flags instead of runtime session scans.
                $open_reg = (int) get_post_meta($course_id, '_lc_open_reg', true);
                $has_session = (int) get_post_meta($course_id, '_lc_has_session', true);
                $course_allowed_cache[$course_id] = ($is_course && $is_publish && ($open_reg === 1 || $has_session === 0));
            }
            if (!$course_allowed_cache[$course_id]) continue;

            $location_raw = get_post_meta($sid, 'location', true);
            $location_id = $extract_related_id($location_raw);
            if ($location_id <= 0 || get_post_type($location_id) !== 'location' || get_post_status($location_id) !== 'publish') continue;

            if (!array_key_exists($location_id, $location_coord_cache)) {
                $raw_lat = get_post_meta($location_id, 'latitude', true);
                $raw_lng = get_post_meta($location_id, 'longitude', true);
                $loc_lat = is_numeric($raw_lat) ? (float) $raw_lat : null;
                $loc_lng = is_numeric($raw_lng) ? (float) $raw_lng : null;
                if ($loc_lat === null || $loc_lng === null || abs($loc_lat) > 90 || abs($loc_lng) > 180) {
                    $location_coord_cache[$location_id] = null;
                } else {
                    $location_coord_cache[$location_id] = [$loc_lat, $loc_lng];
                }
            }

            if ($location_coord_cache[$location_id] === null) continue;
            [$loc_lat, $loc_lng] = $location_coord_cache[$location_id];
            $distance_km = lc_haversine_km($lat, $lng, $loc_lat, $loc_lng);

            if (!isset($best_by_course[$course_id]) || $distance_km < $best_by_course[$course_id]['distance_km']) {
                $best_by_course[$course_id] = [
                    'course_id' => $course_id,
                    'distance_km' => $distance_km,
                ];
            }
        }

        if (empty($best_by_course)) {
            return ['courses' => []];
        }

        $items = array_values($best_by_course);
        usort($items, function ($a, $b) {
            if ($a['distance_km'] === $b['distance_km']) return 0;
            return ($a['distance_km'] < $b['distance_km']) ? -1 : 1;
        });
        $items = array_slice($items, 0, $limit);

        $courses = [];
        foreach ($items as $item) {
            $course_id = (int) $item['course_id'];

            $cat_ctx = function_exists('course_get_primary_category_context')
                ? course_get_primary_category_context($course_id)
                : ['final_color' => '#00744B', 'primary' => null];
            $provider = function_exists('course_get_provider_context')
                ? course_get_provider_context($course_id)
                : ['name' => '', 'img_src' => ''];

            $courses[] = [
                'id' => $course_id,
                'title' => get_the_title($course_id),
                'permalink' => get_permalink($course_id),
                'thumb' => function_exists('course_get_thumb') ? course_get_thumb($course_id) : (get_the_post_thumbnail_url($course_id, 'medium') ?: ''),
                'primary_term_name' => isset($cat_ctx['primary']->name) ? (string) $cat_ctx['primary']->name : '',
                'final_color' => !empty($cat_ctx['final_color']) ? (string) $cat_ctx['final_color'] : '#00744B',
                'provider_name' => (string) ($provider['name'] ?? ''),
                'provider_logo_url' => (string) ($provider['img_src'] ?? ''),
                'duration_text' => function_exists('course_get_duration_text') ? (string) course_get_duration_text($course_id) : 'ตามรอบเรียน',
                'level_text' => function_exists('course_get_level_text') ? (string) course_get_level_text($course_id) : 'ไม่ระบุ',
                'audience_text' => function_exists('course_get_audience_text') ? (string) course_get_audience_text($course_id) : 'ทุกวัย',
                'distance_km' => round((float) $item['distance_km'], 1),
            ];
        }

        $result = ['courses' => $courses];
        set_transient($cache_key, $result, 10 * MINUTE_IN_SECONDS);
        return $result;
    }
}

function lc_get_nearby_courses_ajax() {
    $lat = isset($_POST['lat']) ? (float) $_POST['lat'] : null;
    $lng = isset($_POST['lng']) ? (float) $_POST['lng'] : null;
    $limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 6;
    if ($limit <= 0) $limit = 6;
    if ($limit > 12) $limit = 12;

    $result = lc_get_nearby_courses_data($lat, $lng, $limit);
    if (is_wp_error($result)) {
        $status = (int) $result->get_error_data('status');
        if ($status <= 0) $status = 400;
        wp_send_json_error(['message' => $result->get_error_message()], $status);
    }
    wp_send_json_success($result);
}

add_action('rest_api_init', function () {
    register_rest_route('learningcity/v1', '/nearby-courses', [
        'methods' => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $lat = (float) $request->get_param('lat');
            $lng = (float) $request->get_param('lng');
            $limit = (int) $request->get_param('limit');
            $result = lc_get_nearby_courses_data($lat, $lng, $limit);
            if (is_wp_error($result)) return $result;
            return rest_ensure_response($result);
        },
        'permission_callback' => '__return_true',
        'args' => [
            'lat' => ['required' => true],
            'lng' => ['required' => true],
            'limit' => ['required' => false],
        ],
    ]);
});

if (!function_exists('lc_clear_nearby_courses_cache')) {
    function lc_clear_nearby_courses_cache() {
        global $wpdb;
        if (!isset($wpdb->options)) return;
        $like = $wpdb->esc_like('_transient_lc_nearby_') . '%';
        $like_timeout = $wpdb->esc_like('_transient_timeout_lc_nearby_') . '%';
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $like, $like_timeout));
    }
}
add_action('save_post_course', 'lc_clear_nearby_courses_cache');
add_action('save_post_session', 'lc_clear_nearby_courses_cache');
add_action('save_post_location', 'lc_clear_nearby_courses_cache');
add_action('deleted_post', 'lc_clear_nearby_courses_cache');


/* =========================================================
 * [ADMIN SESSION] ตั้งชื่อจาก ACF Post Object: course - location
 * ========================================================= */
add_action('acf/save_post', 'set_session_title_from_course_location', 20);
function set_session_title_from_course_location($post_id) {

    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;
    if (get_post_type($post_id) !== 'session') return;

    $course   = get_field('course', $post_id);
    $location = get_field('location', $post_id);

    $course_id   = is_object($course) ? (int)$course->ID : (int)$course;
    $location_id = is_object($location) ? (int)$location->ID : (int)$location;

    if (!$course_id || !$location_id) return;

    $course_title   = get_the_title($course_id);
    $location_title = get_the_title($location_id);

    $new_title = trim($course_title . ' - ' . $location_title);

    remove_action('acf/save_post', 'set_session_title_from_course_location', 20);

    wp_update_post([
        'ID'         => $post_id,
        'post_title' => $new_title,
        'post_name'  => sanitize_title($new_title),
    ]);

    add_action('acf/save_post', 'set_session_title_from_course_location', 20);
}


/* =========================================================
 * [ADMIN SESSION] ซ่อน Title/Slug ในหลังบ้าน + ปิด session single
 * ========================================================= */
add_action('admin_head', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'session') return;

    echo '<style>
        #titlediv { display:none !important; }
        #edit-slug-box { display:none !important; }
    </style>';
});

add_action('template_redirect', function () {
    if (is_singular('session')) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        include get_404_template();
        exit;
    }
});


/* =========================================================
 * [ADMIN SESSION] Filter Location ในหน้า All Sessions (เร็ว + cache)
 * - แทนของเดิมที่หนัก (ดึง postmeta ทั้งก้อน)
 * ========================================================= */
function lc_get_used_location_ids_for_sessions() {
    $cache_key = 'lc_session_used_location_ids_v1';
    $ids = get_transient($cache_key);
    if ($ids !== false) return is_array($ids) ? $ids : [];

    global $wpdb;

    // NOTE: field name = location และเป็นตัวเลขเดี่ยว (Post Object)
    $ids = $wpdb->get_col("
        SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED) AS loc_id
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'location'
          AND p.post_type = 'session'
          AND p.post_status IN ('publish','draft','private')
          AND pm.meta_value REGEXP '^[0-9]+$'
          AND pm.meta_value <> ''
    ");

    $ids = array_values(array_unique(array_filter(array_map('intval', (array)$ids))));
    set_transient($cache_key, $ids, 10 * MINUTE_IN_SECONDS);

    return $ids;
}

function lc_invalidate_session_location_cache() {
    delete_transient('lc_session_used_location_ids_v1');
}

add_action('save_post_session', function ($post_id) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    lc_invalidate_session_location_cache();
}, 20);

add_action('trashed_post', function ($post_id) {
    if (get_post_type($post_id) === 'session') lc_invalidate_session_location_cache();
}, 20);

add_action('before_delete_post', function ($post_id) {
    if (get_post_type($post_id) === 'session') lc_invalidate_session_location_cache();
}, 20);

add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'session') return;

    $query_var = 'session_location_filter';
    $selected  = isset($_GET[$query_var]) ? (int) $_GET[$query_var] : 0;

    $ids = lc_get_used_location_ids_for_sessions();

    echo '<select name="' . esc_attr($query_var) . '">';
    echo '<option value="0">All Locations</option>';

    if (!empty($ids)) {
        $locations = get_posts([
            'post_type'              => 'location',
            'post__in'               => $ids,
            'posts_per_page'         => -1,
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        foreach ($locations as $loc) {
            printf(
                '<option value="%d" %s>%s</option>',
                (int) $loc->ID,
                selected($selected, (int) $loc->ID, false),
                esc_html($loc->post_title)
            );
        }
    }

    echo '</select>';
});

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    global $pagenow;
    if ($pagenow !== 'edit.php') return;

    if ($query->get('post_type') !== 'session') return;

    $query_var = 'session_location_filter';
    if (empty($_GET[$query_var])) return;

    $location_id = (int) $_GET[$query_var];

    $meta_query = $query->get('meta_query');
    if (!is_array($meta_query)) $meta_query = [];

    if (!isset($meta_query['relation'])) {
        $meta_query = array_merge(['relation' => 'AND'], $meta_query);
    }

    // Post Object ตัวเดียว -> '=' เร็วกว่า LIKE
    $meta_query[] = [
        'key'     => 'location',
        'value'   => $location_id,
        'compare' => '=',
        'type'    => 'NUMERIC',
    ];

    $query->set('meta_query', $meta_query);

}, 99999);

/* =========================================================
 * [ADMIN SESSION] Preview/View -> Course permalink (เร็ว)
 * - ใช้ get_post_meta ลดโหลด ACF ตอน list
 * ========================================================= */
add_filter('preview_post_link', function ($preview_link, $post) {
    if (!$post || $post->post_type !== 'session') return $preview_link;

    $course_id = (int) get_post_meta($post->ID, 'course', true);
    if ($course_id) return get_permalink($course_id);

    return $preview_link;
}, 10, 2);

add_filter('post_row_actions', function ($actions, $post) {
    if (!$post || $post->post_type !== 'session') return $actions;

    $course_id = (int) get_post_meta($post->ID, 'course', true);
    if (!$course_id) return $actions;

    $course_link = get_permalink($course_id);
    if ($course_link) {
        $actions['view'] = '<a href="' . esc_url($course_link) . '" rel="bookmark" target="_blank">View</a>';
    }

    return $actions;
}, 10, 2);


/* =========================================================
 * [ADMIN] แก้ไขล่าสุดโดย + วันเวลา (course/location/session)
 * ========================================================= */
add_action('init', function () {

    $post_types = ['course', 'location', 'session'];

    // เก็บ user ที่แก้ล่าสุด
    foreach ($post_types as $pt) {
        add_action("save_post_{$pt}", function ($post_id) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
            if (!current_user_can('edit_post', $post_id)) return;

            update_post_meta($post_id, '_last_updated_by', get_current_user_id());
        }, 10);
    }

    // คอลัมน์ในหน้า list
    foreach ($post_types as $pt) {
        add_filter("manage_{$pt}_posts_columns", function ($columns) {
            $columns['last_updated'] = 'อัปเดตล่าสุด';
            return $columns;
        });

        add_action("manage_{$pt}_posts_custom_column", function ($column, $post_id) {
            if ($column !== 'last_updated') return;

            $uid = (int) get_post_meta($post_id, '_last_updated_by', true);
            if (!$uid) { echo 'ไม่มีข้อมูล'; return; }

            $user = get_userdata($uid);
            $name = $user ? $user->display_name : 'ไม่ทราบผู้ใช้';

            $date = get_post_modified_time('d/m/Y', false, $post_id);
            $time = get_post_modified_time('H:i', false, $post_id);

            echo esc_html($name) . '<br>';
            echo '<small style="color:#666;">' . esc_html($date . ' ' . $time) . '</small>';
        }, 10, 2);
    }

    // Meta box ในหน้า edit
    foreach ($post_types as $pt) {
        add_action("add_meta_boxes_{$pt}", function () use ($pt) {

            add_meta_box(
                "{$pt}_last_updated_info",
                'การอัปเดตล่าสุด',
                function ($post) {

                    $uid = (int) get_post_meta($post->ID, '_last_updated_by', true);
                    if (!$uid) {
                        echo '<p>ยังไม่มีข้อมูลการอัปเดต</p>';
                        return;
                    }

                    $user = get_userdata($uid);
                    $name = $user ? $user->display_name : 'ไม่ทราบผู้ใช้';

                    $date = get_post_modified_time('d/m/Y', false, $post);
                    $time = get_post_modified_time('H:i', false, $post);

                    echo '<p><strong>อัปเดตโดย:</strong> ' . esc_html($name) . '</p>';
                    echo '<p><strong>วันที่:</strong> ' . esc_html($date) . '</p>';
                    echo '<p><strong>เวลา:</strong> ' . esc_html($time) . '</p>';
                },
                $pt,
                'side',
                'high'
            );
        });
    }

}, 20);
