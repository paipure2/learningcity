<?php
/**
 * Result accessibility options
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>


<fieldset>
	<legend><?php esc_html_e('Aria Labels', 'ajax-search-lite'); ?></legend>
	<div class="item">
		<?php
		/** @noinspection PhpUndefinedVariableInspection */
		new wpdreamsText(
			'aria_search_form_label',
			__('Search form aria-label', 'ajax-search-lite'),
			$sd['aria_search_form_label']
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsText(
			'aria_settings_form_label',
			__('Search Settings form aria-label', 'ajax-search-lite'),
			$sd['aria_settings_form_label']
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsText(
			'aria_search_input_label',
			__('Search input aria-label', 'ajax-search-lite'),
			$sd['aria_search_input_label']
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsText(
			'aria_search_autocomplete_label',
			__('Search autocomplete input aria-label', 'ajax-search-lite'),
			$sd['aria_search_autocomplete_label']
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsText(
			'aria_magnifier_label',
			__('Search magnifier button aria-label', 'ajax-search-lite'),
			$sd['aria_magnifier_label']
		);
		?>
	</div>
</fieldset>
