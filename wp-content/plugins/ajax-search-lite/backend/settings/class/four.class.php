<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsFour') ) {
	/**
	 * Class wpdreamsFour
	 *
	 * Holds four separate values in four input boxes labeled: Top Bottom Right Left.
	 * Good input choice for CSS padding, margin, border-width etc..
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2014, Ernest Marcinko
	 */
	class wpdreamsFour extends wpdreamsType {
		private string $desc;
		private string $top;
		private string $bottom;
		private string $right;
		private string $left;

		public function getType() {
			parent::getType();
			$this->processData();
			echo "
      <div class='wpdreamsFour'>
        <fieldset>
          <legend>" . esc_attr($this->label) . '</legend>';
			echo "
         <label>Top</label><input type='text' class='threedigit' name='topleft' value='" . esc_attr($this->top) . "' />
         <label>Bottom</label><input type='text' class='threedigit' name='bottomright' value='" . esc_attr($this->bottom) . "' />
         <label>Right</label><input type='text' class='threedigit' name='topright' value='" . esc_attr($this->right) . "' />
         <label>Left</label><input type='text' class='threedigit' name='bottomleft' value='" . esc_attr($this->left) . "' />
         <input isparam=1 type='hidden' value='" . esc_attr($this->data) . "' name='" . esc_attr($this->name) . "'>
         <div class='triggerer'></div>
         <p class='descMsg'>" . esc_html($this->desc) . '</p>
        </fieldset>
      </div>';
		}

		public function processData() {
			$this->desc = $this->data['desc'];
			$this->data = str_replace("\n", '', $this->data['value']);
			preg_match('/\|\|(.*?)\|\|(.*?)\|\|(.*?)\|\|(.*?)\|\|/', $this->data, $matches);
			$this->top    = $matches[1];
			$this->bottom = $matches[2];
			$this->right  = $matches[3];
			$this->left   = $matches[4];
		}

		final public function getData() {
			return $this->data;
		}

		final public function getSelected() {
			return array(
				'top'    => $this->top,
				'bottom' => $this->bottom,
				'right'  => $this->right,
				'left'   => $this->left,
			);
		}
	}
}
