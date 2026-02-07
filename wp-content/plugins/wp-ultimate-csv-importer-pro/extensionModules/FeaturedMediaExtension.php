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

    class FeaturedMediaExtension extends ExtensionHandler{
        public static $instance = null;
    
        public static function getInstance() {		
            if (FeaturedMediaExtension::$instance == null) {
                FeaturedMediaExtension::$instance = new FeaturedMediaExtension;
            }
            return FeaturedMediaExtension::$instance;
        }
    
        /**
        * @param string $data - selected import type
        * @return array - mapping fields
        */
        public function processExtension($data) {
            $mode = isset($_POST['Mode']) ? sanitize_text_field($_POST['Mode']) : '';		
            $import_types = $data;
            $import_type = $this->import_name_as($import_types);
            $response = [];
            if( $import_type == "Posts" || $import_type == "Pages" || $import_type == "CustomPosts" || $import_type == "WooCommerce" || $import_type == "WooCommerce Product" || $import_type == "Categories"){
                $wordpressfields = array(
                                    'Title' => 'featured_image_title',
                                    'Caption' => 'featured_image_caption',
                                    'Alt text' => 'featured_image_alt_text',
                                    'Description' => 'featured_image_description',
                                    'File Name' =>    'featured_file_name'									
                                        );
                $wordpress_value = $this->convert_static_fields_to_array($wordpressfields);
                $response['featured_fields'] = $wordpress_value ;
            }
            return $response;	
        }
    
        /**
        * @param string $import_type - selected import type
        * @return boolean
        */
        public function extensionSupportedImportType($import_type){	

            $import_type = $this->import_name_as($import_type);
            
            if( $import_type == "Posts" || $import_type == "Pages" || $import_type == "CustomPosts" || $import_type == "WooCommerce" || $import_type == "WooCommerce Product"|| $import_type == "Categories" ){
                 return true;
            }
        }
        
    }


