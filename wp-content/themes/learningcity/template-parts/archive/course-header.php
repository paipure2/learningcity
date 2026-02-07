<?php
$is_search = is_search();

$placeholder_image = defined('THEME_URI')
    ? THEME_URI . '/assets/images/placeholder.png'
    : '';

/** Defaults */
$title = '';
$description = '';
$thumbnail_url = $placeholder_image;
$bg_color = function_exists('hex_to_rgba') ? hex_to_rgba('#D6EBE0', 1) : '#D6EBE0';

$show_description = false;
$show_thumbnail = false;

/** 1) Search page */
if ($is_search) {
    $title = sprintf('Search results for: "%s"', get_search_query());

/** 2) Term archive (category/tag/custom tax) */
} elseif (is_category() || is_tag() || is_tax()) {

    $term = get_queried_object(); // WP_Term
    $title = !empty($term->name) ? $term->name : '';
    $description = term_description($term);

    $show_description = !empty($description);
    $show_thumbnail = true;

    // ACF term fields (inherit) - เรียกเฉพาะถ้ามีฟังก์ชันจริง
    $thumbnail = function_exists('get_term_acf_inherit') ? get_term_acf_inherit($term, 'thumbnail') : '';
    $term_color = function_exists('get_term_acf_inherit') ? get_term_acf_inherit($term, 'color') : '';

    if (!empty($term_color) && function_exists('hex_to_rgba')) {
        $bg_color = hex_to_rgba($term_color, 0.2);
    }

    if (!empty($thumbnail)) {
        if (is_array($thumbnail) && !empty($thumbnail['url'])) {
            $thumbnail_url = $thumbnail['url'];
        } elseif (is_numeric($thumbnail)) {
            $img = wp_get_attachment_image_url((int) $thumbnail, 'full');
            if (!empty($img)) {
                $thumbnail_url = $img;
            }
        } elseif (is_string($thumbnail)) {
            $thumbnail_url = $thumbnail;
        }
    }

/** 3) Post type archive (เช่น archive-course.php) */
} elseif (is_post_type_archive()) {

    // ได้ label ของ CPT เช่น "Courses"
    $title = post_type_archive_title('', false);

    // ถ้าอยากมี description/thumbnail สำหรับ landing ให้ไปดึงจาก option หรือ hardcode ได้
    // $description = get_field('course_archive_description', 'option') ?: '';
    // $thumbnail_url = get_field('course_archive_thumbnail', 'option') ?: $thumbnail_url;

    $show_description = !empty($description);
    $show_thumbnail = !empty($thumbnail_url);

/** 4) Fallback */
} else {
    $title = wp_get_document_title();
}
?>

<div class="md:rounded-20 rounded-2xl w-full"
     style="background-color: <?php echo esc_attr($bg_color); ?>;">

    <div class="flex items-center justify-between sm:gap-8 gap-4 lg:pl-12 md:pl-8 pl-5">
        <div class="sm:py-6 py-5 w-full flex-1 max-w-[540px]">
            <h1 class="md:text-fs38 sm:text-fs26 text-fs20 font-semibold">
                <?php echo esc_html($title); ?>
            </h1>

            <?php if (!$is_search && $show_description) : ?>
                <div class="md:text-fs18 sm:text-fs16 text-fs14 font-normal sm:mt-3 mt-2 leading-snug">
                    <?php echo wp_kses_post($description); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$is_search && $show_thumbnail && !empty($thumbnail_url)) : ?>
            <div class="md:h-[190px] sm:h-[150px] h-[150px] p-2 pb-2">
                <img src="<?php echo esc_url($thumbnail_url); ?>"
                     alt="<?php echo esc_attr($title); ?>"
                     class="h-full w-auto object-contain rounded-2xl"
                     loading="lazy">
            </div>
        <?php endif; ?>
    </div>
</div>
