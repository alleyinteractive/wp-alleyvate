<?php
/**
 * Has_Attributes trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Concerns;

use LogicException;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Relations\Relation;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\tap;

/**
 * Model Attributes
 */
trait Has_Attributes {
	use Has_Guarded_Attributes;
	use Hides_Attributes;

	/**
	 * Attributes for the model from the object
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Keep track of attributes that have been modified.
	 *
	 * @var array
	 */
	protected $modified_attributes = [];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array
	 */
	protected $casts = [];

	/**
	 * The accessors to append to the model's array form.
	 *
	 * @var array
	 */
	protected $appends = [];

	/**
	 * The built-in, primitive cast types supported by the model.
	 *
	 * @var array
	 */
	protected static $supported_cast_types = [
		'array',
		'bool',
		'boolean',
		'double',
		'float',
		'int',
		'integer',
		'json',
		'object',
		'real',
		'string',
		'timestamp',
	];

	/**
	 * Get an attribute from the model.
	 *
	 * @param string $attribute Attribute name.
	 * @return mixed
	 */
	public function get_attribute( string $attribute ) {
		// Retrieve the attribute from the object.
		if ( isset( $this->attributes[ $attribute ] ) || $this->has_get_mutator( $attribute ) ) {
			$value = $this->attributes[ $attribute ] ?? null;

			// Check if an attribute has a cast.
			if ( isset( $this->casts[ $attribute ] ) ) {
				$this->cast_attribute( $value, $this->casts[ $attribute ] );
			}

			// Pass the attribute to the mutator.
			if ( $this->has_get_mutator( $attribute ) ) {
				$value = $this->mutate_attribute( $attribute, $value );
			}
		} elseif ( 'ID' !== $attribute ) {
			$value = $this->get_relation_value( $attribute );
		}

		return $value ?? null;
	}

	/**
	 * Retrieve a relationship value.
	 *
	 * @param string $key Relation name.
	 * @return \Mantle\Database\Model\Relations\Relation|null
	 */
	public function get_relation_value( string $key ) {
		if ( 'ID' === $key ) {
			return null;
		}

		if ( array_key_exists( $key, $this->relations ) ) {
			return $this->relations[ $key ];
		}

		if ( method_exists( $this, $key ) ) {
			return $this->get_relationship_from_method( $key );
		}

		return null;
	}

	/**
	 * Retrieve a relationship from a method.
	 *
	 * @param string $method
	 * @return Relation
	 *
	 * @throws LogicException Thrown if the relationship method is not an instance
	 *                        of Relation.
	 */
	protected function get_relationship_from_method( string $method ) {
		$relation = $this->$method();

		if ( ! $relation instanceof Relation ) {
			throw new LogicException(
				sprintf(
					'%s::%s must return a relationship instance.',
					static::class,
					$method
				)
			);
		}

		return tap(
			$relation->get_results(),
			function ( $relation ) use ( $method ): void {
				$this->set_relation( $method, $relation );
			}
		);
	}

	/**
	 * Set a model attribute.
	 *
	 * @todo Add cast support.
	 *
	 * @param string $attribute Attribute name.
	 * @param mixed  $value Value to set.
	 * @return static
	 *
	 * @throws Model_Exception Thrown when trying to set 'id'.
	 */
	public function set_attribute( string $attribute, $value ) {
		if ( $this->is_guarded( $attribute ) ) {
			throw new Model_Exception( "Unable to set '{$attribute} on model." );
		}

		if ( $this->has_set_mutator( $attribute ) ) {
			$value = $this->mutate_set_attribute( $attribute, $value );
		} else {
			$this->attributes[ $attribute ] = $value;
		}

		$this->modified_attributes[] = $attribute;

		return $this;
	}

	/**
	 * Set a raw attribute on the model.
	 *
	 * @param string $attribute Attribute name.
	 * @param mixed  $value Value to set.
	 * @return static
	 */
	public function set_raw_attribute( string $attribute, $value ) {
		$this->attributes[ $attribute ] = $value;

		return $this;
	}

	/**
	 * Get all model attributes.
	 */
	public function get_attributes(): array {
		$attributes = [];

		foreach ( $this->attributes as $key => $value ) {
			$attributes[ $key ] = $this->get_attribute( $key );
		}

		return $attributes;
	}

	/**
	 * Get an attribute array of all arrayable attributes.
	 */
	protected function get_arrayable_attributes(): array {
		return $this->get_arrayable_items( $this->get_attributes() );
	}

	/**
	 * Convert the models' attributes to an array.
	 */
	public function attributes_to_array(): array {
		// Retrieve all attributes, passing them through the mutators.
		$attributes = collect( $this->get_arrayable_attributes() )
			->map(
				fn ( $value, string $attribute ) => $this->get_attribute( $attribute )
			)
			->merge( $this->get_arrayable_appends() );

		return $attributes->to_array();
	}

	/**
	 * Get an attribute array of all arrayable values.
	 *
	 * Filters out any hidden attribute that shouldn't appear and only includes
	 * visible attributes if set.
	 *
	 * @param string[] $values Values to check.
	 */
	protected function get_arrayable_items( array $values ): array {
		$visible = $this->get_visible();
		$hidden  = $this->get_hidden();

		if ( ! empty( $visible ) ) {
			$values = array_intersect_key( $values, array_flip( $visible ) );
		}

		if ( ! empty( $hidden ) ) {
			return array_diff_key( $values, array_flip( $hidden ) );
		}

		return $values;
	}

	/**
	 * Get the raw model attributes.
	 */
	public function get_raw_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Get all modified attributes.
	 */
	public function get_modified_attributes(): array {
		if ( empty( $this->modified_attributes ) ) {
			return [];
		}

		$attributes = [];
		foreach ( array_unique( $this->modified_attributes ) as $attribute ) {
			$attributes[ $attribute ] = $this->attributes[ $attribute ] ?? null;
		}

		return $attributes;
	}

	/**
	 * Check if an attribute has been modified.
	 *
	 * @param string $attribute Attribute to check.
	 */
	public function is_attribute_modified( string $attribute ): bool {
		return in_array( $attribute, $this->modified_attributes, true );
	}

	/**
	 * Set an array of attributes.
	 *
	 * @param array $attributes Attributes to set.
	 * @return static
	 */
	public function set_attributes( array $attributes ) {
		foreach ( $attributes as $key => $value ) {
			$this->set( $key, $value );
		}

		return $this;
	}

	/**
	 * Set the raw attributes on the model.
	 *
	 * @param array $attributes Raw attributes to set.
	 * @return static
	 */
	public function set_raw_attributes( array $attributes ) {
		$this->attributes = $attributes;
		return $this;
	}

	/**
	 * Reset the modified attributes.
	 */
	protected function reset_modified_attributes() {
		$this->modified_attributes = [];
	}

	/**
	 * Cast an attribute to a specific value.
	 *
	 * @todo Add date, collection cast types.
	 *
	 * @param mixed  $value Attribute value.
	 * @param string $cast_type Cast type.
	 * @return mixed
	 */
	protected function cast_attribute( $value, string $cast_type ) {
		return match ( $cast_type ) {
			'int', 'integer' => (int) $value,
			'real', 'float', 'double' => $this->from_float( $value ),
			'string' => (string) $value,
			'bool', 'boolean' => (bool) $value,
			'object' => $this->from_json( $value, true ),
			'array', 'json' => $this->from_json( $value ),
			default => $value,
		};
	}

	/**
	 * Decode the given float.
	 *
	 * @param  mixed $value Value to decode.
	 */
	public function from_float( $value ): float {
		return match ( (string) $value ) {
			'Infinity' => INF,
			'-Infinity' => -INF,
			'NaN' => NAN,
			default => (float) $value,
		};
	}

	/**
	 * Encode the given value as JSON.
	 *
	 * @param mixed $value Value to encode.
	 */
	protected function as_json( $value ): string {
		return \wp_json_encode( $value );
	}

	/**
	 * Decode the given JSON back into an array or object.
	 *
	 * @param string $value Value to convert.
	 * @param bool   $as_object Flag as an object.
	 * @return mixed
	 */
	public function from_json( string $value, bool $as_object = false ) {
		return json_decode( $value, ! $as_object );
	}

	/**
	 * Get the mutator method name for an attribute.
	 *
	 * @param string $attribute Attribute name.
	 */
	public function get_mutator_method_name( string $attribute ): string {
		return 'get_' . strtolower( $attribute ) . '_attribute';
	}

	/**
	 * Get the set mutator method name for an attribute.
	 *
	 * @param string $attribute Attribute name.
	 */
	public function get_set_mutator_method_name( string $attribute ): string {
		return 'set_' . strtolower( $attribute ) . '_attribute';
	}

	/**
	 * Check if the attribute has a get mutator.
	 *
	 * @param string $attribute Attribute to check.
	 */
	public function has_get_mutator( string $attribute ): bool {
		return method_exists( $this, $this->get_mutator_method_name( $attribute ) );
	}

	/**
	 * Check if the attribute has a set mutator.
	 *
	 * @param string $attribute Attribute to check.
	 */
	public function has_set_mutator( string $attribute ): bool {
		return method_exists( $this, $this->get_set_mutator_method_name( $attribute ) );
	}

	/**
	 * Pass an attribute through a get mutator.
	 *
	 * @param string $attribute Attribute to check.
	 * @param mixed  $value Attribute value.
	 * @return mixed
	 */
	public function mutate_attribute( string $attribute, $value ) {
		return $this->{ $this->get_mutator_method_name( $attribute ) }( $value );
	}

	/**
	 * Pass an attribute through a set mutator.
	 *
	 * @param string $attribute Attribute to check.
	 * @param mixed  $value Attribute value.
	 * @return mixed
	 */
	public function mutate_set_attribute( string $attribute, $value ) {
		return $this->{ $this->get_set_mutator_method_name( $attribute ) }( $value );
	}

	/**
	 * Set the accessors to append to model arrays.
	 *
	 * @param string|string[] ...$appends Accessors to append.
	 * @return static
	 */
	public function set_appends( ...$appends ) {
		$this->appends = $appends;
		return $this;
	}

	/**
	 * Check if an attribute is being appended.
	 *
	 * @param string $attribute Attribute to check.
	 */
	public function has_appended( string $attribute ): bool {
		return in_array( $attribute, $this->appends, true );
	}

	/**
	 * Append attributes to the model arrays.
	 *
	 * @param string|string[] ...$attributes Attributes to append.
	 * @return static
	 */
	public function append( ...$attributes ) {
		$this->appends = array_unique(
			array_merge( $this->appends, $attributes )
		);

		return $this;
	}

	/**
	 * Retrieve all the appendable values in an array.
	 */
	public function get_arrayable_appends(): array {
		if ( empty( $this->appends ) ) {
			return [];
		}

		return $this->get_arrayable_items(
			collect( $this->appends )
				->combine(
					collect( $this->appends )->map(
						fn ( string $attribute ) => $this->get_attribute( $attribute )
					)
				)
				->to_array()
		);
	}
}
