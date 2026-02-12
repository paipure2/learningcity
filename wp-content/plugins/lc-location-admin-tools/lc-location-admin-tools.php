<?php
/*
Plugin Name: LC Location Admin Tools
Description: Admin tools for location geocoding and Google image import to ACF gallery.
Version: 1.0.0
Author: Learning City
*/

if (!defined('ABSPATH')) exit;

define('LC_IMG_BG_STATUS_OPTION', 'lc_img_bg_status');
define('LC_IMG_BG_QUEUE_TRANSIENT', 'lc_img_bg_queue');
define('LC_IMG_BG_LOCK_TRANSIENT', 'lc_img_bg_lock');
define('LC_IMG_BG_EVENT_HOOK', 'lc_img_bg_process_event');
define('LC_IMG_BG_STALE_SECONDS', 180);

/* =========================================================
 * [ADMIN LOCATION] Geocode missing lat/lng from Google Geocoding API
 * - Adds a button on Location list screen
 * - Skips records that already have valid latitude + longitude
 * ========================================================= */

function lc_get_google_geocoding_api_key() {
    $const_keys = [
        'BLM_GEOCODING_API_KEY',
        'LC_GOOGLE_GEOCODING_API_KEY',
        'GOOGLE_MAPS_API_KEY',
        'GOOGLE_API_KEY',
        'GMAPS_API_KEY',
    ];
    foreach ($const_keys as $const_key) {
        if (defined($const_key)) {
            $value = constant($const_key);
            if (!empty($value)) return trim((string) $value);
        }
    }

    $option_keys = [
        'lc_google_geocoding_api_key',
        'google_maps_api_key',
        'google_api_key',
    ];
    foreach ($option_keys as $key) {
        $v = get_option($key);
        if (!empty($v)) return trim((string) $v);
    }

    if (function_exists('acf_get_setting')) {
        $acf_key = acf_get_setting('google_api_key');
        if (!empty($acf_key)) return trim((string) $acf_key);
    }

    return '';
}

function lc_is_valid_lat_lng($lat, $lng) {
    if (!is_numeric($lat) || !is_numeric($lng)) return false;
    $lat = (float) $lat;
    $lng = (float) $lng;
    return abs($lat) <= 90 && abs($lng) <= 180;
}

function lc_build_geocode_queries($post_id) {
    $post_id = (int) $post_id;
    $queries = [];

    $address = trim((string) get_post_meta($post_id, 'address', true));
    $title = trim((string) get_the_title($post_id));
    $district_name = '';

    $district_terms = wp_get_post_terms($post_id, 'district', ['fields' => 'names']);
    if (!is_wp_error($district_terms) && !empty($district_terms[0])) {
        $district_name = trim((string) $district_terms[0]);
    }

    if ($address !== '') $queries[] = $address;

    if ($title !== '' && $district_name !== '') {
        $starts_with_district = function_exists('mb_strpos')
            ? (mb_strpos($district_name, 'เขต') === 0)
            : (strpos($district_name, 'เขต') === 0);
        $district_phrase = $starts_with_district
            ? $district_name
            : ('เขต' . $district_name);
        $queries[] = trim($title . ' ' . $district_phrase . ' กรุงเทพฯ');
    }

    if ($title !== '') {
        $queries[] = trim($title . ' กรุงเทพฯ');
    }

    $queries = array_values(array_filter(array_unique(array_map('trim', $queries))));

    return $queries;
}

function lc_normalize_compare_text($text) {
    $text = html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8');
    $text = wp_strip_all_tags($text);
    $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', (string) $text);
    return trim((string) $text);
}

function lc_text_similarity_score($a, $b) {
    $a = lc_normalize_compare_text($a);
    $b = lc_normalize_compare_text($b);
    if ($a === '' || $b === '') return 0.0;

    $pct = 0.0;
    similar_text($a, $b, $pct);
    $sim = max(0.0, min(1.0, ((float) $pct / 100.0)));

    $contains_bonus = (strpos($a, $b) !== false || strpos($b, $a) !== false) ? 0.15 : 0.0;

    $ta = array_values(array_filter(preg_split('/\s+/u', $a)));
    $tb = array_values(array_filter(preg_split('/\s+/u', $b)));
    $jaccard = 0.0;
    if (!empty($ta) && !empty($tb)) {
        $ua = array_values(array_unique($ta));
        $ub = array_values(array_unique($tb));
        $inter = array_intersect($ua, $ub);
        $union = array_unique(array_merge($ua, $ub));
        if (!empty($union)) $jaccard = count($inter) / count($union);
    }

    $score = (0.6 * $sim) + (0.4 * $jaccard) + $contains_bonus;
    return max(0.0, min(1.0, $score));
}

function lc_location_has_gallery_images($post_id) {
    if (function_exists('get_field')) {
        $gallery = get_field('images', $post_id);
        if (is_array($gallery) && !empty($gallery)) return true;
        if (is_numeric($gallery) && (int) $gallery > 0) return true;
        if (is_string($gallery) && trim($gallery) !== '') return true;
    }

    $raw = get_post_meta($post_id, 'images', true);
    if (is_array($raw) && !empty($raw)) return true;
    if (is_numeric($raw) && (int) $raw > 0) return true;
    if (is_string($raw) && trim($raw) !== '') return true;
    return false;
}

function lc_get_img_bg_status() {
    $defaults = [
        'running' => false,
        'started_at' => 0,
        'updated_at' => 0,
        'completed_at' => 0,
        'total' => 0,
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'noquery' => 0,
        'nomatch' => 0,
        'failed' => 0,
        'message' => '',
    ];
    $saved = get_option(LC_IMG_BG_STATUS_OPTION, []);
    if (!is_array($saved)) $saved = [];
    return array_merge($defaults, $saved);
}

function lc_set_img_bg_status($status) {
    if (!is_array($status)) return;
    update_option(LC_IMG_BG_STATUS_OPTION, $status, false);
}

function lc_schedule_img_bg_worker($delay_seconds = 5) {
    $delay_seconds = max(1, (int) $delay_seconds);
    if (!wp_next_scheduled(LC_IMG_BG_EVENT_HOOK)) {
        wp_schedule_single_event(time() + $delay_seconds, LC_IMG_BG_EVENT_HOOK);
    }
}

function lc_unschedule_img_bg_worker() {
    $next = wp_next_scheduled(LC_IMG_BG_EVENT_HOOK);
    while ($next) {
        wp_unschedule_event($next, LC_IMG_BG_EVENT_HOOK);
        $next = wp_next_scheduled(LC_IMG_BG_EVENT_HOOK);
    }
}

function lc_is_img_bg_stalled($status) {
    if (!is_array($status)) return false;
    if (empty($status['running'])) return false;
    $updated_at = isset($status['updated_at']) ? (int) $status['updated_at'] : 0;
    if ($updated_at <= 0) return false;
    return (time() - $updated_at) > LC_IMG_BG_STALE_SECONDS;
}

function lc_find_best_google_place_for_location($post_id, $api_key) {
    $queries = lc_build_geocode_queries($post_id);
    if (empty($queries)) return null;

    $local_name = trim((string) get_the_title($post_id));
    $local_address = trim((string) get_post_meta($post_id, 'address', true));

    $best = null;
    $best_score = 0.0;

    foreach ($queries as $query) {
        $url = add_query_arg([
            'input' => $query,
            'inputtype' => 'textquery',
            'fields' => 'place_id,name,formatted_address',
            'language' => 'th',
            'region' => 'th',
            'key' => $api_key,
        ], 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json');

        $res = wp_remote_get($url, ['timeout' => 20]);
        if (is_wp_error($res)) continue;

        $body = json_decode(wp_remote_retrieve_body($res), true);
        $status = $body['status'] ?? '';
        $candidates = isset($body['candidates']) && is_array($body['candidates']) ? $body['candidates'] : [];
        if ($status !== 'OK' || empty($candidates)) continue;

        foreach (array_slice($candidates, 0, 5) as $candidate) {
            $place_id = trim((string) ($candidate['place_id'] ?? ''));
            $cand_name = trim((string) ($candidate['name'] ?? ''));
            $cand_addr = trim((string) ($candidate['formatted_address'] ?? ''));
            if ($place_id === '' || $cand_name === '') continue;

            $name_score = lc_text_similarity_score($local_name, $cand_name);
            $addr_score = ($local_address !== '') ? lc_text_similarity_score($local_address, $cand_addr) : 0.0;
            $score = ($local_address !== '')
                ? ((0.75 * $name_score) + (0.25 * $addr_score))
                : $name_score;

            if ($score > $best_score) {
                $best_score = $score;
                $best = [
                    'place_id' => $place_id,
                    'name' => $cand_name,
                    'formatted_address' => $cand_addr,
                    'name_score' => $name_score,
                    'address_score' => $addr_score,
                    'score' => $score,
                ];
            }
        }
    }

    if (!$best) return null;
    if ($best['name_score'] < 0.55) return null;
    if ($best['score'] < 0.50) return null;
    return $best;
}

function lc_build_google_photo_credit_caption($photo) {
    $parts = [];
    $atts = isset($photo['html_attributions']) && is_array($photo['html_attributions']) ? $photo['html_attributions'] : [];
    foreach ($atts as $att) {
        $txt = trim(wp_strip_all_tags(html_entity_decode((string) $att, ENT_QUOTES, 'UTF-8')));
        if ($txt !== '') $parts[] = $txt;
    }
    $parts = array_values(array_unique($parts));

    if (!empty($parts)) {
        return 'เครดิตภาพ: ' . implode(' | ', $parts) . ' (Google Maps)';
    }
    return 'เครดิตภาพ: Google Maps';
}

function lc_import_google_place_images($post_id, $place_id, $api_key, $limit = 3) {
    $post_id = (int) $post_id;
    $place_id = trim((string) $place_id);
    $limit = max(1, (int) $limit);
    if ($post_id <= 0 || $place_id === '' || $api_key === '') return [];

    $detail_url = add_query_arg([
        'place_id' => $place_id,
        'fields' => 'photo',
        'language' => 'th',
        'key' => $api_key,
    ], 'https://maps.googleapis.com/maps/api/place/details/json');

    $detail_res = wp_remote_get($detail_url, ['timeout' => 20]);
    if (is_wp_error($detail_res)) return [];

    $detail_body = json_decode(wp_remote_retrieve_body($detail_res), true);
    $status = $detail_body['status'] ?? '';
    $photos = $detail_body['result']['photos'] ?? [];
    if ($status !== 'OK' || !is_array($photos) || empty($photos)) return [];

    if (!function_exists('download_url') || !function_exists('media_handle_sideload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $ids = [];
    $title_base = sanitize_title((string) get_the_title($post_id));
    if ($title_base === '') $title_base = 'location';

    foreach (array_slice($photos, 0, $limit) as $i => $photo) {
        $photo_ref = trim((string) ($photo['photo_reference'] ?? ''));
        if ($photo_ref === '') continue;

        $img_url = add_query_arg([
            'maxwidth' => 1600,
            'photo_reference' => $photo_ref,
            'key' => $api_key,
        ], 'https://maps.googleapis.com/maps/api/place/photo');

        $tmp = download_url($img_url, 25);
        if (is_wp_error($tmp)) continue;

        $file_array = [
            'name' => $title_base . '-google-' . ($i + 1) . '.jpg',
            'tmp_name' => $tmp,
        ];

        $attach_id = media_handle_sideload($file_array, $post_id);
        if (is_wp_error($attach_id)) {
            @unlink($tmp);
            continue;
        }

        $caption = lc_build_google_photo_credit_caption($photo);
        wp_update_post([
            'ID' => (int) $attach_id,
            'post_excerpt' => $caption,
            'post_content' => $caption,
        ]);

        $ids[] = (int) $attach_id;
    }

    return $ids;
}

add_action('restrict_manage_posts', function () {
    global $typenow, $pagenow;
    if (!is_admin() || $pagenow !== 'edit.php' || $typenow !== 'location') return;
    if (!current_user_can('edit_posts')) return;

    $api_key = lc_get_google_geocoding_api_key();
    $run_url = wp_nonce_url(
        admin_url('admin-post.php?action=lc_geocode_missing_locations'),
        'lc_geocode_missing_locations'
    );
    $run_img_url = wp_nonce_url(
        admin_url('admin-post.php?action=lc_fetch_missing_location_images'),
        'lc_fetch_missing_location_images'
    );
    $stop_img_url = wp_nonce_url(
        admin_url('admin-post.php?action=lc_stop_missing_location_images_bg'),
        'lc_stop_missing_location_images_bg'
    );
    $resume_img_url = wp_nonce_url(
        admin_url('admin-post.php?action=lc_resume_missing_location_images_bg'),
        'lc_resume_missing_location_images_bg'
    );

    $img_status = lc_get_img_bg_status();
    $is_running = !empty($img_status['running']);
    $is_stalled = lc_is_img_bg_stalled($img_status);
    $progress = '';
    if (!empty($img_status['total'])) {
        $progress = ' (' . intval($img_status['processed']) . '/' . intval($img_status['total']) . ')';
    }

    echo '<a class="button button-secondary" style="margin-left:8px" href="' . esc_url($run_url) . '">ค้นหา lat/lng ที่ยังไม่มี</a>';
    if ($is_running) {
        $running_label = $is_stalled ? 'งานค้าง (Background)' : 'กำลังดึงรูปแบบ Background';
        echo '<button type="button" class="button" disabled style="margin-left:8px">' . esc_html($running_label) . esc_html($progress) . '</button>';
        if ($is_stalled) {
            echo '<a class="button button-secondary" style="margin-left:8px" href="' . esc_url($resume_img_url) . '">เดินงานต่อ</a>';
        }
        echo '<a class="button button-secondary" style="margin-left:8px" href="' . esc_url($stop_img_url) . '">หยุดงานดึงรูป</a>';
    } else {
        echo '<a class="button button-secondary" style="margin-left:8px" href="' . esc_url($run_img_url) . '">เริ่มดึงรูป Google แบบ Background (สูงสุด 3 รูป)</a>';
    }
    if (!empty($img_status['updated_at'])) {
        echo '<span style="margin-left:8px;color:#475569;">อัปเดตล่าสุด: ' . esc_html(date_i18n('Y-m-d H:i:s', (int) $img_status['updated_at'])) . '</span>';
    }
    if (!$api_key) {
        echo '<span style="margin-left:8px;color:#b91c1c;font-weight:600;">ไม่พบ Google API Key</span>';
    }
}, 30);

add_action('admin_post_lc_geocode_missing_locations', function () {
    if (!current_user_can('edit_posts')) wp_die('forbidden', 403);
    check_admin_referer('lc_geocode_missing_locations');

    $api_key = lc_get_google_geocoding_api_key();
    $counts = [
        'updated' => 0,
        'skipped' => 0,
        'noaddr'  => 0,
        'failed'  => 0,
    ];

    if (!$api_key) {
        $redirect = add_query_arg([
            'post_type' => 'location',
            'lc_geo_done' => 1,
            'lc_geo_key_missing' => 1,
        ], admin_url('edit.php'));
        wp_safe_redirect($redirect);
        exit;
    }

    @set_time_limit(180);

    $ids = get_posts([
        'post_type' => 'location',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    foreach ($ids as $post_id) {
        $lat = get_post_meta($post_id, 'latitude', true);
        $lng = get_post_meta($post_id, 'longitude', true);

        if (lc_is_valid_lat_lng($lat, $lng)) {
            $counts['skipped']++;
            continue;
        }

        $queries = lc_build_geocode_queries($post_id);
        if (empty($queries)) {
            $counts['noaddr']++;
            continue;
        }
        $found_lat = null;
        $found_lng = null;

        foreach ($queries as $query) {
            $url = add_query_arg([
                'address' => $query,
                'key' => $api_key,
                'language' => 'th',
                'region' => 'th',
            ], 'https://maps.googleapis.com/maps/api/geocode/json');

            $res = wp_remote_get($url, ['timeout' => 15]);
            if (is_wp_error($res)) {
                continue;
            }

            $code = (int) wp_remote_retrieve_response_code($res);
            $body = json_decode(wp_remote_retrieve_body($res), true);
            $status = $body['status'] ?? '';
            $loc = $body['results'][0]['geometry']['location'] ?? null;

            if ($code !== 200 || $status !== 'OK' || !is_array($loc) || !isset($loc['lat'], $loc['lng'])) {
                continue;
            }

            $found_lat = round((float) $loc['lat'], 6);
            $found_lng = round((float) $loc['lng'], 6);

            if (!lc_is_valid_lat_lng($found_lat, $found_lng)) {
                $found_lat = null;
                $found_lng = null;
                continue;
            }

            break;
        }

        if ($found_lat === null || $found_lng === null) {
            $counts['failed']++;
            continue;
        }

        // Save via ACF first (requested); fallback to post meta only if ACF is unavailable.
        if (function_exists('update_field')) {
            update_field('latitude', $found_lat, $post_id);
            update_field('longitude', $found_lng, $post_id);
        } else {
            update_post_meta($post_id, 'latitude', $found_lat);
            update_post_meta($post_id, 'longitude', $found_lng);
        }

        if (function_exists('blm_clear_full_cache_for_location_id')) {
            blm_clear_full_cache_for_location_id((int) $post_id);
        }

        $counts['updated']++;
    }

    if (function_exists('blm_clear_light_cache')) blm_clear_light_cache();
    if (function_exists('blm_schedule_rebuild')) blm_schedule_rebuild(0);

    $redirect = add_query_arg([
        'post_type' => 'location',
        'lc_geo_done' => 1,
        'lc_geo_updated' => $counts['updated'],
        'lc_geo_skipped' => $counts['skipped'],
        'lc_geo_noaddr' => $counts['noaddr'],
        'lc_geo_failed' => $counts['failed'],
    ], admin_url('edit.php'));
    wp_safe_redirect($redirect);
    exit;
});

add_action('admin_post_lc_fetch_missing_location_images', function () {
    if (!current_user_can('edit_posts')) wp_die('forbidden', 403);
    check_admin_referer('lc_fetch_missing_location_images');

    $api_key = lc_get_google_geocoding_api_key();

    if (!$api_key) {
        $redirect = add_query_arg([
            'post_type' => 'location',
            'lc_img_bg_key_missing' => 1,
        ], admin_url('edit.php'));
        wp_safe_redirect($redirect);
        exit;
    }

    $ids = get_posts([
        'post_type' => 'location',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    $status = lc_get_img_bg_status();
    if (!empty($status['running'])) {
        $redirect = add_query_arg([
            'post_type' => 'location',
            'lc_img_bg_running' => 1,
        ], admin_url('edit.php'));
        wp_safe_redirect($redirect);
        exit;
    }

    set_transient(LC_IMG_BG_QUEUE_TRANSIENT, array_values(array_map('intval', $ids)), 12 * HOUR_IN_SECONDS);

    $status = [
        'running' => true,
        'started_at' => time(),
        'updated_at' => time(),
        'completed_at' => 0,
        'total' => count($ids),
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'noquery' => 0,
        'nomatch' => 0,
        'failed' => 0,
        'message' => 'started',
    ];
    lc_set_img_bg_status($status);
    lc_schedule_img_bg_worker(3);

    $redirect = add_query_arg([
        'post_type' => 'location',
        'lc_img_bg_started' => 1,
    ], admin_url('edit.php'));
    wp_safe_redirect($redirect);
    exit;
});

add_action('admin_post_lc_stop_missing_location_images_bg', function () {
    if (!current_user_can('edit_posts')) wp_die('forbidden', 403);
    check_admin_referer('lc_stop_missing_location_images_bg');

    $status = lc_get_img_bg_status();
    if (!empty($status['running'])) {
        $status['running'] = false;
        $status['updated_at'] = time();
        $status['completed_at'] = time();
        $status['message'] = 'stopped';
        lc_set_img_bg_status($status);
    }

    lc_unschedule_img_bg_worker();
    delete_transient(LC_IMG_BG_QUEUE_TRANSIENT);
    delete_transient(LC_IMG_BG_LOCK_TRANSIENT);

    $redirect = add_query_arg([
        'post_type' => 'location',
        'lc_img_bg_stopped' => 1,
    ], admin_url('edit.php'));
    wp_safe_redirect($redirect);
    exit;
});

add_action('admin_post_lc_resume_missing_location_images_bg', function () {
    if (!current_user_can('edit_posts')) wp_die('forbidden', 403);
    check_admin_referer('lc_resume_missing_location_images_bg');

    $status = lc_get_img_bg_status();
    $queue = get_transient(LC_IMG_BG_QUEUE_TRANSIENT);
    if (!is_array($queue) || empty($queue)) {
        $redirect = add_query_arg([
            'post_type' => 'location',
            'lc_img_bg_resume_failed' => 1,
        ], admin_url('edit.php'));
        wp_safe_redirect($redirect);
        exit;
    }

    $status['running'] = true;
    $status['updated_at'] = time();
    $status['message'] = 'resumed';
    if (empty($status['started_at'])) $status['started_at'] = time();
    if (empty($status['total'])) $status['total'] = count($queue);
    lc_set_img_bg_status($status);

    delete_transient(LC_IMG_BG_LOCK_TRANSIENT);
    lc_schedule_img_bg_worker(2);

    $redirect = add_query_arg([
        'post_type' => 'location',
        'lc_img_bg_resumed' => 1,
    ], admin_url('edit.php'));
    wp_safe_redirect($redirect);
    exit;
});

add_action(LC_IMG_BG_EVENT_HOOK, function () {
    if (get_transient(LC_IMG_BG_LOCK_TRANSIENT)) return;
    set_transient(LC_IMG_BG_LOCK_TRANSIENT, 1, 50);

    @set_time_limit(60);

    $status = lc_get_img_bg_status();
    if (empty($status['running'])) {
        delete_transient(LC_IMG_BG_LOCK_TRANSIENT);
        return;
    }

    $api_key = lc_get_google_geocoding_api_key();
    if (!$api_key) {
        $status['running'] = false;
        $status['updated_at'] = time();
        $status['completed_at'] = time();
        $status['message'] = 'missing_api_key';
        lc_set_img_bg_status($status);
        delete_transient(LC_IMG_BG_QUEUE_TRANSIENT);
        delete_transient(LC_IMG_BG_LOCK_TRANSIENT);
        return;
    }

    $queue = get_transient(LC_IMG_BG_QUEUE_TRANSIENT);
    if (!is_array($queue)) $queue = [];

    $position = isset($status['processed']) ? (int) $status['processed'] : 0;
    $batch_size = 20;
    $processed_in_batch = 0;

    while ($processed_in_batch < $batch_size && $position < count($queue)) {
        $post_id = (int) $queue[$position];

        $already_imported = (int) get_post_meta($post_id, 'lc_google_images_imported', true) === 1;
        if ($already_imported) {
            $status['skipped']++;
            $status['processed']++;
            $position++;
            $processed_in_batch++;
            continue;
        }

        if (lc_location_has_gallery_images($post_id)) {
            update_post_meta($post_id, 'lc_google_images_imported', 1);
            $status['skipped']++;
            $status['processed']++;
            $position++;
            $processed_in_batch++;
            continue;
        }

        $queries = lc_build_geocode_queries($post_id);
        if (empty($queries)) {
            $status['noquery']++;
            $status['processed']++;
            $position++;
            $processed_in_batch++;
            continue;
        }

        $best = lc_find_best_google_place_for_location($post_id, $api_key);
        if (!$best) {
            $status['nomatch']++;
            $status['processed']++;
            $position++;
            $processed_in_batch++;
            continue;
        }

        $image_ids = lc_import_google_place_images($post_id, $best['place_id'], $api_key, 3);
        if (empty($image_ids)) {
            $status['failed']++;
            $status['processed']++;
            $position++;
            $processed_in_batch++;
            continue;
        }

        if (function_exists('update_field')) {
            update_field('images', $image_ids, $post_id);
        } else {
            update_post_meta($post_id, 'images', $image_ids);
        }
        update_post_meta($post_id, 'google_place_id', $best['place_id']);
        update_post_meta($post_id, 'lc_google_images_imported', 1);

        if (function_exists('blm_clear_full_cache_for_location_id')) {
            blm_clear_full_cache_for_location_id((int) $post_id);
        }

        $status['updated']++;
        $status['processed']++;
        $position++;
        $processed_in_batch++;
    }

    $status['updated_at'] = time();

    if ($position >= count($queue)) {
        $status['running'] = false;
        $status['completed_at'] = time();
        $status['message'] = 'done';
        lc_set_img_bg_status($status);
        delete_transient(LC_IMG_BG_QUEUE_TRANSIENT);
        if (function_exists('blm_clear_light_cache')) blm_clear_light_cache();
        if (function_exists('blm_schedule_rebuild')) blm_schedule_rebuild(0);
        delete_transient(LC_IMG_BG_LOCK_TRANSIENT);
        return;
    }

    lc_set_img_bg_status($status);
    lc_schedule_img_bg_worker(10);
    delete_transient(LC_IMG_BG_LOCK_TRANSIENT);
});

add_action('admin_notices', function () {
    if (!is_admin()) return;
    if (empty($_GET['post_type']) || $_GET['post_type'] !== 'location') return;
    if (empty($_GET['lc_geo_done'])) return;

    if (!empty($_GET['lc_geo_key_missing'])) {
        echo '<div class="notice notice-error"><p><strong>Geocode ไม่สำเร็จ:</strong> ไม่พบ Google API Key</p></div>';
        return;
    }

    $updated = isset($_GET['lc_geo_updated']) ? (int) $_GET['lc_geo_updated'] : 0;
    $skipped = isset($_GET['lc_geo_skipped']) ? (int) $_GET['lc_geo_skipped'] : 0;
    $noaddr  = isset($_GET['lc_geo_noaddr']) ? (int) $_GET['lc_geo_noaddr'] : 0;
    $failed  = isset($_GET['lc_geo_failed']) ? (int) $_GET['lc_geo_failed'] : 0;

    $msg = sprintf(
        'Geocode เสร็จแล้ว: อัปเดต %d, ข้าม (มีพิกัดแล้ว) %d, ข้าม (ไม่มีข้อมูลพอค้นหา) %d, ไม่สำเร็จ %d',
        $updated,
        $skipped,
        $noaddr,
        $failed
    );

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
});

add_action('admin_notices', function () {
    if (!is_admin()) return;
    if (empty($_GET['post_type']) || $_GET['post_type'] !== 'location') return;
    if (!empty($_GET['lc_img_bg_started'])) {
        echo '<div class="notice notice-info is-dismissible"><p>เริ่มดึงรูปแบบ Background แล้ว สามารถปิดแท็บ/ปิดคอมได้ (งานจะทำต่อเมื่อมี traffic กระตุ้น WP-Cron)</p></div>';
        return;
    }

    if (!empty($_GET['lc_img_bg_running'])) {
        echo '<div class="notice notice-warning is-dismissible"><p>งานดึงรูปกำลังรันอยู่แล้ว</p></div>';
        return;
    }
    if (!empty($_GET['lc_img_bg_key_missing'])) {
        echo '<div class="notice notice-error is-dismissible"><p><strong>ดึงรูปไม่สำเร็จ:</strong> ไม่พบ Google API Key</p></div>';
        return;
    }
    if (!empty($_GET['lc_img_bg_resumed'])) {
        echo '<div class="notice notice-info is-dismissible"><p>สั่งเดินงานต่อแล้ว</p></div>';
        return;
    }
    if (!empty($_GET['lc_img_bg_resume_failed'])) {
        echo '<div class="notice notice-error is-dismissible"><p>เดินงานต่อไม่สำเร็จ: ไม่พบคิวงานเดิม (ให้เริ่มงานใหม่)</p></div>';
        return;
    }
    if (!empty($_GET['lc_img_bg_stopped'])) {
        echo '<div class="notice notice-warning is-dismissible"><p>หยุดงานดึงรูปแบบ Background แล้ว</p></div>';
        return;
    }

    $status = lc_get_img_bg_status();
    if (!empty($status['running'])) {
        $stalled = lc_is_img_bg_stalled($status);
        $resume_url = wp_nonce_url(
            admin_url('admin-post.php?action=lc_resume_missing_location_images_bg'),
            'lc_resume_missing_location_images_bg'
        );
        $msg = sprintf(
            'กำลังดึงรูปแบบ Background: %d/%d (อัปเดต %d, ข้าม %d, ไม่มีข้อมูลพอค้นหา %d, ไม่ match %d, ล้มเหลว %d)',
            (int) $status['processed'],
            (int) $status['total'],
            (int) $status['updated'],
            (int) $status['skipped'],
            (int) $status['noquery'],
            (int) $status['nomatch'],
            (int) $status['failed']
        );
        if ($stalled) {
            echo '<div class="notice notice-warning"><p>' . esc_html($msg) . ' งานอาจค้าง สามารถ <a href="' . esc_url($resume_url) . '">กดเดินงานต่อ</a></p></div>';
        } else {
            echo '<div class="notice notice-info"><p>' . esc_html($msg) . '</p></div>';
        }
        return;
    }

    if (!empty($status['completed_at']) && ((int) $status['completed_at'] >= (time() - 600))) {
        if (($status['message'] ?? '') === 'missing_api_key') {
            echo '<div class="notice notice-error is-dismissible"><p><strong>ดึงรูปไม่สำเร็จ:</strong> ไม่พบ Google API Key</p></div>';
            return;
        }

        $msg = sprintf(
            'ดึงรูป Google (Background) เสร็จแล้ว: อัปเดต %d, ข้าม (มีรูปแล้ว/ดึงแล้ว) %d, ข้าม (ไม่มีข้อมูลพอค้นหา) %d, ไม่ผ่านเงื่อนไขความใกล้เคียง %d, ไม่สำเร็จ %d',
            (int) $status['updated'],
            (int) $status['skipped'],
            (int) $status['noquery'],
            (int) $status['nomatch'],
            (int) $status['failed']
        );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    }
});
