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
?>
 <header>
    <div class="container">
       <div class="header">
          <div class="flex-1">
             <a href="<?php echo site_url('/') ?>" class="logo-site"></a>
          </div>
          <nav class="navbar">
             <ul>
                <li>
                  <a href="<?php echo esc_url( home_url('/') ); ?>"
                     class="<?php echo esc_attr( nav_active(['home' => true]) ); ?>">
                  Home
                  </a>
               </li>
               <li>
                  <a href="<?php echo esc_url( home_url('/nextlearn/') ); ?>"
                     class="<?php echo esc_attr( nav_active(['nextlearn' => true, 'post_type' => 'course']) ); ?>">
                  Next Learn
                  </a>
               </li>
                <li>
                  <a href="<?php echo esc_url($blm_url); ?>"
                     class="<?php echo esc_attr( nav_active(['page_template' => 'page-blm.php']) ); ?>">
                  แหล่งเรียนรู้
                  </a>
               </li>
                <li><a href="#!" disabled>การเรียนรู้นอกระบบ<span>เร็วๆนี้</span></a></li>
                <li><a href="#!" disabled>เกี่ยวกับโครงการ<span>เร็วๆนี้</span></a></li>
             </ul>
          </nav>
          <div class="box-right">
             <div class="flex items-center gap-2">
                <div class="xl:w-[136px] lg:w-[110px] w-32"><img src="<?php echo THEME_URI ?>//assets/images/logo-bkk-head.png" alt=""></div>
             </div>
             <div class="flex items-center gap-4">
                <!-- <button class="icon-search xl:w-7 lg:w-5 w-7" data-modal-id="modal-search"></button> -->
                <button class="hamburger-menu">
                   <span></span>
                   <span></span>
                   <span></span>
                </button>
             </div>
          </div>
       </div>
    </div>
    <div class="expand-menu">
       <button class="btn-close icon-close"></button>
       <div class="flex flex-col px-6 pt-24 pb-12 h-full justify-between overflow-auto">
          <ul class="list-menu">
            <li>
            <a href="<?php echo site_url('/') ?>"
               class="<?php echo nav_active(['home' => true]); ?>">
               หน้าหลัก
            </a>
            </li>

            <li>
            <a href="<?php echo esc_url( home_url('/nextlearn/') ); ?>"
               class="<?php echo nav_active(['post_type' => 'course']); ?>">
               Next Learn
            </a>
            </li>
             <li>
             <a href="<?php echo esc_url($blm_url); ?>"
                class="<?php echo nav_active(['page_template' => 'page-blm.php']); ?>">
                แหล่งเรียนรู้
             </a>
             </li>
             <li><a href="#!" disabled>การเรียนรู้นอกระบบ<span>เร็วๆนี้</span></a></li>
             <li><a href="#!" disabled>เกี่ยวกับโครงการ<span>เร็วๆนี้</span></a></li>
          </ul>
          <a href="<?php echo site_url('/') ?>"><span class="logo-site"></span></a>
       </div>
    </div>
 </header>
