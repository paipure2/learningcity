<?php
/**
 * Keyword exception options
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>


<p class="infoMsg"><?php esc_html_e('Keyword exceptions will be replaced with an empty string "" in the search phrase.', 'ajax-search-lite'); ?></p>
<div class="item">
	<?php
	/** @noinspection PhpUndefinedVariableInspection */
	new wd_TextareaExpandable('kw_exceptions', __('Keyword exceptions - replace anywhere', 'ajax-search-lite'), $sd['kw_exceptions']);
	?>
	<p class="descMsg"><?php esc_html_e('<strong>Comma separated list</strong> of keywords you want to remove or ban. Matching anything, even partial words.', 'ajax-search-lite'); ?></p>
</div>
<div class="item">
	<?php
	new wd_TextareaExpandable('kw_exceptions_e', __('Keyword exceptions - replace whole words only', 'ajax-search-lite'), $sd['kw_exceptions_e']);
	?>
	<p class="descMsg"><?php esc_html_e('<strong>Comma separated list</strong> of keywords you want to remove or ban. Only matching whole words between word boundaries.', 'ajax-search-lite'); ?></p>
</div>
