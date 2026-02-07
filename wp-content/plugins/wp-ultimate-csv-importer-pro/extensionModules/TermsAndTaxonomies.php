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

class TermsAndTaxonomies extends ExtensionHandler{
	private static $instance = null;

    public static function getInstance() {
		if (TermsAndTaxonomies::$instance == null) {
			TermsAndTaxonomies::$instance = new TermsAndTaxonomies;
		}
		return TermsAndTaxonomies::$instance;
    }

	/**
	* Provides Terms and Taxonomies fields for specific post type
	* @param string $data - selected import type
	* @return array - mapping fields
	*/
    public function processExtension($data) {
        $response = [];
		$import_type = $data;
		$import_type = $this->import_type_as($import_type);
        $term_taxonomies = array();
		$importas = $this->import_post_types($import_type);	
		$taxonomies = get_object_taxonomies( $importas, 'names' );
	
		$search_array = array('post_format','product_type','product_visibility','product_shipping_class','language','post_translations');
		if(!is_plugin_active('polylang/polylang.php') && !is_plugin_active('polylang-pro/polylang.php') && !is_plugin_active('polylang-wc/polylang-wc.php')  && !is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php')  && !is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
			$search_array = array_values(array_diff($search_array, ["language"]));
		}
		foreach($search_array as $search_values){
			if(in_array($search_values , $taxonomies)){
				$search_format = array_search($search_values , $taxonomies);
				unset($taxonomies[$search_format]);
			}
		}
	
		if(!empty($taxonomies)) {
			$temp = 0;
			foreach ($taxonomies as $key => $value) {
				//added - check for woocommerce product attribute values - if so unset it
				$check_for_pro_attr = explode('_', $value);
				if($check_for_pro_attr[0] == 'pa'){	
					unset($taxonomies[$key]);
					continue;
				}

				$get_taxonomy_label = get_taxonomy($value);
				$taxonomy_label = $get_taxonomy_label->name;
				
				if($value == 'wpsc_product_category' || $value == 'product_cat'){
					$value = 'product_category';
				}elseif($value == 'category'){
					$value = 'post_category';
				}
				$term_taxonomies['TERMS'][$temp]['label'] = $taxonomy_label;
				$term_taxonomies['TERMS'][$temp]['name'] = $value;
				$temp++;
			}
		}

		$terms_value = $this->convert_fields_to_array($term_taxonomies);
		$response['terms_and_taxonomies'] =  $terms_value ;
		return $response;		
    }

	/**
	* Terms and Taxonomies extension supported import types
	* @param string $import_type - selected import type
	* @return boolean
	*/
    public function extensionSupportedImportType($import_type ){
		if($import_type == 'nav_menu_item' || $import_type == 'elementor_library'){
			return false;
		}	
		$import_type = $this->import_name_as($import_type);
			if($import_type =='Posts' || $import_type =='Pages' || $import_type =='CustomPosts' || $import_type =='event' || $import_type == 'event-recurring' || $import_type =='location' || $import_type =='WooCommerce' || $import_type =='WPeCommerce') {	
				return true;
			}
			if($import_type == 'ticket'){
				if(is_plugin_active('events-manager/events-manager.php')){
					return false;
				}else{
					return true;
				}
			}
			else{
				return false;
			}
		}
}