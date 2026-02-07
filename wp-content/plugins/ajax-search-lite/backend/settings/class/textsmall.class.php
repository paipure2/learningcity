<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsTextSmall') ) {
	/**
	 * Class wpdreamsTextSmall
	 *
	 * A 5 characters wide text input field.
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2014, Ernest Marcinko
	 */
	class wpdreamsTextSmall extends wpdreamsType {

		private string $icon          = 'none';
		private string $suffix        = '';
		private string $input_classes = '';
		private array $icon_msg;

		public function getType() {
			parent::getType();
			$this->processData();
			echo "<div class='wpdreamsTextSmall'>";
			if ( $this->label !== '' ) {
				echo "<label for='wpdreamstextsmall_" . esc_attr(self::$_instancenumber) . "'>" . esc_html($this->label) . '</label>';
			}

			if ( $this->icon !== 'none' ) {
				?>
				<span
					title="<?php echo isset($this->icon_msg[ $this->icon ]) ? esc_attr($this->icon_msg[ $this->icon ]) : ''; ?>"
					class="wpd-txt-small-icon wpd-txt-small-icon-<?php echo esc_attr($this->icon); ?>">
				</span>
				<?php
			}
			echo "<input isparam=1 class='small " . esc_attr($this->input_classes) . "' type='text' id='wpdreamstextsmall_" .
				esc_attr(self::$_instancenumber) . "' name='" . esc_attr($this->name) . 
				"' value=\"" . esc_html(wp_unslash($this->value)) . '" />';
			echo esc_html($this->suffix);
			echo "
        <div class='triggerer'></div>
      </div>";
		}

		public function processData() {
			$this->icon_msg = array(
				'phone'   => __('Phone devices, on 0px to 640px widths', 'ajax-search-lite'),
				'tablet'  => __('Tablet devices, on 641px to 1024px widths', 'ajax-search-lite'),
				'desktop' => __('Desktop devices, 1025px width  and higher', 'ajax-search-lite'),
			);

			if ( is_array($this->data) ) {
				$this->icon          = $this->data['icon'] ?? $this->icon;
				$this->suffix        = $this->data['suffix'] ?? $this->suffix;
				$this->input_classes = $this->data['inputClasses'] ?? $this->input_classes;
			}
		}
	}
}