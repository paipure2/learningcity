<?php

use WPDRMS\ASL\Utils\Html;

if ( !defined('ABSPATH') ) {
	die('-1');
}

if ( !class_exists('WD_ASL_Search_Shortcode') ) {
	/**
	 * Class WD_ASL_Search_Shortcode
	 *
	 * Search bar shortcode
	 *
	 * @class         WD_ASL_Search_Shortcode
	 * @version       1.0
	 * @package       AjaxSearchLite/Classes/Shortcodes
	 * @category      Class
	 * @author        Ernest Marcinko
	 */
	class WD_ASL_Search_Shortcode extends WD_ASL_Shortcode_Abstract {

		/**
		 * Overall instance count
		 *
		 * @var int
		 */
		private static $instance_count = 0;

		/**
		 * Does the search shortcode stuff
		 *
		 * @param array|null $atts
		 * @return string
		 */
		public function handle( $atts ) {
			$style = null;
			++self::$instance_count;

			$atts           = shortcode_atts(
				array(
					'id'             => 'something',
					'post_parent'    => '',
					'include_styles' => '',
				),
				$atts
			);
			$id             = $atts['id'];
			$post_parent    = $atts['post_parent'];
			$include_styles = $atts['include_styles'];

			$inst  = wd_asl()->instances->get(0);
			$style = $inst['data'];

			// Set the "_fo" item to indicate that the non-ajax search was made via this form, and save the options there
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset($_POST['p_asl_data']) || isset($_POST['np_asl_data']) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$_p_data = isset($_POST['p_asl_data']) ? sanitize_text_field(wp_unslash($_POST['p_asl_data'])) : sanitize_text_field(wp_unslash($_POST['np_asl_data']));
				parse_str($_p_data, $style['_fo']);
			}

			$settings_hidden = !$style['show_frontend_search_settings'];

			// Triggered by URL
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset($_GET['asl_s']) ) {
				$style['auto_populate'] = 'phrase';
				// This is sanitized later on
				$style['auto_populate_phrase'] = wp_unslash($_GET['asl_s']); // phpcs:ignore
				$style['auto_populate_count']  = intval($style['maxresults']);
			}

			if ( $post_parent !== '' ) {
				$post_parent = array_unique( 
					array_map( 'intval', array_filter( explode(',', $post_parent), 'is_numeric' ) )
				);
				if ( !empty($post_parent) ) {
					$current_instance = self::$instance_count;
					add_action(
						'asl_layout_in_form',
						function ( $search_id ) use ( $post_parent, $current_instance ) {
							if ( $current_instance !== intval($search_id) ) {
								return;
							}
							foreach ( $post_parent as $pid ) {
								echo "<input type='hidden' name='post_parent[]' value='" . esc_attr($pid) . "'>";
							}
						}
					);
				}
			}

			$out = '';

			/**
			 * This is a very special case, only used when the user explicitly wants the styles to be
			 * printed via an argument.
			 */
			if ( $include_styles ) {
				$styles = ( new WD_ASL_Styles() )->get();
				ob_start();
				foreach ( $styles['src'] as $style_url ) :
					// phpcs:ignore
					?><link rel="stylesheet" href="<?php echo $style_url ?>?ver=<?php echo ASL_CURR_VER_STRING; ?>"><?php
				endforeach;
				?>
				<style>
					<?php
					// Escape for safety
					echo Html::escCSS($styles['inline']); // phpcs:ignore
					?>
				</style>
				<?php
				$out = ob_get_clean();
			}

			do_action('asl_layout_before_shortcode', $id);

			ob_start();
			include ASL_PATH . 'includes/views/asl.shortcode.php';
			$out .= ob_get_clean();

			do_action('asl_layout_after_shortcode', $id);

			return $out;
		}

		/**
		 * Importing fonts does not work correctly it appears.
		 * Instead adding the links directly to the header is the best way to go.
		 */
		public function fonts() {
			// If custom font loading is disabled, exit
			$comp_options = wd_asl()->o['asl_compatibility'];
			if ( !$comp_options['load_google_fonts'] ) {
				return false;
			}

			$imports = array(
				'//fonts.googleapis.com/css?family=Open+Sans',
			);

			$imports = apply_filters('asl_custom_fonts', $imports);

			$fonts = array();
			foreach ( $imports as $import ) {
				$import = trim(str_replace(array( '@import url(', ');', 'https:', 'http:' ), '', $import));
				$import = trim(str_replace('//fonts.googleapis.com/css?family=', '', $import));
				if ( $import !== '' ) {
					$fonts[] = $import;
				}
			}

			if ( count($fonts) > 0 ) {
				?>
				<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
				<link rel="preload" as="style" href="//fonts.googleapis.com/css?family=<?php echo esc_attr(implode('|', $fonts)); ?>&display=swap" />
				<?php //phpcs:ignore ?>
				<link rel="stylesheet" href="//fonts.googleapis.com/css?family=<?php echo esc_attr(implode('|', $fonts)); ?>&display=swap" media="all" />
				<?php
			}

			return true;
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