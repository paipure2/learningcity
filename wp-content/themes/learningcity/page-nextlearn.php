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

  function showMessage(msg, options = {}) {
    const showLocateButton = !!(options && options.showLocateButton);
    const buttonHtml = showLocateButton
      ? `<button type="button" id="nearby-use-current-location" class="mt-4 inline-flex items-center justify-center rounded-full bg-[#00744B] px-5 py-2 text-white text-fs14 font-semibold hover:opacity-90">ใช้ตำแหน่งปัจจุบันของคุณ</button>`
      : '';

    sectionEl.classList.remove('hidden');
    gridEl.innerHTML = `
      <div class="col-span-full">
        <div class="w-full rounded-2xl border border-gray-200 bg-gray-100 px-5 py-6 text-center">
          <div class="text-fs16 opacity-80">${esc(msg)}</div>
          ${buttonHtml}
        </div>
      </div>
    `;

    if (showLocateButton) {
      const btn = document.getElementById('nearby-use-current-location');
      if (btn) {
        btn.addEventListener('click', async () => {
          if (!navigator.geolocation) {
            showMessage('อุปกรณ์นี้ไม่รองรับการระบุตำแหน่ง');
            return;
          }

          btn.disabled = true;
          btn.classList.add('opacity-60', 'cursor-not-allowed');
          btn.textContent = 'กำลังขอตำแหน่ง...';

          navigator.geolocation.getCurrentPosition(
            (pos) => {
              const lat = Number(pos && pos.coords ? pos.coords.latitude : NaN);
              const lng = Number(pos && pos.coords ? pos.coords.longitude : NaN);
              if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                showMessage('ไม่สามารถอ่านค่าตำแหน่งได้ กรุณาลองใหม่อีกครั้ง', { showLocateButton: true });
                return;
              }

              try {
                localStorage.setItem(LOCATION_CACHE_KEY, JSON.stringify({
                  lat,
                  lng,
                  ts: Date.now(),
                  source: 'nextlearn',
                }));
              } catch (_) {}

              loadNearby();
            },
            () => {
              showMessage('ไม่สามารถเข้าถึงตำแหน่งได้ กรุณาอนุญาตการเข้าถึงตำแหน่ง', { showLocateButton: true });
            },
            {
              enableHighAccuracy: true,
              timeout: 10000,
              maximumAge: 0,
            }
          );
        });
      }
    }
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

  function decodeHtmlEntities(s) {
    const str = String(s == null ? '' : s);
    const textarea = document.createElement('textarea');
    textarea.innerHTML = str;
    return textarea.value;
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
      const durationText = c.duration_text || 'ตามรอบเรียน';
      const courseTitle = decodeHtmlEntities(c.title || '');
      return `
        <a class="card-course flex flex-col h-full"
           href="${esc(c.permalink || '#')}"
           data-modal-id="modal-course"
           data-course-id="${esc(c.id || '')}"
           data-course-url="${esc(c.permalink || '#')}">
          <div class="card-content gap-10">
            <div class="min-w-0">
              ${categoryName ? `<div class="text-fs12" style="color:${esc(finalColor)}">${esc(categoryName)}</div>` : ''}
              <h2 class="sm:text-fs20 text-fs16">${esc(courseTitle)}</h2>
              ${providerName ? `
                <div class="flex items-center gap-2 mt-1.5">
                  <img src="${esc(providerLogo)}" alt="${esc(providerName)}" class="sm:w-6 w-5 aspect-square rounded-full object-cover">
                  <h3 class="text-fs14">${esc(providerName)}</h3>
                </div>
              ` : ''}
            </div>
            <div class="img shrink-0">
              <img class="h-full w-full object-cover" src="${esc(thumb)}" alt="${esc(courseTitle)}" loading="lazy">
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
                <span class="text-fs14 truncate block max-w-[90px]" title="${esc(durationText)}">${esc(durationText)}</span>
              </div>
            
              <div class="flex items-center sm:gap-1.5 gap-1 flex-1 min-w-0">
                <span class="sm:w-5 w-4 shrink-0 inline-flex text-[#979797]" aria-hidden="true">
                  <svg class="w-full h-full" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.7969 5.50684C12.1776 5.5069 12.5537 5.59048 12.8994 5.75098C13.2451 5.91146 13.552 6.14546 13.8008 6.43555L17.5947 10.8564H17.5938C17.7339 11.0076 17.8468 11.1819 17.9238 11.373C18.0058 11.5766 18.0478 11.7944 18.0479 12.0137C18.0479 12.2329 18.0058 12.4508 17.9238 12.6543C17.8418 12.8579 17.7207 13.0445 17.5674 13.2021C17.4141 13.3597 17.231 13.4859 17.0283 13.5723C16.8254 13.6586 16.6064 13.7031 16.3857 13.7031C16.1652 13.7031 15.9469 13.6586 15.7441 13.5723C15.5578 13.4929 15.3895 13.3784 15.2441 13.2383L13.8359 12.0762L15.2451 17.582C15.3924 17.9655 15.3992 18.3907 15.2588 18.7783C15.1095 19.1904 14.8065 19.5313 14.4092 19.7217C14.0112 19.9124 13.5543 19.9349 13.1396 19.7832C12.7405 19.6372 12.4168 19.3423 12.2266 18.9658L10.0244 15.0654L7.82227 18.9668C7.63199 19.3427 7.30883 19.6373 6.91016 19.7832C6.49554 19.9349 6.03858 19.9122 5.64062 19.7217C5.24326 19.5313 4.93931 19.1905 4.79004 18.7783C4.64924 18.3895 4.65608 17.9625 4.80469 17.5781L6.21289 12.0752L4.79004 13.249C4.48525 13.5366 4.0836 13.7012 3.66113 13.7012C3.2148 13.701 2.79045 13.5188 2.48047 13.2002C2.17117 12.8822 2.00011 12.4548 2 12.0127C2 11.5838 2.16215 11.1693 2.4541 10.8545H2.45312L6.24902 6.43457C6.4977 6.1448 6.80497 5.91134 7.15039 5.75098C7.49592 5.59063 7.87145 5.50696 8.25195 5.50684H11.7969ZM8.25293 6.81738C8.06355 6.81742 7.87503 6.85874 7.70117 6.93945C7.52748 7.02016 7.37127 7.13875 7.24316 7.28809H7.24219L3.44727 11.708C3.43856 11.7181 3.42923 11.7277 3.41992 11.7373C3.35197 11.8071 3.31055 11.9064 3.31055 12.0127C3.31066 12.1188 3.35206 12.2174 3.41992 12.2871C3.48712 12.356 3.57414 12.3915 3.66113 12.3916C3.74829 12.3916 3.836 12.3561 3.90332 12.2871L3.95605 12.2383L6.92871 9.78711C7.14742 9.60675 7.45797 9.58726 7.69727 9.73926C7.93648 9.89127 8.05075 10.1805 7.98047 10.4551L6.06543 17.9346C6.0567 17.9686 6.04532 18.002 6.03125 18.0342C5.99043 18.1277 5.98745 18.2353 6.02246 18.332C6.0573 18.4282 6.12512 18.5012 6.20605 18.54C6.28645 18.5786 6.3774 18.5829 6.45996 18.5527C6.54301 18.5223 6.61616 18.4566 6.65918 18.3652C6.66604 18.3507 6.67372 18.3363 6.68164 18.3223L9.4541 13.4121L9.50293 13.3389C9.62563 13.1768 9.81782 13.0792 10.0244 13.0791C10.2607 13.0791 10.4786 13.2064 10.5947 13.4121L13.3682 18.3223L13.3896 18.3652C13.4327 18.4567 13.5067 18.5223 13.5898 18.5527C13.6723 18.5829 13.7625 18.5784 13.8428 18.54C13.9238 18.5012 13.9925 18.4283 14.0273 18.332C14.0623 18.2353 14.0584 18.1277 14.0176 18.0342C14.0035 18.002 13.9921 17.9686 13.9834 17.9346L12.0693 10.4551C11.999 10.1804 12.1132 9.89122 12.3525 9.73926C12.5919 9.5873 12.9024 9.60657 13.1211 9.78711L16.0918 12.2402L16.1445 12.2891C16.1779 12.3232 16.2166 12.3497 16.2578 12.3672C16.2989 12.3847 16.3424 12.3935 16.3857 12.3936C16.4293 12.3936 16.4734 12.3847 16.5146 12.3672C16.5558 12.3497 16.5945 12.3233 16.6279 12.2891C16.6614 12.2547 16.6889 12.2123 16.708 12.165C16.7271 12.1177 16.7373 12.0659 16.7373 12.0137C16.7373 11.9616 16.727 11.9105 16.708 11.8633C16.6889 11.8159 16.6614 11.7737 16.6279 11.7393C16.6185 11.7296 16.6094 11.7193 16.6006 11.709L12.8066 7.28809C12.6784 7.13856 12.5216 7.02019 12.3477 6.93945C12.2173 6.87896 12.0793 6.84065 11.9385 6.8252L11.7969 6.81738H8.25293ZM10.0234 0C11.5116 0 12.6875 1.23552 12.6875 2.7207C12.6873 4.20572 11.5115 5.44043 10.0234 5.44043C8.53553 5.44023 7.35957 4.20561 7.35938 2.7207C7.35938 1.23564 8.53541 0.000197928 10.0234 0ZM10.0234 1.31055C9.29255 1.31075 8.66992 1.92501 8.66992 2.7207C8.67011 3.51622 9.29266 4.13066 10.0234 4.13086C10.7544 4.13086 11.3777 3.51635 11.3779 2.7207C11.3779 1.92488 10.7545 1.31055 10.0234 1.31055Z" fill="currentColor"/>
                  </svg>
                </span>
                <span class="text-fs14 truncate block max-w-[200px]" title="${esc(audienceText)}">${esc(audienceText)}</span>
              </div>
            </div>
            <div class="text-fs16 font-semibold text-primary">${esc(distanceText(c.distance_km))}</div>
          </div>
        </a>
      `;
    }).join('');

    sectionEl.classList.remove('hidden');
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
      showMessage('ยังไม่พบตำแหน่งปัจจุบัน กรุณาเปิดใช้งานตำแหน่ง', { showLocateButton: true });
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
