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

class MetaBoxImport {
    private static $metabox_instance = null, $media_instance;

    public static function getInstance() {
		
		if (MetaBoxImport::$metabox_instance == null) {
			MetaBoxImport::$metabox_instance = new MetaBoxImport;
			MetaBoxImport::$media_instance = new MediaHandling();
			return MetaBoxImport::$metabox_instance;
		}
		return MetaBoxImport::$metabox_instance;
    }
	
    function set_metabox_values($header_array ,$value_array , $map, $post_id , $type,$line_number, $hash_key,$gmode,$templatekey){
		
		$post_values = [];
		$helpers_instance = ImportHelpers::getInstance();	
		$post_values = $helpers_instance->get_header_values($map , $header_array , $value_array);
		
		$this->metabox_import_function($post_values, $post_id , $header_array , $value_array, $type,$line_number, $hash_key,$gmode,$templatekey);
    }

    public function metabox_import_function ($data_array, $pID, $header_array, $value_array, $type,$line_number, $hash_key,$gmode,$templatekey) {
		
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$media_instance = MediaHandling::getInstance();
		$extension_object = new MetaBoxExtension;
		$import_as = $extension_object->import_post_types($type );
		$listTaxonomy = get_taxonomies();

		if($import_as == 'user')		{
			$get_metabox_fields = \rwmb_get_object_fields( $import_as,$import_as); 
			$get_import_type = 'user';
		}
		elseif(in_array($import_as, $listTaxonomy)){
			$get_metabox_fields = \rwmb_get_object_fields( $import_as,'term'); 
			$get_import_type = 'term';
		}
		else{
			$get_metabox_fields = \rwmb_get_object_fields($import_as); 
			$get_import_type = $import_as;
		}				
		
		
		foreach($data_array as $data_key => $data_value){
			$field_type = $get_metabox_fields[$data_key]['type'];
			$clonable = $get_metabox_fields[$data_key]['clone'];			
			$storage_type = isset($get_metabox_fields[$data_key]['storage']) ? 	$get_metabox_fields[$data_key]['storage'] : "";
			$timestamp = isset($get_metabox_fields[$data_key]['timestamp']) ? $get_metabox_fields[$data_key]['timestamp'] : "";
			$check_for_multiple = isset($get_metabox_fields[$data_key]['multiple']) ? $get_metabox_fields[$data_key]['multiple'] : '';
			$get_fieldset_options = isset($get_metabox_fields[$data_key]['options']) ? $get_metabox_fields[$data_key]['options'] : '';			
			if($field_type == 'taxonomy' || $field_type == 'taxonomy_advanced'){
				$custom_post_name = $get_metabox_fields[$data_key]['query_args']['taxonomy'][0];
				$post_type_count =count($get_metabox_fields[$data_key]['query_args']['taxonomy']);
				$field_types=$get_metabox_fields[$data_key]['field_type'];
			}
			else{
				if (isset($get_metabox_fields[$data_key]['query_args']['post_type'])) {
					$custom_post_name = $get_metabox_fields[$data_key]['query_args']['post_type'][0];
					$post_type_count = is_countable($get_metabox_fields[$data_key]['query_args']['post_type']) ? count($get_metabox_fields[$data_key]['query_args']['post_type']) : 0;
				} else {
					$custom_post_name = ''; 
					$post_type_count = 0;
				}

				$field_types= isset($get_metabox_fields[$data_key]['field_type']) ? $get_metabox_fields[$data_key]['field_type'] : '';
			}

			$addnew = isset($get_metabox_fields[$data_key]['add_new']) ? $get_metabox_fields[$data_key]['add_new'] : '';

			if($storage_type != "" && isset($storage_type->table)){			
				$this->importFieldsCustomTable($data_key,$data_value,$get_metabox_fields,$pID,$hash_key,$line_number,$type,$get_import_type,$gmode,$templatekey);
			}

			else {		
				
				if($clonable){

					$max_item = $get_metabox_fields[$data_key]['max_clone'];
					$this->metabox_clone_import($data_value,$pID,$type,$data_key,$field_type,$check_for_multiple,$timestamp,$max_item,$get_fieldset_options,$line_number, $hash_key, $get_import_type,$gmode,$templatekey,'',$custom_post_name,$addnew,$post_type_count,$field_types);
				}
				else {	
					$listTaxonomy = get_taxonomies();
					if (in_array($get_import_type, $listTaxonomy)) {
						$get_import_type = 'term';
					}elseif ($get_import_type == 'Users' || $type == 'user') {
						$get_import_type = 'user';
					}elseif ($get_import_type == 'Comments') {
						$get_import_type = 'comment';
					} else {	
						$get_import_type = 'post';
					}	

					if($field_type == 'text_list' || $field_type == 'select' || $field_type == 'select_advanced'){
						$get_text_list_fields = explode(',', $data_value);
						foreach($get_text_list_fields as $text_list_fields){
							if($check_for_multiple){
								add_post_meta($pID, $data_key, $text_list_fields);
							}
							else{
								update_post_meta($pID, $data_key, $text_list_fields);
							}
						}
					}
				    elseif ($field_type == 'checkbox_list') {
                    $get_checkbox_list_fields = explode(',', $data_value);
                    $existing_values = get_post_meta($pID, $data_key, false);
                    if (empty($existing_values)) {
                    foreach ($get_checkbox_list_fields as $checkbox_list_fields) {
                    add_post_meta($pID, $data_key, $checkbox_list_fields);
                    }
                    } else {
                    delete_post_meta($pID, $data_key);

                    foreach ($get_checkbox_list_fields as $checkbox_list_fields) {
                    add_post_meta($pID, $data_key, $checkbox_list_fields);
                    }
                    }
                    }
					elseif($field_type == 'fieldset_text'){
						$get_fieldset_text_fields = explode(',', $data_value);				
						$temp = 0;
						$fieldset_array = [];
						if(is_array($get_fieldset_options)){
							foreach($get_fieldset_options as $fieldset_key => $fieldset_options){
								$fieldset_array[$fieldset_key] = $get_fieldset_text_fields[$temp];
								$temp++;
							}
						}
						
				
						update_post_meta($pID, $data_key, $fieldset_array);
					}
					elseif($field_type == 'image' || $field_type == 'single_image' || $field_type == 'file' || $field_type == 'file_advanced' || $field_type == 'file_upload' || $field_type == 'image_advanced' || $field_type == 'image_upload'){
						$shortcode_table = $wpdb->prefix . "ultimate_csv_importer_shortcode_manager";
						$indexs = 0;
						$get_uploads_fields = explode(',', $data_value);
						$get_fields_count = count($get_uploads_fields);
						foreach($get_uploads_fields as $uploads_fields){
							MetaBoxImport::$media_instance->store_image_ids($i=1);
							if($field_type == 'image' || $field_type == 'single_image' || $field_type == 'file_advanced' || $field_type == 'file'){
								$attachmentId = MetaBoxImport::$media_instance->image_meta_table_entry($line_number,'', $pID, $data_key, $uploads_fields, $hash_key, 'metabox', $get_import_type,$templatekey,$gmode,'','','','',$indexs);
							}
							elseif($field_type == 'image_advanced'){
								$attachmentId = MetaBoxImport::$media_instance->image_meta_table_entry($line_number,'', $pID, $data_key, $uploads_fields, $hash_key, 'metabox_advanced', $get_import_type,$templatekey,$gmode,'','','','',$indexs);
							}
							elseif($field_type == 'image_upload' || $field_type == 'file_upload'){
								$attachmentId = MetaBoxImport::$media_instance->image_meta_table_entry($line_number,'', $pID, $data_key, $uploads_fields, $hash_key, 'metabox_upload', $get_import_type,$templatekey,$gmode,'','','','',$indexs);
							}
							if($get_fields_count > 1){
								add_post_meta($pID, $data_key, $attachmentId);	
							}
							else{
								update_post_meta($pID, $data_key, $attachmentId);	
							}
							$indexs++;
						}	
					}
					elseif($field_type == 'file_input'){
						$attachmentId = MetaBoxImport::$media_instance->media_handling($data_value, $pID);
						$get_file_url = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND ID = $attachmentId");
						update_post_meta($pID, $data_key, $get_file_url);
					}
					elseif($field_type == 'password'){
						$data_value = wp_hash_password($data_value);
						update_post_meta($pID, $data_key, $data_value);
					}
					elseif($field_type == 'post' || $field_type == 'user' || $field_type == 'taxonomy'){
						if(is_numeric($data_value)){
							update_post_meta($pID, $data_key, $data_value);
						}
						else{
							if($field_type == 'post'){
								$custom_post_name = $get_metabox_fields[$data_key]['query_args']['post_type'][0];
								$addnew = $get_metabox_fields[$data_key]['add_new'];
								if($check_for_multiple){
									$postTitle = explode(',',$data_value);
									foreach($postTitle as $data_post){
										$get_post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$data_post' AND post_status != 'trash' ");
										if(empty($get_post_id) && $addnew){
											$wordpress_post = array('post_title'   => $data_post,'post_status'  => 'publish','post_author'  => 1,'post_type'    =>  $custom_post_name);
											$get_post_id = wp_insert_post($wordpress_post);
										}
										add_post_meta($pID, $data_key, $get_post_id);
									}
								}else{
								    $get_post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$data_value' AND post_status != 'trash' ");
									if(empty($get_post_id) && $addnew){
										$wordpress_post = array('post_title'   => $data_value,'post_status'  => 'publish','post_author'  => 1, 'post_type'    =>  $custom_post_name);
										$get_post_id = wp_insert_post($wordpress_post);
									}
								}
							}
							elseif($field_type == 'user'){
								$addnew = $get_metabox_fields[$data_key]['add_new'];
								if($check_for_multiple){
									$userName = explode(',',$data_value);
									foreach($userName as $userData){
										$get_post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE user_login = '$userData' ");
										if(empty($get_post_id) && $addnew){
											$user_id = wp_insert_user( array( 'user_login' => $userData, 'user_pass' =>  $userData,  'user_email' => $userData.'@gamil.com',  'first_name' => $userData,  'role' => 'subscriber'));
											$get_post_id = $user_id;
										}
										add_post_meta($pID, $data_key, $get_post_id);
									}
								}else{
									$get_post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE user_login = '$data_value' ");
									if(empty($get_post_id) && $addnew){
										$user_id = wp_insert_user( array( 'user_login' => $data_value, 'user_pass' =>  $data_value,  'user_email' => $data_value.'@gamil.com',  'first_name' => $data_value,  'role' => 'subscriber'));
										$get_post_id = $user_id;
									}
								}
							}
							elseif($field_type == 'taxonomy'){
								$addnew = $get_metabox_fields[$data_key]['add_new'];
								if (strpos($data_value, '|') !== false) {
									$term_fd = explode('|', $data_value);
								} else {
									$term_fd = explode(',', $data_value);
								}
								foreach ($term_fd as $value) {	
									if(!empty($value) && isset($value)){
										$taxonomy = $get_metabox_fields[$data_key]['taxonomy']['0'];
										// Escape values to prevent SQL syntax errors
										$encoded_value = htmlspecialchars($value, ENT_QUOTES);
										$encoded_value = esc_sql($encoded_value);
										$taxonomy = $get_metabox_fields[$data_key]['taxonomy']['0'];
										$taxonomy = esc_sql($taxonomy);
										$value = str_replace("’", "'", $value); 
										$value = trim($value);
										$query = $wpdb->prepare(
											"SELECT tt.term_taxonomy_id 
											 FROM {$wpdb->prefix}terms t
											 INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id
											 WHERE (t.name = %s OR t.name = %s) AND tt.taxonomy = %s",
											$value, $encoded_value, $taxonomy
										);
										$get_post_id = $wpdb->get_var($query);										
										if($get_post_id){
											$wpdb->get_results("INSERT into {$wpdb->prefix}term_relationships (`object_id`,`term_taxonomy_id`) VALUES($pID,$get_post_id)");
										}
										else if(empty($get_post_id) && $addnew && ($post_type_count <=1) && !empty($value)) {
											$tag_name = $value;
											// Optional arguments for the tag
											$args = array(
												'slug' => $value, // Customize the slug
												'description' => 'Description of the new tag', // Add a description
												'parent' => 0, // Set a parent tag (if applicable)
											);
											// Insert the tag
											$result = wp_insert_term($tag_name, $taxonomy, $args);
											if (!is_wp_error($result)) {
												$get_post_id = $result['term_taxonomy_id'];
											}
											$wpdb->get_results("INSERT into {$wpdb->prefix}term_relationships (`object_id`,`term_taxonomy_id`) VALUES($pID,$get_post_id)");
										}
									}					
								}
							}
							if(!$check_for_multiple){
								update_post_meta($pID, $data_key, $get_post_id);
							}
						}	
					}
					else if($field_type == 'taxonomy_advanced'){
						$addnew = $get_metabox_fields[$data_key]['add_new'];
						$term_fd = $term_field_data= array();
						if (strpos($data_value, '|') !== false) {
							$term_fd = explode('|', $data_value);
						} else {
							$term_fd = explode(',', $data_value);
						}
						foreach($term_fd as $value){
							if(is_numeric($value)){
								$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE term_id = $value");
								if($id){
									$term_field_data[] = $id;
								}
							}
							else {
								$taxonomy = $get_metabox_fields[$data_key]['taxonomy']['0'];
								$encoded_value = htmlspecialchars($value, ENT_QUOTES);
								$encoded_value = esc_sql($encoded_value);
								$taxonomy = esc_sql($taxonomy);
								$id = $wpdb->get_var(
									"SELECT t.term_id FROM {$wpdb->prefix}terms t 
									 INNER JOIN {$wpdb->prefix}term_taxonomy tax 
									 ON t.term_id = tax.term_id 
									 WHERE (name = '$encoded_value' OR name = '$value') AND tax.taxonomy = '$taxonomy'"
								);
								if($id){
									$term_field_data[] = $id;
								}
								else{
									if(!empty($value) && isset($addnew) && isset($custom_post_name) && ($post_type_count <=1)){
										$tag_name = trim($value);
											// Optional arguments for the tag
											$args = array(
												'slug' => sanitize_title($tag_name), // Ensure a valid slug
												'description' => 'Description of the new tag',
												'parent' => 0,
											);
										
											// Insert the tag
											$result = wp_insert_term($tag_name, $custom_post_name, $args);
										
											if (!is_wp_error($result)) {
												$term_field_data[] = $result['term_taxonomy_id'];
											}
									}
								}
								$wpdb->get_results("INSERT into {$wpdb->prefix}term_relationships (`object_id`,`term_taxonomy_id`) VALUES($pID,$get_post_id)");
									
							}
						}
						$field_arr = implode(',',$term_field_data,);
						update_post_meta($pID, $data_key, $field_arr);
					}
					elseif($field_type == 'video'){
						$media_fd = explode(',',$data_value);
						$media_arr = array();
						foreach($media_fd as $data){
							if(is_numeric($data)){
								$media_arr[] = $data;
							}
							else {
								$attachmentId = $media_instance->media_handling($data, $pID);
								if($attachmentId)
									$media_arr[] = $data;
							}
						}	
						$media_arr = implode(',',$media_arr);
						update_post_meta($pID, $data_key, $media_arr);			
					}
					elseif($field_type == 'date'){
						$dateformat = $field_type == 'date' ? "Y-m-d" : "Y-m-d H:i:s";
							$date_arr = array();
							if($timestamp) {								
								$date = $helpers_instance->validate_datefield($data_value,$data_key,$dateformat,$line_number);				
								if(!empty($date)){
									$date = strtotime($date);																		
									update_post_meta($pID, $data_key, $date);
								}
							}
							else {
								$date = $helpers_instance->validate_datefield($data_value,$data_key,$dateformat,$line_number);				
								if(!empty($date))
								update_post_meta($pID, $data_key, $date);				
							}
					}
					else{ 
						//text,textarea,radio				
						update_post_meta($pID, $data_key, $data_value);

						$get_meta_id = $wpdb->get_var("SELECT meta_id FROM {$wpdb->prefix}postmeta WHERE post_id = $pID AND meta_key = '$data_key' ");
						if(!empty($get_meta_id)){
							$wpdb->update( $wpdb->prefix . 'postmeta', 
								array( 
									'meta_value' => $data_value,
								) , 
								array( 
									'meta_id' => $get_meta_id
								) 
							);
						}
						else{
							$wpdb->insert($wpdb->prefix . 'postmeta',
								array(
									'post_id' => $pID,
									'meta_key' => $data_key,
									'meta_value' => $data_value
								),
								array('%d','%s','%s')
							);
						}
					}	
				
					if($data_array){
						if($type == 'Users'){ // User module
							foreach($data_array as $data_key => $data_value) {
								$field_type = $get_metabox_fields[$data_key]['type'];
								$fileExtensions = array('.jpg', '.jpeg', '.png', '.gif', '.bmp', '.tif', '.tiff', '.webp', '.ico', '.csv','.svg','.xlsx','.json' ,'.xml','.eps', '.pdf', '.psd', '.ai', '.raw', '.indd');
								$containsImageExtension = false;
								foreach ($fileExtensions as $extension) {
									if (strpos($data_value, $extension) !== false) {
										$containsImageExtension = true;
										break;
									}
								}
								if ($containsImageExtension) {
									if ($field_type == 'image' || $field_type == 'single_image' || $field_type == 'file' || $field_type == 'file_advanced' || $field_type == 'file_upload' || $field_type == 'image_advanced' || $field_type == 'file_input') {
										$attachmentId = MetaBoxImport::$media_instance->media_handling($data_value, $pID);
										if ($attachmentId) {
											update_user_meta($pID, $data_key, $attachmentId);
										}
									}
									}
									 if(!$containsImageExtension) {
										if($field_type == 'date'){
											$data_value = strtotime($data_value);
										}
										update_user_meta($pID, $data_key, $data_value);
									}
							}
						}
						
						if(in_array($type, $listTaxonomy)){ //term module
							foreach($data_array as $data_key => $data_value){
								update_term_meta($pID,$data_key,$data_value);
							}
						}	
								
					}
			
				}
			}
		}
	}

	public function metabox_clone_import ($data_array, $pID,$type,$data_key,$field_type,$is_multiple,$timestamp,$max_item,$options,$line_number, $hash_key, $get_import_type,$gmode,$templatekey,$customtable ,$custom_post_name,$addnew,$post_type_count,$field_types) {
		global $wpdb;		
		$customtable = null;
		$helpers_instance = ImportHelpers::getInstance();
		$media_instance = MediaHandling::getInstance();
		$extension_object = new MetaBoxExtension;
		$import_as = $extension_object->import_post_types($type );
		$listTaxonomy = get_taxonomies();
		$field_arr = array();
		$value_array = explode('|',$data_array);		
		$count = 0;
		$image_type='metabox_clone';
		foreach($value_array as $fvalue){	
			
			switch($field_type){
				case 'date':
					case 'datetime':
						{							
							$dateformat = $field_type == 'date' ? "Y-m-d" : "Y-m-d H:i:s";
							$date_arr = array();
							if($timestamp) {								
								$date = $helpers_instance->validate_datefield($fvalue,$data_key,$dateformat,$line_number);				
								if(!empty($date)){																		
									$field_arr[] = strtotime($date);
								}
							}
							else {
								$date = $helpers_instance->validate_datefield($fvalue,$data_key,$dateformat,$line_number);				
								if(!empty($date))
									$field_arr[] = $date;									
							}
							break;
						}
					case 'checkbox_list':
					case 'autocomplete':
					case 'text_list':
						{                                
							$field_arr[] = explode(',',$fvalue); 
							break;
						}
					case 'checkbox':
						{                           
							if($fvalue)
							$field_arr[] = $fvalue;      							
							break;
						}
					case 'fieldset_text':
						{							
							if(!empty($options)){								
							$fieldset_keys = array_keys($options);							
							$fieldset_values = explode(',',$fvalue);							
							$fieldset_arr = array_combine($fieldset_keys,$fieldset_values);
							$field_arr[] = $fieldset_arr;	
							}						
							break;
						}	
					case 'image':{
						$field_arr[] = $this->process_media($fvalue, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,'','',$count);
						break;
					}
					case 'file_advanced':{
						$field_arr[] = $this->process_media($fvalue, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,'','',$count);
						break;
					}
					case 'file_upload':{
						$field_arr[] = $this->process_media($fvalue, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,'','',$count);
						break;
					}									
					case 'image_upload':{
						$field_arr[] = $this->process_media($fvalue, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,'','',$count);
						break;
					}
					case 'image_advanced':{							
						$field_arr[] = $this->process_media($fvalue, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,'','',$count);
						break;
					}
					case 'video':{							
						$field_arr[] = $this->process_media($fvalue, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,true,'',$count);
						break;
					}
					case 'single_image': {
						MetaBoxImport::$media_instance->store_image_ids($i=1);
						if(is_numeric($fvalue)){
							$field_arr[] = $fvalue;	
						}
						else {
							//$attachmentId = $media_instance->media_handling($fvalue, $pID);
							$attachmentId =MetaBoxImport::$media_instance->image_meta_table_entry($line_number,'', $pID, $data_key, $fvalue, $hash_key, 'metabox_clone__'.$count.'_', $get_import_type,$templatekey,$gmode);
							if($attachmentId)
							$field_arr[] = $attachmentId;
						}
						break;
						}
					case 'file_input': {
						if(is_numeric($fvalue)){
							$url = $wpdb->get_var("select guid from {$wpdb->prefix}posts where id = $fvalue");
							if(!empty($url)){
								$field_arr[] = $url;
							}
						}
						else {
							$field_arr[] = $fvalue;							
						}
						break;
					}
					case 'post':
						{
							$post_field_data = array();
							if(($is_multiple && ($field_types !='select_tree') && ($field_types !='radio_list') ) ||(($field_types =='checkbox_list') || $field_types =='checkbox_tree')){
								$post_fd = explode(',',$fvalue);
								foreach($post_fd as $value){
									if(is_numeric($value)){
										$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE id = $value AND post_status != 'trash' ");
										if($id)
											$post_field_data[] = $id;
									}
									else {
										$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$value' AND post_status != 'trash' ");
										if($id){
											$post_field_data[] = $id;
										}else{
											if(isset($addnew) && isset($custom_post_name) && ($post_type_count <=1)){
												$wordpress_post = array(
													'post_title'   => $value,
													'post_status'  => 'publish',
													'post_author'  => 1,
													'post_type'    => $custom_post_name // Replace 'custom_post_type' with your actual custom post type slug
												);
												$id = wp_insert_post($wordpress_post);
												$post_field_data[] = $id;
											}
											
										}
									}
								}
								if(!empty($post_field_data)){
									$field_arr[] = $post_field_data;
								}

							}
							else {
							if(is_numeric($fvalue)){
								$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE id = $fvalue AND post_status != 'trash' ");
								if($id) // Check it exists or not
								$field_arr[] = $fvalue;
							}
							else {

								$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$fvalue' AND post_status != 'trash' ");
								if($id){
									$field_arr[] = $id;
								}else{
									if(isset($addnew) && isset($custom_post_name) && ($post_type_count <=1)){
										$wordpress_post = array(
											'post_title'   => $fvalue,
											'post_content' => 'new page',
											'post_status'  => 'publish',
											'post_author'  => 1,
											'post_type'    => $custom_post_name // Replace 'custom_post_type' with your actual custom post type slug
										);
										$id = wp_insert_post($wordpress_post);
										$field_arr[] = $id;
									}
								}
							}
						}					
							break;
						}
					case 'user':
						{
							$user_field_data = array();
							if(($is_multiple && ($field_types !='select_tree') && ($field_types !='radio_list') ) ||(($field_types =='checkbox_list') || $field_types =='checkbox_tree')){
								$user_fd = explode(',',$fvalue);
								foreach($user_fd as $value){
									if(is_numeric($value)){
										$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE id = $value");
										if($id){
											$user_field_data[] = $id;
										}
										
									}
									else {
										$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE user_login = '$value' ");
										if($id)
										{
											$user_field_data[] = $id;
										}
										else{
											if(isset($addnew)){
												$user_id = wp_insert_user( array( 'user_login' => $value, 'user_pass' =>  $value,  'user_email' => $value.'@gamil.com',  'first_name' => $value,  'role' => 'subscriber'));
												$user_field_data[] = $user_id;	
											}
										}
									}
								}
								$field_arr[] = $user_field_data;
							}
							else{
							if(is_numeric($fvalue)){
								$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE id = $fvalue");
								if($id) // Check it exists or not
								$field_arr[] = $fvalue;
							}
							else {
								$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE user_login = '$fvalue' ");
								if($id){
									$field_arr[] = $id;
								}
								else{
									if(isset($addnew)){
										$user_id = wp_insert_user( array( 'user_login' => $fvalue, 'user_pass' =>  $fvalue,  'user_email' => $fvalue.'@gamil.com',  'first_name' => $fvalue,  'role' => 'subscriber'));
										$field_arr[] = $user_id;	
									}
								}
								
							}
						}
							break;
						}
					case 'taxonomy_advanced':
						{
							$term_field_data = array();
							if(($is_multiple && ($field_types !='select_tree') && ($field_types !='radio_list') ) ||(($field_types =='checkbox_list') || $field_types =='checkbox_tree')){
								$term_fd = explode(',',$fvalue);								

								foreach($term_fd as $value){
									if(is_numeric($value)){
										$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE term_id = $value");
										if($id)
											$term_field_data[] = $id;
									}
									else {
										$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE name = '$value' ");
										if($id){
											$term_field_data[] = $id;
										}
										else{
											if(isset($addnew) && isset($custom_post_name) && ($post_type_count <=1)){
												$tag_name = $value;
												// Optional arguments for the tag
												$args = array(
													'slug' => $value, // Customize the slug
													'description' => 'Description of the new tag', // Add a description
													'parent' => 0, // Set a parent tag (if applicable)
												);
												// Insert the tag
												$result = wp_insert_term($tag_name, $custom_post_name, $args);
												$get_post_id = $result['term_taxonomy_id'];
												$term_field_data[] = $result['term_id'];
											}
										}
										$wpdb->get_results("INSERT into {$wpdb->prefix}term_relationships (`object_id`,`term_taxonomy_id`) VALUES($pID,$get_post_id)");
											
									}
								}
								$field_arr[] = $term_field_data;
							}
							else {
							if(is_numeric($fvalue)){
								$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE term_id = $fvalue");
								if($id) // Check it exists or not
								$field_arr[] = $id;
							}
							else {
								$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE name = '$fvalue' ");
								if($id){
									$field_arr[] = $id;
								}
								else{
									if(isset($addnew) && isset($custom_post_name)&& ($post_type_count <=1)){
										$tag_name = $fvalue;
										// Optional arguments for the tag
										$args = array(
											'slug' => $fvalue, // Customize the slug
											'description' => 'Description of the new tag', // Add a description
											'parent' => 0, // Set a parent tag (if applicable)
										);
										// Insert the tag
										$result = wp_insert_term($tag_name, $custom_post_name, $args);

										$get_post_id = $result['term_taxonomy_id'];
										$term_field_data = $result['term_id'];
									}
									$field_arr[] = $term_field_data;	
								}

								
							}
						}
							break;
						}			
					default:
					{					
						if($is_multiple){
							$field_arr[] = explode(',',$fvalue);
						}
						else {												
							$field_arr[] = $fvalue;												
						}
						break;
					}
			}
			$count++;
			if($max_item == $count){
				break;
			}
			else
				continue;
		}
		
		if($customtable){
			if(is_array($field_arr))
				$field_arr = serialize($field_arr);
			$id = $wpdb->get_var("select * from $customtable where ID = $pID");
				if($id){									
					$wpdb->update($customtable,
					array($data_key => $field_arr),
					array("ID" => $pID));
				}													
				else {
				$wpdb->insert($customtable,
				array("ID" => $pID,
				"$data_key" => $field_arr),
				array('%d','%s'));
				}
		}
		else {
			if($field_type == 'taxonomy_advanced'){
				$field_arrs =$field_arr;
				if($import_as == 'user')
					update_user_meta($pID,$data_key,$field_arrs);
				elseif(in_array($import_as, $listTaxonomy)){
					update_term_meta($pID,$data_key,$field_arrs);
				}
				else
					update_post_meta($pID, $data_key, $field_arrs);	
			}
			else{
				if($import_as == 'user')
				update_user_meta($pID,$data_key,$field_arr);
			elseif(in_array($import_as, $listTaxonomy)){
				update_term_meta($pID,$data_key,$field_arr);
			}
			else
				update_post_meta($pID, $data_key, $field_arr);
			}
			
								
	}
	}

	public function importFieldsCustomTable($data_key,$data_value,$fieldData,$pID,$hash_key,$line_number,$type,$get_import_type,$gmode,$templatekey){
		$storage_type = $fieldData[$data_key]['storage'];
		$customtable = $storage_type->table;
		$field_type = $fieldData[$data_key]['type'];
		$is_multiple = isset($fieldData[$data_key]['multiple']) ? $fieldData[$data_key]['multiple'] : 0;
		$timestamp = isset($fieldData[$data_key]['timestamp']) ? $fieldData[$data_key]['timestamp'] : 0;
		$options = isset($fieldData[$data_key]['options']) ? $fieldData[$data_key]['options'] : "";					
		$image_type = 'metabox';
		$field_name ='importFieldsCustomTable';
		$clonable = $fieldData[$data_key]['clone'];
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$media_instance = MediaHandling::getInstance();
		if($field_type == 'taxonomy' || $field_type == 'taxonomy_advanced'){
			$custom_post_name = $fieldData[$data_key]['query_args']['taxonomy'][0];
			$post_type_count =count($fieldData[$data_key]['query_args']['taxonomy']);
			$field_types=$fieldData[$data_key]['field_type'];
		}
		else{
			if(isset($fieldData[$data_key]['query_args']['post_type'])){
				$custom_post_name = $fieldData[$data_key]['query_args']['post_type'][0];
				$post_type_count =count($fieldData[$data_key]['query_args']['post_type']);
				$field_types=$fieldData[$data_key]['field_type'];
			}else{
				$custom_post_name = '';
				$post_type_count = 0;
				$field_types ='';
			};
		}

		$addnew = isset($fieldData[$data_key]['add_new']) ? $fieldData[$data_key]['add_new'] : '';

		if($clonable){
			$max_item = $fieldData[$data_key]['max_clone'];
			$this->metabox_clone_import($data_value,$pID,$type,$data_key,$field_type,$is_multiple,$timestamp,$max_item,$options,$line_number, $hash_key, $get_import_type,$gmode,$templatekey,$customtable,$custom_post_name,$addnew,$post_type_count,$field_types);
		}

		else {
		switch($field_type){
			case 'date':
			case 'datetime':
					{						
						$dateformat = $field_type == 'date' ? "Y-m-d" : "Y-m-d H:i:s";						
						$date_arr = array();
						if($timestamp) {								
							$date = $helpers_instance->validate_datefield($data_value,$data_key,$dateformat,$line_number);				
							if(!empty($date)){																		
								$field_arr = strtotime($date);
							}
						}
						else {
							$date = $helpers_instance->validate_datefield($data_value,$data_key,$dateformat,$line_number);				
							if(!empty($date))
								$field_arr = $date;									
						}
						break;
					}
			case 'checkbox_list':
			case 'autocomplete':
			case 'text_list':
					{                                
						$field_arr = explode(',',$data_value); 
						break;
					}
				case 'checkbox':
					{                           
						if($data_value)
						$field_arr = $data_value;      							
						break;
					}
				case 'fieldset_text':
					{								
						if(!empty($options)){								
						$fieldset_keys = array_keys($options);							
						$fieldset_values = explode(',',$data_value);							
						$fieldset_arr = array_combine($fieldset_keys,$fieldset_values);
						$field_arr = $fieldset_arr;	
						}						
						break;
					}	
					case 'image':{
						$field_arr = $this->process_media($data_value, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,'',$field_name,'');
						break;
					}
					case 'file_advanced':{
						$field_arr = $this->process_media($data_value, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,'',$field_name,'');
						break;
					}
					case 'file_upload':{
						$field_arr = $this->process_media($data_value, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,'',$field_name,'');
						break;
					}									
					case 'image_upload':{
						$field_arr = $this->process_media($data_value, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,'',$field_name,'');
						break;
					}
					case 'image_advanced':{							
						$field_arr = $this->process_media($data_value, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type,'',$field_name,'');
						break;
					}
					case 'video':
						{							
						$field_arr = $this->process_media($data_value, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type, true,$field_name,'');
						break;
						}
				case 'single_image': {
					MetaBoxImport::$media_instance->store_image_ids($i=1);
					if(is_numeric($data_value)){
						$field_arr = $data_value;	
					}
					else {
						$attachmentId = MetaBoxImport::$media_instance->image_meta_table_entry($line_number,'', $pID, $data_key, $data_value, $hash_key,'metabox_', $get_import_type,$templatekey,$gmode);
						if($attachmentId)
						$field_arr = $attachmentId;
					}
					break;
				}
				case 'file_input': {
					if(is_numeric($data_value)){
						$url = $wpdb->get_var("select guid from {$wpdb->prefix}posts where id = $data_value");
						if(!empty($url)){
							$field_arr = $url;
						}
					}
					else {
						$field_arr = $data_value;							
					}
					break;
				}				
				case 'post':
						{
							$post_field_data = array();
							if(($is_multiple && ($field_types !='select_tree') && ($field_types !='radio_list') ) ||(($field_types =='checkbox_list') || $field_types =='checkbox_tree')){
								$post_fd = explode(',',$data_value);
								foreach($post_fd as $value){
									if(is_numeric($value)){
										$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE id = $value AND post_status != 'trash' ");
										if($id)
											$post_field_data[] = $id;
									}
									else {
										$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$value' AND post_status != 'trash' ");
										if($id){
											$post_field_data[] = $id;
										}else{
											if(isset($addnew) && isset($custom_post_name) && ($post_type_count <=1)){
												$wordpress_post = array(
												'post_title'   => $value,
												'post_status'  => 'publish',
												'post_author'  => 1,
												'post_type'    => $custom_post_name // Replace 'custom_post_type' with your actual custom post type slug
												);
												$id = wp_insert_post($wordpress_post);
												$post_field_data[] = $id;
											}
											
										}
									}
								}
								$field_arr = $post_field_data;

							}
							else {								
							if(is_numeric($data_value)){
								$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE id = $data_value AND post_status != 'trash' ");
								if($id) // Check it exists or not
								$field_arr = $data_value;
							}
							else {
								$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$data_value' AND post_status != 'trash' ");
								
								if($id){
									$field_arr = $id;
								}else{
									if(isset($addnew) && isset($custom_post_name) && ($post_type_count <=1)){
										$wordpress_post = array(
											'post_title'   => $data_value,
											'post_content' => 'new page',
											'post_status'  => 'publish',
											'post_author'  => 1,
											'post_type'    => $custom_post_name // Replace 'custom_post_type' with your actual custom post type slug
										);
										$id = wp_insert_post($wordpress_post);
										$field_arr = $id;
									}
								}
							}
						}						
							break;
						}
					case 'user':
					{
						$user_field_data = array();
						if(($is_multiple && ($field_types !='select_tree') && ($field_types !='radio_list') ) ||(($field_types =='checkbox_list') || $field_types =='checkbox_tree')){
							$user_fd = explode(',',$data_value);
							foreach($user_fd as $value){
								if(is_numeric($value)){
									$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE id = $value");
									if($id)
										$user_field_data[] = $id;
								}
								else {
									$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE user_login = '$value' ");
									if($id)
									{
										$user_field_data[] = $id;
									}
									else{
										if(isset($addnew)){
											$user_id = wp_insert_user( array( 'user_login' => $value, 'user_pass' =>  $value,  'user_email' => $value.'@gamil.com',  'first_name' => $value,  'role' => 'subscriber'));
											$user_field_data[] = $user_id;	
										}
									}
								}
							}
							$field_arr = $user_field_data;
						}
						else{
							if(is_numeric($data_value)){
								$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE id = $data_value");
								if($id) // Check it exists or not
									$field_arr = $id;
							}
							else {
								$id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE user_login = '$data_value' ");
								if($id){
									$field_arr = $id;
								}
								else{
									if(isset($addnew)){
										$user_id = wp_insert_user( array( 'user_login' => $data_value, 'user_pass' =>  $data_value,  'user_email' => $data_value.'@gamil.com',  'first_name' => $data_value,  'role' => 'subscriber'));
										$field_arr = $user_id;	
									}
								}
							}
						}
						break;
					}
					case 'taxonomy_advanced':
						{
							$term_field_data = array();
							if(($is_multiple && ($field_types !='select_tree') && ($field_types !='radio_list') ) ||(($field_types =='checkbox_list') || $field_types =='checkbox_tree')){
								$term_fd = explode(',',$data_value);								

								foreach($term_fd as $value){
									if(is_numeric($value)){
										$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE term_id = $value");
										if($id)
											$term_field_data[] = $id;
									}
									else {
										$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE name = '$value' ");
										if($id){
											$term_field_data[] = $id;
										}
										else{
											if(isset($addnew) && isset($custom_post_name) && ($post_type_count <=1)){
												$tag_name = $value;
												// Optional arguments for the tag
												$args = array(
													'slug' => $value, // Customize the slug
													'description' => 'Description of the new tag', // Add a description
													'parent' => 0, // Set a parent tag (if applicable)
												);
												// Insert the tag
												$result = wp_insert_term($tag_name, $custom_post_name, $args);
												$get_post_id = $result['term_taxonomy_id'];
												$term_field_data[] = $result['term_id'];
											}
										}
										$wpdb->get_results("INSERT into {$wpdb->prefix}term_relationships (`object_id`,`term_taxonomy_id`) VALUES($pID,$get_post_id)");
											
									}
								}
								$field_arr = $term_field_data;
							}
							else {
							if(is_numeric($data_value)){
								$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE term_id = $data_value");
								if($id) // Check it exists or not
								$field_arr = $id;
							}
							else {
								$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE name = '$data_value' ");
								if($id){
									$field_arr = $id;
								}
								else{
									if(isset($addnew) && isset($custom_post_name)&& ($post_type_count <=1)){
										$tag_name = $data_value;
										// Optional arguments for the tag
										$args = array(
											'slug' => $data_value, // Customize the slug
											'description' => 'Description of the new tag', // Add a description
											'parent' => 0, // Set a parent tag (if applicable)
										);
										// Insert the tag
										$result = wp_insert_term($tag_name, $custom_post_name, $args);
										$get_post_id = $result['term_taxonomy_id'];
										$term_field_data = $result['term_id'];
									}
									$field_arr = $term_field_data;	
								}

								
							}
						}
							break;
						}	
					case 'taxonomy':
						{
							$term_field_data = array();
							if($is_multiple){
								$term_fd = explode(',',$data_value);								
								foreach($term_fd as $value){
									if(is_numeric($value)){
										$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE term_id = $value");
										if($id)
											$term_field_data[] = $id;
									}
									else {
										$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE name = '$value' ");
										if($id)
											$term_field_data[] = $id;
									}
								}
								$field_arr = $term_field_data;
							}
							else {
							if(is_numeric($data_value)){
								$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE term_id = $data_value");
								if($id) // Check it exists or not
								$field_arr = $id;
							}
							else {
								$id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}terms WHERE name = '$data_value' ");
								$field_arr = $id;
							}
						}
							break;
						}	
			default : 
			{
				if($is_multiple){
					$field_arr = explode(',',$data_value);
				}
				else {												
					$field_arr = $data_value;												
				}
				break;
			}
		}

		if(is_array($field_arr))
			$field_arr = serialize($field_arr);					
				
				$id = $wpdb->get_var("select * from $customtable where ID = $pID");
				if($id){									
					$wpdb->update($customtable,
					array($data_key => $field_arr),
					array("ID" => $pID));
				}													
				else {
				$wpdb->insert($customtable,
				array("ID" => $pID,
				"$data_key" => $field_arr),
				array('%d','%s'));
				}
			}
									
	}
	public function process_media($fvalue, $data_key, $line_number, $pID, $hash_key, $get_import_type, $templatekey, $gmode,$image_type, $is_video = false,$field_name=null,$clonecount=null) {
		$media_fd = explode(',', $fvalue);
		$indexs = 0;
	
		foreach ($media_fd as $data) {
			MetaBoxImport::$media_instance->store_image_ids($i=1);
			if (is_numeric($data)) {
				if($field_name == 'importFieldsCustomTable'){
					$media_arr[] = $data;
				}else{
					$media_arr = $data;
				}
			} else {
				if ($is_video) {
					$attachmentId = MetaBoxImport::$media_instance->media_handling($data, $pID);
					if ($attachmentId) {
						$media_arr = $data; // Storing $data directly for videos
					}
				} else {
					if($field_name == 'importFieldsCustomTable'){
						$attachmentId[] = MetaBoxImport::$media_instance->image_meta_table_entry($line_number, '', $pID, $data_key, $data, $hash_key, "metabox_", '', '', '', '', '', '', '', $indexs);
					}else{
						$attachmentId[] =  MetaBoxImport::$media_instance->image_meta_table_entry($line_number, '', $pID, $data_key, $data, $hash_key, "metabox_clone__".$clonecount."_", '', '', '', '', '', '', '', $indexs);
					}
					if ($attachmentId) {
						$media_arr = $attachmentId;
					}
				}
				$indexs++;
			}
		}
	
		return $media_arr;
	}
}
