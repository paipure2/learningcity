<?php
/**
 * Plugin Name: LC Role Permission Manager
 * Description: Minimal role manager for Learning City. Create managed roles and assign core content permissions.
 * Version: 1.0.0
 * Author: Learning City
 */

if (!defined('ABSPATH')) {
    exit;
}

final class LC_Role_Permission_Manager {
    const OPT_MANAGED_ROLES = 'lc_rpm_managed_roles';
    const OPT_POLICIES = 'lc_rpm_role_policies';

    public static function init() {
        add_action('init', [__CLASS__, 'ensure_base_role']);
        add_action('admin_init', [__CLASS__, 'handle_admin_postbacks']);
        add_action('admin_init', [__CLASS__, 'sync_all_managed_role_caps']);
        add_action('admin_init', [__CLASS__, 'redirect_managed_users_to_edit_queue']);
        add_action('admin_menu', [__CLASS__, 'register_admin_page']);
        add_action('admin_menu', [__CLASS__, 'apply_admin_menu_visibility'], 999);
        add_action('admin_head', [__CLASS__, 'force_show_edit_queue_menu_item']);
        add_filter('login_redirect', [__CLASS__, 'login_redirect_for_managed_users'], 10, 3);
        add_filter('user_has_cap', [__CLASS__, 'grant_scoped_manage_options_for_edit_queue'], 10, 4);
    }

    public static function activate() {
        self::ensure_base_role();
        self::ensure_role_in_managed_list('lc_content_admin');
        self::maybe_init_default_policy('lc_content_admin');
    }

    public static function ensure_base_role() {
        if (!get_role('lc_content_admin')) {
            $editor = get_role('editor');
            $caps = $editor ? $editor->capabilities : ['read' => true];
            add_role('lc_content_admin', 'Content Admin', $caps);
        }
    }

    private static function get_managed_roles() {
        $roles = get_option(self::OPT_MANAGED_ROLES, []);
        if (!is_array($roles)) {
            $roles = [];
        }
        $roles = array_values(array_unique(array_filter(array_map('sanitize_key', $roles))));
        return $roles;
    }

    private static function get_policies() {
        $policies = get_option(self::OPT_POLICIES, []);
        return is_array($policies) ? $policies : [];
    }

    private static function is_administrator_user($user = null) {
        if ($user instanceof WP_User) {
            return in_array('administrator', (array) $user->roles, true);
        }

        if (!is_user_logged_in()) {
            return false;
        }

        $current_user = wp_get_current_user();
        return ($current_user instanceof WP_User) && in_array('administrator', (array) $current_user->roles, true);
    }

    private static function default_policy() {
        return [
            'can_edit_requests' => 0,
            'can_manage_course' => 0,
            'can_manage_location' => 0,
            'can_manage_session' => 0,
            'hide_other_menus' => 1,
        ];
    }

    private static function sanitize_policy($input) {
        $base = self::default_policy();
        $out = [];
        foreach ($base as $key => $unused) {
            $out[$key] = !empty($input[$key]) ? 1 : 0;
        }
        return $out;
    }

    private static function ensure_role_in_managed_list($role_slug) {
        $role_slug = sanitize_key($role_slug);
        if ($role_slug === '') {
            return;
        }
        $managed = self::get_managed_roles();
        if (!in_array($role_slug, $managed, true)) {
            $managed[] = $role_slug;
            update_option(self::OPT_MANAGED_ROLES, $managed, false);
        }
    }

    private static function maybe_init_default_policy($role_slug) {
        $role_slug = sanitize_key($role_slug);
        $policies = self::get_policies();
        if (!isset($policies[$role_slug]) || !is_array($policies[$role_slug])) {
            $policies[$role_slug] = self::default_policy();
            update_option(self::OPT_POLICIES, $policies, false);
        }
    }

    public static function handle_admin_postbacks() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['lc_rpm_action'])) {
            return;
        }

        check_admin_referer('lc_rpm_settings_action', 'lc_rpm_nonce');

        $action = sanitize_key((string) wp_unslash($_POST['lc_rpm_action']));

        if ($action === 'create_role') {
            self::handle_create_role();
            return;
        }

        if ($action === 'save_policy') {
            self::handle_save_policy();
            return;
        }

        if ($action === 'reset_policy') {
            self::handle_reset_policy();
            return;
        }
    }

    private static function handle_create_role() {
        $display = isset($_POST['new_role_name']) ? sanitize_text_field((string) wp_unslash($_POST['new_role_name'])) : '';
        $raw_slug = isset($_POST['new_role_slug']) ? sanitize_key((string) wp_unslash($_POST['new_role_slug'])) : '';
        $clone_from = isset($_POST['clone_from']) ? sanitize_key((string) wp_unslash($_POST['clone_from'])) : 'editor';

        if ($display === '') {
            wp_safe_redirect(add_query_arg(['page' => 'lc-rpm', 'lc_rpm_notice' => 'missing_name'], admin_url('users.php')));
            exit;
        }

        $slug = $raw_slug !== '' ? $raw_slug : sanitize_key('lc_' . $display);
        if ($slug === '') {
            wp_safe_redirect(add_query_arg(['page' => 'lc-rpm', 'lc_rpm_notice' => 'invalid_slug'], admin_url('users.php')));
            exit;
        }

        if (get_role($slug)) {
            wp_safe_redirect(add_query_arg(['page' => 'lc-rpm', 'lc_rpm_notice' => 'role_exists', 'role' => $slug], admin_url('users.php')));
            exit;
        }

        $clone = get_role($clone_from);
        $caps = $clone ? $clone->capabilities : ['read' => true];
        add_role($slug, $display, $caps);

        self::ensure_role_in_managed_list($slug);
        self::maybe_init_default_policy($slug);

        wp_safe_redirect(add_query_arg(['page' => 'lc-rpm', 'lc_rpm_notice' => 'role_created', 'role' => $slug], admin_url('users.php')));
        exit;
    }

    private static function handle_save_policy() {
        $role_slug = isset($_POST['role_slug']) ? sanitize_key((string) wp_unslash($_POST['role_slug'])) : '';
        if ($role_slug === '' || !get_role($role_slug)) {
            wp_safe_redirect(add_query_arg(['page' => 'lc-rpm', 'lc_rpm_notice' => 'invalid_role'], admin_url('users.php')));
            exit;
        }

        self::ensure_role_in_managed_list($role_slug);

        $policy_in = isset($_POST['policy']) && is_array($_POST['policy']) ? wp_unslash($_POST['policy']) : [];
        $policy = self::sanitize_policy($policy_in);

        $policies = self::get_policies();
        $policies[$role_slug] = $policy;
        update_option(self::OPT_POLICIES, $policies, false);

        self::sync_one_role_caps($role_slug, $policy);

        wp_safe_redirect(add_query_arg(['page' => 'lc-rpm', 'lc_rpm_notice' => 'policy_saved', 'role' => $role_slug], admin_url('users.php')));
        exit;
    }

    private static function handle_reset_policy() {
        $role_slug = isset($_POST['role_slug']) ? sanitize_key((string) wp_unslash($_POST['role_slug'])) : '';
        if ($role_slug === '' || !get_role($role_slug)) {
            wp_safe_redirect(add_query_arg(['page' => 'lc-rpm', 'lc_rpm_notice' => 'invalid_role'], admin_url('users.php')));
            exit;
        }

        $policies = self::get_policies();
        $policies[$role_slug] = self::default_policy();
        update_option(self::OPT_POLICIES, $policies, false);

        self::sync_one_role_caps($role_slug, $policies[$role_slug]);

        wp_safe_redirect(add_query_arg(['page' => 'lc-rpm', 'lc_rpm_notice' => 'policy_reset', 'role' => $role_slug], admin_url('users.php')));
        exit;
    }

    public static function sync_all_managed_role_caps() {
        if (!is_admin()) {
            return;
        }

        $managed = self::get_managed_roles();
        if (empty($managed)) {
            return;
        }

        $policies = self::get_policies();
        foreach ($managed as $role_slug) {
            $policy = isset($policies[$role_slug]) && is_array($policies[$role_slug]) ? self::sanitize_policy($policies[$role_slug]) : self::default_policy();
            self::sync_one_role_caps($role_slug, $policy);
        }
    }

    private static function cpt_caps($type) {
        $type = sanitize_key($type);
        return [
            'edit_' . $type,
            'read_' . $type,
            'delete_' . $type,
            'edit_' . $type . 's',
            'edit_others_' . $type . 's',
            'publish_' . $type . 's',
            'read_private_' . $type . 's',
            'delete_' . $type . 's',
            'delete_private_' . $type . 's',
            'delete_published_' . $type . 's',
            'delete_others_' . $type . 's',
            'edit_private_' . $type . 's',
            'edit_published_' . $type . 's',
            'create_' . $type . 's',
        ];
    }

    private static function sync_one_role_caps($role_slug, $policy) {
        $role = get_role($role_slug);
        if (!$role) {
            return;
        }

        $grant_edit_requests = !empty($policy['can_edit_requests']);
        self::set_caps($role, ['manage_lc_edit_requests'], $grant_edit_requests);

        $grant_course = !empty($policy['can_manage_course']);
        self::set_caps($role, self::cpt_caps('course'), $grant_course);

        $grant_location = !empty($policy['can_manage_location']);
        self::set_caps($role, self::cpt_caps('location'), $grant_location);

        $grant_session = !empty($policy['can_manage_session']);
        self::set_caps($role, self::cpt_caps('session'), $grant_session);
    }

    private static function set_caps($role, $caps, $grant) {
        foreach ($caps as $cap) {
            if ($grant) {
                if (!$role->has_cap($cap)) {
                    $role->add_cap($cap);
                }
            } else {
                if ($role->has_cap($cap)) {
                    $role->remove_cap($cap);
                }
            }
        }
    }

    public static function register_admin_page() {
        add_users_page(
            'Role Permission Manager',
            'Role Permission Manager',
            'manage_options',
            'lc-rpm',
            [__CLASS__, 'render_admin_page']
        );
    }

    private static function roles_for_select() {
        $wp_roles = wp_roles();
        $roles = [];
        foreach ($wp_roles->roles as $slug => $config) {
            $roles[$slug] = isset($config['name']) ? $config['name'] : $slug;
        }
        return $roles;
    }

    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $roles = self::roles_for_select();
        $managed = self::get_managed_roles();
        if (!in_array('lc_content_admin', $managed, true) && isset($roles['lc_content_admin'])) {
            $managed[] = 'lc_content_admin';
        }

        $selected_role = isset($_GET['role']) ? sanitize_key((string) wp_unslash($_GET['role'])) : 'lc_content_admin';
        if (!in_array($selected_role, $managed, true) && !empty($managed)) {
            $selected_role = $managed[0];
        }

        $policies = self::get_policies();
        $policy = isset($policies[$selected_role]) && is_array($policies[$selected_role])
            ? self::sanitize_policy($policies[$selected_role])
            : self::default_policy();

        echo '<div class="wrap">';
        echo '<h1>Role Permission Manager</h1>';
        self::render_notice();

        echo '<h2>Create Managed Role</h2>';
        echo '<form method="post" action="">';
        wp_nonce_field('lc_rpm_settings_action', 'lc_rpm_nonce');
        echo '<input type="hidden" name="lc_rpm_action" value="create_role">';
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="new_role_name">Display Name</label></th><td><input name="new_role_name" id="new_role_name" class="regular-text" required></td></tr>';
        echo '<tr><th scope="row"><label for="new_role_slug">Role Slug (optional)</label></th><td><input name="new_role_slug" id="new_role_slug" class="regular-text" placeholder="lc_content_admin"></td></tr>';
        echo '<tr><th scope="row"><label for="clone_from">Clone Capabilities From</label></th><td><select name="clone_from" id="clone_from">';
        foreach ($roles as $slug => $name) {
            echo '<option value="' . esc_attr($slug) . '">' . esc_html($name . ' (' . $slug . ')') . '</option>';
        }
        echo '</select></td></tr>';
        echo '</table>';
        submit_button('Create Role');
        echo '</form>';

        echo '<hr>';
        echo '<h2>Role Permissions</h2>';

        if (empty($managed)) {
            echo '<p>No managed roles yet.</p>';
            echo '</div>';
            return;
        }

        echo '<form method="get" action="">';
        echo '<input type="hidden" name="page" value="lc-rpm">';
        echo '<label for="role">Choose Managed Role: </label> ';
        echo '<select id="role" name="role">';
        foreach ($managed as $slug) {
            $name = isset($roles[$slug]) ? $roles[$slug] : $slug;
            echo '<option value="' . esc_attr($slug) . '" ' . selected($selected_role, $slug, false) . '>' . esc_html($name . ' (' . $slug . ')') . '</option>';
        }
        echo '</select> ';
        submit_button('Load', 'secondary', '', false);
        echo '</form>';

        if (!$selected_role || !isset($roles[$selected_role])) {
            echo '<p>Invalid role selected.</p>';
            echo '</div>';
            return;
        }

        echo '<form method="post" action="" style="margin-top:14px;">';
        wp_nonce_field('lc_rpm_settings_action', 'lc_rpm_nonce');
        echo '<input type="hidden" name="lc_rpm_action" value="save_policy">';
        echo '<input type="hidden" name="role_slug" value="' . esc_attr($selected_role) . '">';

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row">Core Access</th><td>';
        self::checkbox('policy[can_edit_requests]', 'can_edit_requests', $policy['can_edit_requests'], 'คำขอแก้ไขข้อมูล + หน้าย่อย');
        self::checkbox('policy[can_manage_course]', 'can_manage_course', $policy['can_manage_course'], 'Course (CPT)');
        self::checkbox('policy[can_manage_location]', 'can_manage_location', $policy['can_manage_location'], 'Location (CPT)');
        self::checkbox('policy[can_manage_session]', 'can_manage_session', $policy['can_manage_session'], 'Session (CPT)');
        echo '</td></tr>';

        echo '<tr><th scope="row">Menu Visibility</th><td>';
        self::checkbox('policy[hide_other_menus]', 'hide_other_menus', $policy['hide_other_menus'], 'Hide all other admin menus (keep only allowed menus + Profile)');
        echo '</td></tr>';
        echo '</table>';

        submit_button('Save Policy');
        echo '</form>';

        echo '<form method="post" action="" style="margin-top:8px;">';
        wp_nonce_field('lc_rpm_settings_action', 'lc_rpm_nonce');
        echo '<input type="hidden" name="lc_rpm_action" value="reset_policy">';
        echo '<input type="hidden" name="role_slug" value="' . esc_attr($selected_role) . '">';
        submit_button('Reset Policy To Default', 'delete');
        echo '</form>';

        echo '</div>';
    }

    private static function checkbox($name, $id, $checked, $label) {
        echo '<label for="' . esc_attr($id) . '" style="display:block;margin:6px 0;">';
        echo '<input type="checkbox" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" value="1" ' . checked((int) $checked, 1, false) . '> ';
        echo esc_html($label);
        echo '</label>';
    }

    private static function render_notice() {
        if (empty($_GET['lc_rpm_notice'])) {
            return;
        }
        $notice = sanitize_key((string) wp_unslash($_GET['lc_rpm_notice']));
        $map = [
            'missing_name' => 'Please enter a display name.',
            'invalid_slug' => 'Role slug is invalid.',
            'role_exists' => 'Role already exists.',
            'role_created' => 'Role created.',
            'invalid_role' => 'Invalid role.',
            'policy_saved' => 'Policy saved.',
            'policy_reset' => 'Policy reset to default.',
        ];
        if (!isset($map[$notice])) {
            return;
        }
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($map[$notice]) . '</p></div>';
    }

    private static function current_user_role_slugs() {
        $user = wp_get_current_user();
        return is_array($user->roles) ? array_map('sanitize_key', $user->roles) : [];
    }

    private static function get_current_managed_role_policy() {
        $managed = self::get_managed_roles();
        if (empty($managed)) {
            return [null, null];
        }

        $user_roles = self::current_user_role_slugs();
        $role_slug = null;
        foreach ($user_roles as $r) {
            if (in_array($r, $managed, true)) {
                $role_slug = $r;
                break;
            }
        }

        if (!$role_slug) {
            return [null, null];
        }

        $policies = self::get_policies();
        $policy = isset($policies[$role_slug]) && is_array($policies[$role_slug])
            ? self::sanitize_policy($policies[$role_slug])
            : self::default_policy();

        return [$role_slug, $policy];
    }

    private static function should_redirect_user_to_edit_queue($user = null) {
        if ($user instanceof WP_User) {
            if (in_array('administrator', (array) $user->roles, true)) {
                return false;
            }
            $managed = self::get_managed_roles();
            if (empty($managed)) {
                return false;
            }
            $matched_role = null;
            foreach ((array) $user->roles as $role_slug) {
                $role_slug = sanitize_key((string) $role_slug);
                if (in_array($role_slug, $managed, true)) {
                    $matched_role = $role_slug;
                    break;
                }
            }
            if (!$matched_role) {
                return false;
            }
            $policies = self::get_policies();
            $policy = isset($policies[$matched_role]) && is_array($policies[$matched_role])
                ? self::sanitize_policy($policies[$matched_role])
                : self::default_policy();
            return !empty($policy['can_edit_requests']);
        }

        if (self::is_administrator_user()) {
            return false;
        }
        list($role_slug, $policy) = self::get_current_managed_role_policy();
        if (!$role_slug) {
            return false;
        }
        return !empty($policy['can_edit_requests']);
    }

    public static function login_redirect_for_managed_users($redirect_to, $requested_redirect_to, $user) {
        if (!$user instanceof WP_User) {
            return $redirect_to;
        }
        if (!self::should_redirect_user_to_edit_queue($user)) {
            return $redirect_to;
        }
        return admin_url('admin.php?page=lc-location-edit-queue');
    }

    public static function redirect_managed_users_to_edit_queue() {
        if (!is_admin() || wp_doing_ajax()) {
            return;
        }
        if (!is_user_logged_in()) {
            return;
        }
        if (!self::should_redirect_user_to_edit_queue()) {
            return;
        }
        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
        if ($page !== '') {
            return;
        }
        if (!empty($_POST) || !empty($_REQUEST['action'])) {
            return;
        }

        global $pagenow;
        if ($pagenow === 'index.php' || $pagenow === 'admin.php') {
            wp_safe_redirect(admin_url('admin.php?page=lc-location-edit-queue'));
            exit;
        }
    }

    public static function apply_admin_menu_visibility() {
        if (!is_admin()) {
            return;
        }
        if (self::is_administrator_user()) {
            return;
        }

        list($role_slug, $policy) = self::get_current_managed_role_policy();
        if (!$role_slug || empty($policy['hide_other_menus'])) {
            return;
        }

        $allowed = self::allowed_menu_slugs_from_policy($policy);
        if (current_user_can('manage_lc_edit_requests')) {
            $allowed[] = 'lc-location-edit-queue';
        }
        $always_keep = ['index.php', 'profile.php', 'separator1', 'separator2', 'separator-last'];
        $allowed = array_values(array_unique(array_merge($allowed, $always_keep)));

        global $menu, $submenu;

        if (is_array($menu)) {
            foreach ($menu as $item) {
                if (!is_array($item) || !isset($item[2])) {
                    continue;
                }
                $slug = (string) $item[2];
                if (!in_array($slug, $allowed, true)) {
                    remove_menu_page($slug);
                }
            }
        }

        if (is_array($submenu)) {
            foreach ($submenu as $parent_slug => $items) {
                if (!in_array((string) $parent_slug, $allowed, true)) {
                    remove_menu_page((string) $parent_slug);
                    continue;
                }
                foreach ($items as $sub_item) {
                    if (!is_array($sub_item) || !isset($sub_item[2])) {
                        continue;
                    }
                    $sub_slug = (string) $sub_item[2];
                    if (!in_array($sub_slug, $allowed, true)) {
                        remove_submenu_page((string) $parent_slug, $sub_slug);
                    }
                }
            }
        }
    }

    public static function force_show_edit_queue_menu_item() {
        if (!is_admin()) {
            return;
        }

        list($role_slug, $policy) = self::get_current_managed_role_policy();
        if (!$role_slug || empty($policy['can_edit_requests'])) {
            return;
        }
        ?>
        <style>
            #toplevel_page_lc-location-edit-queue {
                display: block !important;
            }
            #toplevel_page_lc-location-edit-queue .wp-submenu li:not(:first-child) {
                display: none !important;
            }
        </style>
        <script>
            (function() {
                var el = document.getElementById('toplevel_page_lc-location-edit-queue');
                if (!el) return;
                el.classList.remove('hidden', 'asenha_hidden_menu');
                el.style.display = 'block';
            })();
        </script>
        <?php
    }

    private static function allowed_menu_slugs_from_policy($policy) {
        $allowed = [];

        if (!empty($policy['can_edit_requests'])) {
            $allowed[] = 'lc-location-edit-queue';
            $allowed[] = 'lc-staff-permissions';
            $allowed[] = 'lc-contribution-system-settings';
        }

        if (!empty($policy['can_manage_course'])) {
            $allowed[] = 'edit.php?post_type=course';
            $allowed[] = 'post-new.php?post_type=course';
        }

        if (!empty($policy['can_manage_location'])) {
            $allowed[] = 'edit.php?post_type=location';
            $allowed[] = 'post-new.php?post_type=location';
        }

        if (!empty($policy['can_manage_session'])) {
            $allowed[] = 'edit.php?post_type=session';
            $allowed[] = 'post-new.php?post_type=session';
        }

        return $allowed;
    }

    public static function grant_scoped_manage_options_for_edit_queue($allcaps, $caps, $args, $user) {
        if (!is_admin() || !$user instanceof WP_User) {
            return $allcaps;
        }

        $managed = self::get_managed_roles();
        if (empty($managed)) {
            return $allcaps;
        }

        $matched_role = null;
        foreach ((array) $user->roles as $role_slug) {
            $role_slug = sanitize_key((string) $role_slug);
            if (in_array($role_slug, $managed, true)) {
                $matched_role = $role_slug;
                break;
            }
        }

        if (!$matched_role) {
            return $allcaps;
        }

        $policies = self::get_policies();
        $policy = isset($policies[$matched_role]) && is_array($policies[$matched_role])
            ? self::sanitize_policy($policies[$matched_role])
            : self::default_policy();

        if (!empty($policy['can_edit_requests'])) {
            // Guarantee menu visibility even if stored role caps are stale.
            $allcaps['manage_lc_edit_requests'] = true;
        } else {
            return $allcaps;
        }

        if (!self::is_edit_request_context()) {
            return $allcaps;
        }

        $allcaps['manage_options'] = true;
        return $allcaps;
    }

    private static function is_edit_request_context() {
        // Allow during menu registration so the request-queue menu can be registered for this role.
        if (doing_action('admin_menu')) {
            return true;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
        if (in_array($page, ['lc-location-edit-queue'], true)) {
            return true;
        }

        $action = isset($_REQUEST['action']) ? sanitize_key((string) wp_unslash($_REQUEST['action'])) : '';
        $allowed_actions = [
            'lc_update_location_change_request_status',
            'lc_bulk_update_location_change_request_status',
            'lc_approve_location_change_request',
            'lc_reject_location_change_request',
            'lc_approve_place_submission',
            'lc_reject_place_submission',
        ];

        return in_array($action, $allowed_actions, true);
    }
}

LC_Role_Permission_Manager::init();
register_activation_hook(__FILE__, ['LC_Role_Permission_Manager', 'activate']);
