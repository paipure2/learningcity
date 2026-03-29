 <aside class="aside">
     <div class="sticky top-0 bg-white mt-[-114px]">
         <a href="<?php echo site_url('/') ?>" class="logo-site p-6 block" aria-label="Bangkok Learning City หน้าแรก"></a>
         <div class="overflow-auto pr-3 h-[calc(100vh-114px)] -ml-3">
             <div class="pl-3 opacity-25:">
                 <button class="btn-search-bar " type="button" data-modal-id="modal-search" aria-label="เปิดค้นหา">
                     <span class="icon-search block w-4"></span>
                     <span>ค้นหา</span>
                 </button>
             </div>
             <div class="pl-3">
                 <h2 class="font-anuphan text-fs16! font-semibold! mb-4!">
                     <a href="<?php echo site_url('/') ?>nextlearn" class="flex items-center gap-2 mb-2 hover:text-primary-hover transition-colors">
                         <span class="block w-4"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M219.31,108.68l-80-80a16,16,0,0,0-22.62,0l-80,80A15.87,15.87,0,0,0,32,120v96a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V160h32v56a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V120A15.87,15.87,0,0,0,219.31,108.68ZM208,208H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48V120l80-80,80,80Z"></path></svg></span>
                         <span>หน้าหลัก Next Learn</span>
                     </a>
                     <a href="<?php echo esc_url( get_post_type_archive_link('course') ); ?>" class="flex items-center gap-2 hover:text-primary-hover transition-colors">
                         <span class=" block w-4"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#000000" viewBox="0 0 256 256"><path d="M216,72H131.31L104,44.69A15.86,15.86,0,0,0,92.69,40H40A16,16,0,0,0,24,56V200.62A15.4,15.4,0,0,0,39.38,216H216.89A15.13,15.13,0,0,0,232,200.89V88A16,16,0,0,0,216,72ZM40,56H92.69l16,16H40ZM216,200H40V88H216Z"></path></svg></span>
                         <span>คอร์สทั้งหมด</span>
                     </a>
                 </h2>
             </div>
             <div class="pl-3">
                 <div class="py-6 border-t border-gray-200">

                    <h2 class="text-fs12 font-bold mb-3 flex items-center gap-2">
                        หมวดหมู่
                        <span class="icon-arrow-down block w-2.5"></span>
                    </h2>

                    <div class="items -ml-2 flex flex-col gap-0">

                        <?php
                        // ดึงเฉพาะ parent category
                        $parent_terms = get_terms([
                            'taxonomy'   => 'course_category',
                            'parent'     => 0,
                            'hide_empty' => false,
                        ]);

                        if (!empty($parent_terms) && !is_wp_error($parent_terms)) :
                            foreach ($parent_terms as $term) :

                                $term_link = get_term_link($term);

                                // ===== ACF icon field =====
                                $icon = get_field('icon', $term); // image field
                                $icon_url = '';

                                if (is_array($icon) && !empty($icon['url'])) {
                                    $icon_url = $icon['url'];
                                } elseif (is_string($icon)) {
                                    $icon_url = $icon;
                                } elseif (is_numeric($icon)) {
                                    $icon_url = wp_get_attachment_image_url((int)$icon, 'thumbnail');
                                }

                                // placeholder
                                $icon_placeholder = THEME_URI . '/assets/images/placeholder.png';
                                $icon_src = $icon_url ? $icon_url : $icon_placeholder;
                        ?>

                            <a
                                href="<?php echo !is_wp_error($term_link) ? esc_url($term_link) : '#'; ?>"
                                class="flex w-full max-w-[240px] gap-2 items-center text-fs16 font-normal px-2 py-1.5 hover:bg-black/5 rounded-lg transition-colors"
                            >
                                <div class="w-5.5 aspect-square rounded-sm overflow-hidden bg-black/5 shrink-0">
                                    <img
                                        src="<?php echo esc_url($icon_src); ?>"
                                        alt="<?php echo esc_attr($term->name); ?>"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    >
                                </div>
                                <span class="inline-block truncate">
                                    <?php echo esc_html($term->name); ?>
                                </span>
                            </a>

                        <?php
                            endforeach;
                        endif;
                        ?>

                    </div>
                 </div>

                <div class="py-6 border-t border-gray-200">
                    <h2 class="text-fs12 font-bold mb-3 flex items-center gap-2">
                        รูปแบบการเรียน
                        <span class="icon-arrow-down block w-2.5"></span>
                    </h2>

                    <div class="items -ml-2 flex flex-col gap-0">
                        <a
                            href="<?php echo esc_url(function_exists('lc_get_learning_mode_link') ? lc_get_learning_mode_link('online') : add_query_arg('learning_mode', 'online', get_post_type_archive_link('course'))); ?>"
                            class="flex w-full max-w-[240px] gap-2 items-center text-fs16 font-normal px-2 py-1.5 hover:bg-black/5 rounded-lg transition-colors"
                        >
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-[#DEF6EE] shrink-0 text-primary">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 6.75C4 5.7835 4.7835 5 5.75 5H18.25C19.2165 5 20 5.7835 20 6.75V15.25C20 16.2165 19.2165 17 18.25 17H13.75L15.5 19H17C17.4142 19 17.75 19.3358 17.75 19.75C17.75 20.1642 17.4142 20.5 17 20.5H7C6.58579 20.5 6.25 20.1642 6.25 19.75C6.25 19.3358 6.58579 19 7 19H8.5L10.25 17H5.75C4.7835 17 4 16.2165 4 15.25V6.75ZM5.75 6.5C5.61193 6.5 5.5 6.61193 5.5 6.75V15.25C5.5 15.3881 5.61193 15.5 5.75 15.5H18.25C18.3881 15.5 18.5 15.3881 18.5 15.25V6.75C18.5 6.61193 18.3881 6.5 18.25 6.5H5.75Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <span class="inline-block truncate">ออนไลน์</span>
                        </a>

                        <a
                            href="<?php echo esc_url(function_exists('lc_get_learning_mode_link') ? lc_get_learning_mode_link('onsite') : add_query_arg('learning_mode', 'onsite', get_post_type_archive_link('course'))); ?>"
                            class="flex w-full max-w-[240px] gap-2 items-center text-fs16 font-normal px-2 py-1.5 hover:bg-black/5 rounded-lg transition-colors"
                        >
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-[#DEF6EE] shrink-0 text-primary">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 21C11.8011 21 11.6103 20.921 11.4697 20.7803C10.1101 19.4207 5.5 14.5968 5.5 10.25C5.5 6.52208 8.27208 3.75 12 3.75C15.7279 3.75 18.5 6.52208 18.5 10.25C18.5 14.5968 13.8899 19.4207 12.5303 20.7803C12.3897 20.921 12.1989 21 12 21ZM12 5.25C9.10051 5.25 7 7.35051 7 10.25C7 13.4295 10.1844 17.2605 12 19.1425C13.8156 17.2605 17 13.4295 17 10.25C17 7.35051 14.8995 5.25 12 5.25ZM12 13.25C10.3431 13.25 9 11.9069 9 10.25C9 8.59315 10.3431 7.25 12 7.25C13.6569 7.25 15 8.59315 15 10.25C15 11.9069 13.6569 13.25 12 13.25ZM12 8.75C11.1716 8.75 10.5 9.42157 10.5 10.25C10.5 11.0784 11.1716 11.75 12 11.75C12.8284 11.75 13.5 11.0784 13.5 10.25C13.5 9.42157 12.8284 8.75 12 8.75Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <span class="inline-block truncate">เรียนนอกสถานที่</span>
                        </a>
                    </div>
                </div>

                <div class="py-6 border-t border-gray-200">
                    <h2 class="text-fs12 font-bold mb-3 flex items-center gap-2">
                        สถานที่/หน่วยงาน
                        <span class="icon-arrow-down block w-2.5"></span>
                    </h2>

                    <div class="items -ml-2 flex flex-col gap-0">

                        <?php
                        $providers = function_exists('lc_get_visible_course_provider_terms') ? lc_get_visible_course_provider_terms() : get_terms([
                            'taxonomy'   => 'course_provider',
                            'hide_empty' => true,
                        ]);

                        if (!empty($providers) && !is_wp_error($providers)) :
                            foreach ($providers as $provider) :

                                $term_link = get_term_link($provider);
                                $icon = get_field('image', $provider);
                                $icon_url = '';

                                if (is_array($icon) && !empty($icon['url'])) {
                                    $icon_url = $icon['url'];
                                } elseif (is_string($icon)) {
                                    $icon_url = $icon;
                                } elseif (is_numeric($icon)) {
                                    $icon_url = wp_get_attachment_image_url((int) $icon, 'thumbnail');
                                }

                                $icon_placeholder = THEME_URI . '/assets/images/placeholder-gray.png';
                                $icon_src = $icon_url ? $icon_url : $icon_placeholder;
                        ?>

                            <a
                                href="<?php echo !is_wp_error($term_link) ? esc_url($term_link) : '#'; ?>"
                                class="flex w-full max-w-[240px] gap-2 items-center text-fs16 font-normal px-2 py-1.5 hover:bg-black/5 rounded-lg transition-colors"
                            >
                                <div class="w-5 h-5 rounded-full overflow-hidden bg-black/5 shrink-0">
                                    <img
                                        src="<?php echo esc_url($icon_src); ?>"
                                        alt="<?php echo esc_attr($provider->name); ?>"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    >
                                </div>
                                <span class="inline-block truncate">
                                    <?php echo esc_html($provider->name); ?>
                                </span>
                            </a>

                        <?php
                            endforeach;
                        endif;
                        ?>

                    </div>
                </div>

                <div class="py-6 border-t border-gray-200">
                    <h2 class="text-fs12 font-bold mb-3 flex items-center gap-2">
                        เหมาะสำหรับ
                        <span class="icon-arrow-down block w-2.5"></span>
                    </h2>

                    <div class="items -ml-2 flex flex-col gap-0">

                        <?php
                        $audiences = get_terms([
                            'taxonomy'   => 'audience',
                            'hide_empty' => false,
                        ]);

                        if (!empty($audiences) && !is_wp_error($audiences)) :
                            foreach ($audiences as $audience) :

                                $term_link = get_term_link($audience);
                        ?>

                            <a
                                href="<?php echo !is_wp_error($term_link) ? esc_url($term_link) : '#'; ?>"
                                class="flex w-full max-w-[240px] gap-2 items-center text-fs16 font-normal px-2 py-1.5 hover:bg-black/5 rounded-lg transition-colors"
                            >
                                <span class="inline-block truncate">
                                    <?php echo esc_html($audience->name); ?>
                                </span>
                            </a>

                        <?php
                            endforeach;
                        endif;
                        ?>

                    </div>
                </div>

             </div>
         </div>
     </div>
 </aside>
