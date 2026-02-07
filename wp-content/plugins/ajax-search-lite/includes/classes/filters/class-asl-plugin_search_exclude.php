<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}

if ( !class_exists('WD_ASL_Plugin_SearchExclude_Filter') ) {
	/**
	 * Class WD_ASL_Plugin_SearchExclude_Filter
	 *
	 * Makes Search Exclude plugin exclusions compatible with Ajax Search Lite
	 *
	 * @see           https://wordpress.org/plugins/search-exclude/
	 * @class         WD_ASL_Plugin_SearchExclude_Filter
	 * @version       1.0
	 * @package       AjaxSearchLite/Classes/Filters
	 * @category      Class
	 * @author        Ernest Marcinko
	 */
	class WD_ASL_Plugin_SearchExclude_Filter extends WD_ASL_Filter_Abstract {

		public function handle( array $args = array() ) {
			if ( class_exists('QuadLayers\QLSE\Models\Settings') ) {
				$excluded = QuadLayers\QLSE\Models\Settings::instance()->get();
				if ( ! isset( $excluded ) ) {
					return $args;
				}
				/**
				 * Exclude posts by post type
				 */
				if ( isset( $excluded->entries ) ) {
					$post__not_in = array();
					foreach ( $excluded->entries as $post_type => $excluded_post_type ) {
						$post_type_ids = ! empty( $excluded_post_type['all'] ) ? $this->get_all_post_type_ids( $post_type ) : $excluded_post_type['ids'];
						$post__not_in  = array_merge( $post__not_in, $post_type_ids );
					}
					$args['post_not_in'] = is_array($args['post_not_in']) ? $args['post_not_in'] : array();
					$args['post_not_in'] = array_unique( array_merge( $args['post_not_in'], $post__not_in ) );
				}           
			}
			return $args;
		}

		private function get_all_post_type_ids( $post_type ) {
			return get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);
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
