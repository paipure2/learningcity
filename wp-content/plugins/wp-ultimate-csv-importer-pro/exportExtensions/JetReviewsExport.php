<?php

/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;

if (! defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Class JetReviewsExport
 * @package Smackcoders\WCSV
 */
class JetReviewsExport
{
    protected static $instance = null, $mapping_instance, $export_handler, $export_instance, $post_export;
    public $totalRowCount;
    public $plugin;

    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
            JetReviewsExport::$export_instance = ExportExtension::getInstance();
            JetReviewsExport::$post_export = PostExport::getInstance();
        }
        return self::$instance;
    }

    /**
     * JetReviewsExport constructor.
     */
    public function __construct()
    {
        $this->plugin = Plugin::getInstance();
    }


    	/**
	 * Fetch Jet reviewss Data
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
	public function FetchJetReviewsData($module, $optionalType, $conditions, $offset, $limit, $is_filter, $headers, $mode = null, $eventExclusions = null) {

        global $wpdb;
        $review_ids = JetReviewsExport::$post_export->getRecordsBasedOnPostTypes($module, $optionalType, $conditions,$offset,$limit,'','');

        /** filtered headers if choose specific inclusion */

        if($eventExclusions['is_check'] == 'true'){
			$wp_review_fields_column_value = [
				'source','post_id','post_type','author','date','title','content','type_slug','rating_data','rating','likes','dislikes','pinned','approved'
			];
			$filtered_headers = array_intersect($headers, $wp_review_fields_column_value);
			$selected_columns = implode(', ', $filtered_headers);
		}
        /** Export based on review ids */
        if(!empty($review_ids)){
            foreach ($review_ids as $id) {
                if(!empty($selected_columns) && isset($selected_columns)){
                    $review = $wpdb->get_row(
                        $wpdb->prepare("SELECT $selected_columns FROM {$wpdb->prefix}jet_reviews WHERE id = %d", $id),
                        ARRAY_A
            
                    );
                }else{
                    $review = $wpdb->get_row(
                        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}jet_reviews WHERE id = %d", $id),
                        ARRAY_A
            
                    );
                }
                if (!empty($review)) {
                JetReviewsExport::$export_instance->data[$id]['ID'] = $review['id'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['post_id'] = $review['post_id'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['source'] = $review['source'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['post_type'] = $review['post_type'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['type_slug'] = $review['type_slug'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['author'] = $review['author'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['date'] = date('Y-m-d H:i:s', strtotime($review['date'])) ?? '';
                JetReviewsExport::$export_instance->data[$id]['title'] = $review['title'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['content'] = $review['content'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['rating_data'] = $review['rating_data'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['rating'] = $review['rating'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['likes'] = $review['likes'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['dislikes'] = $review['dislikes'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['approved'] = $review['approved'] ?? '';
                JetReviewsExport::$export_instance->data[$id]['pinned'] = $review['pinned'] ?? '';
                }    
            }
            $results = JetReviewsExport::$export_instance->finalDataToExport(JetReviewsExport::$export_instance->data);
            if($is_filter == 'filter_action'){
                return $results;
            }
    
            if($mode == null)
            JetReviewsExport::$export_instance->proceedExport($results);
            else
                return $results;
        }
    }
}