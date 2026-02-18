<?php get_header(); ?>

<div class="app-layout">
    <?php get_template_part('template-parts/header/site-header'); ?>

    <main id="primary">
        <div class="container">
            <section class="lc-blog-wrap">
                <header class="lc-blog-hero">
                    <p class="lc-blog-hero__eyebrow">หมวดหมู่</p>
                    <h1 class="lc-blog-hero__title"><?php single_cat_title(); ?></h1>
                    <?php if (category_description()) : ?>
                        <div class="lc-blog-hero__desc"><?php echo wp_kses_post(category_description()); ?></div>
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
                    <p class="lc-blog-empty">ยังไม่มีบทความในหมวดหมู่นี้</p>
                <?php endif; ?>
            </section>

            <?php get_template_part('template-parts/footer/site-footer'); ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>
