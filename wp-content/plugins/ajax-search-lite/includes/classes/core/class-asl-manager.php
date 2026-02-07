<?php

use WPDRMS\ASL\Core\Factory;
use WPDRMS\ASL\Utils\Polylang\StringTranslations;

if ( !defined('ABSPATH') ) {
	die('-1');
}

if ( !class_exists('WD_ASL_Manager') ) {
	/**
	 * Class WD_ASL_Manager
	 *
	 * This is the main controller class of the plugin, should be instantiated from the plugin main file.
	 *
	 * @class         WD_ASL_Manager
	 * @version       1.0
	 * @package       AjaxSearchLite/Classes/Core
	 * @category      Class
	 * @author        Ernest Marcinko
	 */
	class WD_ASL_Manager {

		/**
		 * Context of the current WP environment
		 *
		 * Is used to include the correct and only necessary files for each context to save performance
		 *
		 * Possible values:
		 *  ajax - an ajax call triggered by the search
		 *  frontend - simple front-end call, or an ajax request not triggered by ASP
		 *  backend - on any of the plugin back-end pages
		 *  global_backend - on any other back-end page
		 *  special - special cases
		 *
		 * @since 1.0
		 * @var string
		 */
		private $context = 'frontend';

		/**
		 * Core singleton class
		 *
		 * @var WD_ASL_Manager self
		 */
		private static $_instance;

		/**
		 * Initialize and run the plugin-in
		 */
		private function __construct() {

			do_action('wd_asl_before_load');

			$this->preLoad();
			$this->loadInstances();
			/**
			 * Available after this point:
			 *      (WD_ASL_Init) wd_asl()->instances, (global) $wd_asl->instances
			 */
			register_activation_hook(ASL_FILE, array( $this, 'activationHook' ));
			/**
			 * Available after this point:
			 *      (array) wd_asl()->options, (global) $wd_asl->options
			 *      (WD_ASL_Init) wd_asl()->init, (global) $wd_asl->init
			 *      (WD_ASL_DBMan) wd_asl()->db, (global) $wd_asl->db
			 */
			add_action( 'init', array( $this, 'init' ), 0 );
		}

		public function init() {
			// Check if the plugin needs to be stopped on certain conditions
			// ..this needs to be here, otherwise filter not accesible from functions.php
			if ( $this->stopLoading() ) {
				return false;
			}

			$this->getContext();
			/**
			 * Available after this point:
			 *      $this->context
			 */
			$this->loadIncludes();
			$this->loadShortcodes();
			$this->loadAssets();
			$this->loadMenu();

			$this->loadHooks();

			wd_asl()->init->safety_check();

			// Late init, just before footer print scripts
			add_action('wp_footer', array( $this, 'lateInit' ), 99);

			do_action('wd_asl_loaded');

			return true;
		}

		private function stopLoading() {
			$ret = false;

			if ( isset($_GET, $_GET['action']) ) { // phpcs:ignore
				if ( $_GET['action'] === 'ere_property_search_ajax' ) { // phpcs:ignore
					$ret = true;
				}
			}

			// Allow filtering this condition
			return apply_filters('asl_stop_loading', $ret);
		}


		/**
		 *  Preloading: for functions and other stuff needed
		 */
		private function preLoad() {
			require_once ASL_PATH . '/backend/settings/default_options.php';
			require_once ASL_CLASSES_PATH . 'etc/class.asl-mb.php';

			// We need to initialize the init here to get the init->table() function
			wd_asl()->init = WD_ASL_Init::getInstance();

			StringTranslations::init();
		}

		/**
		 * Gets the call context for further use
		 */
		public function getContext() {

			$backend_pages = WD_ASL_Menu::getMenuPages();

			/**
			 * Known actions, only checking
			 */
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( !empty($_POST['action']) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( in_array($_POST['action'], WD_ASL_Ajax::getAll(), true) ) {
					$this->context = 'ajax';
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset($_POST['wd_required']) ) {
					$this->context = 'special';
				}
				// If it is not part of the plugin ajax actions, the context stays "frontend"
			} elseif ( !empty($_GET['page']) && in_array($_GET['page'], $backend_pages, true) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->context = 'backend';
			} elseif ( is_admin() ) {
				$this->context = 'global_backend';
			} else {
				$this->context = 'frontend';
			}

			return $this->context;
		}

		/**
		 * Loads the instance data into the global scope
		 */
		private function loadInstances() {

			wd_asl()->instances = WD_ASL_Instances::getInstance();
		}

		/**
		 * Loads the required files based on the context
		 */
		private function loadIncludes() {

			require_once ASL_FUNCTIONS_PATH . 'functions.php';
			require_once ASL_CLASSES_PATH . 'ajax/ajax.inc.php';
			require_once ASL_CLASSES_PATH . 'filters/filters.inc.php';
			require_once ASL_CLASSES_PATH . 'etc/class-asl_helpers.php';
			require_once ASL_CLASSES_PATH . 'cache/cache.inc.php';
			require_once ASL_CLASSES_PATH . 'suggest/suggest.inc.php';
			require_once ASL_CLASSES_PATH . 'search/search.inc.php';
			require_once ASL_CLASSES_PATH . 'shortcodes/shortcodes.inc.php';

			switch ( $this->context ) {
				case 'special':
					require_once ASL_PATH . '/backend/settings/types.inc.php';
					break;
				case 'ajax':
					break;
				case 'frontend':
					break;
				case 'backend':
					require_once ASL_PATH . '/backend/settings/types.inc.php';
					break;
				case 'global_backend':
					break;
				default:
					break;
			}

			// Lifting some weight off from ajax requests
			if ( $this->context !== 'ajax' ) {
				require_once ASL_CLASSES_PATH . 'actions/actions.inc.php';
				/* Includes on Post/Page/Custom post type edit pages */
				require_once ASL_CLASSES_PATH . 'widgets/widgets.inc.php';
			}
		}

		/**
		 * Use the Shorcodes loader to assign the shortcodes to handler classes
		 */
		private function loadShortcodes() {

			WD_ASL_Shortcodes::registerAll();
		}

		/**
		 * Runs the Assets loader
		 */
		private function loadAssets() {
			// JS
			// WD_MS_Assets::loadJS("ms_search_js");

			// CSS
			// WD_MS_Assets::loadCSS("ms_search_css_basic");

			if ( $this->context === 'backend' ) {
				add_action('admin_enqueue_scripts', array( wd_asl()->init, 'scripts' ));
			}

			if ( $this->context === 'frontend' || $this->context === 'backend' ) {
				add_action('wp_enqueue_scripts', array( wd_asl()->init, 'styles' ));
				add_action('wp_enqueue_scripts', array( wd_asl()->init, 'scripts' ));
				add_action('wp_footer', array( wd_asl()->init, 'footer' ));
			}
		}

		/**
		 * Generates the menu
		 */
		private function loadMenu() {

			add_action('admin_menu', array( 'WD_ASL_Menu', 'register' ));
		}

		private function loadHooks() {
			$factory = Factory::instance();
			// Register handlers only if the context is ajax indeed
			if ( $this->context === 'ajax' ) {
				WD_ASL_Ajax::registerAll();
			}

			if ( $this->context !== 'ajax' ) {
				// Editor blocks
				foreach ( $factory->get( 'Block' ) as $block ) {
					$block->register();
				}

				if ( $this->context === 'backend' ) {
					WD_ASL_Actions::register('admin_init', 'Compatibility');
				}

				WD_ASL_Actions::registerAll();

				add_action(
					'rest_api_init',
					function () {
						foreach ( \WPDRMS\ASL\Core\Factory::instance()->get('Rest') as $rest ) {
							$rest->registerRoutes();
						}
					}
				);
			}

			WD_ASL_Filters::registerAll();
		}

		/**
		 * Run at the plugin activation
		 */
		public function activationHook() {

			// Run the activation tasks
			wd_asl()->init->activate();
		}

		/**
		 * This is triggered in the footer. Used for conditional loading assets and stuff.
		 */
		public function lateInit() {}


		// ------------------------------------------------------------
		// ---------------- SINGLETON SPECIFIC --------------------
		// ------------------------------------------------------------

		/**
		 * Get the instane of WD_ASL_Manager
		 *
		 * @return self
		 */
		public static function getInstance() {
			if ( !( self::$_instance instanceof self ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
}
