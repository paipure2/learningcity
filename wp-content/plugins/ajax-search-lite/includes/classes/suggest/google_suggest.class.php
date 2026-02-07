<?php
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

if ( !class_exists('wpd_googleKeywordSuggest') ) {
	/**
	 * Google keyword suggestion class
	 *
	 * @class       wpd_googleKeywordSuggest
	 * @version     1.2
	 * @package     AjaxSearchLite/Classes
	 * @category    Class
	 * @author      Ernest Marcinko
	 */
	class wpd_googleKeywordSuggest extends wpd_keywordSuggestAbstract {
		private $lang;
		private $match_start;
		private $url;

		public function __construct( $args = array() ) {
			$defaults = array(
				'maxCount'        => 10,
				'maxCharsPerWord' => 25,
				'lang'            => 'en',
				'overrideUrl'     => '',
				'match_start'     => false,
			);
			$args     = wp_parse_args( $args, $defaults );

			$this->max_count          = $args['maxCount'];
			$this->max_chars_per_word = $args['maxCharsPerWord'];
			$this->lang               = $args['lang'];
			$this->match_start        = $args['match_start'];

			if ( $args['overrideUrl'] !== '' ) {
				$this->url = $args['overrideUrl'];
			} else {
				$this->url = 'https://suggestqueries.google.com/complete/search?output=toolbar&oe=utf-8&client=toolbar&hl=' . $this->lang . '&q=';
			}
		}


		public function getKeywords( $q ) {
			$q = trim($q);
			if ( $q === '' ) {
				return array();
			}
			if ( !function_exists('simplexml_load_string') ) {
				return array();
			}
			$qf     = str_replace(' ', '+', $q);
			$method = $this->can_get_file();
			if ( $method === false ) {
				return array( 'Error: The fopen url wrapper is not enabled on your server!' );
			}
			$_content = $this->url_get_contents($this->url . rawurlencode($qf), $method);
			if ( $_content === null || $_content === '' ) {
				return array();
			}
			if ( function_exists('mb_convert_encoding') ) {
				$_content = mb_convert_encoding($_content, 'UTF-8');
			}
			try {
				$xml = simplexml_load_string($_content);
				if ( $xml === null || $xml === '' ) {
					return array();
				}
				$json     = wp_json_encode($xml);
				$array    = json_decode($json, true);
				$res      = array();
				$keywords = array();
				if ( isset($array['CompleteSuggestion']) ) {
					foreach ( $array['CompleteSuggestion'] as $k => $v ) {
						if ( isset($v['suggestion']) ) {
							$keywords[] = $v['suggestion']['@attributes']['data'];
						} elseif ( isset($v[0]) ) {
							$keywords[] = $v[0]['@attributes']['data'];
						}
					}
				}
				foreach ( $keywords as $keyword ) {
					$t = strtolower($keyword);
					if (
						$t !== $q
					) {
						$str = wd_substr_at_word($t, $this->max_chars_per_word);
						if ( $str !== '' ) {
							if ( $this->match_start && strpos($t, $q) === 0 ) {
								$res[] = $str;
							} elseif ( !$this->match_start ) {
								$res[] = $str;
							}
						}
					}
				}
				$res = array_slice($res, 0, $this->max_count);
				if ( count($res) > 0 ) {
					return $res;
				} else {
					return array();
				}
			} catch ( Exception $e ) {
				return array();
			}
		}
	}
}
