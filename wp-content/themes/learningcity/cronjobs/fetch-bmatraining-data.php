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
$api_url = 'https://bmatraining.bangkok.go.th/api/data';

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

try {
    // Fetch data from API
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-KEY: WP0dJbTv4DeetQS7'
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || !$response) {
        throw new Exception('Failed to fetch API data. HTTP Code: ' . $http_code);
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['data'])) {
        throw new Exception('Failed to parse API data');
    }
    
    $courses = $data['data'];
    
    foreach ($courses as &$course) {
        // Insert/Update course
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
        
        $course_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bmatraining_courses WHERE course_code = %s",
            $course['code']
        ));
        
        // Delete old schools for this course
        $wpdb->delete(
            $wpdb->prefix . 'bmatraining_schools',
            ['course_id' => $course_id],
            ['%d']
        );
        
        // Insert schools
        foreach ($course['schools'] as &$school) {
            // Parse semester_year (format: "3/2568")
            $semester_parts = explode('/', $school['semester_year']);
            $semester = isset($semester_parts[0]) ? (int)$semester_parts[0] : null;
            $year = isset($semester_parts[1]) ? (int)$semester_parts[1] : null;
            
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
        }
        unset($school);
    }
    unset($course);
    
    echo "Success: Fetched and stored " . count($courses) . " courses\n";
    
    // Sync locations to wp_posts
    sync_locations_to_posts($wpdb);
    
    // Sync courses to wp_posts
    sync_courses_to_posts($wpdb, $courses);
    
    // Sync sessions to wp_posts
    sync_sessions_to_posts($wpdb, $courses);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
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
}

function update_course_meta($post_id, $course, $total_attendance_hours) {
    update_post_meta($post_id, 'course_uid', 'LC-' . $course['code']);
    update_post_meta($post_id, 'course_code', $course['code']);
    update_post_meta($post_id, 'total_minutes', $course['total_hours'] * 60);
    update_post_meta($post_id, 'total_attendance_hours', $total_attendance_hours);
    update_post_meta($post_id, 'price', $course['price']);
    update_post_meta($post_id, 'has_certificate', 1);
    update_post_meta($post_id, 'source', 'bmatraining.bangkok.go.th');

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
            // Create unique session identifier using school ID instead of long names
            $post_name = 'session-' . $course['code'] . '-' . $school['id'];
            $session_id = 'LC-' . $course['code'] . '-' . $school['id'];
            $post_title = $course['title_th'] . ' - ' . $school['school_name_th'];
            
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
            
            // Triple check by post_title if still not found
            if (!$existing_post) {
                $existing_post = $wpdb->get_var($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'session'",
                    $post_title
                ));
            }
            
            if ($existing_post) {
                // Update existing session
                $wpdb->update(
                    $wpdb->posts,
                    [
                        'post_title' => $post_title,
                        'post_modified' => current_time('mysql'),
                        'post_modified_gmt' => current_time('mysql', 1)
                    ],
                    ['ID' => $existing_post],
                    ['%s', '%s', '%s'],
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
}

function update_session_meta($post_id, $course, $school, $course_uid, $course_post_id, $location_id_raw, $location_post_id) {
    update_post_meta($post_id, 'session_id', 'LC-' . $course['code'] . '-' . $school['id']);
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
}

function sync_locations_to_posts($wpdb) {
    $synced = 0;
    $updated = 0;
    
    // Get unique school names from bmatraining_schools
    $schools = $wpdb->get_results(
        "SELECT DISTINCT school_name_th FROM {$wpdb->prefix}bmatraining_schools ORDER BY school_name_th",
        ARRAY_A
    );
    
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
                $synced++;
            }
        }
    }
    
    echo "Synced to locations: {$synced} new\n";
}
