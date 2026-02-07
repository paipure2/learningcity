<?php
// inc/course-helpers.php
if (!defined('ABSPATH')) exit;

if (!function_exists('get_term_acf_inherit')) {
  function get_term_acf_inherit($term, $field_name) {
    $current = $term;
    while ($current && !is_wp_error($current)) {
      $value = get_field($field_name, $current);
      if (!empty($value)) return $value;
      if (empty($current->parent)) break;
      $current = get_term((int)$current->parent, $current->taxonomy);
    }
    return null;
  }
}

function course_get_primary_category_context($post_id) {
  $final_color = '#00744B';

  $terms = get_the_terms($post_id, 'course_category');
  $primary = null; $parent = null;
  $primary_link = ''; $parent_link = '';

  if (!empty($terms) && !is_wp_error($terms)) {
    usort($terms, function($a, $b){ return $b->parent - $a->parent; });
    $primary = $terms[0];

    $primary_link = get_term_link($primary);

    if (!empty($primary->parent)) {
      $parent = get_term((int)$primary->parent, 'course_category');
      if ($parent && !is_wp_error($parent)) {
        $parent_link = get_term_link($parent);
      }
    }

    $term_color = get_term_acf_inherit($primary, 'color');
    if (!empty($term_color)) $final_color = $term_color;
  }

  return [
    'final_color'  => $final_color,
    'primary'      => $primary,
    'parent'       => $parent,
    'primary_link' => $primary_link,
    'parent_link'  => $parent_link,
  ];
}

function course_get_provider_context($post_id) {
  $provider_terms = get_the_terms($post_id, 'course_provider');
  $name = '';
  $term_link = '';
  $img_src = 'https://learning.bangkok.go.th/wp-content/themes/learningcity/assets/images/placeholder-gray.png';

  if (!empty($provider_terms) && !is_wp_error($provider_terms)) {
    $t = $provider_terms[0];
    $name = $t->name;
    $term_link = get_term_link($t);

    $logo = get_field('image', $t);
    $logo_url = '';
    if (is_array($logo) && !empty($logo['url'])) $logo_url = $logo['url'];
    elseif (is_string($logo) && !empty($logo)) $logo_url = $logo;
    elseif (is_numeric($logo)) $logo_url = wp_get_attachment_image_url((int)$logo, 'thumbnail');

    if (!empty($logo_url)) $img_src = $logo_url;
  }

  return [
    'name' => $name,
    'term_link' => $term_link,
    'img_src' => $img_src,
  ];
}

function course_get_thumb($post_id) {
  $thumb = get_the_post_thumbnail_url($post_id, 'large');
  return $thumb ? $thumb : 'https://learning.bangkok.go.th/wp-content/themes/learningcity/assets/images/placeholder-gray.png';
}

function course_get_duration_text($post_id) {
  $minutes = (int) get_field('total_minutes', $post_id);
  if ($minutes <= 0) return 'ตามรอบเรียน';

  $hours = floor($minutes / 60);
  $mins  = $minutes % 60;

  $out = '';
  if ($hours > 0) $out .= $hours . ' ชม. ';
  if ($mins > 0)  $out .= $mins . ' นาที';
  return trim($out);
}

function course_get_level_text($post_id) {
  $terms = get_the_terms($post_id, 'skill-level');
  if (!empty($terms) && !is_wp_error($terms)) return $terms[0]->name;
  return 'ไม่ระบุ';
}

function course_get_audience_text($post_id) {
  $terms = get_the_terms($post_id, 'audience');
  if (!empty($terms) && !is_wp_error($terms)) return implode(', ', wp_list_pluck($terms, 'name'));
  return 'ทุกวัย';
}

function course_get_price_text($post_id) {
  $price = get_field('price', $post_id);
  if ($price === null || $price === '') return 'ดูรอบเรียน';
  if ((float)$price == 0) return 'ฟรี';
  return number_format((float)$price) . ' บาท';
}

function course_get_grouped_sessions_by_location($course_id) {
  $session_ids = get_posts([
    'post_type'      => 'session',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => [[
      'key'     => 'course',
      'value'   => $course_id,
      'compare' => '=',
    ]],
  ]);

  $grouped = [];

  if (!empty($session_ids)) {
    foreach ($session_ids as $sid) {
      $location = get_field('location', $sid);

      $location_id = 0;
      if (is_object($location) && isset($location->ID)) $location_id = (int)$location->ID;
      elseif (is_numeric($location)) $location_id = (int)$location;

      if (!$location_id) continue;
      if (!isset($grouped[$location_id])) $grouped[$location_id] = [];
      $grouped[$location_id][] = $sid;
    }
  }

  return $grouped;
}
