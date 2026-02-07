<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsCustomPostTypes') ) {
	/**
	 * Class wpdreamsCustomPostTypes
	 *
	 * A custom post types selector UI element with.
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2017, Ernest Marcinko
	 */
	class wpdreamsCustomPostTypes extends wpdreamsType {
		private $types;
		private $selected;
		
		private $args = array(
			// phpcs:ignore
            'exclude' => array("revision", "nav_menu_item", "attachment")
		);

		public function getType() {
			parent::getType();
			$this->processData();

			echo "
      <div class='wpdreamsCustomPostTypes' id='wpdreamsCustomPostTypes-" . esc_attr(self::$_instancenumber) . "'>
        <fieldset>
          <legend>" . esc_html($this->label) . '</legend>';
			echo '<div class="sortablecontainer" id="sortablecontainer' . esc_attr(self::$_instancenumber) . '">
            <div class="arrow-all-left"></div>
            <div class="arrow-all-right"></div>
            <p>Available post types</p><ul id="sortable' . esc_attr(self::$_instancenumber) . '" class="connectedSortable">';
			if ( is_array($this->types) ) {
				foreach ( $this->types as $k => $v ) {
					if ( $this->selected === null || !in_array($k, $this->selected, true) ) {
						echo '<li class="ui-state-default" data-ptype="' . esc_attr($k) . '">'
							. esc_html($v->labels->name) .
							'<span class="extra_info">[' . esc_attr($k) . ']</span></li>';
					}
				}
			}
			echo '</ul></div>';
			echo '<div class="sortablecontainer"><p>Drag here the post types you want to use!</p><ul id="sortable_conn' . esc_attr(self::$_instancenumber) . '" class="connectedSortable">';
			if ( is_array($this->selected) ) {
				foreach ( $this->selected as $v ) {
					if ( !isset($this->types[ trim($v) ]) ) {
						continue;
					}
					echo '<li class="ui-state-default" data-ptype="' . esc_attr($v) . '">'
						. esc_html($this->types[ trim($v) ]->labels->name) .
						'<span class="extra_info">[' . esc_attr($v) . ']</span></li>';
				}
			}
			echo '</ul></div>';
			echo "
         <input isparam=1 type='hidden' value='" . esc_attr($this->data) . "' name='" . esc_attr($this->name) . "'>";
			echo "
         <input type='hidden' value='wpdreamsCustomPostTypes' name='classname-" . esc_attr($this->name) . "'>";
			echo '
        </fieldset>
      </div>';
		}

		public function processData() {
			// Get the args first if exists
			if ( is_array($this->data) && isset($this->data['args']) ) {
				$this->args = array_merge($this->args, $this->data['args']);
			}

			$this->types = get_post_types('', 'objects');
			foreach ( $this->types as $k => $v ) {
				if ( count($this->args['exclude']) > 0 && in_array($k, $this->args['exclude'], true) ) {
					unset($this->types[ $k ]);
					continue;
				}
				if ( $k === 'attachment' ) {
					$v->labels->name = 'Attachment - Media';
				}
			}

			if ( is_array($this->data) && isset($this->data['value']) ) {
				// If called from back-end non-post context
				$this->selected = $this->decode_param($this->data['value']);
				$this->data     = $this->encode_param($this->data['value']);
			} else {
				// POST method or something else
				$this->selected = $this->decode_param($this->data);
				$this->data     = $this->encode_param($this->data);
			}
		}

		final public function getData() {
			return $this->data;
		}

		final public function getSelected() {
			return $this->selected;
		}
	}
}
