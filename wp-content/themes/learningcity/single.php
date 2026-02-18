<?php get_header(); ?>
<?php
$share_links = function_exists('lc_get_share_links') ? lc_get_share_links(get_the_ID()) : [];
$related_query = function_exists('lc_get_related_posts') ? lc_get_related_posts(get_the_ID(), 3) : null;
?>

<div class="app-layout">
    <?php get_template_part('template-parts/header/site-header'); ?>

    <main id="primary">
        <div class="container">
            <section class="lc-article-wrap">
                <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <?php $categories = get_the_category(); ?>
                    <article <?php post_class('lc-article'); ?>>
                        <header class="lc-article-header">
                            <?php if (!empty($categories)) : ?>
                                <a class="lc-blog-pill" href="<?php echo esc_url(get_category_link($categories[0]->term_id)); ?>">
                                    <?php echo esc_html($categories[0]->name); ?>
                                </a>
                            <?php endif; ?>

                            <h1 class="lc-article-title"><?php the_title(); ?></h1>

                            <div class="lc-article-meta">
                                <span><?php echo esc_html(get_the_date('j M Y')); ?></span>
                                <span>โดย <?php echo esc_html(get_the_author()); ?></span>
                            </div>
                        </header>

                        <?php if (has_post_thumbnail()) : ?>
                            <figure class="lc-article-cover">
                                <?php the_post_thumbnail('full', ['loading' => 'eager']); ?>
                            </figure>
                        <?php endif; ?>

                        <div class="lc-article-content">
                            <?php the_content(); ?>
                        </div>

                        <footer class="lc-article-footer">
                            <?php
                            $tags = get_the_tags();
                            if (!empty($tags)) :
                            ?>
                                <div class="lc-article-tags">
                                    <?php foreach ($tags as $tag) : ?>
                                        <a class="lc-blog-pill" href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>">#<?php echo esc_html($tag->name); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($share_links)) : ?>
                                <div class="lc-share-box">
                                    <span class="lc-share-box__label">แชร์บทความ</span>
                                    <div class="lc-share-box__actions">
                                        <a href="<?php echo esc_url($share_links['facebook']); ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
                                        <a href="<?php echo esc_url($share_links['x']); ?>" target="_blank" rel="noopener noreferrer">X</a>
                                        <a href="<?php echo esc_url($share_links['line']); ?>" target="_blank" rel="noopener noreferrer">LINE</a>
                                        <button type="button" class="lc-copy-link" data-link="<?php echo esc_attr($share_links['copy']); ?>">คัดลอกลิงก์</button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </footer>
                    </article>

                    <?php if ($related_query instanceof WP_Query && $related_query->have_posts()) : ?>
                        <section class="lc-related-posts">
                            <h2 class="lc-related-posts__title">บทความที่เกี่ยวข้อง</h2>
                            <div class="lc-blog-grid lc-blog-grid--related">
                                <?php while ($related_query->have_posts()) : $related_query->the_post(); ?>
                                    <?php get_template_part('template-parts/blog/post-card'); ?>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endwhile; endif; ?>
            </section>

            <?php get_template_part('template-parts/footer/site-footer'); ?>
        </div>
    </main>
</div>

<script>
  (function () {
    const copyButtons = document.querySelectorAll('.lc-copy-link');
    if (!copyButtons.length) return;

    copyButtons.forEach((btn) => {
      btn.addEventListener('click', async function () {
        const link = btn.getAttribute('data-link') || '';
        if (!link) return;

        try {
          await navigator.clipboard.writeText(link);
          const original = btn.textContent;
          btn.textContent = 'คัดลอกแล้ว';
          setTimeout(() => {
            btn.textContent = original;
          }, 1400);
        } catch (error) {
          window.prompt('คัดลอกลิงก์นี้', link);
        }
      });
    });
  })();
</script>

<?php get_footer(); ?>
