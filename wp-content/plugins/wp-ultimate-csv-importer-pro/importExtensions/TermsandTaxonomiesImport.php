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
class TermsandTaxonomiesImport {
	private static $terms_taxo_instance = null;

	public static function getInstance() {

		if (TermsandTaxonomiesImport::$terms_taxo_instance == null) {
			TermsandTaxonomiesImport::$terms_taxo_instance = new TermsandTaxonomiesImport;
			return TermsandTaxonomiesImport::$terms_taxo_instance;
		}
		return TermsandTaxonomiesImport::$terms_taxo_instance;
	}
	function set_terms_taxo_values($header_array ,$value_array , $map , $post_id , $type, $mode, $gmode , $line_number = null, $lang_map = null,$poly_array = null){
		
		$post_values = [];
		$helpers_instance = ImportHelpers::getInstance();
		$post_values = $helpers_instance->get_header_values($map , $header_array , $value_array);
		$lang_values = $helpers_instance->get_header_values($lang_map , $header_array , $value_array);
		$poly_values = $helpers_instance->get_header_values($poly_array , $header_array , $value_array);

		$this->terms_taxo_import_function($post_values,$type, $post_id , $mode , $line_number,$lang_values, $gmode,$poly_values);


	}

	public function terms_taxo_import_function ($data_array, $type ,$pID , $mode , $line_number,$lang_values, $gmode,$poly_values = null) {
		$core_instance = CoreFieldsImport::getInstance();
		$helpers_instance = ImportHelpers::getInstance();
		global $core_instance;
		if ($type == 'WooCommerce Product') {
			$woocommerce_default_category = (int) get_option( 'default_product_cat', 0 );
			if ($mode == 'Update') {
				if (empty($data_array['product_category'])) {
					$categories = wp_get_post_terms($pID, 'product_cat', array('fields' => 'names'));
					$data_array['product_category'] = !empty($categories) ? implode(', ', $categories) : 'Uncategorized';
				}
			} else {
				$data_array['product_category'] = empty($data_array['product_category']) ? 'Uncategorized' : $data_array['product_category'];
			}
				if ( $data_array['product_category'] !== 'Uncategorized' ) {
					$this->remove_uncategorized_category($pID);
				}
		}
		unset($data_array['post_format']);
		unset($data_array['product_type']);
		$categories = $tags = array();
		foreach ($data_array as $termKey => $termVal) {

			if (strpos($termKey, 'pa_') !== false) {
				$term_keys = explode(',' , $termVal);
				foreach($term_keys as $term_values){
					// $check_term = term_exists($term_keys);
					$check_term = term_exists($term_values);
					if(isset($check_term)){
#TODO
					}else{
						wp_insert_term($term_values , $termKey);
					}	
				}	
			}

			$smack_taxonomy = array();
			
			switch ($termKey) {
				case 'post_category' :
					$categories [$termKey] = $data_array [$termKey];

					if(preg_match("(Can't|Skipped|Duplicate)", $core_instance->detailed_log[$line_number]['Message']) === 0) {  
						$core_instance->detailed_log[$line_number]['Categories'] = $data_array[$termKey];
					}

					$category_name = 'category';
					if($mode == 'Update'){
						$categories_before = wp_get_object_terms($pID, 'category');
						foreach($categories_before as $category_before){
							wp_remove_object_terms($pID, $category_before->name , 'category');    
						}
					}

					// Create / Assign categories to the post types
					if(isset($categories[$termKey]) && $categories[$termKey] != '')
						$this->assignTermsAndTaxonomies($categories, $category_name, $pID,$lang_values, $gmode,$poly_values);
					//Get Default Category id
					$default_category_id = get_option('default_category');
					//Get Default Category Name
					$default_category_details = get_term_by('id', $default_category_id , 'category');

					//Remove Default Category
					$categories = wp_get_object_terms($pID, 'category');
					if (count($categories) > 1) {
						foreach ($categories as $key => $category) {
							if (isset($default_category_details->name) && $category->name == $default_category_details->name ) {
								wp_remove_object_terms($pID, $default_category_details->name , 'category');
							}
						}
					}
					break;
				case 'post_tag' :
		
					$tags [$termKey] = $data_array [$termKey];
					$tag_name = 'post_tag';
					if($mode == 'Update'){
						$categories_before = wp_get_object_terms($pID, 'post_tag');

						foreach($categories_before as $category_before){
							wp_remove_object_terms($pID, $category_before->name , 'post_tag');    
						}
						if(isset($tags [$termKey]))
						$this->assignTermsAndTaxonomies($tags, $tag_name, $pID,$lang_values, $gmode,$poly_values);
					    if(preg_match("(Can't|Skipped|Duplicate)", $core_instance->detailed_log[$line_number]['Message']) === 0){
						$core_instance->detailed_log[$line_number]['Tags'] = $data_array[$termKey];
					    }	
					}
					else{
						if(isset($tags [$termKey]) && $tags [$termKey] != '')
						$this->assignTermsAndTaxonomies($tags, $tag_name, $pID,$lang_values, $gmode,$poly_values);
					    if(preg_match("(Can't|Skipped|Duplicate)", $core_instance->detailed_log[$line_number]['Message']) === 0){
						$core_instance->detailed_log[$line_number]['Tags'] = $data_array[$termKey];
				        }
					}
					
					break;
				case 'product_tag':
					$tags [$termKey] = $data_array [$termKey];
					$tag_name = 'product_tag';
					if ($mode == 'Update'){
						$categories_before = wp_get_object_terms($pID, 'product_tag');
						foreach($categories_before as $category_before){
							wp_remove_object_terms($pID, $category_before->name , 'product_tag');    
						}
						$this->assignTermsAndTaxonomies($tags, $tag_name, $pID,$lang_values, $gmode,$poly_values);
					    if(preg_match("(Can't|Skipped|Duplicate)", $core_instance->detailed_log[$line_number]['Message']) === 0){
						$core_instance->detailed_log[$line_number]['Tags'] = $data_array[$termKey];
					    }		
					}
					else{
						if(isset($tags [$termKey]) && $tags [$termKey] != '')
						$this->assignTermsAndTaxonomies($tags, $tag_name, $pID,$lang_values, $gmode,$poly_values);
					    if(preg_match("(Can't|Skipped|Duplicate)", $core_instance->detailed_log[$line_number]['Message']) === 0){
						$core_instance->detailed_log[$line_number]['Tags'] = $data_array[$termKey];
					    }	
					}
					$tag_name = 'product_tag';
					break;
				case 'product_category':
					if($type == 'WooCommerce Product')
						$category_name = 'product_cat';
					if($type == 'WPeCommerce Products')
						$category_name = 'wpsc_product_category';
					else
					$category_name = 'product_cat';
					$categories [$termKey] = $data_array [$termKey];
					if(preg_match("(Can't|Skipped|Duplicate)", $core_instance->detailed_log[$line_number]['Message']) === 0){
						$core_instance->detailed_log[$line_number]['Categories'] = $data_array[$termKey];
					}
					// Create / Assign categories to the post types
					if ($mode == 'Update'){
						$categories_before = wp_get_object_terms($pID, 'product_cat');
						foreach($categories_before as $category_before){
							wp_remove_object_terms($pID, $category_before->name , 'product_cat');    
						}
						$this->assignTermsAndTaxonomies($categories, $category_name, $pID,$lang_values, $gmode,$poly_values);
					}
					else{
                        if(isset($categories[$termKey]) && $categories[$termKey] != '')
						$this->assignTermsAndTaxonomies($categories, $category_name, $pID,$lang_values, $gmode,$poly_values);
					}
					break;
				case 'event_tags':
					$eventtags [$termKey] = $data_array [$termKey];
					if(!empty($eventtags)){

						if(preg_match("(Can't|Skipped|Duplicate)", $core_instance->detailed_log[$line_number]['Message']) === 0){
							$core_instance->detailed_log[$line_number]['Tags'] = $data_array[$termKey];
						}
						
						foreach($eventtags as $e_key => $e_value){
							if(!empty($e_value)){
								if (strpos($e_value, ',') !== false) {
									$split_etag = explode(',', $e_value);
								} else {
									$split_etag = $e_value;
								}
								if(is_array($split_etag)) {
									foreach($split_etag as $item) {
										$etagData[] = (string)$item;
									}
								} else {
									$etagData = (string)$split_etag;
								}
								wp_set_object_terms($pID, $etagData,'event-tags');
								
							}
						}
					}
					break;
				case 'event_categories':
					$event_categories [$termKey] = $data_array [$termKey];
					if(!empty($event_categories)) {

						if(preg_match("(Can't|Skipped|Duplicate)", $core_instance->detailed_log[$line_number]['Message']) === 0){
							$core_instance->detailed_log[$line_number]['Categories'] = $data_array[$termKey];
						}
						
						foreach($event_categories as $ec_key => $ec_value){
							if(!empty($ec_value)) {
								if (strpos($ec_value, ',') !== false) {
									$split_ecat = explode(',', $ec_value);
								} else {
									$split_ecat = $ec_value;
								}
								if(is_array($split_ecat)) {
									foreach($split_ecat as $item) {
										$ecatData[] = (string)$item;
									}
								} else {
									$ecatData = (string)$split_ecat;
								}
								wp_set_object_terms($pID, $ecatData,'event-categories');
							}
						}
					}
					break;
				default :
					$smack_taxonomy[$termKey] = $data_array[$termKey];

					if($termKey != 'post_format')
						$core_instance->detailed_log[$line_number][$termKey] = $data_array[$termKey];

					$taxonomy_name = $termKey;
					
					if($mode == 'Update'){
						$taxonomies_before = wp_get_object_terms($pID, $termKey);
						foreach($taxonomies_before as $taxonomy_before){
						 	wp_remove_object_terms($pID, $taxonomy_before->name , $termKey); 
						}
					}

					// Create / Assign taxonomies to the post types
					if(isset($smack_taxonomy[$termKey]) && $smack_taxonomy[$termKey] != '')
						$this->assignTermsAndTaxonomies($smack_taxonomy, $taxonomy_name, $pID,$lang_values, $gmode,$poly_values);
					break;
			}
		}

		// Create / Assign tags to the post types
		if (!empty ($tags)) {
			foreach ($tags as $tag_key => $tag_value) {
				if (!empty($tag_value)) {
					if (strpos($tag_value, ',') !== false) {
						$split_tag = explode(',', $tag_value);
					} else {
						$split_tag = $tag_value;
					}
					if(is_array($split_tag)) {
						foreach($split_tag as $item) {
							$tag_list[] = $item;
						}
					} else {
						$tag_list = $split_tag;
					}
					wp_set_object_terms($pID, $tag_list, $tag_name);
				}
			}
		}


		
	}

	public function assignTermsAndTaxonomies($categories, $category_name, $pID, $data_array = '', $gmode = '',$poly_values = null ) {
		$get_category_list = $category_list = array();
		// Create / Assign categories to the post types
		if(!empty($poly_values)){
			$lang_list = pll_languages_list();
		}
		if (!empty($categories)) {
			foreach ( $categories as $cat_key => $cat_value ) {
				if (strpos($cat_value, ',') !== false) {
					$get_category_list = explode(',', $cat_value);
				} else {
					$get_category_list[] = $cat_value;
				}
			}

		}

		if(!empty($get_category_list)) {
			$i = 0;
			foreach($get_category_list as $key => $value) {
				if (strpos($value, '>') !== false) {		
					$split_line = explode('>', $value);
					if(is_array($split_line)) {
						foreach($split_line as $category) {
							$category_list[$i][] = $category;
						}
					}
				} else {
					$category_list[$i][] = $value;
				}
				$i++;
			}

		}
		foreach($category_list as $index => $category_set) {
			foreach ( $category_set as $item => $category_value ) {
				$term_children_options= get_option( "$category_name" . "_children" );
				$parentTerm           = $item;
				$termName             = trim( $category_value );
				$_name                = (string) $termName;
				$_slug                = preg_replace( '/\s\s+/', '-', strtolower( $_name ) );
				$is_parent = true;
				$is_sub_parent = true;

				$checkAvailable       = array();
				$checkSuperParent     = $checkParent1 = $checkParent2 = null;
				$super_parent_term_id = $parent_term_id1 = $parent_term_id2 = 0;
		
				if(empty($category_value ) && !empty($item)){
				
					$parentterm1=$category_set[$item-1];
					$checkAvailable = term_exists( "$parentterm1", "$category_name" );
				
					$partermid = $checkAvailable['term_id'];
						
				 	$checkchild1 = get_term_children($partermid,$category_name);
				
					if($checkchild1){
						asort($checkchild1);
						$keys = array_keys($checkchild1);
						
						$itemkeys = $keys[$item-1];
						$childterm =get_term($checkchild1[$itemkeys]);
						// foreach($checkchild1 as $child){

						// }
					}
					$category_values = get_term($checkchild1[$itemkeys]);
					$category_value1 = $category_values->name;
				
				}
				
				if ( $parentTerm != 0 ) {
					if ( isset( $category_set[ $item - 1 ] ) ) {
						$checkParent1 = trim( $category_set[ $item - 1 ] );
						$checkParent1 = (string) $checkParent1;
					
						if(empty($checkParent1)){
							$checkParent1 =$category_value1;
							//$itemkeys = $keys[$item+1];
							
						}
						$parent_new = get_term_by('name', $category_set[$item - 2], $category_name);
						$parents_id = isset($parent_new->term_id) ? $parent_new->term_id : '';
						$parent_term  = term_exists( "$checkParent1", "$category_name", $parents_id);
						if ( isset( $parent_term['term_id'] ) ) {
							$parent_term_id1 = $parent_term['term_id'];
                        }					
					
					}
					if ( isset( $category_set[ $item - 2 ] ) ) {
						
						global $wpdb;
						$par =$wpdb->get_results( "SELECT parent FROM {$wpdb->prefix}term_taxonomy WHERE term_id = '$parent_term_id1' " );
						$checkSuperParent1 = $par[0]->parent;
						
						//$parent_term_id1   = 0;
						$checkSuperParent  = trim( $category_set[ $item - 2 ] );
						$checkSuperParent  = (string) $checkSuperParent;
						
						if(empty($checkSuperParent)){
							$checkSuperParent2 = get_term("$checkSuperParent1","$category_name");
						}

						if(isset($checkSuperParent2->name)){
							$checkSuperParent = $checkSuperParent2->name;
						
							$super_parent_term = term_exists( "$checkSuperParent", "$category_name" );
							if ( isset( $super_parent_term['term_id'] ) ) {
								$super_parent_term_id = $super_parent_term['term_id'];
							}
						}
					
						$checkParent2 = trim( $category_set[ $item - 1 ] );
						$checkParent2 = (string) $checkParent2;
						if(empty($checkParent2)){
							$checkParent2 =$category_value1;
							
						}
					
						$parent_term  = term_exists( "$checkParent2", "$category_name", $super_parent_term_id );
						if ( isset( $parent_term['term_id'] ) ) {
							$parent_term_id2 = $parent_term['term_id'];
						}
					}
				}
				if ( $super_parent_term_id != 0 ) {
					if ( $parent_term_id2 == 0 ) {
						$checkAvailable = term_exists( "$checkParent2", "$category_name" );
						
                        if(!empty($checkParent2)){
							if ( ! is_array( $checkAvailable ) ) {
								$taxonomyID          = wp_insert_term( "$checkParent2", "$category_name", array(
											'description' => '',
											'slug'        => $_slug,
											'parent'      => $super_parent_term_id
											) );
	
								if(!is_wp_error($taxonomyID)){
									$parent_term_id2 = $retID = $taxonomyID['term_id'];
									wp_set_object_terms( $pID, $retID, $category_name, true );
									if(!empty($poly_values)){
										$lang = $poly_values['language_code'];
										if(empty($lang) || !in_array($lang,$lang_list)){
											$lang=pll_default_language();
										}
										$t_id=$taxonomyID['term_id'];
										pll_set_term_language($t_id,$lang);
									}
									else{
										$this->wpml_taxonomy_import($category_name,$retID,$data_array,$checkParent2);
									}
									
								}
	
							} else {
								$exist_term_id = array( $checkAvailable['term_id'] );
								$exist_term_id = array_map( 'intval', $exist_term_id );
								$exist_term_id = array_unique( $exist_term_id );
								$parent_term_id2 = $checkAvailable['term_id'];
								wp_set_object_terms( $pID, $exist_term_id, $category_name, true );
	
							}

						}
						
					}
					unset( $checkAvailable );
					
					$checkAvailable = term_exists( "$_name", "$category_name", $parent_term_id2 );
					
					if(!empty($_name)){
						if ( ! is_array( $checkAvailable ) ) {
							$taxonomyID = wp_insert_term( "$_name", "$category_name", array(
										'description' => '',
										'slug'        => $_slug,
										'parent'      => $parent_term_id2
										) );
	
							if(!is_wp_error($taxonomyID)){
								$retID  = $taxonomyID['term_id'];
								wp_set_object_terms( $pID, $retID, $category_name, true );
								if(!empty($poly_values)){
									$lang = $poly_values['language_code'];
									if(empty($lang) || !in_array($lang,$lang_list)){
										$lang=pll_default_language();
									}
									$t_id=$taxonomyID['term_id'];
									pll_set_term_language($t_id,$lang);
								}
								else{
									$this->wpml_taxonomy_import($category_name,$retID,$data_array,$data_array['translated_post_title']);
								}
	
							}
	
						} else {
							$exist_term_id = array( $checkAvailable['term_id'] );
							$exist_term_id = array_map( 'intval', $exist_term_id );
							$exist_term_id = array_unique( $exist_term_id );
							wp_set_object_terms( $pID, $exist_term_id, $category_name, true );
	
						}
					}
					
					unset( $checkAvailable );
				}
				elseif ( $parent_term_id1 != 0 ) {
					if (!empty($category_set[2]) && isset($category_set[2]) && $item == 1) {
						// The first category in the set is a parent
						$is_sub_parent = false;
					}
					$checkAvailable = term_exists( "$_name", "$category_name", $parent_term_id1 );
					
					if(!empty($_name)){
						if ( ! is_array( $checkAvailable )) {
							$taxonomyID = wp_insert_term( "$_name", "$category_name", array(
										'description' => '',
										'slug'        => $_slug,
										'parent'      => $parent_term_id1
										) );
	
							if(!is_wp_error($taxonomyID) && $is_sub_parent){
								$retID  = $taxonomyID['term_id'];
								wp_set_object_terms( $pID, $retID, $category_name, true );
								if(!empty($poly_values)){
									$lang = $poly_values['language_code'];
									if(empty($lang) || !in_array($lang,$lang_list)){
										$lang=pll_default_language();
									}
									$t_id=$taxonomyID['term_id'];
									pll_set_term_language($t_id,$lang);
								}
								else{
									$this->wpml_taxonomy_import($category_name,$retID,$data_array,$_name);
								}
							}    
	
						} else {
							if($is_sub_parent){
								$exist_term_id = array( $checkAvailable['term_id'] );
								$exist_term_id = array_map( 'intval', $exist_term_id );
								$exist_term_id = array_unique( $exist_term_id );
								wp_set_object_terms( $pID, $exist_term_id, $category_name, true );
							}
	
						}
					}
				
					unset( $checkAvailable );
				}
				elseif ( $super_parent_term_id == 0 && $parent_term_id2 == 0 && $parent_term_id1 == 0 ) {
					if($gmode == 'Normal'){
						if (!empty($category_set[1]) && isset($category_set[1]) && $item == 0) {
							// The first category in the set is a parent
							$is_parent = false;
						}
						$checkAvailable = term_exists( "$_name", "$category_name" );

						// if(is_numeric($_name)){
						// 	if($category_name == 'category'){
						// 		$slug = $_name . '-' . $data_array['language_code'];	
						// 	}
						// 	else{
						// 		$slug = $_name;
						// 	}
						// 	$get_term_id = $wpdb->get_var("SELECT * FROM {$wpdb->prefix}terms WHERE name = '$_name' AND slug = '$slug' ");
						// }
						// else{
						// 	$get_term_id = $wpdb->get_var("SELECT * FROM {$wpdb->prefix}terms WHERE name = '$_name'");
						// }
						if(!empty($_name)){
							
							if ( !is_array( $checkAvailable ) ) {
							//if ( !is_array( $get_term_id ) ) {
								$taxonomyID = wp_insert_term( "$_name", "$category_name", array(
											'description' => '',
											'slug'        => $_slug,
											) );
								if(!is_wp_error($taxonomyID) && $is_parent){
									// $retID  = $taxonomyID['term_id'];
									$retID  = $taxonomyID['term_taxonomy_id'];	
									if (!empty($poly_values)) {
										$lang = $poly_values['language_code'];
										if (empty($lang) || !in_array($lang, $lang_list)) {
											$lang = pll_default_language();
										}
										pll_set_term_language($retID, $lang);
									}			
									$test=wp_set_object_terms( $pID, $retID, $category_name, true );
									global $wpdb;
									if(empty($test)){
										$wpdb->get_results("INSERT into {$wpdb->prefix}term_relationships (`object_id`,`term_taxonomy_id`) VALUES($pID,$retID)");
									}

									if(!empty($poly_values)){
										$lang = $poly_values['language_code'];
										if(empty($lang) || !in_array($lang,$lang_list)){
											$lang=pll_default_language();
										}
										$t_id=$taxonomyID['term_id'];
										pll_set_term_language($t_id,$lang);
									}
									else{
										$this->wpml_taxonomy_import($category_name,$retID,$data_array,$_name);
									}
								}	
		
							} else {
								// $exist_term_id = array( $checkAvailable['term_id'] );
								if($is_parent){
									$exist_term_id = array( $checkAvailable['term_id'] );
									$exist_term_id = array_map( 'intval', $exist_term_id );
									$exist_term_id = array_unique( $exist_term_id );
									$insertid = 0;
									global $wpdb;								
									$obj = wp_set_object_terms( $pID, $exist_term_id, $category_name, true );								
									if(is_array($obj) && empty($obj) && isset($checkAvailable['term_taxonomy_id'])){
										$term_taxo_id = $checkAvailable['term_taxonomy_id'];									
										$insertid = $wpdb->insert( $wpdb->prefix .'term_relationships' , array('object_id' =>$pID, 'term_taxonomy_id' => $term_taxo_id ),array('%d','%d'));									
									}
								}		
							}

						}
				
					}
					else{
						global $wpdb;
						$_name = $wpdb->_real_escape($_name);
						$_slug = $wpdb->_real_escape($_slug);
						$checkAvailable = term_exists( "$_name", "$category_name" );
						if ( !is_array( $checkAvailable ) ) {
							$taxonomyID = 	$wpdb->get_results("INSERT INTO {$wpdb->prefix}terms (`slug` , `name`) VALUES( '{$_slug}', '{$_name}')");
							$term_id = $wpdb->insert_id;
	
							$taxonomyIDs = 	$wpdb->get_results("INSERT INTO {$wpdb->prefix}term_taxonomy (`term_id` , `taxonomy`) VALUES( $term_id, '{$category_name}')");
							$terms_id = $wpdb->insert_id;
	
							if(!is_wp_error($taxonomyIDs)){
	
								$retID  = $terms_id ;
								$get_existing_ids = $wpdb->get_var("SELECT object_id FROM {$wpdb->prefix}term_relationships WHERE object_id = $pID AND term_taxonomy_id = $retID ");
								if(!empty($get_existing_ids)){
									$wpdb->delete( $wpdb->prefix.'term_relationships', array( 'object_id' => $get_existing_ids));
								}

								$wpdb->insert( $wpdb->prefix .'term_relationships' , array('object_id' =>$pID, 'term_taxonomy_id' => $retID ),array('%d','%d'));
								$wpdb->get_results("UPDATE {$wpdb->prefix}term_taxonomy SET count =(SELECT  count(object_id) from {$wpdb->prefix}term_relationships where term_taxonomy_id=$retID) WHERE term_id = $retID");
								
								$this->wpml_taxonomy_import($category_name,$retID,$data_array,$_name);
							}	
	
						} else {
							$exist_term_id =  $checkAvailable['term_id'] ;
							$taxo_id = $wpdb->get_results("SELECT term_taxonomy_id FROM {$wpdb->prefix}term_taxonomy WHERE term_id = $exist_term_id ", ARRAY_A);
						
							foreach($taxo_id as $get_taxo_id){
								$terms_id = $get_taxo_id['term_taxonomy_id'];
						        $get_existing_ids = $wpdb->get_var("SELECT object_id FROM {$wpdb->prefix}term_relationships WHERE object_id = $pID AND term_taxonomy_id = $terms_id ");
							
								if(!empty($get_existing_ids)){
									$wpdb->delete( $wpdb->prefix.'term_relationships', array( 'object_id' => $get_existing_ids));
								}	
							}
					
							foreach($taxo_id as $get_taxo_id){
								$terms_id = $get_taxo_id['term_taxonomy_id'];
								$get_existing_ids = $wpdb->get_var("SELECT object_id FROM {$wpdb->prefix}term_relationships WHERE object_id = $pID AND term_taxonomy_id = $terms_id ");
								
								$wpdb->insert( $wpdb->prefix .'term_relationships' , array('object_id' =>$pID, 'term_taxonomy_id' => $terms_id ),array('%d','%d'));
							}
									
							$wpdb->get_results("UPDATE {$wpdb->prefix}term_taxonomy SET count =(SELECT  count(object_id) from {$wpdb->prefix}term_relationships where term_taxonomy_id=$terms_id) WHERE term_id = $terms_id");	
						}
					}
					unset( $checkAvailable );
				}
				
#if ( ! is_wp_error( $retID ) ) {
	update_option( "$category_name" . "_children", $term_children_options );
	delete_option( $category_name . "_children" );
#}
	$categoryData[] = (string) $category_value;
			}
		}

		return $categoryData;
	}

	public function wpml_taxonomy_import($category_name, $pID,$data_array,$checkterm){
		global $sitepress,$wpdb;
		if(empty($data_array['translated_post_title']) && !empty($data_array['language_code'])) {
			$icl_translations = $wpdb->get_results("UPDATE {$wpdb->prefix}icl_translations SET  language_code = '{$data_array['language_code']}' WHERE  element_id = $pID");
		} elseif(!empty($data_array['language_code']) && !empty($data_array['translated_post_title'])) {
			$termdata = get_term_by('name', $checkterm,$category_name,'ARRAY_A');
			if(is_array($termdata) && !empty($termdata)) {
				$element_id = $termdata['term_id'];
				$taxo_type = $category_name;
			}
			else{
				return false;
			}
			$trid_id = $sitepress->get_element_trid($element_id,'tax_'.$taxo_type);
			$translate_lcode = $sitepress->get_language_for_element($element_id,'tax_'.$taxo_type);
			$icl_translations = $wpdb->get_results("UPDATE {$wpdb->prefix}icl_translations SET trid = $trid_id,  language_code = '{$data_array['language_code']}',source_language_code = '$translate_lcode' WHERE  element_id = $pID");
		}
	}

	public function remove_uncategorized_category($product_id){
		global $wpdb;
        // Get the ID of the "Uncategorized" category directly from the database
        $uncategorized_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT t.term_id 
                 FROM {$wpdb->terms} t
                 INNER JOIN {$wpdb->term_taxonomy} tt 
                 ON t.term_id = tt.term_id 
                 WHERE t.slug = %s 
                 AND tt.taxonomy = %s",
                'uncategorized',
                'product_cat'
            )
        );
        if (!$uncategorized_id) {
            return; // Exit if "Uncategorized" category is not found
        }

        // Check if the relationship exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 
                 FROM {$wpdb->term_relationships} 
                 WHERE object_id = %d 
                 AND term_taxonomy_id = %d",
                $product_id,
                $uncategorized_id
            )
        );
        if ($exists) {
            // Delete the relationship
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->term_relationships} 
                     WHERE object_id = %d 
                     AND term_taxonomy_id = %d",
                    $product_id,
                    $uncategorized_id
                )
            );
        }
		return $product_id;
	}
}
