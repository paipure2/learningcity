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

class ProductAttrImport {
    private static $product_attr_instance = null;

    public static function getInstance() {
		
		if (ProductAttrImport::$product_attr_instance == null) {
			ProductAttrImport::$product_attr_instance = new ProductAttrImport;
			return ProductAttrImport::$product_attr_instance;
		}
		return ProductAttrImport::$product_attr_instance;
    }

    function set_product_attr_values($header_array ,$value_array , $map ,$maps, $post_id, $variation_id,$type , $line_number , $mode , $hash_key, $wpml_map, $gmode,$templatekey,$poly_array){
        global $wpdb;
       
        //$attr_map = [];
       // $attr_tax_map = [];
        // foreach($map as $attr_key => $attr_mapping){
        //     $exp_key = explode('_', $attr_key);
        //     if($exp_key[0] == 'pa'){
        //         $attr_tax_map[$attr_key] = $attr_mapping;
        //     }
        //     else{
        //         $attr_map[$attr_key] = $attr_mapping;
        //     }
        // }
      
        $woocommerce_meta_instance = WooCommerceMetaImport::getInstance();
        //$terms_taxo_instance = TermsandTaxonomiesImport::getInstance();
		$helpers_instance = ImportHelpers::getInstance();
        $data_array = $helpers_instance->get_header_values($map , $header_array , $value_array,$hash_key);
        //$data_array_tax = $helpers_instance->get_header_values($attr_tax_map , $header_array , $value_array);
        $core_array = [];
        $image_meta = [];
        $poly_values = $helpers_instance->get_header_values($poly_array,$header_array,$value_array,$hash_key);
        if(($type == 'WooCommerce Product') || ($type == 'WooCommerce Product Variations')){
            $woocommerce_meta_instance->woocommerce_meta_import_function($data_array,$image_meta, $post_id, $variation_id, $type , $line_number , $mode, $header_array, $value_array , $core_array, $hash_key,$gmode,$templatekey,$poly_values);
			//$terms_taxo_instance->set_terms_taxo_values($header_array, $value_array, $data_array_tax, $post_id, $type, $mode, $gmode, $line_number, $wpml_map);
        }
    }
}