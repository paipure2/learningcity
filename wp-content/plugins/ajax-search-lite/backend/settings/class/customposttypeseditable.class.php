<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsCustomPostTypesEditable') ) {
	/**
	 * Class wpdreamsCustomPostTypesEditable
	 *
	 * A custom post types selector UI element with editable titles.
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2014, Ernest Marcinko
	 */
	class wpdreamsCustomPostTypesEditable extends wpdreamsType {
		private array $selected = array();
		private array $types    = array();

		public function getType() {
			parent::getType();
			$this->processData();
			$this->types = get_post_types(
				array(
					'public'   => true,
					'_builtin' => false,
				),
				'objects',
				'OR'
			);
			$exclude     = array(
				'revision',
				'nav_menu_item',
				'attachment',
				'peepso-post',
				'peepso-comment',
				'acf',
				'oembed_cache',
				'user_request',
				'wp_block',
				'shop_coupon',
				'avada_page_options',
				'_pods_template',
				'_pods_pod',
				'_pods_field',
				'bp-email',
				'lbmn_archive',
				'lbmn_footer',
				'mc4wp-form',
				'elementor-front',
				'elementor-icon',
				'fusion_template',
				'fusion_element',
				'wc_product_tab',
				'customize_changeset',
				'wpcf7_contact_form',
				'dslc_templates',
				'acf-field',
				'acf-group',
				'acf-groups',
				'acf-field-group',
				'custom_css',
			);
			foreach ( $this->types as $k => $v ) {
				if ( in_array($k, $exclude, true) ) {
					unset($this->types[ $k ]);
				}
			}
			echo "
      <div class='wpdreamsCustomPostTypesEditable' id='wpdreamsCustomPostTypesEditable-" . esc_attr(self::$_instancenumber) . "'>
        <fieldset>
          <legend>" . esc_html($this->label) . '</legend>';
			echo '<div class="sortablecontainer" id="sortablecontainer' . esc_attr(self::$_instancenumber) . '">
            <p>Available post types</p><ul id="sortable' . esc_attr(self::$_instancenumber) . '" class="connectedSortable">';
			foreach ( $this->types as $k => $v ) {
				if ( !wd_in_array_r($k, $this->selected) ) {
					echo '<li class="ui-state-default ui-left" style="background: #ddd;">
				          <label>' . esc_attr($k) . '</label>
				          <input type="text" value="' . esc_attr($k) . '"/>
				          </li>';
				}
			}
			echo '</ul></div>';
			echo '<div class="sortablecontainer"><p>Drag here the post types you want to use!</p><ul id="sortable_conn' . esc_attr(self::$_instancenumber) . '" class="connectedSortable">';
			foreach ( $this->selected as $v ) {
				echo '<li class="ui-state-default ui-left" style="background: #ddd;">
				        <label>' . esc_attr($v[0]) . '</label>
				        <input type="text" value="' . esc_attr($v[1]) . '"/>
				        </li>';
			}
			echo '</ul></div>';
			echo "
         <input isparam=1 type='hidden' value='" . esc_attr($this->data) . "' name='" . esc_attr($this->name) . "'>";
			echo "
         <input type='hidden' value='wpdreamsCustomPostTypesEditable' name='classname-" . esc_attr($this->name) . "'>";
			echo '
        </fieldset>
      </div>';
		}

		public function processData() {
			$this->data = stripslashes(str_replace("\n", '', $this->data));
			if ( $this->data !== '' ) {
				$selected = explode('|', $this->data);
				foreach ( $selected as $v ) {
					$this->selected[] = explode(';', $v);
				}
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
