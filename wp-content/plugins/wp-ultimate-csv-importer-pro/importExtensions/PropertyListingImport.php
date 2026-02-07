<?php
/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

class PropertyListingImport {
	private static $product_bundle_meta_instance = null;

	public static function getInstance() {

		if (PropertyListingImport::$product_bundle_meta_instance == null) {
			PropertyListingImport::$product_bundle_meta_instance = new PropertyListingImport;
			return PropertyListingImport::$product_bundle_meta_instance;
		}
		return PropertyListingImport::$product_bundle_meta_instance;
	}

	function set_property_values($header_array, $value_array, $maps , $post_id, $selected_type){
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();

		$post_values =$helpers_instance->get_meta_values($maps , $header_array , $value_array);

		foreach ($post_values as $meta_key => $value) {
			
				update_post_meta($post_id, $meta_key, $value[0]);

			

	
		}

	}
}
