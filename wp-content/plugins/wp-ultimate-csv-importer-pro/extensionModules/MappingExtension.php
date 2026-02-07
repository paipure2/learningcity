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

class MappingExtension{
	private static $instance = null,$extension_handler;
	private static $extension = [];
	private static $validatefile,$wpquery_export;

	public function __construct(){
		add_action('wp_ajax_mappingfields',array($this,'mapping_field_function'));
		add_action('wp_ajax_getfields',array($this,'get_fields'));
		add_action('wp_ajax_get_export_fields',array($this,'get_export_fields'));
		add_action('wp_ajax_templateinfo',array($this,'get_template_info'));
		add_action('wp_ajax_search_template',array($this,'search_template'));
		add_action('wp_ajax_get_post_titles', array($this, 'get_post_titles'));
	}

	public static function getInstance() {
		if (MappingExtension::$instance == null) {
			MappingExtension::$instance = new MappingExtension;
			MappingExtension::$validatefile = new ValidateFile;
			MappingExtension::$wpquery_export = new WPQueryExport;

			foreach(get_declared_classes() as $class){
				if(is_subclass_of($class, 'Smackcoders\WCSV\ExtensionHandler')){ 
					array_push(MappingExtension::$extension ,$class::getInstance() );	
				}
			}
			return MappingExtension::$instance;
		}
		return MappingExtension::$instance;
	}
	/**
	* Ajax Call 
	* Provides all Widget Fields for Mapping Section
	* @return array - mapping fields
	*/
	public function mapping_field_function(){

		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		$import_type = sanitize_text_field($_POST['Types']);
		$hash_key = sanitize_key($_POST['HashKey']);
		$mode = sanitize_text_field($_POST['Mode']);
		$media_type = '';
        if (isset($_POST['MediaType'])) {
            $media_type = sanitize_key($_POST['MediaType']);
        }
		$operation_mode = sanitize_text_field($_POST['OperationMode']);
	
		update_option("smack_operation_mode_".$hash_key, $operation_mode);
		global $wpdb;

		$response = [];
		$administratorRole = wp_get_current_user();
        $roles = $administratorRole->roles;
		if (in_array('administrator', $roles)) {
			$current_user_role = 'administrator';
		}
		$details = [];
		$info = [];

		$table_name = $wpdb->prefix."smackcsv_file_events";
		$wpdb->get_results("UPDATE $table_name SET mode ='$mode' WHERE hash_key = '$hash_key'");

		$get_result = $wpdb->get_results("SELECT file_name,total_rows FROM $table_name WHERE hash_key = '$hash_key' ");
		$filename = $get_result[0]->file_name;
		$total_rows = $get_result[0]->total_rows;
		$file_extension = pathinfo($filename, PATHINFO_EXTENSION);
		if(empty($file_extension)){
			$file_extension = 'xml';
		}
		if($file_extension == 'xlsx' || $file_extension == 'xls' || $file_extension == 'json') {
			$file_extension = 'csv';
		}
		$template_table_name = $wpdb->prefix."ultimate_csv_importer_mappingtemplate";
		$get_result = $wpdb->get_results("SELECT distinct(templatename) FROM $template_table_name WHERE csvname = '$filename' and module = '$import_type' and templatename != ' ' ");
		/* Provides Template Details, if Templates are stored*/
		if(!empty($get_result)) {
			foreach($get_result as $value){
				$template_name = $value->templatename;
				$get_temp_result = $wpdb->get_results("SELECT createdtime , module , mapping FROM $template_table_name WHERE templatename = '{$template_name}' ");
				$get_temp_result[0]=isset($get_temp_result[0])?$get_temp_result[0]:'';
				if(isset($get_temp_result[0]->mapping)){
					$mapping = $get_temp_result[0]->mapping;
				}
				//$mapped_elements = unserialize($mapping);
				$mapped_elements = array();
				$mapping_fields = unserialize($mapping);
                foreach ($mapping_fields as $key => $value) {

					if($key == "ATTRMETA"){
						$mapped_elements[$key] =array();
					}
					foreach($value as $map_key=>$map_value){
						if (is_int($map_key)) {
							if($key == "ATTRMETA"){
								if (is_int($map_key)) {
									$mapped_elements[$key] = array_merge($mapped_elements[$key], $map_value);
								}
								else{
									$mapped_elements[$key][$map_key]=$map_value;
								}

							}
							else{
								unset($value[$map_key]);
							}
							
						}else{
							$mapped_elements[$key][$map_key]=$map_value;
						}
                        
                	}    
           		}
				$matched_count = $this->get_matched_count($mapped_elements, $template_name);	
				$created_time = $get_temp_result[0]->createdtime;
				$module = $get_temp_result[0]->module;
				$details['template_name'] = $template_name;
				$details['created_time'] = $created_time;
				$details['module'] = $module;
				$details['count'] = $matched_count;
				array_push($info , $details);
			}
				
			$response['success'] = true;
			$response['show_template'] = true;
			$response['info'] = $info;
			$response['currentuser']=$current_user_role;
			$response['total_records'] = (int)$total_rows;
			echo wp_json_encode($response);
			wp_die();
		}
		/* Provides widget fields, if templates are not stored */
		else{
		
			$smackcsv_instance = SmackCSV::getInstance();
			$upload_dir = $smackcsv_instance->create_upload_dir();
			$response = [];
			if($file_extension == 'csv' || $file_extension == 'txt'){
				if (version_compare(PHP_VERSION, '8.1.0', '<')) {  // Only do this if PHP version is less than 8.1.0
					if (!ini_get("auto_detect_line_endings")) {  // If auto_detect_line_endings is not enabled
						ini_set("auto_detect_line_endings", true);  // Enable it to handle different line endings
					}
				}
				$info = [];
				if (($h = fopen($upload_dir.$hash_key.'/'.$hash_key, "r")) !== FALSE) 
				{
				// Convert each line into the local $data variable
				$delimiters = array( ',','\t',';','|',':','&nbsp');
				$file_path = $upload_dir . $hash_key . '/' . $hash_key;
				$delimiter = MappingExtension::$validatefile->getFileDelimiter($file_path, 5);
				$array_index = array_search($delimiter,$delimiters);
				if($array_index == 5){
					$delimiters[$array_index] = ' ';
				}
				if($delimiter == '\t'){
					$delimiter ='~';
					 $temp=$file_path.'temp';
					 if (($handles = fopen($temp, 'r')) !== FALSE){
						while (($data = fgetcsv($handles, 0, $delimiter)) !== FALSE)
						{
							$trimmed_array = array_map('trim', $data);
							array_push($info , $trimmed_array);	
							$exp_line = $info[0];
							$response['success'] = true;
							$response['show_template'] = false;
							$response['csv_fields'] = $exp_line;
							$response['currentuser']=$current_user_role;
							if(!empty($media_type) && $import_type == 'Media'){
								$value = $this->media_mapping_fields($import_type,$mode,$media_type);
							}else{
								$value = $this->mapping_fields($import_type);
							}
							$response['fields'] = $value;	
							$response['total_records'] = (int)$total_rows;				
							echo wp_json_encode($response);
							wp_die();  			  			
						}
					}

					fclose($handles);
				}
				else{
					while (($data = fgetcsv($h, 0, $delimiters[$array_index])) !== FALSE) 
					{	
		
						// Read the data from a single line
						$trimmed_array = array_map('trim', $data);
						array_push($info , $trimmed_array);	
						$exp_line = $info[0];
						$response['success'] = true;
						$response['show_template'] = false;
						$response['csv_fields'] = $exp_line;
						$response['currentuser']=$current_user_role;
						if(!empty($media_type) && $import_type == 'Media'){
							$value = $this->media_mapping_fields($import_type,$mode,$media_type);
						}else{
							$value = $this->mapping_fields($import_type);
						}
						$response['fields'] = $value;
						$response['total_records'] = (int)$total_rows;					
						echo wp_json_encode($response);
						wp_die();  			
					}	
					// Close the file
					fclose($h);
				}
				}
			}	
			if($file_extension == 'tsv'){
				if (version_compare(PHP_VERSION, '8.1.0', '<')) {  // Only do this if PHP version is less than 8.1.0
					if (!ini_get("auto_detect_line_endings")) {
						ini_set("auto_detect_line_endings", true);
					}
				}
				$info = [];
				if (($h = fopen($upload_dir.$hash_key.'/'.$hash_key, "r")) !== FALSE) 
				{
					
					$file_path = $upload_dir . $hash_key . '/' . $hash_key;
					$delimiter = MappingExtension::$validatefile->getFileDelimiter($file_path, 5);
					if($delimiter == '\t'){
						$hs = $upload_dir . $hash_key . '/' . $hash_key;
						$line =file($hs, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
						$data = explode("\t", $line[0]);
						$trimmed_array = array_map('trim', $data);
						array_push($info , $trimmed_array);	
						$exp_line = $info[0];
						$response['success'] = true;
						$response['show_template'] = false;
						$response['csv_fields'] = $exp_line;
						$response['currentuser']=$current_user_role;
						if(!empty($media_type) && $import_type == 'Media'){
							$value = $this->media_mapping_fields($import_type,$mode,$media_type);
						}else{
							$value = $this->mapping_fields($import_type);
						}
						$response['fields'] = $value;	
						$response['total_records'] = (int)$total_rows;				
						echo wp_json_encode($response);
						wp_die();					}	
				}
			}
			if($file_extension == 'xml'){
				$xml_class = new XmlHandler();
				$upload_dir_path = $upload_dir. $hash_key;
				if (!is_dir($upload_dir_path)) {
					wp_mkdir_p( $upload_dir_path);
				}
				chmod($upload_dir_path, 0777);   
				$path = $upload_dir . $hash_key . '/' . $hash_key;   
				$xml = simplexml_load_file($path);
				$xml_arr = json_decode( json_encode($xml) , 1);
			
				foreach($xml->children() as $child){   
					$child_name = $child->getName();    
				}
				$parse_xml = $xml_class->parse_xmls($hash_key);
				$i = 0;
				$headers = [];
				foreach($parse_xml as $xml_key => $xml_value){
					if(is_array($xml_value)){
						foreach ($xml_value as $e_key => $e_value){
							$headers[$i] = $e_value['name'];
							$i++;
						}
					}
				}
				$response['success'] = true;
				$response['show_template'] = false;
				$response['csv_fields'] = $headers;
				if(!empty($media_type) && $import_type == 'Media'){
					$value = $this->media_mapping_fields($import_type,$mode,$media_type);
				}else{
					$value = $this->mapping_fields($import_type);
				}
				$response['currentuser']=$current_user_role;
				$response['fields'] = $value;
				$response['total_records'] = (int)$total_rows;
				echo wp_json_encode($response);
				wp_die();  			
			}
		}
	}
	public function media_mapping_fields($import_type,$mode =null,$media_type=null){
		MappingExtension::$extension_handler = new ExtensionHandler();
		$support_instance = [];
		if($import_type == 'Media') {
			if($media_type == 'local'){
				$wordpressfields = array(
					'File Name' => 'file_name',
					'Caption' => 'caption',
					'Alt text' => 'alt_text',
					'Desctiption' => 'description',
					'Title' => 'title',
					'Media ID' => 'media_id',
				);
				if(trim($mode) == 'Insert'){
					unset($wordpressfields['Media ID']);
				}
			}else{
				$wordpressfields = array(
					'Post ID' => 'post_id',
					'Media ID' => 'media_id',
					'Actual URL' => 'actual_url',
					'File Name' => 'file_name',
					'Title' => 'title',
					'Caption' => 'caption',
					'Alt text' => 'alt_text',
					'Desctiption' => 'description'		
				);
				if(trim($mode) == 'Insert'){
					unset($wordpressfields['Post ID']);
					unset($wordpressfields['Media ID']);
					
				}
			}
			
		}
		$wordpress_value = MappingExtension::$extension_handler->convert_static_fields_to_array($wordpressfields);
		$response[]['core_fields'] = $wordpress_value ;
		return $response;
	}

	public function mapping_fields($import_type, $process_type = null){		
		$support_instance = [];
		$value = [];
		for($i = 0 ; $i < count(MappingExtension::$extension) ; $i++){
			$extension_instance = MappingExtension::$extension[$i];
			if($extension_instance->extensionSupportedImportType($import_type)){
				array_push($support_instance , $extension_instance);		
			}	
		}
		for($i = 0 ;$i < count($support_instance) ; $i++){	
			$supporting_instance = $support_instance[$i];
			$fields = $supporting_instance->processExtension($import_type, $process_type);
			if($process_type == 'Export'){
				if(is_array($fields)){
					if(array_key_exists('nextgen_gallery_fields',$fields)){
						continue;
					}
					else
						array_push($value , $fields);
				}
			}
			else{
				array_push($value , $fields);	
			}										
		}
		update_option('mapping_fields', $value);
		return $value;
	}

	/**
	* Ajax Call 
	* Provides all Widget Fields for Export Section
	* @return array - mapping fields
	*/
	public function get_export_fields() {
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		$import_type = sanitize_text_field($_POST['Types']);
		$response = [];

		$query_data = isset($_POST['query_data']) ? sanitize_text_field($_POST['query_data']) : '';
		$type       = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

		if (empty($import_type) && $type === 'post') {
			$import_type = MappingExtension::$wpquery_export->getposttype($query_data);
		} elseif (empty($import_type) && $type === 'user') {
			$import_type = 'Users';
		} elseif (empty($import_type) && $type === 'comment') {
			$import_type = 'Comments';
		}

		$value           = $this->mapping_fields($import_type, 'Export');
		$categories_list = $this->get_categories_list($import_type);

		$response['success']    = true;
		$response['fields']     = $value;
		$response['cat_fields'] = $categories_list;

		global $wpdb;
		$results = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}usermeta WHERE meta_key = '{$wpdb->prefix}capabilities'");
		$roles   = [];

		foreach ($results as $row) {
			$capabilities = maybe_unserialize($row->meta_value);
			if (is_array($capabilities)) {
				$filtered = array_filter(array_keys($capabilities), function($key) {
					return !preg_match('/^(wpcf_|wpml_|translate)/', $key);
				});

				if (!empty($filtered)) {
					$roles[] = $filtered[0];
				}
			}
		}

		$unique_roles            = array_unique($roles);
		$response['user_roles']  = array_values($unique_roles);

		if (function_exists('wc_get_products')) {
			$products = \wc_get_products([
				'limit'  => -1,
				'status' => 'publish',
				'return' => 'ids',
				'type'   => ['simple', 'variable', 'grouped', 'external'],
			]);
		} else {
			$products = [];
		}

		if (class_exists('\WC_Payment_Gateways')) {
			$gateways = \WC_Payment_Gateways::instance()->get_available_payment_gateways();
		} else {
			$gateways = [];
		}

		$payment = [];
		foreach ($gateways as $gateway) {
			$payment[] = $gateway->id;
		}

		$titles = [];
		if (function_exists('wc_get_product')) {
			foreach ($products as $product_id) {
				$product = \wc_get_product($product_id);
				if ($product) {
					$titles[] = $product->get_name();
				}
			}
		}

		$response['product_titles']  = $titles;
		$response['payment_methods'] = $payment;

		echo wp_json_encode($response);
		wp_die();
	}

	/**
	* Provides all Widget Fields for Export Section
	* @return array - categories list
	*/

	public function get_categories_list($import_type){
			$cat_fields = [];
		if($import_type == 'WooCommerce' || strpos($import_type, 'WooCommerce') !== false){
			$categories = get_terms( array('taxonomy'   => 'product_cat','hide_empty' => false,) );
			if ( ! empty( $categories ) ) {
				foreach ( $categories as $category ) {
					$cat_fields[] = $category->name;
				}
			}
		}
		elseif($import_type == 'lp_course'){
			$categories = get_categories( array('taxonomy'   => 'course_category','hide_empty' => false,) );
			if (!empty($categories)) {
				foreach ($categories as $category) {
					$cat_fields[] = $category->name;
				}
			}
		}
		elseif ($import_type == 'estate_property') {
			$taxonomies = [
				'property_category',
				'property_action_category',
				'property_city',
				'property_area',
				'property_features',
				'property_status',
			];
		
			foreach ($taxonomies as $taxonomy) {
				$categories = get_categories([
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				]);
		
				if (!empty($categories)) {
					foreach ($categories as $category) {
						$cat_fields[] = $category->name;
					}
				}
			}
		}
		elseif ($import_type == 'estate_agent') {
			$cat_fields[] ='';
		}
		 else{
			$categories = get_categories( array('taxonomy'   => 'category','hide_empty' => false,) );
			if (!empty($categories)) {
				foreach ($categories as $category) {
					$cat_fields[] = $category->name;
				}
			}
		}
			return $cat_fields;
	}

	public function get_categories_list_IDs($import_type) {
		
		if ($import_type == 'WooCommerce' || strpos($import_type, 'WooCommerce') !== false) {
			$categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
		} else {
			$categories = get_categories(array('taxonomy' => 'category', 'hide_empty' => false));
		}
		
		if (!empty($categories)) {
			foreach ($categories as $category) {
				$cat_fields[] = $category->term_id; // Store category ID instead of name
			}
		}
		
		return $cat_fields;
	}

	public function get_category_ids_from_names($category_names, $import_type): array {
		$cat_ids = [];
		if($import_type == 'WooCommerce' || strpos($import_type, 'WooCommerce') !== false){
			$taxonomy = 'product_cat';
		}else{
			$taxonomy = 'category';
		}
		foreach ($category_names as $category_name) {
			$term = get_term_by('name', $category_name, $taxonomy);
			if ($term) {
				$cat_ids[] = $term->term_id;
			}
		}
	
		return $cat_ids;
	}

	public function CustomPostCheck($post_type){
        $all_post_types = get_post_types([], 'names'); // Retrieve all post types
        if (in_array($post_type, $all_post_types)) {
			return true;
		}
		return false;
	}
	
	public function get_post_titles() {
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey'); // Ensure nonce security
	
		$response = [];
	
		// Get the post type from the AJAX request
		$post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post'; // Default to 'post' if not provided
	
		// Fetch post titles based on the post type
		$posts = get_posts([
			'post_type' => $post_type, 
			'posts_per_page' => -1, 
			'post_status' => 'publish'
		]);
	
		// Check if posts are found
		if (!empty($posts)) {
			// Extract post titles
			$post_titles = [];
			foreach ($posts as $post) {
				$post_titles[] = $post->post_title;
			}
	
			$response['success'] = true;
			$response['post_titles'] = $post_titles;
		} else {
			$response['success'] = false;
			$response['message'] = 'No posts found for the selected post type.';
		}
	
		echo wp_json_encode($response);
		wp_die(); // Properly terminate AJAX request
	}

	/**
	* Provides all Widget Fields for Export Section
	* @return array - mapping fields
	*/
	public function get_fields($module){ 
		$import_type = $module;
		$response = [];

		$value = $this->mapping_fields($import_type, 'Export');
		$response['fields'] = $value;
		return $response;
	}


	/**
	* Ajax Call 
	* Provides mapped fields from Template
	* @return array - already mapped fields
	*/
	public function get_template_info(){
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		global $wpdb;	
		$template_name = isset($_POST['TemplateName']) ? sanitize_text_field($_POST['TemplateName']) : '';
		$import_type = sanitize_text_field($_POST['Types']);
		$hash_key = sanitize_key($_POST['HashKey']);
		$get_key = get_option('openAI_settings');
		$response = [];
		$file_name = '';
		$mode = isset($_POST['Mode']) ? sanitize_text_field($_POST['Mode']) : '';
		$media_type = '';
        if (isset($_POST['MediaType'])) {
            $media_type = sanitize_key($_POST['MediaType']);
        }
		$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
		$table_name = $wpdb->prefix."smackcsv_file_events";
		$response['success'] = true;
		$response['get_key'] = $get_key;
		$get_total_rows = $wpdb->get_results("SELECT total_rows FROM $table_name WHERE hash_key = '$hash_key' ");
		$total_rows = $get_total_rows[0]->total_rows;
		$administratorRole = wp_get_current_user();
        $roles = $administratorRole->roles;
		if (in_array('administrator', $roles)) {
			$current_user_role = 'administrator';
		}
		if(!empty($template_name)){
			if($import_type == 'Media'){
				$get_detail   = $wpdb->get_results( "SELECT mapping ,mapping_filter, csvname , mapping_type FROM $template_table_name WHERE templatename = '$template_name' AND module = 'Media' " );
			}else{
				$get_detail   = $wpdb->get_results( "SELECT mapping ,mapping_filter, csvname , mapping_type FROM $template_table_name WHERE templatename = '$template_name' " );
			}
			if(!empty($get_detail)){
				$get_hash_key = isset($get_detail[0]->hash_key) ? $get_detail[0]->hash_key : null;
				$get_mapping = isset($get_detail[0]->mapping) ? $get_detail[0]->mapping : null;
				$get_mapping_filter = isset($get_detail[0]->mapping_filter) ? $get_detail[0]->mapping_filter : null;
				$mapping_type = isset($get_detail[0]->mapping_type) ? $get_detail[0]->mapping_type : null;
				$file_name = isset($get_detail[0]->csvname) ? $get_detail[0]->csvname : null;
				$file_type = $file_name ? pathinfo($file_name, PATHINFO_EXTENSION) : null;
			}

			if(empty($get_hash_key) && !empty($template_name)){
				$wpdb->update($template_table_name,array('eventkey' => $hash_key),array('templatename' => trim($template_name)));
			}
			$hash_key_array = $wpdb->get_results( "SELECT hash_key FROM $table_name WHERE file_name = '$file_name' ORDER BY id DESC");
			$hash_key = $hash_key_array[0]->hash_key;
			$result = unserialize($get_mapping);	
			$mapping_filter = unserialize($get_mapping_filter);		
			foreach($result as $result_key => $result_val){
				if($result_key == 'ATTRMETA'){
					foreach($result_val as $res => $rest){
						if(is_int($res)){
							$result[$result_key] =array();
						}
					}
					foreach($result_val as $res_key=>$res_value){
						if (is_int($res_key) && $result_key =="ATTRMETA") {
							//if($key == "ATTRMETA"){
								$result[$result_key] = array_merge($result[$result_key], $res_value);

							//}
						}
					}
					
				}
			}

			$response['already_mapped'] = $result;
			$response['currentuser']=$current_user_role;
			$response['mapping_type'] = $mapping_type;
			$response['mapping_filter'] = $mapping_filter;
			$response['file_type'] = $file_type;
			$response['hash_key'] = $hash_key;
		}
		if(empty($hash_key)){
			if($import_type == 'Media'){
				$get_detail   = $wpdb->get_results( "SELECT eventKey FROM $template_table_name WHERE templatename = '$template_name' AND module = 'Media' " );
			}else{
				$get_detail   = $wpdb->get_results( "SELECT eventKey FROM $template_table_name WHERE templatename = '$template_name' " );
			}
			
			$hash_key = $get_detail[0]->eventKey;
		}
		$get_result = $wpdb->get_results("SELECT file_name FROM $table_name WHERE hash_key = '$hash_key' ");
		$filename = $get_result[0]->file_name;
		
		if(empty($filename)){
			$get_result = $wpdb->get_results("SELECT csvname FROM $template_table_name WHERE eventKey = '$hash_key' ");
			$filename = $get_result[0]->csvname;	
		}
		$file_extension = pathinfo($filename, PATHINFO_EXTENSION);
		if($file_extension == 'xlsx' ||  $file_extension == 'xls' || $file_extension == 'json'){
			$file_extension = 'csv';
		}
		if(empty($file_extension)){
			$file_extension = 'xml';
		}
		
		$smackcsv_instance = SmackCSV::getInstance();
		$upload_dir = $smackcsv_instance->create_upload_dir();

		if($file_extension == 'csv' || $file_extension == 'txt'){
			if (version_compare(PHP_VERSION, '8.1.0', '<')) {  // Only do this if PHP version is less than 8.1.0
				if (!ini_get("auto_detect_line_endings")) {  // If auto_detect_line_endings is not enabled
					ini_set("auto_detect_line_endings", true);  // Enable it to handle different line endings
				}
			}
			$info = [];
			if (($h = fopen($upload_dir.$hash_key.'/'.$hash_key, "r")) !== FALSE) 
			{
			// Convert each line into the local $data variable
			$delimiters = array( ',','\t',';','|',':','&nbsp');
			$file_path = $upload_dir . $hash_key . '/' . $hash_key;
			$delimiter = MappingExtension::$validatefile->getFileDelimiter($file_path, 5);
			$array_index = array_search($delimiter,$delimiters);
			if($array_index == 5){
				$delimiters[$array_index] = ' ';
			}
			if($delimiter == '\t'){
				$delimiter ='~';
				 $temp=$file_path.'temp';
				 if (($handles = fopen($temp, 'r')) !== FALSE){
					while (($data = fgetcsv($handles, 0, $delimiter)) !== FALSE)
					{
						// Read the data from a single line
						$trimmed_array = array_map('trim', $data);
						array_push($info , $trimmed_array);
						$exp_line = $info[0];									
						
						$response['csv_fields'] = $exp_line;					
						//$value = $this->mapping_fields($import_type);
						
						if(!empty($media_type) && $import_type == 'Media'){
							$value = $this->media_mapping_fields($import_type,$mode,$media_type);
						}else{
							$value = $this->mapping_fields($import_type);
						}


						$response['fields'] = $value;	
						$response['total_records'] = (int)$total_rows;				
						echo wp_json_encode($response);
						wp_die();  	
					}
				}
			}
			else{
				while (($data = fgetcsv($h, 0, $delimiters[$array_index])) !== FALSE) 
				{		
					// Read the data from a single line
					$trimmed_array = array_map('trim', $data);
					array_push($info , $trimmed_array);
					$exp_line = $info[0];									
					
					$response['csv_fields'] = $exp_line;					
					if(!empty($media_type) && $import_type == 'Media'){
						$value = $this->media_mapping_fields($import_type,$mode,$media_type);
					}else{
						$value = $this->mapping_fields($import_type);
					}

					$response['fields'] = $value;	
					$response['total_records'] = (int)$total_rows;				
					echo wp_json_encode($response);
					wp_die();  			
				}	
				// Close the file
				fclose($h);
			}
			}
			
		}
		if($file_extension == 'tsv'){
			if (version_compare(PHP_VERSION, '8.1.0', '<')) {  // Only do this if PHP version is less than 8.1.0
				if (!ini_get("auto_detect_line_endings")) {  // If auto_detect_line_endings is not enabled
					ini_set("auto_detect_line_endings", true);  // Enable it to handle different line endings
				}
			}
			$info = [];
			if (($h = fopen($upload_dir.$hash_key.'/'.$hash_key, "r")) !== FALSE) 
			{
				$file_path = $upload_dir . $hash_key . '/' . $hash_key;
				$delimiter = MappingExtension::$validatefile->getFileDelimiter($file_path, 5);
				if($delimiter == '\t'){
					$hs = $upload_dir . $hash_key . '/' . $hash_key;
					$line =file($hs, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
					$data = explode("\t", $line[0]); 
					$trimmed_array = array_map('trim', $data);
					array_push($info , $trimmed_array);
					$exp_line = $info[0];		
					$response['csv_fields'] = $exp_line;					
					if(!empty($media_type) && $import_type == 'Media'){
						$value = $this->media_mapping_fields($import_type,$mode,$media_type);
					}else{
						$value = $this->mapping_fields($import_type);
					}

					$response['fields'] = $value;	
					$response['total_records'] = (int)$total_rows;				
					echo wp_json_encode($response);
					wp_die();
						
				}
			}
		}

		if($file_extension == 'xml'){
			$xml_class = new XmlHandler();
			
			$upload_dir_path = $upload_dir. $hash_key;
			if (!is_dir($upload_dir_path)) {
				wp_mkdir_p( $upload_dir_path);
			}
			chmod($upload_dir_path, 0777);   
			$path = $upload_dir . $hash_key . '/' . $hash_key; 
			$xml = simplexml_load_file($path);
			$xml_arr = json_decode( json_encode($xml) , 1);	
			foreach($xml->children() as $child){   
				$child_name = $child->getName();    
			}
			$parse_xml = $xml_class->parse_xmls($hash_key);
			$i = 0;
			$headers = [];
			foreach($parse_xml as $xml_key => $xml_value){
				if(is_array($xml_value)){
					foreach ($xml_value as $e_key => $e_value){
						$headers[$i] = $e_value['name'];
						$i++;
					}
				}
			}
			$response['show_template'] = false;
			$response['csv_fields'] = $headers;
			if(!empty($media_type) && $import_type == 'Media'){
				$value = $this->media_mapping_fields($import_type,$mode,$media_type);
			}else{
				$value = $this->mapping_fields($import_type);
			}
			$response['fields'] = $value;
			$response['total_records'] = (int)$total_rows;
			echo wp_json_encode($response);
			
			wp_die();  			
		}
	}

	/**
	* Provides mapped fields count from template
	* @param array $mappingList
	* @return int - count
	*/
	public function get_matched_count($mappingList, $templateName = null){
		$count = 0;

		//added
		$plugins_array = array(
			'ACF' => 'advanced-custom-fields/acf.php',
			'GF' => 'advanced-custom-fields-pro/acf.php',
			'RF' => 'advanced-custom-fields-pro/acf.php',
			'FC' => 'advanced-custom-fields-pro/acf.php',
			'ACFIMAGEMETA' => 'advanced-custom-fields-pro/acf.php',
			'TYPES' => 'types/wpcf.php',
			'TYPESIMAGEMETA' => 'types/wpcf.php',
			'PODS' => 'pods/init.php',
			'PODSIMAGEMETA' => 'pods/init.php',
			'CFS' => 'custom-field-suite/cfs.php',
			'AIOSEO' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
			'YOASTSEO' => 'wordpress-seo/wp-seo.php',
			'RANKMATH' => 'seo-by-rank-math/rank-math.php',
			'WPMEMBERS' => 'wp-members/wp-members.php',
			'ECOMMETA' => 'woocommerce/woocommerce.php',
			'BUNDLEMETA' => 'woocommerce-product-bundles/woocommerce-product-bundles.php',
			'LISTINGMETA' => 'woocommerce-product-bundles/woocommerce-product-bundles.php',
			'PRODUCTIMAGEMETA' => 'woocommerce/woocommerce.php',
			'ORDERMETA' => 'woocommerce/woocommerce.php',
			'COUPONMETA' => 'woocommerce/woocommerce.php',
			'REFUNDMETA' => 'woocommerce/woocommerce.php',
			'WPECOMMETA' => 'wp-e-commerce-custom-fields/custom-fields.php',
			'EVENTS' => 'events-manager/events-manager.php',
			'NEXTGEN' => 'nextgen-gallery/nggallery.php',
			'WPML' => 'wpml-multilingual-cms/sitepress.php',
			'CMB2' => 'cmb2/init.php',
			'JE' => 'jet-engine/jet-engine.php',
			'JERF' => 'jet-engine/jet-engine.php',
			'JECPT' => 'jet-engine/jet-engine.php',
			'JECPTRF' => 'jet-engine/jet-engine.php',
			'JECCT' => 'jet-engine/jet-engine.php',
			'JECCTRF' => 'jet-engine/jet-engine.php',
			'JETAX' => 'jet-engine/jet-engine.php',
			'JETAXRF' => 'jet-engine/jet-engine.php',
			'JEREL' => 'jet-engine/jet-engine.php',
			'LPCOURSE' => 'learnpress/learnpress.php',
			'LPCURRICULUM' => 'learnpress/learnpress.php',
			'LPLESSON' => 'learnpress/learnpress.php',
			'LPQUIZ' => 'learnpress/learnpress.php',
			'LPQUESTION' => 'learnpress/learnpress.php',
			'LPORDER' => 'learnpress/learnpress.php',
			'LIFTERLESSON' => 'lifterlms/lifterlms.php',
			'LIFTERCOURSE' => 'lifterlms/lifterlms.php',
			'LIFTERCOUPON' => 'lifterlms/lifterlms.php',
			'LIFTERQUIZ' => 'lifterlms/lifterlms.php',
			'STMCOURSE' => 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php',
			'STMCURRICULUM' => 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php',
			'STMLESSON' => 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php',
			'STMQUIZ' => 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php',
			'STMQUESTION' => 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php',
			'STMORDER' => 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php',
			'FORUM' => 'bbpress/bbpress.php',
			'TOPIC' => 'bbpress/bbpress.php',
			'REPLY' => 'bbpress/bbpress.php',
			'POLYLANG' => 'polylang/polylang.php',
		);
		if(is_plugin_active('advanced-custom-fields-pro/acf.php'))
		{
			$plugins_array['GF'] = 'advanced-custom-fields-pro/acf.php';
		}
		if(is_plugin_active('advanced-custom-fields/acf.php'))
		{
			$plugins_array['GF'] = 'advanced-custom-fields/acf.php';
		}
		else{
			if(is_plugin_active('secure-custom-fields/secure-custom-fields.php')){
				$plugins_array['GF'] = 'secure-custom-fields/secure-custom-fields.php';	
			}
		}
		foreach ($mappingList as $templatename => $group) {				
			//added condition to check whether mapped fields plugin is active or not, if not remove it from mapping
			if(array_key_exists($templatename, $plugins_array)){
				if($templatename == 'WPML'){
					if(!is_plugin_active($plugins_array[$templatename]) && !is_plugin_active('sitepress-multilingual-cms/sitepress.php')){
						unset($mappingList[$templatename]);
						continue;
					}
				}
				elseif($templatename == 'POLYLANG'){
					if(!is_plugin_active($plugins_array[$templatename]) && !is_plugin_active('polylang-pro/polylang.php')){						
						unset($mappingList[$templatename]);
						continue;
					}
				}
				elseif($templatename == 'RF' ){					
					if(!is_plugin_active($plugins_array[$templatename]) && (!is_plugin_active('advanced-custom-fields/acf.php') || !is_plugin_active('secure-custom-fields/secure-custom-fields.php'))){						
						unset($mappingList[$templatename]);
						continue;
					}
					elseif((is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('secure-custom-fields/secure-custom-fields.php')) && !is_plugin_active('acf-repeater/acf-repeater.php')) {						
						unset($mappingList[$templatename]);
						continue;
					}
				}
				elseif($templatename == 'ACF'){
					if((!is_plugin_active($plugins_array[$templatename]) && !is_plugin_active('secure-custom-fields/secure-custom-fields.php'))&& !is_plugin_active('advanced-custom-fields-pro/acf.php')){
						unset($mappingList[$templatename]);
						continue;
					}
				}
				elseif($templatename == 'AIOSEO'){
					if(!is_plugin_active($plugins_array[$templatename]) && !is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')){
						unset($mappingList[$templatename]);
						continue;
					}
				}
				elseif($templatename == 'RANKMATH'){
					if(!is_plugin_active($plugins_array[$templatename]) && !is_plugin_active('seo-by-rank-math-pro/rank-math-pro.php')){
						unset($mappingList[$templatename]);
						continue;
					}
				}
				elseif($templatename == 'YOASTSEO'){
					if(!is_plugin_active($plugins_array[$templatename]) && !is_plugin_active('wordpress-seo-premium/wp-seo-premium.php')){
						unset($mappingList[$templatename]);
						continue;
					}
				}
				elseif(!is_plugin_active($plugins_array[$templatename])){
					unset($mappingList[$templatename]);
					continue;
				}				
			}

			$count += count(array_filter($group));
		}
	
		//added - updated mapping in template table
		if(!empty($templateName)){
			global $wpdb;
			$template_table_name = $wpdb->prefix."ultimate_csv_importer_mappingtemplate";
			$mapping_fields = serialize($mappingList);
			$mapping_fields = $wpdb->_real_escape($mapping_fields);
			 $wpdb->get_results("UPDATE $template_table_name SET mapping ='$mapping_fields' WHERE templatename = '$templateName' ");
		}

		return $count;	
	}
	
	

	/**
	* Ajax Call 
	* Searches Templates based on Template Name and Dates
	* @return array - Template Details
	*/
	public function search_template(){
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		global $wpdb;
		$template_name = sanitize_text_field($_POST['TemplateName']);
		$start_date = $_POST['FromDate'];
		$end_date = $_POST['ToDate'];
		$filename = sanitize_text_field($_POST['filename']);
		$module = sanitize_text_field($_POST['module']);
		$info = [];
		$details = [];
		$startDate = $start_date . ' 00:00:00';
		$endDate = $end_date . ' 23:59:59';
		$filterclause = '';
		if ( $start_date != 'Invalid date' && $end_date != 'Invalid date'){
			$filterclause .= "createdtime between '$startDate' and '$endDate' and";
			$filterclause = substr($filterclause, 0, -3);
		} else {
			if ( $start_date != 'Invalid date'){
				$filterclause .= "createdtime >= '$startDate' and";
				$filterclause = substr($filterclause, 0, -3);
			} else {
				if ( $end_date != 'Invalid date'){
					$filterclause .= "createdtime <= '$endDate' and";
					$filterclause = substr($filterclause, 0, -3);
				}
			}
		}
		
		if (!empty($template_name) && $start_date != 'Invalid date' && $end_date != 'Invalid date'){
			$filterclause .= " and templatename = '$template_name'";
		}
		if (!empty($template_name) && $start_date == 'Invalid date' && $end_date == 'Invalid date'){
			$filterclause .= " templatename = '$template_name'";
		}
		if (!empty($filterclause)) {
			$filterclause = "where $filterclause";
		}
		
		$templateList = $wpdb->get_results("select * from {$wpdb->prefix}ultimate_csv_importer_mappingtemplate ".$filterclause." and csvname = '".$filename ."' ");
		
		if(!empty($templateList)){
			foreach($templateList as $value){
				$templateName = $value->templatename;
		
				if(!empty($templateName)){					
					$details['template_name'] = $templateName;
					$details['module'] = $value->module;
					$details['created_time'] = $value->createdtime;
					$mapping = $value->mapping;
					$map = unserialize($mapping);
					$count = $this->get_matched_count($map);
					$details['count'] = $count;	
					array_push($info , $details);
				}	
			}
			$response['success'] = true;
			$response['info'] = $info;
		}else{
			$response['success'] = false;
			$response['message'] = "Templates not found";
		}
		echo wp_json_encode($response);
		wp_die(); 	
	}
	

}	