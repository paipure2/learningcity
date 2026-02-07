<?php
/**
 * Script related stuff for the search
 *
 * @noinspection PhpUndefinedVariableInspection
 */

$ana_options   = get_option('asl_analytics');
$search_config = array(
	'homeurl'             => is_admin() ? home_url('/') : ( function_exists('PLL') ? PLL()->links->get_home_url('', true) : home_url('/') ),
	'resultstype'         => 'vertical',
	'resultsposition'     => $style['resultsposition'] === 'hover' ? 'hover' : 'block',
	'itemscount'          => max(1, intval($style['itemscount'])),
	'charcount'           => max(0, intval($style['charcount'])),
	'highlight'           => boolval($style['kw_highlight']),
	'highlightWholewords' => boolval($style['kw_highlight_whole_words']),
	'singleHighlight'     => boolval($style['single_highlight']),
	'scrollToResults'     => array(
		'enabled' => boolval(intval($style['scroll_to_results'])),
		'offset'  => 0,
	),
	'resultareaclickable' => $style['resultareaclickable'] ? 1 : 0,
	'autocomplete'        => array(
		'enabled'           => boolval(w_isset_def($style['autocomplete'], 1)),
		'lang'              => w_isset_def($style['kw_google_lang'], 'en'),
		'trigger_charcount' => 0,
	),
	'mobile'              => array(
		'menu_selector' => $style['mob_auto_focus_menu_selector'],
	),
	'trigger'             => array(
		'click'           => $style['click_action'],
		'click_location'  => $style['click_action_location'],
		'update_href'     => boolval($style['trigger_update_href']),
		'return'          => $style['return_action'],
		'return_location' => $style['return_action_location'],
		'facet'           => boolval($style['trigger_on_facet_change']),
		'type'            => boolval($style['triggerontype']),
		'redirect_url'    => apply_filters('asl_redirect_url', $style['custom_redirect_url'], $real_id),
		'delay'           => 300,
	),
	'animations'          => array(
		'pc'  => array(
			'settings' => array(
				'anim' => 'fadedrop',
				'dur'  => 300,
			),
			'results'  => array(
				'anim' => 'fadedrop',
				'dur'  => 300,
			),
			'items'    => 'voidanim',
		),
		'mob' => array(
			'settings' => array(
				'anim' => 'fadedrop',
				'dur'  => 300,
			),
			'results'  => array(
				'anim' => 'fadedrop',
				'dur'  => 300,
			),
			'items'    => 'voidanim',
		),
	),
	'autop'               => array(
		'state'  => boolval($style['auto_populate']),
		'phrase' => $style['auto_populate_phrase'],
		'count'  => $style['auto_populate_count'],
	),
	'resPage'             => array(
		'useAjax'           => is_search() && $style['res_live_search'],
		'selector'          => wp_unslash($style['res_live_selector']),
		'trigger_type'      => boolval($style['res_live_trigger_type']),
		'trigger_facet'     => boolval($style['res_live_trigger_facet']),
		'trigger_magnifier' => boolval($style['res_live_trigger_click']),
		'trigger_return'    => boolval($style['res_live_trigger_return']),
	),
	'resultsSnapTo'       => $style['results_snap_to'],
	'results'             => array(
		'width'        => $style['results_width'],
		'width_tablet' => $style['results_width_tablet'],
		'width_phone'  => $style['results_width_phone'],
	),
	'settingsimagepos'    => w_isset_def($style['theme'], 'classic-blue') === 'classic-blue' ? 'left' : 'right',
	'closeOnDocClick'     => boolval(w_isset_def($style['close_on_document_click'], 1)),
	'overridewpdefault'   => boolval($style['override_default_results']),
	'override_method'     => $style['override_method'],
);
wd_asl()->instances->add_script_data($real_id, $search_config);
?>
<div class="asl_init_data wpdreams_asl_data_ct"
	style="display:none !important;"
	id="asl_init_id_<?php echo esc_attr($id); ?>"
	data-asl-id="<?php echo esc_attr($id); ?>"
	data-asl-instance="1"
	data-settings="<?php echo esc_attr(wp_json_encode($search_config)); ?>"></div>
