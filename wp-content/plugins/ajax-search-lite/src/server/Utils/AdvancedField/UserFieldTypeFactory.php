<?php

namespace WPDRMS\ASL\Utils\AdvancedField;

use stdClass;
use WPDRMS\ASL\Utils\AdvancedField\Types\AdvancedFieldTypeInterface;
use WPDRMS\ASL\Utils\AdvancedField\Types\LegacyResultsFieldTypes;
use WPDRMS\ASL\Utils\AdvancedField\Types\LegacyUserMetaFieldTypes;
use WPDRMS\ASL\Utils\AdvancedField\Types\ResultsFieldTypes;
use WPDRMS\ASL\Utils\AdvancedField\Types\UserMetaFieldTypes;

class UserFieldTypeFactory {

	/**
	 * Order obviously matter, but i am not going to explain why.
	 *
	 * @var array<class-string<AdvancedFieldTypeInterface>, string[]>
	 */
	private array $rules = array();

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
		return new LegacyUserMetaFieldTypes($field, $field_args, $result);
	}
}
