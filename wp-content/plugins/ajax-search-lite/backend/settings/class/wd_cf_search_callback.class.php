<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wd_CFSearchCallBack') ) {
	/**
	 * Class wd_CFSearchCallBack
	 *
	 * Custom field search, which passes the results to a JS callback method
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2016, Ernest Marcinko
	 */
	class wd_CFSearchCallBack extends wpdreamsType {
		private static string $delimiter = '!!!CFRES!!!';
		private array $args              = array(
			'callback'          => '',       // javacsript function name in the windows scope | if empty, shows results
			'placeholder'       => 'Search custom fields..',
			'search_values'     => 0,
			'limit'             => 500,
			'controls_position' => 'right',
			'class'             => '',
		);

		public function getType() {
			parent::getType();
			$this->processData();
			$this->args['delimiter'] = self::$delimiter;
			?>
			<div class='wd_cf_search<?php echo $this->args['class'] !== '' ? ' ' . esc_attr($this->args['class']) : ''; ?>'
				id='wd_cf_search-<?php echo esc_attr(self::$_instancenumber); ?>'>
				<?php
				if ( $this->args['controls_position'] === 'left' ) {
					$this->printControls();}
				?>
				<?php echo esc_attr($this->label); ?> <input type="search" name="<?php echo esc_attr($this->name); ?>"
													class="wd_cf_search"
													value="<?php echo ( is_array($this->data) && isset($this->data['value']) ) ? esc_attr($this->data['value']) : esc_attr($this->data); ?>"
													placeholder="<?php echo esc_attr($this->args['placeholder']); ?>"/>
				<input type='hidden'
						value="<?php echo esc_attr(wp_json_encode($this->args)); ?>"
						class="wd_args">
				<input type="hidden" name="asl_cf_search_nonce" value="<?php echo esc_attr(wp_create_nonce('asl-cf-search-nonce')); ?>">
				<?php
				if ( $this->args['controls_position'] !== 'left' ) {
					$this->printControls();}
				?>
				<div class="wd_cf_search_res"></div>
			</div>
			<?php
		}

		private function printControls() {
			?>
			<span class="loading-small hiddend"></span>
			<div class="wd_ts_close hiddend">
				<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 512 512" enable-background="new 0 0 512 512" xml:space="preserve">
						<polygon points="438.393,374.595 319.757,255.977 438.378,137.348 374.595,73.607 255.995,192.225 137.375,73.622 73.607,137.352 192.246,255.983 73.622,374.625 137.352,438.393 256.002,319.734 374.652,438.378 "></polygon>
					</svg>
			</div>
			<?php
		}

		public static function searchCF() {
			global $wpdb;
			if ( isset($_POST['wd_phrase'], $_POST['wd_args'], $_POST['asl_cf_search_nonce']) &&
				current_user_can( 'manage_options' )
			) {
				$phrase = '%' . trim(sanitize_text_field(wp_unslash($_POST['wd_phrase']))) . '%';
				$data   = json_decode(sanitize_text_field(wp_unslash($_POST['wd_args'])), true);
				$limit  = max(min(1000, intval($data['limit'])), 1);

				if ( wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['asl_cf_search_nonce'])), 'asl-cf-search-nonce') ) {
					if ( $data['search_values'] ) {
						$cf_query = $wpdb->prepare(
							"SELECT DISTINCT(meta_key) FROM $wpdb->postmeta WHERE meta_key LIKE %s OR meta_value LIKE %s LIMIT %d",
							$phrase,
							$phrase,
							$limit
						);
					} else {
						$cf_query = $wpdb->prepare(
							"SELECT DISTINCT(meta_key) FROM $wpdb->postmeta WHERE meta_key LIKE %s LIMIT %d",
							$phrase,
							$limit
						);
					}
					$cf_results = wp_cache_get('asl-cf-search-cb-' . $phrase, '', false, $found);
					if ( !$found ) {
						// Query is safely prepared at this point
						// phpcs:ignore
						$cf_results = $wpdb->get_results($cf_query, OBJECT);
						if ( !is_wp_error($cf_results) ) {
							wp_cache_set('asl-cf-search-cb-' . $phrase, $cf_results);
						}
					}

					// Delimiter is safe
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					print self::$delimiter . wp_json_encode($cf_results) . self::$delimiter;
				} else {
					// Delimiter is safe
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					print self::$delimiter . esc_html__('The nonce has expired or missing, please reload the page!', 'ajax-search-lite') . self::$delimiter;
				}
			} else {
				print esc_html__('Missing data.', 'ajax-search-lite');
			}
			die();
		}

		public function processData() {
			// Get the args first if exists
			if ( is_array($this->data) && isset($this->data['args']) ) {
				$this->args = array_merge($this->args, $this->data['args']);
			}

			if ( is_array($this->data) && isset($this->data['value']) ) {
				// If called from back-end non-post context
				$this->data = $this->data['value'];
			}
		}

		final public function getData() {
			return $this->data;
		}
	}
}

if ( !has_action('wp_ajax_wd_search_cf') ) {
	add_action('wp_ajax_wd_search_cf', 'wd_CFSearchCallBack::searchCF');
}
