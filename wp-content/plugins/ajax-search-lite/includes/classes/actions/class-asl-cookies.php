<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}

if ( !class_exists('WD_ASL_Cookies_Action') ) {
	/**
	 * Class WD_ASL_Cookies_Action
	 *
	 * Cookie related stuff
	 *
	 * @class         WD_ASL_Cookies_Action
	 * @version       1.0
	 * @package       AjaxSearchLite/Classes/Actions
	 * @category      Class
	 * @author        Ernest Marcinko
	 */
	class WD_ASL_Cookies_Action extends WD_ASL_Action_Abstract {

		public function handle() {
			// Forcefully unset the cookies, if requested
			if ( isset($_GET['asl_unset_cookies']) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$parse = wp_parse_url(get_bloginfo('url'));
				$host  = $parse['host'];
				setcookie('asl_data', '', time() - 7200, '/', $host, is_ssl() );
				setcookie('asl_phrase', '', time() - 7200, '/', $host, is_ssl() );
				unset($_COOKIE['asl_data']);
				unset($_COOKIE['asl_phrase']);
			}

			/**
			 * NOTES
			 * ---------------------------------------
			 * DO NOT DELETE THE COOKIES HERE, UNLESS A CERTAIN CRITERIA MET!!
			 * This filter is executed on ajax requests and redirects and during all
			 * kinds of background tasks. Unset only if the criteria is deterministic,
			 * and applies on a very specific case. (like the example above)
			 */

			// Set cookie if this is search, and asl is active
			// Frontend public request, no need for nonce here
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			if ( isset($_POST['asl_active']) && isset($_GET['s']) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset($_POST['p_asl_data']) || isset($_POST['np_asl_data']) ) {
					$parse = wp_parse_url(get_bloginfo('url'));
					$host  = $parse['host'];

					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_p_data = sanitize_text_field(wp_unslash(isset($_POST['p_asl_data']) ? $_POST['p_asl_data'] : $_POST['np_asl_data']));
					setcookie('asl_data', $_p_data, time() + ( 30 * DAY_IN_SECONDS ), '/', $host, is_ssl() );
					// Sanitization is handled when used
					setcookie('asl_phrase', wp_unslash($_GET['s']), time() + ( 30 * DAY_IN_SECONDS ), '/', $host, is_ssl() ); // phpcs:ignore
					$_COOKIE['asl_data']   = $_p_data;
					$_COOKIE['asl_phrase'] = $_GET['s']; // phpcs:ignore
				}
			}
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
