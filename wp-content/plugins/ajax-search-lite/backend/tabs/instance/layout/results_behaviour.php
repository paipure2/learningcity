<?php
/**
 * Results behavior options
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<div class="item">
	<?php
	/** @noinspection PhpUndefinedVariableInspection */
	new wpdreamsYesNo('results_click_blank', __('Open the results in a new window?', 'ajax-search-lite'), $sd['results_click_blank']);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo('scroll_to_results', __('Sroll the window to the result list?', 'ajax-search-lite'), $sd['scroll_to_results']);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo('resultareaclickable', __('Make the whole result area clickable?', 'ajax-search-lite'), $sd['resultareaclickable']);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo('close_on_document_click', __('Close result list on document click?', 'ajax-search-lite'), $sd['close_on_document_click']);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo('show_close_icon', __('Show the close icon?', 'ajax-search-lite'), $sd['show_close_icon']);
	?>
</div>
<div class="item">
<?php
	new wpdreamsText('noresultstext', __('No results text', 'ajax-search-lite'), $sd['noresultstext']);
?>
</div>
<div class="item">
<?php
	new wpdreamsText('didyoumeantext', __('Did you mean text', 'ajax-search-lite'), $sd['didyoumeantext']);
?>
</div>
