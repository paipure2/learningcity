<?php
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

/**
 * Not a global, this is included within a class as a template part
 */
$id      = self::$instance_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$real_id = self::$instance_count;

if ( isset($style['_fo']) && !isset($style['_fo']['categoryset']) ) {
	$style['_fo']['categoryset'] = array();
}
?>
<div class="asl_w_container asl_w_container_<?php echo esc_attr($real_id); ?>" data-id="<?php echo esc_attr($real_id); ?>" data-instance="1">
	<div id='ajaxsearchlite<?php echo esc_attr(self::$instance_count); ?>'
		data-id="<?php echo esc_attr($real_id); ?>"
		data-instance="1"
		class="asl_w asl_m asl_m_<?php echo esc_attr($real_id); ?> asl_m_<?php echo esc_attr($real_id); ?>_1">
		<?php
		/******************** PROBOX INCLUDE ********************/
		require 'asl.shortcode.probox.php';
		?>
	</div>
	<div class='asl_data_container' style="display:none !important;">
		<?php
		/******************** SCRIPT INCLUDE (hidden) ********************/
		require 'asl.shortcode.script.php';

		/******************** DATA INCLUDE (hidden) */
		require 'asl.shortcode.data.php';
		?>
	</div>

	<?php

	/******************** RESULTS INCLUDE ********************/
	require 'asl.shortcode.results.php';
	?>

	<div id='__original__ajaxsearchlitesettings<?php echo esc_attr($id); ?>'
		data-id="<?php echo esc_attr($real_id); ?>"
		class="searchsettings wpdreams_asl_settings asl_w asl_s asl_s_<?php echo esc_attr($real_id); ?>">
		<?php
		/******************* SETTINGS INCLUDE *******************/
		require 'asl.shortcode.settings.php';
		?>
	</div>
</div>
