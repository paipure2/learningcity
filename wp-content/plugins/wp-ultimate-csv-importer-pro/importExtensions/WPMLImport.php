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

class WPMLImport {
	private static $wpml_instance = null;

	public static function getInstance() {

		if (WPMLImport::$wpml_instance == null) {
			WPMLImport::$wpml_instance = new WPMLImport;
			return WPMLImport::$wpml_instance;
		}
		return WPMLImport::$wpml_instance;
	}
	function set_wpml_values($header_array ,$value_array , $map, $post_id , $type, $line_number){		
		$post_values = [];
		$helpers_instance = ImportHelpers::getInstance();	
		$post_values = $helpers_instance->get_header_values($map , $header_array , $value_array);

		if (empty($post_values['post_name'])) {
			$slug_index = array_search('slug', array_map('trim', $header_array));
			if ($slug_index !== false && isset($value_array[$slug_index])) {
				$post_values['post_name'] = sanitize_title($value_array[$slug_index]);
			} 
		}

		$this->wpml_import_function($post_values,$type, $post_id, $line_number);

	}

	function wpml_import_function($data_array, $import_as, $pId, $line_number) {
		global $sitepress, $wpdb;
		global $core_instance; 

		$site_lang = get_locale();
		$lang_code = $data_array['language_code'];
		
		$core_instance = CoreFieldsImport::getInstance();
		$extension_object = new ExtensionHandler;
		$taxonomies = get_taxonomies();
		$is_active = $wpdb->get_var("select active from {$wpdb->prefix}icl_languages where code = '$lang_code'");		
		if(!$is_active){			
			$lang_tag = $wpdb->get_var("select code from {$wpdb->prefix}icl_languages where default_locale = '$site_lang'");
			if(!empty($lang_tag))
				$data_array['language_code'] = $lang_tag;
				$core_instance->detailed_log[$line_number]['Instruction'] = "The given language code not configured in WPML.So all are done under default language section.";
		}

		if (isset($import_as) && in_array($import_as, $taxonomies)) {
			$import_type = $import_as;
			
			if($import_type == 'category' || $import_type == 'product_category' || $import_type == 'product_cat' || $import_type == 'wpsc_product_category' || $import_type == 'event-categories'):
				$import_as = 'Categories';
			elseif($import_type == 'product_tag' || $import_type == 'event-tags' || $import_type == 'post_tag'):
				$import_as = 'Tags';
		else:
			$import_as = 'Taxonomies';
			endif;
		}						
		$importAs = $extension_object->import_post_types($import_as );
		$get_trid = $wpdb->get_results("select trid from {$wpdb->prefix}icl_translations ORDER BY translation_id DESC limit 1");
		$trid = $get_trid[0]->trid;		
		//Parent lang
		if((isset($data_array['translated_taxonomy_title']) && empty($data_array['translated_taxonomy_title'])) || (isset($data_array['translated_post_title']) && empty($data_array['translated_post_title']))){						
			if($import_as == 'Taxonomies' || $import_as == 'Categories' || $import_as == 'Tags'){
				$termdata = get_term_by('name', $data_array['translated_taxonomy_title'],$import_type,'ARRAY_A');
				if(is_array($termdata) && !empty($termdata)) {
					$element_id = $termdata['term_id'];
					$taxo_type = $termdata['taxonomy'];
				}
				else{					
						$taxo_type = $import_type;															
				}
				$element_type = 'tax_'.$taxo_type;
				$trids = $trid+1;
				$term_taxonomy_id = $wpdb->get_var("select term_taxonomy_id from {$wpdb->prefix}term_taxonomy where term_id = $pId");
				$set_language_args = array(
					'element_id'    => $term_taxonomy_id,
					'trid'   => $trids,
					'element_type'  => $element_type,
					'language_code'   => $data_array['language_code']
				);						
			}
			else{
				//POST,PAGE and Custom Posts
				$update_query = $wpdb->prepare("select ID,post_type from $wpdb->posts where post_title = %s and post_type=%s order by ID DESC",$data_array['translated_post_title'] , $importAs);
				$ID_result = $wpdb->get_results($update_query);
				if(is_array($ID_result) && !empty($ID_result)) {
					$element_id = $ID_result[0]->ID;
					$post_type = $ID_result[0]->post_type;
				}else{					
					$post_type = $importAs;
				}
				$element_type = 'post_'.$post_type;
				$trids = $trid+1;			
			$set_language_args = array(
				'element_id'    => $pId,
				'trid'   => $trids,
				'element_type'  => $element_type,
				'language_code'   => $data_array['language_code']
			);
			}
						
			do_action( 'wpml_set_element_language_details', $set_language_args );									
		}
		//Child lang
		else if(!empty($data_array['language_code'])
			&&
			((isset($data_array['translated_post_title']) && !empty($data_array['translated_post_title']))
			||
			(isset($data_array['translated_taxonomy_title']) && !empty($data_array['translated_taxonomy_title'])))
		){			
			if($import_as == 'Taxonomies' || $import_as == 'Categories' || $import_as == 'Tags'){				
				if($import_as == 'Categories')
					$import_as = 'category';			
					$translate_title = 	$data_array['translated_taxonomy_title'];						
				$termdata = get_term_by('name', "$translate_title","$import_type","ARRAY_A");								
				
				if(is_array($termdata) && !empty($termdata)) {
					$term_id = $termdata['term_id'];					
					$taxo_type = $termdata['taxonomy'];
					$element_id = $wpdb->get_var("select term_taxonomy_id from {$wpdb->prefix}term_taxonomy where term_id = $term_id");
				}
				else {
					$taxo_type = $import_type;
				}
				if (isset($element_id)) {
					$trid_id = apply_filters( 'wpml_element_trid', NULL, $element_id, 'tax_'.$taxo_type);                                    
					$args = array('element_id' => $element_id, 'element_type' => 'tax_'.$taxo_type );
					$my_language_info = apply_filters( 'wpml_element_language_details', NULL, $args );
					if (isset($my_language_info) && isset($my_language_info->language_code)) {
						$translate_lcode = $my_language_info->language_code;
					}
				}
				$element_type = 'tax_'.$taxo_type;
				$term_taxonomy_id = $wpdb->get_var("select term_taxonomy_id from {$wpdb->prefix}term_taxonomy where term_id = $pId");
				$set_language_args = array(
					'element_id'    => $term_taxonomy_id,
					'element_type'  => $element_type,
					'trid'   => isset($trid_id) ? $trid_id : '',
					'language_code'   => $data_array['language_code'],
					'source_language_code' => isset($translate_lcode) ? $translate_lcode : ''
				);				
				do_action( 'wpml_set_element_language_details', $set_language_args );
		
			}
		else{				
			//POST,PAGE,Custom Post				
				$update_query = $wpdb->prepare("SELECT ID,post_name,post_type FROM $wpdb->posts WHERE post_title = %s AND post_type=%s AND post_status NOT IN('%s')order by ID ASC",$data_array['translated_post_title'] , $importAs,'trash');
				$ID_result = $wpdb->get_results($update_query);								
				if(is_array($ID_result) && !empty($ID_result)) {
					$element_id = $ID_result[0]->ID;
					$post_type = $ID_result[0]->post_type;
					$post_name = $ID_result[0]->post_name;
				}					
				else{
					$post_type = $importAs;
				}
					$trid_id = apply_filters( 'wpml_element_trid', NULL, $element_id, 'post_'.$post_type );					
					$args = array('element_id' => $element_id, 'element_type' => 'post_'.$post_type );
					$my_language_info = apply_filters( 'wpml_element_language_details', null, $args );
					$translate_lcode = $my_language_info->language_code;					
					$element_type = 'post_'.$post_type;					
					$set_language_args = array(
						'element_id'    => $pId,
						'element_type'  => $element_type,
						'trid'   => $trid_id,
						'language_code'   => $data_array['language_code'],
						'source_language_code' => $translate_lcode
					);	
					if (!empty($data_array['post_name'])) {
    $new_slug = sanitize_title($data_array['post_name']);
    wp_update_post([
        'ID'        => $pId,
        'post_name' => $new_slug,
    ]);
} else {
    wp_update_post([
        'ID'        => $pId,
        'post_name' => $post_name,
    ]);
}				
				do_action( 'wpml_set_element_language_details', $set_language_args );				
			}
		}			

		//added - to change lang code in admin view link
		if(isset($core_instance->detailed_log[$line_number]['adminLink'])){		
			$admin_view_link = $this->check_for_wpml_urls($core_instance->detailed_log[$line_number]['adminLink'], $data_array['language_code']);			
			$core_instance->detailed_log[$line_number]['adminLink'] = $admin_view_link;
			$core_instance->detailed_log[$line_number]['webLink'] = $this->get_wpml_permalink($pId);
			
		}
	}
	public	function get_wpml_permalink($post_id) {
		// Save the current language context
		$current_language = apply_filters('wpml_current_language', null);

		// Get the post's language
		$post_language = apply_filters('wpml_element_language_code', null, [
			'element_id'   => $post_id,
			'element_type' => 'post_' . get_post_type($post_id),
		]);

		if ($post_language) {
			// Switch to the post's language
			do_action('wpml_switch_language', $post_language);

			// Get the permalink in the correct language
			$permalink = get_permalink($post_id);

			// Restore the original language
			do_action('wpml_switch_language', $current_language);
		} else {
			// Fallback: Use the default permalink if language info is not available
			$permalink = get_permalink($post_id);
		}

		return $permalink;
	}
	public function check_for_wpml_urls($admin_view_link, $lang_code){
		global $wpdb;		
		if(strpos($admin_view_link, 'lang=') !== FALSE){
	
			$get_existing_lang = explode('lang=', $admin_view_link);
			$get_existing_lang_code = substr($get_existing_lang[1], 0, 2);

			$existing_lang_string = 'lang='. $get_existing_lang_code;
			$current_lang_string = 'lang=' . $lang_code;

			$admin_view_link = str_replace($existing_lang_string, $current_lang_string, $admin_view_link);
			return $admin_view_link;
		}
		else {
			return $admin_view_link;
		}
	}
}
