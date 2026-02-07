<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsCategories') ) {
	/**
	 * Class wpdreamsCategories
	 *
	 * Creates a cetegory selector UI element. Each category is stored separated by the "|" element.
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2014, Ernest Marcinko
	 */
	class wpdreamsCategories extends wpdreamsType {
		private array $selected = array();
		private array $types    = array();

		public function getType() {
			parent::getType();
			$this->processData();

			$args = array();
			if ( !empty($this->selected) ) {
				// phpcs:ignore
                $args = array('exclude' => implode(",", $this->selected));
			}
			$this->types = get_categories($args);
			echo "
      <div class='wpdreamsCategories' id='wpdreamsCategories-" . esc_attr(self::$_instancenumber) . "'>
        <fieldset>
          <legend>" . esc_html($this->label) . '</legend>';
			echo '<div class="sortablecontainer" id="sortablecontainer' . esc_attr(self::$_instancenumber) . '">
                  <div class="arrow-all-left"></div>
                  <div class="arrow-all-right"></div>
                Available categories<ul id="sortable' . esc_attr(self::$_instancenumber) . '" class="connectedSortable">';

			foreach ( $this->types as $v ) {
				if ( !in_array($v->term_id, $this->selected, true) ) {
					echo '<li class="ui-state-default" data-id="' . esc_attr($v->term_id) . '">' . esc_html($v->name) . '</li>';
				}
			}
			echo '</ul></div>';
			echo '<div class="sortablecontainer">Drag here the categories you want to exclude!<ul id="sortable_conn' . esc_attr(self::$_instancenumber) . '" class="connectedSortable">';
			if ( !empty($this->selected) ) {
				$args  = array( 'include' => implode(',', $this->selected) );
				$_cats = get_categories($args);
				foreach ( $_cats as $v ) {
					echo '<li class="ui-state-default" data-id="' . esc_attr($v->term_id) . '">' . esc_html($v->name) . '</li>';
				}
			}
			echo '</ul></div>';
			echo "
         <input isparam=1 type='hidden' value='" . esc_attr($this->data) . "' name='" . esc_attr($this->name) . "'>";
			echo "
         <input type='hidden' value='wpdreamsCategories' name='classname-" . esc_attr($this->name) . "'>";
			echo '
        </fieldset>
      </div>';
		}

		public function processData() {
			$this->data = str_replace("\n", '', $this->data);
			if ( $this->data !== '' ) {
				$this->selected = explode('|', $this->data);
			}
		}

		final public function getData() {
			return $this->data;
		}

		final public function getSelected(): array {
			return $this->selected;
		}
	}
}
