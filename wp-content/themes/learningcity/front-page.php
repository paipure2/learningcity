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
                class="text-black md:text-fs26 text-fs18 font-semibold block md:px-4 px-2.5 leading-normal">คุณอยากเรียนอะไร</span>
        </button>

        <section class="xl:pb-20 sm:pb-16 pb-10 overflow-hidden"
                 data-aos=" fade-in">
          <div class="container">
            <div class="grid lg:grid-cols-12 sm:grid-cols-3 grid-cols-1 xl:gap-6 gap-4">
              <div class="overflow-hidden sm:rounded-3xl rounded-2xl lg:col-span-9 sm:col-span-3 col-span-1 h-full">
                <div class="sm:aspect-video aspect-[1/1.12]">
                  <div class="relative h-full">
                   

                    <div class="absolute inset-0 w-full h-full">
                      <video
                        id="heroVideo"
                        class="w-full h-full object-cover"
                        autoplay
                        muted
                        loop
                        playsinline
                        preload="metadata"
                        poster="<?php echo THEME_URI ?>/assets/video/banner-placeholder.jpg"
                      ></video>

                      <script>
                        (function () {
                          const v = document.getElementById('heroVideo');
                          const mobileSrc = "<?php echo THEME_URI ?>/assets/video/hero-mobile.mp4";
                          const desktopSrc = "<?php echo THEME_URI ?>/assets/video/hero-desktop.mp4";

                          const isMobile = window.matchMedia("(max-width: 639px)").matches;
                          v.src = isMobile ? mobileSrc : desktopSrc;

                          // บางเครื่อง iOS ต้องสั่ง load/play ใหม่หลัง set src
                          v.load();
                          const p = v.play();
                          if (p && p.catch) p.catch(() => {});
                        })();
                      </script>

                    </div>



                    <div
                         class="relative flex flex-col sm:justify-center justify-start items-start h-full sm:pl-10 pl-6 max-sm:pt-10">
                      <div class="logo-nextlearn 2xl:w-[307px] lg:w-[20vw] sm:w-[25.5vw] w-[40.5vw] sm:mb-[3%] mb-[4%]">
                      </div>
                      <h2
                          class="font-anuphan 2xl:text-fs36 lg:text-[2.34vw] sm:text-[3.25vw] text-[4.5vw] font-semibold text-white leading-snug">
                        เรียนรู้เพื่อต่อยอด
                      </h2>
                      <a href="<?php echo site_url('/') ?>nextlearn"
                         class="2xl:text-fs24 lg:text-[1.57vw] sm:text-[2vw] text-[3.5vw] bg-white font-semibold rounded-full sm:px-[2.5%] px-[4%] sm:py-[0.5%] py-[1.25%] inline-block sm:mt-[3%] mt-[4%]">ดูคอร์สทั้งหมด</a>
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


              <!-- CARD 1 -->
              <div class="overflow-hidden sm:rounded-3xl rounded-2xl lg:col-span-6 sm:col-span-2 col-span-1 h-full">
                <div
                  data-modal-id="modal-detail-bannercard1"
                  class="min-h-[170px] bg-[#C0E6FF] relative  h-full flex items-center justify-center group"
                >
                  <div class="w-full flex items-center justify-center">
                    <!-- ✅ H2 สำหรับ SEO + Accessibility (ซ่อนแบบถูกต้อง) -->
                    <h2 class="sr-only">
                      Bangkok Learning City Learning for Life Opportunities for All
                      กรุงเทพฯ เมืองแห่งโอกาส เพื่อการเรียนรู้สำหรับทุกคน
                    </h2>

                    <!-- ✅ PNG แสดงแทน (รองรับ mobile/desktop + performance) -->
                    <picture class="block w-full">
                      <!-- ถ้ามีเวอร์ชันมือถือ -->
                      <source
                        media="(max-width: 640px)"
                        srcset="<?php echo THEME_URI ?>/assets/images/homepage/unesco.png"
                      />
                      <!-- ค่า default -->
                      <img
                        src="<?php echo THEME_URI ?>/assets/images/homepage/unesco.png"
                        alt="Bangkok Learning City Learning for Life Opportunities for All กรุงเทพฯ เมืองแห่งโอกาส เพื่อการเรียนรู้สำหรับทุกคน"
                        loading="lazy"
                        decoding="async"
                        width="1200"
                        height="240"
                        class="mx-auto w-full max-w-[760px] h-auto object-contain select-none pointer-events-none"
                      />
                    </picture>
                  </div>

                  <div
                    class="icon-plus xl:w-9 sm:w-7 w-8 aspect-square absolute bottom-4 right-4 group-hover:rotate-90 transition-transform duration-200"
                    aria-hidden="true"
                  ></div>
                </div>
              </div>

              <!-- CARD 2 -->
              <div class="overflow-hidden sm:rounded-3xl rounded-2xl lg:col-span-3 sm:col-span-1 col-span-1 h-full">
                <div
                  data-modal-id="modal-detail-bannercard2"
                  class="min-h-[170px] bg-[#00744B] relative  h-full flex items-center justify-center group"
                >
                  <div class="w-full flex items-center justify-center">
                    <!-- ✅ H2 สำหรับ SEO + Accessibility -->
                    <h2 class="sr-only">
                      BKK Active จองสนามกีฬา สระว่ายน้ำ ฟิตเนส และกิจกรรมนันทนาการ
                    </h2>

                    <!-- ✅ PNG แสดงแทน -->
                    <picture class="block w-full">
                      <source media="(max-width: 640px)" srcset="<?php echo THEME_URI ?>/assets/images/homepage/bkk-active.png" />
                      <img
                        src="<?php echo THEME_URI ?>/assets/images/homepage/bkk-active.png"
                        alt="BKK Active จองสนามกีฬา สระว่ายน้ำ ฟิตเนส และกิจกรรมนันทนาการ"
                        loading="lazy"
                        decoding="async"
                        width="1200"
                        height="240"
                        class="mx-auto w-full max-w-[300px] h-auto object-contain select-none pointer-events-none"
                      />
                    </picture>
                  </div>

                  <div
                    class="icon-plus xl:w-9 sm:w-7 w-8 aspect-square absolute bottom-4 right-4 group-hover:rotate-90 transition-transform duration-200"
                    aria-hidden="true"
                  ></div>
                </div>
              </div>



            </div>
          </div>
        </section>

        <section class="xl:py-20 sm:py-16 py-10"
                 data-aos="fade-in">
          <div class="container">
            <h2 class="text-center xl:text-fs30 lg:text-fs22 md:text-fs30 text-fs22 font-bold text-primary">
              Bangkok Learning City
            </h2>
            <p class="text-center xl:text-fs36 text-fs24 leading-snug sm:mt-6 mt-4 px-4">
              กรุงเทพ เมืองที่เปิดโอกาสให้ทุกคนเรียนรู้ได้ทุกที่ทุกเวลา<br class="sm:block hidden">
              ค้นหากิจกรรม แหล่งเรียนรู้ และโครงการหลากหลาย<br class="sm:block hidden">
              จากทั่วกรุงเทพฯ เพื่อพัฒนาทักษะ เติมแรงบันดาลใจ<br class="sm:block hidden">
              และสร้างสังคมแห่งการเรียนรู้ร่วมกัน
            </p>
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


        <section class="xl:py-20 sm:py-16 py-10  section-expand">
          <div class="container">
            <h2 class="text-center leading-snug xl:text-fs64 md:text-fs50 text-fs34 md:mb-12 sm:mb-8 mb-6">
              เมืองที่เรียนรู้ได้<br class="lg:hidden block"><span class="font-bold text-primary">ทุกช่วงวัย</span>
            </h2>
          </div>
          <div class="container max-lg:px-0 lg:block hidden">
            <div class="lg:flex block justify-between lg:rounded-3xl overflow-hidden">

              <div class="expand-item group  flex-1 lg:transition-all lg:duration-600 ">
                <div data-aos="fade-in"
                     class="expand-item-inner xl:min-h-[520px] lg:min-h-[380px] sm:min-h-[280px] min-h-[200px] bg-center lg:bg-size-[auto_100%] bg-size-[100%_auto] bg-no-repeat h-full"
                     style="background-image:url('<?php echo THEME_URI ?>/assets/images/expand/baby.jpg');">
                  <div class="p-6 max-w-[700px] mx-auto relative">
                    <h2 class="2xl:text-fs34 lg:text-[2.25vw] text-fs28 text-white font-bold font-bkk">เด็กอ่อน</h2>
                    <div class="relative expand-desc opacity-0 transition-opacity duration-400">
                      <div class="absolute w-full whitespace-nowrap">
                        <p
                           class="xl:text-fs20 lg:text-fs14 text-fs16 text-white font-normal font-anuphan leading-snug mt-3">
                          เมืองที่สร้างรากฐาน<br>การดูแลเด็ก
                          ตั้งแต่แรกเกิด</p>
                        <!-- <a href="#!"
                           class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!" data-modal-id="modal-detail-2">อ่านรายละเอียด</a> -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="expand-item group  flex-1 lg:transition-all lg:duration-600 ">
                <div data-aos="fade-in"
                     class="expand-item-inner xl:min-h-[520px] lg:min-h-[380px] sm:min-h-[280px] min-h-[200px] bg-center lg:bg-size-[auto_100%] bg-size-[100%_auto] bg-no-repeat h-full"
                     style="background-image:url('<?php echo THEME_URI ?>/assets/images/expand/youndkid.jpg') ;">
                  <div class="p-6 max-w-[700px] mx-auto relative">
                    <h2 class="2xl:text-fs34 lg:text-[2.25vw] text-fs28 text-white font-bold font-bkk">เด็กเล็ก</h2>
                    <div class="relative expand-desc opacity-0 transition-opacity duration-400">
                      <div class="absolute w-full whitespace-nowrap">
                        <p
                           class="xl:text-fs20 lg:text-fs14 text-fs16 text-white font-normal font-anuphan leading-snug mt-3">
                          เรียนรู้ผ่านการเล่น <br>เสริมพัฒนาการรอบด้าน</p>
                        <!-- <a href="#!"
                           class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!">อ่านรายละเอียด</a> -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="expand-item group  flex-1 lg:transition-all lg:duration-600 ">
                <div data-aos="fade-in"
                     class="expand-item-inner xl:min-h-[520px] lg:min-h-[380px] sm:min-h-[280px] min-h-[200px] bg-center lg:bg-size-[auto_100%] bg-size-[100%_auto] bg-no-repeat h-full"
                     style="background-image:url('<?php echo THEME_URI ?>/assets/images/expand/kid.jpg') ;">
                  <div class="p-6 max-w-[700px] mx-auto relative">
                    <h2 class="2xl:text-fs34 lg:text-[2.25vw] text-fs28 text-white font-bold font-bkk">วัยเรียน</h2>
                    <div class="relative expand-desc opacity-0 transition-opacity duration-400">
                      <div class="absolute w-full whitespace-nowrap">
                        <p
                           class="xl:text-fs20 lg:text-fs14 text-fs16 text-white font-normal font-anuphan leading-snug mt-3">
                          เปิดโลกการเรียนรู้ <br>พัฒนาทักษะอนาคตด</p>
                        <!-- <a href="#!"
                           class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!">อ่านรายละเอียด</a> -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="expand-item group  flex-1 lg:transition-all lg:duration-600 ">
                <div data-aos="fade-in"
                     class="expand-item-inner xl:min-h-[520px] lg:min-h-[380px] sm:min-h-[280px] min-h-[200px] bg-center lg:bg-size-[auto_100%] bg-size-[100%_auto] bg-no-repeat h-full"
                     style="background-image:url('<?php echo THEME_URI ?>/assets/images/expand/adult.jpg') ;">
                  <div class="p-6 max-w-[700px] mx-auto relative">
                    <h2 class="2xl:text-fs34 lg:text-[2.25vw] text-fs28 text-white font-bold font-bkk">วัยทำงาน</h2>
                    <div class="relative expand-desc opacity-0 transition-opacity duration-400">
                      <div class="absolute w-full whitespace-nowrap">
                        <p
                           class="xl:text-fs20 lg:text-fs14 text-fs16 text-white font-normal font-anuphan leading-snug mt-3">
                          เพิ่มทักษะใหม่ พร้อมปรับตัว<br>ทุกการเปลี่ยนแปลง</p>
                        <!-- <a href="#!"
                           class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!">อ่านรายละเอียด</a> -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="expand-item group  flex-1 lg:transition-all lg:duration-600 ">
                <div data-aos="fade-in"
                     class="expand-item-inner xl:min-h-[520px] lg:min-h-[380px] sm:min-h-[280px] min-h-[200px] bg-center lg:bg-size-[auto_100%] bg-size-[100%_auto] bg-no-repeat h-full"
                     style="background-image:url('<?php echo THEME_URI ?>/assets/images/expand/old.jpg') ;">
                  <div class="p-6 max-w-[700px] mx-auto relative">
                    <h2 class="2xl:text-fs34 lg:text-[2.25vw] text-fs28 text-white font-bold font-bkk">สูงอายุ</h2>
                    <div class="relative expand-desc opacity-0 transition-opacity duration-400">
                      <div class="absolute w-full whitespace-nowrap">
                        <p
                           class="xl:text-fs20 lg:text-fs14 text-fs16 text-white font-normal font-anuphan leading-snug mt-3">
                          เรียนรู้อย่างมีคุณค่า <br>ใช้ชีวิตอย่างมีความหมาย</p>
                        <!-- <a href="#!"
                           class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!">อ่านรายละเอียด</a> -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:hidden block">
            <div class="stack_wrapper">
              <ul class="stack_items">
                <li class="stack_section expand-item-inner bg-cover bg-no-repeat bg-center"
                    style="background-image:url('<?php echo THEME_URI ?>/assets/images/expand/baby.jpg');">
                  <div class="p-6 max-lg:pl-16 max-md:pl-10 max-sm:pl-4 text-white relative">
                    <h2 class="text-fs28 font-bold font-bkk">เด็กอ่อน</h2>
                    <div class="mt-2 desc">
                      <p>เมืองที่สร้างรากฐาน<br>
                        การดูแลเด็ก ตั้งแต่แรกเกิด</p>
                      <!-- <a href="#!"
                         class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!">อ่านรายละเอียด</a> -->
                    </div>
                  </div>
                </li>
                <li class="stack_section expand-item-inner bg-cover bg-no-repeat bg-center"
                    style="background-image:url('<?php echo THEME_URI ?>/assets/images/expand/youndkid.jpg');">
                  <div class="p-6 max-lg:pl-16 max-md:pl-10 max-sm:pl-4 text-white relative">
                    <h2 class="text-fs28 font-bold font-bkk">เด็กเล็ก</h2>
                    <div class="mt-2 desc">
                      <p>เรียนรู้ผ่านการเล่น <br>เสริมพัฒนาการรอบด้าน</p>
                      <!-- <a href="#!"
                         class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!">อ่านรายละเอียด</a> -->
                    </div>
                  </div>
                </li>
                <li class="stack_section expand-item-inner bg-cover bg-no-repeat bg-center"
                    style="background-image:url('<?php echo THEME_URI ?>/assets/images/expand/kid.jpg');">
                  <div class="p-6 max-lg:pl-16 max-md:pl-10 max-sm:pl-4 text-white relative">
                    <h2 class="text-fs28 font-bold font-bkk">วัยเรียน</h2>
                    <div class="mt-2 desc">
                      <p>เปิดโลกการเรียนรู้ <br>พัฒนาทักษะอนาคต</p>
                      <!-- <a href="#!"
                         class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!">อ่านรายละเอียด</a> -->
                    </div>
                  </div>
                </li>
                <li class="stack_section expand-item-inner bg-cover bg-no-repeat bg-center"
                    style="background-image:url('<?php echo THEME_URI ?>/assets/images/expand/adult.jpg');">
                  <div class="p-6 max-lg:pl-16 max-md:pl-10 max-sm:pl-4 text-white relative">
                    <h2 class="text-fs28 font-bold font-bkk">วัยทำงาน</h2>
                    <div class="mt-2 desc">
                      <p>เพิ่มทักษะใหม่ พร้อมปรับตัว<br>ทุกการเปลี่ยนแปลง</p>
                      <!-- <a href="#!"
                         class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!">อ่านรายละเอียด</a> -->
                    </div>
                  </div>
                </li>
                <li class="stack_section expand-item-inner bg-cover bg-no-repeat bg-center"
                    style="background-image:url('<?php echo THEME_URI ?>/assets/images/expand/old.jpg');">
                  <div class="p-6 max-lg:pl-16 max-md:pl-10 max-sm:pl-4 text-white relative">
                    <h2 class="text-fs28 font-bold font-bkk">สูงอายุ</h2>
                    <div class="mt-2 desc">
                      <p>เรียนรู้อย่างมีคุณค่า <br>ใช้ชีวิตอย่างมีความหมาย</p>
                      <!-- <a href="#!"
                         class="btn-link-v3 mt-4 max-xl:text-fs12! max-lg:text-fs18!">อ่านรายละเอียด</a> -->
                    </div>
                  </div>
                </li>
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

                    <div class="container  sec-highlight">
                        <div class="swiper xl:overflow-hidden! overflow-visible!">
                            <div class="swiper-wrapper">
                                                    
                                <div class="swiper-slide lg:w-auto!">
                                    <a href="<?php echo site_url('/') ?>course_provider/โรงเรียนฝึกอาชีพ" class="card-highlight lg:w-[400px]" size="medium">
                                        <div>
                                        <img
                                            src="<?php echo THEME_URI ?>/assets/images/highlight/card2.png"
                                            alt="คอร์สโรงเรียนฝึกอาชีพ เปิดรับสมัครแล้ว สำหรับ ปี 2569"
                                        >
                                        </div>

                                        <div class="txt">
                                        <div class="mt-auto">
                                            <!-- SEO title (บรรทัดเดียว ซ่อน) -->
                                            <h3 class="sr-only">คอร์สโรงเรียนฝึกอาชีพ เปิดรับสมัครแล้ว สำหรับ ปี 2569</h3>
                                        </div>
                                        </div>
                                    </a>
                                </div>

                                <div class="swiper-slide lg:w-auto!">
                                    <a href="https://learning.bangkok.go.th/ecdplan/" class="card-highlight lg:w-[400px]" size="medium">
                                        <div>
                                        <img
                                            src="<?php echo THEME_URI ?>/assets/images/highlight/card1.png"
                                            alt="แผนการจัดประสบการณ์การเรียนรู้ของเด็กก่อนวัยเรียน สำหรับเด็กอายุ 2-6 ปี"
                                        >
                                        </div>

                                        <div class="txt">
                                        <div class="mt-auto">
                                            <!-- SEO title (บรรทัดเดียว ซ่อน) -->
                                            <h3 class="sr-only">แผนการจัดประสบการณ์การเรียนรู้ของเด็กก่อนวัยเรียน สำหรับเด็กอายุ 2-6 ปี</h3>
                                        </div>
                                        </div>
                                    </a>
                                </div>

                                <div class="swiper-slide lg:w-auto!">
                                    <a href="<?php echo site_url('/') ?>tag/readyforwork" class="card-highlight lg:w-[400px]" size="medium">
                                        <div>
                                        <img
                                            src="<?php echo THEME_URI ?>/assets/images/highlight/playlist2.jpg"
                                            alt="เรียนจบปุ๊บ รับงานปั๊บ เรียนจบพร้อมต่อยอดงานทันที"
                                        >
                                        </div>

                                        <div class="txt">
                                        <div class="mt-auto">
                                            <!-- SEO title (บรรทัดเดียว ซ่อน) -->
                                            <h3 class="sr-only">เรียนจบปุ๊บ รับงานปั๊บ เรียนจบพร้อมต่อยอดงานทันที</h3>
                                        </div>
                                        </div>
                                    </a>
                                </div>


                                <div class="swiper-slide lg:w-auto!">
                                    <a href="<?php echo site_url('/') ?>tag/weekend" class="card-highlight lg:w-[400px]" size="medium">
                                        <div>
                                        <img
                                            src="<?php echo THEME_URI ?>/assets/images/highlight/playlist3.jpg"
                                            alt="เรียนวันหยุด เสาร์–อาทิตย์ เรียนได้ ไม่กระทบเวลางาน"
                                        >
                                        </div>

                                        <div class="txt">
                                        <div class="mt-auto">
                                            <!-- SEO title (บรรทัดเดียว ซ่อน) -->
                                            <h3 class="sr-only">เรียนวันหยุด เสาร์–อาทิตย์ เรียนได้ ไม่กระทบเวลางาน</h3>
                                        </div>
                                        </div>
                                    </a>
                                </div>

                                <div class="swiper-slide lg:w-auto!">
                                    <a href="<?php echo site_url('/') ?>course_category/งานช่างไฟฟ้า-อิเล็กทรอน/" class="card-highlight lg:w-[400px]" size="medium">
                                        <div>
                                        <img
                                            src="<?php echo THEME_URI ?>/assets/images/highlight/playlist4.jpg"
                                            alt="สายช่าง เสริมทักษะอาชีพ สร้างรายได้จริง"
                                        >
                                        </div>

                                        <div class="txt">
                                        <div class="mt-auto">
                                            <!-- SEO title (บรรทัดเดียว ซ่อน) -->
                                            <h3 class="sr-only">สายช่าง เสริมทักษะอาชีพ สร้างรายได้จริง</h3>
                                        </div>
                                        </div>
                                    </a>
                                </div>

                                <div class="swiper-slide lg:w-auto!">
                                    <a href="<?php echo site_url('/') ?>course_provider/microsoft/" class="card-highlight lg:w-[400px]" size="medium">
                                        <div>
                                        <img
                                            src="<?php echo THEME_URI ?>/assets/images/highlight/playlist5.jpg"
                                            alt="เรียน AI ไม่ตกเทรนด์ อัปสกิลเทคโนโลยี ทันโลกดิจิทัล"
                                        >
                                        </div>

                                        <div class="txt">
                                        <div class="mt-auto">
                                            <!-- SEO title (บรรทัดเดียว ซ่อน) -->
                                            <h3 class="sr-only">เรียน AI ไม่ตกเทรนด์ อัปสกิลเทคโนโลยี ทันโลกดิจิทัล</h3>
                                        </div>
                                        </div>
                                    </a>
                                </div>


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
                <div class="swiper swiper-activity-body overflow-visible! md:pb-12! pb-4!">
                  <div class="swiper-wrapper">
                    <div class="swiper-slide h-auto!">
                      <div class="lg:py-6 py-4 xl:rounded-3xl lg:rounded-20 rounded-2xl sticky lg:top-3 md:top-6 top-0 z-1 flex items-center justify-center text-white xl:text-fs22 lg:text-fs16 md:text-fs14 text-fs16 text-center font-semibold leading-tight shadow-xl"
                           style="background-color: #00744B;">
                        สนับสนุนการ<br>เรียนรู้ตลอดชีวิต</div>
                          <div class="box-activity">
                            <div class="item" style="background-color:#D8FFF1;">
                              พัฒนาหลักสูตรฝึกอาชีพให้ทันสมัย
                            </div>

                            <div class="item" style="background-color:#D8FFF1;">
                              พัฒนาโครงการ Next Learn เพื่อ upskill-reskill คนกรุงเทพฯ
                            </div>

                            <div class="item" style="background-color:#D8FFF1;">
                              พัฒนาแหล่งเรียนรู้นอกห้องเรียนทั่วกรุงเทพฯ
                            </div>

                            <div class="item" style="background-color:#D8FFF1;" data-modal-id="policymodal1">
                              ร่วมกับองค์กรภาคีเครือข่าย จัดเทศกาลการอ่านและการเรียนรู้ (เดือนมีนาคม) ส่งเสริมการเรียนรู้สำหรับทุกคนในครอบครัว
                            </div>

                            <div class="item" style="background-color:#D8FFF1;">
                              พัฒนาโครงการ 'Learn and Earn' เพิ่มทางเลือกการเรียนรู้ที่ยืดหยุ่นสำหรับเด็กที่หลุดจากระบบการศึกษา ด้วยการฝึกอาชีพ และเทียบโอนวุฒิการศึกษา
                            </div>

                            <div class="item" style="background-color:#D8FFF1;">
                              ส่งเสริมการจัดกิจกรรมที่โรงเรียนผู้สูงอายุ และชมรมผู้สูงอายุทั่วกรุงเทพฯ มากกว่า
                              <span>400 แห่ง</span>
                            </div>
                          </div>



                    </div>
                    <div class="swiper-slide h-auto!">
                      <div class="lg:py-6 py-4 xl:rounded-3xl lg:rounded-20 rounded-2xl sticky lg:top-3 md:top-6 top-0 z-1 flex items-center justify-center text-white xl:text-fs22 lg:text-fs16 md:text-fs14 text-fs16 text-center font-semibold leading-tight shadow-xl"
                           style="background-color: #0972CE;">
                        พัฒนาคุณภาพ<br>การศึกษา</div>
                          <div class="box-activity">
                            <div class="item" style="background-color:#DBEEFF;">
                              ปรับโครงสร้างการจัดการเรียนการสอน จากการวัด 'ความรู้' เป็นการวัด 'สมรรถนะ'
                            </div>

                            <div class="item" style="background-color:#DBEEFF;">
                              หลักสูตรโรงเรียนเน้นทักษะแห่งอนาคต เพิ่ม STEAM Process และ Sustainable City Curriculum
                            </div>

                            <div class="item" style="background-color:#DBEEFF;">
                             ใช้ AI ช่วยพัฒนาทักษะภาษาอังกฤษ นักเรียน ป.4-6
                            </div>

                            <div class="item" style="background-color:#DBEEFF;">
                              เพิ่มจำนวนโรงเรียน 2 ภาษา
                            </div>

                            <div class="item" style="background-color:#DBEEFF;">
                              พัฒนาครูวิชาภาษาอังกฤษ
                            </div>

                            <div class="item" style="background-color:#DBEEFF;">
                             โรงเรียนจัดกิจกรรมเปิดชั้นเรียนสาธารณะ (Public Open Class) เพื่อให้ครูต่างโรงเรียนได้แลกเปลี่ยนเรียนรู้ร่วมกัน (SLC model)
                            </div>

                            <div class="item" style="background-color:#DBEEFF;">
                              โครงการ BMA Future School พัฒนาสมรรถนะผู้บริหารโรงเรียนสังกัด กทม.ทุกโรงเรียน
                            </div>

                            <div class="item" style="background-color:#DBEEFF;">
                             ปรับปรุงห้องแล็บคอมพิวเตอร์ในรอบ 10 ปี ทุกโรงเรียนมีห้องคอมฯใหม่ อย่างน้อย 1 ห้อง งบประมาณ <span>776 ล้านบาท</span>
                            </div>

                            <div class="item" style="background-color:#DBEEFF;">
                              เพิ่มจุดกระจายสัญญาณ ​Wi-Fi ในโรงเรียน ครอบคลุมทุกโรงเรียน
                            </div>

                            <div class="item" style="background-color:#DBEEFF;" >
                              Digital Classroom เด็กทุกคนมี Laptop แบบ 1:1 งบประมาณ <span>481 ล้านบาท</span>
                            </div>

                            <div class="item" style="background-color:#DBEEFF;">
                             ปรับปรุงกายภาพโรงเรียน งบประมาณมากกว่า <span>1,300 ล้านบาท</span>
                            </div>

                            <div class="item" style="background-color:#DBEEFF;">
                              ร่วมกับการศึกษาพิเศษส่วนกลาง จัดการศึกษาให้กับเด็กพิการเพิ่มเติมในพื้นที่โรงเรียน กทม.
                            </div>
                          </div>

                    </div>
                    <div class="swiper-slide h-auto!">
                      <div class="lg:py-6 py-4 xl:rounded-3xl lg:rounded-20 rounded-2xl sticky lg:top-3 md:top-6 top-0 z-1 flex items-center justify-center text-white xl:text-fs22 lg:text-fs16 md:text-fs14 text-fs16 text-center font-semibold leading-tight shadow-xl"
                           style="background-color: #EA3DA9;">
                        โรงเรียน<br>เป็นพื้นที่ปลอดภัย</div>
                          <div class="box-activity">
                            <div class="item" style="background-color:#FFD7F0;">
                              สนับสนุนชุดนักเรียน อุปกรณ์ อาหาร ประกันอุบัติเหตุ ตรวจสุขภาพ ผ้าอนามัยฟรี ให้กับนักเรียนทุกคน
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              ไม่บังคับทรงผม ไม่บังคับใส่ชุดลูกเสือ และใส่ไปรเวท 1 วันต่อสัปดาห์
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              มีนโยบายส่งเสริมและคุ้มครองสิทธิเด็กในโรงเรียน
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                             เพิ่มการมีส่วนร่วมของนักเรียน ตั้งคณะกรรมการนักเรียน และสภานักเรียนเป็นครั้งแรก
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              กิจกรรม After School ส่งเสริมพื้นที่ปลอดภัยหลังเลิกเรียน ลดภาระผู้ปกครอง <span>387 โรงเรียน</span>
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              กิจกรรม โรงเรียนวันเสาร์ (Saturday School) ส่งเสริมการเรียนรู้นอกเวลาตามความสนใจ <span>50 โรงเรียน</span>
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              ลดภาระเอกสารของครู กทม.จ้างเจ้าหน้าที่ธุรการเพิ่มใน <span>371 โรงเรียน</span>
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              ลดภาระครู โดยนำระบบดิจิทัลมาใช้ ลดความซ้ำซ้อนของเอกสาร ลดเอกสารได้เกิน <span>45%</span>
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              ลดภาระการเข้าเวรกลางคืนของครู โดยการจ้าง รปภ.เวรกลางคืนเพิ่มทุกโรงเรียน
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              เพิ่มสวัสดิการครู เช่น บ้านพักครู
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                             ปรับแนวทางการเลื่อนวิทยฐานะครูให้ทันสมัยขึ้น
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              ปรับแนวทางการสรรหาครู และกระบวนการคัดเลือกครูใหม่
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                             โครงการ 'ก่อการครู กทม.' เพื่อให้ครูได้กลับมาเรียนรู้ภายใน และดูแลตัวเอง
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              ส่งเสริมจิตวิทยาเชิงบวก ช่วยกันดูแลเด็กทั้งที่บ้านและโรงเรียน
                            </div>

                            <div class="item" style="background-color:#FFD7F0;">
                              ใช้กลไกเชิงรุกดูแลเด็กนอกระบบ เด็กเสี่ยงออกนอกระบบการศึกษา เด็กออกนอกระบบลดลง มากกว่า <span>42,000คน</span>
                            </div>
                          </div>

                    </div>
                    <div class="swiper-slide h-auto!">
                      <div class="lg:py-6 py-4 xl:rounded-3xl lg:rounded-20 rounded-2xl sticky lg:top-3 md:top-6 top-0 z-1 flex items-center justify-center text-white xl:text-fs22 lg:text-fs16 md:text-fs14 text-fs16 text-center font-semibold leading-tight shadow-xl"
                           style="background-color: #FEB449;">
                        ยกระดับ<br>การดูแลเด็กเล็ก</div>
                          <div class="box-activity">
                            <!-- <div class="item" style="background-color:#FFECD1;" data-modal-id="policymodal3">
                              Bookstart<br>
                              แจกนิทานเด็กเล็ก
                              <i class="icon icon-plus"></i>
                            </div> -->

                            <div class="item" style="background-color:#FFECD1;">
                              โครงการ Bookstart แจกหนังสือนิทาน 3 เล่ม ให้เด็กแรกเกิด <span>15,000 ชุด</span>
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              ศูนย์บริการสาธารณสุข ทดลองรับดูแลเด็กอ่อนเร็วขึ้น ตั้งแต่อายุ 3 เดือน
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              โรงเรียนสังกัดกทม.รับเด็กอนุบาลเร็วขึ้นตั้งแต่ 3 ขวบ
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              ริเริ่มโรงเรียนอนุบาลต้นแบบ 7 โรงเรียน ก้าวสู่อนุบาลระดับโลก
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              พัฒนาหลักสูตรระดับอนุบาลเป็น Play-based learning ไม่เร่งอ่านเขียน
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                             ประเมินผลพัฒนาการตามวัยของเด็กอนุบาลด้วย DSPM ไม่ใช่การสอบ
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              ริเริ่มกิจกรรม 'ห้องเรียนพ่อแม่' สานสัมพันธ์เด็ก-ผู้ปกครอง-ครู ระดับอนุบาล <span>100 โรงเรียน</span>
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              ร่างข้อบัญญัติศูนย์เด็กเล็กชุมชนใหม่ ขยายขอบเขตการดูแลเด็กเล็ก ครอบคลุมมากขึ้น
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              ศูนย์เด็กเล็กชุมชนต้นแบบ เน้นด้าน EF, High Scope, การเล่นอิสระ และการอ่าน
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              อบรมเพิ่มทักษะครู และอาสาพี่เลี้ยงเด็ก
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                             จัดทำแผนการสอนกลางสำหรับศูนย์เด็กเล็กเป็นครั้งแรก ใช้ร่วมกันทั้ง 259 ศูนย์
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              ปรับเงินเดือนอาสาพี่เลี้ยงเด็กที่ศูนย์เด็กเล็กครั้งแรกในรอบ 12 ปี
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              เพิ่มงบประมาณสนับสนุนรายหัว ค่าอาหาร นม และอุปกรณ์การเรียนให้ศูนย์เด็กเล็ก
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              ปรับปรุงกายภาพศูนย์เด็กเล็กให้ได้มาตรฐาน โดย กทม. และหน่วยงานภาคเอกชน ปรับปรุงแล้ว 131 ศูนย์
                            </div>

                            <div class="item" style="background-color:#FFECD1;">
                              ปรับปรุง 'ห้องเรียนปลอดฝุ่น' ระดับชั้นอนุบาลของทุกโรงเรียน และในศูนย์เด็กเล็ก โดยร่วมกับกองทุน สปสช.
                            </div>
                          </div>

                    </div>
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

<div data-modal-content="modal-detail-bannercard1" class="modal lg:items-center items-start lg:p-6 p-0">
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

                    <img class="w-[100px] mb-4" src="<?php echo THEME_URI ?>/assets/images/homepage/unesco-logo.png" alt="">
                    <h2 class="md:text-fs40 text-fs30 font-bold sm:mb-5 mb-4 leading-normal">
                      Bangkok Learning City: Learning for Life Opportunities for All
                    </h2>

                    <p class="text-fs16">
                      กรุงเทพมหานครขับเคลื่อน และส่งเสริมการเรียนรู้ตลอดชีวิต สำหรับคนทุกช่วงวัย
                      ด้วยการขับเคลื่อนนโยบายเมืองแห่งการเรียนรู้ 4 ด้าน
                    </p>

                    <ol class="text-fs16 list-decimal pl-6 mt-4 space-y-1">
                      <li>ยกระดับการดูแลเด็กเล็ก</li>
                      <li>พัฒนาคุณภาพการศึกษา</li>
                      <li>ทำให้โรงเรียนเป็นพื้นที่ปลอดภัย</li>
                      <li>ส่งเสริมพื้นที่เรียนรู้สำหรับทุกช่วงวัย</li>
                    </ol>

                    <p class="text-fs16 mt-4">
                      ในปี 2567 กรุงเทพมหานครได้รับเลือกให้เป็นสมาชิกเครือข่ายระดับโลกด้านเมืองแห่งการเรียนรู้ของยูเนสโก
                      (The UNESCO Global Network of Learning Cities: GNLC)
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                      <a
                        href="https://www.uil.unesco.org/en/learning-cities/bangkok"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center rounded-full
                          border border-black text-black
                          px-6 py-2.5 text-fs16 font-semibold
                          hover:bg-black hover:text-white
                          transition-colors duration-200"
                      >
                        เรียนรู้เพิ่มเติม
                      </a>


                      <a
                        href="https://www.eef.or.th/infographic-learning-city/#:~:text=Learning%20City%20%E0%B8%AB%E0%B8%A1%E0%B8%B2%E0%B8%A2%E0%B8%96%E0%B8%B6%E0%B8%87%20%E0%B9%80%E0%B8%A1%E0%B8%B7%E0%B8%AD%E0%B8%87,%E0%B9%80%E0%B8%A3%E0%B8%B5%E0%B8%A2%E0%B8%99%E0%B8%A3%E0%B8%B9%E0%B9%89%E0%B8%95%E0%B8%A5%E0%B8%AD%E0%B8%94%E0%B8%8A%E0%B9%88%E0%B8%A7%E0%B8%87%E0%B8%8A%E0%B8%B5%E0%B8%A7%E0%B8%B4%E0%B8%95"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center rounded-full
                          border border-black text-black
                          px-6 py-2.5 text-fs16 font-semibold
                          hover:bg-black hover:text-white
                          transition-colors duration-200"
                      >
                        Learning City คืออะไร ?
                      </a>
                    </div>


                  </div>


                </div>
            </div>
        </div>
    </div>
</div>

<div data-modal-content="modal-detail-bannercard2" class="modal lg:items-center items-start lg:p-6 p-0">
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

                    <img class="w-[120px] mb-4" src="<?php echo THEME_URI ?>/assets/images/homepage/bkk-active-app.png" alt="">

                    <h2 class="md:text-fs40 text-fs30 font-bold sm:mb-5 mb-4 leading-normal">
                      BKK Active
                    </h2>

                    <p class="text-fs16">
                      <strong>BKK Active</strong> คือแอปพลิเคชันที่รวมบริการด้านกีฬา สุขภาพ
                      และการเรียนรู้ของกรุงเทพมหานครไว้ในที่เดียว
                      เพื่ออำนวยความสะดวกให้ประชาชนทุกช่วงวัยสามารถเข้าถึงกิจกรรมนันทนาการได้อย่างง่ายดาย
                      สะดวก และเป็นระบบ
                    </p>

                    <p class="text-fs16 mt-3">
                      ผู้ใช้งานสามารถจองคิวสนามกีฬา สระว่ายน้ำ
                      พื้นที่เรียนรู้ รวมถึงกิจกรรมต่าง ๆ ได้ผ่านแอปเดียว
                      ตั้งแต่การสมัครสมาชิก การชำระค่าสมาชิก
                      ไปจนถึงการจองใช้งานจริง
                    </p>

                    <p class="text-fs16 mt-3 font-semibold">
                      สมัครสมาชิก – ชำระค่าสมาชิก – จองได้เลย ครบจบในแอปเดียว
                    </p>

                    <p class="text-fs16 mt-4 text-black/80">
                      <strong>หมายเหตุ:</strong>
                      แอป BKK Active ใช้ชื่อเดิมว่า CSTD Smart Member
                      สมาชิกเดิมสามารถอัปเดตแอปได้ทันที
                      โดยไม่จำเป็นต้องดาวน์โหลดใหม่
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                      <a
                        href="https://apps.apple.com/th/app/bkk-active/id1642670243?l=th"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center rounded-full
                          border border-black text-black
                          px-6 py-2.5 text-fs16 font-semibold
                          hover:bg-black hover:text-white
                          transition-colors duration-200"
                      >
                        ดาวน์โหลดสำหรับ iOS
                      </a>

                      <a
                        href="https://play.google.com/store/apps/details?id=go.th.bangkok.cstd"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center rounded-full
                          border border-black text-black
                          px-6 py-2.5 text-fs16 font-semibold
                          hover:bg-black hover:text-white
                          transition-colors duration-200"
                      >
                        ดาวน์โหลดสำหรับ Android
                      </a>
                    </div>
                  </div>



                </div>
            </div>
        </div>
    </div>
</div>


<div data-modal-content="policymodal1" class="modal items-center p-4">
  <div class="overlay-modal"></div>

  <div class="card-modal w-full max-w-[520px] rounded-2xl shadow-xl">
    
    <!-- close button : ใช้ของเดิม -->
    <div class="absolute top-4 right-4 z-20">
      <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
        <div class="icon-close"></div>
      </button>
    </div>

    <div class="modal-content relative z-10 p-6">
      <div class="max-w-[420px] mx-auto text-center">
        <p class="text-fs16 leading-relaxed">
          <strong>Learn &amp; Earn – เรียนไป ทำงานไป</strong><br>
          เพิ่มทางเลือกในการเรียนรู้ที่ยืดหยุ่นสำหรับเด็กที่หลุดนอกระบบการศึกษา
          ด้วยการฝึกอาชีพ และการเทียบโอนวุฒิการศึกษา
        </p>
      </div>
    </div>

  </div>
</div>

<div data-modal-content="policymodal2" class="modal items-center p-4">
  <div class="overlay-modal"></div>

  <div class="card-modal w-full max-w-[520px] rounded-2xl shadow-xl">
    
    <!-- close button : ใช้ของเดิม -->
    <div class="absolute top-4 right-4 z-20">
      <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
        <div class="icon-close"></div>
      </button>
    </div>

    <div class="modal-content relative z-10 p-6">
      <div class="max-w-[420px] mx-auto text-center">
        <p class="text-fs16 leading-relaxed">
          <strong>เด็กทุกคนมี Laptop แบบ 1:1</strong><br>
          Digital Classroom เด็กทุกคนมี Laptop แบบ 1:1 งบประมาณ 481 ล้านบาท
        </p>
      </div>
    </div>

  </div>
</div>


<div data-modal-content="policymodal3" class="modal items-center p-4">
  <div class="overlay-modal"></div>

  <div class="card-modal w-full max-w-[520px] rounded-2xl shadow-xl">
    
    <!-- close button : ใช้ของเดิม -->
    <div class="absolute top-4 right-4 z-20">
      <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
        <div class="icon-close"></div>
      </button>
    </div>

    <div class="modal-content relative z-10 p-6">
      <div class="max-w-[420px] mx-auto text-center">
        <p class="text-fs16 leading-relaxed">
          <strong>โครงการ Bookstart</strong><br>
          แจกหนังสือนิทาน 3 เล่ม ให้เด็กแรกเกิด 15,000 ชุด
        </p>
      </div>
    </div>

  </div>
</div>




<?php get_footer(); ?>


