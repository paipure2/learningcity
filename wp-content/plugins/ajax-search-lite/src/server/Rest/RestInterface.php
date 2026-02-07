<?php

namespace WPDRMS\ASL\Rest;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

interface RestInterface {
	/**
	 * @return self
	 */
	public static function instance();

	public function registerRoutes(): void;
}
