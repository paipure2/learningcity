<?php
/**
 * Plugin Name: LearningCity Analytics
 * Description: Track course/location interactions, popup opens, and search keywords with dashboard reporting.
 * Version: 1.0.2
 * Author: LearningCity
 */

if (!defined('ABSPATH')) {
    exit;
}

final class LearningCity_Analytics {
    const VERSION = '1.0.2';
    const DB_VERSION = '1';
    const DB_VERSION_OPTION = 'lc_analytics_db_version';

    public static function init() {
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);

        add_action('plugins_loaded', [__CLASS__, 'maybe_upgrade']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_frontend_tracker'], 99);

        add_action('template_redirect', [__CLASS__, 'track_page_views'], 1);

        add_action('wp_ajax_lc_analytics_track', [__CLASS__, 'ajax_track']);
        add_action('wp_ajax_nopriv_lc_analytics_track', [__CLASS__, 'ajax_track']);

        add_action('admin_menu', [__CLASS__, 'register_admin_page']);
        add_action('wp_dashboard_setup', [__CLASS__, 'register_dashboard_widget']);
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'lc_analytics_events';
    }

    public static function activate() {
        self::create_table();
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
    }

    public static function maybe_upgrade() {
        $version = (string) get_option(self::DB_VERSION_OPTION, '0');
        if ($version !== self::DB_VERSION) {
            self::create_table();
            update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        }
    }

    private static function create_table() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            object_type varchar(30) DEFAULT '' NOT NULL,
            object_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            keyword varchar(191) DEFAULT '' NOT NULL,
            context varchar(50) DEFAULT '' NOT NULL,
            session_id varchar(64) DEFAULT '' NOT NULL,
            user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            ip_hash varchar(64) DEFAULT '' NOT NULL,
            meta_json longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY object_lookup (object_type, object_id),
            KEY keyword (keyword),
            KEY created_at (created_at),
            KEY session_id (session_id)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    public static function enqueue_frontend_tracker() {
        if (is_admin()) return;
        $script_path = plugin_dir_path(__FILE__) . 'assets/js/tracker.js';
        $script_ver = file_exists($script_path) ? (string) filemtime($script_path) : self::VERSION;

        wp_enqueue_script(
            'lc-analytics-tracker',
            plugin_dir_url(__FILE__) . 'assets/js/tracker.js',
            [],
            $script_ver,
            true
        );

        wp_add_inline_script(
            'lc-analytics-tracker',
            'window.LCAnalyticsConfig = ' . wp_json_encode([
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('lc_analytics_track_nonce'),
            ]) . ';',
            'before'
        );
    }

    private static function get_or_create_session_id() {
        $cookie_name = 'lc_analytics_sid';
        $sid = isset($_COOKIE[$cookie_name]) ? sanitize_text_field(wp_unslash($_COOKIE[$cookie_name])) : '';

        if (!preg_match('/^[a-zA-Z0-9_-]{16,64}$/', $sid)) {
            try {
                $sid = wp_generate_uuid4();
            } catch (Throwable $e) {
                $sid = md5(uniqid('lc', true));
            }

            $expire = time() + YEAR_IN_SECONDS;
            $secure = is_ssl();
            $httponly = true;

            setcookie($cookie_name, $sid, $expire, COOKIEPATH ?: '/', COOKIE_DOMAIN, $secure, $httponly);
            if (COOKIEPATH !== SITECOOKIEPATH) {
                setcookie($cookie_name, $sid, $expire, SITECOOKIEPATH ?: '/', COOKIE_DOMAIN, $secure, $httponly);
            }
            $_COOKIE[$cookie_name] = $sid;
        }

        return $sid;
    }

    private static function normalize_keyword($keyword) {
        $keyword = wp_strip_all_tags((string) $keyword);
        $keyword = html_entity_decode($keyword, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $keyword = preg_replace('/\s+/u', ' ', $keyword);
        $keyword = trim((string) $keyword);
        if (function_exists('mb_substr')) {
            $keyword = mb_substr($keyword, 0, 191, 'UTF-8');
        } else {
            $keyword = substr($keyword, 0, 191);
        }
        return $keyword;
    }

    private static function normalize_email_keyword($email) {
        $email = strtolower(trim((string) $email));
        $email = sanitize_email($email);
        if ($email === '' || !is_email($email)) {
            return '';
        }
        return self::normalize_keyword($email);
    }

    private static function normalize_event_type($type) {
        $type = sanitize_key((string) $type);
        $allowed = [
            'course_view',
            'location_view',
            'course_popup_click',
            'course_click',
            'course_learning_link_click',
            'location_click',
            'search_keyword',
            'search_popup_open',
            'course_notify_subscribe',
        ];
        if (!in_array($type, $allowed, true)) {
            return '';
        }
        return $type;
    }

    public static function track_event($data) {
        global $wpdb;

        $event_type = self::normalize_event_type($data['event_type'] ?? '');
        if ($event_type === '') return false;

        $object_type = sanitize_key((string) ($data['object_type'] ?? ''));
        $object_id = absint($data['object_id'] ?? 0);
        $keyword = self::normalize_keyword($data['keyword'] ?? '');
        $context = sanitize_key((string) ($data['context'] ?? ''));

        if ($event_type === 'course_notify_subscribe') {
            $keyword = self::normalize_email_keyword($data['keyword'] ?? '');
            if ($keyword === '') {
                return false;
            }
            if ($object_type === '') {
                $object_type = 'course';
            }
        }

        $session_id = self::get_or_create_session_id();
        $user_id = get_current_user_id();

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $ip_hash = $ip ? hash('sha256', $ip . wp_salt('auth')) : '';

        $table = self::table_name();

        $inserted = (bool) $wpdb->insert(
            $table,
            [
                'event_type' => $event_type,
                'object_type' => $object_type,
                'object_id' => $object_id,
                'keyword' => $keyword,
                'context' => $context,
                'session_id' => $session_id,
                'user_id' => $user_id,
                'ip_hash' => $ip_hash,
                'meta_json' => '',
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        if (
            $inserted &&
            $event_type === 'course_learning_link_click' &&
            $object_type === 'course' &&
            $object_id > 0
        ) {
            self::accumulate_course_attendance_hours($object_id);
        }

        return $inserted;
    }

    private static function accumulate_course_attendance_hours($course_id) {
        $course_id = absint($course_id);
        if ($course_id <= 0 || get_post_type($course_id) !== 'course') {
            return;
        }

        $minutes = (float) get_post_meta($course_id, 'total_minutes', true);
        if ($minutes <= 0) {
            return;
        }

        $delta_hours = $minutes / 60;
        if ($delta_hours <= 0) {
            return;
        }

        $current_hours = (float) get_post_meta($course_id, 'total_attendance_hours', true);
        $next_hours = $current_hours + $delta_hours;

        // Keep a compact numeric value for ACF/meta while preserving hour precision.
        update_post_meta($course_id, 'total_attendance_hours', round($next_hours, 2));
    }

    public static function track_page_views() {
        if (is_admin() || wp_doing_ajax()) return;

        if (is_singular('course')) {
            self::track_event([
                'event_type'  => 'course_view',
                'object_type' => 'course',
                'object_id'   => get_queried_object_id(),
                'context'     => 'page',
            ]);
            return;
        }

        if (is_singular('location')) {
            self::track_event([
                'event_type'  => 'location_view',
                'object_type' => 'location',
                'object_id'   => get_queried_object_id(),
                'context'     => 'redirect',
            ]);
            return;
        }

        if (is_page_template('page-blm.php')) {
            $place_id = isset($_GET['place']) ? absint($_GET['place']) : 0;
            if ($place_id > 0 && get_post_type($place_id) === 'location') {
                self::track_event([
                    'event_type'  => 'location_view',
                    'object_type' => 'location',
                    'object_id'   => $place_id,
                    'context'     => 'map',
                ]);
            }
        }
    }

    public static function ajax_track() {
        check_ajax_referer('lc_analytics_track_nonce', 'nonce');

        $payload = wp_unslash($_POST);
        $ok = self::track_event([
            'event_type'  => isset($payload['event_type']) ? sanitize_text_field($payload['event_type']) : '',
            'object_type' => isset($payload['object_type']) ? sanitize_text_field($payload['object_type']) : '',
            'object_id'   => isset($payload['object_id']) ? absint($payload['object_id']) : 0,
            'keyword'     => isset($payload['keyword']) ? sanitize_text_field($payload['keyword']) : '',
            'context'     => isset($payload['context']) ? sanitize_text_field($payload['context']) : '',
        ]);

        if (!$ok) {
            wp_send_json_error(['message' => 'track failed'], 400);
        }

        wp_send_json_success(['tracked' => true]);
    }

    public static function register_admin_page() {
        add_submenu_page(
            'index.php',
            'Learning Analytics',
            'Learning Analytics',
            'manage_options',
            'lc-analytics',
            [__CLASS__, 'render_admin_page']
        );
    }

    public static function register_dashboard_widget() {
        wp_add_dashboard_widget(
            'lc_analytics_dashboard_widget',
            'LearningCity Analytics',
            [__CLASS__, 'render_dashboard_widget']
        );
    }

    private static function render_admin_page_ajax_script() {
        echo "<script>
window.LCAnalyticsShowTooltip = function (el, text) {
  var host = el.closest('[data-tooltip-host=\"1\"]');
  if (!host) return;
  var tip = host.querySelector('[data-floating-tip=\"1\"]');
  if (!tip) return;

  tip.textContent = text || '';
  tip.style.opacity = '1';
  tip.style.visibility = 'hidden';
  tip.style.display = 'block';

  var hostRect = host.getBoundingClientRect();
  var elRect = el.getBoundingClientRect();
  var tipRect = tip.getBoundingClientRect();

  var left = (elRect.left - hostRect.left) + (elRect.width / 2) - (tipRect.width / 2);
  var maxLeft = host.clientWidth - tipRect.width;
  if (left < 0) left = 0;
  if (left > maxLeft) left = maxLeft;

  var top = (elRect.top - hostRect.top) - tipRect.height - 8;
  if (top < 0) top = 0;

  tip.style.left = left + 'px';
  tip.style.top = top + 'px';
  tip.style.visibility = 'visible';
};

window.LCAnalyticsHideTooltip = function (el) {
  var host = el.closest('[data-tooltip-host=\"1\"]');
  if (!host) return;
  var tip = host.querySelector('[data-floating-tip=\"1\"]');
  if (!tip) return;
  tip.style.opacity = '0';
};

document.addEventListener('click', async function (event) {
  var trigger = event.target.closest('[data-analytics-load-more=\"1\"]');
  if (!trigger) return;

  event.preventDefault();

  var url = trigger.getAttribute('href');
  var sectionKey = trigger.getAttribute('data-section');
  if (!url || !sectionKey) return;

  var section = document.querySelector('[data-analytics-section=\"' + sectionKey + '\"]');
  if (!section) return;

  var originalText = trigger.textContent;
  trigger.textContent = 'Loading...';
  trigger.setAttribute('aria-disabled', 'true');
  trigger.classList.add('disabled');

  try {
    var response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      throw new Error('Request failed');
    }

    var html = await response.text();
    var parser = new DOMParser();
    var doc = parser.parseFromString(html, 'text/html');
    var nextSection = doc.querySelector('[data-analytics-section=\"' + sectionKey + '\"]');
    if (!nextSection) {
      throw new Error('Section not found');
    }

    section.replaceWith(nextSection);
  } catch (error) {
    trigger.textContent = originalText;
    trigger.removeAttribute('aria-disabled');
    trigger.classList.remove('disabled');
    console.error(error);
  }
});
</script>";
    }

    private static function get_range_options() {
        return [
            'all' => [
                'days' => 0,
                'label' => 'All time',
                'description' => 'ข้อมูลสะสมทั้งหมด',
            ],
            '7' => [
                'days' => 7,
                'label' => '7 วัน',
                'description' => '7 วันล่าสุด',
            ],
            '30' => [
                'days' => 30,
                'label' => '30 วัน',
                'description' => '30 วันล่าสุด',
            ],
            '90' => [
                'days' => 90,
                'label' => '90 วัน',
                'description' => '90 วันล่าสุด',
            ],
            '180' => [
                'days' => 180,
                'label' => '6 เดือน',
                'description' => '6 เดือนล่าสุด',
            ],
            '365' => [
                'days' => 365,
                'label' => '1 ปี',
                'description' => '1 ปีล่าสุด',
            ],
        ];
    }

    private static function get_selected_range_key() {
        $ranges = self::get_range_options();
        $range_key = isset($_GET['range']) ? sanitize_key(wp_unslash($_GET['range'])) : '30';
        return isset($ranges[$range_key]) ? $range_key : '30';
    }

    private static function get_selected_range() {
        $ranges = self::get_range_options();
        $selected_key = self::get_selected_range_key();
        return $ranges[$selected_key];
    }

    private static function get_section_limit($key, $default = 10) {
        $param_key = sanitize_key($key) . '_limit';
        $value = isset($_GET[$param_key]) ? absint(wp_unslash($_GET[$param_key])) : absint($default);
        if ($value < absint($default)) {
            $value = absint($default);
        }
        if ($value > 100) {
            $value = 100;
        }
        return $value;
    }

    private static function build_admin_url(array $args = []) {
        $params = ['page' => 'lc-analytics'];

        if (isset($_GET['range'])) {
            $params['range'] = sanitize_key(wp_unslash($_GET['range']));
        }

        $preserved_limits = [
            'courses_limit',
            'categories_limit',
            'providers_limit',
            'locations_limit',
            'notify_limit',
        ];

        foreach ($preserved_limits as $limit_key) {
            if (isset($_GET[$limit_key])) {
                $params[$limit_key] = absint(wp_unslash($_GET[$limit_key]));
            }
        }

        foreach ($args as $key => $value) {
            if ($value === null) {
                unset($params[$key]);
                continue;
            }
            $params[$key] = $value;
        }

        return add_query_arg($params, admin_url('index.php'));
    }

    private static function render_range_filters($selected_key) {
        $ranges = self::get_range_options();

        echo '<div style="display:flex;flex-wrap:wrap;gap:8px;margin:12px 0 18px;">';
        foreach ($ranges as $key => $range) {
            $is_active = ($key === $selected_key);
            $url = self::build_admin_url(['range' => $key]);
            $styles = $is_active
                ? 'background:#2271b1;border-color:#2271b1;color:#fff;'
                : 'background:#fff;border-color:#dcdcde;color:#1d2327;';

            echo '<a href="' . esc_url($url) . '" class="button" style="border-radius:999px;padding:4px 14px;' . esc_attr($styles) . '">';
            echo esc_html($range['label']);
            echo '</a>';
        }
        echo '</div>';
    }

    private static function get_count($event_types, $days = 7) {
        global $wpdb;
        static $cache = [];

        $table = self::table_name();
        $event_types = (array) $event_types;
        $event_types = array_filter(array_map([__CLASS__, 'normalize_event_type'], $event_types));
        if (empty($event_types)) return 0;
        sort($event_types);

        $cache_key = implode('|', $event_types) . '|' . absint($days);
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $placeholders = implode(', ', array_fill(0, count($event_types), '%s'));
        $sql = "SELECT COUNT(*) FROM {$table} WHERE event_type IN ({$placeholders})";
        $params = $event_types;

        if ($days > 0) {
            $sql .= ' AND created_at >= DATE_SUB(%s, INTERVAL %d DAY)';
            $params[] = current_time('mysql');
            $params[] = absint($days);
        }

        $cache[$cache_key] = (int) $wpdb->get_var($wpdb->prepare($sql, $params));
        return $cache[$cache_key];
    }

    private static function get_top_keywords($days = 30, $limit = 10) {
        global $wpdb;

        $table = self::table_name();
        $sql = "SELECT keyword, COUNT(*) AS total
                FROM {$table}
                WHERE event_type = 'search_keyword'
                  AND keyword <> ''";
        $params = [];

        if ($days > 0) {
            $sql .= ' AND created_at >= DATE_SUB(%s, INTERVAL %d DAY)';
            $params[] = current_time('mysql');
            $params[] = absint($days);
        }

        $sql .= ' GROUP BY keyword
                  ORDER BY total DESC
                  LIMIT %d';
        $params[] = absint($limit);

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    private static function get_unique_keyword_count($event_type, $days = 7) {
        global $wpdb;

        $table = self::table_name();
        $event_type = self::normalize_event_type($event_type);
        if ($event_type === '') return 0;

        $sql = "SELECT COUNT(DISTINCT keyword)
                FROM {$table}
                WHERE event_type = %s
                  AND keyword <> ''";
        $params = [$event_type];

        if ($days > 0) {
            $sql .= ' AND created_at >= DATE_SUB(%s, INTERVAL %d DAY)';
            $params[] = current_time('mysql');
            $params[] = absint($days);
        }

        return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    private static function get_daily_unique_visitors($days = 30) {
        global $wpdb;
        static $cache = [];

        $cache_key = absint($days);
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $table = self::table_name();
        $sql = "SELECT DATE(created_at) AS event_date, COUNT(DISTINCT session_id) AS total
                FROM {$table}
                WHERE session_id <> ''";
        $params = [];

        if ($days > 0) {
            $sql .= ' AND created_at >= DATE_SUB(%s, INTERVAL %d DAY)';
            $params[] = current_time('mysql');
            $params[] = absint($days);
        }

        $sql .= ' GROUP BY DATE(created_at)
                  ORDER BY event_date ASC';

        $rows = $wpdb->get_results($wpdb->prepare($sql, $params));
        $totals_by_date = [];
        $min_ts = null;
        $max_ts = null;

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $date_key = (string) ($row->event_date ?? '');
                $total = absint($row->total ?? 0);
                if ($date_key === '') {
                    continue;
                }

                $totals_by_date[$date_key] = $total;
                $ts = strtotime($date_key . ' 00:00:00');
                if ($ts === false) {
                    continue;
                }
                if ($min_ts === null || $ts < $min_ts) {
                    $min_ts = $ts;
                }
                if ($max_ts === null || $ts > $max_ts) {
                    $max_ts = $ts;
                }
            }
        }

        if ($days > 0) {
            $max_ts = strtotime(current_time('Y-m-d') . ' 00:00:00');
            $min_ts = strtotime('-' . max(0, $days - 1) . ' days', $max_ts);
        } elseif ($min_ts === null || $max_ts === null) {
            $cache[$cache_key] = [];
            return $cache[$cache_key];
        }

        $series = [];
        for ($ts = $min_ts; $ts <= $max_ts; $ts = strtotime('+1 day', $ts)) {
            $date_key = gmdate('Y-m-d', $ts);
            $series[] = (object) [
                'date_key' => $date_key,
                'label' => date_i18n('j M', $ts),
                'total' => isset($totals_by_date[$date_key]) ? absint($totals_by_date[$date_key]) : 0,
            ];
        }

        $cache[$cache_key] = $series;
        return $cache[$cache_key];
    }

    private static function get_unique_visitors_count($days = 30) {
        global $wpdb;
        static $cache = [];

        $cache_key = absint($days);
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $table = self::table_name();
        $sql = "SELECT COUNT(DISTINCT session_id)
                FROM {$table}
                WHERE session_id <> ''";
        $params = [];

        if ($days > 0) {
            $sql .= ' AND created_at >= DATE_SUB(%s, INTERVAL %d DAY)';
            $params[] = current_time('mysql');
            $params[] = absint($days);
        }

        $cache[$cache_key] = (int) $wpdb->get_var($wpdb->prepare($sql, $params));
        return $cache[$cache_key];
    }

    private static function get_top_objects($object_type, array $events, $days = 30, $limit = 10) {
        global $wpdb;
        static $cache = [];

        $table = self::table_name();
        $object_type = sanitize_key($object_type);
        $events = array_values(array_filter(array_map([__CLASS__, 'normalize_event_type'], $events)));
        if (empty($events)) return [];
        sort($events);

        $cache_key = $object_type . '|' . implode('|', $events) . '|' . absint($days);
        if (!isset($cache[$cache_key])) {
            $event_placeholders = implode(', ', array_fill(0, count($events), '%s'));

            $sql = "SELECT object_id, COUNT(*) AS total
                    FROM {$table}
                    WHERE object_type = %s
                      AND object_id > 0
                      AND event_type IN ({$event_placeholders})";
            $params = array_merge([$object_type], $events);

            if ($days > 0) {
                $sql .= ' AND created_at >= DATE_SUB(%s, INTERVAL %d DAY)';
                $params[] = current_time('mysql');
                $params[] = absint($days);
            }

            $sql .= ' GROUP BY object_id
                      ORDER BY total DESC';

            $cache[$cache_key] = $wpdb->get_results($wpdb->prepare($sql, $params));
        }

        $rows = is_array($cache[$cache_key]) ? $cache[$cache_key] : [];
        if ($limit > 0) {
            return array_slice($rows, 0, absint($limit));
        }
        return $rows;
    }

    private static function get_course_interest_rows(array $events, $days = 30) {
        return self::get_top_objects('course', $events, $days, 0);
    }

    private static function get_top_course_categories(array $events, $days = 30, $limit = 10) {
        $rows = self::get_course_interest_rows($events, $days);
        if (empty($rows) || !is_array($rows)) {
            return [];
        }

        $category_totals = [];

        foreach ($rows as $row) {
            $course_id = absint($row->object_id ?? 0);
            $interest_total = absint($row->total ?? 0);
            if ($course_id <= 0 || $interest_total <= 0 || get_post_status($course_id) !== 'publish') {
                continue;
            }

            $terms = get_the_terms($course_id, 'course_category');
            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }

            $parent_term_ids = [];
            foreach ($terms as $term) {
                if (!($term instanceof WP_Term)) {
                    continue;
                }

                $ancestor_ids = get_ancestors($term->term_id, 'course_category', 'taxonomy');
                $parent_term_id = !empty($ancestor_ids) ? (int) end($ancestor_ids) : (int) $term->term_id;
                if ($parent_term_id > 0) {
                    $parent_term_ids[$parent_term_id] = true;
                }
            }

            foreach (array_keys($parent_term_ids) as $parent_term_id) {
                if (!isset($category_totals[$parent_term_id])) {
                    $resolved_term = get_term($parent_term_id, 'course_category');
                    if (!$resolved_term || is_wp_error($resolved_term)) {
                        continue;
                    }

                    $category_totals[$parent_term_id] = (object) [
                        'term_id' => $parent_term_id,
                        'name' => $resolved_term->name,
                        'total' => 0,
                        'children' => [],
                    ];
                }

                $category_totals[$parent_term_id]->total += $interest_total;
            }

            foreach ($terms as $term) {
                if (!($term instanceof WP_Term)) {
                    continue;
                }

                $ancestor_ids = get_ancestors($term->term_id, 'course_category', 'taxonomy');
                $parent_term_id = !empty($ancestor_ids) ? (int) end($ancestor_ids) : (int) $term->term_id;
                $term_id = (int) $term->term_id;

                if ($parent_term_id <= 0 || $term_id <= 0 || !isset($category_totals[$parent_term_id])) {
                    continue;
                }

                if ($term_id === $parent_term_id) {
                    continue;
                }

                if (!isset($category_totals[$parent_term_id]->children[$term_id])) {
                    $category_totals[$parent_term_id]->children[$term_id] = (object) [
                        'term_id' => $term_id,
                        'name' => $term->name,
                        'total' => 0,
                    ];
                }

                $category_totals[$parent_term_id]->children[$term_id]->total += $interest_total;
            }
        }

        if (empty($category_totals)) {
            return [];
        }

        usort($category_totals, function ($a, $b) {
            $a_total = absint($a->total ?? 0);
            $b_total = absint($b->total ?? 0);
            if ($a_total === $b_total) {
                return strcmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
            }
            return $b_total <=> $a_total;
        });

        foreach ($category_totals as $category) {
            if (empty($category->children) || !is_array($category->children)) {
                $category->children = [];
                continue;
            }

            $children = array_values($category->children);
            usort($children, function ($a, $b) {
                $a_total = absint($a->total ?? 0);
                $b_total = absint($b->total ?? 0);
                if ($a_total === $b_total) {
                    return strcmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
                }
                return $b_total <=> $a_total;
            });
            $category->children = $children;
        }

        return array_slice($category_totals, 0, absint($limit));
    }

    private static function get_top_course_providers(array $events, $days = 30, $limit = 10) {
        $rows = self::get_course_interest_rows($events, $days);
        if (empty($rows) || !is_array($rows)) {
            return [];
        }

        $provider_totals = [];

        foreach ($rows as $row) {
            $course_id = absint($row->object_id ?? 0);
            $interest_total = absint($row->total ?? 0);
            if ($course_id <= 0 || $interest_total <= 0 || get_post_status($course_id) !== 'publish') {
                continue;
            }

            $terms = get_the_terms($course_id, 'course_provider');
            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }

            $provider_term_ids = [];
            foreach ($terms as $term) {
                if ($term instanceof WP_Term) {
                    $provider_term_ids[(int) $term->term_id] = true;
                }
            }

            foreach (array_keys($provider_term_ids) as $provider_term_id) {
                if (!isset($provider_totals[$provider_term_id])) {
                    $provider_term = get_term($provider_term_id, 'course_provider');
                    if (!$provider_term || is_wp_error($provider_term)) {
                        continue;
                    }

                    $provider_totals[$provider_term_id] = (object) [
                        'term_id' => $provider_term_id,
                        'name' => $provider_term->name,
                        'total' => 0,
                    ];
                }

                $provider_totals[$provider_term_id]->total += $interest_total;
            }
        }

        if (empty($provider_totals)) {
            return [];
        }

        usort($provider_totals, function ($a, $b) {
            $a_total = absint($a->total ?? 0);
            $b_total = absint($b->total ?? 0);
            if ($a_total === $b_total) {
                return strcmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
            }
            return $b_total <=> $a_total;
        });

        return array_slice($provider_totals, 0, absint($limit));
    }

    public static function render_dashboard_widget() {
        $course_views_combined = self::get_count(['course_view', 'course_popup_click'], 7);
        $location_views = self::get_count(['location_view'], 7);
        $search_logs = self::get_count(['search_keyword'], 7);
        $course_learning_link_clicks = self::get_count(['course_learning_link_click'], 7);
        $course_notify_clicks = self::get_count(['course_notify_subscribe'], 7);
        $course_notify_unique_emails = self::get_unique_keyword_count('course_notify_subscribe', 7);
        $top_keywords = self::get_top_keywords(7, 5);

        echo '<p><strong>สรุป 7 วันล่าสุด</strong></p>';
        echo '<ul style="margin:0 0 10px 18px;list-style:disc;">';
        echo '<li>การเข้าชมคอร์ส (รวม Popup): <strong>' . absint($course_views_combined) . '</strong></li>';
        echo '<li>เข้าแหล่งเรียนรู้: <strong>' . absint($location_views) . '</strong></li>';
        echo '<li>คำค้นหา: <strong>' . absint($search_logs) . '</strong></li>';
        echo '<li>คลิกปุ่มเริ่มต้นเรียน: <strong>' . absint($course_learning_link_clicks) . '</strong></li>';
        echo '<li>กดแจ้งเตือนคอร์ส: <strong>' . absint($course_notify_clicks) . '</strong></li>';
        echo '<li>อีเมลแจ้งเตือนไม่ซ้ำ: <strong>' . absint($course_notify_unique_emails) . '</strong></li>';
        echo '</ul>';

        echo '<p><strong>ค้นหาบ่อย (7 วัน)</strong></p>';
        if (empty($top_keywords)) {
            echo '<p style="margin-top:0;">ยังไม่มีข้อมูล</p>';
        } else {
            echo '<ul style="margin:0 0 10px 18px;list-style:disc;">';
            foreach ($top_keywords as $row) {
                echo '<li>' . esc_html($row->keyword) . ' <strong>(' . absint($row->total) . ')</strong></li>';
            }
            echo '</ul>';
        }

        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('index.php?page=lc-analytics')) . '">เปิดรายงานเต็ม</a></p>';
    }

    public static function render_admin_page() {
        $selected_range_key = self::get_selected_range_key();
        $selected_range = self::get_selected_range();
        $days = (int) $selected_range['days'];
        $range_label = (string) $selected_range['label'];
        $range_description = (string) $selected_range['description'];
        $courses_limit = self::get_section_limit('courses', 10);
        $categories_limit = self::get_section_limit('categories', 10);
        $providers_limit = self::get_section_limit('providers', 10);
        $locations_limit = self::get_section_limit('locations', 10);
        $notify_limit = self::get_section_limit('notify', 10);

        $course_views_combined = self::get_count(['course_view', 'course_popup_click'], $days);
        $location_views = self::get_count(['location_view'], $days);
        $search_logs = self::get_count(['search_keyword'], $days);
        $course_clicks = self::get_count(['course_click'], $days);
        $course_learning_link_clicks = self::get_count(['course_learning_link_click'], $days);
        $location_clicks = self::get_count(['location_click'], $days);
        $search_popup_opens = self::get_count(['search_popup_open'], $days);
        $course_notify_clicks = self::get_count(['course_notify_subscribe'], $days);
        $course_notify_unique_emails = self::get_unique_keyword_count('course_notify_subscribe', $days);
        $unique_visitors = self::get_unique_visitors_count($days);

        $top_keywords = self::get_top_keywords($days, 15);
        $top_courses = self::get_top_objects('course', ['course_view', 'course_click', 'course_popup_click'], $days, $courses_limit);
        $top_locations = self::get_top_objects('location', ['location_view', 'location_click'], $days, $locations_limit);
        $top_notify_courses = self::get_top_objects('course', ['course_notify_subscribe'], $days, $notify_limit);
        $top_course_categories = self::get_top_course_categories(['course_view', 'course_popup_click', 'course_click'], $days, $categories_limit);
        $top_course_providers = self::get_top_course_providers(['course_view', 'course_popup_click', 'course_click'], $days, $providers_limit);
        $daily_unique_visitors = self::get_daily_unique_visitors($days);

        echo '<div class="wrap">';
        echo '<h1>LearningCity Analytics</h1>';
        echo '<p>เลือกช่วงเวลาที่ต้องการดูรายงานได้จากด้านล่าง</p>';
        self::render_range_filters($selected_range_key);
        echo '<p><strong>สรุปข้อมูลย้อนหลัง ' . esc_html($range_description) . '</strong></p>';

        echo '<div style="display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:12px;max-width:980px;margin:16px 0 20px;">';
        self::card('จำนวนคนเข้า', $unique_visitors);
        self::card('ยอดเข้าชมคอร์สรวม (รวมป๊อปอัป)', $course_views_combined);
        self::card('ยอดเข้าชมแหล่งเรียนรู้', $location_views);
        self::card('คลิกดูคอร์ส', $course_clicks);
        self::card('คลิกปุ่มเริ่มต้นเรียน', $course_learning_link_clicks);
        self::card('คลิกดูแหล่งเรียนรู้', $location_clicks);
        self::card('เปิดป๊อปอัปค้นหา', $search_popup_opens);
        self::card('จำนวนคำค้นหา', $search_logs);
        self::card('กดแจ้งเตือนคอร์ส', $course_notify_clicks);
        self::card('อีเมลแจ้งเตือนไม่ซ้ำ', $course_notify_unique_emails);
        echo '</div>';

        self::render_daily_unique_visitors_chart($daily_unique_visitors, 'จำนวนคนเข้าแต่ละวัน (' . $range_label . ')');
        self::render_top_courses_chart($top_courses, 'คอร์สที่คนสนใจมากสุด (' . $range_label . ')', 'courses_limit', $courses_limit, 'chart-courses');
        self::render_top_course_categories_chart($top_course_categories, 'หมวดคอร์สที่คนสนใจมากสุด (' . $range_label . ')', 'categories_limit', $categories_limit, 'chart-categories');
        self::render_top_course_providers_chart($top_course_providers, 'หน่วยงานที่คนสนใจมากสุด (' . $range_label . ')', 'providers_limit', $providers_limit, 'chart-providers');

        echo '<h2>คำค้นหาบ่อย (' . esc_html($range_label) . ')</h2>';
        self::render_simple_table($top_keywords, function ($row) {
            return [
                esc_html($row->keyword),
                '<strong>' . absint($row->total) . '</strong>',
            ];
        }, ['Keyword', 'Count']);

        self::render_rank_table_section('table-courses', 'คอร์สที่ถูกสนใจสูงสุด (' . $range_label . ')', $top_courses, function ($row) {
            $id = absint($row->object_id);
            $title = get_the_title($id);
            $url = get_permalink($id);
            $label = $title ? $title : ('Course #' . $id);
            $title_html = $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>' : esc_html($label);
            return [
                $title_html,
                '<strong>' . absint($row->total) . '</strong>',
            ];
        }, ['Course', 'Count'], 'courses_limit', $courses_limit);

        self::render_rank_table_section('table-categories', 'หมวดคอร์สที่คนสนใจสูงสุด (' . $range_label . ')', $top_course_categories, function ($row) {
            $term_id = absint($row->term_id ?? 0);
            $name = (string) ($row->name ?? '');
            $link = $term_id > 0 ? get_term_link($term_id, 'course_category') : '';
            $label = $name !== '' ? $name : ('Category #' . $term_id);
            if (is_wp_error($link)) $link = '';
            $name_html = $link ? '<a href="' . esc_url($link) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>' : esc_html($label);
            return [
                $name_html,
                '<strong>' . absint($row->total) . '</strong>',
            ];
        }, ['Course Category', 'Count'], 'categories_limit', $categories_limit);

        self::render_rank_table_section('table-providers', 'หน่วยงานที่คนสนใจสูงสุด (' . $range_label . ')', $top_course_providers, function ($row) {
            $term_id = absint($row->term_id ?? 0);
            $name = (string) ($row->name ?? '');
            $link = $term_id > 0 ? get_term_link($term_id, 'course_provider') : '';
            $label = $name !== '' ? $name : ('Provider #' . $term_id);
            if (is_wp_error($link)) $link = '';
            $name_html = $link ? '<a href="' . esc_url($link) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>' : esc_html($label);
            return [
                $name_html,
                '<strong>' . absint($row->total) . '</strong>',
            ];
        }, ['Course Provider', 'Count'], 'providers_limit', $providers_limit);

        self::render_rank_table_section('table-locations', 'แหล่งเรียนรู้ที่ถูกสนใจสูงสุด (' . $range_label . ')', $top_locations, function ($row) {
            $id = absint($row->object_id);
            $title = get_the_title($id);
            $url = get_permalink($id);
            $label = $title ? $title : ('Location #' . $id);
            $title_html = $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>' : esc_html($label);
            return [
                $title_html,
                '<strong>' . absint($row->total) . '</strong>',
            ];
        }, ['Location', 'Count'], 'locations_limit', $locations_limit);

        self::render_rank_table_section('table-notify', 'คอร์สที่ถูกกดแจ้งเตือนมากสุด (' . $range_label . ')', $top_notify_courses, function ($row) {
            $id = absint($row->object_id);
            $title = get_the_title($id);
            $url = get_permalink($id);
            $label = $title ? $title : ('Course #' . $id);
            $title_html = $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>' : esc_html($label);
            return [
                $title_html,
                '<strong>' . absint($row->total) . '</strong>',
            ];
        }, ['Course', 'Notify Count'], 'notify_limit', $notify_limit);

        self::render_admin_page_ajax_script();
        echo '</div>';
    }

    private static function card($label, $count) {
        echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:12px 14px;">';
        echo '<div style="font-size:13px;color:#646970;">' . esc_html($label) . '</div>';
        echo '<div style="font-size:30px;line-height:1.2;font-weight:700;margin-top:4px;">' . esc_html(number_format_i18n(absint($count))) . '</div>';
        echo '</div>';
    }

    private static function render_load_more_button($limit_key, $current_limit, $section_key, $step = 10) {
        $next_limit = absint($current_limit) + absint($step);
        $url = self::build_admin_url([$limit_key => $next_limit]);
        echo '<p style="margin:12px 0 0;">';
        echo '<a class="button" href="' . esc_url($url) . '" data-analytics-load-more="1" data-section="' . esc_attr($section_key) . '">Show 10 more</a>';
        echo '</p>';
    }

    private static function render_daily_unique_visitors_chart($rows, $title) {
        $rows = is_array($rows) ? $rows : [];
        $chart_height = 220;
        $plot_top = 40;
        $plot_height = 180;

        echo '<div style="max-width:980px;background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;margin:8px 0 24px;">';
        echo '<h2 style="margin:0 0 6px;">' . esc_html($title) . '</h2>';
        echo '<p style="margin:0 0 14px;color:#646970;">นับจากผู้เข้าชมไม่ซ้ำต่อวัน โดยอ้างอิง session ของผู้ใช้</p>';

        if (empty($rows)) {
            echo '<p style="margin:0;">ยังไม่มีข้อมูลสำหรับแสดงกราฟ</p>';
            echo '</div>';
            return;
        }

        $max_total = 1;
        foreach ($rows as $row) {
            $max_total = max($max_total, absint($row->total ?? 0));
        }

        $axis_steps = [1, 0.75, 0.5, 0.25, 0];

        echo '<div style="display:grid;grid-template-columns:56px minmax(0,1fr);gap:12px;align-items:start;">';
        echo '<div style="position:relative;height:' . absint($chart_height) . 'px;font-size:11px;color:#646970;">';
        foreach ($axis_steps as $step) {
            $value = (int) round($max_total * $step);
            $top = $plot_top + (int) round((1 - $step) * $plot_height);
            echo '<div style="position:absolute;top:' . absint($top) . 'px;right:0;transform:translateY(-50%);">' . esc_html(number_format_i18n($value)) . '</div>';
        }
        echo '</div>';

        echo '<div style="position:relative;padding-top:' . absint($plot_top) . 'px;" data-tooltip-host="1">';
        echo '<div data-floating-tip="1" style="position:absolute;left:0;top:0;opacity:0;pointer-events:none;transition:opacity .15s ease;background:#1d2327;color:#fff;border-radius:8px;padding:4px 8px;font-size:11px;white-space:nowrap;z-index:3;box-shadow:0 4px 12px rgba(0,0,0,.18);"></div>';
        foreach ($axis_steps as $step) {
            $top = $plot_top + (int) round((1 - $step) * $plot_height);
            echo '<div style="position:absolute;left:0;right:0;top:' . absint($top) . 'px;border-top:1px dashed #e2e8f0;"></div>';
        }

        echo '<div style="overflow-x:auto;overflow-y:visible;">';
        echo '<div style="position:relative;display:flex;align-items:flex-end;gap:8px;height:' . absint($chart_height) . 'px;">';
        foreach ($rows as $row) {
            $label = (string) ($row->label ?? '');
            $total = absint($row->total ?? 0);
            $height = $total > 0 ? max(6, (int) round(($total / $max_total) * $plot_height)) : 4;
            $tooltip_text = $label . ' - ' . number_format_i18n($total) . ' คน';
            $tooltip_json = wp_json_encode($tooltip_text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            echo '<div style="min-width:28px;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;">';
            echo '<div style="position:relative;display:flex;align-items:flex-end;justify-content:center;width:100%;height:' . absint($plot_height) . 'px;">';
            echo '<div title="' . esc_attr($tooltip_text) . '" onmouseenter=\'LCAnalyticsShowTooltip(this, ' . $tooltip_json . ')\' onmouseleave="LCAnalyticsHideTooltip(this)" style="width:18px;height:' . absint($height) . 'px;background:#2271b1;border-radius:8px 8px 0 0;"></div>';
            echo '</div>';
            echo '<div style="margin-top:6px;font-size:11px;line-height:1.2;color:#646970;writing-mode:vertical-rl;transform:rotate(180deg);height:44px;">' . esc_html($label) . '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private static function render_top_courses_chart($rows, $title, $limit_key, $current_limit, $section_key) {
        $rows = is_array($rows) ? $rows : [];

        echo '<div data-analytics-section="' . esc_attr($section_key) . '">';
        echo '<div style="max-width:980px;background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;margin:8px 0 24px;">';
        echo '<h2 style="margin:0 0 6px;">' . esc_html($title) . '</h2>';
        echo '<p style="margin:0 0 14px;color:#646970;">เรียงตามจำนวนการสนใจรวมของแต่ละคอร์ส เช่น เข้าหน้าคอร์ส เปิด popup และคลิกคอร์ส</p>';

        if (empty($rows)) {
            echo '<p style="margin:0;">ยังไม่มีข้อมูลสำหรับแสดงกราฟ</p>';
            echo '</div>';
            echo '</div>';
            return;
        }

        $max_total = 1;
        foreach ($rows as $row) {
            $max_total = max($max_total, absint($row->total ?? 0));
        }

        foreach ($rows as $index => $row) {
            $id = absint($row->object_id ?? 0);
            $total = absint($row->total ?? 0);
            $title_text = get_the_title($id);
            $url = get_permalink($id);
            $label = $title_text ? $title_text : ('Course #' . $id);
            $width = max(6, (int) round(($total / $max_total) * 100));

            echo '<div style="display:grid;grid-template-columns:36px minmax(220px,320px) minmax(260px,1fr) 70px;gap:12px;align-items:center;margin:0 0 10px;">';
            echo '<div style="font-size:12px;color:#646970;">#' . absint($index + 1) . '</div>';
            if ($url) {
                echo '<div><a href="' . esc_url($url) . '" target="_blank" rel="noopener" style="text-decoration:none;">' . esc_html($label) . '</a></div>';
            } else {
                echo '<div>' . esc_html($label) . '</div>';
            }
            echo '<div style="height:12px;background:#f0f0f1;border-radius:999px;overflow:hidden;">';
            echo '<div style="height:12px;width:' . absint($width) . '%;background:#2271b1;border-radius:999px;"></div>';
            echo '</div>';
            echo '<div style="text-align:right;font-weight:600;">' . absint($total) . '</div>';
            echo '</div>';
        }

        if (count($rows) >= $current_limit) {
            self::render_load_more_button($limit_key, $current_limit, $section_key);
        }
        echo '</div>';
        echo '</div>';
    }

    private static function render_top_course_categories_chart($rows, $title, $limit_key, $current_limit, $section_key) {
        $rows = is_array($rows) ? $rows : [];

        echo '<div data-analytics-section="' . esc_attr($section_key) . '">';
        echo '<div style="max-width:980px;background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;margin:8px 0 24px;">';
        echo '<h2 style="margin:0 0 6px;">' . esc_html($title) . '</h2>';
        echo '<p style="margin:0 0 14px;color:#646970;">เรียงตามจำนวนความสนใจรวมของคอร์สในแต่ละหมวด</p>';

        if (empty($rows)) {
            echo '<p style="margin:0;">ยังไม่มีข้อมูลสำหรับแสดงกราฟ</p>';
            echo '</div>';
            echo '</div>';
            return;
        }

        $max_total = 1;
        foreach ($rows as $row) {
            $max_total = max($max_total, absint($row->total ?? 0));
        }

        foreach ($rows as $index => $row) {
            $term_id = absint($row->term_id ?? 0);
            $name = (string) ($row->name ?? '');
            $url = $term_id > 0 ? get_term_link($term_id, 'course_category') : '';
            $label = $name !== '' ? $name : ('Category #' . $term_id);
            $total = absint($row->total ?? 0);
            $width = max(6, (int) round(($total / $max_total) * 100));
            $children = !empty($row->children) && is_array($row->children) ? $row->children : [];
            if (is_wp_error($url)) {
                $url = '';
            }

            echo '<div style="margin:0 0 10px;">';
            echo '<div style="display:grid;grid-template-columns:36px minmax(220px,320px) minmax(260px,1fr) 70px;gap:12px;align-items:center;">';
            echo '<div style="font-size:12px;color:#646970;">#' . absint($index + 1) . '</div>';
            if ($url) {
                echo '<div><a href="' . esc_url($url) . '" target="_blank" rel="noopener" style="text-decoration:none;">' . esc_html($label) . '</a></div>';
            } else {
                echo '<div>' . esc_html($label) . '</div>';
            }
            echo '<div style="height:12px;background:#f0f0f1;border-radius:999px;overflow:hidden;">';
            echo '<div style="height:12px;width:' . absint($width) . '%;background:#00a32a;border-radius:999px;"></div>';
            echo '</div>';
            echo '<div style="text-align:right;font-weight:600;">' . absint($total) . '</div>';
            echo '</div>';

            if (!empty($children)) {
                echo '<div style="margin:8px 0 0 48px;">';
                echo '<details style="background:#f6f7f7;border:1px solid #e0e0e0;border-radius:10px;padding:8px 12px;">';
                echo '<summary style="cursor:pointer;font-size:13px;font-weight:600;color:#1d2327;">ดูหมวดย่อย (' . absint(count($children)) . ')</summary>';
                echo '<div style="margin-top:10px;display:grid;gap:8px;">';

                $child_max_total = 1;
                foreach ($children as $child) {
                    $child_max_total = max($child_max_total, absint($child->total ?? 0));
                }

                foreach ($children as $child) {
                    $child_term_id = absint($child->term_id ?? 0);
                    $child_name = (string) ($child->name ?? '');
                    $child_total = absint($child->total ?? 0);
                    $child_width = max(6, (int) round(($child_total / $child_max_total) * 100));
                    $child_url = $child_term_id > 0 ? get_term_link($child_term_id, 'course_category') : '';
                    if (is_wp_error($child_url)) {
                        $child_url = '';
                    }

                    echo '<div style="display:grid;grid-template-columns:minmax(220px,1fr) minmax(160px,1fr) 64px;gap:12px;align-items:center;font-size:13px;">';
                    if ($child_url) {
                        echo '<div><a href="' . esc_url($child_url) . '" target="_blank" rel="noopener" style="text-decoration:none;">' . esc_html($child_name) . '</a></div>';
                    } else {
                        echo '<div>' . esc_html($child_name) . '</div>';
                    }
                    echo '<div style="height:10px;background:#e8f5e9;border-radius:999px;overflow:hidden;">';
                    echo '<div style="height:10px;width:' . absint($child_width) . '%;background:#4caf50;border-radius:999px;"></div>';
                    echo '</div>';
                    echo '<div style="text-align:right;font-weight:600;">' . absint($child_total) . '</div>';
                    echo '</div>';
                }

                echo '</div>';
                echo '</details>';
                echo '</div>';
            }

            echo '</div>';
        }

        if (count($rows) >= $current_limit) {
            self::render_load_more_button($limit_key, $current_limit, $section_key);
        }
        echo '</div>';
        echo '</div>';
    }

    private static function render_top_course_providers_chart($rows, $title, $limit_key, $current_limit, $section_key) {
        $rows = is_array($rows) ? $rows : [];

        echo '<div data-analytics-section="' . esc_attr($section_key) . '">';
        echo '<div style="max-width:980px;background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;margin:8px 0 24px;">';
        echo '<h2 style="margin:0 0 6px;">' . esc_html($title) . '</h2>';
        echo '<p style="margin:0 0 14px;color:#646970;">เรียงตามจำนวนความสนใจรวมของคอร์สในแต่ละหน่วยงาน</p>';

        if (empty($rows)) {
            echo '<p style="margin:0;">ยังไม่มีข้อมูลสำหรับแสดงกราฟ</p>';
            echo '</div>';
            echo '</div>';
            return;
        }

        $max_total = 1;
        foreach ($rows as $row) {
            $max_total = max($max_total, absint($row->total ?? 0));
        }

        foreach ($rows as $index => $row) {
            $term_id = absint($row->term_id ?? 0);
            $name = (string) ($row->name ?? '');
            $url = $term_id > 0 ? get_term_link($term_id, 'course_provider') : '';
            $label = $name !== '' ? $name : ('Provider #' . $term_id);
            $total = absint($row->total ?? 0);
            $width = max(6, (int) round(($total / $max_total) * 100));
            if (is_wp_error($url)) {
                $url = '';
            }

            echo '<div style="display:grid;grid-template-columns:36px minmax(220px,320px) minmax(260px,1fr) 70px;gap:12px;align-items:center;margin:0 0 10px;">';
            echo '<div style="font-size:12px;color:#646970;">#' . absint($index + 1) . '</div>';
            if ($url) {
                echo '<div><a href="' . esc_url($url) . '" target="_blank" rel="noopener" style="text-decoration:none;">' . esc_html($label) . '</a></div>';
            } else {
                echo '<div>' . esc_html($label) . '</div>';
            }
            echo '<div style="height:12px;background:#f0f0f1;border-radius:999px;overflow:hidden;">';
            echo '<div style="height:12px;width:' . absint($width) . '%;background:#8e44ad;border-radius:999px;"></div>';
            echo '</div>';
            echo '<div style="text-align:right;font-weight:600;">' . absint($total) . '</div>';
            echo '</div>';
        }

        if (count($rows) >= $current_limit) {
            self::render_load_more_button($limit_key, $current_limit, $section_key);
        }
        echo '</div>';
        echo '</div>';
    }

    private static function render_rank_table_section($section_key, $title, $rows, callable $map, array $headers, $limit_key, $current_limit) {
        echo '<div data-analytics-section="' . esc_attr($section_key) . '">';
        echo '<h2 style="margin-top:24px;">' . esc_html($title) . '</h2>';
        self::render_simple_table($rows, $map, $headers);
        if (is_array($rows) && count($rows) >= $current_limit) {
            self::render_load_more_button($limit_key, $current_limit, $section_key);
        }
        echo '</div>';
    }

    private static function render_simple_table($rows, callable $map, array $headers) {
        echo '<table class="widefat striped" style="max-width:980px;">';
        echo '<thead><tr>';
        foreach ($headers as $head) {
            echo '<th>' . esc_html($head) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if (empty($rows)) {
            echo '<tr><td colspan="' . count($headers) . '">ยังไม่มีข้อมูล</td></tr>';
        } else {
            foreach ($rows as $row) {
                $cells = (array) call_user_func($map, $row);
                echo '<tr>';
                foreach ($cells as $cell) {
                    echo '<td>' . wp_kses_post($cell) . '</td>';
                }
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }
}

LearningCity_Analytics::init();
