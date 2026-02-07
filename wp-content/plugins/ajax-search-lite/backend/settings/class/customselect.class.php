<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsCustomSelect') ) {
	/**
	 * Class wpdreamsCustomSelect
	 *
	 * A customisable drop down UI element.
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2014, Ernest Marcinko
	 */
	class wpdreamsCustomSelect extends wpdreamsType {
		private array $selects   = array();
		private string $selected = '';

		public function getType() {
			parent::getType();
			$this->processData();
			echo "<div class='wpdreamsCustomSelect'>";
			echo "<label for='wpdreamscustomselect_" . esc_attr(self::$_instancenumber) . "'>" . esc_html($this->label) . '</label>';
			echo "<select isparam=1 class='wpdreamscustomselect' id='wpdreamscustomselect_" . esc_attr(self::$_instancenumber) . "' name='" . esc_attr($this->name) . "'>";
			foreach ( $this->selects as $sel ) {
				if ( $sel['value'] === $this->selected ) {
					echo "<option value='" . esc_attr($sel['value']) . "' selected='selected'>" . esc_attr($sel['option']) . '</option>';
				} else {
					echo "<option value='" . esc_attr($sel['value']) . "'>" . esc_attr($sel['option']) . '</option>';
				}
			}
			echo '</select>';
			echo "<div class='triggerer'></div>
      </div>";
		}

		public function processData() {
			$this->selects  = $this->data['selects'];
			$this->selected = $this->data['value'];
		}

		final public function getData() {
			return $this->data;
		}

		final public function getSelected(): string {
			return $this->selected;
		}
	}
}
