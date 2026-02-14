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
    $raw = get_post_meta($post_id, 'images', true);
    if (is_array($raw) && !empty($raw)) return true;
    if (is_numeric($raw) && (int) $raw > 0) return true;
    if (is_string($raw) && trim($raw) !== '') return true;
    return false;
}

function lc_build_missing_location_image_queue() {
    $ids = get_posts([
        'post_type' => 'location',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    $queue = [];
    foreach ($ids as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0) continue;
        if (lc_location_has_gallery_images($post_id)) continue;
        $queue[] = $post_id;
    }

    return $queue;
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

function lc_start_img_bg_queue($ids, $message = 'started') {
    $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids))));
    set_transient(LC_IMG_BG_QUEUE_TRANSIENT, $ids, 12 * HOUR_IN_SECONDS);

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
        'message' => (string) $message,
    ];
    lc_set_img_bg_status($status);
    lc_schedule_img_bg_worker(3);
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

function lc_process_location_image_import_ids($ids, $api_key) {
    $counts = [
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'noquery' => 0,
        'nomatch' => 0,
        'failed' => 0,
    ];

    @set_time_limit(600);

    foreach ((array) $ids as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || get_post_type($post_id) !== 'location') continue;

        if (lc_location_has_gallery_images($post_id)) {
            $counts['skipped']++;
            $counts['processed']++;
            continue;
        }

        $queries = lc_build_geocode_queries($post_id);
        if (empty($queries)) {
            $counts['noquery']++;
            $counts['processed']++;
            continue;
        }

        $best = lc_find_best_google_place_for_location($post_id, $api_key);
        if (!$best) {
            $counts['nomatch']++;
            $counts['processed']++;
            continue;
        }

        $image_ids = lc_import_google_place_images($post_id, $best['place_id'], $api_key, 3);
        if (empty($image_ids)) {
            $counts['failed']++;
            $counts['processed']++;
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

        $counts['updated']++;
        $counts['processed']++;
    }

    if (function_exists('blm_clear_light_cache')) blm_clear_light_cache();
    if (function_exists('blm_schedule_rebuild')) blm_schedule_rebuild(0);

    return $counts;
}

function lc_process_geocode_for_location_ids($ids, $api_key) {
    $counts = [
        'updated' => 0,
        'skipped' => 0,
        'noaddr'  => 0,
        'failed'  => 0,
    ];

    @set_time_limit(600);

    foreach ((array) $ids as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || get_post_type($post_id) !== 'location') continue;

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
            if (is_wp_error($res)) continue;

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

    return $counts;
}

function lc_render_location_type_filter_dropdown() {
    if (!taxonomy_exists('location-type')) return;

    $selected_raw = lc_get_location_type_filter_raw();
    if ($selected_raw === '') {
        global $wp_query;
        if ($wp_query instanceof WP_Query) {
            $tax_query = $wp_query->get('tax_query');
            if (is_array($tax_query)) {
                foreach ($tax_query as $tax_clause) {
                    if (!is_array($tax_clause)) continue;
                    if (($tax_clause['taxonomy'] ?? '') !== 'location-type') continue;
                    $terms = $tax_clause['terms'] ?? [];
                    if (is_array($terms) && !empty($terms[0])) {
                        $selected_raw = sanitize_text_field((string) $terms[0]);
                        break;
                    }
                }
            }
        }
    }

    $selected = '';
    $selected_term = lc_resolve_location_type_term($selected_raw);
    if ($selected_term && !is_wp_error($selected_term)) {
        $selected = (string) $selected_term->slug;
    }

    wp_dropdown_categories([
        'show_option_all' => 'ทุกประเภทสถานที่',
        'taxonomy' => 'location-type',
        'name' => 'lc_location_type_filter',
        'orderby' => 'name',
        'selected' => $selected,
        'hierarchical' => true,
        'depth' => 3,
        'show_count' => false,
        'hide_empty' => false,
        'value_field' => 'slug',
    ]);
}

function lc_get_location_type_filter_raw() {
    $raw = '';
    if (isset($_GET['lc_location_type_filter']) && $_GET['lc_location_type_filter'] !== '') {
        $raw = (string) wp_unslash($_GET['lc_location_type_filter']);
    } elseif (isset($_GET['location-type']) && $_GET['location-type'] !== '') {
        $raw = (string) wp_unslash($_GET['location-type']);
    }

    $raw = trim($raw);
    if ($raw === '') return '';

    // Some admin filter forms double-encode values.
    for ($i = 0; $i < 3; $i++) {
        if (strpos($raw, '%') === false) break;
        $decoded = rawurldecode($raw);
        if ($decoded === $raw) break;
        $raw = $decoded;
    }

    return sanitize_text_field($raw);
}

function lc_resolve_location_type_term($raw) {
    $raw = trim((string) $raw);
    if ($raw === '' || !taxonomy_exists('location-type')) return null;

    if (ctype_digit($raw)) {
        $term = get_term((int) $raw, 'location-type');
        return ($term && !is_wp_error($term)) ? $term : null;
    }

    $term = get_term_by('slug', $raw, 'location-type');
    if ($term && !is_wp_error($term)) return $term;

    $term = get_term_by('name', $raw, 'location-type');
    if ($term && !is_wp_error($term)) return $term;

    $slug = sanitize_title($raw);
    if ($slug !== '') {
        $term = get_term_by('slug', $slug, 'location-type');
        if ($term && !is_wp_error($term)) return $term;
    }

    return null;
}

function lc_get_current_location_type_term() {
    return lc_resolve_location_type_term(lc_get_location_type_filter_raw());
}

add_action('restrict_manage_posts', function ($post_type, $which) {
    if (!is_admin()) return;
    if (!empty($which) && $which !== 'top') return;
    if ($post_type !== 'location') return;
    if (!current_user_can('edit_posts')) return;

    lc_render_location_type_filter_dropdown();

    $api_key = lc_get_google_geocoding_api_key();
    if (!$api_key) {
        echo '<span style="margin-left:8px;color:#b91c1c;font-weight:600;">ไม่พบ Google API Key</span>';
    }
}, 30, 2);

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query instanceof WP_Query || !$query->is_main_query()) return;

    global $pagenow;
    if ($pagenow !== 'edit.php') return;

    $post_type = $query->get('post_type');
    $is_location_screen = ($post_type === 'location')
        || (is_array($post_type) && in_array('location', $post_type, true))
        || (isset($_GET['post_type']) && $_GET['post_type'] === 'location');
    if (!$is_location_screen) return;

    if (!taxonomy_exists('location-type')) return;

    $selected_term = lc_resolve_location_type_term(lc_get_location_type_filter_raw());
    if (!$selected_term || is_wp_error($selected_term)) return;

    // Also set native taxonomy query var for compatibility with admin list filters.
    $query->set('location-type', (string) $selected_term->slug);

    $tax_query = $query->get('tax_query');
    if (!is_array($tax_query)) $tax_query = [];
    $clean_tax_query = [];
    foreach ($tax_query as $k => $clause) {
        if ($k === 'relation') continue;
        if (is_array($clause) && (($clause['taxonomy'] ?? '') === 'location-type')) continue;
        $clean_tax_query[] = $clause;
    }
    $tax_query = ['relation' => 'AND'];
    foreach ($clean_tax_query as $clause) {
        $tax_query[] = $clause;
    }
    $tax_query[] = [
        'taxonomy' => 'location-type',
        'field' => 'term_id',
        'terms' => [(int) $selected_term->term_id],
    ];
    $query->set('tax_query', $tax_query);
}, 99);

add_action('admin_notices', function () {
    if (!is_admin()) return;
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'edit' || $screen->post_type !== 'location') return;

    $term = lc_get_current_location_type_term();
    if (!$term) return;

    echo '<div class="notice notice-info"><p>' .
        esc_html('กำลังกรองประเภทสถานที่: ' . $term->name) .
        '</p></div>';
});

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
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
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
            'lc_img_key_missing' => 1,
        ], admin_url('edit.php'));
        wp_safe_redirect($redirect);
        exit;
    }

    $ids = lc_build_missing_location_image_queue();
    $counts = lc_process_location_image_import_ids($ids, $api_key);

    $redirect = add_query_arg([
        'post_type' => 'location',
        'lc_img_done' => 1,
        'lc_img_selected' => count($ids),
        'lc_img_processed' => (int) $counts['processed'],
        'lc_img_updated' => (int) $counts['updated'],
        'lc_img_skipped' => (int) $counts['skipped'],
        'lc_img_noquery' => (int) $counts['noquery'],
        'lc_img_nomatch' => (int) $counts['nomatch'],
        'lc_img_failed' => (int) $counts['failed'],
    ], admin_url('edit.php'));
    wp_safe_redirect($redirect);
    exit;
});

add_filter('bulk_actions-edit-location', function ($bulk_actions) {
    $bulk_actions['lc_bulk_geocode_missing'] = 'ค้นหา lat/lng ที่ยังไม่มี (เฉพาะที่เลือก)';
    $bulk_actions['lc_bulk_fetch_google_images'] = 'ดึงรูป Google (เฉพาะที่เลือก)';
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-location', function ($redirect_to, $doaction, $post_ids) {
    if ($doaction !== 'lc_bulk_geocode_missing') return $redirect_to;

    if (!current_user_can('edit_posts')) {
        return add_query_arg([
            'post_type' => 'location',
            'lc_geo_bulk_forbidden' => 1,
        ], $redirect_to);
    }

    $api_key = lc_get_google_geocoding_api_key();
    if (!$api_key) {
        return add_query_arg([
            'post_type' => 'location',
            'lc_geo_key_missing' => 1,
        ], $redirect_to);
    }

    $eligible = [];
    $skipped_permission = 0;
    foreach ((array) $post_ids as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || get_post_type($post_id) !== 'location') continue;
        if (!current_user_can('edit_post', $post_id)) {
            $skipped_permission++;
            continue;
        }
        $eligible[] = $post_id;
    }

    if (empty($eligible)) {
        return add_query_arg([
            'post_type' => 'location',
            'lc_geo_bulk_empty' => 1,
            'lc_geo_bulk_skipped_permission' => $skipped_permission,
        ], $redirect_to);
    }

    $counts = lc_process_geocode_for_location_ids($eligible, $api_key);

    return add_query_arg([
        'post_type' => 'location',
        'lc_geo_bulk_done' => 1,
        'lc_geo_bulk_total' => count($eligible),
        'lc_geo_bulk_skipped_permission' => $skipped_permission,
        'lc_geo_updated' => (int) $counts['updated'],
        'lc_geo_skipped' => (int) $counts['skipped'],
        'lc_geo_noaddr' => (int) $counts['noaddr'],
        'lc_geo_failed' => (int) $counts['failed'],
    ], $redirect_to);
}, 9, 3);

add_filter('handle_bulk_actions-edit-location', function ($redirect_to, $doaction, $post_ids) {
    if ($doaction !== 'lc_bulk_fetch_google_images') return $redirect_to;

    if (!current_user_can('edit_posts')) {
        return add_query_arg([
            'post_type' => 'location',
            'lc_img_bulk_forbidden' => 1,
        ], $redirect_to);
    }

    $api_key = lc_get_google_geocoding_api_key();
    if (!$api_key) {
        return add_query_arg([
            'post_type' => 'location',
            'lc_img_key_missing' => 1,
        ], $redirect_to);
    }

    $eligible = [];
    $skipped_has_images = 0;
    $skipped_permission = 0;
    foreach ((array) $post_ids as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || get_post_type($post_id) !== 'location') continue;
        if (!current_user_can('edit_post', $post_id)) {
            $skipped_permission++;
            continue;
        }
        if (lc_location_has_gallery_images($post_id)) {
            $skipped_has_images++;
            continue;
        }
        $eligible[] = $post_id;
    }

    if (empty($eligible)) {
        return add_query_arg([
            'post_type' => 'location',
            'lc_img_bulk_empty' => 1,
            'lc_img_bulk_skipped_has_images' => $skipped_has_images,
            'lc_img_bulk_skipped_permission' => $skipped_permission,
        ], $redirect_to);
    }

    $counts = lc_process_location_image_import_ids($eligible, $api_key);

    return add_query_arg([
        'post_type' => 'location',
        'lc_img_bulk_done' => 1,
        'lc_img_bulk_total' => count($eligible),
        'lc_img_bulk_skipped_has_images' => $skipped_has_images,
        'lc_img_bulk_skipped_permission' => $skipped_permission,
        'lc_img_bulk_processed' => (int) $counts['processed'],
        'lc_img_bulk_updated' => (int) $counts['updated'],
        'lc_img_bulk_skipped' => (int) $counts['skipped'],
        'lc_img_bulk_noquery' => (int) $counts['noquery'],
        'lc_img_bulk_nomatch' => (int) $counts['nomatch'],
        'lc_img_bulk_failed' => (int) $counts['failed'],
    ], $redirect_to);
}, 10, 3);

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

        if (lc_location_has_gallery_images($post_id)) {
            update_post_meta($post_id, 'lc_google_images_imported', 1);
            $status['skipped']++;
            $status['processed']++;
            $position++;
            $processed_in_batch++;
            continue;
        }

        $already_imported = (int) get_post_meta($post_id, 'lc_google_images_imported', true) === 1;
        if ($already_imported) {
            // Flag says imported, but gallery is now empty (e.g. removed manually). Allow re-import.
            delete_post_meta($post_id, 'lc_google_images_imported');
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
    if (empty($_GET['lc_geo_done']) && empty($_GET['lc_geo_bulk_done']) && empty($_GET['lc_geo_bulk_empty']) && empty($_GET['lc_geo_bulk_forbidden']) && empty($_GET['lc_geo_key_missing'])) return;

    if (!empty($_GET['lc_geo_key_missing'])) {
        echo '<div class="notice notice-error"><p><strong>Geocode ไม่สำเร็จ:</strong> ไม่พบ Google API Key</p></div>';
        return;
    }
    if (!empty($_GET['lc_geo_bulk_forbidden'])) {
        echo '<div class="notice notice-error"><p>คุณไม่มีสิทธิ์รัน geocode</p></div>';
        return;
    }
    if (!empty($_GET['lc_geo_bulk_empty'])) {
        $skipped_permission = isset($_GET['lc_geo_bulk_skipped_permission']) ? (int) $_GET['lc_geo_bulk_skipped_permission'] : 0;
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html(sprintf('ไม่พบรายการที่รัน geocode ได้จากที่เลือกไว้ (ไม่มีสิทธิ์ %d)', $skipped_permission)) . '</p></div>';
        return;
    }

    $updated = isset($_GET['lc_geo_updated']) ? (int) $_GET['lc_geo_updated'] : 0;
    $skipped = isset($_GET['lc_geo_skipped']) ? (int) $_GET['lc_geo_skipped'] : 0;
    $noaddr  = isset($_GET['lc_geo_noaddr']) ? (int) $_GET['lc_geo_noaddr'] : 0;
    $failed  = isset($_GET['lc_geo_failed']) ? (int) $_GET['lc_geo_failed'] : 0;
    $prefix = !empty($_GET['lc_geo_bulk_done'])
        ? sprintf('Geocode เฉพาะที่เลือกเสร็จแล้ว (%d รายการ): ', isset($_GET['lc_geo_bulk_total']) ? (int) $_GET['lc_geo_bulk_total'] : 0)
        : 'Geocode เสร็จแล้ว: ';
    $extra = !empty($_GET['lc_geo_bulk_done'])
        ? sprintf(' (ไม่มีสิทธิ์ %d)', isset($_GET['lc_geo_bulk_skipped_permission']) ? (int) $_GET['lc_geo_bulk_skipped_permission'] : 0)
        : '';

    $msg = $prefix . sprintf(
        'อัปเดต %d, ข้าม (มีพิกัดแล้ว) %d, ข้าม (ไม่มีข้อมูลพอค้นหา) %d, ไม่สำเร็จ %d%s',
        $updated,
        $skipped,
        $noaddr,
        $failed,
        $extra
    );

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
});

add_action('admin_notices', function () {
    if (!is_admin()) return;
    if (empty($_GET['post_type']) || $_GET['post_type'] !== 'location') return;

    if (!empty($_GET['lc_img_key_missing'])) {
        echo '<div class="notice notice-error is-dismissible"><p><strong>ดึงรูปไม่สำเร็จ:</strong> ไม่พบ Google API Key</p></div>';
        return;
    }
    if (!empty($_GET['lc_img_done'])) {
        $msg = sprintf(
            'ดึงรูป Google เสร็จแล้ว (คัดมา %d): ประมวลผล %d, อัปเดต %d, ข้าม %d, ไม่มีข้อมูลพอค้นหา %d, ไม่ match %d, ไม่สำเร็จ %d',
            isset($_GET['lc_img_selected']) ? (int) $_GET['lc_img_selected'] : 0,
            isset($_GET['lc_img_processed']) ? (int) $_GET['lc_img_processed'] : 0,
            isset($_GET['lc_img_updated']) ? (int) $_GET['lc_img_updated'] : 0,
            isset($_GET['lc_img_skipped']) ? (int) $_GET['lc_img_skipped'] : 0,
            isset($_GET['lc_img_noquery']) ? (int) $_GET['lc_img_noquery'] : 0,
            isset($_GET['lc_img_nomatch']) ? (int) $_GET['lc_img_nomatch'] : 0,
            isset($_GET['lc_img_failed']) ? (int) $_GET['lc_img_failed'] : 0
        );
        echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        return;
    }
    if (!empty($_GET['lc_img_bulk_forbidden'])) {
        echo '<div class="notice notice-error is-dismissible"><p>คุณไม่มีสิทธิ์เริ่มงานดึงรูป</p></div>';
        return;
    }
    if (!empty($_GET['lc_img_bulk_empty'])) {
        $skipped_has_images = isset($_GET['lc_img_bulk_skipped_has_images']) ? (int) $_GET['lc_img_bulk_skipped_has_images'] : 0;
        $skipped_permission = isset($_GET['lc_img_bulk_skipped_permission']) ? (int) $_GET['lc_img_bulk_skipped_permission'] : 0;
        $msg = sprintf(
            'ไม่พบรายการที่ต้องดึงรูปจากที่เลือกไว้ (ข้าม: มีรูปแล้ว %d, ไม่มีสิทธิ์ %d)',
            $skipped_has_images,
            $skipped_permission
        );
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        return;
    }
    if (!empty($_GET['lc_img_bulk_done'])) {
        $total = isset($_GET['lc_img_bulk_total']) ? (int) $_GET['lc_img_bulk_total'] : 0;
        $skipped_has_images = isset($_GET['lc_img_bulk_skipped_has_images']) ? (int) $_GET['lc_img_bulk_skipped_has_images'] : 0;
        $skipped_permission = isset($_GET['lc_img_bulk_skipped_permission']) ? (int) $_GET['lc_img_bulk_skipped_permission'] : 0;
        $msg = sprintf(
            'ดึงรูป Google เฉพาะที่เลือกเสร็จแล้ว: เลือกได้ %d รายการ, อัปเดต %d, ข้ามระหว่างรัน %d, noquery %d, nomatch %d, failed %d (คัดออกก่อนรัน: มีรูปแล้ว %d, ไม่มีสิทธิ์ %d)',
            $total,
            isset($_GET['lc_img_bulk_updated']) ? (int) $_GET['lc_img_bulk_updated'] : 0,
            isset($_GET['lc_img_bulk_skipped']) ? (int) $_GET['lc_img_bulk_skipped'] : 0,
            isset($_GET['lc_img_bulk_noquery']) ? (int) $_GET['lc_img_bulk_noquery'] : 0,
            isset($_GET['lc_img_bulk_nomatch']) ? (int) $_GET['lc_img_bulk_nomatch'] : 0,
            isset($_GET['lc_img_bulk_failed']) ? (int) $_GET['lc_img_bulk_failed'] : 0,
            $skipped_has_images,
            $skipped_permission
        );
        echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        return;
    }
});
