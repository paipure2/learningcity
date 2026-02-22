<?php
// template-parts/course/recommended-grid.php

$count = max(1, (int) (get_query_var('rc_count') ?: 6));
$title = trim((string) get_query_var('rc_title'));
if ($title === '') {
  $title = 'คอร์สแนะนำ';
}
$mode = (string) get_query_var('rc_mode');
if (!in_array($mode, ['random', 'popular', 'handpick', 'mixed'], true)) {
  $mode = 'random';
}
$fallback_mode = (string) get_query_var('rc_fallback_mode');
if (!in_array($fallback_mode, ['random', 'popular'], true)) {
  $fallback_mode = 'random';
}
$popular_metric = (string) get_query_var('rc_popular_metric');
if (!in_array($popular_metric, ['engagement', 'views'], true)) {
  $popular_metric = 'engagement';
}
$popular_days = max(1, (int) (get_query_var('rc_popular_days') ?: 30));
$handpicked_ids = get_query_var('rc_handpicked_ids');
$handpicked_ids = is_array($handpicked_ids) ? array_values(array_filter(array_map('intval', $handpicked_ids))) : [];

$base_meta_query = [
  'relation' => 'OR',
  ['key' => '_lc_open_reg', 'value' => 1, 'compare' => '='],
  ['key' => '_lc_has_session', 'value' => 0, 'compare' => '='],
];

$fetch_random_ids = static function (int $limit, array $exclude_ids = []) use ($base_meta_query): array {
  $ids = get_posts([
    'post_type'              => 'course',
    'post_status'            => 'publish',
    'posts_per_page'         => $limit,
    'orderby'                => 'rand',
    'fields'                 => 'ids',
    'post__not_in'           => $exclude_ids,
    'no_found_rows'          => true,
    'meta_query'             => $base_meta_query,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
  ]);
  return array_values(array_filter(array_map('intval', (array) $ids)));
};

$fetch_popular_ids = static function (int $limit, int $days, string $metric, array $exclude_ids = []) use ($base_meta_query): array {
  global $wpdb;

  $table = $wpdb->prefix . 'lc_analytics_events';
  $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
  if ($table_exists !== $table) {
    return [];
  }

  $cache_key = sprintf('lc_rec_popular_%s_%d_%d', $metric, $days, max(20, $limit * 10));
  $raw_ids = get_transient($cache_key);
  if (!is_array($raw_ids)) {
    $event_types_sql = ($metric === 'views')
      ? "'course_view'"
      : "'course_view','course_click','course_popup_click'";

    $raw_limit = max(20, $limit * 10);
    $since = gmdate('Y-m-d H:i:s', time() - (DAY_IN_SECONDS * $days));

    $sql_period = $wpdb->prepare(
      "SELECT e.object_id
       FROM {$table} e
       INNER JOIN {$wpdb->posts} p ON p.ID = e.object_id
       WHERE e.object_type = %s
         AND e.event_type IN ({$event_types_sql})
         AND e.created_at >= %s
         AND e.object_id > 0
         AND p.post_type = %s
         AND p.post_status = 'publish'
       GROUP BY e.object_id
       ORDER BY COUNT(*) DESC, MAX(e.created_at) DESC
       LIMIT %d",
      'course',
      $since,
      'course',
      $raw_limit
    );
    $raw_ids = array_values(array_filter(array_map('intval', (array) $wpdb->get_col($sql_period))));

    // If no popular data in selected period, fallback to all-time popularity first.
    if (empty($raw_ids)) {
      $sql_all_time = $wpdb->prepare(
        "SELECT e.object_id
         FROM {$table} e
         INNER JOIN {$wpdb->posts} p ON p.ID = e.object_id
         WHERE e.object_type = %s
           AND e.event_type IN ({$event_types_sql})
           AND e.object_id > 0
           AND p.post_type = %s
           AND p.post_status = 'publish'
         GROUP BY e.object_id
         ORDER BY COUNT(*) DESC, MAX(e.created_at) DESC
         LIMIT %d",
        'course',
        'course',
        $raw_limit
      );
      $raw_ids = array_values(array_filter(array_map('intval', (array) $wpdb->get_col($sql_all_time))));
    }

    set_transient($cache_key, $raw_ids, 10 * MINUTE_IN_SECONDS);
  }

  if (empty($raw_ids)) {
    return [];
  }

  $ids = get_posts([
    'post_type'              => 'course',
    'post_status'            => 'publish',
    'posts_per_page'         => $limit,
    'post__in'               => $raw_ids,
    'post__not_in'           => $exclude_ids,
    'orderby'                => 'post__in',
    'fields'                 => 'ids',
    'no_found_rows'          => true,
    'meta_query'             => $base_meta_query,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
  ]);

  return array_values(array_filter(array_map('intval', (array) $ids)));
};

$fetch_handpicked_ids = static function (array $ids, int $limit, array $exclude_ids = []) use ($base_meta_query): array {
  if (empty($ids)) {
    return [];
  }
  $results = get_posts([
    'post_type'              => 'course',
    'post_status'            => 'publish',
    'posts_per_page'         => $limit,
    'post__in'               => $ids,
    'post__not_in'           => $exclude_ids,
    'orderby'                => 'post__in',
    'fields'                 => 'ids',
    'no_found_rows'          => true,
    'meta_query'             => $base_meta_query,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
  ]);
  return array_values(array_filter(array_map('intval', (array) $results)));
};

$selected_ids = [];
$resolved_source = $mode;

if ($mode === 'handpick' || $mode === 'mixed') {
  $selected_ids = $fetch_handpicked_ids($handpicked_ids, $count, []);
}

if (count($selected_ids) < $count) {
  $need = $count - count($selected_ids);
  $auto_mode = ($mode === 'random' || $mode === 'popular') ? $mode : $fallback_mode;

  if ($auto_mode === 'popular') {
    $auto_ids = $fetch_popular_ids($need, $popular_days, $popular_metric, $selected_ids);
    if (empty($auto_ids)) {
      $resolved_source = 'random_fallback';
      $auto_ids = $fetch_random_ids($need, $selected_ids);
    } else {
      $resolved_source = 'popular';
    }
  } else {
    $resolved_source = 'random';
    $auto_ids = $fetch_random_ids($need, $selected_ids);
  }

  foreach ($auto_ids as $id) {
    if (!in_array($id, $selected_ids, true)) {
      $selected_ids[] = $id;
    }
    if (count($selected_ids) >= $count) {
      break;
    }
  }
}

$q = new WP_Query([
  'post_type'      => 'course',
  'post_status'    => 'publish',
  'posts_per_page' => $count,
  'no_found_rows'  => true,
  'post__in'       => !empty($selected_ids) ? $selected_ids : [0],
  'orderby'        => 'post__in',
  'meta_query'     => $base_meta_query,
]);
?>

<div class="xl:py-12 py-8 sec-course" data-aos="fade-in">
  <h2 class="text-heading"><?php echo esc_html($title); ?></h2>

  <div class="mt-6 grid xl:grid-cols-2 grid-cols-1 xl:gap-6 gap-4">
    <?php if ($q->have_posts()) : ?>
      <?php while ($q->have_posts()) : $q->the_post(); ?>
        <?php get_template_part('template-parts/archive/course-card'); ?>
      <?php endwhile; wp_reset_postdata(); ?>
    <?php endif; ?>
  </div>
</div>
