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
                                <span class="inline-flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor" aria-hidden="true">
                                        <path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Zm280 240q-17 0-28.5-11.5T440-440q0-17 11.5-28.5T480-480q17 0 28.5 11.5T520-440q0 17-11.5 28.5T480-400Zm-188.5-11.5Q280-423 280-440t11.5-28.5Q303-480 320-480t28.5 11.5Q360-457 360-440t-11.5 28.5Q337-400 320-400t-28.5-11.5ZM640-400q-17 0-28.5-11.5T600-440q0-17 11.5-28.5T640-480q17 0 28.5 11.5T680-440q0 17-11.5 28.5T640-400ZM480-240q-17 0-28.5-11.5T440-280q0-17 11.5-28.5T480-320q17 0 28.5 11.5T520-280q0 17-11.5 28.5T480-240Zm-188.5-11.5Q280-263 280-280t11.5-28.5Q303-320 320-320t28.5 11.5Q360-297 360-280t-11.5 28.5Q337-240 320-240t-28.5-11.5ZM640-240q-17 0-28.5-11.5T600-280q0-17 11.5-28.5T640-320q17 0 28.5 11.5T680-280q0 17-11.5 28.5T640-240Z"/>
                                    </svg>
                                    <span><?php echo esc_html(get_the_date('j M Y')); ?></span>
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor" aria-hidden="true">
                                        <path d="M262-320q-61 0-93-16.5T110-398q-15-25-25.5-59.5T65-520q-11 0-18-7t-7-18v-49q0-9 6-15.5t15-8.5q56-11 101.5-16t87.5-5q61 0 110.5 10t77.5 29h85q24-18 76-28.5T711-639q42 0 87 5t101 16q9 2 15 8.5t6 15.5v49q0 11-7 18t-18 7q-9 28-19.5 62.5T850-398q-26 44-58.5 61T698-320q-63 0-107-27t-61-87q-5-16-8.5-32t-8.5-32q-4-12-12-17.5t-21-5.5q-12 0-20 6t-13 17q-5 16-8.5 32t-8.5 32q-17 60-61 87t-107 27Zm0-58q71 0 97-42t26-108q0-17-12-26.5T326-572q-26-7-63-8.5t-72 3.5q-26 4-38.5 13T140-538q0 27 4.5 52t13.5 46q16 35 37.5 48.5T262-378Zm436 0q45 0 66.5-13.5T801-440q9-21 14-46t5-53q0-17-12.5-26.5T768-578q-35-5-71.5-3t-62.5 9q-35 8-47 17t-12 26q0 66 26 108.5t97 42.5Z"/>
                                    </svg>
                                    <span>อ่าน <?php echo esc_html(number_format_i18n(function_exists('lc_get_post_views_count') ? lc_get_post_views_count(get_the_ID()) : 0)); ?> ครั้ง</span>
                                </span>
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
