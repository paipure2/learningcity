<?php
namespace Smackcoders\WCSV;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class GoogleSheetsExporter {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $access_token;
    private $refresh_token;

    public function __construct() {
        add_action('wp_ajax_export_to_gsheet', [$this, 'handle_export_to_gsheet']);
    }

    public function load_google_credentials() {
        $credentials = maybe_unserialize(get_option('google_sheet_credentials'));
        if ($credentials) {
            $this->client_id = $credentials['client_id'];
            $this->client_secret = $credentials['client_secret'];
            $this->redirect_uri = $credentials['redirect_uri'];
            
            // Ensure access_token is treated as an array
            $this->access_token = maybe_unserialize($credentials['access_token']);
            if (!is_array($this->access_token)) {
                // Attempt to convert it to an array if necessary
                $this->access_token = ['access_token' => $this->access_token]; // Adjust based on your needs
            }

            // Ensure 'expires_at' is available in access token
            if (!isset($this->access_token['expires_at'])) {
                // Set it to a default past time to force token refresh
                $this->access_token['expires_at'] = 0;
            }
            $this->refresh_token = $credentials['refresh_token'] ?? '';

        } else {
            wp_die('Error: Google credentials not found.');
        }
    }

    public function handle_export_to_gsheet() {
        check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');

        // Check for valid nonce
        if (!isset($_POST['securekey']) || !wp_verify_nonce($_POST['securekey'], 'smack-ultimate-csv-importer-pro')) {
            wp_send_json_error(['message' => 'Invalid security token']);
            return;
        }

        $this->load_google_credentials(); // Load credentials

        // Get the CSV file path and other parameters
        $file_name = sanitize_text_field($_POST['fileName']);
        if (pathinfo($file_name, PATHINFO_EXTENSION) !== 'csv') {
            $file_name .= '.csv';
        }

        $csv_file_path = WP_CONTENT_DIR . "/uploads/smack_uci_uploads/exports/" . $file_name;
        if (!file_exists($csv_file_path)) {
            wp_send_json_error(['message' => 'CSV file not found']);
            return;
        }

        // Now, upload the CSV to Google Sheets
        $gsheet_url = $this->upload_csv_to_gsheet($csv_file_path);
        if ($gsheet_url) {
            wp_send_json_success(['gsheet_url' => $gsheet_url]);
        } else {
            wp_send_json_error(['message' => 'Failed to upload to Google Sheets', 'details' => 'Check your credentials or try again later.']);
        }
    }
    public function upload_csv_to_gsheet($csv_file_path) {
        // Load credentials
        $credentials = maybe_unserialize(get_option('google_sheet_credentials'));
    
        if (!$credentials) {
            wp_die('Error: Google Sheets credentials are not available.');
        }
    
        // Refresh token if expired
        $access_token = $this->get_access_token();
    
        // Step 1: Create a new Google Sheet
        $create_sheet_url = 'https://sheets.googleapis.com/v4/spreadsheets';
        $create_sheet_response = wp_remote_post($create_sheet_url, [
            'method' => 'POST',
            'body' => json_encode([
                'properties' => [
                    'title' => 'Exported CSV Data'
                ]
            ]),
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ]
        ]);
    
        // Check for errors in creating the sheet
        if (is_wp_error($create_sheet_response)) {
            wp_die('Error creating Google Sheets: ' . $create_sheet_response->get_error_message());
        }
    
        // Decode response body
        $create_sheet_body = json_decode(wp_remote_retrieve_body($create_sheet_response), true);
        
    
        // Check if spreadsheetId is present in response
        if (!isset($create_sheet_body['spreadsheetId'])) {
            wp_die('Error: Unable to retrieve spreadsheet ID from Google Sheets API. Response: ' . print_r($create_sheet_body, true));
        }
    
        $spreadsheetId = $create_sheet_body['spreadsheetId'];
    
        $csv_data = [];
        $handle = fopen($csv_file_path, 'r');
        if ($handle !== false) {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                // Combine multi-line content in the 3rd column (post_content)
                foreach ($row as &$field) {
                    $field = preg_replace('/[\r\n]+/', ' ', $field); // Replace line breaks with spaces
                }
                $csv_data[] = $row;
            }
            fclose($handle);
        } else {
            wp_die('Error: Unable to open CSV file for reading.');
        }
        
    
        // Ensure that CSV data is valid and has rows
        if (empty($csv_data)) {
            wp_die('Error: CSV file is empty or not properly formatted.');
        }
    
    
        // Step 3: Prepare and send data to Google Sheets
        $update_data_url = 'https://sheets.googleapis.com/v4/spreadsheets/' . $spreadsheetId . '/values/Sheet1!A1:append?valueInputOption=RAW';
        
        // Prepare body for appending data
        $body = [
            'values' => $csv_data,
            'majorDimension' => 'ROWS'
        ];
    
        $update_data_response = wp_remote_post($update_data_url, [
            'method' => 'POST',
            'body' => json_encode($body),
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ]
        ]);
    
        // Check for errors in uploading data
        if (is_wp_error($update_data_response)) {
            wp_die('Error uploading data to Google Sheets: ' . $update_data_response->get_error_message());
        }
    
        // Log API response for debugging
        $response_body = json_decode(wp_remote_retrieve_body($update_data_response), true);
        
        // Check for errors in response
        if (isset($response_body['error'])) {
            wp_die('Error uploading data to Google Sheets: ' . $response_body['error']['message']);
        }
    
        // Step 4: Return the Google Sheets URL
        return "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit";
    }
    
    private function get_access_token() {
     if ($this->is_access_token_expired()) {
         $this->refresh_access_token();
     }
 
     if (is_array($this->access_token) && isset($this->access_token['access_token'])) {
         return $this->access_token['access_token'];
     }
 
     return '';
 }
    
    
    private function is_access_token_expired() {
        // Check if the access token has expired
        return isset($this->access_token['expires_at']) && time() > $this->access_token['expires_at'];
    }
    

    private function refresh_access_token() {
        // Make REST API request to refresh token
        $url = 'https://oauth2.googleapis.com/token';
        $response = wp_remote_post($url, [
            'method'    => 'POST',
            'body'      => http_build_query([
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token'
            ]),
            'headers'   => [
                'Content-Type'  => 'application/x-www-form-urlencoded'
            ]
        ]);
    
        if (is_wp_error($response)) {
            wp_die('Error refreshing token: ' . $response->get_error_message());
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['access_token'])) {
            // Update access token and expiry
            $this->access_token = [
                'access_token' => $body['access_token'],
                'expires_at' => time() + $body['expires_in']
            ];
            
            // Update stored credentials
            $credentials = maybe_unserialize(get_option('google_sheet_credentials'));
            $credentials['access_token'] = maybe_serialize($this->access_token);
            update_option('google_sheet_credentials', maybe_serialize($credentials));
        }
    }
}    

new GoogleSheetsExporter();
