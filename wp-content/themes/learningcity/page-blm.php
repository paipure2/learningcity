<?php
/* Template Name: Bangkok Learning Map */
$is_embed = isset($_GET['embed']) && $_GET['embed'] === '1';

if ($is_embed) :
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
  <style>
    html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }
    .blm-viewport { height: 100%; min-height: 100%; }
  </style>
</head>
<body <?php body_class('blm-embed'); ?>>
<?php wp_body_open(); ?>
<div class="blm-viewport">
  <?php
  get_template_part('template-parts/blm/app', null, [
    'mode' => 'map',
    'place_id' => 0,
  ]);
  ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
<?php
return;
endif;

get_header();
get_template_part('template-parts/header/site-header');
?>
<div class="blm-viewport">
  <?php
  get_template_part('template-parts/blm/app', null, [
    'mode' => 'map',
    'place_id' => 0,
  ]);
  ?>
</div>
<?php get_footer(); ?>
