<?php
$ctx = get_query_var('ctx');
$mode = get_query_var('mode') ?: 'single';
if (empty($ctx) || !is_array($ctx)) { echo '<!-- course gallery: missing ctx -->'; return; }

$raw_images = !empty($ctx['images']) && is_array($ctx['images']) ? $ctx['images'] : [];
$images = [];

foreach ($raw_images as $img) {
  if (is_string($img) && $img !== '') {
    $images[] = [
      'url' => $img,
      'thumb' => $img,
      'alt' => get_the_title(),
      'caption' => '',
    ];
    continue;
  }

  if (!is_array($img)) continue;

  $full = isset($img['url']) ? trim((string) $img['url']) : '';
  if ($full === '') continue;

  $thumb = '';
  if (!empty($img['sizes']) && is_array($img['sizes'])) {
    $thumb = (string) ($img['sizes']['medium_large'] ?? $img['sizes']['medium'] ?? $img['sizes']['large'] ?? '');
  }
  if ($thumb === '') {
    $thumb = isset($img['large']) ? (string) $img['large'] : $full;
  }

  $images[] = [
    'url' => $full,
    'thumb' => $thumb ?: $full,
    'alt' => isset($img['alt']) && $img['alt'] !== '' ? (string) $img['alt'] : get_the_title(),
    'caption' => isset($img['caption']) ? (string) $img['caption'] : '',
  ];
}

if (empty($images)) return;

$post_id = (int) get_the_ID();
$group = sprintf('course-gallery-%s-%d', sanitize_key($mode), $post_id);
$visible_count = min(3, count($images));
$hidden_count = max(0, count($images) - 3);
?>

<section class="mt-8" data-course-gallery>
  <h2 class="sm:text-fs24 text-fs22 font-bold mb-3">รูปภาพคอร์ส</h2>

  <div class="grid grid-cols-3 gap-2 sm:gap-3">
    <?php foreach ($images as $index => $image): ?>
      <?php
      $is_visible = $index < 3;
      $is_last_visible = $index === ($visible_count - 1);
      ?>
      <a
        href="<?php echo esc_url($image['url']); ?>"
        data-fancybox="<?php echo esc_attr($group); ?>"
        <?php if ($image['caption'] !== ''): ?>
          data-caption="<?php echo esc_attr($image['caption']); ?>"
          data-fancybox-caption="<?php echo esc_attr($image['caption']); ?>"
        <?php endif; ?>
        class="<?php echo $is_visible ? 'block' : 'hidden'; ?> relative overflow-hidden rounded-xl border border-black/5 aspect-[4/3] bg-slate-100"
      >
        <img
          src="<?php echo esc_url($image['thumb']); ?>"
          alt="<?php echo esc_attr($image['alt']); ?>"
          class="w-full h-full object-cover"
          loading="lazy"
        >
        <?php if ($hidden_count > 0 && $is_last_visible): ?>
          <span class="absolute inset-0 bg-black/45 text-white flex items-center justify-center text-fs20 font-bold">
            +<?php echo esc_html((string) $hidden_count); ?>
          </span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>
