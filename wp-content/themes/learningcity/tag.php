<?php get_header(); ?>
<?php $is_course_tag_layout = function_exists('lc_is_course_tag_context') ? lc_is_course_tag_context() : false; ?>

<div class="app-layout">
    <?php get_template_part('template-parts/header/site-header'); ?>

    <?php if ($is_course_tag_layout) : ?>
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

                    <section class="min-w-px" id="lc-course-main-content">

                        <?php get_template_part('template-parts/archive/course-header'); ?>

                        <?php get_template_part('template-parts/archive/course-sub-categories'); ?>

                        <?php get_template_part('template-parts/archive/filter'); ?>

                        <div class="py-8" id="lc-course-results">
                            <?php global $wp_query; ?>

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
                                    ยังไม่มีคอร์สที่ “เปิดรับสมัคร” ในแท็กนี้
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php get_template_part('template-parts/footer/site-footer'); ?>
                    </section>
                </div>
            </div>
        </main>
    <?php else : ?>
        <main id="primary">
            <div class="container">
                <section class="lc-blog-wrap">
                    <header class="lc-blog-hero">
                        <p class="lc-blog-hero__eyebrow">แท็ก</p>
                        <h1 class="lc-blog-hero__title">#<?php single_tag_title(); ?></h1>
                        <?php if (tag_description()) : ?>
                            <div class="lc-blog-hero__desc"><?php echo wp_kses_post(tag_description()); ?></div>
                        <?php endif; ?>
                    </header>

                    <?php if (have_posts()) : ?>
                        <div class="lc-blog-grid">
                            <?php while (have_posts()) : the_post(); ?>
                                <?php get_template_part('template-parts/blog/post-card'); ?>
                            <?php endwhile; ?>
                        </div>
                        <div class="lc-blog-pagination">
                            <?php
                            the_posts_pagination([
                                'mid_size'  => 1,
                                'prev_text' => 'ก่อนหน้า',
                                'next_text' => 'ถัดไป',
                                'screen_reader_text' => '',
                            ]);
                            ?>
                        </div>
                    <?php else : ?>
                        <p class="lc-blog-empty">ยังไม่มีบทความที่ใช้แท็กนี้</p>
                    <?php endif; ?>
                </section>

                <?php get_template_part('template-parts/footer/site-footer'); ?>
            </div>
        </main>
    <?php endif; ?>
</div>

<?php if ($is_course_tag_layout) : ?>
    <?php get_template_part('template-parts/components/modal-course'); ?>
    <?php get_template_part('template-parts/components/modal-category'); ?>
    <?php get_template_part('template-parts/components/modal-search'); ?>
<?php endif; ?>

<?php get_footer(); ?>
