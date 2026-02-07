<?php
/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use GzipDownloader\GzipDownloader;
use Composer\Downloader\GzipDownloader;
if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

class DesktopUpload implements Uploads{

    private static $instance = null;
    private static $smack_csv_instance = null;

    private function __construct(){
		add_action('wp_ajax_get_desktop',array($this,'upload_function'));
        add_action('wp_ajax_oneClickUpload',array($this,'upload_function'));
		add_action('wp_ajax_get_csv_delimiter', [$this, 'get_csv_delimiter']);
		add_action('wp_ajax_nopriv_get_csv_delimiter', [$this, 'get_csv_delimiter']);
		$this->init_review_notice();

    }

    public static function getInstance() {
		if (DesktopUpload::$instance == null) {
			DesktopUpload::$instance = new DesktopUpload;
            DesktopUpload::$smack_csv_instance = SmackCSV::getInstance();
			return DesktopUpload::$instance;
		}
		return DesktopUpload::$instance;
    }
	private function convertJsonToCsv($upload_dir_path, $event_key)
	{
		$csv_file_path = $upload_dir_path . '/' . $event_key;
		$temp = $_FILES['csvFile']['tmp_name'];
		$temp_data = file_get_contents($temp);

		$temp_datas = json_decode($temp_data, true);
		$data = array_values($temp_datas);

		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		// Write header
		$headers = array_keys($data[0]);
		$col = 'A';
		foreach ($headers as $header) {
			$sheet->setCellValue($col.'1', $header);
			$col++;
		}
		$rowNum = 2;
		foreach ($data as $id => $row) {
			$col = 'A';
			foreach ($row as $cell) {
				$sheet->setCellValue($col.$rowNum, $cell);
				$col++;
			}
			$rowNum++;
		}
		$csv_writer = new Csv($spreadsheet);
		$csv_writer->setDelimiter(',');
		$csv_writer->setEnclosure('"');
		$csv_writer->setLineEnding("\r\n");
		$csv_writer->setIncludeSeparatorLine(false);
		$csv_writer->save($csv_file_path);
	}
	private function convertXlsxToCsv($upload_dir_path, $event_key)
	{
		$spreadsheet = IOFactory::load($_FILES['csvFile']['tmp_name']);
		$csv_file_path = $upload_dir_path . '/' . $event_key;
		$csv_writer = new Csv($spreadsheet);
		$csv_writer->setDelimiter(',');
		$csv_writer->setEnclosure('"');
		$csv_writer->setLineEnding("\r\n");
		$csv_writer->setIncludeSeparatorLine(false);
		$csv_writer->save($csv_file_path);
	}

    /**
	 * Upload file from desktop.
	 */
    public function upload_function(){
       
      
        $validate_instance = ValidateFile::getInstance();
        $zip_instance = ZipHandler::getInstance();
		$gz_instance = GzHandler::getInstance();
        $media_type = '';
        //mapping_true
        $template_type = isset($_POST['mappingType'] ) ? sanitize_text_field($_POST['mappingType'] )  : '';
        if (isset($_POST['MediaType'])) {
            $media_type = strtolower(sanitize_key($_POST['MediaType']));
        }
        global $wpdb;
        $file_table_name = $wpdb->prefix ."smackcsv_file_events";
          
        $file_name = $_FILES['csvFile']['name'];    
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        if(empty($file_extension)){
			$file_extension = 'xml';
		}
        $validate_format = $validate_instance->validate_file_format($file_name);
      
        $response =[];
        if (!extension_loaded('xml')) {
            $response['success'] = false;
            $response['message'] = 'The required PHP module xml is not installed. Please install it.';
            echo wp_json_encode($response);
            wp_die();
        }
        if($validate_format == 'yes'){
        
            $upload_dir = DesktopUpload::$smack_csv_instance->create_upload_dir();
            
            if($upload_dir){
                $event_key = DesktopUpload::$smack_csv_instance->convert_string2hash_key($file_name);
				if($file_extension == 'gz'){

					
					if(!function_exists('curl_version')){
                        $response['success'] = false;
                        $response['message'] = 'Curl is not exists.Kindly install it.';
                        echo wp_json_encode($response); 
                        wp_die();
                    }
                    $gz_response = [];    
                    $path = $upload_dir . $event_key . '.gz';
                    $extract_path = $upload_dir . $event_key;
                    
                    if(move_uploaded_file($_FILES['csvFile']['tmp_name'], $path)){
                        chmod($path, 0777);
						$gz_name = $_FILES['csvFile']['name']; 
					
						if (preg_match('/^smbundle_(.*)\.zip$/', $gz_name, $matches)) {
							$mode = 'oneClickUpload';
						}
						$gz_result = $gz_instance->extractGzipFile($path, $extract_path);
                        if($gz_result == 'UnSupported File Format'){
                            $gz_response['success'] = false;
                            $gz_response['message'] = "UnSupported File Format Inside Zip";
                        }
                        else{
                            $gz_response['success'] = true;
                            $gz_response['filename'] = $file_name;
                            $gz_response['file_type'] = 'zip'; 
                            $gz_response['info'] = $gz_result; 
                            $action = $_POST['action'];
							if($action == 'oneClickUpload'){
								$gz_response['info'] = $gz_result;
								$exporter_adapter_list = array(
									"core_fields" => "CORE",
									"Comments" => "Comments",
									"acf_fields" => "ACF",
									"featured_image_meta" => "FEATURED_IMAGE_META",
									"acf_pro_fields" => "ACF",
									"acf_repeater_fields" => "RF",
									"acf_repeater_of_repeater_fields" => "RRF",
									"jet_review_fields" => "JEREVIEW",
									"jet_booking_fields" => "JEBOOKING",
									"jetengine_fields" => "JE",
									"jetengine_rf_fields" => "JERF",
									"jetenginecpt_fields" => "JECPT",
									"jetenginecpt_rf_fields" => "JECPTRF",
									"jetenginecct_fields" => "JECCT",
									"jetenginecct_rf_fields" => "JECCTRF",
									"jetenginetaxonomy_fields" => "JETAX",
									"jetenginetaxonomy_rf_fields" => "JETAXRF",
									"jetengine_rel_fields" => "JEREL",
									"pods_fields" => "PODS",
									"all_in_one_seo_fields" => "AIOSEO",
									"yoast_seo_fields" => "YOASTSEO",
									"wpcomplete_fields" => "WPCOMPLETE",
									"seopress_fields" => "SEOPRESS",
									"rank_math_fields" => "RANKMATH",
									"elementor_meta_fields" => "ELEMENTOR",
									"product_meta_fields" => "ECOMMETA",
									"product_attr_fields" => "ATTRMETA",
									"product_bundle_meta_fields" => "BUNDLEMETA",
									"ppom_meta_fields" => "PPOMMETA",
									"epo_meta_fields" => "EPOMETA",
									"listing_meta_fields" => "LISTINGMETA",
									"wprentals_meta_fields" => "PROPERTYMETA",
									"wprentals_owner_fields" => "OWNERMETA",
									"refund_meta_fields" => "REFUNDMETA",
									"order_meta_fields" => "ORDERMETA",
									"coupon_meta_fields" => "COUPONMETA",
									"cctm_fields" => "CCTM",
									"custom_fields_suite_fields" => "CFS",
									"cmb2_fields" => "CMB2",
									"billing_and_shipping_information" => "BSI",
									"custom_fields_wp_members" => "WPMEMBERS",
									"custom_fields_members" => "MEMBERS",
									"wp_ecom_custom_fields" => "WPECOMMETA",
									"terms_and_taxonomies" => "TERMS",
									"wpml_fields" => "WPML",
									"wordpress_custom_fields" => "CORECUSTFIELDS",
									"directory_pro_fields" => "DPF",
									"events_manager_fields" => "EVENTS",
									"nextgen_gallery_fields" => "NEXTGEN",
									"course_settings_fields" => "LPCOURSE",
									"acf_flexible_fields" => "FC",
									"acf_group_fields" => "GF",
									"types_fields" => "TYPES",
									"lesson_settings_fields" => "LPLESSON",
									"curriculum_settings_fields" => "LPCURRICULUM",
									"quiz_settings_fields" => "LPQUIZ",
									"question_settings_fields" => "LPQUESTION",
									"order_settings_fields" => "LPORDER",
									"lifter_course_settings_fields" => "LIFTERCOURSE",
									"lifter_review_settings_fields" => "LIFTERREVIEW",
									"lifter_coupon_settings_fields" => "LIFTERCOUPON",
									"lifter_lesson_settings_fields" => "LIFTERLESSON",
									"course_settings_fields_stm" => "STMCOURSE",
									"curriculum_settings_fields_stm" => "STMCURRICULUM",
									"lesson_settings_fields_stm" => "STMLESSON",
									"quiz_settings_fields_stm" => "STMQUIZ",
									"order_settings_fields_stm" => "STMORDER",
									"forum_attributes_fields" => "FORUM",
									"topic_attributes_fields" => "TOPIC",
									"reply_attributes_fields" => "REPLY",
									"Polylang_settings_fields" => "POLYLANG",
									"metabox_fields" => "METABOX",
									"acpt_fields" => "ACPT",
									"metabox_relations_fields" => "METABOXRELATION",
									"metabox_group_fields" => "METABOXGROUP",
									"job_listing_fields" => "JOB",
									"fifu_post_settings_fields" => "FIFUPOSTS",
									"fifu_page_settings_fields" => "FIFUPAGE",
									"fifu_custompost_settings_fields" => "FIFUCUSTOMPOST"
								);
								foreach ($zip_result as $file) {

									if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'json') {

										$json_content = file_get_contents($file['path']);
										$decoded_json = json_decode($json_content, true);
										function mapFields($decoded_json, $exporter_adapter_list) {
											$mappedFields = [];

											foreach ($decoded_json as $key => $fields) {
												if (isset($exporter_adapter_list[$key])) {
													$mappedKey = $exporter_adapter_list[$key]; // Get mapped value from the list

													if (!isset($mappedFields[$mappedKey])) {
														$mappedFields[$mappedKey] = []; // Ensure category exists
													}

													foreach ($fields as $fieldKey => $fieldValue) {
														$mappedFields[$mappedKey][$fieldKey] = $fieldValue;
													}
												}
											}

											return json_encode($mappedFields, JSON_PRETTY_PRINT);
										}


										$formatted_data = [];

										foreach ($decoded_json['headers']['fields'] as $field_group) {
											foreach ($field_group as $key => $fields) {
												if (is_array($fields)) {
													foreach ($fields as $field) {
														if (isset($field['name'])) {
															// Assign key and value dynamically
															$formatted_data[$key][$field['name']] = $field['name'];
														}
													}
												}
											}
										}
										$json_content = json_encode($formatted_data, JSON_PRETTY_PRINT);



										$decoded_jsons = json_decode($json_content, true);
										$mappedJson = mapFields($decoded_jsons, $exporter_adapter_list);
										
										if (preg_match('/^smbundle_(.*)\.zip$/', $file_name, $matches)) {
											$path = $upload_dir . $event_key . '/' . $event_key;

											if (file_exists($path)) {
												// Get the MIME type of the file
												$mime_type = mime_content_type($path); // Alternative: finfo_open(FILES)
												
											
												// Get the extension based on MIME type
												$extension = $this->get_extension_from_mime($mime_type);
												if(empty($extension)){
													$extension = 'csv';
												}
											}

											$file_name = $matches[1] .'.'.$extension ;

										} 
										else {
											echo "Invalid file name format";
										}
										$this->save_mapping($mappedJson,$event_key,$_POST,$decoded_json,$file_name );

										if (is_array($decoded_json)) {
											// Merge JSON key-value pairs into the response array
											$gz_response = array_merge($gz_response, $decoded_json);
										}
									}
								}
							}
							$gz_response['hashkey'] = $event_key;
						}
					}
					else{
                        $gz_response['success'] = false;
                        $gz_response['message'] = "Cannot download zip file";
                    }
					$wpdb->insert( $file_table_name , array('file_name' => $file_name ,'total_rows' => $decoded_json['total_rows'],'hash_key' => $event_key , 'status' => 'Downloading', 'lock' => true) );
                    echo wp_json_encode($gz_response); 
                    wp_die();
				}
			
                if($file_extension == 'zip' ){
                    if(!function_exists('curl_version')){
                        $response['success'] = false;
                        $response['message'] = 'Curl is not exists.Kindly install it.';
                        echo wp_json_encode($response); 
                        wp_die();
                    }
                    $zip_response = [];    
                    $path = $upload_dir . $event_key . '.zip';
                    $extract_path = $upload_dir . $event_key;
                    
                    if(move_uploaded_file($_FILES['csvFile']['tmp_name'], $path)){
                        chmod($path, 0777);
						$zip_name = $_FILES['csvFile']['name']; 
					
						if (preg_match('/^smbundle_(.*)\.zip$/', $zip_name, $matches)) {
							$mode = 'oneClickUpload';
						}
                        $zip_result = $zip_instance->zip_upload($path , $extract_path, $event_key, $mode);
                        if($zip_result == 'UnSupported File Format'){
                            $zip_response['success'] = false;
                            $zip_response['message'] = "UnSupported File Format Inside Zip";
                        }
                        else{
                            $zip_response['success'] = true;
                            $zip_response['filename'] = $file_name;
                            $zip_response['file_type'] = 'zip'; 
                            $zip_response['info'] = $zip_result; 
                            $action = $_POST['action'];
							if($action == 'oneClickUpload'){
								$zip_response['info'] = $zip_result;
								$exporter_adapter_list = array(
									"core_fields" => "CORE",
									"Comments" => "Comments",
									"acf_fields" => "ACF",
									"featured_image_meta" => "FEATURED_IMAGE_META",
									"acf_pro_fields" => "ACF",
									"acf_repeater_fields" => "RF",
									"acf_repeater_of_repeater_fields" => "RRF",
									"jet_review_fields" => "JEREVIEW",
									"jet_booking_fields" => "JEBOOKING",
									"jetengine_fields" => "JE",
									"jetengine_rf_fields" => "JERF",
									"jetenginecpt_fields" => "JECPT",
									"jetenginecpt_rf_fields" => "JECPTRF",
									"jetenginecct_fields" => "JECCT",
									"jetenginecct_rf_fields" => "JECCTRF",
									"jetenginetaxonomy_fields" => "JETAX",
									"jetenginetaxonomy_rf_fields" => "JETAXRF",
									"jetengine_rel_fields" => "JEREL",
									"pods_fields" => "PODS",
									"all_in_one_seo_fields" => "AIOSEO",
									"yoast_seo_fields" => "YOASTSEO",
									"wpcomplete_fields" => "WPCOMPLETE",
									"seopress_fields" => "SEOPRESS",
									"rank_math_fields" => "RANKMATH",
									"elementor_meta_fields" => "ELEMENTOR",
									"product_meta_fields" => "ECOMMETA",
									"product_attr_fields" => "ATTRMETA",
									"product_bundle_meta_fields" => "BUNDLEMETA",
									"ppom_meta_fields" => "PPOMMETA",
									"epo_meta_fields" => "EPOMETA",
									"listing_meta_fields" => "LISTINGMETA",
									"wprentals_meta_fields" => "PROPERTYMETA",
									"wprentals_owner_fields" => "OWNERMETA",
									"refund_meta_fields" => "REFUNDMETA",
									"order_meta_fields" => "ORDERMETA",
									"coupon_meta_fields" => "COUPONMETA",
									"cctm_fields" => "CCTM",
									"custom_fields_suite_fields" => "CFS",
									"cmb2_fields" => "CMB2",
									"billing_and_shipping_information" => "BSI",
									"custom_fields_wp_members" => "WPMEMBERS",
									"custom_fields_members" => "MEMBERS",
									"wp_ecom_custom_fields" => "WPECOMMETA",
									"terms_and_taxonomies" => "TERMS",
									"wpml_fields" => "WPML",
									"wordpress_custom_fields" => "CORECUSTFIELDS",
									"directory_pro_fields" => "DPF",
									"events_manager_fields" => "EVENTS",
									"nextgen_gallery_fields" => "NEXTGEN",
									"course_settings_fields" => "LPCOURSE",
									"acf_flexible_fields" => "FC",
									"acf_group_fields" => "GF",
									"types_fields" => "TYPES",
									"lesson_settings_fields" => "LPLESSON",
									"curriculum_settings_fields" => "LPCURRICULUM",
									"quiz_settings_fields" => "LPQUIZ",
									"question_settings_fields" => "LPQUESTION",
									"order_settings_fields" => "LPORDER",
									"lifter_course_settings_fields" => "LIFTERCOURSE",
									"lifter_review_settings_fields" => "LIFTERREVIEW",
									"lifter_coupon_settings_fields" => "LIFTERCOUPON",
									"lifter_lesson_settings_fields" => "LIFTERLESSON",
									"course_settings_fields_stm" => "STMCOURSE",
									"curriculum_settings_fields_stm" => "STMCURRICULUM",
									"lesson_settings_fields_stm" => "STMLESSON",
									"quiz_settings_fields_stm" => "STMQUIZ",
									"order_settings_fields_stm" => "STMORDER",
									"forum_attributes_fields" => "FORUM",
									"topic_attributes_fields" => "TOPIC",
									"reply_attributes_fields" => "REPLY",
									"Polylang_settings_fields" => "POLYLANG",
									"metabox_fields" => "METABOX",
									"acpt_fields" => "ACPT",								
									"metabox_relations_fields" => "METABOXRELATION",
									"metabox_group_fields" => "METABOXGROUP",
									"job_listing_fields" => "JOB",
									"fifu_post_settings_fields" => "FIFUPOSTS",
									"fifu_page_settings_fields" => "FIFUPAGE",
									"fifu_custompost_settings_fields" => "FIFUCUSTOMPOST"
								);
								
								foreach ($zip_result as $file) {

									if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'json') {

										$json_content = file_get_contents($file['path']);
										$decoded_json = json_decode($json_content, true);
										function mapFields($decoded_json, $exporter_adapter_list) {
											$mappedFields = [];

											foreach ($decoded_json as $key => $fields) {
												if (isset($exporter_adapter_list[$key])) {
													$mappedKey = $exporter_adapter_list[$key]; // Get mapped value from the list

													if (!isset($mappedFields[$mappedKey])) {
														$mappedFields[$mappedKey] = []; // Ensure category exists
													}

													foreach ($fields as $fieldKey => $fieldValue) {
														$mappedFields[$mappedKey][$fieldKey] = $fieldValue;
													}
												}
											}

											return json_encode($mappedFields, JSON_PRETTY_PRINT);
										}


										$formatted_data = [];

										foreach ($decoded_json['headers']['fields'] as $field_group) {
											foreach ($field_group as $key => $fields) {
												if (is_array($fields)) {
													foreach ($fields as $field) {
														if (isset($field['name'])) {
															// Assign key and value dynamically
															$formatted_data[$key][$field['name']] = $field['name'];
														}
													}
												}
											}
										}
										$json_content = json_encode($formatted_data, JSON_PRETTY_PRINT);



										$decoded_jsons = json_decode($json_content, true);
										$mappedJson = mapFields($decoded_jsons, $exporter_adapter_list);
										
										if (preg_match('/^smbundle_(.*)\.zip$/', $file_name, $matches)) {
											$path = $upload_dir . $event_key . '/' . $event_key;

						if (file_exists($path)) {
							// Get the MIME type of the file
							$mime_type = mime_content_type($path); // Alternative: finfo_open(FILES)
							
						
							// Get the extension based on MIME type
							$extension = $this->get_extension_from_mime($mime_type);
							if(empty($extension)){
								$extension = 'csv';
							}
						}

						$file_name = $matches[1] .'.'.$extension ;

										} else {
											echo "Invalid file name format";
										}
										$this->save_mapping($mappedJson,$event_key,$_POST,$decoded_json,$file_name );



										if (is_array($decoded_json)) {
											// Merge JSON key-value pairs into the response array
											$zip_response = array_merge($zip_response, $decoded_json);
										}
									}
								}
							}
							$zip_response['hashkey'] = $event_key;
                        }
                    }else{
                        $zip_response['success'] = false;
                        $zip_response['message'] = "Cannot download zip file";
                    }   
					

					$wpdb->insert( $file_table_name , array('file_name' => $file_name ,'total_rows' => $decoded_json['total_rows'],'hash_key' => $event_key , 'status' => 'Downloading', 'lock' => true) );


                    echo wp_json_encode($zip_response); 
                    wp_die();
                }
                
                $upload_dir_path = $upload_dir. $event_key;
                if (!is_dir($upload_dir_path)) {
                    wp_mkdir_p( $upload_dir_path);
                }
                chmod($upload_dir_path, 0777);	
                $wpdb->insert( $file_table_name , array('file_name' => $file_name , 'hash_key' => $event_key , 'status' => 'Downloading', 'lock' => true) );
                $last_id = $wpdb->get_results("SELECT id FROM $file_table_name ORDER BY id DESC LIMIT 1",ARRAY_A);
                $lastid = $last_id[0]['id'];
                
                switch($_FILES['csvFile']['error']){
                    
                    case UPLOAD_ERR_OK:
                        $path = $upload_dir. $event_key. '/' . $event_key;
                        if ($file_extension == 'xlsx' || $file_extension == 'xls' ) {
                            $this->convertXlsxToCsv($upload_dir_path, $event_key);
                            $file_extension = 'csv';
                        }
						elseif($file_extension == 'json'){
							$this->convertJsonToCsv($upload_dir_path, $event_key);
                            $file_extension = 'csv';
						}
                         else{
                            if (move_uploaded_file($_FILES['csvFile']['tmp_name'], $path)) {
                                chmod($path, 0755);
                            } else {
                            $response['success'] = false;
                            $response['message'] = "Cannot download the file";
                            echo wp_json_encode($response); 
                            $wpdb->get_results("UPDATE $file_table_name SET status='Download_Failed' WHERE id = '$lastid'");
                            }
                        }
                            $validate_file = $validate_instance->file_validation($path , $file_extension);
                            $file_size = filesize($path);
                            $filesize = $validate_instance->formatSizeUnits($file_size);   
                            $server_software = sanitize_text_field($_SERVER['SERVER_SOFTWARE']);
                            if($validate_file == "yes"){
                                $wpdb->get_results("UPDATE $file_table_name SET status='Downloaded',`lock`=false WHERE id = '$lastid'");
                                $get_result = $validate_instance->import_record_function($event_key , $file_name); 
                                if(isset($template_type) && $template_type == 'mapping_section'){
                                    $response['success'] = true;
                                    $response['file_path'] = $path;
                                    $response['file_type'] = $file_extension;
                                    $response['message'] = 'success';
    if ($file_extension === 'csv' || $file_extension === 'tsv') {
        $delimiter = $this->detect_csv_delimiter($path);
        update_option("smack_csv_delimiter_{$event_key}", $delimiter);
    }
                                    echo wp_json_encode($response); 
                                }else{
                                    if(isset($media_type) && ($media_type == 'external' || $media_type == 'local')){
                                        $get_result['selected type'] = 'Media';
                                    }
                                    $response['success'] = true;
                                    $response['filename'] = $file_name;
                                    $response['hashkey'] = $event_key;
                                    $response['posttype'] = $get_result['Post Type'];
                                    $response['taxonomy'] = $get_result['Taxonomy'];
                                    $response['selectedtype'] = $get_result['selected type'];
                                    $response['server'] = $server_software;
                                    $response['file_type'] = $file_extension;
                                    $response['file_size'] = $filesize;
                                    $response['message'] = 'success';
	 if ($file_extension === 'csv' || $file_extension === 'tsv') {
        $delimiter = $this->detect_csv_delimiter($path);
        update_option("smack_csv_delimiter_{$event_key}", $delimiter);
    }
                                    echo wp_json_encode($response); 
                                }

                            }
                            else{
                                $response['success'] = false;
                                $response['message'] = $validate_file;
                                echo wp_json_encode($response); 
                                unlink($path);
                                $wpdb->get_results("UPDATE $file_table_name SET status='Download_Failed' WHERE id = '$lastid'");
                            }
                        break;

                    case UPLOAD_ERR_INI_SIZE:
                        $response['success'] = false;
                        $response['message'] = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                        echo wp_json_encode($response); 
                        $wpdb->get_results("UPDATE $file_table_name SET status='Download_Failed' WHERE id = '$lastid'");
                        break;
                        
                    default:
                        $response['success'] = false;
                        $response['message'] = "Cannot download file";
                        echo wp_json_encode($response); 
                        $wpdb->get_results("UPDATE $file_table_name SET status='Download_Failed' WHERE id = '$lastid'");
                        break;
                }
            }else{
                $response['success'] = false;
                $response['message'] = "Please create Upload folder with writable permission";
                echo wp_json_encode($response); 
            }

        }else{
            $response['success'] = false;
            $response['message'] = $validate_format;
            echo wp_json_encode($response); 
        }
        wp_die();
    }


	public function save_mapping($data,$hash_key,$post,$decoded_json,$file_name )
	{

		
		check_ajax_referer('smack-ultimate-csv-importer', 'securekey');
		$type          = $decoded_json['selectedtype'];
		$template_name = ' ';
		$map_fields    = $data;

		$mapping_type = 'mapping-section';
		$counter = isset($counter) ? $counter : 0;
		$selected_mode = 'Advanced';
		global $wpdb;
		if ($selected_mode == 'simpleMode') {
			$fileiteration = 5;
			update_option('sm_bulk_import_free_iteration_limit', $fileiteration);
			$media_settings['media_handle_option'] = 'true';
			$media_settings['use_ExistingImage'] = 'true';
			$image_info = array(
				'media_settings'  => $media_settings
			);
			update_option('smack_image_options', $image_info);
		}
		$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
		$file_table_name = $wpdb->prefix . "smackcsv_file_events";

		$mapping_filter = '';	

        
		// $mapping_fields = $wpdb->_real_escape($mapping_fields);
		$time = date('Y-m-d H:i:s');
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
			}
			elseif($key == "ATTRMETA" && is_array($value)){
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
		// $mapping_fields = $wpdb->_real_escape($mapp


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
					$file_name ,
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
				$file_name ,
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

		

	}

	public function get_extension_from_mime($mime_type) {
		$mime_map = [
			'text/csv'              => 'csv',
			'application/xml'       => 'xml',
			'text/xml'              => 'xml',
			'application/vnd.ms-excel'  => 'xls',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
			'text/tab-separated-values' => 'tsv',
		];
	
		return $mime_map[$mime_type] ?? null;
	}

		public static function detect_csv_delimiter($file_path) {
    $delimiters = [",", ";", "\t", "|"];
    $line = '';

    if (!empty($file_path) && file_exists($file_path)) {
        $handle = fopen($file_path, 'r');
        if ($handle) {
            $line = fgets($handle);
            fclose($handle);
        }
    }

    $best_delimiter = ',';
    $max_count = 0;

    foreach ($delimiters as $delimiter) {
        $fields = str_getcsv($line, $delimiter, '"', '\\');
        if (count($fields) > $max_count) {
            $max_count = count($fields);
            $best_delimiter = $delimiter;
        }
    }

    return $best_delimiter;
}


public function get_csv_delimiter() {
	check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
    $event_key = sanitize_text_field($_POST['hashkey']);

    $delimiter = get_option("smack_csv_delimiter_{$event_key}", ',');

    wp_send_json_success([
        'delimiter' => $delimiter
    ]);
}

	public function init_review_notice() {
    add_action('admin_notices', [$this, 'render_review_notice']);
    add_action('admin_init', [$this, 'handle_review_notice_actions']);
}

public function render_review_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $activation_time = get_option('wcsv_activation_time');
    $dismissed       = get_option('wcsv_review_dismissed');
    $later           = get_option('wcsv_review_later');

    if (!$activation_time) {
        update_option('wcsv_activation_time', time());  
        return;
    }

    if ((time() - $activation_time) < 7 * DAY_IN_SECONDS) {
        return;
    }

    if ($dismissed) {
        return;
    }

    if ($later && (time() - $later) < 7 * DAY_IN_SECONDS) {
        return;
    }
    ?>
    <div class="notice notice-success is-dismissible">
        <h2><?php esc_html_e('Give CSV Importer a review', 'wp-ultimate-csv-importer'); ?></h2>
        <p>
            <?php esc_html_e('Thank you for choosing CSV Importer! We hope you love it. Could you take a couple of seconds to post a nice review?', 'wp-ultimate-csv-importer'); ?>
        </p>
        <p>
            <a href="https://wordpress.org/plugins/wp-ultimate-csv-importer/reviews/?rate=5#new-post"
               target="_blank" class="button button-primary">⭐ Rate now</a>
            <a href="<?php echo esc_url(add_query_arg('wcsv_review_later', '1')); ?>" class="button">Later</a>
            <a href="<?php echo esc_url(add_query_arg('wcsv_review_dismiss', '1')); ?>" class="button">No, thanks</a>
        </p>
    </div>
    <?php
}

public function handle_review_notice_actions() {
    if (isset($_GET['wcsv_review_dismiss'])) {
        update_option('wcsv_review_dismissed', 1);
        wp_redirect(remove_query_arg('wcsv_review_dismiss'));
        exit;
    }

    if (isset($_GET['wcsv_review_later'])) {
        update_option('wcsv_review_later', time());
        wp_redirect(remove_query_arg('wcsv_review_later')); 
        exit;
    }
}


}