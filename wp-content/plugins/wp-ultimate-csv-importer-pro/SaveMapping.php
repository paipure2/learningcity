<?php

/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;

if (!defined('ABSPATH'))
	exit; // Exit if accessed directly

$import_extensions = glob(__DIR__ . '/importExtensions/*.php');

foreach ($import_extensions as $import_extension_value) {
	require_once($import_extension_value);
}

class SaveMapping
{
	public static $instance = null, $validatefile, $media_instance;
	public static $failed_images_instance = null;
	public static $smackcsv_instance = null;
	public static $core = null, $nextgen_instance;
	public $media_log;
    private $categoryList = [];
    private $isCategory;

	public function __construct()
	{
		add_action('wp_ajax_saveTemplateFields', array($this, 'save_template_fields'));
		add_action('wp_ajax_saveMappedFields', array($this, 'check_templatename_exists'));
		add_action('wp_ajax_StartImport', array($this, 'background_starts_function'));
		add_action('wp_ajax_GetProgress', array($this, 'import_detail_function'));
		add_action('wp_ajax_get_total_records', array($this, 'send_total_records'));
		add_action('wp_ajax_ImportState', array($this, 'import_state_function'));
		add_action('wp_ajax_ImportStop', array($this, 'import_stop_function'));
		add_action('wp_ajax_checkmain_mode', array($this, 'checkmain_mode'));
		add_action('wp_ajax_disable_main_mode', array($this, 'disable_main_mode'));
		add_action('wp_ajax_bulk_file_import', array($this, 'bulk_file_import_function'));
		add_action('wp_ajax_bulk_import', array($this, 'bulk_import'));
		add_action('wp_ajax_PauseImport', array($this, 'pause_import'));
		add_action('wp_ajax_ResumeImport', array($this, 'resume_import'));

		add_action('wp_ajax_send_error_status', array($this, 'send_error_status'));
		add_action('smackcsv_image_schedule_hook', array($this, 'smackcsv_image_schedule_function'), 10, 2);
	}

	public static function getInstance()
	{

		if (SaveMapping::$instance == null) {
			SaveMapping::$instance = new SaveMapping;
			SaveMapping::$smackcsv_instance = SmackCSV::getInstance();
			SaveMapping::$validatefile = new ValidateFile;
			SaveMapping::$failed_images_instance = FailedImagesUpdate::getInstance();
			SaveMapping::$nextgen_instance = new NextGenGalleryImport;
			return SaveMapping::$instance;
		}
		return SaveMapping::$instance;
	}


	public static function disable_main_mode()
	{
		$disable_option = sanitize_text_field($_POST['option']);
		delete_option($disable_option);
		$result['success'] = true;
		echo wp_json_encode($result);
		wp_die();
	}

	public static function checkmain_mode()
	{
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		$ucisettings = get_option('sm_uci_pro_settings');
		if (isset($ucisettings['enable_main_mode']) && $ucisettings['enable_main_mode'] == 'true') {
			$result['success'] = true;
		} else {
			$result['success'] = false;
		}
		echo wp_json_encode($result);
		wp_die();
	}

	public function save_template_fields()
	{
		global $wpdb;
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		$file_path = sanitize_text_field($_POST['file_path']);
		$extension = sanitize_text_field($_POST['extension']);
		// $file_path = '/var/www/html/media-free/wp-content/uploads/smack_uci_uploads/imports/abf51501fcbc6e5ebc7ac2615506f2ac/abf51501fcbc6e5ebc7ac2615506f2ac';
		// $extension = 'csv';


    // Check file extension
    if ($extension !== 'csv') {
		$response['success'] = false;
		wp_send_json_error(array('message' => 'Invalid file extension'));
        wp_die();
    }

    // Open the file for reading
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        // Get header row
        $header = fgetcsv($handle, 1000, ',');

        // Map indices for relevant columns
        $template_name_index = array_search('template_name', $header);
        $module_index = array_search('module', $header);
        $csv_name_index = array_search('csv_name', $header);

        if ($template_name_index === false || $module_index === false || $csv_name_index === false) {
			$response['success'] = false;
             wp_send_json_error(array('message' => 'CSV headers do not match the expected format.'));
        }
        // Initialize array to store mappings
        $mappings = [];

        // Loop through each row in the CSV
        while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $template_name = $row[$template_name_index];
            $module = $row[$module_index];
            $csv_name = $row[$csv_name_index];

            // Prepare an empty array for mapping data
            $mapping_data = [];

            // Iterate through the rest of the columns for mapping data (starting from the 4th column)
            for ($i = 3; $i < count($row); $i++) {
                $map_parts = explode('->', $header[$i]);
                if (count($map_parts) == 2) {
                    $section = trim($map_parts[0]); // e.g., "CORE"
                    $field = trim($map_parts[1]);  // e.g., "post_title"
                    if (!isset($mapping_data[$section])) {
                        $mapping_data[$section] = [];
                    }
                    $mapping_data[$section][$field] = $row[$i]; 
                }
            }
            // Serialize the mapping data
            $serialized_mapping = maybe_serialize($mapping_data);
            $mappings[] = ['templatename' => $template_name,'module' => $module,'csvname' => $csv_name,'mapping' => $serialized_mapping,'mapping_type' => 'mapping-section'];
        }
        fclose($handle);

        // Insert into the database
        foreach ($mappings as $mapping) {
            $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ultimate_csv_importer_mappingtemplate WHERE templatename = %s",$mapping['templatename'])); 
            if ($existing > 0) {
				$response['success'] = false;
                 wp_send_json_error(array('message' => 'Template already exists'));
            }else{
                $wpdb->insert(
                    "{$wpdb->prefix}ultimate_csv_importer_mappingtemplate",
                    [
                        'templatename' => $mapping['templatename'],
                        'module' => $mapping['module'],
                        'csvname' => $mapping['csvname'],
                        'mapping' => $mapping['mapping'],
                        'mapping_type' => $mapping['mapping_type']
                    ],
                    ['%s', '%s', '%s', '%s', '%s']
                );
				$response['success'] = true;
				$response['message'] = 'Template inserted successfully.';
            }
			
        }
    } else {
		$response['success'] = false;
		wp_send_json_error(array('message' => 'Unable to open the file'));
    }
	echo wp_json_encode($response);
	wp_die();

	}
	/**
	 * Checks whether Template name already exists.
	 */
	public function check_templatename_exists()
	{
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		$use_template = sanitize_text_field($_POST['UseTemplateState']);
		$template_name = sanitize_text_field($_POST['TemplateName']);
		$hash_key = sanitize_key($_POST['HashKey']);
		$operation_mode = get_option("smack_operation_mode_" . $hash_key);

		$response = [];

		if ($use_template === 'true') {
			$response['success'] = $this->save_temp_fields();
		} else {
			global $wpdb;
			$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
			$get_template_names = $wpdb->get_results("SELECT templatename FROM $template_table_name");
			if (!empty($get_template_names)) {

				foreach ($get_template_names as $temp_names) {
					$inserted_temp_names[] = $temp_names->templatename;
				}
				if (in_array($template_name, $inserted_temp_names) && $template_name != '' && $operation_mode !== 'simpleMode') {
					$response['success'] = false;
					$response['message'] = 'Template Name Already Exists';
				} else {
					$response = $this->save_fields_function();
				}
			} else {
				$response = $this->save_fields_function();

			}
		}
		echo wp_json_encode($response);
		wp_die();
	}

	public function pause_import()
	{
		global $wpdb;
		$page_number = get_option('sm_bulk_import_page_number');
		update_option('sm_bulk_import_page_number', $page_number - 1);

		$response = [];
		$hash_key = sanitize_key($_POST['HashKey']);
		$log_table_name = $wpdb->prefix . "import_detail_log";
		$wpdb->get_results("UPDATE $log_table_name SET running = 0  WHERE hash_key = '$hash_key'");
		$response['pause_state'] = true;
		echo wp_json_encode($response);
		wp_die();
	}

	public function resume_import()
	{
		global $wpdb;
		$response = [];
		$hash_key = sanitize_key($_POST['HashKey']);
		$log_table_name = $wpdb->prefix . "import_detail_log";
		$wpdb->get_results("UPDATE $log_table_name SET running = 1  WHERE hash_key = '$hash_key'");
		$response['resume_state'] = true;
		$response['page_number'] = get_option('sm_bulk_import_page_number') + 1;
		echo wp_json_encode($response);
		wp_die();
	}


	/**
	 * Save the mapped fields on using template
	 * @return boolean
	 */
	public function save_temp_fields()
	{
		$type          = sanitize_text_field($_POST['Types']);
		$map_fields    = $_POST['MappedFields'];
		$template_name = sanitize_text_field($_POST['TemplateName']);
		$new_template_name = sanitize_text_field($_POST['NewTemplate']);
		$mapping_type = sanitize_text_field($_POST['MappingType']);
		$hash_key = sanitize_key($_POST['HashKey']);
		$helpers_instance = ImportHelpers::getInstance();
		$mapping_filter = null;
		$filters = !empty($_POST['MappedFilter']) ? json_decode(stripslashes($_POST['MappedFilter']), true) : '';
		if(!empty($filters)){
			$mapping_filter = serialize($filters);		
		}
		global $wpdb;
		$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";

		$get_detail   = $wpdb->get_results("SELECT id FROM $template_table_name WHERE templatename = '$template_name' ");
		$get_id = $get_detail[0]->id;

		$mapdata = json_decode(stripslashes($map_fields), true);

		if (array_key_exists('CORECUSTFIELDS', $mapdata)) {
			$custfields = $mapdata['CORECUSTFIELDS'];
			foreach ($custfields as $cust_key => $cust_val) {
				if (is_array($cust_val)) {
					foreach ($cust_val as $cus_key => $cus_val) {
						$mapped_fields['CORECUSTFIELDS'][$cus_key] = $cus_val;
					}
					unset($mapdata['CORECUSTFIELDS'][$cust_key]);
				} else {
					$mapped_fields['CORECUSTFIELDS'][$cust_key] = $cust_val;
				}
			}
		}

		// NOTE: If next 8 line code enabled , then headermanipulation not working in UseTemplate method

		// foreach($mapdata as $widget_grp => $widgetdata){
		// 	foreach($widgetdata as $key => $data){
		// 	$repeatedkey = strchr($key,"->",true);
		// 	if($repeatedkey && array_key_exists($repeatedkey,$widgetdata)){
		// 		unset($mapdata[$widget_grp][$key]); // For remove headermanipulation key
		// 	}			
		// 	}
		// }	
		foreach ($mapdata as $maps) {
			foreach ($maps as $header_keys => $value) {
				if (strpos($header_keys, '->cus2') !== false) {
					if (!empty($value)) {
						$helpers_instance->write_to_customfile($value);
					}
				}
			}
		}

		$has_bundlemeta = array_key_exists('BUNDLEMETA', $mapdata);
		foreach ($mapdata as $key => $value) {
			if ($key === 'ECOMMETA') {
				$map_data[$key] = $value;
				if ($has_bundlemeta) {
					$map_data['BUNDLEMETA'] = $mapdata['BUNDLEMETA'];
				}
			}elseif($key == "ATTRMETA"){		
				foreach ($value as $v_key => $val) {
					preg_match('/\d+/', $v_key, $matches);
					$index = $matches[0] ?? $counter;
					if (!isset($map_data['ATTRMETA'][$index])) {
						$map_data['ATTRMETA'][$index] = [];
					}
					$map_data['ATTRMETA'][$index][$v_key] = $val;
				}
				if(is_array($map_data['ATTRMETA'])){
					$map_data['ATTRMETA'] = array_values($map_data['ATTRMETA']);
				}
			} 
			elseif ($key !== 'BUNDLEMETA') {
				$map_data[$key] = $value;
			}
		}
		$mapping_fields = serialize($map_data);
		//added for saving serialized value with apostrophe
		// $mapping_fields = $wpdb->_real_escape($mapping_fields);
		$time = date('Y-m-d h:i:s');
		if (!empty($new_template_name)) {
			$wpdb->get_results("UPDATE $template_table_name SET templatename = '$new_template_name' , mapping ='$mapping_fields' , mapping_filter ='$mapping_filter' , createdtime = '$time' , module = '$type' , eventKey = '$hash_key' , mapping_type = '$mapping_type' WHERE id = $get_id ");
		} else {
			$wpdb->get_results("UPDATE $template_table_name SET mapping ='$mapping_fields',mapping_filter ='$mapping_filter', eventKey = '$hash_key', mapping_type = '$mapping_type' WHERE id = $get_id ");
		}
		return true;
	}
	/**
	 * Save the mapped fields on using new mapping
	 * @return boolean
	 */
	public function save_fields_function()
	{
		global $wpdb;
		$hash_key      = sanitize_key($_POST['HashKey']);
		$type          = sanitize_text_field($_POST['Types']);
		$map_fields    = $_POST['MappedFields'];
		$template_name = sanitize_text_field($_POST['TemplateName']);
		$mapping_type = sanitize_text_field($_POST['MappingType']);
		$mapping_filter = null;
		$filters = !empty($_POST['MappedFilter']) ? json_decode(stripslashes($_POST['MappedFilter']), true) : '';
		if(!empty($filters)){
			$mapping_filter = serialize($filters);		
		}
		$operation_mode = get_option("smack_operation_mode_" . $hash_key);
		if ($operation_mode == 'simpleMode') {
			$template_name = '';
		}
		$helpers_instance = ImportHelpers::getInstance();
		$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
		$file_table_name = $wpdb->prefix . "smackcsv_file_events";
		$mapped_fields = json_decode(stripslashes($map_fields), true);
		if (array_key_exists('CORECUSTFIELDS', $mapped_fields)) {
			$custfields = $mapped_fields['CORECUSTFIELDS'];
			foreach ($custfields as $cust_key => $cust_val) {
				if (is_array($cust_val)) {
					foreach ($cust_val as $cus_key => $cus_val) {
						$mapped_fields['CORECUSTFIELDS'][$cus_key] = $cus_val;
					}
					unset($mapped_fields['CORECUSTFIELDS'][$cust_key]);
				} else {
					$mapped_fields['CORECUSTFIELDS'][$cust_key] = $cust_val;
				}
			}
		}
		foreach ($mapped_fields as $maps) {
			foreach ($maps as $header_keys => $value) {
				if (strpos($header_keys, '->cus2') !== false) {
					if (!empty($value)) {
						$helpers_instance->write_to_customfile($value);
					}
				}
			}
		}
		$has_bundlemeta = array_key_exists('BUNDLEMETA', $mapped_fields);
		foreach ($mapped_fields as $key => $value) {
			if ($key === 'ECOMMETA') {
				$map_data[$key] = $value;
				if ($has_bundlemeta) {
					$map_data['BUNDLEMETA'] = $mapped_fields['BUNDLEMETA'];
				}
			}elseif($key == "ATTRMETA"){
				foreach ($value as $v_key => $val) {
					preg_match('/\d+/', $v_key, $matches);
					$index = $matches[0] ?? $counter;
					if (!isset($map_data['ATTRMETA'][$index])) {
						$map_data['ATTRMETA'][$index] = [];
					}
					$map_data['ATTRMETA'][$index][$v_key] = $val;
				}
				if(is_array($map_data['ATTRMETA'])){
					$map_data['ATTRMETA'] = array_values($map_data['ATTRMETA']);
				}
			} 
			elseif ($key !== 'BUNDLEMETA') {
				$map_data[$key] = $value;
			}
		}

		$mapping_fields = serialize($map_data);
		// $mapping_fields = $wpdb->_real_escape($mapping_fields);
		$time = date('Y-m-d H:i:s');
		$get_detail   = $wpdb->get_results("SELECT file_name FROM $file_table_name WHERE `hash_key` = '$hash_key'");
		$get_file_name = $get_detail[0]->file_name;
		$get_hash = $wpdb->get_results("SELECT eventKey FROM $template_table_name");
		if (!empty($get_hash)) {

			foreach ($get_hash as $hash_values) {
				$inserted_hash_values[] = $hash_values->eventKey;
			}
			if (in_array($hash_key, $inserted_hash_values)) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $template_table_name 
						 SET templatename = %s, 
							 mapping = %s, 
							 mapping_filter = %s, 
							 createdtime = %s, 
							 module = %s, 
							 mapping_type = %s 
						 WHERE eventKey = %s",
						$template_name,
						$mapping_fields,
						$mapping_filter ?: NULL, // Handle empty mapping_filter
						$time,
						$type,
						$mapping_type,
						$hash_key
					)
				);
				
			} else {
				$sql = $wpdb->prepare(
					"INSERT INTO $template_table_name(templatename, mapping, mapping_filter,createdtime, module, csvname, eventKey, mapping_type)VALUES(%s, %s, %s, %s, %s, %s, %s, %s)",
					$template_name,
					$mapping_fields,
					$mapping_filter ?: NULL,
					$time,
					$type,
					$get_file_name,
					$hash_key,
					$mapping_type
				);

				$results = $wpdb->query($sql);
				// $wpdb->get_results("INSERT INTO $template_table_name(templatename ,mapping ,createdtime ,module,csvname ,eventKey , mapping_type)values({$template_name},{$mapping_fields} , {$time} , {$type} , {$get_file_name}, {$hash_key}, {$mapping_type})");
			}
		} else {
			$sql = $wpdb->prepare(
				"INSERT INTO $template_table_name(templatename, mapping, mapping_filter,createdtime, module, csvname, eventKey, mapping_type)VALUES(%s, %s, %s, %s, %s, %s, %s, %s)",
				$template_name,
				$mapping_fields,
				$mapping_filter ?: NULL,
				$time,
				$type,
				$get_file_name,
				$hash_key,
				$mapping_type
			);

			$results = $wpdb->query($sql);
			// $wpdb->get_results("INSERT INTO $template_table_name(templatename ,mapping ,createdtime ,module,csvname ,eventKey , mapping_type)values({$template_name},{$mapping_fields} , {$time} , {$type} , {$get_file_name}, {$hash_key} , {$mapping_type} )");
		}

		// $get_key = array_search('post_content' ,$mapped_fields['CORE']);
		if (isset($mapped_fields['CORE'])) {
			$get_key = array_search('post_content', $mapped_fields['CORE']);
			$feautured_key = array_search('featured_image', $mapped_fields['CORE']);
		}


		$image_included = get_option("SMACK_IMAGE_INCLUDED_" . $hash_key);
		if ($image_included == 'true' || $feautured_key == 'featured_image') {

			if ($get_key == 'post_content' || $feautured_key == 'featured_image') {
				$fileiteration = '5';
			} else {
				$fileiteration = '15';
			}
			update_option('sm_bulk_import_free_iteration_limit', $fileiteration);
		} else {
			$fileiteration = '15';
			update_option('sm_bulk_import_free_iteration_limit', $fileiteration);
		}
		$result['success'] = true;
		$result['image_included'] = $image_included;
		$result['file_iteration'] = (int)$fileiteration; //added for saving serialized value with apostrophe

		return $result;
	}


	/**
	 * Provides import record details
	 */
	public function import_detail_function()
	{
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		global $wpdb;
		$hash_key = sanitize_key($_POST['HashKey']);
		$response = [];
		$log_table_name = $wpdb->prefix . "import_detail_log";
		$file_table_name = $wpdb->prefix . "smackcsv_file_events";
		$file_records = $wpdb->get_results("SELECT mode FROM $file_table_name WHERE hash_key = '$hash_key' ", ARRAY_A);
		$mode = $file_records[0]['mode'];

		if ($mode == 'Insert') {
			$method = 'Import';
		}
		if ($mode == 'Update') {
			$method = 'Update';
		}

		$total_records = $wpdb->get_results("SELECT file_name , total_records , processing_records ,status ,remaining_records , filesize FROM $log_table_name WHERE hash_key = '$hash_key' ", ARRAY_A);
		$response['success'] = true;
		$response['file_name'] = $total_records[0]['file_name'];
		$response['total_records'] = $total_records[0]['total_records'];
		$response['processing_records'] = $total_records[0]['processing_records'];
		$response['remaining_records'] = $total_records[0]['remaining_records'];
		$response['status'] = $total_records[0]['status'];
		$response['filesize'] = $total_records[0]['filesize'];
		$response['method'] = $method;

		if ($total_records[0]['status'] == 'Completed') {
			$response['progress'] = false;
		} else {
			$response['progress'] = true;
		}
		$response['Info'] = [];

		echo wp_json_encode($response);
		wp_die();
	}

	public function send_total_records()
	{
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		global $wpdb;
		$hash_key = sanitize_key($_POST['hashKey']);
		$file_table_name = $wpdb->prefix . "smackcsv_file_events";
		$total_records = $wpdb->get_results("SELECT total_rows ,file_name  FROM $file_table_name WHERE hash_key = '$hash_key' ");
		$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
		$file_name = $total_records[0]->file_name;
		$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
		if ($file_extension == 'xml') {
			$total_rows = json_decode($total_records[0]->total_rows);
			$background_values = $wpdb->get_results("SELECT mapping , module  FROM $template_table_name WHERE `eventKey` = '$hash_key' ");
			foreach ($background_values as $values) {
				$mapped_fields_values = $values->mapping;
				$selected_type = $values->module;
			}
			$map = unserialize($mapped_fields_values);
			$upload_dir = SaveMapping::$smackcsv_instance->create_upload_dir();
			$path = $upload_dir . $hash_key . '/' . $hash_key;

			$xml = simplexml_load_file($path);
			$xml_arr = json_decode(json_encode($xml), 1);
			if (count($xml_arr) == count($xml_arr, COUNT_RECURSIVE)) {
				$item = $xml->addchild('item');
				foreach ($xml_arr as $key => $value) {
					$xml->item->addchild($key, $value);
					unset($xml->$key);
				}
				$arraytype = "not parent";
				$xmls['item'] = $xml_arr;
			} else {
				$arraytype = "parent";
			}
			$i = 0;
			$childs = array();
			foreach ($xml->children() as $child => $val) {
				// $child_name =  $child->getName();  
				$values = (array)$val;
				if (empty($values)) {
					if (!in_array($child, $childs, true)) {
						$childs[$i++] = $child;
					}
				} else {
					if (array_key_exists("@attributes", $values)) {
						if (!in_array($child, $childs, true)) {
							$childs[$i++] = $child;
						}
					} else {
						foreach ($values as $k => $v) {
							is_array($values[$k])  ? $checks = implode(',', $values[$k]) : $checks = (string)$values[$k];
							if (is_numeric($k)) {
								if (empty($checks)) {
									if (!in_array($child, $childs, true)) {
										$childs[$i++] = $child;
									}
								}
							} else {
								if (!empty($checks)) {
									if (!in_array($child, $childs, true)) {
										$childs[$i++] = $child;
									}
								}
							}
						}
					}
				}
			}
			$h = 0;
			if ($arraytype == "parent") {
				foreach ($childs as $child_name) {
					foreach ($map as $field => $value) {
						foreach ($value as $head => $val) {
							$str = str_replace(array('(', '[', ']', ')'), '', $val);
							$ex = explode('/', $str);
							$last = substr($ex[2], -1);
							if (is_numeric($last)) {
								$substr = substr($ex[2], 0, -1);
							} else {
								$substr = $ex[2];
							}

							if ($substr == $child_name) {
								$count = 'count' . $h;
								$totalrows = $total_rows->$count;
							}
						}
					}
					$h++;
				}
			} else {
				$count = 'count' . $h;
				$totalrows = $total_rows->$count;
			}

			$response['total_records'] = $totalrows;
		} else {
			$response['total_records'] = $total_records[0]->total_rows;
		}

		$response['sucess'] = 'true';
		echo wp_json_encode($response);
		wp_die();
	}
	/**
	 * Checks whether the import function is paused or resumed
	 */
	public function import_state_function($hashkey, $type)
	{
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		$response = [];
		$hash_key = sanitize_key($_POST['HashKey']);
		$upload = wp_upload_dir();
		$upload_base_url = $upload['baseurl'];
		$upload_url = $upload_base_url . '/smack_uci_uploads/imports/';
		$upload_dir = SaveMapping::$smackcsv_instance->create_upload_dir();
		$log_path = $upload_dir . $hash_key . '/' . $hash_key . '.html';
		if (file_exists($log_path)) {
			$log_link_path = $upload_url . $hash_key . '/' . $hash_key . '.html';
		}

		$import_txt_path = $upload_dir . 'import_state.txt';
		chmod($import_txt_path, 0777);
		$import_state_arr = array();

		/* Gets string 'true' when Resume button is clicked  */
		if (sanitize_text_field($_POST['State']) == 'true') {
			$open_file = fopen($import_txt_path, "w");
			$import_state_arr = array('import_state' => 'on', 'import_stop' => 'on');
			$state_arr = serialize($import_state_arr);
			fwrite($open_file, $state_arr);
			fclose($open_file);

			$response['import_state'] = false;
		}
		/* Gets string 'false' when Pause button is clicked  */
		if (sanitize_text_field($_POST['State']) == 'false') {
			//first check then set off	
			$open_file = fopen($import_txt_path, "w");
			$import_state_arr = array('import_state' => 'off', 'import_stop' => 'on');
			$state_arr = serialize($import_state_arr);
			fwrite($open_file, $state_arr);
			fclose($open_file);
			if ($log_link_path != null) {
				$response['show_log'] = true;
			} else {
				$response['show_log'] = false;
			}
			$response['import_state'] = true;
			$response['log_link'] = $log_link_path;
		}
		echo wp_json_encode($response);
		wp_die();
	}


	/**
	 * Checks whether the import function is stopped or the page is refreshed
	 */
	public function import_stop_function()
	{
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		$upload_dir = SaveMapping::$smackcsv_instance->create_upload_dir();
		/* Gets string 'false' when page is refreshed */

		if (sanitize_text_field($_POST['Stop']) == 'false') {
			$import_txt_path = $upload_dir . 'import_state.txt';
			chmod($import_txt_path, 0777);
			$import_state_arr = array();
			$open_file = fopen($import_txt_path, "w");
			$import_state_arr = array('import_state' => 'on', 'import_stop' => 'off');
			$state_arr = serialize($import_state_arr);
			fwrite($open_file, $state_arr);
			fclose($open_file);
		}
		wp_die();
	}
	public function smackcsv_image_schedule_function($schedule_array = null, $unikey = null)
	{
		global $wpdb;
		$image = $wpdb->get_results("select post_id from {$wpdb->prefix}ultimate_csv_importer_shortcode_manager where status = 'completed'");
		if (!empty($image)) {
			SaveMapping::$failed_images_instance->delete_image_schedule('', '');
		}
	}

	public function bulk_import()
	{
		global $wpdb, $core_instance;
		$addHeader = false;
		$mapping_filter = '';
		$upload_dir = SaveMapping::$smackcsv_instance->create_upload_dir();
		$hash_key  = sanitize_key($_POST['HashKey']);
		$check = sanitize_text_field($_POST['Check']);
		$page_number = intval($_POST['PageNumber']);
		$rollback_option = sanitize_text_field($_POST['RollBack']);
		$this->isCategory = isset($_POST['iscategories']) && filter_var($_POST['iscategories'], FILTER_VALIDATE_BOOLEAN);
		$categoriesJson = stripslashes($_POST['Categories']);
		$this->categoryList = json_decode($categoriesJson, true);
		$check_filter = sanitize_text_field($_POST['mappingFilterCheck']);
		$check_manage_filter = $check_filter == 'false' ? false : true;
		$media_type = '';
		if (isset($_POST['MediaType'])) {
			$media_type = sanitize_key($_POST['MediaType']);
		}
		$unmatched_row_value = get_option('sm_uci_pro_settings');
		$unmatched_row = isset($unmatched_row_value['unmatchedrow']) ? $unmatched_row_value['unmatchedrow'] : '';
		$update_based_on = sanitize_text_field($_POST['UpdateUsing']);
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		$import_config_instance = ImportConfiguration::getInstance();
		$file_manager_instance = FileManager::getInstance();
		$log_manager_instance = LogManager::getInstance();
		$file_table_name = $wpdb->prefix . "smackcsv_file_events";
		$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
		$log_table_name = $wpdb->prefix . "import_detail_log";

		$schedule_array = array($hash_key, 'hash_key');
		// if ( ! wp_next_scheduled( 'smackcsv_image_schedule_hook', $schedule_array) ) {
		// 	wp_schedule_event( time(), 'smack_image_every_second', 'smackcsv_image_schedule_hook', $schedule_array );	
		// }
		$file_iteration = get_option('sm_bulk_import_iteration_limit');
		$response = [];
		$get_id = $wpdb->get_results("SELECT id , mode ,file_name , total_rows FROM $file_table_name WHERE `hash_key` = '$hash_key'");
		$get_mode = $get_id[0]->mode;
		$file_name = $get_id[0]->file_name;
		$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
		if (empty($file_extension)) {
			$file_extension = 'xml';
		}
		if ($file_extension == 'xlsx' || $file_extension == 'xls' || $file_extension == 'json') {
			$file_extension = 'csv';
		}

		if ($file_extension == 'xml') {
			$total_rows = json_decode($get_id[0]->total_rows);
			$background_values = $wpdb->get_results("SELECT mapping ,mapping_filter, module  FROM $template_table_name WHERE `eventKey` = '$hash_key' ");
			foreach ($background_values as $values) {
				$mapped_fields_values = $values->mapping;
				$mapping_filter = $values->mapping_filter;
				$selected_type = $values->module;
			}

			$map = unserialize($mapped_fields_values);
			$manage_filter = unserialize($mapping_filter);
			$upload_dir = SaveMapping::$smackcsv_instance->create_upload_dir();
			$path = $upload_dir . $hash_key . '/' . $hash_key;

			$xml = simplexml_load_file($path);
			$xml_arr = json_decode(json_encode($xml), 1);
			if (count($xml_arr) == count($xml_arr, COUNT_RECURSIVE)) {
				$item = $xml->addchild('item');
				foreach ($xml_arr as $key => $value) {
					$xml->item->addchild($key, $value);
					unset($xml->$key);
				}
				$arraytype = "not parent";
				$xmls['item'] = $xml_arr;
			} else {
				$arraytype = "parent";
			}
			$i = 0;
			$childs = array();
			foreach ($xml->children() as $child => $val) {
				$values = (array) $val;
				
				if (empty($values)) {
					if (!in_array($child, $childs, true)) {
						$childs[$i++] = $child;
					}
				} else {
					if (array_key_exists("@attributes", $values)) {
						if (!in_array($child, $childs, true)) {
							$childs[$i++] = $child;
						}
					} else {
						foreach ($values as $k => $v) {
							is_array($values[$k]) ? $checks = implode(',', $values[$k]) : $checks = (string) $values[$k];							
							if (is_numeric($k)) {
								if (empty($checks)) {
									if (!in_array($child, $childs, true)) {
										$childs[$i++] = $child;
									}
								}
							} else {
								if (!empty($checks)) {
									if (!in_array($child, $childs, true)) {
										$childs[$i++] = $child;
									}
								}
							}
						}
					}
				}
			}
			$h = 0;
			if ($arraytype == 'parent') {
				foreach ($childs as $child_name) {
					// Count total occurrences of the child element in XML
					$totalrows = count($xml->xpath("//$child_name"));
					foreach ($map as $field => $value) {
						foreach ($value as $head => $val) {
							$str = str_replace(['(', '[', ']', ')'], '', $val);
							$ex = explode('/', $str);
							$last = substr($ex[2], -1);

							if (is_numeric($last)) {
								$substr = substr($ex[2], 0, -1);
							} else {
								$substr = $ex[2];
							}

							if ($substr === $child_name) {
								$count = 'count' . $h;
							}
						}
					}
					$h++;
				}
			}			
			else {
				$count = 'count' . $h;
				$totalrows = $total_rows->$count;
			}
			$total_rows = $totalrows;
		}
		else {
			$total_rows = $get_id[0]->total_rows;
		}
		$total_pages = ceil($total_rows / $file_iteration);
		$file_name = $get_id[0]->file_name;
		$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

		if (empty($file_extension)) {
			$file_extension = 'xml';
		}
		if ($file_extension == 'xlsx' || $file_extension == 'xls' || $file_extension == 'json') {
			$file_extension = 'csv';
		}
		$gmode = 'Normal';
		$upload_dir = SaveMapping::$smackcsv_instance->create_upload_dir();
		$file_size = filesize($upload_dir . $hash_key . '/' . $hash_key);
		$filesize = $helpers_instance->formatSizeUnits($file_size);

		update_option('sm_bulk_import_page_number', $page_number);

		$remain_records = $total_rows - 1;
		$wpdb->insert($log_table_name, array('file_name' => $file_name, 'hash_key' => $hash_key, 'total_records' => $total_rows, 'filesize' => $filesize, 'processing_records' => 1, 'remaining_records' => $remain_records, 'status' => 'Processing'));
		$background_values = $wpdb->get_results("SELECT mapping , mapping_filter, module  FROM $template_table_name WHERE `eventKey` = '$hash_key' ");
		foreach ($background_values as $values) {
			$mapped_fields_values = $values->mapping;
			$mapping_filter = $values->mapping_filter;
			$selected_type = $values->module;
		}
		$manage_filter = unserialize($mapping_filter);
		$map = unserialize($mapped_fields_values);
		$map = $this->remove_existingfields($map, $selected_type, $get_mode, $media_type);

		//By default update process done with post_title
		//Assign $check to data for avoid the warnings

		if ($get_mode == 'Update' && empty($check)) {
			if (is_plugin_active('jet-engine/jet-engine.php')) {
				$moduletype = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
				foreach ($moduletype as $key => $slug) {
					$content_type = $slug->slug;
					if ($selected_type == $content_type) {
						$check = '_ID';
						break;
					}
				}
			} else {
				$check = 'post_title';
			}
		}


		if ($rollback_option == 'true') {
			if ($page_number == 1) {
				$tables = $import_config_instance->get_rollback_tables($selected_type);
				$import_config_instance->set_backup_restore($hash_key, 'backup', $tables);
			}
		}

		$file_iteration = get_option('sm_bulk_import_iteration_limit');

		if ($file_extension == 'csv' || $file_extension == 'txt' || $file_extension == 'xls' || $file_extension == 'json') {
			$check_if_import_paused = get_option('smack_csvpro_paused_record_' . $hash_key);

			if ($check_if_import_paused) {
				$old_line_number = (($file_iteration * $page_number) - $file_iteration) + 1;

				$line_number = $check_if_import_paused;
				$limit = ($file_iteration * $page_number);

				$record_imported = $check_if_import_paused - $old_line_number;
				$parsing_limit = $file_iteration - $record_imported;

				delete_option('smack_csvpro_paused_record_' . $hash_key);
			} else {
				$line_number = (($file_iteration * $page_number) - $file_iteration) + 1;
				$limit = ($file_iteration * $page_number);
				$parsing_limit = $file_iteration;
			}

			if ($page_number == 1) {
				$addHeader = true;
			}

			$file_path = $upload_dir . $hash_key . '/' . $hash_key;
			$delimiter = SaveMapping::$validatefile->getFileDelimiter($file_path, 5);
			if($delimiter == '\t'){
				$i = 0;
				$all_value_array =array();
				$info = [];
				$delimiter ='~';
				 $temp=$file_path.'temp';
				 if (($handles = fopen($temp, 'r')) !== FALSE){
					while (($data = fgetcsv($handles, 0, $delimiter)) !== FALSE)
					{
						$trimmed_info = array_map('trim', $data);
						array_push($info , $trimmed_info);
						if ($i == 0) {
							$header_array = $info[$i];
							$i++;
							continue;
						}
						else{
							$all_value_array[] = $info[$i];
							$i++;
						}
						
					}
		
			
				}
			}
			else{
				$parserObj = new SmackCSVParser();
				$parse_csv_response = $parserObj->parseCSV($file_path, $line_number, $parsing_limit);
				$header_array = !empty($parse_csv_response['headers'][0]) ?  array_map('trim', $parse_csv_response['headers'][0]) : [];
				$all_value_array = $parse_csv_response['values'];
			}		
			

			$openAIKeys = array();
			$openAIValues = array();
			$flag = false;
			$map_openAI = false;
			foreach ($map as $subarray) {
				foreach ($subarray as $key => $value) {
					if (strpos($key, '->OPENAIA') !== false) {
						$map_openAI = 1;
						break 2;
					}
				}
			}
			if ($map_openAI == true) {
				foreach ($map as $mainKey => $mainValue) {
					foreach ($mainValue as $subKey => $subValue) {
						if (substr($subKey, -8) === '->OPENAIA') {
							$flag = true;
							$value_header = str_replace("->OPENAIA", "", $subKey);
							$openAIKeys[] = $value_header;
							$openAIValues[] = $subValue;
						}
						if (substr($subKey, -5) === '->num') {
							$flag = true;
							$value_header = str_replace("->num", "", $subKey);
							$openAInumberKeys[] = $value_header;
							$openAInumberValues[] = $subValue;
						}
					}
				}

				$core_instance->generated_content = $flag;
				$combinedArray = array_combine($openAIKeys, $openAIValues);
				if (isset($combinedArray['featured_image'])) {
					$featuredImageValue = $combinedArray['featured_image'];
					$index = array_search($featuredImageValue, $header_array);

					if ($index !== false) {
						foreach ($all_value_array as $innerArray) {
							if (isset($innerArray[$index])) {
								$resultArray[] = $innerArray[$index];
							} else {
								$resultArray[] = '';
							}
						}
					} else {
						$resultArray = '';
					}
					foreach ($resultArray as $index => $valueName) {
						$OpenAIHelper = new OpenAIHelper;
						$responsevalueArray[] = $OpenAIHelper->generateImage($valueName);
					}

					$value = 'featured_image';
					$index = array_search($value, $header_array);
					if ($index !== false) {
						foreach ($all_value_array as &$subArray) {
							$subArray[$index] = array_shift($responsevalueArray);
						}
					}

					foreach ($responsevalueArray as $value) {
						foreach ($openAIKeys as $mainKey) {
							$index = array_search($mainKey, $header_array);
							foreach ($all_value_array as $innerArray) {
								$innerArray[$index] = $value;
							}
						}
					}
					unset($combinedArray['featured_image']);
				}

				$openAIValues = array_values($combinedArray);
				$openAIKeys = array_keys($combinedArray);

				foreach ($openAIValues as $mainKey) {
					$index = array_search($mainKey, $header_array);
					$resultArray = [];
					if ($index !== false) {
						foreach ($all_value_array as $innerArray) {
							if (isset($innerArray[$index])) {
								$resultArray[] = $innerArray[$index];
							} else {
								$resultArray[] = '';
							}
						}
					} else {
						$resultArray[] = '';
					}

					$resultArrays[$mainKey][] = $resultArray;
				}

				$keys = array_keys($resultArrays);
				for ($i = 0; $i < count($openAInumberValues); $i++) {
					$resultArrays[$openAInumberValues[$i]] = $resultArrays[$keys[$i]];
					unset($resultArrays[$keys[$i]]);
				}

				foreach ($resultArrays as $index => $valueName) {
					foreach ($valueName as $value => $prompt) {
						foreach ($prompt as $val => $word) {
							$OpenAIHelper = new OpenAIHelper;
							$responsevalueArray[] = $OpenAIHelper->generateContent($word, $index);
						}
					}
				}
				$core_instance->OPENAIA_response = $responsevalueArray;

				foreach ($openAIKeys as $value) {
					$index = array_search($value, $header_array);
					if ($index !== false) {
						foreach ($all_value_array as &$subArray) {
							$subArray[$index] = array_shift($responsevalueArray);
						}
					}
				}

				foreach ($responsevalueArray as $value) {
					foreach ($openAIKeys as $mainKey) {
						$index = array_search($mainKey, $header_array);
						foreach ($all_value_array as $innerArray) {
							$innerArray[$index] = $value;
						}
					}
				}

				foreach ($map as $key => &$value) {
					if (is_array($value)) {
						foreach ($value as $innerKey => $innerValue) {
							if (strpos($innerKey, '->OPENAIA') !== false) {
								$newKey = str_replace('->OPENAIA', '', $innerKey);
								$value[$newKey] = $newKey;
								unset($value[$innerKey]);
							}
						}
					}
				}
			}
			if (!empty($all_value_array)) {
				foreach ($all_value_array as $i => $value_array) {
					if (!empty($value_array)) {
						$get_arr = $this->main_import_process($map, $header_array, $value_array, $selected_type, $get_mode, $i, $unmatched_row, $check, $hash_key, $update_based_on, $gmode, '', $media_type,$check_manage_filter,$manage_filter);
						$post_id = $get_arr['id'];
						$core_instance->detailed_log = $get_arr['detail_log'];
						$failed_media_log = $get_arr['failed_media_log'];
						$media_log = $get_arr['media_log'];
						$helpers_instance->get_post_ids($post_id, $hash_key);
						$remaining_records = $total_rows - $i;
						$wpdb->get_results("UPDATE $log_table_name SET processing_records = $i , remaining_records = $remaining_records , status = 'Processing' WHERE hash_key = '$hash_key'");
						if ($i == $total_rows) {
							$wpdb->get_results("UPDATE $log_table_name SET status = 'Completed' WHERE hash_key = '$hash_key'");
						}

						if (is_countable($core_instance->detailed_log) && count($core_instance->detailed_log) > $file_iteration) {
							$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
							$addHeader = false;
							$core_instance->detailed_log = [];
							$media_log = [];
							$failed_media_log = [];
						}

						$running = $wpdb->get_row("SELECT running FROM $log_table_name WHERE hash_key = '$hash_key' ");
						$check_pause = $running->running;
						if ($check_pause == 0) {

							update_option('smack_csvpro_paused_record_' . $hash_key, $i + 1);
							if (count($core_instance->detailed_log) > 0) {
								$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
							}
							$file_manager_instance->manage_records($hash_key, $selected_type, $file_name, $total_rows);
							$response['success'] = false;
							$response['pause_message'] = 'Record Paused';
							echo wp_json_encode($response);
							wp_die();
						}
					}
				}
			}
		}
		if($file_extension == 'tsv'){
			
			$line_number = (($file_iteration * $page_number) - $file_iteration) + 1;
			$limit = ($file_iteration * $page_number);

			if ($page_number == 1) {
				$addHeader = true;
			}

			$file_path = $upload_dir . $hash_key . '/' . $hash_key;
			$delimiter = SaveMapping::$validatefile->getFileDelimiter($file_path, 5);
			if($delimiter == '\t'){
				$i = 0;
				$all_value_array =array();
				$info = [];
				if (($h = fopen($upload_dir . $hash_key . '/' . $hash_key, "r")) !== FALSE) {
					$file_path = $upload_dir . $hash_key . '/' . $hash_key;
					$delimiter = SaveMapping::$validatefile->getFileDelimiter($file_path, 5);
					if (($handles = fopen($file_path, 'r')) !== FALSE){
						while (($data = fgetcsv($handles, 0,"\t")) !== FALSE)
						{
							$trimmed_info = array_map('trim', $data);
							array_push($info , $trimmed_info);
							if ($i == 0) {
								$header_array = $info[$i];
								$i++;
								continue;
							}
							if ($i >= $line_number && $i <= $limit) {
								$value_array = $info[$i];
								$openAIKeys = array();
								$openAIValues = array();
								$flag = false;
								$map_openAI = false;
								foreach ($map as $subarray) {
									foreach ($subarray as $key => $value) {
										if (strpos($key, '->OPENAIA') !== false) {
											$map_openAI = 1;
											break 2;
										}
									}
								}
								if ($map_openAI == true) {
									foreach ($map as $mainKey => $mainValue) {
										foreach ($mainValue as $subKey => $subValue) {
											if (substr($subKey, -8) === '->OPENAIA') {
												$flag = true;
												$value_header = str_replace("->OPENAIA", "", $subKey);
												$openAIKeys[] = $value_header;
												$openAIValues[] = $subValue;
											}
											if (substr($subKey, -5) === '->num') {
												$flag = true;
												$value_header = str_replace("->num", "", $subKey);
												$openAInumberKeys[] = $value_header;
												$openAInumberValues[] = $subValue;
											}
										}
									}

									$core_instance->generated_content = $flag;
									$combinedArray = array_combine($openAIKeys, $openAIValues);
									if (isset($combinedArray['featured_image'])) {
										$featuredImageValue = $combinedArray['featured_image'];
										$index = array_search($featuredImageValue, $header_array);

										if ($index !== false) {
												if (isset($value_array[$index])) {
													$resultArray[] = $value_array[$index];
												} else {
													$resultArray[] = '';
												}
										} else {
											$resultArray = '';
										}
										foreach ($resultArray as $index => $valueName) {
											$OpenAIHelper = new OpenAIHelper;
											$responsevalueArray[] = $OpenAIHelper->generateImage($valueName);
										}

										$value = 'featured_image';
										$index = array_search($value, $header_array);
										if ($index !== false) {
											$value_array[$index] = array_shift($responsevalueArray);
										}

										foreach ($responsevalueArray as $value) {
											foreach ($openAIKeys as $mainKey) {
												$index = array_search($mainKey, $header_array);
												$value_array[$index] = $value;
											}
										}
										unset($combinedArray['featured_image']);
									}

									$openAIValues = array_values($combinedArray);
									$openAIKeys = array_keys($combinedArray);

									foreach ($openAIValues as $mainKey) {
										$index = array_search($mainKey, $header_array);
										$resultArray = [];
										if ($index !== false) {
											if (isset($value_array[$index])) {
												$resultArray[] = $value_array[$index];
											} else {
												$resultArray[] = '';
											}
										} else {
											$resultArray[] = '';
										}

										$resultArrays[$mainKey][] = $resultArray;
									}

									$keys = array_keys($resultArrays);
									for ($i = 0; $i < count($openAInumberValues); $i++) {
										$resultArrays[$openAInumberValues[$i]] = $resultArrays[$keys[$i]];
										unset($resultArrays[$keys[$i]]);
									}

									foreach ($resultArrays as $index => $valueName) {
										foreach ($valueName as $value => $prompt) {
											foreach ($prompt as $val => $word) {
												$OpenAIHelper = new OpenAIHelper;
												$responsevalueArray[] = $OpenAIHelper->generateContent($word, $index);
											}
										}
									}
									$core_instance->OPENAIA_response = $responsevalueArray;

									foreach ($openAIKeys as $value) {
										$index = array_search($value, $header_array);
										if ($index !== false) {
										
											$value_array[$index] = array_shift($responsevalueArray);
										
										}
									}

									foreach ($responsevalueArray as $value) {
										foreach ($openAIKeys as $mainKey) {
											$index = array_search($mainKey, $header_array);
											
											$value_array[$index] = $value;
											
										}
									}

									foreach ($map as $key => &$value) {
										if (is_array($value)) {
											foreach ($value as $innerKey => $innerValue) {
												if (strpos($innerKey, '->OPENAIA') !== false) {
													$newKey = str_replace('->OPENAIA', '', $innerKey);
													$value[$newKey] = $newKey;
													unset($value[$innerKey]);
												}
											}
										}
									}
								}
								// if (!empty($all_value_array)) {
								// 	foreach ($all_value_array as $i => $value_array) {
										if (!empty($value_array)) {
											$get_arr = $this->main_import_process($map, $header_array, $value_array, $selected_type, $get_mode, $i, $unmatched_row, $check, $hash_key, $update_based_on, $gmode, '', $media_type,$check_manage_filter,$manage_filter);
											$post_id = $get_arr['id'];
											$core_instance->detailed_log = $get_arr['detail_log'];
											$failed_media_log = $get_arr['failed_media_log'];
											$media_log = $get_arr['media_log'];
											$helpers_instance->get_post_ids($post_id, $hash_key);
											$remaining_records = $total_rows - $i;
											$wpdb->get_results("UPDATE $log_table_name SET processing_records = $i , remaining_records = $remaining_records , status = 'Processing' WHERE hash_key = '$hash_key'");
											if ($i == $total_rows) {
												$wpdb->get_results("UPDATE $log_table_name SET status = 'Completed' WHERE hash_key = '$hash_key'");
											}

											if (is_countable($core_instance->detailed_log) && count($core_instance->detailed_log) > $file_iteration) {
												$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
												$addHeader = false;
												$core_instance->detailed_log = [];
												$media_log = [];
												$failed_media_log = [];
											}
										}
								// 	}
									
								// }
							}
							if ($i > $limit) {
								break;
							}
		
							$i++;
						}
						$running = $wpdb->get_row("SELECT running FROM $log_table_name WHERE hash_key = '$hash_key' ");
						$check_pause = $running->running;
						if ($check_pause == 0) {

							update_option('smack_csvpro_paused_record_' . $hash_key, $i + 1);
							if (count($core_instance->detailed_log) > 0) {
								$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
							}
							$file_manager_instance->manage_records($hash_key, $selected_type, $file_name, $total_rows);
							$response['success'] = false;
							$response['pause_message'] = 'Record Paused';
							echo wp_json_encode($response);
							wp_die();
						}
							
					}
				}
			}
		}
		if ($file_extension == 'xml') {
			$path = $upload_dir . $hash_key . '/' . $hash_key;
			// $lined_number = (($file_iteration * $page_number) - $file_iteration);
			// $limit = ($file_iteration * $page_number) - 1;

			$lined_number = ($file_iteration * ($page_number - 1));
			$limit = min(($file_iteration * $page_number) - 1, $total_rows - 1); // Ensure limit does not exceed total rows
			$i = 0;
			$info = [];
			$addHeader = true;
			for ($line_number = 0; $line_number < $total_rows; $line_number++) {
				if ($i >= $lined_number && $i <= $limit) {
					$xml_class = new XmlHandler();
					$parse_xml = $xml_class->parse_xmls($hash_key, $i);
					$j = 0;
					$header_array = [];
					$value_array = [];
					$head = array();
					$value = array();
					$count = array();
					foreach ($parse_xml as $xml_key => $xml_value) {

						if (is_array($xml_value)) {
							foreach ($xml_value as $e_key => $e_value) {
								$head['header'][$j] = $e_value['name'];
								$value['value'][$j] = $e_value['value'];
								$j++;
							}
							array_push($header_array, $head);
							array_push($value_array, $value);
						} else {
							if (strpos($xml_key, 'count') !== false) {
								array_push($count, $xml_value);
							}
						}
					}
					$xml = simplexml_load_file($path);
					$xml_arr = json_decode(json_encode($xml), 1);
					if (count($xml_arr) == count($xml_arr, COUNT_RECURSIVE)) {
						$item = $xml->addchild('item');
						foreach ($xml_arr as $key => $value) {
							$xml->item->addchild($key, $value);
							unset($xml->$key);
						}
						$arraytype = "not parent";
						$xmls['item'] = $xml_arr;
					} else {
						$arraytype = "parent";
					}
					$childs = array();
					$s = 0;
					foreach ($xml->children() as $child => $val) {
						// $tag = $child->getName(); 
						$tag = (array)$val;
						if (empty($tag)) {
							if (!in_array($child, $childs, true)) {
								$childs[$s++] = $child;
							}
						} else {
							if (array_key_exists("@attributes", $tag)) {
								if (!in_array($child, $childs, true)) {
									$childs[$s++] = $child;
								}
							} else {
								foreach ($tag as $k => $v) {

									is_array($tag[$k])  ? $checks = implode(',', $tag[$k]) : $checks = (string)$tag[$k];
									if (is_numeric($k)) {
										if (empty($checks)) {
											if (!in_array($child, $childs, true)) {
												$childs[$s++] = $child;
											}
										}
									} else {
										if (!empty($checks)) {
											if (!in_array($child, $childs, true)) {
												$childs[$s++] = $child;
											}
										}
									}
								}
							}
						}
					}
					// $tag =current($childs);
					$h = 0;
					if ($arraytype == 'parent') {
						foreach ($childs as $tag) {
							$mapping = array();
							// $total_xml_count = $this->get_xml_count($path , $tag);
							$total_xml_count = $this->get_xml_count($path, $tag);
							foreach ($xml->children() as $child) {
								$child_names =  $child->getName();
							}
							if ($total_xml_count == 0) {
								$sub_child = $this->get_child($child, $path);
								$tag = $sub_child['child_name'];
								$total_xml_count = $sub_child['total_count'];
							}
							$doc = new \DOMDocument();
							$doc->load($path);

							foreach ($map as $field => $value) {
								foreach ($value as $head => $val) {
									$str = str_replace(array('(', '[', ']', ')'), '', $val);
									$ex = explode('/', $str);

									$last = substr($ex[2], -1);
									if (is_numeric($last)) {
										$substr = substr($ex[2], 0, -1);
									} else {
										$substr = $ex[2];
									}

									//if($substr == $tag){
									if (preg_match('/{/', $val) && preg_match('/}/', $val)) {
										preg_match_all('/{(.*?)}/', $val, $matches);
										$line_numbers = $i + 1;

										$val = preg_replace("{" . "(" . $tag . "[+[0-9]+])" . "}", $tag . "[" . $line_numbers . "]", $val);
										for ($k = 0; $k < count($matches[1]); $k++) {
											$matches[1][$k] = preg_replace("(" . $tag . "[+[0-9]+])", $tag . "[" . $line_numbers . "]", $matches[1][$k]);
											$value = $this->parse_element($doc, $matches[1][$k], $i);
											$search = '{' . $matches[1][$k] . '}';
											$val = str_replace($search, $value, $val);
										}
										$mapping[$field][$head] = $val;
									} else {
										$mapping[$field][$head] = $val;
									}
									//}
								}
							}
							array_push($info, $value_array[$h]['value']);
							if (!empty($mapping)) {
								$get_arr = $this->main_import_process($mapping, $header_array[$h]['header'], $value_array[$h]['value'], $selected_type, $get_mode, $i, $unmatched_row, $check, $hash_key, $update_based_on, $gmode, '', $media_type,$check_manage_filter,$manage_filter);
								$post_id = $get_arr['id'];
								$core_instance->detailed_log = $get_arr['detail_log'];
								$media_log = $get_arr['media_log'];
								$failed_media_log = $get_arr['failed_media_log'];
							}
							$h++;
						}
					} else {
						$total_xml_count = 1;
						$doc = new \DOMDocument();
						$doc->load($path);
						foreach ($map as $field => $value) {
							foreach ($value as $head => $val) {
								if (preg_match('/{/', $val) && preg_match('/}/', $val)) {
									preg_match_all('/{(.*?)}/', $val, $matches);
									$line_numbers = $i + 1;
									$val = preg_replace("{" . "(" . $tag . "[+[0-9]+])" . "}", $tag . "[" . $line_numbers . "]", $val);
									for ($k = 0; $k < count($matches[1]); $k++) {
										$matches[1][$k] = preg_replace("(" . $tag . "[+[0-9]+])", $tag . "[" . $line_numbers . "]", $matches[1][$k]);

										$value = $this->parse_element($doc, $matches[1][$k], $i);
										$search = '{' . $matches[1][$k] . '}';
										$val = str_replace($search, $value, $val);
									}
									$mapping[$field][$head] = $val;
								} else {
									$mapping[$field][$head] = $val;
								}
							}
						}
						array_push($info, $value_array[$h]['value']);
						if (!empty($mapping)) {
							$get_arr = $this->main_import_process($mapping, $header_array[$h]['header'], $value_array[$h]['value'], $selected_type, $get_mode, $i, $unmatched_row, $check, $hash_key, $update_based_on, $gmode, '', $media_type,$check_manage_filter,$manage_filter);
							$post_id = $get_arr['id'];
							$core_instance->detailed_log = $get_arr['detail_log'];
							$media_log = $get_arr['media_log'];
							$failed_media_log = $get_arr['failed_media_log'];
						}
					}
					$helpers_instance->get_post_ids($post_id, $hash_key);
					$line_numbers = $i + 1;
					$remaining_records = max($total_rows - $line_numbers, 0);
					$wpdb->get_results("UPDATE $log_table_name SET processing_records = $line_numbers, remaining_records = $remaining_records, status = 'Processing' WHERE hash_key = '$hash_key'");

					if ($i == $total_rows - 1) {
						$wpdb->get_results("UPDATE $log_table_name SET status = 'Completed' WHERE hash_key = '$hash_key'");
					}

					if (is_countable($core_instance->detailed_log) && count($core_instance->detailed_log) > $file_iteration) {
						$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $i);
						$addHeader = false;
						$core_instance->detailed_log = [];
						$media_log = [];
						$failed_media_log = [];
					}
				}
				if ($i > $limit) {
					break;
				}
				$i++;
			}
			$running = $wpdb->get_row("SELECT running FROM $log_table_name WHERE hash_key = '$hash_key' ");
			$check_pause = $running->running;

			if ($check_pause == 0) {
				update_option('smack_csvpro_paused_record_' . $hash_key, $i + 1);
				if (count($core_instance->detailed_log) > 0) {
					$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
				}
				$file_manager_instance->manage_records($hash_key, $selected_type, $file_name, $total_rows);
				$response['success'] = false;
				$response['pause_message'] = 'Record Paused';
				echo wp_json_encode($response);
				wp_die();
			}
		}
		if ($file_extension == 'xlsx') {
			$check_if_import_paused = get_option('smack_csvpro_paused_record_' . $hash_key);

			if ($check_if_import_paused) {
				$old_line_number = (($file_iteration * $page_number) - $file_iteration) + 1;

				$line_number = $check_if_import_paused;
				$limit = ($file_iteration * $page_number);

				$record_imported = $check_if_import_paused - $old_line_number;
				$parsing_limit = $file_iteration - $record_imported;

				delete_option('smack_csvpro_paused_record_' . $hash_key);
			} else {
				$line_number = (($file_iteration * $page_number) - $file_iteration) + 1;
				$limit = ($file_iteration * $page_number);
				$parsing_limit = $file_iteration;
			}

			if ($page_number == 1) {
				$addHeader = true;
			}
			$file_path = $upload_dir . $hash_key . '/' . $hash_key;
			if ($xlsx = SimpleXLSX::parse($file_path)) {
				$get_file = $xlsx->rows();
			} else {
				echo SimpleXLSX::parseError();
			}
			$header_array = $get_file[0];
			unset($get_file[0]);
			$value_arrays['values'] = $get_file;
			$all_value_array = $value_arrays['values'];
			foreach ($all_value_array as $i => $value_array) {
				$get_arr = $this->main_import_process($map, $header_array, $value_array, $selected_type, $get_mode, $i, $unmatched_row, $check, $hash_key, $update_based_on, $gmode, '', $media_type,$check_manage_filter,$manage_filter);
				$post_id = $get_arr['id'];
				$core_instance->detailed_log = $get_arr['detail_log'];
				$media_log = $get_arr['media_log'];
				$failed_media_log = $get_arr['failed_media_log'];
				$helpers_instance->get_post_ids($post_id, $hash_key);
				$remaining_records = $total_rows - $i;
				$wpdb->get_results("UPDATE $log_table_name SET processing_records = $i , remaining_records = $remaining_records , status = 'Processing' WHERE hash_key = '$hash_key'");
				if ($i == $total_rows) {
					$wpdb->get_results("UPDATE $log_table_name SET status = 'Completed' WHERE hash_key = '$hash_key'");
				}

				if (count($core_instance->detailed_log) > $file_iteration) {
					$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
					$addHeader = false;
					$core_instance->detailed_log = [];
					$media_log = [];
					$failed_media_log = [];
				}

				$running = $wpdb->get_row("SELECT running FROM $log_table_name WHERE hash_key = '$hash_key' ");
				$check_pause = $running->running;
				if ($check_pause == 0) {

					update_option('smack_csvpro_paused_record_' . $hash_key, $i + 1);
					if (count($core_instance->detailed_log) > 0) {
						$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
					}
					$file_manager_instance->manage_records($hash_key, $selected_type, $file_name, $total_rows);
					$response['success'] = false;
					$response['pause_message'] = 'Record Paused';
					echo wp_json_encode($response);
					wp_die();
				}
			}
		}
		if (($unmatched_row == 'true') && ($page_number >= $total_pages)) {
			$post_entries_table = $wpdb->prefix . "post_entries";
			$post_entries_value = $wpdb->get_results("select ID from {$wpdb->prefix}post_entries_table ", ARRAY_A);
			$type = $wpdb->get_var("select type from {$wpdb->prefix}post_entries_table ");
			foreach ($post_entries_value as $product_id) {
				$test[] = $product_id['ID'];
			}

			$unmatched_object = new ExtensionHandler;
			$import_type = $unmatched_object->import_type_as($selected_type);
			$import_type_value = $unmatched_object->import_post_types($import_type);
			$import_name_as = $unmatched_object->import_name_as($import_type);
			if ($type == 'cct') {
				$jettable = $wpdb->prefix . 'jet_cct_' . $import_type;
				$get_total_row_count =  $wpdb->get_col("SELECT DISTINCT _ID FROM $jettable WHERE cct_status != 'trash' ");
				$unmatched_id = array_diff($get_total_row_count, $test);
				foreach ($unmatched_id as $keys => $values) {
					$wpdb->get_results("DELETE FROM $jettable WHERE `_ID`='$values' ");
				}
			} else {
				if ($import_type_value == 'category' || $import_type_value == 'post_tag' || $import_type_value == 'product_cat' || $import_type_value == 'product_tag') {

					$get_total_row_count =  $wpdb->get_col("SELECT term_id FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy = '$import_type_value'");
					$unmatched_id = array_diff($get_total_row_count, $test);

					foreach ($unmatched_id as $keys => $values) {
						$wpdb->get_results("DELETE FROM {$wpdb->prefix}terms WHERE `term_id` = '$values' ");
					}
				}
				if ($import_type_value == 'post' || $import_type_value == 'product' || $import_type_value == 'page' || $import_name_as == 'CustomPosts') {

					$get_total_row_count =  $wpdb->get_col("SELECT DISTINCT ID FROM {$wpdb->prefix}posts WHERE post_type = '{$import_type_value}' AND post_status != 'trash' ");
					if (is_array($test)) {
						$unmatched_id = array_diff($get_total_row_count, $test);
					}


					foreach ($unmatched_id as $keys => $values) {
						$wpdb->get_results("DELETE FROM {$wpdb->prefix}posts WHERE `ID` = '$values' ");
					}
				}
			}
			$wpdb->get_results("DELETE FROM {$wpdb->prefix}post_entries_table");
		}

		if (is_countable($core_instance->detailed_log) && count($core_instance->detailed_log) > 0) {
			$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
		}
		if (!empty($media_log[$line_number]) && count($media_log) > 0) {
			$media_link = $log_manager_instance->mediaExport($media_log, $line_number, $hash_key);
		}
		$file_manager_instance->manage_records($hash_key, $selected_type, $file_name, $total_rows);
		
		if (!empty($media_log[$line_number]) && count($media_log) > 0) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'failed_media';
			$data_to_insert = array();

			foreach ($media_log as $media_item) {
				$media_id = isset($media_item['media_id']) ? $media_item['media_id'] : null;
				$post_title = isset($media_item['title']) ? $media_item['title'] : null;
				$status = isset($media_item['status']) ? $media_item['status'] : null;
				$file_name = isset($media_item['file_name']) ? $media_item['file_name'] : null;
				$file_url = isset($media_item['file_url']) ? $media_item['file_url'] : null;
				$actual_url = isset($media_item['actual_url']) ? $media_item['actual_url'] : null;
				$caption = isset($media_item['caption']) ? $media_item['caption'] : null;
				$alt_text = isset($media_item['alt_text']) ? $media_item['alt_text'] : null;
				$description = isset($media_item['description']) ? $media_item['description'] : null;

				$data_to_insert[] = $wpdb->prepare(
					"(%s, %s, %d, %s, %s, %s, %s, %s, %s, %s)",
					$hash_key,
					$post_title,
					$media_id,
					$status,
					$file_url,
					$file_name,
					$actual_url,
					$caption,
					$alt_text,
					$description
				);
			}

			if (!empty($data_to_insert)) {
				$query = "INSERT INTO $table_name (event_id, title, media_id, status, file_url, file_name, actual_url, caption, alt_text, description) VALUES " . implode(", ", $data_to_insert);
				$wpdb->query($query);
			}
		}


		$log_value = $log_manager_instance->displayLogValue();

		

		//for log
		global $wpdb;
		$table_name = $wpdb->prefix . 'summary';

		//  data for batch insert
		$data_to_insert = array();

		foreach ($log_value as $item) {
			$inserted_id = isset($item['id']) ? $item['id'] : null;
			$post_title = isset($item['post_title']) ? $item['post_title'] : null;
			$post_type = isset($item['post_type']) ? $item['post_type'] : null;
			$status = isset($item['Status']) ? $item['Status'] : null;
			$total_images = isset($item['total_image']) ? $item['total_image'] : 0;
			$failed_images = isset($item['failed_image_count']) ? $item['failed_image_count'] : 0;

			// $categorie = ($post_type == 'Categories') ? 1 : 0;
			if ($post_type == 'Categories') {
				$categorie = 1;
			} elseif ($post_type == 'Tags'|| $post_type == 'Taxonomies') {

				$categorie = 2;
			} elseif ($post_type == 'users') {
				$categorie = 3;
			} elseif ($post_type == 'Comment'||$post_type == 'Reviews') {
				$categorie = 4;
			} else {
				$categorie = 0;
			}


			$data_to_insert[] = $wpdb->prepare(
				"(%d, %s, %s, %s, %s, %d, %d, %d)",
				$inserted_id,
				$hash_key,
				$post_title,
				$post_type,
				$status,
				$categorie,
				$total_images,
				$failed_images
			);
		}

		// Batch insert for boosting speed
		if (!empty($data_to_insert)) {
			$query = "INSERT INTO $table_name (post_id, event_id, post_title, post_type, status, is_category, associated_media, failed_media) VALUES " . implode(", ", $data_to_insert);
			$wpdb->query($query);
		}
		//for failed media log
		global $wpdb;
		$table_name = $wpdb->prefix . 'failed_media';

		if (!empty($failed_media_log)) {
			// Data for batch insert
			$data_to_insert = array();

			foreach ($failed_media_log as $item) {
				$inserted_id = $item['post_id'];
				$post_title = $item['post_title'];
				$media_id = $item['media_id'];
				$image_url = $item['actual_url'];
				$status = 'Failed';
				// Correct the number of placeholders and arguments
				$data_to_insert[] = $wpdb->prepare(
					"(%d, %s, %s,  %d, %s , %s)",
					$inserted_id,
					$hash_key,
					$post_title,
					$media_id,
					$image_url,
					$status
				);
			}

			// Batch insert for boosting speed
			if (!empty($data_to_insert)) {
				$query = "INSERT INTO $table_name (post_id, event_id, title,  media_id, actual_url,status) VALUES " . implode(", ", $data_to_insert);
				$wpdb->query($query);
			}
		}

		// $download_log_url = $log_manager_instance->Insert_log_details($log_value,$line_number,$hash_key);
		// $failed_media_link = $log_manager_instance->failedMediaExport($failed_media_log,$line_number,$hash_key);
		// if(isset($failed_media_link)){	
		// 	$response['media-url'] = $failed_media_link;
		// 	$response['media-progress'] = true;
		// }
		// else {
		// 	$response['media-progress'] = false;
		// }
		// $upload = wp_upload_dir();
		// $upload_base_url = $upload['baseurl'];
		// $upload_url = $upload_base_url . '/smack_uci_uploads/imports/';
		// $log_link_path = $upload_url . $hash_key . '/' . $hash_key . '.html';
		$response['success'] = true;
		// $response['log_link'] = $log_link_path;
		$response['media_link'] = $media_link ?? null;
		$response['download_log_link'] = $download_log_url ?? null;

		$response['log_value'] = $log_value;
		if ($rollback_option == 'true') {
			$response['rollback'] = true;
		}
		$total_records = $wpdb->get_results("SELECT status FROM $log_table_name WHERE hash_key = '$hash_key' ", ARRAY_A);
		if ($total_records[0]['status'] == 'Completed') {
			if (get_option('failed_line_number')) {
				delete_option('failed_line_number');
			}
			if(get_option('mapping_fields')){
				delete_option('mapping_fields');
			}
			if (get_option('total_attachment_ids')) {
				delete_option('total_attachment_ids');
			}
			if (get_option('failed_attachment_ids')) {
				delete_option('failed_attachment_ids');
			}
		}

		echo wp_json_encode($response);
		wp_die();
	}
	public function parse_element($xml, $query)
	{
		$query = strip_tags($query);
		$xpath = new \DOMXPath($xml);
		$entries = $xpath->query($query);
		$content = $entries->item(0)->textContent;
		return $content;
	}

	/**
	 * Starts the import process
	 */
	public function background_starts_function()
	{
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		global $wpdb, $core_instance;
		$hash_key  = sanitize_key($_POST['HashKey']);
		$check = sanitize_text_field($_POST['Check']);
		$rollback_option = sanitize_text_field($_POST['RollBack']);
		//$unmatched_row = $_POST['UnmatchedRow'];
		$file_iteration = get_option('sm_bulk_import_iteration_limit');
		$unmatched_row_value = get_option('sm_uci_pro_settings');
		$unmatched_row = $unmatched_row_value['unmatchedrow'];
		$upload_dir = SaveMapping::$smackcsv_instance->create_upload_dir();
		$import_txt_path = $upload_dir . 'import_state.txt';
		chmod($import_txt_path, 0777);
		$import_state_arr = array();
		$open_file = fopen($import_txt_path, "w");
		$import_state_arr = array('import_state' => 'on', 'import_stop' => 'on');
		$state_arr = serialize($import_state_arr);
		fwrite($open_file, $state_arr);
		fclose($open_file);
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		$import_config_instance = ImportConfiguration::getInstance();
		$file_manager_instance = FileManager::getInstance();
		$log_manager_instance = LogManager::getInstance();
		$file_table_name = $wpdb->prefix . "smackcsv_file_events";
		$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
		$log_table_name = $wpdb->prefix . "import_detail_log";
		$response = [];
		$background_values = $wpdb->get_results("SELECT mapping , module  FROM $template_table_name WHERE `eventKey` = '$hash_key' ");
		foreach ($background_values as $values) {
			$mapped_fields_values = $values->mapping;
			$selected_type = $values->module;
		}

		if ($rollback_option == 'true') {
			$tables = $import_config_instance->get_rollback_tables($selected_type);
			$import_config_instance->set_backup_restore($hash_key, 'backup', $tables);
		}

		$get_id = $wpdb->get_results("SELECT id , mode ,file_name , total_rows FROM $file_table_name WHERE `hash_key` = '$hash_key'");
		$get_mode = $get_id[0]->mode;
		$total_rows = $get_id[0]->total_rows;
		$file_name = $get_id[0]->file_name;
		$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
		if (empty($file_extension)) {
			$file_extension = 'xml';
		}
		if ($file_extension == 'xlsx' || $file_extension == 'xls' || $file_extension == 'json') {
			$file_extension = 'csv';
		}
		$file_size = filesize($upload_dir . $hash_key . '/' . $hash_key);
		$filesize = $helpers_instance->formatSizeUnits($file_size);

		$update_based_on = get_option('csv_importer_update_using');
		if (empty($update_based_on)) {
			$update_based_on = 'normal';
		}
		$gmode = 'Normal';
		$remain_records = $total_rows - 1;
		$wpdb->insert($log_table_name, array('file_name' => $file_name, 'hash_key' => $hash_key, 'total_records' => $total_rows, 'filesize' => $filesize, 'processing_records' => 1, 'remaining_records' => $remain_records, 'status' => 'Processing'));

		$map = unserialize($mapped_fields_values);

		if ($file_extension == 'csv' || $file_extension == 'txt') {

			if (version_compare(PHP_VERSION, '8.1.0', '<')) {  // Only do this if PHP version is less than 8.1.0
				if (!ini_get("auto_detect_line_endings")) {  // If auto_detect_line_endings is not enabled
					ini_set("auto_detect_line_endings", true);  // Enable it to handle different line endings
				}
			}
			$info = [];
			if (($h = fopen($upload_dir . $hash_key . '/' . $hash_key, "r")) !== FALSE) {
				// Convert each line into the local $data variable	
				$line_number = 0;
				$header_array = [];
				$value_array = [];
				$addHeader = true;
				$delimiters = array(',', '\t', ';', '|', ':', '&nbsp');
				$file_path = $upload_dir . $hash_key . '/' . $hash_key;
				$delimiter = SaveMapping::$validatefile->getFileDelimiter($file_path, 5);
				$array_index = array_search($delimiter, $delimiters);
				if ($array_index == 5) {
					$delimiters[$array_index] = ' ';
				}
				while (($data = fgetcsv($h, 0, $delimiters[$array_index], '"', '"')) !== FALSE) {
					// Read the data from a single line
					$trimmed_array = array_map('trim', $data);
					array_push($info, $trimmed_array);

					if ($line_number == 0) {
						$header_array = $info[$line_number];
					} else {
						$value_array = $info[$line_number];
						$get_arr = $this->main_import_process($map, $header_array, $value_array, $selected_type, $get_mode, $line_number, $unmatched_row, $check, $hash_key, $update_based_on, $gmode);
						$post_id = $get_arr['id'];
						$core_instance->detailed_log = $get_arr['detail_log'];
						$media_log = $get_arr['media_log'];
						$failed_media_log = $get_arr['failed_media_log'];
						$helpers_instance->get_post_ids($post_id, $hash_key);

						$remaining_records = $total_rows - $line_number;
						$wpdb->get_results("UPDATE $log_table_name SET processing_records = $line_number , remaining_records = $remaining_records , status = 'Processing' WHERE hash_key = '$hash_key'");

						if ($line_number == $total_rows) {
							$wpdb->get_results("UPDATE $log_table_name SET status = 'Completed' WHERE hash_key = '$hash_key'");
						}

						if (count($core_instance->detailed_log) > $file_iteration) {
							$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
							$addHeader = false;
							$core_instance->detailed_log = [];
							$media_log = [];
							$failed_media_log = [];
						}
					}
					// get the pause or resume state
					$open_txt = fopen($import_txt_path, "r");
					$read_text_ser = fread($open_txt, filesize($import_txt_path));
					$read_state = unserialize($read_text_ser);
					fclose($open_txt);

					if ($read_state['import_stop'] == 'off') {
						return;
					}

					while ($read_state['import_state'] == 'off') {
						$open_txts = fopen($import_txt_path, "r");
						$read_text_sers = fread($open_txts, filesize($import_txt_path));
						$read_states = unserialize($read_text_sers);
						fclose($open_txts);

						if ($read_states['import_state'] == 'on') {
							break;
						}

						if ($read_states['import_stop'] == 'off') {
							return;
						}
					}

					$line_number++;
				}
				fclose($h);
			}
		}
		if ($file_extension == 'tsv') {

			if (version_compare(PHP_VERSION, '8.1.0', '<')) {  // Only do this if PHP version is less than 8.1.0
				if (!ini_get("auto_detect_line_endings")) {  // If auto_detect_line_endings is not enabled
					ini_set("auto_detect_line_endings", true);  // Enable it to handle different line endings
				}
			}
			$info = [];
			if (($h = fopen($upload_dir . $hash_key . '/' . $hash_key, "r")) !== FALSE) {
				// Convert each line into the local $data variable	
				$line_number = 0;
				$header_array = [];
				$value_array = [];
				$addHeader = true;
				$delimiters = array(',', '\t', ';', '|', ':', '&nbsp');
				$file_path = $upload_dir . $hash_key . '/' . $hash_key;
				$delimiter = SaveMapping::$validatefile->getFileDelimiter($file_path, 5);
				while (($data = fgetcsv($h, 0, "\t", '"', '"')) !== FALSE) {
					// Read the data from a single line
					$trimmed_array = array_map('trim', $data);
					array_push($info, $trimmed_array);

					if ($line_number == 0) {
						$header_array = $info[$line_number];
					} else {
						$value_array = $info[$line_number];
						$get_arr = $this->main_import_process($map, $header_array, $value_array, $selected_type, $get_mode, $line_number, $unmatched_row, $check, $hash_key, $update_based_on, $gmode);
						$post_id = $get_arr['id'];
						$core_instance->detailed_log = $get_arr['detail_log'];
						$media_log = $get_arr['media_log'];
						$failed_media_log = $get_arr['failed_media_log'];
						$helpers_instance->get_post_ids($post_id, $hash_key);

						$remaining_records = $total_rows - $line_number;
						$wpdb->get_results("UPDATE $log_table_name SET processing_records = $line_number , remaining_records = $remaining_records , status = 'Processing' WHERE hash_key = '$hash_key'");

						if ($line_number == $total_rows) {
							$wpdb->get_results("UPDATE $log_table_name SET status = 'Completed' WHERE hash_key = '$hash_key'");
						}

						if (count($core_instance->detailed_log) > $file_iteration) {
							$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
							$addHeader = false;
							$core_instance->detailed_log = [];
							$media_log = [];
							$failed_media_log = [];
						}
					}
					// get the pause or resume state
					$open_txt = fopen($import_txt_path, "r");
					$read_text_ser = fread($open_txt, filesize($import_txt_path));
					$read_state = unserialize($read_text_ser);
					fclose($open_txt);

					if ($read_state['import_stop'] == 'off') {
						return;
					}

					while ($read_state['import_state'] == 'off') {
						$open_txts = fopen($import_txt_path, "r");
						$read_text_sers = fread($open_txts, filesize($import_txt_path));
						$read_states = unserialize($read_text_sers);
						fclose($open_txts);

						if ($read_states['import_state'] == 'on') {
							break;
						}

						if ($read_states['import_stop'] == 'off') {
							return;
						}
					}

					$line_number++;
				}
				fclose($h);
			}
		}

		if ($file_extension == 'xml') {
			$path = $upload_dir . $hash_key . '/' . $hash_key;
			$line_number = 0;
			$header_array = [];
			$value_array = [];
			$addHeader = true;
			for ($line_number = 0; $line_number < $total_rows; $line_number++) {
				$xml_class = new XmlHandler();
				$parse_xml = $xml_class->parse_xmls($hash_key, $line_number);
				$i = 0;
				foreach ($parse_xml as $xml_key => $xml_value) {
					if (is_array($xml_value)) {
						foreach ($xml_value as $e_key => $e_value) {
							$header_array['header'][$i] = $e_value['name'];
							$value_array['value'][$i] = $e_value['value'];
							$i++;
						}
					}
				}
				$xml = simplexml_load_file($path);
				$xml_arr = json_decode(json_encode($xml), 1);
				if (count($xml_arr) == count($xml_arr, COUNT_RECURSIVE)) {
					$item = $xml->addchild('item');
					foreach ($xml_arr as $key => $value) {
						$xml->item->addchild($key, $value);
						unset($xml->$key);
					}
					$arraytype = "not parent";
					$xmls['item'] = $xml_arr;
				} else {
					$arraytype = "parent";
				}
				$childs = array();
				$s = 0;
				foreach ($xml->children() as $child => $val) {

					$tag = (array)$val;
					if (empty($tag)) {
						if (!in_array($child, $childs, true)) {
							$childs[$s++] = $child;
						}
					} else {
						if (array_key_exists("@attributes", $tag)) {
							if (!in_array($child, $childs, true)) {
								$childs[$s++] = $child;
							}
						} else {
							foreach ($tag as $k => $v) {
								$checks = (string)$tag[$k];
								if (is_numeric($k)) {
									if (empty($checks)) {
										if (!in_array($child, $childs, true)) {
											$childs[$s++] = $child;
										}
									}
								} else {
									if (!empty($checks)) {
										if (!in_array($child, $childs, true)) {
											$childs[$s++] = $child;
										}
									}
								}
							}
						}
					}
				}

				$tag = current($childs);
				// $total_xml_count = $this->get_xml_count($path , $tag);
				if ($arraytype == "multi") {
					$total_xml_count = $this->get_xml_count($path, $tag);
				} else {
					$total_xml_count = 1;
				}
				foreach ($xml->children() as $child) {
					$child_names =  $child->getName();
				}
				if ($total_xml_count == 0) {
					$sub_child = $this->get_child($child, $path);
					$tag = $sub_child['child_name'];
					$total_xml_count = $sub_child['total_count'];
				}
				$doc = new \DOMDocument();
				$doc->load($path);
				foreach ($map as $field => $value) {
					foreach ($value as $head => $val) {
						if (preg_match('/{/', $val) && preg_match('/}/', $val)) {
							preg_match_all('/{(.*?)}/', $val, $matches);
							$line_numbers = $line_number + 1;
							$val = preg_replace("{" . "(" . $tag . "[+[0-9]+])" . "}", $tag . "[" . $line_numbers . "]", $val);
							for ($i = 0; $i < count($matches[1]); $i++) {
								$matches[1][$i] = preg_replace("{" . "(" . $tag . "[+[0-9]+])" . "}", $tag . "[" . $line_numbers . "]", $matches[1][$i]);
								$value = $this->parse_element($doc, $matches[1][$i], $line_number);
								$search = '{' . $matches[1][$i] . '}';
								$val = str_replace($search, $value, $val);
							}
							$mapping[$field][$head] = $val;
						} else {
							$mapping[$field][$head] = $val;
						}
					}
				}
				$get_arr = $this->main_import_process($mapping, $header_array['header'], $value_array['value'], $selected_type, $get_mode, $line_number, $unmatched_row, $check, $hash_key, $update_based_on, $gmode);
				$post_id = $get_arr['id'];
				$core_instance->detailed_log = $get_arr['detail_log'];
				$media_log = $get_arr['media_log'];
				$failed_media_log = $get_arr['failed_media_log'];
				$helpers_instance->get_post_ids($post_id, $hash_key);
				$line_numbers = $line_number + 1;
				$remaining_records = $total_rows - $line_numbers;
				$wpdb->get_results("UPDATE $log_table_name SET processing_records = $line_number + 1 , remaining_records = $remaining_records, status = 'Processing' WHERE hash_key = '$hash_key'");

				if ($line_number == $total_rows - 1) {
					$wpdb->get_results("UPDATE $log_table_name SET status = 'Completed' WHERE hash_key = '$hash_key'");
				}

				if (count($core_instance->detailed_log) > $file_iteration) {
					$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $line_number);
					$addHeader = false;
					$core_instance->detailed_log = [];
					$media_log = [];
					$failed_media_log = [];
				}

				$open_txt = fopen($import_txt_path, "r");
				$read_text_ser = fread($open_txt, filesize($import_txt_path));
				$read_state = unserialize($read_text_ser);
				fclose($open_txt);

				if ($read_state['import_stop'] == 'off') {
					return;
				}

				while ($read_state['import_state'] == 'off') {
					$open_txts = fopen($import_txt_path, "r");
					$read_text_sers = fread($open_txts, filesize($import_txt_path));
					$read_states = unserialize($read_text_sers);
					fclose($open_txts);

					if ($read_states['import_state'] == 'on') {
						break;
					}

					if ($read_states['import_stop'] == 'off') {
						return;
					}
				}
			}
		}

		if (count($core_instance->detailed_log) > 0) {
			$log_manager_instance->get_event_log($hash_key, $file_name, $file_extension, $get_mode, $total_rows, $selected_type, $core_instance->detailed_log, $addHeader);
		}
		$file_manager_instance->manage_records($hash_key, $selected_type, $file_name, $total_rows);

		$upload = wp_upload_dir();
		$upload_base_url = $upload['baseurl'];
		$upload_url = $upload_base_url . '/smack_uci_uploads/imports/';
		$log_link_path = $upload_url . $hash_key . '/' . $hash_key . '.html';
		$response['success'] = true;
		$response['log_link'] = $log_link_path;
		if ($rollback_option == 'true') {

			$response['rollback'] = true;
		}
		unlink($import_txt_path);
		echo wp_json_encode($response);
		wp_die();
	}

	public function get_child($child, $path)
	{
		foreach ($child->children() as $sub_child) {
			$sub_child_name = $sub_child->getName();
		}
		$total_xml_count = $this->get_xml_count($path, $sub_child_name);
		if ($total_xml_count == 0) {
			$this->get_child($sub_child, $path);
		} else {
			$result['child_name'] = $sub_child_name;
			$result['total_count'] = $total_xml_count;
			return $result;
		}
	}

	public function get_xml_count($eventFile, $child_name)
	{
		$doc = new \DOMDocument();
		$doc->load($eventFile);
		$nodes = $doc->getElementsByTagName($child_name);
		$total_row_count = $nodes->length;
		return $total_row_count;
	}
	public function manage_filteration($manage_filter, $header_array, $value_array, $core_instance, $line_number, $hash_key) {
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$log_table_name = $wpdb->prefix . "import_detail_log";
		$unikey_name = 'hash_key';
		$unikey_value = $hash_key;
		
		$updated_row_counts = $helpers_instance->update_count($unikey_value, $unikey_name);
		$skipped_count = $updated_row_counts['skipped'];
		
		$conditions = [];
		foreach ($manage_filter as $filter) {
			$element = $filter['element'];
			$rule = strtolower(trim($filter['rule']));
			$value = trim($filter['value']);
			$condition = isset($filter['condition']) ? strtoupper(trim($filter['condition'])) : '';
			
			$key = array_search($element, $header_array);
			if ($key === false) continue;
			
			$actual_value = trim($value_array[$key]);
			$match = false;
			switch ($rule) {
				case 'equals':
					$match = $actual_value == $value;
					break;
				case 'not_equals':
					$match = $actual_value != $value;
					break;
				case 'greater_than':
					$match = is_numeric($actual_value) && is_numeric($value) && $actual_value > $value;
					break;
				case 'less_than':
					$match = is_numeric($actual_value) && is_numeric($value) && $actual_value < $value;
					break;
				case 'equals_or_greater_than':
					$match = is_numeric($actual_value) && is_numeric($value) && $actual_value >= $value;
					break;
				case 'equals_or_less_than':
					$match = is_numeric($actual_value) && is_numeric($value) && $actual_value <= $value;
					break;
				case 'is_empty':
					$match = empty($actual_value);
					break;
				case 'is_not_empty':
					$match = !empty($actual_value);
					break;
				case 'contains':
					$match = str_contains($actual_value, $value);
					break;
				case 'not_contains':
					$match = !str_contains($actual_value, $value);
					break;
				default:
					$match = false;
			}

			$conditions[] = [
				"match" => (bool) $match,
				"condition" => $condition
			];

		}
		if (empty($conditions)) {
			return;
		}
		
		// First, evaluate AND conditions
		$filtered_conditions = [$conditions[0]['match']];
		for ($i = 0; $i < count($conditions) - 1; $i++) {
			if ($conditions[$i]['condition'] === 'AND') {
				$filtered_conditions[count($filtered_conditions) - 1] &= $conditions[$i + 1]['match'];
			} else {
				$filtered_conditions[] = $conditions[$i + 1]['match'];
			}
		}
		
		// Then evaluate OR conditions
		$result = false;
		foreach ($filtered_conditions as $value) {
			$result |= $value;
		}
		
		if (!$result) {
			$core_instance->detailed_log[$line_number] = [
				'Message' => "Skipped: Data does not match filter conditions.",
				'state' => 'Skipped'
			];
			$wpdb->query($wpdb->prepare("UPDATE $log_table_name SET skipped = %d WHERE $unikey_name = %s", $skipped_count, $unikey_value));
		}
	}

	public function main_import_process($map, $header_arrays, $value_array, $selected_type, $get_mode, $line_number, $unmatched_row, $check, $hash_key, $update_based_on, $gmode, $templatekey = null, $media_type = null,$check_manage_filter=null,$manage_filter=null)
	{
		$header_array = [];
		global $wpdb;
		foreach ($header_arrays as $header_values) {
			$header_array[] = rtrim($header_values, " ");
		}

		$return_arr = [];
		$core_instance = CoreFieldsImport::getInstance();
		$helpers_instance = ImportHelpers::getInstance();
		$post_values = [];
		$jetengine_map = [];
		$meta_data = '';
		$att_data = '';
		$post_id='';
		$woocom_image = '';
		$attr_data = '';
		$post_values = $helpers_instance->get_header_values($map['CORE'], $header_array, $value_array,$hash_key);
		global $core_instance;
		/*** check manage filteration */
		$check_manage_filter ? $this->manage_filteration($manage_filter,$header_array,$value_array,$core_instance,$line_number,$hash_key) : '';
		if (
			 preg_match("/Skipped/", (string)($core_instance->detailed_log[$line_number]['Message'] ?? '')) === 0 &&
             preg_match("/(Can't|Duplicate)/", (string)($core_instance->detailed_log[$line_number]['Message'] ?? '')) === 0
		) {
			if (is_plugin_active('jet-booking/jet-booking.php') && $selected_type == 'JetBooking'){
				$jet_booking_type = trim(jet_abaf()->settings->get( 'apartment_post_type' ));
			}
			foreach ($map as $group_name => $group_value) {
				if (array_key_exists('CORE', $map)) {
					if ($group_name == 'CORE') {
						$media_meta = isset($map['FEATURED_IMAGE_META']) ? $map['FEATURED_IMAGE_META'] : '';
						$acf_map = isset($map['ACF']) ? $map['ACF'] : '';
						$metabox_map = isset($map['METABOX']) ? $map['METABOX'] : '';
						$post_cat_list = isset($map['TERMS']) ? $map['TERMS'] : '';
						$types_map = isset($map['TYPES']) ? $map['TYPES'] : '';
						$wpml_map = isset($map['WPML']) ? $map['WPML'] : '';
						$pods_map = isset($map['PODS']) ? $map['PODS'] : '';
						$poly_map = isset($map["POLYLANG"]) ? $map["POLYLANG"] : '';
						/***update based on jet engine fields */
						if (isset($map["JE"])) {
							$jetengine_map = $map["JE"];
						}	
						if (isset($map["JECPT"])) {
							// Ensure $jetengine_map is an array before using array_merge
							$jetengine_map = isset($jetengine_map) && is_array($jetengine_map) ? $jetengine_map : [];
							$jetengine_map = array_merge($jetengine_map, $map["JECPT"]);
						}
						
						$core_instance = CoreFieldsImport::getInstance();
						if ($selected_type == 'WooCommerce Product Variations') {
							$post_values['VARIATIONSKU'] = isset($post_values['VARIATIONSKU']) ? $post_values['VARIATIONSKU'] : '';
	
							if (!empty($post_values['VARIATIONSKU']) || !empty($post_values['PARENTSKU'])) {
								$variation_value = !empty($post_values['VARIATIONSKU']) ? $post_values['VARIATIONSKU'] : $post_values['PARENTSKU'];
								$variation_count = explode('->', $variation_value);
								$variation_count = is_array($variation_count) ? $variation_count : [];
								$variation_id[] = $core_instance->set_core_values($header_array, $value_array, $map['CORE'], 'WooCommerce Product Variations', $get_mode, $line_number, $unmatched_row, $check, $hash_key, $acf_map, $metabox_map, $pods_map, $types_map, $jetengine_map, $update_based_on, $gmode, $variation_count, $wpml_map, $templatekey);
								if (!empty($variation_id)) {
									// Set for variation meta data
									$post_id = $variation_id[0];
								}
							}
	
							if(empty($post_values['VARIATIONSKU']) && $get_mode=='Update'){
								$variation_id[] = $core_instance->set_core_values($header_array, $value_array, $map['CORE'], 'WooCommerce Product Variations', $get_mode, $line_number, $unmatched_row, $check, $hash_key, $acf_map, $metabox_map, $pods_map, $types_map, $jetengine_map, $update_based_on, $gmode,'', $wpml_map, $templatekey);
								if (!empty($variation_id)) {
									//Set for variation meta data
									$post_id = $variation_id[0];
								}
							}
	
						} else {
							
								
										
								
										//    $map ['ATTRMETA'] = array(
										// 			'0' => array(
										// 				   'product_attribute_name1' => 'product_attribute_name1',
										// 				   'product_attribute_value1' => 'product_attribute_value1',
										// 				   'product_attribute_visible1' => 'product_attribute_visible1'),
										// 			'1' => array(
										// 					'product_attribute_name2' => 'product_attribute_name2',
										// 				   'product_attribute_value2' => 'product_attribute_value2',
										// 				   'product_attribute_visible2' => 'product_attribute_visible2'
										// 			),
										// 			'2' => array(
										// 					'product_attribute_name3' => 'product_attribute_name3',
										// 				   'product_attribute_value3' => 'product_attribute_value3',
										// 				   'product_attribute_visible3' => 'product_attribute_visible3'
										// 			   )
	
								
										// 			   );
										// 			//    $map ['ATTRMETA'] = array(
										// 			// 	'0' => array(
										// 			// 		   'product_attribute_name1' => 'Attribute 1 name',
										// 			// 		   'product_attribute_value1' => 'Attribute 1 value(s)',
										// 			// 		   'product_attribute_visible1' => 'Attribute 1 visible'),
										// 			// 	'1' => array(
										// 			// 			'product_attribute_name2' => 'Attribute 2 name',
										// 			// 		   'product_attribute_value2' => 'Attribute 2 value(s)',
										// 			// 		   'product_attribute_visible2' => 'Attribute 2 visible'
										// 			// 	   )
									
										// 			// 	   );
	
										
							if($selected_type=='WooCommerce Product'){
								$meta_data = isset($map['ECOMMETA'])?$map['ECOMMETA']:'';
								$attr_data = isset($map['ATTRMETA'])?$map['ATTRMETA']:'';
								$woocom_image = isset($map['PRODUCTIMAGEMETA']) ? $map['PRODUCTIMAGEMETA'] : '';
							}
							if ($selected_type == 'WooCommerce Orders' || ($selected_type == 'JetBooking' && $jet_booking_type == 'product')) {
								$post_id = $core_instance->set_core_values($header_array, $value_array, $map['CORE'], $selected_type, $get_mode, $line_number, $unmatched_row, $check, $hash_key, $acf_map, $metabox_map, $pods_map, $types_map, $jetengine_map, $update_based_on, $gmode, '', $wpml_map, $templatekey, $poly_map, $map['ORDERMETA'],'','','','',$post_cat_list,$this->isCategory,$this->categoryList);
							}
								else {
								$post_id = $core_instance->set_core_values($header_array, $value_array, $map['CORE'], $selected_type, $get_mode, $line_number, $unmatched_row, $check, $hash_key, $acf_map, $metabox_map, $pods_map, $types_map, $jetengine_map, $update_based_on, $gmode, '', $wpml_map, $templatekey, $poly_map, $meta_data, $media_meta, $media_type,$attr_data,$woocom_image,$post_cat_list,$this->isCategory,$this->categoryList);
							}
						}
					}
				} else {
					$acf_map = isset($map['ACF']) ? $map['ACF'] : '';
					$metabox_map = isset($map['METABOX']) ? $map['METABOX'] : '';
					global $wpdb;
					if ($update_based_on == 'acf') {
						foreach ($acf_map as $custom_key => $custom_value) {
							if (strpos($custom_value, '{') !== false && strpos($custom_value, '}') !== false) {
								$custom_value = $custom_key;
							}
							if ($custom_key == $check) {
								$get_key = array_search($custom_value, $header_array);
							}
							if (isset($value_array[$get_key])) {
								$csv_element = $value_array[$get_key];
							}
	
							$get_result = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta as a join {$wpdb->prefix}posts as b on a.post_id = b.ID WHERE a.meta_key = '$check' AND a.meta_value = '$csv_element' AND b.post_status = 'publish' order by a.post_id DESC ");
						}
						if (!empty($get_result)) {
							$post_id = $get_result[0]->post_id;
							$core_instance->detailed_log[$line_number]['Message'] = 'Updated ' . $selected_type . ' ID: ' . $post_id;
							//$core_instance->detailed_log[$line_number]['VERIFY'] = "<b> Click here to verify</b> - <a href='" . get_permalink( $post_id ) . "' target='_blank' title='" . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $post_values['post_title'] ) ) . "'rel='permalink'>Web View</a> | <a href='" . get_edit_post_link( $post_id ) . "'target='_blank' title='" . esc_attr( 'Edit this item' ) . "'>Admin View</a>";	
							$core_instance->detailed_log[$line_number]['status'] = 'publish';
							$core_instance->detailed_log[$line_number]['id'] = $post_id;
							$core_instance->detailed_log[$line_number]['webLink'] = get_permalink($post_id);
							$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link($post_id, true);
							$core_instance->detailed_log[$line_number]['state'] = 'Updated';
						} else {
							$core_instance->detailed_log[$line_number]['Message'] = 'Skipped,Due to existing field value is not presents.';
							$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						}
					}
					if ($update_based_on == 'metabox') {
						foreach ($metabox_map as $custom_key => $custom_value) {
							if (strpos($custom_value, '{') !== false && strpos($custom_value, '}') !== false) {
								$custom_value = $custom_key;
							}
							if ($custom_key == $check) {
								$get_key = array_search($custom_value, $header_array);
							}
							if (isset($value_array[$get_key])) {
								$csv_element = $value_array[$get_key];
							}
							$type = $selected_type;
							$get_metabox_fields = \rwmb_get_object_fields($type);
							$storage_type = isset($get_metabox_fields[$check]['storage']) ? $get_metabox_fields[$check]['storage'] : "";
							if ($storage_type != "" && isset($storage_type->table)) {
								$customtable = $storage_type->table;
								$get_result = $wpdb->get_results("SELECT c.ID FROM $customtable as c inner join {$wpdb->prefix}posts as p ON p.ID=c.ID where c.$check='$csv_element' and p.post_status!='trash' order by p.ID ASC");
							} else {
								$get_result = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta as a join {$wpdb->prefix}posts as b on a.post_id = b.ID WHERE a.meta_key = '$check' AND a.meta_value = '$csv_element' AND b.post_status != 'trash' order by a.post_id DESC ");
							}
						}
						if (!empty($get_result)) {
							if (isset($get_result[0]->post_id)) {
								$post_id = $get_result[0]->post_id;
							} else {
								$post_id = $get_result[0]->ID;
							}
							$core_instance->detailed_log[$line_number]['Message'] = 'Updated ' . $selected_type . ' ID: ' . $post_id;
							//$core_instance->detailed_log[$line_number]['VERIFY'] = "<b> Click here to verify</b> - <a href='" . get_permalink( $post_id ) . "' target='_blank' title='" . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $post_values['post_title'] ) ) . "'rel='permalink'>Web View</a> | <a href='" . get_edit_post_link( $post_id ) . "'target='_blank' title='" . esc_attr( 'Edit this item' ) . "'>Admin View</a>";	
							$core_instance->detailed_log[$line_number]['status'] = 'publish';
							$core_instance->detailed_log[$line_number]['id'] = $post_id;
							$core_instance->detailed_log[$line_number]['webLink'] = get_permalink($post_id);
							$core_instance->detailed_log[$line_number]['adminLink'] = get_edit_post_link($post_id, true);
							$core_instance->detailed_log[$line_number]['state'] = 'Updated';
						} else {
							$core_instance->detailed_log[$line_number]['Message'] = 'Skipped,Due to existing field value is not presents.';
							$core_instance->detailed_log[$line_number]['state'] = 'Skipped';
						}
					}
				}
			}
			if (!empty($post_id)) {
				foreach ($map as $group_name => $group_value) {
					switch ($group_name) {
						case 'ACF':
							$acf_image = isset($map['ACFIMAGEMETA']) ? $map['ACFIMAGEMETA'] : '';
							$acf_pro_instance = ACFProImport::getInstance();
							$poly_array = isset($map['POLYLANG']) ? $map['POLYLANG'] : [];
							$acf_pro_instance->set_acf_pro_values($header_array, $value_array, $map['ACF'], $acf_image, $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey, $poly_array);
							break;
	
						case 'RF':
							$acf_image = isset($map['ACFIMAGEMETA']) ? $map['ACFIMAGEMETA'] : '';
							$acf_pro_instance = ACFProImport::getInstance();
							$acf_pro_instance->set_acf_rf_values($header_array, $value_array, $map['RF'], $acf_image, $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'RRF':
							$acf_image = isset($map['ACFIMAGEMETA']) ? $map['ACFIMAGEMETA'] : '';
							$acf_pro_instance = ACFProImport::getInstance();
							$acf_pro_instance->set_acf_rrf_values($header_array, $value_array, $map['RRF'], $acf_image, $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break; 
						case 'JEREVIEW':
							$jet_engine_instance = JetReviewsImport::getInstance();
							$jet_engine_instance->set_jet_reviews_values($header_array, $value_array, $map['JEREVIEW'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'JEBOOKING':
							$jet_engine_instance = JetBookingImport::getInstance();
							$jet_engine_instance->set_jet_booking_values($header_array, $value_array, $map['JEBOOKING'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'JE':
							$jet_engine_instance = JetEngineImport::getInstance();
							$jet_engine_instance->set_jet_engine_values($header_array, $value_array, $map['JE'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'JERF':
							$jet_engine_instance = JetEngineImport::getInstance();
							$jet_engine_instance->set_jet_engine_rf_values($header_array, $value_array, $map['JERF'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'JECPT':
							$jet_engine_cpt_instance = JetEngineCPTImport::getInstance();
							$jet_engine_cpt_instance->set_jet_engine_cpt_values($header_array, $value_array, $map['JECPT'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'JECPTRF':
							$jet_engine_cpt_instance = JetEngineCPTImport::getInstance();
							$jet_engine_cpt_instance->set_jet_engine_cpt_rf_values($header_array, $value_array, $map['JECPTRF'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'JECCT':
							$jet_engine_cct_instance = JetEngineCCTImport::getInstance();
							$jet_engine_cct_instance->set_jet_engine_cct_values($header_array, $value_array, $map['JECCT'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'JECCTRF':
							$jet_engine_cct_instance = JetEngineCCTImport::getInstance();
							$jet_engine_cct_instance->set_jet_engine_cct_rf_values($header_array, $value_array, $map['JECCTRF'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
	
						case 'JETAX':
							$jet_engine_tax_instance = JetEngineTAXImport::getInstance();
							$jet_engine_tax_instance->set_jet_engine_tax_values($header_array, $value_array, $map['JETAX'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'JETAXRF':
							$jet_engine_tax_instance = JetEngineTAXImport::getInstance();
							$jet_engine_tax_instance->set_jet_engine_tax_rf_values($header_array, $value_array, $map['JETAXRF'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'JEREL':
							$jet_engine_rel_instance = JetEngineRELImport::getInstance();
							$jet_engine_rel_instance->set_jet_engine_rel_values($header_array, $value_array, $map['JEREL'], $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
						case 'PODS':
							$pods_image = isset($map['PODSIMAGEMETA']) ? $map['PODSIMAGEMETA'] : '';
							$map['WPML'] = isset($map['WPML']) ? $map['WPML'] : '';
							$pods_instance = PodsImport::getInstance();
							$pods_instance->set_pods_values($header_array, $value_array, $map['PODS'], $pods_image, $post_id, $selected_type, $hash_key, $gmode, $templatekey, $map['WPML'], $line_number);
							break;
	
						case 'AIOSEO':
							$all_seo_instance = AllInOneSeoImport::getInstance();
							$all_seo_instance->set_all_seo_values($header_array, $value_array, $map['AIOSEO'], $post_id, $selected_type, $get_mode);
							break;
	
    case 'SLIMSEO':
        $slimseo_instance = SlimSeoImport::getInstance();
        $slimseo_instance->set_slimseo_values(
            $header_array,
            $value_array,
            $map['SLIMSEO'],
            $post_id,
            $selected_type,
            $hash_key,
            $gmode,
            $templatekey,
            $line_number
        );
        break;

						case 'YOASTSEO':
							$yoast_instance = YoastSeoImport::getInstance();
							$yoast_instance->set_yoast_values($header_array, $value_array, $map['YOASTSEO'], $post_id, $selected_type, $hash_key, $gmode, $templatekey, $line_number);
							break;
						case 'WPCOMPLETE':
							$wpcomplete_instance = WPCompleteImport::getInstance();
							$wpcomplete_instance->set_wpcomplete_values($header_array, $value_array, $map['WPCOMPLETE'], $post_id, $selected_type, $hash_key, $gmode, $templatekey);
							break;
	
						case 'SEOPRESS':
							$seopress_instance = SeoPressImport::getInstance();
							$seopress_instance->set_seopress_values($header_array, $value_array, $map['SEOPRESS'], $post_id, $selected_type, $hash_key, $gmode, $templatekey);
							break;
						case 'ELEMENTOR':
							$elementor_instance = ElementorImport::getInstance();
							$elementor_instance->set_elementor_value($header_array, $value_array, $map['ELEMENTOR'], $post_id, $selected_type, $hash_key, $gmode, $templatekey);
							break;
	
							//new
						case 'RANKMATH':
							$rankmath_instance = RankMathImport::getInstance();
							$rankmath_instance->set_rankmath_values($header_array, $value_array, $map['RANKMATH'], $post_id, $selected_type);
							break;
						
						case 'LISTINGMETA':
							$listing_instance = ListingImport::getInstance();
							$listing_instance->set_listing_values($header_array, $value_array, $map['LISTINGMETA'], $post_id, $selected_type);
							break;
	
						case 'PROPERTYMETA':
							$listing_instance = PropertyListingImport::getInstance();
							
							$listing_instance->set_property_values($header_array, $value_array, $map['PROPERTYMETA'], $post_id, $selected_type);
							break;

						case 'OWNERMETA':
							$listing_instance = PropertyListingImport::getInstance();
						
							$listing_instance->set_property_values($header_array, $value_array, $map['OWNERMETA'], $post_id, $selected_type);
							break;
						// case 'ECOMMETA':
						// 	// $woocom_image = isset($map['PRODUCTIMAGEMETA']) ? $map['PRODUCTIMAGEMETA'] : '';
						// 	$woocom_image = isset($map['PRODUCTIMAGEMETA']) ? $map['PRODUCTIMAGEMETA'] : [];
						// 	$product_meta_instance = ProductMetaImport::getInstance();
						// 	$variation_id = isset($variation_id) ? $variation_id : '';
						// 	$poly_array = isset($map['POLYLANG']) ? $map['POLYLANG'] : [];
						// 	$product_meta_instance->set_product_meta_values($header_array, $value_array, $map['ECOMMETA'], $woocom_image, $post_id, $variation_id, $selected_type, $line_number, $get_mode, $map['CORE'], $hash_key, $gmode, $templatekey, $poly_array);
						// 	break;
	
						// 	//added for woocommerce product attributes separate widget
						// 	case 'ATTRMETA':
						// 		//$product_attr_instance = ProductAttrImport::getInstance();
						// 		$product_meta_instance = ProductMetaImport::getInstance();
						// 		$type = 'WooCommerce Attribute'; 
						// 		$variation_id = isset($variation_id) ? $variation_id : '';
						// 		$wpml_map = isset($map['WPML']) ? $map['WPML'] : '';
						// 		$poly_array = isset($map['POLYLANG']) ? $map['POLYLANG'] : [];
						// 		// $woocom_image = isset($map['PRODUCTIMAGEMETA']) ? $map['PRODUCTIMAGEMETA'] : '';
						// 		$woocom_image = isset($map['PRODUCTIMAGEMETA']) ? $map['PRODUCTIMAGEMETA'] : [];
						// 		$product_meta_instance->set_product_meta_values($header_array, $value_array, $map['ATTRMETA'], $woocom_image, $post_id, $variation_id, $type, $line_number, $get_mode, $map['CORE'],$hash_key, $gmode, $templatekey, $poly_array,$selected_type);
						// 		break;
		
							case 'BUNDLEMETA':
								// $woocom_image = isset($map['PRODUCTIMAGEMETA']) ? $map['PRODUCTIMAGEMETA'] : '';
								$woocom_image = isset($map['PRODUCTIMAGEMETA']) ? $map['PRODUCTIMAGEMETA'] : [];
								$variation_id = isset($variation_id) ? $variation_id : '';
								$poly_array = isset($map['POLYLANG']) ? $map['POLYLANG'] : [];
								$product_bundle_meta_instance = ProductBundleMetaImport::getInstance();
								$product_bundle_meta_instance->set_product_bundle_meta_values($header_array, $value_array, $map['BUNDLEMETA'], $woocom_image, $post_id, $variation_id, $selected_type, $line_number, $get_mode, $map['CORE'], $hash_key, $gmode, $templatekey, $poly_array);
								break;
								
						case 'PPOMMETA':
								$product_meta_instance = ProductMetaImport::getInstance();
								$meta_type = 'PPOMMETA';
								$product_meta_instance->set_product_meta_values($header_array, $value_array, $map['PPOMMETA'],$map['IMAGEMETA'], $post_id, '', $meta_type, $line_number, $get_mode, $map['CORE'],$hash_key, $gmode, $templatekey, $poly_array);
								break;
				
						case 'EPOMETA':
								$product_meta_instance = ProductMetaImport::getInstance();
								$meta_type = 'EPOMETA';
								$product_meta_instance->set_product_meta_values($header_array, $value_array, $map['EPOMETA'], $map['IMAGEMETA'], $post_id, '', $meta_type, $line_number, $get_mode, $map['CORE'], $hash_key, $gmode, $templatekey, $poly_array);
									break;
	
						case 'WCPAMETA' :
								$product_meta_instance = ProductMetaImport::getInstance();
								$meta_type = 'WCPAMETA';
								$product_meta_instance->set_product_meta_values($header_array, $value_array, $map['WCPAMETA'], $map['IMAGEMETA'], $post_id, '', $meta_type, $line_number, $get_mode, $map['CORE'], $hash_key, $gmode, $templatekey, $poly_array);
									break;	
									
						case 'FPFMETA' :
								$product_meta_instance = ProductMetaImport::getInstance();
								$meta_type = 'FPFMETA';
								$product_meta_instance->set_product_meta_values($header_array, $value_array, $map['FPFMETA'], $map['IMAGEMETA'], $post_id, '', $meta_type, $line_number, $get_mode, $map['CORE'], $hash_key, $gmode, $templatekey, $poly_array);
									break;
								
						case 'REFUNDMETA':
							$product_meta_instance = ProductMetaImport::getInstance();
							$poly_array = isset($map['POLYLANG']) ? $map['POLYLANG'] : [];
							$product_meta_instance->set_product_meta_values($header_array, $value_array, $map['REFUNDMETA'], $map['IMAGEMETA'], $post_id, $variation_id, $selected_type, $line_number, $get_mode, $map['CORE'], $hash_key, $gmode, $templatekey, $poly_array);
							break;
	
							// case 'ORDERMETA':
							// 	$map['IMAGEMETA']=isset($map['IMAGEMETA'])?$map['IMAGEMETA']:'';
							// 	$variation_id=isset($variation_id)?$variation_id:'';
							// 	$product_meta_instance = ProductMetaImport::getInstance();
							// 	$poly_array = isset($map['POLYLANG']) ? $map['POLYLANG'] : [];
							// 	$product_meta_instance->set_product_meta_values($header_array, $value_array, $map['ORDERMETA'], $map['IMAGEMETA'], $post_id,$variation_id, $selected_type, $line_number, $get_mode, $map['CORE'], $hash_key,$gmode,$templatekey,$poly_array);
							// 	break;
	
						case 'COUPONMETA':
							$map['IMAGEMETA'] = isset($map['IMAGEMETA']) ? $map['IMAGEMETA'] : '';
							$variation_id = isset($variation_id) ? $variation_id : '';
							$product_meta_instance = ProductMetaImport::getInstance();
							$poly_array = isset($map['POLYLANG']) ? $map['POLYLANG'] : [];
							$product_meta_instance->set_product_meta_values($header_array, $value_array, $map['COUPONMETA'], $map['IMAGEMETA'], $post_id, $variation_id, $selected_type, $line_number, $get_mode, $map['CORE'], $hash_key, $gmode, $templatekey, $poly_array);
							break;
	
						case 'CCTM':
							$cctm_instance = CCTMImport::getInstance();
							$cctm_instance->set_cctm_values($header_array, $value_array, $map['CCTM'], $post_id, $selected_type);
							break;
	
						case 'CFS':
							$cfs_instance = CFSImport::getInstance();
							$cfs_instance->set_cfs_values($header_array, $value_array, $map['CFS'], $post_id, $selected_type, $hash_key, $gmode, $templatekey, $line_number);
							break;
	
						case 'CMB2':
							$cmb2_instance = CMB2Import::getInstance();
							$cmb2_instance->set_cmb2_values($header_array, $value_array, $map['CMB2'], $post_id, $selected_type, $hash_key, $gmode, $templatekey, $line_number);
							break;
	
						case 'BSI':
							$bsi_instance = BSIImport::getInstance();
							$bsi_instance->set_bsi_values($header_array, $value_array, $map['BSI'], $post_id, $selected_type);
							break;
	
						case 'WPMEMBERS':
							$wpmembers_instance = WPMembersImport::getInstance();
							$wpmembers_instance->set_wpmembers_values($header_array, $value_array, $map['WPMEMBERS'], $post_id, $selected_type, $hash_key, $gmode, $templatekey, $line_number);
							break;
	
						case 'MEMBERS':
							$multirole_instance = MultiroleImport::getInstance();
							$multirole_instance->set_multirole_values($header_array, $value_array, $map['MEMBERS'], $post_id, $selected_type);
							break;
	
						case 'ULTIMATEMEMBER':
							$ultimate_instance = UltimateImport::getInstance();
							$ultimate_instance->set_ultimate_values($header_array, $value_array, $map['ULTIMATEMEMBER'], $post_id, $selected_type);
							break;
	
						case 'WPECOMMETA':
							$wpecom_custom_instance = WPeComCustomImport::getInstance();
							$wpecom_custom_instance->set_wpecom_custom_values($header_array, $value_array, $map['WPECOMMETA'], $post_id, $selected_type);
							break;
	
						case 'TERMS':
							$wpml_map = isset($map['WPML']) ? $map['WPML'] : '';
							$poly_array = isset($map['POLYLANG']) ? $map['POLYLANG'] : '';
							$terms_taxo_instance = TermsandTaxonomiesImport::getInstance();
							$terms_taxo_instance->set_terms_taxo_values($header_array, $value_array, $map['TERMS'], $post_id, $selected_type, $get_mode, $gmode, $line_number, $wpml_map, $poly_array);
							break;
	
						case 'WPML':
							if (
								$selected_type != 'category' || $selected_type != 'post_tag' || $selected_type != 'product_cat' || $selected_type != 'product_tag'
								|| $selected_type != 'wpsc_product_category' || $selected_type != 'event-categories' || $selected_type != 'event-tags'
							) {
								$wpml_instance = WPMLImport::getInstance();
								$wpml_instance->set_wpml_values($header_array, $value_array, $map['WPML'], $post_id, $selected_type, $line_number);
							}
							break;
	
						case 'CORECUSTFIELDS':
							$wordpress_custom_instance = WordpressCustomImport::getInstance();
							$wordpress_custom_instance->set_wordpress_custom_values($header_array, $value_array, $map['CORECUSTFIELDS'], $post_id, $selected_type, $group_name, $hash_key, $gmode, $templatekey, $line_number);
							break;
	
						case 'DPF':
							$instance = WordpressCustomExtension::getInstance();
							$instance->processExtension($data);
							break;
	
						case 'EVENTS':
							if (is_plugin_active('events-manager/events-manager.php') && $selected_type == 'event') {
							$merge = [];
							$merge = array_merge($map['CORE'], $map['EVENTS']);
							$map['TERMS'] = isset($map['TERMS']) ? $map['TERMS'] : '';
							$events_instance = EventsManagerImport::getInstance();
							$events_instance->set_events_values($header_array, $value_array, $merge, $post_id, $selected_type, $get_mode, $map['TERMS'], $gmode);
							break;
							}elseif (is_plugin_active('the-events-calendar/the-events-calendar.php') && $selected_type == 'tribe_events') {
							$merge = [];
							$merge = array_merge($map['CORE'], $map['EVENTS']);
							$map['TERMS'] = isset($map['TERMS']) ? $map['TERMS'] : '';
							$events_instance = EventCalendarImport::getInstance();
							$events_instance->set_events_values($header_array, $value_array, $merge, $post_id, $selected_type, $get_mode, $map['TERMS'], $gmode);
							break;
							}
	
						case 'NEXTGEN':
							$nextgen_import = SaveMapping::$nextgen_instance->nextgenImport($header_array, $value_array, $map['NEXTGEN'], $post_id, $selected_type, $hash_key);
							break;
	
						case 'COREUSERCUSTFIELDS':
							$wordpress_custom_instance = WordpressCustomImport::getInstance();
							$wordpress_custom_instance->set_wordpress_custom_values($header_array, $value_array, $map['COREUSERCUSTFIELDS'], $post_id, $selected_type, $group_name, $hash_key, $gmode, $templatekey, $line_number);
							break;
	
						case 'LPCOURSE':
							//case 'LPCURRICULUM':
							$learn_merge = [];
							$learn_merge = array_merge($map['LPCOURSE'], $map['LPCURRICULUM']);
							$learnpress_instance = LearnPressImport::getInstance();
							$learnpress_instance->set_learnpress_values($header_array, $value_array, $learn_merge, $post_id, $selected_type, $get_mode);
							break;
	
						case 'FC':
							$acf_image = isset($map['ACFIMAGEMETA']) ? $map['ACFIMAGEMETA'] : '';
							$acf_pro_instance = ACFProImport::getInstance();
							$acf_pro_instance->set_acf_fc_values($header_array, $value_array, $map['FC'], $acf_image, $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
	
						case 'GF':
							$acf_image = isset($map['ACFIMAGEMETA']) ? $map['ACFIMAGEMETA'] : '';
							$acf_pro_instance = ACFProImport::getInstance();
							$acf_pro_instance->set_acf_gf_values($header_array, $value_array, $map['GF'], $acf_image, $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
	
						case 'TYPES':
							$types_image = isset($map['TYPESIMAGEMETA']) ? $map['TYPESIMAGEMETA'] : '';
							$toolset_instance = ToolsetImport::getInstance();
							$toolset_instance->set_toolset_values($header_array, $value_array, $map['TYPES'], $types_image, $post_id, $selected_type, $get_mode, $hash_key, $line_number, $gmode, $templatekey);
							break;
	
						case 'LPLESSON':
							$learnpress_instance = LearnPressImport::getInstance();
							$learnpress_instance->set_learnpress_values($header_array, $value_array, $map['LPLESSON'], $post_id, $selected_type, $get_mode);
							break;
	
						case 'LPQUIZ':
							$learnpress_instance = LearnPressImport::getInstance();
							$learnpress_instance->set_learnpress_values($header_array, $value_array, $map['LPQUIZ'], $post_id, $selected_type, $get_mode);
							break;
	
						case 'LPQUESTION':
							$learnpress_instance = LearnPressImport::getInstance();
							$learnpress_instance->set_learnpress_values($header_array, $value_array, $map['LPQUESTION'], $post_id, $selected_type, $get_mode);
							break;
	
						case 'LPORDER':
							$learnpress_instance = LearnPressImport::getInstance();
							$learnpress_instance->set_learnpress_values($header_array, $value_array, $map['LPORDER'], $post_id, $selected_type, $get_mode);
							break;
	
						case 'LIFTERLESSON':
							$lifterlms_instance = LifterLmsImport::getInstance();
							$lifterlms_instance->set_lifterlms_values($header_array, $value_array, $map['LIFTERLESSON'], $post_id, $selected_type, $get_mode);
							break;
	                    case 'LIFTERQUIZ':
							$lifterlms_instance = LifterLmsImport::getInstance();
							$lifterlms_instance->set_lifterlms_values($header_array, $value_array, $map['LIFTERQUIZ'], $post_id, $selected_type, $get_mode);
							break;
						case 'LIFTERCOURSE':
							$lifterlms_instance = LifterLmsImport::getInstance();
							$lifterlms_instance->set_lifterlms_values($header_array, $value_array, $map['LIFTERCOURSE'], $post_id, $selected_type, $get_mode);
							break;
	
						case 'LIFTERCOUPON':
							$lifterlms_instance = LifterLmsImport::getInstance();
							$lifterlms_instance->set_lifterlms_values($header_array, $value_array, $map['LIFTERCOUPON'], $post_id, $selected_type, $get_mode);
							break;
	
						case 'STMCOURSE':
							$stm_merge = [];
							$stm_merge = array_merge($map['STMCOURSE'], $map['STMCURRICULUM']);
							$stm_instance = MasterStudyLMSImport::getInstance();
							$stm_instance->set_stm_values($header_array, $value_array, $stm_merge, $post_id, $selected_type, $get_mode);
							break;
	
						case 'STMLESSON':
							$stm_instance = MasterStudyLMSImport::getInstance();
							$stm_instance->set_stm_values($header_array, $value_array, $map['STMLESSON'], $post_id, $selected_type, $get_mode);
							break;
	
						case 'STMQUIZ':
							$stm_instance = MasterStudyLMSImport::getInstance();
							$stm_instance->set_stm_values($header_array, $value_array, $map['STMQUIZ'], $post_id, $selected_type, $get_mode);
							break;
	
						case 'STMQUESTION':
							$stm_instance = MasterStudyLMSImport::getInstance();
							$stm_instance->set_stm_values($header_array, $value_array, $map['STMQUESTION'], $post_id, $selected_type, $get_mode);
							break;
	
						case 'STMORDER':
							$stm_instance = MasterStudyLMSImport::getInstance();
							$stm_instance->set_stm_values($header_array, $value_array, $map['STMORDER'], $post_id, $selected_type, $get_mode);
							break;
	
						case 'FORUM':
							$bbpress_instance = BBPressImport::getInstance();
							$bbpress_instance->set_bbpress_values($header_array, $value_array, $map['FORUM'], $post_id, $selected_type);
							break;
	
						case 'TOPIC':
							$bbpress_instance = BBPressImport::getInstance();
							$bbpress_instance->set_bbpress_values($header_array, $value_array, $map['TOPIC'], $post_id, $selected_type);
							break;
	
						case 'REPLY':
							$bbpress_instance = BBPressImport::getInstance();
							$bbpress_instance->set_bbpress_values($header_array, $value_array, $map['REPLY'], $post_id, $selected_type);
							break;
						case 'POLYLANG':
							$polylang_instance = PolylangImport::getInstance();
							$polylang_instance->set_polylang_values($header_array, $value_array, $map['POLYLANG'], $post_id, $selected_type, $get_mode);
							break;
						case 'METABOX':
							$metabox_instance = MetaBoxImport::getInstance();
							$metabox_instance->set_metabox_values($header_array, $value_array, $map['METABOX'], $post_id, $selected_type, $line_number, $hash_key, $gmode, $templatekey);
							break;
						case 'ACPT':
							$acpt_instance = ACPTImport::getInstance();
							$acpt_instance->set_acpt_values($header_array, $value_array, $map['ACPT'], $post_id, $selected_type, $line_number, $hash_key, $gmode, $templatekey);
							break;
						case 'METABOXRELATION':
							$metabox_relations_instance = MetaBoxRelationsImport::getInstance();
							$wpml_map = isset($map['WPML']) ? $map['WPML'] : '';
							$metabox_relations_instance->set_metabox_relations_values($header_array, $value_array, $map['METABOXRELATION'], $post_id, $selected_type, $get_mode, $wpml_map);
							break;
						case 'METABOXGROUP':
							$metabox_relations_instance = MetaBoxGroupImport::getInstance();
							$metabox_relations_instance->set_metabox_group_values($header_array, $value_array, $map['METABOXGROUP'], $post_id, $selected_type, $get_mode, $line_number,$hash_key);
							break;
						case 'JOB':
							$job_listing_instance = JobListingImport::getInstance();
							$job_listing_instance->set_job_listing_values($header_array, $value_array, $map['JOB'], $post_id, $selected_type);
							break;
						case 'FIFUPOSTS':
							$fifu_instance = FIFUImport::getInstance();
							$fifu_instance->set_fifu_values($header_array, $value_array, $map['FIFUPOSTS'], $post_id, $selected_type, $get_mode);
							break;
						case 'FIFUPAGE':
							$fifu_instance = FIFUImport::getInstance();
							$fifu_instance->set_fifu_values($header_array, $value_array, $map['FIFUPAGE'], $post_id, $selected_type, $get_mode);
							break;
						case 'FIFUCUSTOMPOST':
							$fifu_instance = FIFUImport::getInstance();
							$fifu_instance->set_fifu_values($header_array, $value_array, $map['FIFUCUSTOMPOST'], $post_id, $selected_type, $get_mode);
							break;
					}
				}
				if (get_option('total_attachment_ids')) {
					$stored_ids = unserialize(get_option('total_attachment_ids', ''));
					delete_option('total_attachment_ids');
					$core_instance->detailed_log[$line_number]['total_image'] = (is_array($stored_ids) && count($stored_ids) > 0) ? count($stored_ids) : '';
					$core_instance->detailed_log[$line_number]['failed_image_count'] = null;
				}
				if (get_option('failed_attachment_ids')) {
					$stored_ids = unserialize(get_option('failed_attachment_ids', ''));
					delete_option('failed_attachment_ids');
					$core_instance->detailed_log[$line_number]['failed_image_count'] = (is_array($stored_ids) && count($stored_ids) > 0) ? count($stored_ids) : '';
				}
			}
			$return_arr['failed_media_log'] = 	!empty($core_instance->failed_media_data) ? $core_instance->failed_media_data : [];
			$return_arr['media_log'] = 	!empty($core_instance->media_log) ? $core_instance->media_log : [];
			$return_arr['id'] = $post_id;
		}
		$return_arr['detail_log'] = $core_instance->detailed_log;
		return $return_arr;
	}

	public	function bulk_file_import_function()
	{
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$hash_key = sanitize_key($_POST['HashKey']);
		$highspeed = sanitize_text_field($_POST['highspeed']);
		$piecebypiece = sanitize_text_field($_POST['PieceByPiece']);
		$fileiteration = sanitize_text_field($_POST['FileIteration']);
		$splitchunks = sanitize_text_field($_POST['SplitChunks']);
		$operation_mode = get_option("smack_operation_mode_" . $hash_key);
		$server_software = sanitize_text_field($_SERVER['SERVER_SOFTWARE']);
		if ($operation_mode == 'simpleMode') {
			$image_included = get_option("SMACK_IMAGE_INCLUDED_" . $hash_key);

			if ($image_included == 'true') {
				//$fileiteration = '5';
				update_option('sm_bulk_import_iteration_limit', $fileiteration);
			} else {
				//$fileiteration = '15';
				update_option('sm_bulk_import_iteration_limit', $fileiteration);
			}
		} else {
			if ($highspeed == 'true') {
				//$fileiteration = '25';
				update_option('sm_bulk_import_iteration_limit', $fileiteration);
			}
			if ($piecebypiece == 'true') {
				//$fileiteration = intval($_POST['FileIteration']);
				update_option('sm_bulk_import_iteration_limit', $fileiteration);
			} elseif (strstr($server_software, 'nginx')) {
				//$fileiteration = '5';
				update_option('sm_bulk_import_iteration_limit', $fileiteration);
			}
		}

		// $iteration_limit=get_option('sm_bulk_import_iteration_limit');
		//$iteration_limit = 5;

		$file_table_name = $wpdb->prefix . "smackcsv_file_events";
		$get_id = $wpdb->get_results("SELECT id , mode ,file_name , total_rows FROM $file_table_name WHERE `hash_key` = '$hash_key'");
		$file_name = $get_id[0]->file_name;
$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
if (empty($file_extension)) {
    $file_extension = 'xml';
}
		$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";

		if ($file_extension == 'xml') {

			$total_rows = json_decode($get_id[0]->total_rows);
			$background_values = $wpdb->get_results("SELECT mapping , module  FROM $template_table_name WHERE `eventKey` = '$hash_key' ");
			foreach ($background_values as $values) {
				$mapped_fields_values = $values->mapping;
				$selected_type = $values->module;
			}
			$map = unserialize($mapped_fields_values);
			$upload_dir = SaveMapping::$smackcsv_instance->create_upload_dir();
	$path = $upload_dir . $hash_key . '/' . $hash_key;

if (!file_exists($path)) {
    $response['error'] = 'Import file not found. Please check if the URL/file was downloaded properly.';
    echo wp_json_encode($response);
    wp_die();
}

libxml_use_internal_errors(true); 
$xml = simplexml_load_file($path);
if (!$xml) {
    $response['error'] = 'Failed to parse the XML file.';
    echo wp_json_encode($response);
    wp_die();
}

			$xml_arr = json_decode(json_encode($xml), 1);
			if (count($xml_arr) == count($xml_arr, COUNT_RECURSIVE)) {
				$item = $xml->addchild('item');
				foreach ($xml_arr as $key => $value) {
					$xml->item->addchild($key, $value);
					unset($xml->$key);
				}
				$arraytype = "not parent";
				$xmls['item'] = $xml_arr;
			} else {
				$arraytype = "parent";
			}
			$i = 0;
			$childs = array();

			// Loop through XML children
			foreach ($xml->children() as $child => $val) {
				$values = (array) $val;
				
				if (empty($values)) {
					if (!in_array($child, $childs, true)) {
						$childs[$i++] = $child;
					}
				} else {
					if (array_key_exists("@attributes", $values)) {
						if (!in_array($child, $childs, true)) {
							$childs[$i++] = $child;
						}
					} else {
						foreach ($values as $k => $v) {
							is_array($values[$k]) ? $checks = implode(',', $values[$k]) : $checks = (string) $values[$k];
							
							if (is_numeric($k)) {
								if (empty($checks)) {
									if (!in_array($child, $childs, true)) {
										$childs[$i++] = $child;
									}
								}
							} else {
								if (!empty($checks)) {
									if (!in_array($child, $childs, true)) {
										$childs[$i++] = $child;
									}
								}
							}
						}
					}
				}
			}
			// Log array type
			$h = 0;
			if ($arraytype === 'parent') {
				foreach ($childs as $child_name) {
					// Count total occurrences of the child element in XML
					$totalrows = count($xml->xpath("//$child_name"));
					foreach ($map as $field => $value) {
						foreach ($value as $head => $val) {
							$str = str_replace(['(', '[', ']', ')'], '', $val);
							$ex = explode('/', $str);
							$last = substr($ex[2], -1);

							if (is_numeric($last)) {
								$substr = substr($ex[2], 0, -1);
							} else {
								$substr = $ex[2];
							}

							if ($substr === $child_name) {
								$count = 'count' . $h;
							}
						}
					}
					$h++;
				}
			}			
			 else {
				$count = 'count' . $h;
				$totalrows = $total_rows->$count;
			}
			$total_rows = $totalrows;
		} 
		else {
			$total_rows = $get_id[0]->total_rows;
		}
		$background_values = $wpdb->get_results("SELECT module  FROM $template_table_name WHERE `eventKey` = '$hash_key' ");
		foreach ($background_values as $values) {
			$selected_type = $values->module;
		}
		$upload_dir = SaveMapping::$smackcsv_instance->create_upload_dir();
		$file_size = filesize($upload_dir . $hash_key . '/' . $hash_key);
		$filesize = $helpers_instance->formatSizeUnits($file_size);
		$image_included = isset($image_included) ? $image_included : '';
		$response['total_rows'] = $total_rows;
		$response['file_extension'] = $file_extension;
		$response['file_name'] = $file_name;
		$response['filesize'] = $filesize;
		// $response['file_iteration'] = (int)$fileiteration;
		if ($selected_type == 'elementor_library') {
			$response['file_iteration'] = 1000000;
		} else {
			$response['file_iteration'] = $fileiteration;
		}
		if (get_option('total_attachment_ids')) {
			delete_option('total_attachment_ids');
		}
		$response['image_included'] = $image_included;
		$response['server_software'] = $server_software;

		echo wp_json_encode($response);
		wp_die();
	}



	public function send_error_status()
	{
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		$hash_key = sanitize_key($_POST['hash_key']);
		global $wpdb;
		$wpdb->get_results("DELETE FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE hash_key = '$hash_key'");

		$schedule_argument = array($hash_key, 'hash_key');
		//wp_clear_scheduled_hook('smackcsv_image_schedule_hook', $schedule_argument);

		$log_table_name = $wpdb->prefix . "import_detail_log";
		$get_processed_records = $wpdb->get_var("SELECT processing_records FROM $log_table_name WHERE hash_key = '$hash_key' order by id desc limit 1");
		$get_total_records = $wpdb->get_var("SELECT total_records FROM $log_table_name WHERE hash_key = '$hash_key' order by id desc limit 1");

		$response['success'] = true;
		$response['processed_records'] = (int)$get_processed_records;
		$response['total_records'] = (int)$get_total_records;
		echo wp_json_encode($response);
		wp_die();
	}

	public function remove_existingfields($map, $selected_type, $mode, $media_type = null)
	{
		$mapextension = new MappingExtension();
		if ($selected_type == 'Media') {
			$mapfield_data = $mapextension->media_mapping_fields($selected_type, $mode, $media_type);
		} else {
			//$mapfield_data = $mapextension->mapping_fields($selected_type);
			$mapfield_data = get_option('mapping_fields');
			if ($mapfield_data == false) {
				$mapfield_data  = $mapextension->mapping_fields($selected_type);
			}
		}

		$keydata = array_keys($map);
		foreach ($keydata as $widgetname) {
			$newmap = [];
			switch ($widgetname) {
				case 'CORE':
					$fieldtype = "core_fields";
					break;
				case 'FEATURED_IMAGE_META':
					$fieldtype = "featured_image_meta";
					break;
				case 'ACF':
					$fieldtype = "acf_pro_fields";
					break;
				case 'RF':
					$fieldtype = "acf_repeater_fields";
					break;
				case 'RRF':
					$fieldtype = "acf_repeater_of_repeater_fields";
					break;
				case 'JEREVIEW':
					$fieldtype = "jet_review_fields";
					break;
				case 'JEBOOKING':
					$fieldtype = "jet_booking_fields";
					break;
				case 'JE':
					$fieldtype = "jetengine_fields";
					break;
				case 'JERF':
					$fieldtype = "jetengine_rf_fields";
					break;
				case 'JECPT':
					$fieldtype = "jetenginecpt_fields";
					break;
				case 'JECPTRF':
					$fieldtype = "jetenginecpt_rf_fields";
					break;
				case 'JECCT':
					$fieldtype = "jetenginecct_fields";
					break;
				case 'JECCTRF':
					$fieldtype = "jetenginecct_rf_fields";
					break;
				case 'JETAX':
					$fieldtype = "jetenginetaxonomy_fields";
					break;
				case 'JETAXRF':
					$fieldtype = "jetenginetaxonomy_rf_fields";
					break;
				case 'JEREL':
					$fieldtype = "jetengine_rel_fields";
					break;
				case 'PODS':
					$fieldtype = "pods_fields";
					break;
				case 'AIOSEO':
					$fieldtype = "all_in_one_seo_fields";
					break;
					case 'SLIMSEO':
    $fieldtype = "slim_seo_fields";
    break;

				case 'YOASTSEO':
					$fieldtype = "yoast_seo_fields";
					break;
				case 'WPCOMPLETE':
					$fieldtype = "wpcomplete_fields";
					break;
				case 'SEOPRESS':
					$fieldtype = "seopress_fields";
					break;
				case 'RANKMATH':
					$fieldtype = "rank_math_fields";
					break;
				case 'ELEMENTOR':
					$fieldtype = "elementor_meta_fields";
					break;
				case 'ECOMMETA':
					$fieldtype = "product_meta_fields";
					break;
				case 'ATTRMETA':
					$fieldtype = "product_attr_fields";
					break;
				case 'BUNDLEMETA':
					$fieldtype = "product_bundle_meta_fields";
					break;
				case 'PPOMMETA':
					$fieldtype = "ppom_meta_fields";
					break;
			    case 'EPOMETA':
					$fieldtype = "epo_meta_fields";
					break;
				case 'LISTINGMETA':
					$fieldtype = "listing_meta_fields";
					break;				
				case 'PROPERTYMETA':
					$fieldtype = "wprentals_owner_fields";
					break;
				case 'OWNERMETA':
					$fieldtype = "wprentals_meta_fields";
					break;
				case 'REFUNDMETA':
					$fieldtype = "refund_meta_fields";
					break;
				case 'ORDERMETA':
					$fieldtype = "order_meta_fields";
					break;
				case 'COUPONMETA':
					$fieldtype = "coupon_meta_fields";
					break;
				case 'CCTM':
					$fieldtype = "cctm_fields";
					break;
				case 'CFS':
					$fieldtype = "custom_fields_suite_fields";
					break;
				case 'CMB2':
					$fieldtype = "cmb2_fields";
					break;
				case 'BSI':
					$fieldtype = "billing_and_shipping_information";
					break;
				case 'WPMEMBERS':
					$fieldtype = "custom_fields_wp_members";
					break;
				case 'MEMBERS':
					$fieldtype = "custom_fields_members";
					break;
				case 'WPECOMMETA':
					$fieldtype = "wp_ecom_custom_fields";
					break;
				case 'TERMS':
					$fieldtype = "terms_and_taxonomies";
					break;
				case 'WPML':
					$fieldtype = "wpml_fields";
					break;
				case 'CORECUSTFIELDS':
					$fieldtype = "wordpress_custom_fields";
					break;
				case 'DPF':
					$fieldtype = "directory_pro_fields";
					break;
				case 'EVENTS':
					$fieldtype = "events_manager_fields";
					break;
				case 'NEXTGEN':
					$fieldtype = "nextgen_gallery_fields";
					break;
				case 'LPCOURSE':
					$fieldtype = "course_settings_fields";
					break;
				case 'FC':
					$fieldtype = "acf_flexible_fields";
					break;
				case 'ACPT':
					$fieldtype = "acpt_fields";
					break;
				case 'GF':
					$fieldtype = "acf_group_fields";
					break;
				case 'TYPES':
					$fieldtype = "types_fields";
					break;
				case 'LPLESSON':
					$fieldtype = "lesson_settings_fields";
					break;
				case 'LPCURRICULUM':
					$fieldtype = "curriculum_settings_fields";
				case 'LPQUIZ':
					$fieldtype = "quiz_settings_fields";
					break;
				case 'LPQUESTION':
					$fieldtype = "question_settings_fields";
					break;
				case 'LPORDER':
					$fieldtype = "order_settings_fields";
					break;
				case 'LIFTERCOURSE':
					$fieldtype = "lifter_course_settings_fields";
					break;
					case 'LIFTERQUIZ':
					$fieldtype = "lifter_quiz_settings_fields";
					break;
				case 'LIFTERREVIEW':
					$fieldtype = "lifter_review_settings_fields";
					break;
				case 'LIFTERCOUPON':
					$fieldtype = "lifter_coupon_settings_fields";
					break;
				case 'LIFTERLESSON':
					$fieldtype = "lifter_lesson_settings_fields";
					break;
				case 'STMCOURSE':
					$fieldtype = "course_settings_fields_stm";
					break;
				case 'STMCURRICULUM':
					$fieldtype = "curriculum_settings_fields_stm";
					break;
				case 'STMLESSON':
					$fieldtype = "lesson_settings_fields_stm";
					break;
				case 'STMQUIZ':
					$fieldtype = "quiz_settings_fields_stm";
					break;
				case 'STMORDER':
					$fieldtype = "order_settings_fields_stm";
					break;
				case 'FORUM':
					$fieldtype = "forum_attributes_fields";
					break;
				case 'TOPIC':
					$fieldtype = "topic_attributes_fields";
					break;
				case 'REPLY':
					$fieldtype = "reply_attributes_fields";
					break;
				case 'POLYLANG':
					$fieldtype = "Polylang_settings_fields";
					break;
				case 'METABOX':
					$fieldtype = "metabox_fields";
					break;
				case 'METABOXRELATION':
					$fieldtype = "metabox_relations_fields";
					break;
				case 'METABOXGROUP':
					$fieldtype = "metabox_group_fields";
					break;
				case 'JOB':
					$fieldtype = "job_listing_fields";
					break;
				case 'FIFUPOSTS':
					$fieldtype = "fifu_post_settings_fields";
					break;
				case 'FIFUPAGE':
					$fieldtype = "fifu_page_settings_fields";
					break;
				case 'FIFUCUSTOMPOST':
					$fieldtype = "fifu_custompost_settings_fields";
					break;
				default:
					$fieldtype = "core_fields";
					break;
			}

			//Get all current fields of mapping widgets
			foreach ($mapfield_data as $extensions) {
                $i=0;
                if (is_array($extensions) && array_key_exists($fieldtype, $extensions)) {
                    if($fieldtype == "product_attr_fields"){
                        foreach ($extensions[$fieldtype] as $fielddata) {
                                $newmap[$i]= $fielddata['name'];
                                $i++;
                            }
                        }
                    }
                    else{
						if (isset($extensions[$fieldtype]) && is_array($extensions[$fieldtype])) {
								foreach ($extensions[$fieldtype] as $fielddata) {
									$newmap[] = $fielddata['name'];             
							}
						}
                    }
                
            }
			//Remove unwanted/deleted fields
			foreach ($map[$widgetname] as $key => $value) {
				if (strpos($key, '->static') !== false || strpos($key, '->math') !== false || strpos($key, '->cus1') !== false || strpos($key, '->cus2') !== false || strpos($key, '->OPENAIA') !== false || strpos($key, '->num') !== false) {
				} else {
					if ((!empty($newmap) && !in_array($key, $newmap)) && ($widgetname != 'CORECUSTFIELDS' || $widgetname !== 'ATTRMETA')) {
                        if($widgetname == 'ATTRMETA'){
                        }
                        else{
                            unset($map[$widgetname][$key]);
                        }
					}
				}
			}
		}
		return $map;
	}
}
