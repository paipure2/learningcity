<?php
/**
 * The template for displaying 404 pages (Not Found)
 */
get_header(); ?>

<div class="app-layout overflow-visible!">
  <?php get_template_part('template-parts/header/site-header'); ?>

  <main id="primary" class="flex-1 flex items-center justify-center min-h-[70vh] py-10">
    <div class="container">
      <div class="text-center">
        <h1 class="text-primary font-bold sm:text-fs64 text-fs50 leading-none">404</h1>
        <p class="sm:text-fs18 text-fs16 text-black/70 mt-3">ไม่พบหน้านี้</p>

        <a
          href="<?php echo esc_url( site_url('/') ); ?>"
          class="inline-flex items-center justify-center rounded-full bg-primary text-white font-semibold px-6 py-3 mt-6 hover:scale-[1.02] transition-transform"
        >
          กลับหน้าแรก
        </a>
      </div>
    </div>
  </main>

  <?php get_template_part('template-parts/footer/site-footer'); ?>
</div>

<?php get_footer();
