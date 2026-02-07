<?php
namespace WPDRMS\ASL;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

class Autoloader {
	protected static $_instance;

	protected $aliases = array(
	);

	private function __construct() {
		defined('ABSPATH') or die();

		spl_autoload_register(array(
			$this, 'loader'
		));
	}

	function loader( $class ) {

		// project-specific namespace prefix
		$prefix = 'WPDRMS\\ASL\\';

		// base directory for the namespace prefix
		$base_dir = ASL_AUTOLOAD_PATH;

		// does the class use the namespace prefix?
		$len = strlen($prefix);

		if ( strncmp($prefix, $class, $len) !== 0 ) {
			// is this an alias?
			if ( isset($this->aliases[$class]) ) {
				if ( !class_exists($this->aliases[$class]) ) {
					$this->loader($this->aliases[$class]);
				}

				if ( class_exists($this->aliases[$class]) ) {
					/**
					 * Create class alias for old class names
					 */
					class_alias($this->aliases[$class], $class);
				}
			}
		} else {
			// get the relative class name
			$relative_class = substr($class, $len);

			// replace the namespace prefix with the base directory, replace namespace
			// separators with directory separators in the relative class name, append
			// with .php
			$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

			// if the file exists, require it
			if ( file_exists($file) ) {
				require $file;
			}
		}
	}

	// ------------------------------------------------------------
	//   ---------------- SINGLETON SPECIFIC --------------------
	// ------------------------------------------------------------
	public static function getInstance() {
		if ( ! ( self::$_instance instanceof self ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
Autoloader::getInstance();