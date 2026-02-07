<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}

class WD_ASL_Styles {
	public function get(): array {
		return array(
			'src'    => $this->getStylesheets(),
			'inline' => $this->getInlineStyles(),
		);
	}

	private function getInlineStyles(): string {
		$inst        = wd_asl()->instances->get(0);
		$asl_options = $inst['data'];

		$inline_css = "
					div[id*='ajaxsearchlitesettings'].searchsettings .asl_option_inner label {
						font-size: 0px !important;
						color: rgba(0, 0, 0, 0);
					}
					div[id*='ajaxsearchlitesettings'].searchsettings .asl_option_inner label:after {
						font-size: 11px !important;
						position: absolute;
						top: 0;
						left: 0;
						z-index: 1;
					}
					.asl_w_container {
						width: " . esc_html($asl_options['box_width']) . ';
						margin: ' . esc_html(wpdreams_four_to_string($asl_options['box_margin'])) . ";
						min-width: 200px;
					}
					div[id*='ajaxsearchlite'].asl_m {
						width: 100%;
					}
					div[id*='ajaxsearchliteres'].wpdreams_asl_results div.resdrg span.highlighted {
						font-weight: bold;
						color: " . esc_html($asl_options['highlight_color']) . ';
						background-color: ' . esc_html($asl_options['highlight_bg_color']) . ";
					}
					div[id*='ajaxsearchliteres'].wpdreams_asl_results .results img.asl_image {
						width: " . esc_html($asl_options['image_width']) . 'px;
						height: ' . esc_html($asl_options['image_height']) . 'px;
						object-fit: ' . esc_html($asl_options['image_display_mode']) . ";
					}
					div[id*='ajaxsearchlite'].asl_r .results {
						max-height: " . esc_html($asl_options['v_res_max_height']) . ";
					}
					div[id*='ajaxsearchlite'].asl_r {
						position: " . ( $asl_options['resultsposition'] === 'block' ? 'static' : 'absolute' ) . ';
					}
				';

		if ( trim($asl_options['box_font']) !== '' && $asl_options['box_font'] !== 'Open Sans' ) {
			$ffamily     = wpd_font('font-family:' . $asl_options['box_font']) . ' !important;';
			$inline_css .= esc_html(
				'
							.asl_w, .asl_w * {' . $ffamily . '}
							.asl_m input[type=search]::placeholder{' . $ffamily . '}
							.asl_m input[type=search]::-webkit-input-placeholder{' . $ffamily . '}
							.asl_m input[type=search]::-moz-placeholder{' . $ffamily . '}
							.asl_m input[type=search]:-ms-input-placeholder{' . $ffamily . '}
						'
			);
		}
		if ( $asl_options['override_bg'] ) {
			$inline_css .= '
						.asl_m, .asl_m .probox {
							background-color: ' . esc_html($asl_options['override_bg_color']) . ' !important;
							background-image: none !important;
							-webkit-background-image: none !important;
							-ms-background-image: none !important;
						}
					';
		}
		if ( $asl_options['override_icon'] ) {
			$inline_css .= '
						.asl_m .probox svg {
							fill: ' . esc_html($asl_options['override_icon_color']) . ' !important;
						}
						.asl_m .probox .innericon {
							background-color: ' . esc_html($asl_options['override_icon_bg_color']) . ' !important;
							background-image: none !important;
							-webkit-background-image: none !important;
							-ms-background-image: none !important;
						}
					';
		}
		if ( $asl_options['override_border'] ) {
			$inline_css .= '
						div.asl_m.asl_w {
							' . esc_html(str_replace(';', ' !important;', $asl_options['override_border_style'])) . '
							box-shadow: none !important;
						}
						div.asl_m.asl_w .probox {border: none !important;}
					';
		}

		if ( $asl_options['results_width'] !== 'auto' ) {
			$inline_css .= '
						.asl_r.asl_w {
							width: ' . esc_attr($asl_options['results_width']) . ';
						}
					';
		}

		if ( $asl_options['results_bg_override'] ) {
			$inline_css .= '
						.asl_r.asl_w {
							background-color: ' . esc_html($asl_options['results_bg_override_color']) . ' !important;
							background-image: none !important;
							-webkit-background-image: none !important;
							-ms-background-image: none !important;
						}
					';
		}
		if ( $asl_options['results_item_bg_override'] ) {
			$inline_css .= '
						.asl_r.asl_w .item {
							background-color: ' . esc_html($asl_options['results_item_bg_override_color']) . ' !important;
							background-image: none !important;
							-webkit-background-image: none !important;
							-ms-background-image: none !important;
						}
					';
		}
		if ( $asl_options['results_override_border'] ) {
			$inline_css .= '
						div.asl_r.asl_w {
							' . esc_html(str_replace(';', ' !important;', $asl_options['results_override_border_style'])) . '
							box-shadow: none !important;
						}
					';
		}

		if ( $asl_options['settings_bg_override'] ) {
			$inline_css .= '
						.asl_s.asl_w {
							background-color: ' . esc_html($asl_options['settings_bg_override_color']) . ' !important;
							background-image: none !important;
							-webkit-background-image: none !important;
							-ms-background-image: none !important;
						}
					';
		}
		if ( $asl_options['settings_override_border'] ) {
			$inline_css .= '
						div.asl_s.asl_w {
							' . esc_html(str_replace(';', ' !important;', $asl_options['settings_override_border_style'])) . '
							box-shadow: none !important;
						}
					';
		}

		if ( intval($asl_options['v_res_column_count']) > 1 ) {
			// Columns specific
			$inline_css .= '
						div.asl_r.asl_w.vertical .resdrg {
							display: flex;
							flex-wrap: wrap;
						}
						div.asl_r.asl_w.vertical .results .item {
							min-width: ' . esc_html($asl_options['v_res_column_min_width']) . ';
							width: ' . floor( 100 / intval($asl_options['v_res_column_count']) - 1 ) . '%;
							flex-grow: 1;
							box-sizing: border-box;
							border-radius: 0;
						}
						@media only screen and (min-width: 641px) and (max-width: 1024px) {
							div.asl_r.asl_w.vertical .results .item {
								min-width: ' . esc_html($asl_options['v_res_column_min_width_tablet']) . ';
							}
						}
						@media only screen and (max-width: 640px) {
							div.asl_r.asl_w.vertical .results .item {
								min-width: ' . esc_html($asl_options['v_res_column_min_width_phone']) . ';
							}
						}
						';
		} else {
			// Spacer, if there are no columns
			$inline_css .= "
						div.asl_r.asl_w.vertical .results .item::after {
							display: block;
							position: absolute;
							bottom: 0;
							content: '';
							height: 1px;
							width: 100%;
							background: #D8D8D8;
						}
						div.asl_r.asl_w.vertical .results .item.asl_last_item::after {
							display: none;
						}
					";
		}

		if ( !empty($asl_options['box_width_tablet']) && $asl_options['box_width'] !== $asl_options['box_width_tablet'] ) {
			$inline_css .= '
						@media only screen and (min-width: 641px) and (max-width: 1024px) {
							.asl_w_container {
								width: ' . esc_attr($asl_options['box_width_tablet']) . ' !important;
							}
						}
					';
		}

		if ( !empty($asl_options['results_width_tablet']) && $asl_options['results_width'] !== $asl_options['results_width_tablet'] ) {
			$inline_css .= '
						@media only screen and (min-width: 641px) and (max-width: 1024px) {
							.asl_r.asl_w {
								width: ' . esc_attr($asl_options['results_width_tablet']) . ';
							}
						}
					';
		}

		if ( !empty($asl_options['box_width_phone']) && $asl_options['box_width'] !== $asl_options['box_width_phone'] ) {
			$inline_css .= '
						@media only screen and (max-width: 640px) {
							.asl_w_container {
								width: ' . esc_html($asl_options['box_width_phone']) . ' !important;
							}
						}
					';
		}

		if ( !empty($asl_options['results_width_phone']) && $asl_options['results_width'] !== $asl_options['results_width_phone'] ) {
			$inline_css .= '
						@media only screen and (max-width: 640px) {
							.asl_r.asl_w {
								width: ' . esc_attr($asl_options['results_width_phone']) . ';
							}
						}
					';
		}

		if ( $asl_options['single_highlight'] ) {
			$inline_css .= 'body span.asl_single_highlighted {
						display: inline !important;
						color: ' . esc_attr($asl_options['single_highlightcolor']) . ' !important;
						background-color: ' . esc_attr($asl_options['single_highlightbgcolor']) . ' !important;
					}';
		}

		if ( $asl_options['custom_css_code'] !== '' ) {
			$inline_css .= ' ' . $asl_options['custom_css_code'];
		}

		return $inline_css;
	}

	private function getStylesheets(): array {
		$inst        = wd_asl()->instances->get(0);
		$asl_options = $inst['data'];

		return array(
			'basic'    => ASL_URL . 'css/style.basic.css',
			'instance' => ASL_URL . 'css/style-' . $asl_options['theme'] . '.css',
		);
	}
}
