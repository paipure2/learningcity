<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
/**
 * Class wpdreamsText
 *
 * A simple text input field.
 *
 * @package  WPDreams/OptionsFramework/Classes
 * @category Class
 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
 * @copyright Copyright (c) 2014, Ernest Marcinko
 */

if ( !class_exists('wpdreamsText') ) {
	class wpdreamsText extends wpdreamsType {
		public function getType() {
			parent::getType();
			echo "<div class='wpdreamsText'>";
			if ( $this->label !== '' ) {
				echo "<label for='wpdreamstext_" . esc_attr(self::$_instancenumber) . "'>" . esc_html($this->label) . '</label>';
			}
			echo "<input isparam=1 type='text' 
			id='wpdreamstext_" . esc_attr(self::$_instancenumber) . "' 
			name='" . esc_attr($this->name) . "' 
			value=\"" . esc_html(wp_unslash($this->data)) . '" />';
			echo "
        <div class='triggerer'></div>
      </div>";
		}
	}
}
