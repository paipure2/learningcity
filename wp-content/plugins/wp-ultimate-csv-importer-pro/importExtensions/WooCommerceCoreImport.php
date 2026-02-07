<?php
/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;
use Smackcoders\WCSV\WC_Product_Bundle;
use Smackcoders\WCSV\WC_Coupon;
use Smackcoders\WCSV\WC_Product_External;
use Smackcoders\WCSV\WC_Product_Attribute;
if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

class WooCommerceCoreImport {
    private static $woocommerce_core_instance = null,$media_instance;

    public static function getInstance() {
		if (WooCommerceCoreImport::$woocommerce_core_instance == null) {
			WooCommerceCoreImport::$woocommerce_core_instance = new WooCommerceCoreImport;
			WooCommerceCoreImport::$media_instance = new MediaHandling();
			return WooCommerceCoreImport::$woocommerce_core_instance;
		}
		return WooCommerceCoreImport::$woocommerce_core_instance;
    }

    public function woocommerce_variations_import($data_array , $mode , $check , $unikey ,$unikey_name, $line_number, $variation_count,$update_based_on) {				
		global $wpdb;
		$log_table_name = $wpdb->prefix ."import_detail_log";
		$core_instance = CoreFieldsImport::getInstance();
		$helpers_instance = ImportHelpers::getInstance();
		$updated_row_counts = $helpers_instance->update_count($unikey,$unikey_name);
		$skipped_count = $updated_row_counts['skipped'];
       
		$productInfo = '';
		$returnArr = array('MODE' => $mode , 'ID' => '');
		$product_id = isset($data_array['PRODUCTID']) ? $data_array['PRODUCTID'] : '';
		$parent_sku = isset($data_array['PARENTSKU']) ? $data_array['PARENTSKU'] : '';
		$variation_id =  isset($data_array['VARIATIONID']) ? $data_array['VARIATIONID'] : '';
		$variation_sku = isset($data_array['VARIATIONSKU']) ? $data_array['VARIATIONSKU'] : '';		
		if($product_id != '' && ($variation_sku == '' || $variation_id == '')) {
			if($variation_sku != ''){
				$variation_condition = 'update_using_variation_sku';
			}
			else if($variation_id != ''){
				$variation_condition = 'update_using_variation_id';
			}
			else{
				$variation_condition = 'insert_using_product_id';
			}

		} 		
		elseif($parent_sku != '') {			
			$get_parent_product_id = $wpdb->get_results("select id from {$wpdb->prefix}posts where post_status != 'trash' and post_type = 'product' and id in (select post_id from {$wpdb->prefix}postmeta where meta_value = '$parent_sku')");									
			$count = count( $get_parent_product_id );
			$key = 0;
			if ( ! empty( $get_parent_product_id ) ) {				
				$product_id = $get_parent_product_id[$key]->id;
				//Check whether the product is variable type
				$term_details = wp_get_object_terms($product_id,'product_type');
				if((!empty($term_details)) && ($term_details[0]->name != 'variable')){
					
					$core_instance->detailed_log[$line_number]['Message'] = "Skipped,Product is not variable in type.";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
					return array('MODE' => $mode,'ID' => '');					
				}
			} else {
				$product_id = '';
				$core_instance->detailed_log[$line_number]['Message'] = "Skipped,Product is not available.";
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
					return array('MODE' => $mode,'ID' => '');
			}			
			if($mode == 'Insert'){
				$variation_condition = 'insert_using_product_sku';
			}
			if($variation_sku != '' && $mode == 'Update'){
				$variation_condition = 'update_using_variation_sku';
			}
			if($variation_id != ''){
				$variation_condition = 'update_using_variation_id';
			}
		}
		elseif($parent_sku == '' && ($variation_sku != '' || $variation_id != '')){
			if($variation_sku != ''){
				$variation_condition = 'update_using_variation_sku';
			}
			if($variation_id != ''){
				$variation_condition = 'update_using_variation_id';
			}
		}

		if($variation_sku != '' && $variation_id != ''){
			update_post_meta($variation_id, '_sku', $variation_sku);
		}
        
		if($product_id != '') {
			$is_exist_product = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}posts where ID = %d", $product_id));
			if(!empty($is_exist_product) && $is_exist_product[0]->ID == $product_id) {
				$productInfo = $is_exist_product[0];
			} else {
				#return $returnArr;
			}
		}			
	
		if(isset($variation_condition)){
		switch ($variation_condition) {
			case 'update_using_variation_id_and_sku':
				
				$get_variation_data = $wpdb->get_results( $wpdb->prepare( "select DISTINCT pm.post_id from {$wpdb->prefix}posts p join {$wpdb->prefix}postmeta pm on p.ID = pm.post_id where p.ID = %d and p.post_type = %s and pm.meta_value = %s", $variation_id, 'product_variation', $variation_sku ) );

				if ( ! empty( $get_variation_data ) && $get_variation_data[0]->post_id == $variation_id ) {
					$returnArr = $this->importVariationData( $product_id, $variation_id, 'update_using_variation_id_and_sku' ,$unikey  , $unikey_name, $line_number, $variation_count,$get_variation_data);
				} else {
					if($update_based_on == 'skip'){
						$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to'$check'not present already <br>";
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
						$returnArr['Mode'] = $mode;
						return $returnArr;
						// $get_variation_data = [];
					}
					else{
						$returnArr = $this->importVariationData( $product_id, $variation_id, 'default' ,$unikey , $unikey_name, $line_number, $variation_count, $productInfo);
					}
					
				}
				break;
			case 'update_using_variation_id':
				
				$get_variation_data = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}posts where ID = %d and post_type = %s", $variation_id, 'product_variation' ) );
				if ( ! empty( $get_variation_data ) && $get_variation_data[0]->ID == $variation_id ) {
					$returnArr = $this->importVariationData( $product_id, $variation_id, 'update_using_variation_id' ,$unikey  , $unikey_name, $line_number, $variation_count, $get_variation_data);
				} else {
					if($update_based_on == 'skip'){
						$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to '$check' not present already <br>";
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
						$returnArr['Mode'] = $mode;
						return $returnArr;
						// $get_variation_data = [];
					}
					else{
						$returnArr = $this->importVariationData( $product_id, $variation_id, 'default',$unikey , $unikey_name, $line_number, $variation_count, $productInfo );
					}
					
				}
				break;
			case 'update_using_variation_sku':								
				$variation_data = $wpdb->get_results("select post_id from {$wpdb->prefix}postmeta where meta_value = '$variation_sku' and post_id in (select id from {$wpdb->prefix}posts where post_type = 'product_variation' and post_status != 'trash' and post_parent = $product_id)");
				$variation_id = !empty($variation_data) ? $variation_data[0]->post_id : "";								
				if($variation_id)
				$get_variation_data = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}posts where ID = %d and post_type = %s", $variation_id, 'product_variation' ) );				
				else
				$get_variation_data = [];
				if ( ! empty( $get_variation_data ) && $get_variation_data[0]->ID == $variation_id) {
					$returnArr = $this->importVariationData( $product_id,$variation_id, 'update_using_variation_sku' ,$unikey  , $unikey_name, $line_number, $variation_count,$get_variation_data);
				} else {
					if($update_based_on == 'skip'){
						$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to '$check 'not present already <br>";
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
						$returnArr['Mode'] = $mode;
						return $returnArr;
						// $get_variation_data = [];
					}
					else{
						$returnArr = $this->importVariationData( $product_id, $variation_id, 'default' ,$unikey , $unikey_name, $line_number, $variation_count, $productInfo);
					}
				}
				break;
			case 'insert_using_product_id':
				if($update_based_on == 'skip'){
					$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to '$check ' not present already <br>";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
					$returnArr['Mode'] = $mode;
					return $returnArr;
					// $get_variation_data = [];
				}
				else{
					$returnArr = $this->importVariationData( $product_id, $variation_id, 'insert_using_product_id',$unikey , $unikey_name, $line_number, $variation_count,  $productInfo);
				}
				break;
			case 'insert_using_product_sku':
				if($update_based_on == 'skip'){
					$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to '$check' not present already <br>";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
					$returnArr['Mode'] = $mode;
					return $returnArr;
					// $get_variation_data = [];
				}else{
					$returnArr = $this->importVariationData( $product_id, $variation_id, 'insert_using_product_sku',$unikey ,$unikey_name, $line_number, $variation_count, $productInfo );
				}
				break;
			default:
				if($update_based_on == 'skip'){
					$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to '$check ' not present already <br>";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
					$returnArr['Mode'] = $mode;
					return $returnArr;
					// $get_variation_data = [];
				}
				else{
					$returnArr = $this->importVariationData( $product_id, $variation_id, 'default',$unikey  ,$unikey_name, $line_number, $variation_count, $productInfo);
				}
				break;
		}
	}
		return $returnArr;
	}

	public function importVariationData ($product_id, $variation_id, $type,$unikey , $unikey_name, $line_number, $variation_count, $exist_variation_data = array()) {		
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		global $core_instance;
		$log_table_name = $wpdb->prefix ."import_detail_log";

		$updated_row_counts = $helpers_instance->update_count($unikey,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];
		if($type == 'default' || $type == 'insert_using_product_id' || $type == 'insert_using_product_sku') {
			
			$get_count_of_variations = $wpdb->get_results( $wpdb->prepare( "select count(*) as variations_count from {$wpdb->prefix}posts where post_parent = %d and post_type = %s", $product_id, 'product_variation' ) );
			$variations_count = $get_count_of_variations[0]->variations_count;
			$menu_order_count = 0;
			if ($variations_count == 0) {
				$variations_count = '';
				$menu_order= 0 ;
			} else {
				$variations_count = $variations_count + 1;
				$menu_order_count = $variations_count - 1;
				$variations_count = '-' . $variations_count;
			}
			$get_variation_data = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}posts where ID = %d", $product_id));
			foreach($get_variation_data as $key => $val) {
				
				if($product_id == $val->ID){

					$variation_data = array();
					$variation_data['post_title'] = $val->post_title ;
					$variation_data['post_date'] = $val->post_date;
					$variation_data['post_type'] = 'product_variation';
					$variation_data['post_status'] = 'publish';
					$variation_data['comment_status'] = 'closed';
					$variation_data['ping_status'] = 'closed';
					$variation_data['menu_order'] = $menu_order_count;
					$variation_data['post_name'] = 'product-' . $val->ID . '-variation' . $variations_count;
					$variation_data['post_parent'] = $val->ID;
					
				}
			}
			//$variation_data=isset($variation_data)?$variation_data:'';
			$variationid = wp_insert_post($variation_data);					
			if(empty($variation_count)){
				$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Variation ID: ' . $variationid;
				$core_instance->detailed_log[$line_number]['id'] = $variationid;
				$core_instance->detailed_log[$line_number]['webLink'] = get_permalink( $variationid );
				$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
			}
			else{				
				$parent_id = $wpdb->get_var( "SELECT post_parent FROM {$wpdb->prefix}posts WHERE id = '$variationid' " );								
				$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Product ID: ' . $parent_id . '   Inserted Variation ID: ' . $variationid;
				$core_instance->detailed_log[$line_number]['Sku'] = $variation_count[0];			
				$core_instance->detailed_log[$line_number]['webLink'] = get_permalink( $variationid );
				$core_instance->detailed_log[$line_number]['state'] = 'Inserted';			
			}
			$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");		
			$returnArr = array( 'ID' => $variationid, 'MODE' => 'Inserted' );
			return $returnArr;
		} elseif ($type == 'update_using_variation_id' || $type == 'update_using_variation_sku' || $type == 'update_using_variation_id_and_sku') {
			//Not needed only variation meta are updated while update
			/*foreach($exist_variation_data as $key => $val) {
				if($variation_id == $val->ID){
					$variation_data['ID'] = $val->ID;
					$variation_data['post_title'] = $val->post_title;
					$variation_data['post_status'] = 'publish';
					$variation_data['comment_status'] = 'open';
					$variation_data['ping_status'] = 'open';
					$variation_data['post_name'] = 'product-' . $val->ID . '-variation' . $variations_count;
					$variation_data['post_parent'] = $val->post_parent;
					$variation_data['post_type'] = 'product_variation';
					$variation_data['menu_order'] = $val->menu_order;
				}
			}

			wp_update_post($variation_data);*/

			$core_instance->detailed_log[$line_number]['Message'] = 'Updated Variation ID: ' . $variation_id;
			$core_instance->detailed_log[$line_number]['webLink'] = get_permalink( $variation_id );
			$core_instance->detailed_log[$line_number]['state'] = 'Updated';
			$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");

			$returnArr = array( 'ID' => $variation_id, 'MODE' => 'Updated');			
			return $returnArr;
		}
	}
	public function woocommerce_orders_new_import($data_array , $mode , $check , $unikey , $unikey_name, $line_number,$order_meta_data,$update_based_on) {
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		global $core_instance;

		$log_table_name = $wpdb->prefix ."import_detail_log";

		$updated_row_counts = $helpers_instance->update_count($unikey,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];
	if (class_exists('WC_Order')) {
		// Create a new order instance
		if ($mode == 'Insert') {
			$order = wc_create_order();
			$order_id = $order->save();
			$mode_of_affect = 'Inserted';
			if(is_wp_error($order_id) || $order_id == '') {
				$core_instance->detailed_log[$line_number]['Message'] = "Can't insert this Order. " . $order_id->get_error_message();
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
				return array('MODE' => $mode, 'ERROR_MSG' => $order_id->get_error_message());
			}
			$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Order ID: ' . $order_id;
			$core_instance->detailed_log[$line_number]['id'] = $order_id;
			$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $order_id, true );
			$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
			$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");
		}
		elseif ($mode == 'Update') {
			$order_id=$data_array['ORDERID'];
			$update_query = "select ID from {$wpdb->prefix}posts where ID = $order_id";
			$ID_result = $wpdb->get_results($update_query);
			if (is_array($ID_result) && !empty($ID_result)) {
				$retID = $ID_result[0]->ID;
				$data_array['ID'] = $retID;
				// wp_update_post($data_array);
				$mode_of_affect = 'Updated';

				$core_instance->detailed_log[$line_number]['Message'] = 'Updated Order ID: ' . $retID;
				$core_instance->detailed_log[$line_number]['id'] = $retID;
				$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $retID, true );
				$core_instance->detailed_log[$line_number]['state'] = 'Updated';
				$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");	
			} else{
							
				$core_instance->detailed_log[$line_number]['Message'] = "Skipped.";
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
				return array('MODE' => $mode);
			}
			$order=wc_get_order($data_array['ORDERID']);
		}else{
				$core_instance->detailed_log[$line_number]['Message'] = "Skipped.";
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
				return array('MODE' => $mode);
				
		}	
	}
	$item_name = $order_meta_data['item_name'];
	$item_qty = $order_meta_data['item_qty'];
	$products = explode(',', $item_name);
	$quantities = explode(',', $item_qty);
	$product_ids = []; 
	if ($mode == 'Insert') {
		$queried_titles = [];

		foreach ($products as $products_value) {
			$title = ltrim($products_value);
			
			if (is_numeric($products_value)) {
				$product_ids[] = $products_value; // Append instead of overwriting
			} 
			elseif (!in_array($title, $queried_titles)) {
				$product_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$title' AND post_type in ('product','product_variation') AND post_status = 'publish'");
				
				if ($product_id !== null) {
					$product_ids[] = $product_id;
					$queried_titles[] = $title;
				}
			}
		}

    // Ensure $product_ids is always an array before using count()
    if (!is_array($product_ids)) {
        $product_ids = [];
    }

    for ($i = 0; $i < count($product_ids); $i++) {
		$my_product = wc_get_product($product_ids[$i]);
		if(!empty($my_product)){
			if ($my_product->is_type('variable')) {
				$variations = $my_product->get_children();
				for ($i = 0; $i < count($variations); $i++) {
					$quantity  = !empty($quantities[$i]) ? $quantities[$i] : '' ;
					$variation = !empty($variations[$i]) ? wc_get_product($variations[$i]) : '';
					if (!empty($variation) && $variation->exists()) {
						$order->add_product($variation, $quantity);
					}
				}
			}else{
				$quantity = !empty($quantities[$i]) ? $quantities[$i] : '' ;
				if(isset($product_ids[$i]) && !empty($product_ids[$i])){
					$order->add_product(wc_get_product($product_ids[$i]), $quantity);
				}
			}
		}
		}
	}
    // Set customer information
	$customer_user = $order_meta_data['customer_user'];
	if(is_numeric($customer_user)){
		$customer_user_id = $customer_user;
	}
	else{
		$email = $customer_user;
		$customer_user_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE user_email='$email'");
	}
	$customer_note = $data_array['customer_note'];

    // Replace with the customer's user ID
    $order->set_customer_id($customer_user_id);
	$billing_first_name = isset($order_meta_data['billing_first_name']) ? $order_meta_data['billing_first_name'] : '';
	$billing_last_name = isset($order_meta_data['billing_last_name']) ? $order_meta_data['billing_last_name'] : '';
	$billing_company = isset($order_meta_data['billing_company']) ? $order_meta_data['billing_company'] : '';
	$billing_address_1 = isset($order_meta_data['billing_address_1']) ? $order_meta_data['billing_address_1'] : '';
	$billing_address_2 = isset($order_meta_data['billing_address_2']) ? $order_meta_data['billing_address_2'] : '';
	$billing_city = isset($order_meta_data['billing_city']) ? $order_meta_data['billing_city'] : '';
	$billing_postcode = isset($order_meta_data['billing_postcode']) ? $order_meta_data['billing_postcode'] : '';
	$billing_country = isset($order_meta_data['billing_country']) ? $order_meta_data['billing_country'] : '';
	$billing_phone = isset($order_meta_data['billing_phone']) ? $order_meta_data['billing_phone'] : '';
	$billing_email = isset($order_meta_data['billing_email']) ? $order_meta_data['billing_email'] : '';
	$billing_state = isset($order_meta_data['billing_state']) ? $order_meta_data['billing_state'] : '';
	$shipping_first_name = isset($order_meta_data['shipping_first_name']) ? $order_meta_data['shipping_first_name'] : '';
	$shipping_last_name = isset($order_meta_data['shipping_last_name']) ? $order_meta_data['shipping_last_name'] : '';
	$shipping_company = isset($order_meta_data['shipping_company']) ? $order_meta_data['shipping_company'] : '';
	$shipping_address_1 = isset($order_meta_data['shipping_address_1']) ? $order_meta_data['shipping_address_1'] : '';
	$shipping_address_2 = isset($order_meta_data['shipping_address_2']) ? $order_meta_data['shipping_address_2'] : '';
	$shipping_city = isset($order_meta_data['shipping_city']) ? $order_meta_data['shipping_city'] : '';
	$shipping_postcode = isset($order_meta_data['shipping_postcode']) ? $order_meta_data['shipping_postcode'] : '';
	$shipping_country = isset($order_meta_data['shipping_country']) ? $order_meta_data['shipping_country'] : '';
	$shipping_phone = isset($order_meta_data['shipping_phone']) ? $order_meta_data['shipping_phone'] : '';
	$shipping_email = isset($order_meta_data['shipping_email']) ? $order_meta_data['shipping_email'] : '';
	$shipping_state = isset($order_meta_data['shipping_state']) ? $order_meta_data['shipping_state'] : '';
	


    // Set billing and shipping address (replace with actual details)
    $billing_address = array(
        'first_name' => $billing_first_name,
        'last_name'  => $billing_last_name,
        'address_1'  => $billing_address_1 ,
		'address_2'  => $billing_address_2,
        'city'       => $billing_city,
        'state'      => $billing_state,
        'postcode'   => $billing_postcode ,
        'country'    => $billing_country,
        'email'      => $billing_email,
        'phone'      => $billing_phone,
		'company' => $billing_company 
    );
	$shipping_address = array(
        'first_name' => $shipping_first_name,
        'last_name'  => $shipping_last_name,
        'address_1'  => $shipping_address_1 ,
		'address_2'  => $shipping_address_2,
        'city'       => $shipping_city,
        'state'      => $shipping_state,
        'postcode'   => $shipping_postcode ,
        'country'    => $shipping_country,
        'email'      => $shipping_email,
        'phone'      => $shipping_phone,
		'company' => $shipping_company 
    );
    $order->set_address($billing_address, 'billing');
    $order->set_address($shipping_address, 'shipping');

    // Set payment method (replace with actual payment method)
    $payment_method = $order_meta_data['payment_method']; // Direct bank transfer
	$order_currency = $order_meta_data['order_currency']; 

    $order->set_payment_method($payment_method);
	$order->set_customer_note($customer_note);
	$order->set_currency( $order_currency);
    // Calculate totals
    $order->calculate_totals();

	$order->update_meta_data( 'ywot_tracking_code', $order_meta_data['ywot_tracking_code'] );
	$order->update_meta_data( 'ywot_tracking_postcode', $order_meta_data['ywot_tracking_postcode']);
	$order->update_meta_data( 'ywot_carrier_id', $order_meta_data['ywot_carrier_id'] );
	$order->update_meta_data( 'ywot_pick_up_date', $order_meta_data['ywot_pick_up_date'] );
	$order->update_meta_data( 'ywot_estimated_delivery_date', $order_meta_data['ywot_estimated_delivery_date'] );
	$order->update_meta_data( 'ywot_picked_up', $order_meta_data['ywot_picked_up'] );

	$order_id = $order->save();

if (!empty($data_array['order_date'])) {
    $order_date_raw = $data_array['order_date'];
    
    $order_date = date('Y-m-d H:i:s', strtotime($order_date_raw));
    
    $wpdb->update(
        $wpdb->posts,
        [
            'post_date'     => $order_date,
            'post_date_gmt' => get_gmt_from_date($order_date)
        ],
        ['ID' => $order_id]
    );

    $order = wc_get_order($order_id);
    if ($order) {
        try {
			$wc_date = new \WC_DateTime($order_date, new \DateTimeZone('UTC'));
			$order->set_date_created($wc_date);
			$order->save();

        } catch (Exception $e) {
        }
    }
}

	// $order = wc_get_order( $order_id );
	// $order->set_status( 'wc-completed' );
	$module =$wpdb->get_var("SELECT post_type FROM {$wpdb->prefix}posts where id=$order_id");
	$order_status =$data_array['order_status'];
	global $wpdb;
	if($module == 'shop_order_placehold'){
		if(!empty($order_status)){
			$wpdb->get_results("Update {$wpdb->prefix}wc_orders set status='$order_status' where id=$order_id");
		}

	}
	else{
		if(!empty($order_status)){
			$wpdb->get_results("Update {$wpdb->prefix}posts set post_status='$order_status' where id=$order_id");
		}
	}
	// $helperss = new \WooCustomerInsightHelper();
	
	// $helperss->WCI_order_status_completed($order_id);
	
	$wpdb->update( $wpdb->prefix . 'posts' , 
	array( 
		'post_excerpt' => $customer_note,
	) , 
	array( 'id' => $order_id ) 
	);
    // Save the order
    

  
$returnArr['ID'] = $order_id;
		$returnArr['MODE'] = $mode_of_affect;
		return $returnArr;
	}
	public function woocommerce_orders_import($data_array , $mode , $check , $unikey , $unikey_name, $line_number) {
		$returnArr = array();	
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		global $core_instance;

		$log_table_name = $wpdb->prefix ."import_detail_log";

		$updated_row_counts = $helpers_instance->update_count($unikey,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];
		$data_array['post_type'] = 'shop_order';
		$arraykey = array_keys($data_array);
		
		if(!in_array('customer_note',$arraykey)){
			$data_array['post_excerpt'] = '';
		}
		else{
			$data_array['post_excerpt'] = $data_array['customer_note'];
		}
		//$data_array['post_excerpt'] = $data_array['customer_note'];
		if(isset($data_array['order_status'])) {
			$data_array['post_status'] = $data_array['order_status'];}
		/* Assign order date */
		if(!isset( $data_array['order_date'] )) {
			$data_array['post_date'] = current_time('Y-m-d H:i:s');
		} else {
			if(strtotime( $data_array['order_date'] )) {
				$data_array['post_date'] = date( 'Y-m-d H:i:s', strtotime( $data_array['order_date'] ) );
			} else {
				$data_array['post_date'] = current_time('Y-m-d H:i:s');
			}
		}
		if ($mode == 'Insert') {	
			$retID = wp_insert_post( $data_array );
			$mode_of_affect = 'Inserted';
			
			if(is_wp_error($retID) || $retID == '') {
				$core_instance->detailed_log[$line_number]['Message'] = "Can't insert this Order. " . $retID->get_error_message();
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
				return array('MODE' => $mode, 'ERROR_MSG' => $retID->get_error_message());
			}
			$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Order ID: ' . $retID;
			$core_instance->detailed_log[$line_number]['id'] = $retID;
			$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $retID, true );
			$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
			$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");

		} else {
			if ($mode == 'Update') {
				if($check == 'ORDERID'){
					if(!empty($data_array['ORDERID'])){
					$orderid = $data_array['ORDERID'];
					$update_query = "select ID from {$wpdb->prefix}posts where ID = '$orderid'";
					$ID_result = $wpdb->get_results($update_query);

					if (is_array($ID_result) && !empty($ID_result)) {
						$retID = $ID_result[0]->ID;
						$data_array['ID'] = $retID;
						wp_update_post($data_array);
						$mode_of_affect = 'Updated';

						$core_instance->detailed_log[$line_number]['Message'] = 'Updated Order ID: ' . $retID;
						$core_instance->detailed_log[$line_number]['id'] = $retID;
						$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $retID, true );
						$core_instance->detailed_log[$line_number]['state'] = 'Updated';
						$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");	
					} else{
						
							$core_instance->detailed_log[$line_number]['Message'] = "Skipped.";
							$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
							$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
							return array('MODE' => $mode);
					}
				}
				}else{
					$core_instance->detailed_log[$line_number]['Message'] = "Skipped.";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
					return array('MODE' => $mode);
					
				}
			}
		}
		$returnArr['ID'] = $retID;
		$returnArr['MODE'] = $mode_of_affect;
		return $returnArr;
	}

	public function woocommerce_coupons_import($data_array , $mode , $check , $unikey , $unikey_name, $line_number) {
		
		global $wpdb; 
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		global $core_instance;
		$log_table_name = $wpdb->prefix ."import_detail_log";

		$updated_row_counts = $helpers_instance->update_count($unikey,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];

		$returnArr = array();
		
		$data_array['post_type'] = 'shop_coupon';
		$data_array['post_title'] = $data_array['coupon_code'];
		$data_array['post_name'] = $data_array['coupon_code'];
		$data_array['post_date'] = $data_array['coupon_date'];
		$data_array = $core_instance->validateDate($data_array,$mode,$line_number);
		if(isset($data_array['description'])) {
			$data_array['post_excerpt'] = $data_array['description'];
		}

		/* Post Status Options */
		if ( !empty($data_array['coupon_status']) ) {
			$data_array = $helpers_instance->assign_post_status( $data_array );
		} else {
			$data_array['coupon_status'] = 'publish';
			$data_array['post_status'] = 'publish';
		}

		if ($mode == 'Insert') {
			$retID = wp_insert_post($data_array);
			$mode_of_affect = 'Inserted';
			
			if(is_wp_error($retID) || $retID == '') {
				$core_instance->detailed_log[$line_number]['Message'] = "Can't insert this Coupon. " . $retID->get_error_message();
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
				return array('MODE' => $mode, 'ERROR_MSG' => $retID->get_error_message());
			}
			$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Coupon ID: ' . $retID;
			$core_instance->detailed_log[$line_number]['id'] = $retID;
			$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $retID, true );
			$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
			$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");

		} else {
				if($check == 'COUPONID'){
					if(!empty($data_array['COUPONID'])){
					$coupon_id = $data_array['COUPONID'];
					$post_type = $data_array['post_type'];
					$update_query = "select ID from {$wpdb->prefix}posts where ID = '$coupon_id' and post_type = '$post_type' and post_status not in('trash','draft') order by ID DESC";
					$ID_result = $wpdb->get_results($update_query);

					if (is_array($ID_result) && !empty($ID_result)) {
						$retID = $ID_result[0]->ID;
						$data_array['ID'] = $retID;
						wp_update_post($data_array);
						$mode_of_affect = 'Updated';

						$core_instance->detailed_log[$line_number]['Message'] = 'Updated Coupon ID: ' . $retID;
						$core_instance->detailed_log[$line_number]['id'] = $retID;
						$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $retID, true );
						$core_instance->detailed_log[$line_number]['state'] = 'Updated';
						$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");			
					} else{
						$core_instance->detailed_log[$line_number]['Message'] = "Skipped.";
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
						return array('MODE' => $mode);
					}
				}
				}
				else{
					$core_instance->detailed_log[$line_number]['Message'] = "Skipped.";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
					return array('MODE' => $mode);
				}
			//} 
		}
		$returnArr['ID'] = $retID;
		$returnArr['MODE'] = $mode_of_affect;
		return $returnArr;
	}

	public function woocommerce_refunds_import($data_array , $mode , $check  ,$unikey , $unikey_name, $line_number) {
		$returnArr = array();
		$mode_of_affect = 'Inserted';
		global $wpdb; 
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		global $core_instance;
		$log_table_name = $wpdb->prefix ."import_detail_log";

		$updated_row_counts = $helpers_instance->update_count($unikey,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];
		
		$parent_order_id = 0;
		$post_excerpt = '';
		if(isset($data_array['REFUNDID']))
			$order_id = $data_array['REFUNDID'];
		elseif(isset($data_array['post_parent']))
			$parent_order_id = $data_array['post_parent'];
		if(isset($data_array['post_excerpt']))
			$post_excerpt = $data_array['post_excerpt'];
		$get_order_id = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}posts where ID = %d", $parent_order_id));
		if(!empty($get_order_id)){
			$refund = $get_order_id[0]->ID;
			
			if(isset($refund)){
				$date_format = date('m-j-Y-Hi-a');
				$date_read = date('M j, Y @ H:i a');
				$data_array['post_title'] = 'Refund &ndash;' . $date_read; 
				$data_array['post_type'] = 'shop_order_refund';
				$data_array['post_parent'] = $parent_order_id;
				$data_array['post_status'] = 'wc-completed';
				$data_array['post_name'] = 'refund-'.$date_format;
				$data_array['guid'] = site_url() . '?shop_order_refund=' . 'refund-'.$date_format;
			}
		}
		if ($mode == 'Insert') {
			$retID = wp_insert_post( $data_array );

			update_post_meta($retID , '_refund_reason' , $post_excerpt);
			
			$update_array = array();
			$update_array['ID'] = $parent_order_id;
			$update_array['post_status'] = 'wc-refunded';
			$update_array['post_modified'] = date('Y-m-d H:i:s');
			$update_array['post_modified_gmt'] = date('Y-m-d H:i:s');
			wp_update_post($update_array);

			if(is_wp_error($retID) || $retID == '') {
				$core_instance->detailed_log[$line_number]['Message'] = "Can't insert this Refund. " . $retID->get_error_message();
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
				return array('MODE' => $mode, 'ERROR_MSG' => $retID->get_error_message());
			}
			$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Refund ID: ' . $retID;
			$core_instance->detailed_log[$line_number]['id'] = $retID;
			$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $retID, true );
			$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
			$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");

		}
		
		else{
			if($check == 'REFUNDID'){
				if(!empty($data_array['REFUNDID'])){
				$refund_id = $data_array['REFUNDID'];
				$update_query = "select ID from {$wpdb->prefix}posts where ID = '$refund_id' and post_type = 'shop_order_refund' order by ID DESC";
				$ID_result = $wpdb->get_results($update_query);
				if (is_array($ID_result) && !empty($ID_result)) {
					$retID = $ID_result[0]->ID;
					$data_array['ID'] = $retID;
					wp_update_post($data_array);

					update_post_meta($retID , '_refund_reason' , $post_excerpt);
					$mode_of_affect = 'Updated';

					$core_instance->detailed_log[$line_number]['Message'] = 'Updated Refund ID: ' . $retID;
					$core_instance->detailed_log[$line_number]['id'] = $retID;
					$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $retID, true );
					$core_instance->detailed_log[$line_number]['state'] = 'Updated';
					$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");
				}else{
					if($mode == 'Update'){
						$core_instance->detailed_log[$line_number]['Message'] = "Skipped.";
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
						return array('MODE' => $mode);
					}
					
				}
			}
			}else{
				if($mode == 'Update'){
					$core_instance->detailed_log[$line_number]['Message'] = "Skipped.";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
					return array('MODE' => $mode);
				}	
			} 
		} 
		
		$returnArr['ID'] = $retID;
		$returnArr['MODE'] = $mode_of_affect;
		return $returnArr;
	}

	public function woocommerce_attributes_import($data_array , $mode , $check , $unikey , $unikey_name, $line_number) {
		
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		global $core_instance;
	
		$returnArr = array();
		$name = $data_array['name'];
		$slug = $data_array['slug'];
		$configure_terms = $data_array['configure_terms'];
		$attr = $data_array['default_sort_order'];
			if($attr == 'Custom ordering'){
				$attr = 'menu_order';
			}
			if($attr == 'Name (numeric)'){
				$attr = 'name_num';
			}
			if($attr == 'Term ID'){
				$attr = 'id';
			}
			if($attr == 'Name'){
				$attr = 'name';
			}
		$attribute=$data_array['enable_archive'];

		$log_table_name = $wpdb->prefix ."import_detail_log";

		$updated_row_counts = $helpers_instance->update_count($unikey,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];

		if($check == 'name' && !empty($data_array['name'])) { 
			$result = $wpdb->get_row("select attribute_id from {$wpdb->prefix}woocommerce_attribute_taxonomies where attribute_label='".$name."'");
		}
		if($check == 'slug' && !empty($data_array['slug'])){
			$result = $wpdb->get_row("select attribute_id from {$wpdb->prefix}woocommerce_attribute_taxonomies where attribute_name='".$slug."'");
		}
		if(!empty($data_array['slug'])){
			$duplicate_check = $wpdb->get_row("select attribute_id from {$wpdb->prefix}woocommerce_attribute_taxonomies where attribute_name='".$slug."'");
		}
	
		if($mode == 'Insert') {

			if (!empty($result) || (!empty($duplicate_check))) {
				$core_instance->detailed_log[$line_number]['Message'] = 'Skipped Product attribute';
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
				$returnArr['Mode'] = $mode;
				return $returnArr;
			}else{

				$wpdb->query("insert into {$wpdb->prefix}woocommerce_attribute_taxonomies(attribute_label,attribute_name,attribute_type,attribute_orderby,attribute_public) values('".$name."','".$slug."','select','".$attr."','".$attribute."')");
				$id = $wpdb->insert_id;

				$existing_attributes = array();
				$existing_attributes = get_option('_transient_wc_attribute_taxonomies', true);

				$at = array( 
						'attribute_id'=>$id,
						'attribute_name'=>$slug,
						'attribute_label'=>$name,
						'attribute_type'=>'select',
						'attribute_orderby'=>$attr,
						'attribute_public'=>$attribute
					);

				$at=(object)$at;
				array_push($existing_attributes,$at);
				update_option('_transient_wc_attribute_taxonomies',$existing_attributes);
					
				if(isset($configure_terms)){
					$taxo = 'pa_'.$slug;
					register_taxonomy($taxo , 'product');

					$configure_exp = explode(',' , $configure_terms);
					foreach($configure_exp as $config_values){
						$check_term = term_exists($config_values);	
						if(isset($check_term)){
						}else{	
							wp_insert_term($config_values , $taxo);	
						}	
					}
				}

				$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Product attribute ID: '.$id;
				$core_instance->detailed_log[$line_number]['id'] = $id;
				$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
				$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");
			}
			
		}
		
		if($mode == 'Update') {
       
				if(!empty($result)) {
					foreach($result as $res=>$value) {
						$id = $value;
					}

					$wpdb->query("update {$wpdb->prefix}woocommerce_attribute_taxonomies set attribute_label='".$name."',attribute_name='".$slug."',attribute_type='select',attribute_orderby='".$attr."',attribute_public='".$attribute."' where attribute_id='".$id."'");
					$wpdb->query("delete from ".$wpdb->prefix."options where option_name='_transient_wc_attribute_taxonomies'");

					$at = array( 'attribute_id'=>$id,
							'attribute_name'=>$slug,
							'attribute_label'=>$name,
							'attribute_type'=>'select',
							'attribute_orderby'=>$attr,
							'attribute_public'=>$attribute
						);
					$at=(object)$at;
					$a=array($at);
					update_option('_transient_wc_attribute_taxonomies',$a);

					if(isset($configure_terms)){
						$taxo = 'pa_'.$slug;
						$configure_exp = explode(',' , $configure_terms);
						foreach($configure_exp as $config_values){
							$check_term = term_exists($config_values);	
							if(isset($check_term)){
							}else{	
								if($mode == 'Import-Update'){
									wp_insert_term($config_values , $taxo);	
								}	
							}	
						}
					}

					$core_instance->detailed_log[$line_number]['Message'] = 'Updated Product attribute ID: '.$id;
					$core_instance->detailed_log[$line_number]['id'] = $id;
					$core_instance->detailed_log[$line_number]['state'] = 'Updated';
					$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");
					
				} else{
					if($mode == 'Update'){
						$core_instance->detailed_log[$line_number]['Message'] = 'Skipped';
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");
						return array('MODE' => $mode);
					}
				}                                                             
		}

		$returnArr['ID'] = $id;
		return $returnArr;
	}

	public function woocommerce_tags_import($data_array , $mode , $check , $unikey , $unikey_name, $line_number) {

		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		global $core_instance;

		$returnArr = array();
		$name = $data_array['name']; 
		$description = $data_array['description'];
		$slug = $data_array['slug'];
		$log_table_name = $wpdb->prefix ."import_detail_log";
		$updated_row_counts = $helpers_instance->update_count($unikey,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];

		if ($check == 'TERMID' && !empty($data_array['TERMID'])) {
			$term_id = $data_array['TERMID'];
			$termid =$wpdb->get_row("select term_id from {$wpdb->prefix}terms where term_id = '$term_id' ");
		}
		if($check == 'slug' && !empty($data_array['slug'])) {
			$termid =$wpdb->get_row("select term_id from {$wpdb->prefix}terms where slug='".$slug."'");
		}

		if($mode == 'Insert') {
			if (!empty($termid)) {

				$core_instance->detailed_log[$line_number]['Message'] = 'Skipped Product tag';
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
				$returnArr['Mode'] = $mode;
				return $returnArr;
				#skipped
			}else{
				$wpdb->query("insert into {$wpdb->prefix}terms(name,slug) values ('".$name."','".$slug."')");
				$id = $wpdb->insert_id;
				$wpdb->query("insert into {$wpdb->prefix}term_taxonomy(term_taxonomy_id,term_id,taxonomy,description) values('".$id."','".$id."','product_tag','".$description."')");
				
				$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Product tag ID: '.$id;
				$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
				$core_instance->detailed_log[$line_number]['id'] = $id;
				$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");
			}
		}
		
		if($mode == 'Update'){
			if (!empty($termid)) {
				foreach($termid as $term =>$value) {
					$id = $value;
				}
				$wpdb->query("update {$wpdb->prefix}terms set name='".$name."',slug='".$slug."' where term_id='".$id."'");
				$wpdb->query("update {$wpdb->prefix}term_taxonomy set term_taxonomy_id='".$id."',term_id='".$id."',taxonomy='product_tag',description='".$description."' where term_id='".$id."'"); 
				
				$core_instance->detailed_log[$line_number]['Message'] = 'Updated Product tag ID: '.$id;
				$core_instance->detailed_log[$line_number]['id'] = $id;
				$core_instance->detailed_log[$line_number]['state'] = 'Updated';
				$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");
			}else{
				$core_instance->detailed_log[$line_number]['Message'] = 'Skipped.';
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");
				$returnArr['Mode'] = $mode;
				return $returnArr;
				//return array('MODE' => $mode);
			}
		}
		$returnArr['ID'] = $id;
		return $returnArr;
	}
	public function woocommerce_product_import($data_array, $mode, $type, $unmatched_row , $check , $unikey , $unikey_name, $line_number  , $acf,$pods, $toolset, $header_array ,$value_array, $wpml_values,$poly_values,$update_based_on) {

		global $wpdb,$core_instance,$sitepress; 
		$wpml_values = null;
		$core_instance = CoreFieldsImport::getInstance();
		$helpers_instance = ImportHelpers::getInstance();
		$log_table_name = $wpdb->prefix ."import_detail_log";
		$data_array['PRODUCTSKU']=isset($data_array['PRODUCTSKU'])?$data_array['PRODUCTSKU']:'';
		$data_array['PRODUCTSKU'] = trim($data_array['PRODUCTSKU']);
		$returnArr = array();
		$assigned_author = '';
		$mode_of_affect = 'Inserted';
		$data_array['post_type'] = 'product';
		$data_array = $core_instance->import_core_fields($data_array , $mode ,$line_number);
		$post_type = $data_array['post_type'];
		if($check == 'ID'){
			if(!empty($data_array['ID'])){
			$ID = $data_array['ID'];	
			if($sitepress != null && isset($wpml_values['language_code']) && !empty($wpml_values['language_code'])) {
				$language_code = $wpml_values['language_code'];
				$get_result =  $wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p join {$wpdb->prefix}icl_translations pm ON p.ID = pm.element_id WHERE p.ID = $title AND p.post_type = '$post_type' AND p.post_status != 'trash' AND pm.language_code = '{$language_code}'");
			}
			elseif(isset($poly_values) && !empty($poly_values)){
				$language_code = $poly_values['language_code'];
				if(!empty($ID)){
					$get_result=$wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts as p inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and p.ID=$ID AND p.post_status != 'trash'");
				}
			}
			else{
				$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE ID = '$ID' AND post_type = '$post_type' AND post_status != 'trash' order by ID DESC ");			
			}
		}
		}
		if($check == 'post_title'){
			if(!empty($data_array['post_title'])){
			$title = $data_array['post_title'];
			if($sitepress != null && isset($wpml_values['language_code']) && !empty($wpml_values['language_code'])) {
				$language_code = $wpml_values['language_code'];
				$get_result =  $wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p join {$wpdb->prefix}icl_translations pm ON p.ID = pm.element_id WHERE p.post_title = '$title' AND p.post_type = '$post_type' AND p.post_status != 'trash' AND pm.language_code = '{$language_code}'");
			}
			elseif(isset($poly_values) && !empty($poly_values)){
				$language_code = $poly_values['language_code'];
				$get_result=$wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts as p inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and p.post_title='$title' AND p.post_status != 'trash'");
			}
			else{
				$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = \"$title\" AND post_type = \"$post_type\" AND post_status != \"trash\" order by ID DESC ");		
			}
		}
		}
		if($check == 'post_name'){
			$name = $data_array['post_name'];
			if(!empty($data_array['post_name'])){
			if($sitepress != null && isset($wpml_values['language_code']) && !empty($wpml_values['language_code'])) {
				$language_code = $wpml_values['language_code'];
				$get_result =  $wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p join {$wpdb->prefix}icl_translations pm ON p.ID = pm.element_id WHERE p.post_name = '$name' AND p.post_type = '$post_type' AND p.post_status != 'trash' AND pm.language_code = '{$language_code}'");
			}
			elseif(isset($poly_values) && !empty($poly_values)){
				$language_code = $poly_values['language_code'];
				$get_result=$wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts as p inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and p.post_name='$name'");
			}
			else{
			$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_name = '$name' AND post_type = '$post_type' AND post_status != 'trash' order by ID DESC ");	
			}
			}
		}
		if($check == 'PRODUCTSKU'){
			if(!empty($data_array['PRODUCTSKU'])){
			$sku = $data_array['PRODUCTSKU'];
			if($sitepress != null && isset($wpml_values['language_code']) && !empty($wpml_values['language_code'])) {
				$language_code = $wpml_values['language_code'];
				$get_result =  $wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p join {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id inner join {$wpdb->prefix}icl_translations icl ON pm.post_id = icl.element_id WHERE p.post_type = 'product' AND p.post_status != 'trash' and pm.meta_value = '$sku' and icl.language_code = '{$language_code}'");               
			}
			elseif(isset($poly_values) && !empty($poly_values)){
				$language_code = $poly_values['language_code'];
				$get_result=$wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts as p inner join {$wpdb->prefix}postmeta pm ON p.ID=pm.post_id inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and p.post_name='$name' and pm.meta_value = '$sku'");
			}
			else{
				$get_result =  $wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p join {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id WHERE p.post_type = 'product' AND p.post_status != 'trash' and pm.meta_value = '$sku' ");
			}
		}
		}

		$update = array('ID','post_title','post_name','PRODUCTSKU');
		if($update_based_on == 'skip' && in_array($check, $update)){
			if(empty($get_result)) {
				$core_instance->detailed_log[$line_number]['Message'] = "Skipped,Due to existing ".$check." is not presents.";
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
			}	
		}
			if(!in_array($check, $update)){
				if(is_plugin_active('advanced-custom-fields-pro/acf.php')||is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('secure-custom-fields/secure-custom-fields.php')){
					if(is_array($acf)){
						$get_key = "";
						foreach($acf as $acf_key => $acf_value){
							if($acf_key == $check){
								$get_key= array_search($acf_value , $header_array);
							}
							if(isset($get_key) && !empty($value_array[$get_key])){
								$csv_element = $value_array[$get_key];                                         
								$get_result = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta as a join {$wpdb->prefix}posts as b on a.post_id = b.ID WHERE a.meta_key = '$check' AND a.meta_value = '$csv_element' AND b.post_status != 'trash' order by a.post_id DESC ");
							}
						}	
					}		
					if(empty($get_result)) {
						$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to existing field value is not presents.";
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					}
				}
			}
			if(!in_array($check, $update)){
				if(is_plugin_active('pods/init.php')){
					if(is_array($pods)){
						
						foreach($pods as $pods_key => $pods_value){
							if($pods_key == $check){
								$get_key= array_search($pods_value , $header_array);
							}
							if(isset($value_array[$get_key])){
								$csv_element = $value_array[$get_key];	
							}
							
							$get_result = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta as a join {$wpdb->prefix}posts as b on a.post_id = b.ID WHERE a.meta_key = '$check' AND a.meta_value = '$csv_element' AND b.post_status != 'trash' order by a.post_id DESC ");
						}	
					}	
					if(empty($get_result)) {
						$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to existing field value is not presents.";
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					}	
				}
			}
			if(!in_array($check, $update)){
				if(is_plugin_active('types/wpcf.php')){
					if(is_array($toolset)){
						foreach($toolset as $tool_key => $tool_value){
							if($tool_key == $check){
								$get_key= array_search($tool_value , $header_array);
							}
							if(isset($value_array[$get_key]) && isset($get_key)){
								$csv_element = $value_array[$get_key];
								$key='wpcf-'.$check;
								$get_result = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta as a join {$wpdb->prefix}posts as b on a.post_id = b.ID WHERE a.meta_key = '$key' AND a.meta_value = '$csv_element' AND b.post_status != 'trash' order by a.post_id DESC ");	
							}
							
						}	
					}	
					if(empty($get_result)) {
						$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to existing field value is not presents.";
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					}	
				}
			}
	
		$updated_row_counts = $helpers_instance->update_count($unikey,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];

		if ($mode == 'Insert') {
			if(!isset( $data_array['post_date'] )) {
				$data_array['post_date'] = current_time('Y-m-d H:i:s');
			} else {	
				if(strtotime( $data_array['post_date'] )) {
					$data_array['post_date'] = date( 'Y-m-d H:i:s', strtotime( $data_array['post_date'] ) );
				} else {
					$data_array['post_date'] = current_time('Y-m-d H:i:s');
				}
			}
			if (isset($get_result) && is_array($get_result) && !empty($get_result)) {
				#skipped
				$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to duplicate Product found!.";
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
				return array('MODE' => $mode);
			}else{
				
				$post_id = wp_insert_post($data_array); 
				$data_array['post_format']=isset($data_array['post_format'])?$data_array['post_format']:'';
				set_post_format($post_id , $data_array['post_format']);	
				if(!empty($data_array['PRODUCTSKU'])){
					update_post_meta($post_id , '_sku' , $data_array['PRODUCTSKU']);
				}
				if(is_wp_error($post_id) || $post_id == '') {
					# skipped
					// $core_instance->detailed_log[$line_number]['Message'] = "Can't insert this Product. " .$post_id->get_error_message();
					
					if(is_wp_error($post_id)){
						$core_instance->detailed_log[$line_number]['Message'] = "Can't insert this Product. " .$post_id->get_error_message();
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					}
					else{
						$core_instance->detailed_log[$line_number]['Message'] = "Can't insert this Product. " ;
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					}
					
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
					return array('MODE' => $mode);
				}

				if($unmatched_row == 'true'){
					global $wpdb;
					$post_entries_table = $wpdb->prefix ."post_entries_table";
					$file_table_name = $wpdb->prefix."smackcsv_file_events";
					$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `$unikey_name` = '$unikey'");	
					$file_name = $get_id[0]->file_name;
					$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$post_id}','{$type}', '{$file_name}','Inserted')");
				}

				$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Product ID: ' . $post_id . ', ' . $assigned_author;
				$core_instance->detailed_log[$line_number]['webLink'] = get_permalink( $post_id );
				$core_instance->detailed_log[$line_number]['id'] = $post_id;
				$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id, true );
				$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
				$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");
			}	
		}	
		if($mode == 'Update'){			
			if(isset($core_instance->detailed_log[$line_number]['Message']) && preg_match("(Skipped)", $core_instance->detailed_log[$line_number]['Message']) !== 0) {					
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
				$returnArr['MODE'] = $mode_of_affect;
			}	
			else {						
			if (is_array($get_result) && !empty($get_result)) {
				if(!in_array($check, $update)){
					$post_id = $get_result[0]->post_id;		
					$data_array['ID'] = $post_id;
				}else{
					$post_id = $get_result[0]->ID;	
					$data_array['ID'] = $post_id;
				}
				wp_update_post($data_array);
				$data_array['post_format']=isset($data_array['post_format'])?$data_array['post_format']:'';
				set_post_format($post_id , $data_array['post_format']);		
				if(!empty($data_array['PRODUCTSKU'])){
					update_post_meta($post_id , '_sku' , $data_array['PRODUCTSKU']);
				}

				if($unmatched_row == 'true'){
					global $wpdb;
					$post_entries_table = $wpdb->prefix ."post_entries_table";
					$file_table_name = $wpdb->prefix."smackcsv_file_events";
					$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `$unikey_name` = '$unikey'");	
					$file_name = $get_id[0]->file_name;
					$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$post_id}','{$type}', '{$file_name}','Updated')");
				}

				$core_instance->detailed_log[$line_number]['Message'] = 'Updated Product ID: ' . $post_id . ', ' . $assigned_author;
				$core_instance->detailed_log[$line_number]['id'] = $post_id;
				$core_instance->detailed_log[$line_number]['webLink'] = get_permalink( $post_id );
				$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id, true );
				$core_instance->detailed_log[$line_number]['state'] = 'Updated';
				$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");

			}else{
				$posttitle = $data_array['post_title'];
				$productsku = $data_array['PRODUCTSKU'];
				if(!empty($posttitle)){
					$post_id = wp_insert_post($data_array); 
					$data_array['post_format'] = isset($data_array['post_format'])?$data_array['post_format']:'';
					set_post_format($post_id , $data_array['post_format']);
					if(is_wp_error($post_id) || $post_id == '') {
						# skipped
						if(is_wp_error($post_id)){
							$core_instance->detailed_log[$line_number]['Message'] = "Can't insert this Product. " .$post_id->get_error_message();
							$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						}
						else{
							$core_instance->detailed_log[$line_number]['Message'] = "Can't insert this Product. " ;
							$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						}						
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
						return array('MODE' => $mode);
					}
					if($unmatched_row == 'true'){
						global $wpdb;
						$post_entries_table = $wpdb->prefix ."post_entries_table";
						$file_table_name = $wpdb->prefix."smackcsv_file_events";
						$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `$unikey_name` = '$unikey'");	
						$file_name = $get_id[0]->file_name;
						$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$post_id}','{$type}', '{$file_name}','Inserted')");
					}
					$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Product ID: ' . $post_id . ', ' . $assigned_author;
					$core_instance->detailed_log[$line_number]['id'] = $post_id;
					$core_instance->detailed_log[$line_number]['Sku'] = $data_array['PRODUCTSKU'];
					$core_instance->detailed_log[$line_number]['webLink'] = get_permalink( $post_id );
					$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id, true );
					$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
					$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");	
				}
				else{
					$core_instance->detailed_log[$line_number]["Message"] = "Skipped. SKU: ".$productsku."<br>";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey'");
					// return array('MODE' => $mode);
				}
				
			
			}
		}

		}

		$returnArr['ID'] = $post_id;
		$returnArr['MODE'] = $mode_of_affect;
		if (!empty($data_array['post_author'])) {
			$returnArr['AUTHOR'] = isset($assigned_author) ? $assigned_author : '';
		}
		return $returnArr;
	}
	public function woocommerce_product_import_new($post_values , $mode , $type, $unmatched_row, $check , $unikey_value , $unikey_name, $line_number, $acf ,$metabox,$pods, $toolset,$jetengine,$header_array, $value_array,  $wpml_values,$poly_values,$update_based_on,$product_meta_data,$attr_meta_data,$image_meta,$post_cat_list,$isCategory,$categoryList){
	try{
		if(!empty($product_meta_data)){
			$post_values = array_merge($post_values,$product_meta_data);
		}
		else{
			$post_values = $post_values;
		}
		
		global $wpdb;
		global $wpdb,$core_instance,$sitepress; 
		$wpml_values = null;
		$core_instance = CoreFieldsImport::getInstance();
		$helpers_instance = ImportHelpers::getInstance();
		$media_instance = MediaHandling::getInstance();

		$log_table_name = $wpdb->prefix ."import_detail_log";
		$returnArr = array();
		$assigned_author = '';
		$mode_of_affect = 'Inserted';
		$updated_row_counts = $helpers_instance->update_count($unikey_value,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];
		$product_type = !empty($post_values['product_type']) ? $post_values['product_type'] : 1;
		if (is_plugin_active('jet-booking/jet-booking.php')){
			$booking_type = trim(jet_abaf()->settings->get( 'apartment_post_type' ));
		}
		if (class_exists('WC_Product')) {
			if($product_type == 'variation' || $product_type== 8){
				$post_type = 'product_variation';
			}
			else{
				$post_type = 'product';
			}
			$sku = $post_values['PRODUCTSKU'];
			if($check == 'ID'){	
				$ID = $post_values['ID'];	
				if($sitepress != null && isset($wpml_values['language_code']) && !empty($wpml_values['language_code'])) {
					$language_code = $wpml_values['language_code'];
					$get_result =  $wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p join {$wpdb->prefix}icl_translations pm ON p.ID = pm.element_id WHERE p.ID = $title AND p.post_type = '$post_type' AND p.post_status != 'trash' AND pm.language_code = '{$language_code}'");
				}
				elseif(isset($poly_values) && !empty($poly_values)){
					$language_code = $poly_values['language_code'];
					if(!empty($ID)){
						$get_result=$wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts as p inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and p.ID=$ID AND p.post_status != 'trash'");
					}
				}
				else{
					$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE ID = '$ID' AND post_type = '$post_type' AND post_status != 'trash' order by ID DESC ");			
				}
			}
			if($check == 'post_title'){
				$title = $post_values['post_title'];
				if($sitepress != null && isset($wpml_values['language_code']) && !empty($wpml_values['language_code'])) {
					$language_code = $wpml_values['language_code'];
					$get_result =  $wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p join {$wpdb->prefix}icl_translations pm ON p.ID = pm.element_id WHERE p.post_title = '$title' AND p.post_type = '$post_type' AND p.post_status != 'trash' AND pm.language_code = '{$language_code}'");
				}
				elseif(isset($poly_values) && !empty($poly_values)){
					$language_code = $poly_values['language_code'];
					$get_result=$wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts as p inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and p.post_title='$title' AND p.post_status != 'trash'");
				}
				else{
					$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = \"$title\" AND post_type = \"$post_type\" AND post_status != \"trash\" order by ID DESC ");		
				}
				
			}
			if($check == 'post_name'){
				$name = $post_values['post_name'];
				if($sitepress != null && isset($wpml_values['language_code']) && !empty($wpml_values['language_code'])) {
					$language_code = $wpml_values['language_code'];
					$get_result =  $wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p join {$wpdb->prefix}icl_translations pm ON p.ID = pm.element_id WHERE p.post_name = '$name' AND p.post_type = '$post_type' AND p.post_status != 'trash' AND pm.language_code = '{$language_code}'");
				}
				elseif(isset($poly_values) && !empty($poly_values)){
					$language_code = $poly_values['language_code'];
					$get_result=$wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts as p inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and p.post_name='$name'");
				}
				else{
				$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_name = '$name' AND post_type = '$post_type' AND post_status != 'trash' order by ID DESC ");	
				}
			}
			if($check == 'PRODUCTSKU'){
				$sku = $post_values['PRODUCTSKU'];
				if($sitepress != null && isset($wpml_values['language_code']) && !empty($wpml_values['language_code'])) {
					$language_code = $wpml_values['language_code'];
					$get_result =  $wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p join {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id inner join {$wpdb->prefix}icl_translations icl ON pm.post_id = icl.element_id WHERE p.post_type = '$post_type' AND p.post_status != 'trash' and pm.meta_value = '$sku' and icl.language_code = '{$language_code}'");               
				}
				elseif(isset($poly_values) && !empty($poly_values)){
					$language_code = $poly_values['language_code'];
					$get_result=$wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts as p inner join {$wpdb->prefix}postmeta pm ON p.ID=pm.post_id inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and pm.meta_value = '$sku'");
				}
				else{
					$get_result =  $wpdb->get_results("SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p join {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id WHERE p.post_type = '$post_type' AND p.post_status != 'trash' and pm.meta_value = '$sku' ");
				}
			}
			$update = array('ID','post_title','post_name','PRODUCTSKU');
			if($update_based_on == 'skip' && in_array($check, $update)){
				if(empty($get_result)) {
					$core_instance->detailed_log[$line_number]['Message'] = "Skipped,Due to existing ".$check." is not presents.";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				}	
			}
			if(!in_array($check, $update)){
				if($update_based_on == 'acf'){
					if(is_plugin_active('advanced-custom-fields-pro/acf.php')||is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('secure-custom-fields/secure-custom-fields.php')){
						$get_result = $core_instance->custom_fields_update_based_on($update_based_on, $acf, $check, $header_array, $value_array,$type,$line_number);
					}
				}
				else if ($update_based_on == 'jetengine') {
					if (is_plugin_active('jet-engine/jet-engine.php')) {
						$get_result = $core_instance->custom_fields_update_based_on($update_based_on, $jetengine, $check, $header_array, $value_array,$type,$line_number);
					}
				}
				elseif($update_based_on == 'toolset'){
					if(is_plugin_active('types/wpcf.php')){
						$get_result = $core_instance->custom_fields_update_based_on($update_based_on, $toolset, $check, $header_array, $value_array,$type,$line_number);
					}
				}
				elseif($update_based_on == 'metabox'){
					if(is_plugin_active('meta-box/meta-box.php') || is_plugin_active('meta-box-aio/meta-box-aio.php')  || is_plugin_active('meta-box-lite/meta-box-lite.php')){
						$get_result = $core_instance->custom_fields_update_based_on($update_based_on, $metabox, $check, $header_array, $value_array,$type,$line_number);
					}
				}
				if($update_based_on == 'pods'){
					if(is_plugin_active('pods/init.php')){
						$get_result = $core_instance->custom_fields_update_based_on($update_based_on, $pods, $check, $header_array, $value_array,$type,$line_number);
					}
				}
			}
			if ($isCategory) {
				$get_result_check = $core_instance->get_matching_posts_by_category($helpers_instance, $post_cat_list, $categoryList ,$header_array, $value_array, 'WooCommerce','product', $wpdb,$get_result,$mode);
				if ($mode === 'Update' && !empty($check) && !empty($get_result) && !$get_result_check) {
					$get_result = [];
				}
			}
			if($mode == 'Insert'){
				if($isCategory && !($get_result_check)){
					$core_instance->detailed_log[$line_number]['Message'] =  "Skipped, Record does not match the selected categories.";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
					return array('MODE' => $mode);
				}
				else if (isset($get_result) && is_array($get_result) && !empty($get_result)) {
					#skipped
					$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to duplicate Product found!.";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
					return array('MODE' => $mode);
				}
				else{
					$post_values['product_type'] = (isset($post_values['product_type']) && !empty($post_values['product_type'])) ? $post_values['product_type'] : 'simple';
					if (isset($post_values['product_type']) && !empty($post_values['product_type'])) {
						$product_type =$post_values['product_type'];
						if ($post_values['product_type'] == 1) {
							$product_type = 'simple';
						}
						if ($post_values['product_type'] == 2) {
							$product_type = 'grouped';
						}
						if ($post_values['product_type'] == 3) {
							$product_type = 'external';
						}
						if ($post_values['product_type'] == 4) {
							$product_type = 'variable';
						}
						if ($post_values['product_type'] == 5) {
							$product_type = 'subscription';
						}
						if ($post_values['product_type'] == 6) {
							$product_type = 'variable-subscription';
						}
						if ($post_values['product_type'] == 7) {
							$product_type = 'bundle';
						}	
						if($post_values['product_type'] == 8){
							$product_type = 'variation';
						}
						if(class_exists('WC_Product_Jet_Booking') && $post_values['product_type'] == 9){
							$product_type = 'jet_booking';
						}
						if($product_type == 'external'){
							$product = new \WC_Product_External();
						}
						elseif($product_type == 'variable'){
							$product = new \WC_Product_Variable();
						}
						elseif($product_type == 'grouped'){
							$product = new	\WC_Product_Grouped();
						}
						elseif($product_type == 'variation'){
							$product = new \WC_Product_Variation();							
						}
						elseif($product_type == 'bundle'){
							$product = new \WC_Product_Bundle();							
						}
						elseif($product_type == 'jet_booking'){   
							$product = new \WC_Product_Jet_Booking();							
						}
						else{
							$product = new  \WC_Product_Simple();
						}
						
						$title = $post_values['post_title'];
						$post_name = $post_values['post_name'] ?? '';
						$post_status = $post_values['post_status'] ?? 'publish';
						if($product_type == 'variation'){
							$product->set_status('publish');
						}
						else{
							$product->set_status($post_status);
						}
						// Generate slug if post_name is empty
						if (empty($post_name)) {
							$post_name = sanitize_title($title);
						}
						$product->set_name($title);
						$product->set_slug($post_name);
												
						// Set the SKU for the current product if it doesn't already exist.
						$prod_sku = $post_values['PRODUCTSKU'] ?? null;
						$sku_check = isset($prod_sku) ?  wc_get_product_id_by_sku( wc_clean($prod_sku) ) : 1;
						if (($sku_check == 0)  && empty($poly_values)) {
							$product->set_sku(wc_clean($prod_sku));
						}
						else{
							if(!empty($poly_values)){
								$product->save();
								$product_id = $product->get_id();
								update_post_meta($product_id, '_sku', $prod_sku);
							}
						}
						$product_id = $product->save();
						$core_instance->detailed_log[$line_number]['Type_of_Product'] = $product_type;
						wp_set_object_terms($product_id, $product_type, 'product_type');
					}
					if($unmatched_row == 'true'){
						global $wpdb;
						$post_entries_table = $wpdb->prefix ."post_entries_table";
						$file_table_name = $wpdb->prefix."smackcsv_file_events";
						$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `$unikey_name` = '$unikey_value'");	
						$file_name = $get_id[0]->file_name;
						$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$product_id}','{$type}', '{$file_name}','Inserted')");
					}
	
					$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Product ID: ' . $product_id . ', ' . $assigned_author;
					$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
					$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey_value'");
				}
				
			}
			elseif($mode == 'Update'){
				if($isCategory && empty($get_result)){
					$core_instance->detailed_log[$line_number]['Message'] =  "Skipped, Record does not match the selected categories.";
					$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				}
				if(isset($core_instance->detailed_log[$line_number]['Message']) && preg_match("(Skipped)", $core_instance->detailed_log[$line_number]['Message']) !== 0) {					
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
					$returnArr['MODE'] = $mode_of_affect;
				}	
				else {	

					if (is_array($get_result) && !empty($get_result)) {
						if(!in_array($check, $update)){
							$post_id = $get_result[0]->post_id;		
							$data_array['ID'] = $post_id;
						}else{
							$post_id = $get_result[0]->ID;	
							$data_array['ID'] = $post_id;
						}
						$mode_of_affect = 'Updated';
						$product = wc_get_product($post_id);
						$product_type = $product->get_type();

						if(!empty($post_values['post_title'])){
							$product->set_name( $post_values['post_title'] );
						}
						if($product_type == 'variation'){
							$product->set_status('publish');
						}
						else{
							$product->set_status($post_status);
						}
						$product_id = $product->save();
						if($unmatched_row == 'true'){
							global $wpdb;
							$post_entries_table = $wpdb->prefix ."post_entries_table";
							$file_table_name = $wpdb->prefix."smackcsv_file_events";
							$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `$unikey_name` = '$unikey_value'");	
							$file_name = $get_id[0]->file_name;
							$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$post_id}','{$type}', '{$file_name}','Updated')");
						}
	
						$core_instance->detailed_log[$line_number]['Message'] = 'Updated Product ID: ' . $product_id . ', ' . $assigned_author;
						$core_instance->detailed_log[$line_number]['state'] = 'Updated';
						$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey_value'");
					}	
					else{
						$post_title = $post_values['post_title'];
						$product_type = !empty($post_values['product_type'])?$post_values['product_type']:'simple';
						
						if(!empty($post_title)){
							$product_types = [
								1 => 'simple',
								2 => 'grouped',
								3 => 'external',
								4 => 'variable',
								5 => 'subscription',
								6 => 'variable-subscription',
								7 => 'bundle',
								8 => 'variation',
								9 => 'jet_booking'
							];
						
							if (isset($product_types[$product_type])) {
								$product_type = $product_types[$product_type];
							}
							// Create the appropriate WooCommerce product object
							switch ($product_type) {
								case 'external':
									$product = new \WC_Product_External();
									break;
								case 'variable':
									$product = new \WC_Product_Variable();
									break;
								case 'grouped':
									$product = new \WC_Product_Grouped();
									break;
								case 'variation':
									$product = new \WC_Product_Variation();
									break;
								case 'bundle':
									$product = new \WC_Product_Bundle();	
									break;
								case 'jet_booking':
									$product = new \WC_Product_Jet_Booking();
									break;
								default:
									$product = new \WC_Product_Simple();
									break;
							}
						
							$product->set_name($post_title);
							$post_status = !empty($post_values['post_status']) ? $post_values['post_status'] : 'publish';
							if($product_type == 'variation'){
								$product->set_status('publish');
							}
							else{
								$product->set_status($post_status);
							}
						
							$prod_sku = trim($post_values['PRODUCTSKU']);
							if (empty($poly_values)) {
								$existing_product = wc_get_product_id_by_sku($prod_sku);
								if ($existing_product) {
									// Update existing product if SKU matches
									$product = wc_get_product($existing_product);
									$product->set_name($post_title);
									if($product_type == 'variation'){
										$product->set_status('publish');
									}
									else{
										$product->set_status($post_status);
									}
								} else {
									// Create new product if SKU does not exist
									$product->set_sku($prod_sku);
								}
							}
							$product_id = $product->save();
							if (!empty($poly_values)) {
								update_post_meta($product_id, '_sku', $prod_sku);
							}
						
							if($unmatched_row == 'true'){
								global $wpdb;
								$post_entries_table = $wpdb->prefix ."post_entries_table";
								$file_table_name = $wpdb->prefix."smackcsv_file_events";
								$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `$unikey_name` = '$unikey_value'");	
								$file_name = $get_id[0]->file_name;
								$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$product_id}','{$type}', '{$file_name}','Inserted')");
							}
			
							$core_instance->detailed_log[$line_number]['Message'] = 'Inserted Product ID: ' . $product_id . ', ' . $assigned_author;
							$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
							$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey_value'");
						}
					}
				}
			}
		if(!empty($product)){
			foreach($post_values as $post_key => $post_val){
				switch($post_key){
					case 'post_name':
						if(!empty($post_values[$post_key])){
							$product->set_slug($post_values[$post_key]); 
						}
						break;
					case 'stock':
						if(!empty($post_values[$post_key])){
							$stock_status = 'instock';
							if (!empty($stock_status) && !$product->is_type('external') && method_exists($product, 'set_stock_status')) {
								$product->set_manage_stock( true ); 
								$product->set_stock_status($stock_status);
							}
						}
						// elseif ($post_values[$post_key] == 0 || $post_values[$post_key] == ''){
						// 	$stock_status = 'outofstock';
						// 	if (!empty($stock_status) && !$product->is_type('external') && method_exists($product, 'set_stock_status')) {
						// 		$product->set_stock_status($stock_status);
						// 	}
						// }
						$product->set_stock_quantity($post_values[$post_key]);
						$product_id = $product->save();
						$product = wc_get_product($product_id);
						break;
					case '_global_unique_id':
					if(!empty($post_values[$post_key])){
						if (is_numeric($post_values[$post_key])) {
							$validated_value = number_format((float)$post_values[$post_key], 0, '.', '');
							// Check if the global unique ID is already assigned to another product
							$args = [
								'post_type'      => 'product',
								'post_status'    => ['publish', 'draft', 'pending', 'private'], // Exclude 'trash'
								'meta_query'     => [
									[
										'key'     => '_global_unique_id', // Replace with the actual meta key for global_unique_id
										'value'   => $validated_value,
										'compare' => '='
									]
								],
								'fields'         => 'ids',
								'posts_per_page' => 1,
							];
							$existing_products = get_posts($args);
							if(empty($existing_products)){
								$product->set_global_unique_id($validated_value);
								if(is_plugin_active('ean-for-woocommerce/ean-for-woocommerce.php')){
									update_post_meta($product_id, '_alg_ean', $validated_value);
								}
							}
						}
					}
					break;
					case 'stock_status':
						if($post_values[$post_key] == 'yes' || $post_values[$post_key] == '1'){
							$stock_status = 'instock';
							$product->set_stock_status($stock_status);
						}
						else if($post_values[$post_key] == 'no' || $post_values[$post_key] == '2'){
							$stock_status = 'outofstock';
							$product->set_stock_status($stock_status);
						}
						break;
					case 'price':
						$product->set_price($post_values[$post_key]); 
						break;
					case 'regular_price': 
						$product->set_regular_price($post_values[$post_key]);
						break;
					case 'sale_price':
						$product->set_sale_price($post_values[$post_key]);
						break;
					case 'manage_stock':
						if($post_values[$post_key] == 'yes' || $post_values[$post_key] == 1){
							$product->set_manage_stock( true ); 
						}
						elseif($post_values[$post_key] == 'no' || $post_values[$post_key] == 0){
							$product->set_manage_stock(false); 
						}
						break;
					// case 'PRODUCTSKU':
					// 	$product->set_sku($post_values[$post_key]);
					// 	break;
					case 'weight':
						$product->set_weight($post_values[$post_key]);
						break;
					case 'length':
						$product->set_length($post_values[$post_key]); 
						break;
					case 'height':
						$product->set_height($post_values[$post_key]);
						break;
					case 'width':
						$product->set_width($post_values[$post_key]);
						break;
					case 'tax_status':
						
						if(is_numeric($post_values[$post_key])){
							if ($post_values[$post_key] == 1) {
								$tax_status = 'taxable';
							}
							if ($post_values[$post_key] == 2) {
								$tax_status = 'shipping';
							}
							if ($post_values[$post_key] == 3) {
								$tax_status = 'none';
							}
						}
						else{
							$tax_status_array = array('taxable','shipping','none' );
							$tax_data =$post_values[$post_key];
							if(!empty($post_values[$post_key])&& in_array($tax_data,$tax_status_array)){
								$tax_status = $post_values[$post_key];
							}
						}
						if (method_exists($product, 'set_tax_status')) {
							$product->set_tax_status($tax_status);
						}
						break;
					case 'tax_class':
						$tax_class = !empty($post_values[$post_key]) ? $post_values[$post_key] : '';
						if (method_exists($product, 'set_tax_class')) {
							$product->set_tax_class($tax_class);
						}
						break;
					case 'post_content':
						if(isset($post_values[$post_key]) && !empty($post_values[$post_key]) && $post_values[$post_key] !==null){
							$content = html_entity_decode($post_values[$post_key]);
							$content = str_replace('\n',"\n",$content);
							$product->set_description( wp_kses_post( $content ) );
						}
						
						break;				
					case 'visibility':
						$visibility_mapping = array( '1'=>'visible','2'=>'catalog','3' => 'search','4' => 'hidden');
						if(is_numeric($post_values[$post_key])){
							$visibility=array_key_exists($post_values[$post_key],$visibility_mapping)?$visibility_mapping[$post_values[$post_key]]:'';
							if(!$product->is_type('external') && !empty($visibility) && method_exists($product, 'set_catalog_visibility')){
								$product->set_catalog_visibility($visibility);
							}
							
						}
						else{
							if(!empty($post_values[$post_key]) && method_exists($product, 'set_catalog_visibility')){
								$product->set_catalog_visibility($post_values[$post_key]);
							}
							
						}
						
						break;
					case 'post_excerpt':
						if(!empty($post_values[$post_key]) && method_exists($product, 'set_short_description')){
							$product->set_short_description($post_values[$post_key]);
						}
						break;
					case 'downloadable' :
						if($post_values[$post_key] == 'yes' || $post_values[$post_key] == 1){
							$product->set_downloadable( true );
						}
						else{
							$product->set_downloadable(false);
						}
						// $metaData['_downloadable'] = $data_array[$ekey];
						break;
					case 'virtual':
						if($post_values[$post_key] == 'yes' || $post_values[$post_key] == 1){
							$product->set_virtual(true);
						}
						else{
							$product->set_virtual(false);
						}
						break;
					case 'manage_stock':
						if($post_values[$post_key] == 'yes' || $post_values[$post_key] == 1 ){
							$product->set_manage_stock(true); 
						}
						elseif($post_values[$post_key] == 'no'){
							$product->set_manage_stock(false); 
						}
						break;
					case 'set_date_on_sale_from':
						$product->set_date_on_sale_from($post_values[$post_key]);
						break;
					case 'set_date_on_sale_to':
						$product->set_date_on_sale_to($post_values[$post_key]); 
						break;
					case 'product_shipping_class' :
					case 'variation_shipping_class' :
				
						$class_name = $post_values[$post_key];
						$class = $wpdb->get_results("SELECT term_id FROM {$wpdb->prefix}terms where name = '{$class_name}' ");
						if(!empty($class)) {
							$class_id = $class[0]->term_id;
							if ($product = wc_get_product($product_id)) {
								$product->set_shipping_class_id($class_id);
								$product->save();
							}
						}
						break;
					case 'product_image_gallery' :

						if (!empty($post_values[$post_key]) && method_exists($product, 'set_gallery_image_ids')) {
							// Check the delimiter (',' or '|') for splitting the images
							if (strpos($post_values[$post_key], ',') !== false) {
								$get_all_gallery_images = explode(',', $post_values[$post_key]);
							} elseif (strpos($post_values[$post_key], '|') !== false) {
								$get_all_gallery_images = explode('|', $post_values[$post_key]);
							} else {
								$get_all_gallery_images[] = $post_values[$post_key];
							}
	
							// Prepare an array for storing image IDs
							$gallery_image_ids = [];
							$indexs = 0;
							foreach ($get_all_gallery_images as $gallery_image) {
								// If it's already an ID, use it; otherwise, handle the image upload
								if (is_numeric($gallery_image)) {
									$media_instance->store_image_ids($i = 1);
									$gallery_image_ids[] = $gallery_image;
								} else {
									$media_instance->store_image_ids($i = 1);
									// Assuming a function to upload image and get its attachment ID exists
									$attachmentId = $media_instance->image_meta_table_entry($line_number, $image_meta, $product_id, 'product_image_gallery', $gallery_image, $unikey_value, 'product', 'post', $templatekey=null, $gmode=null, '', '', '', '', $indexs);
									$gallery_image_ids[] = $attachmentId;
								}
								$indexs++;
							}
							$product->set_gallery_image_ids($gallery_image_ids);
						}
						break;
					case 'downloadable_files' :
						$downloadable_files = '';
						if ($post_values[$post_key]) {
							$exp_key = array();
							$downloads = array();
							$product->set_downloadable( true );
							$exploded_file_data = explode('|', $post_values[$post_key]);
							foreach($exploded_file_data as $file_datas){
								$exploded_separate = explode(',', $file_datas);
								$download = new \WC_Product_Download();
								$attachment_id= $media_instance->media_handling($exploded_separate[1], $product_id,$post_values,'','','',$header_array,$value_array);
								$file_url = wp_get_attachment_url( $attachment_id ); 
								$download->set_name( $exploded_separate[0]);
								if(!empty($file_url) && isset($file_url)){
									$download->set_id( md5( $file_url ) );
									$download->set_file( $file_url );
									$downloads[] = $download;
								}else{
									$download->set_id( md5( $exploded_separate[1], ) );
									$download->set_file($exploded_separate[1], );
									$downloads[] = $download;
								}
							}
						}
						if(!empty($downloads)){
							$product->set_downloads( $downloads );
						}
						
						
						break;
					case 'download_limit':
						$product->set_download_limit($post_values[$post_key]); 
						break;
					case 'download_expiry':	
					$product->set_download_expiry($post_values[$post_key]);
					break;
					case 'crosssell_ids':
						$crosssell_id = []; // Initialize array
					
						if (!empty($post_values[$post_key])) {
							$exploded_crosssell_ids = explode(',', $post_values[$post_key]);
					
							foreach ($exploded_crosssell_ids as $crosssell_item) {
								if (is_numeric($crosssell_item)) {
									$product_id = $crosssell_item;
								} else {
									$product_id = $wpdb->get_var($wpdb->prepare(
										"SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_title = %s ORDER BY ID DESC",
										$crosssell_item
									));
								}
					
								if (!empty($product_id)) {
									$crosssell_id[] = (int) $product_id;
								}
							}
						}
					
						if (!empty($crosssell_id) && method_exists($product, 'set_cross_sell_ids')) {
							$product->set_cross_sell_ids($crosssell_id);
							$product->save();
						}
					
						break;						
					case 'upsell_ids' :
						$upcellids = '';
						if ($post_values[$post_key]) {
							$exploded_upsell_ids = explode(',', $post_values[$post_key]);
							$upcellids = $exploded_upsell_ids;
						}
						if (is_array($upcellids) || is_object($upcellids)){
							foreach($upcellids as $upsell_ids){
								if(is_numeric($upsell_ids)){
									$product_id = $upsell_ids;	
								}
								else{
									$product_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_title = '$upsell_ids' order by ID Desc ");
								}
								if(!empty($product_id)){
									$upsell_id[]=$product_id;	
								}
								
							}
							$product->set_upsell_ids($upsell_id);		
					
						}
						break;		
					case 'low_stock_threshold':
						if($post_values[$post_key] !=null && method_exists($product, 'set_low_stock_amount')){
							$product->set_low_stock_amount($post_values[$post_key]);
						}
						break;		
					case 'sold_individually' :
						if($post_values[$post_key] == 'yes' || $post_values[$post_key] == 1){
							$product->set_sold_individually( true );
						}
						elseif($post_values[$post_key] == 'no' || $post_values[$post_key] == 0){
							$product->set_sold_individually(false);
						}
						break;
					case 'backorders':
						$backorders = '';
						if ($post_values[$post_key] == 1) {
							$backorders = 'no';
						} elseif ($post_values[$post_key] == 2) {
							$backorders = 'notify';
						} elseif ($post_values[$post_key] == 3) {
							$backorders = 'yes';
						}
						if ($product->get_type() !== 'external' && method_exists($product, 'set_backorders')) {
							$product->set_backorders($backorders);
						}
						break;
					case 'grouping_product':
						if (!empty($post_values[$post_key])) {
							$grouping_product_ids = explode(',', $post_values[$post_key]);
							if ($product && 'grouped' === $product->get_type()) {
								$my_grouping_product_id = [];
								foreach ($grouping_product_ids as $grouping_product_id) {
									if (is_numeric($grouping_product_id)) {
										$my_grouping_product_id[] = (int) $grouping_product_id;
									} else {
										$my_grouping_product_id[] =  $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_title = '$grouping_product_id' order by ID Desc ");
									}
								}
								if (!empty($my_grouping_product_id)) {
									$product->set_children($my_grouping_product_id);
								}
							}
						}
						break;
					case 'purchase_note':
						// Set the purchase note meta field
						if (method_exists($product, 'set_purchase_note')) {
							$product->set_purchase_note($post_values[$post_key]);
						}
						break;
					case 'featured_product':
						// Determine if the product should be featured
						$is_featured = !empty($post_values[$post_key]) && $post_values[$post_key] == '1';
						if (method_exists($product, 'set_featured')) {
							$product->set_featured($is_featured);
						}
						break;
					case 'menu_order':
						$menu_order = intval($post_values[$post_key]);				
						break;
					case 'sale_price_dates_from':
						$date_from = strtotime($post_values[$post_key]); // Convert to timestamp
						if (method_exists($product, 'set_date_on_sale_from')) {
							$product->set_date_on_sale_from($date_from);
						}
						break;
		
					case 'sale_price_dates_to':
						$date_to = strtotime($post_values[$post_key]); // Convert to timestamp
						if (method_exists($product, 'set_date_on_sale_to')) {
							$product->set_date_on_sale_to($date_to);
						}
						break;
					case 'product_url':
						if ($product->is_type('external') && method_exists($product, 'set_product_url')) {
							$product_url = $post_values[$post_key];
							!empty($product_url) ? $product->set_product_url($product_url) : '';
						}
						break;
	
					case 'button_text':
						if ($product->is_type('external') && method_exists($product, 'set_button_text')) {
							$button_text = $post_values[$post_key];
							$product->set_button_text($button_text);
						}
						break;
					case '_tiered_price_rules_type':
					case '_fixed_price_rules':
					case '_percentage_price_rules': 
					case '_tiered_price_minimum_qty': 
					case '_administrator_tiered_price_pricing_type':
					case '_administrator_tiered_price_discount':
					case '_administrator_tiered_price_regular_price':
					case '_administrator_tiered_price_sale_price':
					case '_administrator_tiered_price_discount_type':
					case '_administrator_tiered_price_rules_type':
					case '_administrator_percentage_price_rules':
					case '_administrator_fixed_price_rules':
					case '_administrator_tiered_price_minimum_qty':
					case '_editor_tiered_price_pricing_type':
					case '_editor_tiered_price_discount':
					case '_editor_tiered_price_regular_price':
					case '_editor_tiered_price_sale_price':
					case '_editor_tiered_price_discount_type':
					case '_editor_tiered_price_rules_type':
					case '_editor_percentage_price_rules':
					case '_editor_fixed_price_rules':
					case '_editor_tiered_price_minimum_qty':
						if (!empty($post_values[$post_key])) {
							$post_values[$post_key] = strtolower($post_values[$post_key]);
							if ($post_key == '_fixed_price_rules' || $post_key == '_percentage_price_rules' || $post_key == '_administrator_fixed_price_rules' || $post_key == '_administrator_percentage_price_rules' || $post_key == '_editor_fixed_price_rules' || $post_key == '_editor_percentage_price_rules') {
								$pairs = explode('|', $post_values[$post_key]);
								$array_data = [];
								foreach ($pairs as $pair) {
									list($key, $value) = explode('->', $pair);
									$array_data[(int)$key] = $value;
								}
								update_post_meta($product_id, $post_key, $array_data);
							}else{
								update_post_meta($product_id, $post_key, $post_values[$post_key]);
							}
						}
						break;
					case '_jet_booking_has_guests' :
					case '_jet_booking_min_guests' :
					case '_jet_booking_max_guests' :
					case '_jet_booking_guests_multiplier' :
					case 'pdf_download_url':
						update_post_meta($product_id, $post_key , $post_values[$post_key]);
						break;
					case 'chained_product_detail':
					case 'chained_product_manage_stock':
						if(!empty($post_values[$post_key]) && $post_key == 'chained_product_detail'){
							$arr = array();
							$cpid_key = array();
							$chainedid = explode(',', $post_values[$post_key]);
							foreach ($chainedid as $unitid) {
								$id = $unitid;
	
								$chainedunit = explode('|', $unitid);
								if (is_numeric($chainedunit[0])) {
									$chainid = trim($chainedunit[0]);
									$unit = ltrim($chainedunit[1], ' ');
									$priced_individually = !empty($chainedunit[2]) ? strtolower($chainedunit[2]) : 'no';

									$query_result = $wpdb->get_results($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d", $chainedunit[0]));
									$product_name = $query_result[0]->post_title;
									$cpid_key[$chainid]['unit'] = $unit;
									$cpid_key[$chainid]['product_name'] = $product_name;
									$cpid_key[$chainid]['priced_individually'] = $priced_individually;
									$arr[] = $chainedunit[0];
								} else {
	
									$query_result = $wpdb->get_results($wpdb->prepare("select ID from {$wpdb->prefix}posts where post_title = %s", $chainedunit[0]));
									$product_id = $query_result[0]->ID;
									$unit = ltrim($chainedunit[1], ' ');
									$priced_individually = !empty($chainedunit[2]) ? strtolower($chainedunit[2]) : 'no';

									$cpid_key[$product_id]['unit'] = $unit;
									$cpid_key[$product_id]['product_name'] = $chainedunit[0];
									$cpid_key[$product_id]['priced_individually'] = $priced_individually;
									$arr[] = $product_id;
								}
							}
							update_post_meta($product_id, '_chained_product_detail' , $cpid_key);
							update_post_meta($product_id, '_chained_product_ids' , $arr);
						}else if(!empty($post_values[$post_key])){
							$post_values[$post_key] = strtolower($post_values[$post_key]);
							update_post_meta($product_id, '_chained_product_manage_stock' , $post_values[$post_key]);
						}

						break;
					default:
				}
				$product_id = $product->save();

			}
			

			

			if(!empty($menu_order) && isset($menu_order)){
                $update_check = wp_update_post([
                    'ID'         => $product_id,
                    'menu_order' => (int) $menu_order,
                ]);
            }
			if($product_type == 'variation' || $product_type ==8){
				$helpers_instance = ImportHelpers::getInstance();
				$parentsku = $post_values['parent'];
			if ( $poly_values ) {
    $language_code = $poly_values['language_code'];

    $parent_product_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT p.ID
             FROM {$wpdb->posts} AS p
             INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
             INNER JOIN {$wpdb->term_relationships} AS tr ON tr.object_id = p.ID
             INNER JOIN {$wpdb->term_taxonomy} AS tax ON tax.term_taxonomy_id = tr.term_taxonomy_id
             INNER JOIN {$wpdb->terms} AS t ON t.term_id = tax.term_id
             WHERE tax.taxonomy = 'language'
               AND t.slug = %s
               AND p.post_type = 'product'
               AND pm.meta_key = '_sku'
               AND pm.meta_value = %s
               AND p.post_status = 'publish'",
            $language_code,
            $parentsku
        )
    );


    if ( empty( $parent_product_id ) ) {
        $parent_product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT p.ID
                 FROM {$wpdb->posts} AS p
                 INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_sku'
                   AND pm.meta_value = %s
                   AND p.post_status != 'trash'",
                $parentsku
            )
        );
    }

    $product_id = $product->save();
    $wpdb->update(
        $wpdb->posts,
        array( 'post_parent' => $parent_product_id ),
        array( 'ID' => $product_id )
    );
wc_delete_product_transients($parent_product_id);
clean_post_cache($product_id);
$product = wc_get_product($product_id);
}
else {
    $parent_product_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT p.ID
             FROM {$wpdb->posts} AS p
             INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
             WHERE pm.meta_key = '_sku'
               AND pm.meta_value = %s
               AND p.post_status != 'trash'",
            $parentsku
        )
    );
    $product->set_parent_id($parent_product_id);
}


				$attr_data =array();
				foreach($attr_meta_data as $attr_value){
					$attr_data[] = $helpers_instance->get_header_values($attr_value,$header_array,$value_array);
				}
				foreach($attr_data as $attr_k => $attr_value){
					foreach($attr_value as $attr_key => $attr_val){
						$i=$attr_k+1;
						if(!empty($attr_value) && is_array($attr_value)){
							foreach($attr_value as $attr_key => $attr_val){
								switch($attr_key){
									case "product_attribute_name$i":
										$attr_name =$attr_val;
										break;
									case "product_attribute_value$i":
										$attr_value =$attr_val;
										break;
									case  "product_attribute_visible$i":
										// $attribute->set_visible( true );
										break;
		
								}
							}
						}
						$customAttributes = array();

foreach ( $attr_data as $attr_set ) {
    foreach ( $attr_set as $attr_key => $attr_val ) {
        if ( empty( $attr_val ) ) {
            continue;
        }

        if ( strpos( $attr_key, 'product_attribute_name' ) !== false ) {
            $attr_name = $attr_val;
        }
        if ( strpos( $attr_key, 'product_attribute_value' ) !== false ) {
            $attr_values = explode( ',', $attr_val ); 
        }
    }

    if ( ! empty( $attr_name ) && ! empty( $attr_values ) ) {
        $taxonomy_slug = wc_attribute_taxonomy_name( $attr_name );
        $slugs = array();
foreach ( $attr_values as $attr_value ) {
    $attr_value = trim( $attr_value, " \t\n\r\0\x0B'\"" ); 

    $term = get_term_by( 'name', $attr_value, $taxonomy_slug );
    if ( $term ) {
        $slugs[] = $term->slug;
    } 
}


        if ( $slugs ) {
    $customAttributes['attribute_' . $taxonomy_slug] = implode('|', $slugs);
}

    }
}

if ( ! empty( $customAttributes ) ) {
    $product->set_attributes( $customAttributes );
    $product_id = $product->save();
}

						//   
						// $product->set_manage_stock( true ); 
						
					}
				}
			}
			if(!empty($attr_meta_data) && ($product_type !='variation' && $product_type !=8)){
				$helpers_instance = ImportHelpers::getInstance();
				$attr_data =array();
				$slug = '';
				foreach($attr_meta_data as $attr_value){
					$attr_data[] = $helpers_instance->get_header_values($attr_value,$header_array,$value_array);
				}
				
				$booking_attr_index = 0;
				foreach($attr_data as $attr_k => $attr_value){
$i = $attr_k + 1;

$name_key  = "product_attribute_name{$i}";
$value_key = "product_attribute_value{$i}";

$name_field  = isset($attr_value[$name_key]) ? trim( $attr_value[$name_key] ) : '';
$value_field = isset($attr_value[$value_key]) ? trim( $attr_value[$value_key] ) : '';

if ( empty($name_field) ) {
    foreach ($attr_value as $k => $v) {
        if ( stripos($k, 'product_attribute_name') !== false && ! empty($v) ) {
            $name_field = trim($v);
            break;
        }
    }
}

if ( empty($name_field) || stripos($name_field, 'product_attribute_name') !== false ) {
    continue;
}

$attribute_exists = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
        sanitize_title( $name_field )
    )
);

if ( empty( $value_field ) && ! $attribute_exists ) {
    continue;
}

if ( empty( $value_field ) && $attribute_exists ) {
    continue;
}


$slug = 'pa_' . sanitize_title( $name_field );
$attribute = new \WC_Product_Attribute();
$attribute->set_name( $slug );


					foreach($attr_value as $attr_key => $attr_val){
						switch($attr_key){
							case "product_attribute_name$i":
								$name = $attr_val;
									if (strpos($name, '->') !== false && (is_plugin_active('woo-variation-swatches/woo-variation-swatches.php') || is_plugin_active('woo-variation-swatches-pro/woo-variation-swatches.php'))) {
										list($name, $swatch_type) = explode('->', $name);
										$name = $this->import_swatch_and_booking_types(array($name => $swatch_type));
									}elseif(is_plugin_active('jet-booking/jet-booking.php') && ($booking_type == 'product' && $product_type == 'jet_booking')){
										$name = $this->import_swatch_and_booking_types(array($name => 'jet_booking_service') ,'jet_booking');		
									}
									$product_attribute_taxonomy = "product_attribute_taxonomy$i";
									$is_taxonomy = isset($attr_value[$product_attribute_taxonomy]) ? (bool) $attr_value[$product_attribute_taxonomy] : false;
									$slug = 'pa_' . sanitize_title($name);
									$attribute->set_name($slug);
									$attribute_exists = $wpdb->get_var($wpdb->prepare("SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s", sanitize_title($name)));
									if (
    !empty($attr_value["product_attribute_value$i"]) &&
    !$attribute_exists
) {
										$wpdb->insert(
											"{$wpdb->prefix}woocommerce_attribute_taxonomies",
											[
												'attribute_name' => sanitize_title($name),
												'attribute_label' => ucfirst($name),
												'attribute_type' => 'select',
												'attribute_orderby' => 'menu_order',
												'attribute_public' => 1,
											],
											['%s', '%s', '%s', '%s', '%d']
										);

										$attribute_id = $wpdb->insert_id;

										// Register the taxonomy after inserting
										register_taxonomy($slug, 'product', [
											'label' => ucfirst($name),
											'public' => true,
											'hierarchical' => false,
											'show_ui' => true,
											'show_admin_column' => true,
											'query_var' => true,
											'show_in_quick_edit' => true,
											'rewrite' => ['slug' => sanitize_title($name)],
										]);
										delete_transient('wc_attribute_taxonomies');
									}
									$attribute_id = $wpdb->get_var($wpdb->prepare("SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s", sanitize_title($name)));
								break;
							case "product_attribute_value$i":
								$value= explode(',',$attr_val);
								$attribute_datas = [];
								$term_val = explode(',',$attr_val);
								foreach ($term_val as $term_values) {
									if (strpos($term_values, '->') !== false && (is_plugin_active('woo-variation-swatches/woo-variation-swatches.php') || is_plugin_active('woo-variation-swatches-pro/woo-variation-swatches.php'))) {
										list($value, $image_id) = explode('->', $term_values);
										$attribute_datas[$name][$value] = $image_id; // Use dynamic attribute name
									}
								}
								if (!empty($attribute_datas) && isset($attribute_datas)) {
									$values = $this->import_swatch_values($attribute_datas);
									$values = !empty($values) ? explode('|', $values) : [];
								}
								 else {
									$values = $term_val;
								}
								$term_ids = [];
								global $wpdb;

								foreach ($values as $term_name) {
									// Check if the term already exists.
									$existing_term = get_term_by('name', $term_name, $slug);
								
									if (!$existing_term) {
										// Insert the term if it doesn't exist.
										$inserted_term = wp_insert_term($term_name, $slug);
								
										if (!is_wp_error($inserted_term)) {
											$term_id = $inserted_term['term_id'];
											$term_ids[] = $term_id;
											$existing_term = get_term_by('id', $term_id, $slug);
								
											// Jet Booking plugin integration.
											if (is_plugin_active('jet-booking/jet-booking.php') && $booking_type === 'product' && $product_type === 'jet_booking') {
												$this->booking_term_fields_import($existing_term, $post_values, $booking_attr_index++);
											}
								
											// Polylang integration.
											if (!empty($poly_values)) {
												$lang = $poly_values['language_code'];
												pll_set_term_language($term_id, $lang);
											}
										} else {
											$error_message = $inserted_term->get_error_message();
										}
									} else {
										// Handle existing term.
										$term_ids[] = $existing_term->term_id;
								
										// Jet Booking plugin integration.
										if (is_plugin_active('jet-booking/jet-booking.php') && $booking_type === 'product' && $product_type === 'jet_booking') {
											$this->booking_term_fields_import($existing_term, $post_values, $booking_attr_index++);
										}
									}
								}

								$attribute->set_options($term_ids);
								break;
							case "product_attribute_visible$i":
								if($attr_val == '1'){
									$attribute->set_visible( true );
									$attribute->set_variation( true );
								}
								else{
									$attribute->set_visible( false );
									$attribute->set_variation( false );
								}								
								break;

						}
					}
					// $is_visible = isset($attribute_visible[$index]) ? (bool) $attribute_visible[$index] : false;
					// $is_variation = isset($attribute_variation[$index]) ? (bool) $attribute_variation[$index] : false;
					// $position = isset($post_values[$index]) ? intval($attribute_position[$index]) : 0;
					$attribute->set_id($attribute_id);
    				$attribute->set_position( 0 );
					//$attribute->set_variation( true );
					
					$attributes[] = $attribute;
					
				}
$valid_attributes = array();

if ( ! empty( $attributes ) ) {
    foreach ( $attributes as $attr_obj ) {
        if ( $attr_obj instanceof \WC_Product_Attribute ) {
            $data = $attr_obj->get_data();
            if ( taxonomy_exists( $data['name'] ) && ! empty( $data['options'] ) ) {
                $valid_attributes[] = $attr_obj;
            } 
        }
    }
}

if ( ! empty( $valid_attributes ) ) {
    $product->set_attributes( $valid_attributes );
}
$product_id = $product->save();

			}
		}
	}
}catch (\Exception $e) {
	$core_instance->detailed_log[$line_number]['Message'] = $e->getMessage();
	$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
	$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
	return array('MODE' => $mode,'ID' => '');
}
		$returnArr['ID'] = $product_id;
		$returnArr['MODE'] = $mode_of_affect;
		$returnArr['post_type'] = $post_type;
		if (!empty($data_array['post_author'])) {
			$returnArr['AUTHOR'] = isset($assigned_author) ? $assigned_author : '';
		}
		return $returnArr;
	}

	public function booking_term_fields_import($terms,$post_values,$index){
		global $wpdb;
		$jet_abaf_service_cost = !empty($post_values['jet_abaf_service_cost']) ? explode(',', str_replace('|', ',', $post_values['jet_abaf_service_cost'])) : '';
		$jet_abaf_service_cost_format = !empty($post_values['jet_abaf_service_cost_format']) ?  explode(',', str_replace('|', ',', $post_values['jet_abaf_service_cost_format'])) : '';
		$jet_abaf_guests_multiplier = !empty($post_values['jet_abaf_guests_multiplier']) ? explode(',', str_replace('|', ',', $post_values['jet_abaf_guests_multiplier'])) : '';
		$jet_abaf_everyday_service = !empty($post_values['jet_abaf_everyday_service']) ? explode(',', str_replace('|', ',', $post_values['jet_abaf_everyday_service'])) : '';
		$term_id = !empty($terms->term_id) ? $terms->term_id : '';
		$attribute_name = !empty($terms->taxonomy) ? $terms->taxonomy : '';

		$attri_name = trim(str_replace('pa_','',$attribute_name));
		$attribute_type = $wpdb->get_var($wpdb->prepare(
			"SELECT attribute_type FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
			$attri_name
		));
		if($attribute_type == 'jet_booking_service'){
			if(!empty($jet_abaf_service_cost[$index]) && isset($jet_abaf_service_cost[$index])){
				update_term_meta($term_id, 'jet_abaf_service_cost', $jet_abaf_service_cost[$index]);
			}
			if(!empty($jet_abaf_service_cost_format[$index]) && isset($jet_abaf_service_cost_format[$index])){
				update_term_meta($term_id, 'jet_abaf_service_cost_format', $jet_abaf_service_cost_format[$index]);
			}
			if(!empty($jet_abaf_guests_multiplier[$index]) && isset($jet_abaf_guests_multiplier[$index])){
				update_term_meta($term_id, 'jet_abaf_guests_multiplier', true);
			}
			if(!empty($jet_abaf_everyday_service[$index]) && isset($jet_abaf_everyday_service[$index])){
				update_term_meta($term_id, 'jet_abaf_everyday_service', true);
			}
		}

	}
	public function import_swatch_and_booking_types($attribute_data,$produc_type = null)
	{
		global $wpdb;
		$attr_names = [];
		$errors = [];
		foreach ($attribute_data as $attr_name => $type) {
			$attribute_name = sanitize_title($attr_name);
			$taxonomy = 'pa_' . sanitize_title($attr_name);
			// Check if the attribute exists
			$exists = $wpdb->get_var($wpdb->prepare(
				"SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
				$attribute_name
			));

			// Ensure the taxonomy exists
			if (!taxonomy_exists($taxonomy)) {
				// Create the taxonomy if it does not exist
				register_taxonomy($taxonomy, 'product', [
					'label' => ucfirst($attr_name),
					'public' => true,
					'hierarchical' => false,
					'show_ui' => true,
					'show_admin_column' => true,
					'query_var' => true,
					'rewrite' => ['slug' => sanitize_title($attr_name)],
				]);
			}

			if ($exists) {
				// Update the attribute type if it exists
				$updated = $wpdb->update(
					$wpdb->prefix . 'woocommerce_attribute_taxonomies',
					array('attribute_type' => sanitize_text_field($type)),
					array('attribute_name' => $attribute_name),
					array('%s'),
					array('%s')
				);
			} else {
				// Insert the attribute if it does not exist
				$inserted = $wpdb->insert(
					$wpdb->prefix . 'woocommerce_attribute_taxonomies',
					array(
						'attribute_label'   => sanitize_text_field($attr_name),
						'attribute_name'    => $attribute_name,
						'attribute_type'    => sanitize_text_field($type),
						'attribute_orderby' => 'menu_order',
						'attribute_public'  => 0
					),
					array('%s', '%s', '%s', '%s', '%d')
				);
			}

			$attr_names[] = $attribute_name;

			// Flush cache for attributes
			delete_transient('wc_attribute_taxonomies');
		}

		return !empty($attr_names) ? implode('|', $attr_names) : '';
	}
	public function import_swatch_values($attribute_data)
	{
		global $wpdb;
		$media_instance = MediaHandling::getInstance();
		$att_values = [];
		foreach ($attribute_data as $attr_name => $values) {
			$taxonomy = 'pa_' . sanitize_title($attr_name);
			$attr_type = $wpdb->get_var($wpdb->prepare(
				"SELECT attribute_type FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_label = %s",
				$attr_name
			));

			// Ensure the taxonomy exists
			if (!taxonomy_exists($taxonomy)) {
				// Create the taxonomy if it does not exist
				register_taxonomy($taxonomy, 'product', [
					'label' => ucfirst($attr_name),
					'public' => true,
					'hierarchical' => false,
					'show_ui' => true,
					'show_admin_column' => true,
					'query_var' => true,
					'rewrite' => ['slug' => sanitize_title($attr_name)],
				]);
			}

			foreach ($values as $value => $swatch_value) {
				$att_values[] = $value;
				$term = get_term_by('name', $value, $taxonomy);

				if (!$term) {
					// Create term if it does not exist
					$slug = sanitize_title($value);
					$term_id = wp_insert_term($value, $taxonomy, ['slug' => $slug])['term_id'];
				} else {
					$term_id = $term->term_id;
				}
				if (is_numeric($swatch_value) && $attr_type == 'image') {
					$meta_value = $swatch_value;
				} else if ($attr_type == 'image') {
					$meta_value = $media_instance->media_handling($swatch_value, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');
				} else {
					$meta_value = $swatch_value;
				}
				// Update term meta based on attribute type
				$meta_key = $attr_type === 'image' ? 'product_attribute_image' : 'product_attribute_color';
				update_term_meta($term_id, $meta_key, $meta_value);
			}
		}

		return !empty($att_values) ? implode('|', $att_values) : [];
	}
}
