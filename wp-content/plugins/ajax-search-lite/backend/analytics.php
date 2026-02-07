<?php
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

$action_msg = '';
if (
	isset($_POST, $_POST['asl_analytics'], $_POST['reset'], $_POST['asl_analytics_nonce'])
) {
	if ( wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['asl_analytics_nonce'])), 'asl_analytics_nonce' ) ) {
		asl_reset_option('asl_analytics');
		$action_msg = "<div class='infoMsg'><strong>" . esc_html__('Analytics settings were reset to defaults!', 'ajax-search-lite') . '</strong> (' . gmdate('Y-m-d H:i:s') . ')</div>';
	} else {
		$action_msg = "<div class='errorMsg'><strong>" . esc_html__('ERROR Resetting: Invalid NONCE, please try again!', 'ajax-search-lite') . '</strong> (' . gmdate('Y-m-d H:i:s') . ')</div>';
	}
	$_POST = array();
}

if ( isset($_POST, $_POST['analytics'], $_POST['submit'], $_POST['asl_analytics_nonce']) ) {
	if ( wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['asl_analytics_nonce'])), 'asl_analytics_nonce' ) ) {
		$values = array(
			'analytics'                => sanitize_text_field(wp_unslash($_POST['analytics'])),
			'analytics_tracking_id'    => sanitize_text_field(wp_unslash($_POST['analytics_tracking_id'] ?? '')),
			'analytics_string'         => sanitize_text_field(wp_unslash($_POST['analytics_string'] ?? '')),
			// Gtag on input focus
			'gtag_focus'               => sanitize_text_field(wp_unslash($_POST['gtag_focus'] ?? 0)),
			'gtag_focus_action'        => sanitize_text_field(wp_unslash($_POST['gtag_focus_action'] ?? '')),
			'gtag_focus_ec'            => sanitize_text_field(wp_unslash($_POST['gtag_focus_ec'] ?? '')),
			'gtag_focus_el'            => sanitize_text_field(wp_unslash($_POST['gtag_focus_el'] ?? '')),
			'gtag_focus_value'         => sanitize_text_field(wp_unslash($_POST['gtag_focus_value'] ?? '')),
			// Gtag on search start
			'gtag_search_start'        => sanitize_text_field(wp_unslash($_POST['gtag_search_start'] ?? 0)),
			'gtag_search_start_action' => sanitize_text_field(wp_unslash($_POST['gtag_search_start_action'] ?? '')),
			'gtag_search_start_ec'     => sanitize_text_field(wp_unslash($_POST['gtag_search_start_ec'] ?? '')),
			'gtag_search_start_el'     => sanitize_text_field(wp_unslash($_POST['gtag_search_start_el'] ?? '')),
			'gtag_search_start_value'  => sanitize_text_field(wp_unslash($_POST['gtag_search_start_value'] ?? '')),
			// Gtag on search end
			'gtag_search_end'          => sanitize_text_field(wp_unslash($_POST['gtag_search_end'] ?? 0)),
			'gtag_search_end_action'   => sanitize_text_field(wp_unslash($_POST['gtag_search_end_action'] ?? '')),
			'gtag_search_end_ec'       => sanitize_text_field(wp_unslash($_POST['gtag_search_end_ec'] ?? '')),
			'gtag_search_end_el'       => sanitize_text_field(wp_unslash($_POST['gtag_search_end_el'] ?? '')),
			'gtag_search_end_value'    => sanitize_text_field(wp_unslash($_POST['gtag_search_end_value'] ?? '')),
			// Gtag on magnifier
			'gtag_magnifier'           => sanitize_text_field(wp_unslash($_POST['gtag_magnifier'] ?? 0)),
			'gtag_magnifier_action'    => sanitize_text_field(wp_unslash($_POST['gtag_magnifier_action'] ?? '')),
			'gtag_magnifier_ec'        => sanitize_text_field(wp_unslash($_POST['gtag_magnifier_ec'] ?? '')),
			'gtag_magnifier_el'        => sanitize_text_field(wp_unslash($_POST['gtag_magnifier_el'] ?? '')),
			'gtag_magnifier_value'     => sanitize_text_field(wp_unslash($_POST['gtag_magnifier_value'] ?? '')),
			// Gtag on return
			'gtag_return'              => sanitize_text_field(wp_unslash($_POST['gtag_return'] ?? 0)),
			'gtag_return_action'       => sanitize_text_field(wp_unslash($_POST['gtag_return_action'] ?? '')),
			'gtag_return_ec'           => sanitize_text_field(wp_unslash($_POST['gtag_return_ec'] ?? '')),
			'gtag_return_el'           => sanitize_text_field(wp_unslash($_POST['gtag_return_el'] ?? '')),
			'gtag_return_value'        => sanitize_text_field(wp_unslash($_POST['gtag_return_value'] ?? '')),
			// Gtag on facet change
			'gtag_facet_change'        => sanitize_text_field(wp_unslash($_POST['gtag_facet_change'] ?? 0)),
			'gtag_facet_change_action' => sanitize_text_field(wp_unslash($_POST['gtag_facet_change_action'] ?? '')),
			'gtag_facet_change_ec'     => sanitize_text_field(wp_unslash($_POST['gtag_facet_change_ec'] ?? '')),
			'gtag_facet_change_el'     => sanitize_text_field(wp_unslash($_POST['gtag_facet_change_el'] ?? '')),
			'gtag_facet_change_value'  => sanitize_text_field(wp_unslash($_POST['gtag_facet_change_value'] ?? '')),
			// Gtag on result click
			'gtag_result_click'        => sanitize_text_field(wp_unslash($_POST['gtag_result_click'] ?? 0)),
			'gtag_result_click_action' => sanitize_text_field(wp_unslash($_POST['gtag_result_click_action'] ?? '')),
			'gtag_result_click_ec'     => sanitize_text_field(wp_unslash($_POST['gtag_result_click_ec'] ?? '')),
			'gtag_result_click_el'     => sanitize_text_field(wp_unslash($_POST['gtag_result_click_el'] ?? '')),
			'gtag_result_click_value'  => sanitize_text_field(wp_unslash($_POST['gtag_result_click_value'] ?? '')),
		);
		update_option('asl_analytics', $values);
		asl_parse_options();
		$action_msg = "<div class='infoMsg'><strong>" . esc_html__('Analytics settings saved!', 'ajax-search-lite') . '</strong> (' . gmdate('Y-m-d H:i:s') . ')</div>';
	} else {
		$action_msg = "<div class='errorMsg'><strong>" . esc_html__('ERROR: Invalid NONCE, please try again!', 'ajax-search-lite') . '</strong> (' . gmdate('Y-m-d H:i:s') . ')</div>';
		$_POST      = array();
	}
}

$ana_options = wd_asl()->o['asl_analytics'];
?>

<div id="wpdreams" class='wpdreams wrap<?php echo isset($_COOKIE['asl-accessibility']) ? ' wd-accessible' : ''; ?> asl-be-analytics'>
	<div class="wpdreams-box" style="float:left;">
		<?php ob_start(); ?>
		<div class="item">
			<?php
			$o = new wpdreamsCustomSelect(
				'analytics',
				'Google analytics integration method',
				array(
					'selects' => array(
						array(
							'option' => esc_attr__('Disabled', 'ajax-search-lite'),
							'value'  => '0',
						),
						array(
							'option' => esc_attr__('Event Tracking', 'ajax-search-lite'),
							'value'  => 'event',
						),
						array(
							'option' => esc_attr__('Tracking as pageview (legacy)', 'ajax-search-lite'),
							'value'  => 'pageview',
						),
					),
					'value'   => $ana_options['analytics'],
				)
			);
			?>
			<p class="descMsg">
				<?php
				/** @noinspection HtmlUnknownTarget */
				printf(
					/* translators: Analytics integration docs */
					__('To understand how this works, please read the <a href="%s">Analytics Integration Documentation</a>', 'ajax-search-lite'), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'https://documentation.ajaxsearchpro.com/analytics-integration'
				);
				?>
			</p>
		</div>
		<div class="asl_al_both hiddend">
			<div class="item">
				<?php $o = new wpdreamsText('analytics_tracking_id', __('Google analytics Tracking ID (ex.: UA-XXXXXX-X)', 'ajax-search-lite'), $ana_options['analytics_tracking_id']); ?>
				<p class='infoMsg'>
					<?php
					/** @noinspection HtmlUnknownTarget */
					printf(
						/* translators: %s: GA docs url */
						__('Please read this <a href="%s">google analytics documentation</a> to get your tracking ID.', 'ajax-search-lite'), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'https://support.google.com/analytics/answer/7372977'
					);
					?>
				</p>
			</div>
		</div>
		<div class="asl_al_pageview hiddend">
			<div class="item">
				<?php $o = new wpdreamsText('analytics_string', __('Google analytics pageview string', 'ajax-search-lite'), $ana_options['analytics_string']); ?>
				<p class='infoMsg'>
					<?php echo esc_html__('This is how the pageview will look like on the google analytics website. Use the {asl_term} variable to add the search term to the pageview.', 'ajax-search-lite'); ?>
				</p>
			</div>
			<p class='infoMsg'>
				<?php echo esc_html__('After some time you should be able to see the hits on your analytics board.', 'ajax-search-lite'); ?>
			</p>
		</div>
		<div class="asl_al_event hiddend">
			<fieldset>
				<legend><?php echo esc_html__('Search input focus event tracking', 'ajax-search-lite'); ?></legend>
				<div class="item asl_gtag_switch">
					<?php
					$o = new wpdreamsYesNo('gtag_focus', __('Enabled', 'ajax-search-lite'), $ana_options['gtag_focus']);
					?>
					<p class='descMsg'>
						<?php echo esc_html__('Triggers, whenever the user clicks on the search input field.', 'ajax-search-lite'); ?>
					</p>
				</div>
				<div class="item item-flex item-flex-nogrow item-flex-wrap item-flex-two-column asl_gtag_inputs">
					<div class='descMsg item-flex-grow item-flex-100'>
						<?php 
						printf(
								/* translators: %s: search term placeholder */
							esc_html__('Usable variables: %s', 'ajax-search-lite'),
							'{phrase}'
						);
						?>
					</div>
					<?php
					$o = new wpdreamsText('gtag_focus_action', __('Event action', 'ajax-search-lite'), $ana_options['gtag_focus_action']);
					$o = new wpdreamsText('gtag_focus_ec', __('Event category', 'ajax-search-lite'), $ana_options['gtag_focus_ec']);
					$o = new wpdreamsText('gtag_focus_el', __('Event label', 'ajax-search-lite'), $ana_options['gtag_focus_el']);
					$o = new wpdreamsText('gtag_focus_value', __('Event value', 'ajax-search-lite'), $ana_options['gtag_focus_value']);
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php echo esc_html__('Live search start event tracking', 'ajax-search-lite'); ?></legend>
				<div class="item asl_gtag_switch">
					<?php
					$o = new wpdreamsYesNo('gtag_search_start', __('Enabled', 'ajax-search-lite'), $ana_options['gtag_search_start']);
					?>
					<p class='descMsg'>
						<?php echo esc_html__('Triggers, whenever the live search starts.', 'ajax-search-lite'); ?>
					</p>
				</div>
				<div class="item item-flex item-flex-nogrow item-flex-wrap item-flex-two-column asl_gtag_inputs">
					<div class='descMsg item-flex-grow item-flex-100'>
						<?php 
						printf(
								/* translators: %s: search term placeholder */
							esc_html__('Usable variables: %s', 'ajax-search-lite'),
							'{phrase}'
						);
						?>
					</div>
					<?php
					$o = new wpdreamsText('gtag_search_start_action', __('Event action', 'ajax-search-lite'), $ana_options['gtag_search_start_action']);
					$o = new wpdreamsText('gtag_search_start_ec', __('Event category', 'ajax-search-lite'), $ana_options['gtag_search_start_ec']);
					$o = new wpdreamsText('gtag_search_start_el', __('Event label', 'ajax-search-lite'), $ana_options['gtag_search_start_el']);
					$o = new wpdreamsText('gtag_search_start_value', __('Event value', 'ajax-search-lite'), $ana_options['gtag_search_start_value']);
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php echo esc_html__('Live search end event tracking', 'ajax-search-lite'); ?></legend>
				<div class="item asl_gtag_switch">
					<?php
					$o = new wpdreamsYesNo('gtag_search_end', __('Enabled', 'ajax-search-lite'), $ana_options['gtag_search_end']);
					?>
					<p class='descMsg'>
						<?php echo esc_html__('Triggers, whenever the live search ends.', 'ajax-search-lite'); ?>
					</p>
				</div>
				<div class="item item-flex item-flex-nogrow item-flex-wrap item-flex-two-column asl_gtag_inputs">
					<div class='descMsg item-flex-grow item-flex-100'>
						<?php 
						printf(
								/* translators: %s: search term placeholder */
							esc_html__('Usable variables: %s', 'ajax-search-lite'),
							'{phrase}'
						);
						?>
					</div>
					<?php
					$o = new wpdreamsText('gtag_search_end_action', __('Event action', 'ajax-search-lite'), $ana_options['gtag_search_end_action']);
					$o = new wpdreamsText('gtag_search_end_ec', __('Event category', 'ajax-search-lite'), $ana_options['gtag_search_end_ec']);
					$o = new wpdreamsText('gtag_search_end_el', __('Event label', 'ajax-search-lite'), $ana_options['gtag_search_end_el']);
					$o = new wpdreamsText('gtag_search_end_value', __('Event value', 'ajax-search-lite'), $ana_options['gtag_search_end_value']);
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php echo esc_html__('Magnifier click event tracking', 'ajax-search-lite'); ?></legend>
				<div class="item asl_gtag_switch">
					<?php
					$o = new wpdreamsYesNo('gtag_magnifier', __('Enabled', 'ajax-search-lite'), $ana_options['gtag_magnifier']);
					?>
					<p class='descMsg'>
						<?php echo esc_html__('Triggers, whenever the user clicks the magnifier icon', 'ajax-search-lite'); ?>
					</p>
				</div>
				<div class="item item-flex item-flex-nogrow item-flex-wrap item-flex-two-column asl_gtag_inputs">
					<div class='descMsg item-flex-grow item-flex-100'>
						<?php 
						printf(
								/* translators: %s: search term placeholder */
							esc_html__('Usable variables: %s', 'ajax-search-lite'),
							'{phrase}'
						);
						?>
					</div>
					<?php
					$o = new wpdreamsText('gtag_magnifier_action', __('Event action', 'ajax-search-lite'), $ana_options['gtag_magnifier_action']);
					$o = new wpdreamsText('gtag_magnifier_ec', __('Event category', 'ajax-search-lite'), $ana_options['gtag_magnifier_ec']);
					$o = new wpdreamsText('gtag_magnifier_el', __('Event label', 'ajax-search-lite'), $ana_options['gtag_magnifier_el']);
					$o = new wpdreamsText('gtag_magnifier_value', __('Event value', 'ajax-search-lite'), $ana_options['gtag_magnifier_value']);
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php echo esc_html__('Return key event tracking', 'ajax-search-lite'); ?></legend>
				<div class="item asl_gtag_switch">
					<?php
					$o = new wpdreamsYesNo('gtag_return', __('Enabled', 'ajax-search-lite'), $ana_options['gtag_return']);
					?>
					<p class='descMsg'>
						<?php echo esc_html__('Triggers, whenever the user hits the enter button in the search input field', 'ajax-search-lite'); ?>
					</p>
				</div>
				<div class="item item-flex item-flex-nogrow item-flex-wrap item-flex-two-column asl_gtag_inputs">
					<div class='descMsg item-flex-grow item-flex-100'>
						<?php 
						printf(
								/* translators: %s: search term placeholder */
							esc_html__('Usable variables: %s', 'ajax-search-lite'),
							'{phrase}'
						);
						?>
					</div>
					<?php
					$o = new wpdreamsText('gtag_return_action', __('Event action', 'ajax-search-lite'), $ana_options['gtag_return_action']);
					$o = new wpdreamsText('gtag_return_ec', __('Event category', 'ajax-search-lite'), $ana_options['gtag_return_ec']);
					$o = new wpdreamsText('gtag_return_el', __('Event label', 'ajax-search-lite'), $ana_options['gtag_return_el']);
					$o = new wpdreamsText('gtag_return_value', __('Event value', 'ajax-search-lite'), $ana_options['gtag_return_value']);
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php echo esc_html__('Facet change event tracking', 'ajax-search-lite'); ?></legend>
				<div class="item asl_gtag_switch">
					<?php
					$o = new wpdreamsYesNo('gtag_facet_change', __('Enabled', 'ajax-search-lite'), $ana_options['gtag_facet_change']);
					?>
					<p class='descMsg'>
						<?php echo esc_html__('Triggers, whenever the user changes any option on the front-end settings', 'ajax-search-lite'); ?>
					</p>
				</div>
				<div class="item item-flex item-flex-nogrow item-flex-wrap item-flex-two-column asl_gtag_inputs">
					<div class='descMsg item-flex-grow item-flex-100'>
						<?php 
						printf(
								/* translators: %s: search term placeholder */
							esc_html__('Usable variables: %s', 'ajax-search-lite'),
							'{phrase}'
						);
						?>
					</div>
					<?php
					$o = new wpdreamsText('gtag_facet_change_action', __('Event action', 'ajax-search-lite'), $ana_options['gtag_facet_change_action']);
					$o = new wpdreamsText('gtag_facet_change_ec', __('Event category', 'ajax-search-lite'), $ana_options['gtag_facet_change_ec']);
					$o = new wpdreamsText('gtag_facet_change_el', __('Event label', 'ajax-search-lite'), $ana_options['gtag_facet_change_el']);
					$o = new wpdreamsText('gtag_facet_change_value', __('Event value', 'ajax-search-lite'), $ana_options['gtag_facet_change_value']);
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php echo esc_html__('Results click event tracking', 'ajax-search-lite'); ?></legend>
				<div class="item asl_gtag_switch">
					<?php
					$o = new wpdreamsYesNo('gtag_result_click', __('Enabled', 'ajax-search-lite'), $ana_options['gtag_result_click']);
					?>
					<p class='descMsg'>
						<?php echo esc_html__('Triggers, whenever the user changes any option on the front-end settings', 'ajax-search-lite'); ?>
					</p>
				</div>
				<div class="item item-flex item-flex-nogrow item-flex-wrap item-flex-two-column asl_gtag_inputs">
					<div class='descMsg item-flex-grow item-flex-100'>
						<?php 
						printf(
								/* translators: %s: search term placeholder */
							esc_html__('Usable variables: %s', 'ajax-search-lite'),
							'{phrase}'
						);
						?>
					</div>
					<?php
					$o = new wpdreamsText('gtag_result_click_action', __('Event action', 'ajax-search-lite'), $ana_options['gtag_result_click_action']);
					$o = new wpdreamsText('gtag_result_click_ec', __('Event category', 'ajax-search-lite'), $ana_options['gtag_result_click_ec']);
					$o = new wpdreamsText('gtag_result_click_el', __('Event label', 'ajax-search-lite'), $ana_options['gtag_result_click_el']);
					$o = new wpdreamsText('gtag_result_click_value', __('Event value', 'ajax-search-lite'), $ana_options['gtag_result_click_value']);
					?>
				</div>
			</fieldset>
		</div>
		<div class="item">
			<input name="reset"
					class="asl_submit asl_submit_transparent asl_submit_reset"
					type="submit" value="<?php echo esc_attr__('Restore defaults', 'ajax-search-lite'); ?>">
			<input type='submit' name="submit" class='submit' value='<?php echo esc_attr__('Save options', 'ajax-search-lite'); ?>'/>
		</div>
		<?php $_r = ob_get_clean(); ?>

		<div class='wpdreams-slider'>
			<form name='asl_analytics1' method='post'>
				<?php
				// Constructed HTML, all user input escaped above
				echo $action_msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				<fieldset>
					<legend><?php echo esc_html__('Analytics options', 'ajax-search-lite'); ?></legend>
					<?php
					// Constructed HTML, all user input escaped above
					print $_r; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
					<input type='hidden' name='asl_analytics' value='1' />
					<input type="hidden" name="asl_analytics_nonce" id="asl_analytics_nonce" value="<?php echo esc_attr(wp_create_nonce( 'asl_analytics_nonce' )); ?>">
				</fieldset>
			</form>
		</div>
	</div>
	<?php require ASL_PATH . 'backend/sidebar.php'; ?>
	<div class="clear"></div>
</div>
<?php
wp_enqueue_script(
	'asl-backend-analytics',
	plugin_dir_url(__FILE__) . 'settings/assets/analytics.js',
	array(
		'jquery',
		'wpdreams-tabs',
	),
	ASL_CURR_VER_STRING,
	true
);