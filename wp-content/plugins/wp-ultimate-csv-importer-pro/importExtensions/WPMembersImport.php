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

class WPMembersImport {
    private static $wpmembers_instance = null,$media_instance;

    public static function getInstance() {
		
		if (WPMembersImport::$wpmembers_instance == null) {
			WPMembersImport::$wpmembers_instance = new WPMembersImport;
			WPMembersImport::$media_instance = new MediaHandling;
			return WPMembersImport::$wpmembers_instance;
		}
		return WPMembersImport::$wpmembers_instance;
    }
    function set_wpmembers_values($header_array ,$value_array , $map, $post_id , $type, $hash_key,$gmode,$templatekey,$line_number){
		$post_values = [];
		$helpers_instance = ImportHelpers::getInstance();
		$post_values = $helpers_instance->get_header_values($map , $header_array , $value_array);
		
		$this->wpmembers_import_function($post_values, $post_id, $header_array, $value_array, $hash_key,$gmode,$templatekey,$line_number);
    }

    public function wpmembers_import_function($data_array, $uID, $header_array, $value_array, $hash_key,$gmode,$templatekey,$line_number) {
		
		$media_instance = MediaHandling::getInstance();
		$get_WPMembers_fields = get_option('wpmembers_fields');
		foreach ($get_WPMembers_fields as $key => $value) {
			$wpmembers[$value[2]] = $value[3];
		}
		if(!empty($data_array)) {
			foreach ($data_array as $custom_key => $custom_value) {
				if($wpmembers[$custom_key] == 'image' || $wpmembers[$custom_key] == 'file')
				{
					$media_instance->store_image_ids($i=1);
					$imageid = $media_instance->image_meta_table_entry($line_number,'', $uID, $custom_key, $custom_value, $hash_key, 'wpmember', 'user',$templatekey,$gmode);
					update_user_meta($uID, $custom_key, $imageid);
				}
				else
					update_user_meta($uID, $custom_key, $custom_value);
			}
		}
	}
}