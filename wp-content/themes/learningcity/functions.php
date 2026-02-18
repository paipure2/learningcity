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

/* =========================================================
 * [FRONT] AJAX Modal Search (No plugin)
 * ========================================================= */
add_action('wp_enqueue_scripts', function () {
  $script_path = get_template_directory() . '/assets/scripts/modules/modal-search-ajax.js';
  wp_enqueue_script(
    'lc-modal-search-ajax',
    get_template_directory_uri() . '/assets/scripts/modules/modal-search-ajax.js',
    [],
    file_exists($script_path) ? filemtime($script_path) : null,
    true
  );

  wp_add_inline_script(
    'lc-modal-search-ajax',
    'window.LC_MODAL_SEARCH = ' . wp_json_encode([
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('lc_modal_search_nonce'),
    ]) . ';',
    'before'
  );
}, 35);

if (!function_exists('lc_modal_search_collect_sections')) {
  function lc_modal_search_get_popular_keywords($limit = 6) {
    global $wpdb;

    $limit = max(1, min(20, absint($limit)));
    $keywords = [];

    $table = $wpdb->prefix . 'lc_analytics_events';
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

    if ($table_exists === $table) {
      $rows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT keyword, COUNT(*) AS total
           FROM {$table}
           WHERE event_type = %s
             AND keyword <> ''
             AND created_at >= DATE_SUB(%s, INTERVAL %d DAY)
           GROUP BY keyword
           ORDER BY total DESC
           LIMIT %d",
          'search_keyword',
          current_time('mysql'),
          30,
          $limit
        )
      );

      if (!empty($rows) && is_array($rows)) {
        foreach ($rows as $row) {
          $keyword = lc_modal_search_clean_text($row->keyword ?? '');
          if ($keyword !== '') {
            $keywords[] = $keyword;
          }
        }
      }
    }

    if (!empty($keywords)) {
      return array_values(array_unique($keywords));
    }

    $popular_terms = get_terms([
      'taxonomy'   => 'course_category',
      'parent'     => 0,
      'hide_empty' => true,
      'orderby'    => 'count',
      'order'      => 'DESC',
      'number'     => $limit,
    ]);

    if (!is_wp_error($popular_terms) && is_array($popular_terms)) {
      foreach ($popular_terms as $term) {
        $term_name = lc_modal_search_clean_text($term->name ?? '');
        if ($term_name !== '') {
          $keywords[] = $term_name;
        }
      }
    }

    return array_values(array_unique($keywords));
  }

  function lc_modal_search_clean_text($text = '') {
    $text = wp_strip_all_tags((string) $text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim((string) $text);
  }

  function lc_modal_search_collect_sections($query = '') {
    $query = trim((string) $query);
    $limit = 6;
    $has_query = ($query !== '');

    $popular_keywords = lc_modal_search_get_popular_keywords($limit);
    $blm_pages = get_posts([
      'post_type'      => 'page',
      'posts_per_page' => 1,
      'meta_key'       => '_wp_page_template',
      'meta_value'     => 'page-blm.php',
      'post_status'    => 'publish',
      'fields'         => 'ids',
    ]);
    $learning_map_url = !empty($blm_pages) ? get_permalink($blm_pages[0]) : home_url('/learning-map/');

    $get_top_viewed_ids = function ($post_type, $limit = 6) {
      global $wpdb;

      $post_type = sanitize_key((string) $post_type);
      $limit = max(1, min(30, absint($limit)));

      if ($post_type === 'course') {
        $object_type = 'course';
        $event_type = 'course_view';
      } elseif ($post_type === 'location') {
        $object_type = 'location';
        $event_type = 'location_view';
      } else {
        return [];
      }

      $table = $wpdb->prefix . 'lc_analytics_events';
      $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
      if ($table_exists !== $table) return [];

      $rows = $wpdb->get_col(
        $wpdb->prepare(
          "SELECT e.object_id
           FROM {$table} e
           INNER JOIN {$wpdb->posts} p ON p.ID = e.object_id
           WHERE e.object_type = %s
             AND e.event_type = %s
             AND e.object_id > 0
             AND p.post_type = %s
             AND p.post_status = 'publish'
           GROUP BY e.object_id
           ORDER BY COUNT(*) DESC, MAX(e.created_at) DESC
           LIMIT %d",
          $object_type,
          $event_type,
          $post_type,
          $limit
        )
      );

      return array_values(array_filter(array_map('absint', (array) $rows)));
    };

    $collect_items = function ($post_type, $taxonomies = []) use ($query, $limit, $has_query, $learning_map_url, $get_top_viewed_ids) {
      $ids = [];
      $text_hit_ids = [];
      $meta_hit_ids = [];
      $taxonomy_items = [];
      $search_pool_limit = max($limit * 3, 18);

      if ($has_query) {
        $taxonomy_term_map = [];
        $text_hit_ids = get_posts([
          'post_type'              => $post_type,
          'post_status'            => 'publish',
          'posts_per_page'         => $search_pool_limit,
          'fields'                 => 'ids',
          's'                      => $query,
          'ignore_sticky_posts'    => true,
          'no_found_rows'          => true,
          'orderby'                => 'date',
          'order'                  => 'DESC',
          'update_post_meta_cache' => false,
          'update_post_term_cache' => false,
        ]);
        $ids = $text_hit_ids;

        $tax_query = ['relation' => 'OR'];
        foreach ((array) $taxonomies as $taxonomy) {
          $taxonomy = sanitize_key((string) $taxonomy);
          if ($taxonomy === '') continue;

          $term_ids = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
            'fields'     => 'ids',
            'name__like' => $query,
            'number'     => 20,
          ]);

          if (!is_wp_error($term_ids) && !empty($term_ids)) {
            foreach ((array) $term_ids as $term_id) {
              $term_id = absint($term_id);
              if ($term_id > 0) {
                $taxonomy_term_map[$taxonomy][] = $term_id;
              }
            }
            $tax_query[] = [
              'taxonomy' => $taxonomy,
              'field'    => 'term_id',
              'terms'    => array_map('absint', (array) $term_ids),
              'operator' => 'IN',
            ];
          }
        }

        if (count($tax_query) > 1) {
          $tax_ids = get_posts([
            'post_type'              => $post_type,
            'post_status'            => 'publish',
            'posts_per_page'         => $search_pool_limit,
            'fields'                 => 'ids',
            'tax_query'              => $tax_query,
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
          ]);
          $ids = array_merge((array) $ids, array_values(array_unique(array_filter(array_map('absint', (array) $tax_ids)))));
        }

        if (!empty($taxonomy_term_map)) {
          foreach ($taxonomy_term_map as $taxonomy => $term_ids) {
            $terms = get_terms([
              'taxonomy'   => $taxonomy,
              'hide_empty' => true,
              'include'    => array_values(array_unique(array_filter(array_map('absint', (array) $term_ids)))),
            ]);
            if (is_wp_error($terms) || empty($terms)) continue;

            foreach ($terms as $term) {
              if (!($term instanceof WP_Term)) continue;
              $title = lc_modal_search_clean_text($term->name);
              if ($title === '') continue;

              if ($post_type === 'location') {
                $map_args = [];
                if ($taxonomy === 'location-type') {
                  $map_args['categories'] = (string) $term->slug;
                } elseif ($taxonomy === 'age_range') {
                  $map_args['tags'] = (string) $term->slug;
                } elseif ($taxonomy === 'facility') {
                  $map_args['amenities'] = (string) $term->slug;
                } elseif ($taxonomy === 'admission_policy') {
                  $map_args['admission'] = (string) $term->slug;
                } elseif ($taxonomy === 'district') {
                  $map_args['district'] = (string) $term->slug;
                } else {
                  $map_args['categories'] = (string) $term->slug;
                }
                $url = add_query_arg($map_args, $learning_map_url);
              } else {
                $url = get_term_link($term);
              }
              if (is_wp_error($url) || !$url) continue;

              $taxonomy_items[] = [
                'title' => $title,
                'url'   => $url,
                'badge' => 'หมวดหมู่',
              ];
            }
          }
        }

        global $wpdb;
        $like = '%' . $wpdb->esc_like($query) . '%';
        $sql = $wpdb->prepare(
          "SELECT DISTINCT p.ID
           FROM {$wpdb->posts} p
           INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
           WHERE p.post_type = %s
             AND p.post_status = 'publish'
             AND pm.meta_key NOT LIKE %s
             AND pm.meta_value LIKE %s
           ORDER BY p.post_date DESC
           LIMIT %d",
          $post_type,
          '\_%',
          $like,
          $search_pool_limit
        );
        $meta_hit_ids = array_values(array_filter(array_map('absint', (array) $wpdb->get_col($sql))));
        if (!empty($meta_hit_ids)) {
          $ids = array_merge((array) $ids, $meta_hit_ids);
        }

      } else {
        $ids = $get_top_viewed_ids($post_type, $limit);
        if (empty($ids)) {
          $ids = get_posts([
            'post_type'              => $post_type,
            'post_status'            => 'publish',
            'posts_per_page'         => $limit,
            'fields'                 => 'ids',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
          ]);
        }
      }

      $ids = array_values(array_unique(array_filter(array_map('absint', (array) $ids))));
      $post_items = [];
      foreach ($ids as $post_id) {
        $title = lc_modal_search_clean_text(get_the_title((int) $post_id));
        $url = get_permalink((int) $post_id);
        if ($title === '' || !$url) continue;
        $post_items[] = [
          'title' => $title,
          'url'   => $url,
          'badge' => '',
        ];
      }

      $merged = array_merge($taxonomy_items, $post_items);
      if (empty($merged)) return [];

      $unique = [];
      $seen = [];
      foreach ($merged as $item) {
        $key = strtolower(trim((string) ($item['url'] ?? ''))) . '|' . strtolower(trim((string) ($item['title'] ?? '')));
        if ($key === '|' || isset($seen[$key])) continue;
        $seen[$key] = true;
        $unique[] = $item;
        if (count($unique) >= $limit) break;
      }

      return $unique;
    };

    $course_taxonomies = array_values(array_filter((array) get_object_taxonomies('course', 'names'), function ($taxonomy) {
      $obj = get_taxonomy($taxonomy);
      return ($obj && !empty($obj->public));
    }));

    $location_taxonomies = array_values(array_filter((array) get_object_taxonomies('location', 'names'), function ($taxonomy) {
      $obj = get_taxonomy($taxonomy);
      return ($obj && !empty($obj->public));
    }));

    $nextlearn = $collect_items('course', $course_taxonomies);
    $locations = $collect_items('location', $location_taxonomies);

    return [
      'popular_keywords' => $popular_keywords,
      'nextlearn'   => $nextlearn,
      'locations'   => $locations,
    ];
  }
}

add_action('wp_ajax_lc_modal_search', 'lc_modal_search_ajax');
add_action('wp_ajax_nopriv_lc_modal_search', 'lc_modal_search_ajax');
if (!function_exists('lc_modal_search_ajax')) {
  function lc_modal_search_ajax() {
    check_ajax_referer('lc_modal_search_nonce', 'nonce');
    $query = isset($_POST['q']) ? sanitize_text_field(wp_unslash($_POST['q'])) : '';
    wp_send_json_success(lc_modal_search_collect_sections($query));
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
 * [ADMIN COURSE] Bulk set / auto-detect course category
 * ========================================================= */
if (!function_exists('lc_normalize_text_for_match')) {
  function lc_normalize_text_for_match($text) {
    $text = trim((string) $text);
    if ($text === '') return '';
    if (function_exists('mb_strtolower')) {
      return mb_strtolower($text, 'UTF-8');
    }
    return strtolower($text);
  }
}

if (!function_exists('lc_text_contains')) {
  function lc_text_contains($haystack, $needle) {
    $haystack = (string) $haystack;
    $needle = (string) $needle;
    if ($needle === '') return false;

    if (function_exists('mb_strpos')) {
      return mb_strpos($haystack, $needle) !== false;
    }
    return strpos($haystack, $needle) !== false;
  }
}

if (!function_exists('lc_detect_course_category_term_id_by_title')) {
  function lc_detect_course_category_term_id_by_title($course_title) {
    $title = lc_normalize_text_for_match($course_title);
    if ($title === '') return 0;

    $terms = get_terms([
      'taxonomy'   => 'course_category',
      'hide_empty' => false,
      'orderby'    => 'name',
      'order'      => 'ASC',
    ]);
    if (is_wp_error($terms) || empty($terms)) return 0;

    $best_term_id = 0;
    $best_score = 0;

    // 1) Prefer direct term-name containment with longest-name priority.
    foreach ($terms as $term) {
      $term_name = lc_normalize_text_for_match($term->name ?? '');
      $term_slug = lc_normalize_text_for_match(str_replace('-', '', (string) ($term->slug ?? '')));
      if ($term_name === '') continue;
      if (lc_text_contains($title, $term_name)) {
        $score = 1000 + strlen($term_name);
        if ($score > $best_score) {
          $best_score = $score;
          $best_term_id = (int) $term->term_id;
        }
      }
      if ($term_slug !== '' && lc_text_contains($title, $term_slug)) {
        $score = 800 + strlen($term_slug);
        if ($score > $best_score) {
          $best_score = $score;
          $best_term_id = (int) $term->term_id;
        }
      }
    }
    if ($best_term_id > 0) return $best_term_id;

    // 2) Fallback by keyword -> first matching category name.
    $keywords = apply_filters('lc_course_auto_category_keywords', [
      'ภาษา', 'ดิจิทัล', 'คอม', 'ออนไลน์', 'อาหาร', 'เบเกอรี่', 'ขนม',
      'ช่าง', 'ซ่อม', 'ไฟฟ้า', 'เสริมสวย', 'นวด', 'ตัดผม', 'เย็บผ้า',
      'ศิลปะ', 'ดนตรี', 'บัญชี', 'ธุรกิจ', 'การตลาด',
    ]);

    if (is_array($keywords)) {
      foreach ($keywords as $kw) {
        $kw = lc_normalize_text_for_match($kw);
        if ($kw === '') continue;
        if (!lc_text_contains($title, $kw)) continue;

        foreach ($terms as $term) {
          $term_name = lc_normalize_text_for_match($term->name ?? '');
          if ($term_name === '') continue;
          if (lc_text_contains($term_name, $kw) || lc_text_contains($kw, $term_name)) {
            return (int) $term->term_id;
          }
        }
      }
    }

    return 0;
  }
}

if (!function_exists('lc_guess_default_course_category_term_id')) {
  function lc_guess_default_course_category_term_id() {
    $default_term_id = (int) apply_filters('lc_course_auto_category_default_term_id', 0);
    if ($default_term_id > 0) return $default_term_id;

    $terms = get_terms([
      'taxonomy'   => 'course_category',
      'hide_empty' => false,
      'parent'     => 0,
      'orderby'    => 'name',
      'order'      => 'ASC',
      'number'     => 1,
    ]);
    if (is_wp_error($terms) || empty($terms)) return 0;
    return (int) $terms[0]->term_id;
  }
}

add_filter('bulk_actions-edit-course', function ($actions) {
  $actions['lc_auto_course_category'] = 'Auto Category (from title)';
  return $actions;
});

if (!function_exists('lc_normalize_admin_course_return_url')) {
  function lc_normalize_admin_course_return_url($url, $default = '') {
    $default = $default !== '' ? (string) $default : admin_url('edit.php?post_type=course');
    $url = is_string($url) ? trim($url) : '';
    if ($url === '') return $default;

    // WP may submit referer as a relative path in bulk form requests.
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
      if (strpos($url, '/') === 0) {
        $url = home_url($url);
      } else {
        return $default;
      }
    }

    $url = esc_url_raw($url);
    $url = wp_validate_redirect($url, '');
    if ($url === '') return $default;
    if (strpos($url, admin_url()) !== 0) return $default;
    if (strpos($url, 'post_type=course') === false) return $default;

    return $url;
  }
}

add_filter('handle_bulk_actions-edit-course', function ($redirect_to, $doaction, $post_ids) {
  if ($doaction === 'lc_auto_course_category') {
    $default_term_id = lc_guess_default_course_category_term_id();
    $rows = [];
    $raw_return_to = isset($_REQUEST['_wp_http_referer']) ? wp_unslash((string) $_REQUEST['_wp_http_referer']) : '';
    if ($raw_return_to === '') {
      $referer = wp_get_referer();
      $raw_return_to = $referer ? (string) $referer : (string) $redirect_to;
    }
    $raw_return_to = remove_query_arg(['lc_bulk_cat_review', 'lc_bulk_cat_saved', 'lc_bulk_cat_skipped', 'lc_bulk_cat_review_expired'], $raw_return_to);
    $return_to = lc_normalize_admin_course_return_url($raw_return_to, admin_url('edit.php?post_type=course'));

    foreach ((array) $post_ids as $post_id) {
      $post_id = (int) $post_id;
      if ($post_id <= 0 || get_post_type($post_id) !== 'course') continue;
      if (!current_user_can('edit_post', $post_id)) continue;

      $title = get_the_title($post_id);
      $term_id = lc_detect_course_category_term_id_by_title($title);
      if ($term_id <= 0 && $default_term_id > 0) {
        $term_id = $default_term_id;
      }
      $rows[] = [
        'post_id' => $post_id,
        'title' => $title,
        'suggested_term_id' => (int) $term_id,
      ];
    }

    if (empty($rows)) return $redirect_to;

    $token = wp_generate_password(20, false, false);
    $payload = [
      'created_by' => get_current_user_id(),
      'return_to' => $return_to,
      'rows' => $rows,
    ];
    set_transient('lc_bulk_cat_review_' . $token, $payload, 30 * MINUTE_IN_SECONDS);

    return add_query_arg(['lc_bulk_cat_review' => $token], $redirect_to);
  }

  return $redirect_to;
}, 10, 3);

add_action('admin_post_lc_bulk_cat_apply_review', function () {
  if (!current_user_can('edit_posts')) {
    wp_die('forbidden');
  }
  check_admin_referer('lc_bulk_cat_apply_review');

  $token = isset($_POST['lc_bulk_cat_review_token']) ? sanitize_text_field((string) $_POST['lc_bulk_cat_review_token']) : '';
  $raw_return_to = isset($_POST['lc_bulk_cat_return_to']) ? wp_unslash((string) $_POST['lc_bulk_cat_return_to']) : '';
  $default_return = admin_url('edit.php?post_type=course');
  $return_to = lc_normalize_admin_course_return_url($raw_return_to, $default_return);

  if ($token === '') {
    wp_safe_redirect($return_to);
    exit;
  }

  $cache_key = 'lc_bulk_cat_review_' . $token;
  $payload = get_transient($cache_key);
  if (!is_array($payload) || (int) ($payload['created_by'] ?? 0) !== get_current_user_id()) {
    wp_safe_redirect(add_query_arg(['lc_bulk_cat_review_expired' => 1], $return_to));
    exit;
  }

  $rows = isset($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : [];
  $selected = isset($_POST['lc_bulk_category']) && is_array($_POST['lc_bulk_category']) ? $_POST['lc_bulk_category'] : [];

  $updated = 0;
  $skipped = 0;
  foreach ($rows as $row) {
    $post_id = (int) ($row['post_id'] ?? 0);
    if ($post_id <= 0 || get_post_type($post_id) !== 'course' || !current_user_can('edit_post', $post_id)) {
      $skipped++;
      continue;
    }

    $term_id = isset($selected[$post_id]) ? (int) $selected[$post_id] : 0;
    if ($term_id <= 0) {
      $skipped++;
      continue;
    }

    $term = get_term($term_id, 'course_category');
    if (!$term || is_wp_error($term)) {
      $skipped++;
      continue;
    }

    wp_set_object_terms($post_id, [$term_id], 'course_category', false);
    $updated++;
  }

  delete_transient($cache_key);

  wp_safe_redirect(add_query_arg([
    'lc_bulk_cat_saved' => $updated,
    'lc_bulk_cat_skipped' => $skipped,
  ], $return_to));
  exit;
});

add_action('admin_notices', function () {
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || $screen->id !== 'edit-course') return;

  if (isset($_GET['lc_bulk_cat_saved'])) {
    $saved = (int) $_GET['lc_bulk_cat_saved'];
    $skipped = isset($_GET['lc_bulk_cat_skipped']) ? (int) $_GET['lc_bulk_cat_skipped'] : 0;
    echo '<div class="notice notice-success is-dismissible"><p>บันทึกหมวดหมู่แล้ว ' . esc_html((string) $saved) . ' คอร์ส, ข้าม ' . esc_html((string) $skipped) . ' คอร์ส</p></div>';
  }

  if (isset($_GET['lc_bulk_cat_review_expired'])) {
    echo '<div class="notice notice-warning is-dismissible"><p>รายการรีวิวหมดอายุ กรุณาเลือกคอร์สแล้วรันใหม่อีกครั้ง</p></div>';
  }
});

add_action('admin_footer-edit.php', function () {
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || $screen->id !== 'edit-course') return;
  if (!isset($_GET['lc_bulk_cat_review'])) return;

  $token = sanitize_text_field((string) $_GET['lc_bulk_cat_review']);
  if ($token === '') return;

  $payload = get_transient('lc_bulk_cat_review_' . $token);
  if (!is_array($payload) || (int) ($payload['created_by'] ?? 0) !== get_current_user_id()) return;
  $rows = isset($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : [];
  if (empty($rows)) return;

  $terms = get_terms([
    'taxonomy' => 'course_category',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
  ]);
  if (is_wp_error($terms) || empty($terms)) return;
  ?>
  <div id="lcBulkCatModal" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100000;display:flex;align-items:center;justify-content:center;">
    <div style="background:#fff;width:min(1100px,96vw);max-height:88vh;border-radius:12px;display:flex;flex-direction:column;overflow:hidden;">
      <div style="padding:14px 18px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
        <strong>Auto Category (from title)</strong>
        <button type="button" id="lcBulkCatClose" class="button">ปิด</button>
      </div>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;flex-direction:column;min-height:0;">
        <?php wp_nonce_field('lc_bulk_cat_apply_review'); ?>
        <input type="hidden" name="action" value="lc_bulk_cat_apply_review">
        <input type="hidden" name="lc_bulk_cat_review_token" value="<?php echo esc_attr($token); ?>">
        <input type="hidden" name="lc_bulk_cat_return_to" value="<?php echo esc_attr((string) ($payload['return_to'] ?? remove_query_arg('lc_bulk_cat_review'))); ?>">
        <div style="padding:12px 18px;overflow:auto;">
          <p style="margin:0 0 10px;">ตรวจสอบหมวดที่ระบบแนะนำก่อนบันทึก (แก้ไขได้ต่อแถว)</p>
          <table class="widefat striped">
            <thead><tr><th style="width:80px;">ID</th><th>คอร์ส</th><th style="width:360px;">Category</th></tr></thead>
            <tbody>
              <?php foreach ($rows as $row): $post_id = (int) ($row['post_id'] ?? 0); $title = (string) ($row['title'] ?? ''); $suggest = (int) ($row['suggested_term_id'] ?? 0); ?>
                <tr>
                  <td><?php echo esc_html((string) $post_id); ?></td>
                  <td><?php echo esc_html($title); ?></td>
                  <td>
                    <div class="lc-cat-picker" style="position:relative;">
                      <div style="display:flex;align-items:stretch;gap:8px;">
                        <input
                          type="search"
                          class="lc-cat-picker-input"
                          placeholder="ค้นหา category..."
                          style="flex:1;"
                        >
                        <button type="button" class="button button-primary lc-cat-picker-apply" title="ยืนยัน">✓</button>
                        <button type="button" class="button lc-cat-picker-clear" title="ล้างค่า">✕</button>
                      </div>
                      <div class="lc-cat-picker-menu" style="position:absolute;left:0;right:0;top:calc(100% + 4px);max-height:220px;overflow:auto;background:#fff;border:1px solid #d1d5db;border-radius:8px;display:none;z-index:20;"></div>
                      <select
                        class="lc-cat-select"
                        name="lc_bulk_category[<?php echo esc_attr((string) $post_id); ?>]"
                        style="display:none;"
                      >
                        <option value="0">-- ไม่เปลี่ยน --</option>
                        <?php foreach ($terms as $term): ?>
                          <option
                            value="<?php echo esc_attr((string) $term->term_id); ?>"
                            data-label="<?php echo esc_attr(lc_normalize_text_for_match((string) $term->name)); ?>"
                            <?php selected($suggest, (int) $term->term_id); ?>
                          >
                            <?php echo esc_html($term->name); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="padding:12px 18px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
          <button type="button" id="lcBulkCatCancel" class="button">ยกเลิก</button>
          <button type="submit" id="lcBulkCatSaveAll" class="button button-primary">บันทึกทั้งหมด</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    (function () {
      const modal = document.getElementById('lcBulkCatModal');
      const closeBtn = document.getElementById('lcBulkCatClose');
      const cancelBtn = document.getElementById('lcBulkCatCancel');
      if (!modal) return;
      function closeModal() {
        modal.remove();
        const url = new URL(window.location.href);
        url.searchParams.delete('lc_bulk_cat_review');
        window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
      }
      if (closeBtn) closeBtn.addEventListener('click', closeModal);
      if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
      modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
      });

      function normalize(v) {
        return (v || '').toString().trim().toLowerCase();
      }

      const pickers = Array.from(modal.querySelectorAll('.lc-cat-picker'));

      pickers.forEach(function (picker) {
        const input = picker.querySelector('.lc-cat-picker-input');
        const menu = picker.querySelector('.lc-cat-picker-menu');
        const select = picker.querySelector('.lc-cat-select');
        const applyBtn = picker.querySelector('.lc-cat-picker-apply');
        const clearBtn = picker.querySelector('.lc-cat-picker-clear');
        if (!input || !menu || !select || !applyBtn || !clearBtn) return;

        const options = Array.from(select.options)
          .filter(function (opt) { return opt.value !== '0'; })
          .map(function (opt) {
            return {
              value: opt.value,
              label: (opt.textContent || '').trim(),
              normalized: normalize(opt.dataset.label || opt.textContent || ''),
            };
          });

        let pendingValue = select.value && select.value !== '0' ? String(select.value) : '';

        function setInputFromValue(value) {
          const found = options.find(function (o) { return String(o.value) === String(value); });
          input.value = found ? found.label : '';
        }

        function openMenu() {
          menu.style.display = 'block';
        }

        function closeMenu() {
          menu.style.display = 'none';
        }

        function renderMenu() {
          const q = normalize(input.value);
          const filtered = options.filter(function (o) {
            return q === '' || o.normalized.indexOf(q) !== -1;
          });

          if (!filtered.length) {
            menu.innerHTML = '<div style="padding:8px 10px;color:#6b7280;">ไม่พบ category</div>';
            openMenu();
            return;
          }

          menu.innerHTML = filtered.map(function (o) {
            const active = String(o.value) === String(pendingValue);
            const bg = active ? 'background:#e5e7eb;' : '';
            return '<button type="button" data-value="' + o.value + '" style="display:block;width:100%;text-align:left;padding:8px 10px;border:0;border-bottom:1px solid #f1f5f9;' + bg + '">' + o.label + '</button>';
          }).join('');

          menu.querySelectorAll('button[data-value]').forEach(function (btn) {
            btn.addEventListener('click', function () {
              pendingValue = String(btn.getAttribute('data-value') || '');
              const found = options.find(function (o) { return String(o.value) === pendingValue; });
              if (found) input.value = found.label;
              // Apply immediately so Save works even if user doesn't click ✓.
              select.value = pendingValue || '0';
              renderMenu();
            });
          });

          openMenu();
        }

        function applySelection() {
          const q = normalize(input.value);
          if (!pendingValue && q !== '') {
            const exact = options.find(function (o) { return o.normalized === q; });
            if (exact) pendingValue = String(exact.value);
          }
          if (!pendingValue && q !== '') {
            const first = options.find(function (o) { return o.normalized.indexOf(q) !== -1; });
            if (first) pendingValue = String(first.value);
          }

          select.value = pendingValue || '0';
          setInputFromValue(select.value);
          closeMenu();
        }

        function clearSelection() {
          pendingValue = '';
          select.value = '0';
          input.value = '';
          renderMenu();
        }

        setInputFromValue(select.value);

        input.addEventListener('focus', renderMenu);
        input.addEventListener('input', function () {
          pendingValue = '';
          renderMenu();
        });
        input.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            applySelection();
          }
        });

        applyBtn.addEventListener('click', applySelection);
        clearBtn.addEventListener('click', clearSelection);

        document.addEventListener('click', function (e) {
          if (!picker.contains(e.target)) closeMenu();
        });
      });

      const saveBtn = document.getElementById('lcBulkCatSaveAll');
      if (saveBtn) {
        saveBtn.addEventListener('click', function () {
          // Ensure all rows sync their text input -> select before submit.
          pickers.forEach(function (picker) {
            const input = picker.querySelector('.lc-cat-picker-input');
            const select = picker.querySelector('.lc-cat-select');
            if (!input || !select) return;
            const q = normalize(input.value);
            if (q === '') return;

            const opts = Array.from(select.options).filter(function (o) { return o.value !== '0'; });
            let match = opts.find(function (o) {
              const label = normalize(o.dataset.label || o.textContent || '');
              return label === q;
            });
            if (!match) {
              match = opts.find(function (o) {
                const label = normalize(o.dataset.label || o.textContent || '');
                return label.indexOf(q) !== -1;
              });
            }
            if (match) select.value = String(match.value);
          });
        });
      }
    })();
  </script>
  <?php
});

/* =========================================================
 * [ADMIN] BMA Training Sync status
 * ========================================================= */
if (!function_exists('lc_bma_parse_utc_to_ts')) {
    function lc_bma_parse_utc_to_ts($utc_datetime) {
        if (!is_string($utc_datetime) || $utc_datetime === '') return 0;
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $utc_datetime, new DateTimeZone('UTC'));
        return $dt ? (int) $dt->getTimestamp() : 0;
    }
}

if (!function_exists('lc_bma_format_local_datetime')) {
    function lc_bma_format_local_datetime($utc_datetime) {
        $ts = lc_bma_parse_utc_to_ts($utc_datetime);
        return $ts ? wp_date('Y-m-d H:i:s', $ts) : '-';
    }
}

if (!function_exists('lc_bma_get_sync_status')) {
    function lc_bma_get_sync_status() {
        $status = get_option('lc_bmatraining_last_sync', []);
        return is_array($status) ? $status : [];
    }
}

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=course',
        'BMA Sync Status',
        'BMA Sync',
        'edit_posts',
        'lc-bma-sync-status',
        'lc_render_bma_sync_status_page'
    );
}, 20);

if (!function_exists('lc_render_bma_sync_status_page')) {
    function lc_render_bma_sync_status_page() {
        if (!current_user_can('edit_posts')) return;

        $sync_notice = null;
        $sync_output = '';

        if (
            isset($_POST['lc_bma_action'])
            && in_array($_POST['lc_bma_action'], ['sync_now', 'dry_run'], true)
            && check_admin_referer('lc_bma_sync_now_action')
        ) {
            $action = sanitize_text_field((string) $_POST['lc_bma_action']);
            $script_path = get_template_directory() . '/cronjobs/fetch-bmatraining-data.php';

            if (!file_exists($script_path)) {
                $sync_notice = [
                    'type' => 'error',
                    'message' => 'ไม่พบไฟล์ sync script',
                ];
            } else {
                if (!defined('LC_BMA_SYNC_DISABLE_AUTO_RUN')) {
                    define('LC_BMA_SYNC_DISABLE_AUTO_RUN', true);
                }

                require_once $script_path;

                if ($action === 'sync_now' && function_exists('lc_bma_run_sync')) {
                    ob_start();
                    $result = lc_bma_run_sync(true);
                    $sync_output = trim((string) ob_get_clean());

                    $sync_notice = [
                        'type' => !empty($result['ok']) ? 'success' : 'error',
                        'message' => !empty($result['ok'])
                            ? 'Sync สำเร็จ'
                            : ('Sync ไม่สำเร็จ: ' . ($result['message'] ?? 'unknown error')),
                    ];
                } elseif ($action === 'dry_run' && function_exists('lc_bma_run_dry_run')) {
                    ob_start();
                    $result = lc_bma_run_dry_run(true);
                    $sync_output = trim((string) ob_get_clean());

                    $sync_notice = [
                        'type' => !empty($result['ok']) ? 'success' : 'error',
                        'message' => !empty($result['ok'])
                            ? 'Dry Run สำเร็จ (ไม่มีการเขียนข้อมูล)'
                            : ('Dry Run ไม่สำเร็จ: ' . ($result['message'] ?? 'unknown error')),
                    ];
                } else {
                    $sync_notice = [
                        'type' => 'error',
                        'message' => 'ไม่พบฟังก์ชันที่ต้องใช้สำหรับ action นี้',
                    ];
                }
            }
        }

        $status = lc_bma_get_sync_status();
        $started_at = $status['started_at_utc'] ?? '';
        $finished_at = $status['finished_at_utc'] ?? '';
        $state = (string) ($status['status'] ?? 'never');

        $started_ts = lc_bma_parse_utc_to_ts($started_at);
        $finished_ts = lc_bma_parse_utc_to_ts($finished_at);
        $duration = ($started_ts && $finished_ts && $finished_ts >= $started_ts)
            ? ($finished_ts - $started_ts) . ' วินาที'
            : '-';

        $last_sync_age = $finished_ts ? human_time_diff($finished_ts, time()) . ' ที่แล้ว' : '-';

        $status_label = [
            'success' => 'สำเร็จ',
            'failed' => 'ล้มเหลว',
            'running' => 'กำลังรัน',
            'never' => 'ยังไม่เคยรัน',
        ];
        $status_text = $status_label[$state] ?? $state;

        echo '<div class="wrap">';
        echo '<h1>BMA Training Sync Status</h1>';
        echo '<p>อัปเดตหน้านี้เพื่อดูสถานะล่าสุดของการดึงข้อมูลจาก API</p>';
        echo '<p style="display:flex;gap:8px;align-items:center;">';
        echo '<a href="' . esc_url(admin_url('edit.php?post_type=course&page=lc-bma-sync-status')) . '" class="button">Refresh</a>';
        echo '<form method="post" style="display:inline-block;margin:0;">';
        wp_nonce_field('lc_bma_sync_now_action');
        echo '<input type="hidden" name="lc_bma_action" value="sync_now">';
        echo '<button type="submit" class="button button-primary" onclick="return confirm(\'ยืนยันการ Sync ข้อมูล BMA ตอนนี้?\');">Sync Now</button>';
        echo '</form>';
        echo '<form method="post" style="display:inline-block;margin:0 0 0 8px;">';
        wp_nonce_field('lc_bma_sync_now_action');
        echo '<input type="hidden" name="lc_bma_action" value="dry_run">';
        echo '<button type="submit" class="button">Sync Dry Run</button>';
        echo '</form>';
        echo '</p>';

        if (is_array($sync_notice)) {
            $notice_class = $sync_notice['type'] === 'success' ? 'notice notice-success' : 'notice notice-error';
            echo '<div class="' . esc_attr($notice_class) . '"><p>' . esc_html($sync_notice['message']) . '</p></div>';
        }

        if ($sync_output !== '') {
            echo '<h2 style="margin-top:20px;">Sync Log</h2>';
            echo '<pre style="max-width:900px;white-space:pre-wrap;">' . esc_html($sync_output) . '</pre>';
        }

        echo '<table class="widefat striped" style="margin-top:16px;max-width:900px">';
        echo '<tbody>';
        echo '<tr><th style="width:260px">สถานะล่าสุด</th><td>' . esc_html($status_text) . '</td></tr>';
        echo '<tr><th>เริ่มรันล่าสุด</th><td>' . esc_html(lc_bma_format_local_datetime($started_at)) . '</td></tr>';
        echo '<tr><th>จบรันล่าสุด</th><td>' . esc_html(lc_bma_format_local_datetime($finished_at)) . '</td></tr>';
        echo '<tr><th>เวลาที่ใช้</th><td>' . esc_html($duration) . '</td></tr>';
        echo '<tr><th>เวลาผ่านไปจากการ sync ล่าสุด</th><td>' . esc_html($last_sync_age) . '</td></tr>';
        echo '<tr><th>จำนวนคอร์สจาก API</th><td>' . esc_html((string) ($status['api_course_count'] ?? '-')) . '</td></tr>';
        echo '<tr><th>คอร์สที่เปลี่ยน / ไม่เปลี่ยน (API เทียบ DB)</th><td>' . esc_html((string) ($status['api_changed_courses'] ?? '-')) . ' / ' . esc_html((string) ($status['api_unchanged_courses'] ?? '-')) . '</td></tr>';
        echo '<tr><th>คอร์สเพิ่มใหม่ / อัปเดต</th><td>' . esc_html((string) ($status['courses_new'] ?? '-')) . ' / ' . esc_html((string) ($status['courses_updated'] ?? '-')) . '</td></tr>';
        echo '<tr><th>รอบเรียนเพิ่มใหม่ / อัปเดต</th><td>' . esc_html((string) ($status['sessions_new'] ?? '-')) . ' / ' . esc_html((string) ($status['sessions_updated'] ?? '-')) . '</td></tr>';
        echo '<tr><th>สถานที่เพิ่มใหม่</th><td>' . esc_html((string) ($status['locations_new'] ?? '-')) . '</td></tr>';
        echo '<tr><th>API hash ล่าสุด</th><td><code>' . esc_html((string) ($status['api_hash'] ?? '-')) . '</code></td></tr>';
        echo '<tr><th>Error ล่าสุด</th><td>' . esc_html((string) ($status['error'] ?? '-')) . '</td></tr>';
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
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

  // blog pages
  if (!empty($args['blog']) && (is_home() || is_singular('post') || is_category() || is_tag())) {
    return 'active';
  }

  return '';
}

/* =========================================================
 * [BLOG] Helpers
 * ========================================================= */
function lc_get_share_links($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();
    if (!$post_id) {
        return [];
    }

    $title = rawurlencode(wp_strip_all_tags(get_the_title($post_id)));
    $url   = rawurlencode(get_permalink($post_id));

    return [
        'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
        'x'        => 'https://twitter.com/intent/tweet?text=' . $title . '&url=' . $url,
        'line'     => 'https://social-plugins.line.me/lineit/share?url=' . $url,
        'copy'     => get_permalink($post_id),
    ];
}

function lc_get_related_posts($post_id = 0, $limit = 3) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();
    if (!$post_id) {
        return new WP_Query(['post__in' => [0]]);
    }

    $categories = wp_get_post_categories($post_id, ['fields' => 'ids']);
    if (empty($categories)) {
        return new WP_Query([
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'post__not_in'        => [$post_id],
            'posts_per_page'      => (int) $limit,
            'ignore_sticky_posts' => true,
        ]);
    }

    return new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'post__not_in'        => [$post_id],
        'posts_per_page'      => (int) $limit,
        'ignore_sticky_posts' => true,
        'category__in'        => $categories,
    ]);
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
  delete_transient('lc_open_course_ids_runtime_v2');
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

/* =========================================================
 * [CRON] BMA training sync every 30 minutes
 * ========================================================= */
add_filter('cron_schedules', function ($schedules) {
  if (!isset($schedules['lc_every_30_minutes'])) {
    $schedules['lc_every_30_minutes'] = [
      'interval' => 30 * MINUTE_IN_SECONDS,
      'display' => 'Every 30 Minutes (LearningCity)',
    ];
  }
  return $schedules;
});

add_action('init', function () {
  $hook = 'lc_bma_sync_every_30_minutes';
  $event = wp_get_scheduled_event($hook);

  if (!$event) {
    wp_schedule_event(time() + 120, 'lc_every_30_minutes', $hook);
    return;
  }

  if (!isset($event->schedule) || $event->schedule !== 'lc_every_30_minutes') {
    wp_unschedule_event($event->timestamp, $hook, $event->args);
    wp_schedule_event(time() + 120, 'lc_every_30_minutes', $hook);
  }
});

add_action('lc_bma_sync_every_30_minutes', function () {
  $lock_key = 'lc_bma_sync_cron_lock';
  if (get_transient($lock_key)) {
    return;
  }

  set_transient($lock_key, 1, 25 * MINUTE_IN_SECONDS);

  try {
    $script_path = get_template_directory() . '/cronjobs/fetch-bmatraining-data.php';
    if (!file_exists($script_path)) return;

    if (!defined('LC_BMA_SYNC_DISABLE_AUTO_RUN')) {
      define('LC_BMA_SYNC_DISABLE_AUTO_RUN', true);
    }

    require_once $script_path;
    if (function_exists('lc_bma_run_sync')) {
      lc_bma_run_sync(false);
    }
  } catch (Throwable $e) {
    error_log('LC BMA cron sync error: ' . $e->getMessage());
  } finally {
    delete_transient($lock_key);
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
  if (isset($_GET['q']) && $_GET['q'] !== '') {
    $q->set('s', sanitize_text_field(wp_unslash($_GET['q'])));
  }
  $open_ids = lc_get_open_course_ids_runtime();
  $q->set('post__in', empty($open_ids) ? [0] : $open_ids);
}, 20);

function lc_is_course_archive_context() {
  return (
    is_post_type_archive('course') ||
    is_tax('course_category') ||
    is_tax('course_provider') ||
    is_tax('audience') ||
    is_tax('skill-level') ||
    is_tag()
  );
}

function lc_get_open_course_ids_runtime() {
  $cache_key = 'lc_open_course_ids_runtime_v2';
  $cached = get_transient($cache_key);
  if (is_array($cached)) return $cached;

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

  $open_ids = [];
  $today_ts = strtotime(current_time('Y-m-d'));

  $session_ids = get_posts([
    'post_type'      => 'session',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
  ]);

  foreach ($session_ids as $sid) {
    $course_raw = get_post_meta($sid, 'course', true);
    $course_id = $extract_related_id($course_raw);
    if ($course_id <= 0 || get_post_status($course_id) !== 'publish') continue;

    $reg_start = trim((string) get_post_meta($sid, 'reg_start', true));
    $reg_end   = trim((string) get_post_meta($sid, 'reg_end', true));

    if ($reg_start === '' && $reg_end === '') {
      $open_ids[$course_id] = true;
      continue;
    }

    $start_ts = lc_date_to_ts($reg_start);
    $end_ts   = lc_date_to_ts($reg_end);

    $is_open = false;
    if ($start_ts === 0 && $end_ts === 0) $is_open = true;
    elseif ($start_ts > 0 && $end_ts === 0) $is_open = ($today_ts >= $start_ts);
    elseif ($start_ts === 0 && $end_ts > 0) $is_open = ($today_ts <= $end_ts);
    else $is_open = ($today_ts >= $start_ts && $today_ts <= $end_ts);

    if ($is_open) $open_ids[$course_id] = true;
  }

  // Include online courses with learning_link.
  $online_ids = get_posts([
    'post_type'      => 'course',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
    'meta_query'     => [
      [
        'key'     => 'learning_link',
        'value'   => '',
        'compare' => '!=',
      ],
    ],
  ]);
  foreach ($online_ids as $cid) {
    $open_ids[(int) $cid] = true;
  }

  $ids = array_map('intval', array_keys($open_ids));
  set_transient($cache_key, $ids, 5 * MINUTE_IN_SECONDS);
  return $ids;
}

function lc_course_filter_build_query_args($payload = []) {
  $allowed_taxonomies = ['course_category', 'course_provider', 'audience'];
  $allowed_context_taxonomies = ['course_category', 'course_provider', 'audience', 'post_tag', 'skill-level'];

  $page      = isset($payload['page']) ? max(1, (int) $payload['page']) : 1;
  $open_only = !isset($payload['open_only']) || (int) $payload['open_only'] !== 0;

  $context_taxonomy = isset($payload['context_taxonomy']) ? sanitize_key($payload['context_taxonomy']) : '';
  $context_term     = isset($payload['context_term']) ? sanitize_title($payload['context_term']) : '';
  if (!in_array($context_taxonomy, $allowed_context_taxonomies, true)) {
    $context_taxonomy = '';
    $context_term = '';
  }

  $selected = [
    'course_category' => isset($payload['course_category']) ? sanitize_title($payload['course_category']) : '',
    'course_provider' => isset($payload['course_provider']) ? sanitize_title($payload['course_provider']) : '',
    'audience'        => isset($payload['audience']) ? sanitize_title($payload['audience']) : '',
  ];
  $keyword = isset($payload['q']) ? sanitize_text_field($payload['q']) : '';

  $tax_query = ['relation' => 'AND'];
  foreach ($allowed_taxonomies as $tax) {
    $term = $selected[$tax];
    if (!$term && $context_taxonomy === $tax) {
      $term = $context_term;
    }

    if ($term !== '') {
      $tax_query[] = [
        'taxonomy' => $tax,
        'field'    => 'slug',
        'terms'    => [$term],
      ];
    }
  }

  // Keep taxonomy context for pages like tag.php / skill-level archive.
  if ($context_taxonomy && $context_term && !in_array($context_taxonomy, $allowed_taxonomies, true)) {
    $tax_query[] = [
      'taxonomy' => $context_taxonomy,
      'field'    => 'slug',
      'terms'    => [$context_term],
    ];
  }

  $args = [
    'post_type'      => 'course',
    'post_status'    => 'publish',
    'paged'          => $page,
    'posts_per_page' => (int) get_option('posts_per_page'),
    'orderby'        => 'date',
    'order'          => 'DESC',
  ];

  if (count($tax_query) > 1) {
    $args['tax_query'] = $tax_query;
  }

  if ($open_only) {
    $open_ids = lc_get_open_course_ids_runtime();
    $args['post__in'] = empty($open_ids) ? [0] : $open_ids;
  }
  if ($keyword !== '') {
    $args['s'] = $keyword;
  }

  return [$args, $open_only, $page];
}

function lc_course_filter_get_facet_options($payload = []) {
  $allowed_taxonomies = ['course_category', 'course_provider', 'audience'];
  $allowed_context_taxonomies = ['course_category', 'course_provider', 'audience', 'post_tag', 'skill-level'];

  $context_taxonomy = isset($payload['context_taxonomy']) ? sanitize_key($payload['context_taxonomy']) : '';
  if (!in_array($context_taxonomy, $allowed_context_taxonomies, true)) {
    $context_taxonomy = '';
  }

  $selected = [
    'course_category' => isset($payload['course_category']) ? sanitize_title($payload['course_category']) : '',
    'course_provider' => isset($payload['course_provider']) ? sanitize_title($payload['course_provider']) : '',
    'audience'        => isset($payload['audience']) ? sanitize_title($payload['audience']) : '',
  ];
  $open_only = !isset($payload['open_only']) || (int) $payload['open_only'] !== 0;
  $context_term = isset($payload['context_term']) ? sanitize_title($payload['context_term']) : '';
  $keyword = isset($payload['q']) ? sanitize_text_field($payload['q']) : '';

  $options = [];

  foreach ($allowed_taxonomies as $taxonomy) {
    if ($taxonomy === $context_taxonomy) continue;

    $facet_payload = [
      'page'             => 1,
      'open_only'        => $open_only ? 1 : 0,
      'context_taxonomy' => $context_taxonomy,
      'context_term'     => $context_term,
      'course_category'  => $selected['course_category'],
      'course_provider'  => $selected['course_provider'],
      'audience'         => $selected['audience'],
      'q'                => $keyword,
    ];
    // intersection: keep other selected filters, clear only the current facet key
    $facet_payload[$taxonomy] = '';

    [$args] = lc_course_filter_build_query_args($facet_payload);
    $args['fields'] = 'ids';
    $args['posts_per_page'] = -1;
    $args['no_found_rows'] = true;
    $args['orderby'] = 'none';
    $args['paged'] = 1;

    $course_ids = get_posts($args);
    $terms = [];

    if (!empty($course_ids)) {
      $term_ids = wp_get_object_terms($course_ids, $taxonomy, ['fields' => 'ids']);
      if (!is_wp_error($term_ids) && !empty($term_ids)) {
        $terms = get_terms([
          'taxonomy'   => $taxonomy,
          'hide_empty' => false,
          'include'    => array_map('intval', $term_ids),
          'orderby'    => 'name',
          'order'      => 'ASC',
        ]);
      }
    }

    if (!empty($selected[$taxonomy])) {
      $exists = false;
      foreach ($terms as $term) {
        if ($term->slug === $selected[$taxonomy]) {
          $exists = true;
          break;
        }
      }
      if (!$exists) {
        $selected_term = get_term_by('slug', $selected[$taxonomy], $taxonomy);
        if ($selected_term && !is_wp_error($selected_term)) {
          $terms[] = $selected_term;
        }
      }
    }

    $options[$taxonomy] = array_map(function ($term) {
      return [
        'slug' => (string) $term->slug,
        'name' => (string) $term->name,
      ];
    }, is_array($terms) ? $terms : []);
  }

  return $options;
}

function lc_course_filter_render_results_html($query, $open_only = true, $page = 1) {
  ob_start();

  set_query_var('lc_archive_open_only', $open_only);

  if ($query->have_posts()) {
    echo '<div class="grid lg:grid-cols-2 grid-cols-1 sm:gap-6 gap-4" id="lc-course-grid">';
    while ($query->have_posts()) {
      $query->the_post();
      get_template_part('template-parts/archive/course-card');
    }
    echo '</div>';

    set_query_var('max_pages', (int) $query->max_num_pages);
    set_query_var('paged', (int) $page);
    get_template_part('template-parts/archive/course-pagination');
  } else {
    echo '<div class="py-10 text-center text-fs16 opacity-70">';
    echo $open_only
      ? 'ยังไม่มีคอร์สที่ “เปิดรับสมัคร” ในเงื่อนไขนี้'
      : 'ยังไม่พบคอร์สในเงื่อนไขนี้';
    echo '</div>';
  }

  set_query_var('lc_archive_open_only', true);

  return ob_get_clean();
}

add_action('wp_ajax_lc_filter_courses', 'lc_ajax_filter_courses');
add_action('wp_ajax_nopriv_lc_filter_courses', 'lc_ajax_filter_courses');
function lc_ajax_filter_courses() {
  check_ajax_referer('lc_course_filter_nonce', 'nonce');

  $payload = wp_unslash($_POST);
  [$args, $open_only, $page] = lc_course_filter_build_query_args($payload);

  $query = new WP_Query($args);
  $html = lc_course_filter_render_results_html($query, $open_only, $page);

  wp_reset_postdata();

  wp_send_json_success([
    'html'       => $html,
    'found_posts'=> (int) $query->found_posts,
    'max_pages'  => (int) $query->max_num_pages,
    'page'       => (int) $page,
    'options'    => lc_course_filter_get_facet_options($payload),
  ]);
}

add_action('wp_enqueue_scripts', function () {
  if (!lc_is_course_archive_context()) return;

  $script_path = get_template_directory() . '/assets/scripts/modules/course-archive-filter.js';
  wp_enqueue_script(
    'lc-course-archive-filter',
    get_template_directory_uri() . '/assets/scripts/modules/course-archive-filter.js',
    [],
    file_exists($script_path) ? filemtime($script_path) : null,
    true
  );

  $payload = [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('lc_course_filter_nonce'),
  ];

  wp_add_inline_script(
    'lc-course-archive-filter',
    'window.LC_COURSE_FILTER = ' . wp_json_encode($payload) . ';',
    'before'
  );
}, 30);

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

/* =========================================================
 * [ADMIN MENU] Top-level quick links to Homepage ACF tabs
 * ========================================================= */
if (!function_exists('lc_admin_redirect_to_homepage_tab')) {
    function lc_admin_redirect_to_homepage_tab($tab_slug) {
        $front_page_id = (int) get_option('page_on_front');
        if ($front_page_id <= 0) {
            wp_die('Homepage is not configured.');
        }

        $url = admin_url('post.php?post=' . $front_page_id . '&action=edit&lc_acf_tab=' . rawurlencode($tab_slug));
        wp_safe_redirect($url);
        exit;
    }
}

add_action('admin_menu', function () {
    if (!is_admin()) return;

    $capability = 'edit_pages';

    $homepage_hook = add_menu_page(
        'Homepage',
        'Homepage',
        $capability,
        'lc-home-homepage',
        '__return_null',
        'dashicons-admin-home',
        25.0
    );

    $widget_hook = add_menu_page(
        'Widget',
        'Widget',
        $capability,
        'lc-home-widget',
        '__return_null',
        'dashicons-screenoptions',
        25.1
    );

    $policy_hook = add_menu_page(
        'Policy',
        'Policy',
        $capability,
        'lc-home-policy',
        '__return_null',
        'dashicons-list-view',
        25.2
    );

    $partners_hook = add_menu_page(
        'Partners',
        'Partners',
        $capability,
        'lc-home-partners',
        '__return_null',
        'dashicons-groups',
        25.3
    );

    if ($homepage_hook) {
        add_action('load-' . $homepage_hook, function () {
            lc_admin_redirect_to_homepage_tab('homepage-content');
        });
    }

    if ($widget_hook) {
        add_action('load-' . $widget_hook, function () {
            lc_admin_redirect_to_homepage_tab('widget-section');
        });
    }

    if ($policy_hook) {
        add_action('load-' . $policy_hook, function () {
            lc_admin_redirect_to_homepage_tab('policy-section');
        });
    }

    if ($partners_hook) {
        add_action('load-' . $partners_hook, function () {
            lc_admin_redirect_to_homepage_tab('partners');
        });
    }
}, 99);

/* =========================================================
 * [ADMIN STABILITY] Normalize plugin update transient objects
 * - Prevent warning: Undefined property stdClass::$plugin
 * ========================================================= */
if (!function_exists('lc_normalize_update_plugins_transient')) {
    function lc_normalize_update_plugins_transient($transient) {
        if (!is_object($transient)) {
            return $transient;
        }

        foreach (['response', 'no_update'] as $bucket) {
            if (!isset($transient->{$bucket})) {
                continue;
            }

            $items = $transient->{$bucket};
            if (is_object($items)) {
                $items = (array) $items;
            }
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $plugin_file => $item) {
                if (is_object($item) && empty($item->plugin)) {
                    $item->plugin = (string) $plugin_file;
                    $items[$plugin_file] = $item;
                } elseif (is_array($item) && empty($item['plugin'])) {
                    $item['plugin'] = (string) $plugin_file;
                    $items[$plugin_file] = $item;
                }
            }

            $transient->{$bucket} = $items;
        }

        return $transient;
    }
}

add_filter('site_transient_update_plugins', 'lc_normalize_update_plugins_transient', 5);
add_filter('pre_set_site_transient_update_plugins', 'lc_normalize_update_plugins_transient', 5);

/* =========================================================
 * [ADMIN PAGE] Generic tabs for ACF field groups on Homepage
 * - Keep field groups separated, but render as tabs in admin UI
 * ========================================================= */
add_action('admin_enqueue_scripts', function ($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'page') return;

    $post_id = 0;
    if (isset($_GET['post'])) {
        $post_id = (int) $_GET['post'];
    } elseif (isset($_POST['post_ID'])) {
        $post_id = (int) $_POST['post_ID'];
    }
    if ($post_id <= 0) return;

    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id <= 0 || $post_id !== $front_page_id) return;

    wp_add_inline_script('jquery-core', <<<JS
jQuery(function($){
  var \$boxes = $('#normal-sortables .postbox[id^="acf-group_"]');
  if (\$boxes.length < 2) return;

  if (!document.getElementById('lc-acf-tabs-style')) {
    var style = document.createElement('style');
    style.id = 'lc-acf-tabs-style';
    style.textContent = '.lc-acf-tabs-wrap{display:flex;gap:8px;margin:12px 0 16px;align-items:center}.lc-acf-tab-btn{padding:6px 14px;border:1px solid #2271b1;background:#fff;color:#2271b1;border-radius:4px;cursor:pointer}.lc-acf-tab-btn.is-active{background:#2271b1;color:#fff}.lc-acf-tab-btn:focus{outline:2px solid #72aee6;outline-offset:1px}';
    document.head.appendChild(style);
  }

  var \$nav = $('<div class="lc-acf-tabs-wrap" role="tablist" aria-label="ACF Group Tabs"></div>');
  var stateKey = 'lc_acf_admin_tab_' + String({$post_id});
  var urlKey = 'lc_acf_tab';

  function slugify(text) {
    return String(text || '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9\\s-_]/g, '')
      .replace(/[\\s_]+/g, '-')
      .replace(/-+/g, '-');
  }

  function getLabel(\$box, idx) {
    var \$h = \$box.find('> .postbox-header .hndle, > .hndle').first().clone();
    \$h.find('*').remove();
    var label = $.trim(\$h.text());
    if (!label) {
      label = $.trim(\$box.find('> .postbox-header .hndle, > .hndle').first().text());
    }
    if (!label) {
      var raw = String(\$box.attr('id') || '').replace(/^acf-group_/, '');
      label = raw ? raw.replace(/[_-]+/g, ' ') : '';
    }
    if (!label) label = 'Section ' + (idx + 1);
    return label;
  }

  var preferredOrder = {
    'homepage-content': 0,
    'widget-section': 1,
    'policy-section': 2,
    'partners': 3
  };

  \$boxes = $(\$boxes.get().sort(function(a, b){
    var \$a = $(a);
    var \$b = $(b);
    var aSlug = slugify(getLabel(\$a, 0));
    var bSlug = slugify(getLabel(\$b, 0));
    var aRank = Object.prototype.hasOwnProperty.call(preferredOrder, aSlug) ? preferredOrder[aSlug] : 999;
    var bRank = Object.prototype.hasOwnProperty.call(preferredOrder, bSlug) ? preferredOrder[bSlug] : 999;
    if (aRank !== bRank) return aRank - bRank;
    return aSlug.localeCompare(bSlug);
  }));

  // Re-append in sorted order so visual order and tab order match.
  var \$container = $('#normal-sortables');
  \$boxes.each(function(){ \$container.append(this); });

  function activate(index) {
    \$boxes.each(function(i){
      var isActive = i === index;
      $(this).toggle(isActive);
      \$nav.find('.lc-acf-tab-btn').eq(i)
        .toggleClass('is-active', isActive)
        .attr('aria-selected', isActive ? 'true' : 'false');
    });
    try { localStorage.setItem(stateKey, String(index)); } catch (e) {}

    var tabSlug = \$nav.find('.lc-acf-tab-btn').eq(index).data('tabSlug');
    if (tabSlug) {
      try {
        var u = new URL(window.location.href);
        u.searchParams.set(urlKey, tabSlug);
        window.history.replaceState({}, '', u.toString());
      } catch (e) {}
    }
  }

  \$boxes.each(function(i){
    var label = getLabel($(this), i);
    var \$btn = $('<button type="button" class="lc-acf-tab-btn" role="tab" aria-selected="false"></button>');
    \$btn.text(label);
    \$btn.data('tabSlug', slugify(label) || ('section-' + (i + 1)));
    \$btn.on('click', function(){ activate(i); });
    \$nav.append(\$btn);
  });

  \$boxes.first().before(\$nav);

  var initial = 0;
  var hasUrlSelection = false;
  try {
    var u0 = new URL(window.location.href);
    var wanted = u0.searchParams.get(urlKey);
    if (wanted) {
      \$nav.find('.lc-acf-tab-btn').each(function(i){
        if ($(this).data('tabSlug') === wanted) {
          initial = i;
          hasUrlSelection = true;
          return false;
        }
      });
    }
  } catch (e) {}

  try {
    if (!hasUrlSelection) {
      var saved = parseInt(localStorage.getItem(stateKey), 10);
      if (!isNaN(saved) && saved >= 0 && saved < \$boxes.length) initial = saved;
    }
  } catch (e) {}

  activate(initial);
});
JS);
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

/* =========================================================
 * [ADMIN] ซ่อน Brevo web push meta box
 * - กล่องนี้มาจากปลั๊กอิน mailin (Brevo)
 * - ซ่อนสำหรับหน้าแก้ไขโพสต์ทั้งหมดที่ถูกเพิ่มกล่อง
 * ========================================================= */
add_action('add_meta_boxes', function ($post_type) {
    if (!$post_type) return;
    remove_meta_box('sib_push_meta_box', $post_type, 'normal');
    remove_meta_box('sib_push_meta_box', $post_type, 'side');
    remove_meta_box('sib_push_meta_box', $post_type, 'advanced');
}, 999);

add_action('admin_head', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'post') return;

    echo '<style>
      #sib_push_meta_box { display: none !important; }
    </style>';
}, 999);

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
