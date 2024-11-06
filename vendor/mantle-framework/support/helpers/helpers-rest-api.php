<?php
/**
 * REST API schema functions
 *
 * @package Mantle
 */

namespace Mantle\Support\Helpers;

/**
 * Eases writing and reading schema with defaults and requirements.
 *
 * Ensures that all schema have a type, description, context, and default, and
 * that the schema keys are ordered consistently.
 *
 * @throws \BadFunctionCallException For unmet requirements.
 * @throws \InvalidArgumentException For incorrect argument types.
 *
 * @param string|array $description Attribute description or schema array.
 * @param array        $args        Remaining schema definition, if any.
 * @return array Completed schema definition.
 */
function fill_rest_schema( $description, array $args = [] ): array {
	// Pass a string to use it as the description and get all the defaults.
	if ( \is_string( $description ) ) {
		$args['description'] = $description;
	}

	if ( \is_array( $description ) && ! $args ) {
		$args = $description;
	}

	$args = \array_merge(
		[
			'context' => [
				'view',
			],
		],
		$args
	);

	if ( empty( $args['description'] ) ) {
		throw new \BadFunctionCallException( \__( 'Please supply a description.', 'mantle' ) );
	}

	if ( ! \is_array( $args['context'] ) ) {
		/*
		 * This value is not cast to an array to avoid reinforcing any
		 * impression that the API supports a string context.
		 */
		throw new \InvalidArgumentException(
			\sprintf(
				/* translators: 1: $context, 2: PHP type */
				\__( '%1$s must be of type %2$s', 'mantle' ),
				backtickit( '$context' ),
				\gettype( [] )
			)
		);
	}

	// 'object' is inferred if properties are referenced.
	if ( isset( $args['properties'] ) || isset( $args['additionalProperties'] ) ) {
		$args['type'] = 'object';
	}

	// At last, the default.
	if ( empty( $args['type'] ) ) {
		$args['type'] = 'string';
	}

	$args['default'] = default_from_rest_schema( $args );

	if ( 'array' === $args['type'] ) {
		if ( empty( $args['items'] ) ) {
			throw new \BadFunctionCallException( \__( 'Please supply schema for the array items.', 'mantle' ) );
		}

		if ( empty( $args['items']['type'] ) ) {
			throw new \BadFunctionCallException( \__( 'Please supply types for the array items.', 'mantle' ) );
		}
	}

	if ( 'object' === $args['type'] ) {
		if ( empty( $args['properties'] ) ) {
			$args['additionalProperties'] = true;
		} elseif ( ! isset( $args['additionalProperties'] ) ) {
			// 'additionalProperties' must be explicitly allowed if at least one property is declared.
			$args['additionalProperties'] = false;
		}
	}

	/**
	 * Filters the completed schema definition.
	 *
	 * @param array $args The schema definition array.
	 */
	$args = apply_filters( 'mantle_fill_rest_schema', $args );

	\ksort( $args );

	return $args;
}

/**
 * Get a default value for the provided schema's type and properties.
 *
 * @throws \InvalidArgumentException For unmet requirements.
 *
 * @param array $schema Schema.
 * @return mixed Default based on the schema.
 */
function default_from_rest_schema( array $schema ) {
	if ( \array_key_exists( 'default', $schema ) ) {
		return $schema['default'];
	}

	$default = null;

	if ( empty( $schema['type'] ) ) {
		return $default;
	}

	if ( \is_array( $schema['type'] ) && \count( $schema['type'] ) > 1 ) {
		throw new \InvalidArgumentException(
			/* translators: %s: 'default' */
			\__( 'Please supply a `default` for schema with multiple types.', 'mantle' ),
		);
	}

	if ( 'string' === $schema['type'] ) {
		$default = (string) $default;
	}

	if ( 'integer' === $schema['type'] ) {
		$default = (int) $default;
	}

	if ( 'number' === $schema['type'] ) {
		$default = (float) $default;
	}

	if ( 'array' === $schema['type'] ) {
		$default = (array) $default;
	}

	/*
	 * Objects in the REST API are represented in PHP as associative arrays,
	 * which are then encoded in JSON as objects. The exception is an object
	 * with no properties, which must be a PHP object because empty PHP arrays
	 * are encoded in JSON as arrays.
	 */
	if ( 'object' === $schema['type'] ) {
		$default = (array) $default;

		if ( isset( $schema['properties'] ) && \is_array( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as $property => $subschema ) {
				$default[ $property ] = default_from_rest_schema( $subschema );
			}
		}

		if ( ! $default ) {
			$default = (object) $default;
		}
	}

	return $default;
}
