<?php get_header(); ?>
<div class="app-layout">
    <?php get_template_part('template-parts/header/site-header'); ?>
    <main>
        <button class="btn-category-floating" data-modal-id="modal-category">
            <img src="<?php echo THEME_URI ?>/assets/images/btn-menu-category.svg" alt="">
        </button>
        <button class="btn-searh-floating" data-modal-id="modal-search">
            <div class="icon-search"></div>
        </button>

        <div class="container">
            <div class="layout-sidebar">
                <?php get_template_part('template-parts/components/aside'); ?>
                <section class="min-w-px">


                    <div class="box-chart h-auto! sm:rounded-3xl rounded-2xl overflow-hidden">

                        <div class="grid lg:grid-cols-2 grid-cols-1">
                            <div
                                class="lg:w-[80%] sm:w-[500px] w-full lg:ml-0 mx-auto pl-8 lg:p-4 p-8 flex items-center justify-center lg:mb-[-5%]">
                            <img src="<?php echo THEME_URI ?>/assets/images/logo-next-text.svg"
                                alt="" class="w-[300px] md:w-auto drop-shadow-xl">
                            </div>
                            <div class="lg:row-span-2 relative">
                            <div class="flex items-center justify-end max-sm:flex-col">
                                <div
                                    class="flex-1 h-auto aspect-square overflow-visible lg:absolute relative lg:w-[85%] lg:left-[-35%] w-full max-lg:m-[-10%] max-sm:m-[-20%]">
                                <div
                                    class="absolute w-[55%] h-[55%] top-[22.5%] left-[22.5%] border-4 border-white rounded-full bg-[#007D51]">
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


                                <div class="p-4 lg:flex-1 lg:w-auto sm:w-1/2 w-[90%] max-sm:mx-auto max-sm:mt-4">
                                <div
                                    class="lg:w-[60%] lg:max-w-[300px] w-full ml-auto bg-[#005A3A] flex flex-col rounded-20 sm:p-4 p-6 text-center 2xl:text-fs32 xl:text-[2.35vw] lg:text-[2.75vw] sm:text-[4vw] text-[7.73vw] 2xl:gap-4 gap-[1vw]">

                                    <!-- TOTAL -->
                                    <div class="py-4 max-sm:pt-2">
                                    <h2 class="text-white leading-tight font-medium">เรียนไปแล้ว</h2>
                                    <h2 class="text-white leading-tight font-bold">
                                        <span id="total-hours" class="counter-hours">0</span> ชั่วโมง
                                    </h2>
                                    </div>

                                    <!-- BARS -->
                                    <div class="flex-1 max-sm:p-2 flex flex-col justify-center gap-3 pb-4">

                                    <!-- JOB -->
                                    <div class="flex items-start gap-2">
                                        <div
                                        class="2xl:w-11 lg:w-[3vw] sm:w-[4.2vw] w-[10.5vw] aspect-square flex items-center justify-center 2xl:rounded-lg lg:rounded-[0.6vw] sm:rounded-[0.8vw] rounded-[2vw] sm:p-[2%] p-[3%]"
                                        style="background:#F7DD52">
                                        <img src="<?php echo THEME_URI ?>/assets/images/icons/icon_occupation.svg" alt="">
                                        </div>

                                        <div class="flex-1 flex flex-col justify-between 2xl:gap-1 lg:gap-[0.5vw] sm:gap-[0.8vw] gap-[1.5vw]">
                                        <div class="bg-white/20 2xl:h-4 lg:h-[1vw] sm:h-[1.55vw] h-[3.65vw] rounded-full w-full overflow-hidden">
                                            <span
                                            class="progress block h-full rounded-full"
                                            data-theme="job"
                                            style="background:#F7DD52; width:0%"></span>
                                        </div>

                                        <div class="flex items-center justify-between text-white font-medium 2xl:text-fs14 lg:text-[1vw] sm:text-[2vw] text-[3.4vw]">
                                            <div>อาชีพ</div>
                                            <div><span class="counter-hours" data-theme="job">0</span> ชั่วโมง</div>
                                        </div>
                                        </div>
                                    </div>

                                    <!-- DIGITAL -->
                                    <div class="flex items-start gap-2">
                                        <div
                                        class="2xl:w-11 lg:w-[3vw] sm:w-[4.2vw] w-[10.5vw] aspect-square flex items-center justify-center 2xl:rounded-lg lg:rounded-[0.6vw] sm:rounded-[0.8vw] rounded-[2vw] sm:p-[2%] p-[3%]"
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
                                        class="2xl:w-11 lg:w-[3vw] sm:w-[4.2vw] w-[10.5vw] aspect-square flex items-center justify-center 2xl:rounded-lg lg:rounded-[0.6vw] sm:rounded-[0.8vw] rounded-[2vw] sm:p-[2%] p-[3%]"
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
                                </div>
                                </div>



                            </div>
                            </div>
                            <div class="lg:w-[80%] w-full flex items-end">
                            <img src="<?php echo THEME_URI ?>/assets/images/img-person-group.png"
                                alt="">
                            </div>
                        </div>

                    </div>   


                    <div class="xl:py-12 py-8 mt-6 sec-highlight">
                        <h2 class="text-heading">ไฮไลท์</h2>
                        <div class="swiper xl:overflow-hidden! overflow-visible!">
                            <div class="swiper-wrapper">
                                                    
                                <div class="swiper-slide lg:w-auto!">
                                    <a href="<?php echo site_url('/') ?>course_provider/โรงเรียนฝึกอาชีพ" class="card-highlight lg:w-[400px]" size="medium">
                                        <div>
                                        <img
                                            src="<?php echo THEME_URI ?>/assets/images/highlight/playlist1.jpg"
                                            alt="Up-skill อัพสกิลทักษะดิจิทัล"
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


                    <div class="xl:py-12 py-8 sec-category" data-aos="fade-in">
                        <h2 class="text-heading">หมวดหมู่</h2>
                        
                        <?php
                        set_query_var('cc_taxonomy', 'course_category');
                        set_query_var('cc_hide_empty', false); // หรือ true
                        set_query_var('cc_parent', 0);         // เฉพาะหมวดหลัก
                        set_query_var('cc_default_img', THEME_URI . '/assets/images/category/img01.png');

                        get_template_part('template-parts/course/category-swiper');
                        ?>
             



                    </div>


                    <?php
                    set_query_var('rc_count', 6); // อยากให้สุ่มกี่ใบ
                    get_template_part('template-parts/course/recommended-grid');
                    ?>

                    <div id="nearby-home-section" class="xl:py-12 py-8 sec-course" data-aos="fade-in">
                        <h2 class="text-heading">เรียนใกล้บ้านเลยล่ะ</h2>
                        <div id="nearby-home-grid" class="mt-6 grid xl:grid-cols-2 grid-cols-1 xl:gap-6 gap-4">
                          <?php for ($i = 0; $i < 6; $i++) : ?>
                            <div class="card-course flex flex-col h-full pointer-events-none lc-skeleton" aria-hidden="true">
                              <div class="card-content gap-10">
                                <div class="min-w-0 w-full">
                                  <div class="h-3 w-24 lc-sk-bar mb-3"></div>
                                  <div class="h-5 w-4/5 lc-sk-bar mb-3"></div>
                                  <div class="h-4 w-2/3 lc-sk-bar"></div>
                                </div>
                                <div class="img shrink-0 lc-sk-bar"></div>
                              </div>
                              <div class="card-footer mt-auto">
                                <div class="h-4 w-2/3 lc-sk-bar"></div>
                                <div class="h-4 w-16 lc-sk-bar"></div>
                              </div>
                            </div>
                          <?php endfor; ?>
                        </div>
                    </div>



                    <div class="xl:py-12 py-8 sec-category-other" data-aos="fade-in">
                        <h2 class="text-heading">คอร์สที่เหมาะสำหรับ...</h2>
                        <div class="-m-4 xl:overflow-hidden! overflow-visible!">
                            <div class="p-4">
                                <div class="swiper overflow-visible!">
                                    <div class="swiper-wrapper">
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>audience/ทุกวัย" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/allage.jpg" alt="">
                                                <div class="txt">
                                                    <p>ทุกวัย</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>audience/เด็กเล็ก" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/toddler.jpg" alt="">
                                                <div class="txt">
                                                    <p>เด็กเล็ก</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>audience/เด็กและเยาวชน" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/kid.jpg" alt="">
                                                <div class="txt">
                                                    <p>เด็กและเยาวชน</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>audience/ผู้ใหญ่" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/work.jpg" alt="">
                                                <div class="txt">
                                                    <p>ผู้ใหญ่</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>audience/ผู้สูงอายุ" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/old.jpg" alt="">
                                                <div class="txt">
                                                    <p>ผู้สูงอายุ</p>
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
                        </div>
                    </div>


                    <div class="xl:py-12 py-8 sec-category-other" data-aos="fade-in">
                        <h2 class="text-heading">คอร์สโดย</h2>
                        <div class="-m-4 xl:overflow-hidden! overflow-visible!">
                            <div class="p-4">
                                <div class="swiper overflow-visible!">
                                    <div class="swiper-wrapper">
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>course_provider/etda/" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/ETDA.jpg" alt="">
                                                <div class="txt">
                                                    <p>ETDA</p>
                                                </div>
                                            </a>
                                        </div>
                                         <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>tag/dsd/" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/dsd.jpg" alt="">
                                                <div class="txt">
                                                    <p>DSD Online Training</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>course_provider/hook/" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/hook.jpg" alt="">
                                                <div class="txt">
                                                    <p>Hook</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>course_provider/microsoft/" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/Microsoft.jpg" alt="">
                                                <div class="txt">
                                                    <p>Microsoft</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>course_provider/starfish-labz/" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/Starfish.jpg" alt="">
                                                <div class="txt">
                                                    <p>Starfish Labz</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>course_provider/ก่อร่างสร้างเด็ก" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/sharecare.jpg" alt="">
                                                <div class="txt">
                                                    <p>ก่อร่างสร้างเด็ก</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>course_provider/ศูนย์นันทนาการ" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/Recreation-Center.jpg" alt="">
                                                <div class="txt">
                                                    <p>ศูนย์นันทนาการ</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>course_provider/ศูนย์บริการผู้สูงอายุ" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/bangkok-2.jpg" alt="">
                                                <div class="txt">
                                                    <p>ศูนย์บริการผู้สูงอายุ</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>course_provider/ศูนย์ฝึกอาชีพกรุงเทพมห" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/bangkok.jpg" alt="">
                                                <div class="txt">
                                                    <p>ศูนย์ฝึกอาชีพ กทม.</p>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="swiper-slide">
                                            <a href="<?php echo site_url('/') ?>course_provider/โรงเรียนฝึกอาชีพ" class="card-category-other" >
                                                <img src="<?php echo THEME_URI ?>/assets/images/category-other/bangkok-2.jpg" alt="">
                                                <div class="txt">
                                                    <p>โรงเรียนฝึกอาชีพ</p>
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
                        </div>
                    </div>

                    <?php get_template_part('template-parts/footer/site-footer'); ?>
                </section>
            </div>
        </div>
    </main>
</div>

<?php get_template_part('template-parts/components/modal-course'); ?>
<?php get_template_part('template-parts/components/modal-category'); ?>
<?php get_template_part('template-parts/components/modal-search'); ?>

<style>
  @keyframes lcShimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
  }
  .lc-skeleton {
    position: relative;
    overflow: hidden;
  }
  .lc-skeleton::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,.55) 50%, rgba(255,255,255,0) 100%);
    background-size: 200% 100%;
    animation: lcShimmer 1.25s linear infinite;
    pointer-events: none;
  }
  .lc-sk-bar {
    background: #e5e7eb;
    border-radius: 8px;
  }
</style>

<script>
(() => {
  const LOCATION_CACHE_KEY = 'lc_user_location_v1';
  const sectionEl = document.getElementById('nearby-home-section');
  const gridEl = document.getElementById('nearby-home-grid');
  if (!sectionEl || !gridEl) return;

  function showMessage(msg) {
    sectionEl.classList.remove('hidden');
    gridEl.innerHTML = `<div class="col-span-full py-6 text-center text-fs16 opacity-70">${esc(msg)}</div>`;
  }

  function getCachedLocation() {
    try {
      const raw = localStorage.getItem(LOCATION_CACHE_KEY);
      if (!raw) return null;
      const data = JSON.parse(raw);
      const lat = Number(data && data.lat);
      const lng = Number(data && data.lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
      return { lat, lng };
    } catch (_) {
      return null;
    }
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function distanceText(km) {
    const n = Number(km);
    if (!Number.isFinite(n)) return '';
    return `${n.toFixed(n < 10 ? 1 : 0)} กม.`;
  }

  function renderSkeleton(count = 6) {
    const cards = Array.from({ length: count }, () => `
      <div class="card-course flex flex-col h-full pointer-events-none lc-skeleton" aria-hidden="true">
        <div class="card-content gap-10">
          <div class="min-w-0 w-full">
            <div class="h-3 w-24 lc-sk-bar mb-3"></div>
            <div class="h-5 w-4/5 lc-sk-bar mb-3"></div>
            <div class="h-4 w-2/3 lc-sk-bar"></div>
          </div>
          <div class="img shrink-0 lc-sk-bar"></div>
        </div>
        <div class="card-footer mt-auto">
          <div class="h-4 w-2/3 lc-sk-bar"></div>
          <div class="h-4 w-16 lc-sk-bar"></div>
        </div>
      </div>
    `).join('');
    gridEl.innerHTML = cards;
  }

  function injectNearbyIcons() {
    ['icon-calendar', 'icon-chartbar', 'icon-person'].forEach((iconClass) => {
      const sourceSvg = Array.from(document.querySelectorAll('.' + iconClass + ' svg'))
        .find((svg) => !gridEl.contains(svg));
      if (!sourceSvg) return;
      const svgHtml = sourceSvg.outerHTML;
      gridEl.querySelectorAll('.' + iconClass).forEach((el) => {
        if (el.dataset.svgInjected === '1') return;
        el.innerHTML = svgHtml;
        el.dataset.svgInjected = '1';
      });
    });
  }

  function renderCourses(courses) {
    if (!Array.isArray(courses) || courses.length === 0) {
      showMessage('ยังไม่พบคอร์สใกล้บ้านจากข้อมูลรอบเรียนที่เปิดอยู่');
      return;
    }

    const placeholderImg = <?php echo wp_json_encode(THEME_URI . '/assets/images/placeholder-gray.png'); ?>;
    const providerPlaceholder = <?php echo wp_json_encode('https://dummyimage.com/100x100/ddd/aaa'); ?>;

    gridEl.innerHTML = courses.map((c) => {
      const thumb = c.thumb || placeholderImg;
      const providerLogo = c.provider_logo_url || providerPlaceholder;
      const categoryName = c.primary_term_name || '';
      const finalColor = c.final_color || '#00744B';
      const providerName = c.provider_name || '';
      const audienceText = c.audience_text || 'ทุกวัย';
      return `
        <a class="card-course flex flex-col h-full"
           href="${esc(c.permalink || '#')}"
           data-modal-id="modal-course"
           data-course-id="${esc(c.id || '')}"
           data-course-url="${esc(c.permalink || '#')}">
          <div class="card-content gap-10">
            <div class="min-w-0">
              ${categoryName ? `<div class="text-fs12" style="color:${esc(finalColor)}">${esc(categoryName)}</div>` : ''}
              <h2 class="sm:text-fs20 text-fs16">${esc(c.title || '')}</h2>
              ${providerName ? `
                <div class="flex items-center gap-2 mt-1.5">
                  <img src="${esc(providerLogo)}" alt="${esc(providerName)}" class="sm:w-6 w-5 aspect-square rounded-full object-cover">
                  <h3 class="text-fs14">${esc(providerName)}</h3>
                </div>
              ` : ''}
            </div>
            <div class="img shrink-0">
              <img class="h-full w-full object-cover" src="${esc(thumb)}" alt="${esc(c.title || '')}" loading="lazy">
            </div>
          </div>
          <div class="card-footer mt-auto">
            <div class="flex items-center md:gap-5 sm:gap-3 gap-1.5">
              <div class="flex items-center sm:gap-1.5 gap-1">
                <div class="icon-calendar sm:w-5 w-4"></div>
                <span class="text-fs14">${esc(c.duration_text || 'ตามรอบเรียน')}</span>
              </div>
              <div class="flex items-center sm:gap-1.5 gap-1">
                <div class="icon-chartbar sm:w-5 w-4"></div>
                <span class="text-fs14">${esc(c.level_text || 'ไม่ระบุ')}</span>
              </div>
              <div class="flex items-center sm:gap-1.5 gap-1 flex-1 min-w-0">
                <div class="icon-person sm:w-5 w-4 shrink-0"></div>
                <span class="text-fs14 truncate block max-w-[100px]" title="${esc(audienceText)}">${esc(audienceText)}</span>
              </div>
            </div>
            <div class="text-fs16 font-semibold text-primary">${esc(distanceText(c.distance_km))}</div>
          </div>
        </a>
      `;
    }).join('');

    sectionEl.classList.remove('hidden');
    injectNearbyIcons();
    if (window.CourseModalAjax && typeof window.CourseModalAjax.rebind === 'function') {
      window.CourseModalAjax.rebind();
    }
    bindNearbyModalOpeners();
  }

  function lockBodyScroll() {
    const body = document.body;
    const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    body.style.overflow = 'hidden';
    body.style.paddingRight = `${scrollbarWidth}px`;
    body.setAttribute('data-scroll', 'hidden');
  }

  function openModalByContent(content) {
    const modalWrap = document.querySelector(`[data-modal-content="${content}"]`);
    if (!modalWrap) return;
    window.history.replaceState(null, null, `${window.location.pathname}#${content}`);
    modalWrap.classList.add('modal-active');
    lockBodyScroll();
  }

  function bindNearbyModalOpeners() {
    gridEl.querySelectorAll('.card-course[data-modal-id]').forEach((button) => {
      if (button.dataset.modalBound === '1') return;
      button.dataset.modalBound = '1';
      button.addEventListener('click', () => {
        const content = button.getAttribute('data-modal-id');
        if (content) openModalByContent(content);
      });
    });
  }

  async function loadNearby() {
    renderSkeleton(6);
    const loc = getCachedLocation();
    if (!loc) {
      showMessage('ยังไม่พบตำแหน่งปัจจุบัน กรุณาเปิดใช้งานตำแหน่งจากหน้าแผนที่ก่อน');
      return;
    }

    const restUrl = <?php echo wp_json_encode(rest_url('learningcity/v1/nearby-courses')); ?>;
    const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
    const params = new URLSearchParams({
      lat: String(loc.lat),
      lng: String(loc.lng),
      limit: '6',
    });

    try {
      async function fetchWithTimeout(url, options, ms) {
        const fetchPromise = fetch(url, options);
        const timeoutPromise = new Promise((_, reject) => {
          setTimeout(() => reject(new Error('timeout')), ms);
        });
        return Promise.race([fetchPromise, timeoutPromise]);
      }

      let lastError = '';

      try {
        const restRes = await fetchWithTimeout(`${restUrl}?${params.toString()}`, { credentials: 'same-origin' }, 30000);
        if (!restRes.ok) {
          lastError = `REST HTTP ${restRes.status}`;
        } else {
          const restJson = await restRes.json();
          if (restJson && Array.isArray(restJson.courses)) {
            renderCourses(restJson.courses);
            return;
          }
          lastError = 'REST invalid payload';
        }
      } catch (e) {
        lastError = e && e.message ? `REST ${e.message}` : 'REST failed';
      }

      const fd = new FormData();
      fd.append('action', 'lc_get_nearby_courses');
      fd.append('lat', String(loc.lat));
      fd.append('lng', String(loc.lng));
      fd.append('limit', '6');

      try {
        const ajaxRes = await fetchWithTimeout(ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          body: fd,
        }, 30000);

        if (!ajaxRes.ok) {
          showMessage(`โหลดคอร์สใกล้บ้านไม่สำเร็จ (AJAX HTTP ${ajaxRes.status} | ${lastError})`);
          return;
        }

        const json = await ajaxRes.json();
        if (!json || !json.success || !json.data) {
          const msg = json && json.data && json.data.message ? String(json.data.message) : 'invalid payload';
          showMessage(`โหลดคอร์สใกล้บ้านไม่สำเร็จ (${msg} | ${lastError})`);
          return;
        }

        renderCourses(json.data.courses || []);
      } catch (e) {
        const eMsg = e && e.message ? e.message : 'request_failed';
        showMessage(`โหลดคอร์สใกล้บ้านไม่สำเร็จ (${eMsg} | ${lastError})`);
      }
    } catch (_) {
      showMessage('โหลดคอร์สใกล้บ้านไม่สำเร็จ หรือใช้เวลานานเกินไป กรุณาลองใหม่อีกครั้ง');
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadNearby);
  } else {
    loadNearby();
  }
})();
</script>

<?php get_footer(); ?>
