<?php
global $wp_query;

$allowed_taxonomies = ['course_category', 'course_provider', 'audience'];
$queried            = get_queried_object();

$context_taxonomy = '';
$context_term     = '';
if ($queried instanceof WP_Term && in_array($queried->taxonomy, $allowed_taxonomies, true)) {
    $context_taxonomy = $queried->taxonomy;
    $context_term     = $queried->slug;
}

$selected = [
    'course_category' => isset($_GET['course_category']) ? sanitize_title(wp_unslash($_GET['course_category'])) : '',
    'course_provider' => isset($_GET['course_provider']) ? sanitize_title(wp_unslash($_GET['course_provider'])) : '',
    'audience'        => isset($_GET['audience']) ? sanitize_title(wp_unslash($_GET['audience'])) : '',
];

$found_posts = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;

$taxonomy_labels = [
    'course_category' => 'หมวดหมู่',
    'course_provider' => 'คอร์สโดย',
    'audience'        => 'เหมาะสำหรับ',
];
?>

<div
    id="lc-archive-filters"
    class="lc-archive-filters"
    data-context-taxonomy="<?php echo esc_attr($context_taxonomy); ?>"
    data-context-term="<?php echo esc_attr($context_term); ?>"
    data-open-default="1"
>
    <div class="lc-archive-filters__head">
        <h3 class="lc-archive-filters__title">ฟิลเตอร์</h3>

        <label class="lc-open-only-toggle" for="lc-open-only-toggle">
            <input type="checkbox" id="lc-open-only-toggle" checked>
            <span>เปิดรับอยู่</span>
        </label>

        <div class="lc-archive-filters__count" id="lc-course-count">
            <span>คอร์ส</span>
            <strong><?php echo number_format_i18n($found_posts); ?></strong>
        </div>
    </div>

    <div class="lc-archive-filters__controls">
        <?php foreach ($taxonomy_labels as $taxonomy => $label) : ?>
            <?php if ($context_taxonomy === $taxonomy) continue; ?>
            <?php
            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
            ]);
            ?>
            <label class="lc-filter-field">
                <span><?php echo esc_html($label); ?></span>
                <select data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                    <option value="">ทั้งหมด</option>
                    <?php if (!is_wp_error($terms)) : ?>
                        <?php foreach ($terms as $term) : ?>
                            <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected[$taxonomy], $term->slug); ?>>
                                <?php echo esc_html($term->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </label>
        <?php endforeach; ?>

        <button type="button" class="lc-filter-reset" id="lc-filter-reset">ล้างตัวกรอง</button>
        <span class="lc-filter-loading" id="lc-filter-loading" hidden>กำลังโหลด...</span>
    </div>
</div>
