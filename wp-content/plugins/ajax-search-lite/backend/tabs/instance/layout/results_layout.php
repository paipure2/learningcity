<?php
/**
 * Results layout options
 *
 * @noinspection HtmlUnknownAttribute
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<div class="item item-flex-nogrow item-conditional" style="flex-wrap: wrap;">
	<?php
	/** @noinspection PhpUndefinedVariableInspection */
	new wpdreamsCustomSelect(
		'resultsposition',
		__('Results layout position', 'ajax-search-lite'),
		array(
			'selects' =>array(
				array(
					'option' => __('Hover (float over content)', 'ajax-search-lite'),
					'value'  => 'hover',
				),
				array(
					'option' => __('Block (pushes content)', 'ajax-search-lite'),
					'value'  => 'block',
				),
			),
			'value'   =>$sd['resultsposition'],
		)
	);
	new wpdreamsCustomSelect(
		'results_snap_to',
		__('and snap the box to the ', 'ajax-search-lite'),
		array(
			'selects' =>array(
				array(
					'option' => __('left side of the search', 'ajax-search-lite'),
					'value'  => 'left',
				),
				array(
					'option' => __('right side of the search', 'ajax-search-lite'),
					'value'  => 'right',
				),
				array(
					'option' => __('the center', 'ajax-search-lite'),
					'value'  => 'center',
				),
			),
			'value'   =>$sd['results_snap_to'],
		)
	);
	?>
</div>
<div class="item item-flex-nogrow item-flex-wrap">
	<?php
	new wpdreamsTextSmall(
		'results_width',
		__('Results box width', 'ajax-search-lite'),
		array(
			'icon'  => 'desktop',
			'value' => $sd['results_width'],
		)
	);
	new wpdreamsTextSmall(
		'results_width_tablet',
		'',
		array(
			'icon'  => 'tablet',
			'value' => $sd['results_width_tablet'],
		)
	);
	new wpdreamsTextSmall(
		'results_width_phone',
		'',
		array(
			'icon'  => 'phone',
			'value' => $sd['results_width_phone'],
		)
	);
	?>
	<div class="descMsg item-flex-grow item-flex-100">
		<?php
		esc_html_e('Use with CSS units (like 10px or 50% or auto ..) Default: auto', 'ajax-search-lite');
		?>
		&nbsp;<a href="https://www.w3schools.com/cssref/css_units.asp" target="_blank"><?php esc_html_e('CSS units', 'ajax-search-lite'); ?></a>
	</div>
</div>
<div class="item item-flex-nogrow item-flex-wrap">
	<?php
	new wpdreamsCustomSelect(
		'v_res_column_count',
		__('Number of result columns', 'ajax-search-lite'),
		array(
			'selects' =>array(
				array(
					'option' => '1',
					'value'  => 1,
				),
				array(
					'option' => '2',
					'value'  => 2,
				),
				array(
					'option' => '3',
					'value'  => 3,
				),
				array(
					'option' => '4',
					'value'  => 4,
				),
				array(
					'option' => '5',
					'value'  => 5,
				),
				array(
					'option' => '6',
					'value'  => 6,
				),
				array(
					'option' => '7',
					'value'  => 7,
				),
				array(
					'option' => '8',
					'value'  => 8,
				),
			),
			'value'   =>$sd['v_res_column_count'],
		)
	);

	new wpdreamsTextSmall(
		'v_res_column_min_width',
		__('Column minimum width (px)', 'ajax-search-lite'),
		array(
			'icon'  => 'desktop',
			'value' => $sd['v_res_column_min_width'],
		)
	);
	new wpdreamsTextSmall(
		'v_res_column_min_width_tablet',
		'',
		array(
			'icon'  => 'tablet',
			'value' => $sd['v_res_column_min_width_tablet'],
		)
	);
	new wpdreamsTextSmall(
		'v_res_column_min_width_phone',
		'',
		array(
			'icon'  => 'phone',
			'value' => $sd['v_res_column_min_width_phone'],
		)
	);
	?>
	<div class="descMsg item-flex-grow item-flex-100">
		<?php
		esc_html_e('Use with CSS units (like 10px or 50% or auto ..) Default: 200px', 'ajax-search-lite');
		?>
		&nbsp;<a href="https://www.w3schools.com/cssref/css_units.asp" target="_blank"><?php esc_html_e('CSS units', 'ajax-search-lite'); ?></a>
	</div>
</div>
<div class="item">
<?php
	new wpdreamsTextSmall('v_res_max_height', __('Result box maximum height', 'ajax-search-lite'), $sd['v_res_max_height']);
?>
	<p class="descMsg"><?php esc_html_e('If this value is reached, the scrollbar will definitely trigger. none or pixel units, like 800px. Default: none', 'ajax-search-lite'); ?></p>
</div>
<div class="item">
<?php
	new wpdreamsTextSmall(
		'itemscount',
		__('Results box viewport (in item numbers)', 'ajax-search-lite'),
		$sd['itemscount'],
	);
	?>
	<p class="descMsg"><?php esc_html_e('Used to calculate the box height. Result box height = (this option) x (average item height)', 'ajax-search-lite'); ?></p>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo('showmoreresults', __("Show 'More results..' text in the bottom of the search box?", 'ajax-search-lite'), $sd['showmoreresults']);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsText('showmoreresultstext', __("' Show more results..' text", 'ajax-search-lite'), $sd['showmoreresultstext']);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsTextSmall('post_type_res_title_length', __('Result title length', 'ajax-search-lite'), $sd['post_type_res_title_length']);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo('showauthor', __('Show author in results?', 'ajax-search-lite'), $sd['showauthor']);
	?>
</div>
<div class="item item-flex-nogrow item-conditional" style="flex-wrap: wrap;">
	<?php
	new wpdreamsYesNo('showdate', __('Show date in results?', 'ajax-search-lite'), $sd['showdate']);
	?>
	<div wd-enable-on="showdate:1">
	<?php
	new wpdreamsYesNo(
		'custom_date',
		__('Use custom date format?', 'ajax-search-lite'),
		$sd['custom_date']
	);
	?>
	</div>
	<div wd-enable-on="showdate:1">
	<?php
	new wpdreamsText(
		'custom_date_format',
		__(' format', 'ajax-search-lite'),
		$sd['custom_date_format']
	);
	?>
	</div>
	<div class='descMsg' style="min-width: 100%;
		flex-wrap: wrap;
		flex-basis: auto;
		flex-grow: 1;
		box-sizing: border-box;">
		<?php esc_html_e('If turned OFF, it will use WordPress defaults. Default custom value: Y-m-d H:i:s', 'ajax-search-lite'); ?>
	</div>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo('showdescription', __('Show description in results?', 'ajax-search-lite'), $sd['showdescription']);
	?>
</div>
<div class="item item-flex-nogrow item-flex-wrap">
	<?php
	new wpdreamsYesNo('description_context', __('Display the description context?', 'ajax-search-lite'), $sd['description_context']);
	new wpdreamsTextSmall('description_context_depth', __(' ..depth', 'ajax-search-lite'), $sd['description_context_depth']);
	?>
	<div>characters.</div>
	<div class='descMsg' style="min-width: 100%;
		flex-wrap: wrap;
		flex-basis: auto;
		flex-grow: 1;
		box-sizing: border-box;">
		<?php esc_html_e('Will display the description from around the search phrase, not from the beginning.', 'ajax-search-lite'); ?>
	</div>
</div>
<div class="item">
	<?php
	new wpdreamsTextSmall('descriptionlength', __('Description length', 'ajax-search-lite'), $sd['descriptionlength']);
	?>
</div>