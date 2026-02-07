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

class PolylangImport {
	private static $polylang_instance = null;

	public static function getInstance() {

		if (PolylangImport::$polylang_instance == null) {
			PolylangImport::$polylang_instance = new PolylangImport;
			return PolylangImport::$polylang_instance;
		}
		return PolylangImport::$polylang_instance;
	}

function set_polylang_values($header_array, $value_array, $map, $post_id, $type, $get_mode) {	
    $helpers_instance = ImportHelpers::getInstance();	
    $post_values = $helpers_instance->get_header_values($map, $header_array, $value_array);
    $sku_index = array_search('sku', array_map('strtolower', $header_array)); 
    if ($sku_index !== false && isset($value_array[$sku_index]) && $value_array[$sku_index] !== '') {
        $post_values['sku'] = $value_array[$sku_index];
    } 
    elseif ($type === 'product') {
        $post_values['sku'] = get_post_meta($post_id, '_sku', true);
    } else {
        $post_values['sku'] = '';
    }

    $this->polylang_import_function($post_values, $type, $post_id, $get_mode);	
}


	// function polylang_import_function($data_array, $importas,$pId,$mode) {
	// 	global $wpdb;
	// 	$term_id = $checkid = "";
	// 	$code = trim($data_array['language_code']);				 
	// 	$language = $wpdb->get_results("select term_id, term_taxonomy_id, description from {$wpdb->prefix}term_taxonomy where taxonomy ='language'");				
	// 	$language_id = $wpdb->get_results($wpdb->prepare("select term_taxonomy_id from {$wpdb->prefix}term_relationships WHERE object_id = %d",$pId));
	// 	$listTaxonomy = get_taxonomies();
	// 	$lang_list = pll_languages_list();
	// 	if (in_array($importas, $listTaxonomy)) {
	// 		if(empty($code) || !in_array($code,$lang_list)){
	// 			$code=pll_default_language();
	// 		}
	// 		$lang_code = trim($data_array['language_code']);
	// 		pll_set_term_language($pId, $lang_code);
	// 		// $arr = pll_get_term_translations($pId);
	// 		$translated_titles = explode(',',$data_array['translated_taxonomy_title']);
	// 		foreach($translated_titles as $translated_title){
	// 			$translated_post_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE name ='$translated_title'");
	// 			$get_language = pll_get_term_language($translated_post_id);
	// 			$arr = pll_get_term_translations($translated_post_id);
	// 			$arr[$lang_code] =$pId;
	// 			pll_save_term_translations( $arr );
	// 		}
	// 	}
	// 	else{
	// 		if(empty($code) || !in_array($code,$lang_list)){
	// 			$code=pll_default_language();
	// 		}
	// 		pll_set_post_language($pId, $code);
	// 		$arr = pll_get_post_translations($pId);
	// 		if($importas == 'WooCommerce Product Variations'){
	// 			$translated_titles = explode('|',$data_array['translated_post_title']);
	// 		}
	// 		else{
	// 			$translated_titles = explode(',',$data_array['translated_post_title']);
	// 		}
			
	// 		foreach($translated_titles as $translated_title){
	// 			$translated_post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$translated_title' and post_status='publish'");
	// 			if(!empty($translated_post_id)){
	// 				$get_language = pll_get_post_language($translated_post_id);
	// 				$arr[$get_language] =$translated_post_id;
	// 			pll_save_post_translations( $arr );
	// 			}
	// 		}
	// 	}
	// 	// foreach($language_id as $key => $lang_ids){
	// 	// 	$taxonomy = $wpdb->get_results("select taxonomy from {$wpdb->prefix}term_taxonomy where term_taxonomy_id ='$lang_ids->term_taxonomy_id'");
	// 	// 	if(!empty($taxonomy)) {
	// 	// 		$language_name=$taxonomy[0];
	// 	// 		$lang_name=$language_name->taxonomy;				
	// 	// 	}
	// 	// 	else {
	// 	// 		$lang_name = "";
	// 	// 	}
	// 	// 	if($lang_name == 'language'){					
	// 	// 		$wpdb->get_results("DELETE FROM {$wpdb->prefix}term_relationships WHERE object_id = '$pId' and term_taxonomy_id = '$lang_ids->term_taxonomy_id'");
	// 	// 	}
	// 	// }
	
	// 	// foreach($language as $langkey => $langval){
	// 	// 	$description = unserialize($langval->description);
	// 	// 	$descript = explode('_',$description['locale']);
	// 	// 	$languages = $descript[0];
	// 	// 	if($languages == $code){
	// 	// 		//$term_id = $langval->term_id;
	// 	// 		$term_id = $langval->term_taxonomy_id;
	// 	// 	}
	// 	// }
	// 	// if (!empty($term_id)) {
	// 	// 	$exists = $wpdb->get_var($wpdb->prepare(
	// 	// 		"SELECT COUNT(*) FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = %d AND object_id = %d",
	// 	// 		$term_id, $pId
	// 	// 	));
		
	// 	// 	if (!$exists) {
	// 	// 		$wpdb->insert(
	// 	// 			$wpdb->prefix . 'term_relationships',
	// 	// 			array(
	// 	// 				'term_taxonomy_id' => $term_id,
	// 	// 				'object_id' => $pId
	// 	// 			),
	// 	// 			array(
	// 	// 				'%d',
	// 	// 				'%d'
	// 	// 			)
	// 	// 		);
	// 	// 	}
	// 	// }
				
	// 	// //$get_term = $wpdb->get_results($wpdb->prepare("select term_id from {$wpdb->prefix}terms where slug like %s ",'%-'.$code));			 			 
	// 	// $get_term = $wpdb->get_results($wpdb->prepare("select term_id from {$wpdb->prefix}terms where slug like %s ",$code));			 			 
	
	// 	// foreach($get_term as $keys =>$values){
	// 	// 	//$id = $values->term_id;

	// 	// 	$code_id = $values->term_id;
	// 	// 	$id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->prefix}term_taxonomy WHERE term_id = $code_id");
	// 	// 	if (!empty($id) && !empty($pId)) {
	// 	// 		$table_name = $wpdb->prefix . 'term_relationships';
			
	// 	// 		$exists = $wpdb->get_var(
	// 	// 			$wpdb->prepare(
	// 	// 				"SELECT COUNT(*) FROM $table_name WHERE term_taxonomy_id = %d AND object_id = %d",
	// 	// 				$id, $pId
	// 	// 			)
	// 	// 		);
			
	// 	// 		if (!$exists) {
	// 	// 			$wpdb->insert(
	// 	// 				$table_name,
	// 	// 				array(
	// 	// 					'term_taxonomy_id' => $id,
	// 	// 					'object_id' => $pId
	// 	// 				),
	// 	// 				array(
	// 	// 					'%d',
	// 	// 					'%d'
	// 	// 				)
	// 	// 			);
	// 	// 		}
	// 	// 	}
			
	// 	// }					
		
	// 	// if($data_array['language_code']){			
			
	// 	// 	$translatepost=isset($data_array['translated_post_title']) ? $data_array['translated_post_title'] : "";			
	// 	// 	$child=$wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title ='$translatepost' AND post_status='publish' ORDER BY ID ASC");
	// 	// 	if($mode == 'Insert'){
			
    //     //         $result_of_check = $wpdb->get_results("SELECT description, term_id, term_taxonomy_id FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy='post_translations' ");
	// 	// 		$array = json_decode(json_encode($result_of_check),true);
	// 	// 		$trans_post_id = !empty($child) ? $child[0]->ID : "";
				
    //     //         $languageid = $wpdb->get_results("SELECT term_id FROM {$wpdb->prefix}terms WHERE slug= '$code' ");
	// 	// 		$lang_id =!empty($languageid) ? $languageid[0]->term_id : "";
			
	// 	// 		if(!empty($lang_id))	{
	// 	// 			$langcount = $wpdb->get_results("SELECT count FROM {$wpdb->prefix}term_taxonomy WHERE term_id='$lang_id'");
	// 	// 			$termcount=$langcount[0]->count;
	// 	// 			$termcount = $termcount+1;
	// 	// 			$wpdb->update( $wpdb->term_taxonomy , array( 'count' => $termcount  ) , array( 'term_id' => $lang_id ) );
	// 	// 		}
			
	// 	// 		foreach($array as $res_key => $res_val){
	// 	// 			$get_term_id = $array[$res_key]['term_id'];
	// 	// 			$get_termtaxo_id = $array[$res_key]['term_taxonomy_id'];
	// 	// 			$description = unserialize($array[$res_key]['description']);
	// 	// 			$values = is_array($description)? array_values($description): array(); 
	// 	// 			if(is_array($values) && !empty($values)){  
	// 	// 				if (!empty($trans_post_id) && in_array($trans_post_id,$values)) {
	// 	// 					//$checkid = $get_term_id;
	// 	// 					$checkid = $get_termtaxo_id;
	// 	// 					$check_term_id = $get_term_id;
	// 	// 				}
	// 	// 			}
	// 	// 		}   
			
	// 	// 		if($checkid){
	// 	// 			$language = $wpdb->get_results("SELECT term_id,description FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy = 'language'");
	// 	// 			if (!empty($checkid) && !empty($pId)) {
	// 	// 				$table_name = $wpdb->prefix . 'term_relationships';
					
	// 	// 				$exists = $wpdb->get_var(
	// 	// 					$wpdb->prepare(
	// 	// 						"SELECT COUNT(*) FROM $table_name WHERE term_taxonomy_id = %s AND object_id = %s",
	// 	// 						$checkid, $pId
	// 	// 					)
	// 	// 				);
					
	// 	// 				if (!$exists) {
	// 	// 					$wpdb->insert(
	// 	// 						$table_name,
	// 	// 						array(
	// 	// 							'term_taxonomy_id' => $checkid,
	// 	// 							'object_id' => $pId
	// 	// 						),
	// 	// 						array(
	// 	// 							'%s',
	// 	// 							'%s'
	// 	// 						)
	// 	// 					);
	// 	// 				}
	// 	// 			}
					
					 													
	// 	// 			// $result = $wpdb->get_results("select description from {$wpdb->prefix}term_taxonomy where term_id = '$checkid' ");
	// 	// 			$result = $wpdb->get_results("select description from {$wpdb->prefix}term_taxonomy where term_id = '$check_term_id' ");			
	// 	// 			$description = unserialize($result[0]->description);					
				
	// 	// 			foreach($description as $desckey =>$descval){  

	// 	// 				//insert with update 
	// 	// 				$array2= array($code => $pId);
	// 	// 				$descript=array_merge($description,$array2);
	// 	// 				$count = count($descript);
	// 	// 				$description_data = serialize($descript);
	// 	// 				// $wpdb->update( $wpdb->term_taxonomy , array( 'description' => $description_data  ) , array( 'term_id' => $checkid ) );
	// 	// 				// $wpdb->update( $wpdb->term_taxonomy , array( 'count' => $count  ) , array( 'term_id' => $checkid ) );

	// 	// 				$wpdb->update( $wpdb->term_taxonomy , array( 'description' => $description_data  ) , array( 'term_id' => $check_term_id ) );
	// 	// 				$wpdb->update( $wpdb->term_taxonomy , array( 'count' => $count  ) , array( 'term_id' => $check_term_id ) );
	// 	// 		    }
	// 	// 		}
	// 	// 		else{
	// 	// 			if(!empty($translatepost)){	
	// 	// 				$term_name = uniqid('pll_');
	// 	// 				$terms = wp_insert_term($term_name,'post_translations');
	// 	// 				$term_id = $terms['term_id'];
	// 	// 				$term_tax_id = $terms['term_taxonomy_id'];
	// 	// 				$wpdb->insert(
	// 	// 					$table_name,
	// 	// 					array(
	// 	// 						'term_taxonomy_id' => $term_tax_id,
	// 	// 						'object_id' => $trans_post_id
	// 	// 					),
	// 	// 					array(
	// 	// 						'%s',
	// 	// 						'%s'
	// 	// 					)
	// 	// 				);
	// 	// 				$language = $wpdb->get_results("SELECT term_id,description FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy ='language'");
					
	// 	// 				if (!empty($term_tax_id) && !empty($pId)) {
	// 	// 					$table_name = $wpdb->prefix . 'term_relationships';
						
	// 	// 					$exists = $wpdb->get_var(
	// 	// 						$wpdb->prepare(
	// 	// 							"SELECT COUNT(*) FROM $table_name WHERE term_taxonomy_id = %s AND object_id = %s",
	// 	// 							$term_tax_id, $pId
	// 	// 						)
	// 	// 					);
						
	// 	// 					if (!$exists) {
	// 	// 						$wpdb->insert(
	// 	// 							$table_name,
	// 	// 							array(
	// 	// 								'term_taxonomy_id' => $term_tax_id,
	// 	// 								'object_id' => $pId
	// 	// 							),
	// 	// 							array(
	// 	// 								'%s',
	// 	// 								'%s'
	// 	// 							)
	// 	// 						);
	// 	// 					}
	// 	// 				}
						
											
	// 	// 				$taxonomyid = $wpdb->get_results($wpdb->prepare("select term_taxonomy_id from {$wpdb->prefix}term_relationships where object_id = %d",$trans_post_id));
	// 	// 				foreach($taxonomyid as $key => $taxo_id){
	// 	// 					$tid = $taxo_id->term_taxonomy_id;						
	// 	// 					$get_details = $wpdb->get_results($wpdb->prepare("select description,taxonomy from {$wpdb->prefix}term_taxonomy where term_taxonomy_id = %d",$tid));
							
	// 	// 					if(isset($get_details[0]->taxonomy) && $get_details[0]->taxonomy == 'language'){														
	// 	// 						$description=unserialize($get_details[0]->description);
	// 	// 						$descript=explode('_',$description['locale']);
	// 	// 						$language = array_key_exists(0,$descript) ? $descript[0] : "";
	// 	// 						if(!empty($language)) {
	// 	// 						$array = array($language => $trans_post_id);
	// 	// 						$post_description=array_merge($array,array($code => $pId));
	// 	// 						$count=count($post_description);
	// 	// 						$description_data=serialize($post_description);
	// 	// 						$wpdb->update( $wpdb->term_taxonomy , array( 'description' => $description_data  ) , array( 'term_id' => $term_id ) );
	// 	// 						$wpdb->update( $wpdb->term_taxonomy , array( 'count' => $count  ) , array( 'term_id' => $term_id ) );
	// 	// 						}
	// 	// 					}
	// 	// 				}
	// 	// 			}
	// 	// 		}				
	// 	// 	}
	// 	// 	else{

    //     //         $result_of_check = $wpdb->get_results("SELECT description,term_id, term_taxonomy_id FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy='post_translations' ");
			
	// 	// 		$array=json_decode(json_encode($result_of_check),true);
	// 	// 		$trans_post_id = !empty($child) ? $child[0]->ID : "";

	// 	// 		foreach($array as $res_key => $res_val){
	// 	// 			$get_term_id = $array[$res_key]['term_id'];
	// 	// 			$get_taxo_id = $array[$res_key]['term_taxonomy_id'];
	// 	// 			$description = unserialize($array[$res_key]['description']);
	// 	// 			$values = is_array($description)? array_values($description): array(); 
	// 	// 			if(is_array($values) && !empty($values)){  
	// 	// 				if (!empty($trans_post_id) && in_array($trans_post_id,$values)) {
	// 	// 					$check_termid = $get_term_id;
	// 	// 					$checkid = $get_taxo_id;
	// 	// 				}
	// 	// 			}
	// 	// 		}   
	// 	// 		if($checkid){
				
	// 	// 			$language=$wpdb->get_results("SELECT term_id,description FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy ='language'");
		
	// 	// 			if (!empty($checkid) && !empty($pId)) {
	// 	// 				$table_name = $wpdb->prefix . 'term_relationships';
					
	// 	// 				$exists = $wpdb->get_var(
	// 	// 					$wpdb->prepare(
	// 	// 						"SELECT COUNT(*) FROM $table_name WHERE term_taxonomy_id = %s AND object_id = %s",
	// 	// 						$checkid, $pId
	// 	// 					)
	// 	// 				);
					
	// 	// 				if (!$exists) {
	// 	// 					$wpdb->insert(
	// 	// 						$table_name,
	// 	// 						array(
	// 	// 							'term_taxonomy_id' => $checkid,
	// 	// 							'object_id' => $pId
	// 	// 						),
	// 	// 						array(
	// 	// 							'%s',
	// 	// 							'%s'
	// 	// 						)
	// 	// 					);
	// 	// 				}
	// 	// 			}
						
					
	// 	// 			// $result=$wpdb->get_results("select description from {$wpdb->prefix}term_taxonomy where term_id ='$checkid'");
	// 	// 			$result=$wpdb->get_results("select description from {$wpdb->prefix}term_taxonomy where term_id ='$check_termid'");					
	// 	// 			$description=unserialize($result[0]->description);					
	// 	// 			foreach($description as $desckey =>$descval){  

	// 	// 				//insert with update 
	// 	// 				$array2= array($code => $pId);
	// 	// 				$descript=array_merge($description,$array2);
	// 	// 				$count = count($descript);
	// 	// 				$description_data = serialize($descript);
	// 	// 				// $wpdb->update( $wpdb->term_taxonomy , array( 'description' => $description_data  ) , array( 'term_id' => $checkid ) );
	// 	// 				// $wpdb->update( $wpdb->term_taxonomy , array( 'count' => $count  ) , array( 'term_id' => $checkid ) );

	// 	// 				$wpdb->update( $wpdb->term_taxonomy , array( 'description' => $description_data  ) , array( 'term_id' => $check_termid ) );
	// 	// 				$wpdb->update( $wpdb->term_taxonomy , array( 'count' => $count  ) , array( 'term_id' => $check_termid ) );
	// 	// 		    }
	// 	// 		}
	// 	// 		else{
	// 	// 			if(!empty($translatepost)){	
	// 	// 				$term_name = uniqid('pll_');
	// 	// 				$terms = wp_insert_term($term_name,'post_translations');
	// 	// 				$term_id = $terms['term_id'];
	// 	// 				$term_tax_id = $terms['term_taxonomy_id'];
	// 	// 				$language = $wpdb->get_results("SELECT term_id,description FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy ='language'");
	// 	// 				$table_name = $wpdb->prefix . 'term_relationships';
	// 	// 				$wpdb->insert(
	// 	// 					$table_name,
	// 	// 					array(
	// 	// 						'term_taxonomy_id' => $term_tax_id,
	// 	// 						'object_id' => $trans_post_id
	// 	// 					),
	// 	// 					array(
	// 	// 						'%s',
	// 	// 						'%s'
	// 	// 					)
	// 	// 				);
	// 	// 				if (!empty($term_tax_id) && !empty($pId)) {
	// 	// 					$table_name = $wpdb->prefix . 'term_relationships';
						
	// 	// 					$exists = $wpdb->get_var(
	// 	// 						$wpdb->prepare(
	// 	// 							"SELECT COUNT(*) FROM $table_name WHERE term_taxonomy_id = %s AND object_id = %s",
	// 	// 							$term_tax_id, $pId
	// 	// 						)
	// 	// 					);
						
	// 	// 					if (!$exists) {
	// 	// 						$wpdb->insert(
	// 	// 							$table_name,
	// 	// 							array(
	// 	// 								'term_taxonomy_id' => $term_tax_id,
	// 	// 								'object_id' => $pId
	// 	// 							),
	// 	// 							array(
	// 	// 								'%s',
	// 	// 								'%s'
	// 	// 							)
	// 	// 						);
	// 	// 					}
	// 	// 				}
						
						
	// 	// 				$taxonomyid=$wpdb->get_results("SELECT term_taxonomy_id FROM {$wpdb->prefix}term_relationships WHERE object_id ='$trans_post_id'");
	// 	// 				foreach($taxonomyid as $res1key => $resval){
	// 	// 					$resval1=$resval->term_taxonomy_id;
	// 	// 					$taxonomy=$wpdb->get_results("SELECT taxonomy FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id ='$resval1'");
	// 	// 					if($taxonomy[0]->taxonomy == 'language'){
	// 	// 						$taxid =$resval1;
							
	// 	// 						$desc=$wpdb->get_results("SELECT description FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id ='$taxid'");
	// 	// 						$description=unserialize($desc[0]->description);
	// 	// 						$descript=explode('_',$description['locale']);
	// 	// 						$language=$descript[0];
	// 	// 						$array=array($language => $trans_post_id);
	// 	// 						$post_trans=array_merge($array,array($code => $pId));
	// 	// 						$count =count($post_trans);
	// 	// 						$ser=serialize($post_trans);
	// 	// 						$wpdb->update( $wpdb->term_taxonomy , array( 'description' => $ser  ) , array( 'term_id' => $term_id ) );
	// 	// 						$wpdb->update( $wpdb->term_taxonomy , array( 'count' => $count  ) , array( 'term_id' => $term_id ) );
	// 	// 					}
	// 	// 				}
	// 	// 			}
	// 	// 		}
	// 	// 	}
	// 	// }
	// }



function polylang_import_function($data_array, $importas, $pId, $mode) {
    global $wpdb;

    if (!function_exists('pll_get_post_language') || !function_exists('pll_save_post_translations')) {
        return;
    }

    $code = isset($data_array['language_code']) ? trim($data_array['language_code']) : '';
    $lang_list = function_exists('pll_languages_list') ? pll_languages_list() : array();
    if (empty($code) || !in_array($code, $lang_list)) {
        $code = function_exists('pll_default_language') ? pll_default_language() : $code;
    }

    $listTaxonomy = get_taxonomies();

    if (in_array($importas, $listTaxonomy)) {
        pll_set_term_language($pId, $code);

        $translated_field = isset($data_array['translated_taxonomy_title']) ? $data_array['translated_taxonomy_title'] : '';
        $translated_titles = array_filter(array_map('trim', explode(',', $translated_field)));


        foreach ($translated_titles as $translated_title) {
            if ($translated_title === '') continue;

            $translated_term_id = $wpdb->get_var(
                $wpdb->prepare("SELECT term_id FROM {$wpdb->terms} WHERE name = %s LIMIT 1", $translated_title)
            );

            if (!empty($translated_term_id)) {
                $arr = pll_get_term_translations($translated_term_id);
                $arr[$code] = $pId;
                pll_save_term_translations($arr);
            } else {
            }
        }

        return;
    }

    pll_set_post_language($pId, $code);
    $arr = pll_get_post_translations($pId);

    $sku_csv = null;
    if (!empty($data_array['sku'])) {
        $sku_csv = trim($data_array['sku']);
    } elseif (!empty($data_array['_sku'])) {
        $sku_csv = trim($data_array['_sku']);
    }


    $find_posts_by_sku = function($sku, $exclude_post_id = 0) use ($wpdb) {
        if (empty($sku)) return array();

        $sql = "
            SELECT pm.post_id
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_sku'
              AND pm.meta_value = %s
              AND pm.post_id != %d
            AND p.post_status IN ('publish','draft','pending')
        ";
        $results = $wpdb->get_col( $wpdb->prepare( $sql, $sku, $exclude_post_id ) );
        return is_array($results) ? array_map('intval', $results) : array();
    };

    $find_post_by_title = function($title, $exclude_post_id = 0) use ($wpdb) {
        if (empty($title)) return 0;
        $sql = "
            SELECT ID FROM {$wpdb->posts}
            WHERE post_title = %s
              AND ID != %d
              AND post_status IN ('publish','draft','pending')
            LIMIT 1
        ";
        return (int) $wpdb->get_var( $wpdb->prepare( $sql, $title, $exclude_post_id ) );
    };

    if (!empty($sku_csv)) {
        $matched_posts = $find_posts_by_sku($sku_csv, $pId);
        if (!empty($matched_posts)) {
            foreach ($matched_posts as $matched_id) {
                $matched_lang = pll_get_post_language($matched_id);
                if (!empty($matched_lang)) {
                    $arr[$matched_lang] = $matched_id;
                } else {
                }
            }
            pll_save_post_translations($arr);
            return;
        } else {
        }
    }

    $translated_field = isset($data_array['translated_post_title']) ? $data_array['translated_post_title'] : '';
    if ($importas === 'WooCommerce Product Variations') {
        $translated_titles = array_filter(array_map('trim', explode('|', $translated_field)));
    } else {
        $translated_titles = array_filter(array_map('trim', explode(',', $translated_field)));
    }

    foreach ($translated_titles as $translated_title) {
        if ($translated_title === '') continue;

        $translated_post_id = $find_post_by_title($translated_title, $pId);

        if (!empty($translated_post_id)) {
            $get_language = pll_get_post_language($translated_post_id);
            if (!empty($get_language)) {
                $arr[$get_language] = $translated_post_id;
                pll_save_post_translations($arr);
            } else {
            }
        }
    }
}


}