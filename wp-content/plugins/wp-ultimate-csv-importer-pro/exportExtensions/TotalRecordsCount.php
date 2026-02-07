<?php

/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;

use Smackcoders\WCSV\WC_Coupon;

if (! defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Class TotalRecordsCount
 * @package Smackcoders\WCSV
 */
class TotalRecordsCount
{

    protected static $instance = null, $mapping_instance, $export_handler, $export_instance, $post_export;
    public $totalRowCount;
    public $plugin;

    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
            TotalRecordsCount::$export_instance = ExportExtension::getInstance();
            TotalRecordsCount::$post_export = PostExport::getInstance();
        }
        return self::$instance;
    }

    /**
     * TotalRecordsCount constructor.
     */
    public function __construct()
    {
        $this->plugin = Plugin::getInstance();
    }

    public function storeTotalCount($module, $optionalType, $category_export = null, $post_title_export = null, $conditions = null)
    {
        global $wpdb;

        if (!empty($category_export)) {
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
                    // $placeholders = implode(',', array_fill(0, count($term_taxo_ids), '%d'));

                    $placeholders = implode(',', array_fill(0, count($term_taxo_ids), '%d'));

                    $params = array_merge($term_taxo_ids, [$optionalType]);

                    $query = $wpdb->prepare(
                        "SELECT COUNT(p.ID) FROM {$wpdb->prefix}posts p 
						 INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
						 WHERE tr.term_taxonomy_id IN ($placeholders) 
						 AND p.post_type = %s 
						 AND p.post_status != 'trash'",
                        ...$params
                    );
                    $totalCount = $wpdb->get_var($query);
                    // Store in options table
                    update_option('advancedFilter_export_total_count', $totalCount);
                    return $totalCount;
                }
            }
        } else if ($post_title_export) {
            $post_title_export = trim($post_title_export); // Trim initial whitespace
            if ($optionalType == 'posts') {
                $optionalType = 'post';
            }

            // Split and trim post titles
            $post_titles = array_map('trim', explode(',', $post_title_export));
            if (!empty($post_titles)) {
                $placeholders = implode(',', array_fill(0, count($post_titles), '%s'));

                // Merge parameters into an array
                $params = array_merge($post_titles, [$optionalType]);

                // Prepare and execute the query to count posts with specific titles
                $query = $wpdb->prepare(
                    "SELECT COUNT(ID) FROM {$wpdb->prefix}posts 
                    WHERE post_title IN ($placeholders) 
                    AND post_type = %s 
                    AND post_status != 'trash'",
                    ...$params
                );
                $totalCount = $wpdb->get_var($query); // Fetch the count of matching posts
                // Store in options table
                update_option('advancedFilter_export_total_count', $totalCount);
                return $totalCount;
            }
        } else if ($module == 'product') {
            $product_statuses = array('publish', 'draft', 'future', 'private', 'pending');
            $args = array(
                'post_type'      => 'product',
                'post_status'    => $product_statuses,
                'posts_per_page' => -1, // Get all products
                'fields'         => 'ids', // Retrieve only IDs for counting
            );

            // Filter by specific post ID
            if (!empty($conditions['specific_post_id']['is_check']) && $conditions['specific_post_id']['is_check'] == 'true') {
                $prod_ids = explode(',', $conditions['specific_post_id']['post_id']);
                $args['post__in'] = array_map('intval', $prod_ids); // Ensure IDs are integers
            }

            // Filter by specific date range
            if (!empty($conditions['specific_period']['is_check'])) {
                if(empty($conditions['specific_period']['to']) && !empty($conditions['specific_period']['from'])){
                    $conditions['specific_period']['to'] = $conditions['specific_period']['from'];
                }if(empty($conditions['specific_period']['from']) && !empty($conditions['specific_period']['to'])){
                    $conditions['specific_period']['from'] = $conditions['specific_period']['to'];
                }
                if (!empty($conditions['specific_period']['from']) && !empty($conditions['specific_period']['to'])) {
                    $args['date_query'] = array(
                        array(
                            'after'     => $conditions['specific_period']['from'],
                            'before'    => $conditions['specific_period']['to'],
                            'inclusive' => true,
                        ),
                    );
                }
            }
            // Filter by specific status
            if (!empty($conditions['specific_status']['status'])) {
                $args['post_status'] = $conditions['specific_status']['status']; // Example: 'publish', 'draft'
            }
            if (is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php') && !empty($conditions['specific_lang_code']['is_check']) && !empty($conditions['specific_lang_code']['lang_code'])) {
                $lang_code = $conditions['specific_lang_code']['lang_code'];
                $args['suppress_filters'] = false; // Required for WPML filters to work
                $args['meta_query'][] = [
                    'key'     => '_icl_lang',
                    'value'   => $lang_code,
                    'compare' => '='
                ];
            }
            // Fetch products based on filters
            $query = new \WP_Query($args);
            $totalCount = $query->found_posts; // Fetch the count of matching posts
            // Store in options table
            update_option('advancedFilter_export_total_count', $totalCount);
            return $totalCount;
        } else if ($module == 'shop_order') {
            global $wpdb;
            $order_statuses = array('wc-completed', 'wc-cancelled', 'wc-on-hold', 'wc-processing', 'wc-pending', 'wc-refunded');
            $orders = wc_get_orders(array('status' => $order_statuses, 'numberposts' => 1, 'orderby' => 'date', 'order' => 'ASC'));
            $get_post_ids = array();
            foreach ($orders as $my_orders) {
                $get_post_ids[] = $my_orders->get_id();
            }
            foreach ($get_post_ids as $ids) {
                $module = $wpdb->get_var("SELECT post_type FROM {$wpdb->prefix}posts where id=$ids");
            }

            if ($module == 'shop_order_placehold') {
                $query = "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->prefix}posts AS p 
                          INNER JOIN {$wpdb->prefix}wc_orders AS wc ON p.ID = wc.id 
                          WHERE p.post_type = '$module'";

                if (!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
                    if (!empty($conditions['specific_status']['status'])) {
                        $status = esc_sql($conditions['specific_status']['status']);
                        $query .= " AND wc.status = '$status'";
                    }

                    if ($conditions['specific_period']['from'] == $conditions['specific_period']['to']) {
                        $query .= " AND DATE(p.post_date) = '" . esc_sql($conditions['specific_period']['from']) . "'";
                    } else {
                        $query .= " AND p.post_date BETWEEN '" . esc_sql($conditions['specific_period']['from']) . "' 
                                    AND '" . esc_sql($conditions['specific_period']['to']) . " 23:59:59'";
                    }
                } else if (!empty($conditions['specific_status']['status'])) {
                    $status = esc_sql($conditions['specific_status']['status']);
                    $query .= " AND wc.status = '$status'";
                }

                $query .= " AND wc.status != 'trash'";
            } else {
                $query = "SELECT COUNT(DISTINCT ID) FROM {$wpdb->prefix}posts WHERE post_type = '$module'";
                if (!empty($conditions['specific_period']['is_check']) && $conditions['specific_period']['is_check'] == 'true') {
                    if (!empty($conditions['specific_status']['status'])) {
                        $status = esc_sql($conditions['specific_status']['status']);
                        $query .= " AND post_status = '$status'";
                    }

                    if ($conditions['specific_period']['from'] == $conditions['specific_period']['to']) {
                        $query .= " AND DATE(post_date) = '" . esc_sql($conditions['specific_period']['from']) . "'";
                    } else {
                        $query .= " AND post_date BETWEEN '" . esc_sql($conditions['specific_period']['from']) . "' 
                                    AND '" . esc_sql($conditions['specific_period']['to']) . " 23:59:59'";
                    }
                } else if (!empty($conditions['specific_status']['status'])) {
                    $status = esc_sql($conditions['specific_status']['status']);
                    $query .= " AND post_status = '$status'";
                }
                $query .= " AND post_status != 'trash'";
            }
            $totalCount = $wpdb->get_var($query);
            // Store in options table
            update_option('advancedFilter_export_total_count', $totalCount);
            return $totalCount;
        }

        return 0;
    }
}
