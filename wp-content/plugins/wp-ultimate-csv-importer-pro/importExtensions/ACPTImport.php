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

class ACPTImport {
	private static $metabox_instance = null, $media_instance;

	public static function getInstance() {

		if (ACPTImport::$metabox_instance == null) {
			ACPTImport::$metabox_instance = new ACPTImport;
			ACPTImport::$media_instance = new MediaHandling();
			return ACPTImport::$metabox_instance;
		}
		return ACPTImport::$metabox_instance;
	}

	function set_acpt_values($header_array ,$value_array , $map, $post_id , $type,$line_number, $hash_key,$gmode,$templatekey){

		$post_values = [];
		$helpers_instance = ImportHelpers::getInstance();	
		$post_values = $helpers_instance->get_header_values($map , $header_array , $value_array);

		foreach ($post_values as $meta_key => $value) {

			update_post_meta($post_id, $meta_key, $value);




		}
	}

	public function metabox_import_function ($data_array, $pID, $header_array, $value_array, $type,$line_number, $hash_key,$gmode,$templatekey) {

	}
}
