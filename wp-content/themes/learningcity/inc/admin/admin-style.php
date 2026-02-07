<?php
function custom_login_styles()
{
    echo '<style type="text/css">
    body.login{
       form {
        border:1px solid #ddd;
        border-radius:12px;
        }
    }
    body.login #login h1 {
        display:flex;
        justify-content:center;
        align-items:center;
        margin-bottom:1rem;
        padding-right:0.5rem;
        a {
            background: url("' . esc_url(get_template_directory_uri()) . '/assets/images/logo-primary.svg") center top no-repeat;
            background-size:contain;
            background-position:center;
            height: 70px;
            width: 290px;
            max-width: 100%;
            display: block;
            margin: 10px auto;
            text-indent: -9999px;
            outline: none;
            pointer-events:none;
        }
    }
</style>';
}
add_action('login_enqueue_scripts', 'custom_login_styles');