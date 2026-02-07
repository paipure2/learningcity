<?php

namespace WPDRMS\ASL\BlockEditor;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

/**
 * Full Site Editor and Gutenberg blocks controller
 */
class ASLBlock implements BlockInterface {
	/**
	 * Server side registration of the blocks
	 *
	 * @hook init
	 * @return void
	 */
	public function register(): void {
		if ( !function_exists('register_block_type') ) {
			return;
		}

		$metadata = require_once ASL_PATH . '/build/js/block-editor.asset.php'; // @phpstan-ignore-line
		wp_register_script(
			'wdo-asl-block-editor',
			ASL_URL_NP . 'build/js/block-editor.js',
			$metadata['dependencies'],
			$metadata['version'],
			array(
				'in_footer' => true,
			)
		);
		register_block_type(
			'ajax-search-lite/block-asl-main',
			array(
				'editor_script'   => 'wdo-asl-block-editor',
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * How to render the ajax-search-lite/block-asl-main block via ServerSideRender JSX component
	 *
	 * @param array{scType: integer, instance: integer} $atts
	 * @return string
	 */
	public function render( array $atts ): string {
		// Editor render
		if ( isset($_GET['context']) && $_GET['context'] === 'edit' ) { // phpcs:ignore: WordPress.Security.NonceVerification.Recommended
			return do_shortcode('[wd_asl include_styles=1]');

		}
		return do_shortcode('[wd_asl]');
	}
}
