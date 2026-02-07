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

class ProductMetaImport {
    private static $product_meta_instance = null;

    public static function getInstance() {
		
		if (ProductMetaImport::$product_meta_instance == null) {
			ProductMetaImport::$product_meta_instance = new ProductMetaImport;
			return ProductMetaImport::$product_meta_instance;
		}
		return ProductMetaImport::$product_meta_instance;
    }

    function set_product_meta_values($header_array ,$value_array , $map ,$maps, $post_id, $variation_id ,$type , $line_number , $mode , $core_map, $hash_key,$gmode,$templatekey,$poly_array,$selected_type=null){
        global $wpdb;        
        $woocommerce_meta_instance = WooCommerceMetaImport::getInstance();
		$wpecommerce_instance = WPeCommerceImport::getInstance();
		$helpers_instance = ImportHelpers::getInstance();
		$data_array = [];
        $data_array = $helpers_instance->get_header_values($map , $header_array , $value_array,$hash_key);
        $core_array = $helpers_instance->get_header_values($core_map , $header_array , $value_array,$hash_key);
        $image_meta = $helpers_instance->get_meta_values($maps , $header_array , $value_array);
        $poly_values = $helpers_instance->get_header_values($poly_array,$header_array,$value_array,$hash_key);
        if($type == 'WooCommerce Product' || $type == 'WooCommerce Product Variations'){
            $woocommerce_meta_instance->woocommerce_product_meta_import_function($data_array, $image_meta, $post_id, $variation_id ,$type , $line_number , $mode, $header_array, $value_array , $core_array, $hash_key,$gmode,$templatekey,$poly_values);
        }else if($type == 'WooCommerce Attribute'){
            $woocommerce_meta_instance->woocommerce_attribute_meta_import_function($data_array, $image_meta, $post_id, $variation_id ,$type , $line_number , $mode, $header_array, $value_array , $core_array, $hash_key,$gmode,$templatekey,$poly_values,$selected_type);
        }else if($type == 'WooCommerce Coupons'){
            $woocommerce_meta_instance->woocommerce_coupons_meta_import_function($data_array, $image_meta, $post_id, $variation_id ,$type , $line_number , $mode, $header_array, $value_array , $core_array, $hash_key,$gmode,$templatekey,$poly_values);
        }
        else if($type == 'WooCommerce Refunds'){
            $woocommerce_meta_instance->woocommerce_refunds_meta_import_function($data_array, $image_meta, $post_id, $variation_id ,$type , $line_number , $mode, $header_array, $value_array , $core_array, $hash_key,$gmode,$templatekey,$poly_values);
        }
        else if ($type == 'PPOMMETA') {
            $woocommerce_meta_instance->ppom_meta_import_function($data_array, $post_id );
        }
        else if ($type == 'EPOMETA') {
            $woocommerce_meta_instance->epo_meta_import_function($data_array, $post_id);
        }
        else if ($type == 'WCPAMETA') {
            $woocommerce_meta_instance->wcpa_meta_import_function($data_array, $post_id);
        }
        else if ($type == 'FPFMETA') {
            $woocommerce_meta_instance->fpf_meta_import_function($data_array, $post_id);
        }
        if($type == 'WPeCommerce Products'){
            $wpecommerce_instance->wpecommerce_meta_import_function($data_array, $post_id , $line_number,$header_array,$value_array, $hash_key,$gmode,$templatekey);
        }
    }
}