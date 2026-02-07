<?php 
function set_global_edit_permission() {
    global $show_edit_button;
    
    if (!is_user_logged_in()) {
        $show_edit_button = false;
        return;
    }
    
    $user = wp_get_current_user();
    $allowed_roles = array('editor', 'administrator');
    $show_edit_button = !empty(array_intersect($allowed_roles, $user->roles));
}
add_action('init', 'set_global_edit_permission');