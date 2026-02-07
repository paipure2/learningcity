<?php
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

class WD_ASL_Scripts {
	private static $_instance;

	private $scripts = array(
		'wd-asl-ajaxsearchlite'     => array(
			'src'    => 'js/{js_source}/plugin/merged/asl.js',
			'prereq' => false,
		),
		'wd-asl-prereq-and-wrapper' => array(
			'src'    => 'js/{js_source}/plugin/merged/asl-prereq-and-wrapper.js',
			'prereq' => false,
		),
	);

	private $optimized_scripts = array(
		'wd-asl-ajaxsearchlite' => array(
			'wd-asl-ajaxsearchlite-prereq'       => array(
				'handle' => 'wd-asl-ajaxsearchlite',    // Handle alias, for the enqueue
				'src'    => 'js/{js_source}/plugin/optimized/asl-prereq.js',
			),
			'wd-asl-ajaxsearchlite-core'         => array(
				'src' => 'js/{js_source}/plugin/optimized/asl-core.js',
			),
			'wd-asl-ajaxsearchlite-settings'     => array(
				'src'    => 'js/{js_source}/plugin/optimized/asl-settings.js',
				'prereq' => array( 'wd-asl-ajaxsearchlite' ),
			),
			'wd-asl-ajaxsearchlite-vertical'     => array(
				'src'    => 'js/{js_source}/plugin/optimized/asl-results-vertical.js',
				'prereq' => array( 'wd-asl-ajaxsearchlite' ),
			),
			'wd-asl-ajaxsearchlite-ga'           => array(
				'src'    => 'js/{js_source}/plugin/optimized/asl-ga.js',
				'prereq' => array( 'wd-asl-ajaxsearchlite' ),
			),
			'wd-asl-ajaxsearchlite-autocomplete' => array(
				'src'    => 'js/{js_source}/plugin/optimized/asl-autocomplete.js',
				'prereq' => array( 'wd-asl-ajaxsearchlite' ),
			),
			'wd-asl-ajaxsearchlite-wrapper'      => array(
				'src'    => 'js/{js_source}/plugin/optimized/asl-wrapper.js',
				'prereq' => true, // TRUE => previously loaded script
			),
			'wd-asl-ajaxsearchlite-load-async'   => array(
				'src'    => 'js/{js_source}/plugin/optimized/asl-load-async.js',
				'prereq' => true, // TRUE => previously loaded script
			),
		),
	);

	private function __construct() {}

	public function get( $handles = array(), $minified = true, $optimized = false, $except = array() ) {
		$handles   = is_string($handles) ? array( $handles ) : $handles;
		$handles   = count($handles) === 0 ? array_keys($this->scripts) : $handles;
		$js_source = $minified ? 'min' : 'nomin';
		$return    = array();

		foreach ( $handles as $handle ) {
			if ( in_array($handle, $except, true) || !$this->isRequired($handle) ) {
				continue;
			}
			if ( isset($this->scripts[ $handle ]) ) {
				if ( $optimized && isset($this->optimized_scripts[ $handle ]) ) {
					$prev_handle = '';
					foreach ( $this->optimized_scripts[ $handle ] as $optimized_script_handle => $optimized_script ) {
						if ( in_array($optimized_script_handle, $except, true) || !$this->isRequired($optimized_script_handle) ) {
							continue;
						}
						$prereq = !isset($optimized_script['prereq']) || $optimized_script['prereq'] === false ? array() : $optimized_script['prereq'];
						if ( $prereq === true ) {
							$prereq = array( $prev_handle );
						}
						$return[] = array(
							'handle' => isset($optimized_script['handle']) ? $optimized_script['handle'] : $optimized_script_handle,
							'src'    => ASL_URL . str_replace(
								array( '{js_source}' ),
								array( $js_source ),
								$js_source === 'min' ? str_replace('.js', '.min.js', $optimized_script['src']) : $optimized_script['src']
							),
							'prereq' => $prereq,
						);

						$prev_handle = $optimized_script_handle;
					}
					continue;
				}

				$return[] = array(
					'handle' => $handle,
					'src'    => ASL_URL . str_replace(
						array( '{js_source}' ),
						array( $js_source ),
						$js_source === 'min' ? str_replace('.js', '.min.js', $this->scripts[ $handle ]['src']) : $this->scripts[ $handle ]['src'],
					),
					'prereq' => $this->scripts[ $handle ]['prereq'],
				);
			} elseif ( $optimized && wd_in_array_r($handle, $this->optimized_scripts) ) {
				foreach ( $this->optimized_scripts as $opt_handle => $scripts ) {
					if ( isset($scripts[ $handle ]) ) {
						$return[] = array(
							'handle' => $handle,
							'src'    => ASL_URL . str_replace(
								array( '{js_source}' ),
								array( $js_source ),
								$js_source === 'min' ? str_replace('.js', '.min.js', $scripts[ $handle ]['src']) : $scripts[ $handle ]['src']
							),
							'prereq' => $scripts[ $handle ]['prereq'],
						);
					}
				}
			}
		}

		return $return;
	}

	public function enqueue( $scripts = array(), $args = array() ) {
		$defaults = array(
			'media_query' => '',
			'in_footer'   => true,
			'prereq'      => array(),
		);
		$args     = wp_parse_args($args, $defaults);
		foreach ( $scripts as $script ) {
			if ( isset($script['prereq']) ) {
				if ( $script['prereq'] === false ) {
					$prereq = array();
				} else {
					$prereq = $script['prereq'];
				}
			} else {
				$prereq = $args['prereq'];
			}
			wp_register_script(
				$script['handle'],
				$script['src'],
				$prereq,
				$args['media_query'],
				$args['in_footer']
			);
			wp_enqueue_script($script['handle']);
		}

		return $scripts;
	}

	public function isRequired( $handle ) {
		if ( wd_asl()->manager->getContext() === 'backend' ) {
			return true;
		}

		$required = false;
		switch ( $handle ) {
			case 'wd-asl-ajaxsearchlite-settings':
				if ( asl_is_asset_required('settings') ) {
					$required = true;
				}
				break;
			case 'wd-asl-ajaxsearchlite-vertical':
				if ( asl_is_asset_required('vertical') ) {
					$required = true;
				}
				break;
			case 'wd-asl-ajaxsearchlite-autocomplete':
				if ( asl_is_asset_required('autocomplete') ) {
					$required = true;
				}
				break;
			case 'wd-asl-ajaxsearchlite-ga':
				if ( asl_is_asset_required('ga') ) {
					$required = true;
				}
				break;
			default:
				$required = true;
				break;
		}

		return $required;
	}

	/**
	 * Get the instane
	 *
	 * @return self
	 */
	public static function getInstance() {
		if ( !( self::$_instance instanceof self ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
