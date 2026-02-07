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
if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

class UrlUpload implements Uploads{

	private static $instance = null;
	private static $smack_csv_instance = null;

    private function __construct(){
		add_action('wp_ajax_get_csv_url',array($this,'upload_function'));
    }

    public static function getInstance() {
		if (UrlUpload::$instance == null) {
			UrlUpload::$instance = new UrlUpload;
			UrlUpload::$smack_csv_instance = SmackCSV::getInstance();
			return UrlUpload::$instance;
		}
		return UrlUpload::$instance;
    }
	/**
	 * Upload file from URL.
	 */
    public function externalfile_handling($url){
        $file_url = $url;
		$response = [];
		global $wpdb;
		$file_table_name = $wpdb->prefix ."smackcsv_file_events";			
			if(strstr($file_url, 'https://bit.ly/')){
				$file_url = $this->unshorten_bitly_url($file_url);
			}

			$pub = substr($file_url, strrpos($file_url, '/') + 1);
               /*Added support for google addon & dropbox*/
			if($pub=='pubhtml'){
				$spread_sheet=substr($file_url, 0, -7);
				$file_url = $spread_sheet.'pub?gid=0&single=true&output=csv';
			}elseif ($pub=='edit?usp=sharing') { 
				$response['success'] = false;
				$response['message'] = 'Update your Google sheet as Public';			
			}
			
			if(!strstr($file_url, 'https://www.dropbox.com/')) {	
				$file_url   = $this->get_original_url($file_url);	
				$file_url = $file_url;
			}
			
			if(strstr($file_url, 'https://docs.google.com/')) {
				$get_file_headers = get_headers($file_url, 1);
				if(isset($get_file_headers['Content-Disposition'])){
					$url_file_name = $this->get_filename_from_headers($get_file_headers['Content-Disposition']);
				}else{
					$get_file_id = explode('/', $file_url);
					$external_file = 'google-sheet-' . $get_file_id[count($get_file_id) - 2];
					$file_extension = explode('output=', $get_file_id[count($get_file_id) - 1]);
					$file_extension = $file_extension[1];
					$url_file_name = $external_file . '.' . $file_extension;
				}
			}elseif(strstr($file_url, 'https://www.dropbox.com/')) {
				$filename = basename($file_url);
				$get_local_filename = explode('?', $filename);
				$url_file_name = $get_local_filename[0];	
			}else { # Other URL's except google spreadsheets	
				$supported_file = array('csv' , 'xml' , 'zip' , 'txt','json', 'tsv');
				$has_extension = explode(".", basename($file_url));
				$has_file_extension = end($has_extension);
				if($has_extension && in_array($has_file_extension , $supported_file)){
					$url_file_name = basename($file_url);
				}
				else{

					$get_file_headers = get_headers($file_url, 1);
					if(isset($get_file_headers['Content-Disposition'])){
						$url_file_name = $this->get_filename_from_headers($get_file_headers['Content-Disposition']);
					}else{
						if(strpos($file_url, '&type=') !== false) {	
							$get_extension = substr($file_url, strpos($file_url, "&type=") + 6, 3);
							$url_file_name = basename($file_url) .'.'. $get_extension;
						}
						elseif((strpos($file_url, 'format=rss') !== false) || (strpos($file_url, '/rss') !== false)){	
							if(isset($get_file_headers['Content-Type'])){
								$url_extension = substr($get_file_headers['Content-Type'], strpos($get_file_headers['Content-Type'], 'text/') + strlen('text/'), 3);
								$url_file_name = basename($file_url) . '.' . $url_extension;
							}
							else{
								$url_file_name = basename($file_url) . '.xml';
							}
						}else{
							if(isset($get_file_headers['Content-Type'])){
								if( strpos($get_file_headers['Content-Type'], 'text/') !== false) {	
									$url_extension = substr($get_file_headers['Content-Type'], strpos($get_file_headers['Content-Type'], 'text/') + strlen('text/'), 3);
								}
								else{
									$url_extension = substr($get_file_headers['Content-Type'], strpos($get_file_headers['Content-Type'], 'application/') + strlen('application/'), 3);	
								}
								$url_file_name = basename($file_url) . '.' . $url_extension;
							}
							else{
								$url_file_name = basename($file_url);
							}
						}
					}
				}
			}
			
			$validate_instance = ValidateFile::getInstance();
			$zip_instance = ZipHandler::getInstance();
$supported_file = ['csv', 'xml', 'zip', 'txt', 'json', 'tsv'];			
			// Fallback: If no proper extension, try to detect from headers or content
$file_extension = pathinfo($url_file_name, PATHINFO_EXTENSION);

if (empty($file_extension) || !in_array(strtolower($file_extension), $supported_file)) {
    $headers = get_headers($file_url, 1);
    $contentType = isset($headers['Content-Type']) ? (is_array($headers['Content-Type']) ? $headers['Content-Type'][0] : $headers['Content-Type']) : '';

    if (strpos($contentType, 'xml') !== false) {
        $url_file_name .= '.xml';
    } elseif (strpos($contentType, 'json') !== false) {
        $url_file_name .= '.json';
    } elseif (strpos($contentType, 'csv') !== false) {
        $url_file_name .= '.csv';
    } elseif (strpos($contentType, 'text/plain') !== false) {
        $url_file_name .= '.txt';
    } else {
        // Last resort: peek at body
        $body_check = wp_remote_retrieve_body(wp_remote_get($file_url));
        if (strpos($body_check, '<?xml') === 0) {
            $url_file_name .= '.xml';
        }
    }
}

			$validate_format = $validate_instance->validate_file_format($url_file_name);

			if($validate_format == 'yes'){

				$upload_dir = UrlUpload::$smack_csv_instance->create_upload_dir();
				if($upload_dir){
				
					$url_file_name = str_replace('%20', ' ', $url_file_name);
					$event_key = UrlUpload::$smack_csv_instance->convert_string2hash_key($url_file_name);
					$file_extension = pathinfo($url_file_name, PATHINFO_EXTENSION);
					if (empty($file_extension)) {
    $file_extension = 'xml';
    $url_file_name .= '.xml';
}

					if($file_extension == 'zip'){
						$zip_response = [];

						$curlCh = curl_init();
						curl_setopt($curlCh, CURLOPT_URL, $file_url);
						curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($curlCh, CURLOPT_FOLLOWLOCATION, true);
						curl_setopt($curlCh, CURLOPT_FAILONERROR, true);
						curl_setopt($curlCh, CURLOPT_MAXREDIRS, 10);
						curl_setopt($curlCh, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
						curl_setopt($curlCh, CURLOPT_CUSTOMREQUEST,'GET');
						curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($curlCh, CURLOPT_SSL_VERIFYHOST, FALSE);
			
						$curlData = curl_exec ($curlCh);

						if(curl_error($curlCh)){
						
							$zip_response['success'] = false;
							$zip_response['message'] = curl_error($curlCh);
							
						}else{
							$path = $upload_dir. $event_key . '.zip';
							$extract_path = $upload_dir . $event_key;

							$file = fopen($path , "w+");
							fputs($file, $curlData);
							chmod($path, 0777);

							$zip_result = $zip_instance->zip_upload($path , $extract_path);
							if($zip_result == 'UnSupported File Format'){
								$zip_response['success'] = false;
								$zip_response['message'] = "UnSupported File Format Inside Zip";
							}
							else{
								$zip_response['success'] = true;
								$zip_response['filename'] = $url_file_name;
								$zip_response['file_type'] = 'zip'; 
								$zip_response['info'] = $zip_result; 
							}
						}
						wp_die();
					}

					$upload_dir_path = $upload_dir. $event_key;
                    if (!is_dir($upload_dir_path)) {
                        wp_mkdir_p( $upload_dir_path);
                    }
					chmod($upload_dir_path, 0777);
					

					$mode_of_action = $wpdb->get_var("SELECT mode FROM $file_table_name where file_name = '".$url_file_name."' order by id desc limit 1");
					
					$wpdb->insert( $file_table_name , array( 'file_name' => $url_file_name , 'hash_key' => $event_key , 'status' => 'Downloading','lock' => true ) );
					$last_id = $wpdb->get_results("SELECT id FROM $file_table_name ORDER BY id DESC LIMIT 1",ARRAY_A);
					$lastid=$last_id[0]['id']; 

					$curlCh = curl_init();
					curl_setopt($curlCh, CURLOPT_URL, $file_url);
					curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curlCh, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($curlCh, CURLOPT_FAILONERROR, true);
					curl_setopt($curlCh, CURLOPT_MAXREDIRS, 10);
					curl_setopt($curlCh, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
					curl_setopt($curlCh, CURLOPT_CUSTOMREQUEST,'GET');
					curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($curlCh, CURLOPT_SSL_VERIFYHOST, FALSE);
			
					$curlData = curl_exec ($curlCh);

					if(curl_error($curlCh)){
						
						$response['success'] = false;
						$response['message'] = curl_error($curlCh);
						$wpdb->get_results("UPDATE $file_table_name SET status='Download_Failed' WHERE id = '$lastid'");
					}else{
						$path = $upload_dir. $event_key .'/'.$event_key;
						$file = fopen($path , "w+");
						fputs($file, $curlData);
						chmod($path, 0777);

						$validate_file = $validate_instance->file_validation($path , $file_extension );

						$file_size = filesize($path);
		                $filesize = $validate_instance->formatSizeUnits($file_size);
						
						if($validate_file == "yes"){
							$wpdb->get_results("UPDATE $file_table_name SET status='Downloaded',`lock`=false WHERE id = '$lastid'");
							fclose($file);
							$get_result = $validate_instance->import_record_function($event_key , $url_file_name); 
							$response['success'] = true;
							$response['filename'] = $url_file_name;
							$response['hashkey'] = $event_key;
							$response['posttype'] = $get_result['Post Type'];
							$response['taxonomy'] = $get_result['Taxonomy'];
							$response['selectedtype'] = $get_result['selected type'];
							$response['file_type'] = $file_extension;
							$response['file_size'] = $filesize;
							$response['message'] = 'success';
						}else{
							$response['success'] = false;
							$response['message'] = $validate_file; 
							unlink($path);
							$wpdb->get_results("UPDATE $file_table_name SET status='Download Failed',`lock`=true WHERE id = '$lastid'");
						}
					}
					curl_close ($curlCh);
				}else{
					$response['success'] = false;
                    $response['message'] = "Please create Upload folder with writable permission";
				}
			}else{
				$response['success'] = false;
				$response['message'] = $validate_format;
			}	
		return $response;
	}


	function convertXlsxToCsv($curlData, $upload_dir_path,$event_key)
	{
		// Temporary file path for downloaded XLSX
		$csv_file_path = $upload_dir_path . '/' . $event_key;
		$data = file_put_contents($csv_file_path, $curlData);
		try {
			$spreadsheet = IOFactory::load($csv_file_path);
		} catch (Exception $e) {
		}
		// Convert to CSV
		$csv_writer = new Csv($spreadsheet);
		$csv_writer->setDelimiter(','); // Set delimiter (change to "\t" for tab-separated)
		$csv_writer->setEnclosure('"'); // Set text enclosure character
		$csv_writer->setSheetIndex(0); // Convert only the first sheet

		// Save CSV file
		$csv_writer->save($csv_file_path);
	}
		
	/**
	 * Upload file from URL.
	 */
    public function upload_function(){
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
        $file_url = esc_url_raw($_POST['url']);
		$response = [];
		global $wpdb;
		$file_table_name = $wpdb->prefix ."smackcsv_file_events";			
			if(strstr($file_url, 'https://bit.ly/')){
				$file_url = $this->unshorten_bitly_url($file_url);
			}

			$pub = substr($file_url, strrpos($file_url, '/') + 1);
               /*Added support for google addon & dropbox*/
			if($pub=='pubhtml'){
				$spread_sheet=substr($file_url, 0, -7);
				$file_url = $spread_sheet.'pub?gid=0&single=true&output=csv';
			}elseif ($pub=='edit?usp=sharing') {
				$response['success'] = false;
				$response['message'] = 'Update your Google sheet as Public';
				echo wp_json_encode($response);				
			}
			
			// if(!strstr($file_url, 'https://www.dropbox.com/')) {	
			// 	$file_url   = $this->get_original_url($file_url);	
			// 	$file_url = $file_url;
			// }
			
			if (strstr($file_url, 'https://docs.google.com/')) {	
				if (preg_match('/docs\.google\.com\/spreadsheets\/d\/([^\/]+)/', $file_url, $matches)) {
					$sheet_id = $matches[1];
					$allowed_formats = ['csv', 'tsv', 'xlsx', 'zip'];
					$detected_format = 'csv'; 
					
					foreach ($allowed_formats as $fmt) {
						if (stripos($file_url, ".$fmt") !== false) {
							$detected_format = $fmt;
							break;
						}
					}

					$export_url = "https://docs.google.com/spreadsheets/d/{$sheet_id}/export?format={$detected_format}";
					$file_url = $export_url;
				} 

				$get_file_headers = get_headers($file_url, 1);
				if(isset($get_file_headers['Content-Disposition'])){
					$url_file_name = $this->get_filename_from_headers($get_file_headers['Content-Disposition']);
				}else{
					$get_file_id = explode('/', $file_url);
					$external_file = 'google-sheet-' . $get_file_id[count($get_file_id) - 2];
					$file_extension = explode('output=', $get_file_id[count($get_file_id) - 1]);
					$file_extension = $file_extension[1];
					$url_file_name = $external_file . '.' . $file_extension;
				}
			}
			if(strstr($file_url, 'https://www.dropbox.com/')) {
				if (strpos($file_url, 'dl=0') !== false) {
					$file_url = str_replace('dl=0', 'dl=1', $file_url);
				} elseif (strpos($file_url, 'dl=1') === false) {
					$file_url .= '&dl=1';
				}
				
				$filename = basename($file_url);
				$get_local_filename = explode('?', $filename);
				$url_file_name = $get_local_filename[0];	
			}
			// elseif(strstr($file_url, 'https://www.dropbox.com/')) {
			// 	$filename = basename($file_url);
			// 	$get_local_filename = explode('?', $filename);
			// 	$url_file_name = $get_local_filename[0];	
			// }
			else { # Other URL's except google spreadsheets	
		
				$supported_file = array('csv' , 'xml' , 'zip' , 'txt','json', 'tsv');
				$has_extension = explode(".", basename($file_url));
				$has_file_extension = end($has_extension);
				if($has_extension && in_array($has_file_extension , $supported_file)){
					$url_file_name = basename($file_url);
				}
				else{
					$get_file_headers = get_headers($file_url, 1);
					if(isset($get_file_headers['Content-Disposition'])){
						$url_file_name = $this->get_filename_from_headers($get_file_headers['Content-Disposition']);
					}else{
						if(strpos($file_url, '&type=') !== false) {	
							$get_extension = substr($file_url, strpos($file_url, "&type=") + 6, 3);
							$url_file_name = basename($file_url) .'.'. $get_extension;
						}
						elseif((strpos($file_url, 'format=rss') !== false) || (strpos($file_url, '/rss') !== false)){
							if(isset($get_file_headers['Content-Type'])){
								$url_extension = substr($get_file_headers['Content-Type'], strpos($get_file_headers['Content-Type'], 'text/') + strlen('text/'), 3);
								$url_file_name = basename($file_url) . '.' . $url_extension;
							}
							else{
								$url_file_name = basename($file_url) . '.xml';
							}
						}else{
							if(isset($get_file_headers['Content-Type'])){
								if(is_array($get_file_headers['Content-Type']) && isset($get_file_headers['Content-Type'][0])){
									if( strpos($get_file_headers['Content-Type'][0], 'text/') !== false) {	
										$url_extension = substr($get_file_headers['Content-Type'][0], strpos($get_file_headers['Content-Type'][0], 'text/') + strlen('text/'), 3);
									}
								}
								else{
									if( strpos($get_file_headers['Content-Type'], 'text/') !== false) {	
										$url_extension = substr($get_file_headers['Content-Type'], strpos($get_file_headers['Content-Type'], 'text/') + strlen('text/'), 3);
									}
									else{
										$url_extension = substr($get_file_headers['Content-Type'], strpos($get_file_headers['Content-Type'], 'application/') + strlen('application/'), 3);	
									}
								}
								$url_file_name = basename($file_url) . '.' . $url_extension;	
							}
							else{
								$url_file_name = basename($file_url);
							}
						}
					}
				}
			}
			$validate_instance = ValidateFile::getInstance();
			$zip_instance = ZipHandler::getInstance();

$supported_file = ['csv', 'xml', 'zip', 'txt', 'json', 'tsv'];
			// Fallback: If no proper extension, try to detect from headers or content
$file_extension = pathinfo($url_file_name, PATHINFO_EXTENSION);

if (empty($file_extension) || !in_array(strtolower($file_extension), $supported_file)) {
    $headers = get_headers($file_url, 1);
    $contentType = isset($headers['Content-Type']) ? (is_array($headers['Content-Type']) ? $headers['Content-Type'][0] : $headers['Content-Type']) : '';

    if (strpos($contentType, 'xml') !== false) {
        $url_file_name .= '.xml';
    } elseif (strpos($contentType, 'json') !== false) {
        $url_file_name .= '.json';
    } elseif (strpos($contentType, 'csv') !== false) {
        $url_file_name .= '.csv';
    } elseif (strpos($contentType, 'text/plain') !== false) {
        $url_file_name .= '.txt';
    } else {
        // Last resort: peek at body
$response = wp_remote_get($file_url, [
    'timeout'     => 30,
    'redirection' => 5,
    'sslverify'   => false,
    'headers'     => [
        'User-Agent' => 'Mozilla/5.0 (compatible; WordPress Importer)'
    ],
]);

$body_check = wp_remote_retrieve_body($response);

if (!empty($body_check)) {
    // Peek at the start of the file to determine type
    $peek = substr($body_check, 0, 200);

    if (strpos($peek, '<?xml') === 0) {
        $file_extension = 'xml';
        $url_file_name .= '.xml';
    } elseif (strpos($peek, '{') === 0 || strpos($peek, '[') === 0) {
        $file_extension = 'json';
        $url_file_name .= '.json';
    } elseif (strpos($peek, "\t") !== false) {
        $file_extension = 'tsv';
        $url_file_name .= '.tsv';
    } else {
        $file_extension = 'csv';
        $url_file_name .= '.csv';
    }

} 

        if (strpos($body_check, '<?xml') === 0) {
            $url_file_name .= '.xml';
        }
    }
}

			$validate_format = $validate_instance->validate_file_format($url_file_name);
			if($validate_format == 'yes'){
				if (!extension_loaded('curl')) {
					$response['success'] = false;
					$response['message'] = 'The required PHP extension cURL is not installed. Please install it.';
					echo wp_json_encode($response);
					wp_die();
				}				
				$upload_dir = UrlUpload::$smack_csv_instance->create_upload_dir();
				if($upload_dir){
					$url_file_name = str_replace('%20', ' ', $url_file_name);
					$event_key = UrlUpload::$smack_csv_instance->convert_string2hash_key($url_file_name);
					$file_extension = pathinfo($url_file_name, PATHINFO_EXTENSION);

// If the extension is missing, try to get it from Content-Type
if (empty($file_extension)) {
    $headers = get_headers($file_url, 1);
    $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : '';

    if (strpos($contentType, 'xml') !== false) {
        $file_extension = 'xml';
    } elseif (strpos($contentType, 'json') !== false) {
        $file_extension = 'json';
    } elseif (strpos($contentType, 'csv') !== false) {
        $file_extension = 'csv';
    } else {
        $file_extension = 'txt'; // Default fallback
    }
}

					if($file_extension == 'zip'){
						if(!function_exists('curl_version')){
							$response['success'] = false;
							$response['message'] = 'Curl is not exists.Kindly install it.';
							echo wp_json_encode($response); 
							wp_die();
						}
						$zip_response = [];

						$curlCh = curl_init();
						curl_setopt($curlCh, CURLOPT_URL, $file_url);
						curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($curlCh, CURLOPT_FOLLOWLOCATION, true);
						curl_setopt($curlCh, CURLOPT_FAILONERROR, true);
						curl_setopt($curlCh, CURLOPT_MAXREDIRS, 10);
						curl_setopt($curlCh, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
						curl_setopt($curlCh, CURLOPT_CUSTOMREQUEST,'GET');
						curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($curlCh, CURLOPT_SSL_VERIFYHOST, FALSE);
$response = wp_remote_get($file_url, [
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept'     => 'application/xml,text/xml;q=0.9,*/*;q=0.8',
    ],
    'timeout' => 60,
]);

if (is_wp_error($response)) {
    $response['success'] = false;
    $response['message'] = $response->get_error_message();
    echo wp_json_encode($response);
    wp_die();
}

$curlData = wp_remote_retrieve_body($response);

						if(curl_error($curlCh)){
						
							$zip_response['success'] = false;
							$zip_response['message'] = curl_error($curlCh);
							
						}else{
							$path = $upload_dir. $event_key . '.zip';
							$extract_path = $upload_dir . $event_key;

							$file = fopen($path , "w+");
							fputs($file, $curlData);
							chmod($path, 0777);

							$zip_result = $zip_instance->zip_upload($path , $extract_path);
							if($zip_result == 'UnSupported File Format'){
								$zip_response['success'] = false;
								$zip_response['message'] = "UnSupported File Format Inside Zip";
							}
							else{
								$zip_response['success'] = true;
								$zip_response['filename'] = $url_file_name;
								$zip_response['file_type'] = 'zip'; 
								$zip_response['info'] = $zip_result; 
							}

						}
						echo wp_json_encode($zip_response); 
						wp_die();
					}

					$upload_dir_path = $upload_dir. $event_key;
                    if (!is_dir($upload_dir_path)) {
                        wp_mkdir_p( $upload_dir_path);
                    }
                    chmod($upload_dir_path, 0777);
					
					$wpdb->insert( $file_table_name , array( 'file_name' => $url_file_name , 'hash_key' => $event_key , 'status' => 'Downloading','lock' => true ) );
					$last_id = $wpdb->get_results("SELECT id FROM $file_table_name ORDER BY id DESC LIMIT 1",ARRAY_A);
					$lastid=$last_id[0]['id']; 

					$curlCh = curl_init();
					curl_setopt($curlCh, CURLOPT_URL, $file_url);
					curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curlCh, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($curlCh, CURLOPT_FAILONERROR, true);
					curl_setopt($curlCh, CURLOPT_MAXREDIRS, 10);
					curl_setopt($curlCh, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
					curl_setopt($curlCh, CURLOPT_CUSTOMREQUEST,'GET');
					curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($curlCh, CURLOPT_SSL_VERIFYHOST, FALSE);
			
$response = wp_remote_get($file_url, [
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept'     => 'application/xml,text/xml;q=0.9,*/*;q=0.8',
    ],
    'timeout' => 60,
]);

if (is_wp_error($response)) {
    $response['success'] = false;
    $response['message'] = $response->get_error_message();
    echo wp_json_encode($response);
    wp_die();
}

$curlData = wp_remote_retrieve_body($response);

					if(curl_error($curlCh)){
						
						$response['success'] = false;
						$response['message'] = curl_error($curlCh);
						echo wp_json_encode($response); 
						$wpdb->get_results("UPDATE $file_table_name SET status='Download_Failed' WHERE id = '$lastid'");
					}else{
						$path = $upload_dir. $event_key .'/'.$event_key;
						if ($file_extension == 'xlsx' || $file_extension == 'xls') {
							$this->convertXlsxToCsv($curlData ,$upload_dir_path, $event_key);
							chmod($path, 0777);					
                            $file_extension = 'csv';
                        }else{
							$file = fopen($path , "w+");
							fputs($file, $curlData);
							chmod($path, 0777);
							fclose($file);
						}
						$validate_file = $validate_instance->file_validation($path , $file_extension );

						$file_size = filesize($path);
		                $filesize = $validate_instance->formatSizeUnits($file_size);

						if($validate_file == "yes"){
							$wpdb->get_results("UPDATE $file_table_name SET status='Downloaded',`lock`=false WHERE id = '$lastid'");
							$get_result = $validate_instance->import_record_function($event_key , $url_file_name); 
							$template_name = substr($url_file_name, 0, strpos($url_file_name, "."));
							$response['success'] = true;
							$response['filename'] = $url_file_name;
							$response['hashkey'] = $event_key;
							$response['posttype'] = $get_result['Post Type'];
							$response['taxonomy'] = $get_result['Taxonomy'];
							$response['selectedtype'] = $get_result['selected type'];
							$response['file_type'] = $file_extension;
							$response['file_size'] = $filesize;
							$response['templatename'] = $template_name;
							$response['message'] = 'success';
							                            if ($file_extension === 'csv' || $file_extension === 'tsv') {
    $delimiter = DesktopUpload::detect_csv_delimiter($path);
    update_option("smack_csv_delimiter_{$event_key}", $delimiter);
}
							echo wp_json_encode($response); 
						}else{
							$response['success'] = false;
							$response['message'] = $validate_file;
							echo wp_json_encode($response); 
							unlink($path);
							$wpdb->get_results("UPDATE $file_table_name SET status='Download Failed',`lock`=true WHERE id = '$lastid'");
						}
					}
					curl_close ($curlCh);
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
	
	public static function get_original_url($url)
	{
		$url = str_replace(' ', '%20', $url);
		return $url;
	}

	// public function get_filename_from_headers($file_full_name){	
	// 	$arr = explode('filename=', $file_full_name);
	// 	$url_full_file_name = explode('.', $arr[1]);
	// 	$url_extension = substr($arr[1], strpos($arr[1], '.') + strlen('.'), 3);
	// 	$file_name = $url_full_file_name[0] . '.' . $url_extension;
	// 	$file_explode=explode('"',$file_name);
	// 	if(isset($file_explode[1])){
	// 		return $file_explode[1];
	// 	}
	// 	else{
	// 		return $file_explode[0];
	// 	}
	// }

	public function get_filename_from_headers($file_full_name) {
		// Extract the filename part
		preg_match('/filename\*?=([^;]+)/', $file_full_name, $matches);
	
		if (!empty($matches[1])) {
			$filename = trim($matches[1], " \"");
			// Handle cases with encoded filenames (filename*=UTF-8'')
			if (strpos($filename, "UTF-8''") === 0) {
				$filename = urldecode(substr($filename, 7)); // Decode URL-encoded filename
			}
		} else {
			return '';
		}
		return $filename;
	}
	

	public function unshorten_bitly_url($url) {
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_FOLLOWLOCATION => TRUE, 
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_SSL_VERIFYHOST => FALSE, // suppress certain SSL errors
			CURLOPT_SSL_VERIFYPEER => FALSE, 
		));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_exec($ch);
		$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		curl_close($ch);
		return $url;
	}

}