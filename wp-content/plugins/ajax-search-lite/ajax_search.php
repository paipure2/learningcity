<?php
define('DOING_AJAX', true);

if ( !isset( $_POST['action']) ) {  // phpcs:ignore
	die('-1');
}

// make sure you update this line
// to the relative location of the wp-load.php
require_once '../../../wp-load.php';

// Typical headers
header('Content-Type: text/html');
send_nosniff_header();

// Disable caching
header('Cache-Control: no-cache');
header('Pragma: no-cache');

$action = esc_attr(trim($_POST['action'])); // phpcs:ignore

// A bit of security
$allowed_actions = WD_ASL_Ajax::getAll();
WD_ASL_Ajax::registerAll(true);


if ( in_array($action, $allowed_actions, true) ) {
	if ( is_user_logged_in() ) {
		do_action('ASL_' . $action);  // phpcs:ignore
	} else {
		do_action('ASL_nopriv_' . $action);  // phpcs:ignore
	}
} else {
	die('-1');
}
