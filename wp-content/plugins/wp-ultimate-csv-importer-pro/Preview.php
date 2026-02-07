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
  
require_once 'SaveMapping.php';
require_once(__DIR__.'/uploadModules/ValidateFile.php');
require_once 'SmackCSVParser.php';
require_once (__DIR__.'/importExtensions/ImportHelpers.php');
class Preview{
    public static $instance = null, $validatefile, $media_instance;
	public static $failed_images_instance = null;
	public static $smackcsv_instance = null;
	public static $core = null, $nextgen_instance;
	public $media_log;
    private $categoryList = [];
    private $isCategory;
    public static function getInstance()
	{
        if (Preview::$instance == null) {
            Preview::$instance = new Preview;
            // Preview::$smackcsv_instance = SmackCSV::getInstance();
            Preview::$validatefile = new ValidateFile;
            // Preview::$failed_images_instance = FailedImagesUpdate::getInstance();
            // Preview::$nextgen_instance = new NextGenGalleryImport;
             return Preview::$instance;
        }
        return Preview::$instance;
    }
	public function __construct()
	{
        add_action('wp_ajax_get_image_url', array($this, 'get_image_url'));
        add_action('wp_ajax_nopriv_get_image_url', [$this, 'get_image_url']); 
		add_action('wp_ajax_get_test_result', array($this, 'get_test_result'));
        add_action('wp_ajax_nopriv_get_test_result', [$this, 'get_test_result']); 
		add_action('wp_ajax_get_post_content', array($this, 'get_post_content'));
        add_action('wp_ajax_nopriv_get_post_content', [$this, 'get_post_content']); 
		
    }
    public function get_image_url(){
		
        $hash_key  = sanitize_key($_POST['HashKey']);
        $map_fields    = $_POST['MappedFields'];
		$line_number = $_POST['line_number'];
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

        global $wpdb;
        $file_iteration = get_option('sm_bulk_import_iteration_limit');
        $file_table_name = $wpdb->prefix . "smackcsv_file_events";
        $get_id = $wpdb->get_results("SELECT id , mode ,file_name , total_rows FROM $file_table_name WHERE `hash_key` = '$hash_key'");
		$get_mode = $get_id[0]->mode;
		$file_name = $get_id[0]->file_name;
		$total_rows = $get_id[0]->total_rows;
		$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $file_iteration = 5;
       
        $preview_instance =  new Preview;
        $upload_dir = $this->create_upload_dir();
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
			$upload_dir = $this->create_upload_dir();
        
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
        if ($file_extension == 'csv' || $file_extension == 'txt' || $file_extension == 'xls' || $file_extension == 'json') {
			
            // $line_number = (($file_iteration * $page_number) - $file_iteration) + 1;
            $limit = ($file_iteration * $page_number);
            $parsing_limit = $file_iteration;
			
			if ($page_number == 1) {
				$addHeader = true;
			}

			$file_path = $upload_dir . $hash_key . '/' . $hash_key;
            $preview_instance = new ValidateFile;
            $delimiter = $preview_instance->getFileDelimiter($file_path, 5);
			
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

				$parse_csv_response = $parserObj->parseCSV($file_path, $line_number, $total_rows);
				$header_array = !empty($parse_csv_response['headers'][0]) ?  array_map('trim', $parse_csv_response['headers'][0]) : [];
				$all_value_array = $parse_csv_response['values'];
			}		
			
			$openAIKeys = array();
			$openAIValues = array();
			$flag = false;
			$map_openAI = false;
			foreach ($map_data as $subarray) {
				foreach ($subarray as $key => $value) {
					if (strpos($key, '->openAI') !== false) {
						$map_openAI = 1;
						break 2;
					}
				}
			}
			if ($map_openAI == true) {
				foreach ($map as $mainKey => $mainValue) {
					foreach ($mainValue as $subKey => $subValue) {
						if (substr($subKey, -8) === '->openAI') {
							$flag = true;
							$value_header = str_replace("->openAI", "", $subKey);
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
				$core_instance->openAI_response = $responsevalueArray;

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

				foreach ($map_data as $key => &$value) {
					if (is_array($value)) {
						foreach ($value as $innerKey => $innerValue) {
							if (strpos($innerKey, '->openAI') !== false) {
								$newKey = str_replace('->openAI', '', $innerKey);
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
                        $helpers_instance = ImportHelpers::getInstance();
                        $post_values = $helpers_instance->get_header_values($map_data['CORE'] , $header_array , $value_array,$hash_key);
						if(isset($post_values['featured_image'])){
  							$response['featured_image'] = $post_values['featured_image'];
							$response['total_rows'] = $total_rows;
						}
						else{
							$response['featured_image'] = $post_values['actual_url'];
							$response['total_rows'] = $total_rows;
						}
                      
                        echo wp_json_encode($response);
						wp_die();
					}
				}
			}
		}
		if($file_extension == 'tsv'){
			
			//$line_number = (($file_iteration * $page_number) - $file_iteration) + 1;
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
										if (strpos($key, '->openAI') !== false) {
											$map_openAI = 1;
											break 2;
										}
									}
								}
								if ($map_openAI == true) {
									foreach ($map as $mainKey => $mainValue) {
										foreach ($mainValue as $subKey => $subValue) {
											if (substr($subKey, -8) === '->openAI') {
												$flag = true;
												$value_header = str_replace("->openAI", "", $subKey);
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
									$core_instance->openAI_response = $responsevalueArray;

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
												if (strpos($innerKey, '->openAI') !== false) {
													$newKey = str_replace('->openAI', '', $innerKey);
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
					}
				}
			}
		}

	}

    public function get_test_result(){
		$imageUrl = sanitize_url($_POST['image_url']);
		$ch = curl_init($imageUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
		$imageData = curl_exec($ch);
		if (curl_errno($ch)) {
			$response['message'] = "cURL Error: " . curl_error($ch);
			$response['success'] = false;
			echo wp_json_encode($response);
			wp_die();
			echo "cURL Error: " . curl_error($ch);
			curl_close($ch);
			exit;
		}
		curl_close($ch);    
		$response['message'] = "Download successfully";
		$response['success'] = true;
		echo wp_json_encode($response);
		wp_die();
    }
    public function create_upload_dir($mode = null)
    {        
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
			if(!is_dir($upload_dir)){
				return false;
        } else {
				$upload_dir = $upload_dir . '/smack_uci_uploads/imports/';
				if (!is_dir($upload_dir)) {
					wp_mkdir_p($upload_dir);
					chmod($upload_dir, 0755);

					$index_php_file = $upload_dir . 'index.php';
					if (!file_exists($index_php_file)) {
						$file_content = '<?php' . PHP_EOL . '?>';
						file_put_contents($index_php_file, $file_content);
					}
				}
			if($mode != 'CLI')
            {
				chmod($upload_dir, 0777);
			}
			return $upload_dir;
		}
	}
	public function get_post_content(){
		
        $hash_key  = sanitize_key($_POST['HashKey']);
        $map_fields    = $_POST['MappedFields'];
		$line_number = $_POST['line_number'];
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

        global $wpdb;
        $file_iteration = get_option('sm_bulk_import_iteration_limit');
        $file_table_name = $wpdb->prefix . "smackcsv_file_events";
        $get_id = $wpdb->get_results("SELECT id , mode ,file_name , total_rows FROM $file_table_name WHERE `hash_key` = '$hash_key'");
		$get_mode = $get_id[0]->mode;
		$file_name = $get_id[0]->file_name;
		$total_rows = $get_id[0]->total_rows;
		$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $file_iteration = 5;
       
        $preview_instance =  new Preview;
        $upload_dir = $this->create_upload_dir();
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
			$upload_dir = $this->create_upload_dir();
        
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
        if ($file_extension == 'csv' || $file_extension == 'txt' || $file_extension == 'xls' || $file_extension == 'json') {
			
            // $line_number = (($file_iteration * $page_number) - $file_iteration) + 1;
            $limit = ($file_iteration * $page_number);
            $parsing_limit = $file_iteration;
			
			if ($page_number == 1) {
				$addHeader = true;
			}

			$file_path = $upload_dir . $hash_key . '/' . $hash_key;
            $preview_instance = new ValidateFile;
            $delimiter = $preview_instance->getFileDelimiter($file_path, 5);
			
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

				$parse_csv_response = $parserObj->parseCSV($file_path, $line_number, $total_rows);
				$header_array = !empty($parse_csv_response['headers'][0]) ?  array_map('trim', $parse_csv_response['headers'][0]) : [];
				$all_value_array = $parse_csv_response['values'];
			}		
			
			$openAIKeys = array();
			$openAIValues = array();
			$flag = false;
			$map_openAI = false;
			foreach ($map_data as $subarray) {
				foreach ($subarray as $key => $value) {
					if (strpos($key, '->openAI') !== false) {
						$map_openAI = 1;
						break 2;
					}
				}
			}
			if ($map_openAI == true) {
				foreach ($map as $mainKey => $mainValue) {
					foreach ($mainValue as $subKey => $subValue) {
						if (substr($subKey, -8) === '->openAI') {
							$flag = true;
							$value_header = str_replace("->openAI", "", $subKey);
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
				$core_instance->openAI_response = $responsevalueArray;

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

				foreach ($map_data as $key => &$value) {
					if (is_array($value)) {
						foreach ($value as $innerKey => $innerValue) {
							if (strpos($innerKey, '->openAI') !== false) {
								$newKey = str_replace('->openAI', '', $innerKey);
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
                        $helpers_instance = ImportHelpers::getInstance();
                        $post_values = $helpers_instance->get_header_values($map_data['CORE'] , $header_array , $value_array,$hash_key);
                        $response['post_content'] = $post_values['post_content'];
						$response['total_rows'] = $total_rows;
                        echo wp_json_encode($response);
						wp_die();
					}
				}
			}
		}
		if($file_extension == 'tsv'){
			
			// $line_number = (($file_iteration * $page_number) - $file_iteration) + 1;
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
										if (strpos($key, '->openAI') !== false) {
											$map_openAI = 1;
											break 2;
										}
									}
								}
								if ($map_openAI == true) {
									foreach ($map as $mainKey => $mainValue) {
										foreach ($mainValue as $subKey => $subValue) {
											if (substr($subKey, -8) === '->openAI') {
												$flag = true;
												$value_header = str_replace("->openAI", "", $subKey);
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
									$core_instance->openAI_response = $responsevalueArray;

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
												if (strpos($innerKey, '->openAI') !== false) {
													$newKey = str_replace('->openAI', '', $innerKey);
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
					}
				}
			}
		}

	}
	public function get_media_url(){
		
        $hash_key  = sanitize_key($_POST['HashKey']);
        $map_fields    = $_POST['MappedFields'];
		$line_number = $_POST['line_number'];
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

        global $wpdb;
        $file_iteration = get_option('sm_bulk_import_iteration_limit');
        $file_table_name = $wpdb->prefix . "smackcsv_file_events";
        $get_id = $wpdb->get_results("SELECT id , mode ,file_name , total_rows FROM $file_table_name WHERE `hash_key` = '$hash_key'");
		$get_mode = $get_id[0]->mode;
		$file_name = $get_id[0]->file_name;
		$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $file_iteration = 5;
       
        $preview_instance =  new Preview;
        $upload_dir = $this->create_upload_dir();
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
			$upload_dir = $this->create_upload_dir();
        
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
        if ($file_extension == 'csv' || $file_extension == 'txt' || $file_extension == 'xls' || $file_extension == 'json') {
			
           
			// $line_number = (($file_iteration * $page_number) - $file_iteration) + 1;
            $limit = ($file_iteration * $page_number);
            $parsing_limit = $file_iteration;
			
			if ($page_number == 1) {
				$addHeader = true;
			}

			$file_path = $upload_dir . $hash_key . '/' . $hash_key;
            $preview_instance = new ValidateFile;
            $delimiter = $preview_instance->getFileDelimiter($file_path, 5);
			
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

				$parse_csv_response = $parserObj->parseCSV($file_path, $line_number, $total_rows);
				$header_array = !empty($parse_csv_response['headers'][0]) ?  array_map('trim', $parse_csv_response['headers'][0]) : [];
				$all_value_array = $parse_csv_response['values'];
			}		
			
			$openAIKeys = array();
			$openAIValues = array();
			$flag = false;
			$map_openAI = false;
			foreach ($map_data as $subarray) {
				foreach ($subarray as $key => $value) {
					if (strpos($key, '->openAI') !== false) {
						$map_openAI = 1;
						break 2;
					}
				}
			}
			if ($map_openAI == true) {
				foreach ($map as $mainKey => $mainValue) {
					foreach ($mainValue as $subKey => $subValue) {
						if (substr($subKey, -8) === '->openAI') {
							$flag = true;
							$value_header = str_replace("->openAI", "", $subKey);
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
				$core_instance->openAI_response = $responsevalueArray;

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

				foreach ($map_data as $key => &$value) {
					if (is_array($value)) {
						foreach ($value as $innerKey => $innerValue) {
							if (strpos($innerKey, '->openAI') !== false) {
								$newKey = str_replace('->openAI', '', $innerKey);
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
                        $helpers_instance = ImportHelpers::getInstance();
                        $post_values = $helpers_instance->get_header_values($map_data['CORE'] , $header_array , $value_array,$hash_key);
                        $response['actual_url'] = $post_values['actual_url'];
                        echo wp_json_encode($response);
						wp_die();
					}
				}
			}
		}
		if($file_extension == 'tsv'){
			
			// $line_number = (($file_iteration * $page_number) - $file_iteration) + 1;
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
										if (strpos($key, '->openAI') !== false) {
											$map_openAI = 1;
											break 2;
										}
									}
								}
								if ($map_openAI == true) {
									foreach ($map as $mainKey => $mainValue) {
										foreach ($mainValue as $subKey => $subValue) {
											if (substr($subKey, -8) === '->openAI') {
												$flag = true;
												$value_header = str_replace("->openAI", "", $subKey);
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
									$core_instance->openAI_response = $responsevalueArray;

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
												if (strpos($innerKey, '->openAI') !== false) {
													$newKey = str_replace('->openAI', '', $innerKey);
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
					}
				}
			}
		}

	}
}
$class_obj = new Preview();