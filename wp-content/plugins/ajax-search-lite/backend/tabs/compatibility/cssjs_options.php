<?php
/**
 * Result exclusions options
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
	new wpdreamsCustomSelect(
		'js_source',
		esc_attr__('Javascript source', 'ajax-search-lite'),
		array(
			'selects' => wd_asl()->o['asl_compatibility_def']['js_source_def'],
			'value'   => $com_options['js_source'],
		)
	);
	?>
</div>
<div class="item" wd-enable-on="js_source:jqueryless-nomin,jqueryless-min">
	<?php
	new wpdreamsCustomSelect(
		'script_loading_method',
		esc_attr__('Script loading method', 'ajax-search-lite'),
		array(
			'selects' =>array(
				array(
					'option' =>esc_attr__('Classic (default)', 'ajax-search-lite'),
					'value'  =>'classic',
				),
				array(
					'option' =>esc_attr__('Optimized (recommended)', 'ajax-search-lite'),
					'value'  =>'optimized',
				),
				array(
					'option' =>esc_attr__('Optimized asynchronous', 'ajax-search-lite'),
					'value'  =>'optimized_async',
				),
			),
			'value'   =>$com_options['script_loading_method'],
		)
	);
	?>
	<div class="descMsg">
		<ul style="float:right;text-align:left;width:70%;">
			<li><?php echo esc_attr__('Classic - All scripts are loaded as blocking at the same time', 'ajax-search-lite'); ?></li>
			<li><?php echo esc_attr__('Optimized - Scripts are loaded separately, but only the required ones', 'ajax-search-lite'); ?></li>
			<li><?php echo esc_attr__('Optimized asnynchronous - Same as the Optimized, but the scripts load in the background', 'ajax-search-lite'); ?></li>
		</ul>
		<div class="clear"></div>
	</div>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'init_instances_inviewport_only',
		__('Initialize search instances only when they get visible on the viewport?', 'ajax-search-lite'),
		$com_options['init_instances_inviewport_only']
	);
	?>
	<p class='descMsg'>
		<?php echo esc_attr__('Lazy loader for the search initializer script. It can reduce the initial javascript thread work and increase the google lighthouse score.', 'ajax-search-lite'); ?>
	</p>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'detect_ajax',
		__('Try to re-initialize if the page was loaded via ajax?', 'ajax-search-lite'),
		$com_options['detect_ajax']
	);
	?>
	<p class='descMsg'>
		<?php echo esc_attr__('Will try to re-initialize the plugin in case an AJAX page loader is used, like Polylang language switcher etc..', 'ajax-search-lite'); ?>
	</p>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'load_google_fonts',
		esc_attr__('Load the Google Fonts used in the search options?', 'ajax-search-lite'),
		$com_options['load_google_fonts']
	);
	?>
	<p class='descMsg'>
		<?php echo esc_attr__('When turned OFF, the google fonts will not be loaded via this plugin at all.', 'ajax-search-lite'); ?><br>
		<?php echo esc_attr__('Useful if you already have them loaded, to avoid mutliple loading times.', 'ajax-search-lite'); ?>
	</p>
</div>
