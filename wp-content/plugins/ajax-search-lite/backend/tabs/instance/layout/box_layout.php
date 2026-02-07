<?php
/**
 * Search box layout options
 *
 * @noinspection HtmlUnknownAttribute
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

$themes = array(
	array(
		'option' =>'Simple Red',
		'value'  =>'simple-red',
	),
	array(
		'option' =>'Simple Blue',
		'value'  =>'simple-blue',
	),
	array(
		'option' =>'Simple Grey',
		'value'  =>'simple-grey',
	),
	array(
		'option' =>'Classic Blue',
		'value'  =>'classic-blue',
	),
	array(
		'option' =>'Curvy Black',
		'value'  =>'curvy-black',
	),
	array(
		'option' =>'Curvy Red',
		'value'  =>'curvy-red',
	),
	array(
		'option' =>'Curvy Blue',
		'value'  =>'curvy-blue',
	),
	array(
		'option' =>'Underline White',
		'value'  =>'underline',
	),
);
?>
<fieldset>
	<legend>
		<?php esc_html_e('Theme & Input & Colors', 'ajax-search-lite'); ?>
		<span class="asl_legend_docs">
			<a target="_blank" href="https://documentation.ajaxsearchlite.com/layout-options/theme-and-customization"><span class="fa fa-book"></span>
				<?php esc_html_e('Documentation', 'ajax-search-lite'); ?>
			</a>
		</span>
	</legend>
	<div class="item item-flex-nogrow" style="flex-wrap: wrap;">
		<div class="asl_theme"></div>
		<?php
		/** @noinspection PhpUndefinedVariableInspection */
		new wpdreamsCustomSelect(
			'theme',
			__('Theme', 'ajax-search-lite'),
			array(
				'selects' =>$themes,
				'value'   =>$sd['theme'],
			)
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsText('defaultsearchtext', __('Placeholder text', 'ajax-search-lite'), $sd['defaultsearchtext']);
		?>
	</div>
	<div class="item item-flex-nogrow item-flex-wrap wpd-isotopic-width">
		<?php
		new wpdreamsTextSmall(
			'box_width',
			__('Search box width', 'ajax-search-lite'),
			array(
				'icon'  => 'desktop',
				'value' => $sd['box_width'],
			)
		);
		new wpdreamsTextSmall(
			'box_width_tablet',
			'',
			array(
				'icon'  => 'tablet',
				'value' => $sd['box_width_tablet'],
			)
		);
		new wpdreamsTextSmall(
			'box_width_phone',
			'',
			array(
				'icon'  => 'phone',
				'value' => $sd['box_width_phone'],
			)
		);
		?>
		<div class="descMsg item-flex-grow item-flex-100">
			<?php
			esc_html_e('Use with CSS units (like 10px or 50% or auto ..) Default: 100%', 'ajax-search-lite');
			?>
			&nbsp;<a href="https://www.w3schools.com/cssref/css_units.asp" target="_blank"><?php esc_html_e('CSS units', 'ajax-search-lite'); ?></a>
		</div>
	</div>
	<div class="item">
		<?php
		new wpdreamsFour(
			'box_margin',
			__('Search box margin', 'ajax-search-lite'),
			array(
				'desc'  => __('Include the unit as well, example: 10px or 1em or 90%', 'ajax-search-lite'),
				'value' => $sd['box_margin'],
			)
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsText('box_font', __('Search plugin Font Family', 'ajax-search-lite'), $sd['box_font']);
		?>
		<p class="descMsg"><?php esc_html_e('The Font Family used within the plugin. Default: Open Sans', 'ajax-search-lite'); ?><br>
		<?php esc_html_e('Entering multiple font family names like Helvetica, Sans-serif or inherit are also supported.', 'ajax-search-lite'); ?></p>
	</div>
	<div class="item item-flex-nogrow" style="flex-wrap: wrap;">
		<?php
		new wpdreamsYesNo(
			'override_bg',
			__('Override background color?', 'ajax-search-lite'),
			$sd['override_bg']
		);
		?>
		<div wd-enable-on="override_bg:1">
		<?php
		new wpdreamsColorPicker(
			'override_bg_color',
			__('color:', 'ajax-search-lite'),
			$sd['override_bg_color']
		);
		?>
		</div>
	</div>
	<div class="item item-flex-nogrow" style="flex-wrap: wrap;">
		<?php
		new wpdreamsYesNo(
			'override_icon',
			__('Override magnifier & icon colors?', 'ajax-search-lite'),
			$sd['override_icon']
		);
		?>
		<div class="item-flex-nogrow" wd-enable-on="override_icon:1">
		<?php
		new wpdreamsColorPicker(
			'override_icon_bg_color',
			__('icon background colors', 'ajax-search-lite'),
			$sd['override_icon_bg_color']
		);
		new wpdreamsColorPicker(
			'override_icon_color',
			__('icon colors', 'ajax-search-lite'),
			$sd['override_icon_color']
		);
		?>
		</div>
	</div>
	<div class="item">
		<div style="margin: 8px 17px 16px 0;">
		<?php
		new wpdreamsYesNo(
			'override_border',
			__('Override search box border?', 'ajax-search-lite'),
			$sd['override_border']
		);
		?>
		</div>
		<div wd-enable-on="override_border:1">
		<?php
		new wpdreamsBorder(
			'override_border_style',
			__('Border style', 'ajax-search-lite'),
			$sd['override_border_style']
		);
		?>
		</div>
	</div>
</fieldset>
<fieldset>
	<legend><?php esc_html_e('Results theme', 'ajax-search-lite'); ?></legend>
	<div class="item item-flex-nogrow" style="flex-wrap: wrap;">
		<?php
		new wpdreamsYesNo(
			'results_bg_override',
			__('Override results container background color?', 'ajax-search-lite'),
			$sd['results_bg_override']
		);
		?>
		<div wd-enable-on="results_bg_override:1">
		<?php
		new wpdreamsColorPicker(
			'results_bg_override_color',
			__('color:', 'ajax-search-lite'),
			$sd['results_bg_override_color']
		);
		?>
		</div>
	</div>
	<div class="item item-flex-nogrow" style="flex-wrap: wrap;">
		<?php
		new wpdreamsYesNo(
			'results_item_bg_override',
			__('Override results background color?', 'ajax-search-lite'),
			$sd['results_item_bg_override']
		);
		?>
		<div wd-enable-on="results_item_bg_override:1">
		<?php
		new wpdreamsColorPicker(
			'results_item_bg_override_color',
			__('color:', 'ajax-search-lite'),
			$sd['results_item_bg_override_color']
		);
		?>
		</div>
	</div>
	<div class="item">
		<div style="margin: 8px 17px 16px 0;">
		<?php
		new wpdreamsYesNo(
			'results_override_border',
			__('Override results box border?', 'ajax-search-lite'),
			$sd['results_override_border']
		);
		?>
		</div>
		<div wd-enable-on="results_override_border:1">
		<?php
		new wpdreamsBorder(
			'results_override_border_style',
			__('Border style', 'ajax-search-lite'),
			$sd['results_override_border_style']
		);
		?>
		</div>
	</div>
</fieldset>
<fieldset>
	<legend><?php esc_html_e('Settings theme', 'ajax-search-lite'); ?></legend>
	<div class="item item-flex-nogrow" style="flex-wrap: wrap;">
		<?php
		new wpdreamsYesNo(
			'settings_bg_override',
			__('Override settings container background color?', 'ajax-search-lite'),
			$sd['settings_bg_override']
		);
		?>
		<div wd-enable-on="settings_bg_override:1">
		<?php
		new wpdreamsColorPicker(
			'settings_bg_override_color',
			__('color:', 'ajax-search-lite'),
			$sd['settings_bg_override_color']
		);
		?>
		</div>
	</div>
	<div class="item">
		<div style="margin: 8px 17px 16px 0;">
		<?php
		new wpdreamsYesNo(
			'settings_override_border',
			__('Override settings box border?', 'ajax-search-lite'),
			$sd['settings_override_border']
		);
		?>
		</div>
		<div wd-enable-on="settings_override_border:1">
		<?php
		new wpdreamsBorder(
			'settings_override_border_style',
			__('Border style', 'ajax-search-lite'),
			$sd['settings_override_border_style']
		);
		?>
		</div>
	</div>
</fieldset>