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

        wp_enqueue_script(
            'lc-analytics-tracker',
            plugin_dir_url(__FILE__) . 'assets/js/tracker.js',
            [],
            self::VERSION,
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

        return (bool) $wpdb->insert(
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

    private static function get_count($event_types, $days = 7) {
        global $wpdb;

        $table = self::table_name();
        $event_types = (array) $event_types;
        $event_types = array_filter(array_map([__CLASS__, 'normalize_event_type'], $event_types));
        if (empty($event_types)) return 0;

        $placeholders = implode(', ', array_fill(0, count($event_types), '%s'));
        $sql = "SELECT COUNT(*) FROM {$table} WHERE event_type IN ({$placeholders})";
        $params = $event_types;

        if ($days > 0) {
            $sql .= ' AND created_at >= DATE_SUB(%s, INTERVAL %d DAY)';
            $params[] = current_time('mysql');
            $params[] = absint($days);
        }

        return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    private static function get_top_keywords($days = 30, $limit = 10) {
        global $wpdb;

        $table = self::table_name();
        $sql = "SELECT keyword, COUNT(*) AS total
                FROM {$table}
                WHERE event_type = 'search_keyword'
                  AND keyword <> ''
                  AND created_at >= DATE_SUB(%s, INTERVAL %d DAY)
                GROUP BY keyword
                ORDER BY total DESC
                LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($sql, current_time('mysql'), absint($days), absint($limit)));
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

    private static function get_top_objects($object_type, array $events, $days = 30, $limit = 10) {
        global $wpdb;

        $table = self::table_name();
        $object_type = sanitize_key($object_type);
        $events = array_filter(array_map([__CLASS__, 'normalize_event_type'], $events));
        if (empty($events)) return [];

        $event_placeholders = implode(', ', array_fill(0, count($events), '%s'));

        $sql = "SELECT object_id, COUNT(*) AS total
                FROM {$table}
                WHERE object_type = %s
                  AND object_id > 0
                  AND event_type IN ({$event_placeholders})
                  AND created_at >= DATE_SUB(%s, INTERVAL %d DAY)
                GROUP BY object_id
                ORDER BY total DESC
                LIMIT %d";

        $params = array_merge([$object_type], $events, [current_time('mysql'), absint($days), absint($limit)]);
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    public static function render_dashboard_widget() {
        $course_views_combined = self::get_count(['course_view', 'course_popup_click'], 7);
        $location_views = self::get_count(['location_view'], 7);
        $search_logs = self::get_count(['search_keyword'], 7);
        $course_notify_clicks = self::get_count(['course_notify_subscribe'], 7);
        $course_notify_unique_emails = self::get_unique_keyword_count('course_notify_subscribe', 7);
        $top_keywords = self::get_top_keywords(7, 5);

        echo '<p><strong>สรุป 7 วันล่าสุด</strong></p>';
        echo '<ul style="margin:0 0 10px 18px;list-style:disc;">';
        echo '<li>การเข้าชมคอร์ส (รวม Popup): <strong>' . absint($course_views_combined) . '</strong></li>';
        echo '<li>เข้าแหล่งเรียนรู้: <strong>' . absint($location_views) . '</strong></li>';
        echo '<li>คำค้นหา: <strong>' . absint($search_logs) . '</strong></li>';
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
        $course_views_combined = self::get_count(['course_view', 'course_popup_click'], 7);
        $location_views = self::get_count(['location_view'], 7);
        $search_logs = self::get_count(['search_keyword'], 7);
        $course_clicks = self::get_count(['course_click'], 7);
        $location_clicks = self::get_count(['location_click'], 7);
        $search_popup_opens = self::get_count(['search_popup_open'], 7);
        $course_notify_clicks = self::get_count(['course_notify_subscribe'], 7);
        $course_notify_unique_emails = self::get_unique_keyword_count('course_notify_subscribe', 7);

        $top_keywords = self::get_top_keywords(30, 15);
        $top_courses = self::get_top_objects('course', ['course_view', 'course_click', 'course_popup_click'], 30, 10);
        $top_locations = self::get_top_objects('location', ['location_view', 'location_click'], 30, 10);
        $top_notify_courses = self::get_top_objects('course', ['course_notify_subscribe'], 30, 10);

        echo '<div class="wrap">';
        echo '<h1>LearningCity Analytics</h1>';
        echo '<p>สรุปข้อมูลย้อนหลัง 7 วัน</p>';

        echo '<div style="display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:12px;max-width:980px;margin:16px 0 20px;">';
        self::card('Course Views (incl. Popup)', $course_views_combined);
        self::card('Location Views', $location_views);
        self::card('Course Link Clicks', $course_clicks);
        self::card('Location Link Clicks', $location_clicks);
        self::card('Search Popup Opens', $search_popup_opens);
        self::card('Search Keywords', $search_logs);
        self::card('Course Notify Clicks', $course_notify_clicks);
        self::card('Unique Notify Emails', $course_notify_unique_emails);
        echo '</div>';

        echo '<h2>คำค้นหาบ่อย (30 วัน)</h2>';
        self::render_simple_table($top_keywords, function ($row) {
            return [
                esc_html($row->keyword),
                '<strong>' . absint($row->total) . '</strong>',
            ];
        }, ['Keyword', 'Count']);

        echo '<h2 style="margin-top:24px;">คอร์สที่ถูกสนใจสูงสุด (30 วัน)</h2>';
        self::render_simple_table($top_courses, function ($row) {
            $id = absint($row->object_id);
            $title = get_the_title($id);
            $url = get_permalink($id);
            $label = $title ? $title : ('Course #' . $id);
            $title_html = $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>' : esc_html($label);
            return [
                $title_html,
                '<strong>' . absint($row->total) . '</strong>',
            ];
        }, ['Course', 'Count']);

        echo '<h2 style="margin-top:24px;">แหล่งเรียนรู้ที่ถูกสนใจสูงสุด (30 วัน)</h2>';
        self::render_simple_table($top_locations, function ($row) {
            $id = absint($row->object_id);
            $title = get_the_title($id);
            $url = get_permalink($id);
            $label = $title ? $title : ('Location #' . $id);
            $title_html = $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>' : esc_html($label);
            return [
                $title_html,
                '<strong>' . absint($row->total) . '</strong>',
            ];
        }, ['Location', 'Count']);

        echo '<h2 style="margin-top:24px;">คอร์สที่ถูกกดแจ้งเตือนมากสุด (30 วัน)</h2>';
        self::render_simple_table($top_notify_courses, function ($row) {
            $id = absint($row->object_id);
            $title = get_the_title($id);
            $url = get_permalink($id);
            $label = $title ? $title : ('Course #' . $id);
            $title_html = $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>' : esc_html($label);
            return [
                $title_html,
                '<strong>' . absint($row->total) . '</strong>',
            ];
        }, ['Course', 'Notify Count']);

        echo '</div>';
    }

    private static function card($label, $count) {
        echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:12px 14px;">';
        echo '<div style="font-size:13px;color:#646970;">' . esc_html($label) . '</div>';
        echo '<div style="font-size:30px;line-height:1.2;font-weight:700;margin-top:4px;">' . absint($count) . '</div>';
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
