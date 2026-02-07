<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsYesNo') ) {
	/**
	 * Class wpdreamsYesNo
	 *
	 * Displays an ON-OFF switch UI element. Same as wpdreamsOnOff
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2014, Ernest Marcinko
	 */
	class wpdreamsYesNo extends wpdreamsType {
		public function getType() {
			parent::getType();
			echo "<div class='wpdreamsYesNo" . ( ( $this->data ) ? ' active' : '' ) . "'>";
			echo "<label for='wpdreamstext_" . esc_attr(self::$_instancenumber) . "'>" . esc_html($this->label) . '</label>';
			echo "<input isparam=1 type='hidden' value='" . ( intval($this->data) === 1 ? 1 : 0 ) . "' name='" . esc_attr($this->name) . "'>";
			echo "<div class='wpdreamsYesNoInner'></div>";
			echo "<div class='triggerer'></div>";
			echo '</div>';
		}
	}
}
