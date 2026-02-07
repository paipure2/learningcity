<?php
/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;

use PhpParser\Error;
use PhpParser\ParserFactory;
use NXP\MathExecutor;
if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly
	require_once(__DIR__.'/../vendor/autoload.php');

class ImportHelpers {
	private static $helpers_instance = null;

	public static function getInstance() {

		if (ImportHelpers::$helpers_instance == null) {
			ImportHelpers::$helpers_instance = new ImportHelpers;
			return ImportHelpers::$helpers_instance;
		}
		return ImportHelpers::$helpers_instance;
	}

	public function get_requested_term_details ($post_id, $term,$taxonomy) {
		if(is_array($term)){
			foreach($term as $terms){
				$termLen = strlen($terms);
				$checktermid = intval($terms);
				$verifiedTermLen = strlen($checktermid);
				if($termLen == $verifiedTermLen) {
					return $terms;
				} 
			
			}
			$reg_term_id = wp_set_object_terms($post_id, $term, $taxonomy);
			
			$terms = get_term_by('name',"$terms","$taxonomy");
			if(isset($terms->term_id)){
				$term_id = $terms->term_id;

				//incase if term id and term taxonomy id are not same
				// global $wpdb;
				// $term_taxonomy_id = $reg_term_id[0];
				// $term_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id = $term_taxonomy_id");
			}
			return $term_id;
		}
		else{
			$termLen = strlen($term);
			$checktermid = intval($term);
			$verifiedTermLen = strlen($checktermid);
			if($termLen == $verifiedTermLen) {
				return $term;
			} else {
				$reg_term_id = wp_set_object_terms($post_id, $term, $taxonomy);
				
				if(isset($reg_term_id[0])){
					$term_id = $reg_term_id[0];
				}
				return $term_id;
			}
		}
	}

	public function get_from_user_details($request_user) {
		global $wpdb;
		$authorLen = strlen($request_user);
		$checkpostuserid = intval($request_user);
		$postAuthorLen = strlen($checkpostuserid);

		if ($authorLen == $postAuthorLen) {
			$postauthor = $wpdb->get_results($wpdb->prepare("select ID,user_login from {$wpdb->prefix}users where ID = %s", $request_user));
			if (empty($postauthor) || !$postauthor[0]->ID) { // If user name are numeric Ex: 1300001
				$postauthor = $wpdb->get_results($wpdb->prepare("select ID,user_login from {$wpdb->prefix}users where user_login = \"{%s}\"",$request_user));
			}
		} else {
			$postauthor = $wpdb->get_results($wpdb->prepare("select ID,user_login from {$wpdb->prefix}users where user_login = %s", $request_user));
		}
		if (empty($postauthor) || !$postauthor[0]->ID) {
			$request_user = 1;
			$admindet = $wpdb->get_results($wpdb->prepare("select ID,user_login from {$wpdb->prefix}users where ID = %d", 1));
			$message = " <b>Author :- </b> not found (assigned to <b>" . $admindet[0]->user_login . "</b>)";
		} else {
			$request_user = $postauthor[0]->ID;
			$admindet = $wpdb->get_results($wpdb->prepare("select ID,user_login from {$wpdb->prefix}users where ID = %s", $request_user));
			$message = " <b>Author :- </b>" . $admindet[0]->user_login;
		}
		$userDetails['user_id'] = $request_user;
		$userDetails['user_login'] = $admindet[0]->user_login;
		$userDetails['message'] = $message;
		return $userDetails;
	}

	public function assign_post_status($data_array) {
		global $wpdb;
		if (isset($data_array['is_post_status']) && $data_array['is_post_status'] != 'on') {
			$data_array ['post_status'] = $data_array['is_post_status'];
			unset($data_array['is_post_status']);
		}
		if(isset($data_array['post_status']) && $data_array['post_status'] == 'trash'){
			$title=$data_array['post_title'];
			$trash = $wpdb->get_results(
				"UPDATE {$wpdb->prefix}posts SET post_status = 'trash' WHERE post_title = '$title'"	
			);
			$wpdb->query($trash);		
		}elseif(isset($data_array['post_status']) && $data_array['post_status'] == 'delete'){
			$post_title=$data_array['post_title'];
			$wpdb->query("DELETE FROM {$wpdb->prefix}posts WHERE post_status = 'trash' and post_title= '$post_title' ");	
			$wpdb->query("DELETE FROM {$wpdb->prefix}posts WHERE post_status = 'delete' ");		
		}
		else {
			if(isset($data_array['post_status']) || isset($data_array['coupon_status'])) {
				if(isset($data_array['post_status'])) {
					$data_array['post_status'] = strtolower( $data_array['post_status'] );
				} else {
					$data_array['post_status'] = strtolower( $data_array['coupon_status'] );
				}
				$data_array['post_status'] = trim($data_array['post_status']);
				if ($data_array['post_status'] != 'publish' && $data_array['post_status'] != 'private' && $data_array['post_status'] != 'draft' && $data_array['post_status'] != 'pending' && $data_array['post_status'] != 'sticky' && $data_array['post_status'] != 'scheduled' && $data_array['post_status'] != 'future') {
					$stripPSF = strpos($data_array['post_status'], '{');
					if ($stripPSF === 0) {
						$poststatus = substr($data_array['post_status'], 1);
						$stripPSL = substr($poststatus, -1);
						if ($stripPSL == '}') {
							$postpwd = substr($poststatus, 0, -1);
							$data_array['post_status'] = 'publish';
							$data_array ['post_password'] = $postpwd;
						} else {
							$data_array['post_status'] = 'publish';
							$data_array ['post_password'] = $poststatus;
						}
					} else {
						$data_array['post_status'] = 'publish';
					}
				}
				if($data_array['post_status'] == 'scheduled'){
					$data_array['post_status'] = 'future';
				}
				if ($data_array['post_status'] == 'sticky') {
					$data_array['post_status'] = 'publish';
					$sticky = true;

				}

			} else {
				$data_array['post_status'] = 'publish';
			}
		}
		return $data_array;
	}

	public function import_post_types($import_type, $importAs = null) {	
		$import_type = trim($import_type);

		$module = array('Posts' => 'post', 'Pages' => 'page', 'Users' => 'user', 'Comments' => 'comments', 'Taxonomies' => $importAs, 'CustomerReviews' =>'wpcr3_review', 'Categories' => 'categories', 'Tags' => 'tags', 'WooCommerce' => 'product', 'WPeCommerce' => 'wpsc-product','WPeCommerceCoupons' => 'wpsc-product','WooCommerceVariations' => 'product', 'WooCommerceOrders' => 'product', 'WooCommerceCoupons' => 'product', 'WooCommerceRefunds' => 'product', 'CustomPosts' => $importAs,'WooCommerceReviews' =>'reviews','JetReviews' => 'jetreviews', 'GFEntries' => 'gfentries');
		foreach (get_taxonomies() as $key => $taxonomy) {
			$module[$taxonomy] = $taxonomy;
		}
		if(array_key_exists($import_type, $module)) {
			return $module[$import_type];
		}
		else {
			return $import_type;
		}
	}

	public function get_header_values($map , $header_array , $value_array, $hash_key =null){		
		$post_values = [];
		global $wpdb;
		$file_extension = '';
		if (is_array($map)) {
			$trim_content = array(
				'->static' => '', 
				'->math' => '', 
				'->cus1' => '',
				'->num' => ''
			);

			foreach($map as $header_keys => $value){
				if( strpos($header_keys, '->cus2') !== false) {
					if(!empty($value)){
						$this->write_to_customfile($value, $header_array, $value_array);
						unset($map[$header_keys]);
					}
				}
				else{
					$header_trim = strtr($header_keys, $trim_content);
	 				if($header_trim != $header_keys){
						unset($map[$header_keys]);
					}
					$map[$header_trim] = $value;
				}
			}

			foreach($map as $key => $value){	
				$csv_value= trim($map[$key]);
				if (isset($csv_value) && $csv_value !== '') {
					//$pattern = "/({([a-z A-Z 0-9 | , _ -]+)(.*?)(}))/";
					$pattern1 = '/{([^}]*)}/';
					$pattern2 = '/\[([^\]]*)\]/';
					$file_table_name = $wpdb->prefix ."smackcsv_file_events";
					$get_id = $wpdb->get_results( "SELECT file_name FROM $file_table_name WHERE `hash_key` = '$hash_key'");
					
					$file_name = !empty($get_id[0]->file_name) ? $get_id[0]->file_name : '';
					if(!empty($file_name) && isset($file_name)){
						$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
					}
					if(preg_match_all($pattern1, $csv_value, $matches, PREG_PATTERN_ORDER) && $file_extension !='xml'  && strpos($key, '->serialize') === false){		
						
						//check for inbuilt or custom function call -> enclosed in []
						if(preg_match_all($pattern2, $csv_value, $matches2)){
							$matched_element = $matches2[1][0];
							
							foreach($matches[1] as $value){
								$get_value = $this->replace_header_with_values($value, $header_array, $value_array);
								$values = '{'.$value.'}';
								if(strpos($get_value , "'") !== false){
									$get_value=str_replace('"','\"',$get_value);
									$get_value = '"'.$get_value.'"';
								}
								else{
									$get_value = "'".$get_value."'";
								}
	
								$matched_element = str_replace($values, $get_value, $matched_element);
							}
							$csv_element = $this->evalPhp($matched_element);
						}
						else{
							$csv_element = $csv_value;

							//foreach($matches[2] as $value){
							foreach($matches[1] as $value){
								$get_key = array_search($value , $header_array);
								if(isset($value_array[$get_key])){
									$csv_value_element = $value_array[$get_key];	
									//}
									$value = '{'.$value.'}';
									$csv_element = str_replace($value, $csv_value_element, $csv_element);
								}
							}
							$math = 'MATH';
							if (strpos($csv_element, $math) !== false) {

								$equation = str_replace('MATH', '', $csv_element);
								$csv_element = $this->evalMath($equation);
							}
						}
						$wp_element= trim($key);
						if(!empty($csv_element) && !empty($wp_element)){
							$post_values[$wp_element] = $csv_element;
						}	
					}

					// for custom function without headers in it
					elseif(preg_match_all($pattern2, $csv_value, $matches2) && $file_extension !='xml'){
						$matched_element = $matches2[1][0];
					
						$wp_element= trim($key);
						$csv_element1 = $this->evalPhp($matched_element);
						$post_values[$wp_element] = $csv_element1;
					}
					
					elseif(!in_array($csv_value , $header_array)){
						$wp_element= trim($key);
						$post_values[$wp_element] = $csv_value;
					}

					else{
					
						$get_key = array_search($csv_value , $header_array);
						if(isset($value_array[$get_key])){
							$csv_element = trim($value_array[$get_key]);
							$wp_element = trim($key);
							if(isset($csv_element) && !empty($wp_element)){
								$post_values[$wp_element] = $csv_element;
							}
						}
					}
				}
			}
		}

		return $post_values;
	}

	public function get_meta_values($map , $header_array , $value_array){

		$post_values = [];
		if (is_array($map)) {
			$trim_content = array(
				'->static' => '', 
				'->math' => '', 
				'->cus1' => '',
				'->num' => ''				
			);

			foreach($map as $header_keys => $value){
				if( strpos($header_keys, '->cus2') !== false) {
					if(!empty($value)){
						$this->write_to_customfile($value, $header_array, $value_array);
						unset($map[$header_keys]);
					}
				}
				else{
					$header_trim = strtr($header_keys, $trim_content);
					if($header_trim != $header_keys){
						unset($map[$header_keys]);
					}
					$map[$header_trim] = $value;
				}
			}

			foreach($map as $key => $value){	
				$csv_value= trim($map[$key]);
$value_assoc = array_combine($header_array, $value_array);
				if(!empty($csv_value)){
					//$pattern = "/({([a-z A-Z 0-9 | , _ -]+)(.*?)(}))/";
					$pattern1 = '/{([^}]*)}/';
					$pattern2 = '/\[([^\]]*)\]/';

					if(preg_match_all($pattern1, $csv_value, $matches, PREG_PATTERN_ORDER)){		
						//check for inbuilt or custom function call -> enclosed in []
						if(preg_match_all($pattern2, $csv_value, $matches2)){
							$matched_element = $matches2[1][0];
							
							foreach($matches[1] as $value){
								$get_value = $this->replace_header_with_values($value, $header_array, $value_array);
								$values = '{'.$value.'}';
								if(strpos($get_value , "'") !== false){
									$get_value=str_replace('"','\"',$get_value);
									$get_value = '"'.$get_value.'"';
								}
								else{
									$get_value = "'".$get_value."'";
								}
	
								$matched_element = str_replace($values, $get_value, $matched_element);
							}
						
							$csv_element = $this->evalPhp($matched_element);
							$wp_element= trim($key);
							if(!empty($csv_element) && !empty($wp_element)){
								$post_values[$wp_element] = $csv_element;
							}
						}
						else{
							$csv_element = $csv_value;
							
							//foreach($matches[2] as $value){
							foreach($matches[1] as $value){
								$get_key = array_search($value , $header_array);
								if(isset($value_array[$get_key])){
									$csv_value_element = $value_array[$get_key];	

									$value = '{'.$value.'}';
									$csv_element = str_replace($value, $csv_value_element, $csv_element);
								}
							}
							$math = 'MATH';
							if (strpos($csv_element, $math) !== false) {
								$equation = str_replace('MATH', '', $csv_element);
								$csv_element = $this->evalMath($equation);
							}

							$wp_element= trim($key);
							if(!empty($csv_element) && !empty($wp_element)){
								$csv_ele1 = explode('|',$csv_element)	;
								$post_values[$wp_element] = $csv_ele1;
							}	
						}
					}

elseif (preg_match_all($pattern2, $csv_value, $matches2)) {

    $matched_element = $matches2[1][0];
    $wp_element = trim($key);
    $csv_element1 = '';

    if ($matched_element === 'fields' && strpos($wp_element, '_wpbdp[fields][') === 0) {
        if (isset($value_assoc[$wp_element])) {
            $csv_element1 = $value_assoc[$wp_element];
        }

        if (!empty($csv_element1)) {
            $csv_element1 = explode('|', $csv_element1);
        }
    } else {
        $dangerous_tokens = '/\b(exec|system|passthru|shell_exec|proc_open|popen|`|curl_exec|curl_multi_exec|file_put_contents|fopen|fread|fwrite|fgets|eval|base64_decode)\b/i';
        if (preg_match($dangerous_tokens, $matched_element)) {
            $csv_element1 = $matched_element;
        } else {
            $looks_like_expr = preg_match('/[A-Za-z_][A-Za-z0-9_]*\s*\(|->|::|\$[A-Za-z_]/', $matched_element);
            if ($looks_like_expr) {
                try {
                    $eval_code = "return " . $matched_element . ";";
                    $csv_element1 = @eval($eval_code);
                } catch (\Throwable $e) {
                    $csv_element1 = null;
                }
            } else {
                $csv_element1 = $matched_element;
            }
        }
    }

    if (!empty($csv_element1) && !empty($wp_element)) {
        $post_values[$wp_element] = $csv_element1;
    }

}
	
					elseif(!in_array($csv_value , $header_array)){
						$wp_element= trim($key);
						$post_values[$wp_element] = $csv_value;
					}

					else{
						$get_key = array_search($csv_value , $header_array);

						if(isset($value_array[$get_key])){
							$csv_element = $value_array[$get_key];	
							$csv_ele1=explode('|',$csv_element)	;
							//foreach($csv_ele1 as $key => $val){
							$wp_element = trim($key);
							if(isset($csv_element) && !empty($wp_element)){
								$post_values[$wp_element] = $csv_ele1;
							}
							//}
							//}

						}
					}
				}
			}
		}

		return $post_values;
	}

	public function evalMath($equation) {
		$result = 0;

		// sanitize imput
		$equation = preg_replace("/[^0-9+\-.*\/()%]/","",$equation);

		// convert percentages to decimal
		$equation = preg_replace("/([+-])([0-9]{1})(%)/","*(1\$1.0\$2)",$equation);
		$equation = preg_replace("/([+-])([0-9]+)(%)/","*(1\$1.\$2)",$equation);
		//$equation = preg_replace("/([0-9]+)(%)/",".\$1",$equation);

		
	    try {
	    	$executor = new MathExecutor();

			return	$executor->execute($equation);
	    } catch (Exception $e) {
	        $return("Unable to calculate equation");
	    }

	}

	public function get_post_ids($post_id , $eventKey,$templatekey = null){
		$smack_instance = SmackCSV::getInstance();
		$recordId = array($post_id);				
		if($templatekey != null) {
			$upload_dir = $smack_instance->create_upload_dir('CLI');
			$eventInfoFile = $upload_dir.$eventKey.'/'.$templatekey.'/'.$templatekey.'.txt';
		}
		else{
			$upload_dir = $smack_instance->create_upload_dir();
			$eventInfoFile = $upload_dir.$eventKey.'/'.$eventKey.'.txt';
		}
		if(file_exists($eventInfoFile)) {
			$handle   = fopen( $eventInfoFile, 'r' );
			$contents = json_decode( fread( $handle, filesize( $eventInfoFile ) ) );
			fclose( $handle );
		}		
		$fp = fopen($eventInfoFile, 'w+');
		if(!empty($contents) && $contents != null) {
			$contents = array_merge( $contents, $recordId );
			$contents = json_encode( $contents );
		} else {
			$contents = json_encode( $recordId );
		}
		fwrite($fp, $contents);
		fclose($fp);
	}

	public function formatSizeUnits($bytes)
	{
		if ($bytes >= 1073741824)
		{
			$bytes = number_format($bytes / 1073741824, 2) . ' GB';
		}
		elseif ($bytes >= 1048576)
		{
			$bytes = number_format($bytes / 1048576, 2) . ' MB';
		}
		elseif ($bytes >= 1024)
		{
			$bytes = number_format($bytes / 1024, 2) . ' KB';
		}
		elseif ($bytes > 1)
		{
			$bytes = $bytes . ' bytes';
		}
		elseif ($bytes == 1)
		{
			$bytes = $bytes . ' byte';
		}
		else
		{
			$bytes = '0 bytes';
		}

		return $bytes;
	}

	public function update_count($unikey_value,$unikey){
		$response = [];				
		global $wpdb;
				
		$log_table_name = $wpdb->prefix ."import_detail_log";
		$get_data =  $wpdb->get_results("SELECT skipped , created , updated , failed FROM $log_table_name WHERE $unikey = '$unikey_value' ");		
		$skipped = $get_data[0]->skipped;
		$response['skipped'] = $skipped + 1;
		$created = $get_data[0]->created;
		$response['created'] = $created + 1;
		$updated = $get_data[0]->updated;
		$response['updated'] = $updated + 1;
		$failed = $get_data[0]->failed;
		$response['failed'] = $failed + 1;

		return $response;
	}

	public function validate_datefield($date,$field,$dateformat,$line_number){		
		if(empty($date)){
			return $date;
		}
		$core_instance = CoreFieldsImport::getInstance();
		$index = "</br><b>Info about " . $field . "</b>";
			//Validate the date
			if(strtotime( $date )) {									
				$date = date( $dateformat, strtotime( $date ) );				
				}							
			else {																											
					//check the date format as mm-dd-yyyy (valid)
					$date = str_replace(array('.','-'), '/', $date);
					if(!strtotime($date)){
						//Invalid date
						//check the date format as 18/05/2022 (valid)
						$date = str_replace('/','-',$date);
						if(strtotime($date)){
							//valid
							$date = date( $dateformat, strtotime( $date ) );
						}
						else {
							//Invalid							
							$core_instance->detailed_log[$line_number][$index] = "Date format provided is wrong. Correct date format is Y-m-d" ;
							$date = '';
						}
					}
					else {											
					//Valid date
					$date = date( $dateformat, strtotime( $date ) );						
					}						
				}														
		return $date;

	}

	public function write_to_customfile($csv_value, $header_array=null, $value_array=null){
		//if(preg_match_all('/{+(.*?)}/', $csv_value, $matches)) {
		
			// foreach($matches[1] as $value){
			// 	$get_value1 = $this->replace_header_with_values($value, $header_array, $value_array);
			// 	$values1 = '{'.$value.'}';
			// 	$get_value1 = "'".$get_value1."'";
			// 	$csv_value = str_replace($values1, $get_value1, $csv_value);
			// }
		
			$upload = wp_upload_dir();
   			$upload_base_url = $upload['basedir'];
        	$customfn_file_path = $upload_base_url . '/smack_uci_uploads/customFunction.php';

			if(!file_exists($customfn_file_path)){
				$add_php_tag = '<?php';
				$openFile = fopen($customfn_file_path, "w+");
				fwrite($openFile, $add_php_tag);
				fclose($openFile);
				chmod($customfn_file_path , 0777);
			}

			$get_custom_content = file_get_contents($customfn_file_path);
			$exp_data =explode('{',$csv_value);
			if(strpos($get_custom_content,$exp_data[0]) !== false) {
			}
			else{
				$openFile = fopen($customfn_file_path, "a+");
				fwrite($openFile, "\n".$csv_value);
				fclose($openFile);
				chmod($customfn_file_path , 0777);
			}
			require_once $customfn_file_path;
		//}
	}

	public function replace_header_with_values($csv_header, $header_array, $value_array){
		$csv_value = $csv_header;
		$get_key = array_search($csv_header , $header_array);
		if(isset($value_array[$get_key])){
			$csv_value = $value_array[$get_key];
		}
		return $csv_value;
	}
	/**
	 * Function to evaluate PHP expressions
	 */
	public function evalPhp($expression)	{
		//$parser = (new ParserFactory)->createForNewestSupportedVersion();

		// try {
    	// 	$parser->parse($expression);
		// 	$value=$parser->parse($expression);
		if(!empty($expression)){
				$expression=$expression;
				return eval('return '.$expression.';');
			}
    // 	} catch (Error $error) {
    // 		return 'Parse Error: '. $error->getMessage();
	// 	}
	}


}