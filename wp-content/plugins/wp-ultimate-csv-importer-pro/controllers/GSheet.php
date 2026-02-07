<?php
namespace Smackcoders\WCSV;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class GoogleOAuthHandler {
    private $client_id;
    private $client_secret;
    private $redirect_uri;

    public function __construct() {
        $this->doHooks();
    }

    public function doHooks() {
        // Handle the AJAX request to start the OAuth process
        add_action('wp_ajax_handle_google_oauth_request', [$this, 'handle_google_oauth_request']);
        add_action('wp_ajax_nopriv_handle_google_oauth_request', [$this, 'handle_google_oauth_request']);
        
        // Handle the OAuth callback for admin URL
        add_action('admin_init', [$this, 'handle_google_oauth_callback']);
        
        add_action('wp_ajax_check_google_sheet_credentials', [$this, 'check_credentials_saved']);
        add_action('wp_ajax_nopriv_check_google_sheet_credentials', 'check_credentials_saved'); // If you want to handle non-logged-in users

    }

    // Handle the AJAX request from the React form
    public function handle_google_oauth_request() {
        if (!check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            wp_die();
        }

        // Sanitize and validate the incoming data
        $this->client_id = sanitize_text_field($_POST['clientId']);
        $this->client_secret = sanitize_text_field($_POST['clientSecret']);
        $this->redirect_uri = esc_url_raw($_POST['redirectUri']);

        if (empty($this->client_id) || empty($this->client_secret) || empty($this->redirect_uri)) {
            wp_send_json_error(['message' => 'All fields are required.']);
            wp_die();
        }

        // Save credentials in the database
        update_option('google_sheet_credentials', maybe_serialize([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->redirect_uri,
        ]));

        // Generate OAuth URL
        $oauth_url = $this->getAuthorizationUrl();
        wp_send_json_success(['oauthUrl' => $oauth_url]);
        wp_die();
    }

    // Generate the Google OAuth URL
    private function getAuthorizationUrl() {
        $authorizationEndpoint = 'https://accounts.google.com/o/oauth2/auth';
        $queryParameters = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return $authorizationEndpoint . '?' . $queryParameters;
    }

    // Handle the OAuth callback for admin URLs
    public function handle_google_oauth_callback() {
        if (isset($_GET['page']) && $_GET['page'] === 'com.smackcoders.csvimporternewpro.menu' && isset($_GET['code'])) {

            // Fetch stored credentials from the database
            $credentials = maybe_unserialize(get_option('google_sheet_credentials'));
            
            if (!$credentials) {
                wp_die('Error: Client credentials not found.');
            }

            $this->client_id = $credentials['client_id'];
            $this->client_secret = $credentials['client_secret'];
            $this->redirect_uri = $credentials['redirect_uri'];

            $tokenResponse = $this->getAccessToken($_GET['code']);
            
            if (isset($tokenResponse['access_token'])) {
                // Save the access token, refresh token, and expiration time
                $credentials = array_merge($credentials, $tokenResponse);
                $credentials['expires_at'] = time() + $tokenResponse['expires_in']; // Save the token expiry time
                
                // Update the database with the new credentials
                update_option('google_sheet_credentials', maybe_serialize($credentials));

                // after test uncomment the wp_redirect line 
                wp_redirect(admin_url('admin.php?page=com.smackcoders.csvimporternewpro.menu')); 
                exit;
            } else {
                wp_die('Error: Access token not received.');
            }
        }
    }

    // Exchange the authorization code for an access token
    private function getAccessToken($code) {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $code,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => $this->redirect_uri,
                'grant_type' => 'authorization_code',
            ],
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        ]);
        if (is_wp_error($response)) {
            error_log('Token request failed: ' . $response->get_error_message());
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    // Check if the token is expired and refresh it
    private function checkAndRefreshToken() {
        // Fetch stored credentials
        $credentials = maybe_unserialize(get_option('google_sheet_credentials'));
        
        // Check if the access token has expired
        if (time() > $credentials['expires_at']) {
            // Token is expired, refresh it
            $new_token_response = $this->refreshAccessToken($credentials['refresh_token']);
            
            if (isset($new_token_response['access_token'])) {
                // Update credentials with new access token and expiry time
                $credentials['access_token'] = $new_token_response['access_token'];
                $credentials['expires_at'] = time() + $new_token_response['expires_in'];
                
                // Save updated credentials
                update_option('google_sheet_credentials', maybe_serialize($credentials));
            }
        }

        return $credentials['access_token'];
    }

    // Refresh the access token using the refresh token
    private function refreshAccessToken($refresh_token) {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ],
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        ]);

        if (is_wp_error($response)) {
            error_log('Refresh token request failed: ' . $response->get_error_message());
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
    // Add this method to check if credentials are saved
    public function check_credentials_saved() {
        error_log('triigered');

    if (!check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey', false)) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }

    $credentials = maybe_unserialize(get_option('google_sheet_credentials'));

    if ($credentials && isset($credentials['client_id'], $credentials['client_secret'], $credentials['redirect_uri'])) {
        wp_send_json_success(['message' => 'Credentials are saved.']);
    } else {
        wp_send_json_error(['message' => 'Credentials are not saved.']);
    }
    wp_die();
}
}

// Initialize the GoogleOAuthHandler
new GoogleOAuthHandler();
