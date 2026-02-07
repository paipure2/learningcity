<?php
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

/**
 * Settings output
 *
 * @noinspection HtmlUnknownAttribute
 */

/**
 * $_checked array items can be safely printed as attributes without escaping
 */
if ( isset($style['_fo']) ) {
	$_checked = array(
		'set_exactonly' => in_array('exact', $style['_fo']['asl_gen'], true) ? ' checked="checked"' : '',
		'set_intitle'   => in_array('title', $style['_fo']['asl_gen'], true) ? ' checked="checked"' : '',
		'set_incontent' => in_array('content', $style['_fo']['asl_gen'], true) ? ' checked="checked"' : '',
		'set_inexcerpt' => in_array('excerpt', $style['_fo']['asl_gen'], true) ? ' checked="checked"' : '',
	);
} else {
	/** @noinspection PhpUndefinedVariableInspection */
	$_checked = array(
		'set_exactonly' => $style['exactonly'] ? ' checked="checked"' : '',
		'set_intitle'   => $style['searchintitle'] ? ' checked="checked"' : '',
		'set_incontent' => $style['searchincontent'] ? ' checked="checked"' : '',
		'set_inexcerpt' => $style['searchinexcerpt'] ? ' checked="checked"' : '',
	);
}

if ( function_exists('qtranxf_getLanguage') ) {
	$qtr_lg = qtranxf_getLanguage();
} elseif ( function_exists('qtrans_getLanguage') ) {
	$qtr_lg = qtrans_getLanguage();
} else {
	$qtr_lg = 0;
}
?>
<form name='options'
		aria-label="<?php echo esc_attr(asl_icl_t('Search Settings form aria-Label', $style['aria_settings_form_label'])); ?>"
		autocomplete = 'off'>

	<?php
	/** @noinspection PhpUndefinedVariableInspection */
	do_action('asl_layout_in_form', $id);
	?>

	<?php do_action('asl_layout_settings_before_first_item', $id); ?>

	<input type="hidden" name="filters_changed" style="display:none;" value="0">
	<input type="hidden" name="filters_initial" style="display:none;" value="1">

	<div class="asl_option_inner hiddend">
		<input type='hidden' name='qtranslate_lang' id='qtranslate_lang<?php esc_attr($id); ?>'
				value='<?php echo esc_attr($qtr_lg); ?>'/>
	</div>

	<?php
	if ( defined('ICL_LANGUAGE_CODE')
		&& ICL_LANGUAGE_CODE !== ''
		&& defined('ICL_SITEPRESS_VERSION')
	) :
		?>
		<div class="asl_option_inner hiddend">
			<input type='hidden' name='wpml_lang'
					value='<?php echo esc_attr(ICL_LANGUAGE_CODE); ?>'/>
		</div>
	<?php endif; ?>

	<?php if ( function_exists('pll_current_language') ) : ?>
		<div class="asl_option_inner hiddend">
			<input type='hidden' name='polylang_lang'
					value='<?php echo esc_attr(pll_current_language()); ?>'/>
		</div>
	<?php endif; ?>

	<fieldset class="asl_sett_scroll">
		<legend style="display: none;">Generic selectors</legend>
		<div class="asl_option<?php echo( ( !$style['showexactmatches'] ) ? ' hiddend' : '' ); ?>" tabindex="0">
			<div class="asl_option_inner">
				<input type="checkbox" value="exact"
						aria-label="<?php echo esc_attr(asl_icl_t('Exact matches filter', $style['exactmatchestext'])); ?>"
						name="asl_gen[]" <?php echo $_checked['set_exactonly']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>/>
				<div class="asl_option_checkbox"></div>
			</div>
			<div class="asl_option_label">
				<?php echo esc_html(asl_icl_t('Exact matches filter', $style['exactmatchestext'])); ?>
			</div>
		</div>
		<div class="asl_option<?php echo( ( !$style['showsearchintitle'] ) ? ' hiddend' : '' ); ?>" tabindex="0">
			<div class="asl_option_inner">
				<input type="checkbox" value="title"
						aria-label="<?php echo esc_attr(asl_icl_t('Search in title filter', $style['searchintitletext'], true)); ?>"
						name="asl_gen[]" <?php echo $_checked['set_intitle']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>/>
				<div class="asl_option_checkbox"></div>
			</div>
			<div class="asl_option_label">
				<?php echo esc_html(asl_icl_t('Search in title filter', $style['searchintitletext'])); ?>
			</div>
		</div>
		<div class="asl_option<?php echo( ( !$style['showsearchincontent'] ) ? ' hiddend' : '' ); ?>" tabindex="0">
			<div class="asl_option_inner">
				<input type="checkbox" value="content"
						aria-label="<?php echo esc_attr(asl_icl_t('Search in content filter', $style['searchincontenttext'], true)); ?>"
						name="asl_gen[]" <?php echo $_checked['set_incontent']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>/>
				<div class="asl_option_checkbox"></div>
			</div>
			<div class="asl_option_label">
				<?php echo esc_html(asl_icl_t('Search in content filter', $style['searchincontenttext'])); ?>
			</div>
		</div>
		<div class="asl_option_inner hiddend">
			<input type="checkbox" value="excerpt"
					aria-label="Search in excerpt"
					name="asl_gen[]" <?php echo $_checked['set_inexcerpt']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>/>
			<div class="asl_option_checkbox"></div>
		</div>
	</fieldset>
	<fieldset class="asl_sett_scroll">
		<legend style="display: none;">Post Type Selectors</legend>
		<?php

		$i = 1;
		if ( !isset($style['customtypes']) || !is_array($style['customtypes']) ) {
			$style['customtypes'] = array();
		}
		if ( !isset($style['selected-showcustomtypes']) || !is_array($style['selected-showcustomtypes']) ) {
			$style['selected-showcustomtypes'] = array();
		}
		$shown_types = array();

		foreach ( $style['selected-showcustomtypes'] as $k => $v ) {
			$selected      = in_array($v[0], $style['customtypes'], true);
			$hidden        = '';
			$shown_types[] = $v[0];
			?>
			<div class="asl_option" tabindex="0">
				<div class="asl_option_inner">
					<input type="checkbox" value="<?php echo esc_attr($v[0]); ?>"
							aria-label="<?php echo esc_attr(asl_icl_t('Search filter for post type: ' . $v[1], $v[1], true)); ?>"
							name="customset[]" <?php echo( ( $selected ) ? 'checked="checked"' : '' ); ?>/>
					<div class="asl_option_checkbox"></div>
				</div>
				<div class="asl_option_label">
					<?php echo esc_html(asl_icl_t('Search filter for post type: ' . $v[1], $v[1])); ?>
				</div>
			</div>
			<?php
			++$i;
		}

		$remaining_types = array_unique( array_diff($style['customtypes'], $shown_types) );
		foreach ( $remaining_types as $k => $v ) {
			?>
			<div class="asl_option_inner hiddend">
				<input type="checkbox" value="<?php echo esc_attr($v); ?>"
						aria-label="Hidden option, ignore please"
						name="customset[]" checked="checked"/>
			</div>
			<?php
			++$i;
		}
		?>
	</fieldset>
	<?php
	/* Category and term filters */
	if ( $style['showsearchincategories'] ) {
		?>

		<fieldset>
			<?php if ( $style['exsearchincategoriestext'] !== '' ) : ?>
				<legend><?php echo esc_html(asl_icl_t('Categories filter box text', $style['exsearchincategoriestext'])); ?></legend>
			<?php endif; ?>
			<div class='categoryfilter asl_sett_scroll'>
				<?php

				/* Categories */
				if ( !isset($style['selected-exsearchincategories']) || !is_array($style['selected-exsearchincategories']) ) {
					$style['selected-exsearchincategories'] = array();
				}
				if ( !isset($style['selected-excludecategories']) || !is_array($style['selected-excludecategories']) ) {
					$style['selected-excludecategories'] = array();
				}
				$_all_cat    = get_terms(
					array(
						'taxonomy' => 'category',
						'fields'   =>'ids',
					)
				);
				$_needed_cat = array_diff($_all_cat, $style['selected-exsearchincategories']);
				foreach ( $_needed_cat as $k => $v ) {
					if ( isset($style['_fo']) ) {
						// we need non strict comparison
						$selected = in_array( $v, $style['_fo']['categoryset'] ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					} else {
						// we need non strict comparison
						$selected = ! in_array( $v, $style['selected-excludecategories'] ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					}
					$category = get_category($v);
					$val      = $category->name;
					$hidden   = '';
					if ( !$style['showuncategorised'] && $v === 1 ) {
						$hidden = ' hiddend';
					}
					?>
					<div class="asl_option<?php echo esc_attr($hidden); ?>" tabindex="0">
						<div class="asl_option_inner">
							<input type="checkbox" value="<?php echo esc_attr($v); ?>"
									aria-label="<?php echo esc_attr(asl_icl_t('Search filter for category: ' . $val, $val, true)); ?>"
									name="categoryset[]" <?php echo( ( $selected ) ? 'checked="checked"' : '' ); ?>/>
							<div class="asl_option_checkbox"></div>
						</div>
						<div class="asl_option_label">
							<?php echo esc_attr(asl_icl_t('Search filter for category: ' . $val, $val)); ?>
						</div>
					</div>
					<?php
				}
				?>

			</div>
		</fieldset>
		<?php
	}
	?>
</form>
