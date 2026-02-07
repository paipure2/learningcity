<?php
$ctx = get_query_var('ctx');
if (empty($ctx) || !is_array($ctx)) { echo '<!-- hero: missing ctx -->'; return; }
?>

<div class="flex sm:items-center sm:flex-row flex-col-reverse items-center max-sm:text-center gap-4">
  <div class="flex-1 max-sm:mt-2">

    <div>
      <?php if (!empty($ctx['cat']['parent']) && !is_wp_error($ctx['cat']['parent']) && !is_wp_error($ctx['cat']['parent_link'])): ?>
        <div class="text-fs12 opacity-70">
          <a href="<?php echo esc_url($ctx['cat']['parent_link']); ?>" class="hover:underline">
            <?php echo esc_html($ctx['cat']['parent']->name); ?>
          </a>
          <span class="mx-1">/</span>
        </div>
      <?php endif; ?>

      <?php if (!empty($ctx['cat']['primary'])): ?>
        <?php if (!is_wp_error($ctx['cat']['primary_link'])): ?>
          <a href="<?php echo esc_url($ctx['cat']['primary_link']); ?>"
             class="text-fs16 hover:underline"
             style="color:<?php echo esc_attr($ctx['cat']['final_color']); ?>">
            <?php echo esc_html($ctx['cat']['primary']->name); ?>
          </a>
        <?php else: ?>
          <div class="text-fs16" style="color:<?php echo esc_attr($ctx['cat']['final_color']); ?>">
            <?php echo esc_html($ctx['cat']['primary']->name); ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <h2 class="text-fs34 font-bold mt-1 leading-snug"><?php the_title(); ?></h2>

    <?php if (!empty($ctx['provider']['name'])): ?>
      <div class="flex justify-center sm:justify-start gap-2 mt-2">
        <a href="<?php echo !is_wp_error($ctx['provider']['term_link']) ? esc_url($ctx['provider']['term_link']) : '#'; ?>"
           class="flex items-center gap-2 hover:opacity-80 transition-opacity">
          <img src="<?php echo esc_url($ctx['provider']['img_src']); ?>"
               alt="<?php echo esc_attr($ctx['provider']['name']); ?>"
               class="w-6 aspect-square rounded-full object-cover" />
          <p class="text-fs16"><?php echo esc_html($ctx['provider']['name']); ?></p>
        </a>
      </div>
    <?php endif; ?>

  </div>

  <img src="<?php echo esc_url($ctx['thumb']); ?>"
       alt="<?php echo esc_attr(get_the_title()); ?>"
       class="w-32 aspect-square rounded-2xl object-cover max-sm:w-36">
</div>
