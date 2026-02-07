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
    
class ImportConfiguration {
    private static $import_config_instance = null,$mapping_extension;

    private function __construct(){
		add_action('wp_ajax_updatefields',array($this,'get_update_fields'));
		add_action('wp_ajax_rollback_now',array($this,'rollback_now'));
		add_action('wp_ajax_clear_rollback',array($this,'clear_rollback'));
    }
    
    public static function getInstance() {
            
        if (ImportConfiguration::$import_config_instance == null) {
            ImportConfiguration::$import_config_instance = new ImportConfiguration;
			ImportConfiguration::$mapping_extension = new MappingExtension;
            return ImportConfiguration::$import_config_instance;
        }
        return ImportConfiguration::$import_config_instance;
    }

    public static function get_update_fields(){
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		global $wpdb;
		$import_type = sanitize_text_field($_POST['Types']);
        $mode = sanitize_text_field($_POST['Mode']);
        $response = [];
		$taxonomies = get_taxonomies(); 
		if($mode == 'Update') {
		
			$fields = array( 'post_title', 'ID', 'post_name');
			if(is_plugin_active('jet-engine/jet-engine.php')){
				if($import_type == 'Media'){
					$Image_fields = array('media_id');
					$response['update_fields'] = $Image_fields;
					echo wp_json_encode($response);
					wp_die();
				}
				$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
				foreach($get_slug_name as $key=>$get_slug){
					$value=$get_slug->slug;
					if($import_type == $value){
						$fields = array('_ID');
					}
				}
			}
			if(is_plugin_active('jet-reviews/jet-reviews.php')) {
				if($import_type == 'JetReviews'){
					$fields = array('ID');
				}
			}
			if(is_plugin_active('jet-booking/jet-booking.php')){
				if($import_type == 'JetBooking'){
					$fields = array('booking_id');
				}
			}
			if($import_type == 'Images'){
				$fields =array('Filename','image_url','ID');
			}

			if($import_type == 'Comments'){
				$fields =array('comment_post_ID');
			}
			if($import_type == 'lp_order'){
				$fields = array('ORDER_ID');
			}
			if($import_type == 'WooCommerce Orders'){
				$fields = array('ORDERID');
			}	
			if($import_type == 'WooCommerce Coupons' || $import_type =='WPeCommerce Coupons'){
				$fields =  array('COUPONID');
			}
			if($import_type == 'WooCommerce Refunds'){
				$fields = array('REFUNDID');
			}
			if($import_type == 'WooCommerce Product Variations'){
				$fields = array('VARIATIONSKU', 'VARIATIONID');
			}
			if($import_type == 'WooCommerce Product' || $import_type == 'WPeCommerce Products'){
				array_push($fields,"PRODUCTSKU");
            }
            if($import_type == 'WooCommerce Categories') {                              
                		$fields =array('name','slug');
			}
			if($import_type == 'WooCommerce Tags') {                              
                		$fields =array('TERMID','slug');
			}
			if($import_type == 'WooCommerce Attributes'){
				$fields =array('name','slug');
			}
			if($import_type == 'Customer Reviews'){
				$fields = array('review_id');
			}
			if($import_type == 'Users' || $import_type == 'WooCommerce Customer'){
				$fields = array('user_email','ID','user_login');
			}
			if($import_type == 'Tickets'){
				$fields = array('ID' ,'ticket_name');
			}
			if($import_type == 'ngg_pictures'){
				$fields = array('ID','Filename');
			}
			elseif (in_array($import_type, $taxonomies)) {
				$fields = array('slug','termid');
			}
		}
		else {
			if (in_array($import_type, $taxonomies)) {
				$fields = array('slug');
			}
			else if($import_type == 'Media'){
				$fields = array('media_id');
			}
			else if($import_type == 'WooCommerce Categories') {                              
                		$fields =array('name','slug');
			}
			else if($import_type == 'WooCommerce Tags') {                              
                		$fields =array('slug');
			}
			else if($import_type == 'WooCommerce Product Variations'){
				$fields = array('VARIATIONSKU');
			}
			else if($import_type == 'Users' || $import_type == 'WooCommerce Customer'){
				$fields = array('user_email');
			}
			else{
				$fields = array( 'ID','post_title', 'post_name');
		    	}
			if($import_type == 'WooCommerce Product' || $import_type == 'WPeCommerce Products'){
				array_push($fields,"PRODUCTSKU");
			}
			if(is_plugin_active('jet-engine/jet-engine.php')){
			
				$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
				foreach($get_slug_name as $key=>$get_slug){
					$value=$get_slug->slug;
					if($import_type == $value){
						$fields = array('_ID');
					}
				}
			}	
		}
		$response['update_fields'] = $fields;
		$response['category_list'] = ImportConfiguration::$mapping_extension->get_categories_list($import_type);
		$response['CustomPostCheck'] = ImportConfiguration::$mapping_extension->CustomPostCheck($import_type) ;
        echo wp_json_encode($response);
        wp_die();
		
    }
    public function get_rollback_tables($type){
		global $wpdb;
        if($type == 'Users'){
                $tables = array('users','usermeta');
        }elseif($type == 'Comments'){
                $tables = array('comments','commentmeta');
        }elseif($type == 'Customer Reviews'){
				$tables = array('posts','postmeta');
					if(is_plugin_active('wp-customer-reviews/wp-customer-reviews-3.php')){
                	}
        }elseif($type == 'Events' || $type == 'Event Locations' || $type == 'Tickets'){
				if(is_plugin_active('events-manager/events-manager.php')){
                    $tables = array('posts','postmeta','em_locations','em_tickets','em_events');
                }
        }else{
				$tables = array('posts','postmeta','termmeta','terms','term_relationships','term_taxonomy','options','usermeta','comments','commentmeta','ultimate_csv_importer_shortcode_manager');
				if(is_plugin_active('wpml-multilingual-cms/sitepress.php')){
                        array_push($tables,'icl_translations');
                }
                if($type == 'WPeCommerce Products'){
                        array_push($tables,'wpsc_coupon_codes');
				}
				if(is_plugin_active('custom-field-suite/cfs.php')){
                        array_push($tables,'cfs_values');
                }
				if (is_plugin_active('jet-engine/jet-engine.php')) {
					$table_name = $wpdb->prefix . 'jet_rel_default';
					$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");			
					if ($table_exists) {
						array_push($tables, 'jet_rel_default');
					}
				}
        }
        $sqltables = array_map(function($tables) use($wpdb) {
                return $wpdb->prefix . $tables;
        }, $tables);
        return $sqltables;
    }

    public function set_backup_restore($eventkey, $type, $tables = null){
		$dbname = DB_NAME;
		$dbuser = DB_USER;
		$dbpass = DB_PASSWORD;
		$dbhost = DB_HOST;
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		$upload_dir = $upload_dir . '/smack_uci_uploads/';
		$uploadpath = $upload_dir ."rollback_files/". $eventkey;
		$filename = 'Backup_'.$eventkey.'.sql';
		if (!is_dir($uploadpath)) {
                        wp_mkdir_p($uploadpath);
		}
		chmod($uploadpath , 0777);
		$filepath = $uploadpath.'/'.$filename;
		if($type == 'backup'){
			$backtabs = implode(' ',$tables);
			$command = "mysqldump -h{$dbhost} -u{$dbuser} -p'{$dbpass}' {$dbname} {$backtabs} > {$filepath}";
			exec($command,$output,$return);
			if(!$return){
				return 'Backup Completed';
			}else{
				return 'Not Completed';
			}
		}
		if($type == 'restore'){
			if(file_exists($filepath)) {
				$command = "mysql -h{$dbhost} -u{$dbuser}  -p'{$dbpass}' {$dbname} < {$filepath}";
				exec($command,$output,$return);
				
				if(!$return){
					return 'Rollback Completed';
				}else{
					return 'Not Completed';
				}
			}else{
				return 'File not exists';
			}
		}
		if($type == 'delete'){
			if (!unlink($filepath)){
				return 'Error Deleting'.$filename;
			}else{
				rmdir($uploadpath); 
				return 'Deleted'.$filename;
			}
		}
    }

    public function rollback_now(){
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		$response = [];
		$eventKey = sanitize_key($_POST['HashKey']);
		$tables = '';	
		$result = $this->set_backup_restore($eventKey,'restore', $tables);

		if($result == 'Rollback Completed'){
			$response['success'] = true;
		}else{
			$response['success'] = false;
		}

		$response['message'] = $result;
		echo wp_json_encode($response);
        wp_die();
    }

    public function clear_rollback(){
		$response = [];
		$eventKey = sanitize_key($_POST['HashKey']);
		$tables = '';
		$result = $this->set_backup_restore($eventKey,'delete',$tables);
		$search = 'Error Deleting';

		if (strpos($result, $search) !== false){
			$response['success'] = false;
		}else{
			$response['success'] = true;
		}
			
		$response['message'] = $result;
        echo wp_json_encode($response);
        wp_die();
    }
}
