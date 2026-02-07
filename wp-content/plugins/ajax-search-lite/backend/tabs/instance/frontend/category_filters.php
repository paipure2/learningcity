<?php
/**
 * Search filter options
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>


<fieldset>
	<legend><?php esc_html_e('Category Filters', 'ajax-search-lite'); ?></legend>
	<div class="item">
		<?php
		/** @noinspection PhpUndefinedVariableInspection */
		new wpdreamsYesNo('show_frontend_search_settings', __('Show search settings on the frontend?', 'ajax-search-lite'), $sd['show_frontend_search_settings']);
		?>
	</div>
	<div class="item" style="text-align:center;">
		<?php esc_html_e('The default values of the checkboxes on the frontend are the values set above.', 'ajax-search-lite'); ?>
	</div>
	<div class="item">
		<?php
		new wpdreamsYesNo('showexactmatches', __('Show exact matches selector?', 'ajax-search-lite'), $sd['showexactmatches']);
		new wpdreamsText('exactmatchestext', 'Text', $sd['exactmatchestext']);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsYesNo('showsearchintitle', __('Show search in title selector?', 'ajax-search-lite'), $sd['showsearchintitle']);
		new wpdreamsText('searchintitletext', 'Text', $sd['searchintitletext']);
		?>
		</div>
	<div class="item">
		<?php
		new wpdreamsYesNo('showsearchincontent', __('Show search in content selector?', 'ajax-search-lite'), $sd['showsearchincontent']);
		new wpdreamsText('searchincontenttext', 'Text', $sd['searchincontenttext']);
		?>
		</div>
	<div class="item">
	<?php
		new wpdreamsCustomPostTypesEditable('showcustomtypes', __('Show search in custom post types selectors', 'ajax-search-lite'), $sd['showcustomtypes']);
	?>
	</div>
	<div class="item">
		<p class='infoMsg'><?php esc_html_e('Nor recommended if you have more than 500 categories! (the HTML output will get too big)', 'ajax-search-lite'); ?></p>
		<?php
		new wpdreamsYesNo('showsearchincategories', __('Show the categories selectors?', 'ajax-search-lite'), $sd['showsearchincategories']);
		?>
		</div>
	<div class="item">
		<?php
		new wpdreamsYesNo('showuncategorised', __('Show the uncategorised category?', 'ajax-search-lite'), $sd['showuncategorised']);
		?>
		</div>
	<div class="item">
	<?php
		new wpdreamsCategories('exsearchincategories', __('Select which categories exclude', 'ajax-search-lite'), $sd['exsearchincategories']);
	?>
	</div>
	<div class="item">
		<?php
		new wpdreamsText('exsearchincategoriestext', __('Categories filter box header text', 'ajax-search-lite'), $sd['exsearchincategoriestext']);
		?>
	</div>
</fieldset>
