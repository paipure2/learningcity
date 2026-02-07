<?php

function enqueue_acf_tab_navigation()
{
    if (is_admin()) {
        wp_enqueue_script(
            'acf-tab-navigation',
            get_template_directory_uri() . '/assets/scripts/admin/acf-tab.js',
            array('jquery'),
            '1.0',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'enqueue_acf_tab_navigation');
