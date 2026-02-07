 <aside class="aside">
     <div class="sticky top-0 bg-white mt-[-114px]">
         <a href="<?php echo site_url('/') ?>" class="logo-site p-6 block"></a>
         <div class="overflow-auto pr-3 h-[calc(100vh-114px)] -ml-3">
             <div class="pl-3 opacity-25:">
                 <button class="btn-search-bar "  data-modal-id="modal-search">
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
                        สถานที่/หน่วยงาน
                        <span class="icon-arrow-down block w-2.5"></span>
                    </h2>

                    <div class="items -ml-2 flex flex-col gap-0">

                        <?php
                        $providers = get_terms([
                            'taxonomy'   => 'course_provider',
                            'hide_empty' => false,
                        ]);

                        if (!empty($providers) && !is_wp_error($providers)) :
                            foreach ($providers as $provider) :

                                $term_link = get_term_link($provider);
                        ?>

                            <a
                                href="<?php echo !is_wp_error($term_link) ? esc_url($term_link) : '#'; ?>"
                                class="flex w-full max-w-[240px] gap-2 items-center text-fs16 font-normal px-2 py-1.5 hover:bg-black/5 rounded-lg transition-colors"
                            >
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