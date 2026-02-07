<?php
// template-parts/course/category-card-index.php

$term = get_query_var('cc_term');
if (!$term || !is_object($term) || empty($term->term_id)) return;

$link = get_term_link($term);
if (is_wp_error($link)) $link = '#';

$default_color = '#00744B';
$color = get_field('color', $term);
if (empty($color)) $color = $default_color;

// bg อ่อน
$bg = function_exists('cc_lighten_hex') ? cc_lighten_hex($color, 0.85) : '#D6EBE0';

// รูป (ACF: image)
$img = get_field('thumbnail', $term);
$img_url = '';
if (is_array($img) && !empty($img['url'])) $img_url = $img['url'];
elseif (is_string($img) && !empty($img)) $img_url = $img;
elseif (is_numeric($img)) $img_url = wp_get_attachment_image_url((int)$img, 'thumbnail');

$default_img = get_query_var('cc_default_img');
if (empty($img_url)) $img_url = $default_img;
?>

<div class="swiper-slide xl:w-40! md:w-[145px]! w-[110px]!">
  <a href="<?php echo esc_url($link); ?>"
     class="card-category"
     style="background-color: <?php echo esc_attr($color); ?>;">
    <div class="bg" style="background-color:  <?php echo esc_attr($color); ?>;"></div>
    <div class="relative">
      <img class="scale-110" src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
      <p class="h-[60px] px-2 flex items-center justify-center drop-shadow-sm"><?php echo esc_html($term->name); ?></p>
    </div>
  </a>
</div>
