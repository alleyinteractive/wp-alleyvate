<?php
/**
 * Register_Post_Type trait file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass
 */

namespace Mantle\Database\Model\Registration;

use Closure;
use Mantle\Database\Model\Model_Exception;
use Mantle\REST_API\REST_Field;
use Mantle\REST_API\REST_Field_Registrar;

/**
 * Model Trait to REST Fields to be registered for a model.
 */
trait Register_Rest_Fields {
	/**
	 * REST Field Registrar
	 *
	 * @var REST_Field_Registrar
	 */
	protected static $rest_registrar;

	/**
	 * REST API Fields for the model.
	 *
	 * @var array
	 */
	protected static $rest_fields = [];

	/**
	 * Register the post type.
	 */
	public static function boot_register_rest_fields(): void {
		static::$rest_registrar = new REST_Field_Registrar();

		\add_action( 'rest_api_init', [ self::class, 'register_fields' ] );
	}

	/**
	 * Register the fields for the model.
	 *
	 * @throws Model_Exception Thrown when registering a post type that is already registered.
	 */
	public static function register_fields(): void {
		if ( ! isset( static::$rest_registrar ) ) {
			$class = static::class;
			throw new Model_Exception( "REST field registrar is not defined for [{$class}]" );
		}

		array_map( [ static::$rest_registrar, 'register_once' ], static::$rest_fields );
	}

	/**
	 * Register a REST API field.
	 *
	 * @param REST_Field|string $attribute Field instance/field attribute to register.
	 * @param Closure|string    $get_callback Callback for the field if $field isn't a field.
	 * @return Rest_Field
	 *
	 * @throws Model_Exception Thrown on missing REST Registrar.
	 *
	 * @todo Allow for creation of a a REST Field from a SML REST Field.
	 */
	public static function register_field( $attribute, $get_callback = null ): Rest_Field {
		if ( ! isset( static::$rest_registrar ) ) {
			$class = static::class;
			throw new Model_Exception( "REST field registrar is not defined for [{$class}]" );
		}

		// Bail early if the field is a valid REST Field.
		if ( $attribute instanceof Rest_Field ) {
			static::$rest_fields[] = $attribute;
			return $attribute;
		}

		if ( is_null( $get_callback ) ) {
			throw new Model_Exception( "Missing callback for REST field [{$attribute}]" );
		}

		$field = new Rest_Field( [ static::get_object_name() ], $attribute, $get_callback );

		static::$rest_fields[ $attribute ] = $field;
		return $field;
	}
}
