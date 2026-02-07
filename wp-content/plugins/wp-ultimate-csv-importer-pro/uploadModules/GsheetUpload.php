<?php

namespace Smackcoders\WCSV;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class GoogleSheetUpload
{
    private $client_id;
    private $client_secret;
    private $redirect_url;

    public function __construct()
    {
        $this->doHooks();
    }

    public function doHooks()
    {
        // AJAX handlers
        add_action('wp_ajax_gsheet_getauthorise', [$this, 'gsheet_getauthorise']);
        add_action('wp_ajax_nopriv_gsheet_getauthorise', [$this, 'gsheet_getauthorise']);

        add_action('wp_ajax_getaccess_token', [$this, 'getaccess_token']);
        add_action('wp_ajax_nopriv_getaccess_token', [$this, 'getaccess_token']);

        add_action('wp_ajax_handle_auth', [$this, 'handleAuthCallback']);
        add_action('wp_ajax_nopriv_handle_auth', [$this, 'handleAuthCallback']);

        add_action('wp_ajax_refresh_access_token', [$this, 'refreshAccessToken']);
        add_action('wp_ajax_nopriv_refresh_access_token', [$this, 'refreshAccessToken']);

        add_action('wp_ajax_list_sheet_data', [$this, 'listSheetData']);
        add_action('wp_ajax_nopriv_list_sheet_data', [$this, 'listSheetData']);

        add_action('wp_ajax_sheet_list', [$this, 'getSpreadsheetFilesWithGids']);
        add_action('wp_ajax_nopriv_sheet_list', [$this, 'getSpreadsheetFilesWithGids']);

        add_action('wp_ajax_get_sheet_gids', 'handle_get_sheet_gids');
        add_action('wp_ajax_nopriv_get_sheet_gids', [$this, 'handle_get_sheet_gids']);

        add_action('wp_ajax_delete_gsheet_credentials', [$this, 'deleteGSheetCredentials']);

    }

    // Get Authorization URL
    public function gsheet_getauthorise()
    {
        $this->client_id     = sanitize_text_field($_POST['client_id'] ?? '');
        $this->client_secret = sanitize_text_field($_POST['client_secret'] ?? '');
        $this->redirect_url  = esc_url_raw($_POST['redirect_url'] ?? '');

        if (empty($this->client_id) || empty($this->client_secret) || empty($this->redirect_url)) {
            wp_send_json_error(['message' => 'Missing required parameters.']);
        }

        $credentials = maybe_unserialize(get_option('gsheetlisting_oauth_credentials'));
        if (!empty($credentials['refresh_token'])) {
            $current_time = time();
            $expiry_time  = intval($credentials['expires_in'] ?? 0);

            if ($expiry_time <= $current_time) {
                $refreshed = $this->refreshAccessToken();
                if (!$refreshed['success']) {
                    wp_send_json_error([
                        'message' => 'Token refresh failed',
                        'error'   => $refreshed['error'] ?? 'Unknown error'
                    ]);
                }
                // wp_send_json_success([
                //     'message'       => 'Access token refreshed',
                //     'access_token'  => $refreshed['data']['access_token'],
                //     'expires_in'    => $refreshed['data']['expires_in'],
                // ]);
                $this->getSpreadsheetFilesWithGids();
            } else {
                // wp_send_json_success([
                //     'message'      => 'Access token still valid',
                //     'access_token' => $credentials['access_token'],
                //     'expires_in'   => $expiry_time
                // ]);
                $this->getSpreadsheetFilesWithGids();

                // $oauth_url = $this->getoAuth_url();
                // wp_send_json_success(['auth_url' => $oauth_url]);


            }
        } else {
            $oauth_url = $this->getoAuth_url();
            wp_send_json_success(['auth_url' => $oauth_url]);
        }

        wp_die();
    }


    // Exchange authorization code for tokens

    function getaccess_token()
    {

        $url = 'https://oauth2.googleapis.com/token';
        $client_id     = sanitize_text_field($_POST['client_id']);
        $client_secret = sanitize_text_field($_POST['client_secret']);
        $redirect_uri  = esc_url_raw($_POST['redirect_url']);
        $code          = sanitize_text_field($_POST['code']);
        $post_fields = http_build_query([
            'code'          => $code,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
            'grant_type'    => 'authorization_code'
        ]);
        $args = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body'    => $post_fields,
            'timeout' => 20
        ];
        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Request failed',
                'error'   => $response->get_error_message()
            ];
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['error'])) {
            return [
                'success' => false,
                'message' => 'Google returned an error',
                'details' => $body
            ];
        }
        $credentials = [
            'access_token'  => sanitize_text_field($body['access_token']),
            'refresh_token' => sanitize_text_field($body['refresh_token'] ?? ''),
            'expires_in'    => time() + intval($body['expires_in']),
            'token_type'    => sanitize_text_field($body['token_type']),
            'scope'         => sanitize_text_field($body['scope']),
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
        ];

        update_option('gsheetlisting_oauth_credentials', maybe_serialize($credentials));

        // wp_send_json_success([
        //     'message' => 'Access token retrieved successfully',
        //     'data'    => $credentials
        // ]);
        $this->getSpreadsheetFilesWithGids();
    }



    // Use Refresh Token to Get New Access Token
    public function refreshAccessToken()
    {

        $credentials = maybe_unserialize(get_option('gsheetlisting_oauth_credentials'));

        if (empty($credentials['refresh_token'])) {
            wp_send_json_error(['message' => 'Refresh token not available.']);
        }

        $url = 'https://oauth2.googleapis.com/token';

        $post_fields = http_build_query([
            'client_id'     => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
            'refresh_token' => $credentials['refresh_token'],
            'grant_type'    => 'refresh_token'
        ]);

        $args = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body'    => $post_fields,
            'timeout' => 20
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => 'Request failed',
                'error'   => $response->get_error_message()
            ]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            wp_send_json_error([
                'message' => 'Google returned an error',
                'details' => $body
            ]);
        }

        // Merge new token with old credentials
        $credentials['access_token'] = sanitize_text_field($body['access_token']);
        $credentials['expires_in']   = time() + intval($body['expires_in']);
        $credentials['token_type']   = sanitize_text_field($body['token_type']);
        if (isset($body['scope'])) {
            $credentials['scope'] = sanitize_text_field($body['scope']);
        }

        update_option('gsheetlisting_oauth_credentials', maybe_serialize($credentials));

        // wp_send_json_success([
        //     'message' => 'Access token refreshed successfully',
        //     'data'    => $credentials
        // ]);

        $this->getSpreadsheetFilesWithGids();
    }


    // Build OAuth URL dynamically
    public function getoAuth_url()
    {
        $params = [
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_url,
            'response_type' => 'code',
            'scope'         => implode(' ', [
                'https://www.googleapis.com/auth/drive.readonly',
                'https://www.googleapis.com/auth/spreadsheets',
            ]),
            'access_type'   => 'offline',
            'prompt'        => 'consent'
        ];

        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }



    // Handle the callback from Google (you need to route this in WordPress)
    public function handleAuthCallback()
    {
        // Example: retrieve the 'code' parameter
        $code = sanitize_text_field($_GET['code'] ?? '');

        if (empty($code)) {
            wp_send_json_error(['message' => 'No code provided.']);
        }

        // Here you could exchange the code for tokens, or handle it differently
        wp_send_json_success(['message' => 'Callback handled.', 'code' => $code]);
    }


    function getSpreadsheetFilesWithGids()
    {
        $credentials = maybe_unserialize(get_option('gsheetlisting_oauth_credentials'));

        if (empty($credentials['access_token'])) {
            wp_send_json_error(['message' => 'Access token not found. Please authorize first.']);
        }

        $access_token = $credentials['access_token'];

        $drive_url = "https://www.googleapis.com/drive/v3/files";
        $drive_params = http_build_query([
            "q" => "mimeType='application/vnd.google-apps.spreadsheet'",
            "fields" => "nextPageToken,files(id,name)",
            "pageSize" => 1000
        ]);

        $drive_full_url = $drive_url . "?" . $drive_params;

        $headers = [
            "Authorization: Bearer $access_token"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $drive_full_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $drive_response = curl_exec($ch);
        if (curl_errno($ch)) {
            return ['error' => 'Drive API Error: ' . curl_error($ch)];
        }

        $drive_data = json_decode($drive_response, true);
        $files = $drive_data['files'] ?? [];

        $result = [];

        foreach ($files as $file) {
            $file_id = $file['id'];
            $result[] = [
                'sheet_title'    => $file['name'],
                'edit_url'       => "https://docs.google.com/spreadsheets/d/$file_id/edit",
                'csv_export_url' => "https://docs.google.com/spreadsheets/d/$file_id/export?format=csv&gid=0"
            ];
        }

        curl_close($ch);

        wp_send_json_success(['sheets' => $result]);
    }

    function handle_get_sheet_gids()
    {

        $file_id = $_GET['file_id'] ?? '';
        if (!$file_id) {
            wp_send_json_error(['message' => 'Missing file_id']);
        }
        $result = $this->getSpreadsheetGidsFromId($file_id);
        if (isset($result['error'])) {
            wp_send_json_error(['message' => $result['error']]);
        }

        wp_send_json_success($result);
    }

    function getSpreadsheetGidsFromId($file_id)
    {
        $credentials = maybe_unserialize(get_option('gsheetlisting_oauth_credentials'));

        if (empty($credentials['access_token'])) {
            return ['error' => 'Access token not found. Please authorize first.'];
        }

        $access_token = $credentials['access_token'];

        $sheets_url = "https://sheets.googleapis.com/v4/spreadsheets/{$file_id}?fields=sheets.properties";

        $headers = [
            "Authorization: Bearer $access_token"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sheets_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $sheets_response = curl_exec($ch);

        if (curl_errno($ch)) {
            return ['error' => 'Sheets API Error: ' . curl_error($ch)];
        }

        $sheets_data = json_decode($sheets_response, true);
        $sheets = $sheets_data['sheets'] ?? [];

        $result = [];

        foreach ($sheets as $sheet) {
            $props = $sheet['properties'];
            $sheet_title = $props['title'];
            $gid = $props['sheetId'];

            $result[] = [
                'sheet_title' => $sheet_title,
                'gid' => $gid,
                'csv_export_url' => "https://docs.google.com/spreadsheets/d/$file_id/export?format=csv&gid=$gid",
                'edit_url' => "https://docs.google.com/spreadsheets/d/$file_id/edit#gid=$gid"
            ];
        }

        curl_close($ch);

        return ['sheets' => $result];
    }

    public function deleteGSheetCredentials()
{
    check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');

    $credentials = get_option('gsheetlisting_oauth_credentials');
    if ($credentials === false) {
        wp_send_json_error(['message' => 'No Google Sheet credentials found']);
    }

    delete_option('gsheetlisting_oauth_credentials');

    wp_send_json_success(['message' => 'Google Sheet credentials deleted successfully']);
    wp_die();
}

}


new GoogleSheetUpload();



// must be enabled - list out the files from sheet
// https://console.developers.google.com/apis/api/drive.googleapis.com/overview?project=projectid
// https://console.developers.google.com/apis/api/sheets.googleapis.com/overview?project=projectid

