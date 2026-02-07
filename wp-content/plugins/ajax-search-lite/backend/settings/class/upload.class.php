<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}
if ( !class_exists('wpdreamsUpload') ) {
	/**
	 * Class wpdreamsUpload
	 *
	 * @package  WPDreams/OptionsFramework/Classes
	 * @category Class
	 * @author Ernest Marcinko <ernest.marcinko@wp-dreams.com>
	 * @link http://wp-dreams.com, http://codecanyon.net/user/anago/portfolio
	 * @copyright Copyright (c) 2014, Ernest Marcinko
	 */
	class wpdreamsUpload extends wpdreamsType {
		public function getType() {
			parent::getType();
			$this->processData();

			echo "
      <div class='wpdreamsUpload' id='wpdreamsUpload" . esc_attr(self::$_instancenumber) . "'>";
			?>
			<label for='wpdreamsUpload_input<?php echo esc_attr(self::$_instancenumber); ?>'>
				<?php echo esc_html($this->label); ?>
			</label>
			<input id="wpdreamsUpload_input<?php echo esc_attr(self::$_instancenumber); ?>" type="text"
					size="36" name="<?php echo esc_attr($this->name); ?>" value="<?php echo stripslashes(esc_attr($this->data)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"/>
			<input id="wpdreamsUpload_button<?php echo esc_attr(self::$_instancenumber); ?>" class="button" type="button"
					value="Upload"/>

			<script type='text/javascript'>
			jQuery(function ($) {
				let custom_uploader;

				$('#wpdreamsUpload_button<?php echo esc_attr(self::$_instancenumber); ?>').on('click', function (e) {

					e.preventDefault();

					//If the uploader object has already been created, reopen the dialog
					if (custom_uploader) {
						custom_uploader.open();
						return;
					}

					//Extend the wp.media object
					custom_uploader = wp.media.frames.file_frame = wp.media({
						title: 'Choose Image',
						button: {
							text: 'Choose Image'
						},
						multiple: false
					});

					//When a file is selected, grab the URL and set it as the text field's value
					custom_uploader.on('select', function () {
						let attachment = custom_uploader.state().get('selection').first().toJSON();
						$('#wpdreamsUpload_input<?php echo esc_attr(self::$_instancenumber); ?>').val(attachment.url);
					});

					//Open the uploader dialog
					custom_uploader.open();

				});
			});
			</script>
			<?php
			echo '
      </div>';
		}


		public function processData() {
		}

		final public function getData() {
			return $this->data;
		}
	}
}