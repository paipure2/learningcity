<?php
/*
Plugin Name: LC Admin Workflows
Description: Admin workflows for Course/Location editing tabs, session management, and course provider filters.
Version: 0.1.0
*/

if (!defined('ABSPATH')) exit;

if (!defined('LC_ADMIN_WORKFLOWS_PLUGIN_ACTIVE')) {
    define('LC_ADMIN_WORKFLOWS_PLUGIN_ACTIVE', true);
}

/* =========================================================
 * [ADMIN COURSE] Tabs on single edit screen
 * - Tab 1: Course details (all boxes except sessions box)
 * - Tab 2: Sessions in this course (sessions box only)
 * ========================================================= */
add_action('admin_enqueue_scripts', function ($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'course') return;

    $css = '
      .lc-course-tabs { margin: 12px 0 14px; display: flex; gap: 8px; }
      .lc-course-tab-btn {
        border: 1px solid #ccd0d4;
        background: #fff;
        border-radius: 6px;
        padding: 8px 12px;
        cursor: pointer;
        font-weight: 600;
      }
      .lc-course-tab-btn.is-active {
        background: #2271b1;
        color: #fff;
        border-color: #2271b1;
      }
    ';
    wp_register_style('lc-course-admin-tabs', false);
    wp_enqueue_style('lc-course-admin-tabs');
    wp_add_inline_style('lc-course-admin-tabs', $css);

    $js = <<<'JS'
      (function($){
        $(function(){
          const $sessionsBox = $("#lc_course_sessions_list");
          if (!$sessionsBox.length) return;

          const $permalinkBox = $("#edit-slug-box");
          const $titleDiv = $("#titlediv");

          const $tabs = $(
            '<div class="lc-course-tabs">' +
              '<button type="button" class="lc-course-tab-btn is-active" data-tab="details">รายละเอียดคอร์ส</button>' +
              '<button type="button" class="lc-course-tab-btn" data-tab="sessions">Sessions ในคอร์สนี้</button>' +
            '</div>'
          );
          if ($permalinkBox.length) {
            $permalinkBox.after($tabs);
          } else if ($titleDiv.length) {
            $titleDiv.after($tabs);
          } else {
            $("#wpbody-content .wrap h1").first().after($tabs);
          }

          const $allBoxes = $("#poststuff .postbox");
          const $pinnedAlways = $("#submitdiv, #course_last_updated_info, #postimagediv");

          function setTab(tab) {
            const isSessions = tab === "sessions";

            if (isSessions) {
              $allBoxes.hide();
              $pinnedAlways.show();
              $sessionsBox.show();
            } else {
              $allBoxes.show();
              $sessionsBox.hide();
            }

            $tabs.find(".lc-course-tab-btn")
              .removeClass("is-active")
              .filter('[data-tab="' + tab + '"]')
              .addClass("is-active");
          }

          // default: details
          setTab("details");

          $tabs.on("click", ".lc-course-tab-btn", function(){
            const tab = $(this).data("tab");
            setTab(tab);
          });
        });
      })(jQuery);
    JS;
    wp_add_inline_script('jquery', $js, 'after');
});

/* =========================================================
 * [ADMIN LOCATION] Courses in this location + tabs
 * ========================================================= */
add_action('add_meta_boxes_location', function () {
    add_meta_box(
        'lc_location_courses_list',
        'Courses ใน Location นี้',
        'lc_render_location_courses_metabox',
        'location',
        'normal',
        'default'
    );
});

function lc_get_location_type_term_slugs($location_id) {
    $location_id = (int) $location_id;
    if (!$location_id || !taxonomy_exists('location-type')) return [];

    $slugs = wp_get_post_terms($location_id, 'location-type', ['fields' => 'slugs']);
    if (is_wp_error($slugs) || !is_array($slugs)) return [];

    return array_values(array_filter(array_map('sanitize_title', $slugs)));
}

function lc_render_location_session_row_html($sid) {
    $sid = (int) $sid;
    if (!$sid || get_post_type($sid) !== 'session') return '';

    $course_id = function_exists('lc_get_course_id_from_session')
        ? (int) lc_get_course_id_from_session($sid)
        : (int) get_post_meta($sid, 'course', true);
    $course_title = $course_id ? get_the_title($course_id) : 'ไม่ระบุคอร์ส';

    $status = get_post_status($sid);
    $edit_url = get_edit_post_link($sid);

    $is_draft = ($status === 'draft');
    $next_status = $is_draft ? 'publish' : 'draft';
    $toggle_label = $is_draft ? 'Publish' : 'Unpublish';

    ob_start();
    echo '<tr data-session-id="' . esc_attr($sid) . '">';
    echo '<td>' . esc_html($course_title);
    echo ' <small class="lc-session-status" style="color:#666;">(' . esc_html($status ?: '-') . ')</small>';
    echo '</td>';
    echo '<td>';
    echo '<button type="button" class="button button-small lc-location-session-quick-edit" data-session-id="' . esc_attr($sid) . '">Quick Edit</button> ';
    echo '<button type="button" class="button button-small lc-location-session-toggle-status" data-session-id="' . esc_attr($sid) . '" data-next-status="' . esc_attr($next_status) . '">' . esc_html($toggle_label) . '</button> ';
    if ($edit_url) {
        echo '<a class="button button-small" href="' . esc_url($edit_url) . '" target="_blank" rel="noopener">Edit</a>';
    }
    echo '</td>';
    echo '</tr>';
    return ob_get_clean();
}

function lc_render_location_courses_metabox($post) {
    $location_id = (int) $post->ID;
    if (!$location_id) {
        echo '<p>ไม่พบข้อมูลสถานที่</p>';
        return;
    }

    echo '<p style="margin:0 0 10px;">';
    echo '<button type="button" class="button button-primary" id="lc-location-session-add-open">+ Add Session</button>';
    echo '</p>';

    $session_ids = get_posts([
        'post_type'      => 'session',
        'post_status'    => ['publish', 'draft', 'private', 'pending', 'future'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'location',
                'value'   => (string) $location_id,
                'compare' => '=',
            ],
            [
                'key'     => 'location',
                'value'   => '"' . (string) $location_id . '"',
                'compare' => 'LIKE',
            ],
        ],
    ]);

    $has_sessions = !empty($session_ids);
    if (!$has_sessions) {
        echo '<p id="lc-location-session-empty-note">ยังไม่มีคอร์สที่ผูกกับสถานที่นี้</p>';
    }

    echo '<table id="lc-location-sessions-table" class="widefat striped" style="margin-top:8px;' . ($has_sessions ? '' : 'display:none;') . '">';
    echo '<thead><tr>';
    echo '<th>คอร์ส</th>';
    echo '<th style="width:260px;">การจัดการ</th>';
    echo '</tr></thead><tbody id="lc-location-sessions-tbody">';

    foreach ($session_ids as $sid) {
        echo lc_render_location_session_row_html($sid);
    }
    echo '</tbody></table>';

    $course_query_args = [
        'post_type'      => 'course',
        'post_status'    => ['publish', 'draft', 'private', 'pending', 'future'],
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ];

    $location_type_slugs = lc_get_location_type_term_slugs($location_id);
    if (!empty($location_type_slugs)) {
        $course_query_args['tax_query'] = [[
            'taxonomy' => 'course_provider',
            'field' => 'slug',
            'terms' => $location_type_slugs,
            'operator' => 'IN',
        ]];
    }
    $course_options = get_posts($course_query_args);
    $course_select_html = '<option value="">-- เลือกคอร์ส --</option>';
    foreach ($course_options as $c) {
        $course_select_html .= '<option value="' . (int) $c->ID . '">' . esc_html($c->post_title) . '</option>';
    }

    $js_cfg = wp_json_encode([
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lc_course_session_quick_edit'),
        'locationId' => $location_id,
    ]);

    echo '<div id="lc-location-session-quick-modal" style="display:none;"><div class="lc-location-session-quick-backdrop"></div><div class="lc-location-session-quick-dialog">';
    echo '<h3 style="margin-top:0;">Quick Edit Session</h3><p id="lc-location-session-quick-course" style="margin:0 0 12px;color:#666;"></p><input type="hidden" id="lc-location-session-quick-id" value="">';
    echo '<table class="form-table" style="margin-top:0;">';
    echo '<tr><th><label for="lc-location-quick-reg-start">วันที่รับสมัครเริ่ม</label></th><td><input type="date" id="lc-location-quick-reg-start" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-quick-reg-end">วันที่รับสมัครสิ้นสุด</label></th><td><input type="date" id="lc-location-quick-reg-end" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-quick-start-date">วันที่เรียนเริ่ม</label></th><td><input type="date" id="lc-location-quick-start-date" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-quick-end-date">วันที่เรียนสิ้นสุด</label></th><td><input type="date" id="lc-location-quick-end-date" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-quick-time-period">ช่วงเวลาเรียน</label></th><td><input type="text" id="lc-location-quick-time-period" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-quick-apply-url">ลิงก์สมัคร</label></th><td><input type="url" id="lc-location-quick-apply-url" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-quick-session-details">ข้อมูลเพิ่มเติม</label></th><td><textarea id="lc-location-quick-session-details" rows="5" class="large-text"></textarea></td></tr>';
    echo '</table><p id="lc-location-session-quick-message" style="margin:8px 0 0;"></p><div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;"><button type="button" class="button" id="lc-location-session-quick-cancel">ยกเลิก</button><button type="button" class="button button-primary" id="lc-location-session-quick-save">บันทึก</button></div></div></div>';

    echo '<div id="lc-location-session-add-modal" style="display:none;"><div class="lc-location-session-add-backdrop"></div><div class="lc-location-session-add-dialog">';
    echo '<h3 style="margin-top:0;">Add Session</h3><table class="form-table" style="margin-top:0;">';
    echo '<tr><th><label for="lc-location-add-course">คอร์ส</label></th><td><select id="lc-location-add-course" class="regular-text">' . $course_select_html . '</select></td></tr>';
    echo '<tr><th><label for="lc-location-add-post-status">สถานะ</label></th><td><select id="lc-location-add-post-status" class="regular-text"><option value="draft">draft</option><option value="publish">publish</option></select></td></tr>';
    echo '<tr><th><label for="lc-location-add-reg-start">วันที่รับสมัครเริ่ม</label></th><td><input type="date" id="lc-location-add-reg-start" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-add-reg-end">วันที่รับสมัครสิ้นสุด</label></th><td><input type="date" id="lc-location-add-reg-end" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-add-start-date">วันที่เรียนเริ่ม</label></th><td><input type="date" id="lc-location-add-start-date" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-add-end-date">วันที่เรียนสิ้นสุด</label></th><td><input type="date" id="lc-location-add-end-date" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-add-time-period">ช่วงเวลาเรียน</label></th><td><input type="text" id="lc-location-add-time-period" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-add-apply-url">ลิงก์สมัคร</label></th><td><input type="url" id="lc-location-add-apply-url" class="regular-text"></td></tr>';
    echo '<tr><th><label for="lc-location-add-session-details">ข้อมูลเพิ่มเติม</label></th><td><textarea id="lc-location-add-session-details" rows="5" class="large-text"></textarea></td></tr>';
    echo '</table><p id="lc-location-session-add-message" style="margin:8px 0 0;"></p><div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;"><button type="button" class="button" id="lc-location-session-add-cancel">ยกเลิก</button><button type="button" class="button button-primary" id="lc-location-session-add-save">สร้าง Session</button></div></div></div>';

    echo '<style>
      #lc-location-session-quick-modal, #lc-location-session-add-modal { position: fixed; inset: 0; z-index: 100000; }
      #lc-location-session-quick-modal .lc-location-session-quick-backdrop, #lc-location-session-add-modal .lc-location-session-add-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.35); }
      #lc-location-session-quick-modal .lc-location-session-quick-dialog, #lc-location-session-add-modal .lc-location-session-add-dialog { position: relative; z-index: 2; width: min(760px, calc(100vw - 24px)); max-height: calc(100vh - 24px); overflow: auto; margin: 12px auto; background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 12px 40px rgba(0,0,0,.18); }
      #lc-location-session-quick-modal .form-table th, #lc-location-session-add-modal .form-table th { width: 190px; }
    </style>';

    echo '<script>(function($){const cfg=' . $js_cfg . ';const $qm=$("#lc-location-session-quick-modal"),$am=$("#lc-location-session-add-modal"),$qmsg=$("#lc-location-session-quick-message"),$amsg=$("#lc-location-session-add-message");
      function setQ(t,e){$qmsg.text(t||"").css("color",e?"#b42318":"#2f7a1f")} function setA(t,e){$amsg.text(t||"").css("color",e?"#b42318":"#2f7a1f")}
      $("#lc-location-session-add-open").on("click",()=>{$am.show()});
      $("#lc-location-session-add-cancel,#lc-location-session-add-modal .lc-location-session-add-backdrop").on("click",()=>{$am.hide();setA("",false)});
      $("#lc-location-session-quick-cancel,#lc-location-session-quick-modal .lc-location-session-quick-backdrop").on("click",()=>{$qm.hide();setQ("",false)});
      $(document).on("click",".lc-location-session-quick-edit",function(){const sid=$(this).data("session-id"); if(!sid)return; setQ("กำลังโหลดข้อมูล...",false); $qm.show();
        $.post(cfg.ajaxUrl,{action:"lc_course_session_quick_get",nonce:cfg.nonce,session_id:sid}).done(function(res){if(!res||!res.success||!res.data){setQ("โหลดข้อมูลไม่สำเร็จ",true);return;}
          const d=res.data; $("#lc-location-session-quick-id").val(d.id||""); $("#lc-location-session-quick-course").text(d.course_title?("คอร์ส: "+d.course_title):"");
          $("#lc-location-quick-reg-start").val(d.reg_start||""); $("#lc-location-quick-reg-end").val(d.reg_end||""); $("#lc-location-quick-start-date").val(d.start_date||""); $("#lc-location-quick-end-date").val(d.end_date||"");
          $("#lc-location-quick-time-period").val(d.time_period||""); $("#lc-location-quick-apply-url").val(d.apply_url||""); $("#lc-location-quick-session-details").val(d.session_details||""); setQ("",false);
        }).fail(()=>setQ("โหลดข้อมูลไม่สำเร็จ",true));});
      $("#lc-location-session-quick-save").on("click",function(){const sid=$("#lc-location-session-quick-id").val(); if(!sid)return; $(this).prop("disabled",true); setQ("กำลังบันทึก...",false);
        $.post(cfg.ajaxUrl,{action:"lc_course_session_quick_save",nonce:cfg.nonce,session_id:sid,reg_start:$("#lc-location-quick-reg-start").val(),reg_end:$("#lc-location-quick-reg-end").val(),start_date:$("#lc-location-quick-start-date").val(),end_date:$("#lc-location-quick-end-date").val(),time_period:$("#lc-location-quick-time-period").val(),apply_url:$("#lc-location-quick-apply-url").val(),session_details:$("#lc-location-quick-session-details").val()})
         .done(function(res){if(!res||!res.success){setQ("บันทึกไม่สำเร็จ",true);return;} setQ("บันทึกสำเร็จ",false);})
         .fail(()=>setQ("บันทึกไม่สำเร็จ",true)).always(()=>$("#lc-location-session-quick-save").prop("disabled",false));});
      $(document).on("click",".lc-location-session-toggle-status",function(){const sid=$(this).data("session-id"),nextStatus=$(this).data("next-status"); if(!sid||!nextStatus)return;
        if(!window.confirm(nextStatus==="publish"?"เผยแพร่ session นี้ใช่ไหม?":"ย้าย session นี้เป็น draft ใช่ไหม?")) return; const $btn=$(this); $btn.prop("disabled",true);
        $.post(cfg.ajaxUrl,{action:"lc_course_session_toggle_status",nonce:cfg.nonce,session_id:sid,next_status:nextStatus}).done(function(res){if(!res||!res.success){window.alert("เปลี่ยนสถานะไม่สำเร็จ");return;}
          const ns=(res.data&&res.data.status)?String(res.data.status):nextStatus; const $tr=$btn.closest("tr"); $tr.find(".lc-session-status").text("(" + ns + ")");
          if(ns==="draft"){$btn.text("Publish").data("next-status","publish")}else{$btn.text("Unpublish").data("next-status","draft")}
        }).fail(()=>window.alert("เปลี่ยนสถานะไม่สำเร็จ")).always(()=>{$btn.prop("disabled",false)});});
      $("#lc-location-session-add-save").on("click",function(){const payload={action:"lc_course_session_create",nonce:cfg.nonce,location_id:cfg.locationId,course_id:$("#lc-location-add-course").val(),post_status:$("#lc-location-add-post-status").val(),reg_start:$("#lc-location-add-reg-start").val(),reg_end:$("#lc-location-add-reg-end").val(),start_date:$("#lc-location-add-start-date").val(),end_date:$("#lc-location-add-end-date").val(),time_period:$("#lc-location-add-time-period").val(),apply_url:$("#lc-location-add-apply-url").val(),session_details:$("#lc-location-add-session-details").val()};
        if(!payload.course_id){setA("กรุณาเลือกคอร์ส",true);return;} $(this).prop("disabled",true); setA("กำลังสร้าง session...",false);
        $.post(cfg.ajaxUrl,payload).done(function(res){if(!res||!res.success||!res.data||!res.data.row_html_location){setA("สร้าง session ไม่สำเร็จ",true);return;}
          $("#lc-location-sessions-tbody").prepend(res.data.row_html_location); $("#lc-location-sessions-table").show(); $("#lc-location-session-empty-note").hide();
          $("#lc-location-add-course").val(""); $("#lc-location-add-post-status").val("draft"); $("#lc-location-add-reg-start,#lc-location-add-reg-end,#lc-location-add-start-date,#lc-location-add-end-date,#lc-location-add-time-period,#lc-location-add-apply-url,#lc-location-add-session-details").val("");
          $am.hide(); setA("",false);
        }).fail(()=>setA("สร้าง session ไม่สำเร็จ",true)).always(()=>$("#lc-location-session-add-save").prop("disabled",false));});
    })(jQuery);</script>';
}

add_action('admin_enqueue_scripts', function ($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'location') return;

    $css = '
      .lc-location-tabs { margin: 12px 0 14px; display: flex; gap: 8px; }
      .lc-location-tab-btn {
        border: 1px solid #ccd0d4;
        background: #fff;
        border-radius: 6px;
        padding: 8px 12px;
        cursor: pointer;
        font-weight: 600;
      }
      .lc-location-tab-btn.is-active {
        background: #2271b1;
        color: #fff;
        border-color: #2271b1;
      }
    ';
    wp_register_style('lc-location-admin-tabs', false);
    wp_enqueue_style('lc-location-admin-tabs');
    wp_add_inline_style('lc-location-admin-tabs', $css);

    $js = <<<'JS'
      (function($){
        $(function(){
          const $coursesBox = $("#lc_location_courses_list");

          const $reportsBox = $("#acf-group_lc_location_reports").length
            ? $("#acf-group_lc_location_reports")
            : $('#poststuff .postbox[id*="group_lc_location_reports"]');

          const $contentBox = $("#postdivrich, #postdiv");
          const $permalinkBox = $("#edit-slug-box");
          const $titleDiv = $("#titlediv");

          const $tabs = $(
            '<div class="lc-location-tabs">' +
              '<button type="button" class="lc-location-tab-btn is-active" data-tab="details">Location Details</button>' +
              '<button type="button" class="lc-location-tab-btn" data-tab="courses">Courses ใน Location นี้</button>' +
              '<button type="button" class="lc-location-tab-btn" data-tab="reports">Location Reports</button>' +
            '</div>'
          );

          if ($permalinkBox.length) {
            $permalinkBox.after($tabs);
          } else if ($titleDiv.length) {
            $titleDiv.after($tabs);
          } else {
            $("#wpbody-content .wrap h1").first().after($tabs);
          }

          const $allBoxes = $("#poststuff .postbox");
          const $pinnedAlways = $("#submitdiv, #location_last_updated_info, #postimagediv");

          function setTab(tab) {
            if (tab === "courses") {
              $allBoxes.hide();
              $contentBox.hide();
              $pinnedAlways.show();
              if ($coursesBox.length) {
                $coursesBox.show();
              }
            } else if (tab === "reports") {
              $allBoxes.hide();
              $contentBox.hide();
              $pinnedAlways.show();
              if ($reportsBox.length) $reportsBox.show();
            } else {
              $allBoxes.show();
              $contentBox.show();
              $coursesBox.hide();
              if ($reportsBox.length) $reportsBox.hide();
            }

            $tabs.find(".lc-location-tab-btn")
              .removeClass("is-active")
              .filter('[data-tab="' + tab + '"]')
              .addClass("is-active");
          }

          setTab("details");

          $tabs.on("click", ".lc-location-tab-btn", function(){
            setTab($(this).data("tab"));
          });
        });
      })(jQuery);
    JS;
    wp_add_inline_script('jquery', $js, 'after');
});


/* =========================================================

/* =========================================================
 * [ADMIN COURSE] แสดง sessions ที่อยู่ใน course ปัจจุบัน
 * ========================================================= */
function lc_get_location_id_from_session($session_id) {
    $location = get_field('location', $session_id, false);

    if (is_object($location) && isset($location->ID)) return (int) $location->ID;
    if (is_numeric($location)) return (int) $location;

    if (is_array($location) && !empty($location[0])) {
        $first = $location[0];
        if (is_object($first) && isset($first->ID)) return (int) $first->ID;
        if (is_numeric($first)) return (int) $first;
    }

    return 0;
}

function lc_session_date_to_input_value($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') return '';

    if (preg_match('/^\d{8}$/', $raw)) {
        return substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2);
    }

    $ts = strtotime($raw);
    if (!$ts) return '';
    return date('Y-m-d', $ts);
}

function lc_session_input_date_to_storage($value) {
    $value = trim((string) $value);
    if ($value === '') return '';

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return str_replace('-', '', $value);
    }

    $ts = strtotime($value);
    if (!$ts) return '';
    return date('Ymd', $ts);
}

function lc_get_course_provider_term_slugs($course_id) {
    $course_id = (int) $course_id;
    if (!$course_id || !taxonomy_exists('course_provider')) return [];

    $slugs = wp_get_post_terms($course_id, 'course_provider', ['fields' => 'slugs']);
    if (is_wp_error($slugs) || !is_array($slugs)) return [];

    return array_values(array_filter(array_map('sanitize_title', $slugs)));
}

function lc_location_matches_course_provider($course_id, $location_id) {
    if (!taxonomy_exists('location-type')) return true;

    $course_terms = lc_get_course_provider_term_slugs($course_id);
    if (empty($course_terms)) return true;

    $location_terms = wp_get_post_terms((int) $location_id, 'location-type', ['fields' => 'slugs']);
    if (is_wp_error($location_terms) || !is_array($location_terms) || empty($location_terms)) return false;

    $location_terms = array_values(array_filter(array_map('sanitize_title', $location_terms)));
    return !empty(array_intersect($course_terms, $location_terms));
}

function lc_render_course_session_row_html($sid) {
    $sid = (int) $sid;
    if (!$sid || get_post_type($sid) !== 'session') return '';

    $location_id = lc_get_location_id_from_session($sid);
    $location_title = $location_id ? get_the_title($location_id) : 'ไม่ระบุสถานที่';

    $status = get_post_status($sid);
    $edit_url = get_edit_post_link($sid);

    $is_draft = ($status === 'draft');
    $next_status = $is_draft ? 'publish' : 'draft';
    $toggle_label = $is_draft ? 'Publish' : 'Unpublish';

    ob_start();
    echo '<tr data-session-id="' . esc_attr($sid) . '">';
    echo '<td>' . esc_html($location_title);
    echo ' <small class="lc-session-status" style="color:#666;">(' . esc_html($status ?: '-') . ')</small>';
    echo '</td>';
    echo '<td>';

    echo '<button type="button" class="button button-small lc-session-quick-edit" data-session-id="' . esc_attr($sid) . '">Quick Edit</button> ';
    echo '<button type="button" class="button button-small lc-session-toggle-status" data-session-id="' . esc_attr($sid) . '" data-next-status="' . esc_attr($next_status) . '">' . esc_html($toggle_label) . '</button> ';

    if ($edit_url) {
        echo '<a class="button button-small" href="' . esc_url($edit_url) . '" target="_blank" rel="noopener">Edit</a>';
    }

    echo '</td>';
    echo '</tr>';
    return ob_get_clean();
}

add_action('add_meta_boxes_course', function () {
    add_meta_box(
        'lc_course_sessions_list',
        'Sessions ในคอร์สนี้',
        'lc_render_course_sessions_metabox',
        'course',
        'normal',
        'default'
    );
});

function lc_render_course_sessions_metabox($post) {
    $course_id = (int) $post->ID;
    if (!$course_id) {
        echo '<p>ไม่พบข้อมูลคอร์ส</p>';
        return;
    }

    echo '<p style="margin:0 0 10px;">';
    echo '<button type="button" class="button button-primary" id="lc-session-add-open">+ Add Session</button>';
    echo '</p>';

    $session_ids = get_posts([
        'post_type'      => 'session',
        'post_status'    => ['publish', 'draft', 'private', 'pending', 'future'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'course',
                'value'   => (string) $course_id,
                'compare' => '=',
            ],
            [
                'key'     => 'course',
                'value'   => '"' . (string) $course_id . '"',
                'compare' => 'LIKE',
            ],
        ],
    ]);

    $has_sessions = !empty($session_ids);
    if (!$has_sessions) {
        echo '<p id="lc-session-empty-note">ยังไม่มี session ที่ผูกกับคอร์สนี้</p>';
    }

    echo '<table id="lc-course-sessions-table" class="widefat striped" style="margin-top:8px;' . ($has_sessions ? '' : 'display:none;') . '">';
    echo '<thead><tr>';
    echo '<th>สถานที่</th>';
    echo '<th style="width:260px;">การจัดการ</th>';
    echo '</tr></thead><tbody id="lc-course-sessions-tbody">';

    foreach ($session_ids as $sid) {
        echo lc_render_course_session_row_html($sid);
    }

    echo '</tbody></table>';
    $js_cfg = wp_json_encode([
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lc_course_session_quick_edit'),
        'courseId' => $course_id,
    ]);

    $location_query_args = [
        'post_type' => 'location',
        'post_status' => ['publish', 'draft', 'private'],
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ];

    $course_provider_terms = lc_get_course_provider_term_slugs($course_id);
    if (!empty($course_provider_terms)) {
        $location_query_args['tax_query'] = [[
            'taxonomy' => 'location-type',
            'field' => 'slug',
            'terms' => $course_provider_terms,
            'operator' => 'IN',
        ]];
    }

    $location_options = get_posts($location_query_args);

    $location_select_html = '<option value="">-- เลือกสถานที่ --</option>';
    foreach ($location_options as $loc) {
        $location_select_html .= '<option value="' . (int) $loc->ID . '">' . esc_html($loc->post_title) . '</option>';
    }

    echo '<div id="lc-session-quick-modal" style="display:none;">';
    echo '  <div class="lc-session-quick-backdrop"></div>';
    echo '  <div class="lc-session-quick-dialog">';
    echo '    <h3 style="margin-top:0;">Quick Edit Session</h3>';
    echo '    <p id="lc-session-quick-location" style="margin:0 0 12px;color:#666;"></p>';
    echo '    <input type="hidden" id="lc-session-quick-id" value="">';
    echo '    <table class="form-table" style="margin-top:0;">';
    echo '      <tr><th><label for="lc-quick-reg-start">วันที่รับสมัครเริ่ม</label></th><td><input type="date" id="lc-quick-reg-start" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-quick-reg-end">วันที่รับสมัครสิ้นสุด</label></th><td><input type="date" id="lc-quick-reg-end" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-quick-start-date">วันที่เรียนเริ่ม</label></th><td><input type="date" id="lc-quick-start-date" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-quick-end-date">วันที่เรียนสิ้นสุด</label></th><td><input type="date" id="lc-quick-end-date" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-quick-time-period">ช่วงเวลาเรียน</label></th><td><input type="text" id="lc-quick-time-period" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-quick-apply-url">ลิงก์สมัคร</label></th><td><input type="url" id="lc-quick-apply-url" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-quick-session-details">ข้อมูลเพิ่มเติม</label></th><td><textarea id="lc-quick-session-details" rows="5" class="large-text"></textarea></td></tr>';
    echo '    </table>';
    echo '    <p id="lc-session-quick-message" style="margin:8px 0 0;"></p>';
    echo '    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">';
    echo '      <button type="button" class="button" id="lc-session-quick-cancel">ยกเลิก</button>';
    echo '      <button type="button" class="button button-primary" id="lc-session-quick-save">บันทึก</button>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';

    echo '<div id="lc-session-add-modal" style="display:none;">';
    echo '  <div class="lc-session-add-backdrop"></div>';
    echo '  <div class="lc-session-add-dialog">';
    echo '    <h3 style="margin-top:0;">Add Session</h3>';
    echo '    <table class="form-table" style="margin-top:0;">';
    echo '      <tr><th><label for="lc-add-location">สถานที่</label></th><td><select id="lc-add-location" class="regular-text">' . $location_select_html . '</select></td></tr>';
    echo '      <tr><th><label for="lc-add-post-status">สถานะ</label></th><td><select id="lc-add-post-status" class="regular-text"><option value="draft">draft</option><option value="publish">publish</option></select></td></tr>';
    echo '      <tr><th><label for="lc-add-reg-start">วันที่รับสมัครเริ่ม</label></th><td><input type="date" id="lc-add-reg-start" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-add-reg-end">วันที่รับสมัครสิ้นสุด</label></th><td><input type="date" id="lc-add-reg-end" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-add-start-date">วันที่เรียนเริ่ม</label></th><td><input type="date" id="lc-add-start-date" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-add-end-date">วันที่เรียนสิ้นสุด</label></th><td><input type="date" id="lc-add-end-date" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-add-time-period">ช่วงเวลาเรียน</label></th><td><input type="text" id="lc-add-time-period" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-add-apply-url">ลิงก์สมัคร</label></th><td><input type="url" id="lc-add-apply-url" class="regular-text"></td></tr>';
    echo '      <tr><th><label for="lc-add-session-details">ข้อมูลเพิ่มเติม</label></th><td><textarea id="lc-add-session-details" rows="5" class="large-text"></textarea></td></tr>';
    echo '    </table>';
    echo '    <p id="lc-session-add-message" style="margin:8px 0 0;"></p>';
    echo '    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">';
    echo '      <button type="button" class="button" id="lc-session-add-cancel">ยกเลิก</button>';
    echo '      <button type="button" class="button button-primary" id="lc-session-add-save">สร้าง Session</button>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';

    echo '<style>
      #lc-session-quick-modal { position: fixed; inset: 0; z-index: 100000; }
      #lc-session-quick-modal .lc-session-quick-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.35); }
      #lc-session-quick-modal .lc-session-quick-dialog { position: relative; z-index: 2; width: min(760px, calc(100vw - 24px)); max-height: calc(100vh - 24px); overflow: auto; margin: 12px auto; background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 12px 40px rgba(0,0,0,.18); }
      #lc-session-quick-modal .form-table th { width: 190px; }
      #lc-session-add-modal { position: fixed; inset: 0; z-index: 100000; }
      #lc-session-add-modal .lc-session-add-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.35); }
      #lc-session-add-modal .lc-session-add-dialog { position: relative; z-index: 2; width: min(760px, calc(100vw - 24px)); max-height: calc(100vh - 24px); overflow: auto; margin: 12px auto; background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 12px 40px rgba(0,0,0,.18); }
      #lc-session-add-modal .form-table th { width: 190px; }
    </style>';

    echo '<script>
      (function($){
        const cfg = ' . $js_cfg . ';
        const $modal = $("#lc-session-quick-modal");
        const $msg = $("#lc-session-quick-message");
        const $addModal = $("#lc-session-add-modal");
        const $addMsg = $("#lc-session-add-message");

        function setMessage(text, isError) {
          $msg.text(text || "");
          $msg.css("color", isError ? "#b42318" : "#2f7a1f");
        }

        function setAddMessage(text, isError) {
          $addMsg.text(text || "");
          $addMsg.css("color", isError ? "#b42318" : "#2f7a1f");
        }

        function setLoading(loading) {
          $("#lc-session-quick-save").prop("disabled", loading);
          $(".lc-session-quick-edit").prop("disabled", loading);
        }

        function openModal() { $modal.show(); }
        function closeModal() {
          $modal.hide();
          setMessage("", false);
        }
        function openAddModal() { $addModal.show(); }
        function closeAddModal() {
          $addModal.hide();
          setAddMessage("", false);
        }

        function fillForm(data) {
          $("#lc-session-quick-id").val(data.id || "");
          $("#lc-session-quick-location").text(data.location_title ? ("สถานที่: " + data.location_title) : "");
          $("#lc-quick-reg-start").val(data.reg_start || "");
          $("#lc-quick-reg-end").val(data.reg_end || "");
          $("#lc-quick-start-date").val(data.start_date || "");
          $("#lc-quick-end-date").val(data.end_date || "");
          $("#lc-quick-time-period").val(data.time_period || "");
          $("#lc-quick-apply-url").val(data.apply_url || "");
          $("#lc-quick-session-details").val(data.session_details || "");
        }

        $(document).on("click", ".lc-session-quick-edit", function(){
          const sid = $(this).data("session-id");
          if (!sid) return;
          setMessage("กำลังโหลดข้อมูล...", false);
          openModal();
          setLoading(true);

          $.post(cfg.ajaxUrl, {
            action: "lc_course_session_quick_get",
            nonce: cfg.nonce,
            session_id: sid
          }).done(function(res){
            if (!res || !res.success || !res.data) {
              setMessage("โหลดข้อมูลไม่สำเร็จ", true);
              return;
            }
            fillForm(res.data);
            setMessage("", false);
          }).fail(function(){
            setMessage("โหลดข้อมูลไม่สำเร็จ", true);
          }).always(function(){
            setLoading(false);
          });
        });

        $("#lc-session-quick-cancel, #lc-session-quick-modal .lc-session-quick-backdrop").on("click", function(){
          closeModal();
        });

        $("#lc-session-add-open").on("click", function(){
          openAddModal();
        });

        $("#lc-session-add-cancel, #lc-session-add-modal .lc-session-add-backdrop").on("click", function(){
          closeAddModal();
        });

        $("#lc-session-quick-save").on("click", function(){
          const sid = $("#lc-session-quick-id").val();
          if (!sid) return;

          setLoading(true);
          setMessage("กำลังบันทึก...", false);

          $.post(cfg.ajaxUrl, {
            action: "lc_course_session_quick_save",
            nonce: cfg.nonce,
            session_id: sid,
            reg_start: $("#lc-quick-reg-start").val(),
            reg_end: $("#lc-quick-reg-end").val(),
            start_date: $("#lc-quick-start-date").val(),
            end_date: $("#lc-quick-end-date").val(),
            time_period: $("#lc-quick-time-period").val(),
            apply_url: $("#lc-quick-apply-url").val(),
            session_details: $("#lc-quick-session-details").val()
          }).done(function(res){
            if (!res || !res.success) {
              setMessage("บันทึกไม่สำเร็จ", true);
              return;
            }
            setMessage("บันทึกสำเร็จ", false);
          }).fail(function(){
            setMessage("บันทึกไม่สำเร็จ", true);
          }).always(function(){
            setLoading(false);
          });
        });

        $(document).on("click", ".lc-session-toggle-status", function(){
          const sid = $(this).data("session-id");
          const nextStatus = $(this).data("next-status");
          if (!sid) return;
          if (!nextStatus) return;

          const confirmText = nextStatus === "publish"
            ? "เผยแพร่ session นี้ใช่ไหม?"
            : "ย้าย session นี้เป็น draft ใช่ไหม?";
          if (!window.confirm(confirmText)) return;

          const $btn = $(this);
          $btn.prop("disabled", true);

          $.post(cfg.ajaxUrl, {
            action: "lc_course_session_toggle_status",
            nonce: cfg.nonce,
            session_id: sid,
            next_status: nextStatus
          }).done(function(res){
            if (!res || !res.success) {
              window.alert("เปลี่ยนสถานะไม่สำเร็จ");
              return;
            }

            const $tr = $btn.closest("tr");
            const $status = $tr.find(".lc-session-status");
            const newStatus = (res.data && res.data.status) ? String(res.data.status) : nextStatus;
            if ($status.length) {
              $status.text("(" + newStatus + ")");
            }
            if (newStatus === "draft") {
              $btn.text("Publish").data("next-status", "publish");
            } else {
              $btn.text("Unpublish").data("next-status", "draft");
            }
            setMessage("อัปเดตสถานะแล้ว: " + newStatus, false);
          }).fail(function(){
            window.alert("เปลี่ยนสถานะไม่สำเร็จ");
          }).always(function(){
            $btn.prop("disabled", false);
          });
        });

        $("#lc-session-add-save").on("click", function(){
          const payload = {
            action: "lc_course_session_create",
            nonce: cfg.nonce,
            course_id: cfg.courseId,
            location_id: $("#lc-add-location").val(),
            post_status: $("#lc-add-post-status").val(),
            reg_start: $("#lc-add-reg-start").val(),
            reg_end: $("#lc-add-reg-end").val(),
            start_date: $("#lc-add-start-date").val(),
            end_date: $("#lc-add-end-date").val(),
            time_period: $("#lc-add-time-period").val(),
            apply_url: $("#lc-add-apply-url").val(),
            session_details: $("#lc-add-session-details").val()
          };

          if (!payload.location_id) {
            setAddMessage("กรุณาเลือกสถานที่", true);
            return;
          }

          $("#lc-session-add-save").prop("disabled", true);
          setAddMessage("กำลังสร้าง session...", false);

          $.post(cfg.ajaxUrl, payload).done(function(res){
            if (!res || !res.success || !res.data || !res.data.row_html) {
              setAddMessage("สร้าง session ไม่สำเร็จ", true);
              return;
            }

            $("#lc-course-sessions-tbody").prepend(res.data.row_html);
            $("#lc-course-sessions-table").show();
            $("#lc-session-empty-note").hide();

            $("#lc-add-location").val("");
            $("#lc-add-post-status").val("draft");
            $("#lc-add-reg-start").val("");
            $("#lc-add-reg-end").val("");
            $("#lc-add-start-date").val("");
            $("#lc-add-end-date").val("");
            $("#lc-add-time-period").val("");
            $("#lc-add-apply-url").val("");
            $("#lc-add-session-details").val("");

            setMessage("เพิ่ม session สำเร็จ", false);
            closeAddModal();
          }).fail(function(){
            setAddMessage("สร้าง session ไม่สำเร็จ", true);
          }).always(function(){
            $("#lc-session-add-save").prop("disabled", false);
          });
        });
      })(jQuery);
    </script>';
}

add_action('wp_ajax_lc_course_session_quick_get', function () {
    check_ajax_referer('lc_course_session_quick_edit', 'nonce');

    $session_id = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
    if (!$session_id || get_post_type($session_id) !== 'session') {
        wp_send_json_error(['message' => 'invalid_session'], 400);
    }
    if (!current_user_can('edit_post', $session_id)) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }

    $location_id = lc_get_location_id_from_session($session_id);
    $location_title = $location_id ? get_the_title($location_id) : 'ไม่ระบุสถานที่';
    $course_id = function_exists('lc_get_course_id_from_session') ? (int) lc_get_course_id_from_session($session_id) : (int) get_post_meta($session_id, 'course', true);
    $course_title = $course_id ? get_the_title($course_id) : 'ไม่ระบุคอร์ส';

    wp_send_json_success([
        'id' => $session_id,
        'location_title' => $location_title,
        'course_title' => $course_title,
        'reg_start' => lc_session_date_to_input_value(get_field('reg_start', $session_id, false)),
        'reg_end' => lc_session_date_to_input_value(get_field('reg_end', $session_id, false)),
        'start_date' => lc_session_date_to_input_value(get_field('start_date', $session_id, false)),
        'end_date' => lc_session_date_to_input_value(get_field('end_date', $session_id, false)),
        'time_period' => (string) get_field('time_period', $session_id, false),
        'apply_url' => (string) get_field('apply_url', $session_id, false),
        'session_details' => (string) get_field('session_details', $session_id, false),
    ]);
});

add_action('wp_ajax_lc_course_session_quick_save', function () {
    check_ajax_referer('lc_course_session_quick_edit', 'nonce');

    $session_id = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
    if (!$session_id || get_post_type($session_id) !== 'session') {
        wp_send_json_error(['message' => 'invalid_session'], 400);
    }
    if (!current_user_can('edit_post', $session_id)) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }

    $reg_start = lc_session_input_date_to_storage($_POST['reg_start'] ?? '');
    $reg_end = lc_session_input_date_to_storage($_POST['reg_end'] ?? '');
    $start_date = lc_session_input_date_to_storage($_POST['start_date'] ?? '');
    $end_date = lc_session_input_date_to_storage($_POST['end_date'] ?? '');
    $time_period = sanitize_text_field($_POST['time_period'] ?? '');
    $apply_url = esc_url_raw($_POST['apply_url'] ?? '');
    $session_details = sanitize_textarea_field($_POST['session_details'] ?? '');

    if (function_exists('update_field')) {
        update_field('reg_start', $reg_start, $session_id);
        update_field('reg_end', $reg_end, $session_id);
        update_field('start_date', $start_date, $session_id);
        update_field('end_date', $end_date, $session_id);
        update_field('time_period', $time_period, $session_id);
        update_field('apply_url', $apply_url, $session_id);
        update_field('session_details', $session_details, $session_id);
    } else {
        update_post_meta($session_id, 'reg_start', $reg_start);
        update_post_meta($session_id, 'reg_end', $reg_end);
        update_post_meta($session_id, 'start_date', $start_date);
        update_post_meta($session_id, 'end_date', $end_date);
        update_post_meta($session_id, 'time_period', $time_period);
        update_post_meta($session_id, 'apply_url', $apply_url);
        update_post_meta($session_id, 'session_details', $session_details);
    }

    wp_send_json_success(['ok' => true]);
});

add_action('wp_ajax_lc_course_session_toggle_status', function () {
    check_ajax_referer('lc_course_session_quick_edit', 'nonce');

    $session_id = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
    $next_status = isset($_POST['next_status']) ? sanitize_key($_POST['next_status']) : '';
    if (!$session_id || get_post_type($session_id) !== 'session') {
        wp_send_json_error(['message' => 'invalid_session'], 400);
    }
    if (!in_array($next_status, ['draft', 'publish'], true)) {
        wp_send_json_error(['message' => 'invalid_status'], 400);
    }
    if (!current_user_can('edit_post', $session_id)) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }
    if ($next_status === 'publish') {
        $ptype = get_post_type_object('session');
        $publish_cap = ($ptype && !empty($ptype->cap->publish_posts)) ? $ptype->cap->publish_posts : 'publish_posts';
        if (!current_user_can($publish_cap)) {
            wp_send_json_error(['message' => 'forbidden_publish'], 403);
        }
    }

    $updated = wp_update_post([
        'ID' => $session_id,
        'post_status' => $next_status,
    ], true);

    if (is_wp_error($updated)) {
        wp_send_json_error(['message' => 'update_failed'], 500);
    }

    wp_send_json_success(['ok' => true, 'status' => $next_status]);
});

add_action('wp_ajax_lc_course_session_create', function () {
    check_ajax_referer('lc_course_session_quick_edit', 'nonce');

    $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
    $location_id = isset($_POST['location_id']) ? (int) $_POST['location_id'] : 0;
    $post_status = isset($_POST['post_status']) ? sanitize_key($_POST['post_status']) : 'draft';

    if (!$course_id || get_post_type($course_id) !== 'course') {
        wp_send_json_error(['message' => 'invalid_course'], 400);
    }
    if (!$location_id || get_post_type($location_id) !== 'location') {
        wp_send_json_error(['message' => 'invalid_location'], 400);
    }
    if (!lc_location_matches_course_provider($course_id, $location_id)) {
        wp_send_json_error(['message' => 'location_provider_mismatch'], 400);
    }
    if (!current_user_can('edit_post', $course_id)) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }
    if (!in_array($post_status, ['draft', 'publish'], true)) {
        $post_status = 'draft';
    }

    $session_pt = get_post_type_object('session');
    $create_cap = ($session_pt && !empty($session_pt->cap->create_posts)) ? $session_pt->cap->create_posts : 'edit_posts';
    if (!current_user_can($create_cap)) {
        wp_send_json_error(['message' => 'forbidden_create'], 403);
    }
    if ($post_status === 'publish') {
        $publish_cap = ($session_pt && !empty($session_pt->cap->publish_posts)) ? $session_pt->cap->publish_posts : 'publish_posts';
        if (!current_user_can($publish_cap)) {
            $post_status = 'draft';
        }
    }

    $course_title = get_the_title($course_id);
    $location_title = get_the_title($location_id);
    $new_title = trim($course_title . ' - ' . $location_title);
    if ($new_title === '') $new_title = 'Session';

    $session_id = wp_insert_post([
        'post_type' => 'session',
        'post_status' => $post_status,
        'post_title' => $new_title,
        'post_name' => sanitize_title($new_title),
    ], true);

    if (is_wp_error($session_id) || !$session_id) {
        wp_send_json_error(['message' => 'create_failed'], 500);
    }

    $reg_start = lc_session_input_date_to_storage($_POST['reg_start'] ?? '');
    $reg_end = lc_session_input_date_to_storage($_POST['reg_end'] ?? '');
    $start_date = lc_session_input_date_to_storage($_POST['start_date'] ?? '');
    $end_date = lc_session_input_date_to_storage($_POST['end_date'] ?? '');
    $time_period = sanitize_text_field($_POST['time_period'] ?? '');
    $apply_url = esc_url_raw($_POST['apply_url'] ?? '');
    $session_details = sanitize_textarea_field($_POST['session_details'] ?? '');

    if (function_exists('update_field')) {
        update_field('course', $course_id, $session_id);
        update_field('location', $location_id, $session_id);
        update_field('reg_start', $reg_start, $session_id);
        update_field('reg_end', $reg_end, $session_id);
        update_field('start_date', $start_date, $session_id);
        update_field('end_date', $end_date, $session_id);
        update_field('time_period', $time_period, $session_id);
        update_field('apply_url', $apply_url, $session_id);
        update_field('session_details', $session_details, $session_id);
    } else {
        update_post_meta($session_id, 'course', $course_id);
        update_post_meta($session_id, 'location', $location_id);
        update_post_meta($session_id, 'reg_start', $reg_start);
        update_post_meta($session_id, 'reg_end', $reg_end);
        update_post_meta($session_id, 'start_date', $start_date);
        update_post_meta($session_id, 'end_date', $end_date);
        update_post_meta($session_id, 'time_period', $time_period);
        update_post_meta($session_id, 'apply_url', $apply_url);
        update_post_meta($session_id, 'session_details', $session_details);
    }

    if (function_exists('lc_recalc_course_flags')) {
        lc_recalc_course_flags($course_id);
    }

    wp_send_json_success([
        'ok' => true,
        'id' => (int) $session_id,
        'status' => get_post_status($session_id),
        'row_html' => lc_render_course_session_row_html($session_id),
        'row_html_location' => lc_render_location_session_row_html($session_id),
    ]);
});

// Prefill ACF field "course" when creating a new session from course metabox button.
add_filter('acf/load_value/name=course', function ($value, $post_id, $field) {
    if (!is_admin()) return $value;
    if (!empty($value)) return $value;

    global $pagenow;
    if ($pagenow !== 'post-new.php') return $value;

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'session') return $value;

    $course_id = isset($_GET['lc_course_id']) ? (int) $_GET['lc_course_id'] : 0;
    if (!$course_id) return $value;
    if (get_post_type($course_id) !== 'course') return $value;

    return $course_id;
}, 10, 3);

/* =========================================================
 * [ADMIN COURSE] Filter Course Provider ในหน้า All Courses
 * ========================================================= */
add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'course') return;
    if (!taxonomy_exists('course_provider')) return;

    $query_var = 'lc_course_provider_filter';
    $selected = isset($_GET[$query_var]) ? (int) $_GET[$query_var] : 0;

    $terms = get_terms([
        'taxonomy' => 'course_provider',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    echo '<select name="' . esc_attr($query_var) . '">';
    echo '<option value="0">All Course Providers</option>';

    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            echo '<option value="' . (int) $term->term_id . '" ' . selected($selected, (int) $term->term_id, false) . '>' . esc_html($term->name) . '</option>';
        }
    }

    echo '</select>';
});

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    global $pagenow;
    if ($pagenow !== 'edit.php') return;
    if ($query->get('post_type') !== 'course') return;

    $query_var = 'lc_course_provider_filter';
    if (empty($_GET[$query_var])) return;

    $provider_term_id = (int) $_GET[$query_var];
    if ($provider_term_id <= 0) return;

    $tax_query = $query->get('tax_query');
    if (!is_array($tax_query)) $tax_query = [];

    if (!isset($tax_query['relation'])) {
        $tax_query = array_merge(['relation' => 'AND'], $tax_query);
    }

    $tax_query[] = [
        'taxonomy' => 'course_provider',
        'field' => 'term_id',
        'terms' => [$provider_term_id],
        'operator' => 'IN',
    ];

    $query->set('tax_query', $tax_query);
}, 99999);

/* =========================================================
 * [ADMIN COURSE] Auto Featured Image from Pexels
 * ========================================================= */
function lcaw_get_option($key, $default = '') {
    $value = get_option($key, $default);
    return is_string($value) ? trim($value) : $value;
}

function lcaw_get_pexels_api_key() {
    if (defined('LCAW_PEXELS_API_KEY') && LCAW_PEXELS_API_KEY) {
        return trim((string) LCAW_PEXELS_API_KEY);
    }
    return (string) lcaw_get_option('lcaw_pexels_api_key', '');
}

add_action('admin_init', function () {
    register_setting('lcaw_media_settings', 'lcaw_pexels_api_key', [
        'type' => 'string',
        'sanitize_callback' => function ($value) {
            return trim((string) $value);
        },
        'default' => '',
    ]);

    register_setting('lcaw_media_settings', 'lcaw_pexels_auto_featured', [
        'type' => 'string',
        'sanitize_callback' => function ($value) {
            return $value === '1' ? '1' : '0';
        },
        'default' => '1',
    ]);

    register_setting('lcaw_media_settings', 'lcaw_pexels_overwrite_featured', [
        'type' => 'string',
        'sanitize_callback' => function ($value) {
            return $value === '1' ? '1' : '0';
        },
        'default' => '0',
    ]);

    register_setting('lcaw_media_settings', 'lcaw_pexels_translate_to_en', [
        'type' => 'string',
        'sanitize_callback' => function ($value) {
            return $value === '1' ? '1' : '0';
        },
        'default' => '1',
    ]);
});

add_action('admin_menu', function () {
    add_options_page(
        'LearningCity Media',
        'LearningCity Media',
        'manage_options',
        'learningcity-media-settings',
        'lcaw_render_media_settings_page'
    );
});

function lcaw_render_media_settings_page() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
      <h1>LearningCity Media Settings</h1>
      <form method="post" action="options.php">
        <?php settings_fields('lcaw_media_settings'); ?>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="lcaw_pexels_api_key">Pexels API Key</label></th>
            <td>
              <input type="text" id="lcaw_pexels_api_key" name="lcaw_pexels_api_key" class="regular-text" value="<?php echo esc_attr((string) get_option('lcaw_pexels_api_key', '')); ?>">
              <p class="description">ใช้สำหรับดึงรูปอัตโนมัติจากชื่อคอร์ส</p>
            </td>
          </tr>
          <tr>
            <th scope="row">Auto Featured (Course)</th>
            <td>
              <label>
                <input type="checkbox" name="lcaw_pexels_auto_featured" value="1" <?php checked((string) get_option('lcaw_pexels_auto_featured', '1'), '1'); ?>>
                เปิดใช้งานการตั้ง Featured Image อัตโนมัติเมื่อบันทึกคอร์ส
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row">Overwrite Existing Featured</th>
            <td>
              <label>
                <input type="checkbox" name="lcaw_pexels_overwrite_featured" value="1" <?php checked((string) get_option('lcaw_pexels_overwrite_featured', '0'), '1'); ?>>
                ทับ Featured Image เดิม (ถ้าไม่เลือก จะใส่เฉพาะคอร์สที่ยังไม่มีรูป)
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row">Translate Title to English</th>
            <td>
              <label>
                <input type="checkbox" name="lcaw_pexels_translate_to_en" value="1" <?php checked((string) get_option('lcaw_pexels_translate_to_en', '1'), '1'); ?>>
                แปลชื่อคอร์สเป็นอังกฤษก่อนค้นหา (เหมาะกับชื่อภาษาไทย)
              </label>
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
}

function lcaw_contains_thai($text) {
    return preg_match('/[\x{0E00}-\x{0E7F}]/u', (string) $text) === 1;
}

function lcaw_translate_to_english($text) {
    $text = trim((string) $text);
    if ($text === '') return '';

    $cache_key = 'lcaw_tr_en_' . md5($text);
    $cached = get_transient($cache_key);
    if (is_string($cached) && $cached !== '') return $cached;

    $url = add_query_arg([
        'client' => 'gtx',
        'sl' => 'auto',
        'tl' => 'en',
        'dt' => 't',
        'q' => $text,
    ], 'https://translate.googleapis.com/translate_a/single');

    $res = wp_remote_get($url, ['timeout' => 15]);
    if (is_wp_error($res)) return '';
    if ((int) wp_remote_retrieve_response_code($res) !== 200) return '';

    $body = wp_remote_retrieve_body($res);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data[0]) || !is_array($data[0])) return '';

    $translated = '';
    foreach ($data[0] as $chunk) {
        if (is_array($chunk) && !empty($chunk[0]) && is_string($chunk[0])) {
            $translated .= $chunk[0];
        }
    }
    $translated = trim($translated);
    if ($translated === '') return '';

    set_transient($cache_key, $translated, DAY_IN_SECONDS * 7);
    return $translated;
}

function lcaw_get_course_category_queries($course_id, $translate_to_en = true) {
    $course_id = (int) $course_id;
    if ($course_id <= 0 || !taxonomy_exists('course_category')) return [];

    $terms = wp_get_post_terms($course_id, 'course_category', ['fields' => 'names']);
    if (is_wp_error($terms) || !is_array($terms) || empty($terms)) return [];

    $queries = [];
    foreach ($terms as $name) {
        $name = trim((string) $name);
        if ($name === '') continue;

        if ($translate_to_en && lcaw_contains_thai($name)) {
            $translated = lcaw_translate_to_english($name);
            if ($translated !== '') $queries[] = $translated;
        }

        $queries[] = $name;
    }

    return array_values(array_unique(array_filter($queries)));
}

function lcaw_get_course_title_queries($course_id, $translate_to_en = true) {
    $course_id = (int) $course_id;
    if ($course_id <= 0) return [];

    $title = trim((string) get_the_title($course_id));
    if ($title === '') return [];

    $queries = [];
    if ($translate_to_en && lcaw_contains_thai($title)) {
        $translated = lcaw_translate_to_english($title);
        if ($translated !== '') $queries[] = $translated;
    }
    $queries[] = $title;

    return array_values(array_unique(array_filter($queries)));
}

function lcaw_pexels_search_first_photo($query, $api_key) {
    $query = trim((string) $query);
    $api_key = trim((string) $api_key);
    if ($query === '' || $api_key === '') return null;

    $url = add_query_arg([
        'query' => $query,
        'per_page' => 1,
        'page' => 1,
        'orientation' => 'landscape',
        'size' => 'large',
    ], 'https://api.pexels.com/v1/search');

    $res = wp_remote_get($url, [
        'timeout' => 20,
        'headers' => [
            'Authorization' => $api_key,
        ],
    ]);

    if (is_wp_error($res)) return null;
    $code = (int) wp_remote_retrieve_response_code($res);
    if ($code < 200 || $code >= 300) return null;

    $body = wp_remote_retrieve_body($res);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data['photos'][0]) || !is_array($data['photos'][0])) return null;

    $photo = $data['photos'][0];
    $src = isset($photo['src']) && is_array($photo['src']) ? $photo['src'] : [];
    $image_url = '';
    foreach (['landscape', 'large2x', 'large', 'original'] as $k) {
        if (!empty($src[$k]) && is_string($src[$k])) {
            $image_url = $src[$k];
            break;
        }
    }
    if ($image_url === '') return null;

    return [
        'image_url' => $image_url,
        'photographer' => isset($photo['photographer']) ? (string) $photo['photographer'] : '',
        'photographer_url' => isset($photo['photographer_url']) ? (string) $photo['photographer_url'] : '',
        'photo_url' => isset($photo['url']) ? (string) $photo['url'] : '',
        'pexels_id' => isset($photo['id']) ? (int) $photo['id'] : 0,
    ];
}

function lcaw_pexels_fetch_photos($query, $api_key, $per_page = 20, $page = 1) {
    $query = trim((string) $query);
    $api_key = trim((string) $api_key);
    $per_page = max(1, min(80, (int) $per_page));
    $page = max(1, (int) $page);
    if ($query === '' || $api_key === '') return [];

    $url = add_query_arg([
        'query' => $query,
        'per_page' => $per_page,
        'page' => $page,
        'orientation' => 'landscape',
        'size' => 'large',
    ], 'https://api.pexels.com/v1/search');

    $res = wp_remote_get($url, [
        'timeout' => 20,
        'headers' => [
            'Authorization' => $api_key,
        ],
    ]);

    if (is_wp_error($res)) return [];
    $code = (int) wp_remote_retrieve_response_code($res);
    if ($code < 200 || $code >= 300) return [];

    $body = wp_remote_retrieve_body($res);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data['photos']) || !is_array($data['photos'])) return [];
    return $data['photos'];
}

function lcaw_map_pexels_photo($photo) {
    if (!is_array($photo)) return null;
    $src = isset($photo['src']) && is_array($photo['src']) ? $photo['src'] : [];
    $image_url = '';
    foreach (['landscape', 'large2x', 'large', 'original'] as $k) {
        if (!empty($src[$k]) && is_string($src[$k])) {
            $image_url = $src[$k];
            break;
        }
    }
    if ($image_url === '') return null;

    return [
        'image_url' => $image_url,
        'photographer' => isset($photo['photographer']) ? (string) $photo['photographer'] : '',
        'photographer_url' => isset($photo['photographer_url']) ? (string) $photo['photographer_url'] : '',
        'photo_url' => isset($photo['url']) ? (string) $photo['url'] : '',
        'pexels_id' => isset($photo['id']) ? (int) $photo['id'] : 0,
    ];
}

function lcaw_pexels_search_alternative_photo($query, $api_key, $exclude_pexels_id = 0, $cursor = 0) {
    $exclude_pexels_id = (int) $exclude_pexels_id;
    $cursor = max(0, (int) $cursor);

    $pages = [1, 2];
    foreach ($pages as $page) {
        $photos = lcaw_pexels_fetch_photos($query, $api_key, 20, $page);
        if (empty($photos)) continue;

        $count = count($photos);
        for ($i = 0; $i < $count; $i++) {
            $idx = ($cursor + $i) % $count;
            $mapped = lcaw_map_pexels_photo($photos[$idx]);
            if (!$mapped || empty($mapped['image_url'])) continue;
            if ($exclude_pexels_id > 0 && (int) $mapped['pexels_id'] === $exclude_pexels_id) continue;
            return [
                'photo' => $mapped,
                'next_cursor' => ($idx + 1) % max(1, $count),
            ];
        }
    }

    return null;
}

function lcaw_pexels_search_mapped_photos($query, $api_key, $limit = 5, $page = 1) {
    $limit = max(1, min(20, (int) $limit));
    $photos = lcaw_pexels_fetch_photos($query, $api_key, $limit, $page);
    if (empty($photos)) return [];

    $mapped = [];
    foreach ($photos as $photo) {
        $item = lcaw_map_pexels_photo($photo);
        if (!$item || empty($item['image_url'])) continue;
        $mapped[] = $item;
        if (count($mapped) >= $limit) break;
    }
    return $mapped;
}

function lcaw_apply_pexels_photo_to_course($post_id, $photo, $query = '', $success_message = '') {
    $post_id = (int) $post_id;
    if ($post_id <= 0 || !is_array($photo) || empty($photo['image_url'])) {
        return ['status' => 'error', 'message' => 'invalid_photo', 'query' => (string) $query];
    }

    $desc = 'Featured image for course #' . (int) $post_id;
    $attachment_id = lcaw_attach_external_image_to_post((string) $photo['image_url'], $post_id, $desc);
    if ($attachment_id <= 0) {
        lcaw_set_featured_status($post_id, 'error', 'ดาวน์โหลด/แนบรูปไม่สำเร็จ', (string) $query);
        return ['status' => 'error', 'message' => 'ดาวน์โหลด/แนบรูปไม่สำเร็จ', 'query' => (string) $query];
    }

    set_post_thumbnail($post_id, $attachment_id);
    update_post_meta($post_id, '_lcaw_featured_source', 'pexels');
    update_post_meta($post_id, '_lcaw_featured_photographer', (string) ($photo['photographer'] ?? ''));
    update_post_meta($post_id, '_lcaw_featured_photographer_url', (string) ($photo['photographer_url'] ?? ''));
    update_post_meta($post_id, '_lcaw_featured_photo_url', (string) ($photo['photo_url'] ?? ''));
    update_post_meta($post_id, '_lcaw_featured_pexels_id', (int) ($photo['pexels_id'] ?? 0));
    update_post_meta($post_id, '_lcaw_featured_last_query', sanitize_text_field((string) $query));

    $msg = $success_message !== '' ? $success_message : 'ตั้ง Featured Image จาก Pexels สำเร็จ';
    lcaw_set_featured_status($post_id, 'success', $msg, (string) $query);

    return ['status' => 'success', 'message' => $msg, 'query' => (string) $query];
}

function lcaw_attach_external_image_to_post($image_url, $post_id, $desc = '') {
    if (!function_exists('media_sideload_image')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $attachment_id = media_sideload_image($image_url, (int) $post_id, $desc, 'id');
    if (is_wp_error($attachment_id)) return 0;
    return (int) $attachment_id;
}

function lcaw_set_featured_status($post_id, $status, $message, $query = '') {
    update_post_meta($post_id, '_lcaw_featured_last_status', sanitize_key((string) $status));
    update_post_meta($post_id, '_lcaw_featured_last_message', sanitize_text_field((string) $message));
    update_post_meta($post_id, '_lcaw_featured_last_query', sanitize_text_field((string) $query));
    update_post_meta($post_id, '_lcaw_featured_last_time', time());
}

function lcaw_generate_featured_for_course($post_id, $force_overwrite = false, $respect_auto_setting = true) {
    $post_id = (int) $post_id;
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'course') {
        return ['status' => 'error', 'message' => 'invalid_course', 'query' => ''];
    }

    if ($post->post_status === 'auto-draft' || $post->post_status === 'trash') {
        lcaw_set_featured_status($post_id, 'skip', 'ข้าม: สถานะโพสต์ไม่รองรับ');
        return ['status' => 'skip', 'message' => 'ข้าม: สถานะโพสต์ไม่รองรับ', 'query' => ''];
    }

    $enabled = lcaw_get_option('lcaw_pexels_auto_featured', '1') === '1';
    if ($respect_auto_setting && !$enabled) {
        lcaw_set_featured_status($post_id, 'skip', 'ปิดการทำงาน Auto Featured');
        return ['status' => 'skip', 'message' => 'ปิดการทำงาน Auto Featured', 'query' => ''];
    }

    $overwrite = $force_overwrite || (lcaw_get_option('lcaw_pexels_overwrite_featured', '0') === '1');
    if (has_post_thumbnail($post_id) && !$overwrite) {
        lcaw_set_featured_status($post_id, 'skip', 'ข้าม: มี Featured Image อยู่แล้ว');
        return ['status' => 'skip', 'message' => 'ข้าม: มี Featured Image อยู่แล้ว', 'query' => ''];
    }

    $api_key = lcaw_get_pexels_api_key();
    if ($api_key === '') {
        lcaw_set_featured_status($post_id, 'error', 'ไม่พบ Pexels API Key');
        return ['status' => 'error', 'message' => 'ไม่พบ Pexels API Key', 'query' => ''];
    }

    $use_translate = lcaw_get_option('lcaw_pexels_translate_to_en', '1') === '1';
    $queries = lcaw_get_course_category_queries($post_id, $use_translate);
    $query_source = 'course_category';
    if (empty($queries)) {
        $queries = lcaw_get_course_title_queries($post_id, $use_translate);
        $query_source = 'title';
    }
    if (empty($queries)) {
        lcaw_set_featured_status($post_id, 'error', 'ไม่พบคำค้นสำหรับหารูป (course_category/title)');
        return ['status' => 'error', 'message' => 'ไม่พบคำค้นสำหรับหารูป (course_category/title)', 'query' => ''];
    }

    $photo = null;
    $used_query = '';
    foreach ($queries as $q) {
        $photo = lcaw_pexels_search_first_photo($q, $api_key);
        if ($photo && !empty($photo['image_url'])) {
            $used_query = $q;
            break;
        }
    }
    if (!$photo || empty($photo['image_url'])) {
        $query_text = implode(' | ', $queries);
        lcaw_set_featured_status($post_id, 'error', 'ไม่พบรูปจาก Pexels (' . $query_source . ')', $query_text);
        return ['status' => 'error', 'message' => 'ไม่พบรูปจาก Pexels (' . $query_source . ')', 'query' => $query_text];
    }

    return lcaw_apply_pexels_photo_to_course(
        $post_id,
        $photo,
        $used_query,
        'ตั้ง Featured Image จาก Pexels สำเร็จ (' . $query_source . ')'
    );
}

function lcaw_generate_featured_alternative_for_course($post_id) {
    $post_id = (int) $post_id;
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'course') {
        return ['status' => 'error', 'message' => 'invalid_course', 'query' => ''];
    }

    $api_key = lcaw_get_pexels_api_key();
    if ($api_key === '') {
        lcaw_set_featured_status($post_id, 'error', 'ไม่พบ Pexels API Key');
        return ['status' => 'error', 'message' => 'ไม่พบ Pexels API Key', 'query' => ''];
    }

    $query = trim((string) get_post_meta($post_id, '_lcaw_featured_last_query', true));
    if ($query === '') {
        $use_translate = lcaw_get_option('lcaw_pexels_translate_to_en', '1') === '1';
        $queries = lcaw_get_course_category_queries($post_id, $use_translate);
        if (empty($queries)) $queries = lcaw_get_course_title_queries($post_id, $use_translate);
        $query = !empty($queries) ? (string) $queries[0] : '';
    }
    if ($query === '') {
        lcaw_set_featured_status($post_id, 'error', 'ไม่พบคำค้นสำหรับเปลี่ยนรูป');
        return ['status' => 'error', 'message' => 'ไม่พบคำค้นสำหรับเปลี่ยนรูป', 'query' => ''];
    }

    $current_pexels_id = (int) get_post_meta($post_id, '_lcaw_featured_pexels_id', true);
    $cursor = (int) get_post_meta($post_id, '_lcaw_featured_query_cursor', true);
    $picked = lcaw_pexels_search_alternative_photo($query, $api_key, $current_pexels_id, $cursor);
    if (!$picked || empty($picked['photo']['image_url'])) {
        lcaw_set_featured_status($post_id, 'error', 'ไม่พบรูปอื่นจากผลค้นหาเดิม', $query);
        return ['status' => 'error', 'message' => 'ไม่พบรูปอื่นจากผลค้นหาเดิม', 'query' => $query];
    }

    $photo = $picked['photo'];
    $result = lcaw_apply_pexels_photo_to_course(
        $post_id,
        $photo,
        $query,
        'เปลี่ยนรูปจากผลค้นหา Pexels สำเร็จ'
    );
    if (($result['status'] ?? 'error') !== 'success') {
        return $result;
    }

    update_post_meta($post_id, '_lcaw_featured_query_cursor', (int) ($picked['next_cursor'] ?? 0));
    return ['status' => 'success', 'message' => 'เปลี่ยนรูปจากผลค้นหา Pexels สำเร็จ', 'query' => $query];
}

if (!function_exists('lcaw_get_featured_status_html')) {
    function lcaw_get_featured_status_html($post_id) {
        $html = '<div class="lcaw-featured-status" style="margin-top:10px;padding-top:10px;border-top:1px solid #dcdcde;line-height:1.5;">';
        $html .= '<strong>ค้นหาภาพ Stock ฟรี</strong>';
        $ajax_nonce = wp_create_nonce('lcaw_featured_ajax_' . (int) $post_id);

        $saved_keyword = (string) get_post_meta($post_id, '_lcaw_featured_manual_keyword', true);
        $results = get_post_meta($post_id, '_lcaw_featured_manual_results', true);
        if (!is_array($results)) $results = [];
        $panel_open = (!empty($saved_keyword) || !empty($results));

        $html .= '<div class="lcaw-featured-tools" data-post-id="' . (int) $post_id . '" data-nonce="' . esc_attr($ajax_nonce) . '" data-open="' . ($panel_open ? '1' : '0') . '" style="margin-top:8px;">';
        $html .= '<button type="button" class="button button-small lcaw-btn-toggle-panel">' . ($panel_open ? 'ซ่อนแผงค้นหารูป' : 'เปิดแผงค้นหารูป') . '</button>';
        $html .= '</div>';

        $html .= '<div class="lcaw-panel" style="margin-top:12px;' . ($panel_open ? '' : 'display:none;') . '">';
        $html .= '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">';
        $html .= '<input type="text" class="lcaw-keyword" value="' . esc_attr($saved_keyword) . '" placeholder="พิมพ์ keyword เพื่อค้นหารูป" style="min-width:220px;">';
        $html .= '<button type="button" class="button button-small lcaw-btn-search">ค้นหา</button>';
        $html .= '<button type="button" class="button button-small lcaw-btn-shuffle">สุ่มใหม่</button>';
        $html .= '</div>';

        if (!empty($results)) {
            $html .= '<div style="margin-top:10px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">';
            foreach ($results as $idx => $item) {
                if (!is_array($item) || empty($item['image_url'])) continue;
                $html .= '<div style="border:1px solid #dcdcde;border-radius:6px;padding:6px;background:#fff;">';
                $html .= '<img src="' . esc_url((string) $item['image_url']) . '" alt="" style="width:100%;height:70px;object-fit:cover;border-radius:4px;">';
                $html .= '<button type="button" class="button button-small lcaw-btn-pick" data-idx="' . (int) $idx . '" style="margin-top:6px;width:100%;text-align:center;">ใช้รูปนี้</button>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '<div class="lcaw-feedback" style="margin-top:8px;font-size:12px;color:#646970;"></div>';

        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}

add_filter('admin_post_thumbnail_html', function ($content, $post_id) {
    if (!$post_id || get_post_type($post_id) !== 'course') return $content;
    if (!current_user_can('edit_post', $post_id)) return $content;
    return $content . lcaw_get_featured_status_html($post_id);
}, 20, 2);

add_action('admin_post_lcaw_remove_course_featured', function () {
    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
    if ($post_id <= 0) wp_die('Invalid post ID');
    if (!current_user_can('edit_post', $post_id)) wp_die('Forbidden');
    check_admin_referer('lcaw_remove_course_featured_' . $post_id);

    $thumb_id = (int) get_post_thumbnail_id($post_id);
    $source = (string) get_post_meta($post_id, '_lcaw_featured_source', true);

    if ($thumb_id <= 0) {
        lcaw_set_featured_status($post_id, 'skip', 'ข้าม: ไม่มี Featured Image');
        wp_safe_redirect(get_edit_post_link($post_id, 'raw'));
        exit;
    }

    delete_post_thumbnail($post_id);

    $deleted_attachment = false;
    if ($source === 'pexels') {
        // Delete attached media only when it's not used as featured image by other courses.
        $used_elsewhere = get_posts([
            'post_type'      => 'course',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'post__not_in'   => [$post_id],
            'meta_query'     => [
                [
                    'key'     => '_thumbnail_id',
                    'value'   => (string) $thumb_id,
                    'compare' => '=',
                ],
            ],
        ]);

        if (empty($used_elsewhere)) {
            $deleted_attachment = (bool) wp_delete_attachment($thumb_id, true);
        }
    }

    delete_post_meta($post_id, '_lcaw_featured_source');
    delete_post_meta($post_id, '_lcaw_featured_photographer');
    delete_post_meta($post_id, '_lcaw_featured_photographer_url');
    delete_post_meta($post_id, '_lcaw_featured_photo_url');
    delete_post_meta($post_id, '_lcaw_featured_pexels_id');

    $msg = $deleted_attachment
        ? 'ลบ Featured Image จาก Pexels สำเร็จ (ลบไฟล์แนบแล้ว)'
        : 'ลบ Featured Image จาก Pexels สำเร็จ';
    lcaw_set_featured_status($post_id, 'success', $msg);

    wp_safe_redirect(get_edit_post_link($post_id, 'raw'));
    exit;
});

// NOTE: Disabled auto-generate on save. Featured image from Pexels is now manual via button only.

add_filter('post_row_actions', function ($actions, $post) {
    if (!$post || $post->post_type !== 'course') return $actions;
    if (!current_user_can('edit_post', $post->ID)) return $actions;

    $url = wp_nonce_url(
        admin_url('admin-post.php?action=lcaw_find_course_image&post_id=' . (int) $post->ID),
        'lcaw_find_course_image_' . (int) $post->ID
    );
    $actions['lcaw_find_course_image'] = '<a href="' . esc_url($url) . '">Find Image</a>';

    return $actions;
}, 20, 2);

add_action('admin_post_lcaw_find_course_image', function () {
    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
    if ($post_id <= 0) wp_die('Invalid post ID');
    if (!current_user_can('edit_post', $post_id)) wp_die('Forbidden');

    check_admin_referer('lcaw_find_course_image_' . $post_id);

    if (has_post_thumbnail($post_id)) {
        lcaw_set_featured_status($post_id, 'skip', 'ข้าม: มี Featured Image อยู่แล้ว');
        $result = ['status' => 'skip', 'message' => 'ข้าม: มี Featured Image อยู่แล้ว'];
    } else {
        $result = lcaw_generate_featured_for_course($post_id, false, false);
    }

    $redirect_url = admin_url('post.php?post=' . (int) $post_id . '&action=edit');

    wp_safe_redirect($redirect_url);
    exit;
});

add_action('admin_post_lcaw_change_course_image', function () {
    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
    if ($post_id <= 0) wp_die('Invalid post ID');
    if (!current_user_can('edit_post', $post_id)) wp_die('Forbidden');
    check_admin_referer('lcaw_change_course_image_' . $post_id);

    lcaw_generate_featured_alternative_for_course($post_id);

    $redirect_url = admin_url('post.php?post=' . (int) $post_id . '&action=edit');

    wp_safe_redirect($redirect_url);
    exit;
});

add_action('admin_post_lcaw_search_course_image', function () {
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if ($post_id <= 0) wp_die('Invalid post ID');
    if (!current_user_can('edit_post', $post_id)) wp_die('Forbidden');
    check_admin_referer('lcaw_search_course_image_' . $post_id);

    $keyword = isset($_POST['keyword']) ? sanitize_text_field((string) $_POST['keyword']) : '';
    $keyword = trim($keyword);
    $prev_keyword = (string) get_post_meta($post_id, '_lcaw_featured_manual_keyword', true);
    $manual_page = (int) get_post_meta($post_id, '_lcaw_featured_manual_page', true);
    if ($manual_page < 1) $manual_page = 0;
    $next_page = ($keyword !== '' && $keyword === $prev_keyword) ? ($manual_page + 1) : 1;

    update_post_meta($post_id, '_lcaw_featured_manual_keyword', $keyword);

    $results = [];
    if ($keyword !== '') {
        $api_key = lcaw_get_pexels_api_key();
        if ($api_key !== '') {
            $results = lcaw_pexels_search_mapped_photos($keyword, $api_key, 5, $next_page);
            if (empty($results) && $next_page > 1) {
                $next_page = 1;
                $results = lcaw_pexels_search_mapped_photos($keyword, $api_key, 5, $next_page);
            }
        }
    }
    update_post_meta($post_id, '_lcaw_featured_manual_results', $results);
    update_post_meta($post_id, '_lcaw_featured_manual_page', $next_page);

    $redirect_url = admin_url('post.php?post=' . (int) $post_id . '&action=edit');

    wp_safe_redirect($redirect_url);
    exit;
});

add_action('admin_post_lcaw_pick_course_image', function () {
    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
    $idx = isset($_GET['idx']) ? (int) $_GET['idx'] : -1;
    if ($post_id <= 0 || $idx < 0) wp_die('Invalid request');
    if (!current_user_can('edit_post', $post_id)) wp_die('Forbidden');
    check_admin_referer('lcaw_pick_course_image_' . $post_id);

    $results = get_post_meta($post_id, '_lcaw_featured_manual_results', true);
    if (!is_array($results) || empty($results[$idx]) || !is_array($results[$idx])) {
        wp_safe_redirect(get_edit_post_link($post_id, 'raw'));
        exit;
    }

    $keyword = (string) get_post_meta($post_id, '_lcaw_featured_manual_keyword', true);
    lcaw_apply_pexels_photo_to_course($post_id, $results[$idx], $keyword, 'ตั้ง Featured Image จาก Advanced Search สำเร็จ');

    $redirect_url = admin_url('post.php?post=' . (int) $post_id . '&action=edit');

    wp_safe_redirect($redirect_url);
    exit;
});

function lcaw_render_featured_box_html($post_id) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) return '';
    $thumb_id = (int) get_post_thumbnail_id($post_id);
    // _wp_post_thumbnail_html() already runs the admin_post_thumbnail_html filter.
    return _wp_post_thumbnail_html($thumb_id, $post_id);
}

add_action('wp_ajax_lcaw_ajax_find_course_image', function () {
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if ($post_id <= 0) wp_send_json_error(['message' => 'invalid_post_id'], 400);
    if (!current_user_can('edit_post', $post_id)) wp_send_json_error(['message' => 'forbidden'], 403);
    check_ajax_referer('lcaw_featured_ajax_' . $post_id);

    $result = has_post_thumbnail($post_id)
        ? ['status' => 'skip', 'message' => 'ข้าม: มี Featured Image อยู่แล้ว']
        : lcaw_generate_featured_for_course($post_id, false, false);

    wp_send_json_success([
        'result' => $result,
        'box_html' => lcaw_render_featured_box_html($post_id),
    ]);
});

add_action('wp_ajax_lcaw_ajax_change_course_image', function () {
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if ($post_id <= 0) wp_send_json_error(['message' => 'invalid_post_id'], 400);
    if (!current_user_can('edit_post', $post_id)) wp_send_json_error(['message' => 'forbidden'], 403);
    check_ajax_referer('lcaw_featured_ajax_' . $post_id);

    $result = lcaw_generate_featured_alternative_for_course($post_id);
    wp_send_json_success([
        'result' => $result,
        'box_html' => lcaw_render_featured_box_html($post_id),
    ]);
});

add_action('wp_ajax_lcaw_ajax_search_course_image', function () {
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if ($post_id <= 0) wp_send_json_error(['message' => 'invalid_post_id'], 400);
    if (!current_user_can('edit_post', $post_id)) wp_send_json_error(['message' => 'forbidden'], 403);
    check_ajax_referer('lcaw_featured_ajax_' . $post_id);

    $input_keyword = isset($_POST['keyword']) ? sanitize_text_field((string) wp_unslash($_POST['keyword'])) : '';
    $input_keyword = trim($input_keyword);
    $mode = isset($_POST['mode']) ? sanitize_key((string) wp_unslash($_POST['mode'])) : 'reset';

    $prev_keyword = (string) get_post_meta($post_id, '_lcaw_featured_manual_keyword', true);
    $keyword = ($input_keyword !== '') ? $input_keyword : $prev_keyword;
    $manual_page = (int) get_post_meta($post_id, '_lcaw_featured_manual_page', true);
    if ($manual_page < 1) $manual_page = 0;

    if ($keyword === '') {
        $next_page = 0;
    } elseif ($mode === 'next' && $keyword === $prev_keyword) {
        $next_page = $manual_page + 1;
    } else {
        $next_page = 1;
    }

    update_post_meta($post_id, '_lcaw_featured_manual_keyword', $keyword);

    $results = [];
    if ($keyword !== '') {
        $api_key = lcaw_get_pexels_api_key();
        if ($api_key !== '') {
            $results = lcaw_pexels_search_mapped_photos($keyword, $api_key, 6, max(1, $next_page));
            if (empty($results) && $next_page > 1) {
                $next_page = 1;
                $results = lcaw_pexels_search_mapped_photos($keyword, $api_key, 6, $next_page);
            }
        }
    }
    update_post_meta($post_id, '_lcaw_featured_manual_results', $results);
    update_post_meta($post_id, '_lcaw_featured_manual_page', $next_page);

    $count = is_array($results) ? count($results) : 0;
    wp_send_json_success([
        'result' => ['status' => 'success', 'message' => 'พบรูป ' . (int) $count . ' รูป', 'query' => $keyword],
        'box_html' => lcaw_render_featured_box_html($post_id),
    ]);
});

add_action('wp_ajax_lcaw_ajax_pick_course_image', function () {
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $idx = isset($_POST['idx']) ? (int) $_POST['idx'] : -1;
    if ($post_id <= 0 || $idx < 0) wp_send_json_error(['message' => 'invalid_request'], 400);
    if (!current_user_can('edit_post', $post_id)) wp_send_json_error(['message' => 'forbidden'], 403);
    check_ajax_referer('lcaw_featured_ajax_' . $post_id);

    $results = get_post_meta($post_id, '_lcaw_featured_manual_results', true);
    if (!is_array($results) || empty($results[$idx]) || !is_array($results[$idx])) {
        wp_send_json_error(['message' => 'invalid_result'], 400);
    }
    $keyword = (string) get_post_meta($post_id, '_lcaw_featured_manual_keyword', true);
    $result = lcaw_apply_pexels_photo_to_course($post_id, $results[$idx], $keyword, 'ตั้ง Featured Image จาก Advanced Search สำเร็จ');

    wp_send_json_success([
        'result' => $result,
        'box_html' => lcaw_render_featured_box_html($post_id),
    ]);
});

add_action('wp_ajax_lcaw_ajax_bulk_search_images', function () {
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'forbidden'], 403);
    check_ajax_referer('lcaw_bulk_modal_nonce');

    $keyword = isset($_POST['keyword']) ? sanitize_text_field((string) wp_unslash($_POST['keyword'])) : '';
    $keyword = trim($keyword);
    if ($keyword === '') {
        wp_send_json_error(['message' => 'กรุณากรอก keyword'], 400);
    }

    $api_key = lcaw_get_pexels_api_key();
    if ($api_key === '') {
        wp_send_json_error(['message' => 'ไม่พบ Pexels API Key'], 400);
    }

    $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
    if ($page < 1) $page = 1;
    $results = lcaw_pexels_search_mapped_photos($keyword, $api_key, 5, $page);
    if (empty($results) && $page > 1) {
        $page = 1;
        $results = lcaw_pexels_search_mapped_photos($keyword, $api_key, 5, $page);
    }

    wp_send_json_success([
        'keyword' => $keyword,
        'page' => $page,
        'results' => $results,
    ]);
});

add_action('wp_ajax_lcaw_ajax_bulk_apply_image', function () {
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'forbidden'], 403);
    check_ajax_referer('lcaw_bulk_modal_nonce');

    $post_ids = isset($_POST['post_ids']) ? (array) $_POST['post_ids'] : [];
    $post_ids = array_values(array_filter(array_map('intval', $post_ids)));
    if (empty($post_ids)) {
        wp_send_json_error(['message' => 'ไม่ได้เลือกคอร์ส'], 400);
    }

    $keyword = isset($_POST['keyword']) ? sanitize_text_field((string) wp_unslash($_POST['keyword'])) : '';
    $photo = [
        'image_url' => isset($_POST['image_url']) ? esc_url_raw((string) wp_unslash($_POST['image_url'])) : '',
        'photographer' => isset($_POST['photographer']) ? sanitize_text_field((string) wp_unslash($_POST['photographer'])) : '',
        'photographer_url' => isset($_POST['photographer_url']) ? esc_url_raw((string) wp_unslash($_POST['photographer_url'])) : '',
        'photo_url' => isset($_POST['photo_url']) ? esc_url_raw((string) wp_unslash($_POST['photo_url'])) : '',
        'pexels_id' => isset($_POST['pexels_id']) ? (int) $_POST['pexels_id'] : 0,
    ];
    if ($photo['image_url'] === '') {
        wp_send_json_error(['message' => 'ข้อมูลรูปไม่ครบ'], 400);
    }

    $success = 0;
    $skip = 0;
    $error = 0;
    $error_messages = [];

    foreach ($post_ids as $post_id) {
        if (!current_user_can('edit_post', $post_id)) {
            $error++;
            continue;
        }
        if (get_post_type($post_id) !== 'course') {
            $skip++;
            continue;
        }

        $result = lcaw_apply_pexels_photo_to_course($post_id, $photo, $keyword, 'ตั้ง Featured Image จาก Bulk สำเร็จ');
        $status = isset($result['status']) ? (string) $result['status'] : 'error';
        if ($status === 'success') {
            $success++;
        } elseif ($status === 'skip') {
            $skip++;
        } else {
            $error++;
            $msg = isset($result['message']) ? sanitize_text_field((string) $result['message']) : 'unknown_error';
            if ($msg === '') $msg = 'unknown_error';
            if (!isset($error_messages[$msg])) $error_messages[$msg] = 0;
            $error_messages[$msg]++;
        }
    }

    $error_summary = '';
    if (!empty($error_messages)) {
        arsort($error_messages);
        $parts = [];
        foreach ($error_messages as $msg => $count) {
            $parts[] = $msg . ' (' . (int) $count . ')';
            if (count($parts) >= 3) break;
        }
        $error_summary = implode(' | ', $parts);
    }

    $return_url = isset($_POST['return_url']) ? esc_url_raw((string) wp_unslash($_POST['return_url'])) : '';
    $default_return_url = add_query_arg(['post_type' => 'course'], admin_url('edit.php'));
    if ($return_url === '' || strpos($return_url, '/wp-admin/edit.php') === false) {
        $return_url = $default_return_url;
    }
    $return_url = remove_query_arg([
        'lcaw_bulk_done',
        'lcaw_bulk_success',
        'lcaw_bulk_skip',
        'lcaw_bulk_error',
        'lcaw_bulk_error_summary',
    ], $return_url);

    $redirect_url = add_query_arg([
        'post_type' => 'course',
        'lcaw_bulk_done' => 1,
        'lcaw_bulk_success' => $success,
        'lcaw_bulk_skip' => $skip,
        'lcaw_bulk_error' => $error,
        'lcaw_bulk_error_summary' => $error_summary,
    ], $return_url);

    wp_send_json_success([
        'success' => $success,
        'skip' => $skip,
        'error' => $error,
        'redirect_url' => $redirect_url,
    ]);
});

add_action('wp_ajax_lcaw_ajax_bulk_apply_keywords', function () {
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'forbidden'], 403);
    check_ajax_referer('lcaw_bulk_modal_nonce');

    $rows_raw = isset($_POST['rows']) ? (array) $_POST['rows'] : [];
    if (empty($rows_raw)) {
        wp_send_json_error(['message' => 'ไม่ได้ส่งรายการคอร์ส'], 400);
    }

    $api_key = lcaw_get_pexels_api_key();
    if ($api_key === '') {
        wp_send_json_error(['message' => 'ไม่พบ Pexels API Key'], 400);
    }

    $success = 0;
    $skip = 0;
    $error = 0;
    $error_messages = [];

    foreach ($rows_raw as $row_json) {
        $row = json_decode(wp_unslash((string) $row_json), true);
        if (!is_array($row)) {
            $error++;
            $msg = 'รูปแบบข้อมูลแถวไม่ถูกต้อง';
            if (!isset($error_messages[$msg])) $error_messages[$msg] = 0;
            $error_messages[$msg]++;
            continue;
        }
        $post_id = isset($row['post_id']) ? (int) $row['post_id'] : 0;
        $keyword = isset($row['keyword']) ? sanitize_text_field((string) $row['keyword']) : '';
        $keyword = trim($keyword);
        $selected_photo = [
            'image_url' => isset($row['image_url']) ? esc_url_raw((string) $row['image_url']) : '',
            'photographer' => isset($row['photographer']) ? sanitize_text_field((string) $row['photographer']) : '',
            'photographer_url' => isset($row['photographer_url']) ? esc_url_raw((string) $row['photographer_url']) : '',
            'photo_url' => isset($row['photo_url']) ? esc_url_raw((string) $row['photo_url']) : '',
            'pexels_id' => isset($row['pexels_id']) ? (int) $row['pexels_id'] : 0,
        ];
        $has_selected_photo = $selected_photo['image_url'] !== '';

        if ($post_id <= 0) {
            $skip++;
            continue;
        }
        if (!$has_selected_photo && $keyword === '') {
            $skip++;
            continue;
        }
        if (!current_user_can('edit_post', $post_id)) {
            $error++;
            continue;
        }
        if (get_post_type($post_id) !== 'course') {
            $skip++;
            continue;
        }

        $photo = $selected_photo;
        if (!$has_selected_photo) {
            $photo = lcaw_pexels_search_first_photo($keyword, $api_key);
            if (!$photo || empty($photo['image_url'])) {
                $error++;
                $msg = 'ไม่พบรูปจาก keyword';
                if (!isset($error_messages[$msg])) $error_messages[$msg] = 0;
                $error_messages[$msg]++;
                continue;
            }
        }

        $result = lcaw_apply_pexels_photo_to_course($post_id, $photo, $keyword, 'ตั้ง Featured Image จาก Bulk (ต่างกัน) สำเร็จ');
        $status = isset($result['status']) ? (string) $result['status'] : 'error';
        if ($status === 'success') {
            $success++;
        } elseif ($status === 'skip') {
            $skip++;
        } else {
            $error++;
            $msg = isset($result['message']) ? sanitize_text_field((string) $result['message']) : 'unknown_error';
            if ($msg === '') $msg = 'unknown_error';
            if (!isset($error_messages[$msg])) $error_messages[$msg] = 0;
            $error_messages[$msg]++;
        }
    }

    $error_summary = '';
    if (!empty($error_messages)) {
        arsort($error_messages);
        $parts = [];
        foreach ($error_messages as $msg => $count) {
            $parts[] = $msg . ' (' . (int) $count . ')';
            if (count($parts) >= 3) break;
        }
        $error_summary = implode(' | ', $parts);
    }

    $return_url = isset($_POST['return_url']) ? esc_url_raw((string) wp_unslash($_POST['return_url'])) : '';
    $default_return_url = add_query_arg(['post_type' => 'course'], admin_url('edit.php'));
    if ($return_url === '' || strpos($return_url, '/wp-admin/edit.php') === false) {
        $return_url = $default_return_url;
    }
    $return_url = remove_query_arg([
        'lcaw_bulk_done',
        'lcaw_bulk_success',
        'lcaw_bulk_skip',
        'lcaw_bulk_error',
        'lcaw_bulk_error_summary',
    ], $return_url);

    $redirect_url = add_query_arg([
        'post_type' => 'course',
        'lcaw_bulk_done' => 1,
        'lcaw_bulk_success' => $success,
        'lcaw_bulk_skip' => $skip,
        'lcaw_bulk_error' => $error,
        'lcaw_bulk_error_summary' => $error_summary,
    ], $return_url);

    wp_send_json_success([
        'success' => $success,
        'skip' => $skip,
        'error' => $error,
        'redirect_url' => $redirect_url,
    ]);
});

add_action('wp_ajax_lcaw_ajax_bulk_title_en', function () {
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'forbidden'], 403);
    check_ajax_referer('lcaw_bulk_modal_nonce');

    $post_ids = isset($_POST['post_ids']) ? (array) $_POST['post_ids'] : [];
    $post_ids = array_values(array_filter(array_map('intval', $post_ids)));
    if (empty($post_ids)) {
        wp_send_json_success(['titles' => []]);
    }

    $titles = [];
    foreach ($post_ids as $post_id) {
        if (!current_user_can('edit_post', $post_id)) continue;
        if (get_post_type($post_id) !== 'course') continue;

        $title = trim((string) get_the_title($post_id));
        if ($title === '') continue;
        if (!lcaw_contains_thai($title)) continue;

        $en = lcaw_translate_to_english($title);
        if ($en === '') continue;
        $titles[(string) $post_id] = $en;
    }

    wp_send_json_success([
        'titles' => $titles,
    ]);
});

add_action('admin_footer-post.php', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'course') return;
    ?>
    <script>
    (function () {
      function bindLCAW() {
        const wrap = document.querySelector('#postimagediv .lcaw-featured-status');
        if (!wrap || wrap.dataset.bound === '1') return;
        wrap.dataset.bound = '1';

        const tools = wrap.querySelector('.lcaw-featured-tools');
        if (!tools) return;
        const postId = tools.getAttribute('data-post-id');
        const nonce = tools.getAttribute('data-nonce');
        const feedback = wrap.querySelector('.lcaw-feedback');

        const setFeedback = (msg, isError) => {
          if (!feedback) return;
          feedback.textContent = msg || '';
          feedback.style.color = isError ? '#b42318' : '#646970';
        };

        const replaceFeaturedBox = (html) => {
          const inside = document.querySelector('#postimagediv .inside');
          if (!inside || !html) return;
          inside.innerHTML = html;
          bindLCAW();
        };

        const send = async (action, payload) => {
          const fd = new FormData();
          fd.append('action', action);
          fd.append('post_id', postId);
          fd.append('_ajax_nonce', nonce);
          Object.keys(payload || {}).forEach((k) => fd.append(k, String(payload[k])));

          const res = await fetch(ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            body: fd
          });
          const json = await res.json();
          if (!res.ok || !json || !json.success || !json.data) {
            throw new Error((json && json.data && json.data.message) ? json.data.message : 'request_failed');
          }
          return json.data;
        };

        const runAction = async (action, payload, loadingText) => {
          try {
            setFeedback(loadingText || 'กำลังประมวลผล...', false);
            const data = await send(action, payload);
            const msg = data.result && data.result.message ? data.result.message : 'สำเร็จ';
            setFeedback(msg, false);
            replaceFeaturedBox(data.box_html || '');
          } catch (e) {
            setFeedback(e && e.message ? e.message : 'เกิดข้อผิดพลาด', true);
          }
        };

        const panel = wrap.querySelector('.lcaw-panel');
        const btnTogglePanel = wrap.querySelector('.lcaw-btn-toggle-panel');
        if (btnTogglePanel && panel) {
          const isOpen = tools.getAttribute('data-open') === '1';
          panel.style.display = isOpen ? '' : 'none';
          btnTogglePanel.textContent = isOpen ? 'ซ่อนแผงค้นหารูป' : 'เปิดแผงค้นหารูป';
          btnTogglePanel.addEventListener('click', () => {
            const openNow = panel.style.display === 'none';
            panel.style.display = openNow ? '' : 'none';
            btnTogglePanel.textContent = openNow ? 'ซ่อนแผงค้นหารูป' : 'เปิดแผงค้นหารูป';
          });
        }

        const btnSearch = wrap.querySelector('.lcaw-btn-search');
        const keyword = wrap.querySelector('.lcaw-keyword');
        if (btnSearch) {
          btnSearch.addEventListener('click', () => {
            const kw = keyword ? keyword.value : '';
            runAction('lcaw_ajax_search_course_image', { keyword: kw, mode: 'reset' }, 'กำลังค้นหา...');
          });
        }

        const btnShuffle = wrap.querySelector('.lcaw-btn-shuffle');
        if (btnShuffle) {
          btnShuffle.addEventListener('click', () => {
            const kw = keyword ? keyword.value : '';
            runAction('lcaw_ajax_search_course_image', { keyword: kw, mode: 'next' }, 'กำลังสุ่มผลใหม่...');
          });
        }

        wrap.querySelectorAll('.lcaw-btn-pick').forEach((btn) => {
          btn.addEventListener('click', () => {
            const idx = btn.getAttribute('data-idx');
            runAction('lcaw_ajax_pick_course_image', { idx: idx }, 'กำลังตั้งรูป...');
          });
        });
      }

      document.addEventListener('DOMContentLoaded', bindLCAW);
      bindLCAW();
    })();
    </script>
    <?php
});

add_filter('bulk_actions-edit-course', function ($bulk_actions) {
    $bulk_actions['lcaw_find_images'] = 'Find Image';
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-course', function ($redirect_to, $doaction, $post_ids) {
    if ($doaction !== 'lcaw_find_images') return $redirect_to;

    $success = 0;
    $skip = 0;
    $error = 0;
    $error_messages = [];

    foreach ((array) $post_ids as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0) continue;
        if (!current_user_can('edit_post', $post_id)) {
            $error++;
            continue;
        }

        if (has_post_thumbnail($post_id)) {
            lcaw_set_featured_status($post_id, 'skip', 'ข้าม: มี Featured Image อยู่แล้ว');
            $skip++;
            continue;
        }

        $result = lcaw_generate_featured_for_course($post_id, false, false);
        $status = isset($result['status']) ? (string) $result['status'] : 'error';
        if ($status === 'success') {
            $success++;
        } elseif ($status === 'skip') {
            $skip++;
        } else {
            $error++;
            $msg = isset($result['message']) ? sanitize_text_field((string) $result['message']) : 'unknown_error';
            if ($msg === '') $msg = 'unknown_error';
            if (!isset($error_messages[$msg])) $error_messages[$msg] = 0;
            $error_messages[$msg]++;
        }
    }

    $error_summary = '';
    if (!empty($error_messages)) {
        arsort($error_messages);
        $parts = [];
        foreach ($error_messages as $msg => $count) {
            $parts[] = $msg . ' (' . (int) $count . ')';
            if (count($parts) >= 3) break;
        }
        $error_summary = implode(' | ', $parts);
    }

    return add_query_arg([
        'lcaw_bulk_done' => 1,
        'lcaw_bulk_success' => $success,
        'lcaw_bulk_skip' => $skip,
        'lcaw_bulk_error' => $error,
        'lcaw_bulk_error_summary' => $error_summary,
    ], $redirect_to);
}, 10, 3);

add_action('admin_notices', function () {
    global $pagenow;
    if (!is_admin() || $pagenow !== 'edit.php') return;
    if (empty($_GET['post_type']) || sanitize_key((string) $_GET['post_type']) !== 'course') return;

    if (isset($_GET['lcaw_bulk_done'])) {
        $success = isset($_GET['lcaw_bulk_success']) ? (int) $_GET['lcaw_bulk_success'] : 0;
        $skip = isset($_GET['lcaw_bulk_skip']) ? (int) $_GET['lcaw_bulk_skip'] : 0;
        $error = isset($_GET['lcaw_bulk_error']) ? (int) $_GET['lcaw_bulk_error'] : 0;
        $error_summary = isset($_GET['lcaw_bulk_error_summary']) ? sanitize_text_field((string) $_GET['lcaw_bulk_error_summary']) : '';
        $class = $error > 0 ? 'notice-warning' : 'notice-success';
        $message = sprintf(
            'Pexels Bulk: success %d, skipped %d, error %d',
            $success,
            $skip,
            $error
        );
        if ($error > 0 && $error_summary !== '') {
            $message .= ' | ' . $error_summary;
        }
        echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    if (!isset($_GET['lcaw_notice_status'], $_GET['lcaw_notice_message'])) return;

    $status = sanitize_key((string) $_GET['lcaw_notice_status']);
    $message = sanitize_text_field((string) $_GET['lcaw_notice_message']);
    if ($message === '') return;

    $class = 'notice-info';
    if ($status === 'success') $class = 'notice-success';
    if ($status === 'error') $class = 'notice-error';

    echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p><strong>Pexels:</strong> ' . esc_html($message) . '</p></div>';
});

add_action('admin_footer-edit.php', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'edit' || $screen->post_type !== 'course') return;
    $nonce = wp_create_nonce('lcaw_bulk_modal_nonce');
    ?>
    <style>
      .lcaw-bulk-modal { position: fixed; inset: 0; z-index: 100000; display: none; }
      .lcaw-bulk-modal.is-open { display: block; }
      .lcaw-bulk-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.45); }
      .lcaw-bulk-card { position: relative; width: min(860px, calc(100vw - 32px)); max-height: calc(100vh - 64px); overflow: auto; margin: 32px auto; background: #fff; border-radius: 12px; padding: 16px; }
      .lcaw-bulk-grid { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 8px; margin-top: 12px; }
      .lcaw-bulk-item { border: 1px solid #dcdcde; border-radius: 6px; padding: 6px; }
      .lcaw-bulk-item img { width: 100%; height: 100px; object-fit: cover; border-radius: 4px; display: block; }
      .lcaw-tabs { display:flex; gap:8px; margin-top:10px; }
      .lcaw-tab-btn.is-active { background:#2271b1; color:#fff; border-color:#2271b1; }
      .lcaw-tab-panel { margin-top:10px; }
      .lcaw-tab-panel[hidden] { display:none !important; }
      .lcaw-diff-list { margin-top:10px; display:grid; gap:8px; }
      .lcaw-diff-item { border:1px solid #dcdcde; border-radius:6px; padding:8px; background:#fff; display:grid; gap:8px; }
      .lcaw-diff-item-head { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
      .lcaw-diff-row-actions { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
      .lcaw-diff-results { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:6px; }
      .lcaw-diff-result { border:1px solid #dcdcde; border-radius:6px; padding:4px; background:#fff; }
      .lcaw-diff-result.is-selected { border-color:#2271b1; box-shadow:0 0 0 1px #2271b1 inset; }
      .lcaw-diff-result img { width:100%; height:72px; object-fit:cover; border-radius:4px; display:block; }
      .lcaw-diff-feedback { font-size:12px; color:#646970; }
    </style>
    <script>
    (function () {
      const NONCE = <?php echo wp_json_encode($nonce); ?>;
      const ACTION_KEY = 'lcaw_find_images';

      function getCheckedPostIds() {
        return Array.from(document.querySelectorAll('#the-list th.check-column input[type="checkbox"]:checked'))
          .map((el) => parseInt(el.value, 10))
          .filter((n) => Number.isFinite(n) && n > 0);
      }

      function selectedBulkAction() {
        const top = document.getElementById('bulk-action-selector-top');
        const bottom = document.getElementById('bulk-action-selector-bottom');
        const topVal = top ? top.value : '-1';
        const bottomVal = bottom ? bottom.value : '-1';
        return topVal !== '-1' ? topVal : bottomVal;
      }

      function createModal() {
        const wrap = document.createElement('div');
        wrap.className = 'lcaw-bulk-modal';
        wrap.innerHTML = `
          <div class="lcaw-bulk-backdrop"></div>
          <div class="lcaw-bulk-card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
              <h3 style="margin:0;">ค้นหาภาพ Stock ฟรี (Bulk)</h3>
              <button type="button" class="button lcaw-close">ปิด</button>
            </div>
            <p class="description">เลือกภาพ 1 รูปเพื่อใช้กับคอร์สที่ติ๊กไว้ทั้งหมด</p>
            <div class="lcaw-tabs">
              <button type="button" class="button lcaw-tab-btn is-active" data-tab="same">เหมือนกันทุกโพสต์</button>
              <button type="button" class="button lcaw-tab-btn" data-tab="diff">แตกต่างกัน</button>
            </div>
            <div class="lcaw-tab-panel lcaw-tab-same">
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="text" class="lcaw-keyword" placeholder="ค้นหาด้วย keyword ภาษาอังกฤษ" style="min-width:260px;">
                <button type="button" class="button button-primary lcaw-search">ค้นหา</button>
                <button type="button" class="button lcaw-next">สุ่มใหม่</button>
              </div>
              <div class="lcaw-feedback" style="margin-top:8px;color:#646970;"></div>
              <div class="lcaw-bulk-grid"></div>
            </div>
            <div class="lcaw-tab-panel lcaw-tab-diff" hidden>
            <div class="description">ใส่ keyword ภาษาอังกฤษแยกแต่ละโพสต์ แล้วกดตั้งรูปทั้งหมด</div>
              <div class="lcaw-diff-list"></div>
              <div style="margin-top:10px;">
                <button type="button" class="button button-primary lcaw-apply-diff">ตั้งรูปตาม keyword ที่กรอก</button>
              </div>
              <div class="lcaw-feedback-diff" style="margin-top:8px;color:#646970;"></div>
            </div>
          </div>`;
        document.body.appendChild(wrap);
        return wrap;
      }

      const modal = createModal();
      const card = modal.querySelector('.lcaw-bulk-card');
      const backdrop = modal.querySelector('.lcaw-bulk-backdrop');
      const closeBtn = modal.querySelector('.lcaw-close');
      const keywordInput = modal.querySelector('.lcaw-keyword');
      const searchBtn = modal.querySelector('.lcaw-search');
      const nextBtn = modal.querySelector('.lcaw-next');
      const feedback = modal.querySelector('.lcaw-feedback');
      const grid = modal.querySelector('.lcaw-bulk-grid');
      const feedbackDiff = modal.querySelector('.lcaw-feedback-diff');
      const diffList = modal.querySelector('.lcaw-diff-list');
      const applyDiffBtn = modal.querySelector('.lcaw-apply-diff');
      const tabButtons = modal.querySelectorAll('.lcaw-tab-btn');
      const tabSame = modal.querySelector('.lcaw-tab-same');
      const tabDiff = modal.querySelector('.lcaw-tab-diff');
      let currentPage = 1;
      let currentKeyword = '';
      let currentResults = [];
      let selectedPostIds = [];
      let selectedRows = [];

      function setFeedback(msg, isError) {
        feedback.textContent = msg || '';
        feedback.style.color = isError ? '#b42318' : '#646970';
      }
      function setFeedbackDiff(msg, isError) {
        if (!feedbackDiff) return;
        feedbackDiff.textContent = msg || '';
        feedbackDiff.style.color = isError ? '#b42318' : '#646970';
      }

      function activateTab(name) {
        tabButtons.forEach((btn) => {
          const active = btn.getAttribute('data-tab') === name;
          btn.classList.toggle('is-active', active);
        });
        if (tabSame) tabSame.hidden = name !== 'same';
        if (tabDiff) tabDiff.hidden = name !== 'diff';
      }

      function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
      }

      function getSelectedRows(postIds) {
        return postIds.map((id) => {
          const titleEl = document.querySelector(`#post-${id} .row-title`);
          const title = titleEl ? titleEl.textContent.trim() : `Course #${id}`;
          return { post_id: id, title, title_en: '', keyword: '', page: 1, results: [], selected: null, loading: false, feedback: '' };
        });
      }

      function renderDiffList() {
        if (!diffList) return;
        if (!selectedRows.length) {
          diffList.innerHTML = '<div class="description">ไม่ได้เลือกคอร์ส</div>';
          return;
        }
        diffList.innerHTML = selectedRows.map((row, idx) => `
          <div class="lcaw-diff-item">
            <div><strong>${escapeHtml(row.title || '')}</strong></div>
            ${row.title_en ? `<div class="description">(${escapeHtml(row.title_en)})</div>` : ''}
            <div class="lcaw-diff-item-head">
              <input type="text" class="lcaw-diff-keyword" data-idx="${idx}" placeholder="ค้นหาด้วย keyword ภาษาอังกฤษ" value="${escapeHtml(row.keyword || '')}" style="min-width:240px;">
              <div class="lcaw-diff-row-actions">
                <button type="button" class="button button-small lcaw-diff-search" data-idx="${idx}">${row.loading ? 'กำลังค้นหา...' : 'ค้นหา 5 รูป'}</button>
                <button type="button" class="button button-small lcaw-diff-next" data-idx="${idx}" ${row.results && row.results.length ? '' : 'disabled'}>สุ่มใหม่</button>
              </div>
            </div>
            <div class="lcaw-diff-feedback">${escapeHtml(row.feedback || '')}</div>
            <div class="lcaw-diff-results">
              ${(row.results || []).map((it, photoIdx) => `
                <div class="lcaw-diff-result ${row.selected === photoIdx ? 'is-selected' : ''}">
                  <img src="${escapeHtml(it.image_url || '')}" alt="">
                  <button type="button" class="button button-small lcaw-diff-pick" data-idx="${idx}" data-photo="${photoIdx}" style="margin-top:4px;width:100%;">${row.selected === photoIdx ? 'เลือกแล้ว' : 'ใช้รูปนี้'}</button>
                </div>
              `).join('')}
            </div>
          </div>
        `).join('');
      }

      async function searchDiffRow(idx, mode) {
        const row = selectedRows[idx];
        if (!row) return;
        const kw = String(row.keyword || '').trim();
        if (!kw) {
          row.feedback = 'กรุณากรอก keyword';
          renderDiffList();
          return;
        }
        if (mode === 'reset') row.page = 1;
        if (mode === 'next') row.page = (parseInt(row.page || 1, 10) || 1) + 1;
        row.loading = true;
        row.feedback = 'กำลังค้นหา...';
        renderDiffList();
        try {
          const data = await postAjax('lcaw_ajax_bulk_search_images', { keyword: kw, page: row.page || 1 });
          row.page = parseInt(data.page || 1, 10) || 1;
          row.results = Array.isArray(data.results) ? data.results : [];
          row.selected = null;
          row.feedback = row.results.length ? `พบรูป ${row.results.length} รูป` : 'ไม่พบรูปจาก keyword นี้';
        } catch (e) {
          row.feedback = e && e.message ? e.message : 'ค้นหารูปไม่สำเร็จ';
        } finally {
          row.loading = false;
          renderDiffList();
        }
      }

      function openModal(postIds) {
        selectedPostIds = postIds.slice();
        selectedRows = getSelectedRows(postIds);
        modal.classList.add('is-open');
        setFeedback(`เลือกแล้ว ${postIds.length} คอร์ส`, false);
        setFeedbackDiff('', false);
        activateTab('same');
        renderDiffList();
        hydrateEnglishTitles();
      }
      function closeModal() {
        modal.classList.remove('is-open');
      }

      async function hydrateEnglishTitles() {
        if (!selectedPostIds.length) return;
        try {
          const data = await postAjax('lcaw_ajax_bulk_title_en', { post_ids: selectedPostIds });
          const titles = (data && data.titles && typeof data.titles === 'object') ? data.titles : {};
          selectedRows = selectedRows.map((row) => {
            const titleEn = titles[String(row.post_id)] || '';
            return Object.assign({}, row, { title_en: titleEn });
          });
          renderDiffList();
        } catch (_) {
          // no-op: translation is optional in UI
        }
      }

      function renderResults(items) {
        currentResults = Array.isArray(items) ? items : [];
        if (!currentResults.length) {
          grid.innerHTML = '';
          setFeedback('ไม่พบรูปจาก keyword นี้', true);
          return;
        }
        grid.innerHTML = currentResults.map((it, idx) => `
          <div class="lcaw-bulk-item">
            <img src="${String(it.image_url || '').replace(/"/g, '&quot;')}" alt="">
            <button type="button" class="button button-small lcaw-pick" data-idx="${idx}" style="margin-top:6px;width:100%;">ใช้รูปนี้</button>
          </div>
        `).join('');
        grid.querySelectorAll('.lcaw-pick').forEach((btn) => {
          btn.addEventListener('click', () => {
            const idx = parseInt(btn.getAttribute('data-idx'), 10);
            const item = currentResults[idx];
            if (!item) return;
            applyBulkImage(item);
          });
        });
      }

      async function postAjax(action, payload) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('_ajax_nonce', NONCE);
        Object.keys(payload || {}).forEach((k) => {
          const v = payload[k];
          if (Array.isArray(v)) {
            v.forEach((x) => fd.append(k + '[]', String(x)));
          } else {
            fd.append(k, String(v));
          }
        });
        const res = await fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: fd });
        const json = await res.json();
        if (!res.ok || !json || !json.success || !json.data) {
          throw new Error((json && json.data && json.data.message) ? json.data.message : 'request_failed');
        }
        return json.data;
      }

      async function search(mode) {
        const kw = (keywordInput.value || '').trim();
        if (!kw) {
          setFeedback('กรุณากรอก keyword', true);
          return;
        }
        currentKeyword = kw;
        if (mode === 'reset') currentPage = 1;
        if (mode === 'next') currentPage += 1;
        setFeedback('กำลังค้นหารูป...', false);
        try {
          const data = await postAjax('lcaw_ajax_bulk_search_images', { keyword: currentKeyword, page: currentPage });
          currentPage = parseInt(data.page || 1, 10) || 1;
          renderResults(data.results || []);
          setFeedback(`พบรูป ${(data.results || []).length} รูป`, false);
        } catch (e) {
          setFeedback(e && e.message ? e.message : 'ค้นหารูปไม่สำเร็จ', true);
        }
      }

      async function applyBulkImage(item) {
        if (!selectedPostIds.length) {
          setFeedback('ไม่ได้เลือกคอร์ส', true);
          return;
        }
        setFeedback('กำลังตั้งรูปให้คอร์สที่เลือก...', false);
        try {
          const data = await postAjax('lcaw_ajax_bulk_apply_image', {
            post_ids: selectedPostIds,
            keyword: currentKeyword,
            return_url: window.location.href || '',
            image_url: item.image_url || '',
            photographer: item.photographer || '',
            photographer_url: item.photographer_url || '',
            photo_url: item.photo_url || '',
            pexels_id: item.pexels_id || 0
          });
          if (data.redirect_url) {
            window.location.href = data.redirect_url;
          } else {
            window.location.reload();
          }
        } catch (e) {
          setFeedback(e && e.message ? e.message : 'ตั้งรูปไม่สำเร็จ', true);
        }
      }

      async function applyBulkByKeywords() {
        if (!selectedRows.length) {
          setFeedbackDiff('ไม่ได้เลือกคอร์ส', true);
          return;
        }
        const inputs = Array.from(diffList.querySelectorAll('.lcaw-diff-keyword'));
        const rows = inputs.map((input) => {
          const idx = parseInt(input.getAttribute('data-idx'), 10);
          const row = selectedRows[idx] || {};
          const payloadRow = { post_id: row.post_id, keyword: (input.value || '').trim() };
          const picked = Number.isInteger(row.selected) && row.results && row.results[row.selected] ? row.results[row.selected] : null;
          if (picked && picked.image_url) {
            payloadRow.image_url = picked.image_url || '';
            payloadRow.photographer = picked.photographer || '';
            payloadRow.photographer_url = picked.photographer_url || '';
            payloadRow.photo_url = picked.photo_url || '';
            payloadRow.pexels_id = picked.pexels_id || 0;
          }
          return payloadRow;
        }).filter((row) => Number.isFinite(Number(row.post_id)) && row.post_id > 0 && (row.keyword !== '' || row.image_url));

        if (!rows.length) {
          setFeedbackDiff('กรุณากรอก keyword อย่างน้อย 1 รายการ', true);
          return;
        }
        setFeedbackDiff('กำลังตั้งรูปจาก keyword รายโพสต์...', false);
        try {
          const data = await postAjax('lcaw_ajax_bulk_apply_keywords', {
            rows: rows.map((r) => JSON.stringify(r)),
            return_url: window.location.href || ''
          });
          if (data.redirect_url) {
            window.location.href = data.redirect_url;
          } else {
            window.location.reload();
          }
        } catch (e) {
          setFeedbackDiff(e && e.message ? e.message : 'ตั้งรูปไม่สำเร็จ', true);
        }
      }

      searchBtn.addEventListener('click', () => search('reset'));
      nextBtn.addEventListener('click', () => search('next'));
      if (applyDiffBtn) applyDiffBtn.addEventListener('click', applyBulkByKeywords);
      if (diffList) {
        diffList.addEventListener('input', (e) => {
          const target = e.target;
          if (!(target instanceof HTMLInputElement) || !target.classList.contains('lcaw-diff-keyword')) return;
          const idx = parseInt(target.getAttribute('data-idx'), 10);
          if (!Number.isFinite(idx) || !selectedRows[idx]) return;
          selectedRows[idx].keyword = (target.value || '').trim();
        });
        diffList.addEventListener('click', (e) => {
          const target = e.target;
          if (!(target instanceof HTMLElement)) return;
          const searchBtnEl = target.closest('.lcaw-diff-search');
          if (searchBtnEl) {
            const idx = parseInt(searchBtnEl.getAttribute('data-idx'), 10);
            if (Number.isFinite(idx)) searchDiffRow(idx, 'reset');
            return;
          }
          const nextBtnEl = target.closest('.lcaw-diff-next');
          if (nextBtnEl) {
            const idx = parseInt(nextBtnEl.getAttribute('data-idx'), 10);
            if (Number.isFinite(idx)) searchDiffRow(idx, 'next');
            return;
          }
          const pickBtnEl = target.closest('.lcaw-diff-pick');
          if (pickBtnEl) {
            const idx = parseInt(pickBtnEl.getAttribute('data-idx'), 10);
            const photoIdx = parseInt(pickBtnEl.getAttribute('data-photo'), 10);
            if (!Number.isFinite(idx) || !selectedRows[idx]) return;
            if (!Number.isFinite(photoIdx) || !selectedRows[idx].results || !selectedRows[idx].results[photoIdx]) return;
            selectedRows[idx].selected = photoIdx;
            selectedRows[idx].feedback = 'เลือกรูปแล้ว';
            renderDiffList();
          }
        });
      }
      tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => activateTab(btn.getAttribute('data-tab')));
      });
      closeBtn.addEventListener('click', closeModal);
      backdrop.addEventListener('click', closeModal);
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

      document.querySelectorAll('#doaction, #doaction2').forEach((btn) => {
        btn.addEventListener('click', (e) => {
          const action = selectedBulkAction();
          if (action !== ACTION_KEY) return;
          const ids = getCheckedPostIds();
          if (!ids.length) return;
          e.preventDefault();
          openModal(ids);
        });
      });
    })();
    </script>
    <?php
});
