<?php
global $wp_query;

$allowed_taxonomies = ['course_category', 'course_provider', 'audience', 'post_tag', 'skill-level'];
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
$keyword = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

$found_posts = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;
$open_only   = !isset($_GET['open_only']) || (int) $_GET['open_only'] !== 0;

$taxonomy_labels = [
    'course_category' => 'หมวดหมู่',
    'course_provider' => 'คอร์สโดย',
    'audience'        => 'เหมาะสำหรับ',
];

$facet_payload = [
    'page'             => 1,
    'open_only'        => $open_only ? 1 : 0,
    'context_taxonomy' => $context_taxonomy,
    'context_term'     => $context_term,
    'course_category'  => $selected['course_category'],
    'course_provider'  => $selected['course_provider'],
    'audience'         => $selected['audience'],
    'q'                => $keyword,
];
$facet_options = function_exists('lc_course_filter_get_facet_options')
    ? lc_course_filter_get_facet_options($facet_payload)
    : [];
?>

<div
    id="lc-archive-filters"
    class="lc-archive-filters"
    data-context-taxonomy="<?php echo esc_attr($context_taxonomy); ?>"
    data-context-term="<?php echo esc_attr($context_term); ?>"
    data-open-default="1"
>
    <div class="lc-archive-filters__head">
        <h3 class="lc-archive-filters__title">ตัวกรอง</h3>

        <button
            type="button"
            class="lc-filter-panel-toggle"
            id="lc-filter-panel-toggle"
            aria-expanded="false"
            aria-controls="lc-filter-controls"
        >
            <span>ตัวกรอง</span>
            <span class="lc-filter-panel-toggle__icon" aria-hidden="true"></span>
        </button>

        <div class="lc-archive-filters__count" id="lc-course-count">
            <span>คอร์ส</span>
            <strong><?php echo number_format_i18n($found_posts); ?></strong>
        </div>
    </div>

    <div class="lc-archive-filters__controls" id="lc-filter-controls">
        <div class="lc-filter-field lc-filter-field--status">
            <span>สถานะคอร์ส</span>
            <label class="lc-status-toggle" for="lc-open-only-toggle">
                <input class="lc-status-toggle__input" type="checkbox" id="lc-open-only-toggle" <?php checked($open_only); ?>>
                <span class="lc-status-toggle__switch" aria-hidden="true"></span>
                <span class="lc-status-toggle__text" id="lc-open-only-text"><?php echo $open_only ? 'รับสมัครอยู่' : 'ทั้งหมด'; ?></span>
            </label>
        </div>

        <label class="lc-filter-field">
            <span>ค้นหาคอร์ส</span>
            <input
                id="lc-filter-keyword"
                type="text"
                value="<?php echo esc_attr($keyword); ?>"
                placeholder="พิมพ์ชื่อคอร์ส..."
                autocomplete="off"
            >
        </label>

        <?php foreach ($taxonomy_labels as $taxonomy => $label) : ?>
            <?php if ($context_taxonomy === $taxonomy) continue; ?>
            <?php $terms = isset($facet_options[$taxonomy]) ? $facet_options[$taxonomy] : []; ?>
            <label class="lc-filter-field">
                <span><?php echo esc_html($label); ?></span>
                <div class="lc-select-wrap">
                    <select data-taxonomy="<?php echo esc_attr($taxonomy); ?>" data-placeholder="<?php echo esc_attr($label); ?>">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($terms as $term) : ?>
                                <option value="<?php echo esc_attr($term['slug']); ?>" <?php selected($selected[$taxonomy], $term['slug']); ?>>
                                    <?php echo esc_html($term['name']); ?>
                                </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </label>
        <?php endforeach; ?>

        <button type="button" class="lc-filter-reset" id="lc-filter-reset">ล้างตัวกรอง</button>
        <span class="lc-filter-loading" id="lc-filter-loading" hidden>กำลังโหลด...</span>
    </div>
</div>
