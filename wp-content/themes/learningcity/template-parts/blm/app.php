<?php
if (!defined('ABSPATH')) exit;
$mode = $args['mode'] ?? 'map';
$place_id = $args['place_id'] ?? 0;
?>
<?php get_template_part('template-parts/blm/assets'); ?>
<?php get_template_part('template-parts/blm/styles'); ?>

<div id="blmApp" class="bg-slate-50 text-slate-900" data-mode="<?php echo esc_attr($mode); ?>" data-place-id="<?php echo esc_attr($place_id); ?>">
  <?php get_template_part('template-parts/blm/topbar'); ?>

  <div class="h-full lg:grid lg:grid-cols-12">
    <?php get_template_part('template-parts/blm/sidebar'); ?>
    <?php get_template_part('template-parts/blm/list'); ?>
    <?php get_template_part('template-parts/blm/map'); ?>
  </div>

  <?php get_template_part('template-parts/blm/modals'); ?>
  <?php get_template_part('template-parts/blm/loading'); ?>
</div>

<?php get_template_part('template-parts/blm/scripts'); ?>
