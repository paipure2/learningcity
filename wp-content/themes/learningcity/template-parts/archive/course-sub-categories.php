<?php
$term = get_queried_object();
if (!$term || empty($term->term_id) || empty($term->taxonomy)) return;

// ถ้าอยู่หน้า child ให้ใช้ parent ของมัน, ถ้าอยู่หน้า parent ก็ใช้ตัวมันเอง
$parent_id = !empty($term->parent) ? (int) $term->parent : (int) $term->term_id;

$sub_terms = get_terms([
    'taxonomy'   => $term->taxonomy,
    'parent'     => $parent_id,
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

if (!is_wp_error($sub_terms) && !empty($sub_terms)) :
    // สีจะ inherit จาก "parent" เพื่อให้เหมือนกันทุกหน้า (parent/child)
    $parent_term = get_term($parent_id, $term->taxonomy);
    $term_color  = $parent_term ? get_term_acf_inherit($parent_term, 'color') : '';
    $pill_bg     = !empty($term_color) ? hex_to_rgba($term_color, 0.18) : '#D6EBE0';
    $pill_solid  = !empty($term_color) ? $term_color : '#00744B';
?>
<div class="xl:py-3 py-3 sec-category-highlight">
    <div class="lc-subcat-scroll" role="navigation" aria-label="หมวดย่อยของคอร์ส">
            <?php foreach ($sub_terms as $sub) :
                $sub_link = get_term_link($sub);
                if (is_wp_error($sub_link)) continue;

                // (optional) ทำ active state ให้ตัวที่เป็นหน้าปัจจุบัน
                $is_active = ((int)$sub->term_id === (int)$term->term_id);
            ?>
                <div class="lc-subcat-item">
                    <a href="<?php echo esc_url($sub_link); ?>"
                       class="inline-flex items-center justify-center sm:px-6 px-3 sm:py-4 py-2 rounded-full font-semibold sm:text-fs16 text-fs14 whitespace-nowrap transition-all hover:-translate-y-[1px] hover:shadow-md <?php echo $is_active ? 'text-white' : 'text-black'; ?>"
                       style="background-color: <?php echo esc_attr($is_active ? $pill_solid : $pill_bg); ?>;">
                        <?php echo esc_html($sub->name); ?>
                    </a>
                </div>
            <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
