<?php
/**
 * ACF Flexible Content Preview Styles
 * โหลด CSS ไฟล์เดียวกับหน้าบ้าน (styles.css) เข้ามาใน Admin
 */

add_action('acf/admin_enqueue_scripts', function () {
    // กรณี Development Mode (Vite Dev Server)
    if (file_exists(get_template_directory() . '/inc/vite.php') && !file_exists(get_template_directory() . '/dist/.vite/manifest.json')) {
        // โหลด styles.css ไฟล์เดียวกับหน้าบ้าน
        wp_enqueue_style(
            'acf-preview-styles',
            VITE_THEME_DEV_STYLES_PATH,
            [],
            null
        );
    }
    // กรณี Production Mode
    else if (defined('VITE_THEME_MANIFEST_PATH') && file_exists(VITE_THEME_MANIFEST_PATH)) {
        $manifest = json_decode(file_get_contents(VITE_THEME_MANIFEST_PATH), true);
        $themeVersion = wp_get_theme()->get('Version');

        if (is_array($manifest)) {
            // หา styles.css ใน manifest
            foreach ($manifest as $key => $value) {
                if (strpos($key, 'styles.css') !== false) {
                    $file = $value['file'];
                    wp_enqueue_style(
                        'acf-preview-styles',
                        VITE_THEME_ASSETS_DIR . '/' . $file,
                        [],
                        $themeVersion
                    );
                    break;
                }
            }
        }
    }
}, 999);

/**
 * เพิ่ม CSS เข้าไปใน TinyMCE Editor
 * ทำให้ WYSIWYG editor ใช้ font และ style เดียวกับหน้าบ้าน
 */
add_filter('mce_css', function ($mce_css) {
    if (!empty($mce_css)) {
        $mce_css .= ',';
    }

    // กรณี Development Mode (Vite Dev Server)
    if (defined('VITE_THEME_DEV_STYLES_PATH') && !file_exists(get_template_directory() . '/dist/.vite/manifest.json')) {
        $mce_css .= VITE_THEME_DEV_STYLES_PATH;
    }
    // กรณี Production Mode
    else if (defined('VITE_THEME_MANIFEST_PATH') && file_exists(VITE_THEME_MANIFEST_PATH)) {
        $manifest = json_decode(file_get_contents(VITE_THEME_MANIFEST_PATH), true);

        if (is_array($manifest)) {
            // หา styles.css ใน manifest
            foreach ($manifest as $key => $value) {
                if (strpos($key, 'styles.css') !== false) {
                    $file = $value['file'];
                    $mce_css .= VITE_THEME_ASSETS_DIR . '/' . $file;
                    break;
                }
            }
        }
    }

    return $mce_css;
});

/**
 * เพิ่ม body_class ให้ TinyMCE เพื่อ scope CSS
 */
add_filter('tiny_mce_before_init', function ($init) {
    // เพิ่ม class ให้ body ของ TinyMCE
    $init['body_class'] = isset($init['body_class'])
        ? $init['body_class'] . ' wysiwyg-editor'
        : 'wysiwyg-editor';

    return $init;
});