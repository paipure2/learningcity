<?php
/**
 * Plugin Name: lc-course-waitlist
 * Description: Course-specific waitlist notifications when registration opens.
 * Version: 1.0.0
 * Author: LearningCity
 */

if (!defined('ABSPATH')) exit;

define('LCW_VERSION', '1.0.0');
define('LCW_PLUGIN_FILE', __FILE__);
define('LCW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LCW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LCW_DEBUG_OPTION', 'lcw_debug_logs');
define('LCW_DEBUG_MAX', 250);

function lcw_table_name() {
  global $wpdb;
  return $wpdb->prefix . 'lc_course_waitlist';
}

function lcw_normalize_email($email) {
  $email = strtolower(trim((string) $email));
  return sanitize_email($email);
}

function lcw_token() {
  return bin2hex(random_bytes(24));
}

function lcw_mask_email($email) {
  $email = (string) $email;
  if ($email === '' || strpos($email, '@') === false) return $email;
  [$local, $domain] = explode('@', $email, 2);
  if (strlen($local) <= 2) {
    $masked_local = substr($local, 0, 1) . '*';
  } else {
    $masked_local = substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 2));
  }
  return $masked_local . '@' . $domain;
}

function lcw_log($event, $data = [], $level = 'info') {
  if (!is_array($data)) $data = ['value' => $data];

  if (isset($data['email'])) $data['email'] = lcw_mask_email($data['email']);
  if (isset($data['to'])) $data['to'] = lcw_mask_email($data['to']);

  $logs = get_option(LCW_DEBUG_OPTION, []);
  if (!is_array($logs)) $logs = [];

  $logs[] = [
    'time' => current_time('mysql'),
    'level' => (string) $level,
    'event' => (string) $event,
    'data' => $data,
  ];

  if (count($logs) > LCW_DEBUG_MAX) {
    $logs = array_slice($logs, -LCW_DEBUG_MAX);
  }

  update_option(LCW_DEBUG_OPTION, $logs, false);
}

function lcw_send_via_brevo_api($to, $subject, $body_text, $body_html = '') {
  $api_key = trim((string) get_option('sib_api_key_v3', ''));
  if ($api_key === '') {
    return [false, 'brevo_api_key_missing'];
  }

  $to = lcw_normalize_email($to);
  if (!$to || !is_email($to)) {
    return [false, 'brevo_invalid_to'];
  }

  $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
  $sender_email = 'no-reply@' . ($host ? $host : 'localhost');
  $sender_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

  $payload = [
    'sender' => [
      'name' => $sender_name,
      'email' => $sender_email,
    ],
    'to' => [
      ['email' => $to],
    ],
    'subject' => (string) $subject,
    'headers' => [
      'X-LCW-Waitlist' => '1',
    ],
  ];
  if ($body_html !== '') {
    $payload['htmlContent'] = (string) $body_html;
  } else {
    $payload['textContent'] = (string) $body_text;
  }

  $res = wp_remote_post('https://api.brevo.com/v3/smtp/email', [
    'timeout' => 25,
    'headers' => [
      'accept' => 'application/json',
      'content-type' => 'application/json',
      'api-key' => $api_key,
    ],
    'body' => wp_json_encode($payload),
  ]);

  if (is_wp_error($res)) {
    return [false, 'brevo_http_error: ' . implode('; ', $res->get_error_messages())];
  }

  $code = (int) wp_remote_retrieve_response_code($res);
  $resp_body = (string) wp_remote_retrieve_body($res);
  if ($code >= 200 && $code < 300) {
    return [true, 'brevo_ok_' . $code];
  }

  return [false, 'brevo_api_error_' . $code . ': ' . $resp_body];
}

function lcw_send_mail($to, $subject, $body_text, $context = 'generic', $body_html = '') {
  $to = lcw_normalize_email($to);
  if (!$to || !is_email($to)) {
    lcw_log('send_mail_invalid_to', ['context' => $context, 'to' => $to], 'warning');
    return false;
  }

  // Prefer Brevo API when available.
  [$ok, $msg] = lcw_send_via_brevo_api($to, $subject, $body_text, $body_html);
  if ($ok) {
    lcw_log('send_mail_brevo_ok', ['context' => $context, 'to' => $to, 'meta' => $msg]);
    return true;
  }
  lcw_log('send_mail_brevo_fail', ['context' => $context, 'to' => $to, 'meta' => $msg], 'warning');

  // Fallback to wp_mail.
  $headers = ['X-LCW-Waitlist: 1'];
  $message = $body_text;
  if ($body_html !== '') {
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $message = $body_html;
  }

  $sent = wp_mail($to, $subject, $message, $headers);
  if ($sent) {
    lcw_log('send_mail_wp_mail_ok', ['context' => $context, 'to' => $to]);
    return true;
  }

  lcw_log('send_mail_wp_mail_fail', ['context' => $context, 'to' => $to], 'error');
  return false;
}

function lcw_get_email_logo_url() {
  $custom = trim((string) apply_filters('lcw_email_logo_url', ''));
  if ($custom !== '') return esc_url_raw($custom);

  return 'https://learning.bangkok.go.th/wp-content/uploads/2026/02/learningcitylogo.png';
}

function lcw_get_course_test_payload($course_id = 0) {
  $course_id = (int) $course_id;
  $fallback = [
    'title' => 'หลักสูตรตัวอย่าง: Digital Skills สำหรับผู้เริ่มต้น',
    'url' => home_url('/?lcw_test_course=1'),
    'meta' => [
      'คอร์สโดย: โรงเรียนฝึกอาชีพ',
      'สถานที่: โรงเรียนฝึกอาชีพดินแดง',
      'ช่วงเวลาสมัคร: 22 ม.ค. 25 - 22 มี.ค. 25',
      'รอบเรียน: 23 มี.ค. 25 - 22 พ.ค. 25',
    ],
    'is_real' => false,
  ];

  if (!$course_id || get_post_type($course_id) !== 'course' || get_post_status($course_id) !== 'publish') {
    return $fallback;
  }

  $fmt_date = static function ($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') return '';
    if (function_exists('lc_thai_short_date')) {
      return (string) lc_thai_short_date($raw);
    }
    if (preg_match('/^\d{8}$/', $raw)) {
      $y = substr($raw, 0, 4);
      $m = substr($raw, 4, 2);
      $d = substr($raw, 6, 2);
      return $d . '/' . $m . '/' . $y;
    }
    return $raw;
  };

  $title = get_the_title($course_id);
  $url = get_permalink($course_id);
  if (!$title || !$url) return $fallback;

  $meta = [];

  $provider_terms = wp_get_post_terms($course_id, 'course_provider', ['fields' => 'names']);
  $provider_name = (!is_wp_error($provider_terms) && !empty($provider_terms[0])) ? (string) $provider_terms[0] : '-';

  $location_name = '-';
  $reg_period = '-';
  $study_period = '-';

  $session_ids = get_posts([
    'post_type' => 'session',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_query' => [
      'relation' => 'OR',
      ['key' => 'course', 'value' => (string) $course_id, 'compare' => '='],
      ['key' => 'course', 'value' => '"' . (string) $course_id . '"', 'compare' => 'LIKE'],
    ],
  ]);
  if (!empty($session_ids[0])) {
    $sid = (int) $session_ids[0];
    $location_field = lcw_acf_or_meta('location', $sid, false);
    $location_id = 0;
    if (is_object($location_field) && !empty($location_field->ID)) $location_id = (int) $location_field->ID;
    elseif (is_numeric($location_field)) $location_id = (int) $location_field;
    if ($location_id > 0) {
      $location_title = get_the_title($location_id);
      if ($location_title) $location_name = $location_title;
    }

    $reg_start = trim((string) lcw_acf_or_meta('reg_start', $sid, false));
    $reg_end = trim((string) lcw_acf_or_meta('reg_end', $sid, false));
    $start = trim((string) lcw_acf_or_meta('start_date', $sid, false));
    $end = trim((string) lcw_acf_or_meta('end_date', $sid, false));

    if ($reg_start !== '' || $reg_end !== '') {
      $reg_period = ($reg_start !== '' ? $fmt_date($reg_start) : '-') . ($reg_end !== '' ? ' - ' . $fmt_date($reg_end) : '');
    }
    if ($start !== '' || $end !== '') {
      $study_period = ($start !== '' ? $fmt_date($start) : '-') . ($end !== '' ? ' - ' . $fmt_date($end) : '');
    }
  }

  $meta[] = 'คอร์สโดย: ' . $provider_name;
  $meta[] = 'สถานที่: ' . $location_name;
  $meta[] = 'ช่วงเวลาสมัคร: ' . $reg_period;
  $meta[] = 'รอบเรียน: ' . $study_period;

  return [
    'title' => $title,
    'url' => $url,
    'meta' => $meta,
    'is_real' => true,
  ];
}

function lcw_build_notify_email_html($course_id, $course_title, $course_url, $unsubscribe_url, $meta_lines = []) {
  $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
  $accent = '#00744B';
  $logo_url = lcw_get_email_logo_url();
  $course_title = esc_html($course_title);
  $course_url = esc_url($course_url);
  $unsubscribe_url = esc_url($unsubscribe_url);
  $site_name = esc_html($site_name);
  $logo_top_html = '';
  if ($logo_url !== '') {
    $logo_top_html = '<tr><td style="padding:18px 24px 12px 24px;background:#ffffff;"><img src="' . esc_url($logo_url) . '" alt="' . $site_name . '" style="height:58px;width:auto;display:block;margin:0 auto;"></td></tr>';
  }

  $meta_html = '';
  if (!is_array($meta_lines)) $meta_lines = [];
  foreach ($meta_lines as $m) {
    $meta_html .= '<li>' . esc_html((string) $m) . '</li>';
  }

  return '
<!doctype html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>คอร์สเปิดรับสมัครแล้ว</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f5;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f5;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">
          ' . $logo_top_html . '
          <tr>
            <td style="background:' . $accent . ';padding:18px 24px;color:#ffffff;font-size:20px;font-weight:700;text-align:center;">
              ' . $site_name . '
            </td>
          </tr>
          <tr>
            <td style="padding:24px;">
              <p style="margin:0 0 12px 0;font-size:16px;line-height:1.5;">คอร์สที่คุณติดตามเปิดรับสมัครแล้ว</p>
              <h2 style="margin:0 0 16px 0;font-size:24px;line-height:1.35;color:#111827;">' . $course_title . '</h2>
              <p style="margin:0 0 20px 0;font-size:14px;color:#4b5563;line-height:1.6;">
                คุณสามารถกลับมาดูรายละเอียดทั้งหมดของคอร์สได้ที่หน้าเว็บไซต์
              </p>
              <p style="margin:0 0 16px 0;">
                <a href="' . $course_url . '" style="display:inline-block;background:' . $accent . ';color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700;">
                  ดูคอร์สบนเว็บไซต์
                </a>
              </p>
              <p style="margin:0 0 16px 0;font-size:13px;color:#4b5563;line-height:1.5;">
                หรือคลิกลิงก์นี้
                <a href="' . $course_url . '" style="color:' . $accent . ';text-decoration:underline;">' . $course_url . '</a>
              </p>
              <ul style="margin:0 0 20px 18px;padding:0;color:#4b5563;font-size:14px;line-height:1.7;">
                ' . $meta_html . '
              </ul>
              
              <hr style="border:none;border-top:1px solid #e5e7eb;margin:0 0 16px 0;">
              <p style="margin:0;font-size:12px;color:#6b7280;line-height:1.6;">
                ไม่ต้องการรับการแจ้งเตือนคอร์สนี้อีก?
                <a href="' . $unsubscribe_url . '" style="color:' . $accent . ';text-decoration:underline;">ยกเลิกรับแจ้งเตือน</a>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

function lcw_build_test_course_email_html($to, $sample_course_id = 0) {
  $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
  $accent = '#00744B';
  $logo_url = lcw_get_email_logo_url();
  $payload = lcw_get_course_test_payload($sample_course_id);
  $to = esc_html(lcw_mask_email($to));
  $course_title = esc_html((string) $payload['title']);
  $course_url = esc_url((string) $payload['url']);
  $course_meta = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : [];
  $is_real = !empty($payload['is_real']);
  $site_name = esc_html($site_name);
  $logo_top_html = '';
  if ($logo_url !== '') {
    $logo_top_html = '<tr><td style="padding:18px 24px 12px 24px;background:#ffffff;"><img src="' . esc_url($logo_url) . '" alt="' . $site_name . '" style="height:58px;width:auto;display:block;margin:0 auto;"></td></tr>';
  }

  $meta_html = '';
  foreach ($course_meta as $m) {
    $meta_html .= '<li>' . esc_html((string) $m) . '</li>';
  }
  if ($meta_html === '') $meta_html = '<li>ไม่มีข้อมูลเพิ่มเติม</li>';
  $badge = $is_real ? 'ข้อมูลจริงจากคอร์สที่เลือก' : 'ข้อมูลตัวอย่าง';

  return '
<!doctype html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LC Waitlist Test Email</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f5;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f5;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">
          ' . $logo_top_html . '
          <tr>
            <td style="background:' . $accent . ';padding:18px 24px;color:#ffffff;font-size:20px;font-weight:700;text-align:center;">
              ' . $site_name . ' (Test)
            </td>
          </tr>
          <tr>
            <td style="padding:24px;">
              <p style="margin:0 0 10px 0;font-size:12px;color:#6b7280;">
                อีเมลทดสอบนี้กำลังถูกส่งไปที่: <strong>' . $to . '</strong>
              </p>
              <p style="margin:0 0 10px 0;display:inline-block;background:#ecfdf5;color:#065f46;font-size:12px;border-radius:999px;padding:4px 10px;">' . esc_html($badge) . '</p>
              <p style="margin:8px 0 14px 0;font-size:16px;line-height:1.5;">คอร์สที่คุณติดตามเปิดรับสมัครแล้ว</p>
              <h2 style="margin:0 0 12px 0;font-size:24px;line-height:1.35;color:#111827;">' . $course_title . '</h2>
              <p style="margin:0 0 16px 0;">
                <a href="' . $course_url . '" style="display:inline-block;background:' . $accent . ';color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700;">
                  ดูคอร์สบนเว็บไซต์
                </a>
              </p>
              <p style="margin:0 0 16px 0;font-size:13px;color:#4b5563;line-height:1.5;">
                หรือคลิกลิงก์นี้
                <a href="' . $course_url . '" style="color:' . $accent . ';text-decoration:underline;">' . $course_url . '</a>
              </p>
              <ul style="margin:0 0 20px 18px;padding:0;color:#4b5563;font-size:14px;line-height:1.7;">
                ' . $meta_html . '
              </ul>
              
              <p style="margin:0;font-size:12px;color:#6b7280;line-height:1.6;">
                นี่คืออีเมลทดสอบจากหน้า LC Waitlist Debug เพื่อเช็คว่าเส้นทางส่งอีเมลใช้งานได้
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

function lcw_acf_or_meta($key, $post_id, $raw = false) {
  if (function_exists('get_field')) {
    return get_field($key, $post_id, $raw);
  }
  return get_post_meta($post_id, $key, true);
}

function lcw_install_table() {
  global $wpdb;
  $table = lcw_table_name();
  $charset = $wpdb->get_charset_collate();

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  $sql = "CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    email_hash CHAR(64) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'subscribed',
    token VARCHAR(80) NOT NULL,
    confirmed_at DATETIME NULL,
    unsubscribed_at DATETIME NULL,
    last_notified_cycle INT UNSIGNED NOT NULL DEFAULT 0,
    last_notified_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_course_email (course_id, email_hash),
    KEY idx_course_status (course_id, status),
    KEY idx_token (token)
  ) {$charset};";
  dbDelta($sql);
  lcw_log('install_table_done', ['table' => $table]);
}
register_activation_hook(__FILE__, 'lcw_install_table');

function lcw_enqueue_assets() {
  wp_enqueue_style(
    'lcw-waitlist',
    LCW_PLUGIN_URL . 'assets/waitlist.css',
    [],
    LCW_VERSION
  );

  wp_enqueue_script(
    'lcw-waitlist',
    LCW_PLUGIN_URL . 'assets/waitlist.js',
    [],
    LCW_VERSION,
    true
  );

  wp_add_inline_script(
    'lcw-waitlist',
    'window.LCW_WAITLIST = ' . wp_json_encode([
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('lcw_waitlist'),
      'sending' => 'กำลังบันทึก...',
      'success' => 'บันทึกอีเมลแล้ว เราจะแจ้งเมื่อคอร์สนี้เปิดรับสมัคร',
      'error' => 'ไม่สามารถบันทึกได้ กรุณาลองใหม่',
    ]) . ';',
    'before'
  );
}
add_action('wp_enqueue_scripts', 'lcw_enqueue_assets');

function lcw_is_course_open($course_id) {
  $course_id = (int) $course_id;
  if (!$course_id || get_post_type($course_id) !== 'course') return false;

  $session_ids = get_posts([
    'post_type' => 'session',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
      'relation' => 'OR',
      [
        'key' => 'course',
        'value' => (string) $course_id,
        'compare' => '=',
      ],
      [
        'key' => 'course',
        'value' => '"' . (string) $course_id . '"',
        'compare' => 'LIKE',
      ],
    ],
  ]);

  if (empty($session_ids)) return false;

  $today_ts = strtotime(current_time('Y-m-d'));
  foreach ($session_ids as $sid) {
    if (function_exists('lc_is_session_open_for_reg')) {
      if (lc_is_session_open_for_reg($sid, $today_ts)) {
        return true;
      }
      continue;
    }

    $reg_start = trim((string) lcw_acf_or_meta('reg_start', $sid, false));
    $reg_end = trim((string) lcw_acf_or_meta('reg_end', $sid, false));
    $start_ts = $reg_start ? strtotime($reg_start) : 0;
    $end_ts = $reg_end ? strtotime($reg_end) : 0;

    if ($start_ts === 0 && $end_ts === 0) return true;
    if ($start_ts > 0 && $end_ts === 0 && $today_ts >= $start_ts) return true;
    if ($start_ts === 0 && $end_ts > 0 && $today_ts <= $end_ts) return true;
    if ($start_ts > 0 && $end_ts > 0 && $today_ts >= $start_ts && $today_ts <= $end_ts) return true;
  }

  return false;
}

function lcw_get_open_session_ids($course_id) {
  $course_id = (int) $course_id;
  if (!$course_id || get_post_type($course_id) !== 'course') return [];

  $session_ids = get_posts([
    'post_type' => 'session',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
      'relation' => 'OR',
      [
        'key' => 'course',
        'value' => (string) $course_id,
        'compare' => '=',
      ],
      [
        'key' => 'course',
        'value' => '"' . (string) $course_id . '"',
        'compare' => 'LIKE',
      ],
    ],
  ]);

  if (empty($session_ids)) return [];

  $today_ts = strtotime(current_time('Y-m-d'));
  $open_ids = [];

  foreach ($session_ids as $sid) {
    $is_open = function_exists('lc_is_session_open_for_reg')
      ? lc_is_session_open_for_reg($sid, $today_ts)
      : false;

    if ($is_open) {
      $open_ids[] = (int) $sid;
    }
  }

  sort($open_ids, SORT_NUMERIC);
  return $open_ids;
}

function lcw_get_first_open_apply_url($course_id) {
  $course_id = (int) $course_id;
  if (!$course_id) return get_permalink($course_id);

  $session_ids = get_posts([
    'post_type' => 'session',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
      'relation' => 'OR',
      [
        'key' => 'course',
        'value' => (string) $course_id,
        'compare' => '=',
      ],
      [
        'key' => 'course',
        'value' => '"' . (string) $course_id . '"',
        'compare' => 'LIKE',
      ],
    ],
  ]);

  $today_ts = strtotime(current_time('Y-m-d'));
  foreach ($session_ids as $sid) {
    $is_open = function_exists('lc_is_session_open_for_reg')
      ? lc_is_session_open_for_reg($sid, $today_ts)
      : false;
    if (!$is_open) continue;

    $apply_url = trim((string) lcw_acf_or_meta('apply_url', $sid, false));
    if ($apply_url !== '') return $apply_url;
  }

  return get_permalink($course_id);
}

function lcw_render_waitlist_form($course_id = 0, $mode = 'single') {
  $course_id = (int) $course_id;
  if (!$course_id || get_post_type($course_id) !== 'course') return;

  ?>
  <div class="lcw-waitlist-wrap">
    <div class="lcw-title">
      <span class="lcw-title-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" height="22" viewBox="0 -960 960 960" width="22" fill="#1f1f1f"><path d="M480-440 160-640v400h360v80H160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v280h-80v-200L480-440Zm0-80 320-200H160l320 200ZM760-40l-56-56 63-64H600v-80h167l-64-64 57-56 160 160L760-40ZM160-640v440-240 3-283 80Z"/></svg>
      </span>
      <span>แจ้งเตือนเมื่อคอร์สนี้เปิดรับสมัคร</span>
    </div>
    <p class="lcw-desc">ใส่อีเมลของคุณ แล้วเราจะแจ้งทันทีเมื่อคอร์สนี้เปิดรอบใหม่</p>
    <form class="lcw-waitlist-form" data-course-id="<?php echo esc_attr($course_id); ?>">
      <label class="sr-only" for="lcw_email_<?php echo esc_attr($mode . '_' . $course_id); ?>">อีเมล</label>
      <input
        id="lcw_email_<?php echo esc_attr($mode . '_' . $course_id); ?>"
        type="email"
        name="email"
        required
        placeholder="you@example.com"
        class="lcw-input"
      />
      <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
      <button type="submit" class="lcw-btn">รับแจ้งเตือน</button>
    </form>
    <div class="lcw-msg hidden"></div>
  </div>
  <?php
}
add_action('lcw_render_waitlist_form', 'lcw_render_waitlist_form', 10, 2);

function lcw_rate_limit_key($course_id) {
  $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
  return 'lcw_rate_' . md5($ip . '|' . (int) $course_id);
}

function lcw_ajax_subscribe() {
  check_ajax_referer('lcw_waitlist', 'nonce');

  $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
  $email_raw = isset($_POST['email']) ? wp_unslash($_POST['email']) : '';
  $honeypot = isset($_POST['website']) ? trim((string) wp_unslash($_POST['website'])) : '';

  if ($honeypot !== '') {
    wp_send_json_error(['message' => 'invalid request'], 400);
  }

  if (!$course_id || get_post_type($course_id) !== 'course' || get_post_status($course_id) !== 'publish') {
    wp_send_json_error(['message' => 'course not found'], 404);
  }

  if (lcw_is_course_open($course_id)) {
    lcw_log('subscribe_blocked_course_open', ['course_id' => $course_id], 'warning');
    wp_send_json_error(['message' => 'course is already open'], 400);
  }

  $email = lcw_normalize_email($email_raw);
  if (!$email || !is_email($email)) {
    lcw_log('subscribe_invalid_email', ['course_id' => $course_id, 'email' => $email_raw], 'warning');
    wp_send_json_error(['message' => 'invalid email'], 422);
  }

  $rate_key = lcw_rate_limit_key($course_id);
  $count = (int) get_transient($rate_key);
  if ($count >= 5) {
    lcw_log('subscribe_rate_limited', ['course_id' => $course_id], 'warning');
    wp_send_json_error(['message' => 'too many attempts'], 429);
  }
  set_transient($rate_key, $count + 1, HOUR_IN_SECONDS);

  global $wpdb;
  $table = lcw_table_name();
  $now = current_time('mysql');
  $email_hash = hash('sha256', $email);

  $existing = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$table} WHERE course_id = %d AND email_hash = %s LIMIT 1",
    $course_id,
    $email_hash
  ));

  if ($existing) {
    $update_data = [
      'email' => $email,
      'status' => 'subscribed',
      'unsubscribed_at' => null,
      'updated_at' => $now,
    ];
    $where = ['id' => (int) $existing->id];
    $wpdb->update($table, $update_data, $where);
    lcw_log('subscribe_reactivated', [
      'course_id' => $course_id,
      'email' => $email,
      'row_id' => (int) $existing->id,
    ]);
  } else {
    $wpdb->insert($table, [
      'course_id' => $course_id,
      'email' => $email,
      'email_hash' => $email_hash,
      'status' => 'subscribed',
      'token' => lcw_token(),
      'created_at' => $now,
      'updated_at' => $now,
    ]);
    lcw_log('subscribe_created', [
      'course_id' => $course_id,
      'email' => $email,
      'row_id' => (int) $wpdb->insert_id,
    ]);
  }

  wp_send_json_success([
    'message' => 'subscribed',
  ]);
}
add_action('wp_ajax_lcw_subscribe_waitlist', 'lcw_ajax_subscribe');
add_action('wp_ajax_nopriv_lcw_subscribe_waitlist', 'lcw_ajax_subscribe');

function lcw_send_notifications($course_id, $cycle) {
  global $wpdb;
  $table = lcw_table_name();
  $course_id = (int) $course_id;
  $cycle = (int) $cycle;
  if (!$course_id || $cycle <= 0) return;

  $rows = $wpdb->get_results($wpdb->prepare(
    "SELECT id, email, token, last_notified_cycle FROM {$table}
     WHERE course_id = %d
       AND status = 'subscribed'
       AND last_notified_cycle < %d",
    $course_id,
    $cycle
  ));

  if (empty($rows)) return;

  $course_title = get_the_title($course_id);
  $course_link = get_permalink($course_id);
  if (!$course_link) $course_link = home_url('/');
  $now = current_time('mysql');
  $payload = lcw_get_course_test_payload($course_id);
  $meta_lines = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : [];

  lcw_log('notify_start', [
    'course_id' => $course_id,
    'cycle' => $cycle,
    'rows' => count($rows),
    'course_title' => $course_title,
  ]);

  foreach ($rows as $row) {
    $unsubscribe_url = add_query_arg([
      'lcw_unsubscribe' => 1,
      'token' => rawurlencode((string) $row->token),
      'cid' => $course_id,
    ], home_url('/'));

    $subject = sprintf('คอร์ส "%s" เปิดรับสมัครแล้ว', $course_title);
    $body_text =
      "สวัสดี,\n\n" .
      "คอร์สที่คุณติดตามเปิดรับสมัครแล้ว:\n" .
      "{$course_title}\n" .
      implode("\n", $meta_lines) . "\n" .
      "ดูคอร์สบนเว็บไซต์: {$course_link}\n\n" .
      "หากไม่ต้องการรับการแจ้งเตือนคอร์สนี้อีก:\n{$unsubscribe_url}\n";
    $body_html = lcw_build_notify_email_html($course_id, $course_title, $course_link, $unsubscribe_url, $meta_lines);

    $sent = lcw_send_mail($row->email, $subject, $body_text, 'notify', $body_html);
    if ($sent) {
      $wpdb->update(
        $table,
        [
          'last_notified_cycle' => $cycle,
          'last_notified_at' => $now,
          'updated_at' => $now,
        ],
        ['id' => (int) $row->id]
      );
      lcw_log('notify_sent', [
        'course_id' => $course_id,
        'cycle' => $cycle,
        'row_id' => (int) $row->id,
        'to' => $row->email,
      ]);
    } else {
      lcw_log('notify_send_failed', [
        'course_id' => $course_id,
        'cycle' => $cycle,
        'row_id' => (int) $row->id,
        'to' => $row->email,
      ], 'error');
    }
  }
}

function lcw_check_and_notify_course($course_id) {
  $course_id = (int) $course_id;
  if (!$course_id || get_post_type($course_id) !== 'course') return;

  $open_session_ids = lcw_get_open_session_ids($course_id);
  $open_now = !empty($open_session_ids) ? 1 : 0;
  $prev = (int) get_post_meta($course_id, '_lcw_open_reg', true);
  $prev_sig = trim((string) get_post_meta($course_id, '_lcw_open_sig', true));
  $current_sig = implode(',', $open_session_ids);

  $prev_ids = [];
  if ($prev_sig !== '') {
    $prev_ids = array_map('intval', array_filter(array_map('trim', explode(',', $prev_sig)), 'strlen'));
  }
  $newly_opened = array_values(array_diff($open_session_ids, $prev_ids));

  lcw_log('course_open_check', [
    'course_id' => $course_id,
    'open_now' => $open_now,
    'prev' => $prev,
    'open_sessions' => $open_session_ids,
    'prev_sessions' => $prev_ids,
    'newly_opened_sessions' => $newly_opened,
  ]);

  if ($open_now && (!$prev || !empty($newly_opened))) {
    $cycle = (int) get_post_meta($course_id, '_lcw_open_cycle', true);
    $cycle++;
    update_post_meta($course_id, '_lcw_open_cycle', $cycle);
    update_post_meta($course_id, '_lcw_open_reg', 1);
    update_post_meta($course_id, '_lcw_open_sig', $current_sig);
    lcw_log('course_open_transition', [
      'course_id' => $course_id,
      'cycle' => $cycle,
      'reason' => $prev ? 'new_session_opened' : 'course_opened',
      'newly_opened_sessions' => $newly_opened,
    ]);
    lcw_send_notifications($course_id, $cycle);
    return;
  }

  if (!$open_now && $prev) {
    update_post_meta($course_id, '_lcw_open_reg', 0);
    update_post_meta($course_id, '_lcw_open_sig', '');
    lcw_log('course_closed_transition', ['course_id' => $course_id]);
    return;
  }

  if ($open_now && $prev && $prev_sig !== $current_sig) {
    update_post_meta($course_id, '_lcw_open_sig', $current_sig);
    lcw_log('course_open_sig_updated_without_send', [
      'course_id' => $course_id,
      'prev_sig' => $prev_sig,
      'current_sig' => $current_sig,
    ]);
  }
}

function lcw_schedule_course_check($course_id) {
  $course_id = (int) $course_id;
  if (!$course_id) return;
  if (!wp_next_scheduled('lcw_check_course_open_event', [$course_id])) {
    wp_schedule_single_event(time() + 15, 'lcw_check_course_open_event', [$course_id]);
    lcw_log('scheduled_check', ['course_id' => $course_id]);
  }
}

add_action('lcw_check_course_open_event', 'lcw_check_and_notify_course');

function lcw_get_course_id_from_session($session_id) {
  if (function_exists('lc_get_course_id_from_session')) {
    return (int) lc_get_course_id_from_session($session_id);
  }

  $course = lcw_acf_or_meta('course', $session_id, false);
  if (is_object($course) && isset($course->ID)) return (int) $course->ID;
  if (is_numeric($course)) return (int) $course;
  if (is_array($course) && !empty($course[0])) {
    $first = $course[0];
    if (is_object($first) && isset($first->ID)) return (int) $first->ID;
    if (is_numeric($first)) return (int) $first;
  }
  return 0;
}

add_action('save_post_session', function ($post_id, $post, $update) {
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
  $course_id = lcw_get_course_id_from_session($post_id);
  if ($course_id) {
    // Run immediately to avoid depending only on WP-Cron.
    lcw_check_and_notify_course($course_id);
    // Keep a scheduled fallback in case related data updates lag behind.
    lcw_schedule_course_check($course_id);
  }
}, 30, 3);

add_action('trashed_post', function ($post_id) {
  if (get_post_type($post_id) !== 'session') return;
  $course_id = lcw_get_course_id_from_session($post_id);
  if ($course_id) {
    lcw_check_and_notify_course($course_id);
    lcw_schedule_course_check($course_id);
  }
}, 30);

add_action('before_delete_post', function ($post_id) {
  if (get_post_type($post_id) !== 'session') return;
  $course_id = lcw_get_course_id_from_session($post_id);
  if ($course_id) {
    lcw_check_and_notify_course($course_id);
    lcw_schedule_course_check($course_id);
  }
}, 30);

function lcw_handle_unsubscribe() {
  if (empty($_GET['lcw_unsubscribe']) || empty($_GET['token']) || empty($_GET['cid'])) {
    return;
  }

  $token = sanitize_text_field(wp_unslash($_GET['token']));
  $course_id = (int) $_GET['cid'];
  if ($token === '' || !$course_id) return;

  global $wpdb;
  $table = lcw_table_name();
  $row = $wpdb->get_row($wpdb->prepare(
    "SELECT id FROM {$table} WHERE course_id = %d AND token = %s LIMIT 1",
    $course_id,
    $token
  ));

  if ($row) {
    $now = current_time('mysql');
    $wpdb->update($table, [
      'status' => 'unsubscribed',
      'unsubscribed_at' => $now,
      'updated_at' => $now,
    ], ['id' => (int) $row->id]);
    lcw_log('unsubscribe_success', ['course_id' => $course_id, 'row_id' => (int) $row->id]);
  }

  $redirect = get_permalink($course_id);
  if (!$redirect) $redirect = home_url('/');
  $redirect = add_query_arg('waitlist_unsubscribed', '1', $redirect);
  wp_safe_redirect($redirect);
  exit;
}
add_action('init', 'lcw_handle_unsubscribe');

function lcw_is_our_mail_headers($headers) {
  if (is_string($headers)) {
    return stripos($headers, 'X-LCW-Waitlist: 1') !== false;
  }
  if (is_array($headers)) {
    foreach ($headers as $h) {
      if (stripos((string) $h, 'X-LCW-Waitlist: 1') !== false) return true;
    }
  }
  return false;
}

add_action('wp_mail_failed', function ($error) {
  if (!is_wp_error($error)) return;
  $data = $error->get_error_data();
  $headers = is_array($data) && isset($data['headers']) ? $data['headers'] : [];
  if (!lcw_is_our_mail_headers($headers)) return;

  lcw_log('wp_mail_failed', [
    'errors' => $error->get_error_messages(),
    'data' => $data,
  ], 'error');
});

function lcw_render_debug_page() {
  if (!current_user_can('manage_options')) return;

  $logs = get_option(LCW_DEBUG_OPTION, []);
  if (!is_array($logs)) $logs = [];
  $logs = array_reverse($logs);

  $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
  $level_filter = isset($_GET['level']) ? sanitize_text_field(wp_unslash($_GET['level'])) : '';
  $event_filter = isset($_GET['event']) ? sanitize_text_field(wp_unslash($_GET['event'])) : '';
  $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
  if (!in_array($limit, [50, 100, 200, 500], true)) $limit = 100;

  $events = [];
  foreach ($logs as $row) {
    if (!empty($row['event'])) $events[(string) $row['event']] = true;
  }
  $events = array_keys($events);
  sort($events, SORT_NATURAL);

  $filtered = [];
  foreach ($logs as $row) {
    $row_level = isset($row['level']) ? (string) $row['level'] : '';
    $row_event = isset($row['event']) ? (string) $row['event'] : '';
    $row_data_json = wp_json_encode(isset($row['data']) ? $row['data'] : [], JSON_UNESCAPED_UNICODE);

    if ($level_filter !== '' && $row_level !== $level_filter) continue;
    if ($event_filter !== '' && $row_event !== $event_filter) continue;
    if ($q !== '') {
      $hay = mb_strtolower(
        ($row['time'] ?? '') . ' ' .
        $row_level . ' ' .
        $row_event . ' ' .
        ($row_data_json ?: '')
      );
      if (mb_strpos($hay, mb_strtolower($q)) === false) continue;
    }

    $filtered[] = $row;
    if (count($filtered) >= $limit) break;
  }

  $default_to = '';
  if (function_exists('wp_get_current_user')) {
    $u = wp_get_current_user();
    if ($u && !empty($u->user_email)) {
      $default_to = (string) $u->user_email;
    }
  }

  $course_options = get_posts([
    'post_type' => 'course',
    'post_status' => 'publish',
    'posts_per_page' => 80,
    'orderby' => 'modified',
    'order' => 'DESC',
    'fields' => 'ids',
  ]);
  ?>
  <div class="wrap">
    <h1>LC Waitlist Debug</h1>
    <p>ใช้หน้านี้เพื่อตรวจสอบการสมัคร, การ trigger ส่งเมล, และข้อผิดพลาดการส่งอีเมล</p>
    <style>
      .lcw-debug-tools { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin:10px 0; }
      .lcw-debug-tools input, .lcw-debug-tools select { min-height:32px; }
      .lcw-debug-summary { margin:8px 0 12px; color:#50575e; }
      .lcw-debug-table th { position: sticky; top: 32px; background:#fff; z-index: 1; }
      .lcw-debug-time { white-space: nowrap; font-variant-numeric: tabular-nums; }
      .lcw-debug-event { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
      .lcw-badge { display:inline-block; border-radius:999px; padding:2px 9px; font-size:11px; font-weight:700; letter-spacing:.02em; }
      .lcw-badge-info { background:#e8f2ff; color:#0b5cab; }
      .lcw-badge-warning { background:#fff5d6; color:#7a5600; }
      .lcw-badge-error { background:#ffe8e6; color:#a0221a; }
      .lcw-json { margin:0; white-space:pre-wrap; font-size:12px; line-height:1.35; max-height:140px; overflow:auto; }
      .lcw-json-wrap summary { cursor:pointer; color:#2271b1; }
    </style>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:12px 0 10px; display:flex; gap:8px; align-items:center;">
      <?php wp_nonce_field('lcw_run_check'); ?>
      <input type="hidden" name="action" value="lcw_run_check">
      <label for="lcw_course_id"><strong>Course ID</strong></label>
      <input id="lcw_course_id" type="number" name="course_id" min="1" required style="width:140px;">
      <button type="submit" class="button button-primary">Run Check Now</button>
    </form>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:6px 0 10px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
      <?php wp_nonce_field('lcw_send_test_email'); ?>
      <input type="hidden" name="action" value="lcw_send_test_email">
      <label for="lcw_test_to"><strong>Test Email To</strong></label>
      <input
        id="lcw_test_to"
        type="email"
        name="to"
        value="<?php echo esc_attr($default_to); ?>"
        required
        placeholder="you@example.com"
        style="width:260px;"
      >
      <label for="lcw_sample_course_id"><strong>Sample Course</strong></label>
      <select id="lcw_sample_course_id" name="sample_course_id" style="min-width:320px; max-width:520px;">
        <option value="0">ใช้ข้อมูลตัวอย่าง (ไม่ผูกคอร์สจริง)</option>
        <?php foreach ($course_options as $cid): ?>
          <option value="<?php echo esc_attr((string) $cid); ?>">
            #<?php echo esc_html((string) $cid); ?> - <?php echo esc_html(get_the_title($cid)); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="button button-primary">Send Test Email</button>
    </form>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:12px 0 18px;">
      <?php wp_nonce_field('lcw_clear_logs'); ?>
      <input type="hidden" name="action" value="lcw_clear_logs">
      <button type="submit" class="button button-secondary">Clear Logs</button>
    </form>

    <form method="get" class="lcw-debug-tools">
      <input type="hidden" name="page" value="lcw-debug">
      <input type="text" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Search time/event/data..." style="width:280px;">
      <select name="level">
        <option value="">All levels</option>
        <option value="info" <?php selected($level_filter, 'info'); ?>>INFO</option>
        <option value="warning" <?php selected($level_filter, 'warning'); ?>>WARNING</option>
        <option value="error" <?php selected($level_filter, 'error'); ?>>ERROR</option>
      </select>
      <select name="event">
        <option value="">All events</option>
        <?php foreach ($events as $evt): ?>
          <option value="<?php echo esc_attr($evt); ?>" <?php selected($event_filter, $evt); ?>><?php echo esc_html($evt); ?></option>
        <?php endforeach; ?>
      </select>
      <select name="limit">
        <option value="50" <?php selected($limit, 50); ?>>50 rows</option>
        <option value="100" <?php selected($limit, 100); ?>>100 rows</option>
        <option value="200" <?php selected($limit, 200); ?>>200 rows</option>
        <option value="500" <?php selected($limit, 500); ?>>500 rows</option>
      </select>
      <button type="submit" class="button button-primary">Apply</button>
      <a class="button" href="<?php echo esc_url(admin_url('tools.php?page=lcw-debug')); ?>">Reset</a>
    </form>

    <div class="lcw-debug-summary">
      Showing <?php echo esc_html((string) count($filtered)); ?> / <?php echo esc_html((string) count($logs)); ?> rows
    </div>

    <table class="widefat striped lcw-debug-table">
      <thead>
        <tr>
          <th style="width:180px;">Time</th>
          <th style="width:110px;">Level</th>
          <th style="width:280px;">Event</th>
          <th>Data</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($filtered)): ?>
          <tr><td colspan="4">No logs yet.</td></tr>
        <?php else: ?>
          <?php foreach ($filtered as $row): ?>
            <?php
              $lvl = isset($row['level']) ? strtolower((string) $row['level']) : 'info';
              $badge_class = 'lcw-badge-info';
              if ($lvl === 'warning') $badge_class = 'lcw-badge-warning';
              if ($lvl === 'error') $badge_class = 'lcw-badge-error';
              $json = wp_json_encode(isset($row['data']) ? $row['data'] : [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            ?>
            <tr>
              <td class="lcw-debug-time"><?php echo esc_html(isset($row['time']) ? (string) $row['time'] : ''); ?></td>
              <td><span class="lcw-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html(strtoupper($lvl)); ?></span></td>
              <td class="lcw-debug-event"><?php echo esc_html(isset($row['event']) ? (string) $row['event'] : ''); ?></td>
              <td>
                <details class="lcw-json-wrap">
                  <summary>View data</summary>
                  <pre class="lcw-json"><?php echo esc_html($json); ?></pre>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php
}

add_action('admin_menu', function () {
  add_submenu_page(
    'tools.php',
    'LC Waitlist Debug',
    'LC Waitlist Debug',
    'manage_options',
    'lcw-debug',
    'lcw_render_debug_page'
  );
});

add_action('admin_post_lcw_clear_logs', function () {
  if (!current_user_can('manage_options')) wp_die('forbidden');
  check_admin_referer('lcw_clear_logs');
  delete_option(LCW_DEBUG_OPTION);
  wp_safe_redirect(admin_url('tools.php?page=lcw-debug'));
  exit;
});

add_action('admin_post_lcw_run_check', function () {
  if (!current_user_can('manage_options')) wp_die('forbidden');
  check_admin_referer('lcw_run_check');

  $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
  if ($course_id > 0) {
    lcw_log('manual_check_requested', ['course_id' => $course_id]);
    lcw_check_and_notify_course($course_id);
  } else {
    lcw_log('manual_check_invalid_course_id', ['course_id' => $course_id], 'warning');
  }

  wp_safe_redirect(admin_url('tools.php?page=lcw-debug'));
  exit;
});

add_action('admin_post_lcw_send_test_email', function () {
  if (!current_user_can('manage_options')) wp_die('forbidden');
  check_admin_referer('lcw_send_test_email');

  $to = isset($_POST['to']) ? lcw_normalize_email(wp_unslash($_POST['to'])) : '';
  if (!$to || !is_email($to)) {
    lcw_log('test_mail_invalid_to', ['to' => $to], 'warning');
    wp_safe_redirect(admin_url('tools.php?page=lcw-debug'));
    exit;
  }

  $site = wp_parse_url(home_url(), PHP_URL_HOST);
  $subject = sprintf('[LC Waitlist Test] %s', $site ? $site : 'site');
  $sample_course_id = isset($_POST['sample_course_id']) ? (int) $_POST['sample_course_id'] : 0;
  $payload = lcw_get_course_test_payload($sample_course_id);
  $meta_lines = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : [];
  $meta_text = '';
  foreach ($meta_lines as $line) {
    $meta_text .= "- " . (string) $line . "\n";
  }
  if ($meta_text === '') {
    $meta_text = "- ไม่มีข้อมูลเพิ่มเติม\n";
  }

  $title_txt = (string) ($payload['title'] ?? 'หลักสูตรตัวอย่าง');
  $url_txt = (string) ($payload['url'] ?? home_url('/'));
  $is_real = !empty($payload['is_real']);
  $mode_txt = $is_real ? 'ข้อมูลจริงจากคอร์สที่เลือก' : 'ข้อมูลตัวอย่าง';

  $body =
    "LC Waitlist Test Email\n\n" .
    "ส่งทดสอบไปยัง: {$to}\n" .
    "โหมดทดสอบ: {$mode_txt}\n" .
    "หลักสูตร: {$title_txt}\n" .
    $meta_text . "\n" .
    "ลิงก์หน้าเว็บคอร์ส: {$url_txt}\n";
  $body_html = lcw_build_test_course_email_html($to, $sample_course_id);
  $sent = lcw_send_mail($to, $subject, $body, 'test', $body_html);
  if ($sent) {
    lcw_log('test_mail_sent', [
      'to' => $to,
      'subject' => $subject,
      'sample_course_id' => $sample_course_id,
      'is_real_course' => $is_real ? 1 : 0,
    ]);
  } else {
    lcw_log('test_mail_failed', [
      'to' => $to,
      'subject' => $subject,
      'sample_course_id' => $sample_course_id,
      'is_real_course' => $is_real ? 1 : 0,
    ], 'error');
  }

  wp_safe_redirect(admin_url('tools.php?page=lcw-debug'));
  exit;
});
