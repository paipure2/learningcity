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

class TaxonomiesImport {
    private static $taxonomies_instance = null,$media_instance;

    public static function getInstance() {
		
		if (TaxonomiesImport::$taxonomies_instance == null) {
			TaxonomiesImport::$taxonomies_instance = new TaxonomiesImport;
			TaxonomiesImport::$media_instance = new MediaHandling;
			return TaxonomiesImport::$taxonomies_instance;
		}
		return TaxonomiesImport::$taxonomies_instance;
    }

    public function taxonomies_import_function ($data_array, $mode, $importType , $unmatched_row, $check , $hash_key , $templatekey, $line_number ,$header_array ,$value_array,$wpml_array,$gmode,$poly_values,$update_based_on) {
		$returnArr = array();
		$mode_of_affect = 'Inserted';
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		global $core_instance;		
		$log_table_name = $wpdb->prefix ."import_detail_log";
		$events_table = $wpdb->prefix."em_meta" ;

		$unikey_name = 'hash_key';
		$unikey_value = $hash_key;

		if($gmode == 'CLI'){ //Exchange the hashkey value with template key
			$unikey_name = 'templatekey';
			$unikey_value = ($templatekey != null) ? $templatekey : '';
		}

		$updated_row_counts = $helpers_instance->update_count($unikey_value,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];
		
		$terms_table = $wpdb->term_taxonomy;        
        $taxonomy = $importType;
		
		$term_children_options = get_option("$taxonomy" . "_children");
		$_name = isset($data_array['name']) ? $data_array['name'] : '';
		$_slug = isset($data_array['slug']) ? $data_array['slug'] : '';
		$_desc = isset($data_array['description']) ? $data_array['description'] : '';
		$_image = isset($data_array['image']) ? $data_array['image'] : '';
		$_parent = isset($data_array['parent']) ? $data_array['parent'] : '';		
		$_display_type = isset($data_array['display_type']) ? $data_array['display_type'] : '';
		$_color = isset($data_array['color']) ? $data_array['color'] : '';
		$_top_content = isset($data_array['top_content']) ? $data_array['top_content'] : '';
		$_bottom_content = isset($data_array['bottom_content']) ? $data_array['bottom_content'] : '';

		$get_category_list = array();
		
		 if (strpos($_name, '>') !== false) {
			$get_category_list = explode('>', $_name);
		} else {
			$get_category_list[] = trim($_name);
		}				
		$_parent = is_numeric($_parent) ? (int)$_parent : $_parent;		
		$parent_term_id = 0;
		$termID = '';

		if (count($get_category_list) == 1) {
			$_name = trim($get_category_list[0]);
			if($_parent){
				$get_parent = term_exists($_parent, $taxonomy);				
				$parent_term_id = !empty($get_parent) ? $get_parent['term_id'] : 0;
			}
			else{
				$termid_value = $wpdb->get_results($wpdb->prepare("SELECT term.term_id FROM {$wpdb->prefix}terms AS term INNER JOIN {$wpdb->prefix}term_taxonomy AS tax ON term.term_id = tax.term_id WHERE term.slug = %s AND tax.taxonomy = %s", $_slug, $taxonomy));			
				if(isset($termid_value[0]->term_id)){
					$termid_val = $termid_value[0]->term_id;
					$term_parent_value = $wpdb->get_results("SELECT parent FROM {$wpdb->prefix}term_taxonomy WHERE term_id = '$termid_val'");
					$parent_term_id = !empty($term_parent_value) ? $term_parent_value[0]->parent : 0;
				}
			}
		} else {
			$count = count($get_category_list);
			$_name = trim($get_category_list[$count - 1]);
			$checkParent = trim($get_category_list[$count - 2]);
			if (strpos($checkParent, '&') !== false) {
				// Replace "&" with "&amp; it save in DB like this"
				$checkParent = str_replace('&', '&amp;', $checkParent);
			}				
			
			if($count>=3){
				$superParent = trim($get_category_list[$count - 3]);
				$parent_new =get_term_by('name',$superParent,$taxonomy);
				$parents_id=$parent_new->term_id;
				// $parent_term  = term_exists( "$checkParent", "$taxonomy", $parents_id);
				$parent_id = $wpdb->get_var("SELECT t.term_id from {$wpdb->prefix}terms as t inner join {$wpdb->prefix}term_taxonomy as tax ON  t.term_id=tax.term_id  WHERE t.name='$checkParent' and tax.taxonomy ='$taxonomy' and tax.parent=$parents_id");
				// if ( isset( $parent_term['term_id'] ) ) {
				// 	$parent_id = $parent_term['term_id'];
				// }
				$parent_term_id= $wpdb->get_var("select term_id from {$wpdb->prefix}term_taxonomy where taxonomy = '$taxonomy' and term_id=$parent_id ");							
			 }
			 else{
				$parent_term_id = $wpdb->get_var("select term_id from {$wpdb->prefix}term_taxonomy where taxonomy = '$taxonomy' and term_id in (select term_id from {$wpdb->prefix}terms where name = '$checkParent')");							
			 }
		}

		if($check == 'termid'){
			if(!empty($poly_values)){
				$language_code = $poly_values['language_code'];
				$termid = $data_array['TERMID'];
				$get_result=$wpdb->get_results("SELECT t.term_id FROM {$wpdb->prefix}terms as t inner join {$wpdb->prefix}term_taxonomy as tax ON t.term_id=tax.term_id where t.term_id='$termid' ",ARRAY_A);
				foreach($get_result as $res){
					$t_id=$res['term_id'];
					$lang_code=pll_get_term_language($t_id);
					if($lang_code == $language_code){
						$termID = $t_id;
					}

				}
			}
			else{
				$termID = array_key_exists('TERMID',$data_array) ? $data_array['TERMID'] : "";
			}
		}
		elseif($check == 'slug'){
			if(!empty($poly_values)){
				$language_code = $poly_values['language_code'];
				$get_result=$wpdb->get_results("SELECT t.term_id FROM {$wpdb->prefix}terms as t inner join {$wpdb->prefix}term_taxonomy as tax ON t.term_id=tax.term_id where slug='$_slug' ",ARRAY_A);
				// $get_result=$wpdb->get_results("SELECT t.term_id FROM {$wpdb->prefix}terms as t inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=t.term_id inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and t.slug='$_slug' ");
				foreach($get_result as $res){
					$t_id=$res['term_id'];
					$lang_code=pll_get_term_language($t_id);
					if($lang_code == $language_code){
						$termID = $t_id;
					}

				}
			}
			else{
				$get_termid = get_term_by( "slug" ,"$_slug" , "$taxonomy");
				if(!empty($get_termid))
					$termID = $get_termid->term_id;
				else
					$termID = "";
			}
		}	

		elseif($check == 'post_title'){
			$get_termid = get_term_by( "name" ,"$_name" , "$taxonomy");
			$termID = isset($get_termid->term_id) ? $get_termid->term_id : "";
		}	
		else{
			if ($update_based_on == 'acf') {
				if (is_plugin_active('advanced-custom-fields-pro/acf.php') || is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('secure-custom-fields/secure-custom-fields.php')) {
					$get_termid = get_term_by("name", "$_name", "$taxonomy");
					$termID = isset($get_termid->term_id) ? $get_termid->term_id : "";
				}
			}
					if ($update_based_on == 'jetengine') {
    if (is_plugin_active('jet-engine/jet-engine.php')) {
        $termID = '';
        $check_field_value = '';

        if (!empty($check)) {
            $field_index = array_search($check, $header_array ?? []);
            if ($field_index !== false) {
                $check_field_value = $value_array[$field_index] ?? '';
            }

            if (!empty($check_field_value)) {
                global $wpdb;
                $termID = $wpdb->get_var($wpdb->prepare(
                    "SELECT term_id FROM {$wpdb->prefix}termmeta
                     WHERE meta_key = %s AND meta_value = %s
                     LIMIT 1",
                    $check,
                    $check_field_value
                ));
            }
        }

        if (empty($termID)) {
            $get_term = get_term_by('name', $_name, $taxonomy);
            $termID = isset($get_term->term_id) ? $get_term->term_id : '';
        }
    }
} elseif ($update_based_on == 'toolset') {
				if (is_plugin_active('types/wpcf.php')) {
					$get_termid = get_term_by("name", "$_name", "$taxonomy");
					$termID = isset($get_termid->term_id) ? $get_termid->term_id : "";
				}
			} elseif ($update_based_on == 'metabox') {
				if (is_plugin_active('meta-box/meta-box.php') || is_plugin_active('meta-box-aio/meta-box-aio.php')  || is_plugin_active('meta-box-lite/meta-box-lite.php')) {
					$get_termid = get_term_by("name", "$_name", "$taxonomy");
					$termID = isset($get_termid->term_id) ? $get_termid->term_id : "";
				}
			}
			if ($update_based_on == 'pods') {
				if (is_plugin_active('pods/init.php')) {
					$get_termid = get_term_by("name", "$_name", "$taxonomy");
					$termID = isset($get_termid->term_id) ? $get_termid->term_id : "";
				}
			}
		}

		if($_display_type){
			$_display_type = $_display_type;
		}else{
			$term_id_value =$wpdb->get_results($wpdb->prepare("SELECT term.term_id FROM {$wpdb->prefix}terms AS term INNER JOIN {$wpdb->prefix}term_taxonomy AS tax ON term.term_id = tax.term_id WHERE term.slug = %s AND tax.taxonomy = %s", $_slug, $taxonomy));
			if(isset($term_id_value[0]->term_id)){
				$term_id_val = $term_id_value[0]->term_id;				
				$term_display_type_value = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}termmeta WHERE term_id = '$term_id_val' AND meta_key = 'display_type' ");
				
				if(!empty($term_display_type_value)){
					$_display_type = $term_display_type_value[0]->meta_value;
				}
			}
		}
		if($mode == 'Insert'){
			if(!empty($termID)){

				$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to duplicate Term found!.";
				$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
				$core_instance->detailed_log[$line_number]['cat_name'] = $_name;
				$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name  = '$unikey_value'");
				return array('MODE' => $mode, 'ERROR_MSG' => 'The term already exists!');

			}else{
				
				if(!empty($poly_values)){
					$language_code = $poly_values['language_code'];
					$get_result=$wpdb->get_results("SELECT t.term_id FROM {$wpdb->prefix}terms as t inner join {$wpdb->prefix}term_taxonomy as tax ON t.term_id=tax.term_id where slug='$_slug'  AND taxonomy='$taxonomy'",ARRAY_A);
					// $get_result=$wpdb->get_results("SELECT t.term_id FROM {$wpdb->prefix}terms as t inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=t.term_id inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and t.slug='$_slug' ");
					foreach($get_result as $res){
						$t_id=$res['term_id'];
						$lang_code=pll_get_term_language($t_id);
						if($lang_code == $language_code){
							$termID = $t_id;
						}
	
					}
					if(!empty($termID)){

						$core_instance->detailed_log[$line_number]['Message'] = "Skipped, Due to duplicate Term found!.";
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						$core_instance->detailed_log[$line_number]['cat_name'] = $_name;
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name  = '$unikey_value'");
						return array('MODE' => $mode, 'ERROR_MSG' => 'The term already exists!');
		
					}
					$term_table =  $wpdb->prefix.'terms';
					$taxoID = array();
					$query = $wpdb->get_results("INSERT INTO $term_table(`name`,`slug`,`term_group`) VALUES('{$_name}','{$_slug}','0')");
					$term_ids = $wpdb->get_results("SELECT  term_id FROM  {$wpdb->prefix}terms ORDER BY term_id ASC ", ARRAY_A);
					$end_term = end($term_ids);
					$taxoID['term_id'] = $end_term['term_id'];
					
					$term_ids = $end_term['term_id'];
					$tax_table = $wpdb->prefix.'term_taxonomy';
					$wpdb->get_results("INSERT INTO $tax_table(`term_id`,`taxonomy`) VALUES ($term_ids,'{$taxonomy}')");
				}
				else{
					$taxoID = wp_insert_term("$_name", "$taxonomy", array('description' => $_desc, 'slug' => $_slug));
				}
					if(is_wp_error($taxoID)){
						$core_instance->detailed_log[$line_number]['Message'] = "Can't insert this " . $taxonomy . ". " . $taxoID->get_error_message();
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						$core_instance->detailed_log[$line_number]['cat_name'] = $_name;
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name  = '$unikey_value'");
					}else{

						$termID= $taxoID['term_id'];
			        	$date = date("Y-m-d H:i:s");

						if(isset($_image)){
							TaxonomiesImport::$media_instance->store_image_ids($i=1);
							//$imageid = $media_instance->media_handling($_image , $termID ,$data_array ,'','','',$header_array ,$value_array);
							$imageid = TaxonomiesImport::$media_instance->image_meta_table_entry($line_number,'', $termID, 'thumbnail_id', $_image, $hash_key, 'term', 'term',$templatekey);
							
							if($importType == 'product_cat'){
								add_term_meta($termID , 'thumbnail_id' , $imageid); 
							}
							elseif($importType == 'event-categories' || $importType == 'event-tags'){
								$img_guid = $wpdb->get_results("select guid from {$wpdb->prefix}posts where id = '$imageid '");
								foreach($img_guid as $img_value){
									if(isset($img_value)){
									$guid =  $img_value->guid;
									}
								}

								if($importType == 'event-categories'){
									$guid=isset($guid)?$guid:'';
									$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'category-image', 'meta_value' => $guid, 'meta_date' => $date) );
									$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'category-image-id', 'meta_value' => $imageid, 'meta_date' => $date) );
								}elseif($importType == 'event-tags'){
									$guid=isset($guid)?$guid:'';
									$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'tag-image', 'meta_value' => $guid, 'meta_date' => $date) );
									$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'tag-image-id', 'meta_value' => $imageid, 'meta_date' => $date) );
								}
							}
						}

						if(isset($_display_type)){
							add_term_meta($termID , 'display_type' , $_display_type);
						}

						if(isset($_color)){
							if($importType == 'event-categories'){
								$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'category-bgcolor', 'meta_value' => $_color, 'meta_date' => $date) );
							}elseif($importType == 'event-tags'){
								$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'tag-bgcolor', 'meta_value' => $_color, 'meta_date' => $date) );
							}
						}
						
						if($importType == 'product_cat'){
							if(isset($_top_content) || isset($_bottom_content)){
								$cat_meta = array();
								$cat_meta['cat_header'] = $_top_content;
								$cat_meta['cat_footer'] = $_bottom_content;
								add_term_meta($termID , 'cat_meta' , $cat_meta);
							}
						}
						
						if(isset($parent_term_id) && !empty($termID)){							
							$update = $wpdb->get_results("UPDATE $terms_table SET `parent` = $parent_term_id WHERE `term_id` = $termID ");
						}	
						$returnArr = array('ID' => $termID, 'MODE' => $mode_of_affect);								
						if($importType = 'wpsc_product_category'){
							if(isset($data_array['category_image'])){
							$udir = wp_upload_dir();
							$imgurl = $data_array['category_image'];
							$img_name = basename($imgurl);
							$uploadpath = $udir['basedir'] . "/wpsc/category_images/";
							}
							if(isset($data_array['target_market'])){
								$custom_market = explode(',', $data_array['target_market']);
									foreach ($custom_market as $key =>$value) {
										$market[$value - 1] = $value;
									}					
							}
							$data_array['address_calculate']=isset( $data_array['address_calculate'])? $data_array['address_calculate']:'';
							$img_name=isset($img_name)?$img_name:'';
							$data_array['category_image_width']=isset($data_array['category_image_width'])?$data_array['category_image_width']:'';
							$data_array['category_image_height']=isset($data_array['category_image_height'])?$data_array['category_image_height']:'';
							$data_array['catelog_view']=isset($data_array['catelog_view'])?$data_array['catelog_view']:'';
							$market=isset($market)?$market:'';
							$meta_data = array('uses_billing_address' => $data_array['address_calculate'],'image' => $img_name,'image_width' => $data_array['category_image_width'],'image_height' => $data_array['category_image_height'],'display_type'=>$data_array['catelog_view'],'target_market'=>serialize($market));
								foreach($meta_data as $mk => $mv){
								// $wpdb->insert( $wpdb->prefix.'wpsc_meta', array('object_type' => 'wpsc_category','object_id' => $termID,'meta_key' => $mk,'meta_value' => $mv),array('%s','%d','%s','%s')); 
								}
						}

						if(!empty($wpml_array)) {							
						$wpml_instance = WPMLImport::getInstance();					
						$wpml_instance->set_wpml_values($header_array, $value_array, $wpml_array, $termID, $taxonomy, $line_number);
						$parent_term_id = empty($parent_term_id) ? 0 : $parent_term_id;

						//Last language does not displayed in module
						wp_update_term($termID, "$taxonomy", array('name' => $_name, 'slug' => $_slug , 'description' => $_desc, 'parent' => $parent_term_id));
						}
										
						$core_instance->detailed_log[$line_number]['Message'] = 'Inserted ' . $taxonomy . ' ID: ' . $termID;
						$core_instance->detailed_log[$line_number]['id'] = $termID;
						$core_instance->detailed_log[$line_number]['cat_name'] = $_name;
						$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_term_link( $termID, $taxonomy );
						$core_instance->detailed_log[$line_number]['state'] = 'Inserted';
						$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey_value'");
					}
			}
			if($unmatched_row == 'true'){
				global $wpdb;
				$post_entries_table = $wpdb->prefix ."post_entries_table";
				$file_table_name = $wpdb->prefix."smackcsv_file_events";
				$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE $unikey_name = '$unikey_value'");	
				$file_name = $get_id[0]->file_name;
				
				$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$termID}','{$type}', '{$file_name}','Inserted')");
			}

		} else {
				if(!empty($termID)){

					$checkterm = $wpdb->get_var("select term_id from {$wpdb->prefix}terms WHERE `term_id` = '$termID'");;
					if(empty($checkterm)){
						$core_instance->detailed_log[$line_number]['Message'] = "Skipped.Term does not exists already." ;
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						$core_instance->detailed_log[$line_number]['cat_name'] = $_name;
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name  = '$unikey_value'");
						return array('MODE' => $mode);
					}
					if($importType == 'event-categories' || $importType == 'event-tags'){
						 $wpdb->get_results("UPDATE {$wpdb->prefix}terms SET `name` = '$_name' , `slug` = '$_slug' WHERE `term_id` = '$termID'");
						 $wpdb->get_results("UPDATE $terms_table SET `description` = '$_desc' WHERE `term_id` = '$termID'");
					}
					else{
						if(!empty($wpml_array)) {							
							$wpml_instance = WPMLImport::getInstance();					
							$wpml_instance->set_wpml_values($header_array, $value_array, $wpml_array, $termID, $taxonomy, $line_number);
							$parent_term_id = empty($parent_term_id) ? 0 : $parent_term_id;
						}
						wp_update_term($termID, "$taxonomy", array('name' => $_name, 'slug' => $_slug , 'description' => $_desc));
						$wpdb->get_results("UPDATE $terms_table SET `description` = '$_desc' WHERE `term_id` = '$termID'");
					}
					
					$date = date("Y-m-d H:i:s"); 
				
					//start of added for adding thumbnail
					if(isset($_image)){
							TaxonomiesImport::$media_instance->store_image_ids($i=1);
							$_img = TaxonomiesImport::$media_instance->image_meta_table_entry($line_number,'', $termID, 'thumbnail_id', $_image, $hash_key, 'term', 'term',$templatekey);

							if($importType == 'product_cat'){
								update_term_meta($termID , 'thumbnail_id' , $_img); 
							}elseif($importType == 'event-categories' || $importType == 'event-tags'){
							
								$img_guid = $wpdb->get_results("select guid from {$wpdb->prefix}posts where id = '$_img' ");
								foreach($img_guid as $img_value){
										$guid =  $img_value->guid;
								}
								
								if($importType == 'event-categories'){
									 $wpdb->get_results("UPDATE $events_table SET `meta_value` = '$guid' , `meta_date` = '$date' WHERE `object_id` = '$termID' and `meta_key` = 'category-image'");
									 $wpdb->get_results("UPDATE $events_table SET `meta_value` = $_img  , `meta_date` = '$date' WHERE `object_id` = '$termID' and `meta_key` = 'category-image-id'");
									
								}elseif($importType == 'event-tags'){
									 $wpdb->get_results("UPDATE $events_table SET `meta_value` = '$guid' , `meta_date` = '$date' WHERE `object_id` = '$termID' and `meta_key` = 'tag-image' ");
									 $wpdb->get_results("UPDATE $events_table SET `meta_value` = $_img  , `meta_date` = '$date' WHERE `object_id` = '$termID' and `meta_key` = 'tag-image-id' ");
								}
							}				
					}

					if(isset($_display_type)){
                        update_term_meta($termID , 'display_type' , $_display_type);
                    }	
					//end of added for adding thumbnail

					if(isset($_color)){
						if($importType == 'event-categories'){
							 $wpdb->get_results("UPDATE $events_table SET `meta_value` = '$_color' , `meta_date` = '$date' WHERE `object_id` = '$termID' and `meta_key` = 'category-bgcolor' ");
						}elseif($importType == 'event-tags'){
							 $wpdb->get_results("UPDATE $events_table SET `meta_value` = '$_color' , `meta_date` = '$date' WHERE `object_id` = '$termID' and `meta_key` = 'tag-bgcolor' ");
						}
					}

					if($importType == 'product_cat'){
						if(isset($_top_content) || isset($_bottom_content)){
							$cat_meta = array();
							$cat_meta['cat_header'] = $_top_content;
							$cat_meta['cat_footer'] = $_bottom_content;
							update_term_meta($termID , 'cat_meta' , $cat_meta);
						}
					}

					if(isset($parent_term_id) && !empty($termID)){
						$update = $wpdb->get_results("UPDATE $terms_table SET `parent` = $parent_term_id WHERE `term_id` = $termID ");
					}
					
					//start wpsc_product_category meta fields
					if($importType = 'wpsc_product_category'){
						  if(isset($data_array['category_image'])){
							$udir = wp_upload_dir();
							$imgurl = $data_array['category_image'];
							$img_name = basename($imgurl);
							$uploadpath = $udir['basedir'] . "/wpsc/category_images/";
						  }
						  if(isset($data_array['target_market'])){
							$custom_market = explode(',', $data_array['target_market']);
							foreach ($custom_market as $key =>$value) {
								$market[$value - 1] = $value;
							}
						  }
						  $data_array['address_calculate']=isset($data_array['address_calculate'])?$data_array['address_calculate']:'';
						  $img_name=isset($img_name)?$img_name:'';
						  $data_array['category_image_width']=isset($data_array['category_image_width'])?$data_array['category_image_width']:'';
						  $data_array['category_image_height']=isset($data_array['category_image_height'])?$data_array['category_image_height']:''; 
						  $market=isset($market)?$market:'';
						  $data_array['catelog_view']=isset($data_array['catelog_view'])?$data_array['catelog_view']:'';
						  $meta_data = array('uses_billing_address' => $data_array['address_calculate'],'image' => $img_name,'image_width' => $data_array['category_image_width'],'image_height' => $data_array['category_image_height'],'display_type'=>$data_array['catelog_view'],'target_market'=>serialize($market));
						
						  foreach($meta_data as $mk => $mv){
						// $wpdb->insert( $wpdb->prefix.'wpsc_meta', array('object_type' => 'wpsc_category','object_id' => $termID,'meta_key' => $mk,'meta_value' => $mv),array('%s','%d','%s','%s'));  
						  }
						
					}
					//end wpsc_product_category meta fields
					$mode_of_affect = 'Updated';		
					$returnArr = array('ID' => $termID, 'MODE' => $mode_of_affect);
					
					if($unmatched_row == 'true'){
						global $wpdb;
						$post_entries_table = $wpdb->prefix ."post_entries_table";
						$file_table_name = $wpdb->prefix."smackcsv_file_events";
						$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `hash_key` = '$hash_key'");	
						$file_name = $get_id[0]->file_name;
						$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$termID}','{$type}', '{$file_name}','Updated')");
					}

					$core_instance->detailed_log[$line_number]['Message'] = 'Updated ' . $taxonomy . ' ID: ' . $termID;
					$core_instance->detailed_log[$line_number]['id'] = $termID;
					$core_instance->detailed_log[$line_number]['cat_name'] = $_name;
					$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_term_link( $termID, $taxonomy );
					$core_instance->detailed_log[$line_number]['state'] = 'Updated';
					$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey_value'");
				
				}else{		
					if($update_based_on  == 'skip'){			
						$core_instance->detailed_log[$line_number]['Message'] = "Skipped." ;
						$core_instance->detailed_log[$line_number]['cat_name'] = $_name;
						$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						$wpdb->get_results("UPDATE $log_table_name SET created = $skipped_count WHERE $unikey_name = '$unikey_value'");
						return array('MODE' => $mode);					
					}
					else{
						$taxoID = wp_insert_term("$_name", "$taxonomy", array('description' => $_desc, 'slug' => $_slug));
						if(is_wp_error($taxoID)){
							$core_instance->detailed_log[$line_number]['Message'] = "Can't insert this " . $taxonomy . ". " . $taxoID->get_error_message();
							$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
							$core_instance->detailed_log[$line_number]['cat_name'] = $_name;
							$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name  = '$unikey_value'");
						}else{
	
							$termID= $taxoID['term_id'];
							$date = date("Y-m-d H:i:s");
	
							if(isset($_image)){
								//$imageid = $media_instance->media_handling($_image , $termID ,$data_array ,'','','',$header_array ,$value_array);
								TaxonomiesImport::$media_instance->store_image_ids($i=1);
								$imageid = TaxonomiesImport::$media_instance->image_meta_table_entry($line_number,'', $termID, 'thumbnail_id', $_image, $hash_key, 'term', 'term',$templatekey);
								
								if($importType == 'product_cat'){
									add_term_meta($termID , 'thumbnail_id' , $imageid); 
								}
								elseif($importType == 'event-categories' || $importType == 'event-tags'){
									$img_guid = $wpdb->get_results("select guid from {$wpdb->prefix}posts where id = '$imageid '");
									foreach($img_guid as $img_value){
										if(isset($img_value)){
										$guid =  $img_value->guid;
										}
									}
	
									if($importType == 'event-categories'){
										$guid=isset($guid)?$guid:'';
										$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'category-image', 'meta_value' => $guid, 'meta_date' => $date) );
										$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'category-image-id', 'meta_value' => $imageid, 'meta_date' => $date) );
									}elseif($importType == 'event-tags'){
										$guid=isset($guid)?$guid:'';
										$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'tag-image', 'meta_value' => $guid, 'meta_date' => $date) );
										$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'tag-image-id', 'meta_value' => $imageid, 'meta_date' => $date) );
									}
								}
							}
	
							if(isset($_display_type)){
								add_term_meta($termID , 'display_type' , $_display_type);
							}
	
							if(isset($_color)){
								if($importType == 'event-categories'){
									$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'category-bgcolor', 'meta_value' => $_color, 'meta_date' => $date) );
								}elseif($importType == 'event-tags'){
									$wpdb->insert( $events_table , array('object_id' =>  $termID, 'meta_key' => 'tag-bgcolor', 'meta_value' => $_color, 'meta_date' => $date) );
								}
							}
							
							if($importType == 'product_cat'){
								if(isset($_top_content) || isset($_bottom_content)){
									$cat_meta = array();
									$cat_meta['cat_header'] = $_top_content;
									$cat_meta['cat_footer'] = $_bottom_content;
									add_term_meta($termID , 'cat_meta' , $cat_meta);
								}
							}
							
							if(isset($parent_term_id) && !empty($termID)){							
								$update = $wpdb->get_results("UPDATE $terms_table SET `parent` = $parent_term_id WHERE `term_id` = $termID ");
							}	
							$returnArr = array('ID' => $termID, 'MODE' => $mode_of_affect);								
							if($importType = 'wpsc_product_category'){
								if(isset($data_array['category_image'])){
								$udir = wp_upload_dir();
								$imgurl = $data_array['category_image'];
								$img_name = basename($imgurl);
								$uploadpath = $udir['basedir'] . "/wpsc/category_images/";
								}
								if(isset($data_array['target_market'])){
									$custom_market = explode(',', $data_array['target_market']);
										foreach ($custom_market as $key =>$value) {
											$market[$value - 1] = $value;
										}					
								}
								$data_array['address_calculate']=isset( $data_array['address_calculate'])? $data_array['address_calculate']:'';
								$img_name=isset($img_name)?$img_name:'';
								$data_array['category_image_width']=isset($data_array['category_image_width'])?$data_array['category_image_width']:'';
								$data_array['category_image_height']=isset($data_array['category_image_height'])?$data_array['category_image_height']:'';
								$data_array['catelog_view']=isset($data_array['catelog_view'])?$data_array['catelog_view']:'';
								$market=isset($market)?$market:'';
								$meta_data = array('uses_billing_address' => $data_array['address_calculate'],'image' => $img_name,'image_width' => $data_array['category_image_width'],'image_height' => $data_array['category_image_height'],'display_type'=>$data_array['catelog_view'],'target_market'=>serialize($market));
									foreach($meta_data as $mk => $mv){
									// $wpdb->insert( $wpdb->prefix.'wpsc_meta', array('object_type' => 'wpsc_category','object_id' => $termID,'meta_key' => $mk,'meta_value' => $mv),array('%s','%d','%s','%s')); 
									}
							}
	
							if(!empty($wpml_array)) {							
							$wpml_instance = WPMLImport::getInstance();					
							$wpml_instance->set_wpml_values($header_array, $value_array, $wpml_array, $termID, $taxonomy, $line_number);
							$parent_term_id = empty($parent_term_id) ? 0 : $parent_term_id;
	
							//Last language does not displayed in module
							wp_update_term($termID, "$taxonomy", array('name' => $_name, 'slug' => $_slug , 'description' => $_desc, 'parent' => $parent_term_id));
							}
											
							$core_instance->detailed_log[$line_number]['Message'] = 'Updated ' . $taxonomy . ' ID: ' . $termID;
							$core_instance->detailed_log[$line_number]['id'] = $termID;
							$core_instance->detailed_log[$line_number]['cat_name'] = $_name;
							$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_term_link( $termID, $taxonomy );
							$core_instance->detailed_log[$line_number]['state'] = 'Updated';
							$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey_value'");
						}
						if($unmatched_row == 'true'){
							global $wpdb;
							$post_entries_table = $wpdb->prefix ."post_entries_table";
							$file_table_name = $wpdb->prefix."smackcsv_file_events";
							$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `hash_key` = '$hash_key'");	
							$file_name = $get_id[0]->file_name;
							$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$termID}','{$type}', '{$file_name}','Updated')");
						}
					}
				}
			
		}

		if(!is_wp_error($termID)) {
			update_option("$taxonomy" . "_children", $term_children_options);
			delete_option($taxonomy . "_children");
		}
		return $returnArr;
    }
}

