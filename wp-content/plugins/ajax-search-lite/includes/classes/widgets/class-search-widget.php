<?php
class AjaxSearchLiteWidget extends WP_Widget {
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'AjaxSearchLiteWidget',
			'description' => 'Displays an Ajax Search Lite!',
		);
		parent::__construct('AjaxSearchLiteWidget', 'Ajax Search Lite', $widget_ops);
	}
	public function form( $instance ) {
		$title = !isset($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
				Title:
				<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
						name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
						value="<?php echo esc_attr($title); ?>" />
			</label>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ): array {
		$instance          = $old_instance;
		$instance['title'] = $new_instance['title'];
		return $instance;
	}

	public function widget( $args, $instance ) {
		extract($args, EXTR_SKIP); // phpcs:ignore
		echo $before_widget; // phpcs:ignore
		$title = !isset($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		if ( $title !== '' ) {
			echo $before_title . esc_attr($title) . $after_title; // phpcs:ignore
		}
		// WIDGET CODE GOES HERE
		echo do_shortcode('[wpdreams_ajaxsearchlite]');
		echo $after_widget; // phpcs:ignore
	}
}