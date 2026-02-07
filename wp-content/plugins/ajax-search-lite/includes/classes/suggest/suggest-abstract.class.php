<?php
/** @noinspection PhpComposerExtensionStubsInspection */

if ( !defined('ABSPATH') ) {
	die('-1');
}

/**
 * A sample and parent class for keyword suggestion and autocomplete
 */

if ( !class_exists('wpd_keywordSuggestAbstract') ) {
	/**
	 * Statistics database keyword suggestions
	 *
	 * @class       wpd_keywordSuggest
	 * @version     1.0
	 * @package     AjaxSearchLite/Classes
	 * @category    Class
	 * @author      Ernest Marcinko
	 */
	abstract class wpd_keywordSuggestAbstract {

		protected $max_count;
		protected $max_chars_per_word;

		/**
		 * This should always return an array of keywords or an empty array
		 *
		 * @param string $q  search keyword
		 * @return array() of keywords
		 */
		abstract public function getKeywords( $q );

		protected function can_get_file() {
			if ( function_exists('curl_init') ) {
				return 1;
			} elseif ( ini_get('allow_url_fopen') ) {
				return 2;
			}
			return false;
		}

		protected function url_get_contents( $url, $method ) {
			if ( $method === 2 ) {
				return file_get_contents($url); // phpcs:ignore
			} elseif ( $method === 1 ) {
				/**
				 * Unfortunately google suggestion does not work with wp_remote_get()
				 */
				// phpcs:disable
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$output = curl_exec($ch);
				curl_close($ch);
				// phpcs:enable
				return $output;
			}
		}
	}
}
