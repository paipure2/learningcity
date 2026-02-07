 <div data-modal-content="modal-search" class="modal items-end p-0!">
     <div class="overlay-modal"></div>
     <div class="card-modal rounded-none! max-w-full">
         <div class="absolute top-4 right-4 z-20">
             <button class="close-modal bg-black  rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
                 <div class="icon-close"></div>
             </button>
         </div>
         <div class="modal-content relative z-10 overflow-y-auto! group min-h-[600px] min-w-screen max-h-[600px]">
             <div class="sm:px-8 px-4 sm:py-16 py-24">
                 <div class="max-w-[800px] mx-auto relative mb-8">
                    <?php echo do_shortcode('[wpdreams_ajaxsearchlite]'); ?>

                 </div>

                 <?php
                /**
                 * Chips: course_category (parent only) that has at least 1 published course.
                 */

                // 1) ดึง parent terms ทั้งหมดก่อน
                $parent_terms = get_terms([
                'taxonomy'   => 'course_category',
                'parent'     => 0,
                'hide_empty' => false, // เราจะเช็คเองด้วย query
                'orderby'    => 'name',
                'order'      => 'ASC',
                ]);

                if (!is_wp_error($parent_terms) && !empty($parent_terms)) :

                // 2) กรองเฉพาะ term ที่มีคอร์สจริง
                $terms_with_courses = [];

                foreach ($parent_terms as $term) {
                    $q = new WP_Query([
                    'post_type'      => 'course',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,        // เอาแค่ 1 ก็พอเพื่อเช็คว่ามีไหม
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                    'tax_query'      => [
                        [
                        'taxonomy'         => 'course_category',
                        'field'            => 'term_id',
                        'terms'            => [$term->term_id],
                        'include_children' => true, // ถ้าคอร์สอยู่ในลูกของ parent ก็ถือว่ามีเนื้อหา
                        ]
                    ],
                    ]);

                    if ($q->have_posts()) {
                    $terms_with_courses[] = $term;
                    }
                    wp_reset_postdata();
                }

                // 3) แสดงผลเป็น chips
                if (!empty($terms_with_courses)) : ?>
                    <div class="max-w-[1140px] mx-auto">
                    <div class="flex flex-wrap gap-2 justify-center">
                    <?php foreach ($terms_with_courses as $term) : 
                        $url = get_term_link($term, 'course_category');
                        if (is_wp_error($url)) continue;
                        ?>
                        <a href="<?php echo esc_url($url); ?>"
                            class="inline-flex items-center px-3 py-1 text-sm rounded-full border border-gray-300 bg-white text-gray-800 hover:bg-gray-50">
                            <?php echo esc_html($term->name); ?>
                        </a>
                        <?php endforeach; ?>

                    </div>
                    </div>
                <?php endif; ?>

                <?php endif; ?>

                 
             </div>
         </div>
     </div>
 </div>