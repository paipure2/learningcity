<?php get_header(); ?>

<div class="app-layout">
    <?php get_template_part('template-parts/header/site-header'); ?>

    <main id="primary">
        <div class="container">
            <section class="lc-blog-wrap">
                <header class="lc-blog-hero">
                    <p class="lc-blog-hero__eyebrow">Bangkok Learning City</p>
                    <h1 class="lc-blog-hero__title">บทความและความรู้</h1>
                    <p class="lc-blog-hero__desc">อัปเดตข่าวสาร ความรู้ และเรื่องเล่าการเรียนรู้จากเครือข่ายกรุงเทพมหานคร</p>
                </header>

                <?php
                $top_categories = get_categories([
                    'orderby'    => 'count',
                    'order'      => 'DESC',
                    'hide_empty' => true,
                    'number'     => 8,
                ]);
                $blog_page_id = (int) get_option('page_for_posts');
                $blog_url = $blog_page_id ? get_permalink($blog_page_id) : home_url('/');
                if (!empty($top_categories)) :
                ?>
                    <div class="lc-blog-tax-row">
                        <a class="lc-blog-pill <?php echo is_home() ? 'is-active' : ''; ?>" href="<?php echo esc_url($blog_url); ?>">ทั้งหมด</a>
                        <?php foreach ($top_categories as $cat) : ?>
                            <a class="lc-blog-pill" href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"><?php echo esc_html($cat->name); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

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
                    <p class="lc-blog-empty">ยังไม่มีบทความในระบบ</p>
                <?php endif; ?>
            </section>

            <?php get_template_part('template-parts/footer/site-footer'); ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>
