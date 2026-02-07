<?php
/**
 * WP Ultimate CSV Importer plugin file.
 *
 * Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 */

namespace Smackcoders\WCSV;

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

class PropertyListingExtension extends ExtensionHandler{
	private static $instance = null;

	public static function getInstance() {

		if (PropertyListingExtension::$instance == null) {
			PropertyListingExtension::$instance = new PropertyListingExtension;
		}
		return PropertyListingExtension::$instance;
	}

	/**
	 * Provides PPOM Meta fields for specific post type
	 * @param string $data - selected import type
	 * @return array - mapping fields
	 */
	public function processExtension($data){

			$fields = [];
		
			if ($data === 'estate_property') {
				$options = get_option('wprentals_admin');
		
				// // 1. Custom fields
				// $custom_fields = $options['wpestate_custom_fields_list'] ?? [];
				// if (is_array($custom_fields)) {
				// 	foreach ($custom_fields as $field) {
				// 		if (!empty($field['label']) && !empty($field['slug'])) {
				// 			$fields[$field['label']] = $field['slug'];
				// 		}
				// 	}
				// }
		
				// // 2. Submission page fields
				// $submit_fields = $options['wp_estate_submission_page_fields'] ?? [];
				// if (is_array($submit_fields)) {
				// 	foreach ($submit_fields as $label => $slug) {
				// 		$fields[$slug] = $label;
				// 	}
				// }

				global $wpdb;

				$results = $wpdb->get_col("
					SELECT DISTINCT meta_key 
					FROM $wpdb->postmeta 
					WHERE post_id IN (
						SELECT ID FROM $wpdb->posts WHERE post_type = 'estate_property'
					)
					AND meta_key NOT LIKE '\_%'
				");

				foreach ($results as $field) {
					$name = str_replace(['_', '-'], ' ', $field);
					$label = ucwords($name);
				$fields[$label] = $field;
				}
				$fields = $this->convert_static_fields_to_array($fields);
				$response['wprentals_meta_fields'] = $fields ;
			//	ksort($fields); // Optional: sort alphabetically by label
			}
		
			if ($data === 'estate_agent') {
				$fields = [
					'Email'                        => 'agent_email',
					'Phone'                        => 'agent_phone',
					'Mobile'                       => 'agent_mobile',
					'Skype'                        => 'agent_skype',
					'Facebook'                     => 'agent_facebook',
					'Twitter'                      => 'agent_twitter',
					'Linkedin'                     => 'agent_linkedin',
					'Pinterest'                    => 'agent_pinterest',
					'I Live In'                    => 'live_in',
					'I Speak'                      => 'i_speak',
					'Payment Info/Hidden Field'   => 'payment_info',
					'User Agent ID'                => 'user_agent_id',
				];
				$fields = $this->convert_static_fields_to_array($fields);
				$response['wprentals_owner_fields'] = $fields ;
				
			}
		
			
		
			return $response;
            
	}

	/**
	 * PPOM Meta extension supported import types
	 * @param string $import_type - selected import type
	 * @return boolean
	 */
	public function extensionSupportedImportType($import_type ){

	

		//	$import_type = $this->import_name_as($import_type);

			if($import_type == 'estate_property' || $import_type == 'estate_agent' ) { 
				return true;
			}else{
				return false;
			}
		}
	

}
