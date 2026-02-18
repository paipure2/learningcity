<?php get_header(); ?>
<div class="app-layout overflow-visible!">
    <?php get_template_part('template-parts/header/site-header'); ?>
    <main id="primary" class="flex-1 flex items-center justify-center h-full">


      <div class="w-full">

        <button id="searchbar-floating"
                class="fixed md:max-w-[425px] max-w-[320px] w-[75%] md:bottom-6 bottom-4 left-1/2 -translate-x-1/2 z-9 flex items-center justify-start backdrop-blur-md md:p-2.5 p-2 bg-white/75 rounded-full shadow-md transition-all hover:scale-[1.02]"
                data-modal-id="modal-search">
          <div class="bg-primary md:w-10 w-8 rounded-full md:p-2.5 p-2">
            <div class="icon-search white"></div>
          </div>
          <span
                id="searchbar-floating-text"
                class="text-black md:text-fs26 text-fs18 font-semibold block md:px-4 px-2.5 leading-normal">คุณอยากเรียนอะไร</span>
        </button>
        <script>
          (function () {
            var textEl = document.getElementById("searchbar-floating-text");
            if (!textEl) return;

            var messages = [
              "คุณอยากเรียนอะไร",
              "แหล่งเรียนรู้ในกทม.",
              "หาคลาสที่ใช่สำหรับคุณ",
              "อยากพัฒนาทักษะด้านไหน"
            ];

            if (messages.length < 2) return;

            var index = 0;
            var prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

            textEl.style.display = "block";
            textEl.style.willChange = "opacity, transform";
            textEl.style.transition = prefersReducedMotion
              ? "opacity 0.12s linear"
              : "opacity 0.18s ease, transform 0.18s ease";

            window.setInterval(function () {
              index = (index + 1) % messages.length;
              var nextText = messages[index];

              if (prefersReducedMotion) {
                textEl.style.opacity = "0";
                window.setTimeout(function () {
                  textEl.textContent = nextText;
                  textEl.style.opacity = "1";
                }, 80);
                return;
              }

              textEl.style.opacity = "0";
              textEl.style.transform = "translateY(4px)";
              window.setTimeout(function () {
                textEl.textContent = nextText;
                textEl.style.transform = "translateY(0)";
                textEl.style.opacity = "1";
              }, 180);
            }, 2800);
          })();
        </script>

        <?php
        $media_url = static function ($field_value) {
          if (is_array($field_value) && !empty($field_value['url'])) {
            return (string) $field_value['url'];
          }
          if (is_string($field_value)) {
            return $field_value;
          }
          return '';
        };

        $hero_type = function_exists('get_field') ? trim((string) get_field('hero_type')) : '';
        if ($hero_type !== 'image') {
          $hero_type = 'video';
        }

        $hero_heading = function_exists('get_field') ? trim((string) get_field('hero_heading')) : '';
        if ($hero_heading === '') {
          $hero_heading = 'เรียนรู้เพื่อต่อยอด';
        }

        $hero_cta_text = function_exists('get_field') ? trim((string) get_field('hero_cta_text')) : '';
        if ($hero_cta_text === '') {
          $hero_cta_text = 'ดูคอร์สทั้งหมด';
        }

        $hero_cta_url = function_exists('get_field') ? trim((string) get_field('hero_cta_url')) : '';
        if ($hero_cta_url === '') {
          $hero_cta_url = site_url('/') . 'nextlearn';
        }

        $hero_video_mobile = function_exists('get_field') ? $media_url(get_field('hero_video_mobile')) : '';
        if ($hero_video_mobile === '') {
          $hero_video_mobile = THEME_URI . '/assets/video/hero-mobile.mp4';
        }

        $hero_video_desktop = function_exists('get_field') ? $media_url(get_field('hero_video_desktop')) : '';
        if ($hero_video_desktop === '') {
          $hero_video_desktop = THEME_URI . '/assets/video/hero-desktop.mp4';
        }

        $hero_poster = function_exists('get_field') ? $media_url(get_field('hero_poster')) : '';
        if ($hero_poster === '') {
          $hero_poster = THEME_URI . '/assets/video/banner-placeholder.jpg';
        }

        $hero_image_desktop = function_exists('get_field') ? $media_url(get_field('hero_image_desktop')) : '';
        if ($hero_image_desktop === '') {
          $hero_image_desktop = $hero_poster;
        }
        $hero_image_mobile = function_exists('get_field') ? $media_url(get_field('hero_image_mobile')) : '';
        if ($hero_image_mobile === '') {
          $hero_image_mobile = $hero_image_desktop;
        }

        $intro_title = function_exists('get_field') ? trim((string) get_field('intro_title')) : '';
        if ($intro_title === '') {
          $intro_title = 'Bangkok Learning City';
        }

        $intro_body = function_exists('get_field') ? (string) get_field('intro_body') : '';
        if (trim($intro_body) === '') {
          $intro_body = "กรุงเทพ เมืองที่เปิดโอกาสให้ทุกคนเรียนรู้ได้ทุกที่ทุกเวลา<br>ค้นหากิจกรรม แหล่งเรียนรู้ และโครงการหลากหลาย<br>จากทั่วกรุงเทพฯ เพื่อพัฒนาทักษะ เติมแรงบันดาลใจ<br>และสร้างสังคมแห่งการเรียนรู้ร่วมกัน";
        }

        $policy_col_configs = array(
          array(
            'key' => 'col_1',
            'default_title' => "สนับสนุนการ\nเรียนรู้ตลอดชีวิต",
            'item_bg' => '#D8FFF1',
          ),
          array(
            'key' => 'col_2',
            'default_title' => "พัฒนาคุณภาพ\nการศึกษา",
            'item_bg' => '#DBEEFF',
          ),
          array(
            'key' => 'col_3',
            'default_title' => "โรงเรียน\nเป็นพื้นที่ปลอดภัย",
            'item_bg' => '#FFD7F0',
          ),
          array(
            'key' => 'col_4',
            'default_title' => "ยกระดับ\nการดูแลเด็กเล็ก",
            'item_bg' => '#FFECD1',
          ),
        );

        $policy_cols_source = function_exists('get_field') ? get_field('policy_cols') : array();
        if (!is_array($policy_cols_source)) {
          $policy_cols_source = array();
        }

        $policy_items_by_col = array();
        foreach ($policy_col_configs as $policy_cfg) {
          $policy_col_key = $policy_cfg['key'];
          $policy_col_data = isset($policy_cols_source[$policy_col_key]) && is_array($policy_cols_source[$policy_col_key]) ? $policy_cols_source[$policy_col_key] : array();
          $policy_col_title = !empty($policy_col_data['title']) ? (string) $policy_col_data['title'] : (string) $policy_cfg['default_title'];
          $policy_col_items = !empty($policy_col_data['items']) && is_array($policy_col_data['items']) ? $policy_col_data['items'] : array();
          $policy_items_by_col[$policy_col_key] = array(
            'title' => $policy_col_title,
            'item_bg' => $policy_cfg['item_bg'],
            'items' => $policy_col_items,
          );
        }

        $lifelong_section_title = function_exists('get_field') ? trim((string) get_field('lifelong_section_title')) : '';
        if ($lifelong_section_title === '') {
          $lifelong_section_title = 'เมืองที่เรียนรู้ได้ทุกช่วงวัย';
        }

        $lifelong_items = function_exists('get_field') ? get_field('lifelong_items') : array();
        if (!is_array($lifelong_items) || empty($lifelong_items)) {
          $lifelong_items = array(
            array(
              'item_key' => 'baby',
              'title' => 'เด็กอ่อน',
              'teaser' => "เมืองที่สร้างรากฐาน<br>การดูแลเด็ก\nตั้งแต่แรกเกิด",
              'image_desktop' => array('url' => THEME_URI . '/assets/images/expand/baby.jpg'),
              'show_detail_button' => 0,
              'detail_button_text' => 'อ่านรายละเอียด',
              'policy_keys' => array(),
            ),
            array(
              'item_key' => 'young',
              'title' => 'เด็กเล็ก',
              'teaser' => "เรียนรู้ผ่านการเล่น <br>เสริมพัฒนาการรอบด้าน",
              'image_desktop' => array('url' => THEME_URI . '/assets/images/expand/youndkid.jpg'),
              'show_detail_button' => 0,
              'detail_button_text' => 'อ่านรายละเอียด',
              'policy_keys' => array(),
            ),
            array(
              'item_key' => 'student',
              'title' => 'วัยเรียน',
              'teaser' => "เปิดโลกการเรียนรู้ <br>พัฒนาทักษะอนาคต",
              'image_desktop' => array('url' => THEME_URI . '/assets/images/expand/kid.jpg'),
              'show_detail_button' => 0,
              'detail_button_text' => 'อ่านรายละเอียด',
              'policy_keys' => array(),
            ),
            array(
              'item_key' => 'working',
              'title' => 'วัยทำงาน',
              'teaser' => "เพิ่มทักษะใหม่ พร้อมปรับตัว<br>ทุกการเปลี่ยนแปลง",
              'image_desktop' => array('url' => THEME_URI . '/assets/images/expand/adult.jpg'),
              'show_detail_button' => 0,
              'detail_button_text' => 'อ่านรายละเอียด',
              'policy_keys' => array(),
            ),
            array(
              'item_key' => 'senior',
              'title' => 'สูงอายุ',
              'teaser' => "เรียนรู้อย่างมีคุณค่า <br>ใช้ชีวิตอย่างมีความหมาย",
              'image_desktop' => array('url' => THEME_URI . '/assets/images/expand/old.jpg'),
              'show_detail_button' => 0,
              'detail_button_text' => 'อ่านรายละเอียด',
              'policy_keys' => array(),
            ),
          );
        }

        $lifelong_generated_modals = array();

        $banner_card_1 = function_exists('get_field') ? get_field('banner_card_1') : array();
        $banner_card_2 = function_exists('get_field') ? get_field('banner_card_2') : array();
        $home_banner_cards = array(
          is_array($banner_card_1) ? $banner_card_1 : array(),
          is_array($banner_card_2) ? $banner_card_2 : array(),
        );

        $banner_card_1_has_data = !empty($home_banner_cards[0]['image']) || !empty($home_banner_cards[0]['seo_title']) || !empty($home_banner_cards[0]['popup_title']);
        $banner_card_2_has_data = !empty($home_banner_cards[1]['image']) || !empty($home_banner_cards[1]['seo_title']) || !empty($home_banner_cards[1]['popup_title']);
        if (!$banner_card_1_has_data && !$banner_card_2_has_data) {
          $home_banner_cards = array(
            array(
              'image' => array('url' => THEME_URI . '/assets/images/homepage/unesco.png', 'alt' => 'Bangkok Learning City Learning for Life Opportunities for All กรุงเทพฯ เมืองแห่งโอกาส เพื่อการเรียนรู้สำหรับทุกคน'),
              'seo_title' => 'Bangkok Learning City Learning for Life Opportunities for All กรุงเทพฯ เมืองแห่งโอกาส เพื่อการเรียนรู้สำหรับทุกคน',
              'action_type' => 'popup',
              'popup_title' => 'Bangkok Learning City: Learning for Life Opportunities for All',
              'popup_body' => "กรุงเทพมหานครขับเคลื่อน และส่งเสริมการเรียนรู้ตลอดชีวิต สำหรับคนทุกช่วงวัย\nด้วยการขับเคลื่อนนโยบายเมืองแห่งการเรียนรู้ 4 ด้าน",
              'popup_image' => array('url' => THEME_URI . '/assets/images/homepage/unesco-logo.png', 'width' => 640, 'height' => 320),
              'popup_image_display_mode' => 'actual',
              'popup_button_text' => 'เรียนรู้เพิ่มเติม',
              'popup_button_url' => 'https://www.uil.unesco.org/en/learning-cities/bangkok',
              'popup_button_2_text' => 'Learning City คืออะไร ?',
              'popup_button_2_url' => 'https://www.eef.or.th/infographic-learning-city/#:~:text=Learning%20City%20%E0%B8%AB%E0%B8%A1%E0%B8%B2%E0%B8%A2%E0%B8%96%E0%B8%B6%E0%B8%87%20%E0%B9%80%E0%B8%A1%E0%B8%B7%E0%B8%AD%E0%B8%87,%E0%B9%80%E0%B8%A3%E0%B8%B5%E0%B8%A2%E0%B8%99%E0%B8%A3%E0%B8%B9%E0%B9%89%E0%B8%95%E0%B8%A5%E0%B8%AD%E0%B8%94%E0%B8%8A%E0%B9%88%E0%B8%A7%E0%B8%87%E0%B8%8A%E0%B8%B5%E0%B8%A7%E0%B8%B4%E0%B8%95',
            ),
            array(
              'image' => array('url' => THEME_URI . '/assets/images/homepage/bkk-active.png', 'alt' => 'BKK Active จองสนามกีฬา สระว่ายน้ำ ฟิตเนส และกิจกรรมนันทนาการ'),
              'seo_title' => 'BKK Active จองสนามกีฬา สระว่ายน้ำ ฟิตเนส และกิจกรรมนันทนาการ',
              'action_type' => 'popup',
              'popup_title' => 'BKK Active',
              'popup_body' => 'BKK Active คือแอปพลิเคชันที่รวมบริการด้านกีฬา สุขภาพ และการเรียนรู้ของกรุงเทพมหานครไว้ในที่เดียว',
              'popup_image' => array('url' => THEME_URI . '/assets/images/homepage/bkk-active-app.png', 'width' => 700, 'height' => 360),
              'popup_image_display_mode' => 'actual',
              'popup_button_text' => 'ดาวน์โหลดสำหรับ iOS',
              'popup_button_url' => 'https://apps.apple.com/th/app/bkk-active/id1642670243?l=th',
              'popup_button_2_text' => 'ดาวน์โหลดสำหรับ Android',
              'popup_button_2_url' => 'https://play.google.com/store/apps/details?id=go.th.bangkok.cstd',
            ),
          );
        }

        $home_banner_layout = array(
          array(
            'wrap' => 'overflow-hidden sm:rounded-3xl rounded-2xl lg:col-span-6 sm:col-span-2 col-span-1 h-full',
            'maxw' => 'max-w-[760px]',
            'bg' => '#C0E6FF',
            'img_width' => '1200',
            'img_height' => '240',
            'fallback_image' => THEME_URI . '/assets/images/homepage/unesco.png',
          ),
          array(
            'wrap' => 'overflow-hidden sm:rounded-3xl rounded-2xl lg:col-span-3 sm:col-span-1 col-span-1 h-full',
            'maxw' => 'max-w-[300px]',
            'bg' => '#00744B',
            'img_width' => '900',
            'img_height' => '240',
            'fallback_image' => THEME_URI . '/assets/images/homepage/bkk-active.png',
          ),
        );

        $home_banner_modals = array();
        ?>

        <section class="xl:pb-20 sm:pb-16 pb-10 overflow-hidden"
                 data-aos=" fade-in">
          <div class="container">
            <div class="grid lg:grid-cols-12 sm:grid-cols-3 grid-cols-1 xl:gap-6 gap-4">
              <div class="overflow-hidden sm:rounded-3xl rounded-2xl lg:col-span-9 sm:col-span-3 col-span-1 h-full">
                <div class="sm:aspect-video aspect-[1/1.12]">
                  <div class="relative h-full">
                   

                    <div class="absolute inset-0 w-full h-full">
                      <?php if ($hero_type === 'image') : ?>
                        <picture class="block w-full h-full">
                          <source media="(max-width: 639px)" srcset="<?php echo esc_url($hero_image_mobile); ?>">
                          <img
                            src="<?php echo esc_url($hero_image_desktop); ?>"
                            alt="<?php echo esc_attr($hero_heading); ?>"
                            class="w-full h-full object-cover"
                            loading="eager"
                            decoding="async"
                            fetchpriority="high"
                          >
                        </picture>
                      <?php else : ?>
                        <video
                          id="heroVideo"
                          class="w-full h-full object-cover"
                          autoplay
                          muted
                          loop
                          playsinline
                          preload="metadata"
                          poster="<?php echo esc_url($hero_poster); ?>"
                        ></video>

                        <script>
                          (function () {
                            const v = document.getElementById('heroVideo');
                            if (!v) return;
                            const mobileSrc = "<?php echo esc_js($hero_video_mobile); ?>";
                            const desktopSrc = "<?php echo esc_js($hero_video_desktop); ?>";

                            const isMobile = window.matchMedia("(max-width: 639px)").matches;
                            v.src = isMobile ? mobileSrc : desktopSrc;

                            // บางเครื่อง iOS ต้องสั่ง load/play ใหม่หลัง set src
                            v.load();
                            const p = v.play();
                            if (p && p.catch) p.catch(() => {});
                          })();
                        </script>
                      <?php endif; ?>
                    </div>



                    <div
                         class="relative flex flex-col sm:justify-center justify-start items-start h-full sm:pl-10 pl-6 max-sm:pt-10">
                      <div class="logo-nextlearn 2xl:w-[307px] lg:w-[20vw] sm:w-[25.5vw] w-[40.5vw] sm:mb-[3%] mb-[4%]">
                      </div>
                      <h2
                          class="font-anuphan 2xl:text-fs36 lg:text-[2.34vw] sm:text-[3.25vw] text-[4.5vw] font-semibold text-white leading-snug">
                        <?php echo esc_html($hero_heading); ?>
                      </h2>
                      <a href="<?php echo esc_url($hero_cta_url); ?>"
                         class="2xl:text-fs24 lg:text-[1.57vw] sm:text-[2vw] text-[3.5vw] bg-white font-semibold rounded-full sm:px-[2.5%] px-[4%] sm:py-[0.5%] py-[1.25%] inline-block sm:mt-[3%] mt-[4%]"><?php echo esc_html($hero_cta_text); ?></a>
                    </div>

                    <!-- <div
                         class="absolute shadow-lg 2xl:rounded-2xl lg:rounded-[1vw] sm:rounded-[1.5vw] rounded-[2.5vw] bg-white sm:p-[1.75%] p-[3.15%] sm:top-[50%] top-[70%] sm:right-[8%] right-[55%]">
                      <div
                           class="2xl:w-9 lg:w-[2.35vw] sm:w-[3.15vw] w-[5.35vw] aspect-square rounded-[20%] overflow-hidden">
                        <img src="https://dummyimage.com/100x100/EA3DA9/EA3DA9"
                             alt="">
                      </div>
                      <div
                           class="2xl:text-fs20 lg:text-[1.3vw] sm:text-[1.85vw] text-[2.9vw] lg:mt-[6%] mt-[8%] font-medium leading-none">
                        คอร์สทำอาหาร</div>
                    </div> -->
                  </div>
                </div>
              </div>
              <div
                   class="max-sm:overflow-hidden lg:col-span-3 sm:col-span-3 col-span-1 h-full lg:row-span-2 min-h-[300px]">
                <div
                     class="box-chart sm:rounded-3xl rounded-2xl sm:p-3 p-6 flex lg:flex-col sm:flex-row flex-col justify-between">
                  <div class="lg:mb-[-30%] max-sm:mb-[-25%] self-center">
                    <h2
                        class="text-center text-shadow-md lg:pt-3 text-white leading-snug font-bold 2xl:text-fs42 lg:text-[2.93vw] sm:text-[3.91vw] text-[7.25vw]">
                      เรียนรู้<br class="sm:block hidden">ล้านชั่วโมง</h2>
                  </div>

                  <div
                       class="aspect-square relative mx-[-20%] overflow-visible max-lg:scale-[130%] max-md:scale-[100%] max-lg:left-[-3%] max-sm:left-0">
                    <div
                         class="absolute w-[55%] h-[55%] top-[22.5%] left-[22.5%] border-4 border-white rounded-full bg-[#007D51] pointer-events-none">
                      <div id="analog-clock"
                           class="relative w-full h-full z-2">
                        <div class="hand hour-hand"></div>
                        <div class="hand minute-hand"></div>
                        <div class="hand second-hand"></div>
                        <div class="center-dot"></div>
                      </div>
                    </div>
                    <div id="chart"
                         class="h-full w-full"></div>
                  </div>

                  <div
                    class="lg:mt-[-20%] max-sm:mt-[-25%] bg-[#005A3A] flex flex-col rounded-20 sm:p-4 p-6 text-center 2xl:text-fs32 lg:text-[2.35vw] sm:text-[3.13vw] lg:w-auto sm:w-[30vw] w-auto text-[7.73vw] 2xl:gap-4 gap-[1vw]">

                    <!-- TOTAL -->
                    <div class="py-2">
                      <h2 class="text-white leading-tight font-medium">เรียนไปแล้ว</h2>
                      <h2 class="text-white leading-tight font-bold">
                        <span id="total-hours" class="counter-hours">0</span> ชั่วโมง
                      </h2>
                    </div>

                    <div class="flex-1 max-sm:p-2 flex flex-col justify-center gap-3">

                      <!-- JOB -->
                      <div class="flex items-start gap-2">
                        <div
                          class="2xl:w-11 lg:w-[3vw] sm:w-[4.2vw] w-[10.5vw] aspect-square flex items-center justify-center 2xl:rounded-lg lg:rounded-[0.6vw] sm:rounded-[0.8vw] rounded-[2vw] sm:p-[3.5%] p-[3%]"
                          style="background:#F7DD52">
                          <img src="<?php echo THEME_URI ?>/assets/images/icons/icon_occupation.svg" alt="">
                        </div>

                        <div class="flex-1 flex flex-col justify-between 2xl:gap-1 lg:gap-[0.5vw] sm:gap-[1vw] gap-[1.5vw]">
                          <div class="bg-white/20 2xl:h-4 lg:h-[1vw] sm:h-[1.55vw] h-[3.65vw] rounded-full w-full overflow-hidden">
                            <span
                              class="progress block h-full rounded-full"
                              data-theme="job"
                              style="background:#F7DD52; width:0%"></span>
                          </div>

                          <div class="flex items-center justify-between text-white font-medium 2xl:text-fs14 lg:text-[1vw] sm:text-[1.57vw] text-[3.4vw]">
                            <div>อาชีพ</div>
                            <div><span class="counter-hours" data-theme="job">0</span> ชั่วโมง</div>
                          </div>
                        </div>
                      </div>

                      <!-- DIGITAL -->
                      <div class="flex items-start gap-2">
                        <div
                          class="2xl:w-11 lg:w-[3vw] sm:w-[4.2vw] w-[10.5vw] aspect-square flex items-center justify-center 2xl:rounded-lg lg:rounded-[0.6vw] sm:rounded-[0.8vw] rounded-[2vw] sm:p-[3.5%] p-[3%]"
                          style="background:#EA3DA9">
                          <img src="<?php echo THEME_URI ?>/assets/images/icons/icon_it.svg" alt="">
                        </div>

                        <div class="flex-1 flex flex-col justify-between 2xl:gap-1 lg:gap-[0.5vw] sm:gap-[1vw] gap-[1.5vw]">
                          <div class="bg-white/20 2xl:h-4 lg:h-[1vw] sm:h-[1.55vw] h-[3.65vw] rounded-full w-full overflow-hidden">
                            <span
                              class="progress block h-full rounded-full"
                              data-theme="digital"
                              style="background:#EA3DA9; width:0%"></span>
                          </div>

                          <div class="flex items-center justify-between text-white font-medium 2xl:text-fs14 lg:text-[1vw] sm:text-[1.57vw] text-[3.4vw]">
                            <div>ไอที</div>
                            <div><span class="counter-hours" data-theme="digital">0</span> ชั่วโมง</div>
                          </div>
                        </div>
                      </div>

                      <!-- LANGUAGE -->
                      <div class="flex items-start gap-2">
                        <div
                          class="2xl:w-11 lg:w-[3vw] sm:w-[4.2vw] w-[10.5vw] aspect-square flex items-center justify-center 2xl:rounded-lg lg:rounded-[0.6vw] sm:rounded-[0.8vw] rounded-[2vw] sm:p-[3.5%] p-[3%]"
                          style="background:#0972CE">
                          <img src="<?php echo THEME_URI ?>/assets/images/icons/icon_translate.svg" alt="">
                        </div>

                        <div class="flex-1 flex flex-col justify-between 2xl:gap-1 lg:gap-[0.5vw] sm:gap-[1vw] gap-[1.5vw]">
                          <div class="bg-white/20 2xl:h-4 lg:h-[1vw] sm:h-[1.55vw] h-[3.65vw] rounded-full w-full overflow-hidden">
                            <span
                              class="progress block h-full rounded-full"
                              data-theme="language"
                              style="background:#0972CE; width:0%"></span>
                          </div>

                          <div class="flex items-center justify-between text-white font-medium 2xl:text-fs14 lg:text-[1vw] sm:text-[1.57vw] text-[3.4vw]">
                            <div>ภาษา</div>
                            <div><span class="counter-hours" data-theme="language">0</span> ชั่วโมง</div>
                          </div>
                        </div>
                      </div>

                    </div>

                    <a href="<?php echo site_url('/') ?>nextlearn"
                      class="2xl:text-fs24 lg:text-[1.57vw] sm:text-[2vw] text-[5vw] bg-white font-semibold rounded-full w-[95%] mx-auto sm:py-[1.25%] py-[2.25%] inline-block sm:mt-[3%] mt-[4%] hover:bg-[#F7DD52] transition-colors duration-200">
                      เริ่มต้นเรียน
                    </a>
                  </div>




                </div>
              </div>

              <?php foreach ($home_banner_cards as $banner_index => $banner_card) :
                if ($banner_index > 1 || !is_array($banner_card)) {
                  continue;
                }

                $layout = $home_banner_layout[$banner_index] ?? $home_banner_layout[1];
                $card_bg = $layout['bg'];
                $card_img = $media_url($banner_card['image'] ?? null);
                if ($card_img === '') {
                  $card_img = $layout['fallback_image'];
                }

                $card_alt = '';
                if (!empty($banner_card['image']['alt'])) {
                  $card_alt = trim((string) $banner_card['image']['alt']);
                }
                $card_seo_title = trim((string) ($banner_card['seo_title'] ?? ''));
                if ($card_seo_title === '') {
                  $card_seo_title = $card_alt;
                }
                if ($card_alt === '') {
                  $card_alt = $card_seo_title;
                }

                $action_type = trim((string) ($banner_card['action_type'] ?? 'popup'));
                if ($action_type !== 'link' && $action_type !== 'popup') {
                  $action_type = 'popup';
                }
                $link_url = trim((string) ($banner_card['link_url'] ?? ''));
                $modal_id = 'modal-home-banner-' . ($banner_index + 1);
                $is_link = ($action_type === 'link' && $link_url !== '');
                $is_popup = ($action_type === 'popup');
                $wrapper_tag = $is_link ? 'a' : 'div';

                if ($is_popup) {
                  $popup_buttons = array();
                  $popup_button_1_text = trim((string) ($banner_card['popup_button_text'] ?? ''));
                  $popup_button_1_url = trim((string) ($banner_card['popup_button_url'] ?? ''));
                  if ($popup_button_1_text !== '' && $popup_button_1_url !== '') {
                    $popup_buttons[] = array(
                      'text' => $popup_button_1_text,
                      'url' => $popup_button_1_url,
                    );
                  }
                  $popup_button_2_text = trim((string) ($banner_card['popup_button_2_text'] ?? ''));
                  $popup_button_2_url = trim((string) ($banner_card['popup_button_2_url'] ?? ''));
                  if ($popup_button_2_text !== '' && $popup_button_2_url !== '') {
                    $popup_buttons[] = array(
                      'text' => $popup_button_2_text,
                      'url' => $popup_button_2_url,
                    );
                  }

                  $popup_image_field = isset($banner_card['popup_image']) && is_array($banner_card['popup_image']) ? $banner_card['popup_image'] : array();
                  $popup_image_display_mode = trim((string) ($banner_card['popup_image_display_mode'] ?? 'actual'));
                  if ($popup_image_display_mode !== 'full' && $popup_image_display_mode !== 'actual') {
                    $popup_image_display_mode = 'actual';
                  }

                  $home_banner_modals[] = array(
                    'id' => $modal_id,
                    'title' => trim((string) ($banner_card['popup_title'] ?? '')),
                    'body' => (string) ($banner_card['popup_body'] ?? ''),
                    'image' => $media_url($popup_image_field),
                    'image_alt' => !empty($popup_image_field['alt']) ? trim((string) $popup_image_field['alt']) : '',
                    'image_width' => !empty($popup_image_field['width']) ? (int) $popup_image_field['width'] : 0,
                    'image_height' => !empty($popup_image_field['height']) ? (int) $popup_image_field['height'] : 0,
                    'image_display_mode' => $popup_image_display_mode,
                    'buttons' => $popup_buttons,
                  );
                }
              ?>
                <div class="<?php echo esc_attr($layout['wrap']); ?>">
                  <<?php echo $wrapper_tag; ?>
                    <?php if ($is_link) : ?>
                      href="<?php echo esc_url($link_url); ?>"
                    <?php elseif ($is_popup) : ?>
                      data-modal-id="<?php echo esc_attr($modal_id); ?>"
                    <?php endif; ?>
                    class="min-h-[170px] relative h-full flex items-center justify-center group"
                    style="background-color: <?php echo esc_attr($card_bg); ?>;"
                  >
                    <div class="w-full flex items-center justify-center">
                      <?php if ($card_seo_title !== '') : ?>
                        <h2 class="sr-only"><?php echo esc_html($card_seo_title); ?></h2>
                      <?php endif; ?>

                      <?php if ($card_img !== '') : ?>
                        <picture class="block w-full">
                          <img
                            src="<?php echo esc_url($card_img); ?>"
                            alt="<?php echo esc_attr($card_alt); ?>"
                            loading="lazy"
                            decoding="async"
                            width="<?php echo esc_attr($layout['img_width']); ?>"
                            height="<?php echo esc_attr($layout['img_height']); ?>"
                            class="mx-auto w-full <?php echo esc_attr($layout['maxw']); ?> h-auto object-contain select-none pointer-events-none"
                          />
                        </picture>
                      <?php endif; ?>
                    </div>

                    <?php if ($is_popup) : ?>
                      <div
                        class="icon-plus xl:w-9 sm:w-7 w-8 aspect-square absolute bottom-4 right-4 group-hover:rotate-90 transition-transform duration-200"
                        aria-hidden="true"
                      ></div>
                    <?php endif; ?>
                  </<?php echo $wrapper_tag; ?>>
                </div>
              <?php endforeach; ?>



            </div>
          </div>
        </section>

        <section class="xl:py-20 sm:py-16 py-10"
                 data-aos="fade-in">
          <div class="container">
            <h2 class="text-center xl:text-fs30 lg:text-fs22 md:text-fs30 text-fs22 font-bold text-primary">
              <?php echo esc_html($intro_title); ?>
            </h2>
            <div class="max-w-[850px] mx-auto sm:mt-6 mt-4 px-4">
              <p class="text-center xl:text-fs36 text-fs24 leading-snug">
                <?php echo wp_kses_post($intro_body); ?>
              </p>
            </div>
          </div>
        </section>


        <section class="xl:py-20 sm:py-16 py-10 overflow-hidden"
                 data-aos="fade-in">
          <div class="container">
            <h2 class="text-center leading-snug xl:text-fs64 md:text-fs50 text-fs34 md:mb-12 sm:mb-8 mb-6">
              คุณ<span class="font-bold text-primary">อยากเรียน</span><br class="md:hidden block">อะไรใหม่
            </h2>
            <div class="grid grid-cols-12 gap-8">
              <div class="lg:col-span-10 col-span-12 lg:col-start-2 col-start-1">
                <?php
                set_query_var('cc_taxonomy', 'course_category');
                set_query_var('cc_hide_empty', false); // หรือ true
                set_query_var('cc_parent', 0);         // ถ้าต้องการเฉพาะหมวดหลัก (เอาออกถ้าจะเอาทุกหมวด)
                set_query_var('cc_default_img', THEME_URI . '/assets/images/category/img01.png');

                // ปุ่มด้านขวา
                set_query_var('cc_all_link', site_url('/course'));     // ลิงก์รวม
                set_query_var('cc_all_text', 'ดูคอร์สทั้งหมด');      // ข้อความปุ่ม

                get_template_part('template-parts/homepage/category-swiper-index');
                ?>

              </div>
            </div>
          </div>
        </section>


        <section class="xl:py-20 sm:py-16 py-10 section-expand">
          <div class="container">
            <h2 class="text-center leading-snug xl:text-fs64 md:text-fs50 text-fs34 md:mb-12 sm:mb-8 mb-6">
              <?php echo esc_html($lifelong_section_title); ?>
            </h2>
          </div>
          <?php
          $lifelong_modal_count = 0;
          $lifelong_desktop_items = array();
          foreach ($lifelong_items as $lifelong_item_raw) :
            if (!is_array($lifelong_item_raw)) {
              continue;
            }
            $lifelong_title = trim((string) ($lifelong_item_raw['title'] ?? ''));
            if ($lifelong_title === '') {
              continue;
            }
            $lifelong_teaser = (string) ($lifelong_item_raw['teaser'] ?? '');
            $lifelong_image_desktop = $media_url($lifelong_item_raw['image_desktop'] ?? null);
            if ($lifelong_image_desktop === '') {
              continue;
            }

            $lifelong_button_text = trim((string) ($lifelong_item_raw['detail_button_text'] ?? ''));
            if ($lifelong_button_text === '') {
              $lifelong_button_text = 'อ่านรายละเอียด';
            }
            $lifelong_show_button = !empty($lifelong_item_raw['show_detail_button']);
            $lifelong_popup_title = trim((string) ($lifelong_item_raw['popup_title'] ?? ''));
            $lifelong_popup_body = (string) ($lifelong_item_raw['popup_body'] ?? '');
            $lifelong_popup_image_field = isset($lifelong_item_raw['popup_image']) && is_array($lifelong_item_raw['popup_image']) ? $lifelong_item_raw['popup_image'] : array();
            $lifelong_popup_image_url = $media_url($lifelong_popup_image_field);
            $lifelong_popup_image_mode = trim((string) ($lifelong_item_raw['popup_image_display_mode'] ?? 'actual'));
            if ($lifelong_popup_image_mode !== 'full' && $lifelong_popup_image_mode !== 'actual') {
              $lifelong_popup_image_mode = 'actual';
            }

            $lifelong_popup_buttons = array();
            $lifelong_popup_button_1_text = trim((string) ($lifelong_item_raw['popup_button_text'] ?? ''));
            $lifelong_popup_button_1_url = trim((string) ($lifelong_item_raw['popup_button_url'] ?? ''));
            if ($lifelong_popup_button_1_text !== '' && $lifelong_popup_button_1_url !== '') {
              $lifelong_popup_buttons[] = array('text' => $lifelong_popup_button_1_text, 'url' => $lifelong_popup_button_1_url);
            }
            $lifelong_popup_button_2_text = trim((string) ($lifelong_item_raw['popup_button_2_text'] ?? ''));
            $lifelong_popup_button_2_url = trim((string) ($lifelong_item_raw['popup_button_2_url'] ?? ''));
            if ($lifelong_popup_button_2_text !== '' && $lifelong_popup_button_2_url !== '') {
              $lifelong_popup_buttons[] = array('text' => $lifelong_popup_button_2_text, 'url' => $lifelong_popup_button_2_url);
            }

            $lifelong_item_key = trim((string) ($lifelong_item_raw['item_key'] ?? ''));
            $lifelong_related_policy_boxes = array();
            if ($lifelong_item_key !== '') {
              foreach ($policy_items_by_col as $policy_col_data) {
                $policy_col_items = !empty($policy_col_data['items']) && is_array($policy_col_data['items']) ? $policy_col_data['items'] : array();
                foreach ($policy_col_items as $policy_item) {
                  if (!is_array($policy_item)) {
                    continue;
                  }
                  $policy_targets = isset($policy_item['lifelong_targets']) && is_array($policy_item['lifelong_targets']) ? $policy_item['lifelong_targets'] : array();
                  if (!in_array($lifelong_item_key, $policy_targets, true)) {
                    continue;
                  }
                  $policy_text = isset($policy_item['text']) ? trim((string) $policy_item['text']) : '';
                  $policy_highlight = isset($policy_item['highlight']) ? trim((string) $policy_item['highlight']) : '';
                  if ($policy_text === '' && $policy_highlight === '') {
                    continue;
                  }
                  $lifelong_related_policy_boxes[] = array(
                    'text' => $policy_text,
                    'highlight' => $policy_highlight,
                    'item_bg' => $policy_col_data['item_bg'] ?? '#F5F5F5',
                  );
                }
              }
            }

            // Legacy fallback: support old mapping by column from lifelong item.
            if (empty($lifelong_related_policy_boxes)) {
              $lifelong_policy_keys = isset($lifelong_item_raw['policy_keys']) && is_array($lifelong_item_raw['policy_keys']) ? $lifelong_item_raw['policy_keys'] : array();
              foreach ($lifelong_policy_keys as $lifelong_policy_key) {
                $lifelong_policy_key = trim((string) $lifelong_policy_key);
                if ($lifelong_policy_key === '' || empty($policy_items_by_col[$lifelong_policy_key]['items'])) {
                  continue;
                }
                foreach ($policy_items_by_col[$lifelong_policy_key]['items'] as $policy_item) {
                  if (!is_array($policy_item)) {
                    continue;
                  }
                  $policy_text = isset($policy_item['text']) ? trim((string) $policy_item['text']) : '';
                  $policy_highlight = isset($policy_item['highlight']) ? trim((string) $policy_item['highlight']) : '';
                  if ($policy_text === '' && $policy_highlight === '') {
                    continue;
                  }
                  $lifelong_related_policy_boxes[] = array(
                    'text' => $policy_text,
                    'highlight' => $policy_highlight,
                    'item_bg' => $policy_items_by_col[$lifelong_policy_key]['item_bg'],
                  );
                }
              }
            }

            if (!empty($lifelong_related_policy_boxes)) {
              $lifelong_related_policy_boxes = array_values(array_unique(array_map('serialize', $lifelong_related_policy_boxes)));
              $lifelong_related_policy_boxes = array_map('unserialize', $lifelong_related_policy_boxes);
            }

            $lifelong_has_popup_content = $lifelong_popup_title !== ''
              || trim(wp_strip_all_tags($lifelong_popup_body)) !== ''
              || $lifelong_popup_image_url !== ''
              || !empty($lifelong_popup_buttons)
              || !empty($lifelong_related_policy_boxes);
            $lifelong_enable_popup = $lifelong_show_button && $lifelong_has_popup_content;
            $lifelong_modal_id = '';
            if ($lifelong_enable_popup) {
              $lifelong_modal_count++;
              $lifelong_modal_id = 'lifelongmodal' . $lifelong_modal_count;
              $lifelong_generated_modals[] = array(
                'id' => $lifelong_modal_id,
                'title' => $lifelong_popup_title,
                'body' => $lifelong_popup_body,
                'image' => $lifelong_popup_image_url,
                'image_alt' => !empty($lifelong_popup_image_field['alt']) ? trim((string) $lifelong_popup_image_field['alt']) : '',
                'image_width' => !empty($lifelong_popup_image_field['width']) ? (int) $lifelong_popup_image_field['width'] : 0,
                'image_display_mode' => $lifelong_popup_image_mode,
                'buttons' => $lifelong_popup_buttons,
                'policy_boxes' => $lifelong_related_policy_boxes,
              );
            }

            $lifelong_desktop_items[] = array(
              'title' => $lifelong_title,
              'teaser' => $lifelong_teaser,
              'image_desktop' => $lifelong_image_desktop,
              'button_text' => $lifelong_button_text,
              'has_popup' => $lifelong_enable_popup,
              'modal_id' => $lifelong_modal_id,
            );
          endforeach;
          ?>
          <div class="container max-lg:px-0 lg:block hidden">
            <div class="lg:flex block justify-between lg:rounded-3xl overflow-hidden">
              <?php foreach ($lifelong_desktop_items as $lifelong_item) : ?>
                <div class="expand-item group flex-1 lg:transition-all lg:duration-600">
                  <div data-aos="fade-in"
                       class="expand-item-inner xl:min-h-[520px] lg:min-h-[380px] sm:min-h-[280px] min-h-[200px] bg-center lg:bg-size-[auto_100%] bg-size-[100%_auto] bg-no-repeat h-full"
                       style="background-image:url('<?php echo esc_url($lifelong_item['image_desktop']); ?>');">
                    <div class="p-6 max-w-[700px] mx-auto relative">
                      <h2 class="2xl:text-fs34 lg:text-[2.25vw] text-fs28 text-white font-bold font-bkk"><?php echo esc_html($lifelong_item['title']); ?></h2>
                      <div class="relative expand-desc opacity-0 transition-opacity duration-400">
                        <div class="absolute w-full whitespace-nowrap">
                          <?php if ($lifelong_item['teaser'] !== '') : ?>
                            <p class="xl:text-fs20 lg:text-fs14 text-fs16 text-white font-normal font-anuphan leading-snug mt-3">
                              <?php echo wp_kses_post($lifelong_item['teaser']); ?>
                            </p>
                          <?php endif; ?>
                          <?php if ($lifelong_item['has_popup']) : ?>
                            <a href="#!"
                               class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!"
                               data-modal-id="<?php echo esc_attr($lifelong_item['modal_id']); ?>"><?php echo esc_html($lifelong_item['button_text']); ?></a>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="lg:hidden block">
            <div class="stack_wrapper">
              <ul class="stack_items">
                <?php foreach ($lifelong_desktop_items as $lifelong_item) : ?>
                  <li class="stack_section expand-item-inner bg-cover bg-no-repeat bg-center"
                      style="background-image:url('<?php echo esc_url($lifelong_item['image_desktop']); ?>');">
                    <div class="p-6 max-lg:pl-16 max-md:pl-10 max-sm:pl-4 text-white relative">
                      <h2 class="text-fs28 font-bold font-bkk"><?php echo esc_html($lifelong_item['title']); ?></h2>
                      <div class="mt-2 desc">
                        <?php if ($lifelong_item['teaser'] !== '') : ?>
                          <p><?php echo wp_kses_post($lifelong_item['teaser']); ?></p>
                        <?php endif; ?>
                        <?php if ($lifelong_item['has_popup']) : ?>
                          <a href="#!"
                             class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!"
                             data-modal-id="<?php echo esc_attr($lifelong_item['modal_id']); ?>"><?php echo esc_html($lifelong_item['button_text']); ?></a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </section>


        <section class="xl:py-20 sm:py-16 py-10  section-expand overflow-hidden">
          <div class="container">
            <h2 class="text-center leading-snug xl:text-fs64 md:text-fs50 text-fs34 md:mb-12 sm:mb-8 mb-6">

            <span class="font-bold text-primary">กิจกรรม</span>และ<br class="lg:hidden block"><span class="font-bold text-primary">โครงการ</span>ที่น่าสนใจ
            </h2>
          </div>
          <?php
          $highlight_generated_modals = array();
          $highlight_items = function_exists('get_field') ? get_field('highlight_items') : array();

          $highlight_fallback_items = array(
            array(
              'action_type' => 'link',
              'display_pages' => array('homepage', 'nextlearn'),
              'link_url' => site_url('/') . 'course_provider/โรงเรียนฝึกอาชีพ',
              'image_url' => THEME_URI . '/assets/images/highlight/card2.png',
              'seo_title' => 'คอร์สโรงเรียนฝึกอาชีพ เปิดรับสมัครแล้ว สำหรับ ปี 2569',
            ),
            array(
              'action_type' => 'link',
              'display_pages' => array('homepage', 'nextlearn'),
              'link_url' => 'https://learning.bangkok.go.th/ecdplan/',
              'image_url' => THEME_URI . '/assets/images/highlight/card1.png',
              'seo_title' => 'แผนการจัดประสบการณ์การเรียนรู้ของเด็กก่อนวัยเรียน สำหรับเด็กอายุ 2-6 ปี',
            ),
            array(
              'action_type' => 'link',
              'display_pages' => array('homepage', 'nextlearn'),
              'link_url' => site_url('/') . 'tag/readyforwork',
              'image_url' => THEME_URI . '/assets/images/highlight/playlist2.jpg',
              'seo_title' => 'เรียนจบปุ๊บ รับงานปั๊บ เรียนจบพร้อมต่อยอดงานทันที',
            ),
            array(
              'action_type' => 'link',
              'display_pages' => array('homepage', 'nextlearn'),
              'link_url' => site_url('/') . 'tag/weekend',
              'image_url' => THEME_URI . '/assets/images/highlight/playlist3.jpg',
              'seo_title' => 'เรียนวันหยุด เสาร์–อาทิตย์ เรียนได้ ไม่กระทบเวลางาน',
            ),
            array(
              'action_type' => 'link',
              'display_pages' => array('homepage', 'nextlearn'),
              'link_url' => site_url('/') . 'course_category/งานช่างไฟฟ้า-อิเล็กทรอน/',
              'image_url' => THEME_URI . '/assets/images/highlight/playlist4.jpg',
              'seo_title' => 'สายช่าง เสริมทักษะอาชีพ สร้างรายได้จริง',
            ),
            array(
              'action_type' => 'link',
              'display_pages' => array('homepage', 'nextlearn'),
              'link_url' => site_url('/') . 'course_provider/microsoft/',
              'image_url' => THEME_URI . '/assets/images/highlight/playlist5.jpg',
              'seo_title' => 'เรียน AI ไม่ตกเทรนด์ อัปสกิลเทคโนโลยี ทันโลกดิจิทัล',
            ),
          );

          if (empty($highlight_items) || !is_array($highlight_items)) {
            $highlight_items = $highlight_fallback_items;
          }
          ?>
          <div class="container  sec-highlight">
            <div class="swiper xl:overflow-hidden! overflow-visible!">
              <div class="swiper-wrapper">
                <?php
                $highlight_modal_count = 0;
                $widget_tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');
                $widget_now = new DateTimeImmutable('now', $widget_tz);
                $parse_widget_datetime = static function ($raw, DateTimeZone $tz) {
                  $raw = trim((string) $raw);
                  if ($raw === '') {
                    return null;
                  }

                  $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw, $tz);
                  if ($dt instanceof DateTimeImmutable) {
                    return $dt;
                  }

                  try {
                    return new DateTimeImmutable($raw, $tz);
                  } catch (Exception $e) {
                    return null;
                  }
                };

                foreach ($highlight_items as $item) :
                  $display_pages = (isset($item['display_pages']) && is_array($item['display_pages'])) ? $item['display_pages'] : array();
                  $show_on_homepage = empty($display_pages) || in_array('homepage', $display_pages, true);
                  if (!$show_on_homepage) {
                    continue;
                  }

                  $schedule_enabled = !empty($item['schedule_enabled']);
                  if ($schedule_enabled) {
                    $start_at = $parse_widget_datetime($item['start_at'] ?? '', $widget_tz);
                    $end_at = $parse_widget_datetime($item['end_at'] ?? '', $widget_tz);

                    if ($start_at instanceof DateTimeImmutable && $widget_now < $start_at) {
                      continue;
                    }
                    if ($end_at instanceof DateTimeImmutable && $widget_now > $end_at) {
                      continue;
                    }
                  }

                  $action_type = isset($item['action_type']) ? (string) $item['action_type'] : '';
                  if ($action_type === '') {
                    $action_type = !empty($item['action_popup']) ? 'popup' : 'link';
                  }
                  $is_popup = $action_type === 'popup';
                  $link_url = isset($item['link_url']) ? trim((string) $item['link_url']) : '';

                  $image_url = '';
                  if (!empty($item['image']) && is_array($item['image'])) {
                    $image_url = isset($item['image']['url']) ? (string) $item['image']['url'] : '';
                  } elseif (!empty($item['image_url'])) {
                    $image_url = (string) $item['image_url'];
                  }

                  $alt_text = '';
                  if (!empty($item['image']) && is_array($item['image']) && !empty($item['image']['alt'])) {
                    $alt_text = trim((string) $item['image']['alt']);
                  }
                  if ($alt_text === '') {
                    $alt_text = isset($item['seo_title']) ? trim((string) $item['seo_title']) : '';
                  }

                  $seo_title = isset($item['seo_title']) ? trim((string) $item['seo_title']) : '';
                  if ($seo_title === '') {
                    $seo_title = $alt_text;
                  }

                  if ($image_url === '') {
                    continue;
                  }

                  $modal_id = '';
                  if ($is_popup) {
                    $highlight_modal_count++;
                    $modal_id = 'highlightmodal' . $highlight_modal_count;
                    $popup_image_url = '';
                    if (!empty($item['popup_image']) && is_array($item['popup_image'])) {
                      $popup_image_url = isset($item['popup_image']['url']) ? trim((string) $item['popup_image']['url']) : '';
                    }
                    $highlight_generated_modals[] = array(
                      'id' => $modal_id,
                      'title' => isset($item['popup_title']) ? trim((string) $item['popup_title']) : '',
                      'body' => isset($item['popup_body']) ? trim((string) $item['popup_body']) : '',
                      'image' => $popup_image_url,
                      'cta_label' => isset($item['popup_cta_label']) ? trim((string) $item['popup_cta_label']) : '',
                      'cta_url' => isset($item['popup_cta_url']) ? trim((string) $item['popup_cta_url']) : '',
                    );
                  }
                ?>
                  <div class="swiper-slide lg:w-auto!">
                    <?php if ($is_popup) : ?>
                      <div
                        data-modal-id="<?php echo esc_attr($modal_id); ?>"
                        class="card-highlight lg:w-[400px] cursor-pointer group"
                        size="medium"
                        role="button"
                        tabindex="0"
                        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}"
                      >
                    <?php else : ?>
                      <a
                        href="<?php echo esc_url($link_url !== '' ? $link_url : '#'); ?>"
                        class="card-highlight lg:w-[400px]"
                        size="medium"
                      >
                    <?php endif; ?>
                        <div>
                          <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($alt_text); ?>">
                        </div>

                        <div class="txt">
                          <div class="mt-auto">
                            <?php if ($seo_title !== '') : ?>
                              <h3 class="sr-only"><?php echo esc_html($seo_title); ?></h3>
                            <?php endif; ?>
                          </div>
                        </div>
                        <?php if ($is_popup) : ?>
                          <div
                            class="icon-plus xl:w-9 sm:w-7 w-8 aspect-square absolute bottom-4 right-4 group-hover:rotate-90 transition-transform duration-200"
                            aria-hidden="true"
                          ></div>
                        <?php endif; ?>

                    <?php if ($is_popup) : ?>
                      </div>
                    <?php else : ?>
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="swiper-control">
                <div class="swiper-pagination"></div>
                <div class="flex items-center justify-end gap-3">
                  <div class="swiper-button-prev"></div>
                  <div class="swiper-button-next"></div>
                </div>
              </div>
            </div>
          </div>
      </section>

        <!-- <section class="xl:pb-20 sm:pb-16 pb-10"
                 data-aos="fade-in">
          <div class="container">
            <div class="sm:grid sm:grid-cols-12 gap-8 mt-8">
              <div class="sm:col-span-10  sm:col-start-2 grid lg:grid-cols-2 grid-cols-1 gap-8">
                <a href="#!"
                   class="card-highlight"
                   size="large"
                   data-aos="fade-in">
                  <div class="img-bg"><img src="<?php echo THEME_URI ?>/assets/images/highlight/img01.jpg"
                         alt=""></div>
                  <div class="txt">
                    <div class="mt-auto">
                      <h2>Up-skill</h2>
                      <h3>อัพสกิล ทักษะดิจิทัล</h3>
                    </div>
                  </div>
                </a>

                <a href="#!"
                   class="card-highlight"
                   size="large"
                   data-aos="fade-in">
                  <div class="img-bg"><img src="<?php echo THEME_URI ?>/assets/images/highlight/img02.jpg"
                         alt=""></div>
                  <div class="txt">
                    <div>
                      <h2><span class="font-normal">รับสมัครคอร์ส</span></h2>
                      <h2>โรงเรียนฝึกอาชีพ</h2>
                      <h3>ปี 2568</h3>
                    </div>
                    <div class="mt-auto">
                      <div class="box-event-countdown">
                        <div class="flex items-center gap-[3%]">
                          <h4 class="text-center">จะเปิดรับ<br>สมัครในอีก</h4>
                          <div class="flex-1 flex justify-center items-center text-center divide-x divide-white/25">
                            <div class="flex-1">
                              <p><span class="num">3</span>วัน</p>
                            </div>
                            <div class="flex-1">
                              <p><span class="num">22</span>ชั่วโมง</p>
                            </div>
                            <div class="flex-1">
                              <p><span class="num">59</span>วินาที</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </section> -->


        <section class="xl:py-20 sm:py-16 py-10 max-md:overflow-hidden"
                 data-aos="fade-in">
          <div class="container">
            <h2 class="text-center leading-snug xl:text-fs64 md:text-fs50 text-fs34 sm:mb-4 mb-2">
              <span class="font-bold text-primary">มีอะไรดี ๆ</span> <br class="md:hidden block"><span
                    class="inline-block">เกิดขึ้นแล้วบ้าง?</span>
            </h2>
            <h3 class="text-center xl:text-fs26 md:text-fs18 text-fs18 font-anuphan md:mb-12 sm:mb-8 mb-6">
              สิ่งที่กทม.ได้เริ่มทำแล้ว เพื่อขับเคลื่อน<span class="inline-block">เมืองแห่งการเรียนรู้</span>
            </h3>
            <div class="grid grid-cols-12 gap-8">
              <div class="lg:col-span-10 col-span-12 lg:col-start-2 col-start-1">
                <?php
                $policy_cols = function_exists('get_field') ? get_field('policy_cols') : array();
                $policy_generated_modals = array();
                $policy_modal_count = 0;

                $policy_col_configs = array(
                  array(
                    'key' => 'col_1',
                    'default_title' => "สนับสนุนการ\nเรียนรู้ตลอดชีวิต",
                    'header_bg' => '#00744B',
                    'item_bg' => '#D8FFF1',
                  ),
                  array(
                    'key' => 'col_2',
                    'default_title' => "พัฒนาคุณภาพ\nการศึกษา",
                    'header_bg' => '#0972CE',
                    'item_bg' => '#DBEEFF',
                  ),
                  array(
                    'key' => 'col_3',
                    'default_title' => "โรงเรียน\nเป็นพื้นที่ปลอดภัย",
                    'header_bg' => '#EA3DA9',
                    'item_bg' => '#FFD7F0',
                  ),
                  array(
                    'key' => 'col_4',
                    'default_title' => "ยกระดับ\nการดูแลเด็กเล็ก",
                    'header_bg' => '#FEB449',
                    'item_bg' => '#FFECD1',
                  ),
                );
                ?>
                <div class="swiper swiper-activity-body overflow-visible! md:pb-12! pb-4!">
                  <div class="swiper-wrapper">
                    <?php foreach ($policy_col_configs as $col_index => $col_config) :
                      $col_data = $policy_cols[$col_config['key']] ?? array();
                      $col_title_raw = !empty($col_data['title']) ? $col_data['title'] : $col_config['default_title'];
                      $col_items = !empty($col_data['items']) && is_array($col_data['items']) ? $col_data['items'] : array();
                    ?>
                      <div class="swiper-slide h-auto!">
                        <div class="lg:py-6 py-4 xl:rounded-3xl lg:rounded-20 rounded-2xl sticky lg:top-3 md:top-6 top-0 z-1 flex items-center justify-center text-white xl:text-fs22 lg:text-fs16 md:text-fs14 text-fs16 text-center font-semibold leading-tight shadow-xl"
                             style="background-color: <?php echo esc_attr($col_config['header_bg']); ?>;">
                          <?php echo nl2br(esc_html($col_title_raw)); ?>
                        </div>
                        <div class="box-activity">
                          <?php foreach ($col_items as $item_index => $item) :
                            $item_text = isset($item['text']) ? trim((string) $item['text']) : '';
                            $item_highlight = isset($item['highlight']) ? trim((string) $item['highlight']) : '';
                            $item_has_popup = !empty($item['has_popup']);

                            if ($item_text === '' && $item_highlight === '') {
                              continue;
                            }

                            $modal_id = '';
                            if ($item_has_popup) {
                              $policy_modal_count++;
                              $modal_id = 'policymodal' . $policy_modal_count;

                              $modal_title = isset($item['modal_title']) ? trim((string) $item['modal_title']) : '';
                              $modal_body = isset($item['modal_body']) ? trim((string) $item['modal_body']) : '';
                              if ($modal_body === '') {
                                $modal_body = $item_text;
                                if ($item_highlight !== '') {
                                  $modal_body .= ' ' . $item_highlight;
                                }
                              }

                              $policy_generated_modals[] = array(
                                'id' => $modal_id,
                                'title' => $modal_title,
                                'body' => $modal_body,
                              );
                            }
                          ?>
                            <div class="item" style="background-color:<?php echo esc_attr($col_config['item_bg']); ?>;"<?php echo $modal_id ? ' data-modal-id="' . esc_attr($modal_id) . '"' : ''; ?>>
                              <?php echo nl2br(esc_html($item_text)); ?>
                              <?php if ($item_highlight !== '') : ?>
                                <span><?php echo esc_html($item_highlight); ?></span>
                              <?php endif; ?>
                              <?php if ($item_has_popup) : ?>
                                <i class="icon icon-plus" aria-hidden="true"></i>
                              <?php endif; ?>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- <section class="xl:py-20 sm:py-16 py-10"
                 data-aos="fade-in">
          <div class="container">
            <h2 class="text-center leading-snug xl:text-fs64 md:text-fs50 text-fs34 md:mb-12 sm:mb-8 mb-6">
              <span class="font-bold text-primary inline-block">จากใจผู้เรียนจริง</span><br class="md:hidden block">
              ไม่ว่าเป็นใคร<br class="md:block hidden">
              อายุเท่าไหร่<br class="md:hidden block">ก็เรียนได้
            </h2>
          </div>
          <div class="overflow-hidden min-w-px">
            <div class="swiper overflow-visible! swiper-testimonial top md:mb-6 mb-4">
              <div class="swiper-wrapper items-end">
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img01.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img01.jpg"
                         alt="">
                  </a>
                </div>
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://www.kenshinproperty.com/wp-content/uploads/2025/04/section2-video2.mp4"
                     data-fancybox="gallery-testimonial"
                     data-caption="">
                    <video autoplay
                           muted
                           loop
                           playsinline
                           preload="auto"
                           class="aspect-4/5 w-full h-full object-cover">
                      <source src="https://www.kenshinproperty.com/wp-content/uploads/2025/04/section2-video2.mp4"
                              type="video/mp4">
                      Your browser does not support the video tag.
                    </video>
                  </a>
                </div>
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img02.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img02.jpg"
                         alt="">
                  </a>
                </div>
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img03.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="Lorem ipsum dolor sit amet consectetur adipisicing elit. Cum tenetur impedit ipsam sed, fugiat deserunt atque praesentium doloremque perferendis placeat laborum unde nobis a, cumque sint molestias modi quidem facilis!">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img03.jpg"
                         alt="">
                  </a>
                </div>
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img04.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="Lorem ipsum dolor sit amet consectetur adipisicing elit. Cum tenetur impedit ipsam sed, fugiat deserunt atque praesentium doloremque perferendis placeat laborum unde nobis a, cumque sint molestias modi quidem facilis!">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img04.jpg"
                         alt="">
                  </a>
                </div>
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img05.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="กรุงเทพมหานครได้รับคัดเลือกเป็นสมาชิกเครือข่ายเมืองแห่งการเรียนรู้ของยูเนสโก (UNESCO GNLC) เมื่อวันที่ 14 กุมภาพันธ์ 2567 ซึ่งเครือข่ายนี้ มีสมาชิกรวมกว่า 356 เมือง ใน 79 ประเทศ">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img05.jpg"
                         alt="">
                  </a>
                </div>
              </div>
            </div>

            <div class="swiper overflow-visible! swiper-testimonial bottom">
              <div class="swiper-wrapper items-start">
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img05.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="Lorem ipsum dolor sit amet consectetur adipisicing elit. Cum tenetur impedit ipsam sed, fugiat deserunt atque praesentium doloremque perferendis placeat laborum unde nobis a, cumque sint molestias modi quidem facilis!">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img05.jpg"
                         alt="">
                  </a>
                </div>
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img07.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img07.jpg"
                         alt="">
                  </a>
                </div>
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img06.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="Lorem ipsum dolor sit amet consectetur adipisicing elit. Cum tenetur impedit ipsam sed, fugiat deserunt atque praesentium doloremque perferendis placeat laborum unde nobis a, cumque sint molestias modi quidem facilis!">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img06.jpg"
                         alt="">
                  </a>
                </div>
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img03.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="Lorem ipsum dolor sit amet consectetur adipisicing elit. Cum tenetur impedit ipsam sed, fugiat deserunt atque praesentium doloremque perferendis placeat laborum unde nobis a, cumque sint molestias modi quidem facilis!">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img03.jpg"
                         alt="">
                  </a>
                </div>
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img01.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img01.jpg"
                         alt="">
                  </a>
                </div>
                <div
                     class="swiper-slide 2xl:w-[250px]! md:w-[200px]! w-[180px]! md:rounded-2xl rounded-xl overflow-hidden">
                  <a href="https://spicy-dev.com/preview/1dd/bangkok-learning-city/testimonial/img08.jpg"
                     data-fancybox="gallery-testimonial"
                     data-caption="">
                    <img src="<?php echo THEME_URI ?>/assets/images/testimonial/img08.jpg"
                         alt="">
                  </a>
                </div>
              </div>
            </div>
          </div>
        </section> -->

      </div>



    </main>
    <?php get_template_part('template-parts/footer/site-footer'); ?>
</div>


<?php get_template_part('template-parts/components/modal-search'); ?>

<div data-modal-content="modal-detail-1" class="modal lg:items-center items-start lg:p-6 p-0">
    <div class="overlay-modal"></div>
    <div class="card-modal w-full max-w-[1150px] lg:h-[85%] lg:max-h-[680px] h-full">
        <div class="absolute top-4 right-4 z-20">
            <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
                <div class="icon-close"></div>
            </button>
        </div>
        <div class="modal-content relative z-10 max-lg:overflow-y-auto! group max-h-screen h-full">
            <div class="flex lg:flex-row flex-col h-full">
                <div class="lg:flex-[40%] max-lg:h-[300px] max-sm:h-[275px] h-full">
                    <img src="<?php echo THEME_URI ?>/assets/images/expand/img01.jpg" alt="" class="w-full h-full object-cover">
                </div>
                <div class="lg:flex-[60%] xl:p-16 md:p-12 sm:p-8 p-6 lg:overflow-y-auto!">
                    <div class="max-w-[650px] mx-auto">
                        <h2 class="md:text-fs40 text-fs30 font-bold sm:mb-5 mb-4 leading-normal">เด็กอ่อน</h2>
                        <p class="text-fs16">ช่วงวัยเด็กอ่อนคือช่วงเวลาทองของพัฒนาการ เด็กเรียนรู้มากที่สุดผ่านประสาทสัมผัส
                            การได้รับการดูแลที่เหมาะสมในช่วงนี้ คือรากฐานสำคัญของการเติบโตทั้งด้านร่างกาย อารมณ์ สังคม และสติปัญญา
                            กรุงเทพฯ จึงมุ่งสร้างสภาพแวดล้อมและบริการที่ช่วย ให้ทุกครอบครัวดูแลลูกน้อยได้อย่างมั่นใจ</p>
                        <a href="#!" class="btn-link-v2 md:text-fs18! text-fs16! sm:mt-7 mt-6">แนะนำการเรียนรู้สำหรับเด็กอ่อน</a>
                        <div class="sm:mt-12 mt-10">
                            <h2 class="text-fs20 font-semibold">สิ่งที่เมืองพร้อมสนับสนุน</h2>
                            <div class="mt-5">
                                <img src="<?php echo THEME_URI ?>/assets/images/activity/img.png" alt="">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($home_banner_modals)) : ?>
  <?php foreach ($home_banner_modals as $modal) : ?>
    <div data-modal-content="<?php echo esc_attr($modal['id']); ?>" class="modal lg:items-center items-start lg:p-6 p-0">
      <div class="overlay-modal"></div>
      <div class="card-modal w-full max-w-[1150px] lg:h-[85%] lg:max-h-[680px] h-full">
        <div class="absolute top-4 right-4 z-20">
          <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
            <div class="icon-close"></div>
          </button>
        </div>
        <div class="modal-content relative z-10 max-lg:overflow-y-auto! group max-h-screen h-full">
          <div class="flex lg:flex-row flex-col h-full">
            <div class="lg:flex-[60%] xl:p-16 md:p-12 sm:p-8 p-6 lg:overflow-y-auto!">
              <div class="max-w-[650px] mx-auto max-lg:pt-6">
                <?php if (!empty($modal['title'])) : ?>
                  <h2 class="md:text-fs40 text-fs30 font-bold sm:mb-5 mb-4 leading-normal">
                    <?php echo esc_html($modal['title']); ?>
                  </h2>
                <?php endif; ?>

                <?php if (!empty($modal['image'])) : ?>
                  <?php
                  $modal_image_alt = !empty($modal['image_alt']) ? trim((string) $modal['image_alt']) : '';
                  $modal_image_display_mode = !empty($modal['image_display_mode']) ? trim((string) $modal['image_display_mode']) : 'actual';
                  $modal_image_style = '';
                  $modal_image_class = 'h-auto object-cover';
                  if ($modal_image_display_mode === 'full') {
                    $modal_image_class .= ' w-full';
                  } else {
                    $modal_image_class .= ' w-auto max-w-full';
                    $modal_image_width = !empty($modal['image_width']) ? (int) $modal['image_width'] : 0;
                    if ($modal_image_width > 1) {
                      $modal_image_style = 'width:' . max(1, (int) floor($modal_image_width / 2)) . 'px;';
                    }
                  }
                  ?>
                  <div class="mb-6">
                    <img
                      src="<?php echo esc_url($modal['image']); ?>"
                      alt="<?php echo esc_attr($modal_image_alt); ?>"
                      class="<?php echo esc_attr($modal_image_class); ?>"
                      <?php if ($modal_image_style !== '') : ?>style="<?php echo esc_attr($modal_image_style); ?>"<?php endif; ?>
                    >
                  </div>
                <?php endif; ?>

                <?php if (!empty($modal['body'])) : ?>
                  <div class="text-fs16 popup-wysiwyg"><?php echo wp_kses_post($modal['body']); ?></div>
                <?php endif; ?>

                <?php if (!empty($modal['buttons']) && is_array($modal['buttons'])) : ?>
                  <div class="mt-6 flex flex-wrap gap-3">
                    <?php foreach ($modal['buttons'] as $btn) :
                      if (!is_array($btn)) {
                        continue;
                      }
                      $btn_text = trim((string) ($btn['text'] ?? ''));
                      $btn_url = trim((string) ($btn['url'] ?? ''));
                      if ($btn_text === '' || $btn_url === '') {
                        continue;
                      }
                    ?>
                      <a
                        href="<?php echo esc_url($btn_url); ?>"
                        class="inline-flex items-center justify-center rounded-full
                          border border-black text-black
                          px-6 py-2.5 text-fs16 font-semibold
                          hover:bg-black hover:text-white
                          transition-colors duration-200"
                      >
                        <?php echo esc_html($btn_text); ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($lifelong_generated_modals)) : ?>
  <?php foreach ($lifelong_generated_modals as $modal) : ?>
    <div data-modal-content="<?php echo esc_attr($modal['id']); ?>" class="modal lg:items-center items-start lg:p-6 p-0">
      <div class="overlay-modal"></div>
      <div class="card-modal w-full max-w-[1150px] lg:h-[85%] lg:max-h-[680px] h-full">
        <div class="absolute top-4 right-4 z-20">
          <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
            <div class="icon-close"></div>
          </button>
        </div>
        <div class="modal-content relative z-10 max-lg:overflow-y-auto! group max-h-screen h-full">
          <div class="flex lg:flex-row flex-col h-full">
            <div class="lg:flex-[60%] xl:p-16 md:p-12 sm:p-8 p-6 lg:overflow-y-auto!">
              <div class="max-w-[650px] mx-auto max-lg:pt-6">
                <?php if (!empty($modal['title'])) : ?>
                  <h2 class="md:text-fs40 text-fs30 font-bold sm:mb-5 mb-4 leading-normal">
                    <?php echo esc_html($modal['title']); ?>
                  </h2>
                <?php endif; ?>

                <?php if (!empty($modal['image'])) : ?>
                  <?php
                  $modal_image_alt = !empty($modal['image_alt']) ? trim((string) $modal['image_alt']) : '';
                  $modal_image_display_mode = !empty($modal['image_display_mode']) ? trim((string) $modal['image_display_mode']) : 'actual';
                  $modal_image_style = '';
                  $modal_image_class = 'h-auto object-cover';
                  if ($modal_image_display_mode === 'full') {
                    $modal_image_class .= ' w-full';
                  } else {
                    $modal_image_class .= ' w-auto max-w-full';
                    $modal_image_width = !empty($modal['image_width']) ? (int) $modal['image_width'] : 0;
                    if ($modal_image_width > 1) {
                      $modal_image_style = 'width:' . max(1, (int) floor($modal_image_width / 2)) . 'px;';
                    }
                  }
                  ?>
                  <div class="mb-6">
                    <img
                      src="<?php echo esc_url($modal['image']); ?>"
                      alt="<?php echo esc_attr($modal_image_alt); ?>"
                      class="<?php echo esc_attr($modal_image_class); ?>"
                      <?php if ($modal_image_style !== '') : ?>style="<?php echo esc_attr($modal_image_style); ?>"<?php endif; ?>
                    >
                  </div>
                <?php endif; ?>

                <?php if (!empty($modal['body'])) : ?>
                  <div class="text-fs16 popup-wysiwyg"><?php echo wp_kses_post($modal['body']); ?></div>
                <?php endif; ?>

                <?php if (!empty($modal['policy_boxes']) && is_array($modal['policy_boxes'])) : ?>
                  <div class="mt-8">
                    <h3 class="text-fs20 font-semibold mb-4">นโยบายที่เกี่ยวข้อง</h3>
                    <div class="grid sm:grid-cols-2 grid-cols-1 gap-3">
                      <?php foreach ($modal['policy_boxes'] as $policy_box) :
                        if (!is_array($policy_box)) {
                          continue;
                        }
                        $box_text = !empty($policy_box['text']) ? trim((string) $policy_box['text']) : '';
                        $box_highlight = !empty($policy_box['highlight']) ? trim((string) $policy_box['highlight']) : '';
                        if ($box_text === '' && $box_highlight === '') {
                          continue;
                        }
                      ?>
                        <div class="rounded-xl p-4 text-fs16 leading-snug" style="background-color:<?php echo esc_attr($policy_box['item_bg'] ?? '#F5F5F5'); ?>;">
                          <?php if ($box_text !== '') : ?>
                            <span><?php echo esc_html($box_text); ?></span>
                          <?php endif; ?>
                          <?php if ($box_highlight !== '') : ?>
                            <strong><?php echo esc_html($box_highlight); ?></strong>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (!empty($modal['buttons']) && is_array($modal['buttons'])) : ?>
                  <div class="mt-6 flex flex-wrap gap-3">
                    <?php foreach ($modal['buttons'] as $btn) :
                      if (!is_array($btn)) {
                        continue;
                      }
                      $btn_text = trim((string) ($btn['text'] ?? ''));
                      $btn_url = trim((string) ($btn['url'] ?? ''));
                      if ($btn_text === '' || $btn_url === '') {
                        continue;
                      }
                    ?>
                      <a
                        href="<?php echo esc_url($btn_url); ?>"
                        class="inline-flex items-center justify-center rounded-full
                          border border-black text-black
                          px-6 py-2.5 text-fs16 font-semibold
                          hover:bg-black hover:text-white
                          transition-colors duration-200"
                      >
                        <?php echo esc_html($btn_text); ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>


<?php if (!empty($highlight_generated_modals)) : ?>
  <?php foreach ($highlight_generated_modals as $modal) : ?>
    <div data-modal-content="<?php echo esc_attr($modal['id']); ?>" class="modal lg:items-center items-start lg:p-6 p-0">
      <div class="overlay-modal"></div>
      <div class="card-modal w-full max-w-[1150px] lg:h-[85%] lg:max-h-[680px] h-full">
        <div class="absolute top-4 right-4 z-20">
          <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
            <div class="icon-close"></div>
          </button>
        </div>
        <div class="modal-content relative z-10 max-lg:overflow-y-auto! group max-h-screen h-full">
          <div class="flex lg:flex-row flex-col h-full">
            <div class="lg:flex-[60%] xl:p-16 md:p-12 sm:p-8 p-6 lg:overflow-y-auto!">
              <div class="max-w-[650px] mx-auto max-lg:pt-6">
                <?php if (!empty($modal['title'])) : ?>
                  <h2 class="md:text-fs40 text-fs30 font-bold sm:mb-5 mb-4 leading-normal">
                    <?php echo esc_html($modal['title']); ?>
                  </h2>
                <?php endif; ?>
                <?php if (!empty($modal['image'])) : ?>
                  <div class="mb-6">
                    <img src="<?php echo esc_url($modal['image']); ?>" alt="" class="w-full h-auto rounded-2xl object-cover">
                  </div>
                <?php endif; ?>

                <?php if (!empty($modal['body'])) : ?>
                  <p class="text-fs16">
                    <?php echo nl2br(esc_html($modal['body'])); ?>
                  </p>
                <?php endif; ?>

                <?php if (!empty($modal['cta_label']) && !empty($modal['cta_url'])) : ?>
                  <div class="mt-6">
                    <a
                      href="<?php echo esc_url($modal['cta_url']); ?>"
                      class="inline-flex items-center justify-center rounded-full
                        border border-black text-black
                        px-6 py-2.5 text-fs16 font-semibold
                        hover:bg-black hover:text-white
                        transition-colors duration-200"
                    >
                      <?php echo esc_html($modal['cta_label']); ?>
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>


<?php if (!empty($policy_generated_modals)) : ?>
  <?php foreach ($policy_generated_modals as $modal) : ?>
    <div data-modal-content="<?php echo esc_attr($modal['id']); ?>" class="modal items-center p-4">
      <div class="overlay-modal"></div>
      <div class="card-modal w-full max-w-[520px] rounded-2xl shadow-xl">
        <div class="absolute top-4 right-4 z-20">
          <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
            <div class="icon-close"></div>
          </button>
        </div>
        <div class="modal-content relative z-10 p-6">
          <div class="max-w-[420px] mx-auto text-center">
            <p class="text-fs16 leading-relaxed">
              <?php if (!empty($modal['title'])) : ?>
                <strong><?php echo esc_html($modal['title']); ?></strong><br>
              <?php endif; ?>
              <?php echo nl2br(esc_html($modal['body'])); ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>




<?php get_footer(); ?>
