<?php
/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/
namespace Smackcoders\WCSV;
require_once(__DIR__.'/../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

if(class_exists('\Smackcoders\WCSV\MappingExtension')){
	class ExportExtension extends \Smackcoders\WCSV\MappingExtension {
		public $response = array();
		public $headers = array();
		public $module;	
		public $jet_cptfields;
		public $jet_types;
		public $jet_rep_cptfields;
		public $jet_rep_cpttypes;
		public $exportType = 'csv';
		public $optionalType = null;	
		public $totalRowCount;
		public $conditions = array();	
		public $eventExclusions = array();
		public $fileName;
		public $type;
		public $query_data;	
		public $data = array();	
		public $heading = true;	
		public $delimiter = ',';
		public $enclosure = '"';
		public $auto_preferred = ",;\t.:|";
		public $output_delimiter = ',';
		public $linefeed = "\r\n";
		public $export_mode;
		public $alltoolsetfields;
		public $allacf;
		public $allpodsfields;
		public $typeOftypesField;
		public $export_log = array();
		public $limit, $mode, $checkSplit, $offset;
		protected static $instance = null,$mapping_instance,$export_handler,$post_export,$jet_book_export,$jet_reviews_export,$woocom_export,$review_export,$ecom_export,$jet_custom_table_export,$wpquery_export;
		protected $plugin,$activateCrm,$crmFunctionInstance;
		public $plugisnScreenHookSuffix=null;

		/**
		 * ExportExtension constructor.
		 * Set values into global variables based on post value
		 */
		public function __construct() {
			$this->plugin = Plugin::getInstance();
		}

		public static function getInstance() {
			if ( null == self::$instance ) {
				self::$instance = new self;
				ExportExtension::$mapping_instance = MappingExtension::getInstance();
				ExportExtension::$export_handler = ExportHandler::getInstance();
				ExportExtension::$post_export = PostExport::getInstance();
				ExportExtension::$woocom_export = WooCommerceExport::getInstance();
				ExportExtension::$jet_book_export = JetBookingExport::getInstance();
				ExportExtension::$jet_custom_table_export = JetCustomTableExport::getInstance();
				ExportExtension::$jet_reviews_export = JetReviewsExport::getInstance();
				ExportExtension::$review_export = CustomerReviewExport::getInstance();
				ExportExtension::$ecom_export = EComExport::getInstance();
				ExportExtension::$wpquery_export = WPQueryExport::getInstance();
				self::$instance->doHooks();
			}
			return self::$instance;
		}	

		public  function doHooks(){
			add_action('wp_ajax_parse_data',array($this,'parseData'));
			add_action('wp_ajax_total_records', array($this, 'totalRecords'));
			add_action('wp_ajax_preview_records', array($this, 'previewRecords'));
		}

		public function totalRecords(){
			check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
			global $wpdb;	
			global $sitepress;
			$elementor = null;
			$module = sanitize_text_field($_POST['module']);
			$optionalType = isset($_POST['optionalType'])? sanitize_text_field($_POST['optionalType']):'';
			if(empty($optionalType)){
				$check_for_template = $wpdb->get_results("SELECT filename FROM {$wpdb->prefix}ultimate_csv_importer_export_template WHERE module = '$module' ");
			}else{
				$check_for_template = $wpdb->get_results("SELECT filename FROM {$wpdb->prefix}ultimate_csv_importer_export_template WHERE module = '$module' AND optional_type = '$optionalType' ");
			}

			$response = [];
			switch($module) {
			case "WooCommerce":
			case "WooCommerceOrders":
			case "WooCommerceCoupons":
			case "WooCommerceRefunds":
			case "WooCommerceVariations":
			case "WooCommerceCustomer":					
			{
				if(!is_plugin_active('woocommerce/woocommerce.php')){
					$response['count'] = 0;							
					echo wp_json_encode($response);
					wp_die();	
				}
				break;
			}
			// Add JetReviews if the JetReviews plugin is active
			if(is_plugin_active('jet-reviews/jet-reviews.php')) {
				$importas['JetReviews'] = 'jetreviews';
				}
		
			case "JetBooking":				
				{
				if(!is_plugin_active('jet-booking/jet-booking.php')){
					$response['count'] = 0;							
					echo wp_json_encode($response);
					wp_die();	
				}
				break;
			}
			}
			if($module == 'CustomPosts'){
			}
			if(empty($check_for_template)){
				$response['show_template'] = false;
			}else{
				$response['show_template'] = true;
			}

			if ($module == 'WooCommerceOrders') {
				$module = 'shop_order';
			}
			elseif ($module == 'WooCommerceCoupons') {
				$module = 'shop_coupon';
			}
			elseif ($module == 'WooCommerceRefunds') {
				$module = 'shop_order_refund';
			}
			elseif ($module == 'WooCommerceVariations') {
				$module = 'product_variation';
			}
			elseif($module == 'JetBooking'){
			$result = jet_abaf_get_bookings( [ 'return' => 'arrays' ] );
			if($result)	{
				$response['count'] = count($result);
			}
			else{
				$response['count'] = 0;
			}							
			echo wp_json_encode($response);
			wp_die();		
			}
			elseif($module == 'WooCommerceCustomer'){
				$user_count = count_users();
				$result = isset($user_count['avail_roles']['customer']) ? $user_count['avail_roles']['customer'] : 0;
				$response['count'] = $result;							
				echo wp_json_encode($response);
				wp_die();
			}
			elseif($module == 'WPeCommerceCoupons'){
				$result = $wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}wpsc_coupon_codes'");
				if($result)	{
					$query = $wpdb->get_col("SELECT * FROM {$wpdb->prefix}wpsc_coupon_codes");
					$response['count'] = count($query);
				}
				else
					$response['count'] = 0;							
				echo wp_json_encode($response);
				wp_die();		
			}
			elseif($module == 'Comments' || $module == 'WooCommerceReviews') {
				$response['count'] = $this->commentsCount($module);
				echo wp_json_encode($response);
				wp_die();
			}
			elseif($module == 'JetReviews') {
				$reponse['count'] = $this->countJetReviews($module);
				echo wp_json_encode($response);
			}
			elseif($module == 'Images'){
				$get_images = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts where post_type='attachment'");
				$response['count'] = count($get_images);
				echo wp_json_encode($response);
				wp_die();
			}
			elseif($module == 'Users'){
				$get_available_user_ids = "select DISTINCT ID from {$wpdb->prefix}users u join {$wpdb->prefix}usermeta um on um.user_id = u.ID";
				$availableUsers = $wpdb->get_col($get_available_user_ids);
				$response['count'] = count($availableUsers);
				echo wp_json_encode($response);
				wp_die();
			}
			elseif($module == 'Tags'){
				$query = "SELECT * FROM {$wpdb->prefix}terms t INNER JOIN {$wpdb->prefix}term_taxonomy tax 
					ON  `tax`.term_id = `t`.term_id WHERE `tax`.taxonomy =  'post_tag'";         
				$get_all_taxonomies =  $wpdb->get_results($query);
				$response['count'] = count($get_all_taxonomies);
				echo wp_json_encode($response);
				wp_die();
			}
			elseif($module == 'Categories'){
				$query = "SELECT * FROM {$wpdb->prefix}terms t INNER JOIN {$wpdb->prefix}term_taxonomy tax 
					ON  `tax`.term_id = `t`.term_id WHERE `tax`.taxonomy =  'category'";         
				$get_all_taxonomies =  $wpdb->get_results($query);
				$response['count'] = count($get_all_taxonomies);
				echo wp_json_encode($response);
				wp_die();
			}
			elseif($module == 'Taxonomies'){
				$query = "SELECT * FROM {$wpdb->prefix}terms t INNER JOIN {$wpdb->prefix}term_taxonomy tax 
					ON  `tax`.term_id = `t`.term_id WHERE `tax`.taxonomy =  '{$optionalType}'";         
				$get_all_taxonomies =  $wpdb->get_results($query);
				$response['count'] = count($get_all_taxonomies);
				echo wp_json_encode($response);
				wp_die();
			}
			elseif($module == 'CustomPosts' && $optionalType == 'nav_menu_item'){
				$get_menu_ids = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}terms AS t LEFT JOIN {$wpdb->prefix}term_taxonomy AS tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'nav_menu' ", ARRAY_A);
				$response['count'] = count($get_menu_ids);
				echo wp_json_encode($response);
				wp_die();
			}
			elseif($module == 'CustomPosts' && $optionalType == 'widgets'){
				$response['count'] = 1;
				echo wp_json_encode($response);
				wp_die();
			}
			elseif($module == 'CustomPosts' && $optionalType == 'gfentries'){

				$forms = \GFAPI::get_forms();
				$overall_count = 0;

				foreach ($forms as $form) {
					$form_id = $form['id'];
					$count = \GFAPI::count_entries($form_id);
					$overall_count += $count;
				}

				$response['count'] = $overall_count;
				echo wp_json_encode($response);
				wp_die();
			}
			else {
				if($module == 'CustomPosts') {
					$optional_type = $optionalType;
				}
				$optional_type=isset($optional_type)?$optional_type:'';
				$module = ExportExtension::$post_export->import_post_types($module,$optional_type);
			}
			if(is_plugin_active('jet-engine/jet-engine.php')){
				$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
				foreach($get_slug_name as $key=>$get_slug){
					$value=$get_slug->slug;
					$optional_type=$value;	
					if($optionalType == $optional_type){
						$table_name='jet_cct_'.$optional_type;
						$get_menu= $wpdb->get_results("SELECT * FROM {$wpdb->prefix}$table_name");
						if(is_array($get_menu))
							$response['count'] = count($get_menu);
						else
							$response['count'] = 0;

						echo wp_json_encode($response);
						wp_die();
					}
				}
			}
			$get_post_ids = "select DISTINCT ID from {$wpdb->prefix}posts";
			$get_post_ids .= " where post_type = '$module'";

			if($module == 'product' && is_plugin_active('woocommerce/woocommerce.php')){
				if(($sitepress !=null) ||is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php') || is_plugin_active('polylang-wc/polylang-wc.php') || is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php')){
					//TODO temporary fix 
					//wc_get_products only exports default language product 
					$products = "select DISTINCT ID from {$wpdb->prefix}posts";
					$products .= " where post_type = '$module'";
					$products .= "and post_status in ('publish','draft','future','private','pending') ";
					$products = $wpdb->get_col($products);
					$response['count'] = count($products);

				}
				else{
					//new code updated
					$product_counts = wp_count_posts('product');
					$statuses = ['publish', 'draft', 'future', 'private', 'pending'];
					
					$total_count = 0;
					foreach ($statuses as $status) {
						if (isset($product_counts->$status)) {
							$total_count += $product_counts->$status;
						}
					}
					
					// Count variable products
					$variable_products = wc_get_products([
						'type'   => 'variable',
						'status' => $statuses, // Include the same statuses as the total count
						'limit'  => -1, // Fetch all variable products
					]);
					
					$variable_product_count = count($variable_products);
					
					// Count variations for variable products
					$variation_count = 0;
					foreach ($variable_products as $variable_product) {
						$variations = $variable_product->get_children(); // Get variations for each variable product
						$variation_count += count($variations);
					}
					$response['count'] = $total_count;
					$response['variation_count'] = $variation_count;


					//this code old one

					// $status = array('publish', 'draft', 'future', 'private', 'pending');
					// $products = wc_get_products(array('status' => $status, 'numberposts' => -1 ));
					// $product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
					// $products = wc_get_products(array('status' => $product_statuses , 'limit' => -1));
					// $variable_product_ids = [];
					// foreach($products as $product){
					// 	if ($product->is_type('variable')) {
					// 		$variable_product_ids[] = $product->get_id();
					// 	}
					// }	
					// $variation_count = 0;
					// $variation_ids = array();
					// foreach($variable_product_ids as $variable_product_id){
					// 	$variable_product = wc_get_product($variable_product_id);
					// 	$variation_ids[]  = $variable_product->get_children();
					// }
					// $extracted_ids = [];
					// foreach ($variation_ids as $v_ids) {
					// 	foreach ($v_ids as $v_id) {
					// 		$extracted_ids[] = $v_id;
					// 	}
					// }
					// if(!empty($extracted_ids)){
					// 	$response['variation_count'] = count($extracted_ids);
					// }
					//$response['count'] = count($products);
				}
				
				$response['elementor'] = $elementor;
				update_option('woocommerce_product_count', $response['count']);
				echo wp_json_encode($response);	
				wp_die();
			}elseif($module  == 'product_variation'){
				if(is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php') || is_plugin_active('polylang-wc/polylang-wc.php') || is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php')){
					$extracted_ids = "select DISTINCT ID from {$wpdb->prefix}posts";
					$extracted_ids .= " where post_type = '$module'";
					$extracted_ids .= "and post_status in ('publish','draft','future','private','pending') AND post_parent !=0";
					$extracted_id = $wpdb->get_col($extracted_ids);
					$extracted_ids =array();
					//fix added for prema
					foreach($extracted_id as $ids){
						$parent_id = $wpdb->get_var("SELECT post_parent FROM {$wpdb->prefix}posts where ID=$ids");
						$post_status =$wpdb->get_var("SELECT post_status FROM {$wpdb->prefix}posts where ID=$parent_id");
						if(!empty($post_status )){
							if($post_status !='trash' && $post_status != 'inherit'){
								$extracted_ids [] =$ids;
							}

						}

					}

				}
				else{
					$product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
					$products = wc_get_products(array('status' => $product_statuses , 'limit' => -1));
					$variable_product_ids = [];
					foreach($products as $product){
						if ($product->is_type('variable')) {
							$variable_product_ids[] = $product->get_id();
						}
					}	
					$variation_count = 0;
					$variation_ids = array();
					foreach($variable_product_ids as $variable_product_id){
						$variable_product = wc_get_product($variable_product_id);
						$variation_ids[]  = $variable_product->get_children();
					}
					$extracted_ids = [];
					foreach ($variation_ids as $v_ids) {
						foreach ($v_ids as $v_id) {
							$extracted_ids[] = $v_id;
						}
					}
				}
				$response['count'] = count($extracted_ids);
				echo wp_json_encode($response);	
				wp_die();			
			}elseif($module == 'shop_order'){
				if(is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php') || is_plugin_active('polylang-wc/polylang-wc.php') || is_plugin_active('polylang-wc/polylang-wc.php') || is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php')){
					$order_statuses = array('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending');
					$orders= wc_get_orders(array('status' => $order_statuses));
					foreach ($orders as $order_id) {
						$order_ids[] = $order_id->get_id();
					} 
					foreach($order_ids as $ids){
						$module =$wpdb->get_var("SELECT post_type FROM {$wpdb->prefix}posts where id=$ids");
					}
					if($module == 'shop_order_placehold'){
						$orders = "select DISTINCT p.ID from {$wpdb->prefix}posts as p inner join {$wpdb->prefix}wc_orders as wc ON p.ID=wc.id";
						$orders.= " where p.post_type = '$module'";
						$orders .= "and wc.status in ('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending','wc-refunded')";
						$orders = $wpdb->get_col($orders);
					}
					else{
						$orders = "select DISTINCT ID from {$wpdb->prefix}posts";
						$orders.= " where post_type = '$module'";
						$orders .= "and post_status in ('wc-completed','wc-cancelled','wc-on-hold','wc-processing','wc-pending','wc-refunded')";
						$orders = $wpdb->get_col($orders);
					}

				}
				else{
					$order_statuses = array('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending','wc-refunded');
					$orders = $wpdb->get_col("
    SELECT ID 
    FROM {$wpdb->prefix}posts 
    WHERE post_type = 'shop_order' 
    AND post_status IN ('wc-completed','wc-cancelled','wc-on-hold','wc-processing','wc-pending','wc-refunded')
");				}
				$response['count'] = count($orders);
				$response['elementor'] = $elementor;
				update_option('woocommerce_order_count', $response['count']);
				echo wp_json_encode($response);	
				wp_die();
			}elseif ($module == 'shop_coupon') {
				$get_post_ids .= " and post_status in ('publish','draft','pending','private','future')";
			}elseif ($module == 'shop_order_refund') {

			}
			elseif($module == 'lp_order'){
				$get_post_ids .= " and post_status in ('lp-pending', 'lp-processing', 'lp-completed', 'lp-cancelled', 'lp-failed')";
			}
			else{
				$get_post_ids .= " and post_status in ('publish','draft','future','private','pending')";
			}
			$get_total_row_count = $wpdb->get_col($get_post_ids);
			$total = count($get_total_row_count);

			$response['count'] = $total;
			$response['elementor'] = $elementor;
			echo wp_json_encode($response);
			wp_die();
		}

		public function parseData(){				
			check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');		
			if(!empty($_POST)) {
				$query_data = isset($_POST['query_data'])?sanitize_text_field($_POST['query_data']):'';
        		$type = isset($_POST['type'])?sanitize_text_field($_POST['type']):'';
				//static added
				// $query_data = "\"post_type\" =>\"product\", \"post_status\" => \"publish\"";
				// $type='post';
				$categorybased = sanitize_text_field($_POST['categoryName']);
				$this->module          = sanitize_text_field($_POST['module']);
				$this->exportType      = isset( $_POST['exp_type'] ) ? sanitize_text_field( $_POST['exp_type'] ) : 'csv';
				$conditions =  str_replace("\\" , '' , $_POST['conditions']);
				$conditions = json_decode($conditions, True);

				$conditions['specific_period']['to'] = date("Y-m-d", strtotime($conditions['specific_period']['to']) );
				$conditions['specific_period']['from'] = date("Y-m-d", strtotime($conditions['specific_period']['from']) );
				$this->conditions      = isset( $conditions ) && ! empty( $conditions ) ? $conditions : array();
$this->exportRoleChecked        = isset($_POST['exportRoleChecked']) ? filter_var($_POST['exportRoleChecked'], FILTER_VALIDATE_BOOLEAN) : false;
$this->roleSelectedField        = isset($_POST['roleSelectedField']) ? sanitize_text_field($_POST['roleSelectedField']) : '';

$this->exportUserProductChecked = isset($_POST['exportUserProductChecked']) ? filter_var($_POST['exportUserProductChecked'], FILTER_VALIDATE_BOOLEAN) : false;
$this->userProductSelectedField = isset($_POST['userProductSelectedField']) ? sanitize_text_field($_POST['userProductSelectedField']) : '';

				if($this->module == 'Taxonomies' || $this->module == 'CustomPosts' ){
					$this->optionalType    = sanitize_text_field($_POST['optionalType']);
				}
				else{
					$this->optionalType    = $this->getOptionalType($this->module);
				}
				$eventExclusions = str_replace("\\" , '' , $_POST['eventExclusions']);
				$eventExclusions = json_decode($eventExclusions, True);
				$this->eventExclusions = isset( $eventExclusions ) && ! empty( $eventExclusions ) ? $eventExclusions : array();
				$this->fileName        = isset( $_POST['fileName'] ) ? sanitize_file_name( $_POST['fileName'] ) : '';
				if(empty($_POST['offset'] ) || $_POST['offset']== 'undefined'){
					$this->offset = 0 ;
				}
				else{
					$this->offset          = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
				}
				if(!empty($_POST['limit'] )){
					$this->limit           = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 1000;
				}
				else{
					if(!empty($conditions['specific_iteration_id']['is_check']) && $conditions['specific_iteration_id']['is_check'] == 'true') {
						$this->limit = !empty($conditions['specific_iteration_id']['iteration_id']) ? $conditions['specific_iteration_id']['iteration_id'] : '';
					}
					else{
						$this->limit           = 50;
					}
				}
				if(!empty($this->conditions['delimiter']['optional_delimiter'])){
					$this->delimiter = $this->conditions['delimiter']['optional_delimiter'] ? $this->conditions['delimiter']['optional_delimiter']: ',';
				}
				elseif(!empty($this->conditions['delimiter']['delimiter'])){
					$this->delimiter = $this->conditions['delimiter']['delimiter'] ? $this->conditions['delimiter']['delimiter'] : ',';
					if($this->delimiter == '{Tab}'){
						$this->delimiter = " ";
					}
					elseif($this->delimiter == '{Space}'){
						$this->delimiter = " ";	
					}
				}

				$this->export_mode = 'normal';
				$this->checkSplit = isset( $_POST['is_check_split'] ) ? sanitize_text_field( $_POST['is_check_split'] ) : 'false';

				$time = date('Y-m-d h:i:s');
$conditions['export_role'] = [
    'is_check' => $this->exportRoleChecked,
    'value'    => $this->roleSelectedField,
];

$conditions['export_user_product'] = [
    'is_check' => $this->exportUserProductChecked,
    'value'    => $this->userProductSelectedField,
];

				$export_conditions = serialize($conditions);
				$export_event_exclusions = serialize(($eventExclusions));
				global $wpdb;


				$file_post_name = sanitize_file_name($_POST['fileName']);
				$post_module = sanitize_text_field($_POST['module']);
				$_POST['optionalType']=isset($_POST['optionalType'])? sanitize_text_field($_POST['optionalType']):'';
				$post_optional = sanitize_text_field($_POST['optionalType']);

				if(empty($post_optional)){
					$check_for_existing_template = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}ultimate_csv_importer_export_template WHERE filename = '$file_post_name' AND module = '$post_module' ");
				}
				else{
					$check_for_existing_template = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}ultimate_csv_importer_export_template WHERE filename = '$file_post_name' AND module = '$post_module' AND optional_type = '$post_optional' ");
				}

				if(empty($check_for_existing_template)){
					$wpdb->insert($wpdb->prefix.'ultimate_csv_importer_export_template',
						array('filename' => $file_post_name,
						'module' => $post_module,
						'optional_type' => sanitize_text_field($_POST['optionalType']),
						'export_type' => sanitize_text_field($_POST['exp_type']),
						'split' => sanitize_text_field($_POST['is_check_split']),
						'split_limit' => intval($_POST['limit']),
						'category_name' => $categorybased,
						'conditions' => $export_conditions,
						'event_exclusions' => $export_event_exclusions,
						'export_mode' => 'normal',
						'createdtime' => $time,
						'offset' => intval($_POST['offset']),
						'actual_start_date' => $_POST['actual_start_date'],
						'actual_end_date' => $_POST['actual_end_date']
						),
						array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
					);
				}
				else{
					$id = $check_for_existing_template[0]->id;

					$wpdb->update( 
						$wpdb->prefix.'ultimate_csv_importer_export_template', 
						array(
							'export_type' => sanitize_text_field($_POST['exp_type']),
							'split' => sanitize_text_field($_POST['is_check_split']),
							'split_limit' => intval($_POST['limit']),
							'category_name' => $categorybased,
							'conditions' => $export_conditions,
							'event_exclusions' => $export_event_exclusions,
							'export_mode' => 'normal',
							'createdtime' => $time,
							'offset' => intval($_POST['offset']),
							'actual_start_date' => $_POST['actual_start_date'],
							'actual_end_date' => $_POST['actual_end_date']
						),
						array( 'id' => $id )
					);
				}
				$this->mode=isset($this->mode)?$this->mode:'';
				if(empty($this->module) && $type == 'post'){
					ExportExtension::$wpquery_export = new WPQueryExport();
					ExportExtension::$wpquery_export->exportwpquery($query_data);
				}
				elseif(empty($this->module) && $type == 'user'){
					ExportExtension::$wpquery_export = new WPQueryExport();
					ExportExtension::$wpquery_export->exportwpquery_user($query_data);	
				}
				elseif(empty($this->module) && $type == 'comment'){
					ExportExtension::$wpquery_export = new WPQueryExport();
					ExportExtension::$wpquery_export->exportwpquery_comment($query_data);		
				}
				else{
					$this->exportData($this->mode,$categorybased);
				}

			}
		}


		public function commentsCount($module) {
			global $wpdb;
			self::generateHeaders($this->module, $this->optionalType);
			$get_comments = "select * from {$wpdb->prefix}comments";
			// Check status
			if(isset($this->conditions['specific_status'])){
				if($this->conditions['specific_status']['is_check'] == 'true') {
					if($this->conditions['specific_status']['status'] == 'Pending')
						$get_comments .= " where comment_approved = '0'";
					elseif($this->conditions['specific_status']['status'] == 'Approved')
						$get_comments .= " where comment_approved = '1'";
					else
						$get_comments .= " where comment_approved in ('0','1')";
				}
			}
			else
				$get_comments .= " where comment_approved in ('0','1')";

			// Check for specific period
			if(isset($this->conditions['specific_period']['is_check']) && $this->conditions['specific_period']['is_check'] == 'true') {
				if($this->conditions['specific_period']['from'] == $this->conditions['specific_period']['to']){
					$get_comments .= " and comment_date >= '" . $this->conditions['specific_period']['from'] . "'";
				}else{
					$get_comments .= " and comment_date >= '" . $this->conditions['specific_period']['from'] . "' and comment_date <= '" . $this->conditions['specific_period']['to'] . "'";
				}
			}
			// Check for specific authors
			if(isset($this->conditions['specific_authors']['is_check']) && $this->conditions['specific_authors']['is_check'] == '1') {
				if(isset($this->conditions['specific_authors']['author'])) {
					$get_comments .= " and comment_author_email = '".$this->conditions['specific_authors']['author']."'"; 
				}
			}

			if($module == 'WooCommerceReviews'){
				$get_comments .= " and comment_type = 'review'";
			}
			$get_comments .= " order by comment_ID";

			$comments = $wpdb->get_results( $get_comments );
			$totalRowCount = count($comments);
			return $totalRowCount;
		}

		public function countJetReviews($module) {
			global $wpdb;
			
			// Initialize response array
			$response = array();
		
			// Check if the module is 'JetReviews' and handle the count logic
			if ($module == 'JetReviews') {
				// Default to counting only approved reviews
				$query = "SELECT COUNT(*) FROM {$wpdb->prefix}jet_reviews";
				
				// Execute the query to get the count
				$count = $wpdb->get_var($query);
		
				// Ensure the count is an integer (default to 0 if not found)
				$response['count'] = ($count !== null) ? intval($count) : 0;
	
				// Return the response as JSON directly
				echo wp_json_encode($response);
				wp_die();  // Ensure no further execution
			}
		}
		
		
		
		public function getOptionalType($module){
			if($module == 'Tags'){
				$optionalType = 'post_tag';
			}
			elseif($module == 'Posts'){			
				$optionalType = 'post';
			}
			elseif($module == 'Pages'){			
				$optionalType = 'page';
			} 
			elseif($module == 'Categories'){
				$optionalType = 'category';
			}
			elseif($module == 'JetBooking'){
				$optionalType = 'JetBooking';
			}
			elseif($module == 'JetReviews'){
				$optionalType = 'JetReviews';
			}
			elseif ($module == 'WooCommerceCustomer')
			{
				$optionalType = 'users';
			}
			elseif($module == 'Users'){			
				$optionalType = 'user';
			}
			elseif($module == 'Comments'){			
				$optionalType = 'comment';
			}
			elseif($module == 'Images'){
				$optionalType = 'images';
			}
			elseif($module == 'CustomerReviews'){
				$optionalType = 'wpcr3_review';
			}
			elseif($module == 'WooCommerce' || $module == 'WooCommerceOrders' || $module == 'WooCommerceCoupons' || $module == 'WooCommerceRefunds' || $module == 'WooCommerceVariations'){
				$optionalType = 'product';
			}
			elseif($module == 'WooCommerce'){
				$optionalType = 'product';
			}
			elseif($module == 'WPeCommerce'){
				$optionalType = 'wpsc-product';
			}
			elseif($module == 'WPeCommerce' ||$module == 'WPeCommerceCoupons'){
				$optionalType = 'wpsc-product';
			}
			$optionalType=isset($optionalType)?$optionalType:'';
			return $optionalType;
		}

		/**
		 * set the delimiter
		 */
		public function setDelimiter($conditions)
		{		
			if (isset($conditions['optional_delimiter']) && $conditions['optional_delimiter'] != '') {
				return $conditions['optional_delimiter'];
			}
			elseif(isset($conditions['delimiter']) && $conditions['delimiter'] != 'Select'){
				if($conditions['delimiter'] == '{Tab}')
					return "\t";
				elseif ($conditions['delimiter'] == '{Space}')
					return " ";
				else
					return $conditions['delimiter'];
			}
			else{
				return ',';
			}
		}

		/**
		 * Export records based on the requested module
		 */
		public function exportData($mod = '',$cat = '', $is_filter = '') {

			switch ($this->module) {
			case 'Posts':
			case 'Pages':
			case 'CustomPosts':  
			case 'WooCommerce':
			case 'WooCommerceVariations':
			case 'WooCommerceOrders':
			case 'WooCommerceCoupons':
			case 'WooCommerceRefunds':
			case 'WPeCommerce':
			case 'WPeCommerceCoupons':

				$result = self::FetchDataByPostTypes($mod,$cat, $is_filter);
				break;

			case 'Images':
				self::generateHeaders($this->module, $this->optionalType);
				$result =  self::FetchImageMetaData($this->module,$this->optionalType, $this->conditions,$this->offset,$this->limit, $is_filter,$this->headers,$this->mode,$this->eventExclusions);
				break;
			case 'JetBooking':
				self::generateHeaders($this->module, $this->optionalType);
				$result = ExportExtension::$jet_book_export->FetchJetBookingData($this->module,$this->optionalType, $this->conditions,$this->offset,$this->limit, $is_filter,$this->headers,$this->mode,$this->eventExclusions);
				break;
			case 'Users':
			case 'WooCommerceCustomer':
				$result = self::FetchUsers($mod, $is_filter);
				break;
			case 'WooCommerceReviews':
			case 'Comments':
				self::generateHeaders($this->module, $this->optionalType);
				$result = self::FetchComments($this->module,$this->optionalType, $is_filter,$this->headers,$this->mode,$this->eventExclusions);
				break;
			case 'JetReviews':
				self::generateHeaders($this->module, $this->optionalType);
				$result = ExportExtension::$jet_reviews_export->FetchJetReviewsData($this->module,$this->optionalType, $this->conditions,$this->offset,$this->limit, $is_filter,$this->headers,$this->mode,$this->eventExclusions);
				break;
			case 'CustomerReviews':
				self::generateHeaders($this->module, $this->optionalType);
				$result = ExportExtension::$review_export->FetchCustomerReviews($this->module,$this->optionalType, $this->conditions,$this->offset,$this->limit, $is_filter,$this->headers,$this->mode,$this->eventExclusions);
				break;
			case 'Categories':
				self::generateHeaders($this->module, $this->optionalType);
				$result = ExportExtension::$post_export->FetchCategories($this->module,$this->optionalType, $is_filter,$this->headers,$this->mode,$this->eventExclusions);
				break;
			case 'Tags':
				self::generateHeaders($this->module, $this->optionalType);
				$result = ExportExtension::$post_export->FetchTags($this->module,$this->optionalType, $is_filter,$this->headers,$this->mode,$this->eventExclusions);
				break;
			case 'Taxonomies':
				self::generateHeaders($this->module, $this->optionalType);
				$result = ExportExtension::$woocom_export->FetchTaxonomies($this->module,$this->optionalType, $is_filter,$this->headers,$this->mode,$this->eventExclusions);
				break;

			}
			
			$result=isset($result)?$result:'';

			return $result;
		}

		/*  Fetch Image meta data */
		public function FetchImageMetaData($module, $optionalType, $conditions, $offset, $limit, $is_filter, $headers, $mode, $eventExclusions) {
			$Imageids = ExportExtension::$post_export->getRecordsBasedOnPostTypes($this->module, $this->optionalType, $this->conditions,$this->offset,$this->limit,'','');
			/** Export based on Image ids */
			if(!empty($Imageids)){
				foreach($Imageids as $id){
					$attachment = get_post($id);
					if (!$attachment) {
						continue; // Skip if the attachment doesn't exist
					}
					// Get attachment metadata
					$metadata = wp_get_attachment_metadata($id);
					//filtered header array based
					if (in_array('media_id', $headers)) $this->data[ $id ][ 'media_id' ] = $id ?? '';
					if (in_array('caption', $headers)) $this->data[ $id ][ 'caption' ] =  wp_get_attachment_caption($id) ?? '';
					if (in_array('alt_text', $headers)) $this->data[ $id ][ 'alt_text' ] = get_post_meta($id, '_wp_attachment_image_alt', true) ?? '';
					if (in_array('description', $headers)) $this->data[ $id ][ 'description' ] = $attachment->post_content ?? '';
					if (in_array('file_name', $headers)) $this->data[ $id ][ 'file_name' ] =  basename(get_attached_file($id)) ?? '';
					if (in_array('title', $headers)) $this->data[ $id ][ 'title' ] = $attachment->post_title ?? '';
					if (in_array('actual_url', $headers)) $this->data[ $id ][ 'actual_url' ] = wp_get_attachment_url($id) ?? '';
				}
				$results = self::finalDataToExport($this->data);
				if($is_filter == 'filter_action'){
					return $results;
				}
		
				if($mode == null)
				self::proceedExport($results);
				else
					return $results;
			}
			return $Imageids;
		}

		/**
		 * Fetch users and their meta information
		 * @param $mode
		 *
		 * @return array
		 */
		

				public function FetchUsers($mode = null, $is_filter = '') {
    global $wpdb;
    self::generateHeaders($this->module, $this->optionalType);

    $limit  = isset($this->limit) ? absint($this->limit) : 0;
    $offset = isset($this->offset) ? absint($this->offset) : 0;

    if ($this->module == 'WooCommerceCustomer') {
        $this->module = 'Users';

        $args = [
            'role'   => 'customer',
            'fields' => 'ID',
            'orderby' => 'ID',
            'order' => 'ASC',
        ];

        if (!empty($this->conditions['specific_period']['is_check']) && $this->conditions['specific_period']['is_check'] == 'true') {
            $from = $this->conditions['specific_period']['from'];
            $to   = $this->conditions['specific_period']['to'];

            if ($from == $to) {
                $args['date_query'] = [['after' => $from, 'inclusive' => true]];
            } else {
                $args['date_query'] = [['after' => $from, 'before' => $to . ' 23:59:59', 'inclusive' => true]];
            }
        }

$count_args = $args;
$count_args['number'] = -1;
$count_args['offset'] = 0;
$user_query_all = new \WP_User_Query($count_args);
$availableUsers_all = $user_query_all->get_results();
$availableUserss = is_array($availableUsers_all) ? $availableUsers_all : [];


    } else {
        $get_available_user_ids = "SELECT DISTINCT u.ID
                                  FROM {$wpdb->prefix}users u
                                  JOIN {$wpdb->prefix}usermeta um ON um.user_id = u.ID";

        if (!empty($this->conditions['specific_period']['is_check']) && $this->conditions['specific_period']['is_check'] == 'true') {
            $from = esc_sql($this->conditions['specific_period']['from']);
            $to   = esc_sql($this->conditions['specific_period']['to']);

            if ($from == $to) {
                $get_available_user_ids .= " WHERE u.user_registered >= '{$from}'";
            } else {
                $get_available_user_ids .= " WHERE u.user_registered >= '{$from}' AND u.user_registered <= '{$to} 23:59:59'";
            }
        }

$availableUsers_all = $wpdb->get_col( $get_available_user_ids . " ORDER BY ID ASC" );
$availableUserss = is_array($availableUsers_all) ? $availableUsers_all : [];


    }

if (!empty($availableUserss) && !empty($this->exportRoleChecked) && !empty($this->roleSelectedField)) {
    $selectedRoles = is_array($this->roleSelectedField)
        ? $this->roleSelectedField
        : array_map('trim', explode(',', $this->roleSelectedField));

    $availableUserss = array_filter($availableUserss, function($userId) use ($selectedRoles) {
        $user = get_userdata($userId);
        if (empty($user) || empty($user->roles)) {
            return false;
        }
        return (bool) array_intersect((array) $user->roles, $selectedRoles);
    });
}

  if (!empty($availableUserss) && !empty($this->exportUserProductChecked) && !empty($this->userProductSelectedField)) {
    $selectedProducts = is_array($this->userProductSelectedField)
        ? $this->userProductSelectedField
        : array_map('trim', explode(',', $this->userProductSelectedField));

    $product_ids = [];

    foreach ($selectedProducts as $product_identifier) {
        $product_id = 0;

        $product_id = wc_get_product_id_by_sku($product_identifier);

        if (!$product_id && is_numeric($product_identifier)) {
            $product_id = absint($product_identifier);
        }

        if (!$product_id) {
            $product_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts}
                     WHERE post_type = 'product'
                       AND post_status = 'publish'
                       AND post_title = %s
                     LIMIT 1",
                    $product_identifier
                )
            );
        }

        if ($product_id) {
            $product_ids[] = $product_id;
        }
    }

    $product_ids = array_unique(array_filter($product_ids));

    if (!empty($product_ids)) {
        $availableUserss = array_filter($availableUserss, function($userId) use ($product_ids) {
            foreach ($product_ids as $pid) {
                if (wc_customer_bought_product('', $userId, $pid)) {
                    return true;
                }
            }
            return false;
        });
    }
}



    if (!empty($availableUserss)) {
		 $availableUserss = array_values($availableUserss);

    $this->totalRowCount = count($availableUserss);

    if ($limit > 0) {
        $availableUserss = array_slice($availableUserss, $offset, $limit);
    }
        $whereCondition = '';

        foreach ($availableUserss as $userId) {
            $whereCondition = $userId;

            $wp_posts_fields_column_value = [
                'ID','user_login','user_pass','user_nicename','user_email','user_url','user_registered','user_activation_key','user_status','display_name'
            ];
            $filtered_headers = array_intersect($this->headers, $wp_posts_fields_column_value);
            $selected_columns = !empty($filtered_headers) ? implode(', ', $filtered_headers) : '*';

            $query_to_fetch_users = "SELECT $selected_columns FROM {$wpdb->prefix}users WHERE ID IN ($whereCondition);";
            
            
            $users = $wpdb->get_results($query_to_fetch_users);

            if (!empty($users)) {
                foreach ($users as $userInfo) {
                    foreach ($userInfo as $userKey => $userVal) {
                        $this->data[$userId][$userKey] = $userVal;
                    }
                }

                $user_email = $users[0]->user_email ?? '';
            }

            if ($this->eventExclusions['is_check'] == 'true') {
                $userMeta = $this->filterUserMeta($userId);
            } else {
                $query_to_fetch_users_meta = $wpdb->prepare(
                    "SELECT user_id, meta_key, meta_value
                     FROM {$wpdb->prefix}users wp
                     JOIN {$wpdb->prefix}usermeta wpm ON wpm.user_id = wp.ID
                     WHERE ID = %d",
                    $userId
                );
                $userMeta = $wpdb->get_results($query_to_fetch_users_meta);
            }

            $meta_count = is_array($userMeta) ? count($userMeta) : 0;

            if (!empty($userMeta)) {
                foreach ($userMeta as $userMetaInfo) {
                    if ($userMetaInfo->meta_key == $wpdb->prefix . 'capabilities') {
                        if (is_plugin_active('members/members.php')) {
                            $data = unserialize($userMetaInfo->meta_value);
                            $roles = array_keys(array_filter($data));
                            $role = implode('|', $roles);
                            $this->data[$userId]['multi_user_role'] = $role;
                        } else {
                            $userRole = $this->getUserRole($userMetaInfo->meta_value);
                            $this->data[$userId]['role'] = $userRole;
                        }
                    } elseif ($userMetaInfo->meta_key == 'description') {
                        $this->data[$userId]['biographical_info'] = $userMetaInfo->meta_value;
                    } elseif ($userMetaInfo->meta_key == 'comment_shortcuts') {
                        $this->data[$userId]['enable_keyboard_shortcuts'] = $userMetaInfo->meta_value;
                    } elseif ($userMetaInfo->meta_key == 'show_admin_bar_front') {
                        $this->data[$userId]['show_toolbar'] = $userMetaInfo->meta_value;
                    } elseif ($userMetaInfo->meta_key == 'rich_editing') {
                        $this->data[$userId]['disable_visual_editor'] = $userMetaInfo->meta_value;
                    } elseif ($userMetaInfo->meta_key == 'locale') {
                        $this->data[$userId]['language'] = $userMetaInfo->meta_value;
                    } else {
                        $this->data[$userId][$userMetaInfo->meta_key] = $userMetaInfo->meta_value;
                    }
                }
                ExportExtension::$post_export->getPostsMetaDataBasedOnRecordId(
                    $userId,
                    $this->module,
                    $this->optionalType,
                    $this->headers,
                    $this->eventExclusions
                );
            }
        }

    } 

    $result = self::finalDataToExport($this->data, $this->module, $this->optionalType);

    if (is_plugin_active('advanced-custom-fields-pro/acf.php')) {
        $result = $this->convert_acfname_to_key($result, $this->module, $this->optionalType, 'pro');
    } elseif ((is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('secure-custom-fields/secure-custom-fields.php')) && is_plugin_active('acf-repeater/acf-repeater.php')) {
        $result = $this->convert_acfname_to_key($result, $this->module, $this->optionalType, 'free');
    }

    if ($is_filter == 'filter_action') {
        return $result;
    }

    if ($mode == null) {
        self::proceedExport($result);
    } else {
        return $result;
    }
}
			

		public function filterUserMeta($userId){
			global $wpdb;
			// Define the headers array (this is dynamic, so it should be your provided $headers array)
			$user_headers = $this->headers;
			$user_headers[] = 'wp_capabilities';

			// Step 1: Dynamically build the WHERE clause for meta_key filtering
			$placeholders = implode(', ', array_fill(0, count($user_headers), '%s'));
			$query = "
				SELECT user_id, meta_key, meta_value 
				FROM {$wpdb->prefix}usermeta 
				WHERE user_id = %d 
				AND meta_key IN ($placeholders)
			";

			// Step 2: Prepare the query with dynamic values
			$query = $wpdb->prepare($query, $userId, ...$user_headers);

			// Step 3: Execute the query to fetch results
			$user_meta = $wpdb->get_results($query);

			// Step 4: Format the results as desired (i.e., array of objects)
			$userMetaFormatted = [];

			foreach ($user_meta as $meta) {
				$userMetaFormatted[] = (object)[
					'user_id' => $meta->user_id,
					'meta_key' => $meta->meta_key,
					'meta_value' => $meta->meta_value
				];
			}
			return $userMetaFormatted;

		}
/**
	 * Fetch all Categories
	 * 
	 * @param string $module         The module for which categories are being fetched.
	 * @param string|null $optionalType Optional type filter for categories.
	 * @param bool $is_filter        Indicates if filters are to be applied.
	 * @param array $headers         Headers for the request.
	 * @param string|null $mode      Mode of operation (optional).
	 * @param array|null $eventExclusions Categories or events to exclude (optional).
	 * 
	 * @return array
	 */
	public function FetchComments($module, $optionalType, $is_filter, $headers, $mode = null, $eventExclusions = null) {
			global $wpdb;
			//self::generateHeaders($this->module, $this->optionalType);
			if($eventExclusions['is_check'] == 'true' ){
				//$headers[] = 'user_id'; //specific inclusion
				$headers[] = 'comment_ID'; //specific inclusion
				// filtered headers
				if($module == 'Comments' || $module == 'WooCommerceReviews'){
					$wp_review_fields_column_value = [
						'comment_ID','comment_post_ID','post_title','comment_author','comment_author_email','comment_author_url','comment_author_IP','comment_date','comment_date_gmt','comment_content','comment_karma','comment_approved','comment_agent','comment_type','comment_parent','user_id'
					];
				}
				$filtered_headers = array_intersect($headers, $wp_review_fields_column_value);
				$selected_columns = implode(', ', $filtered_headers);
				$get_comments = "select $selected_columns from {$wpdb->prefix}comments";
			}else{
				$get_comments = "select * from {$wpdb->prefix}comments";
			}	

			// Check status
			if($this->conditions['specific_status']['is_check'] == 'true') {
				if($this->conditions['specific_status']['status'] == 'Pending')
					$get_comments .= " where comment_approved = '0'";
				elseif($this->conditions['specific_status']['status'] == 'Approved')
					$get_comments .= " where comment_approved = '1'";
				else
					$get_comments .= " where comment_approved in ('0','1')";
			}
			else
				$get_comments .= " where comment_approved in ('0','1')";
			// Check for specific period
			if($this->conditions['specific_period']['is_check'] == 'true') {
				if($this->conditions['specific_period']['from'] == $this->conditions['specific_period']['to']){
					$get_comments .= " and comment_date >= '" . $this->conditions['specific_period']['from'] . "'";
				}else{
					$get_comments .= " and comment_date >= '" . $this->conditions['specific_period']['from'] . "' and comment_date <= '" . $this->conditions['specific_period']['to'] . "'";
				}
			}
			// Check for specific authors
			if($this->conditions['specific_authors']['is_check'] == '1') {
				if(isset($this->conditions['specific_authors']['author'])) {
					$get_comments .= " and comment_author_email = '".$this->conditions['specific_authors']['author']."'"; 
				}
			}
			if($this->module == 'WooCommerceReviews'){
				$get_comments .= " and comment_type = 'review'";
			}


			$comments = $wpdb->get_results( $get_comments );
			if(!empty($this->conditions['specific_period']['is_check']) && $this->conditions['specific_period']['is_check'] == 'true') {
				if($this->conditions['specific_period']['from'] == $this->conditions['specific_period']['to']){
					$limited_comments = array();
					foreach($comments as $comments_value){
						$get_comment_date_time = $wpdb->get_results( "SELECT comment_date FROM {$wpdb->prefix}comments WHERE comment_id=$comments_value->comment_ID" ,ARRAY_A);
						$get_comment_date = date("Y-m-d",strtotime($get_comment_date_time[0]['comment_date'] ));
						if($get_comment_date == $this->conditions['specific_period']['from']){
							$get_comment_date_value[] = $comments_value;
						}		

					}
					$this->totalRowCount = count($get_comment_date_value);
					$limited_comments = $get_comment_date_value;
				}
				else{
					$this->totalRowCount = count($comments);
					$get_comments .= " order by comment_ID asc limit $this->offset, $this->limit";
					$limited_comments = $wpdb->get_results( $get_comments );
				}	
			}
			else{
				$this->totalRowCount = count($comments);
				$get_comments .= " order by comment_ID asc limit $this->offset, $this->limit";
				$limited_comments = $wpdb->get_results( $get_comments );
			}
			if(!empty($limited_comments)) {
				foreach($limited_comments as $commentInfo) {
					$user_id=$commentInfo->user_id ?? '';
					if(!empty($user_id)) {
						$users_login =  $wpdb->get_results("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = '$user_id'");		
						foreach($users_login as $users_key => $users_value){
							foreach($users_value as $u_key => $u_value){
								$users_id=$u_value;
							}
						}
					}
					foreach($commentInfo as $commentKey => $commentVal) {
						$this->data[$commentInfo->comment_ID][$commentKey] = $commentVal;
						$users_id=isset($users_id)?$users_id:'';
						if(isset($users_id) && !empty($users_id)){
							$this->data[$commentInfo->comment_ID]['user_id'] = $users_id;
						}
					}
					$get_comment_rating = get_comment_meta($commentInfo->comment_ID, 'rating', true);
					if(!empty($get_comment_rating)){
						$this->data[$commentInfo->comment_ID]['comment_rating'] = $get_comment_rating;
					}
				}
			}
			$result = self::finalDataToExport($this->data, $this->module ,$this->optionalType);

			if($is_filter == 'filter_action'){
				return $result;
			}

			if($mode == null)
				self::proceedExport($result);
			else
				return $result;
		}

		/**
		 * Generate CSV headers
		 *
		 * @param $module       - Module to be export
		 * @param $optionalType - Exclusions
		 */
		public function generateHeaders ($module, $optionalType) {

			global $wpdb;
			if($module == 'CustomPosts' || $module == 'Tags' || $module == 'Categories' || $module == 'Taxonomies'){

				if (is_plugin_active('events-manager/events-manager.php') && $optionalType == 'event') {
					$optionalType = 'Events';		
				}elseif (is_plugin_active('the-events-calendar/the-events-calendar.php') && $optionalType == 'tribe_events') {
					$optionalType = 'tribe_events';		
				}elseif($optionalType == 'location'){
					$optionalType = 'Event Locations';
				}elseif($optionalType == 'event-recurring'){
					$optionalType = 'Recurring Events';
				}
				elseif($optionalType == 'gfentries'){
					$optionalType = 'GFEntries';
				}
				if(empty($optionalType)){
					$default = ExportExtension::$mapping_instance->get_fields($module);
				}
				else
					$default = ExportExtension::$mapping_instance->get_fields($optionalType);
			}
			else{
				$default = ExportExtension::$mapping_instance->get_fields($module);
			}

			$headers = [];

			foreach ($default as $key => $fields) {	
				foreach($fields as $groupKey => $fieldArray) {
					foreach ( $fieldArray as $fKey => $fVal ) {
						if (is_array($fVal) || is_object($fVal)){
							foreach ( $fVal as $rKey => $rVal ) {
							if (!in_array($rVal['name'], $headers)) {
	if (is_plugin_active('advanced-custom-fields-pro/acf.php') || is_plugin_active('advanced-custom-fields/acf.php')) {
		if (strpos($rVal['name'], 'field_') !== false) {
			$value = $rVal['name'];
			$get_acf_excerpt = $wpdb->get_var("SELECT post_excerpt FROM {$wpdb->prefix}posts WHERE post_name = '$value'");
			if (!empty($get_acf_excerpt)) {
				$headers[] = $get_acf_excerpt;
			} else {
				$headers[] = $rVal['name'];
			}
		} else {
			$headers[] = $rVal['name'];
		}
	} else {
		$headers[] = $rVal['name'];
	}
}

							}
						}
					}

				}
			}
			if($optionalType == 'elementor_library'){
				$headers = [];
				$headers = ['ID','Template title','Template content','Style','Template type','Created time','Created by','Template status','Category'];
			}

			if(isset($this->eventExclusions['is_check']) && $this->eventExclusions['is_check'] == 'true') {
				$headers_with_exclusion = self::applyEventExclusion($headers,$optionalType);
				$this->headers = $headers_with_exclusion;

			}else{
				$this->headers = $headers;			

			}		
		}

		/**
		 * Fetch data by requested Post types
		 * @param $mode
		 * @return array
		 */
		public function FetchDataByPostTypes ($exp_mod,$exp_cat, $is_filter = '') {	

			if($this->optionalType == 'gfentries'){
				$this->getGfEntriesData($this->optionalType,$exp_mod);
			}
			if(empty($this->headers))

				$this->generateHeaders($this->module, $this->optionalType);
			$recordsToBeExport = ExportExtension::$post_export->getRecordsBasedOnPostTypes($this->module, $this->optionalType, $this->conditions,$this->offset,$this->limit,$exp_mod,$exp_cat);	
			if(!empty($recordsToBeExport)) {
				foreach($recordsToBeExport as $postId) {
					$exp_module = $this->module;

						$this->data[$postId] = $this->getPostsDataBasedOnRecordId($postId,$this->module);

					if($exp_module == 'Posts' || $exp_module =='WooCommerce' || $exp_module == 'CustomPosts' || $exp_module == 'Categories' || $exp_module == 'Tags' || $exp_module == 'Taxonomies' || $exp_module == 'Pages'){
						$this->getWPMLData($postId,$this->optionalType,$exp_module);
					}
					if($exp_module == 'Posts' ||  $exp_module == 'CustomPosts' ||$exp_module == 'Pages'||$exp_module == 'WooCommerce' || $exp_module == 'WooCommerceVariations' || $exp_module == 'Images' || $exp_module== 'WooCommerceOrders'){
						if(is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php') || is_plugin_active('polylang-wc/polylang-wc.php')){
							$this->getPolylangData($postId,$this->optionalType,$exp_module);
						}
					}	
					if($exp_module == 'CustomPosts'){
						if(is_plugin_active('geodirectory/geodirectory.php')){
							$this->getGeoPlaceData($postId,$this->optionalType,$exp_module);
						}
					}		
					ExportExtension::$post_export->getPostsMetaDataBasedOnRecordId($postId, $this->module, $this->optionalType,$this->headers,$this->eventExclusions);
					$is_check_taxonomies = $this->cpt_taxonomies(get_taxonomies([], 'objects'));
					if (in_array('post_category', $this->headers) || in_array('post_tag', $this->headers) || $is_check_taxonomies) {
						$this->getTermsAndTaxonomies($postId, $this->module, $this->optionalType);
					}
					if($this->module == 'WooCommerce')
						ExportExtension::$woocom_export->getProductData($postId, $this->module, $this->optionalType);
					if($this->module == 'WooCommerceRefunds')
						ExportExtension::$woocom_export->getWooComCustomerUser($postId, $this->module, $this->optionalType);
					// Log before executing the function
					if ($this->module == 'WooCommerceOrders') {
						ExportExtension::$woocom_export->getWooComOrderData($postId, $this->module, $this->optionalType);
					}
					if($this->module == 'WooCommerceVariations')
						ExportExtension::$woocom_export->getVariationData($postId, $this->module, $this->optionalType);
					if($this->module == 'WooCommerceCoupons')
						ExportExtension::$woocom_export->getCouponsData($postId, $this->module, $this->optionalType);
					if($this->module == 'WPeCommerce')
						ExportExtension::$ecom_export->getEcomData($postId, $this->module, $this->optionalType);
					if($this->module == 'WPeCommerceCoupons')
						ExportExtension::$ecom_export->getEcomCouponData($postId, $this->module, $this->optionalType);
					if($this->optionalType == 'lp_course')
						ExportExtension::$woocom_export->getCourseData($postId);
					if($this->optionalType == 'lp_lesson')
						ExportExtension::$woocom_export->getLessonData($postId);
					if($this->optionalType == 'lp_quiz')
						ExportExtension::$woocom_export->getQuizData($postId);
					if($this->optionalType == 'lp_question')
						ExportExtension::$woocom_export->getQuestionData($postId);
					if($this->optionalType == 'lp_order')
						ExportExtension::$woocom_export->getOrderData($postId);
					if($this->optionalType == 'stm-courses')
						ExportExtension::$woocom_export->getCourseDataMasterLMS($postId);
					if($this->optionalType == 'stm-questions')
						ExportExtension::$woocom_export->getQuestionDataMasterLMS($postId);
					if($this->optionalType == 'stm-orders')
						ExportExtension::$woocom_export->orderDataMasterLMS($postId);
					if($this->optionalType == 'stm-quizzes')
						ExportExtension::$woocom_export->quizzDataMasterLMS($postId);
					if($this->optionalType == 'stm-lessons')
						ExportExtension::$woocom_export->getLessonDataMasterLMS($postId);
					if($this->optionalType == 'course' && is_plugin_active('lifterlms/lifterlms.php'))
						ExportExtension::$woocom_export->getCourseDataLiferLms($postId);
					if($this->optionalType == 'lesson')
						ExportExtension::$woocom_export->getLessonDataLifterLms($postId);
					if($this->optionalType == 'llms_coupon')
						ExportExtension::$woocom_export->getCouponDataLifterLms($postId);
					if($this->optionalType == 'llms_review')
						ExportExtension::$woocom_export->getReviewDataLifterLms($postId);
					if($this->optionalType == 'llms_quiz')
						ExportExtension::$woocom_export->getQuizDataLifterLms($postId);
					if($this->optionalType == 'elementor_library')
						ExportExtension::$woocom_export->elementor_export($postId);

					if($this->optionalType == 'nav_menu_item')
						ExportExtension::$woocom_export->getMenuData($postId);

					if($this->optionalType == 'widgets')
						self::$instance->getWidgetData($postId,$this->headers);	

				}
			}
			
			$exp_module = $this->module; 
			if(is_plugin_active('jet-engine/jet-engine.php')){
				global $wpdb;
				$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");

				foreach($get_slug_name as $key=>$get_slug){
					$value = $get_slug->slug;
					$optional_type = $value;
					if($this->optionalType == $optional_type){
						$table_name='jet_cct_'.$this->optionalType;										

						$jet_values = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}$table_name order by _ID asc limit $this->offset,$this->limit ");
						if(!empty($jet_values)) {
							foreach($jet_values as $jet_value) {
								foreach($jet_value as $field_id => $value) {
									$this->data[$jet_value->_ID][$field_id] = $value;					

								}
							}
						}
						foreach($this->data as $id => $value){
							ExportExtension::$post_export->getPostsMetaDataBasedOnRecordId($id, $this->module, $this->optionalType,$this->headers,$this->eventExclusions);

						}
					}
				}
				$slug = $this->optionalType;
				$getarg = $wpdb->get_results("SELECT args from {$wpdb->prefix}jet_post_types where slug = '$slug' and status = 'content-type'",ARRAY_A);		
				foreach($getarg as $key => $value){				
					$arg_data = $value['args'];				
					break;
				}			
				$arg_data = unserialize($arg_data);
				if(!empty($arg_data) && array_key_exists('has_single',$arg_data) && $arg_data['has_single']){
					$this->data[$id]['cct_single_post_title'] = $arg_data['related_post_type_title'] ?? '';
					$this->data[$id]['cct_single_post_content'] = $arg_data['related_post_type_content'] ?? '';				
				}
			}			

			/** Added post format for 'standard' property */
			if($exp_module == 'Posts' || $exp_module == 'CustomPosts') {
				foreach($this->data as $id => $records) {
					if(!array_key_exists('post_format',$records))
					{
						$records['post_format'] = 'standard';
						$this->data[$id] = $records;
					}
				}
			}
			if($this->optionalType == 'course' && is_plugin_active('lifterlms/lifterlms.php')){
				foreach($this->data as $id => $records){
					if(array_key_exists('_llms_instructors',$records)){
						// $instructor=unserialize($records['_llms_instructors']);
						if(is_array($instructor)){
							$arr_ins=array();
							foreach($instructor as $ins_val){
								$arr_val=array_values($ins_val);
								unset($arr_val[0]);
								unset($arr_val[2]);
								$arr_ins[] = implode(',',$arr_val);

							}
							$records['_llms_instructors'] = implode('|',$arr_ins);
							$this->data[$id] = $records;
							
						}


					}
				}
			}

			if($this->optionalType == 'lp_course'){
				if(isset($this->data[$id]['_lp_requirements']) && !empty($this->data[$id]['_lp_requirements'])){
					$lprequirement = unserialize($this->data[$id]['_lp_requirements']);
					$this->data[$id]['_lp_requirements'] = !empty($lprequirement) ? implode('|',$lprequirement) : "";
				}
				if(isset($this->data[$id]['_lp_target_audiences']) && !empty($this->data[$id]['_lp_target_audiences'])){
					$lptarget = unserialize($this->data[$id]['_lp_target_audiences']);
					$this->data[$id]['_lp_target_audiences'] = !empty($lptarget) ? implode('|',$lptarget) : "";
				}
				if(isset($this->data[$id]['_lp_key_features']) && !empty($this->data[$id]['_lp_key_features'])){
					$lpfeature = unserialize($this->data[$id]['_lp_key_features']);
					$this->data[$id]['_lp_key_features'] = !empty($lpfeature) ? implode('|',$lpfeature) : "";
				}
			}		
			/** End post format */
			$result = self::finalDataToExport($this->data, $this->module, $this->optionalType);


		   
			if(is_plugin_active('advanced-custom-fields-pro/acf.php')){
				$result = $this->convert_acfname_to_key($result,$this->module,$this->optionalType,'pro');
			}
			elseif((is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('secure-custom-fields/secure-custom-fields.php')) && is_plugin_active('acf-repeater/acf-repeater.php')){
				$result = $this->convert_acfname_to_key($result,$this->module,$this->optionalType,'free');
			}
			if($is_filter == 'filter_action'){
				return $result;
			}
			if(empty($mode))		
				self::proceedExport( $result );
			
			else

				return $result;
		}	

		public function cpt_taxonomies($taxonomies){
			foreach ($taxonomies as $taxonomy) {
				if (!in_array('post', $taxonomy->object_type) && !in_array('page', $taxonomy->object_type)) {
					$cpt_taxonomies[$taxonomy->name] = $taxonomy->label;
				}
			}
			$matched_key = array_intersect(array_keys($cpt_taxonomies) , $this->headers);
			if(!empty($matched_key)){
				return true;
			}
			return false;
		}

		public function convert_acfname_to_key($data,$module,$optional_type,$plugin) {
			global $wpdb;
			$newformat = array();
			if($plugin == 'free'){
				$acfobj = new ACFExtension();
				if($module !='CustomPosts'){
					$acf_fields_ext = $acfobj->processExtension($module);
				}
				else{
					$acf_fields_ext = $acfobj->processExtension($optional_type);
				}

			}
			else {
				$acfobj = new ACFProExtension();
				if($module !='CustomPosts'){
					$acf_fields_ext = $acfobj->processExtension($module);	
				}
				else{
					$acf_fields_ext = $acfobj->processExtension($optional_type);
				}
			}

			foreach($acf_fields_ext as $key => $field_data){
				if(isset($field_data) && is_array($field_data)){
					foreach($field_data as $index => $value){
						if(isset($acf_format) && $key != 'acf_fields' || $key != 'acf_pro_fields'){
							$acf_format[$value['id']] = $value['name'];
						}
					}
				}
			}	
			//Change data array keys
			foreach($data as $postid => $exportdata){
				foreach($this->headers as $key => $value){
					if(isset($acf_format) && is_array($acf_format) && array_key_exists($value,$acf_format) && array_key_exists($value,$exportdata)){				
						$data[$postid][$acf_format[$value]] = $exportdata[$value];									
						unset($data[$postid][$value]);
					}	
				}
			}
			//Change header array keys
			if(isset($acf_format)){
				foreach($acf_format as $name => $key){
					if(in_array($name,$this->headers)){
						$index = array_search($name,$this->headers);
						$this->headers[] = $key;
						unset($this->headers[$index]);
					}
				}
			}

			return $data;			
		}

		public function getWidgetData($postId, $headers){
			global $wpdb;
			$get_sidebar_widgets = get_option('sidebars_widgets');
			$total_footer_arr = [];

			foreach($get_sidebar_widgets as $footer_key => $footer_arr){
				if($footer_key != 'wp_inactive_widgets' || $footer_key != 'array_version'){
					if( strpos($footer_key, 'sidebar') !== false ){
						$get_footer = explode('-', $footer_key);
						$footer_number = $get_footer[1];

						foreach($footer_arr as $footer_values){
							$total_footer_arr[$footer_values] = $footer_number;
						}
					}
				}
			}

			foreach ($headers as $key => $value){
				$get_widget_value[$value] = $wpdb->get_row("SELECT option_value FROM {$wpdb->prefix}options where option_name = '{$value}'", ARRAY_A);

				$header_key = explode('widget_', $value);

				if ($value == 'widget_recent-posts'){
					$recent_posts = unserialize($get_widget_value[$value]['option_value']); 
					$recent_post = '';
					foreach($recent_posts as $dk => $dv){
						if($dk != '_multiwidget'){
							$post_key = $header_key[1].'-'.$dk;
							$recent_post .= $dv['title'].','.$dv['number'].','.$dv['show_date'].'->'.$total_footer_arr[$post_key].'|';
						}
					}
					$recent_post = rtrim($recent_post , '|');
				}
				elseif ($value == 'widget_pages'){
					$recent_pages = unserialize($get_widget_value[$value]['option_value']); 
					$recent_page = '';
					foreach($recent_pages as $dk => $dv){
						if(isset($dv['exclude'])){
							$exclude_value = str_replace(',', '/', $dv['exclude']);
						}

						if($dk != '_multiwidget'){
							$page_key = $header_key[1].'-'.$dk;
							$recent_page .= $dv['title'].','.$dv['sortby'].','.$exclude_value.'->'.$total_footer_arr[$page_key].'|';
						}
					}
					$recent_page = rtrim($recent_page , '|');
				}
				elseif ($value == 'widget_recent-comments'){
					$recent_comments = unserialize($get_widget_value[$value]['option_value']); 
					$recent_comment = '';
					foreach($recent_comments as $dk => $dv){
						if($dk != '_multiwidget'){
							$comment_key = $header_key[1].'-'.$dk;
							$recent_comment .= $dv['title'].','.$dv['number'].'->'.$total_footer_arr[$comment_key].'|';
						}
					}
					$recent_comment = rtrim($recent_comment , '|');
				}
				elseif ($value == 'widget_archives'){
					$recent_archives = unserialize($get_widget_value[$value]['option_value']); 
					$recent_archive = '';
					foreach($recent_archives as $dk => $dv){
						if($dk != '_multiwidget'){
							$archive_key = $header_key[1].'-'.$dk;
							$recent_archive .= $dv['title'].','.$dv['count'].','.$dv['dropdown'].'->'.$total_footer_arr[$archive_key].'|';
						}
					}
					$recent_archive = rtrim($recent_archive , '|');
				}
				elseif ($value == 'widget_categories'){
					$recent_categories = unserialize($get_widget_value[$value]['option_value']); 
					$recent_category = '';
					foreach($recent_categories as $dk => $dv){
						if($dk != '_multiwidget'){
							$cat_key = $header_key[1].'-'.$dk;
							$recent_category .= $dv['title'].','.$dv['count'].','.$dv['hierarchical'].','.$dv['dropdown'].'->'.$total_footer_arr[$cat_key].'|';
						}
					}
					$recent_category = rtrim($recent_category , '|');
				}
			}

			$this->data[$postId]['widget_recent-posts'] = $recent_post;
			$this->data[$postId]['widget_pages'] = $recent_page;
			$this->data[$postId]['widget_recent-comments'] = $recent_comment;
			$this->data[$postId]['widget_archives'] = $recent_archive;
			$this->data[$postId]['widget_categories'] = $recent_category;
		}

		/**
		 * Function used to fetch the Terms & Taxonomies for the specific posts
		 *
		 * @param $id
		 * @param $type
		 * @param $optionalType
		 */
		public function getTermsAndTaxonomies ($id, $type, $optionalType) {
			$TermsData = array();

    // 🔹 Detect the post language directly from WPML
    $post_lang = apply_filters('wpml_element_language_code', null, array(
        'element_id'   => $id,
        'element_type' => 'post_' . $optionalType, 
    ));

    $current_lang = apply_filters('wpml_current_language', null);

    // 🔹 Switch to the post's language before fetching terms
    if ($post_lang && $post_lang !== $current_lang) {
        do_action('wpml_switch_language', $post_lang);
    }
			if($type == 'WooCommerce' || ($type == 'CustomPosts' && $type == 'WooCommerce')) {

				$type = 'product';
				$postTags = '';
				$taxonomies = get_object_taxonomies($type);
				$get_tags = get_the_terms( $id, 'product_tag' );
				if($get_tags){
					foreach($get_tags as $tags){
						$postTags .= $tags->name . ',';
					}
				}
				$postTags = substr($postTags, 0, -1);
				$this->data[$id]['product_tag'] = $postTags;
				foreach ($taxonomies as $taxonomy) {
					$postCategory = '';
					if($taxonomy == 'product_cat' || $taxonomy == 'product_category'){

						$get_categories = wp_get_object_terms( $id, $taxonomy, array( 'orderby' => 'term_order' ) );
						if($get_categories){
							$postCategory = $this->hierarchy_based_term_name($get_categories, $taxonomy) ;

						}
						$postCategory = substr($postCategory, 0 , -1);
						$this->data[$id]['product_category'] = $postCategory;
					}else{
						$get_categories = get_the_terms( $id, $taxonomy );
						if($get_categories){
							$postCategory = $this->hierarchy_based_term_name($get_categories, $taxonomy) ;

						}
						$postCategory = substr($postCategory, 0 , -1);
						$this->data[$id][$taxonomy] = $postCategory;
					}
				}
				if($type == 'WooCommerce' && $type != 'CustomPosts') {
					$product = wc_get_product	($id);
					$pro_type = $product->get_type();
					switch ($pro_type) {
					case 'simple':
						$product_type = 1;
						break;
					case 'grouped':
						$product_type = 2;
						break;
					case 'external':
						$product_type = 3;
						break;
					case 'variable':
						$product_type = 4;
						break;
					case 'subscription':
						$product_type = 5;  
						break;
					case 'variable-subscription':
						$product_type = 6;
						break;
					case 'bundle':
						$product_type = 7;
						break;
					default:
						$product_type = 1;
						break;
					}
					$this->data[$id]['product_type'] = $product_type;
				}
				$shipping = get_the_terms( $id, 'product_shipping_class' );
				if(!(is_wp_error($shipping))){
					if($shipping){
						$taxo_shipping = $shipping[0]->name;	
						$this->data[$id][ 'product_shipping_class' ] = $taxo_shipping;
					}
				}

			} else if($type == 'WPeCommerce') {
				$type = 'wpsc-product';
				$postTags = $postCategory = '';
				$taxonomies = get_object_taxonomies($type);
				$get_tags = get_the_terms( $id, 'product_tag' );
				if($get_tags){
					foreach($get_tags as $tags){
						$postTags .= $tags->name.',';
					}
				}
				$postTags = substr($postTags,0,-1);
				$this->data[$id]['product_tag'] = $postTags;
				foreach ($taxonomies as $taxonomy) {
					$postCategory = '';
					if($taxonomy == 'wpsc_product_category'){
						$get_categories = wp_get_post_terms( $id, $taxonomy );
						if($get_categories){
							$postCategory = $this->hierarchy_based_term_name($get_categories, $taxonomy);

						}
						$postCategory = substr($postCategory, 0 , -1);
						$this->data[$id]['product_category'] = $postCategory;
					}else{
						$get_categories = wp_get_post_terms( $id, $taxonomy );
						if($get_categories){
							$postCategory = $this->hierarchy_based_term_name($get_categories, $taxonomy);

						}
						$postCategory = substr($postCategory, 0 , -1);
						$this->data[$id]['product_category'] = $postCategory;
					}
				}
			} else {

				global $wpdb;
				$postTags = $postCategory = '';
				$taxo1 = [];
				$taxonomyId = $wpdb->get_col($wpdb->prepare("select term_taxonomy_id from {$wpdb->prefix}term_relationships where object_id = %d", $id));
				$termTaxonomyIds = array();
				foreach ($taxonomyId as $taxonomyIds) {
					$termTaxonomyId = $wpdb->get_results($wpdb->prepare("select term_id from {$wpdb->prefix}term_taxonomy where term_taxonomy_id = %d", $taxonomyIds));

					foreach ($termTaxonomyId as $term) {
						$termTaxonomyIds[] = $term->term_id;
					}
				}     
				foreach($termTaxonomyIds as $taxonomy) {
					$taxo[] = get_term($taxonomy);
				}

				if(!empty($taxo)){				
					foreach($taxo as $key=>$taxo_val){
						if(!empty($taxo_val) && !is_wp_error($taxo_val) && $taxo_val->taxonomy == 'category'){
							$taxo1[]=$taxo_val;						
						}
					}
				}

				foreach($termTaxonomyIds as $taxonomy) {
					$taxonomytypeid =$wpdb->get_results("SELECT * FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id='$taxonomy' ");
					if(!empty($taxonomytypeid)){
						if($taxonomytypeid[0]->taxonomy == 'course_category') {
							$taxonomyTypeId = $wpdb->get_col($wpdb->prepare("select term_id from {$wpdb->prefix}term_taxonomy where term_taxonomy_id = %d", $taxonomytypeid[0]->term_taxonomy_id));
							$taxonomy_Type_Id = $taxonomyTypeId[0];
							$taxo0[] = get_term($taxonomy_Type_Id);
						}
						if($taxonomytypeid[0]->taxonomy =='course_tag') {
							$taxonomyTypeId1 = $wpdb->get_col($wpdb->prepare("select term_id from {$wpdb->prefix}term_taxonomy where term_taxonomy_id = %d", $taxonomytypeid[0]->term_taxonomy_id));
							$taxonomy_Type_Id1 = $taxonomyTypeId1[0];
							$taxo2[] = get_term($taxonomy_Type_Id1);
						}
					}
				}
				if(!empty($termTaxonomyIds)) {
					foreach($termTaxonomyIds as $taxonomy) {
						$taxonomyType = $wpdb->get_col($wpdb->prepare("select taxonomy from {$wpdb->prefix}term_taxonomy where term_id = %d", $taxonomy));
						if(!empty($taxonomyType)) {
							foreach($taxonomyType as $taxanomy_name) {
								if($taxanomy_name == 'category'){
									$termName = 'post_category';
								}else{
									$termName = $taxanomy_name;
								}
								if(in_array($termName, $this->headers)) {
									if($termName != 'post_tag' && $termName !='post_category') {	
										
										$taxonomyData = $wpdb->get_col($wpdb->prepare("select name from {$wpdb->prefix}terms where term_id = %d",$taxonomy));
										if(!empty($taxonomyData)) {

											if(isset($TermsData[$termName])){
												$this->data[$id][$termName] = $TermsData[$termName] . ',' . $taxonomyData[0];
											}else{
												$this->data[$id][$termName]=isset($this->data[$id][$termName])?$this->data[$id][$termName]:'';
												$get_exist_data = $this->data[$id][$termName];
											}
											if( $get_exist_data == '' ){
												$this->data[$id][$termName] = $taxonomyData[0];
											}else {
												if($taxanomy_name =='course_category') {
													$postterm1 = '';
													foreach($taxo0 as $taxo_key => $taxo_value){
														$postterm1 .= $taxo_value->name.',';
													}												
													$this->data[$id][$termName] =rtrim($postterm1,',');
												}
												elseif($taxanomy_name =='course_tag') {
													$postterm2 = '';
													foreach($taxo2 as $taxo_key1 => $taxo_value1){
														$postterm2 .= $taxo_value1->name.',';
													}												
													$this->data[$id][$termName] = rtrim($postterm2,',');
												}
												else{
													$postterm = substr($this->hierarchy_based_term_name($taxo, $taxanomy_name), 0 , -1);
													$this->data[$id][$termName] = $postterm;
												}											
											}

										}
									} else {
										if(!isset($TermsData['post_tag'])) {
											if($termName == 'post_tag'){
												$postTags = '';
												$get_tags = wp_get_post_tags($id, array('fields' => 'names'));
												foreach ($get_tags as $tags) {
													$postTags .= $tags . ',';
												}
												$postTags = substr($postTags, 0, -1);
												//if( isset($this->data[$id][$termName]) && $this->data[$id][$termName] == '' ) {
												$this->data[$id][$termName] = $postTags;
												//}
											}
											if($termName == 'post_category'){
												$postCategory = '';
												$get_categories = wp_get_post_categories($id, array('fields' => 'names'));											
												$postterm1= substr($this->hierarchy_based_term_name($taxo1, $taxanomy_name), 0 , -1);
												$this->data[$id][$termName] = $postterm1;

											}

										}
									}								

								}
								else{
									$this->data[$id][$termName] = '';
								}
							}
						}					
					}
				}
			}
			 if ($post_lang && $post_lang !== $current_lang) {
        do_action('wpml_switch_language', $current_lang);
    }
		}

		/**
		 * Get user role based on the capability
		 * @param null $capability  - User capability
		 * @return int|string       - Role of the user
		 */
		public function getUserRole ($capability = null, $type = null) {

			if($capability != null) {
				$getRole = unserialize($capability);
				foreach($getRole as $roleName => $roleStatus) {
					$role = $roleName;
				}
				return $role;
			} else {
				return 'subscriber';
			}
		}
		public function array_to_xml( $data, &$xml_data ) {
			foreach( $data as $key => $value ) {
				if( is_numeric($key) ){
					$key = 'item'; 
				}
				if (strpos($key, '::') !== false) {
					$key = str_replace('::', '_COLON_', $key);
					$key = str_replace(' ', '_', $key);
				}
				if( is_array($value) ) {
					$subnode = $xml_data->addChild($key);
					$this->array_to_xml($value, $subnode);
				} else {
					$xml_data->addChild("$key",htmlspecialchars("$value"));
				}
			}
		}

		public  function getPostTypes(){
		$custom_array = array('post', 'page', 'wpsc-product', 'product_variation', 'shop_order', 'shop_coupon', 'shop_order_refund','mp_product_variation');
		$other_posttypes = array('attachment','revision','wpsc-product-file','mp_order','shop_webhook','custom_css','customize_changeset','oembed_cache','user_request','_pods_template','wpmem_product','wp-types-group','wp-types-user-group','wp-types-term-group','gal_display_source','display_type','displayed_gallery','wpsc_log','lightbox_library','scheduled-action','cfs','_pods_pod','_pods_field','acf-field','acf-field-group','wp_block','ngg_album','ngg_gallery','nf_sub','wpcf7_contact_form','iv_payment','llms_question','llms_membership','llms_engagement','llms_order','llms_transaction','llms_achievement','llms_my_achievement','llms_my_certificate','llms_email','llms_voucher','llms_access_plan','llms_form','section','llms_certificate');
		$importas = array(
			'Posts' => 'Posts',
			'Pages' => 'Pages',
			'Users' =>'Users',
			'Comments' => 'Comments',

		);
		$all_post_types = get_post_types();
		array_push($all_post_types, 'widgets');
		// To avoid toolset repeater group fields from post types in dropdown
		global $wpdb;
		$fields = $wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key = '_wp_types_group_fields' ");
		foreach($fields as $value){
			$repeat_values = $value->meta_value;
			$types_fields = explode( ',', $repeat_values);

			foreach($types_fields as $types_value){
				$explode = explode('_',$types_value);
				if (count($explode)>1) {
					if (in_array('repeatable',$explode)) {
						$name = $wpdb->get_results("SELECT post_name FROM ".$wpdb->prefix."posts WHERE id ='{$explode[3]}'");	
						$type_repeat_value =  $name[0]->post_name;

						if(in_array($type_repeat_value , $all_post_types)){
							unset($all_post_types[$type_repeat_value]);
						}
					}else{

					}
				}else{

				}
			}	
		}

		foreach($other_posttypes as $ptkey => $ptvalue) {
			if (in_array($ptvalue, $all_post_types)) {
				unset($all_post_types[$ptvalue]);
			}
		}
		foreach($all_post_types as $key => $value) {
			if(!in_array($value, $custom_array)) {
				if(is_plugin_active('events-manager/events-manager.php') && $value == 'event') {
					$importas['Events'] = $value;
				} elseif(is_plugin_active('events-manager/events-manager.php') && $value == 'event-recurring') {
					$importas['Recurring Events'] = $value;
				} elseif(is_plugin_active('events-manager/events-manager.php') && $value == 'location') {
					$importas['Event Locations'] = $value;
				} else {
					$importas[$value] = $value;
				}
				$custompost[$value] = $value;
			}
		}
		//Ticket import
		if(is_plugin_active('events-manager/events-manager.php')){
			$importas['Tickets'] = 'ticket';
		}
		if(is_plugin_active('wp-customer-reviews/wp-customer-reviews-3.php') || is_plugin_active('wp-customer-reviews/wp-customer-reviews.php') ){
			$importas['Customer Reviews'] = 'CustomerReviews';
			if(isset($importas['wpcr3_review'])) {
				unset($importas['wpcr3_review']);
			}
		}

     // Add JetReviews if the JetReviews plugin is active
         if(is_plugin_active('jet-reviews/jet-reviews.php')) {
	    $importas['JetReviews'] = 'jetreviews';
        }
		if(is_plugin_active('woocommerce/woocommerce.php')){
			$importas['WooCommerce Product'] ='WooCommerce';
		//	$importas['WooCommerce Product Variations'] ='WooCommerceVariations';
			$importas['WooCommerce Orders'] = 'WooCommerceOrders';
			$importas['WooCommerce Customer'] = 'WooCommerceCustomer';
			$importas['WooCommerce Reviews'] ='WooCommerceReviews';
			$importas['WooCommerce Coupons'] = 'WooCommerceCoupons';
			$importas['WooCommerce Refunds'] = 'WooCommerceRefunds';
			unset($importas['product']);
		}
		if(is_plugin_active('wp-e-commerce/wp-shopping-cart.php')){
			$importas['WPeCommerce Products'] ='WPeCommerce';
			$importas['WPeCommerce Coupons'] = 'WPeCommerceCoupons';
		}
		if(is_plugin_active('gravityforms/gravityforms.php')){
			$importas['GFEntries'] ='GFEntries';
			
		}
		if(is_plugin_active('jet-engine/jet-engine.php')){
			$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
		
			if(!empty($get_slug_name)){
				foreach($get_slug_name as $key => $get_slug){
					$value = $get_slug->slug;
					$importas[$value] = $value;
				}
			}
		}
		if(is_plugin_active('jet-booking/jet-booking.php')){
			$importas['JetBooking'] ='JetBooking';
		}
		return $importas;
			
			
		}

		public function getTaxonomies(){
			$i = 0;
			foreach (get_taxonomies() as $key => $value) {
					$response['taxonomies'][$i] = $value;
					$i++;
			}
			return $response;
			
		}

		/**
		 * Export Data
		 * @param $data
		 */
		public function proceedExport ($data) {	

			$upload_dir = WP_CONTENT_DIR . '/uploads/smack_uci_uploads/exports/';
			if(!is_dir($upload_dir)) {
				wp_mkdir_p($upload_dir);
			}
			$base_dir = wp_upload_dir();
			if(is_multisite()){
				$upload_url = network_home_url().'/wp-content/uploads/smack_uci_uploads/exports/';
			}
			else{
				$upload_url = trailingslashit(content_url( '/uploads/smack_uci_uploads/exports', 'https' ));
			}

			chmod($upload_dir, 0777);
			$index_file = $upload_dir . 'index.php';
			if (!file_exists($index_file)) {
				$index_content = '<?php' . PHP_EOL . '?>';
				file_put_contents($index_file, $index_content);
				chmod($index_file, 0644);
			}
			$export_type =  $this->exportType;
			// if($export_type == 'xls'){
			// 	$export_type = 'csv';
			// }

			if($this->checkSplit == 'true'){
				$i = 1;
				while ( $i != 0) {
					$file = $upload_dir . $this->fileName .'_'.$i.'.' . $export_type;
					if(file_exists($file)){
						$allfiles[$i] = $file;
						$i++;
			}
			else
				break;
			}
			$fileURL = $upload_url . $this->fileName.'_'.$i.'.' .$export_type;
			}
			else{
				$file = $upload_dir . $this->fileName .'.' . $export_type;
				$fileURL = $upload_url . $this->fileName.'.' .$export_type;
			}
			if ($this->offset == 0) {
				if(file_exists($file))
					unlink($file);
			}

			$checkRun = "no";
			if($this->checkSplit == 'true' && ($this->totalRowCount - $this->offset) > 0){
				$checkRun = 'yes';
			}
			if($this->checkSplit != 'true'){
				$checkRun = 'yes';
			}

			if($checkRun == 'yes'){
				$this->isPreview = $_POST['isPreview'];
				if($export_type == 'xml'){

					$xml_data = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
					
					if ($this->isPreview === 'true' ) {		
						$limitedData = array_slice($data, 0, 10);
						$this->array_to_xml($limitedData, $xml_data);	
						$dom = new \DOMDocument('1.0', 'UTF-8');
						$dom->preserveWhiteSpace = false;
						$dom->formatOutput = true;
						$dom->loadXML($xml_data->asXML());
						echo $dom->saveXML();
						wp_die();
					}
					else{
						if(file_exists($file)){
							$xml_data = simplexml_load_file($file);
							$this->array_to_xml($data,$xml_data);
						}
						else{
							$this->array_to_xml($data,$xml_data);
						}
						$result = $xml_data->asXML($file);
					}
					
	
				}
				elseif($this->exportType == 'tsv'){
					$files = fopen($file, "w");
					$headers = array_keys(reset($data)); // Get the keys from the first post
					fputcsv($files, $headers, "\t"); 
					foreach ($data as $row) {
						fputcsv($files, $row, "\t"); // Use tab as delimiter
					}
					if ($this->isPreview === 'true' ) {
						$privewJson = array_slice($data, 0, 10);
						$jsonData = json_encode($privewJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
						$data = json_decode($jsonData, true);
						$headers = array_keys($data[0]);
     					$first10 = array_slice($data, 0, 10);
						$rows = array_map(function($item) use ($headers) {
							return array_map(fn($key) => $item[$key], $headers);
						}, $first10);
						$result = array_merge([$headers], $rows);
						header('Content-Type: application/json');
						echo json_encode($result, JSON_PRETTY_PRINT);
						wp_die();
					}

					
				}
				else{
					if ($this->exportType == 'json')

					{
						$csvData = json_encode($data);
	
						if ($this->isPreview === 'true' ) {							
							$privewJson = array_slice($data, 0, 10);
							header('Content-Type: application/json; charset=utf-8');
							echo json_encode($privewJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
							wp_die();
					}
				}	

				else {
					$csvData = $this->unParse($data, $this->headers);

					// Check if migration is true
					if ($this->isPreview === 'true' ) {
						$privewJson = array_slice($data, 0, 10);
						$jsonData = json_encode($privewJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
						$data = json_decode($jsonData, true);
						if (!is_array($data) || empty($data) || !is_array($data[0])) {
   							 // Return empty result instead of fatal error
							$result = [];
						} else {
						$headers = array_keys($data[0]);
						$first10 = array_slice($data, 0, 10);						
						$rows = array_map(function($item) use ($headers) {
							return array_map(fn($key) => $item[$key], $headers);
						}, $first10);
						$result = array_merge([$headers], $rows);	
					}					
						header('Content-Type: application/json');
						echo json_encode($result, JSON_PRETTY_PRINT);
						wp_die();
					}
					}

					try {
						file_put_contents( $file, $csvData, FILE_APPEND | LOCK_EX );
					} catch (\Exception $e) {
					}
				}
			}

			$this->offset = $this->offset + $this->limit;

			$filePath = $upload_dir . $this->fileName . '.' . $export_type;
			$filename = $fileURL;
			if(($this->offset) > ($this->totalRowCount) && $this->checkSplit == 'true'){
				$allfiles[$i] = $file;
				$zipname = $upload_dir . $this->fileName .'.' . 'zip';
				$zip = new \ZipArchive;
				$zip->open($zipname, \ZipArchive::CREATE);
				foreach ($allfiles as $allfile) {
					$newname = str_replace($upload_dir, '', $allfile);
					$zip->addFile($allfile, $newname);
			}
			$zip->close();
			$fileURL = $upload_url . $this->fileName.'.'.'zip';
			foreach ($allfiles as $removefile) {
				unlink($removefile);
			}
			$filename = $upload_url . $this->fileName.'.'.'zip';
			}

			$file = $upload_dir . $this->fileName . '.' . $export_type;
					$allfiles[] = $file;
			
					$this->isMigration = $_POST['isMigrate'];
					// Check if migration is true
					if ($this->isMigration === 'true' && (($this->offset) > ($this->totalRowCount))) {
						
						// Create JSON file
						$module = $this->module;
						$optionalType = $this->optionalType;
						$headers = self::generateHeaders($this->module, $this->optionalType);
						if ($module == 'CustomPosts' || $module == 'Taxonomies' || $module == 'Categories' || $module == 'Tags')
						{
							if(is_plugin_active('events-manager/events-manager.php') &&$optionalType == 'event'){
								$optionalType = 'Events';
							}
							elseif($optionalType == 'location'){
								$optionalType = 'Event Locations';
							}elseif($optionalType == 'event-recurring'){
								$optionalType = 'Recurring Events';
							}
							elseif($optionalType == 'gfentries'){
								$optionalType = 'GFEntries';
							}
							if(empty($optionalType)){
								$default = ExportExtension::$mapping_instance->get_fields($module);
							}
							else
							$default = ExportExtension::$mapping_instance->get_fields($optionalType); // Call the super class function
							$this->module = $optionalType;
						}
						else
						{
							if(is_plugin_active('woocommerce/woocommerce.php')){
					
							$importas = [
								'WooCommerce' => 'WooCommerce Product' ,
							   //'WooCommerce Product Variations' , 'WooCommerceVariations',
								'WooCommerceOrders' => 'WooCommerce Orders' ,
								'WooCommerceCustomer' => 'WooCommerce Customer' , 
								'WooCommerceReviews' => 'WooCommerce Reviews' ,
								'WooCommerceCoupons' => 'WooCommerce Coupons' ,
								'WooCommerceRefunds' => 'WooCommerce Refunds' ,
						   ];
							
						   if (isset($importas[$this->module])) {		
								$module = $importas[$this->module];
						   }
						}
				
						   $default = ExportExtension::$mapping_instance->get_fields($module);
						}
					
						function transformFieldsArray($fieldsArray) {
							$csv_fields = [];
							$fields = [];
						
							foreach ($fieldsArray as $fieldGroup) {
								$groupKey = key($fieldGroup);
								$groupFields = [];
						
								// Process each group
								foreach ($fieldGroup as $subGroupKey => $fieldList) {
									if (!is_array($fieldList)) continue;
						
									$subFields = [];
						
									foreach ($fieldList as $field) {
										// Support for inner group structures (with 'id' and 'backend_id' keys)
										$label = isset($field['label']) ? $field['label'] : '';
										$name = isset($field['name']) ? $field['name'] : '';
										$subFields[] = ['label' => $label, 'name' => $name];
						
										if (!empty($name)) {
											$csv_fields[] = $name;
										}
									}
						
									// Append subgroup
									if (!empty($subFields)) {
										$fields[] = [$subGroupKey => $subFields];
									}
								}
							}
						
							return [
								'csv_fields' => array_values(array_unique($csv_fields)),
								'fields' => $fields
							];
						}
						
						$fieldsArray = $default['fields'];
						$headers =  transformFieldsArray($fieldsArray);

						if($this->exportType == 'json'){
							$jsonFile = $upload_dir . $this->fileName .'config'. '.json';
						}
						else{
						    $jsonFile = $upload_dir . $this->fileName . '.json';
						}
						
						$postTypes = $this->getPostTypes();
						$import_record_post = array_keys($postTypes);
	
						if(is_plugin_active('woocommerce/woocommerce.php')){
							$importas = [
								'WooCommerce' => 'WooCommerce Product' ,
							   //'WooCommerce Product Variations' , 'WooCommerceVariations',
								'WooCommerceOrders' => 'WooCommerce Orders' ,
								'WooCommerceCustomer' => 'WooCommerce Customer' , 
								'WooCommerceReviews' => 'WooCommerce Reviews' ,
								'WooCommerceCoupons' => 'WooCommerce Coupons' ,
								'WooCommerceRefunds' => 'WooCommerce Refunds' ,
						   ];
							
						   if (isset($importas[$this->module])) {		
								$this->module = $importas[$this->module];
						   }
							
					}
		
						$taxonomies = $this->getTaxonomies();
						$jsonData = [
							'file_name' => $this->fileName,
							'total_rows' => $this->totalRowCount,
							'selectedtype' => $this->module,
							'optionalType' => $optionalType,
							'headers' => $headers,
							'export_time' => date('Y-m-d H:i:s'),
							'status' => 'completed',
							'posttype' => $import_record_post,
							'taxonomy' => $taxonomies['taxonomies'],
							'currentuser' => 'administrator',
							'get_key' => false,
							'show_template' => false,
							'file_iteration' => 5,
							'MediaType' => 'Local',
							'update_fields' => ['ID', 'post_title', 'post_name'],
							'use_ExistingImage' => true,
							'media_handle_option' => true,
							'postContent_image_option' => false,
							'highspeed' => false,
							'mappingFilterCheck' => false
						];
						
						file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT));
				
						// Add JSON file to the list of files to zip
						$allfiles[] = $jsonFile;
						// Create ZIP file (smbundle_ prefix)
						$zipname = $upload_dir . 'smbundle_' . $this->fileName . '.zip';
						$zip = new \ZipArchive;
				
						if ($zip->open($zipname, \ZipArchive::CREATE) === true) {
							foreach ($allfiles as $allfile) {
								// Ensure only CSV, JSON, or related export files are included
								if (preg_match('/\.(csv|json|xml|xls|xlsx|tsv|' . preg_quote($this->exportType, '/') . ')$/', $allfile)) {
									$newname = str_replace($upload_dir, '', $allfile);
									$zip->addFile($allfile, $newname);
								}
							}
							$zip->close();
						}
				
						$zipURL = $upload_url  . 'smbundle_' . $this->fileName . '.zip';
				
						// Remove original files after zipping
						foreach ($allfiles as $removefile) {
							//unlink($removefile);
						}
				
						$filename = $upload_dir  . 'smbundle_' . $this->fileName . '.zip';

						$responseTojQuery = array(
							'success' => true,
							'new_offset' => $this->offset,
							'limit' => $this->limit,
							'total_row_count' => $this->totalRowCount,
							'exported_file' => $fileURL ,
							'zip_file' => $zipURL,
							'exported_path' => $filename,
							'export_type' => $this->exportType	
						);
						echo wp_json_encode($responseTojQuery);
					wp_die();
					}

			if ($this->checkSplit == 'true' && !($this->offset) > ($this->totalRowCount)) {
				// When the split condition is true but offset is within the total row count
				$responseTojQuery = array(
					'success' => false,
					'new_offset' => $this->offset,
					'limit' => $this->limit,
					'total_row_count' => $this->totalRowCount,
					'exported_file' => $zipname,
					'exported_path' => $zipname,
					'export_type' => $export_type
				);
			} elseif ($this->checkSplit == 'true' && (($this->offset) > ($this->totalRowCount))) {
				if ($this->exportType == 'xls' || $this->exportType == 'xlsx') {
					// Convert CSV to XLS or XLSX depending on the export type
					$newpath = str_replace('.csv', '.' . $this->exportType, $filePath);
					$newfilename = str_replace('.csv', '.' . $this->exportType, $fileURL);
					if($this->exportType == 'xlsx'){
						$reader = IOFactory::createReader('Xlsx');
					}else{
						$reader = IOFactory::createReader('Xls');
					}
					if (file_exists($filePath)) {
						$objPHPExcel = $reader->load($filePath);
						$spreadsheet = new Spreadsheet(); // Create new Spreadsheet object
						if($this->exportType == 'xlsx'){
							$objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
						}else{
							$objWriter = IOFactory::createWriter($spreadsheet, 'Xls');
						}
						$objWriter->save($newpath);
					}
			
					// Response after conversion
					$responseTojQuery = array(
						'success' => true,
						'new_offset' => $this->offset,
						'limit' => $this->limit,
						'total_row_count' => $this->totalRowCount,
						'exported_file' => $newfilename,
						'exported_path' => $newpath,
						'export_type' => $this->exportType
					);
					$this->ClearedOptions();
				}				
				else {
					// If export type is neither XLS nor XLSX, return original file
					$responseTojQuery = array(
						'success' => true,
						'new_offset' => $this->offset,
						'limit' => $this->limit,
						'total_row_count' => $this->totalRowCount,
						'exported_file' => $fileURL,
						'exported_path' => $fileURL,
						'export_type' => $this->exportType
					);
					$this->ClearedOptions();
				}
			} elseif (!(($this->offset) > ($this->totalRowCount))) {
				// When the offset is still within the row count, and checkSplit is false
				$responseTojQuery = array(
					'success' => false,
					'new_offset' => $this->offset,
					'limit' => $this->limit,
					'total_row_count' => $this->totalRowCount,
					'exported_file' => $filename,
					'exported_path' => $filePath,
					'export_type' => $export_type
				);
			} else {
				// General case where we perform export
				if ($this->exportType == 'xls' || $this->exportType == 'xlsx') {
					// Convert CSV to XLS or XLSX depending on the export type
					$newpath = str_replace('.csv', '.' . $this->exportType, $filePath);
    $newfilename = str_replace('.csv', '.' . $this->exportType, $fileURL);

					// Load the CSV and save as XLS or XLSX based on export type
					$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
$objPHPExcel = $reader->load($filePath);

					if ($this->exportType == 'xls') {
						// If export type is XLS, save it as XLS
            $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xls');
        } else {
						// If export type is XLSX, save it as XLSX
            $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
        }
        $objWriter->save($newpath);
					
					$responseTojQuery = array(
						'success' => true,
						'new_offset' => $this->offset,
						'limit' => $this->limit,
        'total_row_count' => $this->totalRowCount,
						'exported_file' => $newfilename,
						'exported_path' => $newpath,
						'export_type' => $this->exportType
					);
    $this->ClearedOptions();
				} else {
					// If export type is neither XLS nor XLSX, return original file
					$responseTojQuery = array(
						'success' => true,
						'new_offset' => $this->offset,
						'limit' => $this->limit,
						'total_row_count' => $this->totalRowCount,
						'exported_file' => $filename,
						'exported_path' => $filePath,
						'export_type' => $this->exportType
					);
					$this->ClearedOptions();
				}
			}
			

			if($this->export_mode == 'normal'){
				echo wp_json_encode($responseTojQuery);
				wp_die();
			}
			else{
				$this->export_log = $responseTojQuery;
			}
			}

			public function ClearedOptions(){
				delete_option('advancedFilter_export_total_count');
			}
			/**
			 * Get post data based on the record id
			 * @param $id       - Id of the records
			 * @return array    - Data based on the requested id.
			 */
			public function getPostsDataBasedOnRecordId ($id,$module) {
				global $wpdb;
				$PostData = array();
				if($module == 'Images'){
					$query1 = $wpdb->prepare("SELECT wp.* FROM {$wpdb->prefix}posts wp where ID=%d", $id);
					$result_query1 = $wpdb->get_results($query1);
				}else{
					$wp_posts_fields_column_value = [
						'ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 
						'comment_status', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 
						'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count'
					];
					$filtered_headers = array_intersect($this->headers, $wp_posts_fields_column_value);

					$selected_columns = implode(', ', $filtered_headers);

					$query1 = $wpdb->prepare("SELECT $selected_columns FROM {$wpdb->prefix}posts wp where ID=%d", $id);
					$result_query1 = $wpdb->get_results($query1);
				}

				if (!empty($result_query1)) {
					foreach ($result_query1 as $posts) {
						if(isset($posts->post_parent) && is_numeric($posts->post_parent) && $posts->post_parent !=='0' && $posts->post_type !=='product_variation'){
							if($module != 'WooCommerceRefunds'){
								$tit=get_the_title($posts->post_parent);
								$posts->post_parent=$tit;
							}		
						}
						if (!empty($posts->post_type ?? null) && ($posts->post_type == 'event' || $posts->post_type == 'event-recurring')) {

							$loc=get_post_meta($id , '_location_id' , true);
							$event_id=get_post_meta($id , '_event_id' , true);
							$res = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}em_locations WHERE location_id='$loc' "); 

							if($res){
								foreach($res as $location){
									unset($location-> post_content);	
									$posts=array_merge((array)$posts,(array)$location);
								}
							}

							$ticket = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}em_tickets WHERE event_id='$event_id' "); 

							$ticket[0]=isset($ticket[0])?$ticket[0]:'';
							$ticket_meta= $ticket[0];
							if(isset($ticket_meta->{'ticket_meta'})){
								$ticket_meta_value=$ticket_meta->{'ticket_meta'};
							}
							$ticket_meta_value=isset($ticket_meta_value)?$ticket_meta_value:'';
							$ticket_value=unserialize($ticket_meta_value);
							if(isset($ticket_id)){
								$ticket_values = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}em_tickets WHERE ticket_id='$ticket_id' ");
							}
							$count=count($ticket);
							if($count>1){
								$ticknamevalue = '';
								$tickidvalue = '';
								$eventidvalue = '';
								$tickdescvalue = '';
								$tickpricevalue = '';
								$tickstartvalue = '';
								$tickendvalue = '';
								$tickminvalue = '';
								$tickmaxvalue = '';
								$tickspacevalue = '';
								$tickmemvalue = '';
								$tickmemrolevalue = '';									
								$tickguestvalue = '';
								$tickreqvalue = '';
								$tickparvalue = '';
								$tickordervalue = '';
								$tickmetavalue = '';
								$tickstartdays = '';
								$tickenddays = '';
								$tickstarttime = '';
								$tickendtime = '';
								$t=0;

								foreach($ticket as $tic => $ticval){
									$ticknamevalue .= $ticval->ticket_name . ', ';
									$tickidvalue .=$ticval->ticket_id . ', ';
									$eventidvalue .=$ticval->event_id . ', ';
									$tickdescvalue .=$ticval->ticket_description . ', ';
									$tickpricevalue .=$ticval->ticket_price . ', ';
									$tickstartvalue .=$ticval->ticket_start . ', ';
									$tickendvalue .=$ticval->ticket_end . ', ';
									$tickminvalue .=$ticval->ticket_min . ', ';
									$tickmaxvalue .=$ticval->ticket_max . ', ';
									$tickspacevalue .=$ticval->ticket_spaces . ', ';
									$tickmemvalue .=$ticval->ticket_members . ', ';
									$tickmemroles =unserialize($ticval->ticket_members_roles);
									$tickmemroleval=implode('| ',(array)$tickmemroles);
									$tickmemrolevalue .=$tickmemroleval . ', ';


									$tickguestvalue .=$ticval->ticket_guests . ', ';
									$tickreqvalue .=$ticval->ticket_required . ', ';
									$tickparvalue .=$ticval->ticket_parent . ', ';
									$tickordervalue .=$ticval->ticket_order . ', ';
									$tickmetavalue .=$ticval->ticket_meta . ', ';
									$ticket[$t]=isset($ticket[$t])?$ticket[$t]:'';
									$ticket_meta= $ticket[$t];
									if(isset($ticket_meta->{'ticket_meta'})){
										$ticket_meta_value=$ticket_meta->{'ticket_meta'};
									}
									$ticket_meta_value=isset($ticket_meta_value)?$ticket_meta_value:'';
									if(!empty($ticket_meta_value)){
										$ticket_value=unserialize($ticket_meta_value);
									}

									foreach($ticket_value as $tickval => $val){
										$tickstartdays .= $val['start_days'].', ';
										$tickenddays .= $val['end_days'].', ';
										$tickstarttime .= $val['start_time'].', ';
										$tickendtime .= $val['end_time'].', ';
									}

									$ticknamevalues = rtrim($ticknamevalue, ', ');
									$tickidvalues = rtrim($tickidvalue, ', ');
									$eventidvalues=rtrim($eventidvalue, ', ');
									$tickdescvalues=rtrim($tickdescvalue, ', ');
									$tickpricevalues =rtrim($tickpricevalue, ', ');
									$tickstartvalues   =rtrim($tickstartvalue, ', ');
									$tickendvalues   =rtrim($tickendvalue, ', ');
									$tickminvalues   =rtrim($tickminvalue, ', ');
									$tickmaxvalues =rtrim($tickmaxvalue, ', ');
									$tickspacevalues =rtrim($tickspacevalue, ', ');	
									$tickmemvalues	=rtrim($tickmemvalue, ', ');
									$tickmemrolevalues	=rtrim($tickmemrolevalue, ', ');
									$tickguestvalues	=rtrim($tickguestvalue, ', ');
									$tickreqvalues	=rtrim($tickreqvalue, ', ');
									$tickparvalues	=rtrim($tickparvalue, ', ');
									$tickordervalues	=rtrim($tickordervalue, ', ');	
									$tickmetavalues	=rtrim($tickmetavalue, ', ');	
									$tickstartdaysvalues = rtrim($tickstartdays, ', ');
									$tickenddaysvalues = rtrim($tickenddays, ', ');
									$tickstarttimevalues = rtrim($tickstarttime, ', ');
									$tickendtimevalues = rtrim($tickendtime, ', ');	


									$tic_key1 = array('ticket_id', 'event_id', 'ticket_name','ticket_description','ticket_price','ticket_start','ticket_end','ticket_min','ticket_max','ticket_spaces','ticket_members','ticket_members_roles','ticket_guests','ticket_required','ticket_parent','ticket_order','ticket_meta','start_days','end_days','start_time','end_time');
									$tic_val1 = array($tickidvalues,$eventidvalues, $ticknamevalues,$tickdescvalues,$tickpricevalues,$tickstartvalues,$tickendvalues,$tickminvalues,$tickmaxvalues,$tickspacevalues,$tickmemvalues,$tickmemrolevalues,$tickguestvalues,$tickreqvalues,$tickparvalues,$tickordervalues,$tickmetavalues,$tickstartdaysvalues,$tickenddaysvalues,$tickstarttimevalues,$tickendtimevalues);

									$tickets1 = array_combine($tic_key1,$tic_val1);
									$posts=array_merge((array)$posts,(array)$tickets1);
									$ticket_start[] = $ticval->ticket_start;

									$ticket_start_date = '';
									$ticket_start_time ='';
									foreach(  $ticket_start as $loc =>$locval){
										$date = strtotime($locval);
										$ticket_start_date .= date('Y-m-d', $date) . ', ';

										$ticket_start_time .= date('H:i:s',$date) .', ';	


									}
									$ticket_start_times = rtrim($ticket_start_time, ', ');
									$ticket_start_dates = rtrim($ticket_start_date, ', ');
									$ticket_end[] = trim($ticval->ticket_end);
									$ticket_end_time = '';
									$ticket_end_date = '';
									foreach($ticket_end as $loc => $locvalend){											
										if(isset($locvalend) && !empty($locvalend)){
											$time = strtotime($locvalend);
											$ticket_end_date .= date('Y-m-d', $time) .', ';
											$ticket_end_time .= date('H:i:s',$time) .', ';
										}

									}	
									if(isset($ticket_start_date) && !empty($ticket_start_date)){   
										$ticket_end_times = rtrim($ticket_end_time, ', ');
										$ticket_end_dates = rtrim($ticket_end_date, ', ');
										$tic_key = array('ticket_start_date', 'ticket_start_time', 'ticket_end_date','ticket_end_time');
										$tic_val = array($ticket_start_dates,$ticket_start_times, $ticket_end_dates,$ticket_end_times);
										$tickets = array_combine($tic_key,$tic_val);
										$posts=array_merge((array)$posts,(array)$tickets);
									}

								}

							}
							else{
								foreach($ticket as $tic => $ticval){
									$posts=array_merge((array)$posts,(array)$ticval);
									if(isset($ticval->ticket_start)){
										$ticket_start=$ticval->ticket_start;
									}
									if(is_array($ticket_value)){
										foreach($ticket_value as $tick => $val){
											$posts=array_merge((array)$posts,(array)$val);
										}
									}										
									if(isset($ticket_start) && ($ticket_start != null)){
										$date = strtotime($ticket_start);																						
										$ticket_start_date = date('Y-m-d', $date);
										$ticket_start_time= date('H:i:s',$date);
										$ticket_end=$ticval->ticket_end;
										$time = strtotime($ticket_end);
										$ticket_end_date = date('Y-m-d', $time);
										$ticket_end_time= date('H:i:s',$time);
										$tic_key = array('ticket_start_date', 'ticket_start_time', 'ticket_end_date','ticket_end_time');
										$tic_val = array($ticket_start_date,$ticket_start_time, $ticket_end_date,$ticket_end_time);
										$tickets = array_combine($tic_key,$tic_val);
										$posts=array_merge((array)$posts,(array)$tickets);

									}
								}
							}

						}											
						$post_type=isset($posts->post_type)?$posts->post_type:'';
						$p_type=$post_type;
						$posid = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts  where post_name='$p_type' and post_type='_pods_pod'");
						foreach($posid as $podid){
							$pods_id=$podid->ID;
							$storage = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}postmeta  where post_id=$pods_id AND meta_key='storage'");
							foreach($storage as $pod_storage){
								$pod_stype=$pod_storage->meta_value;
							}

						}
						if(isset($pod_stype) && $pod_stype=='table'){
							$tab='pods_'.$p_type;
							$tab_val = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}$tab where id=$id");
							foreach($tab_val as $table_key =>$table_val ){
								$posts=array_merge((array)$posts,(array)$table_val);

							}

						}
						foreach ($posts as $post_key => $post_value) {
							if(!is_numeric($post_key)){
								if ($post_key == 'post_status') {
									if (is_sticky($id)) {
										$PostData[$post_key] = 'Sticky';
										$post_status = 'Sticky';
									} else {
										$PostData[$post_key] = $post_value;
										$post_status = $post_value;
									}
								} else {
									$PostData[$post_key] = $post_value;

								}
								if ($post_key == 'post_password') {
									if ($post_value) {
										$PostData['post_status'] = "{" . $post_value . "}";
									} else {
										$PostData['post_status'] = $post_status;
									}
								}	
								if($post_key == 'post_author'){
									$user_info = get_userdata($post_value);								
									$user_info=isset($user_info)?$user_info:'';
									$user_login=isset($user_info->user_login)?$user_info->user_login:'';
									$PostData['post_author'] = $user_login;
								}
							}
						}




					}
				}			
				return $PostData;
			} 

			public function getWPMLData ($id,$optional_type,$exp_module) {

				global $wpdb;
				global $sitepress;
				if($sitepress != null) {
					$icl_translation_table = $wpdb->prefix.'icl_translations';
					if($exp_module == 'Categories' || $exp_module == 'Tags' || $exp_module == 'Taxonomies'){
						$get_element_type = 'tax_'.$optional_type;
						$get_element_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->prefix}term_taxonomy WHERE term_id = $id");

						//added
						if(!empty($get_element_tax_id)){
							$args = array('element_id' => $get_element_tax_id ,'element_type' => $get_element_type);
						}
						else{
							$args = array('element_id' => $id ,'element_type' => $get_element_type);
						}
					}
					else{
						$get_element_type = 'post_'.$optional_type;
						$args = array('element_id' => $id ,'element_type' => $get_element_type);
					}

					$get_language_code = apply_filters( 'wpml_element_language_code', null, $args );

					// $code = apply_filters( 'wpml_post_language_details', null,  $id );
					// $get_language_code = $code['language_code'];
					//$get_language_code = $wpdb->get_var("select language_code from {$icl_translation_table} where element_id ='{$id}'");

					//added
					//$get_source_language = $wpdb->get_var("select source_language_code from {$icl_translation_table} where element_id ='{$id}' and language_code ='{$get_language_code}'");
					if($exp_module == 'Categories' || $exp_module == 'Tags' || $exp_module == 'Taxonomies'){
						if(!empty($get_element_tax_id)){
							$get_source_language = $wpdb->get_var("select source_language_code from {$icl_translation_table} where element_id ='{$get_element_tax_id}' and language_code ='{$get_language_code}'");
						}
						else{
							$get_source_language = $wpdb->get_var("select source_language_code from {$icl_translation_table} where element_id ='{$id}' and language_code ='{$get_language_code}'");
						}
					}
					else{
						$get_source_language = $wpdb->get_var("select source_language_code from {$icl_translation_table} where element_id ='{$id}' and language_code ='{$get_language_code}'");
					}

					$this->data[$id]['language_code'] = $get_language_code;	

					$get_trid = apply_filters( 'wpml_element_trid', NULL, $id,$get_element_type );
					if(!empty($get_source_language)){
						// $original_element_id_prepared = $wpdb->prepare(
						// 	"SELECT element_id
						// 	FROM {$wpdb->prefix}icl_translations
						// 	WHERE trid=%d
						// 	and language_code !='%s'
						// 	LIMIT 1",$get_trid,$get_language_code
						// );
						// $element_id = $wpdb->get_var( $original_element_id_prepared );

						$translations_query = $wpdb->prepare(
							"SELECT element_id,language_code
							FROM {$wpdb->prefix}icl_translations
							WHERE trid = %d
							AND language_code != %s", $get_trid, $get_language_code
						);
						$element_id = $wpdb->get_results( $translations_query );
						$translated_post_title = $translated_taxonomy_title = '';

						foreach ($element_id as $translation) {
							$element_id = $translation->element_id;
							if($exp_module == 'Posts' || $exp_module == 'WooCommerce' || $exp_module == 'CustomPosts' || $exp_module == 'Pages'){							
								$element_title = $wpdb->get_var("select post_title from $wpdb->posts where ID ='{$element_id}'");
								$translated_post_title .= $element_title.",";
							}
							else{
								$element_title =  $wpdb->get_var("select name from $wpdb->terms where term_id ='{$element_id}'");
								$this->data[$id]['translated_taxonomy_title'] = $element_title;
							}
						}
						$this->data[$id]['translated_post_title'] = rtrim($translated_post_title, ",");
						$this->data[$id]['translated_taxonomy_title'] = rtrim($translated_taxonomy_title, ",");

						// if($exp_module == 'Posts' || $exp_module == 'WooCommerce' || $exp_module == 'CustomPosts' || $exp_module == 'Pages'){							
						// 	$element_title = $wpdb->get_var("select post_title from $wpdb->posts where ID ='{$element_id}'");
						// 	$this->data[$id]['translated_post_title'] = $element_title;
						// }
						// else{
						// 	$element_title =  $wpdb->get_var("select name from $wpdb->terms where term_id ='{$element_id}'");
						// 	$this->data[$id]['translated_taxonomy_title'] = $element_title;
						// }
					}
					return $this->data[$id];
				}	
			}
			public function getPolylangData ($id,$optional_type,$exp_module) {
				global $wpdb;
				global $sitepress;
				$terms=$wpdb->get_results("select term_taxonomy_id from $wpdb->term_relationships where object_id ='{$id}'");
				$terms_id=json_decode(json_encode($terms),true);
				$post_title = '';
				if(is_plugin_active('polylang-pro/polylang.php')){
					if($exp_module == 'Categories' || $exp_module == 'Tags' || $exp_module == 'Taxonomies'){
						$get_language = pll_get_term_language($id);
						$get_translation = pll_get_term_translations($id);
						unset($get_translation[$get_language]);
						$this->data[$id]['language_code'] = $get_language;
						foreach($get_translation as $trans_key => $trans_val){
							$title = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms where term_id=$trans_val");
							$post_title .= $title.',';
						}
						$this->data[$id]['translated_taxonomy_title'] = rtrim($post_title,',');
					}
					else{
						$get_language=pll_get_post_language( $id );
						$get_translation=pll_get_post_translations($id);
						unset($get_translation[$get_language]);
						$this->data[$id]['language_code'] = $get_language;
						foreach($get_translation as $trans_key => $trans_val){
							$title = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts where id=$trans_val");
							if($exp_module == 'WooCommerceVariations'){
								$post_title .= $title.'|';	
							}
							else{
								$post_title .= $title.',';
							}
						}

						if($exp_module == 'WooCommerceVariations'){
							$this->data[$id]['translated_post_title'] = isset($post_title) ? rtrim($post_title,'|') : '';
						}
						else{

							$this->data[$id]['translated_post_title']= isset($post_title) ? rtrim($post_title,',') : '';
						}
					}
				}
				else{
					foreach($terms_id as $termkey => $termvalue){
						$post_title = '';
						$termids=$termvalue['term_taxonomy_id'];
						$check=$wpdb->get_var("select taxonomy from $wpdb->term_taxonomy where term_id ='{$termids}'");
						if($check == 'category'){
							$category=$wpdb->get_var("select name from $wpdb->terms where term_id ='{$termids}'");
						}
						elseif($check =='language'){
							$language=$wpdb->get_var("select description from $wpdb->term_taxonomy where term_id ='{$termids}'");
							$lang=unserialize($language);
							$langcode=explode('_',$lang['locale']);
							$lang_code=$langcode[0];
							$this->data[$id]['language_code'] = $lang_code;
						}
						elseif($check == 'term_language'){
							if($exp_module == 'Categories' || $exp_module == 'Tags' || $exp_module == 'Taxonomies'){
								$language = $wpdb->get_var("select description from $wpdb->term_taxonomy where term_id ='{$termids}'");
								$lang = unserialize($language);
								$langcode = explode('_', $lang['locale']);
								$lang_code = $langcode[0];
								if(empty($this->data[$id]['language_code'])){
									$this->data[$id]['language_code'] = $lang_code;
								}

							}
						}
						elseif(($exp_module == 'Categories' || $exp_module == 'Tags') &&$check == 'term_translations'){
							$description = $wpdb->get_var("select description from $wpdb->term_taxonomy where term_id ='{$termids}'");
							$desc = unserialize($description);
							$post_id = is_array($desc) ? array_values($desc) : array();
							// $postid = min($post_id);
							foreach($post_id as $post_key => $post_value){
								if($id == $post_value){
									unset($post_id[$post_key]);
								}
							}

							foreach($post_id as $trans_key => $trans_val){
								$title = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms where term_id=$trans_val");
								$post_title .= $title.',';
							}

							$this->data[$id]['translated_taxonomy_title'] = rtrim($post_title,',');
						}
						elseif (($exp_module !== 'Categories' && $exp_module !== 'Tags') &&  $check == 'post_translations')
						{
							$description = $wpdb->get_var("select description from $wpdb->term_taxonomy where term_id ='{$termids}'");
							$desc = unserialize($description);
							$post_id = is_array($desc) ? array_values($desc) : array();
							// $postid = min($post_id);
							foreach($post_id as $post_key => $post_value){
								if($id == $post_value){
									unset($post_id[$post_key]);
								}
							}
							foreach($post_id as $trans_key => $trans_val){
								$post_title = $wpdb->get_var("select post_title from $wpdb->posts where ID ='{$trans_val}'");
								$this->data[$id]['translated_post_title'] = $post_title;
							}
						}
					}	
				}
			}

			public function getGeoPlaceData ($id,$optional_type,$exp_module) {

				$post_info = geodir_get_post_info($id);
				foreach ($post_info as $gdKey => $gdVal){				
					if(!empty($gdVal)){
						$this->data[$id][ $gdKey ] = $gdVal ;
					}
				} 											
			}

			public function getGfEntriesData ($optional_type,$exp_module) {

				$this->generateHeaders('CustomPosts', $optional_type);



				$forms = \GFAPI::get_forms();
				$overall_count = 0;
				$forms = \GFAPI::get_forms();			
				foreach ($forms as $form) {
					$form_id = $form['id'];
					$count = \GFAPI::count_entries($form_id);
					$overall_count += $count;
					// Get all entries for this form
					$entries = \GFAPI::get_entries($form_id);

					if (empty($entries)) {
						continue; // Skip forms with no entries
					}

					$headers = ['form_id', 'entry_id'];
					$field_map = []; 

					foreach ($form['fields'] as $field) {
						$header = "{$form_id}_{$field->id}"; 
						$header_label = "{$form_id}_{$field->label}"; 
						$headers[] = $header; 
						$field_map[$header] = $field->id; 
					}


					foreach ($entries as $entry) {
						$row = [$form_id, $entry['id']]; 

						foreach ($field_map as $header => $field_id) {
						//	$row['form_id'] = $form_id;
						//	$row['entry_id'] = $entry['id'];
							$row[$header] = isset($entry[$field_id]) ? $entry[$field_id] : ''; 
							
						}

						$result[] = array_combine($headers,$row);


					}

					$this->totalRowCount = $count;
			
					self::proceedExport($result);

				}
			}	


			public function getAttachment($id)
			{
				global $wpdb;
				$get_attachment = $wpdb->prepare("select guid from {$wpdb->prefix}posts where ID = %d AND post_type = %s", $id, 'attachment');
				$attachment = $wpdb->get_results($get_attachment);
				$attachment=isset($attachment)?$attachment:'';
				$attachment_file = isset($attachment[0]->guid)?$attachment[0]->guid:'';
				$attachment_file=isset($attachment_file)?$attachment_file:'';
				return $attachment_file;
			}

			public function getRepeater($parent)
			{
				global $wpdb;
				$get_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts where post_parent = %d", $parent), ARRAY_A);
				$i = 0;
				foreach ($get_fields as $key => $value) {
					$array[$i] = $value['post_excerpt'];
					$i++;
				}
				return $array;	
			}

			/**
			 * Get types fields
			 * @return array    - Types fields
			 */
			public function getTypesFields() {
				$getWPTypesFields = get_option('wpcf-fields');

				$typesFields = array();
				if(!empty($getWPTypesFields) && is_array($getWPTypesFields)) {
					foreach($getWPTypesFields as $fKey){
						$typesFields[$fKey['meta_key']] = $fKey['name'];
					}
				}
				return $typesFields;
			}

			/**
			 * Final data to be export
			 * @param $data     - Data to be export based on the requested information
			 * @return array    - Final data to be export
			 */
			public function finalDataToExport ($data, $module = false , $optionalType = false) {
				global $wpdb;
				 // Log the initial data coming into this function
				$result = array();
				foreach ($this->headers as $key => $value) {
					if(empty($value)){
						unset($this->headers[$key]);
					}
				}
				// Fetch Category Custom Field Values
				if($module){
					if($module == 'Categories' || $module == 'Tags'){
						return $this->fetchCategoryFieldValue($data, $this->module);
					}
				}

				$toolset_relationship_fieldnames = ['types_relationship', 'relationship_slug', 'intermediate'];

				foreach ( $data as $recordId => $rowValue ) {
					$optional_type = '';
					if(is_plugin_active('jet-engine/jet-engine.php')){
						global $wpdb;
						$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
						foreach($get_slug_name as $key=>$get_slug){
							$value=$get_slug->slug;
							$optionaltype=$value;
							if($optionalType == $optionaltype){
								$optional_type=$optionaltype;
							}
						}
					}
if ($module == 'Comments' || $module == 'WooCommerceReviews') {
	if (!empty($rowValue['comment_post_ID'])) {
		$post = get_post($rowValue['comment_post_ID']);
		if ($post) {
			$rowValue['post_title'] = $post->post_title;
		} else {
			$rowValue['post_title'] = '';
		}
	}
}
					foreach ($this->headers as $htemp => $hKey) {
						if($hKey == 'post_title'){

							// if(strpos($rowValue[$hKey],'-') !==false){
							// 	$rowValue[$hKey] = str_replace('-','–',$rowValue[$hKey]);
							// }
						}
					
						if(is_array($rowValue) && array_key_exists($hKey, $rowValue) && !empty($rowValue[$hKey]) &&
						!preg_match('/^(og_|twitter_|robots_|keyphrases_)/i', $hKey) ){

							if(isset($this->typeOftypesField) && is_array($this->typeOftypesField) && array_key_exists('wpcf-'.$hKey, $this->typeOftypesField)){
								if($rowValue[$hKey] == 'Array'){
									$result[$recordId][$hKey] = $this->getToolsetRepeaterFieldValue($hKey, $recordId, $rowValue[$hKey]);
								}else{
									$result[$recordId][$hKey] = $this->returnMetaValueAsCustomerInput($rowValue[$hKey], $hKey);
								}
							}

							if(!empty($optional_type) && $optionalType == $optional_type){								
								if(is_plugin_active('jet-engine/jet-engine.php')){
									$result = $this->getJetCCTValue($data,$optionalType);								
									if(is_array($result)){
										return $result;
										die;
									}
									else{
										$result[$recordId][$hKey] = $this->returnMetaValueAsCustomerInput($rowValue[$hKey], $hKey);return $result;	
									}		
								}							
							}
                              

							else{
								$result[$recordId][$hKey] = $this->returnMetaValueAsCustomerInput($rowValue[$hKey], $hKey);
							}
						}	
						else{ 

							$key = $hKey;
							$key = $this->replace_prefix_aioseop_from_fieldname($key);
							$key = $this->replace_prefix_yoast_wpseo_from_fieldname($key);
							$key = $this->replace_prefix_wpcf_from_fieldname($key);
							$key = $this->replace_prefix_wpsc_from_fieldname($key);
							$key = $this->replace_underscore_from_fieldname($key);
							$key = $this->replace_wpcr3_from_fieldname($key);
							// Change fieldname depends on the post type
							$rowValue['post_type']=isset($rowValue['post_type'])?$rowValue['post_type']:'';
							$key = $this->change_fieldname_depends_on_post_type($rowValue['post_type'], $key);			

							if(isset($this->typeOftypesField) && is_array($this->typeOftypesField) && array_key_exists('wpcf-'.$key, $this->typeOftypesField)){
								$rowValue[$key] = $this->getToolsetRepeaterFieldValue($key, $recordId);
							}else if($key == 'Parent_Group'){
								$rowValue[$key] = $this->getToolsetRepeaterParentValue($module);
							}else if($toolset_group_title = $this->hasToolsetRelationship($key, $recordId)){
								$rowValue[$key] = $toolset_group_title;
							}else if(isset($rowValue['wpcr3_'.$key])){
								$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue['wpcr3_'.$key], $hKey);
							}else if (is_plugin_active('slim-seo/slim-seo.php')||    is_plugin_active('slim-seo-pro/slim-seo.php') ||
        is_plugin_active('slim-seo-pro/main.php') ||
        is_plugin_active('slim-seo-pro/slim-seo-pro.php')) {
$slimseo_keys = ['title','description','canonical','noindex','nofollow','redirect','facebook_image','twitter_image','schema'];

if (in_array($key, $slimseo_keys)) {

    if ($key === 'schema') {
	        $slim_seo_schema = get_post_meta($recordId, 'slim_seo_schema', true);

        if (empty($slim_seo_schema)) {
            $rowValue[$key] = '';
        } else {
            if (is_serialized($slim_seo_schema)) {
                $slim_seo_schema = maybe_unserialize($slim_seo_schema);
            }
            $rowValue[$key] = is_array($slim_seo_schema) ? json_encode($slim_seo_schema) : $slim_seo_schema;
        }

    } else {
        $slim_seo_meta = get_post_meta($recordId, 'slim_seo', true);

        if (empty($slim_seo_meta)) {
            $rowValue[$key] = '';
        } else {
            if (is_serialized($slim_seo_meta)) {
                $slim_seo_meta = maybe_unserialize($slim_seo_meta);
            }

            if (in_array($key, ['facebook_image','twitter_image']) && is_array($slim_seo_meta[$key])) {
                $rowValue[$key] = isset($slim_seo_meta[$key]['url']) ? $slim_seo_meta[$key]['url'] : '';
            } else {
                $rowValue[$key] = isset($slim_seo_meta[$key]) ? $slim_seo_meta[$key] : '';
            }
        }
    }

    $result[$recordId][$key] = $rowValue[$key];
    continue;
}

}
							else{

								$rowValue['post_type']=isset($rowValue['post_type'])?$rowValue['post_type']:'';
								$rowValue[$key]=isset($rowValue[$key])?$rowValue[$key]:'';

								if(isset($key,$this->allacf) && is_array($this->allacf) && array_key_exists($key, $this->allacf)){
									$rowValue[$this->allacf[$key]]=isset($rowValue[$this->allacf[$key]])?$rowValue[$this->allacf[$key]]:'';
									$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue[$this->allacf[$key]], $hKey);									
								}

								else if(isset($rowValue['_yoast_wpseo_'.$key])){ // Is available in yoast plugin
									$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue['_yoast_wpseo_'.$key]);
								}
								else if(isset($rowValue['_aioseop_'.$key])){ // Is available in all seo plugin
									$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue['_aioseop_'.$key]);
								}
								else if(isset($rowValue['_'.$key])){ // Is wp custom fields
									$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue['_'.$key], $hKey);
								}
								else if($fieldvalue = $this->getWoocommerceMetaValue($key, $rowValue['post_type'], $rowValue)){
									$rowValue[$key] = $fieldvalue;
								}	
								else if(is_plugin_active('bbpress/bbpress.php')){
									if($optionalType == 'topic'|| $optionalType == 'reply'){
										$rowValue['post_status'] =isset($rowValue['post_status'])?$rowValue['post_status']:'';
										$rowValue['post_author'] =isset($rowValue['post_author'])?$rowValue['post_author']:'';
										if($rowValue['post_status'] == 'publish' ){
											$rowValue['status'] = 'open';
										}
										else{
											$rowValue['status'] = $rowValue['post_status'];
										}
										if(!empty($rowValue['post_author'])){
											$post_author = $rowValue['post_author'];
											$user_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users where user_login='$post_author'");
											$rowValue['author'] = $user_id;
										}
									}

								}														
								else if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')){
								
									if($aioseo_field_value = $this->getaioseoFieldValue($recordId)){
										$rowValue['og_title'] = $aioseo_field_value[0]->og_title;
										$rowValue['og_description']= $aioseo_field_value[0]->og_description;
										$rowValue['custom_link'] = $aioseo_field_value[0]->canonical_url;
										$rowValue['og_image_type'] = $aioseo_field_value[0]->og_image_type;
										$rowValue['og_image_custom_fields'] = $aioseo_field_value[0]->og_image_custom_fields;
										$rowValue['og_video'] = $aioseo_field_value[0]->og_video;
										$rowValue['og_object_type'] = $aioseo_field_value[0]->og_object_type;
										$value=$aioseo_field_value[0]->og_article_tags;
										$article_tags = json_decode($value);
										$og_article_tags=isset($article_tags[0]->value)?$article_tags[0]->value:'';
										$rowValue['og_article_tags'] = $og_article_tags;
										$rowValue['og_article_section'] = $aioseo_field_value[0]->og_article_section;
										$rowValue['twitter_use_og'] = $aioseo_field_value[0]->twitter_use_og;
										$rowValue['twitter_card'] = $aioseo_field_value[0]->twitter_card;
										$rowValue['twitter_image_type'] = $aioseo_field_value[0]->twitter_image_type;
										$rowValue['twitter_image_custom_fields'] = $aioseo_field_value[0]->twitter_image_custom_fields;
										$rowValue['twitter_title'] = $aioseo_field_value[0]->twitter_title;
										$rowValue['twitter_description'] = $aioseo_field_value[0]->twitter_description;
										$rowValue['robots_default'] = $aioseo_field_value[0]->robots_default;
										// $rowValue['robots_noindex'] = $aioseo_field_value[0]->robots_noindex;
										$rowValue['robots_noarchive'] = $aioseo_field_value[0]->robots_noarchive;
										$rowValue['robots_nosnippet'] = $aioseo_field_value[0]->robots_nosnippet;										
										$rowValue['robots_noimageindex'] = $aioseo_field_value[0]->robots_noimageindex;
										$rowValue['noodp'] = $aioseo_field_value[0]->robots_noodp;
										$rowValue['robots_notranslate'] = $aioseo_field_value[0]->robots_notranslate;
										$rowValue['robots_max_snippet'] = $aioseo_field_value[0]->robots_max_snippet;
										$rowValue['robots_max_videopreview'] = $aioseo_field_value[0]->robots_max_videopreview;
										$rowValue['robots_max_imagepreview'] = $aioseo_field_value[0]->robots_max_imagepreview;
										$rowValue['aioseo_title'] = $aioseo_field_value[0]->title;
										$rowValue['aioseo_description'] = $aioseo_field_value[0]->description;

										if(isset($aioseo_field_value[0]->keyphrases)){
											$key = $aioseo_field_value[0]->keyphrases;
											$key1 = json_decode($key);
											$rowValue['keyphrases'] = $key1->focus->keyphrase;	
										}	
									}							
								}
								else{
									$rowValue[$key] = $this->returnMetaValueAsCustomerInput($rowValue[$key], $hKey);
								}
							}
							global  $wpdb;
							if(in_array($hKey, $toolset_relationship_fieldnames)){
								
								if(in_array($hKey,['relationship_slug', 'intermediate'])){
									$toolset_fieldvalues = $this->getToolSetIntermediateFieldValue($recordId);
								}elseif(in_array($hKey,['relationship_slug', 'types_relationship'])){
									$toolset_fieldvalues = $this->getToolSetRelationshipValue($recordId);
								}
								if(isset($toolset_fieldvalues['types_relationship'])){
									$rowValue['types_relationship'] = $toolset_fieldvalues['types_relationship'];
								}
								if(isset($toolset_fieldvalues['relationship_slug'])){
									$rowValue['relationship_slug'] = $toolset_fieldvalues['relationship_slug'];
								}
								if(isset($toolset_fieldvalues['intermediate'])){
									$rowValue['intermediate'] = $toolset_fieldvalues['intermediate'];
								}
							}

							//Added for user export
							if($key =='user_login')
							{
								$wpsc_query = $wpdb->prepare("select ID from {$wpdb->prefix}users where user_login =%s", $rowValue['user_login']);
								$wpdb->get_results($wpsc_query,ARRAY_A);
							}	

							if(((isset($rowValue['post_excerpt']) && $rowValue['post_excerpt'])||(isset($rowValue['_wp_attachment_image_alt']) && $rowValue['_wp_attachment_image_alt'])||(isset($rowValue['post_content']) && $rowValue['post_content'])||(isset($rowValue['guid']) && $rowValue['guid'])||(isset($rowValue['post_title']) && $rowValue['post_title'])||(isset($rowValue['_wp_attached_file']) && $rowValue['_wp_attached_file'])) && ($module == 'Images')){								

								if($key=='media_id'){
									$rowValue[$key] = isset($rowValue['ID']) ? $rowValue['ID'] : '';
								}
								if($key=='alt_text'){
									$rowValue['_wp_attachment_image_alt'] = get_post_meta($recordId,'_wp_attachment_image_alt');
									$rowValue['_wp_attachment_image_alt'] = isset($rowValue['_wp_attachment_image_alt'][0]) ? $rowValue['_wp_attachment_image_alt'][0] : '';
									$rowValue[$key] = isset($rowValue['_wp_attachment_image_alt']) ? $rowValue['_wp_attachment_image_alt'] : '';
								}
								if($key=='caption'){

									$rowValue[$key] = isset($rowValue['caption']) ? $rowValue['caption'] : '';
									$rowValue[$key]=$rowValue['post_excerpt'];
								}
								if($key=='actual_url'){
									$rowValue[$key]=$rowValue['guid'];
								}
								if($key=='description'){
									$rowValue['post_content']=isset($rowValue['post_content'])?$rowValue['post_content']:'';
									// $rowValue[$key]=$rowValue['post_content'];
									$rowValue[$key]=$rowValue['post_excerpt'];
								}
								if($key=='title'){
									$rowValue['post_title']=isset($rowValue['post_title'])?$rowValue['post_title']:'';
									$rowValue[$key]=$rowValue['post_title'];
								}
								if($key=='file_name'){

									$rowValue['_wp_attached_file'] = get_post_meta($recordId,'_wp_attached_file');
									$rowValue['_wp_attached_file'] = $rowValue['_wp_attached_file'][0];
									$rowValue['_wp_attached_file']=isset($rowValue['_wp_attached_file'])?$rowValue['_wp_attached_file']:'';
									$file_names = explode('/', $rowValue['_wp_attached_file']);
									$file_name = end($file_names);

									$rowValue[$key]=$file_name;
								}
							}

							if(isset($rowValue['_bbp_forum_type']) && ($rowValue['_bbp_forum_type'] =='forum'||$rowValue['_bbp_forum_type']=='category' )){
								if($key =='Visibility'){
									$rowValue[$key]=$rowValue['post_status'];
								}
							}
							if($key =='topic_status' ||$key =='author' ||$key =='topic_type' ){
								$rowValue['topic_status']=$rowValue['post_status'];
								$rowValue['author']=$rowValue['post_author'];
								if($key =='topic_type'){
									$Topictype =get_post_meta($rowValue['_bbp_forum_id'],'_bbp_sticky_topics');
									$topic_types = get_option('_bbp_super_sticky_topics');
									$rowValue['topic_type']='Normal';
									if($Topictype){
										foreach($Topictype as $t_type){
											if($t_type['0']== $recordId){
												$rowValue['topic_type']='sticky';
											}
										}
									}elseif(!empty($topic_types)){
										foreach($topic_types as $top_type){
											if($top_type == $recordId){
												$rowValue['topic_type']='super sticky';
											}
										}
									}
								}	
							}
							if($key =='reply_status'||$key =='reply_author'){
								$rowValue['reply_status']=$rowValue['post_status'];
								$rowValue['reply_author']=$rowValue['post_author'];
							}

							if(array_key_exists($hKey, $rowValue)){
								if($hKey=='focus_keyword'){
									$rowValue[$hKey]= isset($rowValue['_yoast_wpseo_focuskw']) ? $rowValue['_yoast_wpseo_focuskw'] :'';

								}
								elseif($hKey=='meta_desc') {
									$rowValue[$hKey]= isset($rowValue['_yoast_wpseo_metadesc']) ? $rowValue['_yoast_wpseo_metadesc'] :'';
								} 
								elseif($hKey == 'cornerstone-content') {
									$rowValue[$hKey]= isset($rowValue['_yoast_wpseo_is_cornerstone']) ? $rowValue['_yoast_wpseo_is_cornerstone'] :'';
								}

								// if(isset($rowValue['_yoast_wpseo_title'])){
								// 	$rowValue['title']= isset($rowValue['_yoast_wpseo_title']) ? $rowValue['_yoast_wpseo_title'] :'';
								// }
								$result[$recordId][$hKey] = $rowValue[$hKey];
							}else{
								$result[$recordId][$hKey] = '';
							}							
						}
					}	
				}
				return $result;

			}

			public function hasToolsetRelationship($fieldname, $post_id){
				global $wpdb;
				if(is_plugin_active('types/wpcf.php')){					
					$plugins = get_plugins();
					$plugin_version = $plugins['types/wpcf.php']['Version'];
					if($plugin_version < '3.4.1'){
						$toolset_relationship_id = $wpdb->get_var("SELECT id FROM ".$wpdb->prefix."toolset_relationships WHERE slug = '".$fieldname."'");
						if(!empty($toolset_relationship_id)){
							$child_ids = $wpdb->get_results("select child_id from {$wpdb->prefix}toolset_associations WHERE parent_id = '$post_id' and relationship_id = '$toolset_relationship_id'" );							
							$relationship_title = '';
							foreach($child_ids as $child_id){
								$relationship_title.= $wpdb->get_var("SELECT post_title FROM ".$wpdb->prefix."posts WHERE ID = ".$child_id->child_id).'|';
							}
							return rtrim($relationship_title, '|');
						}
					}
					else{
						$relationstitle = $this->hasToolsetRelationshipNew($fieldname, $post_id);
						return $relationstitle;
					}
				}
			}

			public function hasToolsetRelationshipNew($fieldname, $post_id){
				global $wpdb;
				if(is_plugin_active('types/wpcf.php')){
					$toolset_relationship_id = $wpdb->get_var("SELECT id FROM ".$wpdb->prefix."toolset_relationships WHERE slug = '".$fieldname."'");
					if(!empty($toolset_relationship_id)){
						$post_par_id = $wpdb->get_row("SELECT group_id FROM ".$wpdb->prefix."toolset_connected_elements WHERE element_id = ".$post_id );
						$post_par_ids = $post_par_id->group_id;
						$child_ids = $wpdb->get_results("select child_id from {$wpdb->prefix}toolset_associations WHERE parent_id = '$post_par_ids' and relationship_id = '$toolset_relationship_id'" );					
						$relationship_title = '';
						foreach($child_ids as $child_id){
							$post_child_id = $wpdb->get_row("SELECT element_id FROM ".$wpdb->prefix."toolset_connected_elements WHERE group_id = ".$child_id->child_id );
							$post_child_ids = $post_child_id->element_id;
							$relationship_title.= $wpdb->get_var("SELECT post_title FROM ".$wpdb->prefix."posts WHERE ID = ".$post_child_ids).'|';
						}

						return rtrim($relationship_title, '|');
					}
				}
			}

			public function getToolsetRepeaterParentValue($modes){
				global $wpdb;	
				$check_group_names = '';
				$mode = ExportExtension::$post_export->import_post_types($modes);

				if($modes == 'CustomPosts'){
					$mode = $this->optionalType;
				}

				$get_group = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}posts WHERE post_type = 'wp-types-group' AND post_status = 'publish' ");
				foreach($get_group as $get_group_values){
					$check_group = get_post_meta($get_group_values->id , '_wp_types_group_post_types' , true);
					$check_group = explode(',' , $check_group);
					if(in_array( $mode , $check_group)){
						$check_group_names .= $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $get_group_values->id") . "|";
					}
				}
				return rtrim($check_group_names , '|');
			}

			public function getToolsetRepeaterFieldValue($fieldname, $post_id, $fieldvalue = false){
				global $wpdb;				
				if(is_plugin_active('types/wpcf.php')){
					$plugins = get_plugins();
					$plugin_version = $plugins['types/wpcf.php']['Version'];
					if($plugin_version < '3.4.1'){
						switch($this->alltoolsetfields[$fieldname]['type']){	
						case 'textfield':
						case 'textarea':
						case 'image':
						case 'audio':
						case 'colorpicker':
						case 'file':
						case 'embed':
						case 'email':
						case 'numeric':
						case 'phone':
						case 'skype':
						case 'url':
						case 'video':
						case 'wysiwyg':
						case 'checkbox':
							$child_ids = $wpdb->get_results("select child_id from {$wpdb->prefix}toolset_associations WHERE parent_id = '$post_id'" );							
							$toolset_fieldvalue = '';
							foreach($child_ids as $child_id){		
								$meta_value = get_post_meta($child_id->child_id, 'wpcf-'.$fieldname, true);
								$toolset_fieldvalue.=$this->returnMetaValueAsCustomerInput($meta_value).'|';
							}
							$toolset_fieldvalue = ltrim($toolset_fieldvalue, '|');
							if(empty($toolset_fieldvalue)){
								return $fieldvalue;
							}
							return rtrim($toolset_fieldvalue, '|');
						case 'radio': 
						case 'select':
						case 'checkboxes':
							$toolset_fieldvalue = '';
							$child_ids = $wpdb->get_results("select child_id from {$wpdb->prefix}toolset_associations WHERE parent_id = '$post_id'" );

							foreach($child_ids as $child_id){		
								$meta_value = get_post_meta($child_id->child_id, 'wpcf-'.$fieldname, true);	
								$toolset_fieldvalue.=$this->returnMetaValueAsCustomerInput($meta_value, '', $this->alltoolsetfields[$fieldname]['type']).'|';
							}
							$toolset_fieldvalue = ltrim($toolset_fieldvalue, '|');
							if(empty($toolset_fieldvalue)){
								return $fieldvalue;
							}
							return rtrim($toolset_fieldvalue, '|');

						case 'date':
							$toolset_fieldvalue = '';
							$child_ids = $wpdb->get_results("select child_id from {$wpdb->prefix}toolset_associations WHERE parent_id = '$post_id'" );						
							foreach($child_ids as $child_id){
								$meta_value = get_post_meta($child_id->child_id, 'wpcf-'.$fieldname, true);
								if(!empty($meta_value)){
									$meta_value = date('m/d/Y', $meta_value);
									$toolset_fieldvalue.=$this->returnMetaValueAsCustomerInput($meta_value).'|';
								}
							}
							$toolset_fieldvalue = ltrim($toolset_fieldvalue, '|');
							if(empty($toolset_fieldvalue)){
								return $fieldvalue;
							}
							return rtrim($toolset_fieldvalue, '|');		
						}
					}
					else{
						$toolsetfields =$this->getToolsetRepeaterFieldValueNew($fieldname, $post_id, $fieldvalue = false);
						return $toolsetfields;
					}
				}

				return false;
			}

			public function getToolsetRepeaterFieldValueNew($fieldname, $post_id, $fieldvalue = false){
				global $wpdb;

				$post_ids = $wpdb->get_row("SELECT group_id FROM ".$wpdb->prefix."toolset_connected_elements WHERE element_id = ".$post_id );				
				if(!empty($post_ids)) {
					$post_par_ids = $post_ids->group_id;

					switch($this->alltoolsetfields[$fieldname]['type']){	
					case 'textfield':
					case 'textarea':
					case 'image':
					case 'audio':
					case 'colorpicker':
					case 'image':
					case 'file':
					case 'embed':
					case 'email':
					case 'numeric':
					case 'phone':
					case 'skype':
					case 'url':
					case 'video':
					case 'wysiwyg':
					case 'checkbox':
						$child_ids = $wpdb->get_results("select child_id from {$wpdb->prefix}toolset_associations WHERE parent_id = '$post_par_ids'" );					

						$toolset_fieldvalue = '';
						foreach($child_ids as $child_id){	
							$post_child_id = $wpdb->get_row("SELECT element_id FROM ".$wpdb->prefix."toolset_connected_elements WHERE group_id = ".$child_id->child_id );
							$post_child_ids = $post_child_id->element_id;	
							$meta_value = get_post_meta($post_child_ids, 'wpcf-'.$fieldname, true);
							$toolset_fieldvalue.=$this->returnMetaValueAsCustomerInput($meta_value).'|';
						}
						$toolset_fieldvalue = ltrim($toolset_fieldvalue, '|');
						if(empty($toolset_fieldvalue)){
							return $fieldvalue;
						}
						return rtrim($toolset_fieldvalue, '|');
					case 'radio': 
					case 'select':
					case 'checkboxes':
						$toolset_fieldvalue = '';
						$child_ids = $wpdb->get_results("select child_id from {$wpdb->prefix}toolset_associations WHERE parent_id = '$post_par_ids'" );
						foreach($child_ids as $child_id){
							$post_child_id = $wpdb->get_row("SELECT element_id FROM ".$wpdb->prefix."toolset_connected_elements WHERE group_id = ".$child_id->child_id );
							$post_child_ids = $post_child_id->element_id;	
							$meta_value = get_post_meta($post_child_ids, 'wpcf-'.$fieldname, true);	
							$toolset_fieldvalue.=$this->returnMetaValueAsCustomerInput($meta_value, '', $this->alltoolsetfields[$fieldname]['type']).'|';
						}
						$toolset_fieldvalue = ltrim($toolset_fieldvalue, '|');
						if(empty($toolset_fieldvalue)){
							return $fieldvalue;
						}
						return rtrim($toolset_fieldvalue, '|');

					case 'date':
						$toolset_fieldvalue = '';
						$child_ids = $wpdb->get_results("select child_id from {$wpdb->prefix}toolset_associations WHERE parent_id = '$post_par_ids'" );
						foreach($child_ids as $child_id){
							$post_child_id = $wpdb->get_row("SELECT element_id FROM ".$wpdb->prefix."toolset_connected_elements WHERE group_id = ".$child_id->child_id );
							$post_child_ids = $post_child_id->element_id;	
							$meta_value = get_post_meta($post_child_ids, 'wpcf-'.$fieldname, true);
							if (is_numeric($meta_value)) {
								$meta_value = date('m/d/Y', intval($meta_value));
							} else {
								$meta_value = '';
							}
							$toolset_fieldvalue.=$this->returnMetaValueAsCustomerInput($meta_value).'|';
						}
						$toolset_fieldvalue = ltrim($toolset_fieldvalue, '|');
						if(empty($toolset_fieldvalue)){
							return $fieldvalue;
						}
						return rtrim($toolset_fieldvalue, '|');		
					}
				}

				return false;
			}

			public function getWoocommerceMetaValue($fieldname, $post_type, $post){
				$post_type=isset($post_type)?$post_type:'';

				if($post_type == 'shop_order_refund'){
					switch ($fieldname) {
					case 'REFUNDID':
						return $post['ID'];
					default:
						return $post[$fieldname];
					}
				}else if($post_type == 'shop_order'){
					switch ($fieldname) {
					case 'ORDERID':
						return $post['ID'];
					case 'order_status':
						return $post['post_status'];
					case 'customer_note':
						return $post['post_excerpt'];
					case 'order_date':
						return $post['post_date'];
					default:
						return $post[$fieldname];
					}
				}else if($post_type == 'shop_coupon'){
					switch ($fieldname) {
					case 'COUPONID':
						return $post['ID'];
					case 'coupon_status':
						return $post['post_status'];
					case 'description':
						return $post['post_excerpt'];
					case 'coupon_date':
						return $post['post_date'];
					case 'coupon_code':
						return $post['post_title'];
					case 'expiry_date':
						if(isset($post['date_expires'])){
							$timeinfo=date('m/d/Y',$post['date_expires']);
						}
						$timeinfo=isset($timeinfo)?$timeinfo:'';
						return $timeinfo;		
					default:
						return $post[$fieldname];
					}
				}else if($post_type == 'product_variation'){
					switch ($fieldname) {
					case 'VARIATIONID':
						return $post['ID'];
					case 'PRODUCTID':
						return $post['post_parent'];
					case 'VARIATIONSKU':
						return $post['sku'];
					default:
						return isset($post[$fieldname]) ? $post[$fieldname] : '';
					}
				}
				return false;
			}

			/**
			 * Create CSV data from array
			 * @param array $data       2D array with data
			 * @param array $fields     field names
			 * @param bool $append      if true, field names will not be output
			 * @param bool $is_php      if a php die() call should be put on the first
			 *                          line of the file, this is later ignored when read.
			 * @param null $delimiter   field delimiter to use
			 * @return string           CSV data (text string)
			 */
			public function unParse ( $data = array(), $fields = array(), $append = false , $is_php = false, $delimiter = null) {
				if ( !is_array($data) || empty($data) ) $data = &$this->data;
				if ( !is_array($fields) || empty($fields) ) $fields = &$this->titles;
				if ( $this->delimiter === null ) $this->delimiter = ',';

				$string = ( $is_php ) ? "<?php header('Status: 403'); die(' '); ?>".$this->linefeed : '' ;
				$entry = array();
				// create heading
				if ($this->offset == 0 || $this->checkSplit == 'true') {
					if ( $this->heading && !$append && !empty($fields) ) {
						foreach( $fields as $key => $value ) {
							$value = trim($value);

							$entry[] = $this->_enclose_value($value);

			}
			$string .= implode($this->delimiter, $entry).$this->linefeed;
			$entry = array();
			}
			}
			// Create data
			foreach( $data as $key => $row ) {
				foreach( $row as $field => $value ) {
					// Check if value is an array before trimming
					if (is_array($value)) {
						$value = implode(', ', $value); // Convert array to string
					} else {
						$value = trim($value);
					}
					$entry[] = $this->_enclose_value($value);
				}
				$string .= implode($this->delimiter, $entry) . $this->linefeed;
				$entry = array();
			}
			
			return $string;
			}

			/**
			 * Enclose values if needed
			 *  - only used by unParse()
			 * @param null $value
			 * @return mixed|null|string
			 */
			public function _enclose_value ($value = null) {
				if ($value !== null && $value != '') {
					$delimiter = preg_quote($this->delimiter, '/');
					$enclosure = preg_quote($this->enclosure, '/');
			
					// Convert array to string before processing
					if (is_array($value)) {
						$value = implode(', ', $value);
					}
			
					// Add a check to ensure $value is not an object
					if (isset($value) && is_string($value) && preg_match("/".$delimiter."|".$enclosure."|\n|\r/i", $value) ||
						(!is_object($value) && isset($value[0]) && ($value[0] == ' ' || isset($value) && substr($value, -1) == ' '))) {
						// Handle enclosure
						$value = str_replace($this->enclosure, $this->enclosure.$this->enclosure, $value);
						$value = $this->enclosure . $value . $this->enclosure;
					} else {
						if (is_string($value) || is_numeric($value)) {
							$value = $this->enclosure . $value . $this->enclosure;
						} else {
							$value = '';
						}
					}
				}
			
				return trim($value);
			}			

		/**
		 * Apply exclusion before export
		 * @param $headers  - Apply exclusion headers
		 * @return array    - Available headers after applying the exclusions
		 */
			public function applyEventExclusion($headers, $optionalType) {
				$header_exclusion = array();
				global $wpdb;
				$exclusion=$this->eventExclusions['exclusion_headers']['header'];

				foreach($exclusion as $exclusion_key=>$exclusion_value){
					if(strpos($exclusion_key, 'field_') !== false){
						$value=$exclusion_key;
						$get_acf_excerpt = $wpdb->get_var("SELECT post_excerpt FROM {$wpdb->prefix}posts WHERE post_name = '$value'  ");
						$exclusion[$get_acf_excerpt]=$exclusion_value;
					}
				}

				$this->eventExclusions['exclusion_headers']['header'] = $exclusion;
				$required_header = $this->eventExclusions['exclusion_headers']['header'];

				if ($optionalType == 'elementor_library') {
					$required_head = array();

					if (isset($required_header['ID'])) {
						$required_head['ID'] = $required_header['ID'];
					}
					if (isset($required_header['Template title'])) {
						$required_head['Template title'] = $required_header['Template title'];
					}
					if (isset($required_header['Template content'])) {
						$required_head['Template content'] = $required_header['Template content'];
					}
					if (isset($required_header['Style'])) {
						$required_head['Style'] = $required_header['Style'];
					}
					if (isset($required_header['Template type'])) {
						$required_head['Template type'] = $required_header['Template type'];
					}
					if (isset($required_header['Created time'])) {
						$required_head['Created time'] = $required_header['Created time'];
					}
					if (isset($required_header['Created by'])) {
						$required_head['Created by'] = $required_header['Created by'];
					}
					if (isset($required_header['Template status'])) {
						$required_head['Template status'] = $required_header['Template status'];
					}
					if (isset($required_header['Category'])) {
						$required_head['Category'] = $required_header['Category'];
					}		
					if (!empty($required_head)) {
						foreach ($headers as $hVal) {
							if (array_key_exists($hVal, $required_head)) {
								$header_exclusion[] = $hVal;
							}
						}
						return $header_exclusion;
					} else {
						return $headers;
					}
				} else {
					if (!empty($required_header)) {
						foreach ($headers as $hVal) {
							if (array_key_exists($hVal, $required_header)) {
								$header_exclusion[] = $hVal;
							}
						}
						return $header_exclusion;
					}
					else {
						return $headers;
					}
				}
			}

			public function replace_prefix_aioseop_from_fieldname($fieldname){
				if(preg_match('/_aioseop_/', $fieldname)){
					return preg_replace('/_aioseop_/', '', $fieldname);
				}

				return $fieldname;
			}
			public function getaioseoFieldValue($post_id){
				if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php'))
				{
					global $wpdb;
					$aioseo_slug =$wpdb->get_results("SELECT * FROM {$wpdb->prefix}aioseo_posts WHERE post_id='$post_id' ");
					return $aioseo_slug;
				}
			}
			public function replace_prefix_pods_from_fieldname($fieldname){
				if(preg_match('/_pods_/', $fieldname)){
					return preg_replace('/_pods_/', '', $fieldname);
				}
				return $fieldname;
			}

			public function replace_prefix_yoast_wpseo_from_fieldname($fieldname){

				if(preg_match('/_yoast_wpseo_/', $fieldname)){
					$fieldname = preg_replace('/_yoast_wpseo_/', '', $fieldname);

					if($fieldname == 'focuskw') {
						$fieldname = 'focus_keyword';
					}elseif($fieldname == 'bread-crumbs-title') { // It is comming as bctitle nowadays
						$fieldname = 'bctitle';
					}elseif($fieldname == 'metadesc') {
						$fieldname = 'meta_desc';
					}
				}

				return $fieldname;
			}

			public function replace_prefix_wpcf_from_fieldname($fieldname){
				if(preg_match('/_wpcf/', $fieldname)){
					return preg_replace('/_wpcf/', '', $fieldname);
				}
				return $fieldname;
			}

			public function replace_prefix_wpsc_from_fieldname($fieldname){
				if(preg_match('/_wpsc_/', $fieldname)){
					return preg_replace('/_wpsc_/', '', $fieldname);
				}
				return $fieldname;
			}

			public function replace_wpcr3_from_fieldname($fieldname){
				if(preg_match('/wpcr3_/', $fieldname)){
					$fieldname = preg_replace('/wpcr3_/', '', $fieldname);
				}
				return $fieldname;
			}

			public function change_fieldname_depends_on_post_type($post_type, $fieldname){
				if($post_type == 'wpcr3_review'){
					switch ($fieldname) {
					case 'ID':
						return 'review_id';
					case 'post_status':
						return 'status';
					case 'post_content':
						return 'review_text';
					case 'post_date':
						return 'date_time';
					default:
						return $fieldname;
					}
				}
				if($post_type == 'shop_order_refund'){
					switch ($fieldname) {
					case 'ID':
						return 'REFUNDID';
					default:
						return $fieldname;
					}
				}else if($post_type == 'shop_order'){
					switch ($fieldname) {
					case 'ID':
						return 'ORDERID';
					case 'post_status':
						return 'order_status';
					case 'post_excerpt':
						return 'customer_note';
					case 'post_date':
						return 'order_date';
					default:
						return $fieldname;
					}
				}else if($post_type == 'shop_coupon'){
					switch ($fieldname) {
					case 'ID':
						return 'COUPONID';
					case 'post_status':
						return 'coupon_status';
					case 'post_excerpt':
						return 'description';
					case 'post_date':
						return 'coupon_date';
					case 'post_title':
						return 'coupon_code';
					default:
						return $fieldname;
					}
				}else if($post_type == 'product_variation'){
					switch ($fieldname) {
					case 'ID':
						return 'VARIATIONID';
					case 'post_parent':
						return 'PRODUCTID';
					case 'sku':
						return 'VARIATIONSKU';
					default:
						return $fieldname;
					}
				}
				return $fieldname;
			}

			public function replace_underscore_from_fieldname($fieldname){
				if(preg_match('/_/', $fieldname)){
					$fieldname = preg_replace('/^_/', '', $fieldname);
				}
				return $fieldname;
			}

			public function fetchCategoryFieldValue($categories){
				global $wpdb;
				$bulk_category = [];
				foreach($categories as $category_id => $category){
					$term_meta = get_term_meta($category_id);
					$single_category = [];
					foreach($this->headers as $header){
						if($header == 'name'){
							global $sitepress;
							if($sitepress != null) {
								$single_category[$header] = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms where term_id=$category_id");
							}
							else{
								$cato[] = get_term($category_id);
								$single_category[$header] = $this->hierarchy_based_term_cat_name($cato, 'category');							
							}
							continue;
						}

						if(array_key_exists($header, $category)){							
							if(is_array($this->allacf) && array_key_exists($header, $this->allacf) && array_key_exists($this->allacf[$header],$category)){	//For acf group field
								$single_category[$header] = $category[$this->allacf[$header]];								
							}
							else 
								$single_category[$header] = $category[$header];
						}else{
							if(isset($term_meta[$header])){
								$single_category[$header] = $this->returnMetaValueAsCustomerInput($term_meta[$header]);
							}else{
								$single_category[$header] = null;
							}
						}
					}
					array_push($bulk_category, $single_category);
				}
				return $bulk_category;
			}
			public function getJetCCTValue($data, $type, $data_type = false){				
				global $wpdb;
				$jet_data = $this->JetEngineCCTFields($type);
				$darray_value=array();		
				$cct_rel = [];

				foreach ($data as $key => $dvalue) {
					$darray2 = array();
					$get_guid ='';
					$select_value='';
					$checkbox_key_value='';
					$checkbox_key_value1 ='';
					foreach($dvalue as $dkey=>$value){						
						if($dkey == '_ID'){
							$darray[$dkey] = $value;
						}
						elseif($dkey =='cct_status'){
							$darray[$dkey] = $value;
						}

						//JET CCT Relation
						if(!empty($jet_data)){
							if(in_array($dkey,$this->headers) && !array_key_exists($dkey,$jet_data['JECCT']) ){							
								$cct_rel[$key][$dkey] = $data[$key][$dkey];
							}

							if(array_key_exists($dkey,$jet_data['JECCT'])){		
								if(empty($value))					{
									$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
								}
								else {
									if($jet_data['JECCT'][$dkey]['type'] == 'text'){	
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
									}
									elseif($jet_data['JECCT'][$dkey]['type'] == 'textarea'){
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
									}
									elseif($jet_data['JECCT'][$dkey]['type'] == 'colorpicker'){
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
									}
									elseif($jet_data['JECCT'][$dkey]['type'] == 'iconpicker'){
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
									}
									elseif($jet_data['JECCT'][$dkey]['type'] == 'radio'){
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
									}
									elseif($jet_data['JECCT'][$dkey]['type'] == 'number'){
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
									}
									elseif($jet_data['JECCT'][$dkey]['type'] == 'wysiwyg'){
										$value = preg_replace('/\s+/', ' ', $value);

										// Minify the HTML content
										$value = trim($value);
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
									}
									elseif($jet_data['JECCT'][$dkey]['type'] == 'switcher'){
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
									}
									elseif($jet_data['JECCT'][$dkey]['type'] == 'time'){
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $value;
									} 
									elseif( $jet_data['JECCT'][$dkey]['type'] == 'media'){									
										if(is_numeric($value)){
											if($value != 0) {
												$get_guid_name = $wpdb->get_results("SELECT guid FROM {$wpdb->prefix}posts WHERE id = '$value'");									
												foreach($get_guid_name as $media_key=>$value){
													$darray1[$jet_data['JECCT'][$dkey]['name']]=$value->guid;
												}
											}
											else {

												$darray1[$jet_data['JECCT'][$dkey]['name']]=$value;									
											}
										}
										elseif(is_serialized($value)){
											$media_value=unserialize($value);
											$darray1[$jet_data['JECCT'][$dkey]['name']] = $media_value['url'];	
										}
										else{
											$media_field_val=$value;
											$darray1[$jet_data['JECCT'][$dkey]['name']]=$media_field_val;
										}								
									}
									elseif( $jet_data['JECCT'][$dkey]['type'] == 'gallery'){
										$get_meta_list = explode(',', $value);
										$get_guid ='';
										foreach($get_meta_list as $get_meta){	
											if(is_numeric($get_meta)){
												$get_guid_name = $wpdb->get_results("SELECT guid FROM {$wpdb->prefix}posts WHERE id = '$get_meta'");
												foreach($get_guid_name as $gallery_key=>$value){		
													$get_guid.=$value->guid.',';
												}
											}
											elseif(is_serialized($get_meta)){
												$gal_value=unserialize($get_meta);
												foreach($gal_value as $gal_key1=>$gal_val){
													$get_guid.=$gal_val['url'].',';
												}	
											}
											else{
												$get_guid .= $get_meta.',';
											}
										}
										$darray1[$jet_data['JECCT'][$dkey]['name']]=rtrim($get_guid,',');
									}						

									elseif($jet_data['JECCT'][$dkey]['type'] == 'date'){
										if(!empty($value)){
											if(strpos($value, '-') !== FALSE){
												$date_value= $value;
											}else{
												$date_value = date('Y-m-d', $value);
											}
										}
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $date_value;
									}

									elseif($jet_data['JECCT'][$dkey]['type'] == 'datetime-local'){
										if(!empty($value)){
											if(strpos($value, '-') !== FALSE){
												$datetime_value = $value;
											}else{
												$datetime_value = date('Y-m-d H:i', $value);
											}
											$datetime_value = str_replace(' ', 'T', $datetime_value);
										}
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $datetime_value;
									}

									elseif($jet_data['JECCT'][$dkey]['type'] == 'checkbox'){
										if($jet_data['JECCT'][$dkey]['is_array'] == 1){									
											$checkbox_value=unserialize($value);																		
											if (is_array($checkbox_value)) {
												$darray1[$jet_data['JECCT'][$dkey]['name']] = implode(',', $checkbox_value);
											} else {
												$darray1[$jet_data['JECCT'][$dkey]['name']] = ''; // or handle as needed
											}
										}
										else{
											$checkbox_value=unserialize($value);
											$checkbox_key_value='';
											foreach($checkbox_value as $check_key=>$check_val){
												if($check_val == 'true'){
													$checkbox_key_value.=$check_key.',';
												}
											}
											$darray1[$jet_data['JECCT'][$dkey]['name']] = rtrim($checkbox_key_value,',');
										}				
									}

									elseif($jet_data['JECCT'][$dkey]['type'] == 'posts'){
										if(is_serialized($value)){
											$jet_posts = unserialize($value);
											$jet_posts_value='';
											foreach($jet_posts as $posts_key=>$post_val){
												$query = "SELECT post_title FROM {$wpdb->prefix}posts WHERE id ='{$post_val}' AND post_status='publish'";
												$name = $wpdb->get_results($query);
												if (!empty($name)) {
													$jet_posts_value.=$name[0]->post_title.',';
												}
											}
											$post_names=rtrim($jet_posts_value,',');
										}
										else{
											$query = "SELECT post_title FROM {$wpdb->prefix}posts WHERE id ='{$value}' AND post_status='publish'";
											$name = $wpdb->get_results($query);
											if (!empty($name)) {
												$post_names=$name[0]->post_title;
											}
										}
										$darray1[$jet_data['JECCT'][$dkey]['name']] = $post_names;
									}
									elseif($jet_data['JECCT'][$dkey]['type'] == 'select'){
										if(is_serialized($value)){
											$select_value='';
											$gal_value=unserialize($value);
											foreach($gal_value as $select_key=>$gal_val){
												$select_value.=$gal_val.',';
											}	
										}
										else{
											$select_val=$value;
											$select_value=$select_val;
										}

										$darray1[$jet_data['JECCT'][$dkey]['name']] = rtrim($select_value,',');
									}

									else{
										if($jet_data['JECCT'][$dkey]['type'] == 'repeater'){
											$jet_rf_data = $this->JetEngineCCTRFFields($type);									
											$val=unserialize($value);
											$new_arr = array();																																																													
											foreach ($val as  $dvalue_key=>$dvalue) {																					
												foreach($dvalue  as $dkey => $dvalues){											
													$rep_array_values[]=$dkey;
													if(array_key_exists($dkey,$jet_rf_data['JECCTRF'])){																																												
														if( $jet_rf_data['JECCTRF'][$dkey]['type'] == 'media'){														
															$new_arr = array_column($val,$dkey);													
															if($jet_rf_data['JECCTRF'][$dkey]['value_format'] != 'url')	{														
																$img_url = array();
																foreach($new_arr as $img_data) {
																	if($jet_rf_data['JECCTRF'][$dkey]['value_format'] == 'both'){
																		$img_data = str_replace("\\",'',$img_data);
																		$img_data = json_decode($img_data);
																		$img_url[] = $img_data->url;
																	}
																	else{ // return format id
																		$img_url[] = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts WHERE id = '$img_data'");
																	}
																}	
																$darray2[$jet_rf_data['JECCTRF'][$dkey]['name']] = implode('|',$img_url);													
															}		
															else {
																$darray2[$jet_rf_data['JECCTRF'][$dkey]['name']] = implode('|',$new_arr);
															}																																	
														}
														elseif( $jet_rf_data['JECCTRF'][$dkey]['type'] == 'gallery'){													
															$new_arr = array_column($val,$dkey);														
															if($jet_rf_data['JECCTRF'][$dkey]['value_format'] != 'url'){														
																$gallery = array();														
																foreach($new_arr as $img_data) {
																	$img_url = array();															
																	if($jet_rf_data['JECCTRF'][$dkey]['value_format'] == 'both'){																
																		$img_data = str_replace("\\",'',$img_data);
																		$img_data = json_decode($img_data,true);

																		if(!empty($img_data)){																
																			foreach($img_data as $gdata){
																				$img_url[] = $gdata['url'];
																			}					
																		}
																		else {
																			$img_url[] = "";
																		}																																																																														
																		$gallery[] = implode(',',$img_url);																																																
																	}
																	else{ // return format id	
																		$gal_data = explode(',',$img_data);															
																		foreach($gal_data as $gal_id) {
																			$img_url[] = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts WHERE id = '$gal_id'");
																		}
																		$gallery[] = implode(',',$img_url);																
																	}
																}	
																$darray2[$jet_rf_data['JECCTRF'][$dkey]['name']] = implode('|',$gallery);													
															}		
															else {
																$darray2[$jet_rf_data['JECCTRF'][$dkey]['name']] = implode('|',$new_arr);
															}													
														}
														elseif($jet_rf_data['JECCTRF'][$dkey]['type'] == 'checkbox'){
															$new_arr = array_column($val,$dkey);													
															foreach($new_arr as $rkey => $cdata){
																$cval = array();
																foreach($cdata as $ckey => $cvalue){
																	if($cvalue == 'true'){
																		$cval[]=$ckey;
																	}
																}
																$new_arr[$rkey] = implode(',',$cval);
															}													
															$darray2[$jet_rf_data['JECCTRF'][$dkey]['name']] = implode('|',$new_arr);												
														}
														elseif($jet_rf_data['JECCTRF'][$dkey]['type'] == 'posts'){	
															$new_arr = array_column($val,$dkey);														
															if(is_array($dvalues)){		
																foreach($new_arr as $rkey => $pdata){
																	$post_title = array();	
																	foreach($pdata as $id)	{
																		$post_title[] = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE id ='{$id}' AND post_status='publish'");
																	}																		
																	$post_data[$rkey] = implode(',',$post_title);
																}																																																	

															}
															else{
																$post_data = array();
																foreach($new_arr as $id) {																
																	$post_data[] = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE id ='{$id}' AND post_status='publish'");
																}

															}
															$darray2[$jet_rf_data['JECCTRF'][$dkey]['name']] = implode('|',$post_data);
														}																								
														elseif($jet_rf_data['JECCTRF'][$dkey]['type'] == 'select'){
															if(is_array($dvalues)){
																$new_arr = array_column($val,$dkey);													
																foreach($new_arr as $rkey => $cdata){														
																	$new_arr[$rkey] = implode(',',$cdata);
																}													
																$darray2[$jet_rf_data['JECCTRF'][$dkey]['name']] = implode('|',$new_arr);
															}
															else{
																$new_arr = array_column($val,$dkey);																																					 
																$darray2[$jet_rf_data['JECCTRF'][$dkey]['name']] = implode('|',$new_arr);
															}
														}	
														else {
															$new_arr = array_column($val,$dkey);																																					 
															$darray2[$jet_rf_data['JECCTRF'][$dkey]['name']] = implode('|',$new_arr);
														}	

													}


												}										
												break;				//End repeater field loop																
											}																																			
										}
									}  
								}
							}

						}											
					}																
					if(!empty($darray1) && empty($darray2)){
						$data_array_values=array_merge($darray,$darray1);
					}
					elseif(empty($darray1) && !empty($darray2)){
						$data_array_values=array_merge($darray,$darray2);
					}
					else if (!empty($darray1) && !empty($darray2)){
						$data_array_values=array_merge($darray,$darray1,$darray2);
					}
					$darray_value[$key]=$data_array_values;												
				}		

				//CCT Relation
				if(!empty($cct_rel) && !empty($darray_value)){
					foreach($cct_rel as $id => $value){
						unset($value['_ID']);
						unset($value['cct_status']);
						$get_val = $darray_value[$id];							
						$all_data[$id] = array_merge($get_val,$value);
					}	
					$darray_value = $all_data;
				}		
				//End CCT Relation		

				//For correct the CSV columns 					
				foreach($this->headers as $row_header){
					foreach($darray_value as $key => $value){
						if(!empty($value)){
							if(array_key_exists($row_header,$value)){
								$new_data[$key][$row_header] = $value[$row_header];
							}
							else {
								$new_data[$key][$row_header] = $value[$row_header];
							}
						}
					} 
				}
				$darray_value = $new_data;


				//added
				if(!empty($darray_value)){	
					return $darray_value;
				}
				else{
					return ;
				}	
			}

			public function JetEngineCCTRFFields($type){
				global $wpdb;	
				$jet_rf_field = array();
				if(is_plugin_active('jet-engine/jet-engine.php')){
					$get_meta_fields = $wpdb->get_results($wpdb->prepare("select id, meta_fields from {$wpdb->prefix}jet_post_types where slug = %s and status = %s", $type, 'content-type'));
					$unserialized_meta = maybe_unserialize($get_meta_fields[0]->meta_fields);

					foreach($unserialized_meta as $jet_key => $jet_value){
						if($jet_value['type'] == 'repeater'){
							$customFields["JECCT"][ $jet_value['name']]['name']  = $jet_value['name'];
							$fields=$jet_value['repeater-fields'];
							foreach($fields as $rep_fieldkey => $rep_fieldvalue){
								$customFields["JECCTRF"][ $rep_fieldvalue['name']]['label'] = $rep_fieldvalue['title'];
								$customFields["JECCTRF"][ $rep_fieldvalue['name']]['name']  = $rep_fieldvalue['name'];
								$customFields["JECCTRF"][ $rep_fieldvalue['name']]['type']  = $rep_fieldvalue['type'];
								$customFields["JECCTRF"][ $rep_fieldvalue['name']]['options']  = isset($rep_fieldvalue['options']) ? $rep_fieldvalue['options'] : '';
								$customFields["JECCTRF"][ $rep_fieldvalue['name']]['is_multiple']  = isset($rep_fieldvalue['is_multiple']) ? $rep_fieldvalue['is_multiple'] : '';
								$customFields["JECCTRF"][ $rep_fieldvalue['name']]['is_array']  = isset($rep_fieldvalue['is_array']) ? $rep_fieldvalue['is_array'] : '';
								$customFields["JECCTRF"][ $rep_fieldvalue['name']]['value_format'] = isset($rep_fieldvalue['value_format']) ? $rep_fieldvalue['value_format'] : '';
								$jet_rf_field[] = $rep_fieldvalue['name'];

							}
						}
					}
					return $customFields;
				}	
			}
			public function JetEngineCCTFields($type){
				global $wpdb;	
				$jet_field = array();
				$customFields = [];
				$get_meta_fields = $wpdb->get_results($wpdb->prepare("select id, meta_fields from {$wpdb->prefix}jet_post_types where slug = %s and status = %s", $type, 'content-type'));

				if(!empty($get_meta_fields)){
					$unserialized_meta = maybe_unserialize($get_meta_fields[0]->meta_fields);

					foreach($unserialized_meta as $jet_key => $jet_value){
						$customFields["JECCT"][ $jet_value['name']]['label'] = $jet_value['title'];
						$customFields["JECCT"][ $jet_value['name']]['name']  = $jet_value['name'];
						$customFields["JECCT"][ $jet_value['name']]['type']  = $jet_value['type'];
						$customFields["JECCT"][ $jet_value['name']]['options'] = isset($jet_value['options']) ? $jet_value['options'] : '';
						$customFields["JECCT"][ $jet_value['name']]['is_multiple'] = isset($jet_value['is_multiple']) ? $jet_value['is_multiple'] : '';
						$customFields["JECCT"][ $jet_value['name']]['is_array'] = isset($jet_value['is_array']) ? $jet_value['is_array'] : '';
						$jet_field[] = $jet_value['name'];
					}
				}
				return $customFields;	
			}


			public function returnMetaValueAsCustomerInput($meta_value, $header = false, $data_type = false) {
				if ($header == 'rating_data') {
					return $meta_value; 
				}
				if ($header != 'jet_abaf_price' && $header != 'jet_abaf_custom_schedule' && $header != 'jet_abaf_configuration' &&
					$header != '_elementor_css' && $header != '_elementor_controls_usage' && $header != 'elementor_library_category' &&
					$header != '_elementor_page_assets' && $header != '_elementor_page_settings' && $header != '_elementor_data') {
			
					if (is_array($meta_value)) {
						if ($data_type == 'checkboxes') {
							$metas_value = '';
							foreach ($meta_value as $key => $meta_values) {
								$meta_value = $meta_values[0];
								if (!empty($meta_value)) {
									$metas_value .= $meta_value . ',';
								}
							}
							return rtrim($metas_value, ',');
						}
						
						$meta_value[0] = isset($meta_value[0]) ? $meta_value[0] : '';
						if (!empty($meta_value[0])) {
							if (is_serialized($meta_value[0])) {
								return unserialize($meta_value[0]);
							} elseif (is_array($meta_value[0])) {
								return implode('|', $meta_value[0]);
							} elseif (is_string($meta_value[0])) {
								return $meta_value[0];
							} elseif ($this->isJSON($meta_value[0])) {
								return json_decode($meta_value[0]);
							}
						}
						return $meta_value[0];
					} else {
						if (is_serialized($meta_value)) {
							$meta_value = unserialize($meta_value);
							
							if (is_array($meta_value)) {
								return implode('|', $meta_value);
							}
						} elseif (is_array($meta_value)) {
							return implode('|', $meta_value);
						} elseif (is_string($meta_value)) {
							if (strpos($meta_value, '[{"keyword":') !== false) {
								$decode_value = json_decode($meta_value, true);
								$keywords = array_column($decode_value, 'keyword');
								return implode('|', $keywords);
							} elseif (strpos($meta_value, '["",') !== false) {
								$decode_value1 = json_decode($meta_value, true);
								if (is_array($decode_value1)) {
									array_shift($decode_value1);
									return implode('|', $decode_value1);
								}
							} else {
								return rtrim($meta_value, '|');
							}
						} elseif ($this->isJSON($meta_value)) {
							return json_decode($meta_value);
						}
					}
				} elseif ($header == '_elementor_data') {
					$meta_value = base64_encode($meta_value);
				}
				return $meta_value;
			}
			

			public function isJSON($meta_value) {
				$json = json_decode($meta_value);
				return $json && $meta_value != $json;
			}

			public function hierarchy_based_term_name($term, $taxanomy_type){

				$tempo = array();
				$termo = '';
				$i=0;
				if(!empty($term))	{
					foreach($term as $termkey => $terms){
						$tempo[] = $terms->name;
						$temp_hierarchy_terms = [];

						if(!empty($terms->parent)){
							$temp1 = $terms->name;
							$i++;												

							$temp_hierarchy_terms[] = $terms->name;
							$hierarchy_terms = $this->call_back_to_get_parent($terms->parent, $taxanomy_type, $tempo, $temp_hierarchy_terms);
							$parent_name=get_term($terms->parent);

							if( $terms->taxonomy == $taxanomy_type){
								$termo .= $this->split_terms_by_arrow($hierarchy_terms,$parent_name->name, 'group').',';
							}
						}else{											    
							if(in_array($terms->name,$tempo)){
								if( $terms->taxonomy == $taxanomy_type){
									$termo .= $terms->name.',';
								}
							}
						}
					}
				}
				return $termo;	

			}

			public function hierarchy_based_term_cat_name($term, $taxanomy_type){
				$tempo = array();
				$termo = '';
				foreach($term as $terms){
					$tempo[] = $terms->name;
					$temp_hierarchy_terms = [];
					if(!empty($terms->parent)){
						$temp_hierarchy_terms[] = $terms->name;
						$hierarchy_terms = $this->call_back_to_get_parent($terms->parent, $taxanomy_type, $tempo, $temp_hierarchy_terms);
						$parent_name=get_term($terms->parent);

						$termo = $this->split_terms_by_arrow($hierarchy_terms, $parent_name->name, 'separate');

					}else{
						$termo = $terms->name;

					}
				}
				return $termo;
			}
			public function call_back_to_get_parent($term_id, $taxanomy_type,$tempo, $temp_hierarchy_terms = []){
				$term = get_term($term_id, $taxanomy_type);
				if(!empty($term->parent)){
					if(in_array($term->name,$tempo)){

						$temp_hierarchy_terms[] = $term->name;

						$temp_hierarchy_terms = $this->call_back_to_get_parent($term->parent, $taxanomy_type,$tempo, $temp_hierarchy_terms);
					}
					else{
						$temp_hierarchy_terms[] = '';

						$temp_hierarchy_terms = $this->call_back_to_get_parent($term->parent, $taxanomy_type,$tempo, $temp_hierarchy_terms);
					}

				}else{					
					if(isset($term) && !empty($term) && in_array($term->name,$tempo)){
						$temp_hierarchy_terms[] = $term->name;
					}
					else{
						$temp_hierarchy_terms[] = '';
					}
				}
				return $temp_hierarchy_terms;
			}			
			// public function split_terms_by_arrow($hierarchy_terms, $termParentName, $type){
			// 	krsort($hierarchy_terms);							
			// 	if($type == 'separate'){
			// 		$terms_value = $termParentName.'>'.$hierarchy_terms[0];
			// 	}

			// 	//changed
			// 	elseif($type == 'group'){
			// 		$terms_value = $hierarchy_terms[0];
			// 	}

			// 	return $terms_value;
			// }

			public function split_terms_by_arrow($hierarchy_terms, $termParentName,$type)
			{
		
				krsort($hierarchy_terms);
				$terms_value = $termParentName . '>' . $hierarchy_terms[0];
				return $terms_value;
			}

			public function getToolSetRelationshipValue($post_id){				
				$plugins = get_plugins();
				$plugin_version = $plugins['types/wpcf.php']['Version'];
				if($plugin_version < '3.4.1'){
					global $wpdb;
					$toolset_relation_values['relationship_slug']='';
					$toolset_intermadiate_values['types_relationship']='';

					$toolset_fieldvalues = array();
					$get_slug = "SELECT distinct relationship_id FROM {$wpdb->prefix}toolset_associations WHERE parent_id ='{$post_id}'";
					$relat_slug = $wpdb->get_results($get_slug,ARRAY_A);
					$get_slug1 = "SELECT distinct relationship_id FROM {$wpdb->prefix}toolset_associations WHERE child_id ='{$post_id}'";
					$relat_slug1 = $wpdb->get_results($get_slug1,ARRAY_A);
					$rel_slug = (object) array_merge( (array) $relat_slug, (array) $relat_slug1); 	
					foreach($rel_slug as $relkey=>$relvalue)
					{	
						$relationship_id = $relvalue['relationship_id'];
						if(!empty($relationship_id)){
							$slug_id="SELECT slug FROM {$wpdb->prefix}toolset_relationships WHERE id IN ($relationship_id) AND origin = 'wizard' ";
							$relationship=$wpdb->get_results($slug_id,ARRAY_A);
						}

						if(is_array($relationship)){
							foreach($relationship as $keys=>$values) {
								$toolset_relation_values['relationship_slug'] .= $values['slug'] . '|';
							}	
						}
						$relationships_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}toolset_relationships WHERE id = $relationship_id AND origin = 'wizard' ");						
						$parents_post = "SELECT post_title FROM {$wpdb->prefix}toolset_associations INNER JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}toolset_associations.child_id WHERE {$wpdb->prefix}toolset_associations.parent_id='$post_id' AND {$wpdb->prefix}toolset_associations.relationship_id='$relationships_id' AND post_status = 'publish'";
						$parent_title1 = $wpdb->get_results($parents_post,ARRAY_A);						
						$parents_post1 = "SELECT post_title FROM {$wpdb->prefix}toolset_associations INNER JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}toolset_associations.parent_id WHERE {$wpdb->prefix}toolset_associations.child_id='$post_id' AND {$wpdb->prefix}toolset_associations.relationship_id='$relationships_id' AND post_status = 'publish'";
						$parent_title2 = $wpdb->get_results($parents_post1,ARRAY_A);

						$parent_title = array_merge($parent_title1, $parent_title2);

						$parent_value = '';
						for($i = 0 ; $i<count($parent_title) ; $i++){
							$parent_value .= $parent_title[$i]['post_title'] . ",";
						}
						$parent_value = rtrim($parent_value , ",");
						$toolset_intermadiate_values['types_relationship'] .= $parent_value . "|";

					}	
					if(is_array($toolset_relation_values)){
						foreach($toolset_relation_values as $relation_value){
							$toolset_fieldvalues['relationship_slug'] = rtrim($relation_value , "|");
						}
					}	
					foreach($toolset_intermadiate_values as $types_value){
						$types_value = ltrim($types_value , "|");
						$toolset_fieldvalues['types_relationship'] = rtrim($types_value , "|");
					}
					return $toolset_fieldvalues;
				}
				else{
					global $wpdb;
					$toolset_relation_values = array();
					$toolset_intermadiate_values = array();
					$toolset_fieldvalues = array();

					$get_con_slug = "SELECT group_id FROM {$wpdb->prefix}toolset_connected_elements WHERE element_id ='{$post_id}'";
					$relat_con_slug = $wpdb->get_results($get_con_slug,ARRAY_A);
					$relat_con_slug[0]['group_id'] = isset($relat_con_slug[0]['group_id']) ? $relat_con_slug[0]['group_id'] : '';
					$con_id =$relat_con_slug[0]['group_id'];

					$get_slug = "SELECT distinct relationship_id FROM {$wpdb->prefix}toolset_associations WHERE parent_id ='{$con_id}'";
					$relat_slug = $wpdb->get_results($get_slug,ARRAY_A);


					$get_slug1 = "SELECT distinct relationship_id FROM {$wpdb->prefix}toolset_associations WHERE child_id ='{$con_id}'";
					$relat_slug1 = $wpdb->get_results($get_slug1,ARRAY_A);
					$parent_value2 = '';

					$rel_slug = (object) array_merge( (array) $relat_slug, (array) $relat_slug1); 

					foreach($rel_slug as $relkey=>$relvalue)
					{

						$relationship_id = $relvalue['relationship_id'];

						if(!empty($relationship_id)){
							$slug_id="SELECT slug FROM {$wpdb->prefix}toolset_relationships WHERE id IN ($relationship_id) AND origin = 'wizard' ";
							$relationship=$wpdb->get_results($slug_id,ARRAY_A);


						}

						if(is_array($relationship)){
							foreach($relationship as $keys=>$values) {
								$toolset_relation_values['relationship_slug'] .= $values['slug'] . '|';
							}	
						}

						$relationships_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}toolset_relationships WHERE id = $relationship_id AND origin = 'wizard' ");

						$get_child_slug = "SELECT distinct child_id FROM {$wpdb->prefix}toolset_associations WHERE parent_id ='{$con_id}' and relationship_id ='{$relationships_id}'";
						$relat_child_slug = $wpdb->get_results($get_child_slug,ARRAY_A);

						$parent_value1 = '';

						if($relat_child_slug){
							foreach($relat_child_slug as $chiildkey => $childvalue){
								$childconid = $childvalue['child_id'];

								$get_child_slug1 = "SELECT distinct element_id FROM {$wpdb->prefix}toolset_connected_elements WHERE group_id ='{$childconid}'";
								$relat_child_slug1 = $wpdb->get_results($get_child_slug1,ARRAY_A);
								$childid = $relat_child_slug1[0]['element_id'];

								$parents_post = "SELECT post_title FROM {$wpdb->prefix}posts WHERE ID ={$childid} AND post_status = 'publish'";
								$parent_title1 = $wpdb->get_results($parents_post,ARRAY_A);
								if(!empty($parent_title1[0]['post_title'])){
									$parent_value1 .= $parent_title1[0]['post_title'] . ",";
								}

							}
							$parent_value = rtrim($parent_value1 , ",");

						}



						$get_par_slug = "SELECT distinct parent_id FROM {$wpdb->prefix}toolset_associations WHERE child_id ='{$con_id}' and relationship_id ='{$relationships_id}'";
						$relat_par_slug = $wpdb->get_results($get_par_slug,ARRAY_A);
						$parent_value2 = '';

						if($relat_par_slug){

							foreach($relat_par_slug as $chiildkey => $childvalue){
								$childconid = $childvalue['parent_id'];

								$get_child_slug1 = "SELECT distinct element_id FROM {$wpdb->prefix}toolset_connected_elements WHERE group_id ='{$childconid}'";
								$relat_child_slug1 = $wpdb->get_results($get_child_slug1,ARRAY_A);
								$childid = $relat_child_slug1[0]['element_id'];

								$parents_post = "SELECT post_title FROM {$wpdb->prefix}posts WHERE ID ={$childid} AND post_status = 'publish'";
								$parent_title1 = $wpdb->get_results($parents_post,ARRAY_A);
								if(!empty($parent_title1[0]['post_title'])){
									$parent_value2 .= $parent_title1[0]['post_title'] . ",";
								}



							}
							$parent_value = rtrim($parent_value2 , ",");

						}

						$toolset_intermadiate_values['types_relationship'] .= $parent_value . "|";

					}
					if(is_array($toolset_relation_values)){
						foreach($toolset_relation_values as $relation_value){
							$toolset_fieldvalues['relationship_slug'] = rtrim($relation_value , "|");

						}
					}	
					foreach($toolset_intermadiate_values as $types_value){
						$types_value = ltrim($types_value , "|");
						$toolset_fieldvalues['types_relationship'] = rtrim($types_value , "|");
					}

					return $toolset_fieldvalues;

				}


			}

			public function getToolSetIntermediateFieldValue($post_id){
				global $wpdb;
				//	include_once( 'wp-admin/includes/plugin.php' );
				//include_once( 'wp-admin/includes/plugin.php' );
				$plugins = get_plugins();
				$plugin_version = $plugins['types/wpcf.php']['Version'];
				if($plugin_version < '3.4.1'){
					$toolset_fieldvalues = [];
					$intermediate_rel=$wpdb->get_var("select relationship_id from {$wpdb->prefix}toolset_associations where intermediary_id ='{$post_id}'");
					if(!empty($intermediate_rel)){
						$intermediate_slug=$wpdb->get_var("select slug from {$wpdb->prefix}toolset_relationships where  id IN ($intermediate_rel)");
					}
					$intern_rel=$intern_relationship=$rel_intermediate=$related_posts= $related_title='';

					if(!empty($intermediate_slug)){
						$toolset_fieldvalues['relationship_slug'] = $intermediate_slug;
						$intermediate_post = "select parent_id,child_id,post_title from {$wpdb->prefix}toolset_associations INNER JOIN {$wpdb->prefix}posts on {$wpdb->prefix}posts.ID = {$wpdb->prefix}toolset_associations.child_id WHERE {$wpdb->prefix}toolset_associations.intermediary_id='{$post_id}' AND post_status = 'publish'";

						$related_ids = $wpdb->get_results($intermediate_post,ARRAY_A);

						foreach($related_ids as $keyd=>$valued)
						{
							$parent_id = $valued['parent_id'];
							$child_id = $valued['child_id'];
							if(!empty($parent_id)){
								$related_posts = $wpdb->get_var("select post_title from {$wpdb->prefix}posts where ID = $parent_id AND post_status = 'publish'");
							}
							if(!empty($child_id)){
								$related_title = $wpdb->get_var("select post_title from {$wpdb->prefix}posts where ID = $child_id AND post_status = 'publish'");
							}
							$rel_intermediate .= $related_posts.','.$related_title;
							$intern_rel =  $rel_intermediate;
							$intern_relationship=rtrim($intern_rel,"| ");   
							$toolset_fieldvalues['intermediate']= $intern_relationship;
						}
					}
				}
				else{
					global $wpdb;
					$toolset_fieldvalues = [];

					$get_con_slug = "SELECT group_id FROM {$wpdb->prefix}toolset_connected_elements WHERE element_id ='{$post_id}'";
					$relat_con_slug = $wpdb->get_results($get_con_slug,ARRAY_A);
					$relat_con_slug = isset($relat_con_slug) ?$relat_con_slug : '';
					if(!empty($relat_con_slug)){
						$con_id =$relat_con_slug[0]['group_id'];
					}


					if(!empty($con_id)){
						$intermediate_rel=$wpdb->get_var("select relationship_id from {$wpdb->prefix}toolset_associations where intermediary_id ='{$con_id}'");
					}

					if(!empty($intermediate_rel)){
						$intermediate_slug=$wpdb->get_var("select slug from {$wpdb->prefix}toolset_relationships where  id IN ($intermediate_rel)");
					}
					$intern_rel=$intern_relationship=$rel_intermediate=$related_posts= $related_title='';

					if(!empty($intermediate_slug)){
						$toolset_fieldvalues['relationship_slug'] = $intermediate_slug;
						$intermediate_post = "select parent_id,child_id from {$wpdb->prefix}toolset_associations where intermediary_id='{$con_id}' and relationship_id = '{$intermediate_rel}'";

						$related_ids = $wpdb->get_results($intermediate_post,ARRAY_A);

						foreach($related_ids as $keyd=>$valued)
						{
							$parent_con_id = $valued['parent_id'];
							$child_con_id = $valued['child_id'];
							$get_par_con = "SELECT element_id FROM {$wpdb->prefix}toolset_connected_elements WHERE id ='{$parent_con_id}'";
							$relat_par_con = $wpdb->get_results($get_par_con,ARRAY_A);
							$parent_id =$relat_par_con[0]['element_id'];
							$get_child_con = "SELECT element_id FROM {$wpdb->prefix}toolset_connected_elements WHERE id ='{$child_con_id}'";
							$relat_child_con = $wpdb->get_results($get_child_con,ARRAY_A);
							$child_id =$relat_child_con[0]['element_id'];
							if(!empty($parent_id)){
								$related_posts = $wpdb->get_var("select post_title from {$wpdb->prefix}posts where ID = $parent_id AND post_status = 'publish'");
							}
							if(!empty($child_id)){
								$related_title = $wpdb->get_var("select post_title from {$wpdb->prefix}posts where ID = $child_id AND post_status = 'publish'");
							}
							$rel_intermediate .= $related_posts.','.$related_title;
							$intern_rel =  $rel_intermediate;
							$intern_relationship=rtrim($intern_rel,"| ");   
							$toolset_fieldvalues['intermediate']= $intern_relationship;
						}
					}
					return $toolset_fieldvalues;
				}

			}

		}


		return new exportExtension();
	}
