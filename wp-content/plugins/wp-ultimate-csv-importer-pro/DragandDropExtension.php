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
    
class DragandDropExtension {
    private static $drag_drop_instance = null,$validatefile;

    private function __construct(){
        add_action('wp_ajax_displayCSV',array($this,'display_csv_values'));
    }
    
    public static function getInstance() {
            
        if (DragandDropExtension::$drag_drop_instance == null) {
            DragandDropExtension::$drag_drop_instance = new DragandDropExtension;
            DragandDropExtension::$validatefile = ValidateFile::getInstance();
         
            return DragandDropExtension::$drag_drop_instance;
        }
        return DragandDropExtension::$drag_drop_instance;
    }

    public function display_csv_values(){
        check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
        global $wpdb;
        $hashkey = sanitize_key($_POST['HashKey']);
        $templatename = isset($_POST['templatename']) ? sanitize_text_field($_POST['templatename']) : "";
        $get_row = intval($_POST['row']);
        $file_table_name = $wpdb->prefix ."smackcsv_file_events";
        $template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
        $row = $get_row - 1;

        if(empty($hashkey)){	
			$get_detail   = $wpdb->get_results( "SELECT eventKey FROM $template_table_name WHERE templatename = '$templatename' " );
			$hashkey = $get_detail[0]->eventKey;
        }

        $smackcsv_instance = SmackCSV::getInstance();
		$upload_dir = $smackcsv_instance->create_upload_dir();

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {  // Only do this if PHP version is less than 8.1.0
            if (!ini_get("auto_detect_line_endings")) {  // If auto_detect_line_endings is not enabled
                ini_set("auto_detect_line_endings", true);  // Enable it to handle different line endings
            }
        }
		$info = [];
		if (($h = fopen($upload_dir.$hashkey.'/'.$hashkey, "r")) !== FALSE) 
		{

            $line_number = 0;
            $Headers = [];
            $Values = [];
            $response = [];
            $delimiters = array( ',','\t',';','|',':','&nbsp');
            $file_path = $upload_dir . $hashkey . '/' . $hashkey;
            $delimiter = DragandDropExtension::$validatefile->getFileDelimiter($file_path, 5);
            $array_index = array_search($delimiter,$delimiters);
            if($delimiter == '\t'){
                while (($data = fgetcsv($h, 0, "\t")) !== FALSE) 
                {		
                    // Read the data from a single line
                    $trimmed_info = array_map('trim', $data);
                    array_push($info , $trimmed_info);
                    if($line_number == 0){
                        $Headers = $info[$line_number];
                    }else{
                        $values = $info[$line_number];
                        array_push($Values , $values);		
                    }
                    $line_number ++;		
                }
            }
            else{
                if($array_index == 5){
                    $delimiters[$array_index] = ' ';
                }
                while (($data = fgetcsv($h, 0, $delimiters[$array_index])) !== FALSE) 
                {		
                    $trimmed_array = array_map('trim', $data);
                    array_push($info , $trimmed_array);
                    if($line_number == 0){
                        $Headers = $info[$line_number];
                    }else{
                        $values = $info[$line_number];
                        array_push($Values , $values);		
                    }
                    $line_number ++;		
                }	
                fclose($h);
            }  
        }
        $get_total_row = $wpdb->get_results("SELECT total_rows FROM $file_table_name WHERE hash_key = '$hashkey' ");
        $total_row = $get_total_row[0]->total_rows;

        $response['success'] = true;
        $response['total_rows'] = $total_row;
        $response['Headers'] = $Headers;
        $response['Values'] = $Values[$row];
        
        echo wp_json_encode($response);
        wp_die();
    }

    /**
	 * @param $xml
	 * @param $query
	 * @param $row
	 * @return string
	 */
	public function parse_element($xml,$value,$row,$parent_name,$child_name){
        $xpath = new \DOMXPath($xml);
        $query = '/'.$parent_name.'/'.$child_name.'['.$row.']/'.$value;
        $entries = $xpath->query($query);
       
		$content = $entries->item(0)->textContent;
		return $content;
	}
}