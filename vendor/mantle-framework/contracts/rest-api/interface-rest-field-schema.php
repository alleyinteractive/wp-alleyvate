<?php
/**
 * Interface file for REST_Field_Schema
 *
 * @package Mantle
 */

namespace Mantle\Contracts\REST_API;

/**
 * Specifies a REST field that has schema.
 */
interface REST_Field_Schema {
	/**
	 * Get the field schema, if any.
	 *
	 * @return array|null Typically a schema array, but could be null.
	 */
	public function get_schema(): ?array;
}
