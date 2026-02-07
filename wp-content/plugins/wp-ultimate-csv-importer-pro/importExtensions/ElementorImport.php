<?php
/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;
// require_once(__DIR__.'/../vendor/autoload.php');

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

class ElementorImport {
	private static $elementor_instance = null,$media_instance;

	public static function getInstance() {
		if (ElementorImport::$elementor_instance == null) {
			ElementorImport::$elementor_instance = new ElementorImport;
			return ElementorImport::$elementor_instance;
		}
		return ElementorImport::$elementor_instance;
	}


	public function set_elementor_value($header_array ,$value_array , $map, $post_id , $type, $hash_key, $gmode, $templatekey){	

		$post_values = [];
		$helpers_instance = ImportHelpers::getInstance();
		$post_values = $helpers_instance->get_header_values($map , $header_array , $value_array);
		foreach ($post_values as $custom_key => $custom_value) {
			if(is_serialized($custom_value) && $custom_key != '_elementor_data'){
				$custom_value = unserialize($custom_value);		
			}
			elseif($custom_key =='_elementor_data'){
				$custom_value = wp_slash(base64_decode($custom_value));
			}
			update_post_meta($post_id, $custom_key, $custom_value);
		}

	}

	function set_elementor_values($header_array, $value_array, $map, $post_id, $type, $mode, $line_number, $hash_key) {
    global $wpdb, $core_log;
    $smackcsv_instance = SmackCSV::getInstance();
    $core_instance     = CoreFieldsImport::getInstance();
    $upload_dir        = $smackcsv_instance->create_upload_dir();
    $file_table_name   = $wpdb->prefix . "smackcsv_file_events";

    $file = $wpdb->get_results("SELECT file_name,total_rows FROM $file_table_name WHERE `hash_key` = '$hash_key'");
    $file_name   = $file[0]->file_name;
    $total_rows  = $file[0]->total_rows;
    $addHeader   = 1;
    $file_ext    = pathinfo($file_name, PATHINFO_EXTENSION);

    $csv_file    = $upload_dir.$hash_key.'/'.$hash_key;
    $file_handle = fopen($csv_file, 'r');

    $first_row = true;

    while (($data = fgetcsv($file_handle, 1000, ",")) !== FALSE) {
        if ($first_row) { $first_row = false; continue; }
$style   = base64_decode($data[3]); // decode JSON
$content = $data[2];

$template_data = [
    'post_title'   => $data[1],
    'post_content' => $content,
    'post_type'    => 'elementor_library',
    'post_status'  => $data[7],
    'post_date'    => $data[5],
    'post_author'  => 1,
];

$new_id = wp_insert_post($template_data);

if ($new_id) {
    update_post_meta($new_id, '_elementor_data', wp_slash($style));
    update_post_meta($new_id, '_elementor_template_type', $data[4]);
    update_post_meta($new_id, '_elementor_edit_mode', 'builder');
    update_post_meta($new_id, '_elementor_version', ELEMENTOR_VERSION);

    // regenerate CSS
    if ( class_exists('\Elementor\Core\Files\CSS\Post') ) {
        $css_file = \Elementor\Core\Files\CSS\Post::create($new_id);
        if ($css_file) { $css_file->update(); }
    }


if ( class_exists('\Elementor\Plugin') ) {
    \Elementor\Plugin::instance()->files_manager->clear_cache(); // global cache clear
}


            $core_instance->detailed_log[$line_number]['state']     = 'Inserted';
            $core_instance->detailed_log[$line_number]['webLink']   = get_permalink($new_id);
            $core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link($new_id, true);
            $core_instance->detailed_log[$line_number]['id']        = $new_id;
        }
    }
    fclose($file_handle);
    return $post_id;
}

}
