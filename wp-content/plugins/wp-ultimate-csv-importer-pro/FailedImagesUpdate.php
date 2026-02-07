<?php

/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class FailedImagesUpdate
{
    private static $instance = null;
    public static $media_instance = null;
    public static $corefields_instance = null;
    public $plugin;

    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
            self::$media_instance = MediaHandling::getInstance();
            self::$corefields_instance = CoreFieldsImport::getInstance();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->plugin = Plugin::getInstance();
    }
    public function failed_image_update($line_number, $post_values, $post_id, $post_title, $actual_url, $hash_key, $image_type, $get_import_type, $media_id, $type = null, $media_type = null)
    {
        global $wpdb;
        $media_mode = 'MediaUpdate';
        $media_instance = MediaHandling::getInstance();
        if ($type == 'Media' && empty($post_id)) {
            $image_type = 'Media';
            $attach_id = $media_instance->media_handling($actual_url, '', $post_values, $image_type, $get_import_type, '', $hash_key, '', '', '', '', '', '', $line_number, '', '', $media_type, $media_id ,'','',$media_mode);
            return isset($attach_id) ?  $attach_id : $media_id;
        }
        $shortcode_table = $wpdb->prefix . "ultimate_csv_importer_shortcode_manager";
        $post_table = $wpdb->prefix . "posts";
        $post_title = esc_sql($post_title);
        $image_shortcode = $wpdb->get_var("SELECT image_shortcode FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE post_id = $post_id AND media_id = $media_id AND status = 'failed'");
        $image_meta = $wpdb->get_var("SELECT image_meta FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE post_id = $post_id AND media_id = $media_id AND status = 'failed'");
        $get_import_type = $wpdb->get_var("SELECT import_type FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE post_id = $post_id AND media_id = $media_id AND status = 'failed'");
        if (empty($get_import_type)) {
            $get_import_type = 'post';
        }
        if ($image_shortcode == 'Featured_image_') {
            $image_type = 'Featured';
            $attach_id = $media_instance->media_handling($actual_url, $post_id, $post_values, $image_type, $get_import_type, '', $hash_key, '', '', '', '', '', '', $line_number,'', '', $media_type, $media_id ,'','',$media_mode);
            if (isset($attach_id) && ($attach_id != $media_id)) {

                $this->update_status_shortcode_table($post_id, 'Featured_image_', 'completed', $media_id);
                set_post_thumbnail( $post_id, $attach_id );
                $this->delete_image_schedule($post_id, $media_id);
            } else {
                set_post_thumbnail( $post_id, $media_id );
            }
        } elseif ($image_shortcode == 'inline_image_') {
            // $get_original_image = $wpdb->get_var("SELECT original_image FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE post_id = $post_id AND media_id = $media_id AND image_shortcode ='inline_image_' AND status = 'failed'");
            $attach_id = $media_instance->media_handling($actual_url, $post_id, $post_values, $image_type, $get_import_type, '', $hash_key, '', '', '', '', '', '', $line_number, '', '', $media_type, $media_id ,'','',$media_mode);
            $core_instance = CoreFieldsImport::getInstance();
            if (!empty($attach_id) && ($attach_id != $media_id)) {
                $indexs = $wpdb->get_var("SELECT indexs FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE media_id = $media_id AND image_shortcode = 'inline_image_' AND post_id = " . (int)$post_id . " AND status = 'failed'");
                $attachment_id = $core_instance->image_handling($post_id, $attach_id, $indexs);
                $this->update_status_shortcode_table($post_id, 'inline_image_', 'completed', $media_id);
                $this->delete_image_schedule($post_id, $media_id);
            }
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'yoast_opengraph_image__') !== false) {
            $image_type = 'yoast_opengraph';
            $attach_id = $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'yoast_twitter_image__') !== false) {
            $image_type = 'yoast_twitter';
            $attach_id = $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'wpmember_image__') !== false) {
            $image_type = 'wpmember';
            $attach_id = $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'term_image__') !== false) {
            $image_type = 'term';
            $attach_id =  $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'cmb2_image__') !== false) {
            $image_type = 'cmb2';
            $attach_id =  $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'cfs_image__') !== false) {
            $image_type = 'cfs';
            $attach_id =  $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'metabox_image__') !== false) {
            $image_type = 'metabox';
            $attach_id = $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'metabox_group__') !== false) {
            $image_type = 'metabox_group';
            $attach_id = $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'metabox_group_clone__') !== false) {
            $image_type = 'metabox_group_clone';
            $attach_id = $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'metabox_group_clone_field__') !== false) {
            $image_type = 'metabox_group_clone_field';
            $attach_id = $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'metabox_clone__') !== false) {
            $image_type = 'metabox_clone';
            $attach_id = $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'metabox_image_clone_image__') !== false) {
            $image_type = 'metabox_image_clone';
            $attach_id =  $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'metabox_advanced_image__') !== false) {
            $image_type = 'metabox_advanced';
            $attach_id = $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'metabox_upload_image__') !== false) {
            $image_type = 'metabox_upload';
            $attach_id = $this->images_import_function($post_id, $actual_url, $media_id, $image_shortcode, $image_type, $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_image__') !== false) {
            $image_type = 'acf';
            $attach_id = $this->acf_image_update($post_id, $actual_url, $media_id, $image_type, $image_shortcode, '', 'acf_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_group_image__') !== false) {
            $image_type = 'acf_group';
            $attach_id = $this->acf_image_update($post_id, $actual_url, $media_id, $image_type, $image_shortcode, '', 'acf_group_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_repeater_image__') !== false) {
            $image_type = 'acf_repeater';
            $attach_id = $this->acf_image_update($post_id, $actual_url, $media_id, $image_type, $image_shortcode, '', 'acf_repeater_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_flexible_image__') !== false) {
            $image_type = 'acf_flexible';
            $attach_id = $this->acf_image_update($post_id, $actual_url, $media_id, $image_type, $image_shortcode, '', 'acf_flexible_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_group_repeater_image__') !== false) {
            $image_type = 'acf_group_repeater';
            $attach_id = $this->acf_image_update($post_id, $actual_url, $media_id, $image_type, $image_shortcode, '', 'acf_group_repeater_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_repeater_group_image__') !== false) {
            $image_type = 'acf_repeater_group';
            $attach_id = $this->acf_image_update($post_id, $actual_url, $media_id, $image_type, $image_shortcode, '', 'acf_repeater_group_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'wordpress_custom_image__') !== false) {
            $attach_id = $this->acf_gallery_image_update($post_id, $actual_url, $media_id, $image_shortcode, '', 'wordpress_custom_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_gallery_image__') !== false) {
            $attach_id =  $this->acf_gallery_image_update($post_id, $actual_url, $media_id, $image_shortcode, '', 'acf_gallery_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_group_gallery_image__') !== false) {
            $attach_id =  $this->acf_gallery_image_update($post_id, $actual_url, $media_id, $image_shortcode, '', 'acf_group_gallery_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_repeater_gallery_image__') !== false) {
            $attach_id = $this->acf_gallery_image_update($post_id, $actual_url, $media_id, $image_shortcode, '', 'acf_repeater_gallery_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_group_repeater_gallery_image__') !== false) {
            $attach_id =  $this->acf_gallery_image_update($post_id, $actual_url, $media_id, $image_shortcode, '', 'acf_group_repeater_gallery_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_repeater_group_gallery_image__') !== false) {
            $attach_id = $this->acf_gallery_image_update($post_id, $actual_url, $media_id, $image_shortcode, '', 'acf_repeater_group_gallery_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'acf_flexible_gallery_image__') !== false) {
            $attach_id =  $this->acf_gallery_image_update($post_id, $actual_url, $media_id, $image_shortcode, '', 'acf_flexible_gallery_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'pods_image__') !== false) {
            $attach_id =  $this->pods_gallery_image_update($post_id, $actual_url, $media_id, $image_shortcode, '', 'pods__image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'types_image__') !== false) {
            $attach_id =  $this->types_image_update($post_id, $actual_url, $media_id, $image_shortcode, '', 'types_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'product_image__') !== false) {
            $attach_id =  $this->product_image_update($post_id, $actual_url, $media_id, $image_shortcode, '', 'product_image__', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'jetengine_media_') !== false) {
            $attach_id =  $this->jetengine_image_update($post_id, $actual_url, $media_id, $image_shortcode, $image_meta, 'jetengine_media_', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'jetengine_gallery_') !== false) {
            $attach_id =  $this->jetengine_image_update($post_id, $actual_url, $media_id, $image_shortcode, $image_meta, 'jetengine_gallery_', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'jetengine_repeater_media_') !== false) {
            $attach_id =  $this->jetengine_image_update($post_id, $actual_url, $media_id, $image_shortcode, $image_meta, 'jetengine_repeater_media_', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'jetengine_repeater_gallery_') !== false) {
            $attach_id =  $this->jetengine_image_update($post_id, $actual_url, $media_id, $image_shortcode, $image_meta, 'jetengine_repeater_gallery_', $get_import_type);
        } elseif ($image_shortcode !== null && strpos($image_shortcode, 'jetengine_relation_media_') !== false) {
            $attach_id =  $this->jetengine_image_update($post_id, $actual_url, $media_id, $image_shortcode, $image_meta, 'jetengine_relation_media_', $get_import_type);
        }
        //}
        return  isset($attach_id) ? $attach_id : $media_id;
    }
    public function jetengine_image_update($id, $actual_url, $media_id, $get_shortcode, $get_image_meta, $image_shortcode, $get_import_type)
    {

        global $wpdb;
        $media_mode = 'MediaUpdate';
        $actual_url = urldecode($actual_url);
        $get_image_fieldname = explode('__', $get_shortcode);
        $shortcode = end($get_image_fieldname);
        $indexs = $wpdb->get_var("SELECT indexs FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE media_id = $media_id AND image_shortcode = '" . esc_sql($get_shortcode) . "' AND post_id = " . (int)$id . " AND status = 'failed'");
        $get_original_image = $wpdb->get_results("SELECT original_image FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE image_shortcode LIKE '%$shortcode' AND post_id = $id AND status = 'failed' ", ARRAY_A);
        $jet_child_object_id = $wpdb->get_var("SELECT jet_child_object_id FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE media_id = $media_id AND image_shortcode = '" . esc_sql($get_shortcode) . "' AND post_id = " . (int)$id . " AND status = 'failed'");
        $jet_parent_object_id = $wpdb->get_var("SELECT jet_parent_object_id FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE media_id = $media_id AND image_shortcode = '" . esc_sql($get_shortcode) . "' AND post_id = " . (int)$id . " AND status = 'failed'");
        if(isset($get_image_meta)){
            $image_meta = json_decode($get_image_meta);
            if(!empty($image_meta)){
                $header_array = $image_meta->headerarray;
                $value_array = $image_meta->valuearray;
                $tablename = $image_meta->tablename;
                $imgformat = $image_meta->returnformat;
            }
        }
        if ($image_shortcode == 'jetengine_media_') {
            $image_type = 'jetengine_media';
            $attach_id = self::$media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
            if (isset($attach_id) && ($attach_id != $media_id)) {
                switch ($imgformat) {
                    case 'url': {
                            $img_metaurl = $wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where post_id=$attach_id and meta_key ='_wp_attached_file'");
                            $dir = wp_upload_dir();
                            $imagedata = $dir['baseurl'] . '/' . $img_metaurl[0]->meta_value;
                            break;
                        }
                    case 'both': {
                            $img_d1['id'] = $attach_id;
                            $img_metaurl = $wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where post_id=$attach_id and meta_key ='_wp_attached_file'");
                            $dir = wp_upload_dir();
                            $img_d2['url'] = $dir['baseurl'] . '/' . $img_metaurl[0]->meta_value;
                            $imagedata = array_merge($img_d1, $img_d2);
                            break;
                        }
                    default: {
                            $imagedata = $attach_id;
                            break;
                        }
                }

                if ($get_import_type == 'post' || $get_import_type == 'term' || $get_import_type == 'user' || $get_import_type == 'comments') {
                    $this->update_db_values($id, $get_image_fieldname[1], $imagedata, $get_import_type);
                } else {
                    $table_name = $wpdb->prefix . 'jet_cct_' . $tablename;
                    $fieldname = $get_image_fieldname[1];
                    $result = $wpdb->update($table_name, array($fieldname => $imagedata), array('_ID' => $id));
                }
                $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
                $this->delete_image_schedule($id, $media_id);
            }
        }
        else if($image_shortcode == 'jetengine_relation_media_'){
            $table_name = $wpdb->prefix . 'jet_rel_default_meta';
            $attach_id = self::$media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
            if(isset($attach_id)){
                if(!empty($jet_parent_object_id)){ //parent relation
                    $wpdb->get_results($wpdb->prepare("UPDATE $table_name SET meta_value = %d WHERE parent_object_id = %s",$attach_id,$jet_parent_object_id));        
                }
                else{ //child relation
                    $wpdb->get_results($wpdb->prepare("UPDATE $table_name SET meta_value = %d WHERE child_object_id = %s",$attach_id,$jet_child_object_id));                
                }
                $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
                $this->delete_image_schedule($id, $media_id);         
            }
        }
        else if ($image_shortcode == 'jetengine_gallery_') {
            $imagedata_both = $imagedata = array();
            $image_type = 'jetengine_gallery';
            $gallery_image_ids = get_post_meta($id, $get_image_fieldname[1]);
            if (isset($gallery_image_ids[0])) {
                $gallery_ids = explode(',', $gallery_image_ids[0]);
                if (isset($gallery_ids[$indexs])) {
                    $attach_id = self::$media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
                    if (isset($attach_id) && ($attach_id != $media_id)) {
                        $gallery_ids[$indexs] = $attach_id;
                        $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
                        $this->delete_image_schedule($id, $media_id);
                    }
                }
            }
            if (!empty($gallery_ids)) {
                foreach ($gallery_ids as $imgid) {
                    switch ($imgformat) {
                        case 'url': {
                                $img_metaurl = $wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where post_id=$imgid and meta_key ='_wp_attached_file'");
                                $dir = wp_upload_dir();
                                $imagedata[] = $dir['baseurl'] . '/' . $img_metaurl[0]->meta_value;
                                break;
                            }
                        case 'both': {
                                $img_d1['id'] = $imgid;
                                $img_metaurl = $wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where post_id=$imgid and meta_key ='_wp_attached_file'");
                                $dir = wp_upload_dir();
                                $img_d2['url'] = $dir['baseurl'] . '/' . $img_metaurl[0]->meta_value;
                                $imagedata_both[] = array_merge($img_d1, $img_d2);
                                break;
                            }
                        default: {
                                $imagedata[] = $imgid;
                                break;
                            }
                    }
                }
                if (!empty($imagedata_both)) {
                    $image_details = $imagedata_both;
                } else {
                    $image_details = implode(',', $imagedata);
                }
                if ($get_import_type == 'post' || $get_import_type == 'term' || $get_import_type == 'user' || $get_import_type == 'comments') {
                    $this->update_db_values($id, $get_image_fieldname[1], $image_details, $get_import_type);
                } else {
                    $table_name = $wpdb->prefix . 'jet_cct_' . $tablename;
                    $fieldname = $get_image_fieldname[1];
                    $result = $wpdb->update($table_name, array($fieldname => $image_details), array('_ID' => $id));
                }
            }
        } 
        else if ($image_shortcode == 'jetengine_repeater_media_') {
            $image_type = 'jetengine_repeater_media';
            $rep = explode('__', $get_shortcode);
            $imagedata = "";

            $attach_id = self::$media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
            if (isset($attach_id) && ($attach_id != $media_id)) {
                switch ($imgformat) {
                    case 'url': {
                            $img_metaurl = $wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where post_id=$attach_id and meta_key ='_wp_attached_file'");
                            $dir = wp_upload_dir();
                            $imagedata = $dir['baseurl'] . '/' . $img_metaurl[0]->meta_value;
                            break;
                        }
                    case 'both': {
                            $img_d1['id'] = $attach_id;
                            $img_metaurl = $wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where post_id=$attach_id and meta_key ='_wp_attached_file'");
                            $dir = wp_upload_dir();
                            $img_d2['url'] = $dir['baseurl'] . '/' . $img_metaurl[0]->meta_value;
                            $imagedata = array_merge($img_d1, $img_d2);
                            break;
                        }
                    default: {
                            $imagedata = $attach_id;
                            break;
                        }
                }
                $get_value = array();
                //$get_value gets the field information
                if ($get_import_type == 'post' || $get_import_type == 'user' || $get_import_type == 'comments') {
                    $get_value = get_post_meta($id, $rep[2]);
                    if (isset($get_value[0]))
                        $get_value = $get_value[0];
                } else if ($get_import_type == 'term') {
                    $get_value = get_term_meta($id, $rep[2]);
                    if (isset($get_value[0]))
                        $get_value = $get_value[0];
                } else if ($get_import_type == 'postcct' || $get_import_type == 'termcct' || $get_import_type == 'usercct' || $get_import_type == 'commentcct') {
                    $fieldname = $rep[2];
                    $table_name = $wpdb->prefix . 'jet_cct_' . $tablename;
                    $get_value = $wpdb->get_var("SELECT $fieldname FROM $table_name WHERE _ID=$id");
                }
                if (is_serialized($get_value)) {
                    $get_value = unserialize($get_value);
                }

                //Process the field values(replace original image id)
                foreach ($get_value as $gkey => $gval) {
                    if ($gkey == $rep[1]) {
                        foreach ($gval as $gk => $gv) {
                            if ($shortcode == $gk) {
                                if ($get_import_type == 'post' || $get_import_type == 'term' || $get_import_type == 'user' || $get_import_type == 'comments') {
                                    $get_value[$gkey][$gk] = $imagedata;
                                } else
                                    $get_value[$gkey][$gk] = $imagedata;
                            }
                        }
                    }
                }
                //Store the data into DB                     
                if ($get_import_type == 'post' || $get_import_type == 'term' || $get_import_type == 'user' || $get_import_type == 'comments') {
                    $this->update_db_values($id, $rep[2], $get_value, $get_import_type);
                } else {
                    if (!empty($get_value))
                        $get_value = serialize($get_value);
                    $table_name = $wpdb->prefix . 'jet_cct_' . $tablename;
                    $fieldname = $rep[2];
                    $result = $wpdb->update($table_name, array($fieldname => $get_value,), array('_ID' => $id));
                }
                $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
                $this->delete_image_schedule($id, $media_id);
            }
        } 
        elseif ($image_shortcode == 'jetengine_repeater_gallery_') {
            $image_type = 'jetengine_repeater_gallery';
            $gallery_arr = array();
            $imagedata_both = $imagedata = $get_value = array();

            $rep = explode('__', $get_shortcode);
            $repeater_gallery_ids = get_post_meta($id, $get_image_fieldname[2]);
            $attach_id = self::$media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
            if (isset($attach_id) && ($attach_id != $media_id)) {
                switch ($imgformat) {
                    case 'url': {
                            $img_metaurl = $wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where post_id=$attach_id and meta_key ='_wp_attached_file'");
                            $dir = wp_upload_dir();
                            $imagedata[$rep[1]][] = $dir['baseurl'] . '/' . $img_metaurl[0]->meta_value;
                            break;
                        }
                    case 'both': {
                            $img_d1['id'] = $attach_id;
                            $img_metaurl = $wpdb->get_results("select meta_value from {$wpdb->prefix}postmeta where post_id=$attach_id and meta_key ='_wp_attached_file'");
                            $dir = wp_upload_dir();
                            $img_d2['url'] = $dir['baseurl'] . '/' . $img_metaurl[0]->meta_value;
                            $imagedata_both[$rep[1]][] = array_merge($img_d1, $img_d2);
                            break;
                        }
                    default: {
                            $imagedata[$rep[1]][] = $attach_id;
                            break;
                        }
                }
                if (!empty($imagedata_both[$rep[1]])) {
                    $image_details[$rep[1]] = $imagedata_both[$rep[1]];
                } else {
                    $image_details[$rep[1]] = implode(',', $imagedata[$rep[1]]);
                }
                $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
                $this->delete_image_schedule($id, $media_id);
            }
            if (!empty($image_details[$rep[1]])) {

                if ($get_import_type == 'post' || $get_import_type == 'user' || $get_import_type == 'comments') {
                    $get_value = get_post_meta($id, $rep[2]);
                    if (isset($get_value[0])) {
                        $get_value = $get_value[0];
                    }
                } elseif ($get_import_type == 'term') {
                    $get_value = get_term_meta($id, $rep[2]);
                    if (isset($get_value[0]))
                        $get_value = $get_value[0];
                } else if ($get_import_type == 'postcct' || $get_import_type == 'termcct' || $get_import_type == 'usercct' || $get_import_type == 'commentcct') {
                    $tablename = $wpdb->prefix . 'jet_cct_' . $tablename;
                    $fieldname = $rep[2];
                    $get_value = $wpdb->get_var("SELECT $fieldname FROM $tablename WHERE _ID=$id");
                }
                if (is_serialized($get_value)) {
                    $get_value = unserialize($get_value);
                }
                foreach ($get_value as $item_key => $item) {
                    foreach ($item as $key => $value) {
                        if (is_string($value) && strpos($value, ',') !== false) {
                            $gallery_array = explode(',', $value);
                            // Update the value at the specified index if it exists
                            if (isset($gallery_array[$indexs])) {
                                $gallery_array[$indexs] = $image_details[$rep[1]];
                            }
                            $get_value[$item_key][$key] = implode(',', $gallery_array);
                        }
                    }
                }
                if ($get_import_type == 'post' || $get_import_type == 'term' || $get_import_type == 'user' || $get_import_type == 'comments') {
                    $this->update_db_values($id, $rep[2], $get_value, $get_import_type);
                } else {
                    $fieldname = $rep[2];

                    if (!empty($get_value))
                        $get_value = serialize($get_value);
                    $result = $wpdb->update(
                        $tablename,
                        array(
                            $fieldname => $get_value,
                        ),
                        array('_ID' => $id)
                    );
                }
            }
        }
        return $attach_id;
    }

    public function product_image_update($id, $actual_url, $media_id, $get_shortcode, $get_image_meta, $image_shortcode, $get_import_type)
    {
        global $wpdb;
        $indexs = $wpdb->get_var("SELECT indexs FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE media_id = $media_id AND image_shortcode = '" . esc_sql($get_shortcode) . "' AND post_id = " . (int)$id . " AND status = 'failed'");
        $media_mode='MediaUpdate';
        $get_image_fieldname = explode('__', $image_shortcode);
        $image_gallery = '';
        $image_type = 'product';
        $gallery_ids = get_post_meta($id, '_product_image_gallery', true);
        $gallery_image_ids = !empty($gallery_ids) ? explode(',', $gallery_ids) : array();
        if (isset($indexs) && !empty($gallery_image_ids) && !empty($gallery_image_ids[$indexs])) {
            $attach_id = self::$media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
            if (isset($attach_id) && ($attach_id != $media_id)) {
                if (!empty($gallery_image_ids[$indexs])) {
                    $gallery_image_ids[$indexs] = $attach_id;
                    $updated_gallery_image_ids = implode(',', $gallery_image_ids);
                    update_post_meta($id, '_product_image_gallery', $updated_gallery_image_ids);
                }
                $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
                $this->delete_image_schedule($id, $media_id);
            }
        }
        return isset($attach_id) ? $attach_id : '';
    }
    public function types_image_update($id, $actual_url, $media_id, $get_shortcode, $get_image_meta, $image_shortcode, $get_import_type)
    {
        global $wpdb;
        $indexs = $wpdb->get_var("SELECT indexs FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE media_id = $media_id AND image_shortcode = '" . esc_sql($get_shortcode) . "' AND post_id = " . (int)$id . " AND status = 'failed'");
        $get_image_fieldname = explode('__', $get_shortcode);
        $image_type = 'types';
        $current_gallery = get_post_meta($id, $get_image_fieldname[1]);
        $media_mode='MediaUpdate';
        if ($current_gallery && is_array($current_gallery)) {
            // Ensure the index is within the bounds of the array
            if (isset($current_gallery[$indexs])) {
                $attach_id = self::$media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
                if (isset($attach_id) && ($attach_id != $media_id)) {
                    $new_image_url = $wpdb->get_var("SELECT guid FROM {$wpdb->prefix}posts WHERE ID = $attach_id");
                    $current_gallery[$indexs] = $new_image_url;
                    delete_post_meta($id, $get_image_fieldname[1]);
                    foreach ($current_gallery as $update_gallery) {
                        add_post_meta($id, $get_image_fieldname[1], $update_gallery);
                    }

                    $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
                    $this->delete_image_schedule($id, $media_id);
                }
            }
        }
        return isset($attach_id) ? $attach_id : '';
    }
    public function pods_gallery_image_update($id, $actual_url, $media_id, $get_shortcode, $get_image_meta, $image_shortcode, $get_import_type)
    {
        global $wpdb;
        $media_mode = 'MediaUpdate';
        $actual_url = urldecode($actual_url);
        $get_image_fieldname = explode('__', $get_shortcode);
        $indexs = $wpdb->get_var("SELECT indexs FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE media_id = $media_id AND image_shortcode = '" . esc_sql($get_shortcode) . "' AND post_id = " . (int)$id . " AND status = 'failed'");
        $post_type = get_post_type($id);
        $importazValue = esc_sql($post_type);
        $pods_id = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_name = '{$importazValue}' AND post_type = '_pods_pod'");
        $pod = pods($importazValue);
        $field_info = $pod->fields($get_image_fieldname[1]);
        $field_id = $field_info['id'];
        $result = $wpdb->get_results("SELECT related_item_id FROM {$wpdb->prefix}podsrel WHERE pod_id = {$pods_id[0]->ID} AND item_id = {$id} AND field_id = {$field_id} AND related_item_id = {$media_id}", ARRAY_A);
        $attach_id = self::$media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
        if ($result[0]['related_item_id'] == $media_id && isset($attach_id) && ($attach_id != $media_id)) {
            $data = array('related_item_id' => $attach_id);
            $where = array('pod_id' => $pods_id[0]->ID, 'item_id' => $id, 'field_id' => $field_id, 'related_item_id' => $media_id);
            $update_status = $wpdb->update("{$wpdb->prefix}podsrel", $data, $where);
            //update post meta
            $current_gallery = get_post_meta($id, $get_image_fieldname[1], true);
            if ($current_gallery && is_array($current_gallery)) {
                $found = false;
                foreach ($current_gallery as $index => $attachment_id) {
                    if ($attachment_id == $media_id) {
                        $current_gallery[$index] = $attach_id;
                        $found = true;
                    }
                }
                if ($found) {
                    // Update the postmeta table with the new gallery data
                    update_post_meta($id, $get_image_fieldname[1], $current_gallery);
                }
            }
            $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
            $this->delete_image_schedule($id, $media_id);
        }
        return $attach_id;
    }

    public function acf_gallery_image_update($id, $actual_url, $media_id, $get_shortcode, $get_image_meta, $image_shortcode, $get_import_type)
    {
        global $wpdb;
        $media_mode = 'MediaUpdate';
        $actual_url = urldecode($actual_url);
        $get_image_fieldname = explode('__', $get_shortcode);
        if ($image_shortcode == 'acf_repeater_gallery_image__' || $image_shortcode == 'acf_group_repeater_gallery_image__' || $image_shortcode == 'acf_repeater_group_gallery_image__') {
            $shortcode = $get_image_fieldname[1];
            $failed_gallery_index = $wpdb->get_results("SELECT indexs FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE image_shortcode LIKE '%$shortcode' AND media_id = $media_id AND post_id = $id ", ARRAY_A);
        } else {
            $shortcode = $get_image_fieldname[1];
            $failed_gallery_index = $wpdb->get_results("SELECT indexs FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE image_shortcode LIKE '%$shortcode' AND media_id = $media_id AND post_id = $id ", ARRAY_A);
        }
        $image_type = chop($image_shortcode, '_image__');
        $gallery_ids = [];
        $get_existing_gallery_ids = get_post_meta($id, $get_image_fieldname[1]);
        if (!empty($get_existing_gallery_ids[0]) && is_array($get_existing_gallery_ids[0])) {
            $gallery_ids = $get_existing_gallery_ids[0];
        }
        $indexs = !empty($failed_gallery_index) ? $failed_gallery_index[0]['indexs'] : null;
        if (!empty($gallery_ids) && isset($indexs)) {
            $search_index = $gallery_ids[$indexs];
            $key_to_replace = array_search($search_index, $gallery_ids);
            if ($key_to_replace !== false) {
                $attach_id = self::$media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
                if (!empty($attach_id) && ($attach_id != $media_id)) {
                    $gallery_ids[$key_to_replace] = $attach_id;
                    if (strpos($get_shortcode, 'wordpress_custom_image__') !== false) {
                        $this->update_db_values($id, 'image_gallery_ids', $gallery_ids, $get_import_type);
                    } else {
                        $this->update_db_values($id, $get_image_fieldname[1], $gallery_ids, $get_import_type);
                    }
                    $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
                    $this->delete_image_schedule($id, $media_id);
                }
            }
        }
        return $attach_id;
    }
    public function images_import_function($id, $actual_url, $media_id, $get_shortcode, $image_type, $get_import_type)
    {
        global $wpdb;
        // $media_instance = MediaHandling::getInstance(); 
        $actual_url = urldecode($actual_url);
        $image_type = trim($image_type);
        $media_mode = 'MediaUpdate';
        $get_image_fieldname = explode('__', $get_shortcode);
        $indexs = $wpdb->get_var("SELECT indexs FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE media_id = $media_id AND image_shortcode = '" . esc_sql($get_shortcode) . "' AND post_id = " . (int)$id . " AND status = 'failed'");
        $attach_id = self::$media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
        if (isset($attach_id) && isset($media_id) && ($attach_id != $media_id)) {
            if (($image_type == 'yoast_opengraph') || ($image_type == 'yoast_twitter')) {
                $this->update_yoast_db_values($id, $actual_url, $attach_id, $image_type);
            } elseif ($image_type == 'wpmember') {
                update_user_meta($id, $get_image_fieldname[1], $attach_id);
            } elseif ($image_type == 'cmb2') {
                $this->update_db_values($id, $get_image_fieldname[1], $attach_id, $get_import_type);
            } elseif ($image_type == 'cfs') {
                $this->update_db_values($id, $get_image_fieldname[1], $attach_id, $get_import_type);
            } elseif ($image_type == 'term') {
                update_term_meta($id, $get_image_fieldname[1], $attach_id);
            } elseif ($image_type == 'metabox') {
                $this->update_db_values($id, $get_image_fieldname[1], $attach_id, $get_import_type);
            } elseif ($image_type == 'metabox_clone' ){
                $this->update_metabox_clone_field($id,$get_image_fieldname[3],$indexs, $attach_id,$media_id,$get_image_fieldname[1]);
            } elseif ($image_type == 'metabox_advanced' || $image_type == 'metabox_upload' || $image_type == 'metabox_clone') {
                $this->update_metabox_image_advanced($id, $get_image_fieldname[1], $indexs, $attach_id);
            } elseif ($image_type == 'metabox_group') {
                $this->update_metabox_group_clone_field($id,$image_type,$get_image_fieldname[1], $get_image_fieldname[3],$indexs, $attach_id,$media_id,'');
            } elseif ($image_type == 'metabox_group_clone' || $image_type == 'metabox_group_clone_field') {
                $this->update_metabox_group_clone_field($id,$image_type,$get_image_fieldname[2], $get_image_fieldname[4],$indexs, $attach_id,$media_id,$get_image_fieldname[1]);
            }
            $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
            $this->delete_image_schedule($id, $media_id);
        } else {
            $this->update_db_values($id, $get_image_fieldname[1], $media_id, $get_import_type);
        }
        return $attach_id;
    }
    public function update_metabox_clone_field($post_id, $meta_key, $image_index, $new_attachment_id, $media_id, $clonecount = null)
    {
        $images = get_post_meta($post_id, $meta_key,true);
        if (is_array($images) && !empty($images)) {
            $get_image_fieldname = explode('_', $meta_key);
            $indexs = 0;
            if($get_image_fieldname[0] == 'single'){
                foreach($images as $img){
                    if($img == $media_id){
                        $image_index = $indexs;
                        break;
                    }
                    $indexs++;        
                }
                $images[$image_index] = $new_attachment_id;
            }else{
                foreach($images[$clonecount] as $img => $img_id){
                    if($img_id == $media_id){
                        $image_index = $indexs;
                        break;
                    }
                    $indexs++;        
                }
                if(!empty($images[$clonecount][$image_index])){
                    $images[$clonecount][$image_index] = $new_attachment_id;
                }
               
            }
        }
        update_post_meta($post_id, $meta_key, $images);
    }
    public function update_metabox_group_clone_field($post_id, $image_type, $group_meta_key, $grp_meta_field, $image_index, $new_attachment_id, $media_id, $clonecount = null){ 
        $images = get_post_meta($post_id, $group_meta_key,true);
        if (is_array($images) && !empty($images)) {
            if($image_type == 'metabox_group'){
                $get_image_fieldname = explode('_', $grp_meta_field);
                if($get_image_fieldname[0] == 'single'){
                    $images[$grp_meta_field] = $new_attachment_id;
                }else {
                    if(!empty($images[$grp_meta_field][$image_index])){
                        $images[$grp_meta_field][$image_index] = $new_attachment_id;
                    }       
                }
            }else if($image_type == 'metabox_group_clone'){
                $indexs = 0;
                foreach($images[$clonecount][$grp_meta_field] as $img){
                    if($img == $media_id){
                        $image_index = $indexs;
                        break;
                    }
                    $indexs++;
                }
                $get_image_fieldname = explode('_', $grp_meta_field);
                if($get_image_fieldname[0] == 'single'){
                    if(!empty($images[$clonecount][$grp_meta_field])){
                        $images[$clonecount][$grp_meta_field] = $new_attachment_id;
                    }
                }else {
                    if(!empty($images[$clonecount][$grp_meta_field][$image_index])){
                        $images[$clonecount][$grp_meta_field][$image_index] = $new_attachment_id;
                    }       
                }
            }else if($image_type == 'metabox_group_clone_field'){
                $indexs = 0;
                foreach($images[$clonecount][$grp_meta_field] as $img => $img_id){
                    if($img_id == $media_id){
                        $image_index = $indexs;
                        break;
                    }
                    $indexs++;
                }
                $get_image_fieldname = explode('_', $grp_meta_field);
                if(!empty($images[$clonecount][$grp_meta_field][$image_index])){
                    $images[$clonecount][$grp_meta_field][$image_index] = $new_attachment_id;
                }
            }
            update_post_meta($post_id, $group_meta_key, $images);
        }
    }
    public function update_metabox_image_advanced($post_id, $meta_key, $image_index, $new_attachment_id)
    {
        $images = get_post_meta($post_id, $meta_key);
        // Update the image at the specified index
        if (isset($image_index)) {
            $images[$image_index] = $new_attachment_id;
        }
        if (!empty($images[$image_index])) {
            delete_post_meta($post_id, $meta_key);
            foreach ($images as $image_id) {
                //add post meta
                add_post_meta($post_id, $meta_key, $image_id);
            }
        }
    }

    public function update_yoast_db_values($post_id, $img_url, $attach_id, $image_type)
    {
        if ($image_type == 'yoast_opengraph') {
            $meta_key_url = '_yoast_wpseo_opengraph-image';
            $meta_key_id = '_yoast_wpseo_opengraph-image-id';
        } elseif ($image_type == 'yoast_twitter') {
            $meta_key_url = '_yoast_wpseo_twitter-image';
            $meta_key_id = '_yoast_wpseo_twitter-image-id';
        }
        $custom_array = array(
            $meta_key_url => $img_url,
            $meta_key_id => $attach_id
        );
        if (!empty($custom_array)) {
            foreach ($custom_array as $custom_key => $custom_value) {
                update_post_meta($post_id, $custom_key, $custom_value);
            }
        }
    }
    public function acf_image_update($id, $actual_url, $media_id, $image_type, $get_shortcode, $get_image_meta, $image_shortcode, $get_import_type)
    {
        global $wpdb;
        $media_instance = MediaHandling::getInstance();
        $media_mode = 'MediaUpdate';
        $get_image_fieldname = explode('__', $get_shortcode);
        $attach_id = $media_instance->media_handling($actual_url, $id,'', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '' ,'','',$media_mode);
        if (isset($attach_id) && ($attach_id != $media_id)) {
            $this->update_db_values($id, $get_image_fieldname[1], $attach_id, $get_import_type);
            $this->update_status_shortcode_table($id, $get_shortcode, 'completed', $media_id);
            $this->delete_image_schedule($id, $media_id);
        } else {
            $this->update_db_values($id, $get_image_fieldname[1], $media_id, $get_import_type);
        }
        return $attach_id;
    }
    public function delete_image_schedule($post_id = null, $media_id = null)
    {
        global $wpdb;
        $media_id = intval($media_id);  // Ensure $media_id is an integer to prevent SQL injection
        $post_title = 'image-failed';
        $escaped_post_title = esc_sql($post_title);
        $wpdb->get_results("DELETE FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE status = 'completed' ");

        // $check_for_pending_images = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE status = 'failed' ");
        // if(empty($check_for_pending_images)){
        $check_for_loading_images = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_title = '$escaped_post_title' AND ID = $media_id");
        if (!empty($check_for_loading_images)) {
            $delete_post_id = $check_for_loading_images[0]->ID;
            $wpdb->get_results("DELETE FROM {$wpdb->prefix}posts WHERE ID = $delete_post_id ");
            $wpdb->get_results("DELETE FROM {$wpdb->prefix}postmeta WHERE post_id = $delete_post_id ");
        }
        // }
    }

    public function update_status_shortcode_table($id, $get_shortcode, $status, $media_id)
    {
        global $wpdb;
        if ($media_id != null) {
            $get_shortcode = esc_sql($get_shortcode);
            $ID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ultimate_csv_importer_shortcode_manager WHERE media_id = $media_id AND image_shortcode = '" . esc_sql($get_shortcode) . "' AND post_id = " . (int)$id . " AND status = 'failed'");
            $wpdb->update(
                $wpdb->prefix . 'ultimate_csv_importer_shortcode_manager',
                array(
                    'status' => $status,
                ),
                array(
                    'id' => $ID
                )
            );
        }
    }
    public function update_db_values($post_id, $meta_key, $meta_value, $import_type)
    {
        global $wpdb;
        if ($import_type == 'post') {
            update_post_meta($post_id, $meta_key, $meta_value);
        } elseif ($import_type == 'term') {
            update_term_meta($post_id, $meta_key, $meta_value);
        } elseif ($import_type == 'user') {
            update_user_meta($post_id, $meta_key, $meta_value);
        }
    }
}
