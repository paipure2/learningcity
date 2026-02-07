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

class DefaultExtension extends ExtensionHandler{
	private static $instance = null;

	public static function getInstance() {
		if (DefaultExtension::$instance == null) {
			DefaultExtension::$instance = new DefaultExtension;
		}
		return DefaultExtension::$instance;
	}

	/**
	* Provides default mapping fields for specific post type or taxonomies
	* @param string $data - selected import type
	* @return array - mapping fields
	*/
	public function processExtension($data){	
		$mode = isset($_POST['Mode']) ? sanitize_text_field($_POST['Mode']) : '';		
		$import_types = $data;
		$import_type = $this->import_name_as($import_types);
		$response = [];
		if ($import_type != 'Users' && $import_type != 'Taxonomies' && $import_type != 'CustomerReviews' && $import_type != 'Categories' && $import_type != 'WooCommerceCustomer' && $import_type != 'Comments' && $import_type != 'WooCommerceVariations' && $import_type != 'WooCommerceOrders' && $import_type != 'WooCommerceCoupons' && $import_type != 'WooCommerceRefunds' && $import_type != 'WooCommerceCategories' && $import_type != 'WooCommerceattribute' && $import_type != 'WooCommercetags' && $import_type != 'ngg_pictures' && $import_types != 'JetBooking' && $import_types != 'lp_order' && $import_types != 'Images' && $import_types != 'nav_menu_item' && $import_types != 'widgets' && $import_type != 'WooCommerceReviews' && $import_types != 'JetReviews' && $import_type != 'GFEntries') {
			if($import_type != 'CustomPosts'){
				
				$wordpressfields = array(
                    'Title' => 'post_title',
                    'ID' => 'ID',
                    'Content' => 'post_content',
                    'Short Description' => 'post_excerpt',
                    'Publish Date' => 'post_date',
                    'Slug' => 'post_name',
                    'Author' => 'post_author',
                	'Status' => 'post_status',
                    'Featured Image' => 'featured_image'    
					);		
			}
			
			
			
			
			if(is_plugin_active('multilanguage/multilanguage.php')) {
				$wordpressfields['Language Code'] = 'lang_code';
			}
			if(is_plugin_active('post-expirator/post-expirator.php')) {
				$wordpressfields['Post Expirator'] = 'post_expirator';
				$wordpressfields['Post Expirator Status'] = 'post_expirator_status';
			}		
			if ($import_type === 'Posts') { 
				$wordpressfields['Format'] = 'post_format';
				$wordpressfields['Comment Status'] = 'comment_status';
				$wordpressfields['Ping Status'] = 'ping_status';
				$wordpressfields['Track Options'] = 'pinged';
			}
			if($import_type === 'CustomPosts'){
			
				if($import_types == 'elementor_library'){
				
						$wordpressfields = array(
						'ID' => 'ID',
						'Template title' => 'Template title',
						'Template content' => 'Template content',
						'Style'=> 'Style',
						'Template type' => 'Template type',
						'Created time' => 'Created time',
						'Created by' => 'Created by',
						'Template status' => 'Template status',
						'Category'=> 'Category'
						);		
				}
				else{
					$wordpressfields = array(
						'Title' => 'post_title',
						'ID' => 'ID',
						'Content' => 'post_content',
						'Short Description' => 'post_excerpt',
						'Publish Date' => 'post_date',
						'Slug' => 'post_name',
						'Author' => 'post_author',
						'Status' => 'post_status',
						'Featured Image' => 'featured_image'    
						);		
					$wordpressfields['Page Template'] = 'wp_page_template'; 
					$wordpressfields['Format'] = 'post_format';
					$wordpressfields['Comment Status'] = 'comment_status';
					$wordpressfields['Ping Status'] = 'ping_status';
					$wordpressfields['Track Options'] = 'pinged';
					$wordpressfields['Parent'] = 'post_parent';
					$wordpressfields['Order'] = 'menu_order';
				}

			}
			if ($import_type === 'Pages') {
				$wordpressfields['Parent'] = 'post_parent';
				$wordpressfields['Order'] = 'menu_order';
				$wordpressfields['Page Template'] = 'wp_page_template';
				$wordpressfields['Comment Status'] = 'comment_status';
				$wordpressfields['Ping Status'] = 'ping_status';
			}
			if($import_type == 'WooCommerce' || $import_type == 'WPeCommerce'){				
				$wordpressfields['PRODUCT SKU'] = 'PRODUCTSKU';				
			}

			if($mode == 'Insert'){
				unset($wordpressfields['ID']);
				if($import_type != 'WooCommerce'){
					unset($wordpressfields['PRODUCT SKU']);
				}
			}

			if($import_types == 'lp_lesson'){
				unset($wordpressfields['Format']);
				unset($wordpressfields['Featured Image']);
				unset($wordpressfields['Short Description']);
				unset($wordpressfields['Author']);
				unset($wordpressfields['Parent']);
				unset($wordpressfields['Order']);
			}

			if($import_types == 'lp_quiz' || $import_types == 'lp_question' || $import_types == 'wp_font_face' || $import_types == 'wp_font_family' || $import_types == 'wp_global_style' || $import_types == 'wp_template' || $import_types == 'wp_template_part' ){
				unset($wordpressfields['Format']);
				unset($wordpressfields['Featured Image']);
				unset($wordpressfields['Short Description']);
				unset($wordpressfields['Author']);
				unset($wordpressfields['Comment Status']);
				unset($wordpressfields['Ping Status']);
				unset($wordpressfields['Track Options']);
				unset($wordpressfields['Parent']);
				unset($wordpressfields['Order']);
			}
		} 
		if($import_types == 'lp_order'){
			$wordpressfields = array(
				'Order Status' => 'order_status',
				'Order Date' => 'order_date',
				'Order ID' => 'ORDER_ID'
			);
			if($mode == 'Insert'){
				unset($wordpressfields['Order ID']);
			}
		}
		if($import_types == 'nav_menu_item'){
			$wordpressfields = array(
				'Menu Title' => 'menu_title',
				'Menu Type' => '_menu_item_type',
				'Menu Items' => '_menu_item_object',
				'Menu Item Ids' => '_menu_item_object_id',
				'Menu Custom Url' => '_menu_item_url',
				'Menu Auto Add' => 'menu_auto_add'
			); 

			$get_navigation_locations = get_nav_menu_locations();
			foreach($get_navigation_locations as $nav_key => $nav_values){
				$wordpressfields[$nav_key] = $nav_key;
			}
		}

		if($import_types == 'widgets') {
			$wordpressfields = array(
				'Recent Posts'   => 'widget_recent-posts',
				'Pages'          => 'widget_pages',
				'Recent Comments'=> 'widget_recent-comments',
				'Archieves' => 'widget_archives',
				'Categories'     => 'widget_categories'
			);
		}

		if($import_type == 'WPeCommerceCoupons' ) {
			$wordpressfields = array(
					'Coupon Code' => 'coupon_code',
					'Coupon Id' => 'COUPONID',
					'Description' => 'description',
					'Status' => 'coupon_status',
					'Discount' => 'discount',
					'Discount Type' => 'discount_type',
					'Start' => 'start',
					'Expiry' => 'expiry',
					'Use Once' => 'use_once',
					'Apply On All Products' => 'apply_on_all_products',
					'Conditions' => 'conditions'
					);
			if($mode == 'Insert'){
				unset($wordpressfields['Coupon Id']);
			}
		}

		if($import_type == 'Users' || $import_type == 'WooCommerceCustomer'){
			$wordpressfields = array(
					'User Login' => 'user_login',
					'User Pass' => 'user_pass',
					'First Name' => 'first_name',
					'Last Name' => 'last_name',
					'Nick Name' => 'nickname',
					'User Email' => 'user_email',
					'User URL' => 'user_url',
					'User Nicename' => 'user_nicename',
					'User Registered' => 'user_registered',
					'Display Name' => 'display_name',
					'User Role' => 'role',
					'Biographical Info' => 'biographical_info',
					'Disable Visual Editor' => 'disable_visual_editor',
					'Syntax Highlighting' => 'syntax_highlighting',
					'Admin Color Scheme' => 'admin_color',
					'Enable Keyboard Shortcuts' => 'enable_keyboard_shortcuts',
					'Show Toolbar' => 'show_toolbar',
					'Language' => 'language',
					'ID' => 'ID'
					);
			if($mode == 'Insert'){
				unset($wordpressfields['ID']);
			}
		}
		if($import_type === 'Comments') {
			$wordpressfields = array(
					'Comment Post Id' => 'comment_post_ID',
					'Comment Post Title' => 'post_title',
					'Comment Author' => 'comment_author',
					'Comment Author Email' => 'comment_author_email',
					'Comment Author URL' => 'comment_author_url',
					'Comment Content' => 'comment_content',
					'Comment Rating' => 'comment_rating',
					'Comment Author IP' => 'comment_author_IP',
					'Comment Date' => 'comment_date',
					'Comment Approved' => 'comment_approved',
					'Comment Parent' => 'comment_parent', 
					'user_id'=>'user_id',
					);
		}
		if($import_type === 'WooCommerceReviews') {
			$wordpressfields = array(
					'Review Product Id' => 'comment_post_ID',
					'Review Post Title' => 'post_title',
					'Review Author' => 'comment_author',
					'Review Author Email' => 'comment_author_email',
					'Review Author URL' => 'comment_author_url',
					'Review Content' => 'comment_content',
					'Review Rating' => 'comment_rating',
					'Review Author IP' => 'comment_author_IP',
					'Review Date' => 'comment_date',
					'Review Approved' => 'comment_approved',
					'Review Parent' => 'comment_parent', 
					'user_id'=>'user_id',
					);
		}

		if($import_types === 'JetReviews') {
			$wordpressfields = array(
				'ID' => 'ID',
				'Review Post Id' => 'post_id',                // The ID of the post being reviewed
				'Review Source' => 'source',                  // Source of the review (e.g., 'post')
				'Review Post Type' => 'post_type',            // Type of post (e.g., 'post', 'page', etc.)
				'Review Author' => 'author',                  // ID of the author who wrote the review
				'Review Date' => 'date',                      // Date of the review
				'Review Title' => 'title',                    // Title of the review
				'Review Content' => 'content',                // Content of the review
				'Review Type Slug' => 'type_slug',            // Slug of the review type (e.g., 'default', etc.)
				'Review Rating Data' => 'rating_data',        // Serialized rating data
				'Review Rating' => 'rating',                  // Rating score (e.g., 100)
				'Review Likes' => 'likes',                    // Number of likes
				'Review Dislikes' => 'dislikes',              // Number of dislikes
				'Review Approved' => 'approved',              // Whether the review is approved (1 or 0)
				'Review Pinned' => 'pinned',                  // Whether the review is pinned (1 or 0)
			);
			if($mode == 'Insert'){
				unset($wordpressfields['ID']);
			}
		}

		
		if($import_type === 'Images') {
			$wordpressfields = array(
				    'Media ID'    => 'media_id',
					'Caption' => 'caption',
					'Alt text' => 'alt_text',
					'Description' => 'description',
					'File Name' => 'file_name',
					'Title' => 'title',				
					'Image URL' => 'actual_url'
				);
		}
		if($import_type === 'ngg_pictures') {
			$wordpressfields = array(
					 'ID' =>'id',
					 'Filename' => 'filename',
					'Alt text' => 'alt_text',
					'Description' => 'description',
					'Featured Image' => 'featured_image',
					'Nextgen Gallery' => 'nextgen_gallery',
					'Manage Tags' =>'manage_tags');
		}
		if($import_type === 'Taxonomies') {
			$wordpressfields = array(
					'Taxonomy Name' => 'name',
					'Taxonomy Slug' => 'slug',
					'Taxonomy Description' => 'description',
					'Term ID' => 'TERMID',
					'Parent' => 'parent'
					);
			if($mode == 'Insert'){
				unset($wordpressfields['Term ID']);
			}
		}
		if($import_type === 'Categories') {
			$wordpressfields = array(
					'Category Name' => 'name',
					'Category Slug' => 'slug',
					'Category Description' => 'description',                        
					'Parent' => 'parent',
					'Term ID' => 'TERMID'
					);
			if($mode == 'Insert'){
				unset($wordpressfields['Term ID']);
			}	
			if($import_types == 'product_cat'){
				$wordpressfields['Category Image'] = 'image';
				$wordpressfields['Display type'] = 'display_type';
				$wordpressfields['Top Content'] = 'top_content';
				$wordpressfields['Bottom Content'] = 'bottom_content';
			}elseif($import_types == 'wpsc_product_category'){
				$wordpressfields['Category Image'] = 'image';
			}elseif($import_types == 'event-categories'){
				$wordpressfields['Category Image'] = 'image';
				$wordpressfields['Category Color'] = 'color';
			}
		}
		if($import_type === 'Tags') {
			$wordpressfields = array(
					'Tag Name' => 'name',
					'Tag Slug' => 'slug',
					'Tag Description' => 'description',
					'Term ID' => 'TERMID',
					);
			if($mode == 'Insert'){
				unset($wordpressfields['Term ID']);
			}if($import_types == 'event-tags'){
				$wordpressfields['Tag Image'] = 'image';
				$wordpressfields['Tag Color'] = 'color';
			}	
		}

		if($import_type === 'WooCommerceOrders'){
			$wordpressfields = array(
				    'Order Id' => 'ORDERID',
					'Customer Note' => 'customer_note',
					'Order Status' => 'order_status',
					'Order Date' => 'order_date'
					);
			if($mode == 'Insert'){
				unset($wordpressfields['Order Id']);
			}
		}

		if($import_type === 'WooCommerceCoupons'){
			$wordpressfields = array(
					'Coupon Code' => 'coupon_code',
					'Description' => 'description',
					'Date' => 'coupon_date',
					'Status' => 'coupon_status',
					'Coupon Id' =>'COUPONID'
					);
			if($mode == 'Insert'){
				unset($wordpressfields['Coupon Id']);
			}
		}

		if($import_type === 'WooCommerceRefunds' ){
			$wordpressfields = array(
					'Post Parent' => 'post_parent',
					'Post Excerpt' => 'post_excerpt',
					'Refund Id' => 'REFUNDID'
					);
			if($mode == 'Insert'){
				unset($wordpressfields['Refund Id']);
			}
		}

		if($import_type === 'WooCommerceVariations'){
			$wordpressfields = array(
					'Product Id' => 'PRODUCTID',
					'Parent Sku' => 'PARENTSKU',
					'Variation Sku' => 'VARIATIONSKU',
					'Variation ID' => 'VARIATIONID',
					'Featured Image' => 'featured_image',
					);
			if($mode == 'Insert'){
				unset($wordpressfields['Variation ID']);
			}
		}

		if($import_type == 'WooCommerceCategories') { 
			$wordpressfields=array( 
					'Category Name'=>'name',                                
					'Category slug'=>'slug',                                
					'Category Description'=>'description',                  
					'Term ID'=>'term_id',                                   
					'Display type'=>'display_type',                         
					'Featured image'=>'featured_image',                     
					'Parent' =>'post_parent' 
					); 
			if($mode == 'Insert'){
				unset($wordpressfields['Term ID']);
			}  
		}

		if($import_type == 'WooCommercetags') {
			$wordpressfields=array(
					'Tag Name'=>'name',
					'Tag Slug'=>'slug',
					'Tag Description' =>'description',
					'Term ID'=>'TERMID'
					);
			if($mode == 'Insert'){
				unset($wordpressfields['Term ID']);
			}

		}
		if($import_type == 'WooCommerceattribute') {
			$wordpressfields=array( 
					'Name'=>'name',
					'Slug'=>'slug',
					'Enable Archives'=>'enable_archive',
					'Default sort order'=>'default_sort_order',
					'Configure terms'=>'configure_terms'
					);
		}			

		if($import_type === 'CustomerReviews') {
			if(is_plugin_active('wp-customer-reviews/wp-customer-reviews-3.php') || is_plugin_active('wp-customer-reviews/wp-customer-reviews.php')){
				$wordpressfields = array(
					'Review Date Time' => 'date_time',
					'Reviewer Name' => 'review_name',
					'Reviewer Email' => 'review_email',
					'Reviewer IP' => 'review_ip',
					'Review Format' => 'review_format',
					'Review Title' => 'review_title',
					'Review Text' => 'review_text',
					'Review Response' => 'review_admin_response',
					'Review Status' => 'status',
					'Review Rating' => 'review_rating',
					'Review URL' => 'review_website',
					'Review to Post/Page Id' => 'review_post',
					'Review ID' => 'review_id',
					);
				if($mode == 'Insert'){
					unset($wordpressfields['Review ID']);
				}
			}
		}
		if($import_type === 'GFEntries') {
		
			if(is_plugin_active('gravityforms/gravityforms.php') ){
				
				if (class_exists('GFAPI')) {
					// Get all forms.
					$forms = \GFAPI::get_forms();
				
					foreach ($forms as $form) {
							
						// Get the fields for this form.
						$fields = $form['fields'];
						$wordpressfields['Form ID' ] = 'form_id' ;
						$wordpressfields['Entry ID' ] = 'entry_id' ;
				
						foreach ($fields as $field) {
							
							$wordpressfields[$field->label ] = $field->label.'_'. $field->id ;
						
						}

							


					
					}
			
			}
			
		}
		}
		if(is_plugin_active('jet-engine/jet-engine.php')){
			global $wpdb;
			$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
			
			foreach($get_slug_name as $key=>$get_slug){
				$value=$get_slug->slug;
				if($import_type == $value){
					$wordpressfields=array(
						'_ID'=>'_ID',
						'Status'=>'cct_status',			
					);
					if($mode == 'Insert'){
						unset($wordpressfields['_ID']);
					}
				}
			}
		}
		if ($import_types == 'JetBooking' && is_plugin_active('jet-booking/jet-booking.php')) {
			$post_type = trim(jet_abaf()->settings->get( 'apartment_post_type' ));

				$wordpressfields = array(
					'booking_id' => 'booking_id',
					'status' => 'status',
					'apartment_id' => 'apartment_id',
					'apartment_unit' => 'apartment_unit',
					'check_in_date' => 'check_in_date',
					'check_out_date' => 'check_out_date',
					'order_id' => 'order_id',
					'user_id' => 'user_id',
					'import_id' => 'import_id',
					'attributes' => 'attributes',
					'guests' => 'guests',
					'orderStatus' => 'orderStatus',
					);
			if($post_type == 'product'){
				unset($wordpressfields['orderStatus']);
			}
			if($mode == 'Insert'){
				unset($wordpressfields['booking_id']);
				unset($wordpressfields['order_id']);
			}if($mode == 'Update'){
				unset($wordpressfields['orderStatus']);
				unset($wordpressfields['order_id']);
				unset($wordpressfields['user_id']);
				//unset($wordpressfields['import_id']);
				
			}

		}
		$wordpress_value = $this->convert_static_fields_to_array($wordpressfields);
		$response['core_fields'] = $wordpress_value ;
		
		return $response;
	} 

	/**
	* Core Fields extension supported import types
	* @param string $import_type - selected import type
	* @return boolean
	*/
	public function extensionSupportedImportType($import_type){	
		return true;
	}

}
