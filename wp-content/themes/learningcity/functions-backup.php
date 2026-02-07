<?php

require_once 'inc/constants.php';

/**
 * Enqueue library styles and scripts properly
 */
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



if (file_exists(VITE_THEME_MANIFEST_PATH)) {
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
        if (in_array($handle, $scriptHandles)) {
            return str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }, 10, 2);
} else {
    require_once 'inc/vite.php';
}

require_once 'inc/init/setup-wordpress.php';

// Admin
require_once 'inc/admin/admin-style.php';
require_once 'inc/admin/admin-class.php';
require_once 'inc/admin/admin-scripts.php';
require_once 'inc/admin/hide-menu-admin.php';
require_once 'inc/admin/button-edit.php';
require_once 'inc/admin/disabled-post.php';
require_once 'inc/admin/acf-tabs-name.php';
require_once 'inc/admin/acf-preview-styles.php';
require_once 'inc/admin/acf-flexible-keyboard.php';





// /** 

// ปุ่มที่ใช้ SYNC


//  * 1. ฟังก์ชันหัวใจ (Logic) - คุณยืนยันว่าส่วนนี้ทำงานได้ถูกต้อง
//  * ทำหน้าที่หา Post ID จาก UID และบันทึกลง ACF Post Object
//  */
// function sync_session_relationships( $value, $post_id, $field, $original ) {
//     if ( get_post_type($post_id) !== 'session' ) return $value;

//     if ( !empty($value) ) {
//         // ค้นหาเป้าหมาย (Course หรือ Location) จาก UID
//         $target_post_type = ($field['name'] == 'course_uid_raw') ? 'course' : 'location';
//         $target_meta_key  = ($field['name'] == 'course_uid_raw') ? 'course_uid' : 'location_id';

//         $args = array(
//             'post_type'      => $target_post_type,
//             'meta_query'     => array(
//                 array('key' => $target_meta_key, 'value' => $value)
//             ),
//             'posts_per_page' => 1,
//             'fields'         => 'ids',
//             'no_found_rows'  => true, // เพิ่มความเร็ว
//         );

//         $related_posts = get_posts($args);

//         if ( !empty($related_posts) ) {
//             $related_id = $related_posts[0];
//             $target_acf_field = ($field['name'] == 'course_uid_raw') ? 'course' : 'location';
            
//             // อัปเดตฟิลด์ ACF Post Object
//             update_field($target_acf_field, $related_id, $post_id);
//         }
//     }
//     return $value;
// }

// // ผูก Hook กับฟิลด์ RAW UID ทั้งสองตัวเพื่อให้ทำงานตอนกด Update มือด้วย
// add_filter('acf/update_value/name=course_uid_raw', 'sync_session_relationships', 10, 4);
// add_filter('acf/update_value/name=location_id_raw', 'sync_session_relationships', 10, 4);


// /**
//  * 2. ระบบปุ่มกดและ Batch Processing (Force Update)
//  */

// // สร้างปุ่มและปุ่มล้างแคชในหน้า Admin
// add_action('restrict_manage_posts', function($post_type) {
//     if ('session' === $post_type) {
//         $sync_url = add_query_arg('action', 'start_bulk_sync');
//         $reset_url = add_query_arg('force_reset_sync', '1');
        
//         echo '<a href="' . esc_url($sync_url) . '" class="button button-primary" style="background-color: #0073aa; margin-right:5px;">🔄 เริ่ม Sync ข้อมูลทั้งหมด (Auto)</a>';
//         echo '<a href="' . esc_url($reset_url) . '" class="button" onclick="return confirm(\'ต้องการล้างความจำเพื่อเริ่มใหม่ใช่หรือไม่?\')" style=" margin-right:5px;">⚠️ ล้างคิวการรัน</a>';
//     }
// });

// // Logic การประมวลผล
// add_action('admin_init', function() {
//     // ระบบล้างคิว (กรณีขึ้นว่าเสร็จแล้วแต่ยังไม่ครบ)
//     if (isset($_GET['force_reset_sync'])) {
//         delete_transient('synced_session_ids_batch');
//         wp_redirect(remove_query_arg(['force_reset_sync', 'sync_finished']));
//         exit;
//     }

//     if (isset($_GET['action']) && $_GET['action'] === 'start_bulk_sync') {
        
//         // ดึง ID ที่ทำไปแล้วในรอบนี้
//         $processed_ids = get_transient('synced_session_ids_batch') ?: [];
        
//         // ดึง Session 100 อันที่ยังไม่ได้ทำในรอบนี้
//         $sessions = get_posts([
//             'post_type'      => 'session',
//             'posts_per_page' => 100, 
//             'post_status'    => 'any',
//             'post__not_in'   => $processed_ids,
//             'cache_results'  => false, // ห้ามใช้แคช
//         ]);

//         if (empty($sessions)) {
//             delete_transient('synced_session_ids_batch');
//             wp_redirect(add_query_arg(['post_type' => 'session', 'sync_finished' => 1], admin_url('edit.php')));
//             exit;
//         }

//         foreach ($sessions as $session) {
//             $pid = $session->ID;
            
//             // ล้างแคชของโพสต์นี้อย่างหนัก
//             clean_post_cache($pid);
//             wp_cache_delete($pid, 'post_meta');

//             // ดึง UID ล่าสุดจาก DB
//             $c_uid = get_post_meta($pid, 'course_uid_raw', true);
//             $l_id  = get_post_meta($pid, 'location_id_raw', true);

//             // บังคับรันฟังก์ชัน Sync
//             if ($c_uid) {
//                 sync_session_relationships($c_uid, $pid, ['name' => 'course_uid_raw'], $c_uid);
//             }
//             if ($l_id) {
//                 sync_session_relationships($l_id, $pid, ['name' => 'location_id_raw'], $l_id);
//             }

//             // บังคับให้ WordPress รับรู้การเปลี่ยนแปลง (Force Save)
//             wp_update_post(['ID' => $pid]); 

//             $processed_ids[] = $pid;
//         }

//         // บันทึกคิว
//         set_transient('synced_session_ids_batch', $processed_ids, HOUR_IN_SECONDS);

//         // หน้าจอสถานะและการรีเฟรช
//         $done = count($processed_ids);
//         $next_url = add_query_arg('action', 'start_bulk_sync');
//         echo "<div style='font-family:sans-serif; padding:20px; text-align:center;'>";
//         echo "<h2>🚀 กำลังเชื่อมข้อมูลชุดใหม่...</h2>";
//         echo "<p style='font-size:18px;'>ประมวลผลไปแล้ว <b>$done</b> รายการ</p>";
//         echo "<p>ห้ามปิดหน้าจอนี้ ระบบจะรีเฟรชตัวเองไปเรื่อยๆ...</p>";
//         echo "</div>";
//         echo "<script>location.href='$next_url';</script>";
//         exit;
//     }
// });

// // แจ้งเตือนเมื่อเสร็จ
// add_action('admin_notices', function() {
//     if (isset($_GET['sync_finished'])) {
//         echo '<div class="notice notice-success is-dismissible"><p>✅ <b>Sync สำเร็จ!</b> ข้อมูล Course และ Location ถูกอัปเดตใหม่เรียบร้อยแล้ว</p></div>';
//     }
// });


// AJAX Modal Course

add_action('wp_enqueue_scripts', function () {
  wp_enqueue_script(
    'course-modal-ajax',
    get_template_directory_uri() . '/assets/scripts/modules/course-modal-ajax.js',
    [], // ถ้าใช้ jQuery ให้เปลี่ยนเป็น ['jquery']
    null,
    true
  );

wp_localize_script('course-modal-ajax', 'COURSE_MODAL', [
  'ajax_url'     => admin_url('admin-ajax.php'),
  'nonce'        => wp_create_nonce('course_modal_nonce'),
  'archive_url'  => get_post_type_archive_link('course'), // ✅ เพิ่มอันนี้
]);
});

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

  ob_start();
  global $post;
  $post = $course_post;
  setup_postdata($post);

  include locate_template('template-parts/components/modal-course-ajax.php');

  wp_reset_postdata();
  $html = ob_get_clean();

  wp_send_json_success([
    'html'      => $html,
    'permalink' => get_permalink($course_id),
    'title'     => get_the_title($course_id),
  ]);
}


add_action('wp_enqueue_scripts', function () {

  // ✅ ทำให้ค่า config เป็น global ใช้ได้กับทุกไฟล์ (ทั้ง Vite bundle และไฟล์แยก)
  $data = [
    'ajax_url'    => admin_url('admin-ajax.php'),
    'nonce'       => wp_create_nonce('course_modal_nonce'),
    'archive_url' => get_post_type_archive_link('course'),
  ];

  wp_register_script('course-modal-config', '', [], null, true);
  wp_enqueue_script('course-modal-config');
  wp_add_inline_script(
    'course-modal-config',
    'window.COURSE_MODAL = ' . wp_json_encode($data) . ';',
    'before'
  );

});



// check page url for header active menu

function nav_active($args = []) {

  // หน้าแรก
  if (!empty($args['home']) && is_front_page()) {
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

  return '';
}




/**
 * Helper functions for Learning City Theme
 */

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

  // รองรับทั้ง 'Y-m-d' และแบบที่ strtotime เข้าใจได้
  $ts = strtotime($date_str);
  if (!$ts) return $date_str;

  $months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];

  $d = (int) date('j', $ts);
  $m = (int) date('n', $ts);
  $y = (int) date('Y', $ts);

  // ปี พ.ศ. 2 หลัก (เช่น 2026 -> 2569 -> "69")
  $by2 = (int) substr((string)($y + 543), -2);

  return $d . ' ' . $months[$m] . ' ' . $by2;
}


// กรองเฉาะคอร์สที่เปิดอยู่

/**
 * LearningCity - Course Open Registration Filter (FAST)
 * - session -> course : ACF Post Object
 * - session reg_start/reg_end : ACF Date (raw)
 * - show course if:
 *   (A) no session at all
 *   OR
 *   (B) has at least one session open for registration
 *
 * It stores flags on course:
 *  - _lc_has_session (0/1)
 *  - _lc_open_reg (0/1)
 */

/* -----------------------------
 * Helpers
 * ----------------------------- */

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

  // Y-m-d หรือ format ที่ strtotime เข้าใจได้
  $ts = strtotime($v);
  return $ts ?: 0;
}

/**
 * get course_id from session ACF field "course"
 * supports: post object / ID / array
 */
function lc_get_course_id_from_session($session_id) {
  // raw value
  $course = get_field('course', $session_id, false);

  if (is_object($course) && isset($course->ID)) return (int) $course->ID;
  if (is_numeric($course)) return (int) $course;

  // in case relationship returns array
  if (is_array($course) && !empty($course[0])) {
    $first = $course[0];
    if (is_object($first) && isset($first->ID)) return (int) $first->ID;
    if (is_numeric($first)) return (int) $first;
  }

  return 0;
}

/**
 * check if a session is open for registration (or always open)
 */
function lc_is_session_open_for_reg($sid, $today_ts) {
  // ✅ raw value (ACF date picker often raw = Ymd)
  $reg_start = get_field('reg_start', $sid, false);
  $reg_end   = get_field('reg_end',   $sid, false);

  $start_ts = lc_date_to_ts($reg_start);
  $end_ts   = lc_date_to_ts($reg_end);

  // always open: no dates
  if ($start_ts === 0 && $end_ts === 0) return true;

  // only start
  if ($start_ts > 0 && $end_ts === 0) return $today_ts >= $start_ts;

  // only end
  if ($start_ts === 0 && $end_ts > 0) return $today_ts <= $end_ts;

  // both
  return ($today_ts >= $start_ts && $today_ts <= $end_ts);
}

/* -----------------------------
 * Recalc course flags
 * ----------------------------- */

function lc_recalc_course_flags($course_id) {
  $course_id = (int) $course_id;
  if (!$course_id) return;

  // find sessions referencing this course in meta "course"
  // supports scalar and serialized/array by LIKE
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
  if ($has_session) {
    $today_ts = strtotime(current_time('Y-m-d'));
    foreach ($session_ids as $sid) {
      if (lc_is_session_open_for_reg($sid, $today_ts)) {
        $open_reg = 1;
        break;
      }
    }
  }
  update_post_meta($course_id, '_lc_open_reg', $open_reg);
}

/* -----------------------------
 * Hooks: update flags on session changes
 * ----------------------------- */

// save session -> recalc its course
add_action('save_post_session', function ($post_id, $post, $update) {
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

  // status change also matters; handle publish only
  if (get_post_status($post_id) !== 'publish') {
    // if it became non-publish, we still should recalc its linked course
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

/* -----------------------------
 * Daily cron refresh (date changes)
 * ----------------------------- */

add_action('init', function () {
  if (!wp_next_scheduled('lc_refresh_course_open_reg_daily')) {
    wp_schedule_event(time() + 60, 'daily', 'lc_refresh_course_open_reg_daily');
  }
});

add_action('lc_refresh_course_open_reg_daily', function () {
  global $wpdb;

  // only numeric course ids
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

/* -----------------------------
 * Default filter on archives (FAST query)
 * ----------------------------- */

add_action('pre_get_posts', function ($q) {
  if (is_admin() || !$q->is_main_query()) return;

  // allow bypass with ?show_all=1
  if (!empty($_GET['show_all'])) return;

  // apply to course contexts
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

  // keep existing meta_query if any; merge as AND
  $existing_mq = $q->get('meta_query');
  if (!is_array($existing_mq)) $existing_mq = [];

  $open_filter = [
    'relation' => 'OR',
    [ 'key' => '_lc_open_reg',    'value' => 1, 'compare' => '=' ], // has open/always-open session
    [ 'key' => '_lc_has_session', 'value' => 0, 'compare' => '=' ], // no session at all
  ];

  // merge safely
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

// NOTE: If migrate DB or import sessions massively,
// this URL /?lc_recalc=1

// add_action('init', function () {
//   if (!current_user_can('manage_options')) return;
//   if (empty($_GET['lc_recalc'])) return;

//   $course_ids = get_posts([
//     'post_type'      => 'course',
//     'post_status'    => 'publish',
//     'posts_per_page' => -1,
//     'fields'         => 'ids',
//   ]);

//   foreach ($course_ids as $cid) {
//     lc_recalc_course_flags($cid);
//   }

//   wp_die('LC recalc done. Remove this block now.');
// });
/////

// ยิงค่าใน Hour Chart
add_action('wp_enqueue_scripts', function () {
  // ✅ เปลี่ยน path ให้ตรงกับไฟล์ build ของ Vite ของคุณ
  wp_enqueue_script(
    'theme-app',
    get_template_directory_uri() . '/assets/scripts/scripts.js',
    [],
    null,
    true
  );

  $wanted = ['job', 'language', 'digital'];

  // แคชกันช้า (10 นาที)
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

      // ✅ นับชั่วโมงให้ทุก theme ที่ติดอยู่ (ถ้าติดหลายอันจะถูกนับซ้ำ)
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
      'total'    => $total,      // ✅ รวมเรียนไปแล้วทั้งหมด (เอาไว้เป็นค่าเต็มของ bar)
      'target'   => 1000000,     // ✅ เป้าหมาย 1,000,000 (ไว้ใช้อีกจุดถ้าต้องการ)
      'percent'  => $percent,    // ✅ % สำหรับ progress bar
    ];

    set_transient($cache_key, $data, 10 * MINUTE_IN_SECONDS);
  }

  // ✅ ส่งให้ JS
  wp_add_inline_script(
    'theme-app',
    'window.__BLC__ = ' . wp_json_encode(['chart' => $data]) . ';',
    'before'
  );
});


// ใส่ข้อมูลใน session single โดยดึงจาก course


/**
 * 1) Enqueue JS เฉพาะหน้า edit / add session
 */
add_action('admin_enqueue_scripts', function ($hook) {

    // ใช้เฉพาะหน้าเพิ่ม / แก้ไข post
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'session') {
        return;
    }

    // แก้ path ตรงนี้ให้ตรงกับที่คุณวางไฟล์
    $src = get_stylesheet_directory_uri() . '/admin-config/session-course-provider.js';

    wp_enqueue_script(
        'session-course-provider',
        $src,
        ['acf-input'],
        '1.0',
        true
    );

    wp_localize_script('session-course-provider', 'SCP', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('scp_nonce'),
    ]);
});


/**
 * 2) AJAX: ดึงข้อมูล course มาแสดง
 */
add_action('wp_ajax_scp_get_course_provider', function () {

    check_ajax_referer('scp_nonce', 'nonce');

    $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;

    // helper
    $no_data = 'ไม่มีข้อมูล';

    if (!$course_id) {
        wp_send_json_success([
            'html' => '<ul>
                <li>หน่วยงาน: '.$no_data.'</li>
                <li>รายละเอียด: '.$no_data.'</li>
                <li>ชั่วโมงเรียน: '.$no_data.'</li>
                <li>ราคา: '.$no_data.'</li>
                <li>ใบรับรอง: '.$no_data.'</li>
            </ul>'
        ]);
    }

    // 1) หน่วยงาน (taxonomy)
    $terms = get_the_terms($course_id, 'course_provider');
    $provider = (!is_wp_error($terms) && !empty($terms))
        ? implode(', ', wp_list_pluck($terms, 'name'))
        : $no_data;

    // 2) รายละเอียด
    $desc = get_field('course_description', $course_id);
    $desc = $desc ? nl2br(esc_html($desc)) : $no_data;

    // 3) ชั่วโมงเรียน (นาที → ชั่วโมง)
    $minutes = get_field('total_minutes', $course_id);
    if ($minutes !== '' && $minutes !== null) {
        $hours = rtrim(rtrim(number_format(((int)$minutes / 60), 2), '0'), '.');
        $hours_text = $hours . ' ชั่วโมง';
    } else {
        $hours_text = $no_data;
    }

    // 4) ราคา
    $price = get_field('price', $course_id);
    $price_text = ($price !== '' && $price !== null)
        ? number_format((float)$price) . ' บาท'
        : $no_data;

    // 5) ใบรับรอง
    $has_cert = get_field('has_certificate', $course_id);
    $cert_text = ($has_cert === null || $has_cert === '')
        ? $no_data
        : ($has_cert ? 'มี' : 'ไม่มี');

    // HTML output
    $html = '
    <ul style="margin:0;padding-left:18px;">
        <li><strong>หน่วยงาน:</strong> '.esc_html($provider).'</li>
        <li><strong>รายละเอียด:</strong> '.$desc.'</li>
        <li><strong>ชั่วโมงเรียน:</strong> '.esc_html($hours_text).'</li>
        <li><strong>ราคา:</strong> '.esc_html($price_text).'</li>
        <li><strong>ใบรับรอง:</strong> '.esc_html($cert_text).'</li>
    </ul>';

    wp_send_json_success(['html' => $html]);
});


// ตั้งชื่อจาก ACF Post Object: course - location

add_action('acf/save_post', 'set_session_title_from_course_location', 20);
function set_session_title_from_course_location($post_id) {

    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;
    if (get_post_type($post_id) !== 'session') return;

    $course   = get_field('course', $post_id);    // post object (WP_Post หรือ ID)
    $location = get_field('location', $post_id);  // post object (WP_Post หรือ ID)

    // แปลงให้เป็น post ID
    $course_id   = is_object($course) ? $course->ID : (int) $course;
    $location_id = is_object($location) ? $location->ID : (int) $location;

    if (!$course_id || !$location_id) return;

    $course_title   = get_the_title($course_id);
    $location_title = get_the_title($location_id);

    $new_title = trim($course_title . ' - ' . $location_title);

    // กัน loop ซ้อนจาก wp_update_post
    remove_action('acf/save_post', 'set_session_title_from_course_location', 20);

    wp_update_post([
        'ID'         => $post_id,
        'post_title' => $new_title,
        'post_name'  => sanitize_title($new_title),
    ]);

    add_action('acf/save_post', 'set_session_title_from_course_location', 20);
}


// ซ่อน/ปิดไม่ให้แก้ Page Title และ Permalink (Slug) เฉพาะ session

add_action('admin_head', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'session') return;

    echo '<style>
        /* ซ่อนช่อง Title */
        #titlediv { display:none !important; }

        /* ซ่อน permalink + ปุ่ม Edit ใต้ title (ถ้ายังมีส่วนขึ้นมา) */
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


/// เพิ่ม Filter Location ใน session

/**
 * SESSION: Location filter (ACF post object) for admin list
 * - Dropdown shows only locations used by session posts
 * - Filter works with Admin Columns Pro (ACP) by running very late
 */

add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'session') return;

    global $wpdb;

    $query_var          = 'session_location_filter'; // อย่าใช้ "location" กันชน ACP
    $acf_key            = 'location';                // <<< ACF FIELD NAME จริง
    $location_post_type = 'location';                // <<< post type ของ Location จริง

    $selected = isset($_GET[$query_var]) ? (int) $_GET[$query_var] : 0;

    // ดึงค่า meta_value ของ field location จาก session ทั้งหมด
    $raw_values = $wpdb->get_col($wpdb->prepare("
        SELECT pm.meta_value
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s
          AND p.post_type = 'session'
          AND p.post_status IN ('publish','draft','private')
          AND pm.meta_value <> ''
    ", $acf_key));

    // แปลง meta_value -> list ของ location IDs (รองรับ single/multiple)
    $ids = [];
    foreach ($raw_values as $v) {
        $maybe = maybe_unserialize($v);
        if (is_array($maybe)) {
            foreach ($maybe as $id) $ids[] = (int) $id;
        } else {
            $ids[] = (int) $v;
        }
    }
    $ids = array_values(array_unique(array_filter($ids)));

    // ถ้าไม่มีเลย ก็ยังโชว์ dropdown ไว้ได้ (แต่จะมีแค่ All)
    echo '<select name="' . esc_attr($query_var) . '">';
    echo '<option value="0">All Locations</option>';

    if (!empty($ids)) {
        $locations = get_posts([
            'post_type'      => $location_post_type,
            'post__in'       => $ids,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
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

    // เฉพาะหน้า list ของ session
    if ($query->get('post_type') !== 'session') return;

    $query_var = 'session_location_filter'; // ต้องตรงกับ dropdown
    $acf_key   = 'location';                // ต้องตรงกับ ACF field name จริง

    if (empty($_GET[$query_var])) return;

    $location_id = (int) $_GET[$query_var];

    // เอา meta_query เดิม (ของ ACP) มา merge แล้วค่อยเติมของเรา
    $meta_query = $query->get('meta_query');
    if (!is_array($meta_query)) $meta_query = [];

    // ensure relation AND
    if (!isset($meta_query['relation'])) {
        $meta_query = array_merge(['relation' => 'AND'], $meta_query);
    }

    // รองรับทั้ง single (meta_value = 80) และ multiple (serialized array มี "80")
    $meta_query[] = [
        'relation' => 'OR',
        [
            'key'     => $acf_key,
            'value'   => $location_id,
            'compare' => '=',
            'type'    => 'NUMERIC',
        ],
        [
            'key'     => $acf_key,
            'value'   => '"' . $location_id . '"',
            'compare' => 'LIKE',
        ],
    ];

    $query->set('meta_query', $meta_query);

}, 99999); // สำคัญ: รันช้ากว่า ACP เพื่อไม่ให้โดนทับ


/**
 * ให้ปุ่ม Preview ของ post type session ไปที่หน้า course single ที่เลือกจาก ACF post object (field name = course)
 */
add_filter('preview_post_link', function ($preview_link, $post) {

    if (!$post || $post->post_type !== 'session') {
        return $preview_link;
    }

    // ACF field 'course' อาจ return เป็น ID หรือ object
    $course = get_field('course', $post->ID);
    if (is_array($course) && isset($course['ID'])) {
        $course_id = (int) $course['ID'];
    } elseif (is_object($course) && isset($course->ID)) {
        $course_id = (int) $course->ID;
    } else {
        $course_id = (int) $course;
    }

    if ($course_id) {
        // พาไปหน้า course เลย (จะเป็น draft/publish ก็ได้ แต่ถ้า draft อาจเปิดไม่เห็นจากหน้าบ้าน)
        return get_permalink($course_id);
    }

    return $preview_link;

}, 10, 2);


// เปลี่ยน Permalink ของ ปุ่ม view ใน session ให้เป็น course

add_filter('post_row_actions', function ($actions, $post) {

    // ทำเฉพาะ post type session
    if (!$post || $post->post_type !== 'session') {
        return $actions;
    }

    // ดึง course จาก ACF post object field ชื่อ "course"
    $course = get_field('course', $post->ID);

    // รองรับทั้ง return เป็น ID / Object / Array
    if (is_object($course) && isset($course->ID)) {
        $course_id = (int) $course->ID;
    } elseif (is_array($course) && isset($course['ID'])) {
        $course_id = (int) $course['ID'];
    } else {
        $course_id = (int) $course;
    }

    if (!$course_id) {
        return $actions; // ถ้าไม่ได้เลือก course ก็ปล่อยเหมือนเดิม
    }

    $course_link = get_permalink($course_id);
    if (!$course_link) {
        return $actions;
    }

    // เปลี่ยนลิงก์ "view" ให้ไป course
    if (isset($actions['view'])) {
        // รักษาหน้าตาเดิม แต่เปลี่ยน href
        $actions['view'] = '<a href="' . esc_url($course_link) . '" rel="bookmark" target="_blank">View</a>';
    } else {
        // บางหน้าหรือบางสถานะอาจไม่มี view ก็เพิ่มให้
        $actions['view'] = '<a href="' . esc_url($course_link) . '" rel="bookmark" target="_blank">View</a>';
    }

    return $actions;

}, 10, 2);


// แก้ไขล่าสุดโดย

/**
 * Last updated by + date/time for: course, location, session
 */

add_action('init', function () {

    $post_types = ['course', 'location', 'session'];

    // 1) บันทึกคนอัปเดตล่าสุดทุกครั้งที่กด Update (ต่อ post type)
    foreach ($post_types as $pt) {
        add_action("save_post_{$pt}", function ($post_id, $post, $update) use ($pt) {

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            if (wp_is_post_revision($post_id)) return;
            if (!current_user_can('edit_post', $post_id)) return;

            update_post_meta($post_id, '_last_updated_by', get_current_user_id());
        }, 10, 3);
    }

    // 2) เพิ่มคอลัมน์ในหน้ารายการ (All Posts) ของแต่ละ post type
    foreach ($post_types as $pt) {
        add_filter("manage_{$pt}_posts_columns", function ($columns) {
            $columns['last_updated'] = 'อัปเดตล่าสุด';
            return $columns;
        });

        add_action("manage_{$pt}_posts_custom_column", function ($column, $post_id) {
            if ($column !== 'last_updated') return;

            $uid = (int) get_post_meta($post_id, '_last_updated_by', true);
            if (!$uid) {
                echo 'ไม่มีข้อมูล';
                return;
            }

            $user = get_userdata($uid);
            $name = $user ? $user->display_name : 'ไม่ทราบผู้ใช้';

            $date = get_post_modified_time('d/m/Y', false, $post_id);
            $time = get_post_modified_time('H:i', false, $post_id);

            echo esc_html($name) . '<br>';
            echo '<small style="color:#666;">' . esc_html($date . ' ' . $time) . '</small>';
        }, 10, 2);
    }

    // 3) เพิ่ม Meta Box ในหน้าแก้ไข (Edit) ของแต่ละ post type
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