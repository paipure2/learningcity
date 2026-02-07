<?php
/**
 * Search source options
 *
 * @noinspection HtmlUnknownAttribute
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<div class="item">
	<?php
	/** @noinspection PhpUndefinedVariableInspection */
	new wpdreamsYesNo(
		'override_search_form',
		__('Try to replace the theme search with Ajax Search Lite form?', 'ajax-search-lite'),
		$sd['override_search_form']
	);
	?>
	<p class="descMsg"><?php esc_html_e('Works with most themes, which use the searchform.php theme file to display their search forms.', 'ajax-search-lite'); ?></p>
</div>
<?php if ( class_exists('WooCommerce') ) : ?>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'override_woo_search_form',
		__('Try to replace the WooCommerce search with Ajax Search Lite form?', 'ajax-search-lite'),
		$sd['override_woo_search_form']
	);
	?>
	<p class="descMsg"><?php esc_html_e('Works with most themes, which use the searchform.php theme file to display their search forms.', 'ajax-search-lite'); ?></p>
</div>
<?php endif; ?>
<div class="item">
<?php
	new wpdreamsCustomPostTypes(
		'customtypes',
		__('Search in custom post types', 'ajax-search-lite'),
		$sd['customtypes']
	);
	?>
	</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'searchintitle',
		__('Search in title?', 'ajax-search-lite'),
		$sd['searchintitle']
	);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'searchincontent',
		__('Search in content?', 'ajax-search-lite'),
		$sd['searchincontent']
	);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'searchinexcerpt',
		__('Search in post excerpts?', 'ajax-search-lite'),
		$sd['searchinexcerpt']
	);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'search_in_permalinks',
		__('Search in permalinks?', 'ajax-search-lite'),
		$sd['search_in_permalinks']
	);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'search_in_ids',
		__('Search in post (and CPT) IDs?', 'ajax-search-lite'),
		$sd['search_in_ids']
	);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'search_all_cf',
		__('Search all custom fields?', 'ajax-search-lite'),
		$sd['search_all_cf']
	);
	?>
</div>
<div class="item" wd-disable-on="search_all_cf:1">
	<?php
	new wpdreamsCustomFields(
		'customfields',
		__('..or search in selected custom fields?', 'ajax-search-lite'),
		$sd['customfields']
	);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsText('post_status', __('Post statuses to search', 'ajax-search-lite'), $sd['post_status']);
	?>
	<p class="descMsg">
		<?php esc_html_e('Comma separated list. WP Defaults: publish, future, draft, pending, private, trash, auto-draft', 'ajax-search-lite'); ?>
	</p>
</div>
<div class="item it_engine_index">
	<?php
	new wpdreamsYesNo('show_password_protected_posts', __('Search and return password protected posts?', 'ajax-search-lite'), $sd['show_password_protected_posts']);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'searchinterms',
		__('Search in terms? (categories, tags)', 'ajax-search-lite'),
		$sd['searchinterms']
	);
	?>
</div>
