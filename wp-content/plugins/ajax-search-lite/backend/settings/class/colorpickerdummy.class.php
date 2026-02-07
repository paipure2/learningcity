<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsColorPickerDummy') ) {
	/**
	 * Class wpdreamsColorPickerDummy
	 *
	 * A dummy colorpicker for using inside other elements. This does not have a frontend trigger method,
	 * so it can't be changed by the themeChooser class.
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2014, Ernest Marcinko
	 */
	class wpdreamsColorPickerDummy extends wpdreamsType {
		public function getType() {
			$this->data = wpdreams_admin_hex2rgb($this->data);
			$this->name = $this->name . '_colorpicker';
			echo "<span class='wpdreamsColorPicker'>";
			if ( $this->label !== '' ) {
				echo "<label for='wpdreamscolorpicker_" . esc_attr(self::$_instancenumber) . "'>" . esc_attr($this->label) . '</label>';
			}
			echo "<input 
            type='text' 
            class='color' 
            id='" . esc_attr($this->name) . "' id='wpdreamscolorpicker_" . esc_attr(self::$_instancenumber) . "'  
            name='" . esc_attr($this->name) . "' id='wpdreamscolorpicker_" . esc_attr(self::$_instancenumber) . "' 
            value='" . esc_attr($this->data) . "' />";
			echo "<div class='triggerer'></div>
      </span>";
		}
	}
}
