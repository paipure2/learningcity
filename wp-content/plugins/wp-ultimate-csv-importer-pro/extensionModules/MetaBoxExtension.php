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

class MetaBoxExtension extends ExtensionHandler{
	private static $instance = null;

    public static function getInstance() {		
		if (MetaBoxExtension::$instance == null) {
			MetaBoxExtension::$instance = new MetaBoxExtension;
		}
		return MetaBoxExtension::$instance;
    }

	/**
	* Provides Metabox mapping fields for specific post type
	* @param string $data - selected import type
	* @return array - mapping fields
	*/
    public function processExtension($data) {	
        global $wpdb;
        $response = []; 
        $import_type = $this->import_post_types($data);		
        $metabox_fields = [];
		$listTaxonomy = get_taxonomies();
		if($import_type == 'user'){
			$get_metabox_fields = \rwmb_get_object_fields($import_type,'user');
		}elseif(in_array($import_type, $listTaxonomy)){
			$get_metabox_fields =  \rwmb_get_object_fields($import_type,'term');
		}else{			
			$get_metabox_fields =  \rwmb_get_object_fields($import_type);

		}		
		$customFields =array();
		if(!empty($get_metabox_fields)){
			foreach($get_metabox_fields as $meta_key => $meta_value){
				if(!isset($meta_value['relationship'])) {
					if($meta_value['type'] != 'group')
					// $customFields[$meta_key] = $meta_value['name'];
					$customFields[$meta_key] = $meta_value;
				}	
			}
			$mb_value = $this->convert_static_fields_to_array($customFields);
		}
		else{
			$mb_value = '';
		}
		$response['metabox_fields'] =  $mb_value;
		return $response;	
    }

	/**
	* Metabox extension supported import types
	* @param string $import_type - selected import type
	* @return boolean
	*/
    public function extensionSupportedImportType($import_type ){	
		if(is_plugin_active('meta-box/meta-box.php') || is_plugin_active('meta-box-aio/meta-box-aio.php')  || is_plugin_active('meta-box-lite/meta-box-lite.php')){
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
                $static_fields_getting[] = array(
					// 'label' => $values, 
                	'label'    => isset($values['name']) ? $values['name'] : $values,
                    'name' => $key,
                	'required' => (isset($values['required']) && $values['required']) ? 1 : ''
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