<?php
/** @noinspection PhpUndefinedVariableInspection */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>
<div id='ajaxsearchliteres<?php echo esc_attr($id); ?>'
	class='<?php echo esc_attr($style['resultstype']); ?> wpdreams_asl_results asl_w asl_r asl_r_<?php echo esc_attr($real_id); ?> asl_r_<?php echo esc_attr($real_id); ?>_1'>

	<?php do_action('asl_layout_before_results', $id); ?>

	<div class="results">

		<?php do_action('asl_layout_before_first_result', $id); ?>

		<div class="resdrg">
		</div>

		<?php do_action('asl_layout_after_last_result', $id); ?>

	</div>

	<?php do_action('asl_layout_after_results', $id); ?>

	<?php if ( $style['showmoreresults'] ) : ?>
		<?php do_action('asl_layout_before_showmore', $id); ?>
		<p class='showmore'>
			<span><?php echo esc_html(asl_icl_t('Show more results text', $style['showmoreresultstext'])); ?></span>
		</p>
		<?php do_action('asl_layout_after_showmore', $id); ?>
	<?php endif; ?>

</div>
