<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}

if ( !class_exists('WD_ASL_SearchOverride_Filter') ) {
	/**
	 * Class WD_ASL_SearchOverride_Filter
	 *
	 * Handles search override filters
	 *
	 * @class         WD_ASL_SearchOverride_Filter
	 * @version       1.0
	 * @package       AjaxSearchLite/Classes/Filters
	 * @category      Class
	 * @author        Ernest Marcinko
	 */
	class WD_ASL_SearchOverride_Filter extends WD_ASL_Filter_Abstract {

		public function handle() {}

		/**
		 * Checks and cancels the original search query made by WordPress, if required
		 *
		 * @param string   $query The SQL query
		 * @param WP_Query $wp_query The instance of WP_Query() for this query
		 * @return bool
		 */
		public function maybeCancelWPQuery( $query, $wp_query ) {
			if ( $this->checkSearchOverride(true, $wp_query) === true ) {
				$query = false;
			}
			return $query;
		}

		public function override( $posts, $wp_query ) {

			$check_override = $this->checkSearchOverride(false, $wp_query);
			if ( $check_override === false ) {
				return $posts;
			} else {
				$_p_id  = $check_override[0];
				$s_data = $check_override[1];
			}

			$inst = wd_asl()->instances->get(0);
			$sd   = $inst['data'];

			$posts_per_page = $sd['results_per_page'];
			if ( $posts_per_page === 'auto' ) {
				$posts_per_page = get_option( 'posts_per_page' );
			}
			$posts_per_page = intval($posts_per_page) === 0 ? 1 : $posts_per_page;

			if ( isset($_GET['paged']) ) { // phpcs:ignore: WordPress.Security.NonceVerification.Recommended
				$paged = intval($_GET['paged']); // phpcs:ignore
			} elseif ( isset($wp_query->query_vars['paged']) ) {
				$paged = $wp_query->query_vars['paged'];
			} else {
				$paged = 1;
			}
			$paged = $paged <= 0 ? 1 : $paged;

			$args = array(
				// It's dealt with in the query
				's'              => wp_unslash($_GET['s']), // phpcs:ignore
				'_ajax_search'   => false,
				'posts_per_page' => $posts_per_page,
				'page'           => $paged,
			);

			add_filter('asl_query_args', array( $this, 'getAdditionalArgs' ), 10, 1);

			if ( count($s_data) === 0 ) {
				$asl_query = new ASL_Query($args, 0);
			} else {
				$asl_query = new ASL_Query($args, 0, $s_data);
			}
			$res = $asl_query->posts;

			$wp_query->found_posts = $asl_query->found_posts;

			if ( ( $wp_query->found_posts / $posts_per_page ) > 1 ) {
				$wp_query->max_num_pages = ceil($wp_query->found_posts / $posts_per_page);
			} else {
				$wp_query->max_num_pages = 0;
			}

			return $res;
		}

		public function getAdditionalArgs( $args ) {
			global $wpdb;

			// WooCommerce or other custom Ordering
			if ( isset($_GET['orderby']) || isset($_GET['product_orderby']) ) { // phpcs:ignore
				// phpcs:ignore: WordPress.Security.NonceVerification.Recommended
				$o_by = sanitize_text_field( wp_unslash($_GET['orderby'] ?? $_GET['product_orderby'] ));
				$o_by = str_replace(' ', '', ( strtolower($o_by) ));
				if ( isset($_GET['order']) || isset($_GET['product_order']) ) { // phpcs:ignore
					// phpcs:ignore: WordPress.Security.NonceVerification.Recommended
					$o_way = sanitize_text_field( wp_unslash($_GET['order'] ?? $_GET['product_order']) );
				} elseif ( $o_by === 'price' || $o_by === 'product_price' ) {
					$o_way = 'ASC';
				} elseif ( $o_by === 'alphabetical' ) {
					$o_way = 'ASC';
				} else {
					$o_way = 'DESC';
				}
				$o_way = strtoupper($o_way);
				if ( $o_way !== 'DESC' && $o_way !== 'ASC' ) {
					$o_way = 'DESC';
				}
				switch ( $o_by ) {
					case 'id':
					case 'post_id':
					case 'product_id':
						$args['post_primary_order'] = "id $o_way";
						break;
					case 'popularity':
					case 'post_popularity':
					case 'product_popularity':
						$args['post_primary_order']          = "customfp $o_way";
						$args['post_primary_order_metatype'] = 'numeric';
						$args['_post_primary_order_metakey'] = 'total_sales';
						break;
					case 'rating':
					case 'post_rating':
					case 'product_rating':
						// Custom query args here
						$args['cpt_query']['fields']  = "(
                            SELECT
                                IF(AVG( $wpdb->commentmeta.meta_value ) IS NULL, 0, AVG( $wpdb->commentmeta.meta_value ))
                            FROM
                                $wpdb->comments
                                LEFT JOIN $wpdb->commentmeta ON($wpdb->comments.comment_ID = $wpdb->commentmeta.comment_id)
                            WHERE
                                $wpdb->posts.ID = $wpdb->comments.comment_post_ID
                                AND ( $wpdb->commentmeta.meta_key = 'rating' OR $wpdb->commentmeta.meta_key IS null )
                        ) as average_rating, ";
						$args['cpt_query']['orderby'] = "average_rating $o_way, ";

						// Force different field order for index table
						$args['post_primary_order'] = "average_rating $o_way";
						break;
					case 'date':
					case 'post_date':
					case 'product_date':
						$args['post_primary_order'] = "post_date $o_way";
						break;
					case 'name':
					case 'post_name':
					case 'product_name':
					case 'alphabetical':
					case 'reverse_alpha':
					case 'reverse_alphabetical':
						$args['post_primary_order'] = "post_title $o_way";
						break;
					case 'price':
					case 'product_price':
					case 'price-desc':
						$args['post_primary_order']          = "customfp $o_way";
						$args['post_primary_order_metatype'] = 'numeric';
						$args['_post_primary_order_metakey'] = '_price';
						break;
					case 'relevance':
						$args['post_primary_order'] = "relevance $o_way";
						break;
				}
			}

			return $args;
		}

		/**
		 * Checks if the default WordPress search query is executed right now, and if it needs an override.
		 * Also sets some cookie and request variables, if needed.
		 *
		 * @param bool     $check_only when true, only checks if the override should be initiated, no variable changes
		 * @param WP_Query $wp_query The instance of WP_Query() for this query
		 * @return array|bool
		 */
		public function checkSearchOverride( $check_only, $wp_query ) {
			// Check the search query
			if ( !$this->isSearch($wp_query) ) {
				return false;
			}

			// If get method is used, then the cookies are not present
			if ( isset($_GET['p_asl_data']) || isset($_GET['np_asl_data']) ) { // phpcs:ignore
				if ( $check_only ) {
					return true;
				}
				// phpcs:ignore: WordPress.Security.NonceVerification.Recommended
				$_p_data = sanitize_text_field( wp_unslash($_GET['p_asl_data'] ?? $_GET['np_asl_data']));
				if ( $_p_data == 1 ) { // phpcs:ignore
					// phpcs:ignore: WordPress.Security.NonceVerification.Recommended
					$s_data = $_GET;
				}

				/**
				 * At this point the asl_data cookie should hold the search data, if not, well then this
				 * is just a simple search query.
				 */
			} elseif (
				isset($_COOKIE['asl_data'], $_COOKIE['asl_phrase']) &&
				$_COOKIE['asl_phrase'] === $_GET['s'] // phpcs:ignore
			) {
				if ( $check_only ) {
					return true;
				}
				parse_str($_COOKIE['asl_data'], $s_data); // phpcs:ignore
				$_POST['np_asl_data'] = $_COOKIE['asl_data']; // phpcs:ignore
			} else {
				$sd = wd_asl()->instances->get(0)['data'];
				// Probably the search results page visited via URL, not triggered via search bar
				if ( $sd['override_default_results'] ) {
					return array( 1, array() );
				}

				// Something is not right
				return false;
			}

			return array( 1, $s_data );
		}

		public function isSearch( $wp_query ) {
			$is_search  = true;
			$soft_check =
				defined('ELEMENTOR_VERSION') || // Elementor
				defined('ET_CORE') || // Divi
				wd_asl()->o['asl_compatibility']['query_soft_check'];

			// This can't be a search query if none of this is set
			if ( !isset($wp_query, $wp_query->query_vars, $_GET['s']) ) { // phpcs:ignore
				$is_search = false;
			} else {
				// Possible candidates for search below
				if ( $soft_check ) {
					// In soft check mode, it does not have to be the main query
					if ( !$wp_query->is_search() ) {
						$is_search = false;
					}
				} elseif ( !$wp_query->is_search() || !$wp_query->is_main_query() ) {
						$is_search = false;
				}
				if ( !$is_search && isset($wp_query->query_vars['aps_title']) ) {
					$is_search = true;
				}
			}

			// GEO directory search, do not override
			if ( $is_search && isset($_GET['geodir_search']) ) { // phpcs:ignore
				$is_search = false;
			}

			// Elementor or other forced override
			if ( isset($wp_query->query_vars) && $wp_query->query_vars['post_type'] === 'asl_override' ) {
				$is_search = true;
			}

			// Is this the admin area?
			if ( $is_search && is_admin() ) {
				$is_search = false;
			}

			// Possibility to add exceptions
			return apply_filters('asl_query_is_search', $is_search, $wp_query);
		}

		public function fixUrls( $url, $post ) {
			if ( isset($post->asl_guid) ) {
				return $post->asl_guid;
			}
			return $url;
		}

		// ------------------------------------------------------------
		// ---------------- SINGLETON SPECIFIC --------------------
		// ------------------------------------------------------------
		/**
		 * Static instance storage
		 *
		 * @var self
		 */
		protected static $_instance;

		public static function getInstance() {
			if ( ! ( self::$_instance instanceof self ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
}
