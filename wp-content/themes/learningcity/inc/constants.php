<?php

if (!defined('CUSTOM_VERSION')) {
    define('CUSTOM_VERSION', '0.1.00');
}

define('VITE_THEME_ASSETS_DIR', get_template_directory_uri() . '/dist');
define('VITE_THEME_MANIFEST_PATH', get_template_directory() . '/dist/.vite/manifest.json');

define('THEME_URI', get_template_directory_uri());
