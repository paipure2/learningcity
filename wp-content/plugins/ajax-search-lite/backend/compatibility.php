<?php
/**
 * @noinspection HtmlUnknownAttribute
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

// Compatibility stuff
$action_msg = '';
if (
	isset($_POST, $_POST['asl_compatibility'], $_POST['asl_compatibility_nonce'])
) {
	if ( wp_verify_nonce(  sanitize_text_field(wp_unslash($_POST['asl_compatibility_nonce'])), 'asl_compatibility_nonce' ) ) {
		$values = array(
			// CSS and JS
			'js_source'                      => sanitize_text_field(wp_unslash($_POST['js_source'] ?? 'jqueryless-min')),
			'script_loading_method'          => sanitize_text_field(wp_unslash($_POST['script_loading_method'] ?? 'optimized')),
			'init_instances_inviewport_only' => sanitize_text_field(wp_unslash($_POST['init_instances_inviewport_only'] ?? 1)),
			'detect_ajax'                    => sanitize_text_field(wp_unslash($_POST['detect_ajax'] ?? 1)),
			'load_google_fonts'              => sanitize_text_field(wp_unslash($_POST['load_google_fonts'] ?? 1)),
			// Query options
			'query_soft_check'               => sanitize_text_field(wp_unslash($_POST['query_soft_check'] ?? 0)),
			'use_acf_getfield'               => sanitize_text_field(wp_unslash($_POST['use_acf_getfield'] ?? 0)),
			'db_force_case'                  => sanitize_text_field(wp_unslash($_POST['db_force_case'] ?? 'none')),
			'db_force_unicode'               => sanitize_text_field(wp_unslash($_POST['db_force_unicode'] ?? 0)),
			'db_force_utf8_like'             => sanitize_text_field(wp_unslash($_POST['db_force_utf8_like'] ?? 0)),
		);
		update_option('asl_compatibility', $values);
		asl_parse_options();
		$action_msg = "<div class='successMsg'>" . esc_html__('Search compatibility settings successfuly updated!', 'ajax-search-lite') . '</div>';
	} else {
		$action_msg = "<div class='errorMsg'>" . esc_html__('Something went wrong, pelase try again!', 'ajax-search-lite') . '</div>';
		$_POST      = array();
	}
}

$com_options = wd_asl()->o['asl_compatibility'];
?>
<div id="wpdreams" class='wpdreams wrap<?php echo isset($_COOKIE['asl-accessibility']) ? ' wd-accessible' : ''; ?>'>
	<div class="wpdreams-box" style="float:left;">

		<div class='wpdreams-slider'>

			<?php
			// Constructed HTML, all user input escaped above
			echo $action_msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<ul id="tabs" class='tabs'>
				<li>
					<a tabid="1" class='current multisite'><?php echo esc_attr__('CSS & JS compatibility', 'ajax-search-lite'); ?></a></li>
				<li>
					<a tabid="2" class='general'><?php echo esc_attr__('Query compatibility', 'ajax-search-lite'); ?></a>
				</li>
			</ul>

			<div id="content" class='tabscontent'>

				<!-- Compatibility form -->
				<form name='compatibility' method='post'>
					<fieldset tabid="1">
						<legend><?php echo esc_attr__('CSS and JS compatibility', 'ajax-search-lite'); ?></legend>
						<?php require ASL_PATH . 'backend/tabs/compatibility/cssjs_options.php'; ?>
					</fieldset>

					<fieldset tabid="2">
						<legend><?php echo esc_attr__('Query compatibility options', 'ajax-search-lite'); ?></legend>
						<?php require ASL_PATH . 'backend/tabs/compatibility/query_options.php'; ?>
					</fieldset>

					<div class="item">
						<input type='submit' class='submit' value='Save options'/>
					</div>
					<input type="hidden" name="asl_compatibility_nonce" id="asl_analytics_nonce"
							value="<?php echo esc_attr(wp_create_nonce( 'asl_compatibility_nonce' )); ?>">
					<input type='hidden' name='asl_compatibility' value='1'/>
				</form>

			</div>
		</div>
	</div>
	<?php require ASL_PATH . 'backend/sidebar.php'; ?>
	<div class="clear"></div>
</div>
<?php
wp_enqueue_script('wd_asl_helpers_jquery_conditionals', plugin_dir_url(__FILE__) . 'settings/assets/js/jquery.conditionals.js', array( 'jquery' ), ASL_CURR_VER_STRING, true);
wp_enqueue_script(
	'wpd-backend-compatibility',
	plugin_dir_url(__FILE__) . 'settings/assets/compatibility_settings.js',
	array(
		'jquery',
	),
	ASL_CURR_VER_STRING,
	true
);