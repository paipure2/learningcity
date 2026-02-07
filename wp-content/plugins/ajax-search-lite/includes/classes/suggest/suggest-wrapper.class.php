<?php
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

if ( !class_exists('wpd_keywordSuggest') ) {
	/**
	 * Keyword suggestion wrapper class
	 *
	 * @class       wpd_keywordSuggest
	 * @version     1.0
	 * @package     AjaxSearchLite/Classes
	 * @category    Class
	 * @author      Ernest Marcinko
	 */
	class wpd_keywordSuggest {

		private $suggest;

		public function __construct( $source, $args ) {
			$class         = 'wpd_' . $source . 'KeywordSuggest';
			$this->suggest = new $class($args);
		}

		public function getKeywords( $q ) {
			return $this->suggest->getKeywords($q);
		}
	}
}
