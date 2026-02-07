<?php
$ctx = get_query_var('ctx');
if (empty($ctx) || !is_array($ctx)) { echo '<!-- modal-style: missing ctx -->'; return; }
$color = !empty($ctx['cat']['final_color']) ? $ctx['cat']['final_color'] : '#00744B';
?>

<style>
  [data-modal-content="modal-course"] .modal-course-gradient{
    background: linear-gradient(0deg, #fcfcfc 10%, <?php echo esc_attr($color); ?> 45%) !important;
    opacity: 0.2;
  }
</style>
