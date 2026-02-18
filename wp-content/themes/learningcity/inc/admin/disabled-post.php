<?php
/**
 * Keep backward compatibility for environments that intentionally disable WP default posts.
 * For normal use, posts stay enabled (required for the blog feature).
 */
if (!defined('LC_DISABLE_DEFAULT_POSTS') || LC_DISABLE_DEFAULT_POSTS !== true) {
    return;
}

// 1. ซ่อนเมนู Post ด้านข้าง
add_action('admin_menu', function () {
    remove_menu_page('edit.php');
});

// 2. ปิด archive/single post
add_action('template_redirect', function () {
    if (is_singular('post') || is_post_type_archive('post')) {
        wp_redirect(home_url('/'), 301);
        exit;
    }
});

// 3. ป้องกันการ query post ในหน้า archive
add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query() && (is_home() || is_post_type_archive('post'))) {
        $query->set('post_type', 'none');
    }
});

// 4. ซ่อนปุ่ม Add New ใน admin list page
add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen->post_type === 'post') {
        echo '<style>.page-title-action, .wrap .add-new-h2 { display: none !important; }</style>';
    }
});

// 5. บล็อกการเข้า post-new.php
add_action('admin_init', function () {
    if (
        is_admin()
        && strpos($_SERVER['PHP_SELF'], 'post-new.php') !== false
        && (empty($_GET['post_type']) || $_GET['post_type'] === 'post')
    ) {
        wp_die(__('Not allowed to create default posts.', 'tobo.local'), '', ['response' => 403]);
    }
});

// 6. ลบเมนู "Post" ใน admin bar (+ New)
add_action('admin_bar_menu', function ($admin_bar) {
    $admin_bar->remove_node('new-post');
}, 999);
