<?php
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

$action_msg = '';

if ( isset($_POST, $_POST['asl_performance'], $_POST['submit'], $_POST['asl_performance_nonce']) ) {
	if ( wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['asl_performance_nonce'])), 'asl_performance_nonce' ) ) {
		$values = array(
			'use_custom_ajax_handler' => sanitize_text_field(wp_unslash($_POST['use_custom_ajax_handler'] ?? 0)),
			'image_cropping'          => sanitize_text_field(wp_unslash($_POST['image_cropping'] ?? 0)),
			'load_in_footer'          => sanitize_text_field(wp_unslash($_POST['load_in_footer'] ?? 1)),
		);
		update_option( 'asl_performance', $values );
		asl_parse_options();
		$action_msg = "<div class='infoMsg'><strong>" . esc_html__('Performance settings successfuly saved!', 'ajax-search-lite') . '</strong> (' . gmdate('Y-m-d H:i:s') . ')</div>';
	} else {
		$action_msg = "<div class='errorMsg'><strong>" . esc_html__('<strong>ERROR Saving:</strong> Invalid NONCE, please try again!', 'ajax-search-lite') . '</strong> (' . gmdate('Y-m-d H:i:s') . ')</div>';
		$_POST      = array();
	}
}

$cache_options = wd_asl()->o['asl_performance'];
?>
<div id="wpdreams" class='wpdreams wrap<?php echo isset($_COOKIE['asl-accessibility']) ? ' wd-accessible' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'>
	<?php if ( wd_asl()->o['asl_performance']['use_custom_ajax_handler'] ) : ?>
		<p class='noticeMsgBox'>
			<?php echo esc_html__('AJAX SEARCH LITE NOTICE: The custom ajax handler is enabled. In case you experience issues, please ', 'ajax-search-lite'); ?>
			<a href='<?php echo esc_attr( get_admin_url() . 'admin.php?page=ajax-search-lite/backend/performance_options.php' ); ?>'><?php echo esc_html__('turn it off.', 'ajax-search-lite'); ?></a></p>
	<?php endif; ?>

	<div class="wpdreams-box" style="float:left;">
		<?php ob_start(); ?>
		<div class="item">
			<p class='infoMsg'><?php echo esc_html__('Turn it OFF if the search stops working correctly after enabling.', 'ajax-search-lite'); ?></p>
			<?php new wpdreamsYesNo( 'use_custom_ajax_handler', __('Use custom ajax handler', 'ajax-search-lite'), $cache_options['use_custom_ajax_handler'] ); ?>
			<p class="descMsg"><?php echo esc_html__('The queries will be posted to a custom ajax handler file, which does not wait for whole WordPress loading process. Usually it has great performance impact.', 'ajax-search-lite'); ?></p>
		</div>
		<div class="item">
			<?php new wpdreamsYesNo( 'image_cropping', __('Crop images for caching?', 'ajax-search-lite'), $cache_options['image_cropping']); ?>
			<p class="descMsg"><?php echo esc_html__('When turned OFF, it disables thumbnail generator, and the full sized images are used as cover. Not much difference visually, but saves a lot of CPU.', 'ajax-search-lite'); ?></p>
		</div>
		<div class="item">
			<?php $o = new wpdreamsYesNo( 'load_in_footer', __('Load JavaScript in site footer?', 'ajax-search-lite'), $cache_options['load_in_footer']); ?>
			<p class="descMsg"><?php echo esc_html__('Will load the JavaScript files in the site footer for better performance.', 'ajax-search-lite'); ?></p>
		</div>

		<?php $_r = ob_get_clean(); ?>

		<div class='wpdreams-slider'>
			<form name='asl_performance_form' method='post'>
				<?php
				// Constructed HTML, all user input escaped above
				echo $action_msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				<fieldset>
					<legend><?php echo esc_html__('Performance Options', 'ajax-search-lite'); ?></legend>
					<?php
					// Constructed HTML, all user input escaped above
					echo $_r; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
					<input type='hidden' name='asl_performance' value='1'/>
					<input type="hidden" name="asl_performance_nonce" id="asl_performance_nonce" value="<?php echo wp_create_nonce( 'asl_performance_nonce' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
					<div class="item">
						<input type='submit' class='submit' name="submit" value='Save options'/>
					</div>
				</fieldset>
			</form>
		</div>

	</div>
	<?php require ASL_PATH . 'backend/sidebar.php'; ?>
	<div class="clear"></div>
</div>