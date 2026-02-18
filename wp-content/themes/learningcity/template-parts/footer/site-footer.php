<?php
$front_page_id = (int) get_option('page_on_front');
$blm_pages = get_posts([
    'post_type'      => 'page',
    'posts_per_page' => 1,
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-blm.php',
    'post_status'    => 'publish',
    'fields'         => 'ids',
]);
$blm_url = !empty($blm_pages) ? get_permalink($blm_pages[0]) : home_url('/learning-map/');
$partners_title = function_exists('get_field') ? get_field('partners_title', $front_page_id) : '';
$partners_title = trim((string) $partners_title);
if ($partners_title === '') {
    $partners_title = 'เพื่อนร่วมพัฒนา';
}

$partners_gallery = function_exists('get_field') ? get_field('partners_gallery', $front_page_id) : array();
if (!is_array($partners_gallery) || empty($partners_gallery)) {
    $partners_gallery = array();
    for ($i = 1; $i <= 24; $i++) {
        $partners_gallery[] = array(
            'url' => THEME_URI . '/assets/images/logo/partner' . $i . '.png',
            'alt' => '',
        );
    }
}
?>
 <div class="border-t border-gray-200">
     <div class="w-full max-w-[1150px] mx-auto sm:px-6 px-4">
         <footer class="pb-12 lg:pt-20 pt-12" data-aos="fade-in">
             <div class="logo-site lg:w-[155px] w-[130px]"></div>

             <div class="flex lg:gap-4 gap-12 lg:flex-row flex-col mt-12">
                 <div class="flex-1 flex lg:flex-row flex-col lg:gap-8 gap-10">
                     <div>
                         <ul class="menu-footer">
                             <li><a href="nextlearn.html" class="active">Next Learn</a></li>
                             <li><a href="<?php echo esc_url($blm_url); ?>">แหล่งเรียนรู้</a></li>
                             <li><a href="#!" disabled>บทความ<span>เร็วๆนี้</span></a></li>
                         </ul>
                     </div>

                     <div class="lg:mx-auto">
                         <h2 class="lg:text-fs20 text-fs18 font-semibold">กรุงเทพมหานคร</h2>
                         <p class="mt-2 lg:text-fs18 text-fs16">173 ถนนดินสอ แขวงเสาชิงช้า เขตพระนคร
                             กรุงเทพฯ 10200
                         </p>
                     </div>
                 </div>

                 <div class="lg:ml-auto">
                     <h2 class="lg:text-fs20 text-fs18 font-semibold">ติดตามเรา</h2>
                     <div class="flex gap-4 items-center mt-3">
                         <a href="https://web.facebook.com/bangkokbma" target="_blank" class="w-8 aspect-square icon-social-facebook"></a>
                         <a href="https://www.youtube.com/@bangkok_bma"  target="_blank" class="w-8 aspect-square icon-social-youtube"></a>
                         <a href="https://www.instagram.com/bangkok_bma/" target="_blank"  class="w-8 aspect-square icon-social-instagram"></a>
                         <a href="https://x.com/bangkokbma"  target="_blank" class="w-8 aspect-square icon-social-x"></a>
                     </div>
                 </div>
             </div>

             <div class="lg:flex items-start gap-4 lg:mt-16 mt-12 overflow-hidden">
                 <div class="min-w-[300px]">
                     <h3 class="text-fs16 font-semibold font-anuphan">Organized by</h3>
                     <div class="flex items-center lg:gap-3 gap-1.5 mt-4">
                         <a href="#!"
                             class="shadow-logo overflow-hidden rounded-xl block lg:w-[90px]! w-[74px]! lg:h-[68px]!  px-1h-[50px]!">
                             <img src="<?php echo THEME_URI ?>/assets/images/logo/logo01.jpg" alt="" class="w-full h-full object-cover
                               ">
                         </a>
                         </a>
                         <a href="#!"
                             class="shadow-logo overflow-hidden rounded-xl block lg:w-[90px]! w-[74px]! lg:h-[68px]!  px-1h-[50px]!">
                             <img src="<?php echo THEME_URI ?>/assets/images/logo/logo02.jpg" alt="" class="w-full h-full object-cover
                               ">
                         </a>
                     </div>
                 </div>

                 <div class="lg:mt-0 mt-12 min-w-px">
                     <h3 class="text-fs16 font-semibold font-anuphan">เพื่อนร่วมพัฒนา</h3>
                     <div class="swiper swiper-logo-loop py-4!">
                         <div class="swiper-wrapper">
                             <?php foreach ($partners_gallery as $partner_item) :
                                 if (!is_array($partner_item)) {
                                     continue;
                                 }
                                 $partner_img = isset($partner_item['url']) ? (string) $partner_item['url'] : '';
                                 if ($partner_img === '') {
                                     continue;
                                 }
                                 $partner_alt = isset($partner_item['alt']) ? (string) $partner_item['alt'] : '';
                             ?>
                                 <div class="swiper-slide w-auto!">
                                     <a href="#!"
                                         class="shadow-logo overflow-hidden rounded-xl block lg:w-[90px]! w-[74px]! lg:h-[68px]! px-1h-[50px]!">
                                         <img src="<?php echo esc_url($partner_img); ?>" alt="<?php echo esc_attr($partner_alt); ?>" class="w-full h-full object-cover">
                                     </a>
                                 </div>
                             <?php endforeach; ?>
                         </div>
                     </div>
                 </div>
             </div>

             <div class="w-full text-fs14 flex gap-4 items-center justify-start flex-wrap mt-3">
                 <div>&copy; กรุงเทพมหานคร <span id="current-year"></span></div>
                 <a href="https://www.bangkok.go.th/privacy" target="_blank" class="link-underline">นโยบายความเป็นส่วนตัว</a>
                 <div>ภาพประกอบโดย Sunhouse</div>
                 <button
                   type="button"
                   data-lc-auth-trigger="1"
                   data-lc-auth-mode="toggle"
                   class="link-underline text-[#0B8664] font-semibold"
                 >
                   เข้าสู่ระบบผู้แก้ไข
                 </button>
             </div>
         </footer>
     </div>
 </div>

<?php get_template_part('template-parts/auth/location-edit-auth-modal'); ?>
