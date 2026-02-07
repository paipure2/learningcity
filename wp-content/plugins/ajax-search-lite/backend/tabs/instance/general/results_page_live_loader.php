<?php
/**
 * Results page live loader settings
 *
 * @noinspection HtmlUnknownAttribute
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<fieldset id="res_live_search">
	<legend>
		<?php esc_html_e('Results page live loader', 'ajax-search-lite'); ?>
		<span class="asl_legend_docs">
			<a target="_blank" href="https://documentation.ajaxsearchlite.com/general-options/results-page-live-loader"><span class="fa fa-book"></span>
				<?php esc_html_e('Documentation', 'ajax-search-lite'); ?>
			</a>
		</span>
	</legend>
	<div class="errorMsg">
		<?php
			esc_html_e(
				'Disclaimer: Live loading items to a page causes the script event handlers to detach on the affected elements - if there are
        interactive elements (pop-up buttons etc..) controlled by a script within the results, they will probably stop working after a live load.
        This cannot be prevented from this plugins perspective.',
				'ajax-search-lite'
			)
			?>
		<a href="https://documentation.ajaxsearchlite.com/general-options/results-page-live-loader" target="_blank"><?php esc_html_e('More information here.', 'ajax-search-lite'); ?></a>	</div>
	<div class="item">
		<?php
		/** @noinspection PhpUndefinedVariableInspection */
		new wpdreamsYesNo(
			'res_live_search',
			__('Live load the results on the results page? (experimental)', 'ajax-search-lite'),
			$sd['res_live_search']
		);
		?>
		<div class="descMsg">
			<?php esc_html_e('If this is enabled, and the current page is the results page, the plugin will try to load the results there, without reloading the page.', 'ajax-search-lite'); ?>
		</div>
	</div>
	<div class="item" wd-enable-on="res_live_search:1">
		<?php
		new wpdreamsText('res_live_selector', __('Results container DOM selector', 'ajax-search-lite'), $sd['res_live_selector']);
		?>
		<div class="descMsg">
			<?php esc_html_e('In many themes this is #main, but it can be different. This is very important to get right, or this will surely not work. The plugin will try other values as well, if this fails.', 'ajax-search-lite'); ?>
		</div>
	</div>
</fieldset>
<fieldset id="res_live_search_triggers" wd-enable-on="res_live_search:1">
	<legend><?php esc_html_e('Results page live loader triggers', 'ajax-search-lite'); ?></legend>
	<div class="item">
		<?php
		new wpdreamsYesNo(
			'res_live_trigger_type',
			__('Trigger live search when typing?', 'ajax-search-lite'),
			$sd['res_live_trigger_type']
		);
		?>
		<div class="descMsg">
			<?php esc_html_e('If enabled, on the results page (or custom Elementor posts widget page), overrides the default behavior.', 'ajax-search-lite'); ?>
		</div>
	</div>
	<div class="item">
		<?php
		new wpdreamsYesNo(
			'res_live_trigger_facet',
			__('Trigger live search when changing a facet on settings?', 'ajax-search-lite'),
			$sd['res_live_trigger_facet']
		);
		?>
		<div class="descMsg">
			<?php esc_html_e('If enabled, on the results page (or custom Elementor posts widget page), overrides the default behavior.', 'ajax-search-lite'); ?>
		</div>
	</div>
	<div class="item">
		<?php
		new wpdreamsYesNo(
			'res_live_trigger_click',
			__('Trigger live search when clicking the magnifier button?', 'ajax-search-lite'),
			$sd['res_live_trigger_click']
		);
		?>
		<div class="descMsg">
			<?php esc_html_e('If enabled, on the results page (or custom Elementor posts widget page), overrides the default behavior.', 'ajax-search-lite'); ?>
		</div>
	</div>
	<div class="item">
		<?php
		new wpdreamsYesNo(
			'res_live_trigger_return',
			__('Trigger live search when hitting the return key?', 'ajax-search-lite'),
			$sd['res_live_trigger_return']
		);
		?>
		<div class="descMsg">
			<?php esc_html_e('If enabled, on the results page (or custom Elementor posts widget page), overrides the default behavior.', 'ajax-search-lite'); ?>
		</div>
	</div>
</fieldset>