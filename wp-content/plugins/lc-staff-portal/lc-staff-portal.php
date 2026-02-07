<?php
/*
Plugin Name: LC Staff Portal
Description: Staff portal auth, permissions, and REST endpoints.
Version: 0.1.0
*/

if (!defined('ABSPATH')) exit;

/* =========================================================
 * [STAFF PORTAL] ACF fields
 * ========================================================= */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) return;

  // Location staff access
  acf_add_local_field_group([
    'key' => 'group_lc_location_staff_access',
    'title' => 'Location Staff Access',
    'fields' => [
      [
        'key' => 'field_lc_location_staff_user',
        'label' => 'Staff User',
        'name' => 'staff_user',
        'type' => 'user',
        'return_format' => 'id',
        'role' => ['author', 'editor', 'administrator'],
        'multiple' => 1,
        'allow_null' => 1,
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'location',
        ],
      ],
    ],
    'position' => 'side',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
  ]);

  // Staff locations on User profile (ACF UI)
  acf_add_local_field_group([
    'key' => 'group_lc_user_staff_locations',
    'title' => 'Staff Locations',
    'fields' => [
      [
        'key' => 'field_lc_user_staff_locations',
        'label' => 'สถานที่ที่รับผิดชอบ',
        'name' => 'staff_locations',
        'type' => 'post_object',
        'post_type' => ['location'],
        'return_format' => 'id',
        'multiple' => 1,
        'ui' => 1,
        'allow_null' => 1,
      ],
    ],
    'location' => [
      [
        [
          'param' => 'user_form',
          'operator' => '==',
          'value' => 'all',
        ],
      ],
    ],
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
  ]);
});

/* =========================================================
 * [STAFF PORTAL] Helpers
 * ========================================================= */
function lc_staff_location_ids_for_user($user_id) {
  $ids = get_user_meta($user_id, 'lc_staff_location_ids', true);
  if (!is_array($ids) || empty($ids)) {
    $ids = get_field('staff_locations', 'user_' . $user_id, false);
  }
  if (!is_array($ids)) $ids = [];
  return array_values(array_filter(array_map('intval', $ids)));
}

function lc_staff_get_location_staff_users($post_id) {
  $val = get_field('staff_user', $post_id, false);
  if (is_array($val)) return array_values(array_filter(array_map('intval', $val)));
  if (is_numeric($val)) return [intval($val)];
  return [];
}

function lc_staff_set_location_staff_users($post_id, $users) {
  $users = array_values(array_filter(array_map('intval', (array) $users)));
  if (function_exists('update_field')) {
    update_field('staff_user', $users, $post_id);
  } else {
    update_post_meta($post_id, 'staff_user', $users);
  }
}

function lc_staff_can_edit_location($user_id, $post_id) {
  if (user_can($user_id, 'manage_options')) return true;
  $ids = lc_staff_location_ids_for_user($user_id);
  if (in_array((int) $post_id, $ids, true)) return true;
  $assigned = lc_staff_get_location_staff_users($post_id);
  return in_array((int) $user_id, $assigned, true);
}

/* =========================================================
 * [AUTH] Magic link + OTP
 * ========================================================= */
function lc_staff_magic_build_token() {
  return bin2hex(random_bytes(24));
}

function lc_staff_magic_hash($token) {
  return hash_hmac('sha256', $token, wp_salt('auth'));
}

function lc_staff_magic_store($user_id, $token) {
  $expires = time() + 15 * MINUTE_IN_SECONDS;
  update_user_meta($user_id, '_lc_staff_magic_hash', lc_staff_magic_hash($token));
  update_user_meta($user_id, '_lc_staff_magic_expires', $expires);
}

function lc_staff_magic_verify($user_id, $token) {
  $hash = get_user_meta($user_id, '_lc_staff_magic_hash', true);
  $expires = (int) get_user_meta($user_id, '_lc_staff_magic_expires', true);
  if (!$hash || !$expires || time() > $expires) return false;
  return hash_equals($hash, lc_staff_magic_hash($token));
}

function lc_staff_magic_clear($user_id) {
  delete_user_meta($user_id, '_lc_staff_magic_hash');
  delete_user_meta($user_id, '_lc_staff_magic_expires');
}

function lc_staff_otp_hash($otp) {
  return hash_hmac('sha256', $otp, wp_salt('auth'));
}

function lc_staff_generate_otp() {
  return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function lc_staff_store_otp($user_id, $otp) {
  $expires = time() + 10 * MINUTE_IN_SECONDS;
  update_user_meta($user_id, '_lc_staff_otp_hash', lc_staff_otp_hash($otp));
  update_user_meta($user_id, '_lc_staff_otp_expires', $expires);
  update_user_meta($user_id, '_lc_staff_otp_attempts', 0);
}

function lc_staff_clear_otp($user_id) {
  delete_user_meta($user_id, '_lc_staff_otp_hash');
  delete_user_meta($user_id, '_lc_staff_otp_expires');
  delete_user_meta($user_id, '_lc_staff_otp_attempts');
}

function lc_staff_verify_otp($user_id, $otp) {
  $hash = get_user_meta($user_id, '_lc_staff_otp_hash', true);
  $expires = (int) get_user_meta($user_id, '_lc_staff_otp_expires', true);
  $attempts = (int) get_user_meta($user_id, '_lc_staff_otp_attempts', true);
  if (!$hash || !$expires || time() > $expires) return false;
  if ($attempts >= 5) return false;
  $ok = hash_equals($hash, lc_staff_otp_hash($otp));
  update_user_meta($user_id, '_lc_staff_otp_attempts', $attempts + 1);
  return $ok;
}

function lc_staff_build_magic_link_for_user($user_id) {
  $token = lc_staff_magic_build_token();
  lc_staff_magic_store($user_id, $token);
  return add_query_arg([
    'staff_magic' => $token,
    'uid' => $user_id,
  ], site_url('/staff/'));
}

function lc_staff_send_magic_link($user_id, $email = '') {
  $user = get_user_by('id', $user_id);
  if (!$user) return false;
  $email = $email ?: $user->user_email;
  if (!is_email($email)) return false;

  $url = lc_staff_build_magic_link_for_user($user_id);
  $subject = 'ลิงก์เข้าสู่ระบบสำหรับทีมงาน';
  $message = "คลิกลิงก์เพื่อเข้าสู่ระบบ (ลิงก์หมดอายุใน 15 นาที):\n\n" . $url;
  return wp_mail($email, $subject, $message);
}

add_action('wp_ajax_nopriv_lc_staff_request_magic', function () {
  $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
  if (!$email || !is_email($email)) {
    wp_send_json_error(['message' => 'invalid_email'], 400);
  }

  // basic rate limit per email + IP
  $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
  $rate_key = 'lc_staff_magic_' . md5($email . '|' . $ip);
  if (get_transient($rate_key)) {
    wp_send_json_error(['message' => 'rate_limited'], 429);
  }
  set_transient($rate_key, 1, 60);

  $user = get_user_by('email', $email);
  if (!$user) {
    wp_send_json_error(['message' => 'not_found'], 404);
  }

  $mail_ok = lc_staff_send_magic_link($user->ID, $email);
  $url = lc_staff_build_magic_link_for_user($user->ID);
  $host = $_SERVER['HTTP_HOST'] ?? '';
  $is_local = in_array($host, ['localhost', '127.0.0.1'], true) || str_contains($host, '.local');
  if ($is_local) {
    wp_send_json_success(['message' => 'sent', 'dev_link' => $url, 'mail_ok' => (bool) $mail_ok]);
  }
  wp_send_json_success(['message' => 'sent']);
});

add_action('wp_ajax_nopriv_lc_staff_request_otp', function () {
  $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
  if (!$email || !is_email($email)) {
    wp_send_json_error(['message' => 'invalid_email'], 400);
  }

  // basic rate limit per email + IP
  $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
  $rate_key = 'lc_staff_otp_' . md5($email . '|' . $ip);
  if (get_transient($rate_key)) {
    wp_send_json_error(['message' => 'rate_limited'], 429);
  }
  set_transient($rate_key, 1, 60);

  $user = get_user_by('email', $email);
  if (!$user) {
    wp_send_json_error(['message' => 'not_found'], 404);
  }

  $otp = lc_staff_generate_otp();
  lc_staff_store_otp($user->ID, $otp);

  $subject = 'รหัส OTP เข้าสู่ระบบ';
  $message = "รหัส OTP ของคุณคือ: {$otp}\n\nรหัสหมดอายุใน 10 นาที";
  $mail_ok = wp_mail($email, $subject, $message);

  $host = $_SERVER['HTTP_HOST'] ?? '';
  $is_local = in_array($host, ['localhost', '127.0.0.1'], true) || str_contains($host, '.local');
  if ($is_local) {
    wp_send_json_success(['message' => 'sent', 'dev_otp' => $otp, 'mail_ok' => (bool) $mail_ok, 'uid' => $user->ID]);
  }
  wp_send_json_success(['message' => 'sent', 'uid' => $user->ID]);
});

add_action('wp_ajax_nopriv_lc_staff_verify_otp', function () {
  $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
  $otp = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';
  if (!$uid || !$otp) {
    wp_send_json_error(['message' => 'invalid'], 400);
  }
  if (lc_staff_verify_otp($uid, $otp)) {
    lc_staff_clear_otp($uid);
    wp_set_auth_cookie($uid, true);
    wp_send_json_success(['message' => 'ok']);
  }
  wp_send_json_error(['message' => 'invalid_otp'], 400);
});

add_action('init', function () {
  if (!isset($_GET['staff_magic'], $_GET['uid'])) return;
  $token = sanitize_text_field($_GET['staff_magic']);
  $uid = intval($_GET['uid']);
  if (!$uid || !$token) return;

  if (lc_staff_magic_verify($uid, $token)) {
    lc_staff_magic_clear($uid);
    wp_set_auth_cookie($uid, true);
    wp_safe_redirect(site_url('/staff/'));
    exit;
  }
});

/* =========================================================
 * [REST] Staff endpoints
 * ========================================================= */
add_action('rest_api_init', function () {
  register_rest_route('lc/v1', '/staff/locations', [
    'methods' => 'GET',
    'permission_callback' => function () {
      return is_user_logged_in();
    },
    'callback' => function () {
      $user_id = get_current_user_id();
      $post__in = [];
      if (!user_can($user_id, 'manage_options')) {
        $post__in = lc_staff_location_ids_for_user($user_id);
        if (empty($post__in)) return rest_ensure_response(['items' => []]);
      }
      $q = new WP_Query([
        'post_type' => 'location',
        'post_status' => 'publish',
        'posts_per_page' => 200,
        'orderby' => 'title',
        'order' => 'ASC',
        'post__in' => $post__in,
      ]);
      $items = [];
      foreach ($q->posts as $p) {
        $items[] = [
          'id' => $p->ID,
          'title' => get_the_title($p->ID),
          'phone' => get_field('phone', $p->ID),
          'hours' => get_field('opening_hours', $p->ID),
        ];
      }
      return rest_ensure_response(['items' => $items]);
    }
  ]);

  register_rest_route('lc/v1', '/staff/location/(?P<id>\d+)', [
    'methods' => 'GET',
    'permission_callback' => function ($request) {
      $id = (int) $request['id'];
      return is_user_logged_in() && lc_staff_can_edit_location(get_current_user_id(), $id);
    },
    'callback' => function ($request) {
      $id = (int) $request['id'];
      return rest_ensure_response([
        'id' => $id,
        'title' => get_the_title($id),
        'phone' => get_field('phone', $id),
        'hours' => get_field('opening_hours', $id),
      ]);
    }
  ]);

  register_rest_route('lc/v1', '/staff/location/(?P<id>\d+)', [
    'methods' => 'POST',
    'permission_callback' => function ($request) {
      $id = (int) $request['id'];
      return is_user_logged_in() && lc_staff_can_edit_location(get_current_user_id(), $id);
    },
    'callback' => function ($request) {
      $id = (int) $request['id'];
      $phone = sanitize_text_field($request->get_param('phone'));
      $hours = sanitize_textarea_field($request->get_param('hours'));

      update_field('phone', $phone, $id);
      update_field('opening_hours', $hours, $id);

      if (function_exists('blm_clear_light_cache')) blm_clear_light_cache();
      if (function_exists('blm_clear_full_cache_for_post')) blm_clear_full_cache_for_post($id);
      if (function_exists('blm_schedule_rebuild')) blm_schedule_rebuild($id);

      return rest_ensure_response(['ok' => true]);
    }
  ]);

  register_rest_route('lc/v1', '/staff/sessions', [
    'methods' => 'GET',
    'permission_callback' => function () {
      return is_user_logged_in();
    },
    'callback' => function ($request) {
      $user_id = get_current_user_id();
      $location_id = (int) $request->get_param('location_id');
      if (!$location_id) {
        return rest_ensure_response(['items' => []]);
      }
      if (!lc_staff_can_edit_location($user_id, $location_id)) {
        return new WP_Error('forbidden', 'forbidden', ['status' => 403]);
      }

      $q = new WP_Query([
        'post_type' => 'session',
        'post_status' => 'publish',
        'posts_per_page' => 200,
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => [
          'relation' => 'OR',
          [
            'key' => 'location',
            'value' => (string) $location_id,
            'compare' => '=',
          ],
          [
            'key' => 'location',
            'value' => '"' . $location_id . '"',
            'compare' => 'LIKE',
          ],
        ],
      ]);
      $items = [];
      foreach ($q->posts as $p) {
        $items[] = [
          'id' => $p->ID,
          'title' => get_the_title($p->ID),
          'edit_url' => get_edit_post_link($p->ID, ''),
          'session_details' => get_field('session_details', $p->ID),
        ];
      }
      return rest_ensure_response(['items' => $items]);
    }
  ]);

  register_rest_route('lc/v1', '/staff/session/(?P<id>\d+)', [
    'methods' => 'POST',
    'permission_callback' => function ($request) {
      $id = (int) $request['id'];
      if (!is_user_logged_in()) return false;
      $loc = get_field('location', $id, false);
      $loc_id = is_object($loc) ? (int) $loc->ID : (int) $loc;
      return $loc_id && lc_staff_can_edit_location(get_current_user_id(), $loc_id);
    },
    'callback' => function ($request) {
      $id = (int) $request['id'];
      $details = sanitize_textarea_field($request->get_param('session_details'));
      update_field('session_details', $details, $id);
      return rest_ensure_response(['ok' => true]);
    }
  ]);
});

/* =========================================================
 * [STAFF PORTAL] Assign locations from user profile
 * ========================================================= */
add_action('acf/save_post', function ($post_id) {
  if (strpos($post_id, 'user_') !== 0) return;
  $user_id = intval(str_replace('user_', '', $post_id));
  if (!$user_id) return;

  $new_locations = get_field('staff_locations', $post_id, false);
  if (!is_array($new_locations)) $new_locations = [];
  $new_locations = array_values(array_filter(array_map('intval', $new_locations)));

  update_user_meta($user_id, 'lc_staff_location_ids', $new_locations);

  // Remove user from locations no longer assigned
  $assigned = get_posts([
    'post_type' => 'location',
    'post_status' => 'publish',
    'fields' => 'ids',
    'posts_per_page' => -1,
    'meta_query' => [
      [
        'key' => 'staff_user',
        'value' => '"' . $user_id . '"',
        'compare' => 'LIKE',
      ],
    ],
  ]);
  foreach ($assigned as $loc_id) {
    if (!in_array((int) $loc_id, $new_locations, true)) {
      $users = lc_staff_get_location_staff_users($loc_id);
      $users = array_values(array_filter($users, fn($u) => (int) $u !== (int) $user_id));
      lc_staff_set_location_staff_users($loc_id, $users);
    }
  }

  // Add user to selected locations
  foreach ($new_locations as $loc_id) {
    $users = lc_staff_get_location_staff_users($loc_id);
    if (!in_array((int) $user_id, $users, true)) {
      $users[] = (int) $user_id;
      lc_staff_set_location_staff_users($loc_id, $users);
    }
  }
}, 20);

/* =========================================================
 * [ADMIN] Send staff magic link from user profile
 * ========================================================= */
add_action('admin_post_lc_staff_send_magic', function () {
  if (!current_user_can('edit_users')) {
    wp_die('Unauthorized');
  }
  $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
  check_admin_referer('lc_staff_send_magic_' . $user_id);
  if (!$user_id) {
    wp_safe_redirect(wp_get_referer());
    exit;
  }
  lc_staff_send_magic_link($user_id);
  $redirect = add_query_arg(['lc_staff_magic_sent' => '1'], wp_get_referer() ?: admin_url('users.php'));
  wp_safe_redirect($redirect);
  exit;
});

function lc_staff_render_user_magic_link_box($user) {
  if (!current_user_can('edit_users')) return;
  ?>
  <h2>Staff Login Help</h2>
  <table class="form-table">
    <tr>
      <th>ส่งลิงก์เข้าสู่ระบบ</th>
      <td>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <?php wp_nonce_field('lc_staff_send_magic_' . $user->ID); ?>
          <input type="hidden" name="action" value="lc_staff_send_magic">
          <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
          <button class="button button-primary">ส่งลิงก์ให้ผู้ใช้</button>
        </form>
        <?php if (isset($_GET['lc_staff_magic_sent'])): ?>
          <p class="description">ส่งลิงก์แล้ว</p>
        <?php endif; ?>
      </td>
    </tr>
  </table>
  <?php
}
add_action('show_user_profile', 'lc_staff_render_user_magic_link_box');
add_action('edit_user_profile', 'lc_staff_render_user_magic_link_box');
