<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsType') ) {
	/**
	 * (abstract) Class wpdreamsType
	 *
	 * Parent of each type defined in this directory. This class should not be used to make an instance.
	 * Each new child type should follow this general interpretation.
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2012, Ernest Marcinko
	 */
	abstract class wpdreamsType {
		protected static int $_instancenumber = 0;
		protected static int $_errors         = 0;

		protected string $name;
		protected string $label;
		protected $default_data;
		protected $value;
		protected $data;

		public function __construct( string $name, string $label, $data ) {
			$this->name         = $name;
			$this->label        = $label;
			$this->default_data = $data; // Preserving constructor default data after posting
			$this->data         = $data;
			$this->value        = is_array($this->data) && isset($this->data['value']) ? $this->data['value'] : $this->data;
			++self::$_instancenumber;
			$this->getType();
		}

		/**
		 * Returns the raw data passed to the class
		 *
		 * @return mixed
		 */
		public function getData() {
			return $this->data;
		}

		/**
		 * Returns the name passed in the constructor
		 *
		 * @return string
		 */
		final public function getName(): string {
			return $this->name;
		}


		/**
		 * Called in the constructor by default.
		 *
		 * Checks for errors when a new value was posted.
		 */
		protected function getType() {}

		protected function decode_param( $v ) {
			if ( gettype($v) === 'string' && substr($v, 0, strlen('_decode_')) === '_decode_' ) {
				$v = substr($v, strlen('_decode_'));
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				$v = json_decode(base64_decode($v), true);
			}
			return $v;
		}

		protected function encode_param( $v ) {
			// No need to decode
			if ( gettype($v) === 'string' && substr($v, 0, strlen('_decode_')) === '_decode_' ) {
				return $v;
			} else {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				return '_decode_' . base64_encode(wp_json_encode($v));
			}
		}

		/**
		 * Returns the error count
		 *
		 * @return int
		 */
		public static function getErrorNum(): int {
			return self::$_errors;
		}
	}
}
