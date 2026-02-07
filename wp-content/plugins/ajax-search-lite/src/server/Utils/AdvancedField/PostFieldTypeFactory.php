<?php

namespace WPDRMS\ASL\Utils\AdvancedField;

use stdClass;
use WPDRMS\ASL\Utils\AdvancedField\Types\AdvancedFieldTypeInterface;
use WPDRMS\ASL\Utils\AdvancedField\Types\LegacyPostMetaFieldTypes;
use WPDRMS\ASL\Utils\AdvancedField\Types\LegacyTaxonomyFieldTypes;
use WPDRMS\ASL\Utils\AdvancedField\Types\PostMetaFieldTypes;
use WPDRMS\ASL\Utils\AdvancedField\Types\LegacyResultsFieldTypes;
use WPDRMS\ASL\Utils\AdvancedField\Types\ResultsFieldTypes;
use WPDRMS\ASL\Utils\AdvancedField\Types\TaxonomyFieldTypes;
use WPDRMS\ASL\Utils\AdvancedField\Types\WooCommercePrice;
use WPDRMS\ASL\Utils\AdvancedField\Types\WooCommerceAddToCart;

class PostFieldTypeFactory {

	/**
	 * Order obviously matter, but i am not going to explain why.
	 *
	 * @var array<class-string<AdvancedFieldTypeInterface>, string[]>
	 */
	private array $rules = array(
		LegacyResultsFieldTypes::class => array(
			'titlefield',
			'descriptionfield',
			'__id',
			'__title',
			'__content',
			'__post_type',
			'__link',
			'__url',
			'__image',
			'__date',
			'__author',
		),
		ResultsFieldTypes::class       => array( 'result_field' ),
		PostMetaFieldTypes::class      => array( 'custom_field' ),
		TaxonomyFieldTypes::class      => array( 'terms' ),
		WooCommercePrice::class        => array( 'woo_price_html' ),
		WooCommerceAddToCart::class    => array( 'woo_add_to_cart_html' ),
	);

	/**
	 * @param string               $field
	 * @param array<string, mixed> $field_args
	 * @param stdClass             $result
	 * @return AdvancedFieldTypeInterface
	 */
	public function create( string $field, array $field_args, stdClass $result ): AdvancedFieldTypeInterface {
		foreach ( $this->rules as $class => $fields ) {
			if ( in_array($field, $fields, true) ) {
				return new $class($field, $field_args, $result);
			}
		}
		if ( str_starts_with($field, '__tax_') || str_starts_with($field, '_taxonomy_') ) {
			return new LegacyTaxonomyFieldTypes($field, $field_args, $result);
		}
		return new LegacyPostMetaFieldTypes($field, $field_args, $result);
	}
}
