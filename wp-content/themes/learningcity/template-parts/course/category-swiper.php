<?php
// template-parts/course/category-swiper.php

$taxonomy   = get_query_var('cc_taxonomy') ?: 'course_category';
$hide_empty = (get_query_var('cc_hide_empty') !== null) ? (bool) get_query_var('cc_hide_empty') : false;
$parent     = (get_query_var('cc_parent') !== null) ? (int) get_query_var('cc_parent') : 0;

$terms = get_terms([
  'taxonomy'   => $taxonomy,
  'hide_empty' => $hide_empty,
  'parent'     => $parent, // 0 = เฉพาะหมวดหลัก, ถ้าจะเอาทุกระดับให้ set เป็น -1 แล้วเอาออกจาก args
  'orderby'    => 'name',
  'order'      => 'ASC',
]);

$default_img = get_query_var('cc_default_img') ?: (defined('THEME_URI') ? THEME_URI . '/assets/images/category/img01.png' : '');
?>

<div class="swiper xl:overflow-hidden! overflow-visible!">
  <div class="swiper-wrapper">

    <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
        <?php foreach ($terms as $term) : ?>
        <?php
            set_query_var('cc_term', $term); // 👈 ต้องเป็น object
            set_query_var('cc_default_img', $default_img);
            get_template_part('template-parts/course/category-card');
        ?>
        <?php endforeach; ?>
    <?php endif; ?>

  </div>

  <div class="swiper-control">
    <div class="swiper-pagination"></div>
    <div class="flex items-center justify-end gap-3">
      <div class="swiper-button-prev"></div>
      <div class="swiper-button-next"></div>
    </div>
  </div>
</div>
