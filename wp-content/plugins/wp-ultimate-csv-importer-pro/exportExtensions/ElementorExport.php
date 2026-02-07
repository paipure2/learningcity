<?php
/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/
 namespace Smackcoders\WCSV;
 require_once(__DIR__.'/../vendor/autoload.php');

 if ( ! defined( 'ABSPATH' ) )
 exit; // Exit if accessed directly

class ElementorExport {

    public function __construct() {
        $this->templateExport();
    }

   public function templateExport() {
    $args = [
        'post_type'      => 'elementor_library',
        'posts_per_page' => -1,
    ];
    $query = new \WP_Query($args);
    $templates = $query->get_posts();

    $output = [];
    $headers = ["ID", "Template title", "Template content", "Style", "Template type", "Created time",
        "Created by", "Template status", "Category"];

    foreach ($templates as $template) {
$elementor_data = get_post_meta($template->ID, '_elementor_data', true); // JSON
        $author_data    = get_userdata($template->post_author);
        $categories     = get_the_terms($template->ID, 'elementor_library_category');

        $category_names = [];
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
        }

   $output[] = [
    'ID'              => $template->ID,
    'Template title'  => $template->post_title,
    'Template content'=> $template->post_content,
    'Style'           => base64_encode($elementor_data), // safe for CSV
    'Template type'   => get_post_meta($template->ID,'_elementor_template_type', true),
    'Created time'    => $template->post_date,
    'Created by'      => $author_data->display_name,
    'Template status' => $template->post_status,
    'Category'        => implode(', ', $category_names),
];
    }
    return $output;
}

}