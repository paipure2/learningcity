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

class JetReviews extends ExtensionHandler
{
    private static $instance = null;

    public static function getInstance()
    {
        if (JetReviews::$instance == null) {
            JetReviews::$instance = new JetReviews;
        }
        return JetReviews::$instance;
    }

    /**
     * Provides JetReviews Meta fields for specific post type
     * @param string $data - selected import type
     * @return array - mapping fields
     */
    public function processExtension($data)
    {
        $import_type = $data;
        $response = [];
        $import_type = $this->import_post_types($import_type);
            $post_type_settings = jet_reviews()->settings->get_post_type_data( $import_type );
            $postTypesData[] = array(
                'allowed'             => $post_type_settings['allowed'],
            );
        if (is_plugin_active('jet-reviews/jet-reviews.php') && $postTypesData[0]['allowed']) {
            $jet_meta_fields = array(
                'Jet Review Title'             => 'jet-review-title',
                'Jet Review Items'             => 'jet-review-items',
                'Jet Review Summary Title'      => 'jet-review-summary-title',
                'Jet Review Summary Text'       => 'jet-review-summary-text',
                'Jet Review Summary Legend'     => 'jet-review-summary-legend',
                'Jet Review Data Name'          => 'jet-review-data-name',
                'Jet Review Data Image'         => 'jet-review-data-image',
                'Jet Review Data Description'   => 'jet-review-data-desc',
                'Jet Review Data Author Name'   => 'jet-review-data-author-name',
            );
        }
        $jet_meta_fields = isset($jet_meta_fields) ? $jet_meta_fields : '';
        $jet_review_fields = $this->convert_static_fields_to_array($jet_meta_fields);
        $response['jet_review_fields'] = $jet_review_fields;
        return $response;
    }

    public function import_post_types($import_type, $importAs = null) {	
		$import_type = trim($import_type);

		$module = array('Posts' => 'post', 'Pages' => 'page', 'Users' => 'user', 'Comments' => 'comments', 'Taxonomies' => $importAs, 'CustomerReviews' =>'wpcr3_review', 'Categories' => 'categories', 'Tags' => 'tags', 'WooCommerce Product' => 'product','WooCommerce' => 'product', 'WPeCommerce' => 'wpsc-product','WPeCommerceCoupons' => 'wpsc-product','WooCommerceVariations' => 'product', 'WooCommerceOrders' => 'product', 'WooCommerceCoupons' => 'product', 'WooCommerceRefunds' => 'product', 'CustomPosts' => $importAs,'WooCommerceReviews' =>'reviews','GFEntries' => 'gfentries');
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

    /**
     * Product Meta extension supported import types
     * @param string $import_type - selected import type
     * @return boolean
     */
    public function extensionSupportedImportType($import_type)
    {
        if (is_plugin_active('jet-reviews/jet-reviews.php')) {
            $import_type = $this->import_name_as($import_type);
            if ($import_type == 'Posts' || $import_type == 'Pages' || $import_type == 'CustomPosts' || $import_type =='WooCommerce') {
                return true;
            } else {
                return false;
            }
        }
    }
}
