<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}

if ( !class_exists('ASL_Helpers') ) {
	/**
	 * Class ASL_Helpers
	 *
	 * Compatibility and other helper functions for data translations
	 *
	 * @class         ASL_Helpers
	 * @version       1.0
	 * @package       AjaxSearchLite/Classes/Etc
	 * @category      Class
	 * @author        Ernest Marcinko
	 */
	class ASL_Helpers {

		public static function addInlineScript( $handle, $object_name, $data, $position = 'before', $safe_mode = false ) {
			// Taken from WP_Srcripts -> localize
			foreach ( (array) $data as $key => $value ) {
				if ( is_string($value) ) {
					$data[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8');
				}
			}
			if ( $safe_mode ) {
				// If the inline script was merged or moved by a minify, the object may already exist, so keep the properties
				$script = "window.$object_name = typeof window.$object_name !== 'undefined' ? window.$object_name : {};";
				foreach ( (array) $data as $key => $value ) {
					if ( is_numeric($value) ) {
						$script .= " window.$object_name.$key = $value;";
					} elseif ( is_bool($value) ) {
						if ( $value ) {
							$script .= " window.$object_name.$key = true;";
						} else {
							$script .= " window.$object_name.$key = false;";
						}
					} else {
						$script .= " window.$object_name.$key = " . wp_json_encode($value) . ';';
					}
				}
			} else {
				$script = "var $object_name = " . wp_json_encode( $data ) . ';';
			}

			wp_add_inline_script($handle, $script, $position);
		}

		/**
		 * Prepares the headers for the ajax request
		 *
		 * @param string $content_type
		 */
		public static function prepareAjaxHeaders( $content_type = 'text/plain' ) {
			if ( !headers_sent() ) {
				header('Content-Type: ' . $content_type);
			}
		}

		/**
		 * Performs a safe sanitation and escape for strings and numeric values in LIKE type queries.
		 * This is not to be used on whole queries, only values.
		 *
		 * @param mixed|array[] $str
		 * @param bool          $remove_quotes
		 * @param string        $remove
		 * @return array|mixed
		 * @uses wd_mysql_escape_mimic()
		 * @uses esc_sql()
		 */
		public static function escape( $str, bool $remove_quotes = false, string $remove = '' ) {

			// recursively go through if it is an array
			if ( is_array($str) ) {
				foreach ( $str as $k => $v ) {
					$str[ $k ] = self::escape($v, $remove_quotes, $remove);
				}
				return $str;
			}

			if ( is_float( $str) ) {
				return $str;
			}

			if ( $remove_quotes ) {
				$str = str_replace(
					array(
						chr(145),
						chr(146),
						chr(147),
						chr(148),
						chr(150),
						chr(151),
						chr(133),
						"'",
						'"',
					),
					'',
					$str
				);
			}
			if ( !empty($remove) ) {
				$str = str_replace(str_split($remove), '', $str);
			}

			if ( function_exists( 'esc_sql' ) ) {
				return esc_sql( $str);
			}

			// Okay, what? Not one function is present, use the one we have
			return wd_mysql_escape_mimic($str);
		}

		/**
		 * Removes gutenberg blocks from the given string by block names array
		 *
		 * @param string   $content
		 * @param string[] $block_names
		 * @return string
		 */
		public static function removeGutenbergBlocks( string $content, array $block_names = array( 'core-embed/*' ) ) {
			foreach ( $block_names as &$block_name ) {
				$block_name = str_replace('*', 'AAAAAAAAAAAAAAA', $block_name);
				$block_name = preg_quote($block_name, '');
				$block_name = str_replace('AAAAAAAAAAAAAAA', '.*?', $block_name);
			}
			$block_names = implode('|', $block_names);

			// preg_quote does not escape '/' by default - use the '~' or '#' as the regex delimiter
			return preg_replace(
				"~<!--\s+wp:($block_names)(\s+\{(.*?)\})?\s+-->(.*?)<!--\s+\/wp:($block_names)\s+-->~s",
				'',
				$content
			);
		}

		/**
		 * Generates a string reverse, support multibyte strings, plus fallback if mbstring if not avail
		 *
		 * @param string $str
		 * @return string
		 */
		public static function reverseString( string $str ) {
			if (
				function_exists('mb_detect_encoding') &&
				function_exists('mb_strlen') &&
				function_exists('mb_substr')
			) {
				// Using mbstring
				$encoding = mb_detect_encoding($str);
				$length   = mb_strlen($str, $encoding);
				$reversed = '';
				while ( $length-- > 0 ) {
					$reversed .= mb_substr($str, $length, 1, $encoding);
				}

				return $reversed;

			} else {
				// Good old regex method, still supporting fully UFT8
				preg_match_all('/./us', $str, $ar);
				return implode(array_reverse($ar[0]));
			}
		}

		/**
		 * Clears and trims a search phrase from extra slashes and extra space characters
		 *
		 * @param string $s
		 * @return mixed
		 */
		public static function clear_phrase( string $s ) {
			return preg_replace( '/\s+/', ' ', trim(stripcslashes($s)) );
		}


		/**
		 * Gets the custom field value, supporting ACF get_field() and WooCommerce multi currency
		 *
		 * @see ASL_Helpers::woo_formattedPriceWithCurrency()   To get the currency formatted field.
		 * @see get_field()                                     ACF post meta parsing.
		 * @since 4.11
		 *
		 * @param string $field      Custom field label
		 * @param object $r          Result object
		 * @param bool   $use_acf    If true, will use the get_field() function from ACF
		 * @param array  $args       Search arguments
		 * @param array  $field_args Additional field arguments
		 * @return string
		 */
		public static function getCFValue( $field, $r, $use_acf = false, $args = array(), $field_args = array() ) {
			$ret             = '';
			$price_fields    = array( '_price', '_price_html', '_tax_price', '_sale_price', '_regular_price' );
			$datetime_fields = array(
				'_EventStartDate',
				'_EventStartDateUTC',
				'_EventEndDate',
				'_EventEndDateUTC',
				'_event_start_date',
				'_event_end_date',
				'_event_start',
				'_event_end',
				'_event_start_local',
				'_event_end_local',
			);

			if ( ( in_array($field, $datetime_fields, true) || isset($field_args['date_format']) ) && isset($r->post_type) ) {
				$mykey_values = get_post_custom_values($field, $r->id);
				if ( isset($mykey_values[0]) ) {
					if ( isset($field_args['date_format']) ) {
						$ret = date_i18n( $field_args['date_format'], strtotime( $mykey_values[0] ) );
					} else {
						$ret = date_i18n( get_option( 'date_format' ), strtotime( $mykey_values[0] ) );
					}
				}
			} elseif ( in_array($field, $price_fields, true) &&
				isset($r->post_type) &&
				in_array($r->post_type, array( 'product', 'product_variation' ), true) &&
				function_exists('wc_get_product')
			) { // Is this a WooCommerce price related field?
				$ret = self::woo_formattedPriceWithCurrency($r->id, $field, $args);
			} elseif ( $use_acf && function_exists('get_field') ) { // ..or just a regular field?
					$mykey_values = get_field($field, $r->id, true);
				if ( !is_null($mykey_values) && $mykey_values !== '' && $mykey_values !== false ) {
					if ( is_array($mykey_values) ) {
						if ( count($mykey_values) > 0 && isset($mykey_values[0]) ) {
							// Field display mode as Array (both label and value)
							if ( isset($mykey_values[0]['label']) ) {
								$labels = array();
								foreach ( $mykey_values as $choice ) {
									if ( isset($choice['label']) ) {
										$labels[] = $choice['label'];
									}
								}
								if ( count($labels) > 0 ) {
									$ret = implode(', ', $labels);
								}
								// Make sure this is not some sort of a repeater or reference
							} elseif ( !is_object($mykey_values[0]) ) {
								$ret = implode(', ', $mykey_values);
							}
						}
					} else {
						$ret = $mykey_values;
					}
				}
			} else {
				$mykey_values = get_post_custom_values($field, $r->id);
				if ( isset($mykey_values[0]) ) {
					$ret = wd_array_to_string( maybe_unserialize( $mykey_values[0] ) );
				}
			}

			return $ret;
		}

		/**
		 * Gets the WooCommerce formatted currency, supporting multiple currencies WPML, WCML
		 *
		 * @since 4.11
		 * @see wc_get_product()    Getting the WooCommerce product.
		 * @see $woocommerce_wpml->multi_currency->prices->get_product_price_in_currency() For multi currency parsing.
		 * @see wc_price()          Price formatting.
		 *
		 * @param int    $id         Product or variation ID
		 * @param string $field      Field label
		 * @param array  $args       Search arguments
		 * @return string
		 */
		public static function woo_formattedPriceWithCurrency( $id, $field, $args ) {
			global $woocommerce_wpml;
			global $sitepress;

			$currency = $args['woo_currency'] ?? ( function_exists('get_woocommerce_currency') ?
				get_woocommerce_currency() : '' );

			$price = '';
			$p     = wc_get_product( $id );

			// WCML Section, copied and modified from
			// ..\wp-content\plugins\wpml-woocommerce\inc\currencies\class-wcml-multi-currency-prices.php
			// line 139, function product_price_filter(..)
			if ( isset($sitepress, $woocommerce_wpml, $woocommerce_wpml->multi_currency) ) {
				$original_object_id = apply_filters( 'translate_object_id', $id, get_post_type($id), false, $sitepress->get_default_language() );
				$ccr                = get_post_meta($original_object_id, '_custom_conversion_rate', true);
				if ( in_array($field, array( '_price', '_regular_price', '_sale_price', '_price_html' ), true) && !empty($ccr) && isset($ccr[ $field ][ $currency ]) ) {
					if ( $field === '_price_html' ) {
						$field = '_price';
					}
					$price_original = get_post_meta($original_object_id, $field, true);
					$price          = $price_original * $ccr[ $field ][ $currency ];
				} else {
					$manual_prices = $woocommerce_wpml->multi_currency->custom_prices->get_product_custom_prices($id, $currency);
					if ( $field === '_price_html' ) {
						$field = '_price';
					}
					if ( $manual_prices && !empty($manual_prices[ $field ]) ) {
						$price = $manual_prices[ $field ];
					} else {
						// 2. automatic conversion
						$price = get_post_meta($id, $field, true);
						$price = apply_filters('wcml_raw_price_amount', $price, $currency );
					}
				}

				if ( $price !== '' ) {
					$price = wc_price($price, array( 'currency' => $currency ));
				}
			} else {
				// For variable products _regular_price, _sale_price are not defined
				// ..however are most likely used together. So in case of _regular_price display the range,
				// ..but do not deal with _sale_price at all
				if ( $p->is_type('variable') && !in_array($field, array( '_sale_price' ), true) ) {
					$price = $p->get_price_html();
				} else {
					switch ( $field ) {
						case '_regular_price':
							$price = $p->get_regular_price();
							break;
						case '_sale_price':
							if ( $p->is_on_sale() ) {
								$price = $p->get_sale_price();
							} else {
								$price = '';
							}
							break;
						case '_tax_price':
							$price = $p->get_price_including_tax();
							break;
						case '_price_html':
							$price = $p->get_price_html();
							break;
						default:
							$price = $p->get_price();
							break;
					}
					if ( $field !== '_price_html' && $price !== '' && function_exists('wc_price') ) {
						if ( $currency !== '' ) {
							$price = wc_price($price, array( 'currency' => $currency ));
						} else {
							$price = wc_price($price);
						}
					}
				}
			}

			return $price;
		}


		/**
		 * Helper method to be used before printing the font styles. Converts font families to apostrophed versions.
		 *
		 * @param string $font
		 * @return mixed
		 */
		public static function font( $font ) {
			preg_match('/family:(.*?);/', $font, $fonts);
			if ( isset($fonts[1]) ) {
				$f = explode(',', str_replace(array( '"', "'" ), '', $fonts[1]));
				foreach ( $f as &$_f ) {
					if ( trim($_f) !== 'inherit' ) {
						$_f = '"' . trim($_f) . '"';
					} else {
						$_f = trim($_f);
					}
				}
				$f = implode(',', $f);
				return preg_replace('/family:(.*?);/', 'family:' . $f . ';', $font);
			} else {
				return $font;
			}
		}

		/**
		 * Translates search data and options to query arguments to use with ASL_Query
		 *
		 * @param int   $search_id
		 * @param array $o
		 * @param array $args
		 * @return mixed
		 */
		public static function toQueryArgs( $search_id, $o, $args = array() ) {
			global $wpdb;
			// When $o is (bool)false, then this is called individually, not as ajax request

			// Always return an emtpy array if something goes wrong
			if ( !wd_asl()->instances->exists($search_id) ) {
				return array();
			}

			$search = wd_asl()->instances->get(0);
			$sd     = $search['data'];

			$args         = empty($args) ? ASL_Query::$defaults : array_merge(ASL_Query::$defaults, $args);
			$comp_options = wd_asl()->o['asl_compatibility'];
			
			$exclude_post_ids = array_unique(explode(',', str_replace(' ', '', $sd['excludeposts'])));
			foreach ( $exclude_post_ids as $k =>$v ) {
				if ( $v === '' ) {
					unset($exclude_post_ids[ $k ]);
				} else {
					$exclude_post_ids[ $k ] = intval($v);
				}
			}

			// ----------------------------------------------------------------
			// 1. CPT
			// ----------------------------------------------------------------
			$args = array_merge(
				$args,
				array(
					'_sd'                  => $sd, // Search Data
					'_sid'                 => 0,
					'keyword_logic'        => $sd['keyword_logic'],
					'secondary_logic'      => 'none',
					'post_not_in'          => $exclude_post_ids,
					'post_in'              => array(),
					'post_primary_order'   => $sd['orderby_primary'],
					'post_secondary_order' => $sd['orderby_secondary'],
					'_db_force_case'       => $comp_options['db_force_case'],
					'_db_force_utf8_like'  => $comp_options['db_force_utf8_like'],
					'_db_force_unicode'    => $comp_options['db_force_unicode'],
					// LIMITS
					'posts_limit'          => intval($sd['maxresults']),
				)
			);
			$args['posts_limit']      = $args['posts_limit'] <= 0 ? 10 : $args['posts_limit'];
			$args['_qtranslate_lang'] = isset($o['qtranslate_lang']) ?$o['qtranslate_lang'] :'';
			if ( $sd['polylang_compatibility'] ) {
				$args['_polylang_lang'] = $o['polylang_lang'] ?? ( function_exists('pll_current_language') ? pll_current_language() : '' );
			}
			$args['_exact_matches']        = isset($o['asl_gen']) && is_array($o['asl_gen']) && in_array('exact', $o['asl_gen'], true) ? 1 : 0;
			$args['_exact_match_location'] = $sd['exact_match_location'];

			/*----------------------- Meta key order ------------------------*/
			if ( strpos($sd['orderby_primary'], 'customfp') !== false ) {
				if ( !empty($sd['orderby_primary_cf']) ) {
					$args['_post_primary_order_metakey'] = $sd['orderby_primary_cf'];
					$args['post_primary_order_metatype'] = $sd['orderby_primary_cf_type'];
				}
			}
			if ( strpos($sd['orderby_secondary'], 'customfs') !== false ) {
				if ( !empty($sd['orderby_secondary_cf']) ) {
					$args['_post_secondary_order_metakey'] = $sd['orderby_secondary_cf'];
					$args['post_secondary_order_metatype'] = $sd['orderby_secondary_cf_type'];
				}
			}

			/*----------------------- Auto populate -------------------------*/
			if ( isset($o['force_count']) ) {
				// Set the advanced limit parameter to be distributed later
				$args['limit']       = $o['force_count'] + 0;
				$args['force_count'] = $o['force_count'] + 0;
			}
			if ( isset($o['force_order']) ) {
				if ( $o['force_order'] == 1 ) { // phpcs:ignore
					$args['post_primary_order'] = 'post_date DESC';
					$args['force_order']        = 1;
				} elseif ( $o['force_order'] == 2 ) { // phpcs:ignore
					$args['post_primary_order'] = 'RAND()';
					$args['force_order']        = 2;
				}
			}

			/*------------------------- Statuses ----------------------------*/
			$args['post_status'] = explode(',', str_replace(' ', '', $sd['post_status']));

			/*--------------------- Password protected ----------------------*/
			$args['has_password'] = $sd['show_password_protected_posts'];

			/*----------------------- Gather Types --------------------------*/
			$args['post_type'] = array();
			if ( $o === false ) {
				if ( isset( $sd['customtypes'] ) && is_array($sd['customtypes']) && count( $sd['customtypes'] ) > 0 ) {
					$args['post_type'] = array_merge( $args['post_type'], $sd['customtypes'] );
				}
			} elseif ( isset( $o['customset'] ) && is_array($o['customset']) && count( $o['customset'] ) > 0 ) {
					$o['customset']    = self::escape( $o['customset'], true, ' ;:.,(){}@[]!?&|#^=' );
					$args['post_type'] = array_merge($args['post_type'], $o['customset']);
			}

			/*--------------------- OTHER FILTER RELATED --------------------*/
			$args['filters_changed'] = $o['filters_changed'] ?? $args['filters_changed'];
			$args['filters_initial'] = $o['filters_initial'] ?? $args['filters_initial'];

			/*--------------------- GENERAL FIELDS --------------------------*/
			$args['search_type'] = array();
			$args['post_fields'] = array();
			if ( $sd['searchinterms'] ) {
				$args['post_fields'][] = 'terms';
			}
			if ( $o === false ) {
				if ( $sd['searchintitle'] ) {
					$args['post_fields'][] = 'title';
				}
				if ( $sd['searchincontent'] ) {
					$args['post_fields'][] = 'content';
				}
				if ( $sd['searchinexcerpt'] ) {
					$args['post_fields'][] = 'excerpt';
				}
			} elseif ( isset($o['asl_gen']) && is_array($o['asl_gen']) ) {
				if ( in_array('title', $o['asl_gen'], true) ) {
					$args['post_fields'][] = 'title';
				}
				if ( in_array('content', $o['asl_gen'], true) ) {
					$args['post_fields'][] = 'content';
				}
				if ( in_array('excerpt', $o['asl_gen'], true) ) {
					$args['post_fields'][] = 'excerpt';
				}
			}
			if ( $sd['search_in_ids'] ) {
				$args['post_fields'][] = 'ids';
			}
			if ( $sd['search_in_permalinks'] ) {
				$args['post_fields'][] = 'permalink';
			}

			/*--------------------- CUSTOM FIELDS ---------------------------*/
			$args['post_custom_fields_all'] = $sd['search_all_cf'];
			$args['post_custom_fields']     = $sd['selected-customfields'] ?? array();

			if ( count($args['post_fields']) > 0 ||
				$args['post_custom_fields_all'] ||
				count($args['post_custom_fields']) > 0 ||
				count($args['post_type']) > 0
			) {
				$args['search_type'][] = 'cpt';
			}

			/*-------------------------- WPML -------------------------------*/
			if ( $sd['wpml_compatibility'] ) {
				if ( isset( $o['wpml_lang'] ) && $args['_ajax_search'] ) {
					$args['_wpml_lang'] = $o['wpml_lang'];
				} elseif (
					defined('ICL_LANGUAGE_CODE')
					&& ICL_LANGUAGE_CODE !== ''
					&& defined('ICL_SITEPRESS_VERSION')
				) {
					$args['_wpml_lang'] = ICL_LANGUAGE_CODE;
				}

				/**
				 * Switching the language will resolve issues with get_terms(..) and other functions
				 * Otherwise wrong taxonomy terms would be returned etc..
				 */
				global $sitepress;
				if ( is_object($sitepress) && method_exists($sitepress, 'switch_lang') ) {
					$sitepress->switch_lang($args['_wpml_lang']);
				}
			}

			/*-------------------- Content, Excerpt -------------------------*/
			$args['_post_get_content'] = $sd['showdescription'];
			$args['_post_get_excerpt'] = (
				$sd['primary_titlefield'] ||
				$sd['secondary_titlefield'] ||
				$sd['primary_descriptionfield'] ||
				$sd['secondary_descriptionfield']
			);

			// WooCommerce - Exclude out of stock
			if ( $sd['woo_exclude_outofstock'] ) {
				$args['post_meta_filter'][] = array(
					'key'           => '_stock_status',
					'value'         => 'instock',
					'operator'      => 'ELIKE',
					'allow_missing' => true,
				);
			}

			/*---------------------- Taxonomy Terms -------------------------*/
			$args['post_tax_filter'] = self::toQueryArgs_Taxonomies($sd, $o);

			// Woocommerce - Excluded catalogue or search products, when variations are selected
			if (
				in_array('product_variation', $args['post_type'], true) &&
				wd_in_array_r('product_visibility', $args['post_tax_filter'])
			) {
				foreach ( $args['post_tax_filter'] as $filter => $items ) {
					if ( $items['taxonomy'] === 'product_visibility' && count($items['exclude']) > 0 ) {
						$product_ids = get_posts(
							array(
								'post_type'   => 'product',
								'numberposts' => 250, // phpcs:ignore
							// phpcs:ignore
							'tax_query'     => array(
								array(
									'taxonomy' => 'product_visibility',
									'field'    => 'id',
									'terms'    => $items['exclude'],
									'operator' => 'IN',
								),
							),
								'fields'      => 'ids',  // Only get post IDs
							)
						);
						if ( !is_wp_error($product_ids) && !empty($product_ids) ) {
							$args['post_parent_exclude'] = array_unique( array_merge($args['post_parent_exclude'], $product_ids) );
						}
						break;
					}
				}
			}

			// ----------------------------------------------------------------
			// X. MISC FIXES
			// ----------------------------------------------------------------
			$args['woo_currency'] = $o['woo_currency'] ?? ( function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '' );
			$args['_page_id']     = isset($o['current_page_id']) ? intval($o['current_page_id']) : $args['_page_id'];
			// Reset search type and post types for WooCommerce search results page
			if ( isset($_GET['post_type']) && $_GET['post_type'] === 'product' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$old_ptype         = $args['post_type'];
				$args['post_type'] = array();
				if ( in_array('product', $old_ptype, true) ) {
					$args['post_type'][] = 'product';
				}
				if ( in_array('product_variation', $old_ptype, true) ) {
					$args['post_type'][] = 'product_variation';
				}
			}

			// Restrict to parent posts from SHORTCODE arguments
			if ( isset($o['post_parent']) && is_array($o['post_parent']) ) {
				$args['post_parent'] = array_map( 'intval', array_filter( $o['post_parent'], 'is_numeric' ) );
			}

			// ----------------------------------------------------------------
			return $args;
		}

		/**
		 * Converts search data and options to Taxonomy Term query argument arrays to use with ASL_Query
		 *
		 * @param mixed $sd
		 * @param mixed $o
		 * @return array
		 */
		private static function toQueryArgs_Taxonomies( $sd, $o ) {
			$ret = array();

			$term_logic = 'and';

			$exclude_categories                  = array();
			$sd['selected-exsearchincategories'] = w_isset_def( $sd['selected-exsearchincategories'], array() );
			$sd['selected-excludecategories']    = w_isset_def( $sd['selected-excludecategories'], array() );

			if ( count( $sd['selected-exsearchincategories'] ) > 0 ||
				count( $sd['selected-excludecategories'] ) > 0 ||
				( isset($o['categoryset']) && count( $o['categoryset'] ) ) > 0 ||
				$sd['showsearchincategories']
			) {

				// If the category settings are invisible, ignore the excluded frontend categories, reset to empty array
				if ( !$sd['showsearchincategories'] ) {
					$sd['selected-exsearchincategories'] = array();
				}

				$_all_cat    = get_terms(
					array(
						'taxonomy' => 'category',
						'fields'   => 'ids',
					) 
				);
				$_needed_cat = array_diff( $_all_cat, $sd['selected-exsearchincategories'] );
				$_needed_cat = ! is_array( $_needed_cat ) ? array() : $_needed_cat;

				if ( $sd['showsearchincategories'] && isset($o['categoryset']) ) {
					$exclude_categories = array_diff( array_merge( $_needed_cat, $sd['selected-excludecategories'] ), $o['categoryset'] );
				} else // ..if the settings is not visible, then only the excluded categories count
				{
					$exclude_categories = $sd['selected-excludecategories'];
				}
			}
			$exclude_terms = array();
			$exclude_terms = array_unique( array_merge( $exclude_categories, $exclude_terms ) );
			if ( count($exclude_terms) > 0 ) {
				$ret[] = array(
					'taxonomy'     => 'category',
					'include'      => array(),
					// phpcs:ignore
					'exclude' => array_map('intval', $exclude_terms),
					'logic'        => $term_logic,
					'_termset'     => isset($o['categoryset']) ? $o['categoryset'] : array(),
					'_is_checkbox' => true,
				);
			}

			// Woocommerce - Exclude hidden and catalog
			if ( class_exists('WooCommerce') && ( $sd['exclude_woo_hidden'] || $sd['exclude_woo_catalog'] ) ) {
				// Check if this is version > 3.0
				if ( asl_woo_version_check('3.0') ) {
					$exclude = array();
					if ( $sd['exclude_woo_hidden'] ) {
						$exclude[] = 'exclude-from-search';
					}
					if ( $sd['exclude_woo_catalog'] ) {
						$exclude[] = 'exclude-from-catalog';
					}
					if ( !empty($exclude) ) {
						$_t = get_terms(
							array(
								'taxonomy'   => 'product_visibility',
								'slug'       => $exclude,
								'hide_empty' => 0,
								'fields'     => 'ids',
							)
						);

						if ( !is_wp_error($_t) && count($_t) > 0 ) {
							$ret[] = array(
								'taxonomy'    => 'product_visibility',
								'exclude'  => $_t, // phpcs:ignore
								'allow_empty' => true,
							);
						}
					}
				}
			}

			return $ret;
		}
	}
}
