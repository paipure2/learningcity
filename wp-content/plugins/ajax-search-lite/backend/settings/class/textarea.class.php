<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsTextarea') ) {
	/**
	 * Class wpdreamsTextarea
	 *
	 * A simple textarea field.
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2014, Ernest Marcinko
	 */
	class wpdreamsTextarea extends wpdreamsType {
		public function getType() {
			parent::getType();
			echo "<label style='vertical-align: top;' for='wpdreamstextarea_" . esc_attr(self::$_instancenumber) . "'>" . esc_html($this->label) . '</label>';
			echo "<textarea id='wpdreamstextarea_" . esc_attr(self::$_instancenumber) . "' name='" . esc_attr($this->name) . "'>" . esc_html(wp_unslash($this->data)) . '</textarea>';
		}
	}
}
