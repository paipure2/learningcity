<?php
$ctx = get_query_var('ctx');
if (empty($ctx) || !is_array($ctx)) { echo '<!-- hero: missing ctx -->'; return; }

$provider_ctx = $ctx['provider'] ?? [];
$providers = [];
if (!empty($provider_ctx['providers']) && is_array($provider_ctx['providers'])) {
  $providers = array_values(array_filter($provider_ctx['providers'], function ($provider) {
    return !empty($provider['name']);
  }));
}
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

    <?php if (!empty($providers)): ?>
      <div class="flex justify-center sm:justify-start mt-2">
        <div class="flex items-center gap-3 flex-wrap">
          <div class="flex items-center">
            <?php foreach (array_slice($providers, 0, 4) as $index => $provider): ?>
              <?php
              $provider_link = !empty($provider['term_link']) && !is_wp_error($provider['term_link']) ? $provider['term_link'] : '';
              ?>
              <<?php echo $provider_link ? 'a' : 'span'; ?>
                <?php if ($provider_link): ?>href="<?php echo esc_url($provider_link); ?>"<?php endif; ?>
                class="block <?php echo $index > 0 ? '-ml-2' : ''; ?> hover:opacity-80 transition-opacity">
                <img src="<?php echo esc_url($provider['img_src']); ?>"
                     alt="<?php echo esc_attr($provider['name']); ?>"
                     class="w-8 h-8 rounded-full object-cover border-2 border-white bg-white" />
              </<?php echo $provider_link ? 'a' : 'span'; ?>>
            <?php endforeach; ?>
          </div>
          <div class="text-fs16 leading-snug">
            <?php foreach ($providers as $index => $provider): ?>
              <?php
              $provider_link = !empty($provider['term_link']) && !is_wp_error($provider['term_link']) ? $provider['term_link'] : '';
              ?>
              <?php if ($index > 0): ?><span class="text-black/50">, </span><?php endif; ?>
              <?php if ($provider_link): ?>
                <a href="<?php echo esc_url($provider_link); ?>" class="hover:underline">
                  <?php echo esc_html($provider['name']); ?>
                </a>
              <?php else: ?>
                <span><?php echo esc_html($provider['name']); ?></span>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>

  <img src="<?php echo esc_url($ctx['thumb']); ?>"
       alt="<?php echo esc_attr(get_the_title()); ?>"
       class="w-32 aspect-square rounded-2xl object-cover max-sm:w-36">
</div>
