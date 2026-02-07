<?php
/**
 * Hook: WP Ultimate CSV Importer - Post Saved
 * Triggered after each post is saved during CSV import
 */

add_action('wp_ultimate_csv_importer_post_saved', 'handle_csv_post_saved', 10, 2);

function handle_csv_post_saved($post_id, $post_data) {
    // Get post type
    $post_type = get_post_type($post_id);
    
    // Handle different post types
    switch ($post_type) {
        case 'course':
            handle_course_post_saved($post_id, $post_data);
            break;
        case 'session':
            handle_session_post_saved($post_id, $post_data);
            break;
        case 'location':
            handle_location_post_saved($post_id, $post_data);
            break;
    }
}

function handle_course_post_saved($post_id, $post_data) {
    // Add custom logic for course post type
    error_log("Course saved: {$post_id}");
}

function handle_session_post_saved($post_id, $post_data) {
    // Add custom logic for session post type
    error_log("Session saved: {$post_id}");
}

function handle_location_post_saved($post_id, $post_data) {
    // Add custom logic for location post type
    error_log("Location saved: {$post_id}");
}
