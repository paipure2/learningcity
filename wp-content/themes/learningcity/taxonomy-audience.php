<?php get_header(); ?>

<div class="app-layout">
    <?php get_template_part('template-parts/header/site-header'); ?>

    <main>
        <button class="btn-category-floating" data-modal-id="modal-category">
            <img src="<?php echo THEME_URI ?>/assets/images/btn-menu-category.svg" alt="Categories">
        </button>

        <button class="btn-searh-floating" data-modal-id="modal-search">
            <div class="icon-search"></div>
        </button>

        <div class="container">
            <div class="layout-sidebar">
                <?php get_template_part('template-parts/components/aside'); ?>
                
                <section class="min-w-px">

                    <?php 
                    /**
                     * 1. ส่วนหัวหมวดหมู่ (Banner)
                     * แสดงชื่อหมวดหมู่ คำอธิบาย และรูปภาพประกอบ
                     */
                    get_template_part('template-parts/archive/course-header'); 
                    ?>

                    <?php 
                    /**
                     * 2. ส่วนหมวดหมู่ย่อย (Sub-categories Swiper)
                     * แสดงปุ่มหมวดหมู่ลูก (ถ้ามี)
                     */
                    get_template_part('template-parts/archive/course-sub-categories'); 
                    ?>

                    <?php 
                
                    get_template_part('template-parts/archive/filter'); 
                    ?>


                        <div class="py-8" id="lc-course-results">
                        <?php if (have_posts()) : ?>
                            <div class="grid lg:grid-cols-2 grid-cols-1 sm:gap-6 gap-4" id="lc-course-grid">

                            <?php while (have_posts()) : the_post(); ?>
                                <?php get_template_part('template-parts/archive/course-card'); ?>
                            <?php endwhile; ?>

                            </div>

                            <?php
                            set_query_var('max_pages', $wp_query->max_num_pages);
                            get_template_part('template-parts/archive/course-pagination');
                            ?>

                        <?php else : ?>
                            <div class="py-10 text-center text-fs16 opacity-70">
                            ยังไม่มีคอร์สที่ “เปิดรับสมัคร” ในหมวดนี้
                            </div>
                        <?php endif; ?>
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
