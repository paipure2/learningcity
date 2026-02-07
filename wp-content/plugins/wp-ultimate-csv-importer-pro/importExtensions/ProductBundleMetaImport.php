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

class ProductBundleMetaImport {
    private static $product_bundle_meta_instance = null;

    public static function getInstance() {
		
		if (ProductBundleMetaImport::$product_bundle_meta_instance == null) {
			ProductBundleMetaImport::$product_bundle_meta_instance = new ProductBundleMetaImport;
			return ProductBundleMetaImport::$product_bundle_meta_instance;
		}
		return ProductBundleMetaImport::$product_bundle_meta_instance;
    }

    function set_product_bundle_meta_values($header_array ,$value_array , $map ,$maps, $post_id, $variation_id, $type, $line_number, $mode, $core_map, $hash_key,$gmode,$templatekey,$poly_array){
        global $wpdb;
       
        $woocommerce_meta_instance = WooCommerceMetaImport::getInstance();
		$wpecommerce_instance = WPeCommerceImport::getInstance();
		$helpers_instance = ImportHelpers::getInstance();
		$data_array = [];
        $data_array = $helpers_instance->get_header_values($map , $header_array , $value_array);
        $core_array = $helpers_instance->get_header_values($core_map , $header_array , $value_array);
        $image_meta = $helpers_instance->get_meta_values($maps , $header_array , $value_array);
        $poly_values = $helpers_instance->get_header_values($poly_array,$header_array,$value_array);
        if($type == 'WooCommerce Product'){
            $woocommerce_meta_instance->woocommerce_product_bundle_import_function($data_array, $image_meta, $post_id, $variation_id ,$type , $line_number , $mode, $header_array, $value_array , $core_array, $hash_key,$gmode,$templatekey,$poly_values);
        }
        if($type == 'WPeCommerce Products'){
            $wpecommerce_instance->wpecommerce_meta_import_function($data_array, $post_id , $line_number,$header_array,$value_array,$hash_key,$gmode,$templatekey);
        }
    }
}