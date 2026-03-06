<?php
/*
Plugin Name: LC Public Location Contributions
Description: Public contributions for Learning City locations: photo uploads and structured location edit requests with OTP verification and admin approval.
Version: 1.0.0
Author: Learning City
*/

if (!defined('ABSPATH')) {
    exit;
}

final class LC_Public_Place_Photo_Upload {
    const CPT = 'lc_place_submission';
    const CHANGE_CPT = 'lc_loc_change_req';
    const LEGACY_CHANGE_CPT_TRUNC = 'lc_location_change_r';
    const OPT_KEY = 'lc_public_place_upload_settings';
    const NONCE_ACTION_SUBMIT = 'lc_place_photo_submit';
    const NONCE_ACTION_MODERATE = 'lc_place_photo_moderate';
    const NONCE_ACTION_AJAX = 'lc_place_photo_upload_ajax';
    const NONCE_ACTION_EDIT_ACCESS = 'lc_location_edit_access';
    const NONCE_ACTION_EDIT_SUBMIT = 'lc_location_edit_submit';
    const NONCE_ACTION_EDIT_MODERATE = 'lc_location_edit_moderate';
    const NONCE_ACTION_EDIT_LOGOUT = 'lc_location_edit_logout';
    const NONCE_ACTION_TEXT_CLEANUP = 'lc_text_artifact_cleanup';
    const ERROR_LOG_TABLE_SUFFIX = 'lc_error_logs';
    const EDIT_SESSION_TTL = 43200; // 12 hours.
    const OTP_VERIFY_MAX_ATTEMPTS = 5;
    const OTP_VERIFY_LOCK_SECONDS = 900; // 15 minutes.

    public static function init() {
        add_action('init', [__CLASS__, 'register_submission_cpt']);
        add_action('init', [__CLASS__, 'register_change_request_cpt']);
        add_action('admin_menu', [__CLASS__, 'register_admin_menus']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('template_redirect', [__CLASS__, 'handle_public_upload_landing']);
        add_action('template_redirect', [__CLASS__, 'handle_public_submission_direct_endpoint']);
        // Legacy full-page editor flow (?lc_location_edit=1) has been retired.
        add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
        add_filter('lc_public_photo_upload_url', [__CLASS__, 'provide_upload_form_url']);
        add_filter('lc_public_photo_upload_config', [__CLASS__, 'provide_frontend_config']);
        add_filter('lc_public_location_edit_config', [__CLASS__, 'provide_location_edit_config']);

        add_shortcode('lc_place_photo_upload_form', [__CLASS__, 'render_upload_form']);

        add_action('admin_post_nopriv_lc_submit_place_photos', [__CLASS__, 'handle_public_submission']);
        add_action('admin_post_lc_submit_place_photos', [__CLASS__, 'handle_public_submission']);
        add_action('admin_post_nopriv_lc_submit_place_photos_json', [__CLASS__, 'handle_public_submission_admin_post_json']);
        add_action('admin_post_lc_submit_place_photos_json', [__CLASS__, 'handle_public_submission_admin_post_json']);
        add_action('wp_ajax_nopriv_lc_submit_place_photos_ajax', [__CLASS__, 'handle_public_submission_ajax']);
        add_action('wp_ajax_lc_submit_place_photos_ajax', [__CLASS__, 'handle_public_submission_ajax']);

        add_action('admin_post_lc_approve_place_submission', [__CLASS__, 'handle_approve_submission']);
        add_action('admin_post_lc_reject_place_submission', [__CLASS__, 'handle_reject_submission']);

        add_action('wp_ajax_nopriv_lc_request_location_edit_otp', [__CLASS__, 'ajax_request_location_edit_otp']);
        add_action('wp_ajax_lc_request_location_edit_otp', [__CLASS__, 'ajax_request_location_edit_otp']);
        add_action('wp_ajax_nopriv_lc_verify_location_edit_otp', [__CLASS__, 'ajax_verify_location_edit_otp']);
        add_action('wp_ajax_lc_verify_location_edit_otp', [__CLASS__, 'ajax_verify_location_edit_otp']);
        add_action('wp_ajax_nopriv_lc_logout_location_edit_session', [__CLASS__, 'ajax_logout_location_edit_session']);
        add_action('wp_ajax_lc_logout_location_edit_session', [__CLASS__, 'ajax_logout_location_edit_session']);
        add_action('wp_ajax_nopriv_lc_fetch_location_edit_dashboard', [__CLASS__, 'ajax_fetch_location_edit_dashboard']);
        add_action('wp_ajax_lc_fetch_location_edit_dashboard', [__CLASS__, 'ajax_fetch_location_edit_dashboard']);
        add_action('wp_ajax_nopriv_lc_fetch_location_edit_status_feed', [__CLASS__, 'ajax_fetch_location_edit_status_feed']);
        add_action('wp_ajax_lc_fetch_location_edit_status_feed', [__CLASS__, 'ajax_fetch_location_edit_status_feed']);
        add_action('wp_ajax_nopriv_lc_fetch_location_edit_status_detail', [__CLASS__, 'ajax_fetch_location_edit_status_detail']);
        add_action('wp_ajax_lc_fetch_location_edit_status_detail', [__CLASS__, 'ajax_fetch_location_edit_status_detail']);
        add_action('wp_ajax_nopriv_lc_delete_location_edit_request', [__CLASS__, 'ajax_delete_location_edit_request']);
        add_action('wp_ajax_lc_delete_location_edit_request', [__CLASS__, 'ajax_delete_location_edit_request']);
        add_action('wp_ajax_nopriv_lc_get_inline_location_edit_context', [__CLASS__, 'ajax_get_inline_location_edit_context']);
        add_action('wp_ajax_lc_get_inline_location_edit_context', [__CLASS__, 'ajax_get_inline_location_edit_context']);
        add_action('wp_ajax_nopriv_lc_submit_inline_location_edit', [__CLASS__, 'ajax_submit_inline_location_edit']);
        add_action('wp_ajax_lc_submit_inline_location_edit', [__CLASS__, 'ajax_submit_inline_location_edit']);
        add_action('wp_ajax_nopriv_lc_get_course_edit_context', [__CLASS__, 'ajax_get_course_edit_context']);
        add_action('wp_ajax_lc_get_course_edit_context', [__CLASS__, 'ajax_get_course_edit_context']);
        add_action('wp_ajax_nopriv_lc_submit_course_edit_request', [__CLASS__, 'ajax_submit_course_edit_request']);
        add_action('wp_ajax_lc_submit_course_edit_request', [__CLASS__, 'ajax_submit_course_edit_request']);

        // Legacy admin-post submit endpoint for full-page editor flow has been retired.
        add_action('admin_post_lc_update_location_change_request_status', [__CLASS__, 'handle_update_location_change_request_status']);
        add_action('admin_post_lc_bulk_update_location_change_request_status', [__CLASS__, 'handle_bulk_update_location_change_request_status']);
        add_action('admin_post_lc_approve_location_change_request', [__CLASS__, 'handle_approve_location_change_request']);
        add_action('admin_post_lc_reject_location_change_request', [__CLASS__, 'handle_reject_location_change_request']);
        add_action('admin_post_lc_cleanup_text_artifacts', [__CLASS__, 'handle_cleanup_text_artifacts']);
    }

    public static function activate() {
        self::register_submission_cpt();
        self::register_change_request_cpt();
        self::maybe_create_error_log_table();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public static function register_submission_cpt() {
        register_post_type(self::CPT, [
            'labels' => [
                'name' => __('Place Photo Submissions', 'lc-public-place-photo-upload'),
                'singular_name' => __('Place Photo Submission', 'lc-public-place-photo-upload'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'rewrite' => false,
            'query_var' => false,
        ]);
    }

    public static function register_change_request_cpt() {
        register_post_type(self::CHANGE_CPT, [
            'labels' => [
                'name' => __('Location Change Requests', 'lc-public-place-photo-upload'),
                'singular_name' => __('Location Change Request', 'lc-public-place-photo-upload'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'rewrite' => false,
            'query_var' => false,
        ]);
    }

    private static function change_request_post_types() {
        return [self::CHANGE_CPT, self::LEGACY_CHANGE_CPT_TRUNC];
    }

    private static function is_change_request_post_type($post_type) {
        $post_type = (string) $post_type;
        return in_array($post_type, self::change_request_post_types(), true);
    }

    private static function status_label_th($status) {
        $status = (string) $status;
        if ($status === 'approved') {
            return 'อนุมัติ';
        }
        if ($status === 'rejected') {
            return 'ไม่อนุมัติ';
        }
        if ($status === 'cancelled') {
            return 'ยกเลิก';
        }
        return 'รอตรวจสอบ';
    }

    private static function request_cancel_window_seconds() {
        return 15 * MINUTE_IN_SECONDS;
    }

    private static function get_request_submitted_ts($request_id) {
        $request_id = (int) $request_id;
        if ($request_id <= 0) {
            return 0;
        }
        $submitted_at = (string) get_post_meta($request_id, '_lc_submitted_at', true);
        if ($submitted_at !== '') {
            $tz = wp_timezone();
            $dt = date_create_immutable_from_format('Y-m-d H:i:s', $submitted_at, $tz);
            if ($dt instanceof DateTimeImmutable) {
                $ts = $dt->getTimestamp();
                if ($ts > 0) {
                    return $ts;
                }
            }
            $ts_fallback = strtotime($submitted_at);
            if (is_int($ts_fallback) && $ts_fallback > 0) {
                return $ts_fallback;
            }
        }
        $post = get_post($request_id);
        if ($post instanceof WP_Post) {
            $fallback = strtotime((string) $post->post_date_gmt . ' UTC');
            if (is_int($fallback) && $fallback > 0) {
                return $fallback;
            }
        }
        return 0;
    }

    private static function is_request_cancelable($request_id, $status = '') {
        $request_id = (int) $request_id;
        if ($request_id <= 0) {
            return false;
        }
        $status = (string) $status;
        if ($status === '') {
            $status = (string) get_post_meta($request_id, '_lc_change_status', true);
        }
        if ($status === '' || !in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $status = 'pending';
        }
        if ($status !== 'pending') {
            return false;
        }
        $submitted_ts = self::get_request_submitted_ts($request_id);
        if ($submitted_ts <= 0) {
            return false;
        }
        $age = time() - $submitted_ts;
        return $age >= 0 && $age <= self::request_cancel_window_seconds();
    }

    private static function request_type_label_th($request_type) {
        $request_type = sanitize_key((string) $request_type);
        if ($request_type === 'update_course') {
            return 'คอร์ส';
        }
        return 'สถานที่';
    }

    private static function reject_reason_options_th() {
        return [
            'invalid_data' => 'ข้อมูลไม่ถูกต้อง',
            'insufficient_data' => 'ข้อมูลไม่ครบถ้วน',
            'duplicate_request' => 'คำขอซ้ำกับรายการเดิม',
            'cannot_verify' => 'ไม่สามารถตรวจสอบข้อมูลได้',
            'policy' => 'ไม่เป็นไปตามหลักเกณฑ์',
            'other' => 'อื่นๆ',
        ];
    }

    private static function resolve_reject_reason_from_post($post_data) {
        $post_data = is_array($post_data) ? $post_data : [];
        $options = self::reject_reason_options_th();
        $preset = isset($post_data['reject_reason_preset']) ? sanitize_key((string) wp_unslash($post_data['reject_reason_preset'])) : '';
        $other = isset($post_data['reject_reason_other']) ? sanitize_textarea_field((string) wp_unslash($post_data['reject_reason_other'])) : '';
        $legacy = isset($post_data['reject_reason']) ? sanitize_textarea_field((string) wp_unslash($post_data['reject_reason'])) : '';
        if ($preset !== '' && isset($options[$preset])) {
            if ($preset === 'other') {
                return $other;
            }
            return (string) $options[$preset];
        }
        return $legacy;
    }

    private static function get_request_type_for_request($request_id, $payload = null) {
        $request_id = (int) $request_id;
        $request_type = sanitize_key((string) get_post_meta($request_id, '_lc_request_type', true));
        if ($request_type !== '') {
            return $request_type;
        }
        if (!is_array($payload)) {
            $payload_json = (string) get_post_meta($request_id, '_lc_change_payload', true);
            $payload = json_decode($payload_json, true);
            if (!is_array($payload)) {
                $payload = [];
            }
        }
        $request_type = sanitize_key((string) ($payload['request_type'] ?? 'update_location'));
        return $request_type !== '' ? $request_type : 'update_location';
    }

    private static function get_request_target_payload($request_id, $payload = null) {
        $request_id = (int) $request_id;
        if (!is_array($payload)) {
            $payload_json = (string) get_post_meta($request_id, '_lc_change_payload', true);
            $payload = json_decode($payload_json, true);
            if (!is_array($payload)) {
                $payload = [];
            }
        }

        $request_type = self::get_request_type_for_request($request_id, $payload);
        $target_type = $request_type === 'update_course' ? 'course' : 'location';
        $target_id = $target_type === 'course'
            ? (int) get_post_meta($request_id, '_lc_course_id', true)
            : (int) get_post_meta($request_id, '_lc_location_id', true);
        if ($target_id <= 0 && $target_type === 'course') {
            $target_id = (int) get_post_meta($request_id, '_lc_location_id', true);
        }
        if ($target_id <= 0) {
            return null;
        }
        if (get_post_type($target_id) !== $target_type) {
            return null;
        }

        return [
            'request_type' => $request_type,
            'target_type' => $target_type,
            'target_id' => $target_id,
            'target_title' => self::decode_html_entities_text((string) get_the_title($target_id)),
        ];
    }

    private static function get_latest_request_for_requester($location_id, $requester_email) {
        $location_id = (int) $location_id;
        $requester_email = sanitize_email((string) $requester_email);
        if ($location_id <= 0 || $requester_email === '') {
            return null;
        }
        $posts = get_posts([
            'post_type' => self::change_request_post_types(),
            'post_status' => ['pending', 'publish', 'draft'],
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_lc_location_id', 'value' => $location_id, 'compare' => '='],
                ['key' => '_lc_requester_email', 'value' => strtolower($requester_email), 'compare' => '='],
            ],
            'fields' => 'ids',
        ]);
        if (empty($posts)) {
            return null;
        }
        return (int) $posts[0];
    }

    private static function build_requester_dashboard_payload($requester_email) {
        $requester_email = sanitize_email((string) $requester_email);
        $q = new WP_Query([
            'post_type' => self::change_request_post_types(),
            'post_status' => ['pending', 'publish', 'draft'],
            'posts_per_page' => 300,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                ['key' => '_lc_requester_email', 'value' => strtolower($requester_email), 'compare' => '='],
            ],
        ]);

        $rows_by_target = [];
        while ($q->have_posts()) {
            $q->the_post();
            $rid = (int) get_the_ID();
            $payload_json = (string) get_post_meta($rid, '_lc_change_payload', true);
            $payload = json_decode($payload_json, true);
            if (!is_array($payload)) {
                $payload = [];
            }
            $target = self::get_request_target_payload($rid, $payload);
            if (!is_array($target)) {
                continue;
            }
            $target_id = (int) ($target['target_id'] ?? 0);
            $target_type = (string) ($target['target_type'] ?? 'location');
            $row_key = $target_type . '_' . $target_id;
            if (isset($rows_by_target[$row_key])) {
                continue;
            }
            $status = (string) get_post_meta($rid, '_lc_change_status', true);
            if ($status === '' || !in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
                $status = 'pending';
            }
            $rows_by_target[$row_key] = [
                'target_type' => $target_type,
                'target_type_label' => self::request_type_label_th((string) ($target['request_type'] ?? 'update_location')),
                'target_id' => $target_id,
                'target_title' => (string) ($target['target_title'] ?? ''),
                'location_id' => $target_id,
                'location_title' => (string) ($target['target_title'] ?? ''),
                'request_type' => (string) ($target['request_type'] ?? 'update_location'),
                'request_id' => $rid,
                'status' => $status,
                'status_label' => self::status_label_th($status),
                'submitted_at' => (string) get_post_meta($rid, '_lc_submitted_at', true),
                'moderated_at' => (string) get_post_meta($rid, '_lc_moderated_at', true),
                'reject_reason' => (string) get_post_meta($rid, '_lc_reject_reason', true),
                'cancelled_at' => (string) get_post_meta($rid, '_lc_cancelled_at', true),
                'cancelled_by' => (string) get_post_meta($rid, '_lc_cancelled_by_email', true),
                'edit_url' => $target_type === 'location' ? add_query_arg([
                    'lc_location_edit' => 1,
                    'place_id' => $target_id,
                    'req_kind' => 'edit',
                ], home_url('/')) : '',
            ];
        }
        wp_reset_postdata();

        $rows = array_values($rows_by_target);
        usort($rows, static function($a, $b) {
            return strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? ''));
        });
        $counts = ['all' => count($rows), 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
        $type_counts = ['all' => count($rows), 'location' => 0, 'course' => 0];
        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? 'pending');
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
            $target_type = (string) ($row['target_type'] ?? 'location');
            if (isset($type_counts[$target_type])) {
                $type_counts[$target_type]++;
            }
        }

        return [
            'rows' => $rows,
            'counts' => $counts,
            'type_counts' => $type_counts,
        ];
    }

    private static function build_requester_attachment_items($ids) {
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));
        $items = [];
        foreach ($ids as $id) {
            if ($id <= 0 || get_post_type($id) !== 'attachment') {
                continue;
            }
            $thumb = wp_get_attachment_image_url($id, 'thumbnail');
            $medium = wp_get_attachment_image_url($id, 'medium');
            $full = wp_get_attachment_url($id);
            if (!$full) {
                continue;
            }
            $items[] = [
                'id' => $id,
                'thumb' => is_string($thumb) && $thumb !== '' ? $thumb : $full,
                'medium' => is_string($medium) && $medium !== '' ? $medium : $full,
                'url' => $full,
            ];
        }
        return $items;
    }

    private static function summarize_change_payload_for_requester($location_id, $payload) {
        $location_id = (int) $location_id;
        $payload = is_array($payload) ? $payload : [];
        $request_type = sanitize_key((string) ($payload['request_type'] ?? 'update_location'));
        $diff_rows = $request_type === 'update_course'
            ? self::build_course_change_diff_rows($location_id, $payload)
            : self::build_change_diff_rows($location_id, $payload);
        $items = [];
        foreach ($diff_rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $section = (string) ($row['section'] ?? '');
            $field = (string) ($row['field'] ?? '');
            if ($section === 'Images' && ($field === 'Remove Images' || $field === 'Add Images')) {
                $image_ids = array_values(array_filter(array_map('intval', (array) ($row['new_ids'] ?? []))));
                $items[] = ($field === 'Remove Images')
                    ? 'ขอลบรูปภาพ ' . count($image_ids) . ' รูป'
                    : 'ขอเพิ่มรูปภาพ ' . count($image_ids) . ' รูป';
                continue;
            }
            if ($field === 'Gallery (Before / After)') {
                continue;
            }
            $label = trim($section . ' / ' . $field);
            if ($label !== '') {
                $items[] = $label;
            }
        }
        $items = array_values(array_unique(array_filter(array_map('trim', $items))));
        if (empty($items)) {
            $items[] = 'ส่งคำขอแก้ไขข้อมูล';
        }
        return $items;
    }

    private static function build_requester_change_details($location_id, $payload) {
        $location_id = (int) $location_id;
        $payload = is_array($payload) ? $payload : [];
        $request_type = sanitize_key((string) ($payload['request_type'] ?? 'update_location'));
        $diff_rows = $request_type === 'update_course'
            ? self::build_course_change_diff_rows($location_id, $payload)
            : self::build_change_diff_rows($location_id, $payload);
        $details = [];
        foreach ($diff_rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $section = (string) ($row['section'] ?? '');
            $field = (string) ($row['field'] ?? '');
            if ($field === 'Gallery (Before / After)') {
                continue;
            }
            if ($section === 'Images' && ($field === 'Remove Images' || $field === 'Add Images')) {
                $image_ids = array_values(array_filter(array_map('intval', (array) ($row['new_ids'] ?? []))));
                if (empty($image_ids)) {
                    continue;
                }
                $details[] = [
                    'type' => 'images',
                    'action' => $field === 'Remove Images' ? 'remove' : 'add',
                    'label' => $field === 'Remove Images' ? 'รูปที่แจ้งให้ลบ' : 'รูปที่แจ้งให้เพิ่ม',
                    'images' => self::build_requester_attachment_items($image_ids),
                ];
                continue;
            }

            $details[] = [
                'type' => 'text',
                'label' => trim($section . ' / ' . $field),
                'old' => (string) ($row['old'] ?? '-'),
                'new' => (string) ($row['new'] ?? '-'),
            ];
        }

        if (empty($details)) {
            $details[] = [
                'type' => 'text',
                'label' => 'คำขอ',
                'old' => '-',
                'new' => 'ส่งคำขอแก้ไขข้อมูล',
            ];
        }
        return $details;
    }

    private static function build_requester_status_feed_payload($requester_email) {
        $requester_email = sanitize_email((string) $requester_email);
        if ($requester_email === '') {
            return ['requester_email' => '', 'rows' => [], 'counts' => ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0], 'type_counts' => ['all' => 0, 'location' => 0, 'course' => 0]];
        }

        $q = new WP_Query([
            'post_type' => self::change_request_post_types(),
            'post_status' => ['pending', 'publish', 'draft'],
            'posts_per_page' => 500,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                ['key' => '_lc_requester_email', 'value' => strtolower($requester_email), 'compare' => '='],
            ],
        ]);

        $rows = [];
        while ($q->have_posts()) {
            $q->the_post();
            $rid = (int) get_the_ID();
            $status = (string) get_post_meta($rid, '_lc_change_status', true);
            if ($status === '' || !in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
                $status = 'pending';
            }

            $payload_json = (string) get_post_meta($rid, '_lc_change_payload', true);
            $payload = json_decode($payload_json, true);
            if (!is_array($payload)) {
                $payload = [];
            }
            $target = self::get_request_target_payload($rid, $payload);
            if (!is_array($target)) {
                continue;
            }
            $target_id = (int) ($target['target_id'] ?? 0);
            $target_type = (string) ($target['target_type'] ?? 'location');
            if ($target_type === 'location') {
                $change_items = self::summarize_change_payload_for_requester($target_id, $payload);
                $change_details = self::build_requester_change_details($target_id, $payload);
            } else {
                $course_diff_rows = self::build_course_change_diff_rows($target_id, $payload);
                $change_items = [];
                $change_details = [];
                foreach ($course_diff_rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $label = trim((string) ($row['section'] ?? '') . ' / ' . (string) ($row['field'] ?? ''));
                    if ($label !== '') {
                        $change_items[] = $label;
                    }
                    $change_details[] = [
                        'type' => 'text',
                        'label' => $label !== '' ? $label : 'คำขอ',
                        'old' => (string) ($row['old'] ?? '-'),
                        'new' => (string) ($row['new'] ?? '-'),
                    ];
                }
                $change_items = array_values(array_unique(array_filter(array_map('trim', $change_items))));
                if (empty($change_items)) {
                    $change_items[] = 'ส่งคำขอแก้ไขข้อมูลคอร์ส';
                }
                if (empty($change_details)) {
                    $change_details[] = [
                        'type' => 'text',
                        'label' => 'คำขอ',
                        'old' => '-',
                        'new' => 'ส่งคำขอแก้ไขข้อมูลคอร์ส',
                    ];
                }
            }

            $rows[] = [
                'request_id' => $rid,
                'request_type' => (string) ($target['request_type'] ?? 'update_location'),
                'target_type' => $target_type,
                'target_type_label' => self::request_type_label_th((string) ($target['request_type'] ?? 'update_location')),
                'target_id' => $target_id,
                'target_title' => (string) ($target['target_title'] ?? ''),
                'location_id' => $target_id,
                'location_title' => (string) ($target['target_title'] ?? ''),
                'status' => $status,
                'status_label' => self::status_label_th($status),
                'can_delete' => self::is_request_cancelable($rid, $status),
                'submitted_at' => (string) get_post_meta($rid, '_lc_submitted_at', true),
                'moderated_at' => (string) get_post_meta($rid, '_lc_moderated_at', true),
                'reject_reason' => (string) get_post_meta($rid, '_lc_reject_reason', true),
                'cancelled_at' => (string) get_post_meta($rid, '_lc_cancelled_at', true),
                'cancelled_by' => (string) get_post_meta($rid, '_lc_cancelled_by_email', true),
                'change_items' => $change_items,
                'change_details' => $change_details,
            ];
        }
        wp_reset_postdata();

        $counts = ['all' => count($rows), 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
        $type_counts = ['all' => count($rows), 'location' => 0, 'course' => 0];
        foreach ($rows as $row) {
            $s = (string) ($row['status'] ?? 'pending');
            if (isset($counts[$s])) {
                $counts[$s]++;
            }
            $target_type = (string) ($row['target_type'] ?? 'location');
            if (isset($type_counts[$target_type])) {
                $type_counts[$target_type]++;
            }
        }
        return [
            'requester_email' => $requester_email,
            'rows' => $rows,
            'counts' => $counts,
            'type_counts' => $type_counts,
        ];
    }

    private static function build_requester_status_detail_payload($requester_email, $request_id) {
        $requester_email = sanitize_email((string) $requester_email);
        $request_id = (int) $request_id;
        if ($requester_email === '' || $request_id <= 0) {
            return null;
        }
        if (!self::is_change_request_post_type(get_post_type($request_id))) {
            return null;
        }
        $owner_email = sanitize_email((string) get_post_meta($request_id, '_lc_requester_email', true));
        if ($owner_email === '' || strtolower($owner_email) !== strtolower($requester_email)) {
            return null;
        }
        $payload_json = (string) get_post_meta($request_id, '_lc_change_payload', true);
        $payload = json_decode($payload_json, true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $target = self::get_request_target_payload($request_id, $payload);
        if (!is_array($target)) {
            return null;
        }
        $target_id = (int) ($target['target_id'] ?? 0);
        $target_type = (string) ($target['target_type'] ?? 'location');
        $status = (string) get_post_meta($request_id, '_lc_change_status', true);
        if ($status === '' || !in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $status = 'pending';
        }
        $change_details = self::build_requester_change_details($target_id, $payload);
        return [
            'request_id' => $request_id,
            'request_type' => (string) ($target['request_type'] ?? 'update_location'),
            'target_type' => $target_type,
            'target_type_label' => self::request_type_label_th((string) ($target['request_type'] ?? 'update_location')),
            'target_id' => $target_id,
            'target_title' => (string) ($target['target_title'] ?? ''),
            'location_id' => $target_id,
            'location_title' => (string) ($target['target_title'] ?? ''),
            'status' => $status,
            'status_label' => self::status_label_th($status),
            'can_delete' => self::is_request_cancelable($request_id, $status),
            'submitted_at' => (string) get_post_meta($request_id, '_lc_submitted_at', true),
            'moderated_at' => (string) get_post_meta($request_id, '_lc_moderated_at', true),
            'reject_reason' => (string) get_post_meta($request_id, '_lc_reject_reason', true),
            'cancelled_at' => (string) get_post_meta($request_id, '_lc_cancelled_at', true),
            'cancelled_by' => (string) get_post_meta($request_id, '_lc_cancelled_by_email', true),
            'change_details' => $change_details,
        ];
    }

    public static function ajax_fetch_location_edit_dashboard() {
        check_ajax_referer(self::NONCE_ACTION_EDIT_ACCESS, 'nonce');
        $token = isset($_POST['token']) ? sanitize_text_field((string) wp_unslash($_POST['token'])) : '';
        if ($token === '') {
            $token = self::get_editor_token_from_cookie();
        }
        $session = self::get_editor_session($token);
        if (!$session) {
            self::send_json_error_debug(__('เซสชันหมดอายุ กรุณาขอ OTP ใหม่อีกครั้ง', 'lc-public-place-photo-upload'), 403, 'session_expired', 'status_dashboard');
        }
        $requester_email = sanitize_email((string) ($session['email'] ?? ''));
        if ($requester_email === '') {
            self::send_json_error_debug(__('อีเมลไม่ถูกต้อง', 'lc-public-place-photo-upload'), 400, 'invalid_email', 'status_dashboard');
        }
        wp_send_json_success(self::build_requester_dashboard_payload($requester_email));
    }

    public static function ajax_fetch_location_edit_status_feed() {
        check_ajax_referer(self::NONCE_ACTION_EDIT_ACCESS, 'nonce');
        $token = isset($_POST['token']) ? sanitize_text_field((string) wp_unslash($_POST['token'])) : '';
        if ($token === '') {
            $token = self::get_editor_token_from_cookie();
        }
        $session = self::get_editor_session($token);
        if (!$session) {
            self::send_json_error_debug(__('เซสชันหมดอายุ กรุณาขอ OTP ใหม่อีกครั้ง', 'lc-public-place-photo-upload'), 403, 'session_expired', 'status_feed');
        }
        $requester_email = sanitize_email((string) ($session['email'] ?? ''));
        if ($requester_email === '') {
            self::send_json_error_debug(__('อีเมลไม่ถูกต้อง', 'lc-public-place-photo-upload'), 400, 'invalid_email', 'status_feed');
        }
        wp_send_json_success(self::build_requester_status_feed_payload($requester_email));
    }

    public static function ajax_fetch_location_edit_status_detail() {
        check_ajax_referer(self::NONCE_ACTION_EDIT_ACCESS, 'nonce');
        $token = isset($_POST['token']) ? sanitize_text_field((string) wp_unslash($_POST['token'])) : '';
        if ($token === '') {
            $token = self::get_editor_token_from_cookie();
        }
        $session = self::get_editor_session($token);
        if (!$session) {
            self::send_json_error_debug(__('เซสชันหมดอายุ กรุณาขอ OTP ใหม่อีกครั้ง', 'lc-public-place-photo-upload'), 403, 'session_expired', 'status_detail');
        }
        $requester_email = sanitize_email((string) ($session['email'] ?? ''));
        $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
        $detail = self::build_requester_status_detail_payload($requester_email, $request_id);
        if (!is_array($detail)) {
            self::send_json_error_debug(__('ไม่พบรายการคำขอนี้', 'lc-public-place-photo-upload'), 404, 'request_not_found', 'status_detail', ['request_id' => $request_id]);
        }
        wp_send_json_success($detail);
    }

    public static function ajax_delete_location_edit_request() {
        check_ajax_referer(self::NONCE_ACTION_EDIT_ACCESS, 'nonce');
        $token = isset($_POST['token']) ? sanitize_text_field((string) wp_unslash($_POST['token'])) : '';
        if ($token === '') {
            $token = self::get_editor_token_from_cookie();
        }
        $session = self::get_editor_session($token);
        if (!$session) {
            self::send_json_error_debug(__('เซสชันหมดอายุ กรุณาขอ OTP ใหม่อีกครั้ง', 'lc-public-place-photo-upload'), 403, 'session_expired', 'request_cancel');
        }
        $requester_email = sanitize_email((string) ($session['email'] ?? ''));
        if ($requester_email === '') {
            self::send_json_error_debug(__('อีเมลไม่ถูกต้อง', 'lc-public-place-photo-upload'), 400, 'invalid_email', 'request_cancel');
        }

        $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
        if ($request_id <= 0 || !self::is_change_request_post_type(get_post_type($request_id))) {
            self::send_json_error_debug(__('ไม่พบคำขอที่ต้องการยกเลิก', 'lc-public-place-photo-upload'), 404, 'request_not_found', 'request_cancel', ['request_id' => $request_id]);
        }
        $owner_email = sanitize_email((string) get_post_meta($request_id, '_lc_requester_email', true));
        if ($owner_email === '' || strtolower($owner_email) !== strtolower($requester_email)) {
            self::send_json_error_debug(__('คุณไม่มีสิทธิ์ยกเลิกคำขอนี้', 'lc-public-place-photo-upload'), 403, 'forbidden_request_owner', 'request_cancel', ['request_id' => $request_id]);
        }
        $status = (string) get_post_meta($request_id, '_lc_change_status', true);
        if ($status === '' || !in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $status = 'pending';
        }
        if (!self::is_request_cancelable($request_id, $status)) {
            self::send_json_error_debug(__('ยกเลิกได้เฉพาะคำขอรอตรวจสอบภายใน 15 นาทีหลังส่งคำขอ', 'lc-public-place-photo-upload'), 400, 'request_cancel_window_expired', 'request_cancel', ['request_id' => $request_id, 'status' => $status]);
        }
        update_post_meta($request_id, '_lc_change_status', 'cancelled');
        update_post_meta($request_id, '_lc_cancelled_at', current_time('mysql'));
        update_post_meta($request_id, '_lc_cancelled_by_email', strtolower($requester_email));
        delete_post_meta($request_id, '_lc_moderated_by');
        delete_post_meta($request_id, '_lc_moderated_at');
        delete_post_meta($request_id, '_lc_reject_reason');
        wp_send_json_success(['request_id' => $request_id]);
    }

    public static function ajax_fetch_editable_locations() {
        check_ajax_referer(self::NONCE_ACTION_EDIT_ACCESS, 'nonce');
        $token = isset($_POST['token']) ? sanitize_text_field((string) wp_unslash($_POST['token'])) : '';
        if ($token === '') {
            $token = self::get_editor_token_from_cookie();
        }
        $session = self::get_editor_session($token);
        if (!$session) {
            self::send_json_error_debug(__('เซสชันหมดอายุ กรุณาขอ OTP ใหม่อีกครั้ง', 'lc-public-place-photo-upload'), 403, 'session_expired', 'editable_locations');
        }

        $locations = get_posts([
            'post_type' => 'location',
            'post_status' => 'publish',
            'posts_per_page' => 1500,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        $rows = [];
        foreach ((array) $locations as $location_id) {
            $location_id = (int) $location_id;
            if ($location_id <= 0) {
                continue;
            }
            $title = (string) get_the_title($location_id);
            if ($title === '') {
                continue;
            }
            $rows[] = [
                'location_id' => $location_id,
                'location_title' => $title,
                'edit_url' => add_query_arg([
                    'lc_location_edit' => 1,
                    'place_id' => $location_id,
                    'req_kind' => 'edit',
                ], home_url('/')),
            ];
        }

        wp_send_json_success(['rows' => $rows]);
    }

    private static function get_inline_editor_session_or_error() {
        $token = isset($_POST['token']) ? sanitize_text_field((string) wp_unslash($_POST['token'])) : '';
        if ($token === '') {
            $token = self::get_editor_token_from_cookie();
        }
        $session = self::get_editor_session($token);
        if (!$session) {
            self::send_json_error_debug(__('เซสชันหมดอายุ กรุณาขอ OTP ใหม่อีกครั้ง', 'lc-public-place-photo-upload'), 403, 'session_expired', 'inline_editor_session');
        }
        $settings = self::get_settings();
        $requester_email = sanitize_email((string) ($session['email'] ?? ''));
        if ($requester_email === '' || !self::is_editor_email_allowed($requester_email, $settings)) {
            self::send_json_error_debug(__('อีเมลนี้ไม่มีสิทธิ์แก้ไขข้อมูลแล้ว', 'lc-public-place-photo-upload'), 403, 'editor_email_not_allowed', 'inline_editor_session');
        }
        return [$session, $settings, $requester_email];
    }

    private static function get_location_session_ids($location_id) {
        $location_id = (int) $location_id;
        if ($location_id <= 0) {
            return [];
        }
        $session_ids = get_posts([
            'post_type' => 'session',
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                ['key' => 'location', 'value' => (string) $location_id, 'compare' => '='],
                ['key' => 'location', 'value' => '"' . (string) $location_id . '"', 'compare' => 'LIKE'],
            ],
        ]);
        return array_values(array_filter(array_map('intval', (array) $session_ids)));
    }

    private static function get_course_session_ids($course_id) {
        $course_id = (int) $course_id;
        if ($course_id <= 0) {
            return [];
        }
        $session_ids = get_posts([
            'post_type' => 'session',
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                ['key' => 'course', 'value' => (string) $course_id, 'compare' => '='],
            ],
        ]);
        return array_values(array_filter(array_map('intval', (array) $session_ids)));
    }

    private static function get_location_available_course_ids($location_id) {
        $location_id = (int) $location_id;
        if ($location_id <= 0) {
            return [];
        }

        $course_query_args = [
            'post_type' => 'course',
            'post_status' => ['publish'],
            'posts_per_page' => 800,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ];

        if (taxonomy_exists('location-type') && taxonomy_exists('course_provider')) {
            $location_type_slugs = wp_get_post_terms($location_id, 'location-type', ['fields' => 'slugs']);
            $location_type_slugs = is_array($location_type_slugs)
                ? array_values(array_filter(array_map('sanitize_title', $location_type_slugs)))
                : [];
            if (!empty($location_type_slugs)) {
                $course_query_args['tax_query'] = [[
                    'taxonomy' => 'course_provider',
                    'field' => 'slug',
                    'terms' => $location_type_slugs,
                    'operator' => 'IN',
                ]];
            }
        }

        $course_ids = get_posts($course_query_args);
        return array_values(array_filter(array_map('intval', (array) $course_ids)));
    }

    private static function get_course_available_location_ids($course_id) {
        $course_id = (int) $course_id;
        if ($course_id <= 0) {
            return [];
        }

        $location_query_args = [
            'post_type' => 'location',
            'post_status' => ['publish'],
            'posts_per_page' => 2000,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ];

        if (taxonomy_exists('course_provider') && taxonomy_exists('location-type')) {
            $provider_slugs = wp_get_post_terms($course_id, 'course_provider', ['fields' => 'slugs']);
            $provider_slugs = is_array($provider_slugs)
                ? array_values(array_filter(array_map('sanitize_title', $provider_slugs)))
                : [];
            if (empty($provider_slugs)) {
                return [];
            }
            $location_query_args['tax_query'] = [[
                'taxonomy' => 'location-type',
                'field' => 'slug',
                'terms' => $provider_slugs,
                'operator' => 'IN',
            ]];
        }

        $location_ids = get_posts($location_query_args);
        return array_values(array_filter(array_map('intval', (array) $location_ids)));
    }

    private static function get_location_facility_slugs($location_id) {
        $location_id = (int) $location_id;
        if ($location_id <= 0) {
            return [];
        }
        $terms = get_the_terms($location_id, 'facility');
        if (!is_array($terms) || empty($terms)) {
            return [];
        }
        $slugs = [];
        foreach ($terms as $term) {
            if (!($term instanceof WP_Term)) {
                continue;
            }
            $slug = sanitize_title((string) $term->slug);
            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }
        return array_values(array_unique($slugs));
    }

    private static function sanitize_facility_slugs($raw_slugs) {
        $raw_slugs = is_array($raw_slugs) ? $raw_slugs : [];
        if (empty($raw_slugs)) {
            return [];
        }
        $options = get_terms([
            'taxonomy' => 'facility',
            'hide_empty' => false,
        ]);
        $allowed = [];
        if (is_array($options)) {
            foreach ($options as $term) {
                if (!($term instanceof WP_Term)) {
                    continue;
                }
                $slug = sanitize_title((string) $term->slug);
                if ($slug !== '') {
                    $allowed[$slug] = true;
                }
            }
        }
        $clean = [];
        foreach ($raw_slugs as $slug_raw) {
            $slug = sanitize_title((string) $slug_raw);
            if ($slug === '' || !isset($allowed[$slug])) {
                continue;
            }
            $clean[] = $slug;
        }
        return array_values(array_unique($clean));
    }

    private static function facility_labels_from_slugs($slugs) {
        $slugs = array_values(array_filter(array_map('sanitize_title', (array) $slugs)));
        if (empty($slugs)) {
            return [];
        }
        $terms = get_terms([
            'taxonomy' => 'facility',
            'hide_empty' => false,
            'slug' => $slugs,
        ]);
        if (!is_array($terms) || empty($terms)) {
            return $slugs;
        }
        $by_slug = [];
        foreach ($terms as $term) {
            if (!($term instanceof WP_Term)) {
                continue;
            }
            $by_slug[(string) $term->slug] = (string) $term->name;
        }
        $labels = [];
        foreach ($slugs as $slug) {
            $labels[] = isset($by_slug[$slug]) ? (string) $by_slug[$slug] : (string) $slug;
        }
        return $labels;
    }

    public static function ajax_get_inline_location_edit_context() {
        check_ajax_referer(self::NONCE_ACTION_EDIT_ACCESS, 'nonce');
        $session_pack = self::get_inline_editor_session_or_error();
        $settings = is_array($session_pack) && isset($session_pack[1]) && is_array($session_pack[1]) ? $session_pack[1] : self::get_settings();

        $location_id = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
        if ($location_id <= 0 || get_post_type($location_id) !== 'location') {
            self::send_json_error_debug(__('ข้อมูลสถานที่ไม่ถูกต้อง', 'lc-public-place-photo-upload'), 400, 'invalid_location', 'load_location_context', ['location_id' => $location_id]);
        }

        $gallery_meta_key = (string) ($settings['location_gallery_meta_key'] ?? 'images');
        $image_ids = self::parse_gallery_ids(get_post_meta($location_id, $gallery_meta_key, true));
        if ($gallery_meta_key !== 'images') {
            $legacy_image_ids = self::parse_gallery_ids(get_post_meta($location_id, 'images', true));
            if (!empty($legacy_image_ids)) {
                $image_ids = array_values(array_unique(array_merge($image_ids, $legacy_image_ids)));
            }
        }

        $images = [];
        foreach ($image_ids as $aid) {
            $aid = (int) $aid;
            if ($aid <= 0 || get_post_type($aid) !== 'attachment') {
                continue;
            }
            $thumb = wp_get_attachment_image_url($aid, 'thumbnail');
            $medium = wp_get_attachment_image_url($aid, 'medium');
            $large = wp_get_attachment_image_url($aid, 'large');
            $full = wp_get_attachment_url($aid);
            if (!$full) {
                continue;
            }
            $images[] = [
                'id' => $aid,
                'thumb' => is_string($thumb) && $thumb !== '' ? $thumb : $full,
                'medium' => is_string($medium) && $medium !== '' ? $medium : $full,
                'large' => is_string($large) && $large !== '' ? $large : $full,
                'url' => $full,
                'caption' => (string) wp_get_attachment_caption($aid),
            ];
        }

        $sessions = [];
        $session_ids = self::get_location_session_ids($location_id);
        foreach ($session_ids as $sid) {
            if ($sid <= 0 || get_post_type($sid) !== 'session') {
                continue;
            }
            $course_id = (int) get_post_meta($sid, 'course', true);
            $sessions[] = [
                'id' => $sid,
                'title' => (string) get_the_title($sid),
                'course_id' => $course_id,
                'course_title' => $course_id > 0 ? (string) get_the_title($course_id) : '',
                'time_period' => self::normalize_multiline_text((string) get_post_meta($sid, 'time_period', true)),
                'session_details' => self::normalize_multiline_text((string) get_post_meta($sid, 'session_details', true)),
            ];
        }

        $available_courses = [];
        foreach (self::get_location_available_course_ids($location_id) as $course_id) {
            if ($course_id <= 0 || get_post_type($course_id) !== 'course') {
                continue;
            }
            $title = (string) get_the_title($course_id);
            if ($title === '') {
                continue;
            }
            $available_courses[] = [
                'id' => $course_id,
                'title' => $title,
            ];
        }

        $facility_options = [];
        $facility_terms = get_terms([
            'taxonomy' => 'facility',
            'hide_empty' => false,
        ]);
        if (is_array($facility_terms)) {
            foreach ($facility_terms as $term) {
                if (!($term instanceof WP_Term)) {
                    continue;
                }
                $slug = sanitize_title((string) $term->slug);
                if ($slug === '') {
                    continue;
                }
                $facility_options[] = [
                    'slug' => $slug,
                    'name' => (string) $term->name,
                ];
            }
        }

        wp_send_json_success([
            'location' => [
                'title' => (string) get_the_title($location_id),
                'address' => (string) get_post_meta($location_id, 'address', true),
                'phone' => (string) get_post_meta($location_id, 'phone', true),
                'opening_hours' => self::normalize_multiline_text((string) get_post_meta($location_id, 'opening_hours', true)),
                'description' => self::normalize_multiline_text((string) get_post_meta($location_id, 'description', true)),
                'google_maps' => (string) get_post_meta($location_id, 'google_maps', true),
                'facebook' => (string) get_post_meta($location_id, 'facebook', true),
                'facility_slugs' => self::get_location_facility_slugs($location_id),
            ],
            'images' => $images,
            'sessions' => $sessions,
            'available_courses' => $available_courses,
            'facility_options' => $facility_options,
        ]);
    }

    private static function render_requester_dashboard($token, $requester_email, $focus_location_id = 0) {
        $requester_email = sanitize_email((string) $requester_email);
        $focus_location_id = (int) $focus_location_id;
        $focus_request_id = isset($_GET['focus_request_id']) ? (int) $_GET['focus_request_id'] : 0;
        $dashboard = self::build_requester_dashboard_payload($requester_email);
        $rows = is_array($dashboard['rows'] ?? null) ? $dashboard['rows'] : [];
        $counts = is_array($dashboard['counts'] ?? null) ? $dashboard['counts'] : ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
        $type_counts = is_array($dashboard['type_counts'] ?? null) ? $dashboard['type_counts'] : ['all' => 0, 'location' => 0, 'course' => 0];
        $ajax_nonce = wp_create_nonce(self::NONCE_ACTION_EDIT_ACCESS);
        $ajax_url = admin_url('admin-ajax.php');
        $picker_url = add_query_arg(['lc_location_edit' => 1, 'mode' => 'pick', 'req_kind' => 'edit'], home_url('/'));
        $logout_url = add_query_arg(['lc_location_edit' => 1, 'lc_location_logout' => 1, '_lc_logout_nonce' => self::current_logout_nonce()], home_url('/'));

        status_header(200);
        nocache_headers();
        get_header();
        ?>
            <div class="app-layout overflow-visible!">
            <?php get_template_part('template-parts/header/site-header'); ?>
            <main id="primary" class="flex-1">
            <style>
            .lc-rq-shell{max-width:1200px;margin:18px auto 26px;padding:0 16px}
            .lc-rq-hero{border:1px solid #d5f2e5;background:linear-gradient(140deg,#f4fff9 0%,#ebf7ff 100%);border-radius:18px;padding:16px 18px;margin-bottom:12px;display:grid;gap:10px}
            .lc-rq-title{margin:0;font-size:34px;font-weight:900;color:#0f2233;line-height:1.1}
            .lc-rq-sub{margin:0;color:#35536b}
            .lc-rq-hero-actions{display:flex;gap:8px;flex-wrap:wrap}
            .lc-rq-btn{height:40px;border-radius:11px;padding:0 14px;border:1px solid #0f766e;background:#0f766e;color:#fff;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:8px;cursor:pointer}
            .lc-rq-btn.secondary{border-color:#cfd8e3;background:#fff;color:#0f172a}
            .lc-rq-refresh{margin-left:auto}
            .lc-rq-toolbar{display:grid;grid-template-columns:1fr auto;gap:10px;border:1px solid #dbe4ee;border-radius:14px;background:#fff;padding:12px}
            .lc-rq-search{height:40px;border:1px solid #cbd5e1;border-radius:10px;padding:0 12px;width:100%}
            .lc-rq-chips{display:flex;flex-wrap:wrap;gap:8px}
            .lc-rq-chip{height:36px;padding:0 12px;border:1px solid #d1d9e2;border-radius:999px;background:#fff;cursor:pointer;font-weight:800;color:#0f172a}
            .lc-rq-chip.is-active{background:#0f766e;border-color:#0f766e;color:#fff}
            .lc-rq-list{display:grid;gap:10px;margin-top:12px}
            .lc-rq-card{border:1px solid #dbe4ee;border-radius:14px;background:#fff;padding:14px;display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center}
            .lc-rq-main{min-width:0}
            .lc-rq-name{font-weight:900;color:#0f172a;font-size:31px;line-height:1.15}
            .lc-rq-meta{margin-top:6px;display:flex;flex-wrap:wrap;gap:10px;color:#64748b;font-size:15px}
            .lc-rq-side{display:flex;flex-direction:column;align-items:flex-end;gap:8px}
            .lc-rq-status{display:inline-flex;align-items:center;height:28px;padding:0 11px;border-radius:999px;font-size:12px;font-weight:900}
            .lc-rq-status.pending{background:#fff7ed;color:#9a3412}
            .lc-rq-status.approved{background:#ecfdf3;color:#166534}
            .lc-rq-status.rejected{background:#fef2f2;color:#991b1b}
            .lc-rq-status.cancelled{background:#f1f5f9;color:#475569}
            .lc-rq-reason{margin-top:10px;border:1px solid #fecaca;background:#fff5f5;border-radius:10px;padding:8px;color:#7f1d1d;grid-column:1/-1}
            .lc-rq-empty{border:1px dashed #cbd5e1;border-radius:14px;padding:22px;color:#64748b;background:#fff}
            .lc-modal{position:fixed;inset:0;background:rgba(12,19,33,.55);display:none;z-index:999999}
            .lc-modal.is-open{display:block}
            .lc-modal-card{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);background:#fff;border-radius:14px;border:1px solid #dbe4ee;box-shadow:0 20px 60px rgba(15,23,42,.25)}
            .lc-pick-card{width:min(860px,calc(100vw - 32px));max-height:min(86vh,760px);display:grid;grid-template-rows:auto auto 1fr}
            .lc-pick-head,.lc-edit-head{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid #e2e8f0}
            .lc-pick-list{padding:12px;overflow:auto;display:grid;gap:8px}
            .lc-pick-item{border:1px solid #dbe4ee;border-radius:10px;padding:10px 12px;display:flex;justify-content:space-between;align-items:center;gap:10px}
            .lc-pick-item-name{font-weight:700;color:#0f172a}
            .lc-edit-card{width:min(1180px,calc(100vw - 24px));height:min(90vh,900px);display:grid;grid-template-rows:auto 1fr}
            .lc-edit-iframe{width:100%;height:100%;border:0;background:#fff}
            .lc-close{border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:9px;height:34px;padding:0 10px;font-weight:700;cursor:pointer}
            @media (max-width: 900px){
              .lc-rq-title{font-size:30px}
              .lc-rq-toolbar{grid-template-columns:1fr}
              .lc-rq-card{grid-template-columns:1fr}
              .lc-rq-side{align-items:flex-start}
              .lc-rq-name{font-size:26px}
              .lc-rq-refresh{margin-left:0}
            }
            </style>
            <main class="lc-rq-shell">
                <div class="lc-rq-hero">
                    <h1 class="lc-rq-title">คำขอแก้ไขข้อมูลของฉัน</h1>
                    <p class="lc-rq-sub">ติดตามสถานะคำขอทั้งสถานที่และคอร์ส พร้อมกรองตามประเภทได้</p>
                    <div class="lc-rq-hero-actions">
                        <button type="button" class="lc-rq-btn secondary" id="lcOpenPickerBtn">แก้ไขสถานที่อื่น</button>
                        <button type="button" class="lc-rq-btn secondary lc-rq-refresh" id="lcRefreshDashboard">รีเฟรช</button>
                        <a class="lc-rq-btn secondary" href="<?php echo esc_url($logout_url); ?>">ออกจากระบบ</a>
                    </div>
                    <?php if (isset($_GET['lc_change_sent']) && (string) $_GET['lc_change_sent'] === '1') : ?>
                        <div style="margin-top:8px;padding:8px 10px;border:1px solid #86efac;border-radius:10px;background:#f0fdf4;color:#166534;font-weight:700;">ส่งคำขอแก้ไขเรียบร้อยแล้ว</div>
                    <?php endif; ?>
                </div>
                <section class="lc-rq-toolbar">
                    <input type="text" class="lc-rq-search" id="lcRqSearch" placeholder="ค้นหาชื่อสถานที่หรือคอร์ส...">
                    <div class="lc-rq-chips" id="lcRqTabs">
                        <button type="button" class="lc-rq-chip is-active" data-status="all">ทั้งหมด (<?php echo esc_html((string) $counts['all']); ?>)</button>
                        <button type="button" class="lc-rq-chip" data-status="pending">รอตรวจสอบ (<?php echo esc_html((string) $counts['pending']); ?>)</button>
                        <button type="button" class="lc-rq-chip" data-status="approved">อนุมัติ (<?php echo esc_html((string) $counts['approved']); ?>)</button>
                        <button type="button" class="lc-rq-chip" data-status="rejected">ไม่อนุมัติ (<?php echo esc_html((string) $counts['rejected']); ?>)</button>
                        <button type="button" class="lc-rq-chip" data-status="cancelled">ยกเลิก (<?php echo esc_html((string) $counts['cancelled']); ?>)</button>
                    </div>
                    <div class="lc-rq-chips" id="lcRqTypeTabs">
                        <button type="button" class="lc-rq-chip is-active" data-type="all">ทุกประเภท (<?php echo esc_html((string) $type_counts['all']); ?>)</button>
                        <button type="button" class="lc-rq-chip" data-type="location">สถานที่ (<?php echo esc_html((string) $type_counts['location']); ?>)</button>
                        <button type="button" class="lc-rq-chip" data-type="course">คอร์ส (<?php echo esc_html((string) $type_counts['course']); ?>)</button>
                    </div>
                </section>
                <section class="lc-rq-list" id="lcRqList">
                    <?php if (empty($rows)) : ?>
                        <div class="lc-rq-empty">ยังไม่มีคำขอแก้ไข กด “แก้ไขสถานที่อื่น” เพื่อเริ่มส่งคำขอ</div>
                    <?php else : ?>
                        <?php foreach ($rows as $row) :
                            $location_id = (int) ($row['location_id'] ?? 0);
                            $status = (string) ($row['status'] ?? 'pending');
                            $status_label = $status === 'approved' ? 'อนุมัติ' : ($status === 'rejected' ? 'ไม่อนุมัติ' : ($status === 'cancelled' ? 'ยกเลิก' : 'รอตรวจสอบ'));
                            $edit_url = (string) ($row['edit_url'] ?? '');
                            ?>
                            <?php $target_type = (string) ($row['target_type'] ?? 'location'); ?>
                            <article class="lc-rq-card" data-status="<?php echo esc_attr($status); ?>" data-target-type="<?php echo esc_attr($target_type); ?>" data-location-id="<?php echo esc_attr((string) $location_id); ?>" data-request-id="<?php echo esc_attr((string) ((int) ($row['request_id'] ?? 0))); ?>" data-name="<?php echo esc_attr((string) ($row['location_title'] ?? '')); ?>">
                                <div class="lc-rq-main">
                                    <div class="lc-rq-name"><?php echo esc_html((string) ($row['location_title'] ?: ('รายการ #' . $location_id))); ?></div>
                                    <div class="lc-rq-meta">
                                        <span>ประเภท: <?php echo esc_html((string) ($row['target_type_label'] ?? 'สถานที่')); ?></span>
                                        <span>คำขอ #<?php echo esc_html((string) ($row['request_id'] ?? 0)); ?></span>
                                        <span>ส่งเมื่อ: <?php echo esc_html((string) ($row['submitted_at'] ?? '-')); ?></span>
                                        <?php if ((string) ($row['moderated_at'] ?? '') !== '') : ?>
                                            <span>ตรวจเมื่อ: <?php echo esc_html((string) $row['moderated_at']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="lc-rq-side">
                                    <span class="lc-rq-status <?php echo esc_attr(in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true) ? $status : 'pending'); ?>"><?php echo esc_html($status_label); ?></span>
                                    <?php if ($edit_url !== '') : ?>
                                    <button type="button" class="lc-rq-btn lc-open-edit" data-edit-url="<?php echo esc_attr($edit_url); ?>" data-title="<?php echo esc_attr((string) ($row['location_title'] ?? '')); ?>">เปิดฟอร์มแก้ไข</button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($status === 'rejected' && (string) ($row['reject_reason'] ?? '') !== '') : ?>
                                    <div class="lc-rq-reason"><strong>เหตุผลที่ไม่อนุมัติ:</strong> <?php echo esc_html((string) $row['reject_reason']); ?></div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </main>
            <div class="lc-modal" id="lcPickModal">
                <div class="lc-modal-card lc-pick-card">
                    <div class="lc-pick-head">
                        <strong>เลือกสถานที่ที่ต้องการแก้ไข</strong>
                        <button type="button" class="lc-close" data-close-modal="lcPickModal">ปิด</button>
                    </div>
                    <div style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">
                        <input type="text" class="lc-rq-search" id="lcPickSearch" placeholder="ค้นหาชื่อสถานที่...">
                    </div>
                    <div class="lc-pick-list" id="lcPickList">
                        <div style="color:#64748b;">กำลังโหลด...</div>
                    </div>
                </div>
            </div>
            <div class="lc-modal" id="lcEditModal">
                <div class="lc-modal-card lc-edit-card">
                    <div class="lc-edit-head">
                        <strong id="lcEditModalTitle">แก้ไขข้อมูลสถานที่</strong>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <a id="lcEditOpenNewTab" class="lc-close" href="#" target="_blank" rel="noopener noreferrer" style="text-decoration:none;display:inline-flex;align-items:center;">เปิดในแท็บใหม่</a>
                            <button type="button" class="lc-close" data-close-modal="lcEditModal">ปิด</button>
                        </div>
                    </div>
                    <iframe id="lcEditIframe" class="lc-edit-iframe" src="about:blank"></iframe>
                </div>
            </div>
            <script>
            (function(){
              const ajaxUrl = <?php echo wp_json_encode($ajax_url); ?>;
              const ajaxNonce = <?php echo wp_json_encode($ajax_nonce); ?>;
              const pickerFallbackUrl = <?php echo wp_json_encode($picker_url); ?>;
              const initialRows = <?php echo wp_json_encode($rows); ?>;
              const tabs = Array.from(document.querySelectorAll('#lcRqTabs .lc-rq-chip'));
              const typeTabs = Array.from(document.querySelectorAll('#lcRqTypeTabs .lc-rq-chip'));
              const list = document.getElementById('lcRqList');
              const searchInput = document.getElementById('lcRqSearch');
              const refreshBtn = document.getElementById('lcRefreshDashboard');
              const openPickerBtn = document.getElementById('lcOpenPickerBtn');
              const pickModal = document.getElementById('lcPickModal');
              const pickList = document.getElementById('lcPickList');
              const pickSearch = document.getElementById('lcPickSearch');
              const editModal = document.getElementById('lcEditModal');
              const editIframe = document.getElementById('lcEditIframe');
              const editModalTitle = document.getElementById('lcEditModalTitle');
              const editOpenNewTab = document.getElementById('lcEditOpenNewTab');
              const focusId = <?php echo (int) $focus_location_id; ?>;
              const focusRequestId = <?php echo (int) $focus_request_id; ?>;
              let rows = Array.isArray(initialRows) ? initialRows : [];
              let activeStatus = 'all';
              let activeType = 'all';
              let locationRows = null;

              const esc = (v) => String(v || '').replace(/[&<>"']/g, (ch) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch] || ch));
              const statusLabel = (s) => (s === 'approved' ? 'อนุมัติ' : (s === 'rejected' ? 'ไม่อนุมัติ' : (s === 'cancelled' ? 'ยกเลิก' : 'รอตรวจสอบ')));
              const statusClass = (s) => (['pending','approved','rejected','cancelled'].includes(s) ? s : 'pending');
              const matchSearch = (name) => {
                const q = String(searchInput?.value || '').trim().toLowerCase();
                return q === '' || String(name || '').toLowerCase().includes(q);
              };
              const closeModal = (id) => {
                const m = document.getElementById(id);
                if (!m) return;
                m.classList.remove('is-open');
                if (id === 'lcEditModal' && editIframe) {
                  editIframe.setAttribute('src', 'about:blank');
                }
              };
              const openModal = (id) => {
                const m = document.getElementById(id);
                if (!m) return;
                m.classList.add('is-open');
              };
              const renderRows = () => {
                if (!list) return;
                const visible = rows.filter((row) => {
                  const status = String(row?.status || 'pending');
                  const statusPass = (activeStatus === 'all' || status === activeStatus);
                  const rowType = String(row?.target_type || 'location');
                  const typePass = (activeType === 'all' || rowType === activeType);
                  return statusPass && typePass && matchSearch(row?.location_title || row?.target_title || '');
                });
                if (!visible.length) {
                  list.innerHTML = '<div class="lc-rq-empty">ไม่พบรายการตามตัวกรองที่เลือก</div>';
                  return;
                }
                list.innerHTML = visible.map((row) => {
                  const status = String(row?.status || 'pending');
                  const rowType = String(row?.target_type || 'location');
                  const reason = String(row?.reject_reason || '');
                  const moderated = String(row?.moderated_at || '');
                  const targetLabel = String(row?.target_type_label || (rowType === 'course' ? 'คอร์ส' : 'สถานที่'));
                  const editUrl = String(row?.edit_url || '');
                  return '<article class="lc-rq-card" data-status="' + esc(status) + '" data-target-type="' + esc(rowType) + '" data-location-id="' + esc(row?.location_id || 0) + '" data-request-id="' + esc(row?.request_id || 0) + '" data-name="' + esc(row?.location_title || row?.target_title || '') + '">' +
                    '<div class="lc-rq-main">' +
                      '<div class="lc-rq-name">' + esc(row?.location_title || row?.target_title || ('รายการ #' + String(row?.location_id || 0))) + '</div>' +
                      '<div class="lc-rq-meta">' +
                        '<span>ประเภท: ' + esc(targetLabel) + '</span>' +
                        '<span>คำขอ #' + esc(row?.request_id || 0) + '</span>' +
                        '<span>ส่งเมื่อ: ' + esc(row?.submitted_at || '-') + '</span>' +
                        (moderated ? '<span>ตรวจเมื่อ: ' + esc(moderated) + '</span>' : '') +
                      '</div>' +
                    '</div>' +
                    '<div class="lc-rq-side">' +
                      '<span class="lc-rq-status ' + esc(statusClass(status)) + '">' + esc(statusLabel(status)) + '</span>' +
                      (editUrl ? '<button type="button" class="lc-rq-btn lc-open-edit" data-edit-url="' + esc(editUrl) + '" data-title="' + esc(row?.location_title || row?.target_title || '') + '">เปิดฟอร์มแก้ไข</button>' : '') +
                    '</div>' +
                    ((status === 'rejected' && reason) ? '<div class="lc-rq-reason"><strong>เหตุผลที่ไม่อนุมัติ:</strong> ' + esc(reason) + '</div>' : '') +
                  '</article>';
                }).join('');
                bindEditButtons();
              };
              const applyFilter = (status) => {
                tabs.forEach((t) => t.classList.toggle('is-active', t.dataset.status === status));
                renderRows();
              };
              const applyTypeFilter = (type) => {
                typeTabs.forEach((t) => t.classList.toggle('is-active', t.dataset.type === type));
                renderRows();
              };
              const updateCountChips = (counts) => {
                const map = counts || {};
                tabs.forEach((t) => {
                  const status = String(t.dataset.status || 'all');
                  const count = Number(map?.[status] || 0);
                        const label = status === 'all' ? 'ทั้งหมด' : (status === 'pending' ? 'รอตรวจสอบ' : (status === 'approved' ? 'อนุมัติ' : 'ไม่อนุมัติ'));
                  t.textContent = label + ' (' + String(count) + ')';
                });
              };
              const updateTypeCountChips = (counts) => {
                const map = counts || {};
                typeTabs.forEach((t) => {
                  const type = String(t.dataset.type || 'all');
                  const count = Number(map?.[type] || 0);
                  const label = type === 'all' ? 'ทุกประเภท' : (type === 'course' ? 'คอร์ส' : 'สถานที่');
                  t.textContent = label + ' (' + String(count) + ')';
                });
              };
              const fetchDashboard = async () => {
                if (refreshBtn) refreshBtn.setAttribute('disabled', 'disabled');
                try {
                  const fd = new FormData();
                  fd.append('action', 'lc_fetch_location_edit_dashboard');
                  fd.append('nonce', String(ajaxNonce || ''));
                  const res = await fetch(String(ajaxUrl || ''), { method: 'POST', body: fd });
                  const json = await res.json();
                  if (!json?.success) throw new Error(json?.data?.message || 'โหลดข้อมูลไม่สำเร็จ');
                  rows = Array.isArray(json?.data?.rows) ? json.data.rows : [];
                  updateCountChips(json?.data?.counts || {});
                  updateTypeCountChips(json?.data?.type_counts || {});
                  renderRows();
                } catch (err) {
                  if (list) {
                    list.innerHTML = '<div class="lc-rq-empty">' + esc(String(err?.message || 'โหลดข้อมูลไม่สำเร็จ')) + '</div>';
                  }
                } finally {
                  if (refreshBtn) refreshBtn.removeAttribute('disabled');
                }
              };
              const bindEditButtons = () => {
                const editBtns = Array.from(document.querySelectorAll('.lc-open-edit'));
                editBtns.forEach((btn) => {
                  btn.addEventListener('click', () => {
                    const url = String(btn.getAttribute('data-edit-url') || '');
                    if (!url) return;
                    const title = String(btn.getAttribute('data-title') || 'แก้ไขข้อมูลสถานที่');
                    if (editIframe) editIframe.setAttribute('src', url);
                    if (editOpenNewTab) editOpenNewTab.setAttribute('href', url);
                    if (editModalTitle) editModalTitle.textContent = title;
                    openModal('lcEditModal');
                  });
                });
              };
              const renderLocationRows = () => {
                if (!pickList) return;
                const q = String(pickSearch?.value || '').trim().toLowerCase();
                const arr = (Array.isArray(locationRows) ? locationRows : []).filter((row) => {
                  return q === '' || String(row?.location_title || '').toLowerCase().includes(q);
                });
                if (!arr.length) {
                  pickList.innerHTML = '<div style="color:#64748b;">ไม่พบสถานที่</div>';
                  return;
                }
                pickList.innerHTML = arr.map((row) => {
                  return '<article class="lc-pick-item"><div class="lc-pick-item-name">' + esc(row?.location_title || '') + '</div><button type="button" class="lc-rq-btn lc-pick-open" data-edit-url="' + esc(row?.edit_url || '') + '" data-title="' + esc(row?.location_title || '') + '">เปิดฟอร์มแก้ไข</button></article>';
                }).join('');
                Array.from(pickList.querySelectorAll('.lc-pick-open')).forEach((btn) => {
                  btn.addEventListener('click', () => {
                    const url = String(btn.getAttribute('data-edit-url') || '');
                    if (!url) return;
                    const title = String(btn.getAttribute('data-title') || 'แก้ไขข้อมูลสถานที่');
                    closeModal('lcPickModal');
                    if (editIframe) editIframe.setAttribute('src', url);
                    if (editOpenNewTab) editOpenNewTab.setAttribute('href', url);
                    if (editModalTitle) editModalTitle.textContent = title;
                    openModal('lcEditModal');
                  });
                });
              };
              const openPicker = async () => {
                openModal('lcPickModal');
                if (Array.isArray(locationRows)) {
                  renderLocationRows();
                  return;
                }
                if (pickList) pickList.innerHTML = '<div style="color:#64748b;">กำลังโหลด...</div>';
                try {
                  const fd = new FormData();
                  fd.append('action', 'lc_fetch_editable_locations');
                  fd.append('nonce', String(ajaxNonce || ''));
                  const res = await fetch(String(ajaxUrl || ''), { method: 'POST', body: fd });
                  const json = await res.json();
                  if (!json?.success) throw new Error(json?.data?.message || 'โหลดสถานที่ไม่สำเร็จ');
                  locationRows = Array.isArray(json?.data?.rows) ? json.data.rows : [];
                  renderLocationRows();
                } catch (err) {
                  locationRows = [];
                  if (pickList) {
                    pickList.innerHTML = '<div style="display:grid;gap:10px;"><div style="color:#7f1d1d;">' + esc(String(err?.message || 'โหลดสถานที่ไม่สำเร็จ')) + '</div><a class="lc-rq-btn secondary" href="' + esc(pickerFallbackUrl) + '">เปิดหน้าเลือกสถานที่แบบเดิม</a></div>';
                  }
                }
              };

              tabs.forEach((t) => t.addEventListener('click', () => {
                activeStatus = t.dataset.status || 'all';
                applyFilter(activeStatus);
              }));
              typeTabs.forEach((t) => t.addEventListener('click', () => {
                activeType = t.dataset.type || 'all';
                applyTypeFilter(activeType);
              }));
              searchInput?.addEventListener('input', () => renderRows());
              refreshBtn?.addEventListener('click', () => fetchDashboard());
              openPickerBtn?.addEventListener('click', () => openPicker());
              pickSearch?.addEventListener('input', () => renderLocationRows());
              document.querySelectorAll('[data-close-modal]').forEach((btn) => {
                btn.addEventListener('click', () => closeModal(String(btn.getAttribute('data-close-modal') || '')));
              });
              [pickModal, editModal].forEach((m) => {
                m?.addEventListener('click', (e) => {
                  if (e.target === m) {
                    m.classList.remove('is-open');
                    if (m === editModal && editIframe) {
                      editIframe.setAttribute('src', 'about:blank');
                    }
                  }
                });
              });
              editIframe?.addEventListener('load', () => {
                try {
                  const href = String(editIframe.contentWindow?.location?.href || '');
                  if (!href) return;
                  const u = new URL(href, window.location.origin);
                  if (u.searchParams.get('lc_change_sent') === '1') {
                    closeModal('lcEditModal');
                    fetchDashboard();
                  }
                } catch (e) {}
              });
              applyFilter(activeStatus);
              applyTypeFilter(activeType);
              bindEditButtons();
              if (focusId > 0) {
                const target = document.querySelector('#lcRqList .lc-rq-card[data-location-id="' + String(focusId) + '"]');
                if (target) {
                  target.scrollIntoView({behavior:'smooth', block:'center'});
                  target.style.boxShadow = '0 0 0 2px #0f766e inset';
                  setTimeout(() => { target.style.boxShadow = ''; }, 2200);
                }
              }
              if (focusRequestId > 0) {
                const target = document.querySelector('#lcRqList .lc-rq-card[data-request-id="' + String(focusRequestId) + '"]');
                if (target) {
                  target.scrollIntoView({behavior:'smooth', block:'center'});
                  target.style.boxShadow = '0 0 0 2px #0f766e inset';
                  setTimeout(() => { target.style.boxShadow = ''; }, 2200);
                }
              }
            })();
            </script>
            </main>
            <?php get_template_part('template-parts/footer/site-footer'); ?>
            </div>
        <?php
        get_footer();
        exit;
    }

    private static function render_location_picker_page($token) {
        $locations = get_posts([
            'post_type' => 'location',
            'post_status' => 'publish',
            'posts_per_page' => 1200,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        status_header(200);
        nocache_headers();
        ?>
        <!doctype html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html__('เลือกสถานที่ที่ต้องการแก้ไข', 'lc-public-place-photo-upload'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class('lc-location-picker-page'); ?>>
            <style>
            .lc-pick-shell{max-width:980px;margin:20px auto;padding:0 16px}
            .lc-pick-hero{border:1px solid #d1fae5;background:linear-gradient(180deg,#f0fdf4 0%,#fff 100%);border-radius:14px;padding:14px 16px;margin-bottom:12px}
            .lc-pick-title{margin:0 0 4px;font-size:28px;font-weight:800;color:#052e2b}
            .lc-pick-sub{margin:0;color:#166534}
            .lc-pick-card{border:1px solid #e2e8f0;background:#fff;border-radius:14px;padding:12px}
            .lc-pick-search{width:100%;height:44px;border:1px solid #cbd5e1;border-radius:10px;padding:0 12px;box-sizing:border-box}
            .lc-pick-list{margin-top:10px;display:grid;gap:8px;max-height:65vh;overflow:auto}
            .lc-pick-item{display:flex;justify-content:space-between;align-items:center;gap:8px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;padding:10px}
            .lc-pick-name{font-weight:700;color:#0f172a}
            .lc-pick-btn{height:34px;border-radius:8px;padding:0 12px;border:1px solid #0f766e;background:#0f766e;color:#fff;font-weight:700;text-decoration:none;display:inline-flex;align-items:center}
            .lc-pick-back{display:inline-flex;align-items:center;margin-top:10px;color:#0f766e;font-weight:700;text-decoration:none}
            .lc-pick-empty{color:#64748b;padding:8px}
            </style>
            <main class="lc-pick-shell">
                <div class="lc-pick-hero">
                    <h1 class="lc-pick-title">เลือกสถานที่ที่ต้องการแก้ไข</h1>
                    <p class="lc-pick-sub">ค้นหาสถานที่จากรายการ แล้วกดแก้ไขเพื่อส่งคำขอให้แอดมินตรวจสอบ</p>
                </div>
                <section class="lc-pick-card">
                    <input id="lcPickSearch" class="lc-pick-search" type="text" placeholder="ค้นหาชื่อสถานที่...">
                    <div id="lcPickList" class="lc-pick-list">
                        <?php if (empty($locations)) : ?>
                            <div class="lc-pick-empty">ไม่พบข้อมูลสถานที่</div>
                        <?php else : ?>
                            <?php foreach ($locations as $location_id) :
                                $location_id = (int) $location_id;
                                $title = (string) get_the_title($location_id);
                                if ($title === '') {
                                    continue;
                                }
                                $edit_url = add_query_arg([
                                    'lc_location_edit' => 1,
                                    'place_id' => $location_id,
                                ], home_url('/'));
                                ?>
                                <article class="lc-pick-item" data-title="<?php echo esc_attr(function_exists('mb_strtolower') ? mb_strtolower($title) : strtolower($title)); ?>">
                                    <div class="lc-pick-name"><?php echo esc_html($title); ?></div>
                                    <a class="lc-pick-btn" href="<?php echo esc_url($edit_url); ?>">แก้ไขข้อมูล</a>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                        <a class="lc-pick-back" href="<?php echo esc_url(add_query_arg(['lc_location_edit' => 1, 'req_kind' => 'edit'], home_url('/'))); ?>">&larr; กลับไปหน้าคำขอของฉัน</a>
                        <a class="lc-pick-back" style="color:#7f1d1d;" href="<?php echo esc_url(add_query_arg(['lc_location_edit' => 1, 'lc_location_logout' => 1, '_lc_logout_nonce' => self::current_logout_nonce()], home_url('/'))); ?>">ออกจากระบบ</a>
                    </div>
                </section>
            </main>
            <script>
            (function(){
              const input = document.getElementById('lcPickSearch');
              const rows = Array.from(document.querySelectorAll('#lcPickList .lc-pick-item'));
              if(!input){ return; }
              const norm = (v) => String(v || '').toLowerCase().trim();
              input.addEventListener('input', function(){
                const q = norm(input.value);
                rows.forEach((row) => {
                  const title = norm(row.getAttribute('data-title') || row.textContent || '');
                  row.style.display = (q === '' || title.indexOf(q) !== -1) ? '' : 'none';
                });
              });
            })();
            </script>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    public static function get_settings() {
        $defaults = [
            'upload_enabled' => '1',
            'location_gallery_meta_key' => 'images',
            'upload_form_url' => home_url('/?lc_place_photo_upload=1'),
            'allowed_submitter_emails' => '',
            'location_edit_enabled' => '1',
            'allowed_editor_emails' => '',
            'otp_ttl_minutes' => 10,
            'brevo_api_key' => '',
            'brevo_sender_email' => '',
            'brevo_sender_name' => '',
            'max_files_per_submission' => 6,
            'max_file_size_mb' => 8,
            'max_submissions_per_hour' => 8,
        ];

        $saved = get_option(self::OPT_KEY, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        $settings = array_merge($defaults, $saved);
        $settings['max_files_per_submission'] = max(1, (int) $settings['max_files_per_submission']);
        $settings['max_file_size_mb'] = max(1, (int) $settings['max_file_size_mb']);
        $settings['max_submissions_per_hour'] = max(1, (int) $settings['max_submissions_per_hour']);
        $settings['otp_ttl_minutes'] = max(3, min(30, (int) ($settings['otp_ttl_minutes'] ?? 10)));
        $settings['location_gallery_meta_key'] = sanitize_key($settings['location_gallery_meta_key']);

        if ($settings['location_gallery_meta_key'] === '') {
            $settings['location_gallery_meta_key'] = 'images';
        }

        return $settings;
    }

    public static function register_settings() {
        register_setting(
            'lc_public_place_upload_settings_group',
            self::OPT_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
                'default' => self::get_settings(),
            ]
        );
    }

    public static function sanitize_settings($input) {
        $current = self::get_settings();
        if (!is_array($input)) {
            return $current;
        }

        if (isset($input['upload_enabled'])) {
            $current['upload_enabled'] = empty($input['upload_enabled']) ? '0' : '1';
        }
        if (isset($input['location_gallery_meta_key'])) {
            $meta_key = sanitize_key((string) $input['location_gallery_meta_key']);
            $current['location_gallery_meta_key'] = $meta_key !== '' ? $meta_key : 'images';
        }

        if (isset($input['upload_form_url'])) {
            $current['upload_form_url'] = esc_url_raw((string) $input['upload_form_url']);
        }

        if (isset($input['allowed_submitter_emails'])) {
            $current['allowed_submitter_emails'] = sanitize_textarea_field((string) $input['allowed_submitter_emails']);
        }
        if (isset($input['location_edit_enabled'])) {
            $current['location_edit_enabled'] = empty($input['location_edit_enabled']) ? '0' : '1';
        }
        if (isset($input['allowed_editor_emails'])) {
            $current['allowed_editor_emails'] = sanitize_textarea_field((string) $input['allowed_editor_emails']);
            // Unified list: use the same emails for submitters and editors.
            $current['allowed_submitter_emails'] = $current['allowed_editor_emails'];
        } elseif (isset($input['allowed_submitter_emails'])) {
            $current['allowed_editor_emails'] = sanitize_textarea_field((string) $input['allowed_submitter_emails']);
        }
        if (isset($input['otp_ttl_minutes'])) {
            $current['otp_ttl_minutes'] = max(3, min(30, (int) $input['otp_ttl_minutes']));
        }
        if (isset($input['brevo_api_key'])) {
            $current['brevo_api_key'] = sanitize_text_field((string) $input['brevo_api_key']);
        }
        if (isset($input['brevo_sender_email'])) {
            $current['brevo_sender_email'] = sanitize_email((string) $input['brevo_sender_email']);
        }
        if (isset($input['brevo_sender_name'])) {
            $current['brevo_sender_name'] = sanitize_text_field((string) $input['brevo_sender_name']);
        }

        if (isset($input['max_files_per_submission'])) {
            $current['max_files_per_submission'] = max(1, (int) $input['max_files_per_submission']);
        }

        if (isset($input['max_file_size_mb'])) {
            $current['max_file_size_mb'] = max(1, (int) $input['max_file_size_mb']);
        }

        if (isset($input['max_submissions_per_hour'])) {
            $current['max_submissions_per_hour'] = max(1, (int) $input['max_submissions_per_hour']);
        }

        return $current;
    }
    public static function register_admin_menus() {
        $pending_count = self::get_pending_change_requests_count();
        $base_menu_title = __('ขอแก้ไข', 'lc-public-place-photo-upload');
        $menu_title = $base_menu_title;
        if ($pending_count > 0) {
            $menu_title .= ' <span class="awaiting-mod count-' . (int) $pending_count . '"><span class="pending-count">' . esc_html(number_format_i18n($pending_count)) . '</span></span>';
        }

        add_menu_page(
            __('คำขอแก้ไขข้อมูล', 'lc-public-place-photo-upload'),
            $menu_title,
            'manage_options',
            'lc-location-edit-queue',
            [__CLASS__, 'render_location_edit_requests_page'],
            'dashicons-feedback',
            26
        );

        // Add explicit first submenu label (without badge) so only top menu shows pending count.
        add_submenu_page(
            'lc-location-edit-queue',
            __('คำขอแก้ไขข้อมูล', 'lc-public-place-photo-upload'),
            $base_menu_title,
            'manage_options',
            'lc-location-edit-queue',
            [__CLASS__, 'render_location_edit_requests_page']
        );

        add_submenu_page(
            'lc-location-edit-queue',
            __('ให้สิทธิ์ผู้แก้ไข', 'lc-public-place-photo-upload'),
            __('ให้สิทธิ์ผู้แก้ไข', 'lc-public-place-photo-upload'),
            'manage_options',
            'lc-staff-permissions',
            [__CLASS__, 'render_staff_permissions_page']
        );

        add_submenu_page(
            'lc-location-edit-queue',
            __('ตั้งค่าระบบ', 'lc-public-place-photo-upload'),
            __('ตั้งค่าระบบ', 'lc-public-place-photo-upload'),
            'manage_options',
            'lc-contribution-system-settings',
            [__CLASS__, 'render_system_settings_page']
        );

        add_submenu_page(
            'lc-location-edit-queue',
            __('Error Logs', 'lc-public-place-photo-upload'),
            __('Error Logs', 'lc-public-place-photo-upload'),
            'manage_options',
            'lc-error-logs',
            [__CLASS__, 'render_error_logs_page']
        );

        // Force top-level menu to show badge in case menu rendering strips prior value.
        if ($pending_count > 0) {
            global $menu;
            if (is_array($menu)) {
                foreach ($menu as $index => $item) {
                    if (!is_array($item) || !isset($item[2]) || (string) $item[2] !== 'lc-location-edit-queue') {
                        continue;
                    }
                    $menu[$index][0] = $menu_title;
                    break;
                }
            }
        }
        add_action('admin_head', [__CLASS__, 'render_pending_badge_fallback']);

    }

    public static function render_pending_badge_fallback() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $pending_count = self::get_pending_change_requests_count();
        if ($pending_count <= 0) {
            return;
        }
        $count_text = esc_js((string) number_format_i18n((int) $pending_count));
        $menu_label = esc_js((string) __('ขอแก้ไข', 'lc-public-place-photo-upload'));
        ?>
        <script>
        (function(){
          const inject = function(){
            const menuItem = document.querySelector('#toplevel_page_lc-location-edit-queue');
            if (!menuItem) return;
            const menuName = menuItem.querySelector('.wp-menu-name');
            if (!menuName) return;
            menuName.innerHTML = "";
            menuName.appendChild(document.createTextNode('<?php echo $menu_label; ?>'));
            let badge = menuName.querySelector('.lc-admin-pending-badge');
            if (!badge) {
              badge = document.createElement('span');
              badge.className = 'update-plugins count-<?php echo (int) $pending_count; ?> lc-admin-pending-badge';
              const inner = document.createElement('span');
              inner.className = 'plugin-count';
              badge.appendChild(inner);
              menuName.appendChild(document.createTextNode(' '));
              menuName.appendChild(badge);
            }
            const inner = badge.querySelector('.plugin-count');
            if (inner) inner.textContent = '<?php echo $count_text; ?>';
          };
          if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inject);
          } else {
            inject();
          }
          window.setTimeout(inject, 120);
        })();
        </script>
        <?php
    }

    private static function get_pending_change_requests_count() {
        $q = new WP_Query([
            'post_type' => self::change_request_post_types(),
            'post_status' => ['pending', 'publish', 'draft'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        if (!$q->have_posts()) {
            return 0;
        }
        $count = 0;
        foreach ((array) $q->posts as $rid) {
            $rid = (int) $rid;
            if ($rid <= 0) {
                continue;
            }
            $status = (string) get_post_meta($rid, '_lc_change_status', true);
            if ($status === '' || $status === 'pending') {
                $count++;
            }
        }
        return max(0, (int) $count);
    }

    private static function admin_notice_transient_key($user_id = 0) {
        $uid = (int) $user_id;
        if ($uid <= 0) {
            $uid = (int) get_current_user_id();
        }
        return 'lc_edit_queue_notice_' . (string) max(0, $uid);
    }

    private static function push_admin_queue_notice($message, $type = 'warning', $user_id = 0) {
        $message = trim((string) $message);
        if ($message === '') {
            return;
        }
        $type = in_array((string) $type, ['success', 'warning', 'error', 'info'], true) ? (string) $type : 'warning';
        set_transient(self::admin_notice_transient_key($user_id), [
            'message' => $message,
            'type' => $type,
        ], 5 * MINUTE_IN_SECONDS);
    }

    private static function pop_admin_queue_notice($user_id = 0) {
        $key = self::admin_notice_transient_key($user_id);
        $data = get_transient($key);
        if (is_array($data)) {
            delete_transient($key);
            return $data;
        }
        return null;
    }

    private static function settings_notice_transient_key($user_id = 0) {
        $uid = (int) $user_id;
        if ($uid <= 0) {
            $uid = (int) get_current_user_id();
        }
        return 'lc_system_settings_notice_' . (string) max(0, $uid);
    }

    private static function push_admin_settings_notice($message, $type = 'info', $user_id = 0) {
        $message = trim((string) $message);
        if ($message === '') {
            return;
        }
        $type = in_array((string) $type, ['success', 'warning', 'error', 'info'], true) ? (string) $type : 'info';
        set_transient(self::settings_notice_transient_key($user_id), [
            'message' => $message,
            'type' => $type,
        ], 5 * MINUTE_IN_SECONDS);
    }

    private static function pop_admin_settings_notice($user_id = 0) {
        $key = self::settings_notice_transient_key($user_id);
        $data = get_transient($key);
        if (is_array($data)) {
            delete_transient($key);
            return $data;
        }
        return null;
    }

    public static function render_location_edit_requests_page() {
        self::render_location_edit_queue_page('all', 'คำขอแก้ไขข้อมูล (สถานที่/คอร์ส)');
    }

    public static function render_staff_permissions_page() {
        self::render_settings_page(true);
    }

    public static function render_system_settings_page() {
        self::render_settings_page(false);
    }

    public static function handle_cleanup_text_artifacts() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'lc-public-place-photo-upload'));
        }
        check_admin_referer(self::NONCE_ACTION_TEXT_CLEANUP);

        $stats = self::run_text_artifact_cleanup_once();
        if (is_wp_error($stats)) {
            self::push_admin_settings_notice((string) $stats->get_error_message(), 'error');
            wp_safe_redirect(admin_url('admin.php?page=lc-contribution-system-settings'));
            exit;
        }

        $message = sprintf(
            'Text cleanup เสร็จแล้ว: location %1$d รายการ, session %2$d รายการ, course %3$d รายการ, change request %4$d รายการ, ฟิลด์ที่แก้ไขทั้งหมด %5$d ฟิลด์ และล้าง cache location %6$d รายการ',
            (int) ($stats['location_posts'] ?? 0),
            (int) ($stats['session_posts'] ?? 0),
            (int) ($stats['course_posts'] ?? 0),
            (int) ($stats['request_posts'] ?? 0),
            (int) ($stats['changed_fields'] ?? 0),
            (int) ($stats['cache_locations'] ?? 0)
        );
        self::push_admin_settings_notice($message, 'success');
        wp_safe_redirect(admin_url('admin.php?page=lc-contribution-system-settings'));
        exit;
    }

    private static function parse_related_post_id($value) {
        if (is_numeric($value)) {
            return (int) $value;
        }
        if (is_object($value) && isset($value->ID)) {
            return (int) $value->ID;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                $id = self::parse_related_post_id($item);
                if ($id > 0) {
                    return $id;
                }
            }
        }
        return 0;
    }

    private static function cleanup_payload_text_value($value, &$changed_fields) {
        if (!is_scalar($value)) {
            return $value;
        }
        $raw = (string) $value;
        $clean = self::normalize_multiline_text($raw);
        if ($clean !== $raw) {
            $changed_fields++;
            return $clean;
        }
        return $value;
    }

    private static function run_text_artifact_cleanup_once() {
        @set_time_limit(120); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged

        $stats = [
            'location_posts' => 0,
            'session_posts' => 0,
            'course_posts' => 0,
            'request_posts' => 0,
            'changed_fields' => 0,
            'cache_locations' => 0,
        ];
        $affected_locations = [];
        $all_post_statuses = ['publish', 'future', 'draft', 'pending', 'private'];

        $location_ids = get_posts([
            'post_type' => 'location',
            'post_status' => $all_post_statuses,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        foreach ((array) $location_ids as $location_id) {
            $location_id = (int) $location_id;
            if ($location_id <= 0) {
                continue;
            }
            $post_changed = false;
            foreach (['opening_hours', 'description', 'address'] as $meta_key) {
                $raw = get_post_meta($location_id, $meta_key, true);
                if (!is_scalar($raw)) {
                    continue;
                }
                $raw_text = (string) $raw;
                $clean_text = self::normalize_multiline_text($raw_text);
                if ($clean_text === $raw_text) {
                    continue;
                }
                update_post_meta($location_id, $meta_key, $clean_text);
                $stats['changed_fields']++;
                $post_changed = true;
            }
            if ($post_changed) {
                $stats['location_posts']++;
                $affected_locations[$location_id] = true;
            }
        }

        $session_ids = get_posts([
            'post_type' => 'session',
            'post_status' => $all_post_statuses,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        foreach ((array) $session_ids as $session_id) {
            $session_id = (int) $session_id;
            if ($session_id <= 0) {
                continue;
            }
            $post_changed = false;
            foreach (['time_period', 'session_details'] as $meta_key) {
                $raw = get_post_meta($session_id, $meta_key, true);
                if (!is_scalar($raw)) {
                    continue;
                }
                $raw_text = (string) $raw;
                $clean_text = self::normalize_multiline_text($raw_text);
                if ($clean_text === $raw_text) {
                    continue;
                }
                update_post_meta($session_id, $meta_key, $clean_text);
                $stats['changed_fields']++;
                $post_changed = true;
            }
            if ($post_changed) {
                $stats['session_posts']++;
                $location_id = self::parse_related_post_id(get_post_meta($session_id, 'location', true));
                if ($location_id <= 0 && function_exists('get_field')) {
                    $location_id = self::parse_related_post_id(get_field('location', $session_id, false));
                }
                if ($location_id > 0) {
                    $affected_locations[$location_id] = true;
                }
            }
        }

        $course_ids = get_posts([
            'post_type' => 'course',
            'post_status' => $all_post_statuses,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        foreach ((array) $course_ids as $course_id) {
            $course_id = (int) $course_id;
            if ($course_id <= 0) {
                continue;
            }
            $raw = get_post_meta($course_id, 'course_description', true);
            if (!is_scalar($raw)) {
                continue;
            }
            $raw_text = (string) $raw;
            $clean_text = self::normalize_multiline_text($raw_text);
            if ($clean_text === $raw_text) {
                continue;
            }
            update_post_meta($course_id, 'course_description', $clean_text);
            if (function_exists('update_field')) {
                update_field('course_description', $clean_text, $course_id);
            }
            $stats['changed_fields']++;
            $stats['course_posts']++;
        }

        $request_ids = get_posts([
            'post_type' => self::change_request_post_types(),
            'post_status' => ['pending', 'publish', 'draft'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        foreach ((array) $request_ids as $request_id) {
            $request_id = (int) $request_id;
            if ($request_id <= 0) {
                continue;
            }
            $payload_json = (string) get_post_meta($request_id, '_lc_change_payload', true);
            if ($payload_json === '') {
                continue;
            }
            $payload = json_decode($payload_json, true);
            if (!is_array($payload)) {
                continue;
            }
            $changed_before = (int) $stats['changed_fields'];

            if (isset($payload['location']) && is_array($payload['location'])) {
                foreach (['address', 'opening_hours', 'description'] as $field_key) {
                    if (array_key_exists($field_key, $payload['location'])) {
                        $payload['location'][$field_key] = self::cleanup_payload_text_value($payload['location'][$field_key], $stats['changed_fields']);
                    }
                }
            }
            if (isset($payload['course']) && is_array($payload['course']) && array_key_exists('course_description', $payload['course'])) {
                $payload['course']['course_description'] = self::cleanup_payload_text_value($payload['course']['course_description'], $stats['changed_fields']);
            }
            if (array_key_exists('request_note', $payload)) {
                $payload['request_note'] = self::cleanup_payload_text_value($payload['request_note'], $stats['changed_fields']);
            }
            if (isset($payload['sessions']) && is_array($payload['sessions'])) {
                foreach ($payload['sessions'] as $idx => $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    foreach (['time_period', 'session_details'] as $field_key) {
                        if (array_key_exists($field_key, $row)) {
                            $payload['sessions'][$idx][$field_key] = self::cleanup_payload_text_value($row[$field_key], $stats['changed_fields']);
                        }
                    }
                }
            }
            if (isset($payload['new_sessions']) && is_array($payload['new_sessions'])) {
                foreach ($payload['new_sessions'] as $idx => $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    foreach (['time_period', 'session_details'] as $field_key) {
                        if (array_key_exists($field_key, $row)) {
                            $payload['new_sessions'][$idx][$field_key] = self::cleanup_payload_text_value($row[$field_key], $stats['changed_fields']);
                        }
                    }
                }
            }
            if (isset($payload['snapshot']) && is_array($payload['snapshot'])) {
                if (isset($payload['snapshot']['location']) && is_array($payload['snapshot']['location'])) {
                    foreach (['address', 'opening_hours', 'description'] as $field_key) {
                        if (array_key_exists($field_key, $payload['snapshot']['location'])) {
                            $payload['snapshot']['location'][$field_key] = self::cleanup_payload_text_value($payload['snapshot']['location'][$field_key], $stats['changed_fields']);
                        }
                    }
                }
                if (isset($payload['snapshot']['course']) && is_array($payload['snapshot']['course']) && array_key_exists('course_description', $payload['snapshot']['course'])) {
                    $payload['snapshot']['course']['course_description'] = self::cleanup_payload_text_value($payload['snapshot']['course']['course_description'], $stats['changed_fields']);
                }
                if (isset($payload['snapshot']['sessions']) && is_array($payload['snapshot']['sessions'])) {
                    foreach ($payload['snapshot']['sessions'] as $sid => $snapshot_row) {
                        if (!is_array($snapshot_row)) {
                            continue;
                        }
                        foreach (['time_period', 'session_details'] as $field_key) {
                            if (array_key_exists($field_key, $snapshot_row)) {
                                $payload['snapshot']['sessions'][$sid][$field_key] = self::cleanup_payload_text_value($snapshot_row[$field_key], $stats['changed_fields']);
                            }
                        }
                    }
                }
            }

            if ((int) $stats['changed_fields'] > $changed_before) {
                update_post_meta($request_id, '_lc_change_payload', wp_json_encode($payload, JSON_UNESCAPED_UNICODE));
                $stats['request_posts']++;
            }
        }

        if (function_exists('blm_clear_light_cache')) {
            blm_clear_light_cache();
        }
        foreach (array_keys($affected_locations) as $location_id) {
            $location_id = (int) $location_id;
            if ($location_id <= 0) {
                continue;
            }
            if (function_exists('blm_clear_full_cache_for_location_id')) {
                blm_clear_full_cache_for_location_id($location_id);
            } elseif (function_exists('blm_clear_full_cache_for_post')) {
                blm_clear_full_cache_for_post($location_id);
            }
        }
        if (!empty($affected_locations) && function_exists('blm_schedule_rebuild')) {
            $first_location_id = (int) array_key_first($affected_locations);
            if ($first_location_id > 0) {
                blm_schedule_rebuild($first_location_id);
            } else {
                blm_schedule_rebuild(0);
            }
        } elseif (function_exists('blm_schedule_rebuild')) {
            blm_schedule_rebuild(0);
        }
        $stats['cache_locations'] = count($affected_locations);
        return $stats;
    }

    public static function render_error_logs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'lc-public-place-photo-upload'));
        }

        self::maybe_create_error_log_table();
        global $wpdb;
        $table = self::error_log_table_name();

        $stage = isset($_GET['stage']) ? sanitize_key((string) wp_unslash($_GET['stage'])) : '';
        $code = isset($_GET['code']) ? sanitize_key((string) wp_unslash($_GET['code'])) : '';
        $http_status = isset($_GET['http_status']) ? (int) $_GET['http_status'] : 0;
        $severity = isset($_GET['severity']) ? sanitize_key((string) wp_unslash($_GET['severity'])) : '';
        if (!in_array($severity, ['', 'info', 'warn', 'error', 'critical'], true)) {
            $severity = '';
        }
        $resolved = isset($_GET['resolved']) ? sanitize_key((string) wp_unslash($_GET['resolved'])) : '';
        if (!in_array($resolved, ['', 'open', 'resolved', 'ignored'], true)) {
            $resolved = '';
        }
        $search = isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '';
        $paged = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $per_page = 50;
        $offset = ($paged - 1) * $per_page;

        $where = ['1=1'];
        $params = [];
        if ($stage !== '') {
            $where[] = 'stage = %s';
            $params[] = $stage;
        }
        if ($code !== '') {
            $where[] = 'code = %s';
            $params[] = $code;
        }
        if ($http_status > 0) {
            $where[] = 'http_status = %d';
            $params[] = $http_status;
        }
        if ($severity !== '') {
            $where[] = 'severity = %s';
            $params[] = $severity;
        }
        if ($resolved !== '') {
            $where[] = 'resolved_status = %s';
            $params[] = $resolved;
        }
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(error_id LIKE %s OR code LIKE %s OR stage LIKE %s OR action_name LIKE %s OR message_user LIKE %s OR user_email_masked LIKE %s)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }

        $where_sql = implode(' AND ', $where);
        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total_items = (int) ($params ? $wpdb->get_var($wpdb->prepare($count_sql, $params)) : $wpdb->get_var($count_sql));

        $rows_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
        $rows_params = array_merge($params, [$per_page, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($rows_sql, $rows_params), ARRAY_A);
        $rows = is_array($rows) ? $rows : [];

        $summary_sql = "SELECT
            COUNT(*) AS total_count,
            SUM(CASE WHEN http_status = 403 THEN 1 ELSE 0 END) AS count_403,
            SUM(CASE WHEN http_status = 429 THEN 1 ELSE 0 END) AS count_429,
            SUM(CASE WHEN http_status >= 500 THEN 1 ELSE 0 END) AS count_500
            FROM {$table}
            WHERE created_at_utc >= %s";
        $summary = $wpdb->get_row($wpdb->prepare($summary_sql, gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS)), ARRAY_A);
        $summary = is_array($summary) ? $summary : [];

        $base_url = admin_url('admin.php?page=lc-error-logs');
        $page_count = max(1, (int) ceil($total_items / $per_page));
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Error Logs', 'lc-public-place-photo-upload'); ?></h1>
            <p><?php echo esc_html__('รวมข้อผิดพลาดจาก AJAX flow (OTP / course / location / status) สำหรับทีม dev และแอดมิน', 'lc-public-place-photo-upload'); ?></p>

            <div style="display:flex;gap:12px;flex-wrap:wrap;margin:12px 0 16px;">
                <div style="background:#fff;border:1px solid #dbe4ee;border-radius:10px;padding:10px 12px;min-width:160px;">
                    <div style="font-size:12px;color:#64748b;">24 ชั่วโมงล่าสุด</div>
                    <div style="font-size:24px;font-weight:800;color:#0f172a;"><?php echo esc_html(number_format_i18n((int) ($summary['total_count'] ?? 0))); ?></div>
                </div>
                <div style="background:#fff;border:1px solid #dbe4ee;border-radius:10px;padding:10px 12px;min-width:120px;">
                    <div style="font-size:12px;color:#64748b;">HTTP 403</div>
                    <div style="font-size:22px;font-weight:800;color:#b91c1c;"><?php echo esc_html(number_format_i18n((int) ($summary['count_403'] ?? 0))); ?></div>
                </div>
                <div style="background:#fff;border:1px solid #dbe4ee;border-radius:10px;padding:10px 12px;min-width:120px;">
                    <div style="font-size:12px;color:#64748b;">HTTP 429</div>
                    <div style="font-size:22px;font-weight:800;color:#9a3412;"><?php echo esc_html(number_format_i18n((int) ($summary['count_429'] ?? 0))); ?></div>
                </div>
                <div style="background:#fff;border:1px solid #dbe4ee;border-radius:10px;padding:10px 12px;min-width:120px;">
                    <div style="font-size:12px;color:#64748b;">HTTP 5xx</div>
                    <div style="font-size:22px;font-weight:800;color:#7c2d12;"><?php echo esc_html(number_format_i18n((int) ($summary['count_500'] ?? 0))); ?></div>
                </div>
            </div>

            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="background:#fff;border:1px solid #dbe4ee;border-radius:10px;padding:12px;margin-bottom:14px;">
                <input type="hidden" name="page" value="lc-error-logs" />
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;">
                    <label>
                        <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;">Search</div>
                        <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="error_id / code / stage / message" class="regular-text" />
                    </label>
                    <label>
                        <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;">Stage</div>
                        <input type="text" name="stage" value="<?php echo esc_attr($stage); ?>" placeholder="otp_verify" />
                    </label>
                    <label>
                        <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;">Code</div>
                        <input type="text" name="code" value="<?php echo esc_attr($code); ?>" placeholder="session_expired" />
                    </label>
                    <label>
                        <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;">HTTP</div>
                        <input type="number" min="0" max="599" name="http_status" value="<?php echo $http_status > 0 ? esc_attr((string) $http_status) : ''; ?>" style="width:90px;" />
                    </label>
                    <label>
                        <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;">Severity</div>
                        <select name="severity">
                            <option value="" <?php selected($severity, ''); ?>>ทั้งหมด</option>
                            <option value="info" <?php selected($severity, 'info'); ?>>info</option>
                            <option value="warn" <?php selected($severity, 'warn'); ?>>warn</option>
                            <option value="error" <?php selected($severity, 'error'); ?>>error</option>
                            <option value="critical" <?php selected($severity, 'critical'); ?>>critical</option>
                        </select>
                    </label>
                    <label>
                        <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;">Status</div>
                        <select name="resolved">
                            <option value="" <?php selected($resolved, ''); ?>>ทั้งหมด</option>
                            <option value="open" <?php selected($resolved, 'open'); ?>>open</option>
                            <option value="resolved" <?php selected($resolved, 'resolved'); ?>>resolved</option>
                            <option value="ignored" <?php selected($resolved, 'ignored'); ?>>ignored</option>
                        </select>
                    </label>
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Filter', 'lc-public-place-photo-upload'); ?></button>
                    <a href="<?php echo esc_url($base_url); ?>" class="button"><?php echo esc_html__('Reset', 'lc-public-place-photo-upload'); ?></a>
                </div>
            </form>

            <div style="background:#fff;border:1px solid #dbe4ee;border-radius:10px;overflow:auto;">
                <table class="widefat striped" style="border:0;table-layout:fixed;min-width:1760px;">
                    <thead>
                        <tr>
                            <th style="width:68px;">ID</th>
                            <th style="width:190px;">Time (UTC)</th>
                            <th style="width:110px;">Severity</th>
                            <th style="width:82px;">HTTP</th>
                            <th style="width:190px;">Stage</th>
                            <th style="width:240px;">Code</th>
                            <th style="width:260px;">Action</th>
                            <th style="width:340px;">Message</th>
                            <th style="width:260px;">Requester</th>
                            <th style="width:130px;">Target</th>
                            <th style="width:190px;">Debug</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$rows) : ?>
                        <tr><td colspan="11"><?php echo esc_html__('No error logs found.', 'lc-public-place-photo-upload'); ?></td></tr>
                    <?php else : foreach ($rows as $row) :
                        $context = json_decode((string) ($row['context_json'] ?? ''), true);
                        $context = is_array($context) ? $context : [];
                        $target_bits = [];
                        foreach (['request_id' => 'REQ', 'location_id' => 'LOC', 'course_id' => 'COURSE'] as $k => $label) {
                            if (!empty($row[$k])) {
                                $target_bits[] = $label . ':' . (int) $row[$k];
                            }
                        }
                        $target_text = $target_bits ? implode(' ', $target_bits) : '-';
                        $sev = sanitize_key((string) ($row['severity'] ?? 'error'));
                        $sev_label = $sev !== '' ? $sev : 'error';
                        $sev_style = 'background:#fee2e2;color:#991b1b;border:1px solid #fecaca;';
                        if ($sev === 'info') {
                            $sev_style = 'background:#e0f2fe;color:#075985;border:1px solid #bae6fd;';
                        } elseif ($sev === 'warn') {
                            $sev_style = 'background:#ffedd5;color:#9a3412;border:1px solid #fed7aa;';
                        } elseif ($sev === 'critical') {
                            $sev_style = 'background:#1f2937;color:#fff;border:1px solid #111827;';
                        }
                        $requester_bits = [];
                        $requester_bits[] = 'WP user: ' . (!empty($row['wp_user_id']) ? ('#' . (int) $row['wp_user_id']) : '-');
                        $requester_bits[] = 'logged_in: ' . (!empty($row['is_wp_logged_in']) ? '1' : '0');
                        if (!empty($row['user_email_masked'])) {
                            $requester_bits[] = 'email: ' . (string) $row['user_email_masked'];
                        }
                        if (!empty($row['user_email_hash'])) {
                            $requester_bits[] = 'email_hash: ' . substr((string) $row['user_email_hash'], 0, 12) . '...';
                        }
                        $copy_payload = [
                            'id' => (int) ($row['id'] ?? 0),
                            'error_id' => (string) ($row['error_id'] ?? ''),
                            'time_utc' => (string) ($row['created_at_utc'] ?? ''),
                            'severity' => (string) ($row['severity'] ?? ''),
                            'http_status' => (int) ($row['http_status'] ?? 0),
                            'stage' => (string) ($row['stage'] ?? ''),
                            'code' => (string) ($row['code'] ?? ''),
                            'action' => (string) ($row['action_name'] ?? ''),
                            'message' => (string) ($row['message_user'] ?? ''),
                            'requester' => [
                                'wp_user_id' => !empty($row['wp_user_id']) ? (int) $row['wp_user_id'] : null,
                                'is_wp_logged_in' => !empty($row['is_wp_logged_in']) ? 1 : 0,
                                'email_masked' => (string) ($row['user_email_masked'] ?? ''),
                                'email_hash' => (string) ($row['user_email_hash'] ?? ''),
                            ],
                            'target' => [
                                'request_id' => !empty($row['request_id']) ? (int) $row['request_id'] : null,
                                'location_id' => !empty($row['location_id']) ? (int) $row['location_id'] : null,
                                'course_id' => !empty($row['course_id']) ? (int) $row['course_id'] : null,
                            ],
                            'request' => [
                                'url' => (string) ($row['page_url'] ?? ''),
                                'referer' => (string) ($row['referer_url'] ?? ''),
                                'user_agent' => (string) ($row['user_agent'] ?? ''),
                            ],
                            'context' => $context,
                        ];
                        $copy_json = wp_json_encode($copy_payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        $copy_b64 = is_string($copy_json) ? base64_encode($copy_json) : '';
                        ?>
                        <tr>
                            <td style="vertical-align:top;"><?php echo esc_html((string) (int) ($row['id'] ?? 0)); ?></td>
                            <td style="vertical-align:top;">
                                <div><?php echo esc_html((string) ($row['created_at_utc'] ?? '')); ?></div>
                                <?php if (!empty($row['error_id'])) : ?>
                                    <div style="font-size:11px;color:#64748b;"><?php echo esc_html((string) $row['error_id']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="vertical-align:top;"><span style="display:inline-flex;align-items:center;height:22px;padding:0 8px;border-radius:999px;font-size:11px;font-weight:700;<?php echo esc_attr($sev_style); ?>"><?php echo esc_html($sev_label); ?></span></td>
                            <td style="vertical-align:top;"><code><?php echo esc_html((string) (int) ($row['http_status'] ?? 0)); ?></code></td>
                            <td style="vertical-align:top;"><code style="display:inline-block;max-width:100%;white-space:normal;word-break:break-word;line-height:1.35;"><?php echo esc_html((string) ($row['stage'] ?? '')); ?></code></td>
                            <td style="vertical-align:top;"><code style="display:inline-block;max-width:100%;white-space:normal;word-break:break-word;line-height:1.35;"><?php echo esc_html((string) ($row['code'] ?? '')); ?></code></td>
                            <td style="vertical-align:top;"><code style="display:inline-block;max-width:100%;white-space:normal;word-break:break-word;line-height:1.35;"><?php echo esc_html((string) ($row['action_name'] ?? '')); ?></code></td>
                            <td style="vertical-align:top;">
                                <div style="line-height:1.45;"><?php echo esc_html((string) ($row['message_user'] ?? '')); ?></div>
                            </td>
                            <td style="vertical-align:top;">
                                <div style="font-size:12px;line-height:1.45;color:#475569;white-space:pre-wrap;word-break:break-word;"><?php echo esc_html(implode("\n", $requester_bits)); ?></div>
                            </td>
                            <td style="vertical-align:top;white-space:normal;word-break:break-word;"><?php echo esc_html($target_text); ?></td>
                            <td style="vertical-align:top;">
                                <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-start;">
                                    <button type="button" class="button button-small lc-error-copy-row" data-copy-b64="<?php echo esc_attr($copy_b64); ?>"><?php echo esc_html__('Copy', 'lc-public-place-photo-upload'); ?></button>
                                    <span class="lc-error-copy-status" style="font-size:11px;color:#64748b;display:none;"><?php echo esc_html__('Copied', 'lc-public-place-photo-upload'); ?></span>
                                </div>
                                <details style="margin-top:6px;">
                                    <summary style="cursor:pointer;"><?php echo esc_html__('View', 'lc-public-place-photo-upload'); ?></summary>
                                    <div style="margin-top:6px;">
                                        <?php if (!empty($row['page_url'])) : ?><div><strong>URL:</strong> <code><?php echo esc_html((string) $row['page_url']); ?></code></div><?php endif; ?>
                                        <?php if (!empty($row['referer_url'])) : ?><div><strong>Referer:</strong> <code><?php echo esc_html((string) $row['referer_url']); ?></code></div><?php endif; ?>
                                        <?php if (!empty($row['user_agent'])) : ?><div><strong>UA:</strong> <code><?php echo esc_html((string) $row['user_agent']); ?></code></div><?php endif; ?>
                                        <?php if ($context) : ?>
                                            <pre style="white-space:pre-wrap;word-break:break-word;background:#0f172a;color:#e2e8f0;border-radius:8px;padding:8px;max-width:520px;overflow:auto;"><?php echo esc_html(wp_json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($page_count > 1) :
                $page_links = paginate_links([
                    'base' => add_query_arg('paged', '%#%', $base_url),
                    'format' => '',
                    'current' => $paged,
                    'total' => $page_count,
                    'type' => 'array',
                    'add_args' => array_filter([
                        's' => $search,
                        'stage' => $stage,
                        'code' => $code,
                        'http_status' => $http_status > 0 ? $http_status : null,
                        'severity' => $severity,
                        'resolved' => $resolved,
                    ], function($v) { return $v !== null && $v !== ''; }),
                ]);
                if (is_array($page_links) && $page_links) : ?>
                <div class="tablenav"><div class="tablenav-pages" style="margin:12px 0;"><?php echo wp_kses_post(implode(' ', $page_links)); ?></div></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <script>
        (function(){
          const decodeB64 = function(v){
            try {
              const bin = window.atob(String(v || ""));
              if (window.TextDecoder) {
                const bytes = new Uint8Array(bin.length);
                for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
                return new TextDecoder('utf-8').decode(bytes);
              }
              return decodeURIComponent(Array.prototype.map.call(bin, function(ch){
                return '%' + ('00' + ch.charCodeAt(0).toString(16)).slice(-2);
              }).join(''));
            } catch (e) {
              return "";
            }
          };
          document.addEventListener('click', async function(event){
            const btn = event.target instanceof Element ? event.target.closest('.lc-error-copy-row') : null;
            if (!(btn instanceof HTMLButtonElement)) return;
            const raw = decodeB64(btn.getAttribute('data-copy-b64'));
            if (!raw) return;
            let ok = false;
            try {
              if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(raw);
                ok = true;
              }
            } catch (e) {}
            if (!ok) {
              const ta = document.createElement('textarea');
              ta.value = raw;
              ta.setAttribute('readonly', 'readonly');
              ta.style.position = 'fixed';
              ta.style.opacity = '0';
              document.body.appendChild(ta);
              ta.select();
              try { ok = document.execCommand('copy'); } catch (e) {}
              ta.remove();
            }
            const status = btn.parentElement ? btn.parentElement.querySelector('.lc-error-copy-status') : null;
            if (status instanceof HTMLElement) {
              status.style.display = ok ? 'inline' : 'inline';
              status.textContent = ok ? 'Copied' : 'Copy failed';
              window.setTimeout(function(){ status.style.display = 'none'; }, 1500);
            }
          });
        })();
        </script>
        <?php
    }

    public static function render_settings_page($permissions_only = false) {
        // Staff permissions & system settings are administrator-only pages.
        // Use a capability check here because "administrator" is a role, not a capability.
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'lc-public-place-photo-upload'));
        }

        $settings = self::get_settings();
        $flash_notice = self::pop_admin_settings_notice();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__($permissions_only ? 'ให้สิทธิ์ผู้แก้ไข' : 'ตั้งค่าระบบ', 'lc-public-place-photo-upload'); ?></h1>
            <?php if (is_array($flash_notice) && !empty($flash_notice['message'])) : ?>
                <?php $notice_type = in_array((string) ($flash_notice['type'] ?? 'info'), ['success', 'warning', 'error', 'info'], true) ? (string) $flash_notice['type'] : 'info'; ?>
                <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible"><p><?php echo esc_html((string) $flash_notice['message']); ?></p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('lc_public_place_upload_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <?php if (!$permissions_only) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('เปิดระบบให้ Staff แก้ไขได้', 'lc-public-place-photo-upload'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[location_edit_enabled]" value="1" <?php checked(($settings['location_edit_enabled'] ?? '0'), '1'); ?> />
                                <?php echo esc_html__('อนุญาตให้ผู้ที่มีสิทธิ์ (Staff) ขอ OTP และส่งคำขอแก้ไขข้อมูลสถานที่ได้', 'lc-public-place-photo-upload'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('จัดการรายชื่ออีเมลได้ที่เมนู "ให้สิทธิ์ผู้แก้ไข"', 'lc-public-place-photo-upload'); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($permissions_only) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Allowed Staff Emails', 'lc-public-place-photo-upload'); ?></th>
                        <td>
                            <div class="lc-email-tags-widget" data-source-id="lc_editor_emails_source">
                                <div class="lc-email-tags-list"></div>
                                <input type="text" class="regular-text lc-email-tags-input" placeholder="พิมพ์อีเมล แล้วกด comma หรือ Enter">
                                <textarea id="lc_editor_emails_source" name="<?php echo esc_attr(self::OPT_KEY); ?>[allowed_editor_emails]" style="display:none;"><?php echo esc_textarea((string) (($settings['allowed_editor_emails'] ?? '') !== '' ? $settings['allowed_editor_emails'] : ($settings['allowed_submitter_emails'] ?? ''))); ?></textarea>
                            </div>
                            <p class="description"><?php echo esc_html__('ลิสต์เดียวสำหรับทั้งการส่งรูป และการขอ OTP เพื่อแก้ไขข้อมูลสถานที่', 'lc-public-place-photo-upload'); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!$permissions_only) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('OTP Expiry (Minutes)', 'lc-public-place-photo-upload'); ?></th>
                        <td>
                            <input type="number" min="3" max="30" name="<?php echo esc_attr(self::OPT_KEY); ?>[otp_ttl_minutes]" value="<?php echo esc_attr((string) ($settings['otp_ttl_minutes'] ?? 10)); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Max Files Per Submission', 'lc-public-place-photo-upload'); ?></th>
                        <td>
                            <input type="number" min="1" max="20" name="<?php echo esc_attr(self::OPT_KEY); ?>[max_files_per_submission]" value="<?php echo esc_attr((string) $settings['max_files_per_submission']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Max File Size (MB)', 'lc-public-place-photo-upload'); ?></th>
                        <td>
                            <input type="number" min="1" max="50" name="<?php echo esc_attr(self::OPT_KEY); ?>[max_file_size_mb]" value="<?php echo esc_attr((string) $settings['max_file_size_mb']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Max Submissions Per IP / Hour', 'lc-public-place-photo-upload'); ?></th>
                        <td>
                            <input type="number" min="1" max="100" name="<?php echo esc_attr(self::OPT_KEY); ?>[max_submissions_per_hour]" value="<?php echo esc_attr((string) $settings['max_submissions_per_hour']); ?>" />
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>

                <?php submit_button(); ?>
            </form>
            <?php if (!$permissions_only) : ?>
            <hr style="margin:24px 0;">
            <h2>One-time Text Cleanup</h2>
            <p style="max-width:860px;">
                เครื่องมือนี้จะทำความสะอาดข้อมูลที่เคยเพี้ยนจาก escape sequence (เช่น <code>rnrn</code>, <code>rn</code>, <code>nn</code>, <code>t</code>, <code>n</code>)
                ในฟิลด์ข้อความหลัก และล้าง cache หลังทำงานเสร็จ เพื่อให้หน้าบ้านเห็นผลทันที
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return window.confirm('ยืนยันรัน cleanup แบบ one-time? ระบบจะอัปเดตข้อมูลข้อความในฐานข้อมูลทันที');">
                <input type="hidden" name="action" value="lc_cleanup_text_artifacts" />
                <?php wp_nonce_field(self::NONCE_ACTION_TEXT_CLEANUP); ?>
                <button type="submit" class="button button-secondary">Run One-time Cleanup</button>
            </form>
            <?php endif; ?>
            <style>
            .lc-email-tags-widget{width:100%;max-width:820px;min-width:0}
            .lc-email-tags-list{display:flex;flex-wrap:wrap;gap:8px;width:100%;max-width:100%;box-sizing:border-box;padding:8px;border:1px solid #d1d5db;border-radius:8px;background:#fff;min-height:44px;margin-bottom:8px}
            .lc-email-tag{display:inline-flex;align-items:center;gap:6px;max-width:100%;background:#ecfeff;border:1px solid #99f6e4;color:#0f766e;border-radius:999px;padding:4px 8px;font-weight:600}
            .lc-email-tag span{overflow-wrap:anywhere;word-break:break-word}
            .lc-email-tag button{border:none;background:transparent;color:#0f766e;cursor:pointer;font-size:14px;line-height:1;padding:0}
            .lc-email-tag button:hover{color:#7f1d1d}
            .lc-email-tags-input{width:100%;max-width:520px;box-sizing:border-box}
            </style>
            <script>
            (function(){
              const widgets = Array.from(document.querySelectorAll('.lc-email-tags-widget'));
              const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/i;
              const splitEmails = (text) => {
                let normalized = String(text || '');
                // Accept literal "\n" text and normalize it to actual newline.
                normalized = normalized.replace(/\\n/g, '\n');
                // Repair legacy bad separator case: "email1nemail2" caused by previous serialization bug.
                normalized = normalized.replace(/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})n(?=[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/ig, '$1\n');
                return normalized.split(/[\n,;\s]+/).map((s) => s.trim().toLowerCase()).filter(Boolean);
              };
              widgets.forEach((widget) => {
                const sourceId = widget.getAttribute('data-source-id') || '';
                const source = sourceId ? document.getElementById(sourceId) : null;
                const list = widget.querySelector('.lc-email-tags-list');
                const input = widget.querySelector('.lc-email-tags-input');
                if(!source || !list || !input){ return; }
                let emails = splitEmails(source.value).filter((e) => emailRe.test(e));
                emails = Array.from(new Set(emails));
                const sync = () => { source.value = emails.join("\n"); };
                const render = () => {
                  list.innerHTML = '';
                  emails.forEach((email) => {
                    const tag = document.createElement('span');
                    tag.className = 'lc-email-tag';
                    tag.innerHTML = '<span></span><button type="button" aria-label="Remove">×</button>';
                    tag.querySelector('span').textContent = email;
                    tag.querySelector('button').addEventListener('click', () => {
                      emails = emails.filter((x) => x !== email);
                      sync(); render();
                    });
                    list.appendChild(tag);
                  });
                };
                const addFromText = (text) => {
                  const next = splitEmails(text).filter((e) => emailRe.test(e));
                  if(!next.length){ return; }
                  next.forEach((e) => { if(!emails.includes(e)) emails.push(e); });
                  sync(); render();
                };
                input.addEventListener('keydown', (ev) => {
                  if(ev.key === 'Enter' || ev.key === ',' || ev.key === 'Tab'){
                    const v = input.value.trim();
                    if(v !== ''){
                      ev.preventDefault();
                      addFromText(v);
                      input.value = '';
                    }
                  }
                });
                input.addEventListener('blur', () => {
                  const v = input.value.trim();
                  if(v !== ''){
                    addFromText(v);
                    input.value = '';
                  }
                });
                input.addEventListener('paste', (ev) => {
                  const txt = (ev.clipboardData && ev.clipboardData.getData('text')) || '';
                  if(/[\\n,;\\s]/.test(txt)){
                    ev.preventDefault();
                    addFromText(txt);
                    input.value = '';
                  }
                });
                render();
                sync();
              });
            })();
            </script>
        </div>
        <?php
    }

    public static function render_upload_form($atts = []) {
        $settings = self::get_settings();
        $status = isset($_GET['lc_upload_status']) ? sanitize_key((string) $_GET['lc_upload_status']) : '';
        $message = self::status_message($status);

        ob_start();

        if ($message !== '') {
            echo '<div class="lc-upload-notice" style="padding:12px;border:1px solid #ddd;margin-bottom:16px;background:#fafafa;">' . esc_html($message) . '</div>';
        }

        if ($settings['upload_enabled'] !== '1') {
            echo '<p>' . esc_html__('Photo submissions are currently closed.', 'lc-public-place-photo-upload') . '</p>';
            return ob_get_clean();
        }

        $locations = get_posts([
            'post_type' => 'location',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        $selected_location = isset($_GET['place_id']) ? (int) $_GET['place_id'] : 0;

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data" class="lc-place-photo-upload-form">';
        echo '<input type="hidden" name="action" value="lc_submit_place_photos" />';
        wp_nonce_field(self::NONCE_ACTION_SUBMIT, '_lc_place_photo_nonce');

        echo '<p><label for="lc_uploader_name">' . esc_html__('Your Name', 'lc-public-place-photo-upload') . ' *</label><br />';
        echo '<input type="text" id="lc_uploader_name" name="uploader_name" required maxlength="120" style="width:100%;max-width:420px;" /></p>';
        echo '<p><label for="lc_uploader_email">' . esc_html__('Your Email', 'lc-public-place-photo-upload') . ' *</label><br />';
        echo '<input type="email" id="lc_uploader_email" name="uploader_email" required maxlength="190" style="width:100%;max-width:420px;" /></p>';

        echo '<p><label for="lc_location_id">' . esc_html__('Place', 'lc-public-place-photo-upload') . ' *</label><br />';
        echo '<select id="lc_location_id" name="location_id" required style="width:100%;max-width:420px;">';
        echo '<option value="">' . esc_html__('Select a place', 'lc-public-place-photo-upload') . '</option>';
        foreach ($locations as $location_id) {
            $location_id = (int) $location_id;
            $title = get_the_title($location_id);
            printf(
                '<option value="%1$d" %2$s>%3$s</option>',
                $location_id,
                selected($selected_location, $location_id, false),
                esc_html($title)
            );
        }
        echo '</select></p>';

        $max_files = (int) $settings['max_files_per_submission'];
        $max_size_text = (int) $settings['max_file_size_mb'];

        echo '<p><label for="lc_place_images">' . esc_html__('Upload Photos', 'lc-public-place-photo-upload') . ' *</label><br />';
        echo '<input type="file" id="lc_place_images" name="place_images[]" accept="image/jpeg,image/png,image/webp" multiple required />';
        echo '<br /><small>' . sprintf(esc_html__('Up to %1$d files, %2$dMB per file. JPG/PNG/WebP only.', 'lc-public-place-photo-upload'), $max_files, $max_size_text) . '</small></p>';

        echo '<p><button type="submit">' . esc_html__('Submit Photos', 'lc-public-place-photo-upload') . '</button></p>';
        echo '</form>';

        return ob_get_clean();
    }

    private static function status_message($status) {
        $messages = [
            'success' => __('Thank you. Your photos were submitted and are pending admin approval.', 'lc-public-place-photo-upload'),
            'closed' => __('Photo submissions are currently closed.', 'lc-public-place-photo-upload'),
            'location_edit_closed' => __('ระบบแก้ไขข้อมูลสถานที่ปิดอยู่ชั่วคราว', 'lc-public-place-photo-upload'),
            'invalid_nonce' => __('Security check failed. Please try again.', 'lc-public-place-photo-upload'),
            'invalid_name' => __('Please provide your name.', 'lc-public-place-photo-upload'),
            'invalid_email' => __('Please provide a valid email address.', 'lc-public-place-photo-upload'),
            'email_not_allowed' => __('This email is not allowed to submit photos.', 'lc-public-place-photo-upload'),
            'editor_email_not_allowed' => __('อีเมลนี้ไม่มีสิทธิ์แก้ไขข้อมูลสถานที่', 'lc-public-place-photo-upload'),
            'otp_send_failed' => __('ไม่สามารถส่ง OTP ได้ กรุณาลองใหม่อีกครั้ง', 'lc-public-place-photo-upload'),
            'invalid_location' => __('Please select a valid place.', 'lc-public-place-photo-upload'),
            'no_images' => __('Please upload at least one image.', 'lc-public-place-photo-upload'),
            'too_many_files' => __('Too many files selected for one submission.', 'lc-public-place-photo-upload'),
            'bad_file' => __('One or more files are invalid. Please use JPG, PNG, or WebP only.', 'lc-public-place-photo-upload'),
            'file_too_large' => __('One or more files are too large.', 'lc-public-place-photo-upload'),
            'upload_failed' => __('Upload failed. Please try again.', 'lc-public-place-photo-upload'),
            'rate_limited' => __('Too many submissions from your network. Please try again later.', 'lc-public-place-photo-upload'),
        ];

        return isset($messages[$status]) ? $messages[$status] : '';
    }

    private static function redirect_with_status($status) {
        $referer = wp_get_referer();
        if (!$referer) {
            $referer = home_url('/');
        }

        $url = add_query_arg('lc_upload_status', $status, $referer);
        wp_safe_redirect($url);
        exit;
    }

    public static function provide_upload_form_url($url = '') {
        $settings = self::get_settings();
        $configured = isset($settings['upload_form_url']) ? (string) $settings['upload_form_url'] : '';
        if ($configured !== '') {
            return esc_url($configured);
        }
        return $url;
    }

    public static function provide_frontend_config($config = []) {
        $settings = self::get_settings();
        $next = is_array($config) ? $config : [];
        $next['enabled'] = $settings['upload_enabled'] === '1';
        $next['max_files'] = (int) $settings['max_files_per_submission'];
        $next['max_file_size_mb'] = (int) $settings['max_file_size_mb'];
        $next['ajax_nonce'] = wp_create_nonce(self::NONCE_ACTION_AJAX);
        $next['submit_nonce'] = wp_create_nonce(self::NONCE_ACTION_SUBMIT);
        $next['rest_url'] = esc_url_raw(rest_url('lc-public-upload/v1/submit'));
        $next['direct_url'] = esc_url_raw(home_url('/?lc_place_photo_submit=1'));
        $next['admin_post_url'] = esc_url_raw(admin_url('admin-post.php'));
        return $next;
    }

    public static function provide_location_edit_config($config = []) {
        $settings = self::get_settings();
        $next = is_array($config) ? $config : [];
        $next['enabled'] = ($settings['location_edit_enabled'] ?? '0') === '1';
        $next['ajax_url'] = esc_url_raw(admin_url('admin-ajax.php'));
        $next['nonce'] = wp_create_nonce(self::NONCE_ACTION_EDIT_ACCESS);
        $next['max_files'] = (int) ($settings['max_files_per_submission'] ?? 6);
        $token = self::get_editor_token_from_cookie();
        $next['has_session'] = self::get_editor_session($token) ? true : false;
        return $next;
    }

    public static function ajax_submit_inline_location_edit() {
        $settings = self::get_settings();
        if (($settings['location_edit_enabled'] ?? '0') !== '1') {
            self::send_json_error_debug(__('ปิดรับคำขอแก้ไขข้อมูลสถานที่ชั่วคราว', 'lc-public-place-photo-upload'), 403, 'location_edit_closed', 'submit_location_request');
        }
        check_ajax_referer(self::NONCE_ACTION_EDIT_ACCESS, 'nonce');
        $session_pack = self::get_inline_editor_session_or_error();
        $settings = is_array($session_pack) && isset($session_pack[1]) && is_array($session_pack[1]) ? $session_pack[1] : self::get_settings();
        $requester_email = is_array($session_pack) && isset($session_pack[2]) ? sanitize_email((string) $session_pack[2]) : '';
        if ($requester_email === '') {
            self::send_json_error_debug(__('อีเมลไม่ถูกต้อง', 'lc-public-place-photo-upload'), 400, 'invalid_email', 'submit_location_request');
        }

        $location_id = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
        if ($location_id <= 0 || get_post_type($location_id) !== 'location') {
            self::send_json_error_debug(__('ข้อมูลสถานที่ไม่ถูกต้อง', 'lc-public-place-photo-upload'), 400, 'invalid_location', 'submit_location_request', ['location_id' => $location_id]);
        }

        $mode = isset($_POST['mode']) ? sanitize_key((string) wp_unslash($_POST['mode'])) : 'single_field';
        $allowed_fields = [
            'address' => 'address',
            'phone' => 'phone',
            'opening_hours' => 'opening_hours',
            'description' => 'description',
            'google_maps' => 'google_maps',
            'facebook' => 'facebook',
        ];

        $current_location = [
            'title' => (string) get_the_title($location_id),
            'address' => (string) get_post_meta($location_id, 'address', true),
            'phone' => (string) get_post_meta($location_id, 'phone', true),
            'opening_hours' => (string) get_post_meta($location_id, 'opening_hours', true),
            'description' => (string) get_post_meta($location_id, 'description', true),
            'google_maps' => (string) get_post_meta($location_id, 'google_maps', true),
            'facebook' => (string) get_post_meta($location_id, 'facebook', true),
        ];
        $next_location = $current_location;
        $snapshot_facility_slugs = self::get_location_facility_slugs($location_id);
        $next_facility_slugs = $snapshot_facility_slugs;

        if ($mode === 'full_location') {
            // Staff cannot edit location title from the inline popup.
            $next_location['title'] = $current_location['title'];
            if (array_key_exists('address', $_POST)) {
                $next_location['address'] = sanitize_textarea_field((string) wp_unslash($_POST['address']));
            }
            if (array_key_exists('phone', $_POST)) {
                $next_location['phone'] = sanitize_text_field((string) wp_unslash($_POST['phone']));
            }
            if (array_key_exists('opening_hours', $_POST)) {
                $next_location['opening_hours'] = sanitize_textarea_field((string) wp_unslash($_POST['opening_hours']));
            }
            if (array_key_exists('description', $_POST)) {
                $next_location['description'] = sanitize_textarea_field(self::normalize_multiline_text((string) wp_unslash($_POST['description'])));
            }
            if (array_key_exists('google_maps', $_POST)) {
                $next_location['google_maps'] = esc_url_raw((string) wp_unslash($_POST['google_maps']));
            }
            if (array_key_exists('facebook', $_POST)) {
                $next_location['facebook'] = esc_url_raw((string) wp_unslash($_POST['facebook']));
            }
            $has_facility_input = isset($_POST['facility_slugs']) || isset($_POST['facility_slugs_json']);
            if ($has_facility_input) {
                $facility_raw = (array) ($_POST['facility_slugs'] ?? []);
                if (empty($facility_raw)) {
                    $facility_json = isset($_POST['facility_slugs_json']) ? (string) wp_unslash($_POST['facility_slugs_json']) : '';
                    $facility_raw_json = json_decode($facility_json, true);
                    if (is_array($facility_raw_json)) {
                        $facility_raw = $facility_raw_json;
                    }
                }
                $next_facility_slugs = self::sanitize_facility_slugs($facility_raw);
            }
        } else {
            $field = isset($_POST['field']) ? sanitize_key((string) wp_unslash($_POST['field'])) : '';
            $suggestion = isset($_POST['suggestion']) ? sanitize_textarea_field((string) wp_unslash($_POST['suggestion'])) : '';
            if ($field === '' || $suggestion === '') {
                self::send_json_error_debug(__('กรุณากรอกข้อมูลที่ต้องการแก้ไข', 'lc-public-place-photo-upload'), 400, 'missing_suggestion', 'submit_location_request');
            }
            if (!isset($allowed_fields[$field])) {
                self::send_json_error_debug(__('ไม่รองรับช่องข้อมูลนี้', 'lc-public-place-photo-upload'), 400, 'unsupported_field', 'submit_location_request', ['field' => $field]);
            }
            $next_location[$field] = $suggestion;
        }

        $snapshot_sessions = [];
        $proposed_sessions = [];
        $delete_session_ids = [];
        $new_sessions = [];
        $has_session_changes = false;
        $request_note = sanitize_textarea_field(self::normalize_multiline_text((string) wp_unslash($_POST['request_note'] ?? '')));
        if ($mode === 'full_location') {
            $sessions_json = isset($_POST['sessions']) ? (string) wp_unslash($_POST['sessions']) : '';
            $sessions_payload = json_decode($sessions_json, true);
            if (!is_array($sessions_payload)) {
                $sessions_payload = [];
            }
            $allowed_session_ids = self::get_location_session_ids($location_id);
            $allowed_session_ids = array_values(array_filter(array_map('intval', $allowed_session_ids)));
            $allowed_session_map = array_fill_keys($allowed_session_ids, true);
            foreach ($allowed_session_ids as $sid) {
                $snapshot_sessions[$sid] = [
                    'time_period' => (string) get_post_meta($sid, 'time_period', true),
                    'session_details' => (string) get_post_meta($sid, 'session_details', true),
                ];
            }
            foreach ($sessions_payload as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $sid = (int) ($row['id'] ?? 0);
                if ($sid <= 0 || !isset($allowed_session_map[$sid])) {
                    continue;
                }
                $proposed_sessions[] = [
                    'id' => $sid,
                    'time_period' => sanitize_textarea_field(self::normalize_multiline_text((string) ($row['time_period'] ?? ''))),
                    'session_details' => sanitize_textarea_field(self::normalize_multiline_text((string) ($row['session_details'] ?? ''))),
                ];
            }

            $delete_session_ids = array_values(array_filter(array_map('intval', (array) ($_POST['delete_session_ids'] ?? []))));
            if (empty($delete_session_ids)) {
                $delete_session_ids_json = isset($_POST['delete_session_ids_json']) ? (string) wp_unslash($_POST['delete_session_ids_json']) : '';
                $delete_session_ids_raw = json_decode($delete_session_ids_json, true);
                if (is_array($delete_session_ids_raw)) {
                    $delete_session_ids = array_values(array_filter(array_map('intval', $delete_session_ids_raw)));
                }
            }
            $delete_session_ids = array_values(array_filter($delete_session_ids, static function($sid) use ($allowed_session_map) {
                return $sid > 0 && isset($allowed_session_map[$sid]);
            }));

            $available_course_ids = self::get_location_available_course_ids($location_id);
            $available_course_map = array_fill_keys(array_values(array_filter(array_map('intval', $available_course_ids))), true);
            $new_sessions_json = isset($_POST['new_sessions']) ? (string) wp_unslash($_POST['new_sessions']) : '';
            $new_sessions_payload = json_decode($new_sessions_json, true);
            if (!is_array($new_sessions_payload)) {
                $new_sessions_payload = [];
            }
            foreach ($new_sessions_payload as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $course_id = (int) ($row['course_id'] ?? 0);
                if ($course_id <= 0 || !isset($available_course_map[$course_id])) {
                    continue;
                }
                $session_details = sanitize_textarea_field(self::normalize_multiline_text((string) ($row['session_details'] ?? '')));
                $time_period = sanitize_textarea_field(self::normalize_multiline_text((string) ($row['time_period'] ?? '')));
                $new_sessions[] = [
                    'course_id' => $course_id,
                    'time_period' => $time_period,
                    'session_details' => $session_details,
                ];
            }

            foreach ($proposed_sessions as $row) {
                $sid = (int) ($row['id'] ?? 0);
                $before = is_array($snapshot_sessions[$sid] ?? null) ? $snapshot_sessions[$sid] : [];
                foreach (['time_period', 'session_details'] as $session_key) {
                    $before_value = self::normalized_compare_key((string) ($before[$session_key] ?? ''));
                    $after_value = self::normalized_compare_key((string) ($row[$session_key] ?? ''));
                    if ($before_value !== $after_value) {
                        $has_session_changes = true;
                        break 2;
                    }
                }
            }
            if (!$has_session_changes && (!empty($delete_session_ids) || !empty($new_sessions))) {
                $has_session_changes = true;
            }
        }

        $has_changes = false;
        foreach ($allowed_fields as $field_key) {
            $normalized_before = self::normalized_compare_key((string) ($current_location[$field_key] ?? ''));
            $normalized_after = self::normalized_compare_key((string) ($next_location[$field_key] ?? ''));
            if ($normalized_before !== $normalized_after) {
                $has_changes = true;
                break;
            }
        }
        $snapshot_facility_compare = $snapshot_facility_slugs;
        $next_facility_compare = $next_facility_slugs;
        sort($snapshot_facility_compare);
        sort($next_facility_compare);
        $has_facility_changes = ($next_facility_compare !== $snapshot_facility_compare);

        $gallery_meta_key = (string) ($settings['location_gallery_meta_key'] ?? 'images');
        $snapshot_image_ids = self::parse_gallery_ids(get_post_meta($location_id, $gallery_meta_key, true));
        if ($gallery_meta_key !== 'images') {
            $legacy_image_ids = self::parse_gallery_ids(get_post_meta($location_id, 'images', true));
            if (!empty($legacy_image_ids)) {
                $snapshot_image_ids = array_values(array_unique(array_merge($snapshot_image_ids, $legacy_image_ids)));
            }
        }
        $remove_image_ids = array_values(array_filter(array_map('intval', (array) ($_POST['remove_image_ids'] ?? []))));
        if (empty($remove_image_ids)) {
            $remove_image_ids_json = isset($_POST['remove_image_ids_json']) ? (string) wp_unslash($_POST['remove_image_ids_json']) : '';
            $remove_image_ids_raw = json_decode($remove_image_ids_json, true);
            if (is_array($remove_image_ids_raw)) {
                $remove_image_ids = array_values(array_filter(array_map('intval', $remove_image_ids_raw)));
            }
        }
        $snapshot_image_map = array_fill_keys($snapshot_image_ids, true);
        $remove_image_ids = array_values(array_filter($remove_image_ids, static function($id) use ($snapshot_image_map) {
            return $id > 0 && isset($snapshot_image_map[$id]);
        }));

        $new_image_ids = [];
        if ($mode === 'full_location') {
            $new_files = self::validate_staff_edit_image_files($_FILES['new_images'] ?? null, $settings);
            if (is_wp_error($new_files)) {
                self::send_json_error_debug((string) $new_files->get_error_message(), 400, 'image_validation_failed', 'submit_location_request');
            }
            if (!empty($new_files)) {
                $allowed_mimes = self::staff_allowed_image_mimes();
                foreach ($new_files as $file) {
                    $attachment_id = self::fast_insert_attachment_from_uploaded_file($file, $location_id, $allowed_mimes);
                    if (is_wp_error($attachment_id) || !$attachment_id) {
                        $file_name = sanitize_file_name((string) ($file['name'] ?? ''));
                        $file_label = $file_name !== '' ? ('"' . $file_name . '"') : 'รูปภาพที่อัปโหลด';
                        self::send_json_error_debug($file_label . ' อัปโหลดไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 400, 'image_upload_failed', 'submit_location_request');
                    }
                    $new_image_ids[] = (int) $attachment_id;
                }
            }
        }

        if (!$has_changes && !$has_facility_changes && empty($remove_image_ids) && empty($new_image_ids) && !$has_session_changes && $request_note === '') {
            self::send_json_error_debug(__('ข้อความใหม่เหมือนข้อมูลเดิม', 'lc-public-place-photo-upload'), 400, 'no_changes_detected', 'submit_location_request');
        }

        if (!post_type_exists(self::CHANGE_CPT)) {
            self::register_change_request_cpt();
        }

        $request_id = wp_insert_post([
            'post_type' => self::CHANGE_CPT,
            'post_status' => 'pending',
            'post_title' => sprintf('Inline Change - %s - %s', get_the_title($location_id), current_time('mysql')),
        ], true);
        if (is_wp_error($request_id) || !$request_id) {
            self::send_json_error_debug(__('ไม่สามารถสร้างคำขอแก้ไขได้', 'lc-public-place-photo-upload'), 500, 'request_create_failed', 'submit_location_request', ['location_id' => $location_id]);
        }

        $payload = [
            'request_type' => 'update_location',
            'location' => $next_location,
            'request_note' => $request_note,
            'remove_image_ids' => $remove_image_ids,
            'new_image_ids' => $new_image_ids,
            'sessions' => $proposed_sessions,
            'delete_session_ids' => $delete_session_ids,
            'new_sessions' => $new_sessions,
            'snapshot' => [
                'location' => $current_location,
                'facility_slugs' => $snapshot_facility_slugs,
                'image_ids' => $snapshot_image_ids,
                'sessions' => $snapshot_sessions,
            ],
            'inline_meta' => [
                'mode' => $mode,
                'submitted_from' => 'map_inline_popup',
            ],
        ];
        if ($mode === 'full_location') {
            $payload['facility_slugs'] = $next_facility_slugs;
        }

        update_post_meta($request_id, '_lc_change_status', 'pending');
        update_post_meta($request_id, '_lc_request_type', 'update_location');
        update_post_meta($request_id, '_lc_location_id', $location_id);
        update_post_meta($request_id, '_lc_requester_email', strtolower($requester_email));
        update_post_meta($request_id, '_lc_change_payload', wp_json_encode($payload, JSON_UNESCAPED_UNICODE));
        update_post_meta($request_id, '_lc_submitted_at', current_time('mysql'));
        delete_post_meta($request_id, '_lc_reject_reason');

        wp_send_json_success([
            'message' => __('ส่งคำขอแก้ไขเรียบร้อยแล้ว', 'lc-public-place-photo-upload'),
            'request_id' => (int) $request_id,
        ]);
    }

    public static function ajax_submit_course_edit_request() {
        $settings = self::get_settings();
        if (($settings['location_edit_enabled'] ?? '0') !== '1') {
            self::send_json_error_debug(__('ปิดรับคำขอแก้ไขข้อมูลชั่วคราว', 'lc-public-place-photo-upload'), 403, 'location_edit_closed', 'submit_course_request');
        }
        check_ajax_referer(self::NONCE_ACTION_EDIT_ACCESS, 'nonce');
        $session_pack = self::get_inline_editor_session_or_error();
        $requester_email = is_array($session_pack) && isset($session_pack[2]) ? sanitize_email((string) $session_pack[2]) : '';
        if ($requester_email === '') {
            self::send_json_error_debug(__('อีเมลไม่ถูกต้อง', 'lc-public-place-photo-upload'), 400, 'invalid_email', 'submit_course_request');
        }

        $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
        if ($course_id <= 0 || get_post_type($course_id) !== 'course') {
            self::send_json_error_debug(__('ข้อมูลคอร์สไม่ถูกต้อง', 'lc-public-place-photo-upload'), 400, 'invalid_course', 'submit_course_request', ['course_id' => $course_id]);
        }

        $current_course = [
            'title' => (string) get_the_title($course_id),
            'course_description' => (string) get_post_meta($course_id, 'course_description', true),
            'learning_link' => (string) get_post_meta($course_id, 'learning_link', true),
            'total_minutes' => (string) get_post_meta($course_id, 'total_minutes', true),
            'price' => (string) get_post_meta($course_id, 'price', true),
            'has_certificate' => !empty(get_post_meta($course_id, 'has_certificate', true)) ? '1' : '0',
            'image_ids' => self::parse_gallery_ids(get_post_meta($course_id, 'images', true)),
        ];
        $next_course = $current_course;
        // Locked fields for staff popup edits.
        $next_course['title'] = $current_course['title'];
        $next_course['course_description'] = sanitize_textarea_field(self::normalize_multiline_text((string) wp_unslash($_POST['course_description'] ?? $current_course['course_description'])));
        $next_course['learning_link'] = esc_url_raw((string) wp_unslash($_POST['learning_link'] ?? $current_course['learning_link']));
        $next_course['total_minutes'] = $current_course['total_minutes'];
        $next_course['price'] = $current_course['price'];
        $next_course['has_certificate'] = $current_course['has_certificate'];

        $remove_image_ids = [];
        if (isset($_POST['remove_image_ids'])) {
            $raw_remove_images = wp_unslash($_POST['remove_image_ids']);
            if (is_string($raw_remove_images)) {
                $decoded = json_decode($raw_remove_images, true);
                if (is_array($decoded)) {
                    $remove_image_ids = $decoded;
                }
            } elseif (is_array($raw_remove_images)) {
                $remove_image_ids = $raw_remove_images;
            }
        }
        $remove_image_ids = array_values(array_filter(array_map('intval', (array) $remove_image_ids)));
        $existing_image_map = array_fill_keys(array_values(array_filter(array_map('intval', (array) ($current_course['image_ids'] ?? [])))), true);
        $remove_image_ids = array_values(array_filter($remove_image_ids, static function($id) use ($existing_image_map) {
            return $id > 0 && isset($existing_image_map[$id]);
        }));

        $new_image_ids = [];
        $new_files = self::validate_staff_edit_image_files($_FILES['new_images'] ?? null, $settings);
        if (is_wp_error($new_files)) {
            self::send_json_error_debug((string) $new_files->get_error_message(), 400, 'image_validation_failed', 'submit_course_request');
        }
        if (!empty($new_files)) {
            $allowed_mimes = self::staff_allowed_image_mimes();
            foreach ($new_files as $file) {
                $attachment_id = self::fast_insert_attachment_from_uploaded_file($file, $course_id, $allowed_mimes);
                if (is_wp_error($attachment_id) || !$attachment_id) {
                    $file_name = sanitize_file_name((string) ($file['name'] ?? ''));
                    $file_label = $file_name !== '' ? ('"' . $file_name . '"') : 'รูปภาพที่อัปโหลด';
                    self::send_json_error_debug($file_label . ' อัปโหลดไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 400, 'image_upload_failed', 'submit_course_request');
                }
                $new_image_ids[] = (int) $attachment_id;
            }
        }

        $request_note = sanitize_textarea_field(self::normalize_multiline_text((string) wp_unslash($_POST['request_note'] ?? '')));
        $session_ids = self::get_course_session_ids($course_id);
        $snapshot_sessions = [];
        foreach ($session_ids as $sid) {
            $snapshot_sessions[$sid] = [
                'time_period' => (string) get_post_meta($sid, 'time_period', true),
                'session_details' => (string) get_post_meta($sid, 'session_details', true),
            ];
        }
        $allowed_session_map = array_fill_keys(array_values(array_filter(array_map('intval', $session_ids))), true);
        $delete_session_ids = [];
        if (isset($_POST['delete_session_ids'])) {
            $raw_delete = wp_unslash($_POST['delete_session_ids']);
            if (is_string($raw_delete)) {
                $decoded = json_decode($raw_delete, true);
                if (is_array($decoded)) {
                    $delete_session_ids = $decoded;
                }
            } elseif (is_array($raw_delete)) {
                $delete_session_ids = $raw_delete;
            }
        }
        $delete_session_ids = array_values(array_filter(array_map('intval', (array) $delete_session_ids)));
        $delete_session_ids = array_values(array_filter($delete_session_ids, static function($sid) use ($allowed_session_map) {
            return $sid > 0 && isset($allowed_session_map[$sid]);
        }));

        $sessions_json = isset($_POST['sessions']) ? (string) wp_unslash($_POST['sessions']) : '';
        $sessions_payload = json_decode($sessions_json, true);
        if (!is_array($sessions_payload)) {
            $sessions_payload = [];
        }
        $sessions = [];
        foreach ($sessions_payload as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sid = (int) ($row['id'] ?? 0);
            if ($sid <= 0 || !isset($allowed_session_map[$sid])) {
                continue;
            }
            $next_row = [
                'id' => $sid,
                'time_period' => sanitize_textarea_field(self::normalize_multiline_text((string) ($row['time_period'] ?? ''))),
                'session_details' => sanitize_textarea_field(self::normalize_multiline_text((string) ($row['session_details'] ?? ''))),
            ];
            $before = is_array($snapshot_sessions[$sid] ?? null) ? $snapshot_sessions[$sid] : [];
            $is_changed = (
                self::normalized_compare_key((string) ($before['time_period'] ?? '')) !== self::normalized_compare_key((string) ($next_row['time_period'] ?? '')) ||
                self::normalized_compare_key((string) ($before['session_details'] ?? '')) !== self::normalized_compare_key((string) ($next_row['session_details'] ?? ''))
            );
            if (!$is_changed) {
                continue;
            }
            $sessions[] = $next_row;
        }

        $new_sessions = [];
        $new_sessions_json = isset($_POST['new_sessions']) ? (string) wp_unslash($_POST['new_sessions']) : '';
        $new_sessions_payload = json_decode($new_sessions_json, true);
        if (!is_array($new_sessions_payload)) {
            $new_sessions_payload = [];
        }
        $allowed_new_location_map = array_fill_keys(self::get_course_available_location_ids($course_id), true);
        foreach ($new_sessions_payload as $row) {
            if (!is_array($row)) {
                continue;
            }
            $location_id = isset($row['location_id']) ? (int) $row['location_id'] : 0;
            if ($location_id <= 0 || get_post_type($location_id) !== 'location' || !isset($allowed_new_location_map[$location_id])) {
                continue;
            }
            $new_sessions[] = [
                'location_id' => $location_id,
                'time_period' => sanitize_textarea_field(self::normalize_multiline_text((string) ($row['time_period'] ?? ''))),
                'session_details' => sanitize_textarea_field(self::normalize_multiline_text((string) ($row['session_details'] ?? ''))),
            ];
        }

        $has_changes = false;
        foreach (['title', 'course_description', 'learning_link'] as $field_key) {
            if (self::normalized_compare_key((string) ($current_course[$field_key] ?? '')) !== self::normalized_compare_key((string) ($next_course[$field_key] ?? ''))) {
                $has_changes = true;
                break;
            }
        }
        if (!$has_changes) {
            $current_total_minutes = is_numeric((string) ($current_course['total_minutes'] ?? '')) ? (int) $current_course['total_minutes'] : null;
            $next_total_minutes = is_numeric((string) ($next_course['total_minutes'] ?? '')) ? (int) $next_course['total_minutes'] : null;
            if ($current_total_minutes !== $next_total_minutes) {
                $has_changes = true;
            }
        }
        if (!$has_changes) {
            $current_price = is_numeric((string) ($current_course['price'] ?? '')) ? (float) $current_course['price'] : null;
            $next_price = is_numeric((string) ($next_course['price'] ?? '')) ? (float) $next_course['price'] : null;
            if ($current_price !== $next_price) {
                $has_changes = true;
            }
        }
        if (!$has_changes && (string) ($current_course['has_certificate'] ?? '0') !== (string) ($next_course['has_certificate'] ?? '0')) {
            $has_changes = true;
        }
        if (!$has_changes && (!empty($remove_image_ids) || !empty($new_image_ids))) {
            $has_changes = true;
        }
        if (!$has_changes && (!empty($sessions) || !empty($delete_session_ids) || !empty($new_sessions))) {
            $has_changes = true;
        }
        if (!$has_changes && $request_note !== '') {
            $has_changes = true;
        }
        if (!$has_changes) {
            self::send_json_error_debug(__('ข้อความใหม่เหมือนข้อมูลเดิม', 'lc-public-place-photo-upload'), 400, 'no_changes_detected', 'submit_course_request');
        }

        if (!post_type_exists(self::CHANGE_CPT)) {
            self::register_change_request_cpt();
        }
        $request_id = wp_insert_post([
            'post_type' => self::CHANGE_CPT,
            'post_status' => 'pending',
            'post_title' => sprintf('Course Change - %s - %s', get_the_title($course_id), current_time('mysql')),
        ], true);
        if (is_wp_error($request_id) || !$request_id) {
            self::send_json_error_debug(__('ไม่สามารถสร้างคำขอแก้ไขได้', 'lc-public-place-photo-upload'), 500, 'request_create_failed', 'submit_course_request', ['course_id' => $course_id]);
        }

        $payload = [
            'request_type' => 'update_course',
            'course' => $next_course,
            'remove_image_ids' => $remove_image_ids,
            'new_image_ids' => $new_image_ids,
            'sessions' => $sessions,
            'delete_session_ids' => $delete_session_ids,
            'new_sessions' => $new_sessions,
            'request_note' => $request_note,
            'snapshot' => [
                'course' => $current_course,
                'image_ids' => $current_course['image_ids'],
                'sessions' => $snapshot_sessions,
            ],
            'inline_meta' => [
                'mode' => 'course_quick',
                'submitted_from' => 'course_page',
            ],
        ];
        $course_diff_rows = self::build_course_change_diff_rows($course_id, $payload);
        if (empty($course_diff_rows)) {
            wp_delete_post((int) $request_id, true);
            self::send_json_error_debug(__('ข้อความใหม่เหมือนข้อมูลเดิม', 'lc-public-place-photo-upload'), 400, 'no_changes_detected', 'submit_course_request');
        }

        update_post_meta($request_id, '_lc_change_status', 'pending');
        update_post_meta($request_id, '_lc_request_type', 'update_course');
        update_post_meta($request_id, '_lc_course_id', $course_id);
        update_post_meta($request_id, '_lc_requester_email', strtolower($requester_email));
        update_post_meta($request_id, '_lc_change_payload', wp_json_encode($payload, JSON_UNESCAPED_UNICODE));
        update_post_meta($request_id, '_lc_submitted_at', current_time('mysql'));
        delete_post_meta($request_id, '_lc_reject_reason');

        wp_send_json_success([
            'message' => __('ส่งคำขอแก้ไขคอร์สเรียบร้อยแล้ว', 'lc-public-place-photo-upload'),
            'request_id' => (int) $request_id,
        ]);
    }

    public static function ajax_get_course_edit_context() {
        $settings = self::get_settings();
        if (($settings['location_edit_enabled'] ?? '0') !== '1') {
            self::send_json_error_debug(__('ปิดรับคำขอแก้ไขข้อมูลชั่วคราว', 'lc-public-place-photo-upload'), 403, 'location_edit_closed', 'load_course_context');
        }
        check_ajax_referer(self::NONCE_ACTION_EDIT_ACCESS, 'nonce');
        self::get_inline_editor_session_or_error();

        $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
        if ($course_id <= 0 || get_post_type($course_id) !== 'course') {
            self::send_json_error_debug(__('ข้อมูลคอร์สไม่ถูกต้อง', 'lc-public-place-photo-upload'), 400, 'invalid_course', 'load_course_context', ['course_id' => $course_id]);
        }

        $sessions = [];
        foreach (self::get_course_session_ids($course_id) as $sid) {
            $location_id = (int) get_post_meta($sid, 'location', true);
            $sessions[] = [
                'id' => $sid,
                'location_id' => $location_id,
                'location_title' => $location_id > 0 ? self::decode_html_entities_text((string) get_the_title($location_id)) : ('Session #' . (string) $sid),
                'time_period' => self::normalize_multiline_text((string) get_post_meta($sid, 'time_period', true)),
                'session_details' => self::normalize_multiline_text((string) get_post_meta($sid, 'session_details', true)),
                'apply_url' => (string) get_post_meta($sid, 'apply_url', true),
            ];
        }

        $available_locations = [];
        $location_ids = self::get_course_available_location_ids($course_id);
        foreach ((array) $location_ids as $location_id) {
            $location_id = (int) $location_id;
            if ($location_id <= 0) {
                continue;
            }
            $title = self::decode_html_entities_text((string) get_the_title($location_id));
            if ($title === '') {
                continue;
            }
            $available_locations[] = [
                'id' => $location_id,
                'title' => $title,
            ];
        }

        wp_send_json_success([
            'course_id' => $course_id,
            'course' => [
                'title' => self::decode_html_entities_text((string) get_the_title($course_id)),
                'course_description' => self::normalize_multiline_text((string) get_post_meta($course_id, 'course_description', true)),
                'learning_link' => (string) get_post_meta($course_id, 'learning_link', true),
                'total_minutes' => (string) get_post_meta($course_id, 'total_minutes', true),
                'price' => (string) get_post_meta($course_id, 'price', true),
                'has_certificate' => !empty(get_post_meta($course_id, 'has_certificate', true)) ? '1' : '0',
                'images' => self::build_requester_attachment_items(self::parse_gallery_ids(get_post_meta($course_id, 'images', true))),
            ],
            'sessions' => $sessions,
            'available_locations' => $available_locations,
        ]);
    }

    private static function parse_editor_allowed_emails($settings) {
        $editor_raw = (string) ($settings['allowed_editor_emails'] ?? '');
        $submitter_raw = (string) ($settings['allowed_submitter_emails'] ?? '');
        $editor = self::parse_allowed_emails($editor_raw);
        if (!empty($editor)) {
            return $editor;
        }
        $submitter = self::parse_allowed_emails($submitter_raw);
        if (!empty($submitter)) {
            return $submitter;
        }
        return [];
    }

    private static function normalize_email_for_match($email) {
        $email = strtolower(trim((string) $email));
        $email = preg_replace('/\s+/', '', $email);
        return is_string($email) ? $email : '';
    }

    private static function is_editor_email_allowed($email, $settings) {
        $email = self::normalize_email_for_match($email);
        if ($email === '' || strpos($email, '@') === false) {
            return false;
        }
        $allowed = self::parse_editor_allowed_emails($settings);
        if (empty($allowed)) {
            return false;
        }
        if (in_array('*', $allowed, true)) {
            return true;
        }
        if (in_array($email, $allowed, true)) {
            return true;
        }
        $email_domain = substr(strrchr($email, '@'), 1);
        foreach ($allowed as $rule) {
            $rule = self::normalize_email_for_match($rule);
            if ($rule === '') {
                continue;
            }
            if (strpos($rule, '@') === 0) {
                $rule_domain = substr($rule, 1);
                if ($rule_domain !== '' && $email_domain === $rule_domain) {
                    return true;
                }
                continue;
            }
            if (strpos($rule, '*@') === 0) {
                $rule_domain = substr($rule, 2);
                if ($rule_domain !== '' && $email_domain === $rule_domain) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function otp_key($email) {
        return 'lc_loc_edit_otp_' . md5(strtolower((string) $email));
    }

    private static function otp_rate_key($email) {
        return 'lc_loc_edit_otp_rate_' . md5(strtolower((string) $email));
    }

    private static function otp_verify_attempt_key($email, $ip) {
        return 'lc_loc_edit_otp_verify_attempt_' . md5(strtolower((string) $email) . '|' . (string) $ip);
    }

    private static function otp_verify_lock_key($email, $ip) {
        return 'lc_loc_edit_otp_verify_lock_' . md5(strtolower((string) $email) . '|' . (string) $ip);
    }

    private static function otp_challenge_id() {
        return wp_generate_password(24, false, false);
    }

    private static function token_key($token) {
        return 'lc_loc_edit_token_' . md5((string) $token);
    }

    private static function token_option_key($token) {
        return 'lc_loc_edit_session_' . md5((string) $token);
    }

    private static function editor_cookie_name() {
        return 'lc_loc_edit_token';
    }

    private static function set_editor_cookie($token) {
        $token = sanitize_text_field((string) $token);
        if ($token === '') {
            return;
        }
        $path = defined('COOKIEPATH') && (string) COOKIEPATH !== '' ? (string) COOKIEPATH : '/';
        setcookie(self::editor_cookie_name(), $token, time() + self::EDIT_SESSION_TTL, $path, '', is_ssl(), true);
        $_COOKIE[self::editor_cookie_name()] = $token;
    }

    private static function get_editor_token_from_cookie() {
        return isset($_COOKIE[self::editor_cookie_name()]) ? sanitize_text_field((string) $_COOKIE[self::editor_cookie_name()]) : '';
    }

    private static function clear_editor_cookie() {
        $path = defined('COOKIEPATH') && (string) COOKIEPATH !== '' ? (string) COOKIEPATH : '/';
        setcookie(self::editor_cookie_name(), '', time() - HOUR_IN_SECONDS, $path, '', is_ssl(), true);
        unset($_COOKIE[self::editor_cookie_name()]);
    }

    private static function current_logout_nonce() {
        return wp_create_nonce(self::NONCE_ACTION_EDIT_LOGOUT);
    }

    private static function is_valid_logout_nonce($nonce) {
        return is_string($nonce) && $nonce !== '' && wp_verify_nonce($nonce, self::NONCE_ACTION_EDIT_LOGOUT);
    }

    private static function issue_editor_token($email, $location_id) {
        $token = wp_generate_password(40, false, false);
        $payload = [
            'email' => strtolower((string) $email),
            'location_id' => (int) $location_id,
            'issued_at' => time(),
            'expires_at' => time() + self::EDIT_SESSION_TTL,
        ];
        update_option(self::token_option_key($token), $payload, false);
        // Backward compatibility with previous transient-based sessions.
        set_transient(self::token_key($token), $payload, self::EDIT_SESSION_TTL);
        self::set_editor_cookie($token);
        return $token;
    }

    private static function get_editor_session($token) {
        if (!is_string($token) || $token === '') {
            return null;
        }
        $payload = get_option(self::token_option_key($token), null);
        if (is_array($payload)) {
            $expires_at = isset($payload['expires_at']) ? (int) $payload['expires_at'] : 0;
            if ($expires_at > 0 && $expires_at < time()) {
                self::clear_editor_session($token);
                return null;
            }
            return $payload;
        }
        // Fallback for old tokens issued before persistent sessions.
        $payload = get_transient(self::token_key($token));
        if (!is_array($payload)) {
            return null;
        }
        if (!isset($payload['expires_at'])) {
            $payload['expires_at'] = time() + self::EDIT_SESSION_TTL;
        }
        update_option(self::token_option_key($token), $payload, false);
        return $payload;
    }

    private static function clear_editor_session($token) {
        if (!is_string($token) || $token === '') {
            return;
        }
        delete_option(self::token_option_key($token));
        delete_transient(self::token_key($token));
        self::clear_editor_cookie();
    }

    private static function send_otp_email($to_email, $subject, $message, $settings) {
        $to_email = sanitize_email((string) $to_email);
        if ($to_email === '' || !is_email($to_email)) {
            return new WP_Error('invalid_email', 'Invalid recipient email');
        }

        // Prefer official Brevo plugin transport when available.
        if (class_exists('SIB_Manager') && is_callable(['SIB_Manager', 'sib_email'])) {
            $sib_result = call_user_func(['SIB_Manager', 'sib_email'], $to_email, (string) $subject, (string) $message);
            if (!is_wp_error($sib_result) && $sib_result) {
                return true;
            }
        }

        // Prefer wp_mail first so existing SMTP/Brevo mail plugins can handle delivery.
        $sent = wp_mail($to_email, (string) $subject, (string) $message);
        if ($sent) {
            return true;
        }

        $brevo_key = trim((string) ($settings['brevo_api_key'] ?? ''));
        $brevo_sender_email = sanitize_email((string) ($settings['brevo_sender_email'] ?? ''));
        $brevo_sender_name = trim((string) ($settings['brevo_sender_name'] ?? ''));
        if ($brevo_sender_name === '') {
            $brevo_sender_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        }

        if ($brevo_key !== '' && $brevo_sender_email !== '') {
            $payload = [
                'sender' => [
                    'name' => $brevo_sender_name,
                    'email' => $brevo_sender_email,
                ],
                'to' => [
                    ['email' => $to_email],
                ],
                'subject' => (string) $subject,
                'textContent' => (string) $message,
            ];
            $res = wp_remote_post('https://api.brevo.com/v3/smtp/email', [
                'timeout' => 20,
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'api-key' => $brevo_key,
                ],
                'body' => wp_json_encode($payload),
            ]);
            if (!is_wp_error($res)) {
                $code = (int) wp_remote_retrieve_response_code($res);
                if ($code >= 200 && $code < 300) {
                    return true;
                }
            }
        }
        return new WP_Error('otp_send_failed', 'Unable to send OTP email');
    }

    public static function ajax_request_location_edit_otp() {
        $settings = self::get_settings();
        if (($settings['location_edit_enabled'] ?? '0') !== '1') {
            self::ajax_error('location_edit_closed', 403, 'otp_request');
        }
        // Intentionally do not require WP nonce here because OTP flow is email-based and
        // should not break when testers login/logout WordPress in another tab.

        $email_raw = isset($_POST['email']) ? (string) wp_unslash($_POST['email']) : '';
        $email = self::normalize_email_for_match($email_raw);
        if ($email === '' || strpos($email, '@') === false) {
            self::ajax_error('invalid_email', 400);
        }
        if (!self::is_editor_email_allowed($email, $settings)) {
            self::ajax_error('editor_email_not_allowed', 403, 'otp_request');
        }

        $rate_key = self::otp_rate_key($email);
        $rate_state = get_transient($rate_key);
        if ($rate_state) {
            $remaining = 60;
            if (is_array($rate_state) && !empty($rate_state['until'])) {
                $remaining = max(1, (int) $rate_state['until'] - time());
            }
            self::send_json_error_debug(__('กรุณารอสักครู่ก่อนขอ OTP ใหม่', 'lc-public-place-photo-upload'), 429, 'otp_rate_limited', 'otp_request', [
                'resend_wait_seconds' => $remaining,
            ]);
        }

        $otp = (string) wp_rand(100000, 999999);
        $challenge_id = self::otp_challenge_id();
        $ttl_minutes = max(3, min(30, (int) ($settings['otp_ttl_minutes'] ?? 10)));
        $subject = sprintf('[%s] OTP สำหรับแก้ไขข้อมูลสถานที่', wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $message = "รหัส OTP ของคุณคือ: {$otp}\nรหัสหมดอายุภายใน {$ttl_minutes} นาที";
        $mail_to = sanitize_email($email);
        if ($mail_to === '' || !is_email($mail_to)) {
            self::ajax_error('invalid_email', 400, 'otp_request');
        }
        $send_result = self::send_otp_email($mail_to, $subject, $message, $settings);
        if (is_wp_error($send_result)) {
            self::ajax_error('otp_send_failed', 500, 'otp_request');
        }
        set_transient(self::otp_key($email), [
            'otp' => $otp,
            'challenge_id' => $challenge_id,
            'location_id' => isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0,
            'created_at' => time(),
        ], $ttl_minutes * MINUTE_IN_SECONDS);
        set_transient($rate_key, [
            'until' => time() + 60,
        ], 60);

        wp_send_json_success([
            'message' => __('ส่ง OTP ไปยังอีเมลเรียบร้อยแล้ว', 'lc-public-place-photo-upload'),
            'challenge_id' => $challenge_id,
            'resend_cooldown_seconds' => 60,
            'otp_ttl_minutes' => $ttl_minutes,
        ]);
    }

    public static function ajax_verify_location_edit_otp() {
        $settings = self::get_settings();
        if (($settings['location_edit_enabled'] ?? '0') !== '1') {
            self::ajax_error('location_edit_closed', 403, 'otp_verify');
        }
        // Intentionally do not require WP nonce here; use OTP + challenge instead.

        $email_raw = isset($_POST['email']) ? (string) wp_unslash($_POST['email']) : '';
        $email = self::normalize_email_for_match($email_raw);
        $otp = isset($_POST['otp']) ? preg_replace('/\D+/', '', (string) wp_unslash($_POST['otp'])) : '';
        $challenge_id = isset($_POST['challenge_id']) ? sanitize_text_field((string) wp_unslash($_POST['challenge_id'])) : '';
        $location_id = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
        if ($email === '' || strpos($email, '@') === false) {
            self::ajax_error('invalid_email', 400, 'otp_verify');
        }
        if ($otp === '') {
            self::ajax_error('invalid_nonce', 400, 'otp_verify');
        }

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
        $verify_lock_key = self::otp_verify_lock_key($email, $remote_ip);
        $verify_attempt_key = self::otp_verify_attempt_key($email, $remote_ip);
        if (get_transient($verify_lock_key)) {
            self::send_json_error_debug(__('กรอก OTP ผิดหลายครั้ง กรุณาขอรหัสใหม่แล้วลองอีกครั้ง', 'lc-public-place-photo-upload'), 429, 'otp_verify_locked', 'otp_verify');
        }

        $stored = get_transient(self::otp_key($email));
        if (!is_array($stored)) {
            self::send_json_error_debug(__('OTP ไม่ถูกต้องหรือหมดอายุแล้ว', 'lc-public-place-photo-upload'), 400, 'otp_invalid_or_expired', 'otp_verify');
        }
        if ($challenge_id === '' || (string) ($stored['challenge_id'] ?? '') === '' || !hash_equals((string) ($stored['challenge_id'] ?? ''), (string) $challenge_id)) {
            self::send_json_error_debug(__('เซสชันการยืนยัน OTP ไม่ตรงกัน กรุณาขอรหัสใหม่', 'lc-public-place-photo-upload'), 400, 'otp_challenge_mismatch', 'otp_verify');
        }
        if ((string) ($stored['otp'] ?? '') !== $otp) {
            $attempts = (int) get_transient($verify_attempt_key);
            $attempts++;
            set_transient($verify_attempt_key, $attempts, self::OTP_VERIFY_LOCK_SECONDS);
            if ($attempts >= self::OTP_VERIFY_MAX_ATTEMPTS) {
                set_transient($verify_lock_key, 1, self::OTP_VERIFY_LOCK_SECONDS);
                delete_transient(self::otp_key($email));
                self::send_json_error_debug(__('กรอก OTP ผิดหลายครั้ง กรุณาขอรหัสใหม่แล้วลองอีกครั้ง', 'lc-public-place-photo-upload'), 429, 'otp_verify_locked', 'otp_verify');
            }
            self::send_json_error_debug(__('OTP ไม่ถูกต้องหรือหมดอายุแล้ว', 'lc-public-place-photo-upload'), 400, 'otp_invalid_or_expired', 'otp_verify');
        }
        delete_transient(self::otp_key($email));
        delete_transient($verify_attempt_key);
        delete_transient($verify_lock_key);

        $target_location_id = $location_id > 0 ? $location_id : (int) ($stored['location_id'] ?? 0);
        $token = self::issue_editor_token($email, $target_location_id);
        self::set_editor_cookie($token);

        wp_send_json_success([
            'logged_in' => true,
            'focus_place_id' => $target_location_id > 0 ? $target_location_id : 0,
        ]);
    }

    public static function ajax_logout_location_edit_session() {
        check_ajax_referer(self::NONCE_ACTION_EDIT_ACCESS, 'nonce');
        $token = self::get_editor_token_from_cookie();
        if ($token !== '') {
            self::clear_editor_session($token);
        } else {
            self::clear_editor_cookie();
        }
        wp_send_json_success([
            'logged_in' => false,
        ]);
    }

    public static function handle_location_edit_landing() {
        if (is_admin()) {
            return;
        }
        $is_editor = isset($_GET['lc_location_edit']) && (string) $_GET['lc_location_edit'] === '1';
        if (!$is_editor) {
            return;
        }

        $settings = self::get_settings();
        if (($settings['location_edit_enabled'] ?? '0') !== '1') {
            wp_die(__('ปิดรับคำขอแก้ไขข้อมูลสถานที่ชั่วคราว', 'lc-public-place-photo-upload'));
        }

        $token_from_query = isset($_GET['token']) ? sanitize_text_field((string) wp_unslash($_GET['token'])) : '';
        $token = $token_from_query !== '' ? $token_from_query : self::get_editor_token_from_cookie();
        $is_logout = isset($_GET['lc_location_logout']) && (string) $_GET['lc_location_logout'] === '1';
        if ($is_logout) {
            $logout_nonce = isset($_GET['_lc_logout_nonce']) ? sanitize_text_field((string) wp_unslash($_GET['_lc_logout_nonce'])) : '';
            if (!self::is_valid_logout_nonce($logout_nonce)) {
                wp_die(__('Security check failed.', 'lc-public-place-photo-upload'));
            }
            self::clear_editor_session($token);
            wp_safe_redirect(home_url('/'));
            exit;
        }
        $session = self::get_editor_session($token);
        if (!$session) {
            wp_die(__('เซสชันหมดอายุ กรุณาขอ OTP ใหม่อีกครั้ง', 'lc-public-place-photo-upload'));
        }
        // Migrate old token-in-query flows to secure cookie-based session.
        if ($token_from_query !== '') {
            self::set_editor_cookie($token_from_query);
            $redirect_args = $_GET;
            unset($redirect_args['token']);
            $redirect_args['lc_location_edit'] = 1;
            $safe_args = [];
            foreach ((array) $redirect_args as $k => $v) {
                if (!is_scalar($v)) {
                    continue;
                }
                $safe_args[sanitize_key((string) $k)] = sanitize_text_field((string) wp_unslash($v));
            }
            wp_safe_redirect(add_query_arg($safe_args, home_url('/')));
            exit;
        }
        $mode = isset($_GET['mode']) ? sanitize_key((string) wp_unslash($_GET['mode'])) : '';
        if ($mode === 'pick') {
            self::render_location_picker_page($token);
        }

        $location_id = isset($_GET['place_id']) ? (int) $_GET['place_id'] : 0;
        $focus_location_id = isset($_GET['focus_place_id']) ? (int) $_GET['focus_place_id'] : 0;
        if ($location_id <= 0) {
            self::render_requester_dashboard($token, (string) ($session['email'] ?? ''), $focus_location_id);
        }
        if (get_post_type($location_id) !== 'location') {
            self::render_requester_dashboard($token, (string) ($session['email'] ?? ''), $focus_location_id);
        }

        $meta = get_post_meta($location_id);
        $requester_email = sanitize_email((string) ($session['email'] ?? ''));
        $latest_request_id = self::get_latest_request_for_requester($location_id, $requester_email);
        $latest_status = '';
        $latest_reason = '';
        $latest_moderated_at = '';
        if ($latest_request_id > 0) {
            $latest_status = (string) get_post_meta($latest_request_id, '_lc_change_status', true);
            if ($latest_status === '') {
                $latest_status = 'pending';
            }
            $latest_reason = (string) get_post_meta($latest_request_id, '_lc_reject_reason', true);
            $latest_moderated_at = (string) get_post_meta($latest_request_id, '_lc_moderated_at', true);
        }
        $gallery_meta_key = (string) ($settings['location_gallery_meta_key'] ?? 'images');
        $images = self::parse_gallery_ids(get_post_meta($location_id, $gallery_meta_key, true));
        if ($gallery_meta_key !== 'images') {
            // Backward compatibility: some locations may still keep gallery in legacy `images` key.
            $legacy_images = self::parse_gallery_ids(get_post_meta($location_id, 'images', true));
            if (!empty($legacy_images)) {
                $images = array_values(array_unique(array_merge($images, $legacy_images)));
            }
        }

        $session_ids = get_posts([
            'post_type' => 'session',
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                ['key' => 'location', 'value' => (string) $location_id, 'compare' => '='],
                ['key' => 'location', 'value' => '"' . (string) $location_id . '"', 'compare' => 'LIKE'],
            ],
        ]);

        status_header(200);
        nocache_headers();
        ?>
        <!doctype html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html__('แก้ไขข้อมูลสถานที่', 'lc-public-place-photo-upload'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class('lc-location-edit-request-page'); ?>>
            <style>
            .lc-edit-shell{max-width:1120px;margin:20px auto 28px;padding:0 16px}
            .lc-edit-hero{background:linear-gradient(180deg,#f0fdf4 0%,#ffffff 100%);border:1px solid #bbf7d0;border-radius:16px;padding:16px 18px;margin-bottom:14px}
            .lc-edit-title{margin:0 0 6px;font-size:30px;line-height:1.2;font-weight:800;color:#022c22}
            .lc-edit-sub{margin:0;color:#166534}
            .lc-edit-ok{margin-top:10px;padding:10px 12px;border-radius:10px;background:#ecfdf3;border:1px solid #86efac;color:#166534;font-weight:600}
            .lc-edit-form{display:grid;gap:14px}
            .lc-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px}
            .lc-card h2{margin:0 0 10px;font-size:20px;color:#0f172a}
            .lc-muted{margin:0 0 10px;color:#64748b}
            .lc-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
            .lc-field{display:flex;flex-direction:column;gap:6px}
            .lc-field label{font-weight:600;color:#0f172a}
            .lc-field input,.lc-field textarea,.lc-field select{width:100%;height:42px;border:1px solid #cbd5e1;border-radius:10px;padding:8px 10px;box-sizing:border-box}
            .lc-field textarea{height:auto;min-height:92px;resize:vertical}
            .lc-img-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px}
            .lc-remove-image-item{border:1px solid #e2e8f0;border-radius:12px;padding:8px;background:#fff;transition:.15s ease}
            .lc-remove-image-item.is-selected{border-color:#dc2626;background:#fef2f2}
            .lc-remove-image-item img{width:100%;height:96px;object-fit:cover;border-radius:8px;display:block}
            .lc-remove-image-btn{width:100%;height:34px;border-radius:8px;border:1px solid #dc2626;background:#fff;color:#dc2626;font-weight:700;cursor:pointer}
            .lc-remove-image-item.is-selected .lc-remove-image-btn{background:#dc2626;color:#fff}
            .lc-session{border:1px solid #e2e8f0;border-radius:12px;padding:10px 12px;margin-bottom:10px;background:#fff}
            .lc-session summary{cursor:pointer;font-weight:700;color:#0f172a;list-style:none}
            .lc-session summary::-webkit-details-marker{display:none}
            .lc-session-title{display:flex;justify-content:space-between;gap:8px;align-items:center}
            .lc-session-badge{font-size:12px;color:#334155;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:2px 8px}
            .lc-sticky-actions{position:sticky;bottom:12px;z-index:10;background:#ffffffd9;backdrop-filter:blur(4px);border:1px solid #d1fae5;border-radius:12px;padding:10px;display:flex;justify-content:space-between;align-items:center;gap:10px}
            .lc-sticky-note{color:#065f46;font-size:13px}
            .lc-submit{height:44px;border:none;border-radius:10px;background:#00744b;color:#fff;font-weight:800;padding:0 20px;cursor:pointer}
            .lc-submit:hover{background:#05603f}
            @media (max-width: 860px){
              .lc-grid-2{grid-template-columns:1fr}
              .lc-edit-title{font-size:26px}
              .lc-sticky-actions{flex-direction:column;align-items:stretch}
              .lc-submit{width:100%}
            }
            </style>

            <main class="lc-edit-shell">
                <div class="lc-edit-hero">
                    <h1 class="lc-edit-title"><?php echo esc_html__('แก้ไขข้อมูลสถานที่', 'lc-public-place-photo-upload'); ?></h1>
                    <p class="lc-edit-sub"><?php echo esc_html__('ข้อมูลที่ส่งจะเข้าสู่คิวตรวจสอบก่อนอัปเดตบนเว็บไซต์จริง', 'lc-public-place-photo-upload'); ?></p>
                    <?php if ($latest_request_id > 0) : ?>
                        <div style="margin-top:10px;padding:10px 12px;border-radius:10px;border:1px solid <?php echo esc_attr($latest_status === 'approved' ? '#86efac' : ($latest_status === 'rejected' ? '#fecaca' : ($latest_status === 'cancelled' ? '#cbd5e1' : '#fde68a'))); ?>;background:<?php echo esc_attr($latest_status === 'approved' ? '#f0fdf4' : ($latest_status === 'rejected' ? '#fef2f2' : ($latest_status === 'cancelled' ? '#f8fafc' : '#fffbeb'))); ?>;color:#111827;">
                            <strong>สถานะคำขอล่าสุด:</strong> <?php echo esc_html(self::status_label_th($latest_status)); ?>
                            <?php if ($latest_moderated_at !== '') : ?>
                                <span style="opacity:.8;">(<?php echo esc_html($latest_moderated_at); ?>)</span>
                            <?php endif; ?>
                            <?php if ($latest_status === 'rejected' && $latest_reason !== '') : ?>
                                <div style="margin-top:6px;"><strong>เหตุผลที่ไม่อนุมัติ:</strong> <?php echo esc_html($latest_reason); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['lc_change_sent']) && (string) $_GET['lc_change_sent'] === '1') : ?>
                        <div class="lc-edit-ok"><?php echo esc_html__('ส่งคำขอเรียบร้อยแล้ว ทีมงานจะตรวจสอบและอนุมัติข้อมูลให้', 'lc-public-place-photo-upload'); ?></div>
                    <?php endif; ?>
                    <div style="margin-top:8px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                        <a href="<?php echo esc_url(add_query_arg(['lc_location_edit' => 1, 'req_kind' => 'edit'], home_url('/'))); ?>" style="color:#0f766e;font-weight:700;text-decoration:none;">&larr; กลับไปหน้าคำขอของฉัน</a>
                        <a href="<?php echo esc_url(add_query_arg(['lc_location_edit' => 1, 'lc_location_logout' => 1, '_lc_logout_nonce' => self::current_logout_nonce()], home_url('/'))); ?>" style="color:#7f1d1d;font-weight:700;text-decoration:none;">ออกจากระบบ</a>
                    </div>
                </div>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="lc-edit-form">
                    <input type="hidden" name="action" value="lc_submit_location_edit_request">
                    <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                    <input type="hidden" name="location_id" value="<?php echo esc_attr((string) $location_id); ?>">
                    <?php wp_nonce_field(self::NONCE_ACTION_EDIT_SUBMIT, '_lc_location_edit_nonce'); ?>

                    <section class="lc-card">
                        <h2><?php echo esc_html__('ข้อมูลสถานที่', 'lc-public-place-photo-upload'); ?></h2>
                        <div class="lc-grid-2">
                            <div class="lc-field" style="grid-column:1/-1;">
                                <label><?php echo esc_html__('ชื่อสถานที่', 'lc-public-place-photo-upload'); ?></label>
                                <input type="text" name="location_title" value="<?php echo esc_attr(get_the_title($location_id)); ?>">
                            </div>
                            <div class="lc-field" style="grid-column:1/-1;">
                                <label><?php echo esc_html__('ที่อยู่', 'lc-public-place-photo-upload'); ?></label>
                                <textarea name="address" rows="2"><?php echo esc_textarea((string) ($meta['address'][0] ?? '')); ?></textarea>
                            </div>
                            <div class="lc-field">
                                <label><?php echo esc_html__('เบอร์โทร', 'lc-public-place-photo-upload'); ?></label>
                                <input type="text" name="phone" value="<?php echo esc_attr((string) ($meta['phone'][0] ?? '')); ?>">
                            </div>
                            <div class="lc-field">
                                <label><?php echo esc_html__('เวลาทำการ', 'lc-public-place-photo-upload'); ?></label>
                                <input type="text" name="opening_hours" value="<?php echo esc_attr((string) (($meta['opening_hours'][0] ?? '') ?: ($meta['hours'][0] ?? ''))); ?>">
                            </div>
                            <div class="lc-field" style="grid-column:1/-1;">
                                <label><?php echo esc_html__('คำอธิบาย', 'lc-public-place-photo-upload'); ?></label>
                                <textarea name="description" rows="4"><?php echo esc_textarea((string) ($meta['description'][0] ?? '')); ?></textarea>
                            </div>
                            <div class="lc-field">
                                <label>ลิงก์ Google Maps</label>
                                <input type="url" name="google_maps" value="<?php echo esc_attr((string) ($meta['google_maps'][0] ?? '')); ?>">
                            </div>
                            <div class="lc-field">
                                <label>ลิงก์ Facebook</label>
                                <input type="url" name="facebook" value="<?php echo esc_attr((string) ($meta['facebook'][0] ?? '')); ?>">
                            </div>
                        </div>
                    </section>

                    <section class="lc-card">
                        <h2><?php echo esc_html__('รูปภาพสถานที่', 'lc-public-place-photo-upload'); ?></h2>
                        <p class="lc-muted"><?php echo esc_html__('กดปุ่ม “ลบรูปนี้” เพื่อนำรูปออกจากสถานที่ และสามารถเพิ่มรูปใหม่ได้ในคำขอเดียวกัน', 'lc-public-place-photo-upload'); ?></p>
                        <div id="lcRemoveImageCount" class="lc-muted">ยังไม่เลือกรูปลบ</div>
                        <div class="lc-img-grid">
                        <?php foreach ($images as $img_id): ?>
                            <div class="lc-remove-image-item" data-image-id="<?php echo esc_attr((string) $img_id); ?>">
                                <input class="lc-remove-image-checkbox" type="checkbox" name="remove_image_ids[]" value="<?php echo esc_attr((string) $img_id); ?>" style="display:none;">
                                <?php echo wp_get_attachment_image((int) $img_id, 'thumbnail'); ?>
                                <div style="font-size:12px;color:#64748b;margin:6px 0;">#<?php echo esc_html((string) $img_id); ?></div>
                                <button type="button" class="lc-remove-image-btn">ลบรูปนี้</button>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <div class="lc-field" style="margin-top:12px;">
                            <label><?php echo esc_html__('เพิ่มรูปใหม่', 'lc-public-place-photo-upload'); ?></label>
                            <input type="file" name="new_images[]" multiple accept="image/jpeg,image/png,image/webp" style="height:auto;padding:8px;">
                        </div>
                    </section>

                    <section class="lc-card">
                        <h2><?php echo esc_html__('ข้อมูล Session', 'lc-public-place-photo-upload'); ?></h2>
                        <?php if (empty($session_ids)): ?>
                            <p class="lc-muted"><?php echo esc_html__('ไม่พบ session ของสถานที่นี้', 'lc-public-place-photo-upload'); ?></p>
                        <?php else: ?>
                            <?php foreach ($session_ids as $sid): ?>
                                <details class="lc-session">
                                    <summary class="lc-session-title">
                                        <span><?php echo esc_html(get_the_title((int) $sid)); ?></span>
                                        <span class="lc-session-badge">#<?php echo esc_html((string) $sid); ?></span>
                                    </summary>
                                    <input type="hidden" name="session_ids[]" value="<?php echo esc_attr((string) $sid); ?>">
                                    <div class="lc-grid-2" style="margin-top:10px;">
                                        <div class="lc-field">
                                            <label>ช่วงเวลาเรียน (time_period)</label>
                                            <input type="text" name="session_time_period[<?php echo esc_attr((string) $sid); ?>]" value="<?php echo esc_attr((string) get_post_meta((int) $sid, 'time_period', true)); ?>">
                                        </div>
                                        <div class="lc-field">
                                            <label>ลิงก์สมัคร (apply_url)</label>
                                            <input type="url" name="session_apply_url[<?php echo esc_attr((string) $sid); ?>]" value="<?php echo esc_attr((string) get_post_meta((int) $sid, 'apply_url', true)); ?>">
                                        </div>
                                        <div class="lc-field">
                                            <label>วันเริ่ม (start_date)</label>
                                            <input type="date" name="session_start_date[<?php echo esc_attr((string) $sid); ?>]" value="<?php echo esc_attr((string) get_post_meta((int) $sid, 'start_date', true)); ?>">
                                        </div>
                                        <div class="lc-field">
                                            <label>วันสิ้นสุด (end_date)</label>
                                            <input type="date" name="session_end_date[<?php echo esc_attr((string) $sid); ?>]" value="<?php echo esc_attr((string) get_post_meta((int) $sid, 'end_date', true)); ?>">
                                        </div>
                                        <div class="lc-field">
                                            <label>วันเปิดรับสมัคร (reg_start)</label>
                                            <input type="date" name="session_reg_start[<?php echo esc_attr((string) $sid); ?>]" value="<?php echo esc_attr((string) get_post_meta((int) $sid, 'reg_start', true)); ?>">
                                        </div>
                                        <div class="lc-field">
                                            <label>วันปิดรับสมัคร (reg_end)</label>
                                            <input type="date" name="session_reg_end[<?php echo esc_attr((string) $sid); ?>]" value="<?php echo esc_attr((string) get_post_meta((int) $sid, 'reg_end', true)); ?>">
                                        </div>
                                        <div class="lc-field" style="grid-column:1/-1;">
                                            <label>รายละเอียดเซสชัน (session_details)</label>
                                            <textarea rows="3" name="session_details[<?php echo esc_attr((string) $sid); ?>]"><?php echo esc_textarea((string) get_post_meta((int) $sid, 'session_details', true)); ?></textarea>
                                        </div>
                                    </div>
                                </details>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>

                    <section class="lc-card">
                        <h2><?php echo esc_html__('ส่งข้อความถึงแอดมิน', 'lc-public-place-photo-upload'); ?></h2>
                        <div class="lc-field" style="margin-top:8px;">
                            <label><?php echo esc_html__('ข้อความเพิ่มเติมสำหรับทีมตรวจสอบ (ไม่บังคับ)', 'lc-public-place-photo-upload'); ?></label>
                            <textarea name="request_note" rows="4" placeholder="<?php echo esc_attr__('เช่น ขอเร่งตรวจสอบ, เหตุผลการเปลี่ยนแปลง, รายละเอียดที่ควรทราบเพิ่มเติม', 'lc-public-place-photo-upload'); ?>"></textarea>
                        </div>
                    </section>

                    <div class="lc-sticky-actions">
                        <div class="lc-sticky-note"><?php echo esc_html__('เมื่อส่งแล้ว แอดมินจะตรวจสอบก่อนอัปเดตข้อมูลจริง', 'lc-public-place-photo-upload'); ?></div>
                        <button type="submit" class="lc-submit"><?php echo esc_html__('ส่งคำขอแก้ไข', 'lc-public-place-photo-upload'); ?></button>
                    </div>
                </form>
            </main>
            <script>
            (function() {
                const items = Array.from(document.querySelectorAll('.lc-remove-image-item'));
                const counter = document.getElementById('lcRemoveImageCount');
                if (!counter) return;

                const syncCount = () => {
                    let selected = 0;
                    items.forEach((item) => {
                        const cb = item.querySelector('.lc-remove-image-checkbox');
                        const btn = item.querySelector('.lc-remove-image-btn');
                        if (!cb || !btn) return;
                        if (cb.checked) {
                            item.classList.add('is-selected');
                            btn.textContent = 'ยกเลิกลบรูป';
                            selected += 1;
                        } else {
                            item.classList.remove('is-selected');
                            btn.textContent = 'ลบรูปนี้';
                        }
                    });
                    counter.textContent = selected > 0 ? ('เลือกลบแล้ว ' + selected + ' รูป') : 'ยังไม่เลือกรูปลบ';
                };

                items.forEach((item) => {
                    const btn = item.querySelector('.lc-remove-image-btn');
                    const cb = item.querySelector('.lc-remove-image-checkbox');
                    if (!btn || !cb) return;
                    btn.addEventListener('click', () => {
                        cb.checked = !cb.checked;
                        syncCount();
                    });
                });
                syncCount();
            })();
            </script>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    public static function handle_public_submission_direct_endpoint() {
        if (is_admin()) {
            return;
        }
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }
        $is_target = isset($_GET['lc_place_photo_submit']) && (string) $_GET['lc_place_photo_submit'] === '1';
        if (!$is_target) {
            return;
        }

        $response = self::handle_public_submission_rest(null);
        if ($response instanceof WP_REST_Response) {
            wp_send_json($response->get_data(), $response->get_status());
        }

        wp_send_json([
            'success' => false,
            'data' => [
                'code' => 'upload_failed',
                'message' => self::status_message('upload_failed'),
            ],
        ], 500);
    }

    public static function register_rest_routes() {
        register_rest_route('lc-public-upload/v1', '/submit', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'handle_public_submission_rest'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle_public_upload_landing() {
        if (is_admin()) {
            return;
        }

        $is_upload_page = isset($_GET['lc_place_photo_upload']) && (string) $_GET['lc_place_photo_upload'] === '1';
        if (!$is_upload_page) {
            return;
        }

        status_header(200);
        nocache_headers();
        ?>
        <!doctype html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html__('Upload Place Photos', 'lc-public-place-photo-upload'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class('lc-place-photo-upload-page'); ?>>
            <main style="max-width:760px;margin:40px auto;padding:0 16px;">
                <h1 style="margin-bottom:16px;"><?php echo esc_html__('Upload Place Photos', 'lc-public-place-photo-upload'); ?></h1>
                <?php echo self::render_upload_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </main>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    private static function normalize_uploaded_files($files) {
        $normalized = [];

        if (empty($files) || !is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
            return $normalized;
        }

        foreach ($files['name'] as $index => $name) {
            $error = isset($files['error'][$index]) ? (int) $files['error'][$index] : UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'type' => isset($files['type'][$index]) ? $files['type'][$index] : '',
                'tmp_name' => isset($files['tmp_name'][$index]) ? $files['tmp_name'][$index] : '',
                'error' => $error,
                'size' => isset($files['size'][$index]) ? (int) $files['size'][$index] : 0,
            ];
        }

        return $normalized;
    }

    private static function file_error_to_status($error_code) {
        $error_code = (int) $error_code;
        if (in_array($error_code, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return 'file_too_large';
        }
        if ($error_code === UPLOAD_ERR_PARTIAL || $error_code === UPLOAD_ERR_CANT_WRITE || $error_code === UPLOAD_ERR_EXTENSION) {
            return 'upload_failed';
        }
        return 'upload_failed';
    }

    private static function staff_allowed_image_mimes() {
        return [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];
    }

    private static function upload_error_message_th($error_code) {
        $error_code = (int) $error_code;
        if ($error_code === UPLOAD_ERR_INI_SIZE || $error_code === UPLOAD_ERR_FORM_SIZE) {
            return 'ไฟล์มีขนาดใหญ่เกินที่ระบบอนุญาต';
        }
        if ($error_code === UPLOAD_ERR_PARTIAL) {
            return 'ไฟล์อัปโหลดไม่ครบถ้วน';
        }
        if ($error_code === UPLOAD_ERR_NO_FILE) {
            return 'ไม่พบไฟล์ที่อัปโหลด';
        }
        if ($error_code === UPLOAD_ERR_NO_TMP_DIR) {
            return 'ระบบไม่มีโฟลเดอร์ชั่วคราวสำหรับอัปโหลด';
        }
        if ($error_code === UPLOAD_ERR_CANT_WRITE) {
            return 'ระบบไม่สามารถเขียนไฟล์ลงดิสก์ได้';
        }
        if ($error_code === UPLOAD_ERR_EXTENSION) {
            return 'อัปโหลดถูกหยุดโดยส่วนขยายของระบบ';
        }
        return 'อัปโหลดไฟล์ไม่สำเร็จ';
    }

    private static function validate_staff_edit_image_files($files, $settings) {
        $normalized = self::normalize_uploaded_files($files);
        if (empty($normalized)) {
            return [];
        }

        $max_files = max(1, (int) ($settings['max_files_per_submission'] ?? 6));
        $max_size_mb = max(1, (int) ($settings['max_file_size_mb'] ?? 8));
        $max_size_bytes = $max_size_mb * 1024 * 1024;
        if (count($normalized) > $max_files) {
            return new WP_Error(
                'too_many_files',
                sprintf('อัปโหลดรูปได้สูงสุด %d รูปต่อคำขอ (ตั้งค่าในหลังบ้าน: Max Files Per Submission)', $max_files)
            );
        }

        $allowed_mimes = self::staff_allowed_image_mimes();
        foreach ($normalized as $file) {
            $name = sanitize_file_name((string) ($file['name'] ?? ''));
            $label = $name !== '' ? ('"' . $name . '"') : 'ไฟล์ที่อัปโหลด';
            $error_code = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_OK;
            if ($error_code !== UPLOAD_ERR_OK) {
                return new WP_Error('upload_failed', $label . ' ' . self::upload_error_message_th($error_code));
            }
            $size = isset($file['size']) ? (int) $file['size'] : 0;
            if ($size <= 0) {
                return new WP_Error('bad_file', $label . ' ไม่ถูกต้องหรือไม่มีข้อมูล');
            }
            if ($size > $max_size_bytes) {
                return new WP_Error('file_too_large', sprintf('%s มีขนาดเกิน %d MB', $label, $max_size_mb));
            }
            $tmp_name = (string) ($file['tmp_name'] ?? '');
            $filename = (string) ($file['name'] ?? '');
            $type_check = wp_check_filetype_and_ext($tmp_name, $filename, $allowed_mimes);
            if (empty($type_check['ext']) || empty($type_check['type'])) {
                return new WP_Error('bad_file', $label . ' ไม่รองรับไฟล์ประเภทนี้ (รองรับเฉพาะ JPG, PNG, WebP)');
            }
        }

        return $normalized;
    }

    private static function normalize_base64_images_payload($raw_payload) {
        if (is_string($raw_payload) && $raw_payload !== '') {
            $decoded = json_decode(wp_unslash($raw_payload), true);
            if (is_array($decoded)) {
                return $decoded;
            }
            return [];
        }
        if (is_array($raw_payload)) {
            return $raw_payload;
        }
        return [];
    }

    public static function disable_intermediate_image_sizes($sizes) {
        return [];
    }

    public static function keep_only_thumbnail_size($sizes) {
        if (!is_array($sizes) || empty($sizes)) {
            return [];
        }
        return isset($sizes['thumbnail']) ? ['thumbnail' => $sizes['thumbnail']] : [];
    }

    private static function generate_minimal_attachment_metadata($attachment_id, $file_path) {
        $attachment_id = (int) $attachment_id;
        $file_path = (string) $file_path;
        if ($attachment_id <= 0 || $file_path === '') {
            return;
        }

        if (!file_exists($file_path)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        add_filter('intermediate_image_sizes_advanced', [__CLASS__, 'keep_only_thumbnail_size'], 9999);
        $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        remove_filter('intermediate_image_sizes_advanced', [__CLASS__, 'keep_only_thumbnail_size'], 9999);

        if (is_array($metadata) && !empty($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }
    }

    private static function fast_insert_attachment_from_binary($filename, $binary, $detected_mime, $submission_id, $allowed_mimes) {
        $filename = sanitize_file_name((string) $filename);
        if ($filename === '') {
            $filename = 'upload.jpg';
        }

        if (!is_string($binary) || $binary === '') {
            return new WP_Error('upload_failed', 'Empty file payload.');
        }

        $upload = wp_upload_bits($filename, null, $binary);
        if (!empty($upload['error'])) {
            return new WP_Error('upload_failed', (string) $upload['error']);
        }

        $allowed_types = array_values((array) $allowed_mimes);
        $wp_filetype = wp_check_filetype((string) $upload['file']);
        $mime = $detected_mime ?: (string) ($wp_filetype['type'] ?? '');
        if ($mime === '' || !in_array($mime, $allowed_types, true)) {
            @unlink((string) $upload['file']);
            return new WP_Error('bad_file', 'Unsupported file type.');
        }

        $attachment_id = wp_insert_attachment([
            'post_mime_type' => $mime,
            'post_title' => preg_replace('/\.[^.]+$/', '', basename((string) $upload['file'])),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => (int) $submission_id,
        ], (string) $upload['file'], (int) $submission_id, true);

        if (is_wp_error($attachment_id) || !$attachment_id) {
            @unlink((string) $upload['file']);
            return is_wp_error($attachment_id) ? $attachment_id : new WP_Error('upload_failed', 'Attachment insert failed.');
        }

        self::generate_minimal_attachment_metadata((int) $attachment_id, (string) $upload['file']);
        return (int) $attachment_id;
    }

    private static function fast_insert_attachment_from_uploaded_file($file, $submission_id, $allowed_mimes) {
        if (!is_array($file) || empty($file['tmp_name']) || empty($file['name'])) {
            return new WP_Error('upload_failed', 'Invalid uploaded file.');
        }

        $tmp_name = (string) $file['tmp_name'];
        $filename = (string) $file['name'];
        $type_check = wp_check_filetype_and_ext($tmp_name, $filename, $allowed_mimes);
        if (empty($type_check['ext']) || empty($type_check['type'])) {
            return new WP_Error('bad_file', 'Unsupported file type.');
        }

        $binary = @file_get_contents($tmp_name);
        if (!is_string($binary) || $binary === '') {
            return new WP_Error('upload_failed', 'Cannot read uploaded file.');
        }

        return self::fast_insert_attachment_from_binary($filename, $binary, (string) $type_check['type'], $submission_id, $allowed_mimes);
    }

    private static function build_photo_credit_text($uploader_name, $uploader_email) {
        $name = sanitize_text_field((string) $uploader_name);
        if ($name === '') {
            return '';
        }
        return $name;
    }

    private static function apply_credit_to_attachment($attachment_id, $uploader_name, $uploader_email) {
        $attachment_id = (int) $attachment_id;
        if ($attachment_id <= 0) {
            return;
        }

        $alt_text = self::build_photo_credit_text($uploader_name, $uploader_email);
        if ($alt_text === '') {
            return;
        }

        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return;
        }

        $current_caption = (string) $attachment->post_excerpt;
        $current_description = (string) $attachment->post_content;
        if ($current_caption === $alt_text && $current_description === $alt_text) {
            return;
        }

        wp_update_post([
            'ID' => $attachment_id,
            'post_excerpt' => $alt_text,
            'post_content' => $alt_text,
        ]);
        update_post_meta($attachment_id, '_lc_photo_credit_name', sanitize_text_field((string) $uploader_name));
    }

    private static function upload_base64_images($images_payload, $submission_id, $allowed_mimes, $max_size_bytes, $uploader_name = '', $uploader_email = '') {
        $attachment_ids = [];
        $errors = [];

        if (!is_array($images_payload) || empty($images_payload)) {
            return ['ids' => [], 'errors' => ['no_images']];
        }

        foreach ($images_payload as $item) {
            if (!is_array($item)) {
                $errors[] = 'bad_file';
                continue;
            }

            $name = sanitize_file_name((string) ($item['name'] ?? 'upload.jpg'));
            $mime = sanitize_text_field((string) ($item['type'] ?? ''));
            $data_url = (string) ($item['data'] ?? '');
            if ($name === '' || $data_url === '') {
                $errors[] = 'bad_file';
                continue;
            }

            if (strpos($data_url, 'base64,') === false) {
                $errors[] = 'bad_file';
                continue;
            }

            $parts = explode('base64,', $data_url, 2);
            $binary = base64_decode($parts[1], true);
            if ($binary === false || $binary === '') {
                $errors[] = 'bad_file';
                continue;
            }

            $size = strlen($binary);
            if ($size <= 0 || $size > $max_size_bytes) {
                $errors[] = 'file_too_large';
                continue;
            }

            $tmp = wp_tempnam($name);
            if (!$tmp || file_put_contents($tmp, $binary) === false) {
                if ($tmp) {
                    @unlink($tmp);
                }
                $errors[] = 'upload_failed';
                continue;
            }

            $type_check = wp_check_filetype_and_ext($tmp, $name, $allowed_mimes);
            @unlink($tmp);
            if (empty($type_check['ext']) || empty($type_check['type'])) {
                $errors[] = 'bad_file';
                continue;
            }

            if ($mime !== '' && $type_check['type'] !== $mime) {
                // Prefer server-detected mime type; do not fail on mismatch.
                $mime = $type_check['type'];
            }

            $attachment_id = self::fast_insert_attachment_from_binary($name, $binary, $mime ?: (string) $type_check['type'], $submission_id, $allowed_mimes);

            if (is_wp_error($attachment_id) || !$attachment_id) {
                if (is_wp_error($attachment_id)) {
                    $errors[] = $attachment_id->get_error_message();
                } else {
                    $errors[] = 'upload_failed';
                }
                continue;
            }

            $attachment_id = (int) $attachment_id;
            $attachment_ids[] = $attachment_id;
            self::apply_credit_to_attachment($attachment_id, $uploader_name, $uploader_email);
        }

        return ['ids' => $attachment_ids, 'errors' => $errors];
    }

    private static function parse_allowed_emails($raw) {
        $raw = (string) $raw;
        if ($raw === '') {
            return [];
        }
        // Accept literal "\n" sequences and repair a legacy separator bug where newlines
        // were serialized as the letter "n" between email addresses.
        $raw = str_replace('\\n', "\n", $raw);
        $raw = preg_replace('/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})n(?=[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/i', '$1' . "\n", $raw);
        $lines = preg_split('/[\r\n,;\s]+/', $raw);
        if (!is_array($lines)) {
            return [];
        }
        $emails = [];
        foreach ($lines as $line) {
            $token = trim((string) $line);
            if ($token === '') {
                continue;
            }
            if ($token === '*') {
                $emails[] = '*';
                continue;
            }
            if (preg_match('/<([^>]+)>/', $token, $m)) {
                $token = (string) ($m[1] ?? $token);
            }
            $email = self::normalize_email_for_match($token);
            if ($email === '') {
                continue;
            }
            if (strpos($email, '@') === false) {
                continue;
            }
            $emails[] = strtolower($email);
        }
        return array_values(array_unique($emails));
    }

    private static function is_email_allowed($email, $settings) {
        $email = self::normalize_email_for_match((string) $email);
        if ($email === '' || !is_email($email)) {
            return false;
        }
        $allowed = self::parse_editor_allowed_emails($settings);
        if (empty($allowed)) {
            return false;
        }
        return in_array($email, $allowed, true);
    }

    private static function get_rate_limit_key($remote_ip) {
        $ip = trim((string) $remote_ip);
        if ($ip === '') {
            $ip = 'unknown';
        }
        return 'lc_place_upload_rate_' . md5($ip);
    }

    private static function is_rate_limited($remote_ip, $max_per_hour) {
        $max_per_hour = max(1, (int) $max_per_hour);
        $key = self::get_rate_limit_key($remote_ip);
        $count = (int) get_transient($key);
        return $count >= $max_per_hour;
    }

    private static function bump_rate_limit($remote_ip) {
        $key = self::get_rate_limit_key($remote_ip);
        $count = (int) get_transient($key);
        $count++;
        set_transient($key, $count, HOUR_IN_SECONDS);
    }

    public static function handle_public_submission() {
        $settings = self::get_settings();

        if ($settings['upload_enabled'] !== '1') {
            self::redirect_with_status('closed');
        }

        $nonce = isset($_POST['_lc_place_photo_nonce']) ? sanitize_text_field((string) $_POST['_lc_place_photo_nonce']) : '';
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION_SUBMIT)) {
            self::redirect_with_status('invalid_nonce');
        }

        $uploader_name = isset($_POST['uploader_name']) ? sanitize_text_field((string) wp_unslash($_POST['uploader_name'])) : '';
        if ($uploader_name === '') {
            self::redirect_with_status('invalid_name');
        }
        $uploader_email = isset($_POST['uploader_email']) ? sanitize_email((string) wp_unslash($_POST['uploader_email'])) : '';
        if ($uploader_email === '' || !is_email($uploader_email)) {
            self::redirect_with_status('invalid_email');
        }
        if (!self::is_email_allowed($uploader_email, $settings)) {
            self::redirect_with_status('email_not_allowed');
        }

        $location_id = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
        if ($location_id <= 0 || get_post_type($location_id) !== 'location' || get_post_status($location_id) !== 'publish') {
            self::redirect_with_status('invalid_location');
        }

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
        if (self::is_rate_limited($remote_ip, (int) $settings['max_submissions_per_hour'])) {
            self::redirect_with_status('rate_limited');
        }

        $files = isset($_FILES['place_images']) ? self::normalize_uploaded_files($_FILES['place_images']) : [];
        if (empty($files)) {
            self::redirect_with_status('no_images');
        }

        $max_files = (int) $settings['max_files_per_submission'];
        if (count($files) > $max_files) {
            self::redirect_with_status('too_many_files');
        }

        $max_size_bytes = (int) $settings['max_file_size_mb'] * 1024 * 1024;
        $allowed_mimes = [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];

        foreach ($files as $file) {
            if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
                self::redirect_with_status(self::file_error_to_status((int) $file['error']));
            }

            if ($file['size'] <= 0 || $file['size'] > $max_size_bytes) {
                self::redirect_with_status('file_too_large');
            }

            $type_check = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);
            if (empty($type_check['ext']) || empty($type_check['type'])) {
                self::redirect_with_status('bad_file');
            }
        }

        $submission_id = wp_insert_post([
            'post_type' => self::CPT,
            'post_status' => 'publish',
            'post_title' => sprintf(
                'Submission - %s - %s',
                $uploader_name,
                current_time('mysql')
            ),
        ], true);

        if (is_wp_error($submission_id) || !$submission_id) {
            self::redirect_with_status('upload_failed');
        }

        update_post_meta($submission_id, '_lc_submission_status', 'pending');
        update_post_meta($submission_id, '_lc_uploader_name', $uploader_name);
        update_post_meta($submission_id, '_lc_uploader_email', $uploader_email);
        update_post_meta($submission_id, '_lc_location_id', $location_id);
        update_post_meta($submission_id, '_lc_submitted_at', current_time('mysql'));

        $attachment_ids = [];
        foreach ($files as $file) {
            $attachment_id = self::fast_insert_attachment_from_uploaded_file($file, $submission_id, $allowed_mimes);
            if (is_wp_error($attachment_id) || !$attachment_id) {
                continue;
            }

            $attachment_id = (int) $attachment_id;
            $attachment_ids[] = $attachment_id;
            self::apply_credit_to_attachment($attachment_id, $uploader_name, $uploader_email);
        }

        if (empty($attachment_ids)) {
            wp_delete_post($submission_id, true);
            self::redirect_with_status('upload_failed');
        }

        update_post_meta($submission_id, '_lc_submission_images', $attachment_ids);
        self::bump_rate_limit($remote_ip);

        self::redirect_with_status('success');
    }

    private static function current_ajax_action_name() {
        $action = isset($_REQUEST['action']) ? sanitize_key((string) wp_unslash($_REQUEST['action'])) : '';
        return $action !== '' ? $action : 'unknown_action';
    }

    private static function make_debug_error_id() {
        try {
            $rand = wp_rand(1000, 9999);
        } catch (Exception $e) {
            $rand = mt_rand(1000, 9999);
        }
        return 'LC-' . gmdate('Ymd-His') . '-' . (string) $rand;
    }

    private static function sanitize_debug_context($context) {
        if (!is_array($context)) {
            return [];
        }
        $out = [];
        foreach ($context as $key => $value) {
            $safe_key = sanitize_key((string) $key);
            if ($safe_key === '') {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $val = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
                if (preg_match('/(token|otp|nonce|cookie|password|secret|api[_-]?key)/i', $safe_key)) {
                    $val = '[redacted]';
                }
                $out[$safe_key] = function_exists('mb_substr') ? mb_substr($val, 0, 200) : substr($val, 0, 200);
                continue;
            }
            if (is_array($value)) {
                $out[$safe_key] = '[array:' . count($value) . ']';
                continue;
            }
            $out[$safe_key] = '[' . gettype($value) . ']';
        }
        return $out;
    }

    private static function build_ajax_debug_payload($code, $http_status = 400, $stage = '', $context = []) {
        $code = sanitize_key((string) $code);
        $http_status = (int) $http_status;
        $stage = sanitize_key((string) $stage);
        if ($stage === '') {
            $stage = 'ajax';
        }
        return [
            'error_id' => self::make_debug_error_id(),
            'code' => $code !== '' ? $code : 'unknown_error',
            'stage' => $stage,
            'action' => self::current_ajax_action_name(),
            'http_status' => $http_status,
            'time_utc' => gmdate('c'),
            'context' => self::sanitize_debug_context($context),
        ];
    }

    private static function error_log_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::ERROR_LOG_TABLE_SUFFIX;
    }

    private static function maybe_create_error_log_table() {
        global $wpdb;
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $table = self::error_log_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at_utc datetime NOT NULL,
            error_id varchar(64) NOT NULL DEFAULT '',
            code varchar(100) NOT NULL DEFAULT '',
            stage varchar(100) NOT NULL DEFAULT '',
            action_name varchar(120) NOT NULL DEFAULT '',
            http_status smallint(5) unsigned NOT NULL DEFAULT 0,
            severity varchar(16) NOT NULL DEFAULT 'error',
            message_user text NULL,
            request_id bigint(20) unsigned NULL,
            location_id bigint(20) unsigned NULL,
            course_id bigint(20) unsigned NULL,
            wp_user_id bigint(20) unsigned NULL,
            is_wp_logged_in tinyint(1) unsigned NOT NULL DEFAULT 0,
            user_email_masked varchar(190) NOT NULL DEFAULT '',
            user_email_hash char(64) NOT NULL DEFAULT '',
            page_url text NULL,
            referer_url text NULL,
            user_agent varchar(255) NOT NULL DEFAULT '',
            ip_hash char(64) NOT NULL DEFAULT '',
            context_json longtext NULL,
            resolved_status varchar(20) NOT NULL DEFAULT 'open',
            PRIMARY KEY  (id),
            KEY error_id (error_id),
            KEY created_at_utc (created_at_utc),
            KEY stage (stage),
            KEY code (code),
            KEY http_status (http_status),
            KEY resolved_status (resolved_status),
            KEY request_id (request_id)
        ) {$charset_collate};";
        dbDelta($sql);
        $ensured = true;
    }

    private static function maybe_extract_int_from_context($context, $keys) {
        if (!is_array($context)) {
            return null;
        }
        foreach ((array) $keys as $key) {
            if (!array_key_exists($key, $context)) {
                continue;
            }
            $val = (int) $context[$key];
            if ($val > 0) {
                return $val;
            }
        }
        return null;
    }

    private static function hash_email_for_log($email) {
        $email = strtolower(trim((string) $email));
        if ($email === '' || !is_email($email)) {
            return '';
        }
        return hash('sha256', wp_salt('auth') . '|' . $email);
    }

    private static function mask_email_for_log($email) {
        $email = strtolower(trim((string) $email));
        if ($email === '' || !is_email($email)) {
            return '';
        }
        $parts = explode('@', $email, 2);
        $local = (string) ($parts[0] ?? '');
        $domain = (string) ($parts[1] ?? '');
        if ($domain === '') {
            return '';
        }
        $local_len = function_exists('mb_strlen') ? mb_strlen($local) : strlen($local);
        $prefix_len = min(3, max(1, $local_len));
        $prefix = function_exists('mb_substr') ? mb_substr($local, 0, $prefix_len) : substr($local, 0, $prefix_len);
        return $prefix . '***@' . $domain;
    }

    private static function current_editor_session_email_for_log() {
        $token = self::get_editor_token_from_cookie();
        if (!is_string($token) || $token === '') {
            return '';
        }
        $session = self::get_editor_session($token);
        if (!is_array($session)) {
            return '';
        }
        $email = sanitize_email((string) ($session['email'] ?? ''));
        return ($email !== '' && is_email($email)) ? $email : '';
    }

    private static function requester_email_from_context_for_log($context) {
        if (!is_array($context)) {
            return '';
        }
        $request_id = isset($context['request_id']) ? (int) $context['request_id'] : 0;
        if ($request_id <= 0) {
            return '';
        }
        $email = sanitize_email((string) get_post_meta($request_id, '_lc_requester_email', true));
        return ($email !== '' && is_email($email)) ? $email : '';
    }

    private static function hash_ip_for_log($ip) {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return '';
        }
        return hash('sha256', wp_salt('nonce') . '|' . $ip);
    }

    private static function severity_from_http_status($http_status) {
        $http_status = (int) $http_status;
        if ($http_status >= 500) {
            return 'critical';
        }
        if ($http_status >= 400) {
            return 'error';
        }
        if ($http_status >= 300) {
            return 'warn';
        }
        return 'info';
    }

    private static function severity_from_error_event($debug_payload = []) {
        $debug = is_array($debug_payload) ? $debug_payload : [];
        $code = sanitize_key((string) ($debug['code'] ?? ''));
        $stage = sanitize_key((string) ($debug['stage'] ?? ''));
        $http_status = (int) ($debug['http_status'] ?? 0);

        if ($http_status >= 500) {
            return 'critical';
        }

        // Expected/normal user-state or validation outcomes.
        $info_codes = [
            'otp_rate_limited',
        ];
        if (in_array($code, $info_codes, true)) {
            return 'info';
        }

        $warn_codes = [
            'invalid_email',
            'otp_invalid_or_expired',
            'otp_verify_locked',
            'image_validation_failed',
            'no_changes_detected',
            'request_cancel_window_expired',
            'invalid_course',
            'invalid_location',
            'missing_suggestion',
            'unsupported_field',
            'location_edit_closed',
            'editor_email_not_allowed',
        ];
        if (in_array($code, $warn_codes, true)) {
            return 'warn';
        }

        // Some 403s are expected auth-state responses during normal UX.
        if ($code === 'session_expired') {
            if (in_array($stage, ['status_dashboard', 'status_feed', 'status_detail', 'editable_locations'], true)) {
                return 'info';
            }
            return 'warn';
        }

        // Security/integrity issues are more serious than generic 4xx validation.
        if (in_array($code, ['otp_challenge_mismatch', 'forbidden_request_owner'], true)) {
            return 'error';
        }

        // Backend/application failures.
        if (in_array($code, ['request_create_failed', 'image_upload_failed'], true)) {
            return 'error';
        }

        return self::severity_from_http_status($http_status);
    }

    private static function should_skip_error_log_event($debug_payload = []) {
        $debug = is_array($debug_payload) ? $debug_payload : [];
        $code = sanitize_key((string) ($debug['code'] ?? ''));
        $stage = sanitize_key((string) ($debug['stage'] ?? ''));
        $http_status = (int) ($debug['http_status'] ?? 0);

        // Expected auth-state probes from frontend should not pollute error logs.
        if ($http_status === 403 && $code === 'session_expired' && in_array($stage, ['status_dashboard', 'status_feed', 'status_detail'], true)) {
            return true;
        }

        return false;
    }

    private static function log_ajax_error_event($message, $debug_payload = []) {
        global $wpdb;
        try {
            if (self::should_skip_error_log_event($debug_payload)) {
                return;
            }
            self::maybe_create_error_log_table();
            $table = self::error_log_table_name();
            $debug = is_array($debug_payload) ? $debug_payload : [];
            $context = (isset($debug['context']) && is_array($debug['context'])) ? $debug['context'] : [];

            $posted_email = '';
            if (isset($_POST['email'])) {
                $posted_email = sanitize_email((string) wp_unslash($_POST['email']));
            }
            if ($posted_email === '' && isset($context['email'])) {
                $posted_email = sanitize_email((string) $context['email']);
            }
            if ($posted_email === '') {
                $posted_email = self::current_editor_session_email_for_log();
            }
            if ($posted_email === '') {
                $posted_email = self::requester_email_from_context_for_log($context);
            }

            $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
            $request_uri = $request_uri !== '' ? esc_url_raw($request_uri) : '';
            $referer = wp_get_referer();
            $referer = $referer ? esc_url_raw((string) $referer) : '';
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field((string) wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
            $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

            $wpdb->insert($table, [
                'created_at_utc' => gmdate('Y-m-d H:i:s'),
                'error_id' => sanitize_text_field((string) ($debug['error_id'] ?? '')),
                'code' => sanitize_key((string) ($debug['code'] ?? '')),
                'stage' => sanitize_key((string) ($debug['stage'] ?? 'ajax')),
                'action_name' => sanitize_key((string) ($debug['action'] ?? '')),
                'http_status' => (int) ($debug['http_status'] ?? 0),
                'severity' => self::severity_from_error_event($debug),
                'message_user' => wp_strip_all_tags((string) $message),
                'request_id' => self::maybe_extract_int_from_context($context, ['request_id']),
                'location_id' => self::maybe_extract_int_from_context($context, ['location_id']),
                'course_id' => self::maybe_extract_int_from_context($context, ['course_id']),
                'wp_user_id' => get_current_user_id() ?: null,
                'is_wp_logged_in' => is_user_logged_in() ? 1 : 0,
                'user_email_masked' => self::mask_email_for_log($posted_email),
                'user_email_hash' => self::hash_email_for_log($posted_email),
                'page_url' => $request_uri,
                'referer_url' => $referer,
                'user_agent' => function_exists('mb_substr') ? mb_substr($ua, 0, 255) : substr($ua, 0, 255),
                'ip_hash' => self::hash_ip_for_log($remote_ip),
                'context_json' => wp_json_encode($context),
                'resolved_status' => 'open',
            ], [
                '%s','%s','%s','%s','%s','%d','%s','%s',
                '%d','%d','%d','%d','%d','%s','%s','%s','%s','%s','%s','%s'
            ]);
        } catch (\Throwable $e) {
            // Never break AJAX error responses because error logging fails.
        } catch (\Exception $e) {
            // PHP < 7 compatibility path.
        }
    }

    private static function send_json_error_debug($message, $http_status = 400, $code = 'error', $stage = 'ajax', $context = []) {
        $http_status = (int) $http_status;
        $code = (string) $code;
        $debug = self::build_ajax_debug_payload($code, $http_status, $stage, $context);
        self::log_ajax_error_event($message, $debug);
        wp_send_json_error([
            'code' => sanitize_key($code),
            'message' => (string) $message,
            'debug' => $debug,
        ], $http_status);
    }

    private static function ajax_error($code, $http_status = 400, $stage = 'ajax', $context = []) {
        self::send_json_error_debug(self::status_message((string) $code), (int) $http_status, (string) $code, (string) $stage, (array) $context);
    }

    public static function handle_public_submission_ajax() {
        // Route is protected by reCAPTCHA, strict file validation, and rate limiting.
        // Nonce is intentionally not required here to avoid cache-related false negatives.

        $settings = self::get_settings();
        if ($settings['upload_enabled'] !== '1') {
            self::ajax_error('closed', 403);
        }

        $uploader_name = isset($_POST['uploader_name']) ? sanitize_text_field((string) wp_unslash($_POST['uploader_name'])) : '';
        if ($uploader_name === '') {
            self::ajax_error('invalid_name', 400);
        }
        $uploader_email = isset($_POST['uploader_email']) ? sanitize_email((string) wp_unslash($_POST['uploader_email'])) : '';
        if ($uploader_email === '' || !is_email($uploader_email)) {
            self::ajax_error('invalid_email', 400);
        }
        if (!self::is_email_allowed($uploader_email, $settings)) {
            self::ajax_error('email_not_allowed', 403);
        }

        $location_id = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
        if ($location_id <= 0 || get_post_type($location_id) !== 'location' || get_post_status($location_id) !== 'publish') {
            self::ajax_error('invalid_location', 400);
        }

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
        if (self::is_rate_limited($remote_ip, (int) $settings['max_submissions_per_hour'])) {
            self::ajax_error('rate_limited', 429);
        }

        $max_files = (int) $settings['max_files_per_submission'];
        $max_size_bytes = (int) $settings['max_file_size_mb'] * 1024 * 1024;
        $allowed_mimes = [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];

        $files = isset($_FILES['place_images']) ? self::normalize_uploaded_files($_FILES['place_images']) : [];
        $images_base64 = self::normalize_base64_images_payload($_POST['images_base64'] ?? '');
        $incoming_count = !empty($files) ? count($files) : count($images_base64);
        if ($incoming_count <= 0) {
            self::ajax_error('no_images', 400);
        }
        if ($incoming_count > $max_files) {
            self::ajax_error('too_many_files', 400);
        }

        if (!empty($files)) {
            foreach ($files as $file) {
                if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
                    self::ajax_error(self::file_error_to_status((int) $file['error']), 400);
                }
                if ($file['size'] <= 0 || $file['size'] > $max_size_bytes) {
                    self::ajax_error('file_too_large', 400);
                }
                $type_check = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);
                if (empty($type_check['ext']) || empty($type_check['type'])) {
                    self::ajax_error('bad_file', 400);
                }
            }
        }

        $submission_id = wp_insert_post([
            'post_type' => self::CPT,
            'post_status' => 'publish',
            'post_title' => sprintf('Submission - %s - %s', $uploader_name, current_time('mysql')),
        ], true);

        if (is_wp_error($submission_id) || !$submission_id) {
            self::ajax_error('upload_failed', 500);
        }

        update_post_meta($submission_id, '_lc_submission_status', 'pending');
        update_post_meta($submission_id, '_lc_uploader_name', $uploader_name);
        update_post_meta($submission_id, '_lc_uploader_email', $uploader_email);
        update_post_meta($submission_id, '_lc_location_id', $location_id);
        update_post_meta($submission_id, '_lc_submitted_at', current_time('mysql'));

        $attachment_ids = [];
        $media_errors = [];
        if (!empty($files)) {
            foreach ($files as $file) {
                $attachment_id = self::fast_insert_attachment_from_uploaded_file($file, $submission_id, $allowed_mimes);
                if (is_wp_error($attachment_id) || !$attachment_id) {
                    if (is_wp_error($attachment_id)) {
                        $media_errors[] = $attachment_id->get_error_message();
                    }
                    continue;
                }
                $attachment_id = (int) $attachment_id;
                $attachment_ids[] = $attachment_id;
                self::apply_credit_to_attachment($attachment_id, $uploader_name, $uploader_email);
            }
        } else {
            $result = self::upload_base64_images($images_base64, $submission_id, $allowed_mimes, $max_size_bytes, $uploader_name, $uploader_email);
            $attachment_ids = $result['ids'];
            $media_errors = $result['errors'];
        }

        if (empty($attachment_ids)) {
            wp_delete_post($submission_id, true);
            if (!empty($media_errors)) {
                wp_send_json_error([
                    'code' => 'upload_failed',
                    'message' => (string) $media_errors[0],
                ], 400);
            }
            self::ajax_error('upload_failed', 500);
        }

        update_post_meta($submission_id, '_lc_submission_images', $attachment_ids);
        self::bump_rate_limit($remote_ip);

        wp_send_json_success([
            'message' => self::status_message('success'),
            'submission_id' => (int) $submission_id,
            'uploaded_count' => count($attachment_ids),
        ]);
    }

    public static function handle_public_submission_admin_post_json() {
        $nonce = isset($_POST['_lc_place_photo_nonce']) ? sanitize_text_field((string) wp_unslash($_POST['_lc_place_photo_nonce'])) : '';
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION_SUBMIT)) {
            self::ajax_error('invalid_nonce', 403);
        }
        self::handle_public_submission_ajax();
    }

    public static function handle_public_submission_rest($request) {
        $settings = self::get_settings();
        if ($settings['upload_enabled'] !== '1') {
            return new WP_REST_Response(['success' => false, 'data' => ['code' => 'closed', 'message' => self::status_message('closed')]], 403);
        }

        $uploader_name = isset($_POST['uploader_name']) ? sanitize_text_field((string) wp_unslash($_POST['uploader_name'])) : '';
        if ($uploader_name === '') {
            return new WP_REST_Response(['success' => false, 'data' => ['code' => 'invalid_name', 'message' => self::status_message('invalid_name')]], 400);
        }
        $uploader_email = isset($_POST['uploader_email']) ? sanitize_email((string) wp_unslash($_POST['uploader_email'])) : '';
        if ($uploader_email === '' || !is_email($uploader_email)) {
            return new WP_REST_Response(['success' => false, 'data' => ['code' => 'invalid_email', 'message' => self::status_message('invalid_email')]], 400);
        }
        if (!self::is_email_allowed($uploader_email, $settings)) {
            return new WP_REST_Response(['success' => false, 'data' => ['code' => 'email_not_allowed', 'message' => self::status_message('email_not_allowed')]], 403);
        }

        $location_id = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
        if ($location_id <= 0 || get_post_type($location_id) !== 'location' || get_post_status($location_id) !== 'publish') {
            return new WP_REST_Response(['success' => false, 'data' => ['code' => 'invalid_location', 'message' => self::status_message('invalid_location')]], 400);
        }

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
        if (self::is_rate_limited($remote_ip, (int) $settings['max_submissions_per_hour'])) {
            return new WP_REST_Response(['success' => false, 'data' => ['code' => 'rate_limited', 'message' => self::status_message('rate_limited')]], 429);
        }

        $files = isset($_FILES['place_images']) ? self::normalize_uploaded_files($_FILES['place_images']) : [];
        if (empty($files)) {
            return new WP_REST_Response(['success' => false, 'data' => ['code' => 'no_images', 'message' => self::status_message('no_images')]], 400);
        }

        $max_files = (int) $settings['max_files_per_submission'];
        if (count($files) > $max_files) {
            return new WP_REST_Response(['success' => false, 'data' => ['code' => 'too_many_files', 'message' => self::status_message('too_many_files')]], 400);
        }

        $max_size_bytes = (int) $settings['max_file_size_mb'] * 1024 * 1024;
        $allowed_mimes = [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];

        foreach ($files as $file) {
            if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
                $code = self::file_error_to_status((int) $file['error']);
                return new WP_REST_Response(['success' => false, 'data' => ['code' => $code, 'message' => self::status_message($code)]], 400);
            }
            if ($file['size'] <= 0 || $file['size'] > $max_size_bytes) {
                return new WP_REST_Response(['success' => false, 'data' => ['code' => 'file_too_large', 'message' => self::status_message('file_too_large')]], 400);
            }
            $type_check = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);
            if (empty($type_check['ext']) || empty($type_check['type'])) {
                return new WP_REST_Response(['success' => false, 'data' => ['code' => 'bad_file', 'message' => self::status_message('bad_file')]], 400);
            }
        }

        $submission_id = wp_insert_post([
            'post_type' => self::CPT,
            'post_status' => 'publish',
            'post_title' => sprintf('Submission - %s - %s', $uploader_name, current_time('mysql')),
        ], true);
        if (is_wp_error($submission_id) || !$submission_id) {
            return new WP_REST_Response(['success' => false, 'data' => ['code' => 'upload_failed', 'message' => self::status_message('upload_failed')]], 500);
        }

        update_post_meta($submission_id, '_lc_submission_status', 'pending');
        update_post_meta($submission_id, '_lc_uploader_name', $uploader_name);
        update_post_meta($submission_id, '_lc_uploader_email', $uploader_email);
        update_post_meta($submission_id, '_lc_location_id', $location_id);
        update_post_meta($submission_id, '_lc_submitted_at', current_time('mysql'));

        $attachment_ids = [];
        $media_errors = [];
        foreach ($files as $file) {
            $attachment_id = self::fast_insert_attachment_from_uploaded_file($file, $submission_id, $allowed_mimes);
            if (is_wp_error($attachment_id) || !$attachment_id) {
                if (is_wp_error($attachment_id)) {
                    $media_errors[] = $attachment_id->get_error_message();
                }
                continue;
            }
            $attachment_id = (int) $attachment_id;
            $attachment_ids[] = $attachment_id;
            self::apply_credit_to_attachment($attachment_id, $uploader_name, $uploader_email);
        }

        if (empty($attachment_ids)) {
            wp_delete_post($submission_id, true);
            $message = !empty($media_errors) ? (string) $media_errors[0] : self::status_message('upload_failed');
            return new WP_REST_Response(['success' => false, 'data' => ['code' => 'upload_failed', 'message' => $message]], 400);
        }

        update_post_meta($submission_id, '_lc_submission_images', $attachment_ids);
        self::bump_rate_limit($remote_ip);

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'message' => self::status_message('success'),
                'submission_id' => (int) $submission_id,
                'uploaded_count' => count($attachment_ids),
            ],
        ], 200);
    }

    public static function handle_submit_location_edit_request() {
        $settings = self::get_settings();
        if (($settings['location_edit_enabled'] ?? '0') !== '1') {
            wp_die(__('ปิดรับคำขอแก้ไขข้อมูลสถานที่ชั่วคราว', 'lc-public-place-photo-upload'));
        }

        $nonce = isset($_POST['_lc_location_edit_nonce']) ? sanitize_text_field((string) wp_unslash($_POST['_lc_location_edit_nonce'])) : '';
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION_EDIT_SUBMIT)) {
            wp_die(__('Security check failed.', 'lc-public-place-photo-upload'));
        }

        $token = isset($_POST['token']) ? sanitize_text_field((string) wp_unslash($_POST['token'])) : '';
        if ($token === '') {
            $token = self::get_editor_token_from_cookie();
        }
        $session = self::get_editor_session($token);
        if (!$session) {
            wp_die(__('เซสชันหมดอายุ กรุณายืนยัน OTP ใหม่อีกครั้ง', 'lc-public-place-photo-upload'));
        }

        $location_id = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
        if ($location_id <= 0 || get_post_type($location_id) !== 'location') {
            wp_die(__('ข้อมูลสถานที่ไม่ถูกต้อง', 'lc-public-place-photo-upload'));
        }

        $requester_email = sanitize_email((string) ($session['email'] ?? ''));
        if (!self::is_editor_email_allowed($requester_email, $settings)) {
            wp_die(__('อีเมลนี้ไม่มีสิทธิ์แก้ไขข้อมูลแล้ว', 'lc-public-place-photo-upload'));
        }

        $session_ids = array_values(array_filter(array_map('intval', (array) ($_POST['session_ids'] ?? []))));
        $snapshot_sessions = [];
        foreach ($session_ids as $sid) {
            if ($sid <= 0 || get_post_type($sid) !== 'session') {
                continue;
            }
            $snapshot_sessions[$sid] = [
                'time_period' => (string) get_post_meta($sid, 'time_period', true),
                'apply_url' => (string) get_post_meta($sid, 'apply_url', true),
                'start_date' => (string) get_post_meta($sid, 'start_date', true),
                'end_date' => (string) get_post_meta($sid, 'end_date', true),
                'reg_start' => (string) get_post_meta($sid, 'reg_start', true),
                'reg_end' => (string) get_post_meta($sid, 'reg_end', true),
                'session_details' => (string) get_post_meta($sid, 'session_details', true),
            ];
        }
        $current_gallery_ids = self::parse_gallery_ids(get_post_meta($location_id, (string) $settings['location_gallery_meta_key'], true));

        $location_title = sanitize_text_field((string) ($_POST['location_title'] ?? ''));
        if ($location_title === '') {
            $location_title = (string) get_the_title($location_id);
        }

        $payload = [
            'request_type' => 'update_location',
            'location' => [
                'title' => $location_title,
                'address' => sanitize_textarea_field((string) ($_POST['address'] ?? '')),
                'phone' => sanitize_text_field((string) ($_POST['phone'] ?? '')),
                'opening_hours' => sanitize_textarea_field((string) ($_POST['opening_hours'] ?? '')),
                'description' => sanitize_textarea_field(self::normalize_multiline_text((string) ($_POST['description'] ?? ''))),
                'google_maps' => esc_url_raw((string) ($_POST['google_maps'] ?? '')),
                'facebook' => esc_url_raw((string) ($_POST['facebook'] ?? '')),
            ],
            'request_note' => sanitize_textarea_field(self::normalize_multiline_text((string) ($_POST['request_note'] ?? ''))),
            'remove_image_ids' => array_values(array_filter(array_map('intval', (array) ($_POST['remove_image_ids'] ?? [])))),
            'new_image_ids' => [],
            'sessions' => [],
            'snapshot' => [
            'location' => [
                'title' => (string) get_the_title($location_id),
                'address' => (string) get_post_meta($location_id, 'address', true),
                'phone' => (string) get_post_meta($location_id, 'phone', true),
                'opening_hours' => self::normalize_multiline_text((string) get_post_meta($location_id, 'opening_hours', true)),
                'description' => self::normalize_multiline_text((string) get_post_meta($location_id, 'description', true)),
                'google_maps' => (string) get_post_meta($location_id, 'google_maps', true),
                'facebook' => (string) get_post_meta($location_id, 'facebook', true),
            ],
                'facility_slugs' => self::get_location_facility_slugs($location_id),
                'image_ids' => $current_gallery_ids,
                'sessions' => $snapshot_sessions,
            ],
        ];
        if (isset($_POST['facility_slugs'])) {
            $payload['facility_slugs'] = self::sanitize_facility_slugs((array) $_POST['facility_slugs']);
        }

        foreach ($session_ids as $sid) {
            $payload['sessions'][] = [
                'id' => $sid,
                'time_period' => sanitize_textarea_field(self::normalize_multiline_text((string) (((array) ($_POST['session_time_period'] ?? []))[$sid] ?? ''))),
                'apply_url' => esc_url_raw((string) (((array) ($_POST['session_apply_url'] ?? []))[$sid] ?? '')),
                'start_date' => sanitize_text_field((string) (((array) ($_POST['session_start_date'] ?? []))[$sid] ?? '')),
                'end_date' => sanitize_text_field((string) (((array) ($_POST['session_end_date'] ?? []))[$sid] ?? '')),
                'reg_start' => sanitize_text_field((string) (((array) ($_POST['session_reg_start'] ?? []))[$sid] ?? '')),
                'reg_end' => sanitize_text_field((string) (((array) ($_POST['session_reg_end'] ?? []))[$sid] ?? '')),
                'session_details' => sanitize_textarea_field(self::normalize_multiline_text((string) (((array) ($_POST['session_details'] ?? []))[$sid] ?? ''))),
            ];
        }

        $new_files = self::validate_staff_edit_image_files($_FILES['new_images'] ?? null, $settings);
        if (is_wp_error($new_files)) {
            wp_die($new_files->get_error_message());
        }
        if (!empty($new_files)) {
            $allowed_mimes = self::staff_allowed_image_mimes();
            foreach ($new_files as $file) {
                $attachment_id = self::fast_insert_attachment_from_uploaded_file($file, $location_id, $allowed_mimes);
                if (is_wp_error($attachment_id) || !$attachment_id) {
                    $file_name = sanitize_file_name((string) ($file['name'] ?? ''));
                    $file_label = $file_name !== '' ? ('"' . $file_name . '"') : 'รูปภาพที่อัปโหลด';
                    wp_die($file_label . ' อัปโหลดไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                }
                $payload['new_image_ids'][] = (int) $attachment_id;
            }
        }

        if (!post_type_exists(self::CHANGE_CPT)) {
            self::register_change_request_cpt();
        }

        $request_id = wp_insert_post([
            'post_type' => self::CHANGE_CPT,
            'post_status' => 'pending',
            'post_title' => sprintf('Change Request - %s - %s', get_the_title($location_id), current_time('mysql')),
        ], true);
        if (is_wp_error($request_id) || !$request_id) {
            $error_message = is_wp_error($request_id) ? $request_id->get_error_message() : 'unknown insert error';
            if (function_exists('error_log')) {
                error_log('[LC] Failed to create change request: ' . $error_message);
            }
            wp_die(__('ไม่สามารถสร้างคำขอแก้ไขได้', 'lc-public-place-photo-upload') . ' ' . esc_html($error_message));
        }

        update_post_meta($request_id, '_lc_change_status', 'pending');
        update_post_meta($request_id, '_lc_request_type', 'update_location');
        update_post_meta($request_id, '_lc_location_id', $location_id);
        update_post_meta($request_id, '_lc_requester_email', $requester_email);
        update_post_meta($request_id, '_lc_change_payload', wp_json_encode($payload, JSON_UNESCAPED_UNICODE));
        update_post_meta($request_id, '_lc_submitted_at', current_time('mysql'));
        delete_post_meta($request_id, '_lc_reject_reason');

        wp_safe_redirect(add_query_arg([
            'lc_location_edit' => 1,
            'focus_place_id' => $location_id,
            'focus_request_id' => $request_id,
            'req_kind' => 'edit',
            'lc_change_sent' => 1,
        ], home_url('/')));
        exit;
    }

    private static function normalize_diff_value($value) {
        if (is_array($value)) {
            $flat = array_map(function($v) {
                if (!is_scalar($v)) {
                    return '';
                }
                $text = self::normalize_multiline_text($v);
                if ($text === '') {
                    return '';
                }
                return $text;
            }, $value);
            $flat = array_values(array_filter($flat, static function($v) {
                return $v !== '';
            }));
            return implode(', ', $flat);
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if ($value === null) {
            return '';
        }
        $text = self::normalize_multiline_text($value);
        if ($text === '') {
            return '';
        }
        return $text;
    }

    private static function normalize_multiline_text($value) {
        if (!is_scalar($value)) {
            return '';
        }
        $text = self::decode_html_entities_text((string) $value);
        if ($text === '') {
            return '';
        }
        // Convert escaped and real line-break tokens into actual new lines.
        $text = str_replace(["\\r\\n", "\\n\\r", "\\n", "\\r"], "\n", $text);
        // Convert escaped tabs into real tabs first; later we normalize tabs to spaces.
        $text = str_replace(["\\t"], "\t", $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Recover stripped escaped newline markers after wp_unslash (e.g. "\r\n" -> "rn", "\r\n\r\n" -> "rnrn").
        // Include punctuation-boundary cases like "น.rnrnวัน..." that were missed by strict Thai/digit-only guards.
        $text = preg_replace('/(?<=[\p{Thai}\d\)\]\.])\s*rnrn\s*(?=[\p{Thai}\d\(])/u', "\n\n", $text);
        $text = preg_replace('/(?<=[\p{Thai}\d\)\]\.])\s*rn\s*(?=[\p{Thai}\d\(])/u', "\n", $text);
        // Fallback for standalone tokens not surrounded by latin words.
        $text = preg_replace('/(?<![A-Za-z0-9])rnrn(?![A-Za-z0-9])/iu', "\n\n", $text);
        $text = preg_replace('/(?<![A-Za-z0-9])rn(?![A-Za-z0-9])/iu', "\n", $text);
        // Recover stripped escaped newline markers after wp_unslash (e.g. "\n\n" -> "nn").
        $text = preg_replace('/(?<=[\p{Thai}\d])nn(?=[\p{Thai}\d])/u', "\n\n", $text);
        $text = preg_replace('/(?<=[\p{Thai}\d])n(?=[\p{Thai}\d])/u', "\n", $text);
        // Recover stripped escaped tab marker after wp_unslash (e.g. "\t" -> "t"), mostly seen between Thai text and time values.
        $text = preg_replace('/(?<=[\p{Thai}\d])\s*t\s*(?=[\p{Thai}\d])/u', "\t", $text);
        // Handle punctuation-separated artifacts (e.g. "น.tห้อง", "การn อาทิตย์", "น. nอาทิตย์").
        $text = preg_replace('/(?<=[\p{Thai}\d\)\]\.])\s*n\s*(?=[\p{Thai}\d\(])/u', "\n", $text);
        $text = preg_replace('/(?<=[\p{Thai}\d\)\]\.])\s*t\s*(?=[\p{Thai}\d\(])/u', "\t", $text);
        $text = str_replace("\t", ' ', $text);
        $text = preg_replace('/[ ]{2,}/u', ' ', $text);
        $text = preg_replace("/[ \t]+\n/u", "\n", $text);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);
        return trim((string) $text);
    }

    private static function decode_html_entities_text($value) {
        if (!is_scalar($value)) {
            return '';
        }
        $text = (string) $value;
        if ($text === '') {
            return '';
        }
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function normalized_compare_key($value) {
        $text = self::normalize_diff_value($value);
        if (!is_string($text) || $text === '') {
            return '';
        }
        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);
            if (is_string($normalized) && $normalized !== '') {
                $text = $normalized;
            }
        }
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
        $text = is_string($text) ? $text : '';
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            $text = mb_strtolower($text, 'UTF-8');
        } else {
            $text = strtolower($text);
        }
        // Treat spacing / punctuation / symbols as non-meaningful for diff checks.
        $text = preg_replace('/[\s\p{Z}\p{P}\p{S}]+/u', '', $text);
        return is_string($text) ? $text : '';
    }

    private static function format_ids_for_admin($ids) {
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));
        if (empty($ids)) {
            return '-';
        }
        $labels = [];
        foreach ($ids as $id) {
            $title = '';
            if (get_post_type($id) === 'attachment') {
                $title = (string) get_the_title($id);
            }
            if ($title === '') {
                $title = '#' . (string) $id;
            } else {
                $title .= ' (#' . (string) $id . ')';
            }
            $labels[] = $title;
        }
        return implode(', ', $labels);
    }

    private static function add_diff_row(&$rows, $section, $field, $old_value, $new_value) {
        $old = self::normalize_diff_value($old_value);
        $new = self::normalize_diff_value($new_value);
        if ($old === $new) {
            return;
        }
        $old_for_compare = self::normalized_compare_key($old);
        $new_for_compare = self::normalized_compare_key($new);
        if ($old_for_compare === $new_for_compare) {
            return;
        }
        // Ignore tiny non-meaningful diffs that still survive normalization.
        if ($old_for_compare !== '' && $new_for_compare !== '' && function_exists('similar_text')) {
            $similar = 0.0;
            similar_text($old_for_compare, $new_for_compare, $similar);
            if ($similar >= 99.2) {
                return;
            }
        }
        $rows[] = [
            'section' => (string) $section,
            'field' => (string) $field,
            'old' => $old === '' ? '-' : $old,
            'new' => $new === '' ? '-' : $new,
        ];
    }

    private static function add_image_diff_row(&$rows, $field, $old_ids, $new_ids, $old_text = '-', $new_text = '-') {
        $old_ids = array_values(array_filter(array_map('intval', (array) $old_ids)));
        $new_ids = array_values(array_filter(array_map('intval', (array) $new_ids)));
        $old = self::normalize_diff_value($old_text);
        $new = self::normalize_diff_value($new_text);
        if ($old === '' && empty($old_ids)) {
            $old = '-';
        }
        if ($new === '' && empty($new_ids)) {
            $new = '-';
        }
        if ($old === $new && $old_ids === $new_ids) {
            return;
        }
        $rows[] = [
            'section' => 'Images',
            'field' => (string) $field,
            'old' => $old,
            'new' => $new,
            'old_ids' => $old_ids,
            'new_ids' => $new_ids,
        ];
    }

    private static function build_change_key_map($diff_rows) {
        $diff_rows = is_array($diff_rows) ? $diff_rows : [];
        $map = [];
        foreach ($diff_rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $section = trim((string) ($row['section'] ?? ''));
            $field = trim((string) ($row['field'] ?? ''));
            if ($section === '' || $field === '') {
                continue;
            }
            // "Gallery (Before / After)" is a summary row and should not be treated as explicit user intent.
            if (strcasecmp($field, 'Gallery (Before / After)') === 0) {
                continue;
            }
            $section_key = self::normalized_compare_key($section);
            $field_key = self::normalized_compare_key($field);
            if ($section_key === '' || $field_key === '') {
                continue;
            }
            $key = $section_key . '|' . $field_key;
            if (!isset($map[$key])) {
                $map[$key] = [
                    'label' => $section . ' / ' . $field,
                    'old' => (string) ($row['old'] ?? '-'),
                    'new' => (string) ($row['new'] ?? '-'),
                ];
            }
        }
        return $map;
    }

    private static function render_attachment_preview_list($ids) {
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));
        if (empty($ids)) {
            return '';
        }
        $html = '<div class="lc-diff-img-grid">';
        foreach ($ids as $id) {
            $thumb = wp_get_attachment_image_url($id, 'thumbnail');
            $html .= '<div class="lc-diff-img-item">';
            if (is_string($thumb) && $thumb !== '') {
                $html .= '<img src="' . esc_url($thumb) . '" alt="" loading="lazy" />';
            } else {
                $html .= '<div class="lc-diff-img-ph">No preview</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private static function render_diff_cell_html($text, $ids = []) {
        $text = (string) $text;
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));
        $html = '';
        if (!empty($ids)) {
            $html .= self::render_attachment_preview_list($ids);
        }
        if ($text !== '' && $text !== '-') {
            $html .= '<div class="lc-diff-note">' . esc_html($text) . '</div>';
        } elseif ($html === '') {
            $html = '<div class="lc-diff-note">-</div>';
        }
        return $html;
    }

    private static function build_change_diff_rows($location_id, $payload) {
        $rows = [];
        $payload = is_array($payload) ? $payload : [];
        $location = is_array($payload['location'] ?? null) ? $payload['location'] : [];
        $snapshot = is_array($payload['snapshot'] ?? null) ? $payload['snapshot'] : [];
        $snapshot_location = is_array($snapshot['location'] ?? null) ? $snapshot['location'] : [];

        $location_fields = [
            'title' => 'Title',
            'address' => 'Address',
            'phone' => 'Phone',
            'opening_hours' => 'Opening Hours',
            'description' => 'Description',
            'google_maps' => 'Google Maps',
            'facebook' => 'Facebook',
        ];
        foreach ($location_fields as $key => $label) {
            $old = $snapshot_location[$key] ?? (($key === 'title')
                ? (string) get_the_title($location_id)
                : (string) get_post_meta($location_id, $key, true));
            $new = isset($location[$key]) ? (string) $location[$key] : '';
            self::add_diff_row($rows, 'Location', $label, $old, $new);
        }

        $snapshot_facility_slugs = array_values(array_filter(array_map('sanitize_title', (array) ($snapshot['facility_slugs'] ?? self::get_location_facility_slugs($location_id)))));
        $proposed_facility_slugs = array_values(array_filter(array_map('sanitize_title', (array) ($payload['facility_slugs'] ?? $snapshot_facility_slugs))));
        $old_facility_labels = self::facility_labels_from_slugs($snapshot_facility_slugs);
        $new_facility_labels = self::facility_labels_from_slugs($proposed_facility_slugs);
        self::add_diff_row($rows, 'Location', 'Facilities', implode(', ', $old_facility_labels), implode(', ', $new_facility_labels));

        $settings = self::get_settings();
        $meta_key = (string) $settings['location_gallery_meta_key'];
        $old_gallery_ids = self::parse_gallery_ids($snapshot['image_ids'] ?? get_post_meta($location_id, $meta_key, true));
        $remove_ids = array_values(array_filter(array_map('intval', (array) ($payload['remove_image_ids'] ?? []))));
        $new_image_ids = array_values(array_filter(array_map('intval', (array) ($payload['new_image_ids'] ?? []))));
        if (!empty($remove_ids)) {
            self::add_image_diff_row($rows, 'Remove Images', [], $remove_ids, '-', 'Selected for removal');
        }
        if (!empty($new_image_ids)) {
            self::add_image_diff_row($rows, 'Add Images', [], $new_image_ids, '-', 'New uploads');
        }
        $merged = $old_gallery_ids;
        if (!empty($remove_ids)) {
            $merged = array_values(array_diff($merged, $remove_ids));
        }
        if (!empty($new_image_ids)) {
            $merged = array_values(array_unique(array_merge($merged, $new_image_ids)));
        }
        self::add_image_diff_row($rows, 'Gallery (Before / After)', $old_gallery_ids, $merged, 'Before', 'After');

        $session_fields = [
            'time_period' => 'Time Period',
            'session_details' => 'Session Details',
        ];
        $snapshot_sessions = is_array($snapshot['sessions'] ?? null) ? $snapshot['sessions'] : [];
        $sessions = is_array($payload['sessions'] ?? null) ? $payload['sessions'] : [];
        foreach ($sessions as $item) {
            if (!is_array($item)) {
                continue;
            }
            $sid = (int) ($item['id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $course_id = (int) get_post_meta($sid, 'course', true);
            $course_title = $course_id > 0 ? self::decode_html_entities_text((string) get_the_title($course_id)) : '';
            $session_title = self::decode_html_entities_text((string) get_the_title($sid));
            $label_core = $course_title !== '' ? $course_title : ($session_title !== '' ? $session_title : 'Session');
            $session_label = 'Session: ' . $label_core . ' (#' . $sid . ')';
            $snapshot_item = is_array($snapshot_sessions[$sid] ?? null) ? $snapshot_sessions[$sid] : [];
            foreach ($session_fields as $field_key => $field_label) {
                $old = $snapshot_item[$field_key] ?? (string) get_post_meta($sid, $field_key, true);
                $new = isset($item[$field_key]) ? (string) $item[$field_key] : '';
                self::add_diff_row($rows, $session_label, $field_label, $old, $new);
            }
        }

        $delete_session_ids = array_values(array_filter(array_map('intval', (array) ($payload['delete_session_ids'] ?? []))));
        foreach ($delete_session_ids as $sid) {
            if ($sid <= 0) {
                continue;
            }
            $course_id = (int) get_post_meta($sid, 'course', true);
            $course_title = $course_id > 0 ? self::decode_html_entities_text((string) get_the_title($course_id)) : '';
            $session_title = self::decode_html_entities_text((string) get_the_title($sid));
            $label_core = $course_title !== '' ? $course_title : ($session_title !== '' ? $session_title : 'Session');
            $session_label = 'Session: ' . $label_core . ' (#' . $sid . ')';
            self::add_diff_row($rows, $session_label, 'Delete Session', 'Keep', 'Delete');
        }

        $new_sessions = is_array($payload['new_sessions'] ?? null) ? $payload['new_sessions'] : [];
        foreach ($new_sessions as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $course_id = (int) ($item['course_id'] ?? 0);
            if ($course_id <= 0) {
                continue;
            }
            $course_title = self::decode_html_entities_text((string) get_the_title($course_id));
            if ($course_title === '') {
                $course_title = 'Course #' . (string) $course_id;
            }
            $details = sanitize_textarea_field((string) ($item['session_details'] ?? ''));
            $time_period = sanitize_text_field((string) ($item['time_period'] ?? ''));
            $label = 'Session (New) #' . (string) ((int) $index + 1);
            self::add_diff_row($rows, $label, 'Create Session', '-', $course_title);
            if ($time_period !== '') {
                self::add_diff_row($rows, $label, 'Time Period', '-', $time_period);
            }
            if ($details !== '') {
                self::add_diff_row($rows, $label, 'Session Details', '-', $details);
            }
        }

        $request_note = sanitize_textarea_field((string) ($payload['request_note'] ?? ''));
        if ($request_note !== '') {
            self::add_diff_row($rows, 'Location', 'Request Note', '-', $request_note);
        }

        return $rows;
    }

    private static function build_course_change_diff_rows($course_id, $payload) {
        $rows = [];
        $course_id = (int) $course_id;
        $payload = is_array($payload) ? $payload : [];
        $course = is_array($payload['course'] ?? null) ? $payload['course'] : [];
        $snapshot = is_array($payload['snapshot'] ?? null) ? $payload['snapshot'] : [];
        $snapshot_course = is_array($snapshot['course'] ?? null) ? $snapshot['course'] : [];
        $course_label = self::decode_html_entities_text((string) get_the_title($course_id));
        if ($course_label === '') {
            $course_label = 'Course #' . (string) $course_id;
        }

        $course_fields = [
            'title' => 'Title',
            'course_description' => 'Description',
            'learning_link' => 'Learning Link',
            'total_minutes' => 'Total Minutes',
            'price' => 'Price',
            'has_certificate' => 'Certificate',
        ];
        foreach ($course_fields as $key => $label) {
            $old = $snapshot_course[$key] ?? (($key === 'title')
                ? (string) get_the_title($course_id)
                : (string) get_post_meta($course_id, $key, true));
            $new = array_key_exists($key, $course) ? $course[$key] : $old;
            if ($key === 'total_minutes') {
                $old = is_numeric((string) $old) ? (string) ((int) $old) : (string) $old;
                $new = is_numeric((string) $new) ? (string) ((int) $new) : (string) $new;
            } elseif ($key === 'price') {
                $old = is_numeric((string) $old) ? rtrim(rtrim(number_format((float) $old, 2, '.', ''), '0'), '.') : (string) $old;
                $new = is_numeric((string) $new) ? rtrim(rtrim(number_format((float) $new, 2, '.', ''), '0'), '.') : (string) $new;
            } elseif ($key === 'has_certificate') {
                $old = !empty($old) ? 'Yes' : 'No';
                $new = !empty($new) ? 'Yes' : 'No';
            }
            self::add_diff_row($rows, 'Course', $label, $old, $new);
        }

        $old_image_ids = self::parse_gallery_ids($snapshot['image_ids'] ?? get_post_meta($course_id, 'images', true));
        $remove_image_ids = array_values(array_filter(array_map('intval', (array) ($payload['remove_image_ids'] ?? []))));
        $new_image_ids = array_values(array_filter(array_map('intval', (array) ($payload['new_image_ids'] ?? []))));
        if (!empty($remove_image_ids)) {
            self::add_image_diff_row($rows, 'Remove Images', [], $remove_image_ids, '-', 'Selected for removal');
        }
        if (!empty($new_image_ids)) {
            self::add_image_diff_row($rows, 'Add Images', [], $new_image_ids, '-', 'New uploads');
        }
        $merged_image_ids = $old_image_ids;
        if (!empty($remove_image_ids)) {
            $merged_image_ids = array_values(array_diff($merged_image_ids, $remove_image_ids));
        }
        if (!empty($new_image_ids)) {
            $merged_image_ids = array_values(array_unique(array_merge($merged_image_ids, $new_image_ids)));
        }
        self::add_image_diff_row($rows, 'Gallery (Before / After)', $old_image_ids, $merged_image_ids, 'Before', 'After');

        $snapshot_sessions = is_array($snapshot['sessions'] ?? null) ? $snapshot['sessions'] : [];
        $sessions = is_array($payload['sessions'] ?? null) ? $payload['sessions'] : [];
        $session_fields = [
            'time_period' => 'Time Period',
            'session_details' => 'Session Details',
            'apply_url' => 'Apply URL',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'reg_start' => 'Reg Start',
            'reg_end' => 'Reg End',
        ];
        foreach ($sessions as $item) {
            if (!is_array($item)) {
                continue;
            }
            $sid = (int) ($item['id'] ?? 0);
            if ($sid <= 0 || get_post_type($sid) !== 'session') {
                continue;
            }
            $location_id = (int) get_post_meta($sid, 'location', true);
            $location_title = $location_id > 0 ? self::decode_html_entities_text((string) get_the_title($location_id)) : '';
            $session_title = self::decode_html_entities_text((string) get_the_title($sid));
            $session_target = $location_title !== '' ? $location_title : ($session_title !== '' ? $session_title : ('#' . (string) $sid));
            $session_label = 'Session: ' . $course_label . ' / ' . $session_target . ' (#' . (string) $sid . ')';
            $snapshot_item = is_array($snapshot_sessions[$sid] ?? null) ? $snapshot_sessions[$sid] : [];
            foreach ($session_fields as $field_key => $field_label) {
                $old = $snapshot_item[$field_key] ?? (string) get_post_meta($sid, $field_key, true);
                $new = array_key_exists($field_key, $item) ? $item[$field_key] : $old;
                self::add_diff_row($rows, $session_label, $field_label, $old, $new);
            }
        }

        $delete_session_ids = array_values(array_filter(array_map('intval', (array) ($payload['delete_session_ids'] ?? []))));
        foreach ($delete_session_ids as $sid) {
            $location_id = (int) get_post_meta($sid, 'location', true);
            $location_title = $location_id > 0 ? self::decode_html_entities_text((string) get_the_title($location_id)) : '';
            $session_title = self::decode_html_entities_text((string) get_the_title($sid));
            $session_target = $location_title !== '' ? $location_title : ($session_title !== '' ? $session_title : ('#' . (string) $sid));
            $session_label = 'Session: ' . $course_label . ' / ' . $session_target . ' (#' . (string) $sid . ')';
            self::add_diff_row($rows, $session_label, 'Delete Session', 'Keep', 'Delete');
        }

        $new_sessions = is_array($payload['new_sessions'] ?? null) ? $payload['new_sessions'] : [];
        foreach ($new_sessions as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $location_id = (int) ($item['location_id'] ?? 0);
            $location_title = $location_id > 0 ? self::decode_html_entities_text((string) get_the_title($location_id)) : '';
            $label = 'Session (New) #' . (string) ((int) $index + 1);
            self::add_diff_row($rows, $label, 'Location', '-', $location_title !== '' ? $location_title : ('#' . (string) $location_id));
            foreach (['time_period' => 'Time Period', 'session_details' => 'Session Details', 'apply_url' => 'Apply URL'] as $field_key => $field_label) {
                $value = isset($item[$field_key]) ? (string) $item[$field_key] : '';
                if ($value !== '') {
                    self::add_diff_row($rows, $label, $field_label, '-', $value);
                }
            }
        }

        $request_note = sanitize_textarea_field((string) ($payload['request_note'] ?? ''));
        if ($request_note !== '') {
            self::add_diff_row($rows, 'Course', 'Request Note', '-', $request_note);
        }
        return $rows;
    }

    public static function render_location_edit_queue_page($request_type_filter = 'update_location', $page_title = 'Location Edit Requests') {
        if (!current_user_can('manage_options')) {
            return;
        }
        $request_type_filter = sanitize_key((string) $request_type_filter);
        if (!in_array($request_type_filter, ['update_location', 'update_course', 'all'], true)) {
            $request_type_filter = 'update_location';
        }
        $queue_page = 'lc-location-edit-queue';
        $per_page = 50;
        $current_page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $server_search = isset($_GET['lcq']) ? sanitize_text_field((string) wp_unslash($_GET['lcq'])) : '';
        $server_search_key = self::normalized_compare_key($server_search);
        $server_status = isset($_GET['lc_status']) ? sanitize_key((string) wp_unslash($_GET['lc_status'])) : 'all';
        if (!in_array($server_status, ['all', 'pending', 'approved', 'rejected', 'cancelled'], true)) {
            $server_status = 'all';
        }
        $server_type = isset($_GET['lc_type']) ? sanitize_key((string) wp_unslash($_GET['lc_type'])) : 'all';
        if (!in_array($server_type, ['all', 'update_location', 'update_course'], true)) {
            $server_type = 'all';
        }
        $location_type_taxonomy = 'location-type';
        $location_type_terms = get_terms([
            'taxonomy' => $location_type_taxonomy,
            'hide_empty' => false,
        ]);
        if (is_wp_error($location_type_terms) || !is_array($location_type_terms)) {
            $location_type_terms = [];
        }
        $location_type_slug_map = [];
        foreach ($location_type_terms as $term) {
            if ($term instanceof WP_Term) {
                $location_type_slug_map[(string) $term->slug] = (string) $term->name;
            }
        }
        $reject_reason_options = self::reject_reason_options_th();
        $reject_reason_option_lookup = [];
        foreach ($reject_reason_options as $reject_reason_key => $reject_reason_label) {
            if ($reject_reason_key === 'other') {
                continue;
            }
            $reject_reason_option_lookup[(string) $reject_reason_label] = (string) $reject_reason_key;
        }
        $server_location_type = isset($_GET['lc_location_type']) ? sanitize_title((string) wp_unslash($_GET['lc_location_type'])) : '';
        if ($server_location_type !== '' && !isset($location_type_slug_map[$server_location_type])) {
            $server_location_type = '';
        }
        $q = new WP_Query([
            'post_type' => self::change_request_post_types(),
            'post_status' => ['pending', 'publish', 'draft'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $rows = [];
        $counts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0, 'other' => 0];
        $type_counts = ['all' => 0, 'update_location' => 0, 'update_course' => 0, 'other' => 0];
        foreach ((array) $q->posts as $rid_raw) {
            $rid = (int) $rid_raw;
            if ($rid <= 0) {
                continue;
            }
            $status = (string) get_post_meta($rid, '_lc_change_status', true);
            if ($status === '' || !in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
                $status = 'pending';
            }
            $cancelled_at = (string) get_post_meta($rid, '_lc_cancelled_at', true);
            $cancelled_by = (string) get_post_meta($rid, '_lc_cancelled_by_email', true);
            $email = (string) get_post_meta($rid, '_lc_requester_email', true);
            $submitted_at = (string) get_post_meta($rid, '_lc_submitted_at', true);
            $moderated_by_id = (int) get_post_meta($rid, '_lc_moderated_by', true);
            $moderated_by_name = '';
            if ($moderated_by_id > 0) {
                $moderator = get_userdata($moderated_by_id);
                if ($moderator instanceof WP_User) {
                    $display_name = trim((string) $moderator->display_name);
                    $user_login = trim((string) $moderator->user_login);
                    $moderated_by_name = $display_name !== '' ? $display_name : $user_login;
                }
            }
            $payload_json = (string) get_post_meta($rid, '_lc_change_payload', true);
            $payload = json_decode($payload_json, true);
            if (!is_array($payload)) {
                $payload = [];
            }
            $request_type = sanitize_key((string) get_post_meta($rid, '_lc_request_type', true));
            if ($request_type === '') {
                $request_type = sanitize_key((string) ($payload['request_type'] ?? 'update_location'));
            }
            $target = self::get_request_target_payload((int) $rid, $payload);
            if (!is_array($target)) {
                continue;
            }
            $target_id = (int) ($target['target_id'] ?? 0);
            $target_type = (string) ($target['target_type'] ?? 'location');
            $target_title = (string) ($target['target_title'] ?? '');
            $request_type = (string) ($target['request_type'] ?? $request_type);
            if ($request_type_filter !== 'all' && $request_type !== $request_type_filter) {
                continue;
            }
            $counts['all']++;
            if (isset($counts[$status])) {
                $counts[$status]++;
            } else {
                $counts['other']++;
            }
            if ($server_type !== 'all' && $request_type !== $server_type) {
                continue;
            }
            if ($server_location_type !== '') {
                if ($request_type !== 'update_location' || $target_type !== 'location' || $target_id <= 0) {
                    continue;
                }
                if (!has_term($server_location_type, $location_type_taxonomy, $target_id)) {
                    continue;
                }
            }
            $search_blob = implode(' ', [
                '#' . (string) $rid,
                (string) $target_title,
                (string) self::request_type_label_th($request_type),
                (string) $email,
                (string) $submitted_at,
                (string) $status,
                (string) $request_type,
                (string) $target_type,
            ]);
            if ($server_search_key !== '' && strpos(self::normalized_compare_key($search_blob), $server_search_key) === false) {
                continue;
            }
            $type_counts['all']++;
            if (isset($type_counts[$request_type])) {
                $type_counts[$request_type]++;
            } else {
                $type_counts['other']++;
            }
            if ($server_status !== 'all' && $status !== $server_status) {
                continue;
            }
            $diff_rows = $target_type === 'location'
                ? self::build_change_diff_rows($target_id, $payload)
                : self::build_course_change_diff_rows($target_id, $payload);
            $approve_url = wp_nonce_url(add_query_arg(['action' => 'lc_approve_location_change_request', 'request_id' => $rid], admin_url('admin-post.php')), self::NONCE_ACTION_EDIT_MODERATE);
            $reject_url = wp_nonce_url(add_query_arg(['action' => 'lc_reject_location_change_request', 'request_id' => $rid], admin_url('admin-post.php')), self::NONCE_ACTION_EDIT_MODERATE);

            $rows[] = [
                'id' => $rid,
                'status' => $status,
                'location_id' => $target_id,
                'location_title' => $target_title,
                'target_type' => $target_type,
                'target_id' => $target_id,
                'target_title' => $target_title,
                'target_type_label' => self::request_type_label_th($request_type),
                'request_type' => $request_type,
                'email' => $email,
                'submitted_at' => $submitted_at,
                'moderated_by_name' => $moderated_by_name,
                'cancelled_at' => $cancelled_at,
                'cancelled_by' => $cancelled_by,
                'approve_url' => $approve_url,
                'reject_url' => $reject_url,
                'diff_rows' => $diff_rows,
                'submitted_ts' => is_string($submitted_at) && $submitted_at !== '' ? (int) strtotime($submitted_at) : 0,
                'change_key_map' => self::build_change_key_map($diff_rows),
            ];
        }
        usort($rows, function ($a, $b) {
            $av = (int) ($a['submitted_ts'] ?? 0);
            $bv = (int) ($b['submitted_ts'] ?? 0);
            if ($av === $bv) {
                return 0;
            }
            return ($av > $bv) ? -1 : 1;
        });

        $total_items = count($rows);
        $total_pages = max(1, (int) ceil($total_items / $per_page));
        if ($current_page > $total_pages) {
            $current_page = $total_pages;
        }
        $offset = max(0, ($current_page - 1) * $per_page);
        $rows = array_slice($rows, $offset, $per_page);

        $pagination_links = '';
        if ($total_pages > 1) {
            $base_args = ['page' => $queue_page];
            if ($server_search !== '') {
                $base_args['lcq'] = $server_search;
            }
            if ($server_status !== 'all') {
                $base_args['lc_status'] = $server_status;
            }
            if ($server_type !== 'all') {
                $base_args['lc_type'] = $server_type;
            }
            if ($server_location_type !== '') {
                $base_args['lc_location_type'] = $server_location_type;
            }
            $base_url = add_query_arg($base_args, admin_url('admin.php'));
            $links = paginate_links([
                'base' => add_query_arg('paged', '%#%', $base_url),
                'format' => '',
                'current' => $current_page,
                'total' => $total_pages,
                'prev_text' => __('&laquo; Previous'),
                'next_text' => __('Next &raquo;'),
                'type' => 'array',
            ]);
            if (is_array($links) && !empty($links)) {
                $pagination_links = '<nav class="lc-pagination" aria-label="Request Pagination">' . implode('', $links) . '</nav>';
            }
        }

        echo '<div class="wrap">';
        echo '<h1 style="margin-bottom:12px;">' . esc_html((string) $page_title) . '</h1>';
        $flash_notice = self::pop_admin_queue_notice();
        if (is_array($flash_notice)) {
            $flash_type = (string) ($flash_notice['type'] ?? 'warning');
            $flash_message = (string) ($flash_notice['message'] ?? '');
            $notice_class = 'notice notice-warning';
            if ($flash_type === 'success') {
                $notice_class = 'notice notice-success';
            } elseif ($flash_type === 'error') {
                $notice_class = 'notice notice-error';
            } elseif ($flash_type === 'info') {
                $notice_class = 'notice notice-info';
            }
            if ($flash_message !== '') {
                echo '<div class="' . esc_attr($notice_class) . '" style="margin:10px 0 14px;"><p>' . esc_html($flash_message) . '</p></div>';
            }
        }
        $bulk_updated = isset($_GET['bulk_updated']) ? (int) $_GET['bulk_updated'] : -1;
        $bulk_failed = isset($_GET['bulk_failed']) ? (int) $_GET['bulk_failed'] : -1;
        if ($bulk_updated >= 0 || $bulk_failed >= 0) {
            $notice_class = ($bulk_failed > 0) ? 'notice notice-warning' : 'notice notice-success';
            echo '<div class="' . esc_attr($notice_class) . '" style="margin:10px 0 14px;"><p>';
            echo esc_html(sprintf('Bulk update completed: updated %d item(s), failed %d item(s).', max(0, $bulk_updated), max(0, $bulk_failed)));
            echo '</p></div>';
        }
        echo '<style>
          .lc-status-toggle-row{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0 0 8px}
          .lc-status-toggle{display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 12px;border:1px solid #d0d7de;border-radius:999px;background:#fff;color:#1d2327;text-decoration:none;font-weight:600}
          .lc-status-toggle.is-active{background:#2271b1;border-color:#2271b1;color:#fff}
          .lc-mail-search{width:min(420px,100%);height:38px;border:1px solid #d0d7de;border-radius:10px;padding:0 12px;background:#fff}
          .lc-search-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0 0 8px;padding:8px 10px;border:1px solid #dbe4ee;background:#fff;border-radius:10px}
          .lc-search-toolbar .lc-mail-search{width:min(640px,100%);flex:1 1 360px}
          .lc-search-toolbar select{height:36px;min-width:130px}
          .lc-active-filters-row{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-start;gap:8px;margin:0 0 10px}
          .lc-active-filters{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin:0 0 10px}
          .lc-filter-chip{display:inline-flex;align-items:center;gap:6px;min-height:30px;padding:0 10px;border:1px solid #c3d4e6;border-radius:999px;background:#eef6ff;color:#0f172a;text-decoration:none}
          .lc-filter-chip-remove{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:999px;background:#dbeafe;color:#1e3a8a;font-weight:700;line-height:1}
          .lc-active-filters-count{font-size:12px;color:#475569;white-space:nowrap}
          .lc-bulk-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0 0 12px;padding:8px 10px;border:1px solid #dbe4ee;background:#fff;border-radius:10px}
          .lc-bulk-toolbar select{height:34px;min-width:160px}
          .lc-bulk-toolbar .button{height:34px}
          .lc-bulk-meta{margin-left:auto;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
          .lc-bulk-meta .lc-pagination{margin:0}
          .lc-pagination{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin:0 0 12px}
          .lc-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 10px;border:1px solid #d0d7de;border-radius:6px;background:#fff;color:#1d2327;text-decoration:none}
          .lc-pagination .page-numbers.current{background:#2271b1;border-color:#2271b1;color:#fff}
          .lc-pagination .page-numbers:hover{background:#f0f6fc}
          .lc-mail-row{display:grid;grid-template-columns:34px 1fr;align-items:stretch;border-bottom:1px solid #eef2f7}
          .lc-mail-row:last-child{border-bottom:none}
          .lc-mail-check-wrap{display:flex;align-items:flex-start;justify-content:center;padding-top:12px;background:#fff}
          .lc-mail-check{width:16px;height:16px}
          .lc-mail-wrap{display:grid;grid-template-columns:500px 1fr;gap:12px;min-height:70vh}
          .lc-mail-left{border:1px solid #d0d7de;background:#fff;border-radius:12px;overflow:auto;max-height:72vh}
          .lc-mail-list-head{position:sticky;top:0;z-index:2;display:grid;grid-template-columns:34px 1fr;align-items:center;background:#f8fafc;border-bottom:1px solid #e2e8f0}
          .lc-mail-list-head .lc-mail-check-wrap{padding:10px 0;background:#f8fafc}
          .lc-mail-list-head-label{height:100%;display:flex;align-items:center;padding:0 12px;color:#475569;font-size:12px;font-weight:700}
          .lc-mail-head-grid{display:grid;grid-template-columns:minmax(0,1fr) 84px 96px;gap:8px;align-items:center;width:100%}
          .lc-mail-item{display:block;width:100%;text-align:left;border:none;background:#fff;padding:8px 10px;cursor:pointer}
          .lc-mail-item:hover{background:#f8fafc}
          .lc-mail-item.is-active{background:#ecfeff}
          .lc-mail-item-grid{display:grid;grid-template-columns:minmax(0,1fr) 84px 96px;gap:8px;align-items:center}
          .lc-mail-col-title{font-weight:700;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
          .lc-mail-col-subtitle{margin-top:2px;font-size:12px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
          .lc-mail-col-date{font-size:12px;color:#64748b}
          .lc-mail-col-status{display:flex;justify-content:flex-start}
          .lc-mail-item-meta{font-size:12px;color:#64748b;display:flex;gap:8px;flex-wrap:wrap}
          .lc-status{font-size:11px;line-height:20px;height:20px;border-radius:999px;padding:0 8px;font-weight:700;display:inline-flex;align-items:center}
          .lc-status.pending{background:#fef3c7;color:#78350f}
          .lc-status.approved{background:#ecfdf3;color:#166534}
          .lc-status.rejected{background:#fef2f2;color:#991b1b}
          .lc-status.cancelled{background:#f1f5f9;color:#475569}
          .lc-status.other{background:#eef2ff;color:#3730a3}
          .lc-mail-right{border:1px solid #d0d7de;background:#fff;border-radius:12px;padding:14px;overflow:auto;max-height:72vh}
          .lc-detail{display:none}
          .lc-detail.is-active{display:block}
          .lc-filter-empty{display:none;padding:18px;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc;color:#475569;font-weight:600}
          .lc-detail-sticky{position:relative;margin:0 0 12px;padding:12px 14px 10px;border:1px solid #c7d7ea;border-radius:12px;background:linear-gradient(135deg,#f8fbff 0%,#eef6ff 55%,#ffffff 100%);box-shadow:0 6px 18px rgba(30,64,175,.08)}
          .lc-detail-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:8px;margin:0 0 8px}
          .lc-detail-title{font-size:18px;line-height:1.25;font-weight:800;color:#0f172a;letter-spacing:-.01em}
          .lc-detail-sticky .lc-status{height:26px;line-height:26px;padding:0 10px;font-size:12px}
          .lc-detail-meta-grid{display:grid;grid-template-columns:auto fit-content(48ch) minmax(220px,1fr);gap:4px 10px;margin-bottom:2px}
          .lc-detail-meta-item{display:inline-flex;align-items:center;gap:6px;min-width:0}
          .lc-detail-meta-item .dashicons{width:14px;height:14px;font-size:14px;line-height:14px;color:#64748b}
          .lc-detail-meta-item b{color:#334155;font-weight:700}
          .lc-detail-meta-links{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 0}
          .lc-detail-meta-links .button{height:32px;display:inline-flex;align-items:center;gap:5px}
          .lc-detail-meta-links .dashicons{width:14px;height:14px;font-size:14px;line-height:14px}
          .lc-queue-actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:10px 0 0;padding-top:10px;border-top:1px solid #d7e3f3}
          .lc-status-label{display:flex;align-items:center;gap:8px}
          .lc-status-label-text{font-weight:700;color:#334155}
          .lc-status-select{min-width:170px;height:36px}
          .lc-save-status-btn{height:36px;padding:0 14px;font-weight:700}
          .lc-moderated-inline{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#475569;padding:0 8px;height:32px;border:1px solid #dbe4ee;border-radius:999px;background:#fff}
          .lc-change-summary{display:grid;gap:10px}
          .lc-change-category{border:1px solid #dbe4ee;border-radius:10px;padding:10px;background:#f8fafc}
          .lc-change-category h3{margin:0 0 8px;font-size:15px;color:#0f172a}
          .lc-change-summary-card{border:1px solid #e2e8f0;border-radius:10px;padding:10px;background:#fff}
          .lc-change-summary-card h4{margin:0 0 8px;font-size:13px;color:#0f172a}
          .lc-img-change-head{display:flex;align-items:center;gap:8px;margin:0 0 8px}
          .lc-img-change-icon{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:999px;font-weight:800;font-size:14px}
          .lc-img-change-icon.remove{background:#fee2e2;color:#b91c1c}
          .lc-img-change-icon.add{background:#dcfce7;color:#15803d}
          .lc-diff-img-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(92px,1fr));gap:8px}
          .lc-diff-img-item img{width:100%;height:82px;object-fit:cover;border-radius:8px;border:1px solid #d1d5db;display:block}
          .lc-diff-img-ph{height:82px;border:1px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:11px;color:#64748b}
          .lc-diff-note{margin-top:6px;font-size:12px;color:#475569}
          .lc-text-diff-list{display:grid;gap:10px}
          .lc-text-diff-item{border:1px solid #e2e8f0;border-radius:10px;padding:10px;background:#f8fafc}
          .lc-text-diff-item b{color:#0f172a}
          .lc-text-diff-title{margin-bottom:8px}
          .lc-text-diff-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
          .lc-text-box{border:1px solid #dbe3ee;border-radius:8px;padding:8px;background:#fff}
          .lc-text-box h5{margin:0 0 6px;font-size:12px}
          .lc-text-box.old h5{color:#6b7280}
          .lc-text-box.new h5{color:#065f46}
          .lc-text-box div{white-space:pre-wrap}
          .lc-empty{padding:14px;color:#64748b}
          .lc-conflict-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.48);display:flex;align-items:center;justify-content:center;z-index:100000}
          .lc-conflict-modal{width:min(760px,92vw);max-height:84vh;overflow:auto;background:#fff;border-radius:12px;border:1px solid #d0d7de;box-shadow:0 12px 36px rgba(2,8,23,.24)}
          .lc-conflict-modal-head{padding:14px 16px;border-bottom:1px solid #e2e8f0}
          .lc-conflict-modal-head h3{margin:0;font-size:18px;line-height:1.35;color:#0f172a}
          .lc-conflict-modal-body{padding:14px 16px}
          .lc-conflict-help{margin:0 0 10px;color:#475569}
          .lc-conflict-list{display:grid;gap:8px}
          .lc-conflict-item{border:1px solid #e2e8f0;background:#f8fafc;border-radius:10px;padding:10px}
          .lc-conflict-item-head{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px}
          .lc-conflict-item-id{font-weight:700;color:#0f172a}
          .lc-conflict-item-status{display:inline-flex;align-items:center;height:20px;border-radius:999px;padding:0 8px;font-size:11px;font-weight:700;background:#fff7ed;color:#9a3412}
          .lc-conflict-item-status.approved{background:#ecfdf3;color:#166534}
          .lc-conflict-fields{margin:0;padding-left:18px;color:#334155}
          .lc-conflict-field{border:1px solid #dbe4ee;background:#fff;border-radius:8px;padding:8px 10px;margin-bottom:8px}
          .lc-conflict-field:last-child{margin-bottom:0}
          .lc-conflict-field-label{font-weight:700;color:#0f172a;margin-bottom:6px}
          .lc-conflict-field-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
          .lc-conflict-field-box{border:1px solid #e2e8f0;border-radius:8px;padding:6px 8px;background:#f8fafc}
          .lc-conflict-field-box h5{margin:0 0 4px;font-size:12px;color:#475569}
          .lc-conflict-field-box div{font-size:13px;line-height:1.4;color:#0f172a;white-space:pre-wrap}
          .lc-conflict-modal-foot{padding:12px 16px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:8px}
          @media (max-width:1200px){.lc-mail-wrap{grid-template-columns:440px 1fr}.lc-mail-item-grid,.lc-mail-head-grid{grid-template-columns:minmax(0,1fr) 78px 90px}}
          @media (max-width:1000px){.lc-mail-wrap{grid-template-columns:1fr}.lc-mail-left{max-height:38vh}.lc-mail-right{max-height:none}.lc-text-diff-grid{grid-template-columns:1fr}.lc-conflict-field-grid{grid-template-columns:1fr}.lc-mail-item-grid,.lc-mail-head-grid{grid-template-columns:minmax(0,1fr) 70px 78px}.lc-mail-col-date{display:none}.lc-search-toolbar{padding:8px}.lc-search-toolbar .lc-mail-search{flex-basis:100%}.lc-bulk-meta{margin-left:0;width:100%}.lc-detail-sticky{padding:8px 9px}.lc-detail-title{font-size:17px}.lc-queue-actions{gap:8px}.lc-detail-meta-grid{grid-template-columns:1fr}}
        </style>';
        $status_toggle_base = ['page' => $queue_page];
        if ($server_search !== '') {
            $status_toggle_base['lcq'] = $server_search;
        }
        if ($server_type !== 'all') {
            $status_toggle_base['lc_type'] = $server_type;
        }
        if ($server_location_type !== '') {
            $status_toggle_base['lc_location_type'] = $server_location_type;
        }
        echo '<div class="lc-status-toggle-row">';
        foreach ([
            'all' => 'ทั้งหมด',
            'pending' => 'รอตรวจ',
            'approved' => 'อนุมัติ',
            'rejected' => 'ไม่อนุมัติ',
            'cancelled' => 'ยกเลิก',
        ] as $status_key => $status_label) {
            $toggle_args = $status_toggle_base;
            if ($status_key !== 'all') {
                $toggle_args['lc_status'] = $status_key;
            }
            $toggle_url = add_query_arg($toggle_args, admin_url('admin.php'));
            $active_class = $server_status === $status_key ? ' is-active' : '';
            $count_value = (int) ($counts[$status_key] ?? 0);
            echo '<a class="lc-status-toggle' . esc_attr($active_class) . '" href="' . esc_url($toggle_url) . '">' . esc_html($status_label) . ' <span>' . esc_html((string) $count_value) . '</span></a>';
        }
        echo '</div>';
        echo '<form method="get" action="' . esc_url(admin_url('admin.php')) . '" class="lc-search-toolbar">';
        echo '<input type="hidden" name="page" value="' . esc_attr($queue_page) . '" />';
        echo '<input type="hidden" name="lc_status" value="' . esc_attr($server_status) . '" />';
        echo '<input id="lcMailSearch" name="lcq" class="lc-mail-search" type="search" placeholder="ค้นหา ID, ชื่อรายการ, อีเมลผู้ส่ง, วันที่..." value="' . esc_attr($server_search) . '" />';
        echo '<select name="lc_type">';
        echo '<option value="all" ' . selected($server_type, 'all', false) . '>ประเภท: ทั้งหมด</option>';
        echo '<option value="update_location" ' . selected($server_type, 'update_location', false) . '>ประเภท: สถานที่</option>';
        echo '<option value="update_course" ' . selected($server_type, 'update_course', false) . '>ประเภท: คอร์ส</option>';
        echo '</select>';
        echo '<select name="lc_location_type">';
        echo '<option value="">ประเภทสถานที่: ทั้งหมด</option>';
        foreach ($location_type_terms as $term) {
            if (!($term instanceof WP_Term)) {
                continue;
            }
            echo '<option value="' . esc_attr((string) $term->slug) . '" ' . selected($server_location_type, (string) $term->slug, false) . '>ประเภทสถานที่: ' . esc_html((string) $term->name) . '</option>';
        }
        echo '</select>';
        echo '<button type="submit" class="button button-primary" id="lcRunServerSearch">ค้นหา</button>';
        if ($server_search !== '') {
            $clear_search_url = add_query_arg(['page' => $queue_page], admin_url('admin.php'));
            echo '<a href="' . esc_url($clear_search_url) . '" class="button">ล้างค้นหา</a>';
        }
        echo '</form>';
        $active_filter_chips = [];
        $chip_base_args = [
            'page' => $queue_page,
            'lc_status' => $server_status,
            'lcq' => $server_search,
            'lc_type' => $server_type,
            'lc_location_type' => $server_location_type,
        ];
        if ($server_search !== '') {
            $remove_search_args = $chip_base_args;
            unset($remove_search_args['lcq']);
            $active_filter_chips[] = '<a class="lc-filter-chip" href="' . esc_url(add_query_arg($remove_search_args, admin_url('admin.php'))) . '">คำค้นหา: ' . esc_html($server_search) . ' <span class="lc-filter-chip-remove">×</span></a>';
        }
        if ($server_type !== 'all') {
            $type_label = $server_type === 'update_course' ? 'คอร์ส' : 'สถานที่';
            $remove_type_args = $chip_base_args;
            $remove_type_args['lc_type'] = 'all';
            $active_filter_chips[] = '<a class="lc-filter-chip" href="' . esc_url(add_query_arg($remove_type_args, admin_url('admin.php'))) . '">ประเภท: ' . esc_html($type_label) . ' <span class="lc-filter-chip-remove">×</span></a>';
        }
        if ($server_location_type !== '') {
            $location_type_label = isset($location_type_slug_map[$server_location_type]) ? $location_type_slug_map[$server_location_type] : $server_location_type;
            $remove_location_type_args = $chip_base_args;
            unset($remove_location_type_args['lc_location_type']);
            $active_filter_chips[] = '<a class="lc-filter-chip" href="' . esc_url(add_query_arg($remove_location_type_args, admin_url('admin.php'))) . '">ประเภทสถานที่: ' . esc_html($location_type_label) . ' <span class="lc-filter-chip-remove">×</span></a>';
        }
        echo '<div class="lc-active-filters-row">';
        if (!empty($active_filter_chips)) {
            echo '<div class="lc-active-filters">' . implode('', $active_filter_chips) . '</div>';
        }
        echo '</div>';

        echo '<form id="lcBulkStatusForm" class="lc-bulk-toolbar" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="lc_bulk_update_location_change_request_status">';
        echo '<input type="hidden" name="queue_page" value="' . esc_attr($queue_page) . '">';
        wp_nonce_field(self::NONCE_ACTION_EDIT_MODERATE, '_wpnonce', true, true);
        echo '<select name="new_status" id="lcBulkStatusSelect">';
        echo '<option value="">การกระทำแบบกลุ่ม</option>';
        echo '<option value="pending">เปลี่ยนเป็น: รอตรวจ</option>';
        echo '<option value="approved">เปลี่ยนเป็น: อนุมัติ</option>';
        echo '<option value="rejected">เปลี่ยนเป็น: ไม่อนุมัติ</option>';
        echo '</select>';
        echo '<select id="lcBulkRejectReasonPreset" name="reject_reason_preset" style="display:none;min-width:220px;height:34px;">';
        echo '<option value="">เลือกเหตุผลที่ไม่อนุมัติ</option>';
        foreach ($reject_reason_options as $reason_key => $reason_label) {
            echo '<option value="' . esc_attr((string) $reason_key) . '">' . esc_html((string) $reason_label) . '</option>';
        }
        echo '</select>';
        echo '<input type="text" id="lcBulkRejectReasonOther" name="reject_reason_other" placeholder="ระบุเหตุผลเพิ่มเติม" style="display:none;min-width:260px;height:34px;" />';
        echo '<button type="submit" class="button button-primary" id="lcBulkApplyBtn" disabled>Apply</button>';
        echo '<span id="lcBulkSelectionCount" style="color:#475569;font-size:12px;">เลือกแล้ว 0 รายการ</span>';
        echo '<div class="lc-bulk-meta">';
        echo '<div class="lc-active-filters-count">พบ ' . esc_html((string) number_format_i18n((int) $total_items)) . ' รายการ</div>';
        if ($pagination_links !== '') {
            echo $pagination_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</div>';
        echo '</form>';

        if (empty($rows)) {
            echo '<p>ไม่มีการแจ้งแก้ไข</p>';
            echo '</div>';
            return;
        }

        echo '<div class="lc-mail-wrap">';
        echo '<aside class="lc-mail-left" id="lcMailList">';
        echo '<div class="lc-mail-list-head">';
        echo '<div class="lc-mail-check-wrap"><input class="lc-mail-check" type="checkbox" id="lcBulkCheckAllVisible" title="เลือกทั้งหมดที่แสดง" /></div>';
        echo '<div class="lc-mail-list-head-label"><div class="lc-mail-head-grid"><span>รายการคำขอ</span><span>สถานะ</span><span>วันที่ส่ง</span></div></div>';
        echo '</div>';
        foreach ($rows as $i => $row) {
            $rid = (int) ($row['id'] ?? 0);
            $status = (string) ($row['status'] ?? 'pending');
            $status_class = in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true) ? $status : 'other';
            $location_title = (string) ($row['location_title'] ?? '');
            if ($location_title === '') {
                $location_title = '(unknown location)';
            }
            $is_active = $i === 0 ? ' is-active' : '';
            $request_type = (string) ($row['request_type'] ?? 'update_location');
            $target_type_label = (string) ($row['target_type_label'] ?? 'สถานที่');
            $target_display = $location_title;
            $submitted_date = (string) ($row['submitted_at'] ?? '');
            if ($submitted_date !== '') {
                $parts = explode(' ', $submitted_date);
                $submitted_date = (string) ($parts[0] ?? $submitted_date);
            }
            $search_blob = implode(' ', [
                '#' . (string) $rid,
                (string) $location_title,
                (string) $target_type_label,
                (string) ($row['email'] ?? ''),
                (string) ($row['submitted_at'] ?? ''),
                (string) $status,
            ]);
            echo '<div class="lc-mail-row" data-row-id="' . esc_attr((string) $rid) . '">';
            $row_checkbox_disabled = in_array($status, ['approved', 'rejected', 'cancelled'], true) ? ' disabled' : '';
            echo '<div class="lc-mail-check-wrap"><input form="lcBulkStatusForm" class="lc-mail-check" type="checkbox" name="request_ids[]" value="' . esc_attr((string) $rid) . '" data-id="' . esc_attr((string) $rid) . '"' . $row_checkbox_disabled . ' /></div>';
            echo '<button type="button" class="lc-mail-item' . esc_attr($is_active) . '" data-id="' . esc_attr((string) $rid) . '" data-status="' . esc_attr($status) . '" data-request-type="' . esc_attr($request_type) . '" data-search="' . esc_attr($search_blob) . '">';
            echo '<div class="lc-mail-item-grid">';
            echo '<div><div class="lc-mail-col-title">' . esc_html($target_display) . '</div><div class="lc-mail-col-subtitle">' . esc_html($target_type_label) . '</div></div>';
            echo '<div class="lc-mail-col-status"><span class="lc-status ' . esc_attr($status_class) . '">' . esc_html(self::status_label_th($status)) . '</span></div>';
            echo '<div class="lc-mail-col-date">' . esc_html($submitted_date) . '</div>';
            echo '</div></button>';
            echo '</div>';
        }
        echo '</aside>';

        echo '<section class="lc-mail-right" id="lcMailDetail">';
        foreach ($rows as $i => $row) {
            $rid = (int) ($row['id'] ?? 0);
            $status = (string) ($row['status'] ?? 'pending');
            $status_class = in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true) ? $status : 'other';
            $location_title = (string) ($row['location_title'] ?? '');
            if ($location_title === '') {
                $location_title = '(unknown location)';
            }
            $diff_rows = is_array($row['diff_rows'] ?? null) ? $row['diff_rows'] : [];
            $is_active = $i === 0 ? ' is-active' : '';
            $request_type = (string) ($row['request_type'] ?? 'update_location');
            $target_type_label = (string) ($row['target_type_label'] ?? 'สถานที่');
            $target_type = (string) ($row['target_type'] ?? 'location');
            $target_id = (int) ($row['target_id'] ?? 0);
            $target_key = sanitize_key($target_type) . ':' . (string) $target_id;
            $submitted_ts = (int) ($row['submitted_ts'] ?? 0);
            $change_key_map = is_array($row['change_key_map'] ?? null) ? $row['change_key_map'] : [];
            $change_map_json = wp_json_encode($change_key_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($change_map_json) || $change_map_json === '') {
                $change_map_json = '{}';
            }
            $search_blob = implode(' ', [
                '#' . (string) $rid,
                (string) $location_title,
                (string) $target_type_label,
                (string) ($row['email'] ?? ''),
                (string) ($row['submitted_at'] ?? ''),
                (string) $status,
            ]);

            echo '<article class="lc-detail' . esc_attr($is_active) . '" data-id="' . esc_attr((string) $rid) . '" data-status="' . esc_attr($status) . '" data-request-type="' . esc_attr($request_type) . '" data-search="' . esc_attr($search_blob) . '" data-target-key="' . esc_attr($target_key) . '" data-submitted-ts="' . esc_attr((string) $submitted_ts) . '" data-change-map="' . esc_attr($change_map_json) . '">';
            echo '<div class="lc-detail-sticky">';
            echo '<div class="lc-detail-head">';
            echo '<div class="lc-detail-title">#' . esc_html((string) $rid) . ' · ' . esc_html($location_title) . ' (#' . esc_html((string) ($row['location_id'] ?? 0)) . ')</div>';
            echo '<span class="lc-status ' . esc_attr($status_class) . '">' . esc_html(self::status_label_th($status)) . '</span>';
            echo '</div>';
            echo '<div class="lc-mail-item-meta lc-detail-meta-grid">';
            echo '<span class="lc-detail-meta-item"><span class="dashicons dashicons-category"></span><b>ประเภท:</b> ' . esc_html($target_type_label) . '</span>';
            echo '<span class="lc-detail-meta-item"><span class="dashicons dashicons-email"></span><b>ผู้ส่ง:</b> ' . esc_html((string) ($row['email'] ?? '')) . '</span>';
            echo '<span class="lc-detail-meta-item"><span class="dashicons dashicons-calendar-alt"></span><b>วันที่ส่ง:</b> ' . esc_html((string) ($row['submitted_at'] ?? '')) . '</span>';
            echo '</div>';
            $target_view_url = '';
            if ($target_id > 0) {
                $target_view_url = get_permalink($target_id);
                if (!is_string($target_view_url)) {
                    $target_view_url = '';
                }
            }
            $target_edit_url = $target_id > 0 ? get_edit_post_link($target_id, '') : '';
            $target_label = $request_type === 'update_course' ? 'คอร์ส' : 'สถานที่';
            if ($target_view_url !== '' || $target_edit_url !== '') {
                echo '<div class="lc-detail-meta-links">';
                if ($target_view_url !== '') {
                    echo '<a class="button button-secondary" href="' . esc_url($target_view_url) . '" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external"></span>ดูหน้า' . esc_html($target_label) . '</a>';
                }
                if (is_string($target_edit_url) && $target_edit_url !== '') {
                    echo '<a class="button" href="' . esc_url($target_edit_url) . '"><span class="dashicons dashicons-edit"></span>แก้ไขในหลังบ้าน</a>';
                }
                echo '</div>';
            }
            $moderated_inline_html = '';
            if (in_array($status, ['approved', 'rejected'], true) && (string) ($row['moderated_by_name'] ?? '') !== '') {
                $moderated_label = $status === 'rejected' ? 'ไม่อนุมัติโดย' : 'อนุมัติโดย';
                $moderated_icon = $status === 'rejected' ? 'dismiss' : 'yes-alt';
                $moderated_inline_html = '<span class="lc-moderated-inline"><span class="dashicons dashicons-' . esc_attr($moderated_icon) . '"></span><b>' . esc_html($moderated_label) . ':</b> ' . esc_html((string) $row['moderated_by_name']) . '</span>';
            }
            if ($status === 'cancelled') {
                $cancelled_by = trim((string) ($row['cancelled_by'] ?? ''));
                $cancelled_at = trim((string) ($row['cancelled_at'] ?? ''));
                $cancelled_note = $cancelled_by !== '' ? $cancelled_by : '-';
                if ($cancelled_at !== '') {
                    $cancelled_note .= ' · ' . $cancelled_at;
                }
                $moderated_inline_html = '<span class="lc-moderated-inline"><span class="dashicons dashicons-dismiss"></span><b>ยกเลิกโดย:</b> ' . esc_html($cancelled_note) . '</span>';
            }
            $current_reject_reason = (string) get_post_meta($rid, '_lc_reject_reason', true);
            $current_reject_reason_preset = isset($reject_reason_option_lookup[$current_reject_reason])
                ? (string) $reject_reason_option_lookup[$current_reject_reason]
                : '';
            $current_reject_reason_other = $current_reject_reason_preset === '' ? $current_reject_reason : '';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-queue-actions lc-status-form" data-status-form>';
            echo '<input type="hidden" name="action" value="lc_update_location_change_request_status">';
            echo '<input type="hidden" name="request_id" value="' . esc_attr((string) $rid) . '">';
            echo '<input type="hidden" name="queue_page" value="' . esc_attr($queue_page) . '">';
            wp_nonce_field(self::NONCE_ACTION_EDIT_MODERATE, '_wpnonce', true, true);
            echo '<label class="lc-status-label">';
            echo '<span class="lc-status-label-text">สถานะ</span>';
            $status_select_disabled = in_array($status, ['approved', 'rejected', 'cancelled'], true) ? ' disabled' : '';
            echo '<select name="new_status" data-status-select class="lc-status-select"' . $status_select_disabled . '>';
            if ($status === 'cancelled') {
                echo '<option value="cancelled" selected>ยกเลิก</option>';
            } elseif ($status === 'approved') {
                echo '<option value="approved" selected>อนุมัติ</option>';
            } elseif ($status === 'rejected') {
                echo '<option value="rejected" selected>ไม่อนุมัติ</option>';
            } else {
                echo '<option value="pending" ' . selected($status, 'pending', false) . '>รอตรวจสอบ</option>';
                echo '<option value="approved" ' . selected($status, 'approved', false) . '>อนุมัติ</option>';
                echo '<option value="rejected" ' . selected($status, 'rejected', false) . '>ไม่อนุมัติ</option>';
            }
            echo '</select>';
            echo '</label>';
            echo '<button class="button button-primary lc-save-status-btn" type="submit"' . $status_select_disabled . '>บันทึกสถานะ</button>';
            if ($moderated_inline_html !== '') {
                echo $moderated_inline_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            if ($status === 'cancelled') {
                echo '<div style="width:100%;margin-top:8px;color:#64748b;font-weight:600;">คำขอนี้ถูกยกเลิกโดยผู้ส่งแล้ว ไม่สามารถอนุมัติหรือไม่อนุมัติได้</div>';
            } elseif ($status === 'approved') {
                echo '<div style="width:100%;margin-top:8px;color:#166534;font-weight:600;">คำขอนี้ถูกอนุมัติและนำข้อมูลไปใช้แล้ว ไม่สามารถเปลี่ยนกลับเป็นสถานะอื่นได้</div>';
            } elseif ($status === 'rejected') {
                echo '<div style="width:100%;margin-top:8px;color:#991b1b;font-weight:600;">คำขอนี้ถูกไม่อนุมัติและปิดเคสแล้ว หากต้องการดำเนินการต่อให้ผู้ส่งทำคำขอใหม่</div>';
            }
            echo '<div data-reject-reason-wrap style="display:' . ($status === 'rejected' ? 'block' : 'none') . ';width:100%;margin-top:8px;">';
            echo '<label style="display:block;font-weight:600;margin-bottom:4px;">เหตุผลที่ไม่อนุมัติ</label>';
            echo '<select name="reject_reason_preset" data-reject-reason-preset style="width:100%;max-width:420px;height:36px;">';
            echo '<option value="">เลือกเหตุผลที่ไม่อนุมัติ</option>';
            foreach ($reject_reason_options as $reason_key => $reason_label) {
                echo '<option value="' . esc_attr((string) $reason_key) . '" ' . selected($current_reject_reason_preset, (string) $reason_key, false) . '>' . esc_html((string) $reason_label) . '</option>';
            }
            echo '</select>';
            echo '<textarea name="reject_reason_other" data-reject-reason-other rows="3" style="display:' . ($current_reject_reason_preset === 'other' ? 'block' : 'none') . ';width:100%;max-width:760px;margin-top:8px;" placeholder="ระบุเหตุผลเพิ่มเติม">' . esc_textarea($current_reject_reason_other) . '</textarea>';
            echo '</div>';
            echo '</form>';
            echo '</div>';

            if (empty($diff_rows)) {
                echo '<p class="lc-empty"><em>ไม่พบการเปลี่ยนแปลงของฟิลด์</em></p>';
            } else {
                $remove_row = null;
                $add_row = null;
                $text_rows = [];
                foreach ($diff_rows as $d) {
                    $field = (string) ($d['field'] ?? '');
                    if ($field === 'Remove Images') {
                        $remove_row = $d;
                        continue;
                    }
                    if ($field === 'Add Images') {
                        $add_row = $d;
                        continue;
                    }
                    if ($field === 'Gallery (Before / After)') {
                        continue;
                    }
                    $text_rows[] = $d;
                }

                echo '<div class="lc-change-summary">';
                $location_text_rows = [];
                $session_text_rows = [];
                if (is_array($remove_row)) {
                    $ids = array_values(array_filter(array_map('intval', (array) ($remove_row['new_ids'] ?? []))));
                    if (!empty($ids)) {
                        $remove_html = '<div class="lc-change-summary-card"><div class="lc-img-change-head"><span class="lc-img-change-icon remove">-</span><h4 style="margin:0;">Images To Remove (' . esc_html((string) count($ids)) . ')</h4></div>' . self::render_diff_cell_html('', $ids) . '</div>';
                    } else {
                        $remove_html = '';
                    }
                } else {
                    $remove_html = '';
                }
                if (is_array($add_row)) {
                    $ids = array_values(array_filter(array_map('intval', (array) ($add_row['new_ids'] ?? []))));
                    if (!empty($ids)) {
                        $add_html = '<div class="lc-change-summary-card"><div class="lc-img-change-head"><span class="lc-img-change-icon add">+</span><h4 style="margin:0;">Images To Add (' . esc_html((string) count($ids)) . ')</h4></div>' . self::render_diff_cell_html('', $ids) . '</div>';
                    } else {
                        $add_html = '';
                    }
                } else {
                    $add_html = '';
                }
                if (!empty($text_rows)) {
                    foreach ($text_rows as $d) {
                        $section = (string) ($d['section'] ?? '-');
                        if (stripos($section, 'location') === 0) {
                            $location_text_rows[] = $d;
                            continue;
                        }
                        if (stripos($section, 'session') === 0) {
                            $session_text_rows[] = $d;
                            continue;
                        }
                        $location_text_rows[] = $d;
                    }
                }

                echo '<section class="lc-change-category"><h3>Location Details</h3>';
                if (empty($location_text_rows)) {
                    echo '<div class="lc-diff-note">ไม่มีการแก้ไขข้อมูลสถานที่</div>';
                } else {
                    echo '<div class="lc-text-diff-list">';
                    foreach ($location_text_rows as $d) {
                        $section = (string) ($d['section'] ?? '-');
                        $field = (string) ($d['field'] ?? '-');
                        $old = (string) ($d['old'] ?? '-');
                        $new = (string) ($d['new'] ?? '-');
                        echo '<div class="lc-text-diff-item">';
                        echo '<div class="lc-text-diff-title"><b>' . esc_html($section . ' / ' . $field) . '</b></div>';
                        echo '<div class="lc-text-diff-grid">';
                        echo '<div class="lc-text-box old"><h5>ก่อนแก้</h5><div>' . esc_html($old) . '</div></div>';
                        echo '<div class="lc-text-box new"><h5>หลังแก้</h5><div>' . esc_html($new) . '</div></div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
                echo '</section>';

                echo '<section class="lc-change-category"><h3>Location Images</h3>';
                if ($remove_html === '' && $add_html === '') {
                    echo '<div class="lc-diff-note">ไม่มีการแก้ไขรูปภาพ</div>';
                } else {
                    echo $remove_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $add_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                echo '</section>';

                echo '<section class="lc-change-category"><h3>Session Details</h3>';
                if (empty($session_text_rows)) {
                    echo '<div class="lc-diff-note">ไม่มีการแก้ไขข้อมูล Session</div>';
                } else {
                    echo '<div class="lc-text-diff-list">';
                    foreach ($session_text_rows as $d) {
                        $section = (string) ($d['section'] ?? '-');
                        $field = (string) ($d['field'] ?? '-');
                        $old = (string) ($d['old'] ?? '-');
                        $new = (string) ($d['new'] ?? '-');
                        echo '<div class="lc-text-diff-item">';
                        echo '<div class="lc-text-diff-title"><b>' . esc_html($section . ' / ' . $field) . '</b></div>';
                        echo '<div class="lc-text-diff-grid">';
                        echo '<div class="lc-text-box old"><h5>ก่อนแก้</h5><div>' . esc_html($old) . '</div></div>';
                        echo '<div class="lc-text-box new"><h5>หลังแก้</h5><div>' . esc_html($new) . '</div></div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
                echo '</section>';
                echo '</div>';
            }
            echo '</article>';
        }
        echo '<div id="lcFilterEmptyNotice" class="lc-filter-empty">ไม่มีการแจ้งแก้ไข</div>';
        echo '</section>';
        echo '</div>';
        if ($pagination_links !== '') {
            echo $pagination_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        echo '<script>
          (function(){
            const list = document.getElementById("lcMailList");
            const detailWrap = document.getElementById("lcMailDetail");
            const bulkForm = document.getElementById("lcBulkStatusForm");
            const bulkStatusSelect = document.getElementById("lcBulkStatusSelect");
            const bulkRejectPreset = document.getElementById("lcBulkRejectReasonPreset");
            const bulkRejectOther = document.getElementById("lcBulkRejectReasonOther");
            const bulkSelectionCount = document.getElementById("lcBulkSelectionCount");
            const bulkApplyBtn = document.getElementById("lcBulkApplyBtn");
            const checkAllVisible = document.getElementById("lcBulkCheckAllVisible");
            if(!list || !detailWrap){return;}
            const items = Array.from(list.querySelectorAll(".lc-mail-item"));
            const checks = Array.from(list.querySelectorAll(".lc-mail-row .lc-mail-check"));
            const details = Array.from(detailWrap.querySelectorAll(".lc-detail"));
            const filterEmptyNotice = document.getElementById("lcFilterEmptyNotice");
            const statusForms = Array.from(detailWrap.querySelectorAll("[data-status-form]"));
            const removeConflictModal = function(){
              const existing = document.getElementById("lcConflictModalBackdrop");
              if(existing){
                existing.remove();
              }
            };
            const openConflictModal = function(conflicts, onConfirm){
              removeConflictModal();
              const backdrop = document.createElement("div");
              backdrop.id = "lcConflictModalBackdrop";
              backdrop.className = "lc-conflict-modal-backdrop";
              const modal = document.createElement("div");
              modal.className = "lc-conflict-modal";

              const head = document.createElement("div");
              head.className = "lc-conflict-modal-head";
              const title = document.createElement("h3");
              title.textContent = "พบคำขอก่อนหน้าที่แก้ไขฟิลด์ซ้ำกัน";
              head.appendChild(title);

              const body = document.createElement("div");
              body.className = "lc-conflict-modal-body";
              const help = document.createElement("p");
              help.className = "lc-conflict-help";
              help.textContent = "ระบบพบคำขอก่อนหน้าของรายการเดียวกันที่แก้ไขฟิลด์ซ้ำกัน กรุณาตรวจสอบก่อนอนุมัติ:";
              body.appendChild(help);
              const list = document.createElement("div");
              list.className = "lc-conflict-list";
              conflicts.forEach((item) => {
                const wrap = document.createElement("div");
                wrap.className = "lc-conflict-item";
                const itemHead = document.createElement("div");
                itemHead.className = "lc-conflict-item-head";
                const idEl = document.createElement("span");
                idEl.className = "lc-conflict-item-id";
                idEl.textContent = "#" + String(item.id || "");
                const st = String(item.status || "pending");
                const statusEl = document.createElement("span");
                statusEl.className = "lc-conflict-item-status" + (st === "approved" ? " approved" : "");
                statusEl.textContent = st.charAt(0).toUpperCase() + st.slice(1);
                itemHead.appendChild(idEl);
                itemHead.appendChild(statusEl);
                wrap.appendChild(itemHead);
                const fieldList = document.createElement("div");
                fieldList.className = "lc-conflict-fields";
                (Array.isArray(item.fields) ? item.fields : []).forEach((field) => {
                  const fieldWrap = document.createElement("div");
                  fieldWrap.className = "lc-conflict-field";
                  const label = document.createElement("div");
                  label.className = "lc-conflict-field-label";
                  label.textContent = String(field.label || "-");
                  fieldWrap.appendChild(label);
                  const grid = document.createElement("div");
                  grid.className = "lc-conflict-field-grid";

                  const oldBox = document.createElement("div");
                  oldBox.className = "lc-conflict-field-box";
                  const oldTitle = document.createElement("h5");
                  oldTitle.textContent = "คำขอก่อนหน้า";
                  const oldVal = document.createElement("div");
                  oldVal.textContent = String(field.otherOld || "-") + " -> " + String(field.otherNew || "-");
                  oldBox.appendChild(oldTitle);
                  oldBox.appendChild(oldVal);

                  const newBox = document.createElement("div");
                  newBox.className = "lc-conflict-field-box";
                  const newTitle = document.createElement("h5");
                  newTitle.textContent = "คำขอนี้";
                  const newVal = document.createElement("div");
                  newVal.textContent = String(field.currentOld || "-") + " -> " + String(field.currentNew || "-");
                  newBox.appendChild(newTitle);
                  newBox.appendChild(newVal);

                  grid.appendChild(oldBox);
                  grid.appendChild(newBox);
                  fieldWrap.appendChild(grid);
                  fieldList.appendChild(fieldWrap);
                });
                wrap.appendChild(fieldList);
                list.appendChild(wrap);
              });
              body.appendChild(list);

              const foot = document.createElement("div");
              foot.className = "lc-conflict-modal-foot";
              const cancelBtn = document.createElement("button");
              cancelBtn.type = "button";
              cancelBtn.className = "button";
              cancelBtn.textContent = "ยกเลิก";
              const confirmBtn = document.createElement("button");
              confirmBtn.type = "button";
              confirmBtn.className = "button button-primary";
              confirmBtn.textContent = "อนุมัติต่อ";
              foot.appendChild(cancelBtn);
              foot.appendChild(confirmBtn);

              modal.appendChild(head);
              modal.appendChild(body);
              modal.appendChild(foot);
              backdrop.appendChild(modal);
              document.body.appendChild(backdrop);

              const close = function(){
                removeConflictModal();
              };
              cancelBtn.addEventListener("click", close);
              backdrop.addEventListener("click", function(event){
                if(event.target === backdrop){
                  close();
                }
              });
              confirmBtn.addEventListener("click", function(){
                close();
                if(typeof onConfirm === "function"){
                  onConfirm();
                }
              });
            };
            const setActive = function(id){
              items.forEach((el) => el.classList.toggle("is-active", el.getAttribute("data-id") === id));
              details.forEach((el) => el.classList.toggle("is-active", el.getAttribute("data-id") === id));
            };
            const clearActive = function(){
              items.forEach((el) => el.classList.remove("is-active"));
              details.forEach((el) => el.classList.remove("is-active"));
            };
            const getSelectableChecks = function(){
              return checks.filter((check) => !check.disabled);
            };
            const firstItem = items.length > 0 ? items[0] : null;
            const syncBulkApplyAvailability = function(){
              const selected = getSelectableChecks().filter((check) => check.checked).length;
              const hasAction = !!(bulkStatusSelect && String(bulkStatusSelect.value || "").trim() !== "");
              if(bulkApplyBtn){
                bulkApplyBtn.disabled = selected <= 0 || !hasAction;
              }
            };
            const syncCheckAllVisibleState = function(){
              if(!checkAllVisible){ return; }
              const visibleChecks = getSelectableChecks().filter((check) => {
                const row = check.closest(".lc-mail-row");
                return !!row && row.style.display !== "none";
              });
              if(visibleChecks.length === 0){
                checkAllVisible.checked = false;
                checkAllVisible.indeterminate = false;
                checkAllVisible.disabled = true;
                return;
              }
              checkAllVisible.disabled = false;
              const checkedCount = visibleChecks.filter((check) => check.checked).length;
              checkAllVisible.checked = checkedCount > 0 && checkedCount === visibleChecks.length;
              checkAllVisible.indeterminate = checkedCount > 0 && checkedCount < visibleChecks.length;
            };
            const updateBulkSelectionCount = function(){
              const selected = getSelectableChecks().filter((check) => check.checked).length;
              if(bulkSelectionCount){
                bulkSelectionCount.textContent = "เลือกแล้ว " + String(selected) + " รายการ";
              }
              syncBulkApplyAvailability();
              syncCheckAllVisibleState();
            };
            const syncBulkRejectVisibility = function(){
              if(!bulkStatusSelect || !bulkRejectPreset || !bulkRejectOther){ return; }
              const isRejected = bulkStatusSelect.value === "rejected";
              const useOther = isRejected && bulkRejectPreset.value === "other";
              bulkRejectPreset.style.display = isRejected ? "" : "none";
              bulkRejectPreset.required = !!isRejected;
              bulkRejectOther.style.display = useOther ? "" : "none";
              bulkRejectOther.required = !!useOther;
              syncBulkApplyAvailability();
            };
            items.forEach((item) => item.addEventListener("click", function(){
              const id = item.getAttribute("data-id") || "";
              if(id){ setActive(id); }
            }));
            checks.forEach((check) => check.addEventListener("change", updateBulkSelectionCount));
            if(checkAllVisible){
              checkAllVisible.addEventListener("change", function(){
                const shouldCheck = !!checkAllVisible.checked;
                getSelectableChecks().forEach((check) => {
                  const row = check.closest(".lc-mail-row");
                  if(!row || row.style.display === "none"){ return; }
                  check.checked = shouldCheck;
                });
                updateBulkSelectionCount();
              });
            }
            if(bulkStatusSelect){
              bulkStatusSelect.addEventListener("change", syncBulkRejectVisibility);
            }
            if(bulkRejectPreset){
              bulkRejectPreset.addEventListener("change", syncBulkRejectVisibility);
            }
            if(bulkForm){
              bulkForm.addEventListener("submit", function(event){
                const selected = getSelectableChecks().filter((check) => check.checked).length;
                const action = bulkStatusSelect ? String(bulkStatusSelect.value || "").trim() : "";
                if(action === ""){
                  event.preventDefault();
                  window.alert("กรุณาเลือกการกระทำแบบกลุ่ม");
                  return;
                }
                if(selected <= 0){
                  event.preventDefault();
                  window.alert("กรุณาเลือกรายการอย่างน้อย 1 รายการ");
                  return;
                }
                if(bulkStatusSelect && bulkStatusSelect.value === "rejected"){
                  const preset = bulkRejectPreset ? String(bulkRejectPreset.value || "").trim() : "";
                  const other = bulkRejectOther ? String(bulkRejectOther.value || "").trim() : "";
                  if(preset === ""){
                    event.preventDefault();
                    window.alert("กรุณาเลือกเหตุผลสำหรับการไม่อนุมัติ");
                    if(bulkRejectPreset){ bulkRejectPreset.focus(); }
                    return;
                  }
                  if(preset === "other" && other === ""){
                    event.preventDefault();
                    window.alert("กรุณาระบุเหตุผลเพิ่มเติม");
                    if(bulkRejectOther){ bulkRejectOther.focus(); }
                  }
                }
              });
            }
            syncBulkRejectVisibility();
            updateBulkSelectionCount();
            if(firstItem){
              const firstId = firstItem.getAttribute("data-id") || "";
              if(firstId){
                setActive(firstId);
              }
              if(filterEmptyNotice){
                filterEmptyNotice.style.display = "none";
              }
            } else if(filterEmptyNotice){
              filterEmptyNotice.style.display = "block";
            }
            statusForms.forEach((form) => {
              const sel = form.querySelector("[data-status-select]");
              const wrap = form.querySelector("[data-reject-reason-wrap]");
              const presetSelect = form.querySelector("[data-reject-reason-preset]");
              const otherField = form.querySelector("[data-reject-reason-other]");
              if(!sel || !wrap){ return; }
              const parseChangeMap = function(raw){
                if(!raw){ return {}; }
                try {
                  const parsed = JSON.parse(raw);
                  return (parsed && typeof parsed === "object") ? parsed : {};
                } catch (error){
                  return {};
                }
              };
              const mapEntry = function(map, key){
                const raw = map && Object.prototype.hasOwnProperty.call(map, key) ? map[key] : null;
                if(!raw || typeof raw !== "object"){
                  return {
                    label: String(raw || key),
                    old: "-",
                    new: "-"
                  };
                }
                return {
                  label: String(raw.label || key),
                  old: String(raw.old || "-"),
                  new: String(raw.new || "-")
                };
              };
              const sync = function(){
                const isRejected = sel.value === "rejected";
                wrap.style.display = isRejected ? "block" : "none";
                const useOther = isRejected && presetSelect && presetSelect.value === "other";
                if(presetSelect){
                  presetSelect.required = isRejected;
                }
                if(otherField){
                  otherField.style.display = useOther ? "block" : "none";
                  otherField.required = !!useOther;
                }
              };
              sel.addEventListener("change", sync);
              if(presetSelect){
                presetSelect.addEventListener("change", sync);
              }
              form.addEventListener("submit", function(event){
                if(sel.value === "rejected"){
                  const preset = presetSelect ? String(presetSelect.value || "").trim() : "";
                  const other = otherField ? String(otherField.value || "").trim() : "";
                  if(preset === ""){
                    event.preventDefault();
                    window.alert("กรุณาเลือกเหตุผลสำหรับการไม่อนุมัติ");
                    if(presetSelect){ presetSelect.focus(); }
                    return;
                  }
                  if(preset === "other" && other === ""){
                    event.preventDefault();
                    window.alert("กรุณาระบุเหตุผลเพิ่มเติม");
                    if(otherField){ otherField.focus(); }
                    return;
                  }
                }
                if(form.dataset.conflictConfirmed === "1"){
                  form.dataset.conflictConfirmed = "";
                  return;
                }
                if(sel.value !== "approved"){ return; }
                const currentDetail = form.closest(".lc-detail");
                if(!currentDetail){ return; }
                const currentStatus = String(currentDetail.getAttribute("data-status") || "");
                if(currentStatus === "approved"){ return; }
                const currentTargetKey = String(currentDetail.getAttribute("data-target-key") || "");
                if(currentTargetKey === ""){ return; }
                const currentSubmittedTs = parseInt(currentDetail.getAttribute("data-submitted-ts") || "0", 10);
                const currentChangeMap = parseChangeMap(currentDetail.getAttribute("data-change-map") || "");
                const currentKeys = Object.keys(currentChangeMap);
                if(currentKeys.length === 0){ return; }
                const currentId = String(currentDetail.getAttribute("data-id") || "");
                const conflicts = [];
                details.forEach((otherDetail) => {
                  if(!otherDetail || otherDetail === currentDetail){ return; }
                  const otherId = String(otherDetail.getAttribute("data-id") || "");
                  if(otherId === currentId){ return; }
                  const otherStatus = String(otherDetail.getAttribute("data-status") || "");
                  if(otherStatus === "rejected"){ return; }
                  const otherTargetKey = String(otherDetail.getAttribute("data-target-key") || "");
                  if(otherTargetKey !== currentTargetKey){ return; }
                  const otherSubmittedTs = parseInt(otherDetail.getAttribute("data-submitted-ts") || "0", 10);
                  if(currentSubmittedTs > 0 && otherSubmittedTs > 0 && otherSubmittedTs >= currentSubmittedTs){ return; }
                  const otherChangeMap = parseChangeMap(otherDetail.getAttribute("data-change-map") || "");
                  const overlapKeys = Object.keys(otherChangeMap).filter((key) => Object.prototype.hasOwnProperty.call(currentChangeMap, key));
                  if(overlapKeys.length === 0){ return; }
                  const fields = overlapKeys.map((key) => {
                    const currentEntry = mapEntry(currentChangeMap, key);
                    const otherEntry = mapEntry(otherChangeMap, key);
                    return {
                      label: String(currentEntry.label || otherEntry.label || key),
                      currentOld: currentEntry.old,
                      currentNew: currentEntry.new,
                      otherOld: otherEntry.old,
                      otherNew: otherEntry.new
                    };
                  });
                  conflicts.push({
                    id: otherId,
                    status: otherStatus || "pending",
                    fields: fields
                  });
                });
                if(conflicts.length === 0){ return; }
                event.preventDefault();
                const showConflicts = conflicts.slice(0, 12);
                openConflictModal(showConflicts, function(){
                  form.dataset.conflictConfirmed = "1";
                  if(typeof form.requestSubmit === "function"){
                    form.requestSubmit();
                  } else {
                    form.submit();
                  }
                });
              });
              sync();
            });
          })();
        </script>';
        echo '</div>';
    }

    public static function handle_update_location_change_request_status() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'lc-public-place-photo-upload'));
        }
        check_admin_referer(self::NONCE_ACTION_EDIT_MODERATE);
        $queue_page = isset($_POST['queue_page']) ? sanitize_key((string) wp_unslash($_POST['queue_page'])) : 'lc-location-edit-queue';
        if (!in_array($queue_page, ['lc-location-edit-queue'], true)) {
            $queue_page = 'lc-location-edit-queue';
        }
        $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
        if ($request_id <= 0 || !self::is_change_request_post_type(get_post_type($request_id))) {
            wp_safe_redirect(admin_url('admin.php?page=' . $queue_page));
            exit;
        }

        $new_status = isset($_POST['new_status']) ? sanitize_key((string) wp_unslash($_POST['new_status'])) : '';
        $allowed = ['pending', 'approved', 'rejected'];
        if (!in_array($new_status, $allowed, true)) {
            wp_safe_redirect(admin_url('admin.php?page=' . $queue_page));
            exit;
        }

        $reject_reason = self::resolve_reject_reason_from_post($_POST);
        if ($new_status === 'rejected' && $reject_reason === '') {
            wp_die(__('Please provide rejection reason.', 'lc-public-place-photo-upload'));
        }

        $result = self::update_location_change_request_status_internal($request_id, $new_status, $reject_reason);
        if (is_wp_error($result)) {
            self::push_admin_queue_notice((string) $result->get_error_message(), 'error');
            wp_safe_redirect(admin_url('admin.php?page=' . $queue_page));
            exit;
        }
        if (is_array($result) && !empty($result['stale_conflict']) && $new_status === 'approved') {
            self::push_admin_queue_notice('รายการนี้ถูกอนุมัติแล้ว แต่พบว่า source data เปลี่ยนหลังการส่งคำขอ (อนุมัติแบบ admin override)', 'warning');
        }

        wp_safe_redirect(admin_url('admin.php?page=' . $queue_page));
        exit;
    }

    public static function handle_bulk_update_location_change_request_status() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'lc-public-place-photo-upload'));
        }
        check_admin_referer(self::NONCE_ACTION_EDIT_MODERATE);
        $queue_page = isset($_POST['queue_page']) ? sanitize_key((string) wp_unslash($_POST['queue_page'])) : 'lc-location-edit-queue';
        if (!in_array($queue_page, ['lc-location-edit-queue'], true)) {
            $queue_page = 'lc-location-edit-queue';
        }

        $new_status = isset($_POST['new_status']) ? sanitize_key((string) wp_unslash($_POST['new_status'])) : '';
        $allowed = ['pending', 'approved', 'rejected'];
        if (!in_array($new_status, $allowed, true)) {
            wp_safe_redirect(admin_url('admin.php?page=' . $queue_page));
            exit;
        }

        $reject_reason = self::resolve_reject_reason_from_post($_POST);
        if ($new_status === 'rejected' && $reject_reason === '') {
            wp_die(__('Please provide rejection reason.', 'lc-public-place-photo-upload'));
        }

        $request_ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['request_ids'] ?? [])))));
        if (empty($request_ids)) {
            wp_safe_redirect(add_query_arg([
                'page' => $queue_page,
                'bulk_updated' => 0,
                'bulk_failed' => 0,
            ], admin_url('admin.php')));
            exit;
        }

        $updated = 0;
        $failed = 0;
        $stale_overrides = 0;
        foreach ($request_ids as $request_id) {
            if ($request_id <= 0 || !self::is_change_request_post_type(get_post_type($request_id))) {
                $failed++;
                continue;
            }
            $result = self::update_location_change_request_status_internal($request_id, $new_status, $reject_reason);
            if (is_wp_error($result)) {
                $failed++;
                continue;
            }
            if (is_array($result) && !empty($result['stale_conflict']) && $new_status === 'approved') {
                $stale_overrides++;
            }
            $updated++;
        }
        if ($stale_overrides > 0) {
            self::push_admin_queue_notice(
                sprintf('อนุมัติแบบ admin override แล้ว %d รายการที่มี source data เปลี่ยนหลังส่งคำขอ', $stale_overrides),
                'warning'
            );
        }

        wp_safe_redirect(add_query_arg([
            'page' => $queue_page,
            'bulk_updated' => (int) $updated,
            'bulk_failed' => (int) $failed,
        ], admin_url('admin.php')));
        exit;
    }

    private static function approval_lock_timeout_seconds() {
        return 120;
    }

    private static function approval_lock_option_key($request_id) {
        return 'lc_loc_change_approve_lock_' . md5((string) ((int) $request_id));
    }

    private static function acquire_request_approval_lock($request_id) {
        $request_id = (int) $request_id;
        if ($request_id <= 0) {
            return new WP_Error('invalid_request', __('Invalid request.', 'lc-public-place-photo-upload'));
        }
        $option_key = self::approval_lock_option_key($request_id);
        $now = time();
        $token = (string) $now . ':' . wp_generate_password(16, false, false);
        $payload = [
            'token' => $token,
            'created_at' => $now,
        ];
        if (add_option($option_key, $payload, '', 'no')) {
            return $token;
        }

        $existing = get_option($option_key, null);
        $existing_created_at = is_array($existing) ? (int) ($existing['created_at'] ?? 0) : 0;
        if ($existing_created_at > 0 && ($now - $existing_created_at) > self::approval_lock_timeout_seconds()) {
            delete_option($option_key);
            if (add_option($option_key, $payload, '', 'no')) {
                return $token;
            }
        }

        return new WP_Error('approval_in_progress', __('มีผู้ดูแลระบบคนอื่นกำลังอนุมัติคำขอนี้อยู่ กรุณาลองใหม่อีกครั้ง', 'lc-public-place-photo-upload'));
    }

    private static function release_request_approval_lock($request_id, $token) {
        $request_id = (int) $request_id;
        $token = (string) $token;
        if ($request_id <= 0 || $token === '') {
            return;
        }
        $option_key = self::approval_lock_option_key($request_id);
        $existing = get_option($option_key, null);
        if (is_array($existing) && (string) ($existing['token'] ?? '') === $token) {
            delete_option($option_key);
        }
    }

    private static function build_new_session_request_key($request_id, $flow, $index, $course_id, $location_id, $row) {
        $request_id = (int) $request_id;
        if ($request_id <= 0) {
            return '';
        }
        $row = is_array($row) ? $row : [];
        $payload = [
            'request_id' => $request_id,
            'flow' => sanitize_key((string) $flow),
            'index' => (int) $index,
            'course_id' => (int) $course_id,
            'location_id' => (int) $location_id,
            'time_period' => sanitize_textarea_field(self::normalize_multiline_text((string) ($row['time_period'] ?? ''))),
            'session_details' => sanitize_textarea_field(self::normalize_multiline_text((string) ($row['session_details'] ?? ''))),
            'apply_url' => esc_url_raw((string) ($row['apply_url'] ?? '')),
            'start_date' => sanitize_text_field((string) ($row['start_date'] ?? '')),
            'end_date' => sanitize_text_field((string) ($row['end_date'] ?? '')),
            'reg_start' => sanitize_text_field((string) ($row['reg_start'] ?? '')),
            'reg_end' => sanitize_text_field((string) ($row['reg_end'] ?? '')),
        ];
        $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            $json = serialize($payload);
        }
        return md5($json);
    }

    private static function find_existing_new_session_by_request_key($request_key) {
        $request_key = sanitize_text_field((string) $request_key);
        if ($request_key === '') {
            return 0;
        }
        $ids = get_posts([
            'post_type' => 'session',
            'post_status' => ['publish', 'future', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                ['key' => '_lc_created_from_request_key', 'value' => $request_key, 'compare' => '='],
            ],
            'no_found_rows' => true,
        ]);
        if (empty($ids)) {
            return 0;
        }
        return (int) $ids[0];
    }

    private static function update_location_change_request_status_internal($request_id, $new_status, $reject_reason = '') {
        $request_id = (int) $request_id;
        $new_status = sanitize_key((string) $new_status);
        $reject_reason = sanitize_textarea_field((string) $reject_reason);
        $allowed = ['pending', 'approved', 'rejected', 'cancelled'];
        if (!in_array($new_status, $allowed, true)) {
            return new WP_Error('invalid_status', __('Invalid status.', 'lc-public-place-photo-upload'));
        }
        if ($request_id <= 0 || !self::is_change_request_post_type(get_post_type($request_id))) {
            return new WP_Error('invalid_request', __('Invalid request.', 'lc-public-place-photo-upload'));
        }

        $current_status = (string) get_post_meta($request_id, '_lc_change_status', true);
        if ($current_status === '' || !in_array($current_status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $current_status = 'pending';
        }
        if ($current_status === 'cancelled' && $new_status !== 'cancelled') {
            return new WP_Error('request_cancelled', __('คำขอนี้ถูกยกเลิกโดยผู้ส่งแล้ว และไม่สามารถเปลี่ยนสถานะได้', 'lc-public-place-photo-upload'));
        }
        if ($current_status === 'rejected' && $new_status !== 'rejected') {
            return new WP_Error('rejected_is_terminal', __('คำขอนี้ถูกไม่อนุมัติและปิดเคสแล้ว หากต้องการดำเนินการต่อให้ผู้ส่งทำคำขอใหม่', 'lc-public-place-photo-upload'));
        }
        if ($current_status === 'approved' && $new_status !== 'approved') {
            return new WP_Error('approved_is_terminal', __('คำขอนี้ถูกอนุมัติและนำไปใช้แล้ว ไม่สามารถเปลี่ยนกลับเป็นสถานะอื่นได้', 'lc-public-place-photo-upload'));
        }
        $location_id = (int) get_post_meta($request_id, '_lc_location_id', true);
        $course_id = (int) get_post_meta($request_id, '_lc_course_id', true);
        $stale_conflict = false;
        $approval_lock_token = '';

        try {
            if ($new_status === 'approved' && $current_status !== 'approved') {
                $lock_result = self::acquire_request_approval_lock($request_id);
                if (is_wp_error($lock_result)) {
                    return $lock_result;
                }
                $approval_lock_token = (string) $lock_result;

                $current_status_after_lock = (string) get_post_meta($request_id, '_lc_change_status', true);
                if ($current_status_after_lock === '' || !in_array($current_status_after_lock, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
                    $current_status_after_lock = 'pending';
                }
                if ($current_status_after_lock === 'approved') {
                    return ['updated' => false, 'stale_conflict' => false];
                }
                if ($current_status_after_lock === 'cancelled') {
                    return new WP_Error('request_cancelled', __('คำขอนี้ถูกยกเลิกโดยผู้ส่งแล้ว และไม่สามารถเปลี่ยนสถานะได้', 'lc-public-place-photo-upload'));
                }

                $payload_json = (string) get_post_meta($request_id, '_lc_change_payload', true);
                $payload = json_decode($payload_json, true);
                if (!is_array($payload)) {
                    $payload = [];
                }
                $request_type = self::get_request_type_for_request($request_id, $payload);
                $stale_error = self::validate_request_snapshot_before_approve($request_id, $request_type, $payload);
                if (is_wp_error($stale_error)) {
                    if ($stale_error->get_error_code() === 'stale_request_conflict') {
                        $stale_conflict = true;
                        update_post_meta($request_id, '_lc_stale_conflict_message', (string) $stale_error->get_error_message());
                    } else {
                        return $stale_error;
                    }
                }
                if ($request_type === 'update_course') {
                    if ($course_id <= 0 || get_post_type($course_id) !== 'course') {
                        return new WP_Error('invalid_course', __('Invalid course for this request.', 'lc-public-place-photo-upload'));
                    }
                    self::apply_course_change_payload($course_id, $payload, $request_id);
                    if (function_exists('clean_post_cache')) {
                        clean_post_cache($course_id);
                    }
                } else {
                    if ($location_id <= 0 || get_post_type($location_id) !== 'location') {
                        return new WP_Error('invalid_location', __('Invalid location for this request.', 'lc-public-place-photo-upload'));
                    }
                    self::apply_location_change_payload($location_id, $payload, $request_id);
                    if ($location_id > 0) {
                        if (function_exists('clean_post_cache')) {
                            clean_post_cache($location_id);
                        }
                        if (function_exists('blm_clear_full_cache_for_post')) {
                            blm_clear_full_cache_for_post($location_id);
                        }
                        if (function_exists('blm_clear_light_cache')) {
                            blm_clear_light_cache();
                        }
                        if (function_exists('blm_schedule_rebuild')) {
                            blm_schedule_rebuild($location_id);
                        }
                    }
                }
                if ($stale_conflict) {
                    update_post_meta($request_id, '_lc_approved_with_conflict', '1');
                } else {
                    delete_post_meta($request_id, '_lc_approved_with_conflict');
                    delete_post_meta($request_id, '_lc_stale_conflict_message');
                }
            }

            update_post_meta($request_id, '_lc_change_status', $new_status);
            update_post_meta($request_id, '_lc_moderated_by', get_current_user_id());
            update_post_meta($request_id, '_lc_moderated_at', current_time('mysql'));
            if ($new_status === 'rejected') {
                update_post_meta($request_id, '_lc_reject_reason', $reject_reason);
            } else {
                delete_post_meta($request_id, '_lc_reject_reason');
            }

            return [
                'updated' => true,
                'stale_conflict' => !empty($stale_conflict),
            ];
        } finally {
            if ($approval_lock_token !== '') {
                self::release_request_approval_lock($request_id, $approval_lock_token);
            }
        }
    }

    private static function validate_request_snapshot_before_approve($request_id, $request_type, $payload) {
        $request_id = (int) $request_id;
        $request_type = sanitize_key((string) $request_type);
        $payload = is_array($payload) ? $payload : [];
        $snapshot = is_array($payload['snapshot'] ?? null) ? $payload['snapshot'] : [];
        if (empty($snapshot)) {
            return true;
        }

        $conflicts = [];
        if ($request_type === 'update_course') {
            $course_id = (int) get_post_meta($request_id, '_lc_course_id', true);
            if ($course_id <= 0 || get_post_type($course_id) !== 'course') {
                return true;
            }

            $snapshot_course = is_array($snapshot['course'] ?? null) ? $snapshot['course'] : [];
            $course = is_array($payload['course'] ?? null) ? $payload['course'] : [];
            foreach (array_keys($course) as $field_key) {
                $before = array_key_exists($field_key, $snapshot_course) ? (string) $snapshot_course[$field_key] : (string) get_post_meta($course_id, $field_key, true);
                $current = $field_key === 'title'
                    ? (string) get_the_title($course_id)
                    : (string) get_post_meta($course_id, $field_key, true);
                if (self::normalized_compare_key($before) !== self::normalized_compare_key($current)) {
                    $conflicts[] = 'course.' . $field_key;
                }
            }

            $has_image_ops = !empty($payload['remove_image_ids']) || !empty($payload['new_image_ids']);
            if ($has_image_ops) {
                $snapshot_image_ids = array_values(array_filter(array_map('intval', (array) ($snapshot['image_ids'] ?? []))));
                sort($snapshot_image_ids);
                $current_image_ids = self::parse_gallery_ids(get_post_meta($course_id, 'images', true));
                sort($current_image_ids);
                if ($snapshot_image_ids !== $current_image_ids) {
                    $conflicts[] = 'course.images';
                }
            }

            $snapshot_sessions = is_array($snapshot['sessions'] ?? null) ? $snapshot['sessions'] : [];
            $sessions = is_array($payload['sessions'] ?? null) ? $payload['sessions'] : [];
            foreach ($sessions as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $sid = (int) ($row['id'] ?? 0);
                if ($sid <= 0 || get_post_type($sid) !== 'session') {
                    continue;
                }
                $snapshot_row = is_array($snapshot_sessions[$sid] ?? null) ? $snapshot_sessions[$sid] : [];
                foreach (array_keys($row) as $field_key) {
                    if ($field_key === 'id') {
                        continue;
                    }
                    $before = array_key_exists($field_key, $snapshot_row) ? (string) $snapshot_row[$field_key] : (string) get_post_meta($sid, $field_key, true);
                    $current = (string) get_post_meta($sid, $field_key, true);
                    if (self::normalized_compare_key($before) !== self::normalized_compare_key($current)) {
                        $conflicts[] = 'session#' . (string) $sid . '.' . $field_key;
                    }
                }
            }

            $delete_session_ids = array_values(array_filter(array_map('intval', (array) ($payload['delete_session_ids'] ?? []))));
            foreach ($delete_session_ids as $sid) {
                if ($sid <= 0) {
                    continue;
                }
                $snapshot_row = is_array($snapshot_sessions[$sid] ?? null) ? $snapshot_sessions[$sid] : [];
                if (!empty($snapshot_row)) {
                    foreach ($snapshot_row as $field_key => $before_value) {
                        $current = (string) get_post_meta($sid, (string) $field_key, true);
                        if (self::normalized_compare_key((string) $before_value) !== self::normalized_compare_key($current)) {
                            $conflicts[] = 'session#' . (string) $sid . '.' . (string) $field_key;
                            break;
                        }
                    }
                } elseif (get_post_type($sid) !== 'session') {
                    $conflicts[] = 'session#' . (string) $sid;
                }
            }
        } else {
            $location_id = (int) get_post_meta($request_id, '_lc_location_id', true);
            if ($location_id <= 0 || get_post_type($location_id) !== 'location') {
                return true;
            }
            $snapshot_location = is_array($snapshot['location'] ?? null) ? $snapshot['location'] : [];
            $location = is_array($payload['location'] ?? null) ? $payload['location'] : [];
            foreach (array_keys($location) as $field_key) {
                $before = array_key_exists($field_key, $snapshot_location) ? (string) $snapshot_location[$field_key] : (string) get_post_meta($location_id, $field_key, true);
                $current = $field_key === 'title'
                    ? (string) get_the_title($location_id)
                    : (string) get_post_meta($location_id, $field_key, true);
                if (self::normalized_compare_key($before) !== self::normalized_compare_key($current)) {
                    $conflicts[] = 'location.' . $field_key;
                }
            }

            if (array_key_exists('facility_slugs', $payload)) {
                $snapshot_facility = array_values(array_filter(array_map('sanitize_title', (array) ($snapshot['facility_slugs'] ?? []))));
                $current_facility = self::get_location_facility_slugs($location_id);
                sort($snapshot_facility);
                sort($current_facility);
                if ($snapshot_facility !== $current_facility) {
                    $conflicts[] = 'location.facility_slugs';
                }
            }

            $has_image_ops = !empty($payload['remove_image_ids']) || !empty($payload['new_image_ids']);
            if ($has_image_ops) {
                $settings = self::get_settings();
                $meta_key = (string) ($settings['location_gallery_meta_key'] ?? 'images');
                $snapshot_image_ids = array_values(array_filter(array_map('intval', (array) ($snapshot['image_ids'] ?? []))));
                sort($snapshot_image_ids);
                $current_image_ids = self::parse_gallery_ids(get_post_meta($location_id, $meta_key, true));
                if ($meta_key !== 'images') {
                    $legacy_image_ids = self::parse_gallery_ids(get_post_meta($location_id, 'images', true));
                    if (!empty($legacy_image_ids)) {
                        $current_image_ids = array_values(array_unique(array_merge($current_image_ids, $legacy_image_ids)));
                    }
                }
                sort($current_image_ids);
                if ($snapshot_image_ids !== $current_image_ids) {
                    $conflicts[] = 'location.images';
                }
            }

            $snapshot_sessions = is_array($snapshot['sessions'] ?? null) ? $snapshot['sessions'] : [];
            $sessions = is_array($payload['sessions'] ?? null) ? $payload['sessions'] : [];
            foreach ($sessions as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $sid = (int) ($row['id'] ?? 0);
                if ($sid <= 0 || get_post_type($sid) !== 'session') {
                    continue;
                }
                $snapshot_row = is_array($snapshot_sessions[$sid] ?? null) ? $snapshot_sessions[$sid] : [];
                foreach (array_keys($row) as $field_key) {
                    if ($field_key === 'id') {
                        continue;
                    }
                    $before = array_key_exists($field_key, $snapshot_row) ? (string) $snapshot_row[$field_key] : (string) get_post_meta($sid, $field_key, true);
                    $current = (string) get_post_meta($sid, $field_key, true);
                    if (self::normalized_compare_key($before) !== self::normalized_compare_key($current)) {
                        $conflicts[] = 'session#' . (string) $sid . '.' . $field_key;
                    }
                }
            }

            $delete_session_ids = array_values(array_filter(array_map('intval', (array) ($payload['delete_session_ids'] ?? []))));
            foreach ($delete_session_ids as $sid) {
                if ($sid <= 0) {
                    continue;
                }
                $snapshot_row = is_array($snapshot_sessions[$sid] ?? null) ? $snapshot_sessions[$sid] : [];
                if (!empty($snapshot_row)) {
                    foreach ($snapshot_row as $field_key => $before_value) {
                        $current = (string) get_post_meta($sid, (string) $field_key, true);
                        if (self::normalized_compare_key((string) $before_value) !== self::normalized_compare_key($current)) {
                            $conflicts[] = 'session#' . (string) $sid . '.' . (string) $field_key;
                            break;
                        }
                    }
                } elseif (get_post_type($sid) !== 'session') {
                    $conflicts[] = 'session#' . (string) $sid;
                }
            }
        }

        if (!empty($conflicts)) {
            $conflicts = array_values(array_unique($conflicts));
            $preview = implode(', ', array_slice($conflicts, 0, 5));
            if (count($conflicts) > 5) {
                $preview .= ' ...';
            }
            return new WP_Error(
                'stale_request_conflict',
                sprintf(
                    __('Cannot approve request #%1$d because source data has changed since submission. Conflicts: %2$s', 'lc-public-place-photo-upload'),
                    $request_id,
                    $preview
                )
            );
        }

        return true;
    }

    private static function apply_location_change_payload($location_id, $payload, $request_id = 0) {
        if ($location_id <= 0 || get_post_type($location_id) !== 'location') {
            return;
        }
        $request_id = (int) $request_id;
        $location = is_array($payload['location'] ?? null) ? $payload['location'] : [];
        $next_title = isset($location['title']) ? sanitize_text_field((string) $location['title']) : '';
        if ($next_title !== '' && $next_title !== '-') {
            wp_update_post(['ID' => $location_id, 'post_title' => $next_title]);
        }
        update_post_meta($location_id, 'address', sanitize_textarea_field((string) ($location['address'] ?? '')));
        update_post_meta($location_id, 'phone', sanitize_text_field((string) ($location['phone'] ?? '')));
        update_post_meta($location_id, 'opening_hours', sanitize_textarea_field((string) ($location['opening_hours'] ?? '')));
        update_post_meta($location_id, 'description', sanitize_textarea_field(self::normalize_multiline_text((string) ($location['description'] ?? ''))));
        update_post_meta($location_id, 'google_maps', esc_url_raw((string) ($location['google_maps'] ?? '')));
        update_post_meta($location_id, 'facebook', esc_url_raw((string) ($location['facebook'] ?? '')));
        if (array_key_exists('facility_slugs', $payload)) {
            $facility_slugs = self::sanitize_facility_slugs((array) ($payload['facility_slugs'] ?? []));
            wp_set_object_terms($location_id, $facility_slugs, 'facility', false);
        }

        $settings = self::get_settings();
        $meta_key = (string) $settings['location_gallery_meta_key'];
        $existing = self::parse_gallery_ids(get_post_meta($location_id, $meta_key, true));
        if ($meta_key !== 'images') {
            $legacy_existing = self::parse_gallery_ids(get_post_meta($location_id, 'images', true));
            if (!empty($legacy_existing)) {
                $existing = array_values(array_unique(array_merge($existing, $legacy_existing)));
            }
        }
        $remove_ids = array_values(array_filter(array_map('intval', (array) ($payload['remove_image_ids'] ?? []))));
        $new_ids = array_values(array_filter(array_map('intval', (array) ($payload['new_image_ids'] ?? []))));
        if (!empty($remove_ids)) {
            $existing = array_values(array_diff($existing, $remove_ids));
        }
        if (!empty($new_ids)) {
            $existing = array_values(array_unique(array_merge($existing, $new_ids)));
            foreach ($new_ids as $aid) {
                if ($aid > 0 && get_post_type($aid) === 'attachment') {
                    wp_update_post(['ID' => $aid, 'post_parent' => $location_id]);
                }
            }
        }
        update_post_meta($location_id, $meta_key, $existing);
        if ($meta_key !== 'images') {
            update_post_meta($location_id, 'images', $existing);
        }

        $allowed_session_ids = self::get_location_session_ids($location_id);
        $allowed_session_ids = array_values(array_filter(array_map('intval', (array) $allowed_session_ids)));
        $allowed_session_map = array_fill_keys($allowed_session_ids, true);

        $delete_session_ids = array_values(array_filter(array_map('intval', (array) ($payload['delete_session_ids'] ?? []))));
        $delete_session_ids = array_values(array_filter($delete_session_ids, static function($sid) use ($allowed_session_map) {
            return $sid > 0 && isset($allowed_session_map[$sid]);
        }));
        $delete_session_map = array_fill_keys($delete_session_ids, true);

        $sessions = is_array($payload['sessions'] ?? null) ? $payload['sessions'] : [];
        foreach ($sessions as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sid = (int) ($row['id'] ?? 0);
            if ($sid <= 0 || !isset($allowed_session_map[$sid])) {
                continue;
            }
            if (isset($delete_session_map[$sid])) {
                continue;
            }
            if (get_post_type($sid) !== 'session') {
                continue;
            }
            update_post_meta($sid, 'time_period', sanitize_textarea_field(self::normalize_multiline_text((string) ($row['time_period'] ?? ''))));
            update_post_meta($sid, 'session_details', sanitize_textarea_field(self::normalize_multiline_text((string) ($row['session_details'] ?? ''))));
            if (function_exists('update_field')) {
                update_field('time_period', sanitize_textarea_field(self::normalize_multiline_text((string) ($row['time_period'] ?? ''))), $sid);
                update_field('session_details', sanitize_textarea_field(self::normalize_multiline_text((string) ($row['session_details'] ?? ''))), $sid);
            }
        }

        foreach ($delete_session_ids as $sid) {
            if ($sid <= 0 || get_post_type($sid) !== 'session') {
                continue;
            }
            wp_trash_post($sid);
        }

        $new_sessions = is_array($payload['new_sessions'] ?? null) ? $payload['new_sessions'] : [];
        $allowed_course_ids = self::get_location_available_course_ids($location_id);
        $allowed_course_map = array_fill_keys(array_values(array_filter(array_map('intval', (array) $allowed_course_ids))), true);
        $location_title = (string) get_the_title($location_id);
        foreach ($new_sessions as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $course_id = (int) ($row['course_id'] ?? 0);
            if ($course_id <= 0 || !isset($allowed_course_map[$course_id]) || get_post_type($course_id) !== 'course') {
                continue;
            }
            $request_key = self::build_new_session_request_key($request_id, 'location', $index, $course_id, $location_id, $row);
            if ($request_key !== '' && self::find_existing_new_session_by_request_key($request_key) > 0) {
                continue;
            }
            $course_title = (string) get_the_title($course_id);
            if ($course_title === '') {
                $course_title = 'Course #' . (string) $course_id;
            }
            $session_title = trim($course_title . ' - ' . $location_title);
            if ($session_title === '') {
                $session_title = 'Session';
            }
            $new_session_id = wp_insert_post([
                'post_type' => 'session',
                'post_status' => 'publish',
                'post_title' => $session_title,
            ], true);
            if (is_wp_error($new_session_id) || !$new_session_id) {
                continue;
            }
            update_post_meta($new_session_id, 'course', $course_id);
            update_post_meta($new_session_id, 'location', $location_id);
            if ($request_id > 0) {
                update_post_meta($new_session_id, '_lc_created_from_request', $request_id);
            }
            if ($request_key !== '') {
                update_post_meta($new_session_id, '_lc_created_from_request_key', $request_key);
            }
            update_post_meta($new_session_id, 'time_period', sanitize_textarea_field(self::normalize_multiline_text((string) ($row['time_period'] ?? ''))));
            update_post_meta($new_session_id, 'session_details', sanitize_textarea_field(self::normalize_multiline_text((string) ($row['session_details'] ?? ''))));
            if (function_exists('update_field')) {
                update_field('course', $course_id, $new_session_id);
                update_field('location', $location_id, $new_session_id);
                update_field('time_period', sanitize_textarea_field(self::normalize_multiline_text((string) ($row['time_period'] ?? ''))), $new_session_id);
                update_field('session_details', sanitize_textarea_field(self::normalize_multiline_text((string) ($row['session_details'] ?? ''))), $new_session_id);
            }
        }
    }

    private static function apply_course_change_payload($course_id, $payload, $request_id = 0) {
        if ($course_id <= 0 || get_post_type($course_id) !== 'course') {
            return;
        }
        $request_id = (int) $request_id;
        $payload = is_array($payload) ? $payload : [];
        $course = is_array($payload['course'] ?? null) ? $payload['course'] : [];

        $next_title = isset($course['title']) ? sanitize_text_field((string) $course['title']) : '';
        if ($next_title !== '' && $next_title !== '-') {
            wp_update_post(['ID' => $course_id, 'post_title' => $next_title]);
        }
        if (array_key_exists('course_description', $course)) {
            update_post_meta($course_id, 'course_description', sanitize_textarea_field(self::normalize_multiline_text((string) ($course['course_description'] ?? ''))));
            if (function_exists('update_field')) {
                update_field('course_description', sanitize_textarea_field(self::normalize_multiline_text((string) ($course['course_description'] ?? ''))), $course_id);
            }
        }
        if (array_key_exists('learning_link', $course)) {
            update_post_meta($course_id, 'learning_link', esc_url_raw((string) ($course['learning_link'] ?? '')));
            if (function_exists('update_field')) {
                update_field('learning_link', esc_url_raw((string) ($course['learning_link'] ?? '')), $course_id);
            }
        }
        if (array_key_exists('total_minutes', $course)) {
            $total_minutes = max(0, (int) ($course['total_minutes'] ?? 0));
            update_post_meta($course_id, 'total_minutes', $total_minutes);
            if (function_exists('update_field')) {
                update_field('total_minutes', $total_minutes, $course_id);
            }
        }
        if (array_key_exists('price', $course)) {
            $price = is_numeric($course['price']) ? (float) $course['price'] : 0;
            update_post_meta($course_id, 'price', $price);
            if (function_exists('update_field')) {
                update_field('price', $price, $course_id);
            }
        }
        if (array_key_exists('has_certificate', $course)) {
            $has_certificate = !empty($course['has_certificate']) ? 1 : 0;
            update_post_meta($course_id, 'has_certificate', $has_certificate);
            if (function_exists('update_field')) {
                update_field('has_certificate', $has_certificate, $course_id);
            }
        }
        $existing_images = self::parse_gallery_ids(get_post_meta($course_id, 'images', true));
        $remove_ids = array_values(array_filter(array_map('intval', (array) ($payload['remove_image_ids'] ?? []))));
        $new_ids = array_values(array_filter(array_map('intval', (array) ($payload['new_image_ids'] ?? []))));
        if (!empty($remove_ids)) {
            $existing_images = array_values(array_diff($existing_images, $remove_ids));
        }
        if (!empty($new_ids)) {
            $existing_images = array_values(array_unique(array_merge($existing_images, $new_ids)));
            foreach ($new_ids as $aid) {
                if ($aid > 0 && get_post_type($aid) === 'attachment') {
                    wp_update_post(['ID' => $aid, 'post_parent' => $course_id]);
                }
            }
        }
        update_post_meta($course_id, 'images', $existing_images);
        if (function_exists('update_field')) {
            update_field('images', $existing_images, $course_id);
        }

        $allowed_session_ids = self::get_course_session_ids($course_id);
        $allowed_session_ids = array_values(array_filter(array_map('intval', (array) $allowed_session_ids)));
        $allowed_session_map = array_fill_keys($allowed_session_ids, true);

        $delete_session_ids = array_values(array_filter(array_map('intval', (array) ($payload['delete_session_ids'] ?? []))));
        $delete_session_ids = array_values(array_filter($delete_session_ids, static function($sid) use ($allowed_session_map) {
            return $sid > 0 && isset($allowed_session_map[$sid]);
        }));
        $delete_session_map = array_fill_keys($delete_session_ids, true);

        $session_fields = ['time_period', 'session_details', 'apply_url', 'start_date', 'end_date', 'reg_start', 'reg_end'];
        $sessions = is_array($payload['sessions'] ?? null) ? $payload['sessions'] : [];
        foreach ($sessions as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sid = (int) ($row['id'] ?? 0);
            if ($sid <= 0 || !isset($allowed_session_map[$sid])) {
                continue;
            }
            if (isset($delete_session_map[$sid]) || get_post_type($sid) !== 'session') {
                continue;
            }
            foreach ($session_fields as $field_key) {
                if (!array_key_exists($field_key, $row)) {
                    continue;
                }
                $value = (string) ($row[$field_key] ?? '');
                if ($field_key === 'session_details') {
                    $value = sanitize_textarea_field(self::normalize_multiline_text($value));
                } elseif ($field_key === 'apply_url') {
                    $value = esc_url_raw($value);
                } else {
                    if ($field_key === 'time_period') {
                        $value = sanitize_textarea_field(self::normalize_multiline_text($value));
                    } else {
                        $value = sanitize_text_field($value);
                    }
                }
                update_post_meta($sid, $field_key, $value);
                if (function_exists('update_field')) {
                    update_field($field_key, $value, $sid);
                }
            }
        }

        foreach ($delete_session_ids as $sid) {
            if ($sid <= 0 || get_post_type($sid) !== 'session') {
                continue;
            }
            wp_trash_post($sid);
        }

        $new_sessions = is_array($payload['new_sessions'] ?? null) ? $payload['new_sessions'] : [];
        $course_title = (string) get_the_title($course_id);
        $allowed_new_location_map = array_fill_keys(self::get_course_available_location_ids($course_id), true);
        foreach ($new_sessions as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $location_id = isset($row['location_id']) ? (int) $row['location_id'] : 0;
            if ($location_id <= 0 || get_post_type($location_id) !== 'location' || !isset($allowed_new_location_map[$location_id])) {
                continue;
            }
            $request_key = self::build_new_session_request_key($request_id, 'course', $index, $course_id, $location_id, $row);
            if ($request_key !== '' && self::find_existing_new_session_by_request_key($request_key) > 0) {
                continue;
            }
            $location_title = (string) get_the_title($location_id);
            $session_title = trim($course_title . ' - ' . $location_title);
            if ($session_title === '') {
                $session_title = 'Session';
            }
            $new_session_id = wp_insert_post([
                'post_type' => 'session',
                'post_status' => 'publish',
                'post_title' => $session_title,
            ], true);
            if (is_wp_error($new_session_id) || !$new_session_id) {
                continue;
            }
            update_post_meta($new_session_id, 'course', $course_id);
            update_post_meta($new_session_id, 'location', $location_id);
            if ($request_id > 0) {
                update_post_meta($new_session_id, '_lc_created_from_request', $request_id);
            }
            if ($request_key !== '') {
                update_post_meta($new_session_id, '_lc_created_from_request_key', $request_key);
            }
            if (function_exists('update_field')) {
                update_field('course', $course_id, $new_session_id);
                update_field('location', $location_id, $new_session_id);
            }
            foreach ($session_fields as $field_key) {
                if (!array_key_exists($field_key, $row)) {
                    continue;
                }
                $value = (string) ($row[$field_key] ?? '');
                if ($field_key === 'session_details') {
                    $value = sanitize_textarea_field(self::normalize_multiline_text($value));
                } elseif ($field_key === 'apply_url') {
                    $value = esc_url_raw($value);
                } else {
                    if ($field_key === 'time_period') {
                        $value = sanitize_textarea_field(self::normalize_multiline_text($value));
                    } else {
                        $value = sanitize_text_field($value);
                    }
                }
                update_post_meta($new_session_id, $field_key, $value);
                if (function_exists('update_field')) {
                    update_field($field_key, $value, $new_session_id);
                }
            }
        }
    }

    public static function handle_approve_location_change_request() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'lc-public-place-photo-upload'));
        }
        check_admin_referer(self::NONCE_ACTION_EDIT_MODERATE);
        $request_id = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;
        if ($request_id <= 0 || !self::is_change_request_post_type(get_post_type($request_id))) {
            wp_safe_redirect(admin_url('admin.php?page=lc-location-edit-queue'));
            exit;
        }
        $_POST['request_id'] = $request_id;
        $_POST['new_status'] = 'approved';
        $_POST['reject_reason'] = '';
        $_POST['queue_page'] = isset($_GET['queue_page']) ? sanitize_key((string) wp_unslash($_GET['queue_page'])) : 'lc-location-edit-queue';
        self::handle_update_location_change_request_status();
    }

    public static function handle_reject_location_change_request() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'lc-public-place-photo-upload'));
        }
        check_admin_referer(self::NONCE_ACTION_EDIT_MODERATE);
        $request_id = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;
        $_POST['request_id'] = $request_id;
        $_POST['new_status'] = 'rejected';
        $_POST['reject_reason'] = 'ไม่อนุมัติโดยผู้ดูแลระบบ';
        $_POST['queue_page'] = isset($_GET['queue_page']) ? sanitize_key((string) wp_unslash($_GET['queue_page'])) : 'lc-location-edit-queue';
        self::handle_update_location_change_request_status();
    }

    public static function render_admin_queue_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $filter = isset($_GET['status_filter']) ? sanitize_key((string) $_GET['status_filter']) : 'pending';
        $allowed_filters = ['pending', 'approved', 'rejected', 'all'];
        if (!in_array($filter, $allowed_filters, true)) {
            $filter = 'pending';
        }

        $meta_query = [];
        if ($filter !== 'all') {
            $meta_query[] = [
                'key' => '_lc_submission_status',
                'value' => $filter,
                'compare' => '=',
            ];
        }

        $query = new WP_Query([
            'post_type' => self::CPT,
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
        ]);

        $action_status = isset($_GET['queue_notice']) ? sanitize_key((string) $_GET['queue_notice']) : '';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Photo Upload Queue', 'lc-public-place-photo-upload') . '</h1>';

        if ($action_status === 'approved') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Submission approved.', 'lc-public-place-photo-upload') . '</p></div>';
        } elseif ($action_status === 'rejected') {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Submission rejected.', 'lc-public-place-photo-upload') . '</p></div>';
        } elseif ($action_status === 'error') {
            echo '<div class="notice notice-error"><p>' . esc_html__('Action failed. Please try again.', 'lc-public-place-photo-upload') . '</p></div>';
        }

        $base_url = admin_url('edit.php?post_type=location&page=lc-photo-upload-queue');
        echo '<p>';
        foreach ($allowed_filters as $status_filter) {
            $url = add_query_arg('status_filter', $status_filter, $base_url);
            $label = ucfirst($status_filter);
            $style = $status_filter === $filter ? 'font-weight:700;' : '';
            echo '<a href="' . esc_url($url) . '" style="margin-right:12px;' . esc_attr($style) . '">' . esc_html($label) . '</a>';
        }
        echo '</p>';

        if (!$query->have_posts()) {
            echo '<p>' . esc_html__('No submissions found.', 'lc-public-place-photo-upload') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('ID', 'lc-public-place-photo-upload') . '</th>';
        echo '<th>' . esc_html__('Submitted', 'lc-public-place-photo-upload') . '</th>';
        echo '<th>' . esc_html__('Name', 'lc-public-place-photo-upload') . '</th>';
        echo '<th>' . esc_html__('Email', 'lc-public-place-photo-upload') . '</th>';
        echo '<th>' . esc_html__('Place', 'lc-public-place-photo-upload') . '</th>';
        echo '<th>' . esc_html__('Images', 'lc-public-place-photo-upload') . '</th>';
        echo '<th>' . esc_html__('Status', 'lc-public-place-photo-upload') . '</th>';
        echo '<th>' . esc_html__('Actions', 'lc-public-place-photo-upload') . '</th>';
        echo '</tr></thead><tbody>';

        while ($query->have_posts()) {
            $query->the_post();
            $submission_id = (int) get_the_ID();
            $uploader_name = (string) get_post_meta($submission_id, '_lc_uploader_name', true);
            $uploader_email = (string) get_post_meta($submission_id, '_lc_uploader_email', true);
            $location_id = (int) get_post_meta($submission_id, '_lc_location_id', true);
            $location_title = $location_id > 0 ? get_the_title($location_id) : '';
            $status = (string) get_post_meta($submission_id, '_lc_submission_status', true);
            $submitted_at = (string) get_post_meta($submission_id, '_lc_submitted_at', true);
            $images = get_post_meta($submission_id, '_lc_submission_images', true);
            $images = is_array($images) ? array_map('intval', $images) : [];

            echo '<tr>';
            echo '<td>' . esc_html((string) $submission_id) . '</td>';
            echo '<td>' . esc_html($submitted_at !== '' ? $submitted_at : get_the_date('Y-m-d H:i:s', $submission_id)) . '</td>';
            echo '<td>' . esc_html($uploader_name) . '</td>';
            echo '<td>' . esc_html($uploader_email !== '' ? $uploader_email : '-') . '</td>';
            echo '<td>';
            if ($location_id > 0) {
                $edit_url = get_edit_post_link($location_id);
                if ($edit_url) {
                    echo '<a href="' . esc_url($edit_url) . '">' . esc_html($location_title) . '</a>';
                } else {
                    echo esc_html($location_title);
                }
            } else {
                echo '-';
            }
            echo '</td>';

            echo '<td>';
            if (empty($images)) {
                echo '-';
            } else {
                foreach ($images as $attachment_id) {
                    $thumb = wp_get_attachment_image($attachment_id, 'thumbnail', false, ['style' => 'margin:0 8px 8px 0;display:inline-block;']);
                    if ($thumb) {
                        echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                }
            }
            echo '</td>';

            echo '<td>' . esc_html($status !== '' ? $status : 'pending') . '</td>';

            echo '<td>';
            if ($status === 'pending') {
                $approve_url = wp_nonce_url(
                    add_query_arg([
                        'action' => 'lc_approve_place_submission',
                        'submission_id' => $submission_id,
                    ], admin_url('admin-post.php')),
                    self::NONCE_ACTION_MODERATE
                );

                $reject_url = wp_nonce_url(
                    add_query_arg([
                        'action' => 'lc_reject_place_submission',
                        'submission_id' => $submission_id,
                    ], admin_url('admin-post.php')),
                    self::NONCE_ACTION_MODERATE
                );

                echo '<a class="button button-primary" style="margin-right:8px;" href="' . esc_url($approve_url) . '">' . esc_html__('Approve', 'lc-public-place-photo-upload') . '</a>';
                echo '<a class="button" href="' . esc_url($reject_url) . '">' . esc_html__('Reject', 'lc-public-place-photo-upload') . '</a>';
            } else {
                $approved_by = (int) get_post_meta($submission_id, '_lc_moderated_by', true);
                $moderated_at = (string) get_post_meta($submission_id, '_lc_moderated_at', true);
                $moderator_name = $approved_by > 0 ? get_the_author_meta('display_name', $approved_by) : '';
                echo esc_html($moderator_name !== '' ? $moderator_name : '-') . '<br />';
                echo '<small>' . esc_html($moderated_at !== '' ? $moderated_at : '-') . '</small>';
            }
            echo '</td>';

            echo '</tr>';
        }

        wp_reset_postdata();

        echo '</tbody></table>';
        echo '</div>';
    }

    private static function parse_gallery_ids($value) {
        if (is_array($value)) {
            return array_values(array_unique(array_map('intval', $value)));
        }

        if (is_numeric($value)) {
            return [(int) $value];
        }

        if (is_string($value) && trim($value) !== '') {
            $parts = array_map('trim', explode(',', $value));
            $ids = array_map('intval', $parts);
            $ids = array_filter($ids, function($id) {
                return $id > 0;
            });
            return array_values(array_unique($ids));
        }

        return [];
    }

    public static function handle_approve_submission() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'lc-public-place-photo-upload'));
        }

        check_admin_referer(self::NONCE_ACTION_MODERATE);

        $submission_id = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;
        if ($submission_id <= 0 || get_post_type($submission_id) !== self::CPT) {
            self::redirect_queue('error');
        }

        $status = (string) get_post_meta($submission_id, '_lc_submission_status', true);
        if ($status !== 'pending') {
            self::redirect_queue('error');
        }

        $location_id = (int) get_post_meta($submission_id, '_lc_location_id', true);
        $new_images = get_post_meta($submission_id, '_lc_submission_images', true);
        $new_images = is_array($new_images) ? array_map('intval', $new_images) : [];

        if ($location_id <= 0 || get_post_type($location_id) !== 'location' || empty($new_images)) {
            self::redirect_queue('error');
        }

        $settings = self::get_settings();
        $meta_key = $settings['location_gallery_meta_key'];

        $existing = get_post_meta($location_id, $meta_key, true);
        $existing_ids = self::parse_gallery_ids($existing);
        $merged = array_values(array_unique(array_merge($existing_ids, $new_images)));

        update_post_meta($location_id, $meta_key, $merged);

        update_post_meta($submission_id, '_lc_submission_status', 'approved');
        update_post_meta($submission_id, '_lc_moderated_by', get_current_user_id());
        update_post_meta($submission_id, '_lc_moderated_at', current_time('mysql'));

        // Invalidate Learning Map API caches so approved images appear immediately.
        if (function_exists('clean_post_cache')) {
            clean_post_cache($location_id);
        }
        wp_cache_delete($location_id, 'post_meta');

        if (function_exists('blm_clear_full_cache_for_post')) {
            blm_clear_full_cache_for_post($location_id);
        }
        if (function_exists('blm_clear_light_cache')) {
            blm_clear_light_cache();
        }
        if (function_exists('blm_schedule_rebuild')) {
            blm_schedule_rebuild($location_id);
        }

        self::redirect_queue('approved');
    }

    public static function handle_reject_submission() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'lc-public-place-photo-upload'));
        }

        check_admin_referer(self::NONCE_ACTION_MODERATE);

        $submission_id = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;
        if ($submission_id <= 0 || get_post_type($submission_id) !== self::CPT) {
            self::redirect_queue('error');
        }

        $status = (string) get_post_meta($submission_id, '_lc_submission_status', true);
        if ($status !== 'pending') {
            self::redirect_queue('error');
        }

        update_post_meta($submission_id, '_lc_submission_status', 'rejected');
        update_post_meta($submission_id, '_lc_moderated_by', get_current_user_id());
        update_post_meta($submission_id, '_lc_moderated_at', current_time('mysql'));

        self::redirect_queue('rejected');
    }

    private static function redirect_queue($notice) {
        $url = add_query_arg([
            'post_type' => 'location',
            'page' => 'lc-photo-upload-queue',
            'queue_notice' => sanitize_key((string) $notice),
        ], admin_url('edit.php'));

        wp_safe_redirect($url);
        exit;
    }
}

LC_Public_Place_Photo_Upload::init();
register_activation_hook(__FILE__, ['LC_Public_Place_Photo_Upload', 'activate']);
register_deactivation_hook(__FILE__, ['LC_Public_Place_Photo_Upload', 'deactivate']);
