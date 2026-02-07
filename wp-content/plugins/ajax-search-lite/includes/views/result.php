<?php
/** @noinspection PhpUndefinedVariableInspection */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

/**
 * This is the default template for one vertical result
 *
 * You should rather make a copy of this in this folder and use that,
 * instead of modifying this one.
 * It's also a good idea to use the actions to insert content instead of modifications.
 *
 * WARNING: Modifying anything in this file might result in search malfunctioning,
 * so be careful and use your test environment.
 *
 * You can use any WordPress function here.
 * Variables to mention:
 *      Object() $r - holding the result details
 *      Array[]  $s_options - holding the search options
 *
 *
 * You can leave empty lines for better visibility, they are cleared before output.
 *
 * MORE INFO: https://wp-dreams.com/knowledge-base/result-templating/
 *
 * @since: 4.0
 */
?>
<div class='item<?php echo esc_attr(apply_filters('asl_result_css_class', $asl_res_css_class, $r->id, $r)); ?>'>

	<?php do_action('asl_res_vertical_begin_item'); ?>

	<div class='asl_content'>


		<?php if ( !empty($r->image) ) : ?>

			<?php do_action('asl_res_vertical_before_image'); ?>

			<img class='asl_image' alt="Result image" loading='lazy' src='<?php echo esc_attr($r->image); ?>'>

			<?php do_action('asl_res_vertical_after_image'); ?>

		<?php endif; ?>



		<h3><a class="asl_res_url" href='<?php echo esc_attr($r->link); ?>'<?php echo ( $s_options['results_click_blank'] ) ?" target='_blank'" :''; ?>>
				<?php echo wp_kses_post($r->title); ?>
				<?php if ( $s_options['resultareaclickable'] ) : ?>
					<span class='overlap'></span>
				<?php endif; ?>
			</a></h3>


		<?php if ( !empty($r->date) || !empty($r->author) ) : ?>

			<div class='etc'>

				<?php if ( $s_options['showauthor'] && !empty($r->author) ) : ?>
					<span class='asl_author'><?php echo esc_html($r->author); ?></span>
				<?php endif; ?>

				<?php if ( $s_options['showdate'] && !empty($r->date) ) : ?>
					<span class='asl_date'><?php echo esc_html($r->date); ?></span>
				<?php endif; ?>

			</div>

		<?php endif; ?>

		<?php if ( $s_options['showdescription'] ) : ?>
			<div class="asl_desc">
			<?php echo asl_kses_content($r->content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>

	</div>

	<?php do_action('asl_res_vertical_after_content'); ?>

	<div class='clear'></div>

	<?php do_action('asl_res_vertical_end_item'); ?>

</div>