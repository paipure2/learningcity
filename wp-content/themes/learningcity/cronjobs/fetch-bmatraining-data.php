<?php
/**
 * Cronjob: Fetch BMA Training data from API
 * Usage: php -d register_argc_argv=On fetch-bmatraining-data.php
 * Or call via WordPress: wp eval-file fetch-bmatraining-data.php
 */

// Load WordPress
require_once __DIR__ . '/../../../../wp-load.php';

global $wpdb;

// API endpoint
const LC_BMA_API_URL = 'https://bmatraining.bangkok.go.th/api/data';
const LC_BMA_SYNC_OPTION = 'lc_bmatraining_last_sync';

function lc_bma_now_utc() {
    return gmdate('Y-m-d H:i:s');
}

function lc_bma_mark_sync_start() {
    update_option(LC_BMA_SYNC_OPTION, [
        'status' => 'running',
        'started_at_utc' => lc_bma_now_utc(),
    ], false);
}

function lc_bma_mark_sync_finish($status, $payload = []) {
    $existing = get_option(LC_BMA_SYNC_OPTION, []);
    if (!is_array($existing)) {
        $existing = [];
    }

    $record = array_merge($existing, $payload, [
        'status' => $status,
        'finished_at_utc' => lc_bma_now_utc(),
    ]);

    update_option(LC_BMA_SYNC_OPTION, $record, false);
}

function lc_bma_fetch_api_payload($api_url) {
    $headers = [
        'X-API-KEY: WP0dJbTv4DeetQS7',
    ];

    // Try cURL first
    if (function_exists('curl_init')) {
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            return $response;
        }

        $curl_message = 'cURL failed. HTTP Code: ' . $http_code;
        if ($curl_errno || $curl_error !== '') {
            $curl_message .= '; cURL error #' . $curl_errno . ': ' . $curl_error;
        }
    } else {
        $curl_message = 'cURL extension is not available';
    }

    // Fallback to WordPress HTTP API
    $wp_response = wp_remote_get($api_url, [
        'timeout' => 30,
        'redirection' => 5,
        'headers' => [
            'X-API-KEY' => 'WP0dJbTv4DeetQS7',
        ],
    ]);

    if (is_wp_error($wp_response)) {
        throw new Exception($curl_message . '; WP HTTP error: ' . $wp_response->get_error_message());
    }

    $wp_http_code = (int) wp_remote_retrieve_response_code($wp_response);
    $wp_body = wp_remote_retrieve_body($wp_response);

    if ($wp_http_code !== 200 || !$wp_body) {
        throw new Exception($curl_message . '; WP HTTP code: ' . $wp_http_code);
    }

    return $wp_body;
}

function lc_bma_school_signature_from_api($school) {
    $semester_parts = explode('/', (string) ($school['semester_year'] ?? ''));
    $semester = isset($semester_parts[0]) ? (int) $semester_parts[0] : 0;
    $year = isset($semester_parts[1]) ? (int) $semester_parts[1] : 0;

    $parts = [
        (string) ($school['school_name_th'] ?? ''),
        (string) $semester,
        (string) $year,
        (string) ((int) ($school['generation'] ?? 0)),
        (string) (convert_date($school['start_date'] ?? '') ?? ''),
        (string) (convert_date($school['end_date'] ?? '') ?? ''),
        (string) (convert_date($school['start_reg'] ?? '') ?? ''),
        (string) (convert_date($school['end_reg'] ?? '') ?? ''),
        (string) ($school['time_period'] ?? ''),
        number_format((float) ($school['total_attendance_hours'] ?? 0), 2, '.', ''),
        (string) ($school['apply_url'] ?? ''),
    ];

    return md5(implode('|', $parts));
}

function lc_bma_school_signature_from_db($school) {
    $parts = [
        (string) ($school['school_name_th'] ?? ''),
        (string) ((int) ($school['semester'] ?? 0)),
        (string) ((int) ($school['year'] ?? 0)),
        (string) ((int) ($school['generation'] ?? 0)),
        (string) ($school['start_date'] ?? ''),
        (string) ($school['end_date'] ?? ''),
        (string) ($school['start_reg'] ?? ''),
        (string) ($school['end_reg'] ?? ''),
        (string) ($school['time_period'] ?? ''),
        number_format((float) ($school['total_attendance_hours'] ?? 0), 2, '.', ''),
        (string) ($school['apply_url'] ?? ''),
    ];

    return md5(implode('|', $parts));
}

// Helper function to convert date to Y-m-d format
function convert_date($date_str) {
    if (empty($date_str)) return null;
    
    // Try dd-mm-yyyy format first
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date_str, $matches)) {
        return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
    }
    
    // Try other formats with DateTime
    $date = DateTime::createFromFormat('d-m-Y', $date_str);
    if ($date) return $date->format('Y-m-d');
    
    $date = DateTime::createFromFormat('Y-m-d', $date_str);
    if ($date) return $date->format('Y-m-d');
    
    $date = DateTime::createFromFormat('d/m/Y', $date_str);
    if ($date) return $date->format('Y-m-d');
    
    return null;
}

// Helper function to convert datetime to Y-m-d H:i:s format
function convert_datetime($datetime_str) {
    if (empty($datetime_str)) return null;
    
    // Try dd-mm-yyyy HH:ii:ss format
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})\s+(.+)$/', $datetime_str, $matches)) {
        return $matches[3] . '-' . $matches[2] . '-' . $matches[1] . ' ' . $matches[4];
    }
    
    $date = DateTime::createFromFormat('d-m-Y H:i:s', $datetime_str);
    if ($date) return $date->format('Y-m-d H:i:s');
    
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $datetime_str);
    if ($date) return $date->format('Y-m-d H:i:s');
    
    return null;
}

function lc_bma_run_sync($echo_output = true) {
    global $wpdb;

    try {
        lc_bma_mark_sync_start();

        // Fetch data from API
        $response = lc_bma_fetch_api_payload(LC_BMA_API_URL);
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['data'])) {
            throw new Exception('Failed to parse API data');
        }
        
        $courses = $data['data'];
        $api_hash = md5($response);
        
        $changed_courses = [];
        $changed_school_names = [];
        $api_changed_courses = 0;
        $api_unchanged_courses = 0;

        foreach ($courses as &$course) {
            $code = (string) ($course['code'] ?? '');
            if ($code === '') {
                continue;
            }

            $db_course = $wpdb->get_row($wpdb->prepare(
                "SELECT id, title_th, total_hours, price FROM {$wpdb->prefix}bmatraining_courses WHERE course_code = %s",
                $code
            ), ARRAY_A);

            $db_course_id = $db_course ? (int) $db_course['id'] : 0;

            $course_fields_changed = false;
            if (!$db_course) {
                $course_fields_changed = true;
            } else {
                if ((string) ($db_course['title_th'] ?? '') !== (string) ($course['title_th'] ?? '')) $course_fields_changed = true;
                if ((int) ($db_course['total_hours'] ?? 0) !== (int) ($course['total_hours'] ?? 0)) $course_fields_changed = true;
                if (abs((float) ($db_course['price'] ?? 0) - (float) ($course['price'] ?? 0)) > 0.001) $course_fields_changed = true;
            }

            $api_school_sigs = [];
            $api_schools = (isset($course['schools']) && is_array($course['schools'])) ? $course['schools'] : [];
            foreach ($api_schools as $s) {
                $api_school_sigs[] = lc_bma_school_signature_from_api($s);
            }
            sort($api_school_sigs);

            $db_school_sigs = [];
            if ($db_course_id > 0) {
                $db_schools = $wpdb->get_results($wpdb->prepare(
                    "SELECT school_name_th, semester, year, generation, start_date, end_date, start_reg, end_reg, time_period, total_attendance_hours, apply_url
                     FROM {$wpdb->prefix}bmatraining_schools WHERE course_id = %d",
                    $db_course_id
                ), ARRAY_A);

                foreach ($db_schools as $db_school) {
                    $db_school_sigs[] = lc_bma_school_signature_from_db($db_school);
                }
                sort($db_school_sigs);
            } else {
                $course_fields_changed = true;
            }

            $schools_changed = ($api_school_sigs !== $db_school_sigs);
            $sessions_count_mismatch = false;
            $course_post_name = 'course-' . sanitize_title($code);
            $course_post_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'course'",
                $course_post_name
            ));
            if ($course_post_id > 0) {
                $session_count = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT p.ID)
                     FROM {$wpdb->posts} p
                     INNER JOIN {$wpdb->postmeta} pm_course ON (pm_course.post_id = p.ID AND pm_course.meta_key = 'course')
                     INNER JOIN {$wpdb->postmeta} pm_source ON (pm_source.post_id = p.ID AND pm_source.meta_key = 'source')
                     WHERE p.post_type = 'session'
                       AND p.post_status IN ('publish', 'draft', 'pending', 'private')
                       AND pm_course.meta_value = %d
                       AND pm_source.meta_value = 'bmatraining.bangkok.go.th'",
                    $course_post_id
                ));
                $sessions_count_mismatch = ($session_count !== count($api_schools));
            }

            $is_changed = $course_fields_changed || $schools_changed || $sessions_count_mismatch;

            if (!$is_changed) {
                $api_unchanged_courses++;
                continue;
            }

            $api_changed_courses++;

            // Insert/Update course only when changed
            $wpdb->replace(
                $wpdb->prefix . 'bmatraining_courses',
                [
                    'course_code' => $course['code'],
                    'title_th' => $course['title_th'],
                    'total_hours' => $course['total_hours'],
                    'price' => $course['price']
                ],
                ['%s', '%s', '%d', '%f']
            );

            $course_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bmatraining_courses WHERE course_code = %s",
                $course['code']
            ));

            // Replace schools only when changed
            $wpdb->delete(
                $wpdb->prefix . 'bmatraining_schools',
                ['course_id' => $course_id],
                ['%d']
            );

            foreach ($api_schools as &$school) {
                $semester_parts = explode('/', (string) ($school['semester_year'] ?? ''));
                $semester = isset($semester_parts[0]) ? (int) $semester_parts[0] : null;
                $year = isset($semester_parts[1]) ? (int) $semester_parts[1] : null;

                $wpdb->insert(
                    $wpdb->prefix . 'bmatraining_schools',
                    [
                        'course_id' => $course_id,
                        'school_name_th' => $school['school_name_th'],
                        'semester' => $semester,
                        'year' => $year,
                        'generation' => $school['generation'],
                        'start_date' => convert_date($school['start_date']),
                        'end_date' => convert_date($school['end_date']),
                        'start_reg' => convert_date($school['start_reg']),
                        'end_reg' => convert_date($school['end_reg']),
                        'time_period' => $school['time_period'],
                        'total_attendance_hours' => $school['total_attendance_hours'],
                        'apply_url' => $school['apply_url'],
                        'updated_at' => convert_datetime($school['updated_at'])
                    ],
                    ['%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s']
                );

                $school['school_id'] = $wpdb->insert_id;
                $school_name = (string) ($school['school_name_th'] ?? '');
                if ($school_name !== '') {
                    $changed_school_names[$school_name] = true;
                }
            }
            unset($school);

            $changed_courses[] = $course;
        }
        unset($course);

        $messages = [];
        $messages[] = "Success: API courses " . count($courses) . ", changed " . $api_changed_courses . ", unchanged " . $api_unchanged_courses;

        $location_result = ['new' => 0, 'updated' => 0];
        $course_result = ['new' => 0, 'updated' => 0];
        $session_result = ['new' => 0, 'updated' => 0];

        if (!empty($changed_courses)) {
            // Sync locations only from changed schools
            $location_result = sync_locations_to_posts($wpdb, array_keys($changed_school_names));
            // Sync courses and sessions only for changed courses
            $course_result = sync_courses_to_posts($wpdb, $changed_courses);
            $session_result = sync_sessions_to_posts($wpdb, $changed_courses);
        }

        lc_bma_mark_sync_finish('success', [
            'api_hash' => $api_hash,
            'api_course_count' => count($courses),
            'api_changed_courses' => (int) $api_changed_courses,
            'api_unchanged_courses' => (int) $api_unchanged_courses,
            'courses_new' => (int) $course_result['new'],
            'courses_updated' => (int) $course_result['updated'],
            'sessions_new' => (int) $session_result['new'],
            'sessions_updated' => (int) $session_result['updated'],
            'locations_new' => (int) $location_result['new'],
        ]);

        if ($echo_output) {
            foreach ($messages as $line) {
                echo $line . "\n";
            }
        }

        return [
            'ok' => true,
            'message' => 'Sync completed successfully',
        ];

    } catch (Exception $e) {
        lc_bma_mark_sync_finish('failed', [
            'error' => $e->getMessage(),
        ]);

        if ($echo_output) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        return [
            'ok' => false,
            'message' => $e->getMessage(),
        ];
    }
}

function lc_bma_run_dry_run($echo_output = true) {
    global $wpdb;

    try {
        $response = lc_bma_fetch_api_payload(LC_BMA_API_URL);
        $data = json_decode($response, true);

        if (!$data || !isset($data['data']) || !is_array($data['data'])) {
            throw new Exception('Failed to parse API data for dry run');
        }

        $api_courses = $data['data'];
        $api_hash = md5($response);

        $db_courses = $wpdb->get_results(
            "SELECT id, course_code, title_th, total_hours, price FROM {$wpdb->prefix}bmatraining_courses",
            ARRAY_A
        );

        $db_course_by_code = [];
        foreach ($db_courses as $db_course) {
            $db_course_by_code[(string) $db_course['course_code']] = $db_course;
        }

        $db_schools_rows = $wpdb->get_results(
            "SELECT course_id, school_name_th, semester, year, generation, start_date, end_date, start_reg, end_reg, time_period, total_attendance_hours, apply_url
             FROM {$wpdb->prefix}bmatraining_schools",
            ARRAY_A
        );

        $db_schools_by_course_id = [];
        foreach ($db_schools_rows as $row) {
            $cid = (int) $row['course_id'];
            if (!isset($db_schools_by_course_id[$cid])) {
                $db_schools_by_course_id[$cid] = [];
            }
            $db_schools_by_course_id[$cid][] = lc_bma_school_signature_from_db($row);
        }

        $summary = [
            'api_hash' => $api_hash,
            'api_courses' => count($api_courses),
            'db_courses' => count($db_courses),
            'courses_new_in_api' => 0,
            'courses_missing_from_api' => 0,
            'courses_changed' => 0,
            'schools_changed_courses' => 0,
        ];

        $seen_api_codes = [];

        foreach ($api_courses as $api_course) {
            $code = (string) ($api_course['code'] ?? '');
            if ($code === '') continue;
            $seen_api_codes[$code] = true;

            if (!isset($db_course_by_code[$code])) {
                $summary['courses_new_in_api']++;
                continue;
            }

            $db_course = $db_course_by_code[$code];
            $course_changed = false;

            if ((string) ($db_course['title_th'] ?? '') !== (string) ($api_course['title_th'] ?? '')) {
                $course_changed = true;
            }
            if ((int) ($db_course['total_hours'] ?? 0) !== (int) ($api_course['total_hours'] ?? 0)) {
                $course_changed = true;
            }
            if (abs((float) ($db_course['price'] ?? 0) - (float) ($api_course['price'] ?? 0)) > 0.001) {
                $course_changed = true;
            }

            if ($course_changed) {
                $summary['courses_changed']++;
            }

            $course_id = (int) ($db_course['id'] ?? 0);
            $db_school_sigs = $db_schools_by_course_id[$course_id] ?? [];
            sort($db_school_sigs);

            $api_school_sigs = [];
            $api_schools = (isset($api_course['schools']) && is_array($api_course['schools'])) ? $api_course['schools'] : [];
            foreach ($api_schools as $api_school) {
                $api_school_sigs[] = lc_bma_school_signature_from_api($api_school);
            }
            sort($api_school_sigs);

            if ($db_school_sigs !== $api_school_sigs) {
                $summary['schools_changed_courses']++;
            }
        }

        foreach ($db_course_by_code as $code => $_row) {
            if (!isset($seen_api_codes[$code])) {
                $summary['courses_missing_from_api']++;
            }
        }

        if ($echo_output) {
            echo "Dry Run Summary\n";
            echo "API hash: {$summary['api_hash']}\n";
            echo "API courses: {$summary['api_courses']}\n";
            echo "DB courses: {$summary['db_courses']}\n";
            echo "New in API (not in DB): {$summary['courses_new_in_api']}\n";
            echo "Missing from API (exists in DB): {$summary['courses_missing_from_api']}\n";
            echo "Course field changes: {$summary['courses_changed']}\n";
            echo "Courses with school/session changes: {$summary['schools_changed_courses']}\n";
        }

        return [
            'ok' => true,
            'message' => 'Dry run completed successfully',
            'summary' => $summary,
        ];
    } catch (Exception $e) {
        if ($echo_output) {
            echo "Dry Run Error: " . $e->getMessage() . "\n";
        }
        return [
            'ok' => false,
            'message' => $e->getMessage(),
        ];
    }
}

function sync_courses_to_posts($wpdb, $courses) {
    $synced = 0;
    $updated = 0;
    
    foreach ($courses as $course) {
        // Calculate total_attendance_hours from schools
        $total_attendance_hours = 0;
        if (isset($course['schools']) && is_array($course['schools'])) {
            foreach ($course['schools'] as $school) {
                $total_attendance_hours += isset($school['total_attendance_hours']) ? floatval($school['total_attendance_hours']) : 0;
            }
        }
        
        // Check if post already exists by course_code in post_name
        $post_name = 'course-' . sanitize_title($course['code']);
        $existing_post = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'course'",
            $post_name
        ));
        
        if ($existing_post) {
            // Update existing post
            $wpdb->update(
                $wpdb->posts,
                [
                    'post_title' => $course['title_th'],
                    'post_modified' => current_time('mysql'),
                    'post_modified_gmt' => current_time('mysql', 1)
                ],
                ['ID' => $existing_post],
                ['%s', '%s', '%s'],
                ['%d']
            );
            
            update_course_meta($existing_post, $course, $total_attendance_hours);
            $updated++;
            continue;
        }
        
        // Insert new course post
        $wpdb->insert(
            $wpdb->posts,
            [
                'post_title' => $course['title_th'],
                'post_name' => $post_name,
                'post_type' => 'course',
                'post_status' => 'publish',
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1)
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        $post_id = $wpdb->insert_id;
        
        if ($post_id) {
            update_course_meta($post_id, $course, $total_attendance_hours);
            $synced++;
        }
    }
    
    echo "Synced to posts: {$synced} new, {$updated} updated\n";
    return ['new' => $synced, 'updated' => $updated];
}

function update_course_meta($post_id, $course, $total_attendance_hours) {
    update_post_meta($post_id, 'course_uid', 'LC-' . $course['code']);
    update_post_meta($post_id, 'course_code', $course['code']);
    update_post_meta($post_id, 'total_minutes', $course['total_hours'] * 60);
    update_post_meta($post_id, 'total_attendance_hours', $total_attendance_hours);
    update_post_meta($post_id, 'price', $course['price']);
    update_post_meta($post_id, 'has_certificate', 1);
    update_post_meta($post_id, 'source', 'bmatraining.bangkok.go.th');
    update_post_meta($post_id, 'bma_last_synced_at_utc', lc_bma_now_utc());

    // Ensure imported BMA Training courses are assigned to provider: "โรงเรียนฝึกอาชีพ"
    assign_default_course_provider_term($post_id);
}

function assign_default_course_provider_term($post_id) {
    $taxonomy = 'course_provider';
    $provider_name = 'โรงเรียนฝึกอาชีพ';

    $term = get_term_by('name', $provider_name, $taxonomy);
    if (!$term || is_wp_error($term)) {
        $created = wp_insert_term($provider_name, $taxonomy);
        if (is_wp_error($created) || empty($created['term_id'])) {
            return;
        }
        $term_id = (int) $created['term_id'];
    } else {
        $term_id = (int) $term->term_id;
    }

    if ($term_id > 0) {
        wp_set_object_terms((int) $post_id, [$term_id], $taxonomy, false);
    }
}

function sync_sessions_to_posts($wpdb, $courses) {
    $synced = 0;
    $updated = 0;
    
    foreach ($courses as $course) {
        // Get course_uid from course post
        $course_uid = 'LC-' . $course['code'];
        
        // Get course post ID
        $post_name = 'course-' . sanitize_title($course['code']);
        $course_post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'course'",
            $post_name
        ));
        
        // Get course_id from bmatraining_courses
        $course_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bmatraining_courses WHERE course_code = %s",
            $course['code']
        ));
        
        if (!$course_id) continue;
        
        // Get schools from database
        $schools = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bmatraining_schools WHERE course_id = %d",
            $course_id
        ), ARRAY_A);
        
        if (empty($schools)) {
            continue;
        }
        
        foreach ($schools as $school) {
            // Use stable key (not DB row id) so one course+location can have multiple sessions correctly.
            $session_key = lc_bma_build_session_key($course['code'], $school);
            $post_name = 'session-' . $session_key;
            $session_id = 'LC-' . strtoupper($session_key);
            $post_title = lc_bma_build_session_title($course['title_th'], $school);
            
            // Get location_id from location post by school_name_th
            $location_id_raw = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_title = %s AND p.post_type = 'location' AND p.post_status = 'publish' AND pm.meta_key = 'location_id'",
                $school['school_name_th']
            ));
            
            // Get location post ID by school_name_th
            $location_post_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'location' AND post_status = 'publish'",
                $school['school_name_th']
            ));
            
            // Check if session post exists by post_name
            $existing_post = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'session'",
                $post_name
            ));
            
            // Double check by session_id in post_meta if not found
            if (!$existing_post) {
                $existing_post = $wpdb->get_var($wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} 
                    WHERE meta_key = 'session_id' AND meta_value = %s
                    AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'session')",
                    $session_id
                ));
            }
            
            // Backward compatibility: try to match old records by semantic fields.
            if (!$existing_post) {
                $existing_post = $wpdb->get_var($wpdb->prepare(
                    "SELECT p.ID
                     FROM {$wpdb->posts} p
                     INNER JOIN {$wpdb->postmeta} m_course ON (m_course.post_id = p.ID AND m_course.meta_key = 'course')
                     INNER JOIN {$wpdb->postmeta} m_location ON (m_location.post_id = p.ID AND m_location.meta_key = 'location')
                     INNER JOIN {$wpdb->postmeta} m_generation ON (m_generation.post_id = p.ID AND m_generation.meta_key = 'generation')
                     INNER JOIN {$wpdb->postmeta} m_year ON (m_year.post_id = p.ID AND m_year.meta_key = 'year')
                     INNER JOIN {$wpdb->postmeta} m_start ON (m_start.post_id = p.ID AND m_start.meta_key = 'start_date')
                     WHERE p.post_type = 'session'
                       AND m_course.meta_value = %d
                       AND m_location.meta_value = %d
                       AND m_generation.meta_value = %d
                       AND m_year.meta_value = %d
                       AND m_start.meta_value = %s
                     LIMIT 1",
                    (int) $course_post_id,
                    (int) $location_post_id,
                    (int) $school['generation'],
                    (int) $school['year'],
                    (string) $school['start_date']
                ));
            }
            
            if ($existing_post) {
                // Update existing session
                $wpdb->update(
                    $wpdb->posts,
                    [
                        'post_title' => $post_title,
                        'post_name' => $post_name,
                        'post_modified' => current_time('mysql'),
                        'post_modified_gmt' => current_time('mysql', 1)
                    ],
                    ['ID' => $existing_post],
                    ['%s', '%s', '%s', '%s'],
                    ['%d']
                );
                
                update_session_meta($existing_post, $course, $school, $course_uid, $course_post_id, $location_id_raw, $location_post_id);
                $updated++;
            } else {
                // Insert new session post
                $wpdb->insert(
                    $wpdb->posts,
                    [
                        'post_title' => $post_title,
                        'post_name' => $post_name,
                        'post_type' => 'session',
                        'post_status' => 'publish',
                        'post_date' => current_time('mysql'),
                        'post_date_gmt' => current_time('mysql', 1),
                        'post_modified' => current_time('mysql'),
                        'post_modified_gmt' => current_time('mysql', 1)
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
                );
                
                $post_id = $wpdb->insert_id;
                
                if ($post_id) {
                    update_session_meta($post_id, $course, $school, $course_uid, $course_post_id, $location_id_raw, $location_post_id);
                    $synced++;
                }
            }
        }
    }
    
    echo "Synced to sessions: {$synced} new, {$updated} updated\n";
    return ['new' => $synced, 'updated' => $updated];
}

function update_session_meta($post_id, $course, $school, $course_uid, $course_post_id, $location_id_raw, $location_post_id) {
    $session_key = lc_bma_build_session_key($course['code'], $school);
    update_post_meta($post_id, 'session_id', 'LC-' . strtoupper($session_key));
    update_post_meta($post_id, 'course_uid_raw', $course_uid);
    update_post_meta($post_id, 'course', $course_post_id);
    update_post_meta($post_id, 'location_id_raw', $location_id_raw);
    update_post_meta($post_id, 'location', $location_post_id);
    update_post_meta($post_id, 'year', $school['year']);
    update_post_meta($post_id, 'generation', $school['generation']);
    update_post_meta($post_id, 'start_date', $school['start_date']);
    update_post_meta($post_id, 'end_date', $school['end_date']);
    update_post_meta($post_id, 'reg_start', $school['start_reg']);
    update_post_meta($post_id, 'reg_end', $school['end_reg']);
    update_post_meta($post_id, 'time_period', $school['time_period']);
    update_post_meta($post_id, 'total_attendance_hours', $school['total_attendance_hours']);
    update_post_meta($post_id, 'apply_url', $school['apply_url']);
    update_post_meta($post_id, 'source', 'bmatraining.bangkok.go.th');
    update_post_meta($post_id, 'bma_last_synced_at_utc', lc_bma_now_utc());
}

function lc_bma_build_session_key($course_code, $school) {
    $course_code = (string) $course_code;
    $apply_url = (string) ($school['apply_url'] ?? '');

    // Preferred stable key from API URL: .../courses/{id}
    if ($apply_url !== '' && preg_match('~/courses/(\d+)(?:$|[/?#])~', $apply_url, $m)) {
        return sanitize_title($course_code . '-api-' . $m[1]);
    }

    // Fallback stable key from semantic fields.
    $raw = implode('|', [
        $course_code,
        (string) ($school['school_name_th'] ?? ''),
        (string) ($school['semester'] ?? ''),
        (string) ($school['year'] ?? ''),
        (string) ($school['generation'] ?? ''),
        (string) ($school['start_date'] ?? ''),
        (string) ($school['end_date'] ?? ''),
        (string) ($school['time_period'] ?? ''),
    ]);

    return sanitize_title($course_code . '-' . substr(md5($raw), 0, 12));
}

function lc_bma_build_session_title($course_title, $school) {
    $suffix_parts = [];
    $location = (string) ($school['school_name_th'] ?? '');
    if ($location !== '') $suffix_parts[] = $location;

    $semester = isset($school['semester']) ? (int) $school['semester'] : 0;
    $year = isset($school['year']) ? (int) $school['year'] : 0;
    if ($semester > 0 && $year > 0) $suffix_parts[] = "เทอม {$semester}/{$year}";

    $generation = isset($school['generation']) ? (int) $school['generation'] : 0;
    if ($generation > 0) $suffix_parts[] = "รุ่น {$generation}";

    return trim((string) $course_title) . ' - ' . implode(' | ', $suffix_parts);
}

function sync_locations_to_posts($wpdb, $school_names = null) {
    $synced = 0;
    $updated = 0;
    $school_names = is_array($school_names)
        ? array_values(array_unique(array_filter(array_map('strval', $school_names))))
        : null;

    if (is_array($school_names)) {
        $schools = array_map(function ($name) {
            return ['school_name_th' => $name];
        }, $school_names);
    } else {
        // Get unique school names from bmatraining_schools
        $schools = $wpdb->get_results(
            "SELECT DISTINCT school_name_th FROM {$wpdb->prefix}bmatraining_schools ORDER BY school_name_th",
            ARRAY_A
        );
    }
    
    foreach ($schools as $school) {
        $post_title = $school['school_name_th'];
        
        // Check if location post exists by post_title
        $existing_post = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'location'",
            $post_title
        ));
        
        if ($existing_post) {
            // Skip if location already exists
            continue;
        } else {
            // Insert new location post
            $post_name = 'location-' . sanitize_title($post_title);
            
            $wpdb->insert(
                $wpdb->posts,
                [
                    'post_title' => $post_title,
                    'post_name' => $post_name,
                    'post_type' => 'location',
                    'post_status' => 'publish',
                    'post_date' => current_time('mysql'),
                    'post_date_gmt' => current_time('mysql', 1),
                    'post_modified' => current_time('mysql'),
                    'post_modified_gmt' => current_time('mysql', 1)
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );
            
            $post_id = $wpdb->insert_id;
            
            if ($post_id) {
                $location_id = 'LOCA_BMA_' . $post_id;
                update_post_meta($post_id, 'location_id', $location_id);
                update_post_meta($post_id, 'source', 'bmatraining.bangkok.go.th');
                update_post_meta($post_id, 'bma_last_synced_at_utc', lc_bma_now_utc());
                $synced++;
            }
        }
    }
    
    echo "Synced to locations: {$synced} new\n";
    return ['new' => $synced, 'updated' => $updated];
}

if (!defined('LC_BMA_SYNC_DISABLE_AUTO_RUN') || LC_BMA_SYNC_DISABLE_AUTO_RUN !== true) {
    $result = lc_bma_run_sync(true);
    if (!$result['ok']) {
        exit(1);
    }
}
