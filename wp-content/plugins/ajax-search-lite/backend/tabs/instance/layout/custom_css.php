<?php
/**
 * Custom CSS options
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<p class='infoMsg'>
	<?php
	esc_html_e(
		'This css will be added before the plugin as inline CSS so it has a precedence
    over plugin CSS. (you can override existing rules)',
		'ajax-search-lite'
	);
	?>
</p>
<div class="item">
	<?php
	/** @noinspection PhpUndefinedVariableInspection */
	new wpdreamsTextarea('custom_css_code', __('Custom CSS', 'ajax-search-lite'), $sd['custom_css_code']);
	?>
</div>
