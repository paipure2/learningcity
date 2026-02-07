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

class ProductAttributeExtension extends ExtensionHandler{
	private static $instance = null;

    public static function getInstance() {	
		if (ProductAttributeExtension::$instance == null) {
			ProductAttributeExtension::$instance = new ProductAttributeExtension;
		}
		return ProductAttributeExtension::$instance;
    }

	/**
	* Provides Product Attribute mapping fields
	* @param string $data - selected import type
	* @return array - mapping fields
	*/
    // public function processExtension($data,$process_type=null) {
	// 	error_log(print_r(['data' => $data],true),3,'/var/www/html/antony.log');
    //     $response = [];
	// 	$import_type = $data;
	// 	$import_type = $this->import_type_as($import_type);
	// 	$importas = $this->import_post_types($import_type);	
	// 	$taxonomies = get_object_taxonomies( $importas, 'names' );
	// 	$count=0;
	// 	$product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
	// 	$products = wc_get_products(array('status' => $product_statuses , 'limit' => -1));
	// 	$variable_product_ids = [];
	// 	foreach($products as $product){
    //         $product_all_attributes = $product->get_attributes();
    //         $prod_attribute_name = array();  
    //         foreach ($product_all_attributes as $attribute_key => $attribute) {
    //             $prod_attribute_name[]= str_replace('pa_', '',$attribute_key);  
    //         }
    //         $prod_attribute_count = count($prod_attribute_name);
    //         if($prod_attribute_count > $count){
    //             $count = $prod_attribute_count;
    //         }
    //         if (($product->is_type('variable'))) {
    //             $variable_product_ids[] = $product->get_id();
    //         }
    //     }

	// 	$variation_id = [];
	// 	foreach($variable_product_ids as $variable_product_id){
		
	// 		if(!empty($variable_product_id)){
	// 			$variable_product = wc_get_product($variable_product_id);
	// 			$variation_id [] = $variable_product->get_children();
	// 		}
			
	// 	} 
	// 	foreach($variation_id as $variation_ids){
	// 		if(!empty($variation_ids)){
	// 			$data = wc_get_product($variation_ids[0]);
	// 			$product_attributes=$data->get_attributes();
	// 			$attribute_name = array();  
	// 			foreach ($product_attributes as $attribute_key => $attribute) {
	// 				$attribute_name[]= str_replace('pa_', '',$attribute_key);	
	// 			}
	// 			$attribute_count = count($attribute_name);
	// 			if($attribute_count > $count){
	// 				$count = $attribute_count;
	// 			}
	// 		}
			
	// 	}
	// 	if($count == 0){
    //         $count =1;
    //     }

	// if($process_type == 'Export'){
	// 	$pro_attr_fields =array();
	// 	for($i=1; $i<=$count;$i++){
	// 		$pro_attr_fields += array(
	// 			'Product Attribute Name' . $i => 'product_attribute_name' . $i,
	// 			'Product Attribute Value' . $i => 'product_attribute_value' . $i,
	// 			'Product Attribute Visible' . $i => 'product_attribute_visible' . $i
	// 		);
	// 	}
	// 	$pro_attr_fields_line = $this->convert_static_fields_to_array($pro_attr_fields);
	// 	$response['product_attr_fields'] = $pro_attr_fields_line; 
	// }
	// else{
	// 	$pro_attr_fields =array();
	// 	for($i=1; $i<=$count;$i++){
	// 		$pro_attr_fields[]= $this->convert_static_fields_to_array(array(
	// 			"Product Attribute Name$i" => "product_attribute_name$i",
	// 				"Product Attribute Value$i" => "product_attribute_value$i",
	// 				"Product Attribute Visible$i" => "product_attribute_visible$i",
	// 		));
	// 	}
	// 	$response['product_attr_fields'] = $pro_attr_fields; 
	// }
	// // $attributecount = count($attribute_name);
    //     // $pro_attr_fields = array(
    //     //     'Product Attribute Name' => 'product_attribute_name',
    //     //     'Product Attribute Value' => 'product_attribute_value',
    //     //     'Product Attribute Visible' => 'product_attribute_visible',
    //     //     'Product Attribute Variation' => 'product_attribute_variation',
    //     //     'Product Attribute Position' => 'product_attribute_position',
    //     //     'Product Attribute Taxonomy' => 'product_attribute_taxonomy',
			
    //     // );

	// 	// if($import_type == 'WooCommerceVariations'){
	// 	// 	unset($pro_attr_fields['Product Attribute Taxonomy']);
	// 	// }

    //     // if(!empty($taxonomies)) {
	// 	// 	foreach ($taxonomies as $key => $value) {
	// 	// 		$check_for_pro_attr = explode('_', $value);
	// 	// 		if($check_for_pro_attr[0] == 'pa'){	
    //     //             $get_taxonomy_label = get_taxonomy($value);
    //     //             $taxonomy_label = $get_taxonomy_label->name;

    //     //             $pro_attr_fields[$taxonomy_label] = $value;
	// 	// 		}
    //     //     }
    //     // }
	// 	$pro_attr_fields_line = $this->convert_static_fields_to_array($pro_attr_fields);
	// 	$response['product_attr_fields'] = $pro_attr_fields_line; 
	// 	error_log(print_r(['response' => $response],true),3,'/var/www/html/antony.log');
	// 	return $response;	
	// }



	
	/***Enhance dynamic generation of product attribute fields in WooCommerce */

	public function processExtension($data,$process_type=null) {

        $response = [];
		$import_type = $data;
		$import_type = $this->import_type_as($import_type);
		$importas = $this->import_post_types($import_type);	
		$taxonomies = get_object_taxonomies( $importas, 'names' );
		$count = 0;
		$product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
		$products = [];
		$page = 1;
		$limit = 100;  // Set limit per page
		$variable_product_ids = []; // Initialize variable product ids array
    // Loop through products with pagination
		// while ($page) {
		// 	$paged_products = wc_get_products([
		// 		'status' => $product_statuses,
		// 		'limit' => $limit,
		// 		'page' => $page, // Paginate by page number
		// 	]);
			
		// 	if (empty($paged_products)) {
		// 		break; // Exit loop if no products are found
		// 	}

		// 	// Process each product
		// 	foreach ($paged_products as $product) {
		// 		$product_all_attributes = $product->get_attributes();
		// 		$prod_attribute_name = [];
		// 		foreach ($product_all_attributes as $attribute_key => $attribute) {
		// 			$prod_attribute_name[] = str_replace('pa_', '', $attribute_key);
		// 		}
		// 		$prod_attribute_count = count($prod_attribute_name);
		// 		if ($prod_attribute_count > $count) {
		// 			$count = $prod_attribute_count;
		// 		}
		// 		if ($product->is_type('variable')) {
		// 			$variable_product_ids[] = $product->get_id();
		// 		}
		// 	}

		// 	$page++; // Move to the next page
		// }
		// error_log(print_r(['variable_product_ids' => $variable_product_ids],true),3,'/var/www/html/antony.log');
		// $variation_id = [];
		// foreach($variable_product_ids as $variable_product_id){
		
		// 	if(!empty($variable_product_id)){
		// 		$variable_product = wc_get_product($variable_product_id);
		// 		$variation_id [] = $variable_product->get_children();
		// 	}
			
		// } 
		// error_log(print_r(['variation_id' => $variation_id],true),3,'/var/www/html/antony.log');
		// foreach($variation_id as $variation_ids){
		// 	if(!empty($variation_ids)){
		// 		$data = wc_get_product($variation_ids[0]);
		// 		$product_attributes=$data->get_attributes();
		// 		$attribute_name = array();  
		// 		foreach ($product_attributes as $attribute_key => $attribute) {
		// 			$attribute_name[]= str_replace('pa_', '',$attribute_key);	
		// 		}
		// 		$attribute_count = count($attribute_name);
		// 		if($attribute_count > $count){
		// 			$count = $attribute_count;
		// 		}
		// 	}
			
		// }
		// if($count == 0){
        //     $count =1;
        // }

		// Initialize variables


		// Process products in batches
		while (true) {
			$paged_products = wc_get_products([
				'status' => $product_statuses,
				'limit' => $limit,
				'page' => $page, // Paginate by page number
				'return' => 'objects', // Return product objects
			]);

			// Exit loop if no products are found
			if (empty($paged_products)) {
				break;
			}

			foreach ($paged_products as $product) {
				// Get product attributes
				$product_all_attributes = $product->get_attributes();
				$prod_attribute_count = count($product_all_attributes);
				
				// Update the maximum attribute count
				if ($prod_attribute_count > $count) {
					$count = $prod_attribute_count;
				}

				// Collect IDs of variable products
				if ($product->is_type('variable')) {
					$variable_product_ids[] = $product->get_id();
				}
			}

			$page++; // Move to the next page
		}
		// Process variable product variations in batches
		if (!empty($variable_product_ids)) {
			$variable_products = wc_get_products([
				'include' => $variable_product_ids,
				'limit' => -1, // Retrieve all variable products
				'return' => 'objects',
			]);

			foreach ($variable_products as $variable_product) {
				$variation_ids = $variable_product->get_children();

				// Fetch attributes for the first variation in each variable product
				if (!empty($variation_ids)) {
					$variation = wc_get_product($variation_ids[0]);
					$variation_attributes = $variation->get_attributes();
					$variation_attribute_count = count($variation_attributes);

					// Update the maximum attribute count
					if ($variation_attribute_count > $count) {
						$count = $variation_attribute_count;
					}
				}
			}
		}

		// Ensure a minimum count of 1
		$count = max($count, 1);
	if($process_type == 'Export'){
		$pro_attr_fields =array();
		for($i=1; $i<=$count;$i++){
			$pro_attr_fields += array(
				'Product Attribute Name' . $i => 'product_attribute_name' . $i,
				'Product Attribute Value' . $i => 'product_attribute_value' . $i,
				'Product Attribute Visible' . $i => 'product_attribute_visible' . $i
			);
		}
		$pro_attr_fields_line = $this->convert_static_fields_to_array($pro_attr_fields);
		$response['product_attr_fields'] = $pro_attr_fields_line; 
	}
	else{
		$pro_attr_fields =array();
		for($i=1; $i<=$count;$i++){
			$pro_attr_fields[]= $this->convert_static_fields_to_array(array(
				"Product Attribute Name$i" => "product_attribute_name$i",
					"Product Attribute Value$i" => "product_attribute_value$i",
					"Product Attribute Visible$i" => "product_attribute_visible$i",
			));
		}
		$response['product_attr_fields'] = $pro_attr_fields; 
	}
		$pro_attr_fields_line = $this->convert_static_fields_to_array($pro_attr_fields);
		$response['product_attr_fields'] = $pro_attr_fields_line; 
		return $response;	
	}
	
	
	/**
	* Product Attribute extension supported import types
	* @param string $import_type - selected import type
	* @return boolean
	*/
	public function extensionSupportedImportType($import_type){
		if(is_plugin_active('woocommerce/woocommerce.php')){
			$import_type = $this->import_name_as($import_type);
			if($import_type == 'WooCommerce' || $import_type == 'WooCommerceVariations' ) { 
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}