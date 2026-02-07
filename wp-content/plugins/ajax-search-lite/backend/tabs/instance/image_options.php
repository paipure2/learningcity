<?php
/**
 * Results image options panel
 *
 * @noinspection HtmlUnknownAttribute
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<div class="item">
	<?php
	/** @noinspection PhpUndefinedVariableInspection */
	new wpdreamsYesNo(
		'show_images',
		__('Show images in results?', 'ajax-search-lite'),
		$sd['show_images']
	);
	?>
</div>
<div class="item item-flex-nogrow item-flex-wrap">
	<?php
	new wpdreamsTextSmall(
		'image_width',
		__('Image width (px)', 'ajax-search-lite'),
		$sd['image_width']
	);

	new wpdreamsTextSmall(
		'image_height',
		__('height (px)', 'ajax-search-lite'),
		$sd['image_height']
	);
	new wpdreamsCustomSelect(
		'image_display_mode',
		__('display mode', 'ajax-search-lite'),
		array(
			'selects' =>array(
				array(
					'option' => 'Cover the space',
					'value'  => 'cover',
				),
				array(
					'option' => 'Contain the image',
					'value'  => 'contain',
				),
			),
			'value'   =>$sd['image_display_mode'],
		)
	);
	?>
</div>
<div class="item">
	<?php
	new wpdreamsYesNo(
		'image_apply_content_filter',
		__('Execute shortcodes when looking for images in content?', 'ajax-search-lite'),
		$sd['image_apply_content_filter']
	);
	?>
	<p class="descMsg">
		<?php esc_html_e('Will execute shortcodes and apply the content filter before looking for images in the post content.', 'ajax-search-lite'); ?><br>
		<?php esc_html_e('If you have missing images in results, try turning ON this option. Can cause lower performance!', 'ajax-search-lite'); ?>
	</p>
</div>
<fieldset>
	<legend>Image source settings</legend>
	<div class="item">
		<?php
		new wpdreamsCustomSelect(
			'image_source1',
			__('Primary image source', 'ajax-search-lite'),
			array(
				'selects' =>$sd['image_sources'],
				'value'   =>$sd['image_source1'],
			)
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsCustomSelect(
			'image_source2',
			__('Alternative image source 1', 'ajax-search-lite'),
			array(
				'selects' =>$sd['image_sources'],
				'value'   =>$sd['image_source2'],
			)
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsCustomSelect(
			'image_source3',
			__('Alternative image source 2', 'ajax-search-lite'),
			array(
				'selects' =>$sd['image_sources'],
				'value'   =>$sd['image_source3'],
			)
		);

		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsCustomSelect(
			'image_source4',
			__('Alternative image source 3', 'ajax-search-lite'),
			array(
				'selects' =>$sd['image_sources'],
				'value'   =>$sd['image_source4'],
			)
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsCustomSelect(
			'image_source5',
			__('Alternative image source 4', 'ajax-search-lite'),
			array(
				'selects' =>$sd['image_sources'],
				'value'   =>$sd['image_source5'],
			)
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsUpload(
			'image_default',
			__('Default image url', 'ajax-search-lite'),
			$sd['image_default']
		);
		?>
	</div>
	<div class="item">
		<?php
		$_feat_image_sizes = get_intermediate_image_sizes();
		$feat_image_sizes  = array(
			array(
				'option' => 'Original size',
				'value'  => 'original',
			),
		);
		foreach ( $_feat_image_sizes as $k => $v ) {
			$feat_image_sizes[] = array(
				'option' => $v,
				'value'  => $v,
			);
		}
		new wpdreamsCustomSelect(
			'image_source_featured',
			__('Featured image size source', 'ajax-search-lite'),
			array(
				'selects' =>$feat_image_sizes,
				'value'   =>$sd['image_source_featured'],
			)
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsText(
			'image_custom_field',
			__('Custom field containing the image', 'ajax-search-lite'),
			$sd['image_custom_field']
		);
		?>
	</div>
	<div class="item">
		<?php
		new wpdreamsTextSmall(
			'image_parser_image_number',
			'Image number the parser should get from the fields',
			$sd['image_parser_image_number']
		);
		?>
	</div>
	<div class="item">
		<?php
		new wd_TextareaExpandable(
			'image_parser_exclude_filenames',
			'Exclude images by file names (comma separated)',
			$sd['image_parser_exclude_filenames']
		);
		?>
		<div class="descMsg"><?php esc_html_e('If any part of the image filename or path contains any of the above strings, it is excluded.', 'ajax-search-lite'); ?></div>
	</div>
</fieldset>
<div class="item">
	<input type="hidden" name='asl_submit' value=1 />
	<input name="submit_asl" type="submit" value="<?php esc_html_e('Save options!', 'ajax-search-lite'); ?>" />
</div>
