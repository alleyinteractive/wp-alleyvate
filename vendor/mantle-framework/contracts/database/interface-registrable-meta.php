<?php
/**
 * Registrable_Meta class file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Database;

/**
 * Registrable Model Meta Interface
 *
 * Provides methods to register a model's meta automatically.
 */
interface Registrable_Meta {
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
	public static function register_meta( string $meta_key, array $args = [] ): bool;
}
