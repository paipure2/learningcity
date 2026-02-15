<?php
$post_id = get_the_ID();

if (function_exists('lc_course_should_show_in_archive') && !lc_course_should_show_in_archive($post_id)) {
    return;
}

// 1. ดึงข้อมูลหมวดหมู่หลัก (Primary Term) และสี
$primary_term_name = '';
$final_color = '#00744B'; // สี Default

$cat_terms = get_the_terms($post_id, 'course_category');
if (!empty($cat_terms) && !is_wp_error($cat_terms)) {
    // เรียงให้ Term ลูกอยู่ก่อน (เช็กจาก parent)
    usort($cat_terms, function($a, $b) {
        return $b->parent - $a->parent;
    });
    $primary_term = $cat_terms[0];
    $primary_term_name = $primary_term->name;

    // ดึงสีแบบ Inherit จากฟังก์ชันใน functions.php
    $term_color = get_term_acf_inherit($primary_term, 'color');
    if ($term_color) {
        $final_color = $term_color;
    }
}

// 2. ข้อมูลผู้จัดสอน (Provider)
$provider_name = '';
$provider_logo_url = 'https://dummyimage.com/100x100/ddd/aaa'; // Placeholder
$provider_terms = get_the_terms($post_id, 'course_provider');

if (!empty($provider_terms) && !is_wp_error($provider_terms)) {
    $provider = $provider_terms[0];
    $provider_name = $provider->name;
    $logo = get_field('image', $provider);
    
    if (!empty($logo)) {
        if (is_array($logo)) $provider_logo_url = $logo['url'];
        elseif (is_numeric($logo)) $provider_logo_url = wp_get_attachment_image_url($logo, 'thumbnail');
        else $provider_logo_url = $logo;
    }
}

// 3. ระยะเวลาเรียน (Duration)
$minutes = (int) get_field('total_minutes', $post_id);
if ($minutes > 0) {
    $hours = floor($minutes / 60);
    $mins  = $minutes % 60;
    $duration_text = ($hours > 0 ? $hours . ' ชม. ' : '') . ($mins > 0 ? $mins . ' นาที' : '');
} else {
    $duration_text = 'ตามรอบเรียน';
}

// 4. ระดับความยาก (Level)
$level_text = 'ไม่ระบุ';
$level_terms = get_the_terms($post_id, 'skill-level');
if (!empty($level_terms)) $level_text = $level_terms[0]->name;

// 5. กลุ่มเป้าหมาย (Audience)
$audience_text = 'ทุกวัย';
$audience_terms = get_the_terms($post_id, 'audience');
if (!empty($audience_terms)) {
    $audience_text = implode(', ', wp_list_pluck($audience_terms, 'name'));
}

// 6. ราคา (Price)
$price = get_field('price', $post_id);
if ($price !== null && $price !== '') {
    $price_text = ((float)$price == 0) ? 'ฟรี' : number_format((float)$price) . ' บาท';
} else {
    $price_text = 'ดูรอบเรียน';
}

// 7. รูป Thumbnail
$thumb = get_the_post_thumbnail_url($post_id, 'medium') ?: THEME_URI . '/assets/images/placeholder-gray.png';
?>

<a class="card-course flex flex-col h-full" 
   href="<?php the_permalink(); ?>" 
   data-modal-id="modal-course" 
   data-course-id="<?php echo esc_attr($post_id); ?>">
    
    <div class="card-content gap-10">
        <div class="min-w-0">
            <?php if ($primary_term_name) : ?>
                <div class="text-fs12" style="color:<?php echo esc_attr($final_color); ?>">
                    <?php echo esc_html($primary_term_name); ?>
                </div>
            <?php endif; ?>

            <h2 class="sm:text-fs20 text-fs16"><?php the_title(); ?></h2>

            <?php if ($provider_name) : ?>
                <div class="flex items-center gap-2 mt-1.5">
                    <img src="<?php echo esc_url($provider_logo_url); ?>" 
                         alt="<?php echo esc_attr($provider_name); ?>" 
                         class="sm:w-6 w-5 aspect-square rounded-full object-cover">
                    <h3 class="text-fs14"><?php echo esc_html($provider_name); ?></h3>
                </div>
            <?php endif; ?>
        </div>

        <div class="img shrink-0">
            <img class="h-full w-full object-cover" src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
        </div>
    </div>

    <div class="card-footer mt-auto">
        <div class="flex items-center md:gap-5 sm:gap-3 gap-1.5">
            <div class="flex items-center sm:gap-1.5 gap-1">
                <div class="icon-calendar sm:w-5 w-4"></div>
                <span class="text-fs14"><?php echo esc_html($duration_text); ?></span>
            </div>
            <!-- <div class="flex items-center sm:gap-1.5 gap-1">
                <div class="icon-chartbar sm:w-5 w-4"></div>
                <span class="text-fs14"><?php echo esc_html($level_text); ?></span>
            </div> -->
            <div class="flex items-center sm:gap-1.5 gap-1 flex-1 min-w-0">
                <div class="icon-person sm:w-5 w-4 shrink-0"></div>
                <span class="text-fs14 truncate block max-w-[200px]" title="<?php echo esc_attr($audience_text); ?>">
                    <?php echo esc_html($audience_text); ?>
                </span>
            </div>
        </div>
        <div class="text-fs16 font-semibold">
            <?php echo esc_html($price_text); ?>
        </div>
    </div>
</a>
