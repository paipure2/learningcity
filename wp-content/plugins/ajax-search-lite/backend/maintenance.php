<?php
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>
<style>
	#wpdreams .asl_maintenance ul {
		list-style-type: disc;
		margin-bottom: 10px;
	}
	#wpdreams .asl_maintenance ul li {
		list-style-type: disc;
		margin-left: 30px;
		margin-top: 10px;
	}
</style>
<div id="wpdreams" class='wpdreams wrap<?php echo isset($_COOKIE['asl-accessibility']) ? ' wd-accessible' : ''; ?>'>
	<div class="wpdreams-box asl_maintenance" style="float:left;">
		<div id='asl_i_error' class="errorMsg hiddend"></div>
		<textarea id="asl_i_error_cont" class="hiddend" aria-label="Error container"></textarea>

		<form name="asl_reset_form" id="asl_reset_form" action="maintenance.php" method="POST">
			<fieldset>
				<legend><?php esc_html_e('Maintencance - Reset', 'ajax-search-lite'); ?></legend>
				<p><?php esc_html_e('This option will reset all the plugin options to the defaults. Use this option if you want to keep using the plugin, but you need to reset the default options.', 'ajax-search-lite'); ?></p>
				<ul>
					<li><?php esc_html_e('All plugin options', 'ajax-search-lite'); ?> <strong><?php esc_html_e('will', 'ajax-search-lite'); ?></strong> <?php esc_html_e('reset to defaults (performance, compatibility, analytics)', 'ajax-search-lite'); ?></li>
					<li><?php esc_html_e('The search instance options', 'ajax-search-lite'); ?> <strong><?php esc_html_e('will not', 'ajax-search-lite'); ?></strong> <?php esc_html_e('be changed', 'ajax-search-lite'); ?></li>
					<li><?php esc_html_e('The database tables, contents and the files', 'ajax-search-lite'); ?> <strong><?php esc_html_e('will not', 'ajax-search-lite'); ?></strong> <?php esc_html_e('be deleted either.', 'ajax-search-lite'); ?></li>
				</ul>

				<div style="text-align: center;">
					<input type="hidden" name="asl_reset_nonce" id="asl_reset_nonce" value="<?php echo esc_attr(wp_create_nonce( 'asl_reset_nonce' )); ?>">
					<input type="button" name="asl_reset"
							id="asl_reset" class="submit wd_button_green"
							value="<?php esc_attr_e('Reset all options to defaults', 'ajax-search-lite'); ?>">
					<span class="loading-small hiddend"></span>
				</div>
			</fieldset>
		</form>
		<form name="asl_wipe_form" id="asl_wipe_form" action="maintenance.php" method="POST">
			<fieldset>
				<legend><?php esc_html_e('Maintencance - Wipe & Deactivate', 'ajax-search-lite'); ?></legend>
				<p><?php esc_html_e("This option will wipe everything related to Ajax Search Lite, as if it was never installed. Use this if you don't want to use the plugin anymore, or if you want to perform a clean installation.", 'ajax-search-lite'); ?></p>
				<ul>
					<li><?php esc_html_e('All plugin options', 'ajax-search-lite'); ?> <strong><?php esc_html_e('will be deleted', 'ajax-search-lite'); ?></strong> </li>
					<li><?php esc_html_e('The search instance options', 'ajax-search-lite'); ?> <strong><?php esc_html_e('will be deleted', 'ajax-search-lite'); ?></strong></li>
					<li><?php esc_html_e('The database tables, contents and the files', 'ajax-search-lite'); ?> <strong><?php esc_html_e('will be deleted', 'ajax-search-lite'); ?></strong></li>
					<li><?php esc_html_e('The plugin ', 'ajax-search-lite'); ?> <strong><?php esc_html_e('will deactivate', 'ajax-search-lite'); ?></strong> <?php esc_html_e('and redirect to the plugin manager screen after, where you can delete it or re-install it again.', 'ajax-search-lite'); ?></li>
				</ul>

				<div style="text-align: center;">
					<input type="hidden" name="asl_wipe_nonce" id="asl_wipe_nonce" value="<?php echo esc_attr(wp_create_nonce( 'asl_wipe_nonce' )); ?>">
					<input type="button" name="asl_wipe" id="asl_wipe" class="submit"
							value="<?php esc_attr_e('Wipe all plugin data & deactivate Ajax Search Lite', 'ajax-search-lite'); ?>">
					<span class="loading-small hiddend"></span>
				</div>
			</fieldset>
		</form>
	</div>
	<?php require ASL_PATH . 'backend/sidebar.php'; ?>
	<div class="clear"></div>
</div>
<?php

wp_enqueue_script(
	'asl-backend-maintenance',
	plugin_dir_url(__FILE__) . 'settings/assets/maintenance.js',
	array(
		'jquery',
	),
	ASL_CURR_VER_STRING,
	true
);
ASL_Helpers::addInlineScript(
	'asl-backend-maintenance',
	'ASL_MNT',
	array(
		'admin_url' => admin_url(),
	)
);

