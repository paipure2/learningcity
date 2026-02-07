<?php
/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;
use Smackcoders\WCSV\WC_Order;

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

class CoreFieldsImport {
	private static $core_instance = null,$media_instance,$nextgen_instance,$mappingInstance;
	public $detailed_log;
	public $generated_content;
	public $openAI_response=array();
	public static $failed_images_instance;
	public $media_log,$failed_media_data;
	public static function getInstance() {
		if (CoreFieldsImport::$core_instance == null) {
			CoreFieldsImport::$core_instance = new CoreFieldsImport;
			CoreFieldsImport::$failed_images_instance = new FailedImagesUpdate();
			CoreFieldsImport::$media_instance = new MediaHandling;
			CoreFieldsImport::$mappingInstance = new MappingExtension;
			CoreFieldsImport::$nextgen_instance = new NextGenGalleryImport;
			return CoreFieldsImport::$core_instance;
		}
		return CoreFieldsImport::$core_instance;
	}

	function set_core_values($header_array ,$value_array , $map , $type , $mode , $line_number , $unmatched_row, $check , $hash_key,$acf,$metabox ,$pods, $toolset, $jetengine, $update_based_on, $gmode, $variation_count, $wpml_array = null,$templatekey = null,$poly_array=null,$order_meta=null,$media_meta=null,$media_type = null,$attr_data=null,$woocom_image=null,$post_cat_list=null,$isCategory=null,$categoryList=null){	
		global $sitepress;
		global $wpdb;
		$get_result = "";
		$post_id = null;
		$helpers_instance = ImportHelpers::getInstance();
		CoreFieldsImport::$media_instance->header_array = $header_array;
		CoreFieldsImport::$media_instance->value_array = $value_array;
		$log_table_name = $wpdb->prefix ."import_detail_log";
		$media_handle = get_option('smack_image_options');

		$unikey_name = 'hash_key';
		$unikey_value = $hash_key;

		if($gmode == 'CLI'){ //Exchange the hashkey value with template key
			$unikey_name = 'templatekey';
			$unikey_value = ($templatekey != null) ? $templatekey : '';
		}	

		$updated_row_counts = $helpers_instance->update_count($unikey_value,$unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];
		$taxonomies = get_taxonomies();
			
		if (in_array($type, $taxonomies)) {
			$get_import_type = 'term';
		}elseif ($type == 'Users') {
			$get_import_type = 'user';
		}elseif ($type == 'Comments') {
			$get_import_type = 'comment';
		} else {	
			$get_import_type = 'post';
		}
		if (in_array($type, $taxonomies)) {
			$import_type = $type;
			if($import_type == 'category' || $import_type == 'product_category' || $import_type == 'product_cat' || $import_type == 'wpsc_product_category' || $import_type == 'event-categories'):
				$type = 'Categories';
			elseif($import_type == 'product_tag' || $import_type == 'event-tags' || $import_type == 'post_tag'):
				$type = 'Tags';
		else:
			$type = 'Taxonomies';
			endif;
		}
		if($type == 'elementor_library'){
			$elementor_import=new ElementorImport;
			$elementor_import->set_elementor_values($header_array ,$value_array , $map, $post_id , $type, $mode, $line_number , $hash_key);
			wp_die();

		}
		if($type == 'GFEntries'){
			
			$res = $this->gfFormImport($header_array ,$value_array , $map, $post_id , $type, $mode, $line_number , $hash_key);
			//wp_die();
			return $res['ID'];

		}
		if(!empty($media_meta)){
			if (isset($media_handle['media_settings'])) {
				$post_values = $helpers_instance->get_header_values($media_meta , $header_array , $value_array,$hash_key);
				$media_handle['media_settings']['title'] = isset($post_values['featured_image_title']) ? $post_values['featured_image_title'] : '';
    			$media_handle['media_settings']['caption'] = isset($post_values['featured_image_caption']) ? $post_values['featured_image_caption'] : '';
    			$media_handle['media_settings']['alttext'] = isset($post_values['featured_image_alt_text']) ? $post_values['featured_image_alt_text'] : '';
    			$media_handle['media_settings']['description'] = isset($post_values['featured_image_description']) ? $post_values['featured_image_description'] : '';
				$media_handle['media_settings']['file_name'] = isset($post_values['featured_file_name']) ? $post_values['featured_file_name'] : '';
				update_option('smack_image_options', $media_handle);
				$image_meta = get_option('smack_image_options');
			}
		}
		if(($type == 'WooCommerce Product Variations' ) || ($type == 'WooCommerce Customer') || ($type == 'WooCommerce Reviews') || ($type == 'JetReviews')|| ($type == 'WooCommerce Orders') || ($type == 'WooCommerce Coupons') || ($type == 'WooCommerce Refunds') || ($type == 'WooCommerce Attributes') || ($type == 'WooCommerce Tags') || ($type == 'WooCommerce Product') || ($type == 'Categories') || ($type == 'Tags') || ($type == 'Taxonomies') || ($type == 'JetBooking') || ($type == 'Comments') || ($type == 'Users') || ($type == 'Customer Reviews') || ($type == 'WPeCommerce Products') || ($type == 'WPeCommerce Coupons') || ($type == 'lp_order')  || ($type == 'nav_menu_item') || ($type == 'widgets') || ($type == 'Media')){  
			$woocommerce_core_instance = WooCommerceCoreImport::getInstance();
			$jet_booking_instance = JetBookingImport::getInstance();
			$jet_reviews_instance = JetReviewsImport::getInstance();
			$taxonomies_instance = TaxonomiesImport::getInstance();
			$users_instance = UsersImport::getInstance();
			$comments_instance = CommentsImport::getInstance();
			$wpecommerce_instance = WPeCommerceImport::getInstance();
			$customer_reviews_instance = CustomerReviewsImport::getInstance();
			$learnpress_instance = LearnPressImport::getInstance();
			$media_core_instance = MediaImport::getInstance();
			$post_values = [];
			$post_values = $helpers_instance->get_header_values($map , $header_array , $value_array,$hash_key);
			$wpml_values = $helpers_instance->get_header_values($wpml_array , $header_array , $value_array,$hash_key);
			$poly_values = $helpers_instance->get_header_values($poly_array,$header_array,$value_array,$hash_key);
			$image_meta = $helpers_instance->get_meta_values($woocom_image , $header_array , $value_array,$hash_key);
			if($type == 'WooCommerce Product'){
				$product_meta_data = $helpers_instance->get_header_values($order_meta,$header_array,$value_array);

			//	$attr_meta_data = $helpers_instance->get_header_values($attr_data,$header_array,$value_array);
				$result = $woocommerce_core_instance->woocommerce_product_import_new($post_values , $mode , $type, $unmatched_row, $check , $unikey_value , $unikey_name, $line_number, $acf ,$metabox,$pods, $toolset,$jetengine,$header_array, $value_array,  $wpml_values,$poly_values,$update_based_on,$product_meta_data,$attr_data,$image_meta,$post_cat_list,$isCategory,$categoryList);			

			//	$result = $woocommerce_core_instance->woocommerce_product_import($post_values , $mode , $type, $unmatched_row, $check , $unikey_value , $unikey_name, $line_number, $acf ,$pods, $toolset,$header_array, $value_array,  $wpml_values,$poly_values,$update_based_on);			
			}
			if($type == 'JetBooking'){
				if(isset($order_meta)){
					$order_meta = $helpers_instance->get_header_values($order_meta,$header_array,$value_array);
				}
				$result = $jet_booking_instance->jet_booking_import($post_values , $type, $mode ,$unikey_value , $unikey_name, $line_number,$update_based_on,$check,$hash_key,$order_meta);
			}
			if($type == 'Media'){
				$media_type = ucfirst($media_type);
				$result = $media_core_instance->media_fields_import($post_values , $mode , $type , $media_type ,$unikey_value ,$unikey_name, $line_number,$hash_key,$header_array ,$value_array);
			}
			if($type == 'WooCommerce Orders'){
				// $result = $woocommerce_core_instance->woocommerce_orders_import($post_values , $mode , $check , $unikey_value ,$unikey_name, $line_number);
				$order_meta_data = $helpers_instance->get_header_values($order_meta,$header_array,$value_array);
				$result = $woocommerce_core_instance->woocommerce_orders_new_import($post_values , $mode , $check , $unikey_value ,$unikey_name, $line_number,$order_meta_data,$update_based_on);
			}
			if($type == 'WooCommerce Product Variations'){
				$result = $woocommerce_core_instance->woocommerce_variations_import($post_values , $mode , $check ,$unikey_value ,  $unikey_name, $line_number, $variation_count,$update_based_on);
			}
			if($type == 'WooCommerce Coupons'){
				$result = $woocommerce_core_instance->woocommerce_coupons_import($post_values , $mode , $check , $unikey_value , $unikey_name, $line_number);
			}
			if($type == 'WooCommerce Refunds'){
				$result = $woocommerce_core_instance->woocommerce_refunds_import($post_values , $mode , $check , $unikey_value , $unikey_name, $line_number);
			}
			if($type == 'WooCommerce Attributes'){
				$result = $woocommerce_core_instance->woocommerce_attributes_import($post_values , $mode , $check ,$unikey_value , $unikey_name, $line_number);
			}
			if($type == 'WooCommerce Tags'){
				$result = $woocommerce_core_instance->woocommerce_tags_import($post_values , $mode , $check , $unikey_value , $unikey_name, $line_number);
			}

			if(($type == 'Categories') || ($type == 'Tags') || ($type == 'Taxonomies') ){
				$result = $taxonomies_instance->taxonomies_import_function($post_values , $mode , $import_type , $unmatched_row, $check , $hash_key , $templatekey, $line_number ,$header_array ,$value_array,$wpml_array,$gmode,$poly_values,$update_based_on);
			}
			if($type == 'Users' || $type == 'WooCommerce Customer'){
				$result = $users_instance->users_import_function($post_values , $mode ,$unikey_value , $unikey_name, $line_number,$update_based_on,$check,$type);
			}
			if ($type == 'Comments' || $type == 'WooCommerce Reviews') {
				$result = $comments_instance->comments_import_function($post_values, $mode, $unikey_value, $unikey_name, $line_number, $type);
		    }
			if ($type == 'JetReviews') {
				$result = $jet_reviews_instance->set_jet_reviews_values($post_values , $mode ,$unikey_value , $unikey_name, $line_number,$update_based_on,$check);
			}
		
			if($type == 'WPeCommerce Products'){
				$result = $wpecommerce_instance->wpecommerce_product_import($post_values , $mode , $check , $unikey_value , $unikey_name, $line_number);
			}
			if($type == 'WPeCommerce Coupons'){
				$result = $wpecommerce_instance->wpecommerce_coupons_import($post_values , $mode ,$unikey_value , $unikey_name, $line_number);
			}
			if($type == 'Customer Reviews'){
				$result = $customer_reviews_instance->customer_reviews_import($post_values , $mode ,$unikey_value , $unikey_name, $line_number);
			}
		
			if($type == 'lp_order'){
				$result = $learnpress_instance->learnpress_orders_import($post_values , $mode , $check, $unikey_value , $unikey_name, $line_number);
			}

			if($type == 'nav_menu_item'){
				$comments_instance->menu_import_function($post_values , $mode ,$unikey_value , $unikey_name, $line_number);
			}

			if($type == 'widgets'){
				$comments_instance->widget_import_function($post_values , $mode ,$unikey_value , $unikey_name, $line_number);
			}
			$post_id = isset($result['ID']) ? $result['ID'] :'';
			
			if($gmode != 'CLI')
				$helpers_instance->get_post_ids($post_id ,$hash_key);

			if($media_handle['media_settings']['media_handle_option'] == 'true' && !empty($post_values['featured_image'])){
				update_option('ultimate_csv_importer_pro_featured_image', $post_values['featured_image']);
			}

			if(isset($post_values['featured_image'])) {	
				if ( preg_match_all( '/\b[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i', $post_values['featured_image'], $matchedlist, PREG_PATTERN_ORDER ) ) {	
					if($media_handle['media_settings']['media_handle_option'] == 'true' ){
						$post_values['featured_image'] = $this->check_for_featured_image_url($post_values['featured_image']);
						$attach_id = $this->featured_image_handling($media_handle, $post_values, $post_id, $type, $get_import_type, $unikey_value, $unikey_name, $header_array, $value_array,$hash_key,$templatekey);
					}	
				}
			}
			if(preg_match("(Can't|Skipped|Duplicate)", $this->detailed_log[$line_number]['Message']) === 0) { 
				if ( $type == 'WooCommerce Product' || $type == 'WPeCommerce Products') {
					if ( ! isset( $post_values['post_title'] ) ) {
						$post_values['post_title'] = '';
					}
					if(isset($result['post_type']) && ($result['post_type'] =='product_variation')){
						$this->detailed_log[$line_number]['webLink'] = get_permalink( $post_id );
						$this->detailed_log[$line_number]['post_type'] = get_post_type($post_id);
						$this->detailed_log[$line_number]['post_title'] = get_the_title($post_id);
						$this->detailed_log[$line_number]['id'] = $post_id;
					}
					else{
						$this->detailed_log[$line_number]['webLink'] = get_permalink( $post_id );
						$this->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id, true );
						$this->detailed_log[$line_number]['post_type'] = get_post_type($post_id);
						$this->detailed_log[$line_number]['post_title'] = get_the_title($post_id);
						$this->detailed_log[$line_number]['id'] = $post_id;
					}
					
				}
				elseif( $type == 'JetBooking'){
					if (!empty($post_id)) {
						$this->detailed_log[$line_number]['post_type'] = "jet-booking";
						$this->detailed_log[$line_number]['id'] = $post_id;	
						$this->detailed_log[$line_number]['webLink'] = admin_url('admin.php?page=jet-abaf-bookings');				
						$this->detailed_log[$line_number]['adminLink'] = admin_url('admin.php?page=jet-abaf-bookings');
						return $post_id;
					}
				}
				elseif( $type == 'Media'){
					if (!empty($post_id)) {
						//$this->detailed_log[$line_number]['mediaData'] = $result['DATA'];
						$this->detailed_log[$line_number]['post_type'] = "Media";
						$this->detailed_log[$line_number]['id'] = $post_id;
						
						$this->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id , true );
						$image_url = wp_get_attachment_url($post_id);
						$this->detailed_log[$line_number]['webLink'] = $image_url;
						return $post_id;
					}
				}
				elseif( $type == 'Users' || $type == 'WooCommerce Customer'){
					if(isset($post_id)){
						$this->detailed_log[$line_number]['webLink'] = get_author_posts_url( $post_id );
						$this->detailed_log[$line_number]['adminLink'] = get_edit_user_link( $post_id , true );
						$this->detailed_log[$line_number]['post_type'] = 'users';
						$this->detailed_log[$line_number]['id'] = $post_id;
					}
				}
				
				elseif($type == 'Comments' || $type == 'WooCommerce Reviews'  ){					
					//$this->detailed_log[$line_number]['VERIFY'] = "<b> Click here to verify</b> - <a href='" . get_comment_link( $post_id ) . "' target='_blank' title='" . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $post_values['post_title'] ) ) . "'rel='permalink'>Web View</a> | <a href='" . get_edit_comment_link( $post_id ) . "'target='_blank' title='" . esc_attr( 'Edit this item' ) . "'>Admin View</a>";
					$this->detailed_log[$line_number]['webLink'] = get_comment_link( $post_id );
					$this->detailed_log[$line_number]['adminLink'] = get_edit_comment_link( $post_id );
					$comment = get_comment($post_id);
					$this->detailed_log[$line_number]['id'] = $post_id ;
					$this->detailed_log[$line_number]['post_type'] = 'Comment' ;
					if($type == 'WooCommerce Reviews'){
						$this->detailed_log[$line_number]['post_type'] = 'Reviews' ;
						$this->detailed_log[$line_number]['adminLink'] = admin_url('comment.php?action=editcomment&c=' . $post_id);					
					}
					$comment_post_id = isset($comment->comment_post_ID) ? $comment->comment_post_ID : null;
					$post_type = isset($comment_post_id) ? get_post_type($comment_post_id) : null;
					// $this->detailed_log[$line_number]['post_type'] = isset($post_type) ? $post_type : 'Comment';

				}

				elseif ($type == 'JetReviews') {
					// Get the permalink for the post
					$post_permalink = get_permalink($post_id);
				
					if ($post_permalink) {
						// Add query parameters to the frontend permalink
						$web_link = add_query_arg(
							[
								'review_id' => $post_id,
								'review_type' => 'JetReviews',
							],
							$post_permalink
						);
					}
				
					// Add data to the detailed log
					$this->detailed_log[$line_number]['id'] = $post_id;
					$this->detailed_log[$line_number]['post_type'] = 'JetReviews';
					$this->detailed_log[$line_number]['webLink'] = $web_link;
					$this->detailed_log[$line_number]['adminLink'] = admin_url('admin.php?page=jet-reviews-list-page');
				}
				
				

				elseif($type == 'WooCommerce Coupons' || $type == 'lp_order'){
					$this->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id, true );
					$this->detailed_log[$line_number]['post_type'] = get_post_type($post_id);
					$this->detailed_log[$line_number]['post_title'] = get_the_title($post_id);
				}	
				elseif($type=='WooCommerce Orders'){
					$this->detailed_log[$line_number]['adminLink'] = $this->get_order_url($post_id) ;
					$this->detailed_log[$line_number]['post_type'] = get_post_type($post_id);
					$order = wc_get_order($post_id);
					if ($order) {
						$order_key = $order->get_order_key();
						$order_id = $order->get_id();
						$customer_order_view_link = wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount')) . '?key=' . $order_key;
						$this->detailed_log[$line_number]['webLink'] = $customer_order_view_link;
					}
				}			
				elseif($type == 'WooCommerce Product Variations' ){
					$this->detailed_log[$line_number]['adminLink'] = isset($post_values['PRODUCTID']) ? get_edit_post_link( $post_values['PRODUCTID'], true ) :'';
					$this->detailed_log[$line_number]['webLink'] = get_permalink( $post_id );
					$this->detailed_log[$line_number]['post_type'] = get_post_type($post_id);
					$this->detailed_log[$line_number]['post_title'] = get_the_title($post_id);
				}
				elseif($type == 'Tags' || $type == 'Categories' || $type == 'Taxonomies' || $type == 'post_tag' || $type =='Post_category'){
					//$this->detailed_log[$line_number]['VERIFY'] = "<b> Click here to verify</b> - <a href='" . get_edit_term_link( $post_id, $import_type ) . "'target='_blank' title='" . esc_attr( 'Edit this item' ) . "'>Admin View</a>";
					$this->detailed_log[$line_number]['adminLink'] = get_edit_term_link( $post_id, $import_type );
					$this->detailed_log[$line_number]['post_type'] = $type;
					$term_link = get_term_link($post_id, $import_type);
					if (!is_wp_error($term_link)) {
						$this->detailed_log[$line_number]['webLink'] = $term_link;
					}
				}
				elseif($type == 'nav_menu_item'){
					$this->detailed_log[1]['Message'] = "Imported Successfully.";
				}
				else{
					$post_value_title = isset($post_values['post_title']) ? $post_values['post_title'] : '';
					if($type == 'llms_coupon'){
						$this->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id, true );
						$this->detailed_log[$line_number]['post_type'] = get_post_type($post_id);
						$this->detailed_log[$line_number]['post_title'] = get_the_title($post_id);
					}
					else{
						$this->detailed_log[$line_number]['webLink'] = get_permalink( $post_id );
						$this->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id, true );
						$this->detailed_log[$line_number]['post_type'] = get_post_type($post_id);
						$this->detailed_log[$line_number]['post_title'] = get_the_title($post_id);
					}
		
				}
				if(isset($post_values['post_status'])){
					$this->detailed_log[$line_number]['Status'] = $post_values['post_status'];
				}	
			}
			return $post_id;
		}
		global $wpdb;
		$optional_type = '';
		if(is_plugin_active('jet-engine/jet-engine.php')){
			$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
			foreach($get_slug_name as $key=>$get_slug){
				$value=$get_slug->slug;
				$optionaltype=$value;						
				if($optionaltype == $type){
					$optional_type=$optionaltype;
				}
			}
		}	
	
		if($optional_type == $type){
			if($gmode != 'CLI'){
			$current_user = wp_get_current_user();									
			$author_id = $current_user->data->ID;
			}
			else { // else part is only used for set the author id
				$post_values = [];				
				$post_values = $this->import_core_fields($post_values,$mode, $line_number);
				$author_id = isset($post_values['post_author']) ? $post_values['post_author'] : "";
			}
			
			if($mode == 'Insert'){
				$post_values = [];
				$post_values = $helpers_instance->get_header_values($map , $header_array , $value_array, $hash_key);
				
				$post_values = $this->import_core_fields($post_values,$mode, $line_number);
				
				$table_name = 'jet_cct_'.$type;												
				$value_status =  empty($post_values['cct_status']) ? "publish" : $post_values['cct_status'] ;
				if($author_id)
					$wpdb->get_results("INSERT INTO {$wpdb->prefix}$table_name(cct_status,cct_author_id) values('$value_status',$author_id)");       			
				else 
					$wpdb->get_results("INSERT INTO {$wpdb->prefix}$table_name(cct_status) values('$value_status')");       			
				$get_result =  $wpdb->get_results("SELECT _ID FROM {$wpdb->prefix}$table_name WHERE  cct_status = '$value_status' order by _ID DESC ");			
				$id = $get_result[0];				
				$post_id = $id->_ID;
		
				$page = 'jet-cct-'.$type;
				$dir=site_url().'/wp-admin';				
				$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey_value'");
				$cct_post_title = isset($post_values['post_title']) ? $post_values['post_title'] : '';

				$this->detailed_log[$line_number]['Message'] = 'Inserted Custom Content Type '  . ' ID: ' . $post_id ;
				//$this->detailed_log[$line_number]['VERIFY'] = "<b> Click here to verify</b> - <a href='$dir/admin.php?page=$page&cct_action=edit&item_id=$post_id' target='_blank' title='" . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $cct_post_title ) ) . "'rel='permalink'>Admin View</a>";	
				$this->detailed_log[$line_number]['id'] = $post_id;
				$this->detailed_log[$line_number]['state'] = 'Inserted';
				$this->detailed_log[$line_number]['adminLink'] = "$dir/admin.php?page=$page&cct_action=edit&item_id=$post_id";
				$this->detailed_log[$line_number]['webLink'] = get_permalink($post_id);
				$this->detailed_log[$line_number]['status'] = $value_status;
				if($unmatched_row == 'true'){
					global $wpdb;
					$type ='cct';
					$post_entries_table = $wpdb->prefix ."post_entries_table";
					$file_table_name = $wpdb->prefix."smackcsv_file_events";
					$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `hash_key` = '$hash_key'");	
					$file_name = $get_id[0]->file_name;
					$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$post_id}','{$type}', '{$file_name}','Inserted')");
				}			
				return $post_id;
			}
			else{
				if($check == '_ID'){
					$post_values = [];
					$post_values = $helpers_instance->get_header_values($map , $header_array , $value_array);					
					if(!empty($post_values['_ID'])){					
					$page = 'jet-cct-'.$type;
					$dir = site_url().'/wp-admin';
					$ID = $post_values['_ID'];	
					$table_name = 'jet_cct_'.$type;					
					$get_result =  $wpdb->get_results("SELECT _ID FROM {$wpdb->prefix}$table_name WHERE _ID = $ID AND cct_status != 'trash' order by _ID DESC ");								
					
					 if(!empty($get_result)) {
					if(isset($post_values['cct_status'])){
						$jet_status =  $post_values['cct_status'];
						$wpdb->update( $wpdb->prefix.'jet_cct_'.$type , 
							array( 
								'cct_status' => $jet_status,
							) , 
							array( '_ID' => $ID
							) 
						);
					}
					$updated_row_counts = $helpers_instance->update_count($unikey_value,$unikey_name);
					$updated_count = $updated_row_counts['updated'];
					$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey_value'");
					$status =  $wpdb->get_results("SELECT cct_status FROM {$wpdb->prefix}$table_name WHERE _ID = $ID ");
					$post_stat = $status[0]->cct_status;

					$cct_post_title = isset($post_values['post_title']) ? $post_values['post_title'] : '';

					$this->detailed_log[$line_number]['Message'] = 'Updated Custom Content Type '  . ' ID: ' . $ID ;
					//$this->detailed_log[$line_number]['VERIFY'] = "<b> Click here to verify</b> - <a href='$dir/admin.php?page=$page&cct_action=edit&item_id=$ID' target='_blank' title='" . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $cct_post_title ) ) . "'rel='permalink'>Admin View</a>";
					$this->detailed_log[$line_number]['adminLink'] = "$dir/admin.php?page=$page&cct_action=edit&item_id=$ID";
					$this->detailed_log[$line_number]['webLink'] = get_permalink($ID);
					$this->detailed_log[$line_number]['id'] = $ID;
					$this->detailed_log[$line_number]['state'] = 'Updated';	
					$this->detailed_log[$line_number]['status'] = $post_stat;						
					if($unmatched_row == 'true'){
						global $wpdb;
						$type ='cct';
						$post_entries_table = $wpdb->prefix ."post_entries_table";
						$file_table_name = $wpdb->prefix."smackcsv_file_events";
						$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `hash_key` = '$hash_key'");	
						$file_name = $get_id[0]->file_name;
						$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$ID}','{$type}', '{$file_name}','Inserted')");
					}
					return $ID;	
				}
				else {
				$value_status =  empty($post_values['cct_status']) ? "publish" : $post_values['cct_status'] ;
				$wpdb->get_results("INSERT INTO {$wpdb->prefix}$table_name(cct_status,cct_author_id) values('$value_status',$author_id)");       			
				$get_result =  $wpdb->get_results("SELECT _ID FROM {$wpdb->prefix}$table_name WHERE  cct_status = '$value_status' order by _ID DESC ");										
				$post_id = $get_result[0]->_ID;
				$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey_value'");
				$cct_post_title = isset($post_values['post_title']) ? $post_values['post_title'] : '';

				$this->detailed_log[$line_number]['Message'] = 'Inserted Custom Content Type '  . ' ID: ' . $post_id ;
				//$this->detailed_log[$line_number]['VERIFY'] = "<b> Click here to verify</b> - <a href='$dir/admin.php?page=$page&cct_action=edit&item_id=$post_id' target='_blank' title='" . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $cct_post_title ) ) . "'rel='permalink'>Admin View</a>";
				$this->detailed_log[$line_number]['id'] = $post_id;
				$this->detailed_log[$line_number]['state'] = 'Inserted';
				$this->detailed_log[$line_number]['adminLink'] = "$dir/admin.php?page=$page&cct_action=edit&item_id=$post_id";
				$this->detailed_log[$line_number]['webLink'] = get_permalink($post_id);		
				$this->detailed_log[$line_number]['status'] = $value_status;
				if($unmatched_row == 'true'){
					global $wpdb;
					$type ='cct';
					$post_entries_table = $wpdb->prefix ."post_entries_table";
					$file_table_name = $wpdb->prefix."smackcsv_file_events";
					$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `hash_key` = '$hash_key'");	
					$file_name = $get_id[0]->file_name;
					$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$post_id}','{$type}', '{$file_name}','Inserted')");
				}
				return $post_id;
				}
				}
				else {					
					$this->detailed_log[$line_number]['Message'] = "Skipped.Cannot update.ID's are empty.";
					$this->detailed_log[$line_number]['state'] = 'Skipped';
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
				}
				}
			}
		}
		
		elseif($type == 'ngg_pictures'){
			$post_values = [];
			$file_table_name = $wpdb->prefix ."smackcsv_file_events";
			$get_id = $wpdb->get_results( "SELECT file_name FROM $file_table_name WHERE `hash_key` = '$hash_key'");
			
			$file_name = $get_id[0]->file_name;
			$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
			foreach($map as $key => $value){
				$csv_value= trim($map[$key]);
				if(!empty($csv_value)){
					$get_key= array_search($csv_value , $header_array);
					if(isset($value_array[$get_key])){
						if($file_extension == 'xml'){
							$csv_element = $value;
						}
						else{
						$csv_element = $value_array[$get_key];	
						}
						$wp_element= trim($key);
						if(!empty($csv_element) && !empty($wp_element)){
							$post_values[$wp_element] = $csv_element;
						}
					}
				}
			}

			if($type == 'ngg_pictures'){
				$result = CoreFieldsImport::$nextgen_instance->nextgenGallery($post_values,$check,$mode,$line_number,$header_array,$value_array);
			}
			return $result;
		}

		else{
			$post_values = [];
					
		    $current_user = wp_get_current_user();
		    $current_user_role = isset($current_user->roles[0]) ? $current_user->roles[0] : '';
		if($current_user_role == 'administrator'){
			$trim_content = array(
							'->static' => '', 
							'->math' => '', 
							'->cus1' => '',
							'->num' => '',
						);
			
			foreach($map as $header_keys => $value){
				if( strpos($header_keys, '->cus2') !== false) {
					if(!empty($value)){
						$helpers_instance->write_to_customfile($value, $header_array, $value_array);
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
		}
			foreach($map as $key => $value){
			
				$csv_value = trim($map[$key]);
				$extension_object = new ExtensionHandler;
				$import_type = $extension_object->import_type_as($type);
				$import_as = $extension_object->import_post_types($import_type );

				if(!empty($csv_value)){
					//$pattern = "/({([a-z A-Z 0-9 | , _ -]+)(.*?)(}))/";
					$pattern1 = '/{([^}]*)}/';
					$pattern2 = '/\[([^\]]*)\]/';
					$file_table_name = $wpdb->prefix ."smackcsv_file_events";
					$get_id = $wpdb->get_results( "SELECT file_name FROM $file_table_name WHERE `hash_key` = '$hash_key'");
					
					$file_name = $get_id[0]->file_name;
					$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
					if(preg_match_all($pattern1, $csv_value, $matches, PREG_PATTERN_ORDER) && $file_extension !='xml'){		
						//check for inbuilt or custom function call -> enclosed in []
						if(preg_match_all($pattern2, $csv_value, $matches2)){
							$matched_element = $matches2[1][0];
							
							foreach($matches[1] as $value){
								$get_value = $helpers_instance->replace_header_with_values($value, $header_array, $value_array);
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
						
							$csv_element = $helpers_instance->evalPhp($matched_element);
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

							//check for math expression
							$math = 'MATH';
							if (strpos($csv_element, $math) !== false) {			
								$equation = str_replace('MATH', '', $csv_element);
								$csv_element = $helpers_instance->evalMath($equation);
							}
						}
						$wp_element= trim($key);
						if(!empty($csv_element) && !empty($wp_element)){
							$post_values[$wp_element] = $csv_element;	
							$post_values['post_type'] = $import_as;
						//	$post_values = $this->import_core_fields($post_values,$mode, $line_number);
						}
					}
					// for custom function without headers in it
					elseif(preg_match_all($pattern2, $csv_value, $matches2) && $file_extension !='xml'){
						$matched_element = $matches2[1][0];
					
						$wp_element= trim($key);
						$csv_element1 = $helpers_instance->evalPhp($matched_element);
						$post_values[$wp_element] = $csv_element1;
					}

					elseif(!in_array($csv_value , $header_array)){
						$wp_element= trim($key);
						$post_values[$wp_element] = $csv_value;
						$post_values['post_type'] = $import_as;
						//$post_values = $this->import_core_fields($post_values,$mode, $line_number);
					}
				    
					else{
						$get_key = array_search($csv_value , $header_array);
						if(isset($value_array[$get_key])){
							$csv_element = $value_array[$get_key];
							$wp_element= trim($key);
							$extension_object = new ExtensionHandler;
							$import_type = $extension_object->import_type_as($type);
							$import_as = $extension_object->import_post_types($import_type );
							if($mode == 'Insert'){
								if(!empty($csv_element) && !empty($wp_element)){
									$post_values[$wp_element] = $csv_element;
									$post_values['post_type'] = $import_as;
									//$post_values = $this->import_core_fields($post_values,$mode, $line_number);
								}
							}
							else{
								if(!empty($csv_element) || !empty($wp_element)){
									$post_values[$wp_element] = $csv_element;
									$post_values['post_type'] = $import_as;
									//$post_values = $this->import_core_fields($post_values,$mode, $line_number);
								}
							}
							if($import_as == 'page'){
								if(isset($post_values['post_parent'])){
									if(!is_numeric($post_values['post_parent'])){
										$post_parent_title = $post_values['post_parent'];
										$post_parent_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$post_parent_title' AND post_type = 'page'");
										$post_values['post_parent'] = $post_parent_id;
									}
								}
							}
						}
					}
				}
			}
			$post_values = $this->import_core_fields($post_values,$mode, $line_number);
			if($check == 'ID'){	
				if(!empty($post_values['ID'])){
					$ID = $post_values['ID'];
					$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE ID = $ID AND post_type = '$import_as' AND post_status != 'trash' ORDER BY ID DESC");
				}		
			}
			if($check == '_ID'){
				if(!empty($post_values['_ID'])){
				// $table_name.='wp_jet_cct_'.$type;
				// $get_result =  $wpdb->get_results("SELECT _ID FROM $table_name WHERE _ID = $ID AND cct_status != 'trash' order by _ID DESC ");			
				// foreach($get_result as $key=>$get_slug){
				// 	$post_id=$get_slug->_ID;
				// }
				//return $post_id;		
				return $ID;
				}
			}
			if($check == 'post_title'){
				if(!empty($post_values['post_title'])){
				$title = $post_values['post_title'];
				$title = $wpdb->_real_escape($title);
				// if(strpos($title,'&') !== false){
				// 	$title = str_replace('&','&amp;',$title);
				// }
				$poly_values = $helpers_instance->get_header_values($poly_array,$header_array,$value_array);
				if($sitepress !=null){
					$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$title' AND post_type = '$import_as' AND post_status != 'trash' order by ID DESC ");		
					if(!empty($get_result)){
					foreach($get_result as $wpml_result){
						$wpml_id[] = $wpml_result->ID;
					}	
					$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
					$background_values = $wpdb->get_results("SELECT mapping FROM $template_table_name WHERE `eventKey` = '$hash_key' ");
					foreach ($background_values as $values) {
						$mapped_fields_values = $values->mapping;
					}
					$map_wpml = unserialize($mapped_fields_values);
					
					$wpml_values = $helpers_instance->get_header_values($map_wpml['WPML'], $header_array , $value_array);
					$get_results =array();
					$w = 0;
					foreach($wpml_id as $w_id){
						$languagecode =  $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = '$w_id'");		
						if($wpml_values['language_code'] == $languagecode){
							$get_results[$w]['ID']= $w_id;

							$w++;
						}
					}
					if(!empty($get_results) && is_array($get_results)){
						foreach($get_results as $g_result){
							$getresult[] = (object) $g_result;
						}
					}
					else{
						$get_result = array();
					}
					}
				}
				elseif(!empty($poly_values)){
					$language_code = $poly_values['language_code'];
					$get_result=$wpdb->get_results("SELECT p.ID FROM {$wpdb->prefix}posts as p inner join {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID inner join {$wpdb->prefix}term_taxonomy as tax on tax.term_taxonomy_id=tr.term_taxonomy_id inner join {$wpdb->prefix}terms as t on t.term_id=tax.term_id  where tax.taxonomy ='language'  and t.slug='$language_code' and p.post_title='$title' AND p.post_status != 'trash' AND p.post_type = '$import_as'");
				}
				else{
					$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$title' AND post_type = '$import_as' AND post_status != 'trash' order by ID DESC ");
				}
			}
			}
			if($check == 'post_name'){
				if(!empty($post_values['post_name'])){
				$name = $post_values['post_name'];
				$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_name = '$name' AND post_type = '$import_as' AND post_status != 'trash' order by ID DESC ");
				}	
			}
			if($check == 'post_content'){
				if(!empty($post_values['post_content'])){
				$content = isset($post_values['post_content']) ? $post_values['post_content'] : '';
				$get_result =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_content = '$content' AND post_type = '$import_as' AND post_status != 'trash' order by ID DESC ");	
				}
			}
			$update = array('ID','post_title','post_name','post_content');
			if($update_based_on == 'skip' && in_array($check, $update)){
				if(empty($get_result)) {
					$this->detailed_log[$line_number]['Message'] = "Skipped,Due to existing ".$check." is not presents.";
					$this->detailed_log[$line_number]['state'] = 'Skipped';
				}	
			}
			if(!in_array($check, $update)){
				if($update_based_on == 'acf'){
					if(is_plugin_active('advanced-custom-fields-pro/acf.php')||is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('secure-custom-fields/secure-custom-fields.php')){
						$get_result = $this->custom_fields_update_based_on($update_based_on, $acf, $check, $header_array, $value_array,$type,$line_number);
					}
				}
				else if ($update_based_on == 'jetengine') {
					if (is_plugin_active('jet-engine/jet-engine.php')) {
						$get_result = $this->custom_fields_update_based_on($update_based_on, $jetengine, $check, $header_array, $value_array,$type,$line_number);
					}
				}
				elseif($update_based_on == 'toolset'){
					if(is_plugin_active('types/wpcf.php')){
						$get_result = $this->custom_fields_update_based_on($update_based_on, $toolset, $check, $header_array, $value_array,$type,$line_number);
					}
				}
				elseif($update_based_on == 'metabox'){
					if(is_plugin_active('meta-box/meta-box.php') || is_plugin_active('meta-box-aio/meta-box-aio.php')  || is_plugin_active('meta-box-lite/meta-box-lite.php')){
						$get_result = $this->custom_fields_update_based_on($update_based_on, $metabox, $check, $header_array, $value_array,$type,$line_number);
					}
				}
				if($update_based_on == 'pods'){
					if(is_plugin_active('pods/init.php')){
						$get_result = $this->custom_fields_update_based_on($update_based_on, $pods, $check, $header_array, $value_array,$type,$line_number);
					}
				}	
			}
			/** Update or Insert based on categories */
			if ($isCategory) {
				$get_result_check = $this->get_matching_posts_by_category($helpers_instance, $post_cat_list,$categoryList, $header_array, $value_array, $import_type, $import_as, $wpdb,$get_result,$mode);
				if ($mode === 'Update' && !empty($check) && !empty($get_result) && !$get_result_check) {
					$get_result = [];
				}
			}

			$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
			$background_values = $wpdb->get_results("SELECT mapping FROM $template_table_name where eventKey = '$hash_key'");
			foreach ($background_values as $values) {
				$mapped_fields_values = $values->mapping;
			}
			$map = unserialize($mapped_fields_values);
			if($this->generated_content){
				$generated_content = $post_values['post_content'];
				if($generated_content == 401 ||$generated_content == 429 ||$generated_content == 500 ||$generated_content == 503 || $generated_content == 400){
					$post_values['post_content'] = '';
				}
			}
			if($this->generated_content){
				$generated_short_description = $post_values['post_excerpt'];
				if($generated_short_description == 401 ||$generated_short_description == 429 ||$generated_short_description == 500 ||$generated_short_description == 503 || $generated_short_description == 400){
					$post_values['post_excerpt'] = '';
				}
			}
			if($mode == 'Insert'){
				if($isCategory && !($get_result_check)){
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
					$this->detailed_log[$line_number]['Message'] =  "Skipped, Record does not match the selected categories.";
					$this->detailed_log[$line_number]['state'] = 'Skipped';
				}
				else if (isset($get_result) && is_array($get_result) && !empty($get_result)) {
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
					$this->detailed_log[$line_number]['Message'] =  "Skipped, Due to duplicate found!.";
					$this->detailed_log[$line_number]['state'] = 'Skipped';
				}
				elseif(!empty($this->detailed_log) && isset($this->detailed_log[$line_number]) && preg_match("(Skipped)", $this->detailed_log[$line_number]['Message']) != 0) {
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name
					 = '$unikey_value'");
				}
				else{
					
								
					//set the post parent
					if( (isset($post_values['post_parent'])) && (!is_numeric($post_values['post_parent'])) && (!empty($post_values['post_parent']))){
						$p_type=$post_values['post_type'];
						$parent_title=$post_values['post_parent'];
						$parent_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '$parent_title' and post_status !='trash' and post_type='$p_type'" );
						$post_values['post_parent']=$parent_id;
					}

					//Insert the posts
					if($post_values['post_status']!='delete'){
						if(isset($post_values['ID'])){
							unset($post_values['ID']);
						}
						
						if(is_plugin_active('multilanguage/multilanguage.php')) {
							$post_id = $this->multiLang($post_values);
						}
						else if($sitepress !=null){
							$post_values['post_content']=isset($post_values['post_content'])?$post_values['post_content']:'';
							$post_values['post_content'] = html_entity_decode($post_values['post_content']);
							$post_values['post_content'] = str_replace('\n',"\n",$post_values['post_content']);
							$active_languages = $wpdb->get_results("SELECT code FROM {$wpdb->prefix}icl_languages where active = 1");
							foreach($active_languages as $lang){
								$active [] =$lang->code;
							}
							$template_table_name = $wpdb->prefix . "ultimate_csv_importer_mappingtemplate";
							$background_values = $wpdb->get_results("SELECT mapping FROM $template_table_name WHERE `eventKey` = '$hash_key' ");
							foreach ($background_values as $values) {
								$mapped_fields_values = $values->mapping;
							}
							$map_wpml = unserialize($mapped_fields_values);
							
							$wpml_values = $helpers_instance->get_header_values($map_wpml['WPML'], $header_array , $value_array);
							if(!empty($wpml_values['language_code'])){
								if(in_array($wpml_values['language_code'],$active)){
									$post_id = wp_insert_post($post_values);
									$status = $post_values['post_status'];
									$update=$wpdb->get_results("UPDATE {$wpdb->prefix}posts set post_status = '$status' where id = $post_id");
								}
								else{
									$wpml_message = "The given language code not configured in WPML";
								}
							}
							else{
								
								$post_values['post_content']=isset($post_values['post_content'])?$post_values['post_content']:'';
								$post_values['post_content'] = html_entity_decode($post_values['post_content']);
								$post_values['post_content'] = str_replace('\n',"\n",$post_values['post_content']);

								$post_id = wp_insert_post($post_values);							
								$status = $post_values['post_status'];
								$update=$wpdb->get_results("UPDATE {$wpdb->prefix}posts set post_status = '$status' where id = $post_id");
								
							}
						}
						else{
							$post_values['post_content']=isset($post_values['post_content'])?$post_values['post_content']:'';
							$post_values['post_content'] = html_entity_decode($post_values['post_content']);
							$post_values['post_content'] = str_replace('\n',"\n",$post_values['post_content']);
							$post_id = wp_insert_post($post_values);							
							$status = $post_values['post_status'];
							$update=$wpdb->get_results("UPDATE {$wpdb->prefix}posts set post_status = '$status' where id = $post_id");
						}
						if(!empty($post_values['wp_page_template'])){
							update_post_meta($post_id, '_wp_page_template', $post_values['wp_page_template']);
						}
					}

					if($post_values['post_status'] == 'delete'){
						$post_title = $post_values['post_title'];
						$post_id = $wpdb->get_results("select ID from {$wpdb->prefix}posts where post_title = '$post_title'");
						foreach($post_id as $value){
							$posts = $value->ID;
							wp_delete_post($posts,true); 
						}
					}

					$post_values['specific_author'] = isset($post_values['specific_author']) ? $post_values['specific_author'] : "";
		
					if($unmatched_row == 'true'){
						global $wpdb;
						$post_entries_table = $wpdb->prefix ."post_entries_table";
						$file_table_name = $wpdb->prefix."smackcsv_file_events";
						$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `$unikey_name` = '$unikey_value'");	
						$file_name = $get_id[0]->file_name;
						$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$post_id}','{$type}', '{$file_name}','Inserted')");
					}

					if(isset($post_values['post_format'])){
						$this->post_format_function($post_id, $post_values['post_format']);
					}

					if(is_plugin_active('post-expirator/post-expirator.php')) {
						$this->postExpirator($post_id,$post_values);
					}

					$media_handle = get_option('smack_image_options');
					if($media_handle['media_settings']['media_handle_option'] == 'true' && $media_handle['media_settings']['enable_postcontent_image'] == 'true'){
						// if (preg_match("/<img/", $post_values['post_content'])) {
						// 	$content = $post_values['post_content'];
						
						// 	$doc = new \DOMDocument();
						// 	@$doc->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // Prevent extra tags
						
						// 	$xpath = new \DOMXPath($doc);
						// 	$searchNode = $xpath->query('//img[@src]');

						// 	if (!empty($searchNode)) {
						// 		$orig_img_src = [];
						// 		foreach ($searchNode as $node) {
						// 			$orig_img_src[] = $node->getAttribute('src');
						// 		}
						// 		if (isset($orig_img_src)) {
						// 			$shortcode_table = $wpdb->prefix . "ultimate_csv_importer_shortcode_manager";
						// 			$indexs = 0;
						// 			foreach ($orig_img_src as $img_val) {
						// 				$shortcode  = 'inline';
						// 				CoreFieldsImport::$media_instance->store_image_ids($i = 1);
						// 				$attach_id = CoreFieldsImport::$media_instance->image_meta_table_entry($line_number, $post_values, $post_id, '', $img_val, $hash_key, $shortcode, $get_import_type, '', '', $header_array, $value_array, '', '', $indexs);
						// 				$indexs++;
						// 			}
						// 		}
						
						// 		if (!empty($attach_id)) {
						// 			$temp_img = $wpdb->get_var(
						// 				$wpdb->prepare(
						// 					"SELECT guid FROM {$wpdb->prefix}posts WHERE ID = %d AND post_type = 'attachment'",
						// 					$attach_id
						// 				)
						// 			);

						// 			$node->setAttribute('src', $temp_img);
						// 		}
						
						// 		// Save the updated content and strip <?xml> declaration
						// 		$post_content = $doc->saveHTML();
						// 		$post_content = preg_replace('/^<\?xml[^>]+>/', '', $post_content);
						
						// 		$update_content = [
						// 			'ID'           => $post_id,
						// 			'post_content' => html_entity_decode($post_content, ENT_QUOTES, 'UTF-8'),
						// 		];
						
						// 		wp_update_post($update_content);
						// 	}
						// }

						if (preg_match("/<img/", $post_values['post_content'])) {
							$content = $post_values['post_content'];
							// Wrap the content in a proper HTML structure with encoding declaration
							$wrapped_content = '<!DOCTYPE html><html><meta charset="UTF-8"><body>' . $content . '</body></html>';
						
							// Load the HTML content into DOMDocument
							$doc = new \DOMDocument('1.0', 'UTF-8');
							@$doc->loadHTML($wrapped_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
						
							// Log the document's body
							$body = $doc->getElementsByTagName('body')->item(0);
					
							if ($body) {
								// Process the content
								$xpath = new \DOMXPath($doc);
								$searchNode = $xpath->query('//img[@src]');
						
								if ($searchNode->length > 0) {
									$orig_img_src = [];
									foreach ($searchNode as $node) {
										$orig_img_src[] = $node->getAttribute('src');
									}
						
									if (isset($orig_img_src)) {
										$shortcode_table = $wpdb->prefix . "ultimate_csv_importer_shortcode_manager";
										$indexs = 0;
						
										foreach ($orig_img_src as $index => $img_val) {
											$shortcode  = 'inline';
											CoreFieldsImport::$media_instance->store_image_ids($i = 1);
											$attach_id = CoreFieldsImport::$media_instance->image_meta_table_entry($line_number, $post_values, $post_id, '', $img_val, $hash_key, $shortcode, $get_import_type, '', '', $header_array, $value_array, '', '', $indexs);
						
											if (!empty($attach_id)) {
												$temp_img = $wpdb->get_var(
													$wpdb->prepare(
														"SELECT guid FROM {$wpdb->prefix}posts WHERE ID = %d AND post_type = 'attachment'",
														$attach_id
													)
												);
						
												// Update the corresponding image node's src attribute
												$node = $searchNode->item($index);
												if ($node) {
													$node->setAttribute('src', $temp_img);
												}
											}
						
											$indexs++;
										}
									}
						
									// Extract updated content
									$updated_body = '';
									foreach ($body->childNodes as $child) {
										$updated_body .= $doc->saveHTML($child);
									}
						
									// Reintroduce line breaks
									//$updated_body = preg_replace('/(>)(<)/', "$1\n$2", $updated_body);
									$updated_body = str_replace('\n',"\n",$updated_body);
									$update_content = [
										'ID'           => $post_id,
										'post_content' => html_entity_decode($updated_body, ENT_QUOTES, 'UTF-8'),
									];
									wp_update_post($update_content);
								}
							}
						}	
					}		
					
					$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey_value'");
						
					if(!empty($post_values['post_content']) && $media_handle['media_settings']['enable_postcontent_image'] == 'false' && preg_match("/<img/", $post_values['post_content'])) {
						$dom = new \DOMDocument();
                        @$dom->loadHTML($post_values['post_content']);
                        $xpath = new \DOMXPath($dom);
                        $searchNode = $xpath->query('//img[@src]');
						$i = 1;
						foreach ( $searchNode as $searchNodes ) {
							$orig_img_src[] = $searchNodes->getAttribute( 'src' );
							if(!empty($orig_img_src)){
								CoreFieldsImport::$media_instance->store_image_ids($i);
							}
							$i++;
						}	
							$media_dir = wp_get_upload_dir();
							$names = $media_dir['url'];
					}			
					$media_dir = wp_get_upload_dir();
					$names = $media_dir['url'];
						
					// image handling code
					
					if($media_handle['media_settings']['media_handle_option'] == 'true' && !empty($post_values['featured_image'])  && !is_plugin_active('featured-image-from-url/featured-image-from-url.php')){
						update_option('ultimate_csv_importer_pro_featured_image', $post_values['featured_image']);
						$post_values['featured_image'] = $this->check_for_featured_image_url($post_values['featured_image']);
						$attach_id = $this->featured_image_handling($media_handle, $post_values, $post_id, $type, $get_import_type, $unikey_value, $unikey_name, $header_array, $value_array,$hash_key,$templatekey,$line_number);
					}

					if(is_wp_error($post_id) || $post_id == '') {
						if(is_wp_error($post_id)) {
							$this->detailed_log[$line_number]['Message'] = "Can't insert this " . $post_values['post_type'] . ". " . $post_id->get_error_message();
						}
						else {
							$wpml_message  = isset($wpml_message )?$wpml_message:'';
							if($sitepress !=null){
								$this->detailed_log[$line_number]['Message'] =  "Can't insert this " . $post_values['post_type'].'. '.$wpml_message;
							}
							else{
								$this->detailed_log[$line_number]['Message'] =  "Can't insert this " . $post_values['post_type'];
							}
						}
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
					}	
					else{
						$content=$this->openAI_response;
						if(!empty($content)){
							if($generated_content == 401) {
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot create Content. Invalid API key provided. Please check your API key.";	
							}
							if($generated_content == 400) {
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot create Content. Please check your Inputs";	
							}
							else if($generated_content == 429) {
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot create Content. Rate limit reached for requests or You exceeded your current quota.";	
							}
							else if($generated_content == 500) {
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot create Content. The server had an error while processing your request.";	
							}
							else if($generated_content == 503) {
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot create Content. The engine is currently overloaded, please try again later.";	
							}
							else if($generated_short_description == 401) {
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot create short description. Invalid API key provided. Please check your API key.";	
							}
							else if($generated_short_description == 400) {
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot create short description. Please check your Inputs.";	
							}
							else if($generated_short_description == 429) {
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot create short description. Rate limit reached for requests or You exceeded your current quota.";	
							}
							else if($generated_short_description == 500) {
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot create short description. The server had an error while processing your request.";	
							}
							else if($generated_short_description == 503) {
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot create short description. The engine is currently overloaded, please try again later.";	
							}

							else{
								$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author'];
								$author = explode('</b>',$post_values['specific_author']);
								$this->detailed_log[$line_number]['id'] = $post_id;
								$this->detailed_log[$line_number]['state'] = 'Inserted';
								$this->detailed_log[$line_number]['author'] = end($author);
							}
						}
						else{
							$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author'];
							$author = explode('</b>',$post_values['specific_author']);
							$this->detailed_log[$line_number]['id'] = $post_id;
							$this->detailed_log[$line_number]['state'] = 'Inserted';
							$this->detailed_log[$line_number]['author'] = end($author);
						}
					}
				}
			}
            if($mode == 'Update'){
				if($isCategory && empty($get_result)){
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
					$this->detailed_log[$line_number]['Message'] =  "Skipped, Record does not match the selected categories.";
					$this->detailed_log[$line_number]['state'] = 'Skipped';
				}
				if(isset($this->detailed_log[$line_number]['Message']) && preg_match("(Skipped)", $this->detailed_log[$line_number]['Message']) !== 0) {					
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
				}
				if($this->generated_content){
					$generated_content = $post_values['post_content'];					
					if($generated_content == 401 ||$generated_content == 429 ||$generated_content == 500 ||$generated_content == 503){
						$post_values['post_content'] = '';
					}
				}	
				if($this->generated_content){
					$generated_short_description = $post_values['post_excerpt'];
					if($generated_short_description == 401 ||$generated_short_description == 429 ||$generated_short_description == 500 ||$generated_short_description == 503){
						$post_values['post_excerpt'] = '';
					}
				}			
				if(isset($this->detailed_log[$line_number]['Message']) && preg_match("(Skipped)", $this->detailed_log[$line_number]['Message']) !== 0) {					
					$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
				}				
				else {									
				if (is_array($get_result) && !empty($get_result)) {	
					if(!in_array($check, $update)){
						if(isset($get_result[0]->post_id)){
							$post_id = $get_result[0]->post_id;	
						}
						else{
							$post_id = $get_result[0]->ID;	
						}
						$post_values['ID'] = $post_id;
					}else{
						$post_id = $get_result[0]->ID;		
						$post_values['ID'] = $post_id;							
					}										
					$media_handle = get_option('smack_image_options');
					if($media_handle['media_settings']['media_handle_option'] == 'true' && !empty($post_values['featured_image'])){
						update_option('ultimate_csv_importer_pro_featured_image', $post_values['featured_image']);
					}
//set the post parent update
						if ((isset($post_values['post_parent'])) && (!is_numeric($post_values['post_parent'])) && (!empty($post_values['post_parent']))) {
							$p_type = $post_values['post_type'];
							$parent_title = $post_values['post_parent'];
							$parent_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_title = '$parent_title' and post_status !='trash' and post_type='$p_type'");
							$post_values['post_parent'] = $parent_id;
						}
					// image handling code
					if($media_handle['media_settings']['media_handle_option'] == 'true' && !empty($post_values['featured_image'])){
						$post_values['featured_image'] = $this->check_for_featured_image_url($post_values['featured_image']);
						$attach_id = $this->featured_image_handling($media_handle, $post_values, $post_id, $type, $get_import_type, $unikey_value, $unikey_name, $header_array, $value_array,$hash_key,$templatekey,$line_number);
					}
					if(isset($post_values['post_content']) && $media_handle['media_settings']['media_handle_option'] == 'true' 
					&& isset($media_handle['media_settings']['enable_postcontent_image'])
					&& $media_handle['media_settings']['enable_postcontent_image'] == 'true'){
						if(preg_match("/<img/", $post_values['post_content'])) {

							$content = "<p>".$post_values['post_content']."</p>";
							$doc = new \DOMDocument();
							if(function_exists('mb_convert_encoding')) {
								@$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
							}else{
								@$doc->loadHTML( $content);
							}
							$xpath = new \DOMXPath($doc);
							$searchNode = $xpath->query('//img[@src]');
							if ( ! empty( $searchNode ) ) {
								foreach ( $searchNode as $searchNodes ) {
									$orig_img_src[] = $searchNodes->getAttribute( 'src' ); 
								}
												
								$media_dir = wp_get_upload_dir();
								$names = $media_dir['url'];
								if(isset($orig_img_src)){
									$shortcode_table = $wpdb->prefix . "ultimate_csv_importer_shortcode_manager";
									$indexs = 0;
									foreach ($orig_img_src as $img_val){
										$shortcode  = 'inline';
										CoreFieldsImport::$media_instance->store_image_ids($i=1);
										$attach_id = CoreFieldsImport::$media_instance->image_meta_table_entry($line_number,$post_values,$post_id ,'',$img_val, $hash_key ,$shortcode,$get_import_type,'','',$header_array,$value_array,'','',$indexs);
										$indexs++;																			
									}
								}
								$image_name = pathinfo($img_val);
								$fimg_name = $image_name['basename'];
								$temp_img = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts where guid like '%$fimg_name%'");
								$searchNodes->setAttribute( 'src', $temp_img);
								$post_content              = $doc->saveHTML();
								$post_content = str_replace('\n',"\n",$post_content);

								$update_content = [
									'ID'           => $post_id,
									'post_content' => html_entity_decode($post_content, ENT_QUOTES, 'UTF-8')
								];
								wp_update_post($update_content);
							}
						}
					}

					if(!empty($post_values['post_content']) && $media_handle['media_settings']['media_handle_option'] == 'false' && preg_match("/<img/", $post_values['post_content'])) {
						$dom = new \DOMDocument();
                        @$dom->loadHTML($post_values['post_content']);
                        $xpath = new \DOMXPath($dom);
                        $searchNode = $xpath->query('//img[@src]');
						$i=1;
						foreach ( $searchNode as $searchNodes ) {
							$orig_img_src[] = $searchNodes->getAttribute( 'src' );
							if(!empty($orig_img_src)){
								CoreFieldsImport::$media_instance->store_image_ids($i);
							}
						}		
						
						$media_dir = wp_get_upload_dir();
						$names = $media_dir['url'];
						
					}

					if(empty($post_values['post_status'])){
						global $wpdb;
						$post_id = $get_result[0]->ID;
						$Post_status_value = $wpdb->get_results("SELECT post_status FROM {$wpdb->prefix}posts WHERE id = '$post_id'");
						$post_values['post_status'] = $Post_status_value[0]->post_status;
					}
					if($post_values['post_status']== 'delete'){
						wp_delete_post($post_values['ID'],true);
					}else{									
						if(isset($post_values['post_content'])){
							$post_values['post_content'] = html_entity_decode($post_values['post_content']);																
							$post_values['post_content'] = str_replace('\n',"\n",$post_values['post_content']);
						}
						wp_update_post($post_values);
						//schedule time process where the update fails due to an ampersand (&) in the post_title
						$values_post = $wpdb->get_results("select ID,post_title,post_content from {$wpdb->prefix}posts where ID = '$post_id'",ARRAY_A);
						$post_title = !empty($values_post[0]['post_title']) ? $values_post[0]['post_title'] : '';
						$post_content = !empty($values_post[0]['post_content']) ? $values_post[0]['post_content'] : '';
						if(strpos($post_title,'&amp;') !== false){
							$post_title = str_replace('&amp;','&',$post_title);
							$wpdb->get_results("UPDATE {$wpdb->prefix}posts set post_title = '$post_title' where id = $post_id");
						}
						if(strpos($post_content,'&amp;') !== false){
							$post_content = str_replace('&amp;','&',$post_content);
							$wpdb->get_results("UPDATE {$wpdb->prefix}posts set post_content = '$post_content' where id = $post_id");
						}//schedule fix
					}
					
					if(isset($post_values['post_format'])){
						$this->post_format_function($post_id, $post_values['post_format']);
					}
					$status = $post_values['post_status'];
					$wpdb->get_results("UPDATE {$wpdb->prefix}posts set post_status = '$status' where id = $post_id");
					$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey_value'");
					// if(isset($post_values['specific_author'])) {
						$content=$this->openAI_response;
						if(!empty($content)){
							if($generated_content == 401) {
								$this->detailed_log[$line_number]['Message'] = 'Updated ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot update Content. Invalid API key provided. Please check your API key.";	
							}
							else if($generated_content == 429) {
								$this->detailed_log[$line_number]['Message'] = 'Updated ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot update Content. Rate limit reached for requests or You exceeded your current quota.";	
							}
							else if($generated_content == 500) {
								$this->detailed_log[$line_number]['Message'] = 'Updated ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot update Content. The server had an error while processing your request.";	
							}
							else if($generated_content == 503) {
								$this->detailed_log[$line_number]['Message'] = 'Updated ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot update Content. The engine is currently overloaded, please try again later.";	
							}
							else if($generated_short_description == 401) {
								$this->detailed_log[$line_number]['Message'] = 'Updated ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot update short description. Invalid API key provided. Please check your API key.";	
							}
							else if($generated_short_description == 429) {
								$this->detailed_log[$line_number]['Message'] = 'Updated ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot update short description. Rate limit reached for requests or You exceeded your current quota.";	
							}
							else if($generated_short_description == 500) {
								$this->detailed_log[$line_number]['Message'] = 'Updated ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot update short description. The server had an error while processing your request.";	
							}
							else if($generated_short_description == 503) {
								$this->detailed_log[$line_number]['Message'] = 'Updated ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author']. 	"<b style='color: red;'> Notice : </b>Cannot update short description. The engine is currently overloaded, please try again later.";	
							}
							else{
								$this->detailed_log[$line_number]['Message'] = 'Updated ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author'];
								$this->detailed_log[$line_number]['id'] = $post_id;
								$this->detailed_log[$line_number]['state'] = 'Updated';
								$author = explode('</b>',$post_values['specific_author']);
								$this->detailed_log[$line_number]['author'] = end($author);
							}
						}
						else{
							$this->detailed_log[$line_number]['Message'] = 'Updated' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author'];
							$this->detailed_log[$line_number]['id'] = $post_id;
							$this->detailed_log[$line_number]['state'] = 'Updated';
							$author = explode('</b>',$post_values['specific_author']);
							$this->detailed_log[$line_number]['author'] = end($author);
						}
				}else{
					//while Update(inserting[records not exists already ])
					if($check == 'post_title'){
						unset($post_values['ID']);
					}
					$post_id = wp_insert_post($post_values);
					$media_handle = get_option('smack_image_options');
					if($media_handle['media_settings']['media_handle_option'] == 'true' && !empty($post_values['featured_image'])){
						update_option('ultimate_csv_importer_pro_featured_image', $post_values['featured_image']);
					}
					// image handling code
					if($media_handle['media_settings']['media_handle_option'] == 'true' && !empty($post_values['featured_image'])){
						$post_values['featured_image'] = $this->check_for_featured_image_url($post_values['featured_image']);
						$attach_id = $this->featured_image_handling($media_handle, $post_values, $post_id, $type, $get_import_type, $unikey_value, $unikey_name, $header_array, $value_array,$hash_key,$templatekey,$line_number);
					}					
				
					if($media_handle['media_settings']['media_handle_option'] == 'true' && $media_handle['media_settings']['enable_postcontent_image'] == 'true'){
					
						if(preg_match("/<img/", $post_values['post_content'])) {

							$content = "<p>".$post_values['post_content']."</p>";
							$doc = new \DOMDocument();
							if(function_exists('mb_convert_encoding')) {
								@$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
							}else{
								@$doc->loadHTML( $content);
							}
							$searchNode = $doc->getElementsByTagName( "img" );
							if ( ! empty( $searchNode ) ) {
								foreach ( $searchNode as $searchNode ) {
									$orig_img_src = $searchNode->getAttribute( 'src' );
									$media_dir = wp_get_upload_dir();
									$names = $media_dir['url'];
									$image_type = 'inline';
									if (strpos($orig_img_src , $names) !== false) {
										$shortcode_img = $orig_img_src;
										$check_inline_image = $wpdb->get_results("SELECT $unikey_name FROM {$wpdb->prefix}ultimate_csv_importer_media_report WHERE $unikey_name = '$unikey_value'  AND image_type = 'inline' "); 										
											$image_table = $wpdb->prefix . "ultimate_csv_importer_media_report";
											$wpdb->get_results("INSERT INTO $image_table (`hash_key`,`templatekey`,`module`,`image_type`,`status`) VALUES ( '{$hash_key}','{$templatekey}','{$type}','{$image_type}','Completed')");										
									}
									else{
										$rand = mt_rand(1, 999);	
										$shortcode_table = $wpdb->prefix . "ultimate_csv_importer_shortcode_manager";
										$get_shortcode = $wpdb->get_results("SELECT `image_shortcode` FROM $shortcode_table WHERE original_image = '{$orig_img_src}' ",ARRAY_A);
										$image_table = $wpdb->prefix . "ultimate_csv_importer_media_report";
										$get_success_count = $wpdb->get_var("SELECT success_count FROM $image_table WHERE $unikey_name = '$unikey_value'  AND image_type = '$image_type' "); 
										//Record not exists 
										if(!$get_success_count)
										$wpdb->get_results("INSERT INTO $image_table (`hash_key`,`templatekey`,`module`,`image_type`,`status`) VALUES ( '{$hash_key}','{$templatekey}','{$type}','{$image_type}','Completed')");
										if(!empty($get_shortcode)) 
										{
											$shortcode_img = $get_shortcode[0]['image_shortcode'];
										}		
										else{
											$shortcode_img = 'inline_'.$rand.'_'.$orig_img_src;
										}
									}

									$temp_img = plugins_url("../assets/images/loading-image.jpg", __FILE__);
									$searchNode->setAttribute( 'src', $temp_img);
									$searchNode->setAttribute( 'alt', $shortcode_img );

								}
								$post_content              = $doc->saveHTML();
								$post_content = str_replace('\n',"\n",$post_content);
								$update_content = [
									'ID'           => $post_id,
									'post_content' => html_entity_decode($post_content, ENT_QUOTES, 'UTF-8')
								];
								wp_update_post($update_content);
							}
						}
					}
					if(!empty($post_values['wp_page_template']) && $type == 'Pages'){
						update_post_meta($post_id, '_wp_page_template', $post_values['wp_page_template']);
					}
					if(isset($post_values['post_format'])){
						if($post_values['post_format'] == 'post-format-video' ){
							$format = 'video';
						}
						else{
							$format=trim($post_values['post_format'],"post-format-");
						}
						set_post_format($post_id , $format);
					}
					$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey_value'");
					if(isset($post_values['post_content'])) {
					if(preg_match("/<img/", $post_values['post_content'])) {
						$shortcode_table = $wpdb->prefix . "ultimate_csv_importer_shortcode_manager";
						$doc = new \DOMDocument();
							$searchNode = $doc->getElementsByTagName( "img" );
							if ( ! empty( $searchNode ) ) {
								foreach ( $searchNode as $searchNode ) {
									$orig_img_src = $searchNode->getAttribute( 'src' ); 
								}
							}			
							$media_dir = wp_get_upload_dir();
							$names = $media_dir['url'];
						if(isset($orig_img_src)){							
								$shortcode  = 'inline';
								$wpdb->get_results("INSERT INTO $shortcode_table (image_shortcode , original_image , post_id,hash_key,templatekey) VALUES ( '{$shortcode}', '{$orig_img_src}', $post_id  ,'{$hash_key}','{$templatekey}')");														
						}						
					}
				}
					if(is_wp_error($post_id) || $post_id == '') {
						if(is_wp_error($post_id)) {
							$this->detailed_log[$line_number]['Message'] = "Can't insert this " . $post_values['post_type'] . ". " . $post_id->get_error_message();
							$this->detailed_log[$line_number]['state'] = 'Skipped';
						}
						else {
							$this->detailed_log[$line_number]['Message'] =  "Can't insert this " . $post_values['post_type'];
							$this->detailed_log[$line_number]['state'] = 'Skipped';
						}
						$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$unikey_value'");
					}
					else{
						// if(isset($post_values['specific_author'])) {
						$this->detailed_log[$line_number]['Message'] = 'Inserted ' . $post_values['post_type'] . ' ID: ' . $post_id . ', ' . $post_values['specific_author'];
						$this->detailed_log[$line_number]['id'] = $post_id;
						$this->detailed_log[$line_number]['state'] = 'Inserted';
						$author = explode('</b>',$post_values['specific_author']);
						$this->detailed_log[$line_number]['author'] = end($author);
						// }
					}
						if ((isset($post_values['post_parent'])) && (!is_numeric($post_values['post_parent'])) && (!empty($post_values['post_parent']))) {
							$p_type = $post_values['post_type'];
							$parent_title = $post_values['post_parent'];
							$parent_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_title = '$parent_title' and post_status !='trash' and post_type='$p_type'");
							$post_values['post_parent'] = $parent_id;
						}


					if($post_values['post_type'] == 'event' || $post_values['post_type'] == 'event-recurring'){
						$status = $post_values['post_status'];
						$wpdb->get_results("UPDATE {$wpdb->prefix}posts set post_status = '$status' where id = $post_id");
					}
				}

				if($unmatched_row == 'true'){
					global $wpdb;
					$post_entries_table = $wpdb->prefix ."post_entries_table";
					$file_table_name = $wpdb->prefix."smackcsv_file_events";
					$get_id  = $wpdb->get_results( "SELECT file_name  FROM $file_table_name WHERE `$unikey_name` = '$unikey_value'");	
					$file_name = $get_id[0]->file_name;
					$wpdb->get_results("INSERT INTO $post_entries_table (`ID`,`type`, `file_name`,`status`) VALUES ( '{$post_id}','{$type}', '{$file_name}','Updated')");
				}
			}
		}

			//update finished
	
			if (isset($this->detailed_log[$line_number]) && isset($this->detailed_log[$line_number]['Message']) && preg_match("(Can't|Skipped|Duplicate)", $this->detailed_log[$line_number]['Message']) === 0) {
				if ( $type == 'Posts' || $type == 'CustomPosts' || $type == 'Pages' || $type == 'Tickets') {
					if ( ! isset( $post_values['post_title'] ) ) {
						$post_values['post_title'] = '';
					}
					if ($gmode == 'Normal'){
						//$this->detailed_log[$line_number]['VERIFY'] = "<b> Click here to verify</b> - <a href='" . get_permalink( $post_id ) . "' target='_blank' title='" . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $post_values['post_title'] ) ) . "'rel='permalink'>Web View</a> | <a href='" . get_edit_post_link( $post_id, true ) . "'target='_blank' title='" . esc_attr( 'Edit this item' ) . "'>Admin View</a>";	
						$this->detailed_log[$line_number]['webLink'] = get_permalink( $post_id );
						$this->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id, true );
						$this->detailed_log[$line_number]['post_type'] = get_post_type($post_id);
					   $this->detailed_log[$line_number]['post_title'] = get_the_title($post_id);
					}
					else{
						if(empty($post_id)){
							$this->detailed_log[$line_number]['Message'] = 'Skipped';
							$this->detailed_log[$line_number]['state'] = 'Skipped';
						}
						else{
							$get_guid =$wpdb->get_results("select guid from {$wpdb->prefix}posts where ID= '$post_id'" ,ARRAY_A);
							$link = $get_guid[0]['guid'];

							$get_edit_link = get_edit_post_link( $post_id, true );
							if(empty($get_edit_link)){
								$get_edit_link = site_url().'/wp-admin/post.php?&post='.$post_id.'&action=edit';
							}
							$this->detailed_log[$line_number]['webLink'] = get_permalink( $post_id );
							$this->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id, true );
							$this->detailed_log[$line_number]['post_type'] = get_post_type($post_id);
						   	$this->detailed_log[$line_number]['post_title'] = get_the_title($post_id);	
						}
					}
			    }
				else{
					//$this->detailed_log[$line_number]['VERIFY'] = "<b> Click here to verify</b> - <a href='" . get_permalink( $post_id ) . "' target='_blank' title='" . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $post_values['post_title'] ) ) . "'rel='permalink'>Web View</a> | <a href='" . get_edit_post_link( $post_id, true ) . "'target='_blank' title='" . esc_attr( 'Edit this item' ) . "'>Admin View</a>";
					$this->detailed_log[$line_number]['webLink'] = get_permalink( $post_id );
					$this->detailed_log[$line_number]['adminLink'] = get_edit_post_link( $post_id, true ) ;
					$this->detailed_log[$line_number]['post_type'] = get_post_type($post_id);
					$this->detailed_log[$line_number]['post_title'] = get_the_title($post_id);	
				}
				$this->detailed_log[$line_number]['status'] = $post_values['post_status'];
			}						
			return $post_id;
		}
	}

	public function gfFormImport($header_array ,$value_array , $map, $post_id , $type, $mode, $line_number , $hash_key){
		global $wpdb;
		$helpers_instance = ImportHelpers::getInstance();
		$post_values = $helpers_instance->get_header_values($map , $header_array , $value_array,$hash_key);
		
		if (class_exists('GFAPI')) {
    
				
		foreach($post_values as $entryKey => $entryVal){
				$field_id_only = explode('_', $entryKey);
				$field_id_only = end($field_id_only);
				$post_values[$field_id_only] = $entryVal;
		}
			
				$result = \GFAPI::add_entry($post_values);
		
				if (is_wp_error($result)) {
					$error_message .= 'Error adding entry: ' . $result->get_error_message();
					$this->detailed_log[$line_number]['Message'] = $error_message;
            		$this->detailed_log[$line_number]['state'] = 'Skipped';
            		$wpdb->get_results("UPDATE $log_table_name SET skipped = $skipped_count WHERE $unikey_name = '$hask_key'");

            		return array('MODE' => $mode, 'ERROR_MSG' => $error_message);
				} 
		}
		
     
		$form_id = $post_values['form_id'];
		$entry_id = $post_values['entry_id'];	
		$entry_view_url = admin_url("admin.php?page=gf_entries&view=entry&id=$form_id&lid=$result");
        $mode_of_affect = 'Inserted';
		$this->detailed_log[$line_number]['Message'] = 'Inserted GF Entries ID: ' . $result;	
		$this->detailed_log[$line_number]['id'] = $result;
        $this->detailed_log[$line_number]['state'] = 'Inserted';
		$this->detailed_log[$line_number]['status'] = 'Publish';
		$this->detailed_log[$line_number]['adminLink'] = $entry_view_url;
		$this->detailed_log[$line_number]['webLink'] = $entry_view_url;
        $wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");
        $returnArr['ID'] = $result;
        $returnArr['MODE'] = $mode_of_affect;
        return $returnArr;
    
	}

	public function multiLang($post_values){
		global $wpdb;
		if (strpos($post_values['post_title'], '|') !== false) {
			$exploded_title = explode('|', $post_values['post_title']);
			$post_values['post_title'] = $exploded_title[0];
			$lang_title = $exploded_title[1];

		}
		if (strpos($post_values['post_content'], '|') !== false) {
			$exploded_content = explode('|', $post_values['post_content']);
			$post_values['post_content'] = $exploded_content[0];
			$lang_content = $exploded_content[1];
		}
		if (strpos($post_values['post_excerpt'], '|') !== false) {
			$exploded_excerpt = explode('|', $post_values['post_excerpt']);
			$post_values['post_excerpt'] = $exploded_excerpt[0];
			$lang_excerpt = $exploded_excerpt[1];
		}
		$lang_code = $post_values['lang_code'];
		$post_id = wp_insert_post($post_values);
		$wpdb->get_results("INSERT INTO {$wpdb->prefix}mltlngg_translate (post_ID , post_content , post_excerpt, post_title,`language`) VALUES ( $post_id, '{$lang_content}', '{$lang_excerpt}' , '{$lang_title}', '{$lang_code}')");
		return $post_id;
	}

	public function postExpirator($post_id,$post_values){
		if(!empty($post_values['post_expirator_status'])){
			$post_values['post_expirator_status'] = array('expireType' => $post_values['post_expirator_status'],'id' => $post_id);
		}
		else{
			$post_values['post_expirator_status'] = array('expireType' => 'draft' ,'id' => $post_id);
		}

		if(!empty($post_values['post_expirator'])){
			update_post_meta($post_id, '_expiration-date-status', 'saved');
			$estimate_date = $post_values['post_expirator'];
			$estimator_date = get_gmt_from_date("$estimate_date",'U');
			update_post_meta($post_id, '_expiration-date', $estimator_date);
			update_post_meta($post_id, '_expiration-date-options', $post_values['post_expirator_status']);			
		}	
	}


	public function image_handling($id,$attach_id,$index){
		global $wpdb;
		// Retrieve the current post content
		$get_result = $wpdb->get_results("SELECT post_content FROM {$wpdb->prefix}posts WHERE ID = $id", ARRAY_A);
		if (empty($get_result)) {
			$post_content = '';
		} else {
			$post_content = htmlspecialchars_decode($get_result[0]['post_content']);
		}
		// Retrieve the attachment URL
		$get_guid = $wpdb->get_results("SELECT guid FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND ID = $attach_id", ARRAY_A);
		if (empty($get_guid)) {
			return null; // Return if the attachment is not found
		}
		$new_img_src = $get_guid[0]['guid'];
		// Load the post content into DOMDocument
		$doc = new \DOMDocument();
		if (function_exists('mb_convert_encoding')) {
			@$doc->loadHTML(mb_convert_encoding($post_content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		} else {
			@$doc->loadHTML($post_content);
		}
	
		// Get all img tags
		$img_tags = $doc->getElementsByTagName('img');
		// Update the img tag at the specified index
		if ($img_tags->length > $index) {
			$img_tags->item($index)->setAttribute('src', $new_img_src);
		} else {
			return null; // Return if the index is out of bounds
		}
	
		// Save the updated content
		$result = $doc->saveHTML();
		$update_content = [
			'ID' => $id,
			'post_content' => $result,
		];
	
		// Update the post content
		$check = wp_update_post($update_content);
		return $attach_id;
	}

	function import_core_fields($data_array, $mode = null, $line_number = null){
		$helpers_instance = ImportHelpers::getInstance();
	  foreach ($data_array as $innerKey => $innerValue) {
			if (strpos($innerKey, '->openAI') !== false) {
					$OpenAIHelper = new OpenAIHelper;
					$newKey = str_replace('->openAI', '', $innerKey);
					$data_array[$newKey] = $OpenAIHelper->generateContent($innerValue, '');
									
			}
			if (stripos($innerKey, 'openAI') !== false) 	{
        			unset($data_array[$innerKey]);
   			}
		}
			$data_array = $this->validateDate($data_array,$mode,$line_number);	
		if(!isset($data_array['post_author']) && $mode != 'Update') {
			$data_array['post_author'] = 1;
		} else {
			if(isset( $data_array['post_author'] )) {
				$user_records = $helpers_instance->get_from_user_details( $data_array['post_author'] );
				$data_array['post_author'] = $user_records['user_id'];
				$data_array['specific_author'] = $user_records['message'];
			}
		}
		if ( !empty($data_array['post_status']) ) {
			$data_array = $helpers_instance->assign_post_status( $data_array );
		}else{
			$data_array['post_status'] = 'publish';
		}
		return $data_array;
	}

	public function validateDate($data_array,$mode,$line_number) {
		if(empty( $data_array['post_date'] )) {
			if($mode == 'insert'){
			$data_array['post_date'] = current_time('Y-m-d H:i:s');
			}
			else {
				//For update
				return $data_array;
			}
		} else {					
			//Validate the date
			if(strtotime( $data_array['post_date'] )) {	
				if(strtotime($data_array['post_date'])> 0)	{
					if (strpos($data_array['post_date'], '.') !== false) {
						$data_array['post_date'] = str_replace('.', '-', $data_array['post_date']);
					}
					$data_array['post_date'] = date( 'Y-m-d H:i:s', strtotime( $data_array['post_date'] ) );									
				}								
				else{
					if($data_array['post_date'] == '0000-00-00T00:00' || $data_array['post_date'] == '0000-00-00'){
						$this->detailed_log[$line_number]["Message"] = "Skipped, Date format provided is wrong. Correct date format is 'YYYY-MM-DD' ";
						$this->detailed_log[$line_number]['state'] = 'Skipped';
					}	
					else{
						$data_array['post_date'] = current_time('Y-m-d H:i:s');
					}
				}									
			} 
			else {				
				//check the date format as 18/05/2022 (valid)
				$data_array['post_date'] = str_replace('/', '-', $data_array['post_date']);
			
				if(!strtotime( $data_array['post_date'])){						
					//check the date format as mm-dd-yyyy (valid)
					$data_array['post_date'] = str_replace(array('.','-'), '/', $data_array['post_date']);
					
					if(!strtotime($data_array['post_date'])){							
						//Wrong format (Not valid date)		
						$this->detailed_log[$line_number]["Message"] = "Skipped, Date format provided is wrong. Correct date format is 'YYYY-MM-DD' ";
						$this->detailed_log[$line_number]['state'] = 'Skipped';					
					}
					else {
						$data_array['post_date'] = date( 'Y-m-d H:i:s', strtotime( $data_array['post_date'] ) );
					}								
				}
				else {					
					//Valid date
					$data_array['post_date'] = date( 'Y-m-d H:i:s', strtotime( $data_array['post_date'] ) );
				}
			}
		}		
		return $data_array;
	}

	public function custom_fields_update_based_on($update_based_on, $custom_array, $check, $header_array, $value_array,$type,$line_number){
		global $wpdb;	
		if(is_array($custom_array)){
			$check = trim($check);	
			foreach($custom_array as $custom_key => $custom_value){
				if (strpos($custom_value, '{') !== false && strpos($custom_value, '}') !== false) {
					$custom_value = $custom_key;
				}
				if(trim($custom_key) == trim($check)){
					$get_key= array_search($custom_value , $header_array);
				}
				if($get_key !== false && isset($value_array[$get_key])) {
					$csv_element = $value_array[$get_key];	
				}	
				if(!empty($csv_element) && ($update_based_on == 'acf' || $update_based_on == 'pods')){
					$get_result = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta as a join {$wpdb->prefix}posts as b on a.post_id = b.ID WHERE a.meta_key = '$check' AND a.meta_value = '$csv_element' AND b.post_status != 'trash' order by a.post_id DESC ");					
				}				
				elseif(!empty($csv_element) && $update_based_on == 'toolset'){ 
					$meta_key = 'wpcf-'.$check;
					$get_result = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta as a join {$wpdb->prefix}posts as b on a.post_id = b.ID WHERE a.meta_key = '$meta_key' AND a.meta_value = '$csv_element' AND b.post_status != 'trash' order by a.post_id DESC ");
				}
				elseif (!empty($csv_element) && $update_based_on == 'jetengine') {
					$get_result = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta as a join {$wpdb->prefix}posts as b on a.post_id = b.ID WHERE a.meta_key = '$check' AND a.meta_value = '$csv_element' AND b.post_status != 'trash' AND b.post_type !='revision' order by a.post_id DESC ");
				}
				elseif(!empty($csv_element) &&  $update_based_on == 'metabox'){
					// $import_type = $extension_object->import_type_as($type);
					// $import_as = $extension_object->import_post_types($import_type );

					$get_metabox_fields = \rwmb_get_object_fields($type); 
					$storage_type = isset($get_metabox_fields[$check]['storage']) ? $get_metabox_fields[$check]['storage'] : "";
					if($storage_type != "" && isset($storage_type->table)){	
						$customtable = $storage_type->table;
						$get_result = $wpdb->get_results("SELECT c.ID FROM $customtable as c inner join {$wpdb->prefix}posts as p ON p.ID=c.ID where c.$check='$csv_element' and p.post_status!='trash' order by p.ID ASC");
						
					}
					else{
						$get_result = $wpdb->get_results("SELECT post_id FROM {$wpdb->prefix}postmeta as a join {$wpdb->prefix}posts as b on a.post_id = b.ID WHERE a.meta_key = '$check' AND a.meta_value = '$csv_element' AND b.post_status != 'trash' order by a.post_id DESC ");					
					}


				}
			}		
			// if(empty($get_result)) {
			// 	$this->detailed_log[$line_number]['Message'] = 'Skipped,Due to existing field value is not presents.';
			// 	$this->detailed_log[$line_number]['state'] = 'Skipped';
			// }				
			return $get_result;
		}		
	}

	public function post_format_function($post_id, $post_format_value){
		$format=str_replace("post-format-","",$post_format_value);
			set_post_format($post_id ,$format );
	}
	
	public function featured_image_handling($media_handle, $post_values, $post_id, $type, $get_import_type, $unikey_value, $unikey_name, $header_array, $value_array,$hash_key,$templatekey,$line_number= null){		
		global $wpdb;
		//added this condition, bcoz header and value array are not available during images schedule - so storing those datas prior in db
		if($media_handle['media_settings']['media_handle_option'] == 'true' && !empty($post_values['featured_image']) && (!empty($media_handle['media_settings']['file_name']) || !empty($media_handle['media_settings']['alttext']) || !empty($media_handle['media_settings']['description']) || !empty($media_handle['media_settings']['caption']) || !empty($media_handle['media_settings']['title']))){
			$media_seo_array = [];
			$media_seo_array['header_array'] = $header_array;
			$media_seo_array['value_array'] = $value_array;
			update_option('smack_media_seo'.$unikey_value, $media_seo_array);
		}
	    $image_meta_value = array(
			'headerarray' => $header_array,
			'valuearray' => $value_array
		);
		$image_meta_value  =json_encode($image_meta_value);
		if($media_handle['media_settings']['use_ExistingImage'] == 'true'  &&  isset($post_values['featured_image'])){
			$image_type = 'Featured';		
			CoreFieldsImport::$media_instance->store_image_ids($i=1);
			$f_image = $post_values['featured_image'];
			$f_path = CoreFieldsImport::$media_instance->get_filename_path($post_values['featured_image'],'');
			$fimg_name = isset($f_path['fimg_name']) ? $f_path['fimg_name'] : '';
			
			$check_featured_image = $wpdb->get_results("SELECT $unikey_name FROM {$wpdb->prefix}ultimate_csv_importer_media_report WHERE $unikey_name = '$unikey_value'  AND image_type = 'Featured' "); 
			if(empty($check_featured_image)){				
				$image_media_table = $wpdb->prefix . "ultimate_csv_importer_media_report";
				$wpdb->get_results("INSERT INTO $image_media_table (`hash_key`,`templatekey`,`module`,`image_type`,`status`) VALUES ( '{$hash_key}','{$templatekey}','{$type}','{$image_type}','Completed') ");
			}
			$wp_content_url = content_url();			
				if(strpos($f_image, $wp_content_url) !== FALSE){
					$attachment_id = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND guid = '$f_image' ", ARRAY_A);
				}
				else{
					if (!empty($media_handle['media_settings']['title'])) {
						$image_title   = $media_handle['media_settings']['title'];
						$fil_name = esc_sql($image_title);
						$attachment_id = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND post_title = '$fil_name' LIMIT 1", ARRAY_A);
					}else{
						$attachment_id = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND guid LIKE '%$fimg_name%'", ARRAY_A);
					}
				}
				if(!empty($attachment_id[0]['ID'])){
					$table_name = $wpdb->prefix . 'smackcsv_file_events';
					$file_name = $wpdb->get_var("SELECT file_name FROM $table_name WHERE hash_key = '$hash_key'");
					$shortcode_table = $wpdb->prefix . "ultimate_csv_importer_shortcode_manager";                                                                   
					$attach_id = $attachment_id[0]['ID'];
					$check_id = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE ID ='{$attach_id}' AND post_title ='image-failed' AND post_type = 'attachment'", ARRAY_A);
					if(!empty($check_id)){
						$failed_ids = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE post_id='{$post_id}' AND media_id = '{$attach_id}' AND image_shortcode = 'Featured_image_'");
						if(!empty($failed_ids) && $failed_ids[0]->post_id != $post_id){
							$attach_id = $failed_ids[0]->media_id;
							$insert_status = $wpdb->insert($shortcode_table,
							array(
								'post_id' => $post_id,
								'post_title' => $failed_ids[0]->post_title,
								'image_shortcode' => $failed_ids[0]->image_shortcode,
								'media_id' => $failed_ids[0]->media_id,
								'original_image' => $failed_ids[0]->original_image,
								'hash_key' => $failed_ids[0]->hash_key,
								'import_type' => $failed_ids[0]->import_type,
								'file_name' => $failed_ids[0]->file_name
							),
							array('%d','%s','%s','%d','%s','%s','%s','%s')
							);
							CoreFieldsImport::$media_instance->store_failed_image_ids($attach_id);
							CoreFieldsImport::$media_instance->failed_media_data($line_number,$failed_ids[0]->post_id,$failed_ids[0]->post_title,$failed_ids[0]->media_id,$failed_ids[0]->original_image);		
						}elseif(empty($failed_ids) ){
							$insert_status = $wpdb->insert($shortcode_table,
							array(
								'post_id' => $post_id,
								'post_title' => $post_values['post_title'],
								'image_shortcode' => 'Featured_image_',
								'media_id' => $attach_id,
								'original_image' => $post_values['featured_image'],
								'hash_key' => $hash_key,
								'import_type' => $get_import_type,
								'file_name' => $file_name,
							),
							array('%d','%s','%s','%d','%s','%s','%s','%s')
							); 
							CoreFieldsImport::$media_instance->store_failed_image_ids($attach_id);
							CoreFieldsImport::$media_instance->failed_media_data($line_number,$post_id,$post_values['post_title'],$attach_id,$post_values['featured_image']);
						}elseif(!empty($failed_ids) && $failed_ids[0]->post_id == $post_id){
							CoreFieldsImport::$media_instance->store_failed_image_ids($failed_ids[0]->media_id);
							CoreFieldsImport::$media_instance->failed_media_data($line_number,$failed_ids[0]->post_id,$failed_ids[0]->post_title,$failed_ids[0]->media_id,$failed_ids[0]->original_image);
						}
						CoreFieldsImport::$media_instance->imageMetaImport($attach_id,$media_handle);
						set_post_thumbnail($post_id, $attach_id);
					}else{
						CoreFieldsImport::$media_instance->imageMetaImport($attach_id,$media_handle);
						set_post_thumbnail($post_id, $attach_id);
					}             
				}
				else{
					$original_featured_image = get_option('ultimate_csv_importer_pro_featured_image');	
					delete_option('ultimate_csv_importer_pro_featured_image');
					$post_values['featured_image'] = $original_featured_image;
					$image_type = 'Featured';
					$attach_id = CoreFieldsImport::$media_instance->image_meta_table_entry($line_number,$post_values,$post_id ,'',$post_values['featured_image'], $hash_key ,$image_type,$get_import_type,'','',$header_array,$value_array);
					set_post_thumbnail( $post_id, $attach_id );
				}
		}
		else{
			$image_type = 'Featured';
			$check_featured_image = $wpdb->get_results("SELECT $unikey_name FROM {$wpdb->prefix}ultimate_csv_importer_media_report WHERE $unikey_name = '$unikey_value'  AND image_type = 'Featured' "); 
			if(empty($check_featured_image)){
				
				$image_media_table = $wpdb->prefix . "ultimate_csv_importer_media_report";
				$wpdb->get_results("INSERT INTO $image_media_table (`hash_key`,`templatekey`,`module`,`image_type`,`status`) VALUES ( '{$hash_key}','{$templatekey}','{$type}','{$image_type}','Completed') ");
			}
			$original_featured_image = get_option('ultimate_csv_importer_pro_featured_image');
			
			delete_option('ultimate_csv_importer_pro_featured_image');

			$featured_image = $post_values['featured_image'];	
			$image_type = 'Featured';
			if(!empty($featured_image)){
				CoreFieldsImport::$media_instance->store_image_ids($i=1);
			}
			$attach_id = CoreFieldsImport::$media_instance->image_meta_table_entry($line_number,$post_values,$post_id ,'',$featured_image, $hash_key ,$image_type,$get_import_type,'','',$header_array,$value_array);
			if(isset($attach_id)){
				$f_image = $post_values['featured_image'];
				$failed_ids = $wpdb->get_results("SELECT media_id FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE media_id = '{$attach_id}' AND original_image = '{$f_image}' AND image_shortcode = 'Featured_image_'");
				if(!empty($failed_ids[0]->media_id)){
					$failed_id = $failed_ids[0]->media_id;
					CoreFieldsImport::$media_instance->store_failed_image_ids($failed_id); 
					CoreFieldsImport::$media_instance->failed_media_data($line_number,$failed_ids[0]->post_id,$failed_ids[0]->post_title,$failed_ids[0]->media_id,$failed_ids[0]->original_image);
				}
				CoreFieldsImport::$media_instance->imageMetaImport($attach_id,$media_handle); 
				set_post_thumbnail( $post_id, $attach_id );
			}	
		}
		$attach_id=isset($attach_id)?$attach_id:'';
		return $attach_id;
	}

	public function check_for_featured_image_url($featured_image){
		if (strpos($featured_image, '|') !== false) {
			$featured_img = explode('|', $featured_image);
			$featured_image_url = $featured_img[0];					
		}
		else if (strpos($featured_image, ',') !== false) {
			$feature_img = explode(',', $featured_image);
			$featured_image_url = $feature_img[0];
		}
		else{
			$featured_image_url = $featured_image;
		}
		return $featured_image_url;
	}
	//TODO: temporary fix for order edit url
	public function get_order_url($post_id){
		$dir=site_url().'/wp-admin/admin.php?page=wc-orders&action=edit&id='.$post_id;
		return  $dir;

	}

	function get_matching_posts_by_category($helpers_instance, $post_cat_list, $categoryList ,$header_array, $value_array, $import_type, $import_as, $wpdb,$get_result,$mode) {
		// Fetch category values
		$post_cat_values = $helpers_instance->get_header_values($post_cat_list, $header_array, $value_array);
		//product_category
		if (empty($post_cat_values['post_category']) && empty($post_cat_values['product_category']) ) {
			return []; // Return empty array if no categories are provided
		}else{
			$csv_categories_values = !empty($post_cat_values['post_category']) ? $post_cat_values['post_category'] : $post_cat_values['product_category'];
		}
		// Extract categories
		$post_category_list = [];
		foreach (explode(',', $csv_categories_values) as $category) {
			$post_category_list = array_merge($post_category_list, explode('>', $category));
		}
	
		// Get all category IDs
		$category_ids = CoreFieldsImport::$mappingInstance->get_category_ids_from_names($categoryList, $import_type);
		// Get category IDs for extracted categories
		$csv_category_ids = CoreFieldsImport::$mappingInstance->get_category_ids_from_names($post_category_list, $import_type);
		// Find the matching category IDs
		$matching_category_ids = array_intersect($csv_category_ids, $category_ids);
		if (empty($matching_category_ids)) {
			return false;
		}
		// Prepare placeholders for SQL IN clause
		if($mode == 'Insert'){
			return true;
		}else{
			$placeholders = implode(',', array_fill(0, count($matching_category_ids), '%d'));
	
			// Prepare the query
			$query = $wpdb->prepare(
				"SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p
				INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
				INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE p.post_type = %s
				AND p.post_status != 'trash'
				AND tt.term_id IN ($placeholders)
				ORDER BY p.ID DESC",
				array_merge([$import_as], $matching_category_ids)
			);
		
			// Execute Query (fetch as objects)
			$get_result_ids = $wpdb->get_results($query);
	
			$get_result_ids_array = array_map(fn($obj) => $obj->ID, $get_result_ids);
			$get_result_array = array_map(fn($obj) => $obj->ID, $get_result);

			// Find the intersection
			$common_ids = array_intersect($get_result_array,$get_result_ids_array);
			$get_result = !empty($common_ids) ? true : false;
			return $get_result;
		}
	}
	
	
}