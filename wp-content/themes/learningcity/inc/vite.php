<?php

/*  DEV */
$lc_vite_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$lc_vite_host = preg_replace('/:\d+$/', '', $lc_vite_host);
if (in_array($lc_vite_host, ['localhost', '127.0.0.1'], true)) {
    $lc_vite_host = 'localhost';
}

if (!defined('VITE_THEME_DEV_SERVER')) {
    define('VITE_THEME_DEV_SERVER', 'http://' . $lc_vite_host . ':5173');
}

define('VITE_THEME_DEV_DIR', 'wp-content/themes/' . basename(get_template_directory()));
define('VITE_THEME_DEV_ASSETS_DIR', VITE_THEME_DEV_DIR . '/assets');
define('VITE_THEME_DEV_CLIENT_PATH', VITE_THEME_DEV_SERVER . '/' . VITE_THEME_DEV_DIR . '/@vite/client');
define('VITE_THEME_DEV_SCRIPTS_PATH', VITE_THEME_DEV_SERVER . '/' . VITE_THEME_DEV_ASSETS_DIR . '/scripts/scripts.js');
define('VITE_THEME_DEV_STYLES_PATH', VITE_THEME_DEV_SERVER . '/' . VITE_THEME_DEV_ASSETS_DIR . '/styles/styles.css');

add_action('wp_enqueue_scripts', function () {

    wp_enqueue_script('vite-client', VITE_THEME_DEV_CLIENT_PATH, [], null, true);
    add_filter('script_loader_tag', function ($tag, $handle) {
        if ($handle === 'vite-client') {
            return str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }, 10, 2);
    wp_enqueue_script('theme-scripts', VITE_THEME_DEV_SCRIPTS_PATH, [], null, true);
    add_filter('script_loader_tag', function ($tag, $handle) {
        if ($handle === 'theme-scripts') {
            return str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }, 10, 2);
    wp_enqueue_style('theme-styles', VITE_THEME_DEV_STYLES_PATH, [], null);
});
