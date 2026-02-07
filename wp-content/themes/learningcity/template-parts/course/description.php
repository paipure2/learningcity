<?php
$ctx = get_query_var('ctx');
if (empty($ctx) || !is_array($ctx)) { echo '<!-- description: missing ctx -->'; return; }
?>

<?php if (!empty($ctx['desc'])): ?>
  <p class="sm:text-fs18 text-fs16 mt-8">
    <?php echo wp_kses_post($ctx['desc']); ?>
  </p>
<?php endif; ?>

<?php if (!empty($ctx['learning_link'])): ?>
  <a href="<?php echo esc_url($ctx['learning_link']); ?>"
     target="_blank"
     rel="noopener noreferrer"
     class="my-2 mt-4 bg-primary rounded-full block text-white text-center text-fs18 font-semibold py-2 px-4 hover:bg-primary-hover transition-colors">
    เริ่มต้นเรียน
  </a>
<?php endif; ?>
