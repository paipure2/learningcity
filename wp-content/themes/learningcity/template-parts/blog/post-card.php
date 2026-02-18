<?php
$post_id = get_the_ID();
$thumb = get_the_post_thumbnail_url($post_id, 'large');
$categories = get_the_category($post_id);
$primary_category = !empty($categories) ? $categories[0] : null;
?>
<article class="lc-blog-card">
    <a class="lc-blog-card__thumb" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
        <?php if ($thumb) : ?>
            <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
        <?php else : ?>
            <span class="lc-blog-card__thumb-placeholder">Bangkok Learning City</span>
        <?php endif; ?>
    </a>

    <div class="lc-blog-card__body">
        <div class="lc-blog-card__meta">
            <?php if ($primary_category) : ?>
                <a class="lc-blog-pill" href="<?php echo esc_url(get_category_link($primary_category->term_id)); ?>">
                    <?php echo esc_html($primary_category->name); ?>
                </a>
            <?php endif; ?>
            <span><?php echo esc_html(get_the_date('j M Y')); ?></span>
        </div>

        <h2 class="lc-blog-card__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>

    </div>
</article>
