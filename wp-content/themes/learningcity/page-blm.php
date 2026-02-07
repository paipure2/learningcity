<?php
/* Template Name: Bangkok Learning Map */
get_header();
?>
<?php get_template_part('template-parts/header/site-header'); ?>
<div class="blm-viewport">
  <?php
  get_template_part('template-parts/blm/app', null, [
    'mode' => 'map',
    'place_id' => 0,
  ]);
  ?>
</div>
<?php get_footer(); ?>
