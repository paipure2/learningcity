<?php
$post_id = get_the_ID();

// Default: show only courses that are open now (or always open/no-session+link logic).
// Can be overridden by query_var('lc_archive_open_only') = false in AJAX/custom filter mode.
$open_only = get_query_var('lc_archive_open_only', true);
if ($open_only && function_exists('lc_course_should_show_in_archive') && !lc_course_should_show_in_archive($post_id)) {
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

$course_hover_rgb = '0, 116, 75';
$hex_color = ltrim(trim((string) $final_color), '#');
if (preg_match('/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $hex_color)) {
    if (strlen($hex_color) === 3) {
        $hex_color = $hex_color[0] . $hex_color[0] . $hex_color[1] . $hex_color[1] . $hex_color[2] . $hex_color[2];
    }

    $course_hover_rgb = implode(', ', [
        hexdec(substr($hex_color, 0, 2)),
        hexdec(substr($hex_color, 2, 2)),
        hexdec(substr($hex_color, 4, 2)),
    ]);
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
    $price_text = 'ฟรี';
}

// 7. รูป Thumbnail
$thumb = get_the_post_thumbnail_url($post_id, 'medium') ?: THEME_URI . '/assets/images/placeholder-gray.png';
?>

<a class="card-course flex flex-col h-full"
   style="--course-accent-color: <?php echo esc_attr($final_color); ?>; --course-accent-rgb: <?php echo esc_attr($course_hover_rgb); ?>;"
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
                <span class="sm:w-5 w-4 inline-flex shrink-0 text-[#979797]" aria-hidden="true">
                    <svg class="w-full h-full" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.9629 2C14.3495 2 14.6631 2.3136 14.6631 2.7002V3.21582H16.6123C17.307 3.21598 17.9746 3.74687 17.9746 4.52441V16.6865C17.9746 17.4641 17.307 17.995 16.6123 17.9951H3.3623C2.66758 17.9949 2 17.4641 2 16.6865V4.52441C2 3.74689 2.66758 3.21601 3.3623 3.21582H5.31055V2.7002C5.31055 2.3136 5.62414 2 6.01074 2C6.39725 2.00011 6.71094 2.31366 6.71094 2.7002V3.21582H13.2627V2.7002C13.2627 2.31366 13.5764 2.00011 13.9629 2ZM3.40039 8.26562V16.5947H16.5752V8.26562H3.40039ZM3.40039 6.86523H16.5752V4.61621H14.6631V5.13281C14.663 5.51936 14.3495 5.83301 13.9629 5.83301C13.5764 5.8329 13.2628 5.51929 13.2627 5.13281V4.61621H6.71094V5.13281C6.71087 5.51929 6.39721 5.8329 6.01074 5.83301C5.62418 5.83301 5.31061 5.51936 5.31055 5.13281V4.61621H3.40039V6.86523Z" fill="currentColor"/>
                    </svg>
                </span>
                <span class="text-fs14"><?php echo esc_html($duration_text); ?></span>
            </div>
            <!-- <div class="flex items-center sm:gap-1.5 gap-1">
                <div class="icon-chartbar sm:w-5 w-4"></div>
                <span class="text-fs14"><?php echo esc_html($level_text); ?></span>
            </div> -->
            <div class="flex items-center sm:gap-1.5 gap-1 flex-1 min-w-0">
                <span class="sm:w-5 w-4 shrink-0 inline-flex text-[#979797]" aria-hidden="true">
                    <svg class="w-full h-full" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.7969 5.50684C12.1776 5.5069 12.5537 5.59048 12.8994 5.75098C13.2451 5.91146 13.552 6.14546 13.8008 6.43555L17.5947 10.8564H17.5938C17.7339 11.0076 17.8468 11.1819 17.9238 11.373C18.0058 11.5766 18.0478 11.7944 18.0479 12.0137C18.0479 12.2329 18.0058 12.4508 17.9238 12.6543C17.8418 12.8579 17.7207 13.0445 17.5674 13.2021C17.4141 13.3597 17.231 13.4859 17.0283 13.5723C16.8254 13.6586 16.6064 13.7031 16.3857 13.7031C16.1652 13.7031 15.9469 13.6586 15.7441 13.5723C15.5578 13.4929 15.3895 13.3784 15.2441 13.2383L13.8359 12.0762L15.2451 17.582C15.3924 17.9655 15.3992 18.3907 15.2588 18.7783C15.1095 19.1904 14.8065 19.5313 14.4092 19.7217C14.0112 19.9124 13.5543 19.9349 13.1396 19.7832C12.7405 19.6372 12.4168 19.3423 12.2266 18.9658L10.0244 15.0654L7.82227 18.9668C7.63199 19.3427 7.30883 19.6373 6.91016 19.7832C6.49554 19.9349 6.03858 19.9122 5.64062 19.7217C5.24326 19.5313 4.93931 19.1905 4.79004 18.7783C4.64924 18.3895 4.65608 17.9625 4.80469 17.5781L6.21289 12.0752L4.79004 13.249C4.48525 13.5366 4.0836 13.7012 3.66113 13.7012C3.2148 13.701 2.79045 13.5188 2.48047 13.2002C2.17117 12.8822 2.00011 12.4548 2 12.0127C2 11.5838 2.16215 11.1693 2.4541 10.8545H2.45312L6.24902 6.43457C6.4977 6.1448 6.80497 5.91134 7.15039 5.75098C7.49592 5.59063 7.87145 5.50696 8.25195 5.50684H11.7969ZM8.25293 6.81738C8.06355 6.81742 7.87503 6.85874 7.70117 6.93945C7.52748 7.02016 7.37127 7.13875 7.24316 7.28809H7.24219L3.44727 11.708C3.43856 11.7181 3.42923 11.7277 3.41992 11.7373C3.35197 11.8071 3.31055 11.9064 3.31055 12.0127C3.31066 12.1188 3.35206 12.2174 3.41992 12.2871C3.48712 12.356 3.57414 12.3915 3.66113 12.3916C3.74829 12.3916 3.836 12.3561 3.90332 12.2871L3.95605 12.2383L6.92871 9.78711C7.14742 9.60675 7.45797 9.58726 7.69727 9.73926C7.93648 9.89127 8.05075 10.1805 7.98047 10.4551L6.06543 17.9346C6.0567 17.9686 6.04532 18.002 6.03125 18.0342C5.99043 18.1277 5.98745 18.2353 6.02246 18.332C6.0573 18.4282 6.12512 18.5012 6.20605 18.54C6.28645 18.5786 6.3774 18.5829 6.45996 18.5527C6.54301 18.5223 6.61616 18.4566 6.65918 18.3652C6.66604 18.3507 6.67372 18.3363 6.68164 18.3223L9.4541 13.4121L9.50293 13.3389C9.62563 13.1768 9.81782 13.0792 10.0244 13.0791C10.2607 13.0791 10.4786 13.2064 10.5947 13.4121L13.3682 18.3223L13.3896 18.3652C13.4327 18.4567 13.5067 18.5223 13.5898 18.5527C13.6723 18.5829 13.7625 18.5784 13.8428 18.54C13.9238 18.5012 13.9925 18.4283 14.0273 18.332C14.0623 18.2353 14.0584 18.1277 14.0176 18.0342C14.0035 18.002 13.9921 17.9686 13.9834 17.9346L12.0693 10.4551C11.999 10.1804 12.1132 9.89122 12.3525 9.73926C12.5919 9.5873 12.9024 9.60657 13.1211 9.78711L16.0918 12.2402L16.1445 12.2891C16.1779 12.3232 16.2166 12.3497 16.2578 12.3672C16.2989 12.3847 16.3424 12.3935 16.3857 12.3936C16.4293 12.3936 16.4734 12.3847 16.5146 12.3672C16.5558 12.3497 16.5945 12.3233 16.6279 12.2891C16.6614 12.2547 16.6889 12.2123 16.708 12.165C16.7271 12.1177 16.7373 12.0659 16.7373 12.0137C16.7373 11.9616 16.727 11.9105 16.708 11.8633C16.6889 11.8159 16.6614 11.7737 16.6279 11.7393C16.6185 11.7296 16.6094 11.7193 16.6006 11.709L12.8066 7.28809C12.6784 7.13856 12.5216 7.02019 12.3477 6.93945C12.2173 6.87896 12.0793 6.84065 11.9385 6.8252L11.7969 6.81738H8.25293ZM10.0234 0C11.5116 0 12.6875 1.23552 12.6875 2.7207C12.6873 4.20572 11.5115 5.44043 10.0234 5.44043C8.53553 5.44023 7.35957 4.20561 7.35938 2.7207C7.35938 1.23564 8.53541 0.000197928 10.0234 0ZM10.0234 1.31055C9.29255 1.31075 8.66992 1.92501 8.66992 2.7207C8.67011 3.51622 9.29266 4.13066 10.0234 4.13086C10.7544 4.13086 11.3777 3.51635 11.3779 2.7207C11.3779 1.92488 10.7545 1.31055 10.0234 1.31055Z" fill="currentColor"/>
                    </svg>
                </span>
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
