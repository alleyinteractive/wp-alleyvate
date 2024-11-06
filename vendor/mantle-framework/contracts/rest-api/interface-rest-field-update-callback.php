<?php
/**
 * Interface file for REST_Update_Callback
 *
 * @package Mantle
 */

namespace Mantle\Contracts\REST_API;

/**
 * Specifies a REST field that implements an `update_callback()`.
 */
interface REST_Field_Update_Callback {
	/**
	 * The callback function used to update the field value.
	 *
	 * @param mixed            $value       Submitted field value.
	 * @param mixed            $object      REST API data object.
	 * @param string           $field_name  Field name.
	 * @param \WP_REST_Request $request     REST API request.
	 * @param string           $object_type Object type.
	 * @return mixed True on success, \WP_Error object if a field cannot be updated.
	 */
	public function update_callback( $value, $object, string $field_name, \WP_REST_Request $request, string $object_type );
}
