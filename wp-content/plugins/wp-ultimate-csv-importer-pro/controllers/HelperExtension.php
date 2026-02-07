<?php
/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;

if (! defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Class HelperExtension
 * @package Smackcoders\WCSV
 */
class HelperExtension
{
    protected static $instance = null;
    public $plugin;

    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
            self::$instance->doHooks();
        }
        return self::$instance;
    }

    /**
     * HelperExtension constructor.
     */
    public function __construct()
    {
        $this->plugin = Plugin::getInstance();
    }


    /**
     * HelperExtension hooks.
     */
    public function doHooks()
    {
		add_action('wp_ajax_support_mail', array($this,'HelperImport'));
        add_action('wp_ajax_helperImport', array($this, 'HelperImport'));
        add_action('wp_ajax_helperSearch', array($this, 'SearchWord'));
    }
    public static function SearchWord()
    {
        self::handleAjaxRequest('Search');
    }

    public static function HelperImport()
    {
        self::handleAjaxRequest('Import', 'Import%20Update%20Media%20Export');
    }

    private static function handleAjaxRequest($mode, $search_term = null)
    {
        check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');

        if ($_POST) {
            $search_term = $search_term ?? sanitize_text_field($_POST['searchInput']);

            if (!empty($search_term)) {
                $encoded_term = rawurlencode($search_term);
                $transient_key = 'smack_support_search_' . md5($encoded_term);
                $data = get_transient($transient_key);
                if ($data === false) {
                    $search_url = 'https://www.smackcoders.com/?swp_form%5Bform_id%5D=1&s=' . $encoded_term;
                    $html = file_get_contents($search_url);

                    if ($html === false) {
                        wp_send_json_error(["error" => "Failed to fetch content"]);
                        wp_die();
                    }

                    $data = self::parseHtml($html);
                    set_transient($transient_key, $data, 24 * HOUR_IN_SECONDS);
                }
                if (!empty($data)) {
                    $response = ['result' => $data];
                    wp_send_json_success($response);
                } else {
                    wp_send_json_error(['message' => 'No data found.']);
                }
                wp_die();
            }
        }
    }

    private static function parseHtml($html)
    {
        try {
            $doc = new \DOMDocument();
            @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $xpath = new \DOMXPath($doc);
            $result = [];
            foreach ($xpath->query("//article") as $index => $article) {

                $title = trim($xpath->query(".//h2", $article)->item(0)->textContent ?? '');
                $link = $xpath->query(".//a", $article)->item(0)->getAttribute("href") ?? '';
                $content = trim($xpath->query(".//p", $article)->item(0)->textContent ?? '');
                //$img_url = $xpath->query(".//img", $article)->item(0)->getAttribute("src") ?? '';

                $result[] = [
                    "title" => $title,
                    "content" => $content,
                    "link" => $link,
                ];
            }
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
