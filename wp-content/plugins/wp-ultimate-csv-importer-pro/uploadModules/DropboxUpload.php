<?php

namespace Smackcoders\WCSV;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class DropboxOAuthHandler
{
    private $client_id;
    private $client_secret;
    private $redirect_uri;

    public function __construct()
    {
        $this->doHooks();
    }

    public function doHooks()
    {
        add_action('wp_ajax_getauthorise', [$this, 'getauthorise']);
        add_action('wp_ajax_nopriv_getauthorise', [$this, 'getauthorise']);

        add_action('wp_ajax_getaccess_token', [$this, 'getaccess_token']);
        add_action('wp_ajax_nopriv_getaccess_token', [$this, 'getaccess_token']);

        add_action('wp_ajax_handle_auth', [$this, 'handleAuthCallback']);
        add_action('wp_ajax_nopriv_handle_auth', [$this, 'handleAuthCallback']);

        add_action('wp_ajax_droprefresh_access_token', [$this, 'refreshAccessToken']);
        add_action('wp_ajax_nopriv_droprefresh_access_token', [$this, 'refreshAccessToken']);

        add_action('wp_ajax_list_sheet_data', [$this, 'listSheetData']);
        add_action('wp_ajax_nopriv_list_sheet_data', [$this, 'listSheetData']);

        add_action('wp_ajax_sheet_list', [$this, 'getSpreadsheetFilesWithGids']);
        add_action('wp_ajax_nopriv_sheet_list', [$this, 'getSpreadsheetFilesWithGids']);

        add_action('wp_ajax_get_sheet_gids', [$this, 'handle_get_sheet_gids']);
        add_action('wp_ajax_nopriv_get_sheet_gids', [$this, 'handle_get_sheet_gids']);

        add_action('wp_ajax_get_sharedlink', [$this, 'getSharedLink']);
        add_action('wp_ajax_nopriv_get_sharedlink', [$this, 'getSharedLink']);

        add_action('wp_ajax_delete_dropbox_credentials', [$this, 'deleteDropboxCredentials']);

    }


    // Get Authorization URL
    public function getauthorise()
    {
        $this->client_id     = sanitize_text_field($_POST['client_id'] ?? '');
        $this->client_secret = sanitize_text_field($_POST['client_secret'] ?? '');
        $this->redirect_uri  = esc_url_raw($_POST['redirect_uri'] ?? '');
        if (empty($this->client_id) || empty($this->client_secret) || empty($this->redirect_uri)) {
            wp_send_json_error(['message' => 'Missing required parameters.']);
        }
        $credentials = maybe_unserialize(get_option('dropbox_credentials'));
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

                $this->getDropboxFilesWithGids();
            } else {
                $this->getDropboxFilesWithGids();
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

        $client_id     = sanitize_text_field($_POST['client_id']);
        $client_secret = sanitize_text_field($_POST['client_secret']);
        $redirect_uri  = esc_url_raw($_POST['redirect_uri']);
        $code          = sanitize_text_field($_POST['code']);
        $response = wp_remote_post("https://api.dropboxapi.com/oauth2/token", [
            'body' => [
                "code"         => $code,
                "grant_type"   => "authorization_code",
                "redirect_uri" => $redirect_uri,
            ],
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
                'Content-Type'  => 'application/x-www-form-urlencoded'
            ]
        ]);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['error'])) {
            return [
                'success' => false,
                'message' => 'Dropbox returned an error',
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

        update_option('dropbox_credentials', maybe_serialize($credentials));
        $this->getDropboxFilesWithGids();
    }

    // Use Refresh Token to Get New Access Token
    public function refreshAccessToken()
    {
        $credentials = maybe_unserialize(get_option('dropbox_credentials'));

        if (empty($credentials['refresh_token'])) {
            wp_send_json_error(['message' => 'Refresh token not available.']);
        }

        $url = 'https://api.dropboxapi.com/oauth2/token';

        $post_fields = http_build_query([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $credentials['refresh_token']
        ]);

        $args = [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($credentials['client_id'] . ':' . $credentials['client_secret']),
                'Content-Type'  => 'application/x-www-form-urlencoded'
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
                'message' => 'Dropbox returned an error',
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

        update_option('dropbox_credentials', maybe_serialize($credentials));
        $this->getDropboxFilesWithGids();
    }

    // Build OAuth URL dynamically
    public function getoAuth_url()
    {
        $scopes = "files.metadata.read files.content.read files.content.write sharing.write";
        $auth_url = "https://www.dropbox.com/oauth2/authorize?" . http_build_query([
            "response_type"    => "code",
            "client_id"        => $this->client_id,
            "redirect_uri"     => $this->redirect_uri,
            "token_access_type" => "offline",
            "scope"            => $scopes
        ]);
        return $auth_url;
    }

    // Handle the callback from DropBox (you need to route this in WordPress)

    public function handleAuthCallback()
    {
        $code = sanitize_text_field($_POST['code'] ?? '');
        $client_id = sanitize_text_field($_POST['client_id'] ?? '');
        $client_secret = sanitize_text_field($_POST['client_secret'] ?? '');
        $redirect_uri = esc_url_raw($_POST['redirect_uri'] ?? '');
        if (empty($code) || empty($client_id) || empty($client_secret) || empty($redirect_uri)) {
            wp_send_json_error(['message' => 'Missing parameters']);
        }
        $_POST['client_id'] = $client_id;
        $_POST['client_secret'] = $client_secret;
        $_POST['redirect_uri'] = $redirect_uri;
        $_POST['code'] = $code;
        $this->getaccess_token();
    }



    public function getDropboxFilesWithGids()
    {
        $credentials = maybe_unserialize(get_option('dropbox_credentials'));

        if (!$credentials || !isset($credentials['access_token'])) {
            wp_send_json_error(['message' => 'No access token found']);
        }

        if (time() > $credentials['expires_in']) {
            $newToken = $this->refreshAccessToken($credentials['refresh_token']);
            if (isset($newToken['access_token'])) {
                $credentials['access_token'] = $newToken['access_token'];
                $credentials['expires_in']   = $newToken['expires_in'];
                $credentials['expires_in']   = time() + $newToken['expires_in'];
                update_option('dropbox_credentials', maybe_serialize($credentials));
            }
        }

        $credentials = maybe_unserialize(get_option('dropbox_credentials'));
        $files = $this->listFiles($credentials['access_token']);

        if (!$files || !isset($files['entries'])) {
            wp_send_json_error(['message' => 'Failed to fetch files']);
        }

        // Build nested tree structure
        $tree = $this->buildTree($files['entries']);
        // Wrap into "data" -> "entries"
        wp_send_json_success([
            'entries' => $tree
        ]);
        wp_die();
    }

    private function buildTree(array $items)
    {
        $tree = [];
        $refs = [];

        foreach ($items as $item) {
            $item['sub_entries'] = []; // Initialize
            $refs[$item['path_lower']] = $item;
        }

        foreach ($refs as $path => &$item) {
            $parentPath = dirname($path);
            if ($parentPath !== "." && isset($refs[$parentPath])) {
                $refs[$parentPath]['sub_entries'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }

        return $tree;
    }

    private function listFiles($access_token, $path = "")
    {
        $response = wp_remote_post("https://api.dropboxapi.com/2/files/list_folder", [
            'body' => json_encode([
                "path" => $path,
                "recursive" => true,   // Enable recursive listing
                "include_media_info" => false,
                "include_deleted" => false,
                "include_has_explicit_shared_members" => false
            ]),
            'headers' => [
                "Authorization" => "Bearer {$access_token}",
                "Content-Type"  => "application/json"
            ]
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function getSharedLink($filepath)
    {
        $credentials = maybe_unserialize(get_option('dropbox_credentials'));
        $credentials = maybe_unserialize(get_option('dropbox_credentials'));
        $access_token = $credentials['access_token'];
        $filepath = $_GET['path'];
        $path = trim($filepath, '"\\');

        if ($path === '/' || empty($path)) {
            return wp_send_json_error("Cannot share the root folder.");
        }
        $linkResponse = $this->createSharedLink($access_token, $path);
        if (isset($linkResponse['url'])) {
            return wp_send_json_success([
                'file' => $path,
                'link' => $linkResponse['url']
            ]);
        } else {
            if (isset($linkResponse['error']['.tag']) && $linkResponse['error']['.tag'] === 'shared_link_already_exists') {
                $existing = $this->listSharedLinks($access_token, $path);
                return wp_send_json_success([
                    'file' => $path,
                    'link' => $existing['links'][0]['url'] ?? null
                ]);
            }

            return wp_send_json_error($linkResponse);
        }
    }

    public function createSharedLink($access_token, $path)
    {

        $response = wp_remote_post("https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings", [
            'headers' => [
                "Authorization" => "Bearer {$access_token}",
                "Content-Type"  => "application/json"
            ],
            'body' => json_encode([
                "path" => $path,
                "settings" => [
                    "requested_visibility" => "public"
                ]
            ])
        ]);

        if (is_wp_error($response)) {
            return false;
        }
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function listSharedLinks($access_token, $path)
    {

        $response = wp_remote_post("https://api.dropboxapi.com/2/sharing/list_shared_links", [
            'headers' => [
                "Authorization" => "Bearer {$access_token}",
                "Content-Type"  => "application/json"
            ],
            'body' => json_encode([
                "path" => $path,
                "direct_only" => true
            ])
        ]);

        if (is_wp_error($response)) {
            return [];
        }
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function deleteDropboxCredentials()
{
    check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');

    $credentials = get_option('dropbox_credentials');
    if ($credentials === false) {
        wp_send_json_error(['message' => 'No Dropbox credentials found']);
    }

    delete_option('dropbox_credentials');

    wp_send_json_success(['message' => 'Dropbox credentials deleted successfully']);
    wp_die();
}

}

new DropboxOAuthHandler();
