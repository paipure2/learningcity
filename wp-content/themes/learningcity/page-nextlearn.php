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

<?php get_footer(); ?>


