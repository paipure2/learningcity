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
 * Class JetBookingExport
 * @package Smackcoders\WCSV
 */
class JetBookingExport
{

    protected static $instance = null, $mapping_instance, $export_handler, $export_instance, $post_export;
    public $totalRowCount;
    public $plugin;

    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
            JetBookingExport::$export_instance = ExportExtension::getInstance();
            JetBookingExport::$post_export = PostExport::getInstance();
        }
        return self::$instance;
    }

    /**
     * JetBookingExport constructor.
     */
    public function __construct()
    {
        $this->plugin = Plugin::getInstance();
    }

	/**
	 * Fetch Jet booking Data
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
	public function FetchJetBookingData($module, $optionalType, $conditions, $offset, $limit, $is_filter, $headers, $mode = null, $eventExclusions = null) {
        global $wpdb;
        if(!empty($conditions['specific_jetbooking_status']['is_check']) && $conditions['specific_jetbooking_status']['is_check'] == 'true' && !empty($conditions['specific_jetbooking_status']['status']) ) {
            $jet_booking_status = $conditions['specific_jetbooking_status']['status'];
            $total_bookings = jet_abaf_get_bookings( ['status' => $jet_booking_status, 'return' => 'ids']);
            $bookings = jet_abaf_get_bookings( ['status' => $jet_booking_status,'return' => 'arrays','offset' => $offset , 'limit' => $limit]);
            $post_ids = wp_list_pluck($bookings, 'ID');
        }
        else{
            $total_bookings = jet_abaf_get_bookings([
                'return' => 'ids'
            ]);
            $bookings = jet_abaf_get_bookings([
                'return' => 'arrays',
                'limit'  => $limit,
                'offset' => $offset
            ]);
            $post_ids = wp_list_pluck($bookings, 'ID');
        }
        JetBookingExport::$export_instance->totalRowCount = count($total_bookings);
        
        $selected_headers = $headers;
        // Iterate over the bookings and filter the data
        if(!empty($post_ids)){
            foreach($post_ids as $id){
                $booking = jet_abaf_get_booking( $id );
                $post_type = trim(jet_abaf()->settings->get( 'apartment_post_type' ));
                
                if(!empty($booking)){
                    $booking_id = $booking->get_id();
                    $status = $booking->get_status();
                    $apartment_id = $booking->get_apartment_id();
                    $apartment_unit = $booking->get_apartment_unit();
                    $check_in_date  = date( 'Y-m-d', $booking->get_check_in_date());
                    $check_out_date = date( 'Y-m-d', $booking->get_check_out_date() );
                    $order_id = $booking->get_order_id();
                    $user_id = $booking->get_user_id();
                    $import_id = $booking->get_import_id();
                    $guests = $booking->get_guests();
                    $orderStatus = !empty($order_id) ? get_post_status($order_id) : '';

                    if($post_type == 'product'){
                        $attributes = $booking->get_attributes();
                        $attr = [];
                        foreach ($attributes as $key => $values) {
                            $cleanKey = str_replace('pa_', '', $key);
                            $valueString = implode('|', $values);
                            $attr[] = $cleanKey . '->' . $valueString;
                        }
                        $attr = implode(',', $attr);

                        if(!empty($order_id) && isset($order_id)){
                            $order = wc_get_order($order_id);
                            if ($order) {
                                // Get customer information
                                $first_name = $order->get_billing_first_name();
                                $last_name = $order->get_billing_last_name();
                                $email = $order->get_billing_email();
                                $phone = $order->get_billing_phone();
                            }
                        }
                    }

                    // Store the data for this booking, but only for the selected headers
                    $booking_data = [];
                    if (in_array('booking_id', $selected_headers)) $booking_data['booking_id'] = $booking_id ?? '';
                    if (in_array('status', $selected_headers)) $booking_data['status'] = $status ?? '';
                    if (in_array('apartment_id', $selected_headers)) $booking_data['apartment_id'] = $apartment_id ?? '';
                    if (in_array('apartment_unit', $selected_headers)) $booking_data['apartment_unit'] = $apartment_unit ?? '';
                    if (in_array('check_in_date', $selected_headers)) $booking_data['check_in_date'] = $check_in_date ?? '';
                    if (in_array('check_out_date', $selected_headers)) $booking_data['check_out_date'] = $check_out_date ?? '';
                    if (in_array('order_id', $selected_headers)) $booking_data['order_id'] = $order_id ?? '';
                    if (in_array('user_id', $selected_headers)) $booking_data['user_id'] = $user_id ?? '';
                    if (in_array('import_id', $selected_headers)) $booking_data['import_id'] = $import_id ?? '';
                    if (in_array('guests', $selected_headers)) $booking_data['guests'] = $guests ?? '';
                    if (in_array('orderStatus', $selected_headers)) $booking_data['orderStatus'] = $orderStatus ?? '';
                    if (in_array('attributes', $selected_headers)) $booking_data['attributes'] = $attr ?? '';
                    if (in_array('firstName', $selected_headers)) $booking_data['firstName'] = $first_name ?? '';
                    if (in_array('lastName', $selected_headers)) $booking_data['lastName'] = $last_name ?? '';
                    if (in_array('email', $selected_headers)) $booking_data['email'] = $email ?? '';
                    if (in_array('phone', $selected_headers)) $booking_data['phone'] = $phone ?? '';

                    // Store filtered data in the export instance
                    JetBookingExport::$export_instance->data[$booking_id] = $booking_data;
                }
            }
            // Prepare the final export data based on filtered headers
            $result = JetBookingExport::$export_instance->finalDataToExport(JetBookingExport::$export_instance->data);

            // Return or proceed with the export depending on the mode
            if($is_filter == 'filter_action'){
                return $result;
            }

            if($mode == null)
                JetBookingExport::$export_instance->proceedExport($result);
            else
                return $result;
        }

    }
}
