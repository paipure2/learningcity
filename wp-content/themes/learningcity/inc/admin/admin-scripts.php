<?php
/**
 * Enqueue Admin Scripts
 * Load JavaScript for WordPress Admin / ACF Backend
 */

function enqueue_admin_scripts()
{
    // Only load on admin pages
    if (!is_admin()) {
        return;
    }

    // Development Mode (Vite Dev Server)
    if (defined('VITE_THEME_DEV_SERVER') && !file_exists(get_template_directory() . '/dist/.vite/manifest.json')) {
        // Enqueue Vite client
        wp_enqueue_script('vite-client-admin', VITE_THEME_DEV_CLIENT_PATH, [], null, false);
        add_filter('script_loader_tag', function ($tag, $handle) {
            if ($handle === 'vite-client-admin') {
                return str_replace('<script ', '<script type="module" ', $tag);
            }
            return $tag;
        }, 10, 2);

        // Enqueue admin scripts with correct path
        $admin_script_path = VITE_THEME_DEV_SERVER . '/wp-content/themes/' . basename(get_template_directory()) . '/assets/scripts/admin-scripts.js';
        wp_enqueue_script('admin-scripts', $admin_script_path, [], null, true);
        add_filter('script_loader_tag', function ($tag, $handle) {
            if ($handle === 'admin-scripts') {
                return str_replace('<script ', '<script type="module" ', $tag);
            }
            return $tag;
        }, 10, 2);
    }
    // Production Mode
    else if (defined('VITE_THEME_MANIFEST_PATH') && file_exists(VITE_THEME_MANIFEST_PATH)) {
        $manifest = json_decode(file_get_contents(VITE_THEME_MANIFEST_PATH), true);

        if (is_array($manifest)) {
            // Find admin-scripts.js in manifest
            foreach ($manifest as $key => $value) {
                if (strpos($key, 'admin-scripts.js') !== false) {
                    $file = $value['file'];
                    wp_enqueue_script(
                        'admin-scripts',
                        VITE_THEME_ASSETS_DIR . '/' . $file,
                        [],
                        wp_get_theme()->get('Version'),
                        true
                    );

                    // Add type="module" for ES modules
                    add_filter('script_loader_tag', function ($tag, $handle) {
                        if ($handle === 'admin-scripts') {
                            return str_replace('<script ', '<script type="module" ', $tag);
                        }
                        return $tag;
                    }, 10, 2);

                    break;
                }
            }
        }
    }
}

add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');
