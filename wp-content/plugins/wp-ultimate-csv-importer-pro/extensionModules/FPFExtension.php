<?php
/**
 * WP Ultimate CSV Importer plugin file.
 *
 * Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 */

namespace Smackcoders\WCSV;

if (!defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class FPFExtension extends ExtensionHandler {
    private static $instance = null;

    public static function getInstance() {
        if (FPFExtension::$instance === null) {
            FPFExtension::$instance = new FPFExtension();
        } 
        return FPFExtension::$instance;
    }

    public function processExtension ($data) {
        global $wpdb;
    
        // Query to fetch meta values from wp_postmeta with meta key '_fields' for FPF
        $meta_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_value 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = %s",
                '_fields'
            ),
            ARRAY_A
        );
    
        // Initialize the headers array
        $headers = [];
    
        // Validate and process the meta results
        if (!empty($meta_results)) {
            foreach ($meta_results as $meta) {
                if (!empty($meta['meta_value'])) {
                    // Unserialize the data instead of using json_decode
                    $decoded_meta = unserialize($meta['meta_value']);
 
                    if (is_array($decoded_meta)) {
                        foreach ($decoded_meta as $field) {
                            if (isset($field['id']) && !empty($field['title'])) {
                                // Collect field names for headers
                                $headers[] = [
                                    'label' => $field['title'],  // Use 'title' instead of 'name' as per debug data
                                    'type'  => $field['type'] ?? 'text', // Default to 'text' if type is not set
                                ];
// Log the data with a custom label
error_log('Headers Check : ' . print_r($headers, true), 3, '/var/www/html/syed.log');
                            }
                        }
                    }
                }
            }
        }
    
        // Prepare the response in the required format
        $response = [];
        foreach ($headers as $header) {
            $response[] = [
                'label' => $header['label'],
                'name'  => sanitize_title($header['label']), // Generate a machine-friendly name
                'type'  => $header['type'],
            ];
        }
    
        // Return the formatted FPF meta fields
        return ['fpf_meta_fields' => $response];
    }
    
    
     /**
     * FPF Meta extension supported import types
     * @param string $import_type - selected import type
     * @return boolean
     */
    public function extensionSupportedImportType($import_type) {
        if (is_plugin_active('flexible-product-fields/flexible-product-fields.php')) {

            $import_type = $this->import_name_as($import_type);

            if ($import_type === 'WooCommerceOrders') {
                return true;
            }
            else {
                return false;
            }
        }

        return false;
    }

}

