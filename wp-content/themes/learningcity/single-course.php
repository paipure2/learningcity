<?php get_header(); ?>

<?php
$post_id = get_the_ID();

// กัน helpers ไม่ถูก include
if (!function_exists('course_get_primary_category_context')) {
  $helpers = get_template_directory() . '/inc/course-helpers.php';
  if (file_exists($helpers)) require_once $helpers;
}

$cat_ctx = function_exists('course_get_primary_category_context')
  ? course_get_primary_category_context($post_id)
  : ['final_color'=>'#00744B','primary'=>null,'parent'=>null,'primary_link'=>'','parent_link'=>''];

$ctx = [
  'cat' => $cat_ctx,
  'thumb' => function_exists('course_get_thumb') ? course_get_thumb($post_id) : (get_the_post_thumbnail_url($post_id, 'large') ?: 'https://learning.bangkok.go.th/wp-content/themes/learningcity/assets/images/placeholder-gray.png'),
  'provider' => function_exists('course_get_provider_context') ? course_get_provider_context($post_id) : ['name'=>'','term_link'=>'','img_src'=>'https://learning.bangkok.go.th/wp-content/themes/learningcity/assets/images/placeholder-gray.png'],

  'duration_text' => function_exists('course_get_duration_text') ? course_get_duration_text($post_id) : 'ตามรอบเรียน',
  'level_text' => function_exists('course_get_level_text') ? course_get_level_text($post_id) : 'ไม่ระบุ',
  'audience_text' => function_exists('course_get_audience_text') ? course_get_audience_text($post_id) : 'ทุกวัย',
  'has_cert' => (bool) get_field('has_certificate', $post_id),
  'price_text' => function_exists('course_get_price_text') ? course_get_price_text($post_id) : 'ดูรอบเรียน',

  'desc' => get_field('course_description', $post_id),
  'learning_link' => get_field('learning_link', $post_id),

  'grouped_sessions' => function_exists('course_get_grouped_sessions_by_location') ? course_get_grouped_sessions_by_location($post_id) : [],
];

$final_color = !empty($ctx['cat']['final_color']) ? $ctx['cat']['final_color'] : '#00744B';

// ส่ง ctx ให้ template-parts
set_query_var('ctx', $ctx);
set_query_var('mode', 'single');
?>

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

          <div class="relative xl:px-6">
            <div class="absolute top-0 xl:left-0 sm:-left-8 -left-4 xl:right-0 sm:-right-8 -right-4 sm:h-96 h-[540px] xl:rounded-20 overflow-hidden"
              style="background: linear-gradient(0deg, #fff 10%, <?php echo esc_attr($final_color); ?> 45%); opacity:0.2;">
            </div>

            <div class="relative max-w-[700px] mx-auto sm:py-16 py-8">
              <div class="content-inner is-loaded">

                <?php
                  get_template_part('template-parts/course/hero');
                  get_template_part('template-parts/course/stats');
                  get_template_part('template-parts/course/description');
                  get_template_part('template-parts/course/sessions-accordion');
                ?>

                <div class="pt-3 pb-8 flex justify-center">
                  <button class="lc-report-btn"
                          data-modal-id="modal-course-report"
                          data-course-report-open
                          data-course-id="<?php echo esc_attr($post_id); ?>">
                    <span class="lc-report-btn__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="currentColor"><path d="M560-80v-123l221-220q9-9 20-13t22-4q12 0 23 4.5t20 13.5l37 37q8 9 12.5 20t4.5 22q0 11-4 22.5T903-300L683-80H560Zm300-263-37-37 37 37ZM620-140h38l121-122-18-19-19-18-122 121v38ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v120h-80v-80H520v-200H240v640h240v80H240Zm280-400Zm241 199-19-18 37 37-18-19Z"/></svg>
                    </span>
                    <span>แจ้งแก้ไขข้อมูล</span>
                  </button>
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

<script type="module">
  async function loadData() {
    const [categories, location, suitableFor] = await Promise.all([
      fetch('<?php echo THEME_URI ?>/jsonMockup/categories.json').then(res => res.json()),
      fetch('<?php echo THEME_URI ?>/jsonMockup/location.json').then(res => res.json()),
      fetch('<?php echo THEME_URI ?>/jsonMockup/suitableFor.json').then(res => res.json())
    ]);

    const listConfig = [
      {
        ids: ["category-list", "modal-category-list"],
        items: categories,
        template: (item) => `
          <a href="<?php echo site_url('/archive') ?>" class="flex w-full max-w-[240px] gap-2 items-center text-fs16 font-normal px-2 py-1.5 hover:bg-black/5 rounded-lg transition-colors">
            <div class="w-5.5 aspect-square rounded-sm overflow-hidden">
              <img src="https://dummyimage.com/100x100/${item.color.replace('#', '')}/${item.color.replace('#', '')}" alt="">
            </div>
            <span class="inline-block">${item.name}</span>
          </a>
        `,
      },
      {
        ids: ["location-list", "modal-location-list"],
        items: location,
        template: (item) => `
          <a href="#!" class="flex w-full max-w-[240px] gap-2 items-center text-fs16 font-normal px-2 py-1.5 hover:bg-black/5 rounded-lg transition-colors"><span>${item}</span></a>
        `,
      },
      {
        ids: ["suitableFor-list", "modal-suitableFor-list"],
        items: suitableFor,
        template: (item) => `
          <a href="#!" class="flex w-full max-w-[240px] gap-2 items-center text-fs16 font-normal px-2 py-1.5 hover:bg-black/5 rounded-lg transition-colors"><span>${item}</span></a>
        `,
      },
    ];

    listConfig.forEach(({ ids, items, template }) => {
      const html = items.map(template).join("");
      ids.forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = html;
      });
    });
  }
  loadData();
</script>
