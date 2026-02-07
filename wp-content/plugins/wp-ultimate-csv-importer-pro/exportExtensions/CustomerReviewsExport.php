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
 * Class CustomerReviewExport
 * @package Smackcoders\WCSV
 */

class CustomerReviewExport {

	protected static $instance = null,$mapping_instance,$export_handler,$export_instance;
	public $totalRowCount;
	public $plugin;	
	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
			CustomerReviewExport::$export_instance = ExportExtension::getInstance();
		}
		return self::$instance;
	}

	/**
	 * CustomerReviewExport constructor.
	 */
	public function __construct() {
		$this->plugin = Plugin::getInstance();
	}

	/**
	 * Fetch Customer Reviews
	 * 
	 * @param string $module         The module for which customer reviews are being fetched.
	 * @param string|null $optionalType Optional type filter for reviews.
	 * @param array $conditions      Conditions to filter the reviews.
	 * @param int $offset            The offset for pagination.
	 * @param int $limit             The limit for pagination.
	 * @param bool $is_filter        Indicates if filters are to be applied.
	 * @param array $headers         Headers for the request.
	 * @param string|null $mode      Mode of operation (optional).
	 * @param array|null $eventExclusions Categories or events to exclude (optional).
	 * 
	 * @return array
	 */
	public function FetchCustomerReviews($module, $optionalType, $conditions, $offset, $limit, $is_filter, $headers, $mode = null, $eventExclusions = null) {

		global $wpdb;
		$get_customer_reviews = "select DISTINCT ID from {$wpdb->prefix}posts";
		$get_customer_reviews .= " where post_type = '$optionalType'";

		/**
		 * Check for specific status
		 */

		if($conditions['specific_status']['status'] == 'true') {
			if(isset($conditions['specific_status']['status']) && $conditions['specific_status']['status'] == 'All') {
				$get_customer_reviews .= " and post_status in ('publish','draft','future','private','pending')";
			} else if(isset($conditions['specific_status']['status']) && $conditions['specific_status']['status'] == 'Publish' || $conditions['specific_status']['status'] == 'Sticky') {
				$get_customer_reviews .= " and post_status in ('publish')";
			} else if(isset($conditions['specific_status']['status']) && $conditions['specific_status']['status'] == 'Draft') {
				$get_customer_reviews .= " and post_status in ('draft')";
			} else if(isset($conditions['specific_status']['status']) && $conditions['specific_status']['status'] == 'Scheduled') {
				$get_customer_reviews .= " and post_status in ('future')";
			} else if(isset($conditions['specific_status']['status']) && $conditions['specific_status']['status'] == 'Private') {
				$get_customer_reviews .= " and post_status in ('private')";
			} else if(isset($conditions['specific_status']['status']) && $conditions['specific_status']['status'] == 'Pending') {
				$get_customer_reviews .= " and post_status in ('pending')";
			} else if(isset($conditions['specific_status']['status']) && $conditions['specific_status']['status'] == 'Protected') {
				$get_customer_reviews .= " and post_status in ('publish') and post_password != ''";
			}
		} else {
			$get_customer_reviews .= " and post_status in ('publish','draft','future','private','pending')";
		}

		/**
		 * Check for specific authors
		 */

		if($conditions['specific_authors']['is_check'] == 'true') {
			if(isset($conditions['specific_authors']['author']) && $conditions['specific_authors']['author'] != 0) {
				$get_customer_reviews .= " and c.comment_author_email = {$conditions['specific_authors']['author']}";
			}
		}
		$get_total_row_count = $wpdb->get_col($get_customer_reviews);
		CustomerReviewExport::$export_instance->totalRowCount = count($get_total_row_count);
		$offset_limit = " order by ID asc limit $offset, $limit";
		$query_with_offset_limit = $get_customer_reviews . $offset_limit;
		$result = $wpdb->get_col($query_with_offset_limit);
		if(!empty($result)) {
			foreach($result as $reviewId) {

				/**
				 * Review Core Information
				 */

				//$query_for_reviews = $wpdb->prepare("SELECT wp.* FROM {$wpdb->prefix}posts wp where ID=%d", $reviewId);
				$query_for_reviews = $wpdb->prepare("SELECT ID,post_status,post_content,post_date FROM {$wpdb->prefix}posts wp where ID=%d", $reviewId);
				$reviewDetails = $wpdb->get_results($query_for_reviews);
				if (!empty($reviewDetails)) {
					foreach ($reviewDetails as $posts) {
						foreach ($posts as $post_key => $post_value) {
							$post_key = CustomerReviewExport::$export_instance->change_fieldname_depends_on_post_type('wpcr3_review', $post_key);
							if ($post_key == 'post_status' || $post_key == 'review_id' || $post_key == 'date_time' || $post_key == 'review_text' || $post_key == 'status') {
								if (is_sticky($reviewId)) {
									CustomerReviewExport::$export_instance->data[$reviewId][$post_key] = 'Sticky';
									$post_status = 'Sticky';
								} else {
									CustomerReviewExport::$export_instance->data[$reviewId][$post_key] = $post_value;
									$post_status = $post_value;
								}
							}
							if ($post_key == 'post_password') {
								if ($post_value) {
									CustomerReviewExport::$export_instance->data[$reviewId]['post_status'] = "{" . $post_value . "}";
								} else {
									CustomerReviewExport::$export_instance->data[$reviewId]['post_status'] = isset($post_status) ? $post_status : "";
								}
							}
						}
					}
				}
				/**
				 * Review Meta Information
				 */
				if ($eventExclusions['is_check'] == 'true') {		
					// Filtered headers
					$wp_customer_fields_column_value = [
						'review_post', 'review_name', 'review_email', 'review_website', 
						'review_title', 'review_rating', 'review_admin_response'
					];
					$filtered_headers = array_intersect($headers, $wp_customer_fields_column_value);
					if (!empty($filtered_headers)) {
						// Add prefix 'wpcr3_' to each filtered header
						$prefixed_headers = array_map(function ($header) {
							return 'wpcr3_' . $header;
						}, $filtered_headers);
						// Prepare placeholders for the query
						$placeholders = implode(', ', array_fill(0, count($prefixed_headers), '%s'));
						// Execute the query
						$query_for_review_meta = $wpdb->prepare(
							"SELECT post_id, meta_key, meta_value 
							 FROM {$wpdb->prefix}posts wp 
							 JOIN {$wpdb->prefix}postmeta wpm 
							 ON wpm.post_id = wp.ID 
							 WHERE meta_key IN ($placeholders) 
							 AND ID = %d",
							array_merge($prefixed_headers, [$reviewId])
						);
						$reviewMetaDetails = $wpdb->get_results($query_for_review_meta);
					} else {
						$reviewMetaDetails = [];
					}
				}
				else {
					$query_for_review_meta = $wpdb->prepare(
						"SELECT post_id, meta_key, meta_value 
						 FROM {$wpdb->prefix}posts wp 
						 JOIN {$wpdb->prefix}postmeta wpm 
						 ON wpm.post_id = wp.ID 
						 WHERE meta_key NOT IN (%s, %s) 
						 AND ID = %d",
						'_edit_lock', '_edit_last', $reviewId
					);
					$reviewMetaDetails = $wpdb->get_results($query_for_review_meta);
				}
				
				if(!empty($reviewMetaDetails)) :
					foreach($reviewMetaDetails as $key => $value) :
						if ($value->meta_key == '_thumbnail_id') {
							$attachment_file = null;
							$get_attachment = $wpdb->prepare("select guid from {$wpdb->prefix}posts where ID = %d AND post_type = %s", $value->meta_value, 'attachment');
							$attachment = $wpdb->get_results($get_attachment);
							$attachment_file = $attachment[0]->guid;
							CustomerReviewExport::$export_instance->data[$reviewId][$value->meta_key] = '';
							$value->meta_key = 'featured_image';
							CustomerReviewExport::$export_instance->data[$reviewId][$value->meta_key] = $attachment_file;
						} else {
							CustomerReviewExport::$export_instance->data[$reviewId][$value->meta_key] = $value->meta_value;
						}
				endforeach;
				endif;

				/**
				 * Prepare the headers
				 */

				// if(!empty($headers)) {
				// 	foreach($headers as $hKey) {
				// 		if(!in_array($hKey, CustomerReviewExport::$export_instance->headers)) {
				// 			CustomerReviewExport::$export_instance->headers[] = $hKey;

				// 		}
				// 	}
				// }
			}
		}
		$result = CustomerReviewExport::$export_instance->finalDataToExport(CustomerReviewExport::$export_instance->data);
		if(is_plugin_active('advanced-custom-fields-pro/acf.php')){
			$result = CustomerReviewExport::$export_instance->convert_acfname_to_key($result,$optionalType,'pro');
		}
		elseif((is_plugin_active('advanced-custom-fields/acf.php')|| is_plugin_active('secure-custom-fields/secure-custom-fields.php')) && is_plugin_active('acf-repeater/acf-repeater.php')){
			$result = CustomerReviewExport::$export_instance->convert_acfname_to_key($result,$optionalType,'free');
		}
		
		if($is_filter == 'filter_action'){
			return $result;
		}

		if($mode == null)
			CustomerReviewExport::$export_instance->proceedExport($result);
		else
			return $result;
	
			return $result;
	}
}
