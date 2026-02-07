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

/**
 * Class PostExport
 * @package Smackcoders\WCSV
 */
class PostExport {

	protected static $instance = null,$mapping_instance,$export_handler,$export_instance,$jet_custom_table_export,$get_total_records;
	public $offset = 0;	
	public $limit;
	public $totalRowCount;
	public $plugin;	
	public $typeOftypesField;
	public $alltoolsetfields;
	public $allpodsfields;
	public $allacf;
	public $image;

	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
			self::$export_instance = ExportExtension::getInstance();
			PostExport::$jet_custom_table_export = JetCustomTableExport::getInstance();
			PostExport::$get_total_records = TotalRecordsCount::getInstance();
		}
		return self::$instance;
	}

	/**
	 * PostExport constructor.
	 */
	public function __construct() {
		$this->plugin = Plugin::getInstance();
	}
	public function get_all_productWithVaraiation_ids($products) {
		$all_product_ids = [];
		foreach ($products as $product_id) {
			$product = wc_get_product($product_id);
			// Add parent product ID
			$all_product_ids[] = $product_id;

			// If variable product, add variation IDs
			if ($product->is_type('variable')) {
				$variation_ids = $product->get_children();
				if (!empty($variation_ids)) {
					$all_product_ids = array_merge($all_product_ids, $variation_ids);
				}
			}


		}

		// Remove duplicate IDs
		$all_product_ids = array_unique($all_product_ids);

		// Update total row count
		//self::$export_instance->totalRowCount = count($all_product_ids);

		return $all_product_ids;
	}
	public function all_product_variation($products){
		// Initialize arrays to store IDs.
		$product_ids = array();
		$variable_product_ids = array();
		$variation_ids = array();

		// Iterate through products to separate parent and variable products.
		foreach ($products as $product) {
			$product_ids[] = $product->get_id(); // Store all product IDs.
			if ($product->is_type('variable')) {
				$variable_product_ids[] = $product->get_id(); // Store variable product IDs.
				$variation_ids = array_merge($variation_ids, $product->get_children()); // Get variations of variable products.
			}
		}
		// Merge parent product IDs and variation IDs.
		$all_product_ids = array_merge($product_ids, $variation_ids);
		// Remove duplicate IDs.
		$all_product_ids = array_unique($all_product_ids);
		// Log the total count.
		//self::$export_instance->totalRowCount = count($all_product_ids);
		//$product_ids = !empty($all_product_ids) ? array_slice($all_product_ids, $offset, $limit) : [];
		$product_ids = !empty($all_product_ids) ? $all_product_ids : [];

		return $product_ids;

	}

	/**
	 * Get records based on the post types
	 * @param $module
	 * @param $optionalType
	 * @param $conditions
	 * @return array
	 */
	public function getRecordsBasedOnPostTypes ($module, $optionalType, $conditions ,$offset , $limit,$category_module,$category_export,$product_titles = '',$exportPaymentSelectedField = '') {
		global $wpdb;
		global $sitepress;
		$post_title_export = !empty($conditions['specific_post_title']['post_title']) ? $conditions['specific_post_title']['post_title'] : '';
		if($optionalType == 'ngg_pictures'){
			global $wpdb;

			// Setup limit and offset


			// Get NextGEN gallery path
			$ngg_options = get_option('ngg_options');
			$gallery_path = trailingslashit(content_url()) . trailingslashit($ngg_options['gallerypath']); // e.g., https://example.com/wp-content/gallery/

			// Prepare and run query
			// Prepare the query with pagination and ordering
			$query = $wpdb->prepare(
				"
    SELECT 
	p.pid               AS id,
	p.filename          AS filename,
	p.alttext           AS alt_text,
	p.description       AS description,
	wp.guid             AS featured_image,
	g.name              AS nextgen_gallery
    FROM {$wpdb->prefix}ngg_pictures p
    LEFT JOIN {$wpdb->prefix}posts wp 
	   ON wp.ID = p.post_id
    LEFT JOIN {$wpdb->prefix}ngg_gallery g 
	   ON g.gid = p.galleryid
    ORDER BY p.pid DESC
    LIMIT %d OFFSET %d
    ",
				$limit,
				$offset
			);

			// Get results as associative array
			$results_raw = $wpdb->get_results( $query, ARRAY_A );

			// Initialize results array
			$results = array();

			// Process each row to append constructed featured image
			foreach ( $results_raw as $row ) {
				$pid      = $row['id'];
				$filename = isset( $row['filename'] ) ? $row['filename'] : '';
				$gallery  = isset( $row['nextgen_gallery'] ) ? $row['nextgen_gallery'] : '';

				// Construct featured image URL manually if not available in wp.guid
				$constructed_image = ( ! empty( $gallery ) && ! empty( $filename ) )
					? trailingslashit( $gallery_path ) . trailingslashit( $gallery ) . $filename
					: '';

				// Append to row
				$row['featured_image'] = $constructed_image;

				// Store row using image ID as key
				$results[ $pid ] = $row;
			}
			self::$export_instance->totalRowCount = count($results);
     
			ExportExtension::getInstance()->proceedExport($results);
			return;
		}
		if (!empty($category_export)) {
			$total_Count = get_option('advancedFilter_export_total_count');
			if ($total_Count === false || $total_Count == 0) {
				$total_Count = PostExport::$get_total_records->storeTotalCount($module, $optionalType, $category_export);
			}

			trim($category_export);
			if ($optionalType == 'posts') {
				$optionalType = 'post';
			}
			$terms_id = [];
			foreach (explode(',', $category_export) as $category_export) {
				$category_export = trim($category_export);
				$terms_id[] = $wpdb->get_var($wpdb->prepare(
					"SELECT term_id FROM {$wpdb->prefix}terms WHERE name = %s",
					$category_export
				));
			}
			if (!empty($terms_id)) {
				$placeholders = implode(',', array_fill(0, count($terms_id), '%d'));

				// Ensure prepare() runs only if we have valid term IDs
				$term_taxo_ids = $wpdb->get_col($wpdb->prepare(
					"SELECT term_taxonomy_id FROM {$wpdb->prefix}term_taxonomy WHERE term_id IN ($placeholders)",
					...$terms_id
				));

				if (!empty($term_taxo_ids)) {
					$placeholders = implode(',', array_fill(0, count($term_taxo_ids), '%d'));

					$params = array_merge($term_taxo_ids, [$optionalType, $limit, $offset]);

					$query = $wpdb->prepare(
						"SELECT p.ID FROM {$wpdb->prefix}posts p 
						INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
						WHERE tr.term_taxonomy_id IN ($placeholders) 
						AND p.post_type = %s 
						AND p.post_status != 'trash' 
						ORDER BY p.ID ASC 
						LIMIT %d OFFSET %d",
				...$params
					);
			$result = $wpdb->get_col($query);
			self::$export_instance->totalRowCount = $total_Count;
			if($module == 'WooCommerce'){
				$result = $this->get_all_productWithVaraiation_ids($result);
			}
			return $result;
				}
			}
		}		
		elseif (!empty($post_title_export)) {
			$total_Count = get_option('advancedFilter_export_total_count');
			if ($total_Count === false || $total_Count == 0) {
				$total_Count = PostExport::$get_total_records->storeTotalCount($module, $optionalType, '',$post_title_export);
			}
			$post_title_export = trim($post_title_export); // Trim initial whitespace
			if ($optionalType == 'posts') {
				$optionalType = 'post';
			}

			// Split and trim post titles
$post_titles = array_map('trim', explode('|', $post_title_export));

			if (!empty($post_titles)) {
				$placeholders = implode(',', array_fill(0, count($post_titles), '%s'));

				// Merge parameters into an array
				$params = array_merge($post_titles, [$optionalType, $limit, $offset]);

				// Prepare and execute the query to fetch posts with specific titles
				$query = $wpdb->prepare(
					"SELECT ID FROM {$wpdb->prefix}posts 
					WHERE post_title IN ($placeholders) 
					AND post_type = %s 
					AND post_status != 'trash' 
					ORDER BY ID ASC 
					LIMIT %d OFFSET %d",
				...$params
				);

			$filtered_posts = $wpdb->get_col($query); // Fetch only the ID column

			self::$export_instance->totalRowCount = $total_Count;
			if($module == 'WooCommerce'){
				$filtered_posts = $this->get_all_productWithVaraiation_ids($filtered_posts);
			} 
			return $filtered_posts;
			}
		}			
		else{
			if ($module == 'JetReviews') {
				// Check if specific review conditions are provided
				if (!empty($conditions['specific_review_status']['approved'])) {
					$approved = $conditions['specific_review_status']['approved'];
					$reviews = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}jet_reviews WHERE approved = %d ORDER BY date DESC LIMIT %d OFFSET %d",
							$approved, 
							$limit, 
							$offset
						),
						ARRAY_A
					);

					// Collect review IDs
					foreach ($reviews as $review) {
						$result[] = $review['id']; // Assuming 'id' is the primary key for reviews
					}

					self::$export_instance->totalRowCount = !empty($result) ? count($result) : 0;
					return !empty($result) ? array_slice($result, $offset, $limit) : [];
				} else {
					// Fetch all reviews if no specific conditions are set
					$reviews = $wpdb->get_results(
						"SELECT * FROM {$wpdb->prefix}jet_reviews ORDER BY date DESC LIMIT {$limit} OFFSET {$offset}",
						ARRAY_A
					);

					foreach ($reviews as $review) {
						$result[] = $review['id'];
					}

					self::$export_instance->totalRowCount = !empty($result) ? count($result) : 0;
					return !empty($result) ? array_slice($result, $offset, $limit) : [];
				}
			}
			if($module == 'CustomPosts' && $optionalType == 'nav_menu_item'){
				$get_menu_id = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}terms AS t LEFT JOIN {$wpdb->prefix}term_taxonomy AS tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'nav_menu' ", ARRAY_A);
				$get_menu_arr = array_column($get_menu_id, 'term_id');
				self::$export_instance->totalRowCount = count($get_menu_arr);
				return $get_menu_arr;			
			}

			if($module == 'CustomPosts' && $optionalType == 'widgets'){
				$get_widget_id = $wpdb->get_row("SELECT option_id FROM {$wpdb->prefix}options where option_name = 'widget_recent-posts' ", ARRAY_A);
				self::$export_instance->totalRowCount = 1;
				return $get_widget_id;			
			}
			if($module == 'CustomPosts') {
				$module = $optionalType;
			} elseif ($module == 'WooCommerceOrders') {
				$module = 'shop_order';
			}
			elseif ($module == 'WooCommerceCoupons') {
				$module = 'shop_coupon';
			}
			elseif ($module == 'WooCommerceRefunds') {
				$module = 'shop_order_refund';
			}
			elseif ($module == 'WooCommerceVariations') {
				if (is_plugin_active('woocommerce/woocommerce.php') && $sitepress == null && !is_plugin_active('polylang/polylang.php') && !is_plugin_active('polylang-pro/polylang.php') && !is_plugin_active('polylang-wc/polylang-wc.php')  && !is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php') && !is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
					if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
						if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
							$from_date = $conditions['specific_period']['from'];
							$product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
							$args = array('date_query' => array(array('year'  => date( 'Y', strtotime( $from_date ) ),'month' => date( 'm', strtotime( $from_date ) ),'day'   => date( 'd', strtotime( $from_date ) ),),),'status' => $product_statuses,'numberposts' => -1,'orderby' => 'date');
						}else{
							$from_date = $conditions['specific_period']['from'] ?? null;
							$to_date   = $conditions['specific_period']['to'] ?? null;
							$product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
							$args = array('date_query' => array(array('after' => $from_date,'before' => $to_date,'inclusive' => true,),),'status' => $product_statuses,'numberposts' => -1,'orderby' => 'date');
						} 
						$products = wc_get_products($args);
					}else{
						$product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
						$products = wc_get_products(array('status' => $product_statuses ,'limit' => -1,'orderby' => 'date'));
					}
					$variable_product_ids = [];
					foreach($products as $product){
						if ($product->is_type('variable')) {
							$variable_product_ids[] = $product->get_id();
						}
					}$variation_ids = []; 
					foreach($variable_product_ids as $variable_product_id){
						$variable_product = wc_get_product($variable_product_id);
						$variation_ids[]  = $variable_product->get_children();
					}
					$product_variation_ids = [];
					foreach ($variation_ids as $v_ids) {
						foreach ($v_ids as $v_id) {
							$product_variation_ids[] = $v_id;
						}
					}
					self::$export_instance->totalRowCount = count($product_variation_ids);
					$variationIds = !empty($product_variation_ids) ? array_slice($product_variation_ids, $offset, $limit) : [];       
					return $variationIds;
				}
				else{ //polylang or wpml active
					$module = 'product_variation';
					$variation = "select DISTINCT ID from {$wpdb->prefix}posts";
					$variation .= " where post_type = '$module'";

					if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
						$variation .= " and post_status in ('publish','draft','future','private','pending') AND post_parent!=0";
						if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
							$variation .= " and DATE(post_date) ='".$conditions['specific_period']['from']."'";
						}else{
							$variation .= " and post_date >= '" . $conditions['specific_period']['from'] . "' and post_date <= '" . $conditions['specific_period']['to'] . " 23:00:00'";
						}
					}else{
						$variation .= " and post_status in ('publish','draft','future','private','pending') AND post_parent!=0 ORDER BY post_date";
					}
					$extracted_id = $wpdb->get_col($variation);
					$variation =array();
					foreach($extracted_id as $ids){
						$parent_id = $wpdb->get_var("SELECT post_parent FROM {$wpdb->prefix}posts where ID=$ids");
						$post_status =$wpdb->get_var("SELECT post_status FROM {$wpdb->prefix}posts where ID=$parent_id");
						if(!empty($post_status )){
							if($post_status !='trash' && $post_status != 'inherit'){
								$variation_ids [] =$ids;
							}

						}
					}
					self::$export_instance->totalRowCount = count($variation_ids);
					$variations_ids = !empty($variation_ids) ? array_slice($variation_ids, $offset, $limit) : [];       
					return $variations_ids;
				}
			}

			elseif($module == 'WPeCommerceCoupons'){
				$module = 'wpsc-coupon';
			}
			elseif($module == 'Images'){
				$module='attachment';

			}
			else {
				$module = self::import_post_types($module);
			}
			$get_post_ids = "select DISTINCT ID from {$wpdb->prefix}posts";
			//$get_post_ids .= " where post_type = '$module' and post_status in ('publish','draft','future','private','pending','inherit')";
			$get_post_ids .= " where post_type = '$module' ";
			/**
			 * Check for specific status
			 */
			if($module == 'product' && is_plugin_active('woocommerce/woocommerce.php')){
				if ($sitepress == null && !is_plugin_active('polylang/polylang.php') && !is_plugin_active('polylang-pro/polylang.php') && !is_plugin_active('polylang-wc/polylang-wc.php')  && !is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php')  && !is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
					if(!empty($conditions['specific_period']['is_check']) || !empty($conditions['specific_status']['status']) || !empty($conditions['specific_post_id']['is_check']) || !empty($conditions['specific_lang_code']['is_check'])){
						$total_Count = get_option('advancedFilter_export_total_count');
						if ($total_Count === false || $total_Count == 0) {
							$total_Count = PostExport::$get_total_records->storeTotalCount($module, $optionalType, '','',$conditions);
						}
					}
					if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
						if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
							$from_date = $conditions['specific_period']['from'];
							$product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
							$args = array('date_query' => array(array('year'  => date( 'Y', strtotime( $from_date ) ),'month' => date( 'm', strtotime( $from_date ) ),'day'   => date( 'd', strtotime( $from_date ) ),),),'status' => $product_statuses,'limit'   => $limit,'offset'  => $offset,'orderby' => 'date');	
						}else{
							$from_date = $conditions['specific_period']['from'] ?? null;
							$to_date   = $conditions['specific_period']['to'] ?? null;
							$product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
							$args = array('date_query' => array(array('column'   => 'post_date','after'    => $from_date,'before'   => $to_date,'inclusive' => true,),),'status' => $product_statuses,'orderby'  => 'date','order'    => 'ASC','limit'    => $limit,'offset'   => $offset);					
						}
						$products = wc_get_products($args);
						$product_ids = $this->all_product_variation($products);
					}
					if(!empty($conditions['specific_post_id']['is_check']) && $conditions['specific_post_id']['is_check'] == 'true'){
						$prod_id=explode(',',$conditions['specific_post_id']['post_id']);
						$args=array('include' => $prod_id);
						$products=wc_get_products($args);
						$product_ids = $this->all_product_variation($products);
					}
					if(!empty($conditions['specific_status']['status'])) {
						$status = $conditions['specific_status']['status'];
						if($conditions['specific_status']['status'] == 'all') {
							$product_statuses = array('publish', 'draft', 'trash', 'private', 'pending');
							$products = wc_get_products(array('status' => $product_statuses, 'limit'   => $limit,'offset'  => $offset,'orderby' => 'date'));		
						} 
						else{
							$product_statuses = array($status);
							$products = wc_get_products(array('status' => $product_statuses, 'limit'   => $limit,'offset'  => $offset,'orderby' => 'date'));	
						}
						$product_ids = $this->all_product_variation($products);
					}
					if(empty($conditions['specific_period']['is_check']) && empty($conditions['specific_status']['status']) && empty($conditions['specific_post_id']['is_check']) && empty($conditions['specific_lang_code']['is_check'])){
						// Define product statuses
						$product_statuses = ['publish', 'draft', 'future', 'private', 'pending'];

						// Prepare query arguments
						$args = [
							'status'  => $product_statuses,
							'limit'   => $limit,
							'offset'  => $offset,
							'orderby' => 'date',
							'order'   => 'DESC', // Order products by date in descending order
						];

						// Fetch products
						$products = wc_get_products($args);
						$product_ids = $this->all_product_variation($products);
						$total_Count = get_option('woocommerce_product_count');
					}

if (!empty($conditions['woo_stock_status']['is_check']) && $conditions['woo_stock_status']['is_check'] == '1') {
   $product_ids = $product_ids ?? [];

	$stock_status = $conditions['woo_stock_status']['status'];

    $filtered_ids = [];

    if (empty($product_ids)) {
        $all_products = wc_get_products([
            'type'  => ['simple','variable','variation'],
            'limit' => -1,
        ]);
        $product_ids = array_map(fn($p) => $p->get_id(), $all_products);
    }

    foreach ($product_ids as $pid) {
        $product = wc_get_product($pid);
        if (!$product) continue;

        $ids_to_check = $product->is_type('variable') ? $product->get_children() : [$pid];

        foreach ($ids_to_check as $id) {
            $p = wc_get_product($id);
            if (!$p) continue;

            $stock  = intval($p->get_stock_quantity());
            $status = $p->get_stock_status();

            if ($stock_status === 'out-of-stock' && $status === 'outofstock') {
                $filtered_ids[] = $id;
            }
            if ($stock_status === 'in-stock' && $status === 'instock' && $stock >= 1) {
                $filtered_ids[] = $id;
            }
            if ($stock_status === 'low-stock') {
                $low_stock_amount = get_option('woocommerce_notify_low_stock_amount', 2);
                if ($status === 'instock' && $stock > 0 && $stock <= $low_stock_amount) {
                    $filtered_ids[] = $id;
                }
            }
        }
    }

    $product_ids = array_values(array_unique($filtered_ids));
}

 $total_Count = count($product_ids);
    self::$export_instance->totalRowCount = $total_Count;

    $product_ids = array_slice($product_ids, $offset, $limit);
    return $product_ids;

				}
				else{
					if(!empty($conditions['specific_period']['is_check']) || !empty($conditions['specific_status']['status']) || !empty($conditions['specific_post_id']['is_check']) || !empty($conditions['specific_lang_code']['is_check'])){
						$total_Count = get_option('advancedFilter_export_total_count');
						if ($total_Count === false || $total_Count == 0) {
							$total_Count = PostExport::$get_total_records->storeTotalCount($module, $optionalType, '','',$conditions);
						}
					}
					//when polylang wpml active
					$products = "select DISTINCT ID from {$wpdb->prefix}posts";
					$products .= " where post_type = '$module'";
					if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true' && !empty($conditions['specific_status']['status'])) { //Period and Status both are TRUE 
						if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
							$status = $conditions['specific_status']['status'];
							$products .= " and post_status = '$status'";
							$products .= " and DATE(post_date) ='" . $conditions['specific_period']['from'] . "'";	
						}else{
							$status = $conditions['specific_status']['status'];
							$products .= " and post_status = '$status'";
							$products .= " and post_date >= '" . $conditions['specific_period']['from'] . "' and post_date <= '" . $conditions['specific_period']['to'] . " 23:00:00'";
						}
					}
					if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
						$products .= " and post_status in ('publish','draft','private','pending') ";
						if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
							$products .= " and DATE(post_date) ='".$conditions['specific_period']['from']."'";
						}else{
							$products .= " and post_date >= '" . $conditions['specific_period']['from'] . "' and post_date <= '" . $conditions['specific_period']['to'] . " 23:00:00'";
						}
					}
					if(!empty($conditions['specific_status']['status'])) {
						$status = $conditions['specific_status']['status'];
						if($conditions['specific_status']['status'] == 'all') {
							$products .= " and post_status in ('publish','draft','trash','private','pending') ORDER by post_date";
						} 
						else{
							$products .= " and post_status = '$status' ORDER by post_date";
						}
					}
					if(!empty($conditions['specific_post_id']['is_check']) && $conditions['specific_post_id']['is_check'] == 'true'){
						$prod_ids =$conditions['specific_post_id']['post_id'];
						$products .= " and ID in ($prod_ids)";

					}
					if(!empty($conditions['specific_lang_code']['is_check']) && !empty($conditions['specific_lang_code']['lang_code'])){
						$lang_code = $conditions['specific_lang_code']['lang_code'];
						$products .= " AND EXISTS (SELECT 1 FROM {$wpdb->prefix}icl_translations t WHERE t.element_id = ID AND t.language_code = '$lang_code')";
					}
					if(empty($conditions['specific_status']['status']) && empty($conditions['specific_period']['is_check'])) {
						$products .= " and post_status in ('publish','draft','future','private','pending') ORDER by post_date";
						$total_Count = get_option('woocommerce_product_count');
					}
					if (is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php') && !empty($conditions['specific_lang_code']['is_check']) && !empty($conditions['specific_lang_code']['lang_code'])) {
						$lang_code = $conditions['specific_lang_code']['lang_code'];
						$products .= " AND EXISTS (SELECT 1 FROM {$wpdb->prefix}icl_translations t WHERE t.element_id = ID AND t.language_code = '$lang_code')";
					}	
					$products = $wpdb->get_col($products);
					$product_array = $products;
					foreach($products as $product_val){
						$products_var = "select DISTINCT ID from {$wpdb->prefix}posts";
						$products_var .= " where post_type = 'product_variation' and post_parent = '$product_val'";
						if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true' && !empty($conditions['specific_status']['status'])) { //Period and Status both are TRUE 
							if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
								$status = $conditions['specific_status']['status'];
								$products_var .= " and post_status = '$status'";
								$products_var .= " and DATE(post_date) ='" . $conditions['specific_period']['from'] . "'";	
							}else{
								$status = $conditions['specific_status']['status'];
								$products_var .= " and post_status = '$status'";
								$products_var .= " and post_date >= '" . $conditions['specific_period']['from'] . "' and post_date <= '" . $conditions['specific_period']['to'] . " 23:00:00'";
							}
						}
						if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
							$products .= " and post_status in ('publish','draft','private','pending') ";
							if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
								$products_var .= " and DATE(post_date) ='".$conditions['specific_period']['from']."'";
							}else{
								$products_var .= " and post_date >= '" . $conditions['specific_period']['from'] . "' and post_date <= '" . $conditions['specific_period']['to'] . " 23:00:00'";
							}
						}
						if(!empty($conditions['specific_status']['status'])) {
							$status = $conditions['specific_status']['status'];
							if($conditions['specific_status']['status'] == 'all') {
								$products_var .= " and post_status in ('publish','draft','trash','private','pending') ORDER by post_date";
							} 
							else{
								$products_var .= " and post_status = '$status' ORDER by post_date";
							}
						}
						if(!empty($conditions['specific_post_id']['is_check']) && $conditions['specific_post_id']['is_check'] == 'true'){
							$prod_ids =$conditions['specific_post_id']['post_id'];
							$products_var .= "and ID in ($prod_ids)";
						}
						if(empty($conditions['specific_status']['status']) && empty($conditions['specific_period']['is_check'])) {
							$products_var .= " and post_status in ('publish','draft','future','private','pending') ORDER by post_date";
						}
						$products_vars = $wpdb->get_col($products_var);
						$product_array = array_merge($product_array,$products_vars);
					}
					self::$export_instance->totalRowCount = $total_Count;
					$products_ids = !empty($products) ? array_slice($product_array, $offset, $limit) : [];
					return $products_ids;
				}	
			}
			elseif($module == 'shop_order' && is_plugin_active('woocommerce/woocommerce.php')){ 
				if(!empty($conditions['specific_period']['is_check']) || !empty($conditions['specific_status']['status'])){
					$total_Count = get_option('advancedFilter_export_total_count');
					if ($total_Count === false || $total_Count == 0) {
						$total_Count = PostExport::$get_total_records->storeTotalCount($module, $optionalType, '','',$conditions);
					}
				}
				if($sitepress == null && !is_plugin_active('polylang/polylang.php') && !is_plugin_active('polylang-pro/polylang.php') && !is_plugin_active('polylang-wc/polylang-wc.php') &&  !is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php') && !is_plugin_active('sitepress-multilingual-cms/sitepress.php')){
					$total_Count = get_option('advancedFilter_export_total_count');
					if(!empty($conditions['specific_period']['is_check']) || !empty($conditions['specific_status']['status'])){
						if ($total_Count === false || $total_Count == 0) {
							$total_Count = PostExport::$get_total_records->storeTotalCount($module, $optionalType, '','',$conditions);
						}
					}
					if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true' && !empty($conditions['specific_status']['status'])) { //Period and Status both are TRUE
						if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
							$from_date = $conditions['specific_period']['from'];
							$status = $conditions['specific_status']['status'];
							$args = array('date_query' => array(array('year'  => date('Y', strtotime($from_date)),'month' => date('m', strtotime($from_date)),'day'   => date('d', strtotime($from_date)),),),'status' => $status,'limit'   => $limit,'offset'  => $offset,'orderby' => 'date');	
						}else{
							$from_date = $conditions['specific_period']['from'] ?? null;
							$to_date   = $conditions['specific_period']['to'] ?? null;
							$status = $conditions['specific_status']['status'];
							$args = array('date_query' => array(array('after' => $from_date,'before'=> $to_date,'inclusive' => true,),),'status' => $status,'limit'   => $limit,'offset'  => $offset,'orderby' => 'date');
						}
						$orders = wc_get_orders($args);
						$get_order_ids = array();
						if(!empty($orders)){
							$get_post_ids = array();
							foreach($orders as $my_orders){
								$get_post_ids[] = $my_orders->get_id();
							}
							$get_order_ids = !empty($get_post_ids) ? $get_post_ids : []; 
						}
						if (!empty($product_titles)) {
							
    $titles_array = array_map('trim', explode(',', $product_titles));
    foreach ($titles_array as $title) {
        $product = get_page_by_title($title, OBJECT, 'product');
        if ($product) {
            $product_ids[] = $product->ID;
        }
    }

	$filtered_orders = [];

	if (!empty($product_ids)) {
    foreach($product_ids as $ids){
		$order_statuses = implode("','", array_map('esc_sql', $order_statuses));

    $get_order_ids = $wpdb->get_col("
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status IN ('$order_statuses')
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = '$ids'
    ");

}
}

}
if (!empty($exportPaymentSelectedField)) {
$selected_methods = array_map('trim', explode(',', $exportPaymentSelectedField));

$filtered_orders = [];

foreach ( $get_order_ids as $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order && in_array( $order->get_payment_method(), $selected_methods ) ) {
        $filtered_orders[] = $order;
    }
}

// Optional: get just the order IDs
$get_order_ids = array_map(function($order) {
    return $order->get_id();
}, $filtered_orders);


}
						
					}
					if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') { //Specific period ONLY TRUE
						if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
							$from_date = $conditions['specific_period']['from'];
							if(!empty($conditions['specific_status']['status'])){
								$status = $conditions['specific_status']['status'];
							}else{
								$status = array('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending','wc-refunded');
							}
							$args = array('date_query' => array(array('year'  => date('Y', strtotime($from_date)),'month' => date('m', strtotime($from_date)),'day'   => date('d', strtotime($from_date)),),),'status' => $status,'limit'   => $limit,'offset'  => $offset,'orderby' => 'date');	
						}else{
							$from_date = $conditions['specific_period']['from'] ?? null;
							$to_date   = $conditions['specific_period']['to'] ?? null;
							if(!empty($conditions['specific_status']['status'])){
								$status = $conditions['specific_status']['status'];
							}else{
								$status = array('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending','wc-refunded');
							}
							$args = array('date_query' => array(array('after' => $from_date,'before'=> $to_date,'inclusive' => true,),),'status' => $status,'limit'   => $limit,'offset'  => $offset,'orderby' => 'date');
						}
						$orders = wc_get_orders($args);
						$get_order_ids = array();
						if(!empty($orders)){
							$get_post_ids = array();
							foreach($orders as $my_orders){
								$get_post_ids[] = $my_orders->get_id();
							}
							$get_order_ids = !empty($get_post_ids) ? $get_post_ids : []; 
						}
						if (!empty($product_titles)) {
							
    $titles_array = array_map('trim', explode(',', $product_titles));
    foreach ($titles_array as $title) {
        $product = get_page_by_title($title, OBJECT, 'product');
        if ($product) {
            $product_ids[] = $product->ID;
        }
    }

	$filtered_orders = [];

	if (!empty($product_ids)) {
   foreach($product_ids as $ids){
		$order_statuses = implode("','", array_map('esc_sql', $order_statuses));

    $get_order_ids = $wpdb->get_col("
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status IN ('$order_statuses')
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = '$ids'
    ");

}
}

}
						
						if (!empty($exportPaymentSelectedField)) {
$selected_methods = array_map('trim', explode(',', $exportPaymentSelectedField));

$filtered_orders = [];

foreach ( $order_ids as $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order && in_array( $order->get_payment_method(), $selected_methods ) ) {
        $filtered_orders[] = $order;
    }
}

// Optional: get just the order IDs
$filtered_order_ids = array_map(function($order) {
    return $order->get_id();
}, $filtered_orders);


}
					}
					if(!empty($conditions['specific_status']['status'])) {//Specific status ONLY TRUE
						$status = $conditions['specific_status']['status'];

						$orders = wc_get_orders(array('status' => $status,'limit'   => $limit,'offset'  => $offset,'orderby' => 'date','order' => 'ASC'));
						$get_order_ids = array();
						if(!empty($orders)){
							$get_post_ids = array();
							foreach($orders as $my_orders){
								$get_post_ids[] = $my_orders->get_id();
							}
							$get_order_ids = !empty($get_post_ids) ? $get_post_ids : []; 
						}

								if (!empty($product_titles)) {
									
    $titles_array = array_map('trim', explode(',', $product_titles));
    foreach ($titles_array as $title) {
        $product = get_page_by_title($title, OBJECT, 'product');
        if ($product) {
            $product_ids[] = $product->ID;
        }
    }

	$filtered_orders = [];

	if (!empty($product_ids)) {
    foreach($product_ids as $ids){
		$order_statuses = implode("','", array_map('esc_sql', $order_statuses));

    $get_order_ids = $wpdb->get_col("
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status IN ('$order_statuses')
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = '$ids'
    ");

}
}

}
						
						if (!empty($exportPaymentSelectedField)) {
$selected_methods = array_map('trim', explode(',', $exportPaymentSelectedField));

$filtered_orders = [];

foreach ( $get_order_ids as $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order && in_array( $order->get_payment_method(), $selected_methods ) ) {
        $filtered_orders[] = $order;
    }
}

// Optional: get just the order IDs
$get_order_ids = array_map(function($order) {
    return $order->get_id();
}, $filtered_orders);


}

					}
					if(empty($conditions['specific_status']['status']) && empty($conditions['specific_period']['is_check'])) {
						$order_statuses = array('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending','wc-refunded');
						$orders = wc_get_orders(array('status' => $order_statuses,'limit'   => $limit,'offset'  => $offset,'orderby' => 'date','order' => 'ASC'));
						$get_order_ids = array();
						if(!empty($orders)){
							$get_post_ids = array();
							foreach($orders as $my_orders){
								$get_post_ids[] = $my_orders->get_id();
							}
							$get_order_ids = !empty($get_post_ids) ? $get_post_ids : []; 
						}
								if (!empty($product_titles)) {
    $titles_array = array_map('trim', explode(',', $product_titles));
    foreach ($titles_array as $title) {
        $product = get_page_by_title($title, OBJECT, 'product');
        if ($product) {
            $product_ids[] = $product->ID;
        }
    }

	$filtered_orders = [];
$get_order_ids = [];
	if (!empty($product_ids)) {
  foreach($product_ids as $ids){
	  $order_statuses = array('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending','wc-refunded');
		$order_statuses = implode("','", array_map('esc_sql', $order_statuses));

    $get_order_ids[] = $wpdb->get_col("
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status IN ('$order_statuses')
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = '$ids'
    ");
//	 $get_order_ids

}
		$get_order_ids = array_column($get_order_ids, 0);


	
}

}
						if (!empty($exportPaymentSelectedField)) {
$selected_methods = array_map('trim', explode(',', $exportPaymentSelectedField));

$filtered_orders = [];

foreach ( $get_order_ids as $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order && in_array( $order->get_payment_method(), $selected_methods ) ) {
        $filtered_orders[] = $order;
    }
}

// Optional: get just the order IDs
$get_order_ids = array_map(function($order) {
    return $order->get_id();
}, $filtered_orders);


}

						$total_Count = get_option('woocommerce_order_count');
					}
					//self::$export_instance->totalRowCount = count($get_order_ids);
					self::$export_instance->totalRowCount = $total_Count;
					return $get_order_ids; 
				}
				else{//polylang or wpml active
					$order_statuses = array('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending','wc-refunded');
					$orders = wc_get_orders(array('status' => $order_statuses,'numberposts' => -1,'orderby' => 'date','order' => 'ASC'));
					$get_post_ids = array();
					foreach($orders as $my_orders){
						$get_post_ids[] = $my_orders->get_id();
					}
							if (!empty($product_titles)) {
    $titles_array = array_map('trim', explode(',', $product_titles));
    foreach ($titles_array as $title) {
        $product = get_page_by_title($title, OBJECT, 'product');
        if ($product) {
            $product_ids[] = $product->ID;
        }
    }

	$filtered_orders = [];

	if (!empty($product_ids)) {
   foreach($product_ids as $ids){
		$order_statuses = implode("','", array_map('esc_sql', $order_statuses));

    $get_order_ids = $wpdb->get_col("
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status IN ('$order_statuses')
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = '$ids'
    ");

}
}

}
		if (!empty($exportPaymentSelectedField)) {
$selected_methods = array_map('trim', explode(',', $exportPaymentSelectedField));

$filtered_orders = [];

foreach ( $get_order_ids as $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order && in_array( $order->get_payment_method(), $selected_methods ) ) {
        $filtered_orders[] = $order;
    }
}

// Optional: get just the order IDs
$get_order_ids = array_map(function($order) {
    return $order->get_id();
}, $filtered_orders);


}
					foreach($get_post_ids as $ids){
						$module =$wpdb->get_var("SELECT post_type FROM {$wpdb->prefix}posts where id=$ids");
					}
					if($module == 'shop_order_placehold'){//post_status shop_order_placehold!
						$orders = "select DISTINCT p.ID from {$wpdb->prefix}posts as p inner join {$wpdb->prefix}wc_orders as wc ON p.ID=wc.id";
						$orders.= " where p.post_type = '$module'";
						if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true' && !empty($conditions['specific_status']['status'])) { //Period and Status both are TRUE 
							if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
								$status = $conditions['specific_status']['status'];
								$orders .= " and wc.status = '$status'";
								$orders .= " and DATE(p.post_date) ='" . $conditions['specific_period']['from'] . "'";	
							}else{
								$status = $conditions['specific_status']['status'];
								$orders .= " and wc.status = '$status'";
								$orders .= " and p.post_date >= '" . $conditions['specific_period']['from'] . "' and p.post_date <= '" . $conditions['specific_period']['to'] . " 23:00:00'";
							}
							$orders .= " AND wc.status != 'trash'";
							$orders = $wpdb->get_col($orders);
						}
						elseif(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
							$orders .= " and wc.status in ('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending')";
							if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
								$orders .= " and DATE(p.post_date) ='".$conditions['specific_period']['from']."'";
							}else{
								$orders .= " and p.post_date >= '" . $conditions['specific_period']['from'] . "' and p.post_date <= '" . $conditions['specific_period']['to'] . " 23:00:00'";
							}
							$orders .= " AND wc.status != 'trash'";
							$orders = $wpdb->get_col($orders);
						}
						elseif(!empty($conditions['specific_status']['status'])) {
							$status = $conditions['specific_status']['status'];
							$orders .= " and wc.status = '$status'";
							$orders .= " AND wc.status != 'trash'";
							$orders = $wpdb->get_col($orders);
						}
						if(empty($conditions['specific_status']['status']) && empty($conditions['specific_period']['is_check'])) {
							$orders .= " and wc.status in ('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending') ORDER BY post_date";
							$orders .= " AND wc.status != 'trash'";
							$orders = $wpdb->get_col($orders);
							$total_Count = get_option('woocommerce_order_count');
						}
					}
					else{//post_status shop_order!
						$orders = "select DISTINCT ID from {$wpdb->prefix}posts";
						$orders.= " where post_type = '$module'";
						if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true' && !empty($conditions['specific_status']['status'])) { //Period and Status both are TRUE 
							if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
								$status = $conditions['specific_status']['status'];
								$orders .= " and post_status = '$status'";
								$orders .= " and DATE(post_date)= '" . $conditions['specific_period']['from'] . "'";	
							}else{
								$status = $conditions['specific_status']['status'];
								$orders .= " and post_status = '$status'";
								$orders .= " and post_date >= '" . $conditions['specific_period']['from'] . "' and post_date <= '" . $conditions['specific_period']['to'] . " 23:00:00'";
							}
						}
						elseif(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
							$orders .= " and post_status in ('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending')";
							if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
								$orders .= " and  DATE(post_date) = '". $conditions['specific_period']['from'] . "'";
							}else{
								$orders .= " and post_date >= '" . $conditions['specific_period']['from'] . "' and post_date <= '" . $conditions['specific_period']['to'] . " 23:00:00'";
							}
						}
						elseif(!empty($conditions['specific_status']['status'])) {
							$status = $conditions['specific_status']['status'];
							$orders .= " and post_status = '$status'";
						}
						if(empty($conditions['specific_status']['status']) && empty($conditions['specific_period']['is_check'])) {
							$orders .= " and post_status in ('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending')";
							$total_Count = get_option('woocommerce_order_count');
						}
						$orders .= " AND post_status != 'trash'";
						$orders = $wpdb->get_col($orders);
					}
					self::$export_instance->totalRowCount = $total_Count;
					$get_order_ids = !empty($orders) ? array_slice($orders, $offset, $limit) : [];
					return $get_order_ids;
				}
			}elseif ($module == 'shop_coupon') {
				if(!empty($conditions['specific_status']['status'])) {
					if($conditions['specific_status']['status'] == 'All') {
						$get_post_ids .= " and post_status in ('publish','draft','pending')";
					} elseif($conditions['specific_status']['status']== 'Publish') {
						$get_post_ids .= " and post_status in ('publish')";
					} elseif($conditions['specific_status']['status'] == 'Draft') {
						$get_post_ids .= " and post_status in ('draft')";
					} elseif($conditions['specific_status']['status'] == 'Private') {
						$get_post_ids .= " and post_status in ('private')";
					} elseif($conditions['specific_status']['status'] == 'Pending') {
						$get_post_ids .= " and post_status in ('pending')";
					} 
				} else {
					$get_post_ids .= " and post_status in ('publish','draft','pending','private','future')";
				}

			}elseif ($module == 'shop_order_refund') {

			}
			elseif( $module == 'lp_order'){
				$get_post_ids .= " and post_status in ('lp-pending', 'lp-processing', 'lp-completed', 'lp-cancelled', 'lp-failed')";
			}
			else {		
				if(!empty($conditions['specific_status']['status'])) {
					if($conditions['specific_status']['status'] == 'All') {
						$get_post_ids .= " and post_status in ('publish','draft','future','private','pending')";
					} elseif($conditions['specific_status']['status'] == 'Publish' || $conditions['specific_status']['status'] == 'Sticky') {
						$get_post_ids .= " and post_status in ('publish')";
					} elseif($conditions['specific_status']['status'] == 'Draft') {
						$get_post_ids .= " and post_status in ('draft')";
					} elseif($conditions['specific_status']['status'] == 'Scheduled') {
						$get_post_ids .= " and post_status in ('future')";
					} elseif($conditions['specific_status']['status'] == 'Private') {
						$get_post_ids .= " and post_status in ('private')";
					} elseif($conditions['specific_status']['status'] == 'Pending') {
						$get_post_ids .= " and post_status in ('pending')";
					} elseif($conditions['specific_status']['status'] == 'Protected') {
						$get_post_ids .= " and post_status in ('publish') and post_password != ''";
					}
					elseif($conditions['specific_status']['status'] == 'Pending payment') {
						$get_post_ids .= " and post_status in ('wc-pending')";
					}
					elseif($conditions['specific_status']['status'] == 'Processing') {
						$get_post_ids .= " and post_status in ('wc-processing')";
					}
					elseif($conditions['specific_status']['status'] == 'On hold') {
						$get_post_ids .= " and post_status in ('wc-on-hold')";
					}
					elseif($conditions['specific_status']['status'] == 'Completed') {
						$get_post_ids .= " and post_status in ('wc-completed')";
					}
					elseif($conditions['specific_status']['status'] == 'Cancelled') {
						$get_post_ids .= " and post_status in ('wc-cancelled')";
					}
					elseif($conditions['specific_status']['status'] == 'Refunded') {
						$get_post_ids .= " and post_status in ('wc-refunded')";
					}
					elseif($conditions['specific_status']['status'] == 'Failed') {
						$get_post_ids .= " and post_status in ('wc-failed')";
					}
				} 
				else {

					// if(!$module=='attachment'){
					// 	$get_post_ids .= " and post_status in ('publish','draft','future','private','pending')";
					// }
					if($module!='attachment'){
						$get_post_ids .= " and post_status in ('publish','draft','future','private','pending')";
					}
					else{
						$get_post_ids .= " and post_status in ('publish','draft','future','private','pending','inherit')";
					}
				}
			}
			// Check for specific period
			if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
				if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
					$get_post_ids .= " and post_date >= '" . $conditions['specific_period']['from'] . "'";
				}else{
					// $get_post_ids .= " and post_date >= '" . $conditions['specific_period']['from'] . "' and post_date <= '" . $conditions['specific_period']['to'] . "'";
					$get_post_ids .= " and post_date >= '" . $conditions['specific_period']['from'] . "' and post_date <= '" . $conditions['specific_period']['to'] . " 23:00:00'";
				}
			}
			elseif(!empty($conditions['specific_post_id']['is_check']) && $conditions['specific_post_id']['is_check'] == 'true'){
				$prod_ids =$conditions['specific_post_id']['post_id'];
				$get_post_ids .= "and ID in ($prod_ids)";

			}

			if($module == 'woocommerce')
				$get_post_ids .= " and pm.meta_key = '_sku'";
			if($module == 'wpcommerce')
				$get_post_ids .= " and pm.meta_key = '_wpsc_sku'";

			// Check for specific authors
			if(!empty($conditions['specific_authors']['is_check'] == '1') && !empty($conditions['specific_authors']['author'])) {
				if(isset($conditions['specific_authors']['author'])) {
					$get_post_ids .= " and post_author = {$conditions['specific_authors']['author']}";
				}
			}

			// Check for specific lang code
if ( ! empty( $conditions['specific_lang_code']['is_check'] ) && ! empty( $conditions['specific_lang_code']['lang_code'] ) ) {
    global $wpdb;

    $lang_code = esc_sql( $conditions['specific_lang_code']['lang_code'] );

    $post_type     = ! empty( $module ) ? $module : 'post';
    $element_type  = 'post_' . $post_type; // e.g. post_post, post_page, post_product

    $get_post_ids .= "
        AND EXISTS (
            SELECT 1 
            FROM {$wpdb->prefix}icl_translations t 
            WHERE t.element_id = ID 
              AND t.language_code = '{$lang_code}'
              AND t.element_type = '{$element_type}'
        )
    ";
}


			//WpeCommercecoupons
			if($module == 'wpsc-coupon'){
				$get_post_ids = "select DISTINCT ID from {$wpdb->prefix}wpsc_coupon_codes";
			}
			//WpeCommercecoupons
			$get_total_row_count = $wpdb->get_col($get_post_ids);
			if(!empty($get_total_row_count )){
				if(!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
					if($conditions['specific_period']['from'] == $conditions['specific_period']['to']){
						$result = array();
						foreach($get_total_row_count as $result_value){
							$get_post_date_time = $wpdb->get_results("SELECT post_date FROM {$wpdb->prefix}posts WHERE id=$result_value" ,ARRAY_A);
							$get_post_date = date("Y-m-d",strtotime($get_post_date_time[0]['post_date'] ));
							if($get_post_date == $conditions['specific_period']['from']){
								$get_post_date_value[] = $result_value;
							}		
						}
						self::$export_instance->totalRowCount = count($get_post_date_value);
						$offset = self::$export_instance->offset;
						$limit = self::$export_instance->limit;
						$final_date_post=array_slice($get_post_date_value, $offset, $limit);
						$result = $final_date_post;

					}
					else{
						self::$export_instance->totalRowCount = count($get_total_row_count);
						$offset = self::$export_instance->offset;
						$limit = self::$export_instance->limit;
						$offset_limit = " order by ID asc limit $offset, $limit";
						$query_with_offset_limit = $get_post_ids . $offset_limit;
						$result = $wpdb->get_col($query_with_offset_limit);
					}
				}
				else{
					self::$export_instance->totalRowCount = count($get_total_row_count);
					$offset = self::$export_instance->offset;
					$limit = self::$export_instance->limit;
					$offset_limit = " order by ID asc limit $offset, $limit";
					$query_with_offset_limit = $get_post_ids . $offset_limit;
					$result = $wpdb->get_col($query_with_offset_limit);
				}
			}
			if(is_plugin_active('jet-engine/jet-engine.php')){
				$get_slug_name = $wpdb->get_results("SELECT slug FROM {$wpdb->prefix}jet_post_types WHERE status = 'content-type'");
				foreach($get_slug_name as $key=>$get_slug){
					$value=$get_slug->slug;
					$optional_type=$value;	
					if($optionalType ==$optional_type){
						$table_name='jet_cct_'.$optional_type;
						$get_total_row_count= $wpdb->get_results("SELECT _ID FROM {$wpdb->prefix}$table_name ");
						self::$export_instance->totalRowCount = count($get_total_row_count);
					}
				}
			}

			// Get sticky post alone on the specific post status
			if(isset($conditions['specific_period']['is_check']) && $conditions['specific_status']['is_check'] == 'true') {
				if(isset($conditions['specific_status']['status']) && $conditions['specific_status']['status'] == 'Sticky') {
					$get_sticky_posts = get_option('sticky_posts');
					foreach($get_sticky_posts as $sticky_post_id) {
						if(in_array($sticky_post_id, $result))
							$sticky_posts[] = $sticky_post_id;
					}
					return $sticky_posts;
				}
			}

		}
		$result = isset($result) ? $result : []; 
		return $result;
	}

	public function import_post_types($import_type, $importAs = null) {	
		$import_type = trim($import_type);
		$module = array('Posts' => 'post', 'Pages' => 'page', 'Users' => 'user', 'Comments' => 'comments', 'Taxonomies' => $importAs, 'CustomerReviews' =>'wpcr3_review', 'Categories' => 'categories', 'Tags' => 'tags', 'WooCommerce' => 'product', 'WPeCommerce' => 'wpsc-product','WPeCommerceCoupons' => 'wpsc-product','WooCommerceVariations' => 'product', 'WooCommerceOrders' => 'product', 'WooCommerceCoupons' => 'product', 'WooCommerceRefunds' => 'product', 'CustomPosts' => $importAs);
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

	private function getAllACFGroupKeys($post_id) {
		// Get all ACF field groups
		$field_groups = acf_get_field_groups();
		$group_keys = array();

		foreach ($field_groups as $group) {
			// Use ACF's location rules to check if this field group applies to the post ID
			if (acf_maybe_get($group, 'location')) {
				$is_group_for_post = acf_get_field_group_visibility($group, array('post_id' => $post_id));

				// If the group is assigned to this post, store the group key
				if ($is_group_for_post) {
					$group_keys[] = $group['key'];
				}
			}
		}

		return $group_keys;
	}

	function process_acf_field($field, $allacf, &$alltype, $parent_key = '') {
		$field_key = $field['name'];
		$full_key = !empty($parent_key) ? $parent_key . '_' . $field_key : $field_key;

		// Capture the type for the current field
		$alltype[$field_key] = isset($field['type']) ? $field['type'] : '';
		// If the field has subfields (group or repeater), process them recursively
		if (!empty($field['sub_fields'])) {
			$allacf[$field_key] = $full_key;

			foreach ($field['sub_fields'] as $sub_field) {
				$allacf = $this->process_acf_field($sub_field, $allacf, $alltype, $field_key); // Use immediate parent key
			}
		} else {
			// No subfields, so directly assign the key
			$allacf[$field_key] = $full_key;
		}
		return $allacf;
	}
	function get_valid_layout( $layout = array() ) {

		// parse
		$layout = wp_parse_args(
			$layout,
			array(
				'key'        => uniqid( 'layout_' ),
				'name'       => '',
				'label'      => '',
				'display'    => 'block',
				'sub_fields' => array(),
				'min'        => '',
				'max'        => '',
			)
		);

		// return
		return $layout;
	}
	function prepare_field_for_import( $field ) {

		// Bail early if no layouts
		if ( empty( $field['layouts'] ) ) {
			return $field;
		}

		// Storage for extracted fields.
		$extra = array();
		// Loop over layouts.
		foreach ( $field['layouts'] as &$layout ) {

			// Ensure layout is valid.
			$layout = $this->get_valid_layout( $layout );
			// Extract sub fields.
			$sub_fields = acf_extract_var( $layout, 'sub_fields' );

			// Modify and append sub fields to $extra.
			if ( $sub_fields ) {
				foreach ( $sub_fields as $i => $sub_field ) {

					// Update atttibutes
					$sub_field['parent']        = $field['key'];
					$sub_field['parent_layout'] = $layout['key'];
					$sub_field['menu_order']    = $i;

					// Append to extra.
					$extra[] = $sub_field;
				}
			}
		}
		// Merge extra sub fields.
		if ( $extra ) {
			//array_unshift( $extra, $field );
			return $extra;
		}

		// Return field.
		return $field;
	}
	public function getRepeater_api($field, $post_id) {
		$repeater_data = [];
		// Check if the field is a repeater, group, or flexible content
		if (in_array($field['type'], ['repeater', 'flexible_content', 'group'])) {
			if($field['type'] == 'flexible_content'){
				// $subfields_test = $field['layouts']['layout_6780d3be41ef1']['sub_fields'];
				$subfields = $this->prepare_field_for_import($field);
			}else{
				$subfields = $field['sub_fields']; // Access sub_fields directly
			}
			foreach ($subfields as $subfield) {
				// Use the field name as the key for the parent repeater
				$parent_key = $field['name'];

				// If the subfield is also a repeater or flexible content
				if (in_array($subfield['type'], ['repeater', 'flexible_content'])) {
					// Handle inner repeaters
					$repeater_data[$parent_key][] = $subfield['name'];

					// Recursively get the repeater data for inner repeaters
					$inner_repeater_data = $this->getRepeater_api($subfield, $post_id);
					$repeater_data = array_merge_recursive($repeater_data, $inner_repeater_data);
				} else {
					// Handle other field types (like text)
					// Here we add the text field to the parent repeater
					$repeater_data[$parent_key][] = $subfield['name'];

					// Also add the text field as a standalone entry
					$repeater_data[$subfield['name']] = '';  // Set it as empty or populate as needed
				}
			}
		} else {
			// If the field is not a repeater, flexible content, or group, add it directly
			$repeater_data[$field['name']] = $field['name'];
		}
		// Return the collected data
		return $repeater_data;
	}
	/**
	 * Function to export the meta information based on Fetch ACF field information to be export
	 * @param $id
	 * @return mixed
	 */
	public function getPostsMetaDataBasedOnRecordId ($id, $module, $optionalType,$headers = null,$eventExclusions = null) {	
		global $wpdb;
		$allacf = $alltype = $checkRep = $parent = $typesf = array();

		if($module == 'Users'){
			$query = $wpdb->prepare("SELECT user_id,meta_key,meta_value FROM {$wpdb->prefix}users wp JOIN {$wpdb->prefix}usermeta wpm ON wpm.user_id = wp.ID where meta_key NOT IN (%s,%s) AND ID=%d", '_edit_lock', '_edit_last', $id);
		}else if($module == 'Categories' || $module == 'Taxonomies' || $module == 'Tags'){
			$query = $wpdb->prepare("SELECT wp.term_id,meta_key,meta_value FROM {$wpdb->prefix}terms wp JOIN {$wpdb->prefix}termmeta wpm ON wpm.term_id = wp.term_id where meta_key NOT IN (%s,%s) AND wp.term_id = %d", '_edit_lock', '_edit_last', $id);

		}else{
			$query = $wpdb->prepare("SELECT post_id,meta_key,meta_value FROM {$wpdb->prefix}posts wp JOIN {$wpdb->prefix}postmeta wpm ON wpm.post_id = wp.ID where meta_key NOT IN (%s,%s,%s) AND ID=%d", '_edit_lock', '_edit_last', '_default_attributes', $id);
		}

		$get_acf_fields = $wpdb->get_results("SELECT ID, post_excerpt, post_content, post_name, post_parent, post_type FROM {$wpdb->prefix}posts where post_type = 'acf-field'", ARRAY_A);
		$group_unset = array('customer_email', 'product_categories', 'exclude_product_categories');

		/************************************* Before Acf api code  ********************************************/

		// if(!empty($get_acf_fields)){
		// 	foreach ($get_acf_fields as $key => $value) {
		// 		if(!empty($value['post_parent'])){
		// 			$parent = get_post($value['post_parent']);
		// 			if(!empty($parent)){
		// 				if($parent->post_type == 'acf-field'){
		// 					$allacf[$value['post_excerpt']] = $parent->post_excerpt.'_'.$value['post_excerpt']; 
		// 				}else{
		// 					$allacf[$value['post_excerpt']] = $value['post_excerpt']; 	
		// 				}
		// 			}else{
		// 				$allacf[$value['post_excerpt']] = $value['post_excerpt']; 
		// 			}
		// 		}else{
		// 			$allacf[$value['post_excerpt']] = $value['post_excerpt']; 
		// 		}

		// 		self::$export_instance->allacf = $allacf;

		// 		$content = unserialize($value['post_content']);
		// 		$alltype[$value['post_excerpt']] = isset($content['type']) ? $content['type'] : '';

		// 		if(!empty($content['type']) && ($content['type'] == 'repeater' || $content['type'] == 'flexible_content'|| $content['type'] == 'group') ){
		// 			$checkRep[$value['post_excerpt']] = $this->getRepeater($value['ID']);
		// 		}else{
		// 			$checkRep[$value['post_excerpt']] = "";
		// 		}
		// 	}
		// }

		/************************************* After Acf api code ********************************************/
		if(is_plugin_active('advanced-custom-fields-pro/acf.php') || is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('secure-custom-fields/secure-custom-fields.php')){
			$get_acf_groups = array();
			$fieldgrouparray = $this->getAllACFGroupKeys($id);
			$get_acf_groups = array();
			$group_id_arr = array(); 
			// Fetch ACF field groups
			foreach($fieldgrouparray as $fieldgroupkey){
				$get_acf_groups[] = acf_get_field_group($fieldgroupkey);
			}
			foreach ( $get_acf_groups as $group_rules ) {
				if (!empty($group_rules)) {
					// Check if the group is active
					if (isset($group_rules['active']) && $group_rules['active']) {
						// Check if not for 'Users'
						if ($module != 'Users') {
							foreach ($group_rules['location'] as $location) {
								if ($location[0]['operator'] == '==' && $location[0]['value'] == $this->import_post_types($optionalType)) {
									$group_id_arr[] = $group_rules['key'];
								} elseif ($location[0]['operator'] == '==' && $location[0]['value'] == 'all' && $location[0]['param'] == 'taxonomy' && in_array($this->import_post_types($optionalType), get_taxonomies())) {
									$group_id_arr[] = $group_rules['key'];
								}
							}
						} else {
							foreach ($group_rules['location'] as $location) {
								if ($location[0]['operator'] == '==' && $location[0]['param'] == 'user_role') {
									$group_id_arr[] = $group_rules['key'];
								}
							}
						}
					}
				}
			}
			foreach($group_id_arr as $groupId) {
				$get_acf_fields = acf_get_fields($groupId);
				if (!empty($get_acf_fields)) {
					foreach ($get_acf_fields as $field) {
						$field_key = $field['name'];
						// Handle the field and its subfields recursively
						$allacf = $this->process_acf_field($field, $allacf, $alltype, ''); // Pass an empty string for the parent key
						if (!empty($field['type']) && in_array($field['type'], ['repeater', 'flexible_content', 'group'])) {
							// Merge the new repeater data into checkRep
							$newCheckRep = $this->getRepeater_api($field, $id);
							$checkRep = array_merge_recursive($checkRep, $newCheckRep);
						}
					}
				}

				self::$export_instance->allacf = $allacf;
			}
		}

		/************************************* end  ********************************************/
		// Retrieve all taxonomies
		$taxonomies = get_taxonomies();

		if (in_array($optionalType, $taxonomies)) {
			// Retrieve the taxonomy term
			$term = get_term_by('slug', $optionalType, $taxonomies);

			if ($term) {
				// Get the term ID
				$term_id = $term->term_id;

				// Retrieve the ACF fields
				$acf_fields = get_fields($term_id);

				if (!empty($acf_fields)) {
					// Loop through the ACF fields and retrieve the meta values
					foreach ($acf_fields as $field_name => $field_value) {
						// Process each field as needed
						$meta_value = $field_value;
						// ...
					}
				} else {
					// No ACF fields found
					$meta_value = '';
				}
			} else {
				// Term not found
				$meta_value = '';
			}
		} else {
			// Taxonomy not found
			$meta_value = '';
		}

		self::$export_instance->allpodsfields = $this->getAllPodsFields();

		if($module == 'Categories' || $module == 'Tags' || $module == 'Taxonomies'){
			self::$export_instance->alltoolsetfields = get_option('wpcf-termmeta');
		}
		elseif($module == 'Users'){
			self::$export_instance->alltoolsetfields = get_option('wpcf-usermeta');

		}
		else{
			self::$export_instance->alltoolsetfields = get_option('wpcf-fields');
		}

		if(!empty(self::$export_instance->alltoolsetfields)){
			$i = 1;
			foreach (self::$export_instance->alltoolsetfields as $key => $value) {
				$typesf[$i] = 'wpcf-'.$key;
				$typeOftypesField[$typesf[$i]] = $value['type']; 
				$i++;
			}
		}
		foreach($typeOftypesField as $type_key => $type_val){
			if($type_val == 'post'){
				$toolset_fields = get_option('wpcf-fields');
				$toolkey = explode('wpcf-',$type_key) ;
				$tools_key = end($toolkey);
				$rel_slug = $toolset_fields[$tools_key]['data']['relationship_slug'];
				$rel_id =$wpdb->get_var("SELECT id FROM {$wpdb->prefix}toolset_relationships WHERE slug ='$rel_slug'");
				$child_id = $wpdb->get_var("SELECT ta.child_id FROM {$wpdb->prefix}toolset_connected_elements  as tc INNER JOIN {$wpdb->prefix}toolset_associations as ta ON tc.group_id=ta.parent_id WHERE ta.relationship_id =$rel_id AND tc.element_id=$id");
				$parent_id = $wpdb->get_var("SELECT ta.parent_id FROM {$wpdb->prefix}toolset_connected_elements  as tc INNER JOIN {$wpdb->prefix}toolset_associations as ta ON tc.group_id=ta.child_id WHERE ta.relationship_id =$rel_id AND tc.element_id=$id");
				if(!empty($parent_id)){
					$element_id	= $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}toolset_connected_elements WHERE  group_id=$parent_id");
					$posttitle = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE  ID=$element_id");
					self::$export_instance->data[$id][$tools_key] = $posttitle;
				}
				if(!empty($child_id)){
					$element_id	= $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}toolset_connected_elements WHERE  group_id=$child_id");
					$posttitle = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE  ID=$element_id");
					self::$export_instance->data[$id][$tools_key] = $posttitle;
				}
			}

		}
		$typeOftypesField=isset($typeOftypesField)?$typeOftypesField:'';
		self::$export_instance->typeOftypesField = $typeOftypesField;	
		$result = $wpdb->get_results($query);
		if (is_plugin_active('jet-booking/jet-booking.php')) {
			$manage_units = jet_abaf()->db->get_apartment_units( $id );
			if(!empty($manage_units)) {

				$titleCounts = [];
				foreach ($manage_units as $unit) {
					// Remove any trailing space followed by numbers (e.g., " 1", " 2", " 3") at the end of unit_title
					$baseTitle = preg_replace('/\s+\d+$/', '', $unit['unit_title']);
					if (!isset($titleCounts[$baseTitle])) {
						$titleCounts[$baseTitle] = 0;
					}
					$titleCounts[$baseTitle]++;
				}
				self::$export_instance->data[$id]['unit_title'] = implode('|', array_keys($titleCounts));
				self::$export_instance->data[$id]['unit_number'] = implode('|', array_values($titleCounts));
			}
		}
		// jeteng fields
		if(is_plugin_active('jet-engine/jet-engine.php')){
			//$jet_enginefields=$wpdb->get_results( $wpdb->prepare("SELECT id, meta_fields FROM {$wpdb->prefix}jet_post_types WHERE status != 'trash' AND slug = '$optionalType'"),ARRAY_A);
			//$jet_enginefields=$wpdb->get_results( $wpdb->prepare("SELECT id, meta_fields FROM {$wpdb->prefix}jet_post_types WHERE status = %s AND slug = %s",'publish',$optionalType),ARRAY_A);

			$jet_enginefields = $wpdb->get_results("SELECT id, meta_fields FROM {$wpdb->prefix}jet_post_types WHERE slug = '$optionalType' AND status IN ('publish','built-in')", ARRAY_A);
			$jet_enginefields[0]['meta_fields']=isset($jet_enginefields[0]['meta_fields'])?$jet_enginefields[0]['meta_fields']:'';

			$unserialized_meta = maybe_unserialize($jet_enginefields[0]['meta_fields']);
			$unserialized_meta=isset($unserialized_meta)?$unserialized_meta:'';

			if(is_array($unserialized_meta)){
				foreach($unserialized_meta as $jet_key => $jet_value){
					$jet_field_label = $jet_value['title'];
					$jet_field_type = $jet_value['type'];
					if($jet_field_type != 'repeater'){					
						$jet_field_namearr[] = $jet_value['name'];
					}
					else{
						$jet_field_namearr[] = $jet_value['name'];
						$fields=$jet_value['repeater-fields'];
						foreach($fields as $rep_fieldkey => $rep_fieldvalue){
							$jet_field_namearr1[] = $rep_fieldvalue['name'];

						}
					}
				}	
			}

			if(isset($jet_field_namearr1) && is_array($jet_field_namearr1) ){
				if(is_array($jet_field_namearr)){
					$jet_cpt_fields_name=array_merge($jet_field_namearr,$jet_field_namearr1);
				}
				else{
					$jet_cpt_fields_name= $jet_field_namearr1;
				}

			}
			else{
				$jet_field_namearr = isset($jet_field_namearr) ? $jet_field_namearr : '';
				$jet_cpt_fields_name= $jet_field_namearr;
			}

			//jeteng metabox fields

			global $wpdb;	
			//$get_meta_fields = $wpdb->get_results( $wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name='jet_engine_meta_boxes'"),ARRAY_A);
			$get_meta_fields = $wpdb->get_results( $wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name=%s",'jet_engine_meta_boxes'),ARRAY_A);			
			if(!empty($get_meta_fields)){
				$unserialized_meta = maybe_unserialize($get_meta_fields[0]['option_value']);
			}
			else{
				$unserialized_meta = '';
			}

			if(is_array($unserialized_meta)){
				$arraykeys = array_keys($unserialized_meta);

				foreach($arraykeys as $val){
					$values = explode('-',$val);
					$v = $values[1];
				}
			}


			$jet_field_name1 = [];
			if(isset($v)){
				for($i=1 ; $i<=$v ; $i++){
					$unserialized_meta['meta-'.$i]= isset($unserialized_meta['meta-'.$i])? $unserialized_meta['meta-'.$i] : '';
					$fields= $unserialized_meta['meta-'.$i];					
					if(!empty($fields)){
						foreach($fields['meta_fields'] as $jet_key => $jet_value){
							if($jet_value['type'] != 'repeater'){
								$jet_field_name1[] = $jet_value['name'];
							}
							else{
								$jet_field_name1[] = $jet_value['name'];
								$jet_rep_fields = $jet_value['repeater-fields'];
								foreach($jet_rep_fields as $jet_rep_fkey => $jet_rep_fvalue){
									$jet_field_name2[] = $jet_rep_fvalue['name'];
								}
							}
						}
					}

				}
			}	
			if( isset($jet_field_name2) && is_array($jet_field_name2)){
				if(is_array($jet_field_name1)){
					$jet_field_name = array_merge($jet_field_name1,$jet_field_name2);
				}
				else{
					$jet_field_name= $jet_field_name2;
				}
			}
			else{
				$jet_field_name= $jet_field_name1;
			}


			//}

			///jetengine custom taxonomy fields
			//$jet_taxfields=$wpdb->get_results( $wpdb->prepare("SELECT id, meta_fields FROM {$wpdb->prefix}jet_taxonomies WHERE status != 'trash' AND slug = '$optionalType'"),ARRAY_A);
			$jet_taxfields=$wpdb->get_results( $wpdb->prepare("SELECT id, meta_fields FROM {$wpdb->prefix}jet_taxonomies WHERE status != %s AND slug = %s",'trash',$optionalType),ARRAY_A);
			if(!empty($jet_taxfields)){
				$unserialized_taxmeta= maybe_unserialize($jet_taxfields[0]['meta_fields']);
			}
			else{
				$unserialized_taxmeta= '';
			}

			if(is_array($unserialized_taxmeta))	{
				foreach($unserialized_taxmeta as $jet_taxkey => $jet_taxvalue){

					$jet_field_tax_label = $jet_taxvalue['title'];
					$jet_field_tax_type = $jet_taxvalue['type'];
					if($jet_field_tax_type != 'repeater'){
						$jet_field_tax_namearr[] = $jet_taxvalue['name'];
					}
					else{
						$jet_field_tax_namearr[] = $jet_taxvalue['name'];
						$taxfields=$jet_taxvalue['repeater-fields'];
						foreach($taxfields as $rep_taxfieldkey => $rep_taxfieldvalue){
							if(isset($rep_taxfieldvalue['name'])){
								$jet_field_tax_namearr1[] = $rep_taxfieldvalue['name'];
							}
						}
					}	
				}
			}

			if( isset($jet_field_tax_namearr1)){
				if(is_array($jet_field_tax_namearr)){
					$jet_tax_fields_name=array_merge($jet_field_tax_namearr,$jet_field_tax_namearr1);
				}
				else{
					$jet_tax_fields_name=$jet_field_tax_namearr1;
				}

			}
			else{
				$jet_field_tax_namearr = isset($jet_field_tax_namearr) ? $jet_field_tax_namearr : '';
				$jet_tax_fields_name=$jet_field_tax_namearr ;
			}

		}
		else{
			$jet_cpt_fields_name =$jet_field_name= $jet_tax_fields_name = '';
		}

		//added for metabox plugin fields
		if(is_plugin_active('meta-box/meta-box.php')  || is_plugin_active('meta-box-lite/meta-box-lite.php')){
			$metabox_import_type = self::import_post_types($module, $optionalType);
			$metabox_fields = \rwmb_get_object_fields( $metabox_import_type ); 
			$taxonomies = get_taxonomies();

			if ($metabox_import_type == 'user')
			{
				$metabox_fields = \rwmb_get_object_fields($metabox_import_type, 'user');
			}
			else if (array_key_exists($metabox_import_type, $taxonomies))
			{
				$metabox_fields = \rwmb_get_object_fields($metabox_import_type, 'term');
			}
			else
			{
				$metabox_fields = \rwmb_get_object_fields($metabox_import_type);
			}
			$this->getCustomFieldValue($id, $value=null, $checkRep, $allacf, $typeOftypesField, $alltype, $jet_fields=null, $jet_types=null, $jet_rep_fields =null, $jet_rep_types=null,  $parent, $typesf, $group_unset , $optionalType , self::$export_instance->allpodsfields, $metabox_fields, $module, $metabox_relation_fields =null);			
		}
		else{
			$metabox_fields = [];
		}
		if (is_plugin_active('meta-box-aio/meta-box-aio.php')){
			$extension_object = new MetaBoxGroupExtension();

			if($module == 'Categories' || $module == 'Taxonomies' || $module == 'Tags')
			{
				$module = $optionalType;
			}

			$metabox_import_types = $extension_object->import_post_types($module, $optionalType);
			$taxonomies = get_taxonomies();
			if ($metabox_import_types == 'user')
			{
				$metabox_relation_fields = \rwmb_get_object_fields($metabox_import_types, 'user');
			}
			else if (array_key_exists($metabox_import_types, $taxonomies))
			{
				$metabox_relation_fields = \rwmb_get_object_fields($metabox_import_types, 'term');
			}
			else
			{
				$metabox_relation_fields = \rwmb_get_object_fields($metabox_import_types);

			}
			//$metabox_fields = [];
			$this->getCustomFieldValue($id, $value=null, $checkRep, $allacf, $typeOftypesField, $alltype, $jet_fields, $jet_types, $jet_rep_fields, $jet_rep_types,  $parent, $typesf, $group_unset , $optionalType , self::$export_instance->allpodsfields, $metabox_fields =null, $module, $metabox_relation_fields);			
		}
		else{
			$metabox_relation_fields = [];

		}
		if(!empty($result)) {
			$this->image = array();
			foreach($result as $key => $value) {
				if(is_array($jet_cpt_fields_name)&& isset($value->meta_key)){
					if(in_array($value->meta_key,$jet_cpt_fields_name) || str_ends_with($value->meta_key, '__config')){
						if(str_ends_with($value->meta_key, '__config')){
							$value->meta_key = explode('__config', $value->meta_key)[0];
						} 
						//$jet_enginefields=$wpdb->get_results( $wpdb->prepare("SELECT id, meta_fields FROM {$wpdb->prefix}jet_post_types WHERE status != 'trash' AND slug = '$optionalType'"),ARRAY_A);

						//$jet_enginefields=$wpdb->get_results( $wpdb->prepare("SELECT id, meta_fields FROM {$wpdb->prefix}jet_post_types WHERE status != %s AND slug = %s",'trash',$optionalType),ARRAY_A);
						$jet_enginefields = $wpdb->get_results("SELECT id, meta_fields FROM {$wpdb->prefix}jet_post_types WHERE slug = '$optionalType' AND status IN ('publish','built-in')", ARRAY_A);
						if(!empty($jet_enginefields)){
							$unserialized_meta = maybe_unserialize($jet_enginefields[0]['meta_fields']);
						}
						else{
							$unserialized_meta = '';
						}
						$jet_types=array();
						$jet_rep_cpttypes=array();
						$jet_rep_cptfields = array();
						$jet_cptfields = array();
						foreach($unserialized_meta as $jet_key => $jet_value){
							$jet_field_label = $jet_value['title'];
							$jet_cptfield_names = $jet_value['name'];
							$jet_field_type = $jet_value['type'];
							if($jet_field_type != 'repeater'){
								$jet_cptfields[$jet_cptfield_names]=$jet_cptfield_names;
								$jet_types[$jet_cptfield_names] = $jet_field_type;
							}
							else{
								$jet_cptfields[$jet_cptfield_names]=$jet_cptfield_names;
								$jet_types[$jet_cptfield_names] = $jet_field_type;
								$fields=$jet_value['repeater-fields'];
								foreach($fields as $rep_fieldkey => $rep_fieldvalue){
									$jet_rep_cptfields_label = $rep_fieldvalue['name'];
									$jet_rep_cptfields_type  = $rep_fieldvalue['type'];
									$jet_rep_cptfields[$jet_rep_cptfields_label] = $jet_rep_cptfields_label;
									$jet_rep_cpttypes[$jet_rep_cptfields_label]  = $jet_rep_cptfields_type;
								}
							}

						}
						self::$export_instance->jet_cptfields = $jet_cptfields;
						self::$export_instance->jet_types = $jet_types;
						if(isset($jet_rep_cptfields)){
							self::$export_instance->jet_rep_cptfields = $jet_rep_cptfields;
							self::$export_instance->jet_rep_cpttypes  = $jet_rep_cpttypes;
						}
						else{
							$jet_rep_cptfields = '';
							$jet_rep_cpttypes = '';
						}

						$this->getCustomFieldValue($id, $value, $checkRep, $allacf, $typeOftypesField, $alltype, $jet_cptfields, $jet_types, $jet_rep_cptfields, $jet_rep_cpttypes,  $parent, $typesf, $group_unset , $optionalType , self::$export_instance->allpodsfields, $metabox_fields, $module, $metabox_relation_fields);
					}
				}
				if((isset($value->meta_key) && is_array($jet_field_name))){
					if(in_array($value->meta_key,$jet_field_name)){
						//$get_meta_fields = $wpdb->get_results( $wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name='jet_engine_meta_boxes'"),ARRAY_A);
						$get_meta_fields = $wpdb->get_results( $wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name=%s",'jet_engine_meta_boxes'),ARRAY_A);
						$get_meta_fields[0]['option_value'] = isset($get_meta_fields[0]) ? $get_meta_fields[0]['option_value'] : '';
						$unserialized_meta = maybe_unserialize($get_meta_fields[0]['option_value']);
						//$count =count($unserialized_meta);
						if(is_array($unserialized_meta)){
							$arraykeys = array_keys($unserialized_meta);

							foreach($arraykeys as $val){
								$values = explode('-',$val);
								$v = $values[1];
							}
						}


						//$jet_rep_fields=[];
						for($i=1 ; $i<=$v ; $i++){
							$unserialized_meta['meta-'.$i] = isset($unserialized_meta['meta-'.$i])? $unserialized_meta['meta-'.$i] :'';
							$fields = $unserialized_meta['meta-'.$i];
							if(!empty($fields)){
								$jet_metatypes=array();
								$jet_reptype=array();
								foreach($fields['meta_fields'] as $jet_key => $jet_value){
									$jet_field_label = $jet_value['title'];
									$jet_field_names = $jet_value['name'];
									$jet_field_type = $jet_value['type'];
									if($jet_field_type != 'repeater'){

										$jet_metafields[$jet_field_names]=$jet_field_names;

										$jet_metatypes[$jet_field_names] = $jet_field_type;

									}
									else{
										$jet_metafields[$jet_field_names]=$jet_field_names;
										$jet_metatypes[$jet_field_names] = $jet_field_type;
										$repfields=$jet_value['repeater-fields'];
										$jet_repfield=array();
										//$jet_reptype=array();
										foreach($repfields as $rep_fieldkey => $rep_fieldvalue){
											$jet_rep_fields_label = $rep_fieldvalue['name'];
											$jet_rep_fields_type  = $rep_fieldvalue['type'];

											$jet_repfield[$jet_rep_fields_label] = $jet_rep_fields_label;
											$jet_reptype[$jet_rep_fields_label]  = $jet_rep_fields_type;
										}
									}		
								}
							}

							self::$export_instance->jet_metafields = $jet_metafields;
							self::$export_instance->jet_metatypes = $jet_metatypes;
							if(!empty($jet_repfield)){
								self::$export_instance->jet_repfield = $jet_repfield;
								self::$export_instance->jet_reptype  = $jet_reptype;
							}
							else{
								$jet_repfield = '';
								$jet_reptype = '';
							}
							$this->getCustomFieldValue($id, $value, $checkRep, $allacf, $typeOftypesField, $alltype, $jet_metafields, $jet_metatypes, $jet_repfield, $jet_reptype,  $parent, $typesf, $group_unset , $optionalType , self::$export_instance->allpodsfields, $metabox_fields, $module,$metabox_relation_fields);
						}	
					}
				}
				if(isset($value->meta_key)&& is_array($jet_tax_fields_name)){
					if(in_array($value->meta_key,$jet_tax_fields_name)){
						//$jety_taxfields=$wpdb->get_results( $wpdb->prepare("SELECT id, meta_fields FROM {$wpdb->prefix}jet_taxonomies WHERE status != 'trash' AND slug = '$optionalType'"),ARRAY_A);
						$jety_taxfields=$wpdb->get_results( $wpdb->prepare("SELECT id, meta_fields FROM {$wpdb->prefix}jet_taxonomies WHERE status != %s AND slug = %s",'trash',$optionalType),ARRAY_A);

						$taxunserialized_meta = maybe_unserialize($jety_taxfields[0]['meta_fields']);
						foreach($taxunserialized_meta as $tax_key => $tax_value){
							$jet_taxfield_label = $tax_value['title'];
							$jet_taxfield_names = $tax_value['name'];
							$jet_taxfield_type = $tax_value['type'];
							if($jet_taxfield_type != 'repeater'){
								$jet_ctax_fields[$jet_taxfield_names]=$jet_taxfield_names;
								$jet_tax_types[$jet_taxfield_names] = $jet_taxfield_type;
							}
							else{
								$jet_ctax_fields[$jet_taxfield_names]=$jet_taxfield_names;
								$jet_tax_types[$jet_taxfield_names] = $jet_taxfield_type;
								$taxfields=$tax_value['repeater-fields'];
								foreach($taxfields as $rep_taxfieldkey => $rep_taxfieldvalue){
									$jet_rep_taxfields_label = $rep_taxfieldvalue['name'];
									$jet_rep_taxfields_type  = $rep_taxfieldvalue['type'];
									$jet_rep_taxfields[$jet_rep_taxfields_label] = $jet_rep_taxfields_label;
									$jet_rep_taxtypes[$jet_rep_taxfields_label]  = $jet_rep_taxfields_type;
								}
							}

						}
						self::$export_instance->jet_taxfields = $jet_ctax_fields;
						self::$export_instance->jet_taxtypes = $jet_tax_types;
						if(isset($jet_rep_taxfields)){
							self::$export_instance->jet_rep_taxfields = $jet_rep_taxfields;
							self::$export_instance->jet_rep_taxtypes  = $jet_rep_taxtypes;
						}
						else{
							$jet_rep_taxfields = '';
							$jet_rep_taxtypes = '';
						}
						$this->getCustomFieldValue($id, $value, $checkRep, $allacf, $typeOftypesField, $alltype, $jet_ctax_fields, $jet_tax_types, $jet_rep_taxfields, $jet_rep_taxtypes,  $parent, $typesf, $group_unset , $optionalType , self::$export_instance->allpodsfields, $metabox_fields, $module, $metabox_relation_fields);
					}
				}
				if(is_plugin_active('seo-by-rank-math/rank-math.php')|| is_plugin_active('seo-by-rank-math-pro/rank-math-pro.php')){
					if($value->meta_key == 'rank_math_focus_keyword'){
						self::$export_instance->data[$id]['rank_math_focus_keyword'] = $value->meta_value;
					}
					elseif($value->meta_key == 'rank_math_description'){
						self::$export_instance->data[$id]['rank_math_description'] = $value->meta_value;
					}
					elseif($value->meta_key == 'rank_math_pillar_content'){
						self::$export_instance->data[$id]['rank_math_pillar_content'] = $value->meta_value;
					}
					elseif($value->meta_key == 'rank_math_facebook_title'){
						self::$export_instance->data[$id]['rank_math_facebook_title'] = $value->meta_value;
					}
					elseif($value->meta_key == 'rank_math_facebook_description'){
						self::$export_instance->data[$id]['rank_math_facebook_description'] = $value->meta_value;
					}
					elseif($value->meta_key == 'rank_math_schema_Article'){

						$unserialize_schema_article = unserialize($value->meta_value);
						$article_type = $unserialize_schema_article['@type'];
						$headline = $unserialize_schema_article['headline'];
						$schema_description = $unserialize_schema_article['description'];
						$speakable_type = $unserialize_schema_article['speakable']['@type'];
						$css_selector = $unserialize_schema_article['speakable']['cssSelector'];
						$date_published = $unserialize_schema_article['datePublished'];
						$date_modified = $unserialize_schema_article['dateModified'];
						$image_type = $unserialize_schema_article['image']['@type'];
						$image_url = $unserialize_schema_article['image']['url'];
						$author_type = $unserialize_schema_article['author']['@type'];
						$author_name = $unserialize_schema_article['author']['name'];
						$enable_speakable = $unserialize_schema_article['metadata']['enableSpeakable'];

						if(is_array($css_selector)){
							self::$export_instance->data[$id]['cssSelector'] = implode(',',$css_selector);
						}


						self::$export_instance->data[$id]['article_type'] = $article_type;
						self::$export_instance->data[$id]['headline'] = $headline;
						self::$export_instance->data[$id]['schema_description'] = $schema_description;
						self::$export_instance->data[$id]['css_selector'] = $css_selector;
						self::$export_instance->data[$id]['date_published'] = $date_published;
						self::$export_instance->data[$id]['date_modified'] = $date_modified;
						self::$export_instance->data[$id]['image_type'] = $image_type;
						self::$export_instance->data[$id]['image_url'] = $image_url;
						self::$export_instance->data[$id]['author_type'] = $author_type;
						self::$export_instance->data[$id]['author_name'] = $author_name;
						self::$export_instance->data[$id]['speakable_type'] = $speakable_type;
						self::$export_instance->data[$id]['enable_speakable'] = $enable_speakable;


					}
					elseif($value->meta_key == 'rank_math_schema_Dataset'){
						$unserialize_schema_datatset = unserialize($value->meta_value);
						$ds_name = $unserialize_schema_datatset['name'];
						$ds_description = $unserialize_schema_datatset['description'];
						$ds_url = $unserialize_schema_datatset['url'];
						$ds_sameAs = $unserialize_schema_datatset['ds_sameAs'];

						if(isset($unserialize_schema_datatset['identifier']) && is_array($unserialize_schema_datatset['identifier'])){
							$ds_identifier = implode(',',$unserialize_schema_datatset['identifier']);
						}
						if(isset($unserialize_schema_datatset['keywords']) && is_array($unserialize_schema_datatset['keywords'])){
							$ds_keywords = implode(',',$unserialize_schema_datatset['keywords']);
						}
						$ds_license = $unserialize_schema_datatset['license'];
						$ds_temp_coverage = $unserialize_schema_datatset['temporalCoverage'];
						$ds_spatial_coverage = $unserialize_schema_datatset['spatialCoverage'];
						$encodingFormat = $unserialize_schema_datatset['distribution'][0]['encodingFormat'];
						$contentUrl = $unserialize_schema_datatset['distribution'][0]['contentUrl'];
						$creator_type = $unserialize_schema_datatset['hasPart'][0]['creator']['@type'];
						$creator_name = $unserialize_schema_datatset['hasPart'][0]['creator']['name'];
						$creator_sameAs = $unserialize_schema_datatset['hasPart'][0]['creator']['sameAs'];
						$ds_cat_name = $unserialize_schema_datatset['includedInDataCatalog']['name'];
						self::$export_instance->data[$id]['ds_name'] = $ds_name;
						self::$export_instance->data[$id]['ds_description'] = $ds_description;
						self::$export_instance->data[$id]['ds_identifier'] = $ds_identifier;
						self::$export_instance->data[$id]['ds_keywords'] = $ds_keywords;
						self::$export_instance->data[$id]['ds_license'] = $ds_license;
						self::$export_instance->data[$id]['ds_temp_coverage'] = $ds_temp_coverage;
						self::$export_instance->data[$id]['ds_spatial_coverage'] = $ds_spatial_coverage;
						self::$export_instance->data[$id]['encodingFormat'] = $encodingFormat;
						self::$export_instance->data[$id]['contentUrl'] = $contentUrl;
						self::$export_instance->data[$id]['creator_type'] = $creator_type;
						self::$export_instance->data[$id]['creator_name'] = $creator_name;
						self::$export_instance->data[$id]['creator_sameAs'] = $creator_sameAs;
						self::$export_instance->data[$id]['ds_url'] = $ds_url;
						self::$export_instance->data[$id]['ds_sameAs'] = $ds_sameAs;
						self::$export_instance->data[$id]['ds_cat_name'] = $ds_cat_name;

					}
					else{
						self::$export_instance->data[$id][$value->meta_key] = $value->meta_value;
					}
					// else{
					// 	self::$export_instance->data[$id][$value->meta_key] = $value->meta_value;
					// }
				}
				if(is_plugin_active('wordpress-seo/wp-seo.php') || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php')){
					self::$export_instance->data[$id]['seo_title'] = self::$export_instance->data[$id]['post_title'];
					if($value->meta_key == '_yoast_wpseo_focuskw'){
						self::$export_instance->data[$id]['focus_keyword'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_linkdex'){
						self::$export_instance->data[$id]['linkdex'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_meta-robots-noindex'){
						self::$export_instance->data[$id]['meta-robots-noindex'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_metadesc'){
						self::$export_instance->data[$id]['meta_desc'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_opengraph-description'){
						self::$export_instance->data[$id]['opengraph-description'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_opengraph-title'){
						self::$export_instance->data[$id]['opengraph-title'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_twitter-title'){
						self::$export_instance->data[$id]['twitter-title'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_google-plus-title'){
						self::$export_instance->data[$id]['google-plus-title'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_google-plus-description'){
						self::$export_instance->data[$id]['google-plus-description'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_google-plus-image'){
						self::$export_instance->data[$id]['google-plus-image'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_twitter-description'){
						self::$export_instance->data[$id]['twitter-description'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_twitter-image'){
						self::$export_instance->data[$id]['twitter-image'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_bctitle'){
						self::$export_instance->data[$id]['bctitle'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_canonical'){
						self::$export_instance->data[$id]['canonical'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_redirect'){
						self::$export_instance->data[$id]['redirect'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_opengraph-image'){
						self::$export_instance->data[$id]['opengraph-image'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_meta-robots-nofollow'){
						self::$export_instance->data[$id]['meta-robots-nofollow'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_meta-robots-adv'){
						self::$export_instance->data[$id]['meta-robots-adv'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_cornerstone-content'){
						self::$export_instance->data[$id]['cornerstone-content'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_focuskeywords'){
						self::$export_instance->data[$id]['focuskeywords'] = $value->meta_value;
					}					
					if($value->meta_key == '_yoast_wpseo_keywordsynonyms'){
						$meta_value = json_decode($value->meta_value, true);
						// Ensure it's an array
						if (is_array($meta_value)) {
							$value->meta_value = implode('|', $meta_value);
						} else {
							// Handle the case where the meta_value is not an array
							$value->meta_value = (string)$meta_value;
						}
						self::$export_instance->data[$id]['keywordsynonyms'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_schema_page_type'){
						self::$export_instance->data[$id]['schema_page_type'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_schema_article_type'){
						self::$export_instance->data[$id]['schema_article_type'] = $value->meta_value;
					}
					if($value->meta_key == '_yoast_wpseo_title'){
						self::$export_instance->data[$id]['seo_title'] = $value->meta_value;
					}
				}
				if(is_plugin_active('wp-seopress/seopress.php') || is_plugin_active('wp-seopress-pro/seopress-pro.php.php')){
					self::$export_instance->data[$id]['seo_title'] = self::$export_instance->data[$id]['post_title'];

					if($value->meta_key == '_seopress_titles_title'){
						self::$export_instance->data[$id]['_seopress_titles_title'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_titles_desc'){
						self::$export_instance->data[$id]['_seopress_titles_desc'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_robots_index'){
						self::$export_instance->data[$id]['_seopress_robots_index'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_robots_follow'){
						self::$export_instance->data[$id]['_seopress_robots_follow'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_robots_imageindex'){
						self::$export_instance->data[$id]['_seopress_robots_imageindex'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_robots_archive'){
						self::$export_instance->data[$id]['_seopress_robots_archive'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_robots_canonical'){
						self::$export_instance->data[$id]['_seopress_robots_canonical'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_analysis_target_kw'){
						self::$export_instance->data[$id]['_seopress_analysis_target_kw'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_robots_primary_cat'){
						self::$export_instance->data[$id]['_seopress_robots_primary_cat'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_robots_breadcrumbs'){
						self::$export_instance->data[$id]['_seopress_robots_breadcrumbs'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_social_fb_title'){
						self::$export_instance->data[$id]['_seopress_social_fb_title'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_social_fb_desc'){
						self::$export_instance->data[$id]['_seopress_social_fb_desc'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_social_fb_img'){
						self::$export_instance->data[$id]['_seopress_social_fb_img'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_social_twitter_title'){
						self::$export_instance->data[$id]['_seopress_social_twitter_title'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_social_twitter_desc'){
						self::$export_instance->data[$id]['_seopress_social_twitter_desc'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_social_twitter_img'){
						self::$export_instance->data[$id]['_seopress_social_twitter_img'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_redirections_type'){
						self::$export_instance->data[$id]['_seopress_redirections_type'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_redirections_value'){
						self::$export_instance->data[$id]['_seopress_redirections_value'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_redirections_enabled'){
						self::$export_instance->data[$id]['_seopress_redirections_enabled'] = $value->meta_value;
					}
					if($value->meta_key == '_seopress_redirections_logged_status'){
						self::$export_instance->data[$id]['_seopress_redirections_logged_status'] = $value->meta_value;
					}					
				}

				if(is_plugin_active('featured-image-from-url/featured-image-from-url.php')){
					self::$export_instance->data[$id]['title'] = self::$export_instance->data[$id]['post_title'];
					if($value->meta_key == 'fifu_image_url'){
						self::$export_instance->data[$id]['fifu_image_url'] = $value->meta_value;
					}
					if($value->meta_key == 'fifu_image_alt'){
						self::$export_instance->data[$id]['fifu_image_alt'] = $value->meta_value;
					}			
				}
				if(is_plugin_active('seo-by-rank-math-pro/rank-math-pro.php')){
					if($value->meta_key == 'rank_math_schema_BlogPosting'){
						$rank_value=$value->meta_value;	
						$rank_math=unserialize($rank_value);
						if(!empty($rank_math)){
							$Selector=$rank_math['speakable']['cssSelector'];
							if(is_array($Selector)){
								$cssSelector=implode(',',$Selector);
							}
							$rank_math_image_type=$rank_math['image'];
							unset($rank_math_image_type['@type']);
							unset($rank_math_image_type['url']);
						}


						//image details

						$rank_math_image_group=array();
						$rank_math_image_property=array();
						$rank_math_image_value='';
						if(isset($rank_math_image_type) && is_array($rank_math_image_type)){
							foreach($rank_math_image_type as $key=>$rank_math_image){						
								if(is_array($rank_math_image)){
									$rank_math_image_group[]=$rank_math_image;							
								}
								else{
									$rank_math_image_property[$key]=$rank_math_image;							
								}
							}
							foreach($rank_math_image_property as $key=>$rank_math_image_property_values){
								$rank_math_image_value.=$key.':'.$rank_math_image_property_values.';';
							}
							$rank_math_image_values=rtrim('image->'.$rank_math_image_value,';');
						}



						//image group details
						$rank_math_image_gp_values='';
						foreach($rank_math_image_group as $rank_math_image_group_values){
							$rank_math_image_group_value='';
							foreach($rank_math_image_group_values as $key=>$rank_math_image_group_val){
								$rank_math_image_group_value.=$key.':'.$rank_math_image_group_val.';';
							}
							$rank_math_image_gp_value=rtrim($rank_math_image_group_value,';');
							$rank_math_image_gp_values.=$rank_math_image_gp_value.',';
						}					
						$rank_math_image_group_values=rtrim('image->'.$rank_math_image_gp_values,',');

						//author details
						if(isset($rank_math['author'])){
							$rank_math_author_type=$rank_math['author'];
							unset($rank_math_author_type['@type']);
							unset($rank_math_author_type['url']);
						}


						$rank_math_author_group=array();
						$rank_math_author_property=array();
						$rank_math_author_value='';
						if(isset($rank_math_author_type)&& is_array($rank_math_author_type)){
							foreach($rank_math_author_type as $key=>$rank_math_author){
								if(is_array($rank_math_author)){
									$rank_math_author_group[]=$rank_math_author;							
								}
								else{
									$rank_math_author_property[$key]=$rank_math_author;							
								}
							}
							foreach($rank_math_author_property as $key=>$rank_math_author_property_values){
								$rank_math_author_value.=$key.':'.$rank_math_author_property_values.';';
							}
							$rank_math_author_values=rtrim('author->'.$rank_math_author_value,';');
						}
						//author group details
						$rank_math_author_gp_values='';
						foreach($rank_math_author_group as $rank_math_author_group_values){
							$rank_math_author_group_value='';
							foreach($rank_math_author_group_values as $key=>$rank_math_author_group_val){
								$rank_math_author_group_value.=$key.':'.$rank_math_author_group_val.';';
							}
							$rank_math_author_gp_value=rtrim($rank_math_author_group_value,';');
							$rank_math_author_gp_values.=$rank_math_author_gp_value.',';
						}
						$rank_math_author_group_values=rtrim('author->'.$rank_math_author_gp_values,',');					

						//speakable details
						if(isset($rank_math['speakable'])){
							$rank_math_speakable_type=$rank_math['speakable'];
							unset($rank_math_speakable_type['@type']);
							unset($rank_math_speakable_type['cssSelector']);
							$rank_math_speakable_group=array();
							$rank_math_speakable_property=array();
							$rank_math_speakable_value='';
							foreach($rank_math_speakable_type as $key=>$rank_math_speakable){

								if(is_array($rank_math_speakable)){
									$rank_math_speakable_group[]=$rank_math_speakable;							
								}
								else{
									$rank_math_speakable_property[$key]=$rank_math_speakable;							
								}
							}
							foreach($rank_math_speakable_property as $key=>$rank_math_speakable_property_values){
								$rank_math_speakable_value.=$key.':'.$rank_math_speakable_property_values.';';
							}
							$rank_math_speakable_values=rtrim('speakable->'.$rank_math_speakable_value,';');

						}

						//speakable group details
						$rank_math_speakable_gp_values='';
						if(isset($rank_math_speakable_group) && is_array($rank_math_speakable_group)){
							foreach($rank_math_speakable_group as $rank_math_speakable_group_values){
								$rank_math_speakable_group_value='';
								foreach($rank_math_speakable_group_values as $key=>$rank_math_speakable_group_val){
									$rank_math_speakable_group_value.=$key.':'.$rank_math_speakable_group_val.';';
								}
								$rank_math_speakable_gp_value=rtrim($rank_math_speakable_group_value,';');
								$rank_math_speakable_gp_values.=$rank_math_speakable_gp_value.',';
							}					
							$rank_math_speakable_group_values=rtrim('speakable->'.$rank_math_speakable_gp_values,',');
						}
						//other details
						$rank_math_new_type=$rank_math;
						unset($rank_math_new_type['image']);
						unset($rank_math_new_type['author']);
						unset($rank_math_new_type['speakable']);
						unset($rank_math_new_type['headline']);
						unset($rank_math_new_type['description']);
						unset($rank_math_new_type['@type']);
						unset($rank_math_new_type['enableSpeakable']);
						unset($rank_math_new_type['dateModified']);
						unset($rank_math_new_type['datePublished']);
						unset($rank_math_new_type['metadata']);
						$rank_math_ne_group=array();
						$rank_math_new_property=array();
						$rank_math_new_value = '';
						if(isset($rank_math_new_type) && is_array($rank_math_new_type)){
							foreach($rank_math_new_type as $key=>$rank_math_new){

								if(is_array($rank_math_new)){
									$rank_math_new_group[]=$rank_math_new;	
								}
								else{
									$rank_math_new_property[$key]=$rank_math_new;								
								}
							}
							foreach($rank_math_new_property as $key=>$rank_math_new_property_values){
								$rank_math_new_value.=$key.':'.$rank_math_new_property_values.';';
							}
							$rank_math_new_values=rtrim('title->'.$rank_math_new_value,';');

						}

						//other group details
						$rank_math_new_gp_values='';
						if(isset($rank_math_new_group) && is_array($rank_math_new_group)){
							foreach($rank_math_new_group as $rank_math_new_group_values){
								$rank_math_new_group_value='';
								foreach($rank_math_new_group_values as $key=>$rank_math_new_group_val){
									$rank_math_new_group_value.=$key.':'.$rank_math_new_group_val.';';
								}
								$rank_math_new_gp_value=rtrim($rank_math_new_group_value,';');
								$rank_math_new_gp_values.=$rank_math_new_gp_value.',';
							}
							$rank_math_new_group_values=rtrim('title->'.$rank_math_new_gp_values,',');

						}

						if(isset($rank_math_image_values) || isset($rank_math_author_values) || isset($rank_math_speakable_values) || isset($rank_math_new_values)){
							$advanced_editor=$rank_math_image_values.'|'.$rank_math_author_values.'|'.$rank_math_speakable_values.'|'.$rank_math_new_values;
							$advanced_editor_group_values=$rank_math_image_group_values.'|'	.$rank_math_author_group_values.'|'.$rank_math_speakable_group_values.'|'.$rank_math_new_group_values;
						}

						$image_type=isset($rank_math['image']['@type'])?$rank_math['image']['@type']:'';
						$image_url=isset($rank_math['image']['url'])?$rank_math['image']['url']:'';
						$author_type=isset($rank_math['author']['@type'])?$rank_math['author']['@type']:'';
						$author_name=isset($rank_math['author']['name'])?$rank_math['author']['name']:'';
						$speakable_type=isset($rank_math['speakable']['@type'])?$rank_math['speakable']['@type']:'';
						$enable_speakable=isset($rank_math['enableSpeakable'])?$rank_math['enableSpeakable']:'';
						$date_modified=isset($rank_math['dateModified'])?$rank_math['dateModified']:'';
						$date_published=isset($rank_math['datePublished'])?$rank_math['datePublished']:'';


						self::$export_instance->data[$id]['cssSelector'] = isset($cssSelector)?$cssSelector:'';
						self::$export_instance->data[$id]['image_type'] = $image_type;
						self::$export_instance->data[$id]['image_url'] = $image_url;
						self::$export_instance->data[$id]['author_type'] = $author_type;
						self::$export_instance->data[$id]['author_name'] = $author_name;
						self::$export_instance->data[$id]['speakable_type'] = $speakable_type;
						self::$export_instance->data[$id]['enable_speakable'] = $enable_speakable;
						self::$export_instance->data[$id]['date_modified'] = $date_modified;
						self::$export_instance->data[$id]['date_published'] = $date_published;
						self::$export_instance->data[$id]['advanced_editor'] = isset($advanced_editor)?$advanced_editor:'';
						self::$export_instance->data[$id]['advanced_editor_group_values'] = isset($advanced_editor_group_values)?$advanced_editor_group_values:'';
					}
					if($value->meta_key == 'rank_math_advanced_robots'){
						$rank_robots_value=$value->meta_value;
						$rank_robots=unserialize($rank_robots_value);
						$max_snippet=$rank_robots['max-snippet'];
						$max_video_preview=$rank_robots['max-video-preview'];
						$max_image_preview=$rank_robots['max-image-preview'];
						$rank_math_advanced_robots=$max_snippet.','.$max_video_preview.','.$max_image_preview;
						self::$export_instance->data[$id]['rank_math_advanced_robots'] = $rank_math_advanced_robots;
					}
				}
				if(is_plugin_active('bbpress/bbpress.php')){

					if($optionalType =='topic'){
						if($value->meta_key =='_bbp_forum_id'){
							$forum_id = $value->meta_value;
							$forum_name =$wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts where ID=$value->meta_value");
							$value->meta_key='forum_name';
							$value->meta_value= $forum_name;

							$post_forum_id= get_post_meta($forum_id,'_bbp_sticky_topics',true);
							$option_forum_id = get_option('_bbp_super_sticky_topics');
							if(!empty($post_forum_id)){
								foreach($post_forum_id as $pf_id){
									if($pf_id == $id){
										$value->meta_key='type';
										$value->meta_value= 'Sticky';
									}
								}
							}
							elseif(!empty($option_forum_id)){
								foreach($option_forum_id as $of_id){
									if($of_id == $id){
										$value->meta_key='type';
										$value->meta_value= 'Super Sticky';
									}	
								}
							}
							else{
								$value->meta_key='type';
								$value->meta_value= 'Normal';
							}
						}

					}
					else if($optionalType == 'reply'){
						if($value->meta_key =='_bbp_forum_id'){
							$forum_name =$wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts where ID=$value->meta_value");
							$value->meta_key='forum_name';
							$value->meta_value= $forum_name;
						}
						else if($value->meta_key =='_bbp_topic_id'){
							$topic_name =$wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts where ID=$value->meta_value");
							$value->meta_key='topic_name';
							$value->meta_value= $topic_name;
						}
					}
				}
				if($value->meta_key == 'rank_math_robots'){
					$rank_robots_meta_value = $value->meta_value;
					$rank_robots_metas = unserialize($rank_robots_meta_value);
					foreach($rank_robots_metas as $robots_meta){
						self::$export_instance->data[$id][$robots_meta] = 1;
					}
				}

				if($value->meta_key == 'rank_math_schema_BlogPosting'){
					$rank_value=$value->meta_value;	
					$rank_math=unserialize($rank_value)	;
					$headline=isset($rank_math['headline'])?$rank_math['headline']:'';
					$schema_description=isset($rank_math['description'])?$rank_math['description']:'';
					$article_type= isset($rank_math['@type'])?$rank_math['@type']:'';
					$checktable = $wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}rank_math_redirections_cache'");
					if($checktable){
						$re_id =  $wpdb->get_results("SELECT redirection_id FROM {$wpdb->prefix}rank_math_redirections_cache where object_id='$id'");	
						$redirect_id=$re_id[0];
						$redirection_id=$redirect_id->redirection_id;
						$result =  $wpdb->get_results("SELECT url_to,header_code FROM {$wpdb->prefix}rank_math_redirections where id='$redirection_id'");	
						$rank_math_redirections=$result[0];
						$url_to=$rank_math_redirections->url_to;
						$header_code=$rank_math_redirections->header_code;
						self::$export_instance->data[$id]['destination_url'] = $url_to;
						self::$export_instance->data[$id]['redirection_type'] = $header_code;
					}
					self::$export_instance->data[$id]['headline'] = $headline;
					self::$export_instance->data[$id]['schema_description'] = $schema_description;
					self::$export_instance->data[$id]['article_type'] = $article_type;


				}

				if($value->meta_key == 'rank_math_schema_Dataset'){

					$schema_data = get_post_meta($id, 'rank_math_schema_Dataset',true);
					self::$export_instance->data[$id]['ds_name'] = $schema_data['name'];
					self::$export_instance->data[$id]['ds_description'] = $schema_data['description'];
					self::$export_instance->data[$id]['ds_url'] = $schema_data['url'];
					self::$export_instance->data[$id]['ds_sameAs'] = $schema_data['sameAs'];
					self::$export_instance->data[$id]['ds_license'] = $schema_data['license'];
					self::$export_instance->data[$id]['ds_temp_coverage'] = $schema_data['temporalCoverage'];
					self::$export_instance->data[$id]['ds_spatial_coverage'] = $schema_data['spatialCoverage'];
					$distribution = $schema_data['distribution'];
					$identifier = $schema_data['identifier'];
					$keywords = $schema_data['keywords'];
					if(is_array($distribution)){
						$encodeFormat = '';
						$contenUrl = '';
						foreach ($distribution as $disKey  => $disVal) {
							$encodeFormat.= $disVal['encodingFormat'].',';
							$contentUrl.= $disVal['contentUrl'].',';
							self::$export_instance->data[$id]['encodingFormat'] = rtrim($encodeFormat,',');
							self::$export_instance->data[$id]['contentUrl'] = rtrim($contentUrl,',');
						}

					}

					if(is_array($identifier)){
						$ident = '';
						foreach ($identifier as $identKey  => $identVal) {
							$ident.= $identVal.',';

							self::$export_instance->data[$id]['ds_identifier'] = rtrim($ident,',');

						}
					}

					if(is_array($keywords)){
						$keyword = '';
						foreach ($keywords as $kwKey  => $keyVal) {
							$keyword.= $keyVal.',';
							self::$export_instance->data[$id]['ds_keywords'] = rtrim($keyword,',');	
						}
					}

				} 				

				if($value->meta_key == 'rank_math_advanced_robots'){
					$rank_robots_value=$value->meta_value;
					$rank_robots=unserialize($rank_robots_value);
					$max_snippet=$rank_robots['max-snippet'];
					$max_video_preview=$rank_robots['max-video-preview'];
					$max_image_preview=$rank_robots['max-image-preview'];
					$rank_math_advanced_robots=$max_snippet.','.$max_video_preview.','.$max_image_preview;
					self::$export_instance->data[$id]['rank_math_advanced_robots'] = $rank_math_advanced_robots;
				}

				if(is_plugin_active('advanced-classifieds-and-directory-pro/acadp.php')) {
					$listingFields = array('price','views','views','zipcode','phone','email','website','images','video','latitude','longtitude','location');


					if(isset($value->meta_key) && in_array($value->meta_key,$listingFields)){

						if(is_serialized($value->meta_value)){
							$value->meta_value = unserialize($value->meta_value);
						}

						self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value ;

					}


				}			

				else{
					if(is_plugin_active('pods/init.php')){
						$this->get_pods_new_fields($id, $value,$optionalType,$module,self::$export_instance->allpodsfields);
					}
					$jet_fields = $jet_field_type = $jet_rep_fields = $jet_rep_types = '';
					$typesf=isset($typesf)?$typesf:'';
					$jet_types=isset($jet_types)?$jet_types:''; 
					$this->getCustomFieldValue($id, $value, $checkRep, $allacf, $typeOftypesField, $alltype, $jet_fields, $jet_types, $jet_rep_fields, $jet_rep_types,  $parent, $typesf, $group_unset , $optionalType , self::$export_instance->allpodsfields, $metabox_fields, $module, $metabox_relation_fields);

				}

			}
		}

		//jetengine latest version(2.11.4) support
		if(is_plugin_active('jet-engine/jet-engine.php')){
			$get_plugins_list = get_plugins();
			$get_jetengine_plugin_version = $get_plugins_list['jet-engine/jet-engine.php']['Version'];

			if($get_jetengine_plugin_version >= '2.11.4'){
				$get_rel_fields = $wpdb->get_results("SELECT id,labels, args, meta_fields FROM {$wpdb->prefix}jet_post_types WHERE status = 'relation' ", ARRAY_A);
				$get_cpt_fields = $wpdb->get_results("SELECT id,labels, args, meta_fields FROM {$wpdb->prefix}jet_post_types WHERE slug = '$optionalType' ", ARRAY_A);
				if(!empty($get_rel_fields)){
					foreach($get_rel_fields as $get_rel_values){
						$imported_type = !empty($optionalType) ? $optionalType : $module;
						$jet_relation_names = maybe_unserialize($get_rel_values['labels']);
						$jet_relation_name = maybe_unserialize($jet_relation_names['name']);
						$jet_relation_id = $get_rel_values['id'];

						$get_rel_fields_args = maybe_unserialize($get_rel_values['args']);
						$get_rel_parent_value = $get_rel_fields_args['parent_object'];
						$get_rel_child_value = $get_rel_fields_args['child_object'];
						$get_rel_db_table = $get_rel_fields_args['db_table'];
						$get_rel_parent1 = explode('::', $get_rel_parent_value);
						$get_rel_parent = $get_rel_parent1[1];
						$get_rel_parent_type = $get_rel_parent1[0];
						$get_rel_child1 = explode('::', $get_rel_child_value);
						$get_rel_child = $get_rel_child1[1];
						$get_rel_child_type = $get_rel_child1[0];

						if($imported_type == 'user'){
							$imported_type = 'users';
						}

						if($get_rel_db_table == 1){
							$jet_rel_table_name = $wpdb->prefix . 'jet_rel_' . $jet_relation_id;
							$jet_relmeta_table_name = $wpdb->prefix . 'jet_rel_' . $jet_relation_id . '_meta';
						}
						else{
							$jet_rel_table_name = $wpdb->prefix . 'jet_rel_default';
							$jet_relmeta_table_name = $wpdb->prefix . 'jet_rel_default_meta';
						}
						if($imported_type == $get_rel_parent || $imported_type == $get_rel_child){
							$get_rel_metafields = maybe_unserialize($get_rel_values['meta_fields']);							

							if($imported_type == $get_rel_parent){
								$get_jet_rel_object_connections =array();
								// $get_jet_rel_connections = $wpdb->get_results("SELECT child_object_id FROM {$wpdb->prefix}jet_rel_default as a join {$wpdb->prefix}posts as b on a.parent_object_id = b.ID WHERE a.rel_id = $jet_relation_id AND a.parent_object_id = $id AND b.post_status = 'publish' ", ARRAY_A);
								$get_jet_rel_connections = $wpdb->get_results("SELECT child_object_id FROM $jet_rel_table_name WHERE parent_object_id = $id  and rel_id = $jet_relation_id", ARRAY_A);
								$get_jet_rel_object_connections = array_column($get_jet_rel_connections, 'child_object_id');

								if(!empty($get_jet_rel_object_connections) && !empty($get_rel_metafields)){
									$this->get_jetengine_relation_meta_fields($jet_relation_id, $id, $get_rel_metafields, $get_jet_rel_object_connections, 'parent', $jet_relmeta_table_name);
								}
							}
							elseif($imported_type == $get_rel_child){
								$get_jet_rel_object_connections =array();
								// $get_jet_rel_connections = $wpdb->get_results("SELECT  parent_object_id FROM {$wpdb->prefix}jet_rel_default as a join {$wpdb->prefix}posts as b on a.child_object_id = b.ID WHERE a.rel_id = $jet_relation_id AND a.child_object_id = $id AND b.post_status = 'publish' ", ARRAY_A);
								$get_jet_rel_connections = $wpdb->get_results("SELECT  parent_object_id FROM $jet_rel_table_name WHERE  child_object_id = $id and rel_id = $jet_relation_id", ARRAY_A);
								$get_jet_rel_object_connections = array_column($get_jet_rel_connections, 'parent_object_id');

								if(!empty($get_jet_rel_object_connections) && !empty($get_rel_metafields)){
									$this->get_jetengine_relation_meta_fields($jet_relation_id, $id, $get_rel_metafields, $get_jet_rel_object_connections, 'child', $jet_relmeta_table_name);
								}
							}
							$get_rel_object_value = '';
							if(!empty($get_jet_rel_object_connections)){
								if($imported_type == $get_rel_parent){

									if($get_rel_child == 'users'){
										$users = $wpdb->prefix.'users';
										$get_jet_rel_object_connections = implode(",", $get_jet_rel_object_connections);
										$get_jet_rel_connections = $wpdb->get_col("SELECT user_login FROM $users WHERE ID IN ($get_jet_rel_object_connections)");
										$get_rel_object_value = implode('|', $get_jet_rel_connections);		
									}
									elseif($get_rel_parent_type== 'terms' && $get_rel_child_type == 'terms'){
										$stored_objects = [];
										foreach($get_jet_rel_object_connections as $my_jet_rel_objects){
											$stored_objects[] = $wpdb->get_col("SELECT wp_terms.name FROM {$wpdb->prefix}terms AS wp_terms INNER JOIN {$wpdb->prefix}jet_rel_default AS wp_jet_rel_default ON wp_terms.term_id = wp_jet_rel_default.child_object_id WHERE wp_jet_rel_default.child_object_id = $my_jet_rel_objects");
										}
										$stored_objects_results = []; 
										foreach($stored_objects as $inner_array_values){
											$stored_objects_results[] = implode('|' , $inner_array_values);
										}
										$get_rel_object_value = implode('|', $stored_objects_results);
									}
									elseif(($get_rel_parent_type== 'cct' && $get_rel_child_type == 'cct') || ($get_rel_parent_type== 'cct' && $get_rel_child_type == 'posts') || ($get_rel_parent_type== 'mix' && $get_rel_child_type == 'cct')){
										$custom_post_type = 'publish';
										$cct_table = $wpdb->prefix . 'jet_cct_'.$get_rel_child;
										$get_jet_rel_object_connections = implode("|", $get_jet_rel_object_connections);
										$get_rel_object_value = $get_jet_rel_object_connections;
									}
									else{
										$posts = $wpdb->prefix . 'posts';
										$get_jet_rel_object_connections = implode(",", $get_jet_rel_object_connections);
										$get_jet_rel_connections = $wpdb->get_col("SELECT post_title FROM $posts WHERE ID IN ($get_jet_rel_object_connections)");
										$get_rel_object_value = implode('|', $get_jet_rel_connections);		
									}
								}
								else if($imported_type == $get_rel_child){
									if($get_rel_parent == 'users'){
										$users = $wpdb->prefix.'users';
										$get_jet_rel_object_connections = implode(",", $get_jet_rel_object_connections);
										$get_jet_rel_connections = $wpdb->get_col("SELECT user_login FROM $users WHERE ID IN ($get_jet_rel_object_connections)");
										$get_rel_object_value = implode('|', $get_jet_rel_connections);		
									}
									elseif($get_rel_parent_type== 'terms' && $get_rel_child_type == 'terms'){
										$stored_objects = [];
										foreach($get_jet_rel_object_connections as $my_jet_rel_objects){
											$stored_objects[] = $wpdb->get_col("SELECT wp_terms.name FROM {$wpdb->prefix}terms AS wp_terms INNER JOIN {$wpdb->prefix}jet_rel_default AS wp_jet_rel_default ON wp_terms.term_id = wp_jet_rel_default.parent_object_id WHERE wp_jet_rel_default.parent_object_id = $my_jet_rel_objects");
										}
										$stored_objects_results = []; 
										foreach($stored_objects as $inner_array_values){
											$stored_objects_results[] = implode('|' , $inner_array_values);
										}
										$get_rel_object_value = implode('|', $stored_objects_results);
									}
									elseif(($get_rel_parent_type== 'cct' && $get_rel_child_type == 'cct') || ($get_rel_parent_type== 'cct' && $get_rel_child_type == 'posts') || ($get_rel_parent_type== 'mix' && $get_rel_child_type == 'cct')){
										$custom_post_type = 'publish';
										$cct_table = $wpdb->prefix . 'jet_cct_'.$get_rel_parent;
										$get_jet_rel_object_connections = implode("|", $get_jet_rel_object_connections);
										$get_rel_object_value = $get_jet_rel_object_connections;
									}
									else{
										$posts = $wpdb->prefix . 'posts';
										$get_jet_rel_object_connections = implode(",", $get_jet_rel_object_connections);
										$get_jet_rel_connections = $wpdb->get_col("SELECT post_title FROM $posts WHERE ID IN ($get_jet_rel_object_connections)");
										$get_rel_object_value = implode('|', $get_jet_rel_connections);		
									}
								}
								// $posts = $wpdb->prefix . 'posts';
								// $get_jet_rel_object_connections = implode(",", $get_jet_rel_object_connections);
								// $get_jet_rel_connections = $wpdb->get_col("SELECT post_title FROM $posts WHERE ID IN ($get_jet_rel_object_connections)");
								// $get_rel_object_value = implode('|', $get_jet_rel_connections);
							}
							self::$export_instance->data[$id][ 'jet_related_post :: ' . $jet_relation_id ] = $get_rel_object_value;
						}
					}
				}
				else if(!empty($get_cpt_fields[0])){
					$get_cpt_fields_args = maybe_unserialize($get_cpt_fields[0]['args']);
					$check_custom_table = $get_cpt_fields_args['custom_storage'];
					if($check_custom_table && isset($check_custom_table)){
						$table_name = $wpdb->prefix . $optionalType . '_meta';
						// Check if the table exists
						if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
							// Call the function if the table exists
							PostExport::$jet_custom_table_export->get_custom_table_meta_fields($module,$id, $table_name, $optionalType, $jet_cpt_fields_name,$jet_types, $jet_rep_fields, $jet_rep_types);
						}
					}
				}
			}
		}

		return self::$export_instance->data;
	}

	public function getAllPodsFields(){		
		$pods_fields = [];
		if(is_plugin_active('pods/init.php')){
			global $wpdb;
			$pods_fields_query_result = $wpdb->get_results("SELECT post_name FROM ".$wpdb->prefix."posts WHERE post_type = '_pods_field'");	
			foreach($pods_fields_query_result as $single_result){
				$pods_fields[] = $single_result->post_name;	
			}
		}
		return $pods_fields;
	}

	public function getCustomFieldValue($id, $value, $checkRep, $allacf, $typeOftypesField, $alltype, $jet_fields, $jet_types, $jet_rep_fields, $jet_rep_types, $parent, $typesf, $group_unset , $optionalType , $pods_type, $metabox_fields, $module, $metabox_relation_fields){										
		global $wpdb; 
		$taxonomies = get_taxonomies();
		$down_file = false;	

		if (isset($value->meta_key) && $value->meta_key == '_thumbnail_id') {
			$attachment_file = null;
			$thumbnail_id = $value->meta_value;
			$get_attachment = $wpdb->prepare("select guid from {$wpdb->prefix}posts where ID = %d AND post_type = %s", $value->meta_value, 'attachment');
			//$attachment_file = $wpdb->get_var($get_attachment);
			$attachment_file =wp_get_attachment_url( $value->meta_value );
			self::$export_instance->data[$id][$value->meta_key] = '';
			$value->meta_key = 'featured_image';
			self::$export_instance->data[$id][$value->meta_key] = $attachment_file;
			if(isset($attachment_file)){
				$attachment = get_post($thumbnail_id);
				$image_meta = wp_get_attachment_metadata($thumbnail_id);
				$title = get_the_title($thumbnail_id);
				$alt_text = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
				$description = $attachment->post_content;
				$caption = $attachment->post_excerpt;
				$file_name = isset($image_meta['file']) ? basename($image_meta['file']) : '';

				self::$export_instance->data[$id]['featured_image_title'] = isset($title)? $title : '' ;
				self::$export_instance->data[$id]['featured_image_alt_text'] = isset($alt_text)? $alt_text : '' ;
				self::$export_instance->data[$id]['featured_image_caption'] = isset($caption)? $caption : '' ;	
				self::$export_instance->data[$id]['featured_image_description'] = isset($description)? $description : '' ;
				self::$export_instance->data[$id]['featured_file_name'] = isset($file_name)? $file_name : '' ;
			}

		}
		else if (is_plugin_active('jet-reviews/jet-reviews.php') &&isset($value->meta_key) && 
			($value->meta_key == 'jet-review-title' || 
			$value->meta_key == 'jet-review-summary-title' || 
			$value->meta_key == 'jet-review-summary-text' || 
			$value->meta_key == 'jet-review-summary-legend' || 
			$value->meta_key == 'jet-review-data-name' || 
			$value->meta_key == 'jet-review-data-image' || 
			$value->meta_key == 'jet-review-data-desc' || 
			$value->meta_key == 'jet-review-data-author-name' ||
			$value->meta_key == 'jet-review-items')) {
			if(isset($value->meta_key) && $value->meta_key == 'jet-review-items'){
				if (!empty($value->meta_value)) {
					$unserialized_data = unserialize($value->meta_value);
					$output = [];
					foreach ($unserialized_data as $item) {
						$formatted_values = [];
						foreach ($item as $field => $name) {
							$formatted_values[] = $name;
						}
						$output[] = implode('|', $formatted_values);
					}
					$final_output = implode(',', $output);
					self::$export_instance->data[$id][$value->meta_key] = $final_output;
				}
			}else{
				self::$export_instance->data[$id][$value->meta_key] = $value->meta_value;
			}
		}

		else if(is_plugin_active('jet-booking/jet-booking.php')&& isset($value->meta_key) && ($value->meta_key == 'jet_abaf_price' || $value->meta_key == 'jet_abaf_configuration' || $value->meta_key == 'jet_abaf_custom_schedule')) 
		{

			self::$export_instance->data[$id][$value->meta_key] = $value->meta_value;
		}
		else if(isset($value->meta_key) && $value->meta_key == '_downloadable_files'){ 
			$downfiles = unserialize($value->meta_value); 
			if(!empty($downfiles) && is_array($downfiles)){
				foreach($downfiles as $dk => $dv){
					$down_file .= $dv['name'].','.$dv['file'].'|';
				}
			}	
			self::$export_instance->data[$id]['downloadable_files'] = rtrim($down_file,"|");
		}
		elseif(isset($value->meta_key) &&$value->meta_key == '_upsell_ids'){
			$upselldata = unserialize($value->meta_value);
			if(!empty($upselldata) && is_array($upselldata)){
				foreach($upselldata as $upselldata_value){
					$upselldata_query = $wpdb->prepare("SELECT post_title FROM {$wpdb->prefix}posts where id = %d", $upselldata_value);
					$upselldata_value=$wpdb->get_results($upselldata_query);
					if(isset($upselldata_value[0]->post_title)){
						$upselldata_item[] = $upselldata_value[0]->post_title;
					}	
				}
				if(!empty($upselldata_item)){
					$upsellids = implode(',',$upselldata_item);
				}
				else{
					$upsellids = '';
				}

			}
			else{
				$upsellids = $upselldata;
			}
			self::$export_instance->data[$id]['upsell_ids'] =  $upsellids;
		}
		elseif(isset($value->meta_key) && $value->meta_key == '_crosssell_ids'){
			$cross_selldata = unserialize($value->meta_value);
			if(!empty($cross_selldata) && is_array($cross_selldata)){
				foreach($cross_selldata as $cross_selldata_value){
					$cross_selldata_query = $wpdb->prepare("SELECT post_title FROM {$wpdb->prefix}posts where id = %d", $cross_selldata_value);
					$cross_selldata_value=$wpdb->get_results($cross_selldata_query);
					if(isset($cross_selldata_value[0]->post_title)){
						$cross_selldata_item[] = $cross_selldata_value[0]->post_title;
					}	
				}
				if(!empty($cross_selldata_item)){
					$cross_sellids = implode(',',$cross_selldata_item);
				}
				else{
					$cross_sellids = '';
				}	
			}
			else{
				$cross_sellids = $cross_selldata;
			}
			self::$export_instance->data[$id]['crosssell_ids'] =  $cross_sellids;
		}
		elseif(isset($value->meta_key) && $value->meta_key == '_children'){
			$grpdata = unserialize($value->meta_value);
			$grpids = !empty($grpdata) ? implode(',',$grpdata) : '';
			self::$export_instance->data[$id]['grouping_product'] =  $grpids;			
		}elseif(isset($value->meta_key) && $value->meta_key == '_product_image_gallery'){
			if(strpos($value->meta_value, ',') !== false) {
				$file_data = explode(',',$value->meta_value);
				foreach($file_data as $k => $v){

					$ids=$v;
					$types_caption=$wpdb->get_results("select post_excerpt from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
					$types_description=$wpdb->get_results("select post_content from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
					$types_title=$wpdb->get_results("select post_title from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
					$types_alt_text=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attachment_image_alt' AND post_id='$ids'" ,ARRAY_A);
					$types_filename=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attached_file' AND post_id='$ids'" ,ARRAY_A);
					$types_filename[0]['meta_value']=isset($types_filename[0]['meta_value'])?$types_filename[0]['meta_value']:'';
					$filename=$types_filename[0]['meta_value'];
					$file_names=explode('/', $filename);
					$file_names[2]=isset($file_names[2])?$file_names[2]:'';
					$file_name= $file_names[2];
					self::$export_instance->data[$id]['product_caption'] = $types_caption;
					self::$export_instance->data[$id]['product_description'] = $types_description;
					self::$export_instance->data[$id]['product_title'] = $types_title;
					self::$export_instance->data[$id]['product_alt_text'] = $types_alt_text;
					self::$export_instance->data[$id]['product_file_name'] = $file_name;
					$attachment = wp_get_attachment_image_src($v);
					$attachment = is_array($attachment) ? $attachment : array(); 
					$attachment[0] = isset($attachment[0]) ? $attachment[0] : '';
					$attach[$k] = $attachment[0];
				}
				if(isset($attach)){
					$gallery_data = '';
					foreach($attach as $values){
						$getid=$wpdb->get_results("select ID from {$wpdb->prefix}posts where guid= '$values'" ,ARRAY_A);
						global $wpdb;
						$gallery_data .= $values.'|';
					}
				}
				$gallery_data=isset($gallery_data)?$gallery_data:'';
				$gallery_data = rtrim($gallery_data , '|');
				self::$export_instance->data[$id]['product_image_gallery'] = $gallery_data;
			}else{
				$attachment = wp_get_attachment_image_src($value->meta_value);
				self::$export_instance->data[$id]['product_image_gallery'] = $attachment[0];
			}
		}elseif(isset($value->meta_key) && $value->meta_key == '_sale_price_dates_from'){
			if(!empty($value->meta_value)){
				if(strpos($value->meta_value, '-') !== FALSE){
					self::$export_instance->data[$id]['sale_price_dates_from'] = $value->meta_value;
				}else{
					self::$export_instance->data[$id]['sale_price_dates_from'] = date('Y-m-d',$value->meta_value);
				}
			}
		}
		elseif(isset($value->meta_key) && $value->meta_key == '_sale_price_dates_to'){
			if(!empty($value->meta_value)){
				if(strpos($value->meta_value, '-') !== FALSE){
					self::$export_instance->data[$id]['sale_price_dates_to'] = $value->meta_value;
				}else{
					self::$export_instance->data[$id]['sale_price_dates_to'] = date('Y-m-d',$value->meta_value);
				}
			}
		}
		// elseif(strpos($value->meta_key,'relation_') !== false){
		elseif(isset($value->meta_key) && strpos($value->meta_key,'jet_relation_') !== false){
			$relatedposttitle = '';
			$metaQuery = $wpdb->get_results("SELECT meta_key FROM {$wpdb->prefix}postmeta WHERE post_id = $id AND meta_key LIKE 'relation_%'", ARRAY_A);
			$relationkeys = array_column($metaQuery, 'meta_key');
			$arraykey = array_unique($relationkeys);

			$jetrelkey =implode('|',$arraykey);

			foreach($arraykey as $arraycomkey => $arraycomval){

				$metval = "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id='{$id}' AND meta_key ='$arraycomval'  ";
				$get_val = $wpdb->get_results($metval);
				$metavalues = array();
				foreach($get_val as $getvals){
					$metavalues []= $getvals->meta_value;

				}
				$countpage = count($metavalues);
				$related_title = [];

				foreach($metavalues as $metavals){
					if($countpage>1){
						$metquery = "SELECT post_title FROM {$wpdb->prefix}posts WHERE id ='{$metavals}'  ";
						$get_title = $wpdb->get_results($metquery);
						$related_title[]=$get_title[0]->post_title;

						$related_titles = !empty($related_title) ? implode(',',$related_title)	: '';
					}
					else{
						$metquery = "SELECT post_title FROM {$wpdb->prefix}posts WHERE id ='{$metavals}'  ";
						$get_title = $wpdb->get_results($metquery);

						if(!empty($get_title)){
							$related_titles = $get_title[0]->post_title;
						}
					}
				}

				if(isset($related_titles)){
					$relatedposttitle .= $related_titles.'|';
				}
				else{
					$relatedposttitle = '';
				}		    
			}
			$relatedposttitle = isset($relatedposttitle) ? $relatedposttitle : '';
			self::$export_instance->data[$id]['jet_related_post'] = rtrim($relatedposttitle, '|');	
			self::$export_instance->data[$id]['jet_relation_metakey'] = $jetrelkey;

		}

		elseif(isset($value->meta_key) && $value->meta_key == '_lp_faqs'){
			$faqs=$value->meta_value;
			$unserialize_faq_value=unserialize($faqs);
			$faqs_value = '';
			foreach($unserialize_faq_value as $faq_key=>$faq_value){
				$faqs_value .= $faq_value[0].','.$faq_value[1].'|';
			}
			self::$export_instance->data[$id][ $value->meta_key ] = isset($faqs_value) ? rtrim($faqs_value,'|') : "";
		}

		else { 			
			if(isset($allacf) && is_array($allacf) && isset($value->meta_key) && !empty($alltype[$value->meta_key])){        
				$repeaterOfrepeater = false;
				$alltype[$value->meta_key]=isset($alltype[$value->meta_key])?$alltype[$value->meta_key]:'';

				$getType = $alltype[$value->meta_key];
				if(empty($getType)){
					$temp_fieldname = array_search($value->meta_key, $allacf);
					$getType = $alltype[$temp_fieldname];
				}					
				if($getType == 'taxonomy'){
					$terms = [];
					if(is_serialized($value->meta_value)){
						$value->meta_value = unserialize($value->meta_value);
						foreach($value->meta_value as $meta){
							$termname = $wpdb->get_row($wpdb->prepare("select name from {$wpdb->prefix}terms where term_id= %d",$meta));							
							$terms[]= !empty($termname) ? $termname->name : "";	
						}
						$value->meta_value = !empty($terms) ? implode(',',$terms ) : "";	
						self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;

					}
				}
				if($getType == 'table'){
					$tab_key=$value->meta_key;
					$tab_id=$value->post_id;
					$tab_value = $wpdb->get_results($wpdb->prepare("SELECT meta_value from {$wpdb->prefix}postmeta where meta_key = %s" , $tab_key ), ARRAY_A);
					$acf_table_value='';
					foreach($tab_value as  $value_type){

						$get_type_field = $value_type['meta_value'];	
						$table_values=unserialize($get_type_field);
						$table_title=$table_values['p']['o']['uh'];
						$header = $table_values['h'];
						$header_arr = array_column($table_values['h'], 'c');

						$table_value_arr = [];
						foreach($table_values['b'] as $table_field_values){

							foreach($table_field_values as $table_keys => $table_values){

								$table_value_arr[$table_keys][] = $table_values['c'];
							}
						}
						$temp = 0;
						$body_value = '';
						foreach($header_arr as $header_key => $header_arr_val){
							$header_arr_values = implode('|', $table_value_arr[$header_key]);
							$body_value .= $header_arr_val . '->' . $header_arr_values . '--';
						}

						$table_body_value = rtrim($body_value, '--');
						$acf_table_value.=$table_title.','.$table_body_value;
						$value->meta_value=	$acf_table_value;
						self::$export_instance->data[$id][$value->meta_key] = $value->meta_value;

					}	


				}
				if($getType =='user'){
					if(is_serialized($value->meta_value)){
						$meta_value = unserialize($value->meta_value);

						foreach($meta_value as $val){
							$user = $wpdb->get_row($wpdb->prepare("select user_login from {$wpdb->prefix}users where ID= %d",$val));
							$username[]=$user->user_login;
						}
						if(is_array($username)){
							$value->meta_value = !empty($username) ? implode(',',$username) : '';	
						}

						self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
					}
				}
				if($getType =='relationship'){		
					$relation = [];
					if(is_serialized($value->meta_value)){
						$rel_value = unserialize($value->meta_value);
						foreach($rel_value as $rel){
							$relname = $wpdb->get_row($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID= %d",$rel));
							$relation[]=$relname->post_title;
						}
						if(!empty($relation))
							$value->meta_value = implode(',',$relation);	
						else
							$value->meta_value = "";
						self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
					}				
				}
				if($getType == 'group'){
					global $wpdb;
					$repkey=$value->meta_key;
					$queid=$wpdb->get_results($wpdb->prepare("SELECT ID FROM {$wpdb->prefix}posts WHERE post_excerpt=%s", $repkey));

					if (!is_null($queid) && !empty($queid)) {
						$grpid = isset($queid[0]->ID) ? $queid[0]->ID : '';
						$quechild=$wpdb->get_results($wpdb->prepare("SELECT post_excerpt FROM {$wpdb->prefix}posts WHERE ID=%d", $grpid));
						$repchildkey=$quechild[0]->post_excerpt;
						$que=$wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}posts wp JOIN {$wpdb->prefix}postmeta wpm ON wpm.post_id = wp.ID where meta_key = %s AND ID=%d", $repchildkey, $id));
						if (isset($que[0])) {
							$que[0]->meta_value = isset($que[0]) ? $que[0]->meta_value : '';
						}
						$queval = $que[0]->meta_value;
						$quechild=$wpdb->get_results($wpdb->prepare("SELECT post_excerpt FROM {$wpdb->prefix}posts WHERE post_parent=%d", $grpid));
						$repchildkey=$quechild[0]->post_excerpt;
						if(empty($queval))
							$value->meta_value=1;

						self::$export_instance->data[$id][$value->meta_key] = self::$export_instance->returnMetaValueAsCustomerInput($queval);
						if(is_serialized($value->meta_value)){
							$value->meta_value = unserialize($value->meta_value);
							$count = count($value->meta_value);
						}else{
							$count = $value->meta_value;
						}
						$value->meta_key=$repkey;
						$getRF = $checkRep[$value->meta_key];
						if(is_array($getRF)){
							foreach ($getRF as $rep => $rep1) {

								$repType = isset($alltype[$rep1]) ? $alltype[$rep1] : '';
								$reval = "";
								for($z=0;$z<$count;$z++){
									$var = $value->meta_key.'_'.$rep1;
									if(in_array($optionalType , $taxonomies)){
										$qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}terms wp JOIN {$wpdb->prefix}termmeta wpm ON wpm.term_id = wp.term_id where meta_key = %s AND wp.term_id = %d", $var, $id));
									}
									elseif($optionalType == 'users'){
										$qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}users wp JOIN {$wpdb->prefix}usermeta wpm ON wpm.user_id = wp.ID where meta_key = %s AND ID = %d", $var, $id));
									}
									else{
										$qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}posts wp JOIN {$wpdb->prefix}postmeta wpm ON wpm.post_id = wp.ID where meta_key = %s AND ID=%d", $var, $id));
									}
									$meta = isset($qry[0]->meta_value) ? $qry[0]->meta_value :'';
									if(isset($meta) && is_numeric($meta) && $repType != 'image' && $repType != 'file' && $repType !='number' && $repType != 'range' && $repType != 'text' && $repType != 'repeater'){
										$meta_title = $wpdb->get_col($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$meta));
										foreach($meta_title as $meta_tit){
											$meta=$meta_tit;	
										}	
									}
									if($repType == 'group'){
										$groupOfgroup = true;
										$grp_grp_fields = $this->getgroupofgroup($rep1);
										foreach($grp_grp_fields as $grpkey => $grpval){
											$group_type = $alltype[$grpval];
											$var_grp = $value->meta_key.'_'.$rep1.'_'.$grpval;
											if(in_array($optionalType , $taxonomies)){

												$grp_qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}terms wp JOIN {$wpdb->prefix}termmeta wpm ON wpm.term_id = wp.term_id where meta_key = %s AND wp.term_id = %d", $var_grp, $id));
											}else{
												$grp_qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}posts wp JOIN {$wpdb->prefix}postmeta wpm ON wpm.post_id = wp.ID where meta_key = %s AND ID=%d", $var_grp, $id));
											}
											$grp_meta = $grp_qry[0]->meta_value;
											if($group_type == 'image')
												$grp_meta = $this->getAttachment($grp_meta);
											if($group_type == 'file')
												$grp_meta =$this->getAttachment($grp_meta);
											if(is_serialized($grp_meta))
											{	
												$unmeta = unserialize($grp_meta);
												$coun =count($unmeta);
												$grp_meta = "";
												$grpmeta = '';
												$grp_gal_val = '';
												$grp_val = '';
												foreach ($unmeta as $unmetakey => $unmeta1) {

													if($group_type == 'image'){
														$grp_val .= $this->getAttachment($unmeta1).',';
													}elseif( $group_type == 'gallery'){	
														$grp_gal_val .= $this->getAttachment($unmeta1).',';
													}
													elseif($group_type == 'relationship'  || $group_type == 'post_object'){
														if(is_numeric($unmeta1)){
															$meta_title = $wpdb->get_col($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$unmeta1));
															foreach($meta_title as $meta_tit){
																$meta .=$meta_tit.',';

															}
															$grp_val = $grp_val = rtrim($meta , ',');	
														}
														else{
															$grpmeta .=$unmeta1.',';
															$grp_val = rtrim($grpmeta , ',');
														}

													}
													else{
														$grp_val = $unmeta1;
													}

												}											
												if($group_type == 'gallery'){
													$grp_val .= rtrim($grp_gal_val , ',') ;
												}
											}elseif($grp_meta != ''){
												$grp_val = $grp_meta ;	
											}
											//$grp_data[$grpval][] = rtrim($grp_val,'|');
											self::$export_instance->data[$id][ $grpval ] = isset($grp_val) ? $grp_val : '' ;

										}

									}
									if($repType == 'image'){
										$meta=isset($meta)?$meta:'';
										$meta = $this->getAttachment($meta);
									}
									if($repType == 'file'){
										$meta=isset($meta)?$meta:'';
										$meta =$this->getAttachment($meta);
									}
									if($repType == 'repeater'){
										$repeaterOfrepeater = true;
										$rep_rep_fields = $this->getRepeaterofRepeater($rep1);
										if(is_array($rep_rep_fields )){
											foreach($rep_rep_fields as $repeat => $repeat1){
												$repeat_type = $alltype[$repeat1];

												$repeater_count = get_post_meta($id , $var , true);
												$repeat_val = "";
												for($r = 0; $r<$repeater_count; $r++){
													$var_rep = $value->meta_key.'_'.$rep1.'_'.$r.'_'.$repeat1;
													if(in_array($optionalType , $taxonomies)){

														$rep_qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}terms wp JOIN {$wpdb->prefix}termmeta wpm ON wpm.term_id = wp.term_id where meta_key = %s AND wp.term_id = %d", $var_rep, $id));
													}else{
														$rep_qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}posts wp JOIN {$wpdb->prefix}postmeta wpm ON wpm.post_id = wp.ID where meta_key = %s AND ID=%d", $var_rep, $id));
													}
													$rep_meta = $rep_qry[0]->meta_value;
													if($repeat_type == 'image')
														$rep_meta = $this->getAttachment($rep_meta);
													if($repeat_type == 'file')
														$rep_meta =$this->getAttachment($rep_meta);
													if(is_serialized($rep_meta))
													{	
														$unmeta = unserialize($rep_meta);
														$coun =count($unmeta);
														$rep_meta = "";
														$repeat_gal_val = '';
														foreach ($unmeta as $unmetakey => $unmeta1) {
															if($coun > 1){
																if($unmetakey == 0){
																	if($repeat_type == 'image'){
																		$repeat_val .= $this->getAttachment($unmeta1).',';
																	}elseif( $repeat_type == 'gallery'){	
																		$repeat_gal_val .= $this->getAttachment($unmeta1).',';
																	}
																	else{
																		$repeat_val .= $unmeta1.',';
																	}
																}
																else{
																	if($repeat_type == 'image'){
																		$repeat_val .= $this->getAttachment($unmeta1).',';
																	}elseif( $repeat_type == 'gallery'){	
																		$repeat_gal_val .= $this->getAttachment($unmeta1).',';
																	}
																	else{
																		$repeat_val .= $unmeta1.'|';
																	}
																}
															}
															else{
																if($repeat_type == 'image'){
																	$repeat_val .= $this->getAttachment($unmeta1).',';
																}elseif( $repeat_type == 'gallery'){	
																	$repeat_gal_val .= $this->getAttachment($unmeta1).',';
																}
																else{
																	$repeat_val .= $unmeta1.'|';
																}
															}


														}											
														if($repeat_type == 'gallery'){
															$repeat_val .= rtrim($repeat_gal_val , ',') . '|';
														}
													}elseif($rep_meta){
														$repeat_val .= $rep_meta . '|';	
													}	
												}
												$repeater_data[$repeat1][] = rtrim($repeat_val,'|');
											}
											//self::$export_instance->data[$id][$repeat1] = self::$export_instance->returnMetaValueAsCustomerInput(rtrim($reval,'|'));
										}

										if($meta != ""){
											if(isset($repeat_val)){
												$reval .= $repeat_val.'|';
											}
										}
									}
									if(is_serialized($meta))
									{
										$unmeta = unserialize($meta);

										$meta = "";
										foreach ($unmeta as $unmeta1) {
											if($repType == 'image' || $repType == 'gallery')
												$meta .= $this->getAttachment($unmeta1).',';
											elseif($repType == 'taxonomy') {
												// $meta .=$unmeta1.',';
												if(is_numeric($unmeta1)){
													$meta_title = $wpdb->get_var("select name from {$wpdb->prefix}terms where term_id =$unmeta1");	
													$meta .=$meta_title.',';
												}
											}
											elseif($repType == 'user') {
												$meta .=$unmeta1.',';
											}
											elseif($repType == 'post_object') {
												if(is_numeric($unmeta1)){
													$meta_title = $wpdb->get_col($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$unmeta1));
													foreach($meta_title as $meta_tit){
														$meta .=$meta_tit.',';

													}	
												}	
											}
											elseif($repType == 'relationship') {
												$meta .=$unmeta1.',';
											}
											elseif($repType == 'page_link') {
												$meta .=$unmeta1.',';
											}
											elseif($repType == 'link') {
												$meta .=$unmeta1 . ',';
											}
											else
												$meta .= $unmeta1.",";
										}

										if($repType == 'image' || $repType == 'gallery'){
											$meta = rtrim($meta,',');
										}else{
											$meta = rtrim($meta,',');
										}

									}
									if($meta != "")
										$reval .= $meta."|";
								}
								if($repeaterOfrepeater){
									if(!empty($repeater_data)){
										foreach($repeater_data as $repeater_key => $repeater_value){
											$repeaterOfvalue = '';
											foreach($repeater_value as $rep_rep_value){
												$repeaterOfvalue .= $rep_rep_value . '|';
											}
											self::$export_instance->data[$id][$repeater_key] = self::$export_instance->returnMetaValueAsCustomerInput(rtrim($repeaterOfvalue,'|'));
										}
									}
								}
								self::$export_instance->data[$id][$rep1] = self::$export_instance->returnMetaValueAsCustomerInput(rtrim($reval,'|'));
							}
							//self::$export_instance->data[$id][$rep1] = self::$export_instance->returnMetaValueAsCustomerInput(rtrim($reval,'|'));

						}
					}
				}
				if ($getType == 'flexible_content' || $getType == 'repeater') { 
					global $wpdb;
					$repkey=$value->meta_key;
					$que=$wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}posts wp JOIN {$wpdb->prefix}postmeta wpm ON wpm.post_id = wp.ID where meta_key = %s AND ID=%d", $repkey, $id));
					$queval = isset($que[0]->meta_value) ? $que[0]->meta_value : '';
					self::$export_instance->data[$id][$value->meta_key] = self::$export_instance->returnMetaValueAsCustomerInput($queval);
					if(is_serialized($value->meta_value)){
						$value->meta_value = unserialize($value->meta_value);
						$count = count($value->meta_value);
					}else{
						$count = $value->meta_value;
					}
					// $checkRep[$value->meta_key] = isset($checkRep[$value->meta_key])? $checkRep[$value->meta_key] : '';
					$getRF = $checkRep[$value->meta_key];
					$repeater_data = [];
					if($getType == 'flexible_content'){

						$flexible_value = '';
						if(is_array($value->meta_value)){
							foreach($value->meta_value as $values){
								$flexible_value .= $values.',';
							}
						}
						$flexible_value = rtrim($flexible_value , ',');	
						self::$export_instance->data[$id][$value->meta_key] = self::$export_instance->returnMetaValueAsCustomerInput($flexible_value);
					}
					// if(is_array($getRF)){
					foreach ($getRF as $rep => $rep1) {
						$repType = $alltype[$rep1];
						$reval = "";
						for($z=0;$z<$count;$z++){
							$var = $value->meta_key.'_'.$z.'_'.$rep1;
							if(in_array($optionalType , $taxonomies)){
								$qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}terms wp JOIN {$wpdb->prefix}termmeta wpm ON wpm.term_id = wp.term_id where meta_key = %s AND wp.term_id = %d", $var, $id));
							}
							elseif($optionalType == 'user'){
								$qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}users wp JOIN {$wpdb->prefix}usermeta wpm ON wpm.user_id = wp.ID where meta_key = %s AND ID = %d", $var, $id));
							}
							else{
								$qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}posts wp JOIN {$wpdb->prefix}postmeta wpm ON wpm.post_id = wp.ID where meta_key = %s AND ID=%d", $var, $id));
							}
							$meta = isset($qry[0]->meta_value) ? $qry[0]->meta_value :'';

							if(is_numeric($meta) && $repType != 'user' && $repType != 'true_false' && $repType != 'image' && $repType != 'file' && $repType !='number' && $repType != 'range' && $repType != 'text' && $repType != 'select'){
								$meta_title = $wpdb->get_col($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$meta));
								foreach($meta_title as $meta_tit){
									$meta=$meta_tit;

								}	
							}
							if($repType == 'user'){
								$user_value = $wpdb->get_row($wpdb->prepare("select user_login from {$wpdb->prefix}users where ID= %d",$meta));
								$meta = $user_value->user_login;								
							}
							if($repType == 'true_false'){

								$meta = $meta;								
							}
							if($repType == 'image')
								$meta = $this->getAttachment($meta);
							if($repType == 'file')
								$meta =$this->getAttachment($meta);
							if($repType == 'repeater' || $repType == 'flexible_content')
								$repeaterOfrepeater = true;
							$rep_rep_fields = $this->getRepeaterofRepeater($rep1);
							if(!empty($rep_rep_fields)){
								//$repeater_data = array();
								foreach($rep_rep_fields as $repeat => $repeat1){
									$repeat_type = $alltype[$repeat1];
									$repeater_count = get_post_meta($id , $var , true);
									$repeat_val = "";
									if(is_numeric($repeater_count)|| empty($repeater_count)){
										for($r = 0; $r<$repeater_count; $r++){
											$var_rep = $value->meta_key.'_'.$z.'_'.$rep1.'_'.$r.'_'.$repeat1;

											if(in_array($optionalType , $taxonomies)){

												$rep_qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}terms wp JOIN {$wpdb->prefix}termmeta wpm ON wpm.term_id = wp.term_id where meta_key = %s AND wp.term_id = %d", $var_rep, $id));
											}else{
												$rep_qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}posts wp JOIN {$wpdb->prefix}postmeta wpm ON wpm.post_id = wp.ID where meta_key = %s AND ID=%d", $var_rep, $id));
											}
											if(isset($rep_qry[0]->meta_value)){
												$rep_meta = $rep_qry[0]->meta_value;
											}
											else{
												$rep_meta='';
											}
											if($repeat_type == 'image')
												$rep_meta = $this->getAttachment($rep_meta);
											if($repeat_type == 'file')
												$rep_meta =$this->getAttachment($rep_meta);

											if(is_serialized($rep_meta))
											{	
												$unmeta = unserialize($rep_meta);
												$rep_meta = "";
												$repeat_gal_val = '';
												$repeat_term_val = '';
												foreach ($unmeta as $unmeta1) {
													if($repeat_type == 'image'){
														$repeat_val .= $this->getAttachment($unmeta1).'|';
													}elseif( $repeat_type == 'gallery'){	
														$repeat_gal_val .= $this->getAttachment($unmeta1).',';
													}
													elseif($repeat_type  == 'taxonomy') {
														global $wpdb;
														if(is_numeric($unmeta1)){
															$meta_title = $wpdb->get_var("select name from {$wpdb->prefix}terms where term_id =$unmeta1");	
															$repeat_term_val .=$meta_title.',';
														}


													}
													else{
														$repeat_val .= $unmeta1.'|';
													}
												}											
												if($repeat_type == 'gallery'){
													$repeat_val .= rtrim($repeat_gal_val , ',') . '|';
												}
												elseif($repeat_type  == 'taxonomy') {
													$repeat_val .= rtrim($repeat_term_val , ',') . '|';
												}
											}
											else{
												$repeat_val .= $rep_meta . '|';		
											}	
										}
										// $repeater_data[$repeat1][] = rtrim($repeat_val,'|');
										$repeater_data[$repeat1][] = substr($repeat_val,0,-1);
									}
									// self::$export_instance->data[$id][$repeat1] = $repeater_data[$repeat1];

								}
							}



							if(is_serialized($meta))
							{
								$unmeta = unserialize($meta);

								$meta = "";
								foreach ($unmeta as $unmeta1) {
									if($repType == 'image' || $repType == 'gallery')
										$meta .= $this->getAttachment($unmeta1).',';
									elseif($repType == 'taxonomy') {
										// $meta .=$unmeta1.',';
										if(is_numeric($unmeta1)){
											$meta_title = $wpdb->get_var("select name from {$wpdb->prefix}terms where term_id =$unmeta1");	
											$meta .=$meta_title.',';
										}
									}
									elseif($repType == 'user') {
										$meta .=$unmeta1.',';
									}
									elseif($repType == 'post_object') {
										if(is_numeric($unmeta1)){
											$meta_title = $wpdb->get_col($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$unmeta1));
											foreach($meta_title as $meta_tit){
												$meta .=$meta_tit.',';

											}	
										}	
									}
									elseif($repType == 'relationship') {
										$meta .=$unmeta1.',';
									}
									elseif($repType == 'page_link') {
										$meta .=$unmeta1.',';
									}
									elseif($repType == 'link') {
										$meta .=$unmeta1 . ',';
									}
									else
										$meta .= $unmeta1.",";
								}

								if($repType == 'image' || $repType == 'gallery'){
									$meta = rtrim($meta,',');
								}else{
									$meta = rtrim($meta,',');
								}

							}
							if($meta)
								$reval .= $meta."|";

						}
						if($repType == 'group'){

							$rep_grp_fields = $this->getRepeaterofGroup($rep1);
							foreach($rep_grp_fields as $repgrpkey => $repgrpval){
								$rep_type = $alltype[$repgrpval];
								$con = $queval;
								$rep_grp_val = '';

								for($y=0;$y<$count;$y++){

									$var_grp_rep = $value->meta_key.'_'.$y.'_'.$rep1.'_'.$repgrpval;
									if(in_array($optionalType , $taxonomies)){

										$rep_grp_qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}terms wp JOIN {$wpdb->prefix}termmeta wpm ON wpm.term_id = wp.term_id where meta_key = %s AND wp.term_id = %d", $var_grp_rep, $id));
									}else{
										$rep_grp_qry = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}posts wp JOIN {$wpdb->prefix}postmeta wpm ON wpm.post_id = wp.ID where meta_key = %s AND ID=%d", $var_grp_rep, $id));
									}
									if(!empty($rep_grp_qry) && !empty($rep_grp_qry[0]->meta_value)){
										$rep_grp_meta = $rep_grp_qry[0]->meta_value;
										if($rep_type == 'image')
											$rep_grp_meta = $this->getAttachment($rep_grp_meta);
										if($rep_type == 'file')
											$rep_grp_meta =$this->getAttachment($rep_grp_meta);
									}
									else {
										$rep_grp_meta = '';
									}

									if(is_serialized($rep_grp_meta))
									{	
										$unmeta = unserialize($rep_grp_meta);
										$rep_grp_meta = "";
										$rep_grp_gal_val = '';
										$rep_grp_check_val = '';
										foreach ($unmeta as $unmeta1) {
											if($rep_type == 'image'){
												$rep_grp_val = $this->getAttachment($unmeta1);
											}elseif( $rep_type == 'gallery'){	
												$rep_grp_gal_val .= $this->getAttachment($unmeta1).',';
											}
											elseif($rep_type == 'taxonomy') {
												$rep_grp_val .=$unmeta1.',';

											}	
											elseif($rep_type == 'user'  || $rep_type == 'relationship'  || $rep_type == 'page_link') {
												if(is_numeric($unmeta1)){
													$meta_title = $wpdb->get_col($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$unmeta1));
													foreach($meta_title as $meta_tit){
														$rep_grp_val .=$meta_tit.',';
													}	
												}
												else{
													$rep_grp_val .= $unmeta1 . ',';
												}			
											}
											elseif($rep_type == 'post_object'){
												if(is_numeric($unmeta1)){
													$meta_title = $wpdb->get_col($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$unmeta1));
													foreach($meta_title as $meta_tit){
														$rep_grp_val .=$meta_tit.',';
													}
												}
												else{
													$rep_grp_val .= $unmeta1 . ',';
												}
											}
											elseif($rep_type == 'select'){
												if(is_numeric($unmeta1)){
													$meta_title = $wpdb->get_col($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$unmeta1));
													foreach($meta_title as $meta_tit){
														$rep_grp_val .=$meta_tit.',';
													}
												}
												else{
													$rep_grp_val .= $unmeta1 . ',';
												}
											}
											elseif($rep_type == 'link') {
												$rep_grp_val .=$unmeta1 . ',';
											}

											else{
												$rep_grp_check_val .= $unmeta1.',';
											}
										}	

										if($rep_type == 'gallery'){
											$rep_grp_val .= rtrim($rep_grp_gal_val , ','). '|'; 
										}
										elseif($rep_type == 'checkbox'){
											$rep_grp_val .= rtrim($rep_grp_check_val , ','). '|'; 
										}
										else{
											if(strpos($rep_grp_val , ',') !== false){
												$rep_grp_val = rtrim($rep_grp_val , ','). '|';
											}
											else{
												$rep_grp_vals = $rep_grp_val ;
												$rep_grp_val = $rep_grp_vals ;
											}

										}
									}elseif($rep_grp_meta != ''){
										if($rep_type == 'post_object'){
											if(is_numeric($rep_grp_meta)){
												$meta_title = $wpdb->get_col($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$rep_grp_meta));
												foreach($meta_title as $meta_tit){
													$rep_grp_val .=$meta_tit.'|';
												}
											}
											else{
												$rep_grp_val .= $rep_grp_meta . '|';
											}
										}
										else{
											$rep_grp_val .= $rep_grp_meta . '|';
										}


									} 	 
								}
								$repeater_data[$repgrpval] = rtrim($rep_grp_val,'|');
								self::$export_instance->data[$id][$repgrpval] = $repeater_data[$repgrpval];
							}
						}

						if($repeaterOfrepeater){
							if(!empty($repeater_data)){
								foreach($repeater_data as $repeater_key => $repeater_value){
									$repeaterOfvalue = '';
									foreach($repeater_value as $rep_rep_value){
										$repeaterOfvalue .= $rep_rep_value . '->';
									}
									// self::$export_instance->data[$id][$repeater_key] = self::$export_instance->returnMetaValueAsCustomerInput(rtrim($repeaterOfvalue,'->'));
									self::$export_instance->data[$id][$repeater_key] = substr(self::$export_instance->returnMetaValueAsCustomerInput($repeaterOfvalue),0,-2);
								}
							}
						}
						self::$export_instance->data[$id][$rep1] = self::$export_instance->returnMetaValueAsCustomerInput(rtrim($reval,'|'));
					}
					// }
				}
				elseif($getType == 'post_object'){
					$check = false;
					if(is_serialized($value->meta_value)){
						$value->meta_value = unserialize($value->meta_value);

						foreach($value->meta_value as $meta){
							$data[]=$meta;
							$check = true;
						}
					}
					if($check){
						foreach($data as $metas){
							if(is_numeric($metas)){

								$title = $wpdb->get_row($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$metas));
								$test[] = $title->post_title;
							}
						}
						if(!empty($test)) {
							$value->meta_value = implode(',',$test );			
						}
						else 
							$value->meta_value = "";
						self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
					}else{
						if(is_numeric($value->meta_value)){	
							//if(is_array($value->meta_value)){
							// foreach($value->meta_value as $meta){
							$meta=$value->meta_value;
							$title = $wpdb->get_col($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$meta));	
							//}
							//}
							if(isset($title )){

								foreach($title as $value->meta_value){
									self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
								}
							}
						}
					}

				}

				elseif( is_serialized($value->meta_value)){
					$acfva = unserialize($value->meta_value);
					$acfdata = "";
					foreach ($acfva as $key1 => $value1) {
						if($getType == 'checkbox'){
							$acfdata .= $value1.',';
						}

						elseif($getType == 'gallery' || $getType == 'image'){
							$attach = $this->getAttachment($value1);
							$getid=$wpdb->get_results("select ID from {$wpdb->prefix}posts where guid= '$attach'" ,ARRAY_A);
							global $wpdb;
							if(!empty($getid) && is_array($getid)){
								$ids=$getid[0]['ID'];


								$types_caption=$wpdb->get_results("select post_excerpt from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
								$types_description=$wpdb->get_results("select post_content from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
								$types_title=$wpdb->get_results("select post_title from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
								$types_alt_text=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attachment_image_alt' AND post_id='$ids'" ,ARRAY_A);
								$types_filename=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attached_file' AND post_id='$ids'" ,ARRAY_A);
								$filename = isset($types_filename[0]['meta_value']) ? $types_filename[0]['meta_value'] : '';

								if(!empty($filename)){
									$file_names=explode('/', $filename);
									$file_name=$file_names[2];
								}
								$typecap = $types_caption[0]['post_excerpt'].',';

								self::$export_instance->data[$id]['acf_caption'] = $typecap;
								self::$export_instance->data[$id]['acf_description'] = isset($types_description[0]['post_content']) ? $types_description[0]['post_content'] : '';
								self::$export_instance->data[$id]['acf_title'] = isset($types_title[0]['post_title']) ? $types_title[0]['post_title'] : '';
								self::$export_instance->data[$id]['acf_alt_text'] = isset($types_alt_text[0]['meta_value']) ? $types_alt_text[0]['meta_value'] : '';
								self::$export_instance->data[$id]['acf_file_name'] = $file_name;
							}
							$acfdata .= $attach.',';
						}
						elseif($getType == 'google_map')
						{
							$acfdata=$acfva['address'].'|'.$acfva['lat'].'|'.$acfva['lng'];
						}
						else{
							if(!empty($value1)) { 
								$acfdata .= $value1.',';
							}
						}
					}

					if($getType == 'gallery' || $getType == 'image'){
						$getid=$wpdb->get_results("select ID from {$wpdb->prefix}posts where guid= '$acfdata'" ,ARRAY_A);
						global $wpdb;
						foreach($getid as $getkey => $getval){
							$ids=$getval['ID'];
							$types_caption=$wpdb->get_results("select post_excerpt from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
							$types_description=$wpdb->get_results("select post_content from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
							$types_title=$wpdb->get_results("select post_title from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
							$types_alt_text=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attachment_image_alt' AND post_id='$ids'" ,ARRAY_A);
							$types_filename=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attached_file' AND post_id='$ids'" ,ARRAY_A);
							$filename=$types_filename[0]['meta_value'];
							$file_names=explode('/', $filename);
							$file_name= $file_names[2];
							$typecap=$types_caption[0]['post_excerpt'].',';
							self::$export_instance->data[$id]['acf_caption'] = $typecap;
							self::$export_instance->data[$id]['acf_description'] = isset($types_description[0]['post_content']) ? $types_description[0]['post_content'] : '';
							self::$export_instance->data[$id]['acf_title'] = isset($types_title[0]['post_title']) ? $types_title[0]['post_title'] : '';
							self::$export_instance->data[$id]['acf_alt_text'] = isset($types_alt_text[0]['meta_value']) ? $types_alt_text[0]['meta_value'] : '';
							self::$export_instance->data[$id]['acf_file_name'] = $file_name;	

						}
						$acfdata = rtrim($acfdata , ',');
					}else{
						$acfdata = rtrim($acfdata , ',');
					}
					self::$export_instance->data[$id][ $value->meta_key ] = self::$export_instance->returnMetaValueAsCustomerInput($acfdata);
				}
				elseif($getType == 'gallery' || $getType == 'image'|| $getType == 'file'  ){

					$attach1 = $this->getAttachment($value->meta_value);
					global $wpdb;
					$getid=$wpdb->get_results("select ID from {$wpdb->prefix}posts where guid= '$attach1'" ,ARRAY_A);
					foreach($getid as $getkey => $getval){
						$ids=$getval['ID'];
						$types_caption=$wpdb->get_results("select post_excerpt from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
						$types_description=$wpdb->get_results("select post_content from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
						$types_title=$wpdb->get_results("select post_title from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
						$types_alt_text=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attachment_image_alt' AND post_id='$ids'" ,ARRAY_A);
						$types_filename=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attached_file' AND post_id='$ids'" ,ARRAY_A);
						if(isset($types_filename[0]['meta_value'])){
							$filename=$types_filename[0]['meta_value'];
						}
						else{
							$filename='';
						}
						if(isset($filename)){
							$file_names=explode('/', $filename);
						}
						if(isset($file_names[2])){
							$file_name= $file_names[2];
						}
						self::$export_instance->data[$id]['acf_caption'] = $types_caption[0]['post_excerpt'];
						self::$export_instance->data[$id]['acf_description'] = $types_description[0]['post_content'];
						self::$export_instance->data[$id]['acf_title'] = $types_title[0]['post_title'];
						$types_alt_text[0]['meta_value']=isset($types_alt_text[0]['meta_value'])?$types_alt_text[0]['meta_value']:'';
						self::$export_instance->data[$id]['acf_alt_text'] = $types_alt_text[0]['meta_value'];
						$file_name=isset($file_name)?$file_name:'';
						self::$export_instance->data[$id]['acf_file_name'] = $file_name;
					}	

					self::$export_instance->data[$id][ $value->meta_key ] = $attach1;
				}
				elseif($getType == 'image_aspect_ratio_crop'){
					$attach2=$this->getAttachment($value->meta_value);
					$getid=$wpdb->get_results("select ID from {$wpdb->prefix}posts where guid= '$attach2'" ,ARRAY_A);
					global $wpdb;
					foreach($getid as $getkey=>$getval){
						$id=$getval['ID'];
						$acf_image_id=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= 'acf_image_aspect_ratio_crop_original_image_id' AND post_id='$id'" ,ARRAY_A);
						$acf_post_id=$acf_image_id[0]['meta_value'];
						$image = $this->getAttachment($acf_post_id);
						$acf_image_aspect_ratio_crop_parent_post_id =$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key='acf_image_aspect_ratio_crop_parent_post_id 'AND post_ID= '$id'" ,ARRAY_A);
						$img_id=$acf_image_aspect_ratio_crop_parent_post_id[0]['meta_value'];
						$acf_image_coordinates=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key='acf_image_aspect_ratio_crop_coordinates'AND post_ID= '$id'" ,ARRAY_A);
						$acf_coordinates=unserialize($acf_image_coordinates[0]['meta_value']);
						$width=$acf_coordinates['width'];
						$height=$acf_coordinates['height'];				
						$acf_crop=$width.'|'.$height .','. $image;
						self::$export_instance->data[$img_id][ $value->meta_key ] = $acf_crop;
					}
				}
				elseif($getType == 'clone'){
					$meta_key = $value->meta_key;
					$acf_post_meta_value = '';
					$get_acf_values = $wpdb->get_results( $wpdb->prepare("SELECT  post_content FROM {$wpdb->prefix}posts WHERE post_excerpt='$meta_key' and post_status != 'trash' AND post_type = %s", 'acf-field'));
					$post_content_values = $get_acf_values[0]->post_content;
					$get_acf_val = unserialize($post_content_values);
					$acf_values = $get_acf_val['clone'];
					foreach($acf_values as $acf_key=>$acf_key_values){
						$get_acf_post_excerpt = $wpdb->get_results( $wpdb->prepare("SELECT  post_excerpt FROM {$wpdb->prefix}posts WHERE post_name='$acf_key_values' and post_status != 'trash' AND post_type = %s", 'acf-field'));
						$acf_post_excerpt = $get_acf_post_excerpt[0]->post_excerpt;
						$get_acf_post_meta_value = $wpdb->get_results( "SELECT  meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key='$acf_post_excerpt' and post_id = $id");						
						if(!empty($get_acf_post_meta_value)) {
							$acf_post_meta_value .= $acf_post_excerpt .'->'.$get_acf_post_meta_value[0]->meta_value.',';
						}
						else {
							if(!empty($acf_post_meta_value))
								$acf_post_meta_value .= "" . ',';
							else
								$acf_post_meta_value = "";
						}
					}					
					$value->meta_value = rtrim($acf_post_meta_value,',');
					self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;					
				}

				else{
					self::$export_instance->data[$id][ $value->meta_key ] = self::$export_instance->returnMetaValueAsCustomerInput($value->meta_value);
				}
			}

			elseif(is_array($jet_fields) && in_array($value->meta_key, $jet_fields) && !empty($value->meta_value)){
				$getjetType = isset($jet_types[$value->meta_key]) ? trim($jet_types[$value->meta_key]) : '';
				if(empty($getjetType)){
					$temp_fieldname = array_search($value->meta_key, $jet_fields);
					$getjetType = isset($jet_types[$temp_fieldname]) ? $jet_types[$temp_fieldname] : '';
				}
				if($getjetType == 'checkbox' && is_string($value->meta_value)){
					$value->meta_value = unserialize($value->meta_value);
					$check = '';
					foreach($value->meta_value as $key => $metvalue){
						if(is_numeric($key)){
							$check .= $metvalue.',';	
							$rcheck = substr($check,0,-1);
							self::$export_instance->data[$id][ $value->meta_key ] = $rcheck;
						}
						else{
							if($metvalue == 'true'){

								$exp_value[] = $key;
							}
							if(isset($exp_value) && is_array($exp_value)){
								$value->meta_value = implode(',',$exp_value );
							}

							self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
						}

					}

				}

				elseif($getjetType == 'gallery' && isset($value->meta_value)){
					$gallery= explode(',',$value->meta_value);
					foreach($gallery as $gallerykey => $galleryval){
						if(is_numeric($galleryval)){
							$galleries[] = $this->getAttachment($galleryval);
						}
						elseif(is_serialized($galleryval)){
							$gal_value=unserialize($galleryval);
							foreach($gal_value as $key=>$gal_val){
								if(is_array($gal_val)){
									$galleries[] = $gal_val['url'];
								}
								else{
									$galleries[] = $gal_val;
								}

							}	
						}
						else{
							$galleries[] = $galleryval;
						}
						if(is_array($galleries)){
							$value->meta_value =!empty($galleries) ? implode(',',$galleries ) : '';	
						}
						self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
					}	
				}
				elseif( $getjetType == 'media'){
					$array_val= $value->meta_value;					
					if(is_numeric($array_val)){
						$value->meta_value = $this->getAttachment($array_val);
					}
					elseif(is_serialized($array_val)){
						$media_value=unserialize($array_val);
						$value->meta_value = array_key_exists('url',$media_value) ? $media_value['url'] : "";	

					}
					else{
						$value->meta_value=$array_val;
					}
					self::$export_instance->data[$id][$value->meta_key] = $value->meta_value;
				}

				elseif($getjetType == 'posts' && !empty($value->meta_value)){					
					if(is_serialized($value->meta_value)){
						$value->meta_value = unserialize($value->meta_value);
						foreach($value->meta_value as $postkey => $metpostvalue){
							if(is_numeric($metpostvalue)){
								$title = $wpdb->get_row($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d",$metpostvalue));
								$test[] = $title->post_title;
							}
						}
						$value->meta_value = !empty($test) ? implode(',',$test ) : '';			
						self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;	
					}
					else{
						$post_value = $value->meta_value;
						$post_title = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $post_value");
						self::$export_instance->data[$id][ $value->meta_key ] = $post_title;	
					}				
				}
				elseif($getjetType == 'select'){					
					if(is_serialized($value->meta_value)){
						$value->meta_value = unserialize($value->meta_value);
						foreach($value->meta_value as $metkey => $metselectvalue){
							$select[] = $metselectvalue;
							$value->meta_value = !empty($select) ? implode(',',$select ) : '';	
							self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
						}						
					}
					else{
						self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;						
					}															
				}
				elseif($getjetType == 'date'){
					if(!empty($value->meta_value)){
						if(strpos($value->meta_value, '-') !== FALSE){
						}else{
							if(is_numeric($value->meta_value)){
								$value->meta_value = date('Y-m-d', $value->meta_value);
							}
						}
					}
					self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
				}
				elseif($getjetType == 'datetime-local'){
					if(!empty($value->meta_value)){
						if(strpos($value->meta_value, '-') !== FALSE){
						}else{
							$value->meta_value = date('Y-m-d H:i', $value->meta_value);
						}
						$value->meta_value = str_replace(' ', 'T', $value->meta_value);
					}
					self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
				}
				elseif($getjetType == 'repeater'){
					global $wpdb;

					foreach($jet_types as $jettypename => $jettypeval){
						if($jettypeval == 'repeater'){
							$jet_fields_name =$jettypename;
							if($module == 'Users'){
								$fieldarr = $wpdb->get_results( $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}usermeta WHERE meta_key = %s AND user_id = %d ",$jet_fields_name,$id),ARRAY_A);
							}
							elseif($module == 'Categories' || $module == 'Taxonomies' || $module == 'Tags'){
								$fieldarr = $wpdb->get_results( $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}termmeta WHERE meta_key = %s AND term_id = %d ",$jet_fields_name,$id),ARRAY_A);
							}
							else{
								$fieldarr = $wpdb->get_results( $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = %s AND post_id = %d ",$jet_fields_name,$id),ARRAY_A);
							}
							$arr =json_decode(json_encode($fieldarr),true);
							$unser = unserialize($arr[0]['meta_value']);
							if(!empty($unser)){
								$arraykey = array_keys($unser);
								foreach($arraykey as $val){
									$values = explode('-',$val);
									$v = $values[1];
								}
							}
							else{
								$v =0;
							}


							$array_valuenum = $array_valuetext = $array_checkval = $array_wysval = $array_timval = $array_datval = $array_dattimval = $array_radval = $array_colorval = $array_switval = $array_iconval = $array_valuetextarea = $array_selval = $array_postval= $array_mediaval = $array_galval = '';							
							for($i=0 ; $i<=$v; $i++){
								$j =0;
								$idkey = 'item-'.$i;
								$array = isset($unser[$idkey]) ? $unser[$idkey] : '';

								if(!empty($array)){

									$array_keys =array_keys($array);
									foreach($array_keys as $arrkey){
										$arrcol[$arrkey] = array_column($unser,$arrkey);
									}
									foreach($arrcol as $array_key => $array_val){

										$array_valuenum = $array_valuetext = $array_checkval = $array_wysval = $array_timval = $array_datval = $array_dattimval = $array_radval = $array_colorval = $array_switval = $array_iconval = $array_valuetextarea = $array_selval = $array_postval= $array_mediaval = $array_galval = '';

										if($jet_rep_types[$array_key] == 'text'){

											foreach($array_val as $arrval){
												$array_valuetext .= $arrval.'|';
											}

											self::$export_instance->data[$id][ $array_key ] = $array_valuetext;
										}
										elseif($jet_rep_types[$array_key] == 'checkbox'){
											foreach($array_val as $arrval){
												$exp_value = [];

												foreach($arrval as $key => $metvalue){
													if($metvalue == 'true'){
														$exp_value[] = $key;

													}

												}
												$checkval = implode(',',$exp_value );	

												$array_checkval .=$checkval.'|';

												self::$export_instance->data[$id][$array_key] = $array_checkval;

											} 
										}

										elseif( $jet_rep_types[$array_key] == 'media'){
											$medias = [];
											foreach($array_val as $arrval){
												if(is_numeric($arrval)){
													$medias[] = $this->getAttachment($arrval);
												}
												elseif(is_array($arrval)){
													$medias[] = $arrval['url'];	

												}
												else{
													$medias[]=$arrval;
												}

											}

											$mediaval = implode('|',$medias );	

											$array_mediaval .=$mediaval.'|';

											self::$export_instance->data[$id][$array_key] = $mediaval;
										}


										elseif( $jet_rep_types[$array_key] == 'gallery'){
											foreach($array_val as $arrval){
												$galleries =[];

												if(is_array($arrval)){
													foreach($arrval as $key => $gallryvalue){
														$galleries[] = $gallryvalue['url'];
													}

												}
												else{
													$gallery= explode(',',$arrval);
													foreach($gallery as $gallerykey => $galleryval){
														if(is_numeric($galleryval)){
															$galleries[] = $this->getAttachment($galleryval);
														}
														else{
															$galleries[]=$galleryval;
														}


													}
												}
												$gal_val = implode(',',$galleries );
												$array_galval .=$gal_val.'|';	
												self::$export_instance->data[$id][$array_key] = $array_galval;
											}
										}
										elseif($jet_rep_types[$array_key] == 'posts'){
											$test =[];
											$posts_val ='';
											foreach($array_val as $arrval){
												$test =[];
												if(is_array($arrval)){

													foreach($arrval as $postkey => $metpostvalue){
														if(is_numeric($metpostvalue)){
															$title = $wpdb->get_row($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d ORDER BY ID DESC",$metpostvalue));
															$test[] = $title->post_title;

														}	

													}
													$postval = implode(',',$test );

													$posts_val .=$postval.'|';	
													self::$export_instance->data[$id][$array_key] = $posts_val;

												}
												else{

													if(is_numeric($arrval)){
														$title = $wpdb->get_row($wpdb->prepare("select post_title from {$wpdb->prefix}posts where ID = %d ORDER BY ID DESC",$arrval));
														$testing = $title->post_title;
													}
													$posts_val .=$testing.'|';
													self::$export_instance->data[$id][$array_key] = $posts_val;
												}	
											}
										}
										elseif($jet_rep_types[$array_key] == 'select'){
											$array_selval ='';
											foreach($array_val as $arrval){
												if(is_array($arrval)){
													$select =[];
													foreach($arrval as $metselectvalue){
														//foreach($metvalue as $metselectvalue){
														$select[] = $metselectvalue;
														$array_vals = implode(',',$select );
														//}	
													}

													$array_selval .=$array_vals.'|';
													self::$export_instance->data[$id][$array_key] = $array_selval;
												}
												else{
													$array_selval .=$arrval.'|';
													self::$export_instance->data[$id][$array_key] = $array_selval;
												}
											}
										}
										elseif($jet_rep_types[$array_key] == 'date'){	
											$repdateval = array();																					
											foreach($array_val as $arrval){
												if(!empty($arrval)){
													if(strpos($arrval, '-') !== FALSE){
													}else{
														$arrval = date('Y-m-d', $arrval);
													}
													$repdateval[] = $arrval;
												}
												else											
													$repdateval[] = "";										
											}											
											if(!empty($repdateval))
												$array_datval = implode('|',$repdateval);
											self::$export_instance->data[$id][ $array_key ] = $array_datval;
										}
										elseif($jet_rep_types[$array_key] == 'time'){
											foreach($array_val as $arrval){
												$array_timval .= $arrval.'|';
											}
											//$array_timval = substr($array_timval,0,-1);
											self::$export_instance->data[$id][ $array_key ] = $array_timval;
										}
										elseif($jet_rep_types[$array_key] == 'wysiwyg'){
											foreach($array_val as $arrval){
												$array_wysval .= $arrval.'|';
											}
											//$array_wysval = substr($array_wysval,0,-1);
											self::$export_instance->data[$id][ $array_key ] = $array_wysval;
										}
										elseif($jet_rep_types[$array_key] == 'datetime-local'){
											$repdatelocalval = array();
											foreach($array_val as $arrval){
												if(!empty($arrval)){
													if(strpos($arrval, '-') !== FALSE){
													}else{
														$arrval = date('Y-m-d H:i', $arrval);
													}
													$arrval = str_replace(' ', 'T', $arrval);
													$repdatelocalval[] = $arrval;
												}
												else 
													$repdatelocalval[] = "";												
											}

											if(!empty($repdatelocalval))
												$array_datval = implode('|',$repdatelocalval);											
											self::$export_instance->data[$id][ $array_key ] = $array_dattimval;
										}
										elseif($jet_rep_types[$array_key] == 'iconpicker'){
											foreach($array_val as $arrval){
												$array_iconval .= $arrval.'|';
											}
											//$array_iconval = substr($array_iconval,0,-1);
											self::$export_instance->data[$id][ $array_key ] = $array_iconval;
										}
										elseif($jet_rep_types[$array_key] == 'switcher'){
											foreach($array_val as $arrval){
												$array_switval .= $arrval.'|';
											}
											//$array_switval = substr($array_switval,0,-1);
											self::$export_instance->data[$id][ $array_key ] = $array_switval;
										}
										elseif($jet_rep_types[$array_key] == 'colorpicker'){
											foreach($array_val as $arrval){
												$array_colorval .= $arrval.'|';
											}
											//$array_colorval = substr($array_colorval,0,-1);
											self::$export_instance->data[$id][ $array_key ] = $array_colorval;
										}

										elseif($jet_rep_types[$array_key] == 'number'){
											foreach($array_val as $arrval){
												$array_valuenum .= $arrval.'|';
												$array_valuenum = rtrim($array_valuenum);
											}
											//$array_valuenum = substr($array_valuenum,0,-1);
											self::$export_instance->data[$id][$array_key] = $array_valuenum;

										}
										elseif($jet_rep_types[$array_key] == 'textarea'){
											foreach($array_val as $arrval){
												$array_valuetextarea .= $arrval.'|';
											}
											//$array_valuetextarea = substr($array_valuetextarea,0,-1);
											self::$export_instance->data[$id][$array_key] = $array_valuetextarea;

										}
										else{
											if(array_search("radio",$jet_rep_types)){
												//$array_radval .= '|';

												if($jet_rep_types[$array_key] == 'radio'){
													foreach($array_val as $arrval){
														$array_radval .= $arrval.'|';
													}
												}
												self::$export_instance->data[$id][ $array_key ] = $array_radval;
											}
										}
									}
								}
							}
						}
					}
				}
				// Check if $value is an object and has the 'meta_value' property
				else if (is_object($value) && isset($value->meta_value)) {
					$meta_value = $value->meta_value;

					// Check if $meta_value is a JSON string
					if (is_string($meta_value) && json_decode($meta_value)) {
						$meta_value = json_decode($meta_value, true);
					}

					$is_unserialized = is_array($meta_value);

					if ($is_unserialized) {
						$output_array = [];

						foreach ($meta_value as $key => $val) {
							// If the value is an array (like 'week_days'), use '|' as a separator
							if (is_array($val)) {
								$output_array[] = implode('|', $val); // Use '|' for arrays
							} else {
								// Otherwise, just add the value as is
								$output_array[] = $val;
							}
						}

						// Join values with commas for CSV format
						$value_all = implode(',', $output_array);

						self::$export_instance->data[$id][$value->meta_key] = $value_all;
					}
				}
				else{	
					self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
				}								
			}

			elseif (!empty($typesf) && isset($value->meta_key) && in_array($value->meta_key, $typesf)) {
				global $wpdb;
				$type_value = '';	
				$typeoftype = $typeOftypesField[$value->meta_key];
				if(in_array($optionalType , $taxonomies)){
					$type_data =  get_term_meta($id,$value->meta_key);
				}
				elseif($optionalType == 'user' || $optionalType == 'users'){
					$type_data =  get_user_meta($id,$value->meta_key);
				}
				else{
					$type_data =  get_post_meta($id,$value->meta_key);
					$typcap = "";
					foreach($type_data as $type_key =>$type_value){
						if(!is_array($type_value)){
							$substring='http';
							$string=substr($type_value,0,4);
							if($string==$substring){	
								$getid=$wpdb->get_results("select ID from {$wpdb->prefix}posts where guid= '$type_value'" ,ARRAY_A);
								foreach($getid as $getkey => $getval){
									global $wpdb;
									$ids=$getval['ID'];
									$types_caption=$wpdb->get_results("select post_excerpt from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
									$types_description=$wpdb->get_results("select post_content from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
									$types_title=$wpdb->get_results("select post_title from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
									$types_alt_text=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attachment_image_alt' AND post_id='$ids'" ,ARRAY_A);
									$types_filename=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attached_file' AND post_id='$ids'" ,ARRAY_A);
									if(isset($types_filename[0])){
										$filename=$types_filename[0]['meta_value'];
									}
									if(isset($filename)){
										$file_names=explode('/', $filename);
									}
									if(isset($file_names[2])){
										$file_name= $file_names[2];
									}
									$file_name=isset($file_name)?$file_name:'';
									self::$export_instance->data[$id]['types_caption'] = $types_caption[0]['post_excerpt'];
									self::$export_instance->data[$id]['types_description'] = $types_description;
									self::$export_instance->data[$id]['types_title'] = $types_title;
									self::$export_instance->data[$id]['types_alt_text'] = $types_alt_text;
									self::$export_instance->data[$id]['types_file_name'] = $file_name;


								}
							}

							$type_value = rtrim($type_value , '|');

						}

					}

					self::$export_instance->data[$id][ $value->meta_key ] = $type_value;

				}

				if(is_array($type_data)){	
					$type_value="";
					foreach($type_data as $k => $mid){	
						if(is_array($mid) && !empty($mid)){
							if($typeoftype == 'skype'){	
								$type_value .= $mid['skypename'] . '|';
							}
							elseif($typeoftype == 'checkboxes'){
								$check_type_value = '';	

								$types_cf_fields = get_option('wpcf-fields');
								$types_fd = end(explode('wpcf-',$value->meta_key));
								$options_value =$types_cf_fields[$types_fd]['data']['options'];
								foreach($mid as $mid_key => $mid_value){
									foreach($options_value as $opt_key => $opt_val){
										if(is_array($mid_value)){
											if($mid_value[0] == 1){
												if($opt_key == $mid_key){
													$check_type_value .= $opt_val['title'] . ',';
												}
											}
											else{
												if($opt_key == $mid_key){
													$check_type_value .= $opt_val['title'] . ',';
												}
											}

										}
									}

								}
								// foreach($mid as $mid_value){
								// 		$check_type_value .= $mid_value[0] . ',';
								// }
								$type_value .= rtrim($check_type_value , ',');
							}	
							elseif($typeoftype == 'checkbox'){
								$check_value = implode(',',$mid_value);
								$type_value = rtrim($check_value,',');
							}
						}
						elseif($typeoftype == 'date'){
							$wptypesfields = get_option('wpcf-fields');
							$fd_name = preg_replace('/wpcf-/','', $value->meta_key );	
							if (isset($wptypesfields[$fd_name]['data']['date_and_time'])) {
								$format = $wptypesfields[$fd_name]['data']['date_and_time'];
								$dateformat =$format == 'date'?"Y-m-d" : "Y-m-d H:i:s";
								if(!empty($mid))
									$type_value .= date($dateformat, $mid) . '|';
							}
						}
						else{
							if(!is_array($mid)){
								$type_value .= $mid . '|';
							}	
						}
					}
					if(preg_match('/wpcf-/',$value->meta_key)){	
						$value->meta_key = preg_replace('/wpcf-/','', $value->meta_key );	
						self::$export_instance->data[$id][ $value->meta_key ] = rtrim($type_value , '|');					
					}
				}	

				if(preg_match('/group_/',$value->meta_key)){
					$getType = $alltype[$value->meta_key];
					if($value->meta_key == 'group_gallery' || $value->meta_key == 'group_image'|| $value->meta_key == 'file'  ){
						$groupattach = $this->getAttachment($value->meta_value);
						self::$export_instance->data[$id][ $value->meta_key ] = $groupattach;
					}
					else{
						$value->meta_key = preg_replace('/group_/','', $value->meta_key );
						self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
					}
				}

				//TYPES Allow multiple-instances of this field
			}
			elseif(isset($value->meta_key) && in_array($value->meta_key, $group_unset) && is_serialized($value->meta_value)) {
				$unser = unserialize($value->meta_value);
				$data = "";
				foreach ($unser as $key4 => $value4) 
					$data .= $value4.',';
				self::$export_instance->data[$id][ $value->meta_key ] = substr($data, 0, -1);
			}


			elseif(isset($value->meta_key) && !empty($metabox_fields) && is_array($metabox_fields) || (is_array($metabox_fields) &&array_key_exists($value->meta_key, $metabox_fields))){
				foreach($metabox_fields as $meta_val){
					if($meta_val['type'] == 'taxonomy'){
						$meta_tax = $meta_val['taxonomy'][0];
						$meta_key = $meta_val['id'];

						$get_metabox_titles = $wpdb->get_results("SELECT t.name FROM {$wpdb->prefix}terms t Inner join {$wpdb->prefix}term_taxonomy tax ON t.term_id=tax.term_id INNER JOIN {$wpdb->prefix}term_relationships tr ON tr.term_taxonomy_id=tax.term_taxonomy_id  WHERE tr.object_id =$id AND tax.taxonomy ='$meta_tax'",ARRAY_A);
						$titles =array();
						if(is_array($get_metabox_titles)){
							foreach($get_metabox_titles as $title => $val){

								$titles[] = $val['name'];
							}
							$tax_val =implode('|',$titles);

							self::$export_instance->data[$id][$meta_key] = $tax_val;
						}
					}
				}
				if(isset($value->meta_key) && array_key_exists($value->meta_key, $metabox_fields)){	
					$get_metabox_fieldtype = $metabox_fields[$value->meta_key]['type'];
					$field_clone = $metabox_fields[$value->meta_key]['clone'];
					if($get_metabox_fieldtype == 'group'){							
						$this->metabox_groupExport($metabox_fields,$value,$id);
					}	

					if($field_clone){
						$this->metaboxFieldCloneExport($metabox_fields,$value,$id);
					}
					else {
						if($get_metabox_fieldtype == 'select' || $get_metabox_fieldtype == 'select_advanced' || $get_metabox_fieldtype == 'checkbox_list' || $get_metabox_fieldtype == 'text_list' || $get_metabox_fieldtype == 'file_advanced' || $get_metabox_fieldtype == 'image_advanced'){

							$metabox_metakey = $value->meta_key;
							if($module == 'Users'){
								$get_metabox_values = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}usermeta WHERE meta_key = '$metabox_metakey' AND user_id = $id ", ARRAY_A);
							}else if($module == 'Categories' || $module == 'Taxonomies' || $module == 'Tags'){
								$get_metabox_values = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}termmeta WHERE meta_key = '$metabox_metakey' AND term_id = $id ", ARRAY_A);
							}else{	
								$get_metabox_values = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '$metabox_metakey' AND post_id = $id ", ARRAY_A);
							}

							$metabox_values = array_column($get_metabox_values, 'meta_value');

							if($get_metabox_fieldtype == 'file_advanced' || $get_metabox_fieldtype == 'image_advanced'){
								$get_metabox_file_url = [];
								foreach($metabox_values as $metavalue){
									$get_metabox_file_url[] = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts WHERE ID = $metavalue AND post_type = 'attachment' ");
								}

								$metabox_file_value = implode(',', $get_metabox_file_url);
								self::$export_instance->data[$id][ $value->meta_key ] = $metabox_file_value;
							}
							else{
								$metabox_value = !empty($metabox_values) ? implode(',', $metabox_values) : '';
								self::$export_instance->data[$id][ $value->meta_key ] = $metabox_value;
							}
						}

						elseif($get_metabox_fieldtype == 'fieldset_text'){
							$fieldset_values = unserialize($value->meta_value);
							$fieldset_value = !empty($fieldset_values) ? implode(',', array_values($fieldset_values)) : '';
							self::$export_instance->data[$id][ $value->meta_key ] = $fieldset_value;
						}

						elseif($get_metabox_fieldtype == 'post' || $get_metabox_fieldtype == 'taxonomy' || $get_metabox_fieldtype == 'user'){
							if($get_metabox_fieldtype == 'post'){
								$get_metabox_titles = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $value->meta_value ");
							}
							elseif($get_metabox_fieldtype == 'taxonomy' || $get_metabox_fieldtype == 'taxonomy_advanced'){
								$get_metabox_titles = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = $value->meta_value ");
							}
							elseif($get_metabox_fieldtype == 'user'){
								$get_metabox_titles = $wpdb->get_var("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = $value->meta_value ");
							}
							self::$export_instance->data[$id][ $value->meta_key ] = $get_metabox_titles;
						}
						elseif($get_metabox_fieldtype == 'date')	{
							$dateformat = "Y-m-d";
							if(is_numeric($value->meta_value))
								$date_value = date($dateformat,$value->meta_value);
							else
								$date_value = $value->meta_value;

							self::$export_instance->data[$id][ $value->meta_key ] = $date_value;	
						}
						elseif($get_metabox_fieldtype == 'image' || $get_metabox_fieldtype == 'file'){
							$upload_values = $value->meta_value;
							$upload_value = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts WHERE ID = $upload_values AND post_type = 'attachment' ");
							self::$export_instance->data[$id][ $value->meta_key ] = $upload_value;
						}
						else{
							self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
						}
					}
				}
			}
			else if(!empty($metabox_relation_fields) && is_array($metabox_relation_fields))
			{		
				foreach($metabox_relation_fields as $meta_key => $meta_value)
				{
					$metabox_relation_table = $wpdb->prefix . "mb_relationships";					
					if($meta_value['type'] == 'group'){							
						$this->metabox_groupExport($metabox_relation_fields,$value,$id);
					}	
					else {										
						$this->metabox_NormalFieldsExport($metabox_relation_fields,$value,$id,$module);
					}							

					if (is_array($meta_value) && array_key_exists('relationship',$meta_value) && $meta_value['relationship'] == 1)
					{
						if ($meta_value['type'] == 'user')
						{
							$post_type = 'user';
						}
						else if ($meta_value['type'] == 'post')
						{
							$post_type = $meta_value['post_type'][0];
						}
						else
						{
							$post_type = $meta_value['taxonomy'][0];
						}
						if (strpos($meta_key, '_to') !== false)
						{
							$types = 'from';
						}
						else
						{
							$types = 'to';
						}
						$meta_title_name = explode('_', $meta_key);
						$meta_title_name = $meta_title_name[0];
						if($types == 'from'){
							$relate_id =array();
							$metabox_relation_value = $wpdb->get_results("SELECT * FROM $metabox_relation_table where type = '$meta_title_name'",ARRAY_A);
							foreach($metabox_relation_value as $meta_relations)
							{
								if($id ==$meta_relations['from']){
									$relate_id[] = $meta_relations['to'];
								}
							}
						}
						else{
							// $metabox_relation_value = $wpdb->get_results("SELECT from FROM $metabox_relation_table where to=$id and type = '$meta_title_name'",ARRAY_A);
							$relate_id =array();
							$metabox_relation_value = $wpdb->get_results("SELECT * FROM $metabox_relation_table where type = '$meta_title_name'",ARRAY_A);
							foreach($metabox_relation_value as $meta_relations)
							{
								if($id ==$meta_relations['to']){
									$relate_id[] = $meta_relations['from'];
								}
							}
						}
						$post_titles ='';
						foreach($relate_id as $relate_value)
						{
							if($post_type == 'user')
							{
								$post_title = $wpdb->get_var("SELECT display_name FROM {$wpdb->prefix}users where ID=$relate_value");
							}
							else if(array_key_exists($post_type,$taxonomies))
							{
								$post_title = $wpdb->get_var("SELECT t.name FROM {$wpdb->prefix}terms as t inner join {$wpdb->prefix}term_taxonomy as tt where t.term_id=$relate_value and tt.taxonomy='$post_type'");
							}
							else
							{
								$post_title = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts where ID=$relate_value and post_type='$post_type'");	
							}

							$post_titles .= $post_title.',';
						}		
						self::$export_instance->data[$id][$meta_key] = trim($post_titles,',');
					}
				}
			}
			else{
				if(isset($value->meta_key) && empty(self::$export_instance->data[$id][ $value->meta_key ])){
					self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
				}
			}
		}

	}

	public function get_jetengine_relation_meta_fields($jet_relation_id, $posted_id, $get_rel_metafields, $get_jet_rel_object_connections, $connection_type, $jet_relmeta_table_name){
		global $wpdb;		
		foreach($get_rel_metafields as $get_rel_metavalue){
			$rel_meta_key = $get_rel_metavalue['name'];

			$get_jet_rel_values = [];
			foreach($get_jet_rel_object_connections as $get_jet_rel_object_connection_values){
				if($connection_type == 'parent'){
					$get_jet_rel_value = $wpdb->get_var("SELECT meta_value FROM $jet_relmeta_table_name  WHERE rel_id = $jet_relation_id AND meta_key = '$rel_meta_key' AND parent_object_id = $posted_id AND child_object_id = $get_jet_rel_object_connection_values ");
					if(is_serialized($get_jet_rel_value)){
						$unser_relvalue = unserialize($get_jet_rel_value);
						//Added for export only media id,while media have return format as both[if]
						if($rel_meta_key == 'media'){
							if(!empty($unser_relvalue) && array_key_exists('id',$unser_relvalue))
								$get_jet_rel_value = $unser_relvalue['id'];
						}
						else
							$get_jet_rel_value = !empty($unser_relvalue) ?  implode(',', $unser_relvalue) : '';
					}
					$get_jet_rel_values[] = $get_jet_rel_value;
				}
				else{					
					$get_jet_rel_values[] = $wpdb->get_var("SELECT meta_value FROM $jet_relmeta_table_name  WHERE rel_id = $jet_relation_id AND meta_key = '$rel_meta_key' AND parent_object_id = $get_jet_rel_object_connection_values AND child_object_id = $posted_id ");
				}
			}			
			$get_rel_meta_value = '';
			if(!empty($get_jet_rel_values)){
				$get_rel_meta_value = !empty($get_jet_rel_values) ? implode('|', $get_jet_rel_values) : '';
			}

			self::$export_instance->data[$posted_id][ $rel_meta_key . ' :: ' . $jet_relation_id ] = $get_rel_meta_value;

		}
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

	public function getRepeaterofRepeater($parent)
	{
		global $wpdb;	
		$get_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts where post_excerpt = %s", $parent), ARRAY_A);
		foreach($get_fields as $getkey =>$getfields){
			$field_info=unserialize($getfields['post_content']);
			if(isset($field_info['type']) && $field_info['type'] == 'repeater'){
				$test=$getfields['ID'];
			}
			else{
				$test=$getfields['ID'];
			}
		}
		// $test = $get_fields[0]['ID'] ;
		$get_fieldss = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts where post_parent = %d", $test), ARRAY_A);
		$i = 0;
		foreach ($get_fieldss as $key => $value) {
			$array[$i] = $value['post_excerpt'];			
			$i++;
		}
		$array=isset($array)?$array:'';
		return $array;	
	}

	public function getgroupofgroup($parent)
	{
		global $wpdb;	
		$get_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts where post_excerpt = %s", $parent), ARRAY_A);
		$test = $get_fields[0]['ID'] ;	
		$get_fieldss = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts where post_parent = %d", $test), ARRAY_A);
		$i = 0;
		foreach ($get_fieldss as $key => $value) {
			$array[$i] = $value['post_excerpt'];			
			$i++;
		}
		return $array;	
	}

	public function getRepeaterofGroup($parent){
		global $wpdb;
		$get_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts where post_excerpt = %s", $parent), ARRAY_A);
		$test = $get_fields[0]['ID'] ;	
		$get_fieldss = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts where post_parent = %d", $test), ARRAY_A);
		$i = 0;
		foreach ($get_fieldss as $key => $value) {
			$array[$i] = $value['post_excerpt'];			
			$i++;
		}
		return $array;	
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
	public function FetchCategories($module, $optionalType, $is_filter, $headers, $mode = null, $eventExclusions = null) {

		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT COUNT(*) AS count 
			FROM {$wpdb->prefix}terms t 
			INNER JOIN {$wpdb->prefix}term_taxonomy tax 
			ON tax.term_id = t.term_id 
			WHERE tax.taxonomy = %s",
		'category'
		);
		$count = $wpdb->get_var($query);
		self::$export_instance->totalRowCount = $count;
		$offset = self::$export_instance->offset;
		$limit = self::$export_instance->limit;		
		$query="SELECT term_id FROM {$wpdb->prefix}term_taxonomy where taxonomy='category'";

		$offset_limit = " order by term_id asc limit $offset, $limit";
		$query_with_offset_limit = $query;
		$result= $wpdb->get_col($query_with_offset_limit);
		if($eventExclusions['is_check'] == 'true'){
			$headers[] = 't.term_id'; //specific inclusion
			// filtered headers
			$wp_cat_fields_column_value = [
				'name','slug','description','parent','t.term_id'
			];
			$filtered_headers = array_intersect($headers, $wp_cat_fields_column_value);
			$selected_columns = implode(', ', $filtered_headers);
		}
		$query1=array();
		foreach ($result as $re) {
			if (isset($selected_columns) && !empty($selected_columns)) {
				// Use the dynamically selected columns based on $headers
				$query1[] = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT $selected_columns FROM {$wpdb->prefix}terms as t 
						JOIN {$wpdb->prefix}term_taxonomy as tx 
						ON t.term_id = tx.term_id 
						WHERE t.term_id = %d",
		$re
					)
				);
			} else {
				$query1[] = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT t.name, t.slug, tx.description, tx.parent, t.term_id 
						FROM {$wpdb->prefix}terms as t 
						JOIN {$wpdb->prefix}term_taxonomy as tx 
						ON t.term_id = tx.term_id 
						WHERE t.term_id = %d",
		$re
					)
				);
			}
		}
		foreach($query1 as $my_data){
			if(!empty($my_data)){
				$query2[] = $my_data;
			}
		}
		$new=array();
		$query3 = !empty($query2) ? array_slice($query2, $offset, $limit) : [];
		foreach($query3 as $qkey => $qval){		
			foreach($qval as $qid){
				$new[]=$qid;
			}

		}	
		if(!empty($new)) {
			foreach( $new as $termKey => $termValue ) {
				$termID = $termValue->term_id ?? '';
				$termValue->cat_name=isset($termValue->cat_name)?$termValue->cat_name:'';
				$termName = $termValue->cat_name ?? '';
				$termSlug = $termValue->slug ?? '';
				$termDesc = $termValue->description ?? '';
				$termParent = $termValue->parent ?? '';
				$term_Parent_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = '$termParent'");
				if($termParent == 0) {
					self::$export_instance->data[$termID]['**name'] = $termName;
				} else {

					$termParentName = get_cat_name( $termParent );
					self::$export_instance->data[$termID]['name'] = $termParentName . '|' . $termName;					
				}
				self::$export_instance->data[$termID]['slug'] = $termSlug;
				self::$export_instance->data[$termID]['description'] = $termDesc;
				self::$export_instance->data[$termID]['parent'] = $term_Parent_name ? $term_Parent_name : '';
				self::$export_instance->data[$termID]['TERMID'] = $termID;

				self::$export_instance->getWPMLData($termID,$optionalType,$module);
				if(is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php') || is_plugin_active('polylang-wc/polylang-wc.php')){
					self::$export_instance->getPolylangData($termID,$optionalType,$module);
				}
				$this->getPostsMetaDataBasedOnRecordId ($termID, $module, $optionalType);
				if(is_plugin_active('wordpress-seo/wp-seo.php') || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php')){
					$seo_yoast_taxonomies = get_option( 'wpseo_taxonomy_meta' );
					if ( isset( $seo_yoast_taxonomies['category'] ) ) {
						self::$export_instance->data[ $termID ]['title'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_title'];
						self::$export_instance->data[ $termID ]['meta_desc'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_desc'];
						self::$export_instance->data[ $termID ]['canonical'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_canonical'];
						self::$export_instance->data[ $termID ]['bctitle'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_bctitle'];
						self::$export_instance->data[ $termID ]['meta-robots-noindex'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_noindex'];
						//self::$export_instance->data[ $termID ]['sitemap-include'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_sitemap_include'];
						self::$export_instance->data[ $termID ]['opengraph-title'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_opengraph-title'];
						self::$export_instance->data[ $termID ]['opengraph-description'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_opengraph-description'];
						self::$export_instance->data[ $termID ]['opengraph-image'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_opengraph-image'];
						self::$export_instance->data[ $termID ]['twitter-title'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_twitter-title'];
						self::$export_instance->data[ $termID ]['twitter-description'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_twitter-description'];
						self::$export_instance->data[ $termID ]['twitter-image'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_twitter-image'];
						self::$export_instance->data[ $termID ]['focus_keyword'] = $seo_yoast_taxonomies['category'][$termID]['wpseo_focuskw'];

						if(isset($seo_yoast_taxonomies['category'][$termID]['wpseo_focuskeywords']) && !empty($seo_yoast_taxonomies['category'][$termID]['wpseo_focuskeywords'])){
							$decode_value = json_decode($seo_yoast_taxonomies['category'][$termID]['wpseo_focuskeywords'], true);
							if (is_array($decode_value) && !empty($decode_value)) {
								$keywords = array_column($decode_value, 'keyword');
								self::$export_instance->data[$termID]['focuskeywords'] = implode('|', $keywords);
							} else {
								self::$export_instance->data[$termID]['focuskeywords'] = '';
							}
						}

						if(isset($seo_yoast_taxonomies['category'][$termID]['wpseo_keywordsynonyms']) && !empty($seo_yoast_taxonomies['category'][$termID]['wpseo_keywordsynonyms'])){
							$decode_value1 = json_decode($seo_yoast_taxonomies['category'][$termID]['wpseo_keywordsynonyms'], true);
							if (is_array($decode_value1)) {
								if (!empty($decode_value1)) {
									array_shift($decode_value1);
								}
								self::$export_instance->data[$termID]['keywordsynonyms'] = implode('|', $decode_value1);
							} else {
								self::$export_instance->data[$termID]['keywordsynonyms'] = '';
							}
						}
					}
				}			
			}
		}
		$result = self::$export_instance->finalDataToExport(self::$export_instance->data, $module);
		if(is_plugin_active('advanced-custom-fields-pro/acf.php')){
			$result = self::$export_instance->convert_acfname_to_key($result,$module,$optionalType,'pro');
		}
		elseif((is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('secure-custom-fields/secure-custom-fields.php')) && is_plugin_active('acf-repeater/acf-repeater.php')){
			$result = self::$export_instance->convert_acfname_to_key($result,$optionalType,'free');
		}	
		if($is_filter == 'filter_action'){
			return $result;
		}

		if($mode == null){
			self::$export_instance->proceedExport($result);
		}else{
			return $result;
		}
		return $result;
	}

	public function get_common_post_metadata($meta_id){
		global $wpdb;
		$mdata = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_id = %d", $meta_id) ,ARRAY_A);
		return $mdata[0];
	}

	public function getAttachment($id)
	{
		global $wpdb;
		$attachment_file = wp_get_attachment_url( $id );
		// $get_attachment = $wpdb->prepare("select guid from {$wpdb->prefix}posts where ID = %d AND post_type = %s ", $id, 'attachment');
		// $attachment = $wpdb->get_results($get_attachment);
		// if(isset($attachment[0]->guid)){
		// 	$attachment_file = $attachment[0]->guid;
		// }
		$attachment_file=isset($attachment_file)?$attachment_file:'';
		return $attachment_file;

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
	public function FetchTags($module, $optionalType, $is_filter, $headers, $mode = null, $eventExclusions = null) {
		global $wpdb;
		// $get_all_terms = get_tags('hide_empty=0');
		// self::$export_instance->totalRowCount = count($get_all_terms);
		$query = $wpdb->prepare(
			"SELECT COUNT(*) AS total 
			FROM {$wpdb->prefix}terms t 
			INNER JOIN {$wpdb->prefix}term_taxonomy tax 
			ON tax.term_id = t.term_id 
			WHERE tax.taxonomy = %s",
		'post_tag'
		);

		self::$export_instance->totalRowCount = (int) $wpdb->get_var($query);


		$offset = self::$export_instance->offset;
		$limit = self::$export_instance->limit;	

		if($eventExclusions['is_check'] == 'true'){
			$headers[] = 't.term_id'; //specific inclusion
			// filtered headers
			$wp_cat_fields_column_value = [
				'name','slug','description','parent','t.term_id'
			];
			$filtered_headers = array_intersect($headers, $wp_cat_fields_column_value);
			$selected_columns = implode(', ', $filtered_headers);
		}

		$query="SELECT term_id FROM {$wpdb->prefix}term_taxonomy where taxonomy='post_tag'";

		$offset_limit = " order by term_id asc limit $offset, $limit";
		$query_with_offset_limit = $query.$offset_limit;

		$result= $wpdb->get_col($query_with_offset_limit);
		$query1=array();
		foreach($result as $res=>$id){
			if (isset($selected_columns) && !empty($selected_columns)) {
				// Use the dynamically selected columns based on $headers
				$query1[]=$wpdb->get_results(" SELECT $selected_columns FROM {$wpdb->prefix}terms as t join {$wpdb->prefix}term_taxonomy as tx on t.term_id = tx.term_id where t.term_id = '$id'");
			} else {
				$query1[]=$wpdb->get_results(" SELECT t.name, t.slug, tx.description, tx.parent, t.term_id FROM {$wpdb->prefix}terms as t join {$wpdb->prefix}term_taxonomy as tx on t.term_id = tx.term_id where t.term_id = '$id'");
			}

		}
		$new=array();
		foreach($query1 as $qkey => $qval){		
			foreach($qval as $qid){
				$new[]=$qid;
			}

		}	
		if(!empty($new)) {
			foreach( $new as $termKey => $termValue ) {
				$termID = $termValue->term_id ?? '';
				$termName = $termValue->name ?? '';
				$termSlug = $termValue->slug ?? '';
				$termDesc = $termValue->description ?? '';
				self::$export_instance->data[$termID]['name'] = $termName;
				self::$export_instance->data[$termID]['slug'] = $termSlug;
				self::$export_instance->data[$termID]['description'] = $termDesc;
				self::$export_instance->data[$termID]['TERMID'] = $termID;

				$this->getPostsMetaDataBasedOnRecordId ($termID, $module, $optionalType);						

				self::$export_instance->getWPMLData($termID,$optionalType,$module);
				if(is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php') || is_plugin_active('polylang-wc/polylang-wc.php')){
					self::$export_instance->getPolylangData($termID,$optionalType,$module);
				}
				if(is_plugin_active('wordpress-seo/wp-seo.php') || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php')){
					$seo_yoast_taxonomies = get_option( 'wpseo_taxonomy_meta' );
					if ( isset( $seo_yoast_taxonomies['post_tag'] ) ) {
						self::$export_instance->data[ $termID ]['title'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_title'];
						self::$export_instance->data[ $termID ]['meta_desc'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_desc'];
						self::$export_instance->data[ $termID ]['canonical'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_canonical'];
						self::$export_instance->data[ $termID ]['bctitle'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_bctitle'];
						self::$export_instance->data[ $termID ]['meta-robots-noindex'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_noindex'];
						//self::$export_instance->data[ $termID ]['sitemap-include'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_sitemap_include'];
						self::$export_instance->data[ $termID ]['opengraph-title'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_opengraph-title'];
						self::$export_instance->data[ $termID ]['opengraph-description'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_opengraph-description'];
						self::$export_instance->data[ $termID ]['opengraph-image'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_opengraph-image'];
						self::$export_instance->data[ $termID ]['twitter-title'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_twitter-title'];
						self::$export_instance->data[ $termID ]['twitter-description'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_twitter-description'];
						self::$export_instance->data[ $termID ]['twitter-image'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_twitter-image'];
						self::$export_instance->data[ $termID ]['focus_keyword'] = $seo_yoast_taxonomies['post_tag'][$termID]['wpseo_focuskw'];	

						if(isset($seo_yoast_taxonomies['post_tag'][$termID]['wpseo_focuskeywords']) && !empty($seo_yoast_taxonomies['post_tag'][$termID]['wpseo_focuskeywords'])){

							$decode_value = json_decode($seo_yoast_taxonomies['post_tag'][$termID]['wpseo_focuskeywords'], true);
							if(is_array($decode_value)){
								$keywords = array_column($decode_value, 'keyword');
								self::$export_instance->data[ $termID ]['focuskeywords'] = !empty($keywords) ?  implode('|', $keywords) : '';
							}

						}

						if(isset($seo_yoast_taxonomies['post_tag'][$termID]['wpseo_keywordsynonyms']) && !empty($seo_yoast_taxonomies['post_tag'][$termID]['wpseo_keywordsynonyms'])){
							$decode_value1 = json_decode($seo_yoast_taxonomies['post_tag'][$termID]['wpseo_keywordsynonyms'], true);
							if (is_array($decode_value1)) {
								if (!empty($decode_value1)) {
									array_shift($decode_value1);
								}
								self::$export_instance->data[$termID]['keywordsynonyms'] = implode('|', $decode_value1);
							} else {
								self::$export_instance->data[$termID]['keywordsynonyms'] = '';
							}
						}
					}
				}
			}	
		}
		$result = self::$export_instance->finalDataToExport(self::$export_instance->data, $module);						
		if(is_plugin_active('advanced-custom-fields-pro/acf.php')){
			$result = self::$export_instance->convert_acfname_to_key($result,$optionalType,'pro',null);
		}
		elseif((is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('secure-custom-fields/secure-custom-fields.php')) && is_plugin_active('acf-repeater/acf-repeater.php')){
			$result = self::$export_instance->convert_acfname_to_key($result,$optionalType,'free');
		}
		if($is_filter == 'filter_action'){
			return $result;
		}

		if($mode == null)
			self::$export_instance->proceedExport($result);
		else
			return $result;	
	}	

	public function metabox_groupExport($fieldData,$postmeta_data,$id){	
		global $wpdb;		
		$customtable_flag = 0;				

		foreach($fieldData as $metagroupid => $eachgroup)
		{					
			if(array_key_exists('storage',$eachgroup) && isset($eachgroup['storage']->table)) 
			{

				$custom_meta_table = $eachgroup['storage']->table;						
				$customtable_flag = 1;

				$clonable = $eachgroup['clone'];
				$grpkey = $eachgroup['id'];					

				$result = $wpdb->query("SHOW COLUMNS FROM $custom_meta_table LIKE '$grpkey'");						

				if($result){											
					// $metavalue = $wpdb->get_var("select $grpkey from $custom_meta_table where ID = $id");					
					$metavalue = $wpdb->get_var("select `$grpkey` from $custom_meta_table where ID = $id");								
					if($metavalue) {

						if($eachgroup['type'] != 'group')
						{
							continue;									
						}
						else {
							//Group Fields
							$metavalue = unserialize($metavalue);
							$metagrp_fields = $eachgroup['fields'];	
							foreach($metagrp_fields as $index => $fdata){
								$sub_fdvalue = "";
								$fieldkey = $fdata['id'];
								$fieldtype = $fdata['type'];
								$is_multiple = isset($fdata['multiple']) ? $fdata['multiple'] : 0;												
								$subclone = $fdata['clone'];
								if($clonable){								
									$sub_fdvalue = $this->metabox_groupclone($fieldtype,$fieldkey,$metavalue,$is_multiple,$subclone,$id,$fdata);
								}
								else {
									if(is_array($metavalue) && array_key_exists($fieldkey,$metavalue)){		
										if($subclone)						
											$sub_fdvalue = $this->metabox_clonefieldsExport($fieldtype,$metavalue[$fieldkey],$is_multiple,$id,$fdata);						
										else
											$sub_fdvalue = $this->metabox_fieldsExport($fieldtype,$metavalue[$fieldkey],$is_multiple,$id,$fdata);						
									}	
								}
								self::$export_instance->data[$id][$fieldkey] = $sub_fdvalue;							
							}
						}
					}

				}
			}
		}			

		if(!$customtable_flag && isset($fieldData) && is_array($fieldData) && isset($postmeta_data->meta_key)&& array_key_exists($postmeta_data->meta_key, $fieldData)){

			$gkey = $postmeta_data->meta_key;	
			if($fieldData[$gkey]['type'] != 'group')
				return;				

			$metavalue = unserialize($postmeta_data->meta_value);				
			$subfields = $fieldData[$gkey]['fields'];
			$clonable = $fieldData[$gkey]['clone'];

			foreach($subfields as $subkey => $sub_fd){
				$sub_fdvalue = "";
				$fieldtype = $sub_fd['type'];
				$fieldkey = $sub_fd['id'];
				$is_multiple = isset($sub_fd['multiple']) ? $sub_fd['multiple'] : 0;												
				$subclone = $sub_fd['clone'];
				if($clonable){
					$sub_fdvalue = $this->metabox_groupclone($fieldtype,$fieldkey,$metavalue,$is_multiple,$subclone,$id,$sub_fd);
				}
				else {
					if(is_array($metavalue) && array_key_exists($fieldkey,$metavalue)){	
						if($subclone)
							$sub_fdvalue = $this->metabox_clonefieldsExport($fieldtype,$metavalue[$fieldkey],$is_multiple,$id,$sub_fd);											
						else
							$sub_fdvalue = $this->metabox_fieldsExport($fieldtype,$metavalue[$fieldkey],$is_multiple,$id,$sub_fd);						
					}	
				}				

				self::$export_instance->data[$id][$fieldkey] = $sub_fdvalue;

			}

		}		
	}

public function metabox_groupclone($fieldtype, $key, $metavalue, $is_multiple, $subclone, $id, $grpmetaData) {	

    $field_arr = [];

    // Ensure $metavalue is an array before looping
    if (!is_array($metavalue)) {
        $metavalue = maybe_unserialize($metavalue);
        if (!is_array($metavalue)) {
            $metavalue = [];
        }
    }

    foreach ($metavalue as $row => $field_data) {

        // Ensure $field_data is an array
        if (!is_array($field_data)) {
            $field_data = [];
        }

        if (!array_key_exists($key, $field_data)) {
            $field_data[$key] = '';
        }

        if (array_key_exists($key, $field_data)) {
            if ($subclone) {
                $field_arr[] = $this->metabox_clonefieldsExport($fieldtype, $field_data[$key], $is_multiple, $id, $grpmetaData);
            } else {
                $field_arr[] = $this->metabox_fieldsExport($fieldtype, $field_data[$key], $is_multiple, $id, $grpmetaData);
            }
        }
    }

    return !empty($field_arr) ? implode('|', $field_arr) : "";
}

	public function metabox_fieldsExport($fieldtype,$fieldvalue,$is_multiple,$id,$grpmetaData = null){										
		// if($fieldvalue){
		switch($fieldtype){
		case 'date':
		case 'datetime':
		{					
			$dateformat = $fieldtype == 'date' ? "Y-m-d" : "Y-m-d H:i:s";
			if(is_array($fieldvalue)){
				$fieldvalue = array_key_exists('formatted',$fieldvalue) ? $fieldvalue['formatted'] : "";
			}		

			break;
		}	
		case 'fieldset_text':
		{
			$fieldvalue = implode(',', array_values($fieldvalue));
			break;
		}
		//unsupported fields
		case 'background':
		case 'google_maps':
		case 'image_select':
		case 'key_value':
		case 'open_street_maps':
		case 'jquery_ui_slider':
		case 'sidebar':
		case 'divider':
		case 'heading':			
		case 'tab':
		{
			$fieldvalue = "";
			break;
		}
		case 'image_advanced':{
			global $wpdb;
			$get_metabox_file_url =array();
			if(is_array($fieldvalue)){
				foreach($fieldvalue as $sub_val){
					$get_metabox_file_url[] = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts WHERE ID = $sub_val AND post_type = 'attachment' ");	
				}
				$fieldvalue = implode(',',$get_metabox_file_url);
			}
			break;
		}
		case 'group':{																		
			$subfields = $grpmetaData['fields'];
			$clonable = $grpmetaData['clone']; //subgroup fields clone						
			foreach($subfields as $subkey => $sub_fd){
				$sub_fdvalue = "";
				$ntype = $sub_fd['type'];
				$nkey = $sub_fd['id'];
				$subclone = $sub_fd['clone'];

				$is_multiple = isset($sub_fd['multiple']) ? $sub_fd['multiple'] : 0;														
				if($clonable){
					$sub_fdvalue = $this->metabox_groupclone($ntype,$nkey,$fieldvalue,$is_multiple,$subclone,$id,$sub_fd);
				}
				else {
					if(array_key_exists($nkey,$fieldvalue)){	
						if($subclone)						
							$sub_fdvalue = $this->metabox_clonefieldsExport($ntype,$fieldvalue[$nkey],$is_multiple,$id,$sub_fd);						
						else											
							$sub_fdvalue = $this->metabox_fieldsExport($ntype,$fieldvalue[$nkey],$is_multiple,$id,$sub_fd);						
					}	
				}

				self::$export_instance->data[$id][$nkey] = $sub_fdvalue;								

			}

			return;																
		}
		case 'post':
		{
			global $wpdb;
			$fieldvalue = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $fieldvalue");
			break;
		}
		default: 
		{	
			//checkbox_list,select,select advanced			
			if(is_array($fieldvalue)){						
				$fieldvalue = implode(',',$fieldvalue);
			}									
			break;
		}
		}
		// }	
		return $fieldvalue;					
	}	

	public function metabox_clonefieldsExport($fieldtype,$fieldvalue,$is_multiple,$id,$grpmetaData = null){														
		if(!empty($fieldvalue)){			
			$get_metabox_file_url = array();
			foreach($fieldvalue as $row => $subdata)	{	
				switch($fieldtype){
				case 'date':
				case 'datetime':
				{						
					$dateformat = $fieldtype == 'date' ? "Y-m-d" : "Y-m-d H:i:s";
					if(is_array($subdata)){							
						if(array_key_exists('formatted',$fieldvalue)){
							$fvalue[$row] = $fieldvalue['formatted'];
						}
						else {
							if(array_key_exists($row,$subdata))
								$fvalue[$row] = $fieldvalue[$row];
						}
					}	
					else {
						$fvalue[$row] = $subdata;
					}				
					break;
				}			
				case 'fieldset_text':
				{
					if(isset($subdata) && is_array($subdata)){
						$fvalue[$row] = implode(',', array_values($subdata));
					}					
					break;
				}						
				//unsupported fields
				case 'background':
				case 'google_maps':
				case 'image_select':
				case 'key_value':
				case 'open_street_maps':
				case 'jquery_ui_slider':
				case 'sidebar':
				case 'divider':
				case 'heading':			
				case 'tab':
				{
					$fvalue = "";
					break;
				}
				case 'image_advanced':{
					global $wpdb;
					$get_metabox_file_url =array();
					if(is_array($subdata)){
						foreach($subdata as $sub_val){
							$get_metabox_file_url[] = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts WHERE ID = $sub_val AND post_type = 'attachment' ");	
						}
						$fvalue[$row] = implode(',',$get_metabox_file_url);
					}
					break;
				}	

				case 'group':{																		
					$subfields = $grpmetaData['fields'];
					$clonable = $grpmetaData['clone']; //subgroup fields clone						
					foreach($subfields as $subkey => $sub_fd){
						$sub_fdvalue = "";
						$ntype = $sub_fd['type'];
						$nkey = $sub_fd['id'];
						$subclone = $sub_fd['clone'];

						$is_multiple = isset($sub_fd['multiple']) ? $sub_fd['multiple'] : 0;														
						if($clonable){
							$sub_fdvalue = $this->metabox_groupclone($ntype,$nkey,$fieldvalue,$is_multiple,$subclone,$id,$sub_fd);
						}
						else {
							if(array_key_exists($nkey,$fieldvalue)){	
								if($subclone)						
									$sub_fdvalue = $this->metabox_clonefieldsExport($ntype,$fieldvalue[$nkey],$is_multiple,$id,$sub_fd);						
								else											
									$sub_fdvalue = $this->metabox_fieldsExport($ntype,$fieldvalue[$nkey],$is_multiple,$id,$sub_fd);						
							}	
						}				
						self::$export_instance->data[$id][$nkey] = $sub_fdvalue;								

					}

					return;																
				}

				default: 
				{	
					//checkbox_list,select,select advanced			
					if(is_array($subdata)){	
						$fvalue[$row] = implode(',',$subdata);
					}									
					else {
						$fvalue[$row] = $subdata;
					}
					break;
				}
				}
			}
		}	

		if(is_array($fvalue)){
			$final_data = implode('->',$fvalue);
		}
		else
			$final_data = $fvalue;

		return $final_data;					
	}

	public function metaboxFieldCloneExport($field_data,$fieldvalue,$id){		//Normal Fields clone Feature function[default table]
		$fieldkey  = $fieldvalue->meta_key;
		$fieldtype = $field_data[$fieldvalue->meta_key]['type'];		
		if($fieldtype == 'post' || $fieldtype == 'user' || $fieldtype == 'taxonomy'){
			$field_types = $field_data[$fieldvalue->meta_key]['field_type'];		
		}

		if(!empty($fieldvalue->meta_value)){			
			$fieldvalue = unserialize($fieldvalue->meta_value);
			foreach($fieldvalue as $row => $subdata)	{		
				switch($fieldtype){	
				case 'date':
				case 'datetime':
				{
					$dateformat = $fieldtype == 'date' ? "Y-m-d" : "Y-m-d H:i:s";
					if(is_numeric($subdata)){
						$fvalue[$row] = date($dateformat,$subdata);
					}
					else {
						$fvalue[$row] = $subdata;
					}
					break;
				}
				case 'fieldset_text':
				{
					if(is_array($subdata))
					{
						$fvalue[$row] = implode(',',array_values($subdata));
					}
					break;
				}
				case 'image_advanced':{
					global $wpdb;
					$get_metabox_file_url =array();
					if(is_array($subdata)){
						foreach($subdata as $sub_val){
							$get_metabox_file_url[] = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts WHERE ID = $sub_val AND post_type = 'attachment' ");	
						}
						$fvalue[$row] = implode(',',$get_metabox_file_url);
					}
					break;
				}		
				//unsupported fields
				case 'background':
				case 'google_maps':
				case 'image_select':
				case 'key_value':
				case 'open_street_maps':
				case 'jquery_ui_slider':
				case 'sidebar':
				case 'divider':
				case 'heading':			
				case 'tab':
				{
					$fvalue = "";
					break;
				}
				case 'post':
				{
					global $wpdb;
					$get_related_posts_details =array();

					if(is_array($subdata)){
						foreach($subdata as $sub_val){

							$get_related_posts_details[] = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $sub_val");
						}

						$fvalue[$row] = implode(',',$get_related_posts_details);
					}
					else{
						$posttitle_val=$wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $subdata");
						$fvalue[$row] = $posttitle_val;
					}
					break;
				}
				case 'user':
				{
					global $wpdb;
					$relatedusers =array();

					if(is_array($subdata)){
						foreach($subdata as $sub_val){
							$relatedusers[] = $wpdb->get_var("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = $sub_val");
						}
						$fvalue[$row] = implode(',',$relatedusers);
					}
					else{
						$posttitle_val=$wpdb->get_var("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = $subdata");
						$fvalue[$row] = $posttitle_val;
					}
					break;
				}
				case 'taxonomy_advanced':
				{
					global $wpdb;
					$get_related_terms =array();

					$related_terms = array();
					$term_array= explode(',',$subdata);
					foreach($term_array as $term_ids){
						$related_terms[] = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = $term_ids");
					}
					$fvalue[$row]= implode(',',$related_terms);
					break;
				}
				default: 
				{	
					//checkbox_list,select,select advanced			
					if(isset($subdata) && is_array($subdata)){	
						$fvalue[$row] = implode(',',$subdata);						
					}									
					else {						
						$fvalue[$row] = $subdata;
					}
					break;
				}
				}
			}
		}	

		if(is_array($fvalue)){
			$final_data = implode('|',$fvalue);
		}
		else
			$final_data = $fvalue;

		self::$export_instance->data[$id][$fieldkey] = $final_data;	
	}

	public function metabox_NormalFieldsExport($metabox_fields,$value,$id,$module){		
		global $wpdb;
		foreach($metabox_fields as $metagroupid => $eachgroup)
		{				
			if(array_key_exists('storage',$eachgroup) && isset($eachgroup['storage']->table)) 
			{						
				$custom_meta_table = $eachgroup['storage']->table;						
				$customtable_flag = 1;						
				$clonable = $eachgroup['clone'];
				$grpkey = $eachgroup['id'];					

				$result = $wpdb->query("SHOW COLUMNS FROM $custom_meta_table LIKE '$grpkey'");											
				if($result){											
					// $metavalue = $wpdb->get_var("select $grpkey from $custom_meta_table where ID = $id");						
					$metavalue = $wpdb->get_var("select `$grpkey` from $custom_meta_table where ID = $id");								
					if($metavalue) {

						if($eachgroup['type'] != 'group')
						{
							//Normal Fields
							if(is_serialized($metavalue)){
								$metavalue = unserialize($metavalue);
							}
							if($clonable){
								$this->customTableCloneFieldsExport($eachgroup['type'],$metavalue,$eachgroup,$id);
							}
							else {

								$this->customTableFieldsExport($eachgroup['type'],$metavalue,$eachgroup,$id);
							}													

						}

					}					
				}
			}
		}
		if(isset($value->meta_key) && array_key_exists($value->meta_key,$metabox_fields)){
			$get_metabox_fieldtype = $metabox_fields[$value->meta_key]['type'];
			$field_clone = $metabox_fields[$value->meta_key]['clone'];				

			if($field_clone){
				$this->metaboxFieldCloneExport($metabox_fields,$value,$id);
			}
			else {
				if($get_metabox_fieldtype == 'select' || $get_metabox_fieldtype == 'select_advanced' || $get_metabox_fieldtype == 'checkbox_list' || $get_metabox_fieldtype == 'text_list' || $get_metabox_fieldtype == 'file_advanced' || $get_metabox_fieldtype == 'image_advanced' || $get_metabox_fieldtype == 'autocomplete' || $get_metabox_fieldtype == 'image_upload'){

					$metabox_metakey = $value->meta_key;
					if($module == 'Users'){
						$get_metabox_values = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}usermeta WHERE meta_key = '$metabox_metakey' AND user_id = $id ", ARRAY_A);
					}else if($module == 'Categories' || $module == 'Taxonomies' || $module == 'Tags'){
						$get_metabox_values = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}termmeta WHERE meta_key = '$metabox_metakey' AND term_id = $id ", ARRAY_A);
					}else{	
						$get_metabox_values = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '$metabox_metakey' AND post_id = $id ", ARRAY_A);
					}
					$metabox_values = array_column($get_metabox_values, 'meta_value');					
					if($get_metabox_fieldtype == 'file_advanced' || $get_metabox_fieldtype == 'image_advanced' || $get_metabox_fieldtype == 'image_upload' ){
	$get_metabox_file_url = [];

	foreach($metabox_values as $metavalue){
		if (!empty($metavalue) && is_numeric($metavalue)) {
			$get_metabox_file_url[] = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT guid FROM {$wpdb->prefix}posts WHERE ID = %d AND post_type = 'attachment'",
					$metavalue
				)
			);
		}
	}

	$metabox_file_value = !empty($get_metabox_file_url) ? implode(',', $get_metabox_file_url) : '';
	self::$export_instance->data[$id][ $value->meta_key ] = $metabox_file_value;
}

					else{
						$metabox_value = !empty($metabox_values) ? implode(',', $metabox_values) : '';
						self::$export_instance->data[$id][ $value->meta_key ] = $metabox_value;
					}
				}

				elseif($get_metabox_fieldtype == 'fieldset_text'){
					$fieldset_values = unserialize($value->meta_value);
					$fieldset_value = !empty($fieldset_values) ? implode(',', array_values($fieldset_values)) : '';
					self::$export_instance->data[$id][ $value->meta_key ] = $fieldset_value;
				}
				elseif($get_metabox_fieldtype == 'date')	{
					$dateformat = "Y-m-d";
					if(is_numeric($value->meta_value))
						$date_value = date($dateformat,$value->meta_value);
					else
						$date_value = $value->meta_value;

					self::$export_instance->data[$id][ $value->meta_key ] = $date_value;	
				}
				elseif($get_metabox_fieldtype == 'autocomplete'){						
					self::$export_instance->data[$id][ $value->meta_key ] = !empty($metabox_values) ? implode(',',$metabox_values) : '';					
				}			
				elseif($get_metabox_fieldtype == 'post' || $get_metabox_fieldtype == 'taxonomy' || $get_metabox_fieldtype == 'user'){
					if($get_metabox_fieldtype == 'post'){
						$get_related_posts_details = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = $value->post_id AND meta_key = '$value->meta_key'");
						$relatedposts = array();     
						foreach($get_related_posts_details as $posts_details){
							$relatedposts[] = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $posts_details->meta_value ");
						}
						$get_metabox_titles = implode('|' , $relatedposts);
					}
					elseif($get_metabox_fieldtype == 'taxonomy' || $get_metabox_fieldtype == 'taxonomy_advanced'){
						$get_metabox_titles = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = $value->meta_value ");
					}
					elseif($get_metabox_fieldtype == 'user'){
						$get_related_user_details = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = $value->post_id AND meta_key = '$value->meta_key'");
						$relatedusers = array();     
						foreach($get_related_user_details as $users_details){
							$relatedusers[] = $wpdb->get_var("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = $users_details->meta_value ");
						}
						$get_metabox_titles = implode('|' , $relatedusers);
					}
					self::$export_instance->data[$id][ $value->meta_key ] = $get_metabox_titles;
				}

				elseif($get_metabox_fieldtype == 'image' || $get_metabox_fieldtype == 'file'){
	$upload_values = $value->meta_value;

	if (!empty($upload_values) && is_numeric($upload_values)) {
		$upload_value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT guid FROM {$wpdb->prefix}posts WHERE ID = %d AND post_type = 'attachment'",
				$upload_values
			)
		);
	} else {
		$upload_value = '';
	}

	self::$export_instance->data[$id][ $value->meta_key ] = $upload_value;
}

				else{
					self::$export_instance->data[$id][ $value->meta_key ] = $value->meta_value;
				}
			}
		}
	}


	public function customTableFieldsExport($fieldtype,$fieldvalue,$fieldData,$id){	
		$fieldkey = $fieldData['id'];
		global $wpdb;

		if(is_array($fieldvalue)){
			if($fieldtype == 'fieldset_text'){		
				$final_data = implode(',', array_values($fieldvalue));				
			}	
			elseif($fieldtype == 'post'){
				$get_related_posts_details =array();
				foreach($fieldvalue as $field_val){

					$get_related_posts_details[] = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $field_val");
				}
				$final_data = implode(',', $get_related_posts_details);				
			}
			elseif($fieldtype== 'user'){
				$relatedusers = array();
				foreach($fieldvalue as $field_val){
					$relatedusers[] = $wpdb->get_var("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = $field_val");
				}
				$final_data = implode(',', $relatedusers);				
			}
			elseif($fieldtype== 'taxonomy_advanced'){
				$related_terms = array();
				//$term_array= explode(',',$fieldvalue);
				foreach($fieldvalue as $term_ids){
					$related_terms[] = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = $term_ids");
				}		
				$final_data = implode(',', $related_terms);				
			}
			else {
				$get_metabox_file_url = [];
				foreach($fieldvalue as $metavalue){
					$get_metabox_file_url[] = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts WHERE ID = $metavalue AND post_type = 'attachment' ");
				}
				$final_data = implode(',',$get_metabox_file_url);
			}
		}
		else{
			if($fieldtype == 'date')	{
				$dateformat = "Y-m-d";
				if(is_numeric($fieldvalue))
					$final_data = date($dateformat,$fieldvalue);
				else
					$final_data = $fieldvalue;
			}
			elseif($fieldtype== 'user'){
				$final_data=$wpdb->get_var("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = $fieldvalue");
			}
			elseif($fieldtype== 'taxonomy_advanced'){
				$related_terms = array();
				$term_array= explode(',',$fieldvalue);
				foreach($term_array as $term_ids){
					$related_terms[] = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = $term_ids");
				}		
				$final_data = implode(',', $related_terms);	
			}
			elseif($fieldtype == 'post'){
				$finaldata = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $fieldvalue");
			}
			elseif($fieldtype == 'datetime'){
				$dateformat = "Y-m-d H:i:s";
				if(is_numeric($fieldvalue))
					$final_data = date($dateformat,$fieldvalue);
				else
					$final_data = $fieldvalue;
			}
			else {
				$final_data = $fieldvalue;
			}
		}
		self::$export_instance->data[$id][$fieldkey] = $final_data;		
	}

	public function customTableCloneFieldsExport($fieldtype,$fieldvalue,$fieldData,$id){
		$fieldkey = $fieldData['id'];
		global $wpdb;
		foreach($fieldvalue as $row => $fvalue){
			if(is_array($fvalue)){
				if($fieldtype == 'fieldset_text'){		
					$field_arr[$row] = implode(',', array_values($fvalue));				
				}			
				elseif($fieldtype == 'post'){
					$get_related_posts_details =array();
					foreach($fvalue as $field_val){

						$get_related_posts_details[] = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $field_val");
					}
					$field_arr[$row] = implode(',', $get_related_posts_details);				
				}
				elseif($fieldtype== 'user'){
					$relatedusers = array();
					foreach($fvalue as $field_val){
						$relatedusers[] = $wpdb->get_var("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = $field_val");
					}
					$field_arr[$row] = implode(',', $relatedusers);				
				}
				elseif($fieldtype== 'taxonomy_advanced'){
					$related_terms = array();
					$term_array= explode(',',$fvalue);
					foreach($term_array as $term_ids){
						$related_terms[] = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = $term_ids");
					}		
					$field_arr[$row] = implode(',', $related_terms);				
				}
				else {
					$field_arr[$row] = implode(',',$fvalue);
				}

			}
			else{
				if($fieldtype == 'date')	{
					$dateformat = "Y-m-d";
					if(is_numeric($fvalue))
						$field_arr[$row] = date($dateformat,$fvalue);
					else
						$field_arr[$row] = $fvalue;
				}
				elseif($fieldtype == 'datetime'){
					$dateformat = "Y-m-d H:i:s";
					if(is_numeric($fvalue))
						$field_arr[$row] = date($dateformat,$fvalue);
					else
						$field_arr[$row] = $fvalue;
				}
				elseif($fieldtype== 'user'){
					$field_arr[$row]=$wpdb->get_var("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = $fvalue");
				}
				elseif($fieldtype== 'taxonomy_advanced'){
					$related_terms = array();
					$term_array= explode(',',$fvalue);
					foreach($term_array as $term_ids){
						$related_terms[] = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = $term_ids");
					}		
					$field_arr[$row]= implode(',', $related_terms);	
				}
				elseif($fieldtype == 'post'){
					$field_arr[$row] = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $fvalue");
				}
				else {
					$field_arr[$row] = $fvalue;
				}		
			}
		}
		$final_data = implode('|',$field_arr);
		self::$export_instance->data[$id][$fieldkey] = $final_data;	
	}
	public function get_pods_new_fields($id, $value,$optionalType,$module, $pods_type){
		global $wpdb;
		$taxonomies = get_taxonomies();

		if(isset($value->meta_key) && in_array($value->meta_key , $pods_type)){
			foreach($pods_type as $pods){
				$pods_id =  $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts where post_title='$pods'");	
				foreach($pods_id as $pod_id){
					$pods_id_value=$pod_id->ID;
					$pods_types =  $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}postmeta where post_id='$pods_id_value' and meta_key='type'");	
					foreach($pods_types as $pod_type){
						$ptype[]=$pod_type->meta_value;	
					}	
				}
			}
			$podsFields = array();
			$post_id = $wpdb->get_results($wpdb->prepare("select ID from {$wpdb->prefix}posts where post_name= %s and post_type = %s", $optionalType, '_pods_pod'));
			if($optionalType == 'images'){
				$post_id = $wpdb->get_results($wpdb->prepare("select ID from {$wpdb->prefix}posts where post_name= %s and post_type = %s", 'media', '_pods_pod'));	
			}
			if($optionalType == 'comments'){
				$post_id = $wpdb->get_results($wpdb->prepare("select ID from {$wpdb->prefix}posts where post_name= %s and post_type = %s", 'comment', '_pods_pod'));
			}	
			if(!empty($post_id)) {
				$lastId  = $post_id[0]->ID;
				$get_pods_fields = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_name FROM {$wpdb->prefix}posts where post_parent = %d AND post_type = %s", $lastId, '_pods_field' ) );
				if ( ! empty( $get_pods_fields ) ) :
					foreach ( $get_pods_fields as $pods_field ) {
						$get_pods_types = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}postmeta where post_id = %d AND meta_key = %s", $pods_field->ID, 'type' ) );
						$get_pods_object = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}postmeta where post_id = %d AND meta_key = %s", $pods_field->ID, 'pick_object' ) );
						$podsFields["PODS"][ $pods_field->post_name ]['label'] = $pods_field->post_name;
						$podsFields["PODS"][ $pods_field->post_name ]['type']  = $get_pods_types[0]->meta_value;
						$this->image =array();
						if($podsFields["PODS"][ $pods_field->post_name ]['type'] == 'file' && ($pods_field->post_name == $value->meta_key)){

							$attachment = $this->getAttachment($value->meta_value);
							// if (!in_array($attachment, $this->image)) {
							// 	$this->image[] = $attachment;
							// }
							$this->image[] = $attachment;
							$attach1=$this->image;
							$attach1 = array_filter($attach1);
							$attach1 = !empty($attach1) ? implode('|',$attach1) : '';
							if($value->meta_key == $pods_field->post_name){
								if(!empty(self::$export_instance->data[$id][$value->meta_key])){
									self::$export_instance->data[$id][$value->meta_key] = self::$export_instance->data[$id][$value->meta_key].'|'.$attach1;	
								}
								else{
									self::$export_instance->data[$id][$value->meta_key] = $attach1;
								}

							}
							$getid=$wpdb->get_results("select ID from {$wpdb->prefix}posts where guid= '$attach1'" ,ARRAY_A);
							foreach($getid as $getkey => $getval){
								$ids=$getval['ID'];
								$pods_caption=$wpdb->get_results("select post_excerpt from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
								$pods_description=$wpdb->get_results("select post_content from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
								$pods_title=$wpdb->get_results("select post_title from {$wpdb->prefix}posts where ID= '$ids'" ,ARRAY_A);
								$pods_alt_text=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attachment_image_alt' AND post_id='$ids'" ,ARRAY_A);
								$pods_filename=$wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where meta_key= '_wp_attached_file' AND post_id='$ids'" ,ARRAY_A);
								$filename=$pods_filename[0]['meta_value'];
								$file_names=explode('/', $filename);
								$file_name= end($file_names);
								$pods_cap=$pods_caption[0]['post_excerpt'];
								$podscaption= $pods_cap;
								$podsdescription = $pods_description[0]['post_content'];

								$podsalttext = !empty($pods_alt_text) && isset($pods_alt_text[0]['meta_value']) ? $pods_alt_text[0]['meta_value'] : '';
								$podsfilename = $file_name;

								$podstitle = $pods_title[0]['post_title'];
								if(!empty(self::$export_instance->data[$id]['pods_title'])){
									self::$export_instance->data[$id]['pods_title'] = self::$export_instance->data[$id]['pods_title'].'|'.$podstitle;	
								}
								if(empty(self::$export_instance->data[$id]['pods_title'])){
									self::$export_instance->data[$id]['pods_title'] = isset($podstitle)?$podstitle:'';
								}

								if(!empty(self::$export_instance->data[$id]['pods_caption'])){
									self::$export_instance->data[$id]['pods_caption'] = self::$export_instance->data[$id]['pods_caption'].'|'.$podscaption;
								}
								if(empty(self::$export_instance->data[$id]['pods_caption'])){
									self::$export_instance->data[$id]['pods_caption'] = isset($podscaption)?$podscaption:' ';
								}
								if(!empty(self::$export_instance->data[$id]['pods_description'])){
									self::$export_instance->data[$id]['pods_description'] = self::$export_instance->data[$id]['pods_description'].'|'.$podsdescription;
								}
								if(empty(self::$export_instance->data[$id]['pods_description'])){
									self::$export_instance->data[$id]['pods_description'] = isset($podsdescription)?$podsdescription:" ";
								}
								if(!empty(self::$export_instance->data[$id]['pods_alt_text'])){
									self::$export_instance->data[$id]['pods_alt_text'] = self::$export_instance->data[$id]['pods_alt_text'].'|'.$podsalttext;
								}
								if(empty(self::$export_instance->data[$id]['pods_alt_text'])){
									self::$export_instance->data[$id]['pods_alt_text'] = isset($podsalttext)?$podsalttext:' ';
								}
								if(!empty(self::$export_instance->data[$id]['pods_file_name'])){
									self::$export_instance->data[$id]['pods_file_name'] = self::$export_instance->data[$id]['pods_file_name'].'|'.$podsfilename;
								}
								if(empty(self::$export_instance->data[$id]['pods_file_name'])){
									self::$export_instance->data[$id]['pods_file_name'] = isset($podsfilename)?$podsfilename:'';
								}
							}
						}
						if(isset($get_pods_object[0]->meta_value)){
							$podsFields["PODS"][ $pods_field->post_name ]['pick_object']=$get_pods_object[0]->meta_value;
						}
						if($podsFields["PODS"][ $pods_field->post_name ]['type'] == 'pick' && ($pods_field->post_name == $value->meta_key)){
							$get_pods_objecttype = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}postmeta where post_id = %d AND meta_key = %s", $pods_field->ID, 'pick_format_type' ) );
							$podsFields["PODS"][ $pods_field->post_name ]['pick_objecttype']=$get_pods_objecttype[0]->meta_value;
						}
						if(!isset(self::$export_instance->data[$id][$value->meta_key]) && ($pods_field->post_name == $value->meta_key)){
							if(in_array($optionalType , $taxonomies)){
								$pods_file_data = get_term_meta($id,$value->meta_key);
							}else{
								$pods_file_data = get_post_meta($id,$value->meta_key);	
							}	
							$pods_value = '';
							foreach($pods_file_data as $pods_file_value){
								if(!empty($pods_file_value)){
									if(is_array($pods_file_value)){
										$pods_file_value['post_type']=isset($pods_file_value['post_type'])?$pods_file_value['post_type']:'';
										$posts_type=$pods_file_value['post_type'];
										if($posts_type=='attachment'){
											$pods_value .= $pods_file_value['guid'] . ',';
										}
										elseif($posts_type!=='attachment'){
											$pods_file_value['guid']=isset($pods_file_value['guid'])?$pods_file_value['guid']:'';
											$p_guid=$pods_file_value['guid'];
											$pod_tit =  $wpdb->get_results("SELECT post_title FROM {$wpdb->prefix}posts where guid='$p_guid'");	
											if(!empty($pod_tit)){
												foreach($pod_tit as $pods_title){
													$pods_title_value=$pods_title->post_title;
													$pods_value .= $pods_title_value . ',';
												}
											}
											else{
												$podstaxval = $pods_file_value['name'];
												$pods_value .= $podstaxval. ',';
											}
										}
										// if(empty($pods_value)){
										// 	$podstaxval = $pods_file_value['name'];
										// 	  $pods_value .= $podstaxval. ',';
										// }

									}else{

										$pods_value .= $pods_file_value . ',';

									}
								}	
							}

							self::$export_instance->data[$id][$value->meta_key] = rtrim($pods_value , ',');		
						}
					}

				foreach ( $get_pods_fields as $pods_field ) {
					$podsFields["PODS"][$pods_field->post_name]['pick_object']=isset($podsFields["PODS"][$pods_field->post_name]['pick_object'])?$podsFields["PODS"][$pods_field->post_name]['pick_object']:'';
					$podsFields["PODS"][$pods_field->post_name]['pick_objecttype'] = isset($podsFields["PODS"][$pods_field->post_name]['pick_objecttype'])?$podsFields["PODS"][$pods_field->post_name]['pick_objecttype']:'';

					$pick_obj=$podsFields["PODS"][$pods_field->post_name]['pick_object'];
					$pick_objtype = $podsFields["PODS"][$pods_field->post_name]['pick_objecttype'];

					$pick_lable = $podsFields["PODS"][$pods_field->post_name]['label'];
					if($pick_obj=='user'){
						if($pick_lable == $value->meta_key){ 
							if($pick_objtype == 'multi'){
								$val='_pods_'.$pick_lable;
								$get_pods_type = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}postmeta where post_id = %d AND meta_key = %s", $id, $val ) );
								$serialize_value=unserialize($get_pods_type[0]->meta_value);
								foreach($serialize_value as $key=>$unser_value){
									$multi_value .= $unser_value.',';

								}

								self::$export_instance->data[$id][$pods_field->post_name] =rtrim($multi_value,',');
							}
							else{	
								self::$export_instance->data[$id][$pods_field->post_name] =$value->meta_value;
							}
						}

					}
				}

				endif;
			}
		}

	}
}

