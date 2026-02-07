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

class LifterLmsExtension extends ExtensionHandler{
	private static $instance = null;

    public static function getInstance() {		
        if (LifterLmsExtension::$instance == null) {
            LifterLmsExtension::$instance = new LifterLmsExtension;
        }
        return LifterLmsExtension::$instance;
    }



    public function processExtension($data){   
            $mode = isset($_POST['Mode']) ? sanitize_text_field($_POST['Mode']) : "";     
            $import_type = $data;
            $response = [];
            $lifter_section_meta_fields = [];
            //$import_type = $this->import_type_as($import_type);
            if(is_plugin_active('lifterlms/lifterlms.php')){   
                if($import_type == 'course'){
                    $lifter_meta_fields = array(
                        'Instructors' => '_llms_instructors',
                        'Blocks Migrated' => '_llms_blocks_migrated',
                        'Sales Page Content Type'=> '_llms_sales_page_content_type',
                        'Sales Page Content Id' => '_llms_sales_page_content_page_id',
                        'Sales Page Content Url' => '_llms_sales_page_content_url',
                        'Course Difficulty' => '_llms_post_course_difficulty',
                        'Video Embed' => '_llms_video_embed',
                        'Featured Video' => '_llms_tile_featured_video',
                        'Audio Embed' => '_llms_audio_embed',
                        'Content Restricted Message' => '_llms_content_restricted_message',
                        'Enrollment Period' => '_llms_enrollment_period',
                        'Enrollment Start Date' => '_llms_enrollment_start_date',
                        'Enrollment End Date' => '_llms_enrollment_end_date',
                        'Enrollment Opens Message' => '_llms_enrollment_opens_message',
                        'Enrollment Closed Message' => '_llms_enrollment_closed_message',
                        'Time Period' => '_llms_time_period',
                        'Start Date' => '_llms_start_date',
                        'End Date' => '_llms_end_date',
                        'Course Opens Message' => '_llms_course_opens_message',
                        'Course Closed Message' => '_llms_course_closed_message',
                        'Has Prerequisite' => '_llms_has_prerequisite',
                        'Prerequisite' => '_llms_prerequisite',
                        'Prerequisite track' => '_llms_prerequisite_track',
                        'Enable Capacity' => '_llms_enable_capacity',
                        'Capacity' => '_llms_capacity',                            
                        'Capacity Message' => '_llms_capacity_message',                            
                        'Reviews Enabled' => '_llms_reviews_enabled',                            
                        'Display Reviews' => '_llms_display_reviews',                            
                        'Num Reviews' => '_llms_num_reviews',                            
                        'Multiple Reviews Disabled' => '_llms_multiple_reviews_disabled',  
                        'Length' => '_llms_length',
                        'Average Progress' =>'_llms_average_progress',                              
                        'Enrolled Students' =>'_llms_enrolled_students',                              
                        'Last Data Calc Run' =>'_llms_last_data_calc_run',                              
                        'Average Grade' =>'_llms_average_grade',   
                        'Access Plan' => 'llms_access_plan',
                        'Enroll Text' => '_llms_enroll_text',                           
                        'Sku' => '_llms_sku',                           
                        'Trial Offer' => '_llms_trial_offer',                           
                        'Price' => '_llms_price',                           
                        'On Sale' => '_llms_on_sale',                           
                        'Is Free' => '_llms_is_free',                           
                        'Frequency' => '_llms_frequency',                           
                        'Checkout Redirect Type' => '_llms_checkout_redirect_type',                           
                        'Checkout Redirect Forced' => '_llms_checkout_redirect_forced',                           
                        'Availability' => '_llms_availability',                           
                        'Access Expiration' => '_llms_access_expiration',                           
                    );

                    if($mode == 'Insert'){
                        unset($lifter_section_meta_fields['Lesson Id']);
                        unset($lifter_section_meta_fields['Quiz Id']);
                    }
                }

                global $wpdb;
                $quiz=$wpdb->get_results("SELECT meta_key FROM {$wpdb->prefix}postmeta WHERE post_id = '_llms_quiz'");

                if($import_type == 'lesson'){            
                    $lifter_meta_fields = array(
                        'Blocks Migrated' => '_llms_blocks_migrated',
                        'Reviews Enabled' => '_llms_reviews_enabled',
                        'Video Embed' => '_llms_video_embed',
                        'Audio Embed' => '_llms_audio_embed',
                        'Free Lesson' => '_llms_free_lesson',
                        'Has Prerequisite' => '_llms_has_prerequisite',
                        'Prerequisite' => '_llms_prerequisite',
                        'Drip Method' => '_llms_drip_method',
                        'Days Before Available' => '_llms_days_before_available',
                        'Date Available' => '_llms_date_available',
                        'Time Available' => '_llms_time_available',           
                        'Require Passing Grade' =>'_llms_require_passing_grade',  
                        'Random Questions' =>'_llms_random_questions',  
                        'Order' =>'_llms_order',  
                        'Parent Section' =>'_llms_parent_section',  
                        'Require Assignment Passing Grade' =>'_llms_require_assignment_passing_grade',  
                        'Parent Course' =>'_llms_parent_course',  
                        'Points' =>'_llms_points',  
                        'Quiz Enabled' =>'_llms_quiz_enabled',  
                    );
                }

                if($import_type == 'llms_quiz'){            
                   $lifter_meta_fields = array(
    'Lesson ID'                    => '_llms_lesson_id',                 // Linked lesson
    'Limit Attempts'               => '_llms_limit_attempts',            // 'yes' / 'no'
    'Allowed Attempts'             => '_llms_allowed_attempts',          // Number of attempts allowed
    'Limit Time'                   => '_llms_limit_time',                // 'yes' / 'no'
    'Time Limit (minutes)'         => '_llms_time_limit',                // Integer (minutes)
    'Passing Percent'              => '_llms_passing_percent',           // % required to pass
    'Randomize Questions'          => '_llms_random_questions',          // 'yes' / 'no'
    'Show Correct Answer'          => '_llms_show_correct_answer',       // 'yes' / 'no'
    'Can Be Resumed'               => '_llms_can_be_resumed',            // 'yes' / 'no'
    'Disable Retake'               => '_llms_disable_retake',            // 'yes' / 'no'
    'Questions'                    => '_llms_questions',   
    'Description Enabled'          => '_llms_description_enabled',       // 'yes' / 'no' per question, pipe-separated
    'Multi Choices'                => '_llms_multi_choices',             // 'yes' / 'no' per question, pipe-separated
    'Points'                       => '_llms_points',                     // integer per question, pipe-separated
    'Video Enabled'                => '_llms_video_enabled',             // 'yes' / 'no' per question, pipe-separated
    'Video Source'                 => '_llms_video_src',     
);


                    if($mode == 'Insert'){
                        unset($learn_meta_fields['Question Id']);
                    }
                }
                if($import_type == 'llms_coupon'){            
                    $lifter_meta_fields = array(
                        'Enable Trial Discount' => '_llms_enable_trial_discount',
                        'Trial Amount' => '_llms_trial_amount',
                        'Coupon Courses' => '_llms_coupon_courses',
                        'Coupon Membership' => '_llms_coupon_membership',
                        'Coupon Amount' => '_llms_coupon_amount',
                        'Usage Limit' => '_llms_usage_limit',
                        'Discount Type' => '_llms_discount_type',
                        'Description' => '_llms_description',
                        'Expiration Date' => '_llms_expiration_date',
                        'Plan Type' => '_llms_plan_type',
                        'Reviews Enabled'=> '_llms_reviews_enabled',
                        'Display Reviews'=> '_llms_display_reviews',
                        'Num Reviews'=> '_llms_num_reviews',
                        'Multiple Reviews Disabled'=> '_llms_multiple_reviews_disabled'
                    );
                }

                if($import_type == 'llms_review'){                  
                    $lifter_meta_fields = array(
                        'Reviews Enabled' => '_llms_reviews_enabled',
                        'Display Reviews' => '_llms_display_reviews',
                        'Num Reviews' => '_llms_num_reviews',
                        'Multiple Reviews Disabled' => '_llms_multiple_reviews_disabled',
                    );
                }
        }

        $lifter_meta_fields_line = $this->convert_static_fields_to_array($lifter_meta_fields);
        
        if($data == 'course'){
            $lifter_section_meta_fields_line = isset($lifter_section_meta_fields) ? $this->convert_static_fields_to_array($lifter_section_meta_fields) : '';

            $response['lifter_course_settings_fields'] = $lifter_meta_fields_line; 
        }
        if($data == 'lesson'){
            $response['lifter_lesson_settings_fields'] = $lifter_meta_fields_line; 
        }
        if($data == 'llms_quiz'){
            $response['lifter_quiz_settings_fields'] = $lifter_meta_fields_line; 
        }  
        if($data == 'llms_coupon'){
            $response['lifter_coupon_settings_fields'] = $lifter_meta_fields_line; 
        }  
        if($data == 'llms_review'){
            $response['lifter_review_settings_fields'] = $lifter_meta_fields_line; 
        } 
		return $response;
			
    }

    public function extensionSupportedImportType($import_type ){
        if(is_plugin_active('lifterlms/lifterlms.php')){
           // $import_type = $this->import_name_as($import_type);
            if($import_type == 'course' || $import_type == 'lesson' || $import_type == 'llms_quiz' || $import_type == 'llms_coupon' || $import_type == 'llms_review') { 
                return true;
            }else{
                return false;
            }
        }
	}
}