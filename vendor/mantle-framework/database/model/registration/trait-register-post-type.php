<?php
/**
 * Register_Post_Type trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Registration;

use Mantle\Database\Model\Concerns\Custom_Post_Permalink;
use Mantle\Database\Model\Model_Exception;

/**
 * Model Trait to allow a post type to be registered for a model.
 */
trait Register_Post_Type {
	use Custom_Post_Permalink;

	/**
	 * Register the post type.
	 */
	public static function boot_register_post_type(): void {
		\add_action( 'init', [ self::class, 'register_object' ] );
	}

	/**
	 * Register the post type for the model.
	 *
	 * @throws Model_Exception Thrown when registering a post type that is already registered.
	 */
	public static function register_object(): void {
		$post_type = static::get_object_name();

		if ( \post_type_exists( $post_type ) ) {
			throw new Model_Exception( 'Unable to register post type (post type already exists): ' . $post_type );
		}

		$register = \register_post_type( $post_type, static::get_registration_args() ); // phpcs:ignore WordPress.NamingConventions.ValidPostTypeSlug.NotStringLiteral

		if ( is_wp_error( $register ) ) {
			throw new Model_Exception( 'Error registering post type: ' . $register->get_error_message() );
		}
	}
}
