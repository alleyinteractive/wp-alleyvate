<?php
/**
 * Register_Meta trait file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass
 */

namespace Mantle\Database\Model\Registration;

use Mantle\Database\Model;

use function Mantle\Support\Helpers\event;

/**
 * Model Trait to register meta for a model.
 */
trait Register_Meta {
	/**
	 * Register the object's meta.
	 */
	public static function boot_register_meta(): void {
		\add_action( 'init', [ self::class, 'register_meta' ], 11 );
	}

	/**
	 * Register a meta field for the model.
	 *
	 * @see register_meta()
	 *
	 * @param string $meta_key Meta key to register.
	 * @param array  $args {
	 *     Data used to describe the meta key when registered.
	 *
	 *     @type string     $object_subtype    A subtype; e.g. if the object type is "post", the post type. If left empty,
	 *                                         the meta key will be registered on the entire object type. Default empty.
	 *     @type string     $type              The type of data associated with this meta key.
	 *                                         Valid values are 'string', 'boolean', 'integer', 'number', 'array', and 'object'.
	 *     @type string     $description       A description of the data attached to this meta key.
	 *     @type bool       $single            Whether the meta key has one value per object, or an array of values per object.
	 *     @type mixed      $default           The default value returned from get_metadata() if no value has been set yet.
	 *                                         When using a non-single meta key, the default value is for the first entry.
	 *                                         In other words, when calling get_metadata() with `$single` set to `false`,
	 *                                         the default value given here will be wrapped in an array.
	 *     @type callable   $sanitize_callback A function or method to call when sanitizing `$meta_key` data.
	 *     @type callable   $auth_callback     Optional. A function or method to call when performing edit_post_meta,
	 *                                         add_post_meta, and delete_post_meta capability checks.
	 *     @type bool|array $show_in_rest      Whether data associated with this meta key can be considered public and
	 *                                         should be accessible via the REST API. A custom post type must also declare
	 *                                         support for custom fields for registered meta to be accessible via REST.
	 *                                         When registering complex meta values this argument may optionally be an
	 *                                         array with 'schema' or 'prepare_callback' keys instead of a boolean.
	 * }
	 * @return bool True if the meta key was successfully registered in the global array, false if not.
	 *              Registering a meta key with distinct sanitize and auth callbacks will fire those callbacks,
	 *              but will not add to the global registry.
	 */
	public static function register_meta( string $meta_key, array $args = [] ): bool {
		$args = array_merge(
			event(
				'mantle_register_meta_default_args',
				[
					[
						'object_subtype' => static::get_object_name(),
						'show_in_rest'   => true,
						'single'         => true,
						'type'           => 'string',
					],
				],
			),
			$args,
		);

		return register_meta( static::get_object_type(), $meta_key, $args );
	}

	/**
	 * Retrieve the object type for the model.
	 */
	public static function get_object_type(): ?string {
		$parent = get_parent_class( static::class );

		if ( Model\Post::class === $parent ) {
			return 'post';
		}

		if ( Model\Term::class === $parent ) {
			return 'term';
		}

		if ( Model\User::class === $parent ) {
			return 'user';
		}

		if ( Model\Comment::class === $parent ) {
			return 'comment';
		}

		return null;
	}
}
