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

class MediaImport
{
	private static $media_core_instance = null, $media_instance;


	public function __construct()
	{
		//require_once(ABSPATH.'wp-load.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/post.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
	}

	public static function getInstance()
	{
		if (MediaImport::$media_core_instance == null) {
			MediaImport::$media_core_instance = new MediaImport;
			MediaImport::$media_instance = new MediaHandling;
			return MediaImport::$media_core_instance;
		}
		return MediaImport::$media_core_instance;
	}
	public function media_fields_import($data_array, $mode, $type, $media_type, $unikey, $unikey_name, $line_number, $hash_key, $header_array, $value_array)
	{
		//$mode = 'Insert';
		//$mode = 'Update';
		//$media_type = 'External';
		//$media_type = 'Local';
		$returnArr = array();
		global $wpdb;
		$image_type = '';
		$helpers_instance = ImportHelpers::getInstance();
		$core_instance = CoreFieldsImport::getInstance();
		$log_manager_instance = LogManager::getInstance();
		global $core_instance;
		$log_table_name = $wpdb->prefix . "import_detail_log";
		$media_handle = get_option('smack_image_options');
		$updated_row_counts = $helpers_instance->update_count($unikey, $unikey_name);
		$created_count = $updated_row_counts['created'];
		$updated_count = $updated_row_counts['updated'];
		$skipped_count = $updated_row_counts['skipped'];
		$failed_count = $updated_row_counts['failed'];
		$post_id = isset($data_array['post_id']) ? $data_array['post_id'] : '';
		$file_name = isset($data_array['file_name']) ? $data_array['file_name'] : '';
		$title = isset($data_array['title']) ? $data_array['title'] : '';
		$caption = isset($data_array['caption']) ? $data_array['caption'] : '';
		$alt_text = isset($data_array['alt_text']) ? $data_array['alt_text'] : '';
		$description = isset($data_array['description']) ? $data_array['description'] : '';
		$actual_url = isset($data_array['actual_url']) ? $data_array['actual_url'] : '';
		$media_id = isset($data_array['media_id']) ? $data_array['media_id'] : '';
		if ($media_type == 'Local') {
			if(!empty($data_array['file_name'])){
				$sanitized_filename = str_replace(' ', '-', basename($data_array['file_name']));
				$img = preg_replace('/[^a-zA-Z0-9._\-\s]/', '', $sanitized_filename);
				$file_name = $img;
			}
		} else {
			$img = isset($data_array['actual_url']) ? $data_array['actual_url'] : '';
		}
		if ($media_handle['media_settings']['media_handle_option'] == 'true') {
			$media_handle['media_settings']['title'] = isset($data_array['title']) ? $data_array['title'] : '';
			$media_handle['media_settings']['caption'] = isset($data_array['caption']) ? $data_array['caption'] : '';
			$media_handle['media_settings']['alttext'] = isset($data_array['alt_text']) ? $data_array['alt_text'] : '';
			$media_handle['media_settings']['description'] = isset($data_array['description']) ? $data_array['description'] : '';
			$media_handle['media_settings']['file_name'] = isset($file_name) ?  $file_name: '';

			update_option('smack_image_options', $media_handle);
		}
		/****** insert functionality  */
		if ($mode == 'Insert') {
			$mode_of_affect = 'Inserted';
			if (!empty($img)) {
				MediaImport::$media_instance->store_image_ids($i=1);
				$attach_id = MediaImport::$media_instance->image_meta_table_entry($line_number, $data_array, '', '', $img, $hash_key, 'Media', 'Media', '', '', '', '', '', '', '', $media_type);
				$attachment_id = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND post_title ='image-failed' AND ID ={$attach_id}", ARRAY_A);
				if (!empty($attachment_id)) {
					if ($media_type == 'Local') {
						$this->media_data($mode, $core_instance, $line_number, 'Unable to detect the image in your import file. Please check and try again.', $attach_id, wp_get_attachment_url($attach_id), '', $file_name, $title, $caption, $alt_text, $description, 'Failed', $media_type);
					} else if ($media_type == 'External') {
						$this->media_data($mode, $core_instance, $line_number, 'The provided image file URL is invalid. Please verify the URL and try again', $attach_id, wp_get_attachment_url($attach_id), $actual_url, $file_name, $title, $caption, $alt_text, $description, 'Failed', $media_type);
					}
					$wpdb->get_results("UPDATE $log_table_name SET  failed = $failed_count WHERE $unikey_name = '$unikey'");
					return array('MODE' => $mode, 'ERROR_MSG' => "Can't insert this Image");
				} else {
					if ($media_type == 'Local') {
						$this->imageImport($attach_id, $data_array, $media_type);
						$this->media_data($mode, $core_instance, $line_number, 'Image Inserted', $attach_id, wp_get_attachment_url($attach_id), '', $file_name, $title, $caption, $alt_text, $description, 'Inserted', $media_type);
					} else if ($media_type == 'External') {
						$this->media_data($mode, $core_instance, $line_number, 'Image Inserted', $attach_id, wp_get_attachment_url($attach_id), $actual_url, $file_name, $title, $caption, $alt_text, $description, 'Inserted', $media_type);
					}
					$wpdb->get_results("UPDATE $log_table_name SET created = $created_count WHERE $unikey_name = '$unikey'");
				}
			} else {
				$this->media_data($mode, $core_instance, $line_number, 'The provided image file is empty. Double-check your import file and retry', '', '', $actual_url, $file_name, $title, $caption, $alt_text, $description, 'Failed', $media_type, $post_id);
				$wpdb->get_results("UPDATE $log_table_name SET  failed = $failed_count WHERE $unikey_name = '$unikey'");
				return array('MODE' => $mode, 'ERROR_MSG' => "Can't insert this Image");
			}
			$returnArr['ID'] = $attach_id;
			$returnArr['MODE'] = $mode_of_affect;
			return $returnArr;
		}
		/****** update functionality  */
		else if ($mode == 'Update') {
			$mode_of_affect = 'Updated';
			$check_attachment = isset($media_id) ? get_post($media_id) : '';
			if ($check_attachment && 'attachment' === $check_attachment->post_type) {

				if ($media_type == 'Local') {
					if (!empty($file_name)) {
						$attach_id = CoreFieldsImport::$failed_images_instance->failed_image_update($line_number, $data_array, $post_id, '', $file_name, $hash_key, $image_type = null, $get_import_type = null, $media_id, $type, $media_type);
						$attachment_id = $wpdb->get_results("SELECT ID,post_title FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND ID = '{$attach_id}' AND post_title != 'image-failed'", ARRAY_A);
						if (!empty($attachment_id) && $attachment_id[0]['ID'] == $attach_id) {
							$this->media_data($mode, $core_instance, $line_number, 'Image Updated', $attach_id, wp_get_attachment_url($attach_id), $actual_url, $file_name, $title, $caption, $alt_text, $description, 'Updated', $media_type);
							$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");
						} else {
							$this->media_data($mode, $core_instance, $line_number, 'The image is missing in your import file. Please check and try again.', $attach_id, wp_get_attachment_url($attach_id), $actual_url, $file_name, $title, $caption, $alt_text, $description, 'Failed', $media_type);
							$wpdb->get_results("UPDATE $log_table_name SET  failed = $failed_count WHERE $unikey_name = '$unikey'");
							return array('MODE' => $mode, 'ERROR_MSG' => "Can't insert this Image");
						}
					} else {
						$this->imageImport($media_id, $data_array, $media_type);
						$this->media_data($mode, $core_instance, $line_number, 'Image Updated', $media_id, wp_get_attachment_url($media_id), '', $file_name, $title, $caption, $alt_text, $description, 'Updated', $media_type);
						$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");
					}
				}
				/***** update attached post/ media */
				else if ($media_type == 'External') {
					if (!empty($post_id) && !empty($actual_url)) {
						$attach_id = CoreFieldsImport::$failed_images_instance->failed_image_update($line_number, $data_array, $post_id, '', $actual_url, $hash_key, $image_type = null, $get_import_type = null, $media_id, $type, $media_type);
						if ($attach_id !== $media_id) {
							$attachment_id = $wpdb->get_results("SELECT ID,post_title FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND ID = '{$attach_id}' AND post_title != 'image-failed'", ARRAY_A);
							if (isset($attachment_id) && $attachment_id[0]['ID'] == $attach_id) {
								$this->media_data($mode, $core_instance, $line_number, 'Image Updated', $attach_id, wp_get_attachment_url($attach_id), $actual_url, '', '', '', '', '', 'Updated', $media_type, $post_id);
								$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");
							} else {
								$this->media_data($mode, $core_instance, $line_number, 'The provided image file URL is invalid. Please verify the URL and try again.', $attach_id, wp_get_attachment_url($attach_id), $actual_url, '', '', '', '', '', 'Failed', $media_type, $post_id);
								$wpdb->get_results("UPDATE $log_table_name SET  failed = $failed_count WHERE $unikey_name = '$unikey'");
								return array('MODE' => $mode, 'ERROR_MSG' => "Can't insert this Image");
							}
						} else {
							$this->media_data($mode, $core_instance, $line_number, 'The provided image file URL is invalid. Please verify the URL and try again', $attach_id, wp_get_attachment_url($attach_id), $actual_url, '', '', '', '', '', 'Failed', $media_type, $post_id);
							$wpdb->get_results("UPDATE $log_table_name SET  failed = $failed_count WHERE $unikey_name = '$unikey'");
							return array('MODE' => $mode, 'ERROR_MSG' => "Can't insert this Image");
						}
					}
					/***** update unattached media */
					else if (!empty($actual_url)) { // update actual url file
						$attach_id = CoreFieldsImport::$failed_images_instance->failed_image_update($line_number, $data_array, $post_id, '', $actual_url, $hash_key, $image_type = null, $get_import_type = null, $media_id, $type, $media_type);
						$attachment_id = $wpdb->get_results("SELECT ID,post_title FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND ID = '{$attach_id}' AND post_title != 'image-failed'", ARRAY_A);
						if (!empty($attachment_id) && $attachment_id[0]['ID'] == $attach_id) {
							$this->imageImport($attach_id, $data_array, $media_type);
							$this->media_data($mode, $core_instance, $line_number, 'Image Updated', $attach_id, wp_get_attachment_url($attach_id), $actual_url, $file_name, $title, $caption, $alt_text, $description, 'Updated', $media_type);
							$core_instance->detailed_log[$line_number]['state'] = 'Updated';
							$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");
						} else {
							$this->media_data($mode, $core_instance, $line_number, 'The provided image file URL is invalid. Please verify the URL and try again', $attach_id, wp_get_attachment_url($attach_id), $actual_url, $file_name, $title, $caption, $alt_text, $description, 'Failed', $media_type);
							$wpdb->get_results("UPDATE $log_table_name SET  failed = $failed_count WHERE $unikey_name = '$unikey'");
							return array('MODE' => $mode, 'ERROR_MSG' => "Can't insert this Image");
						}
					} 
					else {
						$attach_id = $media_id;
						$this->imageImport($attach_id, $data_array, $media_type);
						$this->media_data($mode, $core_instance, $line_number, 'Image Updated', $attach_id, wp_get_attachment_url($attach_id), $actual_url, $file_name, $title, $caption, $alt_text, $description, 'Updated', $media_type);
						$wpdb->get_results("UPDATE $log_table_name SET updated = $updated_count WHERE $unikey_name = '$unikey'");
					}
				}
				$returnArr['ID'] = $attach_id;
				$returnArr['MODE'] = $mode_of_affect;
				return $returnArr;
			} else {
				$this->media_data($mode, $core_instance, $line_number, 'No matching image ID was found in the media library', '', '', $actual_url, $file_name, $title, $caption, $alt_text, $description, 'Failed', $media_type, $post_id);
				$wpdb->get_results("UPDATE $log_table_name SET  failed = $failed_count WHERE $unikey_name = '$unikey'");
				return array('MODE' => $mode, 'ERROR_MSG' => "Can't insert this Image");
			}
		}
	}

	public function media_data($mode, $core_instance, $line_number, $message, $attach_id, $file_url = null, $actual_url = null, $file_name = null, $title = null, $caption = null, $alt_text = null, $description = null, $status = null, $media_type = null, $post_id = null)
	{
		$data = array();

		if ($mode == 'Insert') {
			if ($media_type == 'Local') {
				$data = array('media_id' => $attach_id, 'file_url' => $file_url, 'file_name' => $file_name, 'title' => $title, 'caption' => $caption, 'alt_text' => $alt_text, 'description' => $description, 'status' => $status);
			} else if ($media_type == 'External') {
				$data = array('media_id' => $attach_id, 'file_url' => $file_url, 'actual_url' => $actual_url, 'file_name' => $file_name, 'title' => $title, 'caption' => $caption, 'alt_text' => $alt_text, 'description' => $description, 'status' => $status);
			}
		} else if ($mode == 'Update') {
			if (!empty($post_id) && isset($post_id)) {
				$data = array('post_id' => $post_id, 'media_id' => $attach_id, 'file_url' => $file_url, 'actual_url' => $actual_url, 'status' => $status);
			} else {
				$data = array('media_id' => $attach_id, 'file_url' => $file_url, 'actual_url' => $actual_url, 'title' => $title, 'caption' => $caption, 'alt_text' => $alt_text, 'description' => $description, 'status' => $status);
			}
		}
		$core_instance->media_log[$line_number] = $data;
		$core_instance->detailed_log[$line_number]['Message'] = $message;
		$core_instance->detailed_log[$line_number]['state'] = $status;
	}


	public function imageImport($attach_id, $data_array, $media_type)
	{
		global $wpdb;
		if(!empty($data_array['file_name']) && $media_type == 'Local'){
			$sanitized_filename = str_replace(' ', '-', basename($data_array['file_name']));
			$file_name = preg_replace('/[^a-zA-Z0-9._\-\s]/', '', $sanitized_filename);
		}else{
			$file_name = isset($data_array['file_name']) ? $data_array['file_name'] : '';
		}

		$title = isset($data_array['title']) ? $data_array['title'] : '';
		$caption = isset($data_array['caption']) ? $data_array['caption'] : '';
		$alt_text = isset($data_array['alt_text']) ? $data_array['alt_text'] : '';
		$description = isset($data_array['description']) ? $data_array['description'] : '';
		if (isset($caption) || isset($description)) {
    $caption = wp_strip_all_tags($caption);
    $caption = mb_strimwidth($caption, 0, 60000, '...');
    $description = wp_kses_post($description);

    $updated = wp_update_post(array(
        'ID'           => $attach_id,
        'post_content' => $description,
        'post_excerpt' => $caption
    ));
}
		if (!empty($title)) {
			wp_update_post(array(
				'ID'           => $attach_id,
				'post_title'   => $title
			));
		}
		if (isset($alt_text)) {
			$updated = update_post_meta($attach_id, '_wp_attachment_image_alt', $alt_text);
		}

		if (!empty($file_name)) {
			$attachment = get_post($attach_id);
			if ($attachment) {
				$file = get_attached_file($attach_id);
				$new_file = dirname($file) . '/' . $file_name;
				$result = rename($file, $new_file);
				if ($result) {
					$upload_dir = wp_upload_dir();
					$new_file_url = $upload_dir['baseurl'] . '/' . _wp_relative_upload_path($new_file);
					$wpdb->update($wpdb->posts, array('guid' => $new_file_url), array('ID' => $attach_id), array('%s'), array('%d'));
					update_attached_file($attach_id, $new_file);
					$attach_data = wp_generate_attachment_metadata($attach_id, $new_file);
					wp_update_attachment_metadata($attach_id, $attach_data);
				}
			}
		}
	}
}
