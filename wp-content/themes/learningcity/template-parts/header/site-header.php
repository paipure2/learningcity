<?php
$blm_pages = get_posts([
   'post_type'      => 'page',
   'posts_per_page' => 1,
   'meta_key'       => '_wp_page_template',
   'meta_value'     => 'page-blm.php',
   'post_status'    => 'publish',
   'fields'         => 'ids',
]);
$blm_url = !empty($blm_pages) ? get_permalink($blm_pages[0]) : home_url('/learning-map/');
$blog_page_id = (int) get_option('page_for_posts');
$blog_url = $blog_page_id ? get_permalink($blog_page_id) : home_url('/');
$nextlearn_url = home_url('/nextlearn/');
$learning_online_url = function_exists('lc_get_learning_mode_link')
   ? lc_get_learning_mode_link('online')
   : add_query_arg('learning_mode', 'online', get_post_type_archive_link('course'));
$learning_onsite_url = function_exists('lc_get_learning_mode_link')
   ? lc_get_learning_mode_link('onsite')
   : add_query_arg('learning_mode', 'onsite', get_post_type_archive_link('course'));

$mobile_course_categories = get_terms([
   'taxonomy'   => 'course_category',
   'hide_empty' => true,
   'parent'     => 0,
   'orderby'    => 'name',
   'order'      => 'ASC',
]);

$mobile_audiences = get_terms([
   'taxonomy'   => 'audience',
   'hide_empty' => true,
   'orderby'    => 'name',
   'order'      => 'ASC',
]);

$next_jobs_term = get_term_by('slug', 'next-jobs', 'key-theme');
$next_skills_term = get_term_by('slug', 'next-skills', 'key-theme');

$next_jobs_url = ($next_jobs_term && !is_wp_error($next_jobs_term))
   ? get_term_link($next_jobs_term)
   : home_url('/key-theme/next-jobs/');

$next_skills_url = ($next_skills_term && !is_wp_error($next_skills_term))
   ? get_term_link($next_skills_term)
   : home_url('/key-theme/next-skills/');

$next_jobs_card_image = THEME_URI . '/assets/images/next-jobs-card.svg';
$next_skills_card_image = THEME_URI . '/assets/images/next-skills-card.svg';

if (is_wp_error($next_jobs_url)) {
   $next_jobs_url = home_url('/key-theme/next-jobs/');
}

if (is_wp_error($next_skills_url)) {
   $next_skills_url = home_url('/key-theme/next-skills/');
}

if (function_exists('lc_get_visible_course_provider_terms')) {
   $mobile_course_providers = lc_get_visible_course_provider_terms();
} else {
   $mobile_course_providers = get_terms([
      'taxonomy'   => 'course_provider',
      'hide_empty' => true,
      'orderby'    => 'name',
      'order'      => 'ASC',
   ]);
}

if (is_wp_error($mobile_course_categories)) {
   $mobile_course_categories = [];
}

if (is_wp_error($mobile_audiences)) {
   $mobile_audiences = [];
}

if (is_wp_error($mobile_course_providers)) {
   $mobile_course_providers = [];
}
?>
 <header>
    <div class="container">
       <div class="header">
          <div class="flex-1">
             <a href="<?php echo site_url('/') ?>" class="logo-site" aria-label="Bangkok Learning City หน้าแรก"></a>
          </div>
          <nav class="navbar">
             <ul>
                <li>
                  <a href="<?php echo esc_url( home_url('/') ); ?>"
                     class="<?php echo esc_attr( nav_active(['home' => true]) ); ?>">
                  Home
                  </a>
               </li>
               <li class="desktop-mega-menu-item">
                  <div class="desktop-mega-menu">
                     <a href="<?php echo esc_url($nextlearn_url); ?>"
                        class="desktop-mega-menu__trigger <?php echo esc_attr( nav_active(['nextlearn' => true, 'post_type' => 'course']) ); ?>">
                     Next Learn
                     </a>
                     <div class="desktop-mega-menu__backdrop" aria-hidden="true"></div>
                     <div class="desktop-mega-menu__panel" aria-label="Next Learn mega menu">
                        <div class="desktop-mega-menu__grid">
                           <section class="desktop-mega-menu__column desktop-mega-menu__column--category desktop-mega-menu__column--scroll">
                              <h3 class="desktop-mega-menu__title">เรียนอะไร</h3>
                              <div class="desktop-mega-menu__list desktop-mega-menu__list--scroll">
                                 <?php foreach ($mobile_course_categories as $term) : ?>
                                    <?php
                                    $term_link = get_term_link($term);
                                    $icon = get_field('icon', $term);
                                    $icon_url = '';

                                    if (is_array($icon) && !empty($icon['url'])) {
                                       $icon_url = $icon['url'];
                                    } elseif (is_string($icon)) {
                                       $icon_url = $icon;
                                    } elseif (is_numeric($icon)) {
                                       $icon_url = wp_get_attachment_image_url((int) $icon, 'thumbnail');
                                    }

                                    $icon_placeholder = THEME_URI . '/assets/images/placeholder.png';
                                    $icon_src = $icon_url ? $icon_url : $icon_placeholder;
                                    ?>
                                    <a href="<?php echo esc_url($term_link); ?>" class="desktop-mega-menu__link desktop-mega-menu__link--category">
                                       <span class="desktop-mega-menu__icon">
                                          <img
                                             src="<?php echo esc_url($icon_src); ?>"
                                             alt="<?php echo esc_attr($term->name); ?>"
                                             loading="lazy"
                                          >
                                       </span>
                                       <span class="desktop-mega-menu__label"><?php echo esc_html($term->name); ?></span>
                                    </a>
                                 <?php endforeach; ?>
                              </div>
                              <div class="desktop-mega-menu__scroll-indicator" aria-hidden="true">
                                 <span class="desktop-mega-menu__scroll-indicator-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" aria-hidden="true">
                                       <path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z" fill="currentColor"/>
                                    </svg>
                                 </span>
                              </div>
                           </section>

                           <section class="desktop-mega-menu__column desktop-mega-menu__column--provider">
                              <h3 class="desktop-mega-menu__title">เรียนกับใคร</h3>
                              <div class="desktop-mega-menu__list">
                                 <?php foreach ($mobile_course_providers as $term) : ?>
                                    <?php
                                    $provider_logo = get_field('image', $term);
                                    $provider_logo_url = '';

                                    if (is_array($provider_logo) && !empty($provider_logo['url'])) {
                                       $provider_logo_url = $provider_logo['url'];
                                    } elseif (is_string($provider_logo) && !empty($provider_logo)) {
                                       $provider_logo_url = $provider_logo;
                                    } elseif (is_numeric($provider_logo)) {
                                       $provider_logo_url = wp_get_attachment_image_url((int) $provider_logo, 'thumbnail');
                                    }

                                    $provider_logo_placeholder = THEME_URI . '/assets/images/placeholder-gray.png';
                                    $provider_logo_src = $provider_logo_url ? $provider_logo_url : $provider_logo_placeholder;
                                    ?>
                                    <a href="<?php echo esc_url(get_term_link($term)); ?>" class="desktop-mega-menu__link desktop-mega-menu__link--provider">
                                       <span class="desktop-mega-menu__provider-icon">
                                          <img
                                             src="<?php echo esc_url($provider_logo_src); ?>"
                                             alt="<?php echo esc_attr($term->name); ?>"
                                             loading="lazy"
                                          >
                                       </span>
                                       <span class="desktop-mega-menu__provider-label"><?php echo esc_html($term->name); ?></span>
                                    </a>
                                 <?php endforeach; ?>
                              </div>
                              <div class="desktop-mega-menu__scroll-indicator" aria-hidden="true">
                                 <span class="desktop-mega-menu__scroll-indicator-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" aria-hidden="true">
                                       <path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z" fill="currentColor"/>
                                    </svg>
                                 </span>
                              </div>
                           </section>

                           <div class="desktop-mega-menu__stack">
                              <section class="desktop-mega-menu__column desktop-mega-menu__column--mode">
                                 <h3 class="desktop-mega-menu__title">เรียนที่ไหน</h3>
                                 <div class="desktop-mega-menu__list">
                                    <a href="<?php echo esc_url($learning_online_url); ?>" class="desktop-mega-menu__link">ออนไลน์</a>
                                    <a href="<?php echo esc_url($learning_onsite_url); ?>" class="desktop-mega-menu__link">เรียนนอกสถานที่</a>
                                 </div>
                              </section>

                              <section class="desktop-mega-menu__column desktop-mega-menu__column--audience">
                                 <h3 class="desktop-mega-menu__title">เหมาะกับใคร</h3>
                                 <div class="desktop-mega-menu__list">
                                    <?php foreach ($mobile_audiences as $term) : ?>
                                       <a href="<?php echo esc_url(get_term_link($term)); ?>" class="desktop-mega-menu__link">
                                          <?php echo esc_html($term->name); ?>
                                       </a>
                                    <?php endforeach; ?>
                                 </div>
                              </section>
                           </div>

                           <section class="desktop-mega-menu__column desktop-mega-menu__column--purpose">
                              <h3 class="desktop-mega-menu__title">เรียนเพื่ออะไร</h3>
                              <div class="desktop-mega-menu__list desktop-mega-menu__list--purpose">
                                 <a href="<?php echo esc_url($next_skills_url); ?>" class="desktop-mega-menu__link desktop-mega-menu__link--purpose-card" aria-label="Next Skills เพิ่มทักษะใหม่">
                                    <img
                                       src="<?php echo esc_url($next_skills_card_image); ?>"
                                       alt="Next Skills เพิ่มทักษะใหม่"
                                       loading="lazy"
                                    >
                                 </a>
                                 <a href="<?php echo esc_url($next_jobs_url); ?>" class="desktop-mega-menu__link desktop-mega-menu__link--purpose-card" aria-label="Next Jobs เรียนจบ พร้อมจ้าง">
                                    <img
                                       src="<?php echo esc_url($next_jobs_card_image); ?>"
                                       alt="Next Jobs เรียนจบ พร้อมจ้าง"
                                       loading="lazy"
                                    >
                                 </a>
                              </div>
                           </section>
                        </div>
                     </div>
                  </div>
               </li>
               <li>
                  <a href="<?php echo esc_url($blm_url); ?>"
                     class="<?php echo esc_attr( nav_active(['page_template' => 'page-blm.php']) ); ?>">
                  แหล่งเรียนรู้
                  </a>
               </li>
               <li>
                  <a href="<?php echo esc_url($blog_url); ?>"
                     class="<?php echo esc_attr( nav_active(['blog' => true]) ); ?>">
                  บทความ
                  </a>
               </li>
             </ul>
          </nav>
          <div class="box-right">
             <div class="flex items-center gap-2">
                <div class="xl:w-[136px] lg:w-[110px] w-32"><img src="<?php echo THEME_URI ?>//assets/images/logo-bkk-head.png" alt=""></div>
             </div>
             <div class="flex items-center gap-4">
                <button class="icon-search xl:w-7 lg:w-5 w-7" data-modal-id="modal-search" aria-label="เปิดค้นหา"></button>
                <button class="hamburger-menu" type="button" aria-label="เปิดเมนู">
                   <span></span>
                   <span></span>
                   <span></span>
                </button>
             </div>
          </div>
       </div>
    </div>
    <div class="expand-menu">
       <button class="btn-close icon-close" type="button" aria-label="ปิดเมนู"></button>
       <div class="mobile-menu-shell">
          <div class="mobile-menu-panels" data-menu-current="root">
             <section class="mobile-menu-panel is-active" data-menu-panel="root" aria-label="เมนูหลัก">
                <div class="mobile-menu-panel__head mobile-menu-panel__head--root">
                   <p class="mobile-menu-panel__eyebrow">Menu</p>
                   <p class="mobile-menu-panel__title">เลือกทางไปต่อ</p>
                </div>
                <div class="mobile-menu-panel__body">
                   <ul class="list-menu list-menu-root">
                     <li>
                     <a href="<?php echo site_url('/') ?>"
                        class="<?php echo nav_active(['home' => true]); ?>">
                        หน้าหลัก
                     </a>
                     </li>

                     <li class="list-menu-item-has-children">
                        <div class="list-menu-item-row">
                           <a href="<?php echo esc_url($nextlearn_url); ?>"
                              class="<?php echo nav_active(['post_type' => 'course']); ?>">
                              Next Learn
                           </a>
                           <button
                              class="list-menu-item-toggle"
                              type="button"
                              aria-label="เปิดเมนูย่อย Next Learn"
                              data-menu-target="nextlearn">
                              <span>+</span>
                           </button>
                        </div>
                     </li>
                      <li>
                      <a href="<?php echo esc_url($blm_url); ?>"
                         class="<?php echo nav_active(['page_template' => 'page-blm.php']); ?>">
                         แหล่งเรียนรู้
                      </a>
                      </li>
                      <li>
                      <a href="<?php echo esc_url($blog_url); ?>"
                         class="<?php echo esc_attr( nav_active(['blog' => true]) ); ?>">
                         บทความ
                      </a>
                      </li>
                   </ul>
                </div>
             </section>

             <section class="mobile-menu-panel" data-menu-panel="nextlearn" aria-label="เมนู Next Learn">
                <div class="mobile-menu-panel__head">
                   <div class="mobile-menu-panel__head-main">
                      <p class="mobile-menu-panel__eyebrow">Next Learn</p>
                      <p class="mobile-menu-panel__title">สำรวจคอร์สแบบหลายทาง</p>
                   </div>
                   <button class="mobile-menu-back" type="button" data-menu-back="root" aria-label="กลับไปเมนูหลัก">
                      <span aria-hidden="true">&larr;</span>
                      <span>ย้อนกลับ</span>
                   </button>
                </div>
                <div class="mobile-menu-panel__body">
                   <ul class="list-menu list-menu-sub">
                      <li>
                         <button class="list-menu-link-button" type="button" data-menu-target="nextlearn-category">
                            <span>เรียนอะไร</span>
                            <span aria-hidden="true">&rarr;</span>
                         </button>
                      </li>
                      <li>
                         <button class="list-menu-link-button" type="button" data-menu-target="nextlearn-provider">
                            <span>เรียนกับใคร</span>
                            <span aria-hidden="true">&rarr;</span>
                         </button>
                      </li>
                      <li>
                         <button class="list-menu-link-button" type="button" data-menu-target="nextlearn-mode">
                            <span>เรียนที่ไหน</span>
                            <span aria-hidden="true">&rarr;</span>
                         </button>
                      </li>
                      <li>
                         <button class="list-menu-link-button" type="button" data-menu-target="nextlearn-audience">
                            <span>เหมาะกับใคร</span>
                            <span aria-hidden="true">&rarr;</span>
                         </button>
                      </li>
                      <li>
                         <button class="list-menu-link-button" type="button" data-menu-target="nextlearn-purpose">
                            <span>เรียนเพื่ออะไร</span>
                            <span aria-hidden="true">&rarr;</span>
                         </button>
                      </li>
                   </ul>
                </div>
             </section>

             <section class="mobile-menu-panel" data-menu-panel="nextlearn-category" aria-label="เรียนอะไร">
                <div class="mobile-menu-panel__head">
                   <div class="mobile-menu-panel__head-main">
                      <p class="mobile-menu-panel__eyebrow">Next Learn</p>
                      <p class="mobile-menu-panel__title">เรียนอะไร</p>
                   </div>
                   <button class="mobile-menu-back" type="button" data-menu-back="nextlearn" aria-label="กลับไปเมนู Next Learn">
                      <span aria-hidden="true">&larr;</span>
                      <span>ย้อนกลับ</span>
                   </button>
                </div>
                <div class="mobile-menu-panel__body">
                   <ul class="list-menu list-menu-detail">
                      <?php foreach ($mobile_course_categories as $term) : ?>
                        <li>
                           <a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
                        </li>
                      <?php endforeach; ?>
                   </ul>
                </div>
             </section>

             <section class="mobile-menu-panel" data-menu-panel="nextlearn-provider" aria-label="เรียนกับใคร">
                <div class="mobile-menu-panel__head">
                   <div class="mobile-menu-panel__head-main">
                      <p class="mobile-menu-panel__eyebrow">Next Learn</p>
                      <p class="mobile-menu-panel__title">เรียนกับใคร</p>
                   </div>
                   <button class="mobile-menu-back" type="button" data-menu-back="nextlearn" aria-label="กลับไปเมนู Next Learn">
                      <span aria-hidden="true">&larr;</span>
                      <span>ย้อนกลับ</span>
                   </button>
                </div>
                <div class="mobile-menu-panel__body">
                   <ul class="list-menu list-menu-detail">
                      <?php foreach ($mobile_course_providers as $term) : ?>
                        <li>
                           <a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
                        </li>
                      <?php endforeach; ?>
                   </ul>
                </div>
             </section>

             <section class="mobile-menu-panel" data-menu-panel="nextlearn-mode" aria-label="เรียนที่ไหน">
                <div class="mobile-menu-panel__head">
                   <div class="mobile-menu-panel__head-main">
                      <p class="mobile-menu-panel__eyebrow">Next Learn</p>
                      <p class="mobile-menu-panel__title">เรียนที่ไหน</p>
                   </div>
                   <button class="mobile-menu-back" type="button" data-menu-back="nextlearn" aria-label="กลับไปเมนู Next Learn">
                      <span aria-hidden="true">&larr;</span>
                      <span>ย้อนกลับ</span>
                   </button>
                </div>
                <div class="mobile-menu-panel__body">
                   <ul class="list-menu list-menu-detail">
                      <li>
                         <a href="<?php echo esc_url($learning_online_url); ?>">ออนไลน์</a>
                      </li>
                      <li>
                         <a href="<?php echo esc_url($learning_onsite_url); ?>">เรียนนอกสถานที่</a>
                      </li>
                   </ul>
                </div>
             </section>

             <section class="mobile-menu-panel" data-menu-panel="nextlearn-audience" aria-label="เหมาะกับใคร">
                <div class="mobile-menu-panel__head">
                   <div class="mobile-menu-panel__head-main">
                      <p class="mobile-menu-panel__eyebrow">Next Learn</p>
                      <p class="mobile-menu-panel__title">เหมาะกับใคร</p>
                   </div>
                   <button class="mobile-menu-back" type="button" data-menu-back="nextlearn" aria-label="กลับไปเมนู Next Learn">
                      <span aria-hidden="true">&larr;</span>
                      <span>ย้อนกลับ</span>
                   </button>
                </div>
                <div class="mobile-menu-panel__body">
                   <ul class="list-menu list-menu-detail">
                      <?php foreach ($mobile_audiences as $term) : ?>
                        <li>
                           <a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
                        </li>
                      <?php endforeach; ?>
                   </ul>
                </div>
             </section>

             <section class="mobile-menu-panel" data-menu-panel="nextlearn-purpose" aria-label="เรียนเพื่ออะไร">
                <div class="mobile-menu-panel__head">
                   <div class="mobile-menu-panel__head-main">
                      <p class="mobile-menu-panel__eyebrow">Next Learn</p>
                      <p class="mobile-menu-panel__title">เรียนเพื่ออะไร</p>
                   </div>
                   <button class="mobile-menu-back" type="button" data-menu-back="nextlearn" aria-label="กลับไปเมนู Next Learn">
                      <span aria-hidden="true">&larr;</span>
                      <span>ย้อนกลับ</span>
                   </button>
                </div>
                <div class="mobile-menu-panel__body">
                   <ul class="list-menu list-menu-detail">
                      <li>
                         <a href="<?php echo esc_url($next_jobs_url); ?>">Next Skills เพิ่มทักษะใหม่</a>
                      </li>
                      <li>
                         <a href="<?php echo esc_url($next_skills_url); ?>">Next Jobs เรียนจบ พร้อมจ้าง</a>
                      </li>
                   </ul>
                </div>
             </section>
          </div>

          <div class="mobile-menu-footer">
             <a href="<?php echo site_url('/') ?>" aria-label="Bangkok Learning City หน้าแรก"><span class="logo-site"></span></a>
          </div>
       </div>
    </div>
 </header>
