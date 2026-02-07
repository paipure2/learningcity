<?php
/**
 * Includes resources for types
 *
 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
 * @version 4.0
 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
 * @copyright Copyright (c) 2012, Ernest Marcinko
 */

/* Prevent direct access */
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

// Include the types
require 'class/type.class.php';
require 'class/border.class.php';
require 'class/categories.class.php';
require 'class/colorpicker.class.php';
require 'class/colorpickerdummy.class.php';
require 'class/customposttypes.class.php';
require 'class/customposttypeseditable.class.php';
require 'class/customselect.class.php';
require 'class/customfields.class.php';
require 'class/four.class.php';
require 'class/languageselect.class.php';
require 'class/text.class.php';
require 'class/textsmall.class.php';
require 'class/textarea.class.php';
require 'class/textarea-expandable.class.php';
require 'class/upload.class.php';
require 'class/yesno.class.php';
require 'class/wd_cf_search_callback.class.php';

add_action(
	'admin_print_styles',
	function () {
		$metadata = require_once ASL_PATH . 'build/css/admin-shared.asset.php';
		wp_enqueue_style(
			'wpd-admin-shared',
			ASL_URL_NP . 'build/css/admin-shared.css',
			$metadata['dependencies'],
			$metadata['version'],
		);

		$metadata = require_once ASL_PATH . 'build/css/components.asset.php';
		wp_enqueue_style(
			'wdo-components',
			ASL_URL_NP . 'build/css/components.css',
			$metadata['dependencies'],
			$metadata['version'],
		);

		wp_register_style('wpdreams-style', ASL_URL_NP . 'backend/settings/assets/style.css', array( 'wpdreams-tabs' ), ASL_CURR_VER_STRING);
		wp_enqueue_style('wpdreams-style');
		wp_register_style('wpdreams-style-hc', ASL_URL_NP . 'backend/settings/assets/style-hc.css', array( 'wpdreams-tabs' ), ASL_CURR_VER_STRING);
		wp_enqueue_style('wpdreams-style-hc');
		wp_register_style('wpdreams-jqueryui', ASL_URL_NP . 'backend/settings/assets/jquery-ui.css', array(), ASL_CURR_VER_STRING);
		wp_enqueue_style('wpdreams-jqueryui');
		wp_register_style('wpdreams-tabs', ASL_URL_NP . 'backend/settings/assets/tabs.css', array(), ASL_CURR_VER_STRING);
		wp_enqueue_style('wpdreams-tabs');
		wp_register_style('wpdreams-spectrum', ASL_URL_NP . 'backend/settings/assets/js/spectrum/spectrum.css', array(), ASL_CURR_VER_STRING);
		wp_enqueue_style('wpdreams-spectrum');
		wp_register_style('wpd-modal', ASL_URL_NP . 'backend/settings/assets/wpd-modal/wpd-modal.css', array(), ASL_CURR_VER_STRING);
		wp_enqueue_style('wpd-modal');
		wp_register_style('wpdreams-fa', ASL_URL_NP . 'backend/settings/assets/fa/css/all.min.css', array( 'wpdreams-style' ), ASL_CURR_VER_STRING);
		wp_enqueue_style('wpdreams-fa');
	}
);

add_action(
	'admin_enqueue_scripts',
	function () {
		wp_enqueue_media(); // For image uploader.
		wp_enqueue_script('jquery');

		wp_enqueue_script('jquery-ui-core', false, array( 'jquery' ), ASL_CURR_VER_STRING, true);
		wp_enqueue_script('jquery-ui-slider', false, array( 'jquery-ui-core' ), ASL_CURR_VER_STRING, true);
		wp_enqueue_script('jquery-ui-tabs', false, array( 'jquery-ui-core' ), ASL_CURR_VER_STRING, true);
		wp_enqueue_script('jquery-ui-sortable', false, array( 'jquery-ui-core' ), ASL_CURR_VER_STRING, true);
		wp_enqueue_script('jquery-ui-draggable', false, array( 'jquery-ui-core' ), ASL_CURR_VER_STRING, true);
		wp_enqueue_script('jquery-ui-datepicker', false, array( 'jquery-ui-core' ), ASL_CURR_VER_STRING, true);

		$metadata = require_once ASL_PATH . '/build/js/admin-global.asset.php'; // @phpstan-ignore-line
		wp_enqueue_script(
			'wdo-asp-global-backend',
			ASL_URL_NP . 'build/js/admin-global.js',
			$metadata['dependencies'],
			$metadata['version'],
			array(
				'in_footer' => true,
			)
		);

		wp_register_script(
			'wpdreams-types',
			ASL_URL_NP . 'backend/settings/assets/types.js',
			array(
				'jquery',
			),
			ASL_CURR_VER_STRING,
			true
		);
		wp_enqueue_script('wpdreams-types');

		wp_register_script(
			'wpdreams-tabs',
			ASL_URL_NP . 'backend/settings/assets/tabs.js',
			array(
				'jquery',
			),
			ASL_CURR_VER_STRING,
			true
		);
		wp_enqueue_script('wpdreams-tabs');

		wp_register_script(
			'wpdreams-upload',
			ASL_URL_NP . 'backend/settings/assets/upload.js',
			array(
				'jquery',
				'media-upload',
			),
			ASL_CURR_VER_STRING,
			true
		);
		wp_enqueue_script('wpdreams-upload');

		wp_register_script(
			'wpd-textarea-autosize',
			ASL_URL_NP . 'backend/settings/assets/textarea-autosize/jquery.textarea-autosize.js',
			array(
				'jquery',
			),
			ASL_CURR_VER_STRING,
			true
		);
		wp_enqueue_script('wpd-textarea-autosize');

		wp_register_script(
			'wpdreams-misc',
			ASL_URL_NP . 'backend/settings/assets/misc.js',
			array(
				'jquery',
			),
			ASL_CURR_VER_STRING,
			true
		);
		wp_enqueue_script('wpdreams-misc');

		wp_register_script(
			'wpdreams-spectrum',
			ASL_URL_NP . 'backend/settings/assets/js/spectrum/spectrum.js',
			array(
				'jquery',
			),
			ASL_CURR_VER_STRING,
			true
		);
		wp_enqueue_script('wpdreams-spectrum');

		wp_register_script('wpd-modal', ASL_URL_NP . 'backend/settings/assets/wpd-modal/wpd-modal.js', array( 'jquery' ), ASL_CURR_VER_STRING, true);
		wp_enqueue_script('wpd-modal');
	}
);
