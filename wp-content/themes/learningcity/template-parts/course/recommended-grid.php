<?php
// template-parts/course/recommended-grid.php

$count = (int) (get_query_var('rc_count') ?: 6); // จำนวนคอร์สแนะนำ

$q = new WP_Query([
  'post_type'      => 'course',
  'post_status'    => 'publish',
  'posts_per_page' => $count,
  'orderby'        => 'rand',
  'no_found_rows'  => true,
  'meta_query'     => [
    'relation' => 'OR',
    [ 'key' => '_lc_open_reg',    'value' => 1, 'compare' => '=' ],
    [ 'key' => '_lc_has_session', 'value' => 0, 'compare' => '=' ],
  ],
]);
?>

<div class="xl:py-12 py-8 sec-course" data-aos="fade-in">
  <h2 class="text-heading">คอร์สแนะนำ</h2>

  <div class="mt-6 grid xl:grid-cols-2 grid-cols-1 xl:gap-6 gap-4">
    <?php if ($q->have_posts()) : ?>
      <?php while ($q->have_posts()) : $q->the_post(); ?>
        <?php get_template_part('template-parts/archive/course-card'); ?>
      <?php endwhile; wp_reset_postdata(); ?>
    <?php endif; ?>
  </div>
</div>
