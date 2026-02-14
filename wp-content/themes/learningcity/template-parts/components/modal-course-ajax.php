<?php
// modal-course-ajax.php
if (!defined('ABSPATH')) exit;

// หา course_id (รองรับ GET/POST) หรือ fallback จาก loop
$course_id = 0;
if (!empty($_GET['course_id'])) $course_id = (int) $_GET['course_id'];
elseif (!empty($_POST['course_id'])) $course_id = (int) $_POST['course_id'];
else $course_id = (int) get_the_ID();

if (!$course_id) {
  echo '<!-- modal-course-ajax: missing course_id -->';
  exit;
}

// set global post ให้ template tags ทำงาน
$post = get_post($course_id);
if (!$post) {
  echo '<!-- modal-course-ajax: post not found -->';
  exit;
}
setup_postdata($post);

// include helpers ถ้ายังไม่มา
if (!function_exists('course_get_primary_category_context')) {
  $helpers = get_template_directory() . '/inc/course-helpers.php';
  if (file_exists($helpers)) require_once $helpers;
}

$cat_ctx = function_exists('course_get_primary_category_context')
  ? course_get_primary_category_context($course_id)
  : ['final_color'=>'#00744B','primary'=>null,'parent'=>null,'primary_link'=>'','parent_link'=>''];

$ctx = [
  'cat' => $cat_ctx,
  'thumb' => function_exists('course_get_thumb') ? course_get_thumb($course_id) : (get_the_post_thumbnail_url($course_id, 'large') ?: 'https://learning.bangkok.go.th/wp-content/themes/learningcity/assets/images/placeholder-gray.png'),
  'provider' => function_exists('course_get_provider_context') ? course_get_provider_context($course_id) : ['name'=>'','term_link'=>'','img_src'=>'https://learning.bangkok.go.th/wp-content/themes/learningcity/assets/images/placeholder-gray.png'],

  'duration_text' => function_exists('course_get_duration_text') ? course_get_duration_text($course_id) : 'ตามรอบเรียน',
  'level_text' => function_exists('course_get_level_text') ? course_get_level_text($course_id) : 'ไม่ระบุ',
  'audience_text' => function_exists('course_get_audience_text') ? course_get_audience_text($course_id) : 'ทุกวัย',
  'has_cert' => (bool) get_field('has_certificate', $course_id),
  'price_text' => function_exists('course_get_price_text') ? course_get_price_text($course_id) : 'ดูรอบเรียน',

  'desc' => get_field('course_description', $course_id),
  'learning_link' => get_field('learning_link', $course_id),

  'grouped_sessions' => function_exists('course_get_grouped_sessions_by_location') ? course_get_grouped_sessions_by_location($course_id) : [],
];

// ส่งเข้า component
set_query_var('ctx', $ctx);
set_query_var('mode', 'modal');

// render
get_template_part('template-parts/course/hero');
get_template_part('template-parts/course/stats');
get_template_part('template-parts/course/description');
get_template_part('template-parts/course/sessions-accordion');

echo '<div class="pt-3 pb-8 flex justify-center">';
echo '  <button class="inline-flex items-center gap-2 rounded-xl border border-red-500 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50" data-modal-id="modal-course-report" data-course-report-open data-course-id="' . esc_attr($course_id) . '">แจ้งแก้ไขข้อมูล</button>';
echo '</div>';

get_template_part('template-parts/course/modal-style');

wp_reset_postdata();
