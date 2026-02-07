<?php
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

/**
 * Includes all files required for keyword suggestions
 */

require_once ASL_CLASSES_PATH . 'suggest/suggest-abstract.class.php';
require_once ASL_CLASSES_PATH . 'suggest/google_suggest.class.php';
require_once ASL_CLASSES_PATH . 'suggest/suggest-wrapper.class.php';
