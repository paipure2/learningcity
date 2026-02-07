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

class ACPTExtension extends ExtensionHandler{
	private static $instance = null;

	public static function getInstance() {		
		if (ACPTExtension::$instance == null) {
			ACPTExtension::$instance = new ACPTExtension;
		}
		return ACPTExtension::$instance;
	}

	/**
	 * Provides Metabox mapping fields for specific post type
	 * @param string $data - selected import type
	 * @return array - mapping fields
	 */
	public function processExtension($data) {	
		global $wpdb;
		// error_reporting(E_ALL);
		// ini_set("display_errors","On");
			if(is_plugin_active('acpt-lite/acpt-lite.php')  ){
	
		$results = $wpdb->get_results("
    SELECT 
	box.meta_box_name AS group_name,
	field.field_name,
	field.field_label
    FROM {$wpdb->prefix}acpt_lite_meta_box AS box
    JOIN {$wpdb->prefix}acpt_lite_meta_field AS field
	ON box.id = field.meta_box_id
    ORDER BY box.meta_box_name, field.sort ASC
", ARRAY_A);
			}
			else{
				$results = $wpdb->get_results("
    SELECT 
	box.meta_box_name AS group_name,
	field.field_name,
	field.field_label
    FROM {$wpdb->prefix}acpt_meta_box AS box
    JOIN {$wpdb->prefix}acpt_meta_field AS field
	ON box.id = field.meta_box_id
    ORDER BY box.meta_box_name, field.sort ASC
", ARRAY_A);
			}
$grouped_fields = [];

foreach ($results as $row) {
    $group_name = $row['group_name'];
    $field_name = $row['field_name'];
    $field_label = $row['field_label'];

    // if (!isset($grouped_fields[$group_name])) {
    //     $grouped_fields[$group_name] = [];
    // }

    $grouped_fields[$group_name.'_'.$field_name] = $group_name.$field_label;
}


		$mb_value = $this->convert_static_fields_to_array($grouped_fields);


		$response['acpt_fields'] =  $mb_value;
		return $response;	
    }

	/**
	* Metabox extension supported import types
	* @param string $import_type - selected import type
	* @return boolean
	*/
    public function extensionSupportedImportType($import_type ){	
		if(is_plugin_active('acpt-lite/acpt-lite.php') || is_plugin_active('advanced-custom-post-type/advanced-custom-post-type.php')  ){
			if($import_type == 'nav_menu_item'){
				return false;
			}
			$import_type = $this->import_name_as($import_type);					
			if($import_type == 'Posts' || $import_type == 'Pages' || $import_type == 'CustomPosts' || $import_type == 'event' || $import_type == 'event-recurring' || $import_type == 'Users' || $import_type =='WooCommerce'  || $import_type =='WooCommerceCategories' || $import_type =='WooCommerceattribute' || $import_type =='WooCommercetags' || $import_type =='WPeCommerce' || $import_type == 'Taxonomies'  || $import_type =='Tags' || $import_type =='Categories' || $import_type == 'WooCommerce Product' ||
				$import_type == 'WooCommerceVariations' || $import_type == 'WooCommerceOrders' || $import_type == 'WooCommerceCoupons' || $import_type == 'WooCommerceRefunds') {					
				return true;
			}
			else{				
				return false;
			}
		}
	}

	function import_post_types($import_type, $importAs = null)
    {
	$import_type = trim($import_type);

	$module = array(
	    'Posts' => 'post',
	    'Pages' => 'page',
	    'Users' => 'user',
	    'WooCommerce Product Variations' => 'product_variation',
	    'WooCommerce Refunds' => 'shop_order_refund',
	    'WooCommerce Orders' => 'shop_order',
	    'WooCommerce Coupons' => 'shop_coupon',
	    'Comments' => 'comments',
	    'Taxonomies' => $importAs,
	    'WooCommerce Product' => 'product',
	    'WooCommerce' => 'product',
	    'CustomPosts' => $importAs
	);
	foreach (get_taxonomies() as $key => $taxonomy)
	{
	    $module[$taxonomy] = $taxonomy;
	}
	if (array_key_exists($import_type, $module))
	{
	    return $module[$import_type];
	}
	else
	{
	    return $import_type;
	}
    }

	public function convert_static_fields_to_array($static_value){
	if (is_array($static_value) || is_object($static_value)){
	    foreach($static_value as $key=>$values){
		$static_fields_getting[] = array('label' => $values,                                                
						'name' => $key			
		);
	    }
	}
	$static_fields_getting=isset($static_fields_getting)?$static_fields_getting:'';
	return $static_fields_getting;
    }

	public function import_name_as($import_type){
		$taxonomies = get_taxonomies();
		$customposts = $this->get_import_custom_post_types();

		$import_type_as = $this->get_import_post_types();

		if (in_array($import_type, $taxonomies)) {

			if($import_type == 'category' || $import_type == 'product_category' || $import_type == 'product_cat' || $import_type == 'wpsc_product_category' || $import_type == 'event-categories'):
				$import_types = 'Categories';
			elseif($import_type == 'product_tag' || $import_type == 'event-tags' || $import_type == 'post_tag'):
				$import_types = 'Tags';
			elseif($import_type == 'comments'):
				$import_types = 'Comments';
			else:
				$import_types = 'Taxonomies';
			endif;
		}


		else if(array_key_exists($import_type , $import_type_as )){			

		 if (in_array($import_type, $customposts)) 
			$import_types = 'CustomPosts';
		else
			$import_types = $import_type_as[$import_type];
		}
		else{			
			$import_types = $import_type;
		}
		return $import_types;
	}
}
