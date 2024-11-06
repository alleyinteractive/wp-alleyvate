<?php
/**
 * Interface file for REST_Field
 *
 * @package Mantle
 */

namespace Mantle\Contracts\REST_API;

/**
 * Specifies a registrable REST API field.
 *
 * @see register_rest_field().
 */
interface REST_Field {
	/**
	 * Get the object(s) the field is being registered to.
	 *
	 * @return string|array Object type or types.
	 */
	public function get_object_types();

	/**
	 * Get the attribute name.
	 *
	 * @return string Attribute name.
	 */
	public function get_attribute(): string;
}
