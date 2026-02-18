<?php
$ctx  = get_query_var('ctx');
$mode = get_query_var('mode') ?: 'single';
if (empty($ctx) || !is_array($ctx)) { echo '<!-- sessions: missing ctx -->'; return; }

$grouped = !empty($ctx['grouped_sessions']) ? $ctx['grouped_sessions'] : [];

/**
 * ✅ Filter: เอาเฉพาะ location ที่มี session "เปิดรับสมัครอยู่"
 * - รวมเคสเปิดตลอด: reg_start/reg_end ว่าง
 */
$today_ts = strtotime(current_time('Y-m-d'));

$open_grouped = [];
if (!empty($grouped)) {
  foreach ($grouped as $location_id => $sids) {
    $open_sids = [];

    foreach ((array)$sids as $sid) {
      // ใช้ฟังก์ชันเดียวกับที่ทำ filter ใน archive
      if (function_exists('lc_is_session_open_for_reg') && lc_is_session_open_for_reg($sid, $today_ts)) {
        $open_sids[] = $sid;
      }
    }

    if (!empty($open_sids)) {
      $open_grouped[$location_id] = $open_sids;
    }
  }
}

$grouped = $open_grouped;
$location_count = count($grouped);

$blm_pages = get_posts([
  'post_type'      => 'page',
  'posts_per_page' => 1,
  'meta_key'       => '_wp_page_template',
  'meta_value'     => 'page-blm.php',
  'post_status'    => 'publish',
  'fields'         => 'ids',
]);
$learning_map_url = !empty($blm_pages) ? get_permalink($blm_pages[0]) : home_url('/learning-map/');
?>

<div class="my-10" data-course-session-distance="1">

<?php
// ACF field ในหน้า single course (post ปัจจุบัน)
$learning_link = function_exists('get_field') ? get_field('learning_link', get_the_ID()) : '';
?>

  <?php if ($location_count > 0): ?>
    <div class="flex items-center justify-between gap-3">
      <h2 class="sm:text-fs24 text-fs22 font-bold">
        <?php echo esc_html($location_count); ?> สถานที่ที่เปิดสอน
      </h2>
      <button type="button" class="px-3 py-2 rounded-lg border text-sm font-semibold hover:bg-slate-50 shrink-0" data-course-distance-use-current>
        ใช้ตำแหน่งปัจจุบัน
      </button>
    </div>

  <?php else: ?>

    <?php if (empty($learning_link)): ?>
      <div class="py-6 text-center text-fs16 opacity-70">
        ขณะนี้ยังยังไม่มีสถานที่เปิดคอร์สเรียนนี้ กรุณารอรอเปิดรับสมัคร
      </div>
      <?php do_action('lcw_render_waitlist_form', get_the_ID(), $mode); ?>
    <?php else: ?>
      <!-- กรณีไม่มีสถานที่ แต่มี learning_link (ถ้าไม่อยากแสดงอะไร ก็ปล่อยว่างได้) -->
    <?php endif; ?>

  <?php endif; ?>


  <div class="accordion-detail mt-4" data-course-location-list>

    <?php if (!empty($grouped)) : ?>
      <?php foreach ($grouped as $location_id => $sids) : ?>

        <?php
        $address       = get_field('address', $location_id);
        $phone         = get_field('phone', $location_id);
        $facebook_link = get_field('facebook_link', $location_id);
        $map_url       = get_field('map_url', $location_id);
        $location_map_page_url = add_query_arg(['place' => (int) $location_id], $learning_map_url);
        $lat_raw       = get_post_meta((int) $location_id, 'latitude', true);
        $lng_raw       = get_post_meta((int) $location_id, 'longitude', true);
        $lat_val       = is_numeric($lat_raw) ? (float) $lat_raw : null;
        $lng_val       = is_numeric($lng_raw) ? (float) $lng_raw : null;
        $district_terms = wp_get_post_terms((int) $location_id, 'district', ['fields' => 'names']);
        $district_name  = (!is_wp_error($district_terms) && !empty($district_terms[0])) ? trim((string) $district_terms[0]) : '';
        if ($district_name !== '' && mb_strpos($district_name, 'เขต') !== 0) {
          $district_name = 'เขต' . $district_name;
        }
        ?>

        <div class="accordion-item" data-location-id="<?php echo (int) $location_id; ?>" data-lat="<?php echo $lat_val !== null ? esc_attr((string) $lat_val) : ''; ?>" data-lng="<?php echo $lng_val !== null ? esc_attr((string) $lng_val) : ''; ?>" data-district-label="<?php echo esc_attr($district_name); ?>">

          <div class="accordion-header flex items-center justify-between cursor-pointer">
            <div>
              <span class="block sm:text-fs18 text-fs16 font-semibold">
                <?php echo esc_html(get_the_title($location_id)); ?>
              </span>
              <span class="block text-fs14 text-primary mt-1" data-distance-badge></span>
            </div>

            <?php if ($mode === 'single'): ?>
              <div class="border border-primary rounded-full md:py-1.5 md:px-4 flex items-center justify-center gap-2 hover:bg-primary/10 transition-colors duration-300 max-md:w-7 max-md:aspect-square">
                <span class="md:block hidden text-fs14 font-semibold text-primary accordion-text-expand">รายละเอียด</span>
                <span class="icon-arrow-accordion w-3">
                  <svg class="w-full h-full" viewBox="0 0 12 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M-1.45986e-06 5.87361L6 4.40896e-07L12 5.87361L10.8494 7L6 2.25278L1.15063 7L-1.45986e-06 5.87361Z" fill="#00744B"/>
                  </svg>
                </span>
              </div>
            <?php else: ?>
              <div class="border border-primary rounded-full md:py-2.5 md:px-2 flex items-center justify-center gap-2 hover:bg-primary/10 transition-colors duration-300 max-md:w-7 max-md:aspect-square">
                <span class="icon-arrow-accordion w-3">
                  <svg class="w-full h-full" viewBox="0 0 12 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M-1.45986e-06 5.87361L6 4.40896e-07L12 5.87361L10.8494 7L6 2.25278L1.15063 7L-1.45986e-06 5.87361Z" fill="#00744B"/>
                  </svg>
                </span>
              </div>
            <?php endif; ?>
          </div>

          <div class="accordion-panel">
            <div class="py-3">

              <?php foreach ($sids as $i => $sid) : ?>

                <?php
                // ใช้ raw value (กัน format แปลก)
                $reg_start       = get_field('reg_start', $sid, false);
                $reg_end         = get_field('reg_end', $sid, false);
                $start_date      = get_field('start_date', $sid, false);
                $end_date        = get_field('end_date', $sid, false);
                $time_period     = get_field('time_period', $sid);
                $session_details = get_field('session_details', $sid);
                $apply_url       = get_field('apply_url', $sid);
                ?>

                <?php if (count($sids) > 1): ?>
                  <div class="font-bold sm:text-fs16 text-fs14 mt-2">
                    รอบที่ <?php echo esc_html($i + 1); ?>
                  </div>
                <?php endif; ?>

                <div class="grid sm:grid-cols-2 grid-cols-1 sm:gap-3 gap-2 pb-3 mt-2">

                  <?php if ($reg_start || $reg_end): ?>
                    <div class="bg-[#DEF6EE] rounded-lg py-2 px-4 max-sm:flex gap-3">
                      <i class="block icon-edit w-6 mb-1 max-sm:self-baseline">

                        <svg class="w-full h-full" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.9918 3.11868C17.2721 2.93366 17.6527 2.96514 17.8995 3.21191L20.7885 6.10081C21.0705 6.38285 21.0705 6.83929 20.7885 7.12134L12.1215 15.788C11.9861 15.9235 11.8028 16 11.6112 16H8.72225C8.32336 16 8 15.6766 8 15.2778V12.3889C8 12.1973 8.07652 12.0141 8.21196 11.8786L16.8789 3.21191L16.9918 3.11868ZM9.44449 12.6872V14.5555H11.3129L19.2576 6.61107L17.3892 4.74271L9.44449 12.6872Z" fill="black"/>
                        <path d="M14.3203 6.0703C14.5807 5.8099 15.0021 5.8099 15.2625 6.0703L17.9297 8.73749C18.1901 8.99789 18.1901 9.4193 17.9297 9.6797C17.6693 9.9401 17.2479 9.9401 16.9875 9.6797L14.3203 7.01251C14.0599 6.75211 14.0599 6.3307 14.3203 6.0703Z" fill="black"/>
                        <path d="M3 19.5833V5.41667C3 5.04094 3.14912 4.68047 3.4148 4.4148C3.68047 4.14912 4.04094 4 4.41667 4H11.5C11.8912 4 12.2083 4.31713 12.2083 4.70833C12.2083 5.09954 11.8912 5.41667 11.5 5.41667H4.41667V19.5833H18.5833V12.5C18.5833 12.1088 18.9005 11.7917 19.2917 11.7917C19.6829 11.7917 20 12.1088 20 12.5V19.5833C20 19.9591 19.8509 20.3195 19.5852 20.5852C19.3195 20.8509 18.9591 21 18.5833 21H4.41667C4.04094 21 3.68048 20.8509 3.4148 20.5852C3.14912 20.3195 3 19.9591 3 19.5833Z" fill="black"/>
                        </svg>

                      </i>
                      <div>
                        <div class="sm:text-fs16 text-fs14 font-semibold">วันที่รับสมัคร</div>
                        <div class="text-fs16 font-normal">
                          <?php echo esc_html( lc_thai_short_date($reg_start) ); ?>
                          <?php if ($reg_end): ?> - <?php echo esc_html( lc_thai_short_date($reg_end) ); ?><?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if ($start_date || $end_date): ?>
                    <div class="bg-[#DEF6EE] rounded-lg py-2 px-4 max-sm:flex gap-3">
                      <i class="block icon-calendar-dot w-6 mb-1 max-sm:self-baseline">
                        <svg class="w-full h-full" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5.33333 7.25V19.75H18.6667V7.25H5.33333ZM20 19.75C20 20.4404 19.403 21 18.6667 21H5.33333C4.59695 21 4 20.4404 4 19.75V7.25C4 6.55964 4.59695 6 5.33333 6H18.6667C19.403 6 20 6.55964 20 7.25V19.75Z" fill="black"/>
                        <path d="M16 5V8" stroke="black" stroke-width="1.3125" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 5V8" stroke="black" stroke-width="1.3125" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5 10H19" stroke="black" stroke-width="1.3125" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 15C12.5523 15 13 14.5523 13 14C13 13.4477 12.5523 13 12 13C11.4477 13 11 13.4477 11 14C11 14.5523 11.4477 15 12 15Z" fill="black"/>
                        <path d="M16 15C16.5523 15 17 14.5523 17 14C17 13.4477 16.5523 13 16 13C15.4477 13 15 13.4477 15 14C15 14.5523 15.4477 15 16 15Z" fill="black"/>
                        <path d="M8 18C8.55228 18 9 17.5523 9 17C9 16.4477 8.55228 16 8 16C7.44772 16 7 16.4477 7 17C7 17.5523 7.44772 18 8 18Z" fill="black"/>
                        <path d="M12 18C12.5523 18 13 17.5523 13 17C13 16.4477 12.5523 16 12 16C11.4477 16 11 16.4477 11 17C11 17.5523 11.4477 18 12 18Z" fill="black"/>
                        <path d="M16 18C16.5523 18 17 17.5523 17 17C17 16.4477 16.5523 16 16 16C15.4477 16 15 16.4477 15 17C15 17.5523 15.4477 18 16 18Z" fill="black"/>
                        </svg>
                      </i>
                      <div>
                        <div class="sm:text-fs16 text-fs14 font-semibold">วันที่เรียน</div>
                        <div class="text-fs16 font-normal">
                          <?php echo esc_html( lc_thai_short_date($start_date) ); ?>
                          <?php if ($end_date): ?> - <?php echo esc_html( lc_thai_short_date($end_date) ); ?><?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if ($time_period): ?>
                    <div class="sm:col-span-2 bg-[#DEF6EE] rounded-lg py-2 px-4 ">
                      <i class="block icon-clock w-6 mb-1 max-sm:self-baseline">
                        <svg class="w-full h-full" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_4082_8892)">
                        <path d="M20 11.75C20 7.19365 16.3063 3.5 11.75 3.5C7.19365 3.5 3.5 7.19365 3.5 11.75C3.5 16.3063 7.19365 20 11.75 20C16.3063 20 20 16.3063 20 11.75ZM21.5 11.75C21.5 17.1348 17.1348 21.5 11.75 21.5C6.36522 21.5 2 17.1348 2 11.75C2 6.36522 6.36522 2 11.75 2C17.1348 2 21.5 6.36522 21.5 11.75Z" fill="black"/>
                        <path d="M11 6.5C11 6.08579 11.3358 5.75 11.75 5.75C12.1642 5.75 12.5 6.08579 12.5 6.5V11H17C17.4142 11 17.75 11.3358 17.75 11.75C17.75 12.1642 17.4142 12.5 17 12.5H11.75C11.3358 12.5 11 12.1642 11 11.75V6.5Z" fill="black"/>
                        </g>
                        <defs>
                        <clipPath id="clip0_4082_8892">
                        <rect width="24" height="24" fill="white"/>
                        </clipPath>
                        </defs>
                        </svg>

                      </i>
                      <div>
                        <div class="sm:text-fs16 text-fs14 font-semibold">ช่วงเวลาเรียน</div>
                        <div class="text-fs16 font-normal">
                          <?php echo esc_html($time_period); ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if ($session_details): ?>
                    <div class="sm:col-span-2 bg-[#DEF6EE] rounded-lg py-2 px-4 ">
                      <div>
                        <div class="sm:text-fs16 text-fs14 font-semibold">ข้อมูลเพิ่มเติม</div>
                        <div class="text-fs16 font-normal">
                          <?php echo wp_kses_post($session_details); ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                </div>

                <?php if ($apply_url): ?>
                  <a href="<?php echo esc_url($apply_url); ?>" target="_blank"
                     class="my-2 bg-primary rounded-full block text-white text-center text-fs18 font-semibold py-2 px-4 hover:bg-primary-hover transition-colors">
                    สมัครเรียน
                  </a>
                <?php endif; ?>

                <?php if ($i < count($sids) - 1): ?>
                  <div class="border-t my-4"></div>
                <?php endif; ?>

              <?php endforeach; ?>

              <?php if ($address || $phone || $facebook_link || $map_url): ?>
                <div class="mt-1 inline-block">
                  <div class="sm:text-fs16 text-fs14 font-semibold">สอบถามข้อมูลเพิ่มเติม</div>

                  <ul class="text-fs14 font-normal mt-2 space-y-2">

                    <?php if ($address): ?>
                      <li class="flex gap-2 items-start">
                        <svg class="shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="#00744B">
                          <path d="M12 22s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11z"/>
                          <circle cx="12" cy="11" r="3" fill="#fff"/>
                        </svg>
                        <span><?php echo esc_html($address); ?></span>
                      </li>
                    <?php endif; ?>

                    <?php if ($phone): ?>
                      <li class="flex gap-2 items-center">
                        <svg class="shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="#00744B">
                          <path d="M6.6 10.8c1.6 3.1 3.5 5 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3
                                   1.2.4 2.6.7 4 .7.6 0 1 .4 1 1V21c0 .6-.4 1-1 1
                                   C10.1 22 2 13.9 2 3c0-.6.4-1 1-1h3.8c.6 0 1 .4 1 1z"/>
                        </svg>
                        <a href="tel:<?php echo esc_attr($phone); ?>" class="underline font-semibold">
                          <?php echo esc_html($phone); ?>
                        </a>
                      </li>
                    <?php endif; ?>

                    <?php if ($facebook_link): ?>
                      <li class="flex gap-2 items-center">
                        <svg class="shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="#00744B">
                          <path d="M22 12a10 10 0 1 0-11.6 9.9v-7H8v-2.9h2.4V9.8
                                   c0-2.4 1.4-3.7 3.6-3.7 1 0 2 .2 2 .2v2.2h-1.1
                                   c-1.1 0-1.4.7-1.4 1.4v1.7H16l-.4 2.9h-2.5v7z"/>
                        </svg>
                        <a href="<?php echo esc_url($facebook_link); ?>" target="_blank" class="underline font-semibold">
                          Facebook
                        </a>
                      </li>
                    <?php endif; ?>

                    <?php if ($map_url): ?>
                      <li class="flex gap-2 items-center">
                        <svg class="shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="#00744B">
                          <path d="M12 22s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11z"/>
                        </svg>
                        <a href="<?php echo esc_url($map_url); ?>" target="_blank" class="underline font-semibold">
                          เปิด Google Maps
                        </a>
                      </li>
                    <?php endif; ?>

                  </ul>

                  <a href="<?php echo esc_url($location_map_page_url); ?>" target="_blank" rel="noopener"
                     class="mt-3 inline-flex items-center justify-center gap-2 bg-white text-primary border border-primary/30 rounded-full text-fs14 font-semibold py-2 px-4 hover:bg-slate-50 transition-colors">
                    ดูสถานที่นี้ในแผนที่แหล่งเรียนรู้
                  </a>
                </div>
              <?php endif; ?>

            </div>
          </div>

        </div>

      <?php endforeach; ?>
    <?php endif; ?>

  </div>

  <div class="mt-6 flex justify-center">
    <button
      type="button"
      data-lc-course-edit-trigger="1"
      data-course-id="<?php echo esc_attr((string) get_the_ID()); ?>"
      data-course-title="<?php echo esc_attr((string) get_the_title()); ?>"
      class="inline-flex items-center justify-center gap-2 bg-white text-primary border border-primary/30 rounded-full text-fs16 font-semibold py-2 px-5 hover:bg-slate-50 transition-colors"
    >
      แจ้งแก้ไขข้อมูลคอร์ส
    </button>
  </div>
</div>
