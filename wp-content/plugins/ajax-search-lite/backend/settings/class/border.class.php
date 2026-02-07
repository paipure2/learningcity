<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsBorder') ) {
	/**
	 * Class wpdreamsBorder
	 *
	 * Creates a CSS border defining element.
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2014, Ernest Marcinko
	 */
	class wpdreamsBorder extends wpdreamsType {
		private string $top_left;
		private string $top_right;
		private string $bottom_right;
		private string $bottom_left;
		private string $width;
		private string $style;
		private string $color;
		private $border_styles = array(
			'none',
			'hidden',
			'dotted',
			'dashed',
			'solid',
			'double',
			'groove',
			'ridge',
			'inset',
			'outset',
		);

		public function getType() {
			parent::getType();
			$this->processData();
			?>
			<div class='wpdreamsBorder'>
				<fieldset>
					<legend><?php echo esc_html($this->label); ?></legend>
					<div class="item-flex">
						<div>
							<label>Style<select class='smaller _xx_style_xx_'>
							<?php foreach ( $this->border_styles as $option ) : ?>
								<option value="<?php echo esc_attr($option); ?>"<?php echo $this->style === $option ? ' selected="selected"' : ''; ?>><?php echo esc_attr($option); ?></option>
							<?php endforeach; ?>
							</select></label>
						</div>
						<div class="wpd_br_to_disable">
							<label>Width
								<input type='text' class='twodigit _xx_width_xx_' value="<?php echo esc_attr($this->width); ?>"/>px
							</label>
							<?php new wpdreamsColorPickerDummy('', 'Color', ( $this->color ?? '#000000' )); ?>
						</div>
						<fieldset class="wpd_border_radius">
							<legend>Border Radius</legend>
							<label>Top left<input type='text' class='twodigit _xx_topleft_xx_'value="<?php echo esc_attr($this->top_left); ?>" />px</label>
							<label>Top right<input type='text' class='twodigit _xx_topright_xx_' value="<?php echo esc_attr($this->top_right); ?>" />px</label><br>
							<label>Bottom right<input type='text' class='twodigit _xx_bottomright_xx_' value="<?php echo esc_attr($this->bottom_right); ?>" />px</label>
							<label>Bottom left<input type='text' class='twodigit _xx_bottomleft_xx_' value="<?php echo esc_attr($this->bottom_left); ?>" />px</label>
						</fieldset>
					</div>
				</fieldset>
				<input isparam=1 type='hidden' value="<?php echo esc_attr($this->data); ?>" name="<?php echo esc_attr($this->name); ?>">
				<div class='triggerer'></div>
			</div>
			<?php
		}

		public function processData() {
			$this->data = str_replace("\n", '', $this->data);

			preg_match('/border-radius:(.*?)px(.*?)px(.*?)px(.*?)px;/', $this->data, $matches);
			$this->top_left     = intval($matches[1]);
			$this->top_right    = intval($matches[2]);
			$this->bottom_right = intval($matches[3]);
			$this->bottom_left  = intval($matches[4]);

			preg_match('/border:(.*?)px (.*?) (.*?);/', $this->data, $matches);
			$this->width = $matches[1];
			$this->style = $matches[2];
			$this->color = $matches[3];
		}

		final public function getData() {
			return $this->data;
		}
	}
}