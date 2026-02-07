<?php

namespace WPDRMS\ASL\Core;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

use WPDRMS\ASL\BlockEditor\ASLBlock;
use WPDRMS\ASL\Patterns\SingletonTrait;
use WPDRMS\ASL\Options\Routes\SearchOptionsRoute;
use WPDRMS\ASL\Options\Routes\TaxonomyTermsRoute;

/**
 * Returns all class instances for a given interface name
 *
 * @see .phpstorm.meta.php for corrected type hints
 */
class Factory {
	use SingletonTrait;

	const SUPPORTED_INTERFACES = array(
		'Rest'  => array(
			TaxonomyTermsRoute::class,
			SearchOptionsRoute::class,
		),
		'Block' => array(
			ASLBlock::class,
		),
	);

	/**
	 * Get all the objects array for a given interface
	 *
	 * @param key-of<self::SUPPORTED_INTERFACES> $interface_name
	 * @param mixed[]                            $args
	 */
	public function get( string $interface_name, ?array $args = null ): array {
		if ( !isset(self::SUPPORTED_INTERFACES[ $interface_name ]) ) {
			return array();
		}
		$classes = self::SUPPORTED_INTERFACES[ $interface_name ];
		return array_map(
			function ( $class_name ) use ( $args ) {
				if ( method_exists($class_name, 'instance') ) {
					if ( is_array($args) ) {
						return $class_name::instance(...$args);
					} else {
						return $class_name::instance();
					}
				}
				if ( is_array($args) ) {
					return new $class_name(...$args); // @phpstan-ignore-line
				} else {
					return new $class_name(); // @phpstan-ignore-line
				}
			},
			$classes
		);
	}
}
