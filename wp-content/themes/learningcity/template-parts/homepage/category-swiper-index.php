<?php
// template-parts/homepage/category-swiper-index.php

$taxonomy   = get_query_var('cc_taxonomy') ?: 'course_category';
$hide_empty = (get_query_var('cc_hide_empty') !== null) ? (bool) get_query_var('cc_hide_empty') : false;

// จะเอาเฉพาะหมวดหลักไหม
$parent_qv = get_query_var('cc_parent'); // null = ไม่ filter, 0 = top-level
$args = [
  'taxonomy'   => $taxonomy,
  'hide_empty' => $hide_empty,
  'orderby'    => 'name',
  'order'      => 'ASC',
];
if ($parent_qv !== null) $args['parent'] = (int) $parent_qv;

$terms = get_terms($args);

$default_img = get_query_var('cc_default_img') ?: (defined('THEME_URI') ? THEME_URI . '/assets/images/category/img01.png' : '');
$all_link    = get_query_var('cc_all_link') ?: '#!';
$all_text    = get_query_var('cc_all_text') ?: 'ดูกิจกรรมทั้งหมด';
?>

<div class="swiper overflow-visible! swiper-category-index">
  <div class="swiper-wrapper">

    <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
      <?php foreach ($terms as $term) : ?>
        <?php
          set_query_var('cc_term', $term);
          set_query_var('cc_default_img', $default_img);
          get_template_part('template-parts/homepage/category-card-index');
        ?>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>

  <div class="swiper-control sm:pt-4 pt-2">
    <div class="swiper-pagination"></div>
    <div class="flex items-center gap-4">
      <div class="flex items-center justify-end gap-3 max-xl:hidden">
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
      </div>
      <a href="<?php echo esc_url($all_link); ?>" class="btn-link-v2 max-xl:text-fs18!">
        <?php echo esc_html($all_text); ?>
      </a>
    </div>
  </div>
</div>
