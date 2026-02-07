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
class ClientMode{
    public static $instance = null, $validatefile, $media_instance;
	public static $failed_images_instance = null;
	public static $smackcsv_instance = null;
	public static $core = null, $nextgen_instance;
	public $media_log;
    private $categoryList = [];
    private $isCategory;
    public static function getInstance()
	{
        if (ClientMode::$instance == null) {
            ClientMode::$instance = new ClientMode;
            // ClientMode::$smackcsv_instance = SmackCSV::getInstance();
            ClientMode::$validatefile = new ValidateFile;
            // ClientMode::$failed_images_instance = FailedImagesUpdate::getInstance();
            // ClientMode::$nextgen_instance = new NextGenGalleryImport;
             return ClientMode::$instance;
        }
        return ClientMode::$instance;
    }
	public function __construct()
	{
        add_action('wp_ajax_get_export_template', array($this, 'get_export_template'));
        add_action('wp_ajax_nopriv_get_export_template', [$this, 'get_export_template']); 
		add_action('wp_ajax_run_export', array($this, 'run_export'));
        add_action('wp_ajax_nopriv_run_export', [$this, 'run_export']); 
		add_action('wp_ajax_get_post_content', array($this, 'get_post_content'));
        add_action('wp_ajax_nopriv_get_post_content', [$this, 'get_post_content']); 
		
    }
    public function get_export_template(){
       
        global $wpdb;
		$export_template_table_name = $wpdb->prefix ."ultimate_csv_importer_export_template";
        $current_user = wp_get_current_user();
        $current_role = $current_user->roles;
        global $wpdb;
        foreach($current_role as $role_key => $roles){
            $role = $roles;
        }    
        $export_template = $wpdb->get_results("SELECT * FROM $export_template_table_name WHERE client_mode='true' AND client_mode_values like '%$role%'",ARRAY_A);
        // $export_template = $wpdb->get_results("SELECT * FROM $export_template_table_name",ARRAY_A);
        $export_template_arr = array();
        foreach($export_template as $export_key => $export_val){
            $export_template_arr[$export_key]['id'] = $export_val['id'];
            $export_template_arr[$export_key]['filename'] = $export_val['filename'].'.'.$export_val['export_type'];
            $export_template_arr[$export_key]['module'] = $export_val['module'];
            $export_template_arr[$export_key]['export_mode'] = $export_val['export_mode'];
            $export_template_arr[$export_key]['type'] = $export_val['type'];
            $export_template_arr[$export_key]['client_mode'] = $export_val['client_mode'];

        }
        $response['success'] = true;
        $response['info'] =$export_template_arr;
        echo wp_json_encode($response);
        wp_die();

    }
    public function run_export(){
        $id = $_POST['id'];
        $filename = $_POST['file_name'];
        global $wpdb;
        $export_template_table_name = $wpdb->prefix ."ultimate_csv_importer_export_template";
        $export_template = $wpdb->get_results("SELECT * FROM $export_template_table_name WHERE id=$id",ARRAY_A);
    }
}
$clientmode_obj = new ClientMode();
