<?php

function if_add_admin_body_id_script()
{
    global $pagenow;
    $unique_id = 'admin-body-' . sanitize_html_class($pagenow);

    if (isset($_GET['post'])) {
        $unique_id .= '-' . intval($_GET['post']);
    } elseif (isset($_GET['post_type'])) {
        $unique_id .= '-' . sanitize_html_class($_GET['post_type']);
    }

?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.id = <?php echo json_encode($unique_id); ?>;
        });
    </script>
<?php
}
add_action('admin_footer', 'if_add_admin_body_id_script');
