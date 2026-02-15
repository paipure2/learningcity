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
echo '  <button class="lc-report-btn" data-modal-id="modal-course-report" data-course-report-open data-course-id="' . esc_attr($course_id) . '">';
echo '    <span class="lc-report-btn__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="currentColor"><path d="M560-80v-123l221-220q9-9 20-13t22-4q12 0 23 4.5t20 13.5l37 37q8 9 12.5 20t4.5 22q0 11-4 22.5T903-300L683-80H560Zm300-263-37-37 37 37ZM620-140h38l121-122-18-19-19-18-122 121v38ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v120h-80v-80H520v-200H240v640h240v80H240Zm280-400Zm241 199-19-18 37 37-18-19Z"/></svg></span>';
echo '    <span>แจ้งแก้ไขข้อมูล</span>';
echo '  </button>';
echo '</div>';

get_template_part('template-parts/course/modal-style');

wp_reset_postdata();
