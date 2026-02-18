<?php
/**
 * Keep ACF/TinyMCE in wp-admin on system fonts.
 * Frontend theme CSS (Anuphan/BKKDraft) must not be loaded in admin.
 */

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
