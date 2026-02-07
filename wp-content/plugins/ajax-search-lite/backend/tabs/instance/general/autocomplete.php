<?php
/**
 * Result autocomplete options
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<div class="item">
<?php
	/** @noinspection PhpUndefinedVariableInspection */
	new wpdreamsYesNo('autocomplete', __('Turn on google search autocomplete?', 'ajax-search-lite'), $sd['autocomplete']);
?>
	<p class="descMsg"><?php esc_html_e('Autocomplete feature will try to help the user finish what is being typed into the search box.', 'ajax-search-lite'); ?></p>
</div>
<div class="item">
<?php
	new wpdreamsYesNo('kw_suggestions', __('Turn on google search keyword suggestions?', 'ajax-search-lite'), $sd['kw_suggestions']);
?>
	<p class="descMsg"><?php esc_html_e('Keyword suggestions appear when no results match the keyword.', 'ajax-search-lite'); ?></p>
</div>
<div class="item">
<?php
	new wpdreamsTextSmall(
		'kw_length',
		__('Max. keyword length', 'ajax-search-lite'),
		$sd['kw_length']
	);
	?>
	<p class="descMsg"><?php esc_html_e('The length of each suggestion in characters. 30-60 is a good number to avoid too long suggestions.', 'ajax-search-lite'); ?></p>
</div>
<div class="item">
<?php
	new wpdreamsTextSmall(
		'kw_count',
		__('Max. keywords count', 'ajax-search-lite'),
		$sd['kw_count']
	);
	?>
</div>
<div class="item">
<?php
	new wpdreamsLanguageSelect(
		'kw_google_lang',
		__('Google suggestions language', 'ajax-search-lite'),
		$sd['kw_google_lang']
	);
	?>
</div>
